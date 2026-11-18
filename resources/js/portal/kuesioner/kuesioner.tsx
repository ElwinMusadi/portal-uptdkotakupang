/*
 * Kuesioner PINTU (D-095, D-096) di /kuesioner (pilihan kuesioner beserta kode QR-nya), /kuesioner/operator, dan
 * /kuesioner/wajib-pajak. Pertanyaan dibaca dari pertanyaan.json, yang juga disalin ke Apps Script pembuat
 * Google Formulir (scripts/kuesioner-google-form.gs). Sebelum mulai, pengisi diminta membuka dan mencoba fitur
 * PINTU yang didaftar di pembuka, agar penilaiannya berdasar pengalaman nyata (D-096). Jawaban dikirim langsung
 * ke formResponse Google Formulir (alamat dan nomor entri di google-form.json), sehingga tercatat di Spreadsheet
 * formulir itu. Google tidak memberi jawaban yang bisa dibaca dari situs lain (no-cors), jadi kiriman dianggap
 * berhasil bila permintaannya sampai. Selama google-form.json belum diisi, kuesioner belum dibuka; ?pratinjau=1
 * mencoba tampilan tanpa mengirim apa pun, dan ?loket=1 kembali ke awal sendiri setelah terima kasih (tablet di
 * loket).
 */
import { useCallback, useEffect, useRef, useState } from "react"
import type { MouseEvent as ReactMouseEvent } from "react"
import { createRoot } from "react-dom/client"
import { renderSVG } from "uqr"

import { gulirKe } from "../gulir"
import DATA from "./pertanyaan.json"
import FORM from "./google-form.json"
import "./kuesioner.css"

type JenisKuesioner = "operator" | "wajib-pajak"
type JenisPertanyaan = "skala" | "pilihan" | "pilihan-ganda" | "isian" | "teks" | "angka"

interface Pertanyaan {
  kunci: string
  jenis: JenisPertanyaan
  teks: string
  /** Aspek penilaian sebuah pernyataan berskala, mis. "Kemudahan penggunaan" */
  aspek?: string
  /** Pertanyaan selain skala yang tetap harus dijawab (mis. fitur yang sudah dicoba, data diri wajib pajak) */
  wajib?: boolean
  petunjuk?: string
  pilihan?: string[]
  /** Batas dan satuan jawaban angka, mis. umur 17–100 tahun (D-097) */
  min?: number
  maks?: number
  satuan?: string
}

interface FiturDicoba {
  nama: string
  alamat: string
  keterangan: string
}

interface Kuesioner {
  judul: string
  untuk: string
  ringkas: string
  pembuka: string
  /** Fitur yang perlu dibuka dan dicoba lebih dulu, serta pernyataan yang dicentang sebelum mulai */
  sebelum: { teks: string; fitur: FiturDicoba[]; konfirmasi: string }
  pertanyaan: Pertanyaan[]
}

interface Tujuan {
  aksi: string
  entri: Record<string, string>
}

type Nilai = number | string | string[]
type Jawaban = Record<string, Nilai>
type Status = "isi" | "mengirim" | "terkirim" | "gagal"

const SKALA: string[] = DATA.skala
const KUESIONER = DATA.kuesioner as unknown as Record<JenisKuesioner, Kuesioner>
const URUTAN: JenisKuesioner[] = ["wajib-pajak", "operator"]
const BATAS_ISIAN = 1000
const JEDA_LANJUT = 320
const DETIK_LOKET = 15

const POLA_AKSI = /^https:\/\/docs\.google\.com\/forms\/d\/e\/[\w-]+\/formResponse$/
const POLA_ENTRI = /^entry\.\d+$/

/** Alamat formulir dari google-form.json, hanya bila lengkap: setiap pertanyaan punya nomor entri. */
function tujuanKuesioner(jenis: JenisKuesioner): Tujuan | null {
  const tujuan = (FORM as unknown as Record<string, Tujuan | null>)[jenis]
  if (!tujuan || typeof tujuan.aksi !== "string" || !POLA_AKSI.test(tujuan.aksi)) return null
  const lengkap = KUESIONER[jenis].pertanyaan.every((p) => POLA_ENTRI.test(tujuan.entri?.[p.kunci] ?? ""))
  return lengkap ? tujuan : null
}

