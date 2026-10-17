<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\ThemeRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ThemeRevisionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $this->getJson('/api/pengaturan/tema/riwayat')->assertUnauthorized();
    }

    public function test_shows_the_history_only_to_administrators(): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();

        $this->actingAs($employee)->getJson('/api/pengaturan/tema/riwayat')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengubah Tampilan.']);
    }

    public function test_keeps_each_changed_theme_with_who_saved_it_newest_first(): void
    {
        $this->freezeTime();
        $first = User::factory()->administrator()->create(['name' => 'Admin Pertama']);
        $second = User::factory()->administrator()->create(['name' => 'Admin Kedua']);

        $this->saveTheme($first, ['aksen' => '#2F7D4F']);
        $this->travel(1)->minutes();
        $this->saveTheme($second, ['aksen' => '#2F7D4F']);
        $this->saveTheme($second, ['aksen' => '#C2410C', 'skala' => 110]);

        $response = $this->actingAs($first)->getJson('/api/pengaturan/tema/riwayat');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.oleh', 'Admin Kedua')
            ->assertJsonPath('0.tema.portal.aksen', '#C2410C')
            ->assertJsonPath('0.tema.portal.skala', 110)
            ->assertJsonPath('0.tema.dashboard', Setting::DEFAULT_THEME_SECTION)
            ->assertJsonPath('1.oleh', 'Admin Pertama')
            ->assertJsonPath('1.tema.portal.aksen', '#2F7D4F');
        $this->assertSame(now()->toIso8601String(), $response->json('0.disimpan'));
    }

    public function test_keeps_only_the_last_five_themes(): void
    {
        $administrator = User::factory()->administrator()->create();

        foreach (['#111111', '#222222', '#333333', '#444444', '#555555', '#666666', '#777777'] as $accent) {
            $this->saveTheme($administrator, ['aksen' => $accent]);
        }

        $this->assertSame(ThemeRevision::KEPT, ThemeRevision::query()->count());
        $this->assertSame(
            ['#777777', '#666666', '#555555', '#444444', '#333333'],
            array_column(array_column(array_column($this->actingAs($administrator)->getJson('/api/pengaturan/tema/riwayat')->json(), 'tema'), 'portal'), 'aksen'),
        );
    }

    public function test_shows_a_theme_saved_before_later_settings_with_their_built_in_values_and_a_deleted_saver(): void
    {
        $revision = ThemeRevision::factory()->create([
            'tema' => ['portal' => ['aksen' => '#2F7D4F', 'latar' => 'netral', 'hurufTeks' => 'inter', 'hurufJudul' => 'sama', 'sudut' => 'tegas'], 'dashboard' => Setting::DEFAULT_THEME_SECTION],
        ]);
        $revision->user->delete();

        $response = $this->actingAs(User::factory()->administrator()->create())->getJson('/api/pengaturan/tema/riwayat');

        $response->assertOk()
            ->assertJsonPath('0.tema.portal.skala', 100)
            ->assertJsonPath('0.tema.portal.mode', 'terang')
            ->assertJsonPath('0.tema.portal.tebalJudul', 600)
            ->assertJsonPath('0.tema.portal.sudut', 'tegas')
            ->assertJsonPath('0.oleh', null);
    }

    public function test_reads_a_document_saved_before_the_scale_with_the_built_in_scale(): void
    {
        $section = ['aksen' => '#2F7D4F', 'latar' => 'hangat', 'hurufTeks' => 'sistem', 'hurufJudul' => 'sama', 'sudut' => 'sedang'];
        (new Setting)->forceFill(['name' => Setting::THEME, 'value' => ['portal' => $section, 'dashboard' => $section]])->save();

        $this->actingAs(User::factory()->create())->getJson('/api/pengaturan/tema')
            ->assertOk()
            ->assertJsonPath('portal.skala', 100)
            ->assertJsonPath('portal.mode', 'terang')
            ->assertJsonPath('portal.tebalJudul', 600)
            ->assertJsonPath('dashboard.tebalJudul', 600)
            ->assertJsonMissingPath('dashboard.mode')
            ->assertJsonPath('dashboard.aksen', '#2F7D4F');
    }

    /**
     * Save a theme whose portal part differs from the built-in look.
     *
     * @param  array<string, string|int>  $portal
     */
    private function saveTheme(User $administrator, array $portal): void
    {
        $this->actingAs($administrator)->putJson('/api/pengaturan/tema', [
            'portal' => [...Setting::DEFAULT_PORTAL_THEME, ...$portal],
            'dashboard' => Setting::DEFAULT_THEME_SECTION,
        ])->assertOk();
    }
}
