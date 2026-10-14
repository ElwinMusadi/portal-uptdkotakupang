<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\Activity;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ArticleWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lets_a_module_manager_take_an_article_from_draft_to_published_and_withdraw_it(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        $manager = $this->beritaEmployee('Maria Bria');
        $article = Article::factory()->create();

        $this->step($manager, $article, 'diperiksa')->assertOk()->assertJsonPath('status', 'diperiksa');
        $this->step($manager, $article, 'persetujuan')->assertOk()->assertJsonPath('status', 'persetujuan');
        $this->step($manager, $article, 'terbit')
            ->assertOk()
            ->assertJsonPath('status', 'terbit')
            ->assertJsonPath('terbitPada', '2026-10-14T01:00:00+00:00')
            ->assertJsonPath('riwayat.*.aksi', ['menyetujui dan menerbitkan', 'meneruskan ke penyetuju', 'mengajukan pemeriksaan'])
            ->assertJsonPath('riwayat.*.oleh', ['Maria Bria', 'Maria Bria', 'Maria Bria']);
        $this->step($manager, $article, 'arsip')->assertOk()->assertJsonPath('status', 'arsip');
        $this->step($manager, $article, 'draf')->assertOk()->assertJsonPath('riwayat.0.aksi', 'memulihkan ke draf');

        $this->assertSame(
            ['mengajukan pemeriksaan', 'meneruskan ke penyetuju', 'menyetujui dan menerbitkan', 'menarik dari portal', 'memulihkan ke draf'],
            Activity::query()->orderBy('id')->pluck('action')->all(),
        );
        $this->assertDatabaseHas('activities', ['module' => 'berita', 'target' => $article->judul]);
    }

    public function test_returns_an_article_with_a_note_and_clears_it_on_resubmission(): void
    {
        $manager = $this->beritaEmployee();
        $article = Article::factory()->withStatus(WorkflowStatus::AwaitingApproval)->create();

        $this->step($manager, $article, 'dikembalikan')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['catatanRevisi' => 'Tulis catatan revisi agar penyedia tahu apa yang perlu diperbaiki.']);
        $this->step($manager, $article, 'dikembalikan', ['catatanRevisi' => 'Tambahkan jam layanan.'])
            ->assertOk()
            ->assertJsonPath('catatanRevisi', 'Tambahkan jam layanan.')
            ->assertJsonPath('riwayat.0.catatan', 'Tambahkan jam layanan.');
        $this->step($manager, $article, 'diperiksa')
            ->assertOk()
            ->assertJsonPath('catatanRevisi', '')
            ->assertJsonPath('riwayat.0.aksi', 'mengajukan ulang');
    }

    public function test_rejects_steps_the_workflow_does_not_allow(): void
    {
        $manager = $this->beritaEmployee();
        $article = Article::factory()->create();

        $this->step($manager, $article, 'terbit')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Langkah ini tidak tersedia untuk tulisan berstatus Draf.']);
        $this->step($manager, $article, 'tayang')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Status alur tidak valid.']);
        $this->assertSame(WorkflowStatus::Draft, $article->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function step(User $user, Article $article, string $status, array $data = []): TestResponse
    {
        return $this->actingAs($user)->patchJson("/api/koleksi/berita/{$article->id}", ['status' => $status] + $data);
    }

    private function beritaEmployee(string $name = 'Pegawai Berita'): User
    {
        return User::factory()->withModules(DashboardModule::Berita)->create(['name' => $name]);
    }
}
