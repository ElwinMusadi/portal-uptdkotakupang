<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state: a new question from the portal form.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tiket' => Message::newTicket(),
            'kode_lacak' => Message::newTrackingCode(),
            'jenis' => 'pertanyaan',
            'nama' => fake()->name(),
            'kontak' => fake()->safeEmail(),
            'layanan' => 'Pajak tahunan',
            'isi' => 'Apakah pajak tahunan bisa dibayar di Samsat Keliling?',
            'sumber' => 'formulir',
            'status' => 'baru',
            'diterima' => now(),
            'ditangani' => '',
            'urutan' => 0,
        ];
    }
}
