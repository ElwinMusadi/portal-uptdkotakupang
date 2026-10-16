/*
 * Pemetaan PKB di portal (D-052 Irisan 2): jumlah kendaraan, kepatuhan, dan
 * potensi Pajak Kendaraan Bermotor per kecamatan dan kelurahan Kota Kupang,
 * dari GET /api/public/pemetaan. Datanya hanya tampil setelah Administrator
 * menyalakannya, untuk seluruh data tanpa saringan, tanpa identitas wajib
 * pajak; angka yang menggambarkan kurang dari 3 kendaraan datang sebagai null
 * dan ditulis "disamarkan". Peta memakai batas BIG (batas-wilayah.ts):
 * kecamatan, lalu kelurahan kecamatan yang dipilih.
 */
import { useEffect, useState, type CSSProperties } from "react"
import { createRoot } from "react-dom/client"

import { labelTanggal } from "../penerimaan/periode"
import { BATAS_KECAMATAN, BATAS_KELURAHAN, UKURAN_PETA } from "./batas-wilayah"
import { kotakTampilan, labelTerpasang, useLebar } from "./peta"
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
  /** `rodaLain`: kendaraan yang jenisnya tidak tercatat di berkas (null bila 1–2) */
  kota?: Omit<AngkaWilayah, "rodaTigaLebih"> & { rodaTiga: Angka; rodaEmpat: Angka; rodaLain?: Angka }
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
  const diPeta = dipilih && BATAS_KECAMATAN[dipilih.kunci] ? dipilih : null

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
            {!!kota.rodaLain && (
              <div>
                <dt>Jenis tidak tercatat</dt>
                <dd>{angka(kota.rodaLain)}</dd>
              </div>
            )}
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
            {diPeta ? `Peta kelurahan di ${diPeta.nama}` : "Peta kecamatan"}
          </h2>
          <p className="micro muted">
            {diPeta
              ? `Warna menunjukkan ${u.label.toLowerCase()} per kelurahan di ${diPeta.nama}. Pilih kecamatan lain di peta, atau kembali ke semua kecamatan.`
              : `Warna menunjukkan ${u.label.toLowerCase()} per kecamatan. Pilih kecamatan untuk melihat kelurahannya.`}
          </p>
        </div>
        <div className="chips" role="group" aria-label="Ukuran pada peta">
          {(Object.keys(UKURAN) as Ukuran[]).map((k) => (
            <button key={k} type="button" className="chip" aria-pressed={ukuran === k} onClick={() => setUkuran(k)}>
              {UKURAN[k].label}
            </button>
          ))}
        </div>
        <PetaWilayah wilayah={wilayah} u={u} pilih={pilih} setPilih={setPilih} />
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

/** Kepekatan warna 0–1 dari nilai terendah sampai tertinggi di antara wilayah yang tampil, agar selisih kecil tetap terlihat */
function skalaWarna(nilai: (number | null)[]) {
  const ada = nilai.filter((v): v is number => v !== null)
  const [rendah, tinggi] = [Math.min(...ada), Math.max(...ada)]
  return (v: number | null) => (v === null ? null : tinggi > rendah ? (v - rendah) / (tinggi - rendah) : 0.5)
}

const isian = (s: number | null) => (s === null ? "var(--paper-2)" : `color-mix(in oklab, var(--danger) ${Math.round(8 + s * 62)}%, var(--surface))`)

/**
 * Peta wilayah: enam kecamatan Kota Kupang, atau kelurahan kecamatan yang
 * dipilih (peta diperbesar ke kecamatan itu, kecamatan lain redup dan bisa
 * dipilih). Tulisan diukur dari lebar peta yang tampil (`--px` = satuan peta
 * per piksel) agar selalu 12/11 px, dan label yang akan saling menimpa
 * dilewati (namanya tetap ada di keterangan wilayah dan tabel).
 */
