<?php

namespace App\Actions;

use App\Models\Setting;
use App\Models\User;

class SaveQuestionnaireStatus
{
    public function __construct(private SaveSetting $saveSetting) {}

    /**
     * Open or close the questionnaires (D-098). A questionnaire whose state
     * changes starts its "sejak" now; one left as it is keeps its own, so
     * saving the same states changes nothing. Like the site settings the
     * document is of no module and not in the activity log.
     *
     * @param  array{wajibPajak: bool, operator: bool}  $opened
     * @return array{wajibPajak: array{dibuka: bool, sejak: ?string}, operator: array{dibuka: bool, sejak: ?string}}
     */
    public function handle(User $user, array $opened): array
    {
        $stored = Setting::questionnaires();
        $now = now()->toIso8601String();

        $value = [];
        foreach (Setting::QUESTIONNAIRE_KINDS as $kind) {
            $value[$kind] = $stored[$kind]['dibuka'] === $opened[$kind]
                ? $stored[$kind]
                : ['dibuka' => $opened[$kind], 'sejak' => $now];
        }

        return $this->saveSetting->handle($user, Setting::QUESTIONNAIRE, $value)->value;
    }
}
