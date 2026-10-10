import { zodResolver } from "@hookform/resolvers/zod"
import { IconArrowLeft, IconTrash } from "@tabler/icons-react"
import { useForm, type Resolver } from "react-hook-form"
import { Link, useNavigate, useParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { EntriTidakDitemukan } from "@/components/data/not-found"
import { Bagian, Halaman } from "@/components/data/page"
import { useHapus } from "@/components/data/row-actions"
import { FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada } from "@/components/form/item-fields"
import { FieldKaya } from "@/components/form/rich-text-field"
import { BilahSimpan, KerangkaMemuat } from "@/components/form/settings-form"
import { JagaPerubahan } from "@/components/form/unsaved"
import { Button } from "@/components/ui/button"
import { Form } from "@/components/ui/form"
import type { Halaman as HalamanStatis } from "@/lib/api"
import { useKoleksi, useTambahEntri, useUbahEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  judul: v.wajib("Judul"),
  judulLanjutan: v.teks,
  pengantar: v.teks,
  slug: v.slug,
  isi: v.wajib("Isi halaman"),
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
  const baru = !id || id === "baru"
  const entri = baru ? null : (data.find((h) => h.id === id) ?? null)
  if (!baru && !entri) {
    return <EntriTidakDitemukan judul="Halaman tidak ditemukan" kembali="/halaman" labelKembali="Kembali ke daftar halaman" />
  }
  return <IsiEditor key={entri?.id ?? "baru"} entri={entri} semua={data} />
}

function IsiEditor({ entri, semua }: { entri: HalamanStatis | null; semua: HalamanStatis[] }) {
  const navigate = useNavigate()
  const tambah = useTambahEntri("halaman")
  const ubah = useUbahEntri("halaman")
  const hapus = useHapus("halaman", "halaman")
  const form = useForm<FormHalaman>({
    defaultValues: entri
      ? { judul: entri.judul, judulLanjutan: entri.judulLanjutan, pengantar: entri.pengantar, slug: entri.slug, isi: entri.isi }
      : { judul: "", judulLanjutan: "", pengantar: "", slug: "", isi: "" },
    resolver: zodResolver(schema) as Resolver<FormHalaman>,
    mode: "onTouched",
  })
  const berubah = form.formState.isDirty
  const menyimpan = tambah.isPending || ubah.isPending
  const n = form.watch()

  const simpan = form.handleSubmit(
    async (nilai) => {
      if (semua.some((h) => h.slug === nilai.slug && h.id !== entri?.id)) {
        form.setError("slug", { message: "Slug sudah dipakai halaman lain." })
        return
      }
      try {
        if (entri) {
          await ubah.mutateAsync({ id: entri.id, patch: nilai })
          form.reset(nilai)
          toast.success("Halaman disimpan")
        } else {
          const baru = await tambah.mutateAsync(nilai)
          form.reset(nilai)
          toast.success("Halaman ditambahkan")
          setTimeout(() => navigate(`/halaman/${baru.id}`, { replace: true }), 0)
        }
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
        {entri && (
          <Button
            variant="ghost"
            size="sm"
            className="ml-auto text-destructive hover:text-destructive"
            onClick={async () => {
              if (await hapus([entri.id], entri.judul)) {
                form.reset(form.getValues())
                setTimeout(() => navigate("/halaman", { replace: true }), 0)
              }
            }}
          >
            <IconTrash />
            Hapus halaman
          </Button>
        )}
      </div>
      <Form {...form}>
        <form noValidate onSubmit={simpan} className="flex flex-col gap-4">
          <Bagian judul="Judul & pengantar">
            <FieldJudul2Nada control={form.control} judul="judul" lanjutan="judulLanjutan" nilaiJudul={n.judul} nilaiLanjutan={n.judulLanjutan} />
            <div className="grid gap-5 sm:grid-cols-[minmax(0,1fr)_16rem]">
              <FieldTeks control={form.control} name="pengantar" label="Teks kecil di atas judul" placeholder="Mis. Kebijakan privasi · berlaku sejak 1 Januari 2027" />
              <FieldTeks control={form.control} name="slug" label="Nama berkas" deskripsi={`${n.slug || "nama-halaman"}.html`} className="[&_input]:font-mono" />
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
