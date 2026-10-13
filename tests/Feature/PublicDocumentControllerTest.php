<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\Document;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicDocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_nothing_while_the_initial_documents_are_drafts(): void
    {
        $this->getJson('/api/public/dokumen')->assertOk()->assertExactJson([]);
    }

    public function test_lists_only_published_documents_by_category_and_then_in_order(): void
    {
        Document::query()->delete();
        foreach ([WorkflowStatus::Draft, WorkflowStatus::UnderReview, WorkflowStatus::AwaitingApproval, WorkflowStatus::Returned, WorkflowStatus::Archived] as $status) {
            Document::factory()->withStatus($status)->create();
        }
        Document::factory()->withStatus(WorkflowStatus::Published)->create(['judul' => 'Laporan', 'kategori' => 'laporan', 'urutan' => 1]);
        Document::factory()->withStatus(WorkflowStatus::Published)->create(['judul' => 'Formulir', 'kategori' => 'formulir', 'urutan' => 2]);
        Document::factory()->withStatus(WorkflowStatus::Published)->create(['judul' => 'Standar', 'kategori' => 'standar', 'urutan' => 3]);
        Document::factory()->withStatus(WorkflowStatus::Published)->create(['judul' => 'Perda', 'kategori' => 'regulasi', 'urutan' => 5]);
        Document::factory()->withStatus(WorkflowStatus::Published)->create(['judul' => 'UU', 'kategori' => 'regulasi', 'urutan' => 4]);

        $this->getJson('/api/public/dokumen')->assertOk()->assertJsonPath('*.judul', ['UU', 'Perda', 'Standar', 'Formulir', 'Laporan']);
    }

    public function test_shows_a_document_without_its_id_or_workflow(): void
    {
        Document::factory()->withStatus(WorkflowStatus::Published)->create([
            'judul' => 'Formulir surat kuasa',
            'keterangan' => 'Bermeterai Rp10.000',
            'kategori' => 'formulir',
            'format' => 'DOCX',
            'nomor' => '',
            'tahun' => '2026',
            'berkas' => ['nama' => 'surat-kuasa.docx', 'ukuran' => 24576, 'tipe' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'url' => 'https://berkas.example/dokumen/surat-kuasa.docx'],
            'tautan' => '',
            'penyedia' => 'Seksi Verifikasi',
            'riwayat' => [['waktu' => '2026-10-14T01:00:00+00:00', 'oleh' => 'Yohanes Admin', 'aksi' => 'menyetujui dan menerbitkan', 'ke' => 'terbit']],
        ]);

        $response = $this->getJson('/api/public/dokumen');

        $response->assertOk()->assertExactJson([[
            'judul' => 'Formulir surat kuasa',
            'keterangan' => 'Bermeterai Rp10.000',
            'kategori' => 'formulir',
            'format' => 'DOCX',
            'nomor' => '',
            'tahun' => '2026',
            'berkas' => ['nama' => 'surat-kuasa.docx', 'ukuran' => 24576, 'tipe' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'url' => 'https://berkas.example/dokumen/surat-kuasa.docx'],
            'tautan' => '',
            'terbitPada' => $response->json('0.terbitPada'),
        ]]);
        $this->assertNotNull($response->json('0.terbitPada'));
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/dokumen')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/dokumen?kategori=regulasi')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('documents')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bdocuments\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/dokumen')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
