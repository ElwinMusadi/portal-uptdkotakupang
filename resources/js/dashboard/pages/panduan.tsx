import type * as React from "react"
import { Link } from "react-router"

import { Halaman, PengantarHalaman } from "@/components/data/page"
import { StatusAlurBadge } from "@/components/data/status-badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Kbd, KbdGroup } from "@/components/ui/kbd"
import { api } from "@/lib/api"
import { AKSI_ALUR, STATUS_ALUR, TAHAP_ALUR } from "@/lib/meta"
import { TOMBOL_MOD } from "@/lib/platform"

function Topik({ id, judul, deskripsi, children }: { id: string; judul: string; deskripsi?: string; children: React.ReactNode }) {
  return (
    <Card id={id} className="scroll-mt-20 gap-4">
      <CardHeader>
        <CardTitle className="text-base">{judul}</CardTitle>
        {deskripsi && <CardDescription>{deskripsi}</CardDescription>}
      </CardHeader>
      <CardContent className="grid gap-3 text-sm leading-relaxed">{children}</CardContent>
    </Card>
  )
}

const ENDPOINT: [string, string, string][] = [
  ["GET", "/koleksi/{nama}", "Daftar entri (berita, dokumen, layanan, keliling, pesan, absensi, …)"],
  ["POST", "/koleksi/{nama}", "Tambah entri"],
  ["PATCH", "/koleksi/{nama}/{id}", "Ubah sebagian entri"],
  ["POST", "/koleksi/{nama}/hapus", "Hapus entri, body { ids: [...] }"],
  ["PUT", "/koleksi/{nama}/urutan", "Simpan urutan tampil, body { ids: [...] }"],
  ["GET / PUT", "/pengaturan/{nama}", "Halaman satu-dokumen (profil, kontak, jam, …)"],
  ["POST", "/unggah", "Unggah berkas (multipart, field “berkas”)"],
  ["GET", "/aktivitas", "Log aktivitas"],
]

