# Rancangan UI/UX & Design System — PINTU

**PINTU – Portal Informasi dan Pelayanan**
UPTD Pendapatan Daerah Wilayah Kota Kupang (Samsat Kota Kupang) · Provinsi Nusa Tenggara Timur

Dokumen ini menerjemahkan *Rancangan Aktualisasi* (Elwin Musadi Bessie Sura, S.Kom · Angkatan 355) menjadi rancangan antarmuka, arsitektur informasi, dan design system. Prototipe yang bisa dijalankan ada di root repositori (`index.html` dan halaman lainnya).

---

## 1. Ringkasan

| Hal | Keputusan |
|---|---|
| Masalah utama | Belum ada website profil dan pelayanan informasi. Informasi persyaratan masih ditempel di kertas pada meja dan kaca loket, tersebar di berbagai kanal, dan mudah rusak. |
| Gagasan | Satu portal resmi yang terintegrasi, terverifikasi, dan mudah diakses: **PINTU**. |
| Bahasa desain | Editorial tenang ala referensi *TinyKPI* (Kage): kertas hangat, SF Pro Display semibold untuk headline dua nada, SF Pro Text untuk isi, satu aksen biru, mockup gelap hanya di dalam media. |
| Cakupan prototipe | 15 halaman publik + login pegawai + dasbor ruang pegawai + halaman design system. |
| Prinsip konten | Tugas lebih dulu (cek persyaratan), bahasa baku yang singkat, setiap informasi bertanggal dan bersumber. |

---

## 2. Analisa Rancangan Aktualisasi

### 2.1 Isu dan prioritas (uji APKL)

| No | Isu | Skor | Peringkat | Respons di PINTU |
|---|---|---|---|---|
| 1 | Belum tersedia website profil dan pelayanan informasi | 20 | **1** | Seluruh portal publik |
| 3 | Belum ada aplikasi internal perhitungan PKB, khususnya masa amnesti | 19 | 2 | **Simulasi PKB** (publik) dan **Kalkulator PKB amnesti** (ruang pegawai) |
| 2 | Belum ada absensi online berbasis foto dan lokasi untuk kegiatan lapangan | 17 | 3 | Modul **Absensi lapangan** (foto + GPS) di ruang pegawai |

Isu peringkat 2 dan 3 tidak dikerjakan penuh dalam aktualisasi, tetapi ruang antarmukanya sudah disiapkan agar portal bisa tumbuh tanpa dirombak.

### 2.2 Penyebab (fishbone) → respons desain

| Kategori | Penyebab dalam Rancangan | Respons desain/fitur |
|---|---|---|
| Environment / tata kelola | Kebutuhan portal belum masuk tata kelola; peran penyedia, pemeriksa, dan pengelola publikasi belum jelas; monitoring pemutakhiran belum sistematis | Empat peran dan alur publikasi ditampilkan di halaman *Struktur Organisasi*, *Login*, dan papan **Alur publikasi** (kanban) di ruang pegawai. Tabel **Jadwal pemutakhiran konten** dengan status jatuh tempo. |
| Material / konten | Informasi tersebar; belum ada basis konten terstruktur untuk profil, layanan, jadwal, lokasi, berita, pengumuman; dokumentasi belum dihimpun | Arsitektur informasi lima menu (Profil, Layanan, Jadwal, Informasi, Kontak). Data persyaratan disalin dari papan loket menjadi tabel terstruktur. Halaman *Dokumentasi Kegiatan*. |
| Money / modal | Belum ada anggaran khusus | Situs statis tanpa dependensi berbayar: HTML/CSS/JS murni, font sistem (SF Pro di perangkat Apple) tanpa berkas font yang perlu di-hosting, bisa di-hosting gratis (mis. GitHub Pages) atau di server Pemprov. |
| Method / prosedur | Belum ada alur baku pengumpulan, verifikasi, persetujuan, publikasi; belum ada standar frekuensi dan klasifikasi | Alur 4 langkah dan standar frekuensi dijadikan bagian UI (lihat §9). Metadata "Diterbitkan / Diperbarui / Diverifikasi oleh" di setiap artikel. |
| Machine / teknologi | Belum ada portal resmi; belum ada pengelolaan konten terpusat; belum terintegrasi dengan kanal lain | Portal + ruang pegawai sebagai CMS. Peta **Kanal layanan** menautkan media sosial, WhatsApp, SP4N-LAPOR!, QRIS, SIGNAL. |
| Manusia / SDM | Belum ada penanggung jawab konten; kemampuan pengelolaan konten digital belum seragam; publikasi bergantung pada pegawai tertentu | Antarmuka admin sederhana (kanban, tabel jatuh tempo), peran yang tertulis, dan design system yang terdokumentasi agar siapa pun bisa meneruskan. |

