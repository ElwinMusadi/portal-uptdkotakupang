/*
 * Halaman Ikhtisar (D-099): penerimaan semua loket, Realisasi Target (Form I),
 * dan Pemetaan PKB untuk rapat, dari tautan yang dibuat Administrator di
 * Pengaturan › Tautan Ikhtisar. Angkanya sama dengan dashboard, tanpa
 * identitas wajib pajak, dan selalu menyebut sumber serta tanggal datanya.
 */
import * as React from "react"
import {
  IconAlertTriangle,
  IconCalendarEvent,
  IconInfoCircle,
  IconLinkOff,
  IconLock,
  IconPrinter,
  IconRefresh,
  IconWifiOff,
} from "@tabler/icons-react"

import { IsiRingkasanPemetaan, belumBayar, DaftarBatang, persen as persenPotensi } from "@/components/pemetaan/ringkasan-wilayah"
import { cetakFormI, RingkasanRealisasi, Sumber, TabelFormI, tanggalPanjang, TertutupSaldo } from "@/components/penerimaan/form-i"
import { BadgePerubahan, GrafikHarian, GrafikRincian, PerLoket, Ringkasan, type IntiLaporan } from "@/components/penerimaan/ringkasan-laporan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Skeleton } from "@/components/ui/skeleton"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { angka, rupiah } from "@/lib/format"
import { NAMA_BULAN } from "@/lib/pemetaan"
import { bagianProvinsi, perubahan } from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { jumlahHari, labelPeriode, labelTanggal, MAKS_HARI } from "../portal/penerimaan/periode"
import { catatPilihan, GalatIkhtisar, PERIODE, pilihanDariAlamat, useIkhtisar, type Ikhtisar, type Periode, type Pilihan } from "./data"
import { ambilToken, lupakanToken } from "./token"

const BAGIAN = [
  { id: "ringkasan", judul: "Ringkasan" },
  { id: "penerimaan", judul: "Penerimaan" },
  { id: "realisasi", judul: "Realisasi Target" },
  { id: "pemetaan", judul: "Pemetaan PKB" },
] as const

const jamWita = (d: number) =>
  new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Makassar" }).format(new Date(d)).replace(":", ".")

export function AplikasiIkhtisar() {
  const [token, setToken] = React.useState(ambilToken)
  const [pilihan, setPilihan] = React.useState<Pilihan>(pilihanDariAlamat)
  const ikhtisar = useIkhtisar(token, pilihan)
  const galat = ikhtisar.error instanceof GalatIkhtisar ? ikhtisar.error : null
  const dicabut = galat?.status === 401

  // Tautan yang dicabut tidak disimpan lagi di perangkat ini
  React.useEffect(() => {
    if (dicabut) lupakanToken()
  }, [dicabut])

  const pilih = (p: Pilihan) => {
    setPilihan(p)
    catatPilihan(p)
  }

  if (!token) {
    return (
      <LayarPesan ikon={<IconLock />} judul="Halaman ini dibuka lewat tautan">
        Buka halaman ini dari tautan yang dibagikan Administrator PINTU. Tautannya bisa dibuat di Dashboard › Pengaturan › Tautan Ikhtisar.
      </LayarPesan>
    )
  }
  if (dicabut) {
    return (
      <LayarPesan ikon={<IconLinkOff />} judul="Tautan tidak berlaku">
        Tautan ini sudah dicabut atau tidak berlaku. Minta tautan baru kepada Administrator PINTU.
      </LayarPesan>
    )
  }

  const data = ikhtisar.data
  if (!data) {
    if (galat) {
      return (
        <LayarPesan ikon={<IconAlertTriangle />} judul="Ikhtisar belum dapat dimuat" aksi={<Button onClick={() => void ikhtisar.refetch()}>Coba lagi</Button>}>
          {galat.status === 429 ? "Terlalu banyak permintaan dari jaringan ini. Coba lagi sebentar lagi." : galat.message}
        </LayarPesan>
      )
    }
    return <KerangkaIkhtisar />
  }

  const lupakan = () => {
    lupakanToken()
    setToken(null)
  }

  return (
    <div className="min-h-svh bg-background">
      <Kepala data={data} dimuat={ikhtisar.dataUpdatedAt} memuat={ikhtisar.isFetching} perbarui={() => void ikhtisar.refetch()} />
      <BilahNavigasi data={data} pilihan={pilihan} pilih={pilih} />
      <main className={cn("mx-auto grid w-full max-w-[100rem] gap-10 px-4 py-6 transition-opacity md:px-8", ikhtisar.isFetching && "opacity-70")} aria-busy={ikhtisar.isFetching}>
        {galat && (
          <Alert variant={galat.status === 422 ? "default" : "destructive"} role="status">
            {galat.status === 0 ? <IconWifiOff /> : <IconAlertTriangle />}
            <AlertTitle>{galat.status === 422 ? "Periode tidak bisa ditampilkan" : "Angka belum diperbarui"}</AlertTitle>
            <AlertDescription>
              {galat.status === 429 ? "Terlalu banyak permintaan dari jaringan ini. Coba lagi sebentar lagi." : galat.message}{" "}
              {galat.status !== 422 && `Angka di bawah dimuat pukul ${jamWita(ikhtisar.dataUpdatedAt)} WITA.`}
            </AlertDescription>
          </Alert>
        )}
        <BagianRingkasan data={data} />
        <BagianPenerimaan data={data} />
        <BagianRealisasi data={data} />
        <BagianPemetaan data={data} />
        <KakiHalaman lupakan={lupakan} />
      </main>
    </div>
  )
}

