<?php

namespace Database\Factories;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Models\QueueCounter;
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
            'masuk_tahap_pada' => '2026-10-15 01:00:00',
        ];
    }

    /**
     * A ticket of the service with this code, with its stages.
     */
    public function of(string $serviceCode): static
    {
        return $this->state(function () use ($serviceCode): array {
            $service = QueueService::query()->where('kode', $serviceCode)->sole();

            return ['queue_service_id' => $service->id, 'tahap_layanan' => $service->tahap, 'nomor' => sprintf('%s-%03d', $serviceCode, fake()->unique()->numberBetween(1, 999))];
        });
    }

    /**
     * A ticket waiting at the stage since the given time (UTC).
     */
    public function waitingAt(QueueStage $stage, string $since = '2026-10-15 01:00:00'): static
    {
        return $this->state(['tahap' => $stage, 'status' => QueueTicketStatus::Menunggu, 'masuk_tahap_pada' => $since]);
    }

    /**
     * A ticket called to the counter, at the counter's stage.
     */
    public function calledTo(QueueCounter $counter, int $times = 1): static
    {
        return $this->state([
            'tahap' => $counter->tahap,
            'status' => QueueTicketStatus::Dipanggil,
            'queue_counter_id' => $counter->id,
            'dipanggil_pada' => '2026-10-15 02:00:00',
            'jumlah_panggil' => $times,
        ]);
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
