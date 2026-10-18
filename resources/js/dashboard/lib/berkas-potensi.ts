/*
 * Membaca berkas Data Potensi dari sistem BPAD di peramban (D-052, D-064):
 * tabel HTML berakhiran .xls, atau buku kerja .xlsx yang lebih lengkap.
 * Keduanya dibaca sedikit demi sedikit agar berkas besar (data setahun,
 * ratusan ribu kendaraan) tidak memenuhi memori, dan setiap barisnya disusun
 * menurut KOLOM_POTENSI, susunan yang diterima server. Modul ini tanpa React
 * agar bisa diuji langsung dengan berkas aslinya.
 */

/** Kolom yang dikirim ke server, berurutan: kolom tabel .xls, lalu kolom tambahan buku kerja .xlsx (D-064) */
export const KOLOM_POTENSI = [
  "NOPOL", "NAMA", "KTP", "TELEPON", "ALAMAT", "RT", "RW", "KELURAHAN", "KECAMATAN", "JENIS", "MERK", "TIPE",
  "MESIN", "RANGKA", "TAHUN BUAT", "WARNA PLAT", "WARNA KENDARAAN", "BBM", "TGL PENETAPAN", "UPT ASAL", "PENGGUNAAN", "CYL", "KODE JR",
  "SD STNK", "SD NOTICE", "RUBAH BENTUK", "NJKB", "NJUB", "BOBOT", "BEDA HARI BERJALAN", "BEDA BULAN BERLAJAN", "BULAT BULAN BERLAJAN",
  "TAHUN TUNGGAK", "TAHUN TUNGGAK AWAL", "POKOK PAJAK", "DENDA BERJALAN", "POKOK TUNGGAK", "DENDA TUNGGAK", "KEPATUHAN", "PERINGATAN",
  "STS KENDARAAN DTD", "KET GIAT DTD", "PETUGAS DTD", "KODE UPT", "KODE KEC", "TARIF", "TOTAL", "POKOK PAJAK NOR", "DENDA BERJALAN NOR",
  "POKOK TUNGGAK NOR", "DENDA TUNGGAK NOR", "TOTAL NOR", "DITARIK PADA",
] as const

/** Judul kolom buku kerja .xlsx yang namanya lain dari tabel .xls */
const ALIAS_KOLOM: Record<string, string> = { "STS KENDARAAN": "STS KENDARAAN DTD" }

/** Kolom yang boleh tidak ada: kegiatan dan petugas kunjungan tidak ada di buku kerja, kolom tambahannya tidak ada di tabel .xls */
const KOLOM_PILIHAN = new Set<string>([
  "KET GIAT DTD", "PETUGAS DTD", "KODE UPT", "KODE KEC", "TARIF", "TOTAL", "POKOK PAJAK NOR", "DENDA BERJALAN NOR", "POKOK TUNGGAK NOR",
  "DENDA TUNGGAK NOR", "TOTAL NOR", "DITARIK PADA",
])

/** Letak setiap kolom KOLOM_POTENSI di berkas (-1 bila tidak ada), atau null bila ada kolom wajib yang hilang */
export function petaKolom(judul: string[]): number[] | null {
  const letak = new Map(judul.map((j, i) => [ALIAS_KOLOM[j] ?? j, i]))
  const peta = KOLOM_POTENSI.map((k) => letak.get(k) ?? -1)
  return KOLOM_POTENSI.every((k, i) => peta[i] >= 0 || KOLOM_PILIHAN.has(k)) ? peta : null
}

const ENTITAS: Record<string, string> = { amp: "&", lt: "<", gt: ">", quot: '"', apos: "'", nbsp: " " }