/* ---------- Kepala dan navigasi ---------- */

function Kepala({ data, dimuat, memuat, perbarui }: { data: Ikhtisar; dimuat: number; memuat: boolean; perbarui: () => void }) {
  const p = data.pembaruan
  return (
    <header className="border-b bg-card">
      <div className="mx-auto grid w-full max-w-[100rem] gap-4 px-4 py-5 md:px-8">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="flex items-center gap-4">
            <img src="/assets/img/logo-pintu-nav.png" alt="PINTU" width={1107} height={361} className="h-9 w-auto shrink-0" />
            <div className="grid gap-0.5">
              <h1 className="text-xl font-heading font-(--fw-heading) md:text-2xl">Ikhtisar Penerimaan &amp; Pemetaan PKB</h1>
              <p className="text-sm text-muted-foreground">UPTD Pendapatan Daerah Wilayah Kota Kupang · SAMSAT Kota Kupang</p>
            </div>
          </div>
          <div className="flex items-center gap-3 print:hidden">
            <span className="text-sm text-muted-foreground" aria-live="polite">
              {memuat ? "Memperbarui…" : `Dimuat ${jamWita(dimuat)} WITA`}
            </span>
            <Button variant="outline" size="sm" onClick={perbarui} disabled={memuat}>
              <IconRefresh />
              Perbarui
            </Button>
          </div>
        </div>
        <dl className="grid gap-3 text-sm md:grid-cols-[auto_minmax(0,1fr)] md:gap-x-6">
          <dt className="font-medium">STS terakhir</dt>
          <dd className="flex flex-wrap items-center gap-2">
            {p.stsTerakhir ? (
              <>
                <span className="font-semibold">{labelTanggal(p.stsTerakhir, "lengkap")}</span>
                {p.loket.map((l) => {
                  const tertinggal = l.sampaiTanggal < p.stsTerakhir!
                  return (
                    <Badge key={l.nilai} variant="outline" className={cn(tertinggal && "border-amber-500/60 bg-amber-500/10")}>
                      {l.nama} · {labelTanggal(l.sampaiTanggal)}
                      {tertinggal && <span className="sr-only"> (tertinggal)</span>}
                    </Badge>
                  )
                })}
                {p.belumDiimpor.map((l) => (
                  <Badge key={l.kunci} variant="outline" className="border-dashed text-muted-foreground">
                    {l.nama} · belum diimpor
                  </Badge>
                ))}
              </>
            ) : (
              <span className="text-muted-foreground">Belum ada berkas STS yang diimpor.</span>
            )}
          </dd>
          <dt className="font-medium">Data Potensi</dt>
          <dd className="text-muted-foreground">
            {p.dataPotensi?.tanggal
              ? `Penetapan ${labelTanggal(p.dataPotensi.tanggal, "lengkap")}${data.pemetaan ? ` · ${angka(data.pemetaan.jumlah.kendaraan)} kendaraan` : ""}`
              : "Belum ada berkas Data Potensi yang diimpor."}
          </dd>
        </dl>
      </div>
    </header>
  )
}

