# API PINTU

Semua endpoint ada di `routes/api.php` dan mengembalikan JSON polos (tanpa amplop `data`). Galat berbentuk `{"message": "…"}`, ditambah `errors` (pesan per isian) untuk galat validasi. Data yang dicari lewat alamat tetapi tidak ada dijawab `404` `{"message": "Data tidak ditemukan."}`. Respons dari rute API memakai `Cache-Control: no-store`.

| Metode | Alamat | Akses | Status |
|---|---|---|---|
| `GET` | `/api/health` | anonim | tersedia |
| `GET` | `/api/public/faq` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/keliling` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/jam` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/papan` | anonim, hanya baca | tersedia |
| `POST` | `/api/masuk` | halaman Login Pegawai | tersedia |
| `GET` | `/api/sesi` | pegawai login | tersedia |
| `POST` | `/api/keluar` | pegawai login | tersedia |
| `GET`, `POST` | `/api/akun` | Administrator | tersedia |
| `PATCH` | `/api/akun/{id}` | Administrator | tersedia |
| `PUT` | `/api/akun/{id}/status` | Administrator, bukan untuk akun sendiri | tersedia |
| `POST` | `/api/akun/{id}/tautan-aktivasi` | Administrator, bukan untuk akun sendiri | tersedia |
| `GET`, `POST` | `/api/koleksi/faq` | Administrator, pengelola Tanya Jawab | tersedia |
| `GET`, `PATCH` | `/api/koleksi/faq/{id}` | Administrator, pengelola Tanya Jawab | tersedia |
| `PUT` | `/api/koleksi/faq/urutan` | Administrator, pengelola Tanya Jawab | tersedia |
| `POST` | `/api/koleksi/faq/hapus` | Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/keliling` | Administrator, pengelola Samsat Keliling (daftar: juga pengelola Jadwal Pemutakhiran) | tersedia |
| `GET`, `PATCH` | `/api/koleksi/keliling/{id}` | Administrator, pengelola Samsat Keliling | tersedia |
| `POST` | `/api/koleksi/keliling/hapus` | Administrator, pengelola Samsat Keliling | tersedia |
| `GET`, `PUT` | `/api/pengaturan/jam` | Administrator, pengelola Jam Pelayanan | tersedia |
| `GET`, `PUT` | `/api/pengaturan/papan` | Administrator, pengelola Papan Layanan | tersedia |
| `GET` | `/api/pengaturan` | pegawai login | tersedia |
| `GET`, `POST` | `/api/koleksi/penyesuaian` | Administrator, pengelola Jam Pelayanan | tersedia |
| `GET`, `PATCH` | `/api/koleksi/penyesuaian/{id}` | Administrator, pengelola Jam Pelayanan | tersedia |
| `POST` | `/api/koleksi/penyesuaian/hapus` | Administrator, pengelola Jam Pelayanan | tersedia |
| `GET` | `/api/aktivitas` | pegawai login | tersedia |
| — | koleksi dan pengaturan lain di `/api/koleksi/…`, `/api/pengaturan/…` (kontrak dashboard) | pegawai login | belum dibuat, lihat [docs/dashboard.md](dashboard.md#menyambungkan-ke-server-rest-api) |

## Login pegawai (Laravel Sanctum)

Sesi berupa cookie HttpOnly milik Laravel; portal, dashboard, dan API satu origin, jadi tidak ada token di peramban ([D-007](decisions.md#d-007--2026-10-13--login-pegawai-dengan-laravel-sanctum)). Permintaan dianggap berasal dari peramban bila `Referer`/`Origin`-nya host aplikasi sendiri. Permintaan yang mengubah data wajib membawa header `X-XSRF-TOKEN` (isi cookie `XSRF-TOKEN`), yang bisa diperbarui lewat `GET /sanctum/csrf-cookie`.

| Alamat | Permintaan | Respons |
|---|---|---|
| `POST /api/masuk` | `{"nip": "18 digit", "sandi": "…", "ingat": false}` | `200` dengan data sesi. `422` bila isian tidak valid, NIP/sandi salah (pesan sama untuk keduanya), atau akun tidak aktif (hanya setelah sandi benar). `429` bila percobaan melewati 5 per NIP+IP per menit atau 20 per NIP per 15 menit (dihitung sebelum sandi diperiksa, dihapus setelah berhasil masuk), atau 30 permintaan login per IP per menit. `400` bila tidak dikirim dari peramban di origin aplikasi. `419` bila token CSRF tidak cocok. |
| `GET /api/sesi` | — | `200` dengan data sesi; `401` bila belum masuk atau akun sudah tidak aktif (sesinya diakhiri). |
| `POST /api/keluar` | — | `204`; sesi dihapus dan token CSRF diganti. |

Data sesi:

```json
{ "nip": "199001012020121001", "nama": "Maria Bria", "peran": "pegawai", "modul": ["berita", "keliling"], "masuk": "2026-10-13T06:33:17+00:00" }
```

`peran` bernilai `administrator` atau `pegawai`. `modul` berisi modul dashboard yang boleh dikelola (lihat [Modul dashboard](#modul-dashboard)); Administrator selalu mendapat semua modul. `masuk` kosong (`null`) bila waktu login belum tercatat di sesi itu. Rute `/dashboard` memakai sesi yang sama: tamu dialihkan ke `/login?lanjut=dashboard%2F`. Token bearer (`Authorization: Bearer …`) diabaikan.

Aktivasi akun memakai halaman web, bukan API: `GET` dan `POST /aktivasi/{user}?expires=…&kunci=…&signature=…`, dengan tautan dari halaman *Akun Pegawai* di dashboard, `php artisan pintu:create-admin --no-interaction`, atau `php artisan pintu:activation-link`. Tautan yang kedaluwarsa, sudah dipakai, diganti, diubah, atau untuk akun yang tidak lagi `pending` dijawab `410`. Aktivasi yang berhasil dialihkan ke `/login?aktif=1`.

## Manajemen akun (Administrator)

Dipakai halaman *Akun Pegawai* di dashboard ([D-008](decisions.md#d-008--2026-10-13--manajemen-akun-oleh-administrator)). Semua alamat memerlukan sesi login Administrator yang aktif: tamu mendapat `401`, Pegawai UPTD mendapat `403` `{"message": "Hanya Administrator yang dapat mengelola akun."}`. Kata sandi tidak pernah dikirim atau ditampilkan; pemilik akun memilihnya sendiri lewat tautan aktivasi.

Data akun:

```json
{ "id": "01kap…", "nip": "199001012020121001", "nama": "Maria Bria", "surel": "maria@uptd.test", "peran": "pegawai", "status": "active", "modul": ["berita", "keliling"], "dibuat": "2026-10-13T08:00:00+00:00", "diperbarui": "2026-10-13T08:00:00+00:00" }
```

`peran`: `administrator` atau `pegawai`. `status`: `pending` (menunggu aktivasi), `active`, `suspended` (ditangguhkan), atau `deactivated` (dinonaktifkan). `modul`: modul yang ditugaskan, tanpa duplikat dan dalam urutan tetap; untuk Administrator tidak berpengaruh karena Administrator mengelola semua modul. `surel` boleh `null`.

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/akun` | — | `200` daftar semua akun, urut nama. |
| `POST /api/akun` | `{"nip": "18 digit", "nama": "…", "surel": "…" atau null, "peran": "pegawai", "modul": ["berita"]}`; `modul` boleh tidak dikirim | `201` `{"akun": {…}, "tautan": "https://…/aktivasi/…", "kedaluwarsa": "…"}`. Akun dibuat `pending`; tautan berlaku 60 menit, sekali pakai, dan tidak bisa diminta lagi. `422` bila NIP bukan 18 digit atau sudah terdaftar, nama kosong, surel tidak valid atau dipakai akun lain, peran tidak dikenal, `modul` bukan daftar, atau ada modul yang tidak dikenal (galat per butir, mis. `modul.1`). Isian lain, seperti `status` atau `password`, diabaikan. |
| `PATCH /api/akun/{id}` | sebagian dari `{"nama", "surel", "peran", "modul"}` | `200` data akun. `modul` mengganti seluruh daftar modul; `[]` mencabut semuanya. NIP, status, dan kata sandi tidak bisa diubah di sini. `422` bila Administrator mengubah peran akunnya sendiri. |
| `PUT /api/akun/{id}/status` | `{"status": "active" \| "suspended" \| "deactivated"}` | `200` data akun. Hanya `active` → `suspended`/`deactivated`, `suspended` → `active`/`deactivated`, dan `pending` → `deactivated`; transisi lain `422`. Akun yang tidak lagi aktif kehilangan perangkat yang diingat dan keluar pada permintaan berikutnya. `403` untuk akun sendiri. |
| `POST /api/akun/{id}/tautan-aktivasi` | — | `201` seperti `POST /api/akun`. Akun menjadi `pending` dengan kata sandi baru yang tidak bisa dipakai: tautan sebelumnya, sesi, dan perangkat yang diingat tidak berlaku lagi. Untuk akun yang sudah aktif ini berarti atur ulang kata sandi; untuk akun yang dinonaktifkan, cara memakainya lagi. `403` untuk akun sendiri. |

Setiap perubahan dicatat di log aplikasi (`account.created`, `account.updated`, `account.status`, `account.activation-link`) dengan ID Administrator yang melakukannya.

### Modul dashboard

Kunci modul untuk `modul` ([D-010](decisions.md#d-010--2026-10-13--penugasan-modul-untuk-pegawai-uptd)). Setiap kunci adalah nama koleksi atau pengaturan utama modul itu di dashboard.

| Kunci | Modul | Kunci | Modul |
|---|---|---|---|
| `pesan` | Pesan & Pengaduan | `papan` | Papan Layanan |
| `statistik` | Statistik Layanan | `tarif` | Tarif & Simulasi |
| `berita` | Berita & Pengumuman | `profil` | Tentang UPTD |
| `dokumentasi` | Dokumentasi Kegiatan | `visiMisi` | Visi & Misi |
| `dokumen` | Regulasi & Unduhan | `unit` | Struktur Organisasi |
| `faq` | Tanya Jawab | `kontak` | Kontak & Kanal |
| `layanan` | Persyaratan Layanan | `beranda` | Beranda |
| `jam` | Jam Pelayanan (termasuk koleksi `penyesuaian`) | `halaman` | Halaman Statis |
| `keliling` | Samsat Keliling | `pemutakhiran` | Jadwal Pemutakhiran |

Endpoint konten dashboard (`/api/koleksi/…`, `/api/pengaturan/…`) wajib menolak pegawai yang tidak mengelola modul koleksi atau pengaturan itu. `/api/koleksi/faq`, `/api/koleksi/keliling`, `/api/koleksi/penyesuaian`, `/api/pengaturan/jam`, dan `/api/pengaturan/papan` sudah melakukannya.

## Tanya Jawab di dashboard

Dipakai halaman *Tanya Jawab* di dashboard ([D-011](decisions.md#d-011--2026-10-13--tanya-jawab-tersimpan-di-server)), mengikuti kontrak REST di [docs/dashboard.md](dashboard.md#menyambungkan-ke-server-rest-api). Tamu mendapat `401`; pegawai yang tidak mengelola modul `faq` mendapat `403` `{"message": "Modul Tanya Jawab belum ditugaskan kepada Anda."}`. Seperti semua permintaan yang mengubah data, `POST`, `PATCH`, dan `PUT` wajib membawa `X-XSRF-TOKEN`.

Data entri:

```json
{ "id": "01m4…", "pertanyaan": "Apakah pajak tahunan bisa diwakilkan?", "jawaban": "Bisa. Bawa berkas lengkap atas nama pemilik.", "status": "published", "publik": true, "tampil": true, "urutan": 0, "dibuat": "2026-10-13T08:00:00+00:00", "diperbarui": "2026-10-13T08:00:00+00:00" }
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/faq` | — | `200` semua entri, urut `urutan` lalu ID. |
| `GET /api/koleksi/faq/{id}` | — | `200` data entri; `404` bila tidak ada. |
| `POST /api/koleksi/faq` | `{"pertanyaan", "jawaban", "status", "publik", "tampil"}`, semuanya wajib | `201` data entri, ditaruh di akhir daftar. `id`, `urutan`, dan isian lain dari klien diabaikan. |
| `PATCH /api/koleksi/faq/{id}` | sebagian dari isian di atas | `200` data entri. Isian yang dikirim diperiksa dengan aturan yang sama. |
| `PUT /api/koleksi/faq/urutan` | `{"ids": ["…"]}` | `204`. Entri yang disebut ditaruh paling depan sesuai urutan daftar, sisanya menyusul dalam urutan semula. `diperbarui` tidak berubah. `422` bila ada ID ganda atau entri yang sudah tidak ada ("Urutan tidak cocok dengan data terbaru. Muat ulang halaman lalu coba lagi."). |
| `POST /api/koleksi/faq/hapus` | `{"ids": ["…"]}` | `204`. Hanya Administrator; Pegawai UPTD mendapat `403` `{"message": "Hanya Administrator yang dapat menghapus tanya jawab. Matikan \"Tampilkan di portal\" untuk menyembunyikannya."}`. ID yang sudah tidak ada dilewati. |

Aturan isian (`422` dengan pesan per isian bila dilanggar): `pertanyaan` maks. 200 dan `jawaban` maks. 800 karakter, tanpa karakter kontrol selain tab, CR, dan LF, dan minimal satu karakter terlihat. Syarat ini sama dengan syarat tampil di [`GET /api/public/faq`](#get-apipublicfaq), jadi entri dari dashboard tidak pernah ditahan di sana. Spasi dan karakter tak terlihat di awal dan akhir dipangkas. `status`: `draft`, `published`, atau `unpublished`. `publik` dan `tampil`: boolean. `ids`: daftar ULID tanpa duplikat, maks. 500.

### `GET /api/aktivitas`

Log aktivitas dashboard di server, terbaru lebih dulu, sebatas modul yang dikelola pengguna (Administrator: semua modul). `?limit=` 1–100, bawaan 50; di luar itu `422`.

```json
[
  { "id": "01m4…", "waktu": "2026-10-13T08:05:00+00:00", "oleh": "Maria Bria", "aksi": "menerbitkan", "modul": "Tanya Jawab", "target": "Apakah pajak tahunan bisa diwakilkan?" }
]
```

`aksi` untuk Tanya Jawab: `menambahkan`, `memperbarui`, `menerbitkan`, `menarik dari portal`, `mengembalikan ke draf`, `mengubah urutan` (target "N entri"), dan `menghapus` (pertanyaannya, atau "N entri"). Perubahan dan catatannya disimpan dalam satu transaksi; penyimpanan tanpa perubahan tidak dicatat. `target` dipendekkan menjadi 120 karakter. Tabel `activities`: `id` (ULID), `user_id`, `module` (kunci modul), `action`, `target`, `created_at`.

Kode: `app/Http/Controllers/Dashboard/`, `app/Http/Requests/Dashboard/`, `app/Policies/FaqPolicy.php`, `app/Rules/FaqText.php`, `app/Actions/RecordActivity.php`, `app/Http/Resources/FaqResource.php`, `app/Http/Resources/ActivityResource.php`. Tes: `tests/Feature/FaqControllerTest.php`, `FaqOrderControllerTest.php`, `FaqDeletionControllerTest.php`, `FaqPolicyTest.php`, `ActivityControllerTest.php`.

## Samsat Keliling di dashboard

Dipakai halaman *Samsat Keliling* di dashboard ([D-013](decisions.md#d-013--2026-10-13--samsat-keliling-tersimpan-di-server-batas-laju-api-publik-300)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `keliling` mendapat `403` `{"message": "Modul Samsat Keliling belum ditugaskan kepada Anda."}`. Pengelola Jadwal Pemutakhiran boleh memakai `GET /api/koleksi/keliling` saja.

Data jadwal:

```json
{ "id": "01m4…", "hari": 1, "mulai": "08:00", "selesai": "12:00", "lokasi": "Kantor Kelurahan Oebobo", "kecamatan": "Kec. Oebobo", "layanan": "Pajak tahunan", "catatan": "", "aktif": true, "urutan": 0, "dibuat": "2026-10-13T08:00:00+00:00", "diperbarui": "2026-10-13T08:00:00+00:00" }
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/keliling` | — | `200` semua jadwal, urut hari lalu jam. |
| `GET /api/koleksi/keliling/{id}` | — | `200` data jadwal; `404` bila tidak ada. |
| `POST /api/koleksi/keliling` | `{"hari", "mulai", "selesai", "lokasi", "kecamatan", "layanan", "aktif"}` wajib, `"catatan"` boleh kosong | `201` data jadwal. `id` dan `urutan` dari klien diabaikan. |
| `PATCH /api/koleksi/keliling/{id}` | sebagian dari isian di atas | `200` data jadwal. Jam selesai dibandingkan dengan jam mulai yang akan tersimpan. |
| `POST /api/koleksi/keliling/hapus` | `{"ids": ["…"]}` | `204`. ID yang sudah tidak ada dilewati. |

Aturan isian (`422` dengan pesan per isian): `hari` 1 (Senin) sampai 6 (Sabtu); `mulai` dan `selesai` berformat `HH:MM`, selesai setelah mulai; `lokasi` maks. 120, `kecamatan` maks. 60, `layanan` maks. 80, dan `catatan` maks. 300 karakter, teks polos tanpa karakter kontrol dan berisi teks yang terlihat; `aktif` boolean. Catatan kosong disimpan sebagai `""`. Log aktivitas memakai aksi `menambahkan`, `memperbarui`, dan `menghapus` dengan target seperti "Senin · Kantor Kelurahan Oebobo".

Kode: `app/Http/Controllers/Dashboard/MobileServiceStop*`, `app/Http/Requests/Dashboard/*MobileServiceStop*`, `app/Policies/MobileServiceStopPolicy.php`, `app/Models/MobileServiceStop.php`, `app/Actions/DeleteEntries.php`. Tes: `tests/Feature/MobileServiceStopControllerTest.php`, `MobileServiceStopDeletionControllerTest.php`.

## Jam Pelayanan di dashboard

Dipakai halaman *Jam Pelayanan* di dashboard ([D-014](decisions.md#d-014--2026-10-13--jam-pelayanan-tersimpan-di-server)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `jam` mendapat `403` `{"message": "Modul Jam Pelayanan belum ditugaskan kepada Anda."}`.

Dokumen jam pelayanan (`GET` dan `PUT /api/pengaturan/jam`):

```json
{
  "hari": [{ "hari": 1, "buka": true, "mulai": "08:00", "selesai": "15:00", "catatan": "Berkas s.d. 14.00" }, "… tujuh hari, 0 = Minggu & libur"],
  "batasBerkasMenit": 60,
  "catatan": "Antrean biasanya paling ramai pukul 09.00–11.00.",
  "pemberitahuan": "Perubahan jadwal diumumkan paling lambat H-3."
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/jam` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: semua hari tutup, batas 60 menit, catatan kosong. |
| `PUT /api/pengaturan/jam` | dokumen lengkap | `200` dokumen tersimpan: hari urut Senin–Minggu, jam hari tutup dikosongkan, catatan kosong menjadi `""`. `422` bila tidak tepat tujuh hari berbeda, hari buka tanpa jam buka/tutup atau tutup sebelum buka (galat per butir, mis. `hari.1.selesai`), batas berkas di luar 0–240 menit, atau teks lebih panjang dari 100 (catatan hari), 300 (catatan untuk warga), atau 200 (pemberitahuan) karakter. |
| `GET /api/pengaturan` | — | `200` waktu terakhir tiap pengaturan server disimpan, mis. `{"jam": "2026-10-13T08:00:00+00:00"}`; `{}` bila belum ada. Untuk semua pegawai login. |
| `GET /api/koleksi/penyesuaian` | — | `200` semua penyesuaian, urut tanggal. |
| `GET /api/koleksi/penyesuaian/{id}` | — | `200` satu penyesuaian; `404` bila tidak ada. |
| `POST /api/koleksi/penyesuaian` | `{"tanggal": "2026-12-25", "jenis": "tutup" \| "jam-khusus", "mulai", "selesai", "keterangan"}` | `201`. Jam wajib untuk `jam-khusus` (tutup setelah buka) dan dikosongkan untuk `tutup`. `422` bila tanggal sudah punya penyesuaian ("Sudah ada penyesuaian untuk tanggal ini."). |
| `PATCH /api/koleksi/penyesuaian/{id}` | sebagian dari isian di atas | `200`. Jam dibandingkan dengan nilai yang akan tersimpan. |
| `POST /api/koleksi/penyesuaian/hapus` | `{"ids": ["…"]}` | `204`. ID yang sudah tidak ada dilewati. |

Penyimpanan jam dicatat di log aktivitas sebagai "memperbarui Jam Pelayanan" (hanya bila ada perubahan); penyesuaian sebagai "menambahkan/memperbarui/menghapus 2026-12-25 · Hari Raya Natal".

Kode: `app/Http/Controllers/Dashboard/OpeningHoursController.php`, `SettingTimestampController.php`, `ScheduleAdjustment*`, `app/Models/Setting.php`, `app/Models/ScheduleAdjustment.php`, `app/Policies/SettingPolicy.php`, `ScheduleAdjustmentPolicy.php`, `app/Actions/SaveOpeningHours.php`. Tes: `tests/Feature/OpeningHoursControllerTest.php`, `ScheduleAdjustmentControllerTest.php`.

## Papan Layanan di dashboard

Dipakai halaman *Papan Layanan* di dashboard ([D-015](decisions.md#d-015--2026-10-13--papan-layanan-tersimpan-di-server)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `papan` mendapat `403` `{"message": "Modul Papan Layanan belum ditugaskan kepada Anda."}`.

```json
{
  "nomorAntrean": "A-042",
  "layananAntrean": "Loket 1 · Pajak tahunan",
  "waktuTunggu": 18,
  "selisihKemarin": -6,
  "loket": [{ "id": "…", "kode": "L1", "nama": "Pajak tahunan", "status": "buka" }],
  "catatan": "Diperbarui petugas setiap hari kerja.",
  "diperbarui": "2026-10-13T23:45:00+00:00"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/papan` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: isian kosong, `loket` `[]`, `diperbarui` `null`. |
| `PUT /api/pengaturan/papan` | dokumen lengkap; `diperbarui` diabaikan bila dikirim | `200` dokumen tersimpan dengan `diperbarui` dari server. Isi yang sama dengan yang tersimpan tidak mengubah `diperbarui` dan tidak dicatat. `422` bila nomor antrean (maks. 20 karakter) atau loket & layanan (maks. 80) kosong, waktu tunggu di luar 0–600 menit, selisih di luar -600–600, lebih dari 12 loket, loket tanpa kode (maks. 6) atau nama (maks. 40), status selain `buka`, `istirahat`, `tutup`, ID loket ganda, atau catatan lebih dari 150 karakter. |

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Papan Layanan".

Kode: `app/Http/Controllers/Dashboard/ServiceBoardController.php`, `app/Http/Requests/Dashboard/UpdateServiceBoardRequest.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/ServiceBoardControllerTest.php`.

## `GET /api/public/faq`

Daftar tanya jawab untuk portal publik. Menjalankan keputusan pemilik produk GC18-01–09 ([Phase 2.19](phases/PHASE-2.19-GC18-DECISION-BINDING-AND-CLOSURE-EVIDENCE-COORDINATION.md)), dengan urutan menurut [D-003](decisions.md#d-003--2026-10-13--urutan-faq-publik-mengikuti-dashboard). Bagian Tanya Jawab di beranda memuatnya setiap kali halaman dibuka (`initFaq()` di `public/assets/js/main.js`, [D-012](decisions.md#d-012--2026-10-13--beranda-menampilkan-tanya-jawab-dari-api-publik)), jadi setiap kunjungan beranda ikut dihitung dalam batas laju di bawah.

```json
[
  { "pertanyaan": "Apakah pajak tahunan bisa diwakilkan?", "jawaban": "Bisa. Bawa berkas lengkap atas nama pemilik." }
]
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`, array berisi tepat `pertanyaan` dan `jawaban`; `[]` bila tidak ada yang layak tampil. Tanpa ID atau kolom internal. |
| Kelayakan | `status = published` DAN `publik = true` DAN `tampil = true`. Disaring di SQL, lalu status diperiksa ulang persis di PHP karena collation MySQL menganggap `Published` atau `published ` sama dengan `published`. |
| Validitas teks | UTF-8 valid; pertanyaan maks. 200 dan jawaban maks. 800 karakter; tanpa karakter kontrol selain tab, CR, LF; minimal satu karakter terlihat (bukan hanya spasi, karakter format atau private-use, tanda gabung, atau pengisi kosong). Entri yang gagal **ditahan**, tidak dipotong atau dirapikan, dan ID-nya (bukan isinya) dicatat sebagai peringatan maksimal sekali per 5 menit. |
| Teks polos | Teks dikirim persis seperti tersimpan. Markup dan URL tidak dihapus, tidak di-escape dua kali, dan tidak dijadikan tautan; konsumen wajib menampilkannya sebagai teks. |
| Urutan | `urutan` naik, lalu ID internal (ULID). |
| Batas | Koleksi lengkap tanpa paginasi. Lebih dari `PINTU_PUBLIC_FAQ_MAX_ITEMS` (bawaan 100) entri layak → `503`, tidak pernah daftar terpotong. Entri yang ditahan tetap dihitung. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah `PINTU_PUBLIC_API_REQUESTS_PER_MINUTE` (bawaan 300, [D-013](decisions.md#d-013--2026-10-13--samsat-keliling-tersimpan-di-server-batas-laju-api-publik-300)) permintaan per menit per IP, dipakai bersama semua API publik; `503` bila tabel tidak bisa dibaca atau batas terlampaui. Penyebab `503` dicatat maksimal sekali per menit. |
| Cache | `Cache-Control: no-store` termasuk untuk galat `422`/`429`/`503`. `404`/`405` dari router, preflight CORS, dan mode pemeliharaan memakai bawaan Laravel `no-cache, private`. Tanpa `ETag`/`Last-Modified`, jadi tidak pernah `304`. |
| CORS | Hanya origin persis (`scheme://host[:port]`) dari `CORS_ALLOWED_ORIGINS`; wildcard, path, dan garis miring di akhir diabaikan. Kosong = tidak ada akses lintas origin dari peramban. Portal dan dashboard satu origin dengan API, jadi tidak memerlukannya. |

### Tabel `faqs`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | ULID | kunci internal, tidak tampil di publik |
| `pertanyaan`, `jawaban` | text | teks polos |
| `status` | string | `draft`, `published`, `unpublished` (bawaan `draft`) |
| `publik` | boolean | maksud eksplisit bahwa konten boleh publik (bawaan `false`) |
| `tampil` | boolean | sakelar tampil di dashboard (bawaan `true`) |
| `urutan` | unsigned int | urutan seret-lepas dashboard |
| `created_at`, `updated_at` | timestamp | |

Kode: `app/Http/Controllers/PublicFaqController.php`, `app/Actions/ListPublicFaqs.php`, `app/Models/Faq.php`, `app/Enums/FaqStatus.php`, `app/Http/Middleware/PreventResponseCaching.php`, `config/pintu.php`, `config/cors.php`. Tes: `tests/Feature/PublicFaqControllerTest.php`, `tests/Unit/Models/FaqTest.php`, `tests/Unit/Config/EnvironmentConfigTest.php`.

## `GET /api/public/keliling`

Jadwal Samsat Keliling untuk portal publik ([D-013](decisions.md#d-013--2026-10-13--samsat-keliling-tersimpan-di-server-batas-laju-api-publik-300)). Beranda dan halaman Jadwal memuatnya setiap kali dibuka (`initKeliling()` di `public/assets/js/main.js`).

```json
[
  { "hari": 1, "mulai": "08:00", "selesai": "12:00", "lokasi": "Kantor Kelurahan Oebobo", "kecamatan": "Kec. Oebobo", "layanan": "Pajak tahunan", "catatan": "" }
]
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`, jadwal yang `aktif` saja, urut `hari`, `mulai`, `selesai`, lalu ID internal; `[]` bila tidak ada. Tanpa ID, `aktif`, `urutan`, atau waktu simpan. Teks dikirim persis seperti tersimpan dan wajib ditampilkan sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila tabel tidak bisa dibaca, tidak pernah daftar kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicMobileServiceStopController.php`, `app/Actions/ListPublicMobileServiceStops.php`. Tes: `tests/Feature/PublicMobileServiceStopControllerTest.php`.

## `GET /api/public/jam`

Jam loket untuk portal publik ([D-014](decisions.md#d-014--2026-10-13--jam-pelayanan-tersimpan-di-server)). Semua halaman portal memuatnya untuk status loket, hitung mundur, dan footer (`initStatus()` di `public/assets/js/main.js`).

```json
{
  "hari": [{ "hari": 1, "buka": true, "mulai": "08:00", "selesai": "15:00", "catatan": "Berkas s.d. 14.00" }],
  "batasBerkasMenit": 60,
  "catatan": "…",
  "pemberitahuan": "…",
  "penyesuaian": [{ "tanggal": "2026-12-25", "jenis": "tutup", "mulai": "", "selesai": "", "keterangan": "Hari Raya Natal" }]
}
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. `hari` kosong dan batas `0` bila jam loket belum pernah disimpan. `penyesuaian` berisi penyesuaian mulai hari ini menurut waktu Kupang (WITA), urut tanggal, maks. 100. Teks wajib ditampilkan sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah jam tebakan. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicOpeningHoursController.php`, `app/Actions/ShowPublicOpeningHours.php`. Tes: `tests/Feature/PublicOpeningHoursControllerTest.php`.

## `GET /api/public/papan`

Papan layanan untuk beranda portal ([D-015](decisions.md#d-015--2026-10-13--papan-layanan-tersimpan-di-server), `initPapan()` di `public/assets/js/main.js`). Bentuknya sama dengan dokumen dashboard, tanpa `id` loket; `diperbarui` `null` bila papan belum pernah disimpan.

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. Portal menampilkan nomor antrean, waktu tunggu, selisih, dan status loket hanya bila `diperbarui` jatuh pada hari ini (WITA); selain itu hanya waktu pembaruan terakhir. Teks wajib ditampilkan sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah papan kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicServiceBoardController.php`, `app/Actions/ShowPublicServiceBoard.php`. Tes: `tests/Feature/PublicServiceBoardControllerTest.php`.
