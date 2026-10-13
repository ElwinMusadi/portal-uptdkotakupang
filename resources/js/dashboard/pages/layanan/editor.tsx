import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { IconArrowLeft, IconCheckbox, IconTrash } from "@tabler/icons-react"
import { useForm, type Resolver } from "react-hook-form"
import { Link, useNavigate, useParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { PanelAlur, RiwayatAlur, useAksiAlur } from "@/components/alur/alur"
import { EntriTidakDitemukan } from "@/components/data/not-found"
import { Bagian, Baris, Halaman } from "@/components/data/page"
import { useHapus } from "@/components/data/row-actions"
import { FieldArea, FieldPilih, FieldSakelar, FieldTeks } from "@/components/form/fields"
import { DaftarObjek, FieldLabel } from "@/components/form/list-fields"
import { BilahSimpan, KerangkaMemuat } from "@/components/form/settings-form"
import { JagaPerubahan } from "@/components/form/unsaved"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Form, FormControl, FormField, FormItem, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import type { Layanan } from "@/lib/api"
import { alurBaru } from "@/lib/alur"
import { slugify } from "@/lib/format"
import { validasiFormulir } from "@/lib/formulir"
import { newId } from "@/lib/id"
import { PEMERIKSA, UNIT_PENYEDIA } from "@/lib/meta"
import { useKoleksi, usePengaturan, useTambahEntri, useUbahEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  nama: v.wajib("Nama layanan"),
  slug: v.slug,
  ringkasan: v.wajib("Ringkasan").max(200, "Maksimal 200 karakter."),
  loket: v.wajib("Loket"),
  jenis: z.enum(["layanan", "tambahan"]),
  cekFisik: z.boolean(),
  nonTunai: z.boolean(),
  diwakilkan: z.boolean(),
  unggulan: z.boolean(),
  label: z.array(z.string()),
  persyaratan: z
    .array(z.object({ id: z.string(), dokumen: v.wajib("Nama berkas"), jumlah: v.teks }))
    .min(1, "Tambahkan minimal satu berkas persyaratan."),
  catatan: v.teks,
  penyedia: v.wajib("Unit penyedia"),
  pemeriksa: v.teks,
})
type FormLayanan = z.infer<typeof schema>

function kosong(): FormLayanan {
  return {
    nama: "",
    slug: "",
    ringkasan: "",
    loket: "Loket 1",
    jenis: "layanan",
    cekFisik: false,
    nonTunai: true,
    diwakilkan: true,
    unggulan: false,
    label: [],
    persyaratan: [{ id: newId(), dokumen: "", jumlah: "2 lembar" }],
    catatan: "",
    penyedia: "Seksi Pendataan & Penetapan",
    pemeriksa: PEMERIKSA[1],
  }
}

function dariEntri(l: Layanan): FormLayanan {
  return {
    nama: l.nama,
    slug: l.slug,
    ringkasan: l.ringkasan,
    loket: l.loket,
    jenis: l.jenis,
    cekFisik: l.cekFisik,
    nonTunai: l.nonTunai,
    diwakilkan: l.diwakilkan,
    unggulan: l.unggulan,
    label: l.label,
    persyaratan: l.persyaratan,
    catatan: l.catatan,
    penyedia: l.penyedia,
    pemeriksa: l.pemeriksa,
  }
}

export default function EditorLayanan() {
  const { id } = useParams()
  const { data, isLoading } = useKoleksi("layanan")
  if (isLoading || !data) {
    return (
      <Halaman>
        <KerangkaMemuat />
      </Halaman>
    )
  }
  const entri = id ? (data.find((l) => l.id === id) ?? null) : null
  if (id && !entri) {
    return <EntriTidakDitemukan judul="Layanan tidak ditemukan" kembali="/layanan" labelKembali="Kembali ke daftar layanan" />
  }
  return <IsiEditor key={entri?.id ?? "baru"} entri={entri} semua={data} />
}

