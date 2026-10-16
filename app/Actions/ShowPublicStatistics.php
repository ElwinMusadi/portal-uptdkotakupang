<?php

namespace App\Actions;

use App\Exceptions\PublicContentUnavailableException;
use App\Models\Setting;
use Illuminate\Database\QueryException;

class ShowPublicStatistics
{
    /**
     * Build the transparency dashboard for the portal's homepage. Only
     * statistics verified by the leadership are published (D-018): until
     * then "set" is empty and "diperbarui" is null, and no unverified figure
     * leaves the server. Tile IDs and the status are left out. A source that
     * cannot be read fails the request instead of showing no statistics.
     *
     * @return array{tahun: string, catatan: string, set: list<array{kunci: string, label: string, sub: string, judulGrafik: string, satuan: string, tiles: list<array{label: string, nilai: int|float, satuan: string, perubahan: int|float, naikBaik: bool}>, seri: list<int|float>}>, diperbarui: ?string}
     *
     * @throws PublicContentUnavailableException
     */
    public function handle(): array
    {
        try {
            $setting = Setting::query()->find(Setting::STATISTICS);
        } catch (QueryException $exception) {
            throw new PublicContentUnavailableException('The statistics could not be read.', previous: $exception);
        }

        if ($setting === null || $setting->value['status'] !== Setting::STATISTICS_VERIFIED) {
            return ['tahun' => '', 'catatan' => '', 'set' => [], 'diperbarui' => null];
        }

        $statistics = Setting::currentStatisticsCategories($setting->value);

        return [
            'tahun' => $statistics['tahun'],
            'catatan' => $statistics['catatan'],
            'set' => array_map(fn (array $category): array => [
                'kunci' => $category['kunci'],
                'label' => $category['label'],
                'sub' => $category['sub'],
                'judulGrafik' => $category['judulGrafik'],
                'satuan' => $category['satuan'],
                'tiles' => array_map(fn (array $tile): array => [
                    'label' => $tile['label'],
                    'nilai' => $tile['nilai'],
                    'satuan' => $tile['satuan'],
                    'perubahan' => $tile['perubahan'],
                    'naikBaik' => $tile['naikBaik'],
                ], $category['tiles']),
                'seri' => $category['seri'],
            ], $statistics['set']),
            'diperbarui' => $setting->updated_at?->toIso8601String(),
        ];
    }
}
