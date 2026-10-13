<?php

namespace Database\Factories;

use App\Enums\UploadTarget;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Upload>
 */
class UploadFactory extends Factory
{
    /**
     * Define the model's default state: a public news photo not yet used,
     * stored under its own ID as uploads are.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = Str::lower((string) Str::ulid());

        return [
            'id' => $id,
            'tujuan' => UploadTarget::Berita,
            'publik' => true,
            'path' => 'berita/'.now()->format('Y/m')."/{$id}.jpg",
            'nama' => fake()->word().'.jpg',
            'tipe' => 'image/jpeg',
            'ukuran' => fake()->numberBetween(1000, 500000),
            'user_id' => User::factory(),
        ];
    }

    /**
     * A private field attendance photo.
     */
    public function attendance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tujuan' => UploadTarget::Absensi,
            'publik' => false,
            'path' => 'absensi/'.now()->format('Y/m')."/{$attributes['id']}.jpg",
        ]);
    }
}
