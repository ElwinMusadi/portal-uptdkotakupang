/*
 * Peta dan ringkasan Pemetaan PKB (D-052): angka kota, peta kecamatan,
 * tabel kecamatan dan kelurahan, serta daftar kepatuhan, lama tunggakan, dan
 * jenis kendaraan. Dipakai tab Peta & ringkasan di dashboard dan halaman
 * Ikhtisar (D-099); tanpa identitas wajib pajak.
 */
import * as React from "react"

import { PetaWilayah, type PetakWilayah } from "@/components/pemetaan/peta-wilayah"
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { angka, rupiah } from "@/lib/format"
import {
  KOLOM_WILAYAH,
  LABEL_KELOMPOK,
  LABEL_RODA,
  namaWilayah,
  type AngkaPotensi,
  type Kelompok,
  type KolomWilayah,
  type RingkasanPemetaan,
} from "@/lib/pemetaan"

export const persen = (bagian: number, utuh: number) => (utuh ? `${angka((bagian / utuh) * 100, 1)}%` : "—")

/** "Rp1,2 M", "Rp845 jt", atau rupiah utuh untuk angka kecil */
export function rupiahSingkat(n: number): string {
  if (n >= 1e9) return `Rp${angka(n / 1e9, 1)} M`
  if (n >= 1e6) return `Rp${angka(n / 1e6, 0)} jt`
  return rupiah(n)
}

/** Belum bayar kurang dari setahun: jatuh tempo hari ini dan terlambat */
export const belumBayar = (a: AngkaPotensi) => a.kelompok["jatuh-tempo"] + a.kelompok.terlambat

/** Ringkasan yang sudah termuat: angka kota dan kecamatannya selalu ada */
export type IsiPemetaan = Required<Pick<RingkasanPemetaan, "jumlah" | "kecamatan">> & Pick<RingkasanPemetaan, "tunggakan" | "jenis">

type Metrik = "menunggak" | "belum" | "tagihan"

const METRIK: Record<Metrik, { label: string; nilai: (a: AngkaPotensi) => number; teks: (a: AngkaPotensi) => string }> = {
  menunggak: { label: "Menunggak ≥ 1 tahun", nilai: (a) => (a.kendaraan ? a.kelompok.menunggak / a.kendaraan : 0), teks: (a) => persen(a.kelompok.menunggak, a.kendaraan) },
  belum: { label: "Belum taat", nilai: (a) => (a.kendaraan ? (a.kendaraan - a.kelompok.taat) / a.kendaraan : 0), teks: (a) => persen(a.kendaraan - a.kelompok.taat, a.kendaraan) },
  tagihan: { label: "Tagihan", nilai: (a) => a.tagihan, teks: (a) => rupiahSingkat(a.tagihan) },
}

/**
 * Angka kota, peta, tabel wilayah, dan daftar batang. aturKolom tampil di
 * kartu tabel kecamatan (pemilih kolom Administrator); kartuTambahan menjadi
 * kartu keempat di samping kepatuhan, lama tunggakan, dan jenis kendaraan.
 */
