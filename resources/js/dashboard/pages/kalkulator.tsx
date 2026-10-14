import * as React from "react"
import {
  IconAlertCircle,
  IconArrowsExchange,
  IconCalendar,
  IconCircleCheck,
  IconCopy,
  IconInfoCircle,
  IconRestore,
  IconRosetteDiscountCheck,
  IconSearchOff,
  IconShieldCheck,
} from "@tabler/icons-react"
import { id as localeId } from "react-day-picker/locale"
import { toast } from "sonner"

import { Halaman, PengantarHalaman } from "@/components/data/page"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Separator } from "@/components/ui/separator"
import { Spinner } from "@/components/ui/spinner"
import { Switch } from "@/components/ui/switch"
import { ApiError } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"
import { angka, tanggal } from "@/lib/format"
import { cn } from "@/lib/utils"

import {
  BOBOT_MAP,
  calculateTax,
  dariTanggal,
  DEFAULT_WARNA_PLAT,
  hariIni,
  isWarnaPlat,
  JENIS_KENDARAAN,
  jatuhTempoSesudahTahunBayar,
  jenisDariData,
  keTanggal,
  keteranganMasa,
  keteranganTarif,
  persen,
  rincianPembayaran,
  ringkasanPenetapan,
  rupiah,
  tanggalPanjang,
  TARIF_PKB_PLAT_MERAH,
  warnaPlatDariData,
  WARNA_PLAT_LABEL,
  WARNA_PLAT_OPTIONS,
  type DataKendaraan,
  type JenisKendaraan,
  type TaxCalculationResult,
  type WarnaPlat,
} from "../../pkb"

/** Bentuk nomor polisi yang diterima API kendaraan: DH1234AB */
const POLA_NOPOL = /^[A-Z]{1,2}\d{1,4}[A-Z]{0,3}$/

const normalNopol = (s: string) => s.toUpperCase().replace(/[^A-Z0-9]/g, "")

type Pencarian =
  | { status: "kosong" }
  | { status: "format" }
  | { status: "memuat" }
  | { status: "ditemukan"; data: DataKendaraan }
  | { status: "tidak-ditemukan" }
  | { status: "galat"; pesan: string }

/**
 * Data kendaraan dari GET /api/kendaraan/{nopol} (D-041), dicari 450 ms
 * setelah nomor polisi berhenti diketik. Jawaban untuk nomor yang sudah
 * diganti diabaikan; tidak ada yang disimpan di luar halaman ini.
 */
function useCariKendaraan(nopol: string, isi: (data: DataKendaraan) => void) {
  const normal = normalNopol(nopol)
  const sah = POLA_NOPOL.test(normal)
  const [jawaban, setJawaban] = React.useState<{ nopol: string; hasil: Pencarian } | null>(null)

  React.useEffect(() => {
    if (!sah) return
    let batal = false
    const tunda = window.setTimeout(() => {
      mintaServer<DataKendaraan>(`/api/kendaraan/${encodeURIComponent(normal)}`, "GET").then(
        (data) => {
          if (batal) return
          setJawaban({ nopol: normal, hasil: { status: "ditemukan", data } })
          isi(data)
        },
        (e: unknown) => {
          if (batal) return
          setJawaban({
            nopol: normal,
            hasil:
              e instanceof ApiError && e.status === 404
                ? { status: "tidak-ditemukan" }
                : { status: "galat", pesan: e instanceof Error ? e.message : "Data kendaraan gagal diambil." },
          })
        }
      )
    }, 450)
    return () => {
      batal = true
      window.clearTimeout(tunda)
    }
  }, [normal, sah, isi])

  const hasil: Pencarian =
    normal.length < 4
      ? { status: "kosong" }
      : !sah
        ? { status: "format" }
        : jawaban?.nopol === normal
          ? jawaban.hasil
          : { status: "memuat" }
  return { normal, hasil, lupakan: () => setJawaban(null) }
}

