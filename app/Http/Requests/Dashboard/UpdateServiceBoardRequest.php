<?php

namespace App\Http\Requests\Dashboard;

use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The complete service board: the queue number being served, the waiting
 * time and its change from yesterday, the status of each counter, and a
 * note. The server sets "diperbarui".
 */
class UpdateServiceBoardRequest extends FormRequest
{
    /**
     * Counter states, as the dashboard names them.
     *
     * @var list<string>
     */
    public const array COUNTER_STATES = ['buka', 'istirahat', 'tutup'];

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
            'nomorAntrean' => ['required', 'string', 'max:20', new PlainText('Nomor antrean')],
            'layananAntrean' => ['required', 'string', 'max:80', new PlainText('Loket & layanan')],
            'waktuTunggu' => ['required', 'integer', 'between:0,600'],
            'selisihKemarin' => ['required', 'integer', 'between:-600,600'],
            'loket' => ['present', 'list', 'max:12'],
            'loket.*' => ['required', 'array'],
            'loket.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'loket.*.kode' => ['required', 'string', 'max:6', new PlainText('Kode loket')],
            'loket.*.nama' => ['required', 'string', 'max:40', new PlainText('Nama loket')],
            'loket.*.status' => ['required', Rule::in(self::COUNTER_STATES)],
            'catatan' => ['nullable', 'string', 'max:150', new PlainText('Catatan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomorAntrean.required' => 'Nomor antrean wajib diisi.',
            'nomorAntrean.string' => 'Nomor antrean tidak valid.',
            'nomorAntrean.max' => 'Nomor antrean maksimal :max karakter.',
            'layananAntrean.required' => 'Loket & layanan wajib diisi.',
            'layananAntrean.string' => 'Loket & layanan tidak valid.',
            'layananAntrean.max' => 'Loket & layanan maksimal :max karakter.',
            'waktuTunggu.required' => 'Isi perkiraan waktu tunggu.',
            'waktuTunggu.integer' => 'Waktu tunggu harus bilangan bulat.',
            'waktuTunggu.between' => 'Waktu tunggu 0 sampai 600 menit.',
            'selisihKemarin.required' => 'Isi selisih dari kemarin.',
            'selisihKemarin.integer' => 'Selisih harus bilangan bulat.',
            'selisihKemarin.between' => 'Selisih -600 sampai 600 menit.',
            'loket.present' => 'Daftar loket wajib dikirim.',
            'loket.list' => 'Daftar loket tidak valid.',
            'loket.max' => 'Maksimal :max loket.',
            'loket.*.required' => 'Loket tidak valid.',
            'loket.*.array' => 'Loket tidak valid.',
            'loket.*.id.required' => 'Loket tidak valid.',
            'loket.*.id.string' => 'Loket tidak valid.',
            'loket.*.id.max' => 'Loket tidak valid.',
            'loket.*.id.distinct' => 'Loket tercantum dua kali.',
            'loket.*.kode.required' => 'Kode loket wajib diisi.',
            'loket.*.kode.string' => 'Kode loket tidak valid.',
            'loket.*.kode.max' => 'Kode loket maksimal :max karakter.',
            'loket.*.nama.required' => 'Nama loket wajib diisi.',
            'loket.*.nama.string' => 'Nama loket tidak valid.',
            'loket.*.nama.max' => 'Nama loket maksimal :max karakter.',
            'loket.*.status.required' => 'Pilih status loket.',
            'loket.*.status.in' => 'Status loket tidak valid.',
            'catatan.string' => 'Catatan tidak valid.',
            'catatan.max' => 'Catatan maksimal :max karakter.',
        ];
    }

    /**
     * The board as it is stored, stamped with the current time.
     *
     * @return array{nomorAntrean: string, layananAntrean: string, waktuTunggu: int, selisihKemarin: int, loket: list<array{id: string, kode: string, nama: string, status: string}>, catatan: string, diperbarui: string}
     */
    public function serviceBoard(): array
    {
        return [
            'nomorAntrean' => $this->validated('nomorAntrean'),
            'layananAntrean' => $this->validated('layananAntrean'),
            'waktuTunggu' => (int) $this->validated('waktuTunggu'),
            'selisihKemarin' => (int) $this->validated('selisihKemarin'),
            'loket' => array_map(fn (array $counter): array => [
                'id' => $counter['id'],
                'kode' => $counter['kode'],
                'nama' => $counter['nama'],
                'status' => $counter['status'],
            ], $this->validated('loket')),
            'catatan' => $this->validated('catatan') ?? '',
            'diperbarui' => now()->toIso8601String(),
        ];
    }
}
