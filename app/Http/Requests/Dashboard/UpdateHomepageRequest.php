<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use App\Rules\PortalLink;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete Beranda document: the homepage's opening, values, services
 * heading, partners, channel map, integrity statement, fees and closing.
 */
class UpdateHomepageRequest extends FormRequest
{
    /**
     * Sections made of a title, its muted continuation and a description.
     *
     * @var array<string, string>
     */
    private const array TITLED_SECTIONS = [
        'layananUtama' => 'Layanan utama',
        'integritas' => 'Integritas',
        'biaya' => 'Biaya',
        'penutup' => 'Penutup',
    ];

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
        $rules = [
            'hero' => ['required', 'array'],
            'hero.eyebrow' => ['nullable', 'string', 'max:120', new PlainText('Baris atas pembuka')],
            'hero.judul' => ['required', 'string', 'max:80', new PlainText('Judul pembuka')],
            'hero.lanjutan' => ['nullable', 'string', 'max:80', new PlainText('Lanjutan judul pembuka')],
            'hero.deskripsi' => ['required', 'string', 'max:300', new PlainText('Deskripsi pembuka')],
            'hero.tombolLabel' => ['required', 'string', 'max:40', new PlainText('Label tombol')],
            'hero.tombolUrl' => ['nullable', 'string', 'max:300', new PortalLink('Tautan tombol')],
            'hero.catatan' => ['nullable', 'string', 'max:160', new PlainText('Catatan pembuka')],
            'nilai' => ['present', 'list', 'max:6'],
            'nilai.*' => ['required', 'array'],
            'nilai.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'nilai.*.judul' => ['required', 'string', 'max:60', new PlainText('Judul nilai')],
            'nilai.*.deskripsi' => ['nullable', 'string', 'max:200', new PlainText('Deskripsi nilai')],
            'mitra' => ['present', 'list', 'max:12'],
            'mitra.*' => ['required', 'array'],
            'mitra.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'mitra.*.singkatan' => ['nullable', 'string', 'max:3', new PlainText('Singkatan mitra')],
            'mitra.*.nama' => ['required', 'string', 'max:60', new PlainText('Nama mitra')],
            'kanal' => ['present', 'list', 'max:8'],
            'kanal.*' => ['required', 'array'],
            'kanal.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'kanal.*.kelompok' => ['required', 'string', 'max:40', new PlainText('Kelompok kanal')],
            'kanal.*.item' => ['present', 'list', 'max:8'],
            'kanal.*.item.*' => ['nullable', 'string', 'max:60', new PlainText('Kanal')],
            'biaya.catatanNotis' => ['nullable', 'string', 'max:200', new PlainText('Catatan notis')],
            'biaya.wajibDibawa' => ['nullable', 'string', 'max:200', new PlainText('Wajib dibawa')],
            'biaya.jaminan' => ['present', 'list', 'max:6'],
            'biaya.jaminan.*' => ['nullable', 'string', 'max:60', new PlainText('Jaminan')],
        ];

