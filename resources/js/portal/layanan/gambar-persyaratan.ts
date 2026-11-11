/*
 * Persyaratan layanan sebagai gambar PNG yang bisa diunduh (D-091). Tombol
 * [data-unduh-persyaratan="slug"] di setiap kartu halaman Layanan membuat
 * gambar satu layanan, dan [data-unduh-persyaratan="semua"] satu gambar
 * berisi semua layanan. Gambar dilukis di kanvas tanpa pustaka tambahan,
 * dengan huruf portal dan warna terang (juga saat portal bermode gelap) agar
 * mudah dibaca di ponsel dan dicetak. Persyaratan tambahan (mis. badan hukum)
 * ikut di setiap gambar, karena berlaku untuk semua layanan.
 */
import { bertitik, type Layanan, muatLayanan, pisahKeterangan } from "./data"

const LEBAR = 540 // px logis; gambar 1.080 px pada skala 2
const TEPI = 36
const SKALA = 2
const LUAS_MAKS = 16_000_000 // batas luas kanvas Safari iOS dalam piksel
const PESAN_GAGAL = "Gambar belum dapat dibuat. Coba lagi."

const WARNA = {
  latar: "#FFFFFF",
  kop: "#0F1319",
  tinta: "#0F1319",
  redup: "#5B6270",
  garis: "#DADDE2",
  panel: "#F4F5F7",
  peringatan: { latar: "#FFF3E0", teks: "#8A4B00" },
  aman: { latar: "#E8F5EC", teks: "#1E6B3A" },
}

const fmtBulan = new Intl.DateTimeFormat("id-ID", { month: "long", year: "numeric", timeZone: "Asia/Makassar" })
const fmtWaktu = new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "long", year: "numeric", hour: "2-digit", minute: "2-digit", hourCycle: "h23", timeZone: "Asia/Makassar" })

/** Kanvas dan posisi tulis; saat menakar (gambar false) hanya tinggi yang dihitung */
interface Kuas {
  ctx: CanvasRenderingContext2D
  gambar: boolean
  huruf: string
  y: number
}

const atur = (k: Kuas, ukuran: number, tebal = 400) => {
  k.ctx.font = `${tebal} ${ukuran}px ${k.huruf}`
}

/** Pecah teks menjadi baris selebar maks; kata yang terlalu panjang dipotong per huruf */
function bungkus(ctx: CanvasRenderingContext2D, teks: string, maks: number): string[] {
  const baris: string[] = []
  let kini = ""
  for (const kata of teks.split(/\s+/).filter(Boolean)) {
    const coba = kini ? `${kini} ${kata}` : kata
    if (ctx.measureText(coba).width <= maks) {
      kini = coba
      continue
    }
    if (kini) baris.push(kini)
    kini = ""
    for (const huruf of kata) {
      if (ctx.measureText(kini + huruf).width > maks && kini) {
        baris.push(kini)
        kini = ""
      }
      kini += huruf
    }
  }
  if (kini) baris.push(kini)
  return baris.length ? baris : [""]
}

/** Tulis paragraf terbungkus mulai k.y; mengembalikan jumlah baris */
function paragraf(k: Kuas, teks: string, o: { x?: number; maks?: number; ukuran: number; tebal?: number; warna?: string; jarak?: number }): number {
  atur(k, o.ukuran, o.tebal)
  const x = o.x ?? TEPI
  const tinggi = o.jarak ?? Math.round(o.ukuran * 1.45)
  const baris = bungkus(k.ctx, teks, o.maks ?? LEBAR - x - TEPI)
  for (const b of baris) {
    if (k.gambar) {
      k.ctx.fillStyle = o.warna ?? WARNA.tinta
      k.ctx.fillText(b, x, k.y + o.ukuran)
    }
    k.y += tinggi
  }
  return baris.length
}

function kotak(k: Kuas, x: number, y: number, l: number, t: number, r: number, isi?: string, garis?: string) {
  if (!k.gambar) return
  k.ctx.beginPath()
  k.ctx.roundRect(x, y, l, t, r)
  if (isi) {
    k.ctx.fillStyle = isi
    k.ctx.fill()
  }
  if (garis) {
    k.ctx.strokeStyle = garis
    k.ctx.lineWidth = 1
    k.ctx.stroke()
  }
}

