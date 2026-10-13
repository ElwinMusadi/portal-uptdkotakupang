<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The services in their new order. Services left out keep their relative
 * order after the listed ones.
 */
class ReorderServicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through ServicePolicy.
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
            'ids' => ['required', 'list', 'max:500'],
            'ids.*' => ['string', 'ulid', 'distinct'],
        ];
    }

    /**
     * Every listed service must still exist; one query checks them all.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $ids = $this->input('ids');

                if (Service::query()->whereKey($ids)->count() !== count($ids)) {
                    $validator->errors()->add('ids', 'Urutan tidak cocok dengan data terbaru. Muat ulang halaman lalu coba lagi.');
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
            'ids.required' => 'Daftar urutan kosong.',
            'ids.list' => 'Daftar urutan tidak valid.',
            'ids.max' => 'Daftar urutan terlalu panjang.',
            'ids.*.string' => 'Daftar urutan tidak valid.',
            'ids.*.ulid' => 'Daftar urutan tidak valid.',
            'ids.*.distinct' => 'Daftar urutan berisi entri ganda.',
        ];
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return $this->validated('ids');
    }
}
