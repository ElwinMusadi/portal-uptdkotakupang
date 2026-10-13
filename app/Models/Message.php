<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A question, suggestion or complaint from a citizen, received through the
 * portal's contact form or recorded by staff from another channel (D-016).
 * Its text and sender are never changed afterwards; staff change its status
 * and add responses.
 */
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUlids;

    /**
     * @var list<string>
     */
    public const array KINDS = ['pertanyaan', 'masukan', 'pengaduan'];

    /**
     * @var list<string>
     */
    public const array STATES = ['baru', 'diproses', 'selesai'];

    /**
     * @var list<string>
     */
    public const array SOURCES = ['formulir', 'kotak-saran', 'lapor', 'whatsapp', 'telepon'];

    /**
     * The services a message can be about, as the portal and dashboard list them.
     *
     * @var list<string>
     */
    public const array SERVICES = [
        'Umum / portal PINTU',
        'Pajak tahunan',
        'Perpanjangan STNK 5 tahun',
        'Mutasi & balik nama',
        'Duplikat STNK',
        'Samsat Keliling',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diterima' => 'datetime',
            'urutan' => 'integer',
        ];
    }

    /**
     * Responses in the order they were recorded.
     *
     * @return HasMany<MessageResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(MessageResponse::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * A new ticket number such as "PINTU-7KQ2MX" that no message uses yet.
     */
    public static function newTicket(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $ticket = 'PINTU-';
            for ($i = 0; $i < 6; $i++) {
                $ticket .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::query()->where('tiket', $ticket)->exists());

        return $ticket;
    }
}