### 2.3 Dampak yang ingin dicegah → indikator yang bisa diukur

| Dampak bila tidak ditangani | Indikator di PINTU |
|---|---|
| Wajib pajak sulit memperoleh informasi cepat dan lengkap | Kunjungan halaman persyaratan; pemakaian checklist dan cetak |
| Informasi tidak seragam | Satu sumber data persyaratan untuk portal dan papan loket |
| Pertanyaan ke petugas meningkat | Jumlah pertanyaan di formulir masukan per kategori |
| Kualitas dan kecepatan informasi menurun | Status "Terkini / Jatuh tempo" pada tabel pemutakhiran |
| Kepercayaan masyarakat turun | Pernyataan integritas, biaya transparan, kanal pengaduan, Indeks Kepuasan |

---

## 3. Pengguna dan kebutuhannya

| Pengguna | Kebutuhan utama | Jawaban di portal |
|---|---|---|
| Wajib pajak umum | "Berkas apa yang harus saya bawa?" | CTA hero → *Layanan & Persyaratan*; checklist interaktif |
| Wajib pajak yang bekerja | "Kapan loket buka? Ada Samsat Keliling dekat rumah?" | Status loket langsung (WITA) + hitung mundur; jadwal keliling mingguan |
| Pengguna pertama kali / lansia | Bahasa sederhana, huruf besar, kontras tinggi | Body 16px, kontras ≥ 4,5:1, tanpa jargon, tombol besar (min 48px) |
| Perwakilan / biro / badan hukum | Persyaratan tambahan, surat kuasa | Tabel tambahan badan hukum; unduhan formulir |
| Warga yang ingin mengadu | Kanal resmi dan aman | Formulir masukan + SP4N-LAPOR! + Ombudsman |
| Petugas loket | Rujukan cepat, kalkulator | Kalkulator PKB amnesti, persyaratan cetak |
| Operator / pengelola publikasi | Tahu apa yang harus diperbarui | Kanban alur publikasi, tabel jatuh tempo |
| Kepala UPTD / atasan langsung | Menyetujui konten | Kolom "Persetujuan" dengan penanda lama tunggu |

---

## 4. Arsitektur informasi (sitemap)

```
PINTU
├── Beranda (index.html) ─ landing profil + layanan
├── Profil
│   ├── Tentang UPTD (profil.html)
│   ├── Visi & Misi + Nilai BerAKHLAK + Maklumat (visi-misi.html)
│   └── Struktur Organisasi + Uraian tugas + Peran pengelola informasi (struktur-organisasi.html)
├── Layanan
│   ├── Persyaratan 5 layanan (layanan.html)
│   ├── Alur non-tunai (layanan.html#alur)
│   └── Simulasi PKB (layanan.html#simulasi)
├── Jadwal
│   ├── Jam pelayanan + status langsung (jadwal.html)
│   └── Samsat Keliling mingguan (jadwal.html#keliling)
├── Informasi
│   ├── Berita & Pengumuman (informasi.html) → Detail (berita-detail.html)
│   ├── Dokumentasi Kegiatan (dokumentasi.html)
│   └── Regulasi & Unduhan (unduhan.html)
├── Kontak & Pengaduan (kontak.html)
├── Ruang Pegawai
│   ├── Login Pegawai (login.html)
│   └── Ringkasan / dasbor (dashboard-pegawai.html)
│       ├── Alur persetujuan (kanban)
│       ├── Konten: berita, persyaratan, jadwal, dokumentasi, unduhan
│       ├── Alat: absensi lapangan, kalkulator PKB, masukan publik
│       └── Administrasi: pengguna & peran, log aktivitas
├── Kebijakan Privasi · Syarat & Ketentuan · 404
└── Design System (design-system.html)
```

