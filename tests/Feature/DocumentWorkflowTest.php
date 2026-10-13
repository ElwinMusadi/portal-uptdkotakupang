<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\Activity;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_takes_a_document_from_draft_to_published_with_its_history(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        $manager = $this->dokumenEmployee('Maria Bria');
        $administrator = User::factory()->administrator()->create(['name' => 'Yohanes Admin']);
        $document = Document::factory()->create();

        $this->step($manager, $document, 'diperiksa')->assertOk()->assertJsonPath('status', 'diperiksa');
        $this->step($manager, $document, 'persetujuan')->assertOk()->assertJsonPath('status', 'persetujuan');
        $this->step($manager, $document, 'terbit')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Dokumen yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator.']);
        $response = $this->step($administrator, $document, 'terbit');

        $response->assertOk()
            ->assertJsonPath('status', 'terbit')
            ->assertJsonPath('terbitPada', '2026-10-14T01:00:00+00:00')
            ->assertJsonPath('riwayat.*.aksi', ['menyetujui dan menerbitkan', 'meneruskan ke penyetuju', 'mengajukan pemeriksaan'])
            ->assertJsonPath('riwayat.*.oleh', ['Yohanes Admin', 'Maria Bria', 'Maria Bria']);
        $this->assertSame(
            ['mengajukan pemeriksaan', 'meneruskan ke penyetuju', 'menyetujui dan menerbitkan'],
            Activity::query()->orderBy('id')->pluck('action')->all(),
        );
        $this->assertDatabaseHas('activities', ['module' => 'dokumen', 'target' => $document->judul]);
    }

    public function test_publishes_only_documents_with_a_file_or_link(): void
    {
        $administrator = User::factory()->administrator()->create();
        $document = Document::query()->firstOrFail();
        $document->moveTo(WorkflowStatus::AwaitingApproval, 'PINTU', 'meneruskan ke penyetuju');
        $document->save();

        $this->step($administrator, $document, 'terbit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Lampirkan berkas atau tautan sebelum dokumen diterbitkan.']);
        $this->assertSame(WorkflowStatus::AwaitingApproval, $document->fresh()->status);

        $this->step($administrator, $document, 'terbit', ['tautan' => 'https://jdih.nttprov.go.id/uu-1-2022'])
            ->assertOk()
            ->assertJsonPath('status', 'terbit')
            ->assertJsonPath('tautan', 'https://jdih.nttprov.go.id/uu-1-2022');
        $this->actingAs($administrator)->patchJson("/api/koleksi/dokumen/{$document->id}", ['tautan' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas' => 'Lampirkan berkas atau tautan sebelum dokumen diterbitkan.']);
        $this->step($administrator, $document, 'arsip', ['tautan' => null])->assertOk()->assertJsonPath('tautan', '');
    }

    public function test_returns_a_document_with_a_note_and_clears_it_on_resubmission(): void
    {
        $manager = $this->dokumenEmployee();
        $document = Document::factory()->withStatus(WorkflowStatus::UnderReview)->create();

        $this->step($manager, $document, 'dikembalikan', ['catatanRevisi' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatanRevisi' => 'Tulis catatan revisi agar penyedia tahu apa yang perlu diperbaiki.']);
        $this->step($manager, $document, 'dikembalikan', ['catatanRevisi' => 'Lampirkan berkas dari JDIH.'])
            ->assertOk()
            ->assertJsonPath('catatanRevisi', 'Lampirkan berkas dari JDIH.')
            ->assertJsonPath('riwayat.0.catatan', 'Lampirkan berkas dari JDIH.');
        $this->step($manager, $document, 'diperiksa')
            ->assertOk()
            ->assertJsonPath('catatanRevisi', '')
            ->assertJsonPath('riwayat.0.aksi', 'mengajukan ulang');
    }

    public function test_rejects_steps_the_workflow_does_not_allow(): void
    {
        $administrator = User::factory()->administrator()->create();
        $document = Document::factory()->create();

        $this->step($administrator, $document, 'terbit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Langkah ini tidak tersedia untuk dokumen berstatus Draf.']);
        $this->step($administrator, $document, 'tayang')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Status alur tidak valid.']);
        $this->assertSame(WorkflowStatus::Draft, $document->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function step(User $user, Document $document, string $status, array $data = []): TestResponse
    {
        return $this->actingAs($user)->patchJson("/api/koleksi/dokumen/{$document->id}", ['status' => $status] + $data);
    }

    private function dokumenEmployee(string $name = 'Pegawai Unduhan'): User
    {
        return User::factory()->withModules(DashboardModule::Dokumen)->create(['name' => $name]);
    }
}
