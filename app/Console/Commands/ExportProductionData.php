<?php

namespace App\Console\Commands;

use App\Actions\DumpDatabase;
use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\LocalAccountSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Exports the local database to a .sql file for Laravel MySQL (D-065): all
 * of it, as the owner chose, except that the local administrator, whose
 * password is in the repository, becomes the production administrator
 * awaiting activation, and sessions, cache and reset tokens are emptied.
 * The file holds personal data, so it must be written outside the project.
 */
#[Signature('pintu:export-production {berkas : Berkas .sql tujuan, di luar folder proyek} {--nip= : NIP 18 digit Administrator produksi} {--nama= : Nama lengkap Administrator produksi} {--mysqldump=mysqldump : Program mysqldump}')]
#[Description('Mengekspor database lokal untuk dipindahkan ke Laravel Cloud')]
class ExportProductionData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DumpDatabase $dumpDatabase): int
    {
        if (! app()->environment('local')) {
            $this->components->error('Ekspor ini hanya dijalankan dari salinan lokal (APP_ENV=local).');

            return self::FAILURE;
        }

        $validator = Validator::make(['nip' => $this->option('nip'), 'nama' => $this->option('nama')], [
            'nip' => ['required', 'regex:/^\d{18}\z/'],
            'nama' => ['required', 'string', 'max:255'],
        ], [
            'nip.required' => 'Sebutkan NIP Administrator produksi dengan --nip.',
            'nip.regex' => 'NIP harus 18 digit angka.',
            'nama.required' => 'Sebutkan nama Administrator produksi dengan --nama.',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $path = $this->target();
        if ($path === null) {
            return self::FAILURE;
        }

        $local = User::query()->where('nip', LocalAccountSeeder::NIP)->first();
        if (! $local instanceof User) {
            $this->components->error('Akun admin lokal dari LocalAccountSeeder tidak ditemukan.');

            return self::FAILURE;
        }
        if (User::query()->where('nip', $this->option('nip'))->whereKeyNot($local->getKey())->exists()) {
            $this->components->error('NIP ini sudah dipakai akun lain.');

            return self::FAILURE;
        }

        try {
            $dumpDatabase->handle((string) config('database.default'), $path, (string) $this->option('mysqldump'));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
        file_put_contents($path, $this->closingStatements($local), FILE_APPEND);

        $this->components->info('Data diekspor ke '.$path.' ('.number_format(filesize($path) / 1048576, 1, ',', '.').' MB).');
        $this->components->warn('Berkas ini berisi data pribadi wajib pajak: simpan di tempat aman dan hapus setelah diimpor.');
        $this->components->info("Setelah diimpor, jalankan di Laravel Cloud: php artisan pintu:activation-link {$this->option('nip')}");

        return self::SUCCESS;
    }

    /**
     * The full path of the new file, or null after explaining why it cannot
     * be written there.
     */
    private function target(): ?string
    {
        $path = (string) $this->argument('berkas');
        $folder = realpath(dirname($path));
        $problem = match (true) {
            ! str_ends_with(strtolower($path), '.sql') => 'Nama berkas harus berakhiran .sql.',
            $folder === false || ! is_dir($folder) => 'Folder tujuan tidak ada.',
            str_starts_with(strtolower($folder.DIRECTORY_SEPARATOR), strtolower(base_path().DIRECTORY_SEPARATOR)) => 'Simpan berkas di luar folder proyek agar data pribadi tidak ikut ter-commit.',
            file_exists($folder.DIRECTORY_SEPARATOR.basename($path)) => 'Berkas tujuan sudah ada; pilih nama lain.',
            default => null,
        };
        if ($problem !== null) {
            $this->components->error($problem);

            return null;
        }

        return $folder.DIRECTORY_SEPARATOR.basename($path);
    }

    /**
     * Statements run after the dump: the local administrator becomes the
     * production one with an unusable password, and transient rows go.
     */
    private function closingStatements(User $local): string
    {
        $pdo = DB::connection()->getPdo();
        $quote = fn (string $value): string => (string) $pdo->quote($value);

        return implode("\n", [
            '',
            '-- PINTU (D-065): akun admin lokal menjadi Administrator produksi yang menunggu aktivasi.',
            'UPDATE `users` SET `nip` = '.$quote((string) $this->option('nip'))
                .', `name` = '.$quote((string) $this->option('nama'))
                .', `password` = '.$quote(Hash::make(Str::password(64)))
                .', `status` = '.$quote(AccountStatus::Pending->value)
                .', `remember_token` = '.$quote(Str::random(60))
                .', `updated_at` = '.$quote(now()->toDateTimeString())
                .' WHERE `id` = '.$quote((string) $local->getKey()).';',
            '-- Sesi, cache, dan token atur ulang tidak dibawa.',
            'DELETE FROM `sessions`;',
            'DELETE FROM `cache`;',
            'DELETE FROM `cache_locks`;',
            'DELETE FROM `password_reset_tokens`;',
            '',
        ]);
    }
}
