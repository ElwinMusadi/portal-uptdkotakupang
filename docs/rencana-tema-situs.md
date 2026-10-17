# Rencana Implementasi — Tema Portal dan Dashboard

**Status:** rencana dengan keputusan awal pemilik (17 Oktober 2026, [D-057](decisions.md#d-057--2026-10-17--tema-portal-dan-dashboard-rencana-dan-keputusan-awal)). **Irisan 1 (fondasi) dan Irisan 2 (warna, latar, huruf, sudut, tab Tampilan) selesai** pada 17 Oktober 2026 (hasilnya di §5.1 dan §5.2); berikutnya Irisan 3.
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

Di luar lingkup rencana ini: mode gelap untuk portal publik, mengganti tata letak atau susunan halaman, warna logo (logo tetap biru PINTU, keputusan pemilik), dan tema per pengguna (mode gelap dashboard tetap pilihan masing-masing pegawai seperti sekarang).

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
| Huruf teks | *Sistem (bawaan)*, Inter, Plus Jakarta Sans, DM Sans, Source Sans 3, IBM Plex Sans, Nunito Sans. | Berlisensi OFL dan disimpan di situs sendiri (`public/assets/fonts/`, keputusan pemilik). Hanya huruf yang dipilih yang diunduh pengunjung. |
| Huruf judul | Sama dengan huruf teks, atau pilihan terpisah dari daftar yang sama ditambah Manrope dan Lexend. | Dipakai `--font-display` (judul besar, angka). |
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
  "portal":    { "aksen": "#2B63F6", "latar": "hangat", "hurufTeks": "sistem", "hurufJudul": "sama", "sudut": "sedang" },
  "dashboard": { "aksen": "#2B63F6", "latar": "hangat", "hurufTeks": "sistem", "hurufJudul": "sama", "sudut": "sedang" }
}
```

Semua nilai dari daftar tertutup kecuali `aksen` (hex `#RRGGBB` yang divalidasi, disimpan dengan huruf kapital). Tidak ada teks bebas yang masuk ke CSS, sehingga tidak ada celah menyisipkan CSS. `hurufJudul` `sama` berarti mengikuti huruf teks. Tema siap pakai tidak disimpan: dashboard mencocokkan pilihan dengan daftarnya ("Kustom" bila tidak cocok). `skala` ditambahkan di Irisan 3 dengan nilai bawaan 100 untuk dokumen yang sudah tersimpan (`Setting::theme()` melengkapi kunci yang belum ada dengan nilai bawaan).

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

- **Portal (keputusan pemilik): properti CSS `zoom`** pada halaman (mis. `html { zoom: 1.1 }`), didukung peramban modern. Tata letak ikut menyempit (110% di ponsel 375 px berarti ruang ±341 px), jadi setiap tingkat skala diuji di 375, 768, dan 1280 px, termasuk peta Pemetaan PKB, grafik dasbor beranda, dan layar TV (yang memakai satuan `vh`).
- **Dashboard:** ukuran huruf dasar (`html { font-size: 110% }`); Tailwind memakai `rem`, jadi seluruh dashboard ikut membesar secara proporsional. ±31 ukuran px langsung (mis. `text-[11px]`) diubah ke `rem` di Irisan 1.

### 4.6 Dashboard: tab Tampilan

- Pengaturan → tab **Tampilan** (di samping Situs, Akun, Data), dengan sub-tab **Portal** dan **Dashboard**. Formulirnya hanya bisa disimpan Administrator; pegawai lain melihatnya hanya-baca, seperti Pengaturan Situs.
- Isinya: kartu tema siap pakai, pemilih warna, nuansa latar, huruf, skala, dan sudut.
  - **Portal:** pratinjau langsung di samping formulir (tombol, kartu, lencana, panel gelap, grafik kecil, tautan) yang berubah sebelum disimpan, dan *Pratinjau di portal* (Irisan 3): membuka beranda dengan tema yang belum disimpan lewat tautan pratinjau berumur pendek, tanpa mengubah tampilan bagi pengunjung.
  - **Dashboard:** pilihan langsung diterapkan pada dashboard yang sedang dibuka sebagai pratinjau, dan kembali ke tema tersimpan bila dibatalkan.
- Seperti Pengaturan Situs (D-026), tema bukan milik modul mana pun, sehingga tidak masuk log aktivitas yang dicatat per modul. Siapa dan kapan menyimpan dicatat riwayat lima tema terakhir (Irisan 3), yang juga bisa dipulihkan.

### 4.7 Rancangan API

| Alamat | Siapa | Isi |
|---|---|---|
| `GET /api/pengaturan/tema` | semua pegawai | dokumen tema (bawaan bila belum disimpan) |
| `PUT /api/pengaturan/tema` | Administrator | dokumen lengkap; `422` untuk nilai di luar daftar atau warna tidak valid |
| `GET /api/pengaturan/tema/riwayat` | Administrator | lima tema terakhir (Irisan 3) |
| `POST /api/pengaturan/tema/pratinjau` | Administrator | tema yang belum disimpan sebagaimana akan diterapkan: variabel dan `@font-face` portal untuk contoh, aksen dan stylesheet dashboard, rasio kontras (Irisan 2) |
| `POST /api/pengaturan/tema/tautan-pratinjau` | Administrator | tautan pratinjau portal berumur pendek (Irisan 3) |
| `GET /tema-portal.css` | publik | stylesheet tema portal; `?pratinjau=` hanya dengan tautan pratinjau yang sah |
| `GET /tema-dashboard.css` | publik (isinya hanya warna dan huruf) | stylesheet tema dashboard |

---

## 5. Tahapan pengerjaan

| Irisan | Isi | Yang terlihat pemilik |
|---|---|---|
| **1. Fondasi** | Warna dan sudut yang ditulis langsung di CSS portal, komponen React portal, layar TV, dan `papan.tsx` dijadikan variabel; huruf dan merek dashboard diarahkan ke variabel; ukuran px langsung di dashboard ke `rem`; rute `tema-portal.css` dan `tema-dashboard.css` (kosong) dan `<link>`-nya di semua halaman; tes byte demi byte disesuaikan. | Belum ada perubahan tampilan (dipastikan dengan membandingkan tangkapan layar sebelum dan sesudah). |
| **2. Warna, latar, huruf, sudut** | Dokumen `tema`, `ThemePalette` (turunan dan kontras), tema siap pakai, huruf self-hosted, tab Tampilan dengan sub-tab Portal dan Dashboard, pratinjau langsung, tombol kembali ke bawaan, *Samakan dengan portal*, log aktivitas. | Tema portal dan dashboard bisa diganti dan langsung berlaku. |
| **3. Skala dan pratinjau** | Skala portal (`zoom`) dan dashboard (ukuran huruf dasar), tautan pratinjau portal sebelum disimpan, riwayat lima tema. | Ukuran tampilan bisa diatur; tema portal bisa dicoba sebelum dipakai. |
| **4. Opsional** | `meta theme-color` mengikuti latar portal; mode gelap portal. | Sesuai keputusan pemilik nanti. |

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
| Huruf | **Daftar terkurasi yang di-host sendiri** (berlisensi OFL), bukan Google Fonts. |
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
- Dibuat di Irisan 2: `app/Support/{ThemePalette,ThemeVariables}.php`, `app/Enums/{ThemeFont,ThemeBackground,ThemeCorners}.php`, `app/Actions/BuildThemeStylesheet.php`, `app/Http/Controllers/Dashboard/{ThemeController,ThemePreviewController}.php`, `app/Http/Requests/Dashboard/UpdateThemeRequest.php`, `public/assets/fonts/`, `resources/js/dashboard/lib/tema.ts`, `resources/js/dashboard/components/tema/`, `tests/Unit/Support/`, `tests/Feature/ThemeControllerTest.php`.
- Diubah: `public/assets/css/{tokens,base,components,pages}.css`, CSS komponen portal (`resources/js/portal/**/*.css`), `resources/js/dashboard/index.css`, `pages/papan.tsx`, semua halaman portal dan `errors/404`, `auth/aktivasi`, `layar`, `dashboard`, `absen` (satu `<link>`), `app/Models/Setting.php`, `routes/api.php`, `routes/web.php`, `tests/Feature/PortalPagesTest.php`, `docs/api.md`, `docs/dashboard.md`, `docs/decisions.md`, `KONTEKS-PROYEK.md`.
