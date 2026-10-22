import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import {
  IconCircleCheckFilled,
  IconCopy,
  IconLink,
  IconPencil,
  IconPlayerPause,
  IconPlayerPlay,
  IconRefresh,
  IconSelector,
  IconUserOff,
  IconUserPlus,
  IconUserSearch,
} from "@tabler/icons-react"
import { useForm, type Control, type DefaultValues, type UseFormReturn } from "react-hook-form"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { GRUP_MODUL } from "@/app/nav"
import { useKonfirmasi } from "@/components/data/confirm"
import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris } from "@/components/data/row-actions"
import { NadaBadge } from "@/components/data/status-badge"
import { EntriSheet } from "@/components/form/entri-sheet"
import { FieldPilih, FieldTeks } from "@/components/form/fields"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/dropdown-menu"
import { FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Popover, PopoverAnchor, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Spinner } from "@/components/ui/spinner"
import {
  MIN_CARI_SIKAP,
  STATUS_AKUN,
  useAmbilPegawaiSikap,
  useCariPegawaiSikap,
  useDaftarAkun,
  useTambahAkun,
  useTerbitkanTautan,
  useUbahAkun,
  useUbahStatusAkun,
  type Akun,
  type PegawaiSikap,
  type StatusAkun,
  type TautanAktivasi,
} from "@/lib/akun"
import { ApiError } from "@/lib/api"
import { LABEL_PERAN, SEMUA_MODUL, sesi, type Modul } from "@/lib/auth"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { validasiFormulir } from "@/lib/formulir"
import { laporGalat } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  nip: z.string().trim().regex(/^\d{18}$/, "NIP harus 18 digit angka."),
  nama: v.wajib("Nama").max(255, "Maksimal 255 karakter."),
  surel: v.email,
  peran: z.enum(["pegawai", "administrator"], { error: "Pilih peran akun." }),
  modul: z.array(z.enum(SEMUA_MODUL)),
  /** "sikap" bila NIP dan nama dipilih dari SIKAP dan terkunci (D-075) */
  sumber: z.enum(["manual", "sikap"]),
})
type FormAkun = z.infer<typeof schema>

const KOSONG: FormAkun = { nip: "", nama: "", surel: "", peran: "pegawai", modul: [], sumber: "manual" }

/** Judul modul seperti di menu samping */
const JUDUL_MODUL = Object.fromEntries(GRUP_MODUL.flatMap((g) => g.modul.map((m) => [m.kunci, m.judul]))) as Record<Modul, string>

const OPSI_PERAN = [
  { nilai: "pegawai", label: LABEL_PERAN.pegawai },
  { nilai: "administrator", label: LABEL_PERAN.administrator },
]

/** Label tindakan penerbitan tautan; untuk akun yang sudah dipakai ini berarti atur ulang kata sandi */
const LABEL_TAUTAN: Record<StatusAkun, string> = {
  pending: "Tautan aktivasi baru",
  active: "Atur ulang kata sandi",
  suspended: "Atur ulang kata sandi",
  deactivated: "Aktifkan dengan tautan baru",
}

const k = pembantuKolom<Akun>()

