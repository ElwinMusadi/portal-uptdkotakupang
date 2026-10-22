<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Support\SikapClient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SikapEmployeeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string BASE_URL = 'https://sikap.contoh.test/api/v1';

    /**
     * A made-up key in SIKAP's format; never a real one.
     */
    private const string KEY = 'skp_000000000000_kunci-uji-fiktif-bukan-kunci-sikap-asli-0000';

    private const string SEARCH = self::BASE_URL.'/pegawai?*';

    private const string DETAIL = self::BASE_URL.'/pegawai/*';

    private const string NIP = '199001012020011001';

    private const string UNREACHABLE = 'SIKAP sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sikap.base_url' => self::BASE_URL,
            'services.sikap.key' => self::KEY,
            'services.sikap.timeout' => 8,
        ]);
        Http::preventStrayRequests();
        Sleep::fake();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function routes(): array
    {
        return [
            'search' => ['/api/akun/pegawai-sikap?q=contoh'],
            'detail' => ['/api/akun/pegawai-sikap/'.self::NIP],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests(string $uri): void
    {
        Http::fake();

        $this->getJson($uri)->assertUnauthorized();

        Http::assertNothingSent();
    }

    #[DataProvider('routes')]
    public function test_forbids_employees_with_403_without_asking_sikap(string $uri): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())->getJson($uri)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengelola akun.']);

        Http::assertNothingSent();
    }

    public function test_gives_an_administrator_the_employees_found_and_whether_they_have_an_account(): void
    {
        User::factory()->withStatus(AccountStatus::Deactivated)->create(['nip' => '198501012010011002']);
        Http::fake([self::SEARCH => Http::response([
            'items' => [
                $this->employee(),
                $this->employee(['nip' => '198501012010011002', 'nama' => 'Contoh Pegawai Dua', 'jabatan' => null, 'unit' => null]),
            ],
            'page' => 1,
            'pageSize' => 20,
            'total' => 57,
        ], 200, ['Content-Type' => 'application/json'])]);

        $response = $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh');

        $response->assertOk()->assertExactJson([
            'pegawai' => [
                ['nip' => self::NIP, 'nama' => 'Contoh Pegawai', 'jabatan' => 'Pengelola Kepegawaian', 'unit' => 'Subbagian Tata Usaha', 'sudahPunyaAkun' => false],
                ['nip' => '198501012010011002', 'nama' => 'Contoh Pegawai Dua', 'jabatan' => null, 'unit' => null, 'sudahPunyaAkun' => true],
            ],
            'total' => 57,
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/pegawai?q=contoh&pageSize=20'
            && $request->method() === 'GET'
            && $request->header('Authorization') === ['Bearer '.self::KEY]
            && $request->header('Accept') === ['application/json']);
    }

    #[TestWith([57, 57])]
    #[TestWith([0, 1])]
    #[TestWith(['banyak', 1])]
    public function test_leaves_out_employees_whose_nip_or_name_it_cannot_use(mixed $total, int $counted): void
    {
        Http::fake([self::SEARCH => Http::response([
            'items' => [
                $this->employee(['nip' => '19900101']),
                $this->employee(['nip' => 199001012020011001]),
                $this->employee(['nama' => '  ']),
                $this->employee(['nama' => str_repeat('a', 256)]),
                $this->employee(['nama' => ' '.str_repeat('é', 255).' ']),
            ],
            'total' => $total,
        ], 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertOk()
            ->assertJsonPath('pegawai.*.nama', [str_repeat('é', 255)])
            ->assertJsonPath('total', $counted);
    }

    #[TestWith(['', 'Ketik minimal 2 huruf nama atau angka NIP.'])]
    #[TestWith(['a', 'Ketik minimal 2 huruf nama atau angka NIP.'])]
    #[TestWith(['%20a%20', 'Ketik minimal 2 huruf nama atau angka NIP.'])]
    public function test_rejects_a_search_shorter_than_2_characters_without_asking_sikap(string $search, string $message): void
    {
        Http::fake();

        $this->actingAs($this->administrator())->getJson("/api/akun/pegawai-sikap?q={$search}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q' => $message]);

        Http::assertNothingSent();
    }

    public function test_rejects_a_search_longer_than_100_characters(): void
    {
        Http::fake();

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q' => 'Pencarian maksimal 100 karakter.']);

        Http::assertNothingSent();
    }

    public function test_gives_the_picked_employee_without_their_contact_details(): void
    {
        Http::fake([self::DETAIL => Http::response($this->detail(), 200, ['Content-Type' => 'application/json'])]);

        $response = $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap/'.self::NIP);

        $response->assertOk()->assertExactJson([
            'nip' => self::NIP,
            'nama' => 'Contoh Pegawai',
            'jabatan' => 'Pengelola Kepegawaian',
            'unit' => 'Subbagian Tata Usaha',
            'sudahPunyaAkun' => false,
        ]);
        $this->assertStringNotContainsString('example.com', $response->getContent());
        $this->assertStringNotContainsString('6281234567890', $response->getContent());
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/pegawai/'.self::NIP
            && $request->header('Authorization') === ['Bearer '.self::KEY]);
    }

    public function test_says_when_the_picked_employee_already_has_an_account(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        Http::fake([self::DETAIL => Http::response($this->detail(), 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap/'.self::NIP)
            ->assertOk()
            ->assertJsonPath('sudahPunyaAkun', true);
    }

    public function test_returns_404_for_an_employee_sikap_does_not_have(): void
    {
        Http::fake([self::DETAIL => Http::response(['error' => ['code' => 'NOT_FOUND', 'message' => 'Pegawai tidak ditemukan.']], 404, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap/'.self::NIP)
            ->assertNotFound()
            ->assertExactJson(['message' => 'Pegawai tidak ditemukan atau tidak aktif di SIKAP.']);
    }

    #[TestWith(['19900101'])]
    #[TestWith(['1990010120200110011'])]
    #[TestWith(['19900101202001100a'])]
    public function test_returns_404_for_a_nip_that_is_not_18_digits_without_asking_sikap(string $nip): void
    {
        Http::fake();

        $this->actingAs($this->administrator())->getJson("/api/akun/pegawai-sikap/{$nip}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Pegawai tidak ditemukan atau tidak aktif di SIKAP.']);

        Http::assertNothingSent();
    }

    public function test_treats_an_answer_for_another_nip_as_unavailable(): void
    {
        Http::fake([self::DETAIL => Http::response($this->detail(['nip' => '198501012010011002']), 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap/'.self::NIP)
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);
    }

    #[TestWith([401, 'API_KEY_INVALID', 'Kunci API SIKAP ditolak: tidak valid, sudah dihapus, atau kedaluwarsa. Minta Admin SIKAP membuat kunci baru untuk PINTU.'])]
    #[TestWith([403, 'API_SCOPE_DENIED', 'Kunci API SIKAP tidak punya izin membaca data pegawai. Minta Admin SIKAP membuat kunci dengan izin pegawai:baca.'])]
    #[TestWith([400, 'VALIDATION_ERROR', self::UNREACHABLE])]
    #[TestWith([404, 'NOT_FOUND', self::UNREACHABLE])]
    #[TestWith([500, 'INTERNAL_ERROR', self::UNREACHABLE])]
    public function test_tells_the_administrator_why_sikap_refused_without_retrying(int $status, string $code, string $message): void
    {
        Http::fake([self::SEARCH => Http::response(['error' => ['code' => $code, 'message' => 'Galat SIKAP.']], $status, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => $message]);

        Http::assertSentCount(1);
    }

    public function test_does_not_follow_a_redirect_with_the_key_or_use_its_answer(): void
    {
        Http::fake([self::SEARCH => Http::response(
            ['items' => [$this->employee()], 'total' => 1],
            302,
            ['Content-Type' => 'application/json', 'Location' => 'https://lain.contoh.test/'],
        )]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);

        Http::assertSentCount(1);
    }

    #[TestWith(['<html>Masuk</html>', 'text/html'])]
    #[TestWith(['{"items": "bukan daftar"}', 'application/json'])]
    #[TestWith(['{"items": {"a": 1}}', 'application/json'])]
    #[TestWith(['{"total": 0}', 'application/json'])]
    public function test_treats_an_unexpected_answer_as_unavailable(string $body, string $contentType): void
    {
        Http::fake([self::SEARCH => Http::response($body, 200, ['Content-Type' => $contentType])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);
    }

    public function test_retries_twice_after_a_gateway_failure_or_a_dropped_connection(): void
    {
        $found = ['items' => [$this->employee()], 'total' => 1];
        Http::fakeSequence(self::SEARCH)
            ->push(null, 503)
            ->push($found, 200, ['Content-Type' => 'application/json'])
            ->pushFailedConnection()
            ->pushFailedConnection()
            ->push($found, 200, ['Content-Type' => 'application/json'])
            ->push(null, 502)
            ->push(null, 504)
            ->push(null, 503)
            ->pushFailedConnection()
            ->pushFailedConnection()
            ->pushFailedConnection();
        $administrator = $this->administrator();

        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap?q=contoh')->assertOk();
        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap?q=contoh')->assertOk();
        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);
        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);

        Http::assertSentCount(11);
        Sleep::assertSleptTimes(7);
    }

    public function test_does_not_retry_a_timeout(): void
    {
        $attempts = 0;
        Http::fake([self::SEARCH => function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        }]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);

        $this->assertSame(1, $attempts);
        Sleep::assertNeverSlept();
    }

    #[TestWith([5, 5])]
    #[TestWith([0, 8])]
    #[TestWith([61, 8])]
    public function test_waits_for_sikap_as_long_as_configured_up_to_a_minute(int $configured, int $timeout): void
    {
        config(['services.sikap.timeout' => $configured]);
        $options = [];
        Http::fake(function (Request $request, array $requestOptions) use (&$options) {
            $options[] = [$requestOptions['connect_timeout'], $requestOptions['timeout']];

            return Http::response(['items' => [], 'total' => 0], 200, ['Content-Type' => 'application/json']);
        });

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')->assertOk();

        $this->assertSame([[3, $timeout]], $options);
    }

    public function test_waits_as_long_as_sikap_asks_after_too_many_requests(): void
    {
        $this->travelTo(Carbon::parse('2026-10-22 08:00:00', 'UTC'));
        Http::fake([self::SEARCH => Http::response(['error' => ['code' => 'RATE_LIMITED'], 'retryAfterSeconds' => 40], 429, ['Content-Type' => 'application/json', 'Retry-After' => '40'])]);
        $administrator = $this->administrator();
        Log::spy();

        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'SIKAP sedang membatasi permintaan. Coba lagi dalam 40 detik.']);
        $this->travel(10)->seconds();
        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap/'.self::NIP)
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'SIKAP sedang membatasi permintaan. Coba lagi dalam 30 detik.']);

        Http::assertSentCount(1);
        $this->assertTrue(Cache::has(SikapClient::WAIT_UNTIL));
        Log::shouldHaveReceived('warning')->once()->with('SIKAP API request failed', ['reason' => 'rate_limited', 'http_status' => 429, 'error_code' => 'RATE_LIMITED']);
    }

    #[TestWith(['7200', 3600])]
    #[TestWith(['Thu, 22 Oct 2026 08:01:30 GMT', 90])]
    #[TestWith(['nanti', 60])]
    #[TestWith(['', 60])]
    public function test_reads_how_long_to_wait_from_retry_after_for_at_most_an_hour(string $retryAfter, int $seconds): void
    {
        $this->travelTo(Carbon::parse('2026-10-22 08:00:00', 'UTC'));
        Http::fake([self::SEARCH => Http::response(null, 429, $retryAfter === '' ? [] : ['Retry-After' => $retryAfter])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => "SIKAP sedang membatasi permintaan. Coba lagi dalam {$seconds} detik."]);
    }

    #[TestWith(['services.sikap.key', ''])]
    #[TestWith(['services.sikap.key', null])]
    #[TestWith(['services.sikap.base_url', null])]
    #[TestWith(['services.sikap.base_url', 'http://sikap.contoh.test/api/v1'])]
    #[TestWith(['services.sikap.base_url', 'http://localhost:8788/api/v1'])]
    public function test_asks_nothing_while_sikap_is_not_configured(string $key, ?string $value): void
    {
        config([$key => $value]);
        Http::fake();
        Log::spy();

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'Pencarian pegawai SIKAP belum dikonfigurasi.']);

        Http::assertNothingSent();
        Log::shouldHaveReceived('error')->once()->with('SIKAP API is not configured');
    }

    #[TestWith(['http://localhost:8788/api/v1', true])]
    #[TestWith(['http://127.0.0.1:8788/api/v1', true])]
    #[TestWith(['http://[::1]:8788/api/v1', true])]
    #[TestWith(['http://sikap.contoh.test/api/v1', false])]
    #[TestWith(['http://localhost.contoh.test/api/v1', false])]
    public function test_sends_the_key_over_plain_http_only_to_a_local_sikap_while_developing(string $baseUrl, bool $asked): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        config(['services.sikap.base_url' => $baseUrl]);
        Http::fake([$baseUrl.'/pegawai?*' => Http::response(['items' => [], 'total' => 0], 200, ['Content-Type' => 'application/json'])]);

        $response = $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh');

        if ($asked) {
            $response->assertOk();
            Http::assertSent(fn (Request $request): bool => $request->url() === $baseUrl.'/pegawai?q=contoh&pageSize=20');
        } else {
            $response->assertServiceUnavailable();
            Http::assertNothingSent();
        }
    }

    public function test_never_logs_the_key_the_search_or_contact_details(): void
    {
        Log::spy();
        Http::fake([self::DETAIL => Http::response(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'contoh.pegawai@example.com 6281234567890']], 500, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap/'.self::NIP)->assertServiceUnavailable();

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
            $this->assertSame(['reason' => 'http', 'http_status' => 500, 'error_code' => 'INTERNAL_ERROR'], $context);

            return $message === 'SIKAP API request failed';
        });
    }

    public function test_logs_an_answer_that_is_not_json_with_its_http_status(): void
    {
        Log::spy();
        Http::fake([self::SEARCH => Http::response('<html>Masuk</html>', 200, ['Content-Type' => 'text/html'])]);

        $this->actingAs($this->administrator())->getJson('/api/akun/pegawai-sikap?q=contoh')->assertServiceUnavailable();

        Log::shouldHaveReceived('warning')->once()->with('SIKAP API request failed', ['reason' => 'invalid_payload', 'http_status' => 200, 'error_code' => null]);
    }

    public function test_limits_searches_to_30_a_minute_per_administrator(): void
    {
        Http::fake([self::SEARCH => Http::response(['items' => [], 'total' => 0], 200, ['Content-Type' => 'application/json'])]);
        $administrator = $this->administrator();

        for ($search = 1; $search <= 30; $search++) {
            $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap?q=contoh')->assertOk();
        }

        $this->actingAs($administrator)->getJson('/api/akun/pegawai-sikap/'.self::NIP)
            ->assertTooManyRequests()
            ->assertExactJson(['message' => 'Terlalu banyak pencarian pegawai SIKAP. Coba lagi sebentar lagi.']);
    }

    public function test_creates_an_account_with_the_name_sikap_has_rather_than_the_one_sent(): void
    {
        Http::fake([self::DETAIL => Http::response($this->detail(), 200, ['Content-Type' => 'application/json'])]);
        $administrator = $this->administrator();
        Log::spy();

        $response = $this->actingAs($administrator)->postJson('/api/akun', [
            'sumber' => 'sikap',
            'nip' => self::NIP,
            'nama' => 'Nama Dari Peramban',
            'surel' => 'contoh@uptd.test',
            'peran' => 'pegawai',
            'modul' => ['antrean'],
        ]);

        $response->assertCreated()->assertJsonPath('akun.nama', 'Contoh Pegawai')->assertJsonPath('akun.surel', 'contoh@uptd.test');
        $account = User::query()->where('nip', self::NIP)->sole();
        $this->assertSame('Contoh Pegawai', $account->name);
        $this->assertSame('contoh@uptd.test', $account->email);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/pegawai/'.self::NIP
            && $request->header('Authorization') === ['Bearer '.self::KEY]);
        Log::shouldHaveReceived('info')->once()->with('account.created', [
            'actor' => $administrator->id,
            'user' => $account->id,
            'role' => 'pegawai',
            'modules' => ['antrean'],
            'source' => 'sikap',
            'ip' => '127.0.0.1',
        ]);
    }

    public function test_never_stores_the_email_or_phone_number_sikap_has(): void
    {
        Http::fake([self::DETAIL => Http::response($this->detail(), 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => 'sikap', 'nip' => self::NIP, 'surel' => null, 'peran' => 'pegawai'])
            ->assertCreated()
            ->assertJsonPath('akun.surel', null);

        $this->assertNull(User::query()->where('nip', self::NIP)->sole()->email);
        $this->assertDatabaseMissing('users', ['email' => 'contoh.pegawai@example.com']);
    }

    public function test_rejects_with_422_an_employee_sikap_no_longer_has(): void
    {
        Http::fake([self::DETAIL => Http::response(['error' => ['code' => 'NOT_FOUND']], 404, ['Content-Type' => 'application/json'])]);

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => 'sikap', 'nip' => self::NIP, 'peran' => 'pegawai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nip' => 'Pegawai dengan NIP ini tidak ditemukan atau tidak aktif di SIKAP.']);

        $this->assertDatabaseMissing('users', ['nip' => self::NIP]);
    }

    public function test_creates_no_account_from_sikap_while_sikap_cannot_be_reached(): void
    {
        Http::fake([self::DETAIL => Http::response(null, 500)]);

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => 'sikap', 'nip' => self::NIP, 'nama' => 'Contoh Pegawai', 'peran' => 'pegawai'])
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => self::UNREACHABLE]);

        $this->assertDatabaseMissing('users', ['nip' => self::NIP]);
    }

    public function test_rejects_a_registered_nip_before_asking_sikap(): void
    {
        User::factory()->create(['nip' => self::NIP]);
        Http::fake();

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => 'sikap', 'nip' => self::NIP, 'peran' => 'pegawai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nip' => 'NIP ini sudah terdaftar.']);

        Http::assertNothingSent();
    }

    #[TestWith(['manual'])]
    #[TestWith([null])]
    public function test_creates_an_account_entered_by_hand_without_asking_sikap(?string $source): void
    {
        Http::fake();

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => $source, 'nip' => self::NIP, 'nama' => 'Petugas Mitra', 'peran' => 'pegawai'])
            ->assertCreated()
            ->assertJsonPath('akun.nama', 'Petugas Mitra');

        Http::assertNothingSent();
    }

    public function test_still_requires_a_name_for_an_account_entered_by_hand(): void
    {
        Http::fake();

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => 'manual', 'nip' => self::NIP, 'peran' => 'pegawai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama' => 'Nama wajib diisi.']);
    }

    public function test_rejects_an_unknown_source(): void
    {
        Http::fake();

        $this->actingAs($this->administrator())->postJson('/api/akun', ['sumber' => 'simpeg', 'nip' => self::NIP, 'nama' => 'Contoh Pegawai', 'peran' => 'pegawai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sumber' => 'Sumber data akun tidak dikenal.']);

        Http::assertNothingSent();
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create(['nip' => '197001012000011001']);
    }

    /**
     * A made-up employee in the shape of SIKAP's search results.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function employee(array $changes = []): array
    {
        return array_replace([
            'nip' => self::NIP,
            'nama' => 'Contoh Pegawai',
            'statusKepegawaian' => 'PNS',
            'pangkat' => ['kode' => 'III/a', 'nama' => 'Penata Muda (III/a)'],
            'jabatan' => 'Pengelola Kepegawaian',
            'jenisJabatan' => 'Pelaksana',
            'unit' => ['id' => 1, 'nama' => 'Subbagian Tata Usaha'],
            'diperbarui' => '2026-10-21T08:00:00.000Z',
        ], $changes);
    }

    /**
     * A made-up employee as SIKAP's detail gives them, with contact details.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function detail(array $changes = []): array
    {
        return $this->employee($changes) + ['email' => 'contoh.pegawai@example.com', 'noHp' => '6281234567890'];
    }
}
