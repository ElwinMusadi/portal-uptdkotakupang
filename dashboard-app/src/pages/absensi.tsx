import * as React from "react"
import {
  IconCamera,
  IconExternalLink,
  IconLogin2,
  IconLogout2,
  IconMapPin,
  IconMapPinCheck,
  IconMapPinOff,
  IconPhoto,
  IconRefresh,
  IconTrash,
} from "@tabler/icons-react"
import type { UseFormReturn } from "react-hook-form"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { NadaBadge } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldTeks } from "@/components/form/fields"
import { PlaceholderFoto } from "@/components/form/media-fields"
import { Button } from "@/components/ui/button"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Label } from "@/components/ui/label"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { Spinner } from "@/components/ui/spinner"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { api, type Absensi, type JenisKegiatan, type Koordinat, type TipeAbsen } from "@/lib/api"
import { bacaSesi } from "@/lib/auth"
import { hariIniIso, jamTitik, selisihHari, tanggalJam } from "@/lib/format"
import { kompresGambar, namaBerkasGambar, TIPE_GAMBAR } from "@/lib/gambar"
import { sekarangWita } from "@/lib/jadwal"
import { JENIS_KEGIATAN, TIPE_ABSEN, judulEntri } from "@/lib/meta"
import { useKoleksi, usePengaturan } from "@/lib/queries"
import * as v from "@/lib/validasi"

/** Sisi terpanjang foto absensi; cukup untuk bukti, hemat penyimpanan */
const SISI_FOTO = 1024
/** Akurasi di atas batas ini ditandai kurang tepat */
const AKURASI_LONGGAR = 100

const schema = z
  .object({
    tipe: z.enum(["datang", "pulang"]),
    kegiatan: v.wajib("Nama kegiatan"),
    jenis: z.enum(["keliling", "operasi", "sosialisasi", "lainnya"]),
    foto: z
      .string()
      .nullable()
      .refine((f) => !!f, "Ambil foto bukti kehadiran."),
    lokasi: z.object({ lat: z.number(), lng: z.number(), akurasi: z.number() }).nullable(),
    tempat: v.teks,
    catatan: v.teks.max(500, "Maksimal 500 karakter."),
  })
  .superRefine((f, ctx) => {
    if (!f.lokasi && !f.tempat.trim()) {
      ctx.addIssue({
        code: "custom",
        path: ["tempat"],
        message: "Lokasi GPS belum terekam. Tulis keterangan tempat kegiatan.",
      })
    }
  })
type FormAbsen = z.infer<typeof schema>

const k = pembantuKolom<Absensi>()

function urlPeta(k: Koordinat) {
  return `https://www.google.com/maps/search/?api=1&query=${k.lat},${k.lng}`
}

function teksKoordinat(k: Koordinat) {
  return `${k.lat.toFixed(5)}, ${k.lng.toFixed(5)}`
}

function BadgeTipe({ tipe }: { tipe: TipeAbsen }) {
  return (
    <NadaBadge nada={tipe === "datang" ? "sukses" : "info"} ikon={tipe === "datang" ? IconLogin2 : IconLogout2}>
      {TIPE_ABSEN[tipe]}
    </NadaBadge>
  )
}

/* ------------------------------------------------------------------ */
/* Lokasi GPS                                                          */
/* ------------------------------------------------------------------ */

type StatusLokasi =
  | { status: "mencari" }
  | { status: "ada"; koordinat: Koordinat }
  | { status: "galat"; pesan: string }

/** Minta titik lokasi saat dipasang; `cari` untuk mencoba lagi */
function useLokasi() {
  const [st, setSt] = React.useState<StatusLokasi>({ status: "mencari" })
  const nomor = React.useRef(0)

  const cari = React.useCallback(() => {
    const ke = ++nomor.current
    if (!("geolocation" in navigator)) {
      setSt({ status: "galat", pesan: "Peramban ini tidak mendukung lokasi GPS." })
      return
    }
    if (!window.isSecureContext) {
      setSt({ status: "galat", pesan: "Lokasi GPS hanya bisa dibaca lewat koneksi aman (HTTPS)." })
      return
    }
    setSt({ status: "mencari" })
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        if (ke !== nomor.current) return
        setSt({
          status: "ada",
          koordinat: {
            lat: Number(pos.coords.latitude.toFixed(6)),
            lng: Number(pos.coords.longitude.toFixed(6)),
            akurasi: Math.round(pos.coords.accuracy),
          },
        })
      },
      (galat) => {
        if (ke !== nomor.current) return
        setSt({
          status: "galat",
          pesan:
            galat.code === galat.PERMISSION_DENIED
              ? "Izin lokasi ditolak. Izinkan akses lokasi untuk situs ini di pengaturan peramban, lalu coba lagi."
              : galat.code === galat.TIMEOUT
                ? "Lokasi belum ditemukan. Pastikan GPS aktif dan coba di area terbuka."
                : "Lokasi tidak tersedia. Pastikan GPS aktif, lalu coba lagi.",
        })
      },
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
    )
  }, [])

  React.useEffect(() => {
    cari()
    return () => {
      // abaikan jawaban yang datang setelah panel ditutup
      nomor.current++
    }
  }, [cari])

  return { st, cari }
}

