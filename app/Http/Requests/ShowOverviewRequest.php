<?php

namespace App\Http\Requests;

use App\Http\Requests\Dashboard\ShowRevenueReportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The period of the Ikhtisar page (D-099): one of the periods counted back
 * from the latest STS date, or a range of dates of at most a year. Nothing
 * else is accepted, not the dashboard report's filters nor a token in the
 * address.
 */
class ShowOverviewRequest extends FormRequest
{
    /**
     * The periods counted back from the latest STS date.
     *
     * @var list<string>
     */
    public const array PERIODS = ['hari-terakhir', 'bulan-ini', 'bulan-lalu', 'tahun-ini'];

    /**
     * The parameters the page may send.
     *
     * @var list<string>
     */
    private const array PARAMETERS = ['periode', 'dari', 'sampai'];

    /**
     * Determine if the user is authorized to make this request; the route
     * lets through only the holder of a link (AuthenticateOverviewLink).
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
            'periode' => ['sometimes', 'string', 'in:'.implode(',', self::PERIODS), 'prohibits:dari,sampai'],
            'dari' => ['required_with:sampai', 'date_format:Y-m-d'],
            'sampai' => ['required_with:dari', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ];
    }

    /**
     * Unknown parameters are refused rather than ignored, and a range covers
     * at most a year, like the dashboard's report.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unknown = array_diff(array_keys($this->query()), self::PARAMETERS);
                if ($unknown !== []) {
                    $validator->errors()->add('query', 'Parameter kueri tidak didukung.');
                }
            },
            function (Validator $validator): void {
                if ($this->from() !== null && ! $validator->errors()->hasAny(['dari', 'sampai'])
                    && $this->from()->diffInDays($this->until()) + 1 > ShowRevenueReportRequest::MAX_DAYS) {
                    $validator->errors()->add('sampai', 'Rentang laporan paling lama satu tahun.');
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
            // The first key matching a rule wins, so the specific one comes first
            'periode.prohibits' => 'Pilih periode atau rentang tanggal, tidak keduanya.',
            'periode.*' => 'Pilihan periode tidak dikenal.',
            'dari.required_with' => 'Pilih tanggal awal laporan.',
            'dari.date_format' => 'Tanggal awal laporan tidak valid.',
            'sampai.required_with' => 'Pilih tanggal akhir laporan.',
            'sampai.date_format' => 'Tanggal akhir laporan tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }

    /**
     * The period counted back from the latest STS date, or null for a range.
     */
    public function period(): ?string
    {
        return $this->from() === null ? $this->input('periode', 'bulan-ini') : null;
    }

    /**
     * The first day of the range, or null when a period is chosen instead.
     */
    public function from(): ?Carbon
    {
        return $this->filled('dari') ? Carbon::createFromFormat('!Y-m-d', $this->input('dari')) : null;
    }

    /**
     * The last day of the range, or null when a period is chosen instead.
     */
    public function until(): ?Carbon
    {
        return $this->filled('sampai') ? Carbon::createFromFormat('!Y-m-d', $this->input('sampai')) : null;
    }
}
