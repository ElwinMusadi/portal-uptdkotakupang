<?php

namespace App\Http\Requests\Dashboard;

use App\Models\MobileServiceStop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * The order of the service outside the office on the homepage board for one
 * date from today (D-053). An empty list puts the date back in time order.
 */
class ArrangeServiceBoardRequest extends FormRequest
{
    /**
     * The time zone the schedule is kept in.
     */
    private const string TIME_ZONE = 'Asia/Makassar';

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through MobileServiceStopPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The date comes from the address.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [...$this->all(), 'tanggal' => $this->route('tanggal')];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.Carbon::now(self::TIME_ZONE)->toDateString()],
            'ids' => ['present', 'list', 'max:200'],
            'ids.*' => ['string', 'ulid', 'distinct'],
        ];
    }

    /**
     * Every listed entry must still exist and be served on the date; one
     * query checks them all.
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
                $date = Carbon::parse($this->route('tanggal'));
                $served = MobileServiceStop::query()->whereKey($ids)->get()
                    ->filter(fn (MobileServiceStop $stop): bool => $stop->occursOn($date));

                if ($served->count() !== count($ids)) {
                    $validator->errors()->add('ids', 'Urutan tidak cocok dengan jadwal terbaru. Muat ulang halaman lalu coba lagi.');
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
            'tanggal.required' => 'Pilih tanggal yang diurutkan.',
            'tanggal.date_format' => 'Tanggal tidak valid.',
            'tanggal.after_or_equal' => 'Urutan papan hanya bisa diatur untuk hari ini dan hari berikutnya.',
            'ids.present' => 'Daftar urutan tidak dikirim.',
            'ids.list' => 'Daftar urutan tidak valid.',
            'ids.max' => 'Daftar urutan terlalu panjang.',
            'ids.*.string' => 'Daftar urutan tidak valid.',
            'ids.*.ulid' => 'Daftar urutan tidak valid.',
            'ids.*.distinct' => 'Daftar urutan berisi entri ganda.',
        ];
    }

    /**
     * The date as "YYYY-MM-DD".
     */
    public function day(): string
    {
        return $this->validated('tanggal');
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return $this->validated('ids');
    }
}
