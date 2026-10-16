<?php

namespace App\Models;

use Database\Factories\PotentialImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An import of BPAD's potential-tax file for Pemetaan PKB (D-052). Its rows
 * arrive in parts; once all arrived the import is finished and replaces the
 * data before it, so Pemetaan PKB always shows the latest finished import.
 * Its figures appear on the public portal once an Administrator turns
 * "tampil" on (D-052 slice 2).
 *
 * @property ?Carbon $tanggal_penetapan
 * @property ?Carbon $selesai_pada
 */
#[Fillable(['berkas', 'jumlah', 'diterima', 'tanggal_penetapan', 'diimpor_oleh', 'selesai_pada', 'tampil'])]
class PotentialImport extends Model
{
    /** @use HasFactory<PotentialImportFactory> */
    use HasFactory, HasUlids;

    /**
     * A new import has received no row yet, as in the table.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'diterima' => 0,
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
            'jumlah' => 'integer',
            'diterima' => 'integer',
            'tanggal_penetapan' => 'date',
            'selesai_pada' => 'datetime',
            'tampil' => 'boolean',
        ];
    }

    /**
     * The import Pemetaan PKB shows: the latest finished one.
     */
    public static function current(): ?self
    {
        return self::query()->whereNotNull('selesai_pada')->latest('selesai_pada')->orderByDesc('id')->first();
    }

    /**
     * Imports that are not finished.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUnfinished(Builder $query): void
    {
        $query->whereNull('selesai_pada');
    }

    /**
     * The vehicles of the file.
     *
     * @return HasMany<PotentialVehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(PotentialVehicle::class);
    }

    /**
     * The employee who imported the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diimpor_oleh');
    }
}
