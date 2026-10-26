<?php

namespace App\Actions;

use App\Exceptions\AiRequestFailed;
use App\Support\AiClient;
use App\Support\AiConnection;

class TestAiConnection
{
    public function __construct(private AiClient $client) {}

    /**
     * Ask the model for one short word (D-085). Connected means the API
     * accepted the key and the model and answered; a failure reports why,
     * with the provider's own message for the administrator.
     *
     * @return array{terhubung: bool, pesan: string, penyedia: ?string, model: ?string, detik: float}
     */
    public function handle(AiConnection $connection): array
    {
        $start = microtime(true);

        try {
            $reply = $this->client->chat($connection, [
                ['role' => 'user', 'content' => 'Balas hanya dengan kata: OK'],
            ], ['max_tokens' => 16]);
        } catch (AiRequestFailed $exception) {
            return [
                'terhubung' => false,
                'pesan' => $exception->getMessage(),
                'penyedia' => $exception->providerMessage,
                'model' => null,
                'detik' => $this->seconds($start),
            ];
        }

        $seconds = $this->seconds($start);
        $model = $reply['model'] ?? $connection->model;

        return [
            'terhubung' => true,
            'pesan' => 'Terhubung. Model '.$model.' menjawab dalam '.str_replace('.', ',', (string) $seconds).' detik.',
            'penyedia' => null,
            'model' => $model,
            'detik' => $seconds,
        ];
    }

    private function seconds(float $start): float
    {
        return round(microtime(true) - $start, 1);
    }
}
