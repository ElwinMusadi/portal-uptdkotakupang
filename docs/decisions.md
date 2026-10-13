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
