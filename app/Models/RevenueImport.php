<?php

namespace App\Models;

use Database\Factories\RevenueImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One STS file imported for a counter (D-047): the counter's key and its name
 * at the time, the dates and totals of the transactions it added, and whether
 * the public portal may show them. The file itself is not kept; deleting an
 * import deletes its transactions.
 *
 * @property Carbon $tanggal_awal
 * @property Carbon $tanggal_akhir
 */
#[Fillable(['loket', 'nama_loket', 'nama_berkas', 'tanggal_awal', 'tanggal_akhir', 'jumlah_transaksi', 'jumlah_dilewati', 'total', 'tampil', 'user_id'])]
class RevenueImport extends Model
{
    /** @use HasFactory<RevenueImportFactory> */
    use HasFactory, HasUlids;

    /**
     * A new import stays off the public portal until it is checked, as in the
     * table.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'tampil' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_awal' => 'date',
            'tanggal_akhir' => 'date',
            'jumlah_transaksi' => 'integer',
            'jumlah_dilewati' => 'integer',
            'total' => 'integer',
            'tampil' => 'boolean',
        ];
    }

    /**
     * The transactions this import added.
     *
     * @return HasMany<RevenueTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(RevenueTransaction::class);
    }

    /**
     * The employee who imported the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The import as the activity log names it, e.g. "STS Loket SAMSAT
     * 2026-10-14" or "STS Loket SAMSAT 2026-09-01 s.d. 2026-09-30".
     */
    public function title(): string
    {
        $first = $this->tanggal_awal->toDateString();
        $last = $this->tanggal_akhir->toDateString();

        return "STS {$this->nama_loket} ".($first === $last ? $first : "{$first} s.d. {$last}");
    }
}
