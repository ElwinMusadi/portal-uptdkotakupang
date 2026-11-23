import * as React from "react"
import {
  IconAlertTriangle,
  IconCalendarEvent,
  IconDownload,
  IconFileSpreadsheet,
  IconInfoCircle,
  IconUpload,
} from "@tabler/icons-react"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris } from "@/components/data/row-actions"
import { NadaBadge } from "@/components/data/status-badge"
import { NavPenerimaan } from "@/components/penerimaan/nav-penerimaan"
import { RincianPenerimaan } from "@/components/penerimaan/rincian-penerimaan"
import { PanelSaringan, type PilihanSaringan } from "@/components/penerimaan/saringan-laporan"
import { GrafikHarian, GrafikRincian, PerLoket, persen, Ringkasan } from "@/components/penerimaan/ringkasan-laporan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import { Switch } from "@/components/ui/switch"
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { sesi } from "@/lib/auth"
import { angka, hariIniIso, rupiah, tanggal, tanggalJam, ukuranBerkas } from "@/lib/format"
import {
  csvRincian,
  KOMPONEN,
  LABEL_DIMENSI,
  labelNilai,
  MAKS_BERKAS,
  unduhTeks,
  useDaftarImpor,
  useHapusImpor,
  useTampilImpor,
  useLaporanPenerimaan,
  useLoketPenerimaan,
  usePratinjauImpor,
  useSimpanImpor,
  type BarisRincian,
  type Dimensi,
  type ImporPenerimaan,
  type JenisLoket,
  type LaporanPenerimaan,
  type PratinjauImpor,
  type Saringan,
} from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { galatPeriode, labelPeriode, periodeDari, type Periode } from "../../../portal/penerimaan/periode"

/* ---------- Halaman ---------- */

