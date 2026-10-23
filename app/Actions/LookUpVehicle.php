<?php

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * Look a vehicle up in the private vehicle API (D-041), following the
 * server-side adapter of kalkulator-pajak (src/services/vehicle-data):
 * PINTU's own Data Potensi API key as a Bearer token, which only covers
 * vehicles of UPT 010 Kota Kupang (D-076), and PINTU's own Cloudflare Access
 * Service Token while Access still guards the host; an 8-second timeout, at most
 * one retry and only for network failures and HTTP 502/503/504, no
 * redirects (the token must never follow one), and Retry-After honoured
 * after HTTP 429. Outside BPAD's service hours the API is not asked at all
 * (D-042). Nothing about the vehicle or its owner is logged or
 * stored; a failure logs only the upstream request ID, status and error code.
 */
class LookUpVehicle
{
    /**
     * Cache key holding the time until which the API asked PINTU to wait (HTTP 429).
     */
    public const string WAIT_UNTIL = 'njkb:tunggu-sampai';

    /**
     * What a vehicle the API knows but PINTU's key may not read is (HTTP 403
     * region_not_allowed, D-076).
     */
    public const string OUTSIDE_REGION = 'Kendaraan terdaftar di luar wilayah Kota Kupang.';

    /**
     * Time zone of BPAD's service hours.
     */
    private const string TIME_ZONE = 'Asia/Makassar';

    /**
     * Weight per vehicle kind when the API has no NJKB reference, as BOBOT_MAP
     * in kalkulator-pajak.
     *
     * @var array<string, float>
     */
    private const array WEIGHTS = [
        'SEPEDA MOTOR' => 1.0,
        'MINIBUS' => 1.05,
        'PICK UP' => 1.085,
        'SEDAN' => 1.025,
        'JEEP' => 1.05,
        'LIGHT TRUCK' => 1.3,
        'MICROBUS' => 1.085,
        'TRUCK' => 1.4,
    ];

    /**
     * Safe messages for NJKB references that cannot be used.
     *
     * @var array<string, string>
     */
    private const array REFERENCE_PROBLEMS = [
        'reference_unavailable' => 'Referensi NJKB belum tersedia untuk kendaraan ini.',
        'conflict' => 'Data kendaraan bertentangan dengan referensi NJKB.',
        'ambiguous' => 'Referensi NJKB kendaraan ini perlu diperiksa petugas.',
    ];

