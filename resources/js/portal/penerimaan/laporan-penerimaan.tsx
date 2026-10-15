/*
 * Laporan Penerimaan di portal (D-048): penerimaan per hari, bulan, atau
 * rentang dari impor STS yang sudah ditampilkan Administrator, dibaca dari
 * GET /api/public/penerimaan. Server sudah menyamarkan kelompok dengan kurang
 * dari 3 transaksi; komponen ini hanya menampilkan apa yang diberikan.
 * Periode dan saringan ditulis ke alamat halaman agar laporan bisa dibagikan.
 */
import { useEffect, useId, useMemo, useRef, useState, type ReactNode } from "react"
import { createRoot } from "react-dom/client"

import { KOLOM_CSV, teksCsv, unduhTeks, type KunciJumlah } from "./csv"
import {
  galatPeriode,
  gantiMode,
  geserPeriode,
  labelPeriode,
  labelTanggal,
  periodeBulan,
  periodeDari,
  type Mode,
  type Periode,
} from "./periode"
import "./laporan-penerimaan.css"

type Jumlah = Record<KunciJumlah, number>

interface Baris extends Partial<Jumlah> {
  nilai: string
  nama?: string
  kecamatan?: string | null
  disamarkan?: boolean
  kelompok?: number
}

const DIMENSI = ["loket", "jenisDaftar", "jenisKendaraan", "kecamatan", "kelurahan"] as const
type Dimensi = (typeof DIMENSI)[number]

const LABEL: Record<Dimensi, string> = {
  loket: "Loket",
  jenisDaftar: "Jenis daftar",
  jenisKendaraan: "Jenis kendaraan",
  kecamatan: "Kecamatan",
  kelurahan: "Kelurahan",
}

interface Laporan {
  tersedia: { awal: string; akhir: string } | null
  dari: string | null
  sampai: string | null
  ringkasan: Jumlah | null
  disamarkan: boolean
  pembanding: { dari: string; sampai: string; ringkasan: Jumlah | null } | null
  rincian: Partial<Record<Dimensi | "tanggal", Baris[]>>
  pilihan: { loket: { nilai: string; nama: string }[] } & Record<Exclude<Dimensi, "loket">, string[]>
  minimum: number
}

type Pilihan = Partial<Record<Dimensi, string[]>>

interface Permintaan {
  /** Kosong: hari terakhir yang sudah diterbitkan */
  periode: Periode | null
  pilih: Pilihan
}

/** Komponen penerimaan; semua opsen dijumlah menjadi satu */
const KOMPONEN: { kunci: string; label: string; nilai: (j: Jumlah) => number }[] = [
  { kunci: "pkb", label: "PKB", nilai: (j) => j.pkb },
  { kunci: "bbn", label: "BBNKB", nilai: (j) => j.bbn },
  { kunci: "denda", label: "Denda", nilai: (j) => j.dendaPkb + j.dendaBbn },
  { kunci: "tunggakan", label: "Tunggakan", nilai: (j) => j.tunggakan + j.dendaTunggakan },
  {
    kunci: "opsen",
    label: "Opsen",
    nilai: (j) => j.opsenPkb + j.opsenBbn + j.opsenDendaPkb + j.opsenDendaBbn + j.opsenTunggakan + j.opsenDendaTunggakan,
  },
]

const fmtAngka = new Intl.NumberFormat("id-ID")
const angka = (n: number) => fmtAngka.format(n)
const rupiah = (n: number) => `Rp${fmtAngka.format(Math.round(n))}`
/** Porsi dalam persen; porsi yang ada tetapi di bawah 0,05% tidak dibulatkan menjadi "0%" */
function persen(bagian: number, total: number): string {
  if (total <= 0) return "—"
  const p = (bagian / total) * 100
  return p > 0 && p < 0.05 ? "<0,1%" : `${new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 }).format(p)}%`
}

/** Rupiah ringkas untuk label batang, mis. "563,4 jt" */
function rupiahRingkas(n: number): string {
  const f = (x: number) => new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 }).format(x)
  if (n >= 1e9) return `${f(n / 1e9)} M`
  if (n >= 1e6) return `${f(n / 1e6)} jt`
  if (n >= 1e3) return `${f(n / 1e3)} rb`
  return angka(n)
}

const provinsi = (j: Jumlah) => j.pkb + j.bbn + j.dendaPkb + j.dendaBbn + j.tunggakan + j.dendaTunggakan
const opsen = (j: Jumlah) => KOMPONEN[4].nilai(j)

