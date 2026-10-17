<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
     * The length of the random code behind a message's tracking page (D-058),
     * as long as a queue ticket's: the address is all it takes to open it.
     */
    public const int TRACKING_CODE_LENGTH = 22;

    /**
     * The route pattern of a tracking code.
     */
    public const string TRACKING_CODE_PATTERN = '[A-Za-z0-9]{22}';

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

    /**
     * A new random code for a message's tracking page.
     */
    public static function newTrackingCode(): string
    {
        return Str::random(self::TRACKING_CODE_LENGTH);
    }

    /**
     * The address of the message's tracking page, which the sender gets
     * after sending and staff can pass on (D-058).
     */
    public function trackingUrl(): string
    {
        return url("/pesan/{$this->kode_lacak}");
    }

    /**
     * Whether the contact address is the one the message was sent with, as
     * a sender types it again: an email address in any case, a phone number
     * with or without spaces, dashes or the country code (0812…, 62812…,
     * +62 812…). A message without a contact address matches none.
     */
    public function hasContact(string $contact): bool
    {
        return $this->kontak !== '' && self::contactKey($this->kontak) === self::contactKey($contact);
    }

    /**
     * An email address in lower case, or a phone number as its digits with
     * the Indonesian country code written as a leading 0.
     */
    private static function contactKey(string $contact): string
    {
        if (str_contains($contact, '@')) {
            return mb_strtolower($contact);
        }

        $digits = (string) preg_replace('/^62/', '0', (string) preg_replace('/\D/', '', $contact));

        return str_starts_with($digits, '8') ? "0{$digits}" : $digits;
    }
}
