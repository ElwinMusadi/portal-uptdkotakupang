import * as React from "react"
import {
  IconArrowDown,
  IconArrowUp,
  IconCheck,
  IconClockHour4,
  IconCopy,
  IconListNumbers,
  IconMapPin,
  IconPlus,
  IconRefresh,
  IconSelector,
  IconServer,
} from "@tabler/icons-react"
import { useQueryClient } from "@tanstack/react-query"
import type { Control, UseFormReturn } from "react-hook-form"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldSakelar, FieldTanggal, FieldTeks } from "@/components/form/fields"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form"
import { Label } from "@/components/ui/label"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Switch } from "@/components/ui/switch"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import {
  diServer,
  type Draf,
  type Hari,
  type JadwalKeliling,
  type JenisLayananLuar,
  type LokasiLayanan,
  type UnitKeliling,
} from "@/lib/api"
import { mintaServer } from "@/lib/api/http"
import { hariIniIso, jamTitik, tanggal } from "@/lib/format"
import { HARI_LABEL, JENIS_LAYANAN_LUAR, namaLayananLuar } from "@/lib/meta"
import { laporGalat, qk, useKoleksi, useUbahEntri } from "@/lib/queries"
import { susunPapan, urutJam, useSimpanUrutanPapan, useUrutanPapan } from "@/lib/urutan-papan"
import { cn } from "@/lib/utils"
import * as v from "@/lib/validasi"

const KECAMATAN = ["Alak", "Kelapa Lima", "Kota Lama", "Kota Raja", "Maulafa", "Oebobo"].map((k) => `Kec. ${k}`)
const UNIT: UnitKeliling[] = ["01", "02", "03"]
/** Senin sampai Minggu */
const URUT_HARI: Hari[] = [1, 2, 3, 4, 5, 6, 0]
const HARI_SINGKAT: Record<Hari, string> = { 0: "Min", 1: "Sen", 2: "Sel", 3: "Rab", 4: "Kam", 5: "Jum", 6: "Sab" }
const JENIS: JenisLayananLuar[] = ["keliling", "gerai", "sementara"]
const FILTER_AWAL = { waktu: "akan-datang" }
const TOMBOL_TAMBAH: Record<JenisLayananLuar, string> = { keliling: "Tambah jadwal", gerai: "Tambah gerai", sementara: "Tambah loket" }
const NAMA_ENTRI: Record<JenisLayananLuar, string> = {
  keliling: "jadwal Samsat Keliling",
  gerai: "gerai tetap",
  sementara: "loket sementara",
}

const isJenis = (x: string | null): x is JenisLayananLuar => JENIS.includes(x as JenisLayananLuar)

const schemaLokasi = z.object({
  nama: v.wajib("Nama lokasi").max(120, "Maksimal 120 karakter."),
  kecamatan: v.wajib("Kecamatan").max(60, "Maksimal 60 karakter."),
})
type FormLokasi = z.infer<typeof schemaLokasi>

/** Hari dari tanggal YYYY-MM-DD (0 = Minggu) */
function hariDari(iso: string): Hari {
  const [y, m, d] = iso.split("-").map(Number)
  return new Date(Date.UTC(y, m - 1, d)).getUTCDay() as Hari
}

function geserHari(iso: string, n: number): string {
  const [y, m, d] = iso.split("-").map(Number)
  return new Date(Date.UTC(y, m - 1, d + n)).toISOString().slice(0, 10)
}

const seninDari = (iso: string) => geserHari(iso, -((hariDari(iso) + 6) % 7))

/** "Senin–Jumat", "Setiap hari", atau "Sen, Rab, Jum" */
function ringkasHari(hari: Hari[]): string {
  const urut = URUT_HARI.filter((h) => hari.includes(h))
  if (urut.length === 7) return "Setiap hari"
  const posisi = urut.map((h) => URUT_HARI.indexOf(h))
  const beruntun = posisi.every((p, i) => i === 0 || p === posisi[i - 1] + 1)
  if (urut.length >= 3 && beruntun) return `${HARI_LABEL[urut[0]]}–${HARI_LABEL[urut[urut.length - 1]]}`
  return urut.map((h) => (urut.length === 1 ? HARI_LABEL[h] : HARI_SINGKAT[h])).join(", ")
}

function rentangTanggal(mulai: string | null, selesai: string | null): string {
  if (mulai && selesai) return mulai === selesai ? tanggal(mulai) : `${tanggal(mulai)} – ${tanggal(selesai)}`
  if (mulai) return `Mulai ${tanggal(mulai)}`
  if (selesai) return `Sampai ${tanggal(selesai)}`
  return "Tanpa batas"
}

const jam = (j: JadwalKeliling) => `${jamTitik(j.mulai)}–${jamTitik(j.selesai)}`

const schema = z
  .object({
    jenis: z.enum(["keliling", "gerai", "sementara"]),
    unit: z.string(),
    nama: v.teks.max(120, "Maksimal 120 karakter."),
    tanggalMulai: z.string(),
    tanggalSelesai: z.string(),
    hari: z.array(z.number()),
    mulai: v.jam.refine((x) => x !== "", "Isi jam mulai."),
    selesai: v.jam.refine((x) => x !== "", "Isi jam selesai."),
    lokasi: v.wajib("Lokasi").max(120, "Maksimal 120 karakter."),
    kecamatan: v.wajib("Kecamatan").max(60, "Maksimal 60 karakter."),
    layanan: v.wajib("Layanan").max(80, "Maksimal 80 karakter."),
    catatan: v.teks.max(300, "Maksimal 300 karakter."),
    aktif: z.boolean(),
  })
  .superRefine((d, ctx) => {
    const galat = (path: string, message: string) => ctx.addIssue({ code: "custom", path: [path], message })
    if (d.mulai && d.selesai && d.selesai <= d.mulai) galat("selesai", "Jam selesai harus setelah jam mulai.")
    if (d.jenis === "keliling") {
      if (!d.unit) galat("unit", "Pilih unit Samsat Keliling.")
      if (!d.tanggalMulai) galat("tanggalMulai", "Pilih tanggal layanan.")
      return
    }
    if (!d.nama.trim()) galat("nama", d.jenis === "gerai" ? "Isi nama gerai." : "Isi nama loket.")
    if (d.hari.length === 0) galat("hari", "Pilih hari layanan.")
    if (d.jenis === "sementara") {
      if (!d.tanggalMulai) galat("tanggalMulai", "Isi tanggal mulai.")
      if (!d.tanggalSelesai) galat("tanggalSelesai", "Isi tanggal selesai.")
    }
    if (d.tanggalMulai && d.tanggalSelesai && d.tanggalSelesai < d.tanggalMulai) {
      galat("tanggalSelesai", "Tanggal selesai tidak boleh sebelum tanggal mulai.")
    }
  })
