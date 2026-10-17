<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\WorkflowStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ServiceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/koleksi/layanan')->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/koleksi/layanan'])]
    #[TestWith(['POST', '/api/koleksi/layanan'])]
    public function test_forbids_employees_without_the_module_with_403(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $response = $this->actingAs($employee)->json($method, $uri, $this->service());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Persyaratan Layanan belum ditugaskan kepada Anda.']);
    }

    public function test_lets_update_schedule_managers_list_but_not_change_services(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Pemutakhiran)->create();
        $service = Service::query()->firstOrFail();

        $this->actingAs($employee)->getJson('/api/koleksi/layanan')->assertOk()->assertJsonCount(6);
        $this->actingAs($employee)->getJson("/api/koleksi/layanan/{$service->id}")->assertForbidden();
        $this->actingAs($employee)->postJson('/api/koleksi/layanan', $this->service())->assertForbidden();
    }

    public function test_lists_the_initial_published_services_in_portal_order(): void
    {
        $response = $this->actingAs($this->layananEmployee())->getJson('/api/koleksi/layanan');

        $response->assertOk()
            ->assertJsonPath('*.slug', ['pajak-tahunan', 'lima-tahun', 'mutasi-tetap', 'mutasi-bbn', 'duplikat', 'badan-hukum'])
            ->assertJsonPath('*.status', array_fill(0, 6, 'terbit'))
            ->assertJsonPath('0.nama', 'Pajak tahunan')
            ->assertJsonPath('0.persyaratan.0', ['id' => 'p1', 'dokumen' => 'Fotokopi STNK dan notis pajak (lampirkan asli)', 'jumlah' => '2 lembar'])
            ->assertJsonPath('0.riwayat.0.oleh', 'PINTU')
            ->assertJsonPath('1.label', ['Kuasa: meterai Rp10.000'])
            ->assertJsonPath('5.jenis', 'tambahan')
            ->assertJsonPath('*.unggulan', [true, true, true, false, false, false]);
        $this->assertNotNull($response->json('0.terbitPada'));
    }

    public function test_adds_a_draft_at_the_end_whatever_workflow_fields_are_sent(): void
    {
        $employee = $this->layananEmployee();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/layanan', [
            'status' => 'terbit',
            'terbitPada' => '2026-01-01T00:00:00Z',
            'riwayat' => [['waktu' => '2026-01-01T00:00:00Z', 'oleh' => 'Orang lain', 'aksi' => 'menerbitkan', 'ke' => 'terbit']],
            'catatanRevisi' => 'abaikan',
        ] + $this->service());

        $response->assertCreated()
            ->assertJsonPath('status', 'draf')
            ->assertJsonPath('terbitPada', null)
            ->assertJsonPath('catatanRevisi', '')
            ->assertJsonPath('urutan', 6)
            ->assertJsonCount(1, 'riwayat')
            ->assertJsonPath('riwayat.0.oleh', $employee->name)
            ->assertJsonPath('riwayat.0.aksi', 'menyiapkan draf')
            ->assertJsonPath('riwayat.0.ke', 'draf');
        $this->assertDatabaseHas('activities', ['user_id' => $employee->id, 'module' => 'layanan', 'action' => 'menambahkan', 'target' => 'Balik nama dalam daerah']);
    }

    public function test_stores_empty_optional_fields_as_empty_and_drops_empty_labels_and_situations(): void
    {
        $service = ['catatan' => null, 'pemeriksa' => null, 'label' => ['Meterai Rp10.000', ''], 'situasi' => ['', 'Membeli kendaraan bekas di Kota Kupang', null]] + $this->service();
        $service['persyaratan'][0]['jumlah'] = null;

        $response = $this->actingAs($this->layananEmployee())->postJson('/api/koleksi/layanan', $service);

        $response->assertCreated()
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('pemeriksa', '')
            ->assertJsonPath('label', ['Meterai Rp10.000'])
            ->assertJsonPath('situasi', ['Membeli kendaraan bekas di Kota Kupang'])
            ->assertJsonPath('persyaratan.0.jumlah', '');
    }

    public function test_starts_a_service_sent_without_situations_with_none(): void
    {
        $this->actingAs($this->layananEmployee())->postJson('/api/koleksi/layanan', $this->service())
            ->assertCreated()
            ->assertJsonPath('situasi', []);
    }

    public function test_validates_the_situations(): void
    {
        $tooMany = $this->actingAs($this->layananEmployee())->postJson('/api/koleksi/layanan', ['situasi' => ['1', '2', '3', '4', '5', '6', '7']] + $this->service());
        $tooLong = $this->postJson('/api/koleksi/layanan', ['situasi' => [str_repeat('a', 101), "STNK\u{0007}hilang"]] + $this->service());

        $tooMany->assertUnprocessable()->assertJsonValidationErrors(['situasi' => 'Maksimal 6 situasi.']);
        $tooLong->assertUnprocessable()->assertJsonValidationErrors([
            'situasi.0' => 'Situasi maksimal 100 karakter.',
            'situasi.1' => 'Situasi berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('services', 6);
    }

    public function test_changes_the_situations_of_a_draft(): void
    {
        $service = Service::factory()->create(['situasi' => ['STNK hilang']]);

        $this->actingAs($this->layananEmployee())->patchJson("/api/koleksi/layanan/{$service->id}", ['situasi' => ['STNK hilang', 'STNK rusak']])
            ->assertOk()
            ->assertJsonPath('situasi', ['STNK hilang', 'STNK rusak']);
        $this->assertSame(['STNK hilang', 'STNK rusak'], $service->fresh()->situasi);
    }

    public function test_validates_a_new_service(): void
    {
        $service = $this->service();
        $service['slug'] = 'pajak-tahunan';
        $service['ringkasan'] = str_repeat('a', 201);
        $service['jenis'] = 'lainnya';
        $service['persyaratan'] = [];
        $service['label'] = ['1', '2', '3', '4', '5', '6', '7'];
        $service['penyedia'] = '';

        $response = $this->actingAs($this->layananEmployee())->postJson('/api/koleksi/layanan', $service);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'slug' => 'Slug sudah dipakai layanan lain.',
            'ringkasan' => 'Ringkasan maksimal 200 karakter.',
            'jenis' => 'Jenis layanan tidak valid.',
            'persyaratan' => 'Tambahkan minimal satu berkas persyaratan.',
            'label' => 'Maksimal 6 label.',
            'penyedia' => 'Unit penyedia wajib diisi.',
        ]);
        $this->assertDatabaseCount('services', 6);
    }

    public function test_validates_the_slug_and_requirements(): void
    {
        $service = $this->service();
        $service['slug'] = 'Balik Nama';
        $service['persyaratan'] = [
            ['id' => 'p1', 'dokumen' => "Fotokopi\u{0007}", 'jumlah' => '1 lembar'],
            ['id' => 'p1', 'dokumen' => '', 'jumlah' => ''],
        ];

        $response = $this->actingAs($this->layananEmployee())->postJson('/api/koleksi/layanan', $service);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'slug' => 'Gunakan huruf kecil, angka, dan tanda hubung.',
            'persyaratan.0.dokumen' => 'Nama berkas berisi karakter kontrol yang tidak diizinkan.',
            'persyaratan.1.id' => 'Berkas persyaratan tercantum dua kali.',
            'persyaratan.1.dokumen' => 'Nama berkas wajib diisi.',
        ]);
    }

    public function test_changes_a_draft_and_logs_only_real_changes(): void
    {
        $employee = $this->layananEmployee();
        $service = Service::factory()->create(['slug' => 'balik-nama']);

        $this->actingAs($employee)->patchJson("/api/koleksi/layanan/{$service->id}", ['loket' => 'Loket 4', 'slug' => 'balik-nama'])
            ->assertOk()
            ->assertJsonPath('loket', 'Loket 4')
            ->assertJsonPath('status', 'draf');
        $this->actingAs($employee)->patchJson("/api/koleksi/layanan/{$service->id}", ['loket' => 'Loket 4'])->assertOk();

        $this->assertDatabaseCount('activities', 1);
        $this->assertDatabaseHas('activities', ['action' => 'memperbarui', 'target' => $service->nama]);
    }

    #[TestWith([WorkflowStatus::AwaitingApproval])]
    #[TestWith([WorkflowStatus::Published])]
    public function test_lets_only_administrators_change_a_service_awaiting_approval_or_published(WorkflowStatus $status): void
    {
        $service = Service::factory()->withStatus($status)->create();

        $this->actingAs($this->layananEmployee())->patchJson("/api/koleksi/layanan/{$service->id}", ['loket' => 'Loket 9'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Layanan yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator.']);
        $this->actingAs(User::factory()->administrator()->create())->patchJson("/api/koleksi/layanan/{$service->id}", ['loket' => 'Loket 9'])
            ->assertOk()
            ->assertJsonPath('loket', 'Loket 9')
            ->assertJsonPath('status', $status->value);
    }

    #[TestWith([WorkflowStatus::UnderReview])]
    #[TestWith([WorkflowStatus::Returned])]
    #[TestWith([WorkflowStatus::Archived])]
    public function test_lets_module_managers_change_services_not_awaiting_approval_or_published(WorkflowStatus $status): void
    {
        $service = Service::factory()->withStatus($status)->create();

        $this->actingAs($this->layananEmployee())->patchJson("/api/koleksi/layanan/{$service->id}", ['loket' => 'Loket 9'])
            ->assertOk()
            ->assertJsonPath('loket', 'Loket 9');
    }

    private function layananEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Layanan)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function service(): array
    {
        return [
            'nama' => 'Balik nama dalam daerah',
            'slug' => 'balik-nama-dalam-daerah',
            'ringkasan' => 'Ganti pemilik kendaraan di Kota Kupang.',
            'loket' => 'Loket 3',
            'jenis' => 'layanan',
            'cekFisik' => true,
            'nonTunai' => false,
            'diwakilkan' => true,
            'label' => ['Meterai Rp10.000'],
            'persyaratan' => [['id' => 'p1', 'dokumen' => 'Fotokopi KTP pemilik baru', 'jumlah' => '2 lembar']],
            'catatan' => 'Bawa kuitansi pembelian.',
            'unggulan' => false,
            'penyedia' => 'Seksi Verifikasi',
            'pemeriksa' => 'Kepala Seksi Verifikasi',
        ];
    }
}
