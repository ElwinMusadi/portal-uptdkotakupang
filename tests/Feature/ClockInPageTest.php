<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClockInPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_serves_the_installable_attendance_app_to_a_signed_in_employee(): void
    {
        $this->withoutVite();

        $response = $this->actingAs(User::factory()->create())->get('/absen');

        $response->assertOk()
            ->assertSee('<div id="root"></div>', false)
            ->assertSee('<link rel="manifest" href="'.asset('absen.webmanifest').'" />', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    }

    public function test_sends_guests_to_the_login_page_and_back_to_the_app(): void
    {
        $this->get('/absen')->assertRedirect('/login?lanjut=absen');
        $this->get('/absen/lain')->assertNotFound();
    }

    public function test_signs_out_an_account_that_is_no_longer_active(): void
    {
        $user = User::factory()->withStatus(AccountStatus::Deactivated)->create();

        $response = $this->actingAs($user)->get('/absen');

        $response->assertRedirect('/login?lanjut=absen');
        $this->assertGuest();
    }
}
