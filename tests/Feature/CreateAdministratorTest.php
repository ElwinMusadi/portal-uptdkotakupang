<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class CreateAdministratorTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string NIP = '199001012020121001';

    public function test_creates_an_active_administrator_with_the_password_typed_in(): void
    {
        $this->artisan('pintu:create-admin', ['nip' => self::NIP, 'nama' => 'Kepala Subbag TU'])
            ->expectsQuestion('Kata sandi', 'kata-sandi-kuat-2026')
            ->expectsQuestion('Ulangi kata sandi', 'kata-sandi-kuat-2026')
            ->expectsOutputToContain('Administrator Kepala Subbag TU (NIP '.self::NIP.') dibuat dan aktif.')
            ->assertSuccessful();

        $user = User::where('nip', self::NIP)->sole();
        $this->assertSame(UserRole::Administrator, $user->role);
        $this->assertSame(AccountStatus::Active, $user->status);
        $this->assertTrue(Hash::check('kata-sandi-kuat-2026', $user->password));
    }

    public function test_prints_a_working_activation_link_instead_of_a_password_without_interaction(): void
    {
        $buffer = new BufferedOutput;

        Artisan::call('pintu:create-admin', ['nip' => self::NIP, 'nama' => 'Kepala Subbag TU', '--no-interaction' => true], $buffer);

        $output = $buffer->fetch();
        $link = trim(collect(preg_split('/\R/', trim($output)))->last());
        $user = User::where('nip', self::NIP)->sole();
        $this->assertSame(UserRole::Administrator, $user->role);
        $this->assertSame(AccountStatus::Pending, $user->status);
        $this->assertStringStartsWith(config('app.url').'/aktivasi/'.$user->id.'?', $link);
        $this->assertStringNotContainsStringIgnoringCase('kata sandi sementara', $output);
        $this->post($link, ['sandi' => 'kata-sandi-kuat-2026', 'sandi_ulang' => 'kata-sandi-kuat-2026'])->assertRedirect('/login?aktif=1');
        $this->assertSame(AccountStatus::Active, $user->fresh()->status);
    }

    // Outside tests the prompt asks again; under PHPUnit Laravel ends the command at the first invalid answer.
    public function test_rejects_a_typed_password_that_breaks_the_policy(): void
    {
        $this->artisan('pintu:create-admin', ['nip' => self::NIP, 'nama' => 'Kepala Subbag TU'])
            ->expectsQuestion('Kata sandi', 'pendek')
            ->expectsOutputToContain('Kata sandi minimal 12 karakter.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_rejects_a_repeated_password_that_does_not_match(): void
    {
        $this->artisan('pintu:create-admin', ['nip' => self::NIP, 'nama' => 'Kepala Subbag TU'])
            ->expectsQuestion('Kata sandi', 'kata-sandi-kuat-2026')
            ->expectsQuestion('Ulangi kata sandi', 'kata-sandi-lain-2026')
            ->expectsOutputToContain('Kata sandi tidak sama.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    #[TestWith(['12345'])]
    #[TestWith(['19900101202012100A'])]
    public function test_refuses_a_nip_that_is_not_18_digits(string $nip): void
    {
        $this->artisan('pintu:create-admin', ['nip' => $nip, 'nama' => 'Kepala Subbag TU', '--no-interaction' => true])
            ->expectsOutputToContain('NIP harus 18 digit angka.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_refuses_a_nip_that_is_already_registered(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $this->artisan('pintu:create-admin', ['nip' => self::NIP, 'nama' => 'Kepala Subbag TU', '--no-interaction' => true])
            ->expectsOutputToContain('NIP ini sudah terdaftar.')
            ->doesntExpectOutputToContain('pintu:activation-link')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_points_to_a_new_activation_link_when_the_registered_nip_is_still_pending(): void
    {
        User::factory()->administrator()->withStatus(AccountStatus::Pending)->create(['nip' => self::NIP]);

        $this->artisan('pintu:create-admin', ['nip' => self::NIP, 'nama' => 'Kepala Subbag TU', '--no-interaction' => true])
            ->expectsOutputToContain('NIP ini sudah terdaftar.')
            ->expectsOutputToContain('php artisan pintu:activation-link '.self::NIP)
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }
}