export default function HalamanPenerimaan() {
  const { data: daftarImpor, isLoading: memuatImpor } = useDaftarImpor()
  const [periodePilihan, setPeriode] = React.useState<Periode | null>(null)
  const [pilih, setPilih] = React.useState<PilihanSaringan>({})
  const [tunggakan, setTunggakan] = React.useState<Saringan["tunggakan"]>("")
  const [impor, setImpor] = React.useState(false)
  const panel = React.useRef<HTMLDivElement>(null)

  // Sebelum pengguna memilih, laporan menampilkan hari terakhir yang sudah diimpor
  const terakhir = React.useMemo(
    () => (daftarImpor ? daftarImpor.reduce((m, i) => (i.tanggalAkhir > m ? i.tanggalAkhir : m), "") || hariIniIso() : null),
    [daftarImpor]
  )
  const periode: Periode | null = periodePilihan ?? (terakhir ? { mode: "harian", dari: terakhir, sampai: terakhir } : null)
  const galat = periode ? galatPeriode(periode) : null
  const dari = periode?.dari
  const sampai = periode?.sampai
  const saringan = React.useMemo<Saringan | null>(
    () => (dari && sampai && !galat ? { dari, sampai, pilih, tunggakan } : null),
    [dari, sampai, galat, pilih, tunggakan]
  )
  const { data: laporan, isError, error, isFetching, refetch } = useLaporanPenerimaan(saringan)
  const adaSaringan = Object.values(pilih).some((v) => v && v.length > 0) || tunggakan !== ""

  const ubahPilih = (d: Dimensi, nilai: string[]) =>
    setPilih((p) => {
      const baru = { ...p }
      if (nilai.length > 0) baru[d] = nilai
      else delete baru[d]
      return baru
    })

  const saring = (d: Dimensi | "tanggal", nilai: string) => {
    if (d === "tanggal") setPeriode({ mode: "harian", dari: nilai, sampai: nilai })
    else ubahPilih(d, [nilai])
    panel.current?.scrollIntoView({ behavior: "smooth", block: "start" })
  }

  const hapusSaringan = () => {
    setPilih({})
    setTunggakan("")
  }

  const tampilkanImpor = (i: ImporPenerimaan) => {
    setPeriode(periodeDari(i.tanggalAwal, i.tanggalAkhir))
    hapusSaringan()
    panel.current?.scrollIntoView({ behavior: "smooth", block: "start" })
  }

  const belumAdaImpor = daftarImpor?.length === 0

  return (
    <Halaman>
      <NavPenerimaan />
      <PengantarHalaman deskripsi="Penerimaan PKB, BBNKB, dan opsen per loket dari berkas STS sistem BPAD. Impor berkas harian atau bulanan setiap loket; laporan dihitung dari transaksinya. Nomor polisi, nama pemilik, dan RT/RW hanya disimpan 7 hari untuk Rincian penerimaan dan tidak pernah tampil di portal.">
        <Button onClick={() => setImpor(true)}>
          <IconUpload />
          Impor berkas STS
        </Button>
      </PengantarHalaman>

      {belumAdaImpor ? (
        <Card>
          <CardContent>
            <Empty>
              <EmptyHeader>
                <EmptyMedia variant="icon">
                  <IconFileSpreadsheet />
                </EmptyMedia>
                <EmptyTitle>Belum ada berkas STS yang diimpor</EmptyTitle>
                <EmptyDescription>
                  Ekspor STS harian atau bulanan dari sistem BPAD (berkas .xls), lalu impor di sini untuk setiap loket.
                </EmptyDescription>
              </EmptyHeader>
              <EmptyContent>
                <Button onClick={() => setImpor(true)}>
                  <IconUpload />
                  Impor berkas STS
                </Button>
              </EmptyContent>
            </Empty>
          </CardContent>
        </Card>
      ) : (
        <>
          <div ref={panel} className="scroll-mt-20">
            {periode ? (
              <PanelSaringan
                periode={periode}
                ubahPeriode={setPeriode}
                galat={galat}
                pilihan={laporan?.pilihan}
                pilih={pilih}
                ubahPilih={ubahPilih}
                tunggakan={tunggakan}
                ubahTunggakan={setTunggakan}
                adaSaringan={adaSaringan}
                hapusSaringan={hapusSaringan}
              />
            ) : (
              <Skeleton className="h-30 rounded-xl" />
            )}
          </div>

          {isError ? (
            <Alert variant="destructive">
              <IconAlertTriangle />
              <AlertTitle>Laporan gagal dimuat</AlertTitle>
              <AlertDescription>
                <p>{(error as Error).message}</p>
                <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()}>
                  Coba lagi
                </Button>
              </AlertDescription>
            </Alert>
          ) : !laporan ? (
            galat ? null : <KerangkaLaporan />
          ) : (
            <div
              className={cn("grid gap-4 transition-opacity md:gap-6", isFetching && "opacity-60")}
              aria-busy={isFetching}
            >
              <Ringkasan laporan={laporan} />
              <PerLoket laporan={laporan} adaSaringan={adaSaringan} />
              {laporan.ringkasan.transaksi === 0 ? (
                <Card>
                  <CardContent>
                    <Empty>
                      <EmptyHeader>
                        <EmptyTitle>Tidak ada transaksi</EmptyTitle>
                        <EmptyDescription>
                          {adaSaringan
                            ? "Tidak ada transaksi yang cocok dengan saringan pada periode ini."
                            : "Belum ada berkas STS yang diimpor untuk periode ini."}
                        </EmptyDescription>
                      </EmptyHeader>
                      {adaSaringan && (
                        <EmptyContent>
                          <Button variant="outline" onClick={hapusSaringan}>
                            Hapus saringan
                          </Button>
                        </EmptyContent>
                      )}
                    </Empty>
                  </CardContent>
                </Card>
              ) : (
                <>
                  {laporan.dari !== laporan.sampai && <GrafikHarian laporan={laporan} bukaHari={(h) => saring("tanggal", h)} />}
                  <div className="grid gap-4 md:gap-6 xl:grid-cols-2">
                    <GrafikRincian laporan={laporan} dimensi="jenisDaftar" judul="Per jenis daftar" />
                    <GrafikRincian laporan={laporan} dimensi="jenisKendaraan" judul="Per jenis kendaraan" />
                    <GrafikRincian
                      laporan={laporan}
                      dimensi="kecamatan"
                      judul="Per kecamatan"
                      deskripsi="Menurut alamat pemilik kendaraan."
                    />
                    <GrafikRincian laporan={laporan} dimensi="uptAsal" judul="Per asal UPT" deskripsi="UPT tempat kendaraan terdaftar." />
                  </div>
                  <TabelRincian laporan={laporan} saring={saring} />
                  {saringan && <RincianPenerimaan saringan={saringan} />}
                </>
              )}
            </div>
          )}
        </>
      )}

      <RiwayatImpor data={daftarImpor} memuat={memuatImpor} lihat={tampilkanImpor} />
      {impor && <SheetImpor tutup={() => setImpor(false)} selesai={tampilkanImpor} />}
    </Halaman>
  )
}

