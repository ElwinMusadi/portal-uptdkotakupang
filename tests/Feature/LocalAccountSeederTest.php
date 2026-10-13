<?php

namespace Tests\Feature;

use Database\Seeders\LocalAccountSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LocalAccountSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_a_local_administrator_who_can_sign_in(): void
    {
        $this->seed(LocalAccountSeeder::class);

        $response = $this->withHeader('Referer', config('app.url').'/login')
            ->postJson('/api/masuk', ['nip' => LocalAccountSeeder::NIP, 'sandi' => LocalAccountSeeder::PASSWORD]);

        $response->assertOk()->assertJsonPath('peran', 'administrator');
    }

    public function test_refuses_to_create_the_account_outside_local_development(): void
    {
        $this->app['env'] = 'production';

        $this->assertThrows(fn () => $this->app->make(LocalAccountSeeder::class)->run(), RuntimeException::class);

        $this->assertDatabaseCount('users', 0);
    }
}
