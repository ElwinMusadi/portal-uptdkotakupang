<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\Upload;
use App\Rules\PlainText;
use App\Rules\UploadUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete Visi & Misi document: the vision and its source, the
 * missions, PINTU's contributions, the core values (BerAKHLAK) and the
 * service pledge. The signed pledge is a PDF uploaded to PINTU for Visi &
 * Misi (D-028), or empty; its name, size and type are taken from the upload.
 */
class UpdateVisionMissionRequest extends FormRequest
{
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
        return [
            'sumberVisi' => ['nullable', 'string', 'max:120', new PlainText('Sumber')],
            'visi' => ['required', 'string', 'max:300', new PlainText('Visi')],
            'misi' => ['present', 'list', 'max:10'],
            'misi.*' => ['required', 'array'],
            'misi.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'misi.*.teks' => ['required', 'string', 'max:400', new PlainText('Misi')],
            'misi.*.kataKunci' => ['nullable', 'string', 'max:40', new PlainText('Kata kunci')],
            'kontribusi' => ['present', 'list', 'max:6'],
            'kontribusi.*' => ['required', 'array'],
            'kontribusi.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'kontribusi.*.misi' => ['nullable', 'string', 'max:60', new PlainText('Misi')],
            'kontribusi.*.judul' => ['required', 'string', 'max:120', new PlainText('Judul')],
            'kontribusi.*.deskripsi' => ['nullable', 'string', 'max:400', new PlainText('Penjelasan')],
            'nilai' => ['present', 'list', 'max:10'],
            'nilai.*' => ['required', 'array'],
            'nilai.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'nilai.*.huruf' => ['nullable', 'string', 'max:2', new PlainText('Huruf')],
            'nilai.*.judul' => ['required', 'string', 'max:60', new PlainText('Nilai')],
            'nilai.*.deskripsi' => ['nullable', 'string', 'max:200', new PlainText('Perilaku')],
            'maklumat' => ['required', 'array'],
            'maklumat.teks' => ['required', 'string', 'max:600', new PlainText('Isi maklumat')],
            'maklumat.penandatangan' => ['nullable', 'string', 'max:120', new PlainText('Penandatangan')],
            'maklumat.berkas' => ['nullable', 'array'],
            'maklumat.berkas.url' => ['required_with:maklumat.berkas', 'string', new UploadUrl(UploadTarget::VisiMisi, 'Berkas maklumat harus diunggah lewat dashboard.')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sumberVisi.string' => 'Sumber tidak valid.',
            'sumberVisi.max' => 'Sumber maksimal :max karakter.',
            'visi.required' => 'Visi wajib diisi.',
            'visi.string' => 'Visi tidak valid.',
            'visi.max' => 'Visi maksimal :max karakter.',
            'misi.present' => 'Daftar misi wajib dikirim.',
            'misi.list' => 'Daftar misi tidak valid.',
            'misi.max' => 'Maksimal :max misi.',
            'misi.*.required' => 'Misi tidak valid.',
            'misi.*.array' => 'Misi tidak valid.',
            'misi.*.id.required' => 'Misi tidak valid.',
            'misi.*.id.string' => 'Misi tidak valid.',
            'misi.*.id.max' => 'Misi tidak valid.',
            'misi.*.id.distinct' => 'Misi tercantum dua kali.',
            'misi.*.teks.required' => 'Misi wajib diisi.',
            'misi.*.teks.string' => 'Misi tidak valid.',
            'misi.*.teks.max' => 'Misi maksimal :max karakter.',
            'misi.*.kataKunci.string' => 'Kata kunci tidak valid.',
            'misi.*.kataKunci.max' => 'Kata kunci maksimal :max karakter.',
            'kontribusi.present' => 'Daftar kontribusi wajib dikirim.',
            'kontribusi.list' => 'Daftar kontribusi tidak valid.',
            'kontribusi.max' => 'Maksimal :max kontribusi.',
            'kontribusi.*.required' => 'Kontribusi tidak valid.',
            'kontribusi.*.array' => 'Kontribusi tidak valid.',
            'kontribusi.*.id.required' => 'Kontribusi tidak valid.',
            'kontribusi.*.id.string' => 'Kontribusi tidak valid.',
            'kontribusi.*.id.max' => 'Kontribusi tidak valid.',
            'kontribusi.*.id.distinct' => 'Kontribusi tercantum dua kali.',
            'kontribusi.*.misi.string' => 'Misi tidak valid.',
            'kontribusi.*.misi.max' => 'Misi maksimal :max karakter.',
            'kontribusi.*.judul.required' => 'Judul wajib diisi.',
            'kontribusi.*.judul.string' => 'Judul tidak valid.',
            'kontribusi.*.judul.max' => 'Judul maksimal :max karakter.',
            'kontribusi.*.deskripsi.string' => 'Penjelasan tidak valid.',
            'kontribusi.*.deskripsi.max' => 'Penjelasan maksimal :max karakter.',
            'nilai.present' => 'Daftar nilai wajib dikirim.',
            'nilai.list' => 'Daftar nilai tidak valid.',
            'nilai.max' => 'Maksimal :max nilai.',
            'nilai.*.required' => 'Nilai tidak valid.',
            'nilai.*.array' => 'Nilai tidak valid.',
            'nilai.*.id.required' => 'Nilai tidak valid.',
            'nilai.*.id.string' => 'Nilai tidak valid.',
            'nilai.*.id.max' => 'Nilai tidak valid.',
            'nilai.*.id.distinct' => 'Nilai tercantum dua kali.',
            'nilai.*.huruf.string' => 'Huruf tidak valid.',
            'nilai.*.huruf.max' => 'Satu atau dua huruf.',
            'nilai.*.judul.required' => 'Nilai wajib diisi.',
            'nilai.*.judul.string' => 'Nilai tidak valid.',
            'nilai.*.judul.max' => 'Nilai maksimal :max karakter.',
            'nilai.*.deskripsi.string' => 'Perilaku tidak valid.',
            'nilai.*.deskripsi.max' => 'Perilaku maksimal :max karakter.',
            'maklumat.required' => 'Maklumat wajib dikirim.',
            'maklumat.array' => 'Maklumat tidak valid.',
            'maklumat.teks.required' => 'Isi maklumat wajib diisi.',
            'maklumat.teks.string' => 'Isi maklumat tidak valid.',
            'maklumat.teks.max' => 'Isi maklumat maksimal :max karakter.',
            'maklumat.penandatangan.string' => 'Penandatangan tidak valid.',
            'maklumat.penandatangan.max' => 'Penandatangan maksimal :max karakter.',
            'maklumat.berkas.array' => 'Berkas maklumat tidak valid.',
            'maklumat.berkas.url.required_with' => 'Berkas maklumat harus diunggah lewat dashboard.',
            'maklumat.berkas.url.string' => 'Berkas maklumat harus diunggah lewat dashboard.',
        ];
    }

