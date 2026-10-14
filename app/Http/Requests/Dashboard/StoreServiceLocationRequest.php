<?php

namespace App\Http\Requests\Dashboard;

use App\Models\ServiceLocation;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A new place in the list of places (D-044). Names are unique whatever their
 * letter case. The server sets the order.
 */
class StoreServiceLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through ServiceLocationPolicy.
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
            'nama' => ['required', 'string', 'max:120', new PlainText('Nama lokasi')],
            'kecamatan' => ['required', 'string', 'max:60', new PlainText('Kecamatan')],
        ];
    }

    /**
     * The name must not be in the list already.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('nama') || ! is_string($this->input('nama'))) {
                    return;
                }

                $taken = ServiceLocation::query()
                    ->named($this->input('nama'))
                    ->when($this->currentLocation(), fn ($query, ServiceLocation $location) => $query->whereKeyNot($location->getKey()))
                    ->exists();

                if ($taken) {
                    $validator->errors()->add('nama', 'Lokasi ini sudah ada di daftar.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lokasi wajib diisi.',
            'nama.string' => 'Nama lokasi tidak valid.',
            'nama.max' => 'Nama lokasi maksimal :max karakter.',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'kecamatan.string' => 'Kecamatan tidak valid.',
            'kecamatan.max' => 'Kecamatan maksimal :max karakter.',
        ];
    }

    /**
     * The place being edited; none when adding one.
     */
    protected function currentLocation(): ?ServiceLocation
    {
        return null;
    }
}