Navigasi utama dibatasi **lima** tautan (Profil, Layanan, Jadwal, Informasi, Kontak & Pengaduan) sesuai pola navbar pil referensi; turunan muncul sebagai dropdown gelap. Lebar navbar mengikuti isinya. Akses pegawai dibuat ringkas: ikon **Login Pegawai** di navbar (berlabel untuk pembaca layar dan tooltip), tombol di menu seluler, dan tautan di footer. Navbar desktop tampil mulai 1001px; di bawahnya dipakai menu seluler.

---

## 5. Peta halaman

| Halaman | Satu ide utama | Komponen kunci |
|---|---|---|
| Beranda | "Semua informasi Samsat. Lewat satu pintu." | 14 seksi: hero, papan layanan, 3 fitur, tab layanan, mitra, dasbor transparansi, peta kanal, simulasi bento, integritas, biaya Rp0 + hitung mundur, FAQ, penutup, footer |
| Tentang UPTD | Kedudukan dan tugas | Fakta (dl), daftar tugas bernomor, sambutan, kartu lanjutan |
| Visi & Misi | Arah provinsi → layanan | Visi raksasa dua nada, 5 misi + kata kunci, kontribusi PINTU, grid BerAKHLAK, maklumat |
| Struktur Organisasi | Siapa mengerjakan apa | Bagan CSS responsif, akordeon uraian tugas, 4 peran pengelola informasi |
| Layanan & Persyaratan | Siapkan berkas dari rumah | Ringkasan standar, navigasi samping (scroll-spy), 5 kartu tabel berkas, alur non-tunai, simulasi |
| Jadwal | Cek sebelum berangkat | Kartu status langsung + hitung mundur, tabel jam dengan penanda hari ini, jadwal keliling mingguan otomatis |
| Berita & Pengumuman | Kabar resmi bertanggal | Pengumuman tersemat, berita utama, filter + pencarian, arsip pengumuman |
| Detail berita | Satu artikel | Metadata verifikasi (diterbitkan, diperbarui, diverifikasi oleh), bagikan, baca juga |
| Dokumentasi | Kerja lapangan tercatat | Galeri bento, filter, lightbox dengan navigasi keyboard |
| Regulasi & Unduhan | Dasar hukum terbuka | Daftar dokumen dengan filter & pencarian |
| Kontak & Pengaduan | Tanya dan mengadu | Daftar kontak, peta, formulir masukan, 3 kanal pengaduan |
| Login pegawai | Akses internal aman | Split editorial + kartu formulir NIP, tampilkan sandi, SSO, catatan keamanan |
| Ruang pegawai | Apa yang perlu saya kerjakan hari ini | KPI, kanban publikasi, jadwal pemutakhiran, absensi lapangan, kalkulator, masukan publik, aktivitas |

---

## 6. Design system

Sumber kebenaran: `assets/css/tokens.css`. Panduan visual hidup: `design-system.html`.

### 6.1 Warna

| Token | Nilai | Fungsi |
|---|---|---|
| `--paper` | `#F7F6F2` | Latar halaman (hangat) |
| `--paper-2` | `#EFEDE6` | Kartu media, kanvas bento |
| `--surface` | `#FFFFFF` | Kartu putih, formulir |
| `--ink` | `#0F1319` | Teks utama, tombol hitam, navbar (17,2:1) |
| `--text-2` | `#646A72` | Teks pendukung (5,05:1) |
| `--gray` | `#82878E` | Nada kedua headline, hanya ≥ 24px (3,35:1 di kertas; 3,09:1 di kartu krem) |
| `--line` | `#E7E4DC` | Garis rambut |
| `--accent` | `#2B63F6` | **Hanya** tautan, status aktif, garis bawah (4,6:1) |
| `--night` / `--night-2` | `#0A0A0C` / `#17181B` | Panel dan tile mockup gelap |
| `--ok` / `--warn` / `--danger` | `#1A7349` / `#8C5900` / `#B93A28` | Status, selalu dengan ikon + label (≥ 4,6:1 di atas latar tag) |

