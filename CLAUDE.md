# PINTU — ringkasan untuk Claude Code

Ringkasan singkat per 2026-10-21. Status terbaru, rincian, dan riwayat ada di [`KONTEKS-PROYEK.md`](KONTEKS-PROYEK.md): baca lebih dulu, dan perbarui di commit yang sama dengan setiap perubahan (prosedurnya di §19). Bila ada yang bertentangan, ikuti urutan ini: instruksi pemilik, lalu [`AGENTS.md`](AGENTS.md) (batasan alat AI), [`docs/decisions.md`](docs/decisions.md) (keputusan D-001 dst.), `KONTEKS-PROYEK.md`, baru berkas ini. Jangan menulis rahasia di berkas ini.

## Tujuan dan arsitektur

PINTU (Portal Informasi dan Pelayanan) adalah portal informasi dan pelayanan pajak kendaraan milik UPTD Pendapatan Daerah Wilayah Kota Kupang (Samsat Kota Kupang), BPAD Provinsi NTT. Pemakainya warga wajib pajak (portal) dan pegawai UPTD (dashboard, absensi). Bahasa produknya Indonesia, dengan waktu acuan WITA (`Asia/Makassar`).

Semuanya satu aplikasi Laravel di root repository, satu origin (D-002):

| Bagian | Alamat | Isi |
|---|---|---|
| Portal publik | `/`, `/layanan`, `/penerimaan`, `/pemetaan`, … | Blade berisi HTML statis (`resources/views/portal`) + `public/assets/js/main.js`; komponen React di `[data-react]` (D-045) |
| Dashboard | `/dashboard` (rute hash `#/…`) | SPA React di `resources/js/dashboard` |
| Absensi (PWA) | `/absen` | React, akun yang sama dengan dashboard |
| API JSON | `/api/…` | Laravel + Sanctum (sesi cookie SPA) |

- Produksi: https://uptdpenda-kupang.web.id di Laravel Cloud, region Singapura, MySQL 8.4, bucket privat dan publik. Push ke `main` langsung di-deploy (Push to deploy, deploy menjalankan `php artisan migrate --force`). Cloudflare hanya untuk DNS dan pengalihan `pintu.` → domain utama.
- Database produksi terpisah dari lokal: data yang diisi di lokal tidak sampai ke produksi.
- Modul utama: konten portal (Beranda, Layanan, Berita, Regulasi, dll.), Layanan Luar Kantor, Antrean Online (D-051), Pemetaan PKB (D-052, D-064), Laporan Penerimaan dari berkas STS (D-047–D-049), Realisasi Target Form I (D-068, D-070), Nomoratur SKPD (D-071), tema (D-057), dan Kalkulator PKB + API NJKB (D-041).

## Stack, versi, dan perintah

- **Backend:** PHP 8.3, Laravel 13.35, Sanctum 4.3, league/flysystem-aws-s3-v3. **Dev:** PHPUnit 12.5 (bukan Pest), Pint, Laravel Boost 2.10.
- **Frontend:** React 19.3, react-router 8.4 (hash), TanStack Query 5, React Hook Form 7 + Zod 4.6, radix-ui/shadcn, Vite 8.3, TypeScript 6.0, Tailwind CSS 4.3, oxlint.
- **Data:** MySQL 8 (database lokal `pintu`, kredensial di `.env`); SQLite di memori untuk tes.
- **Mesin pemilik:** Windows 11, Laravel Herd (`https://portal-uptdkotakupang.test`), Node 24, PowerShell dan Git Bash.

| Perintah | Kegunaan |
|---|---|
| `composer test` | Semua tes PHPUnit di SQLite memori (±35 detik, 1640 tes) |
| `php artisan test --compact <berkas> --filter=<tes>` | Tes tertentu |
| `composer test:mysql` | **Mereset database `pintu`** (berisi data pemilik); hanya dengan izin pemilik dan cadangan |
| `npm run typecheck` / `npm run lint` / `npm run build` | `tsc -b` / `oxlint resources/js` / `tsc -b && vite build` |
| `npm run dev` | Server Vite (dijalankan pemilik) |
| `vendor/bin/pint --format agent <berkas>` | Format PHP, hanya berkas yang diubah |
| `node scripts/sync-partials.mjs --check` | Cek header/footer portal yang disalin ke semua halaman |
| `php artisan pintu:create-admin`, `pintu:activation-link`, `pintu:export-production`, `pintu:copy-uploads`, `pintu:rewrite-upload-urls` | Akun dan pindah data ke produksi |

