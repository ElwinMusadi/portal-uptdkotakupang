<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\UploadTarget;
use App\Enums\WorkflowStatus;
use App\Models\Document;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/dokumen')->assertUnauthorized();
        $this->postJson('/api/koleksi/dokumen', $this->document())->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/koleksi/dokumen'])]
    #[TestWith(['POST', '/api/koleksi/dokumen'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(DashboardModule::Layanan)->create();

        $response = $this->actingAs($employee)->json($method, $uri, $this->document());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Regulasi & Unduhan belum ditugaskan kepada Anda.']);
    }

    public function test_lets_update_schedule_managers_list_but_not_read_or_change_documents(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();
        $document = Document::query()->firstOrFail();

        $this->actingAs($employee)->getJson('/api/koleksi/dokumen')->assertOk()->assertJsonCount(9);
        $this->actingAs($employee)->getJson("/api/koleksi/dokumen/{$document->id}")->assertForbidden();
        $this->actingAs($employee)->patchJson("/api/koleksi/dokumen/{$document->id}", ['judul' => 'Judul lain'])->assertForbidden();
    }

    public function test_lists_the_initial_documents_as_drafts_without_files_and_with_their_official_titles(): void
    {
        $response = $this->actingAs($this->dokumenEmployee())->getJson('/api/koleksi/dokumen');

        $response->assertOk()
            ->assertJsonPath('*.judul', [
                'Undang-Undang Nomor 1 Tahun 2022',
                'Peraturan Daerah Provinsi NTT Nomor 1 Tahun 2024',
                'Peraturan Gubernur NTT Nomor 96 Tahun 2023',
                'Standar Pelayanan UPTD Pendapatan Daerah Wilayah Kota Kupang',
                'Maklumat Pelayanan',
                'SOP Pengelolaan dan Publikasi Informasi PINTU',
                'Formulir surat kuasa',
                'Formulir surat pernyataan STNK hilang/rusak',
                'Laporan Survei Kepuasan Masyarakat Semester I 2026',
            ])
            ->assertJsonPath('*.kategori', ['regulasi', 'regulasi', 'regulasi', 'standar', 'standar', 'standar', 'formulir', 'formulir', 'laporan'])
            ->assertJsonPath('*.status', array_fill(0, 9, 'draf'))
            ->assertJsonPath('*.berkas', array_fill(0, 9, null))
            ->assertJsonPath('*.tautan', array_fill(0, 9, ''))
            ->assertJsonPath('*.format', ['PDF', 'PDF', 'PDF', 'PDF', 'PDF', 'PDF', 'DOCX', 'DOCX', 'PDF'])
            ->assertJsonPath('1.keterangan', 'Pajak Daerah dan Retribusi Daerah · tarif PKB, BBNKB, dan opsen')
            ->assertJsonPath('2.nomor', '96')
            ->assertJsonPath('2.tahun', '2023')
            ->assertJsonPath('6.keterangan', "Untuk perpanjangan 5\u{00A0}tahun dan duplikat STNK · meterai Rp10.000")
            ->assertJsonPath('6.penyedia', 'Seksi Verifikasi')
            ->assertJsonPath('0.pemeriksa', 'Kepala Subbagian Tata Usaha')
            ->assertJsonPath('0.riwayat', [[
                'waktu' => $response->json('0.riwayat.0.waktu'),
                'oleh' => 'PINTU',
                'aksi' => 'memasukkan data awal dari halaman Unduhan',
                'ke' => 'draf',
            ]]);
    }

    public function test_adds_a_draft_at_the_end_whatever_workflow_fields_are_sent(): void
    {
        $employee = $this->dokumenEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/dokumen', [
            'status' => 'terbit',
            'terbitPada' => '2026-01-01T00:00:00Z',
            'riwayat' => [['waktu' => '2026-01-01T00:00:00Z', 'oleh' => 'Orang lain', 'aksi' => 'menerbitkan', 'ke' => 'terbit']],
            'catatanRevisi' => 'abaikan',
        ] + $this->document());

        $response->assertCreated()
            ->assertJsonPath('judul', 'Peraturan Gubernur NTT Nomor 9 Tahun 2024')
            ->assertJsonPath('format', 'Tautan')
            ->assertJsonPath('tautan', 'https://jdih.nttprov.go.id/pergub-9-2024')
            ->assertJsonPath('status', 'draf')
            ->assertJsonPath('terbitPada', null)
            ->assertJsonPath('catatanRevisi', '')
            ->assertJsonPath('urutan', 9)
            ->assertJsonCount(1, 'riwayat')
            ->assertJsonPath('riwayat.0.oleh', $employee->name)
            ->assertJsonPath('riwayat.0.aksi', 'menyiapkan draf');
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'dokumen', 'action' => 'menambahkan', 'target' => 'Peraturan Gubernur NTT Nomor 9 Tahun 2024']);
    }

    public function test_stores_empty_or_missing_optional_fields_as_empty(): void
    {
        $empty = ['keterangan' => null, 'nomor' => null, 'tahun' => null, 'pemeriksa' => null, 'tautan' => null, 'format' => 'PDF', 'berkas' => null] + $this->document();
        $missing = array_diff_key($empty, array_flip(['keterangan', 'nomor', 'tahun', 'pemeriksa', 'tautan', 'berkas']));

        foreach ([$empty, $missing] as $document) {
            $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', $document)
                ->assertCreated()
                ->assertJsonPath('keterangan', '')
                ->assertJsonPath('nomor', '')
                ->assertJsonPath('tahun', '')
                ->assertJsonPath('pemeriksa', '')
                ->assertJsonPath('berkas', null)
                ->assertJsonPath('tautan', '');
        }
    }

    public function test_describes_the_file_by_its_upload_and_takes_its_format(): void
    {
        $upload = $this->upload('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'surat kuasa.docx');

        $response = $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', [
            'format' => 'PDF',
            'berkas' => ['nama' => 'lain.pdf', 'ukuran' => 1, 'tipe' => 'application/pdf', 'url' => $upload->url()],
        ] + $this->document());

        $response->assertCreated()
            ->assertJsonPath('format', 'DOCX')
            ->assertJsonPath('berkas', [
                'nama' => 'surat kuasa.docx',
                'ukuran' => $upload->ukuran,
                'tipe' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'url' => $upload->url(),
            ]);
        $this->assertNotNull($upload->fresh()->dipakai_pada);
    }

    #[TestWith(['application/pdf', 'PDF'])]
    #[TestWith(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'XLSX'])]
    #[TestWith(['image/png', 'JPG'])]
    public function test_names_the_format_of_each_file_type(string $type, string $format): void
    {
        $document = Document::factory()->create();

        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['berkas' => ['url' => $this->upload($type)->url()]])
            ->assertOk()
            ->assertJsonPath('format', $format);
    }

    public function test_keeps_the_file_format_when_only_the_format_is_changed(): void
    {
        $upload = $this->upload();
        $document = Document::factory()->create(['format' => 'PDF', 'berkas' => ['nama' => 'perda.pdf', 'ukuran' => 10, 'tipe' => 'application/pdf', 'url' => $upload->url()]]);

        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['format' => 'Tautan'])
            ->assertOk()
            ->assertJsonPath('format', 'PDF');
    }

    public function test_validates_a_new_document(): void
    {
        $document = $this->document();
        $document['judul'] = '';
        $document['keterangan'] = str_repeat('a', 301);
        $document['kategori'] = 'lainnya';
        $document['format'] = 'ZIP';
        $document['nomor'] = "1\u{0007}";
        $document['tahun'] = '226';
        $document['penyedia'] = '';

        $response = $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', $document);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'judul' => 'Judul dokumen wajib diisi.',
            'keterangan' => 'Keterangan maksimal 300 karakter.',
            'kategori' => 'Kategori dokumen tidak valid.',
            'format' => 'Format dokumen tidak valid.',
            'nomor' => 'Nomor berisi karakter kontrol yang tidak diizinkan.',
            'tahun' => 'Tahun 4 angka, mis. 2026.',
            'penyedia' => 'Unit penyedia wajib diisi.',
        ]);
        $this->assertDatabaseCount('documents', 9);
    }

    public function test_limits_the_title_number_and_link(): void
    {
        $document = [
            'judul' => str_repeat('a', 201),
            'nomor' => str_repeat('1', 61),
            'tautan' => 'https://jdih.nttprov.go.id/'.str_repeat('a', 474),
        ] + $this->document();

        $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', $document)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'judul' => 'Judul dokumen maksimal 200 karakter.',
                'nomor' => 'Nomor maksimal 60 karakter.',
                'tautan' => 'Tautan maksimal 500 karakter.',
            ]);
    }

    #[TestWith(['javascript:alert(1)'])]
    #[TestWith(['ftp://jdih.nttprov.go.id/perda.pdf'])]
    #[TestWith(['jdih.nttprov.go.id'])]
    public function test_accepts_only_http_links(string $link): void
    {
        $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', ['tautan' => $link] + $this->document())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tautan' => 'Gunakan alamat lengkap yang diawali http:// atau https://.']);
    }

    public function test_needs_the_link_of_a_link_document(): void
    {
        $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', ['tautan' => null] + $this->document())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tautan' => 'Isi alamat tautan untuk dokumen berformat Tautan.']);

        $document = Document::factory()->create();
        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['tautan' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tautan' => 'Isi alamat tautan untuk dokumen berformat Tautan.']);
    }

    public function test_rejects_files_that_are_not_its_uploads(): void
    {
        $profilePhoto = Upload::factory()->create(['tujuan' => UploadTarget::Profil]);

        foreach (['https://example.com/perda.pdf', $profilePhoto->url(), ['bukan' => 'url']] as $url) {
            $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', ['format' => 'PDF', 'berkas' => is_array($url) ? $url : ['url' => $url]] + $this->document())
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['berkas.url' => 'Berkas dokumen harus diunggah lewat dashboard.']);
        }
        $this->assertDatabaseCount('documents', 9);
        $this->assertNull($profilePhoto->fresh()->dipakai_pada);

        $document = Document::factory()->create();
        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['berkas' => ['nama' => 'perda.pdf']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas.url' => 'Berkas dokumen harus diunggah lewat dashboard.']);
    }

    public function test_checks_the_link_only_once_the_file_is_valid(): void
    {
        // A valid file would set the format, so a missing link is not reported yet
        $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen', ['berkas' => ['url' => 'https://example.com/perda.pdf'], 'tautan' => null] + $this->document())
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['berkas.url' => 'Berkas dokumen harus diunggah lewat dashboard.']);
    }

    public function test_rejects_a_file_another_document_uses(): void
    {
        $upload = $this->upload();
        $other = Document::factory()->create(['format' => 'PDF', 'berkas' => ['nama' => 'perda.pdf', 'ukuran' => 10, 'tipe' => 'application/pdf', 'url' => $upload->url()]]);
        $upload->markUsed();
        $document = Document::factory()->create();

        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['berkas' => ['url' => $upload->url()]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['berkas.url' => 'Berkas ini sudah dipakai dokumen lain. Unggah ulang berkasnya.']);
        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$other->id}", ['judul' => 'Judul baru', 'berkas' => ['url' => $upload->url()]])
            ->assertOk()
            ->assertJsonPath('judul', 'Judul baru');
    }

    public function test_keeps_the_file_in_use_and_deletes_the_ones_it_replaces_or_drops(): void
    {
        Storage::fake('public');
        $employee = $this->dokumenEmployee();
        [$first, $second] = [$this->upload(), $this->upload()];
        Storage::disk('public')->put($first->path, 'pdf');
        Storage::disk('public')->put($second->path, 'pdf');
        $id = $this->actingAs($employee)->postJson('/api/koleksi/dokumen', ['format' => 'PDF', 'berkas' => ['url' => $first->url()]] + $this->document())->json('id');

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumen/{$id}", ['judul' => 'Judul baru', 'berkas' => ['url' => $first->url()]])->assertOk();
        $this->assertModelExists($first);

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumen/{$id}", ['berkas' => ['url' => $second->url()]])->assertOk();
        $this->assertModelMissing($first);
        Storage::disk('public')->assertMissing($first->path);
        $this->assertNotNull($second->fresh()->dipakai_pada);

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumen/{$id}", ['berkas' => null])
            ->assertOk()
            ->assertJsonPath('berkas', null)
            ->assertJsonPath('format', 'PDF');
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_changes_a_draft_and_logs_only_real_changes(): void
    {
        $employee = $this->dokumenEmployee();
        $document = Document::factory()->create();

        $this->actingAs($employee)->patchJson("/api/koleksi/dokumen/{$document->id}", ['nomor' => '12'])
            ->assertOk()
            ->assertJsonPath('nomor', '12')
            ->assertJsonPath('status', 'draf');
        $this->actingAs($employee)->patchJson("/api/koleksi/dokumen/{$document->id}", ['nomor' => '12'])->assertOk();

        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['module' => 'dokumen', 'action' => 'memperbarui', 'target' => $document->judul]);
    }

    #[TestWith([WorkflowStatus::AwaitingApproval])]
    #[TestWith([WorkflowStatus::Published])]
    public function test_lets_only_administrators_change_a_document_awaiting_approval_or_published(WorkflowStatus $status): void
    {
        $document = Document::factory()->withStatus($status)->create();

        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['nomor' => '7'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Dokumen yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator.']);
        $this->actingAs(User::factory()->administrator()->create())->patchJson("/api/koleksi/dokumen/{$document->id}", ['nomor' => '7'])
            ->assertOk()
            ->assertJsonPath('nomor', '7')
            ->assertJsonPath('status', $status->value);
    }

    #[TestWith([WorkflowStatus::UnderReview])]
    #[TestWith([WorkflowStatus::Returned])]
    #[TestWith([WorkflowStatus::Archived])]
    public function test_lets_module_managers_change_documents_not_awaiting_approval_or_published(WorkflowStatus $status): void
    {
        $document = Document::factory()->withStatus($status)->create();

        $this->actingAs($this->dokumenEmployee())->patchJson("/api/koleksi/dokumen/{$document->id}", ['nomor' => '7'])
            ->assertOk()
            ->assertJsonPath('nomor', '7');
    }

    public function test_lets_only_administrators_delete_documents_with_their_files(): void
    {
        Storage::fake('public');
        $administrator = User::factory()->administrator()->create();
        $upload = $this->upload();
        Storage::disk('public')->put($upload->path, 'pdf');
        $withFile = Document::factory()->create(['format' => 'PDF', 'berkas' => ['nama' => 'perda.pdf', 'ukuran' => 10, 'tipe' => 'application/pdf', 'url' => $upload->url()]]);
        $withLink = Document::factory()->create();
        $other = $this->upload();

        $this->actingAs($this->dokumenEmployee())->postJson('/api/koleksi/dokumen/hapus', ['ids' => [$withFile->id]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus dokumen.']);
        $this->assertModelExists($upload);

        $this->actingAs($administrator)->postJson('/api/koleksi/dokumen/hapus', ['ids' => [$withFile->id, $withLink->id]])->assertNoContent();

        $this->assertModelMissing($withFile);
        $this->assertModelMissing($withLink);
        $this->assertModelMissing($upload);
        Storage::disk('public')->assertMissing($upload->path);
        $this->assertModelExists($other);
        $this->assertDatabaseHas('activities', ['module' => 'dokumen', 'action' => 'menghapus', 'target' => '2 entri']);
    }

    private function dokumenEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Dokumen)->create();
    }

    private function upload(string $type = 'application/pdf', string $name = 'perda.pdf'): Upload
    {
        return Upload::factory()->create(['tujuan' => UploadTarget::Dokumen, 'tipe' => $type, 'nama' => $name]);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        return [
            'judul' => 'Peraturan Gubernur NTT Nomor 9 Tahun 2024',
            'keterangan' => 'Tata Cara Pemungutan Pajak Daerah',
            'kategori' => 'regulasi',
            'format' => 'Tautan',
            'nomor' => '9',
            'tahun' => '2024',
            'berkas' => null,
            'tautan' => 'https://jdih.nttprov.go.id/pergub-9-2024',
            'penyedia' => 'Subbagian Tata Usaha',
            'pemeriksa' => 'Kepala Subbagian Tata Usaha',
        ];
    }
}
