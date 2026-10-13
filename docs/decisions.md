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

## D-014 · 2026-10-13 · Jam Pelayanan tersimpan di server

Jam Pelayanan menjadi modul ketiga yang tersimpan di server (D-011, D-013) dan pengaturan pertama yang tidak lagi tersimpan di peramban. Status loket, hitung mundur, dan daftar jam di portal kini mengikuti dashboard.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Jam loket per hari (Senin–Minggu, Minggu mewakili hari libur), batas penerimaan berkas, catatan untuk warga, dan pemberitahuan perubahan disimpan sebagai satu dokumen pengaturan (`/api/pengaturan/jam`, tabel `settings`). Penyesuaian jadwal (loket tutup atau jam khusus pada satu tanggal, satu penyesuaian per tanggal) disimpan sebagai koleksi `/api/koleksi/penyesuaian`. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Jam Pelayanan boleh menyunting jam, serta menambah, menyunting, dan menghapus penyesuaian. Pegawai lain ditolak `403`. Semua pegawai boleh melihat kapan pengaturan server terakhir disimpan (`GET /api/pengaturan`), untuk Jadwal Pemutakhiran. |
| Belum disimpan | Sebelum jam loket pertama kali disimpan, dashboard menampilkan semua hari tutup dan portal menyatakan "Jam loket belum tersedia". Jam contoh di peramban dan di `main.js` tidak dipindahkan, karena belum diverifikasi petugas. |
| Portal | `GET /api/public/jam` memberikan jam per hari, batas berkas, catatan, pemberitahuan, dan penyesuaian mulai hari ini (WITA). Status buka/tutup dan hitung mundur memperhitungkan penyesuaian, termasuk saat mencari jam buka berikutnya (hingga dua pekan ke depan). Tabel jam di halaman Jadwal, ringkasan di halaman Kontak, dan footer semua halaman diisi dari API; halaman Jadwal juga mendaftar penyesuaian mendatang. Bila API gagal, portal menyatakannya dan tidak menebak jam. |
| Jejak | Penyimpanan jam ("memperbarui Jam Pelayanan", hanya bila ada perubahan) dan setiap penyesuaian ("menambahkan 2026-12-25 · Hari Raya Natal") tercatat di log aktivitas server. |

Satu kunjungan beranda kini memakai tiga permintaan dari batas laju API publik (D-013).

Belum dibuat: modul lain di server, dan pengumuman otomatis di portal saat penyesuaian ditambahkan.

## D-015 · 2026-10-13 · Papan Layanan tersimpan di server

Papan Layanan menjadi modul keempat yang tersimpan di server (D-011, D-013, D-014). Papan layanan di beranda kini menampilkan isi dari dashboard, bukan data contoh.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Nomor antrean yang dilayani, loket dan layanannya, perkiraan waktu tunggu, selisihnya dari kemarin, status tiap loket (Buka, Istirahat, Tutup; maks. 12 loket), dan catatan disimpan sebagai dokumen pengaturan `papan` (`/api/pengaturan/papan`). |
| Waktu pembaruan | Server mengisi waktu *diperbarui* setiap kali isi papan berubah; waktu dari peramban diabaikan. Menyimpan isi yang sama tidak mengubah waktunya dan tidak dicatat. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Papan Layanan. Pegawai lain ditolak `403`; Ringkasan mereka memakai waktu simpan papan saja. |
| Portal | `GET /api/public/papan` memberikan isi papan tanpa ID loket. Beranda menampilkan nomor antrean, waktu tunggu, selisih, dan status loket **hanya pada hari yang sama dengan pembaruannya** (WITA); papan yang belum diperbarui hari ini menampilkan "Belum diperbarui hari ini" dan waktu pembaruan terakhirnya, agar antrean kemarin tidak tampil sebagai antrean sekarang. Sebelum pertama kali disimpan, papan menyatakan belum tersedia. Label "Pratinjau · data contoh" diganti waktu pembaruan. |
| Data contoh | Isi papan contoh di peramban tidak dipindahkan ke server. |

Satu kunjungan beranda kini memakai empat permintaan dari batas laju API publik (D-013).

Belum dibuat: kotak *Pengumuman* di papan beranda masih contoh statis; isinya kelak dari Berita & Pengumuman.

## D-016 · 2026-10-13 · Pesan & Pengaduan tersimpan di server

Formulir Kontak di portal kini benar-benar mengirim pesan ke server, dan pesan dikelola dari halaman *Pesan & Pengaduan* di dashboard. Sebelumnya formulir itu hanya demo: tidak ada yang terkirim dan nomor tiketnya acak di peramban.

| Hal | Keputusan |
|---|---|
| Formulir portal | `POST /api/public/pesan`: jenis, nama (opsional), email atau nomor WhatsApp (**wajib**, agar bisa dibalas), layanan, isi (10–2.000 karakter), dan persetujuan penggunaan data. Pengirim mendapat nomor tiket dari server, mis. `PINTU-7KQ2MX`. |
| Anti-spam | Isian tersembunyi (*honeypot*) yang hanya diisi bot: kiriman seperti itu dijawab seolah berhasil tetapi tidak disimpan. Ditambah maks. **5 pesan per jam per IP**. Tanpa CAPTCHA, layanan pihak ketiga, atau cookie. |
| Dashboard | Administrator dan pengelola Pesan & Pengaduan membaca pesan, mencatat pesan dari kanal lain (kotak saran, SP4N-LAPOR!, WhatsApp, telepon), mengubah status (Baru, Diproses, Selesai), dan menambah tanggapan. Pegawai lain ditolak `403`. |
| Keutuhan | Isi dan identitas pengirim tidak bisa diubah setelah diterima. Tanggapan hanya bisa ditambah, tidak diubah atau dihapus, dan dicatat atas nama akun yang menulisnya. Pegawai yang pertama menangani pesan tercatat sebagai penanganannya. |
| Hapus | **Hanya Administrator.** Pegawai menutup pesan dengan menandainya selesai. Tanggapan ikut terhapus. |
| Masa simpan | **Tidak dihapus otomatis**, sesuai kebijakan privasi portal (mengikuti ketentuan kearsipan). Masa simpan pasti diputuskan kemudian. |
| Data pribadi | Log aplikasi dan log aktivitas hanya mencatat nomor tiket, tidak pernah nama, kontak, atau isi pesan. |
| Data contoh | Pesan contoh di peramban tidak dipindahkan ke server. |

