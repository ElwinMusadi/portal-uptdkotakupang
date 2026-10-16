/*
 * Pemetaan PKB di portal (D-052 Irisan 2): jumlah kendaraan, kepatuhan, dan
 * potensi Pajak Kendaraan Bermotor per kecamatan dan kelurahan Kota Kupang,
 * dari GET /api/public/pemetaan. Datanya hanya tampil setelah Administrator
 * menyalakannya, untuk seluruh data tanpa saringan, tanpa identitas wajib
 * pajak; angka yang menggambarkan kurang dari 3 kendaraan datang sebagai null
 * dan ditulis "disamarkan". Peta bersifat skematis (keputusan pemilik).
 */
import { useEffect, useState } from "react"
import { createRoot } from "react-dom/client"

import { labelTanggal } from "../penerimaan/periode"
import "./pemetaan-pkb.css"

type Angka = number | null

interface AngkaWilayah {
  kendaraan: Angka
  rodaDua: Angka
  rodaTigaLebih: Angka
  taat: Angka
  belumBayar: Angka
  menunggak: Angka
  potensi: Angka
  tagihan: Angka
  tunggakan: Angka
}

interface Kecamatan extends AngkaWilayah {
  kunci: string
  nama: string
  kelurahan: (AngkaWilayah & { nama: string })[]
}

interface Pemetaan {
  tersedia: boolean
  tanggalPenetapan?: string | null
  diperbarui?: string | null
  minimum?: number
  kota?: Omit<AngkaWilayah, "rodaTigaLebih"> & { rodaTiga: Angka; rodaEmpat: Angka }
  kecamatan?: Kecamatan[]
}

const fmtAngka = new Intl.NumberFormat("id-ID")
const fmtSatu = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 })
const angka = (n: Angka) => (n === null ? null : fmtAngka.format(n))
const persen = (bagian: Angka, utuh: Angka) => (bagian === null || utuh === null || utuh === 0 ? null : `${fmtSatu.format((bagian / utuh) * 100)}%`)

/** "Rp12,6 M", "Rp845,3 jt", atau rupiah utuh */
function rupiahRingkas(n: Angka): string | null {
  if (n === null) return null
  if (n >= 1e9) return `Rp${fmtSatu.format(n / 1e9)} M`
  if (n >= 1e6) return `Rp${fmtSatu.format(n / 1e6)} jt`
  return `Rp${fmtAngka.format(n)}`
}

/** "NAIKOTEN I" → "Naikoten I", "LAI-LAI BESI KOPAN" → "Lai-Lai Besi Kopan" */
function namaWilayah(teks: string): string {
  return teks
    .toLowerCase()
    .replace(/(^|[\s-])([a-z])/g, (_, a: string, b: string) => a + b.toUpperCase())
    .replace(/\b(Ii|Iii|Iv)\b/g, (r) => r.toUpperCase())
}

/** Nilai, atau tanda disamarkan bila server menyembunyikannya */
function Nilai({ teks }: { teks: string | null }) {
  return teks === null ? (
    <span className="pm-samar" title="Disamarkan: menggambarkan terlalu sedikit kendaraan">
      —<span className="sr-only">disamarkan</span>
    </span>
  ) : (
    <>{teks}</>
  )
}

type Ukuran = "menunggak" | "belum" | "tagihan"

const UKURAN: Record<Ukuran, { label: string; nilai: (a: AngkaWilayah) => number | null; teks: (a: AngkaWilayah) => string | null }> = {
  menunggak: {
    label: "Menunggak ≥ 1 tahun",
    nilai: (a) => (a.menunggak === null || !a.kendaraan ? null : a.menunggak / a.kendaraan),
    teks: (a) => persen(a.menunggak, a.kendaraan),
  },
  belum: {
    label: "Belum taat",
    nilai: (a) => (a.taat === null || !a.kendaraan ? null : (a.kendaraan - a.taat) / a.kendaraan),
    teks: (a) => (a.taat === null || a.kendaraan === null ? null : persen(a.kendaraan - a.taat, a.kendaraan)),
  },
  tagihan: { label: "Tagihan belum terbayar", nilai: (a) => a.tagihan, teks: (a) => rupiahRingkas(a.tagihan) },
}

