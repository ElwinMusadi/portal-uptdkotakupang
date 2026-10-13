<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['/dashboard'])]
    #[TestWith(['/dashboard/'])]
    public function test_serves_the_dashboard_shell_to_a_signed_in_employee(string $uri): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get($uri);

        $response->assertOk()
            ->assertSee('<div id="root"></div>', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    }

    public function test_redirects_guests_to_the_login_page(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login?lanjut=dashboard%2F');
    }

    public function test_signs_out_an_account_that_is_no_longer_active_and_redirects_it_to_the_login_page(): void
    {
        $user = User::factory()->withStatus(AccountStatus::Deactivated)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login?lanjut=dashboard%2F');
        $this->assertGuest();
    }
}
