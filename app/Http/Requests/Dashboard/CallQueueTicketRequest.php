<?php

namespace App\Http\Requests\Dashboard;

use App\Models\QueueTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A counter's call (D-051): the next waiting number, or the given one.
 */
class CallQueueTicketRequest extends FormRequest
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
            'tiket' => ['sometimes', 'string', Rule::exists(QueueTicket::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tiket.string' => 'Nomor antrean ini tidak ditemukan.',
            'tiket.exists' => 'Nomor antrean ini tidak ditemukan.',
        ];
    }

    /**
     * The number to call, or null for the next one.
     */
    public function ticket(): ?QueueTicket
    {
        return $this->has('tiket') ? QueueTicket::query()->findOrFail($this->validated('tiket')) : null;
    }
}
