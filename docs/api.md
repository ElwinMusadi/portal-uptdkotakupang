# API PINTU

Semua endpoint ada di `routes/api.php` dan mengembalikan JSON polos (tanpa amplop `data`). Galat berbentuk `{"message": "…"}`, ditambah `errors` untuk galat validasi. Respons dari rute API memakai `Cache-Control: no-store`.

| Metode | Alamat | Akses | Status |
|---|---|---|---|
| `GET` | `/api/health` | anonim | tersedia |
| `GET` | `/api/public/faq` | anonim, hanya baca | tersedia |
| `POST` | `/api/masuk` | halaman Login Pegawai | tersedia |
| `GET` | `/api/sesi` | pegawai login | tersedia |
| `POST` | `/api/keluar` | pegawai login | tersedia |
| — | `/api/koleksi/…`, `/api/pengaturan/…` (kontrak dashboard) | pegawai login | belum dibuat, lihat [docs/dashboard.md](dashboard.md#menyambungkan-ke-server-rest-api) |

## Login pegawai (Laravel Sanctum)

Sesi berupa cookie HttpOnly milik Laravel; portal, dashboard, dan API satu origin, jadi tidak ada token di peramban ([D-007](decisions.md#d-007--2026-10-13--login-pegawai-dengan-laravel-sanctum)). Permintaan dianggap berasal dari peramban bila `Referer`/`Origin`-nya host aplikasi sendiri. Permintaan yang mengubah data wajib membawa header `X-XSRF-TOKEN` (isi cookie `XSRF-TOKEN`), yang bisa diperbarui lewat `GET /sanctum/csrf-cookie`.

| Alamat | Permintaan | Respons |
|---|---|---|
| `POST /api/masuk` | `{"nip": "18 digit", "sandi": "…", "ingat": false}` | `200` dengan data sesi. `422` bila isian tidak valid, NIP/sandi salah (pesan sama untuk keduanya), atau akun tidak aktif (hanya setelah sandi benar). `429` bila percobaan melewati 5 per NIP+IP per menit atau 20 per NIP per 15 menit (dihitung sebelum sandi diperiksa, dihapus setelah berhasil masuk), atau 30 permintaan login per IP per menit. `400` bila tidak dikirim dari peramban di origin aplikasi. `419` bila token CSRF tidak cocok. |
| `GET /api/sesi` | — | `200` dengan data sesi; `401` bila belum masuk atau akun sudah tidak aktif (sesinya diakhiri). |
| `POST /api/keluar` | — | `204`; sesi dihapus dan token CSRF diganti. |

Data sesi:

```json
{ "nip": "199707162026061002", "nama": "Admin PINTU", "peran": "administrator", "masuk": "2026-10-13T06:33:17+00:00" }
```

`peran` bernilai `administrator` atau `pegawai`; `masuk` kosong (`null`) bila waktu login belum tercatat di sesi itu. Rute `/dashboard` memakai sesi yang sama: tamu dialihkan ke `/login?lanjut=dashboard%2F`. Token bearer (`Authorization: Bearer …`) diabaikan.

Aktivasi akun memakai halaman web, bukan API: `GET` dan `POST /aktivasi/{user}?expires=…&kunci=…&signature=…`, dengan tautan dari `php artisan pintu:create-admin --no-interaction` atau `php artisan pintu:activation-link`. Tautan yang kedaluwarsa, sudah dipakai, diganti, diubah, atau untuk akun yang tidak lagi `pending` dijawab `410`. Aktivasi yang berhasil dialihkan ke `/login?aktif=1`.

## `GET /api/public/faq`

Daftar tanya jawab untuk portal publik. Menjalankan keputusan pemilik produk GC18-01–09 ([Phase 2.19](phases/PHASE-2.19-GC18-DECISION-BINDING-AND-CLOSURE-EVIDENCE-COORDINATION.md)), dengan urutan menurut [D-003](decisions.md#d-003--2026-10-13--urutan-faq-publik-mengikuti-dashboard).

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
| Galat | `422` untuk query parameter apa pun; `405` untuk metode tulis; `429` setelah `PINTU_PUBLIC_API_REQUESTS_PER_MINUTE` (bawaan 60) permintaan per menit per IP; `503` bila tabel tidak bisa dibaca atau batas terlampaui. Penyebab `503` dicatat maksimal sekali per menit. |
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