/** Khusus Administrator; Layout menolak pengguna lain lewat menu "Akun Pegawai" (khusus: administrator) */
export default function HalamanAkun() {
  const { nip: nipSaya } = sesi()
  const { data, isLoading, error, refetch, isRefetching } = useDaftarAkun()
  const ubahStatus = useUbahStatusAkun()
  const terbitkan = useTerbitkanTautan()
  const konfirmasi = useKonfirmasi()
  const editor = useEditorAkun(data)
  const [tautan, setTautan] = React.useState<TautanAktivasi | null>(null)

  const gantiStatus = async (akun: Akun, status: Exclude<StatusAkun, "pending">) => {
    const pesan = {
      suspended: {
        judul: `Tangguhkan akun ${akun.nama}?`,
        deskripsi: `${akun.nama} keluar dari dashboard dan tidak bisa masuk sampai akunnya diaktifkan kembali. Perangkat yang diingat harus masuk ulang.`,
        label: "Tangguhkan",
        bahaya: true,
        berhasil: "Akun ditangguhkan",
      },
      active: {
        judul: `Aktifkan kembali akun ${akun.nama}?`,
        deskripsi: `${akun.nama} bisa masuk lagi dengan kata sandi lamanya.`,
        label: "Aktifkan kembali",
        bahaya: false,
        berhasil: "Akun diaktifkan kembali",
      },
      deactivated: {
        judul: `Nonaktifkan akun ${akun.nama}?`,
        deskripsi: `${akun.nama} keluar dan tidak bisa masuk lagi. Untuk memakai akun ini kembali, terbitkan tautan aktivasi baru.`,
        label: "Nonaktifkan",
        bahaya: true,
        berhasil: "Akun dinonaktifkan",
      },
    }[status]
    if (!(await konfirmasi(pesan))) return
    try {
      await ubahStatus.mutateAsync({ id: akun.id, status })
      toast.success(pesan.berhasil)
    } catch (e) {
      laporGalat(e, "Status akun gagal diubah")
    }
  }

  const terbitkanTautan = async (akun: Akun) => {
    const pesan =
      akun.status === "pending"
        ? {
            judul: `Terbitkan tautan baru untuk ${akun.nama}?`,
            deskripsi: "Tautan aktivasi sebelumnya tidak berlaku lagi.",
            label: "Terbitkan tautan",
          }
        : akun.status === "deactivated"
          ? {
              judul: `Aktifkan kembali akun ${akun.nama}?`,
              deskripsi: "Akun menunggu aktivasi sampai pemiliknya memilih kata sandi baru lewat tautan yang Anda kirimkan.",
              label: "Terbitkan tautan",
            }
          : {
              judul: `Atur ulang kata sandi ${akun.nama}?`,
              deskripsi: `Kata sandi ${akun.nama} langsung tidak berlaku dan ia keluar dari semua perangkat. Ia memilih kata sandi baru lewat tautan aktivasi yang Anda kirimkan.`,
              label: "Atur ulang",
              bahaya: true,
            }
    if (!(await konfirmasi(pesan))) return
    try {
      setTautan(await terbitkan.mutateAsync(akun.id))
    } catch (e) {
      laporGalat(e, "Tautan gagal diterbitkan")
    }
  }

  const kolom = [
    k.accessor("nama", {
      header: "Pegawai",
      cell: ({ row }) => {
        const akun = row.original
        return (
          <button
            type="button"
            onClick={() => editor.bukaUbah(akun.id)}
            className="grid min-w-48 gap-0.5 rounded-md text-left focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            <span className="flex items-center gap-2 font-medium">
              <span className="underline-offset-4 hover:underline">{akun.nama}</span>
              {akun.nip === nipSaya && <Badge variant="secondary">Anda</Badge>}
            </span>
            <span className="text-xs text-muted-foreground">{akun.surel ?? "Tanpa surel"}</span>
          </button>
        )
      },
      enableHiding: false,
    }),
    k.accessor("nip", {
      header: "NIP",
      cell: ({ row }) => <span className="font-mono text-sm tabular-nums">{row.original.nip}</span>,
    }),
    k.accessor("peran", {
      header: "Peran",
      cell: ({ row }) => LABEL_PERAN[row.original.peran],
    }),
    k.accessor("modul", {
      header: "Modul",
      enableSorting: false,
      cell: ({ row }) => {
        const akun = row.original
        if (akun.peran === "administrator") return <span className="text-sm">Semua modul</span>
        if (akun.modul.length === 0) return <span className="text-sm text-muted-foreground">Belum ada</span>
        const judul = akun.modul.map((m) => JUDUL_MODUL[m])
        return (
          <span className="block max-w-56 truncate text-sm" title={judul.join(", ")}>
            {judul.length > 2 ? `${judul.slice(0, 2).join(", ")} +${judul.length - 2}` : judul.join(", ")}
          </span>
        )
      },
    }),
    k.accessor("status", {
      header: "Status",
      cell: ({ row }) => {
        const meta = STATUS_AKUN[row.original.status]
        return (
          <NadaBadge nada={meta.nada} ikon={meta.ikon}>
            {meta.label}
          </NadaBadge>
        )
      },
    }),
    k.accessor("diperbarui", {
      header: "Diperbarui",
      cell: ({ row }) => (
        <span className="text-sm text-muted-foreground" title={tanggalJam(row.original.diperbarui)}>
          {waktuRelatif(row.original.diperbarui)}
        </span>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => {
        const akun = row.original
        return (
          <MenuBaris label={akun.nama} onSunting={() => editor.bukaUbah(akun.id)}>
            {akun.nip !== nipSaya && (
              <>
                {akun.status === "suspended" && (
                  <DropdownMenuItem onSelect={() => void gantiStatus(akun, "active")}>
                    <IconPlayerPlay />
                    Aktifkan kembali
                  </DropdownMenuItem>
                )}
                {akun.status === "active" && (
                  <DropdownMenuItem onSelect={() => void gantiStatus(akun, "suspended")}>
                    <IconPlayerPause />
                    Tangguhkan
                  </DropdownMenuItem>
                )}
                <DropdownMenuItem onSelect={() => void terbitkanTautan(akun)}>
                  <IconLink />
                  {LABEL_TAUTAN[akun.status]}
                </DropdownMenuItem>
                {akun.status !== "deactivated" && (
                  <>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem variant="destructive" onSelect={() => void gantiStatus(akun, "deactivated")}>
                      <IconUserOff />
                      Nonaktifkan
                    </DropdownMenuItem>
                  </>
                )}
              </>
            )}
          </MenuBaris>
        )
      },
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Buat akun untuk pegawai yang mengelola portal. Pemilik akun memilih kata sandinya sendiri lewat tautan aktivasi yang Anda kirimkan, jadi Administrator tidak pernah mengetahui kata sandi siapa pun.">
        <Button onClick={editor.bukaBaru}>
          <IconUserPlus />
          Tambah akun
        </Button>
      </PengantarHalaman>
      {error ? (
        <Alert variant="destructive">
          <AlertTitle>Daftar akun gagal dimuat</AlertTitle>
          <AlertDescription>
            <p>{error instanceof Error ? error.message : String(error)}</p>
            <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()} disabled={isRefetching}>
              <IconRefresh />
              Coba lagi
            </Button>
          </AlertDescription>
        </Alert>
      ) : (
        <DataTable
          data={data}
          memuat={isLoading}
          columns={kolom}
          label="Daftar akun pegawai"
          pilih={false}
          ukuranHalaman={20}
          cari={{
            placeholder: "Cari nama, NIP, surel, atau modul…",
            teks: (a) => `${a.nama} ${a.nip} ${a.surel ?? ""} ${a.modul.map((m) => JUDUL_MODUL[m]).join(" ")}`,
          }}
          filter={[
            {
              id: "status",
              label: "Status",
              opsi: (Object.keys(STATUS_AKUN) as StatusAkun[]).map((s) => ({ nilai: s, label: STATUS_AKUN[s].label })),
              nilai: (a) => a.status,
            },
            { id: "peran", label: "Peran", opsi: OPSI_PERAN, nilai: (a) => a.peran },
          ]}
          kosong={{ judul: "Belum ada akun", deskripsi: "Tambahkan akun untuk pegawai yang mengelola konten portal." }}
        />
      )}
      <EntriSheet
        terbuka={editor.terbuka}
        tutup={editor.tutup}
        judul={editor.akun ? "Sunting akun" : "Tambah akun"}
        deskripsi={
          !editor.akun
            ? "Akun baru menunggu aktivasi. Setelah disimpan, Anda mendapat tautan aktivasi untuk dikirim kepada pemiliknya."
            : editor.akun.nip === nipSaya
              ? "NIP tidak bisa diubah. Kata sandi Anda sendiri diatur ulang oleh Administrator lain."
              : "NIP tidak bisa diubah. Untuk mengganti kata sandi, pakai Atur ulang kata sandi di menu akun."
        }
        form={editor.form}
        simpan={(e) => editor.simpan(e, setTautan)}
        menyimpan={editor.menyimpan}
        labelSimpan={editor.akun ? "Simpan" : "Buat akun"}
      >
        {editor.akun ? (
          <div className="grid gap-2 text-sm">
            <p className="font-medium">NIP</p>
            <p className="font-mono tabular-nums">{editor.akun.nip}</p>
          </div>
        ) : (
          <>
            <PilihPegawaiSikap form={editor.form} />
            <FieldTeks
              control={editor.form.control}
              name="nip"
              label="NIP"
              wajib
              inputMode="numeric"
              autoComplete="off"
              maxLength={18}
              placeholder="18 digit"
              readOnly={editor.dariSikap}
              className={editor.dariSikap ? "[&_input]:bg-muted/50" : undefined}
            />
          </>
        )}
        <FieldTeks
          control={editor.form.control}
          name="nama"
          label="Nama lengkap"
          wajib
          autoComplete="off"
          readOnly={editor.dariSikap}
          className={editor.dariSikap ? "[&_input]:bg-muted/50" : undefined}
          deskripsi={editor.dariSikap ? "NIP dan nama mengikuti SIKAP. Pilih Isi manual untuk mengubahnya." : undefined}
        />
        <FieldTeks
          control={editor.form.control}
          name="surel"
          label="Surel dinas"
          type="email"
          autoComplete="off"
          deskripsi="Opsional."
        />
        {editor.akun?.nip === nipSaya ? (
          <div className="grid gap-2 text-sm">
            <p className="font-medium">Peran</p>
            <p>{LABEL_PERAN[editor.akun.peran]}</p>
            <p className="text-muted-foreground">
              Peran akun Anda sendiri tidak bisa diubah, agar selalu ada Administrator.
            </p>
          </div>
        ) : (
          <FieldPilih
            control={editor.form.control}
            name="peran"
            label="Peran"
            wajib
            opsi={OPSI_PERAN}
            deskripsi="Administrator dapat mengelola akun pegawai dan semua modul."
          />
        )}
        {editor.peran === "pegawai" ? (
          <PilihModul control={editor.form.control} />
        ) : (
          <div className="grid gap-2 text-sm">
            <p className="font-medium">Modul</p>
            <p className="text-muted-foreground">Administrator mengelola semua modul.</p>
          </div>
        )}
      </EntriSheet>
      <DialogTautan hasil={tautan} tutup={() => setTautan(null)} />
    </Halaman>
  )
}

/**
 * Panel tambah/sunting akun. Seperti panel konten lain, status buka/tutup ada
 * di URL (?baru=1 atau ?ubah=<id>) sehingga pencarian bisa membukanya langsung.
 */
function useEditorAkun(data: Akun[] | undefined) {
  const [params, setParams] = useSearchParams()
  const tambah = useTambahAkun()
  const ubah = useUbahAkun()

  const idUbah = params.get("ubah")
  const baru = params.get("baru") === "1"
  const akun = idUbah ? (data?.find((a) => a.id === idUbah) ?? null) : null
  const terbuka = baru || !!akun

  const form = useForm<FormAkun>({
    defaultValues: KOSONG as DefaultValues<FormAkun>,
    resolver: zodResolver(schema),
    mode: "onTouched",
  })

  const kunciTerbuka = baru ? "baru" : (akun?.id ?? "")
  React.useEffect(() => {
    if (!kunciTerbuka) return
    form.reset(
      akun
        ? { nip: akun.nip, nama: akun.nama, surel: akun.surel ?? "", peran: akun.peran, modul: akun.modul, sumber: "manual" }
        : KOSONG
    )
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [kunciTerbuka])

  const tutup = React.useCallback(() => {
    setParams(
      (p) => {
        const n = new URLSearchParams(p)
        n.delete("baru")
        n.delete("ubah")
        return n
      },
      { replace: true }
    )
  }, [setParams])

  const buka = (kunci: "baru" | "ubah", nilai: string) =>
    setParams((p) => {
      const n = new URLSearchParams(p)
      n.delete("baru")
      n.delete("ubah")
      n.set(kunci, nilai)
      return n
    })

  const simpan = async (e: React.BaseSyntheticEvent | undefined, tampilkanTautan: (t: TautanAktivasi) => void) => {
    e?.preventDefault()
    if (!(await validasiFormulir(form))) {
      toast.error("Periksa kembali isian yang ditandai merah.")
      return
    }
    const nilai = form.getValues()
    try {
      if (akun) {
        await ubah.mutateAsync({
          id: akun.id,
          data: { nama: nilai.nama, surel: nilai.surel, peran: nilai.peran, modul: nilai.modul },
        })
        toast.success("Perubahan akun disimpan")
      } else {
        tampilkanTautan(await tambah.mutateAsync(nilai))
      }
      form.reset(nilai)
      tutup()
    } catch (galat) {
      const isian = galat instanceof ApiError ? Object.entries(galat.isian) : []
      if (isian.length === 0) {
        // Server mengambil ulang pegawai SIKAP saat menyimpan; bila SIKAP sedang tidak bisa dihubungi, akun belum dibuat.
        const sikapGagal = !akun && nilai.sumber === "sikap" && galat instanceof ApiError && galat.status === 503
        laporGalat(galat, sikapGagal ? "Akun belum dibuat. Coba lagi, atau pilih Isi manual." : undefined)
        return
      }
      for (const [kunci, pesan] of isian) {
        // Galat daftar datang per butir (modul.0); tampilkan di isiannya.
        const nama = kunci.split(".")[0]
        if (nama in KOSONG) form.setError(nama as keyof FormAkun, { message: pesan }, { shouldFocus: true })
      }
      toast.error("Periksa kembali isian yang ditandai merah.")
    }
  }

  return {
    form,
    akun,
    /** Peran yang sedang dipilih di formulir */
    peran: form.watch("peran"),
    /** NIP dan nama akun baru dipilih dari SIKAP sehingga terkunci */
    dariSikap: !akun && form.watch("sumber") === "sikap",
    terbuka,
    bukaBaru: () => buka("baru", "1"),
    bukaUbah: (id: string) => buka("ubah", id),
    tutup,
    simpan,
    menyimpan: tambah.isPending || ubah.isPending,
  }
}

/**
 * Pemilih pegawai SIKAP di Tambah akun (D-075). Memilih pegawai mengisi NIP
 * dan nama lalu menguncinya; server mengambil ulang namanya dari SIKAP saat
 * akun disimpan. Jabatan dan unit hanya ditampilkan di sini (D-027). Petugas
 * dari luar UPTD (D-051) tetap diisi manual, begitu juga saat SIKAP gagal.
 */
function PilihPegawaiSikap({ form }: { form: UseFormReturn<FormAkun> }) {
  const [buka, setBuka] = React.useState(false)
  const [cari, setCari] = React.useState("")
  const [kataKunci, setKataKunci] = React.useState("")
  const [dipilih, setDipilih] = React.useState<PegawaiSikap | null>(null)
  const [galat, setGalat] = React.useState<string | null>(null)
  const hasil = useCariPegawaiSikap(kataKunci)
  const ambil = useAmbilPegawaiSikap()
  const dariSikap = form.watch("sumber") === "sikap"

  // SIKAP baru ditanya setelah pengetikan berhenti sebentar
  React.useEffect(() => {
    const t = window.setTimeout(() => setKataKunci(cari), 300)
    return () => window.clearTimeout(t)
  }, [cari])

  const pilih = async (pegawai: PegawaiSikap) => {
    setBuka(false)
    setGalat(null)
    try {
      const terbaru = await ambil.mutateAsync(pegawai.nip)
      if (terbaru.sudahPunyaAkun) {
        setGalat(`${terbaru.nama} sudah punya akun. Cari akunnya di daftar Akun Pegawai.`)
        return
      }
      form.setValue("nip", terbaru.nip, { shouldDirty: true, shouldValidate: true })
      form.setValue("nama", terbaru.nama, { shouldDirty: true, shouldValidate: true })
      form.setValue("sumber", "sikap", { shouldDirty: true })
      setDipilih(terbaru)
    } catch (e) {
      if (e instanceof ApiError && e.status === 401) {
        laporGalat(e)
        return
      }
      setGalat(`${e instanceof Error ? e.message : String(e)} Anda tetap bisa mengisi NIP dan nama secara manual.`)
    }
  }

  const isiManual = () => {
    form.setValue("sumber", "manual", { shouldDirty: true })
    setDipilih(null)
    setGalat(null)
  }

  const ketik = cari.trim()
  const keterangan = (p: PegawaiSikap) => [p.jabatan, p.unit].filter(Boolean).join(" · ")

  return (
    <div className="grid gap-2">
      <p className="text-sm font-medium">Pegawai SIKAP</p>
      <Popover
        open={buka}
        onOpenChange={(o) => {
          setBuka(o)
          // Pencarian yang gagal dicoba lagi saat pemilih dibuka, mungkin SIKAP sudah pulih
          if (o && hasil.isError) void hasil.refetch()
        }}
      >
        <PopoverAnchor asChild>
          {dariSikap ? (
            <div className="grid gap-3 rounded-lg border bg-muted/40 p-3">
              <div className="grid gap-0.5 text-sm">
                <p className="flex items-center gap-2 font-medium">
                  <IconCircleCheckFilled className="size-4 shrink-0 text-primary" aria-hidden="true" />
                  Dipilih dari SIKAP
                </p>
                <p className="text-muted-foreground">
                  {(dipilih && keterangan(dipilih)) || "Jabatan dan unit belum tercatat di SIKAP."}
                </p>
              </div>
              <div className="flex flex-wrap gap-2">
                <PopoverTrigger asChild>
                  <Button variant="outline" size="sm">
                    <IconUserSearch />
                    Ganti
                  </Button>
                </PopoverTrigger>
                <Button type="button" variant="ghost" size="sm" onClick={isiManual}>
                  <IconPencil />
                  Isi manual
                </Button>
              </div>
            </div>
          ) : (
            <PopoverTrigger asChild>
              <Button
                variant="outline"
                role="combobox"
                aria-expanded={buka}
                disabled={ambil.isPending}
                className="w-full justify-between font-normal text-muted-foreground"
              >
                <span className="flex min-w-0 items-center gap-2">
                  {ambil.isPending ? <Spinner /> : <IconUserSearch />}
                  <span className="truncate">{ambil.isPending ? "Memuat data pegawai…" : "Cari nama atau NIP pegawai…"}</span>
                </span>
                <IconSelector className="opacity-50" />
              </Button>
            </PopoverTrigger>
          )}
        </PopoverAnchor>
        <PopoverContent className="w-(--radix-popover-trigger-width) min-w-72 p-0" align="start">
          <Command shouldFilter={false}>
            <CommandInput placeholder="Ketik nama atau NIP…" value={cari} onValueChange={setCari} />
            <CommandList>
              {ketik.length < MIN_CARI_SIKAP ? (
                <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                  Ketik minimal {MIN_CARI_SIKAP} huruf nama atau angka NIP.
                </p>
              ) : hasil.isError ? (
                <div role="alert" className="grid gap-1 px-3 py-4 text-sm">
                  <p className="text-destructive">{hasil.error.message}</p>
                  <p className="text-muted-foreground">Tutup pencarian ini lalu isi NIP dan nama secara manual.</p>
                </div>
              ) : !hasil.data || ketik !== kataKunci.trim() || (hasil.isFetching && hasil.isPlaceholderData) ? (
                <p className="flex items-center justify-center gap-2 px-3 py-6 text-sm text-muted-foreground">
                  <Spinner />
                  Mencari di SIKAP…
                </p>
              ) : hasil.data.pegawai.length === 0 ? (
                <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                  Tidak ada pegawai aktif SIKAP yang cocok dengan “{ketik}”.
                </p>
              ) : (
                <>
                  <CommandGroup heading={`${hasil.data.total} pegawai aktif`}>
                    {hasil.data.pegawai.map((p) => (
                      <CommandItem
                        key={p.nip}
                        value={p.nip}
                        disabled={p.sudahPunyaAkun}
                        onSelect={() => void pilih(p)}
                        className="items-start"
                      >
                        <span className="grid min-w-0 flex-1 gap-0.5">
                          <span className="flex items-center justify-between gap-2">
                            <span className="truncate font-medium">{p.nama}</span>
                            {p.sudahPunyaAkun && (
                              <Badge variant="secondary" className="shrink-0">
                                Sudah punya akun
                              </Badge>
                            )}
                          </span>
                          <span className="font-mono text-xs text-muted-foreground tabular-nums">{p.nip}</span>
                          {keterangan(p) && (
                            <span className="truncate text-xs text-muted-foreground">{keterangan(p)}</span>
                          )}
                        </span>
                      </CommandItem>
                    ))}
                  </CommandGroup>
                  {hasil.data.total > hasil.data.pegawai.length && (
                    <p className="border-t px-3 py-2 text-xs text-muted-foreground">
                      Menampilkan {hasil.data.pegawai.length} dari {hasil.data.total} pegawai. Ketik lebih lengkap untuk
                      mempersempit.
                    </p>
                  )}
                </>
              )}
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
      {galat ? (
        <p role="alert" className="text-sm text-destructive">
          {galat}
        </p>
      ) : (
        !dariSikap && (
          <p className="text-sm text-muted-foreground">
            Pilih pegawai UPTD agar NIP dan nama sama dengan SIKAP. Petugas dari luar UPTD, mis. kepolisian atau bank,
            diisi manual di bawah.
          </p>
        )
      )}
    </div>
  )
}

/** Centang modul yang dikelola pegawai, dikelompokkan seperti menu samping */
function PilihModul({ control }: { control: Control<FormAkun> }) {
  return (
    <FormField
      control={control}
      name="modul"
      render={({ field }) => {
        const dipilih = new Set(field.value)
        const ubah = (kunci: Modul, centang: boolean) => {
          if (centang) dipilih.add(kunci)
          else dipilih.delete(kunci)
          field.onChange(SEMUA_MODUL.filter((m) => dipilih.has(m)))
        }
        return (
          <FormItem>
            <FormLabel>Modul yang dikelola</FormLabel>
            <FormDescription>
              Pegawai hanya melihat menu dan ringkasan untuk modul yang dicentang. Absensi, kalkulator, dan panduan
              selalu tersedia.
            </FormDescription>
            <div className="grid gap-4 pt-1">
              {GRUP_MODUL.map((grup) => (
                <fieldset key={grup.label} className="grid gap-2">
                  <legend className="mb-1 text-xs font-medium text-muted-foreground">{grup.label}</legend>
                  <div className="grid gap-2 sm:grid-cols-2">
                    {grup.modul.map((m) => (
                      <label key={m.kunci} className="flex items-center gap-2 text-sm">
                        <Checkbox
                          checked={dipilih.has(m.kunci)}
                          onCheckedChange={(c) => ubah(m.kunci, c === true)}
                          onBlur={field.onBlur}
                        />
                        {m.judul}
                      </label>
                    ))}
                  </div>
                </fieldset>
              ))}
            </div>
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

/** Tautan aktivasi hanya ditampilkan sekali, tepat setelah diterbitkan */
function DialogTautan({ hasil, tutup }: { hasil: TautanAktivasi | null; tutup: () => void }) {
  const kotak = React.useRef<HTMLInputElement>(null)

  const salin = async () => {
    if (!hasil) return
    try {
      await navigator.clipboard.writeText(hasil.tautan)
      toast.success("Tautan disalin")
      return
    } catch {
      // Clipboard API hanya ada di HTTPS atau localhost; di HTTP biasa salin dari kotak tautan.
    }
    kotak.current?.select()
    if (document.execCommand("copy")) {
      toast.success("Tautan disalin")
    } else {
      toast.error("Tautan tidak bisa disalin otomatis", { description: "Pilih isi kotak tautan lalu salin secara manual." })
    }
  }

  return (
    <Dialog open={!!hasil} onOpenChange={(buka) => !buka && tutup()}>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Tautan aktivasi untuk {hasil?.akun.nama}</DialogTitle>
          <DialogDescription>
            Kirim tautan ini hanya kepada pemilik akun (NIP {hasil?.akun.nip}), misalnya lewat pesan pribadi, bukan di
            grup. Tautan berlaku sampai {tanggalJam(hasil?.kedaluwarsa)} dan hanya bisa dipakai sekali.
          </DialogDescription>
        </DialogHeader>
        <div className="flex gap-2">
          <Input
            ref={kotak}
            readOnly
            value={hasil?.tautan ?? ""}
            aria-label="Tautan aktivasi"
            className="font-mono text-xs"
            onFocus={(e) => e.currentTarget.select()}
          />
          <Button onClick={() => void salin()}>
            <IconCopy />
            Salin
          </Button>
        </div>
        <p className="text-sm text-muted-foreground">
          Tautan ini tidak bisa ditampilkan lagi. Bila hilang atau kedaluwarsa, terbitkan tautan baru dari menu akun.
        </p>
        <DialogFooter>
          <Button variant="outline" onClick={tutup}>
            Selesai
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
