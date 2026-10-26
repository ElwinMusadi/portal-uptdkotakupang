<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Setting;
use App\Models\User;
use App\Support\AiSettings;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AiIntegrationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string ENV_KEY = 'sk-env-kunci-ABCD';

    private const string SAVED_KEY = 'sk-baru-kunci-WXYZ';

    private const array FORM = ['baseUrl' => 'https://ai.example.test/v1', 'model' => 'gemini/uji-1'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.base_url' => 'https://ai.example.test/v1',
            'services.ai.api_key' => self::ENV_KEY,
            'services.ai.model' => 'gemini/env-model',
            'services.ai.timeout' => 75,
        ]);
        Http::preventStrayRequests();
    }

    #[TestWith(['GET', '/api/pengaturan/ai'])]
    #[TestWith(['PUT', '/api/pengaturan/ai'])]
    #[TestWith(['POST', '/api/pengaturan/ai/uji'])]
    public function test_returns_401_to_guests(string $method, string $uri): void
    {
        $this->json($method, $uri, self::FORM)->assertUnauthorized();
    }

    #[TestWith(['GET', '/api/pengaturan/ai'])]
    #[TestWith(['PUT', '/api/pengaturan/ai'])]
    #[TestWith(['POST', '/api/pengaturan/ai/uji'])]
    public function test_forbids_employees_who_are_not_administrators_even_with_every_module(string $method, string $uri): void
    {
        $employee = User::factory()->withModules(...DashboardModule::cases())->create();

        $this->actingAs($employee)->json($method, $uri, self::FORM)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Hanya Administrator yang dapat mengatur Integrasi AI.']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_shows_the_env_values_until_saved_without_the_key(): void
    {
        $response = $this->actingAs($this->administrator())->getJson('/api/pengaturan/ai')
            ->assertOk()
            ->assertExactJson([
                'baseUrl' => 'https://ai.example.test/v1',
                'model' => 'gemini/env-model',
                'tersimpan' => false,
                'kunci' => ['ada' => true, 'akhir' => 'ABCD', 'sumber' => 'env'],
            ]);

        $this->assertStringNotContainsString(self::ENV_KEY, $response->getContent());
    }

    public function test_shows_that_there_is_no_key_at_all(): void
    {
        config(['services.ai.api_key' => null]);

        $this->actingAs($this->administrator())->getJson('/api/pengaturan/ai')
            ->assertOk()
            ->assertJsonPath('kunci', ['ada' => false, 'akhir' => null, 'sumber' => null]);
    }

    public function test_saves_the_key_encrypted_and_never_gives_it_back(): void
    {
        $response = $this->actingAs($this->administrator())->putJson('/api/pengaturan/ai', [...self::FORM, 'baseUrl' => 'https://ai.example.test/v1/', 'kunci' => self::SAVED_KEY])
            ->assertOk()
            ->assertExactJson([
                'baseUrl' => 'https://ai.example.test/v1',
                'model' => 'gemini/uji-1',
                'tersimpan' => true,
                'kunci' => ['ada' => true, 'akhir' => 'WXYZ', 'sumber' => 'pengaturan'],
            ]);

        $this->assertStringNotContainsString(self::SAVED_KEY, $response->getContent());
        $raw = (string) Setting::query()->toBase()->where('name', Setting::AI_INTEGRATION)->value('value');
        $this->assertStringNotContainsString(self::SAVED_KEY, $raw);
        $this->assertSame(self::SAVED_KEY, AiSettings::connection()->key);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_keeps_the_saved_key_when_the_field_is_left_empty_and_removes_it_on_request(): void
    {
        $administrator = $this->administrator();
        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...self::FORM, 'kunci' => self::SAVED_KEY])->assertOk();

        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...self::FORM, 'model' => 'gemini/uji-2', 'kunci' => ''])
            ->assertOk()
            ->assertJsonPath('model', 'gemini/uji-2')
            ->assertJsonPath('kunci.sumber', 'pengaturan');
        $this->assertSame(self::SAVED_KEY, AiSettings::connection()->key);

        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...self::FORM, 'hapusKunci' => true])
            ->assertOk()
            ->assertJsonPath('kunci', ['ada' => true, 'akhir' => 'ABCD', 'sumber' => 'env']);
        $this->assertSame(self::ENV_KEY, AiSettings::connection()->key);
        $this->assertNull(AiSettings::stored()['kunci']);
    }

    public function test_falls_back_to_the_env_key_when_the_saved_one_cannot_be_decrypted(): void
    {
        (new Setting)->forceFill(['name' => Setting::AI_INTEGRATION, 'value' => [...self::FORM, 'kunci' => 'bukan-hasil-enkripsi']])->save();

        $connection = AiSettings::connection();

        $this->assertSame(self::ENV_KEY, $connection->key);
        $this->assertSame('env', $connection->keySource);
        $this->assertSame('gemini/uji-1', $connection->model);
    }

    public function test_never_sends_a_key_in_use_to_another_host(): void
    {
        $administrator = $this->administrator();
        $elsewhere = [...self::FORM, 'baseUrl' => 'https://openrouter.example.test/api/v1'];

        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', $elsewhere)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['kunci' => 'Isi kunci API untuk alamat baru. Kunci yang tersimpan tidak dikirim ke alamat lain.']);
        $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', $elsewhere)
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['kunci']);

        // A key saved for one host stays there, also once the .env key is the one left
        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...$elsewhere, 'kunci' => self::SAVED_KEY])->assertOk();
        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...$elsewhere, 'hapusKunci' => true])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors(['kunci']);
        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...$elsewhere, 'baseUrl' => 'https://openrouter.example.test/v2'])->assertOk();
        $this->assertSame(self::SAVED_KEY, AiSettings::connection()->key);
        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', [...self::FORM, 'baseUrl' => 'https://ai.example.test/v2', 'hapusKunci' => true])->assertOk();
        $this->assertSame(self::ENV_KEY, AiSettings::connection()->key);
    }

    public function test_validates_the_settings(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', ['baseUrl' => 'http://ai.example.test/v1', 'model' => 'gemini uji', 'kunci' => 'sk kunci'])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'baseUrl' => 'Base URL harus alamat lengkap yang diawali https://.',
                'model' => 'Nama model hanya boleh berisi huruf, angka, dan tanda . _ - / : @ +.',
                'kunci' => 'Kunci API tidak boleh berisi spasi atau huruf di luar ASCII.',
            ]);
        $this->actingAs($administrator)->putJson('/api/pengaturan/ai', ['baseUrl' => 'https://'.str_repeat('a', 200).'.test', 'kunci' => str_repeat('k', 301), 'hapusKunci' => 'ya'])
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([
                'baseUrl' => 'Base URL maksimal 200 karakter.',
                'model' => 'Model wajib diisi.',
                'kunci' => 'Kunci API maksimal 300 karakter.',
                'hapusKunci' => 'Pilihan hapus kunci tidak valid.',
            ]);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_reports_a_working_connection_with_the_key_typed_in_the_form(): void
    {
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::response([
            'model' => 'gemini/uji-1',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'OK']]],
            'usage' => ['total_tokens' => 20],
        ])]);

        $this->actingAs($this->administrator())->postJson('/api/pengaturan/ai/uji', [...self::FORM, 'kunci' => self::SAVED_KEY])
            ->assertOk()
            ->assertJsonPath('terhubung', true)
            ->assertJsonPath('model', 'gemini/uji-1')
            ->assertJsonPath('penyedia', null)
            ->assertJson(fn ($json) => $json->where('pesan', fn (string $pesan): bool => str_starts_with($pesan, 'Terhubung. Model gemini/uji-1 menjawab dalam '))->etc());

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://ai.example.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer '.self::SAVED_KEY)
            && $request['model'] === 'gemini/uji-1'
            && $request['max_tokens'] === 16);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_tests_with_the_key_in_use_when_the_field_is_empty(): void
    {
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => null]]]])]);

        $this->actingAs($this->administrator())->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertOk()
            ->assertJsonPath('terhubung', true)
            ->assertJsonPath('model', 'gemini/uji-1');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer '.self::ENV_KEY));
    }

    #[TestWith([401])]
    #[TestWith([403])]
    public function test_reports_a_refused_key_with_the_providers_message_but_never_the_key(int $status): void
    {
        $key = 'Rahasia.Kunci+9876';
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::response([
            'error' => ['message' => "Incorrect API key provided: {$key}. Check sk-proj-1234567890."],
        ], $status)]);

        $response = $this->actingAs($this->administrator())->postJson('/api/pengaturan/ai/uji', [...self::FORM, 'kunci' => $key])
            ->assertOk()
            ->assertJsonPath('terhubung', false)
            ->assertJsonPath('pesan', 'Kunci API ditolak penyedia AI. Periksa kunci di Pengaturan › Integrasi AI.')
            ->assertJsonPath('penyedia', 'Incorrect API key provided: ***. Check ***.');

        $this->assertStringNotContainsString($key, $response->getContent());
    }

    public function test_uses_only_an_https_address_with_a_model_and_a_key(): void
    {
        config(['services.ai.base_url' => 'http://ai.example.test/v1']);
        $this->assertFalse(AiSettings::connection()->isConfigured());

        config(['services.ai.base_url' => 'https://ai.example.test/v1', 'services.ai.model' => '']);
        $this->assertFalse(AiSettings::connection()->isConfigured());

        config(['services.ai.model' => 'gemini/uji']);
        $this->assertTrue(AiSettings::connection()->isConfigured());
    }

    #[TestWith([404, 'Alamat atau model tidak dikenal penyedia AI. Periksa Base URL dan model di Pengaturan › Integrasi AI.'])]
    #[TestWith([429, 'Batas permintaan atau saldo penyedia AI habis. Coba lagi nanti.'])]
    #[TestWith([500, 'Penyedia AI menolak permintaan (HTTP 500). Coba lagi nanti.'])]
    public function test_explains_other_refusals(int $status, string $message): void
    {
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::response(['error' => 'galat'], $status)]);

        $this->actingAs($this->administrator())->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertOk()
            ->assertJsonPath('terhubung', false)
            ->assertJsonPath('pesan', $message)
            ->assertJsonPath('penyedia', 'galat');
    }

    public function test_reports_a_timeout_a_lost_connection_and_an_unreadable_answer(): void
    {
        $administrator = $this->administrator();
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::sequence()
            ->pushFailedConnection('cURL error 28: Operation timed out')
            ->pushFailedConnection('cURL error 6: Could not resolve host')
            ->push(['choices' => 'bukan daftar'])
            ->push(['choices' => [['message' => ['content' => ['bukan', 'teks']]]]]),
        ]);

        $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertJsonPath('pesan', 'Penyedia AI tidak menjawab dalam waktu yang ditentukan. Coba lagi, atau pakai sumber yang lebih ringkas.');
        $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertJsonPath('pesan', 'Penyedia AI tidak dapat dihubungi. Periksa Base URL di Pengaturan › Integrasi AI, atau coba lagi.');
        $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertJsonPath('pesan', 'Jawaban penyedia AI tidak dapat dibaca.');
        $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertJsonPath('terhubung', false)
            ->assertJsonPath('pesan', 'Jawaban penyedia AI tidak dapat dibaca.');
    }

    public function test_reports_that_nothing_is_configured_without_calling_anyone(): void
    {
        config(['services.ai.api_key' => '']);

        $this->actingAs($this->administrator())->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertOk()
            ->assertJsonPath('terhubung', false)
            ->assertJsonPath('pesan', 'Integrasi AI belum diatur. Minta Administrator mengisinya di Pengaturan › Integrasi AI.');

        Http::assertNothingSent();
    }

    public function test_limits_connection_tests_to_five_a_minute(): void
    {
        $administrator = $this->administrator();
        Http::fake(['https://ai.example.test/v1/chat/completions' => Http::response(['choices' => [['message' => ['content' => 'OK']]]])]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', self::FORM)->assertOk();
        }

        $this->actingAs($administrator)->postJson('/api/pengaturan/ai/uji', self::FORM)
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak uji koneksi. Coba lagi sebentar lagi.');
    }

    private function administrator(): User
    {
        return User::factory()->administrator()->create();
    }
}
