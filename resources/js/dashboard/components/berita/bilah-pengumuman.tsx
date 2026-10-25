import * as React from "react"
import {
  IconAlertTriangle,
  IconArrowRight,
  IconCheck,
  IconExternalLink,
  IconSelector,
  IconSpeakerphone,
} from "@tabler/icons-react"
import type { UseFormReturn } from "react-hook-form"
import { z } from "zod"

import { Bagian, Baris } from "@/components/data/page"
import { FieldSakelar, FieldTeks } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command"
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form"
import { Label } from "@/components/ui/label"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import type { Berita, BilahPengumuman, SumberBilahPengumuman } from "@/lib/api"
import { tanggal } from "@/lib/format"
import { KATEGORI_BERITA } from "@/lib/meta"
import { useKoleksi } from "@/lib/queries"
import { cn } from "@/lib/utils"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

// Batas yang sama dengan server (UpdateAnnouncementBarRequest)
const schema = z
  .object({
    aktif: z.boolean(),
    beranda: z.boolean(),
    sumber: z.enum(["teks", "tulisan"]),
    tulisan: z.string().nullable(),
    teks: v.teks.max(200, maks(200)),
    tautanLabel: v.teks.max(30, maks(30)),
    tautanUrl: v.tautan.pipe(z.string().max(300, maks(300))),
  })
  .refine((b) => !b.aktif || b.sumber !== "teks" || b.teks.trim() !== "", {
    path: ["teks"],
    message: "Teks pengumuman wajib diisi selama bilah pengumuman ditampilkan.",
  })
  .refine((b) => !b.aktif || b.sumber !== "tulisan" || !!b.tulisan, {
    path: ["tulisan"],
    message: "Pilih tulisan untuk bilah pengumuman.",
  })

const SUMBER: { nilai: SumberBilahPengumuman; label: string; keterangan: string }[] = [
  { nilai: "teks", label: "Tulis sendiri", keterangan: "Teks singkat dan tujuan tautan yang Anda tulis di sini." },
  { nilai: "tulisan", label: "Ambil dari tulisan", keterangan: "Judul dan kategori satu tulisan terbit, menaut ke halamannya." },
]

/** Tulisan terbit, tanggal tayang terbaru lebih dulu seperti di portal */
function tulisanTerbit(semua: Berita[]) {
  return semua
    .filter((b) => b.status === "terbit")
    .sort((a, b) => b.tanggal.localeCompare(a.tanggal) || (b.terbitPada ?? "").localeCompare(a.terbitPada ?? ""))
}

/** Contoh bilah seperti di halaman Informasi portal */
function ContohBilah({ bilah, tulisan }: { bilah: Partial<BilahPengumuman>; tulisan?: Berita }) {
  const dariTulisan = bilah.sumber === "tulisan"
  const teks = dariTulisan ? tulisan?.judul : bilah.teks?.trim()
  if (!bilah.aktif || !teks) {
    return <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">Bilah pengumuman tidak tampil di portal.</p>
  }
  const bertautan = dariTulisan || !!bilah.tautanUrl?.trim()
  return (
    <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-card px-5 py-4" aria-label="Contoh bilah pengumuman di portal">
      <span className="inline-flex items-center gap-1.5 rounded-full bg-brand/10 px-3 py-1 text-sm text-brand">
        <IconSpeakerphone className="size-4" aria-hidden="true" />
        {dariTulisan && tulisan ? KATEGORI_BERITA[tulisan.kategori] : "Pengumuman"}
      </span>
      <span className="min-w-56 flex-1 font-medium">{teks}</span>
      {bertautan && (
        <span className="inline-flex items-center gap-1 text-sm text-brand">
          {bilah.tautanLabel?.trim() || "Baca"}
          <IconArrowRight className="size-4" aria-hidden="true" />
        </span>
      )}
    </div>
  )
}

