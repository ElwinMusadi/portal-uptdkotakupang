<?php

namespace App\Actions;

use App\Models\PotentialImport;

/**
 * The Pemetaan PKB figures on the public portal (D-052 slice 2): only once an
 * Administrator has shown the data, for the whole data only, with no
 * identity. Each area (the city, each kecamatan, each kelurahan) gives its
 * vehicles, motorcycles and vehicles with three wheels or more (the city also
 * three and four wheels apart, and the vehicles of no recorded type), paid
 * up, late by less than a year and in arrears, and the tax potential, the
 * amount still due and the arrears.
 *
 * No figure describes fewer than three vehicles: such figures are hidden,
 * and so are the smallest others while a hidden one could still be worked
 * out from a total it adds up to (cell suppression). The totals are an
 * area's vehicles for its wheel classes and its compliance groups, the
 * city's three-wheelers and up for its three and four wheels, and the
 * kecamatan and city figures for the areas within them. Last, all totals
 * are combined as equations, and one more figure is hidden while they
 * still work out a hidden figure, or hidden figures together, describing
 * fewer than three vehicles.
 */
class BuildPublicPotentialMap
{
    /**
     * Fewest vehicles a published figure may describe.
     */
    public const int MINIMUM = 3;

    /**
     * The counts published for every area.
     *
     * @var list<string>
     */
    private const array COUNTS = ['kendaraan', 'rodaDua', 'rodaTigaLebih', 'taat', 'belumBayar', 'menunggak'];

    /**
     * The amounts published for every area, with the count they sum over.
     *
     * @var array<string, string>
     */
    private const array AMOUNTS = ['potensi' => 'kendaraan', 'tagihan' => 'belumTaat', 'tunggakan' => 'menunggak'];

    /**
     * Cells by area and figure: value, the vehicles it describes, hidden.
     *
     * @var array<string, array<string, array{nilai: int, bobot: int, samar: bool}>>
     */
    private array $cells = [];

    public function __construct(private BuildPotentialSummary $summary) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $current = PotentialImport::current();

        if ($current === null || ! $current->tampil) {
            return ['tersedia' => false];
        }

        $summary = $this->summary->handle($current, null);
        $this->cells = [];
        $this->area('kota', $summary['jumlah']);
        $tree = [];
        foreach ($summary['kecamatan'] as $kecamatan) {
            $this->area($kecamatan['kunci'], $kecamatan);
            // Kelurahan outside Kota Kupang's list are not shown on their own
            $tree[$kecamatan['kunci']] = $kecamatan['kunci'] === ''
                ? []
                : array_map(fn (array $kelurahan): string => $this->kelurahanArea($kecamatan['kunci'], $kelurahan), $kecamatan['kelurahan']);
        }
        $this->suppress($this->totals($tree));