/* ---------- Alamat halaman ---------- */

function dariAlamat(): Permintaan {
  const q = new URLSearchParams(window.location.search)
  const dari = q.get("dari")
  const sampai = q.get("sampai")
  const pilih: Pilihan = {}
  for (const d of DIMENSI) {
    const nilai = q.getAll(d).filter(Boolean)
    if (nilai.length > 0) pilih[d] = nilai
  }
  return { periode: dari && sampai ? periodeDari(dari, sampai) : null, pilih }
}

function keAlamat(p: Permintaan): string {
  const q = new URLSearchParams()
  if (p.periode) {
    q.set("dari", p.periode.dari)
    q.set("sampai", p.periode.sampai)
  }
  for (const d of DIMENSI) for (const nilai of p.pilih[d] ?? []) q.append(d, nilai)
  const teks = q.toString()
  return `${window.location.pathname}${teks ? `?${teks}` : ""}`
}

async function muatLaporan(p: Permintaan, sinyal: AbortSignal): Promise<Laporan> {
  const q = new URLSearchParams()
  if (p.periode) {
    q.set("dari", p.periode.dari)
    q.set("sampai", p.periode.sampai)
  }
  for (const d of DIMENSI) for (const nilai of p.pilih[d] ?? []) q.append(`${d}[]`, nilai)
  const teks = q.toString()
  const res = await fetch(`/api/public/penerimaan${teks ? `?${teks}` : ""}`, { headers: { Accept: "application/json" }, signal: sinyal })
  if (!res.ok) {
    let pesan = "Laporan penerimaan belum dapat dimuat."
    try {
      const j = (await res.json()) as { message?: string; errors?: Record<string, string[]> }
      pesan = Object.values(j.errors ?? {})[0]?.[0] ?? j.message ?? pesan
    } catch {
      // respons bukan JSON
    }
    throw new Error(pesan)
  }
  return (await res.json()) as Laporan
}

/* ---------- Komponen utama ---------- */

function LaporanPenerimaan() {
  const [permintaan, setPermintaan] = useState<Permintaan>(dariAlamat)
  const [laporan, setLaporan] = useState<Laporan | null>(null)
  const [memuat, setMemuat] = useState(true)
  const [galat, setGalat] = useState<string | null>(null)
  const [ulang, setUlang] = useState(0)

  useEffect(() => {
    const kendali = new AbortController()
    setMemuat(true)
    muatLaporan(permintaan, kendali.signal)
      .then((l) => {
        setLaporan(l)
        setGalat(null)
        setMemuat(false)
      })
      .catch((e: unknown) => {
        if (kendali.signal.aborted) return
        setGalat((e as Error).message)
        setMemuat(false)
      })
    return () => kendali.abort()
  }, [permintaan, ulang])

  // Alamat halaman mengikuti laporan yang tampil, agar bisa dibagikan
  useEffect(() => {
    window.history.replaceState(null, "", keAlamat(permintaan))
  }, [permintaan])

  const ubahPeriode = (periode: Periode) => setPermintaan((p) => ({ ...p, periode }))
  const ubahPilih = (d: Dimensi, nilai: string[]) =>
    setPermintaan((p) => {
      const pilih = { ...p.pilih }
      if (nilai.length > 0) pilih[d] = nilai
      else delete pilih[d]
      return { ...p, pilih }
    })
  const hapusSaringan = () => setPermintaan((p) => ({ ...p, pilih: {} }))
  const adaSaringan = Object.keys(permintaan.pilih).length > 0

  if (!laporan) {
    return galat ? (
      <Pesan judul="Laporan penerimaan belum dapat dimuat." isi={galat}>
        <button type="button" className="btn btn--dark btn--sm" onClick={() => (permintaan.periode || adaSaringan ? setPermintaan({ periode: null, pilih: {} }) : setUlang((n) => n + 1))}>
          {permintaan.periode || adaSaringan ? "Tampilkan laporan terbaru" : "Coba lagi"}
        </button>
      </Pesan>
    ) : (
      <p className="center muted" role="status" style={{ padding: "48px 0" }}>
        Memuat laporan penerimaan…
      </p>
    )
  }

  if (!laporan.tersedia || !laporan.dari || !laporan.sampai) {
    return <Pesan judul="Laporan penerimaan belum diterbitkan." isi="Laporan tampil di halaman ini setelah petugas memeriksa data penerimaan setiap loket." />
  }

  const periode = permintaan.periode ?? periodeDari(laporan.dari, laporan.sampai)

  return (
    <div className="lp" aria-busy={memuat}>
      <Kendali
        periode={periode}
        tersedia={laporan.tersedia}
        ubahPeriode={ubahPeriode}
        laporan={laporan}
        pilih={permintaan.pilih}
        ubahPilih={ubahPilih}
        adaSaringan={adaSaringan}
        hapusSaringan={hapusSaringan}
      />
      {galat && (
        <p className="lp-galat" role="alert">
          {galat}{" "}
          <button type="button" className="link" onClick={() => setUlang((n) => n + 1)}>
            Coba lagi
          </button>
        </p>
      )}
      <div className={memuat ? "lp-isi is-memuat" : "lp-isi"}>
        <Isi laporan={laporan} adaSaringan={adaSaringan} hapusSaringan={hapusSaringan} ubahPilih={ubahPilih} bukaHari={(h) => ubahPeriode(periodeDari(h, h))} />
      </div>
    </div>
  )
}

