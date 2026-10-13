<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicMessageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_receives_a_message_and_returns_its_ticket_without_logging_its_content(): void
    {
        Log::spy();

        $response = $this->postJson('/api/public/pesan', $this->form());

        $response->assertCreated();
        $message = Message::query()->sole();
        $response->assertExactJson(['tiket' => $message->tiket]);
        $this->assertSame(
            ['pengaduan', 'Yohanes', '+62 812-3456-7890', 'Samsat Keliling', 'Petugas datang terlambat satu jam.', 'formulir', 'baru'],
            [$message->jenis, $message->nama, $message->kontak, $message->layanan, $message->isi, $message->sumber, $message->status],
        );
        Log::shouldHaveReceived('info')->once()->with('message.received', ['tiket' => $message->tiket]);
    }

    public function test_starts_no_session_and_forbids_caching(): void
    {
        $response = $this->withHeader('Referer', config('app.url').'/kontak')->postJson('/api/public/pesan', $this->form());

        $response->assertCreated()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_requires_a_contact_consent_and_a_message_of_ten_characters(): void
    {
        $response = $this->postJson('/api/public/pesan', ['kontak' => '', 'isi' => 'Halo', 'persetujuan' => false] + $this->form());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'kontak' => 'Isi email atau nomor WhatsApp agar kami bisa membalas.',
            'isi' => 'Pesan minimal 10 karakter.',
            'persetujuan' => 'Setujui penggunaan data untuk menindaklanjuti pesan.',
        ]);
        $this->assertDatabaseCount('messages', 0);
    }

    #[TestWith(['warga@example.com', true])]
    #[TestWith(['081234567890', true])]
    #[TestWith(['+62 812-3456-7890', true])]
    #[TestWith(['12345', false])]
    #[TestWith(['hubungi saya', false])]
    public function test_accepts_an_email_or_a_phone_number_as_contact(string $kontak, bool $valid): void
    {
        $response = $this->postJson('/api/public/pesan', ['kontak' => $kontak] + $this->form());

        $valid
            ? $response->assertCreated()
            : $response->assertUnprocessable()->assertJsonValidationErrors(['kontak' => 'Isi email atau nomor WhatsApp yang valid.']);
    }

    public function test_ignores_a_source_and_status_sent_by_the_visitor(): void
    {
        $this->postJson('/api/public/pesan', ['sumber' => 'telepon', 'status' => 'selesai', 'ditangani' => 'x'] + $this->form())->assertCreated();

        $message = Message::query()->sole();
        $this->assertSame(['formulir', 'baru', ''], [$message->sumber, $message->status, $message->ditangani]);
    }

    public function test_answers_a_filled_hidden_field_like_a_success_but_stores_nothing(): void
    {
        $response = $this->postJson('/api/public/pesan', ['kode_tambahan' => 'https://spam.example'] + $this->form());

        $response->assertCreated()->assertJsonStructure(['tiket']);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_accepts_at_most_five_messages_per_hour_from_one_address(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/public/pesan', $this->form())->assertCreated();
        }

        $response = $this->postJson('/api/public/pesan', $this->form());

        $response->assertTooManyRequests()->assertExactJson([
            'message' => 'Terlalu banyak pesan dari jaringan ini. Coba lagi dalam satu jam, atau hubungi kami lewat kanal lain.',
        ]);
        $this->assertDatabaseCount('messages', 5);
        $this->travel(61)->minutes();
        $this->postJson('/api/public/pesan', $this->form())->assertCreated();
    }

    /**
     * @return array{jenis: string, nama: string, kontak: string, layanan: string, isi: string, persetujuan: bool}
     */
    private function form(): array
    {
        return [
            'jenis' => 'pengaduan',
            'nama' => 'Yohanes',
            'kontak' => '+62 812-3456-7890',
            'layanan' => 'Samsat Keliling',
            'isi' => 'Petugas datang terlambat satu jam.',
            'persetujuan' => true,
        ];
    }
}
