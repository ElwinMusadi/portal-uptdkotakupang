import * as React from "react"
import { IconBan, IconCircleCheck, IconPrinter } from "@tabler/icons-react"
import { toast } from "sonner"

import { NavAntrean } from "@/components/antrean/nav-antrean"
import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris } from "@/components/data/row-actions"
import { NadaBadge } from "@/components/data/status-badge"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Skeleton } from "@/components/ui/skeleton"
import { Textarea } from "@/components/ui/textarea"
import {
  LABEL_STATUS,
  LABEL_TAHAP,
  nopolPlat,
  nopolRingkas,
  nopolSah,
  useBatalkanTiket,
  useCetakTiket,
  useCetakUlangTiket,
  useLayananAntrean,
  useTiketAntrean,
  type TiketAntrean,
} from "@/lib/antrean"
import { cetakTiket, svgQr, waktuTiket } from "@/lib/cetak-tiket"
import { hariIniIso, tanggal, tanggalJam } from "@/lib/format"
import { cn } from "@/lib/utils"

/*
 * Meja Informasi Antrean Online (D-051): setelah berkas wajib pajak lengkap,
 * petugas mengetik nomor polisi, memilih layanan, lalu mencetak dua lembar
 * tiket ber-QR. Di bawahnya daftar nomor yang dicetak pada tanggal itu.
 */
export default function HalamanMejaInformasi() {
  const [hari, setHari] = React.useState(hariIniIso)
  const tiket = useTiketAntrean(hari)
  const [terakhir, setTerakhir] = React.useState<TiketAntrean | null>(null)

  return (
    <Halaman>
      <NavAntrean />
      <PengantarHalaman deskripsi="Cetak nomor antrean setelah berkas wajib pajak dinyatakan lengkap. Tiket dua lembar: lembar 1 untuk wajib pajak, lembar 2 ditempel pada berkas. Keduanya ber-QR untuk melacak berkas." />
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_21.25rem] lg:items-start">
        <FormCetak setelahCetak={setTerakhir} />
        <TiketTerakhir tiket={terakhir} />
      </div>
      <DaftarTiket hari={hari} setHari={setHari} data={tiket.data} memuat={tiket.isLoading} galat={tiket.error} />
    </Halaman>
  )
}