function Pesan({ judul, isi, children }: { judul: string; isi: string; children?: ReactNode }) {
  return (
    <div className="card card--pad lp-pesan" role="status">
      <p className="lp-pesan__judul">{judul}</p>
      <p className="muted">{isi}</p>
      {children && <div className="lp-pesan__aksi">{children}</div>}
    </div>
  )
}

/* ---------- Periode dan saringan ---------- */

function Kendali({
  periode,
  tersedia,
  ubahPeriode,
  laporan,
  pilih,
  ubahPilih,
  adaSaringan,
  hapusSaringan,
}: {
  periode: Periode
  tersedia: { awal: string; akhir: string }
  ubahPeriode: (p: Periode) => void
  laporan: Laporan
  pilih: Pilihan
  ubahPilih: (d: Dimensi, nilai: string[]) => void
  adaSaringan: boolean
  hapusSaringan: () => void
}) {
  const [draf, setDraf] = useState(periode)
  const galatId = useId()
  // Isian rentang mengikuti periode yang tampil sampai pengunjung mengubahnya
  const { mode, dari, sampai } = periode
  useEffect(() => setDraf({ mode, dari, sampai }), [mode, dari, sampai])

  const pakai = (p: Periode) => {
    setDraf(p)
    if (!galatPeriode(p)) ubahPeriode(p)
  }
  const galat = galatPeriode(draf)
  const sebelum = geserPeriode(periode, -1)
  const sesudah = geserPeriode(periode, 1)

  const opsi = (d: Dimensi): { nilai: string; label: string }[] =>
    d === "loket" ? laporan.pilihan.loket.map((l) => ({ nilai: l.nilai, label: l.nama })) : laporan.pilihan[d].map((n) => ({ nilai: n, label: n }))

  return (
    <div className="card card--pad lp-kendali">
      <div className="lp-kendali__baris">
        <div className="chips" role="group" aria-label="Jenis laporan">
          {(["harian", "bulanan", "rentang"] as Mode[]).map((m) => (
            <button key={m} type="button" className="chip" aria-pressed={periode.mode === m} onClick={() => pakai(gantiMode(periode, m, tersedia.akhir))}>
              {m === "harian" ? "Harian" : m === "bulanan" ? "Bulanan" : "Rentang"}
            </button>
          ))}
        </div>
        {draf.mode === "rentang" ? (
          <div className="lp-kendali__tanggal">
            <input
              className="input"
              type="date"
              aria-label="Dari tanggal"
              aria-invalid={!!galat}
              aria-describedby={galat ? galatId : undefined}
              min={tersedia.awal}
              max={tersedia.akhir}
              value={draf.dari}
              onChange={(e) => pakai({ ...draf, dari: e.target.value })}
            />
            <span className="muted">s.d.</span>
            <input
              className="input"
              type="date"
              aria-label="Sampai tanggal"
              aria-invalid={!!galat}
              aria-describedby={galat ? galatId : undefined}
              min={tersedia.awal}
              max={tersedia.akhir}
              value={draf.sampai}
              onChange={(e) => pakai({ ...draf, sampai: e.target.value })}
            />
          </div>
        ) : (
          <div className="lp-kendali__tanggal">
            <button
              type="button"
              className="lp-geser"
              aria-label={periode.mode === "harian" ? "Hari sebelumnya" : "Bulan sebelumnya"}
              disabled={sebelum.sampai < tersedia.awal}
              onClick={() => pakai(sebelum)}
            >
              <svg className="i i--sm" aria-hidden="true">
                <use href="#i-chevron-left" />
              </svg>
            </button>
            {periode.mode === "harian" ? (
              <input
                className="input"
                type="date"
                aria-label="Tanggal laporan"
                min={tersedia.awal}
                max={tersedia.akhir}
                value={draf.dari}
                onChange={(e) => pakai({ mode: "harian", dari: e.target.value, sampai: e.target.value })}
              />
            ) : (
              <input
                className="input"
                type="month"
                aria-label="Bulan laporan"
                min={tersedia.awal.slice(0, 7)}
                max={tersedia.akhir.slice(0, 7)}
                value={draf.dari.slice(0, 7)}
                onChange={(e) => e.target.value && pakai(periodeBulan(e.target.value))}
              />
            )}
            <button
              type="button"
              className="lp-geser"
              aria-label={periode.mode === "harian" ? "Hari berikutnya" : "Bulan berikutnya"}
              disabled={sesudah.dari > tersedia.akhir}
              onClick={() => pakai(sesudah)}
            >
              <svg className="i i--sm" aria-hidden="true">
                <use href="#i-chevron-right" />
              </svg>
            </button>
          </div>
        )}
        <p className="lp-kendali__periode" aria-live="polite">
          {labelPeriode(periode.dari, periode.sampai)}
        </p>
      </div>
      {galat && (
        <p id={galatId} className="lp-galat" role="alert">
          {galat}
        </p>
      )}
      <div className="lp-kendali__saringan">
        <span className="lp-kendali__label">Saring</span>
        {DIMENSI.map((d) => (
          <PilihSaringan key={d} dimensi={d} opsi={opsi(d)} terpilih={pilih[d] ?? []} ubah={(nilai) => ubahPilih(d, nilai)} />
        ))}
        {adaSaringan && (
          <button type="button" className="btn btn--ghost btn--sm" onClick={hapusSaringan}>
            Hapus saringan
          </button>
        )}
      </div>
      <p className="micro muted">
        Data tersedia {labelTanggal(tersedia.awal)} sampai {labelTanggal(tersedia.akhir)}.
      </p>
    </div>
  )
}

