<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicTariff
{
    /**
     * Build the tariff for the portal's PKB simulation. "diperbarui" is null
     * until the tariff is first saved, so the portal never simulates with
     * rates nobody has set. A source that cannot be read fails the request
     * instead of showing zero rates.
     *
     * @return array{opsenPersen: int|float, dendaPersenPerBulan: int|float, maksBulanDenda: int, swdklljMotor: int, swdklljMobil: int, contohPokok: int, contohJenis: string, contohTelat: int, catatan: string, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::TARIFF);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The tariff could not be read.', previous: $exception);
        }

        $tariff = $setting?->value ?? Setting::UNSAVED_TARIFF;

        return [
            'opsenPersen' => $tariff['opsenPersen'],
            'dendaPersenPerBulan' => $tariff['dendaPersenPerBulan'],
            'maksBulanDenda' => $tariff['maksBulanDenda'],
            'swdklljMotor' => $tariff['swdklljMotor'],
            'swdklljMobil' => $tariff['swdklljMobil'],
            'contohPokok' => $tariff['contohPokok'],
            'contohJenis' => $tariff['contohJenis'],
            'contohTelat' => $tariff['contohTelat'],
            'catatan' => $tariff['catatan'],
            'diperbarui' => $setting?->updated_at?->toIso8601String(),
        ];
    }
}
