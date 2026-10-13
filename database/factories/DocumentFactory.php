<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state: a draft regulation linked to JDIH.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => 'Peraturan '.ucfirst(fake()->unique()->words(3, true)),
            'keterangan' => fake()->sentence(),
            'kategori' => 'regulasi',
            'format' => 'Tautan',
            'nomor' => (string) fake()->numberBetween(1, 99),
            'tahun' => (string) fake()->numberBetween(2015, 2026),
            'berkas' => null,
            'tautan' => 'https://jdih.nttprov.go.id/'.fake()->slug(),
            'urutan' => 100,
            'status' => WorkflowStatus::Draft,
            'penyedia' => 'Subbagian Tata Usaha',
            'pemeriksa' => '',
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => [],
        ];
    }

    /**
     * A document in the given workflow status.
     */
    public function withStatus(WorkflowStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'terbit_pada' => $status === WorkflowStatus::Published ? now() : null,
        ]);
    }
}
