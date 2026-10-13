<?php

namespace App\Console\Commands;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\password;

/**
 * Creates an administrator out of band; there is no default or seeded admin
 * in production. Interactive runs ask for the password. Non-interactive runs,
 * such as Laravel Cloud's Commands tab, never print a password: they create a
 * pending account and print a signed, single-use activation link instead.
 */
#[Signature('pintu:create-admin {nip : NIP 18 digit} {nama : Nama lengkap} {--email= : Surel dinas (opsional)}')]
#[Description('Membuat akun Administrator PINTU')]
class CreateAdministrator extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IssueActivationLink $issueActivationLink): int
    {
        $attributes = [
            'nip' => $this->argument('nip'),
            'name' => $this->argument('nama'),
            'email' => $this->option('email'),
        ];

        $validator = Validator::make($attributes, [
            'nip' => ['required', 'regex:/^\d{18}\z/', 'unique:users,nip'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ], [
            'nip.regex' => 'NIP harus 18 digit angka.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
            'name.required' => 'Nama wajib diisi.',
            'email.email' => 'Surel tidak valid.',
            'email.unique' => 'Surel ini sudah terdaftar.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            if (User::query()->where('nip', $attributes['nip'])->where('status', AccountStatus::Pending)->exists()) {
                $this->components->info("Akun ini masih menunggu aktivasi. Jalankan php artisan pintu:activation-link {$attributes['nip']} untuk membuat tautan baru.");
            }

            return self::FAILURE;
        }

        $interactive = $this->input->isInteractive();

        $user = (new User)->forceFill([
            ...$attributes,
            'password' => $interactive ? $this->askForPassword() : Str::password(64),
            'role' => UserRole::Administrator,
            'status' => $interactive ? AccountStatus::Active : AccountStatus::Pending,
        ]);
        $user->save();

        if ($interactive) {
            $this->components->info("Administrator {$user->name} (NIP {$user->nip}) dibuat dan aktif.");

            return self::SUCCESS;
        }

        $this->components->info("Administrator {$user->name} (NIP {$user->nip}) dibuat, menunggu aktivasi.");
        $this->components->warn('Kirim tautan ini hanya kepada pemilik akun. Berlaku '.IssueActivationLink::MINUTES.' menit dan hanya sekali pakai:');
        // Raw output: console formatting must never alter the printed link.
        $this->output->writeln($issueActivationLink->handle($user), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }

    private function askForPassword(): string
    {
        $password = password(
            label: 'Kata sandi',
            required: true,
            validate: fn (string $value): ?string => Validator::make(['sandi' => $value], ['sandi' => [new StrongPassword]])
                ->errors()
                ->first('sandi') ?: null,
            hint: 'Minimal 12 karakter berisi huruf dan angka.',
        );

        password(
            label: 'Ulangi kata sandi',
            required: true,
            validate: fn (string $value): ?string => $value === $password ? null : 'Kata sandi tidak sama.',
        );

        return $password;
    }
}
