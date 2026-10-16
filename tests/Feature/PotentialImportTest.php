<?php

namespace Tests\Feature;

use App\Actions\ReadPotentialRows;
use App\Enums\DashboardModule;
use App\Models\Activity;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PotentialImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-16 03:00:00');
        $this->employee = User::factory()->withModules(DashboardModule::Pemetaan)->create(['name' => 'Petugas Pemetaan']);
    }

    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(): void
    {
        $import = PotentialImport::factory()->unfinished()->create();
        $vehicle = PotentialVehicle::factory()->create();
        $requests = [
            ['GET', '/api/pemetaan/impor'],
            ['POST', '/api/pemetaan/impor'],
            ['POST', "/api/pemetaan/impor/{$import->id}/baris"],
            ['POST', "/api/pemetaan/impor/{$import->id}/selesai"],
            ['GET', '/api/pemetaan/ringkasan'],
            ['GET', '/api/pemetaan/kendaraan'],
            ['GET', "/api/pemetaan/kendaraan/{$vehicle->id}"],
        ];

        foreach ([...$requests, ['DELETE', '/api/pemetaan/impor']] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
        $this->actingAs(User::factory()->withModules(DashboardModule::Penerimaan)->create());
        foreach ($requests as [$method, $uri]) {
            $this->json($method, $uri)->assertForbidden()->assertExactJson(['message' => 'Modul Pemetaan PKB belum ditugaskan kepada Anda.']);
        }
        $this->actingAs($this->employee)->deleteJson('/api/pemetaan/impor')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus data potensi PKB.']);
    }

    public function test_imports_a_file_in_parts_and_replaces_the_data_before_it(): void
    {
        $older = PotentialImport::factory()->create(['jumlah' => 1, 'diterima' => 1, 'selesai_pada' => '2026-10-01 00:00:00', 'tampil' => true]);
        PotentialVehicle::factory()->for($older, 'import')->create();
        $this->actingAs($this->employee)->getJson('/api/pemetaan/impor')->assertOk()->assertJsonPath('impor.id', $older->id);

        $started = $this->start(3)->assertCreated()
            ->assertJsonPath('impor.jumlah', 3)
            ->assertJsonPath('impor.diterima', 0)
            ->assertJsonPath('impor.selesai', null)
            ->assertJsonPath('impor.diimporOleh', 'Petugas Pemetaan')
            ->assertJsonPath('impor.tampil', false)
            ->assertJsonPath('barisPerBagian', 1000);
        $id = $started->json('impor.id');

        $this->part($id, 0, [$this->row(), $this->row(['NOPOL' => 'dh 2 b'])])->assertOk()->assertExactJson(['diterima' => 2, 'jumlah' => 3]);
        $this->actingAs($this->employee)->postJson("/api/pemetaan/impor/{$id}/selesai")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['impor' => 'Baru 2 dari 3 baris yang diterima.']);
        $this->actingAs($this->employee)->getJson('/api/pemetaan/impor')->assertJsonPath('impor.id', $older->id);
        $this->part($id, 2, [$this->row(['NOPOL' => 'DH 3 C'])])->assertOk()->assertExactJson(['diterima' => 3, 'jumlah' => 3]);

        $this->actingAs($this->employee)->postJson("/api/pemetaan/impor/{$id}/selesai")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('tanggalPenetapan', '2026-10-16')
            ->assertJsonPath('selesai', '2026-10-16T03:00:00+00:00')
            ->assertJsonPath('tampil', false);
        $this->getJson('/api/public/pemetaan')->assertExactJson(['tersedia' => false]);

        $this->assertModelMissing($older);
        $this->assertSame(['DH1234AB', 'DH2B', 'DH3C'], PotentialVehicle::query()->orderBy('urut')->pluck('nopol')->all());
        $this->assertSame([1, 2, 3], PotentialVehicle::query()->orderBy('urut')->pluck('urut')->all());
        $this->actingAs($this->employee)->getJson('/api/pemetaan/impor')->assertJsonPath('impor.id', $id);
        $this->actingAs($this->employee)->getJson('/api/pemetaan/ringkasan')->assertJsonPath('jumlah.kendaraan', 3)->assertJsonPath('jumlah.kelompok.menunggak', 3);
        $this->assertSame([$id], DB::table('potential_summaries')->distinct()->pluck('potential_import_id')->all());
        $activity = Activity::query()->sole();
        $this->assertSame(['mengimpor', 'Data potensi PKB penetapan 16 Okt 2026 (3 kendaraan)', 'pemetaan'], [$activity->action, $activity->target, $activity->module->value]);
        $this->actingAs($this->employee)->postJson("/api/pemetaan/impor/{$id}/selesai")->assertUnprocessable()->assertJsonValidationErrors(['impor' => 'Impor ini sudah selesai.']);
        $this->part($id, 3, [$this->row()])->assertUnprocessable()->assertJsonValidationErrors(['baris' => 'Impor ini sudah selesai.']);
    }

    public function test_reads_every_column_and_works_out_the_place_wheels_group_due_month_and_amount_due(): void
    {
        $id = $this->start(5)->json('impor.id');

        $this->part($id, 0, [
            $this->row(['KELURAHAN' => 'BAKUNASE II', 'KECAMATAN' => 'OEBOBO']),
            $this->row(['JENIS' => 'SPM R3', 'KEPATUHAN' => 'Taat Membayar Pajak', 'SD NOTICE' => '2027-03-01', 'KTP' => '', 'TELEPON' => '']),
            $this->row(['JENIS' => 'MINIBUS', 'KELURAHAN' => '', 'KECAMATAN' => '', 'KEPATUHAN' => 'Terlambat Membayar 5 Hari', 'SD NOTICE' => '2026-13-01', 'TGL PENETAPAN' => '16-10-2026']),
            $this->row(['JENIS' => '', 'KELURAHAN' => 'KUPANG BARU', 'KEPATUHAN' => 'Pajak Berlaku Sampai Dengan Hari Ini 1', 'BOBOT' => '', 'TAHUN BUAT' => '-', 'CYL' => '']),
            $this->row(['KEPATUHAN' => 'Sesuatu Yang Lain', 'ALAMAT' => str_repeat('A', 300), 'NAMA' => '  Pemilik   Lima ', 'KELURAHAN' => 'oesapa']),
        ])->assertOk();

        $rows = PotentialVehicle::query()->orderBy('urut')->get();
        $first = $rows[0];
        $this->assertSame(
            ['DH1234AB', 'Pemilik Satu', '5371010101010001', '081234567890', 'BAKUNASE II', 'KOTA RAJA', 'OEBOBO', 'r2', 2020, 125, 18500000, 1.05, 245000, 24500, 245000, 58800, 573300, 'menunggak', 10, '2025-10-20', '2029-10-20', '2026-10-16', 'SIAP BAYAR | SIAP BAYAR', 'STPD', 'PETUGAS SATU'],
            [$first->nopol, $first->nama, $first->ktp, $first->telepon, $first->kelurahan, $first->kecamatan, $first->kecamatan_berkas, $first->roda->value, $first->tahun_buat, $first->cyl, $first->njkb, $first->bobot,
                $first->pokok_pajak, $first->denda_berjalan, $first->pokok_tunggak, $first->denda_tunggak, $first->tagihan, $first->kelompok->value, $first->bulan_jatuh_tempo,
                $first->sd_notice->toDateString(), $first->sd_stnk->toDateString(), $first->tanggal_penetapan->toDateString(), $first->dtd_status, $first->dtd_kegiatan, $first->dtd_petugas],
        );
        $this->assertSame(['r3', 'taat', 0, 3, null, null], [$rows[1]->roda->value, $rows[1]->kelompok->value, $rows[1]->tagihan, $rows[1]->bulan_jatuh_tempo, $rows[1]->ktp, $rows[1]->telepon]);
        $this->assertSame(['r4', '', 'terlambat', null, null], [$rows[2]->roda->value, $rows[2]->kecamatan, $rows[2]->kelompok->value, $rows[2]->sd_notice, $rows[2]->tanggal_penetapan]);
        $this->assertSame([null, '', 'KUPANG BARU', 'jatuh-tempo', null, null, null], [$rows[3]->roda, $rows[3]->kecamatan, $rows[3]->kelurahan, $rows[3]->kelompok->value, $rows[3]->bobot, $rows[3]->tahun_buat, $rows[3]->cyl]);
        $this->assertSame(['lainnya', 255, 'Pemilik Lima', 'OESAPA', 'KELAPA LIMA'], [$rows[4]->kelompok->value, mb_strlen($rows[4]->alamat), $rows[4]->nama, $rows[4]->kelurahan, $rows[4]->kecamatan]);
        $this->assertSame('2026-10-16', PotentialImport::query()->sole()->tanggal_penetapan->toDateString());

        $stored = DB::table('potential_vehicles')->orderBy('urut')->first();
        $this->assertNotSame('5371010101010001', $stored->ktp);
        $this->assertStringNotContainsString('081234567890', (string) $stored->telepon);
    }

    public function test_refuses_a_file_that_is_not_the_potential_data(): void
    {
        $this->actingAs($this->employee)->postJson('/api/pemetaan/impor', ['berkas' => 'STS.xls', 'kolom' => ['NO', 'NOPOL'], 'jumlah' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kolom' => 'Berkas ini bukan Data Potensi dari sistem BPAD: susunan kolomnya tidak dikenali.']);
        $this->actingAs($this->employee)->postJson('/api/pemetaan/impor', ['berkas' => 'Data.xls', 'kolom' => ReadPotentialRows::HEADER, 'jumlah' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jumlah' => 'Berkas tidak berisi baris kendaraan.']);
        $this->assertSame(0, PotentialImport::query()->count());
    }

    public function test_refuses_parts_out_of_order_too_long_or_unreadable(): void
    {
        $id = $this->start(3)->json('impor.id');

        $this->part($id, 1, [$this->row()])->assertUnprocessable()->assertJsonValidationErrors(['baris' => 'Bagian berkas tidak berurutan: berikutnya baris 1.']);
        $this->part($id, 0, [$this->row(), $this->row(), $this->row(), $this->row()])->assertUnprocessable()->assertJsonValidationErrors(['baris' => 'Berkas berisi lebih dari 3 baris yang disebutkan.']);
        $this->part($id, 0, [$this->row(), array_slice($this->row(), 0, 40)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['baris' => 'Baris 2 tidak berisi 43 kolom seperti berkas Data Potensi.']);
        $this->part($id, 0, [$this->row(), [...array_slice($this->row(), 0, 42), ['x']]])->assertUnprocessable()->assertJsonValidationErrors(['baris' => 'Baris 2 tidak bisa dibaca.']);
        $this->part($id, 0, array_fill(0, 1001, $this->row()))->assertUnprocessable()->assertJsonValidationErrors(['baris' => 'Satu bagian berisi paling banyak 1000 baris.']);

        $this->assertSame([0, 0], [PotentialVehicle::query()->count(), PotentialImport::query()->sole()->diterima]);
        $this->actingAs($this->employee)->getJson('/api/pemetaan/impor')->assertExactJson(['impor' => null]);
    }

    public function test_starting_an_import_drops_one_left_unfinished(): void
    {
        $left = PotentialImport::factory()->unfinished(5)->create(['diterima' => 1]);
        PotentialVehicle::factory()->for($left, 'import')->create();
        $current = PotentialImport::factory()->create();

        $this->start(2)->assertCreated();

        $this->assertModelMissing($left);
        $this->assertModelExists($current);
        $this->assertSame(0, PotentialVehicle::query()->count());
    }

    public function test_administrators_delete_the_data_shown(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($administrator)->deleteJson('/api/pemetaan/impor')->assertNotFound();
        $current = PotentialImport::factory()->create(['jumlah' => 2]);
        PotentialVehicle::factory()->count(2)->for($current, 'import')->create();

        $this->actingAs($administrator)->deleteJson('/api/pemetaan/impor')->assertNoContent();

        $this->assertModelMissing($current);
        $this->assertSame(0, PotentialVehicle::query()->count());
        $this->assertSame(['menghapus', 'Data potensi PKB penetapan 16 Okt 2026 (2 kendaraan)'], [Activity::query()->sole()->action, Activity::query()->sole()->target]);
        $this->actingAs($administrator)->getJson('/api/pemetaan/impor')->assertExactJson(['impor' => null]);
    }

    public function test_administrators_show_the_data_on_the_portal_and_take_it_off(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->actingAs($administrator)->patchJson('/api/pemetaan/impor', ['tampil' => true])->assertNotFound();
        $current = PotentialImport::factory()->create(['jumlah' => 2]);
        $unfinished = PotentialImport::factory()->unfinished()->create();

        $this->actingAs($this->employee)->patchJson('/api/pemetaan/impor', ['tampil' => true])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menampilkan pemetaan PKB di portal.']);
        $this->actingAs($administrator)->patchJson('/api/pemetaan/impor')->assertUnprocessable()->assertJsonValidationErrors(['tampil' => 'Pilih apakah pemetaan tampil di portal.']);
        $this->actingAs($administrator)->patchJson('/api/pemetaan/impor', ['tampil' => 'ya'])->assertUnprocessable()->assertJsonValidationErrors(['tampil' => 'Pilih apakah pemetaan tampil di portal.']);
        $this->actingAs($administrator)->patchJson('/api/pemetaan/impor', ['tampil' => true])->assertOk()->assertJsonPath('id', $current->id)->assertJsonPath('tampil', true);
        $this->assertFalse($unfinished->fresh()->tampil);
        $this->actingAs($administrator)->patchJson('/api/pemetaan/impor', ['tampil' => true])->assertOk();
        $this->actingAs($administrator)->patchJson('/api/pemetaan/impor', ['tampil' => false])->assertOk()->assertJsonPath('tampil', false);

        $this->assertSame(
            [['menampilkan', 'Data potensi PKB penetapan 16 Okt 2026 (2 kendaraan) di portal'], ['menyembunyikan', 'Data potensi PKB penetapan 16 Okt 2026 (2 kendaraan) dari portal']],
            Activity::query()->oldest('id')->get()->map(fn (Activity $activity): array => [$activity->action, $activity->target])->all(),
        );
    }

    private function start(int $rows): TestResponse
    {
        return $this->actingAs($this->employee)->postJson('/api/pemetaan/impor', ['berkas' => 'Data Potensi.xls', 'kolom' => ReadPotentialRows::HEADER, 'jumlah' => $rows]);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function part(string $id, int $first, array $rows): TestResponse
    {
        return $this->actingAs($this->employee)->postJson("/api/pemetaan/impor/{$id}/baris", ['mulai' => $first, 'baris' => $rows]);
    }

    /**
     * A row of the file, with some columns changed.
     *
     * @param  array<string, string>  $changes
     * @return list<string>
     */
    private function row(array $changes = []): array
    {
        $row = array_combine(ReadPotentialRows::HEADER, [
            'DH 1234 AB', 'Pemilik Satu', '5371010101010001', '081234567890', 'Jl. Melati No. 1', '001', '002', 'OESAPA', 'KELAPA LIMA', 'SEPEDA MOTOR',
            'HONDA', 'VARIO 125', 'MSN123', 'RKA123', '2020', 'PUTIH', 'HITAM', 'PERTALITE', '2026-10-16', 'Kota Kupang', 'PRIBADI', '125', 'C1',
            '2029-10-20', '2025-10-20', '-- TIDAK ADA PERUBAHAN --', '18.500.000', '0', '1.050', '26', '12', '12', '1', '1',
            '245.000', '24.500', '245.000', '58.800', 'Menunggak 1 Tahun', 'Segera Membayar Pajak di SAMSAT Terdekat', 'SIAP BAYAR | SIAP BAYAR', 'STPD', 'PETUGAS SATU',
        ]);

        return array_values(array_replace($row, $changes));
    }
}
