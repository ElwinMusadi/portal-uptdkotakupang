/*
 * Realisasi Target dalam susunan Form I (D-068, D-070): kartu capaian, tabel,
 * sumber angka, dan cetaknya. Dipakai halaman Realisasi Target di dashboard
 * dan halaman Ikhtisar (D-099).
 */
import * as React from "react"
import { IconTargetArrow } from "@tabler/icons-react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { angka, rupiah } from "@/lib/format"
import type { BarisRealisasi, RealisasiPenerimaan, TargetPenerimaan } from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { labelTanggal } from "../../../portal/penerimaan/periode"

/** Form I yang ditampilkan; Ikhtisar tidak memuat siapa dan kapan target terakhir disimpan */
export type LaporanFormI = Omit<RealisasiPenerimaan, "target"> & { target: Omit<TargetPenerimaan, "diperbarui" | "oleh"> | null }

/* ---------- Bulan ---------- */

export const NAMA_BULAN = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"]

export const kodeBulan = (tahun: number, bulan: number) => `${tahun}-${String(bulan).padStart(2, "0")}`
export const labelBulan = (tahun: number, bulan: number) => `${NAMA_BULAN[bulan - 1]} ${tahun}`

/** "7 Oktober 2026" */
export function tanggalPanjang(tanggal: string): string {
  const [tahun, bulan, hari] = tanggal.split("-").map(Number)
  return `${hari} ${NAMA_BULAN[bulan - 1]} ${tahun}`
}

/** Kolom bulan ini Form I: "Oktober 2026", atau "1–7 Oktober 2026" bila dibatasi sampai tanggal tertentu (D-070) */
export function rentangBulanIni(tahun: number, bulan: number, sampai: string | null): string {
  return sampai ? `1–${tanggalPanjang(sampai)}` : labelBulan(tahun, bulan)
}

/** "Januari–September 2026", atau "Januari 2026" bila saldo awal hanya sebulan */
export function rentangSaldo(tahun: number, sampaiBulan: number): string {
  return sampaiBulan === 1 ? labelBulan(tahun, 1) : `Januari–${labelBulan(tahun, sampaiBulan)}`
}

/* ---------- Angka Form I ---------- */

/** Rupiah tanpa "Rp" seperti di Form I; nol ditulis "-" */
const nilaiForm = (n: number | null) => (n === null ? "" : n === 0 ? "-" : angka(n))
const persenForm = (p: number | null) => (p === null ? "" : `${angka(p, 2)}%`)

/* ---------- Ringkasan ---------- */

export function RingkasanRealisasi({ laporan }: { laporan: LaporanFormI }) {
  const total = laporan.baris.find((b) => b.jenis === "total")
  const [pajak, denda] = laporan.baris.filter((b) => b.jenis === "jumlah")
  if (!total || !pajak || !denda) return null
  const sampaiBulan = `s.d. ${laporan.sampai ? tanggalPanjang(laporan.sampai) : labelBulan(laporan.tahun, laporan.bulan)}`
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <KartuCapaian judul={`Total realisasi ${sampaiBulan}`} baris={total} />
      <KartuCapaian judul="Pajak (PKB dan BBNKB)" baris={pajak} />
      <KartuCapaian judul="Denda pajak" baris={denda} />
      <Card className="@container/card gap-3">
        <CardHeader>
          <CardDescription>Realisasi {rentangBulanIni(laporan.tahun, laporan.bulan, laporan.sampai)}</CardDescription>
          <CardTitle className="text-xl tabular-nums @[250px]/card:text-2xl">{rupiah(total.ini)}</CardTitle>
        </CardHeader>
        <CardFooter className="text-sm text-muted-foreground">
          {laporan.loket.length === 0
            ? "Belum ada berkas STS bulan ini."
            : `${angka(laporan.loket.length)} loket, transaksi sampai ${labelTanggal(laporan.sampaiTanggal!)}.`}
        </CardFooter>
      </Card>
    </div>
  )
}

