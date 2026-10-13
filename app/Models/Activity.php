<?php

namespace App\Models;

use App\Enums\DashboardModule;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of the dashboard activity log. Entries are written by
 * RecordActivity and never changed afterwards.
 */
class Activity extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'module' => DashboardModule::class,
        ];
    }

    /**
     * The account that did it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
