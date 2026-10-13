<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function abilitiesOnAnotherAccount(): array
    {
        return [
            'list accounts' => ['viewAny'],
            'create accounts' => ['create'],
            'edit an account' => ['update'],
            'change an account status' => ['changeStatus'],
            'issue an activation link' => ['issueActivationLink'],
        ];
    }

    #[DataProvider('abilitiesOnAnotherAccount')]
    public function test_allows_administrators_every_ability_on_other_accounts(string $ability): void
    {
        $administrator = User::factory()->administrator()->create();

        $allowed = Gate::forUser($administrator)->allows($ability, $this->argumentsFor($ability, User::factory()->create()));

        $this->assertTrue($allowed);
    }

    #[DataProvider('abilitiesOnAnotherAccount')]
    public function test_denies_employees_every_ability(string $ability): void
    {
        $employee = User::factory()->create();

        $response = Gate::forUser($employee)->inspect($ability, $this->argumentsFor($ability, User::factory()->create()));

        $this->assertTrue($response->denied());
        $this->assertSame('Hanya Administrator yang dapat mengelola akun.', $response->message());
    }

    #[TestWith(['changeStatus', 'Anda tidak dapat mengubah status akun Anda sendiri.'])]
    #[TestWith(['issueActivationLink', 'Anda tidak dapat mengatur ulang akun Anda sendiri.'])]
    public function test_denies_administrators_status_changes_and_resets_of_their_own_account(string $ability, string $message): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = Gate::forUser($administrator)->inspect($ability, $administrator);

        $this->assertTrue($response->denied());
        $this->assertSame($message, $response->message());
    }

    public function test_denies_employees_with_the_administrator_message_even_on_their_own_account(): void
    {
        $employee = User::factory()->create();

        $response = Gate::forUser($employee)->inspect('changeStatus', $employee);

        $this->assertSame('Hanya Administrator yang dapat mengelola akun.', $response->message());
    }

    public function test_allows_administrators_to_edit_their_own_account(): void
    {
        $administrator = User::factory()->administrator()->create();

        $allowed = Gate::forUser($administrator)->allows('update', $administrator);

        $this->assertTrue($allowed);
    }

    /**
     * @return class-string<User>|User
     */
    private function argumentsFor(string $ability, User $account): string|User
    {
        return in_array($ability, ['viewAny', 'create'], true) ? User::class : $account;
    }
}