/** Pernyataan berskala dan pertanyaan bertanda wajib harus dijawab; profil lain dan isian boleh dilewati. */
const wajib = (p: Pertanyaan) => p.jenis === "skala" || p.wajib === true

function terjawab(nilai: Nilai | undefined): boolean {
  if (nilai === undefined) return false
  if (Array.isArray(nilai)) return nilai.length > 0
  return String(nilai).trim() !== ""
}

const BATAS_TEKS = 100
const POLA_ANGKA = /^\d{1,3}$/

/** Jawaban yang sudah diisi juga harus masuk akal: nama paling sedikit 2 huruf, angka bulat di dalam batasnya. */
function sah(p: Pertanyaan, nilai: Nilai | undefined): boolean {
  if (!terjawab(nilai)) return false
  const teks = String(nilai).trim()
  if (p.jenis === "teks") return teks.length >= 2 && teks.length <= BATAS_TEKS
  if (p.jenis === "angka") return POLA_ANGKA.test(teks) && Number(teks) >= (p.min ?? 0) && Number(teks) <= (p.maks ?? 999)
  return true
}

/** Boleh lanjut dari sebuah pertanyaan: jawabannya sah, atau kosong pada pertanyaan yang boleh dilewati. */
const selesaiDijawab = (p: Pertanyaan, nilai: Nilai | undefined) => (terjawab(nilai) ? sah(p, nilai) : !wajib(p))

function pesanWajib(p: Pertanyaan): string {
  switch (p.jenis) {
    case "skala":
      return "Pernyataan ini perlu dijawab sebelum lanjut."
    case "pilihan":
      return "Pilih salah satu sebelum lanjut."
    case "pilihan-ganda":
      return "Pilih paling sedikit satu sebelum lanjut."
    case "teks":
      return "Isi paling sedikit 2 huruf sebelum lanjut."
    case "angka":
      return `Isi angka ${p.min ?? 0}–${p.maks ?? 999}${p.satuan ? ` ${p.satuan}` : ""} sebelum lanjut.`
    default:
      return "Pertanyaan ini perlu dijawab sebelum lanjut."
  }
}

/** Kuesioner yang menanyakan nama tidak disebut "tanpa nama" (D-097) */
const bernama = (k: Kuesioner) => k.pertanyaan.some((p) => p.kunci === "nama")

/** Perkiraan waktu mengisi: pernyataan berskala ±10 detik, pilihan ±20 detik, isian ±45 detik */
const DETIK_PER_JENIS: Record<JenisPertanyaan, number> = { skala: 10, pilihan: 20, "pilihan-ganda": 20, isian: 45, teks: 20, angka: 10 }
const menit = (k: Kuesioner) => Math.max(1, Math.round(k.pertanyaan.reduce((n, p) => n + DETIK_PER_JENIS[p.jenis], 0) / 60))
const jumlahPernyataan = (k: Kuesioner) => k.pertanyaan.filter((p) => p.jenis === "skala").length

function teksJawaban(p: Pertanyaan, nilai: Nilai | undefined): string {
  if (!terjawab(nilai)) return "Tidak diisi"
  if (p.jenis === "skala") return `${nilai} · ${SKALA[Number(nilai) - 1]}`
  if (p.jenis === "angka") return `${Number(String(nilai).trim())}${p.satuan ? ` ${p.satuan}` : ""}`
  return Array.isArray(nilai) ? nilai.join(", ") : String(nilai).trim()
}

/** Isi badan formResponse: satu pasangan per jawaban, pilihan ganda berulang dengan entri yang sama. */
export function badanKiriman(k: Kuesioner, tujuan: Tujuan, jawaban: Jawaban): URLSearchParams {
  const badan = new URLSearchParams()
  for (const p of k.pertanyaan) {
    const nilai = jawaban[p.kunci]
    if (!terjawab(nilai)) continue
    const entri = tujuan.entri[p.kunci]
    if (Array.isArray(nilai)) nilai.forEach((v) => badan.append(entri, v))
    else if (p.jenis === "angka") badan.append(entri, String(Number(String(nilai).trim())))
    else badan.append(entri, String(nilai).trim())
  }
  return badan
}

