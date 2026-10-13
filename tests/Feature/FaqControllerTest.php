<?php

namespace Tests\Feature;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Enums\FaqStatus;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Exceptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string QUESTION = 'Apakah pajak tahunan bisa diwakilkan?';

    private const string ANSWER = 'Bisa. Bawa berkas lengkap atas nama pemilik.';

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'list' => ['GET', '/api/koleksi/faq'],
            'read' => ['GET', '/api/koleksi/faq/{id}'],
            'add' => ['POST', '/api/koleksi/faq'],
            'edit' => ['PATCH', '/api/koleksi/faq/{id}'],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => 'Pertanyaan lama?']);

        $response = $this->json($method, str_replace('{id}', $faq->id, $uri), $this->newEntry());

        $response->assertUnauthorized();
        $this->assertDatabaseCount('faqs', 1);
        $this->assertSame('Pertanyaan lama?', $faq->fresh()->pertanyaan);
    }

    #[DataProvider('routes')]
    public function test_forbids_employees_without_the_faq_module_with_403(string $method, string $uri): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => 'Pertanyaan lama?']);
        $employee = User::factory()->withModules(DashboardModule::Berita)->create();

        $response = $this->actingAs($employee)->json($method, str_replace('{id}', $faq->id, $uri), $this->newEntry());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Tanya Jawab belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('faqs', 1);
        $this->assertSame('Pertanyaan lama?', $faq->fresh()->pertanyaan);
    }

    public function test_lists_every_entry_in_display_order(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        Faq::factory()->create(['urutan' => 1, 'pertanyaan' => 'Kedua?']);
        $first = Faq::factory()->eligibleForPublic()->create(['urutan' => 0, 'pertanyaan' => 'Pertama?', 'jawaban' => 'Jawaban pertama.']);

        $response = $this->actingAs($this->faqEmployee())->getJson('/api/koleksi/faq');

        $response->assertOk()->assertJsonPath('0', [
            'id' => $first->id,
            'pertanyaan' => 'Pertama?',
            'jawaban' => 'Jawaban pertama.',
            'status' => 'published',
            'publik' => true,
            'tampil' => true,
            'urutan' => 0,
            'dibuat' => '2026-10-13T08:00:00+00:00',
            'diperbarui' => '2026-10-13T08:00:00+00:00',
        ]);
        $this->assertSame(['Pertama?', 'Kedua?'], $response->json('*.pertanyaan'));
        $this->assertSame(['published', 'draft'], $response->json('*.status'));
    }

    public function test_lets_administrators_list_entries_without_an_assignment(): void
    {
        Faq::factory()->create();

        $response = $this->actingAs(User::factory()->administrator()->create())->getJson('/api/koleksi/faq');

        $response->assertOk()->assertJsonCount(1);
    }

    public function test_shows_one_entry(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => self::QUESTION]);

        $response = $this->actingAs($this->faqEmployee())->getJson("/api/koleksi/faq/{$faq->id}");

        $response->assertOk()->assertJsonPath('id', $faq->id)->assertJsonPath('pertanyaan', self::QUESTION);
    }

    public function test_returns_404_for_an_entry_that_does_not_exist(): void
    {
        $response = $this->actingAs($this->faqEmployee())->getJson('/api/koleksi/faq/01kaaaaaaaaaaaaaaaaaaaaaaa');

        $response->assertNotFound()->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_adds_an_entry_at_the_end_with_201(): void
    {
        Faq::factory()->create(['urutan' => 4]);

        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', $this->newEntry());

        $response->assertCreated()
            ->assertJsonPath('pertanyaan', self::QUESTION)
            ->assertJsonPath('jawaban', self::ANSWER)
            ->assertJsonPath('status', 'published')
            ->assertJsonPath('publik', true)
            ->assertJsonPath('tampil', true)
            ->assertJsonPath('urutan', 5);
        $faq = Faq::query()->findOrFail($response->json('id'));
        $this->assertSame(self::QUESTION, $faq->pertanyaan);
        $this->assertSame(FaqStatus::Published, $faq->status);
        $this->assertSame(5, $faq->urutan);
    }

    public function test_ignores_the_order_and_identifier_sent_by_the_client(): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', [
            'id' => '01kaaaaaaaaaaaaaaaaaaaaaaa',
            'urutan' => 99,
        ] + $this->newEntry());

        $response->assertCreated()->assertJsonPath('urutan', 0);
        $this->assertNotSame('01kaaaaaaaaaaaaaaaaaaaaaaa', $response->json('id'));
    }

    public function test_logs_a_new_entry(): void
    {
        $employee = $this->faqEmployee();

        $this->actingAs($employee)->postJson('/api/koleksi/faq', $this->newEntry());

        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'faq',
            'action' => 'menambahkan',
            'target' => self::QUESTION,
        ]);
    }

    public function test_does_not_keep_an_entry_whose_activity_could_not_be_logged(): void
    {
        Exceptions::fake();
        $this->mock(RecordActivity::class)->shouldReceive('handle')->andThrow(new RuntimeException('Log aktivitas tidak dapat ditulis.'));

        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', $this->newEntry());

        $response->assertInternalServerError();
        $this->assertDatabaseCount('faqs', 0);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_publishes_an_entry_that_the_public_api_then_lists(): void
    {
        $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', $this->newEntry())->assertCreated();

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([['pertanyaan' => self::QUESTION, 'jawaban' => self::ANSWER]]);
    }

    #[TestWith(['draft', true, true])]
    #[TestWith(['published', false, true])]
    #[TestWith(['published', true, false])]
    public function test_keeps_an_entry_that_is_not_published_for_the_public_and_shown_away_from_the_public_api(string $status, bool $publik, bool $tampil): void
    {
        $this->actingAs($this->faqEmployee())
            ->postJson('/api/koleksi/faq', ['status' => $status, 'publik' => $publik, 'tampil' => $tampil] + $this->newEntry())
            ->assertCreated();

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([]);
    }

    public function test_requires_question_answer_status_public_intent_and_display(): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'pertanyaan' => 'Pertanyaan wajib diisi.',
            'jawaban' => 'Jawaban wajib diisi.',
            'status' => 'Pilih status.',
            'publik' => 'Tentukan apakah tanya jawab ini untuk warga.',
            'tampil' => 'Tentukan apakah tanya jawab ini ditampilkan.',
        ]);
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_rejects_a_question_longer_than_200_characters(): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['pertanyaan' => str_repeat('a', 200).'?'] + $this->newEntry());

        $response->assertUnprocessable()->assertJsonValidationErrors(['pertanyaan' => 'Pertanyaan maksimal 200 karakter.']);
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_counts_characters_rather_than_bytes(): void
    {
        $question = str_repeat('é', 199).'?';

        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['pertanyaan' => $question] + $this->newEntry());

        $response->assertCreated()->assertJsonPath('pertanyaan', $question);
    }

    public function test_rejects_an_answer_longer_than_800_characters(): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['jawaban' => str_repeat('a', 801)] + $this->newEntry());

        $response->assertUnprocessable()->assertJsonValidationErrors(['jawaban' => 'Jawaban maksimal 800 karakter.']);
    }

    #[TestWith(["Pajak\u{0007}tahunan?"])]
    #[TestWith(["Pajak\u{0000}tahunan?"])]
    #[TestWith(["Pajak\u{0085}tahunan?"])]
    public function test_rejects_control_characters(string $question): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['pertanyaan' => $question] + $this->newEntry());

        $response->assertUnprocessable()->assertJsonValidationErrors(['pertanyaan' => 'Pertanyaan berisi karakter kontrol yang tidak diizinkan.']);
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_keeps_tabs_and_line_breaks_in_an_answer(): void
    {
        $answer = "Bawa:\n\t- KTP\r\n\t- BPKB";

        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['jawaban' => $answer] + $this->newEntry());

        $response->assertCreated()->assertJsonPath('jawaban', $answer);
    }

    // Characters that Laravel's TrimStrings leaves in place, so they reach PlainText.
    #[TestWith(["\u{0301}"])]
    #[TestWith(["\u{E000}"])]
    #[TestWith(["\u{FFF9}\u{0301}"])]
    public function test_rejects_a_question_without_visible_text(string $question): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['pertanyaan' => $question] + $this->newEntry());

        $response->assertUnprocessable()->assertJsonValidationErrors(['pertanyaan' => 'Pertanyaan tidak boleh hanya berisi spasi atau karakter tak terlihat.']);
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_treats_a_question_of_blank_fillers_as_empty(): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['pertanyaan' => "\u{3164}\u{2800} "] + $this->newEntry());

        $response->assertUnprocessable()->assertJsonValidationErrors(['pertanyaan' => 'Pertanyaan wajib diisi.']);
        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_rejects_an_unknown_status(): void
    {
        $response = $this->actingAs($this->faqEmployee())->postJson('/api/koleksi/faq', ['status' => 'archived'] + $this->newEntry());

        $response->assertUnprocessable()->assertJsonValidationErrors(['status' => 'Status tidak valid.']);
    }

    public function test_changes_an_entry(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->actingAs($this->faqEmployee())->patchJson("/api/koleksi/faq/{$faq->id}", $this->newEntry());

        $response->assertOk()->assertJsonPath('pertanyaan', self::QUESTION)->assertJsonPath('status', 'published');
        $faq->refresh();
        $this->assertSame(self::QUESTION, $faq->pertanyaan);
        $this->assertSame(self::ANSWER, $faq->jawaban);
        $this->assertSame(FaqStatus::Published, $faq->status);
        $this->assertTrue($faq->publik);
    }

    public function test_changes_only_the_fields_sent(): void
    {
        $faq = Faq::factory()->eligibleForPublic()->create(['pertanyaan' => self::QUESTION]);

        $response = $this->actingAs($this->faqEmployee())->patchJson("/api/koleksi/faq/{$faq->id}", ['tampil' => false]);

        $response->assertOk()->assertJsonPath('tampil', false);
        $faq->refresh();
        $this->assertFalse($faq->tampil);
        $this->assertSame(self::QUESTION, $faq->pertanyaan);
        $this->assertSame(FaqStatus::Published, $faq->status);
    }

    public function test_validates_the_fields_sent_when_editing(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => self::QUESTION]);

        $response = $this->actingAs($this->faqEmployee())->patchJson("/api/koleksi/faq/{$faq->id}", ['pertanyaan' => '']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['pertanyaan' => 'Pertanyaan wajib diisi.']);
        $this->assertSame(self::QUESTION, $faq->fresh()->pertanyaan);
    }

    /**
     * @return array<string, array{FaqStatus, string, string}>
     */
    public static function statusChanges(): array
    {
        return [
            'publish' => [FaqStatus::Draft, 'published', 'menerbitkan'],
            'withdraw' => [FaqStatus::Published, 'unpublished', 'menarik dari portal'],
            'back to draft' => [FaqStatus::Unpublished, 'draft', 'mengembalikan ke draf'],
        ];
    }

    #[DataProvider('statusChanges')]
    public function test_logs_a_status_change_by_what_it_means_for_the_portal(FaqStatus $from, string $to, string $action): void
    {
        $faq = Faq::factory()->create(['status' => $from, 'pertanyaan' => self::QUESTION]);

        $this->actingAs($this->faqEmployee())->patchJson("/api/koleksi/faq/{$faq->id}", ['status' => $to])->assertOk();

        $this->assertDatabaseHas('activities', ['module' => 'faq', 'action' => $action, 'target' => self::QUESTION]);
    }

    public function test_logs_other_changes_as_an_update(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => self::QUESTION]);

        $this->actingAs($this->faqEmployee())->patchJson("/api/koleksi/faq/{$faq->id}", ['jawaban' => 'Jawaban baru.'])->assertOk();

        $this->assertDatabaseHas('activities', ['module' => 'faq', 'action' => 'memperbarui', 'target' => self::QUESTION]);
    }

    public function test_logs_nothing_when_nothing_changes(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => self::QUESTION, 'tampil' => true]);

        $this->actingAs($this->faqEmployee())->patchJson("/api/koleksi/faq/{$faq->id}", ['tampil' => true])->assertOk();

        $this->assertDatabaseCount('activities', 0);
    }

    private function faqEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Faq)->create();
    }

    /**
     * @return array{pertanyaan: string, jawaban: string, status: string, publik: bool, tampil: bool}
     */
    private function newEntry(): array
    {
        return [
            'pertanyaan' => self::QUESTION,
            'jawaban' => self::ANSWER,
            'status' => 'published',
            'publik' => true,
            'tampil' => true,
        ];
    }
}
