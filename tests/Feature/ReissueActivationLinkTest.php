<?php

namespace Tests\Feature;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class ReissueActivationLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string NIP = '199001012020121001';

    private const string PASSWORD = 'sandi-baru-aman-2026';

    public function test_replaces_the_link_of_a_pending_account_and_voids_the_previous_one(): void
    {
        $user = User::factory()->administrator()->withStatus(AccountStatus::Pending)->create(['nip' => self::NIP]);
        $previous = app(IssueActivationLink::class)->handle($user)['url'];
        $buffer = new BufferedOutput;

        $exitCode = Artisan::call('pintu:activation-link', ['nip' => self::NIP], $buffer);

        $this->assertSame(0, $exitCode);
        $link = trim(collect(preg_split('/\R/', trim($buffer->fetch())))->last());
        $this->get($previous)->assertStatus(410);
        $this->post($link, ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD])->assertRedirect('/login?aktif=1');
        $this->assertSame(AccountStatus::Active, $user->fresh()->status);
    }

    /**
     * @return array<string, array{AccountStatus}>
     */
    public static function settledStatuses(): array
    {
        return [
            'active' => [AccountStatus::Active],
            'suspended' => [AccountStatus::Suspended],
            'deactivated' => [AccountStatus::Deactivated],
        ];
    }

    #[DataProvider('settledStatuses')]
    public function test_refuses_an_account_that_is_not_pending_without_the_reset_option(AccountStatus $status): void
    {
        $user = User::factory()->withStatus($status)->create(['nip' => self::NIP]);

        $this->artisan('pintu:activation-link', ['nip' => self::NIP])
            ->expectsOutputToContain('Akun ini tidak menunggu aktivasi. Tambahkan --reset bila kata sandinya memang perlu diatur ulang.')
            ->assertFailed();

        $this->assertSame($user->password, $user->fresh()->password);
        $this->assertSame($status, $user->fresh()->status);
    }

    public function test_resets_an_active_account_with_the_reset_option(): void
    {
        $user = User::factory()->administrator()->create(['nip' => self::NIP]);
        $buffer = new BufferedOutput;

        $exitCode = Artisan::call('pintu:activation-link', ['nip' => self::NIP, '--reset' => true], $buffer);

        $this->assertSame(0, $exitCode);
        $output = $buffer->fetch();
        $this->assertStringContainsString('kata sandi lamanya tidak berlaku lagi', $output);
        $this->assertSame(AccountStatus::Pending, $user->fresh()->status);
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
        $link = trim(collect(preg_split('/\R/', trim($output)))->last());
        $this->post($link, ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD])->assertRedirect('/login?aktif=1');
        $this->assertSame(AccountStatus::Active, $user->fresh()->status);
    }

    public function test_refuses_an_unknown_nip(): void
    {
        $this->artisan('pintu:activation-link', ['nip' => self::NIP, '--reset' => true])
            ->expectsOutputToContain('Tidak ada akun dengan NIP ini.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
