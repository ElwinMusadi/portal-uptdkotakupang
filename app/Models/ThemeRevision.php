<?php

namespace App\Models;

use Database\Factories\ThemeRevisionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A theme as an administrator saved it in Pengaturan › Tampilan (D-057):
 * both parts of the document, who saved it and when. Only the last
 * KEPT revisions stay.
 */
class ThemeRevision extends Model
{
    /** @use HasFactory<ThemeRevisionFactory> */
    use HasFactory, HasUlids;

    /**
     * How many saved themes are kept.
     */
    public const int KEPT = 5;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tema' => 'array',
        ];
    }

    /**
     * The administrator who saved the theme; null once the account is deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
