<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Message;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Staff changes to a message: its status, and responses to add. The
 * dashboard sends the whole response list; responses the server already
 * has are left as they are, so they can be added but never changed. The
 * sender's fields cannot be changed here.
 */
class UpdateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through MessagePolicy.
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
            'status' => ['sometimes', Rule::in(Message::STATES)],
            'tanggapan' => ['sometimes', 'list', 'max:500'],
            'tanggapan.*' => ['required', 'array'],
            'tanggapan.*.id' => ['nullable', 'string', 'max:40'],
            'tanggapan.*.isi' => ['required', 'string', 'max:2000', new PlainText('Tanggapan')],
            'tanggapan.*.untukPelapor' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status tidak valid.',
            'tanggapan.list' => 'Daftar tanggapan tidak valid.',
            'tanggapan.max' => 'Daftar tanggapan terlalu panjang.',
            'tanggapan.*.required' => 'Tanggapan tidak valid.',
            'tanggapan.*.array' => 'Tanggapan tidak valid.',
            'tanggapan.*.id.string' => 'Tanggapan tidak valid.',
            'tanggapan.*.id.max' => 'Tanggapan tidak valid.',
            'tanggapan.*.isi.required' => 'Tanggapan wajib diisi.',
            'tanggapan.*.isi.string' => 'Tanggapan tidak valid.',
            'tanggapan.*.isi.max' => 'Tanggapan maksimal :max karakter.',
            'tanggapan.*.untukPelapor.boolean' => 'Tanggapan tidak valid.',
        ];
    }

    /**
     * The responses the message does not have yet: their text, and whether
     * the sender sees them (D-058; an internal note when not given).
     *
     * @return list<array{isi: string, untukPelapor: bool}>
     */
    public function newResponses(Message $message): array
    {
        $known = $message->responses()->pluck('id')->all();

        return array_values(array_map(
            fn (array $response): array => ['isi' => $response['isi'], 'untukPelapor' => (bool) ($response['untukPelapor'] ?? false)],
            array_filter(
                $this->validated('tanggapan', []),
                fn (array $response): bool => ! in_array($response['id'] ?? null, $known, true),
            ),
        ));
    }
}
