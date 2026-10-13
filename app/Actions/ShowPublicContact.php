<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicContact
{
    /**
     * Build the office's contact details for the portal's footer and Kontak
     * page, without the list items' IDs. "diperbarui" is null until the
     * contact document is first saved, so the portal shows that the details
     * are not available yet instead of placeholders. A source that cannot be
     * read fails the request instead of showing no contact details.
     *
     * @return array{namaKantor: string, alamat: string, kota: string, provinsi: string, telepon: string, whatsapp: string, whatsappCatatan: string, email: string, mediaSosial: list<array{platform: string, akun: string, url: string}>, mapsUrl: string, catatanLokasi: string, janjiBalasan: string, kanalPengaduan: list<array{nama: string, deskripsi: string, tautanLabel: string, tautanUrl: string}>, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::CONTACT);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The contact details could not be read.', previous: $exception);
        }

        $contact = $setting?->value ?? Setting::UNSAVED_CONTACT;

        return [
            'namaKantor' => $contact['namaKantor'],
            'alamat' => $contact['alamat'],
            'kota' => $contact['kota'],
            'provinsi' => $contact['provinsi'],
            'telepon' => $contact['telepon'],
            'whatsapp' => $contact['whatsapp'],
            'whatsappCatatan' => $contact['whatsappCatatan'],
            'email' => $contact['email'],
            'mediaSosial' => array_map(fn (array $account): array => [
                'platform' => $account['platform'],
                'akun' => $account['akun'],
                'url' => $account['url'],
            ], $contact['mediaSosial']),
            'mapsUrl' => $contact['mapsUrl'],
            'catatanLokasi' => $contact['catatanLokasi'],
            'janjiBalasan' => $contact['janjiBalasan'],
            'kanalPengaduan' => array_map(fn (array $channel): array => [
                'nama' => $channel['nama'],
                'deskripsi' => $channel['deskripsi'],
                'tautanLabel' => $channel['tautanLabel'],
                'tautanUrl' => $channel['tautanUrl'],
            ], $contact['kanalPengaduan']),
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
