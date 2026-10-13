<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FaqOrderControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        [$a, $b] = $this->entries('A?', 'B?');

        $response = $this->putJson('/api/koleksi/faq/urutan', ['ids' => [$b->id, $a->id]]);

        $response->assertUnauthorized();
        $this->assertSame(['A?', 'B?'], $this->questionsInOrder());
    }

    public function test_forbids_employees_without_the_faq_module_with_403(): void
    {
        [$a, $b] = $this->entries('A?', 'B?');
        $employee = User::factory()->withModules(DashboardModule::Berita)->create();

        $response = $this->actingAs($employee)->putJson('/api/koleksi/faq/urutan', ['ids' => [$b->id, $a->id]]);

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Tanya Jawab belum ditugaskan kepada Anda.']);
        $this->assertSame(['A?', 'B?'], $this->questionsInOrder());
    }

    public function test_puts_the_listed_entries_first_and_the_others_after_them_with_204(): void
    {
        [$a, , $c] = $this->entries('A?', 'B?', 'C?', 'D?');

        $response = $this->actingAs($this->faqEmployee())->putJson('/api/koleksi/faq/urutan', ['ids' => [$c->id, $a->id]]);

        $response->assertNoContent();
        $this->assertSame(['C?', 'A?', 'B?', 'D?'], $this->questionsInOrder());
        $this->assertSame([0, 1, 2, 3], Faq::query()->orderBy('urutan')->pluck('urutan')->all());
    }

    public function test_does_not_count_reordering_as_an_edit(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        [$a, $b] = $this->entries('A?', 'B?');
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));

        $this->actingAs($this->faqEmployee())->putJson('/api/koleksi/faq/urutan', ['ids' => [$b->id, $a->id]])->assertNoContent();

        $this->assertSame('2026-10-13 08:00:00', $a->fresh()->updated_at->toDateTimeString());
    }

    public function test_logs_the_reordering(): void
    {
        [$a, $b] = $this->entries('A?', 'B?');
        $employee = $this->faqEmployee();

        $this->actingAs($employee)->putJson('/api/koleksi/faq/urutan', ['ids' => [$b->id, $a->id]]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'faq',
            'action' => 'mengubah urutan',
            'target' => '2 entri',
        ]);
    }

    public function test_rejects_an_entry_that_no_longer_exists_with_422(): void
    {
        [$a, $b] = $this->entries('A?', 'B?');

        $response = $this->actingAs($this->faqEmployee())->putJson('/api/koleksi/faq/urutan', [
            'ids' => [$b->id, '01kaaaaaaaaaaaaaaaaaaaaaaa', $a->id],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['ids' => 'Urutan tidak cocok dengan data terbaru. Muat ulang halaman lalu coba lagi.']);
        $this->assertSame(['A?', 'B?'], $this->questionsInOrder());
    }

    public function test_rejects_an_entry_listed_twice(): void
    {
        [$a, $b] = $this->entries('A?', 'B?');

        $response = $this->actingAs($this->faqEmployee())->putJson('/api/koleksi/faq/urutan', ['ids' => [$b->id, $a->id, $b->id]]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['ids.2' => 'Daftar urutan berisi entri ganda.']);
        $this->assertSame(['A?', 'B?'], $this->questionsInOrder());
    }

    public function test_requires_a_list_of_entries(): void
    {
        $response = $this->actingAs($this->faqEmployee())->putJson('/api/koleksi/faq/urutan', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['ids' => 'Daftar urutan kosong.']);
    }

    private function faqEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Faq)->create();
    }

    /**
     * @return list<Faq>
     */
    private function entries(string ...$questions): array
    {
        return array_map(
            fn (string $question, int $urutan): Faq => Faq::factory()->create(['pertanyaan' => $question, 'urutan' => $urutan]),
            $questions,
            array_keys($questions),
        );
    }

    /**
     * @return list<string>
     */
    private function questionsInOrder(): array
    {
        return Faq::query()->orderBy('urutan')->orderBy('id')->pluck('pertanyaan')->all();
    }
}
