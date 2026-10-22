<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * PINTU's client for the read-only employee API of SIKAP (D-075; contract in
 * docs/API-INTEGRASI.md of the SIKAP repository), following LookUpVehicle:
 * PINTU's own API key as a bearer token, only to an https:// address (plain
 * http only to this machine in local development), a 3-second connect and an
 * 8-second overall timeout, no redirects (the key must never follow one), at
 * most two retries and only for dropped connections and HTTP 502/503/504, and
 * Retry-After honoured after HTTP 429. A failure logs only its reason, HTTP
 * status and SIKAP error code: never the key, a search, a NIP or anyone's
 * contact details.
 */
class SikapClient
{
    /**
     * Cache key holding the time until which SIKAP asked PINTU to wait (HTTP 429).
     */
    public const string WAIT_UNTIL = 'sikap:tunggu-sampai';

    /**
     * Retries after a dropped connection or a gateway failure.
     */
    private const int RETRIES = 2;

    /**
     * Hosts a local SIKAP (npm run dev:api) may be reached on over plain http.
     */
    private const array LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

    /**
     * GET a path of the API, such as "/pegawai". A 404 is an answer only
     * where the caller asks for one record; elsewhere it means a wrong
     * address and counts as a failure.
     *
     * @param  array<string, string|int>  $query
     * @return array{status: 'ok', body: array<mixed>}|array{status: 'not_found'}|array{status: 'unavailable', message: string}
     */
    public function get(string $path, array $query = [], bool $notFoundIsAnswer = false): array
    {
        $waitUntil = (int) Cache::get(self::WAIT_UNTIL, 0);
        if ($waitUntil > now()->getTimestamp()) {
            return $this->busy($waitUntil - now()->getTimestamp());
        }

        $baseUrl = rtrim(trim((string) config('services.sikap.base_url')), '/');
        $key = trim((string) config('services.sikap.key'));

        if (! $this->isAllowedAddress($baseUrl) || $key === '') {
            Log::error('SIKAP API is not configured');

            return ['status' => 'unavailable', 'message' => 'Pencarian pegawai SIKAP belum dikonfigurasi.'];
        }

        $timeout = config()->integer('services.sikap.timeout');

        for ($attempt = 0; ; $attempt++) {
            $mayRetry = $attempt < self::RETRIES;

            try {
                $response = Http::withToken($key)
                    ->acceptJson()
                    ->withOptions(['allow_redirects' => false])
                    ->connectTimeout(3)
                    ->timeout($timeout >= 1 && $timeout <= 60 ? $timeout : 8)
                    ->get($baseUrl.$path, $query);
            } catch (ConnectionException $exception) {
                // A timeout is not retried; a dropped connection is
                $timedOut = str_contains($exception->getMessage(), 'cURL error 28');
                if (! $timedOut && $mayRetry) {
                    $this->pause();

                    continue;
                }

                return $this->failed(null, $timedOut ? 'timeout' : 'network');
            }

            if ($response->status() === 404 && $notFoundIsAnswer) {
                return ['status' => 'not_found'];
            }

            if ($response->status() === 429) {
                $seconds = $this->retryAfter($response->header('Retry-After'));
                Cache::put(self::WAIT_UNTIL, now()->getTimestamp() + $seconds, $seconds);
                $this->failed($response, 'rate_limited');

                return $this->busy($seconds);
            }

            if (in_array($response->status(), [502, 503, 504], true) && $mayRetry) {
                $this->pause();

                continue;
            }

            if ($response->status() !== 200) {
                return $this->failed($response, 'http');
            }

            $body = $this->json($response);

            return is_array($body) ? ['status' => 'ok', 'body' => $body] : $this->failed($response, 'invalid_payload');
        }
    }

    /**
     * An employee as PINTU uses them, from an item of SIKAP's search or its
     * detail: NIP and name, with position and unit only for the picker. Any
     * other field, such as e-mail or phone number, is dropped. Null when the
     * NIP is not 18 digits or the name is missing or too long for an account.
     *
     * @return array{nip: string, nama: string, jabatan: ?string, unit: ?string}|null
     */
    public function employee(mixed $item): ?array
    {
        $nip = is_array($item) ? ($item['nip'] ?? null) : null;
        $name = is_array($item) ? $this->text($item['nama'] ?? null) : null;

        if (! is_string($nip) || preg_match('/^\d{18}\z/', $nip) !== 1 || $name === null || mb_strlen($name) > 255) {
            return null;
        }

        return [
            'nip' => $nip,
            'nama' => $name,
            'jabatan' => $this->text($item['jabatan'] ?? null),
            'unit' => $this->text(data_get($item, 'unit.nama')),
        ];
    }

    /**
     * Report an answer of SIKAP that PINTU cannot use.
     *
     * @return array{status: 'unavailable', message: string}
     */
    public function invalidAnswer(): array
    {
        return $this->failed(null, 'invalid_payload');
    }

    /**
     * Whether the API key may be sent to this address: https, or http to
     * this machine while developing locally.
     */
    private function isAllowedAddress(string $baseUrl): bool
    {
        if (str_starts_with($baseUrl, 'https://')) {
            return true;
        }

        return app()->isLocal()
            && str_starts_with($baseUrl, 'http://')
            && in_array(parse_url($baseUrl, PHP_URL_HOST), self::LOOPBACK_HOSTS, true);
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return array<mixed>|null
     */
    private function json(Response $response): ?array
    {
        $body = str_contains(strtolower($response->header('Content-Type')), 'application/json') ? $response->json() : null;

        return is_array($body) ? $body : null;
    }

    /**
     * Seconds to wait from a Retry-After header (seconds or an HTTP date);
     * 60 when it is missing or unreadable, at most an hour.
     */
    private function retryAfter(string $header): int
    {
        $header = trim($header);

        if (ctype_digit($header) && (int) $header > 0) {
            return min((int) $header, 3600);
        }

        $until = $header === '' ? false : strtotime($header);

        return $until !== false && $until > now()->getTimestamp() ? min($until - now()->getTimestamp(), 3600) : 60;
    }

    /**
     * @return array{status: 'unavailable', message: string}
     */
    private function busy(int $seconds): array
    {
        return ['status' => 'unavailable', 'message' => "SIKAP sedang membatasi permintaan. Coba lagi dalam {$seconds} detik."];
    }

    /**
     * Log a failed request with its reason, HTTP status and SIKAP error code
     * only. A refused key is named, so administrators know to ask SIKAP's
     * Admin for a new one.
     *
     * @return array{status: 'unavailable', message: string}
     */
    private function failed(?Response $response, string $reason): array
    {
        $code = $response !== null ? data_get($this->json($response), 'error.code') : null;

        Log::warning('SIKAP API request failed', [
            'reason' => $reason,
            'http_status' => $response?->status(),
            'error_code' => is_string($code) ? $code : null,
        ]);

        return ['status' => 'unavailable', 'message' => match ($response?->status()) {
            401 => 'Kunci API SIKAP ditolak: tidak valid, sudah dihapus, atau kedaluwarsa. Minta Admin SIKAP membuat kunci baru untuk PINTU.',
            403 => 'Kunci API SIKAP tidak punya izin membaca data pegawai. Minta Admin SIKAP membuat kunci dengan izin pegawai:baca.',
            default => 'SIKAP sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.',
        }];
    }

    /**
     * Back off briefly with jitter before a retry.
     */
    private function pause(): void
    {
        Sleep::for(200 + random_int(0, 100))->milliseconds();
    }
}