function garisMendatar(k: Kuas, y: number, warna = WARNA.garis, tebal = 1) {
  if (!k.gambar) return
  k.ctx.fillStyle = warna
  k.ctx.fillRect(TEPI, y, LEBAR - TEPI * 2, tebal)
}

/** Kop berwarna navbar dengan lambang NTT dan nama UPTD */
function kop(k: Kuas, lambang: HTMLImageElement | null) {
  const tinggi = 78
  if (k.gambar) {
    k.ctx.fillStyle = WARNA.kop
    k.ctx.fillRect(0, 0, LEBAR, tinggi)
    if (lambang) k.ctx.drawImage(lambang, TEPI, 17, 42, 44)
  }
  const x = TEPI + (lambang ? 56 : 0)
  k.y = 22
  paragraf(k, "UPTD Pendapatan Daerah Wilayah Kota Kupang", { x, ukuran: 13.5, tebal: 700, warna: "#FFFFFF", jarak: 19 })
  paragraf(k, "SAMSAT Kota Kupang · BPAD Provinsi NTT", { x, ukuran: 11.5, warna: "rgba(255,255,255,.72)", jarak: 16 })
  k.y = tinggi + 26
}

/** Label kecil berderet seperti di kartu halaman Layanan */
function label(k: Kuas, l: Layanan) {
  const daftar: { teks: string; latar?: string; warna: string; garis?: string }[] = [
    l.cekFisik ? { teks: "Wajib cek fisik", latar: WARNA.peringatan.latar, warna: WARNA.peringatan.teks } : { teks: "Tanpa cek fisik", latar: WARNA.aman.latar, warna: WARNA.aman.teks },
    ...(l.nonTunai ? [{ teks: "Bisa non-tunai", warna: WARNA.tinta, garis: WARNA.garis }] : []),
    ...(l.diwakilkan ? [{ teks: "Dapat diwakilkan", warna: WARNA.tinta, garis: WARNA.garis }] : []),
    ...l.label.map((t) => ({ teks: t, warna: WARNA.tinta, garis: WARNA.garis })),
  ]
  atur(k, 11.5, 600)
  const tinggi = 24
  let x = TEPI
  for (const d of daftar) {
    const lebar = Math.min(k.ctx.measureText(d.teks).width + 20, LEBAR - TEPI * 2)
    if (x > TEPI && x + lebar > LEBAR - TEPI) {
      x = TEPI
      k.y += tinggi + 6
    }
    kotak(k, x, k.y, lebar, tinggi, tinggi / 2, d.latar, d.garis)
    if (k.gambar) {
      k.ctx.fillStyle = d.warna
      k.ctx.fillText(d.teks, x + 10, k.y + 16, lebar - 20)
    }
    x += lebar + 6
  }
  k.y += tinggi
}

/** Daftar berkas: kotak centang kosong, nama berkas (keterangan di bawahnya), dan jumlah */
function tabel(k: Kuas, persyaratan: Layanan["persyaratan"]) {
  const kolomJumlah = 96
  const xTeks = TEPI + 28
  const maksTeks = LEBAR - TEPI - kolomJumlah - xTeks - 8
  atur(k, 10.5, 700)
  if (k.gambar) {
    k.ctx.fillStyle = WARNA.redup
    k.ctx.fillText("KELENGKAPAN DOKUMEN", xTeks, k.y + 11)
    k.ctx.textAlign = "right"
    k.ctx.fillText("JUMLAH", LEBAR - TEPI, k.y + 11)
    k.ctx.textAlign = "left"
  }
  k.y += 20
  garisMendatar(k, k.y, WARNA.tinta)
  for (const p of persyaratan) {
    const awal = k.y + 11
    k.y = awal
    kotak(k, TEPI + 2, awal + 2, 14, 14, 3, undefined, WARNA.redup)
    const [utama, ket] = pisahKeterangan(p.dokumen)
    paragraf(k, utama, { x: xTeks, maks: maksTeks, ukuran: 13.5, jarak: 19 })
    if (ket) paragraf(k, ket, { x: xTeks, maks: maksTeks, ukuran: 12, warna: WARNA.redup, jarak: 17 })
    const sesudah = k.y
    k.y = awal
    atur(k, 13.5, 600)
    const jumlah = bungkus(k.ctx, p.jumlah || "—", kolomJumlah)
    if (k.gambar) {
      k.ctx.fillStyle = WARNA.tinta
      k.ctx.textAlign = "right"
      jumlah.forEach((b, i) => k.ctx.fillText(b, LEBAR - TEPI, awal + 13.5 + i * 19))
      k.ctx.textAlign = "left"
    }
    k.y = Math.max(sesudah, awal + jumlah.length * 19) + 9
    garisMendatar(k, k.y)
  }
}

