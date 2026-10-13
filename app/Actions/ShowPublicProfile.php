<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicProfile
{
    /**
     * Build the Tentang UPTD page for the public portal, without the list
     * items' IDs or the photos. Until the document is first saved it is the
     * initial text and "diperbarui" is null. A source that cannot be read
     * fails the request instead of showing other text.
     *
     * @return array{judul: string, lanjutan: string, pengantar: string, keteranganFoto: string, kedudukan: array{judul: string, lanjutan: string, deskripsi: string}, identitas: list<array{label: string, nilai: string}>, tugasFungsi: list<array{judul: string, deskripsi: string, kataKunci: string}>, sambutan: array{kutipan: string, nama: string, jabatan: string}, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::PROFILE);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The profile could not be read.', previous: $exception);
        }

        $profile = $setting?->value ?? Setting::INITIAL_PROFILE;

        return [
            'judul' => $profile['judul'],
            'lanjutan' => $profile['lanjutan'],
            'pengantar' => $profile['pengantar'],
            'keteranganFoto' => $profile['keteranganFoto'],
            'kedudukan' => [
                'judul' => $profile['kedudukan']['judul'],
                'lanjutan' => $profile['kedudukan']['lanjutan'],
                'deskripsi' => $profile['kedudukan']['deskripsi'],
            ],
            'identitas' => array_map(fn (array $fact): array => [
                'label' => $fact['label'],
                'nilai' => $fact['nilai'],
            ], $profile['identitas']),
            'tugasFungsi' => array_map(fn (array $duty): array => [
                'judul' => $duty['judul'],
                'deskripsi' => $duty['deskripsi'],
                'kataKunci' => $duty['kataKunci'],
            ], $profile['tugasFungsi']),
            'sambutan' => [
                'kutipan' => $profile['sambutan']['kutipan'],
                'nama' => $profile['sambutan']['nama'],
                'jabatan' => $profile['sambutan']['jabatan'],
            ],
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
