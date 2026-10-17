# Rencana Implementasi — Tema Portal dan Dashboard

**Status:** rencana dengan keputusan awal pemilik (17 Oktober 2026, [D-057](decisions.md#d-057--2026-10-17--tema-portal-dan-dashboard-rencana-dan-keputusan-awal)). **Keempat irisan selesai** pada 17 Oktober 2026: Irisan 1 (fondasi), Irisan 2 (warna, latar, huruf, sudut, tab Tampilan), Irisan 3 (skala, pratinjau di portal, riwayat), dan Irisan 4 (mode gelap portal yang ditetapkan Administrator, warna bilah peramban mengikuti latar). Hasilnya di §5.1–§5.4. **Irisan 5 (ketebalan judul)**, atas permintaan pemilik pada hari yang sama, juga selesai (§5.5). **Irisan 6 (warna gelap tombol dan navigasi)** juga selesai (§5.6). **Irisan 7 (warna panel gelap)** juga selesai (§5.7). **Irisan 8 (warna navbar dan efek glass)** juga selesai (§5.8).
**Sumber:** permintaan pemilik pada 17 Oktober 2026: Administrator dapat mengatur tema (jenis huruf, warna utama, skala tampilan, dan pengaturan tampilan lain yang lazim) dari menu Pengaturan di dashboard, agar tampilan tidak itu-itu saja.

---

## 1. Tujuan dan ruang lingkup

| Kebutuhan | Bentuknya |
|---|---|
| Satu tempat mengatur | Tab baru **Tampilan** di Pengaturan dashboard, khusus Administrator, dengan dua bagian: **Portal** dan **Dashboard**. |
| Tema masing-masing | Portal dan dashboard punya tema sendiri-sendiri (keputusan pemilik). Tema portal berlaku di semua halaman portal, termasuk 404, Login Pegawai, Aktivasi akun, dan layar TV antrean. Tema dashboard berlaku di dashboard dan aplikasi Absensi. |
| Pilihan yang lazim | Warna utama (aksen), jenis huruf teks dan judul, skala tampilan, kelengkungan sudut, dan nuansa latar; ditambah tema siap pakai (preset). |
| Aman dipakai | Warna apa pun yang dipilih tetap terbaca (kontras sesuai WCAG AA); pilihan bisa dikembalikan ke bawaan PINTU kapan saja. |
| Tanpa kedipan | Halaman langsung tampil dengan tema terpilih, bukan tampil dengan tema bawaan lalu berganti. |

Di luar lingkup rencana awal: mode gelap untuk portal publik (kemudian dibangun di Irisan 4 atas keputusan pemilik), mengganti tata letak atau susunan halaman, warna logo (logo tetap biru PINTU, keputusan pemilik), dan tema per pengguna (mode gelap dashboard tetap pilihan masing-masing pegawai seperti sekarang).

---

## 2. Keadaan sekarang

| Bagian | Temuan | Akibat bagi rencana |
|---|---|---|
| Warna portal | Semua warna ada sebagai variabel di `public/assets/css/tokens.css` (hex): `--accent #2B63F6`, `--accent-ink`, `--accent-soft`, latar `--paper*`, tinta `--ink*`, panel gelap `--night*`, status `--ok/--warn/--danger`. | Tema portal cukup menimpa variabel ini. |
| Warna dashboard | Variabel shadcn di `resources/js/dashboard/index.css` (hex): `--primary #0f1319`, `--brand`/`--ring`/`--chart-1`/`--sidebar-ring #2b63f6`, `--chart-2..5`, `--sidebar`, ditambah blok `.dark`. | Tema dashboard menimpa variabel ini, untuk mode terang dan gelap. |
| Warna yang ditulis langsung | ±70 warna hex/rgb ditulis langsung di `components.css`, `pages.css`, dan CSS komponen React portal; sebagian biru turunan aksen (mis. `#B5C8FF`, `#7FA4FF`, palet `--lp-1..5` Laporan Penerimaan, `#23345F` di layar TV), juga 16 di `pages/papan.tsx` dashboard. | Harus dijadikan variabel dulu (Irisan 1); kalau tidak, sebagian elemen tetap biru saat aksen diganti. Putih (`#FFF`) tidak perlu. |
| Huruf | Hanya tumpukan font sistem (SF Pro, Segoe UI, Roboto…), tanpa webfont. Semua `font-family` portal sudah memakai `var(--font-*)`; dashboard menulis tumpukan yang sama langsung di `@theme inline`. Portal dan dashboard sama-sama memakai nama `--font-sans` dan `--font-display`. | Mengganti huruf mudah di portal; dashboard perlu diarahkan ke variabel. Karena namanya sama, tema portal dan dashboard harus disajikan sebagai dua stylesheet terpisah. |
| Satuan ukuran | CSS portal hampir seluruhnya px (±330 `font-size` px, tanpa `rem`). Dashboard memakai `rem` (Tailwind). | Skala dashboard lewat ukuran huruf dasar; skala portal lewat `zoom` (keputusan pemilik). |
| Sudut | `--r-xs..--r-xl` di portal (dipakai di ±40% deklarasi; sisanya px langsung); `--radius` di dashboard. | Kelengkungan diatur lewat pengali, setelah sudut yang ditulis langsung dijadikan variabel. |
| Halaman portal | Blade statis yang dilayani byte demi byte (`PortalPagesTest`), tanpa direktif Blade; CSS lewat empat `<link>` relatif yang sama di semua halaman. Belum ada rute yang menyajikan CSS dinamis. | Tema portal masuk lewat satu `<link>` tetap ke **stylesheet yang dibuat server**, bukan lewat JavaScript. |
| Pengaturan | Pola dokumen pengaturan sudah ada (`settings`, `SaveSetting`, `GET/PUT /api/pengaturan/{nama}`, `SettingPolicy`): Pengaturan Situs dibaca semua pegawai dan hanya disimpan Administrator. | Tema menjadi dokumen pengaturan `tema` dengan pola yang sama. |
| Keamanan | Tidak ada Content-Security-Policy; webfont yang di-host sendiri tidak terhalang. | Huruf disajikan dari situs sendiri, bukan dari Google Fonts. |

---

## 3. Pengaturan yang tersedia (untuk portal dan dashboard masing-masing)

| Pengaturan | Pilihan | Catatan |
|---|---|---|
| Tema siap pakai | **PINTU (bawaan)** dan 5 tema lain, mis. *Samudra* (biru laut), *Hutan* (hijau), *Senja* (jingga bata), *Tenun* (merah marun khas NTT), *Grafit* (abu netral). Masing-masing sudah diuji kontrasnya. | Memilih tema siap pakai mengisi semua pengaturan di bawah; setelah itu tiap pengaturan masih bisa diubah (menjadi "Kustom"). |
| Warna utama (aksen) | Pemilih warna bebas (hex) atau swatch tema. | Server menurunkan warna lain dari warna ini (§4.3); warna yang terlalu terang atau terlalu gelap disesuaikan otomatis agar tetap terbaca. |
| Nuansa latar | *Hangat* (krem seperti sekarang), *Netral* (abu-putih), *Sejuk* (kebiruan). | Portal: `--paper*`, `--line*`, dan sedikit nuansa panel gelap `--night*`. Dashboard: latar, kartu, dan sidebar mode terang. |
| Huruf teks | *Sistem (bawaan)*, Inter, Plus Jakarta Sans, Source Sans 3, IBM Plex Sans, Nunito Sans. DM Sans semula juga huruf teks, lalu dijadikan khusus judul (pemilik, 17 Oktober 2026). | Berlisensi OFL dan disimpan di situs sendiri (`public/assets/fonts/`, keputusan pemilik). Hanya huruf yang dipilih yang diunduh pengunjung. |
| Huruf judul | Sama dengan huruf teks, atau pilihan terpisah dari daftar yang sama ditambah DM Sans, Manrope, dan Lexend. | Dipakai `--font-display` (judul besar, angka); di dashboard juga judul kartu, dialog, dan lembar samping (Irisan 5). |
| Warna gelap | Pemilih warna bebas (hex) dan contoh: *Hitam* (bawaan), *Dari warna utama*, *Biru malam*, *Hijau tua*, *Cokelat kopi*, *Ungu terong*. | Irisan 6. Tombol gelap, pilihan terpilih, navigasi dan menunya, toast, dan kartu gelap; di dashboard tombol utama. Panel gelap dan warna teks tidak berubah. Server menjaga warnanya tetap gelap dan teks di atasnya terbaca. |
| Warna panel gelap (portal) | Pemilih warna bebas dan contoh: *Hitam* (bawaan), *Sama dengan warna gelap*, *Dari warna utama*, dan contoh gelap yang sama. | Irisan 7. Papan layanan, dasbor layanan, kotak cek pajak, media, dan layar TV; kotak di dalam panel sedikit lebih terang. Server menjaga teks putih, teks abu, dan aksen panel tetap terbaca. |
| Navbar (portal) | Warna: *Ikuti warna gelap* (bawaan) atau warna sendiri, termasuk terang. Efek glass: mati (bawaan), *Halus*, *Sedang*, *Kuat*. | Irisan 8. Navbar, menu turunnya, dan menu ponsel; teks, ikon, logo, dan tombol menyesuaikan warna terang atau gelap; tingkat tembus pandang dijaga agar teks terbaca di atas latar apa pun. |
| Ketebalan judul | *Semi-tebal* (600, bawaan), *Tebal* (700), *Ekstra tebal* (800). | Irisan 5. Saat huruf judul diganti, ketebalan diisi saran untuk huruf itu; teks tetap 400. |
| Skala tampilan | 90%, 100% (bawaan), 110%, 120%. | Portal lewat `zoom`, dashboard lewat ukuran huruf dasar (§4.5). |
| Kelengkungan sudut | *Tegas* (×0,5), *Sedang* (×1, bawaan), *Membulat* (×1,35). | Tombol kapsul tetap kapsul. |
| Kembali ke bawaan | Tombol *Kembalikan tampilan bawaan PINTU* per bagian. | Bisa dibatalkan dengan memulihkan tema sebelumnya dari riwayat (§4.6). |

Bagian Dashboard punya tombol *Samakan dengan portal* untuk menyalin pilihan portal.

---

## 4. Rancangan sistem

### 4.1 Data

Dokumen pengaturan `tema` di tabel `settings` (pola yang sama dengan `situs`, `statistik`, `pemetaan`), dengan dua bagian berbentuk sama:

```json
{
  "portal":    { "aksen": "#2B63F6", "latar": "hangat", "hurufTeks": "sistem", "hurufJudul": "sama", "sudut": "sedang", "skala": 100, "tebalJudul": 600, "gelap": "#0F1319", "panel": "#0A0A0C", "navbar": "gelap", "kaca": "mati", "mode": "terang" },
  "dashboard": { "aksen": "#2B63F6", "latar": "hangat", "hurufTeks": "sistem", "hurufJudul": "sama", "sudut": "sedang", "skala": 100, "tebalJudul": 600, "gelap": "#0F1319" }
}
```

Semua nilai dari daftar tertutup kecuali `aksen` (hex `#RRGGBB` yang divalidasi, disimpan dengan huruf kapital). Tidak ada teks bebas yang masuk ke CSS, sehingga tidak ada celah menyisipkan CSS. `hurufJudul` `sama` berarti mengikuti huruf teks. Tema siap pakai tidak disimpan: dashboard mencocokkan pilihan dengan daftarnya ("Kustom" bila tidak cocok). `skala` (90, 100, 110, 120) ditambahkan di Irisan 3; dokumen yang tersimpan sebelumnya dibaca dengan skala 100 (`Setting::theme()` melengkapi kunci yang belum ada dengan nilai bawaan). `mode` (`terang`, `gelap`; Irisan 4) hanya ada di bagian portal, dibaca `terang` untuk dokumen lama. `tebalJudul` (600, 700, 800; Irisan 5) ada di kedua bagian, dibaca 600 untuk dokumen lama. `gelap` (`#RRGGBB`, huruf kapital; Irisan 6) ada di kedua bagian, dibaca `#0F1319` untuk dokumen lama. `panel` (`#RRGGBB`; Irisan 7) hanya di bagian portal, dibaca `#0A0A0C` untuk dokumen lama. `navbar` (`gelap` = ikuti warna gelap, atau `#RRGGBB`) dan `kaca` (`mati`, `halus`, `sedang`, `kuat`; Irisan 8) hanya di bagian portal, dibaca `gelap` dan `mati` untuk dokumen lama.

### 4.2 Dua stylesheet tema

- Rute baru `GET /tema-portal.css` dan `GET /tema-dashboard.css` (bukan di bawah `/api`) membuat stylesheet dari bagiannya masing-masing:
  - **Portal:** blok `:root { … }` yang menimpa variabel `tokens.css`, `@font-face` huruf terpilih, `zoom` untuk skala, dan pengali sudut.
  - **Dashboard:** blok `html:root { … }` (kedua mode: huruf, `--radius`, warna pratinjau papan), `html:root:not(.dark) { … }` (mode terang) dan `html:root.dark { … }` (mode gelap) yang menimpa variabel shadcn, `@font-face` huruf terpilih, dan ukuran huruf dasar untuk skala (Irisan 3).
- Hanya pengaturan yang berbeda dari tampilan bawaan yang ditulis. Bila dokumen belum pernah disimpan atau sama dengan tampilan bawaan, isinya komentar saja, jadi tampilan sama persis dengan sekarang.
- `Content-Type: text/css`, `Cache-Control: no-cache` dengan `ETag` (hash isi): peramban selalu memeriksa ulang dan mendapat `304` bila tidak berubah, jadi perubahan tema langsung terlihat tanpa nomor versi di HTML.
- Satu `<link rel="stylesheet" href="tema-portal.css">` ditambahkan sebagai **stylesheet terakhir** (sesudah `pages.css`) di 20 halaman portal, 404, Login, Aktivasi, dan layar TV; satu `<link rel="stylesheet" href="{{ route('tema.dashboard') }}">` sesudah CSS Vite di `dashboard.blade.php` dan `absen.blade.php`. Teksnya tetap sama di setiap permintaan, jadi aturan "byte demi byte" halaman portal tetap terpenuhi. Bila stylesheet gagal dimuat, halaman tampil dengan tema bawaan.
- Saat `npm run dev`, Vite menyisipkan CSS dashboard lewat JavaScript **sesudah** `<link>` tema, sehingga aturan `:root` yang sama kuatnya akan kalah. Karena itu `tema-dashboard.css` memakai pemilih `html:root`, `html:root:not(.dark)`, dan `html:root.dark`, yang mengalahkan `:root` dan `.dark` di `index.css` apa pun urutannya.
- Karena `<link>` di `<head>` memblokir penggambaran, halaman tidak sempat tampil dengan warna lama (tanpa kedipan).

### 4.3 Turunan warna dan kontras

Kelas PHP murni (mis. `App\Support\ThemePalette`, diuji unit) menurunkan semua warna dari aksen dengan ruang warna OKLCH:

| Variabel | Diturunkan menjadi | Syarat |
|---|---|---|
| `--accent` (portal), `--brand`/`--ring`/`--sidebar-primary` (dashboard) | aksen terpilih (tombol, tautan aktif, fokus) | teks putih di atasnya ≥ 4,5:1; bila kurang, digelapkan |
| `--accent-ink` | aksen lebih gelap untuk teks/tautan | ≥ 4,5:1 di atas `--surface`, `--paper`, `--paper-2`, dan `--accent-soft` |
| `--accent-soft` | aksen sangat muda (latar lencana, sorotan) | — |
| `--night-accent`, aksen mode gelap dashboard | aksen lebih terang untuk latar gelap, sedikitnya secerah `#7FA4FF` | ≥ 4,5:1 di atas `--night`, `--night-2`, `--night-3` / latar, kartu, popover, dan kotak redup mode gelap dashboard |
| Turunan lain | hover batang grafik (pengganti `#B5C8FF`), palet grafik Laporan Penerimaan dan `--chart-1..5` dashboard | perbedaan antarwarna grafik tetap terlihat |

Aksen bawaan PINTU (`#2B63F6`) tidak diturunkan: warna rancangan tangan di `tokens.css` dan `index.css` tetap dipakai. Kecerahan dan kroma turunan dikalibrasi dari palet bawaan itu (dari `#2B63F7` turunannya berselisih paling banyak 0,015 kecerahan OKLCH dari nilai rancangan tangan). Warna status (hijau taat, merah menunggak, kuning peringatan) **tidak** ikut aksen agar maknanya tetap. `--primary` dashboard (tinta gelap untuk tombol utama) tetap tinta, hanya nuansanya mengikuti latar. Pratinjau di dashboard menampilkan rasio kontras setiap pasangan warna.

### 4.4 Huruf

- File `woff2` variabel (huruf Latin saja, ±30–60 KB per keluarga) disimpan di `public/assets/fonts/{nama}/` beserta berkas lisensi OFL-nya, diunduh sekali dari sumber resminya saat implementasi (dengan izin pemilik untuk setiap unduhan).
- Stylesheet tema hanya memuat `@font-face` huruf terpilih dengan `font-display: swap`, lalu `--font-sans`/`--font-display` diarahkan ke huruf itu dengan tumpukan sistem sebagai cadangan. Dashboard `@theme inline` diubah agar membaca variabel itu.
- Huruf yang lebarnya berbeda dapat membuat judul membungkus lebih awal; tiap huruf dalam daftar diperiksa di beranda dan dashboard (375 dan 1280 px) sebelum dimasukkan.

### 4.5 Skala tampilan

- **Portal (keputusan pemilik): properti CSS `zoom`** pada halaman (`:root:not(:has([data-react="layar-antrean"])) { zoom: 1.1; --zoom: 1.1 }`). Tata letak ikut menyempit (110% di ponsel 375 px berarti ruang ±341 px), jadi setiap tingkat skala diuji di 375, 768, dan 1280 px. Chrome kini memperbesar juga satuan `vw`/`vh`, sehingga tinggi selayar akan melebihi layar: `.auth` (Login, Aktivasi) dan menu ponsel membagi tingginya dengan `var(--zoom, 1)`. **Layar TV antrean tidak ikut skala**, karena seluruh ukurannya relatif terhadap layar dan sudah memenuhinya.
- **Dashboard:** ukuran huruf dasar (`html { font-size: 110% }`); Tailwind memakai `rem`, jadi seluruh dashboard ikut membesar secara proporsional. ±31 ukuran px langsung (mis. `text-[11px]`) diubah ke `rem` di Irisan 1. Skala **di atas 100% hanya berlaku mulai 64rem (1024 px)**: di 375 px, skala 110% membuat 5 halaman dan 120% membuat 10 halaman dashboard lebih lebar dari layar, dan di 768 px dengan sidebar terbuka 7 dan 12 halaman (semula mulai 48rem/768 px; diubah pemilik 17 Oktober 2026 sesudah Irisan 3). Skala 90% berlaku di semua layar.

### 4.6 Dashboard: tab Tampilan

- Pengaturan → tab **Tampilan** (di samping Situs, Akun, Data), dengan sub-tab **Portal** dan **Dashboard**. Formulirnya hanya bisa disimpan Administrator; pegawai lain melihatnya hanya-baca, seperti Pengaturan Situs.
- Isinya: kartu tema siap pakai, pemilih warna, nuansa latar, huruf, skala, dan sudut.
  - **Portal:** pratinjau langsung di samping formulir (tombol, kartu, lencana, panel gelap, grafik kecil, tautan) yang berubah sebelum disimpan, dan *Pratinjau di portal* (Irisan 3): membuka beranda di tab baru dengan tema yang belum disimpan, tanpa mengubah tampilan bagi pengunjung. Tautannya (`/pratinjau-tema/{token}`, 30 menit) menyimpan token di cookie terenkripsi; `tema-portal.css` lalu menyajikan tema yang belum disimpan kepada peramban itu saja, dengan pita "Pratinjau tema · belum disimpan" di setiap halaman. Halaman portal tetap byte demi byte. *Akhiri pratinjau* (atau `/pratinjau-tema/selesai`) menghapus cookie; menyimpan tema juga mengakhirinya.
  - **Dashboard:** pilihan langsung diterapkan pada dashboard yang sedang dibuka sebagai pratinjau, dan kembali ke tema tersimpan bila dibatalkan.
- Seperti Pengaturan Situs (D-026), tema bukan milik modul mana pun, sehingga tidak masuk log aktivitas yang dicatat per modul. Siapa dan kapan menyimpan dicatat riwayat lima tema terakhir (Irisan 3, tabel `theme_revisions`, hanya bila isinya berubah): *Pakai lagi* memuat tema itu ke formulir untuk disimpan ulang.

### 4.7 Rancangan API

| Alamat | Siapa | Isi |
|---|---|---|
| `GET /api/pengaturan/tema` | semua pegawai | dokumen tema (bawaan bila belum disimpan) |
| `PUT /api/pengaturan/tema` | Administrator | dokumen lengkap; `422` untuk nilai di luar daftar atau warna tidak valid |
| `GET /api/pengaturan/tema/riwayat` | Administrator | lima tema terakhir (Irisan 3) |
| `POST /api/pengaturan/tema/pratinjau` | Administrator | tema yang belum disimpan sebagaimana akan diterapkan: variabel dan `@font-face` portal untuk contoh, aksen dan stylesheet dashboard, rasio kontras (Irisan 2) |
| `POST /api/pengaturan/tema/tautan-pratinjau` | Administrator | tautan pratinjau portal berumur pendek (Irisan 3) |
| `GET /pratinjau-tema/{token}`, `GET /pratinjau-tema/selesai` | siapa pun yang memegang tautan | memasang atau menghapus cookie pratinjau, lalu ke beranda (Irisan 3) |
| `GET /tema-portal.css` | publik | stylesheet tema portal; tema yang belum disimpan bila cookie pratinjau masih berlaku |
| `GET /tema-dashboard.css` | publik (isinya hanya warna dan huruf) | stylesheet tema dashboard |

---

## 5. Tahapan pengerjaan

| Irisan | Isi | Yang terlihat pemilik |
|---|---|---|
| **1. Fondasi** | Warna dan sudut yang ditulis langsung di CSS portal, komponen React portal, layar TV, dan `papan.tsx` dijadikan variabel; huruf dan merek dashboard diarahkan ke variabel; ukuran px langsung di dashboard ke `rem`; rute `tema-portal.css` dan `tema-dashboard.css` (kosong) dan `<link>`-nya di semua halaman; tes byte demi byte disesuaikan. | Belum ada perubahan tampilan (dipastikan dengan membandingkan tangkapan layar sebelum dan sesudah). |
| **2. Warna, latar, huruf, sudut** | Dokumen `tema`, `ThemePalette` (turunan dan kontras), tema siap pakai, huruf self-hosted, tab Tampilan dengan sub-tab Portal dan Dashboard, pratinjau langsung, tombol kembali ke bawaan, *Samakan dengan portal*, log aktivitas. | Tema portal dan dashboard bisa diganti dan langsung berlaku. |
| **3. Skala dan pratinjau** | Skala portal (`zoom`) dan dashboard (ukuran huruf dasar), tautan pratinjau portal sebelum disimpan, riwayat lima tema. | Ukuran tampilan bisa diatur; tema portal bisa dicoba sebelum dipakai. |
| **4. Mode gelap portal** | Mode Terang/Gelap portal yang ditetapkan Administrator untuk semua pengunjung (keputusan pemilik); `meta theme-color` mengikuti latar portal. | Portal bisa dibuat gelap; bilah peramban di ponsel ikut warna latar. |
| **5. Ketebalan judul** | Pilihan *Ketebalan judul* (Semi-tebal 600, Tebal 700, Ekstra tebal 800) per bagian dengan saran per huruf; judul kartu dan dialog dashboard ikut huruf dan ketebalan judul (keputusan pemilik). | Judul bisa dibuat lebih tebal, mis. Plus Jakarta Sans Tebal. |
| **6. Warna gelap** | Pilihan *Warna gelap* per bagian untuk tombol gelap dan navigasi (portal) serta tombol utama (dashboard); panel gelap tetap hitam (keputusan pemilik). | Hitam di tombol dan navigasi bisa diganti, mis. biru malam. |
| **7. Warna panel gelap** | Pilihan *Warna panel gelap* di bagian Portal, terpisah dari *Warna gelap* (keputusan pemilik). | Papan layanan, dasbor layanan, dan kotak cek pajak bisa diwarnai. |
| **8. Navbar** | Warna navbar sendiri (termasuk terang, teks dan logo menyesuaikan) dan efek glass bertingkat; menu turun dan menu ponsel ikut (keputusan pemilik). | Navbar bisa diwarnai dan dibuat tembus pandang. |

Setiap irisan diperiksa seperti biasa: tes fitur dan unit, uji mutasi, typecheck/lint/build, dan pemeriksaan di browser (375, 768, 1280 px) di portal dan dashboard.

### 5.1 Hasil Irisan 1 (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Variabel baru portal (`tokens.css`, nilainya sama dengan warna yang digantikan) | `--line-3`, `--surface-2`, `--surface-hover`, `--ink-hover`, `--map` (latar dan garis); `--accent-line`, `--accent-halo` (turunan aksen); `--night-accent-2`, `--night-accent-soft`, `--night-accent-ring`, `--night-accent-dim`, `--night-hover` (panel gelap); `--warn-night`; `--logo-leaf`, `--logo-leaf-night` (daun logo, sebelumnya mengikuti `--accent`, kini terpisah agar logo tetap biru); `--r-scale` (pengali sudut). |
| Pemakaian di portal | `components.css`, `pages.css`, CSS komponen React (`layar-antrean`, `laporan-penerimaan` dengan palet `--lp-1..5` dari variabel, `lambang-pembuka`, `pemetaan-pkb`), dan gaya sebaris `layar.blade.php`. Sudut yang ditulis langsung menjadi `var(--r-xl…xs)` atau `calc(N * var(--r-scale, 1))`; kapsul, lingkaran, dan `0` tetap. |
| Cache CSS portal | CSS portal tidak berversi, jadi peramban bisa memakai `tokens.css` lama bersama `components.css` baru (terjadi saat pemeriksaan: sudut menu menjadi 0). Karena itu setiap variabel baru dipakai **dengan nilai cadangannya**, mis. `var(--map, #ECE9E1)`, seperti `layar-antrean.css` sejak awal. |
| Dashboard | Huruf di blok `@theme` biasa (bukan `inline`) sehingga `font-*` membaca variabel; pratinjau Papan Layanan memakai variabel `--night*`, `--ok-night`, `--warn-night`, `--danger-night` (kelas `bg-night`, `text-night-muted`, …); lencana pesan baru memakai `--brand-foreground`; 30 ukuran px di halaman dan komponen dashboard menjadi `rem` atau skala Tailwind. |
| Rute | `GET /tema-portal.css` dan `GET /tema-dashboard.css` (komentar saja sampai Irisan 2), `no-cache` + `ETag`, tanpa sesi dan cookie, beserta `<link>` di semua halaman. |
| Sengaja tidak dijadikan variabel | Warna status (`--ok`, `--danger`, …, hijau `rgba(95,211,155,.1)`), bayangan dan tirai berbasis tinta (`rgba(15,19,25,…)`, `rgba(10,10,12,.55)`), abu-abu netral (`#9A9EA5`), palet placeholder foto `.ph--*`, contoh warna di halaman Design System (mendokumentasikan palet bawaan), ilustrasi peta statis di Kontak, `meta theme-color`, garis dan cincin fokus komponen shadcn/ui, label peta dashboard (ukurannya dihitung `peta.ts`). |
| Pemeriksaan | Gaya terhitung semua elemen (warna, latar, garis, sudut, huruf, ukuran, bayangan, termasuk `::before`/`::after`) dibandingkan sebelum dan sesudah di 23 halaman portal, 18 halaman dashboard (terang), 5 halaman dashboard (gelap), dan Absensi: tidak ada beda selain teks yang bergerak sendiri (judul berganti di beranda, waktu relatif "… jam yang lalu"). Bukti statis: setelah variabel diganti nilainya, CSS lama dan baru identik (termasuk keadaan *hover*). Uji aksen hijau: tidak ada sisa biru selain daun logo, contoh warna Design System, dan placeholder `.ph--blue`. Simulasi `tokens.css` lama: tetap tanpa beda. |

### 5.2 Hasil Irisan 2 (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Dokumen dan API | Dokumen `tema` (`Setting::THEME`, bawaan `Setting::DEFAULT_THEME_SECTION`), `GET/PUT /api/pengaturan/tema` (`ThemeController`, `UpdateThemeRequest`; semua pegawai membaca, hanya Administrator menyimpan, seperti Pengaturan Situs), `POST /api/pengaturan/tema/pratinjau` (`ThemePreviewController`, Administrator). |
| Turunan warna | `App\Support\ThemePalette` (OKLCH, kontras WCAG, menggelapkan atau mencerahkan secukupnya dengan rona yang sama) dan `App\Support\ThemeVariables` (variabel yang diubah tiap bagian dan pasangan kontrasnya). Latar *Netral* dan *Sejuk* (`ThemeBackground`) memakai kecerahan yang sama dengan latar hangat, dengan kroma 0 atau rona 255°. Sudut (`ThemeCorners`) ×0,5, ×1, ×1,35 lewat `--r-scale` (portal) dan `--radius` (dashboard). |
| Huruf | Delapan berkas WOFF2 Latin variabel beserta `OFL.txt` di `public/assets/fonts/<nama>/`, dari Fontsource 5.3.x lewat jsDelivr (izin unduh pemilik, 17 Oktober 2026; ±283 KB huruf), dipetakan oleh enum `ThemeFont`. |
| Stylesheet | `BuildThemeStylesheet`: portal `:root`; dashboard `html:root`, `html:root:not(.dark)`, `html:root.dark`; hanya pengaturan yang berbeda dari bawaan; `@font-face` huruf terpilih dengan `font-display: swap`. |
| Tab Tampilan | `components/tema/tab-tampilan.tsx` di Pengaturan: sub-tab Portal dan Dashboard; enam tema siap pakai (PINTU, Samudra, Hutan, Senja, Tenun, Grafit, di `lib/tema.ts`); warna utama (pemilih warna dan kode, dengan keterangan bila warna disesuaikan); nuansa latar; huruf teks dan judul; kelengkungan sudut; daftar rasio kontras; *Kembalikan tampilan bawaan PINTU*; *Samakan dengan portal* di bagian Dashboard. Contoh portal (`pratinjau-portal.tsx`) memakai variabel berawalan `--p-` karena nama variabel portal juga dipakai dashboard. Dashboard yang sedang dibuka menjadi pratinjau tema dashboard (stylesheet tersimpan dinonaktifkan, stylesheet dari server disisipkan) dan kembali ke tema tersimpan saat tab ditinggalkan; sesudah disimpan, `/tema-dashboard.css` dimuat ulang tanpa memuat ulang halaman. Pegawai selain Administrator melihat formulirnya hanya-baca, tanpa pratinjau. |
| Berbeda dari rencana | Tema siap pakai tidak disimpan di dokumen (dicocokkan dari nilainya); `skala` menyusul di Irisan 3; latar tidak mengubah panel gelap; tidak ada entri log aktivitas (seperti Pengaturan Situs, D-026). |
| Pemeriksaan | Tes unit `ThemePalette` dan `ThemeVariables` (sepuluh macam aksen termasuk kuning terang, biru tua, abu, hijau menyala, putih, pada tiga latar: semua pasangan teks ≥ 4,5:1, rona terjaga, turunan dari `#2B63F7` dekat nilai rancangan tangan), tes fitur API, stylesheet, nilai bawaan sama dengan `tokens.css`/`index.css`, dan berkas huruf. Di browser: tema siap pakai, pratinjau portal dan dashboard (mode terang dan gelap), kuning `#F2C46B` dipakai sebagai `#936A00`, simpan lalu portal memakai aksen, DM Sans, dan sudut navigasi 29,7 px, kembali ke bawaan; kedelapan huruf di enam halaman portal (375 dan 1280 px) dan dashboard (375 px, Lexend): tidak ada halaman yang meluap ke samping, sebagian judul membungkus satu baris lebih. |

### 5.3 Hasil Irisan 3 (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Skala | `skala` 90/100/110/120 di dokumen (`Setting::THEME_SCALES`). Portal: `zoom` dan `--zoom` untuk semua halaman kecuali layar TV; `tokens.css` mendapat `--zoom: 1`, `.auth` dan menu ponsel membagi tinggi selayar dengan `var(--zoom, 1)`. Dashboard: `font-size` di `html:root`, untuk 110/120% di dalam `@media (min-width: 64rem)` (semula 48rem). Tab Tampilan: pilihan Skala tampilan; contoh portal ikut di-`zoom`; memilih tema siap pakai tidak mengubah skala. |
| Pratinjau di portal | `App\Support\ThemePreview` (token acak di cache 30 menit, cookie `pintu_pratinjau_tema`), `POST /api/pengaturan/tema/tautan-pratinjau` (Administrator), `ThemePreviewPageController` (`/pratinjau-tema/{token}`, `/pratinjau-tema/selesai`), `ThemeStylesheetController` menyajikan tema pratinjau dan pita `body::after`. Tombol *Pratinjau di portal* (tab baru; bila diblokir, toast dengan tombol Buka) dan *Akhiri pratinjau*. |
| Riwayat | Tabel `theme_revisions` (`ThemeRevision`, dimigrasi di `pintu`), `SaveTheme` mencatat tema yang berubah beserta penyimpannya dan menyisakan lima terbaru, `GET /api/pengaturan/tema/riwayat` (Administrator, terbaru dulu). Kartu *Riwayat* di tab Tampilan dengan *Sedang dipakai* dan *Pakai lagi*. |
| Pemeriksaan | Tes fitur (skala, riwayat, tautan pratinjau, cookie, kedaluwarsa) dan unit; uji mutasi 32 dari 35 mati lalu dua pemeriksaan yang terbukti tidak diperlukan dihapus (aturan `.auth` diperiksa di browser). Browser: zoom 90/110/120% di tujuh halaman portal pada 375 px dan empat halaman pada 768 dan 1280 px tanpa luapan; Login tetap setinggi layar; layar TV tetap zoom 1; skala dashboard 110/120% di 36 halaman (375 dan 768 px, temuan di atas); alur tab Tampilan: skala, contoh portal, tautan pratinjau di beranda dan Pemetaan (aksen, huruf, zoom, pita) lalu diakhiri, dua simpanan uji lalu riwayat, *Pakai lagi*, *Batalkan*. |

### 5.4 Hasil Irisan 4 (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Keputusan | Pemilik memilih mode gelap portal **ditetapkan Administrator** (terang atau gelap untuk semua pengunjung), bukan mengikuti perangkat atau tombol di portal. |
| Token baru | `--on-ink` (teks di atas `--ink`) dan `--chrome` (gelap tetap) di `tokens.css`, nilai terangnya sama dengan sekarang dan dipakai dengan nilai cadangan. `--ink` sebagai latar dipisah dua: **tombol dan pilihan aktif** (tombol gelap, chip terpilih, halaman aktif, plus akordeon terbuka) memakai `--ink` + `--on-ink` sehingga dibalik di mode gelap; **permukaan gelap tetap** (navigasi dan menunya, menu ponsel, tautan lompat, toast, pita, kartu status jadwal, kotak nilai, kepala bagan, label peta, tombol putar) memakai `--chrome`. Elemen yang selalu putih (tombol terang, tooltip grafik, kapsul video) memakai `--chrome` untuk teksnya. Bukti statis: tampilan terang identik (hanya ejaan `#fff`/`#ffffff`). |
| Palet gelap | `ThemeVariables::PORTAL_DARK` (latar `#100F0D`, kartu `#1A1917`, teks `#EFEEEB`, teks pendukung `#AFAEAA`, garis, `--chrome #232321`) mengikuti nuansa latar dengan kecerahan sama; `PORTAL_DARK_STATUS` (warna status versi terang dan latar tipisnya, bayangan lebih pekat); aksen diturunkan lebih terang untuk latar gelap (tidak ada isian aksen yang membawa teks). `color-scheme: dark` untuk kontrol formulir dan bilah gulir; logo kaki halaman putih lewat `content: url()`. Panel gelap (`--night*`) tidak berubah. |
| Warna bilah peramban | `main.js` (`initWarnaBilah`) menyalin `--paper` ke `meta theme-color` setelah CSS dimuat; halaman tetap byte demi byte. |
| Dashboard | Pilihan *Mode tampilan* di bagian Portal; contoh portal ikut gelap (`--p-chrome`, `--p-on-ink`); daftar *Keterbacaan* memakai pasangan mode gelap; tema siap pakai tidak mengubah mode; *Samakan dengan portal* tidak menyalin mode. |
| Pemeriksaan | Tes unit (sepuluh aksen × tiga latar × dua mode: semua pasangan teks ≥ 4,5:1) dan fitur; uji mutasi 16 dari 20 mati, tiga setara, satu celah (transparansi `--accent-halo` gelap) dites. Browser: pemindai kontras otomatis (setiap teks terlihat terhadap latar efektifnya) di 18 halaman portal termasuk komponen React, Login, dan 404 dalam mode gelap: satu temuan (kapsul *Tonton Video* putih dengan teks `--ink`) diperbaiki, lalu 0 kekurangan; label peta Pemetaan memakai lingkar gelap; perbandingan gaya terhitung mode terang dengan rekaman sebelum Irisan 1 di delapan halaman: 0 beda warna, sudut, dan huruf selain tombol kategori aktif di beranda yang mengikuti data; `theme-color` `#100F0D` (gelap) dan `#F7F6F2` (terang); tab Tampilan: mode, contoh, kontras, tema siap pakai, *Samakan*, *Kembalikan*, *Batalkan*. |

### 5.5 Hasil Irisan 5: ketebalan judul (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Latar belakang | Pemilik menyukai Plus Jakarta Sans sebagai huruf judul, tetapi judulnya terasa kurang tebal. Tebal batang huruf diukur di browser dengan Inter 600 sebagai rujukan (dekat dengan SF Pro Display Semibold, judul bawaan): Plus Jakarta Sans 600 = 88%, 700 = 102%, 800 = 117%. Judul portal memakai satu ketebalan tetap (`--fw-display: 600`). Berkas huruf variabel yang di-host sudah memuat semua ketebalan, jadi tidak ada unduhan tambahan. |
| Keputusan pemilik | Tiga tingkat ketebalan judul tanpa Light/Regular. Di dashboard, judul kartu, dialog, dan lembar samping ikut huruf dan ketebalan judul (sekarang hanya ±8 angka dan judul besar yang memakai huruf judul). Ketebalan teks tetap 400. |
| Data | `tebalJudul` (600, 700, 800; `Setting::THEME_HEADING_WEIGHTS`) di kedua bagian dokumen `tema`; dokumen dan riwayat lama dibaca 600, sama dengan tampilan sekarang. `ThemeVariables::DEFAULT_HEADING_WEIGHT` = 600. |
| Saran per huruf | Saat huruf judul (atau huruf teks bila judul *Sama*) diganti di tab Tampilan, ketebalan diisi saran yang setebal judul bawaan: Plus Jakarta Sans, Nunito Sans, dan Manrope → Tebal (700); huruf sistem, Inter, DM Sans, Source Sans 3, IBM Plex Sans, dan Lexend → Semi-tebal (600). Tema siap pakai mengisi saran untuk hurufnya. IBM Plex Sans paling tebal 700, jadi *Ekstra tebal* tidak tersedia untuknya. Saran dan batas itu ada di `HURUF` (`lib/tema.ts`). Mengganti huruf teks tidak mengubah ketebalan bila judul memakai huruf sendiri. |
| Portal | `--fw-display` diatur tema. Elemen berhuruf judul yang masih menulis ketebalan langsung (angka kartu dan harga, nama hasil cek pajak, hitung mundur kartu status, judul layar TV) diarahkan ke `--fw-display`; angka besar layar TV memakai `max(700, var(--fw-display, 600))`. Kutipan (`.prose blockquote`, 500), "Rp" kecil di angka harga (550), contoh *Metrik* di Design System (gaya inline 600, mendokumentasikan nilai bawaan), dan judul kecil berhuruf teks tetap. |
| Dashboard | `--font-heading: var(--font-sans)` di `@theme` dan `--fw-heading: 600` di `:root` (`index.css`, juga dipakai Absensi). `CardTitle`, `DialogTitle`, `SheetTitle`, `AlertDialogTitle`, judul bagian halaman (`h1`–`h3` yang semibold), dan angka di kartu memakai `font-heading font-(--fw-heading)`; elemen `font-display` (angka kalkulator, papan, ringkasan) memakai `font-(--fw-heading)`. Judul kecil di header (500) dan judul kartu yang sengaja `font-medium` tetap. Tema mengisi `--font-heading` (huruf judul, atau tumpukan judul sistem untuk *Huruf sistem*) bila huruf judul bukan *Sama*, dan `--fw-heading` bila bukan 600. |
| Tab Tampilan | Pilihan *Ketebalan judul* di bagian Huruf: contoh "Aa" dalam huruf judul pada tiap ketebalan, tanda *disarankan*, dan keterangan batas huruf; `FieldPilih` mendapat `onPilih` untuk mengisi saran. Contoh portal memakai `--p-fw-display`; pratinjau dashboard langsung ikut; *Samakan dengan portal* ikut menyalin ketebalan. |
| Pemeriksaan | Tes fitur dan unit (validasi, dokumen dan riwayat lama, variabel, stylesheet; seluruh rangkaian 1457 tes lulus); uji mutasi 17 dari 17 mati; typecheck/lint/build. Browser (lewat DOM, Vite dev pemilik berjalan, aset build disuntikkan; tema tersimpan pemilik di DB dev tidak diubah): gaya huruf terhitung (keluarga, ketebalan, ukuran, jarak) direkam sebelum perubahan lalu dibandingkan. Portal 17 halaman dan 2 komponen React: 0 beda. Dashboard 36 halaman: satu-satunya beda adalah keluarga huruf judul kartu dan dialog yang kini mengikuti huruf judul tema tersimpan (Plus Jakarta Sans), ketebalan dan ukuran tetap. Tanpa tema (tampilan bawaan): 89 judul di 16 halaman berhuruf teks dengan ketebalan 600, kecuali dua judul kartu yang sengaja 500. Ketebalan 800 disuntikkan: semua elemen berhuruf judul di 8 halaman portal ikut, kecuali dua pengecualian yang disengaja di atas; 78 judul di 15 halaman dashboard ikut; dengan Plus Jakarta Sans 800 di 375 px tidak ada halaman portal (9) atau dashboard (15) yang melebar; `max()` layar TV terhitung 700 dan 800. Tab Tampilan: Plus Jakarta Sans → Tebal 700 (disarankan), IBM Plex Sans → 600 dengan Ekstra tebal nonaktif dan keterangannya, tema siap pakai Tenun → 600, huruf teks diganti saat judul Lexend → ketebalan tetap, judul *Sama* + Nunito Sans → 700 lalu Inter → 600, contoh portal dan pratinjau dashboard ikut, *Samakan dengan portal*, *Batalkan*; tidak ada yang disimpan. |

### 5.6 Hasil Irisan 6: warna gelap tombol dan navigasi (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Latar belakang | Pemilik ingin warna hitam yang dipakai sebagai latar (bukan teks) bisa diganti. Latar gelap portal terdiri dari tiga kelompok: `--ink` sebagai isian (tombol gelap, chip terpilih, halaman aktif, plus akordeon terbuka; token yang sama juga warna teks utama), `--chrome` (navigasi dan menunya, menu ponsel, tautan lompat, toast, pita, kartu status jadwal, kotak nilai, kepala bagan, label peta, tombol putar), dan `--night*` (panel gelap). Dashboard: `--primary` (tombol utama). |
| Keputusan pemilik | Tombol dan navigasi saja (isian `--ink` dan `--chrome`; panel `--night*` tetap); warna bebas dengan contoh; tema siap pakai tetap hitam. |
| Data | `gelap` (`#RRGGBB`, huruf kapital) di kedua bagian; bawaan `#0F1319`; dokumen dan riwayat lama dibaca bawaan. Tema siap pakai tidak mengubahnya (seperti skala dan mode); *Samakan dengan portal* menyalinnya. |
| Turunan | Warna dibuat selalu gelap (kecerahan OKLCH paling tinggi 0,28 dengan rona yang sama) lalu digelapkan lagi bila perlu sampai teks putih, teks abu panel (`#9BA0A8`), dan aksen panel (`--night-accent`) di atasnya ≥ 4,5:1. Portal mode terang: `--ink-fill` (isian, token baru dengan cadangan `var(--ink)`), `--ink-hover` (lebih terang), `--chrome`, dan `--chrome-2` (kotak ikon menu dan avatar kepala bagan, token baru dengan cadangan `var(--night-2)`). Mode gelap: isian tetap dibalik (terang); `--chrome` memakai rona pilihan dengan kecerahan sedikitnya sama dengan `--chrome` gelap bawaan. Dashboard: `--primary` mode terang (mode gelap tetap terang). Warna bawaan tidak menghasilkan variabel apa pun. `ThemeVariables::portalDark()` dan `dashboardDark()` memberi warna yang dipakai; contoh: biru PINTU `#2B63F6` → `#082163`, kuning `#F2C46B` → `#372500`, putih → `#292929`; kelima contoh gelap dipakai apa adanya. |
| CSS portal | Isian `--ink` (tombol gelap, chip terpilih, plus akordeon, pager, pilihan radio) memakai `var(--ink-fill, var(--ink))`; garis hover dan teks tetap `--ink`. Kotak ikon menu navigasi dan avatar kepala bagan memakai `var(--chrome-2, var(--night-2))`. `tokens.css` mendefinisikan `--ink-fill: var(--ink)` dan `--chrome-2: var(--night-2)`. Baris contoh "Terang di gelap" di Design System memakai `--chrome` (sebelumnya `--ink`, yang ikut terang di mode gelap). |
| Tab Tampilan | Bagian *Warna gelap* sesudah *Warna utama*: pemilih warna dan kode (komponen `FieldWarna`, dipakai juga oleh *Warna utama*), contoh siap klik (`GELAP` di `lib/tema.ts`; *Dari warna utama* mengambil warna utama saat itu), dan keterangan *Dipakai sebagai …* dari `gelap` di respons pratinjau; contoh portal (`--p-ink-fill`) dan pratinjau dashboard ikut; daftar *Keterbacaan* mendapat *Teks putih pada tombol gelap*, *Teks abu pada navigasi gelap*, *Aksen pada navigasi gelap*, dan di dashboard *Teks putih pada tombol utama*; riwayat menampilkan warna gelap di samping warna utama. Sekalian diperbaiki: tema siap pakai PINTU semula ikut mengembalikan skala ke 100%. |
| Pemeriksaan | Tes unit (turunan; sepuluh warna gelap × empat aksen × dua mode: semua pasangan ≥ 4,5:1 dan kecerahan paling tinggi ±0,28; rona terjaga; warna bawaan tanpa variabel) dan fitur (validasi, dokumen lama, stylesheet, pratinjau); seluruh rangkaian 1482 tes lulus; uji mutasi 20 dari 25 mati, lima setara (pengaman kontras yang tidak pernah terpicu selama batas kecerahan 0,28, karena warna segelap itu sudah terbaca di bawah teks putih, abu, dan aksen panel). Browser (lewat DOM, aset build disuntikkan, tema tersimpan pemilik tidak diubah): warna terhitung (teks, latar, garis, bayangan, termasuk `::before`/`::after`) direkam sebelum perubahan lalu dibandingkan: portal 18 halaman 0 beda (selain tab dasbor beranda yang sedang bertransisi), dashboard 36 halaman 0 beda pada setiap elemen yang ada di kedua rekaman (selisih hanya struktur grafik). CSS server untuk `#0F1B3D` disuntikkan di 6 halaman: semua latar `#0F1319` ikut (tombol, chip, plus akordeon, navigasi dan menunya, menu ponsel, tautan lompat, toast, pita, kartu status, kotak nilai, kepala bagan, tombol putar), panel gelap tetap kecuali kotak ikon menu dan avatar (`--chrome-2`); toast dan plus akordeon sempat tampak tertinggal karena transisi CSS tidak berjalan di panel, dan ikut setelah transisi dimatikan. Mode gelap dengan warna itu: pemindai kontras 0 kekurangan di 4 halaman. Tab Tampilan: contoh, *Dari warna utama* (`#082163` dengan keterangannya), pratinjau tombol utama dashboard, *Samakan dengan portal*, *Batalkan*; tidak ada yang disimpan. |

### 5.7 Hasil Irisan 7: warna panel gelap (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Keputusan pemilik | Sesudah menyimpan *Warna gelap* Biru malam, pemilik melihat papan layanan, dasbor layanan, dan kotak cek pajak tetap hitam (sesuai pilihan Irisan 6) dan memilih **pilihan warna terpisah** untuk panel gelap. |
| Data | `panel` (`#RRGGBB`, huruf kapital) hanya di bagian portal (dashboard tidak punya panel gelap selain pratinjau papan, yang tetap hitam); bawaan `#0A0A0C`; dokumen dan riwayat lama dibaca bawaan. Tema siap pakai tidak mengubahnya. |
| Turunan | `--night` dari warna pilihan (kecerahan OKLCH paling tinggi 0,24, rona sama), `--night-2` dan `--night-3` sedikit lebih terang (selisih seperti hitam bawaan), `--night-line` dan `--night-hover` (`ThemeVariables::portalPanel()`, `PORTAL_PANEL`, `PANEL_STEPS`); panel dan kotaknya digelapkan bila perlu agar teks putih dan teks abu panel (`#9BA0A8`) ≥ 4,5:1, garis dan hover tombol tidak membawa teks itu. Warna gelap sendiri yang sudah segelap batas dipakai apa adanya. Aksen panel (`--night-accent`, latar tipis dan cincinnya) dihitung ulang terhadap permukaan baru, juga untuk warna utama bawaan. Berlaku di kedua mode. Warna bawaan tidak menghasilkan variabel. |
| CSS | Tidak ada aturan baru: semua panel sudah memakai `--night*`. `--chrome-2` di `tokens.css` menjadi nilai tetap `#17181B` (sebelumnya `var(--night-2)`) dan token baru `--chrome-line` (`#2A2B30`) dipakai garis di atas navigasi (pemisah pita, pemisah grup menu ponsel, bingkai avatar kepala bagan; sebelumnya `--night-line`), keduanya dengan nilai cadangan, agar ikut *Warna gelap*, bukan panel; *Warna gelap* kini juga mengisi `--chrome-line`. |
| Tab Tampilan | Bagian *Warna panel gelap* di sub-tab Portal sesudah *Warna gelap*: pemilih warna (`FieldWarna`), contoh `PANEL` di `lib/tema.ts` (*Hitam*, *Sama dengan warna gelap*, *Dari warna utama*, Biru malam `#0C1839`, Hijau tua `#042017`, Cokelat kopi `#25170F`, Ungu terong `#1F1135`, yang sudah segelap panel), keterangan *Dipakai sebagai …* dari `panel` di respons pratinjau; contoh portal memakai `--night`/`--night-2` dari tema; *Keterbacaan* memakai *Aksen pada panel gelap* (terhadap `--night-2`) dan *Teks abu pada panel gelap* (terhadap `--night-3`). |
| Pemeriksaan | Tes unit (sepuluh warna panel × empat aksen × dua mode: putih, abu, dan aksen ≥ 4,5:1 di setiap permukaan; kecerahan panel ≤ ±0,22; rona terjaga; aksen panel mengikuti warna utama) dan fitur; seluruh rangkaian 1497 tes lulus; uji mutasi 19 dari 23 mati, empat setara (pengaman yang tidak pernah terpicu di bawah batas kecerahan 0,22 dan aksen panel yang selalu ≥ 0,73). Browser: CSS server untuk panel `#0C1839` disuntikkan di 5 halaman: setiap latar `--night*` ikut (papan, dasbor, kotak, daftar papan, cek pajak, sakelar, tombol gelap panel), pemindai kontras 0 kekurangan; contoh Design System yang mendokumentasikan nilai bawaan tetap. Tampilan tersimpan (pemilik menyimpan Biru malam di DB dev sesudah rekaman awal): semua beda di 4 halaman berasal dari keluarga hitam bawaan ke turunan Biru malam yang tersimpan (termasuk garis navigasi `--chrome-line` baru), tidak ada beda lain. Tab Tampilan: contoh, *Sama dengan warna gelap* (`#0C1839` dengan keterangannya), *Hijau tua*, daftar kontras, *Batalkan*; tidak ada yang disimpan. |

### 5.8 Hasil Irisan 8: warna navbar dan efek glass (17 Oktober 2026)

| Bagian | Yang dibuat |
|---|---|
| Keputusan pemilik | Warna navbar bebas dengan teks yang menyesuaikan; menu turun dan menu ponsel ikut warna navbar dan efek glass; efek glass dengan tingkat kekuatan. |
| Data | `navbar` (`gelap` atau `#RRGGBB`, huruf kapital) dan `kaca` (`mati`, `halus`, `sedang`, `kuat`) di bagian portal saja; dokumen lama dibaca `gelap` dan `mati`. Tema siap pakai tidak mengubahnya. |
| Token | Navbar, menu turun, dan menu ponsel memakai token sendiri dengan nilai cadangan sama dengan sekarang: `--nav-surface` (latar), `--nav-tint` (warna teks sebagai RGB, sehingga semua `rgba(255,255,255,a)` di navbar menjadi `rgba(var(--nav-tint), a)`), `--nav-muted`, `--nav-accent`, `--nav-2`, `--nav-line`, `--nav-cta` dan `--nav-cta-text` (tombol di navbar dan menu ponsel). |
| Turunan | `ThemeVariables::portalNavbar()`/`navbarParts()`: server memilih teks putih atau gelap menurut kontras; `navbarColour()` menggelapkan atau menerangkan warna navbar bila tautannya (teks 72%, `ThemePalette::blend`) kurang dari 4,5:1, mis. biru PINTU menjadi `#0D45D2`. Navbar terang: logo gelap (`logo-pintu-nav.png`), tombol gelap. Teks redup, aksen, kotak ikon, dan garis menu diturunkan dari warna navbar. Efek glass: latar tembus pandang dengan `backdrop-filter` (blur dan saturasi) bertingkat (*Halus* 90% dan blur 12 px, *Sedang* 80% dan 18 px, *Kuat* 68% dan 26 px); tutupan dinaikkan bila perlu, sampai penuh, agar tautan, teks redup, dan aksen tetap ≥ 4,5:1 di atas latar putih maupun hitam di belakangnya (`navbarGlassCover()`); warna menengah seperti biru PINTU hanya terbaca di atas warna penuh, jadi glass-nya tidak terlihat. Glass navbar dipasang di `::before` yang membentuk notch beserta lekuknya lewat `mask`, agar menu turun di dalamnya bisa mem-blur halaman sendiri. |
| Ikut diperbaiki | Di mode gelap, tombol terang (*Cek Persyaratan* dan tombol terang lain) menjadi gelap saat disorot karena memakai `--surface-hover` mode gelap; kini memakai `--light-hover` yang tetap terang. |
| Tab Tampilan | Bagian *Navbar* di sub-tab Portal (`BagianNavbar`): *Ikuti warna gelap* atau *Warna sendiri* (pemilih warna dan contoh `NAVBAR`: Putih, Kertas, Warna utama, Biru malam, Hijau tua, Hitam; keterangan *Dipakai sebagai …*), sakelar *Efek glass* dengan tingkat `KACA` dan keterangan bila tutupan dinaikkan atau glass tidak terlihat (`tutupanKaca` di respons pratinjau); contoh portal memakai `--p-nav-surface`, `--p-nav-tint`, `--p-nav-cta`; daftar *Keterbacaan* mendapat *Tautan navbar*, *Teks redup menu navbar*, dan *Aksen navbar*, masing-masing di atas latar terburuknya. |
| Pemeriksaan | Tes unit (sepuluh warna navbar × empat tingkat glass × dua mode: tautan, teks redup, dan aksen ≥ 4,5:1; navbar terang; tutupan glass; `ThemePalette::blend`) dan fitur (validasi, dokumen lama, stylesheet logo dan glass, pratinjau); seluruh rangkaian 1517 tes lulus; uji mutasi 29 dari 29 mati (dua sempat bertahan karena nilai kotak ikon navbar putih dan teks tombol contoh belum dites, lalu dites). Browser (lewat DOM, aset build disuntikkan, CSS uji dari server di folder build sementara lalu dihapus; tema tersimpan pemilik tidak diubah): gaya warna terhitung 7 halaman dibandingkan dengan rekaman awal Irisan 8, 0 beda (beranda: 1 kata hero yang berganti); navbar putih: teks dan tautan gelap, logo gelap, tombol biru malam (warna gelap tersimpan), menu turun dan menu ponsel putih dengan teks redup dan kotak ikon terang; pemindai kontras di 1280 px termasuk menu turun terbuka 0 kekurangan; glass putih *Sedang* dan gelap *Kuat* (tutupan dinaikkan ke 74%): `::before` bertopeng notch dengan blur, `::after` disembunyikan, menu turun dan menu ponsel memakai blur sendiri; tangkapan layar: isi halaman kabur di belakang navbar dan sudut notch sama dengan versi tanpa glass. Tab Tampilan: pilihan, contoh, *Warna utama* → `#0D45D2`, sakelar dan tingkat glass, keterangan tutupan, *Batalkan*; tidak ada yang disimpan. |

---

## 6. Pengujian

- **Unit:** `ThemePalette` — turunan warna untuk aksen terang, gelap, dan jenuh; semua rasio kontras ≥ 4,5:1; warna status tidak berubah.
- **Fitur:** `GET/PUT /api/pengaturan/tema` (bawaan, hanya Administrator, validasi daftar tertutup dan hex); `GET /tema-portal.css` dan `/tema-dashboard.css` (kosong sebelum disimpan, isi sesuai pilihan bagiannya saja, `ETag`/`304`, hanya `@font-face` huruf terpilih, tidak ada teks bebas di keluaran, pratinjau hanya dengan tautan sah).
- **Halaman:** `<link>` tema portal ada di semua halaman portal, 404, Login, Aktivasi, dan layar TV; `<link>` tema dashboard di dashboard dan Absensi; halaman portal tetap byte demi byte.
- **Browser:** tiap tema siap pakai dan tiap huruf di beranda, Pemetaan PKB, Laporan Penerimaan, Antrean (lacak dan layar TV), dan dashboard (terang dan gelap); skala 90–120%.

---

## 7. Keputusan pemilik (D-057)

| Hal | Keputusan |
|---|---|
| Cakupan | **Portal dan dashboard punya tema masing-masing**, diatur dari satu tab Tampilan di Pengaturan. Absensi mengikuti dashboard; 404, Login, Aktivasi, dan layar TV mengikuti portal. Mode gelap dashboard tetap pilihan tiap pegawai. |
| Skala portal | **`zoom` per halaman** (90–120%), diuji per tingkat skala di ponsel, tablet, dan desktop. |
| Logo | **Tetap biru PINTU** di header, footer, dan favicon, apa pun warna temanya. |
| Mode gelap portal (Irisan 4) | **Ditetapkan Administrator** untuk semua pengunjung, terang atau gelap, apa pun pengaturan perangkatnya; tanpa tombol di portal. Mode gelap dashboard tetap pilihan tiap pegawai. |
| Huruf | **Daftar terkurasi yang di-host sendiri** (berlisensi OFL), bukan Google Fonts. |
| DM Sans khusus judul (sesudah Irisan 5) | **DM Sans hanya untuk judul** (pemilik): berkas DM Sans yang di-host tidak punya angka berlebar sama (`tnum`), padahal tabel portal dan dashboard memakainya (`tabular-nums`), jadi angka tabel tidak sejajar bila DM Sans menjadi huruf teks. Lexend juga tanpa `tnum`, tetapi sudah khusus judul. Tema siap pakai *Senja* kini memakai teks Nunito Sans dan judul DM Sans. |
| Warna gelap (Irisan 6) | **Tombol dan navigasi saja**: tombol gelap, pilihan terpilih, navigasi dan menunya, toast, dan kartu gelap (portal) serta tombol utama (dashboard); panel gelap (papan layanan, dasbor beranda, layar TV) tetap hitam. **Warna bebas dengan contoh**, disesuaikan server agar tetap gelap dan terbaca. **Tema siap pakai tetap hitam.** Warna teks tidak berubah. |
| Navbar (Irisan 8) | **Warna bebas, teks menyesuaikan** (terang → teks, ikon, logo, dan tombol gelap); **menu turun dan menu ponsel ikut** warna dan efek glass navbar; **efek glass dengan tingkat** *Halus*, *Sedang*, *Kuat* (semua keputusan pemilik). Bawaan mengikuti *Warna gelap*. |
| Warna panel gelap (Irisan 7) | Sesudah mencoba *Biru malam*, pemilik ingin panel gelap juga bisa diganti dan memilih **pilihan warna terpisah** dari *Warna gelap*. |
| Ketebalan judul (Irisan 5) | **Tiga tingkat** (Semi-tebal 600, Tebal 700, Ekstra tebal 800) per bagian, tanpa Light/Regular agar judul selalu lebih tebal dari teks, dengan saran per huruf. **Judul kartu dan dialog dashboard ikut** huruf dan ketebalan judul (tampilan bawaan tetap). **Ketebalan teks tetap 400** untuk semua huruf. |
| Mengikuti saran (bisa diubah pemilik) | Warna: tema siap pakai ditambah warna bebas dengan penyesuaian kontras otomatis. Pratinjau: langsung di dashboard, ditambah pratinjau portal sebelum disimpan (Irisan 3). Hanya Administrator yang menyimpan. |

---

## 8. Risiko dan penanganannya

| Risiko | Penanganan |
|---|---|
| Warna pilihan sulit dibaca | Turunan warna dipaksa memenuhi kontras 4,5:1; pratinjau menampilkan rasio kontras; tombol kembali ke bawaan. |
| Sebagian elemen tidak ikut berubah | Irisan 1 menjadikan warna yang ditulis langsung sebagai variabel; pemeriksaan visual tiap halaman dengan aksen kontras tinggi (mis. hijau) untuk menemukan sisa biru. |
| Logo biru bertabrakan dengan aksen lain | Tema siap pakai dipilih agar tetap serasi dengan logo biru; pratinjau menampilkan logo bersama aksen. |
| Huruf baru mengubah lebar teks | Setiap huruf diperiksa di halaman utama sebelum masuk daftar; tumpukan sistem sebagai cadangan. |
| `zoom` merusak tata letak portal | Tingkat skala dibatasi (90–120%) dan diuji di tiga lebar layar; peta, grafik, dan layar TV yang mengukur lebar atau memakai `vh` diperiksa khusus. |
| Perubahan tidak langsung terlihat | Stylesheet tema memakai `no-cache` + `ETag`; CSS portal lain memang belum berversi (catatan lama di KONTEKS §8.2). |
| Tema terpasang keliru | Riwayat lima tema (mencatat siapa dan kapan) dan tombol kembali ke bawaan. |

---

## 9. Dokumen dan kode yang terdampak

- Baru: `app/Support/ThemePalette.php`, controller dan request tema, rute `tema-portal.css` dan `tema-dashboard.css`, `public/assets/fonts/`, tab Tampilan di `resources/js/dashboard/pages/pengaturan.tsx`, tes unit dan fitur.
- Dibuat di Irisan 1: `app/Http/Controllers/ThemeStylesheetController.php`, rute `tema.portal` dan `tema.dashboard` di `routes/web.php`, `tests/Feature/ThemeStylesheetTest.php`.
- Diubah di Irisan 8: `Setting`, `UpdateThemeRequest`, `ThemeVariables`, `ThemePalette` (`blend`), `BuildThemeStylesheet` (logo navbar terang, glass), `ThemePreviewController`, `public/assets/css/{tokens,components}.css` (token `--nav-*`, `--light-hover`), tab Tampilan, contoh portal, `lib/tema.ts`, `lib/api/types.ts`.
- Diubah di Irisan 7: `Setting`, `UpdateThemeRequest`, `ThemeVariables`, `ThemePreviewController`, `public/assets/css/{tokens,components,pages}.css` (`--chrome-2` tetap, `--chrome-line`), tab Tampilan, contoh portal, `lib/tema.ts`, `lib/api/types.ts`.
- Diubah di Irisan 6: `Setting`, `UpdateThemeRequest`, `ThemeVariables`, `ThemePreviewController`, `public/assets/css/{tokens,components,pages}.css`, `design-system.blade.php` (satu baris contoh), tab Tampilan, contoh portal, `lib/tema.ts`, `lib/api/types.ts`.
- Diubah di Irisan 5: `Setting`, `UpdateThemeRequest`, `ThemeVariables`, `public/assets/css/{tokens,components,pages}.css`, `resources/js/portal/antrean/layar-antrean.css`, `resources/js/dashboard/index.css`, komponen judul shadcn (`card`, `dialog`, `sheet`, `alert-dialog`), `FieldPilih`, tab Tampilan dan contoh portal, `lib/tema.ts`, beberapa halaman dashboard (judul bagian dan angka besar).
- Diubah di Irisan 4: `public/assets/css/{tokens,base,components,pages}.css` (`--on-ink`, `--chrome`), `public/assets/js/main.js` (`initWarnaBilah`), `ThemeVariables`, `BuildThemeStylesheet`, `Setting`, `UpdateThemeRequest`, `ThemeRevisionResource`, tab Tampilan dan contoh portal.
- Dibuat di Irisan 3: `app/Support/ThemePreview.php`, `app/Actions/SaveTheme.php`, `app/Models/ThemeRevision.php` dan factory-nya, migrasi `create_theme_revisions_table`, `app/Http/Resources/ThemeRevisionResource.php`, `app/Http/Controllers/Dashboard/{ThemeRevisionController,ThemePreviewLinkController}.php`, `app/Http/Controllers/ThemePreviewPageController.php`, `tests/Feature/{ThemeRevisionTest,ThemePreviewLinkTest}.php`.
- Dibuat di Irisan 2: `app/Support/{ThemePalette,ThemeVariables}.php`, `app/Enums/{ThemeFont,ThemeBackground,ThemeCorners}.php`, `app/Actions/BuildThemeStylesheet.php`, `app/Http/Controllers/Dashboard/{ThemeController,ThemePreviewController}.php`, `app/Http/Requests/Dashboard/UpdateThemeRequest.php`, `public/assets/fonts/`, `resources/js/dashboard/lib/tema.ts`, `resources/js/dashboard/components/tema/`, `tests/Unit/Support/`, `tests/Feature/ThemeControllerTest.php`.
- Diubah: `public/assets/css/{tokens,base,components,pages}.css`, CSS komponen portal (`resources/js/portal/**/*.css`), `resources/js/dashboard/index.css`, `pages/papan.tsx`, semua halaman portal dan `errors/404`, `auth/aktivasi`, `layar`, `dashboard`, `absen` (satu `<link>`), `app/Models/Setting.php`, `routes/api.php`, `routes/web.php`, `tests/Feature/PortalPagesTest.php`, `docs/api.md`, `docs/dashboard.md`, `docs/decisions.md`, `KONTEKS-PROYEK.md`.
