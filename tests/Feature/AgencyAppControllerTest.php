<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Models\AgencyApp;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AgencyAppControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/aplikasi')->assertUnauthorized();
        $this->postJson('/api/koleksi/aplikasi', $this->app())->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/koleksi/aplikasi'])]
    #[TestWith(['POST', '/api/koleksi/aplikasi'])]
    #[TestWith(['PUT', '/api/koleksi/aplikasi/urutan'])]
    #[TestWith(['POST', '/api/koleksi/aplikasi/hapus'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(DashboardModule::Unit)->create();

        $response = $this->actingAs($employee)->json($method, $uri, $this->app());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Aplikasi & Situs belum ditugaskan kepada Anda.']);
    }

    public function test_lists_the_apps_the_owner_listed_in_order_with_their_logos(): void
    {
        $response = $this->actingAs($this->appEmployee())->getJson('/api/koleksi/aplikasi');

        $response->assertOk()
            ->assertJsonPath('*.nama', ['PINTU', 'PRO NTT', 'SIKAP', 'SIPAS', 'Tilang / Razia'])
            ->assertJsonPath('*.jenis', ['situs', 'android', 'situs', 'situs', 'situs'])
            ->assertJsonPath('*.pengelola', ['uptd', 'bpad', 'uptd', 'uptd', 'uptd'])
            ->assertJsonPath('*.tautan', [
                'index.html',
                'https://play.google.com/store/apps/details?id=com.iwana.betantt&hl=en',
                'https://sikap.uptdpenda-kupang.web.id',
                'https://sipas.uptdpenda-kupang.web.id',
                'https://tilang.uptdpenda-kupang.web.id',
            ])
            ->assertJsonPath('2.namaLengkap', 'Sistem Informasi Kepegawaian dan Arsip Pegawai UPTD Pendapatan Daerah Wilayah Kota Kupang')
            ->assertJsonPath('3.namaLengkap', 'Sistem Informasi Pengelolaan Administrasi SKPD');

        foreach ($response->json('*.logo') as $logo) {
            $this->assertStringStartsWith(asset('assets/img/aplikasi/'), $logo);
            $this->assertFileExists(public_path(substr($logo, strlen(asset('')))));
        }
    }

    public function test_adds_an_app_at_the_end_and_logs_it(): void
    {
        $employee = $this->appEmployee();
        $logo = $this->logo();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/aplikasi', ['logo' => $logo->url()] + $this->app());

        $response->assertCreated()
            ->assertJsonPath('nama', 'SIMPEL')
            ->assertJsonPath('namaLengkap', 'Sistem Informasi Pelayanan')
            ->assertJsonPath('jenis', 'situs')
            ->assertJsonPath('pengelola', 'uptd')
            ->assertJsonPath('tautan', 'https://simpel.uptdpenda-kupang.web.id')
            ->assertJsonPath('logo', $logo->url())
            ->assertJsonPath('urutan', 5);
        $this->assertNotNull($logo->fresh()->dipakai_pada);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'aplikasi', 'action' => 'menambahkan', 'target' => 'SIMPEL']);
    }

    public function test_stores_empty_or_missing_optional_fields_as_empty(): void
    {
        $empty = ['namaLengkap' => null, 'keterangan' => null, 'logo' => null] + $this->app();
        $missing = array_diff_key($empty, array_flip(['namaLengkap', 'keterangan', 'logo']));

        foreach ([$empty, $missing] as $app) {
            $this->actingAs($this->appEmployee())->postJson('/api/koleksi/aplikasi', $app)
                ->assertCreated()
                ->assertJsonPath('namaLengkap', '')
                ->assertJsonPath('keterangan', '')
                ->assertJsonPath('logo', null);
        }
    }

    public function test_validates_a_new_app(): void
    {
        $this->actingAs($this->appEmployee())->postJson('/api/koleksi/aplikasi', [])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'nama' => 'Nama aplikasi wajib diisi.',
                'jenis' => 'Pilih jenis aplikasi.',
                'pengelola' => 'Pilih pengelola aplikasi.',
                'tautan' => 'Tautan wajib diisi.',
            ]);

        $this->actingAs($this->appEmployee())->postJson('/api/koleksi/aplikasi', [
            'nama' => str_repeat('A', 61),
            'keterangan' => str_repeat('a', 301),
            'jenis' => 'ios',
            'pengelola' => 'polri',
            'tautan' => 'javascript:alert(1)',
        ] + $this->app())
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'nama' => 'Nama aplikasi maksimal 60 karakter.',
                'keterangan' => 'Keterangan maksimal 300 karakter.',
                'jenis' => 'Jenis aplikasi tidak valid.',
                'pengelola' => 'Pengelola aplikasi tidak valid.',
                'tautan' => 'Tautan: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            ]);
    }

    public function test_links_android_apps_to_google_play(): void
    {
        $this->actingAs($this->appEmployee())->postJson('/api/koleksi/aplikasi', ['jenis' => 'android'] + $this->app())
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['tautan' => 'Aplikasi Android ditautkan ke halamannya di Google Play (https://play.google.com/store/apps/…).']);
        $this->actingAs($this->appEmployee())->postJson('/api/koleksi/aplikasi', ['jenis' => 'android', 'tautan' => 'https://play.google.com/store/apps/details?id=id.go.ntt.simpel'] + $this->app())
            ->assertCreated();

        $website = AgencyApp::query()->where('nama', 'SIKAP')->firstOrFail();
        $android = AgencyApp::query()->where('nama', 'PRO NTT')->firstOrFail();
        $this->actingAs($this->appEmployee())->patchJson("/api/koleksi/aplikasi/{$website->id}", ['jenis' => 'android'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tautan']);
        $this->actingAs($this->appEmployee())->patchJson("/api/koleksi/aplikasi/{$android->id}", ['tautan' => 'https://bpad.nttprov.go.id'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tautan']);
        $this->actingAs($this->appEmployee())->patchJson("/api/koleksi/aplikasi/{$android->id}", ['jenis' => 'situs', 'tautan' => 'https://bpad.nttprov.go.id'])
            ->assertOk()
            ->assertJsonPath('jenis', 'situs');
    }

    public function test_keeps_a_bundled_logo_sent_back_unchanged_and_lets_it_be_replaced_or_removed(): void
    {
        $employee = $this->appEmployee();
        $app = AgencyApp::query()->where('nama', 'PRO NTT')->firstOrFail();
        $bundled = asset('assets/img/aplikasi/pro-ntt-240.webp');

        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['logo' => $bundled, 'keterangan' => 'Bayar pajak dari ponsel.'])
            ->assertOk()
            ->assertJsonPath('logo', $bundled)
            ->assertJsonPath('keterangan', 'Bayar pajak dari ponsel.');
        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['logo' => 'assets/img/aplikasi/pro-ntt-240.webp'])
            ->assertOk()
            ->assertJsonPath('logo', $bundled);
        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['logo' => asset('assets/img/aplikasi/sikap-240.webp')])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['logo' => 'Logo harus diunggah lewat dashboard.']);

        $logo = $this->logo();
        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['logo' => $logo->url()])
            ->assertOk()
            ->assertJsonPath('logo', $logo->url());
        $this->assertFileExists(public_path('assets/img/aplikasi/pro-ntt-240.webp'));

        $sikap = AgencyApp::query()->where('nama', 'SIKAP')->firstOrFail();
        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$sikap->id}", ['logo' => null])
            ->assertOk()
            ->assertJsonPath('logo', null);
    }

    public function test_changes_an_app_logs_only_real_changes_and_replaces_its_logo(): void
    {
        Storage::fake('public');
        $employee = $this->appEmployee();
        [$first, $second] = [$this->logo(), $this->logo()];
        Storage::disk('public')->put($first->path, 'logo');
        $app = AgencyApp::query()->where('nama', 'SIPAS')->firstOrFail();

        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['keterangan' => 'Arsip SKPD.', 'logo' => $first->url()])
            ->assertOk()
            ->assertJsonPath('keterangan', 'Arsip SKPD.');
        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['keterangan' => 'Arsip SKPD.', 'logo' => $first->url()])->assertOk();
        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['module' => 'aplikasi', 'action' => 'memperbarui', 'target' => 'SIPAS']);

        $this->actingAs($employee)->patchJson("/api/koleksi/aplikasi/{$app->id}", ['logo' => $second->url()])->assertOk();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->path);
        $this->assertNotNull($second->fresh()->dipakai_pada);
    }

    public function test_rejects_logos_that_are_not_its_uploads_or_belong_to_another_app(): void
    {
        $news = Upload::factory()->create(['tujuan' => UploadTarget::Berita]);
        $this->actingAs($this->appEmployee())->postJson('/api/koleksi/aplikasi', ['logo' => $news->url()] + $this->app())
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['logo' => 'Logo harus diunggah lewat dashboard.']);

        $logo = $this->logo();
        $owner = AgencyApp::factory()->create(['logo' => $logo->url()]);
        $logo->markUsed();
        $app = AgencyApp::factory()->create();
        $this->actingAs($this->appEmployee())->patchJson("/api/koleksi/aplikasi/{$app->id}", ['logo' => $logo->url()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo' => 'Logo ini sudah dipakai aplikasi lain. Unggah ulang logonya.']);
        $this->actingAs($this->appEmployee())->patchJson("/api/koleksi/aplikasi/{$owner->id}", ['logo' => $logo->url(), 'nama' => 'SIMPEL 2'])
            ->assertOk()
            ->assertJsonPath('nama', 'SIMPEL 2');
    }

    public function test_reorders_apps_without_touching_their_update_time(): void
    {
        $ids = AgencyApp::query()->orderBy('urutan')->pluck('id')->all();
        $before = AgencyApp::query()->whereKey($ids[3])->value('updated_at');
        $this->travel(1)->hours();

        $this->actingAs($this->appEmployee())->putJson('/api/koleksi/aplikasi/urutan', ['ids' => [$ids[3], $ids[1]]])->assertNoContent();

        $this->actingAs($this->appEmployee())->getJson('/api/koleksi/aplikasi')
            ->assertOk()
            ->assertJsonPath('*.id', [$ids[3], $ids[1], $ids[0], $ids[2], $ids[4]]);
        $this->assertEquals($before, AgencyApp::query()->whereKey($ids[3])->value('updated_at'));
        $this->assertDatabaseHas('activities', ['module' => 'aplikasi', 'action' => 'mengubah urutan', 'target' => '2 aplikasi']);
        $this->actingAs($this->appEmployee())->putJson('/api/koleksi/aplikasi/urutan', ['ids' => [$ids[0], '01m4zzzzzzzzzzzzzzzzzzzzzz']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Urutan tidak cocok dengan data terbaru. Muat ulang halaman lalu coba lagi.']);
    }

    public function test_deletes_apps_with_their_uploaded_logos_but_keeps_the_bundled_ones(): void
    {
        Storage::fake('public');
        $employee = $this->appEmployee();
        $logo = $this->logo();
        Storage::disk('public')->put($logo->path, 'logo');
        $uploaded = AgencyApp::factory()->create(['logo' => $logo->url()]);
        $bundled = AgencyApp::query()->where('nama', 'PRO NTT')->firstOrFail();

        $this->actingAs($employee)->postJson('/api/koleksi/aplikasi/hapus', ['ids' => [$uploaded->id, $bundled->id]])->assertNoContent();

        $this->assertModelMissing($uploaded);
        $this->assertModelMissing($bundled);
        $this->assertModelMissing($logo);
        Storage::disk('public')->assertMissing($logo->path);
        $this->assertFileExists(public_path('assets/img/aplikasi/pro-ntt-240.webp'));
        $this->assertDatabaseHas('activities', ['module' => 'aplikasi', 'action' => 'menghapus', 'target' => '2 entri']);
    }

    private function appEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Aplikasi)->create();
    }

    private function logo(): Upload
    {
        return Upload::factory()->create(['tujuan' => UploadTarget::Aplikasi]);
    }

    /**
     * @return array<string, mixed>
     */
    private function app(): array
    {
        return [
            'nama' => 'SIMPEL',
            'namaLengkap' => 'Sistem Informasi Pelayanan',
            'keterangan' => 'Pencatatan pelayanan loket.',
            'jenis' => 'situs',
            'pengelola' => 'uptd',
            'tautan' => 'https://simpel.uptdpenda-kupang.web.id',
            'logo' => null,
        ];
    }
}
