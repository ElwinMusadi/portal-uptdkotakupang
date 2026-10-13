<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\ScheduleAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScheduleAdjustmentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $adjustment = ScheduleAdjustment::factory()->create();
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $this->getJson('/api/koleksi/penyesuaian')->assertUnauthorized();
        $this->actingAs($employee)->getJson('/api/koleksi/penyesuaian')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Jam Pelayanan belum ditugaskan kepada Anda.']);
        $this->actingAs($employee)->patchJson("/api/koleksi/penyesuaian/{$adjustment->id}", ['keterangan' => 'Diubah'])->assertForbidden();
        $this->actingAs($employee)->postJson('/api/koleksi/penyesuaian/hapus', ['ids' => [$adjustment->id]])->assertForbidden();
        $this->assertModelExists($adjustment);
    }

    public function test_lists_adjustments_by_date(): void
    {
        ScheduleAdjustment::factory()->create(['tanggal' => '2026-12-25', 'keterangan' => 'Hari Raya Natal']);
        $first = ScheduleAdjustment::factory()->specialHours()->create(['tanggal' => '2026-10-16', 'keterangan' => 'Apel bersama']);

        $response = $this->actingAs($this->jamEmployee())->getJson('/api/koleksi/penyesuaian');

        $response->assertOk()->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('0.tanggal', '2026-10-16')
            ->assertJsonPath('0.jenis', 'jam-khusus')
            ->assertJsonPath('0.mulai', '08:00')
            ->assertJsonPath('0.selesai', '11:30');
        $this->assertSame(['Apel bersama', 'Hari Raya Natal'], $response->json('*.keterangan'));
    }

    public function test_adds_a_closed_day_without_times_and_logs_it(): void
    {
        $employee = $this->jamEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/penyesuaian', [
            'tanggal' => '2026-12-25',
            'jenis' => 'tutup',
            'mulai' => '08:00',
            'selesai' => '12:00',
            'keterangan' => 'Hari Raya Natal',
        ]);

        $response->assertCreated()->assertJsonPath('mulai', '')->assertJsonPath('selesai', '')->assertJsonPath('tanggal', '2026-12-25');
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'jam',
            'action' => 'menambahkan',
            'target' => '2026-12-25 · Hari Raya Natal',
        ]);
    }

    public function test_requires_special_hours_to_have_both_times_closing_after_opening(): void
    {
        $employee = $this->jamEmployee();
        $base = ['tanggal' => '2026-10-16', 'jenis' => 'jam-khusus', 'keterangan' => 'Apel bersama'];

        $this->actingAs($employee)->postJson('/api/koleksi/penyesuaian', $base)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mulai' => 'Isi jam buka.', 'selesai' => 'Isi jam tutup.']);
        $this->actingAs($employee)->postJson('/api/koleksi/penyesuaian', ['mulai' => '11:30', 'selesai' => '08:00'] + $base)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selesai' => 'Jam tutup harus setelah jam buka.']);
    }

    public function test_allows_one_adjustment_per_date(): void
    {
        $existing = ScheduleAdjustment::factory()->create(['tanggal' => '2026-12-25']);
        $employee = $this->jamEmployee();

        $this->actingAs($employee)->postJson('/api/koleksi/penyesuaian', ['tanggal' => '2026-12-25', 'jenis' => 'tutup', 'keterangan' => 'Natal'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal' => 'Sudah ada penyesuaian untuk tanggal ini.']);
        $this->actingAs($employee)->patchJson("/api/koleksi/penyesuaian/{$existing->id}", ['tanggal' => '2026-12-25', 'keterangan' => 'Natal'])
            ->assertOk();
    }

    public function test_checks_a_change_to_special_hours_against_the_stored_times(): void
    {
        $closed = ScheduleAdjustment::factory()->create();
        $special = ScheduleAdjustment::factory()->specialHours('08:00', '11:30')->create();
        $employee = $this->jamEmployee();

        $this->actingAs($employee)->patchJson("/api/koleksi/penyesuaian/{$closed->id}", ['jenis' => 'jam-khusus'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mulai' => 'Isi jam buka.']);
        $this->actingAs($employee)->patchJson("/api/koleksi/penyesuaian/{$special->id}", ['selesai' => '07:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['selesai' => 'Jam tutup harus setelah jam buka.']);
        $this->actingAs($employee)->patchJson("/api/koleksi/penyesuaian/{$special->id}", ['jenis' => 'tutup'])
            ->assertOk()
            ->assertJsonPath('mulai', '');
    }

    public function test_lets_employees_of_the_module_delete_adjustments(): void
    {
        $adjustment = ScheduleAdjustment::factory()->create(['tanggal' => '2026-12-25', 'keterangan' => 'Hari Raya Natal']);
        $employee = $this->jamEmployee();

        $this->actingAs($employee)->postJson('/api/koleksi/penyesuaian/hapus', ['ids' => [$adjustment->id]])->assertNoContent();

        $this->assertModelMissing($adjustment);
        $this->assertDatabaseHas('activities', ['module' => 'jam', 'action' => 'menghapus', 'target' => '2026-12-25 · Hari Raya Natal']);
    }

    private function jamEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Jam)->create();
    }
}