        foreach (self::TITLED_SECTIONS as $section => $label) {
            $label = strtolower($label);
            $rules[$section] = ['required', 'array'];
            $rules["{$section}.judul"] = ['required', 'string', 'max:80', new PlainText("Judul {$label}")];
            $rules["{$section}.lanjutan"] = ['nullable', 'string', 'max:80', new PlainText("Lanjutan judul {$label}")];
            $rules["{$section}.deskripsi"] = ['nullable', 'string', 'max:300', new PlainText("Deskripsi {$label}")];
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
            'list' => ':Attribute tidak valid.',
            'present' => ':Attribute wajib dikirim.',
            'max' => ':Attribute maksimal :max karakter.',
            'distinct' => ':Attribute tercantum dua kali.',
            'nilai.max' => 'Maksimal :max nilai utama.',
            'mitra.max' => 'Maksimal :max mitra.',
            'kanal.max' => 'Maksimal :max kelompok kanal.',
            'kanal.*.item.max' => 'Maksimal :max kanal per kelompok.',
            'biaya.jaminan.max' => 'Maksimal :max jaminan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'hero' => 'pembuka',
            'hero.eyebrow' => 'baris atas pembuka',
            'hero.judul' => 'judul pembuka',
            'hero.lanjutan' => 'lanjutan judul pembuka',
            'hero.deskripsi' => 'deskripsi pembuka',
            'hero.tombolLabel' => 'label tombol',
            'hero.tombolUrl' => 'tautan tombol',
            'hero.catatan' => 'catatan pembuka',
            'nilai' => 'nilai utama',
            'nilai.*' => 'nilai utama',
            'nilai.*.id' => 'nilai utama',
            'nilai.*.judul' => 'judul nilai',
            'nilai.*.deskripsi' => 'deskripsi nilai',
            'mitra' => 'daftar mitra',
            'mitra.*' => 'mitra',
            'mitra.*.id' => 'mitra',
            'mitra.*.singkatan' => 'singkatan mitra',
            'mitra.*.nama' => 'nama mitra',
            'kanal' => 'peta kanal',
            'kanal.*' => 'kelompok kanal',
            'kanal.*.id' => 'kelompok kanal',
            'kanal.*.kelompok' => 'nama kelompok',
            'kanal.*.item' => 'daftar kanal',
            'kanal.*.item.*' => 'kanal',
            'biaya.catatanNotis' => 'catatan notis',
            'biaya.wajibDibawa' => 'wajib dibawa',
            'biaya.jaminan' => 'daftar jaminan',
            'biaya.jaminan.*' => 'jaminan',
        ];

        foreach (self::TITLED_SECTIONS as $section => $label) {
            $attributes[$section] = 'bagian '.strtolower($label);
            $attributes["{$section}.judul"] = 'judul '.strtolower($label);
            $attributes["{$section}.lanjutan"] = 'lanjutan judul '.strtolower($label);
            $attributes["{$section}.deskripsi"] = 'deskripsi '.strtolower($label);
        }

        return $attributes;
    }

    /**
     * The document as it is stored, with empty optional fields as empty
     * strings and empty list entries dropped.
     *
     * @return array<string, mixed>
     */
    public function homepage(): array
    {
        $text = fn (string $key): string => $this->validated($key) ?? '';
        $list = fn (array $items): array => array_values(array_filter($items, fn (?string $item): bool => $item !== null));
        $titled = fn (string $section): array => [
            'judul' => $this->validated("{$section}.judul"),
            'lanjutan' => $text("{$section}.lanjutan"),
            'deskripsi' => $text("{$section}.deskripsi"),
        ];

        return [
            'hero' => [
                'eyebrow' => $text('hero.eyebrow'),
                'judul' => $this->validated('hero.judul'),
                'lanjutan' => $text('hero.lanjutan'),
                'deskripsi' => $this->validated('hero.deskripsi'),
                'tombolLabel' => $this->validated('hero.tombolLabel'),
                'tombolUrl' => $text('hero.tombolUrl'),
                'catatan' => $text('hero.catatan'),
            ],
            'nilai' => array_map(fn (array $value): array => [
                'id' => $value['id'],
                'judul' => $value['judul'],
                'deskripsi' => $value['deskripsi'] ?? '',
            ], $this->validated('nilai')),
            'layananUtama' => $titled('layananUtama'),
            'mitra' => array_map(fn (array $partner): array => [
                'id' => $partner['id'],
                'singkatan' => $partner['singkatan'] ?? '',
                'nama' => $partner['nama'],
            ], $this->validated('mitra')),
            'kanal' => array_map(fn (array $group): array => [
                'id' => $group['id'],
                'kelompok' => $group['kelompok'],
                'item' => $list($group['item']),
            ], $this->validated('kanal')),
            'integritas' => $titled('integritas'),
            'biaya' => [
                ...$titled('biaya'),
                'catatanNotis' => $text('biaya.catatanNotis'),
                'wajibDibawa' => $text('biaya.wajibDibawa'),
                'jaminan' => $list($this->validated('biaya.jaminan')),
            ],
            'penutup' => $titled('penutup'),
        ];
    }
}