Build di Laravel Cloud: `composer install --no-dev && npm ci && npm run build && php artisan optimize`.

## Cara kerja dengan pemilik

- Komunikasi dan laporan dalam Bahasa Indonesia. Keputusan penting ditanyakan dengan pilihan ganda (pilihan yang disarankan ditulis pertama, berlabel "(Recommended)"); hal yang bisa diputuskan dari kode tidak perlu ditanyakan.
- Kerjakan satu irisan utuh (server, dashboard, portal, tes, dokumentasi), verifikasi, lalu laporkan dan tutup dengan **"Commit dan Push?"**.
- **"Commit dan Push" berarti langsung tayang (D-069):**
  1. commit di `laravel-development`;
  2. `git push origin laravel-development`;
  3. `git merge-base --is-ancestor main HEAD`;
  4. `git push origin laravel-development:main`;
  5. `git fetch origin main:main`.

  Bila `main` tidak bisa dimajukan langsung, berhenti dan tanya. Persetujuan berlaku untuk satu kali itu saja. Tes, typecheck, dan build harus lulus sebelum commit.
- Setiap commit memperbarui `KONTEKS-PROYEK.md`:
  - kepala berkas;
  - §3 Status;
  - bagian yang terdampak;
  - §16 bila ada keputusan baru;
  - §17: hash commit sebelumnya dan baris baru;
  - §18;
  - §20 log.

  Keputusan baru pemilik dicatat sebagai D-0xx di `docs/decisions.md`.
- Pesan commit memakai Conventional Commits berbahasa Inggris (`feat(pintu): …`, `fix(pintu): …`, `docs(pintu): …`). Badannya menjelaskan keputusan dan diakhiri trailer `Co-Authored-By`.
- Sesi lain bisa bekerja di checkout yang sama. Periksa `git log -3` dan `git diff` per berkas sebelum staging.

## Batasan penting

- Pemilik login sendiri. Jangan mengetik kata sandi atau kredensial, dan jangan mencetak isi `.env`, token, atau NIP akun uji.
- Berkas STS dan Data Potensi (`docs/Data Penerimaan/`, diabaikan git) berisi nama, nomor polisi, dan alamat. Jangan di-commit, dan laporkan angka ringkasan saja.
- Jangan menjalankan SQL, migrasi, reset, atau seed ke database yang tidak ditunjuk. `pintu` adalah database dev yang ditunjuk, tetapi isinya data pemilik.
- Jangan menekan *Simpan* pada data pemilik saat menguji di dashboard lokal. Aksi di dashboard produksi hanya atas permintaan pemilik.
- Jangan menghentikan `npm run dev` milik pemilik; minta pemilik melakukannya. Minta izin sebelum memakai alat MCP Laravel Boost.
- Jangan memperkenalkan Next.js, Inertia, Livewire, Pest, PostgreSQL, Workers, atau penyedia database baru.

## Konvensi kode

- **Bahasa:**
  - teks UI, pesan galat, dan dokumentasi berbahasa Indonesia;
  - nama kelas dan pesan commit berbahasa Inggris;
  - kolom database dan properti JSON berbahasa Indonesia (`judul`, `tanggalMulai`, `aktif`);
  - nama fungsi dan variabel frontend berbahasa Indonesia (`muatJudul`).
- **PHP:**
  - selalu pakai kurung kurawal, constructor property promotion, tipe kembalian eksplisit, dan PHPDoc dengan array shape;
  - validasi di FormRequest, otorisasi di Policy, logika bisnis di `app/Actions`;
  - buat berkas dengan `php artisan make:… --no-interaction`; model baru disertai factory.
- **API:**
  - JSON polos tanpa amplop `data` (`JsonResource` tanpa pembungkus), ULID huruf kecil, tanpa paginasi kecuali disetujui;
  - galat 422 berbahasa Indonesia;
  - semua rute `Cache-Control: no-store`;
  - API publik tanpa sesi atau cookie, menolak query parameter yang tidak terdaftar, dibatasi 300 permintaan/menit/IP.
- **Log aktivitas:** ditulis ke tabel `activities` dalam transaksi yang sama dan hanya bila ada perubahan. Data pribadi tidak pernah masuk log.
- **Dashboard:**
  - TypeScript ketat; Zod dengan batas yang sama dengan server;
  - hook TanStack Query di `lib/*.ts`, rute di `app/router.tsx`, komponen shadcn di `components/ui`;
  - ukuran dalam `rem` atau skala Tailwind.
