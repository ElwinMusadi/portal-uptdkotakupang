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
| Portal | `GET /api/public/papan` memberikan isi papan tanpa ID loket. Beranda menampilkan nomor antrean, waktu tunggu, selisih, dan status loket **hanya pada hari yang sama dengan pembaruannya** (WITA); papan yang belum diperbarui hari ini menampilkan "Belum diperbarui hari ini" dan waktu pembaruan terakhirnya, agar antrean kemarin tidak tampil sebagai antrean sekarang. Sebelum pertama kali disimpan, papan menyatakan belum tersedia. Label "Pratinjau · data contoh" diganti waktu pembaruan. Diganti D-050: isi terakhir tampil setiap hari. |
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
| Penyimpanan | Dokumen pengaturan `statistik` (`/api/pengaturan/statistik`) memuat status (Ilustrasi atau Terverifikasi), tahun data, dan catatan (maks. 300 karakter). Isinya empat kategori tetap: Transaksi pelayanan, Penerimaan pajak, Samsat Keliling, dan Kepuasan masyarakat (sejak D-055 tiga: Penerimaan pajak diisi otomatis dari Laporan Penerimaan). Tiap kategori punya nama tab, subjudul, judul dan satuan grafik, **empat angka utama** (label, nilai, satuan, perubahan dalam persen, dan apakah kenaikan bermakna baik), serta **12 nilai bulanan Januari–Desember**. |
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

## D-031 · 2026-10-14 · Berita & Pengumuman tersimpan di server; pengelola Berita menerbitkan sendiri

Berita & Pengumuman kini tersimpan di server dengan alur publikasi. Halaman Informasi, halaman satu tulisan di `/berita/{slug}`, dan ubin *Pengumuman* di papan layanan beranda membaca tulisan yang terbit dari API.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Tabel `articles` menyimpan judul, slug unik (alamat tulisan), kategori (`berita`, `pengumuman`, `keliling`, `edukasi`), ringkasan, isi HTML dari editor (diperiksa seperti Halaman Statis, D-024), foto sampul, keterangan dan kredit foto, tanggal tayang, tanda tulisan utama dan tampil di beranda, serta kolom alur. |
| Alur dan akses | **Pengelola Berita & Pengumuman menjalankan semua langkah sendiri** (keputusan pemilik), karena pengumuman sering mendesak. Langkah itu mencakup menyetujui dan menerbitkan, menarik dari portal, dan mengubah tulisan yang sudah terbit. **Hanya Administrator yang menghapus.** Langkah alurnya sama dengan layanan dan dicatat di riwayat. Pengelola Jadwal Pemutakhiran boleh membaca daftarnya. |
| Foto sampul | Foto diunggah dari dashboard (tujuan `berita`, D-028), dan satu foto hanya dipakai satu tulisan. Foto yang diganti, dikosongkan, atau tulisannya dihapus ikut terhapus. |
| Tulisan utama | Hanya satu tulisan yang bertanda utama. Menandai satu tulisan melepas tanda pada tulisan lain tanpa mengubah waktu pembaruannya. Halaman Informasi menampilkan tulisan utama terbaru yang terbit; bila tidak ada, tulisan terbaru. |
| Data awal | **Hanya artikel peluncuran PINTU yang dimasukkan, sebagai draf** (keputusan pemilik), bertanggal hari migrasi dan bertanda tulisan utama. Tulisan lain di halaman Informasi adalah contoh tata letak dan tidak dimasukkan. Isinya tetap seperti contoh; paragraf tentang "empat peran" perlu disesuaikan dengan alur yang berlaku sebelum artikel diterbitkan. |
| Portal | `GET /api/public/berita` memberikan tulisan terbit tanpa isi, terbaru lebih dulu menurut tanggal tayang. `GET /api/public/berita/{slug}` memberikan satu tulisan terbit beserta isinya. Keduanya tanpa ID dan riwayat. **Halaman satu tulisan beralamat `/berita/{slug}` dan diisi skrip** (keputusan pemilik). Server menulis ulang alamat relatif halaman itu menjadi alamat dari akar situs, karena di bawah `/berita/` alamat relatif tidak lagi menunjuk ke tempat yang benar. Alamat contoh lama `/berita-detail` dialihkan ke `/informasi`. |
| Tampilan portal | Halaman Informasi menampilkan tulisan utama, daftar tulisan lain dengan saringan kategori dan pencarian, serta arsip enam pengumuman terbaru. Navigasi halaman (1, 2, 3) yang hanya contoh dihapus, sehingga semua tulisan terbit tampil. Tulisan tanpa foto memakai ilustrasi kategori. Papan layanan beranda menampilkan pengumuman terbaru yang ditandai tampil di beranda, dengan tanda "Baru" selama tujuh hari setelah terbit. Bila belum ada tulisan terbit, setiap bagian menyatakannya. Bila API gagal, tampil keterangan dan tombol coba lagi. Tanggal tayang hanya ditampilkan, bukan jadwal terbit. |
| Dashboard | Unggahan `berita` dikirim ke server. Penghapusan hanya ditawarkan kepada Administrator. Penyunting tidak terkunci bagi pengelola Berita. Ringkasan, antrean kerja, menu perintah, Jadwal Pemutakhiran, dan *Pengaturan › Data* hanya membaca berita bila pengguna boleh membacanya. |

Bilah pengumuman di halaman Informasi tetap diatur di Pengaturan Situs (D-026). Untuk menautkannya ke sebuah tulisan, isi tujuan tautan dengan `./berita/{slug}`.

## D-032 · 2026-10-14 · Dokumentasi Kegiatan tersimpan di server; galeri disusun per bulan

Dokumentasi Kegiatan kini tersimpan di server dengan alur publikasi. Galeri di halaman Dokumentasi membaca kegiatan yang terbit beserta fotonya dari API.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Tabel `documentations` menyimpan nama kegiatan, kategori (`keliling`, `sosialisasi`, `operasi`, `internal`), lokasi, tanggal kegiatan, deskripsi, daftar foto (maks. 30, masing-masing dengan keterangan dan urutan tampil), serta kolom alur. |
| Foto | Foto diunggah dari dashboard (tujuan `dokumentasi`, D-028), dan satu foto hanya dipakai satu kegiatan. Foto yang dihapus dari kegiatan, atau kegiatannya dihapus, ikut terhapus. **Kegiatan terbit wajib memiliki minimal satu foto.** |
| Alur dan akses | **Sama dengan Berita** (keputusan pemilik): pengelola Dokumentasi Kegiatan menjalankan semua langkah sendiri, termasuk menerbitkan, menarik, dan mengubah kegiatan yang sudah terbit. Hanya Administrator yang menghapus. Pengelola Jadwal Pemutakhiran boleh membaca daftarnya. |
| Data awal | **Sembilan kegiatan contoh dari dashboard dimasukkan sebagai draf tanpa foto** (keputusan pemilik), dengan nama, lokasi, tanggal, dan deskripsinya. Isinya belum terverifikasi; dua di antaranya bertanggal November 2026 sebagai rencana kegiatan. Penyedia mengikuti contoh, dan pemeriksanya kepala unit penyedia. |
| Portal | `GET /api/public/dokumentasi` memberikan kegiatan terbit beserta fotonya, terbaru lebih dulu, tanpa ID dan tanpa kolom alur selain waktu terbit. **Galeri disusun per bulan** (keputusan pemilik): yang tampil lebih dulu adalah foto bulan terbaru. Bagian Arsip berisi tombol untuk setiap bulan pada tahun terbaru dan satu tombol per tahun sebelumnya (mis. "Arsip 2025"). Saringan kategori bekerja di dalam periode yang dipilih, dan ringkasan menyebut periode serta jumlah fotonya. Setiap foto menjadi satu ubin galeri, dengan keterangan foto atau nama kegiatan, lokasi, dan tanggal. Lightbox menampilkan foto utuh. Bila belum ada kegiatan terbit, halaman menyatakannya dan menyembunyikan saringan serta arsip. Bila API gagal, tampil keterangan dan tombol coba lagi. Catatan "Placeholder ilustrasi" dihapus. |
| Dashboard | Unggahan `dokumentasi` dikirim ke server. Penghapusan hanya ditawarkan kepada Administrator. Ringkasan, antrean kerja, Jadwal Pemutakhiran, dan *Pengaturan › Data* hanya membaca dokumentasi bila pengguna boleh membacanya. |

## D-033 · 2026-10-14 · Struktur Organisasi tersimpan di server; NIP pejabat tidak ditampilkan di portal

Struktur Organisasi kini tersimpan di server. Bagan dan uraian tugas di halaman Struktur Organisasi dibaca dari API. Modul ini tanpa alur publikasi: perubahan langsung tampil di portal saat disimpan.

| Hal | Keputusan |
|---|---|
| Penyimpanan | Tabel `organization_units` menyimpan nama unit, singkatan (1–3 huruf atau angka, disimpan kapital), jenis (`pimpinan`, `tata-usaha`, `seksi`, `fungsional`, `mitra`), unit di atasnya (`induk`), jabatan, nama pejabat, NIP, foto pejabat, jabatan pelaksana (maks. 20), uraian tugas, catatan, dan urutan. |
| Susunan | Unit di atasnya harus unit yang ada dan bukan mitra. Server menolak memindahkan unit ke bawah dirinya sendiri atau ke bawah unit yang dibawahinya. Unit yang masih membawahi unit lain hanya bisa dihapus bersama unit di bawahnya. |
| Akses | **Pengelola Struktur Organisasi** (keputusan pemilik) menambah, menyunting, mengurutkan, dan menghapus unit. |
| Data awal | **Ketujuh unit yang tampil di halaman** (keputusan pemilik), dengan jabatan pelaksana dan uraian tugas dari Renstra (D-029). Kepala UPTD bernama Remmy Christian Pah, S.STP, M.Si; nama pejabat lain dikosongkan sampai diverifikasi, sehingga bagan menampilkan jabatannya saja. |
| Foto dan NIP | Foto pejabat diunggah dari dashboard (tujuan `unit`, D-028), dan satu foto hanya dipakai satu unit. Foto yang diganti, dikosongkan, atau unitnya dihapus ikut terhapus. **NIP pejabat hanya untuk internal dan tidak pernah dikirim ke API publik** (keputusan pemilik). |
| Portal | `GET /api/public/struktur` memberikan pohon unit UPTD sesuai urutan dan daftar mitra, tanpa ID dan NIP. Bagan disusun seperti sebelumnya: pimpinan di atas, unit di bawahnya berjajar (jumlah kolomnya mengikuti data) dengan jabatan pelaksana, kelompok jabatan fungsional di bawah garis, mitra sebagai lencana dengan catatan koordinasinya. Foto pejabat menggantikan inisial bila ada. Uraian tugas setiap unit tampil di akordeon, dengan catatan unit sebagai paragraf kedua. Bila API gagal, tampil keterangan dan tombol coba lagi. Catatan prototipe diganti keterangan sumber susunan (Renstra BPAD 2025–2029, Pergub NTT No. 96 Tahun 2023). |
| Dashboard | Koleksi `unit` dan unggahan `unit` dikirim ke server. Batas isian formulir mengikuti server, dan keterangan NIP menyebut bahwa NIP tidak ditampilkan di portal. |

