<?php

namespace Database\Factories;

use App\Enums\TaxCompliance;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PotentialVehicle>
 */
class PotentialVehicleFactory extends Factory
{
    /**
     * A motorcycle in Oesapa, Kelapa Lima, paid up.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'potential_import_id' => PotentialImport::factory(),
            'urut' => fake()->unique()->numberBetween(1, 100000),
            'nopol' => 'DH'.fake()->numberBetween(1000, 9999).'AB',
            'nama' => 'Pemilik Uji',
            'ktp' => '5371000000000001',
            'telepon' => '081200000001',
            'alamat' => 'Jl. Uji',
            'rt' => '001',
            'rw' => '002',
            'kelurahan' => 'OESAPA',
            'kecamatan' => 'KELAPA LIMA',
            'kecamatan_berkas' => 'KELAPA LIMA',
            'jenis' => 'SEPEDA MOTOR',
            'roda' => 'r2',
            'merk' => 'HONDA',
            'tipe' => 'VARIO',
            'mesin' => 'MSN001',
            'rangka' => 'RKA001',
            'tahun_buat' => 2020,
            'warna_plat' => 'PUTIH',
            'warna_kendaraan' => 'HITAM',
            'bbm' => 'PERTALITE',
            'tanggal_penetapan' => '2026-10-16',
            'upt_asal' => 'Kota Kupang',
            'penggunaan' => 'PRIBADI',
            'cyl' => 110,
            'kode_jr' => 'C1',
            'sd_stnk' => '2029-10-20',
            'sd_notice' => '2026-10-20',
            'bulan_jatuh_tempo' => 10,
            'rubah_bentuk' => '-- TIDAK ADA PERUBAHAN --',
            'njkb' => 15000000,
            'njub' => 0,
            'bobot' => 1.0,
            'beda_hari' => 0,
            'beda_bulan' => 0,
            'bulat_bulan' => 0,
            'tahun_tunggak' => 0,
            'tahun_tunggak_awal' => 0,
            'pokok_pajak' => 200000,
            'denda_berjalan' => 0,
            'pokok_tunggak' => 0,
            'denda_tunggak' => 0,
            'tagihan' => 0,
            'kepatuhan' => 'Taat Membayar Pajak',
            'kelompok' => TaxCompliance::Taat,
            'peringatan' => '',
            'dtd_status' => '',
            'dtd_kegiatan' => '',
            'dtd_petugas' => '',
        ];
    }

    /**
     * A vehicle in arrears for the given years, owing its tax and fines.
     */
    public function inArrears(int $years, int $owed = 1000000): static
    {
        return $this->state([
            'kepatuhan' => "Menunggak {$years} Tahun",
            'kelompok' => TaxCompliance::Menunggak,
            'tahun_tunggak' => $years,
            'pokok_tunggak' => $owed,
            'tagihan' => $owed + 200000,
        ]);
    }
}
