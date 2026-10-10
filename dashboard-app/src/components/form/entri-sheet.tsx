import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { IconDeviceFloppy } from "@tabler/icons-react"
import {
  useForm,
  type DefaultValues,
  type FieldValues,
  type Resolver,
  type UseFormReturn,
} from "react-hook-form"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import type { z } from "zod"

import { useKonfirmasi } from "@/components/data/confirm"
import { Button } from "@/components/ui/button"
import { Form } from "@/components/ui/form"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { Spinner } from "@/components/ui/spinner"
import type { Draf, Koleksi, NamaKoleksi } from "@/lib/api"
import { validasiFormulir } from "@/lib/formulir"
import { useKoleksi, useTambahEntri, useUbahEntri } from "@/lib/queries"
import { cn } from "@/lib/utils"

interface OpsiEditor<K extends NamaKoleksi, F extends FieldValues> {
  koleksi: K
  schema: z.ZodType
  /** Nilai formulir untuk entri baru */
  kosong: () => F
  /** Nilai formulir dari entri yang ada */
  dariEntri: (e: Koleksi[K]) => F
  /** Data lengkap entri baru dari nilai formulir */
  keEntriBaru: (f: F) => Draf<Koleksi[K]>
  /** Perubahan untuk entri lama (default: nilai formulir apa adanya) */
  kePatch?: (f: F, lama: Koleksi[K]) => Partial<Koleksi[K]>
  /** Nama jenis entri untuk pesan, mis. "dokumen" */
  nama: string
}

/**
 * Pengelola panel sunting entri koleksi. Status buka/tutup disimpan di URL
 * (?baru=1 atau ?ubah=<id>) sehingga menu "Buat konten" dan pencarian bisa
 * langsung membuka formulir yang tepat.
 */
export function useEditorEntri<K extends NamaKoleksi, F extends FieldValues>(opsi: OpsiEditor<K, F>) {
  const [params, setParams] = useSearchParams()
  const { data } = useKoleksi(opsi.koleksi)
  const tambah = useTambahEntri(opsi.koleksi)
  const ubah = useUbahEntri(opsi.koleksi)

  const idUbah = params.get("ubah")
  const baru = params.get("baru") === "1"
  const entri = idUbah ? (data?.find((x) => x.id === idUbah) ?? null) : null
  const terbuka = baru || !!entri

  const form = useForm<F>({
    defaultValues: opsi.kosong() as DefaultValues<F>,
    resolver: zodResolver(opsi.schema as never) as unknown as Resolver<F>,
    mode: "onTouched",
  })

  // isi ulang formulir setiap kali panel dibuka untuk entri berbeda
  const kunciTerbuka = baru ? "baru" : (entri?.id ?? "")
  React.useEffect(() => {
    if (!kunciTerbuka) return
    form.reset((entri ? opsi.dariEntri(entri) : opsi.kosong()) as DefaultValues<F>)
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

  const bukaBaru = () => setParams((p) => withParam(p, "baru", "1"))
  const bukaUbah = (id: string) => setParams((p) => withParam(p, "ubah", id))

  /** Validasi lalu simpan; kembalikan true bila berhasil */
  const simpanNilai = async (tutupSetelah: boolean): Promise<boolean> => {
    if (!(await validasiFormulir(form))) {
      toast.error("Periksa kembali isian yang ditandai merah.")
      return false
    }
    const nilai = form.getValues()
    try {
      if (entri) {
        await ubah.mutateAsync({
          id: entri.id,
          patch: opsi.kePatch ? opsi.kePatch(nilai, entri) : (nilai as unknown as Partial<Koleksi[K]>),
        })
        toast.success(`Perubahan ${opsi.nama} disimpan`)
      } else {
        await tambah.mutateAsync(opsi.keEntriBaru(nilai))
        toast.success(`${kapital(opsi.nama)} ditambahkan`)
      }
      form.reset(nilai as DefaultValues<F>)
      if (tutupSetelah) tutup()
      return true
    } catch {
      return false
    }
  }

  const simpan = async (e?: React.BaseSyntheticEvent) => {
    e?.preventDefault()
    await simpanNilai(true)
  }

  return {
    form,
    entri,
    terbuka,
    baru,
    bukaBaru,
    bukaUbah,
    tutup,
    simpan,
    /** Simpan tanpa menutup panel (dipakai sebelum aksi alur publikasi) */
    simpanTetapBuka: () => simpanNilai(false),
    menyimpan: tambah.isPending || ubah.isPending,
    /** true bila ?ubah=<id> menunjuk entri yang tidak ada */
    tidakDitemukan: !!idUbah && !!data && !entri,
  }
}

function withParam(p: URLSearchParams, k: string, v: string) {
  const n = new URLSearchParams(p)
  n.delete("baru")
  n.delete("ubah")
  n.set(k, v)
  return n
}

function kapital(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}

export function EntriSheet<F extends FieldValues>({
  terbuka,
  tutup,
  judul,
  deskripsi,
  form,
  simpan,
  menyimpan,
  children,
  lebar = "sm:max-w-xl",
  kiri,
  labelSimpan = "Simpan",
}: {
  terbuka: boolean
  tutup: () => void
  judul: React.ReactNode
  deskripsi?: React.ReactNode
  form: UseFormReturn<F>
  simpan: (e?: React.BaseSyntheticEvent) => Promise<void>
  menyimpan: boolean
  children: React.ReactNode
  lebar?: string
  /** Isi tambahan di kiri bilah bawah (mis. tombol hapus) */
  kiri?: React.ReactNode
  labelSimpan?: string
}) {
  const konfirmasi = useKonfirmasi()

  const minta = async (buka: boolean) => {
    if (buka) return
    if (form.formState.isDirty) {
      const ya = await konfirmasi({
        judul: "Buang perubahan?",
        deskripsi: "Perubahan pada formulir ini belum disimpan.",
        label: "Buang perubahan",
        bahaya: true,
      })
      if (!ya) return
      form.reset()
    }
    tutup()
  }

  return (
    <Sheet open={terbuka} onOpenChange={(o) => void minta(o)}>
      <SheetContent className={cn("flex w-full flex-col gap-0 p-0", lebar)}>
        <SheetHeader className="border-b pr-12">
          <SheetTitle>{judul}</SheetTitle>
          {deskripsi ? (
            <SheetDescription>{deskripsi}</SheetDescription>
          ) : (
            <SheetDescription className="sr-only">Formulir {String(judul)}</SheetDescription>
          )}
        </SheetHeader>
        <Form {...form}>
          <form onSubmit={simpan} noValidate className="flex min-h-0 flex-1 flex-col">
            <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">{children}</div>
            <SheetFooter className="flex-row items-center gap-2 border-t">
              {kiri}
              <div className="ml-auto flex gap-2">
                <Button type="button" variant="outline" onClick={() => void minta(false)}>
                  Batal
                </Button>
                <Button type="submit" disabled={menyimpan}>
                  {menyimpan ? <Spinner /> : <IconDeviceFloppy />}
                  {labelSimpan}
                </Button>
              </div>
            </SheetFooter>
          </form>
        </Form>
      </SheetContent>
    </Sheet>
  )
}