Bagian "Empat peran. Satu alur sebelum terbit." di halaman yang sama tetap teks statis. Isinya belum mencerminkan alur yang berlaku: Layanan dan Regulasi & Unduhan disetujui Administrator, sedangkan Berita dan Dokumentasi diterbitkan pengelolanya sendiri.

## D-034 · 2026-10-14 · Absensi Lapangan tersimpan di server; petugas clock-in lewat aplikasi /absen

Absensi Lapangan kini tersimpan di server dan berganti bentuk: dari catatan datang dan pulang di peramban menjadi clock-in petugas di kegiatan lapangan seperti tilang gabungan. Clock-out tidak dicatat PINTU; petugas melakukannya dengan mesin sidik jari di lapangan setelah tugas selesai.

| Hal | Keputusan |
|---|---|
| Aplikasi | **PWA `/absen` di aplikasi ini** (keputusan pemilik): halaman React tersendiri (`resources/js/absen`) yang bisa dipasang ke layar utama ponsel, dengan akun dan sesi yang sama dengan dashboard. Tamu dialihkan ke `/login?lanjut=absen` lalu kembali ke aplikasi. Service worker hanya menyimpan berkas build agar aplikasi cepat dibuka; halaman dan API selalu diambil dari server, dan clock-in tidak pernah diantrekan saat tidak ada koneksi. |
| Kegiatan | Administrator menambah, menyunting, dan menghapus kegiatan di dashboard: nama, tanggal, tempat, titik lokasi (lintang, bujur), radius 20–2000 m, catatan untuk petugas, dan petugas yang ditugaskan (maks. 200). Menghapus kegiatan ikut menghapus clock-in dan swafotonya. Perubahan kegiatan tidak dicatat di log aktivitas karena Absensi bukan modul dashboard. |
| Siapa yang clock-in | **Hanya petugas yang ditugaskan** di kegiatan itu (keputusan pemilik), satu kali per kegiatan. Administrator yang tidak ditugaskan juga tidak bisa clock-in. |
| Waktu | **Sepanjang tanggal kegiatan menurut WITA** (keputusan pemilik), tanpa jendela jam. Waktu clock-in diambil dari jam server, bukan dari ponsel. |
| Lokasi | Ponsel mengirim titik GPS dan akurasinya. Server menghitung jaraknya ke titik kegiatan (haversine, meter bulat) dan menolak clock-in di luar radius atau dengan akurasi lebih buruk dari ±100 m. Jarak dan akurasi disimpan bersama clock-in; mengubah titik atau radius kegiatan tidak mengubah clock-in yang sudah tercatat. Lokasi palsu (GPS *spoofing*) tidak dapat dideteksi dari peramban web; swafoto, jarak, dan akurasi menjadi bahan pemeriksaan. |
| Swafoto | **Diambil langsung dari kamera depan** (keputusan pemilik), bukan dari galeri, diperkecil ke maks. 1024 px di ponsel, lalu diunggah ke bucket privat (tujuan `absensi`, D-028). Server hanya menerima swafoto yang diunggah petugas itu sendiri dalam 15 menit terakhir dan belum dipakai clock-in lain. |
| Akses swafoto | Hanya petugas yang mengunggahnya dan Administrator (`GET /api/berkas/{id}`); tidak ada pembaca lain (menjawab D-028). |
| Rekap | Administrator melihat daftar kegiatan dengan jumlah petugas yang sudah clock-in, dan rekap per kegiatan: jam clock-in (WITA), jarak, akurasi, swafoto, dan tautan peta. Pegawai lain melihat tautan ke aplikasi di menu Absensi Lapangan. |
| Data lama | Koleksi `absensi` di peramban (datang dan pulang) dihapus dari dashboard dan dari cadangan JSON. Isinya contoh dan tidak dipindahkan. Tidak ada data awal kegiatan. |

Kamera dan lokasi hanya bekerja lewat HTTPS, jadi aplikasi diuji penuh di Laravel Cloud, bukan di situs Herd lokal (`http`). Retensi swafoto tetap menunggu keputusan bersama unit hukum (D-028).

## D-035 · 2026-10-14 · Navbar portal berbentuk notch; tema bawaan dashboard Terang

| Hal | Keputusan |
|---|---|
| Navbar portal | **Notch yang menempel di tepi atas halaman** (keputusan pemilik, mengikuti bahasa desain referensi Kage "tinykpi navigation"): sudut bawah membulat 20 px, lekuk 16 px di kedua sisi yang menyatukan notch dengan tepi atas, tetap terlihat saat digulir. Menggantikan pil mengambang. |
| Isi | **Semua menu tetap ada, dipadatkan** (keputusan pemilik): Profil, Layanan, Jadwal, Informasi, Kontak & Pengaduan, ikon Login Pegawai, dan tombol "Cek Persyaratan"; teks 13 px, tombol 36 px. Di bawah 1001 px tautan masuk ke tombol Menu di dalam notch, dan menu seluler terbuka di bawahnya. Markup header di semua halaman tidak berubah; perubahan hanya di `components.css`. |
| Warna | **Tetap warna PINTU** (keputusan pemilik): ink #0F1319, tautan redup, tombol putih, dan aksen #7FA4FF untuk fokus dan halaman aktif. |
| Tema dashboard | **Terang menjadi tema bawaan** dashboard dan aplikasi `/absen` (keputusan pemilik), menggantikan "ikuti sistem". Pilihan Gelap atau Sistem yang sudah disimpan pegawai tetap dihormati. |

## D-036 · 2026-10-14 · Ukuran notch disamakan dengan referensi Kage

Atas permintaan pemilik, notch D-035 diukur ulang dari gambar referensi Kage "tinykpi navigation" (tangkapan 1280 px, skala 1:1) dan disamakan. **Menggantikan** ukuran di D-035; isi, warna PINTU, dan perilaku seluler tetap.

| Hal | Referensi | PINTU |
|---|---|---|
| Tinggi | 62 px: 12 + tombol 38 + 12 | sama |
| Sudut bawah | ±22 px | 22 px |
| Lekuk ke tepi atas | ±18 px | 18 px |
| Jarak tepi kiri ke logo / tombol ke tepi kanan | 23 px / 11 px | 22 px / 12 px |
| Tombol | 38 px | 38 px (tombol Login, "Cek Persyaratan", dan Menu) |
| Bayangan | tidak ada | tidak ada |
| Latar | pekat #08090D | pekat ink #0F1319 (warna PINTU, D-035); tanpa transparansi dan blur agar lekuk menyatu tanpa sambungan |
| Lebar | 800 px, tiga tautan | mengikuti isi, ±870 px di layar 1280 px, karena kelima menu PINTU tetap ada (D-035) |

Tombol "Cek Persyaratan" tetap berbentuk pil seperti tombol lain di portal; di referensi tombolnya persegi dengan sudut ±10 px. Dropdown kini terbuka ±10 px di bawah notch.

## D-037 · 2026-10-14 · Mitra satu atap memakai logo; inisial sebagai cadangan

| Hal | Keputusan |
|---|---|
| Tampilan | **Logo menggantikan lingkaran inisial** di lencana mitra (keputusan pemilik): logo dipaskan utuh di lingkaran 28 px berlatar putih, nama mitra tetap di sebelahnya. Mitra tanpa logo tetap memakai inisial. Logo tidak diberi teks alternatif sendiri karena namanya sudah tertulis. |
| Beranda | Setiap mitra di dokumen Beranda punya `logo` (alamat unggahan atau `null`). Dokumen yang disimpan sebelum ada logo dibaca dengan `logo` `null`. |
| Struktur Organisasi | **Ikut memakai logo** (keputusan pemilik): unit berjenis mitra memakai isian foto yang sudah ada sebagai logonya. Di dashboard isian itu berlabel "Logo mitra" dan ditampilkan utuh, dan `GET /api/public/struktur` kini menyertakan `foto` mitra. |
| Unggahan | **Diunggah lewat dashboard** (keputusan pemilik), bukan diambil dari situs mitra. Tujuan unggahan baru `beranda` (bucket publik, JPG/PNG/WebP, maks. 5 MB) untuk Pengelola Beranda; logo mitra Struktur memakai tujuan `unit`. Logo yang diganti, dikosongkan, atau mitranya dihapus ikut terhapus (D-028). PNG berlatar transparan paling baik. Peramban kini menyimpan WebP tetap sebagai WebP (sebelumnya diubah menjadi JPEG) agar transparansi logo terjaga. |

## D-038 · 2026-10-14 · Isi kanal layanan bisa diberi tautan

| Hal | Keputusan |
|---|---|
| Letak | **Per isi kelompok** (keputusan pemilik): setiap isi kanal di peta kanal beranda punya nama dan tautan opsional, mis. TikTok ke akun TikTok, WhatsApp ke halaman kontak, Ombudsman NTT ke situsnya. Isi tanpa tautan tetap teks biasa. |
| Format tautan | Sama dengan tombol pembuka beranda: alamat lengkap `http(s)://`, halaman portal (mis. `kontak.html#pengaduan`), `tel:`, `mailto:`, atau jangkar `#`; maks. 300 karakter. Nama kanal wajib bila tautannya diisi. |
| Membuka | **Situs luar di tab baru** (keputusan pemilik), dengan `rel="noopener"` dan keterangan "(tab baru)" untuk pembaca layar; halaman portal, telepon, dan surel di tab yang sama. |
| Tampilan | **Hanya garis bawah saat disorot atau difokus** (keputusan pemilik), tanpa tanda panah. Ikon dan nama kanal menjadi satu tautan. |
| Data lama | Dokumen Beranda yang disimpan sebelum ini (isi kanal berupa teks) dibaca sebagai kanal tanpa tautan, dan dashboard yang masih terbuka dengan versi lama tetap bisa menyimpan. |

## D-039 · 2026-10-14 · Teks alur publikasi di portal mengikuti alur yang berlaku

Menjawab catatan di D-033: bagian "Empat peran. Satu alur sebelum terbit." di halaman Struktur Organisasi menyebut bahwa setiap informasi melewati penyedia, pemeriksa, atasan langsung, dan operator portal, padahal alur yang berlaku berbeda menurut modul (D-022, D-030, D-031, D-032).

| Hal | Keputusan |
|---|---|
| Struktur Organisasi | **Ditulis ulang sesuai alur** (keputusan pemilik), tata letak empat kartu tetap: "Empat langkah. Satu alur sebelum terbit." Persyaratan layanan, regulasi, berita, dan dokumentasi kegiatan melewati alur ini; jadwal dan informasi harian diperbarui langsung oleh petugas yang ditugaskan. Langkahnya: disiapkan pengelola modul; diperiksa dan dicocokkan dengan sumber resmi; disetujui Administrator (persyaratan layanan dan regulasi) atau pengelolanya (berita dan dokumentasi); terbit dengan tanggal tercatat, dan informasi yang tidak berlaku lagi ditarik ke arsip. |
| Login | **Ikut disesuaikan** (keputusan pemilik): panel samping menyebut pengelola modul, pemeriksaan, persetujuan oleh Administrator untuk layanan dan regulasi atau oleh pengelolanya untuk berita dan dokumentasi, lalu terbit. |

