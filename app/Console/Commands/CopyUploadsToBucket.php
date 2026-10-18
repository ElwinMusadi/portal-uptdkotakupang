<?php

namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Copies the files of the local uploads into a Laravel Cloud bucket when the
 * local data moves to production (D-065): the public files into the public
 * bucket, or with --privat the private ones into the private bucket, under
 * the same paths, so that the moved upload rows find them. The bucket's keys
 * are typed in at the prompt and never stored. Files already in the bucket
 * with the same size are skipped, so the copy can be run again.
 */
#[Signature('pintu:copy-uploads {--bucket= : Nama bucket tujuan (dari View credentials di Laravel Cloud)} {--endpoint= : Endpoint bucket (dari View credentials)} {--privat : Salin berkas privat ke bucket privat; tanpa ini, berkas publik ke bucket publik}')]
#[Description('Menyalin berkas unggahan lokal ke bucket Laravel Cloud')]
class CopyUploadsToBucket extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->components->error('Penyalinan ini dijalankan dari salinan lokal (APP_ENV=local).');

            return self::FAILURE;
        }

        $bucket = trim((string) $this->option('bucket'));
        $endpoint = trim((string) $this->option('endpoint'));
        if ($bucket === '' || $endpoint === '') {
            $this->components->error('Sebutkan --bucket dan --endpoint dari View credentials bucket di Laravel Cloud.');

            return self::FAILURE;
        }

        $key = trim(text('Access key ID bucket'));
        $secret = trim(password('Secret access key bucket'));
        if ($key === '' || $secret === '') {
            $this->components->error('Access key ID dan secret access key bucket wajib diisi.');

            return self::FAILURE;
        }

        $bucketDisk = Storage::build([
            'driver' => 's3',
            'key' => $key,
            'secret' => $secret,
            'region' => 'auto',
            'bucket' => $bucket,
            'endpoint' => $endpoint,
            'throw' => true,
        ]);

        $copied = $present = $missing = 0;
        try {
            foreach (Upload::query()->where('publik', ! $this->option('privat'))->orderBy('id')->get() as $upload) {
                $local = $upload->disk();
                if (! $local->exists($upload->path)) {
                    $this->components->warn("Berkas {$upload->path} tidak ada di salinan lokal.");
                    $missing++;

                    continue;
                }
                if ($bucketDisk->exists($upload->path) && $bucketDisk->size($upload->path) === $local->size($upload->path)) {
                    $present++;

                    continue;
                }

                $bucketDisk->writeStream($upload->path, $local->readStream($upload->path));
                $copied++;
            }
        } catch (Throwable $exception) {
            $this->components->error("Gagal menyalin ke bucket {$bucket}: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->components->info("{$copied} berkas disalin, {$present} sudah ada di bucket, {$missing} tidak ada di lokal.");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
