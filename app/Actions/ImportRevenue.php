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
 * in the file, is skipped, but gets from the file what it was saved without:
 * the SKPD form number when it was saved before form numbers were kept
 * (D-071), and its details when it is from the last 7 days and was saved
 * before details were kept (D-079). A file with nothing new and nothing to
 * complete is refused, so importing the same file again adds nothing.
 */
class ImportRevenue
{
    public function __construct(private RecordActivity $recordActivity, private ClearRevenueDetails $clearDetails) {}

    /**
     * The transactions not saved yet, in the file's order, how many were
     * skipped, and what each skipped transaction still lacks, by fingerprint:
     * its form number, its details, or both.
     *
     * @param  list<array<string, mixed>>  $transactions
     * @return array{baru: list<array<string, mixed>>, dilewati: int, dilengkapi: array<string, array{nomoratur: ?string, rincian: ?array<string, ?string>}>}
     */
    public function compare(array $transactions): array
    {
        $saved = [];
        foreach (array_chunk(array_column($transactions, 'sidik'), 1000) as $chunk) {
            $rows = RevenueTransaction::query()->whereIn('sidik', $chunk)->toBase()->selectRaw('sidik, nomoratur, rincian is null as tanpa_rincian')->get();
            foreach ($rows as $row) {
                $saved[$row->sidik] = ['nomoratur' => $row->nomoratur === null, 'rincian' => (bool) $row->tanpa_rincian];
            }
        }

        $since = RevenueTransaction::detailsSince()->toDateString();
        $new = [];
        $completed = [];
        foreach ($transactions as $transaction) {
            $sidik = (string) $transaction['sidik'];
            if (! array_key_exists($sidik, $saved)) {
                $new[] = $transaction;
            } else {
                $lacks = [
                    'nomoratur' => $saved[$sidik]['nomoratur'] ? (string) $transaction['nomoratur'] : null,
                    'rincian' => $saved[$sidik]['rincian'] && $transaction['tanggal'] >= $since ? $transaction['rincian'] : null,
                ];
                if ($lacks['nomoratur'] !== null || $lacks['rincian'] !== null) {
                    $completed[$sidik] = $lacks;
                }
            }
            $saved[$sidik] = ['nomoratur' => false, 'rincian' => false];
        }

        return ['baru' => $new, 'dilewati' => count($transactions) - count($new), 'dilengkapi' => $completed];
    }

    /**
     * What an import of these transactions would add, for the preview: the
     * dates, the number of transactions and their total, per registration
     * type with the largest amounts first.
     *
     * @param  list<array<string, mixed>>  $transactions
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
     * import, with their details when they are from the last 7 days; complete
     * the form numbers and details of the transactions saved without them;
     * then drop the details older than 7 days (D-079). The import is not
     * shown on the portal until checked. A file that only completes adds no
     * import.
     *
     * @param  array{kunci: string, nama: string, jenis: string}  $counter
     * @param  list<array<string, mixed>>  $transactions  as read from the file
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
                $since = RevenueTransaction::detailsSince()->toDateString();
                foreach (array_chunk($new, 500) as $chunk) {
                    RevenueTransaction::query()->insert(array_map(
                        fn (array $transaction): array => [
                            ...$transaction,
                            'rincian' => $transaction['tanggal'] >= $since ? RevenueTransaction::encryptDetails($transaction['rincian']) : null,
                            'revenue_import_id' => $import->getKey(),
                            'loket' => $counter['kunci'],
                        ],
                        $chunk,
                    ));
                }
                $this->recordActivity->handle($user, DashboardModule::Penerimaan, 'mengimpor', $import->title().' · '.count($new).' transaksi');
            }

            if ($completed !== []) {
                $this->completeTransactions($completed);
                $what = array_filter([
                    in_array(true, array_map(fn (array $lacks): bool => $lacks['nomoratur'] !== null, $completed), true) ? 'nomoratur' : null,
                    in_array(true, array_map(fn (array $lacks): bool => $lacks['rincian'] !== null, $completed), true) ? 'rincian' : null,
                ]);
                $this->recordActivity->handle($user, DashboardModule::Penerimaan, 'melengkapi', implode(' dan ', $what).' '.$import->title().' · '.count($completed).' transaksi');
            }

            $this->clearDetails->handle();

            return ['impor' => $import->exists ? $import : null, 'dilengkapi' => count($completed)];
        });
    }

    /**
     * Give saved transactions the form number and details they lack, a few
     * hundred per statement. What a transaction already has is kept.
     *
     * @param  array<string, array{nomoratur: ?string, rincian: ?array<string, ?string>}>  $lacking  by fingerprint
     */
    private function completeTransactions(array $lacking): void
    {
        foreach (array_chunk($lacking, 500, true) as $chunk) {
            $cases = implode(' ', array_fill(0, count($chunk), 'WHEN ? THEN ?'));
            $placeholders = implode(', ', array_fill(0, count($chunk), '?'));
            $numbers = [];
            $details = [];
            foreach ($chunk as $sidik => $lacks) {
                array_push($numbers, (string) $sidik, $lacks['nomoratur']);
                array_push($details, (string) $sidik, $lacks['rincian'] === null ? null : RevenueTransaction::encryptDetails($lacks['rincian']));
            }

            DB::update(
                "UPDATE revenue_transactions SET nomoratur = COALESCE(nomoratur, CASE sidik {$cases} END), rincian = COALESCE(rincian, CASE sidik {$cases} END) WHERE sidik IN ({$placeholders})",
                [...$numbers, ...$details, ...array_map('strval', array_keys($chunk))],
            );
        }
    }
}
