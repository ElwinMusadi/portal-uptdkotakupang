# API PINTU

Semua endpoint ada di `routes/api.php` dan mengembalikan JSON polos (tanpa amplop `data`). Galat berbentuk `{"message": "…"}`, ditambah `errors` (pesan per isian) untuk galat validasi. Data yang dicari lewat alamat tetapi tidak ada dijawab `404` `{"message": "Data tidak ditemukan."}`. Respons dari rute API memakai `Cache-Control: no-store`.

| Metode | Alamat | Akses | Status |
|---|---|---|---|
| `GET` | `/api/health` | anonim | tersedia |
| `GET` | `/api/public/faq` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/keliling` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/jam` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/papan` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/statistik` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/kontak` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/visi-misi` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/profil` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/layanan` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/dokumen` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/berita`, `/api/public/berita/{slug}` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/dokumentasi` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/struktur` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/beranda` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/halaman/{slug}` | anonim, hanya baca | tersedia |
| `GET` | `/api/public/situs` | anonim, hanya baca | tersedia |
| `POST` | `/api/public/pesan` | anonim, formulir Kontak | tersedia |
| `POST` | `/api/public/kendaraan` | anonim, cek biaya pajak | tersedia |
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
| `GET`, `POST` | `/api/koleksi/keliling` | Administrator, pengelola Layanan Luar Kantor (daftar: juga pengelola Jadwal Pemutakhiran) | tersedia |
| `GET`, `PATCH` | `/api/koleksi/keliling/{id}` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `POST` | `/api/koleksi/keliling/hapus` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `POST` | `/api/koleksi/keliling/salin-minggu` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `POST` | `/api/koleksi/keliling/salin-hari` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `GET`, `POST` | `/api/koleksi/lokasi` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `GET`, `PATCH` | `/api/koleksi/lokasi/{id}` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `POST` | `/api/koleksi/lokasi/hapus` | Administrator, pengelola Layanan Luar Kantor | tersedia |
| `GET`, `PUT` | `/api/pengaturan/jam` | Administrator, pengelola Jam Pelayanan | tersedia |
| `GET`, `PUT` | `/api/pengaturan/papan` | Administrator, pengelola Papan Layanan | tersedia |
| `GET`, `PUT` | `/api/pengaturan/statistik` | Administrator, pengelola Statistik Layanan | tersedia |
| `GET`, `PUT` | `/api/pengaturan/kontak` | Administrator, pengelola Kontak & Kanal | tersedia |
| `GET`, `PUT` | `/api/pengaturan/visiMisi` | Administrator, pengelola Visi & Misi | tersedia |
| `GET`, `PUT` | `/api/pengaturan/profil` | Administrator, pengelola Tentang UPTD | tersedia |
| `GET`, `PUT` | `/api/pengaturan/beranda` | Administrator, pengelola Beranda | tersedia |
| `GET` | `/api/pengaturan/situs` | pegawai login | tersedia |
| `PUT` | `/api/pengaturan/situs` | Administrator | tersedia |
| `GET`, `PUT` | `/api/pengaturan/akun` | pegawai login, untuk akunnya sendiri | tersedia |
| `GET` | `/api/kendaraan/{nopol}` | pegawai login | tersedia |
| `POST` | `/api/unggah` | pengelola modul tujuan; foto absensi: semua pegawai | tersedia |
| `GET` | `/api/berkas/{id}` | pengunggah berkas privat, Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/pesan` | Administrator, pengelola Pesan & Pengaduan | tersedia |
| `GET`, `PATCH` | `/api/koleksi/pesan/{id}` | Administrator, pengelola Pesan & Pengaduan | tersedia |
| `POST` | `/api/koleksi/pesan/hapus` | Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/layanan` | Administrator, pengelola Persyaratan Layanan (daftar: juga pengelola Jadwal Pemutakhiran) | tersedia |
| `GET`, `PATCH` | `/api/koleksi/layanan/{id}` | Administrator, pengelola Persyaratan Layanan; layanan yang menunggu persetujuan atau terbit hanya Administrator | tersedia |
| `PUT` | `/api/koleksi/layanan/urutan` | Administrator | tersedia |
| `POST` | `/api/koleksi/layanan/hapus` | Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/dokumen` | Administrator, pengelola Regulasi & Unduhan (daftar: juga pengelola Jadwal Pemutakhiran) | tersedia |
| `GET`, `PATCH` | `/api/koleksi/dokumen/{id}` | Administrator, pengelola Regulasi & Unduhan; dokumen yang menunggu persetujuan atau terbit hanya Administrator | tersedia |
| `POST` | `/api/koleksi/dokumen/hapus` | Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/berita` | Administrator, pengelola Berita & Pengumuman (daftar: juga pengelola Jadwal Pemutakhiran) | tersedia |
| `GET`, `PATCH` | `/api/koleksi/berita/{id}` | Administrator, pengelola Berita & Pengumuman, termasuk tulisan yang terbit | tersedia |
| `POST` | `/api/koleksi/berita/hapus` | Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/dokumentasi` | Administrator, pengelola Dokumentasi Kegiatan (daftar: juga pengelola Jadwal Pemutakhiran) | tersedia |
| `GET`, `PATCH` | `/api/koleksi/dokumentasi/{id}` | Administrator, pengelola Dokumentasi Kegiatan, termasuk kegiatan yang terbit | tersedia |
| `POST` | `/api/koleksi/dokumentasi/hapus` | Administrator | tersedia |
| `GET`, `POST` | `/api/koleksi/unit` | Administrator, pengelola Struktur Organisasi | tersedia |
| `GET`, `PATCH` | `/api/koleksi/unit/{id}` | Administrator, pengelola Struktur Organisasi | tersedia |
| `PUT` | `/api/koleksi/unit/urutan` | Administrator, pengelola Struktur Organisasi | tersedia |
| `POST` | `/api/koleksi/unit/hapus` | Administrator, pengelola Struktur Organisasi | tersedia |
| `GET` | `/api/koleksi/halaman` | Administrator, pengelola Halaman Statis | tersedia |
| `GET`, `PATCH` | `/api/koleksi/halaman/{id}` | Administrator, pengelola Halaman Statis | tersedia |
| `GET` | `/api/koleksi/pemutakhiran` | semua pegawai; daftarnya sebatas modul pengguna, lengkap bagi Administrator dan pengelola Jadwal Pemutakhiran | tersedia |
| `POST` | `/api/koleksi/pemutakhiran` | Administrator, pengelola Jadwal Pemutakhiran | tersedia |
| `GET`, `PATCH` | `/api/koleksi/pemutakhiran/{id}` | baca: juga pengelola modul standar itu; ubah: Administrator, pengelola Jadwal Pemutakhiran | tersedia |
| `PUT` | `/api/koleksi/pemutakhiran/urutan` | Administrator, pengelola Jadwal Pemutakhiran | tersedia |
| `POST` | `/api/koleksi/pemutakhiran/hapus` | Administrator, pengelola Jadwal Pemutakhiran | tersedia |
| `GET` | `/api/pengaturan` | pegawai login | tersedia |
| `GET`, `POST` | `/api/koleksi/penyesuaian` | Administrator, pengelola Jam Pelayanan | tersedia |
| `GET`, `PATCH` | `/api/koleksi/penyesuaian/{id}` | Administrator, pengelola Jam Pelayanan | tersedia |
| `POST` | `/api/koleksi/penyesuaian/hapus` | Administrator, pengelola Jam Pelayanan | tersedia |
| `GET`, `POST` | `/api/absensi/kegiatan` | Administrator | tersedia |
| `GET`, `PATCH` | `/api/absensi/kegiatan/{id}` | Administrator | tersedia |
| `POST` | `/api/absensi/kegiatan/hapus` | Administrator | tersedia |
| `GET` | `/api/absen` | pegawai login | tersedia |
| `POST` | `/api/absen/{id}` | petugas yang ditugaskan di kegiatan itu | tersedia |
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
| `POST /api/akun` | `{"nip": "18 digit", "nama": "…", "surel": "…" atau null, "peran": "pegawai", "modul": ["berita"]}`; `modul` boleh tidak dikirim | `201` `{"akun": {…}, "tautan": "https://…/aktivasi/…", "kedaluwarsa": "…"}`. Akun dibuat `pending`; tautan berlaku 60 menit, sekali pakai, dan tidak bisa diminta lagi. `422` bila NIP bukan 18 digit atau sudah terdaftar, nama kosong, surel tidak valid atau dipakai akun lain, peran tidak dikenal, `modul` bukan daftar, atau ada modul yang tidak dikenal atau sudah dipensiunkan (galat per butir, mis. `modul.1`). Isian lain, seperti `status` atau `password`, diabaikan. |
| `PATCH /api/akun/{id}` | sebagian dari `{"nama", "surel", "peran", "modul"}` | `200` data akun. `modul` mengganti seluruh daftar modul; `[]` mencabut semuanya. NIP, status, dan kata sandi tidak bisa diubah di sini. `422` bila Administrator mengubah peran akunnya sendiri. |
| `PUT /api/akun/{id}/status` | `{"status": "active" \| "suspended" \| "deactivated"}` | `200` data akun. Hanya `active` → `suspended`/`deactivated`, `suspended` → `active`/`deactivated`, dan `pending` → `deactivated`; transisi lain `422`. Akun yang tidak lagi aktif kehilangan perangkat yang diingat dan keluar pada permintaan berikutnya. `403` untuk akun sendiri. |
| `POST /api/akun/{id}/tautan-aktivasi` | — | `201` seperti `POST /api/akun`. Akun menjadi `pending` dengan kata sandi baru yang tidak bisa dipakai: tautan sebelumnya, sesi, dan perangkat yang diingat tidak berlaku lagi. Untuk akun yang sudah aktif ini berarti atur ulang kata sandi; untuk akun yang dinonaktifkan, cara memakainya lagi. `403` untuk akun sendiri. |

Setiap perubahan dicatat di log aplikasi (`account.created`, `account.updated`, `account.status`, `account.activation-link`) dengan ID Administrator yang melakukannya.

### Modul dashboard

Kunci modul untuk `modul` ([D-010](decisions.md#d-010--2026-10-13--penugasan-modul-untuk-pegawai-uptd)). Setiap kunci adalah nama koleksi atau pengaturan utama modul itu di dashboard.

| Kunci | Modul | Kunci | Modul |
|---|---|---|---|
| `pesan` | Pesan & Pengaduan | `papan` | Papan Layanan |
| `statistik` | Statistik Layanan | `tarif` | Tarif & Simulasi, dipensiunkan ([D-041](decisions.md#d-041--2026-10-14--kalkulator-pkb-dan-cek-biaya-pajak-memakai-mesin-kalkulator-pajak-tarif--simulasi-dipensiunkan)): tidak bisa ditugaskan dan tidak ikut di sesi |
| `berita` | Berita & Pengumuman | `profil` | Tentang UPTD |
| `dokumentasi` | Dokumentasi Kegiatan | `visiMisi` | Visi & Misi |
| `dokumen` | Regulasi & Unduhan | `unit` | Struktur Organisasi |
| `faq` | Tanya Jawab | `kontak` | Kontak & Kanal |
| `layanan` | Persyaratan Layanan | `beranda` | Beranda |
| `jam` | Jam Pelayanan (termasuk koleksi `penyesuaian`) | `halaman` | Halaman Statis |
| `keliling` | Layanan Luar Kantor (dulu Samsat Keliling) | `pemutakhiran` | Jadwal Pemutakhiran |

Endpoint konten dashboard (`/api/koleksi/…`, `/api/pengaturan/…`) wajib menolak pegawai yang tidak mengelola modul koleksi atau pengaturan itu. `/api/koleksi/faq`, `/api/koleksi/keliling`, `/api/koleksi/penyesuaian`, `/api/koleksi/pesan`, `/api/koleksi/layanan`, `/api/koleksi/halaman`, `/api/koleksi/pemutakhiran`, `/api/pengaturan/jam`, `/api/pengaturan/papan`, `/api/pengaturan/statistik`, `/api/pengaturan/kontak`, `/api/pengaturan/visiMisi`, `/api/pengaturan/profil`, dan `/api/pengaturan/beranda` sudah melakukannya; daftar Jadwal Pemutakhiran boleh dibaca semua pegawai, sebatas standar modul mereka, dan Pengaturan Situs, yang bukan milik modul mana pun, boleh dibaca semua pegawai tetapi hanya disimpan Administrator (`/api/pengaturan/situs`).

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

## Layanan Luar Kantor di dashboard

Dipakai halaman *Layanan Luar Kantor* di dashboard ([D-013](decisions.md#d-013--2026-10-13--samsat-keliling-tersimpan-di-server-batas-laju-api-publik-300), [D-043](decisions.md#d-043--2026-10-15--layanan-luar-kantor-tiga-unit-samsat-keliling-gerai-tetap-dan-loket-sementara)): Samsat Keliling per tanggal, gerai tetap, dan loket sementara dalam satu koleksi. Tamu mendapat `401`; pegawai yang tidak mengelola modul `keliling` mendapat `403` `{"message": "Modul Layanan Luar Kantor belum ditugaskan kepada Anda."}`. Pengelola Jadwal Pemutakhiran boleh memakai `GET /api/koleksi/keliling` saja.

Data jadwal:

```json
{ "id": "01m4…", "jenis": "keliling", "unit": "01", "nama": "", "tanggalMulai": "2026-10-17", "tanggalSelesai": "2026-10-17", "hari": [6], "mulai": "06:00", "selesai": "09:00", "lokasi": "CFD Eltari", "kecamatan": "Kec. Oebobo", "layanan": "Pajak tahunan", "catatan": "", "aktif": true, "urutan": 0, "dibuat": "2026-10-15T08:00:00+00:00", "diperbarui": "2026-10-15T08:00:00+00:00" }
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/keliling` | — | `200` semua jadwal, urut jenis, tanggal mulai, lalu jam. |
| `GET /api/koleksi/keliling/{id}` | — | `200` data jadwal; `404` bila tidak ada. |
| `POST /api/koleksi/keliling` | lihat aturan per jenis di bawah | `201` data jadwal. `id` dan `urutan` dari klien diabaikan. |
| `PATCH /api/koleksi/keliling/{id}` | sebagian dari isian | `200` data jadwal. Jadwal diperiksa utuh: isian yang dikirim di atas isian yang tersimpan. |
| `POST /api/koleksi/keliling/hapus` | `{"ids": ["…"]}` | `204`. ID yang sudah tidak ada dilewati. |
| `POST /api/koleksi/keliling/salin-hari` | `{"dari": "2026-10-19", "ke": ["2026-10-20", "2026-10-21"]}` | `200` daftar salinan ([D-044](decisions.md#d-044--2026-10-15--daftar-lokasi-dan-salin-jadwal-per-hari)): semua jadwal Samsat Keliling bertanggal `dari` disalin ke setiap tanggal `ke` (urut tanggal) dengan `aktif` sama seperti jadwal asalnya. Jadwal yang sudah ada di sana untuk unit, tanggal, jam mulai, dan lokasi yang sama dilewati; `[]` bila tidak ada yang disalin. `422` bila `dari` atau salah satu `ke` bukan tanggal, `ke` kosong, berulang, sama dengan `dari`, atau lebih dari 14 tanggal. |
| `POST /api/koleksi/keliling/salin-minggu` | `{"dari": "2026-10-12"}` (Senin) | `200` daftar salinan: jadwal Samsat Keliling bertanggal Senin–Minggu itu disalin ke hari yang sama minggu berikutnya dengan `aktif` `false`. Jadwal yang sudah ada di sana untuk unit, tanggal, jam mulai, dan lokasi yang sama dilewati; `[]` bila tidak ada yang disalin. `422` bila `dari` bukan tanggal Senin. |

Aturan isian (`422` dengan pesan per isian):

- `jenis` `keliling`, `gerai`, atau `sementara`. `mulai` dan `selesai` berformat `HH:MM`, selesai setelah mulai. `lokasi` maks. 120, `kecamatan` maks. 60, `layanan` maks. 80, dan `catatan` maks. 300 karakter, teks polos tanpa karakter kontrol; `aktif` boolean. Catatan kosong disimpan sebagai `""`.
- **keliling**: `unit` `01`, `02`, atau `03` dan `tanggalMulai` (`YYYY-MM-DD`) wajib. Server mengisi `tanggalSelesai` sama dengan `tanggalMulai`, `hari` dengan hari tanggal itu, dan `nama` kosong.
- **gerai**: `nama` (maks. 120) dan `hari` (daftar 0–6, 0 = Minggu, tanpa pengulangan) wajib; `tanggalMulai` dan `tanggalSelesai` boleh `null`. `unit` selalu `null`.
- **sementara**: `nama`, `hari`, `tanggalMulai`, dan `tanggalSelesai` wajib; selesai tidak sebelum mulai, rentangnya kurang dari 366 hari, dan minimal satu hari terpilih jatuh di antaranya. `unit` selalu `null`.

Log aktivitas memakai aksi `menambahkan`, `memperbarui`, `menghapus`, dan `menyalin`, dengan target seperti "Samsat Keliling 01 · Sabtu, 2026-10-17 · CFD Eltari", "Samsat Corner · Lippo Plaza Kupang", "3 jadwal Samsat Keliling ke minggu 2026-10-19", atau "4 jadwal Samsat Keliling dari 2026-10-19".

Lokasi yang dikirim saat menambah jadwal, atau saat lokasinya diubah, masuk daftar lokasi bila belum ada (tanpa membedakan huruf besar dan kecil), dengan kecamatan jadwal itu dan log "menambahkan Lokasi …" ([D-044](decisions.md#d-044--2026-10-15--daftar-lokasi-dan-salin-jadwal-per-hari)).

### Daftar lokasi

Pilihan isian Lokasi di halaman *Layanan Luar Kantor* ([D-044](decisions.md#d-044--2026-10-15--daftar-lokasi-dan-salin-jadwal-per-hari)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `keliling` mendapat `403` `{"message": "Modul Layanan Luar Kantor belum ditugaskan kepada Anda."}`.

```json
{ "id": "01m4…", "nama": "CFD Eltari", "kecamatan": "Kec. Oebobo", "urutan": 0, "dibuat": "2026-10-15T08:00:00+00:00", "diperbarui": "2026-10-15T08:00:00+00:00" }
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/lokasi` | — | `200` semua lokasi, urut nama. |
| `GET /api/koleksi/lokasi/{id}` | — | `200` data lokasi; `404` bila tidak ada. |
| `POST /api/koleksi/lokasi` | `{"nama", "kecamatan"}` | `201` data lokasi. `422` bila nama (maks. 120) atau kecamatan (maks. 60) kosong, berisi karakter kontrol, atau nama sudah ada di daftar tanpa membedakan huruf besar dan kecil: "Lokasi ini sudah ada di daftar." |
| `PATCH /api/koleksi/lokasi/{id}` | sebagian dari isian di atas | `200` data lokasi. Jadwal yang memakai nama lama tidak ikut berubah. |
| `POST /api/koleksi/lokasi/hapus` | `{"ids": ["…"]}` | `204`. Jadwal yang memakai lokasi itu tidak ikut terhapus. |

Log aktivitas: "menambahkan", "memperbarui", dan "menghapus" dengan target seperti "Lokasi CFD Eltari". Kode: `app/Http/Controllers/Dashboard/ServiceLocationController.php`, `app/Actions/RememberServiceLocation.php`. Tes: `tests/Feature/ServiceLocationControllerTest.php`.

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

## Pencarian kendaraan di dashboard

Dipakai *Kalkulator PKB* ([D-041](decisions.md#d-041--2026-10-14--kalkulator-pkb-dan-cek-biaya-pajak-memakai-mesin-kalkulator-pajak-tarif--simulasi-dipensiunkan)). Tamu mendapat `401`; semua pegawai yang login boleh mencari. PINTU meneruskan pencarian ke API NJKB BPAD NTT (`GET {NJKB_API_BASE_URL}/api/v1/vehicle/{nopol}`) dengan token layanan Cloudflare Access miliknya sendiri; token tidak pernah sampai ke peramban.

```json
{
  "nopol": "DH1234AB", "nama": "PEMILIK CONTOH", "jenis": "MINIBUS",
  "jatuhTempoStnk": "2027-03-01", "jatuhTempoPajak": "2026-03-01",
  "njkb": 150000000, "njub": 0, "bobot": 1.05, "warnaPlat": "putih", "njkbStatus": "matched"
}
```

| Alamat | Respons |
|---|---|
| `GET /api/kendaraan/{nopol}` | `200` data di atas. `nopol` dinormalisasi (huruf kapital tanpa spasi dan tanda baca); `jenis` adalah klasifikasi BPAD apa adanya; tanggal `YYYY-MM-DD` atau `null`; `warnaPlat` `putih`, `hitam`, `merah`, atau `null` bila tidak tercatat atau tidak didukung (mis. kuning). `njkbStatus` `not_found` berarti kendaraan dikenal tetapi referensi NJKB-nya tidak: `njkb` `0`, `bobot` mengikuti jenis kendaraan, dan NJKB diisi manual. `404` `{"message": "Data kendaraan tidak ditemukan."}`. `422` bila nomor polisi tidak berbentuk seperti DH 1234 AB (tanpa menghubungi API), atau bila referensi NJKB belum tersedia, bertentangan, atau perlu diperiksa ("… Isi NJKB secara manual."). `429` setelah 30 pencarian per menit per pegawai. `503` di luar jam layanan BPAD tanpa menghubungi API ([D-042](decisions.md#d-042--2026-10-14--jam-layanan-data-kendaraan-bpad): "Data kendaraan dari BPAD hanya tersedia sampai pukul 21.00 WITA. Coba lagi besok."), atau bila API belum dikonfigurasi, menolak, lambat, sibuk (dengan lama tunggu dari `Retry-After`), atau jawabannya tidak dikenal. |

Pencarian tidak dicatat: nomor polisi, nama pemilik, dan nomor rangka tidak masuk database maupun log. Kegagalan mencatat `NJKB API request failed` dengan alasan, ID permintaan, status HTTP, dan kode galat API saja. Jam layanan diatur dengan `NJKB_SERVICE_CLOSES_AT` (bawaan `21:00`) dan `NJKB_SERVICE_OPENS_AT` (kosong selama jam mulai belum diketahui), dalam WITA. Permintaan ke API memakai batas waktu `NJKB_API_TIMEOUT_SECONDS` (bawaan 8 detik), tidak mengikuti pengalihan, dan diulang sekali hanya untuk koneksi terputus atau `502`/`503`/`504`. Setelah `429`, PINTU menunggu sesuai `Retry-After` (maks. 1 jam) sebelum bertanya lagi.

Kode: `app/Actions/LookUpVehicle.php`, `app/Http/Controllers/Dashboard/VehicleController.php`, `config/services.php` (`njkb`). Tes: `tests/Feature/VehicleLookupTest.php`.

## Statistik Layanan di dashboard

Dipakai halaman *Statistik Layanan* di dashboard ([D-018](decisions.md#d-018--2026-10-13--statistik-layanan-tersimpan-di-server-hanya-angka-terverifikasi-yang-terbit)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `statistik` mendapat `403` `{"message": "Modul Statistik Layanan belum ditugaskan kepada Anda."}`.

```json
{
  "status": "terverifikasi", "tahun": "2026", "catatan": "…",
  "set": [{
    "kunci": "transaksi", "label": "Transaksi pelayanan", "sub": "Transaksi pelayanan · UPTD Kota Kupang",
    "judulGrafik": "Transaksi per bulan", "satuan": "transaksi",
    "tiles": [{ "id": "transaksi-1", "label": "Total transaksi", "nilai": 12480, "satuan": "", "perubahan": 4.2, "naikBaik": true }],
    "seri": [880, 910, 1020, 960, 1040, 1100, 990, 1080, 1150, 1120, 0, 0]
  }]
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/statistik` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: empat kategori dengan label bawaan, semua angka `0`, status `ilustrasi`, dan tahun berjalan (WITA). |
| `PUT /api/pengaturan/statistik` | dokumen lengkap | `200` dokumen tersimpan. Bilangan bulat disimpan sebagai bilangan bulat (`12480.0` menjadi `12480`); isi yang sama dengan yang tersimpan tidak dicatat. `422` bila status selain `ilustrasi` atau `terverifikasi`; tahun bukan 4 angka 19xx/20xx; `set` tidak berisi tepat 4 kategori `transaksi`, `penerimaan`, `keliling`, `kepuasan` (masing-masing sekali); kategori tanpa nama tab (maks. 60 karakter) atau judul grafik (maks. 80); kategori tanpa tepat 4 angka utama atau 12 nilai bulanan; angka utama tanpa label (maks. 60); nilai bukan angka; perubahan di luar -1.000–1.000%; nilai bulanan negatif; atau teks berisi karakter kontrol. |

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Statistik Layanan".

Kode: `app/Http/Controllers/Dashboard/StatisticsController.php`, `app/Http/Requests/Dashboard/UpdateStatisticsRequest.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/StatisticsControllerTest.php`.

## Kontak & Kanal di dashboard

Dipakai halaman *Kontak & Kanal* di dashboard ([D-019](decisions.md#d-019--2026-10-13--kontak--kanal-tersimpan-di-server)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `kontak` mendapat `403` `{"message": "Modul Kontak & Kanal belum ditugaskan kepada Anda."}`.

```json
{
  "namaKantor": "Kantor Bersama Samsat Kota Kupang", "alamat": "Jl. …", "kota": "Kota Kupang", "provinsi": "Nusa Tenggara Timur",
  "telepon": "(0380) 821 234", "whatsapp": "0812-3456-7890", "whatsappCatatan": "Hanya pesan teks, hari kerja", "email": "…@….go.id",
  "mediaSosial": [{ "id": "…", "platform": "Instagram", "akun": "@…", "url": "https://instagram.com/…" }],
  "mapsUrl": "https://maps.google.com/?q=…", "catatanLokasi": "…", "janjiBalasan": "Kami membalas paling lambat tiga hari kerja.",
  "kanalPengaduan": [{ "id": "…", "nama": "SP4N-LAPOR!", "deskripsi": "…", "tautanLabel": "Buka lapor.go.id", "tautanUrl": "https://www.lapor.go.id" }]
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/kontak` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: semua isian kosong dan kedua daftar `[]`. |
| `PUT /api/pengaturan/kontak` | dokumen lengkap | `200` dokumen tersimpan; isian opsional yang kosong disimpan sebagai `""`. Isi yang sama dengan yang tersimpan tidak dicatat. `422` bila nama kantor (maks. 120 karakter) atau alamat (maks. 200) kosong, telepon kurang dari 6 angka atau berisi huruf, WhatsApp kurang dari 9 angka, surel tidak valid, lebih dari 8 akun media sosial atau 8 kanal, butir tanpa platform, akun, atau nama kanal, ID butir ganda, tautan selain `http(s)://`, `mailto:`, `tel:`, `#…`, atau halaman portal `*.html`, tautan berisi spasi, atau teks berisi karakter kontrol. |

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Kontak & Kanal".

Kode: `app/Http/Controllers/Dashboard/ContactController.php`, `app/Http/Requests/Dashboard/UpdateContactRequest.php`, `app/Rules/PortalLink.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/ContactControllerTest.php`.

## Visi & Misi di dashboard

Dipakai halaman *Visi & Misi* di dashboard ([D-020](decisions.md#d-020--2026-10-13--visi--misi-tersimpan-di-server-unggahan-maklumat-ditunda)). Alamatnya memakai nama pengaturan dashboard, `visiMisi`. Tamu mendapat `401`; pegawai yang tidak mengelola modul `visiMisi` mendapat `403` `{"message": "Modul Visi & Misi belum ditugaskan kepada Anda."}`.

```json
{
  "sumberVisi": "Visi Pemerintah Provinsi NTT", "visi": "NTT Maju, Sehat, Cerdas, Sejahtera dan Berkelanjutan.",
  "misi": [{ "id": "misi-1", "teks": "…", "kataKunci": "Maju" }],
  "kontribusi": [{ "id": "kontribusi-1", "misi": "Misi 01 · Maju", "judul": "…", "deskripsi": "…" }],
  "nilai": [{ "id": "nilai-1", "huruf": "B", "judul": "Berorientasi pelayanan", "deskripsi": "…" }],
  "maklumat": { "teks": "…", "penandatangan": "Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang", "berkas": null }
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/visiMisi` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: teks resmi yang sudah tampil di portal. |
| `PUT /api/pengaturan/visiMisi` | dokumen lengkap | `200` dokumen tersimpan; isian opsional yang kosong disimpan sebagai `""`. `maklumat.berkas` berisi `{ nama, ukuran, tipe, url }` dari catatan unggahan PDF untuk tujuan `visiMisi`, atau `null` ([D-029](decisions.md#d-029--2026-10-14--foto-tentang-uptd-dan-maklumat-diunggah-ke-server-profil-disusun-dari-renstra-dan-rpjmd)); berkas yang diganti atau dikosongkan dihapus. Isi yang sama dengan yang tersimpan tidak dicatat. `422` bila visi (maks. 300 karakter) atau isi maklumat (maks. 600) kosong, misi tanpa teks, kontribusi tanpa judul, nilai tanpa nama, huruf nilai lebih dari 2 karakter, lebih dari 10 misi, 6 kontribusi, atau 10 nilai, ID butir ganda, teks berisi karakter kontrol, atau alamat berkas maklumat bukan unggahan PINTU untuk Visi & Misi: `{"maklumat.berkas.url": ["Berkas maklumat harus diunggah lewat dashboard."]}`. |

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Visi & Misi".

Kode: `app/Http/Controllers/Dashboard/VisionMissionController.php`, `app/Http/Requests/Dashboard/UpdateVisionMissionRequest.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/VisionMissionControllerTest.php`.

## Tentang UPTD di dashboard

Dipakai halaman *Tentang UPTD* di dashboard ([D-021](decisions.md#d-021--2026-10-14--tentang-uptd-tersimpan-di-server-unggahan-foto-ditunda)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `profil` mendapat `403` `{"message": "Modul Tentang UPTD belum ditugaskan kepada Anda."}`.

```json
{
  "judul": "Melayani pajak daerah.", "lanjutan": "Dari jantung Kota Kupang.", "pengantar": "…",
  "foto": null, "keteranganFoto": "…",
  "kedudukan": { "judul": "Bagian dari BPAD NTT.", "lanjutan": "Dekat dengan warga kota.", "deskripsi": "…" },
  "identitas": [{ "id": "identitas-1", "label": "Nama unit", "nilai": "UPTD Pendapatan Daerah Wilayah Kota Kupang" }],
  "tugasFungsi": [{ "id": "tugas-1", "judul": "…", "deskripsi": "…", "kataKunci": "Data" }],
  "sambutan": { "kutipan": "…", "nama": "", "jabatan": "Kepala UPTD Pendapatan Daerah Wilayah Kota Kupang", "foto": null }
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/profil` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: teks awal dari Renstra BPAD dan RPJMD NTT, dengan nama Kepala UPTD dari pemilik. |
| `PUT /api/pengaturan/profil` | dokumen lengkap | `200` dokumen tersimpan; isian opsional yang kosong disimpan sebagai `""`. `foto` dan `sambutan.foto` berisi alamat unggahan foto untuk tujuan `profil`, atau `null` ([D-029](decisions.md#d-029--2026-10-14--foto-tentang-uptd-dan-maklumat-diunggah-ke-server-profil-disusun-dari-renstra-dan-rpjmd)); foto yang diganti atau dikosongkan dihapus. Isi yang sama dengan yang tersimpan tidak dicatat. `422` bila judul (maks. 80 karakter), pengantar (maks. 400), atau judul kedudukan kosong, baris identitas tanpa label atau isi, tugas tanpa uraian, lebih dari 12 baris identitas atau 12 tugas, ID butir ganda, teks berisi karakter kontrol, atau foto bukan unggahan PINTU untuk Tentang UPTD: `{"foto": ["Foto kantor harus diunggah lewat dashboard."]}`, `{"sambutan.foto": ["Foto Kepala UPTD harus diunggah lewat dashboard."]}`. |

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Tentang UPTD".

Kode: `app/Http/Controllers/Dashboard/ProfileController.php`, `app/Http/Requests/Dashboard/UpdateProfileRequest.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/ProfileControllerTest.php`.

## Persyaratan Layanan di dashboard

Dipakai halaman *Persyaratan Layanan* di dashboard ([D-022](decisions.md#d-022--2026-10-14--persyaratan-layanan-tersimpan-di-server-dengan-alur-persetujuan)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `layanan` mendapat `403` `{"message": "Modul Persyaratan Layanan belum ditugaskan kepada Anda."}`. Pengelola Jadwal Pemutakhiran boleh memakai `GET /api/koleksi/layanan` saja.

```json
{
  "id": "01m4…", "nama": "Pajak tahunan", "slug": "pajak-tahunan", "ringkasan": "…", "loket": "Loket 1",
  "jenis": "layanan", "cekFisik": false, "nonTunai": true, "diwakilkan": true, "label": [],
  "persyaratan": [{ "id": "p1", "dokumen": "Fotokopi STNK dan notis pajak (lampirkan asli)", "jumlah": "2 lembar" }],
  "catatan": "", "unggulan": true,
  "status": "terbit", "penyedia": "Seksi Verifikasi", "pemeriksa": "", "catatanRevisi": "",
  "terbitPada": "2026-10-14T01:00:00+00:00",
  "riwayat": [{ "waktu": "…", "oleh": "Yohanes Admin", "aksi": "menyetujui dan menerbitkan", "ke": "terbit" }],
  "urutan": 0, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/layanan` | — | `200` semua layanan sesuai urutan, termasuk yang belum terbit. |
| `GET /api/koleksi/layanan/{id}` | — | `200` satu layanan; `404` bila tidak ada. |
| `POST /api/koleksi/layanan` | isi layanan seperti di atas, tanpa kolom alur | `201` sebagai draf di akhir daftar, dengan riwayat "menyiapkan draf" atas nama akun yang masuk. Status, riwayat, catatan revisi, dan waktu terbit dari klien diabaikan. |
| `PATCH /api/koleksi/layanan/{id}` | sebagian isi, `status` untuk langkah alur, `catatanRevisi` saat mengembalikan | `200`. Langkah yang sah: `draf`→`diperiksa`, `diperiksa`→`persetujuan` atau `dikembalikan`, `persetujuan`→`terbit` atau `dikembalikan`, `terbit`→`arsip`, `dikembalikan`→`diperiksa`, `arsip`→`draf`. Langkah dicatat di `riwayat` (terbaru di depan) dan di log aktivitas dengan kata kerjanya, mis. "menyetujui dan menerbitkan Pajak tahunan". Status yang sama dengan sekarang bukan langkah. `422` untuk langkah yang tidak tersedia (`{"status": ["Langkah ini tidak tersedia untuk layanan berstatus Draf."]}`) atau mengembalikan tanpa catatan revisi. `403` bila pegawai bukan Administrator mengubah layanan yang menunggu persetujuan atau sudah terbit: `{"message": "Layanan yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator."}`. |
| `PUT /api/koleksi/layanan/urutan` | `{"ids": ["…"]}` | `204`. Hanya Administrator; pegawai lain `403` `{"message": "Hanya Administrator yang dapat mengubah urutan layanan di portal."}`. |
| `POST /api/koleksi/layanan/hapus` | `{"ids": ["…"]}` | `204`. Hanya Administrator; pegawai lain `403` `{"message": "Hanya Administrator yang dapat menghapus layanan."}`. |

Isi divalidasi seperti formulir dashboard: nama (maks. 120 karakter), slug unik (huruf kecil, angka, tanda hubung; maks. 80), ringkasan (maks. 200), loket (maks. 60), dan penyedia wajib; jenis `layanan` atau `tambahan`; maks. 6 label (label kosong dibuang); 1–20 berkas persyaratan dengan nama wajib (maks. 200) dan ID unik; catatan maks. 400; tanpa karakter kontrol.

Kode: `app/Http/Controllers/Dashboard/Service*`, `app/Http/Requests/Dashboard/*Service*`, `app/Http/Requests/Dashboard/Concerns/TakesWorkflowStep.php`, `app/Actions/UpdateWorkflowEntry.php`, `app/Models/Service.php`, `app/Models/Concerns/HasPublicationWorkflow.php`, `app/Enums/WorkflowStatus.php`, `app/Policies/ServicePolicy.php`, `database/migrations/*_insert_initial_services.php`. Tes: `tests/Feature/ServiceControllerTest.php`, `ServiceWorkflowTest.php`.

## Berita & Pengumuman di dashboard

Dipakai halaman *Berita & Pengumuman* di dashboard ([D-031](decisions.md#d-031--2026-10-14--berita--pengumuman-tersimpan-di-server-pengelola-berita-menerbitkan-sendiri)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `berita` mendapat `403` `{"message": "Modul Berita & Pengumuman belum ditugaskan kepada Anda."}`. Pengelola Jadwal Pemutakhiran boleh memakai `GET /api/koleksi/berita` saja.

```json
{
  "id": "01m4…", "judul": "PINTU resmi diluncurkan. Informasi Samsat kini di satu portal.",
  "slug": "pintu-resmi-diluncurkan", "kategori": "berita", "ringkasan": "…", "isi": "<p>…</p>",
  "sampul": "https://…/berita/2026/10/01m4….jpg", "keteranganSampul": "…", "kreditFoto": "Subbagian Tata Usaha",
  "tanggal": "2026-10-14", "sorotan": true, "pengumumanBeranda": false,
  "status": "terbit", "penyedia": "Pengelola publikasi", "pemeriksa": "Kepala Subbagian Tata Usaha", "catatanRevisi": "",
  "terbitPada": "2026-10-14T01:00:00+00:00",
  "riwayat": [{ "waktu": "…", "oleh": "Maria Bria", "aksi": "menyetujui dan menerbitkan", "ke": "terbit" }],
  "urutan": 0, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/berita` | — | `200` semua tulisan, tanggal tayang terbaru lebih dulu, termasuk yang belum terbit. |
| `GET /api/koleksi/berita/{id}` | — | `200` satu tulisan; `404` bila tidak ada. |
| `POST /api/koleksi/berita` | isi tulisan seperti di atas, tanpa kolom alur | `201` sebagai draf, dengan riwayat "menyiapkan draf" atas nama akun yang masuk. Status, riwayat, catatan revisi, dan waktu terbit dari klien diabaikan. |
| `PATCH /api/koleksi/berita/{id}` | sebagian isi, `status` untuk langkah alur, `catatanRevisi` saat mengembalikan | `200`. Langkah alur dan pesannya sama dengan layanan (`"Langkah ini tidak tersedia untuk tulisan berstatus Draf."`), tetapi pengelola Berita boleh menjalankan semuanya, termasuk menerbitkan dan menarik, dan boleh mengubah tulisan yang sudah terbit. Menandai `sorotan` melepas tanda itu dari tulisan lain tanpa mengubah `diperbarui` mereka. |
| `POST /api/koleksi/berita/hapus` | `{"ids": ["…"]}` | `204`; foto sampul tulisan yang dihapus ikut terhapus. Hanya Administrator; pegawai lain `403` `{"message": "Hanya Administrator yang dapat menghapus tulisan."}`. |

Tidak ada `PUT /api/koleksi/berita/urutan`: tulisan diurutkan menurut tanggal tayang.

Isi divalidasi seperti formulir dashboard: judul (maks. 160 karakter), slug unik (huruf kecil, angka, tanda hubung; maks. 80), ringkasan (maks. 240), isi, tanggal tayang (`YYYY-MM-DD`), dan penyedia wajib. Kategori harus `berita`, `pengumuman`, `keliling`, atau `edukasi`. Keterangan foto maks. 200, kredit foto maks. 120. Teks tidak boleh berisi karakter kontrol. Isi adalah HTML seperti yang ditulis editor (aturan yang sama dengan Halaman Statis, maks. 100.000 karakter). Foto sampul harus alamat unggahan PINTU untuk tujuan `berita` (`{"sampul": ["Foto sampul harus diunggah lewat dashboard."]}`) yang belum dipakai tulisan lain (`"Foto ini sudah dipakai tulisan lain. Unggah ulang fotonya."`). Foto yang diganti atau dikosongkan dihapus bersama catatannya.

Kode: `app/Http/Controllers/Dashboard/Article*`, `app/Http/Requests/Dashboard/*Article*`, `app/Models/Article.php`, `app/Policies/ArticlePolicy.php`, `database/migrations/*_insert_initial_articles.php`. Tes: `tests/Feature/ArticleControllerTest.php`, `ArticleWorkflowTest.php`.

## Dokumentasi Kegiatan di dashboard

Dipakai halaman *Dokumentasi Kegiatan* di dashboard ([D-032](decisions.md#d-032--2026-10-14--dokumentasi-kegiatan-tersimpan-di-server-galeri-disusun-per-bulan)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `dokumentasi` mendapat `403` `{"message": "Modul Dokumentasi Kegiatan belum ditugaskan kepada Anda."}`. Pengelola Jadwal Pemutakhiran boleh memakai `GET /api/koleksi/dokumentasi` saja.

```json
{
  "id": "01m4…", "judul": "Samsat Keliling di Kelurahan Oesapa.", "kategori": "keliling",
  "lokasi": "Kec. Kelapa Lima", "tanggal": "2026-10-21", "deskripsi": "…",
  "foto": [{ "id": "a", "src": "https://…/dokumentasi/2026/10/01m4….jpg", "keterangan": "Warga mengantre di kelurahan." }],
  "status": "terbit", "penyedia": "Seksi Penetapan dan Penagihan", "pemeriksa": "Kepala Seksi Penetapan dan Penagihan",
  "catatanRevisi": "", "terbitPada": "2026-10-21T03:00:00+00:00", "riwayat": [ … ],
  "urutan": 0, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/dokumentasi` | — | `200` semua kegiatan, tanggal terbaru lebih dulu, termasuk yang belum terbit. |
| `GET /api/koleksi/dokumentasi/{id}` | — | `200` satu kegiatan; `404` bila tidak ada. |
| `POST /api/koleksi/dokumentasi` | isi kegiatan seperti di atas, tanpa kolom alur | `201` sebagai draf, dengan riwayat "menyiapkan draf" atas nama akun yang masuk. Status, riwayat, catatan revisi, dan waktu terbit dari klien diabaikan. |
| `PATCH /api/koleksi/dokumentasi/{id}` | sebagian isi, `status` untuk langkah alur, `catatanRevisi` saat mengembalikan | `200`. Langkah alur dan pesannya sama dengan layanan (`"Langkah ini tidak tersedia untuk kegiatan berstatus Draf."`). Pengelola Dokumentasi boleh menjalankan semuanya dan boleh mengubah kegiatan yang sudah terbit. `422` `{"foto": ["Unggah minimal satu foto sebelum kegiatan diterbitkan."]}` bila kegiatan akan terbit, atau tetap terbit, tanpa foto. |
| `POST /api/koleksi/dokumentasi/hapus` | `{"ids": ["…"]}` | `204`; foto kegiatan yang dihapus ikut terhapus. Hanya Administrator; pegawai lain `403` `{"message": "Hanya Administrator yang dapat menghapus dokumentasi."}`. |

Tidak ada `PUT /api/koleksi/dokumentasi/urutan`: kegiatan diurutkan menurut tanggal.

Isi divalidasi seperti formulir dashboard: nama kegiatan (maks. 160 karakter), lokasi (maks. 120), tanggal (`YYYY-MM-DD`), dan penyedia wajib; kategori `keliling`, `sosialisasi`, `operasi`, atau `internal`; deskripsi maks. 600; tanpa karakter kontrol. `foto` wajib dikirim (boleh kosong untuk draf), maks. 30 foto dengan ID unik dan keterangan maks. 200. Alamat setiap foto harus unggahan PINTU untuk tujuan `dokumentasi` (`{"foto.0.src": ["Foto harus diunggah lewat dashboard."]}`), tidak tercantum dua kali, dan belum dipakai kegiatan lain (`"Foto ini sudah dipakai kegiatan lain. Unggah ulang fotonya."`). Foto yang dihapus dari daftar dihapus bersama catatannya.

Kode: `app/Http/Controllers/Dashboard/Documentation*`, `app/Http/Requests/Dashboard/*Documentation*`, `app/Models/Documentation.php`, `app/Policies/DocumentationPolicy.php`, `database/migrations/*_insert_initial_documentations.php`. Tes: `tests/Feature/DocumentationControllerTest.php`, `DocumentationWorkflowTest.php`.

## Struktur Organisasi di dashboard

Dipakai halaman *Struktur Organisasi* di dashboard ([D-033](decisions.md#d-033--2026-10-14--struktur-organisasi-tersimpan-di-server-nip-pejabat-tidak-ditampilkan-di-portal)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `unit` mendapat `403` `{"message": "Modul Struktur Organisasi belum ditugaskan kepada Anda."}`. Perubahan langsung tampil di portal; tidak ada alur publikasi.

```json
{
  "id": "01m4…", "nama": "Seksi Verifikasi", "singkatan": "S2", "jenis": "seksi", "induk": "01m4…",
  "jabatan": "Kepala Seksi", "pejabat": "", "nip": "", "foto": null,
  "anggota": ["Petugas pendataan & verifikasi", "Petugas loket pelayanan"],
  "uraianTugas": "…", "catatan": "", "urutan": 3, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/unit` | — | `200` semua unit sesuai urutan. |
| `GET /api/koleksi/unit/{id}` | — | `200` satu unit; `404` bila tidak ada. |
| `POST /api/koleksi/unit` | isi unit seperti di atas | `201` di akhir daftar; dicatat "menambahkan". |
| `PATCH /api/koleksi/unit/{id}` | sebagian isi | `200`; perubahan dicatat "memperbarui", simpan ulang tanpa perubahan tidak dicatat. `422` `{"induk": ["Unit tidak bisa berada di bawah dirinya sendiri atau unit yang dibawahinya."]}` bila unit dipindahkan ke bawah dirinya atau ke bawah unit di cabangnya. |
| `PUT /api/koleksi/unit/urutan` | `{"ids": ["…"]}` | `204`. Unit yang tidak disebut tetap berurutan di belakang; waktu `diperbarui` tidak berubah. `422` bila daftar tidak cocok dengan data terbaru. |
| `POST /api/koleksi/unit/hapus` | `{"ids": ["…"]}` | `204`; foto pejabat ikut terhapus. `422` `{"ids": ["Unit masih membawahi unit lain. Pindahkan unit di bawahnya terlebih dahulu."]}` bila unit lain di luar daftar masih berada di bawahnya. |

Isi divalidasi seperti formulir dashboard: nama unit (maks. 120 karakter) wajib; singkatan 1–3 huruf atau angka (disimpan kapital); jenis `pimpinan`, `tata-usaha`, `seksi`, `fungsional`, atau `mitra`; `induk` kosong atau ID unit yang ada dan bukan mitra; jabatan maks. 120, nama pejabat maks. 160; NIP kosong atau 18 digit (spasi dibuang); maks. 20 jabatan pelaksana (maks. 120 karakter, yang kosong dibuang); uraian tugas maks. 1000; catatan maks. 200; tanpa karakter kontrol. Foto pejabat harus alamat unggahan PINTU untuk tujuan `unit` yang belum dipakai unit lain.

Kode: `app/Http/Controllers/Dashboard/OrganizationUnit*`, `app/Http/Requests/Dashboard/*OrganizationUnit*`, `app/Models/OrganizationUnit.php`, `app/Policies/OrganizationUnitPolicy.php`, `database/migrations/*_insert_initial_organization_units.php`. Tes: `tests/Feature/OrganizationUnitControllerTest.php`.

## Regulasi & Unduhan di dashboard

Dipakai halaman *Regulasi & Unduhan* di dashboard ([D-030](decisions.md#d-030--2026-10-14--regulasi--unduhan-tersimpan-di-server-dengan-alur-persetujuan)), dengan alur publikasi yang sama dengan Persyaratan Layanan. Tamu mendapat `401`; pegawai yang tidak mengelola modul `dokumen` mendapat `403` `{"message": "Modul Regulasi & Unduhan belum ditugaskan kepada Anda."}`. Pengelola Jadwal Pemutakhiran boleh memakai `GET /api/koleksi/dokumen` saja.

```json
{
  "id": "01m4…", "judul": "Undang-Undang Nomor 1 Tahun 2022",
  "keterangan": "Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah",
  "kategori": "regulasi", "format": "PDF", "nomor": "1", "tahun": "2022",
  "berkas": { "nama": "UU 1 2022.pdf", "ukuran": 245760, "tipe": "application/pdf", "url": "…" },
  "tautan": "",
  "status": "terbit", "penyedia": "Subbagian Tata Usaha", "pemeriksa": "Kepala Subbagian Tata Usaha", "catatanRevisi": "",
  "terbitPada": "2026-10-14T01:00:00+00:00",
  "riwayat": [{ "waktu": "…", "oleh": "Yohanes Admin", "aksi": "menyetujui dan menerbitkan", "ke": "terbit" }],
  "urutan": 0, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/dokumen` | — | `200` semua dokumen sesuai urutan masuk, termasuk yang belum terbit. |
| `GET /api/koleksi/dokumen/{id}` | — | `200` satu dokumen; `404` bila tidak ada. |
| `POST /api/koleksi/dokumen` | isi dokumen seperti di atas, tanpa kolom alur; `berkas` cukup `{"url": "…"}` hasil `POST /api/unggah` | `201` sebagai draf di akhir daftar, dengan riwayat "menyiapkan draf" atas nama akun yang masuk. Status, riwayat, catatan revisi, dan waktu terbit dari klien diabaikan. |
| `PATCH /api/koleksi/dokumen/{id}` | sebagian isi, `status` untuk langkah alur, `catatanRevisi` saat mengembalikan | `200`. Langkah alur dan pesannya sama dengan layanan (`"Langkah ini tidak tersedia untuk dokumen berstatus Draf."`). `422` `{"berkas": ["Lampirkan berkas atau tautan sebelum dokumen diterbitkan."]}` bila dokumen akan terbit, atau tetap terbit, tanpa berkas dan tautan. `403` bila pegawai bukan Administrator mengubah dokumen yang menunggu persetujuan atau sudah terbit: `{"message": "Dokumen yang menunggu persetujuan atau sudah terbit hanya dapat diubah Administrator."}`. |
| `POST /api/koleksi/dokumen/hapus` | `{"ids": ["…"]}` | `204`; berkas dokumen yang dihapus ikut terhapus. Hanya Administrator; pegawai lain `403` `{"message": "Hanya Administrator yang dapat menghapus dokumen."}`. |

Tidak ada `PUT /api/koleksi/dokumen/urutan`: portal mengurutkan dokumen per kategori.

Isi divalidasi seperti formulir dashboard: judul (maks. 200 karakter) dan penyedia wajib; keterangan maks. 300; kategori `regulasi`, `standar`, `formulir`, atau `laporan`; format `PDF`, `DOCX`, `XLSX`, `JPG`, atau `Tautan`; nomor maks. 60; tahun empat angka (19xx atau 20xx) atau kosong; tautan hanya `http://` atau `https://`, maks. 500; tanpa karakter kontrol. Berkas harus alamat unggahan PINTU untuk tujuan `dokumen` (`{"berkas.url": ["Berkas dokumen harus diunggah lewat dashboard."]}`) yang belum dipakai dokumen lain (`"Berkas ini sudah dipakai dokumen lain. Unggah ulang berkasnya."`). Nama, ukuran, dan tipenya diambil dari catatan unggahan, dan `format` mengikuti tipenya (gambar menjadi `JPG`). Dokumen tanpa berkas berformat `Tautan` wajib memiliki tautan. Berkas yang diganti atau dikosongkan dihapus bersama catatannya.

Kode: `app/Http/Controllers/Dashboard/Document*`, `app/Http/Requests/Dashboard/*Document*`, `app/Http/Requests/Dashboard/Concerns/TakesWorkflowStep.php`, `app/Actions/UpdateWorkflowEntry.php`, `app/Models/Document.php`, `app/Models/Concerns/HasPublicationWorkflow.php`, `app/Policies/DocumentPolicy.php`, `database/migrations/*_insert_initial_documents.php`. Tes: `tests/Feature/DocumentControllerTest.php`, `DocumentWorkflowTest.php`.

## Beranda di dashboard

Dipakai halaman *Beranda* di dashboard ([D-023](decisions.md#d-023--2026-10-14--teks-beranda-tersimpan-di-server-teks-html-tetap-sebagai-isi-awal)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `beranda` mendapat `403` `{"message": "Modul Beranda belum ditugaskan kepada Anda."}`.

```json
{
  "hero": { "eyebrow": "…", "judul": "Semua informasi Samsat.", "kataBergilir": ["Pajak", "STNK", "Mutasi", "BBN", "SAMSAT"], "lanjutan": "Lewat satu pintu.", "deskripsi": "…", "tombolLabel": "Cek Persyaratan Layanan", "tombolUrl": "layanan.html", "catatan": "…" },
  "video": { "url": "https://www.youtube.com/watch?v=25sRCp-IFxI", "label": "Tonton alur pelayanan", "durasi": "2:14", "judul": "" },
  "nilai": [{ "id": "nilai-1", "judul": "Persyaratan jelas.", "deskripsi": "…" }],
  "layananUtama": { "judul": "…", "lanjutan": "…", "deskripsi": "…" },
  "mitra": [{ "id": "mitra-1", "singkatan": "BP", "nama": "BPAD Provinsi NTT", "logo": null }],
  "kanal": [{ "id": "kanal-5", "kelompok": "Pengaduan", "item": [{ "teks": "Ombudsman NTT", "tautan": "https://ombudsman.go.id" }, { "teks": "Kotak saran", "tautan": "" }] }],
  "integritas": { "judul": "…", "lanjutan": "…", "deskripsi": "…" },
  "biaya": { "judul": "…", "lanjutan": "…", "deskripsi": "…", "catatanNotis": "…", "wajibDibawa": "…", "jaminan": ["Bukti bayar resmi", "…"] },
  "penutup": { "judul": "…", "lanjutan": "…", "deskripsi": "…" }
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/beranda` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: teks yang tertulis di HTML beranda. `logo` mitra `null` bila belum diunggah, termasuk untuk dokumen yang disimpan sebelum ada logo. Dokumen yang disimpan sebelum ada kata bergilir mendapat daftar awal bila judulnya memuat salah satu katanya, atau `[]` bila tidak ([D-045](decisions.md#d-045--2026-10-15--portal-beralih-ke-react-bertahap-kata-bergilir-di-judul-pembuka)). |
| `PUT /api/pengaturan/beranda` | dokumen lengkap | `200` dokumen tersimpan; isian opsional yang kosong disimpan sebagai `""` dan butir kosong di daftar kanal dan jaminan dibuang. Isi kanal tanpa nama dibuang; `tautan` kosong berarti tidak bisa diklik ([D-038](decisions.md#d-038--2026-10-14--isi-kanal-layanan-bisa-diberi-tautan)). Isi kanal berupa teks polos, seperti yang dikirim dashboard lama, diterima sebagai kanal tanpa tautan. Isi yang sama dengan yang tersimpan tidak dicatat. Tautan video disimpan sebagai tautan tonton tanpa parameter pelacak; bila `video` tidak dikirim (dashboard lama), video yang tersimpan tetap ([D-040](decisions.md#d-040--2026-10-14--video-alur-pelayanan-diatur-dari-dashboard-beranda)); begitu pula `hero.kataBergilir`. Logo mitra yang diganti atau dikosongkan ikut terhapus ([D-037](decisions.md#d-037--2026-10-14--mitra-satu-atap-memakai-logo-inisial-sebagai-cadangan)). `422` bila judul pembuka (maks. 80 karakter), deskripsi pembuka (maks. 300), label tombol (maks. 40), judul bagian, judul nilai, nama mitra, atau nama kelompok kanal kosong; tujuan tombol bukan tautan portal yang sah; singkatan mitra lebih dari 3 huruf; nama kanal lebih dari 60 karakter atau kosong padahal tautannya diisi (`"Nama kanal wajib diisi bila tautannya diisi."`); tautan kanal lebih dari 300 karakter atau bukan tautan portal yang sah; tautan video bukan tautan video YouTube (tautan bagikan, tonton, Shorts, live, atau sematan); label tombol video kosong padahal tautannya diisi; durasi video tidak berbentuk `2:14` atau `1:02:30`; logo mitra bukan alamat unggahan PINTU untuk tujuan `beranda` (`"Logo mitra harus diunggah lewat dashboard."`); batas jumlah butir terlampaui; ID butir ganda; atau teks berisi karakter kontrol. `422` juga bila `hero.kataBergilir` bukan daftar, berisi lebih dari 8 kata, kata kosong, kata lebih dari 24 karakter, kata ganda tanpa membedakan huruf besar-kecil (`"Kata bergilir tercantum dua kali."`), hanya satu kata (`"Isi minimal dua kata bergilir, atau kosongkan daftarnya."`), atau judul pembuka tidak memuat salah satunya sebagai kata utuh (`"Judul pembuka harus memuat salah satu kata bergilir; di kata itulah putarannya berhenti."`). |

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Beranda".

Kode: `app/Http/Controllers/Dashboard/HomepageController.php`, `app/Http/Requests/Dashboard/UpdateHomepageRequest.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/HomepageControllerTest.php`.

## Halaman Statis di dashboard

Dipakai halaman *Halaman Statis* di dashboard ([D-024](decisions.md#d-024--2026-10-14--halaman-statis-tersimpan-di-server-teks-html-tetap-sebagai-isi-awal)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `halaman` mendapat `403` `{"message": "Modul Halaman Statis belum ditugaskan kepada Anda."}`.

```json
{
  "id": "01m4…", "slug": "kebijakan-privasi",
  "judul": "Data Anda secukupnya.", "judulLanjutan": "Dijaga sepenuhnya.",
  "pengantar": "Kebijakan privasi · berlaku sejak [tanggal terbit]",
  "isi": "<p>Kebijakan ini menjelaskan …</p><h2>Data yang kami kumpulkan.</h2><ul><li><p>…</p></li></ul>",
  "urutan": 0, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/halaman` | — | `200` kedua halaman portal sesuai urutan. Halaman yang belum pernah disimpan memakai waktu dibuat sebagai `diperbarui`. |
| `GET /api/koleksi/halaman/{id}` | — | `200` satu halaman; `404` bila tidak ada. |
| `PATCH /api/koleksi/halaman/{id}` | sebagian dari `judul`, `judulLanjutan`, `pengantar`, `isi`, dan `slug` | `200`. Lanjutan judul dan teks kecil yang kosong disimpan sebagai `""`. `slug` boleh dikirim tetapi harus sama dengan nama berkas sekarang. Isi yang sama dengan yang tersimpan tidak disimpan dan tidak dicatat. `422` bila judul atau isi kosong, batas panjang terlampaui, nama berkas diubah (`{"slug": ["Nama berkas halaman tidak dapat diubah."]}`), teks berisi karakter kontrol, atau isi memuat HTML yang tidak ditulis editor, mis. `{"isi": ["Isi halaman memuat elemen <script> yang tidak didukung. Gunakan paragraf, subjudul, daftar, kutipan, dan tautan dari editor."]}`. |

Halaman tidak bisa ditambah atau dihapus: `POST /api/koleksi/halaman` mendapat `405`, dan `PUT /urutan` serta `POST /hapus` tidak ada.

Penyimpanan tercatat di log aktivitas sebagai "memperbarui Kebijakan Privasi" atau "memperbarui Syarat & Ketentuan".

Kode: `app/Http/Controllers/Dashboard/StaticPageController.php`, `app/Http/Requests/Dashboard/UpdateStaticPageRequest.php`, `app/Rules/RichText.php`, `app/Models/StaticPage.php`, `app/Policies/StaticPagePolicy.php`, `database/migrations/*_insert_initial_static_pages.php`. Tes: `tests/Feature/StaticPageControllerTest.php`.

## Jadwal Pemutakhiran di dashboard

Dipakai halaman *Jadwal Pemutakhiran* dan Ringkasan di dashboard ([D-025](decisions.md#d-025--2026-10-14--jadwal-pemutakhiran-tersimpan-di-server)). Tamu mendapat `401`. Semua pegawai boleh memakai `GET /api/koleksi/pemutakhiran`, tetapi hanya pengelola Jadwal Pemutakhiran dan Administrator yang mendapat semua standar; pegawai lain mendapat standar modul yang mereka kelola saja. Selain daftar dan standar modul sendiri, pegawai yang tidak mengelola modul `pemutakhiran` mendapat `403` `{"message": "Modul Jadwal Pemutakhiran belum ditugaskan kepada Anda."}`.

```json
{
  "id": "01m4…", "konten": "Jadwal Samsat Keliling", "modul": "keliling",
  "unit": "Seksi Penetapan dan Penagihan", "frekuensi": "Mingguan, setiap Jumat", "intervalHari": 7,
  "urutan": 1, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/pemutakhiran` | — | `200` standar yang diikuti pengguna, sesuai urutan. |
| `GET /api/koleksi/pemutakhiran/{id}` | — | `200` satu standar bagi pengelola Jadwal Pemutakhiran atau pengelola modul standar itu; `404` bila tidak ada. |
| `POST /api/koleksi/pemutakhiran` | `konten`, `modul`, `unit`, `frekuensi`, `intervalHari` | `201` di akhir daftar. |
| `PATCH /api/koleksi/pemutakhiran/{id}` | sebagian isian di atas | `200`. Standar yang tidak berubah tidak dicatat. |
| `PUT /api/koleksi/pemutakhiran/urutan` | `{"ids": ["…"]}` | `204`. |
| `POST /api/koleksi/pemutakhiran/hapus` | `{"ids": ["…"]}` | `204`. |

Isian divalidasi seperti formulir dashboard: nama konten, penanggung jawab, dan frekuensi wajib (maks. 120 karakter, tanpa karakter kontrol); `modul` salah satu dari `keliling`, `dokumentasi`, `jam`, `layanan`, `berita`, `dokumen`, `papan`, atau `profil` (`{"modul": ["Modul ini tidak dapat dipantau."]}`); `intervalHari` bilangan bulat 1–730.

Penambahan, perubahan, urutan, dan penghapusan tercatat di log aktivitas dengan nama kontennya, mis. "memperbarui Jadwal Samsat Keliling".

Kode: `app/Http/Controllers/Dashboard/UpdateStandard*`, `app/Http/Requests/Dashboard/*UpdateStandard*`, `app/Models/UpdateStandard.php`, `app/Policies/UpdateStandardPolicy.php`, `database/migrations/*_insert_initial_update_standards.php`. Tes: `tests/Feature/UpdateStandardControllerTest.php`.

## Pengaturan Situs di dashboard

Dipakai tab *Situs* di halaman Pengaturan, sidebar, dan tautan ke portal di dashboard ([D-026](decisions.md#d-026--2026-10-14--pengaturan-situs-tersimpan-di-server-hanya-administrator-yang-menyimpan)). Tamu mendapat `401`. Semua pegawai boleh membacanya; pegawai selain Administrator yang menyimpan mendapat `403` `{"message": "Hanya Administrator yang dapat mengubah Pengaturan Situs."}`.

```json
{
  "namaPortal": "PINTU", "kepanjangan": "Portal Informasi dan Pelayanan",
  "namaInstansi": "UPTD Pendapatan Daerah Wilayah Kota Kupang", "namaSingkat": "Samsat Kota Kupang",
  "induk": "Badan Pendapatan dan Aset Daerah Provinsi Nusa Tenggara Timur", "deskripsi": "…",
  "urlPortal": "../", "hakCipta": "© 2026 UPTD Pendapatan Daerah Wilayah Kota Kupang · BPAD Provinsi NTT",
  "bilahPengumuman": { "aktif": true, "teks": "…", "tautanLabel": "Baca", "tautanUrl": "berita-detail.html" }
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/situs` | — | `200` dokumen di atas. Sebelum pertama kali disimpan: identitas dan pengumuman yang tertulis di HTML portal. |
| `PUT /api/pengaturan/situs` | dokumen lengkap | `200` dokumen tersimpan; isian opsional yang kosong disimpan sebagai `""`, `aktif` sebagai boolean, dan kunci lain dibuang. Isi yang sama dengan yang tersimpan tidak disimpan ulang. `422` bila nama portal, nama instansi, atau alamat portal kosong; teks pengumuman kosong selama bilah ditampilkan; batas panjang terlampaui; alamat portal atau tujuan tautan bukan tautan portal yang sah; atau teks berisi karakter kontrol. |

Penyimpanan tidak tercatat di log aktivitas, karena log aktivitas disusun per modul.

Kode: `app/Http/Controllers/Dashboard/SiteController.php`, `app/Http/Requests/Dashboard/UpdateSiteRequest.php`, `app/Policies/SettingPolicy.php`, `app/Actions/SaveSetting.php`, `app/Models/Setting.php`. Tes: `tests/Feature/SiteControllerTest.php`.

## Profil akun di dashboard

Dipakai tab *Akun* di halaman Pengaturan dan menu pengguna di dashboard ([D-027](decisions.md#d-027--2026-10-14--profil-akun-tersimpan-di-server-nama-dan-surel-mengikuti-akun)). Setiap pegawai yang masuk hanya membaca dan mengubah profilnya sendiri; tamu mendapat `401`.

```json
{ "nama": "Maria Pegawai", "jabatan": "Pengelola Data", "unit": "Subbagian Tata Usaha", "peran": "Pemeriksa", "email": "" }
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/pengaturan/akun` | — | `200` profil di atas. `nama` dan `email` berasal dari akun (`email` kosong `""` bila tidak ada); jabatan, unit, dan peran kosong sampai diisi. |
| `PUT /api/pengaturan/akun` | `jabatan`, `unit`, `peran`; `nama` dan `email` boleh ikut dikirim | `200` profil tersimpan; jabatan dan unit yang kosong disimpan sebagai `""`. `422` bila `peran` kosong atau bukan Penyedia, Pemeriksa, Penyetuju, atau Pengelola publikasi; jabatan atau unit lebih dari 120 karakter atau berisi karakter kontrol; atau `nama`/`email` berbeda dari akun (`{"nama": ["Nama mengikuti akun dan hanya dapat diubah Administrator di Akun Pegawai."]}`). |

Penyimpanan profil tidak dicatat di log aktivitas.

Kode: `app/Http/Controllers/Dashboard/OwnAccountController.php`, `app/Http/Requests/Dashboard/UpdateOwnAccountRequest.php`, `app/Models/User.php`. Tes: `tests/Feature/OwnAccountControllerTest.php`.

## Unggahan di dashboard

Penyimpanan berkas dari dashboard ([D-028](decisions.md#d-028--2026-10-14--unggahan-disimpan-di-laravel-cloud-object-storage)). Tamu mendapat `401`. Dashboard memakainya untuk tujuan yang modulnya sudah di server, saat ini `visiMisi`, `profil` ([D-029](decisions.md#d-029--2026-10-14--foto-tentang-uptd-dan-maklumat-diunggah-ke-server-profil-disusun-dari-renstra-dan-rpjmd)), `dokumen` ([D-030](decisions.md#d-030--2026-10-14--regulasi--unduhan-tersimpan-di-server-dengan-alur-persetujuan)), `berita` ([D-031](decisions.md#d-031--2026-10-14--berita--pengumuman-tersimpan-di-server-pengelola-berita-menerbitkan-sendiri)), `dokumentasi` ([D-032](decisions.md#d-032--2026-10-14--dokumentasi-kegiatan-tersimpan-di-server-galeri-disusun-per-bulan)), `unit` ([D-033](decisions.md#d-033--2026-10-14--struktur-organisasi-tersimpan-di-server-nip-pejabat-tidak-ditampilkan-di-portal)), dan `beranda` untuk logo mitra ([D-037](decisions.md#d-037--2026-10-14--mitra-satu-atap-memakai-logo-inisial-sebagai-cadangan)). Aplikasi Absensi PINTU memakai tujuan `absensi` ([D-034](decisions.md#d-034--2026-10-14--absensi-lapangan-tersimpan-di-server-petugas-clock-in-lewat-aplikasi-absen)).

| Alamat | Permintaan | Respons |
|---|---|---|
| `POST /api/unggah` | `multipart/form-data`: `modul` (tujuan: `berita`, `dokumentasi`, `dokumen`, `unit`, `visiMisi`, `profil`, `beranda`, atau `absensi`) dan `berkas` | `201` `{ "nama": "Foto Loket.jpg", "ukuran": 182400, "tipe": "image/jpeg", "url": "…" }`. `url` adalah alamat bucket publik, atau `/api/berkas/{id}` untuk foto absensi. `403` bila pegawai tidak mengelola modul tujuan, mis. `{"message": "Modul Berita & Pengumuman belum ditugaskan kepada Anda."}`; foto absensi boleh diunggah semua pegawai. `422` bila tujuan tidak dikenal, berkas kosong, formatnya tidak sesuai tujuan menurut isinya (`{"berkas": ["Format berkas harus JPG, PNG, atau WebP."]}`), gambar lebih dari 5 MB atau 25 megapiksel, atau dokumen lebih dari 10 MB. `429` setelah 30 unggahan per menit. |
| `GET /api/berkas/{id}` | — | `200` isi berkas privat dengan `Content-Type` hasil deteksi, `X-Content-Type-Options: nosniff`, dan `Cache-Control: no-store, private`. Hanya bagi pengunggahnya dan Administrator; pegawai lain mendapat `403` `{"message": "Berkas ini tidak dapat Anda buka."}`. `404` untuk berkas publik dan berkas yang tidak ada. |

Format per tujuan: gambar (JPG, PNG, WebP) untuk semua tujuan kecuali `visiMisi`; `dokumen` juga menerima PDF, DOCX, dan XLSX; `visiMisi` hanya PDF. Gambar disimpan ulang oleh server tanpa metadata dan paling panjang 1600 px; dokumen disimpan apa adanya. Nama berkas asli hanya untuk tampilan; nama simpannya acak.

Berkas yang belum dipakai konten tersimpan dihapus otomatis setelah sehari.

Kode: `app/Http/Controllers/Dashboard/UploadController.php`, `app/Http/Requests/Dashboard/StoreUploadRequest.php`, `app/Actions/StoreUpload.php`, `app/Enums/UploadTarget.php`, `app/Models/Upload.php`, `app/Policies/UploadPolicy.php`. Tes: `tests/Feature/UploadControllerTest.php`.

## Pesan & Pengaduan di dashboard

Dipakai halaman *Pesan & Pengaduan* di dashboard ([D-016](decisions.md#d-016--2026-10-13--pesan--pengaduan-tersimpan-di-server)). Tamu mendapat `401`; pegawai yang tidak mengelola modul `pesan` mendapat `403` `{"message": "Modul Pesan & Pengaduan belum ditugaskan kepada Anda."}`.

```json
{
  "id": "01m4…", "tiket": "PINTU-7KQ2MX", "jenis": "pengaduan", "nama": "Yohanes", "kontak": "081234567890",
  "layanan": "Samsat Keliling", "isi": "…", "sumber": "formulir", "status": "diproses",
  "diterima": "2026-10-13T08:00:00+00:00", "ditangani": "Maria Bria",
  "tanggapan": [{ "id": "01m4…", "isi": "…", "oleh": "Maria Bria", "waktu": "2026-10-13T09:00:00+00:00" }],
  "urutan": 0, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/koleksi/pesan` | — | `200` semua pesan, terbaru lebih dulu, dengan tanggapannya. |
| `GET /api/koleksi/pesan/{id}` | — | `200` satu pesan; `404` bila tidak ada. |
| `POST /api/koleksi/pesan` | `{"jenis", "nama", "kontak", "layanan", "isi", "sumber"}`; nama dan kontak boleh kosong | `201`. Server mengisi tiket, status `baru`, dan waktu terima; nilai lain dari klien diabaikan. Dicatat sebagai "mencatat PINTU-…". |
| `PATCH /api/koleksi/pesan/{id}` | sebagian dari `{"status", "tanggapan"}` | `200`. `status`: `baru`, `diproses`, atau `selesai`. `tanggapan` adalah daftar lengkap seperti di dashboard; butir yang ID-nya belum ada di server ditambahkan atas nama akun yang masuk (hanya `isi` yang dipakai, maks. 2.000 karakter teks polos), butir lama dibiarkan. Isian pengirim dan `ditangani` diabaikan. Pegawai yang pertama memproses atau menanggapi menjadi `ditangani`. Dicatat sebagai "menanggapi", "memproses", "menyelesaikan", atau "menandai baru", dengan target nomor tiket. |
| `POST /api/koleksi/pesan/hapus` | `{"ids": ["…"]}` | `204`. Hanya Administrator; pegawai lain `403` `{"message": "Hanya Administrator yang dapat menghapus pesan. Tandai selesai untuk menutupnya."}`. Tanggapan ikut terhapus. |

Kontak harus berupa email atau nomor telepon/WhatsApp (min. 9 angka); layanan salah satu dari *Umum / portal PINTU*, *Pajak tahunan*, *Perpanjangan STNK 5 tahun*, *Mutasi & balik nama*, *Duplikat STNK*, *Samsat Keliling*.

Kode: `app/Http/Controllers/Dashboard/MessageController.php`, `MessageDeletionController.php`, `app/Http/Requests/Dashboard/*Message*`, `app/Actions/ReceiveMessage.php`, `UpdateMessage.php`, `app/Models/Message.php`, `MessageResponse.php`, `app/Policies/MessagePolicy.php`, `app/Rules/ContactAddress.php`. Tes: `tests/Feature/MessageControllerTest.php`.

## Absensi Lapangan di dashboard

Dipakai halaman *Absensi Lapangan* di dashboard ([D-034](decisions.md#d-034--2026-10-14--absensi-lapangan-tersimpan-di-server-petugas-clock-in-lewat-aplikasi-absen)). Tamu mendapat `401`; pegawai selain Administrator mendapat `403` `{"message": "Hanya Administrator yang dapat mengelola kegiatan lapangan."}`. Perubahan kegiatan tidak dicatat di log aktivitas.

```json
{
  "id": "01m4…", "nama": "Tilang gabungan Jl. El Tari", "tanggal": "2026-10-20", "tempat": "Depan Kantor Gubernur",
  "lat": -10.1637, "lng": 123.5974, "radius": 150, "catatan": "",
  "petugas": [{ "id": "01m4…", "nama": "…", "nip": "…" }],
  "jumlahHadir": 1, "dibuat": "…", "diperbarui": "…"
}
```

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/absensi/kegiatan` | — | `200` semua kegiatan, tanggal terbaru lebih dulu lalu menurut nama; petugas urut nama. |
| `GET /api/absensi/kegiatan/{id}` | — | `200` satu kegiatan ditambah `absen`, clock-in petugasnya: `[{ "id", "petugas": { "id", "nama", "nip" }, "waktu", "lat", "lng", "akurasi", "jarak", "foto" }]`, termasuk petugas yang tidak lagi ditugaskan. `foto` adalah alamat `/api/berkas/{id}`. `404` bila tidak ada. |
| `POST /api/absensi/kegiatan` | isi kegiatan seperti di atas, `petugas` berupa daftar ID akun | `201`. |
| `PATCH /api/absensi/kegiatan/{id}` | sebagian isi; `petugas`, bila dikirim, menggantikan daftar petugas | `200`. Clock-in yang sudah tercatat tetap, dengan jarak saat itu. |
| `POST /api/absensi/kegiatan/hapus` | `{"ids": ["…"]}` | `204`; clock-in dan swafotonya ikut terhapus. Kegiatan yang sudah tidak ada dilewati. |

Isi divalidasi seperti formulir dashboard: nama dan tempat wajib (maks. 160 karakter); tanggal `YYYY-MM-DD`; `lat` −90 sampai 90 dan `lng` −180 sampai 180, dibulatkan 6 desimal; radius bilangan bulat 20–2000 meter; catatan maks. 300; `petugas` wajib dikirim saat menambah, berisi maks. 200 ID akun yang ada tanpa duplikat; tanpa karakter kontrol.

### Clock-in petugas

Dipakai aplikasi Absensi PINTU di `/absen`. Tamu mendapat `401`. Swafoto diunggah lebih dulu lewat `POST /api/unggah` dengan `modul=absensi`.

| Alamat | Permintaan | Respons |
|---|---|---|
| `GET /api/absen` | — | `200` `{"tanggal": "2026-10-20", "kegiatan": [{ "id", "nama", "tempat", "lat", "lng", "radius", "catatan", "absen": null }]}`: kegiatan hari ini (WITA) yang menugaskan pegawai itu, urut nama. Setelah ia clock-in, `absen` berisi `{ "waktu", "jarak", "akurasi" }`. |
| `POST /api/absen/{id}` | `{"foto": "<alamat swafoto>", "lat": -10.16371, "lng": 123.59742, "akurasi": 12.4}` | `201` `{"waktu": "…", "jarak": 3, "akurasi": 13}`. Waktu dari jam server; akurasi dibulatkan ke atas. `403` `{"message": "Anda tidak ditugaskan di kegiatan ini."}`. `422` per isian: `kegiatan` bila sudah clock-in (`"Anda sudah clock-in di kegiatan ini pukul 07.45 WITA."`) atau bukan tanggal kegiatan (`"Clock-in kegiatan ini hanya dibuka pada tanggal kegiatan."`); `akurasi` bila lebih buruk dari ±100 m; `lokasi` bila di luar radius (`"Anda berada 240 m dari lokasi kegiatan. Clock-in hanya dalam radius 150 m."`); `foto` bila swafoto bukan unggahan `absensi` milik pegawai itu dari 15 menit terakhir yang belum dipakai (`"Ambil swafoto baru untuk clock-in."`). `429` bersama batas 30 unggahan per menit. |

Kode: `app/Http/Controllers/Dashboard/FieldActivity*`, `app/Http/Controllers/Dashboard/ClockInController.php`, `app/Http/Requests/Dashboard/*FieldActivit*`, `app/Http/Requests/Dashboard/ClockInRequest.php`, `app/Actions/ClockIn.php`, `app/Models/FieldActivity.php`, `app/Models/FieldAttendance.php`, `app/Policies/FieldActivityPolicy.php`. Tes: `tests/Feature/FieldActivityControllerTest.php`, `tests/Feature/ClockInControllerTest.php`, `tests/Feature/ClockInPageTest.php`.

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

Jadwal layanan di luar kantor untuk portal publik ([D-043](decisions.md#d-043--2026-10-15--layanan-luar-kantor-tiga-unit-samsat-keliling-gerai-tetap-dan-loket-sementara)). Beranda dan halaman Jadwal memuatnya setiap kali dibuka (`initKeliling()` di `public/assets/js/main.js`).

```json
{
  "dari": "2026-10-15", "sampai": "2026-10-25",
  "jadwal": [
    { "tanggal": "2026-10-17", "jenis": "keliling", "nama": "Samsat Keliling 01", "mulai": "06:00", "selesai": "09:00", "lokasi": "CFD Eltari", "kecamatan": "Kec. Oebobo", "layanan": "Pajak tahunan", "catatan": "" }
  ],
  "gerai": [
    { "nama": "Samsat Corner", "tanggalMulai": null, "tanggalSelesai": null, "hari": [1, 2, 3, 4, 5], "mulai": "10:00", "selesai": "17:00", "lokasi": "Lippo Plaza Kupang", "kecamatan": "Kec. Oebobo", "layanan": "Pajak tahunan", "catatan": "" }
  ],
  "sementara": [
    { "nama": "Loket Kantor Lurah Oebobo", "tanggalMulai": "2026-10-19", "tanggalSelesai": "2026-10-23", "hari": [1, 2, 3, 4, 5], "mulai": "08:00", "selesai": "12:00", "lokasi": "Kantor Lurah Oebobo", "kecamatan": "Kec. Oebobo", "layanan": "Pajak tahunan", "catatan": "" }
  ]
}
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`, jadwal `aktif` saja. `dari` adalah hari ini (WITA) dan `sampai` Minggu pekan depan. `jadwal` berisi setiap jenis pada setiap tanggal ia melayani di rentang itu, urut tanggal, jam mulai, jam selesai, jenis (Samsat Keliling, loket sementara, gerai), dan nama; `nama` Samsat Keliling berbentuk "Samsat Keliling 01". `gerai` dan `sementara` berisi gerai dan loket yang belum berakhir beserta hari dan tanggalnya. Daftar kosong bila tidak ada. Tanpa ID, `aktif`, `urutan`, atau waktu simpan. Teks dikirim persis seperti tersimpan dan wajib ditampilkan sebagai teks polos. |
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

## `POST /api/public/kendaraan`

Cek biaya pajak di beranda dan halaman Layanan ([D-041](decisions.md#d-041--2026-10-14--kalkulator-pkb-dan-cek-biaya-pajak-memakai-mesin-kalkulator-pajak-tarif--simulasi-dipensiunkan), `resources/js/portal/cek-pajak.ts`). Permintaan: `{"nopol": "DH 1234 AB", "rangka": "A5678"}`, yaitu nomor polisi dan 5 digit terakhir nomor rangka; keduanya dinormalisasi.

| Hal | Perilaku |
|---|---|
| Berhasil | `200` data kendaraan seperti `GET /api/kendaraan/{nopol}`, hanya bila 5 karakter terakhir nomor rangka di data BPAD sama. Nomor rangka dan nomor mesin tidak pernah dikirim. Nama pemilik wajib ditampilkan sebagai teks polos. |
| Tidak cocok | `404` `{"message": "Nomor polisi atau nomor rangka tidak cocok dengan data kendaraan."}`, sama untuk nomor rangka salah, kendaraan tidak dikenal, dan nomor rangka yang tidak tercatat. |
| Galat | `422` dengan `errors` bila nomor polisi tidak berbentuk seperti DH 1234 AB atau nomor rangka bukan 5 huruf atau angka. `422` `{"message": "… Silakan cek biaya pajak di loket Samsat."}` bila cocok tetapi referensi NJKB tidak bisa dipakai. `429` `{"message": "Terlalu banyak pengecekan dari jaringan ini. Coba lagi beberapa saat lagi."}` setelah 5 pengecekan per menit atau 20 per jam dari satu alamat IP, atau 30 per menit untuk seluruh portal. `503` seperti pencarian di dashboard. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicVehicleController.php`, `app/Http/Requests/CheckPublicVehicleRequest.php`, `app/Actions/LookUpVehicle.php`, `app/Providers/AppServiceProvider.php` (`public-kendaraan`). Tes: `tests/Feature/VehicleLookupTest.php`.

## `GET /api/public/statistik`

Dasbor transparansi di beranda ([D-018](decisions.md#d-018--2026-10-13--statistik-layanan-tersimpan-di-server-hanya-angka-terverifikasi-yang-terbit), `initDash()` di `public/assets/js/main.js`). Hanya statistik berstatus `terverifikasi` yang diterbitkan: bentuknya sama dengan dokumen dashboard tanpa `status` dan tanpa `id` angka utama, ditambah `diperbarui` (waktu terakhir disimpan).

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. Selama statistik berstatus `ilustrasi` atau belum pernah disimpan: `{"tahun": "", "catatan": "", "set": [], "diperbarui": null}`, tanpa angka apa pun, dan beranda menyatakan statistik layanan belum diterbitkan. Teks wajib ditampilkan sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah statistik kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicStatisticsController.php`, `app/Actions/ShowPublicStatistics.php`. Tes: `tests/Feature/PublicStatisticsControllerTest.php`.

## `GET /api/public/kontak`

Alamat, kontak, dan kanal pengaduan untuk footer semua halaman dan halaman Kontak & Pengaduan ([D-019](decisions.md#d-019--2026-10-13--kontak--kanal-tersimpan-di-server), `initKontak()` di `public/assets/js/main.js`). Bentuknya sama dengan dokumen dashboard tanpa `id` butir, ditambah `diperbarui`: waktu terakhir disimpan, atau `null` bila belum pernah disimpan.

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. Bila `diperbarui` `null`, semua isian kosong dan portal menyatakan alamat, nomor kontak, dan kanal pengaduan belum tersedia. Teks wajib ditampilkan sebagai teks polos; tautan hanya dipasang bila skemanya diizinkan. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah kontak kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicContactController.php`, `app/Actions/ShowPublicContact.php`. Tes: `tests/Feature/PublicContactControllerTest.php`.

## `GET /api/public/visi-misi`

Halaman Visi & Misi di portal ([D-020](decisions.md#d-020--2026-10-13--visi--misi-tersimpan-di-server-unggahan-maklumat-ditunda), `initVisiMisi()` di `public/assets/js/main.js`). Bentuknya sama dengan dokumen dashboard tanpa `id` butir, ditambah `diperbarui`: waktu terakhir disimpan, atau `null` selama isinya masih teks resmi awal.

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. Teks wajib ditampilkan sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah teks lain sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicVisionMissionController.php`, `app/Actions/ShowPublicVisionMission.php`. Tes: `tests/Feature/PublicVisionMissionControllerTest.php`.

## `GET /api/public/profil`

Halaman Profil (Tentang UPTD) di portal ([D-021](decisions.md#d-021--2026-10-14--tentang-uptd-tersimpan-di-server-unggahan-foto-ditunda), `initProfil()` di `public/assets/js/main.js`). Bentuknya sama dengan dokumen dashboard tanpa `id` butir, ditambah `diperbarui`: waktu terakhir disimpan, atau `null` selama isinya masih teks awal.

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. Teks wajib ditampilkan sebagai teks polos. `foto` dan `sambutan.foto` berisi alamat bucket publik atau `null`; selama `null`, portal memakai foto gedung bawaannya dan ilustrasi Kepala UPTD ([D-029](decisions.md#d-029--2026-10-14--foto-tentang-uptd-dan-maklumat-diunggah-ke-server-profil-disusun-dari-renstra-dan-rpjmd)). |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah teks lain sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicProfileController.php`, `app/Actions/ShowPublicProfile.php`. Tes: `tests/Feature/PublicProfileControllerTest.php`.

## `GET /api/public/layanan`

Halaman Layanan dan bagian *Layanan utama* di beranda ([D-022](decisions.md#d-022--2026-10-14--persyaratan-layanan-tersimpan-di-server-dengan-alur-persetujuan), `initLayanan()` di `public/assets/js/main.js`). Daftar layanan berstatus `terbit` sesuai urutan, dengan bentuk dokumen dashboard tanpa `id`, tanpa `id` berkas, dan tanpa kolom alur selain `terbitPada`.

| Hal | Perilaku |
|---|---|
| Berhasil | `200` daftar layanan. Layanan berjenis `tambahan` ditampilkan sebagai lipatan di kartu layanan terakhir; layanan `unggulan` menjadi tab di beranda. Teks wajib ditampilkan sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah daftar kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicServiceController.php`, `app/Actions/ListPublicServices.php`. Tes: `tests/Feature/PublicServiceControllerTest.php`.

## `GET /api/public/dokumen`

Halaman Regulasi & Unduhan ([D-030](decisions.md#d-030--2026-10-14--regulasi--unduhan-tersimpan-di-server-dengan-alur-persetujuan), `initDokumen()` di `public/assets/js/main.js`). Daftar dokumen berstatus `terbit`, per kategori (`regulasi`, `standar`, `formulir`, `laporan`) lalu sesuai urutan masuk, dengan bentuk dokumen dashboard tanpa `id` dan tanpa kolom alur selain `terbitPada`.

| Hal | Perilaku |
|---|---|
| Berhasil | `200` daftar dokumen, `[]` bila belum ada yang terbit. Dokumen dengan `berkas` diunduh dari `berkas.url`; dokumen lain dibuka lewat `tautan` di tab baru. Portal hanya memakai alamat `http(s)` dan menampilkan teks sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah daftar kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicDocumentController.php`, `app/Actions/ListPublicDocuments.php`. Tes: `tests/Feature/PublicDocumentControllerTest.php`.

## `GET /api/public/berita` dan `GET /api/public/berita/{slug}`

Halaman Informasi, halaman `/berita/{slug}`, dan ubin *Pengumuman* di papan layanan beranda ([D-031](decisions.md#d-031--2026-10-14--berita--pengumuman-tersimpan-di-server-pengelola-berita-menerbitkan-sendiri), `initInformasi()`, `initBerita()`, dan `initPengumumanBeranda()` di `public/assets/js/main.js`). Hanya tulisan berstatus `terbit`, tanpa `id` dan tanpa kolom alur selain `terbitPada`.

| Hal | Perilaku |
|---|---|
| Daftar | `GET /api/public/berita` → `200` daftar tulisan tanpa isi, tanggal tayang terbaru lebih dulu (lalu waktu terbit terbaru): `judul`, `slug`, `kategori`, `ringkasan`, `sampul`, `keteranganSampul`, `tanggal`, `sorotan`, `pengumumanBeranda`, `menitBaca`, `terbitPada`. `[]` bila belum ada yang terbit. |
| Satu tulisan | `GET /api/public/berita/{slug}` → `200` dengan `isi` (HTML editor), `kreditFoto`, `pemeriksa` (tampil sebagai "Diverifikasi oleh"), dan `diperbarui`. Tulisan yang tidak ada atau tidak terbit dijawab `404` `{"message": "Data tidak ditemukan."}`. Portal menyalin isi lewat daftar elemen yang diizinkan, memasang teks sebagai teks polos, dan hanya memakai foto beralamat `http(s)`. |
| `menitBaca` | Perkiraan waktu baca isi: 200 kata per menit, dibulatkan, minimal 1. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah daftar kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Halaman `/berita/{slug}` adalah `resources/views/portal/berita-detail.blade.php`. Alamat relatifnya ditulis ulang menjadi alamat dari akar situs oleh `ArticlePageController`; slug yang bukan huruf kecil, angka, dan tanda hubung dijawab `404`.

Kode: `app/Http/Controllers/PublicArticleController.php`, `app/Http/Controllers/ArticlePageController.php`, `app/Actions/ListPublicArticles.php`, `app/Actions/ShowPublicArticle.php`. Tes: `tests/Feature/PublicArticleControllerTest.php`, `tests/Feature/PortalPagesTest.php`.

## `GET /api/public/dokumentasi`

Galeri halaman Dokumentasi Kegiatan ([D-032](decisions.md#d-032--2026-10-14--dokumentasi-kegiatan-tersimpan-di-server-galeri-disusun-per-bulan), `initDokumentasi()` dan `initGallery()` di `public/assets/js/main.js`). Daftar kegiatan berstatus `terbit`, tanggal terbaru lebih dulu, tanpa `id` dan tanpa kolom alur selain `terbitPada`: `judul`, `kategori`, `lokasi`, `tanggal`, `deskripsi`, `foto` (`src` dan `keterangan`, sesuai urutan di dashboard).

| Hal | Perilaku |
|---|---|
| Berhasil | `200` daftar kegiatan, `[]` bila belum ada yang terbit. Portal mengelompokkan foto per bulan pada tahun terbaru dan per tahun sebelumnya, hanya memakai foto beralamat `http(s)`, dan menampilkan teks sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca, tidak pernah daftar kosong sebagai gantinya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicDocumentationController.php`, `app/Actions/ListPublicDocumentations.php`. Tes: `tests/Feature/PublicDocumentationControllerTest.php`.

## `GET /api/public/struktur`

Bagan dan uraian tugas halaman Struktur Organisasi ([D-033](decisions.md#d-033--2026-10-14--struktur-organisasi-tersimpan-di-server-nip-pejabat-tidak-ditampilkan-di-portal), `initStruktur()` di `public/assets/js/main.js`).

```json
{
  "unit": [{ "nama": "Kepala UPTD", "singkatan": "KU", "jenis": "pimpinan", "jabatan": "…", "pejabat": "Remmy Christian Pah, S.STP, M.Si", "foto": null, "anggota": [], "uraianTugas": "…", "catatan": "", "bawahan": [ … ] }],
  "mitra": [{ "nama": "Polri · Satlantas", "singkatan": "PL", "foto": null, "uraianTugas": "…", "catatan": "Mitra satu atap Samsat · garis koordinasi, bukan garis komando." }]
}
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. `unit` berisi unit UPTD teratas sesuai urutan, masing-masing dengan `bawahan` (bentuk yang sama, bertingkat). Unit yang unit induknya sudah tidak ada ditampilkan di tingkat teratas. `mitra` berisi mitra satu atap; `foto` mitra adalah logonya dan portal menampilkannya di tempat inisial ([D-037](decisions.md#d-037--2026-10-14--mitra-satu-atap-memakai-logo-inisial-sebagai-cadangan)). Tanpa ID dan tanpa NIP. Portal hanya memakai foto beralamat `http(s)` dan menampilkan teks sebagai teks polos. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicOrganizationController.php`, `app/Actions/ShowPublicOrganization.php`. Tes: `tests/Feature/PublicOrganizationControllerTest.php`.

## `GET /api/public/beranda`

Teks beranda portal ([D-023](decisions.md#d-023--2026-10-14--teks-beranda-tersimpan-di-server-teks-html-tetap-sebagai-isi-awal), `initBeranda()` di `public/assets/js/main.js`; judul pembuka diisi komponen React `resources/js/portal/beranda/judul-pembuka.tsx`, [D-045](decisions.md#d-045--2026-10-15--portal-beralih-ke-react-bertahap-kata-bergilir-di-judul-pembuka)). Bentuknya sama dengan dokumen dashboard tanpa `id` butir, ditambah `diperbarui`: waktu terakhir disimpan, atau `null` selama isinya masih teks di HTML. `hero.kataBergilir` selalu dipakai, juga saat `diperbarui` `null`: kata judul yang sama dengan salah satunya bergilir satu putaran lalu berhenti di kata judul.

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. Bila `diperbarui` `null`, beranda membiarkan teks HTML; bila tidak, teksnya diganti isi ini. Teks wajib ditampilkan sebagai teks polos; tujuan tombol dipasang hanya bila skemanya diizinkan. `video` berisi `{ "id", "label", "durasi", "judul" }` (ID video YouTube, tanpa tautannya) atau `null` bila belum diisi; tombol "Tonton alur pelayanan" hanya tampil bila ada video, dan pemutar `youtube-nocookie.com` baru dimuat saat dialog dibuka. `logo` mitra (alamat bucket publik atau `null`) menggantikan inisial di lencana bila beralamat `http(s)`. Isi kanal dengan `tautan` yang sah menjadi tautan; alamat `http(s)` dibuka di tab baru, halaman portal, `tel:`, dan `mailto:` di tab yang sama; tautan lain diabaikan dan isinya tampil sebagai teks. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca. Beranda tetap menampilkan teks HTML-nya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicHomepageController.php`, `app/Actions/ShowPublicHomepage.php`. Tes: `tests/Feature/PublicHomepageControllerTest.php`.

## `GET /api/public/halaman/{slug}`

Isi halaman Kebijakan Privasi (`kebijakan-privasi`) dan Syarat & Ketentuan (`syarat-ketentuan`) ([D-024](decisions.md#d-024--2026-10-14--halaman-statis-tersimpan-di-server-teks-html-tetap-sebagai-isi-awal), `initHalaman()` di `public/assets/js/main.js`).

```json
{ "judul": "Informasi resmi.", "judulLanjutan": "Dipakai dengan bijak.", "pengantar": "Syarat & ketentuan · berlaku sejak [tanggal terbit]", "isi": "<h2>Sifat informasi.</h2><p>…</p>", "diperbarui": null }
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`. `diperbarui` berisi waktu terakhir disimpan, atau `null` selama halaman belum pernah disimpan; portal lalu membiarkan teks HTML-nya. `isi` adalah HTML terbatas dari editor dashboard. Klien wajib membangunnya ulang hanya dari elemen yang diizinkan (lihat D-024), bukan memasangnya langsung sebagai HTML. |
| Galat | `404` untuk nama halaman lain; `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca. Halaman tetap menampilkan teks HTML-nya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicStaticPageController.php`, `app/Actions/ShowPublicStaticPage.php`. Tes: `tests/Feature/PublicStaticPageControllerTest.php`.

## `GET /api/public/situs`

Deskripsi dan hak cipta di kaki halaman portal serta bilah pengumuman di halaman Informasi ([D-026](decisions.md#d-026--2026-10-14--pengaturan-situs-tersimpan-di-server-hanya-administrator-yang-menyimpan), `initSitus()` di `public/assets/js/main.js`).

```json
{ "deskripsi": "…", "hakCipta": "…", "bilahPengumuman": { "aktif": true, "teks": "…", "tautanLabel": "Baca", "tautanUrl": "berita-detail.html" }, "diperbarui": null }
```

| Hal | Perilaku |
|---|---|
| Berhasil | `200`, hanya bagian yang dipakai portal. `diperbarui` berisi waktu terakhir disimpan, atau `null` selama isinya masih teks di HTML; portal lalu membiarkan teks HTML-nya. Teks wajib ditampilkan sebagai teks polos; tujuan tautan dipasang hanya bila skemanya diizinkan. |
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah batas laju yang sama dengan `GET /api/public/faq`; `503` bila data tidak bisa dibaca. Portal tetap menampilkan teks HTML-nya. |
| Cache | `Cache-Control: no-store`; tidak memulai sesi dan tidak memasang cookie. |

Kode: `app/Http/Controllers/PublicSiteController.php`, `app/Actions/ShowPublicSite.php`. Tes: `tests/Feature/PublicSiteControllerTest.php`.

## `POST /api/public/pesan`

Formulir Kontak di portal ([D-016](decisions.md#d-016--2026-10-13--pesan--pengaduan-tersimpan-di-server), `initPesanForm()` di `public/assets/js/main.js`). Satu-satunya API publik yang menulis data.

```json
{ "jenis": "pengaduan", "nama": "Yohanes", "kontak": "+62 812-3456-7890", "layanan": "Samsat Keliling", "isi": "…", "persetujuan": true, "kode_tambahan": "" }
```

| Hal | Perilaku |
|---|---|
| Berhasil | `201` `{"tiket": "PINTU-7KQ2MX"}`. Pesan disimpan dengan sumber `formulir` dan status `baru`; sumber, status, atau isian lain dari pengunjung diabaikan. |
| Validasi | `422` dengan pesan per isian bila jenis atau layanan tidak dikenal, kontak kosong atau bukan email/nomor telepon, isi kurang dari 10 atau lebih dari 2.000 karakter, berisi karakter kontrol, atau persetujuan tidak dicentang. |
| Anti-spam | Bila `kode_tambahan` (isian tersembunyi) terisi, jawabannya tetap `201` dengan nomor tiket, tetapi pesan tidak disimpan. `429` setelah 5 pesan per jam dari satu IP: `{"message": "Terlalu banyak pesan dari jaringan ini. Coba lagi dalam satu jam, atau hubungi kami lewat kanal lain."}`. Batas laju API publik umum (D-013) juga berlaku. |
| Privasi | Tidak memulai sesi dan tidak memasang cookie. Log hanya mencatat `message.received` dengan nomor tiket. |

Kode: `app/Http/Controllers/PublicMessageController.php`, `app/Http/Requests/SubmitPublicMessageRequest.php`. Tes: `tests/Feature/PublicMessageControllerTest.php`.
