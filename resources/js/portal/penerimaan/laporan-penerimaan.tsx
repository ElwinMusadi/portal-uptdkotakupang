/*
 * Laporan Penerimaan di portal (D-048, D-049): penerimaan per hari atau per
 * bulan dari impor STS yang sudah ditampilkan Administrator, dibaca dari
 * GET /api/public/penerimaan. Laporannya berupa tabel tetap: hari memuat
 * total dan komponennya saja; bulan juga memuat setiap hari dan setiap
 * kelompok (transaksi dan jumlah saja). Server sudah menyamarkan angka yang
 * mewakili kurang dari 3 transaksi; komponen ini hanya menampilkan apa yang
 * diberikan. Periode ditulis ke alamat halaman agar laporan bisa dibagikan.
 */
import { useEffect, useId, useMemo, useState, type ReactNode } from "react"
import { createRoot } from "react-dom/client"

import { teksCsv, unduhTeks, type KunciJumlah } from "./csv"
import { gantiMode, geserPeriode, labelPeriode, labelTanggal, periodeBulan, TANGGAL_ISO, type Periode } from "./periode"
import "./laporan-penerimaan.css"

type Jumlah = Record<KunciJumlah, number>

/** Yang tampil dari sebuah kelompok */
interface Angka {
  transaksi: number
  jumlah: number
}

