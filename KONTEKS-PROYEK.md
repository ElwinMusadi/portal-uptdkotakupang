# PINTU — Konteks Proyek Lengkap

> **Untuk agen AI dan pengembang berikutnya.** Satu berkas ini memuat seluruh konteks proyek PINTU sampai status terakhirnya: tujuan, arsitektur, aturan kerja dengan pemilik, keputusan, integrasi, cara menguji, jebakan lingkungan, dan pekerjaan yang tertunda. Baca berkas ini lebih dulu, lalu ikuti tautan ke dokumen rinci bila perlu.
>
> **Terakhir diperbarui:** 2026-10-17 · branch `laravel-development` · sampai commit fondasi tema portal dan dashboard (D-057 Irisan 1, sesudah `2eb4cda`).
>
> **Wajib diperbarui setiap ada perubahan** (lihat [§19](#19-prosedur-memperbarui-dokumen-ini)). Jangan pernah menulis rahasia (kata sandi, NIP akun uji, token, isi `.env`) di berkas ini.

## Daftar isi

1. [Cara memakai dokumen ini](#1-cara-memakai-dokumen-ini)
2. [Ringkasan proyek](#2-ringkasan-proyek)
3. [Status terakhir](#3-status-terakhir)
4. [Cara bekerja dengan pemilik](#4-cara-bekerja-dengan-pemilik)
5. [Aturan dan batasan teknis](#5-aturan-dan-batasan-teknis)
6. [Arsitektur dan stack](#6-arsitektur-dan-stack)
7. [Struktur repository](#7-struktur-repository)
8. [Portal publik](#8-portal-publik)
9. [Dashboard](#9-dashboard)
10. [API dan backend](#10-api-dan-backend)
11. [Integrasi eksternal](#11-integrasi-eksternal)
12. [Database, data, dan migrasi](#12-database-data-dan-migrasi)
13. [Pengujian dan verifikasi](#13-pengujian-dan-verifikasi)
14. [Lingkungan pengembangan dan jebakannya](#14-lingkungan-pengembangan-dan-jebakannya)
15. [Deploy dan checklist go-live](#15-deploy-dan-checklist-go-live)
16. [Ringkasan keputusan D-001 sampai D-057](#16-ringkasan-keputusan-d-001-sampai-d-057)
17. [Riwayat commit](#17-riwayat-commit)
18. [Pekerjaan tertunda dan hal yang diketahui](#18-pekerjaan-tertunda-dan-hal-yang-diketahui)
19. [Prosedur memperbarui dokumen ini](#19-prosedur-memperbarui-dokumen-ini)
20. [Log perubahan dokumen](#20-log-perubahan-dokumen)

---

## 1. Cara memakai dokumen ini

**Urutan kewenangan** bila ada yang bertentangan:

1. Instruksi pemilik di percakapan saat ini.
2. [`AGENTS.md`](AGENTS.md): batasan alat AI dan aturan Laravel Boost.
3. [`docs/decisions.md`](docs/decisions.md): keputusan pemilik yang berlaku (D-001 dst.). Hanya dokumen ini yang mencatat keputusan yang disetujui.
4. Berkas ini (`KONTEKS-PROYEK.md`): ringkasan dan status.
5. Dokumen rinci: [`docs/api.md`](docs/api.md) (kontrak API), [`docs/dashboard.md`](docs/dashboard.md) (dashboard), [`docs/rencana-antrean-online.md`](docs/rencana-antrean-online.md) (rencana Antrean Online, D-051), [`docs/rencana-tema-situs.md`](docs/rencana-tema-situs.md) (rencana tema portal dan dashboard, D-057), [`docs/RANCANGAN-UIUX.md`](docs/RANCANGAN-UIUX.md) (analisa, sitemap, design system, tata kelola konten), [`README.md`](README.md) (menyiapkan, menjalankan, deploy).

**Arsip, bukan acuan:**

- [`HANDOVER-CHATGPT.md`](HANDOVER-CHATGPT.md): audit 11 Oktober 2026, sebelum proyek menjadi satu aplikasi Laravel. Path di dalamnya sudah usang.
- [`docs/phases/`](docs/phases/): rekam jejak Phase 1–2.19 (keamanan, tata kelola, rancangan API). Rekomendasinya **belum tentu disetujui**; `PHASE-2-ARCHITECTURE-AND-API-DESIGN.md` secara eksplisit tidak disetujui.

---

## 2. Ringkasan proyek

**PINTU (Portal Informasi dan Pelayanan)** adalah portal resmi informasi dan pelayanan pajak kendaraan milik **UPTD Pendapatan Daerah Wilayah Kota Kupang (Samsat Kota Kupang)**, di bawah BPAD Provinsi Nusa Tenggara Timur. Proyek ini lahir dari *Rancangan Aktualisasi* "Penyediaan Website Profil dan Pelayanan Informasi melalui PINTU".

- **Pengguna:** warga wajib pajak (portal publik) dan pegawai UPTD (dashboard, aplikasi absensi).
- **Bahasa produk:** Bahasa Indonesia. Semua teks antarmuka, pesan galat, dan dokumentasi proyek berbahasa Indonesia; kode, nama kelas, dan pesan commit berbahasa Inggris.
- **Zona waktu acuan:** WITA (`Asia/Makassar`).
- **Domain rencana produksi:** `pintu.uptdpenda-kupang.web.id` (belum dialihkan; produksi saat ini masih situs statis lama di Cloudflare Pages dari branch `main`).
- **Samsat** adalah layanan satu atap Pemprov NTT (pajak daerah), Ditlantas Polda NTT (registrasi kendaraan), dan PT Jasa Raharja (SWDKLLJ). Ketiganya tampil di pembuka beranda (D-046).

Satu aplikasi Laravel berisi empat bagian:

| Bagian | Alamat | Teknologi |
|---|---|---|
| Portal publik (16 halaman) | `/`, `/profil`, `/layanan`, `/jadwal`, … | Blade berisi HTML statis + `public/assets/js/main.js`, dipindahkan ke React secara bertahap (D-045) |
| Dashboard pengelolaan konten | `/dashboard` (rute hash `#/…`) | React 19 + TypeScript + Vite + Tailwind 4 + shadcn/ui |
| Aplikasi Absensi PINTU (PWA) | `/absen` | React, akun dan sesi yang sama dengan dashboard |
| API JSON | `/api/…` | Laravel 13, Sanctum (sesi cookie SPA) |

---

## 3. Status terakhir

**Per 2026-10-17** (perbarui bagian ini setiap ada perubahan):

| Hal | Status |
|---|---|
| Branch kerja | `laravel-development` (`main` = situs statis lama yang masih dilayani Cloudflare Pages; jangan diubah) |
| Commit terakhir di origin | `feat(pintu): lay the theme foundation for the portal and dashboard` (D-057 Irisan 1: fondasi tema tanpa perubahan tampilan), sesudah `2eb4cda` (rencana tema). Hash: `git log -3 --oneline` |
| Working tree | Bersih |
| Tes | `composer test`: **1355 tes lulus, 7038 assertion** (SQLite di memori). Uji mutasi: D-047 66 dari 67 mutan mati, D-048 36 dari 37 (sisanya setara), D-049 30 dari 30, Antrean Online Irisan 1 28 dari 28, Irisan 2 46 dari 47 (1 setara), Irisan 3 61 dari 61 (1 sempat bertahan, lalu tes daftar Cetak SKPD ditambah), Irisan 4 51 dari 53 (2 setara: eager loading riwayat), Pemetaan PKB Irisan 1 84 dari 86 (2 setara: hapus bertahap, urutan bulan dari indeks), Irisan 2 52 dari 54 (2 setara: pencarian sel di dekat angka yang terhitung, pembagi FPB pada eliminasi), jenis tidak tercatat 3 dari 3, kolom tabel dan roda terpisah 24 dari 24 (audit acak penyamaran juga memeriksa tidak ada total dengan satu angka tersamar). Penyamaran Pemetaan PKB juga diaudit acak di luar repo (3.000+ susunan data rekaan, 0 kebocoran). D-053: 26 dari 30 mutan mati (4 setara: `string` di samping `ulid`, isi `urutan` di cabang kembali urut jam, dan dua batas tanggal kueri urutan di API publik yang hanya membatasi baris yang dibaca). Sasaran penagihan *Belum taat* dan pencarian `%`/`_`: 6 dari 6 mutan mati. Aplikasi & Situs (D-054): 17 dari 17 mutan mati, sesudah dua pemeriksaan logo bawaan dan satu pengecualian logo sendiri yang terbukti tidak diperlukan dihapus. Ringkasan beranda (D-055): 9 dari 10 mutan mati (1 setara: tahun data pertama selalu sama dengan tahun data terakhir yang dipakai). Sakelar tampil Statistik Layanan (D-056): 7 dari 7 mutan mati. Fondasi tema (D-057 Irisan 1): 17 dari 17 mutan mati (rute dan header CSS tema, isi portal/dashboard, `<link>` di setiap jenis halaman dan urutannya) |
| Pemeriksaan frontend | `npm run typecheck`, `npm run lint`, build Vite: lulus. Dashboard Laporan Penerimaan diperiksa di browser (415 px dan 1280 px). Halaman portal `/penerimaan` diperiksa di browser (430 px dan 1280 px): keadaan belum diterbitkan dengan API sungguhan, lalu laporan berisi data dev yang sudah disamarkan server (dihitung dalam transaksi yang dibatalkan, tanpa mengubah tanda tampil) lewat `fetch` tiruan: periode, saringan, kartu kelompok, tabel, CSV. Setelah pemilik menyalakan Portal, diperiksa lagi dengan API sungguhan: harian, bulanan, rentang, batang per hari, penyamaran pada 8 kombinasi periode dan saringan, 430 px. D-049 diperiksa dengan API sungguhan: invarian penyamaran September dan Oktober (tanpa baris atau gabungan di bawah 3, kecamatan dikurangi kelurahan yang tampil 0 atau ≥3, bulan dikurangi hari yang tampil 0 atau ≥3, laporan harian sama dengan batang bulanan untuk setiap hari), parameter lama ditolak, CSV, 430 px. Papan Layanan D-050 diperiksa di beranda dengan jam peramban dimajukan: Kamis malam (tutup), Jumat 10.00 WITA (buka, isi terakhir), Sabtu (tutup). Antrean Online Irisan 1 diperiksa di browser (415 px) dengan akun admin lokal: cetak tiket uji (dokumen cetak dicegat, dua lembar 80 mm ditampilkan), cetak ulang, batal dengan alasan, halaman lacak (menunggu, batal, kode tak dikenal), Pengaturan Antrean; tiket uji dihapus lagi. QR belum dipindai dengan ponsel. Irisan 2 diperiksa di browser dengan akun admin lokal: Loket (panggil berikutnya, nomor tertentu lewat ketik, panggil ulang, tunda, selesai), Pengaturan (loket, layar dengan tautan sekali tampil), layar `/layar` (pemasangan, panggilan tampil, cabut); suara pembaca tidak bisa didengar di panel browser; data uji dihapus lagi. Irisan 3 diperiksa di browser dengan akun admin lokal dan MySQL `pintu`: Pos Penetapan (pindai alamat QR seperti pemindai USB, nomor `a1`, Urungkan dari panel dan dari toast, mode Perlu perubahan data), Server (Data sudah diubah, kembali ke Penetapan), Pelunasan (nomor polisi, tahap terlewat otomatis), pesan galat, halaman lacak (jam tahap, posisi) di 375–430 px, Pos Proses di 375, 1280, dan 1600 px; *Pindai dengan kamera* hanya dengan `BarcodeDetector` dan kamera tiruan (panel tidak punya kamera). Pemeriksaan ini menemukan Urungkan gagal di MySQL (kolom JSON menyusun ulang kunci), sudah diperbaiki dengan tes; halaman Loket dan Pos Proses yang melebar di layar ponsel bila daftarnya berisi juga diperbaiki. Data uji dihapus lagi. Irisan 4 diperiksa di browser dengan MySQL `pintu`: arsip (sebagian nomor polisi, nomor `c1`, riwayat lengkap, keterangan hapus untuk nomor hari ini), hapus tiket batal dari hari sebelumnya oleh admin lokal (log aktivitas tanpa nomor polisi; entri log uji itu dibiarkan), ringkasan harian dan rentang, batas 31 hari, CSV ditangkap di peramban (BOM UTF-8, daftar tiket), 375 dan 1280 px. Lembar samping Riwayat tidak bisa ditutup di panel (animasi penutup tidak jalan); di peramban biasa tidak terpengaruh. Data uji dihapus lagi. Pemetaan PKB Irisan 2 diperiksa di browser dengan akun admin lokal dan MySQL `pintu`: `/pemetaan` belum diterbitkan, sakelar *Tampil di portal* dinyalakan (konfirmasi, toast), halaman berisi data pemilik (7 kecamatan, 51 kelurahan, tidak ada angka disamarkan) di 375, 508, 1024, dan 1280 px (tanpa luapan halaman; tabel digeser dalam wadahnya di bawah ±1100 px, judul kolom membungkus), pilih kecamatan menampilkan kelurahannya, lalu sakelar dimatikan lagi (API kembali `{tersedia:false}`); dua entri log aktivitas uji itu dibiarkan. Setelah pemilik menyalakan *Tampil di portal* (2026-10-16), `/pemetaan` diperiksa lagi: angka publik sama persis dengan ringkasan dashboard (kota, 6 kecamatan + Tanpa kelurahan, 51 kelurahan), jumlah kecamatan dan kelurahan cocok dengan totalnya, tidak ada angka disamarkan; label peta di 375 px semula ±9 px lalu dibesarkan (±12 px). Sesudah kolom tabel (`6d243d6`), `/pemetaan` diperiksa lagi di 375, 768, dan 1280 px: tabel mengikuti `kolom` pilihan pemilik (tanpa Roda tiga dan Taat) dan muat di 1280 px tanpa geser; peta kecamatan yang diperbesar semula hanya 480 px di kartu selebar 1119 px (label kelurahan terpasang 39 dari 51, Oebobo 4 dari 7) lalu dibuat setinggi 600 px (44 dari 51; Oebobo 7/7, Kota Raja 8/8, Kota Lama 9/10, Maulafa 8/9, Alak tetap 7/12 karena tampilannya tegak); di ponsel halaman semula melebar (±430–450 px) saat kecamatan dipilih karena teks "disamarkan" pembaca layar keluar dari wadah gulir tabel, lalu diperbaiki. Dashboard `#/pemetaan` lalu diperiksa di 375 dan 1280 px: semua permintaan API 200; di 1280 px (sidebar terbuka, wadah tabel 871 px) peta semula 448 px (label kelurahan 39 dari 51) lalu dibuat setinggi 600 px seperti portal (44 dari 51), tabel kecamatan dan kelurahan semula 936 px karena judul kolom tidak membungkus, dan daftar sasaran semula 1275 px karena sel Pemilik (nama dan alamat) mewarisi `whitespace-nowrap` shadcn (kolomnya 566 px); kini semuanya 871 px; di ponsel tidak ada luapan halaman. Papan beranda D-053 diperiksa di browser dengan data dev (1440, 1024, dan 900 px): kartu kiri setinggi isinya, kartu luar kantor setinggi dua baris kiri dengan 2 lokasi + "+6", 900 px tetap 4 lokasi; dialog urutan di dashboard menyimpan urutan (papan dan `urutanPapan` mengikutinya) lalu dikembalikan ke urut jam (dua entri log aktivitas tertinggal di DB dev). Tab Sasaran penagihan diperiksa (1280 dan 375 px, tanpa menyalin data pribadi): jumlah tiap saringan sama dengan ringkasan, detail kendaraan tanpa nilai rusak; tiga catatan diperbaiki (pencarian `%` semula memunculkan 28.725 kendaraan, keterangan judul saat hasil kosong, kendaraan taat ikut di daftar); di MySQL bawaan Belum taat = 17.748 kendaraan, `%`/`_`/`!` = 0. Aplikasi & Situs (D-054) diperiksa lewat `php artisan serve` sementara di port 8010 (Herd dan Vite sedang mati; `APP_URL`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, dan `SANCTUM_STATEFUL_DOMAINS` diganti lewat variabel lingkungan prosesnya, `.claude/launch.json` dihapus lagi): portal 375 px (satu kolom, tanpa luapan) dan 1280 px (tiga kolom, kartu sebaris sama tinggi, tombol rata bawah), kelima logo termuat, menu Layanan menandai halaman aktif; dashboard (akun lokal) menampilkan menu di Alat Bantu dan tabel 935 px tanpa geser; tambah entri uji (aturan Android ditolak di formulir), simpan ulang PRO NTT berlogo bawaan (200), lalu hapus entri uji. Dasbor beranda (D-055) diperiksa dengan data dev: kelima tab terisi (Penerimaan pajak Rp13,4 miliar dari 7.638 transaksi 1 Sep–14 Okt 2026, grafik Sep 12,8 M dan Okt 563,4 jt; Pemetaan PKB 28.725 kendaraan dan persen menunggak enam kecamatan), tautan "Lihat …" hanya di dua tab itu, kolom kiri tanpa tautan; 375 px (label kecamatan paling banyak dua baris, tanpa luapan) dan 1280 px (label tidak bertumpang) diukur lewat DOM karena panel browser berhenti menggambar. Sakelar tampil (D-056): editor dashboard menampilkan sakelar di setiap kategori dan ikon mata dicoret pada tab yang dimatikan; beranda dengan data dev (ketiga kategori dimatikan pemilik, disimpan 2026-10-17 02.30 WITA lewat dev server Vite) menyembunyikan tab Transaksi, Samsat Keliling, dan Kepuasan dan memilih Penerimaan pajak. Fondasi tema (D-057 Irisan 1): gaya terhitung semua elemen (warna, latar, garis, sudut, huruf, ukuran, bayangan, `::before`/`::after`) direkam sebelum perubahan lalu dibandingkan di 23 halaman portal, 18 halaman dashboard terang, 5 gelap, dan Absensi: tidak ada beda selain teks yang bergerak sendiri (judul berganti di beranda, waktu relatif). Pemeriksaan pertama menemukan sudut menu navigasi menjadi 0 karena panel memakai `tokens.css` lama dari cache bersama `components.css` baru; sejak itu variabel baru dipakai dengan nilai cadangan, dan simulasi `tokens.css` lama tidak lagi menimbulkan beda. Bukti statis: setelah variabel diganti nilainya, CSS lama dan baru identik (termasuk *hover*). Uji aksen hijau: tidak ada sisa biru selain daun logo, contoh warna Design System, dan placeholder `.ph--blue`; huruf dan warna pratinjau papan dashboard terbukti bisa ditimpa. `/tema-portal.css` dan `/tema-dashboard.css` lewat Herd: 200 `text/css`, `no-cache` + ETag, 304 |
| Keputusan terakhir | D-057 (tema: portal dan dashboard punya tema masing-masing dari tab Tampilan di Pengaturan, skala portal lewat `zoom`, logo tetap biru PINTU, huruf terkurasi di-host sendiri; Irisan 1 fondasi selesai tanpa perubahan tampilan, berikutnya Irisan 2) |
| Deploy Laravel Cloud | **Belum.** Pemilik: "jangan deploy ke Laravel Cloud dulu" (D-035). Deploy adalah langkah pemilik |
| Produksi | Masih Cloudflare Pages dari branch `main` (situs statis lama) |
| API data kendaraan (NJKB) | Token PINTU terpasang di `.env` lokal dan lolos Cloudflare Access; Worker `njkb-api-ntt` sudah di-deploy pemilik dengan perbaikan 404 (commit `6a4c4f8`), terverifikasi 2026-10-15 07.04 WITA. **Uji dengan kendaraan sungguhan oleh pemilik belum dikonfirmasi** |
| Data lokal | Database dev `pintu` berisi **data milik pemilik** (lihat [§12](#12-database-data-dan-migrasi)). Tabel `revenue_imports` dan `revenue_transactions` sudah dimigrasi; berkas STS harian 14 Oktober 2026 (320 transaksi, Rp563.444.182) dan bulanan September 2026 (7.318 transaksi baru + 1 ganda dilewati, Rp12.847.135.065) diimpor sebagai Loket SAMSAT lewat endpoint impor (berkas bulanan lewat `php artisan serve` sementara karena batas unggah Herd 2 MB, lihat §14). Pemilik menyalakan kolom **Portal** kedua impor itu (2026-10-15 malam), jadi `/penerimaan` menampilkan 1 September sampai 14 Oktober 2026. Tabel `service_board_orders` (D-053) sudah dimigrasi dan kosong. Tabel `agency_apps` (D-054) sudah dimigrasi dengan lima aplikasi awal; satu entri uji ditambah lalu dihapus lewat dashboard (entri log aktivitas ujinya tertinggal) |

**Isi terakhir yang disimpan pemilik di dashboard lokal** (penting saat menguji tampilan): judul pembuka beranda "Semua informasi SAMSAT" (**tanpa titik**, disimpan pemilik 07.55 WITA 2026-10-15) dengan kata bergilir Pajak, STNK, Mutasi, **BBNKB**, SAMSAT.

---

## 4. Cara bekerja dengan pemilik

Pemilik (Owner produk, pegawai UPTD) berkomunikasi dalam **Bahasa Indonesia**. Pola kerja yang sudah mapan:

1. **Pahami permintaan dan periksa kode** sebelum mengubah apa pun.
2. **Tanyakan keputusan penting** dengan pilihan ganda (pilihan yang disarankan ditulis pertama dengan label "(Recommended)"). Jangan menanyakan hal yang bisa diputuskan dari kode atau kebiasaan proyek; sebutkan saja pilihan yang diambil di laporan.
3. **Implementasikan** satu irisan (slice) utuh: server, dashboard, portal, tes, dokumentasi.
4. **Verifikasi**: tes PHP, uji mutasi untuk logika baru, typecheck, lint, build, dan pemeriksaan di browser pada situs lokal Herd.
5. **Laporkan dalam Bahasa Indonesia**: apa yang berubah, hasil pemeriksaan, catatan penting. Tutup dengan pertanyaan **"Commit dan Push?"**.
6. **Commit dan push hanya setelah pemilik menjawab "Commit dan Push."** Persetujuan berlaku untuk satu kali itu saja.
7. **Catat keputusan pemilik** sebagai entri D-0xx baru di `docs/decisions.md` (format: tabel Hal | Keputusan, tandai "(keputusan pemilik)").
8. **Perbarui berkas ini** di commit yang sama (lihat [§19](#19-prosedur-memperbarui-dokumen-ini)).

Larangan dan kebiasaan penting:

- **Jangan deploy** (Laravel Cloud maupun Worker `njkb-api-ntt`). Pemilik yang menjalankan deploy.
- **Jangan menulis ke data pemilik di dashboard lokal** saat menguji (mis. jangan menekan *Simpan* di Beranda). Batalkan perubahan uji dengan *Batalkan*.
- **Jangan menampilkan rahasia** di percakapan atau berkas (token, kata sandi, NIP akun uji). Minta pemilik mengisi sendiri nilai rahasia ke `.env`.
- **Sesi lain bisa bekerja bersamaan di checkout yang sama.** Selalu cek `git log -3` dan `git diff` per berkas tepat sebelum staging; jangan pernah men-stage hunk milik sesi lain. Bila perlu mendorong satu commit saja: `git push origin <sha>:laravel-development`.
- Pesan commit: Conventional Commits berbahasa Inggris, imperatif, mis. `feat(pintu): …` / `fix(pintu): …`, badan menjelaskan keputusan, diakhiri trailer `Co-Authored-By` sesuai instruksi lingkungan agen.

---

## 5. Aturan dan batasan teknis

Ringkasan dari [`AGENTS.md`](AGENTS.md) (baca berkas aslinya):

- Pertahankan branch dan semua perubahan yang sudah ada di working tree. **Tidak boleh** commit, push, deploy, reset, stash, atau membuang perubahan tanpa izin eksplisit.
- Pertahankan copy berbahasa Indonesia, markup halaman portal, UI dashboard, gaya, dan aset.
- Stack tetap: React 19 + JSON API (**tanpa Livewire, tanpa Inertia**), TypeScript, Vite, Tailwind CSS v4, komponen shadcn/Radix, Laravel 13, MySQL, Laravel Cloud. **Jangan memperkenalkan** Next.js, Inertia, Livewire, Pest, PostgreSQL, Workers, atau penyedia database baru.
- Pertahankan respons **JSON polos** (tanpa amplop `data`), ULID huruf kecil, tanpa paginasi kecuali disetujui.
- Database lokal `pintu` adalah database pengembangan **dan** database uji MySQL (`DB_TEST_DATABASE`): `composer test:mysql` mereset isinya. `composer test` memakai SQLite di memori dan `tests/TestCase.php` menolak target lain. Jangan menjalankan SQL atau migrasi ke database yang tidak ditunjuk pemilik.
- Formatter hanya untuk berkas yang diubah: `vendor/bin/pint --format agent <path>` (bukan `--dirty` saat tree berisi perubahan orang lain).
- Laravel Boost (MCP) hanya alat lokal; **minta izin** sebelum memakai alat Boost.
- Otomasi browser hanya ke server lokal yang terkendali, kecuali diizinkan.
- Tinjau keamanan kode yang terautentikasi juga, bukan hanya rute anonim.

Konvensi kode (dari pedoman Laravel Boost dan kebiasaan proyek):

- PHP: kurung kurawal selalu, constructor property promotion, tipe kembalian eksplisit, PHPDoc dengan array shape, komentar inline hanya untuk logika rumit. Enum TitleCase.
- Buat berkas Laravel dengan `php artisan make:… --no-interaction`.
- Model baru disertai factory; tes fitur dengan PHPUnit 12 (`php artisan make:test --phpunit`).
- Validasi di FormRequest, otorisasi lewat Policy, logika bisnis di `app/Actions`.
- Nama kolom database dan nama properti JSON berbahasa Indonesia (`judul`, `tanggalMulai`, `aktif`), nama kelas berbahasa Inggris (`MobileServiceStop`).
- Frontend: nama fungsi dan variabel berbahasa Indonesia (`muatJudul`, `pisahJudul`), TypeScript ketat, Zod untuk validasi formulir dengan aturan yang sama dengan server.

---

## 6. Arsitektur dan stack

**Versi terpasang** (2026-10-15):

| Lapisan | Paket |
|---|---|
| PHP | PHP 8.3, Laravel 13.35, Sanctum 4.3, Tinker 3, league/flysystem-aws-s3-v3 3.35 |
| PHP dev | PHPUnit 12.5, Pint 1.32, Laravel Boost 2.10, Pail, Collision, Mockery, Faker |
| JS | React 19.3, react-router 8.4 (hash), TanStack Query 5 + Table 9, Zod 4.6, React Hook Form 7, radix-ui 1.7, Tiptap 3, dnd-kit, Recharts 3, uqr 0.1.3 (QR tiket antrean, D-051), sonner, cmdk, react-day-picker 10, idb-keyval |
| JS dev | Vite 8.3, TypeScript 6.0, Tailwind CSS 4.3 (`@tailwindcss/vite`), `@vitejs/plugin-react` 6.1, `laravel-vite-plugin` 3.2, oxlint 1.87 |
| Database | MySQL 8 (lokal: database `pintu`), SQLite di memori untuk tes |
| Hosting rencana | Laravel Cloud + Laravel Cloud Object Storage (R2) |

**Prinsip arsitektur:**

- **Satu origin** (D-002): portal, dashboard, dan API di satu aplikasi Laravel. Tanpa CORS, sesi cookie Sanctum.
- **Portal = HTML lengkap dari server** yang diisi data dari API publik oleh skrip. Teks di HTML menjadi isi awal yang terbaca mesin pencari (pola D-023).
- **Portal beralih ke React bertahap di atas Blade** (D-045): elemen `[data-react="nama"]` diambil alih komponen React; bagian lain masih `main.js`. SPA penuh dan SSR (Inertia/Next.js) tidak dipakai.
- **Dashboard = SPA React** dengan rute hash, berbicara dengan API JSON. Mode "campuran" (`HybridApi`) mengirim koleksi dan pengaturan yang sudah di server ke `/api`, sisanya ke IndexedDB (kini praktis semuanya sudah di server).
- **API publik tanpa sesi dan tanpa cookie**, `Cache-Control: no-store`, menolak query parameter, batas laju bersama 300/menit/IP.

---

## 7. Struktur repository

```
app/Actions/                 logika bisnis (30 kelas: ClockIn, CopyMobileServiceStops, LookUpVehicle, SaveSetting,
                             StoreUpload, SyncUploads, UpdateWorkflowEntry, ListPublic*, ShowPublic*, …)
app/Console/Commands/        pintu:create-admin, pintu:activation-link
app/Enums/                   DashboardModule, WorkflowStatus, UploadTarget, ServicePointKind, AccountStatus, UserRole, FaqStatus
app/Http/Controllers/        Public*Controller (API publik), ArticlePageController (/berita/{slug})
app/Http/Controllers/Dashboard/   45 controller dashboard (koleksi, pengaturan, unggahan, absensi, kendaraan)
app/Http/Controllers/Admin/  akun pegawai; Auth/: login, aktivasi
app/Http/Requests/           FormRequest (Dashboard/, Admin/, Auth/, publik); Dashboard/Concerns/TakesWorkflowStep
app/Http/Resources/          16 JsonResource (withoutWrapping)
app/Http/PortalPage.php      merender halaman portal dengan alamat root-relative (/berita/{slug}, 404)
app/Http/Middleware/         EnsureAccountIsActive, PreventResponseCaching
app/Models/                  model ULID + Setting (kunci string); Concerns/HasPublicationWorkflow
app/Policies/                16 policy
app/Rules/                   PlainText, RichText, PortalLink, ContactAddress, StrongPassword, UploadUrl, YoutubeUrl
config/pintu.php             batas API publik, batas FAQ, pengaman tes MySQL
config/services.php          blok `njkb` (API data kendaraan)
database/migrations/         31 migrasi (tabel, data awal, perubahan)
database/seeders/            LocalAccountSeeder (admin lokal, hanya env local)
resources/views/portal/      16 halaman portal (Blade berisi HTML statis) + partials/{header,footer,icons}.html
resources/views/errors/404.blade.php, auth/aktivasi.blade.php, dashboard.blade.php, absen.blade.php
resources/js/dashboard/      SPA dashboard (alias `@`)
resources/js/absen/          PWA absensi
resources/js/portal/         main.tsx (pemuat komponen React portal), beranda/*.tsx|.css, kata-bergilir.ts, cek-pajak.ts
resources/js/pkb/            mesin pajak (salinan persis kalkulator-pajak) + penetapan.ts milik PINTU
public/assets/css/           tokens.css, base.css, components.css, pages.css (CSS portal, tidak berversi)
public/assets/js/main.js     JavaScript portal tanpa dependensi (±2.300 baris, 30 fungsi init*)
public/assets/img/           lambang NTT, logo PINTU, logo Ditlantas & Jasa Raharja, foto kantor, ikon
public/absen-sw.js, public/absen.webmanifest
public/build/                hasil build Vite (tidak di-commit)
scripts/sync-partials.mjs    menyalin header/footer/ikon ke semua halaman portal
scripts/batas-wilayah.mjs    shapefile Batas Desa/Kelurahan BIG → resources/js/portal/pemetaan/batas-wilayah.ts (D-052)
tests/Feature/ (66 berkas), tests/Unit/, tests/Support/EnforcedCsrfToken.php
docs/                        decisions.md, api.md, dashboard.md, rencana-antrean-online.md, rencana-tema-situs.md, RANCANGAN-UIUX.md, phases/, preview/
```

Berkas alat: `AGENTS.md`, `boost.json`, `kilo.json` (skill di `.agents/skills/`, MCP Boost), `.codex/`, `.claude/`.

---

## 8. Portal publik

### 8.1 Halaman

`index` (Beranda), `profil` (Tentang UPTD), `visi-misi`, `struktur-organisasi`, `layanan` (Persyaratan Layanan), `aplikasi` (Aplikasi & Situs, menu Layanan, `initAplikasi()` dari `GET /api/public/aplikasi`, D-054), `jadwal` (Jam Pelayanan dan Layanan Luar Kantor), `informasi` (Berita & Pengumuman), `dokumentasi`, `unduhan` (Regulasi & Unduhan), `penerimaan` (Laporan Penerimaan, D-048), `pemetaan` (Pemetaan PKB, D-052), `lacak` (`/lacak/{kode}`, halaman lacak Antrean Online dari QR tiket, `noindex`, tidak di menu, D-051), `kontak` (Kontak & Pengaduan), `login` (Login Pegawai), `dashboard-pegawai` (stub pengalihan ke `/dashboard`), `design-system`, `kebijakan-privasi`, `syarat-ketentuan`, `berita-detail` (templat untuk `/berita/{slug}`).

- Alamat lama `*.html` dialihkan 301 ke alamat bersih dengan query string tetap. `/berita-detail(.html)` → 301 `/informasi`.
- Rute portal tanpa sesi, tanpa CSRF, tanpa cookie.
- 404 HTML memakai `errors/404.blade.php` lewat `PortalPage::render` (alamat dijadikan root-relative agar bergaya di path bertingkat). API dan permintaan JSON tetap mendapat 404 JSON.

### 8.2 Aturan markup (penting)

- **Halaman portal dilayani byte demi byte seperti tertulis** (`PortalPagesTest::test_serves_each_portal_page_byte_for_byte_as_written`). Jangan menaruh direktif Blade (`@vite` dsb.) di halaman portal.
- Aset memakai **alamat relatif** (`assets/css/…`, `skrip/portal`). Halaman di path bertingkat (`/berita/{slug}`, 404) ditulis ulang ke root-relative oleh `PortalPage::fromSiteRoot`. Jangan memakai `<base>` (merusak `<use href="#…">` SVG).
- Header, footer, dan ikon disalin dari `resources/views/portal/partials/` dengan `node scripts/sync-partials.mjs` (cek dulu dengan `--check`). Penanda `<!-- @header -->…<!-- /@header -->`, `@footer`, `@icons` harus berpasangan.
- **CSS portal tidak berversi** (`public/assets/css/*.css`): peramban bisa memakai salinan lama setelah deploy. Gaya untuk komponen React baru diletakkan di berkas CSS yang diimpor komponennya (dibangun Vite dengan nama ber-hash).
- **Tema (D-057):** setiap halaman portal memuat `tema-portal.css` sebagai stylesheet terakhir (rute, kosong sampai tema disimpan). Warna dan sudut ditulis lewat variabel `tokens.css`, bukan langsung; sudut baru `calc(Npx * var(--r-scale, 1))`. Variabel yang ditambahkan sesudah 2026-10-17 dipakai **dengan nilai cadangan** (`var(--map, #ECE9E1)`), karena `tokens.css` lama bisa masih tersimpan di peramban bersama berkas CSS lain yang baru. Daun logo memakai `--logo-leaf`/`--logo-leaf-night` (tetap biru), bukan `--accent`.
- **`base.css` berisi `img, svg, video { display: block }`, yang mengalahkan atribut `hidden`.** Elemen gambar yang harus tersembunyi butuh aturan kelas (`.x[hidden] { display: none }`) atau disimpan di `<template>` (pola D-046).
- **Foto di avatar bulat** (`.avatar > img`: bagan Struktur, sambutan Profil) ditempatkan absolut mengisi lingkaran (`.avatar` `position: relative; overflow: hidden`) dan dipangkas dari atas (`object-position: 50% 0`), karena `height: 100%` tidak berlaku pada anak sel grid bertinggi otomatis dan foto pejabat berupa potret tegak dengan kepala di bagian atas.
- **Sampul halaman berita** (`berita-detail`, `.article-figure` di `pages.css`): selebar isi artikel (936 px = kolom info 200 + jarak 56 + teks 680, sejajar kolom info dan ujung teks) dan tampil utuh dengan rasio aslinya, tanpa bingkai 16:8, agar poster tidak terpotong (pemilik, 2026-10-16). Sebelumnya `container--wide` (1320 px) membuatnya lebih lebar dari isi berita di desktop. Kepala artikel (`.article-head`: breadcrumb, judul, ringkasan) juga selebar 936 px (sebelumnya 860 px), jadi tepi kiri judul, sampul, dan kolom info sejajar.
- Hook data memakai atribut `data-*` (mis. `data-beranda-teks="hero.deskripsi"`, `data-keliling-week`, `data-faq-list`). Isi dari API selalu dipasang sebagai **teks polos** (`textContent`); tautan hanya bila lolos `tautanAman()`.

### 8.3 Pola isi dari API

- **Teks HTML tetap sebagai isi awal** (D-023, D-024, D-026): teks yang tertulis di HTML sama dengan isi awal di server (`Setting::INITIAL_*`). Skrip menggantinya hanya bila dokumen sudah pernah disimpan (`diperbarui` tidak `null`). Bila API gagal, teks HTML tetap tampil. Tes memastikan teks awal masih ada di markup.
- Untuk daftar (FAQ, jadwal, berita, …): bila API gagal tampil pesan + tombol *Coba lagi*, tanpa data cadangan atau tebakan. Bagian tanpa isi disembunyikan.
- `public/assets/js/main.js` berisi fungsi `init*`: `initNav`, `initReveal`, `initTabs`, `initChecklists`, `initStatus` (jam buka), `initPapan`, `initKeliling`, `initKontak`, `initVisiMisi`, `initProfil`, `initLayanan`, `initDokumen`, `initInformasi`, `initBerita`, `initPengumumanBeranda`, `initDokumentasi`, `initGallery`, `initStruktur`, `initBeranda`, `initHalaman`, `initSitus`, `initFaq`, `initDash` (dasbor transparansi beranda: Statistik Layanan, ringkasan Laporan Penerimaan, dan Pemetaan PKB per tab, D-055), `initPesanForm`, `initFilters`, `initForms`, `initDialogs`, `initAuthFit`, `initUtilities`, `initGag`.

### 8.4 Komponen React portal (D-045, D-046)

- Halaman memuat `<script type="module" src="skrip/portal"></script>`. Rute `/skrip/portal` (nama `portal.skrip`) mengalihkan 302 ke `Vite::asset('resources/js/portal/main.tsx')`, sehingga halaman tetap byte demi byte.
- `resources/js/portal/main.tsx` mencari setiap `[data-react="nama"]` dan memuat komponen bernama sama secara lazy (peta `KOMPONEN`). Setiap modul komponen mengekspor `pasang(el)`.
- `vite.config.ts`: `react({ exclude: [/\/node_modules\//, /\/resources\/js\/portal\//] })`, sehingga berkas portal tidak memakai Fast Refresh (halaman portal tidak punya preamble) dan dimuat ulang penuh saat pengembangan.
- React portal dibagi dengan dashboard dalam chunk terpisah. Beranda memuat sekitar 65 KB (gzip) tambahan.

| Komponen | Elemen | Isi |
|---|---|---|
| `beranda/judul-pembuka.tsx` + `.css` | `<h1 data-react="judul-pembuka">` | Judul pembuka dari `GET /api/public/beranda`. Kata judul yang sama dengan salah satu *kata bergilir* bergulir vertikal ke kata lain (1,2 detik setelah tampil, 1,6 detik per kata), kembali ke kata judul, diam 10 detik, lalu berulang. Berhenti saat tab tidak dilihat; "kurangi gerak" = judul diam; pembaca layar membaca judul utuh. Bila lebar kata mengubah letak baris, kata diberi baris sendiri agar judul tidak melompat |
| `beranda/lambang-pembuka.tsx` + `.css` | `<div class="hero__lambang" data-react="lambang-pembuka">` | Lambang NTT di tengah; logo Ditlantas (kiri) dan Jasa Raharja (kanan) disimpan di `<template data-lambang-mitra>`. ±2,5 detik setelah halaman dibuka keduanya keluar dari balik lambang (hanya otomatis sekali per muat). Lambang NTT menjadi tombol (`aria-expanded`) untuk menyembunyikan/memunculkan |
| `penerimaan/laporan-penerimaan.tsx` + `.css` | `<div class="penerimaan" data-react="laporan-penerimaan">` di `/penerimaan` | Laporan Penerimaan publik (D-048, D-049) dari `GET /api/public/penerimaan`, sebagai tabel tetap: *Harian* hanya panel gelap ringkasan (total, susunan komponen, transaksi, kendaraan baru, pajak provinsi, opsen) dengan tombol ke laporan bulanannya; *Bulanan* juga batang per hari, kartu per loket/jenis daftar/jenis kendaraan/kecamatan dengan baris "Lainnya (disamarkan)" bergaris, dan tabel rincian bertab (transaksi, jumlah, porsi) dengan unduh CSV. Tanpa saringan dan rentang. Porsi yang ada tetapi di bawah 0,05% ditulis "<0,1%" (sama di dashboard). Periode ditulis ke alamat halaman (`?tanggal=` atau `?bulan=`). Tanpa Recharts (±6 KB gzip) |
| `pemetaan/pemetaan-pkb.tsx` + `.css` | `<div class="pemetaan" data-react="pemetaan-pkb">` di `/pemetaan` | Pemetaan PKB publik (D-052 Irisan 2) dari `GET /api/public/pemetaan`: panel gelap kota (objek pajak, roda dua/tiga/empat, *Jenis tidak tercatat* bila ada, tanggal penetapan) dan petak taat, belum bayar < 1 tahun, menunggak ≥ 1 tahun, potensi, tagihan, tunggakan; peta wilayah dengan Batas Desa/Kelurahan BIG 2022 (`batas-wilayah.ts` dan `peta.ts`, sama dengan dashboard): 6 kecamatan, dan kecamatan terpilih diperbesar dengan kelurahannya (kecamatan lain redup dan bisa dipilih, *← Semua kecamatan*), diwarnai menunggak, belum taat, atau tagihan (terendah–tertinggi di antara wilayah yang tampil); peta setinggi 600 px (paling tinggi 75% layar) dengan lebar mengikuti bentuk tampilan (`--rasio`), sedikitnya 480 px dan paling lebar selebar kartu, jadi kecamatan yang melebar (Oebobo, Kelapa Lima, Kota Lama) tampil besar di layar lebar; tulisan selalu 12/11 px (`--px` dari lebar peta yang tampil lewat ResizeObserver), bertepi latar; label kelurahan yang akan saling menimpa dilewati (nama tetap di keterangan dan tabel); Kota Lama berlabel di laut dengan garis penunjuk; memilih kecamatan di peta memindahkan fokus ke *← Semua kecamatan*, dan kembali memfokuskan kecamatan itu lagi (kecamatan terpilih tidak lagi berupa tombol); tabel per kecamatan dengan tombol pilih (`aria-expanded`) dan tabel kelurahan kecamatan terpilih, keduanya wilayah geser yang bisa difokus (`position: relative` agar teks pembaca layar di selnya tidak melebarkan halaman), dengan kolom dari `kolom` di API (pilihan Administrator; roda tiga dan roda empat terpisah). Angka disamarkan tampil "—" dengan teks pembaca layar "disamarkan" |
| `antrean/lacak-antrean.tsx` + `.css` | `<div class="lacak" data-react="lacak-antrean">` di `/lacak/{kode}` | Halaman lacak Antrean Online (D-051) dari `GET /api/public/antrean/{kode}`: panel gelap tiket (nomor, layanan, nomor polisi, tanggal) dan petunjuk tahap sekarang, daftar tahap layanan (selesai dengan jamnya/sekarang/nanti), banyaknya nomor di depan saat menunggu dipanggil (Irisan 3), petunjuk perubahan data, diperbarui setiap 30 detik selama halaman terlihat (berhenti bila selesai atau batal), tombol *Perbarui sekarang* |
| `antrean/layar-antrean.tsx` + `.css` | `<div data-react="layar-antrean">` di `/layar` (`resources/views/layar.blade.php`, bukan halaman portal) | Layar TV ruang tunggu Antrean Online (D-051 Irisan 2): token dari tautan pemasangan `#k=` disimpan di perangkat, `GET /api/layar` setiap 2 detik dengan bearer token; panggilan terbesar dengan nomor polisi dan loket, panggilan sebelumnya, nomor di setiap loket, jumlah menunggu; panggilan baru: bunyi tanda (Web Audio) lalu suara bahasa Indonesia peramban (Web Speech) tanpa nomor polisi, setelah *Aktifkan suara* diklik |
| `penerimaan/periode.ts`, `penerimaan/csv.ts` | (modul bersama) | Periode laporan (geser hari/bulan, label tanggal Indonesia) dan berkas CSV (titik koma, BOM); dipakai juga halaman dashboard Laporan Penerimaan |
| `kata-bergilir.ts` | (modul bersama) | `kataDalamJudul()` dan `pisahJudul()`: pencocokan kata utuh tanpa membedakan huruf besar-kecil. Server memakai aturan yang sama (`Setting::rotatingWordIn`); dashboard mengimpornya untuk validasi |

**Menambah komponen React portal baru:** buat `resources/js/portal/<bagian>/<nama>.tsx` yang mengekspor `pasang(el)`, impor CSS-nya dari komponen itu, daftarkan di peta `KOMPONEN` di `main.tsx`, tandai elemen di Blade dengan `data-react="<nama>"` (isi HTML tetap sebagai isi awal), pastikan halaman memuat `skrip/portal`, lalu pindahkan kode `main.js` terkait.

### 8.5 Cek biaya pajak di portal (D-041)

`resources/js/portal/cek-pajak.ts` dimuat lewat `/skrip/cek-pajak` di beranda dan Layanan. Pengunjung mengisi nomor polisi + **5 karakter terakhir nomor rangka**; `POST /api/public/kendaraan` memberi data kendaraan hanya bila keduanya cocok (jawaban 404 seragam untuk semua ketidakcocokan, nomor rangka dan mesin tidak pernah dikirim ke peramban). Rincian pajak dihitung di peramban dengan mesin `resources/js/pkb`.

---

## 9. Dashboard

### 9.1 Teknis

- Entri `resources/js/dashboard/main.tsx` (memeriksa sesi lalu memasang ThemeProvider, QueryClient, Router hash). Dimuat oleh `resources/views/dashboard.blade.php` (`@viteReactRefresh` + `@vite`, lalu `/tema-dashboard.css`, D-057). Tema bawaan Terang (D-035), disimpan di `localStorage` `pintu.tema`.
- Tema (D-057): warna lewat variabel `index.css` (pratinjau papan `bg-night`, `text-night-muted`, …; `text-brand-foreground`), huruf di blok `@theme` biasa agar bisa ditimpa, ukuran dalam `rem`/skala Tailwind, bukan `px` (kecuali garis dan cincin di `components/ui` dan label peta).
- `lib/api/`: `client`, `http`, `hybrid` (bawaan: `HybridApi(HttpApi("/api"), LocalApi())`), `local` (IndexedDB `pintu-dashboard`), `seed` (data contoh lokal), `types`.
  - `KOLEKSI_SERVER` (13): faq, keliling, lokasi, penyesuaian, pesan, layanan, halaman, pemutakhiran, dokumen, berita, dokumentasi, unit, aplikasi.
  - `PENGATURAN_SERVER` (9): jam, papan, statistik, kontak, visiMisi, profil, beranda, situs, akun.
  - `UNGGAHAN_SERVER`: visiMisi, profil, beranda, dokumen, berita, dokumentasi, unit, aplikasi.
  - Akun (`lib/akun.ts`), Absensi (`lib/absensi.ts`), dan Laporan Penerimaan (`lib/penerimaan.ts`) selalu dari server.
- Formulir: React Hook Form + Zod dengan batas yang sama dengan server; panel sunting di URL (`?baru=1`, `?ubah=<id>`); perubahan belum disimpan dijaga; `Ctrl K` untuk pencarian cepat.
- Komponen formulir bersama di `components/form/` (mis. `FieldTeks`, `FieldArea`, `FieldJudul2Nada`, `FieldDaftarTeks`, `DaftarObjek`, `IsianLogo`, `FormPengaturan`).

### 9.2 Menu dan modul

| Menu | Rute hash | Data server | Modul |
|---|---|---|---|
| Ringkasan | `#/` | KPI, aktivitas | (disaring per modul) |
| Alur Publikasi | `#/alur` | konten beralur | (disaring) |
| Pesan & Pengaduan | `#/pesan` | koleksi `pesan` | `pesan` |
| Statistik Layanan | `#/statistik` | pengaturan `statistik` (tiga kategori, masing-masing dengan sakelar Tampil di portal, D-056) | `statistik` |
| Laporan Penerimaan | `#/penerimaan` | `/api/penerimaan/…` (impor berkas STS, laporan) | `penerimaan` |
| Pemetaan PKB | `#/pemetaan` (Peta & ringkasan: peta kecamatan dan kelurahan dengan batas BIG 2022, tabel kecamatan/kelurahan, kepatuhan, tunggakan, jenis, kunjungan lapangan; Sasaran penagihan dengan identitas, mulai dari Kepatuhan *Belum taat*; impor Data Potensi; *Atur kolom* khusus Administrator untuk tabel wilayah (juga portal) dan daftar sasaran, tersimpan di server; peta setinggi 600 px seperti portal; judul kolom angka dan sel Pemilik, Kepatuhan, dan Hasil kunjungan membungkus agar tabel muat di 1280 px) | `/api/pemetaan/…` (impor bertahap, ringkasan, kendaraan; sakelar *Tampil di portal* khusus Administrator) | `pemetaan` |
| Berita & Pengumuman | `#/berita` | koleksi `berita` | `berita` |
| Dokumentasi Kegiatan | `#/dokumentasi` | koleksi `dokumentasi` | `dokumentasi` |
| Regulasi & Unduhan | `#/unduhan` | koleksi `dokumen` | `dokumen` |
| Tanya Jawab | `#/faq` | koleksi `faq` | `faq` |
| Persyaratan Layanan | `#/layanan` | koleksi `layanan` | `layanan` |
| Jam Pelayanan | `#/jadwal/jam` | pengaturan `jam`, koleksi `penyesuaian` | `jam` |
| Layanan Luar Kantor | `#/jadwal/keliling` (`?jenis=keliling|gerai|sementara|lokasi`) | koleksi `keliling`, `lokasi`; urutan papan Beranda per tanggal (D-053) | `keliling` |
| Papan Layanan | `#/papan` | pengaturan `papan` | `papan` |
| Antrean Online | `#/antrean` (Meja Informasi), `#/antrean/loket` (Loket), `#/antrean/pos` (Pos Proses: Penetapan, Server, Pelunasan, Cetak SKPD, Cetak STNK; pindai QR langsung menandai, dengan Urungkan), `#/antrean/arsip` (Arsip: cari, riwayat, hapus tiket oleh Administrator), `#/antrean/ringkasan` (Ringkasan harian/bulanan/rentang ≤31 hari, CSV), `#/antrean/pengaturan` (layanan, loket, layar; khusus Administrator) | `/api/antrean/…` (tiket, layanan, loket, pos, arsip, ringkasan, layar) | `antrean` |
| Tentang UPTD | `#/profil` | pengaturan `profil` | `profil` |
| Visi & Misi | `#/visi-misi` | pengaturan `visiMisi` | `visiMisi` |
| Struktur Organisasi | `#/struktur` | koleksi `unit` | `unit` |
| Kontak & Kanal | `#/kontak` | pengaturan `kontak` | `kontak` |
| Beranda | `#/beranda` | pengaturan `beranda` | `beranda` |
| Halaman Statis | `#/halaman` | koleksi `halaman` | `halaman` |
| Jadwal Pemutakhiran | `#/pemutakhiran` | koleksi `pemutakhiran` | `pemutakhiran` |
| Absensi Lapangan | `#/absensi` | `/api/absensi/kegiatan` | Administrator mengelola; pegawai lain diarahkan ke `/absen` |
| Kalkulator PKB | `#/kalkulator` | `/api/kendaraan/{nopol}` | semua pegawai |
| Aplikasi & Situs (Alat Bantu, D-054) | `#/aplikasi` | koleksi `aplikasi` | `aplikasi` |
| Pengaturan | `#/pengaturan` (tab Situs, Akun, Data) | `situs`, `akun`, cadangan | Situs: baca semua, simpan Admin |
| Akun Pegawai | `#/akun` | `/api/akun` | khusus Administrator |
| Panduan | `#/panduan` | — | — |

`#/tarif` dialihkan ke Kalkulator PKB (Tarif & Simulasi dipensiunkan, D-041).

### 9.3 Peran dan alur

- **Peran akses:** `administrator` (mengelola semua modul) atau `pegawai` (Pegawai UPTD, hanya modul yang dicentang Administrator; D-010). 17 modul dapat ditugaskan (`DashboardModule`, `tarif` dipensiunkan).
- **Hapus konten editorial hanya Administrator** (FAQ, pesan, layanan, dokumen, berita, dokumentasi). Pengelola boleh menghapus data operasional (jadwal luar kantor, penyesuaian jam, standar pemutakhiran, unit struktur, lokasi).
- **Alur publikasi** (`WorkflowStatus`): `draf → diperiksa → persetujuan → terbit → arsip`, plus `dikembalikan` (wajib catatan revisi). Layanan dan Dokumen: hanya Administrator yang menyetujui, menerbitkan, menarik, dan mengubah entri yang menunggu persetujuan atau terbit. Berita dan Dokumentasi: pengelola menerbitkan sendiri. FAQ punya status sendiri (`draft`/`published`/`unpublished` + *Untuk warga* + *Tampilkan di portal*). Struktur tanpa alur.
- **Peran informasi** (Penyedia, Pemeriksa, Penyetuju, Pengelola publikasi) di profil akun hanya keterangan, tidak memengaruhi akses (D-027).

---

## 10. API dan backend

Kontrak lengkap ada di [`docs/api.md`](docs/api.md). Ringkasan:

### 10.1 Konvensi

- JSON polos tanpa amplop. Galat `{"message": "…"}`; validasi `422` `{"message", "errors": {isian: [...]}}` dengan kunci bertingkat (`hari.1.selesai`).
- ID ULID huruf kecil; waktu ISO 8601; tanggal `YYYY-MM-DD`; jam `HH:MM` WITA. Server mengisi `id`, `dibuat`, `diperbarui`, `urutan`.
- Semua rute API `Cache-Control: no-store`. API publik menolak query parameter (`422`) dan metode tulis (`405`), tanpa sesi dan cookie.
- `503` di API publik bila data tidak bisa dibaca (tidak pernah diganti daftar kosong).
- Log aktivitas: tabel `activities` (`user_id`, `module`, `action`, `target` ≤120), ditulis dalam transaksi yang sama dengan perubahan; tanpa perubahan tidak dicatat. Tidak dicatat: Pengaturan Situs, profil akun, kegiatan Absensi, pencarian kendaraan. Perubahan akun masuk log aplikasi (`account.*`, `auth.*`).
- Data pribadi tidak masuk log: pesan hanya nomor tiket; pencarian kendaraan tidak menyimpan nomor polisi, nama, atau nomor rangka.

### 10.2 Autentikasi (D-007, D-008)

- Laravel Sanctum mode SPA: `GET /sanctum/csrf-cookie` → `POST /api/masuk` `{nip, sandi, ingat}` dengan header `X-XSRF-TOKEN` → `GET /api/sesi` → `POST /api/keluar`. Token bearer diabaikan.
- Sesi berakhir 8 jam tanpa aktivitas atau saat peramban ditutup; "Ingat perangkat ini" 7 hari.
- Batas: 5 percobaan per NIP+IP per menit, 20 per NIP per 15 menit, 30 login per IP per menit.
- Kata sandi minimal 12 karakter berisi huruf dan angka. Status akun `pending → active → suspended/deactivated`. Akun baru dibuat Administrator, pemilik akun memilih sandi lewat **tautan aktivasi** (60 menit, sekali pakai). Tidak ada endpoint ganti/lupa sandi; atur ulang = tautan aktivasi baru.
- Perintah: `php artisan pintu:create-admin <nip> "<nama>"` (dengan `--no-interaction` mencetak tautan aktivasi), `php artisan pintu:activation-link <nip> [--reset]`.

### 10.3 Batas laju (`AppServiceProvider`)

| Limiter | Batas |
|---|---|
| `public-api` | 300/menit/IP (`PINTU_PUBLIC_API_REQUESTS_PER_MINUTE`), dipakai bersama semua API publik |
| `public-messages` | 5 pesan/jam/IP |
| `login` | 30/menit/IP (+ batas per NIP di `LoginRequest`) |
| `kendaraan` | 30/menit per pegawai |
| `public-kendaraan` | 5/menit dan 20/jam per IP, plus 30/menit untuk seluruh portal |
| `uploads` | 30/menit per pegawai (juga clock-in) |
| `public-penerimaan` | 60/menit/IP untuk laporan penerimaan publik (D-048), selain `public-api` |

### 10.4 Endpoint (ringkas)

- **Sistem/akun:** `GET /api/health`, `POST /api/masuk`, `GET /api/sesi`, `POST /api/keluar`, `/api/akun` (index, store, `PATCH /{id}`, `PUT /{id}/status`, `POST /{id}/tautan-aktivasi`; khusus Administrator), `GET|PUT /api/pengaturan/akun` (profil sendiri), halaman web `GET|POST /aktivasi/{user}`.
- **Koleksi `/api/koleksi/{nama}`** (index, store, show, `PATCH`): `faq` (+`urutan`, `hapus`), `keliling` (+`hapus`, `salin-minggu`, `salin-hari`, `GET urutan-papan`, `PUT urutan-papan/{tanggal}`), `lokasi` (+`hapus`), `penyesuaian` (+`hapus`), `pesan` (+`hapus`), `layanan` (+`urutan`, `hapus`), `dokumen` (+`hapus`), `berita` (+`hapus`), `dokumentasi` (+`hapus`), `unit` (+`urutan`, `hapus`), `aplikasi` (+`urutan`, `hapus`; D-054), `halaman` (hanya baca/sunting), `pemutakhiran` (+`urutan`, `hapus`).
- **Pengaturan:** `GET /api/pengaturan` (waktu simpan), `GET|PUT /api/pengaturan/{jam|papan|statistik|kontak|visiMisi|profil|beranda|situs}` (gate `SettingPolicy`; `situs` disimpan hanya Administrator).
- **Lain-lain:** `POST /api/unggah`, `GET /api/berkas/{id}` (berkas privat), `GET /api/aktivitas?limit=`, `GET /api/kendaraan/{nopol}`, `/api/absensi/kegiatan` (Administrator), `GET /api/absen`, `POST /api/absen/{id}` (clock-in).
- **Laporan Penerimaan (D-047, `RevenueImportPolicy`, modul `penerimaan`):** `GET /api/penerimaan/laporan?dari&sampai&<saringan>[]&tunggakan`, `GET /api/penerimaan/loket`, `GET|POST /api/penerimaan/impor`, `POST /api/penerimaan/impor/pratinjau` (multipart `berkas` + `loket`, tanpa menyimpan), `POST /api/penerimaan/impor/hapus`, `PATCH /api/penerimaan/impor/{id}` `{tampil}` (khusus Administrator, D-048). Pratinjau dan impor memakai batas `uploads`.
- **Pemetaan PKB (D-052, `PotentialImportPolicy`, modul `pemetaan`):** `GET|POST /api/pemetaan/impor`, `POST /api/pemetaan/impor/{id}/baris` (`{mulai, baris}` maks. 1.000), `POST /api/pemetaan/impor/{id}/selesai`, `DELETE /api/pemetaan/impor` (Administrator), `PATCH /api/pemetaan/impor` `{tampil}` (Administrator, Irisan 2), `GET /api/pemetaan/kolom`, `PUT /api/pemetaan/kolom` `{wilayah, sasaran}` (Administrator; pengaturan `pemetaan`), `GET /api/pemetaan/ringkasan?bulan=`, `GET /api/pemetaan/kendaraan` (saringan, `kelompok=belum-taat` untuk semua selain taat, `q` dengan `%`/`_` apa adanya, maks. 200, tagihan terbesar dulu), `GET /api/pemetaan/kendaraan/{id}` (semua kolom termasuk KTP).
- **Antrean Online (D-051, `QueueTicketPolicy`/`QueueServicePolicy`, modul `antrean`):** `GET /api/antrean/layanan`, `PUT /api/antrean/layanan/{id}` (Administrator), `GET|POST /api/antrean/tiket` (`?tanggal=`), `POST /api/antrean/tiket/{id}/cetak-ulang`, `POST /api/antrean/tiket/{id}/batal`; Irisan 2: `GET|POST /api/antrean/loket`, `PUT|DELETE /api/antrean/loket/{id}` (Administrator), `GET /api/antrean/loket/{id}/antrean`, `POST /api/antrean/loket/{id}/panggil` (`{tiket?}`), `POST /api/antrean/tiket/{id}/{panggil-ulang|tunda|selesai}`, `GET /api/antrean/cari?q=`, `GET|POST /api/antrean/layar`, `DELETE /api/antrean/layar/{id}` (Administrator), dan `GET /api/layar` untuk layar TV (bearer token dari tautan pemasangan, tanpa sesi); Irisan 3 (`QueueTicketPolicy::mark`): `GET /api/antrean/pos/{pos}` dan `POST /api/antrean/pos/{pos}/tandai` (`{tiket}`; pos `penetapan`, `server`, `pelunasan`, `cetak-skpd`, `cetak-stnk`), `POST /api/antrean/tiket/{id}/perubahan-data`, `POST /api/antrean/tiket/{id}/urungkan`; Irisan 4: `GET /api/antrean/arsip` (`q`, `dari`, `sampai`, `layanan`, `status`, `tahap`; maks. 200), `GET /api/antrean/tiket/{id}` (dengan `riwayat`), `DELETE /api/antrean/tiket/{id}` (Administrator, `QueueTicketPolicy::delete`; hanya tiket selesai/batal dari hari sebelumnya), `GET /api/antrean/ringkasan` dan `GET /api/antrean/ringkasan/tiket` (`dari`, `sampai`, maks. 31 hari).
- **Publik `/api/public/*`:** `faq`, `keliling`, `jam`, `papan`, `statistik`, `kontak`, `visi-misi`, `profil`, `layanan`, `dokumen`, `dokumentasi`, `struktur`, `aplikasi`, `berita`, `berita/{slug}`, `beranda`, `situs`, `halaman/{slug}`, `antrean/{kode}` (halaman lacak dari QR tiket, D-051), `penerimaan` (GET; satu-satunya yang menerima parameter: `tanggal` atau `bulan`, D-048, D-049), `penerimaan/ringkasan` (GET tanpa parameter; tahun berjalan per bulan untuk dasbor beranda, bulan yang disamarkan tidak dijumlah, D-055), `pemetaan` (GET tanpa parameter; angka gabungan kota/kecamatan/kelurahan dengan penyamaran, `{tersedia:false}` sebelum dinyalakan, D-052); `POST pesan` (formulir kontak, honeypot `kode_tambahan`); `POST kendaraan` (cek biaya pajak).

### 10.5 Model dan tabel

Semua model ULID kecuali `Setting`. Kolom berbahasa Indonesia.

| Model | Tabel | Catatan |
|---|---|---|
| User | `users` | `nip` char(18) unik, `role`, `status`, `modules` (json), `jabatan`, `unit`, `peran_informasi` |
| Activity | `activities` | log aktivitas per modul |
| Faq | `faqs` | `FaqStatus`, `publik`, `tampil`, `urutan` |
| MobileServiceStop | `mobile_service_stops` | `jenis` (`ServicePointKind`: keliling/gerai/sementara), `unit` 01–03, `nama`, `tanggal_mulai`, `tanggal_selesai`, `hari` (json, 0=Minggu), jam, lokasi, `aktif` |
| ServiceLocation | `service_locations` | daftar lokasi (nama unik tanpa beda huruf) |
| ServiceBoardOrder | `service_board_orders` | urutan *Di luar kantor hari ini* di papan Beranda per tanggal (D-053): `tanggal` unik, `urutan` TEXT dengan cast array (ID jadwal dari atas) |
| ScheduleAdjustment | `schedule_adjustments` | penyesuaian jam per tanggal (`tutup`/`jam-khusus`) |
| Message, MessageResponse | `messages`, `message_responses` | pesan publik dan tanggapan (append-only) |
| Service | `services` | persyaratan layanan, beralur |
| Document | `documents` | regulasi dan unduhan, beralur |
| Article | `articles` | berita dan pengumuman, beralur, `slug` |
| Documentation | `documentations` | dokumentasi kegiatan, beralur, foto ≤30 |
| OrganizationUnit | `organization_units` | struktur organisasi (`induk` tanpa FK) |
| AgencyApp | `agency_apps` | aplikasi dan situs BPAD/UPTD di halaman Aplikasi & Situs (D-054): `jenis` situs/android, `pengelola` bpad/uptd, `logo` unggahan atau jalur logo bawaan `assets/img/aplikasi/…` (`logoUrl()` memberi alamat lengkap), `urutan` |
| StaticPage | `static_pages` | kebijakan-privasi, syarat-ketentuan |
| UpdateStandard | `update_standards` | standar pemutakhiran |
| Upload | `uploads` | catatan unggahan, `Prunable` (tak terpakai >1 hari) |
| FieldActivity, FieldAttendance | `field_activities` (+pivot `field_activity_user`), `field_attendances` | absensi lapangan |
| RevenueImport | `revenue_imports` | satu berkas STS yang diimpor: `loket` (kunci), `nama_loket` (saat impor), `nama_berkas`, tanggal awal/akhir, `jumlah_transaksi`, `jumlah_dilewati`, `total`, `tampil` (bawaan mati; untuk portal), `user_id` |
| PotentialImport | `potential_imports` | impor berkas Data Potensi (D-052): `berkas`, `jumlah`, `diterima`, `tanggal_penetapan`, `diimpor_oleh`, `selesai_pada` (null = masih menerima bagian), `tampil` (angka gabungannya di portal, bawaan `false`, Irisan 2); yang ditampilkan = selesai paling akhir (`current()`), impor selesai menggantikan yang lain |
| PotentialVehicle | `potential_vehicles` | satu kendaraan berkas Data Potensi dengan **semua 43 kolom termasuk identitas** (keputusan pemilik); `ktp` dan `telepon` cast `encrypted`; turunan: `kecamatan` dari daftar resmi kelurahan (`App\Enums\Kecamatan`), `roda` (`VehicleWheels`), `kelompok` (`TaxCompliance`), `bulan_jatuh_tempo` (dari SD NOTICE), `tagihan`; tanpa timestamps, disisipkan massal |
| — | `potential_summaries` | hitungan per impor saat selesai (bulan, kecamatan, kelurahan, roda, kelompok, tahun tunggak, jenis, hasil kunjungan) dengan rupiahnya; ringkasan dashboard membaca tabel ini (±60 ms untuk 28.725 kendaraan) |
| RevenueTransaction | `revenue_transactions` | id bigint, tanpa timestamps, `tanggal` disimpan `Y-m-d` (`$dateFormat`); dimensi laporan, 12 komponen rupiah + `jumlah`, `sidik` unik (HMAC); terhapus bersama impornya. **Tanpa** nama pemilik, nomor polisi, alamat |
| QueueService | `queue_services` | layanan antrean (D-051): `kode` A–D unik, `nama`, `cakupan`, `tahap` (json, urut alur), `aktif`, `urutan`; isi awal dari migrasi |
| QueueTicket | `queue_tickets` | nomor antrean: `kode_lacak` acak 22 karakter unik (QR), `tanggal` WITA disimpan string `Y-m-d` (tanpa cast date), `urut` unik per layanan dan tanggal, `nomor` (A-001), `nopol` ringkas (DH1234AB, diindeks, disimpan sampai dihapus Administrator), `tahap_layanan` (json salinan saat dicetak), `tahap`, `status` (`menunggu`/`dipanggil`/`ditunda`/`perubahan-data`/`selesai`/`batal`), `queue_counter_id` (loket yang memanggil), `dipanggil_pada`, `jumlah_panggil`, `masuk_tahap_pada` (urutan panggil), `selesai_pada`, `alasan_batal`, `dicetak_oleh` |
| QueueEvent | `queue_events` | riwayat tiket tanpa `updated_at`: `aksi` (dicetak, dicetak ulang, dibatalkan, dipanggil, dipanggil ulang, ditunda, tahap selesai, selesai, perlu perubahan data, perubahan data selesai, diurungkan), `tahap`, `keterangan`, `queue_counter_id`, `otomatis`, `keadaan` (json `{sebelum, sesudah}` pada tanda di pos, untuk Urungkan; Irisan 3), `user_id` |
| QueueCounter | `queue_counters` | loket pemanggil (Irisan 2): `nama`, `tahap` (pendaftaran/kwitansi/penyerahan), `layanan` (json kode, kosong = semua), `nama_suara`, `aktif`, `urutan`; tiga loket awal dari migrasi |
| QueueDisplay | `queue_displays` | layar TV (Irisan 2): `nama`, `token_hash` (SHA-256, token hanya ditampilkan sekali), `terakhir_terlihat`, `dibuat_oleh` |
| Setting | `settings` | kunci `name`, `value` TEXT dengan cast array (bukan kolom json: MySQL json mengubah urutan kunci); termasuk `pemetaan` = kolom tabel Pemetaan PKB `{sasaran, wilayah}` (D-052) |

**Kunci `settings`:** `jam`, `papan`, `statistik`, `kontak`, `visiMisi`, `profil`, `situs`, `beranda` (`tarif` lama dibiarkan di DB). Default sebelum pertama disimpan: `Setting::INITIAL_*`/`UNSAVED_*`. `Setting::upgradeHomepage()` membaca dokumen Beranda lama ke bentuk terbaru (logo mitra, tautan kanal, video, kata bergilir).

### 10.6 Unggahan (D-028)

- `POST /api/unggah` (multipart `modul` + `berkas`). Tujuan (`UploadTarget`): `berita`, `dokumentasi`, `dokumen`, `unit`, `visiMisi`, `profil`, `beranda`, `aplikasi` (publik) dan `absensi` (privat).
- Tipe dibaca dari **isi** berkas (finfo). Gambar JPG/PNG/WebP ≤5 MB/25 MP, disimpan ulang tanpa metadata, maks. 1600 px. Dokumen PDF/DOCX/XLSX ≤10 MB; maklumat hanya PDF.
- Satu unggahan untuk satu entri; berkas yang diganti/dikosongkan/entrinya dihapus ikut terhapus (`SyncUploads`). Unggahan tak terpakai dihapus setelah sehari (`model:prune` harian di `routes/console.php`).
- Lokal memakai disk lokal; produksi Laravel Cloud Object Storage (bucket privat + publik).

---

## 11. Integrasi eksternal

### 11.1 API NJKB BPAD NTT (data kendaraan)

- Repository terpisah **`D:\Herd\njkb-api-ntt`** (Cloudflare Worker, branch `main`). API privat `GET {NJKB_API_BASE_URL}/api/v1/vehicle/{nopol}` (`https://api.uptdpenda-kupang.web.id`), dilindungi **Cloudflare Access service token**.
- PINTU memakai **token miliknya sendiri** (principal `njkb-api-pintu`, scope `vehicle:read registration:read owner:read tax:read njkb:read`), terdaftar di `wrangler.jsonc` produksi Worker. **Jangan memakai token aplikasi lain.** Nilai token hanya di `.env` (`NJKB_ACCESS_CLIENT_ID`, `NJKB_ACCESS_CLIENT_SECRET`), diisi pemilik.
- `app/Actions/LookUpVehicle.php`: timeout 8 detik (koneksi 3), tidak mengikuti pengalihan, ulang sekali hanya untuk koneksi terputus atau 502/503/504, mematuhi `Retry-After` setelah 429 (cache `njkb:tunggu-sampai`, maks. 1 jam). Kegagalan hanya mencatat ID permintaan, status, dan kode galat.
- **Jam layanan BPAD** (D-042): API hulu hanya aktif sampai **21.00 WITA** (aktif lagi sekitar 00.01 WITA). Di luar jam PINTU menolak tanpa memanggil API (`NJKB_SERVICE_CLOSES_AT=21:00`, `NJKB_SERVICE_OPENS_AT` kosong = hanya 21.00–24.00 dianggap tutup). Teks jam di portal dan Kalkulator ditulis tetap; sunting manual bila jam berubah.
- **Nomor polisi tidak terdaftar:** BPAD menjawab HTTP 200 `{kode: "0", status: "failed", pesan: "Nomor polisi … tidak ditemukan"}`. Sejak Worker commit `6a4c4f8` (sudah di-deploy pemilik), API privat menjawab **404 `vehicle_not_found`**; PINTU dan kalkulator-pajak membaca 404 sebagai "tidak ditemukan". Endpoint publik lama Worker `/api/njkb/{nopol}` tetap 502.
- **Cara uji yang aman privasi** (cetak kode status saja, jangan pernah mencetak token atau field kendaraan): `GET …/api/v1/vehicle/DH1A?probe=1` ditolak Worker setelah Access+izin dan sebelum BPAD (harapkan `400 unsupported_query_parameter`; `401` = token/Access, `403` = izin/scope); `GET …/api/v1/vehicle/DH0ZZZ` (nomor 0 mustahil ada) menanyai BPAD tanpa kendaraan nyata (harapkan `404`). Uji kendaraan sungguhan dilakukan pemilik dengan kendaraannya sendiri.
- Deploy Worker adalah langkah pemilik: `npx wrangler deploy --env production` di `D:\Herd\njkb-api-ntt`.

### 11.2 Mesin pajak kalkulator-pajak (D-041)

- Repository terpisah **`D:\Herd\kalkulator-pajak`**. `src/lib/tax-calculator.ts` dan `src/types/tax.ts` dari commit `7e90d89` **disalin persis** ke `resources/js/pkb/lib/` dan `resources/js/pkb/types/`. Jangan menyunting salinan itu di PINTU; perbarui dengan menyalin ulang dan catat commit sumber di `resources/js/pkb/index.ts`.
- `resources/js/pkb/penetapan.ts` milik PINTU (label, ringkasan, penjaga untuk jatuh tempo di tahun sesudah tanggal bayar karena mesin sumber menghitung salah untuk kasus itu).
- Dasar hukum mesin: Pergub NTT 54/2026. Tembak RU dinonaktifkan; plat kuning tetap dihitung di loket.
- **Jangan membaca berkas rahasia** kalkulator-pajak (`.env.local`, `.dev.vars`).

### 11.3 Lain-lain

- **Laravel Cloud Object Storage** (R2 yang dikelola Laravel Cloud, bukan akun Cloudflare pemilik) untuk unggahan produksi.
- **YouTube** (D-040): video alur pelayanan diputar lewat `youtube-nocookie.com`, iframe dibuat saat dialog dibuka dan dilepas saat ditutup; API publik hanya memberi ID video.

---

## 12. Database, data, dan migrasi

- **Database lokal `pintu`** (MySQL, kredensial di `.env`) adalah database pengembangan dan sekaligus `DB_TEST_DATABASE`. Isinya kini **data milik pemilik** (dokumen Beranda yang sudah disimpan, jadwal Layanan Luar Kantor, daftar lokasi, unggahan, aktivitas). **Jangan reset atau seed ulang.**
- `composer test` aman (SQLite di memori). **`composer test:mysql` mereset `pintu`**; bila memang harus dijalankan: cadangkan dulu (`mysqldump --defaults-extra-file=<berkas opsi sementara> --single-transaction --routines --triggers --default-character-set=utf8mb4 --set-gtid-purged=OFF pintu`), catat `CHECKSUM TABLE` dan jumlah baris, jalankan tes, pulihkan (`mysql … pintu < dump`), bandingkan checksum, lalu hapus berkas opsi dan dump. Jangan mencetak kredensial. Minta izin pemilik dulu.
- Sebelum migrasi data di database pemilik, buat cadangan tabel terkait.
- **Berkas STS dari pemilik** (`docs/Data Penerimaan/`, diabaikan git) berisi nama pemilik, nomor polisi, dan alamat wajib pajak: jangan di-commit, jangan mencetak barisnya ke percakapan atau log (ringkasan angka saja). Impor hanya menyimpan kolom laporan (D-047).
- **Migrasi data awal** (bukan seeder): `insert_initial_services` (6 layanan terbit), `insert_initial_static_pages`, `insert_initial_update_standards` (8 standar), `insert_initial_documents` (9 draf tanpa berkas), `insert_initial_articles` (artikel peluncuran, draf), `insert_initial_documentations` (9 draf tanpa foto), `insert_initial_organization_units` (7 unit), `create_service_locations_table` (lokasi dari jadwal yang ada), `widen_mobile_service_stops_to_out_of_office_services` (jadwal lama → Samsat Keliling 01, nonaktif). Data contoh dashboard di peramban **tidak** dipindahkan ke server.
- **Antrean Online:** tabel `queue_services`, `queue_tickets`, `queue_events`, `queue_counters`, `queue_displays` sudah dimigrasi di `pintu` (2026-10-15), ditambah kolom `queue_events.keadaan` (Irisan 3, 2026-10-16); `queue_services` berisi A–D dan `queue_counters` tiga loket dari migrasi; tiket, riwayat, dan layar kosong (data uji pemeriksaan browser sudah dihapus).
- **Pemetaan PKB:** tabel `potential_imports`, `potential_vehicles`, `potential_summaries` sudah dimigrasi di `pintu` (2026-10-16), begitu pula kolom `potential_imports.tampil` (Irisan 2). **Pemilik menyalakan *Tampil di portal*** untuk impor itu (2026-10-16), jadi `/pemetaan` menampilkan angka gabungannya (data penetapan 16/10/2026). Isinya **impor berkas Data Potensi pemilik** (penetapan 16/10/2026, 28.725 kendaraan, dengan identitas; KTP dan telepon terenkripsi), diimpor lewat UI saat pemeriksaan. Jangan mencetak baris atau identitasnya ke percakapan atau log; berkas sumber `docs/Data Penerimaan/Data Potensi.xls` diabaikan git.
- **Seeder:** `LocalAccountSeeder` membuat admin lokal (NIP dan kata sandinya ada di berkas seeder; jangan disalin ke dokumen atau percakapan). Hanya berjalan di env `local`.

---

## 13. Pengujian dan verifikasi

| Perintah | Isi |
|---|---|
| `composer test` | Semua tes PHPUnit di SQLite di memori (±35 detik) |
| `php artisan test --compact <berkas> [--filter=…]` | Tes tertentu |
| `composer test:mysql` | Tes yang sama di MySQL `DB_TEST_DATABASE` (**mereset database**, lihat §12) |
| `npm run typecheck` | `tsc -b` |
| `npm run lint` | `oxlint resources/js` |
| `npm run build` | `tsc -b && vite build` (lihat catatan memori di §14) |
| `vendor/bin/pint --format agent <berkas>` | Format PHP berkas yang diubah saja |
| `node scripts/sync-partials.mjs --check` | Cek header/footer portal |

Kebiasaan verifikasi proyek:

- **Tes untuk setiap perilaku baru**, termasuk mode gagal. Tes portal memeriksa markup (`PortalPagesTest`), termasuk bahwa halaman dilayani byte demi byte.
- **Uji mutasi** untuk logika baru: ubah satu syarat di kode, jalankan tes terkait, pastikan gagal, lalu pulihkan berkas (periksa keluaran "RESTORE FAILED"). Mutan yang bertahan berarti tes kurang, atau kodenya mubazir (hapus bila setara).
- **Pemeriksaan di browser** pada situs lokal Herd `https://portal-uptdkotakupang.test` (Herd mengalihkan http ke https; `curl` perlu `-k`):
  - Login dashboard tanpa mengetik di layar: dari halaman situs jalankan `fetch('/sanctum/csrf-cookie')` lalu `POST /api/masuk` `{nip, sandi}` dengan header `X-XSRF-TOKEN` memakai akun `LocalAccountSeeder`. Keluar dengan `POST /api/keluar` setelah selesai.
  - Panel browser bawaan kadang tersembunyi (viewport 0×0): animasi dan `requestAnimationFrame` tidak jalan, `ResizeObserver` tidak terpicu, gambar `loading="lazy"` tidak dimuat. Ukur posisi lewat JavaScript (mis. `getBoundingClientRect`), set ukuran dengan `resize_window`, dan bekukan animasi (`getAnimations()` → `pause()`) bila perlu tangkapan layar di tengah gerak.
  - CSS/JS portal di `public/assets` tidak berversi: muat ulang dengan `fetch(url, { cache: 'reload' })` lalu `location.reload()` sebelum menilai tampilan.
  - Untuk memeriksa portal tanpa menulis data: timpa `window.fetch` di halaman, suntik ulang `main.js`, lalu kirim `DOMContentLoaded`.

---

## 14. Lingkungan pengembangan dan jebakannya

- **Mesin pemilik:** Windows 11, Laravel Herd (`portal-uptdkotakupang.test`), PowerShell dan Git Bash, Node 24, PHP 8.3. Klien MySQL dari Laragon ada di PATH.
- **Memori commit Windows sering hampir habis.** `vite build` bisa gagal ("os error 1450" atau "memory allocation failed"). Tutup tab panel browser agen, lalu build dengan:
  `RAYON_NUM_THREADS=1 TOKIO_WORKER_THREADS=1 NODE_OPTIONS=--max-old-space-size=768 node node_modules/vite/bin/vite.js build`.
  Jangan menutup aplikasi pemilik (mis. aplikasi node lain yang sedang berjalan). Pesan "Committing semi space failed" dari Node juga tanda memori commit habis, bukan heap terlalu kecil; periksa dengan `Get-Counter '\Memory\Committed Bytes','\Memory\Commit Limit'`.
- **Batas PHP Herd lokal:** `upload_max_filesize = 2M`, `post_max_size = 8M`, `memory_limit = 128M` (`%USERPROFILE%\.config\herd\bin\php84\php.ini`, juga php81). Berkas STS harian (±200 KB) lolos, tetapi berkas bulanan (4,3 MB) ditolak sebelum sampai ke aplikasi sampai batas unggah dinaikkan (pengaturan PHP di Herd). Di Laravel Cloud batasnya perlu sedikitnya 10 MB (D-047).
- **Akhir baris campuran:** repository menyimpan LF (`core.autocrlf=true`). Di working tree, berkas PHP dan `vite.config.ts` LF, sedangkan Blade portal, `main.js`, CSS portal, dan berkas TS dashboard CRLF. Alat Edit menjaga akhir baris berkas.
  - `grep`/`sed`/`cat -A` di Git Bash **menyembunyikan CR** dan `grep -c $'\r'` memberi hasil salah. Hitung akhir baris dengan Node (`/\r\n/g`).
  - `sed -i` di Git Bash diam-diam mengubah CRLF menjadi LF (diff seluruh berkas). Hindari, atau pulihkan CRLF dengan Node.
  - Selalu jalankan `git diff --check` dan `git diff --stat` sebelum commit.
- **Jebakan skrip penyambung:** `String.replace(a, "teks")` di JavaScript menafsirkan `$$`, `$&`, `` $` ``, `$'` di teks pengganti (pernah merusak `$$(` di `main.js`). Pakai `s.split(a).join(b)` atau `s.replace(a, () => b)`. Garis miring terbalik di `node -e` lewat shell bisa hilang (namespace PHP rusak); pakai alat Edit atau berkas skrip.
- **Tes:** `getJson`/`postJson` Laravel tidak mengirim cookie kecuali `withCredentials()`. `JsonResource::response()` sudah menjawab 201 untuk model baru. ULID berurutan menurut waktu pembuatan, jadi buat baris uji tidak urut nama agar mutan urutan tidak lolos kebetulan.
- **Sesi agen lain** bisa mengubah berkas yang sama bersamaan (lihat §4).
- **Kolom JSON MySQL menyusun ulang kunci objek** (SQLite tidak), jadi array yang dibaca kembali tidak sama `===` dengan yang disimpan meskipun isinya sama. Tes SQLite tidak menangkapnya; bandingkan tanpa urutan kunci (`ksort`). Urungkan Antrean Online pernah gagal karena ini.
- **Grid dashboard tanpa kolom eksplisit** (`grid gap-4` dengan `lg:grid-cols-…`) memakai kolom `auto` di layar kecil, sehingga tabel dalam `overflow-x-auto` melebarkan halaman. Beri `grid-cols-[minmax(0,1fr)]`.
- **Panel browser bawaan memblokir permintaan ke origin lain** (`ERR_BLOCKED_BY_CLIENT`, termasuk `http://127.0.0.1:port`). Untuk menguji impor berkas lewat UI, salin berkas sementara ke `public/build/` (diabaikan git) dengan nama acak, ambil dengan `fetch` dari origin yang sama, masukkan ke `<input type=file>` lewat `DataTransfer`, lalu hapus salinannya.
- **Vite dev server berjalan** (`npm run dev`, berkas `public/hot`): `/skrip/portal` dan aset dashboard dialihkan ke `https://portal-uptdkotakupang.test:5173`, yang ditolak panel browser bawaan (lintas asal, `ERR_BLOCKED_BY_CLIENT`), jadi komponen React portal macet di "Memuat…". Jangan hentikan dev server pemilik; untuk memeriksa, build Vite (`public/build`, `public/hot` tetap ada) lalu sisipkan `<script type="module" src="/build/assets/main-….js">` (nama dari `public/build/manifest.json`) ke halaman lewat JavaScript.
- **Herd mati** (situs `.test` tidak menjawab, tidak ada proses nginx/php-cgi): untuk pemeriksaan di panel browser, build Vite lalu jalankan `php artisan serve --port=8010` lewat `.claude/launch.json` sementara dengan `cmd /c set "APP_URL=http://127.0.0.1:8010" && set "SESSION_DOMAIN=" && set "SESSION_SECURE_COOKIE=false" && set "SANCTUM_STATEFUL_DOMAINS=127.0.0.1:8010" && php artisan serve …` (variabel lingkungan proses mengalahkan `.env`), buka `http://127.0.0.1:8010`, lalu hapus berkas itu lagi. Di panel browser, tombol yang diklik saat panel tidak menggambar bisa gagal; kirim formulir dengan `form.requestSubmit()`.
- **Sel tabel shadcn (`TableHead`, `TableCell`) memakai `whitespace-nowrap`;** `min-w-*` saja tidak membuat teks panjang membungkus. Tambahkan `whitespace-normal` pada sel yang boleh membungkus (Pemilik di daftar sasaran Pemetaan PKB semula 566 px).
- **Teks `sr-only` di dalam wadah gulir:** `.sr-only` memakai `position: absolute`; bila wadah `overflow-x: auto` tidak `position: relative`, teks itu keluar dari wadah dan melebarkan halaman (terjadi di tabel kelurahan `/pemetaan` pada ponsel).
- **Panel browser bawaan** pernah menolak membuka situs lokal (navigasi macet lalu "denied or failed"); pulih setelah pemilik me-restart Claude Desktop.
- **Tata letak dashboard:** `TabsList` shadcn memakai `group-data-[orientation=horizontal]/tabs:h-9`, yang mengalahkan `h-auto`, jadi deretan tab yang dilipat (`flex-wrap`) keluar dari latarnya; geser ke samping dalam pembungkus `overflow-x-auto` (Laporan Penerimaan). Tabel lebar di dalam `grid` melebarkan kolom grid dan halaman; pakai `flex flex-col` atau `min-w-0` pada pembungkusnya.

---

## 15. Deploy dan checklist go-live

Belum pernah di-deploy ke Laravel Cloud; pemilik yang memutuskan dan menjalankannya. Panduan ada di [README › Deploy ke Laravel Cloud](README.md#deploy-ke-laravel-cloud):

- Root directory = root repository. Build: `composer install --no-dev && npm ci && npm run build && php artisan optimize`. Deploy: `php artisan migrate --force`.
- Laravel MySQL; env `APP_URL` https, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `SESSION_LIFETIME=480`, `SESSION_EXPIRE_ON_CLOSE=true`, `CACHE_STORE=database`, token NJKB.
- Administrator pertama: `php artisan pintu:create-admin <nip> "<nama>" --no-interaction` di tab *Commands*; kirim tautan aktivasi hanya ke pemilik akun.

Checklist sebelum go-live (dari D-002, D-006, D-028, D-034, D-047):

- [ ] Aktifkan rate limiting di edge Laravel Cloud (di Cloud, Laravel memercayai semua proxy sehingga IP bisa dipalsukan).
- [ ] Buat dua bucket Object Storage produksi (privat + publik) dan nyalakan *Scheduler* (`model:prune`).
- [ ] Pastikan `upload_max_filesize` dan `post_max_size` ≥ 10 MB (unggahan D-028 dan berkas STS bulanan D-047).
- [ ] `APP_KEY` produksi tidak diganti setelah berkas STS diimpor (sidik transaksi ganda memakainya, D-047).
- [ ] Konfirmasi lokasi data ke unit hukum/Diskominfo (PP 71/2019; R2 dan database tanpa wilayah Indonesia).
- [ ] Tentukan cadangan bucket dan database.
- [ ] Uji Absensi (kamera dan GPS butuh HTTPS) di Laravel Cloud.
- [ ] Arahkan `pintu.uptdpenda-kupang.web.id` ke Laravel Cloud, lalu matikan deploy otomatis Cloudflare Pages.
- [ ] Isi konten yang masih contoh/draf (lihat §18).

---

## 16. Ringkasan keputusan D-001 sampai D-057

Rincian lengkap di [`docs/decisions.md`](docs/decisions.md). "Pemilik" = keputusan langsung pemilik.

| ID | Tanggal | Keputusan |
|---|---|---|
| D-001 | 10-13 | Dashboard React 19 + JSON API; tanpa Livewire/Inertia |
| D-002 | 10-13 | Satu aplikasi Laravel di root; portal Blade berisi HTML lama; `*.html` → 301; produksi tetap Cloudflare Pages sampai cutover |
| D-003 | 10-13 | FAQ publik diurutkan menurut `urutan` dashboard, lalu ID |
| D-004 | 10-13 | Nilai teknis FAQ: ULID, pertanyaan ≤200, jawaban ≤800, ≤100 entri (lebih → 503), `no-store`, query ditolak (laju 60 diganti 300 di D-013) |
| D-005 | 10-13 | `composer test` SQLite; `test:mysql` mereset database lokal |
| D-006 | 10-13 | Hosting Laravel Cloud; rate limiting edge wajib sebelum go-live |
| D-007 | 10-13 | Login pegawai Sanctum SPA, NIP 18 digit, status akun, batas percobaan, sandi ≥12, `pintu:create-admin` |
| D-008 | 10-13 | Akun Pegawai dikelola Administrator; tautan aktivasi 60 menit; tangguhkan/nonaktifkan; Admin tidak bisa mengubah dirinya |
| D-009 | 10-13 | Halaman login dan aktivasi tepat setinggi layar |
| D-010 | 10-13 | Penugasan modul untuk Pegawai UPTD (kini 17 modul); Administrator semua modul |
| D-011 | 10-13 | Tanya Jawab modul server pertama (mode campuran); hapus hanya Admin; log `activities` |
| D-012 | 10-13 | Beranda memuat FAQ dari API publik, teks polos, *Coba lagi* bila gagal |
| D-013 | 10-13 | Samsat Keliling di server; laju API publik 300/menit/IP (model per hari diganti D-043) |
| D-014 | 10-13 | Jam Pelayanan + penyesuaian per tanggal di server; status buka/tutup portal dari API |
| D-015 | 10-13 | Papan Layanan di server; tampil di beranda hanya pada hari pembaruan (WITA), diganti D-050 |
| D-016 | 10-13 | Pesan & Pengaduan: formulir publik, kontak wajib, honeypot + 5/jam/IP, hapus hanya Admin |
| D-017 | 10-13 | Tarif & Simulasi di server (**dipensiunkan D-041**) |
| D-018 | 10-13 | Statistik Layanan: 4 kategori × 4 angka × 12 bulan (3 kategori sejak D-055); hanya status Terverifikasi yang terbit |
| D-019 | 10-13 | Kontak & Kanal di server; aturan tautan `PortalLink` |
| D-020 | 10-13 | Visi & Misi di server, teks resmi menjadi isi awal (unggahan maklumat ditunda → D-029) |
| D-021 | 10-14 | Tentang UPTD di server (unggahan foto ditunda → D-029) |
| D-022 | 10-14 | Persyaratan Layanan dengan alur publikasi; Administrator menerbitkan; 6 layanan awal Terbit |
| D-023 | 10-14 | Teks Beranda di server; teks HTML tetap sebagai isi awal (pola inti portal) |
| D-024 | 10-14 | Halaman Statis (2 halaman) di server; validasi `RichText` ketat |
| D-025 | 10-14 | Jadwal Pemutakhiran (internal), 8 standar awal |
| D-026 | 10-14 | Pengaturan Situs: hanya Administrator menyimpan; portal membaca bilah pengumuman dan footer |
| D-027 | 10-14 | Profil akun: nama/surel mengikuti akun; jabatan, unit, peran informasi di `users` |
| D-028 | 10-14 | Unggahan di Laravel Cloud Object Storage (bucket privat + publik), tipe dari isi berkas |
| D-029 | 10-14 | Foto Tentang UPTD dan maklumat diunggah; profil dari Renstra BPAD 2025–2029 dan RPJMD NTT 2025–2029; nama unit Renstra |
| D-030 | 10-14 | Regulasi & Unduhan beralur (Administrator menerbitkan); terbit wajib berkas/tautan; 9 draf awal |
| D-031 | 10-14 | Berita & Pengumuman; pengelola menerbitkan sendiri; halaman `/berita/{slug}` |
| D-032 | 10-14 | Dokumentasi Kegiatan; terbit wajib ≥1 foto; galeri per bulan |
| D-033 | 10-14 | Struktur Organisasi tanpa alur; NIP pejabat tidak pernah ke API publik |
| D-034 | 10-14 | Absensi Lapangan: PWA `/absen`, clock-in petugas yang ditugaskan, swafoto + GPS dalam radius |
| D-035 | 10-14 | Navbar portal berbentuk notch; tema dashboard bawaan Terang; **jangan deploy dulu** |
| D-036 | 10-14 | Ukuran notch mengikuti referensi Kage (62 px, sudut 22 px) |
| D-037 | 10-14 | Mitra satu atap memakai logo unggahan; inisial sebagai cadangan |
| D-038 | 10-14 | Isi kanal layanan beranda bisa diberi tautan (situs luar di tab baru) |
| D-039 | 10-14 | Teks alur publikasi di portal dan Login mengikuti alur yang berlaku |
| D-040 | 10-14 | Video alur pelayanan (YouTube nocookie) diatur dari Dashboard › Beranda |
| D-041 | 10-14 | Kalkulator PKB dan cek biaya pajak portal memakai mesin kalkulator-pajak + API NJKB; Tarif & Simulasi dipensiunkan |
| D-042 | 10-14 | Jam layanan data kendaraan BPAD sampai 21.00 WITA; di luar jam ditolak tanpa memanggil API |
| D-043 | 10-15 | Layanan Luar Kantor: Samsat Keliling 01–03 per tanggal, gerai tetap, loket sementara; salin minggu (nonaktif) |
| D-044 | 10-15 | Daftar lokasi (lokasi baru otomatis masuk) dan salin jadwal per hari (≤14 hari, status mengikuti asal) |
| D-045 | 10-15 | Portal ke React bertahap di atas Blade; kata bergilir di judul pembuka (gulir vertikal, berulang dengan jeda 10 detik) |
| D-046 | 10-15 | Logo Ditlantas (kiri) dan Jasa Raharja (kanan) keluar dari balik lambang NTT; klik lambang untuk menyembunyikan/memunculkan |
| D-047 | 10-15 | Laporan Penerimaan dari berkas STS BPAD per loket (menu dan modul `penerimaan` terpisah, pemilik); identitas wajib pajak tidak disimpan, transaksi ganda dikenali sidik HMAC; data awal September dan 14 Oktober = Loket SAMSAT kantor (pemilik). Portal menyusul: per impor setelah diperiksa, sampai kelurahan dengan kelompok <3 transaksi disamarkan (pemilik) |
| D-048 | 10-15 | Halaman portal `/penerimaan` (menu Informasi + tautan Transparansi beranda, pemilik); hanya impor yang dinyalakan Administrator (pemilik); saringan utama loket/jenis daftar/jenis kendaraan/kecamatan/kelurahan (pemilik); total + komponen + CSV (pemilik); penyamaran <3 transaksi di server dengan penggabungan kedua; API publik menerima parameter terdaftar (pengecualian D-004) |
| D-049 | 10-15 | Laporan Penerimaan portal sebagai tabel tetap: per kelompok hanya transaksi dan jumlah, komponen hanya untuk seluruh periode (pemilik); Harian total saja, Bulanan dengan rincian, saringan dan rentang dihapus dari portal (pemilik); kelurahan disamarkan per kecamatan, hari disamarkan sama di laporan harian dan bulanan; API hanya `tanggal` atau `bulan` |
| D-050 | 10-15 | Papan Layanan berlaku setiap hari: semua isi terakhir tampil sampai diubah (pemilik); selama loket tutup menurut Jam Pelayanan semua loket Tutup tanpa waktu tunggu (pemilik); label "Diperbarui hari ini" di hari selain hari penyimpanan (pemilik) |
| D-051 | 10-15 | Antrean Online (rencana disetujui; Irisan 1–4 dibangun): kode A RU/Duplikat, B Pengesahan, C BBN/Mutasi, D Pembayaran Online, nomor A-001 harian, cetak 2 lembar thermal 80 mm hanya di Meja Informasi, status per pos lewat pindai QR (tidak wajib) lalu API Samsat Online, layar TV + suara tanpa nopol, satu loket per tahap, panggil berikutnya menunda otomatis, akun pribadi, nopol disimpan tanpa batas dan tampil apa adanya, tiket lintas hari, Papan tetap manual, uji coba paralel, pustaka QR `uqr`, pindaian di pos langsung menandai dengan Urungkan, perubahan data lewat pos Server tersendiri, Administrator menghapus tiket seluruhnya, arsip dan ringkasan untuk semua petugas Antrean, ringkasan harian atau rentang ≤31 hari, CSV dengan daftar tiket (semua pemilik) |
| D-052 | 10-16 | Pemetaan PKB dari berkas Data Potensi BPAD (Irisan 1 dan 2 dibangun): dashboard menyimpan semua kolom termasuk identitas untuk sasaran penagihan, portal `/pemetaan` hanya angka gabungan kota/kecamatan/kelurahan, angka 1–2 kendaraan disamarkan beserta angka yang bisa menghitungnya balik (per total lalu semua total digabung), tampil setelah Administrator menyalakannya; roda tiga semula digabung roda empat per wilayah lalu dipisah di semua tabel, kolom tabel dipilih Administrator, daftar sasaran penagihan mulai dari Belum taat, seluruh data saja, menu Informasi dan beranda, panel kota menyebut jenis tidak tercatat (pemilik); impor baru menggantikan data lama (data master setahun menyusul), peta skematis dulu, lalu (2026-10-16) batas sungguhan: sebentar dari geoportal Kementerian Pertanian, kemudian Batas Desa/Kelurahan BIG 1:10.000 edisi 2022 dari berkas pemilik dengan peta kelurahan per kecamatan (semua pemilik); kecamatan dari daftar resmi kelurahan; impor dibaca di peramban per 1.000 baris |
| D-053 | 10-16 | Papan Beranda: kartu kiri setinggi isinya, kartu *Di luar kantor hari ini* mengikuti tinggi dua baris kiri dan meringkas sisanya (layar >980 px); urutannya diatur per tanggal di Layanan Luar Kantor (pemilik), hanya untuk papan, halaman Jadwal tetap urut jam (pemilik) |
| D-054 | 10-17 | Halaman Aplikasi & Situs `/aplikasi` di menu Layanan (pemilik), satu daftar sesuai urutan dashboard (pemilik); modul baru *Aplikasi & Situs* di Alat Bantu yang bisa ditugaskan (pemilik); lima aplikasi awal (PINTU, PRO NTT, SIKAP, SIPAS, Tilang / Razia) dengan logo diunduh dari sumber masing-masing, 240 px WebP (pemilik mengizinkan); aplikasi Android ditautkan ke Google Play |
| D-055 | 10-17 | Dasbor transparansi beranda: tab Penerimaan pajak otomatis dari Laporan Penerimaan yang tampil di portal, tahun berjalan dengan grafik per bulan (pemilik); tab baru Pemetaan PKB, empat angka dan grafik persen menunggak per kecamatan (pemilik); kedua tab tersembunyi sampai datanya ditampilkan; tautan "Lihat …" di bawah judul dihapus, kini di dalam panel; kategori Penerimaan pajak dihapus dari Statistik Layanan (pemilik) |
| D-056 | 10-17 | Setiap kategori Statistik Layanan punya sakelar Tampil di portal (pemilik), bawaan menyala; yang dimatikan tidak muncul sebagai tab dasbor beranda apa pun status datanya; status Terverifikasi (D-018) tetap berlaku |
| D-057 | 10-17 | Rencana tema (`docs/rencana-tema-situs.md`): portal dan dashboard punya tema masing-masing dari tab Tampilan di Pengaturan, khusus Administrator (pemilik); skala portal lewat `zoom` (pemilik); logo tetap biru PINTU (pemilik); huruf terkurasi di-host sendiri (pemilik); dua stylesheet buatan server lewat `<link>` tetap; Irisan 1 (fondasi, tanpa perubahan tampilan) selesai |

**Pola lintas keputusan:** teks HTML sebagai isi awal (D-023/024/026); data contoh peramban tidak dipindahkan, kecuali data awal lewat migrasi; hapus konten editorial hanya Administrator; pengelola Jadwal Pemutakhiran boleh membaca daftar modul lain; WITA sebagai acuan waktu; retensi pesan dan swafoto absensi belum diputuskan (menunggu unit hukum/kearsipan).

---

## 17. Riwayat commit

110 commit di `laravel-development` (per commit fondasi tema portal dan dashboard, D-057 Irisan 1). Tahapan besar:

| Rentang | Isi |
|---|---|
| 2026-10-12 s.d. `46cc53a` (10-13) | Dokumen tata kelola dan rancangan Phase 1–2.19 (`docs/phases/`) |
| `44b3c8a` (10-13) | Restrukturisasi menjadi satu aplikasi Laravel + API FAQ publik |
| `0fc0cde` … `b462f48` | Login Sanctum, akun, penugasan modul, Tanya Jawab (D-007–D-012) |
| `4e71c0c` … `cfb06a7` | Modul ke server satu per satu: Keliling, Jam, Papan, Pesan, Tarif, Statistik, Kontak, Visi & Misi, Tentang UPTD, Layanan, Beranda, Halaman Statis, Pemutakhiran (D-013–D-025) |
| `3839347` … `697be56` (10-14) | Situs, profil akun, unggahan, foto & Renstra, logo PINTU, Regulasi, Berita, Dokumentasi, Struktur, Absensi (D-026–D-034) |
| `62f0a0c` … `7935f70` | Navbar notch, logo mitra, kanal bertautan, perbaikan teks alur, nilai Visi & Misi, sinkron partial, 404 bertingkat (D-035–D-039) |
| `12e30da` … `4005cab` | Video alur, Kalkulator PKB + cek biaya pajak, jam layanan BPAD (D-040–D-042) |
| `d41d2f4` (10-15) | Layanan Luar Kantor + daftar lokasi + salin per hari (D-043, D-044) |
| `31cd078` | Teks kecil di atas judul pembuka lebih besar di layar ≥1280 px |
| `a7d3e36`, `078f4b9` | React portal pertama: kata bergilir di judul pembuka, lalu berulang dengan jeda 10 detik (D-045) |
| `8699e8d` | Logo Ditlantas dan Jasa Raharja di pembuka (D-046) |
| `docs(pintu): add the single-file project context for AI agents` | Berkas ini, aturan baca-dan-perbarui di `AGENTS.md`, rujukan di README |
| `e0eb24d` | Foto pejabat di bagan Struktur terpotong bulat, dipangkas dari atas |
| `8d481be` | Laporan Penerimaan tahap 1: impor berkas STS per loket, laporan harian/bulanan di dashboard (D-047) |
| `ba5aacf` | Laporan Penerimaan tahap 2: halaman portal `/penerimaan`, sakelar Portal khusus Administrator, API publik dengan penyamaran (D-048) |
| `db21e53` | Porsi Laporan Penerimaan yang sangat kecil ditulis "<0,1%" di portal dan dashboard |
| `5cab5d9` | Laporan Penerimaan portal sebagai tabel tetap: per kelompok hanya transaksi dan jumlah, tanpa saringan dan rentang, kelurahan disamarkan per kecamatan (D-049) |
| `7f9c648` | Papan Layanan berlaku setiap hari: isi terakhir tampil sampai diubah, semua loket Tutup selama loket tutup menurut Jam Pelayanan (D-050) |
| `0fce13c` | Rencana Antrean Online (`docs/rencana-antrean-online.md`), belum ada kode (D-051) |
| `ca695bc` | Antrean Online Irisan 1: Meja Informasi, tiket dua lembar 80 mm ber-QR, Pengaturan Antrean, halaman lacak (D-051) |
| `43ecc0b` | Antrean Online Irisan 2: loket pemanggil, layar TV dengan suara, pengaturan loket dan layar (D-051) |
| `20f25a7` | Antrean Online Irisan 3: Pos Proses (Penetapan, Server, Pelunasan, Cetak SKPD, Cetak STNK), pindai QR langsung menandai dengan Urungkan, perubahan data lewat pos Server, halaman lacak dengan jam tahap dan posisi (D-051) |
| `56619c6` | Antrean Online Irisan 4: Arsip (cari nomor, sebagian nomor polisi, QR, tanggal, layanan, status, tahap; riwayat lengkap; hapus tiket oleh Administrator) dan Ringkasan (harian/rentang ≤31 hari, per layanan, per tahap dengan median waktu, per hari, CSV dengan daftar tiket) (D-051) |
| `b93025f` | Lencana "Satu atap bersama" di beranda lebih besar mulai 1024 px (logo 40 px, teks 16 px) dan 1280 px (logo 46 px, teks 17 px), atas permintaan pemilik; lencana di halaman lain tetap |
| `0f30037` | Pemetaan PKB Irisan 1: modul `pemetaan`, impor Data Potensi dibaca di peramban dan dikirim per 1.000 baris, semua kolom termasuk identitas (KTP/telepon terenkripsi), peta skematis 6 kecamatan, ringkasan per kecamatan/kelurahan, sasaran penagihan (D-052) |
| `b10c762` | Pemetaan PKB Irisan 2: halaman portal `/pemetaan` (menu Informasi, tautan beranda), `GET /api/public/pemetaan` dengan penyamaran sel (per total, lalu semua total digabung sebagai persamaan), sakelar *Tampil di portal* khusus Administrator (D-052) |
| `375c200` | Label peta Pemetaan PKB portal terbaca di ponsel (±12 px, sebelumnya ±9 px), keterangan skala tidak terpotong (D-052) |
| `8031b63` | Urutan *Di luar kantor hari ini* di papan Beranda per tanggal (tombol di kartu hari Layanan Luar Kantor, naik/turun, Urut jam), tabel `service_board_orders`, `urutanPapan` di `GET /api/public/keliling`; kartu papan setinggi isinya dan daftar luar kantor dipas ke tinggi dua baris kiri (D-053) |
| `befc8d5` | Panel kota Pemetaan PKB portal menyebut *Jenis tidak tercatat* (`rodaLain` kota, 14 kendaraan pada data 16/10/2026) sehingga jenis roda berjumlah sama dengan total; disembunyikan hanya bila 1–2 (D-052) |
| `0926836` | Sampul halaman berita selebar isi artikel (936 px, bukan 1320 px) dan tampil utuh dengan rasio aslinya, atas permintaan pemilik |
| `51bacee` | Kepala halaman berita (breadcrumb, judul, ringkasan) selebar sampul dan isi artikel (936 px), sehingga tepi kirinya sejajar, atas permintaan pemilik |
| `a862d19` | Hanya penggantian nama `peta-skematis.tsx` menjadi `peta-kecamatan.tsx` (isi lama): `git add` gagal karena satu path, jadi isi perubahan peta masuk di commit berikutnya; dashboard di commit ini tidak bisa dibangun |
| `56d7b24` | Peta Pemetaan PKB (portal dan dashboard) memakai batas enam kecamatan dari geoportal Kementerian Pertanian (geodatabase BIG edisi Mei 2021) lewat `scripts/batas-kecamatan.mjs` → `batas-kecamatan.ts`, menggantikan petak skematis; sumber disebut di halaman (D-052) |
| `3b373c2` | Peta Pemetaan PKB memakai Batas Desa/Kelurahan BIG 1:10.000 edisi 2022 dari berkas pemilik (`scripts/batas-wilayah.mjs` → `batas-wilayah.ts`, ruas bersama tanpa celah), kecamatan terpilih diperbesar dengan kelurahannya di portal dan dashboard (`peta.ts`, `peta-wilayah.tsx`); Bakunase II terkonfirmasi di Kota Raja (D-052) |
| `cb0a7e6` | Peta Pemetaan PKB (portal dan dashboard): memilih kecamatan memfokuskan *← Semua kecamatan*, kembali memfokuskan kecamatan itu lagi; konfirmasi pemilik bahwa ketentuan LapakGIS membolehkan batas turunannya tampil di situs (D-052) |
| `6d243d6` | Roda tiga dan roda empat terpisah di semua tabel Pemetaan PKB (dashboard, portal, API publik) dengan penyamaran yang disesuaikan; Administrator memilih kolom tabel wilayah (juga di portal) dan daftar sasaran penagihan, tersimpan di server (D-052) |
| `07cc4f3` | Peta `/pemetaan` setinggi 600 px (paling tinggi 75% layar) dengan lebar mengikuti bentuk tampilan, sedikitnya 480 px, jadi label kelurahan yang terpasang di 1280 px naik dari 39 ke 44 dari 51; wadah tabel `position: relative` agar teks "disamarkan" pembaca layar tidak melebarkan halaman di ponsel (D-052) |
| `f6d854a` | Peta dashboard Pemetaan PKB setinggi 600 px dengan lebar mengikuti bentuk tampilan, sedikitnya 448 px (label kelurahan di 1280 px 39 → 44 dari 51); judul kolom angka tabel wilayah boleh membungkus dan sel Pemilik, Kepatuhan, dan Hasil kunjungan di daftar sasaran `whitespace-normal`, jadi ketiga tabel muat di 1280 px tanpa geser (D-052) |
| `5dc0555` | Daftar Sasaran penagihan mulai dari Kepatuhan *Belum taat* (`kelompok=belum-taat`, semua selain taat, termasuk 465 kendaraan menunggak bertagihan Rp0), pilihan pemilik; pencarian nomor polisi atau nama memperlakukan `%`, `_`, dan `!` apa adanya (`escape '!'`); keterangan judul tidak tampil saat hasil kosong dan pesannya menyebut kendaraan taat tidak termasuk (D-052) |
| `76c58e5` | Kalimat keterangan di bawah papan layanan Beranda dihapus (pemilik); `<figcaption>` papan tersembunyi dan `initPapan` hanya menampilkannya bila catatan papan berisi (`.caption-row[hidden]` di `components.css`), jadi tidak ada garis pemisah kosong. Catatan papan, bila ada, kini satu-satunya isi baris itu (13 px, di kiri) |
| `aa29edd` | Halaman portal `/aplikasi` (menu Layanan) dan modul dashboard *Aplikasi & Situs* di Alat Bantu: koleksi `aplikasi` (`agency_apps`, `GET`/`POST`/`PATCH`/`urutan`/`hapus`, `AgencyAppPolicy`), `GET /api/public/aplikasi`, tujuan unggahan `aplikasi`, lima aplikasi awal dengan logo 240 px dari sumbernya (D-054) |
| `88aeead` | Dasbor transparansi beranda: tab Penerimaan pajak dari `GET /api/public/penerimaan/ringkasan` (tahun berjalan per bulan, bulan yang disamarkan tidak dijumlah), tab baru Pemetaan PKB dari `GET /api/public/pemetaan`, keduanya tersembunyi sampai ditampilkan dan bertautan ke halamannya di dalam panel; dua tautan di bawah judul dihapus; Statistik Layanan tinggal tiga kategori (D-055) |
| `2c9e3d5` | Sakelar *Tampil di portal* per kategori Statistik Layanan (`tampil` di dokumen `statistik`, bawaan menyala, dokumen lama dibaca tampil); `GET /api/public/statistik` memberi `kategori` yang tampil dan hanya angkanya; beranda menyembunyikan tab yang dimatikan, juga sebelum terverifikasi (D-056) |
| `2eb4cda` | Rencana tema portal dan dashboard (`docs/rencana-tema-situs.md`), belum ada kode (D-057) |
| `feat(pintu): lay the theme foundation for the portal and dashboard` | Fondasi tema (D-057 Irisan 1): warna, sudut, huruf, dan ukuran menjadi variabel bernilai sama, daun logo terpisah dari aksen, `/tema-portal.css` dan `/tema-dashboard.css` (kosong) di semua halaman; tanpa perubahan tampilan |

Repository terkait: `njkb-api-ntt` `main` — `f7a1ee5` (izin konsumen PINTU), `6a4c4f8` (404 untuk nomor polisi tidak terdaftar, sudah di-deploy).

---

## 18. Pekerjaan tertunda dan hal yang diketahui

**Menunggu pemilik:**

- Pemetaan PKB (D-052): impor berikutnya selalu mulai tersembunyi, jadi Administrator perlu menyalakan *Tampil di portal* lagi setelah memeriksanya; data master setahun (Januari–Desember); ekspor CSV daftar sasaran belum ada (perlu keputusan karena berisi data pribadi).
- Antrean Online (D-051): keputusan rencana §9 yang tersisa (akun non-UPTD, antrean prioritas, Kebijakan Privasi, waktu deploy), pengadaan perangkat (§8: printer thermal 80 mm, pemindai 2D, layar TV dengan peramban Chrome/Edge dan pengeras suara), uji pindai QR tiket dengan ponsel dan dengan pemindai USB 2D, uji *Pindai dengan kamera* di Chrome Android (tidak bisa diuji di panel browser), dan uji suara layar di perangkat TV (suara bahasa Indonesia peramban). Irisan 1–4 selesai; berikutnya uji coba paralel di loket setelah deploy (rencana §7), lalu Irisan 5 (Papan Layanan dari antrean) dan 6 (API Samsat Online) bila pemilik memutuskan.
- Jadwal Pemutakhiran masih mencatat papan "Setiap hari kerja, 07.45 WITA" (interval 1 hari); sejak D-050 papan cukup diubah bila ada perubahan, jadi frekuensinya bisa diubah pengelola.
- Uji cek data kendaraan dengan kendaraan sungguhan (portal: nomor polisi + 5 karakter terakhir nomor rangka, sebelum 21.00 WITA) belum dikonfirmasi.
- Deploy ke Laravel Cloud dan cutover domain (lihat §15).
- Judul pembuka yang tersimpan tidak bertitik ("Semua informasi SAMSAT"); konfirmasi apakah disengaja.
- Jam mulai layanan BPAD (`NJKB_SERVICE_OPENS_AT`) belum pasti; teramati aktif lagi sekitar 00.01 WITA.

**Konten yang belum lengkap (diisi dari dashboard):**

- Aplikasi & Situs (D-054): keterangan kelima aplikasi awal disusun dari nama dan situs masing-masing; SIPAS ("Pengelolaan administrasi SKPD"), SIKAP, dan Tilang / Razia perlu diperiksa pemilik. SIKAP, SIPAS, dan Tilang / Razia sama-sama berlogo Pemprov NTT sesuai sumbernya; bisa diganti dengan unggahan.
- 9 dokumen Regulasi & Unduhan masih draf tanpa berkas; 9 kegiatan Dokumentasi draf tanpa foto; artikel peluncuran draf (paragraf "empat peran" perlu disesuaikan).
- Statistik masih status Ilustrasi sampai diverifikasi pimpinan; Jam, Kontak, Papan kosong/tutup sampai disimpan pertama kali.
- Foto Kepala UPTD belum ada; nama pejabat selain Kepala UPTD kosong; pengantar halaman statis masih "berlaku sejak [tanggal terbit]".

**Fitur yang belum dibuat (dicatat di keputusan):**

- Tema portal dan dashboard (D-057): Irisan 1 (fondasi) selesai; berikutnya Irisan 2 (dokumen `tema`, `ThemePalette`, tema siap pakai, huruf, tab Tampilan), lalu Irisan 3 (skala, pratinjau portal, riwayat). Huruf yang akan di-host sendiri perlu izin unduh satu per satu.
- Berkas STS loket selain Loket SAMSAT belum diterima pemilik (D-047).
- Laporan Penerimaan publik (D-049) masih menampilkan komponen dan kendaraan baru seluruh hari walau berasal dari 1–2 transaksi (tanpa keterangan lain selain tanggal), dan jumlah kelompok yang didominasi satu transaksi besar; keduanya diterima pemilik bersama tabel tetap.
- Bagian portal lain masih di `main.js` dan cek pajak belum React (D-045).
- Ganti/lupa kata sandi sendiri, kirim tautan aktivasi via surel, MFA, waktu login terakhir (D-007, D-008).
- Peran alur per langkah di server (D-010, D-022); versi draf untuk layanan terbit.
- Notifikasi pesan ke petugas, cek status tiket oleh warga, masa simpan pesan (D-016).
- Statistik per rentang waktu dan tahun sebelumnya (D-018).
- Nomor polisi/petugas per unit Samsat Keliling, penanda jadwal bentrok (D-043).
- Salin hari dan salin minggu belum menyalin urutan papan Beranda (D-053).
- Plat kuning dan pembayaran lebih awal untuk jatuh tempo tahun berikutnya di kalkulator (D-041).
- Endpoint `GET /ekspor`, `POST /impor`, `POST /reset` untuk mode REST penuh dashboard.

**Utang dokumentasi dan teknis kecil:**

- Beberapa kalimat lama di `docs/dashboard.md` masih menyebut modul "di IndexedDB" padahal sudah di server (blok Status baris awal, catatan profil akun, daftar policy, cadangan). `docs/api.md` daftar penegakan modul belum menyebut `berita`, `dokumentasi`, `dokumen`, `unit`, `lokasi`.
- `.gitignore` menyebut `scripts/stage-frontend.mjs` dan `/_site/` yang sudah tidak ada; komentar di `dashboard-pegawai.blade.php` menyebut folder `dashboard-app/` lama.
- Batas login 30/menit per IP mungkin perlu dinaikkan bila semua pegawai berbagi satu IP kantor.
- CSS portal tidak berversi (lihat §8.2).
- Tab bertingkat `TabsList className="… h-auto flex-wrap"` di Statistik Layanan dan Layanan Luar Kantor bisa keluar dari latarnya bila dilipat di layar sempit (lihat §14); belum diperbaiki.

---

## 19. Prosedur memperbarui dokumen ini

**Atas permintaan pemilik (2026-10-15), berkas ini wajib diperbarui setiap ada perubahan**, di commit yang sama dengan perubahannya:

1. **§3 Status terakhir:** commit terakhir di origin (isi setelah push), status working tree, jumlah tes, keputusan terakhir, dan status deploy/integrasi bila berubah.
2. **Bagian yang terdampak:** mis. endpoint baru di §10, komponen React baru di §8.4, modul baru di §9, jebakan baru di §14.
3. **§16:** tambahkan baris untuk setiap keputusan D-0xx baru (dan ubah baris yang digantikan).
4. **§17:** tambahkan commit penting setelah di-push.
5. **§18:** hapus pekerjaan yang selesai, tambahkan yang baru tertunda atau ditemukan.
6. **§20 Log perubahan dokumen:** tambahkan satu baris di paling atas (tanggal, ringkasan, keputusan/commit terkait).
7. Perbarui tanggal dan commit di kepala berkas.
8. Jangan menulis rahasia; ringkas, faktual, dan berbahasa Indonesia.

Bila commit dibuat sebelum hash-nya diketahui, tulis status "belum di-commit" lalu perbaiki hash di commit berikutnya.

---

## 20. Log perubahan dokumen

| Tanggal | Perubahan |
|---|---|
| 2026-10-17 | Fondasi tema (D-057 Irisan 1). Status (tes 1355, pemeriksaan frontend, keputusan terakhir), §8.2 (aturan tema dan nilai cadangan variabel), §9.1 (tema dashboard), §17 (`2eb4cda` dicatat), §18 |
| 2026-10-17 | Rencana tema portal dan dashboard (D-057): `docs/rencana-tema-situs.md` dengan temuan keadaan sekarang, pengaturan, `tema-portal.css`/`tema-dashboard.css`, turunan warna dan kontras, huruf, skala, tab Tampilan, API, irisan, pengujian, risiko. Status, §1 (daftar dokumen), §7 (struktur `docs/`), §16 (D-057; judul dan daftar isi sampai D-057), §17 (`2c9e3d5` dicatat), §18 |
| 2026-10-17 | Sakelar tampil kategori Statistik Layanan (D-056). Status (tes 1328), §9 (baris Statistik Layanan), §16 (D-056; judul dan daftar isi sampai D-056), §17 (`88aeead` dicatat) |
| 2026-10-17 | Dasbor beranda (D-055): tab Penerimaan pajak dan Pemetaan PKB otomatis, `GET /api/public/penerimaan/ringkasan`, Statistik Layanan tiga kategori. Status (tes 1327), §8 (`initDash`), §10.4, §16 (D-018, D-055; judul dan daftar isi sampai D-055), §17 (`aa29edd` dicatat) |
| 2026-10-17 | Halaman Aplikasi & Situs (D-054): modul `aplikasi`, tabel `agency_apps`, API dashboard dan publik, menu Layanan di semua halaman portal, menu Alat Bantu di dashboard. Status (tes 1322), §8.1, §9 (menu dan `KOLEKSI_SERVER`/`UNGGAHAN_SERVER`), §10.4, §10.5, §10.6, §16 (D-054; judul dan daftar isi sampai D-054), §17 (`76c58e5` dicatat), §18 |
| 2026-10-17 | Keterangan di bawah papan Beranda dihapus (pemilik); baris itu hanya tampil bila catatan papan diisi. Status, §17 (`5dc0555` dicatat) |
| 2026-10-16 | Sasaran penagihan (D-052): Kepatuhan mulai dari *Belum taat* (pemilik), pencarian `%`/`_` apa adanya, keterangan judul saat hasil kosong. Status (tes 1282), §9 (baris Pemetaan PKB), §10.4 (`/api/pemetaan/kendaraan`), §16 (D-052), §17 (`f6d854a` dicatat) |
| 2026-10-16 | Pemeriksaan dashboard `#/pemetaan`: peta setinggi 600 px seperti portal, judul kolom angka tabel wilayah membungkus, sel Pemilik/Kepatuhan/Hasil kunjungan di daftar sasaran `whitespace-normal`. Status, §9 (baris Pemetaan PKB), §14 (`whitespace-nowrap` sel shadcn), §17 (`07cc4f3` dicatat) |
| 2026-10-16 | Pemeriksaan `/pemetaan` sesudah kolom tabel: peta diperbesar dibuat setinggi 600 px dengan lebar mengikuti bentuk tampilan (`--rasio`), wadah tabel `position: relative` (halaman melebar di ponsel karena teks `sr-only`). Status, §8 (baris `pemetaan-pkb.tsx`), §14 (dev server Vite dan panel browser; `sr-only` di wadah gulir), §17 (`6d243d6` dicatat) |
| 2026-10-16 | Kolom tabel Pemetaan PKB (D-052): roda tiga dan roda empat terpisah di semua tabel dan di API publik (`rodaTigaLebih` diganti `rodaTiga` dan `rodaEmpat`; penyamaran disesuaikan, perulangan per total kembali sampai stabil); pengaturan `pemetaan` dengan `GET`/`PUT /api/pemetaan/kolom` (khusus Administrator menyimpan), `kolom` di `GET /api/public/pemetaan`, menu *Atur kolom* di dashboard. Status, §8.4, §9.2, §10.4, §10.5, §16, §17 (`cb0a7e6` dicatat) |
| 2026-10-16 | Pemeriksaan `/pemetaan` (data, galat konsol, 375 dan 1280 px, keyboard) menemukan fokus keyboard hilang saat kecamatan dipilih di peta; diperbaiki di portal dan dashboard. Pemilik memastikan ketentuan LapakGIS membolehkan batas turunannya ditampilkan; hal tertunda itu ditutup. Status, §8.4, §17 (`3b373c2` dicatat), §18 |
| 2026-10-16 | Peta Pemetaan PKB dengan Batas Desa/Kelurahan BIG 1:10.000 edisi 2022 dari berkas pemilik (D-052): `scripts/batas-wilayah.mjs` (menggantikan `batas-kecamatan.mjs`) → `batas-wilayah.ts` (menggantikan `batas-kecamatan.ts`), modul bersama `peta.ts`, komponen dashboard `peta-kecamatan.tsx` menjadi `peta-wilayah.tsx`; kecamatan terpilih diperbesar dengan kelurahannya; catatan sumber di halaman portal dan dashboard; Bakunase II terkonfirmasi di Kota Raja. Status, §7, §8.4, §9.2, §16, §17 (`56d7b24` dicatat), §18 |
| 2026-10-16 | Peta Pemetaan PKB memakai batas kecamatan sungguhan (D-052): GeoJSON dari layanan `Batas_Administrasi` geoportal Kementerian Pertanian (geodatabase batas desa/kelurahan BIG edisi Mei 2021), `scripts/batas-kecamatan.mjs` → `resources/js/portal/pemetaan/batas-kecamatan.ts`, komponen dashboard `peta-skematis.tsx` menjadi `peta-kecamatan.tsx`, catatan sumber di halaman portal dan dashboard. Batas kelurahan menunggu berkas resmi. Status, §7, §8.4, §9.2, §16, §17 (`51bacee` dicatat), §18 |
| 2026-10-16 | Kepala halaman berita selebar sampul dan isi artikel (`.article-head` 936 px, sebelumnya 860 px), atas permintaan pemilik. Status, §8.2, §17 (`0926836` dicatat) |
| 2026-10-16 | Sampul halaman berita: selebar isi artikel (`.article-figure`, 936 px) dan utuh dengan rasio aslinya, atas permintaan pemilik (foto lebih lebar dari isi berita di desktop, poster terpotong bingkai 16:8). Status, §8.2, §17 (`befc8d5` dicatat) |
| 2026-10-16 | Pemetaan PKB portal: panel kota menyebut *Jenis tidak tercatat* (`rodaLain` di `GET /api/public/pemetaan`, hanya kota; `null` bila 1–2, tanpa penyamaran lanjutan karena selisih angka yang tampil), atas permintaan pemilik. Status, §8.4, §16, §17 (`8031b63` dicatat) |
| 2026-10-16 | Urutan papan luar kantor (D-053): tabel `service_board_orders`, `GET` dan `PUT` `/api/koleksi/keliling/urutan-papan`, `urutanPapan` di API publik, dialog *Urutan papan* di Layanan Luar Kantor (`lib/urutan-papan.ts`); papan beranda (`pages.css`, `initKeliling()`): kartu kiri setinggi isinya, daftar luar kantor dipas ke tinggi kartu kiri di layar >980 px. Status, §9.2, §10.4, §10.5, §16, §17 (hash `375c200` dicatat, baris kosong di tabel dihapus), §18 diperbarui |
| 2026-10-16 | Pemilik menyalakan *Tampil di portal* Pemetaan PKB; `/pemetaan` diperiksa dengan data sungguhan (sama dengan dashboard, tanpa penyamaran). Label peta portal dibesarkan di kartu sempit (container query ≤380 px) dan keterangan skala dirapikan. Status, §8.4, §12, §17 (`b10c762` dicatat), §18 |
| 2026-10-16 | Pemetaan PKB Irisan 2 (D-052): halaman portal `pemetaan` (menu Informasi desktop/seluler lewat `sync-partials`, tautan Transparansi beranda, `$portalPages`), komponen `pemetaan/pemetaan-pkb.tsx`, `GET /api/public/pemetaan` (`BuildPublicPotentialMap`: penyamaran per total lalu audit gabungan dengan eliminasi bilangan bulat), kolom `potential_imports.tampil` (dimigrasi di `pintu`), `PATCH /api/pemetaan/impor` dan sakelar dashboard khusus Administrator. Status, §8.1, §8.4, §9.2, §10.4, §10.5, §12, §16, §17 (`0f30037` dicatat), §18 |
| 2026-10-16 | Pemetaan PKB Irisan 1 (D-052): modul `pemetaan`, tabel `potential_imports`/`potential_vehicles`/`potential_summaries` (dimigrasi di `pintu`, berkas Data Potensi pemilik diimpor saat pemeriksaan), impor bertahap dari peramban, kecamatan dari daftar resmi kelurahan (enum `Kecamatan`), kelompok kepatuhan (`TaxCompliance`), roda (`VehicleWheels`), halaman dashboard dengan peta skematis, ringkasan, dan sasaran penagihan beridentitas; ringkasan dihitung sekali saat impor selesai. Status, §9.2, §10.4, §10.5, §12, §14, §16, §17 (hash `b93025f`), §18 diperbarui |
| 2026-10-16 | Lencana mitra "Satu atap bersama" di beranda diperbesar untuk layar lebar atas permintaan pemilik (`pages.css`, hanya `.mitra`): ≥1024 px logo 40 px dan teks 16 px, ≥1280 px logo 46 px dan teks 17 px; di bawah 1024 px tetap. Status, §17 (hash `56619c6`) diperbarui |
| 2026-10-16 | Antrean Online Irisan 4 (D-051): Arsip di dashboard (cari menurut nomor di tanggal mana pun, sebagian nomor polisi, QR, tanggal, layanan, status, tahap; riwayat lengkap dengan petugas dan loket; Administrator menghapus tiket seluruhnya, hanya yang selesai/batal dari hari sebelumnya, pemilik), Ringkasan (harian/bulanan/rentang ≤31 hari, pemilik; per layanan, per hari, per tahap dengan median tunggu dan lama), CSV dengan daftar tiket dan nomor polisi (pemilik). Pencarian QR/nomor/nopol dipakai bersama loket (`ReadsQueueTicketQuery`). Tanpa migrasi. Status, §9.2, §10.4, §16, §17 (hash `20f25a7`), §18 diperbarui |
| 2026-10-16 | Antrean Online Irisan 3 (D-051): Pos Proses di dashboard (Penetapan, Server – Perubahan data, Pelunasan, Cetak SKPD, Cetak STNK), pindaian QR/nomor/nomor polisi langsung menandai dengan konfirmasi dan Urungkan (pemilik), perubahan data lewat pos Server tersendiri (pemilik), status `perubahan-data`, kolom `queue_events.keadaan` (dimigrasi di `pintu`), *Pindai dengan kamera* lewat `BarcodeDetector`, halaman lacak dengan jam setiap tahap dan posisi antrean, `AdvanceQueueTicket` dipakai bersama loket dan pos. Halaman Loket tidak lagi melebar di layar ponsel. Status, §8.4, §9.2, §10.4, §10.5, §12, §14, §16, §17 (hash `43ecc0b`), §18 diperbarui |
| 2026-10-16 | Antrean Online Irisan 2 (D-051): loket pemanggil (tiga loket awal: satu per tahap, pemilik), Loket di dashboard (panggil berikutnya dengan penundaan otomatis nomor yang masih dipanggil, panggil nomor tertentu lewat ketik/nopol/QR dengan tahap terlewat dicatat otomatis, panggil ulang, tunda, selesai), pengaturan loket dan layar, layar TV `/layar` (tautan pemasangan sekali tampil, hash token, suara tanpa nopol, pemilik), halaman lacak untuk dipanggil/ditunda/selesai. Tabel antrean baru dimigrasi di `pintu`. Status, §8.4, §9.2, §10.4, §10.5, §12, §16, §17 (hash `ca695bc`), §18 diperbarui |
| 2026-10-15 | Antrean Online Irisan 1 (D-051): modul `antrean`, tabel layanan/tiket/riwayat (A–D dari migrasi), Meja Informasi (cetak dua lembar 80 mm ber-QR lewat iframe, cetak ulang, batal), Pengaturan Antrean (Administrator), halaman lacak `/lacak/{kode}` dengan `GET /api/public/antrean/{kode}`, pustaka `uqr` (pemilik). Tabel antrean dimigrasi di `pintu`. Status, §6, §8.1, §8.4, §9.2, §10.4, §10.5, §12, §16, §17 (hash `0fce13c`), §18 diperbarui |
| 2026-10-15 | Rencana Antrean Online dari flowchart pemilik dan tanya jawab (D-051): `docs/rencana-antrean-online.md` dengan alur 8 tahap, layanan A–D, data, penomoran, siklus tiket, cetak 80 mm, pindai QR, layar TV, halaman lacak, arsip, API, irisan 0–6, uji coba paralel, perangkat, hal terbuka, dan risiko. Belum ada kode. Status, §1 (dokumen rinci), §7, §16, §17 (hash `7f9c648`), §18 diperbarui |
| 2026-10-15 | Papan Layanan berlaku setiap hari (D-050): isi terakhir tampil di beranda setiap hari dengan label "Diperbarui hari ini"; selama loket tutup menurut Jam Pelayanan semua loket Tutup tanpa waktu tunggu. Jam loket dimuat sekali untuk status loket dan papan (`siapkanJam()` di `main.js`). Status, §16, §17 (hash `5cab5d9`), §18 diperbarui |
| 2026-10-15 | Laporan Penerimaan portal sebagai tabel tetap (D-049): komponen per kelompok ternyata membuka satu transaksi (114 dari 175 baris kelurahan September), dan selisih dua permintaan juga; kini per kelompok hanya transaksi dan jumlah, Harian total saja, Bulanan dengan rincian, saringan dan rentang dihapus dari portal, kelurahan disamarkan per kecamatan, hari disamarkan sama di laporan harian dan bulanan, API hanya `tanggal` atau `bulan`. Status, §8.4, §10.4, §16, §17 (hash `db21e53`), §18 diperbarui |
| 2026-10-15 | Pemilik menyalakan Portal untuk impor 14 Oktober dan September; `/penerimaan` diperiksa dengan data sungguhan. Porsi yang ada tetapi di bawah 0,05% kini ditulis "<0,1%" di portal dan dashboard (dulu "0%"/"0,0%"), atas persetujuan pemilik. Status, §8.4, §17 (hash `ba5aacf`), §18 diperbarui |
| 2026-10-15 | Laporan Penerimaan tahap 2 (D-048): halaman portal `/penerimaan` (komponen React, menu Informasi, tautan beranda), sakelar Portal khusus Administrator, `GET /api/public/penerimaan` dengan penyamaran; modul bersama `periode.ts` dan `csv.ts`. Status, §8.1, §8.4, §10.3, §10.4, §16, §17, §18 diperbarui. Diperiksa di browser (430 px dan 1280 px; perbaikan: angka di kotak kecil terpotong) dan pada data dev tanpa mengubah tanda tampil |
| 2026-10-15 | Laporan Penerimaan tahap 1 (D-047): menu dan modul `penerimaan`, impor berkas STS dengan pratinjau, laporan harian/bulanan/rentang dengan saringan, bento, grafik, tabel, dan CSV; status, §9.1–9.2, §10.4–10.5, §12, §14 (batas PHP Herd, memori commit), §15, §16, §18 diperbarui. Diperiksa di browser setelah pemilik me-restart Claude Desktop (perbaikan: halaman melebar karena tabel riwayat impor dan deretan tab, lencana angka miliaran) |
| 2026-10-15 | Foto pejabat di bagan Struktur Organisasi tidak lagi menjuntai keluar lingkaran: foto mengisi avatar dan dipangkas dari atas (§8.2) |
| 2026-10-15 | Berkas dibuat: konteks lengkap proyek sampai D-046 (`8699e8d`), atas permintaan pemilik untuk agen AI berikutnya. `AGENTS.md` kini mewajibkan agen membaca dan memperbarui berkas ini |