/** Isian rupiah: angka mentah saat diketik, berformat ribuan saat tidak difokus */
function InputRupiah({
  id,
  nilai,
  ubah,
  describedBy,
}: {
  id: string
  nilai: number
  ubah: (n: number) => void
  describedBy?: string
}) {
  const [fokus, setFokus] = React.useState(false)
  const [teks, setTeks] = React.useState("")
  return (
    <div className="relative">
      <span
        aria-hidden="true"
        className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
      >
        Rp
      </span>
      <Input
        id={id}
        inputMode="numeric"
        autoComplete="off"
        className="pl-9 tabular-nums"
        value={fokus ? teks : nilai ? angka(nilai) : ""}
        placeholder="0"
        aria-describedby={describedBy}
        onFocus={(e) => {
          setFokus(true)
          setTeks(nilai ? String(nilai) : "")
          const el = e.currentTarget
          requestAnimationFrame(() => el.select())
        }}
        onBlur={() => setFokus(false)}
        onChange={(e) => {
          const bersih = e.target.value.replace(/\D/g, "").replace(/^0+(?=\d)/, "").slice(0, 13)
          setTeks(bersih)
          ubah(bersih ? Number(bersih) : 0)
        }}
      />
    </div>
  )
}

function PilihTanggal({
  id,
  nilai,
  ubah,
  describedBy,
}: {
  id: string
  nilai: string
  ubah: (iso: string) => void
  describedBy?: string
}) {
  const [buka, setBuka] = React.useState(false)
  const terpilih = keTanggal(nilai)
  return (
    <Popover open={buka} onOpenChange={setBuka}>
      <PopoverTrigger asChild>
        <Button
          id={id}
          variant="outline"
          aria-describedby={describedBy}
          className={cn("w-full justify-start font-normal", !terpilih && "text-muted-foreground")}
        >
          <IconCalendar />
          {terpilih ? tanggal(nilai, "panjang") : "Pilih tanggal"}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto overflow-hidden p-0" align="start">
        <Calendar
          mode="single"
          locale={localeId}
          selected={terpilih}
          defaultMonth={terpilih}
          captionLayout="dropdown"
          formatters={{
            formatMonthDropdown: (d) => d.toLocaleString("id-ID", { month: "short" }),
          }}
          startMonth={new Date(2000, 0)}
          endMonth={new Date(new Date().getFullYear() + 6, 11)}
          onSelect={(d) => {
            if (d) ubah(dariTanggal(d))
            setBuka(false)
          }}
        />
      </PopoverContent>
    </Popover>
  )
}

function PesanPencarian({ hasil }: { hasil: Pencarian }) {
  switch (hasil.status) {
    case "kosong":
      return (
        <p className="text-sm text-muted-foreground">
          Data kendaraan terisi otomatis bila nomor polisi terdaftar.
        </p>
      )
    case "format":
      return <p className="text-sm text-muted-foreground">Tulis nomor polisi seperti DH 1234 AB.</p>
    case "memuat":
      return <p className="text-sm text-muted-foreground">Memeriksa data kendaraan…</p>
    case "ditemukan":
      return (
        <div className="rounded-lg bg-success-soft px-3 py-2 text-sm">
          <p className="text-xs font-medium text-success">Data ditemukan</p>
          <p className="mt-0.5 font-medium">{hasil.data.nama ?? "Nama pemilik tidak tersedia"}</p>
          {hasil.data.njkb <= 0 && (
            <p className="mt-1 text-muted-foreground">
              Referensi NJKB tidak tersedia. Isi NJKB secara manual.
            </p>
          )}
          {hasil.data.jenis && !jenisDariData(hasil.data.jenis) && (
            <p className="mt-1 text-muted-foreground">
              Jenis {hasil.data.jenis} tidak ada di daftar. Pilih jenis kendaraan secara manual.
            </p>
          )}
        </div>
      )
    case "tidak-ditemukan":
      return (
        <div className="flex items-start gap-2 rounded-lg bg-warning-soft px-3 py-2 text-sm">
          <IconSearchOff className="mt-0.5 size-4 shrink-0 text-warning" aria-hidden="true" />
          <p>
            <span className="font-medium">Kendaraan tidak ditemukan.</span>{" "}
            <span className="text-muted-foreground">Lengkapi data kendaraan secara manual.</span>
          </p>
        </div>
      )
    case "galat":
      return (
        <div className="flex items-start gap-2 rounded-lg bg-danger-soft px-3 py-2 text-sm">
          <IconAlertCircle className="mt-0.5 size-4 shrink-0 text-destructive" aria-hidden="true" />
          <p>
            {hasil.pesan} <span className="text-muted-foreground">Data kendaraan bisa diisi manual.</span>
          </p>
        </div>
      )
  }
}

