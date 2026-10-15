/*
 * Layar ruang tunggu Antrean Online (D-051), /layar. Hanya untuk layar yang
 * dipasang Administrator: tautan pemasangan (/layar#k=<token>) dibuka sekali,
 * tokennya disimpan di perangkat lalu dikirim sebagai bearer token ke
 * GET /api/layar setiap 2 detik. Menampilkan panggilan terbaru (dengan nomor
 * polisi apa adanya, keputusan pemilik), nomor yang dipanggil di setiap
 * loket, dan jumlah nomor yang menunggu. Panggilan baru dibacakan: bunyi
 * tanda lalu "Nomor antrean A, nol nol satu. Silakan ke Loket kwitansi."
 * dengan suara bahasa Indonesia peramban, tanpa nomor polisi. Peramban
 * mewajibkan satu klik sebelum suara boleh diputar.
 */
import { useCallback, useEffect, useRef, useState } from "react"
import { createRoot } from "react-dom/client"

import "./layar-antrean.css"

interface Tiket {
  nomor: string
  tanggal: string
  hariIni: boolean
  nopol: string | null
  layanan: string
}

interface Panggilan extends Tiket {
  id: number
  loket: { nama: string; suara: string } | null
  waktu: string | null
}

interface DataLayar {
  waktu: string
  panggilan: Panggilan[]
  loket: { nama: string; tahap: string; dipanggil: Tiket | null }[]
  menunggu: { tahap: string; nama: string; jumlah: number }[]
}

const KUNCI_TOKEN = "pintu-layar-antrean"
const SELANG_MS = 2000
const SOROT_MS = 12_000

const KATA_ANGKA = ["nol", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan"]

const fmtJam = new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", hourCycle: "h23", timeZone: "Asia/Makassar" })
const fmtJamDetik = new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit", hourCycle: "h23", timeZone: "Asia/Makassar" })
const fmtTanggal = new Intl.DateTimeFormat("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric", timeZone: "Asia/Makassar" })
const fmtTanggalPendek = new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "short", timeZone: "UTC" })

const jam = (iso: string | null) => (iso ? fmtJam.format(new Date(iso)).replace(":", ".") : "")
const tanggalPendek = (t: string) => fmtTanggalPendek.format(new Date(`${t}T00:00:00Z`))

/** Token dari tautan pemasangan (#k=…) disimpan di perangkat; alamatnya dibersihkan */
function ambilToken(): string | null {
  const cocok = window.location.hash.match(/^#k=([A-Za-z0-9]+)$/)
  try {
    if (cocok) {
      window.localStorage.setItem(KUNCI_TOKEN, cocok[1])
      window.history.replaceState(null, "", window.location.pathname)
      return cocok[1]
    }
    return window.localStorage.getItem(KUNCI_TOKEN)
  } catch {
    return cocok?.[1] ?? null
  }
}

function lupakanToken() {
  try {
    window.localStorage.removeItem(KUNCI_TOKEN)
  } catch {
    // penyimpanan tidak tersedia
  }
}

/** "Nomor antrean A, nol nol satu. Silakan ke Loket kwitansi." */
export function ucapan(p: Pick<Panggilan, "nomor" | "loket">): string {
  const [huruf, angka = ""] = p.nomor.split("-")
  const digit = angka
    .split("")
    .map((d) => KATA_ANGKA[Number(d)] ?? d)
    .join(" ")
  return `Nomor antrean ${huruf}, ${digit}. Silakan ke ${p.loket?.suara ?? "loket"}.`
}

/* ---------- Suara ---------- */

let konteksAudio: AudioContext | null = null

/** Bunyi tanda dua nada sebelum pengumuman */
function bunyiTanda(): Promise<void> {
  return new Promise((selesai) => {
    try {
      konteksAudio ??= new AudioContext()
      const ctx = konteksAudio
      const mulai = ctx.currentTime
      ;[784, 523].forEach((frekuensi, i) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.type = "sine"
        osc.frequency.value = frekuensi
        gain.gain.setValueAtTime(0.0001, mulai + i * 0.45)
        gain.gain.exponentialRampToValueAtTime(0.4, mulai + i * 0.45 + 0.03)
        gain.gain.exponentialRampToValueAtTime(0.0001, mulai + i * 0.45 + 0.42)
        osc.connect(gain).connect(ctx.destination)
        osc.start(mulai + i * 0.45)
        osc.stop(mulai + i * 0.45 + 0.45)
      })
      window.setTimeout(selesai, 1000)
    } catch {
      selesai()
    }
  })
}

