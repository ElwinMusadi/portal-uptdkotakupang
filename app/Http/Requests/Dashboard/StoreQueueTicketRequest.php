<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\LookUpVehicle;
use App\Models\QueueService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A queue number printed at the information desk (D-051): the plate number
 * the desk typed and the code of an active service.
 */
class StoreQueueTicketRequest extends FormRequest
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
     * Prepare the data for validation: the plate number in capitals with
     * letters and digits only, as the tax check stores it.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nopol'))) {
            $this->merge(['nopol' => LookUpVehicle::normalise($this->input('nopol'))]);
        }
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
            'layanan' => ['required', 'string', Rule::exists(QueueService::class, 'kode')->where('aktif', true)],
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
            'layanan.required' => 'Pilih layanan.',
            'layanan.string' => 'Pilih layanan.',
            'layanan.exists' => 'Layanan ini tidak ada atau sedang tidak aktif.',
        ];
    }

    /**
     * The service chosen.
     */
    public function service(): QueueService
    {
        return QueueService::query()->where('kode', $this->validated('layanan'))->firstOrFail();
    }
}
