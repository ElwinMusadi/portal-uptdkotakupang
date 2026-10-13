<?php

namespace Tests\Feature;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        $previous = app(IssueActivationLink::class)->handle($user);
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
    public function test_refuses_an_account_that_is_not_pending_and_keeps_its_password(AccountStatus $status): void
    {
        $user = User::factory()->withStatus($status)->create(['nip' => self::NIP]);

        $this->artisan('pintu:activation-link', ['nip' => self::NIP])
            ->expectsOutputToContain('Tidak ada akun dengan NIP ini yang menunggu aktivasi.')
            ->assertFailed();

        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_refuses_an_unknown_nip(): void
    {
        $this->artisan('pintu:activation-link', ['nip' => self::NIP])
            ->expectsOutputToContain('Tidak ada akun dengan NIP ini yang menunggu aktivasi.')
            ->assertFailed();
    }
}
