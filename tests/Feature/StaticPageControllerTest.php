<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\StaticPage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StaticPageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $page = $this->privacyPage();

        $this->getJson('/api/koleksi/halaman')->assertUnauthorized();
        $this->patchJson("/api/koleksi/halaman/{$page->id}", ['judul' => 'Judul baru.'])->assertUnauthorized();
    }

    #[TestWith(['GET', ''])]
    #[TestWith(['GET', '/{id}'])]
    #[TestWith(['PATCH', '/{id}'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $suffix): void
    {
        $employee = User::factory()->withModules(DashboardModule::Beranda, DashboardModule::Pemutakhiran)->create();
        $page = $this->privacyPage();

        $response = $this->actingAs($employee)->json($method, '/api/koleksi/halaman'.str_replace('{id}', $page->id, $suffix), ['judul' => 'Judul baru.']);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Halaman Statis belum ditugaskan kepada Anda.']);
        $this->assertSame('Data Anda secukupnya.', $page->fresh()->judul);
    }

    public function test_lists_the_portal_pages_as_their_markup_shows_them(): void
    {
        $response = $this->actingAs($this->halamanEmployee())->getJson('/api/koleksi/halaman');

        $response->assertOk()
            ->assertJsonPath('*.slug', ['kebijakan-privasi', 'syarat-ketentuan'])
            ->assertJsonPath('0.judul', 'Data Anda secukupnya.')
            ->assertJsonPath('0.judulLanjutan', 'Dijaga sepenuhnya.')
            ->assertJsonPath('0.pengantar', 'Kebijakan privasi · berlaku sejak [tanggal terbit]')
            ->assertJsonPath('1.judul', 'Informasi resmi.')
            ->assertJsonPath('1.pengantar', 'Syarat & ketentuan · berlaku sejak [tanggal terbit]');
        $this->assertStringStartsWith('<p>Kebijakan ini menjelaskan data apa', $response->json('0.isi'));
        $this->assertStringContainsString('<a href="kontak.html#masukan">formulir kontak</a>', $response->json('0.isi'));
        // Never saved: the list shows when the page was added
        $this->assertSame($response->json('0.dibuat'), $response->json('0.diperbarui'));
        $this->assertNotNull($response->json('0.dibuat'));
    }

    public function test_lets_administrators_read_a_page(): void
    {
        $page = $this->privacyPage();

        $this->actingAs(User::factory()->administrator()->create())->getJson("/api/koleksi/halaman/{$page->id}")
            ->assertOk()
            ->assertJsonPath('id', $page->id)
            ->assertJsonPath('slug', 'kebijakan-privasi');
    }

    public function test_saves_a_page_and_logs_it_by_its_name(): void
    {
        $employee = $this->halamanEmployee();
        $page = $this->privacyPage();
        $isi = '<h2>Data.</h2><p>Satu <strong>dua</strong> <em>tiga</em> <u>empat</u> <s>lima</s><br>enam</p><hr>'
            .'<ol start="3" type="a"><li><p>Butir</p></li></ol><ul><li><p>Butir</p></li></ul><h3>Sub.</h3>'
            .'<blockquote><p>Kutipan &amp; &lt;teks&gt; “miring”</p></blockquote>'
            .'<p><a target="_blank" rel="noopener noreferrer nofollow" class="tautan" title="Kontak" href="kontak.html#masukan">formulir</a>, '
            .'<a href="https://www.lapor.go.id/?a=1&amp;b=2">LAPOR!</a>, <a href="mailto:uptd@example.go.id">surel</a>, <a href="tel:+62380821234">telepon</a></p>';

        $response = $this->actingAs($employee)->patchJson("/api/koleksi/halaman/{$page->id}", [
            'slug' => 'kebijakan-privasi',
            'judul' => 'Data Anda aman.',
            'judulLanjutan' => null,
            'pengantar' => null,
            'isi' => $isi,
        ]);

        $response->assertOk()
            ->assertJsonPath('judul', 'Data Anda aman.')
            ->assertJsonPath('judulLanjutan', '')
            ->assertJsonPath('pengantar', '')
            ->assertJsonPath('isi', $isi);
        $page->refresh();
        $this->assertSame('Data Anda aman.', $page->judul);
        $this->assertSame($isi, $page->isi);
        $this->assertNotNull($page->updated_at);
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'halaman', 'action' => 'memperbarui', 'target' => 'Kebijakan Privasi']);
    }

    public function test_neither_saves_nor_logs_an_unchanged_page(): void
    {
        $page = $this->privacyPage();

        $this->actingAs($this->halamanEmployee())->patchJson("/api/koleksi/halaman/{$page->id}", [
            'slug' => $page->slug,
            'judul' => $page->judul,
            'judulLanjutan' => $page->judul_lanjutan,
            'pengantar' => $page->pengantar,
            'isi' => $page->isi,
        ])->assertOk();

        $this->assertNull($page->fresh()->updated_at);
        $this->assertDatabaseCount('activities', 0);
    }

    #[TestWith(['syarat-ketentuan'])]
    #[TestWith(['kebijakan-baru'])]
    #[TestWith([''])]
    public function test_never_changes_the_slug(string $slug): void
    {
        $page = $this->privacyPage();

        $this->actingAs($this->halamanEmployee())->patchJson("/api/koleksi/halaman/{$page->id}", ['slug' => $slug, 'judul' => 'Judul baru.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Nama berkas halaman tidak dapat diubah.']);
        $this->assertSame('kebijakan-privasi', $page->fresh()->slug);
    }

    public function test_cannot_add_or_delete_pages(): void
    {
        $administrator = User::factory()->administrator()->create();
        $page = $this->privacyPage();

        $this->actingAs($administrator)->postJson('/api/koleksi/halaman', ['slug' => 'baru', 'judul' => 'Baru', 'isi' => '<p>Baru</p>'])->assertMethodNotAllowed();
        $this->actingAs($administrator)->deleteJson("/api/koleksi/halaman/{$page->id}")->assertMethodNotAllowed();
        $this->actingAs($administrator)->postJson('/api/koleksi/halaman/hapus', ['ids' => [$page->id]])->assertNotFound();
        $this->assertDatabaseCount('static_pages', 2);
    }

    public function test_validates_the_texts(): void
    {
        $page = $this->privacyPage();
        $long = str_repeat('a', StaticPage::MAX_TITLE_LENGTH + 1);

        $this->actingAs($this->halamanEmployee())->patchJson("/api/koleksi/halaman/{$page->id}", [
            'judul' => '',
            'judulLanjutan' => $long,
            'pengantar' => str_repeat('a', StaticPage::MAX_INTRO_LENGTH + 1),
            'isi' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'judul' => 'Judul wajib diisi.',
            'judulLanjutan' => 'Lanjutan judul maksimal 80 karakter.',
            'pengantar' => 'Teks kecil di atas judul maksimal 120 karakter.',
            'isi' => 'Isi halaman wajib diisi.',
        ]);

        $this->actingAs($this->halamanEmployee())->patchJson("/api/koleksi/halaman/{$page->id}", [
            'judul' => "Judul\u{0007}",
            'isi' => '<p>'.str_repeat('a', StaticPage::MAX_CONTENT_LENGTH).'</p>',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'judul' => 'Judul berisi karakter kontrol yang tidak diizinkan.',
            'isi' => 'Isi halaman terlalu panjang (maksimal 100000 karakter HTML).',
        ]);
        $this->assertNull($page->fresh()->updated_at);
    }

    #[DataProvider('unsupportedContent')]
    public function test_rejects_content_the_editor_does_not_write(string $isi, string $message): void
    {
        $page = $this->privacyPage();

        $this->actingAs($this->halamanEmployee())->patchJson("/api/koleksi/halaman/{$page->id}", ['isi' => $isi])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isi' => $message]);
        $this->assertNull($page->fresh()->updated_at);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unsupportedContent(): array
    {
        $element = fn (string $name): string => "Isi halaman memuat elemen <{$name}> yang tidak didukung. Gunakan paragraf, subjudul, daftar, kutipan, dan tautan dari editor.";
        $link = fn (string $href): string => "Isi halaman: alamat tautan \"{$href}\" tidak valid. Gunakan alamat lengkap (https://…) atau nama halaman portal (mis. kontak.html#masukan).";

        return [
            'script' => ['<p>a</p><script>alert(1)</script>', $element('script')],
            'image' => ['<p><img src="x" onerror="alert(1)"></p>', $element('img')],
            'iframe' => ['<iframe src="https://example.com"></iframe>', $element('iframe')],
            'style' => ['<style>p{}</style><p>a</p>', $element('style')],
            'svg' => ['<p><svg><a href="#">x</a></svg></p>', $element('svg')],
            'closing body' => ['</body><p>a</p>', $element('body')],
            'h1' => ['<h1>Judul</h1>', $element('h1')],
            'event attribute' => ['<p onclick="alert(1)">a</p>', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "<p onclick="alert(1)">a</p>". Hapus lalu tulis ulang bagian itu di editor.'],
            'attribute after a slash' => ['<p/onclick=alert(1)>a</p>', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "<p/onclick=alert(1)>a</p>". Hapus lalu tulis ulang bagian itu di editor.'],
            'uppercase element' => ['<P>a</P>', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "<P>a</P>". Hapus lalu tulis ulang bagian itu di editor.'],
            'comment' => ['<p>a</p><!-- catatan -->', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "<!-- catatan -->". Hapus lalu tulis ulang bagian itu di editor.'],
            'bare less-than sign' => ['<p>a < b</p>', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "< b</p>". Hapus lalu tulis ulang bagian itu di editor.'],
            'style attribute on a link' => ['<p><a href="kontak.html" style="color:red">a</a></p>', 'Isi halaman memuat atribut style pada <a> yang tidak didukung.'],
            'event attribute on a link' => ['<p><a href="kontak.html" onmouseover="alert(1)">a</a></p>', 'Isi halaman memuat atribut onmouseover pada <a> yang tidak didukung.'],
            'unquoted attribute' => ['<p><a href=kontak.html>a</a></p>', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "<a href=kontak.html>a</a></p>". Hapus lalu tulis ulang bagian itu di editor.'],
            'repeated href' => ['<p><a href="kontak.html" href="javascript:alert(1)">a</a></p>', 'Isi halaman memuat atribut href pada <a> yang tidak didukung.'],
            'javascript link' => ['<p><a href="javascript:alert(1)">a</a></p>', $link('javascript:alert(1)')],
            'encoded javascript link' => ['<p><a href="java&#10;script:alert(1)">a</a></p>', 'Isi halaman berisi karakter kontrol yang tidak diizinkan.'],
            'data link' => ['<p><a href="data:text/html,x">a</a></p>', $link('data:text/html,x')],
            'link without address' => ['<p><a>a</a></p>', $link('')],
            'list numbering' => ['<ol start="-1"><li><p>a</p></li></ol>', 'Isi halaman memuat penomoran daftar yang tidak didukung.'],
            'attribute on a paragraph' => ['<p class="proto-note">a</p>', 'Isi halaman memuat kode HTML yang tidak didukung editor di dekat "<p class="proto-note">a</p>". Hapus lalu tulis ulang bagian itu di editor.'],
            'control character' => ["<p>a\u{0001}</p>", 'Isi halaman berisi karakter kontrol yang tidak diizinkan.'],
            'encoded control character' => ['<p>a&#1;</p>', 'Isi halaman berisi karakter kontrol yang tidak diizinkan.'],
            'only empty paragraphs' => ['<p></p><p>&nbsp;</p>', 'Isi halaman tidak boleh hanya berisi spasi atau karakter tak terlihat.'],
        ];
    }

    private function halamanEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Halaman)->create();
    }

    private function privacyPage(): StaticPage
    {
        return StaticPage::query()->where('slug', 'kebijakan-privasi')->firstOrFail();
    }
}
