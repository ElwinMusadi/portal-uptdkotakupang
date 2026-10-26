<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Pengaturan › Integrasi AI (D-085). The stored document holds the base URL,
 * the model and the key encrypted with APP_KEY ("kunci", null without one).
 * Until it is saved, and for a key it does not hold, the server's .env is
 * used (config services.ai). The key is never given back whole: the
 * dashboard sees only whether there is one, its last four characters and
 * where it comes from.
 */
final class AiSettings
{
    /**
     * The stored document, or null until an administrator saves it.
     *
     * @return array{baseUrl: string, model: string, kunci: ?string}|null
     */
    public static function stored(): ?array
    {
        $value = Setting::query()->find(Setting::AI_INTEGRATION)?->value;

        return is_array($value) ? [
            'baseUrl' => (string) ($value['baseUrl'] ?? ''),
            'model' => (string) ($value['model'] ?? ''),
            'kunci' => isset($value['kunci']) && is_string($value['kunci']) ? $value['kunci'] : null,
        ] : null;
    }

    /**
     * The connection in use: the saved base URL and model, or those of .env;
     * the saved key, or that of .env.
     */
    public static function connection(): AiConnection
    {
        $stored = self::stored();
        $savedKey = self::decrypt($stored['kunci'] ?? null);
        $envKey = trim((string) config('services.ai.api_key'));

        return new AiConnection(
            baseUrl: rtrim($stored['baseUrl'] ?? trim((string) config('services.ai.base_url')), '/'),
            model: $stored['model'] ?? trim((string) config('services.ai.model')),
            key: $savedKey ?? ($envKey !== '' ? $envKey : null),
            keySource: $savedKey !== null ? 'pengaturan' : ($envKey !== '' ? 'env' : null),
        );
    }

    /**
     * The settings as the dashboard shows them, without the key itself.
     *
     * @return array{baseUrl: string, model: string, tersimpan: bool, kunci: array{ada: bool, akhir: ?string, sumber: ?string}}
     */
    public static function describe(): array
    {
        $connection = self::connection();

        return [
            'baseUrl' => $connection->baseUrl,
            'model' => $connection->model,
            'tersimpan' => self::stored() !== null,
            'kunci' => [
                'ada' => $connection->key !== null,
                'akhir' => $connection->key === null ? null : mb_substr($connection->key, -4),
                'sumber' => $connection->keySource,
            ],
        ];
    }

    public static function encrypt(string $key): string
    {
        return Crypt::encryptString($key);
    }

    /**
     * A saved key, or null when there is none or APP_KEY cannot read it.
     */
    private static function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            Log::error('AI integration key cannot be decrypted');

            return null;
        }
    }
}
