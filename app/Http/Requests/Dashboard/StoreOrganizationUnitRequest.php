<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\OrganizationUnit;
use App\Models\Upload;
use App\Rules\PlainText;
use App\Rules\UploadUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new unit of the organisation chart. It reports to an existing unit
 * that is not a partner, or to none. The official's photo is one uploaded
 * to PINTU for Struktur Organisasi (D-028) that no other unit uses; the NIP
 * is 18 digits, written with or without spaces.
 */
class StoreOrganizationUnitRequest extends FormRequest
{
    /**
     * The dashboard's field names and the columns they are stored in.
     *
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'nama' => 'nama',
        'singkatan' => 'singkatan',
        'jenis' => 'jenis',
        'induk' => 'induk',
        'jabatan' => 'jabatan',
        'pejabat' => 'pejabat',
        'nip' => 'nip',
        'foto' => 'foto',
        'anggota' => 'anggota',
        'uraianTugas' => 'uraian_tugas',
        'catatan' => 'catatan',
    ];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through OrganizationUnitPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation: the NIP without spaces.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nip'))) {
            $this->merge(['nip' => preg_replace('/\s+/', '', $this->input('nip'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:120', new PlainText('Nama unit')],
            'singkatan' => ['required', 'string', 'regex:/^[A-Za-z0-9]{1,3}\z/'],
            'jenis' => ['required', Rule::in(OrganizationUnit::KINDS)],
            'induk' => ['nullable', 'string', Rule::exists('organization_units', 'id')->whereNot('jenis', OrganizationUnit::PARTNER)],
            'jabatan' => ['nullable', 'string', 'max:120', new PlainText('Jabatan')],
            'pejabat' => ['nullable', 'string', 'max:160', new PlainText('Nama pejabat')],
            'nip' => ['nullable', 'string', 'regex:/^\d{18}\z/'],
            'foto' => ['nullable', 'string', new UploadUrl(UploadTarget::Unit, 'Foto pejabat harus diunggah lewat dashboard.')],
            'anggota' => ['present', 'list', 'max:20'],
            'anggota.*' => ['nullable', 'string', 'max:120', new PlainText('Jabatan pelaksana')],
            'uraianTugas' => ['nullable', 'string', 'max:1000', new PlainText('Uraian tugas')],
            'catatan' => ['nullable', 'string', 'max:200', new PlainText('Catatan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.string' => 'Nama unit tidak valid.',
            'nama.max' => 'Nama unit maksimal :max karakter.',
            'singkatan.required' => 'Isi singkatan.',
            'singkatan.string' => 'Singkatan tidak valid.',
            'singkatan.regex' => 'Singkatan 1–3 huruf atau angka.',
            'jenis.required' => 'Pilih jenis unit.',
            'jenis.in' => 'Jenis unit tidak valid.',
            'induk.string' => 'Pilih unit di atasnya dari daftar.',
            'induk.exists' => 'Pilih unit di atasnya dari daftar.',
            'jabatan.string' => 'Jabatan tidak valid.',
            'jabatan.max' => 'Jabatan maksimal :max karakter.',
            'pejabat.string' => 'Nama pejabat tidak valid.',
            'pejabat.max' => 'Nama pejabat maksimal :max karakter.',
            'nip.string' => 'NIP 18 digit angka.',
            'nip.regex' => 'NIP 18 digit angka.',
            'foto.string' => 'Foto pejabat harus diunggah lewat dashboard.',
            'anggota.present' => 'Daftar jabatan pelaksana wajib dikirim.',
            'anggota.list' => 'Daftar jabatan pelaksana tidak valid.',
            'anggota.max' => 'Maksimal :max jabatan pelaksana.',
            'anggota.*.string' => 'Jabatan pelaksana tidak valid.',
            'anggota.*.max' => 'Jabatan pelaksana maksimal :max karakter.',
            'uraianTugas.string' => 'Uraian tugas tidak valid.',
            'uraianTugas.max' => 'Uraian tugas maksimal :max karakter.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->checkPhoto(...), $this->checkParent(...)];
    }

    /**
     * A photo another unit already uses cannot be taken: removing it from
     * one unit would remove it from the other.
     */
    protected function checkPhoto(Validator $validator): void
    {
        $url = $this->input('foto');
        $upload = is_string($url) ? Upload::fromUrl($url, UploadTarget::Unit) : null;

        if ($upload?->dipakai_pada !== null && $upload->url() !== $this->currentUnit()?->foto) {
            $validator->errors()->add('foto', 'Foto ini sudah dipakai unit lain. Unggah ulang fotonya.');
        }
    }

    /**
     * A unit cannot report to itself or to a unit under it, which would
     * take both off the chart.
     */
    protected function checkParent(Validator $validator): void
    {
        $unit = $this->currentUnit();
        $parent = $this->input('induk');
        $seen = [];

        // Walk up from the new parent; $seen stops on chains that already loop.
        while ($unit !== null && is_string($parent) && ! in_array($parent, $seen, true)) {
            if ($parent === $unit->id) {
                $validator->errors()->add('induk', 'Unit tidak bisa berada di bawah dirinya sendiri atau unit yang dibawahinya.');

                return;
            }

            $seen[] = $parent;
            $parent = OrganizationUnit::query()->whereKey($parent)->value('induk');
        }
    }

    /**
     * The unit the request changes; none when adding one.
     */
    protected function currentUnit(): ?OrganizationUnit
    {
        return null;
    }

    /**
     * The validated content as model attributes. Empty optional texts are
     * stored as empty strings, empty staff positions are dropped, and the
     * abbreviation is stored in capitals.
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
                'singkatan' => strtoupper($validated['singkatan']),
                'induk', 'foto' => $validated[$field],
                'anggota' => array_values(array_filter($validated['anggota'], fn (?string $position): bool => $position !== null)),
                default => $validated[$field] ?? '',
            };
        }

        return $content;
    }
}