function BilahNavigasi({ data, pilihan, pilih }: { data: Ikhtisar; pilihan: Pilihan; pilih: (p: Pilihan) => void }) {
  const aktif = useBagianAktif()
  return (
    <div className="sticky top-0 z-20 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80 print:hidden">
      <div className="mx-auto flex w-full max-w-[100rem] flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-2.5 md:px-8">
        <nav aria-label="Bagian ikhtisar" className="-mx-1 flex max-w-full gap-1 overflow-x-auto">
          {BAGIAN.map((b) => (
            <a
              key={b.id}
              href={`#${b.id}`}
              aria-current={aktif === b.id ? "true" : undefined}
              className={cn(
                "rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap text-muted-foreground hover:bg-muted hover:text-foreground",
                aktif === b.id && "bg-muted font-medium text-foreground"
              )}
            >
              {b.judul}
            </a>
          ))}
        </nav>
        <div className="flex max-w-full items-center gap-2 overflow-x-auto">
          <ToggleGroup
            type="single"
            variant="outline"
            size="sm"
            value={"periode" in pilihan ? pilihan.periode : ""}
            onValueChange={(v) => v && pilih({ periode: v as Periode })}
            aria-label="Periode"
          >
            {(Object.keys(PERIODE) as Periode[]).map((k) => (
              <ToggleGroupItem key={k} value={k} className="px-3 whitespace-nowrap">
                {PERIODE[k]}
              </ToggleGroupItem>
            ))}
          </ToggleGroup>
          <PilihRentang data={data} pilihan={pilihan} pilih={pilih} />
        </div>
      </div>
    </div>
  )
}

/** Rentang tanggal sendiri, paling lama satu tahun */
function PilihRentang({ data, pilihan, pilih }: { data: Ikhtisar; pilihan: Pilihan; pilih: (p: Pilihan) => void }) {
  const [buka, setBuka] = React.useState(false)
  const [dari, setDari] = React.useState(data.dari)
  const [sampai, setSampai] = React.useState(data.sampai)
  const id = React.useId()
  const galat = !dari || !sampai ? "Isi kedua tanggal." : sampai < dari ? "Tanggal akhir tidak boleh sebelum tanggal awal." : jumlahHari(dari, sampai) > MAKS_HARI ? "Rentang paling lama satu tahun." : null
  const rentang = !("periode" in pilihan)

  return (
    <Popover
      open={buka}
      onOpenChange={(b) => {
        setBuka(b)
        if (b) {
          setDari(data.dari)
          setSampai(data.sampai)
        }
      }}
    >
      <PopoverTrigger asChild>
        <Button variant={rentang ? "secondary" : "outline"} size="sm" className="whitespace-nowrap">
          <IconCalendarEvent />
          {rentang ? labelPeriode(data.dari, data.sampai) : "Rentang…"}
        </Button>
      </PopoverTrigger>
      <PopoverContent align="end" className="grid w-72 gap-3">
        <form
          className="grid gap-3"
          onSubmit={(e) => {
            e.preventDefault()
            if (galat) return
            pilih({ dari, sampai })
            setBuka(false)
          }}
        >
          <div className="grid gap-1.5">
            <Label htmlFor={`${id}-dari`}>Dari tanggal</Label>
            <Input id={`${id}-dari`} type="date" value={dari} max={data.pembaruan.stsTerakhir ?? undefined} onChange={(e) => setDari(e.target.value)} />
          </div>
          <div className="grid gap-1.5">
            <Label htmlFor={`${id}-sampai`}>Sampai tanggal</Label>
            <Input id={`${id}-sampai`} type="date" value={sampai} onChange={(e) => setSampai(e.target.value)} />
          </div>
          {galat && dari && sampai && <p className="text-sm text-destructive">{galat}</p>}
          <Button type="submit" disabled={!!galat}>
            Tampilkan
          </Button>
        </form>
      </PopoverContent>
    </Popover>
  )
}