type FormLayanan = z.infer<typeof schema>

function kosongUntuk(jenis: JenisLayananLuar): FormLayanan {
  const hariIni = hariIniIso()
  return {
    jenis,
    unit: jenis === "keliling" ? "01" : "",
    nama: "",
    tanggalMulai: jenis === "gerai" ? "" : hariIni,
    tanggalSelesai: jenis === "sementara" ? geserHari(hariIni, 4) : "",
    hari: jenis === "keliling" ? [] : [1, 2, 3, 4, 5],
    mulai: "08:00",
    selesai: jenis === "gerai" ? "15:00" : "12:00",
    lokasi: "",
    kecamatan: KECAMATAN[0],
    layanan: "Pajak tahunan",
    catatan: "",
    aktif: true,
  }
}

/** Data yang dikirim ke server dari nilai formulir */
function keData(f: FormLayanan): Draf<JadwalKeliling> {
  const dasar = {
    mulai: f.mulai,
    selesai: f.selesai,
    lokasi: f.lokasi,
    kecamatan: f.kecamatan,
    layanan: f.layanan,
    catatan: f.catatan,
    aktif: f.aktif,
  }
  if (f.jenis === "keliling") {
    return {
      ...dasar,
      jenis: "keliling",
      unit: f.unit as UnitKeliling,
      nama: "",
      tanggalMulai: f.tanggalMulai,
      tanggalSelesai: f.tanggalMulai,
      hari: [hariDari(f.tanggalMulai)],
    }
  }
  return {
    ...dasar,
    jenis: f.jenis,
    unit: null,
    nama: f.nama.trim(),
    tanggalMulai: f.tanggalMulai || null,
    tanggalSelesai: f.tanggalSelesai || null,
    hari: URUT_HARI.filter((h) => f.hari.includes(h)),
  }
}