function Fasilitas({
  id,
  judul,
  keterangan,
  ikon,
  aktif,
  ubah,
}: {
  id: string
  judul: string
  keterangan: string
  ikon: React.ReactNode
  aktif: boolean
  ubah: (aktif: boolean) => void
}) {
  return (
    <div className="flex items-start justify-between gap-4 rounded-lg border p-4">
      <div className="flex gap-3">
        <span className="mt-0.5 text-muted-foreground [&_svg]:size-4" aria-hidden="true">
          {ikon}
        </span>
        <div className="grid gap-1">
          <Label htmlFor={id}>{judul}</Label>
          <p id={`${id}-ket`} className="text-sm text-muted-foreground">
            {keterangan}
          </p>
        </div>
      </div>
      <Switch id={id} checked={aktif} onCheckedChange={ubah} aria-describedby={`${id}-ket`} />
    </div>
  )
}

function Rincian({ hasil }: { hasil: TaxCalculationResult }) {
  return (
    <div className="grid gap-4">
      {rincianPembayaran(hasil).map((k) => (
        <section key={k.judul} aria-label={k.judul} className="grid gap-1.5">
          <div className="flex justify-between gap-4 text-sm font-medium">
            <h3>{k.judul}</h3>
            <span className="tabular-nums">{rupiah(k.jumlah)}</span>
          </div>
          <dl className="grid gap-1 border-l pl-3 text-sm">
            {k.baris.map((b) => (
              <div key={b.label} className="flex justify-between gap-4">
                <dt className="text-muted-foreground">{b.label}</dt>
                <dd className={cn("shrink-0 tabular-nums", b.dibebaskan && "text-success")}>{rupiah(b.nilai)}</dd>
              </div>
            ))}
          </dl>
        </section>
      ))}
    </div>
  )
}

const daftarKata = (kata: string[]) =>
  kata.length > 1 ? `${kata.slice(0, -1).join(", ")} dan ${kata[kata.length - 1]}` : (kata[0] ?? "")

