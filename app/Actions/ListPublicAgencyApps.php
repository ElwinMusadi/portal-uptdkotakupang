<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\AgencyApp;
use Illuminate\Database\QueryException;

class ListPublicAgencyApps
{
    /**
     * The apps and websites for the portal's Aplikasi & Situs page (D-054),
     * in display order, without IDs. A source that cannot be read fails the
     * request.
     *
     * @return list<array{nama: string, namaLengkap: string, keterangan: string, jenis: string, pengelola: string, tautan: string, logo: ?string}>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $apps = AgencyApp::query()->orderBy('urutan')->orderBy('id')->get();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The apps could not be read.', previous: $exception);
        }

        return $apps->map(fn (AgencyApp $app): array => [
            'nama' => $app->nama,
            'namaLengkap' => $app->nama_lengkap,
            'keterangan' => $app->keterangan,
            'jenis' => $app->jenis,
            'pengelola' => $app->pengelola,
            'tautan' => $app->tautan,
            'logo' => $app->logoUrl(),
        ])->values()->all();
    }
}
