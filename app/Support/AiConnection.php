<?php

namespace App\Support;

/**
 * The OpenAI-compatible AI API PINTU calls (D-085): its base URL, model and
 * key, and where the key came from. Only an https:// address with a model
 * and a key is usable, so the key never travels in plain text.
 */
final readonly class AiConnection
{
    /**
     * @param  'pengaturan'|'env'|'formulir'|null  $keySource  Pengaturan › Integrasi AI, the server's .env, a key typed for a connection test, or no key at all
     */
    public function __construct(
        public string $baseUrl,
        public string $model,
        public ?string $key,
        public ?string $keySource,
    ) {}

    public function isConfigured(): bool
    {
        return str_starts_with($this->baseUrl, 'https://') && $this->model !== '' && ($this->key ?? '') !== '';
    }

    /**
     * The chat completions address under the base URL.
     */
    public function chatUrl(): string
    {
        return rtrim($this->baseUrl, '/').'/chat/completions';
    }
}
