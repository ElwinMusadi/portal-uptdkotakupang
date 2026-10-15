<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\QueueStage;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An administrator's change to a queue service (D-051): its name, what it
 * covers, its stages and whether numbers can be printed for it. The code
 * letter stays, since printed numbers carry it.
 */
class UpdateQueueServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; the route
     * checks QueueServicePolicy.
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
            'nama' => ['required', 'string', 'max:80', new PlainText('Nama layanan')],
            'cakupan' => ['present', 'nullable', 'string', 'max:200', new PlainText('Cakupan')],
            'tahap' => ['required', 'list'],
            'tahap.*' => ['string', 'distinct', Rule::enum(QueueStage::class)],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Tulis nama layanan.',
            'nama.string' => 'Tulis nama layanan.',
            'nama.max' => 'Nama layanan paling banyak 80 karakter.',
            'cakupan.present' => 'Cakupan layanan tidak terkirim.',
            'cakupan.string' => 'Cakupan layanan tidak valid.',
            'cakupan.max' => 'Cakupan layanan paling banyak 200 karakter.',
            'tahap.required' => 'Pilih sedikitnya satu tahap.',
            'tahap.list' => 'Tahap layanan tidak valid.',
            'tahap.*.distinct' => 'Setiap tahap hanya dipilih sekali.',
            'tahap.*' => 'Tahap layanan tidak dikenal.',
            'aktif.required' => 'Pilih apakah layanan aktif.',
            'aktif.boolean' => 'Pilih apakah layanan aktif.',
        ];
    }

    /**
     * The service's new values, its stages in flow order.
     *
     * @return array{nama: string, cakupan: string, tahap: list<string>, aktif: bool}
     */
    public function service(): array
    {
        return [
            'nama' => $this->validated('nama'),
            'cakupan' => (string) $this->validated('cakupan'),
            'tahap' => array_map(fn (QueueStage $stage): string => $stage->value, QueueStage::inOrder($this->validated('tahap'))),
            'aktif' => $this->boolean('aktif'),
        ];
    }
}
