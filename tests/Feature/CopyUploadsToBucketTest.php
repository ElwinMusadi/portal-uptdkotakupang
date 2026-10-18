<?php

namespace Tests\Feature;

use App\Enums\UploadTarget;
use App\Models\Upload;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CopyUploadsToBucketTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const array OPTIONS = ['--bucket' => 'pintu-publik', '--endpoint' => 'https://akun.r2.cloudflarestorage.com'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'local';
        Storage::fake('public');
        Storage::fake();
    }

    public function test_copies_the_public_files_under_their_paths_and_skips_them_the_next_time(): void
    {
        $photo = $this->stored(Upload::factory()->create(), 'foto berita');
        $logo = $this->stored(Upload::factory()->create(['tujuan' => UploadTarget::Aplikasi, 'path' => 'aplikasi/2026/10/logo.png']), 'logo');
        $selfie = $this->stored(Upload::factory()->attendance()->create(), 'swafoto');
        $bucket = $this->bucket(2);

        $this->copy()->expectsOutputToContain('2 berkas disalin, 0 sudah ada di bucket, 0 tidak ada di lokal.')->assertSuccessful();

        $this->assertSame(['foto berita', 'logo'], [$bucket->get($photo->path), $bucket->get($logo->path)]);
        $bucket->assertMissing($selfie->path);

        $this->copy()->expectsOutputToContain('0 berkas disalin, 2 sudah ada di bucket, 0 tidak ada di lokal.')->assertSuccessful();
    }

    public function test_copies_the_private_files_into_the_private_bucket_with_the_privat_option(): void
    {
        $photo = $this->stored(Upload::factory()->create(), 'foto berita');
        $selfie = $this->stored(Upload::factory()->attendance()->create(), 'swafoto');
        $bucket = $this->bucket(1, 'pintu-privat');

        $this->copy(['--bucket' => 'pintu-privat', '--privat' => true])
            ->expectsOutputToContain('1 berkas disalin, 0 sudah ada di bucket, 0 tidak ada di lokal.')
            ->assertSuccessful();

        $this->assertSame('swafoto', $bucket->get($selfie->path));
        $bucket->assertMissing($photo->path);
    }

    public function test_recopies_a_file_whose_size_differs_and_reports_files_missing_locally(): void
    {
        $photo = $this->stored(Upload::factory()->create(), 'foto berita baru');
        $lost = Upload::factory()->create();
        $bucket = $this->bucket(1);
        $bucket->put($photo->path, 'lama');

        $this->copy()
            ->expectsOutputToContain("Berkas {$lost->path} tidak ada di salinan lokal.")
            ->expectsOutputToContain('1 berkas disalin, 0 sudah ada di bucket, 1 tidak ada di lokal.')
            ->assertFailed();

        $this->assertSame('foto berita baru', $bucket->get($photo->path));
    }

    public function test_reports_a_bucket_that_refuses_the_copy(): void
    {
        $this->stored(Upload::factory()->create(), 'foto berita');
        $bucket = Mockery::mock(Filesystem::class);
        $bucket->shouldReceive('exists')->andThrow(new RuntimeException('Forbidden'));
        $manager = Mockery::mock(Storage::getFacadeRoot());
        $manager->shouldReceive('build')->once()->andReturn($bucket);
        Storage::swap($manager);

        $this->copy()->expectsOutputToContain('Gagal menyalin ke bucket pintu-publik: Forbidden')->assertFailed();
    }

    public function test_runs_only_on_the_local_copy_and_needs_the_bucket_and_its_keys(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('pintu:copy-uploads', self::OPTIONS)
            ->expectsOutputToContain('Penyalinan ini dijalankan dari salinan lokal (APP_ENV=local).')
            ->assertFailed();

        $this->app['env'] = 'local';
        $this->artisan('pintu:copy-uploads', ['--bucket' => 'pintu-publik'])
            ->expectsOutputToContain('Sebutkan --bucket dan --endpoint dari View credentials bucket di Laravel Cloud.')
            ->assertFailed();

        $this->artisan('pintu:copy-uploads', self::OPTIONS)
            ->expectsQuestion('Access key ID bucket', 'kunci-uji')
            ->expectsQuestion('Secret access key bucket', '')
            ->expectsOutputToContain('Access key ID dan secret access key bucket wajib diisi.')
            ->assertFailed();
    }

    private function stored(Upload $upload, string $contents): Upload
    {
        $upload->disk()->put($upload->path, $contents);

        return $upload;
    }

    /**
     * A fake bucket that Storage::build() returns for the expected settings.
     */
    private function bucket(int $builds, string $name = 'pintu-publik'): Filesystem
    {
        $bucket = Storage::fake('bucket-uji');
        $manager = Mockery::mock(Storage::getFacadeRoot());
        $manager->shouldReceive('build')->times($builds)->with([
            'driver' => 's3', 'key' => 'kunci-uji', 'secret' => 'rahasia-uji', 'region' => 'auto', 'bucket' => $name,
            'endpoint' => 'https://akun.r2.cloudflarestorage.com', 'throw' => true,
        ])->andReturn($bucket);
        Storage::swap($manager);

        return $bucket;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function copy(array $options = []): PendingCommand
    {
        return $this->artisan('pintu:copy-uploads', [...self::OPTIONS, ...$options])
            ->expectsQuestion('Access key ID bucket', 'kunci-uji')
            ->expectsQuestion('Secret access key bucket', 'rahasia-uji');
    }
}
