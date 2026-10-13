<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Service;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * A new service with its requirements. It always starts as a draft; the
 * workflow fields the dashboard sends along are ignored (D-022).
 */
class StoreServiceRequest extends FormRequest
{
    /**
     * The dashboard's field names and the columns they are stored in.
     *
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'nama' => 'nama',
        'slug' => 'slug',
        'ringkasan' => 'ringkasan',
        'loket' => 'loket',
        'jenis' => 'jenis',
        'cekFisik' => 'cek_fisik',
        'nonTunai' => 'non_tunai',
        'diwakilkan' => 'diwakilkan',
        'label' => 'label',
        'persyaratan' => 'persyaratan',
        'catatan' => 'catatan',
        'unggulan' => 'unggulan',
        'penyedia' => 'penyedia',
        'pemeriksa' => 'pemeriksa',
    ];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through ServicePolicy.
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
            'nama' => ['required', 'string', 'max:120', new PlainText('Nama layanan')],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('services', 'slug')->ignore($this->route('service'))],
            'ringkasan' => ['required', 'string', 'max:200', new PlainText('Ringkasan')],
            'loket' => ['required', 'string', 'max:60', new PlainText('Loket')],
            'jenis' => ['required', Rule::in(Service::KINDS)],
            'cekFisik' => ['required', 'boolean'],
            'nonTunai' => ['required', 'boolean'],
            'diwakilkan' => ['required', 'boolean'],
            'label' => ['present', 'list', 'max:6'],
            'label.*' => ['nullable', 'string', 'max:60', new PlainText('Label')],
            'persyaratan' => ['required', 'list', 'min:1', 'max:20'],
            'persyaratan.*' => ['required', 'array'],
            'persyaratan.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'persyaratan.*.dokumen' => ['required', 'string', 'max:200', new PlainText('Nama berkas')],
            'persyaratan.*.jumlah' => ['nullable', 'string', 'max:40', new PlainText('Jumlah')],
            'catatan' => ['nullable', 'string', 'max:400', new PlainText('Catatan')],
            'unggulan' => ['required', 'boolean'],
            'penyedia' => ['required', 'string', 'max:120', new PlainText('Unit penyedia')],
            'pemeriksa' => ['nullable', 'string', 'max:120', new PlainText('Pemeriksa')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama layanan wajib diisi.',
            'nama.string' => 'Nama layanan tidak valid.',
            'nama.max' => 'Nama layanan maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.string' => 'Slug tidak valid.',
            'slug.max' => 'Slug maksimal :max karakter.',
            'slug.regex' => 'Gunakan huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai layanan lain.',
            'ringkasan.required' => 'Ringkasan wajib diisi.',
            'ringkasan.string' => 'Ringkasan tidak valid.',
            'ringkasan.max' => 'Ringkasan maksimal :max karakter.',
            'loket.required' => 'Loket wajib diisi.',
            'loket.string' => 'Loket tidak valid.',
            'loket.max' => 'Loket maksimal :max karakter.',
            'jenis.required' => 'Pilih jenis layanan.',
            'jenis.in' => 'Jenis layanan tidak valid.',
            'cekFisik.required' => 'Pilih apakah layanan wajib cek fisik.',
            'cekFisik.boolean' => 'Pilihan cek fisik tidak valid.',
            'nonTunai.required' => 'Pilih apakah layanan bisa non-tunai.',
            'nonTunai.boolean' => 'Pilihan non-tunai tidak valid.',
            'diwakilkan.required' => 'Pilih apakah layanan dapat diwakilkan.',
            'diwakilkan.boolean' => 'Pilihan diwakilkan tidak valid.',
            'label.present' => 'Daftar label wajib dikirim.',
            'label.list' => 'Daftar label tidak valid.',
            'label.max' => 'Maksimal :max label.',
            'label.*.string' => 'Label tidak valid.',
            'label.*.max' => 'Label maksimal :max karakter.',
            'persyaratan.required' => 'Tambahkan minimal satu berkas persyaratan.',
            'persyaratan.list' => 'Daftar persyaratan tidak valid.',
            'persyaratan.min' => 'Tambahkan minimal satu berkas persyaratan.',
            'persyaratan.max' => 'Maksimal :max berkas persyaratan.',
            'persyaratan.*.required' => 'Berkas persyaratan tidak valid.',
            'persyaratan.*.array' => 'Berkas persyaratan tidak valid.',
            'persyaratan.*.id.required' => 'Berkas persyaratan tidak valid.',
            'persyaratan.*.id.string' => 'Berkas persyaratan tidak valid.',
            'persyaratan.*.id.max' => 'Berkas persyaratan tidak valid.',
            'persyaratan.*.id.distinct' => 'Berkas persyaratan tercantum dua kali.',
            'persyaratan.*.dokumen.required' => 'Nama berkas wajib diisi.',
            'persyaratan.*.dokumen.string' => 'Nama berkas tidak valid.',
            'persyaratan.*.dokumen.max' => 'Nama berkas maksimal :max karakter.',
            'persyaratan.*.jumlah.string' => 'Jumlah tidak valid.',
            'persyaratan.*.jumlah.max' => 'Jumlah maksimal :max karakter.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
            'unggulan.required' => 'Pilih apakah layanan tampil di beranda.',
            'unggulan.boolean' => 'Pilihan beranda tidak valid.',
            'penyedia.required' => 'Unit penyedia wajib diisi.',
            'penyedia.string' => 'Unit penyedia tidak valid.',
            'penyedia.max' => 'Unit penyedia maksimal :max karakter.',
            'pemeriksa.string' => 'Pemeriksa tidak valid.',
            'pemeriksa.max' => 'Pemeriksa maksimal :max karakter.',
        ];
    }

    /**
     * The validated content as model attributes. Empty labels are dropped,
     * and empty optional texts are stored as empty strings.
     *
     * @return array<string, mixed>
     */
    public function content(): array
    {
        $validated = $this->validated();
        $content = [];

        foreach (self::COLUMNS as $field => $column) {
            if (! Arr::has($validated, $field)) {
                continue;
            }

            $content[$column] = match ($field) {
                'cekFisik', 'nonTunai', 'diwakilkan', 'unggulan' => (bool) $validated[$field],
                'label' => array_values(array_filter($validated['label'], fn (?string $label): bool => $label !== null)),
                'persyaratan' => array_map(fn (array $requirement): array => [
                    'id' => $requirement['id'],
                    'dokumen' => $requirement['dokumen'],
                    'jumlah' => $requirement['jumlah'] ?? '',
                ], $validated['persyaratan']),
                default => $validated[$field] ?? '',
            };
        }

        return $content;
    }
}
