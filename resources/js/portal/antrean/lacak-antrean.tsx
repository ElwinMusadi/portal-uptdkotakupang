/*
 * Lacak antrean (D-051): halaman yang dibuka dari QR pada tiket antrean,
 * /lacak/<kode>. Membaca GET /api/public/antrean/<kode> dan memperlihatkan
 * nomor, layanan, nomor polisi, tahap berkas sekarang beserta petunjuknya,
 * dan tahap-tahap layanan itu. Diperbarui setiap 30 detik selama halaman
 * terlihat, sampai tiketnya dibatalkan.
 */
import { useCallback, useEffect, useState } from "react"
import { createRoot } from "react-dom/client"

import { labelTanggal } from "../penerimaan/periode"
import "./lacak-antrean.css"

type Keadaan = "selesai" | "sekarang" | "nanti"

interface Tiket {
  nomor: string
  tanggal: string
  layanan: { kode: string; nama: string }
  nopol: string | null
  status: "menunggu" | "batal"
  tahap: string | null
  petunjuk: string
  tahapan: { kunci: string; nama: string; keadaan: Keadaan }[]
  dicetak: string | null
}

const SELANG_MS = 30_000

const fmtJam = new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Makassar" })
const jamWita = (d: Date) => `${fmtJam.format(d).replace(":", ".")} WITA`

/** Kode lacak dari alamat /lacak/<kode> */
const kodeLacak = () => window.location.pathname.split("/").filter(Boolean).pop() ?? ""

async function muatTiket(sinyal: AbortSignal): Promise<Tiket> {
  const res = await fetch(`/api/public/antrean/${encodeURIComponent(kodeLacak())}`, { headers: { Accept: "application/json" }, signal: sinyal })
  if (!res.ok) {
    let pesan = "Nomor antrean belum dapat dimuat. Coba lagi sebentar lagi."
    try {
      pesan = ((await res.json()) as { message?: string }).message ?? pesan
    } catch {
      // respons bukan JSON
    }
    throw new Error(pesan)
  }
  return (await res.json()) as Tiket
}

function LacakAntrean() {
  const [tiket, setTiket] = useState<Tiket | null>(null)
  const [galat, setGalat] = useState<string | null>(null)
  const [diperbarui, setDiperbarui] = useState<Date | null>(null)
  const [memuat, setMemuat] = useState(true)
  const [putaran, setPutaran] = useState(0)
  const perbarui = useCallback(() => setPutaran((n) => n + 1), [])

  useEffect(() => {
    const kendali = new AbortController()
    setMemuat(true)
    muatTiket(kendali.signal)
      .then((t) => {
        setTiket(t)
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

  // Diperbarui berkala selama halaman terlihat; tiket yang dibatalkan tidak berubah lagi
  const selesai = tiket?.status === "batal"
  useEffect(() => {
    if (selesai) return
    const id = window.setInterval(() => {
      if (document.visibilityState === "visible") perbarui()
    }, SELANG_MS)
    const terlihat = () => document.visibilityState === "visible" && perbarui()
    document.addEventListener("visibilitychange", terlihat)
    return () => {
      window.clearInterval(id)
      document.removeEventListener("visibilitychange", terlihat)
    }
  }, [selesai, perbarui])

  if (!tiket) {
    return galat ? (
      <div className="card card--pad lk-pesan" role="alert">
        <p className="lk-pesan__judul">{galat}</p>
        <button type="button" className="btn btn--dark btn--sm" onClick={perbarui}>
          Coba lagi
        </button>
      </div>
    ) : (
      <p className="center muted" role="status" style={{ padding: "48px 0" }}>
        Memuat nomor antrean…
      </p>
    )
  }

  const batal = tiket.status === "batal"
  return (
    <div className="lk" aria-busy={memuat}>
      <section className="night-panel lk-tiket" aria-label="Tiket antrean">
        <div className="tile lk-tiket__utama">
          <p className="tile__label">Nomor antrean</p>
          <p className="lk-tiket__nomor">{tiket.nomor}</p>
          <p className="lk-tiket__layanan">{tiket.layanan.nama}</p>
          <dl className="lk-tiket__data">
            <div>
              <dt>Nomor polisi</dt>
              <dd>{tiket.nopol ?? "—"}</dd>
            </div>
            <div>
              <dt>Tanggal</dt>
              <dd>{labelTanggal(tiket.tanggal, "lengkap")}</dd>
            </div>
          </dl>
        </div>
        <div className={batal ? "tile lk-tiket__kini is-batal" : "tile lk-tiket__kini"}>
          <p className="tile__label">{batal ? "Dibatalkan" : "Sekarang"}</p>
          <p className="lk-tiket__petunjuk" aria-live="polite">
            {tiket.petunjuk}
          </p>
        </div>
      </section>

      <section className="card card--pad lk-tahap" aria-labelledby="lk-tahap-judul">
        <h2 id="lk-tahap-judul" className="lk-tahap__judul">
          Tahap berkas
        </h2>
        <ol className="lk-tahap__daftar" role="list">
          {tiket.tahapan.map((t) => (
            <li key={t.kunci} className={`lk-tahap__item is-${t.keadaan}`} aria-current={t.keadaan === "sekarang" ? "step" : undefined}>
              <span className="lk-tahap__titik" aria-hidden="true">
                {t.keadaan === "selesai" && (
                  <svg className="i i--sm">
                    <use href="#i-check" />
                  </svg>
                )}
              </span>
              <span className="lk-tahap__nama">{t.nama}</span>
              <span className="lk-tahap__keadaan">{t.keadaan === "selesai" ? "Selesai" : t.keadaan === "sekarang" ? "Sekarang" : ""}</span>
            </li>
          ))}
        </ol>
      </section>

      <p className="micro muted lk-catatan">
        {galat ? `${galat} ` : ""}
        {diperbarui && `Diperbarui ${jamWita(diperbarui)}`}
        {!batal && " · otomatis setiap 30 detik. "}
        <button type="button" className="link lk-perbarui" onClick={perbarui} disabled={memuat}>
          Perbarui sekarang
        </button>
      </p>
    </div>
  )
}

/** Ambil alih elemen [data-react="lacak-antrean"] */
export function pasang(el: HTMLElement) {
  createRoot(el).render(<LacakAntrean />)
}
