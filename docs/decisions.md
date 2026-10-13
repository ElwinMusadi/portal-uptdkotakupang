# Catatan Keputusan PINTU

Keputusan pemilik produk (PINTU Project Owner) yang mengikat pengembangan. Keputusan baru ditambahkan di bawah; keputusan lama tidak diubah, cukup dinyatakan digantikan. Rekam jejak tata kelola sebelumnya ada di [`docs/phases/`](phases/).

## D-001 · 2026-10-13 · Arsitektur aplikasi: React + JSON API

Dashboard tetap React 19 (TypeScript, Vite, Tailwind CSS v4, shadcn/ui) yang berbicara dengan Laravel lewat JSON API. **Tidak memakai Livewire maupun Inertia.**

## D-002 · 2026-10-13 · Satu aplikasi Laravel di root repository

Seluruh proyek menjadi satu aplikasi Laravel standar, tanpa folder `backend/` atau `frontend/` terpisah:

| Bagian | Lokasi |
|---|---|
| Halaman portal publik (Blade, isi sama persis dengan HTML lama) | `resources/views/portal/`, halaman galat `resources/views/errors/404.blade.php` |
| Aset portal (CSS, JS, gambar) | `public/assets/` |
| Dashboard React | `resources/js/dashboard/`, dibuild Vite ke `public/build/`, disajikan di `/dashboard` |
| API JSON | `routes/api.php` |

Alasan: satu deploy di Laravel Cloud; dashboard dan API satu origin (tanpa CORS, siap memakai sesi cookie Sanctum yang dianjurkan untuk SPA milik sendiri); halaman portal bisa menampilkan konten dari database secara bertahap; perkakas Laravel (Boost, Pint, PHPUnit, Vite) bekerja tanpa penyesuaian path.

Alamat lama `*.html` dialihkan permanen (301) ke alamat bersih dengan query string tetap. Cloudflare Pages masih melayani produksi dari branch `main` sampai cutover ke Laravel Cloud.

## D-003 · 2026-10-13 · Urutan FAQ publik mengikuti dashboard

`GET /api/public/faq` mengurutkan menurut `urutan` (diatur editor dengan seret-lepas di dashboard), lalu ID internal bila sama. FAQ adalah daftar kurasi menurut kepentingan, dan dashboard sudah menjanjikan "tampil di beranda sesuai urutan di bawah". **Menggantikan GC18-07** (urut menurut teks pertanyaan) dari Phase 2.19.

## D-004 · 2026-10-13 · Nilai teknis FAQ publik

| Hal | Nilai |
|---|---|
| ID | ULID (string, tidak berurutan, cocok dengan `Id = string` di dashboard) |
| Panjang teks | pertanyaan maks. 200, jawaban maks. 800 karakter (sama dengan formulir dashboard) |
| Batas koleksi | 100 entri; lebih dari itu → 503, bukan daftar terpotong |
| Pembatasan laju | 60 permintaan per menit per IP |
| Query parameter | ditolak dengan 422 (koleksi lengkap tanpa paginasi) |
| Cache | `Cache-Control: no-store` untuk semua respons API |

Batas koleksi dan laju bisa diubah lewat `PINTU_PUBLIC_FAQ_MAX_ITEMS` dan `PINTU_PUBLIC_API_REQUESTS_PER_MINUTE`.

## D-005 · 2026-10-13 · Database pengujian

`composer test` memakai SQLite di memori. `composer test:mysql` menjalankan tes yang sama di database MySQL lokal `DB_TEST_DATABASE` (saat ini `pintu`) dan **mereset isinya**. Selama `pintu` juga dipakai sebagai database pengembangan, datanya bisa hilang setiap kali tes MySQL dijalankan. Buat database terpisah (mis. `pintu_testing`) begitu ada data lokal yang perlu dipertahankan.

## D-006 · 2026-10-13 · Laravel Cloud

Target hosting produksi adalah Laravel Cloud dengan root directory = root repository. Sebelum go-live: aktifkan rate limiting di edge Laravel Cloud, karena di Cloud Laravel memercayai semua proxy sehingga IP pengunjung untuk pembatasan laju di aplikasi bisa dipalsukan. Isi `CORS_ALLOWED_ORIGINS` hanya bila ada origin lain yang perlu membaca API.

## D-007 · 2026-10-13 · Login pegawai dengan Laravel Sanctum

Login diperiksa server dengan sesi cookie Laravel Sanctum (mode SPA, satu origin dengan portal dan dashboard). Tidak ada token di peramban; akun statis prototipe di `public/assets/js/main.js` dihapus.

