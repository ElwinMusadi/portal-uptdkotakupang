<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicSite
{
    /**
     * The parts of Pengaturan Situs the portal shows: the footer's
     * description and copyright. The announcement bar has its own document
     * since D-082 (ShowPublicAnnouncementBar). Until the document is first
     * saved they are the text in the portal's markup and "diperbarui" is
     * null, so the portal keeps the markup as it is. A source that cannot be
     * read fails the request.
     *
     * @return array{deskripsi: string, hakCipta: string, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::SITE);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The site settings could not be read.', previous: $exception);
        }

        $site = $setting?->value ?? Setting::INITIAL_SITE;

        return [
            'deskripsi' => $site['deskripsi'],
            'hakCipta' => $site['hakCipta'],
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
