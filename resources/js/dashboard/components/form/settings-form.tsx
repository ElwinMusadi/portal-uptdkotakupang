import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { IconCircleCheck, IconDeviceFloppy } from "@tabler/icons-react"
import { useForm, type DefaultValues, type Resolver, type UseFormReturn } from "react-hook-form"
import { toast } from "sonner"
import type { z } from "zod"

import { JagaPerubahan } from "@/components/form/unsaved"
import { Button } from "@/components/ui/button"
import { Form } from "@/components/ui/form"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import type { NamaPengaturan, Pengaturan } from "@/lib/api"
import { waktuRelatif } from "@/lib/format"
import { usePengaturan, useSimpanPengaturan, useWaktuPengaturan } from "@/lib/queries"
import { cn } from "@/lib/utils"

export function BilahSimpan({
  berubah,
  menyimpan,
  onBatal,
  keterangan,
  labelSimpan = "Simpan perubahan",
}: {
  berubah: boolean
  menyimpan: boolean
  onBatal: () => void
  keterangan?: React.ReactNode
  labelSimpan?: string
}) {
  return (
    <div
      className={cn(
        "sticky bottom-0 z-20 -mx-4 mt-2 flex flex-wrap items-center gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80 lg:-mx-6 lg:px-6"
      )}
    >
      <p className="flex items-center gap-2 text-sm text-muted-foreground" aria-live="polite">
        {berubah ? (
          <>
            <span aria-hidden="true" className="size-2 rounded-full bg-warning" />
            Ada perubahan yang belum disimpan.
          </>
        ) : (
          <>
            <IconCircleCheck className="size-4 text-success" aria-hidden="true" />
            {keterangan ?? "Semua perubahan tersimpan."}
          </>
        )}
      </p>
      <div className="ml-auto flex gap-2">
        <Button type="button" variant="ghost" disabled={!berubah || menyimpan} onClick={onBatal}>
          Batalkan
        </Button>
        <Button type="submit" disabled={!berubah || menyimpan}>
          {menyimpan ? <Spinner /> : <IconDeviceFloppy />}
          {labelSimpan}
        </Button>
      </div>
    </div>
  )
}

export function KerangkaMemuat() {
  return (
    <div className="grid gap-4">
      <Skeleton className="h-40 w-full rounded-xl" />
      <Skeleton className="h-64 w-full rounded-xl" />
    </div>
  )
}

interface FormPengaturanProps<N extends NamaPengaturan> {
  nama: N
  schema: z.ZodType
  children: (form: UseFormReturn<Pengaturan[N]>) => React.ReactNode
  /** Ubah nilai sesaat sebelum disimpan (mis. cap waktu) */
  sebelumSimpan?: (nilai: Pengaturan[N]) => Pengaturan[N]
}

/** Halaman pengaturan: muat → sunting → simpan, dengan penjaga perubahan */
export function FormPengaturan<N extends NamaPengaturan>(props: FormPengaturanProps<N>) {
  const { data, error } = usePengaturan(props.nama)
  if (error) {
    return (
      <p className="rounded-lg border border-destructive/40 p-4 text-sm text-destructive">
        Data gagal dimuat: {(error as Error).message}
      </p>
    )
  }
  if (!data) return <KerangkaMemuat />
  return <IsiFormPengaturan {...props} data={data} />
}

function IsiFormPengaturan<N extends NamaPengaturan>({
  nama,
  schema,
  children,
  sebelumSimpan,
  data,
}: FormPengaturanProps<N> & { data: Pengaturan[N] }) {
  const simpan = useSimpanPengaturan(nama)
  const { data: waktu } = useWaktuPengaturan()
  const form = useForm<Pengaturan[N]>({
    defaultValues: structuredClone(data) as DefaultValues<Pengaturan[N]>,
    resolver: zodResolver(schema as never) as unknown as Resolver<Pengaturan[N]>,
    mode: "onTouched",
  })
  const berubah = form.formState.isDirty

  const onSubmit = form.handleSubmit(
    async () => {
      // getValues() menjaga kolom yang tidak tercantum di skema validasi
      let nilai = form.getValues()
      if (sebelumSimpan) nilai = sebelumSimpan(nilai)
      try {
        const tersimpan = await simpan.mutateAsync(nilai)
        form.reset(structuredClone(tersimpan) as DefaultValues<Pengaturan[N]>)
        toast.success("Perubahan disimpan")
      } catch {
        // galat sudah ditampilkan oleh useSimpanPengaturan
      }
    },
    () => toast.error("Periksa kembali isian yang ditandai merah.")
  )

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4 md:gap-6">
        {children(form)}
        <BilahSimpan
          berubah={berubah}
          menyimpan={simpan.isPending}
          onBatal={() => form.reset()}
          keterangan={
            waktu?.[nama] ? `Tersimpan · diperbarui ${waktuRelatif(waktu[nama])}.` : undefined
          }
        />
      </form>
      <JagaPerubahan aktif={berubah && !simpan.isPending} />
    </Form>
  )
}
