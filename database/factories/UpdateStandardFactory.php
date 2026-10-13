<?php

namespace Database\Factories;

use App\Models\UpdateStandard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpdateStandard>
 */
class UpdateStandardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'konten' => fake()->sentence(3),
            'modul' => fake()->randomElement(UpdateStandard::MONITORED_MODULES),
            'unit' => fake()->company(),
            'frekuensi' => fake()->randomElement(['Mingguan', 'Bulanan', 'Tahunan']),
            'interval_hari' => fake()->numberBetween(1, 365),
            'urutan' => fake()->numberBetween(0, 10),
        ];
    }
}
