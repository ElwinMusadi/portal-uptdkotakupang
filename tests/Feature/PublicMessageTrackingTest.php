<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\MessageResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicMessageTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_shows_a_message_with_only_the_replies_for_the_sender_and_no_names(): void
    {
        $this->travelTo(Carbon::parse('2026-10-17 01:00:00', 'UTC'));
        $message = $this->message(['status' => 'diproses', 'ditangani' => 'Maria Bria']);
        $staff = User::factory()->create(['name' => 'Maria Bria']);
        $this->respond($message, $staff, 'Cek ulang dengan koordinator keliling.', false);
        $this->travel(2)->hours();
        $this->respond($message, $staff, 'Mohon maaf, jadwal sudah kami perbaiki.', true);

        $response = $this->getJson("/api/public/pesan/{$message->kode_lacak}");

        $response->assertOk()->assertExactJson([
            'tiket' => 'PINTU-7KQ2MX',
            'jenis' => 'pengaduan',
            'layanan' => 'Samsat Keliling',
            'isi' => 'Petugas datang terlambat satu jam.',
            'diterima' => '2026-10-17T01:00:00+00:00',
            'status' => 'diproses',
            'balasan' => [['isi' => 'Mohon maaf, jadwal sudah kami perbaiki.', 'waktu' => '2026-10-17T03:00:00+00:00']],
        ]);
        $this->assertSame([], $response->headers->getCookies());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_answers_404_for_an_unknown_code_and_422_for_query_parameters(): void
    {
        $message = $this->message();

        $this->getJson('/api/public/pesan/AbCdEfGhIjKlMnOpQrStUv')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Pesan tidak ditemukan. Periksa kembali tautan lacak Anda, atau lacak dengan nomor tiket di halaman Kontak.']);
        $this->getJson("/api/public/pesan/{$message->kode_lacak}?tiket=PINTU-7KQ2MX")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['query' => 'Parameter kueri tidak didukung.']);
        $this->getJson('/api/public/pesan/'.substr($message->kode_lacak, 1))->assertNotFound();
        $this->getJson('/api/public/pesan/PINTU-7KQ2MX')->assertNotFound();
    }

    #[TestWith(['PINTU-7KQ2MX', '+62 812-3456-7890'])]
    #[TestWith(['pintu-7kq2mx', '081234567890'])]
    #[TestWith(['7KQ2MX', '6281234567890'])]
    #[TestWith([' PINTU 7KQ2MX ', '0812 3456 7890'])]
    #[TestWith(['PINTU-7KQ2MX', '812-3456-7890'])]
    public function test_finds_a_message_by_its_ticket_and_contact_as_the_sender_types_them_again(string $ticket, string $contact): void
    {
        $message = $this->message();

        $response = $this->postJson('/api/public/pesan/lacak', ['tiket' => $ticket, 'kontak' => $contact]);

        $response->assertOk()->assertExactJson(['kode' => $message->kode_lacak]);
        $this->assertSame([], $response->headers->getCookies());
    }

    public function test_finds_a_message_sent_with_an_email_address_in_any_case(): void
    {
        $message = $this->message(['kontak' => 'Warga.Kupang@Example.com']);

        $this->postJson('/api/public/pesan/lacak', ['tiket' => 'PINTU-7KQ2MX', 'kontak' => ' warga.kupang@example.com '])
            ->assertOk()
            ->assertExactJson(['kode' => $message->kode_lacak]);
    }

    #[TestWith(['PINTU-7KQ2MY', '081234567890'])]
    #[TestWith(['PINTU-7KQ2MX', '081234567891'])]
    #[TestWith(['PINTU-7KQ2MX', '6281234567890@example.com'])]
    #[TestWith(['PINTU-7KQ2MX', 'Yohanes'])]
    public function test_answers_the_same_404_for_a_wrong_ticket_or_contact(string $ticket, string $contact): void
    {
        $this->message();

        $this->postJson('/api/public/pesan/lacak', ['tiket' => $ticket, 'kontak' => $contact])
            ->assertNotFound()
            ->assertExactJson(['message' => 'Nomor tiket dan kontak tidak cocok dengan pesan mana pun. Periksa kembali keduanya.']);
    }

    public function test_never_finds_a_message_recorded_without_a_contact_address(): void
    {
        $this->message(['kontak' => '', 'sumber' => 'kotak-saran']);

        $this->postJson('/api/public/pesan/lacak', ['tiket' => 'PINTU-7KQ2MX', 'kontak' => 'tidak ada'])->assertNotFound();
    }

    public function test_requires_the_ticket_and_the_contact(): void
    {
        $this->postJson('/api/public/pesan/lacak', ['tiket' => '', 'kontak' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'tiket' => 'Isi nomor tiket pesan Anda.',
                'kontak' => 'Isi email atau nomor WhatsApp yang Anda pakai saat mengirim pesan.',
            ]);
    }

    public function test_allows_five_lookups_a_minute_from_one_address(): void
    {
        $this->message();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/public/pesan/lacak', ['tiket' => 'PINTU-7KQ2MX', 'kontak' => "08123456789{$attempt}"])->assertNotFound();
        }

        $this->postJson('/api/public/pesan/lacak', ['tiket' => 'PINTU-7KQ2MX', 'kontak' => '081234567890'])
            ->assertTooManyRequests()
            ->assertExactJson(['message' => 'Terlalu banyak percobaan dari jaringan ini. Coba lagi beberapa saat lagi.']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function message(array $attributes = []): Message
    {
        return Message::factory()->create([
            'tiket' => 'PINTU-7KQ2MX',
            'jenis' => 'pengaduan',
            'nama' => 'Yohanes',
            'kontak' => '+62 812-3456-7890',
            'layanan' => 'Samsat Keliling',
            'isi' => 'Petugas datang terlambat satu jam.',
            ...$attributes,
        ]);
    }

    private function respond(Message $message, User $staff, string $text, bool $forSender): void
    {
        (new MessageResponse)->forceFill(['message_id' => $message->id, 'user_id' => $staff->id, 'isi' => $text, 'untuk_pelapor' => $forSender])->save();
    }
}