Belum dibuat: pemberitahuan surel atau WhatsApp kepada petugas saat pesan masuk, pengecekan status tiket oleh warga, dan masa simpan otomatis.

## D-017 · 2026-10-13 · Tarif & Simulasi tersimpan di server

Tarif & Simulasi kini tersimpan di server. Simulasi PKB di beranda dan halaman Layanan menghitung dengan tarif dari dashboard, bukan lagi nilai contoh yang tertanam di kode portal.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Opsen PKB (0–100%), denda keterlambatan (0–10% per bulan), maksimal bulan denda (0–60), SWDKLLJ sepeda motor dan mobil (Rp0–Rp10.000.000), nilai awal simulasi (pokok PKB, jenis kendaraan, bulan terlambat), dan catatan di bawah simulasi (maks. 300 karakter) disimpan sebagai dokumen pengaturan `tarif` (`/api/pengaturan/tarif`). |
| Akses | Menyimpan: Administrator dan Pegawai UPTD yang mengelola Tarif & Simulasi; pegawai lain ditolak `403`. Membaca: **semua pegawai yang login**, karena Kalkulator PKB terbuka untuk semua pegawai (D-010) dan tarif yang sama memang diumumkan di portal. |
| Sebelum disimpan | Semua tarif bernilai nol. Portal menyatakan simulasi belum tersedia dan Kalkulator PKB menampilkan peringatan. Tidak ada tarif contoh yang dipakai sebagai cadangan. |
| Portal | `GET /api/public/tarif` memberikan tarif dan waktu penyimpanannya. Simulasi di beranda dan halaman Layanan, label tile (opsen, denda, maks. bulan), dan tabel asumsi di halaman Layanan diisi dari sana. Catatan dari dashboard menggantikan keterangan di bawah simulasi; bila kosong, keterangan bawaan tetap tampil. Catatan "Asumsi contoh. Wajib diverifikasi petugas sebelum terbit." diganti tanggal pembaruan tarif. Selama dimuat, sebelum tarif disimpan, atau bila API gagal, simulasi dinonaktifkan dan keterangannya tampil di bawah kanvas, dengan tombol coba lagi saat gagal. |
| Simulasi | Mengganti jenis kendaraan tidak lagi mengganti pokok PKB ke nilai contoh mobil (Rp2.450.000) yang tertanam di kode. Bulan terlambat dibatasi maksimal bulan denda. |
| Data contoh | Tarif contoh di peramban tidak dipindahkan ke server. |

Satu kunjungan beranda kini memakai lima permintaan dari batas laju API publik (D-013).

Belum dibuat: tanggal mulai berlaku tarif dan riwayat nilai tarif sebelumnya (log aktivitas hanya mencatat "memperbarui Tarif & Simulasi").

## D-018 · 2026-10-13 · Statistik Layanan tersimpan di server; hanya angka terverifikasi yang terbit

Statistik Layanan kini tersimpan di server. Dasbor transparansi di beranda menampilkan angka dari dashboard, dan hanya bila statusnya Terverifikasi pimpinan.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Dokumen pengaturan `statistik` (`/api/pengaturan/statistik`) memuat status (Ilustrasi atau Terverifikasi), tahun data, dan catatan (maks. 300 karakter). Isinya empat kategori tetap: Transaksi pelayanan, Penerimaan pajak, Samsat Keliling, dan Kepuasan masyarakat. Tiap kategori punya nama tab, subjudul, judul dan satuan grafik, **empat angka utama** (label, nilai, satuan, perubahan dalam persen, dan apakah kenaikan bermakna baik), serta **12 nilai bulanan Januari–Desember**. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Statistik Layanan; pegawai lain ditolak `403`. |
| Terbit | **Hanya statistik berstatus Terverifikasi yang tampil di portal** (keputusan pemilik). Selama berstatus Ilustrasi atau belum pernah disimpan, `GET /api/public/statistik` tidak memberikan angka apa pun, dan beranda menyatakan "Statistik layanan belum diterbitkan." Label "Data ilustrasi untuk keperluan desain." dihapus. |
| Rentang waktu | Tombol *Tahun ini*, *12 bln*, *3 bln*, *30 hari*, dan *Hari ini*, serta garis tren di tiap angka utama, **dihapus** (keputusan pemilik). Angkanya selama ini dikarang dari data contoh (dikalikan faktor tetap dan diberi gelombang buatan), padahal data dashboard hanya berisi angka utama dan nilai bulanan. |
| Grafik | Dua belas batang berlabel Januari–Desember tahun data. Bulan berjalan disorot bila tahun datanya tahun ini (WITA). Sebelumnya batang diberi label 12 bulan terakhir walaupun datanya Januari–Desember. |
| Catatan | Di bawah dasbor tampil tahun data, keterangan terverifikasi pimpinan, tanggal pembaruan, dan catatan dari dashboard. |
| Data contoh | Angka contoh di peramban tidak dipindahkan ke server. Sebelum statistik pertama kali disimpan, keempat kategori dan label angka utamanya sudah tersedia, dengan semua angka nol dan status Ilustrasi. |

Satu kunjungan beranda kini memakai enam permintaan dari batas laju API publik (D-013).

Belum dibuat: data per rentang waktu, data tahun-tahun sebelumnya, dan perhitungan otomatis dari transaksi.