function FormCetak({ setelahCetak }: { setelahCetak: (t: TiketAntrean) => void }) {
  const layanan = useLayananAntrean()
  const cetak = useCetakTiket()
  const [nopol, setNopol] = React.useState("")
  const [kode, setKode] = React.useState("")
  const [galat, setGalat] = React.useState<{ nopol?: string; layanan?: string }>({})
  const kotakNopol = React.useRef<HTMLInputElement>(null)
  const aktif = (layanan.data ?? []).filter((l) => l.aktif)

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    const periksa = {
      nopol: !nopolRingkas(nopol) ? "Tulis nomor polisi kendaraan." : !nopolSah(nopol) ? "Tulis nomor polisi seperti DH 1234 AB." : undefined,
      layanan: kode ? undefined : "Pilih layanan.",
    }
    setGalat(periksa)
    if (periksa.nopol || periksa.layanan) return
    try {
      const t = await cetak.mutateAsync({ nopol, layanan: kode })
      cetakTiket(t)
      setelahCetak(t)
      toast.success(`Nomor ${t.nomor} dicetak`, { description: `${t.layanan.nama} · ${t.nopol ?? ""}` })
      setNopol("")
      setKode("")
      kotakNopol.current?.focus()
    } catch (err) {
      toast.error("Nomor gagal dicetak", { description: (err as Error).message })
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Cetak nomor</CardTitle>
        <CardDescription>Ketik nomor polisi, pilih layanan, lalu tekan Cetak nomor (atau Enter).</CardDescription>
      </CardHeader>
      <CardContent>
        <form className="grid gap-5" onSubmit={(e) => void kirim(e)} noValidate>
          <div className="grid gap-2">
            <Label htmlFor="antrean-nopol">Nomor polisi</Label>
            <Input
              id="antrean-nopol"
              ref={kotakNopol}
              value={nopol}
              onChange={(e) => setNopol(e.target.value.toUpperCase())}
              placeholder="DH 1234 AB"
              autoComplete="off"
              autoFocus
              aria-invalid={!!galat.nopol}
              aria-describedby="antrean-nopol-ket"
              className="h-12 max-w-xs font-mono text-xl tracking-wider"
            />
            <p id="antrean-nopol-ket" className={cn("text-sm", galat.nopol ? "text-destructive" : "text-muted-foreground")}>
              {galat.nopol ?? (nopolSah(nopol) ? `Tercetak: ${nopolPlat(nopol)}` : "Satu atau dua huruf, angka, lalu sampai tiga huruf.")}
            </p>
          </div>
          <fieldset className="grid gap-2">
            <legend className="mb-2 text-sm leading-none font-medium">Layanan</legend>
            {layanan.isLoading ? (
              <div className="grid gap-2 sm:grid-cols-2">
                {[0, 1, 2, 3].map((i) => (
                  <Skeleton key={i} className="h-20" />
                ))}
              </div>
            ) : layanan.error ? (
              <p className="text-sm text-destructive">Layanan gagal dimuat: {layanan.error.message}</p>
            ) : (
              <RadioGroup value={kode} onValueChange={setKode} className="grid gap-2 sm:grid-cols-2" aria-invalid={!!galat.layanan}>
                {aktif.map((l) => (
                  <Label
                    key={l.id}
                    htmlFor={`antrean-layanan-${l.kode}`}
                    className="flex cursor-pointer items-start gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-primary has-[[data-state=checked]]:bg-primary/5"
                  >
                    <RadioGroupItem id={`antrean-layanan-${l.kode}`} value={l.kode} className="mt-1" />
                    <span className="grid gap-0.5">
                      <span className="flex items-baseline gap-2">
                        <span className="font-mono text-lg font-semibold">{l.kode}</span>
                        <span className="font-medium">{l.nama}</span>
                      </span>
                      {l.cakupan && <span className="text-xs text-muted-foreground">{l.cakupan}</span>}
                    </span>
                  </Label>
                ))}
              </RadioGroup>
            )}
            {galat.layanan && <p className="text-sm text-destructive">{galat.layanan}</p>}
          </fieldset>
          <div>
            <Button type="submit" size="lg" disabled={cetak.isPending || layanan.isLoading}>
              <IconPrinter />
              {cetak.isPending ? "Mencetak…" : "Cetak nomor"}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  )
}

/** Pratinjau tiket yang baru dicetak, dengan tombol cetak ulang */
function TiketTerakhir({ tiket }: { tiket: TiketAntrean | null }) {
  const cetakUlang = useCetakUlang()
  const qr = React.useMemo(() => (tiket ? svgQr(tiket.urlLacak) : ""), [tiket])

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Tiket terakhir</CardTitle>
        <CardDescription>{tiket ? "Bila kertas macet atau rusak, cetak ulang: nomor dan QR tetap." : "Belum ada nomor yang dicetak dari meja ini."}</CardDescription>
      </CardHeader>
      {tiket && (
        <CardContent className="grid justify-items-center gap-2 text-center">
          <p className="font-mono text-5xl font-bold tracking-tight">{tiket.nomor}</p>
          <p className="font-medium">{tiket.layanan.nama}</p>
          <p className="font-mono text-lg tracking-wider">{tiket.nopol}</p>
          <p className="text-xs text-muted-foreground">{waktuTiket(tiket.dicetak)}</p>
          <div className="size-32 [&_svg]:size-full" role="img" aria-label={`QR tiket ${tiket.nomor}`} dangerouslySetInnerHTML={{ __html: qr }} />
          <div className="flex flex-wrap justify-center gap-2">
            <Button variant="outline" size="sm" onClick={() => void cetakUlang(tiket)}>
              <IconPrinter />
              Cetak ulang
            </Button>
            <Button variant="ghost" size="sm" asChild>
              <a href={tiket.urlLacak} target="_blank" rel="noreferrer">
                Buka halaman lacak
              </a>
            </Button>
          </div>
        </CardContent>
      )}
    </Card>
  )
}

