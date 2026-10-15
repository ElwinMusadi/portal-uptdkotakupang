<?php

namespace Tests\Feature;

use App\Actions\ReadStsFile;
use App\Enums\DashboardModule;
use App\Models\MobileServiceStop;
use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Support\StsReport;
use Tests\TestCase;

class RevenueImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET', '/api/penerimaan/impor'])]
    #[TestWith(['GET', '/api/penerimaan/loket'])]
    #[TestWith(['GET', '/api/penerimaan/laporan?dari=2026-10-14&sampai=2026-10-14'])]
    #[TestWith(['POST', '/api/penerimaan/impor'])]
    #[TestWith(['POST', '/api/penerimaan/impor/pratinjau'])]
    #[TestWith(['POST', '/api/penerimaan/impor/hapus'])]
    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized();

        $this->actingAs(User::factory()->withModules(DashboardModule::Statistik)->create())
            ->json($method, $uri)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Laporan Penerimaan belum ditugaskan kepada Anda.']);
    }

    public function test_lists_the_office_the_three_units_and_each_outlet_and_temporary_counter_once(): void
    {
        MobileServiceStop::factory()->outlet('Samsat Corner', [1, 2, 3])->create();
        MobileServiceStop::factory()->outlet('Samsat Corner', [6])->create(['mulai' => '10:00', 'selesai' => '14:00']);
        MobileServiceStop::factory()->outlet('Mall Pelayanan Publik')->create();
        MobileServiceStop::factory()->counter('2026-10-19', '2026-10-23', 'Pameran Pembangunan')->inactive()->create();
        MobileServiceStop::factory()->on('2026-10-19', '02')->create();

        $response = $this->actingAs($this->revenueEmployee())->getJson('/api/penerimaan/loket');

        $response->assertOk()->assertExactJson([
            ['kunci' => 'kantor', 'nama' => 'Loket SAMSAT', 'jenis' => 'kantor'],
            ['kunci' => 'keliling-01', 'nama' => 'Samsat Keliling 01', 'jenis' => 'keliling'],
            ['kunci' => 'keliling-02', 'nama' => 'Samsat Keliling 02', 'jenis' => 'keliling'],
            ['kunci' => 'keliling-03', 'nama' => 'Samsat Keliling 03', 'jenis' => 'keliling'],
            ['kunci' => 'gerai:mall-pelayanan-publik', 'nama' => 'Mall Pelayanan Publik', 'jenis' => 'gerai'],
            ['kunci' => 'gerai:samsat-corner', 'nama' => 'Samsat Corner', 'jenis' => 'gerai'],
            ['kunci' => 'sementara:pameran-pembangunan', 'nama' => 'Pameran Pembangunan', 'jenis' => 'sementara'],
        ]);
    }

    public function test_previews_a_file_without_saving_it(): void
    {
        $file = StsReport::file([
            StsReport::row(['no' => '1', 'jenis_daftar' => 'BARU', 'nopol' => 'DH1001AA', 'bbn' => '9000000', 'opsen_bbn' => '5940000']),
            StsReport::row(['no' => '2', 'nopol' => 'DH1002AA', 'tanggal' => '2026-10-13']),
            StsReport::row(['no' => '3', 'nopol' => 'DH1003AA']),
        ]);

        $response = $this->actingAs($this->revenueEmployee())->post('/api/penerimaan/impor/pratinjau', ['berkas' => $file, 'loket' => 'kantor'], ['Accept' => 'application/json']);

        $response->assertOk()->assertExactJson([
            'loket' => ['kunci' => 'kantor', 'nama' => 'Loket SAMSAT', 'jenis' => 'kantor'],
            'namaBerkas' => 'STS_Harian_Kota_Kupang_2026-10-14.xls',
            'berkas' => [
                'tanggalAwal' => '2026-10-13',
                'tanggalAkhir' => '2026-10-14',
                'transaksi' => 3,
                'jumlah' => 16434000,
                'jenisDaftar' => [
                    ['nilai' => 'BARU', 'transaksi' => 1, 'jumlah' => 15438000],
                    ['nilai' => 'Teliti Ulang / Pengesahan', 'transaksi' => 2, 'jumlah' => 996000],
                ],
            ],
            'baru' => [
                'tanggalAwal' => '2026-10-13',
                'tanggalAkhir' => '2026-10-14',
                'transaksi' => 3,
                'jumlah' => 16434000,
                'jenisDaftar' => [
                    ['nilai' => 'BARU', 'transaksi' => 1, 'jumlah' => 15438000],
                    ['nilai' => 'Teliti Ulang / Pengesahan', 'transaksi' => 2, 'jumlah' => 996000],
                ],
            ],
            'dilewati' => 0,
        ]);
        $this->assertDatabaseCount('revenue_imports', 0);
        $this->assertDatabaseCount('revenue_transactions', 0);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_imports_the_transactions_without_the_owners_identity(): void
    {
        $employee = $this->revenueEmployee();
        $file = StsReport::file([
            StsReport::row(['no' => '1', 'nopol' => 'DH1001AA', 'nama' => 'NAMA PEMILIK SATU', 'rt' => '011', 'rw' => '022']),
            StsReport::row([
                'no' => '2', 'jenis_daftar' => 'PENDAFTARAN ULANG 5 TAHUN', 'nopol' => 'DH 1002 AB', 'nama' => 'NAMA PEMILIK DUA', 'jns_kend' => 'MINIBUS',
                'merk' => 'TOYOTA', 'plat' => 'MERAH', 'jenis_kendaraan' => 'MINIBUS PEMERINTAH DAERAH', 'upt_asal' => 'Kab. Kupang',
                'kelurahan' => 'Oesao', 'kecamatan' => 'KUPANG TIMUR', 'pkb' => '1200000', 'd_pkb' => '24000', 'd_bbn' => '1000', 'tunggakan' => '1100000',
                'denda_tunggakan' => '2000', 'opsen_pkb' => '792000', 'opsen_dpkb' => '15840', 'opsen_dbbn' => '660', 'opsen_tpkb' => '726000',
                'opsen_tdpkb' => '1320', 'tahun_tunggakan' => '2',
            ]),
        ]);

        $response = $this->actingAs($employee)->post('/api/penerimaan/impor', ['berkas' => $file, 'loket' => 'kantor'], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('loket', 'kantor')
            ->assertJsonPath('namaLoket', 'Loket SAMSAT')
            ->assertJsonPath('namaBerkas', 'STS_Harian_Kota_Kupang_2026-10-14.xls')
            ->assertJsonPath('tanggalAwal', '2026-10-14')
            ->assertJsonPath('tanggalAkhir', '2026-10-14')
            ->assertJsonPath('jumlahTransaksi', 2)
            ->assertJsonPath('jumlahDilewati', 0)
            ->assertJsonPath('total', 498000 + 3862820)
            ->assertJsonPath('tampil', false)
            ->assertJsonPath('oleh', $employee->name);
        $this->assertDatabaseHas('revenue_transactions', [
            'revenue_import_id' => $response->json('id'),
            'loket' => 'kantor',
            'tanggal' => '2026-10-14',
            'jenis_daftar' => 'PENDAFTARAN ULANG 5 TAHUN',
            'jenis_kendaraan' => 'MINIBUS',
            'kepemilikan' => 'pemerintah',
            'merk' => 'TOYOTA',
            'warna_plat' => 'MERAH',
            'upt_asal' => 'Kab. Kupang',
            'kecamatan' => 'KUPANG TIMUR',
            'kelurahan' => 'Oesao',
            'tahun_tunggakan' => 2,
            'pkb' => 1200000,
            'bbn' => 0,
            'denda_pkb' => 24000,
            'denda_bbn' => 1000,
            'tunggakan' => 1100000,
            'denda_tunggakan' => 2000,
            'opsen_pkb' => 792000,
            'opsen_bbn' => 0,
            'opsen_denda_pkb' => 15840,
            'opsen_denda_bbn' => 660,
            'opsen_tunggakan' => 726000,
            'opsen_denda_tunggakan' => 1320,
            'jumlah' => 3862820,
            'sidik' => ReadStsFile::fingerprint('DH1002AB', '2026-10-14', 'PENDAFTARAN ULANG 5 TAHUN', 3862820),
        ]);
        $stored = json_encode(DB::table('revenue_transactions')->get());
        foreach (['NAMA PEMILIK', 'DH1001AA', 'DH1002AB', 'DH 1002 AB', 'A001', 'TIPE UJI', '18000000', '2027-10-14', '011', '022'] as $secret) {
            $this->assertStringNotContainsString($secret, $stored);
        }
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'penerimaan',
            'action' => 'mengimpor',
            'target' => 'STS Loket SAMSAT 2026-10-14 · 2 transaksi',
        ]);
    }

    public function test_skips_transactions_saved_before_or_listed_twice(): void
    {
        $employee = $this->revenueEmployee();
        $first = StsReport::row(['no' => '1', 'nopol' => 'DH1001AA']);
        $this->actingAs($employee)->post('/api/penerimaan/impor', ['berkas' => StsReport::file([$first]), 'loket' => 'kantor'], ['Accept' => 'application/json'])->assertCreated();
        // The same transaction in a monthly file, numbered differently, and one listed twice
        $rows = [
            [...$first, 'no' => '7'],
            StsReport::row(['no' => '8', 'nopol' => 'DH1002AA', 'jenis_daftar' => 'MUTASI KELUAR LUAR PROPINSI', 'pkb' => '0', 'opsen_pkb' => '0', 'jumlah' => '']),
            StsReport::row(['no' => '9', 'nopol' => 'DH1002AA', 'jenis_daftar' => 'MUTASI KELUAR LUAR PROPINSI', 'pkb' => '0', 'opsen_pkb' => '0', 'jumlah' => '']),
            StsReport::row(['no' => '10', 'nopol' => 'DH1001AA', 'tanggal' => '2026-10-15']),
        ];

        $this->actingAs($employee)->post('/api/penerimaan/impor/pratinjau', ['berkas' => StsReport::file($rows), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('berkas.transaksi', 4)
            ->assertJsonPath('baru.transaksi', 2)
            ->assertJsonPath('baru.jumlah', 498000)
            ->assertJsonPath('dilewati', 2);
        $this->actingAs($employee)->post('/api/penerimaan/impor', ['berkas' => StsReport::file($rows), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('jumlahTransaksi', 2)
            ->assertJsonPath('jumlahDilewati', 2)
            ->assertJsonPath('total', 498000)
            ->assertJsonPath('tanggalAwal', '2026-10-14')
            ->assertJsonPath('tanggalAkhir', '2026-10-15');
        $this->assertDatabaseCount('revenue_transactions', 3);
    }

    public function test_recognises_a_transaction_whatever_the_case_of_its_registration_type(): void
    {
        $employee = $this->revenueEmployee();
        $this->actingAs($employee)->post('/api/penerimaan/impor', ['berkas' => StsReport::file([StsReport::row()]), 'loket' => 'kantor'], ['Accept' => 'application/json'])->assertCreated();

        $this->actingAs($employee)
            ->post('/api/penerimaan/impor/pratinjau', ['berkas' => StsReport::file([StsReport::row(['jenis_daftar' => 'TELITI ULANG / PENGESAHAN'])]), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('baru.transaksi', 0)
            ->assertJsonPath('dilewati', 1);
    }

    public function test_keys_the_fingerprint_with_the_application_key(): void
    {
        $fingerprint = ReadStsFile::fingerprint('DH1234XY', '2026-10-14', 'BARU', 498000);

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->assertNotSame($fingerprint, ReadStsFile::fingerprint('DH1234XY', '2026-10-14', 'BARU', 498000));
    }

    public function test_accepts_texts_as_long_as_their_columns(): void
    {
        $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => StsReport::file([StsReport::row(['merk' => str_repeat('M', 40), 'kelurahan' => str_repeat('K', 80)])]), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertDatabaseHas('revenue_transactions', ['merk' => str_repeat('M', 40), 'kelurahan' => str_repeat('K', 80)]);
    }

    public function test_refuses_a_file_with_nothing_new(): void
    {
        $employee = $this->revenueEmployee();
        $file = fn (): UploadedFile => StsReport::file([StsReport::row()]);
        $this->actingAs($employee)->post('/api/penerimaan/impor', ['berkas' => $file(), 'loket' => 'kantor'], ['Accept' => 'application/json'])->assertCreated();

        $this->actingAs($employee)->post('/api/penerimaan/impor', ['berkas' => $file(), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Semua transaksi di berkas ini sudah pernah diimpor.']);
        $this->assertDatabaseCount('revenue_imports', 1);
        $this->assertDatabaseCount('activities', 1);
    }

    #[TestWith(['SEPEDA MOTOR PRIBADI', 'pribadi'])]
    #[TestWith(['TRUCK UMUM', 'umum'])]
    #[TestWith(['MINIBUS PEMERINTAH DAERAH', 'pemerintah'])]
    #[TestWith(['SEPEDA MOTOR TNI/POLRI', 'lainnya'])]
    public function test_reads_the_ownership_from_the_full_vehicle_kind(string $kind, string $ownership): void
    {
        $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => StsReport::file([StsReport::row(['jenis_kendaraan' => $kind])]), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertSame($ownership, RevenueTransaction::query()->value('kepemilikan'));
    }

    public function test_imports_for_a_unit_with_its_name_at_the_time(): void
    {
        $response = $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => StsReport::file([StsReport::row()]), 'loket' => 'keliling-02'], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('loket', 'keliling-02')->assertJsonPath('namaLoket', 'Samsat Keliling 02');
        $this->assertSame('keliling-02', RevenueTransaction::query()->value('loket'));
    }

    #[TestWith([null, 'kantor', 'berkas', 'Pilih berkas STS yang akan diimpor.'])]
    #[TestWith(['pdf', 'kantor', 'berkas', 'Pilih berkas STS berformat .xls hasil ekspor sistem BPAD.'])]
    #[TestWith(['besar', 'kantor', 'berkas', 'Ukuran berkas maksimal 10 MB.'])]
    #[TestWith(['bukan-sts', 'kantor', 'berkas', 'Berkas ini bukan laporan STS dari sistem BPAD: susunan kolomnya tidak dikenali.'])]
    #[TestWith(['judul-lain', 'kantor', 'berkas', 'Berkas ini bukan laporan STS dari sistem BPAD: susunan kolomnya tidak dikenali.'])]
    #[TestWith(['kosong', 'kantor', 'berkas', 'Berkas tidak berisi transaksi.'])]
    #[TestWith(['sts', null, 'loket', 'Pilih loket asal berkas ini.'])]
    #[TestWith(['sts', 'keliling-04', 'loket', 'Loket tidak dikenal.'])]
    public function test_validates_the_file_and_the_counter(?string $file, ?string $counter, string $field, string $message): void
    {
        $upload = match ($file) {
            null => null,
            'pdf' => UploadedFile::fake()->createWithContent('STS.pdf', StsReport::html([StsReport::row()])),
            'besar' => UploadedFile::fake()->create('STS.xls', 10241),
            'bukan-sts' => UploadedFile::fake()->createWithContent('STS.xls', '<table><tr><td>Nama</td></tr><tr><td>Isi</td></tr></table>'),
            'judul-lain' => UploadedFile::fake()->createWithContent('STS.xls', str_replace('<td>JUMLAH</td>', '<td>TOTAL</td>', StsReport::html([StsReport::row()]))),
            'kosong' => StsReport::file([]),
            'sts' => StsReport::file([StsReport::row()]),
        };

        $response = $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', array_filter(['berkas' => $upload, 'loket' => $counter]), ['Accept' => 'application/json']);

        $response->assertUnprocessable()->assertOnlyJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('revenue_imports', 0);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    #[TestWith([['jumlah' => '500000'], 'Baris 4: jumlah tidak sama dengan rinciannya.'])]
    #[TestWith([['tanggal' => '14-10-2026'], 'Baris 4: tanggal validasi "14-10-2026" tidak dikenali.'])]
    #[TestWith([['tanggal' => '2026-02-30'], 'Baris 4: tanggal validasi "2026-02-30" tidak dikenali.'])]
    #[TestWith([['pkb' => '300000.50'], 'Baris 4: nilai pkb "300000.50" bukan angka rupiah bulat.'])]
    #[TestWith([['opsen_pkb' => '-198000'], 'Baris 4: nilai opsen_pkb "-198000" bukan angka rupiah bulat.'])]
    #[TestWith([['tahun_tunggakan' => 'dua'], 'Baris 4: nilai tahun tunggakan "dua" bukan angka bulat.'])]
    #[TestWith([['merk' => 'MERK YANG NAMANYA SANGAT PANJANG SEKALI MELEBIHI'], 'Baris 4: isi kolom merk lebih dari 40 karakter.'])]
    public function test_names_the_row_that_cannot_be_read(array $overrides, string $message): void
    {
        $file = StsReport::file([StsReport::row(['no' => '1', 'nopol' => 'DH1001AA']), StsReport::row(['no' => '2', 'nopol' => 'DH1002AA', ...$overrides])]);

        $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => $file, 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['berkas' => $message]);
        $this->assertDatabaseCount('revenue_transactions', 0);
    }

    #[TestWith(['', 35])]
    #[TestWith(['<td>OEBOBO</td><td>LEBIH</td>', 37])]
    public function test_rejects_a_row_with_missing_or_extra_cells(string $districtCells, int $cells): void
    {
        $html = str_replace('<td>OEBOBO</td>', $districtCells, StsReport::html([StsReport::row()]));

        $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => UploadedFile::fake()->createWithContent('STS.xls', $html), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['berkas' => "Baris 3: ada {$cells} kolom, seharusnya 36."]);
    }

    public function test_skips_blank_rows_and_marks_empty_columns(): void
    {
        $html = str_replace(
            '</table>',
            '<tr></tr><tr>'.str_repeat('<td> </td>', 36).'</tr></table>',
            StsReport::html([StsReport::row(['merk' => '', 'kelurahan' => '', 'tahun_tunggakan' => ''])]),
        );

        $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => UploadedFile::fake()->createWithContent('STS.xls', $html), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('jumlahTransaksi', 1);
        $this->assertDatabaseHas('revenue_transactions', ['merk' => '-', 'kelurahan' => '-', 'kecamatan' => 'OEBOBO', 'tahun_tunggakan' => 0]);
    }

    public function test_reads_a_file_saved_in_windows_1252(): void
    {
        $html = mb_convert_encoding(StsReport::html([StsReport::row(['merk' => 'CITROËN'])]), 'Windows-1252', 'UTF-8');

        $this->actingAs($this->revenueEmployee())
            ->post('/api/penerimaan/impor', ['berkas' => UploadedFile::fake()->createWithContent('STS.xls', $html), 'loket' => 'kantor'], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertDatabaseHas('revenue_transactions', ['merk' => 'CITROËN']);
    }

    public function test_lists_the_imports_latest_first(): void
    {
        $employee = $this->revenueEmployee();
        $this->travelTo(now()->subHour());
        $older = RevenueImport::factory()->create(['tanggal_awal' => '2026-09-01', 'tanggal_akhir' => '2026-09-30', 'user_id' => $employee->id]);
        $this->travelBack();
        $newer = RevenueImport::factory()->counter('keliling-01', 'Samsat Keliling 01')->create(['jumlah_transaksi' => 12, 'total' => 5400000]);

        $response = $this->actingAs($employee)->getJson('/api/penerimaan/impor');

        $response->assertOk()
            ->assertJsonPath('*.id', [$newer->id, $older->id])
            ->assertJsonPath('0.namaLoket', 'Samsat Keliling 01')
            ->assertJsonPath('0.jumlahTransaksi', 12)
            ->assertJsonPath('0.total', 5400000)
            ->assertJsonPath('0.oleh', null)
            ->assertJsonPath('1.tanggalAkhir', '2026-09-30')
            ->assertJsonPath('1.oleh', $employee->name);
    }

    public function test_deletes_an_import_with_its_transactions(): void
    {
        $employee = $this->revenueEmployee();
        $import = RevenueImport::factory()->create();
        RevenueTransaction::factory()->count(2)->for($import)->create();
        $kept = RevenueTransaction::factory()->create();

        $this->actingAs($employee)->postJson('/api/penerimaan/impor/hapus', ['ids' => [$import->id]])->assertNoContent();

        $this->assertModelMissing($import);
        $this->assertSame([$kept->id], RevenueTransaction::query()->pluck('id')->all());
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'penerimaan', 'action' => 'menghapus', 'target' => 'STS Loket SAMSAT 2026-10-14']);
    }

    public function test_lets_only_administrators_show_an_import_on_the_portal(): void
    {
        $import = RevenueImport::factory()->create();

        $this->patchJson("/api/penerimaan/impor/{$import->id}", ['tampil' => true])->assertUnauthorized();
        $this->actingAs($this->revenueEmployee())
            ->patchJson("/api/penerimaan/impor/{$import->id}", ['tampil' => true])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menampilkan laporan penerimaan di portal.']);
        $this->assertFalse($import->fresh()->tampil);
    }

    public function test_shows_an_import_on_the_portal_and_takes_it_off(): void
    {
        $administrator = User::factory()->administrator()->create();
        $import = RevenueImport::factory()->create();
        $address = "/api/penerimaan/impor/{$import->id}";

        $this->actingAs($administrator)->patchJson($address, ['tampil' => true])
            ->assertOk()
            ->assertJsonPath('id', $import->id)
            ->assertJsonPath('tampil', true);
        $this->assertTrue($import->fresh()->tampil);
        $this->assertDatabaseHas('activities', [
            'user_id' => $administrator->id, 'module' => 'penerimaan', 'action' => 'menampilkan', 'target' => 'STS Loket SAMSAT 2026-10-14 di portal',
        ]);

        $this->actingAs($administrator)->patchJson($address, ['tampil' => true])->assertOk();
        $this->assertDatabaseCount('activities', 1);

        $this->actingAs($administrator)->patchJson($address, ['tampil' => false])->assertOk()->assertJsonPath('tampil', false);
        $this->assertFalse($import->fresh()->tampil);
        $this->assertDatabaseHas('activities', ['action' => 'menyembunyikan', 'target' => 'STS Loket SAMSAT 2026-10-14 dari portal']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[TestWith([[]])]
    #[TestWith([['tampil' => 'ya']])]
    public function test_asks_whether_the_import_shows_on_the_portal(array $data): void
    {
        $import = RevenueImport::factory()->create();

        $this->actingAs(User::factory()->administrator()->create())
            ->patchJson("/api/penerimaan/impor/{$import->id}", $data)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['tampil' => 'Pilih apakah impor ini tampil di portal.']);
    }

    public function test_answers_404_for_an_import_that_does_not_exist(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->patchJson('/api/penerimaan/impor/01m4z24mbwh6zsh2g2y5t3nfpr', ['tampil' => true])
            ->assertNotFound();
    }

    private function revenueEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Penerimaan)->create();
    }
}
