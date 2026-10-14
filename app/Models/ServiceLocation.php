<?php

namespace App\Models;

use Database\Factories\ServiceLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A place in the list picked when service outside the office is scheduled
 * (D-044). Schedule entries keep their own copy of the place's name, so
 * renaming or removing a place here does not change them.
 */
#[Fillable(['nama', 'kecamatan'])]
class ServiceLocation extends Model
{
    /** @use HasFactory<ServiceLocationFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    /**
     * Limit the query to the place with this name, whatever its letter case.
     */
    #[Scope]
    protected function named(Builder $query, string $name): void
    {
        $query->whereRaw('lower(nama) = ?', [mb_strtolower($name)]);
    }

    /**
     * The place as the activity log names it.
     */
    public function title(): string
    {
        return "Lokasi {$this->nama}";
    }
}
