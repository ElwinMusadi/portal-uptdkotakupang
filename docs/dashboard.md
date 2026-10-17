# Dashboard PINTU

Dashboard pengelolaan konten portal **PINTU** (UPTD Pendapatan Daerah Wilayah Kota Kupang). Semua informasi yang tampil di portal publik (berita, persyaratan layanan, jadwal, profil, kontak, dan lainnya) dikelola dari sini, lengkap dengan alur publikasi draf → diperiksa → persetujuan → terbit.

Tata letaknya mengikuti template [shadcn/ui *dashboard-01*](https://ui.shadcn.com/blocks#dashboard-01): sidebar, kartu KPI, grafik area interaktif, dan tabel data. Warna dan tipografinya disesuaikan dengan design system portal.

> **Status.** Login sudah diperiksa server (lihat [Sesi login](#sesi-login)) dan Tanya Jawab, Layanan Luar Kantor, Pesan & Pengaduan, Persyaratan Layanan, Jam Pelayanan, Papan Layanan, Statistik Layanan, Laporan Penerimaan, Kontak & Kanal, Visi & Misi, Tentang UPTD, Beranda, Halaman Statis, Jadwal Pemutakhiran, Pengaturan Situs, serta profil akun sudah tersimpan di server (lihat [Penyimpanan data](#penyimpanan-data)). Konten lain masih tersimpan di peramban (IndexedDB) sampai gilirannya pindah ke server; kontraknya ada di bagian [Menyambungkan ke server](#menyambungkan-ke-server-rest-api).

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
resources/views/dashboard.blade.php  ← halaman induk (tema terang/gelap dipasang sebelum React dimuat; bawaannya Terang; /tema-dashboard.css dimuat sesudah CSS Vite)
public/assets/img/                   ← lambang NTT dan favicon (dipakai bersama portal)
vite.config.ts                       ← entri resources/js/dashboard/main.tsx, keluaran public/build
resources/js/dashboard/
    ├── main.tsx          ← cek sesi, lalu pasang provider dan router
    ├── index.css         ← token warna shadcn/ui yang diselaraskan dengan portal, huruf, dan warna pratinjau papan (bisa ditimpa tema)
    ├── app/              ← layout, router (rute lazy), daftar menu
    ├── components/
    │   ├── ui/           ← komponen shadcn/ui (teks bawaan sudah berbahasa Indonesia)
    │   ├── data/         ← tabel data, lencana status, dialog konfirmasi, kerangka halaman
    │   ├── form/         ← isian formulir, unggah foto/berkas, editor teks kaya, panel sunting
    │   └── alur/         ← aksi dan riwayat alur publikasi
    ├── lib/
    │   ├── api/          ← model data (types.ts), klien lokal, REST & campuran, data awal (seed.ts)
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
| Laporan Penerimaan | `#/penerimaan` | server (`/api/penerimaan/…`) dari berkas STS | `penerimaan.html` (impor yang dinyalakan Administrator) |
| Pemetaan PKB | `#/pemetaan` | server (`/api/pemetaan/…`) dari berkas Data Potensi | `pemetaan.html` (angka gabungan, setelah dinyalakan Administrator) |
| Berita & Pengumuman | `#/berita` | koleksi `berita` | `informasi.html`, `berita-detail.html` |
| Dokumentasi Kegiatan | `#/dokumentasi` | koleksi `dokumentasi` | `dokumentasi.html` |
| Regulasi & Unduhan | `#/unduhan` | koleksi `dokumen` | `unduhan.html` |
| Tanya Jawab | `#/faq` | koleksi `faq` | `index.html#faq` |
| Persyaratan Layanan | `#/layanan` | koleksi `layanan` | `layanan.html` |
| Jam Pelayanan | `#/jadwal/jam` | pengaturan `jam`, koleksi `penyesuaian` | `jadwal.html` |
| Layanan Luar Kantor | `#/jadwal/keliling` | koleksi `keliling` | `jadwal.html#keliling` |
| Papan Layanan | `#/papan` | pengaturan `papan` | `index.html` |
| Tentang UPTD | `#/profil` | pengaturan `profil` | `profil.html` |
| Visi & Misi | `#/visi-misi` | pengaturan `visiMisi` | `visi-misi.html` |
| Struktur Organisasi | `#/struktur` | koleksi `unit` | `struktur-organisasi.html` |
| Kontak & Kanal | `#/kontak` | pengaturan `kontak` | `kontak.html` |
| Beranda | `#/beranda` | pengaturan `beranda` | `index.html` |
| Halaman Statis | `#/halaman` | koleksi `halaman` | `kebijakan-privasi.html`, `syarat-ketentuan.html` |
| Jadwal Pemutakhiran | `#/pemutakhiran` | koleksi `pemutakhiran` | — (internal) |
| Absensi Lapangan | `#/absensi` | server (`/api/absensi/kegiatan`); aplikasi petugas di `/absen` | — (internal) |
| Kalkulator PKB | `#/kalkulator` | server (`/api/kendaraan/{nopol}`) | — (internal) |
| Aplikasi & Situs | `#/aplikasi` | koleksi `aplikasi` | `aplikasi.html` |
| Pengaturan | `#/pengaturan` | pengaturan `situs`, `akun`, cadangan data | — |

Formulir sunting membuka panel samping dengan status di URL (`?baru=1` atau `?ubah=<id>`), jadi tautannya bisa dibagikan. Perubahan yang belum disimpan dijaga saat pindah halaman atau menutup tab. Tekan `Ctrl K` (`⌘K` di Mac) untuk mencari halaman dan konten.

### Alur publikasi

Berita, dokumentasi, dokumen unduhan, dan persyaratan layanan memiliki kolom `status`:

```
draf → diperiksa → persetujuan → terbit → arsip
```

- Pemeriksa dan penyetuju bisa **mengembalikan** konten dengan catatan revisi (`dikembalikan`), lalu penyedia mengajukannya ulang ke `diperiksa`.
- Konten `terbit` bisa ditarik dari portal ke `arsip`, dan arsip bisa dipulihkan ke `draf`.

Peran tiap tahap mengikuti rancangan: **penyedia** (unit pemilik data), **pemeriksa** (kepala seksi/subbag), **penyetuju** (atasan langsung), dan **pengelola publikasi**. Setiap langkah dicatat di `riwayat` entri dan di log aktivitas. Untuk koleksi yang sudah di server (saat ini Persyaratan Layanan, [D-022](decisions.md#d-022--2026-10-14--persyaratan-layanan-tersimpan-di-server-dengan-alur-persetujuan), dan Regulasi & Unduhan, [D-030](decisions.md#d-030--2026-10-14--regulasi--unduhan-tersimpan-di-server-dengan-alur-persetujuan)), hanya Administrator yang mengubah entri yang menunggu persetujuan atau sudah terbit, termasuk menyetujui, mengembalikan dari tahap persetujuan, dan menarik dari portal; tombol langkah itu tidak ditawarkan kepada pegawai lain (`bolehUbahAlur` di `lib/alur.ts`). Berita & Pengumuman dan Dokumentasi Kegiatan juga tersimpan di server, tetapi pengelolanya menjalankan semua langkah sendiri; hanya penghapusannya yang khusus Administrator ([D-031](decisions.md#d-031--2026-10-14--berita--pengumuman-tersimpan-di-server-pengelola-berita-menerbitkan-sendiri), [D-032](decisions.md#d-032--2026-10-14--dokumentasi-kegiatan-tersimpan-di-server-galeri-disusun-per-bulan)). Halaman satu-dokumen (profil, kontak, jam, dan sejenisnya) langsung berlaku saat disimpan.

### Alat bantu

- **Absensi Lapangan** ([D-034](decisions.md#d-034--2026-10-14--absensi-lapangan-tersimpan-di-server-petugas-clock-in-lewat-aplikasi-absen)): Administrator mengelola kegiatan lapangan seperti tilang gabungan (tanggal, titik lokasi, radius, dan petugas yang ditugaskan) dan membuka rekap clock-in per kegiatan: jam (WITA), jarak, akurasi, swafoto, dan tautan peta. Petugas clock-in lewat aplikasi Absensi PINTU di `/absen` (`resources/js/absen`), yang bisa dipasang ke layar utama ponsel, dengan swafoto dari kamera depan (maks. 1024 px) dan lokasi GPS. Kamera dan lokasi hanya bisa dipakai lewat HTTPS atau `localhost`. Pegawai selain Administrator melihat tautan ke aplikasi di halaman ini. Data kegiatan selalu dibaca dari server (`lib/absensi.ts`); tidak ada salinannya di IndexedDB.
- **Kalkulator PKB** ([D-041](decisions.md#d-041--2026-10-14--kalkulator-pkb-dan-cek-biaya-pajak-memakai-mesin-kalkulator-pajak-tarif--simulasi-dipensiunkan)) memakai mesin perhitungan kalkulator-pajak (`resources/js/pkb`, Pergub NTT Nomor 54 Tahun 2026), sama dengan cek biaya pajak di portal. Pegawai mengetik nomor polisi; 450 ms kemudian data kendaraan dicari lewat `GET /api/kendaraan/{nopol}` dan mengisi NJKB, jenis, bobot, jatuh tempo, dan warna plat. Mengganti nomor polisi mengosongkan data kendaraan kecuali tanggal pembayaran. Bila tidak ditemukan, semua isian diisi manual. Rincian dihitung begitu data lengkap; fasilitas wilayah gempa dan mutasi masuk tersedia, Tembak RU tidak. Tombol *Salin ringkasan* menyalin ringkasan penetapan seperti di kalkulator-pajak. Data kendaraan dari BPAD tersedia sampai pukul 21.00 WITA; di luar jam itu pencarian langsung dijawab dengan jam layanannya ([D-042](decisions.md#d-042--2026-10-14--jam-layanan-data-kendaraan-bpad)) dan data diisi manual.

## Model data

Sumber kebenarannya `resources/js/dashboard/lib/api/types.ts`. Ada dua bentuk data:

- **Koleksi**: daftar entri. Setiap entri punya `id`, `dibuat`, `diperbarui` (ISO 8601), dan `urutan`. Konten beralur menambahkan `status`, `penyedia`, `pemeriksa`, `catatanRevisi`, `terbitPada`, dan `riwayat`.
  Nama koleksi: `berita`, `dokumentasi`, `dokumen`, `layanan`, `faq`, `keliling`, `penyesuaian`, `unit`, `pesan`, `halaman`, `pemutakhiran`.
- **Pengaturan**: satu dokumen per halaman.
  Nama pengaturan: `situs`, `beranda`, `papan`, `jam`, `statistik`, `profil`, `visiMisi`, `kontak`, `akun`.

Ketentuan format: tanggal kalender `YYYY-MM-DD`, jam `HH:MM` (24 jam, WITA), isi teks kaya berupa HTML sederhana (paragraf, subjudul h2/h3, daftar, kutipan, tautan), dan berkas berbentuk `{ nama, ukuran, tipe, url }`.

Contoh entri `faq`:

```json
{
  "id": "01m4tdcs3vdwjm3pg8pqmxmjhx",
  "dibuat": "2026-09-01T01:00:00+00:00",
  "diperbarui": "2026-10-02T03:15:00+00:00",
  "urutan": 0,
  "pertanyaan": "Apakah pajak tahunan bisa diwakilkan?",
  "jawaban": "Bisa, dengan membawa KTP pemilik dan surat kuasa bermeterai.",
  "status": "published",
  "publik": true,
  "tampil": true
}
```

## Penyimpanan data

Tanpa `VITE_API_URL`, dashboard berjalan dalam **mode campuran** (`lib/api/hybrid.ts`, [D-011](decisions.md#d-011--2026-10-13--tanya-jawab-tersimpan-di-server)):

- Koleksi di `KOLEKSI_SERVER` (saat ini `faq`, `keliling`, `penyesuaian`, `pesan`, `layanan`, `halaman`, `pemutakhiran`, `dokumen`, `berita`, `dokumentasi`, `unit`, dan `aplikasi`) dan pengaturan di `PENGATURAN_SERVER` (saat ini `jam`, `papan`, `statistik`, `kontak`, `visiMisi`, `profil`, `beranda`, `situs`, dan `akun`) dibaca dan ditulis lewat API PINTU (`/api/koleksi/…`, `/api/pengaturan/jam`, `/api/pengaturan/papan`, `/api/pengaturan/statistik`, `/api/pengaturan/kontak`, `/api/pengaturan/visiMisi`, `/api/pengaturan/profil`, `/api/pengaturan/beranda`, `/api/pengaturan/situs`, `/api/pengaturan/akun`, lihat [docs/api.md](api.md#tanya-jawab-di-dashboard)). Perubahan pegawai lain muncul saat tab kembali dilihat, dan server memeriksa modul pengguna. Waktu simpan pengaturan server (`GET /api/pengaturan`) menggantikan waktu di peramban untuk pengaturan itu.
- Koleksi dan pengaturan lain serta unggahan tersimpan di IndexedDB peramban: database `pintu-dashboard`, store `data`, dengan kunci `koleksi:<nama>`, `pengaturan:<nama>`, `aktivitas`, dan `meta`.
- Saat pertama dibuka, dashboard mengisi data lokal awal dari isi portal yang sudah ada (`resources/js/dashboard/lib/api/seed.ts`). Tanya jawab contoh di sana tidak dipakai lagi dan tidak dipindahkan ke server.
- Foto dan berkas disimpan sebagai data URL. Foto dikecilkan dulu menjadi maks. 1600 px.
- Bila IndexedDB tidak tersedia (mis. mode privat tertentu), data lokal hanya tersimpan di memori dan dashboard menampilkan peringatan.
- Data lokal hanya ada di peramban itu dan **belum tampil di portal publik**.
- Log aktivitas di Ringkasan menggabungkan log server (`GET /api/aktivitas`) dengan log peramban, terbaru lebih dulu.

**Cadangan.** Di *Pengaturan › Data*, unduh seluruh data sebagai JSON atau pulihkan dari berkas cadangan. Cadangan menyertakan Tanya Jawab dari server, tetapi *Pulihkan dari cadangan*, *Kosongkan log aktivitas*, dan *Kembalikan ke data awal* hanya mengubah data di peramban. Berkas yang sama bisa dipakai sebagai data awal server:

```json
{
  "versi": 1,
  "diekspor": "2026-10-10T02:00:00.000Z",
  "koleksi": { "berita": [], "dokumentasi": [], "…": [], "pemutakhiran": [] },
  "pengaturan": { "situs": {}, "beranda": {}, "…": {} },
  "aktivitas": []
}
```

## Menyambungkan ke server (REST API)

> **Sebagian tersedia.** Laravel sudah menyediakan koleksi `faq`, `keliling`, `penyesuaian`, `pesan`, `layanan`, `halaman`, `pemutakhiran`, `dokumen`, `berita`, `dokumentasi`, dan `unit` (tanpa `PUT /urutan` untuk `keliling` dan `penyesuaian`, yang diurutkan menurut hari, jam, atau tanggal, untuk `dokumen`, yang diurutkan per kategori, dan untuk `berita` serta `dokumentasi`, yang diurutkan menurut tanggal; `halaman` hanya dibaca dan disunting), pengaturan `jam`, `papan`, `statistik`, `kontak`, `visiMisi`, `profil`, `beranda`, `situs`, dan `akun`, `GET /pengaturan`, serta `GET /aktivitas` ([docs/api.md](api.md#tanya-jawab-di-dashboard)); mode campuran memakainya otomatis. Endpoint lain di bawah adalah sasaran modul berikutnya, jadi `VITE_API_URL` baru berguna setelah semuanya tersedia. `DELETE /aktivitas` sengaja tidak dibuat: log aktivitas server tidak bisa dikosongkan dari dashboard.

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
| `POST` | `/unggah` | `multipart/form-data`, field `modul` (tujuan) dan `berkas` | `{ nama, ukuran, tipe, url }`; tersedia di server ([D-028](decisions.md#d-028--2026-10-14--unggahan-disimpan-di-laravel-cloud-object-storage)). Mode campuran memakainya untuk tujuan di `UNGGAHAN_SERVER` (saat ini `visiMisi`, `profil`, `beranda`, `dokumen`, `berita`, `dokumentasi`, `unit`, dan `aplikasi`) |
| `GET` | `/ekspor` | — | Cadangan lengkap (format di atas) |
| `POST` | `/impor` | Cadangan lengkap | 204 |
| `POST` | `/reset` | — | 204 |

Ketentuan:

- Permintaan memakai cookie sesi Laravel (satu origin) dan, untuk metode selain `GET`, header `X-XSRF-TOKEN`. Balas **401** bila sesi tidak berlaku; dashboard akan kembali ke halaman login. Balasan **419** berarti token CSRF kedaluwarsa.
- Pesan galat dikirim sebagai JSON `{ "pesan": "…" }` (atau `{ "message": "…" }`) dan ditampilkan apa adanya. Galat validasi (`422`) membawa `errors` per isian; formulir entri menampilkannya di isian yang bersangkutan.
- Server yang mengisi `id`, `dibuat`, `diperbarui`, dan `urutan`, mencatat log aktivitas, serta memeriksa hak akses sesuai peran.
- **Bersihkan HTML** dari kolom teks kaya (`isi`) dengan daftar tag yang diizinkan sebelum disimpan atau ditampilkan di portal.
- Portal publik hanya membaca entri yang boleh tampil lewat endpoint publik tersendiri, bukan kontrak admin ini. FAQ: `published` + `publik` + `tampil` (lihat [docs/api.md](api.md)); modul lain menyusul. Koleksi `pesan` dan `pemutakhiran`, pengaturan `akun`, serta data Absensi Lapangan hanya untuk internal.

## Sesi login

Halaman `/login` (`public/assets/js/main.js`) mengirim NIP dan kata sandi ke `POST /api/masuk`. Server menyimpan sesi sebagai cookie HttpOnly Laravel Sanctum; tidak ada data sesi di `localStorage`/`sessionStorage`. Kontrak lengkapnya ada di [docs/api.md](api.md#login-pegawai-laravel-sanctum) dan keputusannya di [D-007](decisions.md#d-007--2026-10-13--login-pegawai-dengan-laravel-sanctum).

- Rute `/dashboard` hanya terbuka untuk akun aktif yang sudah masuk; tamu dialihkan ke `/login?lanjut=dashboard%2F`, dan layar yang dituju (`#/berita`) dibuka kembali setelah masuk.
- `resources/js/dashboard/lib/auth.ts` memuat sesi (`GET /api/sesi`) sebelum dashboard tampil dan memeriksanya lagi setiap kali tab kembali dilihat. Pemeriksaan tidak berjalan berkala, supaya tab yang dibiarkan terbuka tidak memperpanjang sesi.
- Sesi berakhir setelah 8 jam tanpa aktivitas atau saat peramban ditutup; “Ingat perangkat ini” menyimpan login selama 7 hari.
- **Keluar** memanggil `POST /api/keluar` lalu kembali ke `/login?keluar=1`. Data dashboard di IndexedDB tetap ada di peramban setelah keluar, jadi hindari komputer bersama sampai data pindah ke server.
- Nama, NIP, dan peran di menu akun berasal dari server. Profil di *Pengaturan › Akun* (jabatan, unit, peran alur) masih data lokal untuk riwayat alur publikasi.
- Administrator produksi dibuat dengan `php artisan pintu:create-admin <nip> "<nama>"`; tanpa mode interaktif, perintah ini mencetak tautan aktivasi (lihat [README](../README.md#login-pegawai)).

## Akun pegawai

Halaman *Akun Pegawai* (`#/akun`, `pages/akun.tsx`) hanya tampil di menu dan pencarian untuk Administrator; Pegawai UPTD yang membuka alamatnya melihat pesan bahwa halaman itu khusus Administrator, dan server tetap menolak permintaannya. Keputusannya di [D-008](decisions.md#d-008--2026-10-13--manajemen-akun-oleh-administrator), kontraknya di [docs/api.md](api.md#manajemen-akun-administrator).

- Data akun selalu dibaca dari server (`lib/akun.ts`), apa pun sumber data konten dashboard; tidak ada salinannya di IndexedDB.
- *Tambah akun* membuat akun yang menunggu aktivasi, lalu menampilkan tautan aktivasi sekali saja untuk disalin dan dikirim kepada pemiliknya.
- Menu tiap baris: *Sunting* (nama, surel, peran, modul), *Tangguhkan* / *Aktifkan kembali*, *Atur ulang kata sandi* atau *Tautan aktivasi baru*, dan *Nonaktifkan*. Setiap tindakan yang mengubah akses meminta konfirmasi yang menjelaskan akibatnya. Baris akun sendiri hanya bisa disunting, dan perannya tidak bisa diubah.
- Galat validasi dari server ditampilkan di isian yang bersangkutan (`ApiError.isian`).

## Modul pegawai

Pegawai UPTD hanya melihat modul yang ditugaskan Administrator ([D-010](decisions.md#d-010--2026-10-13--penugasan-modul-untuk-pegawai-uptd)); Administrator melihat semuanya. Daftar modul pengguna datang dari server lewat `sesi().modul`.

- `app/nav.ts` adalah satu-satunya sumber: setiap item menu konten punya `modul`, item khusus Administrator punya `khusus`. `navUntuk()` menyaring menu samping, pencarian, dan "Buat konten"; `GRUP_MODUL` menyusun centang modul di panel akun dengan judul dan kelompok yang sama dengan menu.
- `Layout` mencocokkan alamat yang dibuka dengan item menu. Bila item itu tidak boleh dilihat, halaman diganti pesan "Modul ini belum ditugaskan kepada Anda" atau "Khusus Administrator".
- `useItemAlur()` dan `usePemutakhiran()` di `lib/hitung.ts` menyaring konten dan jadwal per modul, sehingga Ringkasan dan Alur Publikasi ikut terbatas. Daftar Jadwal Pemutakhiran juga sudah disaring server ([D-025](decisions.md#d-025--2026-10-14--jadwal-pemutakhiran-tersimpan-di-server)). Kartu dan grafik pesan hanya tampil bagi pengelola Pesan & Pengaduan.
- Untuk Tanya Jawab, Layanan Luar Kantor, Pesan & Pengaduan, Persyaratan Layanan, Jam Pelayanan, Papan Layanan, Statistik Layanan, Laporan Penerimaan, Kontak & Kanal, Visi & Misi, Tentang UPTD, Beranda, Halaman Statis, dan Jadwal Pemutakhiran, server ikut menegakkannya (`FaqPolicy`, `MobileServiceStopPolicy`, `MessagePolicy`, `ServicePolicy`, `SettingPolicy`, `RevenueImportPolicy`, `ScheduleAdjustmentPolicy`, `StaticPagePolicy`, `UpdateStandardPolicy`, `AgencyAppPolicy`): pegawai tanpa modul itu mendapat `403`. Pengaturan Situs bukan milik modul mana pun: semua pegawai membacanya, hanya Administrator yang menyimpannya, dan formulirnya hanya-baca bagi pegawai lain. Karena itu Ringkasan hanya memuat jadwal Samsat Keliling bagi pengelolanya dan pengelola Jadwal Pemutakhiran (`useKoleksi(…, { aktif })`), isi Papan Layanan hanya bagi pengelolanya (`usePengaturan(…, { aktif })`), dan pesan (lencana menu, kartu, grafik, antrean kerja, jumlah di *Pengaturan › Data*) hanya bagi pengelola Pesan & Pengaduan; yang lain memakai waktu simpannya. Untuk konten yang masih di IndexedDB, pembatasan ini baru mengatur tampilan; endpoint konten berikutnya wajib memeriksa modul yang sama.

## Tanya Jawab

Halaman *Tanya Jawab* (`#/faq`, `pages/faq.tsx`) adalah modul pertama yang tersimpan di server ([D-011](decisions.md#d-011--2026-10-13--tanya-jawab-tersimpan-di-server)).

- Formulir berisi Pertanyaan, Jawaban, Status (*Draf*, *Terbit*, *Ditarik*), *Untuk warga*, dan *Tampilkan di portal*. Entri baru mulai sebagai draf yang belum untuk warga. Kalimat di bawah formulir menjelaskan apakah isian itu membuat entri tampil di portal.
- Kolom *Status* menandai entri *Di portal* bila terbit, untuk warga, dan ditampilkan (`keadaanPortalFaq()` di `lib/meta.ts`), sama dengan syarat `GET /api/public/faq`. Selain itu kolom ini menyebut alasannya: draf, ditarik, bukan untuk warga, atau disembunyikan.
- Hapus, per baris maupun massal, hanya untuk Administrator. Pegawai UPTD menyembunyikan entri dengan sakelar atau menariknya dari portal.
- Beranda portal membaca `GET /api/public/faq` ([D-012](decisions.md#d-012--2026-10-13--beranda-menampilkan-tanya-jawab-dari-api-publik)). Bila tidak ada entri yang *Di portal*, bagian Tanya Jawab di beranda disembunyikan.

## Layanan Luar Kantor

Halaman *Layanan Luar Kantor* (`#/jadwal/keliling`, `pages/jadwal/keliling.tsx`) tersimpan di server sejak [D-013](decisions.md#d-013--2026-10-13--samsat-keliling-tersimpan-di-server-batas-laju-api-publik-300) dan mencakup tiga jenis sejak [D-043](decisions.md#d-043--2026-10-15--layanan-luar-kantor-tiga-unit-samsat-keliling-gerai-tetap-dan-loket-sementara). Tab aktif tersimpan di alamat (`?jenis=gerai`), jadi `?jenis=sementara&baru=1` langsung membuka formulir loket sementara.

- **Samsat Keliling**: jadwal per tanggal dengan unit 01–03. Pratinjau mingguan (Senin–Minggu) menampilkan semua jadwal, yang nonaktif ditandai. Tombol *Salin ke minggu berikutnya* menyalin minggu yang sedang ditampilkan ke minggu berikutnya dalam keadaan nonaktif; periksa unit dan lokasinya, lalu aktifkan. Daftar menampilkan jadwal hari ini dan yang akan datang lebih dulu.
- **Isian Lokasi** ([D-044](decisions.md#d-044--2026-10-15--daftar-lokasi-dan-salin-jadwal-per-hari)) dipilih dari daftar lokasi (sekaligus mengisi kecamatan) atau diketik bila belum ada; lokasi yang diketik masuk daftar saat jadwal disimpan. Tab **Lokasi** mengelola daftar itu: tambah, ubah, hapus, dan jumlah jadwal mendatang yang memakainya. Mengubah atau menghapus lokasi tidak mengubah jadwal yang sudah tersimpan.
- **Salin per hari** ([D-044](decisions.md#d-044--2026-10-15--daftar-lokasi-dan-salin-jadwal-per-hari)): tombol Salin pada kartu hari di pratinjau mingguan menyalin semua jadwal Samsat Keliling hari itu ke hari-hari yang dicentang (minggu yang sama atau berikutnya); statusnya mengikuti jadwal asal.
- **Gerai Tetap**: gerai rutin seperti Samsat Corner dan Mall Pelayanan Publik, dengan hari dan jam buka. Jam yang berbeda per hari dicatat sebagai baris tersendiri.
- **Loket Sementara**: loket musiman atau acara, mis. di kantor lurah, pasar, atau pameran, dengan tanggal mulai dan selesai.
- Jadwal yang aktif langsung tampil di beranda ("Di luar kantor hari ini") dan halaman Jadwal portal (`GET /api/public/keliling`).

## Jam Pelayanan

Halaman *Jam Pelayanan* (`#/jadwal/jam`, `pages/jadwal/jam.tsx`) tersimpan di server sejak [D-014](decisions.md#d-014--2026-10-13--jam-pelayanan-tersimpan-di-server): jam loket sebagai pengaturan `jam`, penyesuaian jadwal sebagai koleksi `penyesuaian`.

- Sebelum jam loket pertama kali disimpan, semua hari tercatat tutup dan portal menyatakan jam loket belum tersedia. Jam contoh dari penyimpanan peramban tidak dipindahkan.
- Jam yang disimpan langsung mengubah status loket, hitung mundur, tabel jam di halaman Jadwal, ringkasan di halaman Kontak, dan footer semua halaman portal (`GET /api/public/jam`). Penyesuaian menggantikan jam reguler pada tanggalnya dan didaftar di halaman Jadwal sampai tanggalnya lewat.

## Papan Layanan

Halaman *Papan Layanan* (`#/papan`, `pages/papan.tsx`) tersimpan di server sejak [D-015](decisions.md#d-015--2026-10-13--papan-layanan-tersimpan-di-server) sebagai pengaturan `papan`.

- Server mengisi waktu *diperbarui* saat isi papan berubah; menyimpan isi yang sama tidak mengubahnya.
- Beranda menampilkan isi terakhir setiap hari sampai diubah ([D-050](decisions.md#d-050--2026-10-15--papan-layanan-berlaku-setiap-hari)), dengan label "Diperbarui hari ini" pada hari selain hari penyimpanan. Selama loket tutup menurut Jam Pelayanan, semua loket tampil Tutup dan waktu tunggu tidak ditampilkan. Papan cukup disimpan bila ada perubahan.

## Pemetaan PKB

Halaman *Pemetaan PKB* (`#/pemetaan`, `pages/pemetaan.tsx`, modul `pemetaan`, [D-052](decisions.md#d-052--2026-10-16--pemetaan-pkb-dari-berkas-data-potensi)) memetakan potensi dan kepatuhan PKB Kota Kupang dari berkas Data Potensi BPAD. Kontraknya di [`api.md`](api.md#pemetaan-pkb-di-dashboard).

- **Impor Data Potensi** membuka panel samping: pilih berkas .xls; peramban memeriksanya (susunan 43 kolom, banyaknya kendaraan, tanggal penetapan) tanpa mengirim apa pun, lalu *Impor* mengirimnya per 1.000 baris dengan bilah kemajuan (`lib/pemetaan.ts`, `bacaBerkasPotensi` membaca berkas sedikit demi sedikit). Impor yang selesai menggantikan data sebelumnya; *Batalkan* menghentikannya tanpa mengubah data yang ditampilkan. Administrator bisa *Hapus data*.
- **Atur kolom** (khusus Administrator, `AturKolom` di `pages/pemetaan.tsx`): tombol di kartu *Per kecamatan* memilih kolom tabel kecamatan dan kelurahan (Kendaraan, Roda dua, Roda tiga, Roda empat+, Taat, Belum bayar, Menunggak, % menunggak, Potensi pokok, Tagihan, Tunggakan), yang juga berlaku di tabel portal; tombol di kartu daftar Sasaran penagihan memilih kolom daftar itu (Pemilik, Telepon, Kendaraan, Kecamatan, Kepatuhan, Lama tunggakan, Pokok pajak, Tunggakan, Tagihan, Hasil kunjungan; nomor polisi selalu tampil). Setiap centang langsung tersimpan di server (`useKolomPemetaan`, `PUT /api/pemetaan/kolom`), jadi tetap sama setelah muat ulang atau masuk lagi; sedikitnya satu kolom tetap terpilih. Pegawai lain melihat tabel sesuai pilihan itu tanpa tombolnya.
- **Tampil di portal** (Irisan 2): sakelar Administrator di samping keterangan data (`useTampilPotensi`). Menyalakannya meminta konfirmasi, lalu angka gabungan per kecamatan dan kelurahan, tanpa identitas, tampil di halaman portal *Pemetaan PKB* (`/pemetaan`); mematikannya langsung menyembunyikan. Impor baru selalu mulai tersembunyi. Pegawai lain melihat keterangan "Tampil di portal" atau "Belum tampil di portal".
- **Peta & ringkasan**: angka utama (objek pajak per roda, taat, belum bayar < 1 tahun, menunggak ≥ 1 tahun, potensi pokok PKB, tagihan belum terbayar); peta wilayah dengan Batas Desa/Kelurahan BIG 1:10.000 edisi 2022 (`components/pemetaan/peta-wilayah.tsx`, batas di `portal/pemetaan/batas-wilayah.ts` dan perhitungan di `portal/pemetaan/peta.ts`, keduanya juga dipakai portal): enam kecamatan, dan saat kecamatan dipilih peta diperbesar ke kecamatan itu dengan kelurahannya (kecamatan lain redup dan bisa dipilih, *← Semua kecamatan* kembali); Kota Lama yang sempit diberi label di laut dengan garis penunjuk, label kelurahan yang akan saling menimpa dilewati; peta setinggi 600 px (paling tinggi 75% layar) dengan lebar mengikuti bentuk tampilan, sedikitnya 448 px, sama seperti portal; peta itu diwarnai menurut *Menunggak ≥ 1 tahun*, *Belum taat*, atau *Tagihan*, dari nilai terendah sampai tertinggi; tabel per kecamatan dan per kelurahan (memilih kecamatan di peta atau tabel menyaring kelurahannya; judul kolom angka boleh membungkus agar tabel muat); kepatuhan, lama tunggakan, jenis kendaraan, dan hasil kunjungan lapangan. Bila data memuat lebih dari satu bulan jatuh tempo, ringkasan bisa disaring per bulan.
- **Sasaran penagihan**: peringatan data pribadi, saringan (nomor polisi atau nama, kecamatan, kelurahan, kepatuhan, lama tunggakan, roda, bulan), dan tabel 200 kendaraan dengan tagihan terbesar (nomor polisi, pemilik dan alamat, telepon, kendaraan, kepatuhan, tagihan). Kepatuhan mulai dari **Belum taat** (semua selain Taat, termasuk kendaraan menunggak yang tagihannya tidak tercantum di berkas); *Semua status* dan tiap status tetap bisa dipilih, dan *Atur ulang* kembali ke Belum taat. Bila tidak ada yang cocok, keterangan di bawah judul tidak tampil, dan pesan kosong menyebut bahwa kendaraan taat tidak termasuk selama Belum taat dipilih. Nomor polisi membuka lembar samping dengan semua kolom berkas, termasuk KTP, mesin, rangka, dan kunjungan lapangan.

## Antrean Online

Modul `antrean` ([D-051](decisions.md#d-051--2026-10-15--antrean-online-rencana-dan-keputusan-awal), rencana: [`rencana-antrean-online.md`](rencana-antrean-online.md)), menu *Layanan › Antrean Online*. Selalu dibaca dari server; kontraknya di [`api.md`](api.md#antrean-online-di-dashboard).

- **Meja Informasi** (`#/antrean`, `pages/antrean/meja.tsx`): ketik nomor polisi, pilih layanan (A–D yang aktif), lalu *Cetak nomor* (atau Enter). Tiket dua lembar dicetak di printer thermal 80 mm (`lib/cetak-tiket.ts`): lembar 1 untuk wajib pajak, lembar 2 ditempel pada berkas, keduanya dengan nomor, layanan, nomor polisi, waktu, dan QR alamat halaman lacak (pustaka `uqr`). Pencetakan memakai iframe tersendiri dengan halaman 80 × 110 mm per lembar, jadi gaya cetak tidak memengaruhi halaman lain; Chrome/Edge dengan `--kiosk-printing` mencetak tanpa dialog. Kartu *Tiket terakhir* menampilkan QR dan tombol *Cetak ulang*. Daftar nomor per tanggal (diperbarui setiap 15 detik) dengan pencarian nomor/nomor polisi, saringan layanan, *Cetak ulang*, dan *Batalkan* (dengan alasan).
- **Loket** (`#/antrean/loket`, `pages/antrean/loket.tsx`, Irisan 2): petugas memilih loketnya (diingat di perangkat), lalu *Panggil berikutnya* (nomor menunggu paling awal di tahap loket; nomor yang masih dipanggil otomatis ditunda), atau *Panggil nomor tertentu* dengan mengetik nomor/nomor polisi atau memindai QR berkas (pemindai USB mengetik lalu Enter; tahap sebelumnya yang belum ditandai dianggap selesai). Nomor yang dipanggil: *Panggil ulang*, *Tunda*, *Selesai · lanjut ke …*. Daftar Menunggu dan Ditunda diperbarui setiap 3 detik.
- **Pos Proses** (`#/antrean/pos`, `pages/antrean/pos.tsx`, Irisan 3): petugas memilih posnya (Penetapan, Server – Perubahan data, Pelunasan, Cetak SKPD, Cetak STNK; diingat di perangkat). Kotak pindai selalu siap: pindaian QR berkas dengan pemindai USB, nomor/nomor polisi yang diketik lalu Enter, atau *Pindai dengan kamera* (hanya tampil bila peramban punya `BarcodeDetector`, mis. Chrome Android) **langsung menandai** nomornya, lalu tampil konfirmasi dengan *Urungkan* (keputusan pemilik). Di Penetapan, pilihan *Pindaian menandai* menentukan *Selesai* atau *Perlu perubahan data*; *Perlu perubahan data* hanya berlaku untuk satu pindaian, lalu kembali ke *Selesai*. Konfirmasi tampil di bawah kotak pindai sampai pindaian berikutnya dan sebagai toast 10 detik, keduanya dengan *Urungkan*. Daftar nomor di tahap pos (diperbarui setiap 3 detik) dengan *Selesai*, di Penetapan juga *Perubahan data* dan tanda *Di Server*; di Server, *Data sudah diubah* mengembalikan berkas ke daftar Penetapan. Tahap sebelumnya yang belum ditandai dianggap selesai.
- **Arsip** (`#/antrean/arsip`, `pages/antrean/arsip.tsx`, Irisan 4): semua petugas Antrean mencari nomor yang pernah dicetak, termasuk yang selesai atau batal: nomor antrean (di tanggal mana pun), sebagian nomor polisi, atau QR yang dipindai, ditambah tanggal cetak, layanan, status, dan tahap; paling banyak 200 nomor terbaru. *Riwayat* membuka lembar samping dengan data tiket, tautan halaman lacak, dan riwayat lengkap (waktu, kejadian, petugas, loket, tanda *Otomatis*). Administrator bisa *Hapus nomor ini* untuk tiket yang selesai atau batal dari hari sebelumnya (dengan konfirmasi).
- **Ringkasan** (`#/antrean/ringkasan`, `pages/antrean/ringkasan.tsx`, Irisan 4): Harian, Bulanan, atau Rentang sampai 31 hari; angka Dicetak, Selesai, Dibatalkan, Masih berjalan, Pernah ditunda; tabel per layanan, per tahap (ditandai selesai, dilewati otomatis, pernah ditunda, nilai tengah tunggu dipanggil dan lama di tahap), dan per hari untuk rentang. *Unduh CSV* (`csvRingkasan` di `lib/antrean.ts`) memuat ringkasan dan daftar nomor dengan nomor polisi.
- **Pengaturan Antrean** (`#/antrean/pengaturan`, khusus Administrator): layanan (nama, cakupan, tahap yang dilalui, aktif; huruf kode tetap), **loket** (nama, tahap pemanggil, layanan yang diambil *Panggil berikutnya*, nama yang dibacakan, dipakai/tidak; tambah, ubah, hapus), dan **layar TV** (tambah layar → tautan pemasangan sekali tampil untuk dibuka di peramban TV; cabut).
- **Layar TV** (`/layar`, di luar dashboard): panggilan terbaru dengan nomor polisi, nomor yang dipanggil di setiap loket, dan jumlah yang menunggu; panggilan baru dibacakan dengan suara bahasa Indonesia peramban setelah *Aktifkan suara* diklik.
- Halaman-halaman itu dihubungkan navigasi di atasnya (`components/antrean/nav-antrean.tsx`). Halaman lacak `/lacak/<kode>` di portal menampilkan jam selesai setiap tahap dan banyaknya nomor di depan saat menunggu dipanggil.

## Tarif & Simulasi (dipensiunkan)

Halaman *Tarif & Simulasi* dipensiunkan oleh [D-041](decisions.md#d-041--2026-10-14--kalkulator-pkb-dan-cek-biaya-pajak-memakai-mesin-kalkulator-pajak-tarif--simulasi-dipensiunkan): Kalkulator PKB dan cek biaya pajak di portal menghitung dengan mesin kalkulator-pajak, jadi tidak ada tarif yang diatur di dashboard. Alamat `#/tarif` dialihkan ke Kalkulator PKB, dan modul `tarif` tidak lagi bisa ditugaskan.

## Statistik Layanan

Halaman *Statistik Layanan* (`#/statistik`, `pages/statistik.tsx`) tersimpan di server sejak [D-018](decisions.md#d-018--2026-10-13--statistik-layanan-tersimpan-di-server-hanya-angka-terverifikasi-yang-terbit) sebagai pengaturan `statistik`.

- Hanya statistik berstatus *Terverifikasi pimpinan* yang tampil di dasbor transparansi beranda (`GET /api/public/statistik`). Selama berstatus *Ilustrasi*, angkanya tidak keluar dari server dan beranda menyatakan statistik layanan belum diterbitkan.
- Setiap kategori punya sakelar *Tampil di portal* ([D-056](decisions.md#d-056--2026-10-17--kategori-statistik-layanan-bisa-disembunyikan-dari-portal)) di atas isiannya; kategori yang dimatikan tidak muncul sebagai tab di dasbor transparansi beranda, apa pun status datanya, dan tabnya di editor diberi ikon mata dicoret. Bawaannya menyala.
- Ketiga kategori (Transaksi pelayanan, Samsat Keliling, Kepuasan masyarakat), empat angka utama per kategori, dan 12 nilai bulanan (Januari–Desember) tetap; server menolak dokumen dengan susunan lain. Kategori Penerimaan pajak dihapus ([D-055](decisions.md#d-055--2026-10-17--ringkasan-penerimaan-dan-pemetaan-di-dasbor-beranda)): tab Penerimaan pajak dan Pemetaan PKB di dasbor beranda terisi otomatis dari Laporan Penerimaan dan Pemetaan PKB yang ditampilkan di portal. Dokumen yang tersimpan sebelumnya dibaca tanpa kategori itu. Angka contoh dari penyimpanan peramban tidak dipindahkan.

## Laporan Penerimaan

Halaman *Laporan Penerimaan* (`#/penerimaan`, `pages/penerimaan.tsx`, modul `penerimaan`, [D-047](decisions.md#d-047--2026-10-15--laporan-penerimaan-dari-berkas-sts-per-loket)) mengolah berkas STS sistem BPAD menjadi laporan penerimaan per loket. Datanya selalu dibaca dari server (`lib/penerimaan.ts`, [docs/api.md](api.md#laporan-penerimaan-di-dashboard)); tidak ada salinannya di IndexedDB.

- **Impor berkas STS** membuka panel samping: pilih loket asal (berkas tidak mencantumkannya) dan berkas .xls, lalu **Periksa berkas** menampilkan pratinjau dari server (transaksi di berkas, yang akan disimpan, yang dilewati karena sudah pernah diimpor, dan rincian per jenis daftar) sebelum **Simpan**. Setelah tersimpan, laporan pindah ke periode berkas itu.
- Periode **Harian**, **Bulanan**, atau **Rentang** (maks. satu tahun), dengan tombol hari/bulan sebelumnya dan berikutnya. Sebelum dipilih, laporan menampilkan tanggal terakhir yang sudah diimpor. Saringan berupa daftar pilihan bercentang yang bisa dicari (loket, jenis daftar, jenis kendaraan, kepemilikan, warna plat, merek, asal UPT, kecamatan, kelurahan) dan pilihan tunggakan; isinya nilai yang ada dalam periode itu.
- Isi laporan: bento ringkasan (total penerimaan dengan susunan komponen dan perubahan terhadap periode pembanding, transaksi, kendaraan baru dari jenis daftar Baru, pajak provinsi, opsen kabupaten/kota; lencana angka turun ke bawah angka bila tempatnya tidak cukup), kartu per loket (loket yang terjadwal melayani tetapi belum diimpor ditandai "Belum ada data" pada laporan harian tanpa saringan), grafik per hari (untuk lebih dari sehari; batang membuka laporan harian), grafik bertumpuk per jenis daftar, jenis kendaraan, kecamatan, dan asal UPT (delapan terbesar, sisanya digabung), dan tabel rincian bertab per saringan dan per tanggal. Nama di kolom pertama tabel menyaring laporan dengannya; **Ekspor CSV** mengunduh tab yang dibuka (pemisah titik koma dengan BOM, untuk Excel berbahasa Indonesia).
- **Riwayat impor** mendaftar setiap impor (periode, berkas, loket, transaksi baru dan yang dilewati, total, pengimpor). Membuka impor menampilkan laporannya; menghapusnya menghapus transaksinya dari laporan.
- Kolom **Portal** di Riwayat impor ([D-048](decisions.md#d-048--2026-10-15--halaman-portal-laporan-penerimaan)): Administrator menyalakan impor yang sudah diperiksa, setelah konfirmasi, agar penerimaannya tampil di halaman Laporan Penerimaan portal (`/penerimaan`), dan bisa mematikannya lagi. Pegawai lain melihat statusnya (Tampil atau Belum tampil). Kepala halaman punya tautan **Lihat di portal**.

## Kontak & Kanal

Halaman *Kontak & Kanal* (`#/kontak`, `pages/kontak.tsx`) tersimpan di server sejak [D-019](decisions.md#d-019--2026-10-13--kontak--kanal-tersimpan-di-server) sebagai pengaturan `kontak`.

- Isinya langsung tampil di footer semua halaman portal dan di halaman Kontak & Pengaduan (`GET /api/public/kontak`). Baris yang tidak diisi tidak ditampilkan.
- Tautan hanya boleh berupa alamat lengkap (`https://…`), `mailto:`, `tel:`, jangkar `#…`, atau halaman portal seperti `jadwal.html`; server menolak yang lain.
- Sebelum disimpan pertama kali, portal menyatakan alamat, nomor kontak, dan kanal pengaduan belum tersedia. Kontak contoh dari penyimpanan peramban tidak dipindahkan.

## Visi & Misi

Halaman *Visi & Misi* (`#/visi-misi`, `pages/visi-misi.tsx`) tersimpan di server sejak [D-020](decisions.md#d-020--2026-10-13--visi--misi-tersimpan-di-server-unggahan-maklumat-ditunda) sebagai pengaturan `visiMisi`.

- Sebelum disimpan pertama kali, isinya adalah teks resmi yang sudah tampil di portal. Perubahan langsung tampil di halaman Visi & Misi portal (`GET /api/public/visi-misi`).
- Berkas maklumat (PDF bertanda tangan) diunggah ke server sejak [D-029](decisions.md#d-029--2026-10-14--foto-tentang-uptd-dan-maklumat-diunggah-ke-server-profil-disusun-dari-renstra-dan-rpjmd). Tanpa berkas, tombol unduh di portal mengarah ke halaman Regulasi & Unduhan.

## Tentang UPTD

Halaman *Tentang UPTD* (`#/profil`, `pages/profil.tsx`) tersimpan di server sejak [D-021](decisions.md#d-021--2026-10-14--tentang-uptd-tersimpan-di-server-unggahan-foto-ditunda) sebagai pengaturan `profil`.

- Sebelum disimpan pertama kali, isinya adalah teks awal dari Renstra BPAD dan RPJMD NTT, dengan nama Kepala UPTD dari pemilik. Perubahan langsung tampil di portal (`GET /api/public/profil`).
- Foto kantor dan foto Kepala UPTD diunggah ke server sejak [D-029](decisions.md#d-029--2026-10-14--foto-tentang-uptd-dan-maklumat-diunggah-ke-server-profil-disusun-dari-renstra-dan-rpjmd). Tanpa foto kantor, portal menampilkan foto gedung bawaannya; tanpa foto Kepala UPTD, ilustrasi.
- Isi awal disusun dari Renstra BPAD 2025–2029 dan RPJMD NTT 2025–2029.

## Persyaratan Layanan

Halaman *Persyaratan Layanan* (`#/layanan`, `pages/layanan/`) tersimpan di server sejak [D-022](decisions.md#d-022--2026-10-14--persyaratan-layanan-tersimpan-di-server-dengan-alur-persetujuan), dengan alur publikasi di server.

- Pengelola modul menyiapkan draf, mengajukan, memeriksa, dan meneruskan ke penyetuju. Administrator menyetujui-dan-menerbitkan, mengembalikan dari tahap persetujuan, menarik dari portal, mengubah urutan, dan menghapus. Penyunting terkunci bagi pegawai lain selama layanan menunggu persetujuan atau terbit.
- Hanya layanan terbit yang tampil di halaman Layanan dan, bila bertanda *Tampil di beranda*, sebagai tab *Layanan utama* di beranda (`GET /api/public/layanan`).
- Enam layanan yang sudah tampil di portal dimasukkan sebagai data awal berstatus Terbit lewat migrasi. Layanan contoh dari penyimpanan peramban tidak dipindahkan.

## Berita & Pengumuman

Halaman *Berita & Pengumuman* (`#/berita`, `pages/berita/`) tersimpan di server sejak [D-031](decisions.md#d-031--2026-10-14--berita--pengumuman-tersimpan-di-server-pengelola-berita-menerbitkan-sendiri) sebagai koleksi `berita`.

- Pengelola modul menjalankan semua langkah alur sendiri, termasuk menerbitkan dan menarik dari portal, dan boleh mengubah tulisan yang sudah terbit. Hanya Administrator yang menghapus.
- Foto sampul diunggah ke server (tujuan `berita`). Menandai *Jadikan tulisan utama* melepas tanda itu dari tulisan lain di server.
- Tulisan terbit tampil di halaman Informasi, di `/berita/{slug}`, dan, bila ditandai *Tampilkan di beranda*, di ubin Pengumuman papan layanan beranda (`GET /api/public/berita`).
- Hanya artikel peluncuran PINTU yang menjadi data awal, sebagai draf. Tulisan contoh dari penyimpanan peramban tidak dipindahkan.

## Dokumentasi Kegiatan

Halaman *Dokumentasi Kegiatan* (`#/dokumentasi`, `pages/dokumentasi.tsx`) tersimpan di server sejak [D-032](decisions.md#d-032--2026-10-14--dokumentasi-kegiatan-tersimpan-di-server-galeri-disusun-per-bulan) sebagai koleksi `dokumentasi`.

- Foto diunggah ke server (tujuan `dokumentasi`); urutannya di formulir adalah urutan tampil, dan foto pertama menjadi sampul di daftar. Kegiatan terbit wajib memiliki minimal satu foto.
- Pengelola modul menjalankan semua langkah alur sendiri, termasuk menerbitkan dan menarik dari portal. Hanya Administrator yang menghapus.
- Galeri portal (`GET /api/public/dokumentasi`) menampilkan foto per bulan; bagian Arsip berpindah bulan, atau tahun untuk tahun-tahun sebelumnya.
- Sembilan kegiatan contoh menjadi data awal berstatus Draf tanpa foto.

## Struktur Organisasi

Halaman *Struktur Organisasi* (`#/struktur`, `pages/struktur.tsx`) tersimpan di server sejak [D-033](decisions.md#d-033--2026-10-14--struktur-organisasi-tersimpan-di-server-nip-pejabat-tidak-ditampilkan-di-portal) sebagai koleksi `unit`, tanpa alur publikasi: perubahan langsung tampil di portal (`GET /api/public/struktur`).

- Pengelola modul menambah, menyunting, mengurutkan, dan menghapus unit. Server menolak susunan yang melingkar dan penghapusan unit yang masih membawahi unit lain.
- Urutan di daftar adalah urutan unit di bagan portal, dari kiri ke kanan. Foto pejabat diunggah ke server (tujuan `unit`) dan menggantikan inisial di bagan.
- NIP pejabat hanya untuk internal dan tidak dikirim ke portal.
- Ketujuh unit yang tampil di portal menjadi data awal; nama pejabat selain Kepala UPTD diisi setelah diverifikasi.

## Aplikasi & Situs

Halaman *Aplikasi & Situs* (`#/aplikasi`, `pages/aplikasi.tsx`, di bagian Alat Bantu) mengelola daftar aplikasi dan situs milik BPAD Provinsi NTT dan UPTD untuk halaman portal `aplikasi.html` ([D-054](decisions.md#d-054--2026-10-17--halaman-aplikasi--situs)), sebagai koleksi `aplikasi` di server tanpa alur publikasi: perubahan langsung tampil di portal (`GET /api/public/aplikasi`).

- Menu dan halamannya hanya bagi Administrator dan pegawai yang ditugasi modul *Aplikasi & Situs* (`AgencyAppPolicy`).
- Pengelola menambah, menyunting, mengurutkan (seret baris), dan menghapus aplikasi. Isian: logo (unggahan tujuan `aplikasi`, inisial bila kosong), nama, nama lengkap, keterangan, jenis (*Situs web* atau *Android (Google Play)*), pengelola (*BPAD Provinsi NTT* atau *UPTD Pendapatan Daerah Wilayah Kota Kupang*), dan tautan. Aplikasi Android harus ditautkan ke halamannya di Google Play.
- Lima aplikasi yang didaftarkan pemilik menjadi data awal (PINTU, PRO NTT, SIKAP, SIPAS, Tilang / Razia) dengan logo dari sumber masing-masing di `public/assets/img/aplikasi/`; logo itu bisa diganti dengan unggahan.

## Regulasi & Unduhan

Halaman *Regulasi & Unduhan* (`#/unduhan`, `pages/unduhan.tsx`) tersimpan di server sejak [D-030](decisions.md#d-030--2026-10-14--regulasi--unduhan-tersimpan-di-server-dengan-alur-persetujuan) sebagai koleksi `dokumen`, dengan alur publikasi yang sama dengan Persyaratan Layanan.

- Berkas diunggah ke server (tujuan `dokumen`). Format dokumen mengikuti jenis berkasnya; dokumen tanpa berkas dapat menautkan sumber resmi seperti JDIH.
- Pengelola modul menyiapkan, mengajukan, dan memeriksa. Administrator menerbitkan, menarik, dan menghapus. Penyunting terkunci bagi pegawai lain selama dokumen menunggu persetujuan atau terbit. Dokumen terbit wajib memiliki berkas atau tautan.
- Sembilan dokumen yang tercantum di portal menjadi data awal berstatus Draf tanpa berkas, dengan judul resmi untuk regulasinya. Portal menampilkan "Dokumen sedang disiapkan" sampai ada dokumen terbit (`GET /api/public/dokumen`).

## Beranda

Halaman *Beranda* (`#/beranda`, `pages/beranda.tsx`) tersimpan di server sejak [D-023](decisions.md#d-023--2026-10-14--teks-beranda-tersimpan-di-server-teks-html-tetap-sebagai-isi-awal) sebagai pengaturan `beranda`.

- Teks yang tertulis di HTML beranda menjadi isi awal dan tetap tampil sampai dokumen pertama kali disimpan; setelah itu beranda memakai isi dari server (`GET /api/public/beranda`). Bila API gagal, teks HTML yang tampil.
- Ikon butir nilai utama dan peta kanal mengikuti urutannya; butir tambahan memakai ikon umum.
- **Kata bergilir** di bagian Pembuka ([D-045](decisions.md#d-045--2026-10-15--portal-beralih-ke-react-bertahap-kata-bergilir-di-judul-pembuka)): kata di judul yang sama dengan salah satu kata di daftar bergulir ke kata-kata lain sesuai urutan sampai kembali ke kata judul, diam 10 detik, lalu berulang. Isi 2–8 kata (maks. 24 karakter, tanpa kata ganda) dan pastikan judul memuat salah satunya; kosongkan daftar untuk judul tanpa animasi. Aturan yang sama diperiksa server.

## Halaman Statis

Halaman *Halaman Statis* (`#/halaman`, `pages/halaman/`) tersimpan di server sejak [D-024](decisions.md#d-024--2026-10-14--halaman-statis-tersimpan-di-server-teks-html-tetap-sebagai-isi-awal) sebagai koleksi `halaman`.

- Hanya Kebijakan Privasi dan Syarat & Ketentuan, karena setiap halaman adalah halaman portal: halaman disunting, tetapi tidak ditambah atau dihapus, dan nama berkasnya terkunci.
- Teks yang tertulis di HTML kedua halaman menjadi isi awal dan tetap tampil sampai halaman pertama kali disimpan; setelah itu portal memakai isi dari server (`GET /api/public/halaman/{slug}`). Bila API gagal, teks HTML yang tampil.
- Server hanya menerima HTML seperti yang ditulis editor; HTML lain ditolak dengan pesan galat.

## Jadwal Pemutakhiran

Halaman *Jadwal Pemutakhiran* (`#/pemutakhiran`, `pages/pemutakhiran.tsx`) tersimpan di server sejak [D-025](decisions.md#d-025--2026-10-14--jadwal-pemutakhiran-tersimpan-di-server) sebagai koleksi `pemutakhiran`.

- Delapan standar yang sebelumnya tampil di dashboard menjadi data awal di server.
- Pengelola Jadwal Pemutakhiran menambah, menyunting, mengurutkan, dan menghapus standar. Pegawai lain hanya menerima standar modulnya untuk Ringkasan.
- Waktu *terakhir diperbarui* tetap dihitung dari modul yang dipantau; untuk modul yang masih di IndexedDB, waktunya berasal dari peramban pegawai.

## Pengaturan Situs

Tab *Situs* di halaman *Pengaturan* (`#/pengaturan?tab=situs`, `pages/pengaturan.tsx`) tersimpan di server sejak [D-026](decisions.md#d-026--2026-10-14--pengaturan-situs-tersimpan-di-server-hanya-administrator-yang-menyimpan) sebagai pengaturan `situs`.

- Hanya Administrator yang menyimpan; pegawai lain melihat formulirnya dalam keadaan hanya-baca. Nama portal dan nama yang dikenal tetap dipakai sidebar, dan alamat portal dipakai tautan *Lihat di portal*.
- Deskripsi dan hak cipta tampil di kaki halaman portal, dan bilah pengumuman di halaman Informasi (`GET /api/public/situs`). Teks di HTML portal tetap tampil sampai pengaturan pertama kali disimpan. Nama di kepala halaman dan judul tab tetap mengikuti HTML.

## Profil akun

Tab *Akun* di halaman *Pengaturan* (`#/pengaturan?tab=akun`) tersimpan di server sejak [D-027](decisions.md#d-027--2026-10-14--profil-akun-tersimpan-di-server-nama-dan-surel-mengikuti-akun) sebagai pengaturan `akun`: profil akun yang sedang masuk.

- Nama dan surel mengikuti akun dan tampil hanya-baca; Administrator mengubahnya di *Akun Pegawai*. Jabatan, unit, dan peran dalam pengelolaan informasi diisi pegawai sendiri, dan unit tampil di menu pengguna.
- Riwayat alur publikasi dan log aktivitas untuk modul yang masih di IndexedDB memakai nama akun yang sedang masuk (`sesi().nama`), sama seperti modul di server.

## Pesan & Pengaduan

Halaman *Pesan & Pengaduan* (`#/pesan`, `pages/pesan.tsx`) tersimpan di server sejak [D-016](decisions.md#d-016--2026-10-13--pesan--pengaduan-tersimpan-di-server). Formulir Kontak di portal mengirim pesan ke `POST /api/public/pesan`.

- Membuka pesan baru menandainya *Diproses*. Tanggapan ditambahkan dari panel detail dan dicatat atas nama akun yang masuk; isi pesan dan tanggapan lama tidak bisa diubah.
- Hapus, per baris maupun massal, hanya untuk Administrator. Tombol *Hapus semua pesan* di *Pengaturan › Data* tidak tampil selama pesan tersimpan di server.
- Pesan contoh dari penyimpanan peramban tidak dipindahkan ke server.

## Tema

Tab *Tampilan* di halaman *Pengaturan* (`#/pengaturan?tab=tampilan`, `components/tema/tab-tampilan.tsx`) mengatur tema portal dan dashboard masing-masing sebagai pengaturan `tema` ([D-057](decisions.md#d-057--2026-10-17--tema-portal-dan-dashboard-rencana-dan-keputusan-awal), [rencana](rencana-tema-situs.md)).

- Hanya Administrator yang menyimpan; pegawai lain melihat formulirnya hanya-baca. Sub-tab *Portal* dan *Dashboard* masing-masing berisi tema siap pakai (`TEMA_SIAP_PAKAI` di `lib/tema.ts`: PINTU, Samudra, Hutan, Senja, Tenun, Grafit; "Kustom" bila pilihan tidak cocok dengan satu pun), warna utama, nuansa latar, *Warna gelap* (latar tombol gelap dan navigasi portal, tombol utama dashboard; pemilih warna bebas dengan contoh Hitam, Dari warna utama, Biru malam, Hijau tua, Cokelat kopi, Ungu terong; disesuaikan server agar tetap gelap; panel gelap dan warna teks tidak berubah; tema siap pakai tidak mengubahnya), huruf teks dan judul, *Ketebalan judul* (Semi-tebal 600, Tebal 700, Ekstra tebal 800; saat huruf judul diganti, ketebalan diisi saran untuk huruf itu, mis. Plus Jakarta Sans → Tebal; di dashboard judul kartu, dialog, dan lembar samping ikut huruf dan ketebalan judul; teks tetap 400), kelengkungan sudut, skala tampilan (90–120%; memilih tema siap pakai tidak mengubah skala maupun mode), *Mode tampilan* portal (Terang/Gelap untuk semua pengunjung; contoh portal dan daftar kontras ikut), *Keterbacaan* (rasio kontras dari server), dan *Kembalikan tampilan bawaan PINTU*; bagian Dashboard juga *Samakan dengan portal* (termasuk ketebalan judul, tanpa mode, karena mode gelap dashboard pilihan tiap pegawai).
- *Pratinjau di portal* (Administrator) membuat tautan pratinjau 30 menit lalu membukanya di tab baru (bila pemblokir pop-up menolak, toast menampilkan tombol Buka); portal di peramban itu memakai tema yang belum disimpan dengan pita penanda. *Akhiri pratinjau* menghapusnya; menyimpan tema juga mengakhirinya.
- Kartu *Riwayat* (Administrator) menampilkan lima tema terakhir yang disimpan beserta penyimpannya (`useRiwayatTema`); *Pakai lagi* memuat tema itu ke formulir untuk disimpan ulang.
- Pratinjau dari `POST /api/pengaturan/tema/pratinjau` (ditunda 250 ms selama warna diubah). Bagian Portal menampilkan contoh portal (`components/tema/pratinjau-portal.tsx`) dengan variabel berawalan `--p-`, karena nama variabel portal (mis. `--accent`) juga dipakai dashboard. Dashboard yang sedang dibuka menjadi pratinjau tema dashboard: `<link>` tema tersimpan dinonaktifkan dan stylesheet pratinjau disisipkan (`pratinjauDashboard`), lalu dikembalikan saat tab ditinggalkan. Sesudah disimpan, `/tema-dashboard.css` dimuat ulang tanpa memuat ulang halaman (`muatUlangTemaDashboard`).
- Logo tetap biru PINTU apa pun warna utamanya.

Dashboard dan Absensi memuat `/tema-dashboard.css` sesudah CSS hasil build. Agar tema bisa menimpa tampilan:

- Warna ditulis lewat variabel di `index.css` (`:root` dan `.dark`), bukan heksadesimal di kelas. Pratinjau Papan Layanan memakai `bg-night`, `bg-night-2`, `text-night-muted`, `text-night-accent`, `text-ok-night`, `text-warn-night`, dan `text-danger-night`; teks di atas warna merek memakai `text-brand-foreground`.
- `--font-sans` dan `--font-display` ada di blok `@theme` biasa (bukan `@theme inline`), sehingga kelas `font-*` membaca variabelnya.
- Ukuran ditulis dalam `rem` atau skala Tailwind (mis. `h-62.5`, `text-[0.6875rem]`), bukan `px`, agar ikut skala tampilan. Pengecualian: garis dan cincin fokus di `components/ui`, serta label peta yang ukurannya dihitung `peta.ts`.

## Pemeriksaan sebelum commit

```bash
npm run typecheck && npm run lint && npm run build && composer test
```
