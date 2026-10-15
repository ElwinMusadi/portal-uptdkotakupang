<?php

namespace App\Models;

use App\Enums\QueueStage;
use Database\Factories\QueueServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A service a queue number is printed for (Antrean Online, D-051): its code
 * letter (A–D), name, what it covers, and the stages its tickets go through,
 * in flow order.
 *
 * @property list<string> $tahap
 */
#[Fillable(['kode', 'nama', 'cakupan', 'tahap', 'aktif', 'urutan'])]
class QueueService extends Model
{
    /** @use HasFactory<QueueServiceFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahap' => 'array',
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * The tickets printed for the service.
     *
     * @return HasMany<QueueTicket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    /**
     * The services in the order the information desk lists them.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('kode');
    }

    /**
     * The service's stages in flow order.
     *
     * @return list<QueueStage>
     */
    public function stages(): array
    {
        return QueueStage::inOrder($this->tahap);
    }
}
