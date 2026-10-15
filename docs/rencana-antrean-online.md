# Rencana Implementasi — Antrean Online (Queue Management System)

**Status:** disetujui pemilik; **Irisan 1–4 selesai dibangun** (lihat §6); berikutnya uji coba paralel di loket. Keputusan pemilik dicatat di [D-051](decisions.md#d-051--2026-10-15--antrean-online-rencana-dan-keputusan-awal).
**Sumber:** flowchart pemilik *Flowchart Queue Management System* (draw.io, 11 jalur) dan tanya jawab dengan pemilik pada 15 Oktober 2026.

Dokumen ini memakai kata baku **antrean** (seperti di Papan Layanan: "Nomor antrean dilayani").

---

## 1. Tujuan dan ruang lingkup

Saat ini nomor antrean di Loket SAMSAT Kota Kupang ditulis tangan oleh petugas Meja Informasi setelah berkas diverifikasi. Wajib pajak (WP) tidak tahu berkasnya sedang di tahap mana, dan bila berkas atau kertas nomor tercecer, sulit dilacak.

Yang dibangun:

| Kebutuhan | Bentuknya |
|---|---|
| Nomor tercetak | Meja Informasi mengetik nomor polisi dan memilih layanan; sistem mencetak **2 lembar** di printer thermal 80 mm: lembar 1 untuk WP, lembar 2 ditempel ke berkas. Keduanya ber-QR. |
| Antrean bersambung | Satu nomor mengikuti berkas dari Meja Informasi sampai Loket 2 – Penyerahan. Setiap tahap punya daftarnya sendiri; nomor yang selesai di satu tahap otomatis masuk daftar tahap berikutnya. |
| Pemanggilan | Loket 1 – Pendaftaran, Loket Kwitansi, dan Loket 2 – Penyerahan memanggil nomor; panggilan tampil di **layar TV** ruang tunggu dengan **suara**. |
| Tahap di belakang layar | Penetapan, Pelunasan, Cetak SKPD, dan Cetak STNK menandai tahapnya selesai, dengan **memindai QR** di berkas atau memilih dari daftar (memindai tidak wajib). |
| Lacak mandiri | WP memindai QR di lembar 1 dan melihat tahap berkasnya dari ponsel, tanpa bertanya ke petugas. |
| Arsip | Semua tiket beserta nomor polisi dan riwayat tahapnya tersimpan untuk mencari berkas yang tercecer. |

Di luar ruang lingkup tahap ini (bisa menyusul): mengambil nomor dari rumah, notifikasi WhatsApp/SMS, pengambilan status otomatis dari API Samsat Online (Irisan 6), dan Papan Layanan beranda yang diisi dari antrean (Irisan 5).

---

## 2. Alur yang dibangun

### 2.1 Tahap

Diturunkan dari flowchart. **Dipanggil** berarti nomor dipanggil ke loket (tampil di layar TV); **ditandai** berarti petugas menandai tahapnya selesai.

| Tahap | Pos (jalur flowchart) | Cara | Yang dilihat WP lewat QR |
|---|---|---|---|
| Cetak | Meja Informasi + Queue Management System | Petugas mencetak tiket setelah berkas lengkap | "Menunggu dipanggil di Loket Pendaftaran" |
| Pendaftaran | Loket 1 – Pendaftaran (proses di aplikasi ERI) | Dipanggil, lalu berkas diteruskan ke penetapan | "Dipanggil ke Loket 1" / "Berkas didaftarkan" |
| Penetapan | Petugas Penetapan (Samsat Online); bila ada perubahan data, Petugas Server | Ditandai; bisa diberi tanda **Perlu perubahan data** | "Berkas sedang ditetapkan" / "Menunggu perubahan data" → **Sudah Penetapan** |
| Kwitansi | Loket Kwitansi | Dipanggil, kwitansi diserahkan | "Dipanggil ke Loket Kwitansi" |
| Pembayaran | Loket Bank (teller) → Petugas Pelunasan (Samsat Online) | Tidak ada tindakan di sistem untuk teller; Pelunasan menandai | "Silakan membayar di bank" → **Sudah Pembayaran** |
| Cetak SKPD | Petugas Cetak SKPD | Ditandai | "Notice pajak sedang dicetak" → **Sudah Cetak Notice** |
| Cetak STNK | Petugas Cetak STNK | Ditandai | "STNK sedang dicetak" |
| Penyerahan | Loket 2 – Penyerahan (pengesahan STNK, berkas diarsipkan) | Dipanggil, lalu ditandai selesai | "Dipanggil ke Loket 2" → **Selesai Diproses** |

Empat status di jalur *Queue Management System* pada flowchart (Sudah Penetapan, Sudah Pembayaran, Sudah Cetak Notice, Selesai Diproses) menjadi tanda selesainya tahap Penetapan, Pembayaran, Cetak SKPD, dan Penyerahan.

### 2.2 Layanan dan urutan tahapnya

Urutan tahap **disimpan per layanan dan bisa diubah Administrator** di dashboard, sehingga perubahan alur di loket tidak perlu mengubah program. Isi awalnya mengikuti jawaban pemilik:

| Kode | Layanan | Pendaftaran | Penetapan | Kwitansi | Pembayaran | Cetak SKPD | Cetak STNK | Penyerahan |
|---|---|---|---|---|---|---|---|---|
| **A** | Registrasi Ulang / Perpanjangan STNK 5 Tahun, Duplikat STNK | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **B** | Pengesahan / Pajak Tahunan | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **C** | BBN / Mutasi | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| **D** | Pembayaran Online | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ |

Catatan untuk diperiksa ulang saat uji coba: flowchart hanya mengirim RU ke Cetak STNK, sedangkan jawaban pemilik menyertakan B dan tidak menyertakan C; antrean D (sudah membayar online) tetap melewati Kwitansi dan Pembayaran. Karena urutannya bisa diatur, koreksi cukup dilakukan di dashboard.

---

## 3. Keputusan pemilik (D-051)

| Hal | Keputusan |
|---|---|
| Kode layanan | Sesuai flowchart: A Registrasi Ulang (termasuk Duplikat STNK), B Pengesahan, C BBN/Mutasi; ditambah **D Pembayaran Online** |
| Format nomor | **A-001**: huruf layanan, tanda hubung, tiga digit; mulai dari 001 setiap hari (WITA) |
| Ambil nomor | Hanya di Meja Informasi, setelah berkas diverifikasi lengkap |
| Status tahap | Dipindai dari QR berkas (atau dipilih dari daftar; memindai tidak wajib) di **setiap pos**; API Samsat Online menyusul bila BPAD memberi akses |
| Pemanggilan | Layar TV + suara |
| Akun | Akun pribadi per petugas |
| Nomor polisi | Disimpan **tanpa batas waktu** untuk mencari berkas atau nomor yang tercecer; hanya Administrator yang bisa menghapusnya. Tampil **apa adanya** (tidak disamarkan) di halaman QR dan layar TV |
| Lintas hari | Tiket yang belum selesai tetap aktif dengan nomor lamanya dan ditandai tanggalnya (mis. A-001 · 14 Okt) |
| Papan Layanan | Tetap manual (D-050); diambil dari antrean setelah antrean benar-benar berjalan |
| Printer | Thermal 80 mm |
| Mulai pakai | Uji coba paralel dengan nomor manual |

---

## 4. Rancangan sistem

Mengikuti arsitektur PINTU yang ada: Laravel 13 + MySQL, API JSON, dashboard React, portal Blade dengan komponen React (D-045). Tidak ada layanan atau penyedia baru.

### 4.1 Data

Diturunkan dari tabel *Queues, Services, Counters, Users* di flowchart, ditambah riwayat agar setiap tahap dan petugasnya tercatat.

| Tabel | Isi |
|---|---|
| `queue_services` (layanan) | `kode` (A–D, unik), `nama`, `cakupan` (teks, mis. "Registrasi Ulang, Duplikat STNK"), `tahap` (daftar berurutan), `digit` (3), `aktif`, `urutan` |
| `queue_counters` (loket/pos) | `nama` (mis. "Loket 1A", "Loket Kwitansi", "Cetak STNK"), `tahap`, layanan yang dilayani (kosong = semua), `aktif`, `nama_suara` (cara dibacakan di layar) |
| `queue_tickets` (tiket) | `id` (ULID), `kode_lacak` (acak, ±22 karakter, untuk QR), `queue_service_id`, `tanggal` (WITA), `urut`, `nomor` ("A-001"), `nopol`, `tahap` saat ini, `status` (menunggu, dipanggil, ditunda, diproses, perlu perubahan data, selesai, batal), `queue_counter_id` terakhir, `dicetak_oleh`, waktu cetak, waktu selesai/batal, `alasan_batal` |
| `queue_events` (riwayat) | `queue_ticket_id`, `tahap`, `aksi` (dicetak, dipanggil, dipanggil ulang, ditunda, tahap selesai, perlu perubahan data, dibatalkan, selesai, nopol dihapus), `queue_counter_id`, `user_id`, `otomatis` (tahap yang dilewati tanpa dipindai), waktu |
| `queue_daily_numbers` | nomor terakhir per layanan per tanggal, dikunci (`lockForUpdate`) saat mencetak agar dua petugas tidak mendapat nomor yang sama |
| `queue_displays` (layar) | nama layar, hash token perangkat, waktu terakhir terlihat, dicabut |

Tanggal dan "hari ini" selalu WITA, seperti modul lain. `nopol` disimpan apa adanya (dinormalisasi huruf besar dan spasi), diindeks untuk pencarian.

### 4.2 Penomoran

- Nomor = kode layanan + "-" + urutan tiga digit per layanan per tanggal WITA: A-001, A-002, …, B-001, … Urutan dimulai lagi dari 001 setiap hari.
- Diambil di dalam transaksi database dengan penguncian baris `queue_daily_numbers`, lalu dijaga indeks unik (`queue_service_id`, `tanggal`, `urut`).
- Lewat dari 999 dalam sehari tetap diterima (menjadi A-1000); dengan pola layanan sekarang hal ini tidak diperkirakan terjadi.
- Tiket dari hari sebelumnya tetap memakai nomornya; di daftar petugas, layar TV, dan halaman lacak ditulis dengan tanggalnya (mis. **A-001 · 14 Okt**) agar tidak tertukar dengan A-001 hari ini.

### 4.3 Siklus tiket

```
Cetak ─► Pendaftaran ─► Penetapan ─► Kwitansi ─► Pembayaran ─► Cetak SKPD ─► Cetak STNK ─► Penyerahan ─► Selesai
           (panggil)     (tandai)     (panggil)   (Pelunasan     (tandai)      (tandai)      (panggil)
                                                   menandai)
                                    tahap yang tidak ada di layanannya dilewati
```

- **Tahap dipanggil:** nomor masuk daftar *Menunggu* loket yang melayani layanannya. Petugas menekan **Panggil berikutnya** (urut waktu masuk tahap, jadi tiket hari sebelumnya lebih dulu) atau memanggil nomor tertentu. Setelah dipanggil petugas bisa **Panggil ulang**, **Tunda** (WP tidak datang; nomor pindah ke daftar *Ditunda* dan bisa dipanggil lagi kapan saja), atau **Selesai** (nomor pindah ke tahap berikutnya).
- **Tahap ditandai:** petugas memindai QR berkas, yang **langsung menandai** tahap posnya dengan konfirmasi singkat dan tombol **Urungkan** (keputusan pemilik), atau memilih nomor dari daftar tahapnya lalu menekan **Selesai**. Petugas Penetapan juga bisa menandai **Perlu perubahan data** (jalur *Server* di flowchart): nomor masuk daftar **pos Server** tersendiri (keputusan pemilik), dan setelah Server menandai selesai berkas kembali ke daftar Penetapan di urutan lamanya. WP membaca "Data kendaraan Anda sedang diubah petugas".
- **Memindai tidak wajib:** bila pos sesudahnya menandai nomor yang tahap sebelumnya belum ditandai, tahap yang terlewat dianggap selesai dan dicatat `otomatis`. Dengan begitu satu pos yang lupa memindai tidak menghentikan antrean.
- **Batal:** Meja Informasi atau Administrator membatalkan tiket dengan alasan (mis. berkas ditolak saat penetapan, WP pulang).
- **Ganti layanan / cetak ulang:** bila salah memilih layanan sebelum dipanggil, tiket dibatalkan dan dicetak ulang; bila kertas rusak, **Cetak ulang** mencetak tiket yang sama (nomor dan QR tetap).
- Setiap tindakan masuk `queue_events` dengan petugas dan loketnya. Perubahan pengaturan (layanan, loket, layar) masuk log aktivitas yang sudah ada.

### 4.4 Akun, peran, dan pos

- Modul dashboard baru **`antrean`**, diberikan Administrator kepada pegawai seperti modul lain (D-008). Pengaturan layanan, loket, dan layar hanya untuk Administrator.
- Setiap petugas masuk dengan akunnya sendiri lalu memilih **pos/loket** hari itu; pilihan ini diingat di perangkatnya sampai diganti.
- Akun PINTU saat ini wajib memakai NIP 18 digit (D-007). Bila petugas Polri (mis. Cetak STNK, pengesahan) atau bank ikut memindai, perlu keputusan tambahan (lihat §9).

### 4.5 Cetak tiket (80 mm)

- Dicetak dari peramban di komputer Meja Informasi dengan tata letak khusus cetak (`@page` lebar 80 mm). Dua lembar dicetak berurutan dengan pemisah halaman, sehingga printer dengan pemotong otomatis memotong di antaranya.
- Dialog cetak bisa dilewati dengan menjalankan Chrome/Edge dalam mode cetak kios (`--kiosk-printing`) di komputer itu; tidak perlu aplikasi tambahan.
- **Lembar 1 (WP):** nama UPTD, nomor besar (A-001), layanan, nomor polisi, tanggal dan jam cetak, QR, "Pindai untuk melacak berkas Anda. Simpan lembar ini sampai berkas diserahkan."
- **Lembar 2 (berkas):** nomor besar, nomor polisi, layanan, tanggal, QR, "Tempel pada berkas".
- QR berisi alamat halaman lacak, mis. `https://pintu.uptdpenda-kupang.web.id/lacak/<kode>`. QR yang sama dipakai petugas untuk memindai berkas.
- Membuat gambar QR memerlukan pustaka baru (belum ada di proyek); perlu persetujuan pemilik (lihat §9).

### 4.6 Memindai QR di pos

- **Pemindai barcode USB 2D** (yang bisa membaca QR) bekerja seperti keyboard: kotak "Pindai atau ketik nomor" di layar pos selalu siap, hasil pindaian langsung mencari tiketnya. Pemindai 1D/laser biasa tidak bisa membaca QR.
- Di ponsel atau laptop berkamera, tombol **Pindai dengan kamera** memakai fitur bawaan peramban (*BarcodeDetector*, tersedia di Chrome Android) tanpa pustaka tambahan.
- Tanpa pemindai, petugas mengetik nomor (A-001) atau nomor polisi, atau memilih dari daftar tahapnya.

### 4.7 Layar TV dan suara

- Halaman layar penuh `/layar` dibuka di komputer kecil atau peramban TV ruang tunggu. Halaman ini **tidak bisa dibuka sembarang orang**: Administrator mendaftarkan layar di dashboard dan mendapat kode pasang sekali pakai; perangkat menyimpan tokennya dan bisa dicabut kapan saja. Ini penting karena nomor polisi tampil apa adanya.
- Isinya: panggilan terbaru dalam ukuran besar ("A-001 · DH 1234 AB → Loket Kwitansi"), nomor terakhir di setiap loket pemanggil, dan jumlah nomor yang menunggu per tahap.
- Suara: bunyi tanda lalu kalimat berbahasa Indonesia dari fitur suara bawaan peramban (*Web Speech*), mis. "Nomor antrean A, nol nol satu, silakan ke Loket Kwitansi." Apakah nomor polisi ikut dibacakan bisa diatur. Peramban mewajibkan satu klik **Aktifkan suara** saat layar pertama kali dibuka.
- Suara bahasa Indonesia harus tersedia di perangkat layar (Edge di Windows menyediakannya); ini diperiksa saat menyiapkan perangkat.

### 4.8 Halaman lacak (QR)

- Alamat `/lacak/<kode>` di portal, tidak muncul di menu. Kode acak sepanjang ±22 karakter sehingga tidak bisa ditebak atau diurutkan; tidak ada pencarian nomor polisi bagi publik.
- Isinya: nomor dan tanggal, layanan, nomor polisi, tahap sekarang dengan petunjuk ("Tunggu dipanggil di Loket Kwitansi", "Silakan membayar di bank"), banyaknya nomor di depan pada tahap pemanggilan, dan riwayat tahap beserta jamnya. Tidak menampilkan nama petugas.
- Diperbarui otomatis setiap 30 detik; tidak memasang cookie; dibatasi laju per alamat IP seperti API publik lain.

### 4.9 Arsip dan pencarian

- Halaman **Arsip** di dashboard (semua petugas Antrean, keputusan pemilik): cari menurut nomor antrean, sebagian nomor polisi, QR, tanggal, layanan, status, atau tahap; detail tiket menampilkan riwayat lengkap beserta petugas dan loketnya.
- Nomor polisi disimpan tanpa batas waktu (keputusan pemilik). Administrator bisa **menghapus tiket seluruhnya** beserta riwayatnya (keputusan pemilik), untuk tiket yang sudah selesai atau dibatalkan dan dicetak sebelum hari ini; penghapusan tercatat di log aktivitas tanpa nomor polisi.
- Halaman **Ringkasan**: satu hari, satu bulan, atau rentang sampai 31 hari (keputusan pemilik): jumlah tiket per layanan dan per hari, dan per tahap jumlah yang selesai, dilewati otomatis, dan ditunda, serta nilai tengah waktu tunggu dan lama di tahap. Unduhan CSV berisi ringkasan dan daftar tiket dengan nomor polisi (keputusan pemilik).

### 4.10 Pembaruan langsung

Layar TV, daftar di loket, dan halaman lacak meminta data terbaru secara berkala (layar dan loket ±2–3 detik, halaman lacak 30 detik). Cara ini tidak memerlukan layanan WebSocket baru di Laravel Cloud dan cukup untuk beberapa loket dan satu–dua layar. Bila kelak terasa lambat, Laravel Reverb bisa dipertimbangkan sebagai keputusan terpisah.

### 4.11 Rancangan API

Mengikuti pola API yang ada (JSON polos, pesan galat berbahasa Indonesia, `422` untuk masukan salah). Garis besarnya:

| Metode dan alamat | Untuk |
|---|---|
| `GET/PUT /api/antrean/layanan` | Daftar layanan; Administrator mengubah nama, cakupan, urutan tahap, aktif |
| `GET/POST/PATCH /api/antrean/loket` | Loket/pos (Administrator) |
| `POST /api/antrean/tiket` | Mencetak tiket (`nopol`, `layanan`), menjawab data cetak dan kode QR |
| `GET /api/antrean/tiket` | Daftar dan arsip dengan pencarian |
| `GET /api/antrean/tiket/{id}` | Detail dan riwayat |
| `POST /api/antrean/tiket/{id}/{panggil-ulang\|tunda\|selesai\|perubahan-data\|batal\|cetak-ulang}` | Tindakan petugas |
| `POST /api/antrean/loket/{id}/panggil` | Panggil berikutnya di loket itu |
| `POST /api/antrean/pindai` | Mencari tiket dari hasil pindai atau ketikan |
| `GET /api/antrean/pos/{id}` | Daftar menunggu, dipanggil, dan ditunda untuk satu pos (diminta berkala) |
| `DELETE /api/antrean/tiket/{id}` | Administrator menghapus tiket beserta riwayatnya (keputusan Irisan 4) |
| `POST /api/layar/pasang`, `GET /api/layar/panggilan` | Memasang layar dengan kode sekali pakai; data layar (token perangkat) |
| `GET /api/public/antrean/{kode}` | Halaman lacak (publik, tanpa sesi) |

---

## 5. Halaman

| Tempat | Halaman | Untuk |
|---|---|---|
| Dashboard › Antrean | **Meja Informasi** | Ketik nomor polisi, pilih layanan, cetak 2 lembar; daftar tiket hari ini; cetak ulang, batal |
| Dashboard › Antrean | **Loket** | Pilih loket; tombol besar Panggil berikutnya, Panggil ulang, Tunda, Selesai; daftar menunggu dan ditunda; kotak pindai |
| Dashboard › Antrean | **Pos Proses** | Penetapan, Pelunasan, Cetak SKPD, Cetak STNK: kotak pindai, daftar tahap, Selesai, Perlu perubahan data |
| Dashboard › Antrean | **Arsip**, **Ringkasan** | Pencarian, detail riwayat, hapus tiket (Administrator); ringkasan harian atau rentang, CSV |
| Dashboard › Antrean | **Pengaturan Antrean** | Layanan dan urutan tahap, loket/pos, layar, kalimat suara (Administrator) |
| Portal | `/lacak/<kode>` | Halaman lacak WP |
| Perangkat layar | `/layar` | Layar TV ruang tunggu |

---

## 6. Tahapan pengerjaan

Setiap irisan dikerjakan, diuji, diperiksa di browser, dilaporkan, lalu di-commit setelah pemilik menyetujui ("Commit dan Push"), seperti fitur sebelumnya.

| Irisan | Isi | Hasil yang bisa diperiksa pemilik |
|---|---|---|
| **0. Persiapan** | Persetujuan rencana ini; keputusan §9; pengadaan perangkat (§8) | — |
| **1. Fondasi + Meja Informasi** ✓ | Tabel layanan, tiket, dan riwayat; isi awal A–D; pengaturan layanan (Administrator); penomoran A-001; halaman Meja Informasi (cetak, cetak ulang, batal, daftar per tanggal); cetak 2 lembar 80 mm dengan QR lewat iframe; halaman lacak dasar. Tabel dan pengaturan loket/pos dipindah ke Irisan 2, karena baru dipakai saat memanggil | Mencetak tiket dan melacaknya dari ponsel |
| **2. Loket pemanggil + layar TV** ✓ | Tabel dan pengaturan loket/pos; Loket 1, Loket Kwitansi, Loket 2: panggil, panggil ulang, tunda, selesai; tiket lintas hari; layar TV dengan suara; pendaftaran layar | Memanggil nomor dan melihat/mendengarnya di layar |
| **3. Pos proses + pindai** ✓ | Penetapan (dengan perubahan data lewat pos Server), Pelunasan, Cetak SKPD, Cetak STNK; pindai USB/kamera yang langsung menandai, dengan Urungkan; tahap terlewat otomatis; halaman lacak lengkap dengan jam setiap tahap dan posisi | Satu berkas berjalan penuh dari cetak sampai selesai |
| **4. Arsip + ringkasan** ✓ | Pencarian nomor polisi/nomor/tanggal/layanan/status/tahap, detail riwayat, hapus tiket seluruhnya (Administrator), ringkasan harian atau rentang sampai 31 hari, CSV dengan daftar tiket | Mencari berkas yang "tercecer" |
| **Uji coba** | Deploy ke Laravel Cloud (langkah pemilik); 1–2 minggu paralel dengan nomor manual, dimulai dari satu layanan, lalu semua | Evaluasi bersama petugas loket |
| **5. Papan Layanan otomatis** | Setelah antrean berjalan (keputusan pemilik): nomor dilayani dan waktu tunggu di beranda dari antrean; Statistik Layanan dari data antrean | — |
| **6. API Samsat Online** | Bila BPAD memberi akses: status Penetapan, Pembayaran, dan Cetak Notice diambil otomatis per nomor polisi; pindai menjadi cadangan | — |

Pengujian setiap irisan: tes fitur untuk setiap endpoint (hak akses, validasi, penomoran per layanan dan per hari, lintas hari, setiap urutan tahap A–D, tahap terlewat otomatis, kode lacak tidak bisa ditebak, layar tanpa token ditolak), uji mutasi untuk penomoran dan perpindahan tahap, typecheck/lint/build, serta pemeriksaan browser (pratinjau cetak 80 mm, layar TV, halaman lacak di ponsel).

---

## 7. Uji coba paralel

1. Setelah Irisan 1–4 selesai dan PINTU di-deploy, perangkat dipasang dan petugas dilatih (±1 jam per pos).
2. Minggu pertama: satu layanan (mis. B) memakai tiket cetak, layanan lain tetap manual. Petugas mencatat kendala.
3. Minggu kedua: semua layanan. Nomor manual disiapkan sebagai cadangan.
4. **Prosedur darurat:** bila internet atau server mati, Meja Informasi kembali menulis nomor manual dengan format yang sama ditambah tanda (mis. "M-A-001"); setelah pulih, berkas yang masih berjalan dicetakkan tiket agar bisa dilacak.
5. Evaluasi bersama pemilik: alur per layanan, kalimat suara, tata letak tiket, lalu keputusan memakai penuh.

---

## 8. Perangkat yang perlu disiapkan

| Tempat | Perangkat |
|---|---|
| Meja Informasi | Komputer/laptop, **printer thermal 80 mm dengan pemotong otomatis** (USB atau LAN), kertas thermal 80 mm |
| Loket 1, Loket Kwitansi, Loket 2 | Komputer yang ada + **pemindai barcode USB 2D** (bisa membaca QR) |
| Penetapan, Pelunasan, Cetak SKPD, Cetak STNK | Komputer yang ada + pemindai 2D, atau ponsel berkamera |
| Ruang tunggu | TV + komputer kecil/stick PC atau TV dengan peramban Chrome/Edge, pengeras suara |
| Umum | Internet kantor yang stabil dan UPS untuk Meja Informasi dan layar |

---

## 9. Hal yang masih perlu diputuskan

| No | Hal | Usulan |
|---|---|---|
| 1 | Pustaka pembuat QR (dependensi baru) | **Diputuskan: uqr** (pemilik), tanpa dependensi, SVG di peramban |
| 2 | Akun petugas non-UPTD (Polri di Cetak STNK/pengesahan, bank) bila ikut memindai | Pos tersebut dipindai petugas UPTD; atau akun boleh memakai NRP/nomor pegawai lain (mengubah D-007) |
| 3 | Jumlah loket fisik per tahap dan layanan yang dilayani tiap loket | **Diputuskan:** satu loket per tahap untuk semua layanan (pemilik); diubah di Pengaturan Antrean bila loket bertambah |
| 4 | Antrean prioritas (lansia, disabilitas, ibu hamil) | Tanda "Prioritas" saat cetak yang membuat nomor dipanggil lebih dulu di setiap tahap, bila diinginkan |
| 5 | Kalimat suara dan apakah nomor polisi ikut dibacakan | **Diputuskan:** tanpa nomor polisi (pemilik); nama loket yang dibacakan diatur di Pengaturan Antrean |
| 6 | Kebijakan Privasi portal | Ditambah penjelasan data antrean: nomor polisi, waktu, dan tahap disimpan untuk pelacakan dan pencarian berkas sampai dihapus Administrator, dan tampil di halaman lacak serta layar ruang tunggu |
| 7 | Waktu deploy ke Laravel Cloud | Antrean hanya bisa diuji coba di loket setelah PINTU berjalan di Laravel Cloud (§15 `KONTEKS-PROYEK.md`) |

---

## 10. Risiko dan penanganannya

| Risiko | Penanganan |
|---|---|
| Internet kantor atau server terputus | Prosedur darurat nomor manual (§7); halaman cepat pulih karena hanya meminta data berkala |
| Nomor polisi tampil utuh di layar ruang tunggu dan halaman lacak | Keputusan pemilik; layar hanya untuk perangkat terdaftar, halaman lacak hanya dengan kode acak dari QR, tanpa pencarian publik; dijelaskan di Kebijakan Privasi (UU 27/2022 tentang Pelindungan Data Pribadi) |
| Petugas lupa memindai | Tahap terlewat otomatis selesai saat pos berikutnya memindai; daftar per pos tetap bisa diklik |
| Suara tidak keluar di layar | Tombol Aktifkan suara, pemeriksaan suara bahasa Indonesia saat pemasangan, tampilan tetap berjalan tanpa suara |
| Dua petugas mencetak bersamaan | Penomoran dikunci dalam transaksi dan dijaga indeks unik |
| Berkas lebih dari sehari | Tiket tetap aktif dengan tanggalnya; daftar mengurutkan yang lebih lama lebih dulu |
| Kode lacak disebar orang lain | Kode hanya membuka satu tiket; tidak ada daftar atau pencarian publik |

---

## 11. Dokumen dan kode yang terdampak

- `docs/decisions.md` (D-051 dan keputusan berikutnya), `docs/api.md` (endpoint antrean), `docs/dashboard.md` (modul Antrean), `KONTEKS-PROYEK.md` (setiap irisan).
- Kode baru: model dan migrasi antrean, controller dan request di `app/Http`, kebijakan akses modul `antrean`, halaman dashboard di `resources/js/dashboard/pages/antrean/`, halaman portal `/lacak/<kode>` (komponen React portal), halaman `/layar`, tes fitur.
- Kode yang diubah sedikit: daftar modul dashboard (`DashboardModule`), menu dashboard, rute API dan web, batas laju publik.
- Papan Layanan (D-050) dan Laporan Penerimaan tidak berubah sampai Irisan 5.
