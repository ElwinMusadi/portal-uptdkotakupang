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

## D-011 · 2026-10-13 · Tanya Jawab tersimpan di server

Konten dashboard pindah ke server satu modul demi satu, dimulai dari Tanya Jawab. Ini melanjutkan D-010: untuk Tanya Jawab, penugasan modul kini ditegakkan server.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Mode campuran: Tanya Jawab dibaca dan ditulis lewat API PINTU (`/api/koleksi/faq`, mengikuti kontrak REST dashboard). Modul lain, pengaturan, dan unggahan tetap di IndexedDB peramban sampai gilirannya. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Tanya Jawab boleh melihat, menambah, menyunting, dan mengubah urutan. Pegawai lain ditolak `403`. |
| Hapus | Hanya Administrator. Pegawai menyembunyikan entri dengan sakelar *Tampilkan di portal* atau menariknya dari portal. |
| Isian | Pertanyaan (maks. 200 karakter) dan jawaban (maks. 800) wajib, tanpa karakter kontrol selain tab dan baris baru, dan harus berisi teks yang terlihat; syaratnya sama dengan syarat tampil di API publik (GC18-04). Status (*Draf*, *Terbit*, *Ditarik*), *Untuk warga*, dan *Tampilkan di portal* selalu dikirim eksplisit. Entri baru di formulir mulai sebagai draf yang belum untuk warga. |
| Portal | API publik hanya memberikan entri yang terbit, untuk warga, dan ditampilkan (GC18-01–03); dashboard menandainya *Di portal*. Beranda portal belum membaca API ini. |
| Urutan | Entri baru ditaruh di akhir daftar. Mengubah urutan tidak dihitung sebagai suntingan, jadi waktu *diperbarui* tetap. |
| Data contoh | Tanya jawab contoh di IndexedDB tidak dipindahkan ke server, karena setiap teks perlu diperiksa sebelum terbit (GC18-05). |
| Jejak | Setiap perubahan tercatat di log aktivitas server (tabel `activities`: siapa, apa, kapan), dalam transaksi yang sama dengan perubahannya. Ringkasan menggabungkannya dengan log aktivitas peramban. Pegawai hanya melihat aktivitas modulnya. Log server tidak bisa dikosongkan dari dashboard. |
| Cadangan | *Unduh cadangan* menyertakan Tanya Jawab dari server. *Pulihkan dari cadangan*, *Kosongkan log aktivitas*, dan *Kembalikan ke data awal* hanya mengubah data di peramban. |

Belum dibuat: beranda portal membaca `GET /api/public/faq`, modul lain di server, dan impor Tanya Jawab dari berkas cadangan ke server.

## D-012 · 2026-10-13 · Beranda menampilkan Tanya Jawab dari API publik

Bagian *Tanya jawab* di beranda kini menampilkan tanya jawab yang dikelola di dashboard. Ini melengkapi D-011: beranda portal membaca `GET /api/public/faq`.

| Hal | Keputusan |
|---|---|
| Sumber | `public/assets/js/main.js` memuat `GET /api/public/faq` saat beranda dibuka. Daftar statis lama dihapus dari halaman, jadi yang tampil hanya entri yang terbit, untuk warga, dan ditampilkan, dalam urutan dashboard. Halaman portal tetap berkas statis yang disajikan apa adanya. |
| Tampilan | Akordeon yang sama dengan sebelumnya. Pertanyaan dan jawaban dipasang sebagai teks polos: baris baru di jawaban dipertahankan, markup tampil sebagai teks, dan URL tidak menjadi tautan (GC18-06). |
| Kosong | Bila tidak ada entri yang *Di portal*, bagian Tanya Jawab disembunyikan. |
| Gagal | Bila API gagal atau tidak menjawab dalam 10 detik, bagian itu menampilkan "Tanya jawab belum dapat dimuat." dengan tombol *Coba lagi*. Tidak ada daftar cadangan, agar entri yang sudah ditarik tidak muncul kembali. |
| Tanpa JavaScript | Bagian itu meminta pengunjung mengaktifkan JavaScript. |

Batasan yang diketahui: setiap kunjungan beranda memakai satu permintaan dari batas laju API publik (D-004: 60 per menit per IP). Pengunjung yang berbagi satu IP, misalnya lewat CGNAT operator seluler atau jaringan kantor, bisa melihat pesan gagal bila kunjungan dari IP itu melebihi batas; naikkan `PINTU_PUBLIC_API_REQUESTS_PER_MINUTE` bila perlu. Tautan di jawaban lama (mis. ke halaman persyaratan) tidak bisa dibuat ulang selama API hanya memberikan teks polos.

## D-013 · 2026-10-13 · Samsat Keliling tersimpan di server; batas laju API publik 300

Samsat Keliling menjadi modul kedua yang tersimpan di server, dengan pola yang sama seperti Tanya Jawab (D-011). Beranda dan halaman Jadwal portal kini menampilkan jadwal dari dashboard.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Jadwal dibaca dan ditulis lewat `/api/koleksi/keliling`. Satu entri adalah satu lokasi pada satu hari (Senin–Sabtu) dengan jam mulai dan selesai (WITA), kecamatan, layanan, catatan, dan sakelar *Tampilkan di portal* (`aktif`). |
| Akses | Administrator dan Pegawai UPTD yang mengelola Samsat Keliling boleh menambah, menyunting, dan **menghapus** jadwal; jadwal adalah data operasional, bukan konten editorial seperti Tanya Jawab. Pengelola Jadwal Pemutakhiran boleh melihat daftarnya (hanya baca) untuk memantau waktu pembaruannya. Pegawai lain ditolak `403`. |
| Isian | Jam selesai harus setelah jam mulai, juga saat hanya salah satunya diubah. Lokasi (maks. 120 karakter), kecamatan (60), layanan (80), dan catatan (300, boleh kosong) berupa teks polos tanpa karakter kontrol. |
| Portal | `GET /api/public/keliling` memberikan jadwal aktif saja, urut hari lalu jam, tanpa ID. Beranda menampilkan jadwal hari ini; halaman Jadwal menampilkan Senin–Jumat minggu ini, ditambah Sabtu bila ada jadwalnya, dan beralih ke minggu depan setelah hari terakhir lewat. Teks dipasang sebagai teks polos. Bila API gagal, tampil pesan dan tombol *Coba lagi*; rotasi contoh di `main.js` dihapus. |
| Data contoh | Jadwal contoh di peramban dan di `main.js` tidak dipindahkan ke server, karena lokasinya belum diverifikasi petugas. |
| Jejak | Penambahan, perubahan, dan penghapusan tercatat di log aktivitas server, misalnya "menambahkan Senin · Kantor Kelurahan Oebobo". |
| Batas laju | Bawaan `PINTU_PUBLIC_API_REQUESTS_PER_MINUTE` naik dari 60 menjadi **300** permintaan per menit per IP, **menggantikan nilai di D-004**. Batas ini dipakai bersama semua API publik; satu kunjungan beranda kini memakai dua permintaan (Tanya Jawab dan Samsat Keliling), dan pengunjung yang berbagi satu IP (CGNAT seluler, jaringan kantor) perlu ruang lebih. |

Belum dibuat: penyesuaian jadwal (libur, jam khusus) di server dan modul lain di server.