function bacakan(teks: string): Promise<void> {
  return new Promise((selesai) => {
    if (!("speechSynthesis" in window)) return selesai()
    const u = new SpeechSynthesisUtterance(teks)
    u.lang = "id-ID"
    u.rate = 0.9
    const suaraId = window.speechSynthesis.getVoices().find((v) => v.lang.toLowerCase().startsWith("id"))
    if (suaraId) u.voice = suaraId
    const batas = window.setTimeout(selesai, 15_000)
    u.onend = u.onerror = () => {
      window.clearTimeout(batas)
      selesai()
    }
    window.speechSynthesis.speak(u)
  })
}

/* ---------- Layar ---------- */

function LayarAntrean() {
  const [token, setToken] = useState(ambilToken)
  const [data, setData] = useState<DataLayar | null>(null)
  const [dicabut, setDicabut] = useState(false)
  const [terputus, setTerputus] = useState(false)
  const [suara, setSuara] = useState(false)
  const [sekarang, setSekarang] = useState(() => new Date())
  const [sorot, setSorot] = useState<number | null>(null)
  const terakhir = useRef<number | null>(null)
  const antrian = useRef<Panggilan[]>([])
  const berbicara = useRef(false)
  const suaraNyala = useRef(false)

  const umumkan = useCallback(async () => {
    if (berbicara.current) return
    berbicara.current = true
    while (antrian.current.length > 0) {
      const p = antrian.current.shift()!
      setSorot(p.id)
      if (suaraNyala.current) {
        await bunyiTanda()
        await bacakan(ucapan(p))
      }
    }
    berbicara.current = false
  }, [])

  // Data layar setiap 2 detik; panggilan baru masuk antrean pengumuman
  useEffect(() => {
    if (!token || dicabut) return
    let berhenti = false
    const muat = async () => {
      try {
        const res = await fetch("/api/layar", { headers: { Accept: "application/json", Authorization: `Bearer ${token}` }, cache: "no-store" })
        if (res.status === 401) {
          setDicabut(true)
          return
        }
        if (!res.ok) throw new Error(String(res.status))
        const d = (await res.json()) as DataLayar
        if (berhenti) return
        const baru = d.panggilan.filter((p) => terakhir.current !== null && p.id > terakhir.current).sort((a, b) => a.id - b.id)
        terakhir.current = Math.max(terakhir.current ?? 0, ...d.panggilan.map((p) => p.id))
        setData(d)
        setTerputus(false)
        if (baru.length > 0) {
          antrian.current.push(...baru)
          void umumkan()
        }
      } catch {
        if (!berhenti) setTerputus(true)
      }
    }
    void muat()
    const id = window.setInterval(() => void muat(), SELANG_MS)
    return () => {
      berhenti = true
      window.clearInterval(id)
    }
  }, [token, dicabut, umumkan])

  useEffect(() => {
    const id = window.setInterval(() => setSekarang(new Date()), 1000)
    return () => window.clearInterval(id)
  }, [])

  useEffect(() => {
    if (sorot === null) return
    const id = window.setTimeout(() => setSorot(null), SOROT_MS)
    return () => window.clearTimeout(id)
  }, [sorot])

  const nyalakanSuara = () => {
    try {
      konteksAudio ??= new AudioContext()
      void konteksAudio.resume()
    } catch {
      // tanpa Web Audio, hanya suara pembaca
    }
    if ("speechSynthesis" in window) window.speechSynthesis.getVoices()
    suaraNyala.current = true
    setSuara(true)
  }

  if (!token || dicabut) {
    return (
      <main className="ly ly--pesan">
        <div className="ly-pesan">
          <p className="ly-pesan__judul">{dicabut ? "Layar ini sudah dicabut atau tautannya tidak berlaku." : "Layar ini belum dipasang."}</p>
          <p>Buka tautan pemasangan dari Dashboard › Antrean Online › Pengaturan pada perangkat ini. Tautan dibuat Administrator dan hanya ditampilkan sekali.</p>
          {dicabut && (
            <button
              type="button"
              className="ly-tombol"
              onClick={() => {
                lupakanToken()
                setToken(null)
                setDicabut(false)
              }}
            >
              Lupakan layar ini
            </button>
          )}
        </div>
      </main>
    )
  }

  const [utama, ...sebelumnya] = data?.panggilan ?? []
  return (
    <main className="ly">
      <header className="ly-atas">
        <div>
          <p className="ly-atas__merek">PINTU · Samsat Kota Kupang</p>
          <p className="ly-atas__judul">Antrean Online</p>
        </div>
        <div className="ly-atas__waktu">
          <p className="ly-atas__jam">{fmtJamDetik.format(sekarang).replace(/:/g, ".")}</p>
          <p>{fmtTanggal.format(sekarang)}</p>
        </div>
      </header>

      <div className="ly-isi">
        <section className={utama && sorot === utama.id ? "ly-utama is-baru" : "ly-utama"} aria-live="assertive">
          <p className="ly-label">Nomor dipanggil</p>
          {utama ? (
            <>
              <p className="ly-utama__nomor">
                {utama.nomor}
                {!utama.hariIni && <span className="ly-utama__tgl"> · {tanggalPendek(utama.tanggal)}</span>}
              </p>
              {utama.nopol && <p className="ly-utama__nopol">{utama.nopol}</p>}
              <p className="ly-utama__loket">
                <span aria-hidden="true">→ </span>
                {utama.loket?.nama ?? "Loket"}
              </p>
              <p className="ly-utama__layanan">
                {utama.layanan} · dipanggil {jam(utama.waktu)} WITA
              </p>
            </>
          ) : (
            <p className="ly-kosong">Belum ada panggilan hari ini.</p>
          )}
        </section>

        <aside className="ly-samping">
          <section className="ly-kotak">
            <p className="ly-label">Di loket</p>
            <ul className="ly-loket" role="list">
              {(data?.loket ?? []).map((l) => (
                <li key={l.nama}>
                  <span className="ly-loket__nama">{l.nama}</span>
                  <span className="ly-loket__nomor">{l.dipanggil ? l.dipanggil.nomor : "—"}</span>
                </li>
              ))}
            </ul>
          </section>
          <section className="ly-kotak">
            <p className="ly-label">Menunggu</p>
            <ul className="ly-menunggu" role="list">
              {(data?.menunggu ?? []).map((m) => (
                <li key={m.tahap}>
                  <span>{m.nama}</span>
                  <span className="ly-menunggu__jumlah">{m.jumlah}</span>
                </li>
              ))}
            </ul>
          </section>
        </aside>

        <section className="ly-riwayat">
          <p className="ly-label">Panggilan sebelumnya</p>
          <ol className="ly-riwayat__daftar" role="list">
            {sebelumnya.slice(0, 4).map((p) => (
              <li key={p.id}>
                <span className="ly-riwayat__nomor">
                  {p.nomor}
                  {!p.hariIni && <small> · {tanggalPendek(p.tanggal)}</small>}
                </span>
                <span className="ly-riwayat__nopol">{p.nopol ?? ""}</span>
                <span>{p.loket?.nama ?? ""}</span>
                <span className="ly-riwayat__jam">{jam(p.waktu)}</span>
              </li>
            ))}
            {sebelumnya.length === 0 && <li className="ly-kosong">—</li>}
          </ol>
        </section>
      </div>

      {terputus && <p className="ly-status">Koneksi terputus, mencoba lagi…</p>}
      {!suara && (
        <button type="button" className="ly-tombol ly-tombol--suara" onClick={nyalakanSuara}>
          Aktifkan suara
        </button>
      )}
    </main>
  )
}

/** Ambil alih elemen [data-react="layar-antrean"] */
export function pasang(el: HTMLElement) {
  createRoot(el).render(<LayarAntrean />)
}
