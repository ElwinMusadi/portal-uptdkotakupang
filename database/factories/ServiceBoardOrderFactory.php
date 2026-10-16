<?php

namespace Database\Factories;

use App\Models\MobileServiceStop;
use App\Models\ServiceBoardOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceBoardOrder>
 */
class ServiceBoardOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->unique()->dateTimeBetween('now', '+1 week')->format('Y-m-d'),
            'urutan' => [],
        ];
    }

    /**
     * The given entries first on the board on the given date, in that order.
     *
     * @param  list<MobileServiceStop>  $stops
     */
    public function of(string $tanggal, array $stops): static
    {
        return $this->state(fn (): array => [
            'tanggal' => $tanggal,
            'urutan' => array_map(fn (MobileServiceStop $stop): string => $stop->id, $stops),
        ]);
    }
}