export function KartuCapaian({ judul, baris }: { judul: string; baris: BarisRealisasi }) {
  const persen = baris.persen
  return (
    <Card className="@container/card gap-3">
      <CardHeader>
        <CardDescription>{judul}</CardDescription>
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <CardTitle className="text-xl tabular-nums @[250px]/card:text-2xl">{rupiah(baris.sampaiIni)}</CardTitle>
          {persen !== null && <Badge variant="outline">{angka(persen, 2)}% dari target</Badge>}
        </div>
      </CardHeader>
      <CardFooter className="grid gap-2 text-sm text-muted-foreground">
        {persen !== null && (
          <div className="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
            <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(persen, 100)}%` }} />
          </div>
        )}
        <span>{baris.target ? `Target ${rupiah(baris.target)}` : "Target belum diisi"}</span>
      </CardFooter>
    </Card>
  )
}

/* ---------- Form I ---------- */

export function TabelFormI({ laporan, ref }: { laporan: LaporanFormI; ref: React.Ref<HTMLTableElement> }) {
  const sel = "border border-border px-2 py-1.5"
  return (
    <div className="overflow-x-auto">
      <table ref={ref} className="w-full min-w-[50rem] border-collapse text-sm tabular-nums">
        <caption className="sr-only">
          Realisasi penerimaan gabungan {rentangBulanIni(laporan.tahun, laporan.bulan, laporan.sampai)} terhadap target
        </caption>
        <thead className="bg-muted/50 text-xs font-semibold">
          <tr>
            <th rowSpan={2} scope="col" className={cn(sel, "w-12")}>
              NO.
            </th>
            <th rowSpan={2} scope="col" className={sel}>
              JENIS PENERIMAAN
            </th>
            <th rowSpan={2} scope="col" className={sel}>
              TARGET (Rp)
            </th>
            <th colSpan={3} scope="colgroup" className={sel}>
              REALISASI PENERIMAAN GABUNGAN
            </th>
            <th rowSpan={2} scope="col" className={cn(sel, "w-20")}>
              PCT
            </th>
          </tr>
          <tr>
            <th scope="col" className={sel}>
              S/D BULAN LALU (Rp)
            </th>
            <th scope="col" className={sel}>
              BULAN INI (Rp)
            </th>
            <th scope="col" className={sel}>
              S/D BULAN INI (Rp)
            </th>
          </tr>
          <tr data-nomor-kolom="">
            {[1, 2, 3, 4, 5, 6, 7].map((n) => (
              <th key={n} scope="col" className={cn(sel, "py-0.5 font-normal text-muted-foreground")}>
                {n}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {laporan.baris.map((b, i) => {
            const tebal = b.jenis === "jumlah" || b.jenis === "total"
            return (
              <tr
                key={i}
                data-jenis={b.jenis}
                className={cn(tebal && "font-semibold", b.jenis === "jumlah" && "bg-muted/30", b.jenis === "total" && "bg-muted/60")}
              >
                {tebal ? (
                  <th scope="row" colSpan={2} data-kolom="uraian" className={cn(sel, "text-center", b.jenis === "total" && "tracking-[0.3em]")}>
                    {b.uraian}
                  </th>
                ) : (
                  <>
                    {b.baris > 0 && (
                      <td rowSpan={b.baris} data-kolom="no" className={cn(sel, "text-center")}>
                        {b.no}
                      </td>
                    )}
                    <th scope="row" data-kolom="uraian" className={cn(sel, "text-left font-normal", b.jenis === "subjumlah" ? "pl-8" : "pl-3")}>
                      {b.uraian}
                    </th>
                  </>
                )}
                {b.baris > 0 && (
                  <td rowSpan={b.baris} className={cn(sel, "text-right whitespace-nowrap")}>
                    {laporan.target ? nilaiForm(b.target) : ""}
                  </td>
                )}
                <td className={cn(sel, "text-right whitespace-nowrap")}>{nilaiForm(b.lalu)}</td>
                <td className={cn(sel, "text-right whitespace-nowrap")}>{nilaiForm(b.ini)}</td>
                <td className={cn(sel, "text-right whitespace-nowrap")}>{nilaiForm(b.sampaiIni)}</td>
                <td className={cn(sel, "text-right whitespace-nowrap")}>{persenForm(b.persen)}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}

/** Sumber angka: saldo awal, bulan-bulan STS sebelumnya, dan loket bulan ini */
export function Sumber({ laporan }: { laporan: LaporanFormI }) {
  const saldo = laporan.target?.saldoAwal
  const bulanStsPertama = (saldo?.sampaiBulan ?? 0) + 1
  const adaStsSebelumnya = laporan.bulan > bulanStsPertama
  const stsSebelumnya =
    bulanStsPertama === laporan.bulan - 1
      ? labelBulan(laporan.tahun, bulanStsPertama)
      : `${NAMA_BULAN[bulanStsPertama - 1]}–${labelBulan(laporan.tahun, laporan.bulan - 1)}`

  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">Sumber angka</CardTitle>
        <CardDescription>
          {saldo
            ? `S/D bulan lalu memuat saldo awal ${rentangSaldo(laporan.tahun, saldo.sampaiBulan)} yang diisi manual${adaStsSebelumnya ? `, ditambah transaksi STS ${stsSebelumnya}` : ""}.`
            : laporan.bulan > 1
              ? `S/D bulan lalu dihitung dari transaksi STS ${stsSebelumnya}.`
              : "Januari tidak memiliki bulan lalu."}{" "}
          Bulan ini dihitung dari transaksi STS semua loket{laporan.sampai ? ` tanggal ${rentangBulanIni(laporan.tahun, laporan.bulan, laporan.sampai)}` : ""}.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {laporan.loket.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada berkas STS yang diimpor untuk {rentangBulanIni(laporan.tahun, laporan.bulan, laporan.sampai)}.</p>
        ) : (
          <ul className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3" role="list">
            {laporan.loket.map((l) => (
              <li key={l.nilai} className="grid gap-0.5 rounded-lg border px-3 py-2 text-sm">
                <span className="font-medium">{l.nama}</span>
                <span className="text-muted-foreground tabular-nums">
                  {angka(l.transaksi)} transaksi · {rupiah(l.jumlah)} · sampai {labelTanggal(l.sampaiTanggal)}
                </span>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}

/** Bulan yang tercatat dalam saldo awal; bila ubah diisi, ada tombol ke bulan pertama yang punya Form I */
export function TertutupSaldo({ laporan, ubah }: { laporan: LaporanFormI; ubah?: (b: string) => void }) {
  const sampai = laporan.target?.saldoAwal?.sampaiBulan ?? 0
  const pertama = sampai + 1
  return (
    <Card>
      <CardContent>
        <Empty>
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <IconTargetArrow />
            </EmptyMedia>
            <EmptyTitle>Bulan ini tercatat dalam saldo awal</EmptyTitle>
            <EmptyDescription>
              Realisasi {rentangSaldo(laporan.tahun, sampai)} diisi manual sebagai saldo awal, jadi tidak ada Form I per bulannya.
              {pertama <= 12 && ` Laporan per bulan tersedia mulai ${labelBulan(laporan.tahun, pertama)}.`}
            </EmptyDescription>
          </EmptyHeader>
          {ubah && pertama <= 12 && (
            <EmptyContent>
              <Button variant="outline" onClick={() => ubah(kodeBulan(laporan.tahun, pertama))}>
                Lihat {labelBulan(laporan.tahun, pertama)}
              </Button>
            </EmptyContent>
          )}
        </Empty>
      </CardContent>
    </Card>
  )
}

/* ---------- Cetak ---------- */

const JUDUL_FORM = [
  "REALISASI PENERIMAAN PAJAK DAN PENERIMAAN LAIN-LAIN YANG SAH",
  "UPTD. PENDAPATAN DAERAH WILAYAH KOTA KUPANG PADA",
  "BADAN PENDAPATAN DAN ASET DAERAH PROVINSI NUSA TENGGARA TIMUR",
]

const GAYA_CETAK = `
@page { size: A4 landscape; margin: 12mm; }
* { box-sizing: border-box; }
body { margin: 0; color: #000; font: 10pt Calibri, Carlito, Arial, sans-serif; }
.judul { margin: 0 0 14pt; text-align: center; font-weight: 700; line-height: 1.4; }
.atas { display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 2pt; }
table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
caption { display: none; }
th, td { border: 0.75pt solid #000; padding: 2pt 5pt; }
thead th { text-align: center; font-weight: 700; }
td { text-align: right; white-space: nowrap; }
td[data-kolom="no"] { text-align: center; }
tbody th { text-align: left; font-weight: 400; padding-left: 8pt; }
tr[data-jenis="subjumlah"] th { padding-left: 24pt; }
tr[data-jenis="jumlah"] th, tr[data-jenis="total"] th { text-align: center; font-weight: 700; padding-left: 5pt; }
tr[data-jenis="jumlah"] td, tr[data-jenis="total"] td { font-weight: 700; }
tr[data-jenis="total"] th { letter-spacing: 0.3em; }
`

/** Cetak Form I lewat bingkai tersembunyi: judul resmi di atas tabel yang sama dengan di layar, A4 mendatar */
export function cetakFormI(tabel: HTMLTableElement, laporan: LaporanFormI) {
  const judul = [
    ...JUDUL_FORM,
    `BULAN ${NAMA_BULAN[laporan.bulan - 1].toUpperCase()}`,
    `TAHUN ${laporan.tahun}`,
    ...(laporan.sampai ? [`KEADAAN S/D ${tanggalPanjang(laporan.sampai).toUpperCase()}`] : []),
  ]
  const bingkai = document.createElement("iframe")
  bingkai.setAttribute("aria-hidden", "true")
  bingkai.tabIndex = -1
  bingkai.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0"
  bingkai.srcdoc = `<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Realisasi Penerimaan ${kodeBulan(laporan.tahun, laporan.bulan)}</title><style>${GAYA_CETAK}</style></head><body><div class="judul">${judul.join("<br>")}</div><div class="atas"><span>GABUNGAN</span><span>Form. I</span></div>${tabel.outerHTML}</body></html>`
  bingkai.onload = () => {
    const jendela = bingkai.contentWindow
    if (!jendela) return
    jendela.addEventListener("afterprint", () => bingkai.remove())
    jendela.focus()
    jendela.print()
  }
  document.body.append(bingkai)
}