    /**
     * The document as it is stored, with empty optional fields as empty
     * strings and the pledge file as its upload describes it, or null.
     *
     * @return array{sumberVisi: string, visi: string, misi: list<array{id: string, teks: string, kataKunci: string}>, kontribusi: list<array{id: string, misi: string, judul: string, deskripsi: string}>, nilai: list<array{id: string, huruf: string, judul: string, deskripsi: string}>, maklumat: array{teks: string, penandatangan: string, berkas: ?array{nama: string, ukuran: int, tipe: string, url: string}}}
     */
    public function visionMission(): array
    {
        return [
            'sumberVisi' => $this->validated('sumberVisi') ?? '',
            'visi' => $this->validated('visi'),
            'misi' => array_map(fn (array $mission): array => [
                'id' => $mission['id'],
                'teks' => $mission['teks'],
                'kataKunci' => $mission['kataKunci'] ?? '',
            ], $this->validated('misi')),
            'kontribusi' => array_map(fn (array $contribution): array => [
                'id' => $contribution['id'],
                'misi' => $contribution['misi'] ?? '',
                'judul' => $contribution['judul'],
                'deskripsi' => $contribution['deskripsi'] ?? '',
            ], $this->validated('kontribusi')),
            'nilai' => array_map(fn (array $value): array => [
                'id' => $value['id'],
                'huruf' => $value['huruf'] ?? '',
                'judul' => $value['judul'],
                'deskripsi' => $value['deskripsi'] ?? '',
            ], $this->validated('nilai')),
            'maklumat' => [
                'teks' => $this->validated('maklumat.teks'),
                'penandatangan' => $this->validated('maklumat.penandatangan') ?? '',
                'berkas' => $this->pledgeFile(),
            ],
        ];
    }

    /**
     * The pledge file as its upload describes it, whatever name, size or
     * type the request claims.
     *
     * @return ?array{nama: string, ukuran: int, tipe: string, url: string}
     */
    private function pledgeFile(): ?array
    {
        $url = $this->validated('maklumat.berkas.url');
        $upload = is_string($url) ? Upload::fromUrl($url, UploadTarget::VisiMisi) : null;

        return $upload === null ? null : [
            'nama' => $upload->nama,
            'ukuran' => $upload->ukuran,
            'tipe' => $upload->tipe,
            'url' => $upload->url(),
        ];
    }
}