/** Teks satu sel: tanpa tag, entitas HTML/XML dibaca, spasi dirapikan */
function teksSel(html: string): string {
  return html
    .replace(/<[^>]*>/g, "")
    .replace(/&(#x[0-9a-f]+|#\d+|[a-z]+);/gi, (asli, k: string) =>
      k[0] === "#" ? String.fromCodePoint(k[1].toLowerCase() === "x" ? parseInt(k.slice(2), 16) : Number(k.slice(1))) : (ENTITAS[k.toLowerCase()] ?? asli)
    )
    .replace(/\s+/g, " ")
    .trim()
}

interface PenerimaTabel {
  /** Judul kolom, sekali, sebelum baris pertama */
  judul: (kolom: string[]) => void
  baris: (sel: string[]) => void | Promise<void>
}

/** Tabel HTML berakhiran .xls: judul dari <th>, lalu setiap <tr> berisi lebih dari satu <td> (baris catatan kaki dilewati) */
async function bacaTabelHtml(berkas: Blob, penerima: PenerimaTabel, sinyal?: AbortSignal): Promise<void> {
  const pembaca = berkas.stream().pipeThrough(new TextDecoderStream()).getReader()
  let sisa = ""
  let kolom: string[] | null = null
  try {
    for (;;) {
      if (sinyal?.aborted) throw new DOMException("Dibatalkan", "AbortError")
      const { value, done } = await pembaca.read()
      if (value) sisa += value
      if (!kolom) {
        const awalBaris = sisa.search(/<td[\s>]/i)
        if (awalBaris < 0 && !done) continue
        kolom = [...sisa.slice(0, awalBaris < 0 ? sisa.length : awalBaris).matchAll(/<th\b[^>]*>([\s\S]*?)<\/th>/gi)].map((m) => teksSel(m[1]))
        penerima.judul(kolom)
      }
      const akhir = done ? sisa.length : sisa.toLowerCase().lastIndexOf("</tr>")
      if (akhir >= 0) {
        const potong = sisa.slice(0, done ? akhir : akhir + 5)
        sisa = done ? "" : sisa.slice(akhir + 5)
        for (const m of potong.matchAll(/<tr\b[^>]*>([\s\S]*?)<\/tr>/gi)) {
          const sel = [...m[1].matchAll(/<td\b[^>]*>([\s\S]*?)<\/td>/gi)].map((c) => teksSel(c[1]))
          if (sel.length > 1) await penerima.baris(sel)
        }
      }
      if (done) break
    }
  } finally {
    pembaca.releaseLock()
  }
}

/* ---------- Buku kerja .xlsx: arsip ZIP berisi XML ---------- */

const BUKAN_XLSX = "Berkas .xlsx tidak bisa dibaca. Simpan ulang berkasnya dari Excel, lalu coba lagi."

/** Satu berkas di dalam arsip ZIP: cara pemadatan, ukuran terpadatkan, dan letak kepala lokalnya */
interface EntriZip {
  metode: number
  ukuran: number
  letak: number
}

/** Daftar isi arsip ZIP dari direktori pusat di ujung berkas, termasuk ukuran dan letak ZIP64 */
async function isiZip(berkas: Blob): Promise<Map<string, EntriZip>> {
  const awalEkor = Math.max(0, berkas.size - 65_557)
  const ekor = new DataView(await berkas.slice(awalEkor).arrayBuffer())
  let akhir = -1
  for (let i = ekor.byteLength - 22; i >= 0; i--) {
    if (ekor.getUint32(i, true) === 0x06054b50) {
      akhir = i
      break
    }
  }
  if (akhir < 0) throw new Error(BUKAN_XLSX)
  let ukuranDaftar = ekor.getUint32(akhir + 12, true)
  let letakDaftar = ekor.getUint32(akhir + 16, true)
  if (letakDaftar === 0xffffffff && akhir >= 20 && ekor.getUint32(akhir - 20, true) === 0x07064b50) {
    const letak64 = Number(ekor.getBigUint64(akhir - 20 + 8, true))
    const rekam = new DataView(await berkas.slice(letak64, letak64 + 56).arrayBuffer())
    ukuranDaftar = Number(rekam.getBigUint64(40, true))
    letakDaftar = Number(rekam.getBigUint64(48, true))
  }
  const daftar = new DataView(await berkas.slice(letakDaftar, letakDaftar + ukuranDaftar).arrayBuffer())
  const dekoder = new TextDecoder()
  const isi = new Map<string, EntriZip>()
  for (let p = 0; p + 46 <= daftar.byteLength && daftar.getUint32(p, true) === 0x02014b50; ) {
    const panjangNama = daftar.getUint16(p + 28, true)
    const akhirTambahan = p + 46 + panjangNama + daftar.getUint16(p + 30, true)
    let ukuran = daftar.getUint32(p + 20, true)
    let letak = daftar.getUint32(p + 42, true)
    // Ruas ZIP64 (0x0001) hanya memuat nilai yang ditulis 0xFFFFFFFF, berurutan: ukuran asli, terpadatkan, letak
    for (let q = p + 46 + panjangNama; q + 4 <= akhirTambahan; q += 4 + daftar.getUint16(q + 2, true)) {
      if (daftar.getUint16(q, true) !== 1) continue
      let r = q + 4
      if (daftar.getUint32(p + 24, true) === 0xffffffff) r += 8
      if (ukuran === 0xffffffff) {
        ukuran = Number(daftar.getBigUint64(r, true))
        r += 8
      }
      if (letak === 0xffffffff) letak = Number(daftar.getBigUint64(r, true))
    }
    isi.set(dekoder.decode(new Uint8Array(daftar.buffer, p + 46, panjangNama)), { metode: daftar.getUint16(p + 10, true), ukuran, letak })
    p = akhirTambahan + daftar.getUint16(p + 32, true)
  }
  return isi
}

/** Isi satu berkas dalam arsip sebagai aliran teks, dibuka dengan DecompressionStream bawaan peramban */
async function aliranZip(berkas: Blob, entri: EntriZip): Promise<ReadableStream<string>> {
  const kepala = new DataView(await berkas.slice(entri.letak, entri.letak + 30).arrayBuffer())
  if (kepala.getUint32(0, true) !== 0x04034b50) throw new Error(BUKAN_XLSX)
  const awal = entri.letak + 30 + kepala.getUint16(26, true) + kepala.getUint16(28, true)
  const mentah = berkas.slice(awal, awal + entri.ukuran).stream()
  if (entri.metode === 0) return mentah.pipeThrough(new TextDecoderStream())
  if (entri.metode !== 8) throw new Error(BUKAN_XLSX)
  return mentah.pipeThrough(new DecompressionStream("deflate-raw")).pipeThrough(new TextDecoderStream())
}

/** Setiap elemen <tag>…</tag> yang utuh dalam aliran XML, sedikit demi sedikit; elemen kosong <tag/> berisi "" */
async function tiapElemen(aliran: ReadableStream<string>, tag: string, tiap: (isi: string) => void | Promise<void>, sinyal?: AbortSignal): Promise<void> {
  const pembaca = aliran.getReader()
  const tutup = `</${tag}>`
  const pola = new RegExp(`<${tag}\\b[^>]*?(?:/>|>([\\s\\S]*?)</${tag}>)`, "g")
  let sisa = ""
  try {
    for (;;) {
      if (sinyal?.aborted) throw new DOMException("Dibatalkan", "AbortError")
      const { value, done } = await pembaca.read()
      if (value) sisa += value
      const akhir = done ? sisa.length : sisa.lastIndexOf(tutup)
      if (akhir >= 0) {
        const potong = done ? sisa : sisa.slice(0, akhir + tutup.length)
        sisa = done ? "" : sisa.slice(akhir + tutup.length)
        for (const m of potong.matchAll(pola)) await tiap(m[1] ?? "")
      }
      if (done) break
    }
  } catch (e) {
    await pembaca.cancel().catch(() => {})
    throw e
  } finally {
    pembaca.releaseLock()
  }
}

/** "AC" → 28: urutan kolom dari huruf alamat sel, mulai 0 */
function indeksKolom(huruf: string): number {
  let n = 0
  for (const c of huruf) n = n * 26 + c.charCodeAt(0) - 64
  return n - 1
}

/** Angka sel sebagai teks seperti tabel .xls: bilangan bulat tanpa pemisah atau eksponen, pecahan apa adanya */
function angkaSel(v: string): string {
  const n = Number(v)
  if (v === "" || !Number.isFinite(n)) return v
  return Number.isInteger(n) ? n.toFixed(0) : String(n)
}

/** Teks <t> sebuah teks sel (<is> atau <si>) apa adanya, belum dibaca entitasnya; ejaan fonetik <rPh> dilewati */
function teksXml(isi: string): string {
  return [...isi.replace(/<rPh\b[\s\S]*?<\/rPh>/g, "").matchAll(/<t\b[^>]*>([\s\S]*?)<\/t>/g)].map((t) => t[1]).join("")
}

/** Sel satu baris <row> menurut kolomnya; sel kosong yang tidak ditulis menjadi "" */
function selBaris(baris: string, teksBersama: string[]): string[] {
  const sel: string[] = []
  let berikut = 0
  for (const m of baris.matchAll(/<c\b([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g)) {
    const atribut = m[1]
    const isi = m[2] ?? ""
    const ref = /\br="([A-Z]+)\d*"/.exec(atribut)?.[1]
    const tipe = /\bt="([^"]*)"/.exec(atribut)?.[1] ?? "n"
    const i = ref ? indeksKolom(ref) : berikut
    const v = /<v\b[^>]*>([\s\S]*?)<\/v>/.exec(isi)?.[1] ?? ""
    const nilai =
      tipe === "inlineStr" ? teksXml(isi)
      : tipe === "s" ? (teksBersama[Number(v)] ?? "")
      : tipe === "n" ? angkaSel(v)
      : v
    while (sel.length < i) sel.push("")
    sel[i] = teksSel(nilai)
    berikut = i + 1
  }
  return sel
}

/** Lembar kerja pertama: baris pertama yang berisi menjadi judul, baris sesudahnya dilengkapi selebar judul */
async function bacaTabelXlsx(berkas: Blob, penerima: PenerimaTabel, sinyal?: AbortSignal): Promise<void> {
  const isi = await isiZip(berkas)
  const nomorLembar = (nama: string) => Number(/sheet(\d+)\.xml$/.exec(nama)?.[1])
  const lembar = [...isi.keys()].filter((n) => /^xl\/worksheets\/sheet\d+\.xml$/.test(n)).sort((a, b) => nomorLembar(a) - nomorLembar(b))[0]
  if (!lembar) throw new Error(BUKAN_XLSX)
  const teksBersama: string[] = []
  const entriBersama = isi.get("xl/sharedStrings.xml")
  if (entriBersama) await tiapElemen(await aliranZip(berkas, entriBersama), "si", (s) => void teksBersama.push(teksXml(s)), sinyal)
  let lebar = -1
  await tiapElemen(
    await aliranZip(berkas, isi.get(lembar) as EntriZip),
    "row",
    async (baris) => {
      const sel = selBaris(baris, teksBersama)
      if (!sel.some(Boolean)) return
      if (lebar < 0) {
        lebar = sel.length
        penerima.judul(sel)
        return
      }
      while (sel.length < lebar) sel.push("")
      await penerima.baris(sel)
    },
    sinyal
  )
  if (lebar < 0) penerima.judul([])
}

/** Arsip ZIP (.xlsx) dikenali dari empat bita pertamanya, "PK\x03\x04" */
async function berkasZip(berkas: Blob): Promise<boolean> {
  const awal = new Uint8Array(await berkas.slice(0, 4).arrayBuffer())
  return awal[0] === 0x50 && awal[1] === 0x4b && awal[2] === 0x03 && awal[3] === 0x04
}

/**
 * Membaca berkas Data Potensi baris demi baris. Setiap baris disusun menurut
 * KOLOM_POTENSI (kolom yang tidak ada di berkas menjadi ""), dengan tanda
 * apakah jumlah selnya sama dengan judul. Mengembalikan judul berkas.
 */
export async function bacaBerkasPotensi(
  berkas: Blob,
  tiapBaris: (sel: string[], utuh: boolean) => void | Promise<void>,
  sinyal?: AbortSignal
): Promise<string[]> {
  let judul: string[] = []
  let peta: number[] | null = null
  const penerima: PenerimaTabel = {
    judul: (kolom) => {
      judul = kolom
      peta = petaKolom(kolom)
    },
    baris: (sel) => tiapBaris(peta ? peta.map((i) => (i < 0 ? "" : (sel[i] ?? ""))) : sel, sel.length === judul.length),
  }
  await ((await berkasZip(berkas)) ? bacaTabelXlsx(berkas, penerima, sinyal) : bacaTabelHtml(berkas, penerima, sinyal))
  return judul
}

export interface PemeriksaanBerkas {
  kolomCocok: boolean
  jumlah: number
  /** Tanggal penetapan yang muncul di berkas */
  tanggalPenetapan: string[]
  /** Baris yang jumlah selnya tidak sama dengan judul */
  barisRusak: number
}

/** Lintasan pertama: susunan kolom, banyaknya baris, dan tanggal penetapannya, tanpa mengirim apa pun */
export async function periksaBerkasPotensi(berkas: Blob, sinyal?: AbortSignal): Promise<PemeriksaanBerkas> {
  let jumlah = 0
  let barisRusak = 0
  const tanggal = new Set<string>()
  const judul = await bacaBerkasPotensi(
    berkas,
    (sel, utuh) => {
      jumlah++
      if (!utuh) barisRusak++
      else if (sel[18]) tanggal.add(sel[18])
    },
    sinyal
  )
  return { kolomCocok: petaKolom(judul) !== null, jumlah, tanggalPenetapan: [...tanggal].sort(), barisRusak }
}
