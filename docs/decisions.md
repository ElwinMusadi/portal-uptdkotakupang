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
