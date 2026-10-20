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
 * in the file, is skipped, but gets the SKPD form number from the file when
 * it was saved before form numbers were kept (D-071). A file with nothing new
 * and nothing to complete is refused, so importing the same file again adds
 * nothing.
 */
class ImportRevenue
{
    public function __construct(private RecordActivity $recordActivity) {}

    /**
     * The transactions not saved yet, in the file's order, how many were
     * skipped, and the form number of each skipped transaction still without
     * one, by fingerprint.
     *
     * @param  list<array<string, int|string>>  $transactions
     * @return array{baru: list<array<string, int|string>>, dilewati: int, dilengkapi: array<string, string>}
     */
    public function compare(array $transactions): array
    {
        $saved = [];
        foreach (array_chunk(array_column($transactions, 'sidik'), 1000) as $chunk) {
            foreach (RevenueTransaction::query()->whereIn('sidik', $chunk)->toBase()->get(['sidik', 'nomoratur']) as $row) {
                $saved[$row->sidik] = $row->nomoratur;
            }
        }

        $new = [];
        $completed = [];
        foreach ($transactions as $transaction) {
            $sidik = (string) $transaction['sidik'];
            if (! array_key_exists($sidik, $saved)) {
                $new[] = $transaction;
            } elseif ($saved[$sidik] === null) {
                $completed[$sidik] = (string) $transaction['nomoratur'];
            }
            $saved[$sidik] ??= (string) $transaction['nomoratur'];
        }

        return ['baru' => $new, 'dilewati' => count($transactions) - count($new), 'dilengkapi' => $completed];
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
     * import, and complete the form numbers of the transactions saved before
     * form numbers were kept (D-071). The import is not shown on the portal
     * until checked. A file that only completes form numbers adds no import.
     *
     * @param  array{kunci: string, nama: string, jenis: string}  $counter
     * @param  list<array<string, int|string>>  $transactions  as read from the file
     * @return array{impor: ?RevenueImport, dilengkapi: int}
     *
     * @throws ValidationException when the file neither adds nor completes anything
     */
    public function handle(User $user, array $counter, string $fileName, array $transactions): array
    {
        return DB::transaction(function () use ($user, $counter, $fileName, $transactions): array {
            ['baru' => $new, 'dilewati' => $skipped, 'dilengkapi' => $completed] = $this->compare($transactions);
            if ($new === [] && $completed === []) {
                throw ValidationException::withMessages(['berkas' => 'Semua transaksi di berkas ini sudah pernah diimpor.']);
            }
            $dates = array_column($transactions, 'tanggal');
            $import = new RevenueImport([
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

            if ($new !== []) {
                $import->save();
                foreach (array_chunk($new, 500) as $chunk) {
                    RevenueTransaction::query()->insert(array_map(
                        fn (array $transaction): array => [...$transaction, 'revenue_import_id' => $import->getKey(), 'loket' => $counter['kunci']],
                        $chunk,
                    ));
                }
                $this->recordActivity->handle($user, DashboardModule::Penerimaan, 'mengimpor', $import->title().' · '.count($new).' transaksi');
            }

            if ($completed !== []) {
                $this->completeFormNumbers($completed);
                $this->recordActivity->handle($user, DashboardModule::Penerimaan, 'melengkapi', 'nomoratur '.$import->title().' · '.count($completed).' transaksi');
            }

            return ['impor' => $import->exists ? $import : null, 'dilengkapi' => count($completed)];
        });
    }

    /**
     * Give saved transactions without a form number the one from the file,
     * a few hundred per statement.
     *
     * @param  array<string, string>  $numbers  by fingerprint
     */
    private function completeFormNumbers(array $numbers): void
    {
        foreach (array_chunk($numbers, 500, true) as $chunk) {
            $cases = implode(' ', array_fill(0, count($chunk), 'WHEN ? THEN ?'));
            $placeholders = implode(', ', array_fill(0, count($chunk), '?'));
            $bindings = [];
            foreach ($chunk as $sidik => $number) {
                array_push($bindings, (string) $sidik, $number);
            }

            DB::update(
                "UPDATE revenue_transactions SET nomoratur = CASE sidik {$cases} END WHERE nomoratur IS NULL AND sidik IN ({$placeholders})",
                [...$bindings, ...array_map('strval', array_keys($chunk))],
            );
        }
    }
}