function catatan(k: Kuas, teks: string) {
  if (!teks) return
  atur(k, 12.5)
  const baris = bungkus(k.ctx, teks, LEBAR - TEPI * 2 - 28)
  const tinggi = baris.length * 18 + 20
  kotak(k, TEPI, k.y, LEBAR - TEPI * 2, tinggi, 10, WARNA.panel)
  k.y += 10
  paragraf(k, teks, { x: TEPI + 14, maks: LEBAR - TEPI * 2 - 28, ukuran: 12.5, warna: WARNA.redup, jarak: 18 })
  k.y += 10
}

/** Satu layanan: loket, judul, ringkasan, label, daftar berkas, dan catatan */
function bagianLayanan(k: Kuas, l: Layanan, judul: string) {
  atur(k, 11, 700)
  if (k.gambar) {
    k.ctx.fillStyle = WARNA.redup
    k.ctx.letterSpacing = "1px"
    k.ctx.fillText(l.loket.toUpperCase(), TEPI, k.y + 11)
    k.ctx.letterSpacing = "0px"
  }
  k.y += 22
  paragraf(k, judul, { ukuran: 23, tebal: 700, jarak: 29 })
  k.y += 4
  if (l.ringkasan) paragraf(k, l.ringkasan, { ukuran: 13.5, warna: WARNA.redup, jarak: 20 })
  k.y += 10
  label(k, l)
  k.y += 20
  tabel(k, l.persyaratan)
  k.y += 14
  catatan(k, l.catatan)
}

/** Persyaratan tambahan yang berlaku untuk semua layanan, mis. kendaraan atas nama badan hukum */
function bagianTambahan(k: Kuas, t: Layanan) {
  atur(k, 11, 700)
  if (k.gambar) {
    k.ctx.fillStyle = WARNA.redup
    k.ctx.fillText("BAWA JUGA BILA BERLAKU", TEPI, k.y + 11)
  }
  k.y += 22
  paragraf(k, bertitik(t.nama), { ukuran: 17, tebal: 700, jarak: 23 })
  if (t.ringkasan) paragraf(k, t.ringkasan, { ukuran: 12.5, warna: WARNA.redup, jarak: 18 })
  k.y += 12
  tabel(k, t.persyaratan)
  k.y += 14
  catatan(k, t.catatan)
}

function kaki(k: Kuas, dipakai: Layanan[], alamat: string) {
  k.y += 10
  garisMendatar(k, k.y)
  k.y += 14
  const terakhir = dipakai.map((l) => l.terbitPada).filter((t): t is string => Boolean(t)).sort().pop()
  const sumber = `Sumber: papan persyaratan loket UPTD${terakhir ? ` · diverifikasi petugas, ${fmtBulan.format(new Date(terakhir))}` : ""}`
  paragraf(k, sumber, { ukuran: 11.5, warna: WARNA.redup, jarak: 17 })
  paragraf(k, `Selengkapnya dan terbaru: ${alamat}`, { ukuran: 11.5, warna: WARNA.redup, jarak: 17 })
  paragraf(k, `Diunduh ${fmtWaktu.format(new Date()).replace(":", ".")} WITA`, { ukuran: 11.5, warna: WARNA.redup, jarak: 17 })
  k.y += TEPI - 10
}

function lukis(k: Kuas, layanan: Layanan[], tambahan: Layanan[], semua: boolean, lambang: HTMLImageElement | null, alamat: string) {
  if (k.gambar) {
    k.ctx.fillStyle = WARNA.latar
    k.ctx.fillRect(0, 0, LEBAR, 100_000)
  }
  kop(k, lambang)
  if (semua) {
    paragraf(k, "Persyaratan Layanan", { ukuran: 26, tebal: 700, jarak: 32 })
    paragraf(k, `${layanan.length} layanan pajak kendaraan di SAMSAT Kota Kupang. Bawa berkas sesuai layanan yang Anda perlukan.`, { ukuran: 13.5, warna: WARNA.redup, jarak: 20 })
    k.y += 22
  }
  layanan.forEach((l, i) => {
    if (semua && i > 0) {
      k.y += 12
      garisMendatar(k, k.y, WARNA.tinta, 2)
      k.y += 26
    }
    bagianLayanan(k, l, semua ? bertitik(l.nama) : `Persyaratan ${l.nama}`)
  })
  for (const t of tambahan) {
    k.y += 16
    garisMendatar(k, k.y, WARNA.tinta, 2)
    k.y += 22
    bagianTambahan(k, t)
  }
  kaki(k, [...layanan, ...tambahan], alamat)
}

