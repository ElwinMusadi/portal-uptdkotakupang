<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\UploadTarget;
use App\Models\AgencyApp;
use App\Models\Upload;
use App\Rules\PlainText;
use App\Rules\PortalLink;
use App\Rules\UploadUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new app or website of BPAD or the UPTD (D-054). Its link is a full
 * address or a portal page; an Android app links to Google Play, since the
 * portal offers it as "Unduh di Google Play". The logo is one uploaded to
 * PINTU for Aplikasi (D-028) that no other app uses.
 */
class StoreAgencyAppRequest extends FormRequest
{
    /**
     * Where Android apps are offered.
     */
    public const string GOOGLE_PLAY = 'https://play.google.com/store/apps/';

    /**
     * The dashboard's field names and the columns they are stored in.
     *
     * @var array<string, string>
     */
    private const array COLUMNS = [
        'nama' => 'nama',
        'namaLengkap' => 'nama_lengkap',
        'keterangan' => 'keterangan',
        'jenis' => 'jenis',
        'pengelola' => 'pengelola',
        'tautan' => 'tautan',
        'logo' => 'logo',
    ];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through AgencyAppPolicy.
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
            'nama' => ['required', 'string', 'max:60', new PlainText('Nama aplikasi')],
            'namaLengkap' => ['nullable', 'string', 'max:160', new PlainText('Nama lengkap')],
            'keterangan' => ['nullable', 'string', 'max:300', new PlainText('Keterangan')],
            'jenis' => ['required', Rule::in(AgencyApp::KINDS)],
            'pengelola' => ['required', Rule::in(AgencyApp::OWNERS)],
            'tautan' => ['required', 'string', 'max:500', new PortalLink('Tautan')],
            'logo' => ['nullable', 'string', new UploadUrl(UploadTarget::Aplikasi, 'Logo harus diunggah lewat dashboard.')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama aplikasi wajib diisi.',
            'nama.string' => 'Nama aplikasi tidak valid.',
            'nama.max' => 'Nama aplikasi maksimal :max karakter.',
            'namaLengkap.string' => 'Nama lengkap tidak valid.',
            'namaLengkap.max' => 'Nama lengkap maksimal :max karakter.',
            'keterangan.string' => 'Keterangan tidak valid.',
            'keterangan.max' => 'Keterangan maksimal :max karakter.',
            'jenis.required' => 'Pilih jenis aplikasi.',
            'jenis.in' => 'Jenis aplikasi tidak valid.',
            'pengelola.required' => 'Pilih pengelola aplikasi.',
            'pengelola.in' => 'Pengelola aplikasi tidak valid.',
            'tautan.required' => 'Tautan wajib diisi.',
            'tautan.string' => 'Tautan tidak valid.',
            'tautan.max' => 'Tautan maksimal :max karakter.',
            'logo.string' => 'Logo harus diunggah lewat dashboard.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->checkLogo(...), $this->checkStore(...)];
    }

    /**
     * A logo another app already uses cannot be taken: removing it from one
     * app would remove it from the other. An app's own logo sent back
     * unchanged never reaches this check (UpdateAgencyAppRequest).
     */
    protected function checkLogo(Validator $validator): void
    {
        $url = $this->input('logo');
        $upload = is_string($url) ? Upload::fromUrl($url, UploadTarget::Aplikasi) : null;

        if ($upload?->dipakai_pada !== null) {
            $validator->errors()->add('logo', 'Logo ini sudah dipakai aplikasi lain. Unggah ulang logonya.');
        }
    }

    /**
     * An Android app links to its page on Google Play.
     */
    protected function checkStore(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['jenis', 'tautan'])) {
            return;
        }

        $kind = $this->input('jenis', $this->currentApp()?->jenis);
        $link = (string) $this->input('tautan', $this->currentApp()?->tautan);

        if ($kind === 'android' && ! str_starts_with($link, self::GOOGLE_PLAY)) {
            $validator->errors()->add('tautan', 'Aplikasi Android ditautkan ke halamannya di Google Play (https://play.google.com/store/apps/…).');
        }
    }

    /**
     * The app the request changes; none when adding one.
     */
    protected function currentApp(): ?AgencyApp
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

        foreach (self::COLUMNS as $field => $column) {
            if (Arr::has($validated, $field)) {
                $content[$column] = $field === 'logo' ? $validated['logo'] : ($validated[$field] ?? '');
            }
        }

        return $content;
    }
}
