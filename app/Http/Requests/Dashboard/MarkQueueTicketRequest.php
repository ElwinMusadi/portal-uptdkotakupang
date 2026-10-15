<?php

namespace App\Http\Requests\Dashboard;

use App\Models\QueueTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A station's marking (D-051 slice 3): the number scanned or chosen from the
 * station's list.
 */
class MarkQueueTicketRequest extends FormRequest
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
            'tiket' => ['required', 'string', Rule::exists(QueueTicket::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tiket.required' => 'Pilih nomor antrean yang ditandai.',
            'tiket.string' => 'Nomor antrean ini tidak ditemukan.',
            'tiket.exists' => 'Nomor antrean ini tidak ditemukan.',
        ];
    }

    /**
     * The number to mark.
     */
    public function ticket(): QueueTicket
    {
        return QueueTicket::query()->findOrFail($this->validated('tiket'));
    }
}
