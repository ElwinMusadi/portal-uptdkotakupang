<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\QueueStage;
use App\Models\QueueService;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An administrator's counter for Antrean Online (D-051): its name, the
 * calling stage it serves, the services "call next" takes there (all when
 * none is chosen), how the TV display reads its name, and whether it is in
 * use.
 */
class SaveQueueCounterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; the route
     * checks QueueCounterPolicy.
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
            'nama' => ['required', 'string', 'max:60', new PlainText('Nama loket')],
            'tahap' => ['required', 'string', Rule::in(array_map(
                fn (QueueStage $stage): string => $stage->value,
                array_filter(QueueStage::cases(), fn (QueueStage $stage): bool => $stage->isCalled()),
            ))],
            'layanan' => ['present', 'list'],
            'layanan.*' => ['string', 'distinct', Rule::exists(QueueService::class, 'kode')],
            'namaSuara' => ['required', 'string', 'max:80', new PlainText('Nama yang dibacakan')],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Tulis nama loket.',
            'nama.string' => 'Tulis nama loket.',
            'nama.max' => 'Nama loket paling banyak 60 karakter.',
            'tahap.required' => 'Pilih tahap loket.',
            'tahap.string' => 'Pilih tahap loket.',
            'tahap.in' => 'Loket hanya untuk Loket 1 – Pendaftaran, Loket Kwitansi, atau Loket 2 – Penyerahan.',
            'layanan.present' => 'Layanan loket tidak terkirim.',
            'layanan.list' => 'Layanan loket tidak valid.',
            'layanan.*.distinct' => 'Setiap layanan hanya dipilih sekali.',
            'layanan.*' => 'Layanan ini tidak ada.',
            'namaSuara.required' => 'Tulis nama loket yang dibacakan di layar.',
            'namaSuara.string' => 'Tulis nama loket yang dibacakan di layar.',
            'namaSuara.max' => 'Nama yang dibacakan paling banyak 80 karakter.',
            'aktif.required' => 'Pilih apakah loket dipakai.',
            'aktif.boolean' => 'Pilih apakah loket dipakai.',
        ];
    }

    /**
     * The counter's values, its services in desk order.
     *
     * @return array{nama: string, tahap: string, layanan: list<string>, nama_suara: string, aktif: bool}
     */
    public function counter(): array
    {
        $chosen = $this->validated('layanan');

        return [
            'nama' => $this->validated('nama'),
            'tahap' => $this->validated('tahap'),
            'layanan' => QueueService::query()->ordered()->whereIn('kode', $chosen)->pluck('kode')->all(),
            'nama_suara' => $this->validated('namaSuara'),
            'aktif' => $this->boolean('aktif'),
        ];
    }
}
