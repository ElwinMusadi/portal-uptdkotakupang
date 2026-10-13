<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Sign-in attempts allowed for one NIP from one address per minute.
     */
    public const int MAX_ATTEMPTS_PER_ADDRESS = 5;

    /**
     * Sign-in attempts allowed for one NIP from all addresses per 15 minutes.
     * Client addresses can be forged behind some proxies, so this limit
     * protects an account even when the per-address limit is bypassed.
     */
    public const int MAX_ATTEMPTS_PER_NIP = 20;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'regex:/^\d{18}\z/'],
            'sandi' => ['required', 'string', 'max:255', 'not_regex:/\x00/'],
            'ingat' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nip.string' => 'NIP harus 18 digit angka.',
            'nip.regex' => 'NIP harus 18 digit angka.',
            'sandi.required' => 'Kata sandi wajib diisi.',
            'sandi.string' => 'Kata sandi tidak valid.',
            'sandi.max' => 'Kata sandi terlalu panjang.',
            'sandi.not_regex' => 'Kata sandi tidak valid.',
            'ingat.boolean' => 'Pilihan "Ingat perangkat ini" tidak valid.',
        ];
    }

    /**
     * Sign the employee in with NIP and password.
     *
     * Every attempt is counted before the password is checked, so parallel
     * requests cannot slip past the limits. The response never reveals
     * whether a NIP exists: unknown NIPs cost the same hashing time as wrong
     * passwords, and inactive accounts are refused only after their password
     * has been verified.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->countAttempt();

        $guard = Auth::guard('web');
        $provider = $guard->getProvider();
        $credentials = [
            'nip' => $this->string('nip')->value(),
            'password' => $this->string('sandi')->value(),
        ];
        $user = $provider->retrieveByCredentials(['nip' => $credentials['nip']]);

        if (! $user instanceof User) {
            Hash::check($credentials['password'], $this->dummyHash());
        }

        if (! $user instanceof User || ! $provider->validateCredentials($user, $credentials)) {
            Log::notice('auth.failed', ['nip' => $credentials['nip'], 'ip' => $this->ip()]);

            throw ValidationException::withMessages([
                'nip' => 'NIP atau kata sandi belum sesuai.',
            ]);
        }

        if (! $user->canSignIn()) {
            throw ValidationException::withMessages([
                'nip' => 'Akun Anda belum aktif atau sedang dinonaktifkan. Hubungi admin di Sub Bagian Tata Usaha.',
            ]);
        }

        $provider->rehashPasswordIfRequired($user, $credentials);
        $guard->login($user, $this->boolean('ingat'));

        RateLimiter::clear($this->addressThrottleKey());
        RateLimiter::clear($this->nipThrottleKey());

        return $user;
    }

    /**
     * Count this attempt against both limits and refuse it when either is
     * exceeded. The Lockout event fires once per lockout, not per request.
     *
     * @throws ValidationException
     */
    private function countAttempt(): void
    {
        $limits = [
            [$this->addressThrottleKey(), self::MAX_ATTEMPTS_PER_ADDRESS, 60],
            [$this->nipThrottleKey(), self::MAX_ATTEMPTS_PER_NIP, 15 * 60],
        ];

        foreach ($limits as [$key, $maxAttempts, $decaySeconds]) {
            $attempts = RateLimiter::hit($key, $decaySeconds);

            if ($attempts > $maxAttempts) {
                if ($attempts === $maxAttempts + 1) {
                    event(new Lockout($this));
                }

                throw ValidationException::withMessages([
                    'nip' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
                ])->status(429);
            }
        }
    }

    /**
     * A hash with the configured cost to verify against when the NIP is
     * unknown, cached so that every request pays for exactly one check.
     */
    private function dummyHash(): string
    {
        return Cache::rememberForever(
            'auth:dummy-hash:'.config('hashing.bcrypt.rounds'),
            fn (): string => Hash::make(Str::random(40)),
        );
    }

    private function addressThrottleKey(): string
    {
        return 'login:'.$this->string('nip')->value().'|'.$this->ip();
    }

    private function nipThrottleKey(): string
    {
        return 'login:'.$this->string('nip')->value();
    }
}