function IsiEditor({ entri, semua }: { entri: Layanan | null; semua: Layanan[] }) {
  const navigate = useNavigate()
  const tambah = useTambahEntri("layanan")
  const ubah = useUbahEntri("layanan")
  const { data: akun } = usePengaturan("akun")
  const hapus = useHapus("layanan", "layanan")
  const { jalankan, dialog, memproses } = useAksiAlur("layanan")

  const form = useForm<FormLayanan>({
    defaultValues: entri ? dariEntri(entri) : kosong(),
    resolver: zodResolver(schema) as Resolver<FormLayanan>,
    mode: "onTouched",
  })
  const berubah = form.formState.isDirty
  const menyimpan = tambah.isPending || ubah.isPending

  const nama = form.watch("nama")
  React.useEffect(() => {
    if (!entri) form.setValue("slug", slugify(nama), { shouldDirty: true })
  }, [nama, entri, form])

  const pratinjau = form.watch()

  const simpan = async (): Promise<boolean> => {
    if (!(await validasiFormulir(form))) {
      toast.error("Periksa kembali isian yang ditandai merah.")
      return false
    }
    const nilai = form.getValues()
    if (semua.some((l) => l.slug === nilai.slug && l.id !== entri?.id)) {
      form.setError("slug", { message: "Slug sudah dipakai layanan lain." })
      return false
    }
    try {
      if (entri) {
        await ubah.mutateAsync({ id: entri.id, patch: nilai })
        toast.success("Perubahan layanan disimpan")
      } else {
        const baru = await tambah.mutateAsync({
          ...alurBaru(akun?.nama ?? "Pengelola PINTU", nilai.penyedia, nilai.pemeriksa),
          ...nilai,
        })
        toast.success("Layanan ditambahkan sebagai draf")
        form.reset(nilai)
        setTimeout(() => navigate(`/layanan/${baru.id}`, { replace: true }), 0)
        return true
      }
      form.reset(nilai)
      return true
    } catch {
      return false
    }
  }

  return (
    <Halaman>
      <div className="flex flex-wrap items-center gap-2">
        <Button variant="ghost" size="sm" asChild className="-ml-2">
          <Link to="/layanan">
            <IconArrowLeft />
            Semua layanan
          </Link>
        </Button>
        {entri && (
          <Button
            variant="ghost"
            size="sm"
            className="ml-auto text-destructive hover:text-destructive"
            onClick={async () => {
              if (await hapus([entri.id], entri.nama)) {
                form.reset(form.getValues())
                setTimeout(() => navigate("/layanan", { replace: true }), 0)
              }
            }}
          >
            <IconTrash />
            Hapus layanan
          </Button>
        )}
      </div>
      <Form {...form}>
        <form
          noValidate
          className="flex flex-col gap-4"
          onSubmit={(e) => {
            e.preventDefault()
            void simpan()
          }}
        >
          <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
            <div className="grid min-w-0 gap-4">
              <Bagian judul="Identitas layanan">
                <FieldTeks control={form.control} name="nama" label="Nama layanan" wajib placeholder="Mis. Pajak tahunan" />
                <FieldArea control={form.control} name="ringkasan" label="Ringkasan" wajib rows={2} maks={200} />
                <Baris>
                  <FieldTeks control={form.control} name="loket" label="Loket" wajib placeholder="Mis. Loket 1" />
                  <FieldPilih
                    control={form.control}
                    name="jenis"
                    label="Jenis"
                    opsi={[
                      { nilai: "layanan", label: "Layanan" },
                      { nilai: "tambahan", label: "Lampiran tambahan (mis. badan hukum)" },
                    ]}
                  />
                </Baris>
                <FieldTeks control={form.control} name="slug" label="Slug" deskripsi="Penanda bagian halaman, mis. layanan.html#pajak-tahunan." className="[&_input]:font-mono" />
                <FieldLabel control={form.control} name="label" label="Label tambahan" deskripsi="Mis. Meterai Rp10.000, Laporan polisi asli." />
              </Bagian>

              <Bagian judul="Kelengkapan dokumen" deskripsi="Urutkan sesuai papan persyaratan di loket. Isi jumlah lembar agar warga tidak bolak-balik.">
                <DaftarObjek
                  control={form.control}
                  name="persyaratan"
                  label="Daftar berkas"
                  labelTambah="Tambah berkas"
                  labelItem={(i) => `berkas ${i + 1}`}
                  itemBaru={() => ({ id: newId(), dokumen: "", jumlah: "2 lembar" })}
                  render={(i) => (
                    <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_9rem]">
                      <FormField
                        control={form.control}
                        name={`persyaratan.${i}.dokumen`}
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Input {...field} placeholder="Nama berkas" aria-label={`Nama berkas ${i + 1}`} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={form.control}
                        name={`persyaratan.${i}.jumlah`}
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              <Input {...field} placeholder="Jumlah" aria-label={`Jumlah berkas ${i + 1}`} />
                            </FormControl>
                          </FormItem>
                        )}
                      />
                    </div>
                  )}
                />
                {form.formState.errors.persyaratan?.root?.message || form.formState.errors.persyaratan?.message ? (
                  <p className="text-sm text-destructive">
                    {form.formState.errors.persyaratan?.root?.message ?? form.formState.errors.persyaratan?.message}
                  </p>
                ) : null}
                <FieldArea control={form.control} name="catatan" label="Catatan di bawah tabel" rows={2} placeholder="Mis. Jika dikuasakan, sertakan surat kuasa bermeterai." />
              </Bagian>
            </div>

            <div className="grid min-w-0 gap-4">
              <PanelAlur
                item={entri}
                entriBaru={!entri}
                jalankan={jalankan}
                memproses={memproses || menyimpan}
                sebelumAksi={async () => (berubah ? simpan() : true)}
              />
              <Bagian judul="Ketentuan">
                <FieldSakelar control={form.control} name="cekFisik" label="Wajib cek fisik" deskripsi="Kendaraan dibawa untuk gesek nomor rangka & mesin." />
                <FieldSakelar control={form.control} name="nonTunai" label="Bisa non-tunai" />
                <FieldSakelar control={form.control} name="diwakilkan" label="Dapat diwakilkan" />
                <FieldSakelar control={form.control} name="unggulan" label="Tampil di beranda" deskripsi="Menjadi tab Layanan utama di beranda." />
              </Bagian>
              <Bagian judul="Penanggung jawab">
                <FieldPilih control={form.control} name="penyedia" label="Unit penyedia" opsi={UNIT_PENYEDIA.map((u) => ({ nilai: u, label: u }))} />
                <FieldPilih control={form.control} name="pemeriksa" label="Pemeriksa" opsi={PEMERIKSA.map((u) => ({ nilai: u, label: u }))} />
              </Bagian>
              <Bagian judul="Pratinjau" deskripsi="Daftar “Bawa saat datang” di portal.">
                <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                  <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Persyaratan · {pratinjau.nama || "Nama layanan"}
                  </p>
                  <ul className="mt-2 grid gap-1.5">
                    {pratinjau.persyaratan?.filter((p) => p.dokumen).map((p) => (
                      <li key={p.id} className="flex items-start gap-2">
                        <IconCheckbox className="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        <span className="flex-1">{p.dokumen}</span>
                        <span className="shrink-0 text-muted-foreground">{p.jumlah}</span>
                      </li>
                    ))}
                  </ul>
                  <div className="mt-3 flex flex-wrap gap-1">
                    <Badge variant="outline">{pratinjau.cekFisik ? "Wajib cek fisik" : "Tanpa cek fisik"}</Badge>
                    <Badge variant="outline">{pratinjau.loket}</Badge>
                    {pratinjau.nonTunai && <Badge variant="outline">Bisa non-tunai</Badge>}
                    {pratinjau.label?.map((l) => (
                      <Badge key={l} variant="outline">
                        {l}
                      </Badge>
                    ))}
                  </div>
                </div>
              </Bagian>
              {entri && (
                <Bagian judul="Riwayat">
                  <RiwayatAlur riwayat={entri.riwayat} />
                </Bagian>
              )}
            </div>
          </div>
          <BilahSimpan
            berubah={berubah}
            menyimpan={menyimpan}
            onBatal={() => form.reset()}
            labelSimpan={entri ? "Simpan perubahan" : "Simpan sebagai draf"}
          />
        </form>
      </Form>
      <JagaPerubahan aktif={berubah && !menyimpan} />
      {dialog}
    </Halaman>
  )
}
