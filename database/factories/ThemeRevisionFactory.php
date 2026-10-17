<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\ThemeRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeRevision>
 */
class ThemeRevisionFactory extends Factory
{
    /**
     * Define the model's default state: a portal with a random accent saved
     * by an administrator, the dashboard in the built-in look.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tema' => [
                'portal' => [...Setting::DEFAULT_THEME_SECTION, 'aksen' => strtoupper(fake()->hexColor())],
                'dashboard' => Setting::DEFAULT_THEME_SECTION,
            ],
            'user_id' => User::factory()->administrator(),
            'created_at' => now(),
        ];
    }
}