function KerangkaLaporan() {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" role="status">
      <span className="sr-only">Memuat laporan…</span>
      <Skeleton className="h-65 rounded-xl md:col-span-2 xl:row-span-2" />
      {Array.from({ length: 4 }).map((_, i) => (
        <Skeleton key={i} className="h-30.5 rounded-xl" />
      ))}
    </div>
  )
}

/* ---------- Tabel rincian ---------- */

const TAB_RINCIAN: (Dimensi | "tanggal")[] = [
  "jenisDaftar",
  "jenisKendaraan",
  "kepemilikan",
  "warnaPlat",
  "merk",
  "uptAsal",
  "kecamatan",
  "kelurahan",
  "loket",
  "tanggal",
]

const judulRincian = (d: Dimensi | "tanggal") => (d === "tanggal" ? "Tanggal" : LABEL_DIMENSI[d])

function TabelRincian({ laporan, saring }: { laporan: LaporanPenerimaan; saring: (d: Dimensi | "tanggal", nilai: string) => void }) {
  const [pilihan, setPilihan] = React.useState<Dimensi | "tanggal">("jenisDaftar")
  const tab = laporan.dari === laporan.sampai ? TAB_RINCIAN.filter((t) => t !== "tanggal") : TAB_RINCIAN
  const dimensi = tab.includes(pilihan) ? pilihan : "jenisDaftar"
  const baris = laporan.rincian[dimensi]
  const total = laporan.ringkasan
  const kelurahan = dimensi === "kelurahan"

  const ekspor = () => {
    const periode = laporan.dari === laporan.sampai ? laporan.dari : `${laporan.dari}_sd_${laporan.sampai}`
    const nama = judulRincian(dimensi).toLowerCase().replace(/\s+/g, "-")
    unduhTeks(csvRincian(dimensi, baris, total), `penerimaan-per-${nama}-${periode}.csv`)
  }

  const tampil = (b: BarisRincian) => (dimensi === "tanggal" ? tanggal(b.nilai) : labelNilai(dimensi, b))

  return (
    <Card className="shadow-xs">
      <CardHeader>
        <CardTitle className="text-base">Rekap penerimaan</CardTitle>
        <CardDescription>Rupiah setiap komponen. Pilih nama di kolom pertama untuk menyaring laporan dengannya.</CardDescription>
        <CardAction>
          <Button variant="outline" size="sm" onClick={ekspor}>
            <IconDownload />
            Ekspor CSV
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent className="grid gap-4">
        <Tabs value={dimensi} onValueChange={(v) => setPilihan(v as Dimensi | "tanggal")} className="min-w-0">
          {/* Di layar sempit deretan tab digeser ke samping, bukan dilipat */}
          <div className="-mx-1 max-w-full overflow-x-auto px-1 pb-1">
            <TabsList>
              {tab.map((t) => (
                <TabsTrigger key={t} value={t} className="flex-none">
                  {judulRincian(t)}
                </TabsTrigger>
              ))}
            </TabsList>
          </div>
        </Tabs>
        <div className="max-h-120 overflow-auto rounded-lg border">
          <Table aria-label={`Rekap penerimaan per ${judulRincian(dimensi).toLowerCase()}`}>
            <TableHeader className="sticky top-0 z-10 bg-muted">
              <TableRow>
                <TableHead>{judulRincian(dimensi)}</TableHead>
                {kelurahan && <TableHead>Kecamatan</TableHead>}
                <TableHead className="text-right">Transaksi</TableHead>
                {KOMPONEN.map((k) => (
                  <TableHead key={k.kunci} className="text-right">
                    {k.label}
                  </TableHead>
                ))}
                <TableHead className="text-right">Jumlah</TableHead>
                <TableHead className="text-right">Porsi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {baris.map((b) => (
                <TableRow key={`${b.nilai}|${b.kecamatan ?? ""}`}>
                  <TableCell className="font-medium">
                    <button
                      type="button"
                      onClick={() => saring(dimensi, b.nilai)}
                      className="max-w-64 truncate rounded-sm text-left underline-offset-4 hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                      title={dimensi === "tanggal" ? "Buka laporan harian tanggal ini" : "Saring laporan dengan nilai ini"}
                    >
                      {tampil(b)}
                    </button>
                  </TableCell>
                  {kelurahan && <TableCell className="text-muted-foreground">{b.kecamatan}</TableCell>}
                  <TableCell className="text-right tabular-nums">{angka(b.transaksi)}</TableCell>
                  {KOMPONEN.map((k) => (
                    <TableCell key={k.kunci} className="text-right tabular-nums">
                      {angka(k.nilai(b))}
                    </TableCell>
                  ))}
                  <TableCell className="text-right font-medium tabular-nums">{angka(b.jumlah)}</TableCell>
                  <TableCell className="text-right text-muted-foreground tabular-nums">{persen(b.jumlah, total.jumlah)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
            <TableFooter className="sticky bottom-0">
              <TableRow>
                <TableCell colSpan={kelurahan ? 2 : 1}>Jumlah</TableCell>
                <TableCell className="text-right tabular-nums">{angka(total.transaksi)}</TableCell>
                {KOMPONEN.map((k) => (
                  <TableCell key={k.kunci} className="text-right tabular-nums">
                    {angka(k.nilai(total))}
                  </TableCell>
                ))}
                <TableCell className="text-right tabular-nums">{angka(total.jumlah)}</TableCell>
                <TableCell className="text-right tabular-nums">{total.jumlah > 0 ? "100%" : "—"}</TableCell>
              </TableRow>
            </TableFooter>
          </Table>
        </div>
      </CardContent>
    </Card>
  )
}

/* ---------- Riwayat impor ---------- */

const ki = pembantuKolom<ImporPenerimaan>()

const judulImpor = (i: ImporPenerimaan) => `${i.namaLoket} · ${labelPeriode(i.tanggalAwal, i.tanggalAkhir)}`

function RiwayatImpor({
  data,
  memuat,
  lihat,
}: {
  data: ImporPenerimaan[] | undefined
  memuat: boolean
  lihat: (i: ImporPenerimaan) => void
}) {
  const hapus = useHapusImpor()
  const tampil = useTampilImpor()
  const konfirmasi = useKonfirmasi()
  // Hanya Administrator yang menampilkan impor di portal (D-048); server juga menolaknya
  const administrator = sesi().peran === "administrator"

  const ubahTampil = async (impor: ImporPenerimaan, nyala: boolean) => {
    if (nyala) {
      const ya = await konfirmasi({
        judul: `Tampilkan ${judulImpor(impor)} di portal?`,
        deskripsi:
          "Penerimaannya tampil untuk umum di halaman Laporan Penerimaan portal, tanpa identitas wajib pajak. Kelompok dengan kurang dari 3 transaksi disamarkan. Pastikan berkasnya sudah diperiksa.",
        label: "Tampilkan",
      })
      if (!ya) return
    }
    try {
      await tampil.mutateAsync({ id: impor.id, tampil: nyala })
      toast.success(nyala ? "Impor tampil di portal" : "Impor disembunyikan dari portal")
    } catch (e) {
      toast.error("Tampilan di portal gagal diubah", { description: (e as Error).message })
    }
  }

  const hapusImpor = async (daftar: ImporPenerimaan[]) => {
    const ya = await konfirmasi({
      judul: daftar.length === 1 ? `Hapus impor ${judulImpor(daftar[0])}?` : `Hapus ${daftar.length} impor?`,
      deskripsi:
        "Transaksi dari impor ini ikut terhapus dari laporan. Berkas STS yang sama bisa diimpor ulang setelahnya.",
      label: "Hapus",
      bahaya: true,
    })
    if (!ya) return false
    try {
      await hapus.mutateAsync(daftar.map((i) => i.id))
      toast.success(daftar.length === 1 ? "Impor dihapus" : `${daftar.length} impor dihapus`)
      return true
    } catch (e) {
      toast.error("Impor gagal dihapus", { description: (e as Error).message })
      return false
    }
  }

  const kolom = [
    ki.accessor("tanggalAkhir", {
      header: ({ column }) => <HeaderUrut column={column} judul="Periode" />,
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => lihat(row.original)}
          className="grid min-w-48 gap-0.5 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="font-medium underline-offset-4 hover:underline">
            {labelPeriode(row.original.tanggalAwal, row.original.tanggalAkhir)}
          </span>
          <span className="text-xs break-all text-muted-foreground">{row.original.namaBerkas}</span>
        </button>
      ),
      enableHiding: false,
    }),
    ki.accessor("namaLoket", { header: "Loket" }),
    ki.accessor("jumlahTransaksi", {
      header: ({ column }) => <HeaderUrut column={column} judul="Transaksi" kanan />,
      cell: ({ row }) => (
        <div className="text-right tabular-nums">
          {angka(row.original.jumlahTransaksi)}
          {row.original.jumlahDilewati > 0 && (
            <span className="block text-xs text-muted-foreground">{angka(row.original.jumlahDilewati)} dilewati</span>
          )}
        </div>
      ),
    }),
    ki.accessor("total", {
      header: ({ column }) => <HeaderUrut column={column} judul="Total" kanan />,
      cell: ({ row }) => <div className="text-right tabular-nums">{rupiah(row.original.total)}</div>,
    }),
    ki.accessor("tampil", {
      header: "Portal",
      cell: ({ row }) =>
        administrator ? (
          <Switch
            checked={row.original.tampil}
            disabled={tampil.isPending}
            onCheckedChange={(nyala) => void ubahTampil(row.original, nyala)}
            aria-label={`Tampilkan ${judulImpor(row.original)} di portal`}
          />
        ) : (
          <NadaBadge nada={row.original.tampil ? "sukses" : "netral"}>{row.original.tampil ? "Tampil" : "Belum tampil"}</NadaBadge>
        ),
    }),
    ki.accessor("dibuat", {
      header: ({ column }) => <HeaderUrut column={column} judul="Diimpor" />,
      cell: ({ row }) => (
        <span className="grid text-sm whitespace-nowrap">
          <span>{tanggalJam(row.original.dibuat)}</span>
          <span className="text-xs text-muted-foreground">{row.original.oleh ?? "—"}</span>
        </span>
      ),
    }),
    ki.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris label={judulImpor(row.original)} onHapus={() => void hapusImpor([row.original])}>
          <DropdownMenuItem onSelect={() => lihat(row.original)}>
            <IconCalendarEvent />
            Lihat laporannya
          </DropdownMenuItem>
        </MenuBaris>
      ),
    }),
  ]

  return (
    <section aria-labelledby="judul-riwayat-impor" className="flex min-w-0 flex-col gap-3">
      <div className="grid gap-1">
        <h2 id="judul-riwayat-impor" className="text-base font-heading font-(--fw-heading)">
          Riwayat impor
        </h2>
        <p className="max-w-2xl text-sm text-muted-foreground">
          Transaksi yang sudah pernah diimpor dilewati, jadi berkas bulanan aman diimpor setelah berkas hariannya. Menghapus
          impor menghapus transaksinya dari laporan. Impor baru tampil di portal setelah Administrator menyalakan kolom
          Portal.
        </p>
      </div>
      <DataTable
        data={data}
        memuat={memuat}
        columns={kolom}
        label="Riwayat impor berkas STS"
        sortingAwal={[{ id: "dibuat", desc: true }]}
        cari={{ placeholder: "Cari berkas atau loket…", teks: (i) => `${i.namaBerkas} ${i.namaLoket}` }}
        tindakanMassal={(baris, bersihkan) => (
          <Button variant="destructive" size="sm" onClick={async () => (await hapusImpor(baris)) && bersihkan()}>
            Hapus
          </Button>
        )}
        kosong={{ judul: "Belum ada berkas yang diimpor", deskripsi: "Impor berkas STS harian atau bulanan dari sistem BPAD." }}
      />
    </section>
  )
}

