<?php

namespace Database\Factories;

use App\Models\MobileServiceStop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileServiceStop>
 */
class MobileServiceStopFactory extends Factory
{
    /**
     * Define the model's default state: an active morning stop.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hari' => fake()->numberBetween(1, 5),
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Kantor Kelurahan '.fake()->lastName(),
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => '',
            'aktif' => true,
            'urutan' => 0,
        ];
    }

    /**
     * Switched off, so the portal does not show it.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['aktif' => false]);
    }
}
