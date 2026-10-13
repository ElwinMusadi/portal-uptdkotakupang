# PINTU — Handover, Analisis, Review, dan Audit Project untuk ChatGPT

> **Catatan 13 Oktober 2026:** dokumen ini adalah snapshot audit sebelum proyek menjadi satu aplikasi Laravel di root repository. Path seperti `*.html` di root, `assets/`, `dashboard-app/`, `dashboard/`, dan `backend/` sudah berubah; lihat [`README.md`](README.md) dan [`docs/decisions.md`](docs/decisions.md) untuk struktur dan keputusan yang berlaku.

> **Tujuan:** memberikan konteks produk dan teknis yang mandiri kepada ChatGPT atau pengembang berikutnya. Dokumen ini menjelaskan implementasi yang ada, bukan menganggap rancangan atau prototipe sebagai sistem produksi yang sudah selesai.
>
> **Tanggal audit:** 11 Oktober 2026. **Bahasa produk:** Bahasa Indonesia. **Instansi:** UPTD Pendapatan Daerah Wilayah Kota Kupang / Samsat Kota Kupang, BPAD Provinsi Nusa Tenggara Timur.

## Daftar isi

1. [Ringkasan eksekutif dan batas kesiapan](#1-ringkasan-eksekutif-dan-batas-kesiapan)
2. [Baseline, cakupan, dan metode audit](#2-baseline-cakupan-dan-metode-audit)
3. [Konteks produk, pengguna, dan tujuan](#3-konteks-produk-pengguna-dan-tujuan)
4. [Arsitektur dan struktur repository](#4-arsitektur-dan-struktur-repository)
5. [Teknologi dan dependensi](#5-teknologi-dan-dependensi)
6. [Struktur menu dan peta halaman publik](#6-struktur-menu-dan-peta-halaman-publik)
7. [Fitur portal publik dan perjalanan pengguna](#7-fitur-portal-publik-dan-perjalanan-pengguna)
8. [Struktur menu, route, dan fitur dashboard](#8-struktur-menu-route-dan-fitur-dashboard)
9. [Model data, penyimpanan, dan alur publikasi](#9-model-data-penyimpanan-dan-alur-publikasi)
10. [Autentikasi, integrasi, dan kontrak API](#10-autentikasi-integrasi-dan-kontrak-api)
11. [Design system portal publik](#11-design-system-portal-publik)
12. [Design system dashboard dan konsistensi visual](#12-design-system-dashboard-dan-konsistensi-visual)
13. [Audit UI/UX, aksesibilitas, dan responsivitas](#13-audit-uiux-aksesibilitas-dan-responsivitas)
14. [Audit keamanan, privasi, dan kebenaran informasi](#14-audit-keamanan-privasi-dan-kebenaran-informasi)
15. [Audit kualitas teknis, performa, SEO, dan pengujian](#15-audit-kualitas-teknis-performa-seo-dan-pengujian)
16. [Menjalankan, mengembangkan, dan membangun project](#16-menjalankan-mengembangkan-dan-membangun-project)
17. [Deployment Cloudflare Pages dan status domain](#17-deployment-cloudflare-pages-dan-status-domain)
18. [Tata kelola konten dan checklist data resmi](#18-tata-kelola-konten-dan-checklist-data-resmi)
19. [Roadmap, prioritas, dan kriteria penerimaan](#19-roadmap-prioritas-dan-kriteria-penerimaan)
20. [Panduan handover kepada ChatGPT dan peta source](#20-panduan-handover-kepada-chatgpt-dan-peta-source)

## 1. Ringkasan eksekutif dan batas kesiapan

PINTU adalah **Portal Informasi dan Pelayanan** untuk membantu masyarakat memperoleh informasi persyaratan kendaraan, jadwal pelayanan, lokasi Samsat Keliling, berita, dokumentasi, regulasi, dan kanal pengaduan. Portal publik memakai HTML/CSS/JavaScript tanpa framework. Dashboard internal memakai React/TypeScript dan memiliki hasil build statis yang sudah masuk repository.

Project bukan aplikasi backend yang sudah operasional. Keberadaan tombol simpan, alur terbit, login, dashboard pengaduan, absensi, atau kalkulator **tidak berarti** ada layanan server, autentikasi aman, sinkronisasi antarpegawai, ataupun penerimaan laporan masyarakat yang nyata.

### Istilah status yang dipakai

| Status | Makna |
|---|---|
| **Berfungsi lokal** | Interaksi benar-benar diimplementasikan di peramban; tidak menjamin ada penyimpanan atau layanan server. |
| **Data contoh** | Nilai/isi ilustrasi untuk prototipe, bukan fakta layanan yang telah disahkan. |
| **Placeholder** | Media, tautan, identitas, atau isi belum tersedia secara nyata. |
| **Adapter siap** | Kode klien untuk integrasi tersedia, tetapi server/API pasangannya belum ada di repository. |
| **Usulan** | Rancangan atau rekomendasi berikutnya; belum diimplementasikan. |
| **Teruji HTTP** | Respons halaman/aset benar-benar diperiksa; bukan bukti seluruh interaksi browser lolos. |

**Kesimpulan kesiapan:** cocok sebagai prototipe dan pratinjau hosting statis. Pemakaian sebagai portal resmi operasional membutuhkan verifikasi informasi, integrasi data publik, backend, autentikasi dan otorisasi server, tata kelola privasi, serta pengujian menyeluruh.

### Baca cepat sebelum melanjutkan

1. Ada **dua frontend**: portal statis dan dashboard React; belum ada backend pusat.
2. Login/role bukan keamanan produksi; sesi disimpan dan diverifikasi lokal.
3. CMS menyimpan ke IndexedDB; tombol terbit tidak memperbarui HTML masyarakat.
4. Pengaduan menghasilkan tiket demo tanpa mengirim laporan.
5. Jadwal/statistik/antrean/pajak menggunakan konfigurasi atau seed contoh yang harus diverifikasi.
6. Domain utama **sudah online HTTPS** pada pemeriksaan audit, berbeda dari status DNS pending pada sesi deployment awal.
7. Design system publik dan dashboard berhubungan tetapi berbeda density/theme/semantic tokens; jangan redesign otomatis.
8. Hosting menyalin hasil build `dashboard/`; source TSX harus dibuild terlebih dahulu untuk ikut terbit.
9. Tidak ada suite tests/CI quality gate; HTTP smoke tidak sama dengan E2E/browser/security tests.
10. `index.html` memiliki perubahan pengguna yang harus dipertahankan; dokumen ini tidak mengubah source.

## 2. Baseline, cakupan, dan metode audit

| Item | Kondisi saat audit |
|---|---|
| Lokasi kerja | `D:\Herd\portal-uptdkotakupang` |
| Repository GitHub | `https://github.com/ElwinMusadi/portal-uptdkotakupang` |
| Branch lokal | `main` |
| Baseline commit lokal | `1726f7c9e0a4c1a855a338ef88793ddedffca515` |
| Perubahan yang sudah ada | `index.html` memiliki perubahan lokal belum di-commit; audit menggunakan working tree, bukan mengembalikannya ke commit lama. |
| Source yang diaudit | HTML publik, partials, CSS/JS publik, source dashboard, konfigurasi build, lockfile, dokumen rancangan, dan aset. |
| Perubahan hasil pekerjaan audit | Hanya dokumen handover ini; tidak ada implementasi perbaikan source atau deployment ulang. |

Metode: analisis source, pemeriksaan lintaslapisan, review independen, inventaris fitur dan desain, serta pemeriksaan HTTP situs yang sudah online. Audit dilakukan dengan pembagian bidang arsitektur/dashboard, portal/keamanan, UI/UX, dan kualitas/pengujian. Klaim dari README dan rancangan dibedakan dari kondisi implementasi.

**Batas bukti:** audit bukan penetration test, sertifikasi WCAG, pengesahan aturan pajak, audit hukum, ataupun pengukuran Lighthouse. Dokumen rancangan menyebut pengujian Playwright historis; klaim itu tidak otomatis menjadi hasil pengujian baru pada sesi ini. Nomor baris rujukan dapat berubah setelah source diedit, terutama `index.html`.

## 3. Konteks produk, pengguna, dan tujuan

### 3.1 Masalah yang diselesaikan

Rancangan aktualisasi menjelaskan informasi persyaratan yang masih tersebar di kertas pada meja/kaca loket, kanal yang tidak terpusat, pembaruan yang belum baku, dan belum jelasnya peran pengelola informasi. PINTU menyatukan akses informasi dan menyediakan prototipe pengelolaan konten.

Tiga isu dalam rancangan:

1. Website profil dan informasi pelayanan: prioritas utama, skor APKL 20.
2. Perhitungan PKB termasuk masa amnesti: skor 19; diwujudkan sebagai simulasi dan alat bantu, bukan engine penetapan pajak resmi.
3. Absensi lapangan berbasis foto/lokasi: skor 17; diwujudkan sebagai modul lokal, bukan sistem kehadiran tervalidasi server.

Sumber konteks: `docs/RANCANGAN-UIUX.md:22–54`. Skor adalah isi dokumen rancangan, bukan penilaian audit baru.

### 3.2 Pengguna dan kebutuhan

| Pengguna | Kebutuhan | Jawaban di project |
|---|---|---|
| Wajib pajak | Dokumen apa yang dibawa, loket mana, perkiraan biaya | CTA persyaratan, tabel/checklist, simulasi PKB. |
| Warga dengan waktu terbatas | Jam buka dan lokasi layanan | Status WITA, countdown, jadwal keliling. |
| Pengguna pertama kali/lansia | Bahasa sederhana, mudah dibaca, akses mobile | Struktur editorial, headline besar, fokus pada tugas; aksesibilitas tetap perlu diuji. |
| Perwakilan/badan hukum | Persyaratan tambahan dan surat kuasa | Bagian persyaratan tambahan dan rancangan unduhan formulir. |
| Pelapor | Kanal aman dan kepastian tindak lanjut | Kontak/pengaduan tersedia sebagai UI; penerimaan laporan riil belum diimplementasikan. |
| Petugas/operator | Menyiapkan, meninjau, dan mengelola konten | Dashboard, tabel, formulir, papan alur, pemutakhiran. |
| Pemeriksa/atasan | Verifikasi dan persetujuan | Model workflow dan kontrol klien; bukan enforcement otorisasi server. |

### 3.3 Prinsip produk yang harus dipertahankan

- Tugas masyarakat lebih dulu: **Cek Persyaratan** sebagai CTA utama.
- Bahasa Indonesia ringkas, tidak menggantikan notis pajak atau informasi petugas dengan estimasi.
- Sumber, tanggal pembaruan, dan verifikasi perlu dapat ditelusuri.
- Tidak menjanjikan keamanan, kerahasiaan, penerimaan laporan, atau data real-time yang belum ada.
- Pembayaran berlangsung di kanal resmi; project tidak menyediakan payment gateway.

## 4. Arsitektur dan struktur repository

### 4.1 Dua frontend, belum satu sumber data

```text
Masyarakat
  mengakses HTML root + assets/css + assets/js/main.js
  membaca markup/CONFIG yang statis atau contoh

Pegawai (login demo)
  mengakses dashboard/index.html + dashboard/assets/*
  menjalankan React + TanStack Query + ApiClient
    default: LocalApi menyimpan IndexedDB di browser/origin tersebut
    opsional: HttpApi meminta REST API bila VITE_API_URL diisi saat build

Belum ada backend / database server / public content API / generator HTML CMS.
Mengubah atau menerbitkan konten di dashboard tidak mengubah HTML publik.
```

Pemisahan ini merupakan temuan arsitektur paling penting. Field `portal` pada konfigurasi navigasi dashboard hanya menunjukkan **tautan halaman tujuan yang direncanakan**, bukan bukti integrasi baca/tulis.

### 4.2 Struktur source

```text
portal-uptdkotakupang/
├── *.html                      17 HTML root, termasuk login/design system/404/redirect
├── assets/
│   ├── css/tokens.css           token visual portal
│   ├── css/base.css             reset, type, layout, accessibility foundation
│   ├── css/components.css       komponen reusable portal
│   ├── css/pages.css            pola dan layout khusus halaman
│   ├── js/main.js              interaksi portal, CONFIG, login demo
│   └── img/                    lambang NTT, turunan WebP, favicon, logo cadangan
├── partials/                   sumber header/footer/SVG sprite
├── scripts/sync-partials.mjs    sinkronisasi blok partial ke HTML root
├── dashboard-app/
│   ├── package.json + package-lock.json
│   ├── vite.config.ts          base, output, plugin dev serving portal
│   ├── tsconfig*.json
│   ├── public/brand/           aset brand yang disalin ke build
│   └── src/
│       ├── main.tsx + index.css
│       ├── app/                layout, nav, router
│       ├── components/ui/      source komponen shadcn-style/Radix
│       ├── components/data/    tabel, actions, status, confirmations
│       ├── components/form/    fields, sheets, editor, media, unsaved guard
│       ├── components/alur/    workflow UI
│       ├── hooks/              antara lain mobile breakpoint
│       ├── lib/api/            client contract, LocalApi, HttpApi, seed, types
│       ├── lib/                auth, query, validasi, pajak, gambar, jadwal
│       └── pages/              seluruh modul dashboard
├── dashboard/                  artefak Vite committed, bukan sumber utama
├── docs/RANCANGAN-UIUX.md       konteks rancangan; tidak selalu sama implementasi
├── docs/preview/               screenshot historis, bukan snapshot audit live
├── README.md
└── HANDOVER-CHATGPT.md          dokumen mandiri ini
```

### 4.3 Pola implementasi dan batas tanggung jawab

- Portal memakai class CSS semantik/BEM-style dan selector `data-*` sebagai hook JavaScript. Hindari menghapus data attributes ketika mengubah markup.
- Shared partials bukan runtime include: HTML sudah berisi hasil salinan; perubahan partial harus disinkronkan.
- Dashboard memakai lazy route imports, layout/sidebar bersama, reusable tables/forms, dan TypeScript contracts berbahasa Indonesia.
- `ApiClient` memisahkan operasi UI dari persistence. `LocalApi` adalah implementasi nyata lokal; `HttpApi` adalah adapter klien nyata dengan backend yang belum tersedia.
- Build `dashboard/` harus konsisten dengan `dashboard-app/`. Jangan mengedit bundle minified sebagai sumber fitur.
- Root repository tidak memiliki aplikasi Laravel, Express, Worker Functions, migrasi DB, layanan email, atau server autentikasi.

## 5. Teknologi dan dependensi

### 5.1 Portal publik

HTML5, CSS custom properties, JavaScript vanilla, inline SVG sprite, WebP/PNG/SVG lokal. Tidak ada root `package.json`; tidak memerlukan React atau build untuk halaman publik. Font sistem digunakan tanpa distribusi berkas font SF Pro.

### 5.2 Dashboard

Tabel berikut menyatakan **rentang versi yang dideklarasikan `dashboard-app/package.json`**, bukan asumsi versi terpasang. `package-lock.json` menjadi rujukan resolusi pasti; gunakan `npm ci`, bukan upgrade tak diminta.

| Bidang | Paket dan rentang deklarasi |
|---|---|
| React | `react`, `react-dom`: `^19.3.0` |
| Router | `react-router`: `^8.4.0` |
| Bundler | `vite`: `^8.3.4`; `@vitejs/plugin-react`: `^6.1.2` |
| Bahasa/lint | `typescript`: `~6.0.2`; `oxlint`: `^1.87.0` |
| Tailwind | `tailwindcss`, `@tailwindcss/vite`: `^4.3.3` |
| Primitive UI | `radix-ui`: `^1.7.0`; `cmdk`: `^1.1.1` |
| Query/table | `@tanstack/react-query`: `^5.104.1`; `@tanstack/react-table`: `^9.2.8` |
| Form/validasi | `react-hook-form`: `^7.89.0`; `@hookform/resolvers`: `^5.9.1`; `zod`: `^4.6.5` |
| Rich text | `@tiptap/react`, `@tiptap/pm`, `@tiptap/starter-kit`, `@tiptap/extensions`: `^3.31.4` |
| Grafik | `recharts`: `^3.10.1` |
| Drag/drop | `@dnd-kit/core`: `^6.3.1`; `sortable`: `^10.0.0`; `modifiers`: `^9.0.0`; `utilities`: `^3.2.2` |
| Persistence | `idb-keyval`: `^6.3.0` |
| Calendar | `react-day-picker`: `^10.0.2` |
| Ikon | `@tabler/icons-react`: `^3.49.0`; `lucide-react`: `^1.54.0` |
| Toast | `sonner`: `^2.0.8` |
| CSS utility | `class-variance-authority`: `^0.7.1`; `clsx`: `^2.1.1`; `tailwind-merge`: `^3.7.0`; `tw-animate-css`: `^1.4.0` |

shadcn/ui di sini berarti pola dan source komponen yang masuk project, bukan satu runtime framework/backend. Tidak ditemukan `components.json` pada inventaris tracked. Menambahkan komponen lewat generator perlu memeriksa alias/theme yang ada; jangan menginisialisasi ulang project secara otomatis.

## 6. Struktur menu dan peta halaman publik

### 6.1 Header desktop

```text
Brand: lambang NTT + PINTU / Samsat Kota Kupang → index.html
Profil
├── Tentang UPTD → profil.html
├── Visi & Misi → visi-misi.html
└── Struktur Organisasi → struktur-organisasi.html
Layanan
├── Persyaratan Layanan → layanan.html
├── Alur Non-Tunai → layanan.html#alur
└── Simulasi Pajak → layanan.html#simulasi
Jadwal
├── Jam Pelayanan → jadwal.html
└── Samsat Keliling → jadwal.html#keliling
Informasi
├── Berita & Pengumuman → informasi.html
├── Dokumentasi Kegiatan → dokumentasi.html
└── Regulasi & Unduhan → unduhan.html
Kontak & Pengaduan → kontak.html
Ikon Login Pegawai → login.html
CTA Cek Persyaratan → layanan.html
```

Lima menu utama ialah Profil, Layanan, Jadwal, Informasi, Kontak & Pengaduan. Beranda dicapai melalui brand; akses pegawai dan CTA bukan tambahan kategori utama.

Menu seluler `#mobile-menu` mengelompokkan **Profil**, **Layanan & Jadwal**, dan **Informasi**, lalu CTA persyaratan/login. Tautan Alur Non-Tunai tidak tampil sebagai item khusus menu seluler; tetap tersedia melalui halaman layanan. Menu aktif ditulis ke HTML lewat skrip partial, bukan parsing `.html` pada runtime.

### 6.2 Footer

Empat kolom: identitas/deskripsi instansi, alamat kantor, jam loket, dan kontak. Tautan sekunder Profil/Layanan/Jadwal/Informasi/Kontak & Pengaduan, kembali ke atas, kemudian Login Pegawai/Kebijakan Privasi/Syarat & Ketentuan/Design System. Alamat, telepon, dan surel masih contoh dan perlu diganti dari source partial serta konten halaman.

### 6.3 Inventaris 17 HTML root

| File | Isi/sections dan fungsi | Status penting |
|---|---|---|
| `index.html` | Hero; papan layanan; tiga manfaat; tab tiga layanan/checklist; mitra; transparansi; kanal; simulasi bento; integritas; biaya; FAQ; penutup; dialog video. | Implementasi UI/interaksi lokal; antrean/statistik/media/pengumuman contoh. |
| `profil.html` | Hero, media gedung, kedudukan/fakta, tugas-fungsi, sambutan, lanjut membaca. | Foto/pejabat/identitas perlu dilengkapi. |
| `visi-misi.html` | Visi provinsi, lima misi, kontribusi PINTU, BerAKHLAK, maklumat. | Teks ada; kebenaran resmi dan dokumen perlu pengesahan, bukan dinyatakan terverifikasi oleh audit. |
| `struktur-organisasi.html` | Bagan CSS, uraian tugas accordion, empat peran informasi. | Susunan/nama/dasar hukum perlu verifikasi; bukan direktori user backend. |
| `layanan.html` | Sidebar/scroll-spy, lima layanan, tambahan, checklist/tabel/cetak, alur non-tunai, simulasi. | Persyaratan disalin dari bahan rancangan; nomor loket/aturan wajib validasi. |
| `jadwal.html` | Status/countdown, tabel jam mingguan, kalender keliling/map links. | Jam dan rotasi CONFIG contoh; bukan feed petugas. |
| `informasi.html` | Pengumuman/sorotan, kartu berita, kategori/search, arsip/pagination. | Search/filter lokal; pagination/berita contoh. |
| `berita-detail.html` | Template artikel peluncuran, metadata, rich content, share/copy/print, terkait. | Satu file/template statis, bukan detail CMS per slug. |
| `dokumentasi.html` | Filter kategori, grid/bento galeri, lightbox. | Foto/ilustrasi placeholder. |
| `unduhan.html` | Kategori/search, daftar regulasi/standar/formulir/laporan, rujukan JDIH. | Tombol berkas masih `href="#"`; belum download nyata. |
| `kontak.html` | Kartu kontak, peta/arah, formulir masukan, kanal pengaduan. | Identitas contoh dan submit demo tanpa penerimaan server. |
| `login.html` | NIP/sandi, toggle visibility, ingat perangkat, pesan sesi/error, SSO. | Demo auth klien, bukan login aman produksi. |
| `dashboard-pegawai.html` | Meta refresh dan JavaScript redirect ke `dashboard/`. | Kompatibilitas alamat dashboard lama, bukan dashboard source. |
| `design-system.html` | Living styleguide: brand/tokens/type/components/forms/status/icons. | Referensi developer publik, bukan halaman layanan masyarakat. |
| `kebijakan-privasi.html` | Draf kebijakan data/storage. | Perlu sinkronisasi implementasi dan review hukum. |
| `syarat-ketentuan.html` | Draf syarat, hak cipta, batas tanggung jawab. | Perlu review hukum/instansi. |
| `404.html` | Galat/tautan pemulihan. | Root custom HTTP 404 pada hosting. |

### 6.4 Layanan yang dipetakan

Lima kartu aktual: **Pajak tahunan** (`#pajak-tahunan`), **Perpanjangan STNK 5 tahun** (`#lima-tahun`), **Mutasi keluar, pemilik tetap** (`#mutasi-tetap`), **Mutasi keluar, ganti pemilik/BBN** (`#mutasi-bbn`), dan **Duplikat STNK hilang/rusak** (`#duplikat`). Kartu BBN bukan seluruh proses balik nama yang terpisah dari mutasi. Tambahan badan hukum/perwakilan tersedia, khususnya pada duplikat. Beranda menonjolkan pajak tahunan, lima tahun, dan mutasi keluar. Detail meterai, dokumen asli/fotokopi, dan cek fisik mengikuti `layanan.html:210–329`; wajib dikonfirmasi petugas sebelum menjadi acuan resmi.

## 7. Fitur portal publik dan perjalanan pengguna

### 7.1 Inventaris interaksi aktual

Source pusat `assets/js/main.js` menginisialisasi fitur secara selektif dari selector `data-*`; tidak semua halaman memuat semua komponen.

| Fitur | Mekanisme aktual | Batas |
|---|---|---|
| Navbar/dropdown/mobile | State menu, dismissal/keyboard sesuai handler. | Tidak boleh menghapus aria/data hooks saat restyling. |
| Reveal saat scroll | IntersectionObserver/kelas reveal dan reduced-motion CSS. | Bukan bukti semua konten no-JS tampil pada semua state. |
| Status loket | `Intl.DateTimeFormat` zona `Asia/Makassar`, jadwal CONFIG, buka/tutup dan countdown. | Waktu dihitung lokal, bukan status manual server; libur/perubahan darurat belum otomatis dari CMS. |
| Tabel jam | Menandai hari WITA saat ini. | Tabel footer dan CONFIG berpotensi berbeda jika diedit terpisah. |
| Samsat Keliling | Rotasi hari kerja, dua titik contoh per hari, render minggu berjalan/berikutnya dan Google Maps links. | Tanggal otomatis tidak menjadikan jadwal contoh sebagai jadwal resmi. |
| Tab layanan | Pilih panel pajak tahunan/lima tahun/mutasi. | Konten statis. |
| Checklist berkas | Checkbox/count siap pada tab layanan beranda; halaman detail layanan terutama tabel persyaratan dan tombol cetak. | Tidak memverifikasi dokumen; bukan pengajuan layanan. |
| Scroll-spy | Menandai subnav bagian layanan yang terlihat. | Uji keyboard/scroll offset tetap diperlukan. |
| Transparansi | Empat dataset transaksi/penerimaan/keliling/kepuasan; rentang ytd/12m/3m/30d/today; SVG charts/sparklines. | Dataset/range ilustratif, bukan query transaksi riil. |
| Simulasi PKB | Pokok, motor/mobil, bulan keterlambatan, opsen/denda/SWDKLLJ, toggle pemutihan/reset. | Nilai contoh; bukan tagihan resmi. |
| Angka responsif | `fitText` mengukur rentang teks dan mengurangi ukuran/meringkas nominal bila perlu. | Uji input besar, zoom, dan font OS. |
| Filter/search | Filter kategori + pencarian lokal judul/deskripsi, empty state. | Tidak mencari ke server; data hanya yang ada di DOM. |
| Galeri/lightbox | Native `<dialog>`, prev/next, keyboard panah/close. | Konten media placeholder dan navigasi filter perlu regression tests. |
| FAQ | `<details>/<summary>` native. | Tidak mengambil FAQ CMS. |
| Copy/print | Clipboard/cetak browser untuk artikel/layanan. | Clipboard butuh secure context/permissions; cetak perlu uji output. |
| Login | Validasi NIP 18 angka, banding demo, storage sesi, redirect terbatas. | Bukan verifikasi identitas. |
| Form masukan | Validasi browser, preventDefault, toast tiket lalu reset. | Tidak ada fetch/simpan laporan. |
| Video profil | Dialog berisi pesan video sedang disiapkan. | Tidak ada video nyata. |
| Interaksi penutup | Tombol pengingat map dan variasi microcopy. | Dekoratif; bukan proses bisnis. |

### 7.2 Rumus pajak yang dikodekan — bukan validasi hukum

Parameter default contoh: opsen 66%, denda 1% per bulan dengan batas 24 bulan, SWDKLLJ motor Rp35.000/mobil Rp143.000. Dashboard menggunakan `src/lib/pajak.ts`; portal memiliki implementasi sendiri di `main.js`.

```text
opsen       = pokok × persentase opsen
bulan       = keterlambatan yang dibatasi maksimum
denda asli  = (pokok + opsen) × persentase denda per bulan × bulan
denda       = 0 jika pemutihan; selain itu denda asli
total       = pokok + opsen + denda + SWDKLLJ
```

Penting: basis denda pada kode mencakup pokok + opsen; jangan mengganti atau mengesahkan rumus tanpa sumber aturan. Kebijakan pembulatan/format rupiah, batas input, jenis kendaraan, dan konsekuensi amnesti harus diuji dan disahkan petugas. Dua implementasi dan dua sumber konfigurasi membuat risiko drift.

### 7.3 Perjalanan pengguna

1. **Mempersiapkan berkas:** beranda/CTA persyaratan; pilih layanan; baca tabel asli/fotokopi/cek fisik; checklist; cetak; periksa jadwal sebelum datang.
2. **Mencari waktu/lokasi:** jadwal; lihat status WITA; baca jam; keliling; buka petunjuk Maps. Saat ini hasil berlandaskan contoh, bukan konfirmasi operasional.
3. **Mengestimasi pajak:** isi pokok dari notis; pilih motor/mobil dan bulan; lihat breakdown; pemutihan hanya jika berlaku. Keputusan akhir tetap notis resmi.
4. **Mencari kabar/dokumen:** informasi/unduhan; filter/search; buka artikel/berkas. Dokumen yang belum ada tidak boleh diberi kesan sudah terunduh.
5. **Menyampaikan keluhan:** kontak; pilih kanal resmi. Form demo tidak boleh dipakai sebagai kanal pengaduan riil.
6. **Mengelola konten:** login demo; pilih modul CMS; simpan/ajukan/terbitkan lokal. Portal publik tidak berubah karena langkah ini.

## 8. Struktur menu, route, dan fitur dashboard

### 8.1 Hierarki navigasi aktual

Sumber: `dashboard-app/src/app/nav.ts:48–120`. URL di bawah adalah route setelah hash, contoh lengkap: `/dashboard/#/berita`.

```text
Utama
├── Ringkasan /
├── Alur Publikasi /alur
├── Pesan & Pengaduan /pesan
└── Statistik Layanan /statistik
Konten
├── Berita & Pengumuman /berita
├── Dokumentasi Kegiatan /dokumentasi
├── Regulasi & Unduhan /unduhan
└── Tanya Jawab /faq
Layanan
├── Persyaratan Layanan /layanan
├── Jam Pelayanan /jadwal/jam
├── Samsat Keliling /jadwal/keliling
├── Papan Layanan /papan
└── Tarif & Simulasi /tarif
Profil Instansi
├── Tentang UPTD /profil
├── Visi & Misi /visi-misi
├── Struktur Organisasi /struktur
└── Kontak & Kanal /kontak
Situs
├── Beranda /beranda
├── Halaman Statis /halaman
└── Jadwal Pemutakhiran /pemutakhiran
Alat Bantu
├── Absensi Lapangan /absensi
└── Kalkulator PKB /kalkulator
Sekunder
├── Pengaturan /pengaturan
└── Panduan /panduan
```

### 8.2 Inventaris route dan fungsi

Seluruh penyimpanan berikut default **lokal per browser**. Kolom sasaran publik menyatakan rancangan pemetaan, bukan integrasi otomatis.

| Route hash | Fungsi/modul | Sasaran publik atau batas |
|---|---|---|
| `#/` | Ringkasan, KPI, grafik pesan, antrean kerja/pemutakhiran. | Bukan dashboard real-time server. |
| `#/alur` | Kanban alur publikasi untuk konten beralur; riwayat/catatan/transisi. | Role berupa label, bukan RBAC. |
| `#/pesan` | Catat pesan, jenis/sumber/status, penanganan dan tanggapan. | Form publik tidak mengisi koleksi ini. |
| `#/statistik` | Set transaksi, penerimaan, keliling, kepuasan; seri bulanan/status ilustrasi. | Rancangan untuk transparansi beranda. |
| `#/berita` | Tabel/filter dan tindakan berita/pengumuman. | `informasi.html`. |
| `#/berita/baru`, `#/berita/:id` | Editor judul/slug/kategori/ringkasan, rich text, sampul, metadata/sorotan/workflow. | Tidak menghasilkan file detail artikel publik. |
| `#/dokumentasi` | Koleksi kegiatan, kategori/lokasi/tanggal, daftar foto/caption, workflow. | `dokumentasi.html`. |
| `#/unduhan` | Dokumen regulasi/standar/formulir/laporan, format, nomor/tahun, file/tautan, workflow. | `unduhan.html`. |
| `#/faq` | Pertanyaan/jawaban/tampil/urutan. | FAQ beranda belum membaca koleksi. |
| `#/layanan` | Daftar layanan dan pengaturan publikasi. | `layanan.html`. |
| `#/layanan/baru`, `#/layanan/:id` | Nama/slug, loket, dokumen/jumlah, cek fisik/non-tunai/diwakilkan, label, unggulan. | Rancangan persyaratan dan tab beranda. |
| `#/jadwal/jam` | Jam per hari, cutoff berkas, pemberitahuan; penyesuaian libur/jam khusus. | `jadwal.html`; portal masih CONFIG sendiri. |
| `#/jadwal/keliling` | Hari/jam/lokasi/kecamatan/layanan/catatan/aktif. | `jadwal.html#keliling`. |
| `#/papan` | Antrean, layanan antrean, estimasi tunggu, status loket. | Papan di beranda belum membaca pengaturan. |
| `#/tarif` | Opsen, denda/batas, SWDKLLJ, nilai contoh dan catatan. | Kalkulator internal membaca; simulasi publik masih CONFIG. |
| `#/profil` | Identitas, kedudukan, tugas/fungsi, foto/sambutan. | `profil.html`. |
| `#/visi-misi` | Visi/sumber, misi, kontribusi, nilai, maklumat/file. | `visi-misi.html`. |
| `#/struktur` | Unit/jenis/induk, jabatan/pejabat/NIP, foto, anggota, uraian tugas. | NIP internal jangan diterbitkan tanpa kebijakan. |
| `#/kontak` | Identitas kontak, media sosial, map URL, janji balasan, kanal pengaduan. | `kontak.html`. |
| `#/beranda` | Hero, nilai/manfaat, layanan utama, mitra, kanal, integritas, biaya, penutup. | `index.html` tidak diperbarui oleh simpan. |
| `#/halaman` | Daftar halaman statis. | Kebijakan dan syarat masih file HTML sendiri. |
| `#/halaman/baru`, `#/halaman/:id` | Slug, judul dua nada, pengantar, rich-text body. | Tidak menghasilkan routing publik dinamis. |
| `#/pemutakhiran` | Konten/modul/unit/frekuensi/interval hari dan jatuh tempo. | Pengingat berdasarkan metadata lokal. |
| `#/absensi` | Datang/pulang, kegiatan, foto, GPS, riwayat. | Bukan bukti kehadiran anti-manipulasi. |
| `#/kalkulator` | Estimasi PKB/opsen/denda/SWDKLLJ, amnesti, salin ringkasan. | Tidak menerbitkan notis atau menagih pembayaran. |
| `#/pengaturan` | Situs, profil akun, ekspor/impor/reset, aktivitas. | Profil akun bukan user management/auth server. |
| `#/panduan` | Panduan pengelola. | Dokumentasi UI internal. |
| wildcard `#/*` | Halaman dashboard tidak ditemukan. | Berbeda dari root HTTP `404.html`. |

Router: `src/app/router.tsx:54–94`; lazy imports memisahkan halaman menjadi chunks. Error boundary memberikan pesan gagal memuat, termasuk kebutuhan reload saat chunk lama tidak tersedia setelah deployment.

### 8.3 Navigasi pendukung

- Menu **Buat konten** memiliki shortcut berita, dokumentasi, unduhan, keliling, FAQ, dan catat pesan.
- Form cepat pada beberapa modul memakai query `?baru=1`; editor berita/layanan/halaman memakai route tersendiri.
- Command menu `Ctrl K`/`⌘K` mencari halaman, shortcut, judul/ID berita, nama/ID layanan, dan judul/ID dokumen; juga menyediakan theme, lihat portal, absensi, dan logout. Ini bukan full-text search seluruh body artikel atau seluruh koleksi. Source: `src/components/command-menu.tsx:61–170`.
- Sidebar, header, akun, theme provider, toast, confirmation dialog, unsaved-change guard, media fields, dan rich text adalah komponen bersama.

## 9. Model data, penyimpanan, dan alur publikasi

### 9.1 Kontrak dasar

Source utama: `dashboard-app/src/lib/api/types.ts` (473 baris saat audit).

```typescript
type StatusAlur = "draf" | "diperiksa" | "persetujuan"
  | "terbit" | "dikembalikan" | "arsip"

interface Entri {
  id: string
  dibuat: string       // ISO date-time
  diperbarui: string   // ISO date-time
  urutan: number
}

interface Beralur {
  status: StatusAlur
  penyedia: string
  pemeriksa: string
  catatanRevisi: string
  terbitPada: string | null
  riwayat: { waktu: string; oleh: string; aksi: string;
             ke?: StatusAlur; catatan?: string }[]
}
```

TypeScript types mendefinisikan kontrak klien, bukan validasi server. `IsoDateTime`, `IsoDate`, dan `Jam` adalah string aliases; backend harus memvalidasi format dan aturan domain.

### 9.2 Dua belas koleksi

| Kunci koleksi | Field/domain penting | Workflow |
|---|---|---|
| `berita` | judul, slug, kategori berita/pengumuman/keliling/edukasi, ringkasan, HTML isi, sampul, caption/kredit, tanggal, sorotan/pengumumanBeranda. | Beralur. |
| `dokumentasi` | judul, kategori keliling/sosialisasi/operasi/internal, lokasi/tanggal/deskripsi, foto `{id,src,keterangan}`. | Beralur. |
| `dokumen` | judul/keterangan, kategori regulasi/standar/formulir/laporan, format PDF/DOCX/XLSX/JPG/Tautan, nomor/tahun, berkas atau URL. | Beralur. |
| `layanan` | nama/slug/ringkasan/loket, jenis layanan/tambahan, flags, label, persyaratan dokumen/jumlah, catatan, unggulan. | Beralur. |
| `faq` | pertanyaan, jawaban, tampil. | Tanpa workflow. |
| `keliling` | hari `0–6`, mulai/selesai, lokasi, kecamatan, layanan, catatan, aktif. | Tanpa workflow. |
| `penyesuaian` | tanggal, jenis tutup/jam-khusus, mulai/selesai, keterangan. | Tanpa workflow. |
| `unit` | nama/singkatan/jenis, induk ID, jabatan/pejabat/NIP, foto, anggota, tugas/catatan. | Tanpa workflow. |
| `pesan` | tiket, jenis, nama/kontak, layanan/isi, sumber, status baru/diproses/selesai, diterima/ditangani, tanggapan. | Alur penanganan pesan, bukan StatusAlur publikasi. |
| `halaman` | slug, judul/lanjutan, pengantar, HTML isi. | Tanpa workflow. |
| `pemutakhiran` | konten, modul, unit, frekuensi, intervalHari. | Pemantauan, bukan scheduler server. |
| `absensi` | datang/pulang, kegiatan/jenis, waktu, petugas/NIP, foto, lokasi lat/lng/akurasi atau null, tempat/catatan. | Internal; tanpa workflow publikasi. |

Semua entri koleksi mewarisi `Entri`. Jangan mengklaim seluruh perubahan situs melewati persetujuan: hanya empat koleksi mewarisi `Beralur`.

### 9.3 Sepuluh dokumen pengaturan

| Kunci | Isi |
|---|---|
| `situs` | Nama portal/instansi/induk, deskripsi/URL/hak cipta, bilah pengumuman. |
| `beranda` | Hero, manfaat/nilai, layanan utama, mitra, kanal, integritas, biaya, penutup. |
| `papan` | Nomor antrean/layanan, waktu tunggu/selisih, daftar loket/status, catatan/waktu pembaruan. |
| `jam` | Hari buka/jam, batasBerkasMenit, catatan/pemberitahuan. |
| `tarif` | opsenPersen, dendaPersenPerBulan, maksBulanDenda, SWDKLLJ motor/mobil, contohPokok/Jenis/Telat. |
| `statistik` | ilustrasi/terverifikasi, tahun/catatan, empat set kategori, metric tiles, 12 nilai bulanan. |
| `profil` | Identitas, kedudukan, foto/caption, tugas/fungsi, sambutan. |
| `visiMisi` | Visi/sumber, misi/kontribusi/nilai, maklumat/penandatangan/file. |
| `kontak` | Alamat/kanal kontak/social/maps, janjiBalasan dan kanal pengaduan. |
| `akun` | nama, jabatan, unit, peran, email; tidak menyimpan sistem user/password server. |

`Berkas` memuat `nama`, `ukuran` byte, `tipe`, `url`: data URL pada mode lokal atau URL server pada integrasi.

### 9.4 Persistence, cache, dan backup

- Database IndexedDB `pintu-dashboard`, object store `data`, melalui `idb-keyval`.
- Seed dari `src/lib/api/seed.ts` dimuat bila metadata belum ada.
- Fallback memori tersedia bila persistence gagal; data dapat hilang saat reload/penutupan. Warning tidak sama dengan backup.
- Operasi tulis diserialkan dalam instance klien. Jangan menyimpulkan ini memberikan transaksi lintas-tab, conflict resolution multiuser, atau DB integrity server.
- Audit aktivitas maksimal 300 entri, dengan pelaku/waktu/aksi/modul/target. Log lokal bisa diubah/dihapus pengguna; bukan tamper-proof audit trail.
- Snapshot versi data 1: `{versi, diekspor, koleksi, pengaturan, aktivitas}`.
- Ekspor mengemas seluruh data; impor memvalidasi struktur/versi sebelum overwrite; reset mengembalikan seed. Semuanya membutuhkan kewaspadaan terhadap kehilangan data dan PII.
- Foto/data URL dapat memperbesar IndexedDB dan JSON backup. Data tidak tersinkron antarbrowser/perangkat, termasuk antar-origin `pages.dev` dan custom domain.
- TanStack Query (`src/lib/queries.ts`) memakai staleTime 30 detik, refetch saat window focus hanya mode server, dan tidak retry 401/403/404. Mutasi mengisi/menginvalidate cache; reorder optimistis memiliki rollback cache bila gagal. Ini tidak menggantikan concurrency control DB. `laporGalat` men-trigger logout sekitar 1,2 detik untuk 401 yang melewati helper tersebut; tidak ada refresh-token protocol.

### 9.5 Media dan alat bantu internal

`src/lib/gambar.ts` membatasi input gambar 15 MiB dan berkas umum 10 MiB; tipe foto JPEG/PNG/WebP. Kompresi default sisi terpanjang 1600px, JPEG quality 0.82, PNG tetap PNG untuk transparansi, dan hasil hanya dipakai bila lebih kecil dari asli. Modul absensi meminta 1024px. Validasi peramban bukan pengganti validasi MIME/content/ukuran pada server.

Absensi mencatat datang/pulang, kegiatan/jenis, nama/NIP, waktu, foto dan koordinat/akurasi. Lokasi bisa `null` bila izin ditolak; keterangan tempat menjadi fallback. Catatan absensi ditujukan sebagai bukti lokal; menghapus/mengubah database atau waktu perangkat tetap mungkin. Kalkulator menyediakan salin ringkasan dan nominal denda asli yang dihapus saat amnesti; tidak menyimpan transaksi pembayaran resmi.

Pengambilan foto menggunakan file input native dengan `capture="environment"`, **bukan** live stream `getUserMedia`; capture hanya petunjuk kepada browser. GPS memakai `enableHighAccuracy:true`, timeout20detik, maximumAge0; akurasi >100m diberi peringatan. Foto wajib, lokasi dapat diganti keterangan tempat sesuai validasi. Permission otomatis ketika panel dipasang membutuhkan penjelasan UX dan uji perangkat.

### 9.6 State machine publikasi aktual

```text
draf            Ajukan pemeriksaan        diperiksa
diperiksa       Teruskan ke penyetuju     persetujuan
diperiksa       Kembalikan + catatan      dikembalikan
persetujuan     Setujui & terbitkan       terbit
persetujuan     Kembalikan + catatan      dikembalikan
dikembalikan    Ajukan ulang              diperiksa
terbit          Tarik dari portal         arsip
arsip           Pulihkan ke draf          draf
```

Timestamp terbit dan riwayat diperbarui oleh logika klien. Label Penyedia/Pemeriksa/Atasan/Pengelola menjelaskan proses bisnis, tetapi **tidak membatasi aksi berdasarkan identitas/peran yang diverifikasi**. Status `terbit` lokal tidak mempublikasikan ke HTML.

## 10. Autentikasi, integrasi, dan kontrak API

### 10.1 Login demo dan sesi

Source: `assets/js/main.js` bagian `CONFIG.auth` dan handler login; `dashboard-app/src/lib/auth.ts`; `src/app/layout.tsx`.

- Akun demo diketahui publik melalui README/source. Login membandingkan NIP/sandi di peramban; tidak ada endpoint login server.
- Storage key `pintu.sesi`; sesi normal disimpan `sessionStorage` dengan 8 jam, opsi ingat memakai `localStorage` dengan 7 hari.
- Struktur sesi berisi NIP, waktu masuk/kedaluwarsa, flag ingat, dan optional token.
- Dashboard memeriksa sesi lokal, termasuk interval 60 detik, `visibilitychange`, dan `storage`, lalu redirect ke login bila berakhir.
- Sesi tidak bertanda tangan dan dapat dibuat/diubah pengguna. Guard adalah navigasi UX, **bukan batas keamanan**.
- SSO ditampilkan sebagai rencana/demo. Field peran/profil akun bukan provisioning pengguna.
- Token server optional tidak dihasilkan oleh login demo. Mengisi `VITE_API_URL` saja tidak menyelesaikan auth.

Dokumen ini sengaja tidak mengulang NIP/sandi lengkap: keberadaan kredensial statis sudah cukup sebagai bukti risiko; akun demo ada dalam README saat dibutuhkan untuk pratinjau. Jangan memakai kredensial ini sebagai akun produksi.

### 10.2 Pemilihan API saat build

`src/lib/api/index.ts` memilih `HttpApi` bila `import.meta.env.VITE_API_URL?.trim()` berisi nilai, selain itu `LocalApi`. `VITE_*` adalah konfigurasi frontend yang masuk bundle; **bukan tempat menyimpan secret**. Mengubah environment hosting tanpa rebuild tidak otomatis mengganti adapter pada bundle yang sudah committed.

### 10.3 Kontrak HTTP yang benar-benar dikodekan

Source: `dashboard-app/src/lib/api/http.ts:22–130`.

| Method | Endpoint relatif base URL | Payload/hasil penting |
|---|---|---|
| GET | `/koleksi/:koleksi` | Array entri sesuai tipe koleksi. |
| GET | `/koleksi/:koleksi/:id` | Entri; 404 diterjemahkan klien menjadi `null`. |
| POST | `/koleksi/:koleksi` | Draf entri; hasil entri. |
| PATCH | `/koleksi/:koleksi/:id` | Partial entri; hasil entri. |
| POST | `/koleksi/:koleksi/hapus` | `{ids}`; bulk removal. |
| PUT | `/koleksi/:koleksi/urutan` | `{ids}`; urutan. |
| GET / PUT | `/pengaturan/:nama` | Dokumen pengaturan. |
| GET | `/pengaturan` | Map nama pengaturan ke waktu pembaruan. |
| GET | `/aktivitas?limit=50` | Array aktivitas. |
| DELETE | `/aktivitas` | Hapus riwayat. |
| POST | `/unggah` | FormData field `berkas`; hasil metadata Berkas. |
| GET | `/ekspor` | Snapshot. |
| POST | `/impor` | Snapshot. |
| POST | `/reset` | Reset. |

Klien meminta `Accept: application/json`, mengirim JSON untuk body non-FormData, menambahkan `Authorization: Bearer` hanya bila sesi memiliki token, mendukung 204, dan membaca error `pesan`/`message`. Error 401 diterjemahkan ke pesan sesi berakhir, tetapi bukan implementasi refresh/revocation lengkap. Tidak ada `credentials: include` pada fetch; pindah ke cookie auth memerlukan revisi kontrak klien/CORS/CSRF.

**Belum ada endpoint auth/public/read-only yang diimplementasikan server.** Tidak ada endpoint khusus `/:id/status` di adapter ini; perubahan workflow harus ditetapkan server secara eksplisit atau diamankan melalui PATCH yang ada. Jangan menuliskan endpoint rekomendasi sebagai kontrak yang telah tersedia.

### 10.4 Pilihan arsitektur berikutnya — belum keputusan implementasi

1. **API pusat + portal membaca data publik:** memerlukan endpoint publik yang memfilter status terbit dan PII, rendering aman, loading/error states, caching, SEO, dan persistence server.
2. **CMS + publikasi build statis:** backend menyimpan data resmi; konten yang disetujui menghasilkan HTML/JSON publik dan memicu build Pages. Cocok bila publikasi tidak membutuhkan real-time.

Pilihan Laravel/Node/Cloudflare Workers/DB belum ditetapkan. Tetapkan dahulu hosting backend, identitas pengguna, RBAC, model public/private data, dan strategi publikasi. Hindari memilih framework baru atau migrasi frontend tanpa kebutuhan.

## 11. Design system portal publik

### 11.1 Arah visual dan sumber kebenaran

Portal memakai bahasa **editorial tenang**: kertas hangat, tinta biru-hitam, headline sans dua nada, ruang kosong besar, navbar gelap berbentuk pil, dan panel mockup gelap di dalam halaman terang. Referensi TinyKPI/Kage tercatat sebagai inspirasi struktur, bukan brand/copy yang disalin. Prioritas pemerintah: informasi mudah dipahami dan tugas layanan jelas.

Sumber token aktual: `assets/css/tokens.css`; pemakaian `base.css`, `components.css`, `pages.css`. `design-system.html` adalah living showcase. Rancangan dan showcase harus disinkronkan bila token berubah.

### 11.2 Palet warna lengkap

| Kelompok | Token | Nilai aktual | Pemakaian |
|---|---|---|---|
| Latar | `--paper`, `--paper-2`, `--paper-3`, `--surface` | `#F7F6F2`, `#EFEDE6`, `#E9E6DD`, `#FFFFFF` | Halaman, kanvas, hover kanvas, kartu/form. |
| Teks | `--ink`, `--ink-2` | `#0F1319`, `#2A2F37` | Teks utama, tombol/navbar. |
| Nada sekunder | `--gray`, `--text-2` | `#82878E`, `#646A72` | Gray hanya headline besar; text-2 untuk body pendukung. |
| Garis | `--line`, `--line-2` | `#E7E4DC`, `#D9D5CA` | Hairline, garis lebih tegas. |
| Aksen | `--accent`, `--accent-ink`, `--accent-soft` | `#2B63F6`, `#2457E0`, `#E8EEFE` | Tautan/aktif/fokus; bukan semua panel dihias biru. |
| Panel gelap | `--night`, `--night-2`, `--night-3`, `--night-line` | `#0A0A0C`, `#17181B`, `#202125`, `#2A2B30` | Panel/tile/hover/input/garis. |
| Teks panel | `--night-text`, `--night-muted`, `--night-faint`, `--night-accent` | `#FFFFFF`, `#9BA0A8`, `#5C6068`, `#7FA4FF` | Teks, pendukung, de-emphasis chart, aksen gelap. |
| Sukses | `--ok`, `--ok-soft`, `--ok-night` | `#1A7349`, `#E3F2EA`, `#5FD39B` | Status sukses/buka. |
| Peringatan | `--warn`, `--warn-soft` | `#8C5900`, `#F8EED9` | Warning/jatuh tempo. |
| Bahaya | `--danger`, `--danger-soft`, `--danger-night` | `#B93A28`, `#F9E4DF`, `#FF8F7E` | Error/bahaya. |

Status harus disertai label/ikon, bukan hanya warna. `--gray` sekitar 3,35:1 pada paper dan 3,09:1 pada paper-2 menurut token/rancangan; jangan digunakan untuk teks kecil yang membutuhkan 4,5:1. Kontras token tidak otomatis menyatakan seluruh kombinasi/state aplikasi lulus.

### 11.3 Tipografi

Font display: `"SF Pro Display", -apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`.

Font body: urutan sama dengan `"SF Pro Text"` sebagai nama pertama. SF Pro tidak didistribusikan sebagai web font; perangkat Apple memakai sistem Apple, Windows Segoe UI, Android Roboto. Screenshot Linux historis menggunakan fallback dan tidak boleh dianggap bukti font Apple ter-host.

| Token | Nilai |
|---|---|
| `--fw-display` | `600` |
| `--fs-hero` | `clamp(36px, 4.6vw + 22px, 80px)` |
| `--fs-display` | `clamp(36px, 5.2vw, 64px)` |
| `--fs-h2` | `clamp(30px, 4vw, 48px)` |
| `--fs-h3` | `clamp(24px, 2.2vw, 28px)` |
| `--fs-metric` | `clamp(28px, 3vw, 40px)` |
| Lead / body / small | `18px` / `16px` / `14px` |
| Micro / eyebrow | `12.5px` / `11px` |
| Tight / heading / body line-height | `1.05` / `1.08` / `1.6` |
| Tracking headline / eyebrow | `-0.022em` / `0.12em` |
| Measure | `38ch` |

Headline memakai kalimat lengkap, titik, dan `<span class="tone">` untuk lanjutan gray; `text-wrap: balance`. Display ditujukan teks ≥20px; body/UI memakai Text. `--font-mono` sebenarnya alias font body dengan tabular numerals, **bukan font monospace**. Gunakan nonbreaking space untuk angka/satuan penting.

### 11.4 Layout, spacing, radius, elevation

| Aspek | Nilai aktual |
|---|---|
| Spacing scale | `--s-1/2/3/4/5/6/8/10/12/16/20/24` = `4/8/12/16/20/24/32/40/48/64/80/96px`. |
| Section | `--section-y: clamp(96px, 12vw, 160px)`; `--inner-y:64px`. |
| Gutter | `clamp(16px, 4vw, 40px)`. |
| Container | `1200px`; narrow `760px`. |
| Radius | pill `999px`; xl `32px`; lg `24px`; md `16px`; sm `12px`; xs `8px`. |
| Hairline | `1px solid var(--line)`. |
| Soft shadow | `0 1px 2px rgba(15,19,25,.04), 0 12px 32px -16px rgba(15,19,25,.16)`. |
| Nav shadow | `0 10px 30px -12px rgba(15,19,25,.45)`. |
| Popup shadow | `0 24px 60px -20px rgba(15,19,25,.35)`. |
| Z index | nav `50`, popup `60`, modal `80`. |

Bayangan minimal; pemisahan lebih banyak memakai hairline/kontras permukaan. Layout menggunakan container, split, grid, bento, tables dan responsive stack, bukan sistem frontend utility Tailwind pada portal publik.

### 11.5 Motion

`--ease: cubic-bezier(.2,.7,.2,1)`, durasi `160ms`, `240ms`, `600ms` untuk hover/dropdown/reveal. Tanpa kebutuhan parallax. Portal memiliki aturan reduced motion; dashboard perlu penilaian tersendiri. Jangan menganimasikan lambang resmi.

### 11.6 Komponen publik

- Floating pill navbar/dropdown gelap, mobile menu, brand lockup, login icon, CTA.
- Button dark/light/ghost/night serta ukuran utama/kecil; link/arrow/underline.
- Eyebrow, dua-tone heading, lead, captions, breadcrumb, proto-note.
- Kartu/media/placeholder, numbered tabs, checklist, badges/chips/status, partner strip.
- Accordion native, tables, organisasi tree/uraian tugas.
- Papan antrean, metrics/sparkline, range filter, notch kanal.
- Canvas/bento simulasi, money input, segmented radios, stepper, switch.
- Form input/select/textarea/checkbox, search, validation messages.
- Dialog/video/lightbox, toast, price card, footer, print styles.

Pertahankan state hover/focus/selected/disabled/error, accessible names, dan selector data ketika menyusun komponen baru.

### 11.7 Brand dan aset

Logo utama **Lambang Provinsi NTT**, bukan ikon pintu. PNG asli `lambang-ntt.png` dan turunan WebP 96/192/360; favicon PNG/apple touch/icon 192. Lockup lambang dan tulisan PINTU dibuat terpisah untuk menjaga kejernihan dan keterbacaan.

Ketentuan rancangan: tidak mengganti warna, memotong, memutar, meregangkan, menambah efek/bayangan, atau menaruh lambang di foto ramai. Proporsi harus terjaga. Logo cadangan `assets/img/logo-pintu.svg`, `favicon.svg`, dan sprite `#logo-pintu` masih disimpan, bukan primary brand. Lambang milik Pemprov NTT; penggunaan/versi resmi perlu mengikuti ketentuan instansi. Lisensi SF Pro tidak mengizinkan penyebaran webfont menurut kebijakan project.

## 12. Design system dashboard dan konsistensi visual

### 12.1 Arah dan layout

Dashboard mengikuti pola shadcn/ui **dashboard-01** / tema new-york yang disesuaikan: sidebar berwarna kertas, area kerja putih, primary tinta, aksen/fokus/grafik biru. Dashboard memiliki light/dark/system theme, default system, dan preferensi `localStorage` key `pintu.tema`; bila storage ditolak, theme tetap dapat berlaku pada sesi. Portal publik **selalu light chrome**, meskipun panel demonstrasi di dalamnya gelap.

### 12.2 Token dashboard

Source: `dashboard-app/src/index.css:57–136`. `@theme inline` memetakan variable ke utility Tailwind; token dashboard bukan import langsung token portal, sehingga perubahan harus diselaraskan manual.

| Token dashboard | Light | Dark |
|---|---|---|
| Background | `#ffffff` | `#0d0e11` |
| Foreground | `#0f1319` | `#f4f4f3` |
| Card | `#ffffff` | `#141519` |
| Popover | `#ffffff` | `#17181b` |
| Primary / primary text | `#0f1319` / `#ffffff` | `#f4f4f3` / `#0f1319` |
| Secondary / muted | `#f2f1ec` | `#222328` |
| Muted foreground | `#5e646c` | `#a3a8b0` |
| Accent surface | `#f0eee8` | `#26272c` |
| Destructive | `#b93a28` | `#ff8f7e` |
| Border | `#e7e4dc` | putih alpha 10%. |
| Input | `#dcd8cd` | putih alpha 16%. |
| Ring/brand | `#2b63f6` | `#7fa4ff` |
| Sidebar | `#f7f6f2` | `#0a0a0c` |
| Sidebar accent | `#ecebe5` | `#1e1f23` |
| Success | `#1a7349` | `#5fd39b` |
| Warning | `#8c5900` | `#f2c46b` |

Light chart palette: `#2b63f6`, `#9cb8ff`, `#1a3fa8`, `#5f87f8`, `#0f1319`. Dark: `#7fa4ff`, `#2b63f6`, `#c3d4ff`, `#4c7cf7`, `#f4f4f3`.

Base radius `0.625rem` (10px pada root 16px); sm/md/lg/xl derived `radius−4/−2/0/+4px`. Ini lebih padat daripada kartu portal 24–32px; perbedaan sadar sesuai tugas CMS, bukan berarti semua radius harus diseragamkan. Font stack SF Pro sama. Rich text `.prose-pintu` body 15px, line-height 28px, heading/list/quote/link styles.

### 12.3 Inventaris UI source

`components/ui/` mencakup: alert-dialog, alert, avatar, badge, button, calendar, card, chart, checkbox, command, dialog, dropdown-menu, empty, form, input, kbd, label, popover, radio-group, select, separator, sheet, sidebar, skeleton, sonner, spinner, switch, table, tabs, textarea, toggle-group, toggle, tooltip.

Komponen produk di atas primitive: data-table/row-actions/status-badge/confirmation; entri-sheet/fields/list/item/media/photo/rich-text/settings-form/unsaved; kanban/riwayat; chart-area/KPI; command menu; nav/header/sidebar/theme.

### 12.4 Titik konsistensi dan drift

- Sama: brand/lambang, fonts, tinta/aksen, status, Bahasa Indonesia.
- Berbeda sengaja: publik editorial berjarak besar vs CMS padat; permukaan paper vs putih; radius dan skala type; dark mode hanya dashboard.
- Risiko: token duplikat, ikon Tabler + Lucide + public sprite, komponen/status labels/UX states berbeda, font OS berbeda, source/build dashboard dapat tertinggal.
- Perbaikan sebaiknya mendefinisikan semantic mapping dan acceptance visual, bukan mengganti seluruh UI menjadi satu template baru.

**Perbedaan semantik penting:** `--accent` publik adalah biru; `--accent` dashboard adalah surface hover netral. Gunakan `brand`/`ring` dashboard untuk peran biru, jangan memindahkan nama token secara buta. `font-mono` dashboard juga tidak dioverride menjadi font sans seperti `--font-mono` publik.

Dimensi aktual: input publik 48px minimum, font15px, radius14px; textarea128px minimum. Input dashboard36px, tombol dashboard24/32/36/40px sesuai variant size, focus ring3px. Sidebar layout memakai 72 unit spacing (288px dengan default4px), header12 unit (48px); primitive mobile sidebar18rem. Perbedaan density perlu diuji pada touch, bukan otomatis dianggap konsisten.

## 13. Audit UI/UX, aksesibilitas, dan responsivitas

### 13.1 Kekuatan yang tampak di source

- Tugas utama dan CTA persyaratan jelas; hierarki informasi publik relatif ringkas.
- Typeface sistem, headline cair, pembatas lebar teks, dan dua-tone memberi karakter konsisten.
- Skip link, `lang="id"`, focus-visible biru 2px, label kontrol, status label/ikon, native details/dialog dan aria states tersedia.
- Tab umum memiliki arrow/Home/End handlers dan roving tabIndex (`main.js:128–150`).
- Komponen dashboard menyediakan dialog/sheet/alert confirmations, loading/empty/error, dan unsaved-change guard; manfaat perlu diuji pada integrasi nyata.
- Mode reduced motion portal mematikan smooth scrolling dan hampir seluruh durasi animasi (`base.css:141–145`).

Ini bukti implementasi fondasi, **bukan sertifikasi WCAG 2.2 AA**.

### 13.2 Responsive behavior aktual

| Area | Breakpoint/pola source |
|---|---|
| Navbar | Desktop mulai 1001px; mobile ≤1000px; padding compact 1001–1140px; CTA navbar disembunyikan ≤480px. |
| Split umum | Menjadi satu kolom ≤960px; sticky dilepas. |
| Hero/papan | Penyesuaian tersendiri sekitar 980/560px. |
| Simulasi | Split satu kolom ≤1100px; bento/nilai/input disesuaikan ≤560px. |
| Feature strip | Satu kolom ≤760px. |
| Sidebar layanan | ≤960px menjadi subnav horizontal scroll; kartu head stack ≤560px. |
| Tabel | Wrapper scroll/fluid rules; padding mengecil ≤400px. |
| Jam/status | Countdown pindah baris ≤640px. |
| Keliling | Hari/stops stack ≤720px. |
| Berita | Dua kolom sekitar ≤900px, satu kolom ≤600px. |
| Galeri | Dua kolom ≤900px; satu kolom ≤520px, bento span dilepas. |
| Form kontak | Grid stack ≤960px; form-row stack ≤560px. |
| Login | Split menjadi stack ≤900px. |
| Footer | Penyesuaian ≤900px, kolom tunggal ≤520px. |
| Dashboard | Hook mobile threshold 768px (`useIsMobile`, mobile <768); utility responsive terpisah; sidebar sheet mobile, grid/table dense desktop. |

Source: `base.css:64–78`, media queries `components.css`/`pages.css`, dashboard `hooks/use-mobile.ts` dan sidebar. Breakpoint banyak karena kebutuhan komponen berbeda, bukan satu mobile threshold seluruh aplikasi.

### 13.3 Temuan UX/accessibility yang perlu ditindaklanjuti

| ID / prioritas | Temuan/batas source | Rekomendasi |
|---|---|---|
| UX-01 / P1 | Demo ticket/status/publikasi dapat terlihat operasional; lihat TRUST/DATA di §14. | Ubah copy/state secara jujur dan pisahkan preview dengan operasional. |
| A11Y-01 / P1 | Klaim "tanpa JavaScript semua konten terbaca" tidak berlaku penuh: submenu desktop dibuka JS; panel tab lain hidden; inline head mengganti no-js sebelum external main berhasil. `main.js:75–150`, `base.css:137–139`, head HTML. | Progressive enhancement yang tidak menyembunyikan informasi/tugas ketika script gagal; fallback nav/panel dan reveal-safe default. |
| A11Y-02 / P2 | Portal reduced-motion ada, dashboard memiliki animate-pulse/spin/transitions tanpa kebijakan global ekuivalen di `src/index.css`. | Tambahkan/check `motion-reduce` dan preference handling pada loading/sidebar/chart/editor; uji computed behavior. |
| A11Y-03 / P2 | `body { overflow-x:hidden }` dapat menutupi overflow, bukan membuktikan layout bebas terpotong. `base.css:17`. | Uji zoom, teks panjang, 320px, tabel, angka besar dan font tiap OS dengan scrollWidth dan visual checks. |
| A11Y-04 / P2 | Target klik komponen kecil/dense dan border input/ikon perlu evaluasi kontras non-text serta target size; token teks saja tidak cukup. | Uji WCAG target 24px/spacing dan tujuan produk touch nyaman; focus visible di semua surface. |
| A11Y-05 / P2 | Mobile menu bukan modal focus-trap; dibuka via hidden/aria-expanded dan Escape. Ini tidak otomatis salah untuk disclosure, tetapi perlu memastikan fokus/navigasi tetap logis. | Tentukan disclosure vs modal, uji tab order/focus return dan close saat navigasi/resize. |
| A11Y-06 / P2 | Charts, dynamic countdown/toast, gallery dialog dan filter/table perlu uji screen reader/keyboard penuh. | Text alternatives, live announcement tidak berlebihan, labelling tab/panel, focus restore, accessible empty/error. |
| UI-01 / P2 | Token/icon/layout dua frontend bisa drift; headline gray tidak boleh turun menjadi body kecil. | Semantic mapping, visual regression, audit kontras pasangan actual states. |
| UI-02 / P3 | Showcase/source/rancangan dapat berbeda setelah edit; screenshot historis tidak selalu terbaru. | Update styleguide bersama token, catat intentional deviations, snapshot ulang saat perubahan UI. |

### 13.4 Temuan UI fungsional tambahan dari pemeriksaan source

| ID / prioritas | Bukti aktual / tingkat kepastian | Tindakan berikutnya |
|---|---|---|
| UI-ERR-01 / P1 | Generic DataTable menafsirkan `data ?? []`; beberapa halaman hanya mengambil data/isLoading tanpa error state. `components/data/data-table.tsx:214`, `pages/absensi.tsx:416–418`, `queries.ts:41–55`. **Risiko source**, belum uji network runtime. | Pisahkan empty dari query failed/permission/offline; retry dan pesan yang dapat ditindaklanjuti. |
| FORM-01 / P2 | FieldTanggal memiliki kontrol clear Button tanpa `type="button"`, primitive Button tidak memberi default type. `components/form/fields.tsx:326–330`, `ui/button.tsx:40–59`. Trigger kalender juga tidak menuliskan type, tetapi behavior Radix composition perlu diverifikasi. | Konfirmasi accidental submit di form; tetapkan type explicit untuk non-submit terutama clear; regression test tanggal. |
| MEDIA-01 / P2 | Upload foto absensi memakai state lokal; error lewat toast; sheet/save state terkait mutasi entri. `absensi.tsx:227–294,678–686`. | Uji save ketika pemrosesan foto belum selesai, foto lama/baru, cancel, quota, error dan retry dekat field. |
| A11Y-07 / P2 | Error foto terhubung ke file input tersembunyi, bukan tombol kamera yang digunakan. `absensi.tsx:271–294`. | Error/help accessible pada tombol operasional dan fokus invalid-submit menuju tindakan foto. |
| A11Y-08 / P2 | Checkbox row bernama umum "Pilih baris"; sorting generic perlu `aria-sort`. `data-table.tsx:262–268,433–440,572–586`. | Nama yang menyebut identitas record dan sorting state; pertahankan DnD keyboard/touch yang sudah ada. |
| A11Y-09 / P2 | Radio simulator memiliki arrow updates tetapi belum pola roving tabIndex yang lengkap. `main.js:420,464–475`. | Radio native atau complete radiogroup, uji tab/arrow/selection. |
| A11Y-10 / P2 | Chart role img + focusable bar berpotensi tidak mengumumkan descendants seperti yang diharapkan. `index.html` bagian chart, `main.js:342–348`. | Ringkasan/table accessible dan uji screen reader nilai/filter. |
| BRAND-01 / P3 | Panduan melarang animasi lambang, loader `router.tsx:21` memakai animate-pulse pada lambang. | Animasi indikator terpisah dari lambang. |

### 13.5 Kontras sampel yang dihitung

Perhitungan sRGB pasangan warna, bukan scan DOM. AA teks normal memerlukan4,5:1; teks besar3:1. Border kontrol yang menjadi satu-satunya indikator perlu evaluasi non-text3:1; border dekoratif tidak otomatis wajib sama.

| Pasangan | Rasio kira-kira | Penilaian |
|---|---:|---|
| Ink / paper | 17,22:1 | Lolos teks normal. |
| Text-2 / paper | 5,05:1 | Lolos. |
| Text-2 / paper-2 | 4,66:1 | Lolos, margin lebih kecil. |
| Gray / paper; gray / paper-2 | 3,35:1; 3,09:1 | Hanya sesuai untuk headline besar. |
| Accent / paper; accent / paper-2 | 4,60:1; 4,25:1 | Pada paper-2 tidak cukup untuk teks normal; cek pemakaian aktual. |
| Accent-ink / accent-soft | 5,15:1 | Lolos. |
| OK/OK-soft; warn/warn-soft; danger/danger-soft | 5,05:1; 5,14:1; 4,65:1 | Lolos. |
| Night-muted/night-2; night-accent/night-2 | 6,75:1; 7,32:1 | Lolos. |
| Placeholder `#9A9EA5` / white | 2,69:1 | Perlu diperbaiki, `components.css:317`; label tetap diperlukan. |
| Dashboard muted text / white; / muted light | 5,97:1; 5,28:1 | Lolos. |
| Dashboard muted dark / card dark | 7,63:1 | Lolos. |
| Line-2 / white; line / white | 1,47:1; 1,27:1 | Evaluasi batas kontrol, bukan deklarasi gagal semua garis. |

Belum menghitung semua alpha overlay/gradient/hover/focus secara komposit. Target tombol dashboard 24–40px tidak otomatis gagal WCAG2.2 AA minimum24px dengan pengecualian; ukuran44px adalah sasaran kenyamanan touch/AAA yang layak untuk mobile.

### 13.6 Pemeriksaan yang belum dibuktikan

Belum menjalankan axe, screen reader, keyboard traversal seluruh route, zoom 400%, contrast semua rendered states, device matrix kamera/GPS, dark-mode charts, atau full browser responsive regression. Tidak ada alasan menyatakan semua modal/form/drag-and-drop accessible hanya karena menggunakan Radix. Foto/alt/caption, tanggal keterkinian, print dan placeholders perlu content accessibility juga.

Screenshot historis `docs/preview/beranda-desktop.jpg`, `beranda-mobile.jpg`, `dashboard.jpg`, `design-system.jpg`, dan `login.jpg` mendukung observasi visual editorial/stacking/density. Ukuran gambar panjang yang tereskalakan tidak layak untuk menilai ketajaman teks mikro atau ukuran kontrol aktual. Seluruh 33 primitive belum diinspeksi mendalam; provenance/lisensi aset/font belum diaudit hukum.

## 14. Audit keamanan, privasi, dan kebenaran informasi

### 14.1 Cara membaca prioritas

**P0** = penghalang pemakaian produksi dengan data/identitas nyata; **P1** = risiko tinggi untuk integritas layanan/data/privasi; **P2** = perbaikan kualitas/operasional; **P3** = penyempurnaan. Tingkat ini berkaitan dengan penggunaan yang dituju, bukan bukti seluruh risiko sudah dieksploitasi. Prototype intentional tetap harus disebut sebagai batas produksi.

| ID / prioritas | Temuan dan bukti | Dampak | Rekomendasi, belum implementasi |
|---|---|---|---|
| SEC-01 / P0 | Kredensial hardcoded dan sesi JSON peramban; `assets/js/main.js:51–56`, handler login, `src/lib/auth.ts`. | Identitas dapat dipalsukan; tidak boleh melindungi informasi internal/PII. Saat ini tidak ada backend pusat yang dapat diklaim telah dibobol oleh sesi lokal. | Auth server, akun individu, verifikasi tiap request, expiry/revocation, hapus demo dari alur operasional. |
| SEC-02 / P0 | Role hanya label; seluruh action workflow tersedia tanpa policy terverifikasi. `nav.ts`, `meta.ts`, `components/alur/`, LocalApi. | Tidak ada separation of duties yang aman; pengguna bisa menyetujui/menerbitkan perubahan sendiri. | RBAC/policy server untuk read/write/transition/export/reset; pelaku diambil dari sesi server. |
| DATA-01 / P1 | CMS IndexedDB terpisah dari markup/CONFIG portal. `src/lib/api/index.ts`, `local.ts`, `assets/js/main.js`. | Dashboard mengesankan publikasi selesai, tetapi masyarakat tetap membaca konten lama. | Pilih API publik atau generator/build publikasi, serta versioning/approval/cache invalidation. |
| TRUST-01 / P1 | Form `data-demo-form` hanya membuat tiket/toast tanpa kirim. `main.js` handler demo, `kontak.html`. | Pelapor dapat mengira keluhan diterima padahal hilang. Lebih serius untuk layanan nyata daripada placeholder visual. | Label demo jelas/nonaktifkan submit operasional, atau backend tiket valid/tautan kanal resmi. |
| TRUST-02 / P1 | Antrean/statistik/jadwal/amnesti/dates masih contoh. `CONFIG`, HTML portal, `seed.ts`, `proto-note`. | Informasi salah bisa memengaruhi kedatangan/pembayaran dan kepercayaan publik. | Verifikasi semua sumber; label/status resmi; hindari generated future schedule tanpa persetujuan. |
| SEC-03 / P1 bersyarat | Model berita/halaman menerima HTML rich text; impor/URL juga perlu boundary validasi. `types.ts:78–93,219–225`, editor dan `http.ts`. | Risiko stored XSS **jika** kelak dirender tanpa sanitasi. Audit tidak membuktikan eksploit stored XSS pada portal statis sekarang. | Allowlist sanitasi HTML/URL server dan renderer; jangan mengandalkan editor sebagai sanitizer keamanan. |
| PRIV-01 / P1 | Foto, GPS, NIP, pesan/kontak, struktur dan snapshot data. `types.ts:179–217,261–285,467–473`, absensi/backup. | PII dapat tertinggal di perangkat bersama atau backup; data lokal bukan otomatis aman. | Data minimization, retention/penghapusan, akses privat, storage terenkripsi sesuai kebutuhan, kebijakan ekspor dan persetujuan. |
| DATA-02 / P1 | Data terikat origin/browser; fallback memori dan reset/impor overwrite. `local.ts`, settings. | Kehilangan data saat browser dihapus, origin berubah, reload fallback, atau backup salah. | Backup tervalidasi, warning/mode indikator, transaksi/restore test; DB pusat untuk operasi nyata. |
| DATA-03 / P1 | `tulisSnapshot` melakukan `clear()` lalu `setMany()` terpisah; validasi snapshot ringan. `local.ts:130–147,321–340`. | Kegagalan tulis setelah clear berpotensi menghapus database lama; pengecekan object/array belum memvalidasi setiap field/relasi. | Atomic replacement/staging+rollback, schema validation penuh, backup sebelum reset/impor, uji quota/failure; jangan sebut restore aman hanya karena JSON lolos parse. |
| SEC-04 / P2 | Log hanya 300 entri dan dapat dihapus; timestamp/pelaku lokal. `local.ts`, `/aktivitas`, `/reset`. | Tidak memenuhi audit trail kedinasan yang tidak dapat diubah. | Audit server append-only/policy retention; batasi clear/reset/impor. |
| CONTENT-01 / P2 | Tombol unduh `href="#"`, kontak/pejabat placeholder. `unduhan.html`, partial footer/profil/struktur. | Tugas masyarakat tidak terselesaikan. | File resmi/link sah; state belum tersedia yang eksplisit; jangan mengganti dengan PDF rekaan. |
| SEC-05 / P2 | Header hosting belum mencakup kebijakan app-specific lengkap; external links/files. | Defense-in-depth belum diaudit komprehensif. | Rancang CSP/frame policy/referrer dengan allowlist nyata, uji inline styles/SVG/maps; jangan menambahkan CSP yang mematahkan UI. |

### 14.2 Mitigasi yang sudah ada dan batasnya

- Login redirect `lanjut` dibatasi menuju `dashboard/` dan hash path; mitigasi open redirect, bukan autentikasi.
- Browser form validation dan Zod/form helpers meningkatkan kualitas input; server tetap wajib memvalidasi.
- Import snapshot memiliki pengecekan struktur/versi, bukan jaminan seluruh string HTML/URL aman atau seluruh constraint bisnis terpenuhi.
- HTTPS domain aktif dan `nosniff`/referrer header teramati; HTTPS tidak menggantikan auth/RBAC.
- Staging output tidak mengunggah seluruh source repo. Repository GitHub tetap publik; konten yang masuk repo tidak dapat dianggap privat.

### 14.3 Privasi dan kebijakan yang perlu ditetapkan

Absensi membutuhkan akses kamera/foto/lokasi dan secure context; pengguna dapat menolak izin, GPS bisa tidak akurat/manipulated, dan foto upload bukan jaminan foto diambil saat itu. Jangan menyebutnya sistem anti-fraud/geofence tanpa implementasi. Waktu server, kebijakan akurasi/lokasi, akses foto, retention, serta persetujuan pegawai perlu dirancang.

Pengaduan memerlukan data minimum, informasi pemrosesan, kanal respons, SLA, hak akses dan penghapusan. Pernyataan kerahasiaan pada UI/policy harus sesuai cara server/vendor benar-benar memprosesnya. Peta/tautan eksternal berpotensi melibatkan pihak ketiga; evaluasi apakah embed atau outbound link dan implikasi privacy sebelum rilis.

### 14.4 Batas klaim keamanan

Tidak dilakukan penetration test, eksploitasi XSS, audit TLS mendalam, scan CVE dependency, uji malware upload, atau analisis kepatuhan hukum. Risiko rich text/upload/API tertentu adalah kebutuhan hardening fase integrasi, bukan temuan backend vulnerable yang telah berjalan. **Review keseluruhan: perubahan diperlukan sebelum penggunaan produksi.**

## 15. Audit kualitas teknis, performa, SEO, dan pengujian

### 15.1 Hasil pemeriksaan source dan aset

| Pemeriksaan | Hasil / batas |
|---|---|
| HTML root | 17 file; seluruhnya `lang="id"` dan title. Description 16, tidak ada pada redirect shim. |
| Referensi lokal | Pemeriksaan static href/src tidak menemukan broken file reference. |
| Anchor/ID | Tidak ditemukan target anchor hilang atau duplicate ID pada 17 HTML. |
| CSS/module assets | Audit deployment sebelumnya menemukan CSS url dan imports chunk lengkap; smoke live saat ini memeriksa referensi shell dashboard. |
| Paket publish working tree | 112 file, 3.988.097 byte (sekitar 3,80 MiB); ukuran dapat berbeda dari deployment awal karena `index.html` lokal berubah. |
| Ukuran per bagian | HTML 546.755 byte; assets 1.210.446 byte; dashboard 2.230.896 byte. |
| Lockfile | v3; inventaris audit 296 entri paket dan hash integritas dependency yang teramati. Ini bukan scan kerentanan. |
| Test runner | Tidak ditemukan unit/integration/E2E tests atau script `test`, Vitest/Jest/Playwright/Cypress dalam project. |
| CI repository | Tidak ditemukan `.github/workflows` atau pipeline quality checks. Pages Git build ada secara hosting, bukan suite test CI. |
| Dependencies lokal | `node_modules` tidak tersedia; tsc/oxlint/build tidak dieksekusi. Tidak memasang dependency atau mengubah bundle demi audit dokumentasi. |
| Live HTTP | 21 resource/route checks berhasil sesuai status yang diharapkan; lihat §17.2. |

Regex/static link checks tidak membuktikan HTML valid sepenuhnya, render benar, eksternal link sehat, placeholder `href="#"` berfungsi sebagai download, ataupun semua filter/form lolos. Nomor file/jumlah byte adalah snapshot, bukan batas ukuran tetap.

### 15.2 Temuan operasional dan maintainability

| ID / prioritas | Temuan | Tindak lanjut |
|---|---|---|
| QA-01 / P1 | Belum ada regression tests untuk kalkulator, tanggal/jadwal, auth, workflow, impor, dan integrasi. | Unit untuk logic murni; integration API/persistence; browser E2E public/dashboard; aksesibilitas. |
| BUILD-01 / P1 | Pages hanya menyalin `dashboard/` committed, bukan membangun source. | Disiplin build+review artefak atau pipeline otomatis yang jelas; jangan beranggapan edit TSX langsung online. |
| HYGIENE-01 / P2 | `.gitignore` hanya node_modules/log/.DS_Store/Thumbs.db. | Abaikan local env/secrets, `_site/`, `.wrangler/`, cache/build metadata yang memang bukan artefak committed; jangan mengabaikan `dashboard/` tanpa mengganti strategi. |
| SYNC-01 / P2 | Sync partial menulis markup massal dan formatting beranda lokal dapat berubah besar. | Tambah dry-run/check/format convention bila diperlukan; selalu review diff dan lindungi perubahan user. |
| MAINT-01 / P2 | Konten/jam/tarif/token diduplikasi di HTML/CONFIG/seed/dashboard. | Tentukan source of truth; contract tests atau generator; semantic token mapping. |
| CI-01 / P2 | Tidak ada quality gate repository sebelum publish. | PR lint/typecheck/tests dan validasi build; deployment smoke dan rollback. |

Dry-run analisis partial memperkirakan 16/17 HTML berubah saat sync, termasuk perubahan format blok `index.html`. **Koreksi penting:** regex `sync-partials.mjs:33–34` memerlukan marker pembuka dan penutup; marker penutup yang hilang biasanya membuat blok tidak cocok dan tidak tersinkron, bukan otomatis menghapus seluruh sisa file. Skrip tidak melaporkan marker yang hilang/ambigu. Tindak lanjut yang tepat adalah validasi marker dan fail-fast, bukan klaim penghapusan yang tidak terbukti.

### 15.3 SEO/discovery

Source tidak memiliki canonical links, Open Graph/Twitter cards, JSON-LD, `robots.txt`, atau `sitemap.xml`. Ini gap optimasi/discovery, **bukan penghalang HTML dapat diakses**. Domain `pages.dev` dan domain utama sama-sama dapat diakses; canonical/redirect domain utama perlu dipertimbangkan agar URL publik konsisten.

Prioritas: title/deskripsi per konten yang riil, canonical clean URLs, sitemap hanya halaman publik, metadata artikel, OG image berizin, serta structured data instansi/artikel yang faktual. Login/dashboard/design guide perlu keputusan indexability; `robots.txt`/noindex bukan kontrol akses keamanan. Build command harus menyalin file SEO baru yang ditambahkan.

### 15.4 Performa

Positif: portal tanpa framework runtime, font sistem, aset lokal kecil, dashboard lazy chunks, gambar logo WebP dan kompresi upload. Risiko: sprite/partial inline berulang di seluruh HTML, semua public CSS/JS untuk banyak fitur, base64 media lokal, table/query collection tanpa server pagination, rich editor/chart dependencies pada dashboard, dan source/build drift.

Tidak diukur LCP/INP/CLS, Lighthouse, transfer compression per resource, memory profiling, atau jumlah request browser penuh. Angka ukuran file adalah raw bytes, bukan ukuran transfer Brotli/gzip. Uji perangkat mobile riil serta kondisi lambat sebelum menyatakan performa baik.

### 15.5 Pengujian berikutnya dan koreksi routing

Dashboard memakai `createHashRouter`: deep link benar seperti `/dashboard/#/berita`, bukan `/dashboard/berita`. Hash tidak dikirim ke server. **Tidak perlu menambahkan wildcard SPA rewrite hanya untuk refresh route hash.** Root custom 404 tetap dipertahankan. Usulan history routing/fallback baru hanya relevan bila router memang dimigrasi secara sadar.

Quality checks yang tersedia setelah instalasi: `npm --prefix dashboard-app run typecheck`, `run lint`, `run build`. Belum dijalankan pada audit ini. Pemeriksaan baru minimum: isolated build; public/dashboard smoke browser; local store refused/quota; workflow; form/error; media permissions; print; mobile/keyboard; real API auth dan RBAC setelah backend tersedia.

## 16. Menjalankan, mengembangkan, dan membangun project

### 16.1 Portal statis

Tidak ada kebutuhan build untuk portal publik. Jalankan dari root repository:

```bash
npx http-server .
```

Buka `http://localhost:8080`. Dashboard memerlukan server HTTP karena memakai JavaScript modules; jangan menganggap membuka file langsung melalui `file://` cukup untuk semua fitur.

### 16.2 Header, footer, dan ikon publik

Sumber bersama berada pada `partials/header.html`, `partials/footer.html`, dan `partials/icons.html`. Penanda `<!-- @header -->` / `<!-- /@header -->`, serta penanda footer/icons, menjadi target sinkronisasi. Atribut `data-page` dan `data-group` pada `<body>` menentukan state navigasi.

```bash
node scripts/sync-partials.mjs
```

**Perhatian:** perintah ini menulis ulang blok partial pada seluruh HTML root. Perubahan manual pada header/footer/ikon hasil salinan bisa hilang. Review `git diff` sebelum dan sesudah menjalankannya; lindungi perubahan lokal `index.html`. Skrip tidak dijalankan selama audit ini.

### 16.3 Dashboard

Jalankan dari root melalui `--prefix`, atau gunakan working directory `dashboard-app/`:

```bash
npm --prefix dashboard-app ci
npm --prefix dashboard-app run dev
npm --prefix dashboard-app run typecheck
npm --prefix dashboard-app run lint
npm --prefix dashboard-app run build
npm --prefix dashboard-app run preview
```

| Script | Implementasi aktual |
|---|---|
| `dev` | `vite`; plugin menyajikan portal statis juga. |
| `typecheck` | `tsc -b`; dapat menghasilkan metadata build TypeScript. |
| `lint` | `oxlint src`. |
| `build` | `tsc -b && vite build`. |
| `preview` | `vite preview`. |

`vite.config.ts` memakai base `/dashboard/` saat serve dan `./` saat build; output diarahkan ke sibling `dashboard/`, dengan `emptyOutDir: true`. **Build mengganti artefak committed `dashboard/`.** Review perubahan bundle dan jangan menjalankannya pada working tree yang harus dipertahankan tanpa persiapan.

Development proxy/plugin adalah fasilitas lokal, bukan backend/API produksi. Versi Node harus sesuai engine dependency lockfile, bukan sekadar mengikuti contoh versi lama dari dokumentasi. Saat audit lingkungan memakai Node `v24.21.0` dan npm `11.6.4`; itu bukan penetapan wajib untuk seluruh developer.

## 17. Deployment Cloudflare Pages dan status domain

### 17.1 Konfigurasi yang diketahui

Konfigurasi berikut berasal dari deployment sebelumnya pada 11 Oktober 2026; bukan config file Infrastructure as Code yang tersimpan di repository.

| Pengaturan | Nilai |
|---|---|
| Produk | Cloudflare Pages, integrasi GitHub; bukan Direct Upload dan bukan Workers application. |
| Nama project | `pintu` |
| Repository | `ElwinMusadi/portal-uptdkotakupang` |
| Production branch | `main` |
| Framework preset | `None` |
| Root directory | Root repository / kosong. |
| Build output directory | `_site` |
| URL Pages | `https://pintu-4gi.pages.dev` |
| Domain utama | `https://pintu.uptdpenda-kupang.web.id` |
| Commit deployment awal yang tercatat | `1726f7c9e0a4c1a855a338ef88793ddedffca515` |
| Deploy produksi otomatis | Aktif untuk push `main` pada konfigurasi deployment awal. |
| Preview branch otomatis | Dinonaktifkan pada konfigurasi awal. |

Build command:

```bash
mkdir -p _site && cp *.html _site/ && cp -R assets dashboard _site/
```

Command ini berjalan pada lingkungan Linux Pages. Command **mengemas artefak yang sudah ada**, tidak melakukan build ulang dashboard. Bila source `dashboard-app/` berubah, build dan sertakan hasil `dashboard/` sebelum push, atau ubah pipeline secara sadar agar membangun dashboard di CI.

Hanya `*.html` root, `assets/`, dan `dashboard/` yang diterbitkan. `docs/`, `partials/`, `scripts/`, `dashboard-app/`, dan dokumen Markdown ini tidak ikut disajikan. Jika nanti menambah PDF/berkas unduhan dalam folder baru, build command harus ikut diperbarui.

### 17.2 Status live yang benar-benar diperiksa saat audit

Pada 11 Oktober 2026 sekitar 11.30 UTC:

- `https://pintu.uptdpenda-kupang.web.id/` merespons **HTTPS 200**.
- DNS A melalui resolver `1.1.1.1` mengembalikan IP Cloudflare; subdomain tidak lagi NXDOMAIN seperti saat deployment awal.
- URL `https://pintu-4gi.pages.dev/` juga merespons 200.
- **21 pemeriksaan respons HTTP/aset, 0 gagal:** beranda; profil; layanan; kontak; login; dashboard; trailing slash dashboard; JS/CSS publik; delapan referensi aset shell dashboard; empat URL yang memang harus 404.
- `.html` dialihkan ke URL tanpa ekstensi, misalnya `/profil.html` menjadi `/profil` lalu 200.
- `/dashboard` menjadi `/dashboard/`; relative module paths tetap benar.
- `/dashboard-app/package.json`, `/README.md`, dan `/partials/header.html` merespons 404, bukan membocorkan file sumber dari output deployment.
- Header yang diamati: `Cache-Control: public, max-age=0, must-revalidate`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`.

**Batas:** pembacaan ulang status custom-domain lewat API Cloudflare menghasilkan `401 Authentication error`; audit tidak memperbarui/memperluas izin akun. Oleh karena itu, kesiapan domain dinyatakan dari bukti DNS/HTTPS publik, bukan dari status internal API atau commit deployment terbaru. Jangan menyatakan identitas commit live sekarang hanya dari baseline lokal.

### 17.3 Routing dan file konfigurasi

Pages menyediakan clean URLs dan directory index secara bawaan. Root `404.html` harus dipertahankan agar rute tidak dikenal tidak menjadi SPA fallback ke beranda. Dashboard menggunakan hash routing, sehingga tidak membutuhkan wildcard `/* /index.html 200`.

Build command saat ini tidak menyalin `_headers`, `_redirects`, `robots.txt`, atau `sitemap.xml` jika nantinya ditambahkan ke root. Menambahkan berkas tersebut tanpa mengubah pipeline tidak cukup untuk menerbitkannya.

## 18. Tata kelola konten dan checklist data resmi

### 18.1 Peran bisnis yang dirancang

1. **Penyedia:** menyiapkan data/bahan dari unit pemilik.
2. **Pemeriksa:** mencocokkan data dengan sumber resmi.
3. **Penyetuju:** memberikan izin terbit.
4. **Pengelola publikasi:** menerbitkan, mencatat metadata, dan memantau pembaruan.

Pembagian ini berasal dari rancangan tata kelola. Ia tidak membuktikan tersedianya empat identitas pengguna atau enforcement RBAC di server.

### 18.2 Frekuensi pemutakhiran yang diusulkan

| Konten | Pemilik yang diusulkan | Frekuensi yang diusulkan |
|---|---|---|
| Samsat Keliling | Seksi Penagihan | Mingguan, setiap Jumat. |
| Jam pelayanan | Sub Bagian TU | Bulanan; perubahan paling lambat H-3. |
| Pengumuman | Unit terkait | Saat ada kebijakan. |
| Berita | Pengelola publikasi | Minimal dua per bulan. |
| Dokumentasi | Unit pelaksana | Setiap kegiatan, maksimal tiga hari kerja. |
| Persyaratan layanan | Seksi Pendataan | Ketika regulasi berubah. |
| Profil/visi/struktur | Sub Bagian TU | Ketika pejabat/regulasi berubah. |
| Regulasi/unduhan | Sub Bagian TU | Ketika regulasi baru tersedia. |

Nama unit, frekuensi, dan pemilik perlu disahkan instansi. Sumber: `docs/RANCANGAN-UIUX.md:231–253`.

### 18.3 Checklist sebelum disebut portal resmi operasional

- [ ] Alamat, telepon, WhatsApp, surel, media sosial, dan koordinat kantor valid.
- [ ] Jam pelayanan, cutoff penerimaan berkas, hari libur, dan perubahan jadwal valid.
- [ ] Lokasi/tanggal/jam Samsat Keliling valid; tidak otomatis menerbitkan rotasi contoh sebagai jadwal nyata.
- [ ] Nama pejabat, nama seksi, tugas, dasar hukum, foto, dan maklumat disahkan.
- [ ] Persyaratan lima layanan, jumlah fotokopi, meterai, nomor loket, dan ketentuan biaya diverifikasi.
- [ ] Parameter PKB, opsen, denda, SWDKLLJ, serta program pemutihan/amnesti diverifikasi petugas berwenang.
- [ ] QRIS, Bank NTT, SIGNAL, dan kanal pembayaran yang disebut memang berlaku.
- [ ] Statistik, antrean, waktu tunggu, grafik, dan kepuasan berasal dari data resmi.
- [ ] Berita/pengumuman bukan contoh; tanggal, sumber, dan pemeriksa dapat ditelusuri.
- [ ] PDF regulasi/SOP/formulir benar-benar ada, sah, dan dapat diunduh.
- [ ] Foto/video publik memiliki izin serta tidak menampilkan data wajib pajak tanpa dasar yang sah.
- [ ] Formulir pengaduan benar-benar diterima server, memiliki SLA, penanggung jawab, dan kebijakan privasi yang sesuai.
- [ ] Kebijakan privasi dan syarat-ketentuan ditinjau hukum; isi tidak bertentangan dengan implementasi.
- [ ] Data internal/PII tidak masuk HTML, build output, seed publik, atau repository terbuka.

## 19. Roadmap, prioritas, dan kriteria penerimaan

### 19.1 Urutan pengembangan yang disarankan

Roadmap berikut adalah **rekomendasi**, bukan pekerjaan yang sudah dikerjakan atau otorisasi untuk mengubah project.

| Tahap | Hasil yang dicari | Kriteria penerimaan |
|---|---|---|
| 0 — Kebenaran prototipe | Pengunjung tidak tertipu oleh data contoh dan simulasi laporan. | Label demo terlihat; tidak ada klaim laporan tersimpan/ditindaklanjuti bila belum ada backend; konten resmi diverifikasi. |
| 1 — Arsitektur pusat | Putuskan API atau publishing build statis, hosting, DB, file storage, public/private data. | Kontrak request/response/error, skema/migrasi, hak akses, workflow, backup/restore, dan deployment disepakati. |
| 2 — Identitas & keamanan | Login server, akun individu, kebijakan peran, session expiry/revocation. | Sesi palsu ditolak server; setiap operasi mutasi dan akses PII diautorisasi; auth secret tidak masuk bundle. |
| 3 — CMS operasional | CRUD tervalidasi, workflow enforceable, asset storage, log audit server. | Transisi ilegal/role salah ditolak; timestamp dan pelaku berasal server; concurrency/versioning mencegah lost update. |
| 4 — Publikasi portal | Portal memakai konten resmi CMS tanpa membocorkan draft/PII. | Perubahan yang disetujui terbit sesuai strategi; semua halaman terkait konsisten; withdrawal/cache invalidation teruji. |
| 5 — Pengaduan operasional | Form publik, tiket, penanganan, notifikasi bila diperlukan, anti-abuse. | Tiket ada di server; dedup/idempotency; status privacy-safe; SLA nyata; validasi dan rate limiting. |
| 6 — Alat bantu internal | Absensi foto/GPS dan parameter pajak tervalidasi. | Izin perangkat/error handling; timestamp server; retention; aturan pajak disahkan; uji batas dan amnesti. |
| 7 — Hardening rilis | Test automation, accessibility, SEO, performance, monitoring. | Quality gates CI; backup restore rehearsal; smoke production; observability dan rollback terdokumentasi. |

### 19.2 Uji penerimaan lintaslapisan yang wajib ada

- Login benar/salah, logout, sesi habis, refresh/revocation, tab baru, perangkat bersama, dan penyimpanan ditolak.
- Setiap peran hanya menjalankan tindakan yang diizinkan; permintaan manual langsung ke API tidak melewati policy.
- Draf/dikembalikan/arsip tidak masuk response publik; konten terbit memiliki source dan metadata benar.
- Edit jadwal/tarif/FAQ/kontak/berita terlihat pada portal sesuai strategi publikasi, bukan hanya tabel CMS.
- Tanggal WITA, Jumat/Sabtu/Minggu, libur, sebelum buka/setelah tutup, pergantian minggu/tahun.
- Kalkulator: nol, input invalid, angka sangat besar, batas bulan, motor/mobil, pemutihan; hasil konsisten publik/internal.
- Form pengaduan: sukses server nyata, error/offline, retry/double submit, spam, input malicious, consent/retention.
- Upload: tipe/ukuran invalid, gagal upload, sanitasi nama/URL, akses privat foto/GPS, kuota.
- Ekspor/impor/reset: file rusak/versi berbeda, backup sebelum overwrite, tidak kehilangan data setengah jalan.
- Keyboard penuh, screen reader, zoom 200–400%, reduced motion, viewport 320–1440px, dark mode, tabel dan dialog.
- Deep link/clean URL, 404 bertingkat, asset chunk lama setelah deploy, cache invalidation dan rollback.
- Pemeriksaan tidak ada secret/PII/draft pada HTML/JS/JSON publik, sitemap, build output, dan backup yang terunggah.

## 20. Panduan handover kepada ChatGPT dan peta source

### 20.1 Prompt siap pakai untuk ChatGPT

```text
Saya melanjutkan project PINTU untuk UPTD Pendapatan Daerah Wilayah Kota
Kupang / Samsat Kota Kupang. Baca HANDOVER-CHATGPT.md yang saya unggah
sebagai konteks awal, lalu cocokkan dengan source repository terbaru.

Project memiliki portal HTML/CSS/JavaScript statis dan dashboard React/
TypeScript/Tailwind/shadcn-style. Dashboard default memakai IndexedDB;
portal publik belum membaca hasil pengeditan dashboard. Login masih demo
client-side; tidak ada backend atau RBAC server. Jangan menyebutnya sistem
produksi yang aman atau menganggap fitur placeholder sudah operasional.

Pertahankan Bahasa Indonesia, identitas/lambang NTT, design system portal,
struktur menu, data-* hooks, dan kontrak ApiClient kecuali perubahan
disepakati. Jangan edit artefak dashboard/assets sebagai source fitur.
Jangan menimpa perubahan lokal index.html. Jangan commit/push/deploy atau
memperluas cakupan sebelum diminta. Jangan meminta secret ditempel di chat.

Sebelum implementasi, jelaskan batas masalah, verifikasi source terkait,
tentukan kontrak data/auth/publikasi bila lintaslapisan, lalu kerjakan
perubahan terkecil dan uji. Pisahkan hasil aktual, asumsi, usulan, dan gap.

Tugas lanjutan saya: [isi fitur/perbaikan yang diminta secara spesifik].
```

### 20.2 Informasi yang harus tersedia sebelum backend dikerjakan

- Infrastruktur backend yang disetujui, DB dan object/file storage, biaya/operasional, dan penanggung jawab.
- Identitas pegawai (akun lokal, SSO, atau penyedia identitas); daftar peran dan policy transisi yang disahkan.
- Strategi portal: API langsung atau hasil build publikasi; kebutuhan SEO, latency, dan frekuensi pembaruan.
- Informasi resmi kontak/pajak/jadwal/pejabat/regulasi, bukan menebak dari seed.
- Kebijakan laporan/absensi: data minimum, dasar pemrosesan, retention, akses, lokasi storage, dan ekspor.
- Backup, monitoring, rollback dan kriteria rilis. Domain online tidak menggantikan keputusan ini.

### 20.3 Peta source untuk orientasi cepat

Path `src/...` pada tabel berikut relatif terhadap `dashboard-app/`. Nama tanpa folder setelah path lengkap mengacu ke folder yang sama, kecuali komponen theme provider yang berada di `dashboard-app/src/components/theme-provider.tsx`.

| Kebutuhan | Berkas pertama yang dibaca |
|---|---|
| Tujuan/sitemap/tata kelola | `README.md`, `docs/RANCANGAN-UIUX.md`. |
| Navigasi publik | `partials/header.html`, `partials/footer.html`. |
| Hook/fungsi publik & login demo | `assets/js/main.js`, HTML halaman terkait. |
| Token/layout/komponen publik | `assets/css/tokens.css`, `base.css`, `components.css`, `pages.css`, `design-system.html`. |
| Sinkronisasi shared markup | `scripts/sync-partials.mjs`. |
| Bootstrap dashboard | `dashboard-app/src/main.tsx`, `src/app/layout.tsx`. |
| Route dan menu dashboard | `src/app/router.tsx`, `src/app/nav.ts`, `src/components/app-sidebar.tsx`. |
| Entity/kontrak API | `src/lib/api/types.ts`, `client.ts`, `index.ts`, `http.ts`. |
| Local persistence/seed/backup | `src/lib/api/local.ts`, `seed.ts`. |
| Sesi dan akun demo | `assets/js/main.js`, `src/lib/auth.ts`. |
| Publikasi | `src/lib/meta.ts`, `src/lib/alur.ts`, `src/components/alur/alur.tsx`, `src/pages/alur.tsx`. |
| Form/validasi/upload | `src/components/form/`, `src/lib/formulir.ts`, `validasi.ts`, `gambar.ts`. |
| Kalkulator/absensi | `src/pages/kalkulator.tsx`, `absensi.tsx`, `src/lib/pajak.ts`, `hitung.ts`. |
| Theme UI dashboard | `src/index.css`, `src/components/ui/`, `theme-provider.tsx`. |
| Build dan dependency | `dashboard-app/package.json`, `package-lock.json`, `vite.config.ts`, `tsconfig*.json`. |
| Artefak hosting | `dashboard/index.html`, `dashboard/assets/`; lihat source sebelum mengganti. |

### 20.4 Larangan asumsi bagi penerus

1. Jangan menyamakan simpan lokal dengan publikasi portal atau database pusat.
2. Jangan menyamakan label role/workflow dengan otorisasi yang aman.
3. Jangan menganggap status antrean, statistik, jadwal contoh, dan notifikasi UI berasal dari server.
4. Jangan menganggap seluruh entitas melewati workflow yang sama.
5. Jangan menyimpulkan API server tersedia hanya karena `HttpApi` atau `VITE_API_URL` ada.
6. Jangan mengklaim semua audit lulus hanya karena HTTP 200 atau screenshot terlihat baik.
7. Jangan mengubah typography/logo/warna/menu untuk memulai pengembangan backend.
8. Jangan menerbitkan seluruh repository sebagai output hosting.
9. Jangan menaruh API token, secret, NIP riil, laporan masyarakat, atau foto absensi di repo publik/dokumen chat.
10. Dokumen ini adalah snapshot audit; source/config/domain terbaru harus diverifikasi kembali sebelum keputusan operasional.