function PanelLokasi({ form }: { form: UseFormReturn<FormAbsen> }) {
  const { st, cari } = useLokasi()
  const koordinat = form.watch("lokasi")

  React.useEffect(() => {
    if (st.status === "ada") {
      form.setValue("lokasi", st.koordinat, { shouldValidate: form.formState.isSubmitted })
      if (form.formState.isSubmitted) void form.trigger("tempat")
    }
  }, [st, form])

  return (
    <div className="grid gap-2">
      <p className="text-sm font-medium">Lokasi GPS</p>
      <div role="status" aria-live="polite" className="flex items-start gap-3 rounded-lg border p-3 text-sm">
        {st.status === "mencari" ? (
          <Spinner className="mt-0.5 size-5 shrink-0" />
        ) : koordinat ? (
          <IconMapPinCheck className="mt-0.5 size-5 shrink-0 text-success" aria-hidden="true" />
        ) : (
          <IconMapPinOff className="mt-0.5 size-5 shrink-0 text-warning" aria-hidden="true" />
        )}
        <div className="grid min-w-0 flex-1 gap-1">
          {st.status === "mencari" && <p>Mencari lokasi GPS…</p>}
          {koordinat && st.status !== "mencari" && (
            <>
              <p className="font-medium">Lokasi terekam</p>
              <p className="font-mono text-xs text-muted-foreground">
                {teksKoordinat(koordinat)} · akurasi ±{koordinat.akurasi} m
              </p>
              {koordinat.akurasi > AKURASI_LONGGAR && (
                <p className="text-muted-foreground">
                  Akurasi masih longgar. Pindah ke area terbuka lalu perbarui lokasi.
                </p>
              )}
            </>
          )}
          {st.status === "galat" && <p className={koordinat ? "text-muted-foreground" : undefined}>{st.pesan}</p>}
        </div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="shrink-0"
          disabled={st.status === "mencari"}
          onClick={cari}
        >
          <IconRefresh />
          {koordinat ? "Perbarui" : "Coba lagi"}
        </Button>
      </div>
    </div>
  )
}

/* ------------------------------------------------------------------ */
/* Foto bukti                                                          */
/* ------------------------------------------------------------------ */

