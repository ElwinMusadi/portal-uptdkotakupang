<?php

namespace Database\Factories;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueService;
use App\Models\QueueTicket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QueueTicket>
 */
class QueueTicketFactory extends Factory
{
    /**
     * A waiting ticket of service A printed on 15 October 2026.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $urut = fake()->unique()->numberBetween(1, 999);

        return [
            'kode_lacak' => Str::random(QueueTicket::TRACKING_CODE_LENGTH),
            'queue_service_id' => fn (): string => QueueService::query()->where('kode', 'A')->value('id'),
            'tanggal' => '2026-10-15',
            'urut' => $urut,
            'nomor' => sprintf('A-%03d', $urut),
            'nopol' => 'DH1234AB',
            'tahap_layanan' => array_map(fn (QueueStage $stage): string => $stage->value, QueueStage::cases()),
            'tahap' => QueueStage::Pendaftaran,
            'status' => QueueTicketStatus::Menunggu,
        ];
    }

    /**
     * A ticket cancelled by staff.
     */
    public function cancelled(): static
    {
        return $this->state([
            'status' => QueueTicketStatus::Batal,
            'alasan_batal' => 'Berkas belum lengkap.',
            'dibatalkan_pada' => '2026-10-15 02:00:00',
        ]);
    }
}
