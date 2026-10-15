<?php

namespace App\Models;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use Database\Factories\QueueTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A queue number printed at the information desk (Antrean Online, D-051),
 * e.g. A-001: one per service per WITA day, following the documents through
 * the stages its service had when it was printed. The random tracking code
 * behind the ticket's QR opens the taxpayer's tracking page. The plate number
 * stays until an administrator removes it.
 *
 * @property string $tanggal date in WITA, as YYYY-MM-DD
 * @property list<string> $tahap_layanan
 * @property ?QueueStage $tahap
 * @property QueueTicketStatus $status
 * @property ?Carbon $dibatalkan_pada
 * @property ?Carbon $dipanggil_pada
 * @property ?Carbon $masuk_tahap_pada
 * @property ?Carbon $selesai_pada
 */
#[Fillable([
    'kode_lacak', 'queue_service_id', 'tanggal', 'urut', 'nomor', 'nopol', 'tahap_layanan', 'tahap', 'status', 'alasan_batal', 'dicetak_oleh',
    'dibatalkan_pada', 'queue_counter_id', 'dipanggil_pada', 'jumlah_panggil', 'masuk_tahap_pada', 'selesai_pada',
])]
class QueueTicket extends Model
{
    /** @use HasFactory<QueueTicketFactory> */
    use HasFactory, HasUlids;

    /**
     * Length of the tracking code: 22 letters and digits, about 131 bits.
     */
    public const int TRACKING_CODE_LENGTH = 22;

    /**
     * Route pattern of a tracking code.
     */
    public const string TRACKING_CODE_PATTERN = '[A-Za-z0-9]{22}';

    /**
     * Local time zone of the counters; a ticket's date and number follow it.
     */
    public const string TIME_ZONE = 'Asia/Makassar';

    /**
     * A new ticket has not been called, as in the table.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'jumlah_panggil' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urut' => 'integer',
            'tahap_layanan' => 'array',
            'tahap' => QueueStage::class,
            'status' => QueueTicketStatus::class,
            'dibatalkan_pada' => 'datetime',
            'dipanggil_pada' => 'datetime',
            'jumlah_panggil' => 'integer',
            'masuk_tahap_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    /**
     * The service the number was printed for.
     *
     * @return BelongsTo<QueueService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(QueueService::class, 'queue_service_id');
    }

    /**
     * The counter that called the number last at its stage, while called.
     *
     * @return BelongsTo<QueueCounter, $this>
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(QueueCounter::class, 'queue_counter_id');
    }

    /**
     * The employee who printed the number.
     *
     * @return BelongsTo<User, $this>
     */
    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicetak_oleh');
    }

    /**
     * What happened to the ticket, oldest first.
     *
     * @return HasMany<QueueEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(QueueEvent::class)->oldest('id');
    }

    /**
     * The ticket's stages in flow order.
     *
     * @return list<QueueStage>
     */
    public function stages(): array
    {
        return QueueStage::inOrder($this->tahap_layanan);
    }

    /**
     * The ticket's place in its stages, as a station's marking records it
     * before and after so it can be undone.
     *
     * @return array{tahap: ?string, status: string, queue_counter_id: ?string, dipanggil_pada: ?string, jumlah_panggil: int, masuk_tahap_pada: ?string, selesai_pada: ?string}
     */
    public function snapshot(): array
    {
        return [
            'tahap' => $this->tahap?->value,
            'status' => $this->status->value,
            'queue_counter_id' => $this->queue_counter_id,
            'dipanggil_pada' => $this->dipanggil_pada?->format('Y-m-d H:i:s'),
            'jumlah_panggil' => $this->jumlah_panggil,
            'masuk_tahap_pada' => $this->masuk_tahap_pada?->format('Y-m-d H:i:s'),
            'selesai_pada' => $this->selesai_pada?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * The plate number as written on a plate, e.g. "DH 1234 AB"; null once
     * removed.
     */
    public function plate(): ?string
    {
        return $this->nopol === null ? null : trim((string) preg_replace('/^([A-Z]+)(\d+)([A-Z]*)$/', '$1 $2 $3', $this->nopol));
    }

    /**
     * The address of the taxpayer's tracking page, which the ticket's QR
     * holds.
     */
    public function trackingUrl(): string
    {
        return url("/lacak/{$this->kode_lacak}");
    }
}
