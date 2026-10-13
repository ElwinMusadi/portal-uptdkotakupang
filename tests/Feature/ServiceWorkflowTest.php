<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\Activity;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ServiceWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_takes_a_service_from_draft_to_published_with_its_history(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        $manager = $this->layananEmployee('Maria Bria');
        $administrator = User::factory()->administrator()->create(['name' => 'Yohanes Admin']);
        $service = Service::factory()->create();

        $this->step($manager, $service, 'diperiksa')->assertOk()->assertJsonPath('status', 'diperiksa');
        $this->step($manager, $service, 'persetujuan')->assertOk()->assertJsonPath('status', 'persetujuan');
        $this->step($manager, $service, 'terbit')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Layanan yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator.']);
        $response = $this->step($administrator, $service, 'terbit');

        $response->assertOk()
            ->assertJsonPath('status', 'terbit')
            ->assertJsonPath('terbitPada', '2026-10-14T01:00:00+00:00')
            ->assertJsonPath('riwayat.*.aksi', ['menyetujui dan menerbitkan', 'meneruskan ke penyetuju', 'mengajukan pemeriksaan'])
            ->assertJsonPath('riwayat.*.oleh', ['Yohanes Admin', 'Maria Bria', 'Maria Bria'])
            ->assertJsonPath('riwayat.0.ke', 'terbit');
        $this->assertSame(
            ['mengajukan pemeriksaan', 'meneruskan ke penyetuju', 'menyetujui dan menerbitkan'],
            $this->activityActions(),
        );
    }

    public function test_returns_a_service_with_a_note_and_clears_it_on_resubmission(): void
    {
        $manager = $this->layananEmployee();
        $service = Service::factory()->withStatus(WorkflowStatus::UnderReview)->create();

        $this->step($manager, $service, 'dikembalikan', ['catatanRevisi' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatanRevisi' => 'Tulis catatan revisi agar penyedia tahu apa yang perlu diperbaiki.']);
        $this->step($manager, $service, 'dikembalikan', ['catatanRevisi' => 'Sesuaikan jumlah lembar.'])
            ->assertOk()
            ->assertJsonPath('status', 'dikembalikan')
            ->assertJsonPath('catatanRevisi', 'Sesuaikan jumlah lembar.')
            ->assertJsonPath('riwayat.0.catatan', 'Sesuaikan jumlah lembar.');
        $this->step($manager, $service, 'diperiksa', ['catatanRevisi' => 'Sesuaikan jumlah lembar.'])
            ->assertOk()
            ->assertJsonPath('catatanRevisi', '')
            ->assertJsonPath('riwayat.0.aksi', 'mengajukan ulang')
            ->assertJsonMissingPath('riwayat.0.catatan');
    }

    public function test_lets_only_administrators_return_from_approval_and_withdraw(): void
    {
        $manager = $this->layananEmployee();
        $administrator = User::factory()->administrator()->create();
        $awaiting = Service::factory()->withStatus(WorkflowStatus::AwaitingApproval)->create();
        $published = Service::factory()->withStatus(WorkflowStatus::Published)->create();

        $this->step($manager, $awaiting, 'dikembalikan', ['catatanRevisi' => 'Perbaiki.'])->assertForbidden();
        $this->step($manager, $published, 'arsip')->assertForbidden();
        $this->step($administrator, $awaiting, 'dikembalikan', ['catatanRevisi' => 'Perbaiki.'])->assertOk()->assertJsonPath('status', 'dikembalikan');
        $this->step($administrator, $published, 'arsip')->assertOk()->assertJsonPath('status', 'arsip');
        $this->step($manager, $published, 'draf')->assertOk()->assertJsonPath('riwayat.0.aksi', 'memulihkan ke draf');
    }

    public function test_rejects_steps_the_workflow_does_not_allow(): void
    {
        $administrator = User::factory()->administrator()->create();
        $service = Service::factory()->create();

        $this->step($administrator, $service, 'terbit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Langkah ini tidak tersedia untuk layanan berstatus Draf.']);
        $this->step($administrator, $service, 'tayang')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Status alur tidak valid.']);
        $this->assertSame(WorkflowStatus::Draft, $service->fresh()->status);
    }

    public function test_ignores_the_current_status_and_history_or_time_sent_by_the_client(): void
    {
        $manager = $this->layananEmployee();
        $service = Service::factory()->create();

        $response = $this->step($manager, $service, 'draf', [
            'riwayat' => [['waktu' => '2026-01-01T00:00:00Z', 'oleh' => 'Orang lain', 'aksi' => 'menerbitkan', 'ke' => 'terbit']],
            'terbitPada' => '2026-01-01T00:00:00Z',
        ]);

        $response->assertOk()->assertJsonPath('riwayat', [])->assertJsonPath('terbitPada', null);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_lets_only_administrators_reorder_and_delete(): void
    {
        $manager = $this->layananEmployee();
        $administrator = User::factory()->administrator()->create();
        $ids = Service::query()->orderBy('urutan')->pluck('id')->all();

        $this->actingAs($manager)->putJson('/api/koleksi/layanan/urutan', ['ids' => array_reverse($ids)])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengubah urutan layanan di portal.']);
        $this->actingAs($manager)->postJson('/api/koleksi/layanan/hapus', ['ids' => [$ids[0]]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus layanan.']);

        $this->actingAs($administrator)->putJson('/api/koleksi/layanan/urutan', ['ids' => [$ids[5], $ids[0]]])->assertNoContent();
        $this->assertSame([$ids[5], $ids[0], $ids[1], $ids[2], $ids[3], $ids[4]], Service::query()->orderBy('urutan')->pluck('id')->all());
        $this->actingAs($administrator)->postJson('/api/koleksi/layanan/hapus', ['ids' => [$ids[0]]])->assertNoContent();
        $this->assertDatabaseMissing('services', ['id' => $ids[0]]);
        $this->assertSame(['mengubah urutan', 'menghapus'], $this->activityActions());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function step(User $user, Service $service, string $status, array $data = []): TestResponse
    {
        return $this->actingAs($user)->patchJson("/api/koleksi/layanan/{$service->id}", ['status' => $status] + $data);
    }

    private function layananEmployee(string $name = 'Pegawai Layanan'): User
    {
        return User::factory()->withModules(DashboardModule::Layanan)->create(['name' => $name]);
    }

    /**
     * @return list<string>
     */
    private function activityActions(): array
    {
        return Activity::query()->orderBy('id')->pluck('action')->all();
    }
}
