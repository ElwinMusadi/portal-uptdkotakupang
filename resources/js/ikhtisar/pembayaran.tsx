/*
 * Pembayaran PKB di halaman Ikhtisar (D-099): kepatuhan menurut Data Potensi
 * per kecamatan dan kelurahan, berdampingan dengan transaksi bayar PKB di
 * loket sejak tanggal Data Potensi sampai STS terakhir. Kedua sumber tidak
 * dikurangkan: angka STS adalah transaksi, bukan kendaraan unik.
 */
import * as React from "react"
import { IconChevronDown, IconChevronRight } from "@tabler/icons-react"

import { Angka, persen } from "@/components/pemetaan/ringkasan-wilayah"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { angka } from "@/lib/format"
import { namaWilayah } from "@/lib/pemetaan"
import { cn } from "@/lib/utils"

import { labelPeriode, labelTanggal } from "../portal/penerimaan/periode"
import type { AngkaPembayaran, Ikhtisar } from "./data"

type Pembayaran = NonNullable<Ikhtisar["pembayaran"]>

/** "per 16 Okt 2026", atau kosong bila Data Potensi tidak bertanggal */
const perTanggal = (p: Pembayaran) => (p.dataPotensi.tanggal ? ` per ${labelTanggal(p.dataPotensi.tanggal)}` : "")

