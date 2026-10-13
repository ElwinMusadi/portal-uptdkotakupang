<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Support\EnforcedCsrfToken;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string NIP = '199001012020121001';

    private const string WRONG_CREDENTIALS = 'NIP atau kata sandi belum sesuai.';

    protected function setUp(): void
    {
        parent::setUp();

        // Like the login page, every request comes from the application's own origin.
        $this->withHeader('Referer', config('app.url').'/login');
    }

    public function test_signs_in_an_active_employee_and_returns_the_session(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $user = User::factory()->create(['nip' => self::NIP, 'name' => 'Maria Bria']);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertOk()->assertExactJson([
            'nip' => self::NIP,
            'nama' => 'Maria Bria',
            'peran' => 'pegawai',
            'masuk' => '2026-10-13T08:00:00+00:00',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_a_wrong_password_with_422_without_signing_in(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['nip' => self::WRONG_CREDENTIALS]);
        $this->assertGuest();
    }

    public function test_answers_an_unknown_nip_exactly_like_a_wrong_password(): void
    {
        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertUnprocessable()->assertExactJson([
            'message' => self::WRONG_CREDENTIALS,
            'errors' => ['nip' => [self::WRONG_CREDENTIALS]],
        ]);
        $this->assertGuest();
    }

    /**
     * @return array<string, array{AccountStatus}>
     */
    public static function inactiveStatuses(): array
    {
        return [
            'pending' => [AccountStatus::Pending],
            'suspended' => [AccountStatus::Suspended],
            'deactivated' => [AccountStatus::Deactivated],
        ];
    }

    #[DataProvider('inactiveStatuses')]
    public function test_refuses_an_account_that_is_not_active_once_its_password_is_verified(AccountStatus $status): void
    {
        User::factory()->withStatus($status)->create(['nip' => self::NIP]);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nip' => 'Akun Anda belum aktif atau sedang dinonaktifkan. Hubungi admin di Sub Bagian Tata Usaha.',
        ]);
        $this->assertGuest();
    }

    public function test_does_not_reveal_an_inactive_account_to_a_wrong_password(): void
    {
        User::factory()->withStatus(AccountStatus::Suspended)->create(['nip' => self::NIP]);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['nip' => self::WRONG_CREDENTIALS]);
    }

    public function test_answers_a_null_byte_password_identically_for_known_and_unknown_nips(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $known = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => "a\u{0000}b"]);
        $unknown = $this->postJson('/api/masuk', ['nip' => '199001012020121009', 'sandi' => "a\u{0000}b"]);

        $known->assertUnprocessable()->assertJsonValidationErrors(['sandi' => 'Kata sandi tidak valid.']);
        $unknown->assertUnprocessable();
        $this->assertSame($known->json(), $unknown->json());
    }

    public function test_signs_in_with_a_password_that_starts_and_ends_with_spaces(): void
    {
        User::factory()->create(['nip' => self::NIP, 'password' => '  sandi berspasi 2026  ']);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => '  sandi berspasi 2026  ']);

        $response->assertOk();
    }

    public function test_requires_an_18_digit_nip_and_a_password(): void
    {
        $response = $this->postJson('/api/masuk', ['nip' => '12345', 'sandi' => '']);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nip' => 'NIP harus 18 digit angka.',
            'sandi' => 'Kata sandi wajib diisi.',
        ]);
    }

    public function test_locks_out_a_nip_after_five_failures_from_one_address_even_for_the_right_password(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah'])->assertUnprocessable();
        }

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertTooManyRequests();
        $this->assertStringStartsWith('Terlalu banyak percobaan masuk. Coba lagi dalam ', $response->json('message'));
        $this->assertGuest();
    }

    public function test_locks_out_a_nip_after_twenty_failures_spread_over_many_addresses(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        foreach (range(1, 20) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
                ->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah'])
                ->assertUnprocessable();
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_a_successful_sign_in_resets_the_failure_count(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        foreach (range(1, 4) as $attempt) {
            $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah']);
        }
        $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password'])->assertOk();
        $this->postJson('/api/keluar')->assertNoContent();
        foreach (range(1, 4) as $attempt) {
            $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah']);
        }

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertOk();
    }

    public function test_logs_a_failed_attempt_without_the_password(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        Log::spy();

        $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'salah']);

        Log::shouldHaveReceived('notice')->once()->with('auth.failed', ['nip' => self::NIP, 'ip' => '127.0.0.1']);
    }

    public function test_remembers_the_device_for_seven_days_when_asked(): void
    {
        $this->freezeTime();
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password', 'ingat' => true]);

        $response->assertOk();
        $this->assertSame(now()->addDays(7)->getTimestamp(), $this->rememberCookie($response->headers->getCookies())?->getExpiresTime());
    }

    public function test_does_not_remember_the_device_unless_asked(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertOk();
        $this->assertNull($this->rememberCookie($response->headers->getCookies()));
    }

    public function test_refuses_a_sign_in_that_does_not_come_from_the_portal_with_400(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->flushHeaders()->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertBadRequest()->assertExactJson(['message' => 'Login harus dilakukan dari halaman Login Pegawai.']);
        $this->assertGuest();
    }

    public function test_refuses_a_sign_in_without_a_csrf_token_with_419(): void
    {
        config(['sanctum.middleware.validate_csrf_token' => EnforcedCsrfToken::class]);
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertStatus(419);
        $this->assertGuest('web');
    }

    public function test_treats_a_cross_site_sign_in_as_not_coming_from_the_portal(): void
    {
        User::factory()->create(['nip' => self::NIP]);

        $response = $this->withHeader('Referer', 'https://situs-lain.example/login')
            ->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertBadRequest()->assertCookieMissing(config('session.cookie'));
        $this->assertGuest('web');
    }

    public function test_rotates_the_session_id_when_signing_in(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        $this->get('/sanctum/csrf-cookie');
        $planted = session()->getId();
        $this->startNextRequestFromCookiesOnly();

        $response = $this->withCookie(config('session.cookie'), $planted)
            ->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password']);

        $response->assertOk();
        $signedIn = session()->getId();
        $this->assertNotSame($planted, $signedIn);
        $this->startNextRequestFromCookiesOnly();
        $this->withCookie(config('session.cookie'), $signedIn)->getJson('/api/sesi')->assertOk();
        $this->startNextRequestFromCookiesOnly();
        $this->withCookie(config('session.cookie'), $planted)->getJson('/api/sesi')->assertUnauthorized();
    }

    public function test_destroys_the_session_when_signing_out(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password'])->assertOk();
        $signedIn = session()->getId();
        $this->startNextRequestFromCookiesOnly();

        $response = $this->withCookie(config('session.cookie'), $signedIn)->postJson('/api/keluar');

        $response->assertNoContent();
        $this->assertNotSame($signedIn, session()->getId());
        $this->assertFalse(session()->has('pintu.masuk'));
        $this->startNextRequestFromCookiesOnly();
        $this->withCookie(config('session.cookie'), $signedIn)->getJson('/api/sesi')->assertUnauthorized();
    }

    public function test_signs_the_employee_back_in_with_the_remember_cookie_after_the_session_ends(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        $login = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password', 'ingat' => true]);
        $remember = $this->rememberCookie($login->headers->getCookies());
        $this->startNextRequestFromCookiesOnly();

        $response = $this->withUnencryptedCookie($remember->getName(), $remember->getValue())->getJson('/api/sesi');

        $response->assertOk()->assertJsonPath('nip', self::NIP);
    }

    public function test_rejects_the_remember_cookie_of_an_account_suspended_since(): void
    {
        $user = User::factory()->create(['nip' => self::NIP]);
        $login = $this->postJson('/api/masuk', ['nip' => self::NIP, 'sandi' => 'password', 'ingat' => true]);
        $remember = $this->rememberCookie($login->headers->getCookies());
        $token = $user->fresh()->remember_token;
        $user->forceFill(['status' => AccountStatus::Suspended])->save();
        $this->startNextRequestFromCookiesOnly();

        $response = $this->withUnencryptedCookie($remember->getName(), $remember->getValue())->getJson('/api/sesi');

        $response->assertUnauthorized();
        $this->assertTrue($this->rememberCookie($response->headers->getCookies())?->isCleared());
        $this->assertNotSame($token, $user->fresh()->remember_token);
    }

    public function test_ignores_bearer_tokens(): void
    {
        $response = $this->withToken('1|bukan-token-sungguhan')->getJson('/api/sesi');

        $response->assertUnauthorized();
    }

    public function test_returns_the_signed_in_employee(): void
    {
        $user = User::factory()->administrator()->create(['nip' => self::NIP, 'name' => 'Admin PINTU']);

        $response = $this->actingAs($user)->getJson('/api/sesi');

        $response->assertOk()->assertExactJson([
            'nip' => self::NIP,
            'nama' => 'Admin PINTU',
            'peran' => 'administrator',
            'masuk' => null,
        ]);
    }

    public function test_returns_401_to_guests_asking_for_the_session(): void
    {
        $response = $this->getJson('/api/sesi');

        $response->assertUnauthorized()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_ends_the_session_of_an_account_that_is_no_longer_active(): void
    {
        $user = User::factory()->withStatus(AccountStatus::Suspended)->create();

        $response = $this->actingAs($user)->getJson('/api/sesi');

        $response->assertUnauthorized()->assertExactJson(['message' => 'Akun Anda tidak aktif.']);
        $this->assertGuest('web');
    }

    public function test_signs_out_and_ends_the_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/keluar');

        $response->assertNoContent();
        $this->assertGuest('web');
    }

    /**
     * Requests in a test share one application, which keeps the session data,
     * the signed-in user and queued cookies of the previous request. Forget
     * them, and send cookies with JSON requests too, so the next request is
     * recognised only by the cookies it carries, as from a browser.
     */
    private function startNextRequestFromCookiesOnly(): void
    {
        session()->flush();
        $this->app['auth']->forgetGuards();
        $this->app['cookie']->flushQueuedCookies();
        $this->withCredentials();
    }

    /**
     * @param  array<int, Cookie>  $cookies
     */
    private function rememberCookie(array $cookies): ?Cookie
    {
        return collect($cookies)->first(fn (Cookie $cookie): bool => str_starts_with($cookie->getName(), 'remember_web_'));
    }
}
