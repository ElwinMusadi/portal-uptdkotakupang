<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Setting;
use App\Rules\MapPoint;
use App\Rules\PlainText;
use App\Rules\PortalLink;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete contact document: the office and its address, phone,
 * WhatsApp and email, social media accounts, the map link and point, the
 * reply promise and the official complaint channels.
 */
class UpdateContactRequest extends FormRequest
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
            'namaKantor' => ['required', 'string', 'max:120', new PlainText('Nama kantor')],
            'alamat' => ['required', 'string', 'max:200', new PlainText('Alamat')],
            'kota' => ['nullable', 'string', 'max:80', new PlainText('Kota')],
            'provinsi' => ['nullable', 'string', 'max:80', new PlainText('Provinsi')],
            'telepon' => ['nullable', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6})\+?[\d ().-]+\z/'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){9})\+?[\d ().-]+\z/'],
            'whatsappCatatan' => ['nullable', 'string', 'max:80', new PlainText('Catatan WhatsApp')],
            'email' => ['nullable', 'string', 'max:120', 'email'],
            'mediaSosial' => ['present', 'list', 'max:8'],
            'mediaSosial.*' => ['required', 'array'],
            'mediaSosial.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'mediaSosial.*.platform' => ['required', 'string', 'max:40', new PlainText('Platform')],
            'mediaSosial.*.akun' => ['required', 'string', 'max:60', new PlainText('Akun')],
            'mediaSosial.*.url' => ['nullable', 'string', 'max:300', new PortalLink('Tautan akun')],
            'mapsUrl' => ['nullable', 'string', 'max:500', new PortalLink('Tautan Google Maps')],
            'titikPeta' => ['nullable', 'string', 'max:60', new MapPoint],
            'catatanLokasi' => ['nullable', 'string', 'max:120', new PlainText('Catatan lokasi')],
            'janjiBalasan' => ['nullable', 'string', 'max:200', new PlainText('Janji waktu balasan')],
            'kanalPengaduan' => ['present', 'list', 'max:8'],
            'kanalPengaduan.*' => ['required', 'array'],
            'kanalPengaduan.*.id' => ['required', 'string', 'max:40', 'distinct'],
            'kanalPengaduan.*.nama' => ['required', 'string', 'max:80', new PlainText('Nama kanal')],
            'kanalPengaduan.*.deskripsi' => ['nullable', 'string', 'max:300', new PlainText('Penjelasan')],
            'kanalPengaduan.*.tautanLabel' => ['nullable', 'string', 'max:60', new PlainText('Label tautan')],
            'kanalPengaduan.*.tautanUrl' => ['nullable', 'string', 'max:300', new PortalLink('Alamat tautan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'namaKantor.required' => 'Nama kantor wajib diisi.',
            'namaKantor.string' => 'Nama kantor tidak valid.',
            'namaKantor.max' => 'Nama kantor maksimal :max karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat tidak valid.',
            'alamat.max' => 'Alamat maksimal :max karakter.',
            'kota.string' => 'Kota tidak valid.',
            'kota.max' => 'Kota maksimal :max karakter.',
            'provinsi.string' => 'Provinsi tidak valid.',
            'provinsi.max' => 'Provinsi maksimal :max karakter.',
            'telepon.string' => 'Nomor telepon tidak valid.',
            'telepon.max' => 'Nomor telepon maksimal :max karakter.',
            'telepon.regex' => 'Nomor telepon berisi minimal 6 angka, boleh dengan spasi, tanda kurung, titik, tanda hubung, dan + di depan.',
            'whatsapp.string' => 'Nomor WhatsApp tidak valid.',
            'whatsapp.max' => 'Nomor WhatsApp maksimal :max karakter.',
            'whatsapp.regex' => 'Nomor WhatsApp berisi minimal 9 angka, boleh dengan spasi, tanda kurung, titik, tanda hubung, dan + di depan.',
            'whatsappCatatan.string' => 'Catatan WhatsApp tidak valid.',
            'whatsappCatatan.max' => 'Catatan WhatsApp maksimal :max karakter.',
            'email.string' => 'Format email belum benar.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.email' => 'Format email belum benar.',
            'mediaSosial.present' => 'Daftar media sosial wajib dikirim.',
            'mediaSosial.list' => 'Daftar media sosial tidak valid.',
            'mediaSosial.max' => 'Maksimal :max akun media sosial.',
            'mediaSosial.*.required' => 'Akun media sosial tidak valid.',
            'mediaSosial.*.array' => 'Akun media sosial tidak valid.',
            'mediaSosial.*.id.required' => 'Akun media sosial tidak valid.',
            'mediaSosial.*.id.string' => 'Akun media sosial tidak valid.',
            'mediaSosial.*.id.max' => 'Akun media sosial tidak valid.',
            'mediaSosial.*.id.distinct' => 'Akun media sosial tercantum dua kali.',
            'mediaSosial.*.platform.required' => 'Platform wajib diisi.',
            'mediaSosial.*.platform.string' => 'Platform tidak valid.',
            'mediaSosial.*.platform.max' => 'Platform maksimal :max karakter.',
            'mediaSosial.*.akun.required' => 'Akun wajib diisi.',
            'mediaSosial.*.akun.string' => 'Akun tidak valid.',
            'mediaSosial.*.akun.max' => 'Akun maksimal :max karakter.',
            'mediaSosial.*.url.string' => 'Tautan akun tidak valid.',
            'mediaSosial.*.url.max' => 'Tautan akun maksimal :max karakter.',
            'mapsUrl.string' => 'Tautan Google Maps tidak valid.',
            'mapsUrl.max' => 'Tautan Google Maps maksimal :max karakter.',
            'titikPeta.string' => 'Titik peta tidak valid.',
            'titikPeta.max' => 'Titik peta maksimal :max karakter.',
            'catatanLokasi.string' => 'Catatan lokasi tidak valid.',
            'catatanLokasi.max' => 'Catatan lokasi maksimal :max karakter.',
            'janjiBalasan.string' => 'Janji waktu balasan tidak valid.',
            'janjiBalasan.max' => 'Janji waktu balasan maksimal :max karakter.',
            'kanalPengaduan.present' => 'Daftar kanal pengaduan wajib dikirim.',
            'kanalPengaduan.list' => 'Daftar kanal pengaduan tidak valid.',
            'kanalPengaduan.max' => 'Maksimal :max kanal pengaduan.',
            'kanalPengaduan.*.required' => 'Kanal pengaduan tidak valid.',
            'kanalPengaduan.*.array' => 'Kanal pengaduan tidak valid.',
            'kanalPengaduan.*.id.required' => 'Kanal pengaduan tidak valid.',
            'kanalPengaduan.*.id.string' => 'Kanal pengaduan tidak valid.',
            'kanalPengaduan.*.id.max' => 'Kanal pengaduan tidak valid.',
            'kanalPengaduan.*.id.distinct' => 'Kanal pengaduan tercantum dua kali.',
            'kanalPengaduan.*.nama.required' => 'Nama kanal wajib diisi.',
            'kanalPengaduan.*.nama.string' => 'Nama kanal tidak valid.',
            'kanalPengaduan.*.nama.max' => 'Nama kanal maksimal :max karakter.',
            'kanalPengaduan.*.deskripsi.string' => 'Penjelasan tidak valid.',
            'kanalPengaduan.*.deskripsi.max' => 'Penjelasan maksimal :max karakter.',
            'kanalPengaduan.*.tautanLabel.string' => 'Label tautan tidak valid.',
            'kanalPengaduan.*.tautanLabel.max' => 'Label tautan maksimal :max karakter.',
            'kanalPengaduan.*.tautanUrl.string' => 'Alamat tautan tidak valid.',
            'kanalPengaduan.*.tautanUrl.max' => 'Alamat tautan maksimal :max karakter.',
        ];
    }

    /**
     * The contact document as it is stored, with empty optional fields as
     * empty strings. A map point left out (a dashboard from before D-063)
     * keeps the stored one; an empty one turns the Google map off.
     *
     * @return array{namaKantor: string, alamat: string, kota: string, provinsi: string, telepon: string, whatsapp: string, whatsappCatatan: string, email: string, mediaSosial: list<array{id: string, platform: string, akun: string, url: string}>, mapsUrl: string, titikPeta: string, catatanLokasi: string, janjiBalasan: string, kanalPengaduan: list<array{id: string, nama: string, deskripsi: string, tautanLabel: string, tautanUrl: string}>}
     */
    public function contact(): array
    {
        return [
            'namaKantor' => $this->validated('namaKantor'),
            'alamat' => $this->validated('alamat'),
            'kota' => $this->validated('kota') ?? '',
            'provinsi' => $this->validated('provinsi') ?? '',
            'telepon' => $this->validated('telepon') ?? '',
            'whatsapp' => $this->validated('whatsapp') ?? '',
            'whatsappCatatan' => $this->validated('whatsappCatatan') ?? '',
            'email' => $this->validated('email') ?? '',
            'mediaSosial' => array_map(fn (array $account): array => [
                'id' => $account['id'],
                'platform' => $account['platform'],
                'akun' => $account['akun'],
                'url' => $account['url'] ?? '',
            ], $this->validated('mediaSosial')),
            'mapsUrl' => $this->validated('mapsUrl') ?? '',
            'titikPeta' => match (true) {
                ! $this->has('titikPeta') => Setting::contact()['titikPeta'],
                filled($this->validated('titikPeta')) => MapPoint::normalize($this->validated('titikPeta')),
                default => '',
            },
            'catatanLokasi' => $this->validated('catatanLokasi') ?? '',
            'janjiBalasan' => $this->validated('janjiBalasan') ?? '',
            'kanalPengaduan' => array_map(fn (array $channel): array => [
                'id' => $channel['id'],
                'nama' => $channel['nama'],
                'deskripsi' => $channel['deskripsi'] ?? '',
                'tautanLabel' => $channel['tautanLabel'] ?? '',
                'tautanUrl' => $channel['tautanUrl'] ?? '',
            ], $this->validated('kanalPengaduan')),
        ];
    }
}