## D-019 · 2026-10-13 · Kontak & Kanal tersimpan di server

Kontak & Kanal kini tersimpan di server. Footer semua halaman portal dan halaman Kontak & Pengaduan menampilkan alamat, nomor kontak, dan kanal pengaduan dari dashboard, bukan alamat dan nomor contoh.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Dokumen pengaturan `kontak` (`/api/pengaturan/kontak`) memuat nama kantor, alamat, kota, provinsi, telepon, WhatsApp dan catatannya, surel, media sosial (maks. 8 akun), tautan Google Maps, catatan lokasi, janji waktu balasan, dan kanal pengaduan resmi (maks. 8). |
| Akses | Administrator dan Pegawai UPTD yang mengelola Kontak & Kanal; pegawai lain ditolak `403`. |
| Tautan dan nomor | Server hanya menerima alamat `http(s)://`, `mailto:`, `tel:`, jangkar `#…`, atau halaman portal (mis. `jadwal.html`, `kontak.html#masukan`). Skema lain seperti `javascript:` ditolak server dan juga diabaikan portal. Telepon minimal 6 angka dan WhatsApp minimal 9 angka. Portal membuat tautan `tel:` dan `wa.me` dengan kode negara +62 dari nomor yang diawali 0. |
| Portal | `GET /api/public/kontak` (tanpa ID butir) mengisi footer di semua halaman (alamat, telepon, surel). Di halaman Kontak, API ini juga mengisi daftar kontak, tombol Google Maps, catatan lokasi, janji waktu balasan, dan kanal pengaduan. Baris yang tidak diisi disembunyikan. Catatan "Alamat dan nomor kontak diisi setelah verifikasi." dan label "contoh akun" dihapus. Sebelum disimpan, portal menyatakan alamat, nomor kontak, dan kanal pengaduan belum tersedia. Bila API gagal, portal menampilkan keterangannya dengan tombol coba lagi. |
| Data contoh | Kontak contoh di peramban tidak dipindahkan ke server. Kanal pengaduan (mis. SP4N-LAPOR!, kotak saran, Ombudsman) diisi petugas di dashboard. |

Setiap kunjungan halaman portal kini memakai satu permintaan tambahan dari batas laju API publik (D-013); beranda memakai tujuh.

Belum dibuat: ringkasan "3 kanal" di halaman Layanan dan peta kanal di beranda masih teks statis.

## D-020 · 2026-10-13 · Visi & Misi tersimpan di server; unggahan maklumat ditunda

Visi & Misi kini tersimpan di server, dan halaman Visi & Misi di portal membaca isinya dari API.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Dokumen pengaturan `visiMisi` (`/api/pengaturan/visiMisi`) memuat sumber dan rumusan visi, misi (maks. 10, dengan kata kunci), kontribusi PINTU (maks. 6), nilai dasar ASN (maks. 10, huruf satu atau dua karakter), serta maklumat pelayanan (isi dan penandatangan). |
| Isi awal | **Teks resmi yang sudah tampil di portal menjadi isi awal** (keputusan pemilik): visi Pemerintah Provinsi NTT, lima misi, dua kontribusi, tujuh nilai BerAKHLAK, dan maklumat. Halaman tidak pernah kosong; petugas cukup mengubah bila ada perubahan. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Visi & Misi; pegawai lain ditolak `403`. |
| Berkas maklumat | **Unggahan PDF ditunda** (keputusan pemilik). Server menolak berkas pada maklumat, dan dashboard menampilkan keterangan sebagai pengganti isian unggahan. Tombol "Unduh maklumat (PDF)" di portal tetap mengarah ke halaman Regulasi & Unduhan. Unggahan dirancang bersama Berita dan Regulasi & Unduhan, termasuk tempat penyimpanan berkas di Laravel Cloud. |
| Portal | `GET /api/public/visi-misi` (tanpa ID butir dan tanpa berkas) mengisi visi, misi, kontribusi, nilai, dan maklumat. Bagian tampil setelah isinya dimuat; bagian tanpa butir disembunyikan. Jumlah pada judul ("Lima misi.", "mendukung dua misi provinsi", angka nilai inti) mengikuti isinya. Bagian akhir visi dan kalimat terakhir maklumat tetap bernada redup seperti desain sebelumnya. Bila API gagal, keterangan dan tombol coba lagi tampil di bawah visi. |

Belum dibuat: unggahan berkas maklumat ke server.

## D-021 · 2026-10-14 · Tentang UPTD tersimpan di server; unggahan foto ditunda

Tentang UPTD kini tersimpan di server, dan halaman Profil di portal membaca isinya dari API. Keputusan pemilik untuk Visi & Misi (D-020) diterapkan dengan cara yang sama.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Dokumen pengaturan `profil` (`/api/pengaturan/profil`) memuat judul pembuka dan lanjutannya, pengantar, keterangan foto gedung, kedudukan (judul, lanjutan, deskripsi), tabel identitas (maks. 12 baris), tugas dan fungsi (maks. 12, dengan kata kunci), serta sambutan pimpinan (kutipan, nama, jabatan). |
| Isi awal | Teks yang sudah tampil di halaman Profil menjadi isi awal, seperti Visi & Misi. Nama "[Nama Kepala UPTD]" hanya tempat isian, jadi isi awalnya kosong. Selama nama kosong, portal menampilkan jabatan saja. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Tentang UPTD; pegawai lain ditolak `403`. |
| Foto | **Unggahan foto gedung dan foto Kepala UPTD ditunda**, sama seperti berkas maklumat (D-020). Server menolak foto, dan dashboard menampilkan keterangan sebagai pengganti isian unggahan. Portal tetap menampilkan ilustrasi. |
| Portal | `GET /api/public/profil` (tanpa ID butir dan tanpa foto) mengisi judul, pengantar, keterangan foto, kedudukan, tabel identitas, tugas dan fungsi, serta sambutan. Baris "Dasar hukum" yang menautkan ke Regulasi & Unduhan tetap di akhir tabel identitas. Bagian tampil setelah isinya dimuat; tugas tanpa butir disembunyikan. Lanjutan judul dan kalimat terakhir sambutan tetap bernada redup. Bila API gagal, keterangan dan tombol coba lagi tampil di bawah judul. |

