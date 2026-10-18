<?php

namespace App\Actions;

use App\Enums\Kecamatan;
use App\Enums\TaxCompliance;
use App\Enums\VehicleWheels;
use App\Models\PotentialImport;
use App\Models\PotentialVehicle;
use Illuminate\Support\Facades\DB;

/**
 * The Pemetaan PKB summary of an import (D-052). When the import finishes,
 * its vehicles are counted once per due month, kelurahan, wheel class,
 * compliance group, years in arrears, type and visit outcome; the summary
 * for one due month or all is then built from those few thousand rows: the
 * vehicles per wheel class and compliance group with the tax and fines due
 * and in arrears, for the city, each kecamatan and each kelurahan; the
 * vehicles per years in arrears and per type; the outcomes of the
 * door-to-door visits; and the vehicles per due month. Vehicles whose
 * kelurahan is empty or unknown are counted under an empty kecamatan.
 */
class BuildPotentialSummary
{
    /**
     * Rows saved at once.
     */
    private const int ROWS_PER_INSERT = 300;

    /**
     * Count the import's vehicles for its summary, anew. A visit outcome is
     * the part before the officer's note, "SIAP BAYAR | …" in the .xls table
     * and "SIAP BAYAR : …" in the .xlsx workbook (D-064), in capitals so that
     * "Rusak Berat" counts as "RUSAK BERAT".
     */
    public function store(PotentialImport $import): void
    {
        DB::table('potential_summaries')->where('potential_import_id', $import->getKey())->delete();

        $groups = PotentialVehicle::query()->whereBelongsTo($import, 'import')->toBase()
            ->selectRaw('bulan_jatuh_tempo, kecamatan, kelurahan, roda, kelompok, tahun_tunggak, jenis, dtd_status, count(*) as kendaraan, sum(pokok_pajak) as pokok_pajak, sum(denda_berjalan) as denda_berjalan, sum(pokok_tunggak) as pokok_tunggak, sum(denda_tunggak) as denda_tunggak, sum(tagihan) as tagihan')
            ->groupBy('bulan_jatuh_tempo', 'kecamatan', 'kelurahan', 'roda', 'kelompok', 'tahun_tunggak', 'jenis', 'dtd_status')
            ->get();

        $rows = [];
        foreach ($groups as $group) {
            $outcome = mb_strtoupper(trim(preg_split('/[|:]/', $group->dtd_status, 2)[0]));
            $key = implode("\0", [$group->bulan_jatuh_tempo, $group->kecamatan, $group->kelurahan, $group->roda, $group->kelompok, $group->tahun_tunggak, $group->jenis, $outcome]);
            $rows[$key] ??= [
                'potential_import_id' => $import->getKey(),
                'bulan' => $group->bulan_jatuh_tempo === null ? null : (int) $group->bulan_jatuh_tempo,
                'kecamatan' => $group->kecamatan,
                'kelurahan' => $group->kelurahan,
                'roda' => $group->roda,
                'kelompok' => $group->kelompok,
                'tahun_tunggak' => (int) $group->tahun_tunggak,
                'jenis' => $group->jenis,
                'hasil_kunjungan' => $outcome,
                'kendaraan' => 0, 'pokok_pajak' => 0, 'denda_berjalan' => 0, 'pokok_tunggak' => 0, 'denda_tunggak' => 0, 'tagihan' => 0,
            ];
            foreach (['kendaraan', 'pokok_pajak', 'denda_berjalan', 'pokok_tunggak', 'denda_tunggak', 'tagihan'] as $column) {
                $rows[$key][$column] += (int) $group->{$column};
            }
        }

        foreach (array_chunk(array_values($rows), self::ROWS_PER_INSERT) as $chunk) {
            DB::table('potential_summaries')->insert($chunk);
        }
    }

