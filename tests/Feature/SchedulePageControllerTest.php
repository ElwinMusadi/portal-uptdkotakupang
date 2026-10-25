<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SchedulePageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/keliling', Setting::INITIAL_SCHEDULE_PAGE)->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_layanan_luar_kantor_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Jam, DashboardModule::Beranda)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/keliling', Setting::INITIAL_SCHEDULE_PAGE);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_text_of_the_jadwal_page_markup_until_first_saved(): void
    {
        $this->actingAs($this->kelilingEmployee())->getJson('/api/pengaturan/keliling')
            ->assertOk()
            ->assertExactJson(Setting::INITIAL_SCHEDULE_PAGE)
            ->assertJsonPath('luarKantor.judul', 'Kami yang datang.')
            ->assertJsonPath('luarKantor.lanjutan', 'Ke kelurahan Anda.')
            ->assertJsonPath('luarKantor.catatan', 'Samsat Keliling hanya melayani pajak tahunan.')
            ->assertJsonPath('pembuka.judul', 'Buka setiap hari kerja.')
            ->assertJsonPath('jam.lanjutan', 'Antre lebih pendek.');
    }

    public function test_saves_the_document_with_empty_optional_texts_and_logs_it(): void
    {
        $employee = $this->kelilingEmployee();
        $page = Setting::INITIAL_SCHEDULE_PAGE;
        $page['luarKantor']['judul'] = 'Kami datang ke Anda.';
        $page['luarKantor']['catatan'] = null;
        $page['luarKantor']['lanjutan'] = '';
        $page['pembuka']['eyebrow'] = null;
        $page['jam']['deskripsi'] = 'Tidak disimpan';
        $page['lainnya'] = ['judul' => 'Tidak disimpan'];

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/keliling', $page);

        $expected = Setting::INITIAL_SCHEDULE_PAGE;
        $expected['luarKantor'] = [...$expected['luarKantor'], 'judul' => 'Kami datang ke Anda.', 'lanjutan' => '', 'catatan' => ''];
        $expected['pembuka']['eyebrow'] = '';
        $response->assertOk()->assertExactJson($expected);
        $this->assertSame($expected, Setting::schedulePage());
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'keliling', 'action' => 'memperbarui', 'target' => 'Layanan Luar Kantor']);
    }

    public function test_lets_administrators_save_it_and_logs_nothing_when_the_same_document_is_saved_again(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($administrator)->putJson('/api/pengaturan/keliling', Setting::INITIAL_SCHEDULE_PAGE)->assertOk();

        $this->actingAs($administrator)->putJson('/api/pengaturan/keliling', Setting::INITIAL_SCHEDULE_PAGE)->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_every_section(): void
    {
        $page = Setting::INITIAL_SCHEDULE_PAGE;
        $page['pembuka']['judul'] = '';
        $page['pembuka']['deskripsi'] = str_repeat('a', 301);
        $page['jam']['eyebrow'] = str_repeat('a', 121);
        $page['jam']['lanjutan'] = str_repeat('a', 81);
        $page['luarKantor']['judul'] = str_repeat('a', 81);
        $page['luarKantor']['deskripsi'] = "Bawa STNK\u{0007}";
        // A private-use character survives the request's trimming but shows nothing
        $page['luarKantor']['catatan'] = "\u{E000}";

        $response = $this->actingAs($this->kelilingEmployee())->putJson('/api/pengaturan/keliling', $page);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'pembuka.judul' => 'Judul pembuka wajib diisi.',
            'pembuka.deskripsi' => 'Paragraf pembuka maksimal 300 karakter.',
            'jam.eyebrow' => 'Teks kecil jam loket maksimal 120 karakter.',
            'jam.lanjutan' => 'Lanjutan judul jam loket maksimal 80 karakter.',
            'luarKantor.judul' => 'Judul layanan di luar kantor maksimal 80 karakter.',
            'luarKantor.deskripsi' => 'Paragraf layanan di luar kantor berisi karakter kontrol yang tidak diizinkan.',
            'luarKantor.catatan' => 'Catatan layanan di luar kantor tidak boleh hanya berisi spasi atau karakter tak terlihat.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_keeps_the_note_to_one_line_of_160_characters(): void
    {
        $page = Setting::INITIAL_SCHEDULE_PAGE;
        $page['luarKantor']['catatan'] = str_repeat('a', 161);

        $this->actingAs($this->kelilingEmployee())->putJson('/api/pengaturan/keliling', $page)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['luarKantor.catatan' => 'Catatan layanan di luar kantor maksimal 160 karakter.']);

        $page['luarKantor']['catatan'] = str_repeat('a', 160);
        $this->actingAs($this->kelilingEmployee())->putJson('/api/pengaturan/keliling', $page)->assertOk();
    }

    public function test_requires_every_section(): void
    {
        $page = Setting::INITIAL_SCHEDULE_PAGE;
        unset($page['jam']);
        $page['luarKantor'] = 'Kami yang datang.';

        $this->actingAs($this->kelilingEmployee())->putJson('/api/pengaturan/keliling', $page)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jam' => 'Bagian jam loket wajib diisi.', 'luarKantor' => 'Bagian layanan di luar kantor tidak valid.']);
    }

    private function kelilingEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Keliling)->create();
    }
}