Belum dibuat: unggahan foto ke server. Keterangan "Senin–Sabtu · jam layanan di halaman Jadwal" di bawah foto gedung masih teks statis.

## D-022 · 2026-10-14 · Persyaratan Layanan tersimpan di server dengan alur persetujuan

Persyaratan Layanan kini tersimpan di server. Ini koleksi pertama yang memakai alur publikasi di server: Draf → Diperiksa → Menunggu persetujuan → Terbit, dengan Dikembalikan dan Arsip. Halaman Layanan dan bagian *Layanan utama* di beranda membaca layanan yang terbit dari API.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Tabel `services` menyimpan nama, slug (juga jangkar di halaman Layanan), ringkasan, loket, jenis (`layanan` atau `tambahan`), cek fisik, non-tunai, dapat diwakilkan, label (maks. 6), persyaratan (1–20 berkas dengan jumlah), catatan, tanda unggulan (tab beranda), urutan, serta kolom alur: status, unit penyedia, pemeriksa, catatan revisi, waktu terbit, dan riwayat langkah. |
| Alur dan akses | **Administrator yang menerbitkan** (keputusan pemilik). Pengelola Persyaratan Layanan menyiapkan draf, mengajukan, memeriksa, meneruskan ke penyetuju, mengembalikan dari tahap pemeriksaan (dengan catatan), mengajukan ulang, dan memulihkan arsip ke draf. Hanya Administrator yang boleh mengubah layanan yang menunggu persetujuan atau sudah terbit: isinya, menyetujui-dan-menerbitkan, mengembalikan dari tahap persetujuan, dan menarik dari portal. Mengubah urutan dan menghapus juga khusus Administrator. Server hanya menerima langkah yang ada di alur, dan mengembalikan wajib disertai catatan revisi. Riwayat dan waktu terbit ditulis server atas nama akun yang masuk; kiriman klien untuk keduanya diabaikan. |
| Pembaca lain | Pengelola Jadwal Pemutakhiran boleh membaca daftar layanan untuk melihat kapan terakhir diperbarui, seperti Samsat Keliling (D-013). |
| Data awal | **Enam entri yang sudah tampil di halaman Layanan dimasukkan lewat migrasi berstatus Terbit** (keputusan pemilik). Isinya lima layanan dan tambahan untuk badan hukum, dengan teks persis seperti di halaman (diverifikasi petugas, Oktober 2026), serta tanda cek fisik, non-tunai, diwakilkan, label, dan unggulan dari model data dashboard. Migrasi ini ikut berjalan saat deploy. |
| Portal | `GET /api/public/layanan` memberikan layanan terbit sesuai urutan, tanpa ID dan tanpa kolom alur selain waktu terbit. Halaman Layanan menampilkan kartu tiap layanan, navigasi samping, dan persyaratan tambahan yang dilipat di kartu terakhir. Catatan sumber mencantumkan bulan terbit terakhir. Beranda menampilkan layanan unggulan sebagai tab dengan daftar centang (**keputusan pemilik: beranda ikut**). Keterangan dalam kurung di akhir nama berkas ditampilkan redup. Bila API gagal, keterangan dan tombol coba lagi. |
| Perubahan tampilan | Tag mengikuti data: "Dapat diwakilkan" kini juga tampil di Perpanjangan STNK 5 tahun dan Duplikat STNK, dan "Kuasa: meterai Rp10.000" di Perpanjangan. Navigasi samping dan tab beranda memakai nama layanan lengkap. Keterangan di beranda memakai ringkasan layanan, sehingga kalimat khusus beranda dan tag "Ganti pemilik: lihat BBN" tidak ada lagi. |
| Dashboard | Langkah khusus Administrator tidak ditawarkan kepada pegawai lain. Penyunting layanan terkunci bagi mereka selama layanan menunggu persetujuan atau terbit. Ringkasan, antrean kerja, menu perintah, dan *Pengaturan › Data* hanya membaca layanan bila pengguna boleh membacanya. |

Kontrak alur ini (`WorkflowStatus`) disiapkan untuk dipakai juga oleh Berita, Dokumentasi, dan Regulasi & Unduhan saat pindah ke server.

Belum dibuat: peran terpisah per langkah (penyedia, pemeriksa, penyetuju, pengelola publikasi) dan versi draf untuk layanan yang sudah terbit. Saat ini layanan terbit diubah langsung oleh Administrator, atau ditarik ke arsip lalu dipulihkan ke draf.

## D-023 · 2026-10-14 · Teks Beranda tersimpan di server; teks HTML tetap sebagai isi awal