/** Letak petak kecamatan dalam kotak 400 × 300, utara (Teluk Kupang) di atas */
const LETAK: Record<string, { x: number; y: number; w: number; h: number }> = {
  ALAK: { x: 8, y: 52, w: 112, h: 176 },
  "KOTA LAMA": { x: 128, y: 30, w: 118, h: 72 },
  "KELAPA LIMA": { x: 254, y: 20, w: 138, h: 92 },
  "KOTA RAJA": { x: 128, y: 110, w: 100, h: 82 },
  OEBOBO: { x: 236, y: 120, w: 156, h: 72 },
  MAULAFA: { x: 128, y: 200, w: 264, h: 92 },
}

function PemetaanPkb() {
  const [data, setData] = useState<Pemetaan | null>(null)
  const [galat, setGalat] = useState<string | null>(null)
  const [ulang, setUlang] = useState(0)
  const [ukuran, setUkuran] = useState<Ukuran>("menunggak")
  const [pilih, setPilih] = useState<string | null>(null)

  useEffect(() => {
    const kendali = new AbortController()
    fetch("/api/public/pemetaan", { headers: { Accept: "application/json" }, signal: kendali.signal })
      .then(async (res) => {
        if (!res.ok) throw new Error("Pemetaan belum dapat dimuat. Coba lagi sebentar lagi.")
        setData((await res.json()) as Pemetaan)
        setGalat(null)
      })
      .catch((e: unknown) => {
        if (!kendali.signal.aborted) setGalat((e as Error).message)
      })
    return () => kendali.abort()
  }, [ulang])

  if (galat && !data) {
    return (
      <div className="card card--pad pm-pesan" role="alert">
        <p className="pm-pesan__judul">{galat}</p>
        <button type="button" className="btn btn--dark btn--sm" onClick={() => setUlang((n) => n + 1)}>
          Coba lagi
        </button>
      </div>
    )
  }
  if (!data) {
    return (
      <p className="center muted" role="status" style={{ padding: "48px 0" }}>
        Memuat pemetaan PKB…
      </p>
    )
  }
  if (!data.tersedia || !data.kota || !data.kecamatan) {
    return (
      <div className="card card--pad pm-pesan" role="status">
        <p className="pm-pesan__judul">Pemetaan PKB belum diterbitkan.</p>
        <p className="muted">Angka per kecamatan dan kelurahan tampil di sini setelah diperiksa petugas.</p>
      </div>
    )
  }

  const kota = data.kota
  const wilayah = data.kecamatan
  const dipilih = wilayah.find((k) => k.kunci === pilih) ?? null
  const u = UKURAN[ukuran]
  const nilai = wilayah.filter((k) => LETAK[k.kunci]).map((k) => u.nilai(k)).filter((v): v is number => v !== null)
  const [terendah, tertinggi] = [Math.min(...nilai), Math.max(...nilai)]
  const skala = (v: number | null) => (v === null ? null : tertinggi > terendah ? (v - terendah) / (tertinggi - terendah) : 0.5)

  return (
    <div className="pm">
      <section className="night-panel pm-ringkas" aria-label="Ringkasan Kota Kupang">
        <div className="tile pm-ringkas__utama">
          <p className="tile__label">Objek pajak kendaraan bermotor</p>
          <p className="pm-ringkas__total">
            <Nilai teks={angka(kota.kendaraan)} />
          </p>
          <dl className="pm-ringkas__roda">
            <div>
              <dt>Roda dua</dt>
              <dd>
                <Nilai teks={angka(kota.rodaDua)} />
              </dd>
            </div>
            <div>
              <dt>Roda tiga</dt>
              <dd>
                <Nilai teks={angka(kota.rodaTiga)} />
              </dd>
            </div>
            <div>
              <dt>Roda empat atau lebih</dt>
              <dd>
                <Nilai teks={angka(kota.rodaEmpat)} />
              </dd>
            </div>
          </dl>
          <p className="pm-ringkas__catatan">
            {data.tanggalPenetapan ? `Data penetapan ${labelTanggal(data.tanggalPenetapan, "lengkap")}` : "Data sistem BPAD"} · plat putih Kota Kupang
          </p>
        </div>
        <div className="pm-ringkas__petak">
          <Petak label="Taat membayar" nilai={angka(kota.taat)} catatan={persen(kota.taat, kota.kendaraan)} />
          <Petak label="Belum bayar < 1 tahun" nilai={angka(kota.belumBayar)} catatan={persen(kota.belumBayar, kota.kendaraan)} />
          <Petak label="Menunggak ≥ 1 tahun" nilai={angka(kota.menunggak)} catatan={persen(kota.menunggak, kota.kendaraan)} />
          <Petak label="Potensi pokok PKB" nilai={rupiahRingkas(kota.potensi)} catatan="Pokok pajak semua kendaraan" />
          <Petak label="Tagihan belum terbayar" nilai={rupiahRingkas(kota.tagihan)} catatan="Pokok, denda, dan tunggakan" />
          <Petak label="Tunggakan" nilai={rupiahRingkas(kota.tunggakan)} catatan="Pokok dan denda tunggakan" />
        </div>
      </section>

      <section className="card card--pad pm-peta" aria-labelledby="pm-peta-judul">
        <div className="pm-bagian__kepala">
          <h2 id="pm-peta-judul" className="pm-bagian__judul">
            Peta kecamatan
          </h2>
          <p className="micro muted">Warna menunjukkan {u.label.toLowerCase()} per kecamatan. Pilih kecamatan untuk melihat kelurahannya.</p>
        </div>
        <div className="chips" role="group" aria-label="Ukuran pada peta">
          {(Object.keys(UKURAN) as Ukuran[]).map((k) => (
            <button key={k} type="button" className="chip" aria-pressed={ukuran === k} onClick={() => setUkuran(k)}>
              {UKURAN[k].label}
            </button>
          ))}
        </div>
        <svg viewBox="0 0 400 300" className="pm-peta__svg" role="group" aria-label="Peta skematis kecamatan Kota Kupang">
          <text x="200" y="12" textAnchor="middle" className="pm-peta__laut">
            Teluk Kupang (utara)
          </text>
          {wilayah
            .filter((k) => LETAK[k.kunci])
            .map((k) => {
              const l = LETAK[k.kunci]
              const s = skala(u.nilai(k))
              const aktif = pilih === k.kunci
              const teks = u.teks(k)
              const gelap = s !== null && s > 0.55
              return (
                <g
                  key={k.kunci}
                  role="button"
                  tabIndex={0}
                  aria-pressed={aktif}
                  aria-label={`${k.nama}: ${teks ?? "disamarkan"}`}
                  className="pm-peta__petak"
                  onClick={() => setPilih(aktif ? null : k.kunci)}
                  onKeyDown={(e) => {
                    if (e.key === "Enter" || e.key === " ") {
                      e.preventDefault()
                      setPilih(aktif ? null : k.kunci)
                    }
                  }}
                >
                  <rect
                    x={l.x}
                    y={l.y}
                    width={l.w}
                    height={l.h}
                    rx={10}
                    className={aktif ? "is-aktif" : undefined}
                    style={{ fill: s === null ? "var(--paper-2)" : `color-mix(in oklab, var(--danger) ${Math.round(8 + s * 62)}%, var(--surface))` }}
                  />
                  <text x={l.x + l.w / 2} y={l.y + l.h / 2} dy="-0.3em" textAnchor="middle" className={gelap ? "pm-peta__nama is-gelap" : "pm-peta__nama"}>
                    {k.nama}
                  </text>
                  <text x={l.x + l.w / 2} y={l.y + l.h / 2} dy="1.1em" textAnchor="middle" className={gelap ? "pm-peta__nilai is-gelap" : "pm-peta__nilai"}>
                    {teks ?? "disamarkan"}
                  </text>
                </g>
              )
            })}
        </svg>
        <p className="micro muted pm-peta__legenda">
          <span>{u.label} per kecamatan:</span>
          <span className="pm-peta__rentang">
            terendah
            <span className="pm-peta__skala" aria-hidden="true" />
            tertinggi
          </span>
        </p>
      </section>

      <section className="card card--pad pm-tabel" aria-labelledby="pm-kecamatan-judul">
        <div className="pm-bagian__kepala">
          <h2 id="pm-kecamatan-judul" className="pm-bagian__judul">
            Per kecamatan
          </h2>
          <p className="micro muted">Kecamatan menurut daftar resmi kelurahan Kota Kupang. Pilih nama kecamatan untuk melihat kelurahannya.</p>
        </div>
        <Tabel
          judul="Kecamatan"
          baris={wilayah.map((k) => ({ kunci: k.kunci || "tanpa", nama: k.nama, angka: k, pilih: k.kelurahan.length ? () => setPilih(pilih === k.kunci ? null : k.kunci) : undefined, aktif: pilih === k.kunci }))}
        />
      </section>

      {dipilih && (
        <section className="card card--pad pm-tabel" aria-labelledby="pm-kelurahan-judul">
          <div className="pm-bagian__kepala">
            <h2 id="pm-kelurahan-judul" className="pm-bagian__judul">
              Kelurahan di {dipilih.nama}
            </h2>
          </div>
          <Tabel judul="Kelurahan" baris={dipilih.kelurahan.map((l) => ({ kunci: l.nama, nama: namaWilayah(l.nama), angka: l }))} />
        </section>
      )}
    </div>
  )
}

