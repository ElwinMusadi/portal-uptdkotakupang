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
            ->assertJsonPath('hero.tombolUrl', 'layanan.html')
            ->assertJsonPath('nilai.*.judul', ['Persyaratan jelas.', 'Jadwal terbaru.', 'Tanpa perantara.'])
            ->assertJsonPath('mitra.*.singkatan', ['BP', 'PL', 'JR', 'BN', 'QR', 'SG'])
            ->assertJsonPath('mitra.*.logo', [null, null, null, null, null, null])
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
        (new Setting)->forceFill(['name' => Setting::HOMEPAGE, 'value' => $homepage])->save();
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
        $this->assertSame($shown, Setting::homepage());
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
