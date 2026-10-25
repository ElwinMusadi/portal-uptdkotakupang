<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete Jadwal page document (D-080): the page's opening, the heading
 * of the office's opening hours and the introduction to the services outside
 * the office, each with a small line above, a title and its muted
 * continuation. Limits are Beranda's (UpdateHomepageRequest).
 */
class UpdateSchedulePageRequest extends FormRequest
{
    /**
     * The sections, their name in messages and their fields beyond the
     * small line, the title and its continuation.
     *
     * @var array<string, array{string, list<string>}>
     */
    private const array SECTIONS = [
        'pembuka' => ['pembuka', ['deskripsi']],
        'jam' => ['jam loket', []],
        'luarKantor' => ['layanan di luar kantor', ['deskripsi', 'catatan']],
    ];

    /**
     * The longest text of each field.
     *
     * @var array<string, int>
     */
    private const array LENGTHS = ['eyebrow' => 120, 'judul' => 80, 'lanjutan' => 80, 'deskripsi' => 300, 'catatan' => 160];

    /**
     * The fields as messages name them.
     *
     * @var array<string, string>
     */
    private const array NAMES = ['eyebrow' => 'teks kecil', 'judul' => 'judul', 'lanjutan' => 'lanjutan judul', 'deskripsi' => 'paragraf', 'catatan' => 'catatan'];

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
        $rules = [];
        foreach (self::SECTIONS as $section => [$name, $extra]) {
            $rules[$section] = ['required', 'array'];
            foreach (['eyebrow', 'judul', 'lanjutan', ...$extra] as $field) {
                $rules["{$section}.{$field}"] = [
                    $field === 'judul' ? 'required' : 'nullable',
                    'string',
                    'max:'.self::LENGTHS[$field],
                    new PlainText(ucfirst(self::NAMES[$field])." {$name}"),
                ];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute tidak valid.',
            'array' => ':Attribute tidak valid.',
            'max' => ':Attribute maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];
        foreach (self::SECTIONS as $section => [$name, $extra]) {
            $attributes[$section] = "bagian {$name}";
            foreach (['eyebrow', 'judul', 'lanjutan', ...$extra] as $field) {
                $attributes["{$section}.{$field}"] = self::NAMES[$field]." {$name}";
            }
        }

        return $attributes;
    }

    /**
     * The document as it is stored, with empty optional fields as empty
     * strings and nothing it does not hold.
     *
     * @return array{pembuka: array{eyebrow: string, judul: string, lanjutan: string, deskripsi: string}, jam: array{eyebrow: string, judul: string, lanjutan: string}, luarKantor: array{eyebrow: string, judul: string, lanjutan: string, deskripsi: string, catatan: string}}
     */
    public function schedulePage(): array
    {
        $document = [];
        foreach (self::SECTIONS as $section => [, $extra]) {
            foreach (['eyebrow', 'judul', 'lanjutan', ...$extra] as $field) {
                $document[$section][$field] = (string) ($this->validated("{$section}.{$field}") ?? '');
            }
        }

        /** @var array{pembuka: array{eyebrow: string, judul: string, lanjutan: string, deskripsi: string}, jam: array{eyebrow: string, judul: string, lanjutan: string}, luarKantor: array{eyebrow: string, judul: string, lanjutan: string, deskripsi: string, catatan: string}} $document */
        return $document;
    }
}