Teks beranda kini tersimpan di server: pembuka, nilai utama, judul *Layanan utama*, mitra, peta kanal, integritas, biaya, dan penutup.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Dokumen pengaturan `beranda` (`/api/pengaturan/beranda`) memuat pembuka (baris atas, judul dan lanjutannya, deskripsi, label dan tujuan tombol, catatan), nilai utama (maks. 6), judul dan deskripsi *Layanan utama*, integritas, biaya, dan penutup, mitra (maks. 12, singkatan maks. 3 huruf), peta kanal (maks. 8 kelompok, masing-masing maks. 8 kanal), serta catatan notis, yang wajib dibawa, dan jaminan biaya (maks. 6). Tujuan tombol harus berupa tautan portal yang sah (`PortalLink`, D-019). |
| Akses | Administrator dan Pegawai UPTD yang mengelola Beranda; pegawai lain ditolak `403`. |
| Tampilan | **Teks yang sudah tertulis di HTML beranda tetap di sana dan menjadi isi awal di server** (keputusan pemilik). Teks itu terbaca mesin pencari dan pembuka tidak pernah kosong. Skrip menggantinya dengan isi server hanya bila dokumen sudah pernah disimpan. Bila API gagal, teks HTML yang tampil, walau mungkin sudah tidak sama dengan isi server. Setelah teks diubah, pengunjung bisa sekilas melihat teks lama sebelum berganti. Tes portal memastikan setiap teks isi awal masih ada di HTML beranda. |
| Ikon | **Ikon butir nilai utama dan peta kanal mengikuti posisinya** (keputusan pemilik): butir ke-n memakai ikon yang tertulis di posisi itu dalam HTML, dan butir tambahan memakai ikon umum. Data dashboard tidak memuat ikon. |
| Portal | `GET /api/public/beranda` (tanpa ID butir) dibaca beranda. Bagian tanpa nilai utama atau mitra disembunyikan, tombol pembuka disembunyikan bila tujuannya kosong, dan catatan pembuka, catatan notis, serta yang wajib dibawa disembunyikan bila kosong. Kalimat pertama catatan notis ditebalkan. Teks dipasang sebagai teks polos. |

Beranda kini memakai delapan permintaan dari batas laju API publik (D-013).

Belum dibuat: isi kartu harga biaya (Rp0, cek fisik), garis peta kanal yang menyesuaikan jumlah kelompok, dan teks lain di beranda yang tidak ada di dokumen ini masih statis.

## D-024 · 2026-10-14 · Halaman Statis tersimpan di server; teks HTML tetap sebagai isi awal

Kebijakan Privasi dan Syarat & Ketentuan kini tersimpan di server, dan halaman portalnya membaca isi dari API. Cara isi awal Beranda (D-023) diterapkan dengan cara yang sama.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Koleksi `halaman` (`/api/koleksi/halaman`, tabel `static_pages`) memuat judul (maks. 80 karakter), lanjutan judul (maks. 80), teks kecil di atas judul (maks. 120), nama berkas (`slug`), dan isi kaya-teks (HTML, maks. 100.000 karakter). |
| Halaman tetap | **Hanya dua halaman portal yang ada, `kebijakan-privasi` dan `syarat-ketentuan`.** Setiap halaman adalah halaman portal, jadi halaman hanya disunting: tidak ditambah, diurutkan, atau dihapus, dan nama berkasnya tidak bisa diubah. Dashboard tidak lagi menawarkan *Tambah halaman* dan *Hapus halaman*. |
| Isi awal | Teks yang tertulis di HTML kedua halaman tetap di sana dan menjadi isi awal lewat migrasi, dalam bentuk yang ditulis editor dashboard (catatan draf menjadi paragraf miring). Portal menggantinya dengan isi server hanya bila halaman sudah pernah disimpan, seperti Beranda (D-023). Bila API gagal, teks HTML yang tampil. Tes memastikan teks isi awal sama dengan teks di HTML. |
| Akses | Administrator dan Pegawai UPTD yang mengelola Halaman Statis; pegawai lain ditolak `403`. Penyimpanan tercatat sebagai "memperbarui Kebijakan Privasi" atau "memperbarui Syarat & Ketentuan". |
| Isi kaya-teks | Server hanya menerima HTML seperti yang ditulis editor (`RichText`): paragraf, subjudul (`h2`, `h3`), daftar berbutir dan bernomor, kutipan, tebal, miring, garis bawah, coret, baris baru, garis pemisah, dan tautan. Atribut hanya boleh pada tautan (`href`, `target`, `rel`, `class`, `title`) dan daftar bernomor (`start`, `type`). Tujuan tautan harus berupa tautan portal yang sah (`PortalLink`, D-019). Skrip, gambar, gaya, atribut event, komentar, dan karakter kontrol ditolak dengan `422`, tidak dibuang diam-diam. |
| Portal | `GET /api/public/halaman/{slug}` (tanpa ID) dibaca halamannya. Portal tidak memasang HTML dari API secara langsung: isi diurai tanpa dijalankan (`DOMParser`), lalu dibangun ulang hanya dari elemen yang diizinkan, dengan teks sebagai teks dan tautan hanya dengan skema yang diizinkan. Teks kecil di atas judul disembunyikan bila kosong. |

Belum dibuat: halaman statis baru di portal, pratinjau di dashboard, dan tanggal *berlaku sejak* yang terisi otomatis.

## D-025 · 2026-10-14 · Jadwal Pemutakhiran tersimpan di server

Standar Jadwal Pemutakhiran kini tersimpan di server. Modul ini hanya untuk internal; portal tidak membacanya.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Koleksi `pemutakhiran` (`/api/koleksi/pemutakhiran`, tabel `update_standards`) memuat nama konten, modul yang dipantau, penanggung jawab, dan frekuensi (masing-masing maks. 120 karakter), serta batas hari (bilangan bulat 1–730). Modul yang bisa dipantau hanya delapan modul yang waktu pembaruannya bisa dihitung dashboard: Samsat Keliling, Dokumentasi Kegiatan, Jam Pelayanan, Persyaratan Layanan, Berita & Pengumuman, Regulasi & Unduhan, Papan Layanan, dan Tentang UPTD. |
| Isi awal | **Delapan standar yang tampil di dashboard dimasukkan sebagai data awal lewat migrasi** (keputusan pemilik). Standar itu berasal dari usulan frekuensi di [RANCANGAN-UIUX.md](RANCANGAN-UIUX.md) bagian 9.2 dan tetap bisa diubah di dashboard. |
| Akses baca | Sesuai D-010: pengelola Jadwal Pemutakhiran dan Administrator melihat semua standar; pegawai lain hanya melihat standar modul yang mereka kelola, untuk Ringkasan. Server yang menyaring daftarnya, jadi tidak ada pegawai yang ditolak saat membuka Ringkasan. |
| Akses ubah | Administrator dan pengelola Jadwal Pemutakhiran menambah, menyunting, mengurutkan, dan **menghapus** standar (keputusan pemilik): standar adalah data internal, bukan konten portal, seperti Samsat Keliling dan penyesuaian jadwal. Pegawai lain ditolak `403`, termasuk untuk standar modulnya sendiri. |

