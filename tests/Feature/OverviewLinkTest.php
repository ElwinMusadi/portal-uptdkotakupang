<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\OverviewLink;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Pengaturan › Tautan Ikhtisar (D-099): administrators make and revoke the
 * links to the Ikhtisar page.
 */
class OverviewLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET', '/api/pengaturan/ikhtisar'])]
    #[TestWith(['POST', '/api/pengaturan/ikhtisar'])]
    #[TestWith(['DELETE', '/api/pengaturan/ikhtisar/01kaaaaaaaaaaaaaaaaaaaaaaa'])]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $this->json($method, $uri, ['nama' => 'Rapat staf'])->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['POST'])]
    #[TestWith(['DELETE'])]
    public function test_forbids_employees_other_than_administrators(string $method): void
    {
        $link = OverviewLink::factory()->create();
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();
        $uri = $method === 'DELETE' ? "/api/pengaturan/ikhtisar/{$link->id}" : '/api/pengaturan/ikhtisar';

        $this->actingAs($employee)->json($method, $uri, ['nama' => 'Rapat staf'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengatur tautan ikhtisar.']);
        $this->assertDatabaseCount('overview_links', 1);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_makes_a_link_shown_once_and_keeps_only_the_hash_of_its_token(): void
    {
        $administrator = User::factory()->administrator()->create(['name' => 'Admin Uji']);

        $response = $this->actingAs($administrator)->postJson('/api/pengaturan/ikhtisar', ['nama' => 'Rapat staf'])
            ->assertCreated()
            ->assertJsonPath('tautan.nama', 'Rapat staf')
            ->assertJsonPath('tautan.dibuatOleh', 'Admin Uji')
            ->assertJsonPath('tautan.terakhirDibuka', null);

        $this->assertSame(1, preg_match('/^'.preg_quote(url('/ikhtisar'), '/').'#k=([A-Za-z0-9]{48})$/', $response->json('alamat'), $match));
        $link = OverviewLink::query()->sole();
        $this->assertSame(hash('sha256', $match[1]), $link->token_hash);
        $this->assertSame(['id', 'nama', 'terakhirDibuka', 'dibuat', 'dibuatOleh'], array_keys($response->json('tautan')));
        $this->assertDatabaseHas('activities', ['module' => 'penerimaan', 'action' => 'menambahkan', 'target' => 'Tautan ikhtisar Rapat staf']);

        // The token opens the page's figures
        $this->withToken($match[1])->getJson('/api/ikhtisar')->assertOk();
    }

    public function test_lists_the_links_latest_first_without_their_tokens(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->travelTo('2026-11-20 01:00:00');
        $older = OverviewLink::factory()->create(['nama' => 'Grup WA pimpinan', 'terakhir_dibuka' => '2026-11-20 00:30:00']);
        $this->travelTo('2026-11-21 01:00:00');
        OverviewLink::factory()->create(['nama' => 'Rapat staf']);

        $response = $this->actingAs($administrator)->getJson('/api/pengaturan/ikhtisar')
            ->assertOk()
            ->assertJsonPath('*.nama', ['Rapat staf', 'Grup WA pimpinan'])
            ->assertJsonPath('1.terakhirDibuka', '2026-11-20T00:30:00+00:00');

        $this->assertStringNotContainsString($older->token_hash, $response->getContent());
        $this->assertStringNotContainsString('token', $response->getContent());
    }

    public function test_validates_the_name(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->postJson('/api/pengaturan/ikhtisar', ['nama' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Tulis nama tautan, mis. Rapat staf.']);
        $this->actingAs($administrator)->postJson('/api/pengaturan/ikhtisar', ['nama' => str_repeat('a', 61)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Nama tautan paling banyak 60 karakter.']);
        $this->actingAs($administrator)->postJson('/api/pengaturan/ikhtisar', ['nama' => "Rapat\u{0007}"])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Nama tautan berisi karakter kontrol yang tidak diizinkan.']);
        $this->assertDatabaseCount('overview_links', 0);
    }

    public function test_revokes_a_link_so_its_token_no_longer_opens_the_page(): void
    {
        $administrator = User::factory()->administrator()->create();
        $link = OverviewLink::factory()->withToken(str_repeat('a', 48))->create(['nama' => 'Rapat staf']);
        $this->withToken(str_repeat('a', 48))->getJson('/api/ikhtisar')->assertOk();

        $this->actingAs($administrator)->deleteJson("/api/pengaturan/ikhtisar/{$link->id}")->assertNoContent();

        $this->assertModelMissing($link);
        $this->assertDatabaseHas('activities', ['module' => 'penerimaan', 'action' => 'mencabut', 'target' => 'Tautan ikhtisar Rapat staf']);
        $this->withToken(str_repeat('a', 48))->getJson('/api/ikhtisar')->assertUnauthorized();
        $this->actingAs($administrator)->deleteJson("/api/pengaturan/ikhtisar/{$link->id}")->assertNotFound();
        $this->actingAs($administrator)->deleteJson('/api/pengaturan/ikhtisar/bukan-ulid')->assertNotFound();
    }
}
