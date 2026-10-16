<?php

namespace Database\Factories;

use App\Models\PotentialImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PotentialImport>
 */
class PotentialImportFactory extends Factory
{
    /**
     * A finished import of a file set on 16 October 2026.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'berkas' => 'Data Potensi.xls',
            'jumlah' => 0,
            'diterima' => 0,
            'tanggal_penetapan' => '2026-10-16',
            'selesai_pada' => '2026-10-16 02:00:00',
        ];
    }

    /**
     * An import still receiving the given number of rows.
     */
    public function unfinished(int $rows = 3): static
    {
        return $this->state(['jumlah' => $rows, 'selesai_pada' => null, 'tanggal_penetapan' => null]);
    }
}