async function muatLambang(): Promise<HTMLImageElement | null> {
  try {
    const img = new Image()
    img.src = "/assets/img/lambang-ntt-192.webp"
    await img.decode()
    return img
  } catch {
    return null
  }
}

/** Huruf portal (tema) setelah selesai dimuat, agar ukuran teks yang ditakar sama dengan yang dilukis */
async function hurufPortal(): Promise<string> {
  const huruf = getComputedStyle(document.body).fontFamily || "Arial, sans-serif"
  try {
    await Promise.all([document.fonts.load(`400 16px ${huruf}`), document.fonts.load(`700 16px ${huruf}`)])
    await document.fonts.ready
  } catch {
    // Huruf cadangan peramban tetap dipakai
  }
  return huruf
}

const namaBerkas = (judul: string) => `${judul.replace(/[\\/:*?"<>|]+/g, "-").replace(/\s+/g, " ").trim()} - SAMSAT Kota Kupang.png`

/** Buat gambar untuk satu layanan (slug) atau semua layanan ("semua") */
export async function buatGambar(pilihan: string): Promise<{ blob: Blob; nama: string }> {
  const [data, lambang, huruf] = await Promise.all([muatLayanan(PESAN_GAGAL), muatLambang(), hurufPortal()])
  const semua = pilihan === "semua"
  const layanan = data.filter((l) => l.jenis === "layanan" && (semua || l.slug === pilihan))
  const tambahan = data.filter((l) => l.jenis !== "layanan")
  if (!layanan.length) throw new Error(PESAN_GAGAL)
  const alamat = `${location.host}/layanan${semua ? "" : `#${layanan[0].slug}`}`

  const kanvas = document.createElement("canvas")
  const ctx = kanvas.getContext("2d")
  if (!ctx) throw new Error(PESAN_GAGAL)
  // Takar dulu tingginya, lalu lukis pada ukuran itu
  const takar: Kuas = { ctx, gambar: false, huruf, y: 0 }
  lukis(takar, layanan, tambahan, semua, lambang, alamat)
  const tinggi = Math.ceil(takar.y)
  const skala = Math.min(SKALA, Math.sqrt(LUAS_MAKS / (LEBAR * tinggi)))
  kanvas.width = Math.floor(LEBAR * skala)
  kanvas.height = Math.floor(tinggi * skala)
  ctx.scale(skala, skala)
  ctx.textBaseline = "alphabetic"
  lukis({ ctx, gambar: true, huruf, y: 0 }, layanan, tambahan, semua, lambang, alamat)

  const blob = await new Promise<Blob | null>((selesai) => kanvas.toBlob(selesai, "image/png"))
  if (!blob) throw new Error(PESAN_GAGAL)
  return { blob, nama: namaBerkas(semua ? "Persyaratan Layanan" : `Persyaratan ${layanan[0].nama}`) }
}

/** Tombol unduh: tidak aktif selama gambar dibuat, lalu berkasnya diunduh; galat tampil di [data-unduh-status] */
export async function unduhPersyaratan(tombol: HTMLButtonElement) {
  if (tombol.disabled) return
  const status = tombol.parentElement?.querySelector<HTMLElement>("[data-unduh-status]") ?? null
  const isiAwal = [...tombol.childNodes].map((n) => n.cloneNode(true))
  tombol.disabled = true
  tombol.setAttribute("aria-busy", "true")
  tombol.replaceChildren(isiAwal[0]?.nodeName.toLowerCase() === "svg" ? isiAwal[0].cloneNode(true) : "", "Menyiapkan…")
  if (status) status.textContent = ""
  try {
    const { blob, nama } = await buatGambar(tombol.dataset.unduhPersyaratan ?? "")
    const url = URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = nama
    document.body.append(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch {
    if (status) status.textContent = PESAN_GAGAL
  } finally {
    tombol.replaceChildren(...isiAwal)
    tombol.disabled = false
    tombol.removeAttribute("aria-busy")
  }
}
