<?php

namespace Tests\Feature;

use App\Actions\LookUpVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class VehicleLookupTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string UPSTREAM = 'https://api.uptdpenda-kupang.web.id/api/v1/vehicle/*';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.njkb.base_url' => 'https://api.uptdpenda-kupang.web.id',
            'services.njkb.client_id' => 'pintu-uji.access',
            'services.njkb.client_secret' => 'rahasia-uji',
        ]);
        Sleep::fake();
    }

    public function test_returns_401_to_guests(): void
    {
        Http::fake();

        $this->getJson('/api/kendaraan/DH1234AB')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_gives_an_employee_the_vehicle_as_the_calculator_needs_it(): void
    {
        Http::fake([self::UPSTREAM => Http::response($this->vehicle(), 200, ['Content-Type' => 'application/json'])]);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/dh 1234-ab');

        $response->assertOk()->assertExactJson([
            'nopol' => 'DH1234AB',
            'nama' => 'PEMILIK CONTOH',
            'jenis' => 'MINIBUS',
            'jatuhTempoStnk' => '2031-12-31',
            'jatuhTempoPajak' => '2026-11-20',
            'njkb' => 150000000,
            'njub' => 0,
            'bobot' => 1.05,
            'warnaPlat' => 'hitam',
            'njkbStatus' => 'matched',
        ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.uptdpenda-kupang.web.id/api/v1/vehicle/DH1234AB'
            && $request->method() === 'GET'
            && $request->header('CF-Access-Client-Id') === ['pintu-uji.access']
            && $request->header('CF-Access-Client-Secret') === ['rahasia-uji']
            && $request->header('Accept') === ['application/json']);
        $this->assertStringNotContainsString('SYNTHETIC', $response->getContent());
    }

    public function test_uses_the_vehicle_kind_weight_when_the_api_has_no_njkb_reference(): void
    {
        $vehicle = $this->vehicle(['njkb_status' => 'not_found', 'njkb' => null]);
        $vehicle['vehicle']['category_raw'] = 'PICK UP';
        Http::fake([self::UPSTREAM => Http::response($vehicle, 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertOk()
            ->assertJsonPath('njkb', 0)
            ->assertJsonPath('bobot', 1.085)
            ->assertJsonPath('njkbStatus', 'not_found');
    }

    public function test_uses_the_weight_the_api_gives_without_an_njkb_reference(): void
    {
        $vehicle = $this->vehicle(['njkb_status' => 'not_found', 'njkb' => ['value' => null, 'weight' => '1.200000']]);
        $vehicle['vehicle']['category_raw'] = null;
        Http::fake([self::UPSTREAM => Http::response($vehicle, 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertOk()
            ->assertJsonPath('njkb', 0)
            ->assertJsonPath('bobot', 1.2)
            ->assertJsonPath('jenis', 'MOBIL PENUMPANG');
    }

    #[TestWith([null, 404])]
    #[TestWith([['status' => 'vehicle_not_found'], 200])]
    #[TestWith([['status' => 'invalid_request'], 200])]
    #[TestWith([['vehicle_status' => 'not_found'], 200])]
    public function test_answers_404_when_the_vehicle_is_unknown(?array $body, int $status): void
    {
        Http::fake([self::UPSTREAM => Http::response($body, $status, ['Content-Type' => 'application/json'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data kendaraan tidak ditemukan.']);
    }

    public function test_rejects_plate_numbers_in_another_form_without_asking_the_api(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/1234DH')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nopol' => 'Tulis nomor polisi seperti DH 1234 AB.']);
        $this->assertSame(['status' => 'not_found'], app(LookUpVehicle::class)->handle('1234DH'));
        Http::assertNothingSent();
    }

    #[TestWith(['2026-03-01', '2026-03-01'])]
    #[TestWith(['2026-03-01T00:00:00+08:00', '2026-03-01'])]
    #[TestWith(['01/03/2026', '2026-03-01'])]
    #[TestWith(['1.3.2026', '2026-03-01'])]
    #[TestWith(['31-12-2031', '2031-12-31'])]
    #[TestWith(['2026-02-30', null])]
    #[TestWith(['1899-12-31', null])]
    #[TestWith(['2101-01-01', null])]
    #[TestWith(['bulan depan', null])]
    public function test_reads_due_dates_written_either_way(string $raw, ?string $date): void
    {
        $vehicle = $this->vehicle();
        $vehicle['tax']['notice_valid_until'] = $raw;
        Http::fake([self::UPSTREAM => Http::response($vehicle, 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertOk()
            ->assertJsonPath('jatuhTempoPajak', $date);
    }

    #[TestWith([5, 5])]
    #[TestWith([0, 8])]
    #[TestWith([61, 8])]
    public function test_waits_for_the_api_as_long_as_configured_up_to_a_minute(int $configured, int $timeout): void
    {
        config(['services.njkb.timeout' => $configured]);
        $options = [];
        Http::fake(function (Request $request, array $requestOptions) use (&$options) {
            $options[] = [$requestOptions['connect_timeout'], $requestOptions['timeout']];

            return Http::response(null, 404);
        });

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')->assertNotFound();

        $this->assertSame([[3, $timeout]], $options);
    }

    #[TestWith(['reference_unavailable', 'Referensi NJKB belum tersedia untuk kendaraan ini. Isi NJKB secara manual.'])]
    #[TestWith(['conflict', 'Data kendaraan bertentangan dengan referensi NJKB. Isi NJKB secara manual.'])]
    #[TestWith(['ambiguous', 'Referensi NJKB kendaraan ini perlu diperiksa petugas. Isi NJKB secara manual.'])]
    public function test_does_not_use_an_njkb_reference_that_cannot_be_trusted(string $status, string $message): void
    {
        Http::fake([self::UPSTREAM => Http::response($this->vehicle(['njkb_status' => $status]), 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertUnprocessable()
            ->assertExactJson(['message' => $message]);
    }

    #[TestWith([['njkb' => ['value' => '150000000.50', 'weight' => '1.05']]])]
    #[TestWith([['njkb' => ['value' => '0.00', 'weight' => '1.05']]])]
    #[TestWith([['njkb' => ['value' => '150000000', 'weight' => 'satu']]])]
    #[TestWith([['njkb' => ['value' => '150000000', 'weight' => '0.000']]])]
    #[TestWith([['njkb_status' => 'lain']])]
    #[TestWith([['nopol' => 'DH']])]
    public function test_treats_an_unexpected_answer_as_unavailable(array $changes): void
    {
        Http::fake([self::UPSTREAM => Http::response($this->vehicle($changes), 200, ['Content-Type' => 'application/json'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'Data kendaraan sedang tidak dapat diambil. Coba lagi beberapa saat lagi.']);
    }

    public function test_treats_an_answer_that_is_not_json_as_unavailable(): void
    {
        Http::fakeSequence(self::UPSTREAM)
            ->push('<html>Access</html>', 200, ['Content-Type' => 'text/html'])
            ->push(json_encode($this->vehicle()), 200, ['Content-Type' => 'text/html']);
        $employee = User::factory()->create();

        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')->assertServiceUnavailable();
        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')->assertServiceUnavailable();
    }

    public function test_retries_once_after_a_gateway_failure_or_a_dropped_connection(): void
    {
        Http::fakeSequence(self::UPSTREAM)
            ->push(null, 503)
            ->push($this->vehicle(), 200, ['Content-Type' => 'application/json'])
            ->pushFailedConnection()
            ->push($this->vehicle(), 200, ['Content-Type' => 'application/json'])
            ->push(null, 504)
            ->push(null, 502);
        $employee = User::factory()->create();

        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')->assertOk();
        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')->assertOk();
        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')->assertServiceUnavailable();

        Http::assertSentCount(6);
        Sleep::assertSleptTimes(3);
    }

    #[TestWith([500])]
    #[TestWith([401])]
    #[TestWith([403])]
    #[TestWith([302])]
    public function test_does_not_retry_other_failures_or_follow_redirects(int $status): void
    {
        Http::fake([self::UPSTREAM => Http::response(null, $status, $status === 302 ? ['Location' => 'https://lain.example/'] : [])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')->assertServiceUnavailable();

        Http::assertSentCount(1);
    }

    public function test_does_not_retry_a_timeout(): void
    {
        Http::fake([self::UPSTREAM => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')->assertServiceUnavailable();

        Sleep::assertNeverSlept();
    }

    public function test_waits_as_long_as_the_api_asks_after_too_many_requests(): void
    {
        Http::fake([self::UPSTREAM => Http::response(null, 429, ['Retry-After' => '40'])]);
        $employee = User::factory()->create();

        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'Layanan data kendaraan sedang sibuk. Coba lagi dalam 40 detik.']);
        $this->travel(10)->seconds();
        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'Layanan data kendaraan sedang sibuk. Coba lagi dalam 30 detik.']);

        Http::assertSentCount(1);
        $this->assertTrue(Cache::has(LookUpVehicle::WAIT_UNTIL));
    }

    #[TestWith(['7200', 3600])]
    #[TestWith(['Wed, 14 Oct 2026 08:01:30 GMT', 90])]
    #[TestWith(['Wed, 14 Oct 2026 07:59:00 GMT', 60])]
    #[TestWith(['nanti', 60])]
    #[TestWith(['', 60])]
    public function test_reads_how_long_to_wait_from_retry_after_for_at_most_an_hour(string $retryAfter, int $seconds): void
    {
        $this->travelTo(Carbon::parse('2026-10-14 08:00:00', 'UTC'));
        Http::fake([self::UPSTREAM => Http::response(null, 429, $retryAfter === '' ? [] : ['Retry-After' => $retryAfter])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => "Layanan data kendaraan sedang sibuk. Coba lagi dalam {$seconds} detik."]);
    }

    #[TestWith(['services.njkb.client_secret', ''])]
    #[TestWith(['services.njkb.client_id', null])]
    #[TestWith(['services.njkb.base_url', 'http://api.uptdpenda-kupang.web.id'])]
    public function test_asks_nothing_while_the_api_is_not_configured(string $key, ?string $value): void
    {
        config([$key => $value]);
        Http::fake();
        Log::spy();

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'Pencarian data kendaraan belum dikonfigurasi.']);
        Http::assertNothingSent();
        Log::shouldHaveReceived('error')->once()->with('NJKB API is not configured');
    }

    public function test_never_logs_the_plate_number_or_personal_data(): void
    {
        Log::spy();
        Http::fake([self::UPSTREAM => Http::response(['status' => 'upstream_error', 'error' => ['code' => 'bpad_down'], 'owner' => ['name' => 'PEMILIK CONTOH']], 500, ['Content-Type' => 'application/json', 'X-Request-ID' => 'req-1'])]);

        $this->actingAs(User::factory()->create())->getJson('/api/kendaraan/DH1234AB')->assertServiceUnavailable();

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
            $this->assertSame(['reason' => 'http', 'request_id' => 'req-1', 'http_status' => 500, 'status' => 'upstream_error', 'error_code' => 'bpad_down'], $context);

            return $message === 'NJKB API request failed';
        });
    }

    public function test_limits_lookups_to_30_a_minute_per_employee(): void
    {
        Http::fake([self::UPSTREAM => Http::response(null, 404)]);
        $employee = User::factory()->create();

        for ($lookup = 1; $lookup <= 30; $lookup++) {
            $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')->assertNotFound();
        }

        $this->actingAs($employee)->getJson('/api/kendaraan/DH1234AB')
            ->assertTooManyRequests()
            ->assertExactJson(['message' => 'Terlalu banyak pencarian kendaraan. Coba lagi sebentar lagi.']);
    }

    #[TestWith(['PUTIH', 'putih'])]
    #[TestWith(['Merah', 'merah'])]
    #[TestWith(['TNKB HITAM', 'hitam'])]
    #[TestWith(['DASAR MERAH TULISAN PUTIH', 'merah'])]
    #[TestWith(['KUNING', null])]
    #[TestWith(['DASAR KUNING TULISAN HITAM', null])]
    #[TestWith(['PUTIH HITAM', null])]
    #[TestWith(['', null])]
    #[TestWith([null, null])]
    public function test_reads_the_plate_colours_the_calculator_supports(?string $raw, ?string $colour): void
    {
        $this->assertSame($colour, LookUpVehicle::plateColour($raw));
    }

    public function test_gives_a_visitor_the_vehicle_once_the_chassis_characters_match(): void
    {
        $vehicle = $this->vehicle();
        $vehicle['vehicle']['chassis_number'] = 'synthetic-cha5678';
        Http::fake([self::UPSTREAM => Http::response($vehicle, 200, ['Content-Type' => 'application/json'])]);

        $response = $this->postJson('/api/public/kendaraan', ['nopol' => 'dh 1234 ab', 'rangka' => 'a5678']);

        $response->assertOk()
            ->assertJsonPath('nopol', 'DH1234AB')
            ->assertJsonPath('nama', 'PEMILIK CONTOH')
            ->assertJsonPath('njkb', 150000000)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
        $this->assertStringNotContainsStringIgnoringCase('synthetic', $response->getContent());
        $this->assertArrayNotHasKey('chassis', $response->json());
    }

    public function test_answers_a_wrong_chassis_an_unknown_vehicle_and_a_missing_chassis_alike(): void
    {
        $withoutChassis = $this->vehicle();
        $withoutChassis['vehicle']['chassis_number'] = null;
        Http::fakeSequence(self::UPSTREAM)
            ->push($this->vehicle(), 200, ['Content-Type' => 'application/json'])
            ->push(null, 404)
            ->push($withoutChassis, 200, ['Content-Type' => 'application/json'])
            ->push($this->vehicle(['njkb_status' => 'conflict']), 200, ['Content-Type' => 'application/json']);

        foreach (['A5679', 'A5678', 'A5678', 'B5678'] as $chassis) {
            $this->postJson('/api/public/kendaraan', ['nopol' => 'DH1234AB', 'rangka' => $chassis])
                ->assertNotFound()
                ->assertExactJson(['message' => 'Nomor polisi atau nomor rangka tidak cocok dengan data kendaraan.']);
        }
    }

    public function test_tells_a_confirmed_visitor_when_the_njkb_reference_cannot_be_used(): void
    {
        Http::fake([self::UPSTREAM => Http::response($this->vehicle(['njkb_status' => 'ambiguous']), 200, ['Content-Type' => 'application/json'])]);

        $this->postJson('/api/public/kendaraan', ['nopol' => 'DH1234AB', 'rangka' => 'A5678'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'Referensi NJKB kendaraan ini perlu diperiksa petugas. Silakan cek biaya pajak di loket Samsat.']);
    }

    public function test_validates_a_visitors_check(): void
    {
        Http::fake();

        $this->postJson('/api/public/kendaraan', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nopol' => 'Tulis nomor polisi kendaraan.', 'rangka' => 'Tulis 5 digit terakhir nomor rangka.']);
        $this->postJson('/api/public/kendaraan', ['nopol' => '1234', 'rangka' => '5678'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nopol' => 'Tulis nomor polisi seperti DH 1234 AB.', 'rangka' => 'Tulis tepat 5 digit terakhir nomor rangka seperti tertera di STNK.']);
        Http::assertNothingSent();
    }

    public function test_tells_a_visitor_when_vehicle_data_cannot_be_reached(): void
    {
        Http::fake([self::UPSTREAM => Http::response(null, 500)]);

        $this->postJson('/api/public/kendaraan', ['nopol' => 'DH1234AB', 'rangka' => 'A5678'])
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'Data kendaraan sedang tidak dapat diambil. Coba lagi beberapa saat lagi.']);
    }

    public function test_limits_visitor_checks_per_address_and_for_the_whole_portal(): void
    {
        Http::fake([self::UPSTREAM => Http::response(null, 404)]);
        $check = fn (string $address) => $this->withServerVariables(['REMOTE_ADDR' => $address])
            ->postJson('/api/public/kendaraan', ['nopol' => 'DH1234AB', 'rangka' => 'A5678']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $check('10.0.0.1')->assertNotFound();
        }
        $check('10.0.0.1')->assertTooManyRequests()
            ->assertExactJson(['message' => 'Terlalu banyak pengecekan dari jaringan ini. Coba lagi beberapa saat lagi.']);

        for ($address = 2; $address <= 6; $address++) {
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $check("10.0.0.{$address}")->assertNotFound();
            }
        }
        $check('10.0.0.7')->assertTooManyRequests();
    }

    public function test_limits_one_address_to_20_checks_an_hour(): void
    {
        Http::fake([self::UPSTREAM => Http::response(null, 404)]);

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->postJson('/api/public/kendaraan', ['nopol' => 'DH1234AB', 'rangka' => 'A5678'])->assertNotFound();
            if ($attempt % 5 === 0) {
                $this->travel(61)->seconds();
            }
        }

        $this->postJson('/api/public/kendaraan', ['nopol' => 'DH1234AB', 'rangka' => 'A5678'])->assertTooManyRequests();
    }

    /**
     * A synthetic answer of the private vehicle API, in the shape of its
     * documented contract.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function vehicle(array $changes = []): array
    {
        return array_replace([
            'status' => 'vehicle_found',
            'vehicle_status' => 'found',
            'njkb_status' => 'matched',
            'nopol' => 'DH1234AB',
            'vehicle' => [
                'category' => 'MOBIL PENUMPANG',
                'category_raw' => 'MINIBUS',
                'type' => 'CONTOH MODEL 1.5 M/T',
                'year' => 2024,
                'chassis_number' => 'SYNTHETIC-CHA5678',
                'engine_number' => 'SYNTHETIC-ENGINE',
            ],
            'registration' => ['bpkb_number' => 'SYNTHETIC-BPKB', 'stnk_valid_until' => '2031-12-31', 'plate_color' => 'HITAM'],
            'tax' => ['notice_valid_until' => '2026-11-20', 'kohir' => 'SYNTHETIC-KOHIR'],
            'owner' => ['name' => 'PEMILIK CONTOH', 'identity_number' => '0000000000000000', 'address' => 'ALAMAT CONTOH'],
            'njkb' => ['value' => '150000000.00', 'weight' => '1.050000', 'dpp_pkb' => '157500000.00'],
        ], $changes);
    }
}
