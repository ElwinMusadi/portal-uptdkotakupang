<?php

namespace App\Http\Requests\Dashboard;

use App\Models\QueueTicket;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The day whose queue numbers the information desk lists (D-051); today in
 * WITA when omitted.
 */
class ListQueueTicketsRequest extends FormRequest
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
            'tanggal' => ['sometimes', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.date_format' => 'Tanggal tidak valid.',
        ];
    }

    /**
     * The day asked for, as YYYY-MM-DD.
     */
    public function day(): string
    {
        return $this->validated('tanggal') ?? now(QueueTicket::TIME_ZONE)->toDateString();
    }
}
