<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ZipArchive;

class UploadControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        Storage::fake('public');
    }

    public function test_returns_401_to_guests(): void
    {
        $upload = Upload::factory()->attendance()->create();
        Storage::put($upload->path, 'x');

        $this->post('/api/unggah', ['modul' => 'berita', 'berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])->assertUnauthorized();
        $this->getJson("/api/berkas/{$upload->id}")->assertUnauthorized();
    }

    public function test_forbids_employees_without_the_target_module_but_lets_everyone_upload_attendance(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'berita', 'berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Berita & Pengumuman belum ditugaskan kepada Anda.']);
        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'beranda', 'berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Beranda belum ditugaskan kepada Anda.']);
        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'absensi', 'berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->actingAs(User::factory()->withModules(DashboardModule::Beranda)->create())
            ->post('/api/unggah', ['modul' => 'beranda', 'berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertDatabaseCount('uploads', 2);
        $this->assertTrue(Upload::query()->where('tujuan', 'beranda')->sole()->publik);
    }

    public function test_stores_a_photo_in_the_public_bucket_resized_and_without_metadata(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Berita)->create();
        $photo = $this->jpeg(3200, 1600, $this->exifSegment(1, 'PINTU-GPS-RAHASIA'), '../Foto Loket.JPG');

        $response = $this->actingAs($employee)->post('/api/unggah', ['modul' => 'berita', 'berkas' => $photo], ['Accept' => 'application/json']);

        $upload = Upload::query()->sole();
        $stored = Storage::disk('public')->get($upload->path);
        $response->assertCreated()->assertExactJson([
            'nama' => 'Foto Loket.jpg',
            'ukuran' => strlen($stored),
            'tipe' => 'image/jpeg',
            'url' => Storage::disk('public')->url($upload->path),
        ]);
        $this->assertMatchesRegularExpression('#^berita/\d{4}/\d{2}/'.$upload->id.'\.jpg$#', $upload->path);
        $this->assertTrue($upload->publik);
        $this->assertSame($employee->id, $upload->user_id);
        $this->assertNull($upload->dipakai_pada);
        $this->assertSame([1600, 800], array_slice(getimagesizefromstring($stored), 0, 2));
        $this->assertStringNotContainsString('PINTU-GPS-RAHASIA', $stored);
        $this->assertStringNotContainsString('Exif', $stored);
        Storage::assertMissing($upload->path);
    }

    /**
     * A 300 × 100 photo whose left third is red, with the orientation its
     * camera recorded: where the red part ends up once the photo is upright.
     */
    #[TestWith([1, [300, 100], [50, 50]])]
    #[TestWith([3, [300, 100], [250, 50]])]
    #[TestWith([6, [100, 300], [50, 50]])]
    #[TestWith([8, [100, 300], [50, 250]])]
    public function test_turns_a_photo_the_way_its_camera_recorded_it(int $orientation, array $size, array $red): void
    {
        $employee = User::factory()->withModules(DashboardModule::Dokumentasi)->create();

        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'dokumentasi', 'berkas' => $this->jpeg(300, 100, $this->exifSegment($orientation), redThird: true)], ['Accept' => 'application/json'])
            ->assertCreated();

        $stored = Storage::disk('public')->get(Upload::query()->sole()->path);
        $image = imagecreatefromstring($stored);
        $this->assertSame($size, [imagesx($image), imagesy($image)]);
        $this->assertGreaterThan(200, imagecolorsforindex($image, imagecolorat($image, ...$red))['red']);
    }

    public function test_keeps_png_transparency_and_webp(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Unit)->create();
        $image = imagecreatetruecolor(40, 20);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        ob_start();
        imagewebp($image);
        $webp = (string) ob_get_clean();

        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'unit', 'berkas' => UploadedFile::fake()->createWithContent('logo.png', $png)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('tipe', 'image/png')
            ->assertJsonPath('nama', 'logo.png');
        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'unit', 'berkas' => UploadedFile::fake()->createWithContent('foto.webp', $webp)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('tipe', 'image/webp');

        $stored = imagecreatefromstring(Storage::disk('public')->get(Upload::query()->where('tipe', 'image/png')->sole()->path));
        $this->assertSame(127, imagecolorsforindex($stored, imagecolorat($stored, 5, 5))['alpha']);
    }

    public function test_stores_documents_as_they_are(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Dokumen, DashboardModule::VisiMisi)->create();
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";

        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'dokumen', 'berkas' => UploadedFile::fake()->createWithContent('Perda 2026.pdf', $pdf)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('nama', 'Perda 2026.pdf')
            ->assertJsonPath('tipe', 'application/pdf')
            ->assertJsonPath('ukuran', strlen($pdf));
        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'dokumen', 'berkas' => $this->office('docx')], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('tipe', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'dokumen', 'berkas' => $this->office('xlsx')], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'visiMisi', 'berkas' => UploadedFile::fake()->createWithContent('maklumat.pdf', $pdf)], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertSame($pdf, Storage::disk('public')->get(Upload::query()->where('nama', 'Perda 2026.pdf')->sole()->path));
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function unacceptedFiles(): array
    {
        return [
            'text named as a photo' => ['berita', 'foto.jpg', 'bukan gambar', 'Format berkas harus JPG, PNG, atau WebP.'],
            'html named as a pdf' => ['dokumen', 'perda.pdf', '<html><script>alert(1)</script></html>', 'Format berkas harus PDF, Word (DOCX), Excel (XLSX), JPG, PNG, atau WebP.'],
            'svg' => ['berita', 'logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'Format berkas harus JPG, PNG, atau WebP.'],
            'pdf for a news photo' => ['berita', 'perda.pdf', "%PDF-1.4\n%%EOF\n", 'Format berkas harus JPG, PNG, atau WebP.'],
        ];
    }

    #[DataProvider('unacceptedFiles')]
    public function test_rejects_files_by_their_content(string $target, string $name, string $contents, string $message): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post('/api/unggah', ['modul' => $target, 'berkas' => UploadedFile::fake()->createWithContent($name, $contents)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => $message]);
        $this->assertDatabaseCount('uploads', 0);
    }

    public function test_rejects_a_docx_for_the_pledge_and_unknown_targets(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'visiMisi', 'berkas' => $this->office('docx')], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Format berkas harus PDF.']);
        $archive = UploadedFile::fake()->createWithContent('arsip.zip', (string) file_get_contents($this->zip(['a.txt' => 'a'])));
        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'dokumen', 'berkas' => $archive], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Format berkas harus PDF, Word (DOCX), Excel (XLSX), JPG, PNG, atau WebP.']);
        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'faq', 'berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['modul' => 'Tujuan unggahan tidak dikenal.']);
        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'berita'], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Pilih berkas yang akan diunggah.']);
        $this->actingAs($administrator)->post('/api/unggah', ['berkas' => $this->jpeg(10, 10)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['modul' => 'Tujuan unggahan wajib diisi.']);
    }

    public function test_limits_images_to_5_mb_documents_to_10_mb_and_photos_to_25_megapixels(): void
    {
        $administrator = User::factory()->administrator()->create();
        // A small photo padded past 5 MB with comment segments
        $bigImage = $this->jpeg(10, 10, str_repeat("\xFF\xFE".pack('n', 65535).str_repeat('a', 65533), 81), 'besar.jpg');

        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'dokumen', 'berkas' => $bigImage], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Ukuran gambar maksimal 5 MB.']);
        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'dokumen', 'berkas' => UploadedFile::fake()->createWithContent('besar.pdf', "%PDF-1.4\n".str_repeat('0', 10 * 1024 * 1024))], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Ukuran dokumen maksimal 10 MB.']);
        $this->actingAs($administrator)->post('/api/unggah', ['modul' => 'berita', 'berkas' => $this->jpeg(6000, 5000)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Gambar terlalu besar (maksimal 25 megapiksel).']);
        $this->assertDatabaseCount('uploads', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_keeps_attendance_photos_private_and_streams_them_only_to_the_uploader_and_administrators(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)->post('/api/unggah', ['modul' => 'absensi', 'berkas' => $this->jpeg(20, 20)], ['Accept' => 'application/json']);

        $upload = Upload::query()->sole();
        $response->assertCreated()->assertJsonPath('url', route('berkas.show', $upload));
        $this->assertFalse($upload->publik);
        Storage::assertExists($upload->path);
        Storage::disk('public')->assertMissing($upload->path);

        $this->actingAs($employee)->get("/api/berkas/{$upload->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->administrator()->create())->get("/api/berkas/{$upload->id}")->assertOk();
        $this->actingAs(User::factory()->withModules(...DashboardModule::cases())->create())->getJson("/api/berkas/{$upload->id}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Berkas ini tidak dapat Anda buka.']);
    }

    public function test_does_not_stream_public_files(): void
    {
        $upload = Upload::factory()->create();
        Storage::disk('public')->put($upload->path, 'x');

        $this->actingAs($upload->user)->getJson("/api/berkas/{$upload->id}")->assertNotFound();
    }

    public function test_limits_uploads_to_30_a_minute_per_employee(): void
    {
        $employee = User::factory()->create();

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($employee)->post('/api/unggah', ['modul' => 'absensi', 'berkas' => $this->jpeg(5, 5)], ['Accept' => 'application/json'])->assertCreated();
        }

        $this->actingAs($employee)->post('/api/unggah', ['modul' => 'absensi', 'berkas' => $this->jpeg(5, 5)], ['Accept' => 'application/json'])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak unggahan. Coba lagi sebentar lagi.');
    }

    public function test_finds_an_upload_only_by_its_own_url_and_target(): void
    {
        $upload = Upload::factory()->create();
        $private = Upload::factory()->attendance()->create();

        $this->assertTrue($upload->is(Upload::fromUrl($upload->url(), UploadTarget::Berita)));
        $this->assertTrue($private->is(Upload::fromUrl($private->url(), UploadTarget::Absensi)));
        $this->assertNull(Upload::fromUrl($upload->url(), UploadTarget::Dokumentasi));
        $this->assertNull(Upload::fromUrl('https://example.com/'.basename($upload->path), UploadTarget::Berita));
        $this->assertNull(Upload::fromUrl($upload->url().'x', UploadTarget::Berita));
        $this->assertNull(Upload::fromUrl('javascript:alert(1)', UploadTarget::Berita));
    }

    public function test_prunes_unused_uploads_after_a_day_with_their_files(): void
    {
        $old = Upload::factory()->create(['created_at' => now()->subHours(25)]);
        $used = Upload::factory()->create(['created_at' => now()->subDays(3)]);
        $recent = Upload::factory()->create(['created_at' => now()->subHours(23)]);
        $used->markUsed();
        foreach ([$old, $used, $recent] as $upload) {
            Storage::disk('public')->put($upload->path, 'x');
        }

        $this->artisan('model:prune', ['--model' => [Upload::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        Storage::disk('public')->assertMissing($old->path);
        $this->assertModelExists($used);
        $this->assertModelExists($recent);
        Storage::disk('public')->assertExists($used->path);
        $this->assertNotNull($used->fresh()->dipakai_pada);
    }

    private function jpeg(int $width, int $height, string $segment = '', string $name = 'foto.jpg', bool $redThird = false): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, (int) imagecolorallocate($image, 30, 90, 200));
        if ($redThird) {
            imagefilledrectangle($image, 0, 0, intdiv($width, 3) - 1, $height - 1, (int) imagecolorallocate($image, 250, 20, 20));
        }
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();

        return UploadedFile::fake()->createWithContent($name, substr($jpeg, 0, 2).$segment.substr($jpeg, 2));
    }

    /**
     * An EXIF segment holding only an orientation, and any extra bytes.
     */
    private function exifSegment(int $orientation, string $extra = ''): string
    {
        $tiff = "II*\x00".pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, $orientation, 0).pack('V', 0);
        $payload = "Exif\x00\x00".$tiff.$extra;

        return "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    }

    private function office(string $extension): UploadedFile
    {
        [$part, $type] = $extension === 'docx'
            ? ['word/document.xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml']
            : ['xl/workbook.xml', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml'];
        $path = $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/'.$part.'" ContentType="'.$type.'"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>',
            $part => '<?xml version="1.0"?><x/>',
        ]);

        return UploadedFile::fake()->createWithContent("lampiran.{$extension}", (string) file_get_contents($path));
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function zip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $path;
    }
}