function Kalkulator() {
  const id = React.useId()
  const [nopol, setNopol] = React.useState("")
  const [njkb, setNjkb] = React.useState(0)
  const [njub, setNjub] = React.useState(0)
  /** Bobot dari data kendaraan; tanpa itu bobot mengikuti jenis kendaraan */
  const [bobotData, setBobotData] = React.useState<number>()
  const [jenis, setJenis] = React.useState<JenisKendaraan>()
  const [jatuhTempoPajak, setJatuhTempoPajak] = React.useState("")
  const [jatuhTempoStnk, setJatuhTempoStnk] = React.useState("")
  const [tanggalBayar, setTanggalBayar] = React.useState(hariIni)
  const [warnaPlat, setWarnaPlat] = React.useState<WarnaPlat>(DEFAULT_WARNA_PLAT)
  const [gempa, setGempa] = React.useState(false)
  const [mutasi, setMutasi] = React.useState(false)

  const isiDariData = React.useCallback((d: DataKendaraan) => {
    setNjkb(d.njkb > 0 ? d.njkb : 0)
    setNjub(d.njub || 0)
    setBobotData(d.bobot || undefined)
    setJenis(jenisDariData(d.jenis))
    setJatuhTempoPajak(keTanggal(d.jatuhTempoPajak) ? (d.jatuhTempoPajak ?? "") : "")
    setJatuhTempoStnk(keTanggal(d.jatuhTempoStnk) ? (d.jatuhTempoStnk ?? "") : "")
    setWarnaPlat(warnaPlatDariData(d.warnaPlat))
  }, [])
  const { normal, hasil: pencarian, lupakan } = useCariKendaraan(nopol, isiDariData)

  /** Data kendaraan dikosongkan setiap nomor polisi berubah; tanggal pembayaran tetap */
  const kosongkanKendaraan = () => {
    lupakan()
    setNjkb(0)
    setNjub(0)
    setBobotData(undefined)
    setJenis(undefined)
    setJatuhTempoPajak("")
    setJatuhTempoStnk("")
    setWarnaPlat(DEFAULT_WARNA_PLAT)
    setGempa(false)
    setMutasi(false)
  }

  const bobot = bobotData ?? (jenis ? BOBOT_MAP[jenis] : undefined)
  const tglPajak = keTanggal(jatuhTempoPajak)
  const tglStnk = keTanggal(jatuhTempoStnk)
  const tglBayar = keTanggal(tanggalBayar)

  const kurang = [
    njkb > 0 ? null : "NJKB",
    jenis ? null : "jenis kendaraan",
    tglPajak ? null : "jatuh tempo pajak",
    tglStnk ? null : "jatuh tempo STNK",
    tglBayar ? null : "tanggal pembayaran",
  ].filter((k): k is string => k !== null)
  const lunas =
    tglPajak && tglBayar && jatuhTempoSesudahTahunBayar(tglPajak, tglBayar)
      ? { tahunBayar: tglBayar.getFullYear(), tahunPajak: tglPajak.getFullYear() }
      : null

  const hasil =
    !lunas && jenis && bobot && njkb > 0 && tglPajak && tglStnk && tglBayar
      ? calculateTax({
          njkb,
          njub,
          bobot,
          jenisKendaraan: jenis,
          jatuhTempoPajak: tglPajak,
          jatuhTempoStnk: tglStnk,
          tanggalBayar: tglBayar,
          isDomisiliGempa: gempa,
          isMutasiMasuk: mutasi,
          isTembakRu: false,
          warnaPlat,
        })
      : null

  const platData =
    pencarian.status === "ditemukan" && isWarnaPlat(pencarian.data.warnaPlat) ? pencarian.data.warnaPlat : null
  const keteranganPlat =
    pencarian.status === "ditemukan"
      ? platData
        ? warnaPlat === platData
          ? "Terisi otomatis dari data kendaraan."
          : `Diubah manual (data kendaraan: ${WARNA_PLAT_LABEL[platData]}).`
        : warnaPlat === DEFAULT_WARNA_PLAT
          ? `Data kendaraan tidak menyebut warna plat. Default ${WARNA_PLAT_LABEL[DEFAULT_WARNA_PLAT]}; ubah bila kendaraan dinas (plat merah).`
          : "Dipilih manual; data kendaraan tidak menyebut warna plat."
      : `Pilih sesuai TNKB kendaraan. Default ${WARNA_PLAT_LABEL[DEFAULT_WARNA_PLAT]}.`

  const salin = async () => {
    if (!hasil) return
    try {
      await navigator.clipboard.writeText(ringkasanPenetapan(hasil, POLA_NOPOL.test(normal) ? normal : undefined))
      toast.success("Ringkasan disalin")
    } catch {
      toast.error("Ringkasan gagal disalin", { description: "Peramban tidak mengizinkan akses papan klip." })
    }
  }

  const aturUlang = () => {
    setNopol("")
    kosongkanKendaraan()
    setTanggalBayar(hariIni())
  }

  const tarif = hasil && keteranganTarif(hasil)

  return (
    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start">
      <Card className="gap-6">
        <CardHeader>
          <CardTitle className="text-base">Data kendaraan</CardTitle>
          <CardDescription>
            Masukkan nomor polisi. Bila tidak ditemukan, isi datanya sesuai notis pajak dan STNK.
          </CardDescription>
        </CardHeader>
        <CardContent className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor={`${id}-nopol`}>Nomor polisi</Label>
            <div className="relative">
              <Input
                id={`${id}-nopol`}
                value={nopol}
                onChange={(e) => {
                  kosongkanKendaraan()
                  setNopol(e.target.value.toUpperCase())
                }}
                placeholder="DH 1234 AB"
                autoComplete="off"
                autoCapitalize="characters"
                spellCheck={false}
                maxLength={12}
                aria-describedby={`${id}-nopol-ket`}
                className="h-11 pr-10 text-lg font-medium uppercase md:text-lg"
              />
              <span className="pointer-events-none absolute inset-y-0 right-3 flex items-center" aria-hidden="true">
                {pencarian.status === "memuat" && <Spinner className="size-4 text-muted-foreground" />}
                {pencarian.status === "ditemukan" && <IconCircleCheck className="size-4 text-success" />}
              </span>
            </div>
            <div id={`${id}-nopol-ket`} aria-live="polite">
              <PesanPencarian hasil={pencarian} />
            </div>
          </div>

          <Separator />

          <fieldset className="grid min-w-0 gap-4">
            <legend className="mb-1 font-medium">Dasar pengenaan</legend>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="grid gap-2">
                <Label htmlFor={`${id}-njkb`}>NJKB</Label>
                <InputRupiah
                  id={`${id}-njkb`}
                  nilai={njkb}
                  ubah={(n) => {
                    setNjkb(n)
                    setBobotData(undefined)
                  }}
                  describedBy={`${id}-njkb-ket`}
                />
                <p id={`${id}-njkb-ket`} className="text-sm text-muted-foreground">
                  Nilai jual kendaraan bermotor.
                </p>
              </div>
              <div className="grid gap-2">
                <Label htmlFor={`${id}-njub`}>NJUB</Label>
                <InputRupiah id={`${id}-njub`} nilai={njub} ubah={setNjub} describedBy={`${id}-njub-ket`} />
                <p id={`${id}-njub-ket`} className="text-sm text-muted-foreground">
                  Nilai jual ubah bentuk, bila ada.
                </p>
              </div>
            </div>
            <div className="grid gap-2">
              <Label htmlFor={`${id}-jenis`}>Jenis kendaraan</Label>
              <Select
                value={jenis ?? ""}
                onValueChange={(v) => {
                  setJenis(v as JenisKendaraan)
                  setBobotData(undefined)
                }}
              >
                <SelectTrigger id={`${id}-jenis`} className="w-full" aria-describedby={`${id}-jenis-ket`}>
                  <SelectValue placeholder="Pilih jenis kendaraan" />
                </SelectTrigger>
                <SelectContent>
                  {JENIS_KENDARAAN.map((j) => (
                    <SelectItem key={j.value} value={j.value}>
                      {j.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p id={`${id}-jenis-ket`} className="text-sm text-muted-foreground">
                {bobotData !== undefined
                  ? `Bobot ${bobotData.toLocaleString("id-ID", { maximumFractionDigits: 3 })} dari data kendaraan.`
                  : "Bobot mengikuti jenis kendaraan."}
              </p>
            </div>
            <div className="grid gap-2">
              <Label id={`${id}-plat`}>Warna plat (TNKB)</Label>
              <RadioGroup
                value={warnaPlat}
                onValueChange={(v) => setWarnaPlat(v as WarnaPlat)}
                aria-labelledby={`${id}-plat`}
                aria-describedby={`${id}-plat-ket`}
                className="grid grid-cols-3 gap-2 sm:max-w-sm"
              >
                {WARNA_PLAT_OPTIONS.map((w) => (
                  <Label
                    key={w}
                    htmlFor={`${id}-plat-${w}`}
                    className="gap-2 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                  >
                    <RadioGroupItem value={w} id={`${id}-plat-${w}`} />
                    {WARNA_PLAT_LABEL[w]}
                  </Label>
                ))}
              </RadioGroup>
              <div id={`${id}-plat-ket`} className="grid gap-1 text-sm text-muted-foreground">
                <p>{keteranganPlat}</p>
                {warnaPlat === "merah" && (
                  <p className="flex items-start gap-1.5 font-medium text-foreground">
                    <IconInfoCircle className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    Kendaraan dinas: tarif PKB {persen(TARIF_PKB_PLAT_MERAH)} untuk semua periode.
                  </p>
                )}
                <p>Plat kuning (angkutan umum) dihitung di loket.</p>
              </div>
            </div>
          </fieldset>

          <Separator />

          <fieldset className="grid min-w-0 gap-4">
            <legend className="mb-1 font-medium">Masa pajak</legend>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="grid content-start gap-2">
                <Label htmlFor={`${id}-jt-pajak`}>Jatuh tempo pajak</Label>
                <PilihTanggal id={`${id}-jt-pajak`} nilai={jatuhTempoPajak} ubah={setJatuhTempoPajak} />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor={`${id}-jt-stnk`}>Jatuh tempo STNK</Label>
                <PilihTanggal id={`${id}-jt-stnk`} nilai={jatuhTempoStnk} ubah={setJatuhTempoStnk} />
              </div>
              <div className="grid content-start gap-2">
                <Label htmlFor={`${id}-bayar`}>Tanggal pembayaran</Label>
                <PilihTanggal id={`${id}-bayar`} nilai={tanggalBayar} ubah={setTanggalBayar} />
              </div>
            </div>
          </fieldset>

          <Separator />

          <fieldset className="grid min-w-0 gap-3">
            <legend className="mb-1 font-medium">Fasilitas</legend>
            <Fasilitas
              id={`${id}-gempa`}
              judul="Wilayah terdampak gempa (diskon tunggakan 75%)"
              keterangan="Sikka, Ende, Nagekeo, Ngada, Manggarai, Manggarai Timur, Manggarai Barat."
              ikon={<IconShieldCheck />}
              aktif={gempa}
              ubah={setGempa}
            />
            <Fasilitas
              id={`${id}-mutasi`}
              judul="Mutasi masuk luar daerah (diskon PKB berjalan 50%)"
              keterangan="Menggantikan diskon pembayaran awal pada PKB berjalan."
              ikon={<IconArrowsExchange />}
              aktif={mutasi}
              ubah={setMutasi}
            />
          </fieldset>
        </CardContent>
      </Card>

      <Card className="gap-5 lg:sticky lg:top-4">
        <CardHeader>
          <CardDescription>Estimasi total bayar</CardDescription>
          <CardTitle>
            <output aria-live="polite" className="font-display text-4xl font-semibold tracking-tight tabular-nums">
              {hasil ? rupiah(hasil.total) : "—"}
            </output>
          </CardTitle>
          {hasil && <p className="text-sm text-muted-foreground">{keteranganMasa(hasil)}</p>}
          {tarif && (
            <Badge variant="outline" className="mt-1 w-fit text-muted-foreground">
              {tarif}
            </Badge>
          )}
        </CardHeader>
        <CardContent className="grid gap-4">
          {hasil ? (
            <>
              <p className="flex gap-2 rounded-lg bg-success-soft p-3 text-sm">
                <IconRosetteDiscountCheck className="mt-0.5 size-4 shrink-0 text-success" aria-hidden="true" />
                Denda keterlambatan PKB dan opsen dibebaskan 100%. Pokok pajak dan SWDKLLJ tetap dihitung.
              </p>
              <Rincian hasil={hasil} />
            </>
          ) : lunas ? (
            <Alert>
              <IconInfoCircle />
              <AlertTitle>PKB tahun {lunas.tahunBayar} sudah dibayar</AlertTitle>
              <AlertDescription>
                Jatuh tempo pajak {tanggalPanjang(jatuhTempoPajak)} berada di tahun sesudah tanggal pembayaran. Untuk
                menghitung tagihan berikutnya, pilih tanggal pembayaran di tahun {lunas.tahunPajak}.
              </AlertDescription>
            </Alert>
          ) : (
            <p className="text-sm text-muted-foreground">
              Lengkapi {daftarKata(kurang)} untuk melihat rincian pembayaran.
            </p>
          )}
          <p className="text-xs text-muted-foreground">
            Dihitung menurut Pergub NTT Nomor 54 Tahun 2026. Tagihan resmi mengikuti notis pajak di loket.
          </p>
        </CardContent>
        <CardFooter className="flex-wrap gap-2 border-t">
          <Button type="button" disabled={!hasil} onClick={() => void salin()}>
            <IconCopy />
            Salin ringkasan
          </Button>
          <Button type="button" variant="ghost" onClick={aturUlang}>
            <IconRestore />
            Atur ulang
          </Button>
        </CardFooter>
      </Card>
    </div>
  )
}

export default function HalamanKalkulator() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Hitung PKB, opsen, SWDKLLJ, dan PNBP untuk wajib pajak di loket menurut Pergub NTT Nomor 54 Tahun 2026. Nomor polisi mengisi data kendaraan dari Samsat; denda PKB dan opsen dibebaskan." />
      <Kalkulator />
    </Halaman>
  )
}