export function IsiRingkasanPemetaan({
  ringkasan: r,
  kolom,
  aturKolom,
  kartuTambahan,
}: {
  ringkasan: IsiPemetaan
  kolom: KolomWilayah[]
  aturKolom?: React.ReactNode
  kartuTambahan?: React.ReactNode
}) {
  const [metrik, setMetrik] = React.useState<Metrik>("menunggak")
  const [kecamatan, setKecamatan] = React.useState<string | null>(null)

  const j = r.jumlah
  const terpetakan = r.kecamatan.filter((k) => k.kunci !== "")
  const m = METRIK[metrik]
  // Warna dari nilai terendah sampai tertinggi di antara wilayah yang tampil, agar selisih kecil tetap terlihat
  const petakDari = (wilayah: (AngkaPotensi & { nama: string; kunci?: string })[]): PetakWilayah[] => {
    const nilai = wilayah.map((w) => m.nilai(w))
    const [terendah, tertinggi] = [Math.min(...nilai), Math.max(...nilai)]
    return wilayah.map((w) => ({
      kunci: w.kunci ?? w.nama,
      nama: w.nama,
      skala: tertinggi > terendah ? (m.nilai(w) - terendah) / (tertinggi - terendah) : 0.5,
      label: m.teks(w),
    }))
  }
  const petak = petakDari(terpetakan)
  const dipilih = r.kecamatan.find((k) => k.kunci === kecamatan) ?? null
  const petakKelurahan = dipilih ? petakDari(dipilih.kelurahan) : []
  const kelurahan = (dipilih ? dipilih.kelurahan.map((l) => ({ ...l, kecamatan: dipilih.nama })) : r.kecamatan.flatMap((k) => k.kelurahan.map((l) => ({ ...l, kecamatan: k.nama })))).sort(
    (a, b) => b.tagihan - a.tagihan
  )

  return (
    <>
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <Angka label="Objek pajak" nilai={angka(j.kendaraan)} keterangan={`R2 ${angka(j.roda.r2)} · R3 ${angka(j.roda.r3)} · R4+ ${angka(j.roda.r4)}`} />
        <Angka label="Taat" nilai={angka(j.kelompok.taat)} keterangan={persen(j.kelompok.taat, j.kendaraan)} />
        <Angka label="Belum bayar < 1 tahun" nilai={angka(belumBayar(j))} keterangan={persen(belumBayar(j), j.kendaraan)} />
        <Angka label="Menunggak ≥ 1 tahun" nilai={angka(j.kelompok.menunggak)} keterangan={persen(j.kelompok.menunggak, j.kendaraan)} />
        <Angka label="Potensi pokok PKB" nilai={rupiahSingkat(j.pokokPajak)} keterangan="Pokok pajak semua kendaraan" />
        <Angka label="Tagihan belum terbayar" nilai={rupiahSingkat(j.tagihan)} keterangan={`Tunggakan ${rupiahSingkat(j.pokokTunggak + j.dendaTunggak)}`} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Peta kecamatan</CardTitle>
          <CardDescription>Warna menunjukkan {m.label.toLowerCase()} per kecamatan.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4">
          <ToggleGroup type="single" variant="outline" value={metrik} onValueChange={(v) => v && setMetrik(v as Metrik)} aria-label="Ukuran pada peta" className="flex-wrap">
            {(Object.keys(METRIK) as Metrik[]).map((k) => (
              <ToggleGroupItem key={k} value={k} className="px-3">
                {METRIK[k].label}
              </ToggleGroupItem>
            ))}
          </ToggleGroup>
          <PetaWilayah petak={petak} kelurahan={petakKelurahan} dipilih={kecamatan} pilih={setKecamatan} judulSkala={m.label} />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Per kecamatan</CardTitle>
          <CardDescription>Kecamatan menurut daftar resmi kelurahan Kota Kupang, karena kolom kecamatan di berkas tidak selalu konsisten.</CardDescription>
          {aturKolom && <CardAction>{aturKolom}</CardAction>}
        </CardHeader>
        <CardContent>
          <TabelWilayah
            kolom={kolom}
            baris={r.kecamatan.map((k) => ({ kunci: k.kunci || "tanpa", nama: k.nama, angka: k }))}
            jumlah={j}
            dipilih={kecamatan ?? undefined}
            pilih={(k) => setKecamatan(k === kecamatan ? null : k)}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">{dipilih ? `Kelurahan di ${dipilih.nama}` : "Semua kelurahan"}</CardTitle>
          <CardDescription>Tagihan terbesar dulu.{dipilih && " Pilih kecamatan lain di peta atau tabel, atau pilih lagi untuk melihat semua."}</CardDescription>
        </CardHeader>
        <CardContent>
          <TabelWilayah
            kolom={kolom}
            baris={kelurahan.map((l) => ({ kunci: `${l.kecamatan}|${l.nama}`, nama: namaWilayah(l.nama), keterangan: dipilih ? undefined : l.kecamatan, angka: l }))}
          />
        </CardContent>
      </Card>

      <div className="grid grid-cols-[minmax(0,1fr)] gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Kepatuhan</CardTitle>
          </CardHeader>
          <CardContent>
            <DaftarBatang
              baris={(Object.keys(LABEL_KELOMPOK) as Kelompok[])
                .filter((k) => j.kelompok[k] > 0)
                .map((k) => ({ label: LABEL_KELOMPOK[k], nilai: j.kelompok[k], keterangan: persen(j.kelompok[k], j.kendaraan) }))}
            />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Lama tunggakan</CardTitle>
            <CardDescription>Tahun tunggakan menurut sistem BPAD; 4 tahun termasuk yang lebih lama.</CardDescription>
          </CardHeader>
          <CardContent>
            <DaftarBatang
              baris={(r.tunggakan ?? []).map((t) => ({
                label: t.tahun === 0 ? "Tidak menunggak" : `${t.tahun} tahun`,
                nilai: t.kendaraan,
                keterangan: t.tahun === 0 ? undefined : rupiahSingkat(t.pokokTunggak + t.dendaTunggak),
              }))}
            />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Jenis kendaraan</CardTitle>
          </CardHeader>
          <CardContent>
            <DaftarBatang
              baris={(r.jenis ?? []).slice(0, 10).map((x) => ({ label: x.jenis || "Jenis kosong", nilai: x.kendaraan, keterangan: x.roda ? LABEL_RODA[x.roda] : undefined }))}
            />
          </CardContent>
        </Card>
        {kartuTambahan}
      </div>
    </>
  )
}

