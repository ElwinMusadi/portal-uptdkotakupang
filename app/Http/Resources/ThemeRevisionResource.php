<?php

namespace App\Http\Resources;

use App\Models\Setting;
use App\Models\ThemeRevision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A saved theme in the history of Pengaturan › Tampilan (D-057). Settings
 * added after it was saved take their built-in value, so it can be used
 * again as it is.
 *
 * @mixin ThemeRevision
 */
class ThemeRevisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tema' => [
                'portal' => [...Setting::DEFAULT_PORTAL_THEME, ...$this->tema['portal']],
                'dashboard' => [...Setting::DEFAULT_THEME_SECTION, ...$this->tema['dashboard']],
            ],
            'disimpan' => $this->created_at?->toIso8601String(),
            'oleh' => $this->user?->name,
        ];
    }
}
