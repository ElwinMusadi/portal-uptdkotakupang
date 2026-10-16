import * as React from "react"
import { IconAlertTriangle, IconChevronDown, IconFileUpload, IconLayoutColumns, IconSearch, IconTrash } from "@tabler/icons-react"
import { useQueryClient } from "@tanstack/react-query"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { PetaWilayah, type PetakWilayah } from "@/components/pemetaan/peta-wilayah"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { Switch } from "@/components/ui/switch"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { sesi } from "@/lib/auth"
import { angka, rupiah, tanggal, tanggalJam, ukuranBerkas } from "@/lib/format"
import {
  BATAS_SASARAN,
  imporBerkasPotensi,
  KECAMATAN,
  KOLOM_BAWAAN,
  KOLOM_SASARAN,
  KOLOM_WILAYAH,
  LABEL_KELOMPOK,
  LABEL_RODA,
  NAMA_BULAN,
  namaWilayah,
  periksaBerkasPotensi,
  SARINGAN_SASARAN,
  useDetailKendaraan,
  useHapusPotensi,
  useImporPotensi,
  useKolomPemetaan,
  useRingkasanPemetaan,
  useSasaranPenagihan,
  useSimpanKolomPemetaan,
  useTampilPotensi,
  type AngkaPotensi,
  type ImporPotensi,
  type KendaraanPotensi,
  type KolomPemetaan,
  type KolomSasaran,
  type KolomWilayah,
  type Kelompok,
  type PemeriksaanBerkas,
  type RingkasanPemetaan,
  type Roda,
  type SaringanSasaran,
} from "@/lib/pemetaan"

/*
 * Pemetaan PKB (D-052): potensi dan kepatuhan pajak kendaraan Kota Kupang
 * dari berkas Data Potensi BPAD. Ringkasan dengan peta batas kecamatan,
 * tabel per kecamatan dan kelurahan, serta daftar sasaran penagihan dengan
 * identitas wajib pajak (keputusan pemilik). Impor baru menggantikan data
 * sebelumnya.
 */

const persen = (bagian: number, utuh: number) => (utuh ? `${angka((bagian / utuh) * 100, 1)}%` : "—")

/** "Rp1,2 M", "Rp845 jt", atau rupiah utuh untuk angka kecil */
function rupiahSingkat(n: number): string {
  if (n >= 1e9) return `Rp${angka(n / 1e9, 1)} M`
  if (n >= 1e6) return `Rp${angka(n / 1e6, 0)} jt`
  return rupiah(n)
}

const belumBayar = (a: AngkaPotensi) => a.kelompok["jatuh-tempo"] + a.kelompok.terlambat

