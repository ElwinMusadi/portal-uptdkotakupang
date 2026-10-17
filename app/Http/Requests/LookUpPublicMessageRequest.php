<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A sender looking up their message on the contact page (D-058): the ticket
 * number they received and the contact address they sent the message with.
 */
class LookUpPublicMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. Anyone may
     * try; PublicMessageLookupController answers only a matching pair.
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
            'tiket' => ['required', 'string', 'max:20'],
            'kontak' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tiket.required' => 'Isi nomor tiket pesan Anda.',
            'tiket.string' => 'Nomor tiket tidak valid.',
            'tiket.max' => 'Nomor tiket tidak valid.',
            'kontak.required' => 'Isi email atau nomor WhatsApp yang Anda pakai saat mengirim pesan.',
            'kontak.string' => 'Kontak tidak valid.',
            'kontak.max' => 'Kontak maksimal :max karakter.',
        ];
    }

    /**
     * The ticket number as messages store it, e.g. "PINTU-7KQ2MX" from
     * "pintu-7kq2mx", "PINTU 7KQ2MX" or just "7kq2mx".
     */
    public function ticket(): string
    {
        $ticket = strtoupper((string) preg_replace('/[\s-]/', '', $this->validated('tiket')));

        return 'PINTU-'.(str_starts_with($ticket, 'PINTU') ? substr($ticket, 5) : $ticket);
    }
}
