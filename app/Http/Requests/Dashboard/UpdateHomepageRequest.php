<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\Setting;
use App\Rules\PlainText;
use App\Rules\PortalLink;
use App\Rules\UploadUrl;
use App\Rules\YoutubeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
     * Prepare the data for validation: channels sent as plain text, as a
     * dashboard opened before channel links (D-038) still sends them, become
     * channels without a link.
     */
    protected function prepareForValidation(): void
    {
        $groups = $this->input('kanal');

        if (! is_array($groups)) {
            return;
        }

        $this->merge(['kanal' => array_map(fn (mixed $group): mixed => is_array($group) && is_array($group['item'] ?? null)
            ? [...$group, 'item' => array_map(fn (mixed $channel): mixed => is_string($channel) || $channel === null ? ['teks' => $channel, 'tautan' => null] : $channel, $group['item'])]
            : $group, $groups)]);
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
            'hero.kataBergilir' => ['sometimes', 'list', 'max:8'],
            'hero.kataBergilir.*' => ['required', 'string', 'max:24', 'distinct:ignore_case', new PlainText('Kata bergilir')],
            'hero.lanjutan' => ['nullable', 'string', 'max:80', new PlainText('Lanjutan judul pembuka')],
            'hero.deskripsi' => ['required', 'string', 'max:300', new PlainText('Deskripsi pembuka')],
            'hero.tombolLabel' => ['required', 'string', 'max:40', new PlainText('Label tombol')],
            'hero.tombolUrl' => ['nullable', 'string', 'max:300', new PortalLink('Tautan tombol')],
            'hero.catatan' => ['nullable', 'string', 'max:160', new PlainText('Catatan pembuka')],
            'video' => ['sometimes', 'array'],
            'video.url' => ['nullable', 'string', 'max:300', new YoutubeUrl('Tautan video')],
            'video.label' => ['nullable', 'required_with:video.url', 'string', 'max:40', new PlainText('Label tombol video')],
            'video.durasi' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}(?::\d{2})?\z/'],
            'video.judul' => ['nullable', 'string', 'max:120', new PlainText('Judul video')],
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
            'mitra.*.logo' => ['nullable', 'string', new UploadUrl(UploadTarget::Beranda, 'Logo mitra harus diunggah lewat dashboard.')],
            'kanal' => ['present', 'list', 'max:8'],
            'kanal.*' => ['required', 'array'],
            'kanal.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'kanal.*.kelompok' => ['required', 'string', 'max:40', new PlainText('Kelompok kanal')],
            'kanal.*.item' => ['present', 'list', 'max:8'],
            'kanal.*.item.*' => ['required', 'array'],
            'kanal.*.item.*.teks' => ['nullable', 'required_with:kanal.*.item.*.tautan', 'string', 'max:60', new PlainText('Nama kanal')],
            'kanal.*.item.*.tautan' => ['nullable', 'string', 'max:300', new PortalLink('Tautan kanal')],
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
     * Rotating words need at least two of them, and the title must hold one:
     * the portal's rotation starts and stops on that word (D-045).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $words = $this->input('hero.kataBergilir');

                if (! is_array($words) || $words === [] || $validator->errors()->hasAny(['hero.judul', 'hero.kataBergilir', 'hero.kataBergilir.*'])) {
                    return;
                }

                if (count($words) < 2) {
                    $validator->errors()->add('hero.kataBergilir', 'Isi minimal dua kata bergilir, atau kosongkan daftarnya.');
                } elseif (Setting::rotatingWordIn($this->input('hero.judul'), $words) === null) {
                    $validator->errors()->add('hero.kataBergilir', 'Judul pembuka harus memuat salah satu kata bergilir; di kata itulah putarannya berhenti.');
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
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute tidak valid.',
            'array' => ':Attribute tidak valid.',
            'list' => ':Attribute tidak valid.',
            'present' => ':Attribute wajib dikirim.',
            'max' => ':Attribute maksimal :max karakter.',
            'distinct' => ':Attribute tercantum dua kali.',
            'required_with' => ':Attribute wajib diisi bila tautannya diisi.',
            'hero.kataBergilir.max' => 'Maksimal :max kata bergilir.',
            'video.durasi.regex' => 'Tulis durasi video seperti 2:14 atau 1:02:30.',
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
            'hero.kataBergilir' => 'daftar kata bergilir',
            'hero.kataBergilir.*' => 'kata bergilir',
            'hero.lanjutan' => 'lanjutan judul pembuka',
            'hero.deskripsi' => 'deskripsi pembuka',
            'hero.tombolLabel' => 'label tombol',
            'hero.tombolUrl' => 'tautan tombol',
            'hero.catatan' => 'catatan pembuka',
            'video' => 'video',
            'video.url' => 'tautan video',
            'video.label' => 'label tombol video',
            'video.durasi' => 'durasi video',
            'video.judul' => 'judul video',
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
            'mitra.*.logo' => 'logo mitra',
            'kanal' => 'peta kanal',
            'kanal.*' => 'kelompok kanal',
            'kanal.*.id' => 'kelompok kanal',
            'kanal.*.kelompok' => 'nama kelompok',
            'kanal.*.item' => 'daftar kanal',
            'kanal.*.item.*' => 'kanal',
            'kanal.*.item.*.teks' => 'nama kanal',
            'kanal.*.item.*.tautan' => 'tautan kanal',
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
                // A dashboard opened before the rotating words existed sends none; the saved ones stay
                'kataBergilir' => $this->has('hero.kataBergilir') ? $this->validated('hero.kataBergilir') : Setting::homepage()['hero']['kataBergilir'],
                'lanjutan' => $text('hero.lanjutan'),
                'deskripsi' => $this->validated('hero.deskripsi'),
                'tombolLabel' => $this->validated('hero.tombolLabel'),
                'tombolUrl' => $text('hero.tombolUrl'),
                'catatan' => $text('hero.catatan'),
            ],
            // A dashboard opened before the video existed sends none; the saved one stays
            'video' => $this->has('video') ? [
                'url' => YoutubeUrl::canonical($text('video.url')) ?? '',
                'label' => $text('video.label'),
                'durasi' => $text('video.durasi'),
                'judul' => $text('video.judul'),
            ] : Setting::homepage()['video'],
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
                'logo' => $partner['logo'] ?? null,
            ], $this->validated('mitra')),
            'kanal' => array_map(fn (array $group): array => [
                'id' => $group['id'],
                'kelompok' => $group['kelompok'],
                'item' => array_values(array_filter(
                    array_map(fn (array $channel): array => ['teks' => $channel['teks'] ?? '', 'tautan' => $channel['tautan'] ?? ''], $group['item']),
                    fn (array $channel): bool => $channel['teks'] !== '',
                )),
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
