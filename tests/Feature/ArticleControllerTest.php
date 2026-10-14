<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Models\Article;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/berita')->assertUnauthorized();
        $this->postJson('/api/koleksi/berita', $this->article())->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/koleksi/berita'])]
    #[TestWith(['POST', '/api/koleksi/berita'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(DashboardModule::Dokumen)->create();

        $response = $this->actingAs($employee)->json($method, $uri, $this->article());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Berita & Pengumuman belum ditugaskan kepada Anda.']);
    }

    public function test_lets_update_schedule_managers_list_but_not_read_or_change_articles(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();
        $article = Article::query()->firstOrFail();

        $this->actingAs($employee)->getJson('/api/koleksi/berita')->assertOk()->assertJsonCount(1);
        $this->actingAs($employee)->getJson("/api/koleksi/berita/{$article->id}")->assertForbidden();
        $this->actingAs($employee)->patchJson("/api/koleksi/berita/{$article->id}", ['judul' => 'Judul lain.'])->assertForbidden();
    }

    public function test_lists_only_the_launch_article_as_a_draft(): void
    {
        $response = $this->actingAs($this->beritaEmployee())->getJson('/api/koleksi/berita');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.judul', 'PINTU resmi diluncurkan. Informasi Samsat kini di satu portal.')
            ->assertJsonPath('0.slug', 'pintu-resmi-diluncurkan')
            ->assertJsonPath('0.kategori', 'berita')
            ->assertJsonPath('0.status', 'draf')
            ->assertJsonPath('0.sorotan', true)
            ->assertJsonPath('0.pengumumanBeranda', false)
            ->assertJsonPath('0.sampul', null)
            ->assertJsonPath('0.kreditFoto', 'Subbagian Tata Usaha')
            ->assertJsonPath('0.pemeriksa', 'Kepala Subbagian Tata Usaha')
            ->assertJsonPath('0.riwayat.0.oleh', 'PINTU')
            ->assertJsonPath('0.riwayat.0.aksi', 'memasukkan data awal dari halaman Informasi');
        $this->assertStringContainsString('<a href="kontak.html#masukan">formulir masukan</a>', $response->json('0.isi'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $response->json('0.tanggal'));
    }

    public function test_lists_articles_newest_first(): void
    {
        Article::factory()->create(['slug' => 'lama', 'tanggal' => '2026-01-01']);
        Article::factory()->create(['slug' => 'baru', 'tanggal' => '2030-01-01']);

        $this->actingAs($this->beritaEmployee())->getJson('/api/koleksi/berita')
            ->assertOk()
            ->assertJsonPath('*.slug', ['baru', 'pintu-resmi-diluncurkan', 'lama']);
    }

    public function test_accepts_the_launch_article_body_as_the_editor_wrote_it(): void
    {
        $article = Article::query()->firstOrFail();

        $this->actingAs($this->beritaEmployee())->patchJson("/api/koleksi/berita/{$article->id}", ['isi' => $article->isi, 'ringkasan' => 'Ringkasan baru.'])
            ->assertOk()
            ->assertJsonPath('ringkasan', 'Ringkasan baru.');
    }

    public function test_adds_a_draft_whatever_workflow_fields_are_sent(): void
    {
        $employee = $this->beritaEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/berita', [
            'status' => 'terbit',
            'terbitPada' => '2026-01-01T00:00:00Z',
            'riwayat' => [['waktu' => '2026-01-01T00:00:00Z', 'oleh' => 'Orang lain', 'aksi' => 'menerbitkan', 'ke' => 'terbit']],
            'catatanRevisi' => 'abaikan',
        ] + $this->article());

        $response->assertCreated()
            ->assertJsonPath('judul', 'Loket Samsat buka Sabtu pagi. Khusus pajak tahunan.')
            ->assertJsonPath('tanggal', '2026-10-17')
            ->assertJsonPath('pengumumanBeranda', true)
            ->assertJsonPath('status', 'draf')
            ->assertJsonPath('terbitPada', null)
            ->assertJsonPath('catatanRevisi', '')
            ->assertJsonCount(1, 'riwayat')
            ->assertJsonPath('riwayat.0.oleh', $employee->name)
            ->assertJsonPath('riwayat.0.aksi', 'menyiapkan draf');
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'berita', 'action' => 'menambahkan', 'target' => 'Loket Samsat buka Sabtu pagi. Khusus pajak tahunan.']);
    }

    public function test_stores_empty_or_missing_optional_fields_as_empty(): void
    {
        $empty = ['keteranganSampul' => null, 'kreditFoto' => null, 'pemeriksa' => null, 'sampul' => null] + $this->article();
        $missing = array_diff_key($empty, array_flip(['keteranganSampul', 'kreditFoto', 'pemeriksa', 'sampul']));

        foreach ([$empty, ['slug' => 'tanpa-isian'] + $missing] as $article) {
            $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita', $article)
                ->assertCreated()
                ->assertJsonPath('keteranganSampul', '')
                ->assertJsonPath('kreditFoto', '')
                ->assertJsonPath('pemeriksa', '')
                ->assertJsonPath('sampul', null);
        }
    }

    public function test_validates_a_new_article(): void
    {
        $article = $this->article();
        $article['judul'] = '';
        $article['slug'] = 'pintu-resmi-diluncurkan';
        $article['kategori'] = 'opini';
        $article['ringkasan'] = str_repeat('a', 241);
        $article['isi'] = '<p>Halo</p><script>alert(1)</script>';
        $article['tanggal'] = '17-10-2026';
        $article['sorotan'] = 'ya';
        $article['kreditFoto'] = "Foto\u{0007}";
        $article['penyedia'] = '';

        $response = $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita', $article);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'judul' => 'Judul wajib diisi.',
            'slug' => 'Slug sudah dipakai tulisan lain.',
            'kategori' => 'Kategori tulisan tidak valid.',
            'ringkasan' => 'Ringkasan maksimal 240 karakter.',
            'isi' => 'Isi tulisan memuat elemen <script> yang tidak didukung. Gunakan paragraf, subjudul, daftar, kutipan, dan tautan dari editor.',
            'tanggal' => 'Pilih tanggal tayang.',
            'sorotan' => 'Pilihan tulisan utama tidak valid.',
            'kreditFoto' => 'Kredit foto berisi karakter kontrol yang tidak diizinkan.',
            'penyedia' => 'Unit penyedia wajib diisi.',
        ]);
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_limits_the_texts(): void
    {
        $article = [
            'judul' => str_repeat('a', 161),
            'slug' => str_repeat('a', 81),
            'keteranganSampul' => str_repeat('a', 201),
            'kreditFoto' => str_repeat('a', 121),
            'isi' => '<p>'.str_repeat('a', Article::MAX_CONTENT_LENGTH).'</p>',
        ] + $this->article();

        $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita', $article)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'judul' => 'Judul maksimal 160 karakter.',
                'slug' => 'Slug maksimal 80 karakter.',
                'keteranganSampul' => 'Keterangan foto maksimal 200 karakter.',
                'kreditFoto' => 'Kredit foto maksimal 120 karakter.',
                'isi' => 'Isi tulisan terlalu panjang (maksimal 100000 karakter HTML).',
            ]);
        $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita', ['slug' => 'Loket Sabtu', 'judul' => "Loket\u{0007}"] + $this->article())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'slug' => 'Gunakan huruf kecil, angka, dan tanda hubung.',
                'judul' => 'Judul berisi karakter kontrol yang tidak diizinkan.',
            ]);
    }

    public function test_keeps_the_cover_in_use_and_deletes_the_one_it_replaces(): void
    {
        Storage::fake('public');
        $employee = $this->beritaEmployee();
        [$first, $second] = [$this->cover(), $this->cover()];
        Storage::disk('public')->put($first->path, 'foto');
        Storage::disk('public')->put($second->path, 'foto');
        $id = $this->actingAs($employee)->postJson('/api/koleksi/berita', ['sampul' => $first->url()] + $this->article())
            ->assertCreated()
            ->assertJsonPath('sampul', $first->url())
            ->json('id');
        $this->assertNotNull($first->fresh()->dipakai_pada);

        $this->actingAs($employee)->patchJson("/api/koleksi/berita/{$id}", ['judul' => 'Judul baru.', 'sampul' => $first->url()])->assertOk();
        $this->assertModelExists($first);

        $this->actingAs($employee)->patchJson("/api/koleksi/berita/{$id}", ['sampul' => $second->url()])->assertOk();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->path);

        $this->actingAs($employee)->patchJson("/api/koleksi/berita/{$id}", ['sampul' => null])->assertOk()->assertJsonPath('sampul', null);
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_rejects_covers_that_are_not_its_uploads_or_belong_to_another_article(): void
    {
        $profilePhoto = Upload::factory()->create(['tujuan' => UploadTarget::Profil]);

        foreach (['https://example.com/foto.jpg', $profilePhoto->url()] as $url) {
            $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita', ['sampul' => $url] + $this->article())
                ->assertUnprocessable()
                ->assertOnlyJsonValidationErrors(['sampul' => 'Foto sampul harus diunggah lewat dashboard.']);
        }

        $cover = $this->cover();
        $other = Article::factory()->create(['sampul' => $cover->url()]);
        $cover->markUsed();
        $article = Article::factory()->create();
        $this->actingAs($this->beritaEmployee())->patchJson("/api/koleksi/berita/{$article->id}", ['sampul' => $cover->url()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sampul' => 'Foto ini sudah dipakai tulisan lain. Unggah ulang fotonya.']);
        $this->actingAs($this->beritaEmployee())->patchJson("/api/koleksi/berita/{$other->id}", ['sampul' => $cover->url(), 'kreditFoto' => 'Humas'])
            ->assertOk()
            ->assertJsonPath('kreditFoto', 'Humas');
    }

    public function test_keeps_a_single_main_article_without_touching_the_others(): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 01:00:00', 'UTC'));
        $launch = Article::query()->firstOrFail();
        $before = $launch->updated_at;
        $article = Article::factory()->create();
        $this->travel(1)->hours();
        $this->actingAs($this->beritaEmployee())->patchJson("/api/koleksi/berita/{$article->id}", ['ringkasan' => 'Ringkasan lain.'])->assertOk();
        $this->assertTrue($launch->fresh()->sorotan);

        $this->actingAs($this->beritaEmployee())->patchJson("/api/koleksi/berita/{$article->id}", ['sorotan' => true])
            ->assertOk()
            ->assertJsonPath('sorotan', true);

        $this->assertFalse($launch->fresh()->sorotan);
        $this->assertEquals($before, $launch->fresh()->updated_at);
        $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita', ['sorotan' => true] + $this->article())->assertCreated();
        $this->assertSame(1, Article::query()->where('sorotan', true)->count());
        $this->assertFalse($article->fresh()->sorotan);
    }

    public function test_changes_an_article_and_logs_only_real_changes(): void
    {
        $employee = $this->beritaEmployee();
        $article = Article::factory()->create();

        $this->actingAs($employee)->patchJson("/api/koleksi/berita/{$article->id}", ['kategori' => 'edukasi', 'tanggal' => '2026-10-14'])
            ->assertOk()
            ->assertJsonPath('kategori', 'edukasi');
        $this->actingAs($employee)->patchJson("/api/koleksi/berita/{$article->id}", ['kategori' => 'edukasi', 'tanggal' => '2026-10-14', 'slug' => $article->slug])->assertOk();

        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['module' => 'berita', 'action' => 'memperbarui', 'target' => $article->judul]);
    }

    #[TestWith([WorkflowStatus::AwaitingApproval])]
    #[TestWith([WorkflowStatus::Published])]
    public function test_lets_module_managers_change_articles_awaiting_approval_or_published(WorkflowStatus $status): void
    {
        $article = Article::factory()->withStatus($status)->create();

        $this->actingAs($this->beritaEmployee())->patchJson("/api/koleksi/berita/{$article->id}", ['ringkasan' => 'Ralat jam layanan.'])
            ->assertOk()
            ->assertJsonPath('ringkasan', 'Ralat jam layanan.')
            ->assertJsonPath('status', $status->value);
    }

    public function test_lets_only_administrators_delete_articles_with_their_covers(): void
    {
        Storage::fake('public');
        $cover = $this->cover();
        Storage::disk('public')->put($cover->path, 'foto');
        $withCover = Article::factory()->withStatus(WorkflowStatus::Published)->create(['sampul' => $cover->url()]);

        $this->actingAs($this->beritaEmployee())->postJson('/api/koleksi/berita/hapus', ['ids' => [$withCover->id]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus tulisan.']);
        $this->assertModelExists($cover);

        $this->actingAs(User::factory()->administrator()->create())->postJson('/api/koleksi/berita/hapus', ['ids' => [$withCover->id]])->assertNoContent();

        $this->assertModelMissing($withCover);
        $this->assertModelMissing($cover);
        Storage::disk('public')->assertMissing($cover->path);
        $this->assertDatabaseHas('activities', ['module' => 'berita', 'action' => 'menghapus', 'target' => $withCover->judul]);
    }

    private function beritaEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Berita)->create();
    }

    private function cover(): Upload
    {
        return Upload::factory()->create(['tujuan' => UploadTarget::Berita]);
    }

    /**
     * @return array<string, mixed>
     */
    private function article(): array
    {
        return [
            'judul' => 'Loket Samsat buka Sabtu pagi. Khusus pajak tahunan.',
            'slug' => 'loket-buka-sabtu',
            'kategori' => 'pengumuman',
            'ringkasan' => 'Loket pajak tahunan buka pukul 08.00–12.00.',
            'isi' => '<p>Loket pajak tahunan buka pada <strong>Sabtu</strong>.</p><ul><li><p>Bawa STNK asli.</p></li></ul>',
            'sampul' => null,
            'keteranganSampul' => 'Loket pajak tahunan.',
            'kreditFoto' => 'Subbagian Tata Usaha',
            'tanggal' => '2026-10-17',
            'sorotan' => false,
            'pengumumanBeranda' => true,
            'penyedia' => 'Pengelola publikasi',
            'pemeriksa' => 'Kepala Subbagian Tata Usaha',
        ];
    }
}
