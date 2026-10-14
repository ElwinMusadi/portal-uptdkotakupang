<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Documentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documentation>
 */
class DocumentationFactory extends Factory
{
    /**
     * Define the model's default state: a draft activity without photos.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => ucfirst(fake()->unique()->words(4, true)).'.',
            'kategori' => 'keliling',
            'lokasi' => 'Kec. '.fake()->city(),
            'tanggal' => '2026-10-14',
            'deskripsi' => '',
            'foto' => [],
            'status' => WorkflowStatus::Draft,
            'penyedia' => 'Seksi Penetapan dan Penagihan',
            'pemeriksa' => '',
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => [],
        ];
    }

    /**
     * An activity in the given workflow status.
     */
    public function withStatus(WorkflowStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'terbit_pada' => $status === WorkflowStatus::Published ? now() : null,
        ]);
    }

    /**
     * An activity with photos at the given addresses.
     *
     * @param  list<string>  $urls
     */
    public function withPhotos(array $urls): static
    {
        return $this->state(fn (): array => [
            'foto' => array_map(fn (string $url, int $index): array => ['id' => "f{$index}", 'src' => $url, 'keterangan' => ''], $urls, array_keys($urls)),
        ]);
    }
}
