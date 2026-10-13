<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OwnAccountControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['GET'])]
    #[TestWith(['PUT'])]
    public function test_returns_401_to_guests(string $method): void
    {
        $this->json($method, '/api/pengaturan/akun', ['peran' => 'Penyedia'])->assertUnauthorized();
    }

    public function test_shows_the_account_name_and_email_with_an_empty_profile(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create(['name' => 'Maria Pegawai', 'email' => null]);

        $this->actingAs($employee)->getJson('/api/pengaturan/akun')
            ->assertOk()
            ->assertExactJson(['nama' => 'Maria Pegawai', 'jabatan' => '', 'unit' => '', 'peran' => '', 'email' => '']);
    }

    public function test_saves_the_own_profile_without_an_activity_entry(): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create(['name' => 'Maria Pegawai', 'email' => 'maria@example.go.id']);
        $other = User::factory()->create();

        $response = $this->actingAs($employee)->putJson('/api/pengaturan/akun', [
            'nama' => 'Maria Pegawai',
            'email' => 'maria@example.go.id',
            'jabatan' => 'Pengelola Data',
            'unit' => 'Sub Bagian Tata Usaha',
            'peran' => 'Pemeriksa',
        ]);

        $response->assertOk()->assertExactJson([
            'nama' => 'Maria Pegawai',
            'jabatan' => 'Pengelola Data',
            'unit' => 'Sub Bagian Tata Usaha',
            'peran' => 'Pemeriksa',
            'email' => 'maria@example.go.id',
        ]);
        $this->assertSame(['Pengelola Data', 'Sub Bagian Tata Usaha', 'Pemeriksa'], [$employee->fresh()->jabatan, $employee->fresh()->unit, $employee->fresh()->peran_informasi]);
        $this->assertSame('', $other->fresh()->jabatan);
        $this->assertDatabaseCount('activities', 0);

        $this->actingAs($employee)->putJson('/api/pengaturan/akun', ['jabatan' => null, 'unit' => '', 'peran' => 'Penyedia'])
            ->assertOk()
            ->assertJsonPath('jabatan', '')
            ->assertJsonPath('unit', '');
    }

    public function test_never_changes_the_account_name_or_email(): void
    {
        $employee = User::factory()->create(['name' => 'Maria Pegawai', 'email' => null]);

        $this->actingAs($employee)->putJson('/api/pengaturan/akun', ['nama' => 'Maria P.', 'email' => 'lain@example.go.id', 'peran' => 'Penyedia'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama' => 'Nama mengikuti akun dan hanya dapat diubah Administrator di Akun Pegawai.',
                'email' => 'Surel mengikuti akun dan hanya dapat diubah Administrator di Akun Pegawai.',
            ]);
        $this->actingAs($employee)->putJson('/api/pengaturan/akun', ['nama' => null, 'peran' => 'Penyedia'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nama']);
        $this->actingAs($employee)->putJson('/api/pengaturan/akun', ['nama' => 'Maria Pegawai', 'email' => '', 'peran' => 'Penyedia'])->assertOk();

        $this->assertSame('Maria Pegawai', $employee->fresh()->name);
        $this->assertNull($employee->fresh()->email);
    }

    public function test_validates_the_profile(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)->putJson('/api/pengaturan/akun', ['jabatan' => str_repeat('a', 121), 'unit' => "Unit\u{0007}"])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'jabatan' => 'Jabatan maksimal 120 karakter.',
                'unit' => 'Unit berisi karakter kontrol yang tidak diizinkan.',
                'peran' => 'Pilih peran dalam pengelolaan informasi.',
            ]);
        $this->actingAs($employee)->putJson('/api/pengaturan/akun', ['peran' => 'Administrator'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['peran' => 'Peran dalam pengelolaan informasi tidak dikenal.']);
        $this->assertSame('', $employee->fresh()->peran_informasi);
    }
}