    /**
     * @return array{jumlah: array<string, mixed>, kecamatan: list<array<string, mixed>>, tunggakan: list<array{tahun: int, kendaraan: int, pokokTunggak: int, dendaTunggak: int}>, jenis: list<array{jenis: string, roda: ?string, kendaraan: int}>, kunjungan: list<array{hasil: string, kendaraan: int}>, bulan: list<array{bulan: int, kendaraan: int}>}
     */
    public function handle(PotentialImport $import, ?int $month): array
    {
        $rows = DB::table('potential_summaries')->where('potential_import_id', $import->getKey())->get();

        $city = $this->empty();
        $areas = [];
        $arrears = [];
        $types = [];
        $outcomes = [];
        $months = [];
        foreach ($rows as $row) {
            $count = (int) $row->kendaraan;
            if ($row->bulan !== null) {
                $months[(int) $row->bulan] = ($months[(int) $row->bulan] ?? 0) + $count;
            }
            if ($month !== null && (int) $row->bulan !== $month) {
                continue;
            }

            $this->add($city, $row);
            $areas[$row->kecamatan]['angka'] ??= $this->empty();
            $this->add($areas[$row->kecamatan]['angka'], $row);
            $areas[$row->kecamatan]['kelurahan'][$row->kelurahan] ??= $this->empty();
            $this->add($areas[$row->kecamatan]['kelurahan'][$row->kelurahan], $row);

            $years = (int) $row->tahun_tunggak;
            $arrears[$years] ??= ['tahun' => $years, 'kendaraan' => 0, 'pokokTunggak' => 0, 'dendaTunggak' => 0];
            $arrears[$years]['kendaraan'] += $count;
            $arrears[$years]['pokokTunggak'] += (int) $row->pokok_tunggak;
            $arrears[$years]['dendaTunggak'] += (int) $row->denda_tunggak;

            $types[$row->jenis."\0".$row->roda] ??= ['jenis' => $row->jenis, 'roda' => $row->roda, 'kendaraan' => 0];
            $types[$row->jenis."\0".$row->roda]['kendaraan'] += $count;

            if ($row->hasil_kunjungan !== '') {
                $outcomes[$row->hasil_kunjungan] = ($outcomes[$row->hasil_kunjungan] ?? 0) + $count;
            }
        }
        ksort($arrears);
        ksort($months);
        usort($types, fn (array $a, array $b): int => [$b['kendaraan'], $a['jenis']] <=> [$a['kendaraan'], $b['jenis']]);
        uksort($outcomes, fn (string $a, string $b): int => [$outcomes[$b], $a] <=> [$outcomes[$a], $b]);

        return [
            'jumlah' => $city,
            'kecamatan' => $this->kecamatan($areas),
            'tunggakan' => array_values($arrears),
            'jenis' => $types,
            'kunjungan' => array_map(fn (string $outcome, int $count): array => ['hasil' => $outcome, 'kendaraan' => $count], array_keys($outcomes), $outcomes),
            'bulan' => array_map(fn (int $number, int $count): array => ['bulan' => $number, 'kendaraan' => $count], array_keys($months), $months),
        ];
    }

    /**
     * The kecamatan in their official order, those not placed last, each
     * with its kelurahan by name.
     *
     * @param  array<string, array{angka: array<string, mixed>, kelurahan: array<string, array<string, mixed>>}>  $areas
     * @return list<array<string, mixed>>
     */
    private function kecamatan(array $areas): array
    {
        $order = [...array_column(Kecamatan::cases(), 'value'), ''];

        return array_values(array_filter(array_map(function (string $key) use ($areas): ?array {
            if (! isset($areas[$key])) {
                return null;
            }
            ksort($areas[$key]['kelurahan']);

            return [
                'kunci' => $key,
                'nama' => Kecamatan::tryFrom($key)?->label() ?? 'Tanpa kelurahan',
                ...$areas[$key]['angka'],
                'kelurahan' => array_map(
                    fn (string $name, array $figures): array => ['nama' => $name, ...$figures],
                    array_map('strval', array_keys($areas[$key]['kelurahan'])),
                    $areas[$key]['kelurahan'],
                ),
            ];
        }, $order)));
    }

    /**
     * @return array<string, mixed>
     */
    private function empty(): array
    {
        return [
            'kendaraan' => 0,
            'roda' => array_fill_keys([...array_column(VehicleWheels::cases(), 'value'), 'lain'], 0),
            'kelompok' => array_fill_keys(array_column(TaxCompliance::cases(), 'value'), 0),
            'pokokPajak' => 0,
            'dendaBerjalan' => 0,
            'pokokTunggak' => 0,
            'dendaTunggak' => 0,
            'tagihan' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $figures
     */
    private function add(array &$figures, object $row): void
    {
        $count = (int) $row->kendaraan;
        $figures['kendaraan'] += $count;
        $figures['roda'][$row->roda ?? 'lain'] += $count;
        $figures['kelompok'][$row->kelompok] += $count;
        $figures['pokokPajak'] += (int) $row->pokok_pajak;
        $figures['dendaBerjalan'] += (int) $row->denda_berjalan;
        $figures['pokokTunggak'] += (int) $row->pokok_tunggak;
        $figures['dendaTunggak'] += (int) $row->denda_tunggak;
        $figures['tagihan'] += (int) $row->tagihan;
    }
}
