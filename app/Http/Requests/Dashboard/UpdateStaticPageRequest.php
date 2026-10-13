<?php

namespace App\Http\Requests\Dashboard;

use App\Models\StaticPage;
use App\Rules\PlainText;
use App\Rules\RichText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changes to a static page (D-024): any of its texts, each checked when
 * sent. The slug may be sent as it is but never changed, because it names
 * the portal page.
 */
class UpdateStaticPageRequest extends FormRequest
{
    /**
     * The request's fields and the columns they are stored in.
     *
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'judul' => 'judul',
        'judulLanjutan' => 'judul_lanjutan',
        'pengantar' => 'pengantar',
        'isi' => 'isi',
    ];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through StaticPagePolicy.
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
        /** @var StaticPage $page */
        $page = $this->route('page');

        return [
            'slug' => ['sometimes', 'required', 'string', Rule::in([$page->slug])],
            'judul' => ['sometimes', 'required', 'string', 'max:'.StaticPage::MAX_TITLE_LENGTH, new PlainText('Judul')],
            'judulLanjutan' => ['sometimes', 'nullable', 'string', 'max:'.StaticPage::MAX_TITLE_LENGTH, new PlainText('Lanjutan judul')],
            'pengantar' => ['sometimes', 'nullable', 'string', 'max:'.StaticPage::MAX_INTRO_LENGTH, new PlainText('Teks kecil di atas judul')],
            'isi' => ['sometimes', 'required', 'string', 'max:'.StaticPage::MAX_CONTENT_LENGTH, new RichText('Isi halaman')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => 'Nama berkas halaman tidak dapat diubah.',
            'slug.string' => 'Nama berkas halaman tidak dapat diubah.',
            'slug.in' => 'Nama berkas halaman tidak dapat diubah.',
            'judul.required' => 'Judul wajib diisi.',
            'judul.string' => 'Judul tidak valid.',
            'judul.max' => 'Judul maksimal :max karakter.',
            'judulLanjutan.string' => 'Lanjutan judul tidak valid.',
            'judulLanjutan.max' => 'Lanjutan judul maksimal :max karakter.',
            'pengantar.string' => 'Teks kecil di atas judul tidak valid.',
            'pengantar.max' => 'Teks kecil di atas judul maksimal :max karakter.',
            'isi.required' => 'Isi halaman wajib diisi.',
            'isi.string' => 'Isi halaman tidak valid.',
            'isi.max' => 'Isi halaman terlalu panjang (maksimal :max karakter HTML).',
        ];
    }

    /**
     * The sent texts by column; an emptied optional text is stored as "".
     *
     * @return array<string, string>
     */
    public function content(): array
    {
        $validated = $this->validated();
        $content = [];

        foreach (self::COLUMNS as $field => $column) {
            if (array_key_exists($field, $validated)) {
                $content[$column] = $validated[$field] ?? '';
            }
        }

        return $content;
    }
}
