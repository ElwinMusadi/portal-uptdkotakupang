/*
 * Lacak pesan (D-058): halaman /pesan/<kode> yang didapat pengirim setelah
 * mengirim pesan, atau dibuka dari nomor tiket dan kontak di halaman Kontak.
 * Membaca GET /api/public/pesan/<kode> dan memperlihatkan nomor tiket,
 * status, isi pesan, dan balasan petugas untuk pengirim (tanpa nama
 * petugas). Dimuat ulang saat halaman kembali dilihat.
 */
import { useCallback, useEffect, useState } from "react"
import { createRoot } from "react-dom/client"

import "../antrean/lacak-antrean.css"
import "./lacak-pesan.css"

type Status = "baru" | "diproses" | "selesai"

interface Pesan {
  tiket: string
  jenis: "pertanyaan" | "masukan" | "pengaduan"
  layanan: string
  isi: string
  diterima: string
  status: Status
  balasan: { isi: string; waktu: string }[]
}

const JENIS: Record<Pesan["jenis"], string> = {
  pertanyaan: "Pertanyaan",
  masukan: "Masukan",
  pengaduan: "Pengaduan",
}

const STATUS: Record<Status, { judul: string; teks: string }> = {
  baru: { judul: "Diterima", teks: "Pesan Anda sudah kami terima dan menunggu dibaca petugas." },
  diproses: { judul: "Sedang ditindaklanjuti", teks: "Petugas sedang menindaklanjuti pesan Anda. Balasannya tampil di halaman ini." },
  selesai: { judul: "Selesai", teks: "Pesan Anda sudah selesai ditindaklanjuti. Terima kasih." },
}

const TAHAP: { kunci: Status; nama: string }[] = [
  { kunci: "baru", nama: "Diterima" },
  { kunci: "diproses", nama: "Ditindaklanjuti" },
  { kunci: "selesai", nama: "Selesai" },
]

const fmtTanggal = new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "long", year: "numeric", timeZone: "Asia/Makassar" })
const fmtJam = new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Makassar" })
const jamWita = (d: Date) => `${fmtJam.format(d).replace(":", ".")} WITA`
const waktuWita = (iso: string) => `${fmtTanggal.format(new Date(iso))}, ${jamWita(new Date(iso))}`

/** Kode lacak dari alamat /pesan/<kode> */
const kodeLacak = () => window.location.pathname.split("/").filter(Boolean).pop() ?? ""

async function muatPesan(sinyal: AbortSignal): Promise<Pesan> {
  const res = await fetch(`/api/public/pesan/${encodeURIComponent(kodeLacak())}`, { headers: { Accept: "application/json" }, signal: sinyal })
  if (!res.ok) {
    let pesan = "Pesan belum dapat dimuat. Coba lagi sebentar lagi."
    try {
      pesan = ((await res.json()) as { message?: string }).message ?? pesan
    } catch {
      // respons bukan JSON
    }
    throw new Error(pesan)
  }
  return (await res.json()) as Pesan
}

