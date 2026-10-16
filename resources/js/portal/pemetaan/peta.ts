/*
 * Perhitungan bersama peta wilayah Pemetaan PKB di portal dan dashboard
 * (D-052): kotak tampilan saat satu kecamatan diperbesar, lebar peta yang
 * tampil, dan label yang tidak saling menimpa.
 */
import { useCallback, useState } from "react"

import { UKURAN_PETA } from "./batas-wilayah"

export interface Tampilan {
  x: number
  y: number
  w: number
  h: number
}

/** Seluruh peta, atau kotak di sekeliling sebuah kotak batas [x0, y0, x1, y1] dengan tepi 8% dan tinggi sedikitnya 0,6 kali lebarnya */
export function kotakTampilan(kotak?: readonly number[]): Tampilan {
  if (!kotak) return { x: 0, y: 0, w: UKURAN_PETA.lebar, h: UKURAN_PETA.tinggi }
  const [x0, y0, x1, y1] = kotak
  const w = (x1 - x0) * 1.16
  const h = Math.max((y1 - y0) * 1.16, w * 0.6)
  return { x: (x0 + x1 - w) / 2, y: (y0 + y1 - h) / 2, w, h }
}

/** Lebar elemen yang tampil (piksel), diperbarui saat ukurannya berubah; null sebelum terpasang */
export function useLebar<T extends Element>(): [(el: T | null) => (() => void) | undefined, number | null] {
  const [lebar, setLebar] = useState<number | null>(null)
  const ref = useCallback((el: T | null) => {
    if (!el) return undefined
    const pengamat = new ResizeObserver(([entri]) => setLebar(entri.contentRect.width))
    pengamat.observe(el)
    return () => pengamat.disconnect()
  }, [])
  return [ref, lebar]
}

export interface CalonLabel {
  kunci: string
  titik: readonly [number, number]
  /** Jarak titik label ke tepi wilayah (satuan peta) */
  ruang: number
  nama: string
  nilai: string
}

/**
 * Kunci label yang dapat dipasang tanpa saling menimpa, wilayah yang paling
 * lega lebih dulu. `px` = satuan peta per piksel; nama 12 px dan nilai 11 px
 * di bawahnya.
 */
export function labelTerpasang(daftar: CalonLabel[], px: number): Set<string> {
  const terpasang: [number, number, number, number][] = []
  const hasil = new Set<string>()
  for (const l of [...daftar].sort((a, b) => b.ruang - a.ruang)) {
    const lebar = Math.max(l.nama.length * 7.2, l.nilai.length * 6.4) * px
    const k: [number, number, number, number] = [l.titik[0] - lebar / 2, l.titik[1] - 14 * px, l.titik[0] + lebar / 2, l.titik[1] + 16 * px]
    if (terpasang.some((t) => k[0] < t[2] && t[0] < k[2] && k[1] < t[3] && t[1] < k[3])) continue
    terpasang.push(k)
    hasil.add(l.kunci)
  }
  return hasil
}
