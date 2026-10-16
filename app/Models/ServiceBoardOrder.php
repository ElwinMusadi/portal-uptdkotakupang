<?php

namespace App\Models;

use Database\Factories\ServiceBoardOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The order of the service outside the office on the homepage board for one
 * date (D-053): the listed entries come first, in this order, and the other
 * entries served that date follow them by time. The Jadwal page keeps
 * listing every date by time.
 *
 * @property list<string> $urutan
 */
#[Fillable(['tanggal', 'urutan'])]
class ServiceBoardOrder extends Model
{
    /** @use HasFactory<ServiceBoardOrderFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'array',
        ];
    }

    /**
     * The date as "YYYY-MM-DD", whatever the database returns.
     *
     * @return Attribute<string, string>
     */
    protected function tanggal(): Attribute
    {
        return Attribute::make(get: fn (string $value): string => substr($value, 0, 10));
    }
}
