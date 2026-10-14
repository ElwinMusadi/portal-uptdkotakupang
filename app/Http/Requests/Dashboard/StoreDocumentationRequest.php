<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\Documentation;
use App\Models\Upload;
use App\Rules\PlainText;
use App\Rules\UploadUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new activity and its photos. It always starts as a draft; the workflow
 * fields the dashboard sends along are ignored (D-022). Every photo is one
 * uploaded to PINTU for Dokumentasi Kegiatan (D-028) that no other activity
 * uses, in the order the portal shows them.
 */
class StoreDocumentationRequest extends FormRequest
{
    /**
     * The fields, named as the dashboard and the columns name them.
     *
     * @var list<string>
     */
    private const array FIELDS = ['judul', 'kategori', 'lokasi', 'tanggal', 'deskripsi', 'foto', 'penyedia', 'pemeriksa'];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through DocumentationPolicy.
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
            'judul' => ['required', 'string', 'max:160', new PlainText('Nama kegiatan')],
            'kategori' => ['required', Rule::in(Documentation::CATEGORIES)],
            'lokasi' => ['required', 'string', 'max:120', new PlainText('Lokasi')],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'deskripsi' => ['nullable', 'string', 'max:600', new PlainText('Deskripsi')],
            'foto' => ['present', 'list', 'max:'.Documentation::MAX_PHOTOS],
            'foto.*' => ['required', 'array'],
            'foto.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'foto.*.src' => ['required', 'string', 'distinct', new UploadUrl(UploadTarget::Dokumentasi, 'Foto harus diunggah lewat dashboard.')],
            'foto.*.keterangan' => ['nullable', 'string', 'max:200', new PlainText('Keterangan foto')],
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
            'judul.required' => 'Nama kegiatan wajib diisi.',
            'judul.string' => 'Nama kegiatan tidak valid.',
            'judul.max' => 'Nama kegiatan maksimal :max karakter.',
            'kategori.required' => 'Pilih kategori kegiatan.',
            'kategori.in' => 'Kategori kegiatan tidak valid.',
            'lokasi.required' => 'Lokasi wajib diisi.',
            'lokasi.string' => 'Lokasi tidak valid.',
            'lokasi.max' => 'Lokasi maksimal :max karakter.',
            'tanggal.required' => 'Pilih tanggal kegiatan.',
            'tanggal.date_format' => 'Pilih tanggal kegiatan.',
            'deskripsi.string' => 'Deskripsi tidak valid.',
            'deskripsi.max' => 'Deskripsi maksimal :max karakter.',
            'foto.present' => 'Daftar foto wajib dikirim.',
            'foto.list' => 'Daftar foto tidak valid.',
            'foto.max' => 'Maksimal :max foto per kegiatan.',
            'foto.*.required' => 'Foto tidak valid.',
            'foto.*.array' => 'Foto tidak valid.',
            'foto.*.id.required' => 'Foto tidak valid.',
            'foto.*.id.string' => 'Foto tidak valid.',
            'foto.*.id.max' => 'Foto tidak valid.',
            'foto.*.id.distinct' => 'Foto tercantum dua kali.',
            'foto.*.src.required' => 'Foto harus diunggah lewat dashboard.',
            'foto.*.src.string' => 'Foto harus diunggah lewat dashboard.',
            'foto.*.src.distinct' => 'Foto tercantum dua kali.',
            'foto.*.keterangan.string' => 'Keterangan foto tidak valid.',
            'foto.*.keterangan.max' => 'Keterangan foto maksimal :max karakter.',
            'penyedia.required' => 'Unit penyedia wajib diisi.',
            'penyedia.string' => 'Unit penyedia tidak valid.',
            'penyedia.max' => 'Unit penyedia maksimal :max karakter.',
            'pemeriksa.string' => 'Pemeriksa tidak valid.',
            'pemeriksa.max' => 'Pemeriksa maksimal :max karakter.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->checkPhotos(...)];
    }

    /**
     * A photo another activity already uses cannot be taken: removing it
     * from one activity would remove it from the other.
     */
    protected function checkPhotos(Validator $validator): void
    {
        $own = $this->currentDocumentation()?->photoUrls() ?? [];

        foreach ((array) $this->input('foto') as $index => $photo) {
            $url = is_array($photo) ? ($photo['src'] ?? null) : null;
            $upload = is_string($url) ? Upload::fromUrl($url, UploadTarget::Dokumentasi) : null;

            if ($upload?->dipakai_pada !== null && ! in_array($url, $own, true)) {
                $validator->errors()->add("foto.{$index}.src", 'Foto ini sudah dipakai kegiatan lain. Unggah ulang fotonya.');
            }
        }
    }

    /**
     * The activity the request changes; none when adding one.
     */
    protected function currentDocumentation(): ?Documentation
    {
        return null;
    }

    /**
     * The validated content as model attributes. Empty optional texts are
     * stored as empty strings.
     *
     * @return array<string, mixed>
     */
    public function content(): array
    {
        $validated = $this->validated();
        $content = [];

        foreach (self::FIELDS as $field) {
            if (Arr::has($validated, $field)) {
                $content[$field] = $field === 'foto' ? array_map(fn (array $photo): array => [
                    'id' => $photo['id'],
                    'src' => $photo['src'],
                    'keterangan' => $photo['keterangan'] ?? '',
                ], $validated['foto']) : $validated[$field] ?? '';
            }
        }

        return $content;
    }
}
