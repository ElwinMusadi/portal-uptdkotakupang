<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\OrganizationUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicOrganizationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_the_initial_chart_as_a_tree_with_the_partners(): void
    {
        $response = $this->getJson('/api/public/struktur');

        $response->assertOk()
            ->assertJsonCount(1, 'unit')
            ->assertJsonPath('unit.0.nama', 'Kepala UPTD')
            ->assertJsonPath('unit.0.pejabat', 'Remmy Christian Pah, S.STP, M.Si')
            ->assertJsonPath('unit.0.bawahan.*.nama', ['Subbagian Tata Usaha', 'Seksi Penetapan dan Penagihan', 'Seksi Verifikasi', 'Kelompok Jabatan Fungsional'])
            ->assertJsonPath('unit.0.bawahan.1', [
                'nama' => 'Seksi Penetapan dan Penagihan',
                'singkatan' => 'S1',
                'jenis' => 'seksi',
                'jabatan' => 'Kepala Seksi',
                'pejabat' => '',
                'foto' => null,
                'anggota' => ['Petugas penetapan & korektor', 'Petugas penagihan & Samsat Keliling', 'Kasir & bendahara penerimaan', 'Petugas pembukuan & pelaporan'],
                'uraianTugas' => 'Merencanakan dan melaksanakan kegiatan penetapan dan penagihan pajak serta pendapatan lain-lain, termasuk pemberian surat keterangan terdaftar untuk kendaraan bermotor.',
                'catatan' => '',
                'bawahan' => [],
            ])
            ->assertJsonPath('mitra', [
                ['nama' => 'Polri · Satlantas', 'singkatan' => 'PL', 'foto' => null, 'uraianTugas' => 'Registrasi dan identifikasi kendaraan bermotor, termasuk pengesahan STNK.', 'catatan' => 'Mitra satu atap Samsat · garis koordinasi, bukan garis komando.'],
                ['nama' => 'PT Jasa Raharja', 'singkatan' => 'JR', 'foto' => null, 'uraianTugas' => 'Pengelolaan Sumbangan Wajib Dana Kecelakaan Lalu Lintas Jalan (SWDKLLJ).', 'catatan' => 'Mitra satu atap Samsat · garis koordinasi, bukan garis komando.'],
            ]);
    }

    public function test_shows_a_partners_photo_as_its_logo(): void
    {
        OrganizationUnit::query()->where('singkatan', 'JR')->update(['foto' => 'https://bucket.test/unit/2026/10/jasa-raharja.png']);

        $response = $this->getJson('/api/public/struktur');

        $response->assertOk()
            ->assertJsonPath('mitra.0.foto', null)
            ->assertJsonPath('mitra.1.foto', 'https://bucket.test/unit/2026/10/jasa-raharja.png');
    }

    public function test_never_shows_the_officials_nip_or_ids_and_follows_the_order(): void
    {
        $section = OrganizationUnit::query()->where('singkatan', 'S2')->firstOrFail();
        $section->update(['nip' => '198501012010011001', 'pejabat' => 'Maria Bria, S.E.']);
        OrganizationUnit::factory()->under($section)->create(['nama' => 'Tim Data', 'urutan' => 1]);
        OrganizationUnit::query()->where('singkatan', 'S1')->update(['urutan' => 99]);
        OrganizationUnit::factory()->create(['nama' => 'Unit lepas', 'induk' => '01m4zzzzzzzzzzzzzzzzzzzzzz']);

        $response = $this->getJson('/api/public/struktur');

        $response->assertOk()
            ->assertJsonPath('unit.*.nama', ['Kepala UPTD', 'Unit lepas'])
            ->assertJsonPath('unit.0.bawahan.*.nama', ['Subbagian Tata Usaha', 'Seksi Verifikasi', 'Kelompok Jabatan Fungsional', 'Seksi Penetapan dan Penagihan'])
            ->assertJsonPath('unit.0.bawahan.1.pejabat', 'Maria Bria, S.E.')
            ->assertJsonPath('unit.0.bawahan.1.bawahan.0.nama', 'Tim Data');
        $this->assertStringNotContainsString('198501012010011001', $response->getContent());
        $this->assertStringNotContainsString($section->id, $response->getContent());
        $this->assertStringNotContainsString('"nip"', $response->getContent());
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/struktur')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/struktur?unit=S1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('organization_units')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\borganization_units\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/struktur')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
