/*
 * Lambang di atas judul pembuka beranda (D-046). HTML dari Blade memuat
 * lambang Provinsi NTT, dan logo Ditlantas serta Jasa Raharja di dalam
 * <template>, jadi keduanya tidak pernah tampil sebelum komponen siap (atribut
 * hidden tidak cukup: base.css membuat setiap img display: block). Tanpa
 * skrip hanya lambang NTT yang tampil. Komponen ini
 * mengambil alih ketiganya: sekitar 2,5 detik setelah halaman dibuka, logo
 * Ditlantas (kiri) dan Jasa Raharja (kanan) keluar dari balik lambang NTT,
 * dan mengklik lambang NTT menyembunyikan atau memunculkannya lagi. Hanya
 * pemunculan pertama yang otomatis.
 */
import { useEffect, useRef, useState } from "react"
import { createRoot } from "react-dom/client"

import "./lambang-pembuka.css"

/** Jeda sebelum logo mitra muncul sendiri setelah halaman dibuka (ms) */
const MUNCUL_SETELAH = 2500

interface Gambar {
  src: string
  alt: string
  width: number
  height: number
}

function LambangPembuka({ utama, kiri, kanan }: { utama: Gambar; kiri: Gambar; kanan: Gambar }) {
  const [tampil, setTampil] = useState(false)
  const otomatis = useRef(0)

  useEffect(() => {
    otomatis.current = window.setTimeout(() => setTampil(true), MUNCUL_SETELAH)
    return () => clearTimeout(otomatis.current)
  }, [])

  // "Logo Ditlantas Polda NTT" dan "Logo Jasa Raharja" menjadi "Ditlantas Polda NTT dan Jasa Raharja"
  const mitra = [kiri, kanan].map((g) => g.alt.replace(/^logo\s+/i, "")).join(" dan ")
  const alih = () => {
    clearTimeout(otomatis.current)
    setTampil((t) => !t)
  }

  return (
    <div className={tampil ? "lambang-pembuka is-tampil" : "lambang-pembuka"}>
      <img className="lambang-pembuka__mitra lambang-pembuka__mitra--kiri" {...kiri} aria-hidden={!tampil} />
      <button
        type="button"
        className="lambang-pembuka__utama"
        onClick={alih}
        aria-expanded={tampil}
        aria-label={`${utama.alt}: logo ${mitra}`}
        title={`${tampil ? "Sembunyikan" : "Tampilkan"} logo ${mitra}`}
      >
        <img className="emblem hero__emblem" {...utama} alt="" />
      </button>
      <img className="lambang-pembuka__mitra lambang-pembuka__mitra--kanan" {...kanan} aria-hidden={!tampil} />
    </div>
  )
}

/** Mengambil alih lambang pembuka; gambar di HTML-nya menjadi isinya */
export function pasang(el: HTMLElement) {
  const mitra = el.querySelector<HTMLTemplateElement>("template[data-lambang-mitra]")?.content
  const gambar = (posisi: string): Gambar | null => {
    const img = (posisi === "utama" ? el : mitra)?.querySelector<HTMLImageElement>(`img[data-lambang="${posisi}"]`)
    const angka = (nama: string) => Number(img?.getAttribute(nama))
    return img ? { src: img.getAttribute("src") ?? "", alt: img.alt, width: angka("width"), height: angka("height") } : null
  }
  const [utama, kiri, kanan] = ["utama", "kiri", "kanan"].map(gambar)
  if (!utama || !kiri || !kanan) return
  createRoot(el).render(<LambangPembuka utama={utama} kiri={kiri} kanan={kanan} />)
}
