import { IconArrowRight, IconExternalLink, IconSpeakerphone } from "@tabler/icons-react"
import { z } from "zod"

import { Bagian, Baris } from "@/components/data/page"
import { FieldSakelar, FieldTeks } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Button } from "@/components/ui/button"
import type { BilahPengumuman } from "@/lib/api"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

// Batas yang sama dengan server (UpdateAnnouncementBarRequest)
const schema = z
  .object({
    aktif: z.boolean(),
    teks: v.teks.max(200, maks(200)),
    tautanLabel: v.teks.max(30, maks(30)),
    tautanUrl: v.tautan.pipe(z.string().max(300, maks(300))),
  })
  .refine((b) => !b.aktif || b.teks.trim() !== "", {
    path: ["teks"],
    message: "Teks pengumuman wajib diisi selama bilah pengumuman ditampilkan.",
  })

/** Contoh bilah seperti di halaman Informasi portal */
function ContohBilah({ bilah }: { bilah: Partial<BilahPengumuman> }) {
  if (!bilah.aktif || !bilah.teks?.trim()) {
    return <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">Bilah pengumuman tidak tampil di portal.</p>
  }
  return (
    <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-card px-5 py-4" aria-label="Contoh bilah pengumuman di portal">
      <span className="inline-flex items-center gap-1.5 rounded-full bg-brand/10 px-3 py-1 text-sm text-brand">
        <IconSpeakerphone className="size-4" aria-hidden="true" />
        Pengumuman
      </span>
      <span className="min-w-56 flex-1 font-medium">{bilah.teks}</span>
      {bilah.tautanUrl?.trim() && (
        <span className="inline-flex items-center gap-1 text-sm text-brand">
          {bilah.tautanLabel?.trim() || "Baca"}
          <IconArrowRight className="size-4" aria-hidden="true" />
        </span>
      )}
    </div>
  )
}

/**
 * Bilah pengumuman di atas daftar berita halaman Informasi (D-082), disimpan
 * sebagai pengaturan `berita` dan langsung tampil di portal setelah disimpan.
 */
export function FormBilahPengumuman() {
  return (
    <FormPengaturan nama="berita" schema={schema}>
      {(form) => {
        const bilah = form.watch()
        return (
          <Bagian
            judul="Bilah pengumuman"
            deskripsi="Pita pengumuman singkat di atas daftar berita halaman Informasi portal. Langsung tampil setelah disimpan, tanpa alur publikasi."
            aksi={
              <Button variant="outline" size="sm" asChild>
                <a href="/informasi" target="_blank" rel="noopener">
                  <IconExternalLink />
                  Lihat di portal
                </a>
              </Button>
            }
          >
            <FieldSakelar control={form.control} name="aktif" label="Tampilkan bilah pengumuman" />
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
            <div className="grid gap-2">
              <p className="text-sm font-medium">Contoh di portal</p>
              <ContohBilah bilah={bilah} />
            </div>
          </Bagian>
        )
      }}
    </FormPengaturan>
  )
}