- **Portal:**
  - pertahankan markup dan copy halaman;
  - header dan footer disalin dengan `scripts/sync-partials.mjs`;
  - CSS dan JS di `public/assets` tidak berversi.
- **Tes:**
  - setiap perilaku baru dites beserta mode gagalnya;
  - logika baru diuji mutasi: ubah satu syarat, pastikan tes gagal, lalu pulihkan;
  - mutan yang bertahan berarti tes kurang, atau kodenya mubazir.

## Keputusan desain dan alasannya

- **React SPA + JSON API dalam satu aplikasi Laravel (D-001, D-002):** tanpa CORS, sesi cookie Sanctum, dan satu deploy.
- **Portal berupa HTML lengkap dari server yang diisi API publik (D-023):** teks HTML menjadi isi awal yang terbaca mesin pencari dan tetap tampil bila API gagal.
- **React di portal dipasang bertahap di atas Blade (D-045):** tanpa SPA atau SSR.
- **Impor STS tanpa identitas wajib pajak (D-047):** transaksi dikenali lewat sidik HMAC (nopol, tanggal, jenis daftar, jumlah) dengan `APP_KEY`, jadi impor ulang tidak menggandakan penerimaan. Karena itu `APP_KEY` produksi sama dengan lokal (D-065).
- **Angka publik disamarkan di server:**
  - kelompok di bawah 3 transaksi pada Laporan Penerimaan portal (D-048, D-049);
  - angka 1–2 kendaraan di Pemetaan PKB, termasuk angka yang bisa dipakai menghitungnya balik (D-052).

  Identitas wajib pajak hanya ada di dashboard, untuk sasaran penagihan.
- **Produksi tetap MySQL, bukan Cloudflare D1 (D-065):** D1 dari luar Workers hanya lewat REST berbatas, tanpa transaksi interaktif, dan tanpa driver Laravel.
- **Form I (D-068, D-070):** realisasi dihitung dari STS semua loket, tanpa opsen. Saldo awal diisi manual untuk bulan sebelum STS lengkap, dan transaksi di bulan-bulan itu tidak dihitung. Ada satu target per tahun. Laporan bisa dibatasi sampai tanggal tertentu.
- **Nomoratur (D-071):**
  - nomor selalu 7 digit; nol di depan yang hilang di Excel dikembalikan;
  - `-` berarti tanpa SKPD, dan `null` berarti diimpor sebelum D-071;
  - impor ulang hanya melengkapi nomoratur yang kosong;
  - "terlewat" hanya dihitung untuk celah ≤ 50 nomor, karena lompatan lebih lebar dianggap tumpukan formulir lain;
  - CSV menulis `="0012345"` agar Excel mempertahankan nol.
- **Tema (D-057):** stylesheet dibuat server di `/tema-portal.css` dan `/tema-dashboard.css`, dengan huruf yang di-host sendiri.
- **Gulir halus Lenis (D-072):**
  - Lenis 1.3.27 dilayani sendiri dari `public/assets/vendor/lenis-1.3.27/`, hanya di halaman portal berheader;
  - tidak berjalan saat gerak dikurangi, dan sentuhan tetap native;
  - gulir dari kode lewat `window.pintuScrollTo` (React: `gulirKe()` di `resources/js/portal/gulir.ts`);
  - posisi tujuan sudah memperhitungkan `scroll-padding-top`, jadi jangan menambah offset sendiri;
  - versi baru berarti folder baru, karena aset portal tidak berversi.

## Masalah yang sudah diselesaikan

