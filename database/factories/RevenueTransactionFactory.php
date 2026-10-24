<?php

namespace Database\Factories;

use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevenueTransaction>
 */
class RevenueTransactionFactory extends Factory
{
    /**
     * Define the model's default state: a private motorcycle from Oebobo
     * paying its yearly PKB at the office counter on 14 October 2026.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'revenue_import_id' => RevenueImport::factory(),
            'loket' => 'kantor',
            'tanggal' => '2026-10-14',
            'jenis_daftar' => 'Teliti Ulang / Pengesahan',
            'jenis_kendaraan' => 'SEPEDA MOTOR',
            'kepemilikan' => 'pribadi',
            'merk' => 'HONDA',
            'warna_plat' => 'PUTIH',
            'upt_asal' => 'Kota Kupang',
            'kecamatan' => 'OEBOBO',
            'kelurahan' => 'Oebobo',
            'tahun_tunggakan' => 0,
            ...array_fill_keys(RevenueTransaction::PARTS, 0),
            'pkb' => 300000,
            'opsen_pkb' => 198000,
            'jumlah' => 498000,
            'sidik' => fake()->unique()->sha256(),
        ];
    }

    /**
     * The transaction's amounts; the others are zero and "jumlah" is their sum.
     *
     * @param  array<string, int>  $parts
     */
    public function paying(array $parts): static
    {
        $parts = [...array_fill_keys(RevenueTransaction::PARTS, 0), ...$parts];

        return $this->state([...$parts, 'jumlah' => array_sum($parts)]);
    }

    /**
     * Made-up details from the STS file (D-079), as kept for the last 7 days.
     *
     * @param  array<string, ?string>  $details
     */
    public function withDetails(array $details = []): static
    {
        return $this->state(['rincian' => [
            'nopol' => 'DH1234XY', 'nama' => 'PEMILIK RAHASIA', 'tipeKendaraan' => 'TIPE UJI 125', 'jatuhTempoAwal' => '2027-10-14',
            'jatuhTempoAkhir' => '2027-10-14', 'nilaiJual' => '18000000.00', 'uptBayar' => 'Kota Kupang', 'kodeJenisKendaraan' => 'J1',
            'jenisKendaraan' => 'SEPEDA MOTOR PRIBADI', 'rt' => '007', 'rw' => '003', 'ubahBentuk' => null,
            ...$details,
        ]]);
    }
}
