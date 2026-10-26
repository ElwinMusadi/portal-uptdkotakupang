<?php

namespace App\Support;

use App\Exceptions\AiRequestFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PINTU's client for an OpenAI-compatible chat completions API (D-085),
 * following SikapClient: the key as a bearer token, only to an https://
 * address, no redirects (the key must never follow one), a 5-second connect
 * timeout and the configured overall one, kept under Cloudflare's 100
 * seconds. Nothing is retried, since a draft is slow and paid for. A failure
 * logs only its reason, HTTP status and model: never the key, a prompt, a
 * document or an answer.
 */
class AiClient
{
    /**
     * Send one chat completion and return the first answer's text.
     *
     * @param  list<array{role: string, content: string|list<array<string, mixed>>}>  $messages
     * @param  array<string, mixed>  $options  e.g. response_format, temperature, max_tokens
     * @return array{content: string, model: ?string, tokens: ?int}
     *
     * @throws AiRequestFailed
     */
    public function chat(AiConnection $connection, array $messages, array $options = []): array
    {
        if (! $connection->isConfigured()) {
            Log::error('AI API is not configured');

            throw new AiRequestFailed('not_configured', 'Integrasi AI belum diatur. Minta Administrator mengisinya di Pengaturan › Integrasi AI.');
        }

        $timeout = config()->integer('services.ai.timeout');

        try {
            $response = Http::withToken((string) $connection->key)
                ->acceptJson()
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(5)
                ->timeout($timeout >= 5 && $timeout <= 90 ? $timeout : 75)
                ->post($connection->chatUrl(), ['model' => $connection->model, 'messages' => $messages, ...$options]);
        } catch (ConnectionException $exception) {
            $timedOut = str_contains($exception->getMessage(), 'cURL error 28');

            throw $this->failed($connection, $timedOut ? 'timeout' : 'network', null, $timedOut
                ? 'Penyedia AI tidak menjawab dalam waktu yang ditentukan. Coba lagi, atau pakai sumber yang lebih ringkas.'
                : 'Penyedia AI tidak dapat dihubungi. Periksa Base URL di Pengaturan › Integrasi AI, atau coba lagi.');
        }

        if ($response->status() !== 200) {
            throw $this->failed($connection, ...$this->httpFailure($response));
        }

        // A reasoning model may answer a very short request with no text at all
        $choice = $response->json('choices.0');
        $content = is_array($choice) ? ($choice['message']['content'] ?? null) : false;
        if (! is_string($content) && $content !== null) {
            throw $this->failed($connection, 'invalid_response', $response, 'Jawaban penyedia AI tidak dapat dibaca.');
        }

        $tokens = $response->json('usage.total_tokens');

        return [
            'content' => (string) $content,
            'model' => is_string($response->json('model')) ? $response->json('model') : null,
            'tokens' => is_int($tokens) ? $tokens : null,
        ];
    }

    /**
     * @return array{0: string, 1: Response, 2: string}
     */
    private function httpFailure(Response $response): array
    {
        return match (true) {
            in_array($response->status(), [401, 403], true) => ['unauthorized', $response, 'Kunci API ditolak penyedia AI. Periksa kunci di Pengaturan › Integrasi AI.'],
            $response->status() === 404 => ['not_found', $response, 'Alamat atau model tidak dikenal penyedia AI. Periksa Base URL dan model di Pengaturan › Integrasi AI.'],
            $response->status() === 429 => ['rate_limited', $response, 'Batas permintaan atau saldo penyedia AI habis. Coba lagi nanti.'],
            default => ['http', $response, "Penyedia AI menolak permintaan (HTTP {$response->status()}). Coba lagi nanti."],
        };
    }

    private function failed(AiConnection $connection, string $reason, ?Response $response, string $message): AiRequestFailed
    {
        Log::warning('AI request failed', [
            'reason' => $reason,
            'http_status' => $response?->status(),
            'model' => $connection->model,
        ]);

        return new AiRequestFailed($reason, $message, $response?->status(), $this->providerMessage($response, $connection));
    }

    /**
     * The provider's own error text, shortened and with anything that looks
     * like the key masked, for the administrator's connection test.
     */
    private function providerMessage(?Response $response, AiConnection $connection): ?string
    {
        if ($response === null || $response->status() === 200) {
            return null;
        }

        $text = $response->json('error.message') ?? $response->json('message') ?? $response->json('error');
        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        $text = preg_replace('/\b(?:sk|pk|key)-[A-Za-z0-9_\-]{4,}/i', '***', $text) ?? '';
        if (($connection->key ?? '') !== '') {
            $text = str_replace((string) $connection->key, '***', $text);
        }

        return mb_strimwidth(trim(preg_replace('/\s+/', ' ', $text) ?? ''), 0, 300, '…');
    }
}
