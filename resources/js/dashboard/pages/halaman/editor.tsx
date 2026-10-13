import { zodResolver } from "@hookform/resolvers/zod"
import { IconArrowLeft } from "@tabler/icons-react"
import { useForm, type Resolver } from "react-hook-form"
import { Link, useParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { EntriTidakDitemukan } from "@/components/data/not-found"
import { Bagian, Halaman } from "@/components/data/page"
import { FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada } from "@/components/form/item-fields"
import { FieldKaya } from "@/components/form/rich-text-field"
import { BilahSimpan, KerangkaMemuat } from "@/components/form/settings-form"
import { JagaPerubahan } from "@/components/form/unsaved"
import { Button } from "@/components/ui/button"
import { Form } from "@/components/ui/form"
import type { Halaman as HalamanStatis } from "@/lib/api"
import { useKoleksi, useUbahEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

// Batas yang sama dengan server (D-024)
const schema = z.object({
  judul: v.wajib("Judul").max(80, maks(80)),
  judulLanjutan: v.teks.max(80, maks(80)),
  pengantar: v.teks.max(120, maks(120)),
  slug: v.slug,
  isi: v.wajib("Isi halaman").max(100000, "Isi halaman terlalu panjang (maksimal 100000 karakter HTML)."),
})
type FormHalaman = z.infer<typeof schema>

export default function EditorHalaman() {
  const { id } = useParams()
  const { data, isLoading } = useKoleksi("halaman")
  if (isLoading || !data) {
    return (
      <Halaman>
        <KerangkaMemuat />
      </Halaman>
    )
  }
  // Setiap halaman adalah halaman portal: hanya disunting, tidak ditambah atau dihapus (D-024)
  const entri = data.find((h) => h.id === id)
  if (!entri) {
    return <EntriTidakDitemukan judul="Halaman tidak ditemukan" kembali="/halaman" labelKembali="Kembali ke daftar halaman" />
  }
  return <IsiEditor key={entri.id} entri={entri} />
}

function IsiEditor({ entri }: { entri: HalamanStatis }) {
  const ubah = useUbahEntri("halaman")
  const form = useForm<FormHalaman>({
    defaultValues: { judul: entri.judul, judulLanjutan: entri.judulLanjutan, pengantar: entri.pengantar, slug: entri.slug, isi: entri.isi },
    resolver: zodResolver(schema) as Resolver<FormHalaman>,
    mode: "onTouched",
  })
  const berubah = form.formState.isDirty
  const menyimpan = ubah.isPending
  const n = form.watch()

  const simpan = form.handleSubmit(
    async (nilai) => {
      try {
        await ubah.mutateAsync({ id: entri.id, patch: nilai })
        form.reset(nilai)
        toast.success("Halaman disimpan")
      } catch {
        // galat ditampilkan oleh mutasi
      }
    },
    () => toast.error("Periksa kembali isian yang ditandai merah.")
  )

  return (
    <Halaman>
      <div className="flex flex-wrap items-center gap-2">
        <Button variant="ghost" size="sm" asChild className="-ml-2">
          <Link to="/halaman">
            <IconArrowLeft />
            Semua halaman
          </Link>
        </Button>
      </div>
      <Form {...form}>
        <form noValidate onSubmit={simpan} className="flex flex-col gap-4">
          <Bagian judul="Judul & pengantar">
            <FieldJudul2Nada control={form.control} judul="judul" lanjutan="judulLanjutan" nilaiJudul={n.judul} nilaiLanjutan={n.judulLanjutan} />
            <div className="grid gap-5 sm:grid-cols-[minmax(0,1fr)_16rem]">
              <FieldTeks control={form.control} name="pengantar" label="Teks kecil di atas judul" placeholder="Mis. Kebijakan privasi · berlaku sejak 1 Januari 2027" />
              <FieldTeks control={form.control} name="slug" label="Nama berkas" deskripsi={`${n.slug}.html · tidak dapat diubah`} readOnly className="[&_input]:font-mono" />
            </div>
          </Bagian>
          <Bagian judul="Isi halaman">
            <FieldKaya control={form.control} name="isi" label="Isi" wajib deskripsi="Gunakan subjudul untuk setiap bagian agar mudah dipindai." />
          </Bagian>
          <BilahSimpan berubah={berubah} menyimpan={menyimpan} onBatal={() => form.reset()} />
        </form>
      </Form>
      <JagaPerubahan aktif={berubah && !menyimpan} />
    </Halaman>
  )
}
