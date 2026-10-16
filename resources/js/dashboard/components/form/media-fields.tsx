import * as React from "react"
import {
  IconFileDescription,
  IconLink,
  IconPhoto,
  IconTrash,
  IconUpload,
} from "@tabler/icons-react"
import type { Control, FieldPath, FieldValues } from "react-hook-form"
import { toast } from "sonner"

import type { PropsDasar } from "@/components/form/fields"
import { Button } from "@/components/ui/button"
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { api, unggahanDiServer, type Berkas, type TujuanUnggah } from "@/lib/api"
import { ukuranBerkas } from "@/lib/format"
import { kompresGambar, MAKS_BERKAS, namaBerkasGambar, TIPE_GAMBAR } from "@/lib/gambar"
import { cn } from "@/lib/utils"

/** Unggah foto: dikompres di peramban lalu dikirim ke penyimpanan tujuannya */
export async function unggahGambar(berkas: File, tujuan: TujuanUnggah): Promise<string> {
  const blob = await kompresGambar(berkas)
  const hasil = await api.upload(blob, namaBerkasGambar(berkas.name, blob), tujuan)
  return hasil.url
}

/** Bingkai pengganti saat foto belum tersedia */
export function PlaceholderFoto({ className, label }: { className?: string; label?: string }) {
  return (
    <div
      className={cn(
        "flex size-full flex-col items-center justify-center gap-1 bg-[repeating-linear-gradient(135deg,var(--muted)_0_10px,transparent_10px_20px)] text-muted-foreground",
        className
      )}
    >
      <IconPhoto className="size-6" aria-hidden="true" />
      {label && <span className="px-2 text-center text-xs">{label}</span>}
    </div>
  )
}

/**
 * Logo kecil di baris daftar, mis. logo mitra (D-037): pratinjau bundar seperti
 * lencana di portal, dengan inisial bila logo belum diunggah.
 */
export function IsianLogo<T extends FieldValues>({
  control,
  name,
  label,
  labelAksi,
  inisial,
  tujuan,
}: {
  control: Control<T>
  name: FieldPath<T>
  label: string
  /** Nama logo di label tombol untuk pembaca layar, mis. "logo mitra 2" */
  labelAksi: string
  inisial?: string
  tujuan: TujuanUnggah
}) {
  const inputRef = React.useRef<HTMLInputElement>(null)
  const [proses, setProses] = React.useState(false)

  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const src = (field.value as string | null) || null
        const pilih = async (berkas: File | undefined) => {
          if (!berkas) return
          setProses(true)
          try {
            field.onChange(await unggahGambar(berkas, tujuan))
          } catch (e) {
            toast.error("Logo gagal diunggah", { description: (e as Error).message })
          } finally {
            setProses(false)
            if (inputRef.current) inputRef.current.value = ""
          }
        }
        return (
          <FormItem className="gap-1.5">
            <FormLabel className="text-xs text-muted-foreground">{label}</FormLabel>
            <div className="flex items-center gap-1">
              <span
                className={cn(
                  "relative flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full border text-[0.6875rem] font-semibold text-muted-foreground",
                  src ? "bg-white p-1" : "bg-muted"
                )}
              >
                {src ? <img src={src} alt="" className="size-full object-contain" /> : inisial || <IconPhoto className="size-4" aria-hidden="true" />}
                {proses && (
                  <span className="absolute inset-0 flex items-center justify-center bg-background/70">
                    <Spinner className="size-4" />
                  </span>
                )}
              </span>
              <FormControl>
                <input
                  ref={inputRef}
                  type="file"
                  accept={TIPE_GAMBAR.join(",")}
                  className="sr-only"
                  tabIndex={-1}
                  onChange={(e) => void pilih(e.target.files?.[0])}
                />
              </FormControl>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                disabled={proses}
                aria-label={`${src ? "Ganti" : "Unggah"} ${labelAksi}`}
                title={src ? "Ganti logo" : "Unggah logo (JPG, PNG, atau WebP; PNG transparan paling baik)"}
                onClick={() => inputRef.current?.click()}
              >
                <IconUpload />
              </Button>
              {src && (
                <Button type="button" variant="ghost" size="icon" aria-label={`Hapus ${labelAksi}`} title="Hapus logo" onClick={() => field.onChange(null)}>
                  <IconTrash />
                </Button>
              )}
            </div>
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

export function FieldGambar<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  rasio = "aspect-[16/9]",
  logo = false,
  tujuan,
}: PropsDasar<T> & {
  rasio?: string
  /** Tampilkan utuh di atas latar putih, tidak dipotong (logo) */
  logo?: boolean
  tujuan: TujuanUnggah
}) {
  const inputRef = React.useRef<HTMLInputElement>(null)
  const [proses, setProses] = React.useState(false)
  const [modeUrl, setModeUrl] = React.useState(false)

  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const src = (field.value as string | null) || null
        const pilih = async (berkas: File | undefined) => {
          if (!berkas) return
          setProses(true)
          try {
            field.onChange(await unggahGambar(berkas, tujuan))
          } catch (e) {
            toast.error("Gambar gagal diunggah", { description: (e as Error).message })
          } finally {
            setProses(false)
            if (inputRef.current) inputRef.current.value = ""
          }
        }
        return (
          <FormItem className={className}>
            <FormLabel>{label}</FormLabel>
            <div
              className={cn("relative overflow-hidden rounded-lg border bg-muted/40", rasio)}
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                e.preventDefault()
                void pilih(e.dataTransfer.files[0])
              }}
            >
              {src ? (
                <img src={src} alt="" className={cn("size-full", logo ? "bg-white object-contain p-4" : "object-cover")} />
              ) : (
                <PlaceholderFoto
                  label={logo ? "Belum ada logo. Seret logo ke sini atau pilih berkas." : "Belum ada foto. Seret foto ke sini atau pilih berkas."}
                />
              )}
              {proses && (
                <div className="absolute inset-0 flex items-center justify-center bg-background/70">
                  <Spinner />
                  <span className="sr-only">Mengunggah…</span>
                </div>
              )}
            </div>
            <div className="flex flex-wrap gap-2">
              <FormControl>
                <input
                  ref={inputRef}
                  type="file"
                  accept={TIPE_GAMBAR.join(",")}
                  className="sr-only"
                  tabIndex={-1}
                  onChange={(e) => void pilih(e.target.files?.[0])}
                />
              </FormControl>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={proses}
                onClick={() => inputRef.current?.click()}
              >
                <IconUpload />
                {logo ? (src ? "Ganti logo" : "Pilih logo") : src ? "Ganti foto" : "Pilih foto"}
              </Button>
              {/* Server hanya menerima foto yang diunggah ke PINTU */}
              {!unggahanDiServer(tujuan) && (
                <Button type="button" variant="ghost" size="sm" onClick={() => setModeUrl((m) => !m)}>
                  <IconLink />
                  Pakai URL
                </Button>
              )}
              {src && (
                <Button type="button" variant="ghost" size="sm" onClick={() => field.onChange(null)}>
                  <IconTrash />
                  Hapus
                </Button>
              )}
            </div>
            {modeUrl && (
              <Input
                type="url"
                placeholder="https://…/foto.jpg"
                aria-label="URL foto"
                defaultValue={src?.startsWith("data:") ? "" : (src ?? "")}
                onBlur={(e) => field.onChange(e.target.value.trim() || null)}
              />
            )}
            <FormDescription>
              {deskripsi ?? "JPG, PNG, atau WebP. Foto diperkecil otomatis menjadi maks. 1600 px."}
            </FormDescription>
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

