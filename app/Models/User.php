<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\DashboardModule;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Role, status and modules are deliberately not mass assignable: they are
 * set explicitly by the code paths allowed to change them.
 */
#[Fillable(['nip', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    /**
     * The roles in the information workflow an employee may state in their
     * profile (D-027). They describe the employee; access still follows the
     * account's role and modules.
     *
     * @var list<string>
     */
    public const array INFORMATION_ROLES = ['Penyedia', 'Pemeriksa', 'Penyetuju', 'Pengelola publikasi'];

    /**
     * The profile is empty until the employee fills it in, as in the database.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'jabatan' => '',
        'unit' => '',
        'peran_informasi' => '',
    ];

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
            'modules' => 'array',
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
     * The dashboard modules assigned to this account, in the order of the
     * DashboardModule cases. Unknown stored values are ignored.
     *
     * @return list<string>
     */
    public function assignedModules(): array
    {
        return DashboardModule::ordered(array_filter((array) $this->modules, 'is_string'));
    }

    /**
     * The dashboard modules this account may manage: every module for an
     * administrator, the assigned ones for an employee.
     *
     * @return list<string>
     */
    public function managedModules(): array
    {
        return $this->isAdministrator()
            ? array_map(fn (DashboardModule $module): string => $module->value, DashboardModule::cases())
            : $this->assignedModules();
    }

    /**
     * Binds an activation link to the current password hash, so the link
     * stops working as soon as a password has been set through it.
     */
    public function activationKey(): string
    {
        return hash_hmac('sha256', (string) $this->getAuthPassword(), (string) config('app.key'));
    }

    /**
     * The field activities this officer is assigned to (D-034).
     *
     * @return BelongsToMany<FieldActivity, $this>
     */
    public function fieldActivities(): BelongsToMany
    {
        return $this->belongsToMany(FieldActivity::class);
    }
}