function PilihSaringan({
  dimensi,
  opsi,
  terpilih,
  ubah,
}: {
  dimensi: Dimensi
  opsi: { nilai: string; label: string }[]
  terpilih: string[]
  ubah: (nilai: string[]) => void
}) {
  const [cari, setCari] = useState("")
  const ref = useRef<HTMLDetailsElement>(null)
  const label = LABEL[dimensi]
  // Nilai terpilih yang tidak ada di periode ini tetap tampil agar bisa dilepas
  const semua = [...opsi, ...terpilih.filter((t) => !opsi.some((o) => o.nilai === t)).map((t) => ({ nilai: t, label: t }))]
  const q = cari.trim().toLowerCase()
  const tersaring = q ? semua.filter((o) => o.label.toLowerCase().includes(q)) : semua

  // Tutup saat klik di luar atau Escape
  useEffect(() => {
    const el = ref.current
    if (!el) return
    const luar = (e: MouseEvent) => {
      if (el.open && !el.contains(e.target as Node)) el.open = false
    }
    const tombol = (e: KeyboardEvent) => {
      if (e.key === "Escape" && el.open) {
        el.open = false
        el.querySelector("summary")?.focus()
      }
    }
    document.addEventListener("click", luar)
    el.addEventListener("keydown", tombol)
    return () => {
      document.removeEventListener("click", luar)
      el.removeEventListener("keydown", tombol)
    }
  }, [])

  return (
    <details className="lp-saring" ref={ref}>
      <summary className={terpilih.length > 0 ? "chip is-active" : "chip"}>
        {label}
        {terpilih.length > 0 && <span className="lp-saring__jumlah">{terpilih.length}</span>}
        <svg className="i i--sm" aria-hidden="true">
          <use href="#i-chevron-down" />
        </svg>
      </summary>
      <div className="lp-saring__panel">
        {semua.length > 6 && (
          <input
            className="input lp-saring__cari"
            type="search"
            placeholder={`Cari ${label.toLowerCase()}`}
            aria-label={`Cari ${label.toLowerCase()}`}
            value={cari}
            onChange={(e) => setCari(e.target.value)}
          />
        )}
        <ul className="lp-saring__daftar" role="list" aria-label={label}>
          {tersaring.map((o) => (
            <li key={o.nilai}>
              <label className="lp-saring__opsi">
                <input
                  type="checkbox"
                  checked={terpilih.includes(o.nilai)}
                  onChange={(e) => ubah(e.target.checked ? [...terpilih, o.nilai] : terpilih.filter((t) => t !== o.nilai))}
                />
                <span>{o.label}</span>
              </label>
            </li>
          ))}
          {tersaring.length === 0 && <li className="lp-saring__kosong">Tidak ada pada periode ini.</li>}
        </ul>
        {terpilih.length > 0 && (
          <button type="button" className="btn btn--ghost btn--sm" onClick={() => ubah([])}>
            Lepas pilihan
          </button>
        )}
      </div>
    </details>
  )
}