function Petak({ label, nilai, catatan }: { label: string; nilai: string | null; catatan?: string | null }) {
  return (
    <div className="tile pm-petak">
      <p className="tile__label">{label}</p>
      <p className="pm-petak__nilai">
        <Nilai teks={nilai} />
      </p>
      {catatan && <p className="pm-petak__catatan">{catatan}</p>}
    </div>
  )
}

function Tabel({ judul, baris }: { judul: string; baris: { kunci: string; nama: string; angka: AngkaWilayah; pilih?: () => void; aktif?: boolean }[] }) {
  return (
    <div className="table-wrap pm-tabel__isi" role="region" aria-label={`Tabel per ${judul.toLowerCase()}`} tabIndex={0}>
      <table className="table">
        <caption className="sr-only">Kendaraan, kepatuhan, dan tagihan PKB per {judul.toLowerCase()}</caption>
        <thead>
          <tr>
            <th scope="col">{judul}</th>
            <th scope="col" className="pm-angka">
              Kendaraan
            </th>
            <th scope="col" className="pm-angka">
              Roda dua
            </th>
            <th scope="col" className="pm-angka">
              Roda tiga+
            </th>
            <th scope="col" className="pm-angka">
              Taat
            </th>
            <th scope="col" className="pm-angka">
              Belum bayar &lt; 1 th
            </th>
            <th scope="col" className="pm-angka">
              Menunggak ≥ 1 th
            </th>
            <th scope="col" className="pm-angka">
              % menunggak
            </th>
            <th scope="col" className="pm-angka">
              Tagihan
            </th>
          </tr>
        </thead>
        <tbody>
          {baris.map((b) => (
            <tr key={b.kunci} className={b.aktif ? "is-aktif" : undefined}>
              <th scope="row">
                {b.pilih ? (
                  <button type="button" className="pm-tabel__pilih" aria-expanded={b.aktif} onClick={b.pilih}>
                    {b.nama}
                  </button>
                ) : (
                  b.nama
                )}
              </th>
              <td className="pm-angka">
                <Nilai teks={angka(b.angka.kendaraan)} />
              </td>
              <td className="pm-angka">
                <Nilai teks={angka(b.angka.rodaDua)} />
              </td>
              <td className="pm-angka">
                <Nilai teks={angka(b.angka.rodaTigaLebih)} />
              </td>
              <td className="pm-angka">
                <Nilai teks={angka(b.angka.taat)} />
              </td>
              <td className="pm-angka">
                <Nilai teks={angka(b.angka.belumBayar)} />
              </td>
              <td className="pm-angka">
                <Nilai teks={angka(b.angka.menunggak)} />
              </td>
              <td className="pm-angka">
                <Nilai teks={persen(b.angka.menunggak, b.angka.kendaraan)} />
              </td>
              <td className="pm-angka pm-angka--kuat">
                <Nilai teks={b.angka.tagihan === null ? null : `Rp${fmtAngka.format(b.angka.tagihan)}`} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

/** Ambil alih elemen [data-react="pemetaan-pkb"] */
export function pasang(el: HTMLElement) {
  createRoot(el).render(<PemetaanPkb />)
}
