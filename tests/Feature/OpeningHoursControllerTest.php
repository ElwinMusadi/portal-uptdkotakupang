<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OpeningHoursControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/jam', $this->openingHours())->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Keliling)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/jam', $this->openingHours());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Jam Pelayanan belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_every_day_closed_until_the_hours_are_first_saved(): void
    {
        $response = $this->actingAs($this->jamEmployee())->getJson('/api/pengaturan/jam');

        $response->assertOk()
            ->assertJsonPath('batasBerkasMenit', 60)
            ->assertJsonPath('hari.0', ['hari' => 1, 'buka' => false, 'mulai' => '', 'selesai' => '', 'catatan' => ''])
            ->assertJsonPath('hari.6.hari', 0);
        $this->assertSame([false], array_values(array_unique($response->json('hari.*.buka'))));
    }

    public function test_saves_the_hours_in_weekday_order_without_times_on_closed_days_and_logs_it(): void
    {
        $employee = $this->jamEmployee();
        $hours = $this->openingHours();
        $hours['hari'] = array_reverse($hours['hari']);
        $hours['hari'][0] = ['hari' => 0, 'buka' => false, 'mulai' => '08:00', 'selesai' => '12:00', 'catatan' => null];
        $hours['pemberitahuan'] = null;

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/jam', $hours);

        $response->assertOk()
            ->assertJsonPath('hari.0.hari', 1)
            ->assertJsonPath('hari.6', ['hari' => 0, 'buka' => false, 'mulai' => '', 'selesai' => '', 'catatan' => ''])
            ->assertJsonPath('pemberitahuan', '');
        $this->assertSame($response->json(), Setting::openingHours());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'jam',
            'action' => 'memperbarui',
            'target' => 'Jam Pelayanan',
        ]);
        $this->actingAs($employee)->getJson('/api/pengaturan/jam')->assertExactJson($response->json());
    }

    public function test_logs_nothing_when_the_same_hours_are_saved_again(): void
    {
        $employee = $this->jamEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/jam', $this->openingHours())->assertOk();

        $this->actingAs($employee)->putJson('/api/pengaturan/jam', $this->openingHours())->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_requires_both_times_on_an_open_day_and_closing_after_opening(): void
    {
        $hours = $this->openingHours();
        $hours['hari'][0]['mulai'] = '';
        $hours['hari'][1]['selesai'] = '08:00';

        $response = $this->actingAs($this->jamEmployee())->putJson('/api/pengaturan/jam', $hours);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'hari.0.mulai' => 'Isi jam buka.',
            'hari.1.selesai' => 'Jam tutup harus setelah jam buka.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_requires_seven_different_days(): void
    {
        $hours = $this->openingHours();
        $hours['hari'][6]['hari'] = 1;
        $short = $this->openingHours();
        array_pop($short['hari']);
        $employee = $this->jamEmployee();

        $this->actingAs($employee)->putJson('/api/pengaturan/jam', $hours)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hari.6.hari' => 'Setiap hari hanya boleh muncul sekali.']);
        $this->actingAs($employee)->putJson('/api/pengaturan/jam', $short)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hari' => 'Jam per hari harus berisi tujuh hari.']);
    }

    public function test_limits_the_document_deadline_to_240_minutes(): void
    {
        $response = $this->actingAs($this->jamEmployee())->putJson('/api/pengaturan/jam', ['batasBerkasMenit' => 241] + $this->openingHours());

        $response->assertUnprocessable()->assertJsonValidationErrors(['batasBerkasMenit' => 'Batas penerimaan berkas 0 sampai 240 menit.']);
    }

    public function test_tells_every_employee_when_server_settings_were_last_saved(): void
    {
        $employee = User::factory()->create();

        $this->getJson('/api/pengaturan')->assertUnauthorized();
        $this->actingAs($employee)->getJson('/api/pengaturan')->assertOk()->assertExactJson([]);
        $this->assertSame('{}', $this->actingAs($employee)->getJson('/api/pengaturan')->getContent());

        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $this->actingAs($this->jamEmployee())->putJson('/api/pengaturan/jam', $this->openingHours())->assertOk();

        $this->actingAs($employee)->getJson('/api/pengaturan')->assertExactJson(['jam' => '2026-10-13T08:00:00+00:00']);
    }

    private function jamEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Jam)->create();
    }

    /**
     * @return array{hari: list<array{hari: int, buka: bool, mulai: string, selesai: string, catatan: string}>, batasBerkasMenit: int, catatan: string, pemberitahuan: string}
     */
    private function openingHours(): array
    {
        $day = fn (int $hari, string $mulai, string $selesai, string $catatan = ''): array => [
            'hari' => $hari,
            'buka' => $mulai !== '',
            'mulai' => $mulai,
            'selesai' => $selesai,
            'catatan' => $catatan,
        ];

        return [
            'hari' => [
                $day(1, '08:00', '15:00', 'Berkas s.d. 14.00'),
                $day(2, '08:00', '15:00', 'Berkas s.d. 14.00'),
                $day(3, '08:00', '15:00'),
                $day(4, '08:00', '15:00'),
                $day(5, '08:00', '11:30'),
                $day(6, '08:00', '12:00', 'Pajak tahunan saja'),
                $day(0, '', ''),
            ],
            'batasBerkasMenit' => 60,
            'catatan' => 'Antrean biasanya paling ramai pukul 09.00–11.00.',
            'pemberitahuan' => 'Perubahan jadwal diumumkan paling lambat H-3.',
        ];
    }
}