/* ---------- Impor ---------- */

const GRUP_LOKET: { jenis: JenisLoket; label: string }[] = [
  { jenis: "kantor", label: "Kantor" },
  { jenis: "keliling", label: "Samsat Keliling" },
  { jenis: "gerai", label: "Gerai tetap" },
  { jenis: "sementara", label: "Loket sementara" },
]

function SheetImpor({ tutup, selesai }: { tutup: () => void; selesai: (i: ImporPenerimaan) => void }) {
  const { data: daftarLoket } = useLoketPenerimaan()
  const pratinjau = usePratinjauImpor()
  const simpan = useSimpanImpor()
  const [loket, setLoket] = React.useState("kantor")
  const [berkas, setBerkas] = React.useState<File | null>(null)
  const [galat, setGalat] = React.useState<string | null>(null)
  const hasil = pratinjau.data

  const pilihBerkas = (f: File | null) => {
    pratinjau.reset()
    setGalat(null)
    if (f && !/\.(xls|html?)$/i.test(f.name)) {
      setGalat("Pilih berkas STS berformat .xls hasil ekspor sistem BPAD.")
      setBerkas(null)
      return
    }
    if (f && f.size > MAKS_BERKAS) {
      setGalat("Ukuran berkas maksimal 10 MB.")
      setBerkas(null)
      return
    }
    setBerkas(f)
  }

  const periksa = async () => {
    if (!berkas) {
      setGalat("Pilih berkas STS yang akan diimpor.")
      return
    }
    setGalat(null)
    try {
      await pratinjau.mutateAsync({ berkas, loket })
    } catch (e) {
      setGalat((e as Error).message)
    }
  }

  const kirim = async () => {
    if (!berkas) return
    setGalat(null)
    try {
      const i = await simpan.mutateAsync({ berkas, loket })
      const lengkap = hasil?.dilengkapi ? ` Nomoratur atau rincian ${angka(hasil.dilengkapi)} transaksi lama juga dilengkapi.` : ""
      if ("id" in i) {
        toast.success("Berkas STS diimpor", {
          description: `${angka(i.jumlahTransaksi)} transaksi, ${rupiah(i.total)}, untuk ${i.namaLoket}.${lengkap}`,
        })
        selesai(i)
      } else {
        // Berkas yang sudah pernah diimpor hanya melengkapi nomoratur (D-071) atau rincian 7 hari terakhir (D-079)
        toast.success("Transaksi dilengkapi", {
          description: `${angka(i.dilengkapi)} transaksi yang sudah diimpor kini tercatat nomoratur atau rinciannya.`,
        })
      }
      tutup()
    } catch (e) {
      setGalat((e as Error).message)
    }
  }

  return (
    <Sheet open onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>Impor berkas STS</SheetTitle>
          <SheetDescription>
            Berkas .xls hasil ekspor STS harian atau bulanan dari sistem BPAD. Nomor polisi, nama pemilik, RT/RW, dan kolom lain
            untuk Rincian penerimaan hanya disimpan (terenkripsi) untuk transaksi 7 hari terakhir.
          </SheetDescription>
        </SheetHeader>
        <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
          <div className="grid gap-2">
            <Label htmlFor="impor-loket">Loket asal berkas</Label>
            <Select
              value={loket}
              onValueChange={(v) => {
                setLoket(v)
                pratinjau.reset()
              }}
            >
              <SelectTrigger id="impor-loket" className="w-full">
                <SelectValue placeholder="Pilih loket" />
              </SelectTrigger>
              <SelectContent>
                {GRUP_LOKET.map((g) => {
                  const isi = (daftarLoket ?? []).filter((l) => l.jenis === g.jenis)
                  return isi.length === 0 ? null : (
                    <SelectGroup key={g.jenis}>
                      <SelectLabel>{g.label}</SelectLabel>
                      {isi.map((l) => (
                        <SelectItem key={l.kunci} value={l.kunci}>
                          {l.nama}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  )
                })}
                {!daftarLoket && <SelectItem value="kantor">Loket SAMSAT</SelectItem>}
              </SelectContent>
            </Select>
            <p className="text-xs text-muted-foreground">
              Berkas STS tidak mencantumkan loketnya, jadi pilih loket yang mengekspor berkas ini.
            </p>
          </div>
          <div className="grid gap-2">
            <Label htmlFor="impor-berkas">Berkas STS</Label>
            <Input
              id="impor-berkas"
              type="file"
              accept=".xls,.htm,.html,application/vnd.ms-excel,text/html"
              onChange={(e) => pilihBerkas(e.target.files?.[0] ?? null)}
            />
            {berkas && (
              <p className="text-xs text-muted-foreground">
                {berkas.name} · {ukuranBerkas(berkas.size)}
              </p>
            )}
          </div>
          {galat && (
            <Alert variant="destructive">
              <IconAlertTriangle />
              <AlertTitle>Berkas belum bisa diimpor</AlertTitle>
              <AlertDescription>{galat}</AlertDescription>
            </Alert>
          )}
          {hasil && <HasilPratinjau hasil={hasil} />}
        </div>
        <SheetFooter className="flex-row justify-end gap-2 border-t">
          <Button type="button" variant="outline" onClick={tutup}>
            Batal
          </Button>
          {hasil ? (
            <Button type="button" onClick={kirim} disabled={simpan.isPending || (hasil.baru.transaksi === 0 && hasil.dilengkapi === 0)}>
              {simpan.isPending && <Spinner />}
              {hasil.baru.transaksi > 0 || hasil.dilengkapi === 0
                ? `Simpan ${angka(hasil.baru.transaksi)} transaksi`
                : `Lengkapi ${angka(hasil.dilengkapi)} transaksi`}
            </Button>
          ) : (
            <Button type="button" onClick={periksa} disabled={pratinjau.isPending || !berkas}>
              {pratinjau.isPending && <Spinner />}
              Periksa berkas
            </Button>
          )}
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}

function HasilPratinjau({ hasil }: { hasil: PratinjauImpor }) {
  const { berkas, baru } = hasil
  return (
    <div className="grid gap-4 rounded-lg border p-4" aria-live="polite">
      <div className="grid gap-0.5">
        <p className="font-medium">
          {hasil.loket.nama}
          {berkas.tanggalAwal && berkas.tanggalAkhir && ` · ${labelPeriode(berkas.tanggalAwal, berkas.tanggalAkhir)}`}
        </p>
        <p className="text-xs break-all text-muted-foreground">{hasil.namaBerkas}</p>
      </div>
      <dl className="grid gap-3 text-sm sm:grid-cols-3">
        <div className="grid gap-0.5">
          <dt className="text-xs text-muted-foreground">Di berkas</dt>
          <dd className="font-medium tabular-nums">{angka(berkas.transaksi)} transaksi</dd>
          <dd className="text-xs text-muted-foreground tabular-nums">{rupiah(berkas.jumlah)}</dd>
        </div>
        <div className="grid gap-0.5">
          <dt className="text-xs text-muted-foreground">Akan disimpan</dt>
          <dd className="font-medium tabular-nums">{angka(baru.transaksi)} transaksi</dd>
          <dd className="text-xs text-muted-foreground tabular-nums">{rupiah(baru.jumlah)}</dd>
        </div>
        <div className="grid gap-0.5">
          <dt className="text-xs text-muted-foreground">Dilewati</dt>
          <dd className="font-medium tabular-nums">{angka(hasil.dilewati)} transaksi</dd>
          <dd className="text-xs text-muted-foreground">sudah pernah diimpor atau tercantum dua kali</dd>
        </div>
      </dl>
      {hasil.dilengkapi > 0 && (
        <Alert>
          <IconInfoCircle />
          <AlertDescription>
            {angka(hasil.dilengkapi)} transaksi yang dilewati diimpor sebelum nomoratur atau rinciannya dicatat. Yang kosong akan
            diisi dari berkas ini tanpa menghitung penerimaannya dua kali.
          </AlertDescription>
        </Alert>
      )}
      {baru.transaksi === 0 ? (
        hasil.dilengkapi === 0 && (
          <Alert>
            <IconInfoCircle />
            <AlertDescription>Semua transaksi di berkas ini sudah pernah diimpor, jadi tidak ada yang perlu disimpan.</AlertDescription>
          </Alert>
        )
      ) : (
        <div className="overflow-hidden rounded-md border">
          <Table aria-label="Transaksi yang akan disimpan per jenis daftar">
            <TableHeader className="bg-muted">
              <TableRow>
                <TableHead>Jenis daftar</TableHead>
                <TableHead className="text-right">Transaksi</TableHead>
                <TableHead className="text-right">Jumlah</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {baru.jenisDaftar.map((j) => (
                <TableRow key={j.nilai}>
                  <TableCell className="whitespace-normal">{labelNilai("jenisDaftar", j)}</TableCell>
                  <TableCell className="text-right tabular-nums">{angka(j.transaksi)}</TableCell>
                  <TableCell className="text-right tabular-nums">{rupiah(j.jumlah)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}
    </div>
  )
}
