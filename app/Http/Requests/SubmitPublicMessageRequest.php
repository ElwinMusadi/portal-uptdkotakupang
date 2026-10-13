<?php

namespace App\Http\Requests;

use App\Http\Requests\Dashboard\StoreMessageRequest;
use App\Rules\ContactAddress;
use App\Rules\PlainText;
use Illuminate\Support\Arr;

/**
 * A message from the portal's contact form (D-016). A contact address and
 * consent are required. The hidden field "kode_tambahan" stays empty for
 * people; PublicMessageController discards messages that fill it.
 */
class SubmitPublicMessageRequest extends StoreMessageRequest
{
    /**
     * The hidden field only bots fill in.
     */
    public const string HONEYPOT = 'kode_tambahan';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...Arr::except(parent::rules(), ['sumber']),
            'kontak' => ['required', 'string', 'max:120', new ContactAddress],
            'isi' => ['required', 'string', 'min:10', 'max:2000', new PlainText('Isi pesan')],
            'persetujuan' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'persetujuan.accepted' => 'Setujui penggunaan data untuk menindaklanjuti pesan.',
        ];
    }

    /**
     * Whether the hidden field was filled in, which people never do.
     */
    public function isFromBot(): bool
    {
        return filled($this->input(self::HONEYPOT));
    }
}
