<?php

namespace App\Http\Requests\Dashboard;

use App\Models\FieldActivity;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * A new field activity: its name, date (WITA), place, the point officers
 * clock in around and the radius in metres, and the officers assigned.
 */
class StoreFieldActivityRequest extends FormRequest
{
    /**
     * The fields stored on the activity itself; "petugas" are its officers.
     *
     * @var list<string>
     */
    private const array FIELDS = ['nama', 'tanggal', 'tempat', 'lat', 'lng', 'radius', 'catatan'];

    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through FieldActivityPolicy.
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
            'nama' => ['required', 'string', 'max:160', new PlainText('Nama kegiatan')],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'tempat' => ['required', 'string', 'max:160', new PlainText('Tempat')],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'between:'.FieldActivity::MIN_RADIUS.','.FieldActivity::MAX_RADIUS],
            'catatan' => ['nullable', 'string', 'max:300', new PlainText('Catatan')],
            'petugas' => ['present', 'list', 'max:200'],
            'petugas.*' => ['string', 'distinct', Rule::exists('users', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kegiatan wajib diisi.',
            'nama.string' => 'Nama kegiatan tidak valid.',
            'nama.max' => 'Nama kegiatan maksimal :max karakter.',
            'tanggal.required' => 'Pilih tanggal kegiatan.',
            'tanggal.date_format' => 'Pilih tanggal kegiatan.',
            'tempat.required' => 'Tempat wajib diisi.',
            'tempat.string' => 'Tempat tidak valid.',
            'tempat.max' => 'Tempat maksimal :max karakter.',
            'lat.required' => 'Tentukan titik lokasi kegiatan.',
            'lat.numeric' => 'Lintang tidak valid.',
            'lat.between' => 'Lintang harus di antara -90 dan 90.',
            'lng.required' => 'Tentukan titik lokasi kegiatan.',
            'lng.numeric' => 'Bujur tidak valid.',
            'lng.between' => 'Bujur harus di antara -180 dan 180.',
            'radius.required' => 'Isi radius absen.',
            'radius.integer' => 'Radius dalam meter bulat.',
            'radius.between' => 'Radius :min–:max meter.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
            'petugas.present' => 'Daftar petugas wajib dikirim.',
            'petugas.list' => 'Daftar petugas tidak valid.',
            'petugas.max' => 'Maksimal :max petugas per kegiatan.',
            'petugas.*.string' => 'Petugas tidak valid.',
            'petugas.*.distinct' => 'Petugas tercantum dua kali.',
            'petugas.*.exists' => 'Petugas tidak ditemukan.',
        ];
    }

    /**
     * The validated activity as model attributes, with coordinates rounded
     * to six decimals (about 10 cm) and an empty note stored as text.
     *
     * @return array<string, mixed>
     */
    public function content(): array
    {
        $validated = $this->validated();
        $content = [];

        foreach (self::FIELDS as $field) {
            if (Arr::has($validated, $field)) {
                $content[$field] = match ($field) {
                    'lat', 'lng' => round((float) $validated[$field], 6),
                    default => $validated[$field] ?? '',
                };
            }
        }

        return $content;
    }

    /**
     * The officers assigned, or null when the request leaves them as they are.
     *
     * @return ?list<string>
     */
    public function officers(): ?array
    {
        return $this->validated('petugas');
    }
}