### 6.2 Tipografi

- **SF Pro Display** (semibold 600) untuk headline dan angka ≥ 20px: hero `clamp(36px, 4.6vw + 22px, 80px)`, lh 1.05, tracking −0.022em; H1 halaman 36–64px; H2 30–48px; H3 24–28px; angka metrik 28–40px.
- **SF Pro Text** untuk teks, UI, dan label di bawah 20px.
- **Angka tabular** (`font-variant-numeric: tabular-nums`) dengan SF Pro Text untuk nomor tab (01/02/03), jam, dan hitung mundur kecil; hitung mundur besar (≥ 20px) memakai SF Pro Display. Tidak ada font monospace.
- Semua headline memakai `text-wrap: balance`. Angka dan satuannya dipisah spasi tak-putus (`5&nbsp;tahun`). Angka di simulasi mengecil otomatis bila tidak muat di tile (minimum 14px); bila tetap tidak muat, tile kecil memakai format ringkas (mis. `Rp660 jt`) dengan angka lengkap di rincian dan tooltip.
- SF Pro adalah font sistem Apple dan **tidak di-host** di situs (lisensi Apple tidak mengizinkan distribusi web). Urutan font: `"SF Pro Display"/"SF Pro Text", -apple-system, BlinkMacSystemFont, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`. Artinya perangkat Apple menampilkan SF Pro, Windows menampilkan Segoe UI, dan Android menampilkan Roboto.
- Body 16px / 1.6, lebar maks. ±38ch. Eyebrow 11px uppercase, tracking 0.12em.
- **Aturan dua nada**: kalimat pertama tinta, lanjutan abu-abu (`<span class="tone">`). Semua headline berupa kalimat lengkap dan diakhiri titik.

### 6.3 Spasi, sudut, bayangan

- Basis 4px. Jarak antar-seksi `clamp(96px, 12vw, 160px)`; di dalam seksi 64px.
- Radius: pil 999 (nav, tombol, notch), 32 (media/kartu seksi), 24 (kartu), 16 (tile), 12 (input).
- Bayangan jarang dan lembut; pemisahan memakai garis rambut dan kontras terang/gelap.

### 6.4 Logo & lambang

- **Logo utama:** Lambang Provinsi Nusa Tenggara Timur (`assets/img/lambang-ntt.png`, PNG transparan 1020×1081; turunan web `lambang-ntt-96/192/360.webp`; favicon `favicon-32.png`, `icon-192.png`, `apple-touch-icon.png`).
- **Lockup:** lambang + teks "PINTU / Samsat Kota Kupang" (navbar) atau "PINTU / Portal Informasi dan Pelayanan / UPTD …" (footer). Lambang dan teks tidak dilebur menjadi satu gambar.
- **Ukuran:** hero 92–116px, footer 44px, navbar 31px, ikon kanal 26px, papan layanan 24px (minimum). Lambang tidak diletakkan di dalam bentuk lain dan tidak dianimasikan. Atur tinggi; lebar mengikuti proporsi asli.
- **Larangan:** mengubah warna, memotong, memutar, meregangkan, menambah bayangan/efek, atau menaruhnya di atas foto ramai.
- **Logo cadangan PINTU** (kusen pintu + daun pintu terbuka) tidak dipakai saat ini, tetapi disimpan: simbol `#logo-pintu` di `partials/icons.html`, berkas `assets/img/logo-pintu.svg`, dan `assets/img/favicon.svg`. Cara memasangnya kembali ada sebagai komentar di `partials/header.html`.

### 6.5 Komponen

Navbar pil mengambang + dropdown gelap + ikon Login Pegawai · tombol (dark, light, ghost, night; 48/38/30px) · tautan garis bawah animasi · eyebrow · headline dua nada · media + baris keterangan · strip tiga fitur · tab bernomor · daftar kategori aktif · lencana mitra · chip filter · tag status · akordeon · tile metrik + sparkline · baris filter waktu · pil notch · kanvas bento · kartu biaya · formulir (input, select, textarea, sakelar, radio-chip) · tabel · remah roti · placeholder foto · dialog/lightbox · toast · bagan organisasi · kanban.

