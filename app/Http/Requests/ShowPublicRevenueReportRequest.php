<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The period of the portal's revenue report (D-048, D-049). Unlike the other
 * public endpoints (D-004), it takes a query parameter, but only one: a day
 * (`tanggal`) or a whole month (`bulan`); the latest published day when
 * omitted. Any other parameter is refused, so no visitor can ask for a
 * period or a group the fixed tables do not publish.
 */
class ShowPublicRevenueReportRequest extends FormRequest
{
    /**
     * The query parameters taken.
     *
     * @var list<string>
     */
    private const array PARAMETERS = ['tanggal', 'bulan'];

    /**
     * Determine if the user is authorized to make this request.
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
            'tanggal' => ['sometimes', 'prohibits:bulan', 'date_format:Y-m-d'],
            'bulan' => ['sometimes', 'date_format:Y-m'],
        ];
    }

    /**
     * Refuse unknown parameters.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (array_diff(array_keys($this->query()), self::PARAMETERS) !== []) {
                    $validator->errors()->add('query', 'Parameter kueri tidak didukung.');
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
            'tanggal.prohibits' => 'Pilih tanggal atau bulan laporan, tidak keduanya.',
            'tanggal.date_format' => 'Tanggal laporan tidak valid.',
            'bulan.date_format' => 'Bulan laporan tidak valid.',
        ];
    }

    /**
     * The first day of the period, or null for the latest published day.
     */
    public function from(): ?Carbon
    {
        return match (true) {
            $this->filled('tanggal') => Carbon::createFromFormat('!Y-m-d', $this->input('tanggal')),
            $this->filled('bulan') => Carbon::createFromFormat('!Y-m', $this->input('bulan')),
            default => null,
        };
    }

    /**
     * The last day of the period, or null for the latest published day.
     */
    public function until(): ?Carbon
    {
        return $this->filled('bulan') ? $this->from()->endOfMonth()->startOfDay() : $this->from();
    }
}
