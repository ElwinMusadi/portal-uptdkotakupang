<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StatisticsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/statistik', $this->statistics())->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Tarif)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/statistik', $this->statistics());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Statistik Layanan belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_four_categories_with_zero_figures_as_an_illustration_until_first_saved(): void
    {
        // Already 2027 in Kupang (WITA, UTC+8).
        $this->travelTo(Carbon::parse('2026-12-31 16:30:00', 'UTC'));

        $response = $this->actingAs($this->statistikEmployee())->getJson('/api/pengaturan/statistik');

        $response->assertOk()
            ->assertJsonPath('status', 'ilustrasi')
            ->assertJsonPath('tahun', '2027')
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('set.*.kunci', ['transaksi', 'penerimaan', 'keliling', 'kepuasan'])
            ->assertJsonPath('set.1.tiles.0', ['id' => 'penerimaan-1', 'label' => 'Penerimaan PKB', 'nilai' => 0, 'satuan' => 'M', 'perubahan' => 0, 'naikBaik' => true])
            ->assertJsonPath('set.3.tiles.1.naikBaik', false)
            ->assertJsonPath('set.3.seri', array_fill(0, 12, 0));
        foreach ($response->json('set') as $category) {
            $this->assertCount(4, $category['tiles']);
            $this->assertSame([0], array_values(array_unique(array_column($category['tiles'], 'nilai'))));
        }
    }

    public function test_saves_the_statistics_and_logs_it(): void
    {
        $employee = $this->statistikEmployee();
        $statistics = $this->statistics();
        $statistics['catatan'] = null;
        $statistics['set'][3]['sub'] = null;
        $statistics['set'][3]['tiles'][1]['satuan'] = ' mnt';

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/statistik', $statistics);

        $response->assertOk()
            ->assertJsonPath('catatan', '')
            ->assertJsonPath('set.3.sub', '')
            ->assertJsonPath('set.3.tiles.1.satuan', 'mnt')
            ->assertJsonPath('set.0.tiles.0.satuan', '')
            ->assertJsonPath('set.1.tiles.0.nilai', 18.4)
            ->assertJsonPath('set.0.seri.0', 880);
        $this->assertSame($response->json(), Setting::statistics());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'statistik',
            'action' => 'memperbarui',
            'target' => 'Statistik Layanan',
        ]);
    }

    public function test_logs_nothing_when_the_same_statistics_are_saved_again(): void
    {
        $employee = $this->statistikEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/statistik', $this->statistics())->assertOk();
        $statistics = $this->statistics();
        // A whole number sent as text or with a decimal point is the same figure.
        $statistics['set'][0]['tiles'][0]['nilai'] = '12480.0';

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/statistik', $statistics);

        $response->assertOk()->assertJsonPath('set.0.tiles.0.nilai', 12480);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_validates_the_status_year_note_and_figures(): void
    {
        $statistics = $this->statistics();
        $statistics['status'] = 'terbit';
        $statistics['tahun'] = '26';
        $statistics['catatan'] = "Catatan\u{0007}";
        $statistics['set'][0]['label'] = '';
        $statistics['set'][1]['tiles'][2]['nilai'] = 'banyak';
        $statistics['set'][2]['tiles'][0]['perubahan'] = 1001;
        $statistics['set'][3]['seri'][5] = -1;

        $response = $this->actingAs($this->statistikEmployee())->putJson('/api/pengaturan/statistik', $statistics);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'status' => 'Status data tidak valid.',
            'tahun' => 'Tahun 4 angka.',
            'catatan' => 'Catatan berisi karakter kontrol yang tidak diizinkan.',
            'set.0.label' => 'Nama tab wajib diisi.',
            'set.1.tiles.2.nilai' => 'Nilai angka utama harus berupa angka.',
            'set.2.tiles.0.perubahan' => 'Perubahan -1.000 sampai 1.000%.',
            'set.3.seri.5' => 'Nilai bulanan tidak boleh negatif atau terlalu besar.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_requires_the_four_known_categories_with_four_tiles_and_twelve_months(): void
    {
        $statistics = $this->statistics();
        $statistics['set'][1]['kunci'] = 'transaksi';
        $statistics['set'][2]['kunci'] = 'retribusi';
        array_pop($statistics['set'][0]['tiles']);
        array_pop($statistics['set'][3]['seri']);

        $response = $this->actingAs($this->statistikEmployee())->putJson('/api/pengaturan/statistik', $statistics);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'set.1.kunci' => 'Kategori tercantum dua kali.',
            'set.2.kunci' => 'Kategori tidak dikenal.',
            'set.0.tiles' => 'Setiap kategori berisi 4 angka utama.',
            'set.3.seri' => 'Data bulanan berisi 12 nilai, Januari sampai Desember.',
        ]);

        $statistics = $this->statistics();
        array_pop($statistics['set']);

        $this->actingAs($this->statistikEmployee())->putJson('/api/pengaturan/statistik', $statistics)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['set' => 'Statistik harus berisi 4 kategori.']);
        $this->assertDatabaseCount('settings', 0);
    }

    private function statistikEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Statistik)->create();
    }

    /**
     * The unsaved document with figures filled in, as the dashboard sends it.
     *
     * @return array<string, mixed>
     */
    private function statistics(): array
    {
        $statistics = Setting::statistics();
        $statistics['status'] = 'terverifikasi';
        $statistics['tahun'] = '2026';
        $statistics['catatan'] = 'Angka triwulan III telah disetujui pimpinan.';
        $statistics['set'][0]['tiles'][0]['nilai'] = 12480;
        $statistics['set'][0]['tiles'][0]['perubahan'] = 4.2;
        $statistics['set'][1]['tiles'][0]['nilai'] = 18.4;
        $statistics['set'][3]['tiles'][1]['perubahan'] = -6;
        $statistics['set'][0]['seri'] = [880, 910, 1020, 960, 1040, 1100, 990, 1080, 1150, 1120, 1060, 1160];

        return $statistics;
    }
}
