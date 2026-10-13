<?php

namespace App\Models;

use App\Enums\DashboardModule;
use Database\Factories\UpdateStandardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A standard in Jadwal Pemutakhiran (D-025): how often the content of a
 * monitored module must be updated, who owns it, and after how many days it
 * is overdue. The dashboard computes the last update from the module itself.
 */
#[Fillable(['konten', 'modul', 'unit', 'frekuensi', 'interval_hari'])]
class UpdateStandard extends Model
{
    /** @use HasFactory<UpdateStandardFactory> */
    use HasFactory, HasUlids;

    /**
     * The modules whose last update the dashboard can compute.
     *
     * @var list<DashboardModule>
     */
    public const array MONITORED_MODULES = [
        DashboardModule::Keliling,
        DashboardModule::Dokumentasi,
        DashboardModule::Jam,
        DashboardModule::Layanan,
        DashboardModule::Berita,
        DashboardModule::Dokumen,
        DashboardModule::Papan,
        DashboardModule::Profil,
    ];

    /**
     * Longest content name, owner and frequency, in characters. Matches the dashboard form.
     */
    public const int MAX_TEXT_LENGTH = 120;

    /**
     * Longest limit, in days. Matches the dashboard form.
     */
    public const int MAX_INTERVAL_DAYS = 730;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modul' => DashboardModule::class,
            'interval_hari' => 'integer',
            'urutan' => 'integer',
        ];
    }

    /**
     * Limit the query to the standards the user follows: every standard for
     * managers of Jadwal Pemutakhiran, otherwise those of the modules the
     * user manages (D-010).
     */
    #[Scope]
    protected function followedBy(Builder $query, User $user): void
    {
        $modules = $user->managedModules();

        if (! in_array(DashboardModule::Pemutakhiran->value, $modules, true)) {
            $query->whereIn('modul', $modules);
        }
    }
}
