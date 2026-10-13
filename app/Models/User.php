<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Role and status are deliberately not mass assignable: they are set
 * explicitly by the code paths allowed to change them.
 */
#[Fillable(['nip', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => AccountStatus::class,
        ];
    }

    /**
     * Whether this account may sign in and keep using an existing session.
     * The stored status is compared exactly, so an unexpected value fails
     * closed instead of throwing or matching loosely under a collation.
     */
    public function canSignIn(): bool
    {
        return ($this->getAttributes()['status'] ?? null) === AccountStatus::Active->value;
    }

    /**
     * Whether this account may manage other accounts. Like canSignIn(), the
     * stored role is compared exactly.
     */
    public function isAdministrator(): bool
    {
        return ($this->getAttributes()['role'] ?? null) === UserRole::Administrator->value;
    }

    /**
     * Binds an activation link to the current password hash, so the link
     * stops working as soon as a password has been set through it.
     */
    public function activationKey(): string
    {
        return hash_hmac('sha256', (string) $this->getAuthPassword(), (string) config('app.key'));
    }
}
