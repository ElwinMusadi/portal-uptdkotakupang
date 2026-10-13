# Dashboard PINTU

Dashboard pengelolaan konten portal **PINTU** (UPTD Pendapatan Daerah Wilayah Kota Kupang). Semua informasi yang tampil di portal publik (berita, persyaratan layanan, jadwal, profil, kontak, dan lainnya) dikelola dari sini, lengkap dengan alur publikasi draf → diperiksa → persetujuan → terbit.

Tata letaknya mengikuti template [shadcn/ui *dashboard-01*](https://ui.shadcn.com/blocks#dashboard-01): sidebar, kartu KPI, grafik area interaktif, dan tabel data. Warna dan tipografinya disesuaikan dengan design system portal.

> **Status.** Login sudah diperiksa server (lihat [Sesi login](#sesi-login)), tetapi data konten masih tersimpan di peramban (IndexedDB) sampai dashboard disambungkan ke API. Langkahnya ada di bagian [Menyambungkan ke server](#menyambungkan-ke-server-rest-api).

## Menjalankan

Dashboard adalah bagian dari aplikasi Laravel di root repository (lihat [README](../README.md)). Perlu Node.js 20.19+ atau 22.12+.

```bash
npm install
npm run dev
```

Buat akun admin lokal sekali (hanya berjalan di lingkungan `local`; data dan NIP-nya ada di `database/seeders/LocalAccountSeeder.php`):

```bash
php artisan db:seed
```

Lalu buka `http://portal-uptdkotakupang.test/login` dengan Laravel Herd (atau jalankan `composer dev` lalu buka `http://127.0.0.1:8000/login`) dan masuk dengan akun itu. `composer test:mysql` mereset database, jadi jalankan `php artisan db:seed` lagi sesudahnya.

Dashboard ada di `/dashboard`. Selama `npm run dev` berjalan, perubahan di `resources/js/dashboard/` langsung tampil; tanpa itu Laravel memakai hasil `npm run build`.

| Perintah | Fungsi |
|---|---|
| `npm run dev` | Server Vite dengan muat ulang otomatis |
| `npm run build` | Periksa tipe lalu build ke `public/build/` |
| `npm run typecheck` | Periksa tipe TypeScript |
| `npm run lint` | Periksa kode dengan oxlint |

### Build

`npm run build` menulis hasil ke `public/build/`. Folder itu tidak di-commit; Laravel Cloud menjalankan build saat deploy. Laravel memuat berkasnya lewat `@vite` di `resources/views/dashboard.blade.php`.

Dashboard memakai rute hash (`/dashboard/#/berita`), jadi satu rute Laravel (`/dashboard`) melayani semua layar.

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
resources/views/dashboard.blade.php  ← halaman induk (tema terang/gelap dipasang sebelum React dimuat)
public/assets/img/                   ← lambang NTT dan favicon (dipakai bersama portal)
vite.config.ts                       ← entri resources/js/dashboard/main.tsx, keluaran public/build
resources/js/dashboard/
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
- **Kalkulator PKB** memakai rumus yang sama dengan simulasi di portal (`public/assets/js/main.js`), ditambah sakelar amnesti yang menghapus denda keterlambatan.

## Model data

Sumber kebenarannya `resources/js/dashboard/lib/api/types.ts`. Ada dua bentuk data:

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
- Saat pertama dibuka, dashboard mengisi data awal dari isi portal yang sudah ada (`resources/js/dashboard/lib/api/seed.ts`).
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

> **Belum diimplementasikan di Laravel.** Endpoint publik yang sudah tersedia ada di [docs/api.md](api.md). Kontrak di bawah adalah sasaran untuk modul admin berikutnya.

Build dengan alamat API, lalu semua baca/tulis memakai REST. Karena dashboard dan API kini satu origin, alamatnya cukup `/api`:

```bash
VITE_API_URL=/api npm run build
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

- Permintaan memakai cookie sesi Laravel (satu origin) dan, untuk metode selain `GET`, header `X-XSRF-TOKEN`. Balas **401** bila sesi tidak berlaku; dashboard akan kembali ke halaman login. Balasan **419** berarti token CSRF kedaluwarsa.
- Pesan galat dikirim sebagai JSON `{ "pesan": "…" }` (atau `{ "message": "…" }`) dan ditampilkan apa adanya.
- Server yang mengisi `id`, `dibuat`, `diperbarui`, dan `urutan`, mencatat log aktivitas, serta memeriksa hak akses sesuai peran.
- **Bersihkan HTML** dari kolom teks kaya (`isi`) dengan daftar tag yang diizinkan sebelum disimpan atau ditampilkan di portal.
- Portal publik hanya membaca entri yang boleh tampil lewat endpoint publik tersendiri, bukan kontrak admin ini. FAQ: `published` + `publik` + `tampil` (lihat [docs/api.md](api.md)); modul lain menyusul. Koleksi `pesan`, `absensi`, dan `pemutakhiran` serta pengaturan `akun` hanya untuk internal.

## Sesi login

Halaman `/login` (`public/assets/js/main.js`) mengirim NIP dan kata sandi ke `POST /api/masuk`. Server menyimpan sesi sebagai cookie HttpOnly Laravel Sanctum; tidak ada data sesi di `localStorage`/`sessionStorage`. Kontrak lengkapnya ada di [docs/api.md](api.md#login-pegawai-laravel-sanctum) dan keputusannya di [D-007](decisions.md#d-007--2026-10-13--login-pegawai-dengan-laravel-sanctum).

- Rute `/dashboard` hanya terbuka untuk akun aktif yang sudah masuk; tamu dialihkan ke `/login?lanjut=dashboard%2F`, dan layar yang dituju (`#/berita`) dibuka kembali setelah masuk.
- `resources/js/dashboard/lib/auth.ts` memuat sesi (`GET /api/sesi`) sebelum dashboard tampil dan memeriksanya lagi setiap kali tab kembali dilihat. Pemeriksaan tidak berjalan berkala, supaya tab yang dibiarkan terbuka tidak memperpanjang sesi.
- Sesi berakhir setelah 8 jam tanpa aktivitas atau saat peramban ditutup; “Ingat perangkat ini” menyimpan login selama 7 hari.
- **Keluar** memanggil `POST /api/keluar` lalu kembali ke `/login?keluar=1`. Data dashboard di IndexedDB tetap ada di peramban setelah keluar, jadi hindari komputer bersama sampai data pindah ke server.
- Nama, NIP, dan peran di menu akun berasal dari server. Profil di *Pengaturan › Akun* (jabatan, unit, peran alur) masih data lokal untuk riwayat alur publikasi.
- Administrator produksi dibuat dengan `php artisan pintu:create-admin <nip> "<nama>"`; tanpa mode interaktif, perintah ini mencetak tautan aktivasi (lihat [README](../README.md#login-pegawai)).

## Pemeriksaan sebelum commit

```bash
npm run typecheck && npm run lint && npm run build && composer test
```
