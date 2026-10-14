<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\Article;
use App\Models\Upload;
use App\Rules\PlainText;
use App\Rules\RichText;
use App\Rules\UploadUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new article. It always starts as a draft; the workflow fields the
 * dashboard sends along are ignored (D-022). The body is HTML as the
 * dashboard's editor writes it (RichText), and the cover photo is one
 * uploaded to PINTU for Berita (D-028) that no other article uses.
 */
class StoreArticleRequest extends FormRequest
{
    /**
     * The dashboard's field names and the columns they are stored in.
     *
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'judul' => 'judul',
        'slug' => 'slug',
        'kategori' => 'kategori',
        'ringkasan' => 'ringkasan',
        'isi' => 'isi',
        'sampul' => 'sampul',
        'keteranganSampul' => 'keterangan_sampul',
        'kreditFoto' => 'kredit_foto',
        'tanggal' => 'tanggal',
        'sorotan' => 'sorotan',
        'pengumumanBeranda' => 'pengumuman_beranda',
        'penyedia' => 'penyedia',
        'pemeriksa' => 'pemeriksa',
    ];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through ArticlePolicy.
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
            'judul' => ['required', 'string', 'max:160', new PlainText('Judul')],
            'slug' => ['required', 'string', 'max:80', 'regex:/^'.Article::SLUG_PATTERN.'\z/', Rule::unique('articles', 'slug')->ignore($this->route('article'))],
            'kategori' => ['required', Rule::in(Article::CATEGORIES)],
            'ringkasan' => ['required', 'string', 'max:240', new PlainText('Ringkasan')],
            'isi' => ['required', 'string', 'max:'.Article::MAX_CONTENT_LENGTH, new RichText('Isi tulisan')],
            'sampul' => ['nullable', 'string', new UploadUrl(UploadTarget::Berita, 'Foto sampul harus diunggah lewat dashboard.')],
            'keteranganSampul' => ['nullable', 'string', 'max:200', new PlainText('Keterangan foto')],
            'kreditFoto' => ['nullable', 'string', 'max:120', new PlainText('Kredit foto')],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'sorotan' => ['required', 'boolean'],
            'pengumumanBeranda' => ['required', 'boolean'],
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
            'judul.required' => 'Judul wajib diisi.',
            'judul.string' => 'Judul tidak valid.',
            'judul.max' => 'Judul maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.string' => 'Slug tidak valid.',
            'slug.max' => 'Slug maksimal :max karakter.',
            'slug.regex' => 'Gunakan huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai tulisan lain.',
            'kategori.required' => 'Pilih kategori tulisan.',
            'kategori.in' => 'Kategori tulisan tidak valid.',
            'ringkasan.required' => 'Ringkasan wajib diisi.',
            'ringkasan.string' => 'Ringkasan tidak valid.',
            'ringkasan.max' => 'Ringkasan maksimal :max karakter.',
            'isi.required' => 'Isi tulisan wajib diisi.',
            'isi.string' => 'Isi tulisan tidak valid.',
            'isi.max' => 'Isi tulisan terlalu panjang (maksimal :max karakter HTML).',
            'sampul.string' => 'Foto sampul harus diunggah lewat dashboard.',
            'keteranganSampul.string' => 'Keterangan foto tidak valid.',
            'keteranganSampul.max' => 'Keterangan foto maksimal :max karakter.',
            'kreditFoto.string' => 'Kredit foto tidak valid.',
            'kreditFoto.max' => 'Kredit foto maksimal :max karakter.',
            'tanggal.required' => 'Pilih tanggal tayang.',
            'tanggal.date_format' => 'Pilih tanggal tayang.',
            'sorotan.required' => 'Pilih apakah tulisan menjadi tulisan utama.',
            'sorotan.boolean' => 'Pilihan tulisan utama tidak valid.',
            'pengumumanBeranda.required' => 'Pilih apakah tulisan tampil di beranda.',
            'pengumumanBeranda.boolean' => 'Pilihan beranda tidak valid.',
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
        return [$this->checkCover(...)];
    }

    /**
     * A cover photo another article already uses cannot be taken: removing
     * it from one article would remove it from the other.
     */
    protected function checkCover(Validator $validator): void
    {
        $url = $this->input('sampul');
        $upload = is_string($url) ? Upload::fromUrl($url, UploadTarget::Berita) : null;

        if ($upload?->dipakai_pada !== null && $upload->url() !== $this->currentArticle()?->sampul) {
            $validator->errors()->add('sampul', 'Foto ini sudah dipakai tulisan lain. Unggah ulang fotonya.');
        }
    }

    /**
     * The article the request changes; none when adding one.
     */
    protected function currentArticle(): ?Article
    {
        return null;
    }

    /**
     * The validated content as model attributes. Empty optional texts are
     * stored as empty strings; an empty cover is stored as null.
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

            $content[$column] = $field === 'sampul' ? $validated['sampul'] : $validated[$field] ?? '';
        }

        return $content;
    }
}