| Masalah | Solusi |
|---|---|
| Browser pane Claude Desktop putih saat `npm run dev` berjalan (origin `:5173` diblokir, `ERR_BLOCKED_BY_CLIENT`) | Pemilik menghentikan `npm run dev`, lalu agen menjalankan `npm run build` (atau menyisipkan skrip hasil build ke halaman) |
| `vite build` gagal karena memori commit Windows habis | `RAYON_NUM_THREADS=1 TOKIO_WORKER_THREADS=1 NODE_OPTIONS=--max-old-space-size=768 node node_modules/vite/bin/vite.js build` |
| Herd menolak berkas STS bulanan (`upload_max_filesize` 2 MB) | `php artisan serve` sementara, atau naikkan batas PHP; produksi perlu ≥ 10 MB |
| Akhir baris campuran (repo LF, sebagian working tree CRLF) | Pakai alat Edit, hindari `sed -i` di Git Bash, jalankan `git diff --check`; Pint merapikan CRLF dari tulisan Python |
| Garis miring terbalik hilang di heredoc atau `node -e` | Tulis skrip ke berkas di scratchpad |
| `String.replace` JS menafsirkan `$&`, `$$` | Pakai `split(a).join(b)` |
| Kolom JSON MySQL menyusun ulang kunci (Urungkan Antrean gagal) | Bandingkan setelah `ksort` |
| Tes: `JsonResource` menjawab 201 untuk model baru; JSON menulis `15.0` sebagai `15` | Pakai `assertCreated`; harapkan bilangan bulat |
| Halaman dashboard melebar | `grid-cols-[minmax(0,1fr)]`; `min-w-0` di `SidebarInset`; `whitespace-normal` di sel tabel shadcn; wadah gulir `relative` untuk `sr-only` |
| Panel browser tersembunyi tidak menjalankan transisi atau `ResizeObserver` | Matikan transisi lewat CSS sisipan, ukur dengan `getBoundingClientRect` |
| Panel browser tersembunyi juga tidak menjalankan `requestAnimationFrame`, jadi Lenis diam | Uji di Edge headless lewat CDP (`--headless=new --remote-debugging-port`); hentikan proses Edge uji menurut folder profilnya, bukan semua `msedge.exe` |
| Hash saat dibuka meleset dengan Lenis karena isi muncul di atas tujuan selama animasi | Lompat langsung (`immediate`), lalu scroll anchoring peramban yang menahan posisinya |
| Worktree dengan `vendor` berupa junction menguji kode checkout utama | Jalankan PHPUnit dengan `--bootstrap` sementara yang memuat autoloader worktree |
| Form I produksi berbeda dari contoh pemilik | Berkas Loket SAMSAT Oktober belum diimpor, dan contoh dibuat per 7 Oktober; diselesaikan dengan pilihan *Sampai tanggal* (D-070) |

## TODO / pekerjaan terbuka

Daftar lengkapnya di `KONTEKS-PROYEK.md` §15 dan §18.

- **Nomoratur produksi:**
  - Oktober sudah lengkap;
  - 15.281 transaksi September menunggu impor ulang berkas STS September;
  - cek ke petugas: nomor ganda lintas loket (terutama Loket SAMSAT dan SAMSAT Corner) dan 1.548 nomor terlewat di Loket SAMSAT yang tidak muncul di STS mana pun.
- **Checklist go-live:**
  - rate limiting di edge (butuh paket Growth);
  - *Scheduler* (`model:prune`), ditunda pemilik;
  - `upload_max_filesize` ≥ 10 MB;
  - konfirmasi lokasi data ke unit hukum (PP 71/2019, UU 27/2022);
  - cadangan bucket dan database;
  - uji Absensi di HTTPS.
- **Pekerjaan lain:**
  - pemilik mencoba rasa gulir Lenis dengan mouse, touchpad, dan ponsel di situs lokal Herd atau produksi (D-072);
  - Kebijakan Privasi perlu menyebut Google Maps di halaman Kontak (D-063);
  - persyaratan balik nama dan mutasi masuk perlu dicocokkan petugas dengan papan loket (D-061, D-062);
  - Antrean Online: keputusan tersisa, perangkat, uji QR, kamera, dan suara, uji coba paralel, lalu Irisan 5–6.
- **Integrasi:**
  - data pegawai dari API SIKAP;
  - uji cek kendaraan NJKB dengan kendaraan sungguhan;
  - jam mulai layanan BPAD (`NJKB_SERVICE_OPENS_AT`).
- **Konten draf:** regulasi, dokumentasi, artikel peluncuran, statistik berstatus Ilustrasi, foto dan nama pejabat.
- **Fitur belum dibuat:** ganti atau lupa sandi sendiri, MFA, notifikasi pesan, masa simpan pesan dan swafoto, statistik per rentang, ekspor CSV sasaran penagihan (perlu keputusan karena berisi data pribadi).
- **Utang kecil:**
  - kalimat lama "IndexedDB" di `docs/dashboard.md`;
  - entri usang di `.gitignore`;
  - batas login 30/menit per IP untuk kantor yang memakai satu IP.
