<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\OrganizationUnit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class ShowPublicOrganization
{
    /**
     * The organisation chart for the portal's Struktur Organisasi page: the
     * UPTD's units as a tree in display order, each with the units under
     * it, and the partners in the joint office. IDs and the officials' NIP
     * are left out. A unit whose parent no longer exists is shown at the
     * top. A source that cannot be read fails the request.
     *
     * @return array{unit: list<array<string, mixed>>, mitra: list<array{nama: string, singkatan: string, foto: ?string, uraianTugas: string, catatan: string}>}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $units = OrganizationUnit::query()->orderBy('urutan')->orderBy('id')->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The organisation chart could not be read.', previous: $exception);
        }

        [$partners, $internal] = $units->partition(fn (OrganizationUnit $unit): bool => $unit->jenis === OrganizationUnit::PARTNER);
        $ids = $internal->pluck('id')->all();
        $children = $internal->groupBy(fn (OrganizationUnit $unit): string => in_array($unit->induk, $ids, true) ? $unit->induk : '');

        return [
            'unit' => $this->branch($children, ''),
            'mitra' => $partners->map(fn (OrganizationUnit $unit): array => [
                'nama' => $unit->nama,
                'singkatan' => $unit->singkatan,
                'foto' => $unit->foto,
                'uraianTugas' => $unit->uraian_tugas,
                'catatan' => $unit->catatan,
            ])->values()->all(),
        ];
    }

    /**
     * The units under the given parent ('' for the top), with theirs.
     *
     * @param  Collection<string, Collection<int, OrganizationUnit>>  $children
     * @return list<array<string, mixed>>
     */
    private function branch(Collection $children, string $parent): array
    {
        return $children->get($parent, collect())->map(fn (OrganizationUnit $unit): array => [
            'nama' => $unit->nama,
            'singkatan' => $unit->singkatan,
            'jenis' => $unit->jenis,
            'jabatan' => $unit->jabatan,
            'pejabat' => $unit->pejabat,
            'foto' => $unit->foto,
            'anggota' => $unit->anggota,
            'uraianTugas' => $unit->uraian_tugas,
            'catatan' => $unit->catatan,
            'bawahan' => $this->branch($children, $unit->id),
        ])->values()->all();
    }
}
