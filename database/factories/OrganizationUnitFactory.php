<?php

namespace Database\Factories;

use App\Models\OrganizationUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationUnit>
 */
class OrganizationUnitFactory extends Factory
{
    /**
     * Define the model's default state: a section without an official.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Seksi '.ucfirst(fake()->unique()->words(2, true)),
            'singkatan' => 'S'.fake()->numberBetween(3, 9),
            'jenis' => 'seksi',
            'induk' => null,
            'jabatan' => 'Kepala Seksi',
            'pejabat' => '',
            'nip' => '',
            'foto' => null,
            'anggota' => [],
            'uraian_tugas' => fake()->sentence(),
            'catatan' => '',
            'urutan' => 100,
        ];
    }

    /**
     * A unit under the given unit.
     */
    public function under(OrganizationUnit $unit): static
    {
        return $this->state(fn (): array => ['induk' => $unit->id]);
    }
}
