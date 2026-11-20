<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Pengaturan › Kuesioner (D-098): administrators open and close each
 * questionnaire at /kuesioner.
 */
class QuestionnaireControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{wajibPajak: array{dibuka: bool}, operator: array{dibuka: bool}}
     */
    private function states(bool $taxpayers, bool $operators): array
    {
        return ['wajibPajak' => ['dibuka' => $taxpayers], 'operator' => ['dibuka' => $operators]];
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/kuesioner', $this->states(true, false))->assertUnauthorized();
    }

    public function test_lets_every_employee_read_both_questionnaires_open_until_first_saved(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $this->actingAs($employee)->getJson('/api/pengaturan/kuesioner')
            ->assertOk()
            ->assertExactJson([
                'wajibPajak' => ['dibuka' => true, 'sejak' => null],
                'operator' => ['dibuka' => true, 'sejak' => null],
            ]);
    }

    public function test_forbids_employees_other_than_administrators_to_open_or_close_them(): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();

        $this->actingAs($employee)->putJson('/api/pengaturan/kuesioner', $this->states(true, false))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat membuka atau menutup kuesioner.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_closes_one_questionnaire_from_now_without_an_activity_entry(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->travelTo('2026-11-20 06:15:00');
        $states = $this->states(true, false);
        // Only the states are taken from the dashboard; the server keeps when each began
        $states['operator']['sejak'] = '2020-01-01T00:00:00+00:00';
        $states['lainnya'] = ['dibuka' => false];

        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', $states)
            ->assertOk()
            ->assertExactJson([
                'wajibPajak' => ['dibuka' => true, 'sejak' => null],
                'operator' => ['dibuka' => false, 'sejak' => '2026-11-20T06:15:00+00:00'],
            ]);

        $this->assertSame([
            'wajibPajak' => ['dibuka' => true, 'sejak' => null],
            'operator' => ['dibuka' => false, 'sejak' => '2026-11-20T06:15:00+00:00'],
        ], Setting::query()->find(Setting::QUESTIONNAIRE)->value);
        $this->assertDatabaseCount('activities', 0);
        $this->actingAs(User::factory()->create())->getJson('/api/pengaturan')->assertOk()->assertJsonStructure(['kuesioner']);
    }

    public function test_keeps_when_each_state_began_until_it_changes(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->travelTo('2026-11-20 06:15:00');
        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', $this->states(true, false))->assertOk();
        $saved = Setting::query()->find(Setting::QUESTIONNAIRE)->updated_at;

        // Saving the same states again changes nothing
        $this->travelTo('2026-11-21 01:00:00');
        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', $this->states(true, false))
            ->assertOk()
            ->assertJsonPath('operator.sejak', '2026-11-20T06:15:00+00:00');
        $this->assertEquals($saved, Setting::query()->find(Setting::QUESTIONNAIRE)->updated_at);

        // Reopening the operators' and closing the taxpayers' start both anew
        $this->travelTo('2026-11-22 02:30:00');
        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', $this->states(false, true))
            ->assertOk()
            ->assertExactJson([
                'wajibPajak' => ['dibuka' => false, 'sejak' => '2026-11-22T02:30:00+00:00'],
                'operator' => ['dibuka' => true, 'sejak' => '2026-11-22T02:30:00+00:00'],
            ]);
        $this->actingAs($administrator)->getJson('/api/pengaturan/kuesioner')
            ->assertJsonPath('wajibPajak.dibuka', false)
            ->assertJsonPath('operator.sejak', '2026-11-22T02:30:00+00:00');
    }

    public function test_validates_both_states(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', ['wajibPajak' => ['dibuka' => 'ya']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'wajibPajak.dibuka' => 'Status Kuesioner Wajib Pajak harus dibuka atau ditutup.',
                'operator' => 'Status Kuesioner Petugas Operator wajib diisi.',
            ]);
        // Only true and false, not 1 or "0"
        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', ['wajibPajak' => ['dibuka' => 1], 'operator' => ['dibuka' => '0']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['wajibPajak.dibuka', 'operator.dibuka']);
        $this->actingAs($administrator)->putJson('/api/pengaturan/kuesioner', ['wajibPajak' => true, 'operator' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'wajibPajak' => 'Status Kuesioner Wajib Pajak tidak valid.',
                'operator.dibuka' => 'Status Kuesioner Petugas Operator wajib diisi.',
            ]);
        $this->assertDatabaseCount('settings', 0);
    }
}
