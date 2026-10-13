<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicServiceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_the_initial_services_as_the_layanan_page_showed_them(): void
    {
        $response = $this->getJson('/api/public/layanan');

        $response->assertOk()
            ->assertJsonPath('*.slug', ['pajak-tahunan', 'lima-tahun', 'mutasi-tetap', 'mutasi-bbn', 'duplikat', 'badan-hukum'])
            ->assertJsonPath('0', [
                'nama' => 'Pajak tahunan',
                'slug' => 'pajak-tahunan',
                'ringkasan' => 'Pembayaran PKB dan pengesahan STNK setiap tahun.',
                'loket' => 'Loket 1',
                'jenis' => 'layanan',
                'cekFisik' => false,
                'nonTunai' => true,
                'diwakilkan' => true,
                'label' => [],
                'persyaratan' => [
                    ['dokumen' => 'Fotokopi STNK dan notis pajak (lampirkan asli)', 'jumlah' => '2 lembar'],
                    ['dokumen' => 'Fotokopi BPKB halaman 2–3', 'jumlah' => '2 lembar'],
                    ['dokumen' => 'Fotokopi KTP pemilik (sesuai alamat di STNK dan BPKB)', 'jumlah' => '2 lembar'],
                    ['dokumen' => 'Map', 'jumlah' => '1 buah'],
                ],
                'catatan' => '',
                'unggulan' => true,
                'terbitPada' => $response->json('0.terbitPada'),
            ])
            ->assertJsonPath('1.nama', "Perpanjangan STNK 5\u{00A0}tahun")
            ->assertJsonPath('3.catatan', 'Kendaraan sudah berada di tempat baru? Ajukan permohonan cek fisik bantuan di Samsat tujuan.');
        $this->assertNotNull($response->json('0.terbitPada'));
        $this->assertArrayNotHasKey('id', $response->json('0'));
        $this->assertArrayNotHasKey('riwayat', $response->json('0'));
    }

    public function test_lists_only_published_services_in_their_order(): void
    {
        Service::query()->delete();
        foreach ([WorkflowStatus::Draft, WorkflowStatus::UnderReview, WorkflowStatus::AwaitingApproval, WorkflowStatus::Returned, WorkflowStatus::Archived] as $status) {
            Service::factory()->withStatus($status)->create();
        }
        Service::factory()->withStatus(WorkflowStatus::Published)->create(['slug' => 'kedua', 'urutan' => 2]);
        Service::factory()->withStatus(WorkflowStatus::Published)->create(['slug' => 'pertama', 'urutan' => 1]);

        $this->getJson('/api/public/layanan')->assertOk()->assertJsonPath('*.slug', ['pertama', 'kedua']);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/layanan')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/layanan?status=draf')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('services')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bservices\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/layanan')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