function FieldHari({ control }: { control: Control<FormLayanan> }) {
  return (
    <FormField
      control={control}
      name="hari"
      render={({ field }) => (
        <FormItem>
          <FormLabel>
            Hari layanan
            <span aria-hidden="true" className="text-destructive">
              *
            </span>
          </FormLabel>
          <FormControl>
            <ToggleGroup
              type="multiple"
              variant="outline"
              spacing={1}
              value={field.value.map(String)}
              onValueChange={(nilai) => field.onChange(nilai.map(Number))}
              onBlur={field.onBlur}
              ref={field.ref}
              className="w-full flex-wrap"
            >
              {URUT_HARI.map((h) => (
                <ToggleGroupItem key={h} value={String(h)} aria-label={HARI_LABEL[h]} className="flex-1">
                  {HARI_SINGKAT[h]}
                </ToggleGroupItem>
              ))}
            </ToggleGroup>
          </FormControl>
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

/**
 * Lokasi dipilih dari daftar (sekaligus mengisi kecamatannya) atau diketik
 * sendiri bila belum ada; lokasi yang diketik masuk daftar saat jadwal
 * disimpan (D-044).
 */
function FieldLokasi({ form, lokasi, contoh }: { form: UseFormReturn<FormLayanan>; lokasi: LokasiLayanan[]; contoh: string }) {
  const [buka, setBuka] = React.useState(false)
  const [cari, setCari] = React.useState("")
  const ketik = cari.trim()
  const adaDiDaftar = lokasi.some((l) => l.nama.toLowerCase() === ketik.toLowerCase())

  return (
    <FormField
      control={form.control}
      name="lokasi"
      render={({ field }) => {
        const pilih = (nama: string, kecamatan?: string) => {
          field.onChange(nama)
          if (kecamatan) form.setValue("kecamatan", kecamatan, { shouldDirty: true, shouldValidate: true })
          setBuka(false)
        }
        return (
          <FormItem>
            <FormLabel>
              Lokasi
              <span aria-hidden="true" className="text-destructive">
                *
              </span>
            </FormLabel>
            <Popover
              open={buka}
              onOpenChange={(o) => {
                setBuka(o)
                if (o) setCari("")
              }}
            >
              <PopoverTrigger asChild>
                <FormControl>
                  <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={buka}
                    ref={field.ref}
                    onBlur={field.onBlur}
                    className={cn("w-full justify-between font-normal", !field.value && "text-muted-foreground")}
                  >
                    <span className="truncate">{field.value || `Pilih atau ketik lokasi, mis. ${contoh}`}</span>
                    <IconSelector className="opacity-50" />
                  </Button>
                </FormControl>
              </PopoverTrigger>
              <PopoverContent className="w-(--radix-popover-trigger-width) p-0" align="start">
                <Command>
                  <CommandInput placeholder="Cari, atau ketik lokasi baru…" value={cari} onValueChange={setCari} />
                  <CommandList>
                    <CommandEmpty>{ketik ? "Tidak ada di daftar." : "Daftar lokasi masih kosong."}</CommandEmpty>
                    {lokasi.length > 0 && (
                      <CommandGroup heading="Daftar lokasi">
                        {lokasi.map((l) => (
                          <CommandItem key={l.id} value={`${l.nama} ${l.kecamatan}`} onSelect={() => pilih(l.nama, l.kecamatan)}>
                            <IconMapPin />
                            <span className="truncate">{l.nama}</span>
                            <span className="ml-auto shrink-0 text-xs text-muted-foreground">{l.kecamatan}</span>
                            <IconCheck className={cn(field.value === l.nama ? "opacity-100" : "opacity-0")} />
                          </CommandItem>
                        ))}
                      </CommandGroup>
                    )}
                    {ketik && !adaDiDaftar && (
                      <CommandGroup>
                        <CommandItem forceMount value={`baru ${ketik}`} onSelect={() => pilih(ketik)}>
                          <IconPlus />
                          <span className="truncate">Gunakan “{ketik}”</span>
                          <span className="ml-auto shrink-0 text-xs text-muted-foreground">lokasi baru</span>
                        </CommandItem>
                      </CommandGroup>
                    )}
                  </CommandList>
                </Command>
              </PopoverContent>
            </Popover>
            <FormDescription>Pilih dari daftar, atau ketik lokasi baru. Lokasi baru masuk daftar saat jadwal disimpan.</FormDescription>
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

/** Salin semua jadwal Samsat Keliling pada satu hari ke hari-hari yang dicentang (D-044) */
function DialogSalinHari({
  sumber,
  senin,
  data,
  tutup,
}: {
  sumber: string | null
  senin: string
  data: JadwalKeliling[]
  tutup: () => void
}) {
  const [terpilih, setTerpilih] = React.useState<string[]>([])
  const [menyalin, setMenyalin] = React.useState(false)
  const qc = useQueryClient()
  const hariIni = hariIniIso()
  const titik = sumber ? data.filter((x) => x.jenis === "keliling" && x.tanggalMulai === sumber) : []
  const minggu = [
    { judul: "Minggu yang sama", hari: Array.from({ length: 7 }, (_, i) => geserHari(senin, i)) },
    { judul: "Minggu berikutnya", hari: Array.from({ length: 7 }, (_, i) => geserHari(senin, i + 7)) },
  ]

  const salin = async () => {
    if (!sumber) return
    setMenyalin(true)
    try {
      const baru = await mintaServer<JadwalKeliling[]>("/api/koleksi/keliling/salin-hari", "POST", { dari: sumber, ke: terpilih })
      void qc.invalidateQueries({ queryKey: qk.koleksi("keliling") })
      void qc.invalidateQueries({ queryKey: qk.aktivitas })
      if (baru.length === 0) {
        toast.info("Tidak ada jadwal baru yang disalin", { description: "Semua jadwal itu sudah ada di hari tujuan." })
      } else {
        toast.success(`${baru.length} jadwal disalin ke ${terpilih.length} hari`)
      }
      tutup()
    } catch (e) {
      laporGalat(e, "Jadwal gagal disalin")
    } finally {
      setMenyalin(false)
    }
  }

  return (
    <Dialog open={!!sumber} onOpenChange={(o) => !o && tutup()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>
            Salin jadwal {sumber && `${HARI_LABEL[hariDari(sumber)]}, ${tanggal(sumber)}`}
          </DialogTitle>
          <DialogDescription>
            {titik.length} jadwal (unit, jam, dan lokasi) disalin ke hari yang dicentang. Statusnya mengikuti jadwal asal;
            jadwal yang sudah ada di hari tujuan dilewati.
          </DialogDescription>
        </DialogHeader>
        <ul className="grid gap-1 rounded-lg border p-3 text-sm">
          {titik.map((t) => (
            <li key={t.id} className="flex flex-wrap items-center gap-1.5">
              <Badge variant="secondary">{t.unit}</Badge>
              <span className="tabular-nums">{jam(t)}</span>
              <span>{t.lokasi}</span>
              {!t.aktif && (
                <Badge variant="outline" className="text-muted-foreground">
                  Nonaktif
                </Badge>
              )}
            </li>
          ))}
        </ul>
        <div className="grid gap-4 sm:grid-cols-2">
          {minggu.map((m) => (
            <fieldset key={m.judul} className="grid gap-2">
              <legend className="mb-1 text-sm font-medium">{m.judul}</legend>
              {m.hari.map((iso) => {
                const id = `salin-${iso}`
                const nonaktif = iso === sumber || iso < hariIni
                return (
                  <div key={iso} className="flex items-center gap-2">
                    <Checkbox
                      id={id}
                      disabled={nonaktif}
                      checked={terpilih.includes(iso)}
                      onCheckedChange={(c) => setTerpilih((x) => (c ? [...x, iso] : x.filter((y) => y !== iso)))}
                    />
                    <Label htmlFor={id} className={cn("font-normal", nonaktif && "text-muted-foreground")}>
                      {HARI_LABEL[hariDari(iso)]}, {tanggal(iso)}
                      {iso === sumber && " (asal)"}
                    </Label>
                  </div>
                )
              })}
            </fieldset>
          ))}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={tutup}>
            Batal
          </Button>
          <Button onClick={() => void salin()} disabled={menyalin || terpilih.length === 0}>
            <IconCopy />
            {terpilih.length ? `Salin ke ${terpilih.length} hari` : "Salin"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

/**
 * Urutan layanan luar kantor di papan Beranda pada satu tanggal (D-053): semua
 * jenis yang dilayani hari itu, termasuk yang belum aktif. Bila urutannya sama
 * dengan urut jam, tanggal itu dikembalikan ke urut jam.
 */
function DialogUrutanPapan({
  tanggal: iso,
  semua,
  urutan,
  tutup,
}: {
  tanggal: string | null
  semua: JadwalKeliling[]
  urutan: string[]
  tutup: () => void
}) {
  const entri = React.useMemo(() => (iso ? susunPapan(semua, iso, urutan) : []), [iso, semua, urutan])
  const [daftar, setDaftar] = React.useState(() => entri.map((j) => j.id))
  const simpan = useSimpanUrutanPapan()
  const perId = new Map(entri.map((j) => [j.id, j]))
  const baris = daftar.flatMap((id) => perId.get(id) ?? [])
  const urutJamId = [...entri].sort(urutJam).map((j) => j.id)
  const samaJam = baris.length === urutJamId.length && baris.every((j, i) => j.id === urutJamId[i])

  const geser = (i: number, arah: -1 | 1) => {
    const x = baris.map((j) => j.id)
    ;[x[i], x[i + arah]] = [x[i + arah], x[i]]
    setDaftar(x)
  }

  const kirim = () => {
    if (!iso) return
    simpan.mutate(
      { tanggal: iso, ids: samaJam ? [] : baris.map((j) => j.id) },
      {
        onSuccess: () => {
          toast.success(samaJam ? "Papan kembali urut jam" : "Urutan papan disimpan", {
            description: `${HARI_LABEL[hariDari(iso)]}, ${tanggal(iso)}`,
          })
          tutup()
        },
        onError: (e) => laporGalat(e, "Urutan gagal disimpan"),
      }
    )
  }

  return (
    <Dialog open={!!iso} onOpenChange={(o) => !o && tutup()}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Urutan papan {iso && `${HARI_LABEL[hariDari(iso)]}, ${tanggal(iso)}`}</DialogTitle>
          <DialogDescription>
            Urutan “Di luar kantor hari ini” di papan Beranda. Papan menampilkan dari atas sebanyak yang muat; sisanya
            diringkas. Halaman Jadwal tetap urut jam, dan entri yang ditambahkan nanti tampil di bawah.
          </DialogDescription>
        </DialogHeader>
        <ol className="grid gap-1.5" aria-label="Urutan di papan Beranda">
          {baris.map((j, i) => (
            <li
              key={j.id}
              className={cn("flex items-center gap-2 rounded-lg border py-2 pr-1.5 pl-3 text-sm", !j.aktif && "text-muted-foreground")}
            >
              <span className="w-5 shrink-0 font-medium tabular-nums">{i + 1}</span>
              <span className="grid min-w-0 flex-1 gap-0.5">
                <span className="flex flex-wrap items-center gap-1.5">
                  <span className="font-medium">{j.lokasi}</span>
                  {!j.aktif && (
                    <Badge variant="outline" className="text-muted-foreground">
                      Nonaktif
                    </Badge>
                  )}
                </span>
                <span className="text-xs text-muted-foreground">
                  <span className="tabular-nums">{jam(j)}</span> · {namaLayananLuar(j)}
                  {j.jenis !== "keliling" && ` · ${JENIS_LAYANAN_LUAR[j.jenis]}`}
                </span>
              </span>
              <Button
                variant="ghost"
                size="icon"
                className="size-8"
                onClick={() => geser(i, -1)}
                disabled={i === 0}
                aria-label={`Naikkan ${j.lokasi}, ${jam(j)}`}
              >
                <IconArrowUp />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="size-8"
                onClick={() => geser(i, 1)}
                disabled={i === baris.length - 1}
                aria-label={`Turunkan ${j.lokasi}, ${jam(j)}`}
              >
                <IconArrowDown />
              </Button>
            </li>
          ))}
        </ol>
        <DialogFooter className="sm:justify-between">
          <Button variant="ghost" onClick={() => setDaftar(urutJamId)} disabled={samaJam}>
            <IconClockHour4 />
            Urut jam
          </Button>
          <div className="flex flex-col-reverse gap-2 sm:flex-row">
            <Button variant="outline" onClick={tutup}>
              Batal
            </Button>
            <Button onClick={kirim} disabled={simpan.isPending}>
              <IconCheck />
              Simpan urutan
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}

/** Samsat Keliling per tanggal dalam satu minggu, Senin–Minggu, termasuk yang belum aktif */
function PratinjauMinggu({ data, semua }: { data: JadwalKeliling[]; semua: JadwalKeliling[] }) {
  const hariIni = hariIniIso()
  const [senin, setSenin] = React.useState(() => seninDari(hariIni))
  const [menyalin, setMenyalin] = React.useState(false)
  const [sumberSalin, setSumberSalin] = React.useState<string | null>(null)
  const [tanggalUrut, setTanggalUrut] = React.useState<string | null>(null)
  const qc = useQueryClient()
  const bisaSalin = diServer("keliling")
  // Urutan papan juga hanya tersimpan di server
  const { data: urutanPapan } = useUrutanPapan(bisaSalin)
  const urutanPada = (iso: string) => urutanPapan?.find((u) => u.tanggal === iso)?.urutan ?? []
  const mingguIni = seninDari(hariIni)

  const salin = async () => {
    setMenyalin(true)
    try {
      const baru = await mintaServer<JadwalKeliling[]>("/api/koleksi/keliling/salin-minggu", "POST", { dari: senin })
      void qc.invalidateQueries({ queryKey: qk.koleksi("keliling") })
      void qc.invalidateQueries({ queryKey: qk.aktivitas })
      const berikut = geserHari(senin, 7)
      if (baru.length === 0) {
        toast.info("Tidak ada jadwal baru yang disalin", {
          description: "Minggu ini kosong, atau semua jadwalnya sudah ada di minggu berikutnya.",
        })
        return
      }
      setSenin(berikut)
      toast.success(`${baru.length} jadwal disalin ke minggu ${tanggal(berikut)}`, {
        description: "Salinan belum tampil di portal. Periksa unit dan lokasinya, lalu aktifkan.",
      })
    } catch (e) {
      laporGalat(e, "Jadwal gagal disalin")
    } finally {
      setMenyalin(false)
    }
  }

  const hari = Array.from({ length: 7 }, (_, i) => geserHari(senin, i))
  const label = senin === mingguIni ? "Minggu ini" : senin === geserHari(mingguIni, 7) ? "Minggu depan" : `Minggu ${tanggal(senin)}`

  return (
    <Card className="@container/card gap-4">
      {/* Kartu sempit: tombol pindah ke bawah judul agar keterangan tidak terjepit */}
      <CardHeader className="@max-3xl/card:has-data-[slot=card-action]:grid-cols-1">
        <CardTitle className="text-base">{label}</CardTitle>
        <CardDescription>
          {tanggal(hari[0])} – {tanggal(hari[6])}. Jadwal nonaktif ditandai dan belum tampil di portal.
        </CardDescription>
        <CardAction className="flex flex-wrap gap-2 @max-3xl/card:col-start-1 @max-3xl/card:row-span-1 @max-3xl/card:row-start-3 @max-3xl/card:justify-self-start">
          <Button variant="outline" size="sm" onClick={() => setSenin(geserHari(senin, -7))}>
            Sebelumnya
          </Button>
          <Button variant="outline" size="sm" onClick={() => setSenin(geserHari(senin, 7))}>
            Berikutnya
          </Button>
          {bisaSalin && (
            <Button size="sm" onClick={() => void salin()} disabled={menyalin}>
              <IconCopy />
              Salin ke minggu berikutnya
            </Button>
          )}
        </CardAction>
      </CardHeader>
      <CardContent>
        <ol className="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
          {hari.map((iso) => {
            const titik = data
              .filter((x) => x.jenis === "keliling" && x.tanggalMulai === iso)
              .sort((a, b) => a.mulai.localeCompare(b.mulai) || (a.unit ?? "").localeCompare(b.unit ?? ""))
            const diPapan = susunPapan(semua, iso).length
            const diatur = urutanPada(iso).length > 0
            return (
              <li
                key={iso}
                className={cn(
                  "rounded-lg border p-3",
                  iso === hariIni && "border-brand/50 bg-brand/5",
                  iso < hariIni && "opacity-60"
                )}
              >
                <p className="flex items-center justify-between gap-2 text-sm font-medium">
                  <span>
                    {HARI_LABEL[hariDari(iso)]}
                    <span className="ml-1 font-normal text-muted-foreground">{tanggal(iso)}</span>
                  </span>
                  <span className="flex items-center gap-1">
                    {iso === hariIni && <span className="text-xs font-normal text-brand">Hari ini</span>}
                    {bisaSalin && titik.length > 0 && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="size-7"
                        onClick={() => setSumberSalin(iso)}
                        aria-label={`Salin jadwal ${HARI_LABEL[hariDari(iso)]}, ${tanggal(iso)} ke hari lain`}
                        title="Salin ke hari lain"
                      >
                        <IconCopy />
                      </Button>
                    )}
                    {bisaSalin && iso >= hariIni && diPapan > 1 && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className={cn("size-7", diatur && "text-brand")}
                        onClick={() => setTanggalUrut(iso)}
                        aria-label={`Atur urutan papan Beranda ${HARI_LABEL[hariDari(iso)]}, ${tanggal(iso)}`}
                        title="Atur urutan papan Beranda"
                      >
                        <IconListNumbers />
                      </Button>
                    )}
                  </span>
                </p>
                {titik.length === 0 ? (
                  <p className="mt-1 text-sm text-muted-foreground">Tidak ada jadwal.</p>
                ) : (
                  <ul className="mt-2 grid gap-2">
                    {titik.map((t) => (
                      <li key={t.id} className={cn("grid gap-0.5 text-sm", !t.aktif && "text-muted-foreground")}>
                        <span className="flex flex-wrap items-center gap-1.5">
                          <Badge variant="secondary" className="tabular-nums">
                            {t.unit}
                          </Badge>
                          <span className="tabular-nums">{jam(t)}</span>
                          {!t.aktif && (
                            <Badge variant="outline" className="text-muted-foreground">
                              Nonaktif
                            </Badge>
                          )}
                        </span>
                        <span>{t.lokasi}</span>
                      </li>
                    ))}
                  </ul>
                )}
                {diatur && iso >= hariIni && <p className="mt-2 text-xs text-brand">Urutan papan Beranda diatur.</p>}
              </li>
            )
          })}
        </ol>
        <DialogSalinHari key={sumberSalin ?? ""} sumber={sumberSalin} senin={senin} data={data} tutup={() => setSumberSalin(null)} />
        <DialogUrutanPapan
          key={tanggalUrut ?? ""}
          tanggal={tanggalUrut}
          semua={semua}
          urutan={tanggalUrut ? urutanPada(tanggalUrut) : []}
          tutup={() => setTanggalUrut(null)}
        />
      </CardContent>
    </Card>
  )
}

const k = pembantuKolom<JadwalKeliling>()
const kl = pembantuKolom<LokasiLayanan>()

export default function HalamanLayananLuarKantor() {
  const [params, setParams] = useSearchParams()
  const jenisParam = params.get("jenis")
  const tabLokasi = jenisParam === "lokasi"
  const jenis: JenisLayananLuar = isJenis(jenisParam) ? jenisParam : "keliling"
  const { data, isLoading, error, refetch, isRefetching } = useKoleksi("keliling")
  const { data: lokasi, isLoading: memuatLokasi } = useKoleksi("lokasi")
  const ubah = useUbahEntri("keliling")
  const hapus = useHapus("keliling", "jadwal")
  const hapusLokasi = useHapus("lokasi", "lokasi")
  const hariIni = hariIniIso()

  const daftarLokasi = React.useMemo(
    () => (lokasi ?? []).slice().sort((a, b) => a.nama.localeCompare(b.nama, "id")),
    [lokasi]
  )
  /** Jadwal hari ini dan yang akan datang yang memakai setiap lokasi */
  const pemakaian = React.useMemo(() => {
    const hitung = new Map<string, number>()
    for (const j of data ?? []) {
      if ((j.tanggalSelesai ?? "9999-12-31") < hariIni) continue
      const kunci = j.lokasi.toLowerCase()
      hitung.set(kunci, (hitung.get(kunci) ?? 0) + 1)
    }
    return hitung
  }, [data, hariIni])

  const edLokasi = useEditorEntri({
    koleksi: "lokasi",
    schema: schemaLokasi,
    nama: "lokasi",
    kosong: (): FormLokasi => ({ nama: "", kecamatan: KECAMATAN[0] }),
    dariEntri: (l): FormLokasi => ({ nama: l.nama, kecamatan: l.kecamatan }),
    keEntriBaru: (f) => ({ nama: f.nama.trim(), kecamatan: f.kecamatan }),
    kePatch: (f) => ({ nama: f.nama.trim(), kecamatan: f.kecamatan }),
  })

  const ed = useEditorEntri({
    koleksi: "keliling",
    schema,
    nama: "jadwal",
    kosong: () => kosongUntuk(jenis),
    dariEntri: (j): FormLayanan => ({
      jenis: j.jenis,
      unit: j.unit ?? "",
      nama: j.nama,
      tanggalMulai: j.tanggalMulai ?? "",
      tanggalSelesai: j.tanggalSelesai ?? "",
      hari: j.hari,
      mulai: j.mulai,
      selesai: j.selesai,
      lokasi: j.lokasi,
      kecamatan: j.kecamatan,
      layanan: j.layanan,
      catatan: j.catatan,
      aktif: j.aktif,
    }),
    keEntriBaru: keData,
    kePatch: keData,
  })
  const jenisForm = ed.form.watch("jenis")

  // Formulir tambah mengikuti jenis di alamat, mis. ?jenis=sementara&baru=1 saat panel sudah terbuka
  React.useEffect(() => {
    if (ed.baru && !ed.entri && ed.form.getValues("jenis") !== jenis) ed.form.reset(kosongUntuk(jenis))
  }, [jenis, ed.baru, ed.entri, ed.form])

  const perJenis = React.useMemo(() => {
    const kelompok: Record<JenisLayananLuar, JadwalKeliling[]> = { keliling: [], gerai: [], sementara: [] }
    for (const j of data ?? []) kelompok[j.jenis]?.push(j)
    // Yang akan datang lebih dulu, berurutan; yang sudah lewat di belakang, terbaru lebih dulu
    const lewat = (j: JadwalKeliling) => (j.tanggalSelesai ?? "9999-12-31") < hariIni
    kelompok.keliling.sort(
      (a, b) =>
        Number(lewat(a)) - Number(lewat(b)) ||
        (lewat(a) ? -1 : 1) * (a.tanggalMulai ?? "").localeCompare(b.tanggalMulai ?? "") ||
        a.mulai.localeCompare(b.mulai) ||
        (a.unit ?? "").localeCompare(b.unit ?? "")
    )
    kelompok.sementara.sort(
      (a, b) =>
        Number(lewat(a)) - Number(lewat(b)) ||
        (lewat(a) ? -1 : 1) * (a.tanggalMulai ?? "").localeCompare(b.tanggalMulai ?? "") ||
        a.nama.localeCompare(b.nama)
    )
    kelompok.gerai.sort((a, b) => a.nama.localeCompare(b.nama) || a.mulai.localeCompare(b.mulai))
    return kelompok
  }, [data, hariIni])

  const pilihTab = (nilai: string) =>
    setParams(
      (p) => {
        const n = new URLSearchParams(p)
        n.set("jenis", nilai)
        return n
      },
      { replace: true }
    )

  const setAktif = async (baris: JadwalKeliling[], aktif: boolean) => {
    for (const b of baris) await ubah.mutateAsync({ id: b.id, patch: { aktif } })
    toast.success(`${baris.length} jadwal ${aktif ? "diaktifkan" : "dinonaktifkan"}`)
  }

  const tombolNama = (j: JadwalKeliling, teks: React.ReactNode, sub: React.ReactNode) => (
    <button
      type="button"
      onClick={() => ed.bukaUbah(j.id)}
      className="flex min-w-48 items-start gap-2 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
    >
      <IconMapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
      <span className="grid gap-0.5">
        <span className="font-medium underline-offset-4 hover:underline">{teks}</span>
        <span className="text-xs text-muted-foreground">{sub}</span>
      </span>
    </button>
  )

  const kolomAktif = k.accessor("aktif", {
    header: "Aktif",
    cell: ({ row }) => (
      <Switch
        checked={row.original.aktif}
        aria-label={`Tampilkan ${namaLayananLuar(row.original)} di ${row.original.lokasi} di portal`}
        onCheckedChange={(aktif) => ubah.mutate({ id: row.original.id, patch: { aktif } })}
      />
    ),
  })
  const kolomAksi = k.display({
    id: "aksi",
    cell: ({ row }) => (
      <MenuBaris
        label={row.original.lokasi}
        onSunting={() => ed.bukaUbah(row.original.id)}
        onHapus={() => void hapus([row.original.id], `${namaLayananLuar(row.original)} · ${row.original.lokasi}`)}
      />
    ),
  })
  const kolomJam = k.accessor("mulai", {
    header: "Jam",
    cell: ({ row }) => <span className="whitespace-nowrap tabular-nums">{jam(row.original)}</span>,
  })

  const kolomKeliling = [
    k.accessor("tanggalMulai", {
      header: "Tanggal",
      cell: ({ row }) =>
        row.original.tanggalMulai && (
          <span className="grid whitespace-nowrap">
            <span className="font-medium">{HARI_LABEL[hariDari(row.original.tanggalMulai)]}</span>
            <span className="text-xs text-muted-foreground">{tanggal(row.original.tanggalMulai)}</span>
          </span>
        ),
    }),
    kolomJam,
    k.accessor("unit", {
      header: "Unit",
      cell: ({ row }) => <Badge variant="secondary">Samsat Keliling {row.original.unit}</Badge>,
    }),
    k.accessor("lokasi", {
      header: "Lokasi",
      cell: ({ row }) => tombolNama(row.original, row.original.lokasi, row.original.kecamatan),
      enableHiding: false,
    }),
    k.accessor("layanan", { header: "Layanan" }),
    kolomAktif,
    kolomAksi,
  ]

  const kolomGerai = [
    k.accessor("nama", {
      header: "Gerai",
      cell: ({ row }) => tombolNama(row.original, row.original.nama, `${row.original.lokasi} · ${row.original.kecamatan}`),
      enableHiding: false,
    }),
    k.accessor("hari", { header: "Hari", cell: ({ row }) => ringkasHari(row.original.hari) }),
    kolomJam,
    k.accessor("tanggalSelesai", {
      header: "Berlaku",
      cell: ({ row }) => (
        <span className="whitespace-nowrap">{rentangTanggal(row.original.tanggalMulai, row.original.tanggalSelesai)}</span>
      ),
    }),
    kolomAktif,
    kolomAksi,
  ]

  const kolomSementara = [
    k.accessor("nama", {
      header: "Loket",
      cell: ({ row }) => tombolNama(row.original, row.original.nama, `${row.original.lokasi} · ${row.original.kecamatan}`),
      enableHiding: false,
    }),
    k.accessor("tanggalMulai", {
      header: "Tanggal",
      cell: ({ row }) => {
        const j = row.original
        const status =
          (j.tanggalSelesai ?? "") < hariIni ? "Selesai" : (j.tanggalMulai ?? "") > hariIni ? "Akan datang" : "Berlangsung"
        return (
          <span className="grid gap-1 whitespace-nowrap">
            <span>{rentangTanggal(j.tanggalMulai, j.tanggalSelesai)}</span>
            <Badge variant={status === "Berlangsung" ? "default" : "outline"} className="w-fit">
              {status}
            </Badge>
          </span>
        )
      },
    }),
    k.accessor("hari", { header: "Hari", cell: ({ row }) => ringkasHari(row.original.hari) }),
    kolomJam,
    kolomAktif,
    kolomAksi,
  ]

  const filterWaktu = {
    id: "waktu",
    label: "Waktu",
    opsi: [
      { nilai: "akan-datang", label: "Hari ini & akan datang" },
      { nilai: "lewat", label: "Sudah lewat" },
    ],
    nilai: (j: JadwalKeliling) => ((j.tanggalSelesai ?? "9999-12-31") < hariIni ? "lewat" : "akan-datang"),
  }
  const filterKecamatan = {
    id: "kecamatan",
    label: "Kecamatan",
    opsi: KECAMATAN.map((x) => ({ nilai: x, label: x })),
    nilai: (j: JadwalKeliling) => j.kecamatan,
  }
  const tindakanMassal = (baris: JadwalKeliling[], bersihkan: () => void) => (
    <>
      <Button variant="outline" size="sm" onClick={() => void setAktif(baris, true).then(bersihkan)}>
        Aktifkan
      </Button>
      <Button variant="outline" size="sm" onClick={() => void setAktif(baris, false).then(bersihkan)}>
        Nonaktifkan
      </Button>
      <TombolHapusMassal
        onClick={async () => {
          if (await hapus(baris.map((b) => b.id))) bersihkan()
        }}
      />
    </>
  )
  const cari = {
    placeholder: "Cari nama, lokasi, atau kecamatan…",
    teks: (j: JadwalKeliling) => `${namaLayananLuar(j)} ${j.lokasi} ${j.kecamatan} ${j.catatan}`,
  }

  const kolomLokasi = [
    kl.accessor("nama", {
      header: "Lokasi",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => edLokasi.bukaUbah(row.original.id)}
          className="flex min-w-48 items-start gap-2 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <IconMapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <span className="font-medium underline-offset-4 hover:underline">{row.original.nama}</span>
        </button>
      ),
      enableHiding: false,
    }),
    kl.accessor("kecamatan", { header: "Kecamatan" }),
    kl.accessor((l) => pemakaian.get(l.nama.toLowerCase()) ?? 0, {
      id: "pemakaian",
      header: "Jadwal mendatang",
      cell: ({ getValue }) => <span className="tabular-nums">{getValue()}</span>,
    }),
    kl.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.nama}
          onSunting={() => edLokasi.bukaUbah(row.original.id)}
          onHapus={() => void hapusLokasi([row.original.id], row.original.nama)}
        />
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Pelayanan pajak di luar kantor: Samsat Keliling 01–03 berpindah lokasi per tanggal, gerai tetap seperti Samsat Corner dan Mall Pelayanan Publik, serta loket sementara untuk musim atau acara tertentu. Jadwal Samsat Keliling minggu berikutnya diperbarui setiap Jumat.">
        <Button onClick={tabLokasi ? edLokasi.bukaBaru : ed.bukaBaru}>
          <IconPlus />
          {tabLokasi ? "Tambah lokasi" : TOMBOL_TAMBAH[jenis]}
        </Button>
      </PengantarHalaman>
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Jadwal ini dipakai bersama semua pengelola Layanan Luar Kantor. Jadwal aktif langsung tampil di beranda dan
          halaman Jadwal portal.
        </AlertDescription>
      </Alert>
      {error ? (
        <Alert variant="destructive">
          <AlertTitle>Jadwal layanan luar kantor gagal dimuat</AlertTitle>
          <AlertDescription>
            <p>{error instanceof Error ? error.message : String(error)}</p>
            <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()} disabled={isRefetching}>
              <IconRefresh />
              Coba lagi
            </Button>
          </AlertDescription>
        </Alert>
      ) : (
        <Tabs value={tabLokasi ? "lokasi" : jenis} onValueChange={pilihTab} className="gap-4">
          <TabsList className="flex-wrap group-data-[orientation=horizontal]/tabs:h-auto *:data-[slot=tabs-trigger]:h-auto">
            {JENIS.map((j) => (
              <TabsTrigger key={j} value={j}>
                {JENIS_LAYANAN_LUAR[j]}
                {data && <span className="text-muted-foreground tabular-nums">{perJenis[j].length}</span>}
              </TabsTrigger>
            ))}
            <TabsTrigger value="lokasi">
              Lokasi
              {lokasi && <span className="text-muted-foreground tabular-nums">{lokasi.length}</span>}
            </TabsTrigger>
          </TabsList>
          <TabsContent value="keliling" className="grid grid-cols-[minmax(0,1fr)] gap-4">
            {data && <PratinjauMinggu data={perJenis.keliling} semua={data} />}
            <DataTable
              data={data && perJenis.keliling}
              memuat={isLoading}
              columns={kolomKeliling}
              label="Daftar jadwal Samsat Keliling"
              ukuranHalaman={20}
              cari={cari}
              filterAwal={FILTER_AWAL}
              filter={[
                filterWaktu,
                {
                  id: "unit",
                  label: "Unit",
                  opsi: UNIT.map((u) => ({ nilai: u, label: `Samsat Keliling ${u}` })),
                  nilai: (j) => j.unit ?? "",
                },
                filterKecamatan,
              ]}
              tindakanMassal={tindakanMassal}
              kosong={{
                judul: "Belum ada jadwal Samsat Keliling",
                deskripsi: "Tambahkan lokasi pertama, atau salin jadwal minggu sebelumnya dari pratinjau di atas.",
              }}
            />
          </TabsContent>
          <TabsContent value="gerai">
            <DataTable
              data={data && perJenis.gerai}
              memuat={isLoading}
              columns={kolomGerai}
              label="Daftar gerai tetap"
              cari={cari}
              filter={[filterKecamatan]}
              tindakanMassal={tindakanMassal}
              kosong={{
                judul: "Belum ada gerai tetap",
                deskripsi: "Tambahkan gerai yang buka rutin di luar kantor, mis. Samsat Corner atau Mall Pelayanan Publik.",
              }}
            />
          </TabsContent>
          <TabsContent value="sementara">
            <DataTable
              data={data && perJenis.sementara}
              memuat={isLoading}
              columns={kolomSementara}
              label="Daftar loket sementara"
              cari={cari}
              filterAwal={FILTER_AWAL}
              filter={[filterWaktu, filterKecamatan]}
              tindakanMassal={tindakanMassal}
              kosong={{
                judul: "Belum ada loket sementara",
                deskripsi: "Tambahkan loket musiman atau acara, mis. loket di kantor lurah, pasar, atau pameran.",
              }}
            />
          </TabsContent>
          <TabsContent value="lokasi" className="grid grid-cols-[minmax(0,1fr)] gap-4">
            <p className="text-sm text-muted-foreground">
              Daftar pilihan Lokasi saat mengisi jadwal. Lokasi yang diketik di jadwal otomatis masuk daftar ini. Mengubah
              atau menghapus lokasi tidak mengubah jadwal yang sudah tersimpan.
            </p>
            <DataTable
              data={lokasi && daftarLokasi}
              memuat={memuatLokasi}
              columns={kolomLokasi}
              label="Daftar lokasi layanan luar kantor"
              cari={{ placeholder: "Cari lokasi atau kecamatan…", teks: (l) => `${l.nama} ${l.kecamatan}` }}
              filter={[
                {
                  id: "kecamatan",
                  label: "Kecamatan",
                  opsi: KECAMATAN.map((x) => ({ nilai: x, label: x })),
                  nilai: (l) => l.kecamatan,
                },
              ]}
              tindakanMassal={(baris, bersihkan) => (
                <TombolHapusMassal
                  onClick={async () => {
                    if (await hapusLokasi(baris.map((b) => b.id))) bersihkan()
                  }}
                />
              )}
              kosong={{
                judul: "Belum ada lokasi",
                deskripsi: "Tambahkan lokasi yang sering dipakai, atau ketik lokasi baru saat mengisi jadwal.",
              }}
            />
          </TabsContent>
        </Tabs>
      )}
      <EntriSheet
        terbuka={ed.terbuka && (!tabLokasi || !!ed.entri)}
        tutup={ed.tutup}
        judul={`${ed.entri ? "Sunting" : "Tambah"} ${NAMA_ENTRI[jenisForm]}`}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
      >
        {jenisForm === "keliling" ? (
          <Baris>
            <FieldPilih
              control={ed.form.control}
              name="unit"
              label="Unit"
              wajib
              opsi={UNIT.map((u) => ({ nilai: u, label: `Samsat Keliling ${u}` }))}
            />
            <FieldTanggal control={ed.form.control} name="tanggalMulai" label="Tanggal" wajib />
          </Baris>
        ) : (
          <>
            <FieldTeks
              control={ed.form.control}
              name="nama"
              label={jenisForm === "gerai" ? "Nama gerai" : "Nama loket"}
              wajib
              placeholder={jenisForm === "gerai" ? "Mis. Samsat Corner" : "Mis. Loket Kantor Lurah Oebobo"}
            />
            <Baris>
              <FieldTanggal
                control={ed.form.control}
                name="tanggalMulai"
                label={jenisForm === "gerai" ? "Berlaku mulai" : "Tanggal mulai"}
                wajib={jenisForm === "sementara"}
                bisaKosong={jenisForm === "gerai"}
              />
              <FieldTanggal
                control={ed.form.control}
                name="tanggalSelesai"
                label={jenisForm === "gerai" ? "Berlaku sampai" : "Tanggal selesai"}
                wajib={jenisForm === "sementara"}
                bisaKosong={jenisForm === "gerai"}
              />
            </Baris>
            <FieldHari control={ed.form.control} />
          </>
        )}
        <Baris>
          <FieldTeks control={ed.form.control} name="mulai" label="Mulai" type="time" step={300} wajib />
          <FieldTeks control={ed.form.control} name="selesai" label="Selesai" type="time" step={300} wajib />
        </Baris>
        <FieldLokasi form={ed.form} lokasi={daftarLokasi} contoh={jenisForm === "keliling" ? "CFD Eltari" : "Lippo Plaza Kupang"} />
        <Baris>
          <FieldPilih
            control={ed.form.control}
            name="kecamatan"
            label="Kecamatan"
            opsi={KECAMATAN.map((x) => ({ nilai: x, label: x }))}
          />
          <FieldTeks control={ed.form.control} name="layanan" label="Layanan" wajib />
        </Baris>
        <FieldArea control={ed.form.control} name="catatan" label="Catatan" rows={2} placeholder="Mis. Bawa STNK, notis, dan KTP asli." />
        <FieldSakelar control={ed.form.control} name="aktif" label="Tampilkan di portal" />
      </EntriSheet>
      <EntriSheet
        terbuka={edLokasi.terbuka && (tabLokasi || !!edLokasi.entri)}
        tutup={edLokasi.tutup}
        judul={edLokasi.entri ? "Sunting lokasi" : "Tambah lokasi"}
        form={edLokasi.form}
        simpan={edLokasi.simpan}
        menyimpan={edLokasi.menyimpan}
      >
        <FieldTeks control={edLokasi.form.control} name="nama" label="Nama lokasi" wajib placeholder="Mis. CFD Eltari" />
        <FieldPilih
          control={edLokasi.form.control}
          name="kecamatan"
          label="Kecamatan"
          opsi={KECAMATAN.map((x) => ({ nilai: x, label: x }))}
        />
        <p className="text-sm text-muted-foreground">
          Jadwal yang sudah memakai lokasi ini tidak ikut berubah; nama baru dipakai untuk pengisian berikutnya.
        </p>
      </EntriSheet>
    </Halaman>
  )
}
