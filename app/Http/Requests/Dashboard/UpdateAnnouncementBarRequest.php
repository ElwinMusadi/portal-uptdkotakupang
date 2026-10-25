<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use App\Rules\PortalLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The announcement bar above the news on the portal's Informasi page
 * (D-082): whether it shows, its text, and the link it may lead to. The
 * rules are those it had in Pengaturan Situs (D-026).
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'aktif' => ['required', 'boolean'],
            'teks' => [Rule::requiredIf(fn (): bool => $this->boolean('aktif')), 'nullable', 'string', 'max:200', new PlainText('Teks pengumuman')],
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
            'max' => ':Attribute maksimal :max karakter.',
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
            'teks' => 'teks pengumuman',
            'tautanLabel' => 'label tautan',
            'tautanUrl' => 'tujuan tautan',
        ];
    }

    /**
     * The bar as it is stored, with empty optional fields as empty strings.
     *
     * @return array{aktif: bool, teks: string, tautanLabel: string, tautanUrl: string}
     */
    public function announcementBar(): array
    {
        $text = fn (string $key): string => $this->validated($key) ?? '';

        return [
            'aktif' => $this->boolean('aktif'),
            'teks' => $text('teks'),
            'tautanLabel' => $text('tautanLabel'),
            'tautanUrl' => $text('tautanUrl'),
        ];
    }
}
