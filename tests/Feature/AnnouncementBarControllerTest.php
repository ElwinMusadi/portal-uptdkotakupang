<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AnnouncementBarControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/berita', Setting::INITIAL_ANNOUNCEMENT_BAR)->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_berita_and_pengumuman_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Beranda, DashboardModule::Dokumentasi)->create();

        $this->actingAs($employee)->json($method, '/api/pengaturan/berita', Setting::INITIAL_ANNOUNCEMENT_BAR)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Berita & Pengumuman belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_bar_of_the_informasi_markup_until_first_saved(): void
    {
        $this->actingAs($this->beritaEmployee())->getJson('/api/pengaturan/berita')
            ->assertOk()
            ->assertExactJson(Setting::INITIAL_ANNOUNCEMENT_BAR);
    }

    public function test_lets_a_berita_employee_save_it_without_empty_texts_or_unknown_keys_and_logs_it(): void
    {
        $employee = $this->beritaEmployee();

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/berita', [
            'aktif' => 0, 'teks' => null, 'tautanLabel' => null, 'tautanUrl' => null, 'warna' => 'merah',
        ]);

        $expected = ['aktif' => false, 'teks' => '', 'tautanLabel' => '', 'tautanUrl' => ''];
        $response->assertOk()->assertExactJson($expected);
        $this->assertSame($expected, Setting::announcementBar());
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'berita', 'action' => 'memperbarui', 'target' => 'Berita & Pengumuman']);
        $this->assertSame(Setting::INITIAL_SITE, Setting::site());
    }

    public function test_lets_administrators_save_it_and_logs_nothing_when_the_same_bar_is_saved_again(): void
    {
        $administrator = User::factory()->administrator()->create();
        $bar = ['aktif' => true, 'teks' => 'Loket tutup Senin.', 'tautanLabel' => 'Lihat', 'tautanUrl' => 'https://uptdpenda-kupang.web.id/berita/loket-tutup'];
        $this->actingAs($administrator)->putJson('/api/pengaturan/berita', $bar)->assertOk()->assertExactJson($bar);

        $this->actingAs($administrator)->putJson('/api/pengaturan/berita', $bar)->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_the_bar(): void
    {
        $employee = $this->beritaEmployee();

        $this->actingAs($employee)->putJson('/api/pengaturan/berita', ['aktif' => true, 'teks' => '', 'tautanLabel' => str_repeat('a', 31), 'tautanUrl' => 'data:text/html,x'])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'teks' => 'Teks pengumuman wajib diisi selama bilah pengumuman ditampilkan.',
                'tautanLabel' => 'Label tautan maksimal 30 karakter.',
                'tautanUrl' => 'Tujuan tautan: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            ]);
        $this->actingAs($employee)->putJson('/api/pengaturan/berita', ['aktif' => 'ya', 'teks' => str_repeat('a', 201), 'tautanUrl' => 'https://example.com/'.str_repeat('a', 281)])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'aktif' => 'Pilihan tampilkan bilah pengumuman tidak valid.',
                'teks' => 'Teks pengumuman maksimal 200 karakter.',
                'tautanUrl' => 'Tujuan tautan maksimal 300 karakter.',
            ]);
        $this->actingAs($employee)->putJson('/api/pengaturan/berita', ['teks' => "Pengumuman\u{0007}"])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['aktif' => 'Pilihan tampilkan bilah pengumuman wajib diisi.', 'teks' => 'Teks pengumuman berisi karakter kontrol yang tidak diizinkan.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_keeps_a_hidden_bar_without_text(): void
    {
        $this->actingAs($this->beritaEmployee())->putJson('/api/pengaturan/berita', ['aktif' => false, 'teks' => ''])
            ->assertOk()
            ->assertExactJson(['aktif' => false, 'teks' => '', 'tautanLabel' => '', 'tautanUrl' => '']);
    }

    private function beritaEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Berita)->create();
    }
}
