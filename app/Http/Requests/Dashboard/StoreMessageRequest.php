<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Message;
use App\Rules\ContactAddress;
use App\Rules\PlainText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A message staff record from another channel (suggestion box, SP4N-LAPOR!,
 * WhatsApp, telephone). The server sets the ticket, status and time.
 */
class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request. The route
     * authorizes it through MessagePolicy.
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
            'jenis' => ['required', Rule::in(Message::KINDS)],
            'nama' => ['nullable', 'string', 'max:100', new PlainText('Nama')],
            'kontak' => ['nullable', 'string', 'max:120', new ContactAddress],
            'layanan' => ['required', Rule::in(Message::SERVICES)],
            'isi' => ['required', 'string', 'max:2000', new PlainText('Isi pesan')],
            'sumber' => ['required', Rule::in(Message::SOURCES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Pilih jenis pesan.',
            'jenis.in' => 'Jenis pesan tidak valid.',
            'nama.string' => 'Nama tidak valid.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'kontak.required' => 'Isi email atau nomor WhatsApp agar kami bisa membalas.',
            'kontak.string' => 'Kontak tidak valid.',
            'kontak.max' => 'Kontak maksimal :max karakter.',
            'layanan.required' => 'Pilih layanan terkait.',
            'layanan.in' => 'Layanan tidak valid.',
            'isi.required' => 'Isi pesan wajib diisi.',
            'isi.string' => 'Isi pesan tidak valid.',
            'isi.min' => 'Pesan minimal :min karakter.',
            'isi.max' => 'Pesan maksimal :max karakter.',
            'sumber.required' => 'Pilih sumber pesan.',
            'sumber.in' => 'Sumber pesan tidak valid.',
        ];
    }

    /**
     * The sender's fields, with empty optional ones as empty strings.
     *
     * @return array{jenis: string, nama: string, kontak: string, layanan: string, isi: string}
     */
    public function messageFields(): array
    {
        return [
            'jenis' => $this->validated('jenis'),
            'nama' => $this->validated('nama') ?? '',
            'kontak' => $this->validated('kontak') ?? '',
            'layanan' => $this->validated('layanan'),
            'isi' => $this->validated('isi'),
        ];
    }
}
