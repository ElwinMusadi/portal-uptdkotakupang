<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A waiting-room TV display an administrator sets up (D-051): its name.
 */
class StoreQueueDisplayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; the route
     * checks QueueDisplayPolicy.
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
            'nama' => ['required', 'string', 'max:60', new PlainText('Nama layar')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Tulis nama layar, mis. TV ruang tunggu.',
            'nama.string' => 'Tulis nama layar, mis. TV ruang tunggu.',
            'nama.max' => 'Nama layar paling banyak 60 karakter.',
        ];
    }
}
