<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The complete tariff for the PKB simulation: the opsen and late-payment
 * penalty rates, the SWDKLLJ contribution per vehicle type, the example
 * values the simulation opens with, and a note shown below it.
 */
class UpdateTariffRequest extends FormRequest
{
    /**
     * Vehicle types, as the dashboard names them.
     *
     * @var list<string>
     */
    public const array VEHICLE_TYPES = ['motor', 'mobil'];

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
            'opsenPersen' => ['required', 'numeric', 'between:0,100'],
            'dendaPersenPerBulan' => ['required', 'numeric', 'between:0,10'],
            'maksBulanDenda' => ['required', 'integer', 'between:0,60'],
            'swdklljMotor' => ['required', 'integer', 'between:0,10000000'],
            'swdklljMobil' => ['required', 'integer', 'between:0,10000000'],
            'contohPokok' => ['required', 'integer', 'between:0,999999999'],
            'contohJenis' => ['required', Rule::in(self::VEHICLE_TYPES)],
            'contohTelat' => ['required', 'integer', 'between:0,60'],
            'catatan' => ['nullable', 'string', 'max:300', new PlainText('Catatan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opsenPersen.required' => 'Isi opsen PKB.',
            'opsenPersen.numeric' => 'Opsen PKB harus berupa angka.',
            'opsenPersen.between' => 'Opsen PKB 0 sampai 100%.',
            'dendaPersenPerBulan.required' => 'Isi denda keterlambatan.',
            'dendaPersenPerBulan.numeric' => 'Denda keterlambatan harus berupa angka.',
            'dendaPersenPerBulan.between' => 'Denda keterlambatan 0 sampai 10% per bulan.',
            'maksBulanDenda.required' => 'Isi maksimal bulan denda.',
            'maksBulanDenda.integer' => 'Maksimal bulan denda harus bilangan bulat.',
            'maksBulanDenda.between' => 'Maksimal bulan denda 0 sampai 60.',
            'swdklljMotor.required' => 'Isi SWDKLLJ sepeda motor.',
            'swdklljMotor.integer' => 'SWDKLLJ sepeda motor harus bilangan bulat.',
            'swdklljMotor.between' => 'SWDKLLJ sepeda motor 0 sampai Rp10.000.000.',
            'swdklljMobil.required' => 'Isi SWDKLLJ mobil.',
            'swdklljMobil.integer' => 'SWDKLLJ mobil harus bilangan bulat.',
            'swdklljMobil.between' => 'SWDKLLJ mobil 0 sampai Rp10.000.000.',
            'contohPokok.required' => 'Isi pokok PKB contoh.',
            'contohPokok.integer' => 'Pokok PKB contoh harus bilangan bulat.',
            'contohPokok.between' => 'Pokok PKB contoh 0 sampai Rp999.999.999.',
            'contohJenis.required' => 'Pilih jenis kendaraan contoh.',
            'contohJenis.in' => 'Jenis kendaraan contoh tidak valid.',
            'contohTelat.required' => 'Isi bulan keterlambatan contoh.',
            'contohTelat.integer' => 'Bulan keterlambatan contoh harus bilangan bulat.',
            'contohTelat.between' => 'Bulan keterlambatan contoh 0 sampai 60.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
        ];
    }

    /**
     * The tariff as it is stored. Whole percentages are kept as integers, so
     * a saved tariff reads back identical and saving it again is no change.
     *
     * @return array{opsenPersen: int|float, dendaPersenPerBulan: int|float, maksBulanDenda: int, swdklljMotor: int, swdklljMobil: int, contohPokok: int, contohJenis: string, contohTelat: int, catatan: string}
     */
    public function tariff(): array
    {
        return [
            'opsenPersen' => $this->percentage('opsenPersen'),
            'dendaPersenPerBulan' => $this->percentage('dendaPersenPerBulan'),
            'maksBulanDenda' => (int) $this->validated('maksBulanDenda'),
            'swdklljMotor' => (int) $this->validated('swdklljMotor'),
            'swdklljMobil' => (int) $this->validated('swdklljMobil'),
            'contohPokok' => (int) $this->validated('contohPokok'),
            'contohJenis' => $this->validated('contohJenis'),
            'contohTelat' => (int) $this->validated('contohTelat'),
            'catatan' => $this->validated('catatan') ?? '',
        ];
    }

    private function percentage(string $key): int|float
    {
        $value = (float) $this->validated($key);

        return floor($value) === $value ? (int) $value : $value;
    }
}
