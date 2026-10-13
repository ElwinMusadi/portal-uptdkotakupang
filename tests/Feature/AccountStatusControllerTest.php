<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccountStatusControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $account = User::factory()->create();

        $response = $this->putJson("/api/akun/{$account->id}/status", ['status' => 'suspended']);

        $response->assertUnauthorized();
        $this->assertSame(AccountStatus::Active, $account->fresh()->status);
    }

    public function test_forbids_employees_with_403(): void
    {
        $account = User::factory()->create();

        $response = $this->actingAs(User::factory()->create())->putJson("/api/akun/{$account->id}/status", ['status' => 'suspended']);

        $response->assertForbidden()->assertExactJson(['message' => 'Hanya Administrator yang dapat mengelola akun.']);
        $this->assertSame(AccountStatus::Active, $account->fresh()->status);
    }

    public function test_forbids_administrators_to_change_their_own_status_with_403(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)->putJson("/api/akun/{$administrator->id}/status", ['status' => 'deactivated']);

        $response->assertForbidden()->assertExactJson(['message' => 'Anda tidak dapat mengubah status akun Anda sendiri.']);
        $this->assertSame(AccountStatus::Active, $administrator->fresh()->status);
    }

    /**
     * @return array<string, array{AccountStatus, AccountStatus}>
     */
    public static function allowedTransitions(): array
    {
        return [
            'suspend an active account' => [AccountStatus::Active, AccountStatus::Suspended],
            'deactivate an active account' => [AccountStatus::Active, AccountStatus::Deactivated],
            'reactivate a suspended account' => [AccountStatus::Suspended, AccountStatus::Active],
            'deactivate a suspended account' => [AccountStatus::Suspended, AccountStatus::Deactivated],
            'deactivate a pending account' => [AccountStatus::Pending, AccountStatus::Deactivated],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_changes_the_status_along_an_allowed_transition(AccountStatus $from, AccountStatus $to): void
    {
        $account = User::factory()->withStatus($from)->create();

        $response = $this->actingAs($this->administrator())->putJson("/api/akun/{$account->id}/status", ['status' => $to->value]);

        $response->assertOk()->assertJsonPath('id', $account->id)->assertJsonPath('status', $to->value);
        $this->assertSame($to, $account->fresh()->status);
    }

    /**
     * @return array<string, array{AccountStatus, AccountStatus, string}>
     */
    public static function refusedTransitions(): array
    {
        $onlyActive = 'Hanya akun aktif yang bisa ditangguhkan.';
        $onlySuspended = 'Hanya akun yang ditangguhkan yang bisa diaktifkan kembali. Akun lain aktif setelah pemiliknya memakai tautan aktivasi.';

        return [
            'suspend a pending account' => [AccountStatus::Pending, AccountStatus::Suspended, $onlyActive],
            'activate a pending account' => [AccountStatus::Pending, AccountStatus::Active, $onlySuspended],
            'suspend a deactivated account' => [AccountStatus::Deactivated, AccountStatus::Suspended, $onlyActive],
            'activate a deactivated account' => [AccountStatus::Deactivated, AccountStatus::Active, $onlySuspended],
            'activate an active account' => [AccountStatus::Active, AccountStatus::Active, 'Akun ini sudah aktif.'],
            'suspend a suspended account' => [AccountStatus::Suspended, AccountStatus::Suspended, 'Akun ini sudah ditangguhkan.'],
            'deactivate a deactivated account' => [AccountStatus::Deactivated, AccountStatus::Deactivated, 'Akun ini sudah dinonaktifkan.'],
        ];
    }

    #[DataProvider('refusedTransitions')]
    public function test_rejects_a_transition_that_is_not_allowed_with_422(AccountStatus $from, AccountStatus $to, string $message): void
    {
        $account = User::factory()->withStatus($from)->create();

        $response = $this->actingAs($this->administrator())->putJson("/api/akun/{$account->id}/status", ['status' => $to->value]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['status' => $message]);
        $this->assertSame($from, $account->fresh()->status);
    }

    #[TestWith(['pending'])]
    #[TestWith(['dihapus'])]
    public function test_rejects_a_status_that_cannot_be_set_directly(string $status): void
    {
        $account = User::factory()->create();

        $response = $this->actingAs($this->administrator())->putJson("/api/akun/{$account->id}/status", ['status' => $status]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['status' => 'Status tidak valid.']);
        $this->assertSame(AccountStatus::Active, $account->fresh()->status);
    }

    public function test_requires_a_status(): void
    {
        $account = User::factory()->create();

        $response = $this->actingAs($this->administrator())->putJson("/api/akun/{$account->id}/status", []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['status' => 'Pilih status akun.']);
    }

    #[TestWith(['suspended'])]
    #[TestWith(['deactivated'])]
    public function test_forgets_remembered_devices_when_the_account_stops_being_active(string $status): void
    {
        $account = User::factory()->create(['remember_token' => 'token-perangkat-lama']);

        $this->actingAs($this->administrator())->putJson("/api/akun/{$account->id}/status", ['status' => $status])->assertOk();

        $this->assertNotSame('token-perangkat-lama', $account->fresh()->remember_token);
    }

    public function test_keeps_remembered_devices_when_a_suspended_account_is_reactivated(): void
    {
        $account = User::factory()->withStatus(AccountStatus::Suspended)->create(['remember_token' => 'token-perangkat-lama']);

        $this->actingAs($this->administrator())->putJson("/api/akun/{$account->id}/status", ['status' => 'active'])->assertOk();

        $this->assertSame('token-perangkat-lama', $account->fresh()->remember_token);
    }

    public function test_logs_the_status_change(): void
    {
        $administrator = $this->administrator();
        $account = User::factory()->create();
        Log::spy();

        $this->actingAs($administrator)->putJson("/api/akun/{$account->id}/status", ['status' => 'suspended']);

        Log::shouldHaveReceived('info')->once()->with('account.status', [
            'actor' => $administrator->id,
            'user' => $account->id,
            'from' => 'active',
            'to' => 'suspended',
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_returns_404_for_an_account_that_does_not_exist(): void
    {
        $response = $this->actingAs($this->administrator())->putJson('/api/akun/01kaaaaaaaaaaaaaaaaaaaaaaa/status', ['status' => 'suspended']);

        $response->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }
}