export default function HalamanPanduan() {
  return (
    <Halaman className="max-w-5xl">
      <PengantarHalaman deskripsi="Ringkasan cara kerja dashboard PINTU: alur publikasi, standar pemutakhiran, penyimpanan data, dan langkah menyambungkan ke server." />
      <nav aria-label="Topik panduan" className="flex flex-wrap gap-1.5">
        {[
          ["alur", "Alur publikasi"],
          ["pemutakhiran", "Pemutakhiran"],
          ["alat", "Alat bantu"],
          ["data", "Penyimpanan data"],
          ["server", "Menyambungkan ke server"],
          ["keamanan", "Akun & keamanan"],
          ["pintasan", "Pintasan"],
        ].map(([id, label]) => (
          <a
            key={id}
            href={`#${id}`}
            onClick={(e) => {
              e.preventDefault()
              document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" })
            }}
            className="rounded-full border px-3 py-1 text-sm text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            {label}
          </a>
        ))}
      </nav>

      <Topik id="alur" judul="Alur publikasi" deskripsi="Sesuai rancangan PINTU: pengumpulan → verifikasi → persetujuan → publikasi.">
        <ol className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {TAHAP_ALUR.map((s, i) => (
            <li key={s} className="grid content-start gap-1.5 rounded-lg border p-3">
              <span className="text-xs text-muted-foreground">
                {String(i + 1).padStart(2, "0")} · {STATUS_ALUR[s].peran}
              </span>
              <StatusAlurBadge status={s} className="w-fit" />
              <span className="text-muted-foreground">{STATUS_ALUR[s].keterangan}</span>
              {AKSI_ALUR[s].length > 0 && (
                <span className="text-xs">
                  Aksi: {AKSI_ALUR[s].map((a) => a.label).join(" · ")}
                </span>
              )}
            </li>
          ))}
        </ol>
        <p>
          Berita, dokumentasi, dokumen unduhan, dan persyaratan layanan melewati alur ini. Konten yang
          dikembalikan membawa catatan revisi dan kembali ke penyedia. Semua langkah tercatat di riwayat
          konten dan di tab Aktivitas pada <Link to="/" className="underline underline-offset-4">Ringkasan</Link>.
        </p>
        <p className="text-muted-foreground">
          Halaman satu-dokumen (profil, visi misi, kontak, jam pelayanan, papan layanan, tarif) langsung
          berlaku saat disimpan. Pastikan isinya sudah diverifikasi sebelum menyimpan.
        </p>
      </Topik>

      <Topik id="pemutakhiran" judul="Standar pemutakhiran">
        <p>
          Setiap jenis konten punya batas frekuensi, mis. jadwal Samsat Keliling setiap Jumat dan jam
          pelayanan setiap bulan. Dashboard menghitung waktu pembaruan terakhir dari modul terkait dan
          menandai konten yang <strong>jatuh tempo</strong>. Atur standarnya di{" "}
          <Link to="/pemutakhiran" className="underline underline-offset-4">Jadwal Pemutakhiran</Link>.
        </p>
      </Topik>

      <Topik id="alat" judul="Alat bantu petugas" deskripsi="Untuk kerja internal; tidak tampil di portal publik.">
        <p>
          <Link to="/absensi" className="underline underline-offset-4">Absensi Lapangan</Link> merekam foto dan
          titik GPS saat petugas tiba dan selesai di lokasi kegiatan, mis. Samsat Keliling atau operasi
          gabungan. Peramban akan meminta izin kamera dan lokasi; keduanya hanya bekerja lewat HTTPS. Bila GPS
          tidak tersedia, tulis keterangan tempat. Catatan absensi tidak bisa diubah agar tetap sah sebagai
          bukti, hanya bisa dihapus.
        </p>
        <p>
          <Link to="/kalkulator" className="underline underline-offset-4">Kalkulator PKB</Link> menghitung
          estimasi tagihan untuk wajib pajak di loket, termasuk saat masa amnesti (denda dihapus). Rumus dan
          parameternya sama dengan simulasi di portal dan diatur di{" "}
          <Link to="/tarif" className="underline underline-offset-4">Tarif & Simulasi</Link>.
        </p>
      </Topik>

      <Topik id="data" judul="Penyimpanan data">
        {api.mode === "lokal" ? (
          <>
            <p>
              Saat ini dashboard berjalan dalam <strong>mode lokal</strong>: data disimpan di IndexedDB
              peramban yang sedang Anda pakai. Perubahan belum tampil di portal publik dan tidak terlihat
              dari perangkat lain.
            </p>
            <p>
              Unduh cadangan secara berkala lewat{" "}
              <Link to="/pengaturan?tab=data" className="underline underline-offset-4">Pengaturan › Data</Link>.
              Berkas cadangan (JSON) bisa dipulihkan di perangkat lain atau dipakai sebagai data awal server.
            </p>
          </>
        ) : (
          <p>
            Dashboard terhubung ke server. Semua perubahan langsung tersimpan di basis data dan dipakai portal publik.
          </p>
        )}
        <p className="text-muted-foreground">
          Foto diperkecil otomatis sebelum disimpan (sisi terpanjang 1600 px, foto absensi 1024 px). Berkas
          dokumen maksimal 10 MB.
        </p>
      </Topik>

      <Topik id="server" judul="Menyambungkan ke server" deskripsi="Agar isi dashboard tampil di portal secara dinamis.">
        <p>
          Bangun dashboard dengan alamat API, mis.{" "}
          <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">VITE_API_URL=https://pintu.example.go.id/api npm run build</code>.
          Server cukup menyediakan endpoint berikut dengan bentuk JSON yang sama dengan berkas cadangan:
        </p>
        <div className="overflow-x-auto rounded-lg border">
          <table className="w-full text-left text-sm">
            <thead className="bg-muted">
              <tr>
                <th className="px-3 py-2 font-medium">Metode</th>
                <th className="px-3 py-2 font-medium">Alamat</th>
                <th className="px-3 py-2 font-medium">Fungsi</th>
              </tr>
            </thead>
            <tbody>
              {ENDPOINT.map(([m, p, f]) => (
                <tr key={m + p} className="border-t">
                  <td className="px-3 py-2 font-mono text-xs whitespace-nowrap">{m}</td>
                  <td className="px-3 py-2 font-mono text-xs whitespace-nowrap">{p}</td>
                  <td className="px-3 py-2">{f}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p className="text-muted-foreground">
          Kontrak lengkap, contoh data, dan cara memasang di hosting ada di berkas{" "}
          <code className="font-mono text-xs">dashboard-app/README.md</code> pada repositori portal.
        </p>
      </Topik>

      <Topik id="keamanan" judul="Akun & keamanan">
        <p>
          Login memakai satu akun statis (NIP dan kata sandi) yang diperiksa di peramban. Ini cukup untuk uji
          coba, tetapi <strong>tidak aman untuk dipakai publik</strong>: siapa pun yang membuka kode halaman
          bisa membaca kata sandinya. Sebelum portal dipakai resmi, pindahkan login ke server, buat akun per
          pegawai sesuai peran (penyedia, pemeriksa, penyetuju, pengelola publikasi), dan aktifkan HTTPS.
        </p>
        <p className="text-muted-foreground">
          Sesi berakhir setelah 8 jam, atau 7 hari bila “Ingat perangkat ini” dicentang. Keluar dari menu
          akun di kiri bawah.
        </p>
      </Topik>

      <Topik id="pintasan" judul="Pintasan papan ketik">
        <dl className="grid gap-2 sm:grid-cols-2">
          <div className="flex items-center justify-between gap-4 rounded-lg border px-3 py-2">
            <dt>Cari halaman & konten</dt>
            <dd>
              <KbdGroup>
                <Kbd>{TOMBOL_MOD}</Kbd>
                <Kbd>K</Kbd>
              </KbdGroup>
            </dd>
          </div>
          <div className="flex items-center justify-between gap-4 rounded-lg border px-3 py-2">
            <dt>Buka/tutup sidebar</dt>
            <dd>
              <KbdGroup>
                <Kbd>{TOMBOL_MOD}</Kbd>
                <Kbd>B</Kbd>
              </KbdGroup>
            </dd>
          </div>
          <div className="flex items-center justify-between gap-4 rounded-lg border px-3 py-2">
            <dt>Pindahkan baris (setelah diangkat)</dt>
            <dd>
              <KbdGroup>
                <Kbd>Spasi</Kbd>
                <Kbd>↑</Kbd>
                <Kbd>↓</Kbd>
              </KbdGroup>
            </dd>
          </div>
        </dl>
      </Topik>
    </Halaman>
  )
}
