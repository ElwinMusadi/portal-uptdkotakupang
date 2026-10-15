<?php

namespace App\Models;

use Database\Factories\RevenueTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A transaction from an imported STS file (D-047), without the owner's name,
 * the plate number, the numbering or the address below the kelurahan.
 * Amounts are whole rupiah; "jumlah" is the sum of the other amounts.
 *
 * @property Carbon $tanggal
 */
#[Fillable(['revenue_import_id', 'loket', 'tanggal', 'jenis_daftar', 'jenis_kendaraan', 'kepemilikan', 'merk', 'warna_plat', 'upt_asal',
    'kecamatan', 'kelurahan', 'tahun_tunggakan', 'pkb', 'bbn', 'denda_pkb', 'denda_bbn', 'tunggakan', 'denda_tunggakan', 'opsen_pkb',
    'opsen_bbn', 'opsen_denda_pkb', 'opsen_denda_bbn', 'opsen_tunggakan', 'opsen_denda_tunggakan', 'jumlah', 'sidik'])]
class RevenueTransaction extends Model
{
    /** @use HasFactory<RevenueTransactionFactory> */
    use HasFactory;

    /**
     * The amounts that make up "jumlah", in the order of the STS file: the
     * province's PKB, BBNKB, fines and arrears, then the city's opsen on each.
     *
     * @var list<string>
     */
    public const array PARTS = ['pkb', 'bbn', 'denda_pkb', 'denda_bbn', 'tunggakan', 'denda_tunggakan', 'opsen_pkb', 'opsen_bbn',
        'opsen_denda_pkb', 'opsen_denda_bbn', 'opsen_tunggakan', 'opsen_denda_tunggakan'];

    /**
     * Every amount column, the parts and their sum.
     *
     * @var list<string>
     */
    public const array AMOUNTS = [...self::PARTS, 'jumlah'];

    public $timestamps = false;

    /**
     * The only date is a day, stored without a time so the report can compare
     * it as stored, on its index, whatever the database.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahun_tunggakan' => 'integer',
            ...array_fill_keys(self::AMOUNTS, 'integer'),
        ];
    }

    /**
     * The import that added the transaction.
     *
     * @return BelongsTo<RevenueImport, $this>
     */
    public function revenueImport(): BelongsTo
    {
        return $this->belongsTo(RevenueImport::class);
    }
}