function PilihSumber({ form }: { form: UseFormReturn<BilahPengumuman> }) {
  const id = React.useId()
  return (
    <FormField
      control={form.control}
      name="sumber"
      render={({ field }) => (
        <FormItem>
          <FormLabel>Isi bilah</FormLabel>
          <FormControl>
            <RadioGroup value={field.value} onValueChange={field.onChange} className="grid gap-2 sm:grid-cols-2">
              {SUMBER.map((s) => (
                <Label
                  key={s.nilai}
                  htmlFor={`${id}-${s.nilai}`}
                  className="items-start gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                >
                  <RadioGroupItem value={s.nilai} id={`${id}-${s.nilai}`} className="mt-0.5" />
                  <span className="grid gap-0.5">
                    <span className="font-medium">{s.label}</span>
                    <span className="text-xs text-muted-foreground">{s.keterangan}</span>
                  </span>
                </Label>
              ))}
            </RadioGroup>
          </FormControl>
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

/** Pemilih satu tulisan terbit dari semua kategori Berita & Pengumuman */
function PilihTulisan({ form, terbit, terpilih }: { form: UseFormReturn<BilahPengumuman>; terbit: Berita[]; terpilih?: Berita }) {
  const [buka, setBuka] = React.useState(false)
  return (
    <FormField
      control={form.control}
      name="tulisan"
      render={({ field }) => (
        <FormItem>
          <FormLabel>
            Tulisan
            <span aria-hidden="true" className="text-destructive">
              *
            </span>
          </FormLabel>
          <Popover open={buka} onOpenChange={setBuka}>
            <PopoverTrigger asChild>
              <FormControl>
                <Button
                  variant="outline"
                  role="combobox"
                  aria-expanded={buka}
                  ref={field.ref}
                  onBlur={field.onBlur}
                  className={cn("w-full justify-between font-normal", !terpilih && "text-muted-foreground")}
                >
                  <span className="truncate">{terpilih ? terpilih.judul : "Pilih tulisan terbit…"}</span>
                  <IconSelector className="opacity-50" />
                </Button>
              </FormControl>
            </PopoverTrigger>
            <PopoverContent className="w-(--radix-popover-trigger-width) min-w-72 p-0" align="start">
              <Command>
                <CommandInput placeholder="Cari judul atau kategori…" />
                <CommandList>
                  <CommandEmpty>{terbit.length ? "Tidak ada tulisan yang cocok." : "Belum ada tulisan yang terbit."}</CommandEmpty>
                  {terbit.length > 0 && (
                    <CommandGroup heading="Tulisan terbit">
                      {terbit.map((b) => (
                        <CommandItem
                          key={b.id}
                          value={`${b.judul} ${KATEGORI_BERITA[b.kategori]} ${b.id}`}
                          onSelect={() => {
                            field.onChange(b.id)
                            setBuka(false)
                          }}
                        >
                          <span className="grid min-w-0 flex-1 gap-0.5">
                            <span className="truncate">{b.judul}</span>
                            <span className="text-xs text-muted-foreground">
                              {KATEGORI_BERITA[b.kategori]} · {tanggal(b.tanggal)}
                            </span>
                          </span>
                          <IconCheck className={cn(field.value === b.id ? "opacity-100" : "opacity-0")} />
                        </CommandItem>
                      ))}
                    </CommandGroup>
                  )}
                </CommandList>
              </Command>
            </PopoverContent>
          </Popover>
          <FormDescription>
            Semua kategori. Bilah memakai judul dan kategori tulisan ini, dan tautannya membuka halaman tulisan di portal.
          </FormDescription>
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

function IsiBilah({ form }: { form: UseFormReturn<BilahPengumuman> }) {
  const { data: semua } = useKoleksi("berita")
  const bilah = form.watch()
  const terbit = React.useMemo(() => tulisanTerbit(semua ?? []), [semua])
  const terpilih = terbit.find((b) => b.id === bilah.tulisan)
  // Tulisan tersimpan yang kini ditarik atau dihapus: bilah tidak tampil di portal (D-083)
  const hilang = bilah.sumber === "tulisan" && !!bilah.tulisan && !!semua && !terpilih

  return (
    <>
      <FieldSakelar control={form.control} name="aktif" label="Tampilkan bilah pengumuman" deskripsi="Di atas daftar berita halaman Informasi." />
      <FieldSakelar
        control={form.control}
        name="beranda"
        label="Tampilkan juga di Beranda"
        deskripsi="Di bawah pembuka Beranda, sebelum papan layanan hari ini, selama bilah pengumuman ditampilkan."
      />
      <PilihSumber form={form} />
      {bilah.sumber === "tulisan" ? (
        <>
          {hilang && (
            <Alert variant="destructive">
              <IconAlertTriangle />
              <AlertTitle>Tulisan tidak lagi terbit</AlertTitle>
              <AlertDescription>
                Tulisan yang dipilih sudah ditarik dari portal atau dihapus, jadi bilah tidak tampil. Pilih tulisan lain, atau tulis teks sendiri.
              </AlertDescription>
            </Alert>
          )}
          <PilihTulisan form={form} terbit={terbit} terpilih={terpilih} />
          <FieldTeks control={form.control} name="tautanLabel" label="Label tautan" placeholder="Baca" className="sm:max-w-1/2" />
        </>
      ) : (
        <>
          <FieldTeks control={form.control} name="teks" label="Teks pengumuman" deskripsi="Satu kalimat singkat, maksimal 200 karakter." />
          <Baris>
            <FieldTeks control={form.control} name="tautanLabel" label="Label tautan" placeholder="Baca" />
            <FieldTeks
              control={form.control}
              name="tautanUrl"
              label="Tujuan tautan"
              placeholder="https://uptdpenda-kupang.web.id/berita/…"
              deskripsi="Salin alamat berita dari portal, atau tulis nama halaman portal. Kosongkan bila bilah tidak perlu tautan."
            />
          </Baris>
        </>
      )}
      <div className="grid gap-2">
        <p className="text-sm font-medium">Contoh di portal</p>
        <ContohBilah bilah={bilah} tulisan={terpilih} />
      </div>
    </>
  )
}

/**
 * Bilah pengumuman di atas daftar berita halaman Informasi (D-082) dan, bila
 * dipilih, di bawah pembuka Beranda (D-083), disimpan sebagai pengaturan
 * `berita` dan langsung tampil di portal setelah disimpan. Teksnya ditulis
 * sendiri atau diambil dari satu tulisan terbit (D-083).
 */
export function FormBilahPengumuman() {
  return (
    <FormPengaturan nama="berita" schema={schema}>
      {(form) => (
        <Bagian
          judul="Bilah pengumuman"
          deskripsi="Pita pengumuman singkat di halaman Informasi portal, dan bila dipilih juga di Beranda. Langsung tampil setelah disimpan, tanpa alur publikasi."
          aksi={
            <div className="flex flex-wrap gap-2">
              <Button variant="outline" size="sm" asChild>
                <a href="/informasi" target="_blank" rel="noopener">
                  <IconExternalLink />
                  Lihat Informasi
                </a>
              </Button>
              <Button variant="outline" size="sm" asChild>
                <a href="/" target="_blank" rel="noopener">
                  <IconExternalLink />
                  Lihat Beranda
                </a>
              </Button>
            </div>
          }
        >
          <IsiBilah form={form} />
        </Bagian>
      )}
    </FormPengaturan>
  )
}
