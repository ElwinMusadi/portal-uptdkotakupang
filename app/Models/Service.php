<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    use HasFactory, HasUlids;

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
            'status' => WorkflowStatus::class,
            'terbit_pada' => 'datetime',
            'riwayat' => 'array',
        ];
    }

    /**
     * Limit the query to services on the portal.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', WorkflowStatus::Published->value);
    }

    /**
     * Put the service in a workflow status and record the step first in its
     * history, as the dashboard shows it: who, what, the new status and the
     * note when there is one.
     */
    public function moveTo(WorkflowStatus $status, string $by, string $verb, string $note = ''): void
    {
        $this->status = $status;
        $this->riwayat = [
            ['waktu' => now()->toIso8601String(), 'oleh' => $by, 'aksi' => $verb, 'ke' => $status->value, ...($note !== '' ? ['catatan' => $note] : [])],
            ...($this->riwayat ?? []),
        ];
    }
}
