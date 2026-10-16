<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Setting;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The complete statistics document: its status and year, a note, and the
 * three categories of the portal's transparency dashboard (D-055), each
 * with four tiles and twelve monthly values (January to December).
 */
class UpdateStatisticsRequest extends FormRequest
{
    /**
     * Statistics states, as the dashboard names them. Only verified figures
     * are published (D-018).
     *
     * @var list<string>
     */
    public const array STATES = ['ilustrasi', Setting::STATISTICS_VERIFIED];

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
            'status' => ['required', Rule::in(self::STATES)],
            'tahun' => ['required', 'string', 'regex:/^(19|20)\d{2}\z/'],
            'catatan' => ['nullable', 'string', 'max:300', new PlainText('Catatan')],
            'set' => ['required', 'list', 'size:'.count(Setting::STATISTICS_CATEGORIES)],
            'set.*' => ['required', 'array'],
            'set.*.kunci' => ['required', Rule::in(Setting::STATISTICS_CATEGORIES), 'distinct'],
            'set.*.label' => ['required', 'string', 'max:60', new PlainText('Nama tab')],
            'set.*.sub' => ['nullable', 'string', 'max:120', new PlainText('Subjudul dasbor')],
            'set.*.judulGrafik' => ['required', 'string', 'max:80', new PlainText('Judul grafik')],
            'set.*.satuan' => ['nullable', 'string', 'max:30', new PlainText('Satuan grafik')],
            'set.*.tiles' => ['required', 'list', 'size:4'],
            'set.*.tiles.*' => ['required', 'array'],
            'set.*.tiles.*.id' => ['required', 'string', 'max:40'],
            'set.*.tiles.*.label' => ['required', 'string', 'max:60', new PlainText('Label')],
            'set.*.tiles.*.nilai' => ['required', 'numeric', 'between:-1000000000000,1000000000000'],
            'set.*.tiles.*.satuan' => ['nullable', 'string', 'max:12', new PlainText('Satuan')],
            'set.*.tiles.*.perubahan' => ['required', 'numeric', 'between:-1000,1000'],
            'set.*.tiles.*.naikBaik' => ['required', 'boolean'],
            'set.*.seri' => ['required', 'list', 'size:12'],
            'set.*.seri.*' => ['required', 'numeric', 'between:0,1000000000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status data.',
            'status.in' => 'Status data tidak valid.',
            'tahun.required' => 'Isi tahun data.',
            'tahun.string' => 'Tahun 4 angka.',
            'tahun.regex' => 'Tahun 4 angka.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
            'set.required' => 'Kategori statistik wajib dikirim.',
            'set.list' => 'Kategori statistik tidak valid.',
            'set.size' => 'Statistik harus berisi :size kategori.',
            'set.*.required' => 'Kategori tidak valid.',
            'set.*.array' => 'Kategori tidak valid.',
            'set.*.kunci.required' => 'Kategori tidak valid.',
            'set.*.kunci.in' => 'Kategori tidak dikenal.',
            'set.*.kunci.distinct' => 'Kategori tercantum dua kali.',
            'set.*.label.required' => 'Nama tab wajib diisi.',
            'set.*.label.string' => 'Nama tab tidak valid.',
            'set.*.label.max' => 'Nama tab maksimal :max karakter.',
            'set.*.sub.string' => 'Subjudul dasbor tidak valid.',
            'set.*.sub.max' => 'Subjudul dasbor maksimal :max karakter.',
            'set.*.judulGrafik.required' => 'Judul grafik wajib diisi.',
            'set.*.judulGrafik.string' => 'Judul grafik tidak valid.',
            'set.*.judulGrafik.max' => 'Judul grafik maksimal :max karakter.',
            'set.*.satuan.string' => 'Satuan grafik tidak valid.',
            'set.*.satuan.max' => 'Satuan grafik maksimal :max karakter.',
            'set.*.tiles.required' => 'Angka utama wajib dikirim.',
            'set.*.tiles.list' => 'Angka utama tidak valid.',
            'set.*.tiles.size' => 'Setiap kategori berisi :size angka utama.',
            'set.*.tiles.*.required' => 'Angka utama tidak valid.',
            'set.*.tiles.*.array' => 'Angka utama tidak valid.',
            'set.*.tiles.*.id.required' => 'Angka utama tidak valid.',
            'set.*.tiles.*.id.string' => 'Angka utama tidak valid.',
            'set.*.tiles.*.id.max' => 'Angka utama tidak valid.',
            'set.*.tiles.*.label.required' => 'Label angka utama wajib diisi.',
            'set.*.tiles.*.label.string' => 'Label angka utama tidak valid.',
            'set.*.tiles.*.label.max' => 'Label angka utama maksimal :max karakter.',
            'set.*.tiles.*.nilai.required' => 'Isi nilai angka utama.',
            'set.*.tiles.*.nilai.numeric' => 'Nilai angka utama harus berupa angka.',
            'set.*.tiles.*.nilai.between' => 'Nilai angka utama terlalu besar.',
            'set.*.tiles.*.satuan.string' => 'Satuan angka utama tidak valid.',
            'set.*.tiles.*.satuan.max' => 'Satuan angka utama maksimal :max karakter.',
            'set.*.tiles.*.perubahan.required' => 'Isi perubahan dalam persen.',
            'set.*.tiles.*.perubahan.numeric' => 'Perubahan harus berupa angka.',
            'set.*.tiles.*.perubahan.between' => 'Perubahan -1.000 sampai 1.000%.',
            'set.*.tiles.*.naikBaik.required' => 'Pilih apakah kenaikan bermakna baik.',
            'set.*.tiles.*.naikBaik.boolean' => 'Pilihan kenaikan tidak valid.',
            'set.*.seri.required' => 'Data bulanan wajib dikirim.',
            'set.*.seri.list' => 'Data bulanan tidak valid.',
            'set.*.seri.size' => 'Data bulanan berisi :size nilai, Januari sampai Desember.',
            'set.*.seri.*.required' => 'Isi nilai bulanan.',
            'set.*.seri.*.numeric' => 'Nilai bulanan harus berupa angka.',
            'set.*.seri.*.between' => 'Nilai bulanan tidak boleh negatif atau terlalu besar.',
        ];
    }

    /**
     * The statistics as they are stored. Whole numbers are kept as integers,
     * so saved statistics read back identical and saving them again is no
     * change.
     *
     * @return array{status: string, catatan: string, tahun: string, set: list<array{kunci: string, label: string, sub: string, judulGrafik: string, satuan: string, tiles: list<array{id: string, label: string, nilai: int|float, satuan: string, perubahan: int|float, naikBaik: bool}>, seri: list<int|float>}>}
     */
    public function statistics(): array
    {
        return [
            'status' => $this->validated('status'),
            'catatan' => $this->validated('catatan') ?? '',
            'tahun' => $this->validated('tahun'),
            'set' => array_map(fn (array $category): array => [
                'kunci' => $category['kunci'],
                'label' => $category['label'],
                'sub' => $category['sub'] ?? '',
                'judulGrafik' => $category['judulGrafik'],
                'satuan' => $category['satuan'] ?? '',
                'tiles' => array_map(fn (array $tile): array => [
                    'id' => $tile['id'],
                    'label' => $tile['label'],
                    'nilai' => $this->number($tile['nilai']),
                    'satuan' => $tile['satuan'] ?? '',
                    'perubahan' => $this->number($tile['perubahan']),
                    'naikBaik' => (bool) $tile['naikBaik'],
                ], $category['tiles']),
                'seri' => array_map($this->number(...), $category['seri']),
            ], $this->validated('set')),
        ];
    }

    private function number(int|float|string $value): int|float
    {
        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }
}
