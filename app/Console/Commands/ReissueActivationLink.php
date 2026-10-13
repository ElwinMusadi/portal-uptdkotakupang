<?php

namespace App\Console\Commands;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Replaces the activation link of an account that is still pending, for
 * example once the first link expired. Earlier links stop working. Active,
 * suspended and deactivated accounts are refused, so this is never a way to
 * reset someone's password.
 */
#[Signature('pintu:activation-link {nip : NIP 18 digit akun yang menunggu aktivasi}')]
#[Description('Membuat tautan aktivasi baru untuk akun PINTU yang belum aktif')]
class ReissueActivationLink extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IssueActivationLink $issueActivationLink): int
    {
        $user = User::query()
            ->where('nip', (string) $this->argument('nip'))
            ->where('status', AccountStatus::Pending)
            ->first();

        if (! $user instanceof User) {
            $this->components->error('Tidak ada akun dengan NIP ini yang menunggu aktivasi.');

            return self::FAILURE;
        }

        // A new unusable password changes the activation key, which voids earlier links.
        $user->forceFill(['password' => Str::password(64)])->save();

        $this->components->info("Tautan aktivasi baru untuk {$user->name} (NIP {$user->nip}); tautan sebelumnya tidak berlaku lagi.");
        $this->components->warn('Kirim tautan ini hanya kepada pemilik akun. Berlaku '.IssueActivationLink::MINUTES.' menit dan hanya sekali pakai:');
        // Raw output: console formatting must never alter the printed link.
        $this->output->writeln($issueActivationLink->handle($user), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