        return [
            'tersedia' => true,
            'tanggalPenetapan' => $current->tanggal_penetapan?->toDateString(),
            'diperbarui' => $current->selesai_pada?->toIso8601String(),
            'minimum' => self::MINIMUM,
            'kota' => $this->published('kota'),
            'kecamatan' => array_map(fn (array $kecamatan): array => [
                'kunci' => $kecamatan['kunci'],
                'nama' => $kecamatan['nama'],
                ...$this->published($kecamatan['kunci']),
                'kelurahan' => array_map(
                    fn (string $area): array => ['nama' => substr($area, strlen($kecamatan['kunci']) + 1), ...$this->published($area)],
                    $tree[$kecamatan['kunci']],
                ),
            ], $summary['kecamatan']),
        ];
    }

    /**
     * @param  array<string, mixed>  $kelurahan
     */
    private function kelurahanArea(string $kecamatan, array $kelurahan): string
    {
        $area = $kecamatan.'|'.$kelurahan['nama'];
        $this->area($area, $kelurahan);

        return $area;
    }

    /**
     * The cells of an area from its summary figures, those below the minimum
     * hidden. Vehicles of no recorded type (published for the city only) and
     * of other compliance texts are not counted on to hide anything: they
     * are the difference of figures already shown, so they are taken as
     * known, and the city's are only hidden below the minimum.
     *
     * @param  array<string, mixed>  $figures
     */
    private function area(string $area, array $figures): void
    {
        $counts = [
            'kendaraan' => $figures['kendaraan'],
            'rodaDua' => $figures['roda']['r2'],
            'rodaTigaLebih' => $figures['roda']['r3'] + $figures['roda']['r4'],
            'rodaTiga' => $figures['roda']['r3'],
            'rodaEmpat' => $figures['roda']['r4'],
            'rodaLain' => $figures['roda']['lain'],
            'taat' => $figures['kelompok']['taat'],
            'belumBayar' => $figures['kelompok']['jatuh-tempo'] + $figures['kelompok']['terlambat'],
            'menunggak' => $figures['kelompok']['menunggak'],
            'belumTaat' => $figures['kendaraan'] - $figures['kelompok']['taat'],
        ];
        $amounts = [
            'potensi' => $figures['pokokPajak'],
            'tagihan' => $figures['tagihan'],
            'tunggakan' => $figures['pokokTunggak'] + $figures['dendaTunggak'],
        ];

        foreach ($counts as $figure => $count) {
            $this->cells[$area][$figure] = ['nilai' => $count, 'bobot' => $count, 'samar' => $this->tooFew($count)];
        }
        foreach (self::AMOUNTS as $figure => $count) {
            $this->cells[$area][$figure] = ['nilai' => $amounts[$figure], 'bobot' => $counts[$count], 'samar' => $this->tooFew($counts[$count])];
        }
    }

    /**
     * The totals the published figures add up to: an area's vehicles over
     * its wheel classes and over its compliance groups (the vehicles of no
     * recorded type and of other compliance texts are taken as known), the
     * city's three-wheelers and up over its three and four wheels, and each
     * kecamatan and city figure over the areas within.
     *
     * @param  array<string, list<string>>  $tree  the kelurahan areas of each kecamatan
     * @return list<array{array{string, string}, list<array{string, string}>}>
     */
    private function totals(array $tree): array
    {
        $totals = [];
        foreach (array_keys($this->cells) as $area) {
            $totals[] = [[$area, 'kendaraan'], [[$area, 'rodaDua'], [$area, 'rodaTigaLebih']]];
            $totals[] = [[$area, 'kendaraan'], [[$area, 'taat'], [$area, 'belumBayar'], [$area, 'menunggak']]];
        }
        $totals[] = [['kota', 'rodaTigaLebih'], [['kota', 'rodaTiga'], ['kota', 'rodaEmpat']]];
        foreach ([...self::COUNTS, ...array_keys(self::AMOUNTS)] as $column) {
            $totals[] = [['kota', $column], array_map(fn (string $kecamatan): array => [$kecamatan, $column], array_keys($tree))];
            foreach (array_filter($tree) as $kecamatan => $kelurahan) {
                $totals[] = [[$kecamatan, $column], array_map(fn (string $area): array => [$area, $column], $kelurahan)];
            }
        }

        return $totals;
    }

    /**
     * Hide more figures until no hidden one describing fewer than the
     * minimum vehicles can be worked out: each total is checked on its own,
     * then all totals are combined, and while that still works out such a
     * figure, one more is hidden and everything is checked again.
     *
     * @param  list<array{array{string, string}, list<array{string, string}>}>  $totals
     */
    private function suppress(array $totals): void
    {
        do {
            foreach ($totals as [$total, $parts]) {
                $this->protect($total, $parts);
            }
            $workedOut = $this->workedOut($totals);
        } while ($workedOut !== null && $this->hideAnother($workedOut, $totals));
    }

    /**
     * Hide the smallest shown parts of a total while it gives a hidden figure
     * away; hide the total itself when no part is left to hide.
     *
     * @param  array{string, string}  $total
     * @param  list<array{string, string}>  $parts
     */
    private function protect(array $total, array $parts): void
    {
        while ($this->givesAway($total, $parts)) {
            $shown = array_values(array_filter($parts, fn (array $part): bool => ! $this->cell($part)['samar'] && $this->cell($part)['bobot'] > 0));
            if ($shown === []) {
                $this->cells[$total[0]][$total[1]]['samar'] = true;

                return;
            }
            usort($shown, fn (array $a, array $b): int => $this->cell($a)['bobot'] <=> $this->cell($b)['bobot']);
            $this->cells[$shown[0][0]][$shown[0][1]]['samar'] = true;
        }
    }

    /**
     * Whether a total and its parts give a hidden figure away: it is the only
     * one hidden among them, so the others work it out.
     *
     * @param  array{string, string}  $total
     * @param  list<array{string, string}>  $parts
     */
    private function givesAway(array $total, array $parts): bool
    {
        return count(array_filter([$total, ...$parts], fn (array $at): bool => $this->cell($at)['samar'])) === 1;
    }

    /**
     * Hidden figures that all totals combined still work out as fewer than
     * the minimum vehicles: one figure, or two together; null when there are
     * none.
     *
     * @param  list<array{array{string, string}, list<array{string, string}>}>  $totals
     * @return ?list<array{string, string}>
     */
    private function workedOut(array $totals): ?array
    {
        $small = [];
        foreach ($totals as [$total, $parts]) {
            foreach ([$total, ...$parts] as $at) {
                if ($this->cell($at)['samar'] && $this->cell($at)['bobot'] < self::MINIMUM) {
                    $small[$this->key($at)] = $at;
                }
            }
        }
        $small = array_values($small);
        $echelon = $this->echelon($totals);

        $sets = [];
        foreach ($small as $i => $first) {
            $sets[] = [$first];
            foreach (array_slice($small, $i + 1) as $second) {
                $sets[] = [$first, $second];
            }
        }
        foreach ($sets as $set) {
            if ($this->tooFew(array_sum(array_map(fn (array $at): int => $this->cell($at)['bobot'], $set)))
                && $this->reduce($echelon, array_fill_keys(array_map($this->key(...), $set), 1)) === []) {
                return $set;
            }
        }

        return null;
    }

    /**
     * Hide one more figure near figures worked out: the smallest shown one in
     * a total with them that stops it, zeros last since a hidden zero reads
     * as a missing figure; otherwise the first of these, so that repeating
     * this ends.
     *
     * @param  list<array{string, string}>  $workedOut
     * @param  list<array{array{string, string}, list<array{string, string}>}>  $totals
     */
    private function hideAnother(array $workedOut, array $totals): bool
    {
        $near = [];
        $shown = [];
        $workedOutKeys = array_map($this->key(...), $workedOut);
        foreach ($totals as [$total, $parts]) {
            $cells = [$total, ...$parts];
            $isNear = array_intersect(array_map($this->key(...), $cells), $workedOutKeys) !== [];
            foreach ($cells as $at) {
                if (! $this->cell($at)['samar']) {
                    $shown[$this->key($at)] = $at;
                    if ($isNear) {
                        $near[$this->key($at)] = $at;
                    }
                }
            }
        }
        $candidates = array_values($near !== [] ? $near : $shown);
        if ($candidates === []) {
            return false;
        }
        usort($candidates, fn (array $a, array $b): int => [$this->cell($a)['bobot'] === 0, $this->cell($a)['bobot']] <=> [$this->cell($b)['bobot'] === 0, $this->cell($b)['bobot']]);

        $vector = array_fill_keys($workedOutKeys, 1);
        foreach ($candidates as $at) {
            $this->cells[$at[0]][$at[1]]['samar'] = true;
            if ($this->reduce($this->echelon($totals), $vector) !== []) {
                return true;
            }
            $this->cells[$at[0]][$at[1]]['samar'] = false;
        }
        $this->cells[$candidates[0][0]][$candidates[0][1]]['samar'] = true;

        return true;
    }

    /**
     * The totals as equations over the hidden figures, in row echelon form: each row eliminated against the rows before it, kept
     * with the figure it is now the only one to have among them.
     *
     * @param  list<array{array{string, string}, list<array{string, string}>}>  $totals
     * @return list<array{string, array<string, int>}>
     */
    private function echelon(array $totals): array
    {
        $echelon = [];
        foreach ($totals as [$total, $parts]) {
            $row = [];
            foreach ([[$total, -1], ...array_map(fn (array $part): array => [$part, 1], $parts)] as [$at, $sign]) {
                if ($this->cell($at)['samar']) {
                    $row[$this->key($at)] = ($row[$this->key($at)] ?? 0) + $sign;
                }
            }
            $row = $this->reduce($echelon, $row);
            if ($row !== []) {
                $echelon[] = [array_key_first($row), $row];
            }
        }

        return $echelon;
    }

    /**
     * Eliminate the echelon rows' figures from a combination of figures, in
     * whole numbers; nothing is left when the totals work the combination
     * out.
     *
     * @param  list<array{string, array<string, int>}>  $echelon
     * @param  array<string, int>  $row
     * @return array<string, int>
     */
    private function reduce(array $echelon, array $row): array
    {
        $row = array_filter($row);
        foreach ($echelon as [$key, $pivot]) {
            if (isset($row[$key])) {
                $times = $row[$key];
                $row = array_map(fn (int $value): int => $value * $pivot[$key], $row);
                foreach ($pivot as $other => $value) {
                    $row[$other] = ($row[$other] ?? 0) - $times * $value;
                }
                $row = array_filter($row);
                $divisor = array_reduce($row, fn (int $carry, int $value): int => $this->gcd($carry, abs($value)), 0);
                $row = $divisor > 1 ? array_map(fn (int $value): int => intdiv($value, $divisor), $row) : $row;
            }
        }

        return $row;
    }

    /**
     * @param  array{string, string}  $at
     * @return array{nilai: int, bobot: int, samar: bool}
     */
    private function cell(array $at): array
    {
        return $this->cells[$at[0]][$at[1]];
    }

    /**
     * @param  array{string, string}  $at
     */
    private function key(array $at): string
    {
        return $at[0]."\0".$at[1];
    }

    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * The figures published of an area: the city gives its three and four
     * wheels apart and its vehicles of no recorded type, the others their
     * three-wheelers and up together.
     *
     * @return list<string>
     */
    private function figures(string $area): array
    {
        return $area === 'kota'
            ? ['kendaraan', 'rodaDua', 'rodaTiga', 'rodaEmpat', 'rodaLain', 'taat', 'belumBayar', 'menunggak', ...array_keys(self::AMOUNTS)]
            : [...self::COUNTS, ...array_keys(self::AMOUNTS)];
    }

    /**
     * The figures a visitor sees of an area: hidden ones are null.
     *
     * @return array<string, ?int>
     */
    private function published(string $area): array
    {
        $figures = $this->figures($area);

        return array_combine($figures, array_map(fn (string $figure): ?int => $this->cells[$area][$figure]['samar'] ? null : $this->cells[$area][$figure]['nilai'], $figures));
    }

    /**
     * Whether a figure describing this many vehicles must not be published:
     * some, but fewer than the minimum.
     */
    private function tooFew(int $vehicles): bool
    {
        return $vehicles > 0 && $vehicles < self::MINIMUM;
    }
}
