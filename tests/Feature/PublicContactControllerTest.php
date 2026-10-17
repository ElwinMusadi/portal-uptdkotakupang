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

class PublicContactControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_empty_details_without_a_time_until_they_are_first_saved(): void
    {
        $response = $this->getJson('/api/public/kontak');

        $response->assertOk()->assertExactJson([
            'namaKantor' => '',
            'alamat' => '',
            'kota' => '',
            'provinsi' => '',
            'telepon' => '',
            'whatsapp' => '',
            'whatsappCatatan' => '',
            'email' => '',
            'mediaSosial' => [],
            'mapsUrl' => '',
            'titikPeta' => '-10.172878287411304, 123.60848636621598',
            'catatanLokasi' => '',
            'janjiBalasan' => '',
            'kanalPengaduan' => [],
            'diperbarui' => null,
        ]);
    }

    public function test_returns_the_saved_details_without_the_items_ids(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        (new Setting)->forceFill(['name' => Setting::CONTACT, 'value' => [
            'namaKantor' => 'Kantor Bersama Samsat Kota Kupang',
            'alamat' => 'Jl. Soeharto No. 1',
            'kota' => 'Kota Kupang',
            'provinsi' => 'Nusa Tenggara Timur',
            'telepon' => '(0380) 821 234',
            'whatsapp' => '+62 812-3456-7890',
            'whatsappCatatan' => 'Hanya pesan teks, hari kerja',
            'email' => 'uptd.kotakupang@example.go.id',
            'mediaSosial' => [['id' => 'm1', 'platform' => 'Instagram', 'akun' => '@samsatkotakupang', 'url' => '']],
            'mapsUrl' => 'https://maps.google.com/?q=Samsat+Kota+Kupang',
            'catatanLokasi' => 'Parkir tersedia di halaman kantor',
            'janjiBalasan' => 'Kami membalas paling lambat tiga hari kerja.',
            'kanalPengaduan' => [['id' => 'k1', 'nama' => 'SP4N-LAPOR!', 'deskripsi' => 'Sistem pengaduan nasional.', 'tautanLabel' => 'Buka lapor.go.id', 'tautanUrl' => 'https://www.lapor.go.id']],
        ]])->save();

        $response = $this->getJson('/api/public/kontak');

        $response->assertOk()
            ->assertJsonPath('namaKantor', 'Kantor Bersama Samsat Kota Kupang')
            ->assertJsonPath('telepon', '(0380) 821 234')
            ->assertJsonPath('mediaSosial', [['platform' => 'Instagram', 'akun' => '@samsatkotakupang', 'url' => '']])
            ->assertJsonPath('kanalPengaduan', [['nama' => 'SP4N-LAPOR!', 'deskripsi' => 'Sistem pengaduan nasional.', 'tautanLabel' => 'Buka lapor.go.id', 'tautanUrl' => 'https://www.lapor.go.id']])
            ->assertJsonPath('titikPeta', '-10.172878287411304, 123.60848636621598')
            ->assertJsonPath('diperbarui', '2026-10-13T08:00:00+00:00');
    }

    public function test_returns_no_map_point_once_it_is_saved_empty(): void
    {
        (new Setting)->forceFill(['name' => Setting::CONTACT, 'value' => ['titikPeta' => ''] + Setting::UNSAVED_CONTACT])->save();

        $this->getJson('/api/public/kontak')->assertOk()->assertJsonPath('titikPeta', '');
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/kontak')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/kontak?email=1')
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

        $this->getJson('/api/public/kontak')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