export function Angka({ label, nilai, keterangan }: { label: string; nilai: string; keterangan?: string }) {
  return (
    <Card className="gap-1 py-4">
      <CardContent className="grid gap-1 px-4">
        <p className="text-xs text-muted-foreground">{label}</p>
        <p className="text-xl font-semibold tabular-nums">{nilai}</p>
        {keterangan && <p className="text-xs text-muted-foreground">{keterangan}</p>}
      </CardContent>
    </Card>
  )
}

/** Isi sel tabel wilayah per kolom */
const SEL_WILAYAH: Record<KolomWilayah, (a: AngkaPotensi) => string> = {
  kendaraan: (a) => angka(a.kendaraan),
  rodaDua: (a) => angka(a.roda.r2),
  rodaTiga: (a) => angka(a.roda.r3),
  rodaEmpat: (a) => angka(a.roda.r4),
  taat: (a) => angka(a.kelompok.taat),
  belumBayar: (a) => angka(belumBayar(a)),
  menunggak: (a) => angka(a.kelompok.menunggak),
  persenMenunggak: (a) => persen(a.kelompok.menunggak, a.kendaraan),
  potensi: (a) => rupiah(a.pokokPajak),
  tagihan: (a) => rupiah(a.tagihan),
  tunggakan: (a) => rupiah(a.pokokTunggak + a.dendaTunggak),
}

export function TabelWilayah({
  kolom,
  baris,
  jumlah,
  dipilih,
  pilih,
}: {
  kolom: KolomWilayah[]
  baris: { kunci: string; nama: string; keterangan?: string; angka: AngkaPotensi }[]
  jumlah?: AngkaPotensi
  dipilih?: string
  pilih?: (kunci: string) => void
}) {
  const sel = (a: AngkaPotensi) =>
    kolom.map((k) => (
      <TableCell key={k} className="text-right whitespace-nowrap tabular-nums">
        {SEL_WILAYAH[k](a)}
      </TableCell>
    ))
  return (
    <div className="overflow-x-auto">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="align-bottom">Wilayah</TableHead>
            {kolom.map((k) => (
              <TableHead key={k} className="text-right align-bottom whitespace-normal">
                {KOLOM_WILAYAH[k]}
              </TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {baris.map((b) => (
            <TableRow key={b.kunci} data-state={dipilih === b.kunci ? "selected" : undefined}>
              <TableCell className="whitespace-nowrap">
                {pilih ? (
                  <button type="button" className="font-medium underline-offset-4 hover:underline" onClick={() => pilih(b.kunci)}>
                    {b.nama}
                  </button>
                ) : (
                  <span className="font-medium">{b.nama}</span>
                )}
                {b.keterangan && <span className="block text-xs text-muted-foreground">{b.keterangan}</span>}
              </TableCell>
              {sel(b.angka)}
            </TableRow>
          ))}
        </TableBody>
        {jumlah && (
          <TableFooter>
            <TableRow>
              <TableCell>Kota Kupang</TableCell>
              {sel(jumlah)}
            </TableRow>
          </TableFooter>
        )}
      </Table>
    </div>
  )
}

export function DaftarBatang({ baris }: { baris: { label: string; nilai: number; keterangan?: string }[] }) {
  const tertinggi = Math.max(...baris.map((b) => b.nilai), 1)
  return (
    <ul className="grid gap-2.5" role="list">
      {baris.map((b) => (
        <li key={b.label} className="grid gap-1">
          <div className="flex items-baseline justify-between gap-3 text-sm">
            <span className="min-w-0 truncate">{b.label}</span>
            <span className="shrink-0 tabular-nums">
              {angka(b.nilai)}
              {b.keterangan && <span className="ml-1.5 text-xs text-muted-foreground">{b.keterangan}</span>}
            </span>
          </div>
          <div className="h-1.5 rounded-full bg-muted" aria-hidden="true">
            <div className="h-full rounded-full bg-brand" style={{ width: `${Math.max(2, (b.nilai / tertinggi) * 100)}%` }} />
          </div>
        </li>
      ))}
    </ul>
  )
}