## D-040 · 2026-10-14 · Video alur pelayanan diatur dari Dashboard › Beranda

Tombol "Tonton alur pelayanan" di bawah papan layanan beranda sebelumnya placeholder prototipe: dialognya hanya berisi "Video alur pelayanan sedang disiapkan." dan durasi contoh 2:14.

| Hal | Keputusan |
|---|---|
| Isian | **Tautan YouTube, label tombol, durasi, dan judul** (keputusan pemilik) di bagian baru "Video alur pelayanan" pada dokumen Beranda. Tautan bagikan (`youtu.be`), tonton, Shorts, live, dan sematan diterima, lalu disimpan sebagai tautan tonton tanpa parameter pelacak seperti `si`. Durasi opsional dan ditulis sendiri (mis. 2:14); judul opsional, bila kosong label tombol yang dipakai. |
| Tanpa video | **Tombol disembunyikan** (keputusan pemilik) selama tautan kosong; dialog "sedang disiapkan" dihapus. |
| Pemutar | **Mode privasi, dimuat saat dibuka** (keputusan pemilik): sematan `youtube-nocookie.com`, baru dimuat ketika dialog dibuka dan dilepas saat ditutup, sehingga beranda tidak memuat apa pun dari YouTube dan video berhenti ketika dialog ditutup. API publik hanya memberikan ID videonya. |
| Akses | Pengelola Beranda, sama dengan isi Beranda lainnya. Dashboard yang masih terbuka dengan versi lama tetap bisa menyimpan tanpa menghapus video yang tersimpan. |

## D-041 · 2026-10-14 · Kalkulator PKB dan cek biaya pajak memakai mesin kalkulator-pajak; Tarif & Simulasi dipensiunkan

Kalkulator PKB di dashboard dan simulasi di portal sebelumnya menghitung dari pokok PKB yang diketik, dengan tarif dari Tarif & Simulasi (D-017). Keduanya kini memakai mesin perhitungan aplikasi kalkulator-pajak menurut Pergub NTT Nomor 54 Tahun 2026, dengan data kendaraan dari API NJKB BPAD NTT (`api.uptdpenda-kupang.web.id`).

| Hal | Keputusan |
|---|---|
| Mesin | **Disalin persis dan dicatat sumbernya** (keputusan pemilik): `src/lib/tax-calculator.ts` dan `src/types/tax.ts` dari repository kalkulator-pajak, commit `7e90d89`, menjadi `resources/js/pkb/lib/tax-calculator.ts` dan `resources/js/pkb/types/tax.ts` tanpa perubahan. Label rincian dan ringkasan milik PINTU ada di `resources/js/pkb/penetapan.ts`. Bila rumus berubah, kedua berkas disalin ulang dan commit-nya dicatat di `resources/js/pkb/index.ts`. |
| Dashboard | Pegawai cukup mengetik **nomor polisi**; nama pemilik, jenis, NJKB, bobot, jatuh tempo pajak dan STNK, serta warna plat terisi dari API. Bila kendaraan tidak ditemukan atau referensi NJKB-nya tidak bisa dipakai, data diisi manual. Semua pegawai yang login boleh memakainya, seperti sebelumnya (D-010). |
| Portal | Pengunjung memasukkan **nomor polisi dan 5 digit terakhir nomor rangka** (keputusan pemilik) di beranda dan halaman Layanan. Data hanya diberikan bila keduanya cocok; nomor rangka salah, kendaraan tidak dikenal, dan nomor rangka yang tidak tercatat mendapat jawaban yang sama. Hasilnya **rincian lengkap beserta nama pemilik** (keputusan pemilik); nomor rangka dan nomor mesin tidak pernah dikirim ke peramban. Halaman portal tetap HTML statis; skrip cek biaya dibuild Vite dan dimuat lewat `/skrip/cek-pajak`. |
| Fasilitas | **Tembak RU dinonaktifkan** untuk pegawai dan pengunjung (keputusan pemilik). Wilayah terdampak gempa dan mutasi masuk luar daerah tersedia untuk keduanya, dan tanggal bayar dapat dipilih. |
| Perlindungan | **Batas laju dan pesan seragam** (keputusan pemilik): pengunjung 5 pengecekan per menit dan 20 per jam per alamat IP, seluruh portal 30 per menit; pegawai 30 pencarian per menit. Pencarian **tidak dicatat** (keputusan pemilik): nomor polisi, nama pemilik, dan nomor rangka tidak masuk database maupun log; kegagalan hanya mencatat ID permintaan, status, dan kode galat API. |
| Akses API | Token layanan Cloudflare Access milik PINTU sendiri, hanya di server (`NJKB_ACCESS_CLIENT_ID`, `NJKB_ACCESS_CLIENT_SECRET`); token aplikasi lain tidak dipakai. Tanpa token, pencarian menjawab "Pencarian data kendaraan belum dikonfigurasi." |
| Tarif & Simulasi | **Dipensiunkan** (keputusan pemilik), menggantikan D-017. Halaman dashboard, `GET`/`PUT /api/pengaturan/tarif`, dan `GET /api/public/tarif` dihapus; alamat lama `#/tarif` dialihkan ke Kalkulator PKB. Modul `tarif` tidak bisa ditugaskan lagi dan tidak ikut di sesi, sehingga daftar modul D-010 menjadi 17. Nilainya tetap dikenal agar riwayat aktivitas lama terbaca, tetapi riwayat itu tidak lagi tampil di tab Aktivitas. Dokumen tarif yang pernah disimpan dibiarkan di database. |
| Masa pajak | Mesin menghitung masa berjalan pada tahun pembayaran. Bila jatuh tempo pajak berada di tahun sesudah tanggal bayar (PKB tahun itu sudah lunas), PINTU tidak menampilkan rincian dan meminta tanggal bayar di tahun jatuh tempo. |

Belum dibuat: plat kuning (angkutan umum) tetap dihitung di loket; pembayaran lebih awal untuk jatuh tempo di tahun berikutnya belum bisa dihitung mesin.

## D-042 · 2026-10-14 · Jam layanan data kendaraan BPAD

API data kendaraan BPAD hanya aktif sampai pukul 21.00 WITA. Sebelumnya, pencarian di luar jam itu hanya dijawab "Data kendaraan sedang tidak dapat diambil", tanpa penjelasan.

| Hal | Keputusan |
|---|---|
| Keterangan | Portal (di bawah cek biaya pajak di Beranda dan Layanan) dan Kalkulator PKB menyebut bahwa data kendaraan dari BPAD tersedia **sampai pukul 21.00 WITA**. Jam mulai **belum pasti** (keputusan pemilik), jadi tidak disebut. |
| Di luar jam | **Ditolak tanpa menghubungi API** (keputusan pemilik): "Data kendaraan dari BPAD hanya tersedia sampai pukul 21.00 WITA. Coba lagi besok." (`503`). Selama jam mulai belum diketahui, hanya pukul 21.00–24.00 WITA yang dianggap tutup; selepas tengah malam PINTU tetap mencoba API. |
| Pengaturan | `NJKB_SERVICE_CLOSES_AT` (bawaan `21:00`) dan `NJKB_SERVICE_OPENS_AT` (kosong), format `HH:MM` WITA. Bila jam mulai diisi, pesan menjadi "… pukul 07.00–21.00 WITA. Coba lagi pukul 07.00 WITA." dan jam sebelum buka ikut ditolak. Keterangan di portal dan Kalkulator PKB ditulis tetap, jadi perlu disunting bila jamnya berubah. |

## D-043 · 2026-10-15 · Layanan Luar Kantor: tiga unit Samsat Keliling, gerai tetap, dan loket sementara

Samsat Keliling sebelumnya dicatat sebagai satu jadwal mingguan per hari (D-013). Kenyataannya ada tiga unit (Samsat Keliling 01, 02, dan 03) yang bergiliran di lokasi berbeda setiap minggu dan bisa ditugaskan ke acara seperti festival. Di luar kantor juga ada gerai tetap (Samsat Corner, Mall Pelayanan Publik) dan loket musiman atau kondisional (kantor lurah, pasar, pameran).

| Hal | Keputusan |
|---|---|
| Samsat Keliling | **Per tanggal dengan unit** (keputusan pemilik): setiap jadwal punya tanggal, jam, lokasi, dan unit 01–03; harinya mengikuti tanggal dan boleh Senin sampai Minggu. Tombol **Salin ke minggu berikutnya** menyalin jadwal satu minggu (Senin–Minggu) ke hari yang sama minggu berikutnya dalam keadaan **nonaktif**, agar unit dan lokasinya diperiksa dulu; jadwal yang sudah ada untuk unit, tanggal, jam mulai, dan lokasi yang sama tidak disalin lagi. |
| Gerai tetap | Nama, hari buka, jam, dan lokasi; tanggal berlaku mulai dan sampai boleh kosong. Jam yang berbeda per hari dicatat sebagai baris tersendiri. |
| Loket sementara | Nama, tanggal mulai dan selesai (paling lama satu tahun), hari buka di antaranya, jam, dan lokasi. |
| Dashboard | **Satu menu, tiga tab** (keputusan pemilik): menu Samsat Keliling menjadi **Layanan Luar Kantor** dengan tab Samsat Keliling, Gerai Tetap, dan Loket Sementara. Modul `keliling` tetap dipakai, jadi pegawai yang mengelola Samsat Keliling otomatis mengelola ketiganya; nama modulnya kini Layanan Luar Kantor. |
| Portal | **Semua jenis** (keputusan pemilik). Halaman Jadwal: agenda Samsat Keliling dan loket sementara per tanggal dari hari ini sampai Minggu pekan depan, lalu daftar gerai tetap dan loket sementara yang belum berakhir. Tile beranda dan notch kanal menjadi "Di luar kantor hari ini" dan memuat semua yang melayani hari ini (empat pertama, sisanya disebut jumlahnya). |
| Data lama | **Dipindahkan, nonaktif** (keputusan pemilik): jadwal Senin di Bundaran PU menjadi Samsat Keliling 01 pada Senin 19 Oktober 2026, belum tampil di portal sampai diaktifkan. |

Belum dibuat: nomor polisi atau petugas per unit Samsat Keliling, dan penandaan bentrok bila satu unit dijadwalkan di dua tempat pada jam yang sama.

## D-044 · 2026-10-15 · Daftar lokasi dan salin jadwal per hari

Mengisi Lokasi jadwal layanan luar kantor (D-043) satu per satu lambat, dan jadwal yang sama untuk beberapa hari harus diketik ulang.

