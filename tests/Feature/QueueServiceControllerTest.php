<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Activity;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QueueServiceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_the_four_services_of_the_owner_s_answers_in_desk_order(): void
    {
        $response = $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create())->getJson('/api/antrean/layanan');

        $response->assertOk()->assertJsonCount(4)->assertJsonPath('*.kode', ['A', 'B', 'C', 'D']);
        $this->assertSame([
            ['kode' => 'A', 'nama' => 'Registrasi Ulang', 'cakupan' => 'Registrasi Ulang/Perpanjangan STNK 5 Tahun, Duplikat STNK', 'tahap' => ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'cetak-stnk', 'penyerahan'], 'aktif' => true],
            ['kode' => 'B', 'nama' => 'Pengesahan', 'cakupan' => 'Pajak Tahunan/Pengesahan STNK', 'tahap' => ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'cetak-stnk', 'penyerahan'], 'aktif' => true],
            ['kode' => 'C', 'nama' => 'BBN/Mutasi', 'cakupan' => 'Bea Balik Nama dan Mutasi', 'tahap' => ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-skpd', 'penyerahan'], 'aktif' => true],
            ['kode' => 'D', 'nama' => 'Pembayaran Online', 'cakupan' => 'Pajak yang sudah dibayar secara online', 'tahap' => ['pendaftaran', 'penetapan', 'kwitansi', 'pembayaran', 'cetak-stnk', 'penyerahan'], 'aktif' => true],
        ], array_map(fn (array $service): array => array_diff_key($service, ['id' => true]), $response->json()));
    }

    public function test_lets_only_administrators_change_a_service(): void
    {
        $service = QueueService::query()->where('kode', 'B')->sole();
        $input = ['nama' => 'Pengesahan STNK', 'cakupan' => '', 'tahap' => ['pendaftaran'], 'aktif' => true];

        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create())
            ->putJson("/api/antrean/layanan/{$service->id}", $input)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengubah layanan antrean.']);
        $this->assertSame('Pengesahan', $service->fresh()->nama);
    }

    public function test_saves_the_stages_in_flow_order_and_logs_a_change_once(): void
    {
        $administrator = User::factory()->administrator()->create();
        $service = QueueService::query()->where('kode', 'A')->sole();
        $printed = QueueTicket::factory()->create();
        $input = ['nama' => 'Registrasi Ulang 5 Tahun', 'cakupan' => 'Perpanjangan STNK', 'tahap' => ['penyerahan', 'pendaftaran', 'kwitansi'], 'aktif' => false];

        $this->actingAs($administrator)->putJson("/api/antrean/layanan/{$service->id}", $input)
            ->assertOk()
            ->assertExactJson([
                'id' => $service->id, 'kode' => 'A', 'nama' => 'Registrasi Ulang 5 Tahun', 'cakupan' => 'Perpanjangan STNK',
                'tahap' => ['pendaftaran', 'kwitansi', 'penyerahan'], 'aktif' => false,
            ]);
        $this->assertSame(['memperbarui', 'Layanan antrean A (Registrasi Ulang 5 Tahun)'], [Activity::query()->sole()->action, Activity::query()->sole()->target]);
        $this->assertSame(DashboardModule::Antrean, Activity::query()->sole()->module);

        // Tickets already printed keep their stages; the same values again log nothing
        $this->assertCount(7, $printed->fresh()->tahap_layanan);
        $this->actingAs($administrator)->putJson("/api/antrean/layanan/{$service->id}", $input)->assertOk();
        $this->assertSame(1, Activity::query()->count());
    }

    /**
     * @param  array<string, mixed>  $change
     */
    #[TestWith([['nama' => ''], 'nama', 'Tulis nama layanan.'])]
    #[TestWith([['nama' => "Nama\u{0007}"], 'nama', 'Nama layanan berisi karakter kontrol yang tidak diizinkan.'])]
    #[TestWith([['tahap' => []], 'tahap', 'Pilih sedikitnya satu tahap.'])]
    #[TestWith([['tahap' => ['loket-3']], 'tahap.0', 'Tahap layanan tidak dikenal.'])]
    #[TestWith([['tahap' => ['penetapan', 'penetapan']], 'tahap.1', 'Setiap tahap hanya dipilih sekali.'])]
    #[TestWith([['tahap' => ['a' => 'penetapan']], 'tahap', 'Tahap layanan tidak valid.'])]
    #[TestWith([['aktif' => 'ya'], 'aktif', 'Pilih apakah layanan aktif.'])]
    public function test_rejects_an_invalid_change(array $change, string $field, string $message): void
    {
        $service = QueueService::query()->where('kode', 'C')->sole();
        $input = [...['nama' => 'BBN/Mutasi', 'cakupan' => '', 'tahap' => ['pendaftaran'], 'aktif' => true], ...$change];

        $this->actingAs(User::factory()->administrator()->create())
            ->putJson("/api/antrean/layanan/{$service->id}", $input)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
    }

    public function test_accepts_an_empty_scope(): void
    {
        $service = QueueService::query()->where('kode', 'C')->sole();

        $this->actingAs(User::factory()->administrator()->create())
            ->putJson("/api/antrean/layanan/{$service->id}", ['nama' => 'BBN/Mutasi', 'cakupan' => ' ', 'tahap' => ['pendaftaran'], 'aktif' => true])
            ->assertOk()
            ->assertJsonPath('cakupan', '');
    }
}
