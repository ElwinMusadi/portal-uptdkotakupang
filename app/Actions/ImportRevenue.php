<?php

namespace App\Actions;

use App\Enums\DashboardModule;
use App\Models\RevenueImport;
use App\Models\RevenueTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns the transactions read from an STS file into an import for a counter
 * (D-047). A transaction already saved by an earlier import, or listed twice
 * in the file, is skipped; a file with nothing new is refused, so importing
 * the same file again adds nothing.
 */
class ImportRevenue
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * The transactions not saved yet, in the file's order, and how many were
     * skipped.
     *
     * @param  list<array<string, int|string>>  $transactions
     * @return array{list<array<string, int|string>>, int}
     */
    public function newOnly(array $transactions): array
    {
        $saved = [];
        foreach (array_chunk(array_column($transactions, 'sidik'), 1000) as $chunk) {
            $saved += array_fill_keys(RevenueTransaction::query()->whereIn('sidik', $chunk)->pluck('sidik')->all(), true);
        }

        $new = [];
        foreach ($transactions as $transaction) {
            if (! isset($saved[$transaction['sidik']])) {
                $new[] = $transaction;
                $saved[$transaction['sidik']] = true;
            }
        }

        return [$new, count($transactions) - count($new)];
    }

    /**
     * What an import of these transactions would add, for the preview: the
     * dates, the number of transactions and their total, per registration
     * type with the largest amounts first.
     *
     * @param  list<array<string, int|string>>  $transactions
     * @return array{tanggalAwal: ?string, tanggalAkhir: ?string, transaksi: int, jumlah: int, jenisDaftar: list<array{nilai: string, transaksi: int, jumlah: int}>}
     */
    public function summary(array $transactions): array
    {
        $types = [];
        foreach ($transactions as $transaction) {
            $type = (string) $transaction['jenis_daftar'];
            $types[$type] ??= ['nilai' => $type, 'transaksi' => 0, 'jumlah' => 0];
            $types[$type]['transaksi']++;
            $types[$type]['jumlah'] += (int) $transaction['jumlah'];
        }
        usort($types, fn (array $a, array $b): int => [$b['jumlah'], $b['transaksi']] <=> [$a['jumlah'], $a['transaksi']]);
        $dates = array_column($transactions, 'tanggal');

        return [
            'tanggalAwal' => $dates === [] ? null : min($dates),
            'tanggalAkhir' => $dates === [] ? null : max($dates),
            'transaksi' => count($transactions),
            'jumlah' => array_sum(array_column($transactions, 'jumlah')),
            'jenisDaftar' => $types,
        ];
    }

    /**
     * Save the new transactions of a file for a counter, logged as one
     * import. The import is not shown on the portal until checked.
     *
     * @param  array{kunci: string, nama: string, jenis: string}  $counter
     * @param  list<array<string, int|string>>  $transactions  as read from the file
     *
     * @throws ValidationException when every transaction was saved before
     */
    public function handle(User $user, array $counter, string $fileName, array $transactions): RevenueImport
    {
        return DB::transaction(function () use ($user, $counter, $fileName, $transactions): RevenueImport {
            [$new, $skipped] = $this->newOnly($transactions);
            if ($new === []) {
                throw ValidationException::withMessages(['berkas' => 'Semua transaksi di berkas ini sudah pernah diimpor.']);
            }
            $dates = array_column($transactions, 'tanggal');

            $import = RevenueImport::query()->create([
                'loket' => $counter['kunci'],
                'nama_loket' => $counter['nama'],
                'nama_berkas' => mb_substr($fileName, 0, 160),
                'tanggal_awal' => min($dates),
                'tanggal_akhir' => max($dates),
                'jumlah_transaksi' => count($new),
                'jumlah_dilewati' => $skipped,
                'total' => array_sum(array_column($new, 'jumlah')),
                'user_id' => $user->getKey(),
            ]);

            foreach (array_chunk($new, 500) as $chunk) {
                RevenueTransaction::query()->insert(array_map(
                    fn (array $transaction): array => [...$transaction, 'revenue_import_id' => $import->getKey(), 'loket' => $counter['kunci']],
                    $chunk,
                ));
            }

            $this->recordActivity->handle($user, DashboardModule::Penerimaan, 'mengimpor', $import->title().' · '.count($new).' transaksi');

            return $import;
        });
    }
}
