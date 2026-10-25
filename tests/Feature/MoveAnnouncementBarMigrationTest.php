<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MoveAnnouncementBarMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A site document saved before D-082, with its announcement bar.
     *
     * @var array<string, mixed>
     */
    private const array SAVED_SITE = [
        ...Setting::INITIAL_SITE,
        'bilahPengumuman' => ['aktif' => true, 'teks' => 'Loket tutup Senin.', 'tautanLabel' => 'Lihat', 'tautanUrl' => 'https://uptdpenda-kupang.web.id/berita/loket-tutup'],
    ];

    public function test_moves_a_saved_bar_into_its_own_document_with_its_time_and_keeps_the_site_s_time(): void
    {
        $this->saveSite();

        $this->migration()->up();

        $bar = DB::table('settings')->where('name', 'berita')->sole();
        $this->assertSame(self::SAVED_SITE['bilahPengumuman'], json_decode($bar->value, true));
        $this->assertSame('2026-10-13 23:13:33', (string) $bar->created_at);
        $this->assertSame('2026-10-13 23:13:33', (string) $bar->updated_at);
        $this->assertSame(self::SAVED_SITE['bilahPengumuman'], Setting::query()->find(Setting::ANNOUNCEMENT_BAR)?->value);
        $this->assertSame(Setting::INITIAL_SITE, Setting::site());
        $this->assertSame('2026-10-13 23:13:33', (string) DB::table('settings')->where('name', 'situs')->value('updated_at'));
        $this->getJson('/api/public/pengumuman')->assertOk()->assertJsonPath('teks', 'Loket tutup Senin.')->assertJsonPath('diperbarui', '2026-10-13T23:13:33+00:00');
    }

    public function test_keeps_a_bar_document_already_there_and_still_takes_the_bar_out_of_the_site(): void
    {
        $this->saveSite();
        $kept = ['aktif' => false, 'teks' => '', 'tautanLabel' => '', 'tautanUrl' => ''];
        DB::table('settings')->insert(['name' => 'berita', 'value' => json_encode($kept), 'created_at' => now(), 'updated_at' => now()]);

        $this->migration()->up();

        $this->assertSame($kept, Setting::query()->find(Setting::ANNOUNCEMENT_BAR)?->value);
        $this->assertArrayNotHasKey('bilahPengumuman', Setting::site());
    }

    public function test_leaves_an_unsaved_site_and_an_already_moved_bar_alone(): void
    {
        $this->migration()->up();
        $this->assertDatabaseCount('settings', 0);

        DB::table('settings')->insert(['name' => 'situs', 'value' => json_encode(Setting::INITIAL_SITE), 'created_at' => now(), 'updated_at' => now()]);
        $this->migration()->up();
        $this->assertDatabaseCount('settings', 1);
    }

    public function test_puts_the_bar_back_into_the_site_when_rolled_back(): void
    {
        $this->saveSite();
        $migration = $this->migration();
        $migration->up();

        $migration->down();

        $this->assertDatabaseMissing('settings', ['name' => 'berita']);
        $this->assertSame(self::SAVED_SITE, json_decode(DB::table('settings')->where('name', 'situs')->value('value'), true));
    }

    private function saveSite(): void
    {
        DB::table('settings')->insert([
            'name' => 'situs', 'value' => json_encode(self::SAVED_SITE), 'created_at' => '2026-10-13 23:00:00', 'updated_at' => '2026-10-13 23:13:33',
        ]);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_25_124358_move_announcement_bar_to_its_own_setting.php');
    }
}
