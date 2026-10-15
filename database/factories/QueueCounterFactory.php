<?php

namespace Database\Factories;

use App\Enums\QueueStage;
use App\Models\QueueCounter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueCounter>
 */
class QueueCounterFactory extends Factory
{
    /**
     * A counter beside the three created by the migration (D-051), calling
     * every service to Loket 1 – Pendaftaran.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Loket '.fake()->unique()->numberBetween(10, 99),
            'tahap' => QueueStage::Pendaftaran,
            'layanan' => [],
            'nama_suara' => 'Loket uji',
            'aktif' => true,
            'urutan' => 10,
        ];
    }

    /**
     * A counter at the given calling stage.
     */
    public function at(QueueStage $stage): static
    {
        return $this->state(['tahap' => $stage]);
    }

    /**
     * A counter whose "call next" takes only these services.
     *
     * @param  list<string>  $services
     */
    public function serving(array $services): static
    {
        return $this->state(['layanan' => $services]);
    }
}