### 6.6 Gerak

`--dur-1` 160ms (hover), `--dur-2` 240ms (dropdown, akordeon), `--dur-3` 600ms (fade-up, garis tab). Easing `cubic-bezier(.2,.7,.2,1)`. Tanpa paralaks. Semua dimatikan pada `prefers-reduced-motion`.

### 6.7 Deviasi sadar dari referensi

| Referensi | PINTU | Alasan |
|---|---|---|
| Serif editorial kontras tinggi + grotesk netral | SF Pro Display + SF Pro Text (font sistem) | Permintaan pengguna. Karakter editorial dipertahankan lewat ukuran besar, bobot semibold, tracking rapat, dan aturan dua nada. |
| Semua teks pendukung `#8A8F96` | Teks kecil memakai `#646A72`; nada kedua headline memakai `#82878E` dan hanya untuk teks ≥ 24px | `#8A8F96` di atas kertas hanya 3,0:1 (dan 2,8:1 di kartu krem) — gagal WCAG AA. Situs pemerintah wajib mudah dibaca semua warga. |
| Hero minimal 56px | Minimal 36px di layar sempit (skala cair `4.6vw + 22px`) | Kata bahasa Indonesia lebih panjang; headline tetap tiga baris di 320px dan tanpa gulir horizontal. |
| Angka metrik tabular | Proporsional untuk angka besar, tabular untuk kolom | Angka besar tabular terlihat renggang; kolom tetap rata. |
| Tidak ada warna status | Hijau/kuning/merah dengan ikon + label | Dibutuhkan untuk status loket, jatuh tempo, cek fisik. |
| Maskot | **Lambang Provinsi NTT** sebagai logo utama (navbar, hero, footer, login, ruang pegawai, favicon). Logo PINTU disimpan sebagai logo cadangan. | Instansi pemerintah memakai lambang resmi; nama portal PINTU cukup ditulis sebagai teks di sebelah lambang. |

---

## 7. Adaptasi struktur referensi (TinyKPI → PINTU)

| # | Seksi referensi | Adaptasi PINTU |
|---|---|---|
| 1 | Navbar pil gelap | Lambang + "PINTU / Samsat Kota Kupang", 5 menu (termasuk "Kontak & Pengaduan"), ikon Login Pegawai, dan "Cek Persyaratan"; lebar mengikuti isi |
| 2 | Hero maskot | Lambang Provinsi NTT di tengah, eyebrow nama UPTD, "Semua informasi Samsat. *Lewat satu pintu.*", CTA hitam, mikrokopi gembok "Gratis dan tanpa perantara." |
| 3 | Media produk + caption | **Papan layanan hari ini** (antrean, waktu tunggu, Samsat Keliling, pengumuman, status loket) + tombol video profil |
| 4 | Strip tiga fitur | "Persyaratan jelas." · "Jadwal terbaru." · "Tanpa perantara." |
| 5 | Views 01/02/03 | Layanan utama 01 Pajak tahunan / 02 Perpanjangan 5 tahun / 03 Mutasi; kartu berisi checklist berkas interaktif |
| 6 | Teaser integrasi | Lencana mitra satu atap: BPAD NTT, Polri, Jasa Raharja, Bank NTT, QRIS, SIGNAL |
| 7 | "Your tools. One view." | **Transparansi layanan**: 4 kategori data + dasbor gelap dengan filter waktu |
| 8 | Peta integrasi | **Kanal layanan**: pil notch (status loket, jumlah titik keliling) → 5 kelompok kanal |
| 9 | Bento playground | **Simulasi PKB** dalam kanvas bento (pokok, jenis, terlambat, opsen, denda, total, pemutihan, SWDKLLJ) |
| 10 | Pernyataan privasi | **Integritas**: "Bayar sesuai notis. *Tidak lebih sepeser pun.*" |
| 11 | Pricing | **Biaya**: cek fisik Rp0 + hitung mundur jam loket langsung |
| 12 | FAQ | "Perlu diketahui. *Sebelum ke loket.*" 7 pertanyaan |
| 13 | Outro jenaka | "Map apa saja boleh. *Asal jangan lupa dibawa.*" + kontrol "Cek lagi" |
| 14 | Footer | Logo, alamat, jam loket, kontak, 5 tautan, kembali ke atas, © + Login Pegawai + kebijakan |