function LacakPesan() {
  const [pesan, setPesan] = useState<Pesan | null>(null)
  const [galat, setGalat] = useState<string | null>(null)
  const [diperbarui, setDiperbarui] = useState<Date | null>(null)
  const [memuat, setMemuat] = useState(true)
  const [putaran, setPutaran] = useState(0)
  const perbarui = useCallback(() => setPutaran((n) => n + 1), [])

  useEffect(() => {
    const kendali = new AbortController()
    setMemuat(true)
    muatPesan(kendali.signal)
      .then((p) => {
        setPesan(p)
        setGalat(null)
        setDiperbarui(new Date())
        setMemuat(false)
      })
      .catch((e: unknown) => {
        if (kendali.signal.aborted) return
        setGalat((e as Error).message)
        setMemuat(false)
      })
    return () => kendali.abort()
  }, [putaran])

  // Balasan bisa datang kapan saja: dimuat ulang saat pengirim kembali ke halaman ini
  useEffect(() => {
    const terlihat = () => document.visibilityState === "visible" && perbarui()
    document.addEventListener("visibilitychange", terlihat)
    return () => document.removeEventListener("visibilitychange", terlihat)
  }, [perbarui])

  if (!pesan) {
    return galat ? (
      <div className="card card--pad lk-pesan" role="alert">
        <p className="lk-pesan__judul">{galat}</p>
        <button type="button" className="btn btn--dark btn--sm" onClick={perbarui}>
          Coba lagi
        </button>
      </div>
    ) : (
      <p className="center muted" role="status" style={{ padding: "48px 0" }}>
        Memuat pesan…
      </p>
    )
  }

  const status = STATUS[pesan.status]
  const kini = TAHAP.findIndex((t) => t.kunci === pesan.status)
  return (
    <div className="lk" aria-busy={memuat}>
      <section className="night-panel lk-tiket" aria-label="Pesan Anda">
        <div className="tile lk-tiket__utama">
          <p className="tile__label">Nomor tiket</p>
          <p className="lp-tiket">{pesan.tiket}</p>
          <dl className="lk-tiket__data">
            <div>
              <dt>Jenis</dt>
              <dd>{JENIS[pesan.jenis]}</dd>
            </div>
            <div>
              <dt>Layanan</dt>
              <dd>{pesan.layanan}</dd>
            </div>
            <div>
              <dt>Diterima</dt>
              <dd>{waktuWita(pesan.diterima)}</dd>
            </div>
          </dl>
        </div>
        <div className={pesan.status === "selesai" ? "tile lk-tiket__kini lp-status is-selesai" : "tile lk-tiket__kini lp-status"}>
          <p className="tile__label">Status</p>
          <p className="lk-tiket__petunjuk" aria-live="polite">
            {status.judul}
          </p>
          <p className="lk-tiket__posisi">{status.teks}</p>
        </div>
      </section>

      <section className="card card--pad lk-tahap" aria-labelledby="lp-tahap-judul">
        <h2 id="lp-tahap-judul" className="lk-tahap__judul">
          Tindak lanjut
        </h2>
        <ol className="lk-tahap__daftar" role="list">
          {TAHAP.map((t, i) => {
            const keadaan = i < kini || pesan.status === "selesai" ? "selesai" : i === kini ? "sekarang" : "nanti"
            return (
              <li key={t.kunci} className={`lk-tahap__item is-${keadaan}`} aria-current={keadaan === "sekarang" ? "step" : undefined}>
                <span className="lk-tahap__titik" aria-hidden="true">
                  {keadaan === "selesai" && (
                    <svg className="i i--sm">
                      <use href="#i-check" />
                    </svg>
                  )}
                </span>
                <span className="lk-tahap__nama">{t.nama}</span>
                <span className="lk-tahap__keadaan">{t.kunci === "baru" ? fmtTanggal.format(new Date(pesan.diterima)) : keadaan === "sekarang" ? "Sekarang" : ""}</span>
              </li>
            )
          })}
        </ol>
      </section>

      <section className="card card--pad lp-bagian" aria-labelledby="lp-balasan-judul">
        <h2 id="lp-balasan-judul" className="lk-tahap__judul">
          Balasan petugas
        </h2>
        {pesan.balasan.length === 0 ? (
          <p className="muted">
            {pesan.status === "selesai"
              ? "Tidak ada balasan tertulis di halaman ini. Bila petugas membalas lewat kontak Anda, periksa email atau WhatsApp Anda."
              : "Belum ada balasan. Kami membalas paling lambat tiga hari kerja; balasannya tampil di sini."}
          </p>
        ) : (
          <ol className="lp-balasan" role="list">
            {pesan.balasan.map((b, i) => (
              <li key={i} className="lp-balasan__item">
                <p className="lp-balasan__isi">{b.isi}</p>
                <p className="lp-balasan__meta">Petugas UPTD · {waktuWita(b.waktu)}</p>
              </li>
            ))}
          </ol>
        )}
      </section>

      <section className="card card--pad lp-bagian" aria-labelledby="lp-isi-judul">
        <h2 id="lp-isi-judul" className="lk-tahap__judul">
          Pesan Anda
        </h2>
        <blockquote className="lp-isi">{pesan.isi}</blockquote>
      </section>

      <p className="micro muted lk-catatan">
        {galat ? `${galat} ` : ""}
        {diperbarui && `Diperbarui ${jamWita(diperbarui)}. `}
        <button type="button" className="link lk-perbarui" onClick={perbarui} disabled={memuat}>
          Perbarui sekarang
        </button>
      </p>
    </div>
  )
}

/** Ambil alih elemen [data-react="lacak-pesan"] */
export function pasang(el: HTMLElement) {
  createRoot(el).render(<LacakPesan />)
}