/** Bagian yang sedang terbaca di layar, untuk menandai tautannya di bilah navigasi */
function useBagianAktif(): string {
  const [aktif, setAktif] = React.useState<string>(BAGIAN[0].id)
  React.useEffect(() => {
    if (typeof IntersectionObserver === "undefined") return
    const pengamat = new IntersectionObserver(
      (entri) => {
        const terlihat = entri.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]
        if (terlihat) setAktif(terlihat.target.id)
      },
      { rootMargin: "-20% 0px -70% 0px" }
    )
    for (const b of BAGIAN) {
      const el = document.getElementById(b.id)
      if (el) pengamat.observe(el)
    }
    return () => pengamat.disconnect()
  }, [])
  return aktif
}

/* ---------- Bagian ---------- */

function Bagian({ id, judul, sumber, children }: { id: string; judul: string; sumber: React.ReactNode; children: React.ReactNode }) {
  return (
    <section id={id} aria-labelledby={`${id}-judul`} className="grid grid-cols-[minmax(0,1fr)] gap-4">
      <div className="grid gap-1">
        <h2 id={`${id}-judul`} className="text-lg font-heading font-(--fw-heading) md:text-xl">
          {judul}
        </h2>
        <p className="text-sm text-muted-foreground">{sumber}</p>
      </div>
      {children}
    </section>
  )
}

const laporanDari = (data: Ikhtisar): IntiLaporan => ({ ...data.penerimaan, dari: data.dari, sampai: data.sampai })

function BagianRingkasan({ data }: { data: Ikhtisar }) {
  const r = data.penerimaan.ringkasan
  const total = data.realisasi.baris.find((b) => b.jenis === "total")
  const p = data.pemetaan
  const sampaiRealisasi = data.realisasi.sampai ?? data.sampai

  return (
    <Bagian id="ringkasan" judul={`Ringkasan · ${labelPeriode(data.dari, data.sampai)}`} sumber="Setiap angka menyebut sumber dan tanggal datanya.">
      <div className="grid gap-4 lg:grid-cols-3">
        <KartuRingkasan
          judul="Penerimaan semua loket"
          nilai={rupiah(r.jumlah)}
          lencana={<BadgePerubahan nilai={perubahan(r.jumlah, data.penerimaan.pembanding?.ringkasan.jumlah)} />}
          isi={
            <>
              <strong className="font-semibold text-foreground">{angka(r.transaksi)} unit</strong> membayar · pajak provinsi {rupiah(bagianProvinsi(r))}
            </>
          }
          sumber={`STS semua loket, ${labelPeriode(data.dari, data.sampai)}. Termasuk opsen; satu transaksi = satu kendaraan.`}
        />
        <KartuRingkasan
          judul={`Realisasi target ${data.realisasi.tahun}`}
          nilai={data.realisasi.tersedia && total ? rupiah(total.sampaiIni) : "—"}
          lencana={total?.persen != null && <Badge variant="outline">{angka(total.persen, 2)}% dari target</Badge>}
          isi={
            !data.realisasi.tersedia ? (
              "Bulan ini tercatat dalam saldo awal."
            ) : total?.target ? (
              <div className="grid gap-2">
                {total.persen !== null && (
                  <div className="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                    <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(total.persen, 100)}%` }} />
                  </div>
                )}
                <span>Target {rupiah(total.target)}</span>
              </div>
            ) : (
              "Target tahun ini belum diisi."
            )
          }
          sumber={`Form I tanpa opsen, s.d. ${tanggalPanjang(sampaiRealisasi)}.`}
        />
        <KartuRingkasan
          judul="Kepatuhan PKB"
          nilai={p ? persenPotensi(p.jumlah.kelompok.taat, p.jumlah.kendaraan) : "—"}
          lencana={p && <Badge variant="outline">taat</Badge>}
          isi={
            p ? (
              <>
                {angka(p.jumlah.kelompok.taat)} dari {angka(p.jumlah.kendaraan)} kendaraan taat · belum bayar {angka(belumBayar(p.jumlah))} · menunggak{" "}
                {angka(p.jumlah.kelompok.menunggak)}
              </>
            ) : (
              "Data Potensi belum diimpor."
            )
          }
          sumber={p?.tanggal ? `Data Potensi BPAD, penetapan ${labelTanggal(p.tanggal, "lengkap")}. Tidak berubah oleh pembayaran STS sesudahnya.` : "Data Potensi BPAD."}
        />
      </div>
    </Bagian>
  )
}

