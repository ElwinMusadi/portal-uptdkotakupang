<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\QueueStage;
use App\Enums\QueueTicketStatus;
use App\Http\Requests\Dashboard\Concerns\ReadsQueueTicketQuery;
use App\Models\QueueService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A search of the queue archive (D-051 slice 4), e.g. for documents or a
 * ticket gone astray: what was typed or scanned (QR, number, or any part of
 * the plate number), the print dates, the service, the status and the
 * stage, each optional.
 */
class ArchiveQueueTicketsRequest extends FormRequest
{
    use ReadsQueueTicketQuery;

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
            'q' => ['sometimes', 'nullable', 'string', 'max:300'],
            'dari' => ['sometimes', 'date_format:Y-m-d'],
            'sampai' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'layanan' => ['sometimes', 'string', Rule::exists(QueueService::class, 'kode')],
            'status' => ['sometimes', Rule::enum(QueueTicketStatus::class)],
            'tahap' => ['sometimes', Rule::enum(QueueStage::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.string' => 'Ketik nomor antrean atau nomor polisi, atau pindai QR.',
            'q.max' => 'Teks pencarian terlalu panjang.',
            'dari.date_format' => 'Tanggal awal tidak valid.',
            'sampai.date_format' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'layanan.*' => 'Layanan ini tidak ada.',
            'status.*' => 'Status ini tidak ada.',
            'tahap.*' => 'Tahap ini tidak ada.',
        ];
    }

    /**
     * Whether anything was typed or scanned.
     */
    public function hasTyped(): bool
    {
        return $this->validated('q') !== null;
    }
}
