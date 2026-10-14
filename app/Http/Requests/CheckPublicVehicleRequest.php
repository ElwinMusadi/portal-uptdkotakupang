<?php

namespace App\Http\Requests;

use App\Actions\LookUpVehicle;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A visitor's tax check on the portal (D-041): the plate number and the last
 * five characters of the chassis number, which stands in for proof that the
 * visitor holds the vehicle's papers.
 */
class CheckPublicVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request: anyone.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation: both values in capitals with letters
     * and digits only.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(array_map(
            fn (mixed $value): mixed => is_string($value) ? LookUpVehicle::normalise($value) : $value,
            $this->only(['nopol', 'rangka']),
        ));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nopol' => ['required', 'string', 'regex:/^[A-Z]{1,2}\d{1,4}[A-Z]{0,3}$/'],
            'rangka' => ['required', 'string', 'regex:/^[A-Z0-9]{5}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nopol.required' => 'Tulis nomor polisi kendaraan.',
            'nopol.string' => 'Tulis nomor polisi seperti DH 1234 AB.',
            'nopol.regex' => 'Tulis nomor polisi seperti DH 1234 AB.',
            'rangka.required' => 'Tulis 5 digit terakhir nomor rangka.',
            'rangka.string' => 'Tulis 5 digit terakhir nomor rangka.',
            'rangka.regex' => 'Tulis tepat 5 digit terakhir nomor rangka seperti tertera di STNK.',
        ];
    }
}
