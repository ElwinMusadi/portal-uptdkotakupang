<?php

namespace Tests\Feature;

use App\Enums\UploadTarget;
use App\Models\Article;
use App\Models\Upload;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RewriteUploadUrlsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string OLD = 'http://portal-uptdkotakupang.test';

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('https://pintu.uptdpenda-kupang.web.id');
        URL::forceScheme('https');
        Storage::fake('public', ['url' => 'https://bucket-pintu.laravel.cloud']);
        Storage::fake();
    }

    public function test_gives_saved_content_each_uploads_current_address_also_inside_json(): void
    {
        $photo = $this->stored(Upload::factory()->create());
        $banner = $this->stored(Upload::factory()->create(['tujuan' => UploadTarget::Beranda, 'path' => 'beranda/2026/10/spanduk.png']));
        $selfie = Upload::factory()->attendance()->create();
        $article = Article::factory()->create(['sampul' => self::OLD.'/storage/'.$photo->path]);
        $this->setting('beranda', ['gambar' => self::OLD.'/storage/'.$banner->path, 'tautan' => self::OLD.'/layanan']);
        $this->setting('uji', ['foto' => self::OLD.'/api/berkas/'.$selfie->id]);

        $this->artisan('pintu:rewrite-upload-urls', ['lama' => self::OLD.'/'])
            ->expectsOutputToContain('Alamat diganti di 3 baris.')
            ->doesntExpectOutputToContain('belum ada di bucket')
            ->assertSuccessful();

        $this->assertSame('https://bucket-pintu.laravel.cloud/'.$photo->path, $article->fresh()->sampul);
        $this->assertTrue(Upload::fromUrl($article->fresh()->sampul, UploadTarget::Berita)?->is($photo));
        $this->assertSame(['gambar' => $banner->url(), 'tautan' => self::OLD.'/layanan'], $this->settingValue('beranda'));
        $this->assertSame(['foto' => 'https://pintu.uptdpenda-kupang.web.id/api/berkas/'.$selfie->id], $this->settingValue('uji'));
    }

    public function test_only_counts_with_the_dry_run_option_and_warns_of_files_not_yet_in_the_bucket(): void
    {
        $photo = Upload::factory()->create();
        $old = self::OLD.'/storage/'.$photo->path;
        $article = Article::factory()->create(['sampul' => $old]);

        $this->artisan('pintu:rewrite-upload-urls', ['lama' => self::OLD, '--dry-run' => true])
            ->expectsOutputToContain('Alamat lama ditemukan di 1 baris; tidak ada yang diubah.')
            ->expectsOutputToContain('1 berkas publik belum ada di bucket; salin dengan php artisan pintu:copy-uploads dari salinan lokal.')
            ->assertSuccessful();

        $this->assertSame($old, $article->fresh()->sampul);
    }

    public function test_refuses_an_old_address_that_is_not_an_application_root(): void
    {
        foreach (['portal-uptdkotakupang.test', 'ftp://portal-uptdkotakupang.test', self::OLD.'/storage'] as $old) {
            $this->artisan('pintu:rewrite-upload-urls', ['lama' => $old])
                ->expectsOutputToContain('Alamat lama harus berupa alamat aplikasi tanpa jalur, mis. http://portal-uptdkotakupang.test.')
                ->assertFailed();
        }
    }

    private function stored(Upload $upload): Upload
    {
        $upload->disk()->put($upload->path, 'isi');

        return $upload;
    }

    /**
     * @param  array<string, string>  $value
     */
    private function setting(string $name, array $value): void
    {
        DB::table('settings')->insert(['name' => $name, 'value' => json_encode($value)]);
    }

    /**
     * @return array<string, string>
     */
    private function settingValue(string $name): array
    {
        return json_decode((string) DB::table('settings')->where('name', $name)->value('value'), true);
    }
}
