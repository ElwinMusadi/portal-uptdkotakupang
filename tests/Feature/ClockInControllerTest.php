<?php

namespace Tests\Feature;

use App\Actions\ClockIn;
use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClockInControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * The activities' point, as FieldActivityFactory sets it.
     */
    private const float LAT = -10.1637;

    private const float LNG = 123.5974;

    protected function setUp(): void
    {
        parent::setUp();

        // 09.00 WITA on 15 October, still 14 October in UTC (the application's time zone)
        $this->travelTo(Carbon::parse('2026-10-15 09:00', FieldActivity::TIME_ZONE));
    }

    public function test_returns_401_to_guests(): void
    {
        $activity = FieldActivity::factory()->create();

        $this->getJson('/api/absen')->assertUnauthorized();
        $this->postJson("/api/absen/{$activity->id}", $this->clockIn('x'))->assertUnauthorized();
    }

    public function test_lists_the_officers_activities_of_the_day_in_wita_with_their_own_clock_in(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30', FieldActivity::TIME_ZONE));
        $officer = User::factory()->create();
        $colleague = User::factory()->create();
        $second = FieldActivity::factory()->create(['nama' => 'Tilang gabungan Oesapa', 'radius' => 200, 'catatan' => 'Bawa rompi.']);
        $first = FieldActivity::factory()->create(['nama' => 'Tilang gabungan El Tari']);
        $notAssigned = FieldActivity::factory()->create();
        $yesterday = FieldActivity::factory()->create(['tanggal' => '2026-10-14']);
        $tomorrow = FieldActivity::factory()->create(['tanggal' => '2026-10-16']);
        foreach ([$second, $first, $yesterday, $tomorrow] as $activity) {
            $activity->officers()->attach([$officer->id, $colleague->id]);
        }
        $notAssigned->officers()->attach($colleague);
        FieldAttendance::factory()->for($second, 'activity')->for($officer)->create(['waktu' => now()->subMinutes(5), 'jarak' => 42, 'akurasi' => 11]);
        FieldAttendance::factory()->for($first, 'activity')->for($colleague)->create();

        $response = $this->actingAs($officer)->getJson('/api/absen');

        $response->assertOk()->assertExactJson([
            'tanggal' => '2026-10-15',
            'kegiatan' => [
                [
                    'id' => $first->id,
                    'nama' => 'Tilang gabungan El Tari',
                    'tempat' => 'Jl. El Tari',
                    'lat' => self::LAT,
                    'lng' => self::LNG,
                    'radius' => 150,
                    'catatan' => '',
                    'absen' => null,
                ],
                [
                    'id' => $second->id,
                    'nama' => 'Tilang gabungan Oesapa',
                    'tempat' => 'Jl. El Tari',
                    'lat' => self::LAT,
                    'lng' => self::LNG,
                    'radius' => 200,
                    'catatan' => 'Bawa rompi.',
                    'absen' => ['waktu' => '2026-10-14T16:25:00+00:00', 'jarak' => 42, 'akurasi' => 11],
                ],
            ],
        ]);
    }

    public function test_clocks_in_within_the_radius_at_the_servers_time_and_keeps_the_selfie(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);
        $colleague = User::factory()->create();
        $activity->officers()->attach($colleague);
        FieldAttendance::factory()->for($activity, 'activity')->for($colleague)->create();
        $selfie = $this->selfie($officer);

        $response = $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", [
            'foto' => $selfie->url(),
            'lat' => $this->north(149.6),
            'lng' => self::LNG + 0.0000004,
            'akurasi' => 12.2,
            'waktu' => '2026-10-15T07:00:00+08:00',
        ]);

        $response->assertCreated()->assertExactJson(['waktu' => '2026-10-15T01:00:00+00:00', 'jarak' => 150, 'akurasi' => 13]);
        $attendance = FieldAttendance::query()->where('user_id', $officer->id)->sole();
        $this->assertSame($activity->id, $attendance->field_activity_id);
        $this->assertSame($officer->id, $attendance->user_id);
        $this->assertSame(round($this->north(149.6), 6), $attendance->lat);
        $this->assertSame(self::LNG, $attendance->lng);
        $this->assertSame(13, $attendance->akurasi);
        $this->assertSame(150, $attendance->jarak);
        $this->assertSame($selfie->url(), $attendance->foto);
        $this->assertTrue($attendance->waktu->equalTo(now()));
        $this->assertNotNull($selfie->fresh()->dipakai_pada);
    }

    public function test_forbids_clock_in_to_everyone_not_assigned_to_the_activity(): void
    {
        $activity = $this->activityFor(User::factory()->create());

        foreach ([User::factory()->create(), User::factory()->administrator()->create()] as $user) {
            $this->actingAs($user)->postJson("/api/absen/{$activity->id}", $this->clockIn($this->selfie($user)->url()))
                ->assertForbidden()
                ->assertExactJson(['message' => 'Anda tidak ditugaskan di kegiatan ini.']);
        }
        $this->actingAs($user)->postJson('/api/absen/01k7aaaaaaaaaaaaaaaaaaaaaa', $this->clockIn('x'))->assertNotFound();
        $this->assertDatabaseEmpty('field_attendances');
    }

    public function test_opens_clock_in_only_on_the_activitys_date_in_wita(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30', FieldActivity::TIME_ZONE));
        $officer = User::factory()->create();
        $yesterday = $this->activityFor($officer, ['tanggal' => '2026-10-14']);
        $tomorrow = $this->activityFor($officer, ['tanggal' => '2026-10-16']);
        $today = $this->activityFor($officer, ['tanggal' => '2026-10-15']);

        foreach ([$yesterday, $tomorrow] as $activity) {
            $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn('bukan-swafoto', 5000))
                ->assertUnprocessable()
                ->assertExactJson([
                    'message' => 'Clock-in kegiatan ini hanya dibuka pada tanggal kegiatan.',
                    'errors' => ['kegiatan' => ['Clock-in kegiatan ini hanya dibuka pada tanggal kegiatan.']],
                ]);
        }
        $this->actingAs($officer)->postJson("/api/absen/{$today->id}", $this->clockIn($this->selfie($officer)->url()))->assertCreated();
    }

    public function test_refuses_clock_in_outside_the_radius(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);
        $selfie = $this->selfie($officer);

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($selfie->url(), 150.6))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['lokasi' => 'Anda berada 151 m dari lokasi kegiatan. Clock-in hanya dalam radius 150 m.']);
        $this->assertNull($selfie->fresh()->dipakai_pada);

        $activity->update(['radius' => 151]);
        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($selfie->url(), 150.6))->assertCreated();
    }

    public function test_measures_distances_along_the_earths_surface(): void
    {
        $this->assertSame(111195, (new FieldActivity(['lat' => 0, 'lng' => 0]))->distanceTo(0, 1));
        $this->assertSame(55597, (new FieldActivity(['lat' => 60, 'lng' => 0]))->distanceTo(60, 1));
        $this->assertSame(20015087, (new FieldActivity(['lat' => 0, 'lng' => 0]))->distanceTo(0, 180));
        $this->assertSame(109, (new FieldActivity(['lat' => self::LAT, 'lng' => self::LNG]))->distanceTo(self::LAT, 123.5984));
        $this->assertSame(624, (new FieldActivity(['lat' => self::LAT, 'lng' => self::LNG]))->distanceTo(-10.1597, 123.5934));
    }

    public function test_refuses_positions_less_accurate_than_100_m(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);
        $selfie = $this->selfie($officer);

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", ['akurasi' => 100.2] + $this->clockIn($selfie->url()))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['akurasi' => 'Akurasi GPS ±101 m terlalu rendah; batasnya ±100 m. Pindah ke area terbuka lalu perbarui lokasi.']);

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", ['akurasi' => 100] + $this->clockIn($selfie->url()))
            ->assertCreated()
            ->assertJsonPath('akurasi', 100);
    }

    public function test_reports_every_problem_with_a_clock_in_at_once(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", ['akurasi' => 150] + $this->clockIn('bukan-swafoto', 400))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'akurasi' => 'Akurasi GPS ±150 m terlalu rendah; batasnya ±100 m. Pindah ke area terbuka lalu perbarui lokasi.',
                'lokasi' => 'Anda berada 400 m dari lokasi kegiatan. Clock-in hanya dalam radius 150 m.',
                'foto' => 'Ambil swafoto baru untuk clock-in.',
            ]);
    }

    public function test_requires_a_selfie_the_officer_uploaded_in_the_last_15_minutes_for_attendance(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);
        $stale = $this->selfie($officer);
        $this->travel(1)->second();
        $fresh = $this->selfie($officer);
        $this->travel(15)->minutes();

        $refused = [
            'another officer\'s' => $this->selfie(User::factory()->create())->url(),
            'already used' => Upload::factory()->attendance()->for($officer)->create(['dipakai_pada' => now()])->url(),
            'older than 15 minutes' => $stale->url(),
            'a news photo' => Upload::factory()->for($officer)->create()->url(),
            'not an upload' => 'https://example.com/swafoto.jpg',
        ];
        foreach ($refused as $case => $url) {
            $response = $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($url));

            $response->assertUnprocessable();
            $this->assertSame(['foto' => ['Ambil swafoto baru untuk clock-in.']], $response->json('errors'), $case);
        }

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($fresh->url()))->assertCreated();
    }

    public function test_refuses_a_second_clock_in_with_the_time_of_the_first(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);
        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($this->selfie($officer)->url()))->assertCreated();
        $this->travel(20)->minutes();

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($this->selfie($officer)->url(), 5000))
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['kegiatan' => 'Anda sudah clock-in di kegiatan ini pukul 09.00 WITA.']);
        $this->assertSame(1, FieldAttendance::query()->count());
    }

    public function test_answers_a_clock_in_sent_twice_at_once_like_a_repeated_one(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);
        app(ClockIn::class)->handle($officer, $activity, $this->selfie($officer), self::LAT, self::LNG, 10, 0);

        try {
            app(ClockIn::class)->handle($officer, $activity, $this->selfie($officer), self::LAT, self::LNG, 10, 0);
            $this->fail('The second clock-in was recorded.');
        } catch (ValidationException $exception) {
            $this->assertSame(['kegiatan' => ['Anda sudah clock-in di kegiatan ini.']], $exception->errors());
        }
        $this->assertSame(1, FieldAttendance::query()->count());
    }

    public function test_shares_the_upload_limit_of_30_a_minute_per_employee(): void
    {
        $officer = User::factory()->create();
        $activity = $this->activityFor($officer);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn('bukan-swafoto'))->assertUnprocessable();
        }

        $this->actingAs($officer)->postJson("/api/absen/{$activity->id}", $this->clockIn($this->selfie($officer)->url()))->assertTooManyRequests();
    }

    public function test_validates_the_clock_in(): void
    {
        $officer = User::factory()->create();
        $closed = $this->activityFor($officer, ['tanggal' => '2026-10-01']);

        $this->actingAs($officer)->postJson("/api/absen/{$closed->id}", [])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'foto' => 'Ambil swafoto untuk clock-in.',
                'lat' => 'Lokasi GPS belum terekam.',
                'lng' => 'Lokasi GPS belum terekam.',
                'akurasi' => 'Akurasi GPS belum terekam.',
            ]);
        $this->actingAs($officer)->postJson("/api/absen/{$closed->id}", ['foto' => ['x'], 'lat' => 90.1, 'lng' => 'timur', 'akurasi' => -1])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'foto' => 'Ambil swafoto untuk clock-in.',
                'lat' => 'Lokasi GPS tidak valid.',
                'lng' => 'Lokasi GPS tidak valid.',
                'akurasi' => 'Akurasi GPS tidak valid.',
            ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activityFor(User $officer, array $attributes = []): FieldActivity
    {
        $activity = FieldActivity::factory()->create($attributes);
        $activity->officers()->attach($officer);

        return $activity;
    }

    private function selfie(User $officer): Upload
    {
        return Upload::factory()->attendance()->for($officer)->create();
    }

    /**
     * A clock-in the given distance north of the activities' point.
     *
     * @return array<string, mixed>
     */
    private function clockIn(string $selfie, float $metres = 0): array
    {
        return ['foto' => $selfie, 'lat' => $this->north($metres), 'lng' => self::LNG, 'akurasi' => 8];
    }

    /**
     * The latitude the given distance north of the activities' point.
     */
    private function north(float $metres): float
    {
        return self::LAT + rad2deg($metres / 6371000);
    }
}
