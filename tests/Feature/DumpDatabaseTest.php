<?php

namespace Tests\Feature;

use App\Actions\DumpDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DumpDatabaseTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pintu-uji-'.Str::random(8).'.sql';
        config(['database.connections.sumber' => [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => '3306', 'database' => 'pintu', 'username' => 'pengguna', 'password' => 'sandi #uji"\\',
        ]]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_dumps_with_the_credentials_in_an_option_file_it_deletes_afterwards(): void
    {
        $seen = [];
        Process::fake(function (PendingProcess $process) use (&$seen) {
            $seen['command'] = $process->command;
            $seen['options'] = Str::after($process->command[1], '--defaults-extra-file=');
            $seen['contents'] = file_get_contents($seen['options']);

            return Process::result();
        });

        app(DumpDatabase::class)->handle('sumber', $this->path, 'mysqldump');

        $this->assertSame(['mysqldump', "--defaults-extra-file={$seen['options']}", '--single-transaction', '--set-gtid-purged=OFF', '--no-tablespaces',
            '--default-character-set=utf8mb4', '--hex-blob', "--result-file={$this->path}", 'pintu'], $seen['command']);
        $this->assertSame("[client]\nhost=\"127.0.0.1\"\nport=\"3306\"\nuser=\"pengguna\"\npassword='sandi #uji\"\\\\'\n", $seen['contents']);
        $this->assertFileDoesNotExist($seen['options']);
    }

    public function test_removes_the_partial_dump_and_reports_why_mysqldump_failed(): void
    {
        $options = null;
        Process::fake(function (PendingProcess $process) use (&$options) {
            $options = Str::after($process->command[1], '--defaults-extra-file=');
            file_put_contents($this->path, '-- sebagian');

            return Process::result(errorOutput: "Access denied\n", exitCode: 2);
        });

        try {
            app(DumpDatabase::class)->handle('sumber', $this->path);
            $this->fail('mysqldump yang gagal harus dilaporkan.');
        } catch (RuntimeException $exception) {
            $this->assertSame('mysqldump gagal: Access denied', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->path);
        $this->assertFileDoesNotExist($options);
    }

    public function test_refuses_a_connection_that_is_not_mysql(): void
    {
        Process::fake();

        $this->expectExceptionObject(new RuntimeException('Koneksi database sqlite bukan MySQL.'));

        try {
            app(DumpDatabase::class)->handle('sqlite', $this->path);
        } finally {
            Process::assertNothingRan();
        }
    }

    public function test_refuses_credentials_holding_both_kinds_of_quote_without_leaving_the_option_file(): void
    {
        config(['database.connections.sumber.password' => 'a\'b"c']);
        Process::fake();
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'pintu-dump-*');

        try {
            app(DumpDatabase::class)->handle('sumber', $this->path);
            $this->fail('Kata sandi dengan dua jenis kutip harus ditolak.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Kredensial database berisi tanda kutip tunggal dan ganda; jalankan mysqldump sendiri.', $exception->getMessage());
        }

        Process::assertNothingRan();
        $this->assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'pintu-dump-*'));
    }
}
