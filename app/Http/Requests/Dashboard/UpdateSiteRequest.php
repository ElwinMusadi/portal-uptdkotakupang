<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use App\Rules\PortalLink;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete Pengaturan Situs document: the portal's identity and the
 * announcement bar on the Informasi page (D-026).
 */
class UpdateSiteRequest extends FormRequest
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
            'namaPortal' => ['required', 'string', 'max:40', new PlainText('Nama portal')],
            'kepanjangan' => ['nullable', 'string', 'max:80', new PlainText('Kepanjangan')],
            'namaInstansi' => ['required', 'string', 'max:120', new PlainText('Nama instansi')],
            'namaSingkat' => ['nullable', 'string', 'max:60', new PlainText('Dikenal sebagai')],
            'induk' => ['nullable', 'string', 'max:160', new PlainText('Instansi induk')],
            'deskripsi' => ['nullable', 'string', 'max:300', new PlainText('Deskripsi singkat')],
            'urlPortal' => ['required', 'string', 'max:300', new PortalLink('Alamat portal publik')],
            'hakCipta' => ['nullable', 'string', 'max:160', new PlainText('Teks hak cipta')],
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
            'max' => ':Attribute maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'namaPortal' => 'nama portal',
            'kepanjangan' => 'kepanjangan',
            'namaInstansi' => 'nama instansi',
            'namaSingkat' => 'nama yang dikenal',
            'induk' => 'instansi induk',
            'deskripsi' => 'deskripsi singkat',
            'urlPortal' => 'alamat portal publik',
            'hakCipta' => 'teks hak cipta',
        ];
    }

    /**
     * The document as it is stored, with empty optional fields as empty
     * strings. An announcement bar sent by a dashboard opened before D-082 is
     * not kept: it belongs to Berita & Pengumuman.
     *
     * @return array{namaPortal: string, kepanjangan: string, namaInstansi: string, namaSingkat: string, induk: string, deskripsi: string, urlPortal: string, hakCipta: string}
     */
    public function site(): array
    {
        $text = fn (string $key): string => $this->validated($key) ?? '';

        return [
            'namaPortal' => $this->validated('namaPortal'),
            'kepanjangan' => $text('kepanjangan'),
            'namaInstansi' => $this->validated('namaInstansi'),
            'namaSingkat' => $text('namaSingkat'),
            'induk' => $text('induk'),
            'deskripsi' => $text('deskripsi'),
            'urlPortal' => $this->validated('urlPortal'),
            'hakCipta' => $text('hakCipta'),
        ];
    }
}
