<?php

namespace Database\Factories;

use App\Models\AgencyApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyApp>
 */
class AgencyAppFactory extends Factory
{
    /**
     * Define the model's default state: a UPTD website without a logo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = strtoupper(fake()->unique()->lexify('SI????'));

        return [
            'nama' => $name,
            'nama_lengkap' => 'Sistem Informasi '.ucfirst(fake()->words(2, true)),
            'keterangan' => fake()->sentence(),
            'jenis' => 'situs',
            'pengelola' => 'uptd',
            'tautan' => 'https://'.strtolower($name).'.uptdpenda-kupang.web.id',
            'logo' => null,
            'urutan' => 100,
        ];
    }

    /**
     * An Android app on Google Play made by BPAD.
     */
    public function android(): static
    {
        return $this->state(fn (): array => [
            'jenis' => 'android',
            'pengelola' => 'bpad',
            'tautan' => 'https://play.google.com/store/apps/details?id=id.go.ntt.'.fake()->unique()->lexify('??????'),
        ]);
    }
}
