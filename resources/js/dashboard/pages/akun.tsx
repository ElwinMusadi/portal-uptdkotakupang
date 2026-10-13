import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import {
  IconCopy,
  IconLink,
  IconLock,
  IconPlayerPause,
  IconPlayerPlay,
  IconRefresh,
  IconUserOff,
  IconUserPlus,
} from "@tabler/icons-react"
import { useForm, type DefaultValues } from "react-hook-form"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { DropdownMenuItem, DropdownMenuSeparator } from "@/components/ui/dropdown-menu"
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from "@/components/ui/empty"
import { Input } from "@/components/ui/input"
import {
  STATUS_AKUN,
  useDaftarAkun,
  useTambahAkun,
  useTerbitkanTautan,
  useUbahAkun,
  useUbahStatusAkun,
  type Akun,
  type StatusAkun,
  type TautanAktivasi,
} from "@/lib/akun"
import { ApiError } from "@/lib/api"
import { LABEL_PERAN, sesi } from "@/lib/auth"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { validasiFormulir } from "@/lib/formulir"
import { laporGalat } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  nip: z.string().trim().regex(/^\d{18}$/, "NIP harus 18 digit angka."),
  nama: v.wajib("Nama").max(255, "Maksimal 255 karakter."),
  surel: v.email,
  peran: z.enum(["pegawai", "administrator"], { error: "Pilih peran akun." }),
})
type FormAkun = z.infer<typeof schema>

const KOSONG: FormAkun = { nip: "", nama: "", surel: "", peran: "pegawai" }

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

export default function HalamanAkun() {
  if (sesi().peran !== "administrator") {
    return (
      <Halaman>
        <Empty className="border py-16">
          <EmptyHeader>
            <EmptyMedia variant="icon">
              <IconLock />
            </EmptyMedia>
            <EmptyTitle>Khusus Administrator</EmptyTitle>
            <EmptyDescription>
              Akun pegawai dikelola oleh Administrator di Sub Bagian Tata Usaha.
            </EmptyDescription>
          </EmptyHeader>
        </Empty>
      </Halaman>
    )
  }
  return <KelolaAkun />
}

function KelolaAkun() {
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
          cari={{ placeholder: "Cari nama, NIP, atau surel…", teks: (a) => `${a.nama} ${a.nip} ${a.surel ?? ""}` }}
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
          <FieldTeks
            control={editor.form.control}
            name="nip"
            label="NIP"
            wajib
            inputMode="numeric"
            autoComplete="off"
            maxLength={18}
            placeholder="18 digit"
          />
        )}
        <FieldTeks control={editor.form.control} name="nama" label="Nama lengkap" wajib autoComplete="off" />
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
            deskripsi="Administrator dapat mengelola akun pegawai."
          />
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
    form.reset(akun ? { nip: akun.nip, nama: akun.nama, surel: akun.surel ?? "", peran: akun.peran } : KOSONG)
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
        await ubah.mutateAsync({ id: akun.id, data: { nama: nilai.nama, surel: nilai.surel, peran: nilai.peran } })
        toast.success("Perubahan akun disimpan")
      } else {
        tampilkanTautan(await tambah.mutateAsync(nilai))
      }
      form.reset(nilai)
      tutup()
    } catch (galat) {
      const isian = galat instanceof ApiError ? Object.entries(galat.isian) : []
      if (isian.length === 0) {
        laporGalat(galat)
        return
      }
      for (const [nama, pesan] of isian) {
        if (nama in KOSONG) form.setError(nama as keyof FormAkun, { message: pesan }, { shouldFocus: true })
      }
      toast.error("Periksa kembali isian yang ditandai merah.")
    }
  }

  return {
    form,
    akun,
    terbuka,
    bukaBaru: () => buka("baru", "1"),
    bukaUbah: (id: string) => buka("ubah", id),
    tutup,
    simpan,
    menyimpan: tambah.isPending || ubah.isPending,
  }
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
