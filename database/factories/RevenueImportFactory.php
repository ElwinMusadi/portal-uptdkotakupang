<?php

namespace Database\Factories;

use App\Models\RevenueImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevenueImport>
 */
class RevenueImportFactory extends Factory
{
    /**
     * Define the model's default state: a day's file of the office counter,
     * not shown on the portal.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loket' => 'kantor',
            'nama_loket' => 'Loket SAMSAT',
            'nama_berkas' => 'STS_Harian_Kota_Kupang_2026-10-14.xls',
            'tanggal_awal' => '2026-10-14',
            'tanggal_akhir' => '2026-10-14',
            'jumlah_transaksi' => 0,
            'jumlah_dilewati' => 0,
            'total' => 0,
            'tampil' => false,
        ];
    }

    /**
     * An import for another counter, e.g. "keliling-01" named "Samsat Keliling 01".
     */
    public function counter(string $key, string $name): static
    {
        return $this->state(['loket' => $key, 'nama_loket' => $name]);
    }
}
