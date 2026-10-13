<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Message;
use App\Models\MessageResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function routes(): array
    {
        return [
            'list' => ['GET', '/api/koleksi/pesan'],
            'record' => ['POST', '/api/koleksi/pesan'],
            'read' => ['GET', '/api/koleksi/pesan/{id}'],
            'respond' => ['PATCH', '/api/koleksi/pesan/{id}'],
        ];
    }

    #[DataProvider('routes')]
    public function test_returns_401_to_guests_and_403_to_employees_without_the_module(string $method, string $uri): void
    {
        $message = Message::factory()->create();
        $uri = str_replace('{id}', $message->id, $uri);

        $this->json($method, $uri, $this->recorded())->assertUnauthorized();
        $this->actingAs(User::factory()->withModules(DashboardModule::Faq)->create())
            ->json($method, $uri, $this->recorded())
            ->assertForbidden()
            ->assertExactJson(['message' => 'Modul Pesan & Pengaduan belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_lists_messages_newest_first_with_their_responses(): void
    {
        $this->travelTo(Carbon::parse('2026-10-13 08:00:00', 'UTC'));
        $older = Message::factory()->create(['diterima' => now()->subDay()]);
        $newest = Message::factory()->create([
            'tiket' => 'PINTU-ABC234',
            'jenis' => 'pengaduan',
            'nama' => 'Yohanes',
            'kontak' => '081234567890',
            'isi' => 'Antrean terlalu lama.',
            'status' => 'diproses',
            'ditangani' => 'Maria Bria',
        ]);
        $staff = User::factory()->withModules(DashboardModule::Pesan)->create(['name' => 'Maria Bria']);
        $response = (new MessageResponse)->forceFill(['message_id' => $newest->id, 'user_id' => $staff->id, 'isi' => 'Kami tambah petugas loket.']);
        $response->save();

        $result = $this->actingAs($staff)->getJson('/api/koleksi/pesan');

        $result->assertOk()->assertJsonPath('0', [
            'id' => $newest->id,
            'tiket' => 'PINTU-ABC234',
            'jenis' => 'pengaduan',
            'nama' => 'Yohanes',
            'kontak' => '081234567890',
            'layanan' => 'Pajak tahunan',
            'isi' => 'Antrean terlalu lama.',
            'sumber' => 'formulir',
            'status' => 'diproses',
            'diterima' => '2026-10-13T08:00:00+00:00',
            'ditangani' => 'Maria Bria',
            'tanggapan' => [['id' => $response->id, 'isi' => 'Kami tambah petugas loket.', 'oleh' => 'Maria Bria', 'waktu' => '2026-10-13T08:00:00+00:00']],
            'urutan' => 0,
            'dibuat' => '2026-10-13T08:00:00+00:00',
            'diperbarui' => '2026-10-13T08:00:00+00:00',
        ]);
        $this->assertSame([$newest->id, $older->id], $result->json('*.id'));
    }

    public function test_records_a_message_from_another_channel_under_a_new_ticket(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($staff)->postJson('/api/koleksi/pesan', [
            'tiket' => 'PINTU-AAAAAA',
            'status' => 'selesai',
            'tanggapan' => [['isi' => 'Palsu']],
        ] + $this->recorded());

        $response->assertCreated()
            ->assertJsonPath('status', 'baru')
            ->assertJsonPath('sumber', 'kotak-saran')
            ->assertJsonPath('kontak', '')
            ->assertJsonPath('tanggapan', []);
        $this->assertMatchesRegularExpression('/^PINTU-[A-HJ-NP-Z2-9]{6}$/', $response->json('tiket'));
        $this->assertNotSame('PINTU-AAAAAA', $response->json('tiket'));
        $this->assertDatabaseHas('activities', ['user_id' => $staff->id, 'module' => 'pesan', 'action' => 'mencatat', 'target' => $response->json('tiket')]);
    }

    public function test_validates_a_recorded_message(): void
    {
        $response = $this->actingAs($this->staff())->postJson('/api/koleksi/pesan', [
            'kontak' => 'bukan kontak',
            'layanan' => 'Layanan lain',
            'isi' => '',
            'sumber' => 'surat',
        ] + $this->recorded());

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'kontak' => 'Isi email atau nomor WhatsApp yang valid.',
            'layanan' => 'Layanan tidak valid.',
            'isi' => 'Isi pesan wajib diisi.',
            'sumber' => 'Sumber pesan tidak valid.',
        ]);
    }

    public function test_makes_the_employee_handler_when_they_start_on_a_message(): void
    {
        $message = Message::factory()->create();
        $staff = $this->staff();

        $response = $this->actingAs($staff)->patchJson("/api/koleksi/pesan/{$message->id}", ['status' => 'diproses', 'ditangani' => 'Orang lain']);

        $response->assertOk()->assertJsonPath('status', 'diproses')->assertJsonPath('ditangani', 'Petugas Pesan');
        $this->assertDatabaseHas('activities', ['module' => 'pesan', 'action' => 'memproses', 'target' => $message->tiket]);
    }

    public function test_adds_new_responses_by_the_employee_and_keeps_earlier_ones_unchanged(): void
    {
        $message = Message::factory()->create(['status' => 'diproses', 'ditangani' => 'Maria Bria']);
        $earlier = (new MessageResponse)->forceFill(['message_id' => $message->id, 'user_id' => User::factory()->create(['name' => 'Maria Bria'])->id, 'isi' => 'Tanggapan pertama.']);
        $earlier->save();
        $staff = $this->staff();

        $response = $this->actingAs($staff)->patchJson("/api/koleksi/pesan/{$message->id}", [
            'tanggapan' => [
                ['id' => $earlier->id, 'isi' => 'Diubah diam-diam.', 'oleh' => 'Penyusup'],
                ['id' => 'lokal-1', 'isi' => 'Sudah kami tindak lanjuti.', 'oleh' => 'Nama palsu', 'waktu' => '2020-01-01T00:00:00Z'],
            ],
            'status' => 'selesai',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'selesai')
            ->assertJsonPath('ditangani', 'Maria Bria')
            ->assertJsonPath('tanggapan.0.isi', 'Tanggapan pertama.')
            ->assertJsonPath('tanggapan.0.oleh', 'Maria Bria')
            ->assertJsonPath('tanggapan.1.isi', 'Sudah kami tindak lanjuti.')
            ->assertJsonPath('tanggapan.1.oleh', 'Petugas Pesan');
        $this->assertSame(2, $message->responses()->count());
        $this->assertDatabaseHas('activities', ['user_id' => $staff->id, 'module' => 'pesan', 'action' => 'menanggapi', 'target' => $message->tiket]);
    }

    public function test_never_changes_what_the_sender_wrote(): void
    {
        $message = Message::factory()->create(['nama' => 'Yohanes', 'isi' => 'Isi asli.']);

        $this->actingAs($this->staff())->patchJson("/api/koleksi/pesan/{$message->id}", ['nama' => 'Diubah', 'isi' => 'Isi diubah.', 'kontak' => 'x@y.z'])->assertOk();

        $this->assertSame(['Yohanes', 'Isi asli.'], [$message->fresh()->nama, $message->fresh()->isi]);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_rejects_a_response_with_control_characters(): void
    {
        $message = Message::factory()->create();

        $response = $this->actingAs($this->staff())->patchJson("/api/koleksi/pesan/{$message->id}", ['tanggapan' => [['id' => 'baru', 'isi' => "Halo\u{0007}"]]]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['tanggapan.0.isi' => 'Tanggapan berisi karakter kontrol yang tidak diizinkan.']);
        $this->assertSame(0, $message->responses()->count());
    }

    public function test_lets_only_administrators_delete_messages_with_their_responses(): void
    {
        $message = Message::factory()->create();
        (new MessageResponse)->forceFill(['message_id' => $message->id, 'user_id' => User::factory()->create()->id, 'isi' => 'Tanggapan.'])->save();
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($this->staff())->postJson('/api/koleksi/pesan/hapus', ['ids' => [$message->id]])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat menghapus pesan. Tandai selesai untuk menutupnya.']);
        $this->actingAs($administrator)->postJson('/api/koleksi/pesan/hapus', ['ids' => [$message->id]])->assertNoContent();

        $this->assertModelMissing($message);
        $this->assertDatabaseCount('message_responses', 0);
        $this->assertDatabaseHas('activities', ['user_id' => $administrator->id, 'action' => 'menghapus', 'target' => $message->tiket]);
    }

    private function staff(): User
    {
        return User::factory()->withModules(DashboardModule::Pesan)->create(['name' => 'Petugas Pesan']);
    }

    /**
     * @return array{jenis: string, nama: string, kontak: string, layanan: string, isi: string, sumber: string}
     */
    private function recorded(): array
    {
        return [
            'jenis' => 'masukan',
            'nama' => '',
            'kontak' => '',
            'layanan' => 'Pajak tahunan',
            'isi' => 'Kursi di ruang tunggu kurang saat jam ramai.',
            'sumber' => 'kotak-saran',
        ];
    }
}
