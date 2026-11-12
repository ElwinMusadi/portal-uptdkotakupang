<?php

namespace Tests\Feature;

use App\Enums\FaqStatus;
use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class VehicleTaxFaqsMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string EXAMPLE_ANSWER = 'Opsen adalah pungutan pajak untuk daerah kabupaten/kota.';

    /**
     * The reviewed questions in their order on the portal (D-094).
     */
    private const array QUESTIONS = [
        'Apa saja yang saya bayar saat membayar pajak kendaraan tahunan?',
        'Apa itu Opsen PKB?',
        'Berapa denda bila terlambat membayar pajak kendaraan?',
        'Bagaimana cara mengetahui besar pajak dan tanggal jatuh tempo kendaraan saya?',
        'Bisakah saya membayar pajak kendaraan secara online?',
        'Apa akibatnya bila pajak kendaraan tidak dibayar?',
    ];

    public function test_does_nothing_where_the_example_question_does_not_exist(): void
    {
        $this->assertDatabaseCount('faqs', 0);
        $other = Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Pertanyaan lain?', 'urutan' => 4]);

        $this->migration()->up();

        $this->assertDatabaseCount('faqs', 1);
        $this->assertSame(4, $other->refresh()->urutan);
    }

    public function test_completes_the_list_around_the_example_question_and_publishes_it(): void
    {
        Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Apa itu Opsen PKB?', 'jawaban' => self::EXAMPLE_ANSWER, 'urutan' => 0]);
        $draft = Faq::factory()->create(['pertanyaan' => 'Draf petugas?', 'urutan' => 1]);

        $this->migration()->up();

        $faqs = Faq::query()->orderBy('urutan')->get();
        $this->assertSame([...self::QUESTIONS, 'Draf petugas?'], $faqs->pluck('pertanyaan')->all());
        $this->assertSame(range(0, 6), $faqs->pluck('urutan')->all());
        $this->assertSame(FaqStatus::Draft, $draft->refresh()->status);
        foreach ($faqs->take(6) as $faq) {
            $this->assertTrue($faq->isPublishable(), $faq->pertanyaan);
            $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $faq->id);
        }
        $this->assertStringStartsWith('Opsen PKB adalah pungutan tambahan sebesar 66% dari Pajak Kendaraan Bermotor (PKB) yang terutang.', $faqs[1]->jawaban);
        $this->assertStringContainsString("Notis pajak tahunan (SKPD) terdiri atas:\n– PKB", $faqs[0]->jawaban);

        $public = $this->getJson('/api/public/faq')->assertOk()->json();
        $this->assertSame(self::QUESTIONS, array_column($public, 'pertanyaan'));
        $this->assertSame($faqs[1]->jawaban, $public[1]['jawaban']);
    }

    public function test_keeps_an_answer_the_owner_changed_and_does_not_add_a_question_twice(): void
    {
        Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Apa itu Opsen PKB?', 'jawaban' => 'Jawaban petugas.', 'urutan' => 0]);
        Faq::factory()->create(['pertanyaan' => 'Berapa denda bila terlambat membayar pajak kendaraan?', 'jawaban' => 'Denda versi petugas.', 'urutan' => 1]);

        $this->migration()->up();

        $this->assertSame('Jawaban petugas.', Faq::query()->where('pertanyaan', 'Apa itu Opsen PKB?')->value('jawaban'));
        $fine = Faq::query()->where('pertanyaan', 'Berapa denda bila terlambat membayar pajak kendaraan?')->sole();
        $this->assertSame(['Denda versi petugas.', FaqStatus::Draft, 2], [$fine->jawaban, $fine->status, $fine->urutan]);
        $this->assertDatabaseCount('faqs', 6);
        $this->assertSame(self::QUESTIONS, Faq::query()->orderBy('urutan')->pluck('pertanyaan')->all());
    }

    public function test_takes_back_only_entries_nobody_changed_and_the_example_answer(): void
    {
        Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Apa itu Opsen PKB?', 'jawaban' => self::EXAMPLE_ANSWER]);
        $migration = $this->migration();
        $migration->up();
        Faq::query()->where('pertanyaan', 'Bisakah saya membayar pajak kendaraan secara online?')->update(['jawaban' => 'Diubah petugas.']);

        $migration->down();

        $this->assertSame(['Apa itu Opsen PKB?', 'Bisakah saya membayar pajak kendaraan secara online?'], Faq::query()->orderBy('urutan')->pluck('pertanyaan')->all());
        $this->assertSame(self::EXAMPLE_ANSWER, Faq::query()->where('pertanyaan', 'Apa itu Opsen PKB?')->value('jawaban'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_12_002102_add_vehicle_tax_faqs.php');
    }
}
