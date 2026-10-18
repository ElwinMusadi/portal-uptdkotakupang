<?php

namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites the upload addresses saved in content after the data moved to
 * another host (D-065). Content keeps each file's full address, and
 * Upload::fromUrl only accepts the upload's current one, so addresses of the
 * old application (its public disk under /storage/ and its private file
 * route) become each upload's address on this host and its buckets. JSON
 * saved as text writes slashes escaped ("\/"), so that form is replaced too.
 * Imported data and framework tables never hold such addresses and are not
 * searched.
 */
#[Signature('pintu:rewrite-upload-urls {lama : Alamat aplikasi lama (APP_URL salinan lokal), mis. http://portal-uptdkotakupang.test} {--dry-run : Hanya menghitung, tanpa mengubah data}')]
#[Description('Mengganti alamat unggahan lama yang tersimpan di konten dengan alamat saat ini')]
class RewriteUploadUrls extends Command
{
    /**
     * Tables that never hold upload addresses.
     *
     * @var list<string>
     */
    private const array SKIPPED_TABLES = ['cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations', 'password_reset_tokens', 'potential_summaries',
        'potential_vehicles', 'revenue_transactions', 'sessions', 'uploads'];

    /**
     * Column types that can hold an address.
     *
     * @var list<string>
     */
    private const array TEXT_TYPES = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'json'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $old = rtrim((string) $this->argument('lama'), '/');
        $host = parse_url($old, PHP_URL_HOST);
        if (! is_string($host) || ! in_array(parse_url($old, PHP_URL_SCHEME), ['http', 'https'], true) || parse_url($old, PHP_URL_PATH) !== null) {
            $this->components->error('Alamat lama harus berupa alamat aplikasi tanpa jalur, mis. http://portal-uptdkotakupang.test.');

            return self::FAILURE;
        }

        $replacements = $this->replacements($old);
        $rows = 0;
        foreach ($replacements === [] ? [] : Schema::getTableListing(Schema::getCurrentSchemaName(), false) as $table) {
            $key = collect(Schema::getIndexes($table))->firstWhere('primary', true)['columns'] ?? [];
            if (in_array($table, self::SKIPPED_TABLES, true) || count($key) !== 1) {
                continue;
            }

            foreach (Schema::getColumns($table) as $column) {
                if (! in_array(strtolower($column['type_name']), self::TEXT_TYPES, true)) {
                    continue;
                }

                $changed = $this->rewrite($table, $key[0], $column['name'], $host, $replacements);
                if ($changed > 0) {
                    $this->components->twoColumnDetail("{$table}.{$column['name']}", "{$changed} baris");
                    $rows += $changed;
                }
            }
        }

        $this->components->info($this->option('dry-run')
            ? "Alamat lama ditemukan di {$rows} baris; tidak ada yang diubah."
            : "Alamat diganti di {$rows} baris.");

        $missing = Upload::query()->where('publik', true)->get()->reject(fn (Upload $upload): bool => $upload->disk()->exists($upload->path))->count();
        if ($missing > 0) {
            $this->components->warn("{$missing} berkas publik belum ada di bucket; salin dengan php artisan pintu:copy-uploads dari salinan lokal.");
        }

        return self::SUCCESS;
    }

    /**
     * Each upload's old address and its current one, also with escaped
     * slashes, for strtr().
     *
     * @return array<string, string>
     */
    private function replacements(string $old): array
    {
        $replacements = [];
        foreach (Upload::query()->get() as $upload) {
            $current = $upload->url();
            $previous = $upload->publik
                ? "{$old}/storage/{$upload->path}"
                : $old.parse_url($current, PHP_URL_PATH);
            if ($previous !== $current) {
                $replacements[$previous] = $current;
                $replacements[$this->escaped($previous)] = $this->escaped($current);
            }
        }

        return $replacements;
    }

    /**
     * Replace the old addresses in one column, returning the rows changed.
     *
     * @param  array<string, string>  $replacements
     */
    private function rewrite(string $table, string $key, string $column, string $host, array $replacements): int
    {
        $changed = 0;
        foreach (DB::table($table)->where($column, 'like', "%{$host}%")->get([$key, $column]) as $row) {
            $value = (string) $row->{$column};
            $rewritten = strtr($value, $replacements);
            if ($rewritten === $value) {
                continue;
            }

            $changed++;
            if (! $this->option('dry-run')) {
                DB::table($table)->where($key, $row->{$key})->update([$column => $rewritten]);
            }
        }

        return $changed;
    }

    /**
     * The text as json_encode() writes it inside a string, slashes escaped.
     */
    private function escaped(string $text): string
    {
        return substr((string) json_encode($text), 1, -1);
    }
}
