<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * A known administrator account for local development only. Production
 * administrators are created with `php artisan pintu:create-admin`.
 */
class LocalAccountSeeder extends Seeder
{
    public const string NIP = '199707162026061002';

    public const string PASSWORD = 'pintu-lokal-2026';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LocalAccountSeeder hanya untuk pengembangan lokal.');
        }

        User::firstOrNew(['nip' => self::NIP])->forceFill([
            'name' => 'Admin PINTU (lokal)',
            'password' => self::PASSWORD,
            'role' => UserRole::Administrator,
            'status' => AccountStatus::Active,
        ])->save();
    }
}
