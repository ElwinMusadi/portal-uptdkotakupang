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

class PublicHomepageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_the_markup_text_without_a_time_until_first_saved(): void
    {
        $response = $this->getJson('/api/public/beranda');

        $response->assertOk()
            ->assertJsonPath('hero.judul', 'Semua informasi Samsat.')
            ->assertJsonPath('hero.kataBergilir', ['Pajak', 'STNK', 'Mutasi', 'BBN', 'SAMSAT'])
            ->assertJsonPath('nilai.0', ['judul' => 'Persyaratan jelas.', 'deskripsi' => 'Daftar berkas setiap layanan, lengkap dengan jumlah lembar fotokopi yang perlu dibawa.'])
            ->assertJsonPath('mitra.0', ['singkatan' => 'BP', 'nama' => 'BPAD Provinsi NTT', 'logo' => null])
            ->assertJsonPath('kanal.4', ['kelompok' => 'Pengaduan', 'item' => [['teks' => 'SP4N-LAPOR!', 'tautan' => ''], ['teks' => 'Kotak saran', 'tautan' => ''], ['teks' => 'Ombudsman NTT', 'tautan' => '']]])
            ->assertJsonPath('video', null)
            ->assertJsonPath('diperbarui', null);
    }

    public function test_gives_the_video_by_its_youtube_id_without_the_link(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['video'] = ['url' => 'https://www.youtube.com/watch?v=25sRCp-IFxI', 'label' => 'Tonton alur pelayanan', 'durasi' => '2:14', 'judul' => 'Alur pelayanan PINTU'];
        (new Setting)->forceFill(['name' => Setting::HOMEPAGE, 'value' => $homepage])->save();

        $this->getJson('/api/public/beranda')
            ->assertOk()
            ->assertJsonPath('video', ['id' => '25sRCp-IFxI', 'label' => 'Tonton alur pelayanan', 'durasi' => '2:14', 'judul' => 'Alur pelayanan PINTU']);
    }

    public function test_returns_the_saved_document_with_its_time_and_without_item_ids(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['hero']['judul'] = 'Judul baru.';
        (new Setting)->forceFill(['name' => Setting::HOMEPAGE, 'value' => $homepage])->save();

        $response = $this->getJson('/api/public/beranda');

        $response->assertOk()
            ->assertJsonPath('hero.judul', 'Judul baru.')
            ->assertJsonPath('biaya.catatanNotis', 'Bayar sesuai notis. Tunai di kasir atau non-tunai lewat QRIS.')
            ->assertJsonPath('diperbarui', '2026-10-14T08:00:00+00:00');
        foreach (['nilai', 'mitra', 'kanal'] as $list) {
            $this->assertArrayNotHasKey('id', $response->json("{$list}.0"));
        }
    }

    public function test_returns_partner_logos_and_channel_links_in_the_current_shape(): void
    {
        $homepage = Setting::INITIAL_HOMEPAGE;
        $homepage['mitra'] = [
            ['id' => 'mitra-1', 'singkatan' => 'BP', 'nama' => 'BPAD Provinsi NTT', 'logo' => 'https://bucket.test/beranda/2026/10/logo.png'],
            ['id' => 'mitra-2', 'singkatan' => 'PL', 'nama' => 'Polri · Satlantas'],
        ];
        $homepage['kanal'] = [['id' => 'kanal-1', 'kelompok' => 'Pengaduan', 'item' => ['SP4N-LAPOR!', ['teks' => 'Ombudsman NTT', 'tautan' => 'https://ombudsman.go.id']]]];
        (new Setting)->forceFill(['name' => Setting::HOMEPAGE, 'value' => $homepage])->save();

        $response = $this->getJson('/api/public/beranda');

        $response->assertOk()->assertJsonPath('mitra', [
            ['singkatan' => 'BP', 'nama' => 'BPAD Provinsi NTT', 'logo' => 'https://bucket.test/beranda/2026/10/logo.png'],
            ['singkatan' => 'PL', 'nama' => 'Polri · Satlantas', 'logo' => null],
        ])->assertJsonPath('kanal.0.item', [['teks' => 'SP4N-LAPOR!', 'tautan' => ''], ['teks' => 'Ombudsman NTT', 'tautan' => 'https://ombudsman.go.id']]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/beranda')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/beranda?hero=1')
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

        $this->getJson('/api/public/beranda')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