/* ---------- Isi laporan ---------- */

function Isi({
  laporan,
  adaSaringan,
  hapusSaringan,
  ubahPilih,
  bukaHari,
}: {
  laporan: Laporan
  adaSaringan: boolean
  hapusSaringan: () => void
  ubahPilih: (d: Dimensi, nilai: string[]) => void
  bukaHari: (hari: string) => void
}) {
  const r = laporan.ringkasan
  if (laporan.disamarkan || !r) {
    return (
      <Pesan
        judul="Angka tidak ditampilkan."
        isi={`Kurang dari ${laporan.minimum} transaksi cocok dengan saringan ini. Angkanya tidak ditampilkan untuk melindungi data wajib pajak.`}
      >
        {adaSaringan && (
          <button type="button" className="btn btn--dark btn--sm" onClick={hapusSaringan}>
            Hapus saringan
          </button>
        )}
      </Pesan>
    )
  }
  if (r.transaksi === 0) {
    return (
      <Pesan judul="Tidak ada transaksi." isi={adaSaringan ? "Tidak ada transaksi yang cocok dengan saringan pada periode ini." : "Belum ada penerimaan yang diterbitkan untuk periode ini."}>
        {adaSaringan && (
          <button type="button" className="btn btn--dark btn--sm" onClick={hapusSaringan}>
            Hapus saringan
          </button>
        )}
      </Pesan>
    )
  }

  const satuHari = laporan.dari === laporan.sampai
  return (
    <>
      <Ringkasan laporan={laporan} ringkasan={r} />
      {!satuHari && <PerHari hari={laporan.rincian.tanggal ?? []} bukaHari={bukaHari} />}
      <div className="lp-kelompok-grid">
        {(["loket", "jenisDaftar", "jenisKendaraan", "kecamatan"] as Dimensi[]).map((d) => (
          <Kelompok key={d} dimensi={d} baris={laporan.rincian[d] ?? []} total={r.jumlah} saring={(nilai) => ubahPilih(d, [nilai])} />
        ))}
      </div>
      <Rincian laporan={laporan} ringkasan={r} />
    </>
  )
}

function Perubahan({ kini, lalu }: { kini: number; lalu: number | undefined }) {
  if (lalu === undefined || lalu === 0) return null
  const ubah = ((kini - lalu) / lalu) * 100
  const naik = ubah >= 0
  return (
    <span className={naik ? "delta delta--up" : "delta delta--down"}>
      <svg className="i i--sm" aria-hidden="true">
        <use href={naik ? "#i-trend-up" : "#i-trend-down"} />
      </svg>
      {naik ? "+" : ""}
      {new Intl.NumberFormat("id-ID", { maximumFractionDigits: Math.abs(ubah) < 10 ? 1 : 0 }).format(ubah)}%
      <span className="sr-only"> dibanding periode sebelumnya</span>
    </span>
  )
}

