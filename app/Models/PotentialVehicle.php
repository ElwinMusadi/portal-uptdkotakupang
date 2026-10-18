<?php

namespace App\Models;

use App\Enums\TaxCompliance;
use App\Enums\VehicleWheels;
use Database\Factories\PotentialVehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A vehicle of BPAD's potential-tax file (D-052), with its owner's identity
 * for the collection lists in the dashboard. The identity card and phone
 * numbers are stored encrypted. Rows are saved in bulk at import and never
 * changed.
 *
 * @property ?string $ktp
 * @property ?string $telepon
 * @property ?VehicleWheels $roda
 * @property TaxCompliance $kelompok
 * @property ?Carbon $tanggal_penetapan
 * @property ?Carbon $sd_stnk
 * @property ?Carbon $sd_notice
 */
class PotentialVehicle extends Model
{
    /** @use HasFactory<PotentialVehicleFactory> */
    use HasFactory;

    /**
     * Rows have no timestamps.
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ktp' => 'encrypted',
            'telepon' => 'encrypted',
            'roda' => VehicleWheels::class,
            'kelompok' => TaxCompliance::class,
            'tahun_buat' => 'integer',
            'tanggal_penetapan' => 'date',
            'cyl' => 'integer',
            'sd_stnk' => 'date',
            'sd_notice' => 'date',
            'bulan_jatuh_tempo' => 'integer',
            'njkb' => 'integer',
            'njub' => 'integer',
            'bobot' => 'float',
            'beda_hari' => 'integer',
            'beda_bulan' => 'integer',
            'bulat_bulan' => 'integer',
            'tahun_tunggak' => 'integer',
            'tahun_tunggak_awal' => 'integer',
            'pokok_pajak' => 'integer',
            'denda_berjalan' => 'integer',
            'pokok_tunggak' => 'integer',
            'denda_tunggak' => 'integer',
            'tagihan' => 'integer',
            'tarif' => 'float',
            'total' => 'integer',
            'pokok_pajak_nor' => 'integer',
            'denda_berjalan_nor' => 'integer',
            'pokok_tunggak_nor' => 'integer',
            'denda_tunggak_nor' => 'integer',
            'total_nor' => 'integer',
            'ditarik_pada' => 'datetime',
        ];
    }

    /**
     * The import the vehicle came with.
     *
     * @return BelongsTo<PotentialImport, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(PotentialImport::class, 'potential_import_id');
    }
}
