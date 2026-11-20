<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengaturan › Kuesioner (D-098): whether each questionnaire takes answers.
 * The dashboard sends both; when each state began is kept by the server.
 */
class UpdateQuestionnaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through SettingPolicy.
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
            'wajibPajak' => ['required', 'array'],
            'wajibPajak.dibuka' => ['required', 'boolean:strict'],
            'operator' => ['required', 'array'],
            'operator.dibuka' => ['required', 'boolean:strict'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'array' => ':Attribute tidak valid.',
            'boolean' => ':Attribute harus dibuka atau ditutup.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'wajibPajak' => 'status Kuesioner Wajib Pajak',
            'wajibPajak.dibuka' => 'status Kuesioner Wajib Pajak',
            'operator' => 'status Kuesioner Petugas Operator',
            'operator.dibuka' => 'status Kuesioner Petugas Operator',
        ];
    }

    /**
     * Whether each questionnaire is to take answers. Anything else sent with
     * it, such as "sejak", is not kept.
     *
     * @return array{wajibPajak: bool, operator: bool}
     */
    public function opened(): array
    {
        return [
            'wajibPajak' => $this->validated('wajibPajak.dibuka'),
            'operator' => $this->validated('operator.dibuka'),
        ];
    }
}
