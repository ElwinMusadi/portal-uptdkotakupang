<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\ThemeBackground;
use App\Enums\ThemeCorners;
use App\Enums\ThemeFont;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The complete theme document (D-057): the portal part and the dashboard
 * part, each from closed lists except the accent, a "#RRGGBB" colour. No
 * free text reaches the generated stylesheets.
 */
class UpdateThemeRequest extends FormRequest
{
    /**
     * The two parts of the document and their names in messages.
     *
     * @var array<string, string>
     */
    private const array SECTIONS = ['portal' => 'portal', 'dashboard' => 'dashboard'];

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
        $textFonts = array_map(fn (ThemeFont $font): string => $font->value, ThemeFont::textFonts());
        $rules = [];

        foreach (array_keys(self::SECTIONS) as $section) {
            $rules += [
                $section => ['required', 'array'],
                "{$section}.aksen" => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}\z/'],
                "{$section}.latar" => ['required', 'string', Rule::in(array_column(ThemeBackground::cases(), 'value'))],
                "{$section}.hurufTeks" => ['required', 'string', Rule::in($textFonts)],
                "{$section}.hurufJudul" => ['required', 'string', Rule::in(['sama', ...array_column(ThemeFont::cases(), 'value')])],
                "{$section}.sudut" => ['required', 'string', Rule::in(array_column(ThemeCorners::cases(), 'value'))],
            ];
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
            'array' => ':Attribute tidak valid.',
            'string' => ':Attribute tidak valid.',
            'regex' => ':Attribute harus berupa kode warna #RRGGBB.',
            'in' => ':Attribute tidak tersedia.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (self::SECTIONS as $section => $name) {
            $attributes += [
                $section => "tema {$name}",
                "{$section}.aksen" => "warna utama {$name}",
                "{$section}.latar" => "nuansa latar {$name}",
                "{$section}.hurufTeks" => "huruf teks {$name}",
                "{$section}.hurufJudul" => "huruf judul {$name}",
                "{$section}.sudut" => "kelengkungan sudut {$name}",
            ];
        }

        return $attributes;
    }

    /**
     * The validated document, with the accents in capitals.
     *
     * @return array{portal: array{aksen: string, latar: string, hurufTeks: string, hurufJudul: string, sudut: string}, dashboard: array{aksen: string, latar: string, hurufTeks: string, hurufJudul: string, sudut: string}}
     */
    public function theme(): array
    {
        $section = fn (string $name): array => [
            'aksen' => $this->string("{$name}.aksen")->upper()->value(),
            'latar' => $this->string("{$name}.latar")->value(),
            'hurufTeks' => $this->string("{$name}.hurufTeks")->value(),
            'hurufJudul' => $this->string("{$name}.hurufJudul")->value(),
            'sudut' => $this->string("{$name}.sudut")->value(),
        ];

        return ['portal' => $section('portal'), 'dashboard' => $section('dashboard')];
    }
}
