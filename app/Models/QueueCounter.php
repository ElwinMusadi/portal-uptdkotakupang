<?php

namespace App\Models;

use App\Enums\QueueStage;
use Database\Factories\QueueCounterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A counter that calls queue numbers (Antrean Online, D-051): its name, the
 * calling stage it serves, the services it calls (all when the list is
 * empty), and how the TV display reads its name aloud.
 *
 * @property QueueStage $tahap
 * @property list<string> $layanan
 */
#[Fillable(['nama', 'tahap', 'layanan', 'nama_suara', 'aktif', 'urutan'])]
class QueueCounter extends Model
{
    /** @use HasFactory<QueueCounterFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahap' => QueueStage::class,
            'layanan' => 'array',
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * The tickets the counter calls or called last at their stage.
     *
     * @return HasMany<QueueTicket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * The counters in the order of their stages, then as arranged.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('nama');
    }
}
