<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Dashboard\Concerns\ReadsQueueTicketQuery;
use Illuminate\Foundation\Http\FormRequest;

/**
 * What a counter typed or scanned to find a queue number (D-051): the
 * ticket's QR (its tracking address or code), its number (A-001, a001, A1)
 * or the plate number.
 */
class SearchQueueTicketsRequest extends FormRequest
{
    use ReadsQueueTicketQuery;

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
}