| Hal | Keputusan |
|---|---|
| Daftar lokasi | Isian Lokasi menjadi pilihan dari **daftar lokasi** (nama dan kecamatan; memilih lokasi sekaligus mengisi kecamatannya). Administrator dan pegawai pengelola Layanan Luar Kantor menambah, mengubah, dan menghapus lokasi di tab **Lokasi**. Daftar ini awalnya berisi lokasi yang sudah dipakai jadwal. Nama lokasi unik tanpa membedakan huruf besar dan kecil. |
| Lokasi baru | Lokasi tetap bisa diketik bila belum ada di daftar, dan **otomatis masuk daftar** (keputusan pemilik) beserta kecamatannya saat jadwal disimpan atau lokasinya diubah. Menyalakan atau mematikan jadwal tidak menambahkan kembali lokasi yang sudah dihapus dari daftar. |
| Mengubah lokasi | **Tidak mengubah jadwal yang sudah ada** (keputusan pemilik): jadwal menyimpan nama lokasinya sendiri, jadi mengubah atau menghapus lokasi di daftar hanya berlaku untuk pengisian berikutnya. |
| Salin per hari | **Dari kartu hari** di pratinjau mingguan (keputusan pemilik): tombol Salin pada hari yang berjadwal menyalin semua jadwal Samsat Keliling hari itu (unit, jam, lokasi, layanan, catatan) ke hari-hari yang dicentang di minggu yang sama dan minggu berikutnya, paling banyak 14 hari sekali salin. Hari yang sudah lewat tidak bisa dipilih. Jadwal yang sudah ada di hari tujuan untuk unit, jam mulai, dan lokasi yang sama dilewati. |
| Status salinan | **Mengikuti jadwal asal** (keputusan pemilik): jadwal aktif disalin aktif dan langsung tampil di portal. Salin minggu (D-043) tetap menyalin dalam keadaan nonaktif. |

## D-045 · 2026-10-15 · Portal beralih ke React bertahap; kata bergilir di judul pembuka

Pemilik ingin kata "SAMSAT" di judul pembuka beranda berganti-ganti (Pajak → STNK → Mutasi → BBN → SAMSAT) dengan daftar kata yang diatur dari dashboard, dan ingin portal publik memakai React seperti dashboard.

| Hal | Keputusan |
|---|---|
| Arsitektur portal | **React bertahap di atas Blade** (keputusan pemilik). Laravel tetap mengirim halaman portal sebagai HTML lengkap, persis seperti tertulis (D-002), sehingga halaman cepat tampil, terbaca mesin pencari dan pratinjau tautan, dan tetap terbaca bila skrip gagal. Bagian interaktif menjadi komponen React + TypeScript yang dibangun Vite seperti dashboard: `resources/js/portal/main.tsx`, dimuat halaman lewat `/skrip/portal`, mengambil alih setiap elemen `[data-react="nama"]` dengan komponen bernama sama. Bagian yang masih diisi `public/assets/js/main.js` dipindahkan satu per satu; tampilan dan CSS portal tetap. Seluruh portal sebagai aplikasi React di peramban tidak dipilih karena halaman kosong sampai skrip dimuat, sedangkan render di server (Inertia/Next.js) dikecualikan D-001. |
| Komponen pertama | Judul pembuka beranda (`resources/js/portal/beranda/judul-pembuka.tsx`). Gayanya dibangun Vite bersama komponennya dengan nama berkas ber-hash, jadi gaya dan skripnya selalu sejalan walau peramban pengunjung masih menyimpan CSS portal yang lama. |
| Gaya animasi | **Gulir vertikal** (keputusan pemilik): kata lama naik keluar, kata baru masuk dari bawah, dan lebar kata menyesuaikan dengan halus. Dibuat dengan React dan CSS tanpa pustaka animasi baru; Framer Motion tidak diperlukan untuk efek ini. |
| Putaran | **Berulang terus, dengan jeda 10 detik di kata judul** (keputusan pemilik, mengganti pilihan awal satu putaran): kata pertama berganti sekitar 1,2 detik setelah judul tampil, setiap kata tampil 1,6 detik, dan setelah kembali ke "SAMSAT" kata itu diam 10 detik sebelum putaran berikutnya. Putaran berhenti sementara selama tab tidak dilihat. Pengunjung yang memilih gerak dikurangi melihat judul diam, dan pembaca layar selalu membaca judul utuh. Bila panjang kata akan memindahkan letak baris judul, kata diberi baris sendiri agar isi di bawahnya tidak melompat. |
| Pengaturan | Dashboard › Beranda › Pembuka, isian **Kata bergilir**: kata ditambah, diubah, diurutkan, dan dihapus (2–8 kata, masing-masing maks. 24 karakter, tanpa kata ganda; huruf besar-kecil tidak dibedakan). Kata di judul yang sama dengan salah satu kata itu (kata utuh, huruf besar-kecil diabaikan) yang bergilir, mulai dari kata sesudahnya dalam daftar, jadi judul wajib memuat salah satunya. Daftar kosong berarti judul tanpa animasi. Isi awal: Pajak, STNK, Mutasi, BBN, SAMSAT. Beranda yang sudah disimpan sebelumnya mendapat daftar itu bila judulnya memuat salah satu katanya. |

Belum dipindahkan ke React: bagian portal lain di `main.js` dan cek pajak (`/skrip/cek-pajak`, D-041).

## D-046 · 2026-10-15 · Logo Ditlantas dan Jasa Raharja di samping lambang pembuka

Samsat adalah kerja sama Pemerintah Provinsi NTT, Ditlantas Polda NTT, dan Jasa Raharja. Pemilik ingin ketiga logonya tampil di pembuka beranda.

| Hal | Keputusan |
|---|---|
| Susunan | **Ditlantas di kiri, Jasa Raharja di kanan** lambang Provinsi NTT (keputusan pemilik). Kedua logo sekitar dua pertiga tinggi lambang NTT, ketiganya rata bawah, dan lambang NTT tetap di tengah. |
| Kemunculan | **Otomatis hanya saat halaman dibuka** (keputusan pemilik): awalnya hanya lambang NTT; sekitar 2,5 detik kemudian kedua logo bergeser keluar dari balik lambang NTT ke tempatnya. |
| Klik lambang | **Lambang NTT menyembunyikan dan memunculkan** kedua logo (keputusan pemilik) dengan gerak yang sama, kembali ke balik lambang. Lambang NTT menjadi tombol yang juga bisa dipakai dengan papan ketik; pembaca layar diberi tahu apakah logo sedang tampil, dan kursor menampilkan petunjuk "Tampilkan/Sembunyikan logo …". Pengunjung yang memilih gerak dikurangi melihat logo muncul dan hilang tanpa gerak. |
| Teknis | Komponen React kedua di portal (D-045): `resources/js/portal/beranda/lambang-pembuka.tsx`. Kedua logo tertulis di HTML di dalam `<template>`, jadi tidak pernah tampil sebelum komponen siap dan tanpa skrip hanya lambang NTT yang tampil (atribut `hidden` tidak cukup karena `base.css` membuat setiap `img` tampil sebagai blok). Gambar dari pemilik dipotong tepi transparannya dan disimpan sebagai `public/assets/img/logo-ditlantas-240.webp` dan `logo-jasa-raharja-240.webp` (tinggi 240 px). Logo tidak diatur dari dashboard. |

## D-047 · 2026-10-15 · Laporan Penerimaan dari berkas STS per loket

Pemilik ingin laporan penerimaan harian dan bulanan setiap loket (Loket SAMSAT, Samsat Keliling 01–03, Samsat Corner, Mall Pelayanan Publik, dan loket luar kantor lain yang sedang melayani) yang diolah dari berkas STS sistem BPAD, dengan saringan per jenis daftar, jenis kendaraan, kelurahan, kecamatan, dan lainnya, serta pilihan untuk menampilkannya di portal sebagai transparansi.

