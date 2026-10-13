<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\DashboardModule;
use App\Models\UpdateStandard;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new update standard (D-025); the server sets the order.
 */
class StoreUpdateStandardRequest extends FormRequest
{
    /**
     * The request's fields and the columns they are stored in.
     *
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'konten' => 'konten',
        'modul' => 'modul',
        'unit' => 'unit',
        'frekuensi' => 'frekuensi',
        'intervalHari' => 'interval_hari',
    ];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through UpdateStandardPolicy.
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
        $text = fn (string $label): array => ['required', 'string', 'max:'.UpdateStandard::MAX_TEXT_LENGTH, new PlainText($label)];

        return [
            'konten' => $text('Konten'),
            'modul' => ['required', 'string', Rule::enum(DashboardModule::class)->only(UpdateStandard::MONITORED_MODULES)],
            'unit' => $text('Penanggung jawab'),
            'frekuensi' => $text('Frekuensi'),
            'intervalHari' => ['required', 'integer', 'between:1,'.UpdateStandard::MAX_INTERVAL_DAYS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'konten.required' => 'Nama konten wajib diisi.',
            'konten.string' => 'Nama konten tidak valid.',
            'konten.max' => 'Nama konten maksimal :max karakter.',
            'modul.required' => 'Pilih modul yang dipantau.',
            'modul.string' => 'Modul yang dipantau tidak valid.',
            'modul.enum' => 'Modul ini tidak dapat dipantau.',
            'unit.required' => 'Penanggung jawab wajib diisi.',
            'unit.string' => 'Penanggung jawab tidak valid.',
            'unit.max' => 'Penanggung jawab maksimal :max karakter.',
            'frekuensi.required' => 'Frekuensi wajib diisi.',
            'frekuensi.string' => 'Frekuensi tidak valid.',
            'frekuensi.max' => 'Frekuensi maksimal :max karakter.',
            'intervalHari.required' => 'Batas hari wajib diisi.',
            'intervalHari.integer' => 'Batas hari harus bilangan bulat.',
            'intervalHari.between' => 'Batas hari antara :min dan :max.',
        ];
    }

    /**
     * The sent fields by column.
     *
     * @return array<string, mixed>
     */
    public function content(): array
    {
        $validated = $this->validated();
        $content = [];

        foreach (self::COLUMNS as $field => $column) {
            if (array_key_exists($field, $validated)) {
                $content[$column] = $validated[$field];
            }
        }

        return $content;
    }
}
