<?php

namespace Database\Factories;

use App\Models\RevenueTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RevenueTarget>
 */
class RevenueTargetFactory extends Factory
{
    /**
     * Define the model's default state: the 2026 targets of the owner's Form I
     * example, with every month taken from the STS imports.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tahun' => 2026,
            'target_pkb' => 240000209667,
            'target_bbn' => 73264603499,
            'target_denda_pkb' => 8225972907,
            'target_denda_bbn' => 69224567,
            'saldo_sampai_bulan' => null,
            ...array_fill_keys(array_map(fn (string $part): string => "saldo_{$part}", RevenueTarget::PARTS), 0),
        ];
    }

    /**
     * The realisation of the months through the given one entered by hand;
     * the amounts not given are zero.
     *
     * @param  array<string, int>  $amounts  by STS amount, e.g. ['pkb' => 1000]
     */
    public function openingThrough(int $month, array $amounts): static
    {
        $balances = [];
        foreach (RevenueTarget::PARTS as $part) {
            $balances["saldo_{$part}"] = $amounts[$part] ?? 0;
        }

        return $this->state(['saldo_sampai_bulan' => $month, ...$balances]);
    }
}
