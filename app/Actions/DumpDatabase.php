<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Writes a mysqldump of a MySQL connection to a file, to move the local data
 * to Laravel Cloud (D-065). The credentials go into a temporary option file
 * rather than the command line, and that file is deleted again whatever
 * happens. The dump keeps every table and its rows in one consistent
 * snapshot, without GTIDs or tablespaces, which Laravel MySQL refuses.
 */
class DumpDatabase
{
    /**
     * @throws RuntimeException when the connection is not MySQL or mysqldump fails
     */
    public function handle(string $connection, string $path, string $mysqldump = 'mysqldump'): void
    {
        $config = config("database.connections.{$connection}");
        if (! is_array($config) || ! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException("Koneksi database {$connection} bukan MySQL.");
        }

        $options = tempnam(sys_get_temp_dir(), 'pintu-dump-');
        try {
            chmod($options, 0600);
            file_put_contents($options, implode("\n", [
                '[client]',
                'host='.$this->optionValue((string) $config['host']),
                'port='.$this->optionValue((string) $config['port']),
                'user='.$this->optionValue((string) $config['username']),
                'password='.$this->optionValue((string) $config['password']),
                '',
            ]));

            $result = Process::forever()->run([
                $mysqldump,
                "--defaults-extra-file={$options}",
                '--single-transaction',
                '--set-gtid-purged=OFF',
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
                '--hex-blob',
                "--result-file={$path}",
                (string) $config['database'],
            ]);
        } finally {
            if (is_file($options)) {
                unlink($options);
            }
        }

        if ($result->failed()) {
            if (is_file($path)) {
                unlink($path);
            }

            throw new RuntimeException('mysqldump gagal: '.trim($result->errorOutput() ?: $result->output()));
        }
    }

    /**
     * A value for the option file, quoted so that "#" and spaces survive;
     * backslashes are escape characters there.
     *
     * @throws RuntimeException when the value holds both kinds of quote
     */
    private function optionValue(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);

        return match (true) {
            ! str_contains($value, '"') => '"'.$value.'"',
            ! str_contains($value, "'") => "'".$value."'",
            default => throw new RuntimeException('Kredensial database berisi tanda kutip tunggal dan ganda; jalankan mysqldump sendiri.'),
        };
    }
}