function FieldFotoBukti({ form }: { form: UseFormReturn<FormAbsen> }) {
  const inputRef = React.useRef<HTMLInputElement>(null)
  const [proses, setProses] = React.useState(false)

  return (
    <FormField
      control={form.control}
      name="foto"
      render={({ field }) => {
        const pilih = async (berkas: File | undefined) => {
          if (!berkas) return
          setProses(true)
          try {
            const blob = await kompresGambar(berkas, SISI_FOTO)
            const hasil = await api.upload(blob, namaBerkasGambar(berkas.name || "absensi.jpg", blob))
            field.onChange(hasil.url)
          } catch (e) {
            toast.error("Foto gagal diproses", { description: (e as Error).message })
          } finally {
            setProses(false)
            if (inputRef.current) inputRef.current.value = ""
          }
        }
        return (
          <FormItem>
            <FormLabel>
              Foto bukti
              <span aria-hidden="true" className="text-destructive">
                *
              </span>
            </FormLabel>
            <div className="relative aspect-[4/3] overflow-hidden rounded-lg border bg-muted/40">
              {field.value ? (
                <img src={field.value} alt="Pratinjau foto bukti" className="size-full object-cover" />
              ) : (
                <PlaceholderFoto label="Belum ada foto. Foto suasana kegiatan dengan papan nama lokasi atau rekan petugas terlihat." />
              )}
              {proses && (
                <div className="absolute inset-0 flex items-center justify-center bg-background/70">
                  <Spinner />
                  <span className="sr-only">Memproses foto…</span>
                </div>
              )}
            </div>
            <FormControl>
              <input
                ref={inputRef}
                type="file"
                accept={TIPE_GAMBAR.join(",")}
                capture="environment"
                className="sr-only"
                tabIndex={-1}
                onChange={(e) => void pilih(e.target.files?.[0])}
              />
            </FormControl>
            <div>
              <Button
                type="button"
                variant={field.value ? "outline" : "default"}
                disabled={proses}
                onClick={() => inputRef.current?.click()}
              >
                <IconCamera />
                {field.value ? "Ambil ulang foto" : "Ambil foto"}
              </Button>
            </div>
            <FormDescription>Di ponsel, tombol ini membuka kamera belakang. Foto diperkecil otomatis.</FormDescription>
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

/* ------------------------------------------------------------------ */
/* Detail                                                              */
/* ------------------------------------------------------------------ */

function DetailAbsensi({
  absen,
  tutup,
  hapus,
}: {
  absen: Absensi | null
  tutup: () => void
  hapus: (a: Absensi) => void
}) {
  return (
    <Sheet open={!!absen} onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
        {absen && (
          <>
            <SheetHeader className="border-b pr-12">
              <SheetTitle className="flex flex-wrap items-center gap-2">
                <BadgeTipe tipe={absen.tipe} />
                <span>{absen.kegiatan}</span>
              </SheetTitle>
              <SheetDescription>{tanggalJam(absen.waktu)}</SheetDescription>
            </SheetHeader>
            <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
              <div className="aspect-[4/3] overflow-hidden rounded-lg border bg-muted/40">
                {absen.foto ? (
                  <img src={absen.foto} alt={`Foto bukti ${judulEntri("absensi", absen)}`} className="size-full object-cover" />
                ) : (
                  <PlaceholderFoto label="Data contoh tanpa foto" />
                )}
              </div>
              <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                  <dt className="text-muted-foreground">Petugas</dt>
                  <dd>{absen.petugas}</dd>
                  {absen.nip && <dd className="font-mono text-xs text-muted-foreground">NIP {absen.nip}</dd>}
                </div>
                <div>
                  <dt className="text-muted-foreground">Jenis kegiatan</dt>
                  <dd>{JENIS_KEGIATAN[absen.jenis]}</dd>
                </div>
                <div className="col-span-2">
                  <dt className="text-muted-foreground">Tempat</dt>
                  <dd>{absen.tempat || "—"}</dd>
                </div>
                <div className="col-span-2">
                  <dt className="text-muted-foreground">Lokasi GPS</dt>
                  {absen.lokasi ? (
                    <dd className="flex flex-wrap items-center gap-x-3 gap-y-1">
                      <span className="font-mono text-xs">
                        {teksKoordinat(absen.lokasi)} · ±{absen.lokasi.akurasi} m
                      </span>
                      <a
                        href={urlPeta(absen.lokasi)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 underline-offset-4 hover:underline"
                      >
                        Buka peta
                        <IconExternalLink className="size-3.5" aria-hidden="true" />
                        <span className="sr-only">(tab baru)</span>
                      </a>
                    </dd>
                  ) : (
                    <dd>
                      <NadaBadge nada="peringatan" ikon={IconMapPinOff}>
                        Tidak terekam
                      </NadaBadge>
                    </dd>
                  )}
                </div>
                {absen.catatan && (
                  <div className="col-span-2">
                    <dt className="text-muted-foreground">Catatan</dt>
                    <dd className="whitespace-pre-line">{absen.catatan}</dd>
                  </div>
                )}
              </dl>
              <p className="text-xs text-muted-foreground">
                Catatan absensi tidak bisa diubah agar tetap sah sebagai bukti. Hapus lalu absen ulang bila ada
                kekeliruan.
              </p>
            </div>
            <SheetFooter className="flex-row border-t">
              <Button
                type="button"
                variant="outline"
                className="text-destructive hover:text-destructive"
                onClick={() => hapus(absen)}
              >
                <IconTrash />
                Hapus
              </Button>
              <Button type="button" variant="outline" className="ml-auto" onClick={tutup}>
                Tutup
              </Button>
            </SheetFooter>
          </>
        )}
      </SheetContent>
    </Sheet>
  )
}

/* ------------------------------------------------------------------ */
/* Halaman                                                             */
/* ------------------------------------------------------------------ */

type Periode = "semua" | "hari-ini" | "pekan"

export default function HalamanAbsensi() {
  const [params, setParams] = useSearchParams()
  const { data, isLoading } = useKoleksi("absensi")
  const { data: keliling } = useKoleksi("keliling")
  const { data: akun } = usePengaturan("akun")
  const hapusEntri = useHapus("absensi", "absensi")
  const [periode, setPeriode] = React.useState<Periode>("semua")

  const nama = akun?.nama || "Pengelola PINTU"
  const nip = bacaSesi()?.nip ?? ""
  const hariIni = hariIniIso()

  const ed = useEditorEntri({
    koleksi: "absensi",
    schema,
    nama: "absensi",
    kosong: (): FormAbsen => {
      // absen pulang otomatis disarankan bila hari ini sudah absen datang
      const terakhir = data
        ?.filter((a) => a.petugas === nama && hariIniIso(new Date(a.waktu)) === hariIni)
        .sort((a, b) => b.waktu.localeCompare(a.waktu))[0]
      if (terakhir?.tipe === "datang") {
        return {
          tipe: "pulang",
          kegiatan: terakhir.kegiatan,
          jenis: terakhir.jenis,
          foto: null,
          lokasi: null,
          tempat: terakhir.tempat,
          catatan: "",
        }
      }
      return { tipe: "datang", kegiatan: "", jenis: "keliling", foto: null, lokasi: null, tempat: "", catatan: "" }
    },
    dariEntri: (a): FormAbsen => ({
      tipe: a.tipe,
      kegiatan: a.kegiatan,
      jenis: a.jenis,
      foto: a.foto,
      lokasi: a.lokasi,
      tempat: a.tempat,
      catatan: a.catatan,
    }),
    keEntriBaru: (f) => ({
      tipe: f.tipe,
      kegiatan: f.kegiatan.trim(),
      jenis: f.jenis,
      waktu: new Date().toISOString(),
      petugas: nama,
      nip,
      foto: f.foto,
      lokasi: f.lokasi,
      tempat: f.tempat.trim(),
      catatan: f.catatan.trim(),
    }),
  })

  const jadwalHariIni = React.useMemo(() => {
    const hari = sekarangWita().hari
    return (keliling ?? []).filter((j) => j.aktif && j.hari === hari).sort((a, b) => a.mulai.localeCompare(b.mulai))
  }, [keliling])

  const lihatId = params.get("lihat")
  const detail = lihatId ? (data?.find((a) => a.id === lihatId) ?? null) : null
  const bukaDetail = (id: string) =>
    setParams((p) => {
      const n = new URLSearchParams(p)
      n.set("lihat", id)
      return n
    })
  const tutupDetail = () =>
    setParams(
      (p) => {
        const n = new URLSearchParams(p)
        n.delete("lihat")
        return n
      },
      { replace: true }
    )

  const hapus = async (a: Absensi) => {
    if (await hapusEntri([a.id], judulEntri("absensi", a))) tutupDetail()
  }

  const terurut = React.useMemo(() => data?.slice().sort((a, b) => b.waktu.localeCompare(a.waktu)), [data])
  const tersaring = React.useMemo(
    () =>
      terurut?.filter((a) =>
        periode === "semua"
          ? true
          : periode === "hari-ini"
            ? hariIniIso(new Date(a.waktu)) === hariIni
            : selisihHari(a.waktu) <= 6
      ),
    [terurut, periode, hariIni]
  )
  const jumlah = (p: Periode) =>
    p === "semua"
      ? (data?.length ?? 0)
      : (data?.filter((a) => (p === "hari-ini" ? hariIniIso(new Date(a.waktu)) === hariIni : selisihHari(a.waktu) <= 6))
          .length ?? 0)

  const pilihJadwal = (lokasi: string) => {
    const opsi = { shouldDirty: true, shouldValidate: ed.form.formState.isSubmitted }
    ed.form.setValue("kegiatan", `Samsat Keliling · ${lokasi.replace(/^Kantor Kelurahan /, "")}`, opsi)
    ed.form.setValue("jenis", "keliling", opsi)
    if (!ed.form.getValues("tempat").trim()) ed.form.setValue("tempat", lokasi, opsi)
  }

  const kolom = [
    k.accessor("foto", {
      header: "Foto",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => bukaDetail(row.original.id)}
          aria-label={`Lihat detail: ${judulEntri("absensi", row.original)}`}
          className="block h-10 w-14 overflow-hidden rounded-md border bg-muted/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          {row.original.foto ? (
            <img src={row.original.foto} alt="" className="size-full object-cover" loading="lazy" />
          ) : (
            <span className="flex size-full items-center justify-center text-muted-foreground">
              <IconPhoto className="size-4" aria-hidden="true" />
            </span>
          )}
        </button>
      ),
      enableSorting: false,
      enableHiding: false,
    }),
    k.accessor("waktu", {
      header: ({ column }) => <HeaderUrut column={column} judul="Waktu" />,
      cell: ({ row }) => {
        const [tgl, jam] = tanggalJam(row.original.waktu).split(", ")
        return (
          <time dateTime={row.original.waktu} className="grid text-sm whitespace-nowrap tabular-nums">
            <span>{tgl}</span>
            <span className="text-xs text-muted-foreground">{jam}</span>
          </time>
        )
      },
    }),
    k.accessor("kegiatan", {
      header: "Kegiatan",
      cell: ({ row }) => (
        <div className="grid max-w-[22rem] min-w-52 gap-0.5 whitespace-normal">
          <button
            type="button"
            onClick={() => bukaDetail(row.original.id)}
            className="w-fit rounded-sm text-left font-medium underline-offset-4 hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            {row.original.kegiatan}
          </button>
          <span className="text-xs text-muted-foreground">
            {JENIS_KEGIATAN[row.original.jenis]}
            {row.original.tempat && ` · ${row.original.tempat}`}
          </span>
        </div>
      ),
    }),
    k.accessor("tipe", {
      header: "Absen",
      cell: ({ row }) => <BadgeTipe tipe={row.original.tipe} />,
    }),
    k.accessor("lokasi", {
      header: "Lokasi",
      cell: ({ row }) =>
        row.original.lokasi ? (
          <a
            href={urlPeta(row.original.lokasi)}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 text-sm whitespace-nowrap underline-offset-4 hover:underline"
          >
            <IconMapPin className="size-4 text-muted-foreground" aria-hidden="true" />
            ±{row.original.lokasi.akurasi} m
            <span className="sr-only">, buka peta di tab baru</span>
          </a>
        ) : (
          <NadaBadge nada="peringatan" ikon={IconMapPinOff}>
            Tanpa GPS
          </NadaBadge>
        ),
      enableSorting: false,
    }),
    k.accessor("petugas", {
      header: "Petugas",
      cell: ({ row }) => <span className="text-sm">{row.original.petugas}</span>,
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={judulEntri("absensi", row.original)}
          onHapus={() => void hapus(row.original)}
        >
          <DropdownMenuItem onSelect={() => bukaDetail(row.original.id)}>Lihat detail</DropdownMenuItem>
        </MenuBaris>
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Bukti kehadiran petugas di kegiatan lapangan seperti Samsat Keliling, operasi gabungan, dan sosialisasi. Foto dan titik lokasi direkam saat absen. Data ini hanya untuk internal dan tidak tampil di portal.">
        <Button onClick={ed.bukaBaru}>
          <IconCamera />
          Absen sekarang
        </Button>
      </PengantarHalaman>
      <ToggleGroup
        type="single"
        variant="outline"
        value={periode}
        onValueChange={(p) => p && setPeriode(p as Periode)}
        className="w-fit flex-wrap"
        aria-label="Saring menurut waktu"
      >
        {(
          [
            ["semua", "Semua"],
            ["hari-ini", "Hari ini"],
            ["pekan", "7 hari terakhir"],
          ] as const
        ).map(([nilai, label]) => (
          <ToggleGroupItem key={nilai} value={nilai} className="px-3">
            {label} <span className="ml-1 text-muted-foreground tabular-nums">{jumlah(nilai)}</span>
          </ToggleGroupItem>
        ))}
      </ToggleGroup>
      <DataTable
        data={tersaring}
        memuat={isLoading}
        columns={kolom}
        label="Riwayat absensi lapangan"
        cari={{ placeholder: "Cari kegiatan, tempat, atau petugas…", teks: (a) => `${a.kegiatan} ${a.tempat} ${a.petugas} ${a.catatan}` }}
        filter={[
          {
            id: "jenis",
            label: "Kegiatan",
            opsi: (Object.keys(JENIS_KEGIATAN) as JenisKegiatan[]).map((j) => ({ nilai: j, label: JENIS_KEGIATAN[j] })),
            nilai: (a) => a.jenis,
          },
          {
            id: "tipe",
            label: "Absen",
            opsi: (Object.keys(TIPE_ABSEN) as TipeAbsen[]).map((t) => ({ nilai: t, label: TIPE_ABSEN[t] })),
            nilai: (a) => a.tipe,
          },
        ]}
        tindakanMassal={(baris, bersihkan) => (
          <TombolHapusMassal
            onClick={async () => {
              if (await hapusEntri(baris.map((b) => b.id))) bersihkan()
            }}
          />
        )}
        kosong={{
          judul: periode === "semua" ? "Belum ada absensi" : "Tidak ada absensi pada periode ini",
          deskripsi: "Tekan Absen sekarang saat tiba dan selesai di lokasi kegiatan.",
        }}
      />
      <DetailAbsensi absen={detail} tutup={tutupDetail} hapus={(a) => void hapus(a)} />
      <EntriSheet
        terbuka={ed.baru}
        tutup={ed.tutup}
        judul="Absen kegiatan lapangan"
        deskripsi={`Dicatat atas nama ${nama}${nip ? ` (NIP ${nip})` : ""}. Waktu absen diisi otomatis saat disimpan.`}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
        labelSimpan="Simpan absensi"
      >
        <FormField
          control={ed.form.control}
          name="tipe"
          render={({ field }) => (
            <div className="grid gap-2">
              <Label id="absen-tipe">Jenis absen</Label>
              <RadioGroup
                value={field.value}
                onValueChange={field.onChange}
                aria-labelledby="absen-tipe"
                className="grid grid-cols-2 gap-2"
              >
                {(Object.keys(TIPE_ABSEN) as TipeAbsen[]).map((t) => (
                  <Label
                    key={t}
                    htmlFor={`absen-tipe-${t}`}
                    className="gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                  >
                    <RadioGroupItem value={t} id={`absen-tipe-${t}`} />
                    {t === "datang" ? (
                      <IconLogin2 className="size-4 text-muted-foreground" aria-hidden="true" />
                    ) : (
                      <IconLogout2 className="size-4 text-muted-foreground" aria-hidden="true" />
                    )}
                    <span className="font-medium">{TIPE_ABSEN[t]}</span>
                  </Label>
                ))}
              </RadioGroup>
            </div>
          )}
        />
        {jadwalHariIni.length > 0 && (
          <div className="grid gap-2">
            <p className="text-sm font-medium" id="absen-jadwal">
              Jadwal Samsat Keliling hari ini
            </p>
            <div className="flex flex-wrap gap-2" role="group" aria-labelledby="absen-jadwal">
              {jadwalHariIni.map((j) => (
                <Button key={j.id} type="button" variant="outline" size="sm" className="h-auto py-1.5" onClick={() => pilihJadwal(j.lokasi)}>
                  <span className="grid text-left">
                    <span>{j.lokasi.replace(/^Kantor Kelurahan /, "")}</span>
                    <span className="text-xs font-normal text-muted-foreground tabular-nums">
                      {jamTitik(j.mulai)}–{jamTitik(j.selesai)}
                    </span>
                  </span>
                </Button>
              ))}
            </div>
          </div>
        )}
        <Baris>
          <FieldTeks control={ed.form.control} name="kegiatan" label="Nama kegiatan" wajib placeholder="Mis. Operasi gabungan Jl. El Tari" />
          <FieldPilih
            control={ed.form.control}
            name="jenis"
            label="Jenis kegiatan"
            opsi={(Object.keys(JENIS_KEGIATAN) as JenisKegiatan[]).map((j) => ({ nilai: j, label: JENIS_KEGIATAN[j] }))}
          />
        </Baris>
        <FieldFotoBukti form={ed.form} />
        <PanelLokasi form={ed.form} />
        <FieldTeks
          control={ed.form.control}
          name="tempat"
          label="Keterangan tempat"
          placeholder="Mis. Halaman Kantor Kelurahan Oesapa"
          deskripsi="Wajib diisi bila lokasi GPS tidak terekam."
        />
        <FieldArea control={ed.form.control} name="catatan" label="Catatan" rows={3} maks={500} placeholder="Opsional, mis. jumlah transaksi atau kendala di lapangan" />
      </EntriSheet>
    </Halaman>
  )
}
