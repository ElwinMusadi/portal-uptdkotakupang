<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\WorkflowStatus;
use App\Models\Article;
use App\Models\Setting;
use App\Rules\PlainText;
use App\Rules\PortalLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The announcement bar above the news on the portal's Informasi page
 * (D-082): whether it shows, also on Beranda (D-083), its text, and the link
 * it may lead to. The text is written in it or taken from one published
 * Tulisan (D-083); the rules for a written text are those it had in
 * Pengaturan Situs (D-026).
 */
class UpdateAnnouncementBarRequest extends FormRequest
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
     * Get the validation rules that apply to the request. A Tulisan is only
     * kept when it is the source, and must be published while the bar
     * shows, so a bar whose Tulisan was withdrawn can still be switched off;
     * the written text is kept either way.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $shown = $this->boolean('aktif');

        return [
            'aktif' => ['required', 'boolean'],
            'beranda' => ['required', 'boolean'],
            'sumber' => ['required', 'string', Rule::in(Setting::ANNOUNCEMENT_BAR_SOURCES)],
            'tulisan' => ['exclude_unless:sumber,tulisan', Rule::requiredIf($shown), 'nullable', 'string', Rule::when($shown, [Rule::exists(Article::class, 'id')->where('status', WorkflowStatus::Published->value)])],
            'teks' => [Rule::requiredIf($shown && $this->input('sumber') === 'teks'), 'nullable', 'string', 'max:200', new PlainText('Teks pengumuman')],
            'tautanLabel' => ['nullable', 'string', 'max:30', new PlainText('Label tautan')],
            'tautanUrl' => ['nullable', 'string', 'max:300', new PortalLink('Tujuan tautan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute tidak valid.',
            'boolean' => ':Attribute tidak valid.',
            'in' => ':Attribute tidak valid.',
            'max' => ':Attribute maksimal :max karakter.',
            'tulisan.required' => 'Pilih tulisan untuk bilah pengumuman.',
            'tulisan.exists' => 'Tulisan yang dipilih belum terbit atau sudah dihapus.',
            'teks.required' => 'Teks pengumuman wajib diisi selama bilah pengumuman ditampilkan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'aktif' => 'pilihan tampilkan bilah pengumuman',
            'beranda' => 'pilihan tampilkan di Beranda',
            'sumber' => 'sumber bilah pengumuman',
            'tulisan' => 'tulisan',
            'teks' => 'teks pengumuman',
            'tautanLabel' => 'label tautan',
            'tautanUrl' => 'tujuan tautan',
        ];
    }

    /**
     * The bar as it is stored, with empty optional fields as empty strings
     * and no Tulisan when the text is written in it.
     *
     * @return array{aktif: bool, beranda: bool, sumber: string, tulisan: ?string, teks: string, tautanLabel: string, tautanUrl: string}
     */
    public function announcementBar(): array
    {
        $text = fn (string $key): string => $this->validated($key) ?? '';

        return [
            'aktif' => $this->boolean('aktif'),
            'beranda' => $this->boolean('beranda'),
            'sumber' => $this->validated('sumber'),
            'tulisan' => $this->validated('tulisan'),
            'teks' => $text('teks'),
            'tautanLabel' => $text('tautanLabel'),
            'tautanUrl' => $text('tautanUrl'),
        ];
    }
}
