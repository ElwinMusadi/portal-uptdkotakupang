<?php

namespace Database\Factories;

use App\Models\OverviewLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OverviewLink>
 */
class OverviewLinkFactory extends Factory
{
    /**
     * Define the model's default state: a link for a random token. Use
     * withToken() to know the token.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Rapat '.fake()->word(),
            'token_hash' => OverviewLink::hash(Str::random(48)),
            'dibuat_oleh' => User::factory()->administrator(),
        ];
    }

    /**
     * A link holding the given token.
     */
    public function withToken(string $token): static
    {
        return $this->state(['token_hash' => OverviewLink::hash($token)]);
    }
}
