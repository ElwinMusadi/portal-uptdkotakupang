<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\FaqStatus;
use App\Models\Faq;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new FAQ entry. Lifecycle, public intent and display are always stated
 * explicitly (GC18-01–03); the server sets the order.
 */
class StoreFaqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through FaqPolicy.
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
            'pertanyaan' => ['required', 'string', 'max:'.Faq::MAX_QUESTION_LENGTH, new PlainText('Pertanyaan')],
            'jawaban' => ['required', 'string', 'max:'.Faq::MAX_ANSWER_LENGTH, new PlainText('Jawaban')],
            'status' => ['required', Rule::enum(FaqStatus::class)],
            'publik' => ['required', 'boolean'],
            'tampil' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pertanyaan.required' => 'Pertanyaan wajib diisi.',
            'pertanyaan.string' => 'Pertanyaan tidak valid.',
            'pertanyaan.max' => 'Pertanyaan maksimal :max karakter.',
            'jawaban.required' => 'Jawaban wajib diisi.',
            'jawaban.string' => 'Jawaban tidak valid.',
            'jawaban.max' => 'Jawaban maksimal :max karakter.',
            'status.required' => 'Pilih status.',
            'status.enum' => 'Status tidak valid.',
            'publik.required' => 'Tentukan apakah tanya jawab ini untuk warga.',
            'publik.boolean' => 'Pilihan "Untuk warga" tidak valid.',
            'tampil.required' => 'Tentukan apakah tanya jawab ini ditampilkan.',
            'tampil.boolean' => 'Pilihan "Tampilkan di portal" tidak valid.',
        ];
    }
}
