<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A link to the Ikhtisar page an administrator makes (D-099): its name, so
 * the links given to different groups can be told apart and revoked.
 */
class StoreOverviewLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; the route
     * checks OverviewLinkPolicy.
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
            'nama' => ['required', 'string', 'max:60', new PlainText('Nama tautan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Tulis nama tautan, mis. Rapat staf.',
            'nama.string' => 'Tulis nama tautan, mis. Rapat staf.',
            'nama.max' => 'Nama tautan paling banyak 60 karakter.',
        ];
    }
}