Waktu "terakhir diperbarui" tetap dihitung dashboard dari modul yang dipantau. Untuk modul yang masih tersimpan di peramban (Berita, Dokumentasi, Unduhan), waktunya berasal dari peramban masing-masing pegawai.

## D-026 · 2026-10-14 · Pengaturan Situs tersimpan di server; hanya Administrator yang menyimpan

Pengaturan Situs kini tersimpan di server. Kaki halaman portal dan bilah pengumuman di halaman Informasi membaca isinya dari API.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Dokumen pengaturan `situs` (`/api/pengaturan/situs`) memuat nama portal (maks. 40 karakter), kepanjangan (80), nama instansi (120), nama yang dikenal (60), instansi induk (160), deskripsi singkat (300), alamat portal publik, teks hak cipta (160), dan bilah pengumuman: tampil atau tidak, teks (maks. 200, wajib selama tampil), label tautan (30), dan tujuan tautan. Alamat portal dan tujuan tautan harus berupa tautan portal yang sah (`PortalLink`, D-019). |
| Akses | **Hanya Administrator yang menyimpan** (keputusan pemilik): pengaturan ini berlaku untuk seluruh portal dan bukan milik modul mana pun. Semua pegawai boleh membacanya untuk sidebar dan tautan ke portal; di dashboard, formulirnya hanya-baca bagi pegawai lain. Karena log aktivitas disusun per modul, penyimpanan ini tidak dicatat di sana, sama seperti pengelolaan akun. Waktu simpannya tetap tercatat (`GET /api/pengaturan`). |
| Portal | **Portal membaca bilah pengumuman serta deskripsi dan hak cipta di kaki halaman** (keputusan pemilik), lewat `GET /api/public/situs`, yang hanya memuat bagian itu. Nama dan logo di kepala halaman, merek di kaki halaman, judul tab, dan baris hak cipta ringkas di halaman Login tetap mengikuti HTML, supaya tidak berganti setelah halaman dimuat dan tetap terbaca mesin pencari. |
| Isi awal | Teks yang tertulis di HTML portal menjadi isi awal dan tetap tampil sampai pengaturan pertama kali disimpan, seperti Beranda (D-023). Bila API gagal, teks HTML yang tampil. Tes portal memastikan teks isi awal masih sama dengan teks di HTML. |
| Tampilan | Teks dipasang sebagai teks polos. Deskripsi atau hak cipta yang dikosongkan disembunyikan. Bilah pengumuman disembunyikan bila dimatikan atau teksnya kosong. Tanpa tujuan tautan yang sah, bilah tampil sebagai teks tanpa tautan dan tanpa label. |

Setiap halaman portal yang memiliki kaki halaman kini memakai satu permintaan tambahan dari batas laju API publik (D-013).

## D-027 · 2026-10-14 · Profil akun tersimpan di server; nama dan surel mengikuti akun

Tab *Akun* di halaman Pengaturan kini membaca dan menyimpan profil akun yang sedang masuk di server, bukan salinan di peramban.

| Hal | Keputusan |
|---|---|
| Nama dan surel | **Mengikuti akun server** (keputusan pemilik). Tab Akun menampilkannya sebagai hanya-baca; Administrator mengubahnya di Akun Pegawai (D-008). Server menolak nama atau surel yang berbeda dari akun. |
| Profil | **Jabatan, unit, dan peran dalam pengelolaan informasi disimpan per akun di server** (keputusan pemilik), di tabel `users` (`jabatan`, `unit`, `peran_informasi`). Setiap pegawai mengisi profilnya sendiri: jabatan dan unit maks. 120 karakter, dan peran salah satu dari Penyedia, Pemeriksa, Penyetuju, atau Pengelola publikasi. Profil hanya keterangan; hak akses tetap ditentukan peran akun dan modulnya. Penyimpanan profil tidak dicatat di log aktivitas. |
| Nama pelaku | Riwayat alur publikasi dan log aktivitas untuk modul yang masih tersimpan di peramban (Berita, Dokumentasi, Unduhan, Absensi) kini memakai nama akun yang sedang masuk, sama seperti modul di server. Nama di profil peramban tidak dipakai lagi. |

`GET` dan `PUT /api/pengaturan/akun` hanya membaca dan mengubah akun yang sedang masuk. Administrator belum melihat profil pegawai lain di Akun Pegawai.

## D-028 · 2026-10-14 · Unggahan disimpan di Laravel Cloud Object Storage

Berkas yang diunggah dari dashboard kini bisa disimpan di server. Ini fondasinya: modul yang memakai unggahan pindah ke server di slice berikutnya, dan sampai saat itu dashboard tetap menyimpan unggahannya di peramban. Semua keputusan di bawah adalah keputusan pemilik atas rancangan yang diajukan.

