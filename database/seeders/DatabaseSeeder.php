<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Outside local development nothing is
     * seeded: there is no default account in production.
     */
    public function run(): void
    {
        if (app()->environment('local')) {
            $this->call(LocalAccountSeeder::class);
        }
    }
}