async function kirimKeGoogle(k: Kuesioner, tujuan: Tujuan, jawaban: Jawaban): Promise<void> {
  const kendali = new AbortController()
  const batas = window.setTimeout(() => kendali.abort(), 20000)
  try {
    await fetch(tujuan.aksi, { method: "POST", mode: "no-cors", body: badanKiriman(k, tujuan, jawaban), signal: kendali.signal })
  } finally {
    window.clearTimeout(batas)
  }
}

const MULUT = [
  "M8 17.2c1.3-2.5 6.7-2.5 8 0",
  "M8.6 16.4c1.6-1.3 5.2-1.3 6.8 0",
  "M8.6 15.6h6.8",
  "M8.6 14.6c1.6 1.4 5.2 1.4 6.8 0",
  "M8 14c1.3 3 6.7 3 8 0",
]

function Wajah({ nilai }: { nilai: number }) {
  return (
    <svg className="kq-wajah" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <circle cx="12" cy="12" r="9.5" />
      <circle className="kq-wajah__mata" cx="9" cy="10" r="1" />
      <circle className="kq-wajah__mata" cx="15" cy="10" r="1" />
      <path d={MULUT[nilai - 1]} />
    </svg>
  )
}

// ---------------------------------------------------------------- pilihan kuesioner (/kuesioner)

function KartuPilihan({ jenis }: { jenis: JenisKuesioner }) {
  const k = KUESIONER[jenis]
  const alamat = `${window.location.origin}/kuesioner/${jenis}`
  const terbuka = tujuanKuesioner(jenis) !== null
  const [disalin, setDisalin] = useState(false)

  const salin = () => {
    void navigator.clipboard
      ?.writeText(alamat)
      .then(() => {
        setDisalin(true)
        window.setTimeout(() => setDisalin(false), 2500)
      })
      .catch(() => setDisalin(false))
  }

  return (
    <article className="card card--xl kq-kartu">
      <div className="kq-kartu__teks">
        <p className="eyebrow">{k.untuk}</p>
        <h2 className="h3">{k.judul}</h2>
        <p className="body">{k.ringkas}</p>
        <p className="kq-kartu__meta">
          {jumlahPernyataan(k)} pernyataan penilaian · ± {menit(k)} menit{bernama(k) ? "" : " · tanpa nama"}
          {!terbuka && <span className="kq-tanda">Belum dibuka</span>}
        </p>
        <div className="kq-kartu__aksi">
          <a className="btn btn--dark btn--sm" href={`/kuesioner/${jenis}`}>
            Isi kuesioner
          </a>
          <button type="button" className="btn btn--ghost btn--sm" onClick={salin}>
            {disalin ? "Tautan disalin" : "Salin tautan"}
          </button>
        </div>
      </div>
      <figure className="kq-kartu__qr">
        <div className="kq-qr" role="img" aria-label={`Kode QR menuju ${k.judul}`} dangerouslySetInnerHTML={{ __html: renderSVG(alamat, { ecc: "M", border: 1 }) }} />
        <figcaption className="micro muted">Pindai dengan kamera telepon</figcaption>
      </figure>
    </article>
  )
}

function PilihKuesioner() {
  return (
    <div className="kq-pilih">
      <aside className="kq-pengantar" aria-label="Sebelum mengisi">
        <svg className="kq-pengantar__ikon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <circle cx="12" cy="12" r="9.5" />
          <path d="M12 11v5.5M12 7.6v.1" />
        </svg>
        <p>
          <strong>Sebelum mengisi, coba dulu PINTU.</strong> Wajib pajak membuka fitur portal, misalnya persyaratan
          layanan dan Cek Biaya Pajak; petugas operator masuk ke dashboard dan mencoba menu tugasnya. Dengan begitu
          penilaian Anda sesuai pengalaman nyata. Daftar fitur yang perlu dicoba ada di halaman pembuka setiap kuesioner.
        </p>
      </aside>
      {URUTAN.map((jenis) => (
        <KartuPilihan key={jenis} jenis={jenis} />
      ))}
    </div>
  )
}

