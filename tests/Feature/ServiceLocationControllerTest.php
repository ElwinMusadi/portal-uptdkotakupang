<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\ServiceLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceLocationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'list' => ['GET', '/api/koleksi/lokasi'],
            'add' => ['POST', '/api/koleksi/lokasi'],
            'read' => ['GET', '/api/koleksi/lokasi/{id}'],
            'edit' => ['PATCH', '/api/koleksi/lokasi/{id}'],
            'delete' => ['POST', '/api/koleksi/lokasi/hapus'],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $location = ServiceLocation::factory()->create();

        $this->json($method, str_replace('{id}', $location->id, $uri), ['nama' => 'CFD Eltari', 'kecamatan' => 'Kec. Oebobo', 'ids' => [$location->id]])
            ->assertUnauthorized();
    }

    #[DataProvider('routes')]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $location = ServiceLocation::factory()->create();
        $monitor = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();

        $response = $this->actingAs($monitor)->json($method, str_replace('{id}', $location->id, $uri), ['nama' => 'CFD Eltari', 'kecamatan' => 'Kec. Oebobo', 'ids' => [$location->id]]);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('service_locations', 1);
    }

    public function test_lists_every_place_by_name(): void
    {
        ServiceLocation::factory()->create(['nama' => 'Taman Nostalgia', 'kecamatan' => 'Kec. Kota Lama']);
        $first = ServiceLocation::factory()->create(['nama' => 'Bundaran PU', 'urutan' => 4]);

        $response = $this->actingAs($this->kelilingEmployee())->getJson('/api/koleksi/lokasi');

        $response->assertOk()->assertJsonPath('0', [
            'id' => $first->id,
            'nama' => 'Bundaran PU',
            'kecamatan' => 'Kec. Oebobo',
            'urutan' => 4,
            'dibuat' => $first->created_at->toIso8601String(),
            'diperbarui' => $first->updated_at->toIso8601String(),
        ]);
        $this->assertSame(['Bundaran PU', 'Taman Nostalgia'], $response->json('*.nama'));
    }

    public function test_adds_a_place_with_201_and_logs_it(): void
    {
        ServiceLocation::factory()->create(['urutan' => 2]);
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/lokasi', ['nama' => 'CFD Eltari', 'kecamatan' => 'Kec. Oebobo', 'urutan' => 99]);

        $response->assertCreated()->assertJsonPath('nama', 'CFD Eltari')->assertJsonPath('urutan', 3);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'keliling', 'action' => 'menambahkan', 'target' => 'Lokasi CFD Eltari']);
    }

    public function test_rejects_a_name_already_in_the_list_whatever_its_letter_case(): void
    {
        ServiceLocation::factory()->create(['nama' => 'CFD Eltari']);

        $response = $this->actingAs($this->kelilingEmployee())->postJson('/api/koleksi/lokasi', ['nama' => 'cfd eltari', 'kecamatan' => 'Kec. Oebobo']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['nama' => 'Lokasi ini sudah ada di daftar.']);
        $this->assertDatabaseCount('service_locations', 1);
    }

    public function test_requires_a_name_and_district_of_plain_text(): void
    {
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->postJson('/api/koleksi/lokasi', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Nama lokasi wajib diisi.', 'kecamatan' => 'Kecamatan wajib diisi.']);
        $this->actingAs($employee)->postJson('/api/koleksi/lokasi', ['nama' => str_repeat('a', 121), 'kecamatan' => "Kec.\u{0007}"])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Nama lokasi maksimal 120 karakter.', 'kecamatan' => 'Kecamatan berisi karakter kontrol yang tidak diizinkan.']);
    }

    public function test_renames_a_place_without_changing_the_schedule_and_logs_it(): void
    {
        $location = ServiceLocation::factory()->create(['nama' => 'Bundaran PU']);
        $stop = MobileServiceStop::factory()->create(['lokasi' => 'Bundaran PU']);
        $employee = $this->kelilingEmployee();

        $response = $this->actingAs($employee)->patchJson("/api/koleksi/lokasi/{$location->id}", ['nama' => 'Bundaran PU Kupang']);

        $response->assertOk()->assertJsonPath('nama', 'Bundaran PU Kupang')->assertJsonPath('kecamatan', 'Kec. Oebobo');
        $this->assertSame('Bundaran PU', $stop->fresh()->lokasi);
        $this->assertDatabaseHas('activities', ['action' => 'memperbarui', 'target' => 'Lokasi Bundaran PU Kupang']);
    }

    public function test_keeps_a_place_its_own_name_in_another_letter_case_but_not_another_places(): void
    {
        $location = ServiceLocation::factory()->create(['nama' => 'Bundaran PU']);
        ServiceLocation::factory()->create(['nama' => 'Taman Nostalgia']);
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->patchJson("/api/koleksi/lokasi/{$location->id}", ['nama' => 'Bundaran Pu'])->assertOk();
        $this->actingAs($employee)->patchJson("/api/koleksi/lokasi/{$location->id}", ['nama' => 'taman nostalgia'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Lokasi ini sudah ada di daftar.']);
    }

    public function test_logs_nothing_when_nothing_changes(): void
    {
        $location = ServiceLocation::factory()->create(['kecamatan' => 'Kec. Oebobo']);

        $this->actingAs($this->kelilingEmployee())->patchJson("/api/koleksi/lokasi/{$location->id}", ['kecamatan' => 'Kec. Oebobo'])->assertOk();

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_removes_places_from_the_list_without_touching_the_schedule(): void
    {
        $location = ServiceLocation::factory()->create(['nama' => 'Bundaran PU']);
        $kept = ServiceLocation::factory()->create();
        $stop = MobileServiceStop::factory()->create(['lokasi' => 'Bundaran PU']);
        $employee = $this->kelilingEmployee();

        $this->actingAs($employee)->postJson('/api/koleksi/lokasi/hapus', ['ids' => [$location->id]])->assertNoContent();

        $this->assertModelMissing($location);
        $this->assertModelExists($kept);
        $this->assertSame('Bundaran PU', $stop->fresh()->lokasi);
        $this->assertDatabaseHas('activities', ['action' => 'menghapus', 'target' => 'Lokasi Bundaran PU']);
        $this->actingAs($employee)->postJson('/api/koleksi/lokasi/hapus', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids' => 'Pilih lokasi yang akan dihapus.']);
    }

    private function kelilingEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Keliling)->create();
    }
}
