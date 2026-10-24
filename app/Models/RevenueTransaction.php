<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RevenueTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * A transaction from an imported STS file (D-047). Amounts are whole rupiah;
 * "jumlah" is the sum of the other amounts. "nomoratur" is the SKPD form
 * number (D-071): seven digits, "-" when the transaction has no form, null
 * when it was imported before form numbers were kept. "rincian" holds the
 * file's other columns, the plate number, the owner's name and the RT/RW
 * among them, for the dashboard's Rincian penerimaan (D-079): encrypted, and
 * only for the last 7 days, null otherwise.
 *
 * @property Carbon $tanggal
 * @property array<string, ?string>|null $rincian
 */
#[Fillable(['revenue_import_id', 'loket', 'tanggal', 'jenis_daftar', 'jenis_kendaraan', 'kepemilikan', 'merk', 'warna_plat', 'upt_asal',
    'kecamatan', 'kelurahan', 'nomoratur', 'rincian', 'tahun_tunggakan', 'pkb', 'bbn', 'denda_pkb', 'denda_bbn', 'tunggakan', 'denda_tunggakan',
    'opsen_pkb', 'opsen_bbn', 'opsen_denda_pkb', 'opsen_denda_bbn', 'opsen_tunggakan', 'opsen_denda_tunggakan', 'jumlah', 'sidik'])]
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

    /**
     * How many days, today (WITA) included, a transaction keeps its
     * "rincian" (D-079).
     */
    public const int DETAIL_DAYS = 7;

    /**
     * The time zone of the days the details are kept for.
     */
    private const string TIME_ZONE = 'Asia/Makassar';

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
            'rincian' => 'encrypted:array',
            ...array_fill_keys(self::AMOUNTS, 'integer'),
        ];
    }

    /**
     * Today in Kupang (WITA), the last day the details are kept for.
     */
    public static function detailsUntil(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIME_ZONE)->startOfDay();
    }

    /**
     * The first day whose transactions keep their details: 6 days before
     * today (WITA).
     */
    public static function detailsSince(): CarbonImmutable
    {
        return self::detailsUntil()->subDays(self::DETAIL_DAYS - 1);
    }

    /**
     * The details as stored: encrypted as the "encrypted:array" cast does, for
     * rows saved with a plain insert.
     *
     * @param  array<string, ?string>  $details
     */
    public static function encryptDetails(array $details): string
    {
        return Crypt::encryptString((string) json_encode($details));
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