export default function HalamanPemetaan() {
  const impor = useImporPotensi()
  const hapus = useHapusPotensi()
  const tampil = useTampilPotensi()
  const konfirmasi = useKonfirmasi()
  const [unggah, setUnggah] = React.useState(false)
  const administrator = sesi().peran === "administrator"
  const data = impor.data?.impor ?? null

  const hapusData = async () => {
    if (!data) return
    const ya = await konfirmasi({
      judul: "Hapus seluruh data potensi PKB?",
      deskripsi: `${angka(data.jumlah)} kendaraan beserta identitas pemiliknya dihapus dari PINTU. Peta dan daftar sasaran kosong sampai berkas baru diimpor.`,
      label: "Hapus data",
      bahaya: true,
    })
    if (!ya) return
    try {
      await hapus.mutateAsync()
      toast.success("Data potensi PKB dihapus")
    } catch (e) {
      toast.error("Data gagal dihapus", { description: (e as Error).message })
    }
  }

  const ubahTampil = async (nyala: boolean) => {
    if (nyala) {
      const ya = await konfirmasi({
        judul: "Tampilkan pemetaan PKB di portal?",
        deskripsi:
          "Jumlah kendaraan, kepatuhan, dan rupiah per kecamatan dan kelurahan tampil untuk umum di halaman Pemetaan PKB, tanpa identitas wajib pajak. Angka yang menggambarkan kurang dari 3 kendaraan disamarkan. Pastikan datanya sudah diperiksa.",
        label: "Tampilkan",
      })
      if (!ya) return
    }
    try {
      await tampil.mutateAsync(nyala)
      toast.success(nyala ? "Pemetaan tampil di portal" : "Pemetaan disembunyikan dari portal")
    } catch (e) {
      toast.error("Tampilan di portal gagal diubah", { description: (e as Error).message })
    }
  }

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Potensi dan kepatuhan pajak kendaraan bermotor Kota Kupang dari berkas Data Potensi BPAD, dipetakan per kecamatan dan kelurahan, dengan daftar sasaran penagihan.">
        <div className="flex flex-wrap gap-2">
          <Button onClick={() => setUnggah(true)}>
            <IconFileUpload />
            Impor Data Potensi
          </Button>
          {administrator && data && (
            <Button variant="outline" disabled={hapus.isPending} onClick={() => void hapusData()}>
              <IconTrash />
              Hapus data
            </Button>
          )}
        </div>
      </PengantarHalaman>
      {impor.isLoading ? (
        <Skeleton className="h-64" />
      ) : impor.error ? (
        <p className="text-sm text-destructive">Data gagal dimuat: {impor.error.message}</p>
      ) : !data ? (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Belum ada data potensi</CardTitle>
            <CardDescription>Impor berkas Data Potensi dari sistem BPAD untuk menampilkan peta, ringkasan, dan sasaran penagihan.</CardDescription>
          </CardHeader>
        </Card>
      ) : (
        <>
          <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">
              Data penetapan {tanggal(data.tanggalPenetapan)} · {angka(data.jumlah)} kendaraan · diimpor {tanggalJam(data.selesai)}
              {data.diimporOleh && ` oleh ${data.diimporOleh}`}
            </p>
            {administrator ? (
              <div className="flex items-center gap-2">
                <Switch id="pemetaan-tampil" checked={data.tampil} disabled={tampil.isPending} onCheckedChange={(v) => void ubahTampil(v)} />
                <Label htmlFor="pemetaan-tampil" className="text-sm">
                  Tampil di portal
                </Label>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">{data.tampil ? "Tampil di portal" : "Belum tampil di portal"}</p>
            )}
          </div>
          <Tabs defaultValue="ringkasan" className="gap-4">
            <TabsList>
              <TabsTrigger value="ringkasan">Peta &amp; ringkasan</TabsTrigger>
              <TabsTrigger value="sasaran">Sasaran penagihan</TabsTrigger>
            </TabsList>
            <TabsContent value="ringkasan" className="grid grid-cols-[minmax(0,1fr)] gap-4">
              <TabRingkasan />
            </TabsContent>
            <TabsContent value="sasaran" className="grid grid-cols-[minmax(0,1fr)] gap-4">
              <TabSasaran />
            </TabsContent>
          </Tabs>
        </>
      )}
      {unggah && <SheetImpor sekarang={data} tutup={() => setUnggah(false)} />}
    </Halaman>
  )
}

/* ---------- Peta dan ringkasan ---------- */

type Metrik = "menunggak" | "belum" | "tagihan"

const METRIK: Record<Metrik, { label: string; nilai: (a: AngkaPotensi) => number; teks: (a: AngkaPotensi) => string }> = {
  menunggak: { label: "Menunggak ≥ 1 tahun", nilai: (a) => (a.kendaraan ? a.kelompok.menunggak / a.kendaraan : 0), teks: (a) => persen(a.kelompok.menunggak, a.kendaraan) },
  belum: { label: "Belum taat", nilai: (a) => (a.kendaraan ? (a.kendaraan - a.kelompok.taat) / a.kendaraan : 0), teks: (a) => persen(a.kendaraan - a.kelompok.taat, a.kendaraan) },
  tagihan: { label: "Tagihan", nilai: (a) => a.tagihan, teks: (a) => rupiahSingkat(a.tagihan) },
}