function Ringkasan({ laporan, ringkasan: r }: { laporan: Laporan; ringkasan: Jumlah }) {
  const p = laporan.pembanding
  const lalu = p?.ringkasan ?? undefined
  const susunan = KOMPONEN.map((k, i) => ({ ...k, rp: k.nilai(r), warna: `var(--lp-${i + 1})` }))
  const baru = laporan.rincian.jenisDaftar?.find((b) => !b.disamarkan && b.nilai.toUpperCase() === "BARU")
  const namaPembanding = laporan.dari === laporan.sampai ? "Hari layanan sebelumnya" : "Periode sebelumnya"

  return (
    <section className="night-panel lp-ringkas" aria-label="Ringkasan penerimaan">
      <div className="tile lp-ringkas__utama">
        <p className="tile__label">
          <span>Total penerimaan · {labelPeriode(laporan.dari!, laporan.sampai!)}</span>
          <Perubahan kini={r.jumlah} lalu={lalu?.jumlah} />
        </p>
        <p className="lp-ringkas__total">{rupiah(r.jumlah)}</p>
        <div
          className="lp-susunan"
          role="img"
          aria-label={`Susunan penerimaan: ${susunan.map((k) => `${k.label} ${persen(k.rp, r.jumlah)}`).join(", ")}`}
        >
          {susunan
            .filter((k) => k.rp > 0)
            .map((k) => (
              <span key={k.kunci} style={{ width: `${(k.rp / r.jumlah) * 100}%`, background: k.warna }} />
            ))}
        </div>
        <dl className="lp-susunan__keterangan">
          {susunan.map((k) => (
            <div key={k.kunci}>
              <dt>
                <span style={{ background: k.warna }} aria-hidden="true" />
                {k.label}
              </dt>
              <dd>{rupiah(k.rp)}</dd>
            </div>
          ))}
        </dl>
        <p className="lp-ringkas__catatan">
          {!p
            ? "Belum ada periode sebelumnya untuk dibandingkan."
            : !p.ringkasan
              ? `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): kurang dari ${laporan.minimum} transaksi, tidak ditampilkan.`
              : p.ringkasan.transaksi === 0
                ? `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): belum ada data.`
                : `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): ${rupiah(p.ringkasan.jumlah)}.`}
        </p>
      </div>
      <div className="lp-ringkas__petak">
        <Petak label="Transaksi" nilai={angka(r.transaksi)} tambahan={<Perubahan kini={r.transaksi} lalu={lalu?.transaksi} />} />
        <Petak
          label="Kendaraan baru"
          nilai={baru ? angka(baru.transaksi ?? 0) : "—"}
          tambahan={baru ? persen(baru.jumlah ?? 0, r.jumlah) : undefined}
          catatan={baru ? rupiah(baru.jumlah ?? 0) : "Tidak ada atau disamarkan"}
        />
        <Petak label="Pajak provinsi" nilai={rupiah(provinsi(r))} tambahan={persen(provinsi(r), r.jumlah)} catatan="PKB, BBNKB, denda, tunggakan" />
        <Petak label="Opsen kabupaten/kota" nilai={rupiah(opsen(r))} tambahan={persen(opsen(r), r.jumlah)} catatan="Untuk daerah asal kendaraan" />
      </div>
    </section>
  )
}

function Petak({ label, nilai, tambahan, catatan }: { label: string; nilai: string; tambahan?: ReactNode; catatan?: string }) {
  return (
    <div className="tile lp-petak">
      <p className="tile__label">
        <span>{label}</span>
        {tambahan}
      </p>
      <p className="lp-petak__nilai">{nilai}</p>
      {catatan && <p className="lp-petak__catatan">{catatan}</p>}
    </div>
  )
}

function PerHari({ hari, bukaHari }: { hari: Baris[]; bukaHari: (hari: string) => void }) {
  const maks = Math.max(1, ...hari.map((h) => h.jumlah ?? 0))
  const adaSamar = hari.some((h) => h.disamarkan)
  return (
    <section className="card card--pad lp-hari" aria-labelledby="lp-hari-judul">
      <div className="lp-bagian__kepala">
        <h2 id="lp-hari-judul" className="lp-bagian__judul">
          Penerimaan per hari
        </h2>
        <p className="micro muted">
          {angka(hari.length)} hari berisi transaksi. Pilih batang untuk membuka laporan hariannya.
          {adaSamar && " Hari bergaris berisi kurang dari 3 transaksi, jadi angkanya tidak ditampilkan."}
        </p>
      </div>
      <ol className="lp-hari__batang" role="list" style={{ gridTemplateColumns: `repeat(${hari.length}, minmax(6px, 1fr))` }}>
        {hari.map((h) => (
          <li key={h.nilai}>
            <button
              type="button"
              className={h.disamarkan ? "lp-hari__kolom is-samar" : "lp-hari__kolom"}
              onClick={() => bukaHari(h.nilai)}
              aria-label={`${labelTanggal(h.nilai, "lengkap")}: ${h.disamarkan ? "disamarkan" : `${rupiah(h.jumlah ?? 0)}, ${angka(h.transaksi ?? 0)} transaksi`}`}
              title={`${labelTanggal(h.nilai)}: ${h.disamarkan ? "disamarkan" : rupiah(h.jumlah ?? 0)}`}
            >
              <span className="lp-hari__isi" style={{ height: h.disamarkan ? "12%" : `${Math.max(2, ((h.jumlah ?? 0) / maks) * 100)}%` }} />
            </button>
          </li>
        ))}
      </ol>
      <div className="lp-hari__sumbu micro muted" aria-hidden="true">
        <span>{labelTanggal(hari[0]?.nilai ?? "")}</span>
        <span>{labelTanggal(hari[hari.length - 1]?.nilai ?? "")}</span>
      </div>
    </section>
  )
}

