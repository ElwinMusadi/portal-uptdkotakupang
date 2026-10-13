<?php

namespace Database\Factories;

use App\Enums\FaqStatus;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state: a draft that is not intended for the
     * public, so nothing reaches the portal unless a test opts in.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pertanyaan' => rtrim(fake()->sentence(), '.').'?',
            'jawaban' => fake()->paragraph(),
            'status' => FaqStatus::Draft,
            'publik' => false,
            'tampil' => true,
            'urutan' => 0,
        ];
    }

    /**
     * Published, explicitly intended for the public and switched on.
     */
    public function eligibleForPublic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FaqStatus::Published,
            'publik' => true,
            'tampil' => true,
        ]);
    }
}
