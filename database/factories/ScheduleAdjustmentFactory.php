<?php

namespace Database\Factories;

use App\Models\ScheduleAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleAdjustment>
 */
class ScheduleAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state: a closed day in the coming weeks.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->unique()->dateTimeBetween('+1 day', '+60 days')->format('Y-m-d'),
            'jenis' => ScheduleAdjustment::CLOSED,
            'mulai' => '',
            'selesai' => '',
            'keterangan' => 'Cuti bersama',
            'urutan' => 0,
        ];
    }

    /**
     * Open at special hours.
     */
    public function specialHours(string $mulai = '08:00', string $selesai = '11:30'): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis' => ScheduleAdjustment::SPECIAL_HOURS,
            'mulai' => $mulai,
            'selesai' => $selesai,
        ]);
    }
}
