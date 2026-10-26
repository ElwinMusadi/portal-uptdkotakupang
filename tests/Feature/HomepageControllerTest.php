<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Models\Setting;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HomepageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE)->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Profil)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Beranda belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_text_of_the_homepage_markup_until_first_saved(): void
    {
        $response = $this->actingAs($this->berandaEmployee())->getJson('/api/pengaturan/beranda');

        $response->assertOk()
            ->assertJsonPath('hero.judul', 'Semua informasi Samsat.')
            ->assertJsonPath('hero.kataBergilir', ['Pajak', 'STNK', 'Mutasi', 'BBN', 'SAMSAT'])
            ->assertJsonPath('hero.tombolUrl', 'layanan.html')
            ->assertJsonPath('nilai.*.judul', ['Persyaratan jelas.', 'Jadwal terbaru.', 'Tanpa perantara.'])
            ->assertJsonPath('mitra.*.singkatan', ['BP', 'PL', 'JR', 'BN', 'QR', 'SG'])
            ->assertJsonPath('mitra.*.logo', [null, null, null, null, null, null])
            ->assertJsonPath('video', ['url' => '', 'label' => 'Tonton alur pelayanan', 'durasi' => '', 'judul' => ''])
            ->assertJsonPath('kanal.*.kelompok', ['Kantor', 'Keliling', 'Digital', 'Informasi', 'Pengaduan'])
            ->assertJsonPath('kanal.0.item.1', ['teks' => "Loket 5\u{00A0}tahunan", 'tautan' => ''])
            ->assertJsonPath('biaya.jaminan', ['Bukti bayar resmi', 'Bisa non-tunai', 'Tercatat di sistem'])
            ->assertJsonPath('penutup.lanjutan', 'Asal jangan lupa dibawa.');
    }

    public function test_saves_the_document_and_logs_it(): void
    {
        $employee = $this->berandaEmployee();
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = 'Judul baru.';
        $homepage['hero']['kataBergilir'] = [];
        $homepage['hero']['catatan'] = null;
        $homepage['nilai'][0]['deskripsi'] = null;
        $homepage['mitra'][0]['singkatan'] = null;
        $homepage['kanal'][0]['item'] = [['teks' => 'Loket pajak tahunan', 'tautan' => null], ['teks' => '', 'tautan' => '']];
        $homepage['kanal'][2]['item'][1]['tautan'] = 'https://www.bankntt.co.id';
        $homepage['kanal'][4]['item'][1]['tautan'] = 'kontak.html#masukan';
        $homepage['biaya']['jaminan'] = ['Bukti bayar resmi', ''];
        $homepage['penutup']['lanjutan'] = null;

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertOk()
            ->assertJsonPath('hero.judul', 'Judul baru.')
            ->assertJsonPath('hero.kataBergilir', [])
            ->assertJsonPath('hero.catatan', '')
            ->assertJsonPath('nilai.0.deskripsi', '')
            ->assertJsonPath('mitra.0.singkatan', '')
            ->assertJsonPath('kanal.0.item', [['teks' => 'Loket pajak tahunan', 'tautan' => '']])
            ->assertJsonPath('kanal.2.item.1', ['teks' => 'Bank NTT', 'tautan' => 'https://www.bankntt.co.id'])
            ->assertJsonPath('kanal.4.item.1.tautan', 'kontak.html#masukan')
            ->assertJsonPath('biaya.jaminan', ['Bukti bayar resmi'])
            ->assertJsonPath('penutup.lanjutan', '');
        $this->assertSame($response->json(), Setting::homepage());
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'beranda', 'action' => 'memperbarui', 'target' => 'Beranda']);
    }

    public function test_logs_nothing_when_the_same_document_is_saved_again(): void
    {
        $employee = $this->berandaEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE)->assertOk();

        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', Setting::INITIAL_HOMEPAGE)->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_the_opening_lists_and_sections(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = '';
        $homepage['hero']['tombolUrl'] = 'javascript:alert(1)';
        $homepage['mitra'][0]['singkatan'] = 'BPAD';
        $homepage['kanal'][1]['item'] = array_fill(0, 9, ['teks' => 'Kanal', 'tautan' => '']);
        $homepage['kanal'][2]['item'][0]['tautan'] = 'javascript:alert(1)';
        $homepage['kanal'][2]['item'][1] = ['teks' => '', 'tautan' => 'https://www.bankntt.co.id'];
        $homepage['kanal'][3]['item'][0]['tautan'] = 'https://example.com/'.str_repeat('a', 281);
        $homepage['kanal'][4]['item'][0]['teks'] = str_repeat('a', 61);
        $homepage['penutup']['judul'] = '';
        $homepage['biaya']['deskripsi'] = "Biaya\u{0007}";

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'hero.judul' => 'Judul pembuka wajib diisi.',
            'hero.tombolUrl' => 'Tautan tombol: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            'mitra.0.singkatan' => 'Singkatan mitra maksimal 3 karakter.',
            'kanal.1.item' => 'Maksimal 8 kanal per kelompok.',
            'kanal.2.item.0.tautan' => 'Tautan kanal: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            'kanal.2.item.1.teks' => 'Nama kanal wajib diisi bila tautannya diisi.',
            'kanal.3.item.0.tautan' => 'Tautan kanal maksimal 300 karakter.',
            'kanal.4.item.0.teks' => 'Nama kanal maksimal 60 karakter.',
            'penutup.judul' => 'Judul penutup wajib diisi.',
            'biaya.deskripsi' => 'Deskripsi biaya berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_limits_the_lists_and_rejects_duplicate_items(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['nilai'] = array_map(fn (int $index): array => ['id' => "n{$index}", 'judul' => "Nilai {$index}.", 'deskripsi' => ''], range(1, 7));
        $homepage['mitra'][1]['id'] = $homepage['mitra'][0]['id'];

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'nilai' => 'Maksimal 6 nilai utama.',
            'mitra.1.id' => 'Mitra tercantum dua kali.',
        ]);
    }

    public function test_saves_rotating_words_when_the_title_holds_one_in_any_case(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = 'Semua informasi SAMSAT.';
        $homepage['hero']['kataBergilir'] = [' Pajak kendaraan bermotor ', 'STNK', 'samsat'];

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertOk()->assertJsonPath('hero.kataBergilir', ['Pajak kendaraan bermotor', 'STNK', 'samsat']);
        $this->assertSame(['Pajak kendaraan bermotor', 'STNK', 'samsat'], Setting::homepage()['hero']['kataBergilir']);
    }

    /**
     * @param  array<string, string>  $errors
     */
    #[TestWith(['Semua informasi Samsat.', ['Pajak', 'STNK', 'Mutasi', 'BBN', 'Balik nama', 'Pajak progresif', 'Plat', 'Notis', 'Samsat'], ['hero.kataBergilir' => 'Maksimal 8 kata bergilir.']])]
    #[TestWith(['Semua informasi Samsat.', ['Samsat'], ['hero.kataBergilir' => 'Isi minimal dua kata bergilir, atau kosongkan daftarnya.']])]
    #[TestWith(['Semua informasi Samsat.', ['Pajak', 'STNK'], ['hero.kataBergilir' => 'Judul pembuka harus memuat salah satu kata bergilir; di kata itulah putarannya berhenti.']])]
    #[TestWith(['Semua informasi SAMSATKU.', ['Pajak', 'Samsat'], ['hero.kataBergilir' => 'Judul pembuka harus memuat salah satu kata bergilir; di kata itulah putarannya berhenti.']])]
    #[TestWith(['Semua informasi KuSamsat.', ['Pajak', 'Samsat'], ['hero.kataBergilir' => 'Judul pembuka harus memuat salah satu kata bergilir; di kata itulah putarannya berhenti.']])]
    #[TestWith(['Semua informasi PKB.', ['P.B', 'Samsat'], ['hero.kataBergilir' => 'Judul pembuka harus memuat salah satu kata bergilir; di kata itulah putarannya berhenti.']])]
    #[TestWith(['Semua informasi Samsat.', ['Pajak', 'pajak', 'Samsat'], ['hero.kataBergilir.0' => 'Kata bergilir tercantum dua kali.', 'hero.kataBergilir.1' => 'Kata bergilir tercantum dua kali.']])]
    #[TestWith(['Semua informasi Samsat.', ['Pajak', ''], ['hero.kataBergilir.1' => 'Kata bergilir wajib diisi.']])]
    #[TestWith(['Semua informasi Samsat.', ['Pajak', 'Balik nama kendaraan baru', 'Samsat'], ['hero.kataBergilir.1' => 'Kata bergilir maksimal 24 karakter.']])]
    #[TestWith(['Semua informasi Samsat.', ["Pajak\u{0007}", 'Samsat'], ['hero.kataBergilir.0' => 'Kata bergilir berisi karakter kontrol yang tidak diizinkan.']])]
    #[TestWith(['Semua informasi Samsat.', 'Pajak', ['hero.kataBergilir' => 'Daftar kata bergilir tidak valid.']])]
    #[TestWith(['', ['Pajak', 'Samsat'], ['hero.judul' => 'Judul pembuka wajib diisi.']])]
    public function test_validates_the_rotating_words(string $title, mixed $words, array $errors): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = $title;
        $homepage['hero']['kataBergilir'] = $words;

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors($errors);
        $this->assertDatabaseCount('settings', 0);
    }

    /**
     * @param  list<string>  $words
     */
    #[TestWith(['Semua informasi SAMSAT.', ['Pajak', 'STNK', 'Mutasi', 'BBN', 'SAMSAT']])]
    #[TestWith(['Satu pintu untuk semua layanan.', []])]
    public function test_gives_documents_saved_before_rotating_words_the_initial_ones_when_the_title_holds_one(string $title, array $words): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = $title;
        unset($homepage['hero']['kataBergilir']);
        (new Setting)->forceFill(['name' => Setting::HOMEPAGE, 'value' => $homepage])->save();

        $this->actingAs($this->berandaEmployee())->getJson('/api/pengaturan/beranda')
            ->assertOk()
            ->assertJsonPath('hero.kataBergilir', $words);
    }

    public function test_keeps_partner_logos_and_deletes_replaced_or_removed_ones(): void
    {
        Storage::fake('public');
        $employee = $this->berandaEmployee();
        [$first, $second] = [$this->logo(), $this->logo()];
        $homepage = Setting::INITIAL_HOMEPAGE;

        $homepage['mitra'][0]['logo'] = $first->url();
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage)
            ->assertOk()
            ->assertJsonPath('mitra.0.logo', $first->url())
            ->assertJsonPath('mitra.1.logo', null);
        $this->assertNotNull($first->fresh()->dipakai_pada);

        $homepage['mitra'][0]['logo'] = $second->url();
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage)->assertOk();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->path);
        $this->assertNotNull($second->fresh()->dipakai_pada);

        $homepage['mitra'][0]['logo'] = null;
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage)
            ->assertOk()
            ->assertJsonPath('mitra.0.logo', null);
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_rejects_logos_that_are_not_beranda_uploads(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['mitra'][0]['logo'] = Upload::factory()->create(['tujuan' => UploadTarget::Unit])->url();
        $homepage['mitra'][1]['logo'] = 'https://example.com/logo.png';

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'mitra.0.logo' => 'Logo mitra harus diunggah lewat dashboard.',
            'mitra.1.logo' => 'Logo mitra harus diunggah lewat dashboard.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_reads_documents_saved_before_logos_and_channel_links_in_the_current_shape(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['mitra'] = [['id' => 'mitra-1', 'singkatan' => 'BP', 'nama' => 'BPAD Provinsi NTT']];
        $homepage['kanal'] = [['id' => 'kanal-1', 'kelompok' => 'Pengaduan', 'item' => ['SP4N-LAPOR!', 'Kotak saran']]];
        unset($homepage['video'], $homepage['beritaTerbaru']);
        (new Setting)->forceFill(['name' => Setting::HOMEPAGE, 'value' => $homepage])->save();
        $this->assertSame(['url' => '', 'label' => 'Tonton alur pelayanan', 'durasi' => '', 'judul' => ''], Setting::homepage()['video']);
        // Berita terbaru (D-087) shows as it starts in a document saved before it
        $this->assertSame(Setting::INITIAL_HOMEPAGE['beritaTerbaru'], Setting::homepage()['beritaTerbaru']);
        $employee = $this->berandaEmployee();

        $shown = $this->actingAs($employee)->getJson('/api/pengaturan/beranda')
            ->assertOk()
            ->assertJsonPath('mitra', [['id' => 'mitra-1', 'singkatan' => 'BP', 'nama' => 'BPAD Provinsi NTT', 'logo' => null]])
            ->assertJsonPath('kanal.0.item', [['teks' => 'SP4N-LAPOR!', 'tautan' => ''], ['teks' => 'Kotak saran', 'tautan' => '']])
            ->json();

        // The dashboard saves the document as it was shown
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $shown)
            ->assertOk()
            ->assertJsonPath('mitra.0.logo', null)
            ->assertJsonPath('kanal.0.item.1', ['teks' => 'Kotak saran', 'tautan' => '']);
        // Same content; the saved document lists the video right after the opening
        $this->assertEquals($shown, Setting::homepage());
    }

    public function test_accepts_channels_sent_as_plain_text_by_an_older_dashboard(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['kanal'] = [
            ['id' => 'kanal-1', 'kelompok' => 'Pengaduan', 'item' => ['Kotak saran', '', 'Ombudsman NTT']],
            ['id' => 'kanal-2', 'kelompok' => 'Digital', 'item' => 'SIGNAL'],
        ];

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors(['kanal.1.item' => 'Daftar kanal tidak valid.']);

        $homepage['kanal'][1]['item'] = ['SIGNAL'];
        $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage)
            ->assertOk()
            ->assertJsonPath('kanal.0.item', [['teks' => 'Kotak saran', 'tautan' => ''], ['teks' => 'Ombudsman NTT', 'tautan' => '']])
            ->assertJsonPath('kanal.1.item', [['teks' => 'SIGNAL', 'tautan' => '']]);
    }

    #[TestWith(['https://youtu.be/25sRCp-IFxI?si=PKD-EyH-_dDyIiFU'])]
    #[TestWith(['https://www.youtube.com/watch?v=25sRCp-IFxI&t=10s'])]
    #[TestWith(['https://m.youtube.com/watch?v=25sRCp-IFxI'])]
    #[TestWith(['https://youtube.com/shorts/25sRCp-IFxI?feature=share'])]
    #[TestWith(['https://www.youtube.com/live/25sRCp-IFxI'])]
    #[TestWith(['https://www.youtube-nocookie.com/embed/25sRCp-IFxI'])]
    public function test_saves_the_video_as_a_plain_watch_link(string $url): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['video'] = ['url' => $url, 'label' => 'Tonton alur pelayanan', 'durasi' => '2:14', 'judul' => null];

        $response = $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage);

        $response->assertOk()->assertJsonPath('video', [
            'url' => 'https://www.youtube.com/watch?v=25sRCp-IFxI',
            'label' => 'Tonton alur pelayanan',
            'durasi' => '2:14',
            'judul' => '',
        ]);
    }

    public function test_validates_the_video(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['video'] = ['url' => 'https://evil.example/watch?v=25sRCp-IFxI', 'label' => str_repeat('a', 41), 'durasi' => '2 menit', 'judul' => "Judul\u{0007}"];

        $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'video.url' => 'Tautan video: gunakan tautan video YouTube, mis. https://youtu.be/… atau https://www.youtube.com/watch?v=….',
                'video.label' => 'Label tombol video maksimal 40 karakter.',
                'video.durasi' => 'Tulis durasi video seperti 2:14 atau 1:02:30.',
                'video.judul' => 'Judul video berisi karakter kontrol yang tidak diizinkan.',
            ]);

        $homepage['video'] = ['url' => 'https://youtu.be/25sRCp-IFxI', 'label' => '', 'durasi' => '1:02:30', 'judul' => ''];
        $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['video.label' => 'Label tombol video wajib diisi bila tautannya diisi.']);
        $this->assertDatabaseCount('settings', 0);
    }

    #[TestWith(['ftp://youtu.be/25sRCp-IFxI'])]
    #[TestWith(['https://youtu.be/25sRCp'])]
    #[TestWith(['https://www.youtube.com/watch?v=25sRCp-IFxI1'])]
    #[TestWith(['https://youtube.com.evil.example/watch?v=25sRCp-IFxI'])]
    #[TestWith(['https://www.youtube.com/@uptdkupang'])]
    #[TestWith(['https://youtu.be/25sRCp-IFxI/lain'])]
    #[TestWith(['https://www.youtube.com/embed/25sRCp-IFxI/lain'])]
    #[TestWith(['https://www.youtube.com/watch?v[]=25sRCp-IFxI'])]
    public function test_rejects_links_that_are_not_youtube_videos(string $url): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['video']['url'] = $url;

        $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['video.url']);
    }

    public function test_keeps_the_saved_video_and_rotating_words_when_an_older_dashboard_sends_none(): void
    {
        $employee = $this->berandaEmployee();
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['kataBergilir'] = ['STNK', 'Samsat'];
        $homepage['video'] = ['url' => 'https://youtu.be/25sRCp-IFxI', 'label' => 'Tonton alur pelayanan', 'durasi' => '2:14', 'judul' => 'Alur pelayanan PINTU'];
        $homepage['beritaTerbaru'] = [...$homepage['beritaTerbaru'], 'tampil' => false, 'jumlah' => 6];
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage)->assertOk();

        unset($homepage['video'], $homepage['hero']['kataBergilir'], $homepage['beritaTerbaru']);
        $homepage['hero']['judul'] = 'Judul baru.';
        $this->actingAs($employee)->putJson('/api/pengaturan/beranda', $homepage)
            ->assertOk()
            ->assertJsonPath('hero.judul', 'Judul baru.')
            ->assertJsonPath('hero.kataBergilir', ['STNK', 'Samsat'])
            ->assertJsonPath('video.url', 'https://www.youtube.com/watch?v=25sRCp-IFxI')
            ->assertJsonPath('video.judul', 'Alur pelayanan PINTU')
            ->assertJsonPath('beritaTerbaru.tampil', false)
            ->assertJsonPath('beritaTerbaru.jumlah', 6);
    }

    public function test_saves_the_newest_articles_section(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['beritaTerbaru'] = ['tampil' => '0', 'jumlah' => '5', 'judul' => 'Berita terkini.', 'lanjutan' => null, 'deskripsi' => null];

        $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage)
            ->assertOk()
            ->assertJsonPath('beritaTerbaru', ['tampil' => false, 'jumlah' => 5, 'judul' => 'Berita terkini.', 'lanjutan' => '', 'deskripsi' => '']);
        $this->assertSame(['tampil' => false, 'jumlah' => 5, 'judul' => 'Berita terkini.', 'lanjutan' => '', 'deskripsi' => ''], Setting::homepage()['beritaTerbaru']);
    }

    /**
     * @param  array<string, mixed>  $section
     * @param  array<string, string>  $errors
     */
    #[TestWith([['tampil' => 'ya', 'jumlah' => 2, 'judul' => ''], [
        'beritaTerbaru.tampil' => 'Pilihan tampilkan berita terbaru tidak valid.',
        'beritaTerbaru.jumlah' => 'Jumlah berita terbaru 3 sampai 6 tulisan.',
        'beritaTerbaru.judul' => 'Judul berita terbaru wajib diisi.',
    ]])]
    #[TestWith([['tampil' => true, 'jumlah' => 7, 'judul' => 'Judul.', 'lanjutan' => 'Lanjutan yang terlalu panjang untuk judul dua nada di beranda, lebih dari delapan puluh', 'deskripsi' => 'Deskripsi.'], [
        'beritaTerbaru.jumlah' => 'Jumlah berita terbaru 3 sampai 6 tulisan.',
        'beritaTerbaru.lanjutan' => 'Lanjutan judul berita terbaru maksimal 80 karakter.',
    ]])]
    #[TestWith([['jumlah' => 3.5, 'judul' => 'Judul.'], [
        'beritaTerbaru.tampil' => 'Pilihan tampilkan berita terbaru wajib diisi.',
        'beritaTerbaru.jumlah' => 'Jumlah berita terbaru tidak valid.',
    ]])]
    public function test_validates_the_newest_articles_section(array $section, array $errors): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['beritaTerbaru'] = $section;

        $this->actingAs($this->berandaEmployee())->putJson('/api/pengaturan/beranda', $homepage)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors($errors);
        $this->assertDatabaseCount('settings', 0);
    }

    private function logo(): Upload
    {
        $upload = Upload::factory()
            ->state(fn (array $attributes): array => ['tujuan' => UploadTarget::Beranda, 'path' => "beranda/2026/10/{$attributes['id']}.png", 'tipe' => 'image/png'])
            ->create();
        Storage::disk('public')->put($upload->path, 'png');

        return $upload;
    }

    private function berandaEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Beranda)->create();
    }
}
