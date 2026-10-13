<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete Tentang UPTD document: the page opening, the building photo
 * caption, the unit's position and identity facts, its duties, and the
 * head's welcome. Photos cannot be uploaded to the server yet (D-021), so
 * "foto" and "sambutan.foto" must stay empty.
 */
class UpdateProfileRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:80', new PlainText('Judul')],
            'lanjutan' => ['nullable', 'string', 'max:80', new PlainText('Lanjutan judul')],
            'pengantar' => ['required', 'string', 'max:400', new PlainText('Pengantar')],
            'foto' => ['prohibited'],
            'keteranganFoto' => ['nullable', 'string', 'max:200', new PlainText('Keterangan foto')],
            'kedudukan' => ['required', 'array'],
            'kedudukan.judul' => ['required', 'string', 'max:80', new PlainText('Judul kedudukan')],
            'kedudukan.lanjutan' => ['nullable', 'string', 'max:80', new PlainText('Lanjutan judul kedudukan')],
            'kedudukan.deskripsi' => ['nullable', 'string', 'max:400', new PlainText('Deskripsi kedudukan')],
            'identitas' => ['present', 'list', 'max:12'],
            'identitas.*' => ['required', 'array'],
            'identitas.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'identitas.*.label' => ['required', 'string', 'max:40', new PlainText('Label')],
            'identitas.*.nilai' => ['required', 'string', 'max:160', new PlainText('Isi')],
            'tugasFungsi' => ['present', 'list', 'max:12'],
            'tugasFungsi.*' => ['required', 'array'],
            'tugasFungsi.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'tugasFungsi.*.judul' => ['required', 'string', 'max:200', new PlainText('Tugas')],
            'tugasFungsi.*.deskripsi' => ['nullable', 'string', 'max:300', new PlainText('Penjelasan')],
            'tugasFungsi.*.kataKunci' => ['nullable', 'string', 'max:30', new PlainText('Kata kunci')],
            'sambutan' => ['required', 'array'],
            'sambutan.kutipan' => ['nullable', 'string', 'max:500', new PlainText('Kutipan sambutan')],
            'sambutan.nama' => ['nullable', 'string', 'max:120', new PlainText('Nama')],
            'sambutan.jabatan' => ['nullable', 'string', 'max:120', new PlainText('Jabatan')],
            'sambutan.foto' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul wajib diisi.',
            'judul.string' => 'Judul tidak valid.',
            'judul.max' => 'Judul maksimal :max karakter.',
            'lanjutan.string' => 'Lanjutan judul tidak valid.',
            'lanjutan.max' => 'Lanjutan judul maksimal :max karakter.',
            'pengantar.required' => 'Pengantar wajib diisi.',
            'pengantar.string' => 'Pengantar tidak valid.',
            'pengantar.max' => 'Pengantar maksimal :max karakter.',
            'foto.prohibited' => 'Foto kantor belum bisa diunggah ke server.',
            'keteranganFoto.string' => 'Keterangan foto tidak valid.',
            'keteranganFoto.max' => 'Keterangan foto maksimal :max karakter.',
            'kedudukan.required' => 'Kedudukan wajib dikirim.',
            'kedudukan.array' => 'Kedudukan tidak valid.',
            'kedudukan.judul.required' => 'Judul kedudukan wajib diisi.',
            'kedudukan.judul.string' => 'Judul kedudukan tidak valid.',
            'kedudukan.judul.max' => 'Judul kedudukan maksimal :max karakter.',
            'kedudukan.lanjutan.string' => 'Lanjutan judul kedudukan tidak valid.',
            'kedudukan.lanjutan.max' => 'Lanjutan judul kedudukan maksimal :max karakter.',
            'kedudukan.deskripsi.string' => 'Deskripsi kedudukan tidak valid.',
            'kedudukan.deskripsi.max' => 'Deskripsi kedudukan maksimal :max karakter.',
            'identitas.present' => 'Tabel identitas wajib dikirim.',
            'identitas.list' => 'Tabel identitas tidak valid.',
            'identitas.max' => 'Maksimal :max baris identitas.',
            'identitas.*.required' => 'Baris identitas tidak valid.',
            'identitas.*.array' => 'Baris identitas tidak valid.',
            'identitas.*.id.required' => 'Baris identitas tidak valid.',
            'identitas.*.id.string' => 'Baris identitas tidak valid.',
            'identitas.*.id.max' => 'Baris identitas tidak valid.',
            'identitas.*.id.distinct' => 'Baris identitas tercantum dua kali.',
            'identitas.*.label.required' => 'Label wajib diisi.',
            'identitas.*.label.string' => 'Label tidak valid.',
            'identitas.*.label.max' => 'Label maksimal :max karakter.',
            'identitas.*.nilai.required' => 'Isi wajib diisi.',
            'identitas.*.nilai.string' => 'Isi tidak valid.',
            'identitas.*.nilai.max' => 'Isi maksimal :max karakter.',
            'tugasFungsi.present' => 'Daftar tugas wajib dikirim.',
            'tugasFungsi.list' => 'Daftar tugas tidak valid.',
            'tugasFungsi.max' => 'Maksimal :max tugas.',
            'tugasFungsi.*.required' => 'Tugas tidak valid.',
            'tugasFungsi.*.array' => 'Tugas tidak valid.',
            'tugasFungsi.*.id.required' => 'Tugas tidak valid.',
            'tugasFungsi.*.id.string' => 'Tugas tidak valid.',
            'tugasFungsi.*.id.max' => 'Tugas tidak valid.',
            'tugasFungsi.*.id.distinct' => 'Tugas tercantum dua kali.',
            'tugasFungsi.*.judul.required' => 'Tugas wajib diisi.',
            'tugasFungsi.*.judul.string' => 'Tugas tidak valid.',
            'tugasFungsi.*.judul.max' => 'Tugas maksimal :max karakter.',
            'tugasFungsi.*.deskripsi.string' => 'Penjelasan tidak valid.',
            'tugasFungsi.*.deskripsi.max' => 'Penjelasan maksimal :max karakter.',
            'tugasFungsi.*.kataKunci.string' => 'Kata kunci tidak valid.',
            'tugasFungsi.*.kataKunci.max' => 'Kata kunci maksimal :max karakter.',
            'sambutan.required' => 'Sambutan wajib dikirim.',
            'sambutan.array' => 'Sambutan tidak valid.',
            'sambutan.kutipan.string' => 'Kutipan sambutan tidak valid.',
            'sambutan.kutipan.max' => 'Kutipan sambutan maksimal :max karakter.',
            'sambutan.nama.string' => 'Nama tidak valid.',
            'sambutan.nama.max' => 'Nama maksimal :max karakter.',
            'sambutan.jabatan.string' => 'Jabatan tidak valid.',
            'sambutan.jabatan.max' => 'Jabatan maksimal :max karakter.',
            'sambutan.foto.prohibited' => 'Foto Kepala UPTD belum bisa diunggah ke server.',
        ];
    }

    /**
     * The document as it is stored, with empty optional fields as empty
     * strings and no photos.
     *
     * @return array{judul: string, lanjutan: string, pengantar: string, foto: null, keteranganFoto: string, kedudukan: array{judul: string, lanjutan: string, deskripsi: string}, identitas: list<array{id: string, label: string, nilai: string}>, tugasFungsi: list<array{id: string, judul: string, deskripsi: string, kataKunci: string}>, sambutan: array{kutipan: string, nama: string, jabatan: string, foto: null}}
     */
    public function profile(): array
    {
        return [
            'judul' => $this->validated('judul'),
            'lanjutan' => $this->validated('lanjutan') ?? '',
            'pengantar' => $this->validated('pengantar'),
            'foto' => null,
            'keteranganFoto' => $this->validated('keteranganFoto') ?? '',
            'kedudukan' => [
                'judul' => $this->validated('kedudukan.judul'),
                'lanjutan' => $this->validated('kedudukan.lanjutan') ?? '',
                'deskripsi' => $this->validated('kedudukan.deskripsi') ?? '',
            ],
            'identitas' => array_map(fn (array $fact): array => [
                'id' => $fact['id'],
                'label' => $fact['label'],
                'nilai' => $fact['nilai'],
            ], $this->validated('identitas')),
            'tugasFungsi' => array_map(fn (array $duty): array => [
                'id' => $duty['id'],
                'judul' => $duty['judul'],
                'deskripsi' => $duty['deskripsi'] ?? '',
                'kataKunci' => $duty['kataKunci'] ?? '',
            ], $this->validated('tugasFungsi')),
            'sambutan' => [
                'kutipan' => $this->validated('sambutan.kutipan') ?? '',
                'nama' => $this->validated('sambutan.nama') ?? '',
                'jabatan' => $this->validated('sambutan.jabatan') ?? '',
                'foto' => null,
            ],
        ];
    }
}
