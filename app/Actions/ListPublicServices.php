<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Service;
use Illuminate\Database\QueryException;

class ListPublicServices
{
    /**
     * List the published services in display order for the portal's Layanan
     * page and homepage, without IDs or workflow details other than the time
     * of publication. A source that cannot be read fails the request instead
     * of showing no services.
     *
     * @return list<array{nama: string, slug: string, ringkasan: string, loket: string, jenis: string, cekFisik: bool, nonTunai: bool, diwakilkan: bool, label: list<string>, persyaratan: list<array{dokumen: string, jumlah: string}>, catatan: string, unggulan: bool, terbitPada: ?string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $services = Service::query()->published()->orderBy('urutan')->orderBy('id')->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The services could not be read.', previous: $exception);
        }

        return $services->map(fn (Service $service): array => [
            'nama' => $service->nama,
            'slug' => $service->slug,
            'ringkasan' => $service->ringkasan,
            'loket' => $service->loket,
            'jenis' => $service->jenis,
            'cekFisik' => $service->cek_fisik,
            'nonTunai' => $service->non_tunai,
            'diwakilkan' => $service->diwakilkan,
            'label' => $service->label,
            'persyaratan' => array_map(fn (array $requirement): array => [
                'dokumen' => $requirement['dokumen'],
                'jumlah' => $requirement['jumlah'],
            ], $service->persyaratan),
            'catatan' => $service->catatan,
            'unggulan' => $service->unggulan,
            'terbitPada' => $service->terbit_pada?->toIso8601String(),
        ])->values()->all();
    }
}
