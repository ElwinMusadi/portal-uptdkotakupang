<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/kontak', $this->contact())->assertUnauthorized();
    }

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_forbids_employees_without_the_module_with_403(string $method): void
    {
        $employee = User::factory()->withModules(DashboardModule::Pesan)->create();

        $response = $this->actingAs($employee)->json($method, '/api/pengaturan/kontak', $this->contact());

        $response->assertForbidden()->assertExactJson(['message' => 'Modul Kontak & Kanal belum ditugaskan kepada Anda.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_an_empty_document_until_it_is_first_saved(): void
    {
        $response = $this->actingAs($this->kontakEmployee())->getJson('/api/pengaturan/kontak');

        $response->assertOk()->assertExactJson([
            'namaKantor' => '',
            'alamat' => '',
            'kota' => '',
            'provinsi' => '',
            'telepon' => '',
            'whatsapp' => '',
            'whatsappCatatan' => '',
            'email' => '',
            'mediaSosial' => [],
            'mapsUrl' => '',
            'catatanLokasi' => '',
            'janjiBalasan' => '',
            'kanalPengaduan' => [],
        ]);
    }

    public function test_saves_the_contact_document_and_logs_it(): void
    {
        $employee = $this->kontakEmployee();
        $contact = $this->contact();
        $contact['whatsappCatatan'] = null;
        $contact['mediaSosial'][0]['url'] = '';
        $contact['kanalPengaduan'][1]['deskripsi'] = null;

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/kontak', $contact);

        $response->assertOk()
            ->assertJsonPath('whatsappCatatan', '')
            ->assertJsonPath('mediaSosial.0.url', '')
            ->assertJsonPath('kanalPengaduan.1.deskripsi', '')
            ->assertJsonPath('kanalPengaduan.0', $this->contact()['kanalPengaduan'][0]);
        $this->assertSame($response->json(), Setting::contact());
        $this->assertDatabaseHas('activities', [
            'user_id' => $employee->id,
            'module' => 'kontak',
            'action' => 'memperbarui',
            'target' => 'Kontak & Kanal',
        ]);
    }

    public function test_logs_nothing_when_the_same_document_is_saved_again(): void
    {
        $employee = $this->kontakEmployee();
        $this->actingAs($employee)->putJson('/api/pengaturan/kontak', $this->contact())->assertOk();

        $this->actingAs($employee)->putJson('/api/pengaturan/kontak', $this->contact())->assertOk();

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_accepts_full_addresses_portal_pages_anchors_mail_and_phone_links(): void
    {
        $contact = $this->contact();
        $contact['kanalPengaduan'][0]['tautanUrl'] = 'kontak.html#masukan';
        $contact['kanalPengaduan'][1]['tautanUrl'] = '#pengaduan';
        $contact['kanalPengaduan'][2]['tautanUrl'] = 'mailto:pengaduan@example.go.id';
        $contact['mediaSosial'][0]['url'] = 'tel:+62380821234';

        $this->actingAs($this->kontakEmployee())->putJson('/api/pengaturan/kontak', $contact)->assertOk();
    }

    public function test_validates_the_office_numbers_email_and_links(): void
    {
        $contact = $this->contact();
        $contact['namaKantor'] = '';
        $contact['telepon'] = '0380-8';
        $contact['whatsapp'] = '+62 812 abc 7890';
        $contact['email'] = 'uptd.kotakupang';
        $contact['mapsUrl'] = 'https://maps.google.com/?q=Samsat Kota Kupang';
        $contact['mediaSosial'][0]['url'] = 'javascript:alert(1)';
        $contact['kanalPengaduan'][0]['nama'] = '';
        $contact['kanalPengaduan'][1]['tautanUrl'] = 'jadwal.html/../admin';
        $contact['kanalPengaduan'][2]['deskripsi'] = "Penjelasan\u{0007}";

        $response = $this->actingAs($this->kontakEmployee())->putJson('/api/pengaturan/kontak', $contact);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'namaKantor' => 'Nama kantor wajib diisi.',
            'telepon' => 'Nomor telepon berisi minimal 6 angka, boleh dengan spasi, tanda kurung, titik, tanda hubung, dan + di depan.',
            'whatsapp' => 'Nomor WhatsApp berisi minimal 9 angka, boleh dengan spasi, tanda kurung, titik, tanda hubung, dan + di depan.',
            'email' => 'Format email belum benar.',
            'mapsUrl' => 'Tautan Google Maps: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            'mediaSosial.0.url' => 'Tautan akun: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            'kanalPengaduan.0.nama' => 'Nama kanal wajib diisi.',
            'kanalPengaduan.1.tautanUrl' => 'Alamat tautan: gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html).',
            'kanalPengaduan.2.deskripsi' => 'Penjelasan berisi karakter kontrol yang tidak diizinkan.',
        ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_limits_the_lists_and_rejects_duplicate_items(): void
    {
        $contact = $this->contact();
        $contact['kanalPengaduan'] = array_map(
            fn (int $index): array => ['id' => "k{$index}", 'nama' => "Kanal {$index}", 'deskripsi' => '', 'tautanLabel' => '', 'tautanUrl' => ''],
            range(1, 9),
        );
        $contact['mediaSosial'][] = $contact['mediaSosial'][0];

        $response = $this->actingAs($this->kontakEmployee())->putJson('/api/pengaturan/kontak', $contact);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'kanalPengaduan' => 'Maksimal 8 kanal pengaduan.',
            'mediaSosial.1.id' => 'Akun media sosial tercantum dua kali.',
        ]);
    }

    private function kontakEmployee(): User
    {
        return User::factory()->withModules(DashboardModule::Kontak)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function contact(): array
    {
        return [
            'namaKantor' => 'Kantor Bersama Samsat Kota Kupang',
            'alamat' => 'Jl. Soeharto No. 1',
            'kota' => 'Kota Kupang',
            'provinsi' => 'Nusa Tenggara Timur',
            'telepon' => '(0380) 821 234',
            'whatsapp' => '+62 812-3456-7890',
            'whatsappCatatan' => 'Hanya pesan teks, hari kerja',
            'email' => 'uptd.kotakupang@example.go.id',
            'mediaSosial' => [['id' => 'm1', 'platform' => 'Instagram', 'akun' => '@samsatkotakupang', 'url' => 'https://instagram.com/samsatkotakupang']],
            'mapsUrl' => 'https://maps.google.com/?q=Samsat+Kota+Kupang',
            'catatanLokasi' => 'Parkir tersedia di halaman kantor',
            'janjiBalasan' => 'Kami membalas paling lambat tiga hari kerja.',
            'kanalPengaduan' => [
                ['id' => 'k1', 'nama' => 'SP4N-LAPOR!', 'deskripsi' => 'Sistem pengaduan pelayanan publik nasional.', 'tautanLabel' => 'Buka lapor.go.id', 'tautanUrl' => 'https://www.lapor.go.id'],
                ['id' => 'k2', 'nama' => 'Kotak saran di kantor', 'deskripsi' => 'Tersedia di ruang tunggu.', 'tautanLabel' => 'Lihat jam kantor', 'tautanUrl' => 'jadwal.html'],
                ['id' => 'k3', 'nama' => 'Ombudsman RI Perwakilan NTT', 'deskripsi' => 'Bila pengaduan tidak ditanggapi.', 'tautanLabel' => 'Buka ombudsman.go.id', 'tautanUrl' => 'https://ombudsman.go.id'],
            ],
        ];
    }
}