    /**
     * A plate number in capitals with letters and digits only: "dh 2506-ka"
     * becomes "DH2506KA".
     */
    public static function normalise(string $nopol): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($nopol));
    }

    /**
     * Whether a normalised plate number has the form the API accepts: one or
     * two letters, one to four digits and up to three letters.
     */
    public static function isPlateNumber(string $nopol): bool
    {
        return preg_match('/^[A-Z]{1,2}\d{1,4}[A-Z]{0,3}$/', $nopol) === 1;
    }

    /**
     * The vehicle behind a plate number. "vehicle" is what the tax calculator
     * needs; "chassis" is only for checking a visitor's chassis digits and is
     * never sent to a browser. A vehicle registered outside Kota Kupang comes
     * back without any data at all.
     *
     * @return array{status: 'found', vehicle: array{nopol: string, nama: ?string, jenis: ?string, jatuhTempoStnk: ?string, jatuhTempoPajak: ?string, njkb: int, njub: int, bobot: float, warnaPlat: ?string, njkbStatus: string}, chassis: ?string}|array{status: 'reference_problem', message: string, chassis: ?string}|array{status: 'outside_region', message: string}|array{status: 'not_found'}|array{status: 'unavailable', message: string}
     */
    public function handle(string $nopol): array
    {
        $nopol = self::normalise($nopol);

        if (! self::isPlateNumber($nopol)) {
            return ['status' => 'not_found'];
        }

        $waitUntil = (int) Cache::get(self::WAIT_UNTIL, 0);
        if ($waitUntil > now()->getTimestamp()) {
            return $this->busy($waitUntil - now()->getTimestamp());
        }

        $baseUrl = rtrim((string) config('services.njkb.base_url'), '/');
        $apiKey = trim((string) config('services.njkb.api_key'));
        $clientId = trim((string) config('services.njkb.client_id'));
        $clientSecret = trim((string) config('services.njkb.client_secret'));
        $accessHeaders = $clientId !== '' && $clientSecret !== ''
            ? ['CF-Access-Client-Id' => $clientId, 'CF-Access-Client-Secret' => $clientSecret]
            : [];

        if (! str_starts_with($baseUrl, 'https://') || ($apiKey === '' && $accessHeaders === [])) {
            Log::error('NJKB API is not configured');

            return ['status' => 'unavailable', 'message' => 'Pencarian data kendaraan belum dikonfigurasi.'];
        }

        $closed = $this->outsideServiceHours();
        if ($closed !== null) {
            return ['status' => 'unavailable', 'message' => $closed];
        }

        $timeout = config()->integer('services.njkb.timeout');
        $url = "{$baseUrl}/api/v1/vehicle/".rawurlencode($nopol);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $request = Http::acceptJson()->withHeaders($accessHeaders);
                $response = ($apiKey === '' ? $request : $request->withToken($apiKey))
                    ->withOptions(['allow_redirects' => false])
                    ->connectTimeout(3)
                    ->timeout($timeout >= 1 && $timeout <= 60 ? $timeout : 8)
                    ->get($url);
            } catch (ConnectionException $exception) {
                // A timeout is not retried; a dropped connection is, once
                $timedOut = str_contains($exception->getMessage(), 'cURL error 28');
                if (! $timedOut && $attempt === 0) {
                    $this->pause();

                    continue;
                }

                return $this->failed(null, $timedOut ? 'timeout' : 'network');
            }

            if ($response->status() === 404) {
                return ['status' => 'not_found'];
            }

            if ($response->status() === 403 && data_get($this->json($response), 'error.code') === 'region_not_allowed') {
                return ['status' => 'outside_region', 'message' => self::OUTSIDE_REGION];
            }

            if ($response->status() === 429) {
                $seconds = $this->retryAfter($response->header('Retry-After'));
                Cache::put(self::WAIT_UNTIL, now()->getTimestamp() + $seconds, $seconds);
                $this->failed($response, 'rate_limited');

                return $this->busy($seconds);
            }

            if (in_array($response->status(), [502, 503, 504], true) && $attempt === 0) {
                $this->pause();

                continue;
            }

            if ($response->status() !== 200) {
                return $this->failed($response, 'http');
            }

            $body = $this->json($response);

            return is_array($body)
                ? $this->map($body, $nopol, $response)
                : $this->failed($response, 'invalid_payload');
        }

        return $this->failed(null, 'network');
    }

    /**
     * The API's answer in the calculator's terms, as mapApiResponseToVehicleData
     * in kalkulator-pajak: the vehicle kind is BPAD's raw category, money and
     * weights arrive as decimal strings, and an NJKB reference that is
     * unavailable, conflicting or ambiguous is not used.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function map(array $body, string $nopol, Response $response): array
    {
        if (data_get($body, 'vehicle_status') === 'not_found' || in_array(data_get($body, 'status'), ['vehicle_not_found', 'invalid_request'], true)) {
            return ['status' => 'not_found'];
        }

        $plate = self::normalise(is_string($body['nopol'] ?? null) ? $body['nopol'] : $nopol);
        if (strlen($plate) < 4) {
            return $this->failed($response, 'invalid_payload');
        }

        $rawKind = $this->text(data_get($body, 'vehicle.category_raw'));
        $kind = $rawKind ?? $this->text(data_get($body, 'vehicle.category'));
        $chassis = $this->text(data_get($body, 'vehicle.chassis_number'));
        $njkbStatus = data_get($body, 'njkb_status');

        if (is_string($njkbStatus) && isset(self::REFERENCE_PROBLEMS[$njkbStatus])) {
            return ['status' => 'reference_problem', 'message' => self::REFERENCE_PROBLEMS[$njkbStatus], 'chassis' => $chassis];
        }

        if ($njkbStatus === 'matched') {
            $njkb = $this->rupiah(data_get($body, 'njkb.value'));
            $weight = $this->weight(data_get($body, 'njkb.weight'));
            if ($njkb === null || $weight === null) {
                return $this->failed($response, 'invalid_payload');
            }
        } elseif ($njkbStatus === 'not_found') {
            $njkb = 0;
            $weight = $this->weight(data_get($body, 'njkb.weight'))
                ?? self::WEIGHTS[$rawKind ?? ''] ?? self::WEIGHTS[$kind ?? ''] ?? 1.0;
        } else {
            return $this->failed($response, 'invalid_payload');
        }

        return [
            'status' => 'found',
            'vehicle' => [
                'nopol' => $plate,
                'nama' => $this->text(data_get($body, 'owner.name')),
                'jenis' => $kind,
                'jatuhTempoStnk' => $this->date(data_get($body, 'registration.stnk_valid_until')),
                'jatuhTempoPajak' => $this->date(data_get($body, 'tax.notice_valid_until')),
                'njkb' => $njkb,
                'njub' => 0,
                'bobot' => $weight,
                'warnaPlat' => self::plateColour(data_get($body, 'registration.plate_color')),
                'njkbStatus' => $njkbStatus,
            ],
            'chassis' => $chassis,
        ];
    }

    /**
     * The plate colour the calculator supports (putih, hitam, merah), as
     * normalizeWarnaPlat in kalkulator-pajak; null for unsupported or
     * ambiguous colours, so staff choose it themselves.
     */
    public static function plateColour(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $tokens = preg_split('/[^A-Z]+/', strtoupper($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $colours = ['PUTIH' => 'putih', 'WHITE' => 'putih', 'HITAM' => 'hitam', 'BLACK' => 'hitam', 'MERAH' => 'merah', 'RED' => 'merah'];

        if ($tokens === [] || array_intersect($tokens, ['KUNING', 'YELLOW', 'HIJAU', 'GREEN', 'BIRU', 'BLUE']) !== []) {
            return null;
        }

        $base = array_search('DASAR', $tokens, true);
        if ($base !== false && isset($colours[$tokens[$base + 1] ?? ''])) {
            return $colours[$tokens[$base + 1]];
        }

        $found = array_values(array_unique(array_filter(array_map(fn (string $token): ?string => $colours[$token] ?? null, $tokens))));

        return count($found) === 1 ? $found[0] : null;
    }

    /**
     * A whole Rupiah amount from a decimal string such as "150000000.00";
     * null for anything else, including amounts with non-zero sen.
     */
    private function rupiah(mixed $raw): ?int
    {
        if (! is_string($raw) || preg_match('/^(\d+)(?:\.(\d+))?$/', trim($raw), $match) !== 1 || preg_match('/[1-9]/', $match[2] ?? '') === 1) {
            return null;
        }

        $amount = filter_var(ltrim($match[1], '0') ?: '0', FILTER_VALIDATE_INT);

        return is_int($amount) && $amount > 0 ? $amount : null;
    }

    /**
     * A positive weight from a decimal string such as "1.050000".
     */
    private function weight(mixed $raw): ?float
    {
        if (! is_string($raw) || preg_match('/^\d+(?:\.\d+)?$/', trim($raw)) !== 1) {
            return null;
        }

        $weight = (float) trim($raw);

        return $weight > 0 ? $weight : null;
    }

    /**
     * A date as YYYY-MM-DD from "YYYY-MM-DD…" or "DD/MM/YYYY", years 1900–2100.
     */
    private function date(mixed $raw): ?string
    {
        $value = is_string($raw) ? trim($raw) : '';

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $match) === 1) {
            [, $year, $month, $day] = $match;
        } elseif (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})/', $value, $match) === 1) {
            [, $day, $month, $year] = $match;
        } else {
            return null;
        }

        return (int) $year >= 1900 && (int) $year <= 2100 && checkdate((int) $month, (int) $day, (int) $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : null;
    }

    private function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * The answer to a lookup outside BPAD's service hours (D-042), given
     * without asking the API, or null while it is open. Until the opening
     * time is known, only the hours from closing to midnight count as
     * closed; earlier hours are left to the API.
     */
    private function outsideServiceHours(): ?string
    {
        $opensAt = $this->clockTime(config('services.njkb.opens_at'));
        $closesAt = $this->clockTime(config('services.njkb.closes_at'));

        if ($closesAt === null) {
            return null;
        }

        $now = now(self::TIME_ZONE)->format('H:i');
        $opens = $opensAt === null ? null : str_replace(':', '.', $opensAt);
        $closes = str_replace(':', '.', $closesAt);
        $hours = $opens === null ? "sampai pukul {$closes} WITA" : "pukul {$opens}–{$closes} WITA";

        if ($now >= $closesAt) {
            return "Data kendaraan dari BPAD hanya tersedia {$hours}. Coba lagi besok".($opens === null ? '.' : " pukul {$opens} WITA.");
        }

        if ($opensAt !== null && $now < $opensAt) {
            return "Data kendaraan dari BPAD hanya tersedia {$hours}. Coba lagi pukul {$opens} WITA.";
        }

        return null;
    }

    /**
     * A time of day as "HH:MM", or null for anything else.
     */
    private function clockTime(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value : null;
    }

    /**
     * Seconds to wait from a Retry-After header (seconds or an HTTP date);
     * 60 when it is missing or unreadable.
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
        return ['status' => 'unavailable', 'message' => "Layanan data kendaraan sedang sibuk. Coba lagi dalam {$seconds} detik."];
    }

    /**
     * Log a failed lookup without anything about the vehicle.
     *
     * @return array{status: 'unavailable', message: string}
     */
    private function failed(?Response $response, string $reason): array
    {
        $body = $response === null ? null : $this->json($response);

        Log::warning('NJKB API request failed', [
            'reason' => $reason,
            'request_id' => $response?->header('X-Request-ID') ?: null,
            'http_status' => $response?->status(),
            'status' => is_array($body) ? data_get($body, 'status') : null,
            'error_code' => is_array($body) ? data_get($body, 'error.code') : null,
        ]);

        return ['status' => 'unavailable', 'message' => 'Data kendaraan sedang tidak dapat diambil. Coba lagi beberapa saat lagi.'];
    }

    /**
     * The body of an answer that says it is JSON, or null.
     */
    private function json(Response $response): mixed
    {
        return str_contains(strtolower($response->header('Content-Type')), 'application/json') ? $response->json() : null;
    }

    /**
     * Back off briefly with jitter before the one retry.
     */
    private function pause(): void
    {
        Sleep::for(200 + random_int(0, 100))->milliseconds();
    }
}
