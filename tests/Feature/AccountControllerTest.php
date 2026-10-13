<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\DashboardModule;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string NIP = '199001012020121001';

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'list' => ['GET', '/api/akun'],
            'create' => ['POST', '/api/akun'],
            'edit' => ['PATCH', '/api/akun/{id}'],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $account = User::factory()->create(['name' => 'Nama Lama']);

        $response = $this->json($method, str_replace('{id}', $account->id, $uri), $this->newAccount());

        $response->assertUnauthorized();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Nama Lama', $account->fresh()->name);
    }

    #[DataProvider('routes')]
    public function test_forbids_employees_with_403(string $method, string $uri): void
    {
        $account = User::factory()->create(['name' => 'Nama Lama']);

        $response = $this->actingAs(User::factory()->create())
            ->json($method, str_replace('{id}', $account->id, $uri), $this->newAccount());

        $response->assertForbidden()->assertExactJson(['message' => 'Hanya Administrator yang dapat mengelola akun.']);
        $this->assertDatabaseCount('users', 2);
        $this->assertSame('Nama Lama', $account->fresh()->name);
    }

    public function test_ends_the_session_of_an_administrator_suspended_since(): void
    {
        $administrator = User::factory()->administrator()->withStatus(AccountStatus::Suspended)->create();

        $response = $this->actingAs($administrator)->getJson('/api/akun');

        $response->assertUnauthorized()->assertExactJson(['message' => 'Akun Anda tidak aktif.']);
    }

    public function test_lists_every_account_by_name_without_secrets(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $administrator = User::factory()->administrator()->create(['nip' => '198501012010011001', 'name' => 'Agustina Lay', 'email' => 'agustina@uptd.test']);
        $pending = User::factory()->withStatus(AccountStatus::Pending)->create(['nip' => '199501012020011002', 'name' => 'Yohanes Bili', 'email' => null]);
        $suspended = User::factory()
            ->withStatus(AccountStatus::Suspended)
            ->withModules(DashboardModule::Keliling, DashboardModule::Berita)
            ->create(['nip' => '199001012015011003', 'name' => 'Maria Bria', 'email' => 'maria@uptd.test']);

        $response = $this->actingAs($administrator)->getJson('/api/akun');

        $response->assertOk()->assertExactJson([
            $this->accountJson($administrator, '198501012010011001', 'Agustina Lay', 'agustina@uptd.test', 'administrator', 'active'),
            $this->accountJson($suspended, '199001012015011003', 'Maria Bria', 'maria@uptd.test', 'pegawai', 'suspended', ['berita', 'keliling']),
            $this->accountJson($pending, '199501012020011002', 'Yohanes Bili', null, 'pegawai', 'pending'),
        ]);
        $this->assertSame(['Agustina Lay', 'Maria Bria', 'Yohanes Bili'], $response->json('*.nama'));
        $this->assertSame(['berita', 'keliling'], $response->json('1.modul'));
    }

    public function test_creates_a_pending_account_and_returns_its_activation_link_with_201(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));

        $response = $this->actingAs($this->administrator())->postJson('/api/akun', $this->newAccount());

        $account = User::query()->where('nip', self::NIP)->sole();
        $response->assertCreated()
            ->assertJsonPath('akun', $this->accountJson($account, self::NIP, 'Maria Bria', 'maria@uptd.test', 'pegawai', 'pending', ['berita', 'faq']))
            ->assertJsonPath('kedaluwarsa', '2026-10-13T09:00:00+00:00');
        $this->assertStringStartsWith(config('app.url').'/aktivasi/'.$account->id.'?', $response->json('tautan'));
        $this->assertSame(AccountStatus::Pending, $account->status);
        $this->assertSame(UserRole::Pegawai, $account->role);
    }

    public function test_returns_a_link_with_which_the_owner_activates_the_account(): void
    {
        $link = $this->actingAs($this->administrator())->postJson('/api/akun', $this->newAccount())->json('tautan');

        $response = $this->post($link, ['sandi' => 'sandi-baru-aman-2026', 'sandi_ulang' => 'sandi-baru-aman-2026']);

        $response->assertRedirect('/login?aktif=1');
        $account = User::query()->where('nip', self::NIP)->sole();
        $this->assertSame(AccountStatus::Active, $account->status);
        $this->assertTrue(Hash::check('sandi-baru-aman-2026', $account->password));
    }

    public function test_logs_who_created_the_account(): void
    {
        $administrator = $this->administrator();
        Log::spy();

        $this->actingAs($administrator)->postJson('/api/akun', $this->newAccount());

        $account = User::query()->where('nip', self::NIP)->sole();
        Log::shouldHaveReceived('info')->once()->with('account.created', [
            'actor' => $administrator->id,
            'user' => $account->id,
            'role' => 'pegawai',
            'modules' => ['berita', 'faq'],
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_stores_each_chosen_module_once_in_a_fixed_order(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', ['modul' => ['pesan', 'faq', 'berita', 'faq']] + $this->newAccount());

        $response->assertCreated()->assertJsonPath('akun.modul', ['pesan', 'berita', 'faq']);
        $this->assertSame(['pesan', 'berita', 'faq'], User::query()->where('nip', self::NIP)->sole()->modules);
    }

    public function test_creates_an_account_without_modules_when_none_are_chosen(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', Arr::except($this->newAccount(), 'modul'));

        $response->assertCreated()->assertJsonPath('akun.modul', []);
        $this->assertSame([], User::query()->where('nip', self::NIP)->sole()->modules);
    }

    public function test_rejects_a_module_it_does_not_know(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', ['modul' => ['berita', 'keuangan']] + $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['modul.1' => 'Modul tidak dikenal.']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_rejects_modules_that_are_not_a_list(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', ['modul' => 'berita'] + $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['modul' => 'Daftar modul tidak valid.']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_requires_a_nip_a_name_and_a_role(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nip' => 'NIP wajib diisi.',
            'nama' => 'Nama wajib diisi.',
            'peran' => 'Pilih peran akun.',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    #[TestWith(['12345'])]
    #[TestWith(['19900101202012100A'])]
    #[TestWith(['1990010120201210011'])]
    public function test_rejects_a_nip_that_is_not_18_digits(string $nip): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', ['nip' => $nip] + $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['nip' => 'NIP harus 18 digit angka.']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_rejects_a_nip_that_is_already_registered(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->actingAs($this->administrator())->postJson('/api/akun', $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['nip' => 'NIP ini sudah terdaftar.']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_rejects_an_email_another_account_uses(): void
    {
        User::factory()->create(['email' => 'maria@uptd.test']);

        $response = $this->actingAs($this->administrator())->postJson('/api/akun', $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['surel' => 'Surel ini sudah dipakai akun lain.']);
    }

    public function test_rejects_an_invalid_email(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', ['surel' => 'bukan-surel'] + $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['surel' => 'Surel tidak valid.']);
    }

    public function test_rejects_an_unknown_role(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', ['peran' => 'superadmin'] + $this->newAccount());

        $response->assertUnprocessable()->assertJsonValidationErrors(['peran' => 'Peran tidak valid.']);
    }

    public function test_ignores_attributes_that_only_the_application_sets(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/akun', [
            'id' => '01kaaaaaaaaaaaaaaaaaaaaaaa',
            'status' => 'active',
            'password' => 'kata-sandi-titipan-2026',
            'role' => 'administrator',
            'modules' => ['pesan'],
        ] + $this->newAccount());

        $response->assertCreated();
        $account = User::query()->where('nip', self::NIP)->sole();
        $this->assertNotSame('01kaaaaaaaaaaaaaaaaaaaaaaa', $account->id);
        $this->assertSame(AccountStatus::Pending, $account->status);
        $this->assertSame(UserRole::Pegawai, $account->role);
        $this->assertSame(['berita', 'faq'], $account->modules);
        $this->assertFalse(Hash::check('kata-sandi-titipan-2026', $account->password));
    }

    public function test_changes_the_name_email_and_role(): void
    {
        $account = User::factory()->create(['nip' => self::NIP, 'name' => 'Maria Bria', 'email' => 'maria@uptd.test']);

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", [
            'nama' => 'Maria Bria Kana',
            'surel' => 'maria.kana@uptd.test',
            'peran' => 'administrator',
        ]);

        $response->assertOk()
            ->assertJsonPath('nama', 'Maria Bria Kana')
            ->assertJsonPath('surel', 'maria.kana@uptd.test')
            ->assertJsonPath('peran', 'administrator');
        $account->refresh();
        $this->assertSame('Maria Bria Kana', $account->name);
        $this->assertSame('maria.kana@uptd.test', $account->email);
        $this->assertSame(UserRole::Administrator, $account->role);
    }

    public function test_keeps_the_fields_that_are_not_sent(): void
    {
        $account = User::factory()->withModules(DashboardModule::Faq)->create(['name' => 'Maria Bria', 'email' => 'maria@uptd.test']);

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['nama' => 'Maria Bria Kana']);

        $response->assertOk();
        $account->refresh();
        $this->assertSame('maria@uptd.test', $account->email);
        $this->assertSame(UserRole::Pegawai, $account->role);
        $this->assertSame(['faq'], $account->modules);
    }

    public function test_replaces_the_assigned_modules(): void
    {
        $account = User::factory()->withModules(DashboardModule::Berita, DashboardModule::Faq)->create();

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['modul' => ['keliling', 'jam']]);

        $response->assertOk()->assertJsonPath('modul', ['jam', 'keliling']);
        $this->assertSame(['jam', 'keliling'], $account->fresh()->modules);
    }

    public function test_removes_every_module_when_sent_an_empty_list(): void
    {
        $account = User::factory()->withModules(DashboardModule::Berita)->create();

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['modul' => []]);

        $response->assertOk()->assertJsonPath('modul', []);
        $this->assertSame([], $account->fresh()->modules);
    }

    public function test_rejects_a_module_it_does_not_know_when_editing(): void
    {
        $account = User::factory()->withModules(DashboardModule::Berita)->create();

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['modul' => ['keuangan']]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['modul.0' => 'Modul tidak dikenal.']);
        $this->assertSame(['berita'], $account->fresh()->modules);
    }

    public function test_removes_the_email_when_it_is_sent_empty(): void
    {
        $account = User::factory()->create(['email' => 'maria@uptd.test']);

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['surel' => '']);

        $response->assertOk()->assertJsonPath('surel', null);
        $this->assertNull($account->fresh()->email);
    }

    public function test_accepts_the_accounts_own_email_sent_unchanged(): void
    {
        $account = User::factory()->create(['email' => 'maria@uptd.test']);

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['surel' => 'maria@uptd.test']);

        $response->assertOk();
    }

    public function test_rejects_an_email_another_account_uses_when_editing(): void
    {
        User::factory()->create(['email' => 'yohanes@uptd.test']);
        $account = User::factory()->create(['email' => 'maria@uptd.test']);

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", ['surel' => 'yohanes@uptd.test']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['surel' => 'Surel ini sudah dipakai akun lain.']);
        $this->assertSame('maria@uptd.test', $account->fresh()->email);
    }

    public function test_rejects_an_administrators_change_to_their_own_role_with_422(): void
    {
        $administrator = $this->administrator();

        $response = $this->actingAs($administrator)->patchJson("/api/akun/{$administrator->id}", ['peran' => 'pegawai']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['peran' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
        $this->assertSame(UserRole::Administrator, $administrator->fresh()->role);
    }

    public function test_lets_administrators_rename_themselves_with_their_role_unchanged(): void
    {
        $administrator = $this->administrator();

        $response = $this->actingAs($administrator)->patchJson("/api/akun/{$administrator->id}", [
            'nama' => 'Agustina Lay',
            'peran' => 'administrator',
        ]);

        $response->assertOk()->assertJsonPath('nama', 'Agustina Lay');
        $this->assertSame('Agustina Lay', $administrator->fresh()->name);
    }

    public function test_never_changes_the_nip_status_or_password(): void
    {
        $account = User::factory()->withStatus(AccountStatus::Suspended)->create(['nip' => self::NIP]);

        $response = $this->actingAs($this->administrator())->patchJson("/api/akun/{$account->id}", [
            'nip' => '199001012020121009',
            'status' => 'active',
            'password' => 'kata-sandi-titipan-2026',
        ]);

        $response->assertOk();
        $account->refresh();
        $this->assertSame(self::NIP, $account->nip);
        $this->assertSame(AccountStatus::Suspended, $account->status);
        $this->assertTrue(Hash::check('password', $account->password));
    }

    public function test_logs_which_fields_changed(): void
    {
        $administrator = $this->administrator();
        $account = User::factory()->withModules(DashboardModule::Faq)->create(['name' => 'Maria Bria']);
        Log::spy();

        $this->actingAs($administrator)->patchJson("/api/akun/{$account->id}", [
            'nama' => 'Maria Bria Kana',
            'peran' => 'pegawai',
            'modul' => ['faq', 'berita'],
        ]);

        Log::shouldHaveReceived('info')->once()->with('account.updated', [
            'actor' => $administrator->id,
            'user' => $account->id,
            'fields' => ['name', 'modules'],
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_returns_404_for_an_account_that_does_not_exist(): void
    {
        $response = $this->actingAs($this->administrator())->patchJson('/api/akun/01kaaaaaaaaaaaaaaaaaaaaaaa', ['nama' => 'Maria Bria']);

        $response->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_returns_404_for_an_address_that_is_not_an_account_id(): void
    {
        $response = $this->actingAs($this->administrator())->patchJson('/api/akun/1', ['nama' => 'Maria Bria']);

        $response->assertNotFound();
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }

    /**
     * @return array{nip: string, nama: string, surel: string, peran: string, modul: list<string>}
     */
    private function newAccount(): array
    {
        return ['nip' => self::NIP, 'nama' => 'Maria Bria', 'surel' => 'maria@uptd.test', 'peran' => 'pegawai', 'modul' => ['faq', 'berita']];
    }

    /**
     * @param  list<string>  $modules
     * @return array<string, mixed>
     */
    private function accountJson(User $account, string $nip, string $name, ?string $email, string $role, string $status, array $modules = []): array
    {
        return [
            'id' => $account->id,
            'nip' => $nip,
            'nama' => $name,
            'surel' => $email,
            'peran' => $role,
            'status' => $status,
            'modul' => $modules,
            'dibuat' => '2026-10-13T08:00:00+00:00',
            'diperbarui' => '2026-10-13T08:00:00+00:00',
        ];
    }
}
