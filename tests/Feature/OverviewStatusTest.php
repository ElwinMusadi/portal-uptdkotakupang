<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Activity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Pengaturan › Halaman Ikhtisar (D-100): administrators open the page at
 * /laporan-penerimaan to anyone and close it again.
 */
class OverviewStatusTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/ikhtisar', ['aktif' => true])->assertUnauthorized();
    }

    public function test_lets_every_employee_read_the_page_closed_until_first_opened(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $this->actingAs($employee)->getJson('/api/pengaturan/ikhtisar')
            ->assertOk()
            ->assertExactJson(['aktif' => false, 'sejak' => null, 'alamat' => url('/laporan-penerimaan')]);
        $this->getJson('/api/ikhtisar')->assertForbidden();
    }

    public function test_forbids_employees_other_than_administrators_to_open_or_close_it(): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();

        $this->actingAs($employee)->putJson('/api/pengaturan/ikhtisar', ['aktif' => true])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengaktifkan atau menonaktifkan halaman Ikhtisar.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_opens_and_closes_the_page_from_now_and_logs_each_change(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->travelTo('2026-11-23 01:15:00');

        // Only the state is taken from the dashboard; the server keeps when it began
        $this->actingAs($administrator)->putJson('/api/pengaturan/ikhtisar', ['aktif' => true, 'sejak' => '2020-01-01T00:00:00+00:00'])
            ->assertOk()
            ->assertExactJson(['aktif' => true, 'sejak' => '2026-11-23T01:15:00+00:00', 'alamat' => url('/laporan-penerimaan')]);
        $this->assertSame(['aktif' => true, 'sejak' => '2026-11-23T01:15:00+00:00'], Setting::query()->find(Setting::OVERVIEW)->value);
        $this->getJson('/api/ikhtisar')->assertOk();

        // Saving the same state again changes and logs nothing
        $this->travelTo('2026-11-23 02:00:00');
        $this->actingAs($administrator)->putJson('/api/pengaturan/ikhtisar', ['aktif' => true])
            ->assertOk()
            ->assertJsonPath('sejak', '2026-11-23T01:15:00+00:00');

        $this->travelTo('2026-11-23 03:30:00');
        $this->actingAs($administrator)->putJson('/api/pengaturan/ikhtisar', ['aktif' => false])
            ->assertOk()
            ->assertJsonPath('aktif', false)
            ->assertJsonPath('sejak', '2026-11-23T03:30:00+00:00');
        $this->getJson('/api/ikhtisar')->assertForbidden();

        $this->assertSame(
            [['penerimaan', 'mengaktifkan', 'Halaman Ikhtisar /laporan-penerimaan'], ['penerimaan', 'menonaktifkan', 'Halaman Ikhtisar /laporan-penerimaan']],
            Activity::query()->orderBy('created_at')->get()->map(fn (Activity $a): array => [$a->module->value, $a->action, $a->target])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    #[TestWith([[], 'Pilih aktif atau nonaktif.'])]
    #[TestWith([['aktif' => 'true'], 'Status halaman harus aktif atau nonaktif.'])]
    #[TestWith([['aktif' => 1], 'Status halaman harus aktif atau nonaktif.'])]
    public function test_validates_the_state(array $body, string $message): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->putJson('/api/pengaturan/ikhtisar', $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['aktif' => $message]);
        $this->assertDatabaseCount('settings', 0);
    }
}
