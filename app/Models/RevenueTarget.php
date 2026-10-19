<?php

namespace App\Models;

use Database\Factories\RevenueTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The revenue target of a year (D-068), for the realisation report (Form I):
 * one target per numbered kind of revenue, PKB and its fines each including
 * arrears. The "saldo awal" is the realisation of the months before the STS
 * imports start, entered by hand through "saldo_sampai_bulan"; the report
 * ignores STS transactions of those months. Amounts are whole rupiah.
 */
#[Fillable(['tahun', 'target_pkb', 'target_bbn', 'target_denda_pkb', 'target_denda_bbn', 'saldo_sampai_bulan', 'saldo_pkb',
    'saldo_tunggakan', 'saldo_bbn', 'saldo_denda_pkb', 'saldo_denda_tunggakan', 'saldo_denda_bbn', 'user_id'])]
class RevenueTarget extends Model
{
    /** @use HasFactory<RevenueTargetFactory> */
    use HasFactory;

    /**
     * The targets, by the STS amount each one covers.
     *
     * @var array<string, list<string>>
     */
    public const array TARGETS = [
        'target_pkb' => ['pkb', 'tunggakan'],
        'target_bbn' => ['bbn'],
        'target_denda_pkb' => ['denda_pkb', 'denda_tunggakan'],
        'target_denda_bbn' => ['denda_bbn'],
    ];

    /**
     * The STS amounts the report counts, in the order of Form I. Opsen goes
     * to the cities and is not part of it.
     *
     * @var list<string>
     */
    public const array PARTS = ['pkb', 'tunggakan', 'bbn', 'denda_pkb', 'denda_tunggakan', 'denda_bbn'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'saldo_sampai_bulan' => 'integer',
            ...array_fill_keys(array_keys(self::TARGETS), 'integer'),
            ...array_fill_keys(array_map(fn (string $part): string => "saldo_{$part}", self::PARTS), 'integer'),
        ];
    }

    /**
     * The employee who last saved the target.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
