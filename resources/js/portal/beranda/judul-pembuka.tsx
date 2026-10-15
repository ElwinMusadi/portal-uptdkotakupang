/*
 * Judul pembuka beranda (D-045), komponen React pertama di portal. HTML dari
 * Blade tetap menjadi isi awal yang dibaca mesin pencari dan tampil sebelum
 * skrip dimuat; komponen ini lalu mengambil alih <h1> itu. Teks dibaca dari
 * GET /api/public/beranda dan dipakai bila beranda sudah pernah disimpan
 * (D-023); kata bergilir selalu dari sana. Kata judul yang bergilir berganti
 * dengan gulir vertikal satu putaran, diam sejenak di kata judul, lalu
 * berulang. Pembaca layar selalu mendengar judul utuh, dan pengunjung yang
 * memilih gerak dikurangi melihat judul diam.
 */
import { useEffect, useLayoutEffect, useRef, useState } from "react"
import { createRoot } from "react-dom/client"

import { pisahJudul, type JudulBergilir } from "../kata-bergilir"
import "./judul-pembuka.css"

/** Jeda sebelum kata pertama berganti, setelah judul tampil (ms) */
const JEDA_AWAL = 1200
/** Lama setiap kata tampil (ms) */
const TAHAN = 1600
/** Lama kata judul diam sebelum putaran berikutnya (ms) */
const JEDA_ULANG = 10_000

interface IsiJudul {
  judul: string
  lanjutan: string
  kata: string[]
}

const teks = (x: unknown): x is string => typeof x === "string"

/** Isi judul dari API; teksnya hanya bila beranda sudah pernah disimpan */
async function muatJudul(): Promise<{ isi: IsiJudul; disimpan: boolean }> {
  const res = await fetch("/api/public/beranda", { headers: { Accept: "application/json" } })
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  const b = (await res.json()) as { hero?: Record<string, unknown>; diperbarui?: unknown }
  const { judul, lanjutan, kataBergilir } = b.hero ?? {}
  if (!teks(judul) || !teks(lanjutan) || !Array.isArray(kataBergilir) || !kataBergilir.every(teks) || !(b.diperbarui === null || teks(b.diperbarui))) {
    throw new Error("Respons tidak dikenal")
  }
  return { isi: { judul, lanjutan, kata: kataBergilir }, disimpan: b.diperbarui !== null }
}

function JudulPembuka({ awal }: { awal: IsiJudul }) {
  const [isi, setIsi] = useState(awal)

  useEffect(() => {
    let batal = false
    muatJudul()
      .then(({ isi: dariApi, disimpan }) => {
        if (!batal) setIsi(disimpan ? dariApi : { ...awal, kata: dariApi.kata })
      })
      // Bila API gagal, judul dari HTML tetap tampil tanpa animasi
      .catch(() => {})
    return () => {
      batal = true
    }
  }, [awal])

  const bagian = pisahJudul(isi.judul, isi.kata)
  return (
    <>
      {bagian ? <KataBergilir key={[bagian.sebelum, ...bagian.urutan, bagian.sesudah].join("\n")} bagian={bagian} /> : isi.judul}
      {isi.lanjutan && (
        <>
          <br />
          <span className="tone">{isi.lanjutan}</span>
        </>
      )}
    </>
  )
}

