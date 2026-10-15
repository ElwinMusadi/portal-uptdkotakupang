/*
 * Cetak tiket Antrean Online di printer thermal 80 mm (D-051): dua lembar,
 * untuk wajib pajak dan untuk ditempel pada berkas, keduanya dengan QR berisi
 * alamat halaman lacak. QR yang sama dipindai petugas di setiap tahap.
 *
 * Tiket dicetak dari iframe tersendiri, jadi ukuran halaman 80 mm tidak
 * memengaruhi cetakan dashboard lain. Setiap lembar satu halaman cetak, agar
 * printer dengan pemotong otomatis memotong di antaranya. Chrome atau Edge
 * yang dijalankan dengan --kiosk-printing langsung mencetak ke printer bawaan
 * tanpa dialog.
 */
import { renderSVG } from "uqr"

import type { TiketAntrean } from "./antrean"
import { ZONA_WAKTU } from "./format"

/** QR alamat halaman lacak sebagai SVG */
export function svgQr(teks: string): string {
  return renderSVG(teks, { ecc: "M", border: 1 })
}

const esc = (s: string) => s.replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c] ?? c)

const fmtTanggal = new Intl.DateTimeFormat("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric", timeZone: ZONA_WAKTU })
const fmtJam = new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", hourCycle: "h23", timeZone: ZONA_WAKTU })

/** "Kamis, 15 Oktober 2026 · 09.00 WITA" */
export function waktuTiket(iso: string | null): string {
  const d = iso ? new Date(iso) : new Date()
  return `${fmtTanggal.format(d)} · ${fmtJam.format(d).replace(":", ".")} WITA`
}

const GAYA = `
@page { size: 80mm 110mm; margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff; }
.tiket { width: 80mm; height: 110mm; padding: 4mm 5mm; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; gap: 1.6mm; text-align: center; overflow: hidden; break-after: page; }
.tiket:last-child { break-after: auto; }
.instansi { font-size: 8.5pt; line-height: 1.25; }
.lembar { font-size: 8pt; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; border: 1px solid #000; border-radius: 1mm; padding: 0.6mm 2mm; }
.nomor { font-size: 42pt; font-weight: 800; line-height: 1; letter-spacing: -0.01em; }
.layanan { font-size: 12pt; font-weight: 700; }
.nopol { font-size: 15pt; font-weight: 700; letter-spacing: 0.04em; }
.waktu { font-size: 8.5pt; }
.qr { width: 30mm; height: 30mm; }
.qr svg { display: block; width: 100%; height: 100%; }
.catatan { font-size: 8pt; line-height: 1.3; }
`

/** Dua lembar tiket sebagai dokumen HTML untuk dicetak */
export function htmlTiket(t: TiketAntrean): string {
  const qr = svgQr(t.urlLacak)
  const inti = `
    <p class="nomor">${esc(t.nomor)}</p>
    <p class="layanan">${esc(t.layanan.nama)}</p>
    <p class="nopol">${esc(t.nopol ?? "")}</p>
    <p class="waktu">${esc(waktuTiket(t.dicetak))}</p>
    <div class="qr">${qr}</div>`
  return `<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Tiket ${esc(t.nomor)}</title><style>${GAYA}</style></head><body>
  <section class="tiket">
    <p class="instansi">PINTU · Samsat Kota Kupang<br>UPTD Pendapatan Daerah Wilayah Kota Kupang</p>
    <p class="lembar">Lembar 1 · Wajib pajak</p>${inti}
    <p class="catatan">Pindai QR untuk melacak berkas Anda.<br>Simpan lembar ini sampai berkas diserahkan.</p>
  </section>
  <section class="tiket">
    <p class="lembar">Lembar 2 · Tempel pada berkas</p>${inti}
    <p class="catatan">Dipindai petugas di setiap tahap.</p>
  </section>
</body></html>`
}

/** Cetak dua lembar tiket lewat iframe tersendiri */
export function cetakTiket(t: TiketAntrean): void {
  const bingkai = document.createElement("iframe")
  bingkai.title = `Tiket ${t.nomor}`
  bingkai.setAttribute("aria-hidden", "true")
  bingkai.tabIndex = -1
  Object.assign(bingkai.style, { position: "fixed", right: "0", bottom: "0", width: "0", height: "0", border: "0" })
  bingkai.addEventListener(
    "load",
    () => {
      const jendela = bingkai.contentWindow
      if (!jendela) return
      jendela.addEventListener("afterprint", () => window.setTimeout(() => bingkai.remove(), 500), { once: true })
      jendela.focus()
      jendela.print()
    },
    { once: true }
  )
  // Bila peramban tidak pernah memicu afterprint, iframe tetap dibuang
  window.setTimeout(() => bingkai.remove(), 120_000)
  bingkai.srcdoc = htmlTiket(t)
  document.body.append(bingkai)
}
