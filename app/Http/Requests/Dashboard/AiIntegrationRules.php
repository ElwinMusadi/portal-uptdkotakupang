<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use App\Support\AiSettings;
use Illuminate\Validation\Validator;

/**
 * The fields shared by saving and testing Pengaturan › Integrasi AI (D-085):
 * an https:// base URL of an OpenAI-compatible API, a model name and, when
 * changed, the key. A key already in use is never sent to another host: a
 * new host needs its own key.
 */
trait AiIntegrationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function aiIntegrationRules(): array
    {
        return [
            'baseUrl' => ['required', 'string', 'max:200', 'url:https', new PlainText('Base URL')],
            'model' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\/@+-]*$/'],
            'kunci' => ['nullable', 'string', 'max:300', 'regex:/^[\x21-\x7E]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function aiIntegrationMessages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute tidak valid.',
            'boolean' => ':Attribute tidak valid.',
            'max' => ':Attribute maksimal :max karakter.',
            'baseUrl.url' => 'Base URL harus alamat lengkap yang diawali https://.',
            'model.regex' => 'Nama model hanya boleh berisi huruf, angka, dan tanda . _ - / : @ +.',
            'kunci.regex' => 'Kunci API tidak boleh berisi spasi atau huruf di luar ASCII.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function aiIntegrationAttributes(): array
    {
        return [
            'baseUrl' => 'Base URL',
            'model' => 'model',
            'kunci' => 'kunci API',
            'hapusKunci' => 'pilihan hapus kunci',
        ];
    }

    /**
     * Refuse to send the key that would be used to a host other than its own:
     * a key saved here belongs to the saved base URL, the .env key to the
     * .env base URL.
     */
    protected function keepKeyOnItsHost(Validator $validator, bool $keyRemoved = false): void
    {
        if ($validator->errors()->isNotEmpty() || $this->newKey() !== null) {
            return;
        }

        $current = AiSettings::connection();
        [$key, $home] = $keyRemoved || $current->keySource !== 'pengaturan'
            ? [trim((string) config('services.ai.api_key')), (string) config('services.ai.base_url')]
            : [(string) $current->key, $current->baseUrl];
        $host = fn (string $url): string => strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($key !== '' && $host((string) $this->input('baseUrl')) !== $host($home)) {
            $validator->errors()->add('kunci', 'Isi kunci API untuk alamat baru. Kunci yang tersimpan tidak dikirim ke alamat lain.');
        }
    }

    /**
     * The key typed in the form, or null when it was left empty. Read from
     * the input, since the after hook runs before validated() is ready.
     */
    public function newKey(): ?string
    {
        $key = $this->input('kunci');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
