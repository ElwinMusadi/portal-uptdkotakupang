<?php

namespace Tests\Feature;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_initial_text_without_a_time_until_the_document_is_first_saved(): void
    {
        $response = $this->getJson('/api/public/profil');

        $response->assertOk()
            ->assertJsonPath('judul', 'Melayani pajak daerah.')
            ->assertJsonPath('identitas.3', ['label' => 'Dasar organisasi', 'nilai' => 'Peraturan Gubernur NTT Nomor 96 Tahun 2023'])
            ->assertJsonPath('identitas.5', ['label' => 'Wilayah kerja', 'nilai' => 'Kota Kupang · 6 kecamatan, 51 kelurahan, 159 km²'])
            ->assertJsonPath('tugasFungsi.3.judul', "Pelayanan Samsat: pengesahan STNK, perpanjangan 5\u{00A0}tahun, mutasi, dan balik nama.")
            ->assertJsonPath('sambutan.nama', 'Remmy Christian Pah, S.STP, M.Si')
            ->assertJsonPath('foto', null)
            ->assertJsonPath('sambutan.foto', null)
            ->assertJsonPath('diperbarui', null);
    }

    public function test_returns_the_saved_document_without_item_ids(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        (new Setting)->forceFill(['name' => Setting::PROFILE, 'value' => [
            'judul' => 'Judul.',
            'lanjutan' => 'Lanjutan.',
            'pengantar' => 'Pengantar.',
            'foto' => 'https://bucket.example/profil/2026/10/gedung.jpg',
            'keteranganFoto' => 'Keterangan.',
            'kedudukan' => ['judul' => 'Kedudukan.', 'lanjutan' => '', 'deskripsi' => 'Deskripsi.'],
            'identitas' => [['id' => 'i1', 'label' => 'Nama unit', 'nilai' => 'UPTD']],
            'tugasFungsi' => [['id' => 't1', 'judul' => 'Tugas.', 'deskripsi' => '', 'kataKunci' => 'Data']],
            'sambutan' => ['kutipan' => 'Sambutan.', 'nama' => 'Nama Kepala', 'jabatan' => 'Kepala UPTD', 'foto' => null],
        ]])->save();

        $response = $this->getJson('/api/public/profil');

        $response->assertOk()->assertExactJson([
            'judul' => 'Judul.',
            'lanjutan' => 'Lanjutan.',
            'pengantar' => 'Pengantar.',
            'foto' => 'https://bucket.example/profil/2026/10/gedung.jpg',
            'keteranganFoto' => 'Keterangan.',
            'kedudukan' => ['judul' => 'Kedudukan.', 'lanjutan' => '', 'deskripsi' => 'Deskripsi.'],
            'identitas' => [['label' => 'Nama unit', 'nilai' => 'UPTD']],
            'tugasFungsi' => [['judul' => 'Tugas.', 'deskripsi' => '', 'kataKunci' => 'Data']],
            'sambutan' => ['kutipan' => 'Sambutan.', 'nama' => 'Nama Kepala', 'jabatan' => 'Kepala UPTD', 'foto' => null],
            'diperbarui' => '2026-10-13T08:00:00+00:00',
        ]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/profil')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/profil?foto=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('settings')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bsettings\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/profil')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