/** Cetak ulang tiket yang sama dan catat di riwayatnya */
function useCetakUlang() {
  const mutasi = useCetakUlangTiket()
  return async (t: TiketAntrean) => {
    try {
      cetakTiket(await mutasi.mutateAsync(t.id))
    } catch (e) {
      toast.error("Tiket gagal dicetak ulang", { description: (e as Error).message })
    }
  }
}

const ki = pembantuKolom<TiketAntrean>()

function DaftarTiket({
  hari,
  setHari,
  data,
  memuat,
  galat,
}: {
  hari: string
  setHari: (h: string) => void
  data: TiketAntrean[] | undefined
  memuat: boolean
  galat: Error | null
}) {
  const cetakUlang = useCetakUlang()
  const [batalkan, setBatalkan] = React.useState<TiketAntrean | null>(null)
  const hariIni = hari === hariIniIso()

  const kolom = [
    ki.accessor("nomor", {
      header: ({ column }) => <HeaderUrut column={column} judul="Nomor" />,
      cell: ({ row }) => <span className="font-mono text-base font-semibold">{row.original.nomor}</span>,
      enableHiding: false,
    }),
    ki.accessor((t) => t.nopol ?? "", { id: "nopol", header: "Nomor polisi", cell: ({ getValue }) => <span className="font-mono">{getValue() || "—"}</span> }),
    ki.accessor((t) => t.layanan.nama, { id: "layanan", header: "Layanan" }),
    ki.accessor("status", {
      header: "Tahap",
      cell: ({ row }) => {
        const t = row.original
        if (t.status === "batal")
          return (
            <NadaBadge nada="bahaya" ikon={IconBan}>
              Dibatalkan
            </NadaBadge>
          )
        if (t.status === "selesai")
          return (
            <NadaBadge nada="sukses" ikon={IconCircleCheck}>
              Selesai
            </NadaBadge>
          )
        return (
          <span className="grid gap-0.5">
            <Badge variant="outline">{t.tahap ? LABEL_TAHAP[t.tahap] : "—"}</Badge>
            {t.status !== "menunggu" && (
              <span className="text-xs text-muted-foreground">{t.status === "dipanggil" ? `Dipanggil di ${t.loket ?? "loket"}` : LABEL_STATUS[t.status]}</span>
            )}
          </span>
        )
      },
    }),
    ki.accessor("dicetak", {
      header: ({ column }) => <HeaderUrut column={column} judul="Dicetak" />,
      cell: ({ row }) => (
        <span className="grid text-sm whitespace-nowrap">
          <span>{tanggalJam(row.original.dicetak)}</span>
          <span className="text-xs text-muted-foreground">{row.original.dicetakOleh ?? "—"}</span>
        </span>
      ),
    }),
    ki.display({
      id: "aksi",
      cell: ({ row }) =>
        row.original.status === "batal" || row.original.status === "selesai" ? null : (
          <MenuBaris label={`nomor ${row.original.nomor}`}>
            <DropdownMenuItem onSelect={() => void cetakUlang(row.original)}>
              <IconPrinter />
              Cetak ulang
            </DropdownMenuItem>
            <DropdownMenuItem variant="destructive" onSelect={() => setBatalkan(row.original)}>
              <IconBan />
              Batalkan
            </DropdownMenuItem>
          </MenuBaris>
        ),
    }),
  ]

  return (
    <Card>
      <CardHeader className="flex flex-wrap items-end justify-between gap-3">
        <div className="grid gap-1.5">
          <CardTitle className="text-base">{hariIni ? "Nomor hari ini" : `Nomor ${tanggal(hari, "panjang")}`}</CardTitle>
          <CardDescription>
            {data ? `${data.length} nomor dicetak${data.some((t) => t.status === "batal") ? `, ${data.filter((t) => t.status === "batal").length} dibatalkan` : ""}.` : "Memuat…"}
          </CardDescription>
        </div>
        <div className="grid gap-1.5">
          <Label htmlFor="antrean-hari" className="text-xs text-muted-foreground">
            Tanggal
          </Label>
          <Input id="antrean-hari" type="date" value={hari} max={hariIniIso()} onChange={(e) => e.target.value && setHari(e.target.value)} className="w-auto" />
        </div>
      </CardHeader>
      <CardContent>
        {galat ? (
          <p className="text-sm text-destructive">Daftar nomor gagal dimuat: {galat.message}</p>
        ) : (
          <DataTable
            data={data}
            columns={kolom}
            label="Daftar nomor antrean"
            memuat={memuat}
            pilih={false}
            cari={{ placeholder: "Cari nomor atau nomor polisi…", teks: (t) => `${t.nomor} ${t.nopol ?? ""} ${(t.nopol ?? "").replace(/\s/g, "")}` }}
            filter={[
              {
                id: "layanan",
                label: "Layanan",
                opsi: [...new Set((data ?? []).map((t) => t.layanan.nama))].map((n) => ({ nilai: n, label: n })),
                nilai: (t) => t.layanan.nama,
              },
            ]}
            kosong={{ judul: "Belum ada nomor", deskripsi: hariIni ? "Nomor yang dicetak hari ini muncul di sini." : "Tidak ada nomor yang dicetak pada tanggal ini." }}
          />
        )}
      </CardContent>
      <DialogBatal tiket={batalkan} tutup={() => setBatalkan(null)} />
    </Card>
  )
}

