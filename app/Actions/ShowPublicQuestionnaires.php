<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicQuestionnaires
{
    /**
     * Whether each questionnaire at /kuesioner takes answers (D-098), without
     * when it was opened or closed. A source that cannot be read fails the
     * request.
     *
     * @return array{wajibPajak: array{dibuka: bool}, operator: array{dibuka: bool}}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $questionnaires = Setting::questionnaires();
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The questionnaire settings could not be read.', previous: $exception);
        }

        return [
            'wajibPajak' => ['dibuka' => $questionnaires['wajibPajak']['dibuka']],
            'operator' => ['dibuka' => $questionnaires['operator']['dibuka']],
        ];
    }
}
