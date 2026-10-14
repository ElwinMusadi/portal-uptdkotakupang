<?php

namespace Tests\Feature;

use App\Enums\WorkflowStatus;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\Documentation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use PDOException;
use Tests\TestCase;

class PublicDocumentationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_nothing_while_the_example_activities_are_drafts(): void
    {
        $this->getJson('/api/public/dokumentasi')->assertOk()->assertExactJson([]);
    }

    public function test_lists_only_published_activities_newest_first_with_their_photos(): void
    {
        foreach ([WorkflowStatus::UnderReview, WorkflowStatus::AwaitingApproval, WorkflowStatus::Returned, WorkflowStatus::Archived] as $status) {
            Documentation::factory()->withPhotos(['https://berkas.example/a.jpg'])->withStatus($status)->create(['tanggal' => '2026-12-01']);
        }
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        Documentation::factory()->withPhotos(['https://berkas.example/pagi.jpg'])->withStatus(WorkflowStatus::Published)->create(['judul' => 'Pagi.', 'tanggal' => '2026-10-14']);
        $this->travel(3)->hours();
        // Published last, but held earlier
        Documentation::factory()->withPhotos(['https://berkas.example/lama.jpg'])->withStatus(WorkflowStatus::Published)->create(['judul' => 'Lama.', 'tanggal' => '2025-12-30']);
        $this->travel(-1)->hours();
        Documentation::factory()->withStatus(WorkflowStatus::Published)->create([
            'judul' => 'Operasi gabungan.',
            'kategori' => 'operasi',
            'lokasi' => 'Jl. El Tari',
            'tanggal' => '2026-10-14',
            'deskripsi' => 'Bersama Satlantas.',
            'foto' => [
                ['id' => 'a', 'src' => 'https://berkas.example/1.jpg', 'keterangan' => 'Pemeriksaan fisik.'],
                ['id' => 'b', 'src' => 'https://berkas.example/2.jpg', 'keterangan' => ''],
            ],
        ]);

        $response = $this->getJson('/api/public/dokumentasi');

        $response->assertOk()
            ->assertJsonPath('*.judul', ['Operasi gabungan.', 'Pagi.', 'Lama.'])
            ->assertJsonPath('0', [
                'judul' => 'Operasi gabungan.',
                'kategori' => 'operasi',
                'lokasi' => 'Jl. El Tari',
                'tanggal' => '2026-10-14',
                'deskripsi' => 'Bersama Satlantas.',
                'foto' => [
                    ['src' => 'https://berkas.example/1.jpg', 'keterangan' => 'Pemeriksaan fisik.'],
                    ['src' => 'https://berkas.example/2.jpg', 'keterangan' => ''],
                ],
                'terbitPada' => '2026-10-14T03:00:00+00:00',
            ]);
    }

    public function test_starts_no_session_forbids_caching_and_rejects_query_parameters(): void
    {
        $this->withHeader('Referer', config('app.url').'/')->getJson('/api/public/dokumentasi')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->get('/api/public/dokumentasi?bulan=2026-10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
    }

    public function test_returns_503_when_the_source_cannot_be_read(): void
    {
        DB::table('documentations')->exists();
        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bdocumentations\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
        Exceptions::fake();

        $this->getJson('/api/public/dokumentasi')->assertServiceUnavailable();

        Exceptions::assertReported(PublicContentUnavailableException::class);
    }
}