function KataBergilir({ bagian }: { bagian: JudulBergilir }) {
  const { urutan } = bagian
  const [, isiSebelum, spasi] = /^([\s\S]*?)(\s*)$/.exec(bagian.sebelum) ?? ["", bagian.sebelum, ""]
  // Tanda baca yang menempel pada kata ikut satu baris dengannya
  const [, lekat, sisa] = /^(\S*)([\s\S]*)$/.exec(bagian.sesudah) ?? ["", "", bagian.sesudah]
  // Banyaknya pergantian sejauh ini; kata yang tampil berputar di urutan
  const [langkah, setLangkah] = useState(0)
  const kini = langkah % urutan.length
  const tadi = (langkah - 1) % urutan.length
  const [lebar, setLebar] = useState<number[] | null>(null)
  const [barisSendiri, setBarisSendiri] = useState(false)
  const penanda = useRef<HTMLSpanElement>(null)
  const satuan = useRef<HTMLSpanElement>(null)
  const kotak = useRef<HTMLSpanElement>(null)
  const ukuran = useRef<HTMLSpanElement>(null)

  // Lebar setiap kata, diukur ulang saat ukuran layar atau huruf berubah. Bila
  // panjang kata mengubah letak baris judul, kata diberi baris sendiri agar
  // judul tidak melompat setiap kali kata berganti.
  useLayoutEffect(() => {
    const ukur = () => {
      const k = kotak.current
      const s = satuan.current
      if (!k || !s || !ukuran.current) return
      const w = Array.from(ukuran.current.children, (el) => el.getBoundingClientRect().width)
      const semula = { lebar: k.style.width, transisi: k.style.transition }
      k.style.transition = "none"
      s.classList.remove("kata-gilir--baris")
      const letak = (uji: number) => {
        k.style.width = `${uji}px`
        return [penanda.current?.getBoundingClientRect().top ?? 0, s.getBoundingClientRect().top]
      }
      const [sebelumSempit, kataSempit] = letak(Math.min(...w))
      const [sebelumLebar, kataLebar] = letak(Math.max(...w))
      const sendiri = Math.abs(sebelumSempit - sebelumLebar) > 1 || Math.abs(kataSempit - kataLebar) > 1
      s.classList.toggle("kata-gilir--baris", sendiri)
      k.style.width = semula.lebar
      void k.offsetWidth
      k.style.transition = semula.transisi
      setLebar(w)
      setBarisSendiri(sendiri)
    }
    ukur()
    let bingkai = 0
    const ukurNanti = () => {
      cancelAnimationFrame(bingkai)
      bingkai = requestAnimationFrame(ukur)
    }
    window.addEventListener("resize", ukurNanti)
    void document.fonts.ready.then(ukurNanti)
    return () => {
      window.removeEventListener("resize", ukurNanti)
      cancelAnimationFrame(bingkai)
    }
  }, [])

  // Putaran demi putaran, dengan jeda di kata judul; menunggu bila tab sedang tidak dilihat
  const siap = lebar !== null
  useEffect(() => {
    if (!siap || matchMedia("(prefers-reduced-motion: reduce)").matches) return
    let waktu = 0
    let i = 0
    const maju = () => {
      if (document.hidden) {
        document.addEventListener("visibilitychange", maju, { once: true })
        return
      }
      i += 1
      setLangkah(i)
      waktu = window.setTimeout(maju, i % urutan.length === 0 ? JEDA_ULANG : TAHAN)
    }
    waktu = window.setTimeout(maju, JEDA_AWAL)
    return () => {
      clearTimeout(waktu)
      document.removeEventListener("visibilitychange", maju)
    }
  }, [siap, urutan.length])

  return (
    <>
      {isiSebelum}
      {isiSebelum && <span ref={penanda} />}
      {spasi}
      <span ref={satuan} className={barisSendiri ? "kata-gilir kata-gilir--baris" : "kata-gilir"}>
        <span className="sr-only">{urutan[0]}</span>
        <span ref={kotak} className="kata-gilir__kotak" aria-hidden="true" style={lebar ? { width: `${lebar[kini]}px` } : undefined}>
          {langkah > 0 && (
            <span key={langkah - 1} className="kata-gilir__kata kata-gilir__kata--keluar">
              {urutan[tadi]}
            </span>
          )}
          <span key={langkah} className={langkah > 0 ? "kata-gilir__kata kata-gilir__kata--masuk" : "kata-gilir__kata"}>
            {urutan[kini]}
          </span>
          <span ref={ukuran} className="kata-gilir__ukur">
            {urutan.map((k, i) => (
              <span key={i}>{k}</span>
            ))}
          </span>
        </span>
        {lekat}
      </span>
      {sisa}
    </>
  )
}

/** Mengambil alih <h1> judul pembuka; teks HTML-nya menjadi isi awal */
export function pasang(el: HTMLElement) {
  const rapi = (t: string | null | undefined) => (t ?? "").replace(/\s+/g, " ").trim()
  const tanpaLanjutan = el.cloneNode(true) as HTMLElement
  tanpaLanjutan.querySelector(".tone")?.remove()
  const awal: IsiJudul = { judul: rapi(tanpaLanjutan.textContent), lanjutan: rapi(el.querySelector(".tone")?.textContent), kata: [] }
  createRoot(el).render(<JudulPembuka awal={awal} />)
}
