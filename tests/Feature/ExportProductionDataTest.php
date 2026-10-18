<?php

namespace Tests\Feature;

use App\Actions\DumpDatabase;
use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\LocalAccountSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ExportProductionDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string NIP = '199707162025061002';

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'local';
        $this->path = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'pintu-ekspor-'.Str::random(8).'.sql';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_makes_the_local_administrator_the_pending_production_one_and_drops_transient_rows(): void
    {
        $local = User::factory()->administrator()->create(['nip' => LocalAccountSeeder::NIP, 'password' => LocalAccountSeeder::PASSWORD]);
        $other = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'sesi', 'user_id' => $local->id, 'payload' => '', 'last_activity' => 1]);
        DB::table('cache')->insert(['key' => 'kunci', 'value' => 'nilai', 'expiration' => 1]);
        DB::table('cache_locks')->insert(['key' => 'kunci', 'owner' => 'pemilik', 'expiration' => 1]);
        DB::table('password_reset_tokens')->insert(['email' => 'a@example.com', 'token' => 'token']);
        $this->dumpWrites("-- dump\n");

        $this->artisan('pintu:export-production', ['berkas' => $this->path, '--nip' => self::NIP, '--nama' => "Elwin O'Bessiesura"])
            ->expectsOutputToContain('php artisan pintu:activation-link '.self::NIP)
            ->assertSuccessful();

        $sql = (string) file_get_contents($this->path);
        $this->assertStringStartsWith("-- dump\n", $sql);
        DB::unprepared(Str::after($sql, "-- dump\n"));
        $local->refresh();
        $this->assertSame([self::NIP, "Elwin O'Bessiesura", AccountStatus::Pending], [$local->nip, $local->name, $local->status]);
        $this->assertFalse(Hash::check(LocalAccountSeeder::PASSWORD, $local->password));
        $this->assertSame([$other->nip, $other->password], [$other->fresh()->nip, $other->fresh()->password]);
        $this->assertSame([0, 0, 0, 0], [DB::table('sessions')->count(), DB::table('cache')->count(), DB::table('cache_locks')->count(), DB::table('password_reset_tokens')->count()]);
    }

    public function test_lets_the_production_administrator_keep_the_local_nip(): void
    {
        $local = User::factory()->administrator()->create(['nip' => LocalAccountSeeder::NIP]);
        $this->dumpWrites("-- dump\n");

        $this->artisan('pintu:export-production', ['berkas' => $this->path, '--nip' => LocalAccountSeeder::NIP, '--nama' => 'Elwin Bessiesura'])
            ->assertSuccessful();

        DB::unprepared(Str::after((string) file_get_contents($this->path), "-- dump\n"));
        $this->assertSame([LocalAccountSeeder::NIP, 'Elwin Bessiesura', AccountStatus::Pending], [$local->fresh()->nip, $local->fresh()->name, $local->fresh()->status]);
    }

    public function test_runs_only_on_the_local_copy(): void
    {
        $this->app['env'] = 'production';
        $this->mock(DumpDatabase::class, fn (MockInterface $mock) => $mock->shouldNotReceive('handle'));

        $this->artisan('pintu:export-production', ['berkas' => $this->path, '--nip' => self::NIP, '--nama' => 'Elwin Bessiesura'])
            ->expectsOutputToContain('Ekspor ini hanya dijalankan dari salinan lokal (APP_ENV=local).')
            ->assertFailed();
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidOptions(): array
    {
        return [
            'no NIP' => [['--nama' => 'Elwin Bessiesura'], 'Sebutkan NIP Administrator produksi dengan --nip.'],
            'short NIP' => [['--nip' => '19970716202506100', '--nama' => 'Elwin Bessiesura'], 'NIP harus 18 digit angka.'],
            'no name' => [['--nip' => self::NIP], 'Sebutkan nama Administrator produksi dengan --nama.'],
        ];
    }

    /**
     * @param  array<string, string>  $options
     */
    #[DataProvider('invalidOptions')]
    public function test_requires_the_production_administrators_nip_and_name(array $options, string $message): void
    {
        User::factory()->administrator()->create(['nip' => LocalAccountSeeder::NIP]);
        $this->mock(DumpDatabase::class, fn (MockInterface $mock) => $mock->shouldNotReceive('handle'));

        $this->artisan('pintu:export-production', ['berkas' => $this->path, ...$options])
            ->expectsOutputToContain($message)
            ->assertFailed();
    }

    public function test_refuses_a_file_inside_the_project_an_existing_one_or_another_extension(): void
    {
        User::factory()->administrator()->create(['nip' => LocalAccountSeeder::NIP]);
        $this->mock(DumpDatabase::class, fn (MockInterface $mock) => $mock->shouldNotReceive('handle'));
        file_put_contents($this->path, 'lama');

        foreach ([
            base_path('pintu-produksi.sql') => 'Simpan berkas di luar folder proyek agar data pribadi tidak ikut ter-commit.',
            $this->path => 'Berkas tujuan sudah ada; pilih nama lain.',
            Str::replaceLast('.sql', '.txt', $this->path) => 'Nama berkas harus berakhiran .sql.',
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'tidak-ada-'.Str::random(8).DIRECTORY_SEPARATOR.'pintu.sql' => 'Folder tujuan tidak ada.',
        ] as $path => $message) {
            $this->artisan('pintu:export-production', ['berkas' => $path, '--nip' => self::NIP, '--nama' => 'Elwin Bessiesura'])
                ->expectsOutputToContain($message)
                ->assertFailed();
        }

        $this->assertSame('lama', file_get_contents($this->path));
        $this->assertFileDoesNotExist(base_path('pintu-produksi.sql'));
    }

    public function test_needs_the_local_administrator_and_a_nip_no_other_account_uses(): void
    {
        $this->mock(DumpDatabase::class, fn (MockInterface $mock) => $mock->shouldNotReceive('handle'));

        $this->artisan('pintu:export-production', ['berkas' => $this->path, '--nip' => self::NIP, '--nama' => 'Elwin Bessiesura'])
            ->expectsOutputToContain('Akun admin lokal dari LocalAccountSeeder tidak ditemukan.')
            ->assertFailed();

        User::factory()->administrator()->create(['nip' => LocalAccountSeeder::NIP]);
        User::factory()->create(['nip' => self::NIP]);

        $this->artisan('pintu:export-production', ['berkas' => $this->path, '--nip' => self::NIP, '--nama' => 'Elwin Bessiesura'])
            ->expectsOutputToContain('NIP ini sudah dipakai akun lain.')
            ->assertFailed();
    }

    public function test_reports_a_failed_dump_without_writing_the_closing_statements(): void
    {
        User::factory()->administrator()->create(['nip' => LocalAccountSeeder::NIP]);
        $this->mock(DumpDatabase::class, fn (MockInterface $mock) => $mock->shouldReceive('handle')->once()->andThrow(new RuntimeException('mysqldump gagal: Access denied')));

        $this->artisan('pintu:export-production', ['berkas' => $this->path, '--nip' => self::NIP, '--nama' => 'Elwin Bessiesura'])
            ->expectsOutputToContain('mysqldump gagal: Access denied')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->path);
    }

    private function dumpWrites(string $contents): void
    {
        $this->mock(DumpDatabase::class, fn (MockInterface $mock) => $mock->shouldReceive('handle')->once()
            ->with('sqlite', $this->path, 'mysqldump')
            ->andReturnUsing(fn () => file_put_contents($this->path, $contents)));
    }
}
