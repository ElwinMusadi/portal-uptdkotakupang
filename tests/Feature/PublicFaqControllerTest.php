<?php

namespace Tests\Feature;

use App\Enums\FaqStatus;
use App\Exceptions\PublicContentUnavailableException;
use App\Models\Faq;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicFaqControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string UNAVAILABLE_MESSAGE = 'Konten sedang tidak dapat ditampilkan. Silakan coba lagi nanti.';

    private const string WITHHELD_WARNING = 'Public FAQ entries withheld: stored values are not publishable.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cors.allowed_origins' => [],
            'pintu.public_api.requests_per_minute' => 60,
            'pintu.public_faq.max_items' => 100,
        ]);
    }

    public function test_returns_eligible_faqs_as_question_and_answer_pairs_only(): void
    {
        Faq::factory()->eligibleForPublic()->create([
            'pertanyaan' => 'Apakah pajak tahunan bisa diwakilkan?',
            'jawaban' => 'Bisa, dengan berkas lengkap atas nama pemilik.',
            'urutan' => 4,
        ]);

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                [
                    'pertanyaan' => 'Apakah pajak tahunan bisa diwakilkan?',
                    'jawaban' => 'Bisa, dengan berkas lengkap atas nama pemilik.',
                ],
            ]);
    }

    public function test_returns_an_empty_array_when_no_faq_is_eligible(): void
    {
        Faq::factory()->create();

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function ineligibleStates(): array
    {
        return [
            'draft' => [['status' => FaqStatus::Draft]],
            'unpublished' => [['status' => FaqStatus::Unpublished]],
            'not intended for the public' => [['publik' => false]],
            'switched off' => [['tampil' => false]],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    #[DataProvider('ineligibleStates')]
    public function test_excludes_faqs_that_are_not_eligible_for_the_public_without_logging_them(array $state): void
    {
        Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Terbit?', 'jawaban' => 'Ya.']);
        Faq::factory()->eligibleForPublic()->create([...$state, 'pertanyaan' => 'Tersembunyi?', 'jawaban' => 'Tidak.']);
        Log::spy();

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([
            ['pertanyaan' => 'Terbit?', 'jawaban' => 'Ya.'],
        ]);
        Log::shouldNotHaveReceived('warning');
    }

    public function test_excludes_faqs_with_an_unrecognised_status_value(): void
    {
        DB::table('faqs')->insert([
            'id' => Str::lower((string) Str::ulid()),
            'pertanyaan' => 'Status lama?',
            'jawaban' => 'Status prototipe tidak diakui.',
            'status' => 'terbit',
            'publik' => true,
            'tampil' => true,
            'urutan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([]);
    }

    public function test_orders_faqs_by_their_dashboard_order_then_by_internal_key(): void
    {
        $factory = Faq::factory()->eligibleForPublic();
        $factory->create(['id' => '01k0000000000000000000000d', 'pertanyaan' => 'Apakah pajak bisa dicicil?', 'jawaban' => 'Tidak.', 'urutan' => 1]);
        $factory->create(['id' => '01k0000000000000000000000a', 'pertanyaan' => 'Kapan loket buka?', 'jawaban' => 'Pukul 08.00.', 'urutan' => 0]);
        $factory->create(['id' => '01k0000000000000000000000b', 'pertanyaan' => 'Zona mana yang dilayani?', 'jawaban' => 'Kota Kupang.', 'urutan' => 1]);
        $factory->create(['id' => '01k0000000000000000000000c', 'pertanyaan' => 'Bagaimana cara mengadu?', 'jawaban' => 'Lewat formulir.', 'urutan' => 2]);

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([
            ['pertanyaan' => 'Kapan loket buka?', 'jawaban' => 'Pukul 08.00.'],
            ['pertanyaan' => 'Zona mana yang dilayani?', 'jawaban' => 'Kota Kupang.'],
            ['pertanyaan' => 'Apakah pajak bisa dicicil?', 'jawaban' => 'Tidak.'],
            ['pertanyaan' => 'Bagaimana cara mengadu?', 'jawaban' => 'Lewat formulir.'],
        ]);
    }

    public function test_returns_markup_urls_and_line_breaks_as_unaltered_plain_text(): void
    {
        $answer = "Baris pertama.\nLihat <a href=\"https://contoh.go.id\">tautan</a> atau https://contoh.go.id\n<script>alert(1)</script> &amp; 5\u{00A0}tahun.";
        Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Apakah <b>tebal</b> ikut?', 'jawaban' => $answer]);

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([
            ['pertanyaan' => 'Apakah <b>tebal</b> ikut?', 'jawaban' => $answer],
        ]);
    }

    public function test_withholds_and_logs_eligible_faqs_whose_text_is_not_publishable(): void
    {
        Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Sah?', 'jawaban' => 'Ya.']);
        $blank = Faq::factory()->eligibleForPublic()->create(['jawaban' => " \n\t "]);
        Log::spy();

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertExactJson([
            ['pertanyaan' => 'Sah?', 'jawaban' => 'Ya.'],
        ]);
        Log::shouldHaveReceived('warning')->once()->with(self::WITHHELD_WARNING, ['ids' => [$blank->id]]);
    }

    public function test_logs_withheld_faqs_at_most_once_every_five_minutes(): void
    {
        $blank = Faq::factory()->eligibleForPublic()->create(['jawaban' => " \n\t "]);
        Log::spy();

        $this->getJson('/api/public/faq')->assertOk();
        $this->getJson('/api/public/faq')->assertOk();
        $this->travel(301)->seconds();
        $this->getJson('/api/public/faq')->assertOk();

        Log::shouldHaveReceived('warning')->twice()->with(self::WITHHELD_WARNING, ['ids' => [$blank->id]]);
    }

    public function test_forbids_caching_and_sends_no_revalidation_headers(): void
    {
        $response = $this->getJson('/api/public/faq');

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeaderMissing('ETag')
            ->assertHeaderMissing('Last-Modified');
    }

    public function test_returns_every_faq_when_the_eligible_count_equals_the_limit(): void
    {
        config(['pintu.public_faq.max_items' => 2]);
        Faq::factory()->eligibleForPublic()->count(2)->create();

        $response = $this->getJson('/api/public/faq');

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_returns_503_instead_of_a_partial_list_when_more_faqs_are_eligible_than_the_limit(): void
    {
        config(['pintu.public_faq.max_items' => 2]);
        Faq::factory()->eligibleForPublic()->count(3)->create();
        Exceptions::fake();

        $response = $this->getJson('/api/public/faq');

        $response->assertServiceUnavailable()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['message' => self::UNAVAILABLE_MESSAGE]);
        Exceptions::assertReported(PublicContentUnavailableException::class);
    }

    public function test_returns_503_and_reports_the_cause_when_the_faq_source_cannot_be_read(): void
    {
        $this->makeFaqQueriesFail();
        Exceptions::fake();

        $response = $this->getJson('/api/public/faq');

        $response->assertServiceUnavailable()->assertExactJson(['message' => self::UNAVAILABLE_MESSAGE]);
        Exceptions::assertReported(
            fn (PublicContentUnavailableException $exception): bool => $exception->getPrevious() instanceof QueryException
        );
    }

    public function test_reports_an_unreadable_faq_source_at_most_once_per_minute(): void
    {
        $this->makeFaqQueriesFail();
        Exceptions::fake();

        $this->getJson('/api/public/faq')->assertServiceUnavailable();
        $this->getJson('/api/public/faq')->assertServiceUnavailable();

        Exceptions::assertReportedCount(1);
    }

    public function test_rejects_query_parameters_with_422_json_even_without_a_json_accept_header(): void
    {
        Faq::factory()->eligibleForPublic()->create();

        $response = $this->get('/api/public/faq?status=draft&limit=1000');

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Parameter kueri tidak didukung.',
            'errors' => ['query' => ['Parameter kueri tidak didukung.']],
        ]);
    }

    #[TestWith(['POST'])]
    #[TestWith(['PUT'])]
    #[TestWith(['PATCH'])]
    #[TestWith(['DELETE'])]
    public function test_rejects_write_methods_with_405_and_changes_nothing(string $method): void
    {
        $faq = Faq::factory()->eligibleForPublic()->create(['pertanyaan' => 'Asli?']);

        $response = $this->json($method, '/api/public/faq', ['pertanyaan' => 'Diubah?']);

        $response->assertMethodNotAllowed();
        $this->assertDatabaseCount('faqs', 1);
        $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'pertanyaan' => 'Asli?']);
    }

    public function test_returns_429_with_a_message_once_the_per_minute_limit_is_reached(): void
    {
        config(['pintu.public_api.requests_per_minute' => 2]);
        $this->getJson('/api/public/faq')->assertOk();
        $this->getJson('/api/public/faq')->assertOk();

        $response = $this->getJson('/api/public/faq');

        $response->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['message' => 'Terlalu banyak permintaan. Silakan coba lagi sebentar lagi.']);
    }

    public function test_echoes_a_configured_origin_in_the_cors_header(): void
    {
        config(['cors.allowed_origins' => ['https://portal.example', 'https://dashboard.example']]);

        $response = $this->getJson('/api/public/faq', ['Origin' => 'https://dashboard.example']);

        $response->assertOk()->assertHeader('Access-Control-Allow-Origin', 'https://dashboard.example');
    }

    public function test_grants_no_cors_access_to_an_origin_that_is_not_configured(): void
    {
        config(['cors.allowed_origins' => ['https://portal.example', 'https://dashboard.example']]);

        $response = $this->getJson('/api/public/faq', ['Origin' => 'https://not-configured.example']);

        $response->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /**
     * Fail every query on the faqs table the way an unreachable database does.
     * Dropping the table instead would commit the test transaction on MySQL.
     */
    private function makeFaqQueriesFail(): void
    {
        DB::table('faqs')->exists();

        DB::beforeExecuting(function (string $query): void {
            if (preg_match('/\bfaqs\b/', $query) === 1) {
                throw new QueryException(DB::getName(), $query, [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
            }
        });
    }
}
