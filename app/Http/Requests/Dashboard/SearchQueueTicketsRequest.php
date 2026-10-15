<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\LookUpVehicle;
use App\Models\QueueTicket;
use Illuminate\Foundation\Http\FormRequest;

/**
 * What a counter typed or scanned to find a queue number (D-051): the
 * ticket's QR (its tracking address or code), its number (A-001, a001, A1)
 * or the plate number.
 */
class SearchQueueTicketsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; the route
     * checks QueueTicketPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Pindai QR atau ketik nomor antrean atau nomor polisi.',
            'q.string' => 'Pindai QR atau ketik nomor antrean atau nomor polisi.',
            'q.max' => 'Teks pencarian terlalu panjang.',
        ];
    }

    /**
     * The tracking code in a scanned QR, if the text holds one.
     */
    public function trackingCode(): ?string
    {
        $pattern = '/(?:^|\/lacak\/)('.QueueTicket::TRACKING_CODE_PATTERN.')(?:[?#].*)?$/';

        return preg_match($pattern, trim($this->validated('q')), $match) === 1 ? $match[1] : null;
    }

    /**
     * The queue number typed, written as printed (A-001), if the text is one.
     */
    public function number(): ?string
    {
        return preg_match('/^([A-Za-z])\s*-?\s*(\d{1,4})$/', trim($this->validated('q')), $match) === 1
            ? sprintf('%s-%03d', strtoupper($match[1]), (int) $match[2])
            : null;
    }

    /**
     * The text as a plate number: capitals, letters and digits only.
     */
    public function plate(): string
    {
        return LookUpVehicle::normalise($this->validated('q'));
    }
}
