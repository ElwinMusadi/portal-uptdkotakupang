# Dashboard PINTU

Dashboard pengelolaan konten portal **PINTU** (UPTD Pendapatan Daerah Wilayah Kota Kupang). Semua informasi yang tampil di portal publik (berita, persyaratan layanan, jadwal, profil, kontak, dan lainnya) dikelola dari sini, lengkap dengan alur publikasi draf → diperiksa → persetujuan → terbit.

Tata letaknya mengikuti template [shadcn/ui *dashboard-01*](https://ui.shadcn.com/blocks#dashboard-01): sidebar, kartu KPI, grafik area interaktif, dan tabel data. Warna dan tipografinya disesuaikan dengan design system portal.

> **Status prototipe.** Login memakai satu akun statis yang diperiksa di peramban, dan data tersimpan di peramban (IndexedDB). Sebelum dipakai publik, sambungkan ke server dan pindahkan login ke server. Langkahnya ada di bagian [Menyambungkan ke server](#menyambungkan-ke-server-rest-api) dan [Mengganti login statis](#mengganti-login-statis).

## Menjalankan

Perlu Node.js 20.19+ atau 22.12+.

```bash
cd dashboard-app
npm install
npm run dev
```

Buka `http://localhost:5173/login.html`, lalu masuk dengan akun prototipe:

| NIP | Kata sandi |
|---|---|
| `199707162026061002` | `password123` |

Server pengembangan juga menyajikan halaman portal dari folder induk, jadi alur **Login Pegawai → Dashboard** bisa dicoba persis seperti di server. Dashboard sendiri ada di `http://localhost:5173/dashboard/`.

| Perintah | Fungsi |
|---|---|
| `npm run dev` | Server pengembangan dengan muat ulang otomatis |
| `npm run build` | Periksa tipe lalu build ke `../dashboard/` |
| `npm run typecheck` | Periksa tipe TypeScript |
| `npm run lint` | Periksa kode dengan oxlint |
| `npm run preview` | Pratinjau hasil build |

### Build

`npm run build` menulis hasil ke folder `dashboard/` di root repo. Folder itu ikut di-commit supaya portal tetap bisa di-host sebagai situs statis tanpa langkah build. **Setelah mengubah kode di `src/`, jalankan build lalu commit `dashboard/` juga.**

Hasil build memakai path relatif, jadi bisa ditaruh di subfolder mana pun selama letaknya bersebelahan dengan halaman portal (`../login.html`). Dashboard memakai modul JavaScript, sehingga harus dibuka lewat server (`npx http-server .` dari root repo), bukan langsung dari berkas.

## Teknologi

| Bagian | Pustaka |
|---|---|
| Kerangka | Vite, React 19, TypeScript |
| Tampilan | Tailwind CSS 4, shadcn/ui (Radix UI), Tabler Icons |
| Data | TanStack Query, IndexedDB lewat idb-keyval, adapter REST |
| Tabel | TanStack Table (cari, saring, urut, pilih massal, seret untuk mengurutkan dengan dnd-kit) |
| Formulir | React Hook Form + Zod, Tiptap (teks kaya), react-day-picker (kalender) |
| Lainnya | React Router (hash), Recharts, sonner (notifikasi), cmdk (pencarian cepat) |

## Struktur folder

```
dashboard-app/
├── index.html            ← halaman induk (tema terang/gelap dipasang sebelum React dimuat)
├── public/brand/         ← lambang NTT dan favicon
├── vite.config.ts        ← base /dashboard/, keluaran ../dashboard, penyaji portal saat dev
└── src/
    ├── main.tsx          ← cek sesi, lalu pasang provider dan router
    ├── index.css         ← token warna shadcn/ui yang diselaraskan dengan portal
    ├── app/              ← layout, router (rute lazy), daftar menu
    ├── components/
    │   ├── ui/           ← komponen shadcn/ui (teks bawaan sudah berbahasa Indonesia)
    │   ├── data/         ← tabel data, lencana status, dialog konfirmasi, kerangka halaman
    │   ├── form/         ← isian formulir, unggah foto/berkas, editor teks kaya, panel sunting
    │   └── alur/         ← aksi dan riwayat alur publikasi
    ├── lib/
    │   ├── api/          ← model data (types.ts), klien lokal & REST, data awal (seed.ts)
    │   ├── auth.ts       ← membaca sesi yang ditulis halaman Login Pegawai
    │   ├── queries.ts    ← hook TanStack Query untuk baca/tulis data
    │   ├── meta.ts       ← label, status alur, kategori
    │   └── …             ← format tanggal & rupiah (WITA), validasi, jadwal, rumus pajak
    └── pages/            ← satu berkas per modul
```

## Modul

| Menu | Rute | Data | Tampil di portal |
|---|---|---|---|
| Ringkasan | `#/` | KPI, grafik pesan masuk, antrean kerja, aktivitas | — |
| Alur Publikasi | `#/alur` | semua konten beralur | — |
| Pesan & Pengaduan | `#/pesan` | koleksi `pesan` | `kontak.html#masukan` |
| Statistik Layanan | `#/statistik` | pengaturan `statistik` | `index.html#transparansi` |
| Berita & Pengumuman | `#/berita` | koleksi `berita` | `informasi.html`, `berita-detail.html` |
| Dokumentasi Kegiatan | `#/dokumentasi` | koleksi `dokumentasi` | `dokumentasi.html` |
| Regulasi & Unduhan | `#/unduhan` | koleksi `dokumen` | `unduhan.html` |
| Tanya Jawab | `#/faq` | koleksi `faq` | `index.html#faq` |
| Persyaratan Layanan | `#/layanan` | koleksi `layanan` | `layanan.html` |
| Jam Pelayanan | `#/jadwal/jam` | pengaturan `jam`, koleksi `penyesuaian` | `jadwal.html` |
| Samsat Keliling | `#/jadwal/keliling` | koleksi `keliling` | `jadwal.html#keliling` |
| Papan Layanan | `#/papan` | pengaturan `papan` | `index.html` |
| Tarif & Simulasi | `#/tarif` | pengaturan `tarif` | `layanan.html#simulasi` |
| Tentang UPTD | `#/profil` | pengaturan `profil` | `profil.html` |
| Visi & Misi | `#/visi-misi` | pengaturan `visiMisi` | `visi-misi.html` |
| Struktur Organisasi | `#/struktur` | koleksi `unit` | `struktur-organisasi.html` |
| Kontak & Kanal | `#/kontak` | pengaturan `kontak` | `kontak.html` |
| Beranda | `#/beranda` | pengaturan `beranda` | `index.html` |
| Halaman Statis | `#/halaman` | koleksi `halaman` | `kebijakan-privasi.html`, `syarat-ketentuan.html` |
| Jadwal Pemutakhiran | `#/pemutakhiran` | koleksi `pemutakhiran` | — (internal) |
| Absensi Lapangan | `#/absensi` | koleksi `absensi` | — (internal) |
| Kalkulator PKB | `#/kalkulator` | pengaturan `tarif` | — (internal) |
| Pengaturan | `#/pengaturan` | pengaturan `situs`, `akun`, cadangan data | — |

Formulir sunting membuka panel samping dengan status di URL (`?baru=1` atau `?ubah=<id>`), jadi tautannya bisa dibagikan. Perubahan yang belum disimpan dijaga saat pindah halaman atau menutup tab. Tekan `Ctrl K` (`⌘K` di Mac) untuk mencari halaman dan konten.

### Alur publikasi

Berita, dokumentasi, dokumen unduhan, dan persyaratan layanan memiliki kolom `status`:

```
draf → diperiksa → persetujuan → terbit → arsip
```

- Pemeriksa dan penyetuju bisa **mengembalikan** konten dengan catatan revisi (`dikembalikan`), lalu penyedia mengajukannya ulang ke `diperiksa`.
- Konten `terbit` bisa ditarik dari portal ke `arsip`, dan arsip bisa dipulihkan ke `draf`.

Peran tiap tahap mengikuti rancangan: **penyedia** (unit pemilik data), **pemeriksa** (kepala seksi/subbag), **penyetuju** (atasan langsung), dan **pengelola publikasi**. Setiap langkah dicatat di `riwayat` entri dan di log aktivitas. Halaman satu-dokumen (profil, kontak, jam, dan sejenisnya) langsung berlaku saat disimpan.

### Alat bantu

- **Absensi Lapangan** merekam foto (dikecilkan menjadi maks. 1024 px) dan titik GPS saat petugas tiba atau selesai di lokasi kegiatan. Kamera dan lokasi hanya bisa dipakai lewat HTTPS atau `localhost`. Bila izin lokasi ditolak, keterangan tempat wajib diisi. Catatan absensi tidak bisa diubah, hanya dihapus.
- **Kalkulator PKB** memakai rumus yang sama dengan simulasi di portal (`assets/js/main.js`), ditambah sakelar amnesti yang menghapus denda keterlambatan.

## Model data

Sumber kebenarannya `src/lib/api/types.ts`. Ada dua bentuk data:

- **Koleksi**: daftar entri. Setiap entri punya `id`, `dibuat`, `diperbarui` (ISO 8601), dan `urutan`. Konten beralur menambahkan `status`, `penyedia`, `pemeriksa`, `catatanRevisi`, `terbitPada`, dan `riwayat`.
  Nama koleksi: `berita`, `dokumentasi`, `dokumen`, `layanan`, `faq`, `keliling`, `penyesuaian`, `unit`, `pesan`, `halaman`, `pemutakhiran`, `absensi`.
- **Pengaturan**: satu dokumen per halaman.
  Nama pengaturan: `situs`, `beranda`, `papan`, `jam`, `tarif`, `statistik`, `profil`, `visiMisi`, `kontak`, `akun`.

Ketentuan format: tanggal kalender `YYYY-MM-DD`, jam `HH:MM` (24 jam, WITA), isi teks kaya berupa HTML sederhana (paragraf, subjudul h2/h3, daftar, kutipan, tautan), dan berkas berbentuk `{ nama, ukuran, tipe, url }`.

Contoh entri `faq`:

```json
{
  "id": "3f2a9c1e7b6d4a10",
  "dibuat": "2026-09-01T01:00:00.000Z",
  "diperbarui": "2026-10-02T03:15:00.000Z",
  "urutan": 0,
  "pertanyaan": "Apakah pajak tahunan bisa diwakilkan?",
  "jawaban": "Bisa, dengan membawa KTP pemilik dan surat kuasa bermeterai.",
  "tampil": true
}
```

## Penyimpanan data

Tanpa `VITE_API_URL`, dashboard berjalan dalam **mode lokal**:

- Data tersimpan di IndexedDB peramban: database `pintu-dashboard`, store `data`, dengan kunci `koleksi:<nama>`, `pengaturan:<nama>`, `aktivitas`, dan `meta`.
- Saat pertama dibuka, dashboard mengisi data awal dari isi portal yang sudah ada (`src/lib/api/seed.ts`).
- Foto dan berkas disimpan sebagai data URL. Foto dikecilkan dulu menjadi maks. 1600 px.
- Bila IndexedDB tidak tersedia (mis. mode privat tertentu), data hanya tersimpan di memori dan dashboard menampilkan peringatan.
- Data hanya ada di peramban itu dan **belum tampil di portal publik**.

**Cadangan.** Di *Pengaturan › Data*, unduh seluruh data sebagai JSON atau pulihkan dari berkas cadangan. Berkas yang sama bisa dipakai sebagai data awal server:

```json
{
  "versi": 1,
  "diekspor": "2026-10-10T02:00:00.000Z",
  "koleksi": { "berita": [], "dokumentasi": [], "…": [], "absensi": [] },
  "pengaturan": { "situs": {}, "beranda": {}, "…": {} },
  "aktivitas": []
}
```

## Menyambungkan ke server (REST API)

Build dengan alamat API, lalu semua baca/tulis memakai REST:

```bash
VITE_API_URL=https://pintu.example.go.id/api npm run build
```

Server perlu menyediakan endpoint berikut. Bentuk JSON-nya sama dengan model data di atas.

| Metode | Alamat | Isi permintaan | Respons |
|---|---|---|---|
| `GET` | `/koleksi/{nama}` | — | Daftar entri, urut menurut `urutan` |
| `GET` | `/koleksi/{nama}/{id}` | — | Satu entri, atau 404 |
| `POST` | `/koleksi/{nama}` | Entri tanpa `id`, `dibuat`, `diperbarui` (`urutan` opsional) | Entri lengkap |
| `PATCH` | `/koleksi/{nama}/{id}` | Sebagian kolom | Entri lengkap setelah diubah |
| `POST` | `/koleksi/{nama}/hapus` | `{ "ids": ["…"] }` | 204 |
| `PUT` | `/koleksi/{nama}/urutan` | `{ "ids": ["…"] }`, urutan baru | 204 |
| `GET` | `/pengaturan/{nama}` | — | Dokumen pengaturan |
| `PUT` | `/pengaturan/{nama}` | Dokumen pengaturan lengkap | Dokumen tersimpan |
| `GET` | `/pengaturan` | — | `{ "situs": "<waktu ISO>", … }`, waktu terakhir tiap pengaturan disimpan |
| `GET` | `/aktivitas?limit=50` | — | Log aktivitas terbaru `{ id, waktu, oleh, aksi, modul, target }` |
| `DELETE` | `/aktivitas` | — | 204 |
| `POST` | `/unggah` | `multipart/form-data`, field `berkas` | `{ nama, ukuran, tipe, url }` |
| `GET` | `/ekspor` | — | Cadangan lengkap (format di atas) |
| `POST` | `/impor` | Cadangan lengkap | 204 |
| `POST` | `/reset` | — | 204 |

Ketentuan:

- Setiap permintaan membawa `Authorization: Bearer <token>` bila sesi memiliki `token`. Balas **401** bila token tidak berlaku; dashboard akan kembali ke halaman login.
- Pesan galat dikirim sebagai JSON `{ "pesan": "…" }` (atau `{ "message": "…" }`) dan ditampilkan apa adanya.
- Server yang mengisi `id`, `dibuat`, `diperbarui`, dan `urutan`, mencatat log aktivitas, serta memeriksa hak akses sesuai peran.
- **Bersihkan HTML** dari kolom teks kaya (`isi`) dengan daftar tag yang diizinkan sebelum disimpan atau ditampilkan di portal.
- Portal publik cukup membaca entri yang boleh tampil: konten beralur dengan `status: "terbit"`, FAQ dengan `tampil: true`, jadwal keliling dengan `aktif: true`. Koleksi `pesan`, `absensi`, dan `pemutakhiran` serta pengaturan `akun` hanya untuk internal.

## Sesi login

Halaman `login.html` di portal memeriksa NIP dan kata sandi (`CONFIG.auth` di `assets/js/main.js`), lalu menyimpan sesi dengan kunci `pintu.sesi`:

```json
{ "nip": "199707162026061002", "masuk": "2026-10-10T00:00:00.000Z", "kedaluwarsa": "2026-10-10T08:00:00.000Z", "ingat": false }
```

Sesi biasa disimpan di `sessionStorage` dan berlaku 8 jam. Bila “Ingat perangkat ini” dicentang, sesi disimpan di `localStorage` selama 7 hari. Dashboard memeriksa sesi saat dibuka, setiap menit, dan saat tab kembali aktif. Bila sesi tidak ada atau kedaluwarsa, pengguna diarahkan ke `login.html?lanjut=…` lalu dikembalikan ke halaman semula setelah masuk.

## Mengganti login statis

Akun statis **tidak aman**: kata sandinya bisa dibaca siapa pun dari kode halaman. Untuk produksi:

1. Hapus `CONFIG.auth` dari `assets/js/main.js`. Kirim NIP dan kata sandi ke endpoint login di server (mis. `POST /api/masuk`) lewat HTTPS.
2. Server memeriksa akun per pegawai beserta perannya, lalu mengembalikan token dan waktu kedaluwarsa.
3. Simpan sesi dengan format di atas ditambah `"token": "…"`. Dashboard otomatis mengirim token itu ke setiap permintaan REST.
4. Untuk keluar, hapus sesi di peramban dan cabut token di server.

## Pemeriksaan sebelum commit

```bash
npm run typecheck && npm run lint && npm run build
```
