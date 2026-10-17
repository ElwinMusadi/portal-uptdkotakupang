<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TransferServicesMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_publishes_change_of_owner_and_transfer_in_at_the_end_of_the_list(): void
    {
        $services = Service::query()->whereIn('slug', ['balik-nama', 'mutasi-masuk'])->orderBy('urutan')->get();

        $this->assertSame(['balik-nama', 'mutasi-masuk'], $services->pluck('slug')->all());
        $this->assertSame([WorkflowStatus::Published, WorkflowStatus::Published], $services->pluck('status')->all());
        $this->assertSame([6, 7], $services->pluck('urutan')->all());
        $this->assertNotNull($services[0]->terbit_pada);
        $this->assertSame(['menerbitkan atas keputusan pemilik', 'terbit'], [$services[0]->riwayat[0]['aksi'], $services[0]->riwayat[0]['ke']]);
        $this->assertSame('draf', $services[0]->riwayat[1]['ke']);
        $this->assertSame(['Membeli kendaraan bekas yang terdaftar di Kota Kupang', 'Mengganti nama pemilik di STNK dan BPKB'], $services[0]->situasi);
        $this->assertSame(['Pindah ke Kota Kupang dengan kendaraan dari daerah lain', 'Membeli kendaraan dari luar daerah untuk dipakai di Kota Kupang'], $services[1]->situasi);
        $this->assertSame(['id' => 'p1', 'dokumen' => 'Berkas mutasi dari Samsat daerah asal (tersegel)', 'jumlah' => '1 berkas'], $services[1]->persyaratan[0]);
        $this->assertSame(['Loket 3', true, 'layanan'], [$services[0]->loket, $services[0]->cek_fisik, $services[0]->jenis]);
        $this->assertSame(['balik-nama', 'mutasi-masuk'], array_slice($this->getJson('/api/public/layanan')->assertOk()->json('*.slug'), -2));
    }

    public function test_adds_drafts_only_for_free_slugs_and_removes_only_drafts(): void
    {
        [$insert, $publish] = $this->migrations();
        $publish->down();
        $insert->down();
        Service::factory()->withStatus(WorkflowStatus::Published)->create(['slug' => 'balik-nama', 'nama' => 'Balik nama versi petugas']);

        $insert->up();

        $this->assertSame(['Balik nama versi petugas'], Service::query()->where('slug', 'balik-nama')->pluck('nama')->all());
        $this->assertSame([WorkflowStatus::Draft], Service::query()->where('slug', 'mutasi-masuk')->pluck('status')->all());

        $insert->down();

        $this->assertSame(1, Service::query()->where('slug', 'balik-nama')->count());
        $this->assertSame(0, Service::query()->where('slug', 'mutasi-masuk')->count());
    }

    public function test_publishes_only_drafts_and_takes_back_only_its_own_step(): void
    {
        [, $publish] = $this->migrations();
        $publish->down();
        $this->assertSame([[WorkflowStatus::Draft, null, 1], [WorkflowStatus::Draft, null, 1]], $this->states());
        $transferIn = Service::query()->where('slug', 'mutasi-masuk')->sole();
        $transferIn->moveTo(WorkflowStatus::UnderReview, 'Petugas Layanan', 'mengajukan');
        $transferIn->save();

        $publish->up();

        $this->assertSame([WorkflowStatus::Published, WorkflowStatus::UnderReview], array_column($this->states(), 0));

        $ownerChange = Service::query()->where('slug', 'balik-nama')->sole();
        $ownerChange->moveTo(WorkflowStatus::Published, 'Administrator', 'menyetujui dan menerbitkan');
        $ownerChange->save();
        $publish->down();

        $this->assertSame([WorkflowStatus::Published, WorkflowStatus::UnderReview], array_column($this->states(), 0));
    }

    /**
     * The migration that adds the drafts and the one that publishes them.
     *
     * @return array{Migration, Migration}
     */
    private function migrations(): array
    {
        $this->assertDatabaseCount('services', 8);

        return [
            require database_path('migrations/2026_10_17_110750_insert_draft_transfer_services.php'),
            require database_path('migrations/2026_10_17_141522_publish_transfer_services.php'),
        ];
    }

    /**
     * Status, time of publication and number of workflow steps of the two
     * services, change of owner first.
     *
     * @return list<array{WorkflowStatus, ?string, int}>
     */
    private function states(): array
    {
        return Service::query()->whereIn('slug', ['balik-nama', 'mutasi-masuk'])->orderBy('slug')->get()
            ->map(fn (Service $service): array => [$service->status, $service->terbit_pada?->toIso8601String(), count($service->riwayat)])
            ->all();
    }
}
