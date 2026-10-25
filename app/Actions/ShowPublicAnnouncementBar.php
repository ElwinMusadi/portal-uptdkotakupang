<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicAnnouncementBar
{
    /**
     * The announcement bar for the portal's Informasi page (D-082). Until it
     * is first saved it is the bar in the page's markup and "diperbarui" is
     * null, so the portal keeps the markup as it is. A source that cannot be
     * read fails the request.
     *
     * @return array{aktif: bool, teks: string, tautanLabel: string, tautanUrl: string, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::ANNOUNCEMENT_BAR);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The announcement bar could not be read.', previous: $exception);
        }

        return [
            ...($setting?->value ?? Setting::INITIAL_ANNOUNCEMENT_BAR),
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
