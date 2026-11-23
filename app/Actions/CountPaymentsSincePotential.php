<?php

namespace App\Actions;

use App\Enums\Kecamatan;
use App\Enums\TaxCompliance;
use App\Models\PotentialImport;
use Illuminate\Support\Facades\DB;

/**
 * Pembayaran PKB on the Ikhtisar page (D-099): beside each kelurahan's
 * compliance in the Data Potensi in use, the STS transactions paying PKB
 * or arrears at any counter from its date (penetapan) through the latest
 * STS date. They are transactions, not vehicles: plate numbers are kept
 * for 7 days only (D-079), so a payment cannot be matched to a vehicle of
 * the Data Potensi. New vehicles (jenis daftar BARU) are not in the Data
 * Potensi and are counted apart. The kecamatan of a kelurahan comes from
 * the official list (D-052), as the STS kecamatan column often names
 * another city kecamatan; but a transaction whose kecamatan lies outside
 * the city is outside it, even when its village shares a city kelurahan's
 * name (MERDEKA, Kupang Timur). Those and names outside the list are
 * counted under "lainnya", so the totals still add up.
 */
class CountPaymentsSincePotential
{
    /**
     * The Data Potensi figures and the payments since its date, per
     * kelurahan of each kecamatan in the official order, every kelurahan
     * listed even without a figure.
     *
     * @param  array{kecamatan: list<array<string, mixed>>}  $summary  the import's Pemetaan summary of every due month
     * @return array<string, mixed>
     */
    public function handle(PotentialImport $import, array $summary, ?string $latest): array
    {
        $from = $import->tanggal_penetapan?->toDateString() ?? $import->selesai_pada?->setTimezone('Asia/Makassar')->toDateString();
        $window = $from !== null && $latest !== null && $from <= $latest ? ['dari' => $from, 'sampai' => $latest] : null;
        [$paid, $other, $new, $types] = $window === null ? [[], 0, 0, []] : $this->payments($window['dari'], $window['sampai']);

        $potential = [];
        $unplaced = null;
        foreach ($summary['kecamatan'] as $area) {
            if ($area['kunci'] === '') {
                $unplaced = $area;

                continue;
            }
            foreach ($area['kelurahan'] as $kelurahan) {
                $potential[$kelurahan['nama']] = $kelurahan;
            }
        }

        $areas = array_map(function (Kecamatan $kecamatan) use ($potential, $paid): array {
            $kelurahan = array_map(
                fn (string $name): array => ['nama' => $name, ...$this->figures($potential[$name] ?? null, $paid[$name] ?? 0)],
                $kecamatan->kelurahan(),
            );

            return ['kunci' => $kecamatan->value, 'nama' => $kecamatan->label(), ...$this->sum($kelurahan), 'kelurahan' => $kelurahan];
        }, Kecamatan::cases());
        // Vehicles of the Data Potensi and payments whose kelurahan is not on the list
        $other = $this->figures($unplaced, $other);

        return [
            'dataPotensi' => ['tanggal' => $from],
            'sts' => $window,
            'jumlah' => $this->sum([...$areas, $other]),
            'kecamatan' => $areas,
            'lainnya' => $other,
            'baru' => $new,
            'jenisDaftar' => $types,
        ];
    }

    /**
     * The transactions paying PKB or arrears in the window: per official
     * kelurahan, outside the list, of new vehicles, and per jenis daftar.
     *
     * @return array{array<string, int>, int, int, list<array{nilai: string, transaksi: int}>}
     */
    private function payments(string $from, string $until): array
    {
        $rows = DB::table('revenue_transactions')
            ->whereBetween('tanggal', [$from, $until])
            ->whereRaw('pkb + tunggakan > 0')
            ->select('kelurahan', 'kecamatan', 'jenis_daftar')
            ->selectRaw('COUNT(*) AS transaksi')
            ->groupBy('kelurahan', 'kecamatan', 'jenis_daftar')
            ->get();

        $paid = [];
        $other = 0;
        $new = 0;
        $types = [];
        foreach ($rows as $row) {
            $count = (int) $row->transaksi;
            $type = trim((string) $row->jenis_daftar);
            $types[$type] = ($types[$type] ?? 0) + $count;

            if (mb_strtoupper($type) === 'BARU') {
                $new += $count;

                continue;
            }

            $kecamatan = mb_strtoupper(trim((string) $row->kecamatan));
            $inCity = in_array($kecamatan, ['', '-'], true) || Kecamatan::tryFrom($kecamatan) !== null;
            $kelurahan = $inCity ? Kecamatan::officialKelurahan((string) $row->kelurahan) : null;
            if ($kelurahan === null) {
                $other += $count;
            } else {
                $paid[$kelurahan] = ($paid[$kelurahan] ?? 0) + $count;
            }
        }
        arsort($types);

        return [$paid, $other, $new, array_map(fn (string $type, int $count): array => ['nilai' => $type, 'transaksi' => $count], array_keys($types), $types)];
    }

    /**
     * A kelurahan's Data Potensi figures and payments.
     *
     * @param  ?array<string, mixed>  $figures
     * @return array{kendaraan: int, taat: int, belumBayar: int, menunggak: int, bayar: int}
     */
    private function figures(?array $figures, int $paid): array
    {
        $groups = $figures['kelompok'] ?? [];

        return [
            'kendaraan' => $figures['kendaraan'] ?? 0,
            'taat' => $groups[TaxCompliance::Taat->value] ?? 0,
            'belumBayar' => ($groups[TaxCompliance::JatuhTempo->value] ?? 0) + ($groups[TaxCompliance::Terlambat->value] ?? 0),
            'menunggak' => $groups[TaxCompliance::Menunggak->value] ?? 0,
            'bayar' => $paid,
        ];
    }

    /**
     * The sum of each figure over the rows.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{kendaraan: int, taat: int, belumBayar: int, menunggak: int, bayar: int}
     */
    private function sum(array $rows): array
    {
        $sum = array_fill_keys(['kendaraan', 'taat', 'belumBayar', 'menunggak', 'bayar'], 0);
        foreach ($rows as $row) {
            foreach ($sum as $key => $value) {
                $sum[$key] = $value + $row[$key];
            }
        }

        return $sum;
    }
}