function PetaWilayah({ wilayah, u, pilih, setPilih }: { wilayah: Kecamatan[]; u: (typeof UKURAN)[Ukuran]; pilih: string | null; setPilih: (kunci: string | null) => void }) {
  const [ref, lebar] = useLebar<SVGSVGElement>()
  const kecamatan = wilayah.filter((k) => BATAS_KECAMATAN[k.kunci])
  const dipilih = kecamatan.find((k) => k.kunci === pilih) ?? null
  const tampil = kotakTampilan(dipilih ? BATAS_KECAMATAN[dipilih.kunci].kotak : undefined)
  const px = tampil.w / (lebar ?? Math.min(UKURAN_PETA.lebar, 480))
  const warnaKecamatan = skalaWarna(kecamatan.map((k) => u.nilai(k)))
  const kelurahan = dipilih
    ? Object.entries(BATAS_KELURAHAN)
        .filter(([, b]) => b.kecamatan === dipilih.kunci)
        .map(([nama, b]) => {
          const angka = dipilih.kelurahan.find((l) => l.nama === nama) ?? null
          return { nama, b, angka, teks: angka ? (u.teks(angka) ?? "disamarkan") : "tidak ada kendaraan" }
        })
    : []
  const warnaKelurahan = skalaWarna(kelurahan.map((l) => (l.angka ? u.nilai(l.angka) : null)))
  const berlabel = labelTerpasang(
    kelurahan.map((l) => ({ kunci: l.nama, titik: l.b.label, ruang: l.b.ruang, nama: namaWilayah(l.nama), nilai: l.teks })),
    px,
  )
  const adaSamar = dipilih ? kelurahan.some((l) => !l.angka || u.nilai(l.angka) === null) : kecamatan.some((k) => u.nilai(k) === null)
  const pilihKecamatan = (kunci: string) => setPilih(pilih === kunci ? null : kunci)

  return (
    <>
      {dipilih && (
        <button type="button" className="pm-kembali" onClick={() => setPilih(null)}>
          ← Semua kecamatan
        </button>
      )}
      <svg
        ref={ref}
        viewBox={`${tampil.x} ${tampil.y} ${tampil.w} ${tampil.h}`}
        className="pm-peta__svg"
        style={{ "--px": px } as CSSProperties}
        role="group"
        aria-label={dipilih ? `Peta kelurahan di ${dipilih.nama}` : "Peta kecamatan Kota Kupang"}
      >
        {!dipilih && (
          <text x={UKURAN_PETA.laut[0]} y={UKURAN_PETA.laut[1]} textAnchor="middle" className="pm-peta__laut">
            Teluk Kupang
          </text>
        )}
        {kecamatan
          .filter((k) => k.kunci !== dipilih?.kunci)
          .map((k) => (
            <g
              key={k.kunci}
              role="button"
              tabIndex={0}
              aria-pressed={false}
              aria-label={dipilih ? `Tampilkan kelurahan di ${k.nama}` : `${k.nama}: ${u.teks(k) ?? "disamarkan"}`}
              className={dipilih ? "pm-peta__petak is-redup" : "pm-peta__petak"}
              onClick={() => pilihKecamatan(k.kunci)}
              onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault()
                  pilihKecamatan(k.kunci)
                }
              }}
            >
              <path d={BATAS_KECAMATAN[k.kunci].jalur} style={dipilih ? undefined : { fill: isian(warnaKecamatan(u.nilai(k))) }} />
            </g>
          ))}
        {kelurahan.map((l) => (
          <g key={l.nama} role="img" aria-label={`${namaWilayah(l.nama)}: ${l.teks}`} className="pm-peta__kelurahan">
            <title>{`${namaWilayah(l.nama)}: ${l.teks}`}</title>
            <path d={l.b.jalur} style={{ fill: isian(l.angka ? warnaKelurahan(u.nilai(l.angka)) : null) }} />
          </g>
        ))}
        {dipilih && <path d={BATAS_KECAMATAN[dipilih.kunci].jalur} className="pm-peta__tepi" />}
        {dipilih
          ? kelurahan
              .filter((l) => berlabel.has(l.nama))
              .map((l) => (
                <g key={l.nama} className="pm-peta__label" aria-hidden="true">
                  <text x={l.b.label[0]} y={l.b.label[1]} dy="-0.3em" textAnchor="middle" className="pm-peta__nama">
                    {namaWilayah(l.nama)}
                  </text>
                  <text x={l.b.label[0]} y={l.b.label[1]} dy="1.1em" textAnchor="middle" className="pm-peta__nilai">
                    {l.angka ? (u.teks(l.angka) ?? "disamarkan") : "—"}
                  </text>
                </g>
              ))
          : kecamatan.map((k) => {
              const { label, luar } = BATAS_KECAMATAN[k.kunci]
              const [x, y] = luar ?? label
              return (
                <g key={k.kunci} className="pm-peta__label" aria-hidden="true">
                  {luar && <line x1={luar[0]} y1={luar[1] + 17 * px} x2={label[0]} y2={label[1]} className="pm-peta__garis" />}
                  <text x={x} y={y} dy="-0.3em" textAnchor="middle" className="pm-peta__nama">
                    {k.nama}
                  </text>
                  <text x={x} y={y} dy="1.1em" textAnchor="middle" className="pm-peta__nilai">
                    {u.teks(k) ?? "disamarkan"}
                  </text>
                </g>
              )
            })}
      </svg>
      <p className="micro muted pm-peta__legenda">
        <span>{dipilih ? `${u.label} per kelurahan di ${dipilih.nama}:` : `${u.label} per kecamatan:`}</span>
        <span className="pm-peta__rentang">
          terendah
          <span className="pm-peta__skala" aria-hidden="true" />
          tertinggi
        </span>
        {adaSamar && (
          <span className="pm-peta__rentang">
            <span className="pm-peta__samar" aria-hidden="true" />
            disamarkan atau tanpa kendaraan
          </span>
        )}
      </p>
    </>
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
