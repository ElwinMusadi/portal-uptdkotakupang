<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceSituationsMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_gives_services_added_before_it_no_situations_and_the_initial_ones_theirs(): void
    {
        $this->assertDatabaseCount('services', 8);
        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_10_17_084541_add_situations_to_services_table.php');
        $migration->down();
        DB::table('services')->insert([
            'id' => (string) Str::ulid(), 'nama' => 'Layanan uji', 'slug' => 'layanan-uji', 'ringkasan' => '', 'loket' => 'Loket 3',
            'jenis' => 'layanan', 'cek_fisik' => true, 'non_tunai' => false, 'diwakilkan' => true, 'label' => '[]', 'persyaratan' => '[]',
            'catatan' => '', 'unggulan' => false, 'urutan' => 6, 'status' => 'draf', 'penyedia' => 'Seksi Verifikasi', 'pemeriksa' => '',
            'catatan_revisi' => '', 'riwayat' => '[]', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertSame([], Service::query()->where('slug', 'layanan-uji')->sole()->situasi);
        $this->assertSame(['STNK hilang', 'STNK rusak atau tidak terbaca'], Service::query()->where('slug', 'duplikat')->sole()->situasi);
    }
}
