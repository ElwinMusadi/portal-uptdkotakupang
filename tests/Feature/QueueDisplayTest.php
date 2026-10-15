<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Enums\QueueStage;
use App\Models\Activity;
use App\Models\QueueCounter;
use App\Models\QueueDisplay;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QueueDisplayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lets_only_administrators_set_up_and_revoke_displays(): void
    {
        $display = QueueDisplay::query()->create(['nama' => 'TV', 'token_hash' => QueueDisplay::hash('rahasia')]);
        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create());

        foreach ([['GET', '/api/antrean/layar'], ['POST', '/api/antrean/layar'], ['DELETE', "/api/antrean/layar/{$display->id}"]] as [$method, $uri]) {
            $this->json($method, $uri, ['nama' => 'TV'])->assertForbidden()->assertExactJson(['message' => 'Hanya Administrator yang dapat mengatur layar antrean.']);
        }
    }

    public function test_gives_a_setup_link_once_and_keeps_only_the_token_s_hash(): void
    {
        $this->travelTo('2026-10-15 01:00:00');
        $administrator = User::factory()->administrator()->create(['name' => 'Admin']);

        $response = $this->actingAs($administrator)->postJson('/api/antrean/layar', ['nama' => 'TV ruang tunggu']);

        $response->assertCreated()
            ->assertJsonPath('layar.nama', 'TV ruang tunggu')
            ->assertJsonPath('layar.dibuatOleh', 'Admin')
            ->assertJsonPath('layar.terakhirTerlihat', null);
        $this->assertMatchesRegularExpression('#^'.preg_quote(url('/layar'), '#').'\#k=[A-Za-z0-9]{48}$#', $response->json('tautan'));
        $token = substr($response->json('tautan'), -48);
        $display = QueueDisplay::query()->sole();
        $this->assertSame(hash('sha256', $token), $display->token_hash);
        $this->assertStringNotContainsString($token, (string) json_encode($this->actingAs($administrator)->getJson('/api/antrean/layar')->json()));
        $this->assertSame(['menambahkan', 'Layar antrean TV ruang tunggu'], [Activity::query()->sole()->action, Activity::query()->sole()->target]);
        $this->actingAs($administrator)->postJson('/api/antrean/layar', ['nama' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Tulis nama layar, mis. TV ruang tunggu.']);
    }

    public function test_shows_a_set_up_display_the_day_s_calls_the_counters_and_the_waiting_numbers(): void
    {
        $this->travelTo('2026-10-15 03:00:00');
        $receipt = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $registration = QueueCounter::query()->where('tahap', 'pendaftaran')->sole();
        $called = QueueTicket::factory()->calledTo($receipt)->create(['nomor' => 'A-007', 'tanggal' => '2026-10-14', 'nopol' => 'DH77A']);
        $earlier = QueueTicket::factory()->calledTo($registration)->create(['nomor' => 'B-001']);
        $first = $earlier->events()->forceCreate(['aksi' => 'dipanggil', 'tahap' => 'pendaftaran', 'queue_counter_id' => $registration->id, 'created_at' => '2026-10-15 01:00:00']);
        $last = $called->events()->forceCreate(['aksi' => 'dipanggil ulang', 'tahap' => 'kwitansi', 'queue_counter_id' => $receipt->id, 'created_at' => '2026-10-15 02:59:00']);
        $called->events()->forceCreate(['aksi' => 'dipanggil', 'tahap' => 'kwitansi', 'queue_counter_id' => $receipt->id, 'created_at' => '2026-10-14 15:59:00']);
        $called->events()->forceCreate(['aksi' => 'ditunda', 'tahap' => 'kwitansi', 'created_at' => '2026-10-15 02:00:00']);
        QueueTicket::factory()->count(2)->waitingAt(QueueStage::Pendaftaran)->create();
        QueueTicket::factory()->waitingAt(QueueStage::Penetapan)->create();
        QueueCounter::query()->where('tahap', 'penyerahan')->update(['aktif' => false]);

        $response = $this->display($this->displayToken());

        $response->assertOk()
            ->assertJsonPath('waktu', '2026-10-15T03:00:00+00:00')
            ->assertJsonPath('panggilan', [
                [
                    'id' => $last->id, 'nomor' => 'A-007', 'tanggal' => '2026-10-14', 'hariIni' => false, 'nopol' => 'DH 77 A', 'layanan' => 'Registrasi Ulang',
                    'loket' => ['nama' => 'Loket Kwitansi', 'suara' => 'Loket kwitansi'], 'waktu' => '2026-10-15T02:59:00+00:00',
                ],
                [
                    'id' => $first->id, 'nomor' => 'B-001', 'tanggal' => '2026-10-15', 'hariIni' => true, 'nopol' => 'DH 1234 AB', 'layanan' => 'Registrasi Ulang',
                    'loket' => ['nama' => 'Loket 1 – Pendaftaran', 'suara' => 'Loket satu, pendaftaran'], 'waktu' => '2026-10-15T01:00:00+00:00',
                ],
            ])
            ->assertJsonPath('loket.*.nama', ['Loket 1 – Pendaftaran', 'Loket Kwitansi'])
            ->assertJsonPath('loket.1.dipanggil.nomor', 'A-007')
            ->assertJsonPath('menunggu', [
                ['tahap' => 'pendaftaran', 'nama' => 'Loket 1 – Pendaftaran', 'jumlah' => 2],
                ['tahap' => 'kwitansi', 'nama' => 'Loket Kwitansi', 'jumlah' => 0],
                ['tahap' => 'penyerahan', 'nama' => 'Loket 2 – Penyerahan', 'jumlah' => 0],
            ]);
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_turns_away_a_display_without_a_valid_token_and_a_revoked_one(): void
    {
        $token = $this->displayToken();
        $administrator = User::factory()->administrator()->create();

        $this->getJson('/api/layar')->assertUnauthorized()->assertExactJson(['message' => 'Layar ini belum dipasang atau sudah dicabut.']);
        $this->display('salah')->assertUnauthorized();
        $this->display($token)->assertOk();

        $this->actingAs($administrator)->deleteJson('/api/antrean/layar/'.QueueDisplay::query()->sole()->id)->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->display($token)->assertUnauthorized();
        $this->assertSame('mencabut', Activity::query()->latest('id')->first()->action);
    }

    public function test_notes_when_a_display_was_last_seen_to_the_minute(): void
    {
        $token = $this->displayToken();
        $display = QueueDisplay::query()->sole();

        $this->travelTo('2026-10-15 01:00:00');
        $this->display($token);
        $this->assertSame('2026-10-15 01:00:00', $display->fresh()->terakhir_terlihat->toDateTimeString());
        $this->travelTo('2026-10-15 01:00:50');
        $this->display($token);
        $this->assertSame('2026-10-15 01:00:00', $display->fresh()->terakhir_terlihat->toDateTimeString());
        $this->travelTo('2026-10-15 01:01:01');
        $this->display($token);
        $this->assertSame('2026-10-15 01:01:01', $display->fresh()->terakhir_terlihat->toDateTimeString());
    }

    public function test_serves_the_display_page_kept_out_of_search(): void
    {
        $response = $this->get('/layar');

        $response->assertOk();
        $this->assertStringContainsString('<div data-react="layar-antrean">', $response->getContent());
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $response->getContent());
        $this->assertSame([], $response->headers->getCookies());
    }

    /**
     * A display set up directly, with its token.
     */
    private function displayToken(): string
    {
        QueueDisplay::query()->create(['nama' => 'TV', 'token_hash' => QueueDisplay::hash('token-layar-uji')]);

        return 'token-layar-uji';
    }

    private function display(string $token): TestResponse
    {
        return $this->getJson('/api/layar', ['Authorization' => "Bearer {$token}"]);
    }
}
