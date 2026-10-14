import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import {
  IconCircleCheckFilled,
  IconClockHour4,
  IconCurrentLocation,
  IconDeviceMobile,
  IconExternalLink,
  IconMapPin,
  IconPlus,
  IconUsers,
} from "@tabler/icons-react"
import { useForm, type Resolver } from "react-hook-form"
import { toast } from "sonner"
import { z } from "zod"

import { useKonfirmasi } from "@/components/data/confirm"
import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris } from "@/components/data/row-actions"
import { NadaBadge } from "@/components/data/status-badge"
import { FieldAngka, FieldArea, FieldTanggal, FieldTeks } from "@/components/form/fields"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Spinner } from "@/components/ui/spinner"
import {
  RADIUS_MAKS,
  RADIUS_MIN,
  urlPeta,
  useDaftarKegiatan,
  useHapusKegiatan,
  useRekapKegiatan,
  useSimpanKegiatan,
  type KegiatanLapangan,
} from "@/lib/absensi"
import { useDaftarAkun } from "@/lib/akun"
import { ApiError } from "@/lib/api"
import { sesi } from "@/lib/auth"
import { hariIniIso, tanggal } from "@/lib/format"
import { validasiFormulir } from "@/lib/formulir"
import * as v from "@/lib/validasi"

/** "lintang, bujur" seperti yang disalin dari Google Maps, mis. "-10.16370, 123.59740" */
function bacaKoordinat(teks: string): { lat: number; lng: number } | null {
  const cocok = teks.trim().match(/^(-?\d{1,2}(?:\.\d+)?)\s*,\s*(-?\d{1,3}(?:\.\d+)?)$/)
  if (!cocok) return null
  const [lat, lng] = [Number(cocok[1]), Number(cocok[2])]
  return Math.abs(lat) <= 90 && Math.abs(lng) <= 180 ? { lat, lng } : null
}

const schema = z.object({
  nama: v.wajib("Nama kegiatan").max(160, "Nama kegiatan maksimal 160 karakter."),
  tanggal: v.tanggalIso,
  tempat: v.wajib("Tempat").max(160, "Tempat maksimal 160 karakter."),
  koordinat: z.string().refine((t) => bacaKoordinat(t) !== null, "Tulis lintang, bujur, mis. -10.16370, 123.59740."),
  radius: v.angka({ min: RADIUS_MIN, maks: RADIUS_MAKS, bulat: true }),
  catatan: v.teks.max(300, "Catatan maksimal 300 karakter."),
  petugas: z.array(z.string()),
})
type FormKegiatan = z.infer<typeof schema>

function kosong(): FormKegiatan {
  return { nama: "", tanggal: hariIniIso(), tempat: "", koordinat: "", radius: 150, catatan: "", petugas: [] }
}

function dariKegiatan(k: KegiatanLapangan): FormKegiatan {
  return {
    nama: k.nama,
    tanggal: k.tanggal,
    tempat: k.tempat,
    koordinat: `${k.lat}, ${k.lng}`,
    radius: k.radius,
    catatan: k.catatan,
    petugas: k.petugas.map((p) => p.id),
  }
}

const jamWita = (iso: string) =>
  new Intl.DateTimeFormat("id-ID", { timeZone: "Asia/Makassar", hour: "2-digit", minute: "2-digit", hourCycle: "h23" })
    .format(new Date(iso))
    .replace(":", ".")

const k = pembantuKolom<KegiatanLapangan>()

export default function HalamanAbsensi() {
  return sesi().peran === "administrator" ? <KelolaKegiatan /> : <AjakanAplikasi />
}

/** Pegawai lain melakukan clock-in lewat aplikasi Absensi PINTU */
function AjakanAplikasi() {
  return (
    <Halaman>
      <Card className="max-w-xl">
        <CardHeader>
          <CardTitle>Clock-in lewat aplikasi Absensi PINTU</CardTitle>
          <CardDescription>
            Buka aplikasi di ponsel saat berada di lokasi kegiatan lapangan, lalu lakukan clock-in dengan swafoto dan lokasi GPS.
            Pasang aplikasinya ke layar utama agar mudah dibuka. Clock-out dilakukan dengan mesin sidik jari di lapangan.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Button asChild>
            <a href="/absen">
              <IconDeviceMobile />
              Buka aplikasi absensi
            </a>
          </Button>
        </CardContent>
      </Card>
    </Halaman>
  )
}

