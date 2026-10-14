<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Models\Activity;
use App\Models\Documentation;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DocumentationWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lets_a_module_manager_take_an_activity_from_draft_to_published_and_withdraw_it(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        $manager = $this->dokumentasiEmployee('Maria Bria');
        $activity = Documentation::factory()->withPhotos([$this->photo()->url()])->create();

        $this->step($manager, $activity, 'diperiksa')->assertOk();
        $this->step($manager, $activity, 'persetujuan')->assertOk();
        $this->step($manager, $activity, 'terbit')
            ->assertOk()
            ->assertJsonPath('status', 'terbit')
            ->assertJsonPath('terbitPada', '2026-10-14T01:00:00+00:00')
            ->assertJsonPath('riwayat.*.oleh', ['Maria Bria', 'Maria Bria', 'Maria Bria']);
        $this->step($manager, $activity, 'arsip')->assertOk()->assertJsonPath('status', 'arsip');

        $this->assertSame(
            ['mengajukan pemeriksaan', 'meneruskan ke penyetuju', 'menyetujui dan menerbitkan', 'menarik dari portal'],
            Activity::query()->orderBy('id')->pluck('action')->all(),
        );
        $this->assertDatabaseHas('activities', ['module' => 'dokumentasi', 'target' => $activity->judul]);
    }

    public function test_publishes_only_activities_with_a_photo(): void
    {
        $manager = $this->dokumentasiEmployee();
        $activity = Documentation::query()->firstOrFail();
        $activity->moveTo(WorkflowStatus::AwaitingApproval, 'PINTU', 'meneruskan ke penyetuju');
        $activity->save();

        $this->step($manager, $activity, 'terbit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto' => 'Unggah minimal satu foto sebelum kegiatan diterbitkan.']);
        $this->step($manager, $activity, 'terbit', ['lokasi' => ''])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['lokasi' => 'Lokasi wajib diisi.']);
        $this->assertSame(WorkflowStatus::AwaitingApproval, $activity->fresh()->status);

        $photo = $this->photo();
        $this->step($manager, $activity, 'terbit', ['foto' => [['id' => 'a', 'src' => $photo->url(), 'keterangan' => '']]])
            ->assertOk()
            ->assertJsonPath('status', 'terbit');
        $this->actingAs($manager)->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['foto' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto' => 'Unggah minimal satu foto sebelum kegiatan diterbitkan.']);
        $this->step($manager, $activity, 'arsip', ['foto' => []])->assertOk()->assertJsonPath('foto', []);
    }

    public function test_returns_an_activity_with_a_note_and_rejects_steps_the_workflow_does_not_allow(): void
    {
        $manager = $this->dokumentasiEmployee();
        $activity = Documentation::factory()->withStatus(WorkflowStatus::UnderReview)->create();

        $this->step($manager, $activity, 'dikembalikan')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatanRevisi' => 'Tulis catatan revisi agar penyedia tahu apa yang perlu diperbaiki.']);
        $this->step($manager, $activity, 'dikembalikan', ['catatanRevisi' => 'Tambahkan foto.'])
            ->assertOk()
            ->assertJsonPath('catatanRevisi', 'Tambahkan foto.');
        $this->step($manager, $activity, 'terbit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Langkah ini tidak tersedia untuk kegiatan berstatus Dikembalikan.']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function step(User $user, Documentation $activity, string $status, array $data = []): TestResponse
    {
        return $this->actingAs($user)->patchJson("/api/koleksi/dokumentasi/{$activity->id}", ['status' => $status] + $data);
    }

    private function dokumentasiEmployee(string $name = 'Pegawai Dokumentasi'): User
    {
        return User::factory()->withModules(DashboardModule::Dokumentasi)->create(['name' => $name]);
    }

    private function photo(): Upload
    {
        return Upload::factory()->create(['tujuan' => UploadTarget::Dokumentasi]);
    }
}
