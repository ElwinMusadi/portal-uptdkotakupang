<?php

namespace Tests\Feature;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountActivationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string NIP = '199001012020121001';

    private const string PASSWORD = 'sandi-baru-aman-2026';

    public function test_shows_the_password_form_for_a_valid_link(): void
    {
        $user = $this->pendingAdministrator();

        $response = $this->get($this->activationLink($user));

        $response->assertOk()->assertSee('Buat kata sandi Anda.')->assertSee(self::NIP);
    }

    public function test_activates_the_account_with_the_chosen_password(): void
    {
        $user = $this->pendingAdministrator();

        $response = $this->post($this->activationLink($user), ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD]);

        $response->assertRedirect('/login?aktif=1');
        $this->assertSame(AccountStatus::Active, $user->fresh()->status);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    public function test_refuses_a_link_that_was_already_used(): void
    {
        $user = $this->pendingAdministrator();
        $link = $this->activationLink($user);
        $this->post($link, ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD]);

        $response = $this->get($link);

        $response->assertStatus(410)->assertSee('Tautan tidak berlaku.');
    }

    public function test_refuses_an_expired_link(): void
    {
        $link = $this->activationLink($this->pendingAdministrator());
        $this->travel(61)->minutes();

        $response = $this->get($link);

        $response->assertStatus(410)->assertSee('Tautan tidak berlaku.');
    }

    public function test_refuses_a_tampered_link_without_changing_the_account(): void
    {
        $user = $this->pendingAdministrator();
        $link = str_replace('kunci=', 'kunci=0', $this->activationLink($user));

        $response = $this->post($link, ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD]);

        $response->assertStatus(410);
        $this->assertSame(AccountStatus::Pending, $user->fresh()->status);
    }

    public function test_refuses_a_link_for_an_account_that_is_no_longer_pending(): void
    {
        $user = $this->pendingAdministrator();
        $link = $this->activationLink($user);
        $user->forceFill(['status' => AccountStatus::Suspended])->save();

        $response = $this->post($link, ['sandi' => self::PASSWORD, 'sandi_ulang' => self::PASSWORD]);

        $response->assertStatus(410);
        $this->assertSame(AccountStatus::Suspended, $user->fresh()->status);
    }

    public function test_requires_a_password_that_meets_the_policy(): void
    {
        $user = $this->pendingAdministrator();

        $response = $this->post($this->activationLink($user), ['sandi' => 'pendek1', 'sandi_ulang' => 'pendek1']);

        $response->assertSessionHasErrors(['sandi' => 'Kata sandi minimal 12 karakter.']);
        $this->assertSame(AccountStatus::Pending, $user->fresh()->status);
    }

    public function test_requires_the_repeated_password_to_match(): void
    {
        $user = $this->pendingAdministrator();

        $response = $this->post($this->activationLink($user), ['sandi' => self::PASSWORD, 'sandi_ulang' => 'sandi-lain-aman-2026']);

        $response->assertSessionHasErrors(['sandi' => 'Ulangan kata sandi tidak sama.']);
        $this->assertSame(AccountStatus::Pending, $user->fresh()->status);
    }

    public function test_never_keeps_a_rejected_password_in_the_session(): void
    {
        $user = $this->pendingAdministrator();

        $response = $this->post($this->activationLink($user), ['sandi' => self::PASSWORD, 'sandi_ulang' => 'sandi-lain-aman-2026']);

        $response->assertSessionHasErrors('sandi');
        $this->assertFalse(session()->hasOldInput('sandi'));
        $this->assertFalse(session()->hasOldInput('sandi_ulang'));
    }

    private function pendingAdministrator(): User
    {
        return User::factory()->administrator()->withStatus(AccountStatus::Pending)->create(['nip' => self::NIP]);
    }

    private function activationLink(User $user): string
    {
        return app(IssueActivationLink::class)->handle($user)['url'];
    }
}
