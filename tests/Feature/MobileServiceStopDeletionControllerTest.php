<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MobileServiceStopDeletionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $stop = MobileServiceStop::factory()->create();

        $this->postJson('/api/koleksi/keliling/hapus', ['ids' => [$stop->id]])->assertUnauthorized();

        $this->assertModelExists($stop);
    }

    public function test_forbids_employees_without_the_module_with_403(): void
    {
        $stop = MobileServiceStop::factory()->create();
        $monitor = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();

        $response = $this->actingAs($monitor)->postJson('/api/koleksi/keliling/hapus', ['ids' => [$stop->id]]);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Layanan Luar Kantor belum ditugaskan kepada Anda.']);
        $this->assertModelExists($stop);
    }

    public function test_lets_employees_of_the_module_delete_stops_and_logs_one_by_its_title(): void
    {
        $stop = MobileServiceStop::factory()->on('2026-10-23', '02')->create(['lokasi' => 'Kantor Kelurahan Alak']);
        $kept = MobileServiceStop::factory()->create();
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/keliling/hapus', [
            'ids' => [$stop->id, '01kaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $response->assertNoContent();
        $this->assertModelMissing($stop);
        $this->assertModelExists($kept);
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'keliling',
            'action' => 'menghapus',
            'target' => 'Samsat Keliling 02 · Jumat, 2026-10-23 · Kantor Kelurahan Alak',
        ]);
    }

    public function test_logs_several_deletions_as_a_count(): void
    {
        $ids = MobileServiceStop::factory()->count(2)->create()->modelKeys();

        $this->actingAs(User::factory()->administrator()->create())->postJson('/api/koleksi/keliling/hapus', ['ids' => $ids])->assertNoContent();

        $this->assertDatabaseHas('activities', ['module' => 'keliling', 'action' => 'menghapus', 'target' => '2 entri']);
    }

    public function test_requires_the_stops_to_delete(): void
    {
        $response = $this->actingAs(User::factory()->administrator()->create())->postJson('/api/koleksi/keliling/hapus', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['ids' => 'Pilih jadwal keliling yang akan dihapus.']);
    }
}