function KartuRingkasan({
  judul,
  nilai,
  lencana,
  isi,
  sumber,
}: {
  judul: string
  nilai: string
  lencana?: React.ReactNode
  isi: React.ReactNode
  sumber: string
}) {
  return (
    <Card className="gap-3 bg-gradient-to-t from-primary/5 to-card shadow-xs">
      <CardHeader>
        <CardDescription className="text-sm font-medium">{judul}</CardDescription>
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <CardTitle className="text-3xl tabular-nums">{nilai}</CardTitle>
          {lencana}
        </div>
      </CardHeader>
      <CardContent className="text-sm text-muted-foreground">{isi}</CardContent>
      <CardFooter className="mt-auto border-t pt-3 text-xs text-muted-foreground">{sumber}</CardFooter>
    </Card>
  )
}

function BagianPenerimaan({ data }: { data: Ikhtisar }) {
  const laporan = laporanDari(data)
  const tersedia = data.pembaruan.tersedia
  return (
    <Bagian
      id="penerimaan"
      judul={`Penerimaan · ${labelPeriode(data.dari, data.sampai)}`}
      sumber={`Semua berkas STS yang diimpor, semua loket. Data sampai ${data.pembaruan.stsTerakhir ? labelTanggal(data.pembaruan.stsTerakhir, "lengkap") : "—"}.`}
    >
      {tersedia && data.dari < tersedia.awal && (
        <Alert>
          <IconInfoCircle />
          <AlertDescription>Data STS di PINTU tersedia mulai {labelTanggal(tersedia.awal, "lengkap")}; hari sebelumnya belum tercatat.</AlertDescription>
        </Alert>
      )}
      {laporan.ringkasan.transaksi === 0 ? (
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">Belum ada transaksi STS pada periode ini.</CardContent>
        </Card>
      ) : (
        <>
          <Ringkasan laporan={laporan} />
          <PerLoket laporan={laporan} adaSaringan={false} />
          {data.dari !== data.sampai && <GrafikHarian laporan={laporan} perBulan={jumlahHari(data.dari, data.sampai) > 62} />}
          <div className="grid grid-cols-[minmax(0,1fr)] gap-4 xl:grid-cols-2">
            <GrafikRincian laporan={laporan} dimensi="jenisDaftar" judul="Per jenis daftar" />
            <GrafikRincian laporan={laporan} dimensi="jenisKendaraan" judul="Per jenis kendaraan" />
            <GrafikRincian laporan={laporan} dimensi="kecamatan" judul="Per kecamatan" deskripsi="Kecamatan alamat pemilik menurut berkas STS." />
            <GrafikRincian laporan={laporan} dimensi="uptAsal" judul="Per asal UPT" deskripsi="UPT tempat kendaraan terdaftar." />
          </div>
        </>
      )}
    </Bagian>
  )
}