// ---------------------------------------------------------------- mengisi kuesioner (/kuesioner/<jenis>)

function IsiKuesioner({ jenis }: { jenis: JenisKuesioner }) {
  const k = KUESIONER[jenis]
  const params = new URLSearchParams(window.location.search)
  const pratinjau = params.get("pratinjau") === "1"
  const loket = params.get("loket") === "1"
  const tujuan = tujuanKuesioner(jenis)
  const terbuka = tujuan !== null || pratinjau
  const jumlah = k.pertanyaan.length

  // 0 = pembuka, 1..jumlah = pertanyaan, jumlah + 1 = periksa jawaban
  const [langkah, setLangkah] = useState(0)
  const [mundur, setMundur] = useState(false)
  const [jawaban, setJawaban] = useState<Jawaban>({})
  const [status, setStatus] = useState<Status>("isi")
  const [dariPeriksa, setDariPeriksa] = useState(false)
  const [sudahMencoba, setSudahMencoba] = useState(false)
  const [hitungMundur, setHitungMundur] = useState(DETIK_LOKET)
  const wadah = useRef<HTMLDivElement>(null)
  const judul = useRef<HTMLHeadingElement>(null)
  const masukan = useRef<HTMLInputElement>(null)
  const pewaktu = useRef<number | undefined>(undefined)
  const sudahMulai = useRef(false)

  const p = langkah >= 1 && langkah <= jumlah ? k.pertanyaan[langkah - 1] : null
  const bolehLanjut = p === null || selesaiDijawab(p, jawaban[p.kunci])
  const terisi = k.pertanyaan.filter((q) => sah(q, jawaban[q.kunci])).length

  const pindah = useCallback(
    (ke: number) => {
      window.clearTimeout(pewaktu.current)
      setMundur(ke < langkah)
      setLangkah(ke)
    },
    [langkah],
  )

  const lanjut = useCallback(() => {
    if (!bolehLanjut) return
    if (dariPeriksa && langkah >= 1 && langkah <= jumlah) {
      setDariPeriksa(false)
      pindah(jumlah + 1)
      return
    }
    pindah(Math.min(langkah + 1, jumlah + 1))
  }, [bolehLanjut, dariPeriksa, langkah, jumlah, pindah])

  const kembali = () => pindah(Math.max(0, langkah - 1))

  const ulangi = useCallback(() => {
    window.clearTimeout(pewaktu.current)
    setJawaban({})
    setStatus("isi")
    setDariPeriksa(false)
    setSudahMencoba(false)
    setMundur(true)
    setLangkah(0)
  }, [])

  const isi = (kunci: string, nilai: Nilai) => setJawaban((j) => ({ ...j, [kunci]: nilai }))

  /** Skala dan pilihan tunggal berlanjut sendiri bila dipilih dengan klik atau angka, tidak dengan tombol panah. */
  const pilihLaluLanjut = (kunci: string, nilai: Nilai) => {
    isi(kunci, nilai)
    window.clearTimeout(pewaktu.current)
    pewaktu.current = window.setTimeout(() => {
      setMundur(false)
      setLangkah((l) => (dariPeriksa ? jumlah + 1 : Math.min(l + 1, jumlah + 1)))
      setDariPeriksa(false)
    }, JEDA_LANJUT)
  }

  const kirim = async () => {
    setStatus("mengirim")
    try {
      if (tujuan && !pratinjau) await kirimKeGoogle(k, tujuan, jawaban)
      else await new Promise((r) => window.setTimeout(r, 700))
      setStatus("terkirim")
    } catch {
      setStatus("gagal")
    }
  }

  // Judul langkah baru menerima fokus (pembaca layar), dan kartu digulir agar terlihat di telepon
  useEffect(() => {
    if (!sudahMulai.current) {
      sudahMulai.current = true
      return
    }
    // Pada pertanyaan isian singkat (nama, umur) kotak isiannya yang menerima fokus, agar bisa langsung diketik
    const sasaran = masukan.current ?? judul.current
    sasaran?.focus({ preventScroll: true })
    if (wadah.current && wadah.current.getBoundingClientRect().top < 72) gulirKe(wadah.current, "start")
  }, [langkah, status])

  useEffect(() => () => window.clearTimeout(pewaktu.current), [])

  // Angka 1–5 memilih skala, Enter melanjutkan (kecuali di kotak isian)
  useEffect(() => {
    if (status !== "isi" || !terbuka) return
    const tekan = (e: KeyboardEvent) => {
      if (e.altKey || e.ctrlKey || e.metaKey) return
      const diIsian = (e.target as HTMLElement | null)?.tagName === "TEXTAREA"
      if (p?.jenis === "skala" && /^[1-5]$/.test(e.key) && !diIsian) {
        e.preventDefault()
        pilihLaluLanjut(p.kunci, Number(e.key))
      } else if (e.key === "Enter" && !diIsian && (e.target as HTMLElement | null)?.tagName !== "BUTTON" && (e.target as HTMLElement | null)?.tagName !== "A") {
        if (langkah >= 1 && langkah <= jumlah) {
          e.preventDefault()
          lanjut()
        }
      }
    }
    document.addEventListener("keydown", tekan)
    return () => document.removeEventListener("keydown", tekan)
  })

  // Tablet di loket: kembali ke awal sendiri untuk pengisi berikutnya
  useEffect(() => {
    if (!loket || status !== "terkirim") return
    setHitungMundur(DETIK_LOKET)
    const detak = window.setInterval(() => setHitungMundur((d) => d - 1), 1000)
    return () => window.clearInterval(detak)
  }, [loket, status])
  useEffect(() => {
    if (loket && status === "terkirim" && hitungMundur <= 0) ulangi()
  }, [loket, status, hitungMundur, ulangi])

  const kelasLangkah = `kq-langkah${mundur ? " kq-langkah--mundur" : ""}`
  const pita = pratinjau ? (
    <p className="kq-pita" role="note">
      Mode pratinjau: jawaban tidak dikirim ke Google Formulir.
    </p>
  ) : null

  if (status === "terkirim" || status === "mengirim" || status === "gagal") {
    const nilaiSkala = k.pertanyaan.filter((q) => q.jenis === "skala").map((q) => Number(jawaban[q.kunci])).filter((n) => n >= 1)
    const rerata = nilaiSkala.length ? nilaiSkala.reduce((a, b) => a + b, 0) / nilaiSkala.length : 0
    return (
      <div className="card card--xl kq" ref={wadah}>
        {pita}
        {status === "mengirim" && (
          <div className="kq-langkah kq-selesai" role="status">
            <span className="kq-putar" aria-hidden="true" />
            <h2 className="h3" ref={judul} tabIndex={-1}>
              Mengirim jawaban…
            </h2>
          </div>
        )}
        {status === "gagal" && (
          <div className="kq-langkah kq-selesai" role="alert">
            <h2 className="h3" ref={judul} tabIndex={-1}>
              Jawaban belum terkirim.
            </h2>
            <p className="body">Periksa koneksi internet Anda, lalu coba lagi. Jawaban Anda masih tersimpan di halaman ini.</p>
            <div className="kq-aksi kq-aksi--tengah">
              <button type="button" className="btn btn--ghost" onClick={() => setStatus("isi")}>
                Periksa jawaban
              </button>
              <button type="button" className="btn btn--dark" onClick={() => void kirim()}>
                Coba kirim lagi
              </button>
            </div>
          </div>
        )}
        {status === "terkirim" && (
          <div className="kq-langkah kq-selesai" role="status">
            <svg className="kq-centang" viewBox="0 0 52 52" aria-hidden="true" focusable="false">
              <circle cx="26" cy="26" r="24" />
              <path d="m15 27 7.5 7.5L37.5 19" />
            </svg>
            <h2 className="h3" ref={judul} tabIndex={-1}>
              Terima kasih atas waktu Anda.
            </h2>
            <p className="body">
              {pratinjau ? "Ini pratinjau, jadi jawaban tidak dikirim." : "Jawaban Anda sudah terkirim dan akan dipakai untuk memperbaiki PINTU."}
            </p>
            {rerata > 0 && (
              <p className="kq-rerata" data-nilai={Math.round(rerata)}>
                <Wajah nilai={Math.round(rerata)} />
                <span>
                  Rata-rata penilaian Anda <strong>{rerata.toLocaleString("id-ID", { maximumFractionDigits: 1 })}</strong> dari 5
                </span>
              </p>
            )}
            <div className="kq-aksi kq-aksi--tengah">
              <a className="btn btn--ghost" href="/">
                Ke Beranda PINTU
              </a>
              <button type="button" className="btn btn--dark" onClick={ulangi}>
                Isi lagi
              </button>
            </div>
            {loket && <p className="micro muted">Kembali ke awal dalam {Math.max(0, hitungMundur)} detik.</p>}
          </div>
        )}
      </div>
    )
  }

  return (
    <div className="card card--xl kq" ref={wadah}>
      {pita}
      {langkah > 0 && (
        <div className="kq-kepala">
          <p className="kq-kepala__urutan" aria-live="polite">
            {langkah <= jumlah ? `Pertanyaan ${langkah} dari ${jumlah}` : "Periksa jawaban"}
          </p>
          <div className="kq-progres" role="progressbar" aria-label="Jawaban terisi" aria-valuemin={0} aria-valuemax={jumlah} aria-valuenow={terisi}>
            <span style={{ width: `${(terisi / jumlah) * 100}%` }} />
          </div>
        </div>
      )}

      {langkah === 0 && (
        <div className={kelasLangkah} key="pembuka">
          <p className="eyebrow">{k.untuk}</p>
          <h2 className="h3" ref={judul} tabIndex={-1}>
            {k.judul}
          </h2>
          <p className="body">{k.pembuka}</p>
          <ul className="kq-info" role="list">
            <li>{jumlah} pertanyaan</li>
            <li>{jumlahPernyataan(k)} pernyataan penilaian</li>
            <li>± {menit(k)} menit</li>
            {!bernama(k) && <li>Tanpa nama</li>}
          </ul>

          <section className="kq-sebelum" aria-labelledby="kq-sebelum-judul">
            <h3 className="kq-sebelum__judul" id="kq-sebelum-judul">
              Sebelum mengisi, coba dulu PINTU
            </h3>
            <p className="kq-sebelum__teks">{k.sebelum.teks}</p>
            <ol className="kq-fitur" role="list">
              {k.sebelum.fitur.map((f, i) => (
                <li key={f.nama}>
                  <span className="kq-fitur__no" aria-hidden="true">
                    {i + 1}
                  </span>
                  <div>
                    <a className="kq-fitur__nama" href={f.alamat} target="_blank" rel="noopener">
                      {f.nama}
                      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M14 5h5v5M19 5l-8 8M17 14v5H5V7h5" />
                      </svg>
                      <span className="sr-only"> (terbuka di tab baru)</span>
                    </a>
                    <p className="kq-fitur__ket">{f.keterangan}</p>
                  </div>
                </li>
              ))}
            </ol>
            {terbuka && (
              <label className="kq-konfirmasi">
                <input type="checkbox" checked={sudahMencoba} onChange={(e) => setSudahMencoba(e.target.checked)} />
                <span>{k.sebelum.konfirmasi}</span>
              </label>
            )}
          </section>

          {terbuka ? (
            <div className="kq-aksi kq-aksi--mulai">
              <button type="button" className="btn btn--dark" onClick={() => pindah(1)} disabled={!sudahMencoba} aria-describedby={sudahMencoba ? undefined : "kq-mulai-syarat"}>
                Mulai mengisi
              </button>
              {!sudahMencoba && (
                <p className="micro muted" id="kq-mulai-syarat">
                  Centang pernyataan di atas setelah Anda mencoba PINTU.
                </p>
              )}
            </div>
          ) : (
            <div className="kq-tutup" role="status">
              <p>
                <strong>Kuesioner belum dibuka.</strong> Silakan kembali lagi nanti.
              </p>
              <a className="btn btn--ghost btn--sm" href="?pratinjau=1">
                Lihat pratinjau
              </a>
            </div>
          )}
          <p className="micro muted kq-catatan">
            Jawaban disimpan di Google Formulir milik UPTD Pendapatan Daerah Wilayah Kota Kupang dan hanya dipakai untuk menilai serta memperbaiki PINTU.
            {bernama(k) && " Nama dan data diri Anda tidak ditampilkan kepada publik."}
          </p>
        </div>
      )}

      {p && (
        <div className={kelasLangkah} key={p.kunci}>
          <div className="kq-soal" role="group" aria-labelledby={`kq-soal-${p.kunci}`}>
            {p.aspek && <p className="kq-soal__aspek">Aspek {p.aspek}</p>}
            <h2 className="kq-soal__teks" id={`kq-soal-${p.kunci}`} ref={judul} tabIndex={-1}>
              {p.teks}
            </h2>
            {p.petunjuk && <p className="kq-soal__petunjuk">{p.petunjuk}</p>}
            {p.jenis === "skala" && !p.petunjuk && (
              <p className="kq-soal__petunjuk">
                Pilih satu<span className="kq-papan-ketik">, atau tekan angka 1–5</span>.
              </p>
            )}

            {p.jenis === "skala" && (
              <div className="kq-skala">
                {SKALA.map((label, i) => {
                  const nilai = i + 1
                  return (
                    <label key={nilai} className="kq-skala__opsi" data-nilai={nilai}>
                      <input
                        type="radio"
                        name={p.kunci}
                        value={nilai}
                        checked={jawaban[p.kunci] === nilai}
                        onChange={() => isi(p.kunci, nilai)}
                        onClick={(e: ReactMouseEvent<HTMLInputElement>) => {
                          if (e.detail > 0) pilihLaluLanjut(p.kunci, nilai)
                        }}
                      />
                      <Wajah nilai={nilai} />
                      <span className="kq-skala__angka">{nilai}</span>
                      <span className="kq-skala__label">{label}</span>
                    </label>
                  )
                })}
              </div>
            )}

            {p.jenis === "pilihan" && (
              <div className="kq-pilihan">
                {p.pilihan?.map((opsi) => (
                  <label key={opsi} className="kq-pilihan__opsi">
                    <input
                      type="radio"
                      name={p.kunci}
                      value={opsi}
                      checked={jawaban[p.kunci] === opsi}
                      onChange={() => isi(p.kunci, opsi)}
                      onClick={(e: ReactMouseEvent<HTMLInputElement>) => {
                        if (e.detail > 0) pilihLaluLanjut(p.kunci, opsi)
                      }}
                    />
                    <span>{opsi}</span>
                  </label>
                ))}
              </div>
            )}

            {p.jenis === "pilihan-ganda" && (
              <div className="kq-pilihan">
                {p.pilihan?.map((opsi) => {
                  const dipilih = Array.isArray(jawaban[p.kunci]) ? (jawaban[p.kunci] as string[]) : []
                  return (
                    <label key={opsi} className="kq-pilihan__opsi kq-pilihan__opsi--ganda">
                      <input
                        type="checkbox"
                        value={opsi}
                        checked={dipilih.includes(opsi)}
                        onChange={(e) => {
                          const baru = e.target.checked ? [...dipilih, opsi] : dipilih.filter((v) => v !== opsi)
                          // urutan mengikuti daftar pilihan, sama seperti di Google Formulir
                          isi(p.kunci, (p.pilihan ?? []).filter((v) => baru.includes(v)))
                        }}
                      />
                      <span>{opsi}</span>
                    </label>
                  )
                })}
              </div>
            )}

            {(p.jenis === "teks" || p.jenis === "angka") && (
              <div className={`kq-masukan${p.jenis === "angka" ? " kq-masukan--angka" : ""}`}>
                <label className="sr-only" htmlFor={`kq-${p.kunci}`}>
                  {p.teks}
                </label>
                <input
                  ref={masukan}
                  id={`kq-${p.kunci}`}
                  className="input"
                  type="text"
                  inputMode={p.jenis === "angka" ? "numeric" : undefined}
                  autoComplete={p.kunci === "nama" ? "name" : "off"}
                  maxLength={p.jenis === "angka" ? 3 : BATAS_TEKS}
                  value={typeof jawaban[p.kunci] === "string" ? (jawaban[p.kunci] as string) : ""}
                  onChange={(e) => isi(p.kunci, p.jenis === "angka" ? e.target.value.replace(/\D/g, "").slice(0, 3) : e.target.value)}
                  aria-invalid={terjawab(jawaban[p.kunci]) && !sah(p, jawaban[p.kunci]) ? true : undefined}
                  aria-describedby={bolehLanjut ? undefined : `kq-wajib-${p.kunci}`}
                />
                {p.satuan && <span className="kq-satuan">{p.satuan}</span>}
              </div>
            )}

            {p.jenis === "isian" && (
              <div className="field">
                <label className="sr-only" htmlFor={`kq-${p.kunci}`}>
                  {p.teks}
                </label>
                <textarea
                  id={`kq-${p.kunci}`}
                  className="textarea"
                  maxLength={BATAS_ISIAN}
                  rows={5}
                  value={typeof jawaban[p.kunci] === "string" ? (jawaban[p.kunci] as string) : ""}
                  onChange={(e) => isi(p.kunci, e.target.value)}
                />
                <p className="micro muted kq-hitung">
                  {String(jawaban[p.kunci] ?? "").length}/{BATAS_ISIAN} karakter
                </p>
              </div>
            )}
          </div>

          <div className="kq-aksi">
            <button type="button" className="btn btn--ghost" onClick={kembali}>
              Kembali
            </button>
            <button type="button" className="btn btn--dark" onClick={lanjut} disabled={!bolehLanjut}>
              {dariPeriksa ? "Simpan" : !terjawab(jawaban[p.kunci]) && !wajib(p) ? "Lewati" : "Lanjut"}
            </button>
          </div>
          {!bolehLanjut && (
            <p className="micro muted kq-wajib" id={`kq-wajib-${p.kunci}`}>
              {pesanWajib(p)}
            </p>
          )}
        </div>
      )}

      {langkah === jumlah + 1 && (
        <div className={kelasLangkah} key="periksa">
          <h2 className="h3" ref={judul} tabIndex={-1}>
            Periksa jawaban Anda
          </h2>
          <ol className="kq-periksa" role="list">
            {k.pertanyaan.map((q, i) => (
              <li key={q.kunci}>
                <span className="kq-periksa__no">{i + 1}</span>
                <div>
                  <p className="kq-periksa__soal">{q.teks}</p>
                  <p className={`kq-periksa__jawab${terjawab(jawaban[q.kunci]) ? "" : " is-kosong"}`}>{teksJawaban(q, jawaban[q.kunci])}</p>
                </div>
                <button
                  type="button"
                  className="btn btn--ghost btn--xs"
                  onClick={() => {
                    setDariPeriksa(true)
                    pindah(i + 1)
                  }}
                  aria-label={`Ubah jawaban pertanyaan ${i + 1}`}
                >
                  Ubah
                </button>
              </li>
            ))}
          </ol>
          <div className="kq-aksi">
            <button type="button" className="btn btn--ghost" onClick={kembali}>
              Kembali
            </button>
            <button type="button" className="btn btn--dark" onClick={() => void kirim()} disabled={!k.pertanyaan.every((q) => selesaiDijawab(q, jawaban[q.kunci]))}>
              Kirim jawaban
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

// ---------------------------------------------------------------- pemasangan

/** Jenis kuesioner dari alamat /kuesioner/<jenis>, atau null di /kuesioner */
function jenisDariAlamat(): JenisKuesioner | null {
  const bagian = window.location.pathname.split("/").filter(Boolean)
  const jenis = bagian[1] as JenisKuesioner | undefined
  return jenis && jenis in KUESIONER ? jenis : null
}

export function pasang(el: HTMLElement) {
  const jenis = jenisDariAlamat()
  if (jenis) document.title = `${KUESIONER[jenis].judul} — PINTU UPTD Pendapatan Daerah Wilayah Kota Kupang`
  createRoot(el).render(jenis ? <IsiKuesioner jenis={jenis} /> : <PilihKuesioner />)
}