| Hal | Keputusan |
|---|---|
| Menu | **Menu terpisah** (keputusan pemilik): menu **Laporan Penerimaan** dengan modul baru `penerimaan` yang ditugaskan Administrator seperti modul lain (D-010). Statistik Layanan dan dasbor transparansi beranda tidak berubah. |
| Sumber data | Berkas STS harian atau bulanan hasil ekspor sistem BPAD: tabel HTML yang disimpan berekstensi .xls, satu baris per transaksi di bawah dua baris judul. Berkas tidak mencantumkan loket (UPT BAYAR selalu Kota Kupang), jadi pegawai memilih loket asalnya saat mengimpor. Server memeriksa susunan kolom, tanggal, setiap angka rupiah, dan bahwa JUMLAH sama dengan rinciannya; baris yang tidak terbaca menggagalkan seluruh berkas dengan pesan yang menyebut nomor barisnya. Berkas maks. 10 MB (sekitar 17.000 transaksi; berkas bulanan September berisi 7.319 transaksi, 4,3 MB). Impor selalu didahului **pratinjau** (jumlah transaksi, yang baru, yang dilewati, dan rincian per jenis daftar) tanpa menyimpan apa pun. |
| Data yang disimpan | Hanya yang dibutuhkan laporan: tanggal validasi, jenis daftar, jenis kendaraan (JNS_KEND), kepemilikan (pribadi, umum, pemerintah, atau lainnya, dari JENIS KENDARAAN), merek, warna plat, UPT asal, kecamatan, kelurahan, tahun tunggakan, dan dua belas komponen rupiah beserta jumlahnya. **Tidak disimpan**: nama pemilik, nomor polisi, nomorator, tipe kendaraan, nilai jual, jatuh tempo, RT/RW, ubah bentuk, dan berkasnya sendiri. Berkas STS contoh dari pemilik disimpan di `docs/Data Penerimaan/`, yang diabaikan git karena berisi data pribadi wajib pajak. |
| Transaksi ganda | Setiap transaksi diberi **sidik**: HMAC-SHA256 dengan `APP_KEY` atas nomor polisi, tanggal, jenis daftar, dan jumlah, sehingga nomor polisi tidak bisa ditebak kembali. Transaksi yang sudah pernah diimpor atau tercantum dua kali di berkas dilewati, jadi berkas bulanan aman diimpor setelah berkas hariannya; berkas tanpa transaksi baru ditolak. Dua transaksi dengan nomor polisi, tanggal, jenis daftar, dan jumlah yang sama dianggap satu (berkas September memuat satu pasangan seperti itu, Rp0). Mengganti `APP_KEY` mengubah sidik, jadi setelahnya berkas yang sama bisa terimpor ganda: hapus impor lamanya dulu. |
| Loket | Loket SAMSAT (kantor), Samsat Keliling 01–03, serta setiap gerai tetap dan loket sementara di Layanan Luar Kantor (D-043), dikenali dari jenis dan nama tempatnya. Nama loket dicatat pada setiap impor, jadi laporan memakai nama dari impor terakhirnya. |
| Data awal | Berkas bulanan September 2026 adalah **Loket SAMSAT kantor saja** (keputusan pemilik), begitu juga berkas harian 14 Oktober 2026. Keduanya diimpor ke database pengembangan lewat fitur ini. |
| Laporan | Periode **Harian**, **Bulanan**, atau **Rentang** (maks. satu tahun), dengan saringan loket, jenis daftar, jenis kendaraan, kepemilikan, warna plat, merek, asal UPT, kecamatan, kelurahan, dan ada/tanpa tunggakan; semuanya dijumlahkan database. Ringkasan berbentuk bento: total penerimaan dengan susunan komponennya (PKB, BBNKB, denda, tunggakan, opsen), transaksi, kendaraan baru (jenis daftar Baru, seperti contoh tampilan yang disetujui pemilik), pajak provinsi, dan opsen kabupaten/kota. Pembanding: untuk satu hari, hari sebelumnya yang berisi transaksi; untuk bulan penuh, bulan-bulan sebelumnya sebanyak itu; untuk rentang lain, hari-hari tepat sebelumnya sebanyak itu. Kartu per loket menandai loket yang menurut jadwal Layanan Luar Kantor melayani pada hari itu tetapi belum diimpor. Grafik per hari, per jenis daftar, per jenis kendaraan, per kecamatan, dan per asal UPT; tabel rincian per setiap saringan dan per tanggal, yang bisa diekspor sebagai CSV (pemisah titik koma, untuk Excel berbahasa Indonesia). |
| Hapus impor | Menghapus impor menghapus transaksinya dari laporan; berkasnya bisa diimpor ulang. Impor, hapus, dan siapa pelakunya tercatat di log aktivitas. |
| Portal | Dibuat di [D-048](#d-048--2026-10-15--halaman-portal-laporan-penerimaan). **Per impor, setelah diperiksa** (keputusan pemilik): setiap impor punya tanda tampil yang awalnya mati, dan hanya impor yang dinyalakan yang masuk halaman publik. **Sampai kelurahan, angka kecil disamarkan** (keputusan pemilik): kelompok dengan kurang dari 3 transaksi digabung menjadi "Lainnya (disamarkan)" yang berisi sedikitnya 3 transaksi, dihitung di server; hasil saringan dengan kurang dari 3 transaksi tidak ditampilkan. Endpoint publiknya menerima parameter saringan yang terdaftar, pengecualian dari aturan D-004 bahwa API publik menolak query parameter. |

## D-048 · 2026-10-15 · Halaman portal Laporan Penerimaan

Tahap kedua D-047: penerimaan yang sudah diperiksa tampil di portal publik sebagai transparansi kepada masyarakat dan wajib pajak.

| Hal | Keputusan |
|---|---|
| Halaman | `/penerimaan` (Laporan Penerimaan), komponen React portal (D-045) `resources/js/portal/penerimaan/laporan-penerimaan.tsx`. **Menu Informasi dan beranda** (keputusan pemilik): tautan di menu Informasi (desktop dan ponsel, di semua halaman lewat partial header) dan "Lihat laporan penerimaan" di bagian Transparansi beranda (sejak D-055 di dalam tab Penerimaan pajak dasbor beranda, yang meringkas laporan ini). Periode dan saringan tertulis di alamat halaman, jadi laporan yang sedang dilihat bisa dibagikan. |
| Yang tampil | Hanya transaksi dari impor yang kolom **Portal**-nya dinyalakan di Riwayat impor. **Administrator saja** (keputusan pemilik) yang menyalakan dan mematikannya, dengan konfirmasi sebelum menyalakan; perubahannya tercatat di log aktivitas ("menampilkan … di portal", "menyembunyikan … dari portal"). Pegawai lain melihat statusnya. Tanpa periode di alamat, halaman membuka hari terakhir yang sudah tampil. |
| Saringan | **Utama** (keputusan pemilik): periode harian, bulanan, atau rentang (maks. satu tahun, di dalam tanggal yang tersedia) ditambah loket, jenis daftar, jenis kendaraan, kecamatan, dan kelurahan. Daftar pilihan saringan hanya memuat nilai dengan sedikitnya 3 transaksi dalam periode itu. Saringan dan rentang dihapus dari portal di D-049. |
| Angka | **Total, komponen, dan CSV** (keputusan pemilik): total penerimaan dengan susunan komponen (PKB, BBNKB, denda, tunggakan, opsen) dan perubahannya terhadap periode sebelumnya, transaksi, kendaraan baru, pajak provinsi, opsen kabupaten/kota, penerimaan per hari (untuk lebih dari sehari), per loket, jenis daftar, jenis kendaraan, dan kecamatan, serta tabel rincian per saringan dan per tanggal yang bisa diunduh sebagai CSV dari data yang sudah disamarkan. |
| Penyamaran | Dihitung server, sesuai D-047: kelompok dengan kurang dari 3 transaksi digabung menjadi "Lainnya (disamarkan)"; bila gabungan itu masih kurang dari 3 transaksi, kelompok tampil dengan transaksi paling sedikit ikut digabung, agar kelompok kecil tidak bisa dihitung dari total. Hasil saringan atau periode pembanding dengan 1–2 transaksi tidak ditampilkan sama sekali (0 transaksi tetap ditampilkan sebagai nol). Hari dengan kurang dari 3 transaksi tetap terdaftar tanpa angka, dengan aturan gabungan yang sama. |
| API | `GET /api/public/penerimaan` menerima parameter terdaftar saja (`dari`, `sampai`, `loket[]`, `jenisDaftar[]`, `jenisKendaraan[]`, `kecamatan[]`, `kelurahan[]`); parameter lain ditolak `422`. Ini pengecualian dari D-004. Batas 60 permintaan per menit per alamat, di samping batas API publik. |

Batas yang diketahui: ambang 3 transaksi mencegah angka kelompok kecil terbaca langsung, tetapi tidak mencegah pengunjung mengurangkan dua hasil saringan yang hanya berbeda satu transaksi (mis. satu kelurahan seluruhnya dan kelurahan yang sama tanpa satu jenis daftar). Data tidak memuat nama, nomor polisi, atau alamat lengkap, jadi yang bisa ditebak hanya rupiah transaksi bila pengunjung sudah tahu siapa pemiliknya. Bila perlu, saringan dapat dibatasi atau angka dibulatkan. Ditutup di D-049, bersama komponen per kelompok yang ternyata membuka satu transaksi.

## D-049 · 2026-10-15 · Laporan Penerimaan portal sebagai tabel tetap

Memperketat D-048 sebelum data penerimaan naik ke produksi. Pemeriksaan pada data September menemukan dua celah. Pertama, komponen per kelompok: pada 114 dari 175 baris kelurahan yang tampil, kolom BBNKB, denda, atau tunggakan berasal dari 1–2 transaksi saja, sehingga angka satu wajib pajak terbaca langsung di tabel dan CSV. Kedua, selisih: dua permintaan cukup untuk membuka satu transaksi, misalnya rentang 1–15 dikurangi 1–14 pada saringan yang sama, nilai saringan di luar daftar pilihan, atau kelompok "Lainnya" di satu saringan dikurangi sel yang tampil di saringan lain.

| Hal | Keputusan |
|---|---|
| Per kelompok | **Transaksi dan jumlah saja** (keputusan pemilik) di kartu, tabel rincian, dan CSV. Susunan komponen (PKB, BBNKB, denda, tunggakan, opsen) serta kotak Kendaraan baru, Pajak provinsi, dan Opsen hanya untuk seluruh periode (semua loket). |
| Periode | **Tabel tetap** (keputusan pemilik). *Harian*: total dan komponennya saja, dengan tautan ke laporan bulanannya. *Bulanan*: total, komponen, batang per hari, kartu per loket/jenis daftar/jenis kendaraan/kecamatan, dan tabel rincian per jenis daftar, jenis kendaraan, loket, kecamatan, kelurahan, dan tanggal. Saringan dan rentang dihapus dari portal; dashboard tidak berubah. |
| Penyamaran | Sama seperti D-048 (kelompok di bawah 3 transaksi digabung, ditambah kelompok terkecil bila gabungannya masih di bawah 3), dengan dua tambahan agar tabel yang saling terkait tidak membuka satu sama lain. **Kelurahan per kecamatan**: semua kelurahan di kecamatan yang disamarkan ikut disamarkan, dan di kecamatan lain kelurahan yang disamarkan berjumlah sedikitnya 3 transaksi, agar total kecamatan dikurangi kelurahan yang tampil tidak membuka satu kelurahan. **Hari dan bulan**: hari yang disamarkan di batang bulanan (termasuk yang ikut digabung) juga disamarkan di laporan hariannya, begitu pula sebagai periode pembanding. Bulan dengan 1–2 transaksi tidak ditampilkan. |
| API | `GET /api/public/penerimaan` hanya menerima `tanggal=YYYY-MM-DD` atau `bulan=YYYY-MM` (tidak keduanya); tanpa parameter, hari terakhir yang tampil. Parameter lain, termasuk `dari`, `sampai`, dan saringan D-048, ditolak `422`. Respons tidak lagi memuat `pilihan`; ada `periode` (`harian`/`bulanan`) dan `kendaraanBaru`. Halaman menulis `?tanggal=` atau `?bulan=` di alamatnya. |

Yang masih diterima: komponen seluruh hari (mis. denda hari itu dari satu transaksi) dan kendaraan baru seluruh hari tetap tampil tanpa keterangan lain selain tanggal. Jumlah sebuah kelompok yang didominasi satu transaksi besar (mis. satu kendaraan baru di kelurahan kecil) tetap terlihat sebagai jumlah kelompok, tidak terpisah.

## D-050 · 2026-10-15 · Papan Layanan berlaku setiap hari

Isi papan layanan jarang berubah, jadi pemilik tidak ingin menyimpannya ulang setiap hari kerja. Ini mengganti aturan "hanya pada hari pembaruan" dari D-015.

| Hal | Keputusan |
|---|---|
| Isi | **Semua isi** (keputusan pemilik): nomor antrean, layanannya, perkiraan waktu tunggu, selisih dari kemarin, status loket, dan catatan dari penyimpanan terakhir tampil di beranda setiap hari sampai diubah. |
| Loket tutup | **Ikuti Jam Pelayanan** (keputusan pemilik): selama loket tutup menurut jam loket dan penyesuaiannya (di luar jam, hari tutup, atau penyesuaian tutup), semua loket tampil *Tutup* dan waktu tunggu serta selisihnya tidak ditampilkan. Bila jam loket belum diatur atau gagal dimuat, papan tampil apa adanya. Papan mengikuti buka-tutup dan pergantian hari selama halaman terbuka (diperiksa setiap 30 detik). |
| Label waktu | **"Diperbarui hari ini"** (keputusan pemilik) pada hari selain hari penyimpanan, tanpa tanggal penyimpanan terakhir; pada hari penyimpanan tetap "Diperbarui HH.MM WITA". Tanggal penyimpanan terakhir tetap terlihat di dashboard. |
| Server | Tidak berubah: `GET /api/public/papan` tetap memberikan `diperbarui` dari penyimpanan terakhir. |

Jadwal Pemutakhiran di dashboard masih bisa mencatat papan sebagai "Setiap hari kerja"; frekuensinya diubah pengelola bila perlu.

## D-051 · 2026-10-15 · Antrean Online: rencana dan keputusan awal

Pemilik meminta Sistem Antrean Online (Queue Management System) di bagian Layanan, berdasarkan flowchart pemilik: nomor antrean tercetak menggantikan nomor tulis tangan, satu nomor mengikuti berkas dari Meja Informasi sampai Loket 2 – Penyerahan, dan wajib pajak melacak tahap berkasnya lewat QR. Rencana lengkap: [`docs/rencana-antrean-online.md`](rencana-antrean-online.md), disetujui pemilik. **Irisan 1–4 dibangun**: Meja Informasi, cetak dua lembar, halaman lacak, pengaturan layanan; loket pemanggil, layar TV dengan suara, dan pengaturan loket serta layar; pos proses (Penetapan, Server, Pelunasan, Cetak SKPD, Cetak STNK) dengan pindai QR, serta halaman lacak dengan jam setiap tahap dan posisi antrean; arsip dengan riwayat dan ringkasan dengan CSV. Berikutnya uji coba di loket, lalu Irisan 5–6.

| Hal | Keputusan |
|---|---|
| Kode layanan | Sesuai flowchart (keputusan pemilik): **A** Registrasi Ulang/Perpanjangan STNK 5 Tahun dan Duplikat STNK, **B** Pengesahan/Pajak Tahunan, **C** BBN/Mutasi, ditambah **D** Pembayaran Online. Urutan tahap per layanan bisa diatur Administrator; isi awal: A dan B melewati semua tahap, C tanpa Cetak STNK, D tanpa Cetak SKPD. |
| Nomor | **A-001** (pemilik): huruf layanan, tanda hubung, tiga digit; mulai dari 001 setiap hari (WITA). Dicetak **hanya di Meja Informasi** setelah berkas lengkap (pemilik), dua lembar di **printer thermal 80 mm** (pemilik): untuk WP dan untuk berkas, keduanya ber-QR. |
| Status tahap | **Pindai QR dulu, API Samsat Online menyusul** (pemilik). Setiap pos menandai tahapnya, tetapi memindai **tidak wajib** (pemilik): bisa juga dipilih dari daftar, dan tahap yang terlewat selesai otomatis saat pos berikutnya menandai. |
| Pemanggilan | **Layar TV + suara** (pemilik) di ruang tunggu, hanya di perangkat yang didaftarkan Administrator. |
| Akun | **Akun pribadi** per petugas (pemilik), modul dashboard `antrean`. |
| Nomor polisi | Disimpan **tanpa batas waktu** untuk mencari berkas atau nomor yang tercecer; hanya Administrator yang menghapusnya. Tampil **apa adanya** di halaman lacak dan layar TV (pemilik). Halaman lacak hanya bisa dibuka dengan kode acak dari QR, tanpa pencarian publik. |
| Lintas hari | Tiket yang belum selesai **tetap aktif dengan nomor lamanya**, ditandai tanggalnya (pemilik). |
| Papan Layanan | **Tetap manual** (D-050) sampai antrean benar-benar berjalan, lalu diambil dari antrean (pemilik). |
| Mulai pakai | **Uji coba paralel** dengan nomor manual (pemilik), setelah PINTU di-deploy ke Laravel Cloud. |
| Pustaka QR | **uqr** 0.1.3 (keputusan pemilik): tanpa dependensi, menghasilkan SVG di peramban, hanya dimuat halaman Meja Informasi. |
| Loket awal (Irisan 2) | **Satu loket per tahap** (pemilik): Loket 1 – Pendaftaran, Loket Kwitansi, Loket 2 – Penyerahan, masing-masing semua layanan; diatur Administrator di Pengaturan Antrean. |
| Suara panggilan | **Tanpa nomor polisi** (pemilik): bunyi tanda lalu "Nomor antrean A, nol nol satu. Silakan ke Loket kwitansi.", dengan nama loket yang dibacakan sesuai pengaturannya. Nomor polisi tetap tampil di layar. |
| Panggil berikutnya | **Nomor yang masih dipanggil otomatis ditunda** (pemilik) saat loketnya memanggil nomor lain. Nomor yang ditunda bisa dipanggil lagi kapan saja. |
| Panggil nomor tertentu | Loket bisa memanggil nomor yang diketik, nomor polisi, atau QR berkas yang dipindai; tahap sebelum loket itu yang belum ditandai dianggap selesai dan dicatat otomatis (rencana §4.3). |
| Layar TV | Dipasang Administrator dengan tautan sekali tampil (`/layar#k=…`); perangkat menyimpan tokennya, server hanya menyimpan hash-nya, dan layar bisa dicabut. |
| Pindai di pos (Irisan 3) | **Langsung ditandai selesai** (pemilik): satu pindaian (pemindai USB, kamera, atau nomor yang diketik) langsung menandai tahap pos itu, lalu tampil konfirmasi singkat dengan tombol **Urungkan**. Urungkan hanya berlaku untuk tanda petugas itu sendiri dan selama nomornya belum berubah lagi; nomor kembali persis ke keadaan sebelumnya. |
| Perubahan data | **Pos Server tersendiri** (pemilik): Penetapan menandai **Perlu perubahan data**, nomor masuk daftar pos Server, dan setelah Server menandai selesai berkas kembali ke daftar Penetapan di urutan lamanya. Selama menunggu perubahan data, nomor tidak bisa dipanggil atau ditandai di tahap sesudahnya. |
| Hapus dari arsip (Irisan 4) | **Tiket seluruhnya** (pemilik): Administrator menghapus tiket beserta nomor polisi dan riwayatnya secara permanen, dan ringkasan ikut berkurang. Hanya tiket yang sudah selesai atau dibatalkan dan dicetak sebelum hari ini: nomor yang masih berjalan dibatalkan dulu, dan nomor hari ini tetap ada agar cetakan berikutnya tidak memakai nomor yang sama. Penghapusan dicatat di log aktivitas dengan nomor dan tanggal, tanpa nomor polisi. |
| Arsip dan ringkasan (Irisan 4) | Dibuka **semua petugas Antrean** (pemilik): mencari menurut nomor, sebagian nomor polisi, QR, tanggal, layanan, status, atau tahap (200 nomor terbaru per pencarian), dan riwayat lengkap dengan petugas dan loketnya. Ringkasan **harian atau rentang sampai 31 hari** (pemilik), dihitung pada tanggal cetak; waktu tunggu dan lama per tahap ditulis sebagai nilai tengah dan hanya diukur dari saat yang tercatat. |
| CSV ringkasan (Irisan 4) | **Ringkasan + daftar tiket** (pemilik): per layanan, per tahap, per hari, lalu satu baris per nomor dengan nomor polisi, status, tahap, dan waktunya. Halaman mengingatkan bahwa berkasnya berisi data pribadi. |
| Halaman lacak (Irisan 3) | Menampilkan jam selesai setiap tahap dan banyaknya nomor yang menunggu di depan pada loket pemanggil (semua layanan dihitung bersama, sesuai satu loket per tahap); tetap tanpa nama petugas. |

Belum diputuskan: akun petugas non-UPTD (D-007 mewajibkan NIP), antrean prioritas, penjelasan di Kebijakan Privasi, dan waktu deploy (rencana §9).

## D-052 · 2026-10-16 · Pemetaan PKB dari berkas Data Potensi

Pemilik meminta fitur **Pemetaan Pajak Kendaraan Bermotor (PKB) Kota Kupang**: pendataan, analisis, dan visualisasi potensi serta kepatuhan pembayaran PKB per wilayah, untuk mengetahui potensi pendapatan, memetakan tunggakan, serta membantu target PAD dan strategi penagihan. Sumbernya berkas **Data Potensi** dari sistem BPAD (contoh awal: jatuh tempo Oktober, tanggal penetapan 16/10/2026, 28.725 kendaraan plat putih Kota Kupang, 43 kolom termasuk identitas pemilik). Data dikelola dan ditampilkan di dashboard dan di portal publik. **Irisan 1 dibangun**: modul dashboard, impor, peta skematis, ringkasan, dan sasaran penagihan. **Irisan 2 dibangun**: halaman portal *Pemetaan PKB* (`/pemetaan`) dan sakelar Administrator "Tampil di portal".

| Hal | Keputusan |
|---|---|
| Data yang disimpan | **Dashboard menyimpan semua kolom termasuk identitas** (nama, KTP, telepon, alamat, nomor polisi, mesin, rangka) untuk sasaran penagihan; **portal publik hanya angka gabungan** per kecamatan dan kelurahan (pemilik). KTP dan telepon disimpan terenkripsi; hanya Administrator dan pegawai bermodul `pemetaan` yang melihatnya, dan hanya Administrator yang menghapus data. |
| Periode | **Impor baru menggantikan data lama**, tanpa riwayat (pemilik). Data berikutnya berupa data master setahun (Januari–Desember); ringkasan bisa disaring per bulan jatuh tempo (dari kolom SD NOTICE). |
| Peta | Awalnya **peta skematis, peta batas resmi menyusul** (pemilik): OpenStreetMap tidak memuat batas kecamatan/kelurahan Kota Kupang. Sejak 2026-10-16 **peta memakai batas kecamatan sungguhan dari geoportal** (pemilik): geodatabase batas desa/kelurahan edisi Mei 2021 (BIG bersama pemerintah daerah) di layanan `Batas_Administrasi` geoportal Kementerian Pertanian, diunduh sekali sebagai GeoJSON dan diubah oleh `scripts/batas-kecamatan.mjs` menjadi jalur SVG di `resources/js/portal/pemetaan/batas-kecamatan.ts` (±3 KB, dipakai portal dan dashboard; tanpa peta dasar atau permintaan ke pihak luar). Batasnya sudah disederhanakan oleh layanan itu (15–43 titik per kecamatan, ±150 km²) dan belum mengikuti batas kota yang definitif (Permendagri 46/2022); sumbernya disebut di halaman. Layanan tidak mencantumkan lisensi. Batas **kelurahan** menunggu berkas resmi (Bappeda Kota Kupang, BIG, atau BPS); geoportal BPS tidak dapat dihubungi saat diperiksa. Pada hari yang sama pemilik memberikan berkas **Batas Desa/Kelurahan BIG skala 1:10.000 edisi 27 Desember 2022** (shapefile `ADMINISTRASI_AR_DESAKEL`, kemasan LapakGIS, WGS 1984; paket RBI 2018 yang lain hanya memuat potongan utara kota). Isinya ke-51 kelurahan Kota Kupang dengan kode Kemendagri, sama dengan daftar resmi (lima ejaan dipetakan: Bakunase Dua, Naikoten Satu/Dua, Batu Plat, Lai Lai Bisi Kopan), total ±160 km². Atas usul yang disetujui pemilik, peta kini memakai berkas itu: `scripts/batas-wilayah.mjs` memecah batas menjadi ruas bersama, menyederhanakan setiap ruas sekali (23.306 → 2.226 titik), lalu merangkai kelurahan dan kecamatan dari ruas yang sama (tanpa celah) ke `resources/js/portal/pemetaan/batas-wilayah.ts` (±18 KB gzip). Peta menampilkan kecamatan, dan **kecamatan yang dipilih diperbesar dengan kelurahannya** diwarnai menurut ukuran yang sama (kecamatan lain redup dan bisa dipilih); label kelurahan yang akan saling menimpa dilewati (nama tetap di keterangan dan tabel). Berkas asli tidak masuk repo. README kemasan tidak memuat ketentuan penggunaan; pemilik memastikan (2026-10-16) bahwa ketentuan LapakGIS membolehkan batas wilayah turunannya ditampilkan di situs. Sumber BIG disebut di halaman. |
| Portal (Irisan 2) | **Kecamatan dan kelurahan** (pemilik): jumlah kendaraan per jenis dan status kepatuhan, serta rupiah potensi dan tunggakan; kelompok di bawah 3 kendaraan disamarkan, dan baru tampil setelah Administrator menyalakannya (seperti D-048). Tanpa nama, nomor polisi, alamat, atau identitas lain. |
| Jenis kendaraan di portal | Awalnya **roda tiga digabung dengan roda empat** per kecamatan dan kelurahan ("Roda tiga+"), karena roda tiga sangat sedikit (37 kendaraan). Sejak 2026-10-16 **roda tiga dan roda empat dipisah di semua tabel, dashboard dan portal** (pemilik), dengan konsekuensi yang disetujui: pada data 16/10/2026 semua angka kota dan kecamatan tampil, tetapi di tingkat kelurahan 12 angka roda tiga (10 di antaranya 1–2 kendaraan), 14 angka roda empat, dan 2 angka roda dua disamarkan. Panel kota juga menyebut **jenis tidak tercatat** (kendaraan tanpa jenis di berkas, 14 pada data 16/10/2026), agar ketiga jenis roda dan angka itu berjumlah sama dengan total (pemilik); angka itu hanya disembunyikan bila 1–2 dan tidak ikut penyamaran lanjutan karena memang selisih angka yang sudah tampil. |
| Periode di portal | **Seluruh data saja** (pemilik): tanpa saringan bulan jatuh tempo; parameter kueri ditolak. |
| Letak di portal | **Menu Informasi dan beranda** (pemilik): tautan "Pemetaan PKB" di menu Informasi (desktop dan seluler) dan "Lihat pemetaan PKB per wilayah" di bagian Transparansi beranda, di samping Laporan Penerimaan (sejak D-055 di dalam tab Pemetaan PKB dasbor beranda, yang meringkas pemetaan ini). |
| Daftar sasaran penagihan | **Mulai dari kendaraan yang belum taat** (pemilik, 2026-10-16): saringan Kepatuhan diberi pilihan *Belum taat* (semua selain Taat) yang terpilih sejak awal dan setelah *Atur ulang*; *Semua status* dan tiap status tetap bisa dipilih. Yang disaring statusnya, bukan tagihan Rp0, karena 465 kendaraan *Menunggak 4 Tahun*/*4 Tahun 11 Bulan* di data 16/10/2026 bertagihan Rp0 (NJKB kosong di berkas) dan tetap sasaran penagihan. Pesan hasil kosong memberi tahu bahwa kendaraan taat tidak termasuk. |
| Kolom tabel | **Administrator memilih kolom** tabel pemetaan (pemilik): satu pilihan untuk tabel kecamatan dan kelurahan yang berlaku di dashboard dan portal (Kendaraan, Roda dua, Roda tiga, Roda empat+, Taat, Belum bayar, Menunggak, % menunggak, Potensi pokok, Tagihan, Tunggakan), dan pilihan tersendiri untuk daftar Sasaran penagihan yang hanya ada di dashboard. Pilihan tersimpan di server (pengaturan `pemetaan`) sehingga tetap sama setelah muat ulang atau masuk lagi; nama wilayah dan nomor polisi selalu tampil; sedikitnya satu kolom per tabel. Portal tetap menerima angka semua kolom wilayah (peta dan panel kota memakainya); pilihan hanya mengatur tabel. |
| Tampil di portal | Sakelar **khusus Administrator** di halaman dashboard; menyalakannya meminta konfirmasi, mematikannya tidak. Impor baru selalu mulai tersembunyi, jadi data baru perlu diperiksa dan dinyalakan lagi. Dicatat di log aktivitas ("menampilkan … di portal", "menyembunyikan … dari portal"). |
| Penyamaran | Setiap angka yang menggambarkan 1–2 kendaraan disamarkan (rupiah menurut banyaknya kendaraan di baliknya: potensi = kendaraan, tagihan = kendaraan belum taat, tunggakan = kendaraan menunggak). Lalu angka lain ikut disamarkan agar yang disamarkan tidak bisa dihitung dari total: (1) bila satu angka saja yang disamarkan di antara sebuah total dan bagian-bagiannya (kendaraan suatu wilayah = roda dua + roda tiga+, = taat + belum bayar + menunggak; roda tiga+ kota = roda tiga + roda empat; angka kecamatan = jumlah kelurahannya; angka kota = jumlah kecamatannya), angka bagian terkecil yang tampil ikut disamarkan, atau totalnya bila tak ada lagi; (2) lalu semua total digabung sebagai persamaan (eliminasi bilangan bulat), dan selama satu angka tersamar 1–2 kendaraan, atau dua angka tersamar bersama di bawah 3 kendaraan, masih bisa dihitung, satu angka lagi di dekatnya disamarkan (yang terkecil yang memutusnya, angka nol paling akhir). Kendaraan tanpa jenis tercatat dan dengan teks kepatuhan lain dianggap diketahui pengunjung (perkiraan terburuk). Diuji dengan audit acak terpisah (3.000+ susunan data rekaan, 0 kebocoran); pada data 16 Okt 2026 tidak ada angka yang disamarkan. |
| Kecamatan | Ditentukan dari **kelurahan menurut daftar resmi** 6 kecamatan dan 51 kelurahan Kota Kupang, karena kolom KECAMATAN di berkas mencatat beberapa kecamatan untuk kelurahan yang sama (99,3% baris tetap cocok; selisih terbesar Bakunase II). Baris tanpa kelurahan yang dikenal dihitung sebagai "Tanpa kelurahan". Batas BIG 2022 menegaskan daftar ini: Bakunase II (Bakunase Dua, 53.71.05.1002) masuk **Kota Raja**, jadi kolom KECAMATAN berkas Data Potensi yang sering mencatat Oebobo memang keliru. |
| Kepatuhan | Dikelompokkan dari kolom KEPATUHAN: **Taat**, **Jatuh tempo hari ini**, **Terlambat < 1 tahun**, **Menunggak ≥ 1 tahun** (lama dari kolom TAHUN TUNGGAK; 4 berarti 4 tahun atau lebih), Lainnya. **Tagihan** = pokok dan denda berjalan ditambah pokok dan denda tunggakan, untuk kendaraan yang belum taat. Roda: sepeda motor = roda dua, jenis berakhiran R3 = roda tiga, lainnya roda empat atau lebih. |
| Impor | Berkas dibaca **di peramban** lalu dikirim per 1.000 baris, agar berkas besar (data setahun) tidak terbentur batas unggah dan memori server; impor yang tidak selesai dibuang saat impor berikutnya dimulai. Saat selesai, kendaraan dihitung sekali ke tabel ringkasan agar peta dan tabel tetap cepat. |

## D-053 · 2026-10-16 · Urutan "Di luar kantor hari ini" di papan Beranda

Pemilik ingin kartu papan layanan di beranda setinggi isinya, tanpa ruang kosong di bawahnya, dan bisa menentukan layanan luar kantor mana yang tampil paling atas, kedua, dan seterusnya.

| Hal | Keputusan |
|---|---|
| Tinggi kartu | Di layar lebar (di atas 980 px) kartu Nomor antrean, Perkiraan waktu tunggu, dan Pengumuman setinggi isinya (permintaan pemilik); dua kartu pada baris yang sama tetap sama tinggi. Kartu *Di luar kantor hari ini* mengikuti tinggi dua baris di kirinya dan menampilkan dari atas sebanyak yang muat; sisanya diringkas menjadi "+N Lokasi lain hari ini". Di layar 980 px ke bawah tetap 4 lokasi pertama. |
| Tempat mengatur | **Per tanggal di halaman Layanan Luar Kantor** (pemilik): tombol *Atur urutan papan* di kartu hari (hari ini dan sesudahnya, bila ada dua layanan atau lebih) pada pratinjau minggu. Daftarnya memuat semua jenis yang dilayani hari itu (Samsat Keliling, gerai, loket sementara), termasuk yang belum aktif, dengan tombol naik dan turun. Diatur pengelola Layanan Luar Kantor dan Administrator. |
| Aturan urutan | Urutan disimpan per tanggal (tabel `service_board_orders`). Tanggal yang belum diatur tetap urut jam; entri yang ditambahkan sesudah diatur tampil di bawah, urut jam. Urutan yang sama dengan urut jam disimpan sebagai "kembali urut jam". Salin hari dan salin minggu tidak menyalin urutan. |
| Cakupan | **Hanya papan Beranda** (pemilik); halaman Jadwal tetap urut jam. `GET /api/public/keliling` memberi `urutanPapan` pada setiap jadwal tanpa mengubah urutan `jadwal`. |

## D-054 · 2026-10-17 · Halaman Aplikasi & Situs

Pemilik meminta satu halaman portal yang memuat aplikasi dan situs milik atau buatan BPAD/UPTD, beserta menu di dashboard (bagian Alat Bantu) untuk menambah, mengubah, dan menghapusnya, dengan logo dari sumber masing-masing aplikasi.

| Hal | Keputusan |
|---|---|
| Daftar awal | Lima aplikasi dari pemilik, sesuai urutannya: **PINTU** (situs ini), **PRO NTT** (bayar Pajak Tahunan online, hanya di Google Play; pengembang BAPENDA NTT), **SIKAP** (Sistem Informasi Kepegawaian dan Arsip Pegawai UPTD), **SIPAS** (Sistem Informasi Pengelolaan Administrasi SKPD), dan **Tilang / Razia** (Razia SAMSAT Kota Kupang). Keterangan singkat disusun dari nama, keterangan di situs masing-masing, dan permintaan pemilik; SIKAP, SIPAS, dan Tilang / Razia ditandai "Khusus pegawai/petugas" di keterangannya. |
| Pengelola | **Modul baru *Aplikasi & Situs*** (pemilik): Administrator selalu bisa, dan Administrator dapat menugaskannya ke pegawai di Akun Pegawai. Menu ada di bagian Alat Bantu dan hanya tampil bagi yang berhak. Tanpa alur publikasi: perubahan langsung tampil di portal. |
| Letak di portal | **Menu Layanan** (pemilik): "Aplikasi & Situs" di `/aplikasi`, desktop dan seluler, bersama Persyaratan Layanan, Alur Non-Tunai, dan Simulasi Pajak. |
| Susunan halaman | **Satu daftar** (pemilik) sesuai urutan di dashboard, tanpa pembagian masyarakat/pegawai. Tiap kartu: logo, nama, nama lengkap, keterangan, jenis (situs web atau Android · Google Play), pengelola (BPAD Provinsi NTT atau UPTD Kota Kupang), dan tombol "Buka situs", "Unduh di Google Play", atau "Buka halaman" (halaman portal). Tautan ke luar portal dibuka di tab baru. Catatan di bawah daftar mengingatkan agar waspada terhadap aplikasi atau situs tiruan. |
| Logo | **Diunduh dari sumber masing-masing** (pemilik mengizinkan): ikon PRO NTT dari Google Play, logo SIKAP dari `raw.githubusercontent.com/ElwinMusadi/app-assets`, logo SIPAS dari situs SIPAS, dan ikon Razia SAMSAT dari situs Tilang; PINTU memakai ikonnya sendiri. Semua dikecilkan menjadi 240 × 240 px WebP di `public/assets/img/aplikasi/`. SIKAP, SIPAS, dan Tilang / Razia sama-sama berlogo Pemprov NTT, sesuai sumbernya. Logo baru diunggah lewat dashboard (tujuan `aplikasi`); logo bawaan tetap di situs bila aplikasinya dihapus atau logonya diganti. |
| Aturan tautan | Alamat lengkap atau halaman portal; aplikasi Android harus ditautkan ke halamannya di Google Play, karena portal menawarkannya sebagai "Unduh di Google Play". |

## D-055 · 2026-10-17 · Ringkasan penerimaan dan pemetaan di dasbor beranda

Pemilik ingin bagian *Transparansi layanan* di beranda menampilkan ringkasan Laporan Penerimaan dan Pemetaan PKB secara otomatis, tanpa dua tautan terpisah di bawah judulnya; pengunjung yang ingin rinciannya baru membuka halaman masing-masing.

| Hal | Keputusan |
|---|---|
| Penerimaan pajak | Tab *Penerimaan pajak* **diisi otomatis dari Laporan Penerimaan yang ditampilkan di portal**, untuk **tahun berjalan** (pemilik): Total penerimaan (dengan banyaknya transaksi), PKB, BBNKB, dan Opsen kabupaten/kota (dengan persennya dari total), serta grafik penerimaan per bulan Januari–Desember tahun data terakhir. Tanpa persen naik/turun, karena bulan berjalan belum lengkap. Data dari `GET /api/public/penerimaan/ringkasan`: bulan dengan 1–2 transaksi disamarkan seperti di Laporan Penerimaan dan tidak ikut dijumlah, dan bulan di luar tanggal data tampil "belum ada data". |
| Pemetaan PKB | Tab baru *Pemetaan PKB* setelah Penerimaan pajak, **empat angka dan grafik kecamatan** (pemilik): Objek pajak, Taat, dan Menunggak ≥ 1 tahun (dengan persennya), Tagihan belum terbayar, serta grafik persen menunggak per kecamatan, dari `GET /api/public/pemetaan`. |
| Tautan | "Lihat laporan penerimaan" dan "Lihat pemetaan PKB per wilayah" di bawah judul bagian dihapus (pemilik); masing-masing kini tampil di dalam panel dasbor pada tabnya. |
| Bila belum tampil | Kedua tab disembunyikan selama datanya belum dinyalakan untuk portal (sakelar Administrator D-048 dan D-052). Tab lain tetap dari Statistik Layanan dan hanya berisi angka bila terverifikasi pimpinan (D-018). |
| Statistik Layanan | Kategori Penerimaan pajak **dihapus dari editor** (pemilik): Statistik Layanan tinggal tiga kategori (Transaksi pelayanan, Samsat Keliling, Kepuasan masyarakat). Dokumen yang tersimpan sebelumnya dibaca tanpa kategori itu, dan kategori itu hilang saat disimpan lagi. |

## D-056 · 2026-10-17 · Kategori Statistik Layanan bisa disembunyikan dari portal

Pemilik ingin ketiga kategori Statistik Layanan (Transaksi pelayanan, Samsat Keliling, Kepuasan masyarakat) masing-masing punya pilihan tampil di portal atau tidak.

| Hal | Keputusan |
|---|---|
| Sakelar | Setiap kategori di dashboard Statistik Layanan punya sakelar **Tampil di portal** (permintaan pemilik), tersimpan di dokumen `statistik` sebagai `tampil` per kategori. Bawaannya menyala, dan dokumen yang disimpan sebelumnya dibaca dengan semua kategori tampil, jadi beranda tidak berubah sampai sakelar dimatikan. |
| Di beranda | Kategori yang dimatikan **tidak muncul sebagai tab** dasbor transparansi beranda, apa pun status datanya (Ilustrasi atau Terverifikasi); isinya tetap tersimpan di dashboard. Bila tab yang terpilih tersembunyi, beranda memilih tab pertama yang tampil; bila tidak ada tab sama sekali, panel menyatakan statistik layanan belum diterbitkan. `GET /api/public/statistik` menyebut kategori yang tampil di `kategori` dan hanya mengirim angka kategori itu. |
| Status data | Status Ilustrasi/Terverifikasi (D-018) tetap berlaku untuk semua kategori: kategori yang tampil tetap menunggu status Terverifikasi sebelum angkanya terbit. |

## D-057 · 2026-10-17 · Tema portal dan dashboard: rencana dan keputusan awal

Pemilik ingin Administrator dapat mengatur tema (jenis huruf, warna utama, skala tampilan, dan pengaturan tampilan lain yang lazim) dari menu Pengaturan di dashboard, agar tampilan tidak itu-itu saja. Rencananya di [`docs/rencana-tema-situs.md`](rencana-tema-situs.md). **Irisan 1 (fondasi) dibangun** pada 17 Oktober 2026, tanpa perubahan tampilan: warna dan sudut yang ditulis langsung menjadi variabel bernilai sama (dipakai dengan nilai cadangan karena CSS portal tidak berversi), daun logo punya variabel sendiri agar tetap biru, huruf dan ukuran dashboard bisa ditimpa, serta `/tema-portal.css` dan `/tema-dashboard.css` (masih kosong) dimuat di semua halaman. **Irisan 2 dibangun** pada hari yang sama: tab *Tampilan* di Pengaturan dengan bagian Portal dan Dashboard (enam tema siap pakai, warna utama dengan turunan yang dijaga kontrasnya, nuansa latar, huruf teks dan judul, kelengkungan sudut, pratinjau langsung, kembali ke bawaan, samakan dengan portal). Seperti Pengaturan Situs (D-026), tema bukan milik modul sehingga tidak tercatat di log aktivitas per modul; riwayat tema mencatat siapa dan kapan. **Irisan 3 dibangun** pada hari yang sama: skala tampilan 90–120% (portal lewat `zoom`, kecuali layar TV antrean yang sudah memenuhi layarnya; dashboard lewat ukuran huruf dasar, untuk 110–120% hanya di layar mulai 768 px karena beberapa halaman dashboard melebar di ponsel; kemudian mulai 1024 px, lihat baris *Skala dashboard*), tautan *Pratinjau di portal* yang menampilkan tema belum disimpan hanya di peramban yang membukanya selama 30 menit, dan riwayat lima tema terakhir dengan penyimpannya. **Irisan 4 dibangun** pada hari yang sama: mode gelap portal dan warna bilah peramban yang mengikuti latar portal.

| Hal | Keputusan |
|---|---|
| Cakupan | **Portal dan dashboard punya tema masing-masing** (pemilik), diatur dari satu tab *Tampilan* di Pengaturan, khusus Administrator. Absensi mengikuti dashboard; 404, Login Pegawai, Aktivasi, dan layar TV antrean mengikuti portal. Mode gelap dashboard tetap pilihan tiap pegawai. |
| Pengaturan | Tema siap pakai (PINTU bawaan dan beberapa lainnya), warna aksen bebas dengan turunan warna yang dipaksa memenuhi kontras WCAG AA, nuansa latar, huruf teks dan judul, skala tampilan, dan kelengkungan sudut; tombol kembali ke bawaan. Warna status (taat, menunggak, peringatan) tidak ikut aksen. |
| Skala portal | **`zoom` per halaman** (pemilik), 90–120%, karena CSS portal memakai px; dashboard memakai ukuran huruf dasar (rem). |
| Skala dashboard | **110% dan 120% baru berlaku di layar mulai 1024 px** (pemilik, 17 Oktober 2026, sesudah Irisan 3; semula mulai 768 px). Di 768 px dengan sidebar terbuka, lebar isi pada 120% tinggal ±400 px: 110% membuat 7 halaman dan 120% membuat 12 halaman lebih lebar dari layar, dan judul header tinggal beberapa huruf. Di 768–1023 px dashboard tetap 100%. Header juga meringkas diri menurut lebarnya sendiri (kotak Cari lalu *Lihat di portal* menjadi ikon), karena di 1024 px judul terpotong sampai 111 px (110%) dan 29 px (120%); di 1280 px ke atas tampilannya tetap. Skala 90% tetap berlaku di semua layar. |
| Logo | **Tetap biru PINTU** (pemilik) di header, footer, dan favicon. |
| Huruf | **Daftar terkurasi yang di-host sendiri** (pemilik), berlisensi OFL, bukan Google Fonts. |
| Mode gelap portal (Irisan 4) | **Ditetapkan Administrator** (pemilik): portal terang atau gelap untuk semua pengunjung, apa pun pengaturan perangkatnya, tanpa tombol di portal. Mode gelap dashboard tetap pilihan tiap pegawai. Panel gelap portal (papan layanan, dasbor beranda, layar TV) tetap gelap di kedua mode. |
| Sumber huruf (Irisan 2) | **Fontsource 5.3.x lewat jsDelivr** (pemilik mengizinkan unduhan 16 berkas, 17 Oktober 2026): delapan huruf Latin variabel (Inter, Plus Jakarta Sans, DM Sans, Source Sans 3, IBM Plex Sans, Nunito Sans; Manrope dan Lexend khusus judul) beserta lisensi OFL-nya, disimpan di `public/assets/fonts/`. |
| Cara menerapkan | Dua stylesheet yang dibuat server (`tema-portal.css`, `tema-dashboard.css`) lewat satu `<link>` tetap di setiap halaman, sehingga halaman portal tetap dilayani byte demi byte dan tidak berkedip; kosong selama tema belum disimpan. |
| Tahapan | Irisan 1 fondasi (warna yang ditulis langsung dijadikan variabel, tanpa perubahan tampilan), Irisan 2 warna/latar/huruf/sudut dengan tab Tampilan, Irisan 3 skala dan pratinjau portal, Irisan 4 opsional (`theme-color`, mode gelap portal). |