| Hal | Keputusan |
|---|---|
| Alur | `GET /sanctum/csrf-cookie` → `POST /api/masuk` (`nip`, `sandi`, `ingat`) → `GET /api/sesi` → `POST /api/keluar` |
| Asal permintaan | Sesi hanya berlaku untuk permintaan dari origin PINTU sendiri (dikenali dari `Referer`/`Origin`; halaman mengirim `referrerPolicy: "same-origin"`). Login dari tempat lain ditolak 400. Token bearer diabaikan; tabel `personal_access_tokens` tidak dibuat. |
| Akun | NIP 18 digit unik; peran `administrator` (Administrator) atau `pegawai` (Pegawai UPTD); status `pending`, `active`, `suspended`, `deactivated`. Peran dan status tidak bisa diisi massal. |
| Akses | Hanya akun `active` yang bisa masuk; akun yang dinonaktifkan langsung keluar pada permintaan berikutnya, termasuk dashboard dan cookie "Ingat perangkat ini" (token ingatnya diganti) |
| Sesi | Berakhir setelah 8 jam tanpa aktivitas atau saat peramban ditutup; "Ingat perangkat ini" = 7 hari. ID sesi diganti saat masuk; sesi dihancurkan saat keluar. Halaman portal publik tidak memulai sesi dan tidak memasang cookie. |
| Percobaan gagal | Setiap percobaan dihitung sebelum sandi diperiksa (dihapus setelah berhasil masuk): maks. 5 per NIP+IP per menit dan 20 per NIP per 15 menit, ditambah 30 permintaan login per IP per menit. NIP tak dikenal dan sandi salah mendapat jawaban (dan waktu respons) yang sama; status akun baru disebut setelah sandi benar. |
| Kata sandi | Minimal 12 karakter berisi huruf dan angka. Spasi di awal dan akhir tidak dipangkas. `sandi` dan `sandi_ulang` tidak pernah disimpan di sesi sebagai isian lama saat validasi gagal. |
| Jejak | Log aplikasi `auth.login`, `auth.logout`, `auth.failed`, `auth.lockout`, `auth.inactive`, `auth.activated`, tanpa kata sandi |
| Administrator pertama | `php artisan pintu:create-admin`; tidak ada akun bawaan di produksi. Di terminal, kata sandi diketik dan akun langsung aktif. Tanpa mode interaktif (tab *Commands* Laravel Cloud) tidak ada kata sandi yang dicetak: akun `pending` mendapat tautan aktivasi bertanda tangan, berlaku 60 menit, sekali pakai (kuncinya terikat pada hash kata sandi), yang bisa diganti dengan `php artisan pintu:activation-link`. `php artisan db:seed` hanya membuat akun admin lokal untuk pengembangan. |

Batasan yang diketahui: cookie "Ingat perangkat ini" kedaluwarsa di peramban setelah 7 hari, tetapi server menerimanya sampai token ingatnya diganti (saat keluar atau saat akun dinonaktifkan). Data dashboard yang tersimpan di IndexedDB peramban tidak dihapus saat keluar.

Belum dibuat: manajemen akun dari dashboard, penugasan modul untuk Pegawai UPTD, ganti dan reset kata sandi, MFA.

## D-008 · 2026-10-13 · Manajemen akun oleh Administrator

Administrator mengelola akun pegawai dari halaman *Akun Pegawai* di dashboard. Ini melengkapi D-007: manajemen akun dari dashboard dan reset kata sandi oleh Administrator kini tersedia.

| Hal | Keputusan |
|---|---|
| Akses | Hanya Administrator yang aktif. Pegawai UPTD tidak melihat menunya dan server menolaknya dengan `403`. |
| Akun baru | NIP 18 digit, nama, surel (opsional), dan peran. Akun dibuat `pending` tanpa kata sandi yang bisa dipakai. Administrator mendapat tautan aktivasi (60 menit, sekali pakai) untuk dikirim kepada pemiliknya, yang memilih kata sandinya sendiri. Tautan hanya ditampilkan sekali. |
| Ubah data | Nama, surel, dan peran. NIP tidak bisa diubah. |
| Status | *Tangguhkan* untuk sementara (bisa diaktifkan kembali dengan kata sandi lama) atau *nonaktifkan* bila pegawai pindah atau pensiun (hanya bisa dipakai lagi lewat tautan aktivasi baru). Akun yang ditangguhkan atau dinonaktifkan kehilangan perangkat yang diingat dan keluar pada permintaan berikutnya. Akun tidak pernah dihapus, agar jejaknya tetap ada. |
| Atur ulang kata sandi | Menerbitkan tautan aktivasi baru. Kata sandi lama, sesi, perangkat yang diingat, dan tautan sebelumnya langsung tidak berlaku. |
| Akun sendiri | Administrator tidak bisa mengubah peran atau status akunnya sendiri dan tidak bisa mengatur ulangnya, sehingga selalu ada minimal satu Administrator aktif. |
| Pemulihan | Bila Administrator satu-satunya lupa kata sandi: `php artisan pintu:activation-link <nip> --reset` di server (tab *Commands* Laravel Cloud). |
| Jejak | Log aplikasi `account.created`, `account.updated`, `account.status`, dan `account.activation-link` dengan ID Administrator yang melakukannya. |