interface Baris extends Partial<Angka> {
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
  periode: "harian" | "bulanan" | null
  dari: string | null
  sampai: string | null
  ringkasan: Jumlah | null
  kendaraanBaru: Angka | null
  disamarkan: boolean
  pembanding: { dari: string; sampai: string; ringkasan: Angka | null } | null
  rincian: Partial<Record<Dimensi | "tanggal", Baris[]>>
  minimum: number
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

const BULAN_ISO = /^\d{4}-\d{2}$/

/** Periode di alamat halaman (?tanggal= atau ?bulan=); kosong: hari terakhir yang diterbitkan */
function dariAlamat(): Periode | null {
  const q = new URLSearchParams(window.location.search)
  const tanggal = q.get("tanggal") ?? ""
  const bulan = q.get("bulan") ?? ""
  if (TANGGAL_ISO.test(tanggal)) return { mode: "harian", dari: tanggal, sampai: tanggal }
  if (BULAN_ISO.test(bulan)) return periodeBulan(bulan)
  return null
}

/** Parameter periode untuk alamat halaman dan API */
function kueri(p: Periode | null): string {
  if (!p) return ""
  return p.mode === "bulanan" ? `?bulan=${p.dari.slice(0, 7)}` : `?tanggal=${p.dari}`
}

async function muatLaporan(p: Periode | null, sinyal: AbortSignal): Promise<Laporan> {
  const res = await fetch(`/api/public/penerimaan${kueri(p)}`, { headers: { Accept: "application/json" }, signal: sinyal })
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
  const [periode, setPeriode] = useState<Periode | null>(dariAlamat)
  const [laporan, setLaporan] = useState<Laporan | null>(null)
  const [memuat, setMemuat] = useState(true)
  const [galat, setGalat] = useState<string | null>(null)
  const [ulang, setUlang] = useState(0)

  useEffect(() => {
    const kendali = new AbortController()
    setMemuat(true)
    muatLaporan(periode, kendali.signal)
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
  }, [periode, ulang])

  // Alamat halaman mengikuti laporan yang tampil, agar bisa dibagikan
  useEffect(() => {
    window.history.replaceState(null, "", `${window.location.pathname}${kueri(periode)}`)
  }, [periode])

  if (!laporan) {
    return galat ? (
      <Pesan judul="Laporan penerimaan belum dapat dimuat." isi={galat}>
        <button type="button" className="btn btn--dark btn--sm" onClick={() => (periode ? setPeriode(null) : setUlang((n) => n + 1))}>
          {periode ? "Tampilkan laporan terbaru" : "Coba lagi"}
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

  const tampil: Periode = periode ?? { mode: "harian", dari: laporan.dari, sampai: laporan.sampai }

  return (
    <div className="lp" aria-busy={memuat}>
      <Kendali periode={tampil} tersedia={laporan.tersedia} ubah={setPeriode} />
      {galat && (
        <p className="lp-galat" role="alert">
          {galat}{" "}
          <button type="button" className="link" onClick={() => setUlang((n) => n + 1)}>
            Coba lagi
          </button>
        </p>
      )}
      <div className={memuat ? "lp-isi is-memuat" : "lp-isi"}>
        <Isi laporan={laporan} ubah={setPeriode} />
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

/* ---------- Periode ---------- */

function Kendali({ periode, tersedia, ubah }: { periode: Periode; tersedia: { awal: string; akhir: string }; ubah: (p: Periode) => void }) {
  const harian = periode.mode === "harian"
  const nilai = harian ? periode.dari : periode.dari.slice(0, 7)
  // Isian mengikuti periode yang tampil, tetapi tidak direset selagi pengunjung mengetik
  const [draf, setDraf] = useState(nilai)
  useEffect(() => setDraf(nilai), [nilai])
  const sebelum = geserPeriode(periode, -1)
  const sesudah = geserPeriode(periode, 1)

  return (
    <div className="card card--pad lp-kendali">
      <div className="lp-kendali__baris">
        <div className="chips" role="group" aria-label="Jenis laporan">
          {(["harian", "bulanan"] as const).map((m) => (
            <button key={m} type="button" className="chip" aria-pressed={periode.mode === m} onClick={() => ubah(gantiMode(periode, m, tersedia.akhir))}>
              {m === "harian" ? "Harian" : "Bulanan"}
            </button>
          ))}
        </div>
        <div className="lp-kendali__tanggal">
          <button type="button" className="lp-geser" aria-label={harian ? "Hari sebelumnya" : "Bulan sebelumnya"} disabled={sebelum.sampai < tersedia.awal} onClick={() => ubah(sebelum)}>
            <svg className="i i--sm" aria-hidden="true">
              <use href="#i-chevron-left" />
            </svg>
          </button>
          {harian ? (
            <input
              className="input"
              type="date"
              aria-label="Tanggal laporan"
              min={tersedia.awal}
              max={tersedia.akhir}
              value={draf}
              onChange={(e) => {
                setDraf(e.target.value)
                if (TANGGAL_ISO.test(e.target.value)) ubah({ mode: "harian", dari: e.target.value, sampai: e.target.value })
              }}
            />
          ) : (
            <input
              className="input"
              type="month"
              aria-label="Bulan laporan"
              min={tersedia.awal.slice(0, 7)}
              max={tersedia.akhir.slice(0, 7)}
              value={draf}
              onChange={(e) => {
                setDraf(e.target.value)
                if (BULAN_ISO.test(e.target.value)) ubah(periodeBulan(e.target.value))
              }}
            />
          )}
          <button type="button" className="lp-geser" aria-label={harian ? "Hari berikutnya" : "Bulan berikutnya"} disabled={sesudah.dari > tersedia.akhir} onClick={() => ubah(sesudah)}>
            <svg className="i i--sm" aria-hidden="true">
              <use href="#i-chevron-right" />
            </svg>
          </button>
        </div>
        <p className="lp-kendali__periode" aria-live="polite">
          {labelPeriode(periode.dari, periode.sampai)}
        </p>
      </div>
      <p className="micro muted">
        Data tersedia {labelTanggal(tersedia.awal)} sampai {labelTanggal(tersedia.akhir)}.
      </p>
    </div>
  )
}

/* ---------- Isi laporan ---------- */

function Isi({ laporan, ubah }: { laporan: Laporan; ubah: (p: Periode) => void }) {
  const r = laporan.ringkasan
  const harian = laporan.periode === "harian"
  if (laporan.disamarkan || !r) {
    return (
      <Pesan
        judul="Angka tidak ditampilkan."
        isi={
          harian
            ? `Tanggal ini berisi kurang dari ${laporan.minimum} transaksi, atau angkanya dapat dipakai menghitung tanggal lain yang berisi kurang dari ${laporan.minimum} transaksi. Angkanya disamarkan untuk melindungi data wajib pajak.`
            : `Bulan ini berisi kurang dari ${laporan.minimum} transaksi. Angkanya disamarkan untuk melindungi data wajib pajak.`
        }
      />
    )
  }
  if (r.transaksi === 0) {
    return <Pesan judul="Tidak ada transaksi." isi="Belum ada penerimaan yang diterbitkan untuk periode ini." />
  }

  const bulan = laporan.dari!.slice(0, 7)
  return (
    <>
      <Ringkasan laporan={laporan} ringkasan={r} />
      {harian ? (
        <div className="card card--pad lp-lanjut">
          <p className="muted">Rincian per hari, loket, jenis daftar, jenis kendaraan, kecamatan, dan kelurahan ada di laporan bulanan.</p>
          <button type="button" className="btn btn--ghost btn--sm" onClick={() => ubah(periodeBulan(bulan))}>
            Lihat {labelPeriode(`${bulan}-01`, periodeBulan(bulan).sampai)}
          </button>
        </div>
      ) : (
        <>
          <PerHari hari={laporan.rincian.tanggal ?? []} bukaHari={(h) => ubah({ mode: "harian", dari: h, sampai: h })} />
          <div className="lp-kelompok-grid">
            {(["loket", "jenisDaftar", "jenisKendaraan", "kecamatan"] as Dimensi[]).map((d) => (
              <Kelompok key={d} dimensi={d} baris={laporan.rincian[d] ?? []} total={r.jumlah} />
            ))}
          </div>
          <Rincian laporan={laporan} ringkasan={r} />
        </>
      )}
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
  const baru = laporan.kendaraanBaru
  const namaPembanding = laporan.periode === "harian" ? "Hari layanan sebelumnya" : "Periode sebelumnya"

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
              ? `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): disamarkan untuk melindungi data wajib pajak.`
              : p.ringkasan.transaksi === 0
                ? `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): belum ada data.`
                : `${namaPembanding} (${labelPeriode(p.dari, p.sampai)}): ${rupiah(p.ringkasan.jumlah)}.`}
        </p>
      </div>
      <div className="lp-ringkas__petak">
        <Petak label="Transaksi" nilai={angka(r.transaksi)} tambahan={<Perubahan kini={r.transaksi} lalu={lalu?.transaksi} />} />
        <Petak
          label="Kendaraan baru"
          nilai={baru ? angka(baru.transaksi) : "—"}
          tambahan={baru ? persen(baru.jumlah, r.jumlah) : undefined}
          catatan={baru ? rupiah(baru.jumlah) : "Disamarkan"}
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
          {adaSamar && " Angka hari bergaris disamarkan untuk melindungi data wajib pajak."}
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

function Kelompok({ dimensi, baris, total }: { dimensi: Dimensi; baris: Baris[]; total: number }) {
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
        {[...tampil, ...(samar ? [samar] : [])].map((b) => (
          <li key={`${b.nilai}|${b.kecamatan ?? ""}`} className={b.disamarkan ? "is-samar" : undefined}>
            <div className="lp-kelompok__baris">
              <span className="lp-kelompok__nama">{b.nama ?? b.nilai}</span>
              <span className="lp-kelompok__nilai">{rupiahRingkas(b.jumlah ?? 0)}</span>
            </div>
            <div className="lp-kelompok__jalur" aria-hidden="true">
              <span style={{ width: `${((b.jumlah ?? 0) / maks) * 100}%` }} />
            </div>
            <p className="lp-kelompok__meta">
              {angka(b.transaksi ?? 0)} transaksi{b.disamarkan ? ` dari ${angka(b.kelompok ?? 0)} kelompok kecil` : ""} · {persen(b.jumlah ?? 0, total)}
            </p>
          </li>
        ))}
      </ul>
      {lainnya > 0 && <p className="micro muted">Dan {angka(lainnya)} lainnya di tabel rincian.</p>}
    </section>
  )
}

/* ---------- Tabel rincian ---------- */

type Tab = Dimensi | "tanggal"
const TAB: Tab[] = ["jenisDaftar", "jenisKendaraan", "loket", "kecamatan", "kelurahan", "tanggal"]

function Rincian({ laporan, ringkasan: r }: { laporan: Laporan; ringkasan: Jumlah }) {
  const [aktif, setAktif] = useState<Tab>("jenisDaftar")
  const baris = laporan.rincian[aktif] ?? []
  const judul = aktif === "tanggal" ? "Tanggal" : LABEL[aktif]
  const kelurahan = aktif === "kelurahan"
  const tampil = (b: Baris) => (aktif === "tanggal" ? labelTanggal(b.nilai) : (b.nama ?? b.nilai))

  const csv = useMemo(() => {
    const kepala = [judul, ...(kelurahan ? ["Kecamatan"] : []), "Transaksi", "Jumlah"]
    const isi = baris.map((b) => [
      aktif === "tanggal" ? b.nilai : (b.nama ?? b.nilai),
      ...(kelurahan ? [b.kecamatan ?? ""] : []),
      ...(b.disamarkan && aktif === "tanggal" ? ["disamarkan", "disamarkan"] : [b.transaksi ?? 0, b.jumlah ?? 0]),
    ])
    const jumlah = ["Jumlah", ...(kelurahan ? [""] : []), r.transaksi, r.jumlah]
    return teksCsv([kepala, ...isi, jumlah])
  }, [aktif, baris, judul, kelurahan, r])

  const unduh = () => unduhTeks(csv, `penerimaan-per-${judul.toLowerCase().replace(/\s+/g, "-")}-${laporan.dari!.slice(0, 7)}.csv`)

  return (
    <section className="card card--pad lp-rincian" aria-labelledby="lp-rincian-judul">
      <div className="lp-bagian__kepala lp-rincian__kepala">
        <div>
          <h2 id="lp-rincian-judul" className="lp-bagian__judul">
            Rincian penerimaan
          </h2>
          <p className="micro muted">Transaksi dan rupiah setiap kelompok. Susunan komponen hanya untuk seluruh bulan, agar angka seorang wajib pajak tidak terbaca.</p>
        </div>
        <button type="button" className="btn btn--ghost btn--sm" onClick={unduh}>
          <svg className="i i--sm" aria-hidden="true">
            <use href="#i-download" />
          </svg>
          Unduh CSV
        </button>
      </div>
      <div className="chips lp-rincian__tab" role="group" aria-label="Rincian per">
        {TAB.map((t) => (
          <button key={t} type="button" className="chip" aria-pressed={aktif === t} onClick={() => setAktif(t)}>
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
              <th scope="col" className="lp-angka">
                Jumlah
              </th>
              <th scope="col" className="lp-angka">
                Porsi
              </th>
            </tr>
          </thead>
          <tbody>
            {baris.map((b) => (
              <tr key={`${b.nilai}|${b.kecamatan ?? ""}`} className={b.disamarkan ? "is-samar" : undefined}>
                <th scope="row">{tampil(b)}</th>
                {kelurahan && <td>{b.kecamatan ?? "—"}</td>}
                {b.disamarkan && aktif === "tanggal" ? (
                  <td className="lp-angka muted" colSpan={3}>
                    Disamarkan
                  </td>
                ) : (
                  <>
                    <td className="lp-angka">{angka(b.transaksi ?? 0)}</td>
                    <td className="lp-angka lp-angka--kuat">{angka(b.jumlah ?? 0)}</td>
                    <td className="lp-angka">{persen(b.jumlah ?? 0, r.jumlah)}</td>
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
              <td className="lp-angka lp-angka--kuat">{angka(r.jumlah)}</td>
              <td className="lp-angka">100%</td>
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
