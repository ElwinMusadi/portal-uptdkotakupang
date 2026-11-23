<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengaturan › Halaman Ikhtisar (D-100): whether the page at
 * /laporan-penerimaan is open to anyone. When the state began is kept by the
 * server.
 */
class UpdateOverviewStatusRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'aktif' => ['required', 'boolean:strict'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aktif.required' => 'Pilih aktif atau nonaktif.',
            'aktif.boolean' => 'Status halaman harus aktif atau nonaktif.',
        ];
    }

    /**
     * Whether the page is to be open. Anything else sent with it, such as
     * "sejak", is not kept.
     */
    public function open(): bool
    {
        return $this->validated('aktif');
    }
}
