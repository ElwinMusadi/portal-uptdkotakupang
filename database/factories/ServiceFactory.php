<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state: a draft service.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = ucfirst(fake()->unique()->words(3, true));

        return [
            'nama' => $nama,
            'slug' => Str::slug($nama),
            'ringkasan' => fake()->sentence(),
            'loket' => 'Loket '.fake()->numberBetween(1, 3),
            'jenis' => 'layanan',
            'cek_fisik' => false,
            'non_tunai' => true,
            'diwakilkan' => true,
            'label' => [],
            'persyaratan' => [['id' => 'p1', 'dokumen' => 'Fotokopi KTP pemilik', 'jumlah' => '2 lembar']],
            'catatan' => '',
            'unggulan' => false,
            'urutan' => 100,
            'status' => WorkflowStatus::Draft,
            'penyedia' => 'Seksi Verifikasi',
            'pemeriksa' => '',
            'catatan_revisi' => '',
            'terbit_pada' => null,
            'riwayat' => [],
        ];
    }

    /**
     * A service in the given workflow status.
     */
    public function withStatus(WorkflowStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'terbit_pada' => $status === WorkflowStatus::Published ? now() : null,
        ]);
    }
}
