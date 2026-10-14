<?php

namespace Database\Factories;

use App\Models\FieldActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldActivity>
 */
class FieldActivityFactory extends Factory
{
    /**
     * Define the model's default state: a joint operation in front of the
     * Kantor Bersama Samsat Kota Kupang today, with a 150 m radius.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Tilang gabungan '.fake()->unique()->streetName(),
            'tanggal' => now(FieldActivity::TIME_ZONE)->toDateString(),
            'tempat' => 'Jl. El Tari',
            'lat' => -10.163700,
            'lng' => 123.597400,
            'radius' => 150,
            'catatan' => '',
        ];
    }
}
