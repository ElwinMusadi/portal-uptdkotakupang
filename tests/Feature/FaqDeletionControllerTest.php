<?php

namespace Tests\Feature;

use App\Actions\RecordActivity;
use App\Enums\DashboardModule;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

class FaqDeletionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_to_guests(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->postJson('/api/koleksi/faq/hapus', ['ids' => [$faq->id]]);

        $response->assertUnauthorized();
        $this->assertModelExists($faq);
    }

    public function test_forbids_employees_of_the_module_with_403(): void
    {
        $faq = Faq::factory()->create();
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $response = $this->actingAs($employee)->postJson('/api/koleksi/faq/hapus', ['ids' => [$faq->id]]);

        $response->assertForbidden()->assertExactJson([
            'message' => 'Hanya Administrator yang dapat menghapus tanya jawab. Matikan "Tampilkan di portal" untuk menyembunyikannya.',
        ]);
        $this->assertModelExists($faq);
    }

    public function test_lets_an_administrator_delete_entries_with_204(): void
    {
        [$first, $second, $kept] = Faq::factory()->count(3)->create()->all();

        $response = $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', ['ids' => [$first->id, $second->id]]);

        $response->assertNoContent();
        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
        $this->assertModelExists($kept);
    }

    public function test_skips_entries_that_no_longer_exist(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', [
            'ids' => [$faq->id, '01kaaaaaaaaaaaaaaaaaaaaaaa'],
        ]);

        $response->assertNoContent();
        $this->assertModelMissing($faq);
    }

    public function test_logs_one_deletion_with_its_question(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => 'Apakah pajak tahunan bisa diwakilkan?']);
        $administrator = $this->administrator();

        $this->actingAs($administrator)->postJson('/api/koleksi/faq/hapus', ['ids' => [$faq->id]]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $administrator->id,
            'module' => 'faq',
            'action' => 'menghapus',
            'target' => 'Apakah pajak tahunan bisa diwakilkan?',
        ]);
    }

    public function test_logs_several_deletions_as_a_count(): void
    {
        $ids = Faq::factory()->count(3)->create()->modelKeys();

        $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', ['ids' => $ids]);

        $this->assertDatabaseHas('activities', ['module' => 'faq', 'action' => 'menghapus', 'target' => '3 entri']);
    }

    public function test_keeps_the_entries_when_the_deletion_could_not_be_logged(): void
    {
        Exceptions::fake();
        $faq = Faq::factory()->create();
        $this->mock(RecordActivity::class)->shouldReceive('handle')->andThrow(new RuntimeException('Log aktivitas tidak dapat ditulis.'));

        $response = $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', ['ids' => [$faq->id]]);

        $response->assertInternalServerError();
        $this->assertModelExists($faq);
    }

    public function test_logs_nothing_when_nothing_was_deleted(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', ['ids' => ['01kaaaaaaaaaaaaaaaaaaaaaaa']]);

        $response->assertNoContent();
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_requires_the_entries_to_delete(): void
    {
        $response = $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['ids' => 'Pilih tanya jawab yang akan dihapus.']);
    }

    public function test_rejects_an_identifier_that_is_not_an_entry_identifier(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->actingAs($this->administrator())->postJson('/api/koleksi/faq/hapus', ['ids' => [$faq->id, '1 OR 1=1']]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['ids.1' => 'Daftar tanya jawab tidak valid.']);
        $this->assertModelExists($faq);
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }
}
