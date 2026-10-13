<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationWorkflow;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A service and its requirements on the portal's Layanan page and the
 * homepage (Persyaratan Layanan, D-022). It goes through the publication
 * workflow; only published services reach the portal. "riwayat" lists the
 * workflow steps, newest first, and is written only by the server.
 */
#[Fillable(['nama', 'slug', 'ringkasan', 'loket', 'jenis', 'cek_fisik', 'non_tunai', 'diwakilkan', 'label', 'persyaratan', 'catatan', 'unggulan', 'penyedia', 'pemeriksa'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasPublicationWorkflow, HasUlids;

    /**
     * Service kinds: a service of its own, or extra requirements shown with
     * the services (e.g. for companies).
     *
     * @var list<string>
     */
    public const array KINDS = ['layanan', 'tambahan'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cek_fisik' => 'boolean',
            'non_tunai' => 'boolean',
            'diwakilkan' => 'boolean',
            'label' => 'array',
            'persyaratan' => 'array',
            'unggulan' => 'boolean',
            'urutan' => 'integer',
        ];
    }
}
