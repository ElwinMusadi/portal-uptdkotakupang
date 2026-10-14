<?php

namespace Database\Factories;

use App\Models\ServiceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceLocation>
 */
class ServiceLocationFactory extends Factory
{
    /**
     * Define the model's default state: a kelurahan office in Oebobo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Kantor Kelurahan '.fake()->unique()->lastName(),
            'kecamatan' => 'Kec. Oebobo',
            'urutan' => 0,
        ];
    }
}
