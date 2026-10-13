<?php

namespace Tests\Feature;

use App\Enums\DashboardModule;
use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FaqPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function moduleAbilities(): array
    {
        return [
            'list entries' => ['viewAny'],
            'read an entry' => ['view'],
            'add entries' => ['create'],
            'edit an entry' => ['update'],
            'reorder entries' => ['reorder'],
        ];
    }

    #[DataProvider('moduleAbilities')]
    public function test_allows_administrators(string $ability): void
    {
        $allowed = Gate::forUser(User::factory()->administrator()->create())->allows($ability, $this->argumentsFor($ability));

        $this->assertTrue($allowed);
    }

    #[DataProvider('moduleAbilities')]
    public function test_allows_employees_assigned_the_faq_module(string $ability): void
    {
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $allowed = Gate::forUser($employee)->allows($ability, $this->argumentsFor($ability));

        $this->assertTrue($allowed);
    }

    #[DataProvider('moduleAbilities')]
    public function test_denies_employees_without_the_faq_module(string $ability): void
    {
        $employee = User::factory()->withModules(DashboardModule::Berita)->create();

        $response = Gate::forUser($employee)->inspect($ability, $this->argumentsFor($ability));

        $this->assertTrue($response->denied());
        $this->assertSame('Modul Tanya Jawab belum ditugaskan kepada Anda.', $response->message());
    }

    public function test_lets_only_administrators_delete(): void
    {
        $administrator = User::factory()->administrator()->create();
        $employee = User::factory()->withModules(DashboardModule::Faq)->create();

        $response = Gate::forUser($employee)->inspect('deleteAny', Faq::class);

        $this->assertTrue(Gate::forUser($administrator)->allows('deleteAny', Faq::class));
        $this->assertTrue($response->denied());
        $this->assertSame('Hanya Administrator yang dapat menghapus tanya jawab. Matikan "Tampilkan di portal" untuk menyembunyikannya.', $response->message());
    }

    /**
     * @return class-string<Faq>|Faq
     */
    private function argumentsFor(string $ability): string|Faq
    {
        return in_array($ability, ['view', 'update'], true) ? Faq::factory()->create() : Faq::class;
    }
}
