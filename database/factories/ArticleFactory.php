<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state: a draft news item without a cover.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = ucfirst(fake()->unique()->words(4, true)).'.';

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul),
            'kategori' => 'berita',
            'ringkasan' => fake()->sentence(),
            'isi' => '<p>'.fake()->paragraph().'</p>',
            'sampul' => null,
            'keterangan_sampul' => '',
            'kredit_foto' => '',
            'tanggal' => '2026-10-14',
            'sorotan' => false,
            'pengumuman_beranda' => false,
            'status' => WorkflowStatus::Draft,
            'penyedia' => 'Pengelola publikasi',
            'pemeriksa' => '',
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => [],
        ];
    }

    /**
     * An article in the given workflow status.
     */
    public function withStatus(WorkflowStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'terbit_pada' => $status === WorkflowStatus::Published ? now() : null,
        ]);
    }
}
