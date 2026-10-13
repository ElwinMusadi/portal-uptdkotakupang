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

class PublicVisionMissionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_official_text_without_a_time_until_the_document_is_first_saved(): void
    {
        $response = $this->getJson('/api/public/visi-misi');

        $response->assertOk()
            ->assertJsonPath('visi', 'NTT Maju, Sehat, Cerdas, Sejahtera dan Berkelanjutan.')
            ->assertJsonPath('misi.0', ['teks' => 'Memastikan infrastruktur berkelanjutan demi mewujudkan ekonomi berbasis potensi daerah yang berdaya saing.', 'kataKunci' => 'Maju'])
            ->assertJsonPath('kontribusi.1.judul', 'Akses informasi yang setara.')
            ->assertJsonPath('nilai.6', ['huruf' => 'K', 'judul' => 'Kolaboratif', 'deskripsi' => 'Bersinergi dengan Polri, Jasa Raharja, dan bank mitra.'])
            ->assertJsonPath('maklumat.penandatangan', 'Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang')
            ->assertJsonPath('diperbarui', null);
        $this->assertCount(5, $response->json('misi'));
        $this->assertCount(7, $response->json('nilai'));
    }

    public function test_returns_the_saved_document_without_item_ids_or_the_pledge_file(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        (new Setting)->forceFill(['name' => Setting::VISION_MISSION, 'value' => [
            'sumberVisi' => 'Visi Pemerintah Provinsi NTT',
            'visi' => 'Visi baru.',
            'misi' => [['id' => 'm1', 'teks' => 'Misi pertama.', 'kataKunci' => 'Maju']],
            'kontribusi' => [['id' => 'k1', 'misi' => 'Misi 01 · Maju', 'judul' => 'Tata kelola.', 'deskripsi' => 'Penjelasan.']],
            'nilai' => [['id' => 'n1', 'huruf' => 'B', 'judul' => 'Berorientasi pelayanan', 'deskripsi' => 'Ramah.']],
            'maklumat' => ['teks' => 'Kami sanggup.', 'penandatangan' => 'Kepala UPTD', 'berkas' => null],
        ]])->save();

        $response = $this->getJson('/api/public/visi-misi');

        $response->assertOk()->assertExactJson([
            'sumberVisi' => 'Visi Pemerintah Provinsi NTT',
            'visi' => 'Visi baru.',
            'misi' => [['teks' => 'Misi pertama.', 'kataKunci' => 'Maju']],
            'kontribusi' => [['misi' => 'Misi 01 · Maju', 'judul' => 'Tata kelola.', 'deskripsi' => 'Penjelasan.']],
            'nilai' => [['huruf' => 'B', 'judul' => 'Berorientasi pelayanan', 'deskripsi' => 'Ramah.']],
            'maklumat' => ['teks' => 'Kami sanggup.', 'penandatangan' => 'Kepala UPTD'],
            'diperbarui' => '2026-10-13T08:00:00+00:00',
        ]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/visi-misi')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/visi-misi?misi=1')
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

        $this->getJson('/api/public/visi-misi')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
