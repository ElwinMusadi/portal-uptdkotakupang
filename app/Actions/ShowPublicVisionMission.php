<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicVisionMission
{
    /**
     * Build the Visi & Misi page for the public portal, without the list
     * items' IDs or the pledge file. Until the document is first saved it is
     * the official initial text and "diperbarui" is null. A source that
     * cannot be read fails the request instead of showing other text.
     *
     * @return array{sumberVisi: string, visi: string, misi: list<array{teks: string, kataKunci: string}>, kontribusi: list<array{misi: string, judul: string, deskripsi: string}>, nilai: list<array{huruf: string, judul: string, deskripsi: string}>, maklumat: array{teks: string, penandatangan: string}, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::VISION_MISSION);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The vision and missions could not be read.', previous: $exception);
        }

        $document = $setting?->value ?? Setting::INITIAL_VISION_MISSION;

        return [
            'sumberVisi' => $document['sumberVisi'],
            'visi' => $document['visi'],
            'misi' => array_map(fn (array $mission): array => [
                'teks' => $mission['teks'],
                'kataKunci' => $mission['kataKunci'],
            ], $document['misi']),
            'kontribusi' => array_map(fn (array $contribution): array => [
                'misi' => $contribution['misi'],
                'judul' => $contribution['judul'],
                'deskripsi' => $contribution['deskripsi'],
            ], $document['kontribusi']),
            'nilai' => array_map(fn (array $value): array => [
                'huruf' => $value['huruf'],
                'judul' => $value['judul'],
                'deskripsi' => $value['deskripsi'],
            ], $document['nilai']),
            'maklumat' => [
                'teks' => $document['maklumat']['teks'],
                'penandatangan' => $document['maklumat']['penandatangan'],
            ],
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