const BATAS_KELOMPOK = 8

function Kelompok({ dimensi, baris, total, saring }: { dimensi: Dimensi; baris: Baris[]; total: number; saring: (nilai: string) => void }) {
  const tampil = baris.filter((b) => !b.disamarkan).slice(0, BATAS_KELOMPOK)
  const lainnya = baris.filter((b) => !b.disamarkan).length - tampil.length
  const samar = baris.find((b) => b.disamarkan)
  const maks = Math.max(1, ...baris.map((b) => b.jumlah ?? 0))
  const judulId = useId()

  return (
    <section className="card card--pad lp-kelompok" aria-labelledby={judulId}>
      <h2 id={judulId} className="lp-bagian__judul">
        Per {LABEL[dimensi].toLowerCase()}
      </h2>
      <ul className="lp-kelompok__daftar" role="list">
        {tampil.map((b) => (
          <li key={`${b.nilai}|${b.kecamatan ?? ""}`}>
            <div className="lp-kelompok__baris">
              <button type="button" className="lp-kelompok__nama" onClick={() => saring(b.nilai)} title="Saring laporan dengan ini">
                {b.nama ?? b.nilai}
              </button>
              <span className="lp-kelompok__nilai">{rupiahRingkas(b.jumlah ?? 0)}</span>
            </div>
            <div className="lp-kelompok__jalur" aria-hidden="true">
              <span style={{ width: `${((b.jumlah ?? 0) / maks) * 100}%` }} />
            </div>
            <p className="lp-kelompok__meta">
              {angka(b.transaksi ?? 0)} transaksi · {persen(b.jumlah ?? 0, total)}
            </p>
          </li>
        ))}
        {samar && (
          <li className="is-samar">
            <div className="lp-kelompok__baris">
              <span className="lp-kelompok__nama">{samar.nilai}</span>
              <span className="lp-kelompok__nilai">{rupiahRingkas(samar.jumlah ?? 0)}</span>
            </div>
            <div className="lp-kelompok__jalur" aria-hidden="true">
              <span style={{ width: `${((samar.jumlah ?? 0) / maks) * 100}%` }} />
            </div>
            <p className="lp-kelompok__meta">
              {angka(samar.transaksi ?? 0)} transaksi dari {angka(samar.kelompok ?? 0)} kelompok kecil · {persen(samar.jumlah ?? 0, total)}
            </p>
          </li>
        )}
      </ul>
      {lainnya > 0 && <p className="micro muted">Dan {angka(lainnya)} lainnya di tabel rincian.</p>}
    </section>
  )
}

/* ---------- Tabel rincian ---------- */

type Tab = Dimensi | "tanggal"

