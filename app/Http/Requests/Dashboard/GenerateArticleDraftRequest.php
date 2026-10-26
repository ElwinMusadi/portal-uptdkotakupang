<?php

namespace App\Http\Requests\Dashboard;

use App\Actions\StoreUpload;
use App\Models\Article;
use App\Rules\PlainText;
use App\Support\DocxText;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * The sources of an AI draft for Berita & Pengumuman (D-085): a link and/or
 * up to five documents (PDF, Word .docx, JPEG or PNG, checked by their
 * contents), 10 MB together, with an optional category and instructions.
 */
class GenerateArticleDraftRequest extends FormRequest
{
    public const int MAX_FILES = 5;

    public const int MAX_KILOBYTES = 10240;

    private const array TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

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
            'tautan' => ['nullable', 'required_without:berkas', 'string', 'max:2000', 'url:http,https'],
            'berkas' => ['nullable', 'required_without:tautan', 'array', 'max:'.self::MAX_FILES],
            'berkas.*' => ['required', 'file', 'max:'.self::MAX_KILOBYTES, $this->supportedType(...)],
            'kategori' => ['nullable', 'string', 'in:'.implode(',', Article::CATEGORIES)],
            'petunjuk' => ['nullable', 'string', 'max:500', new PlainText('Petunjuk tambahan')],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $bytes = array_sum(array_map(fn (UploadedFile $file): int => (int) $file->getSize(), $this->documents()));
            if ($validator->errors()->isEmpty() && $bytes > self::MAX_KILOBYTES * 1024) {
                $validator->errors()->add('berkas', 'Ukuran semua dokumen maksimal 10 MB.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'string' => ':Attribute tidak valid.',
            'max' => ':Attribute maksimal :max karakter.',
            'tautan.required_without' => 'Isi tautan atau unggah dokumen sebagai sumber.',
            'berkas.required_without' => 'Isi tautan atau unggah dokumen sebagai sumber.',
            'tautan.url' => 'Tautan harus alamat lengkap yang diawali http:// atau https://.',
            'berkas.array' => 'Dokumen tidak valid.',
            'berkas.max' => 'Unggah paling banyak '.self::MAX_FILES.' dokumen.',
            'berkas.*.required' => 'Dokumen tidak valid.',
            'berkas.*.file' => 'Dokumen gagal diunggah. Coba lagi.',
            // PHP's own upload limit (upload_max_filesize), which may be lower than 10 MB
            'berkas.*.uploaded' => 'Dokumen gagal diunggah, mungkin melebihi batas unggah server. Coba dokumen yang lebih kecil.',
            'berkas.*.max' => 'Setiap dokumen maksimal 10 MB.',
            'kategori.in' => 'Kategori tidak dikenal.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['tautan' => 'tautan', 'kategori' => 'kategori', 'petunjuk' => 'petunjuk tambahan'];
    }

    /**
     * @return list<UploadedFile>
     */
    public function documents(): array
    {
        return array_values(array_filter((array) $this->file('berkas', []), fn (mixed $file): bool => $file instanceof UploadedFile));
    }

    /**
     * Accept PDF, JPEG, PNG and Word (.docx) by what the file holds, not by
     * its name.
     *
     * @param  Closure(string): void  $fail
     */
    private function supportedType(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $type = StoreUpload::detectType($value);
        if (in_array($type, self::TYPES, true) || (in_array($type, ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true) && DocxText::isDocx((string) $value->getRealPath()))) {
            return;
        }

        $fail("Dokumen {$value->getClientOriginalName()} harus PDF, Word (.docx), JPEG, atau PNG.");
    }
}