| Hal | Keputusan |
|---|---|
| Tempat simpan | Laravel Cloud Object Storage (Cloudflare R2), dikelola dari Laravel Cloud, bukan dari akun Cloudflare sendiri. Setiap environment memakai dua bucket: bucket privat sebagai disk bawaan dan bucket publik dengan nama disk `public`. Laravel membaca konfigurasi keduanya dari Cloud. Visibilitas R2 berlaku per bucket, jadi aplikasi tidak pernah menetapkan visibilitas per berkas. Dependensi baru `league/flysystem-aws-s3-v3`. Di lokal dipakai disk lokal; tes memakai `Storage::fake`. |
| Publik atau privat | Publik: foto Berita, foto Dokumentasi, dokumen Regulasi & Unduhan, PDF maklumat Visi & Misi, dan foto Tentang UPTD serta Struktur Organisasi. Privat: foto Absensi Lapangan, karena berisi data pribadi dan bukti kehadiran. |
| Sebelum terbit | Berkas konten langsung masuk bucket publik dengan nama acak yang tidak bisa ditebak. Portal hanya menautkan entri yang sudah terbit. |
| Akses berkas privat | Dibaca hanya lewat aplikasi (`GET /api/berkas/{id}`), dengan hak akses diperiksa di setiap permintaan: pengunggahnya dan Administrator. Slice Absensi menentukan siapa lagi yang boleh membacanya. |
| Jenis dan ukuran | Tipe dibaca dari isi berkas, bukan dari nama atau tipe kiriman browser. Gambar: JPG, PNG, WebP, maks. 5 MB dan 25 megapiksel. Server menegakkan foto sesuai orientasi kameranya, memperkecilnya ke maks. 1600 px, dan menyimpannya ulang tanpa metadata, termasuk lokasi GPS. Dokumen: PDF, DOCX, XLSX, maks. 10 MB, disimpan apa adanya. Maklumat Visi & Misi hanya PDF. Berkas `.doc`/`.xls` lama, SVG, dan HTML tidak diterima. |
| Pencatatan | Setiap berkas dicatat di tabel `uploads`: tujuan, publik atau privat, lokasi `tujuan/tahun/bulan/ID.ext`, nama asli untuk tampilan, tipe hasil deteksi, ukuran, pengunggah, dan waktu pertama dipakai. `POST /api/unggah` mewajibkan isian `modul` (tujuan). Hanya pengelola modul tujuan yang boleh mengunggah, kecuali foto absensi yang boleh diunggah semua pegawai. Batas laju 30 unggahan per menit per pegawai. |
| Pembersihan | Berkas yang tidak dipakai konten tersimpan dalam sehari dihapus otomatis (`model:prune`, harian). Menghapus catatan berkas juga menghapus berkasnya. Slice tiap modul menghapus berkas lama saat entri dihapus atau berkasnya diganti. |
| Retensi foto absensi | Belum dihapus otomatis, sama seperti Pesan (D-016). Aturan retensinya diputuskan bersama unit hukum. |

Sebelum go-live:

- Buat dan pasang dua bucket di environment produksi: privat sebagai disk bawaan dan publik dengan nama disk `public`. Lokasinya dipilih saat dibuat dan tidak bisa diubah. Nyalakan *Scheduler* agar pembersihan harian berjalan.
- Pastikan batas unggahan PHP di Cloud (`upload_max_filesize` dan `post_max_size`) minimal 10 MB lewat tab *Commands* (`php -i`).
- Konfirmasi ke unit hukum atau Diskominfo kewajiban lokasi data lingkup publik (PP 71/2019). R2 tidak menawarkan wilayah Indonesia; ini juga menyangkut database di Laravel Cloud (D-006).
- Tentukan cadangan isi bucket; dokumentasi Laravel Cloud tidak menyebut cadangan otomatis untuk bucket.
- Bila portal kelak memakai Content-Security-Policy, izinkan host bucket publik.

## D-029 · 2026-10-14 · Foto Tentang UPTD dan maklumat diunggah ke server; profil disusun dari Renstra dan RPJMD

Unggahan yang ditunda di D-020 dan D-021 kini disimpan di server dengan fondasi D-028. Isi awal Tentang UPTD juga disusun ulang dari dokumen perencanaan resmi, atas permintaan pemilik.

| Hal | Keputusan |
|---|---|
| Foto Tentang UPTD | Foto kantor dan foto Kepala UPTD diunggah dari dashboard (tujuan `profil`). Server hanya menerima alamat unggahan PINTU untuk tujuan itu; foto yang diganti atau dikosongkan dihapus bersama berkasnya. |
| Foto gedung bawaan | **Foto Kantor Bersama Samsat Kota Kupang dari pemilik menjadi foto gedung bawaan portal** (`public/assets/img/kantor-samsat-kota-kupang-1600.webp` dan versi 800 px, tanpa metadata). Foto ini tampil sampai foto kantor diunggah dari dashboard. Foto Kepala UPTD belum ada, jadi ilustrasinya tetap tampil sampai diunggah. |
| Berkas maklumat | PDF maklumat diunggah dari dashboard (tujuan `visiMisi`, hanya PDF). Nama, ukuran, dan tipenya diambil dari catatan unggahan, bukan dari kiriman dashboard. Tombol "Unduh maklumat (PDF)" di portal menautkan berkas itu, atau halaman Regulasi & Unduhan bila belum ada. |
| Isi awal Tentang UPTD | **Disusun dari Renstra BPAD 2025–2029** (Pergub NTT No. 46 Tahun 2025) **dan RPJMD NTT 2025–2029** (Perda NTT No. 6 Tahun 2025), atas permintaan pemilik. Isinya: dasar organisasi Pergub NTT No. 96 Tahun 2023; satu dari 22 UPTD; susunan organisasi (Kepala UPTD, Subbagian Tata Usaha, Seksi Penetapan dan Penagihan, Seksi Verifikasi, kelompok jabatan fungsional); tugas pemungutan PKB, BBNKB, PBBKB, dan Pajak Air Permukaan; layanan terpadu bersama Polri dan PT Jasa Raharja; peran mengawal retribusi dan memantau aset; luas Kota Kupang 159 km². Teks lama yang tidak bertentangan dengan dokumen tetap dipakai. Kedua dokumen tidak menyebut nama Kepala UPTD; namanya, **Remmy Christian Pah, S.STP, M.Si**, diberikan pemilik dan juga tampil di bagan Struktur Organisasi. |
| Nama unit kerja | **Seluruh portal dan dashboard memakai nama unit menurut Renstra** (keputusan pemilik): Subbagian Tata Usaha, Seksi Penetapan dan Penagihan, dan Seksi Verifikasi. Nama lama dipetakan menurut tugasnya. "Seksi Penagihan & Pelaporan" (penagihan, Samsat Keliling) menjadi Seksi Penetapan dan Penagihan. "Seksi Pendataan & Penetapan" (pengumpulan data, pemeriksaan berkas persyaratan) menjadi Seksi Verifikasi. Perubahan mencakup bagan dan uraian tugas di halaman Struktur Organisasi (uraian dari Renstra), standar awal Jadwal Pemutakhiran (D-025), penyedia layanan awal (D-022), daftar unit penyedia dan pemeriksa di dashboard, serta pesan yang menyebut Subbagian Tata Usaha. |
| Unggahan dashboard | Setiap unggahan menyebut tujuannya. Mode campuran mengirim unggahan `visiMisi` dan `profil` ke server; tujuan lain tetap di peramban sampai modulnya pindah. Isian foto tidak menawarkan "Pakai URL" untuk tujuan yang sudah di server. |

