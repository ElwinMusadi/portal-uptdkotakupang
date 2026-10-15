<?php

namespace App\Http\Requests\Dashboard;

use App\Models\QueueTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The print dates a queue summary covers (D-051 slice 4): one day, or a
 * range of at most 31 days (the owner's decision); today in WITA when
 * omitted.
 */
class ShowQueueSummaryRequest extends FormRequest
{
    /**
     * Longest range, in days.
     */
    public const int MAX_DAYS = 31;

    /**
     * Determine if the user is authorized to make this request; the route
     * checks QueueTicketPolicy.
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
            'dari' => ['required_with:sampai', 'date_format:Y-m-d'],
            'sampai' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:dari'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dari.required_with' => 'Pilih tanggal awal.',
            'dari.date_format' => 'Tanggal awal tidak valid.',
            'sampai.date_format' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && Carbon::parse($this->from())->diffInDays(Carbon::parse($this->until())) >= self::MAX_DAYS) {
                    $validator->errors()->add('sampai', 'Rentang ringkasan paling lama 31 hari.');
                }
            },
        ];
    }

    /**
     * The first print date, as YYYY-MM-DD.
     */
    public function from(): string
    {
        return $this->input('dari') ?? now(QueueTicket::TIME_ZONE)->toDateString();
    }

    /**
     * The last print date, as YYYY-MM-DD.
     */
    public function until(): string
    {
        return $this->input('sampai') ?? $this->from();
    }
}