export function FieldBerkas<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  terima = ".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png",
  petunjuk = "PDF, Word, Excel, atau gambar · maks. 10 MB",
  tujuan,
}: PropsDasar<T> & { terima?: string; petunjuk?: string; tujuan: TujuanUnggah }) {
  const inputRef = React.useRef<HTMLInputElement>(null)
  const [proses, setProses] = React.useState(false)

  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const berkas = field.value as Berkas | null
        const pilih = async (b: File | undefined) => {
          if (!b) return
          if (b.size > MAKS_BERKAS) {
            toast.error("Berkas terlalu besar", { description: "Ukuran maksimal 10 MB." })
            return
          }
          setProses(true)
          try {
            field.onChange(await api.upload(b, b.name, tujuan))
          } catch (e) {
            toast.error("Berkas gagal diunggah", { description: (e as Error).message })
          } finally {
            setProses(false)
            if (inputRef.current) inputRef.current.value = ""
          }
        }
        return (
          <FormItem className={className}>
            <FormLabel>{label}</FormLabel>
            {berkas ? (
              <div className="flex items-center gap-3 rounded-lg border p-3">
                <IconFileDescription className="size-8 shrink-0 text-muted-foreground" aria-hidden="true" />
                <div className="grid min-w-0 flex-1 text-sm">
                  <a
                    href={berkas.url}
                    download={berkas.nama}
                    className="truncate font-medium underline-offset-4 hover:underline"
                  >
                    {berkas.nama}
                  </a>
                  <span className="text-xs text-muted-foreground">{ukuranBerkas(berkas.ukuran)}</span>
                </div>
                <Button type="button" variant="ghost" size="sm" onClick={() => field.onChange(null)}>
                  <IconTrash />
                  Hapus
                </Button>
              </div>
            ) : (
              <button
                type="button"
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => e.preventDefault()}
                onDrop={(e) => {
                  e.preventDefault()
                  void pilih(e.dataTransfer.files[0])
                }}
                className="flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground transition-colors hover:bg-muted/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
              >
                {proses ? <Spinner /> : <IconUpload className="size-5" aria-hidden="true" />}
                <span className="font-medium text-foreground">Pilih berkas atau seret ke sini</span>
                <span>{petunjuk}</span>
              </button>
            )}
            <FormControl>
              <input
                ref={inputRef}
                type="file"
                accept={terima}
                className="sr-only"
                tabIndex={-1}
                onChange={(e) => void pilih(e.target.files?.[0])}
              />
            </FormControl>
            {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}
