<?php

namespace Database\Factories;

use App\Models\FieldActivity;
use App\Models\FieldAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldAttendance>
 */
class FieldAttendanceFactory extends Factory
{
    /**
     * Define the model's default state: a clock-in at the activity's point.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'field_activity_id' => FieldActivity::factory(),
            'user_id' => User::factory(),
            'waktu' => now(),
            'lat' => -10.163700,
            'lng' => 123.597400,
            'akurasi' => 12,
            'jarak' => 0,
            'foto' => 'http://localhost/api/berkas/01m4aaaaaaaaaaaaaaaaaaaaaa',
        ];
    }
}
