<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\DashboardModule;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state: an active employee (Pegawai UPTD).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nip' => fake()->unique()->numerify('19################'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Pegawai,
            'status' => AccountStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * An administrator account.
     */
    public function administrator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Administrator,
        ]);
    }

    /**
     * An account in the given lifecycle state.
     */
    public function withStatus(AccountStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }

    /**
     * An account assigned the given dashboard modules.
     */
    public function withModules(DashboardModule ...$modules): static
    {
        return $this->state(fn (array $attributes): array => [
            'modules' => array_map(fn (DashboardModule $module): string => $module->value, $modules),
        ]);
    }
}
