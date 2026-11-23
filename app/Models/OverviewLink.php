<?php

namespace App\Models;

use Database\Factories\OverviewLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A link to the Ikhtisar page an administrator made (D-099): whoever holds
 * it sees the revenue, Form I and Pemetaan PKB figures without signing in,
 * until it is revoked. The token is shown once; only its hash is kept.
 *
 * @property ?Carbon $terakhir_dibuka
 */
#[Fillable(['nama', 'token_hash', 'terakhir_dibuka', 'dibuat_oleh'])]
class OverviewLink extends Model
{
    /** @use HasFactory<OverviewLinkFactory> */
    use HasFactory, HasUlids;

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
            'terakhir_dibuka' => 'datetime',
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
     * The administrator who made the link.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
