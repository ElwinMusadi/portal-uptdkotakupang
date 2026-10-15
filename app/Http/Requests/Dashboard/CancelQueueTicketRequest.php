<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelling a queue number, with the reason (D-051).
 */
class CancelQueueTicketRequest extends FormRequest
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
            'alasan' => ['required', 'string', 'max:200', new PlainText('Alasan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Tulis alasan pembatalan.',
            'alasan.string' => 'Tulis alasan pembatalan.',
            'alasan.max' => 'Alasan paling banyak 200 karakter.',
        ];
    }
}
