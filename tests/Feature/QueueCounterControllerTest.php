<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Activity;
use App\Models\QueueCounter;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QueueCounterControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @var array<string, mixed>
     */
    private const array INPUT = ['nama' => 'Loket 1A', 'tahap' => 'pendaftaran', 'layanan' => ['B', 'A'], 'namaSuara' => 'Loket satu A', 'aktif' => true];

    public function test_lists_one_counter_per_calling_stage_from_the_migration(): void
    {
        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create())
            ->getJson('/api/antrean/loket')
            ->assertOk()
            ->assertJsonPath('*.nama', ['Loket 1 – Pendaftaran', 'Loket Kwitansi', 'Loket 2 – Penyerahan'])
            ->assertJsonPath('*.tahap', ['pendaftaran', 'kwitansi', 'penyerahan'])
            ->assertJsonPath('*.layanan', [[], [], []])
            ->assertJsonPath('*.namaSuara', ['Loket satu, pendaftaran', 'Loket kwitansi', 'Loket dua, penyerahan']);
    }

    public function test_lets_only_administrators_arrange_counters(): void
    {
        $counter = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $this->actingAs(User::factory()->withModules(DashboardModule::Antrean)->create());

        foreach ([['POST', '/api/antrean/loket'], ['PUT', "/api/antrean/loket/{$counter->id}"], ['DELETE', "/api/antrean/loket/{$counter->id}"]] as [$method, $uri]) {
            $this->json($method, $uri, self::INPUT)->assertForbidden()->assertExactJson(['message' => 'Hanya Administrator yang dapat mengatur loket antrean.']);
        }
        $this->assertDatabaseCount('queue_counters', 3);
    }

    public function test_adds_and_changes_a_counter_and_logs_it(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = $this->actingAs($administrator)->postJson('/api/antrean/loket', self::INPUT);

        $response->assertCreated()->assertJson(['nama' => 'Loket 1A', 'tahap' => 'pendaftaran', 'layanan' => ['A', 'B'], 'namaSuara' => 'Loket satu A', 'aktif' => true]);
        $this->assertSame(3, QueueCounter::query()->findOrFail($response->json('id'))->urutan);
        $this->actingAs($administrator)->putJson("/api/antrean/loket/{$response->json('id')}", [...self::INPUT, 'layanan' => [], 'aktif' => false])
            ->assertOk()
            ->assertJsonPath('layanan', [])
            ->assertJsonPath('aktif', false);
        $this->actingAs($administrator)->putJson("/api/antrean/loket/{$response->json('id')}", [...self::INPUT, 'layanan' => [], 'aktif' => false])->assertOk();
        $this->assertSame(
            [['menambahkan', 'Loket antrean Loket 1A'], ['memperbarui', 'Loket antrean Loket 1A']],
            Activity::query()->oldest('id')->get()->map(fn (Activity $activity): array => [$activity->action, $activity->target])->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $change
     */
    #[TestWith([['nama' => ''], 'nama', 'Tulis nama loket.'])]
    #[TestWith([['tahap' => 'penetapan'], 'tahap', 'Loket hanya untuk Loket 1 – Pendaftaran, Loket Kwitansi, atau Loket 2 – Penyerahan.'])]
    #[TestWith([['layanan' => ['Z']], 'layanan.0', 'Layanan ini tidak ada.'])]
    #[TestWith([['layanan' => ['A', 'A']], 'layanan.1', 'Setiap layanan hanya dipilih sekali.'])]
    #[TestWith([['namaSuara' => ''], 'namaSuara', 'Tulis nama loket yang dibacakan di layar.'])]
    #[TestWith([['aktif' => 'ya'], 'aktif', 'Pilih apakah loket dipakai.'])]
    public function test_rejects_an_invalid_counter(array $change, string $field, string $message): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->postJson('/api/antrean/loket', [...self::INPUT, ...$change])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
    }

    public function test_removing_a_counter_puts_aside_the_number_it_calls(): void
    {
        $counter = QueueCounter::query()->where('tahap', 'kwitansi')->sole();
        $called = QueueTicket::factory()->calledTo($counter)->create();
        $called->events()->create(['aksi' => 'dipanggil', 'tahap' => 'kwitansi', 'queue_counter_id' => $counter->id]);

        $this->actingAs(User::factory()->administrator()->create())->deleteJson("/api/antrean/loket/{$counter->id}")->assertNoContent();

        $this->assertModelMissing($counter);
        $this->assertSame(['ditunda', null], [$called->fresh()->status->value, $called->fresh()->queue_counter_id]);
        $this->assertDatabaseHas('queue_events', ['queue_ticket_id' => $called->id, 'aksi' => 'dipanggil', 'queue_counter_id' => null]);
        $this->assertSame(['menghapus', 'Loket antrean Loket Kwitansi'], [Activity::query()->sole()->action, Activity::query()->sole()->target]);
    }
}
