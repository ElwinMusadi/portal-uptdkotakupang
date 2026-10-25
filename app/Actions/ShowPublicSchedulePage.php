<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicSchedulePage
{
    /**
     * Build the Jadwal page's texts for the public portal (D-080). Until the
     * document is first saved it is the text already in the page's markup
     * and "diperbarui" is null, so the portal keeps the markup as it is. A
     * source that cannot be read fails the request.
     *
     * @return array{pembuka: array<string, string>, jam: array<string, string>, luarKantor: array<string, string>, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::SCHEDULE_PAGE);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The Jadwal page texts could not be read.', previous: $exception);
        }

        return [
            ...($setting?->value ?? Setting::INITIAL_SCHEDULE_PAGE),
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