function KelolaKegiatan() {
  const { data, isLoading } = useDaftarKegiatan()
  const hapus = useHapusKegiatan()
  const konfirmasi = useKonfirmasi()
  const [editor, setEditor] = React.useState<{ kegiatan: KegiatanLapangan | null } | null>(null)
  const [rekap, setRekap] = React.useState<string | null>(null)

  const hapusKegiatan = async (daftar: KegiatanLapangan[]) => {
    const ya = await konfirmasi({
      judul: daftar.length === 1 ? `Hapus ${daftar[0].nama}?` : `Hapus ${daftar.length} kegiatan?`,
      deskripsi: "Clock-in petugas dan swafotonya ikut terhapus. Tindakan ini tidak dapat dibatalkan.",
      label: "Hapus",
      bahaya: true,
    })
    if (!ya) return false
    try {
      await hapus.mutateAsync(daftar.map((d) => d.id))
      toast.success("Kegiatan dihapus")
      return true
    } catch (e) {
      toast.error("Kegiatan gagal dihapus", { description: (e as Error).message })
      return false
    }
  }

  const kolom = [
    k.accessor("nama", {
      header: "Kegiatan",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => setRekap(row.original.id)}
          className="grid min-w-56 gap-0.5 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="font-medium underline-offset-4 hover:underline">{row.original.nama}</span>
          <span className="text-xs text-muted-foreground">
            {row.original.tempat} · radius {row.original.radius} m
          </span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("tanggal", {
      header: ({ column }) => <HeaderUrut column={column} judul="Tanggal" />,
      cell: ({ row }) => <span className="whitespace-nowrap">{tanggal(row.original.tanggal)}</span>,
    }),
    k.accessor((d) => d.jumlahHadir / Math.max(1, d.petugas.length), {
      id: "hadir",
      header: "Clock-in",
      cell: ({ row }) => (
        <NadaBadge
          nada={row.original.petugas.length > 0 && row.original.jumlahHadir >= row.original.petugas.length ? "sukses" : "netral"}
          ikon={IconUsers}
        >
          {row.original.jumlahHadir} dari {row.original.petugas.length}
        </NadaBadge>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.nama}
          onSunting={() => setEditor({ kegiatan: row.original })}
          onHapus={() => void hapusKegiatan([row.original])}
        >
          <DropdownMenuItem onSelect={() => setRekap(row.original.id)}>
            <IconClockHour4 />
            Rekap clock-in
          </DropdownMenuItem>
        </MenuBaris>
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Kegiatan lapangan seperti tilang gabungan. Tetapkan titik lokasi, radius, dan petugas; petugas melakukan clock-in lewat aplikasi Absensi PINTU di ponsel pada tanggal kegiatan, dengan swafoto dan lokasi GPS di dalam radius. Clock-out dilakukan dengan mesin sidik jari di lapangan.">
        <Button variant="outline" asChild>
          <a href="/absen">
            <IconDeviceMobile />
            Aplikasi petugas
          </a>
        </Button>
        <Button onClick={() => setEditor({ kegiatan: null })}>
          <IconPlus />
          Tambah kegiatan
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar kegiatan lapangan"
        sortingAwal={[{ id: "tanggal", desc: true }]}
        cari={{ placeholder: "Cari kegiatan, tempat, atau petugas…", teks: (d) => `${d.nama} ${d.tempat} ${d.petugas.map((p) => p.nama).join(" ")}` }}
        tindakanMassal={(baris, bersihkan) => (
          <Button variant="destructive" size="sm" onClick={async () => (await hapusKegiatan(baris)) && bersihkan()}>
            Hapus
          </Button>
        )}
        kosong={{ judul: "Belum ada kegiatan lapangan", deskripsi: "Tambahkan kegiatan dan tugaskan petugasnya." }}
      />
      {editor && <EditorKegiatan kegiatan={editor.kegiatan} tutup={() => setEditor(null)} />}
      <RekapKegiatan id={rekap} tutup={() => setRekap(null)} />
    </Halaman>
  )
}

function EditorKegiatan({ kegiatan, tutup }: { kegiatan: KegiatanLapangan | null; tutup: () => void }) {
  const simpan = useSimpanKegiatan()
  const { data: akun } = useDaftarAkun()
  const [cari, setCari] = React.useState("")
  const [mencariLokasi, setMencariLokasi] = React.useState(false)
  const form = useForm<FormKegiatan>({
    defaultValues: kegiatan ? dariKegiatan(kegiatan) : kosong(),
    resolver: zodResolver(schema) as Resolver<FormKegiatan>,
    mode: "onTouched",
  })
  const titik = bacaKoordinat(form.watch("koordinat"))

  // Akun yang bisa ditugaskan: aktif atau menunggu aktivasi, ditambah petugas yang sudah ditugaskan
  const terpilih = form.watch("petugas")
  const calon = (akun ?? [])
    .filter((a) => a.status === "active" || a.status === "pending" || terpilih.includes(a.id))
    .filter((a) => `${a.nama} ${a.nip}`.toLowerCase().includes(cari.trim().toLowerCase()))
    .sort((a, b) => a.nama.localeCompare(b.nama))

  const pakaiLokasiSaya = () => {
    if (!window.isSecureContext || !("geolocation" in navigator)) {
      toast.error("Lokasi tidak tersedia", { description: "Lokasi GPS hanya bisa dibaca lewat koneksi aman (HTTPS)." })
      return
    }
    setMencariLokasi(true)
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setMencariLokasi(false)
        form.setValue("koordinat", `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`, {
          shouldDirty: true,
          shouldValidate: true,
        })
        toast.success(`Titik lokasi diisi (akurasi ±${Math.ceil(pos.coords.accuracy)} m)`)
      },
      () => {
        setMencariLokasi(false)
        toast.error("Lokasi tidak tersedia", { description: "Izinkan akses lokasi dan pastikan GPS aktif, lalu coba lagi." })
      },
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
    )
  }

  const kirim = async (e?: React.BaseSyntheticEvent) => {
    e?.preventDefault()
    if (!(await validasiFormulir(form))) {
      toast.error("Periksa kembali isian yang ditandai merah.")
      return
    }
    const nilai = form.getValues()
    const t = bacaKoordinat(nilai.koordinat)!
    try {
      await simpan.mutateAsync({
        id: kegiatan?.id,
        data: {
          nama: nilai.nama,
          tanggal: nilai.tanggal,
          tempat: nilai.tempat,
          lat: t.lat,
          lng: t.lng,
          radius: nilai.radius,
          catatan: nilai.catatan,
          petugas: nilai.petugas,
        },
      })
      toast.success(kegiatan ? "Perubahan kegiatan disimpan" : "Kegiatan ditambahkan")
      tutup()
    } catch (galat) {
      if (galat instanceof ApiError) {
        for (const [nama, pesan] of Object.entries(galat.isian)) {
          const isian = nama === "lat" || nama === "lng" ? "koordinat" : nama.split(".")[0]
          form.setError(isian as keyof FormKegiatan, { type: "server", message: pesan })
        }
      }
      toast.error("Kegiatan gagal disimpan", { description: (galat as Error).message })
    }
  }

  return (
    <Sheet open onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-2xl">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>{kegiatan ? "Sunting kegiatan lapangan" : "Tambah kegiatan lapangan"}</SheetTitle>
          <SheetDescription>Clock-in dibuka sepanjang tanggal kegiatan (WITA) bagi petugas yang ditugaskan, di dalam radius dari titik lokasi.</SheetDescription>
        </SheetHeader>
        <Form {...form}>
          <form onSubmit={kirim} noValidate className="flex min-h-0 flex-1 flex-col">
            <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
              <FieldTeks control={form.control} name="nama" label="Nama kegiatan" wajib placeholder="Mis. Tilang gabungan Jl. El Tari" />
              <Baris>
                <FieldTanggal control={form.control} name="tanggal" label="Tanggal" wajib />
                <FieldAngka control={form.control} name="radius" label="Radius clock-in" wajib satuan="meter" min={RADIUS_MIN} max={RADIUS_MAKS} />
              </Baris>
              <FieldTeks control={form.control} name="tempat" label="Tempat" wajib placeholder="Mis. Depan Kantor Gubernur, Jl. El Tari" />
              <div className="grid gap-2">
                <FieldTeks
                  control={form.control}
                  name="koordinat"
                  label="Titik lokasi (lintang, bujur)"
                  wajib
                  inputMode="decimal"
                  placeholder="-10.16370, 123.59740"
                  deskripsi="Salin dari Google Maps (tekan lama titik di peta), atau pakai lokasi Anda saat berada di tempat kegiatan."
                />
                <div className="flex flex-wrap gap-2">
                  <Button type="button" variant="outline" size="sm" disabled={mencariLokasi} onClick={pakaiLokasiSaya}>
                    {mencariLokasi ? <Spinner /> : <IconCurrentLocation />}
                    Pakai lokasi saya
                  </Button>
                  {titik && (
                    <Button type="button" variant="ghost" size="sm" asChild>
                      <a href={urlPeta(titik.lat, titik.lng)} target="_blank" rel="noopener">
                        <IconExternalLink />
                        Lihat di peta
                      </a>
                    </Button>
                  )}
                </div>
              </div>
              <FieldArea control={form.control} name="catatan" label="Catatan untuk petugas" rows={2} maks={300} />
              <FormField
                control={form.control}
                name="petugas"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Petugas yang ditugaskan ({field.value.length})</FormLabel>
                    <Input value={cari} onChange={(e) => setCari(e.target.value)} placeholder="Cari nama atau NIP…" aria-label="Cari petugas" />
                    <FormControl>
                      <ul className="grid max-h-72 gap-1 overflow-y-auto rounded-md border p-2" aria-label="Pilih petugas">
                        {!akun ? (
                          <li className="p-2 text-sm text-muted-foreground">Memuat akun…</li>
                        ) : calon.length === 0 ? (
                          <li className="p-2 text-sm text-muted-foreground">Tidak ada akun yang cocok.</li>
                        ) : (
                          calon.map((a) => {
                            const dipilih = field.value.includes(a.id)
                            return (
                              <li key={a.id}>
                                <label className="flex cursor-pointer items-center gap-3 rounded-md p-2 text-sm hover:bg-muted/50">
                                  <Checkbox
                                    checked={dipilih}
                                    onCheckedChange={(cek) =>
                                      field.onChange(cek ? [...field.value, a.id] : field.value.filter((id) => id !== a.id))
                                    }
                                  />
                                  <span className="grid">
                                    <span>{a.nama}</span>
                                    <span className="font-mono text-xs text-muted-foreground">
                                      {a.nip}
                                      {a.status === "pending" && " · belum aktivasi"}
                                    </span>
                                  </span>
                                </label>
                              </li>
                            )
                          })
                        )}
                      </ul>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <SheetFooter className="flex-row justify-end gap-2 border-t">
              <Button type="button" variant="outline" onClick={tutup}>
                Batal
              </Button>
              <Button type="submit" disabled={simpan.isPending}>
                {simpan.isPending && <Spinner />}
                Simpan
              </Button>
            </SheetFooter>
          </form>
        </Form>
      </SheetContent>
    </Sheet>
  )
}

function RekapKegiatan({ id, tutup }: { id: string | null; tutup: () => void }) {
  const { data: kegiatan, isLoading } = useRekapKegiatan(id)
  const absen = kegiatan?.absen ?? []
  const ditugaskan = new Set(kegiatan?.petugas.map((p) => p.id))
  const tambahan = absen.filter((a) => !ditugaskan.has(a.petugas.id))

  return (
    <Sheet open={!!id} onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>{kegiatan?.nama ?? "Rekap clock-in"}</SheetTitle>
          <SheetDescription>
            {kegiatan ? `${tanggal(kegiatan.tanggal)} · ${kegiatan.tempat} · radius ${kegiatan.radius} m` : "Memuat…"}
          </SheetDescription>
        </SheetHeader>
        <div className="grid flex-1 content-start gap-3 overflow-y-auto p-4">
          {isLoading || !kegiatan ? (
            <div className="flex justify-center py-10">
              <Spinner />
            </div>
          ) : (
            <>
              <p className="flex items-center gap-2 text-sm">
                <IconMapPin className="size-4 text-muted-foreground" aria-hidden="true" />
                <a className="underline underline-offset-4" href={urlPeta(kegiatan.lat, kegiatan.lng)} target="_blank" rel="noopener">
                  {kegiatan.lat}, {kegiatan.lng}
                </a>
                <span className="text-muted-foreground">
                  · {kegiatan.jumlahHadir} dari {kegiatan.petugas.length} petugas sudah clock-in
                </span>
              </p>
              <ul className="grid gap-2">
                {[...kegiatan.petugas.map((p) => ({ p, a: absen.find((x) => x.petugas.id === p.id) })), ...tambahan.map((a) => ({ p: a.petugas, a }))].map(
                  ({ p, a }) => (
                    <li key={p.id} className="flex items-center gap-3 rounded-lg border p-3">
                      {a ? (
                        <a href={a.foto} target="_blank" rel="noopener" className="shrink-0" aria-label={`Swafoto ${p.nama}`}>
                          <img src={a.foto} alt="" className="size-14 rounded-md object-cover" loading="lazy" />
                        </a>
                      ) : (
                        <span className="flex size-14 shrink-0 items-center justify-center rounded-md border bg-muted text-xs text-muted-foreground">
                          —
                        </span>
                      )}
                      <div className="grid min-w-0 flex-1 gap-0.5 text-sm">
                        <span className="font-medium">{p.nama}</span>
                        <span className="font-mono text-xs text-muted-foreground">{p.nip}</span>
                        {a ? (
                          <span className="flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                            <IconCircleCheckFilled className="size-3.5 text-success" aria-hidden="true" />
                            {jamWita(a.waktu)} WITA · {a.jarak} m dari titik · akurasi ±{a.akurasi} m ·
                            <a className="underline underline-offset-4" href={urlPeta(a.lat, a.lng)} target="_blank" rel="noopener">
                              peta
                            </a>
                            {!ditugaskan.has(p.id) && " · tidak lagi ditugaskan"}
                          </span>
                        ) : (
                          <span className="text-xs text-muted-foreground">Belum clock-in</span>
                        )}
                      </div>
                    </li>
                  )
                )}
              </ul>
              {kegiatan.petugas.length === 0 && tambahan.length === 0 && (
                <p className="text-sm text-muted-foreground">Belum ada petugas yang ditugaskan.</p>
              )}
            </>
          )}
        </div>
      </SheetContent>
    </Sheet>
  )
}
