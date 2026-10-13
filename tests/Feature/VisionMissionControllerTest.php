<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class VisionMissionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/visiMisi', $this->document())->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Profil)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/visiMisi', $this->document());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Visi & Misi belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_official_text_until_the_document_is_first_saved(): void
    {
        $response = $this->actingAs($this->visiMisiEmployee())->getJson('/api/pengaturan/visiMisi');

        $response->assertOk()
            ->assertJsonPath('sumberVisi', 'Visi Pemerintah Provinsi NTT')
            ->assertJsonPath('visi', 'NTT Maju, Sehat, Cerdas, Sejahtera dan Berkelanjutan.')
            ->assertJsonPath('misi.*.kataKunci', ['Maju', 'Sehat', 'Cerdas', 'Sejahtera', 'Berkelanjutan'])
            ->assertJsonPath('kontribusi.*.misi', ['Misi 01 · Maju', 'Misi 04 · Sejahtera'])
            ->assertJsonPath('nilai.*.huruf', ['B', 'A', 'K', 'H', 'L', 'A', 'K'])
            ->assertJsonPath('maklumat.penandatangan', 'Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang')
            ->assertJsonPath('maklumat.berkas', null);
        $this->assertStringStartsWith('Dengan ini, kami menyatakan sanggup', $response->json('maklumat.teks'));
    }

    public function test_saves_the_document_and_logs_it(): void
    {
        $employee = $this->visiMisiEmployee();
        $document = $this->document();
        $document['sumberVisi'] = null;
        $document['misi'][0]['kataKunci'] = null;
        $document['kontribusi'][0]['deskripsi'] = null;
        $document['nilai'][0]['huruf'] = null;
        $document['maklumat']['penandatangan'] = null;
        $document['maklumat']['berkas'] = [];

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/visiMisi', $document);

        $response->assertOk()
            ->assertJsonPath('sumberVisi', '')
            ->assertJsonPath('visi', 'Visi baru yang sudah ditetapkan.')
            ->assertJsonPath('misi.0.kataKunci', '')
            ->assertJsonPath('kontribusi.0.deskripsi', '')
            ->assertJsonPath('nilai.0.huruf', '')
            ->assertJsonPath('maklumat', ['teks' => 'Kami sanggup melayani sesuai standar.', 'penandatangan' => '', 'berkas' => null]);
        $this->assertSame($response->json(), Setting::visionMission());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'visiMisi',
            'action' => 'memperbarui',
            'target' => 'Visi & Misi',
        ]);
    }

    public function test_logs_nothing_when_the_same_document_is_saved_again(): void
    {
        $employee = $this->visiMisiEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/visiMisi', $this->document())->assertOk();

        $this->actingAs($employee)->putJson('/api/pengaturan/visiMisi', $this->document())->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_accepts_empty_lists(): void
    {
        $document = ['misi' => [], 'kontribusi' => [], 'nilai' => []] + $this->document();

        $response = $this->actingAs($this->visiMisiEmployee())->putJson('/api/pengaturan/visiMisi', $document);

        $response->assertOk()->assertJsonPath('misi', [])->assertJsonPath('kontribusi', [])->assertJsonPath('nilai', []);
    }

    public function test_rejects_a_pledge_file_until_uploads_are_stored_on_the_server(): void
    {
        $document = $this->document();
        $document['maklumat']['berkas'] = ['nama' => 'maklumat.pdf', 'ukuran' => 1024, 'tipe' => 'application/pdf', 'url' => 'data:application/pdf;base64,JVBERi0='];

        $response = $this->actingAs($this->visiMisiEmployee())->putJson('/api/pengaturan/visiMisi', $document);

        $response->assertUnprocessable()->assertJsonValidationErrors(['maklumat.berkas' => 'Berkas maklumat belum bisa diunggah ke server.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_validates_the_vision_lists_and_pledge(): void
    {
        $document = $this->document();
        $document['visi'] = '';
        $document['misi'][0]['teks'] = '';
        $document['misi'][1]['id'] = $document['misi'][0]['id'];
        $document['nilai'][0]['huruf'] = 'BER';
        $document['kontribusi'][0]['judul'] = '';
        $document['maklumat']['teks'] = "Maklumat\u{0007}";

        $response = $this->actingAs($this->visiMisiEmployee())->putJson('/api/pengaturan/visiMisi', $document);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'visi' => 'Visi wajib diisi.',
            'misi.0.teks' => 'Misi wajib diisi.',
            'misi.1.id' => 'Misi tercantum dua kali.',
            'nilai.0.huruf' => 'Satu atau dua huruf.',
            'kontribusi.0.judul' => 'Judul wajib diisi.',
            'maklumat.teks' => 'Isi maklumat berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_limits_the_lists(): void
    {
        $document = $this->document();
        $document['misi'] = array_map(fn (int $index): array => ['id' => "m{$index}", 'teks' => "Misi {$index}", 'kataKunci' => ''], range(1, 11));
        $document['kontribusi'] = array_map(fn (int $index): array => ['id' => "k{$index}", 'misi' => '', 'judul' => "Kontribusi {$index}", 'deskripsi' => ''], range(1, 7));

        $response = $this->actingAs($this->visiMisiEmployee())->putJson('/api/pengaturan/visiMisi', $document);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'misi' => 'Maksimal 10 misi.',
            'kontribusi' => 'Maksimal 6 kontribusi.',
        ]);
    }

    private function visiMisiEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::VisiMisi)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        return [
            'sumberVisi' => 'Visi Pemerintah Provinsi NTT',
            'visi' => 'Visi baru yang sudah ditetapkan.',
            'misi' => [
                ['id' => 'm1', 'teks' => 'Misi pertama.', 'kataKunci' => 'Maju'],
                ['id' => 'm2', 'teks' => 'Misi kedua.', 'kataKunci' => 'Sehat'],
            ],
            'kontribusi' => [['id' => 'k1', 'misi' => 'Misi 01 · Maju', 'judul' => 'Tata kelola informasi.', 'deskripsi' => 'Penjelasan.']],
            'nilai' => [['id' => 'n1', 'huruf' => 'B', 'judul' => 'Berorientasi pelayanan', 'deskripsi' => 'Ramah dan solutif.']],
            'maklumat' => ['teks' => 'Kami sanggup melayani sesuai standar.', 'penandatangan' => 'Kepala UPTD', 'berkas' => null],
        ];
    }
}