function Rincian({ laporan, ringkasan: r }: { laporan: Laporan; ringkasan: Jumlah }) {
  const [pilihan, setPilihan] = useState<Tab>("jenisDaftar")
  const tab: Tab[] = laporan.dari === laporan.sampai ? ["jenisDaftar", "jenisKendaraan", "loket", "kecamatan", "kelurahan"] : ["jenisDaftar", "jenisKendaraan", "loket", "kecamatan", "kelurahan", "tanggal"]
  const aktif = tab.includes(pilihan) ? pilihan : "jenisDaftar"
  const baris = laporan.rincian[aktif] ?? []
  const judul = aktif === "tanggal" ? "Tanggal" : LABEL[aktif]
  const kelurahan = aktif === "kelurahan"
  const tampil = (b: Baris) => (aktif === "tanggal" ? labelTanggal(b.nilai) : (b.nama ?? b.nilai))

  const csv = useMemo(() => {
    const kepala = [judul, ...(kelurahan ? ["Kecamatan"] : []), ...KOLOM_CSV.map(([label]) => label)]
    const isi = baris.map((b) => [
      aktif === "tanggal" ? b.nilai : (b.nama ?? b.nilai),
      ...(kelurahan ? [b.kecamatan ?? ""] : []),
      ...KOLOM_CSV.map(([, kunci]) => (b.disamarkan && aktif === "tanggal" ? "disamarkan" : (b[kunci] ?? ""))),
    ])
    const jumlah = ["Jumlah", ...(kelurahan ? [""] : []), ...KOLOM_CSV.map(([, kunci]) => r[kunci])]
    return teksCsv([kepala, ...isi, jumlah])
  }, [aktif, baris, judul, kelurahan, r])

  const unduh = () => {
    const periode = laporan.dari === laporan.sampai ? laporan.dari : `${laporan.dari}_sd_${laporan.sampai}`
    unduhTeks(csv, `penerimaan-per-${judul.toLowerCase().replace(/\s+/g, "-")}-${periode}.csv`)
  }

  return (
    <section className="card card--pad lp-rincian" aria-labelledby="lp-rincian-judul">
      <div className="lp-bagian__kepala lp-rincian__kepala">
        <div>
          <h2 id="lp-rincian-judul" className="lp-bagian__judul">
            Rincian penerimaan
          </h2>
          <p className="micro muted">Rupiah setiap komponen. Semua opsen dijumlah menjadi satu kolom; berkas CSV memuat rinciannya.</p>
        </div>
        <button type="button" className="btn btn--ghost btn--sm" onClick={unduh}>
          <svg className="i i--sm" aria-hidden="true">
            <use href="#i-download" />
          </svg>
          Unduh CSV
        </button>
      </div>
      <div className="chips lp-rincian__tab" role="group" aria-label="Rincian per">
        {tab.map((t) => (
          <button key={t} type="button" className="chip" aria-pressed={aktif === t} onClick={() => setPilihan(t)}>
            {t === "tanggal" ? "Tanggal" : LABEL[t]}
          </button>
        ))}
      </div>
      <div className="table-wrap lp-rincian__tabel">
        <table className="table">
          <caption className="sr-only">Rincian penerimaan per {judul.toLowerCase()}</caption>
          <thead>
            <tr>
              <th scope="col">{judul}</th>
              {kelurahan && <th scope="col">Kecamatan</th>}
              <th scope="col" className="lp-angka">
                Transaksi
              </th>
              {KOMPONEN.map((k) => (
                <th key={k.kunci} scope="col" className="lp-angka">
                  {k.label}
                </th>
              ))}
              <th scope="col" className="lp-angka">
                Jumlah
              </th>
            </tr>
          </thead>
          <tbody>
            {baris.map((b) => (
              <tr key={`${b.nilai}|${b.kecamatan ?? ""}`} className={b.disamarkan ? "is-samar" : undefined}>
                <th scope="row">{tampil(b)}</th>
                {kelurahan && <td>{b.kecamatan ?? "—"}</td>}
                {b.disamarkan && aktif === "tanggal" ? (
                  <td className="lp-angka muted" colSpan={KOMPONEN.length + 2}>
                    Disamarkan (kurang dari 3 transaksi)
                  </td>
                ) : (
                  <>
                    <td className="lp-angka">{angka(b.transaksi ?? 0)}</td>
                    {KOMPONEN.map((k) => (
                      <td key={k.kunci} className="lp-angka">
                        {angka(k.nilai(b as Jumlah))}
                      </td>
                    ))}
                    <td className="lp-angka lp-angka--kuat">{angka(b.jumlah ?? 0)}</td>
                  </>
                )}
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr>
              <th scope="row" colSpan={kelurahan ? 2 : 1}>
                Jumlah
              </th>
              <td className="lp-angka">{angka(r.transaksi)}</td>
              {KOMPONEN.map((k) => (
                <td key={k.kunci} className="lp-angka">
                  {angka(k.nilai(r))}
                </td>
              ))}
              <td className="lp-angka lp-angka--kuat">{angka(r.jumlah)}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>
  )
}

/** Ambil alih elemen [data-react="laporan-penerimaan"] */
export function pasang(el: HTMLElement) {
  createRoot(el).render(<LaporanPenerimaan />)
}
