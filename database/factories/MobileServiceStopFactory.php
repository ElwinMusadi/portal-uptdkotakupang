<?php

namespace Database\Factories;

use App\Enums\ServicePointKind;
use App\Models\MobileServiceStop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<MobileServiceStop>
 */
class MobileServiceStopFactory extends Factory
{
    /**
     * Define the model's default state: an active morning Samsat Keliling
     * stop on a weekday of next week.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::now('Asia/Makassar')->addWeek()->startOfWeek(Carbon::MONDAY)->addDays(fake()->numberBetween(0, 4));

        return [
            'jenis' => ServicePointKind::Keliling,
            'unit' => fake()->randomElement(MobileServiceStop::UNITS),
            'nama' => '',
            'tanggal_mulai' => $date->toDateString(),
            'tanggal_selesai' => $date->toDateString(),
            'hari' => [$date->dayOfWeek],
            'mulai' => '08:00',
            'selesai' => '12:00',
            'lokasi' => 'Kantor Kelurahan '.fake()->lastName(),
            'kecamatan' => 'Kec. Oebobo',
            'layanan' => 'Pajak tahunan',
            'catatan' => '',
            'aktif' => true,
            'urutan' => 0,
        ];
    }

    /**
     * A Samsat Keliling unit at a place on the given date.
     */
    public function on(string $date, string $unit = '01'): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis' => ServicePointKind::Keliling,
            'unit' => $unit,
            'nama' => '',
            'tanggal_mulai' => $date,
            'tanggal_selesai' => $date,
            'hari' => [Carbon::parse($date)->dayOfWeek],
        ]);
    }

    /**
     * A fixed outlet open on the given weekdays, without dates.
     *
     * @param  list<int>  $days
     */
    public function outlet(string $name = 'Samsat Corner', array $days = [1, 2, 3, 4, 5]): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis' => ServicePointKind::Gerai,
            'unit' => null,
            'nama' => $name,
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'hari' => $days,
        ]);
    }

    /**
     * A temporary counter open on the given weekdays between two dates.
     *
     * @param  list<int>  $days
     */
    public function counter(string $first, string $last, string $name = 'Loket Kantor Lurah Oebobo', array $days = [1, 2, 3, 4, 5]): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis' => ServicePointKind::Sementara,
            'unit' => null,
            'nama' => $name,
            'tanggal_mulai' => $first,
            'tanggal_selesai' => $last,
            'hari' => $days,
        ]);
    }

    /**
     * Switched off, so the portal does not show it.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['aktif' => false]);
    }
}