function DialogBatal({ tiket, tutup }: { tiket: TiketAntrean | null; tutup: () => void }) {
  const batalkan = useBatalkanTiket()
  const [alasan, setAlasan] = React.useState("")
  const [galat, setGalat] = React.useState<string | null>(null)

  React.useEffect(() => {
    setAlasan("")
    setGalat(null)
  }, [tiket])

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!tiket) return
    if (!alasan.trim()) {
      setGalat("Tulis alasan pembatalan.")
      return
    }
    try {
      await batalkan.mutateAsync({ id: tiket.id, alasan: alasan.trim() })
      toast.success(`Nomor ${tiket.nomor} dibatalkan`)
      tutup()
    } catch (err) {
      setGalat((err as Error).message)
    }
  }

  return (
    <Dialog open={!!tiket} onOpenChange={(buka) => !buka && tutup()}>
      <DialogContent>
        <form className="grid gap-4" onSubmit={(e) => void kirim(e)} noValidate>
          <DialogHeader>
            <DialogTitle>Batalkan nomor {tiket?.nomor}?</DialogTitle>
            <DialogDescription>
              Nomor ini tidak dipanggil lagi, dan halaman lacaknya menyatakan dibatalkan. Nomor yang sudah dicetak tidak dipakai ulang.
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-2">
            <Label htmlFor="antrean-alasan">Alasan</Label>
            <Textarea
              id="antrean-alasan"
              value={alasan}
              onChange={(e) => setAlasan(e.target.value)}
              maxLength={200}
              placeholder="Mis. salah memilih layanan, wajib pajak pulang"
              aria-invalid={!!galat}
              aria-describedby={galat ? "antrean-alasan-galat" : undefined}
            />
            {galat && (
              <p id="antrean-alasan-galat" className="text-sm text-destructive">
                {galat}
              </p>
            )}
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={tutup}>
              Kembali
            </Button>
            <Button type="submit" variant="destructive" disabled={batalkan.isPending}>
              <IconBan />
              Batalkan nomor
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