function TabRingkasan() {
  const [bulan, setBulan] = React.useState<number | null>(null)
  const [metrik, setMetrik] = React.useState<Metrik>("menunggak")
  const [kecamatan, setKecamatan] = React.useState<string | null>(null)
  const ringkasan = useRingkasanPemetaan(bulan)
  const r = ringkasan.data
  const { kolom, atur } = useKolom()

  if (ringkasan.isLoading) return <Skeleton className="h-96" />
  if (ringkasan.error || !r?.jumlah || !r.kecamatan) return <p className="text-sm text-destructive">Ringkasan gagal dimuat: {ringkasan.error?.message}</p>

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
      {(r.bulan?.length ?? 0) > 1 ? (
        <div className="flex flex-wrap items-center gap-2">
          <Label htmlFor="pemetaan-bulan" className="text-sm">
            Bulan jatuh tempo
          </Label>
          <Select value={bulan ? String(bulan) : "semua"} onValueChange={(v) => setBulan(v === "semua" ? null : Number(v))}>
            <SelectTrigger id="pemetaan-bulan" className="w-48">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">Semua bulan</SelectItem>
              {r.bulan?.map((b) => (
                <SelectItem key={b.bulan} value={String(b.bulan)}>
                  {NAMA_BULAN[b.bulan - 1]} ({angka(b.kendaraan)})
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      ) : (
        r.bulan?.[0] && <p className="text-sm text-muted-foreground">Kendaraan yang jatuh tempo bulan {NAMA_BULAN[r.bulan[0].bulan - 1]}.</p>
      )}

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
          {atur && (
            <CardAction>
              <AturKolom
                katalog={KOLOM_WILAYAH}
                terpilih={kolom.wilayah}
                keterangan="Berlaku untuk tabel kecamatan dan kelurahan, di dashboard dan portal."
                ubah={(wilayah) => atur({ ...kolom, wilayah })}
              />
            </CardAction>
          )}
        </CardHeader>
        <CardContent>
          <TabelWilayah
            kolom={kolom.wilayah}
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
            kolom={kolom.wilayah}
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
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Hasil kunjungan lapangan</CardTitle>
            <CardDescription>Kunjungan door to door petugas yang tercatat di berkas.</CardDescription>
          </CardHeader>
          <CardContent>
            {(r.kunjungan ?? []).length === 0 ? (
              <p className="text-sm text-muted-foreground">Tidak ada kunjungan yang tercatat.</p>
            ) : (
              <DaftarBatang baris={(r.kunjungan ?? []).slice(0, 10).map((k) => ({ label: k.hasil, nilai: k.kendaraan }))} />
            )}
          </CardContent>
        </Card>
      </div>
    </>
  )
}

function Angka({ label, nilai, keterangan }: { label: string; nilai: string; keterangan?: string }) {
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

function TabelWilayah({
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
            <TableHead>Wilayah</TableHead>
            {kolom.map((k) => (
              <TableHead key={k} className="text-right">
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

function DaftarBatang({ baris }: { baris: { label: string; nilai: number; keterangan?: string }[] }) {
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

/* ---------- Sasaran penagihan ---------- */

const SEMUA = "semua"

function TabSasaran() {
  const [isian, setIsian] = React.useState<SaringanSasaran>(SARINGAN_SASARAN)
  const [saringan, setSaringan] = React.useState<SaringanSasaran>(SARINGAN_SASARAN)
  const [pilih, setPilih] = React.useState<number | null>(null)
  const sasaran = useSasaranPenagihan(saringan)
  const ringkasan = useRingkasanPemetaan(null)
  const { kolom, atur } = useKolom()
  const ubah = <K extends keyof SaringanSasaran>(k: K, v: SaringanSasaran[K]) => setIsian((s) => ({ ...s, [k]: v, ...(k === "kecamatan" ? { kelurahan: "" } : {}) }))
  const kelurahan = pilihanKelurahan(ringkasan.data, isian.kecamatan)

  return (
    <>
      <Alert>
        <IconAlertTriangle />
        <AlertTitle>Berisi data pribadi wajib pajak</AlertTitle>
        <AlertDescription>Nama, alamat, telepon, dan KTP hanya untuk keperluan penagihan. Jangan disalin, difoto, atau dibagikan di luar tugas.</AlertDescription>
      </Alert>
      <Card>
        <CardContent>
          <form
            className="grid gap-4"
            onSubmit={(e) => {
              e.preventDefault()
              setSaringan(isian)
            }}
          >
            <div className="grid gap-1.5">
              <Label htmlFor="sasaran-q">Nomor polisi atau nama pemilik</Label>
              <Input id="sasaran-q" value={isian.q} onChange={(e) => ubah("q", e.target.value)} placeholder="Sebagian nomor polisi atau nama" autoComplete="off" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
              <PilihSaringan id="sasaran-kecamatan" label="Kecamatan" nilai={isian.kecamatan} ubah={(v) => ubah("kecamatan", v)} semua="Semua kecamatan"
                pilihan={[...KECAMATAN.map((k) => ({ nilai: k.kunci, label: k.nama })), { nilai: "tanpa", label: "Tanpa kelurahan" }]} />
              <PilihSaringan id="sasaran-kelurahan" label="Kelurahan" nilai={isian.kelurahan} ubah={(v) => ubah("kelurahan", v)} semua="Semua kelurahan"
                pilihan={kelurahan.map((l) => ({ nilai: l, label: namaWilayah(l) }))} />
              <PilihSaringan id="sasaran-kelompok" label="Kepatuhan" nilai={isian.kelompok} ubah={(v) => ubah("kelompok", v as Kelompok | "")} semua="Semua status"
                pilihan={(Object.keys(LABEL_KELOMPOK) as Kelompok[]).map((k) => ({ nilai: k, label: LABEL_KELOMPOK[k] }))} />
              <PilihSaringan id="sasaran-tahun" label="Lama tunggakan" nilai={isian.tahun} ubah={(v) => ubah("tahun", v)} semua="Semua"
                pilihan={[0, 1, 2, 3, 4].map((t) => ({ nilai: String(t), label: t === 0 ? "Tidak menunggak" : `${t} tahun` }))} />
              <PilihSaringan id="sasaran-roda" label="Roda" nilai={isian.roda} ubah={(v) => ubah("roda", v as Roda | "")} semua="Semua"
                pilihan={(["r2", "r3", "r4"] as Roda[]).map((r) => ({ nilai: r, label: LABEL_RODA[r] }))} />
              <PilihSaringan id="sasaran-bulan" label="Bulan jatuh tempo" nilai={isian.bulan} ubah={(v) => ubah("bulan", v)} semua="Semua bulan"
                pilihan={(ringkasan.data?.bulan ?? []).map((b) => ({ nilai: String(b.bulan), label: NAMA_BULAN[b.bulan - 1] }))} />
            </div>
            <div className="flex flex-wrap gap-2">
              <Button type="submit">
                <IconSearch />
                Cari
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setIsian(SARINGAN_SASARAN)
                  setSaringan(SARINGAN_SASARAN)
                }}
              >
                Atur ulang
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle className="text-base">
            Kendaraan {sasaran.data && <span className="text-muted-foreground">({angka(sasaran.data.jumlah)})</span>}
          </CardTitle>
          {atur && (
            <CardAction>
              <AturKolom
                katalog={KOLOM_SASARAN}
                terpilih={kolom.sasaran}
                keterangan="Hanya untuk daftar ini di dashboard; nomor polisi selalu tampil."
                ubah={(sasaran) => atur({ ...kolom, sasaran })}
              />
            </CardAction>
          )}
          <CardDescription>
            {sasaran.data && sasaran.data.jumlah > BATAS_SASARAN
              ? `Menampilkan ${BATAS_SASARAN} kendaraan dengan tagihan terbesar. Persempit pencarian untuk melihat yang lain.`
              : "Tagihan terbesar dulu. Pilih kendaraan untuk melihat semua datanya."}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {sasaran.isLoading ? (
            <Skeleton className="h-48" />
          ) : sasaran.error ? (
            <p className="text-sm text-destructive">Daftar gagal dimuat: {sasaran.error.message}</p>
          ) : !sasaran.data?.kendaraan.length ? (
            <p className="text-sm text-muted-foreground">Tidak ada kendaraan yang cocok.</p>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nomor polisi</TableHead>
                    {kolom.sasaran.map((k) => (
                      <TableHead key={k} className={KANAN_SASARAN.includes(k) ? "text-right" : undefined}>
                        {KOLOM_SASARAN[k]}
                      </TableHead>
                    ))}
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {sasaran.data.kendaraan.map((k) => (
                    <TableRow key={k.id}>
                      <TableCell className="whitespace-nowrap">
                        <button type="button" className="font-mono font-semibold underline-offset-4 hover:underline" onClick={() => setPilih(k.id)}>
                          {k.nopol}
                        </button>
                      </TableCell>
                      {kolom.sasaran.map((kol) => (
                        <SelSasaran key={kol} kolom={kol} kendaraan={k} />
                      ))}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
      <SheetKendaraan id={pilih} tutup={() => setPilih(null)} />
    </>
  )
}

/* ---------- Kolom tabel (D-052) ---------- */

/** Kolom tabel pemetaan dari server, dan cara Administrator menyimpannya (null untuk pegawai lain) */
function useKolom(): { kolom: KolomPemetaan; atur: ((kolom: KolomPemetaan) => void) | null } {
  const data = useKolomPemetaan()
  const simpan = useSimpanKolomPemetaan()
  const atur = async (kolom: KolomPemetaan) => {
    try {
      await simpan.mutateAsync(kolom)
      toast.success("Kolom tabel disimpan")
    } catch (e) {
      toast.error("Kolom tabel gagal disimpan", { description: (e as Error).message })
    }
  }
  return { kolom: data.data ?? KOLOM_BAWAAN, atur: sesi().peran === "administrator" ? (kolom) => void atur(kolom) : null }
}

/** Pilihan kolom yang tampil; sedikitnya satu kolom tetap terpilih */
function AturKolom<K extends string>({ katalog, terpilih, keterangan, ubah }: { katalog: Record<K, string>; terpilih: K[]; keterangan: string; ubah: (kolom: K[]) => void }) {
  const semua = Object.keys(katalog) as K[]
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm">
          <IconLayoutColumns />
          Atur kolom
          <IconChevronDown />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-64">
        <DropdownMenuLabel className="text-xs font-normal text-muted-foreground">{keterangan}</DropdownMenuLabel>
        <DropdownMenuSeparator />
        {semua.map((k) => (
          <DropdownMenuCheckboxItem
            key={k}
            checked={terpilih.includes(k)}
            disabled={terpilih.length === 1 && terpilih.includes(k)}
            onCheckedChange={(nyala) => ubah(nyala ? semua.filter((x) => x === k || terpilih.includes(x)) : terpilih.filter((x) => x !== k))}
            onSelect={(e) => e.preventDefault()}
          >
            {katalog[k]}
          </DropdownMenuCheckboxItem>
        ))}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

/** Kolom sasaran penagihan yang berisi rupiah, rata kanan */
const KANAN_SASARAN: KolomSasaran[] = ["pokokPajak", "tunggakan", "tagihan"]

function SelSasaran({ kolom, kendaraan: k }: { kolom: KolomSasaran; kendaraan: KendaraanPotensi }) {
  switch (kolom) {
    case "pemilik":
      return (
        <TableCell className="min-w-56">
          <span className="block font-medium">{k.nama}</span>
          <span className="block text-xs text-muted-foreground">
            {k.alamat}
            {(k.rt || k.rw) && ` RT ${k.rt || "-"}/RW ${k.rw || "-"}`}, {namaWilayah(k.kelurahan)}
          </span>
        </TableCell>
      )
    case "telepon":
      return <TableCell className="font-mono whitespace-nowrap">{k.telepon || "—"}</TableCell>
    case "kendaraan":
      return (
        <TableCell className="whitespace-nowrap">
          {k.merk} {k.jenis.toLowerCase()}
          {k.tahunBuat && <span className="block text-xs text-muted-foreground">{k.tahunBuat}</span>}
        </TableCell>
      )
    case "kecamatan":
      return <TableCell className="whitespace-nowrap">{KECAMATAN.find((x) => x.kunci === k.kecamatan)?.nama ?? "Tanpa kelurahan"}</TableCell>
    case "kepatuhan":
      return (
        <TableCell className="min-w-40">
          <span className="block">{k.kepatuhan}</span>
          {k.sdNotice && <span className="block text-xs text-muted-foreground">Berlaku s.d. {tanggal(k.sdNotice)}</span>}
        </TableCell>
      )
    case "lamaTunggakan":
      return <TableCell className="whitespace-nowrap">{k.tahunTunggak ? `${k.tahunTunggak} tahun` : "—"}</TableCell>
    case "pokokPajak":
      return <TableCell className="text-right whitespace-nowrap tabular-nums">{rupiah(k.pokokPajak)}</TableCell>
    case "tunggakan":
      return <TableCell className="text-right whitespace-nowrap tabular-nums">{k.pokokTunggak + k.dendaTunggak ? rupiah(k.pokokTunggak + k.dendaTunggak) : "—"}</TableCell>
    case "tagihan":
      return <TableCell className="text-right whitespace-nowrap tabular-nums">{k.tagihan ? rupiah(k.tagihan) : "—"}</TableCell>
    case "kunjungan":
      return <TableCell className="min-w-40">{k.hasilKunjungan || "—"}</TableCell>
  }
}

/** Kelurahan yang ada di data, untuk kecamatan yang dipilih atau semuanya */
function pilihanKelurahan(r: RingkasanPemetaan | undefined, kecamatan: string): string[] {
  const kunci = kecamatan === "tanpa" ? "" : kecamatan
  return (r?.kecamatan ?? [])
    .filter((k) => !kecamatan || k.kunci === kunci)
    .flatMap((k) => k.kelurahan.map((l) => l.nama))
    .filter(Boolean)
    .sort()
}

function PilihSaringan({
  id,
  label,
  nilai,
  ubah,
  semua,
  pilihan,
}: {
  id: string
  label: string
  nilai: string
  ubah: (v: string) => void
  semua: string
  pilihan: { nilai: string; label: string }[]
}) {
  return (
    <div className="grid gap-1.5">
      <Label htmlFor={id}>{label}</Label>
      <Select value={nilai || SEMUA} onValueChange={(v) => ubah(v === SEMUA ? "" : v)}>
        <SelectTrigger id={id} className="w-full">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={SEMUA}>{semua}</SelectItem>
          {pilihan.map((p) => (
            <SelectItem key={p.nilai} value={p.nilai}>
              {p.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  )
}

function SheetKendaraan({ id, tutup }: { id: number | null; tutup: () => void }) {
  const detail = useDetailKendaraan(id)
  const k = detail.data
  const bagian: [string, [string, React.ReactNode][]][] = k
    ? [
        [
          "Pemilik",
          [
            ["Nama", k.nama],
            ["KTP", k.ktp || "—"],
            ["Telepon", k.telepon || "—"],
            ["Alamat", `${k.alamat}${k.rt || k.rw ? ` RT ${k.rt || "-"}/RW ${k.rw || "-"}` : ""}`],
            ["Kelurahan", namaWilayah(k.kelurahan)],
            ["Kecamatan", KECAMATAN.find((x) => x.kunci === k.kecamatan)?.nama ?? "—"],
            ["Kecamatan di berkas", k.kecamatanBerkas || "—"],
          ],
        ],
        [
          "Kendaraan",
          [
            ["Nomor polisi", k.nopol],
            ["Jenis", `${k.jenis || "—"}${k.roda ? ` (${LABEL_RODA[k.roda].toLowerCase()})` : ""}`],
            ["Merk dan tipe", `${k.merk} ${k.tipe}`],
            ["Tahun buat", k.tahunBuat ?? "—"],
            ["Warna", `${k.warnaKendaraan} · plat ${k.warnaPlat.toLowerCase()}`],
            ["BBM · CC", `${k.bbm} · ${k.cyl ?? "—"}`],
            ["Nomor mesin", k.mesin],
            ["Nomor rangka", k.rangka],
            ["Penggunaan", k.penggunaan],
            ["Rubah bentuk", k.rubahBentuk],
            ["Kode Jasa Raharja", k.kodeJr || "—"],
          ],
        ],
        [
          "Pajak",
          [
            ["Kepatuhan", k.kepatuhan],
            ["Peringatan", k.peringatan || "—"],
            ["Berlaku s.d. (notice)", tanggal(k.sdNotice)],
            ["STNK s.d.", tanggal(k.sdStnk)],
            ["Tanggal penetapan", tanggal(k.tanggalPenetapan)],
            ["Tahun tunggakan", `${k.tahunTunggak} (awal ${k.tahunTunggakAwal})`],
            ["Pokok pajak", rupiah(k.pokokPajak)],
            ["Denda berjalan", rupiah(k.dendaBerjalan)],
            ["Pokok tunggakan", rupiah(k.pokokTunggak)],
            ["Denda tunggakan", rupiah(k.dendaTunggak)],
            ["Tagihan", rupiah(k.tagihan)],
            ["NJKB · NJUB", `${rupiah(k.njkb)} · ${rupiah(k.njub)}`],
            ["Bobot", k.bobot ?? "—"],
          ],
        ],
        [
          "Kunjungan lapangan",
          [
            ["Hasil", k.hasilKunjungan || "—"],
            ["Kegiatan", k.kegiatanKunjungan || "—"],
            ["Petugas", k.petugasKunjungan || "—"],
          ],
        ],
      ]
    : []

  return (
    <Sheet open={id !== null} onOpenChange={(buka) => !buka && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>{k ? `${k.nopol} · ${k.nama}` : "Data kendaraan"}</SheetTitle>
          <SheetDescription>Data pribadi: hanya untuk penagihan.</SheetDescription>
        </SheetHeader>
        <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
          {detail.isLoading ? (
            <Skeleton className="h-64" />
          ) : detail.error || !k ? (
            <p className="text-sm text-destructive">Data gagal dimuat: {detail.error?.message}</p>
          ) : (
            bagian.map(([judul, isi]) => (
              <section key={judul} className="grid gap-2">
                <h3 className="text-sm font-semibold">{judul}</h3>
                <dl className="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)] gap-x-4 gap-y-1.5 text-sm">
                  {isi.map(([label, nilai]) => (
                    <React.Fragment key={label}>
                      <dt className="text-muted-foreground">{label}</dt>
                      <dd className="break-words">{nilai}</dd>
                    </React.Fragment>
                  ))}
                </dl>
              </section>
            ))
          )}
        </div>
      </SheetContent>
    </Sheet>
  )
}

/* ---------- Impor ---------- */

type LangkahImpor = { tahap: "pilih" } | { tahap: "memeriksa" } | { tahap: "siap"; hasil: PemeriksaanBerkas } | { tahap: "mengimpor"; diterima: number; jumlah: number }

function SheetImpor({ sekarang, tutup }: { sekarang: ImporPotensi | null; tutup: () => void }) {
  const qc = useQueryClient()
  const [berkas, setBerkas] = React.useState<File | null>(null)
  const [langkah, setLangkah] = React.useState<LangkahImpor>({ tahap: "pilih" })
  const [galat, setGalat] = React.useState<string | null>(null)
  const kendali = React.useRef<AbortController | null>(null)
  const sibuk = langkah.tahap === "memeriksa" || langkah.tahap === "mengimpor"

  React.useEffect(() => () => kendali.current?.abort(), [])

  const periksa = async (b: File) => {
    setBerkas(b)
    setGalat(null)
    setLangkah({ tahap: "memeriksa" })
    kendali.current = new AbortController()
    try {
      const hasil = await periksaBerkasPotensi(b, kendali.current.signal)
      if (!hasil.kolomCocok) throw new Error("Berkas ini bukan Data Potensi dari sistem BPAD: susunan kolomnya tidak dikenali.")
      if (hasil.jumlah === 0) throw new Error("Berkas tidak berisi baris kendaraan.")
      if (hasil.barisRusak > 0) throw new Error(`${angka(hasil.barisRusak)} baris tidak berisi 43 kolom. Unduh ulang berkas dari sistem BPAD.`)
      setLangkah({ tahap: "siap", hasil })
    } catch (e) {
      setLangkah({ tahap: "pilih" })
      if ((e as Error).name !== "AbortError") setGalat((e as Error).message)
    }
  }

  const impor = async () => {
    if (!berkas || langkah.tahap !== "siap") return
    const jumlah = langkah.hasil.jumlah
    setGalat(null)
    setLangkah({ tahap: "mengimpor", diterima: 0, jumlah })
    kendali.current = new AbortController()
    try {
      const hasil = await imporBerkasPotensi(berkas, jumlah, (diterima) => setLangkah({ tahap: "mengimpor", diterima, jumlah }), kendali.current.signal)
      await qc.invalidateQueries({ queryKey: ["pemetaan"] })
      toast.success(`${angka(hasil.jumlah)} kendaraan diimpor`, { description: "Data potensi sebelumnya sudah digantikan." })
      tutup()
    } catch (e) {
      setLangkah({ tahap: "siap", hasil: langkah.hasil })
      setGalat((e as Error).name === "AbortError" ? "Impor dibatalkan. Data sebelumnya tetap ditampilkan." : (e as Error).message)
    }
  }

  return (
    <Sheet open onOpenChange={(buka) => !buka && !sibuk && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>Impor Data Potensi</SheetTitle>
          <SheetDescription>
            Berkas Data Potensi dari sistem BPAD (.xls). Berkas dibaca di peramban lalu dikirim per 1.000 baris, jadi berkas besar pun bisa diimpor. Impor yang selesai menggantikan data sebelumnya.
          </SheetDescription>
        </SheetHeader>
        <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
          <div className="grid gap-2">
            <Label htmlFor="potensi-berkas">Berkas</Label>
            <Input
              id="potensi-berkas"
              type="file"
              accept=".xls,.htm,.html"
              disabled={sibuk}
              onChange={(e) => {
                const b = e.target.files?.[0]
                if (b) void periksa(b)
              }}
            />
            {berkas && (
              <p className="text-xs text-muted-foreground">
                {berkas.name} · {ukuranBerkas(berkas.size)}
              </p>
            )}
          </div>
          {langkah.tahap === "memeriksa" && <p className="text-sm text-muted-foreground">Memeriksa berkas…</p>}
          {langkah.tahap === "siap" && (
            <div className="grid gap-3 rounded-lg border p-3 text-sm">
              <p>
                <span className="font-semibold">{angka(langkah.hasil.jumlah)} kendaraan</span>, tanggal penetapan{" "}
                {langkah.hasil.tanggalPenetapan.map((t) => tanggal(t)).join(", ") || "tidak tercantum"}.
              </p>
              {sekarang && (
                <p className="text-muted-foreground">
                  Menggantikan data sekarang: {angka(sekarang.jumlah)} kendaraan, penetapan {tanggal(sekarang.tanggalPenetapan)}.
                </p>
              )}
              <p className="text-muted-foreground">Semua kolom disimpan, termasuk identitas wajib pajak; KTP dan telepon disimpan terenkripsi.</p>
            </div>
          )}
          {langkah.tahap === "mengimpor" && (
            <div className="grid gap-2" aria-live="polite">
              <p className="text-sm">
                Mengirim {angka(langkah.diterima)} dari {angka(langkah.jumlah)} baris…
              </p>
              <div className="h-2 rounded-full bg-muted" role="progressbar" aria-valuemin={0} aria-valuemax={langkah.jumlah} aria-valuenow={langkah.diterima}>
                <div className="h-full rounded-full bg-brand transition-[width]" style={{ width: `${(langkah.diterima / langkah.jumlah) * 100}%` }} />
              </div>
              <p className="text-xs text-muted-foreground">Biarkan halaman ini terbuka sampai selesai.</p>
            </div>
          )}
          {galat && (
            <p className="text-sm text-destructive" role="alert">
              {galat}
            </p>
          )}
        </div>
        <SheetFooter className="flex-row justify-end gap-2 border-t">
          {sibuk ? (
            <Button variant="outline" onClick={() => kendali.current?.abort()}>
              Batalkan
            </Button>
          ) : (
            <Button variant="outline" onClick={tutup}>
              Tutup
            </Button>
          )}
          <Button disabled={langkah.tahap !== "siap"} onClick={() => void impor()}>
            <IconFileUpload />
            {langkah.tahap === "siap" ? `Impor ${angka(langkah.hasil.jumlah)} kendaraan` : "Impor"}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
