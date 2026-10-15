<?php

namespace Database\Factories;

use App\Enums\QueueStage;
use App\Models\QueueService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QueueService>
 */
class QueueServiceFactory extends Factory
{
    /**
     * A service beside the four created by the migration (D-051), going
     * through every stage.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->randomElement(range('E', 'Z')),
            'nama' => fake()->words(2, true),
            'cakupan' => '',
            'tahap' => array_map(fn (QueueStage $stage): string => $stage->value, QueueStage::cases()),
            'aktif' => true,
            'urutan' => 10,
        ];
    }
}