Sumber resmi susunan dan tugas UPTD adalah Pergub NTT No. 96 Tahun 2023; Renstra meringkasnya, dan Pergub-nya belum dicocokkan langsung.

## D-030 · 2026-10-14 · Regulasi & Unduhan tersimpan di server dengan alur persetujuan

Regulasi & Unduhan kini tersimpan di server dengan alur publikasi yang sama dengan Persyaratan Layanan (D-022). Halaman Regulasi & Unduhan membaca dokumen yang terbit dari API.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Tabel `documents` menyimpan judul, keterangan, kategori (`regulasi`, `standar`, `formulir`, `laporan`), format (PDF, DOCX, XLSX, JPG, atau Tautan), nomor, tahun, berkas, tautan luar, urutan masuk, serta kolom alur seperti layanan. |
| Berkas dan tautan | Berkas diunggah dari dashboard (tujuan `dokumen`, D-028): PDF, DOCX, atau XLSX maks. 10 MB, atau gambar maks. 5 MB. Nama, ukuran, dan tipenya diambil dari catatan unggahan, dan formatnya mengikuti jenis berkas yang dikenali server. Satu unggahan hanya dipakai satu dokumen. Berkas yang diganti atau dikosongkan, dan berkas dokumen yang dihapus, ikut terhapus. Dokumen tanpa berkas dapat menautkan sumber resmi seperti JDIH; tautan hanya alamat `http(s)`. |
| Alur dan akses | **Sama dengan Persyaratan Layanan** (keputusan pemilik). Pengelola Regulasi & Unduhan menyiapkan, mengajukan, dan memeriksa. Administrator menyetujui dan menerbitkan, mengembalikan dari tahap persetujuan, menarik dari portal, dan menghapus. Dokumen yang menunggu persetujuan atau terbit hanya dapat diubah Administrator. **Dokumen terbit wajib memiliki berkas atau tautan.** Tidak ada pengurutan manual karena portal mengurutkan per kategori. Pengelola Jadwal Pemutakhiran boleh membaca daftarnya. |
| Data awal | **Sembilan dokumen yang tercantum di halaman Unduhan dimasukkan lewat migrasi sebagai draf tanpa berkas, dengan judul resmi** (keputusan pemilik). Judul regulasi diambil dari dasar hukum Renstra BPAD 2025–2029: UU No. 1 Tahun 2022 (Hubungan Keuangan antara Pemerintah Pusat dan Pemerintahan Daerah), Perda Provinsi NTT No. 1 Tahun 2024 (Pajak Daerah dan Retribusi Daerah), dan Pergub NTT No. 96 Tahun 2023 (Kedudukan, Susunan Organisasi, Tugas dan Fungsi serta Tata Kerja BPAD). Enam dokumen lain (standar pelayanan, maklumat, SOP, dua formulir, dan laporan SKM) memakai judul di halaman. Penyedianya Subbagian Tata Usaha, kecuali formulir (Seksi Verifikasi); pemeriksanya Kepala Subbagian Tata Usaha. |
| Portal | `GET /api/public/dokumen` memberikan dokumen terbit tanpa ID dan tanpa kolom alur selain waktu terbit. Halaman Regulasi & Unduhan menyusun daftar dengan tampilan yang sama: tombol "Unduh" untuk berkas dan "Buka" di tab baru untuk tautan. Saringan kategori dan pencarian tetap bekerja. Sampai ada dokumen terbit, halaman menyatakan dokumen sedang disiapkan dan menyembunyikan saringan. Bila API gagal, tampil keterangan dan tombol coba lagi. Catatan prototipe diganti keterangan bahwa dokumen terbit setelah diverifikasi petugas. |
| Dashboard | Unggahan `dokumen` dikirim ke server dan formulir hanya menerima format yang diterima server. Penghapusan hanya ditawarkan kepada Administrator. Penyunting terkunci bagi pegawai lain selama dokumen menunggu persetujuan atau terbit. Ringkasan, antrean kerja, menu perintah, Jadwal Pemutakhiran, dan *Pengaturan › Data* hanya membaca dokumen bila pengguna boleh membacanya. |

Alur publikasi kini dipakai bersama oleh layanan dan dokumen: status dan riwayat di model, pemeriksaan langkah di permintaan, dan aksi pembaruan yang sama. Berita dan Dokumentasi dapat memakainya saat pindah ke server.

Belum dibuat: berkas atau tautan untuk sembilan dokumen awal. Administrator perlu mengunggahnya dari naskah resmi, mencocokkan judul dan keterangannya, lalu menerbitkannya.
