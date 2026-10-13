<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MobileServiceStopControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string MODULE_ONLY = 'Modul Samsat Keliling belum ditugaskan kepada Anda.';

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'list' => ['GET', '/api/koleksi/keliling'],
            'add' => ['POST', '/api/koleksi/keliling'],
            'read' => ['GET', '/api/koleksi/keliling/{id}'],
            'edit' => ['PATCH', '/api/koleksi/keliling/{id}'],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $stop = MobileServiceStop::factory()->create();

        $response = $this->json($method, str_replace('{id}', $stop->id, $uri), $this->newStop());

        $response->assertUnauthorized();
    }

    #[DataProvider('routes')]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $stop = MobileServiceStop::factory()->create();
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $response = $this->actingAs($employee)->json($method, str_replace('{id}', $stop->id, $uri), $this->newStop());

        $response->assertForbidden()->assertExactJson(['message' => self::MODULE_ONLY]);
        $this->assertDatabaseCount('mobile_service_stops', 1);
    }

    public function test_lets_update_schedule_managers_list_the_stops_but_not_change_them(): void
    {
        MobileServiceStop::factory()->create();
        $monitor = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();

        $this->actingAs($monitor)->getJson('/api/koleksi/keliling')->assertOk()->assertJsonCount(1);
        $this->actingAs($monitor)->postJson('/api/koleksi/keliling', $this->newStop())->assertForbidden();
    }

    public function test_lists_every_stop_by_day_and_time(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        MobileServiceStop::factory()->create(['hari' => 2, 'mulai' => '08:00', 'lokasi' => 'Selasa pagi']);
        MobileServiceStop::factory()->inactive()->create(['hari' => 1, 'mulai' => '13:00', 'selesai' => '15:00', 'lokasi' => 'Senin siang']);
        $first = MobileServiceStop::factory()->create([
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Senin pagi',
            'kecamatan' => 'Kec. Kelapa Lima',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK.',
            'urutan' => 7,
        ]);

        $response = $this->actingAs($this->kelilingEmployee())->getJson('/api/koleksi/keliling');

        $response->assertOk()->assertJsonPath('0', [
            'id' => $first->id,
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Senin pagi',
            'kecamatan' => 'Kec. Kelapa Lima',
            'layanan' => 'Pajak tahunan',
            'catatan' => 'Bawa STNK.',
            'aktif' => true,
            'urutan' => 7,
            'dibuat' => '2026-10-13T08:00:00+00:00',
            'diperbarui' => '2026-10-13T08:00:00+00:00',
        ]);
        $this->assertSame(['Senin pagi', 'Senin siang', 'Selasa pagi'], $response->json('*.lokasi'));
    }

    public function test_shows_one_stop_and_returns_404_for_one_that_does_not_exist(): void
    {
        $stop = MobileServiceStop::factory()->create();
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->getJson("/api/koleksi/keliling/{$stop->id}")->assertOk()->assertJsonPath('id', $stop->id);
        $this->actingAs($employee)->getJson('/api/koleksi/keliling/01kaaaaaaaaaaaaaaaaaaaaaaa')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_adds_a_stop_with_201_and_logs_it(): void
    {
        MobileServiceStop::factory()->create(['urutan' => 3]);
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling', ['id' => '01kaaaaaaaaaaaaaaaaaaaaaaa', 'urutan' => 99] + $this->newStop());

        $response->assertCreated()
            ->assertJsonPath('hari', 1)
            ->assertJsonPath('lokasi', 'Kantor Kelurahan Oebobo')
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('urutan', 4);
        $this->assertNotSame('01kaaaaaaaaaaaaaaaaaaaaaaa', $response->json('id'));
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'menambahkan',
            'target' => 'Senin · Kantor Kelurahan Oebobo',
        ]);
    }

    public function test_publishes_only_active_stops_to_the_public_api(): void
    {
        $employee = $this->kelilingEmployee();
        $this->actingAs($employee)->postJson('/api/koleksi/keliling', $this->newStop())->assertCreated();
        $this->actingAs($employee)->postJson('/api/koleksi/keliling', ['lokasi' => 'Disembunyikan', 'aktif' => false] + $this->newStop())->assertCreated();

        $response = $this->getJson('/api/public/keliling');

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.lokasi', 'Kantor Kelurahan Oebobo');
    }

    public function test_requires_day_times_place_district_service_and_display(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['catatan' => '']);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'hari' => 'Pilih hari.',
            'mulai' => 'Isi jam mulai.',
            'selesai' => 'Isi jam selesai.',
            'lokasi' => 'Lokasi wajib diisi.',
            'kecamatan' => 'Kecamatan wajib diisi.',
            'layanan' => 'Layanan wajib diisi.',
            'aktif' => 'Tentukan apakah jadwal ini ditampilkan.',
        ]);
    }

    #[TestWith([0])]
    #[TestWith([7])]
    public function test_accepts_only_monday_to_saturday(int $hari): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['hari' => $hari] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['hari' => 'Samsat Keliling melayani Senin sampai Sabtu.']);
    }

    #[TestWith(['8:00'])]
    #[TestWith(['08.00'])]
    #[TestWith(['24:00'])]
    public function test_rejects_a_time_that_is_not_hh_mm(string $mulai): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['mulai' => $mulai] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['mulai' => 'Jam mulai tidak valid.']);
    }

    #[TestWith(['12:00'])]
    #[TestWith(['07:30'])]
    public function test_requires_the_stop_to_end_after_it_starts(string $selesai): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['mulai' => '12:00', 'selesai' => $selesai] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['selesai' => 'Jam selesai harus setelah jam mulai.']);
    }

    public function test_compares_a_changed_end_time_with_the_stored_start_time(): void
    {
        $stop = MobileServiceStop::factory()->create(['mulai' => '13:00', 'selesai' => '15:00']);

        $response = $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/keliling/{$stop->id}", ['selesai' => '12:00']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['selesai' => 'Jam selesai harus setelah jam mulai.']);
        $this->assertSame('15:00', $stop->fresh()->selesai);
    }

    public function test_rejects_control_characters_and_invisible_text(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', [
            'lokasi' => "Kantor\u{0007}",
            'layanan' => "\u{0301}",
        ] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'lokasi' => 'Lokasi berisi karakter kontrol yang tidak diizinkan.',
            'layanan' => 'Layanan tidak boleh hanya berisi spasi atau karakter tak terlihat.',
        ]);
    }

    public function test_rejects_a_place_longer_than_120_characters(): void
    {
        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/keliling', ['lokasi' => str_repeat('a', 121)] + $this->newStop());

        $response->assertUnprocessable()->assertJsonValidationErrors(['lokasi' => 'Lokasi maksimal 120 karakter.']);
    }

    public function test_changes_only_the_fields_sent_and_logs_the_change(): void
    {
        $stop = MobileServiceStop::factory()->create(['hari' => 3, 'lokasi' => 'Kantor Kelurahan Penfui', 'aktif' => true]);
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->patchJson("/api/koleksi/keliling/{$stop->id}", ['aktif' => false]);

        $response->assertOk()->assertJsonPath('aktif', false)->assertJsonPath('lokasi', 'Kantor Kelurahan Penfui');
        $this->assertFalse($stop->fresh()->aktif);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'memperbarui',
            'target' => 'Rabu · Kantor Kelurahan Penfui',
        ]);
    }

    public function test_logs_nothing_when_nothing_changes(): void
    {
        $stop = MobileServiceStop::factory()->create(['aktif' => true]);

        $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/keliling/{$stop->id}", ['aktif' => true])->assertOk();

        $this->assertDatabaseCount('activities', 0);
    }

    private function kelilingEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Keliling)->create();
    }

    /**
     * @return array{hari: int, mulai: string, selesai: string, lokasi: string, kecamatan: string, layanan: string, catatan: string, aktif: bool}
     */
    private function newStop(): array
    {
        return [
            'hari' => 1,
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Kantor Kelurahan Oebobo',
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => '',
            'aktif' => true,
        ];
    }
}