function BagianRealisasi({ data }: { data: Ikhtisar }) {
  const laporan = data.realisasi
  const tabel = React.useRef<HTMLTableElement>(null)
  return (
    <Bagian
      id="realisasi"
      judul={`Realisasi Target · ${laporan.sampai ? `s.d. ${tanggalPanjang(laporan.sampai)}` : `${NAMA_BULAN[laporan.bulan - 1]} ${laporan.tahun}`}`}
      sumber="Form I: PKB, BBNKB, dan dendanya dari STS semua loket terhadap target tahunan. Opsen kabupaten/kota tidak termasuk."
    >
      {!laporan.target && (
        <Alert>
          <IconInfoCircle />
          <AlertTitle>Target {laporan.tahun} belum diisi</AlertTitle>
          <AlertDescription>Realisasi tetap dihitung; kolom target dan persentase kosong sampai target diisi di dashboard.</AlertDescription>
        </Alert>
      )}
      {laporan.tersedia ? (
        <>
          <RingkasanRealisasi laporan={laporan} />
          <Card className="gap-4">
            <CardHeader className="flex flex-wrap items-start justify-between gap-3">
              <div className="grid gap-1.5">
                <CardTitle className="text-base">Form I</CardTitle>
                <CardDescription>Realisasi penerimaan gabungan semua loket, sama seperti berkas Excel di dashboard.</CardDescription>
              </div>
              <Button variant="outline" size="sm" className="print:hidden" onClick={() => tabel.current && cetakFormI(tabel.current, laporan)}>
                <IconPrinter />
                Cetak Form I
              </Button>
            </CardHeader>
            <CardContent className="px-0 sm:px-6">
              <TabelFormI laporan={laporan} ref={tabel} />
            </CardContent>
          </Card>
          <Sumber laporan={laporan} />
        </>
      ) : (
        <TertutupSaldo laporan={laporan} />
      )}
    </Bagian>
  )
}

function BagianPemetaan({ data }: { data: Ikhtisar }) {
  const p = data.pemetaan
  return (
    <Bagian
      id="pemetaan"
      judul="Pemetaan PKB"
      sumber={
        p?.tanggal
          ? `Data Potensi BPAD, penetapan ${labelTanggal(p.tanggal, "lengkap")}: status taat, belum bayar, dan menunggak per tanggal itu, tidak berubah oleh pembayaran STS sesudahnya.`
          : "Data Potensi BPAD."
      }
    >
      {p ? (
        <IsiRingkasanPemetaan
          ringkasan={p}
          kolom={p.kolom}
          kartuTambahan={
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Jatuh tempo per bulan</CardTitle>
                <CardDescription>Kendaraan menurut bulan jatuh tempo pajaknya.</CardDescription>
              </CardHeader>
              <CardContent>
                <DaftarBatang baris={(p.bulan ?? []).map((b) => ({ label: NAMA_BULAN[b.bulan - 1], nilai: b.kendaraan }))} />
              </CardContent>
            </Card>
          }
        />
      ) : (
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">Belum ada berkas Data Potensi yang diimpor.</CardContent>
        </Card>
      )}
    </Bagian>
  )
}

function KakiHalaman({ lupakan }: { lupakan: () => void }) {
  return (
    <footer className="grid gap-3 border-t pt-6 text-sm text-muted-foreground">
      <p>
        Khusus pegawai UPTD Pendapatan Daerah Wilayah Kota Kupang; jangan dibagikan ke luar. Halaman ini tidak memuat identitas wajib pajak.
      </p>
      <div className="flex flex-wrap gap-2 print:hidden">
        <Button variant="outline" size="sm" onClick={() => window.print()}>
          <IconPrinter />
          Cetak halaman
        </Button>
        <Button variant="ghost" size="sm" onClick={lupakan}>
          Lupakan tautan di perangkat ini
        </Button>
      </div>
    </footer>
  )
}

/* ---------- Keadaan ---------- */

function LayarPesan({ ikon, judul, aksi, children }: { ikon: React.ReactNode; judul: string; aksi?: React.ReactNode; children: React.ReactNode }) {
  return (
    <main className="grid min-h-svh place-items-center bg-background px-4">
      <div className="grid max-w-md justify-items-center gap-4 text-center">
        <span className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground [&_svg]:size-6" aria-hidden="true">
          {ikon}
        </span>
        <h1 className="text-xl font-heading font-(--fw-heading)">{judul}</h1>
        <p className="text-sm text-muted-foreground">{children}</p>
        {aksi}
      </div>
    </main>
  )
}

function KerangkaIkhtisar() {
  return (
    <div className="mx-auto grid w-full max-w-[100rem] gap-6 px-4 py-6 md:px-8" role="status" aria-label="Memuat ikhtisar">
      <Skeleton className="h-24 rounded-xl" />
      <div className="grid gap-4 lg:grid-cols-3">
        {[0, 1, 2].map((i) => (
          <Skeleton key={i} className="h-44 rounded-xl" />
        ))}
      </div>
      <Skeleton className="h-96 rounded-xl" />
    </div>
  )
}