Model kepercayaan: Administrator yang memegang tautan aktivasi bisa saja memakainya sendiri. Karena itu tautan dikirim hanya kepada pemilik akun lewat kanal pribadi, dan setiap penerbitan tautan tercatat di log.

Belum dibuat: penugasan modul untuk Pegawai UPTD, ganti kata sandi sendiri dari dashboard, pengiriman tautan lewat surel, waktu login terakhir di daftar akun, MFA.

## D-009 · 2026-10-13 · Halaman login setinggi layar

Halaman Login Pegawai, dan halaman aktivasi yang memakai tata letak yang sama, selalu tepat setinggi layar dan tidak pernah bisa digulir di ukuran layar berapa pun. Di layar pendek jaraknya dirapatkan, lalu bagian pelengkap disembunyikan bertahap: daftar peran di kolom kiri, catatan keamanan, tombol SSO (belum tersedia), lalu salam dan subjudul kartu. Di layar sempit kolom kiri menjadi bilah lambang; di ponsel mendatar isian NIP dan kata sandi bersebelahan. Bila kartu tetap tidak muat, misalnya pesan galat panjang di layar yang sangat pendek, kartu dikecilkan secukupnya.

## D-010 · 2026-10-13 · Penugasan modul untuk Pegawai UPTD

Administrator menentukan modul dashboard yang dikelola tiap Pegawai UPTD, di panel akun pada halaman *Akun Pegawai*. Ini melengkapi D-007: penugasan modul untuk Pegawai UPTD kini tersedia.

| Hal | Keputusan |
|---|---|
| Modul | 18 modul, satu per menu konten: Pesan & Pengaduan, Statistik Layanan, Berita & Pengumuman, Dokumentasi Kegiatan, Regulasi & Unduhan, Tanya Jawab, Persyaratan Layanan, Jam Pelayanan, Samsat Keliling, Papan Layanan, Tarif & Simulasi, Tentang UPTD, Visi & Misi, Struktur Organisasi, Kontak & Kanal, Beranda, Halaman Statis, dan Jadwal Pemutakhiran. Kuncinya ada di [docs/api.md](api.md#modul-dashboard). |
| Selalu tersedia | Ringkasan, Absensi Lapangan, Kalkulator PKB, Pengaturan, dan Panduan untuk semua pegawai. Alur Publikasi tampil bila pegawai mengelola Berita & Pengumuman, Dokumentasi Kegiatan, Regulasi & Unduhan, atau Persyaratan Layanan, dan hanya berisi konten dari modulnya. |
| Administrator | Selalu mengelola semua modul tanpa perlu ditugaskan. |
| Akun baru | Tanpa modul sampai Administrator mencentangnya; modul bisa dipilih sejak akun dibuat. |
| Dashboard | Menu samping, pencarian, menu "Buat konten", Ringkasan (kartu, antrean kerja, grafik pesan), dan Alur Publikasi hanya memuat modul pegawai. Membuka alamat modul lain menampilkan pesan bahwa modul itu belum ditugaskan. Pengelola Jadwal Pemutakhiran memantau jadwal semua modul; pegawai lain hanya jadwal modulnya. |
| Penegakan | Server menyimpan penugasan dan mengirimkannya lewat `/api/sesi`. Selama konten dashboard masih tersimpan di peramban (IndexedDB), pembatasan ini baru mengatur tampilan. Endpoint konten di server yang kelak dibuat wajib menolak pegawai yang tidak mengelola modulnya. |
| Jejak | Perubahan modul tercatat di log `account.created` dan `account.updated`. |

Perubahan modul berlaku saat dashboard pegawai dimuat ulang.

Belum dibuat: peran alur per modul (penyedia, pemeriksa, penyetuju, pengelola publikasi) di server, dan pembatasan *Pengaturan › Situs* serta *Pengaturan › Data* untuk Administrator.
