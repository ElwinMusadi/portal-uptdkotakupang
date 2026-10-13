<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\Document;
use App\Models\Upload;
use App\Rules\PlainText;
use App\Rules\UploadUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new document. It always starts as a draft; the workflow fields the
 * dashboard sends along are ignored (D-022). The file is one uploaded to
 * PINTU for Regulasi & Unduhan (D-028) and not used by another document;
 * its name, size and type are taken from the upload, and the format follows
 * its type. A document without a file is a link ("Tautan") or awaits one.
 */
class StoreDocumentRequest extends FormRequest
{
    /**
     * The fields, named as the dashboard and the columns name them.
     *
     * @var list<string>
     */
    private const array FIELDS = ['judul', 'keterangan', 'kategori', 'format', 'nomor', 'tahun', 'berkas', 'tautan', 'penyedia', 'pemeriksa'];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through DocumentPolicy.
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
            'judul' => ['required', 'string', 'max:200', new PlainText('Judul dokumen')],
            'keterangan' => ['nullable', 'string', 'max:300', new PlainText('Keterangan')],
            'kategori' => ['required', Rule::in(Document::CATEGORIES)],
            'format' => ['required', Rule::in(Document::FORMATS)],
            'nomor' => ['nullable', 'string', 'max:60', new PlainText('Nomor')],
            'tahun' => ['nullable', 'string', 'regex:/^(19|20)\d{2}\z/'],
            'berkas' => ['nullable', 'array'],
            'berkas.url' => ['required_with:berkas', 'string', new UploadUrl(UploadTarget::Dokumen, 'Berkas dokumen harus diunggah lewat dashboard.')],
            'tautan' => ['nullable', 'string', 'max:500', 'url:http,https'],
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
            'judul.required' => 'Judul dokumen wajib diisi.',
            'judul.string' => 'Judul dokumen tidak valid.',
            'judul.max' => 'Judul dokumen maksimal :max karakter.',
            'keterangan.string' => 'Keterangan tidak valid.',
            'keterangan.max' => 'Keterangan maksimal :max karakter.',
            'kategori.required' => 'Pilih kategori dokumen.',
            'kategori.in' => 'Kategori dokumen tidak valid.',
            'format.required' => 'Pilih format dokumen.',
            'format.in' => 'Format dokumen tidak valid.',
            'nomor.string' => 'Nomor tidak valid.',
            'nomor.max' => 'Nomor maksimal :max karakter.',
            'tahun.string' => 'Tahun 4 angka, mis. 2026.',
            'tahun.regex' => 'Tahun 4 angka, mis. 2026.',
            'berkas.array' => 'Berkas dokumen tidak valid.',
            'berkas.url.required_with' => 'Berkas dokumen harus diunggah lewat dashboard.',
            'berkas.url.string' => 'Berkas dokumen harus diunggah lewat dashboard.',
            'tautan.string' => 'Tautan tidak valid.',
            'tautan.max' => 'Tautan maksimal :max karakter.',
            'tautan.url' => 'Gunakan alamat lengkap yang diawali http:// atau https://.',
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
        return [$this->checkDocument(...)];
    }

    /**
     * The document as it would be saved must hold together: a file is not
     * one another document already uses, a link document has its link, and
     * a published document has a file or a link.
     */
    protected function checkDocument(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $current = $this->currentDocument();
        $document = (new Document($current?->only(self::FIELDS) ?? []))->fill($this->contentFrom($validator->getData()));
        $upload = $this->upload();

        if ($upload?->dipakai_pada !== null && $upload->url() !== ($current?->berkas['url'] ?? null)) {
            $validator->errors()->add('berkas.url', 'Berkas ini sudah dipakai dokumen lain. Unggah ulang berkasnya.');
        } elseif ($document->format === 'Tautan' && $document->tautan === '') {
            $validator->errors()->add('tautan', 'Isi alamat tautan untuk dokumen berformat Tautan.');
        } elseif ($this->resultingStatusIsPublished() && ! $document->isAvailable()) {
            $validator->errors()->add('berkas', 'Lampirkan berkas atau tautan sebelum dokumen diterbitkan.');
        }
    }

    /**
     * The document the request changes; none when adding one.
     */
    protected function currentDocument(): ?Document
    {
        return null;
    }

    /**
     * Whether the document is published once the request is saved. A new
     * document is always a draft.
     */
    protected function resultingStatusIsPublished(): bool
    {
        return false;
    }

    /**
     * The validated content as model attributes. Empty optional texts are
     * stored as empty strings; the file is described by its upload, and a
     * document with a file takes the file's format.
     *
     * @return array<string, mixed>
     */
    public function content(): array
    {
        return $this->contentFrom($this->validated());
    }

    /**
     * The content of valid request data as model attributes. The validation
     * hooks use it before the validated data exists.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contentFrom(array $data): array
    {
        $content = [];

        foreach (self::FIELDS as $field) {
            if (Arr::has($data, $field)) {
                $content[$field] = $field === 'berkas' ? $this->fileFromUpload() : ($data[$field] ?? '');
            }
        }

        $file = array_key_exists('berkas', $content) ? $content['berkas'] : $this->currentDocument()?->berkas;

        if ($file !== null) {
            $content['format'] = Document::FILE_FORMATS[$file['tipe']];
        }

        return $content;
    }

    /**
     * The file as its upload describes it, whatever name, size or type the
     * request claims, or null without one.
     *
     * @return ?array{nama: string, ukuran: int, tipe: string, url: string}
     */
    private function fileFromUpload(): ?array
    {
        $upload = $this->upload();

        return $upload === null ? null : [
            'nama' => $upload->nama,
            'ukuran' => $upload->ukuran,
            'tipe' => $upload->tipe,
            'url' => $upload->url(),
        ];
    }

    private function upload(): ?Upload
    {
        $url = $this->input('berkas.url');

        return is_string($url) ? Upload::fromUrl($url, UploadTarget::Dokumen) : null;
    }
}
