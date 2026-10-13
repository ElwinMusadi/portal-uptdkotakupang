<?php

namespace Database\Factories;

use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaticPage>
 */
class StaticPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(3),
            'judul' => fake()->sentence(3),
            'judul_lanjutan' => fake()->sentence(3),
            'pengantar' => fake()->sentence(5),
            'isi' => '<h2>'.fake()->sentence(3).'</h2><p>'.fake()->paragraph().'</p>',
            'urutan' => fake()->numberBetween(0, 10),
        ];
    }
}
