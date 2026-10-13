<?php

namespace Tests\Feature;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $response = $this->getJson('/api/aktivitas');

        $response->assertUnauthorized();
    }

    public function test_lists_the_activity_of_the_users_modules_newest_first(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $editor = User::factory()->withModules(DashboardModule::Faq)->create(['name' => 'Maria Bria']);
        $this->record($editor, DashboardModule::Faq, 'menambahkan', 'Apakah pajak bisa diwakilkan?');
        $this->travel(5)->minutes();
        $newest = $this->record($editor, DashboardModule::Faq, 'menerbitkan', 'Apakah pajak bisa diwakilkan?');
        $this->record(User::factory()->administrator()->create(), DashboardModule::Berita, 'menambahkan', 'Pembayaran QRIS');

        $response = $this->actingAs($editor)->getJson('/api/aktivitas');

        $response->assertOk()->assertJsonCount(2)->assertJsonPath('0', [
            'id' => $newest,
            'waktu' => '2026-10-13T08:05:00+00:00',
            'oleh' => 'Maria Bria',
            'aksi' => 'menerbitkan',
            'modul' => 'Tanya Jawab',
            'target' => 'Apakah pajak bisa diwakilkan?',
        ]);
        $this->assertSame(['menerbitkan', 'menambahkan'], $response->json('*.aksi'));
    }

    public function test_shows_administrators_the_activity_of_every_module(): void
    {
        $editor = User::factory()->withModules(DashboardModule::Faq)->create();
        $this->record($editor, DashboardModule::Faq, 'menambahkan', 'A?');
        $this->record($editor, DashboardModule::Berita, 'menambahkan', 'B');

        $response = $this->actingAs(User::factory()->administrator()->create())->getJson('/api/aktivitas');

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_shows_nothing_to_an_employee_without_modules(): void
    {
        $this->record(User::factory()->administrator()->create(), DashboardModule::Faq, 'menambahkan', 'A?');

        $response = $this->actingAs(User::factory()->create())->getJson('/api/aktivitas');

        $response->assertOk()->assertExactJson([]);
    }

    public function test_returns_at_most_the_requested_number_of_entries(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->record($administrator, DashboardModule::Faq, 'menambahkan', 'A?');
        $this->travel(1)->minutes();
        $this->record($administrator, DashboardModule::Faq, 'menambahkan', 'B?');

        $response = $this->actingAs($administrator)->getJson('/api/aktivitas?limit=1');

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.target', 'B?');
    }

    public function test_shortens_a_long_target(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->record($administrator, DashboardModule::Faq, 'menambahkan', str_repeat('é', 200));

        $response = $this->actingAs($administrator)->getJson('/api/aktivitas');

        $response->assertOk()->assertJsonPath('0.target', str_repeat('é', 120).'...');
    }

    #[TestWith(['0'])]
    #[TestWith(['101'])]
    #[TestWith(['banyak'])]
    public function test_rejects_a_limit_outside_1_to_100_with_422(string $limit): void
    {
        $response = $this->actingAs(User::factory()->administrator()->create())->getJson("/api/aktivitas?limit={$limit}");

        $response->assertUnprocessable()->assertJsonValidationErrors(['limit' => 'Jumlah aktivitas harus 1 sampai 100.']);
    }

    private function record(User $user, DashboardModule $module, string $action, string $target): string
    {
        return app(RecordActivity::class)->handle($user, $module, $action, $target)->id;
    }
}
