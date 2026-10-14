<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use App\Rules\YoutubeUrl;
use Illuminate\Database\QueryException;

class ShowPublicHomepage
{
    /**
     * Build the homepage's texts for the public portal, without the list
     * items' IDs. Until the document is first saved it is the text already
     * in the homepage's markup and "diperbarui" is null, so the portal keeps
     * the markup as it is. A source that cannot be read fails the request.
     *
     * @return array<string, mixed>
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::HOMEPAGE);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The homepage texts could not be read.', previous: $exception);
        }

        $homepage = Setting::upgradeHomepage($setting?->value ?? Setting::INITIAL_HOMEPAGE);
        $withoutId = fn (array $items): array => array_map(fn (array $item): array => array_diff_key($item, ['id' => true]), $items);
        $videoId = YoutubeUrl::id($homepage['video']['url']);

        return [
            ...$homepage,
            // The portal embeds the video by its ID; no link, no button (D-040)
            'video' => $videoId === null ? null : [
                'id' => $videoId,
                'label' => $homepage['video']['label'],
                'durasi' => $homepage['video']['durasi'],
                'judul' => $homepage['video']['judul'],
            ],
            'nilai' => $withoutId($homepage['nilai']),
            'mitra' => $withoutId($homepage['mitra']),
            'kanal' => $withoutId($homepage['kanal']),
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
