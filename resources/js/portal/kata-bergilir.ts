/*
 * Kata bergilir di judul pembuka beranda (D-045). Kata judul yang sama dengan
 * salah satu kata bergilir (kata utuh, huruf besar-kecil diabaikan) berganti
 * dengan kata lain sesuai urutan daftar, satu putaran, lalu berhenti lagi di
 * kata judul. Server mencocokkan dengan cara yang sama
 * (Setting::rotatingWordIn), dan dashboard memakai berkas ini untuk memeriksa
 * judul sebelum menyimpan.
 */

export interface JudulBergilir {
  /** Teks judul sebelum kata bergilir, termasuk spasinya */
  sebelum: string
  /** Teks judul sesudah kata bergilir */
  sesudah: string
  /** Kata yang tampil berurutan: kata judul, kata lain, lalu kata judul lagi */
  urutan: string[]
}

/** Posisi kata di judul sebagai kata utuh, tanpa membedakan huruf besar-kecil */
function cariKata(judul: string, kata: string): { indeks: number; teks: string } | null {
  const pola = new RegExp(`(^|[^\\p{L}\\p{N}])(${kata.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})(?![\\p{L}\\p{N}])`, "iu")
  const cocok = pola.exec(judul)
  return cocok ? { indeks: cocok.index + cocok[1].length, teks: cocok[2] } : null
}

/** Urutan kata pertama dari daftar yang dimuat judul, atau -1 bila tidak ada */
export function kataDalamJudul(judul: string, kata: readonly string[]): number {
  return kata.findIndex((k) => k.trim() !== "" && cariKata(judul, k.trim()) !== null)
}

/** Judul yang dipecah di sekitar kata bergilirnya; null bila tidak ada yang bergilir */
export function pisahJudul(judul: string, kata: readonly string[]): JudulBergilir | null {
  const rapi = kata.map((k) => k.trim())
  const i = kataDalamJudul(judul, rapi)
  if (i < 0) return null
  const lain = [...rapi.slice(i + 1), ...rapi.slice(0, i)].filter((k) => k !== "")
  if (lain.length === 0) return null
  const { indeks, teks } = cariKata(judul, rapi[i])!
  return { sebelum: judul.slice(0, indeks), sesudah: judul.slice(indeks + teks.length), urutan: [teks, ...lain, teks] }
}
