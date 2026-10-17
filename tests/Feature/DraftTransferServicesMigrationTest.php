<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DraftTransferServicesMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_adds_change_of_owner_and_transfer_in_as_drafts_kept_off_the_portal(): void
    {
        $services = Service::query()->whereIn('slug', ['balik-nama', 'mutasi-masuk'])->orderBy('urutan')->get();

        $this->assertSame(['balik-nama', 'mutasi-masuk'], $services->pluck('slug')->all());
        $this->assertSame([WorkflowStatus::Draft, WorkflowStatus::Draft], $services->pluck('status')->all());
        $this->assertSame([6, 7], $services->pluck('urutan')->all());
        $this->assertSame(['Membeli kendaraan bekas yang terdaftar di Kota Kupang', 'Mengganti nama pemilik di STNK dan BPKB'], $services[0]->situasi);
        $this->assertSame(['Pindah ke Kota Kupang dengan kendaraan dari daerah lain', 'Membeli kendaraan dari luar daerah untuk dipakai di Kota Kupang'], $services[1]->situasi);
        $this->assertSame(['id' => 'p1', 'dokumen' => 'Berkas mutasi dari Samsat daerah asal (tersegel)', 'jumlah' => '1 berkas'], $services[1]->persyaratan[0]);
        $this->assertSame(['Loket 3', true, 'layanan'], [$services[0]->loket, $services[0]->cek_fisik, $services[0]->jenis]);
        $this->assertNotContains('balik-nama', $this->getJson('/api/public/layanan')->assertOk()->json('*.slug'));
    }

    public function test_leaves_a_service_whose_slug_is_taken_and_removes_only_drafts(): void
    {
        $migration = $this->migration();
        $migration->down();
        Service::factory()->withStatus(WorkflowStatus::Published)->create(['slug' => 'balik-nama', 'nama' => 'Balik nama versi petugas']);

        $migration->up();

        $this->assertSame(['Balik nama versi petugas'], Service::query()->where('slug', 'balik-nama')->pluck('nama')->all());
        $this->assertSame(1, Service::query()->where('slug', 'mutasi-masuk')->count());

        $migration->down();

        $this->assertSame(1, Service::query()->where('slug', 'balik-nama')->count());
        $this->assertSame(0, Service::query()->where('slug', 'mutasi-masuk')->count());
    }

    private function migration(): Migration
    {
        $this->assertDatabaseCount('services', 8);

        return require database_path('migrations/2026_10_17_110750_insert_draft_transfer_services.php');
    }
}