Tidak ada nama, logo, salinan, harga, atau tangkapan layar TinyKPI yang dipakai.

---

## 8. Referensi portal pemerintah

| Referensi | Pola yang diadopsi |
|---|---|
| GOV.UK | Tugas lebih dulu (CTA utama langsung ke persyaratan), bahasa sederhana, tanggal "terakhir diperbarui" |
| gov.sg / Singapore Government Design System | Penanda situs resmi; diwujudkan lewat lambang daerah di navbar dan footer (bilah masthead terpisah dihapus agar header lebih ringkas) |
| IRAS (otoritas pajak Singapura) | Portal pajak yang menekankan aksesibilitas (Best Accessibility Award, GovTech Digital Services Awards 2024) → target WCAG 2.2 AA |
| Pemerintah Belanda (DICTU, Splash Awards 2025) | Situs dasar berbasis templat yang memenuhi pedoman aksesibilitas dan konten → design system + partial header/footer |
| Ombudsman RI Perwakilan NTT | Catatan bahwa gesek nomor rangka/mesin di Samsat Kota Kupang tidak dipungut biaya → kartu "Rp0", pernyataan integritas, kanal pengaduan |
| Praktik kanal non-tunai Samsat NTT (QRIS) | Kanal digital pada peta kanal dan alur non-tunai (perlu verifikasi kanal yang berlaku saat ini) |

---

## 9. Tata kelola konten (dari Rencana Kegiatan 1–7)

### 9.1 Alur publikasi

1. **Penyedia** (unit/petugas pemilik data) menyiapkan bahan.
2. **Pemeriksa** (Kepala Seksi/Subbag) mencocokkan dengan sumber resmi.
3. **Penyetuju** (atasan langsung/Kepala UPTD) memberi izin terbit.
4. **Pengelola publikasi** (operator/Penata Kelola Sistem & TI) menerbitkan, mencatat tanggal, dan memantau masukan.

### 9.2 Klasifikasi dan frekuensi pemutakhiran (usulan)

| Konten | Sifat | Pemilik | Frekuensi |
|---|---|---|---|
| Jadwal Samsat Keliling | Dinamis | Seksi Penagihan | Mingguan, setiap Jumat |
| Jam pelayanan & perubahan jadwal | Dinamis | Sub Bagian TU | Bulanan + paling lambat H-3 bila berubah |
| Pengumuman | Dinamis | Semua unit | Saat ada kebijakan baru |
| Berita | Semi-dinamis | Pengelola publikasi | Minimal 2 per bulan |
| Dokumentasi kegiatan | Semi-dinamis | Semua unit | Setiap kegiatan, maks. 3 hari kerja |
| Persyaratan layanan | Statis | Seksi Pendataan | Saat regulasi berubah |
| Profil, visi-misi, struktur | Statis | Sub Bagian TU | Saat ada perubahan pejabat/regulasi |
| Regulasi & unduhan | Statis | Sub Bagian TU | Saat ada regulasi baru |

Informasi kedinasan yang tidak untuk publik (data wajib pajak, NIP lengkap, dokumen internal) **tidak** ditampilkan di portal publik.

---

## 10. Inventaris konten yang wajib diverifikasi sebelum terbit

Semua elemen bertanda titik kuning "proto-note" di prototipe adalah contoh. Daftar yang harus diisi atau dicek petugas:

