<?php

namespace Tests\Feature;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AccountActivationLinkControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string PASSWORD = 'sandi-baru-aman-2026';

    public function test_returns_401_to_guests(): void
    {
        $account = User::factory()->create();

        $response = $this->postJson("/api/akun/{$account->id}/tautan-aktivasi");

        $response->assertUnauthorized();
        $this->assertSame(AccountStatus::Active, $account->fresh()->status);
    }

    public function test_forbids_employees_with_403(): void
    {
        $account = User::factory()->create();

        $response = $this->actingAs(User::factory()->create())->postJson("/api/akun/{$account->id}/tautan-aktivasi");

        $response->assertForbidden()->assertExactJson(['message' => 'Hanya Administrator yang dapat mengelola akun.']);
        $this->assertSame(AccountStatus::Active, $account->fresh()->status);
    }

    public function test_forbids_administrators_to_reset_their_own_account_with_403(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)->postJson("/api/akun/{$administrator->id}/tautan-aktivasi");

        $response->assertForbidden()->assertExactJson(['message' => 'Anda tidak dapat mengatur ulang akun Anda sendiri.']);
        $this->assertSame(AccountStatus::Active, $administrator->fresh()->status);
        $this->assertTrue(Hash::check('password', $administrator->fresh()->password));
    }

    public function test_replaces_the_link_of_a_pending_account_with_201(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $account = User::factory()->withStatus(AccountStatus::Pending)->create();
        $previous = app(IssueActivationLink::class)->handle($account)['url'];

        $response = $this->actingAs($this->administrator())->postJson("/api/akun/{$account->id}/tautan-aktivasi");

        $response->assertCreated()
            ->assertJsonPath('akun.id', $account->id)
            ->assertJsonPath('akun.status', 'pending')
            ->assertJsonPath('kedaluwarsa', '2026-10-13T09:00:00+00:00');
        $this->get($previous)->assertStatus(410);
        $this->get($response->json('tautan'))->assertOk();
    }

    public function test_resets_an_active_account_so_its_password_and_remembered_devices_stop_working(): void
    {
        $account = User::factory()->create(['remember_token' => 'token-perangkat-lama']);

        $response = $this->actingAs($this->administrator())->postJson("/api/akun/{$account->id}/tautan-aktivasi");

        $response->assertCreated()->assertJsonPath('akun.status', 'pending');
        $account->refresh();
        $this->assertSame(AccountStatus::Pending, $account->status);
        $this->assertFalse(Hash::check('password', $account->password));
        $this->assertNotSame('token-perangkat-lama', $account->remember_token);
    }

    public function test_lets_the_owner_of_a_deactivated_account_activate_it_again_through_the_link(): void
    {
        $account = User::factory()->withStatus(AccountStatus::Deactivated)->create();
        $link = $this->actingAs($this->administrator())->postJson("/api/akun/{$account->id}/tautan-aktivasi")->json('tautan');

        $response = $this->post($link, ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD]);

        $response->assertRedirect('/login?aktif=1');
        $this->assertSame(AccountStatus::Active, $account->fresh()->status);
        $this->assertTrue(Hash::check(self::PASSWORD, $account->fresh()->password));
    }

    public function test_logs_who_issued_the_link(): void
    {
        $administrator = $this->administrator();
        $account = User::factory()->withStatus(AccountStatus::Suspended)->create();
        Log::spy();

        $this->actingAs($administrator)->postJson("/api/akun/{$account->id}/tautan-aktivasi");

        Log::shouldHaveReceived('info')->once()->with('account.activation-link', [
            'actor' => $administrator->id,
            'user' => $account->id,
            'from' => 'suspended',
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_returns_404_for_an_account_that_does_not_exist(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun/01kaaaaaaaaaaaaaaaaaaaaaaa/tautan-aktivasi');

        $response->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }
}
