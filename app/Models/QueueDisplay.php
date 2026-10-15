<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A waiting-room TV display an administrator set up (D-051). It shows the
 * numbers called, with their plate numbers, so only a display holding its
 * token may read them. The token is shown once; only its hash is kept.
 *
 * @property ?Carbon $terakhir_terlihat
 */
#[Fillable(['nama', 'token_hash', 'terakhir_terlihat', 'dibuat_oleh'])]
class QueueDisplay extends Model
{
    use HasUlids;

    /**
     * The hash is never sent anywhere.
     *
     * @var list<string>
     */
    protected $hidden = ['token_hash'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'terakhir_terlihat' => 'datetime',
        ];
    }

    /**
     * The hash a token is kept as.
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * The administrator who set the display up.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