- [ ] Alamat kantor, telepon, WhatsApp, surel, akun media sosial
- [ ] Jam loket per hari dan batas penerimaan berkas
- [ ] Lokasi dan jadwal Samsat Keliling (data rotasi di `assets/js/main.js` → `CONFIG.keliling`)
- [ ] Nama seksi dan pejabat sesuai Pergub NTT yang berlaku; foto Kepala UPTD
- [ ] Parameter simulasi: opsen PKB, denda per bulan, batas bulan, SWDKLLJ (`CONFIG.sim`)
- [ ] Nomor loket per layanan
- [ ] Kanal pembayaran non-tunai yang berlaku (QRIS, bank mitra, SIGNAL)
- [ ] Dokumen regulasi, standar pelayanan, maklumat, SOP, formulir (berkas & tautan JDIH)
- [ ] Angka dasbor transparansi (saat ini data ilustrasi)
- [ ] Foto gedung, kegiatan, dan video profil (saat ini placeholder ilustrasi)
- [ ] Kebijakan privasi dan syarat & ketentuan (draf, perlu tinjauan hukum)
- [x] Lambang Provinsi NTT resolusi tinggi (PNG transparan 1020×1081, diberikan pengguna). Versi SVG resmi dari Biro Organisasi/BPAD tetap disarankan bila tersedia.

Data persyaratan lima layanan diambil dari foto papan persyaratan loket dalam dokumen Rancangan.

---

## 11. Implementasi teknis

```
/
├── index.html, profil.html, … (halaman siap buka, tanpa build)
├── assets/
│   ├── css/tokens.css      ← token design system
│   ├── css/base.css        ← reset, tipografi, tata letak
│   ├── css/components.css  ← komponen
│   ├── css/pages.css       ← pola halaman & ruang pegawai
│   ├── js/main.js          ← interaksi + CONFIG konten dinamis
│   └── img/                ← lambang NTT (logo utama), favicon, logo cadangan PINTU
├── partials/               ← header, footer, sprite ikon (sumber tunggal)
├── scripts/sync-partials.mjs
└── docs/RANCANGAN-UIUX.md
```

- **Mengubah header/footer/ikon:** edit `partials/*.html`, lalu jalankan `node scripts/sync-partials.mjs`. Skrip menyalin isi partial ke semua halaman dan menandai menu aktif dari atribut `data-page`/`data-group` pada `<body>`.
- **Menjalankan lokal:** buka `index.html` langsung, atau `npx http-server .` lalu kunjungi `http://localhost:8080`.
- **Status loket** dihitung dari zona waktu `Asia/Makassar` (WITA), jadi tetap benar meski pengunjung berada di zona lain.
- **Tanpa JavaScript** semua konten tetap terbaca (akordeon memakai `<details>`, navigasi berupa tautan biasa).
- **Tahap berikut (pasca-aktualisasi):** sambungkan ruang pegawai ke backend/CMS (mis. Laravel atau headless CMS) dengan otentikasi NIP/SSO, peran berbasis 4 peran di atas, log audit, dan penyimpanan foto absensi dengan koordinat.

### Checklist aksesibilitas

- Kontras teks ≥ 4,5:1 (teks kecil) dan ≥ 3:1 (headline besar)
- Fokus terlihat (`outline` biru 2px) pada semua kontrol
- Tab, radio, dan galeri dapat dioperasikan dengan papan ketik (panah, Home/End, Esc)
- Tautan "Lewati ke konten utama"
- `prefers-reduced-motion` dihormati
- Lebar 360–1440px tanpa gulir horizontal (diuji dengan Playwright)
- Tampilan cetak untuk halaman persyaratan

---

## 12. Keterkaitan dengan jadwal aktualisasi

| Kegiatan Rancangan | Minggu | Artefak di repositori |
|---|---|---|
| 1. Konsultasi kebutuhan, peran, alur | Okt I–II | §3, §9.1 |
| 2. Inventarisasi & verifikasi konten | Okt II | §10, data persyaratan di `layanan.html` |
| 3. Alur/prosedur pengelolaan informasi | Okt III | §9, halaman Struktur Organisasi, kanban ruang pegawai |
| 4. Pengembangan & pengisian website | Okt III–IV | Seluruh halaman + design system |
| 5. Uji coba, sosialisasi, evaluasi | Nov I–II | Formulir masukan, tabel pemutakhiran |
| 6. Implementasi & operasionalisasi | Nov III–IV | Deploy, pengisian data resmi |
| 7. Evaluasi & monitoring | Nov IV | Dasbor masukan publik, log aktivitas |
