<?php

namespace App\Console\Commands;

use App\Actions\IssueActivationLink;
use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Issues a new activation link from the command line, for example once the
 * first link expired; earlier links stop working. Administrators normally do
 * this from the dashboard. For an account that is not pending the --reset
 * option is required, because the owner's password stops working: it is the
 * recovery path when no other administrator can reset an administrator.
 */
#[Signature('pintu:activation-link {nip : NIP 18 digit} {--reset : Atur ulang akun yang tidak menunggu aktivasi; kata sandinya tidak berlaku lagi}')]
#[Description('Membuat tautan aktivasi baru untuk akun PINTU')]
class ReissueActivationLink extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IssueActivationLink $issueActivationLink): int
    {
        $user = User::query()->where('nip', (string) $this->argument('nip'))->first();

        if (! $user instanceof User) {
            $this->components->error('Tidak ada akun dengan NIP ini.');

            return self::FAILURE;
        }

        $pending = $user->status === AccountStatus::Pending;

        if (! $pending && ! $this->option('reset')) {
            $this->components->error('Akun ini tidak menunggu aktivasi. Tambahkan --reset bila kata sandinya memang perlu diatur ulang.');

            return self::FAILURE;
        }

        $link = $issueActivationLink->handle($user);

        $this->components->info($pending
            ? "Tautan aktivasi baru untuk {$user->name} (NIP {$user->nip}); tautan sebelumnya tidak berlaku lagi."
            : "Akun {$user->name} (NIP {$user->nip}) diatur ulang; kata sandi lamanya tidak berlaku lagi.");
        $this->components->warn('Kirim tautan ini hanya kepada pemilik akun. Berlaku '.IssueActivationLink::MINUTES.' menit dan hanya sekali pakai:');
        // Raw output: console formatting must never alter the printed link.
        $this->output->writeln($link['url'], OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
