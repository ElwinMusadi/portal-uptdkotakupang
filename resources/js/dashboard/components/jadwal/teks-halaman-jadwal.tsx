import { IconExternalLink, IconRestore } from "@tabler/icons-react"
import type { UseFormReturn } from "react-hook-form"
import { z } from "zod"

import { Bagian } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada } from "@/components/form/item-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Button } from "@/components/ui/button"
import type { TeksJadwal } from "@/lib/api"
import { TEKS_JADWAL_BAWAAN } from "@/lib/teks-jadwal"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

const judulBagian = {
  eyebrow: v.teks.max(120, maks(120)),
  judul: v.wajib("Judul").max(80, maks(80)),
  lanjutan: v.teks.max(80, maks(80)),
}

// Batas yang sama dengan server (UpdateSchedulePageRequest)
const schema = z.object({
  pembuka: z.object({ ...judulBagian, deskripsi: v.teks.max(300, maks(300)) }),
  jam: z.object(judulBagian),
  luarKantor: z.object({ ...judulBagian, deskripsi: v.teks.max(300, maks(300)), catatan: v.teks.max(160, maks(160)) }),
})

/** Teks kecil di atas judul dan judul dua nada satu bagian halaman Jadwal */
function JudulBagian({ form, bagian }: { form: UseFormReturn<TeksJadwal>; bagian: keyof TeksJadwal }) {
  const nilai = form.watch(bagian)
  return (
    <>
      <FieldTeks
        control={form.control}
        name={`${bagian}.eyebrow`}
        label="Teks kecil di atas judul"
        deskripsi="Huruf kapital kecil di atas judul. Kosongkan bila tidak perlu."
      />
      <FieldJudul2Nada
        control={form.control}
        judul={`${bagian}.judul`}
        lanjutan={`${bagian}.lanjutan`}
        nilaiJudul={nilai?.judul}
        nilaiLanjutan={nilai?.lanjutan}
      />
    </>
  )
}

/**
 * Teks pembuka halaman Jadwal portal (D-080): judul halaman, judul bagian jam
 * loket, dan pengantar layanan di luar kantor. Disimpan sebagai pengaturan
 * `keliling` dan langsung tampil di portal.
 */
export function TeksHalamanJadwal() {
  return (
    <FormPengaturan nama="keliling" schema={schema}>
      {(form) => {
        const kembalikan = () => {
          for (const bagian of ["pembuka", "jam", "luarKantor"] as const) {
            form.setValue(bagian, structuredClone(TEKS_JADWAL_BAWAAN[bagian]), { shouldDirty: true, shouldValidate: true })
          }
        }
        return (
          <>
            <div className="flex flex-wrap items-center gap-2">
              <p className="mr-auto max-w-2xl text-sm text-muted-foreground">
                Teks pembuka di halaman Jadwal portal. Jadwal, jam loket, dan status loket di halaman itu tetap diisi dari
                data masing-masing. Isian yang dikosongkan tidak tampil.
              </p>
              <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" size="sm" onClick={kembalikan}>
                  <IconRestore />
                  Kembalikan teks bawaan
                </Button>
                <Button variant="outline" size="sm" asChild>
                  <a href="/jadwal" target="_blank" rel="noopener">
                    <IconExternalLink />
                    Lihat di portal
                  </a>
                </Button>
              </div>
            </div>
            <Bagian judul="Pembuka halaman" deskripsi="Judul besar dan paragraf di bagian atas halaman Jadwal.">
              <JudulBagian form={form} bagian="pembuka" />
              <FieldArea control={form.control} name="pembuka.deskripsi" label="Paragraf" rows={3} maks={300} />
            </Bagian>
            <Bagian judul="Jam loket kantor" deskripsi="Judul di samping tabel jam loket. Catatan di bawahnya diatur di Jam Pelayanan.">
              <JudulBagian form={form} bagian="jam" />
            </Bagian>
            <Bagian
              judul="Layanan di luar kantor"
              deskripsi="Pengantar di atas jadwal Samsat Keliling, gerai tetap, dan loket sementara."
            >
              <JudulBagian form={form} bagian="luarKantor" />
              <FieldArea control={form.control} name="luarKantor.deskripsi" label="Paragraf" rows={4} maks={300} />
              <FieldTeks
                control={form.control}
                name="luarKantor.catatan"
                label="Catatan berikon"
                deskripsi="Satu baris dengan ikon info di bawah paragraf, mis. layanan yang tidak tersedia."
              />
            </Bagian>
          </>
        )
      }}
    </FormPengaturan>
  )
}
