/*
 * Berkas CSV Laporan Penerimaan, dipakai halaman portal (D-048) dan
 * dashboard (D-047): pemisah titik koma seperti pengaturan Excel berbahasa
 * Indonesia, dengan BOM agar Excel membacanya sebagai UTF-8.
 */

/** Kolom rupiah berkas CSV, dalam urutan berkas STS, dengan nama isian API */
export const KOLOM_CSV = [
  ["Transaksi", "transaksi"],
  ["PKB", "pkb"],
  ["BBNKB", "bbn"],
  ["Denda PKB", "dendaPkb"],
  ["Denda BBNKB", "dendaBbn"],
  ["Tunggakan", "tunggakan"],
  ["Denda Tunggakan", "dendaTunggakan"],
  ["Opsen PKB", "opsenPkb"],
  ["Opsen BBNKB", "opsenBbn"],
  ["Opsen Denda PKB", "opsenDendaPkb"],
  ["Opsen Denda BBNKB", "opsenDendaBbn"],
  ["Opsen Tunggakan", "opsenTunggakan"],
  ["Opsen Denda Tunggakan", "opsenDendaTunggakan"],
  ["Jumlah", "jumlah"],
] as const

export type KunciJumlah = (typeof KOLOM_CSV)[number][1]

/** Deret angka yang harus tetap teks di Excel, mis. nomoratur "0012345" yang nol di depannya akan dibuang bila dibaca sebagai angka */
export interface TeksAngka {
  teksAngka: string
}

function sel(teks: string | number | TeksAngka): string {
  if (typeof teks === "object") {
    // ="0012345" dibaca Excel sebagai teks; hanya untuk deret angka, selain itu sel biasa
    return /^\d+$/.test(teks.teksAngka) ? `"=""${teks.teksAngka}"""` : sel(teks.teksAngka)
  }
  const s = String(teks)
  // Teks yang diawali =, +, -, atau @ dibaca Excel sebagai rumus
  const aman = typeof teks === "string" && s.length > 1 && /^[=+\-@\t\r]/.test(s) ? `'${s}` : s
  return /[";\n\r]/.test(aman) ? `"${aman.replace(/"/g, '""')}"` : aman
}

/** Baris-baris tabel sebagai teks CSV */
export function teksCsv(baris: (string | number | TeksAngka)[][]): string {
  return "﻿" + baris.map((b) => b.map(sel).join(";")).join("\r\n") + "\r\n"
}

/** Unduh teks sebagai berkas dari peramban */
export function unduhTeks(teks: string, nama: string, jenis = "text/csv;charset=utf-8") {
  const url = URL.createObjectURL(new Blob([teks], { type: jenis }))
  const a = document.createElement("a")
  a.href = url
  a.download = nama
  document.body.append(a)
  a.click()
  a.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