export function IsiPembayaran({ pembayaran: p }: { pembayaran: Pembayaran }) {
  const [buka, setBuka] = React.useState<string | null>(null)
  const j = p.jumlah
  const sejak = p.dataPotensi.tanggal ? labelTanggal(p.dataPotensi.tanggal) : "tanggal Data Potensi"

  return (
    <>
      <div className="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <Angka label={`Kendaraan Data Potensi${perTanggal(p)}`} nilai={angka(j.kendaraan)} keterangan={`Taat ${angka(j.taat)} (${persen(j.taat, j.kendaraan)})`} />
        <Angka
          label={`Belum taat${perTanggal(p)}`}
          nilai={angka(j.belumBayar + j.menunggak)}
          keterangan={`Belum bayar < 1 th ${angka(j.belumBayar)} · menunggak ≥ 1 th ${angka(j.menunggak)}`}
        />
        <Angka
          label={`Bayar PKB di loket sejak ${sejak}`}
          nilai={angka(j.bayar)}
          keterangan={p.sts ? `Transaksi STS ${labelPeriode(p.sts.dari, p.sts.sampai)}` : "Belum ada STS sejak tanggal Data Potensi"}
        />
        <Angka label="Kendaraan baru" nilai={angka(p.baru)} keterangan="Tidak ada di Data Potensi; tidak dihitung per kelurahan" />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Per kecamatan dan kelurahan</CardTitle>
          <CardDescription>Pilih kecamatan untuk melihat kelurahannya. Kecamatan menurut daftar resmi kelurahan Kota Kupang.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead rowSpan={2} className="align-bottom">
                    Wilayah
                  </TableHead>
                  <TableHead colSpan={4} className="border-l text-center whitespace-normal">
                    Data Potensi{perTanggal(p)}
                  </TableHead>
                  <TableHead className="border-l bg-primary/5 text-center whitespace-normal">STS{p.sts ? ` ${labelPeriode(p.sts.dari, p.sts.sampai)}` : ""}</TableHead>
                </TableRow>
                <TableRow>
                  <TableHead className="border-l text-right whitespace-normal">Kendaraan</TableHead>
                  <TableHead className="text-right whitespace-normal">Taat</TableHead>
                  <TableHead className="text-right whitespace-normal">Belum bayar &lt; 1 th</TableHead>
                  <TableHead className="text-right whitespace-normal">Menunggak ≥ 1 th</TableHead>
                  <TableHead className="border-l bg-primary/5 text-right whitespace-normal">Bayar PKB di loket</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {p.kecamatan.map((k) => {
                  const terbuka = buka === k.kunci
                  return (
                    <React.Fragment key={k.kunci}>
                      <TableRow data-state={terbuka ? "selected" : undefined}>
                        <TableCell className="whitespace-nowrap">
                          <button
                            type="button"
                            className="inline-flex items-center gap-1.5 font-medium underline-offset-4 hover:underline"
                            aria-expanded={terbuka}
                            onClick={() => setBuka(terbuka ? null : k.kunci)}
                          >
                            {terbuka ? <IconChevronDown className="size-4" aria-hidden="true" /> : <IconChevronRight className="size-4" aria-hidden="true" />}
                            {k.nama}
                          </button>
                        </TableCell>
                        <SelAngka angka={k} />
                      </TableRow>
                      {terbuka &&
                        k.kelurahan.map((l) => (
                          <TableRow key={l.nama} className="text-muted-foreground">
                            <TableCell className="pl-9 whitespace-nowrap">{namaWilayah(l.nama)}</TableCell>
                            <SelAngka angka={l} />
                          </TableRow>
                        ))}
                    </React.Fragment>
                  )
                })}
                {(p.lainnya.kendaraan > 0 || p.lainnya.bayar > 0) && (
                  <TableRow className="text-muted-foreground">
                    <TableCell className="whitespace-nowrap">Luar Kota Kupang atau kelurahan tak dikenal</TableCell>
                    <SelAngka angka={p.lainnya} />
                  </TableRow>
                )}
              </TableBody>
              <TableFooter>
                <TableRow>
                  <TableCell>Kota Kupang</TableCell>
                  <SelAngka angka={j} />
                </TableRow>
              </TableFooter>
            </Table>
          </div>
          {p.baru > 0 && <p className="mt-3 text-sm text-muted-foreground">Ditambah {angka(p.baru)} transaksi kendaraan baru yang tidak dihitung per kelurahan.</p>}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Cara menghitung</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 text-sm text-muted-foreground">
          <ul className="grid list-disc gap-1.5 pl-5">
            <li>
              Kolom Data Potensi: status taat, belum bayar, dan menunggak menurut berkas Data Potensi BPAD{perTanggal(p)}. Status itu tidak berubah oleh
              pembayaran sesudahnya.
            </li>
            <li>
              Kolom STS: transaksi di semua loket yang membayar PKB atau tunggakan sejak {sejak} sampai STS terakhir, tanpa kendaraan baru. Satu transaksi
              adalah satu pembayaran kendaraan; kendaraan yang sama bisa membayar dua kali, dan kendaraan yang jatuh temponya sesudah tanggal Data Potensi
              ikut terhitung. Karena itu kedua angka tidak dikurangkan satu dari yang lain.
            </li>
            <li>
              Kelurahan dicocokkan dari nama kelurahan di STS ke daftar resmi Kota Kupang, dan kecamatannya mengikuti daftar itu. Transaksi kendaraan beralamat
              di luar kota (kecamatan STS di luar enam kecamatan Kota Kupang, walau nama desanya sama dengan kelurahan kota) dan nama yang tidak dikenal masuk
              baris Luar Kota Kupang atau kelurahan tak dikenal, sehingga totalnya tetap sesuai.
            </li>
          </ul>
          {p.jenisDaftar.length > 0 && (
            <p>
              Transaksi bayar PKB per jenis daftar:{" "}
              {p.jenisDaftar.map((d, i) => (
                <span key={d.nilai}>
                  {i > 0 && " · "}
                  {d.nilai} {angka(d.transaksi)}
                  {d.nilai.toUpperCase() === "BARU" && " (tidak dihitung per kelurahan)"}
                </span>
              ))}
              .
            </p>
          )}
        </CardContent>
      </Card>
    </>
  )
}

function SelAngka({ angka: a }: { angka: AngkaPembayaran }) {
  const sel = "text-right whitespace-nowrap tabular-nums"
  return (
    <>
      <TableCell className={cn(sel, "border-l")}>{angka(a.kendaraan)}</TableCell>
      <TableCell className={sel}>{angka(a.taat)}</TableCell>
      <TableCell className={sel}>{angka(a.belumBayar)}</TableCell>
      <TableCell className={sel}>{angka(a.menunggak)}</TableCell>
      <TableCell className={cn(sel, "border-l bg-primary/5 font-medium text-foreground")}>{angka(a.bayar)}</TableCell>
    </>
  )
}
