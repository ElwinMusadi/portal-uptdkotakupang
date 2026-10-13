import * as React from "react"
import { zodResolver } from "@hookform/resolvers/zod"
import { IconArrowLeft, IconTrash, IconWand } from "@tabler/icons-react"
import { useForm, type Resolver } from "react-hook-form"
import { Link, useNavigate, useParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { PanelAlur, RiwayatAlur, useAksiAlur } from "@/components/alur/alur"
import { EntriTidakDitemukan } from "@/components/data/not-found"
import { Bagian, Baris, Halaman } from "@/components/data/page"
import { useHapus } from "@/components/data/row-actions"
import { FieldArea, FieldPilih, FieldSakelar, FieldTanggal, FieldTeks } from "@/components/form/fields"
import { FieldGambar } from "@/components/form/media-fields"
import { FieldKaya } from "@/components/form/rich-text-field"
import { BilahSimpan, KerangkaMemuat } from "@/components/form/settings-form"
import { JagaPerubahan } from "@/components/form/unsaved"
import { Button } from "@/components/ui/button"
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import type { Berita } from "@/lib/api"
import { alurBaru } from "@/lib/alur"
import { hariIniIso, menitBaca, slugify } from "@/lib/format"
import { validasiFormulir } from "@/lib/formulir"
import { KATEGORI_BERITA, PEMERIKSA, UNIT_PENYEDIA } from "@/lib/meta"
import { useKoleksi, usePengaturan, useTambahEntri, useUbahEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  judul: v.wajib("Judul").max(160, "Judul maksimal 160 karakter."),
  slug: v.slug,
  kategori: z.enum(["berita", "pengumuman", "keliling", "edukasi"]),
  ringkasan: v.wajib("Ringkasan").max(240, "Ringkasan maksimal 240 karakter."),
  isi: v.wajib("Isi tulisan"),
  sampul: z.string().nullable(),
  keteranganSampul: v.teks,
  kreditFoto: v.teks,
  tanggal: v.tanggalIso,
  sorotan: z.boolean(),
  pengumumanBeranda: z.boolean(),
  penyedia: v.wajib("Unit penyedia"),
  pemeriksa: v.teks,
})

type FormBerita = z.infer<typeof schema>

function kosong(): FormBerita {
  return {
    judul: "",
    slug: "",
    kategori: "berita",
    ringkasan: "",
    isi: "",
    sampul: null,
    keteranganSampul: "",
    kreditFoto: "",
    tanggal: hariIniIso(),
    sorotan: false,
    pengumumanBeranda: false,
    penyedia: "Pengelola publikasi",
    pemeriksa: PEMERIKSA[0],
  }
}

function dariEntri(b: Berita): FormBerita {
  return {
    judul: b.judul,
    slug: b.slug,
    kategori: b.kategori,
    ringkasan: b.ringkasan,
    isi: b.isi,
    sampul: b.sampul,
    keteranganSampul: b.keteranganSampul,
    kreditFoto: b.kreditFoto,
    tanggal: b.tanggal,
    sorotan: b.sorotan,
    pengumumanBeranda: b.pengumumanBeranda,
    penyedia: b.penyedia,
    pemeriksa: b.pemeriksa,
  }
}

export default function EditorBerita() {
  const { id } = useParams()
  const { data, isLoading } = useKoleksi("berita")
  if (isLoading || !data) {
    return (
      <Halaman>
        <KerangkaMemuat />
      </Halaman>
    )
  }
  const entri = id ? (data.find((b) => b.id === id) ?? null) : null
  if (id && !entri) {
    return (
      <EntriTidakDitemukan
        judul="Tulisan tidak ditemukan"
        kembali="/berita"
        labelKembali="Kembali ke daftar berita"
      />
    )
  }
  return <IsiEditor key={entri?.id ?? "baru"} entri={entri} semua={data} />
}

function IsiEditor({ entri, semua }: { entri: Berita | null; semua: Berita[] }) {
  const navigate = useNavigate()
  const tambah = useTambahEntri("berita")
  const ubah = useUbahEntri("berita")
  const { data: akun } = usePengaturan("akun")
  const hapus = useHapus("berita", "tulisan")
  const { jalankan, dialog, memproses } = useAksiAlur("berita")
  const [slugManual, setSlugManual] = React.useState(!!entri)

  const form = useForm<FormBerita>({
    defaultValues: entri ? dariEntri(entri) : kosong(),
    resolver: zodResolver(schema) as Resolver<FormBerita>,
    mode: "onTouched",
  })
  const berubah = form.formState.isDirty
  const menyimpan = tambah.isPending || ubah.isPending

  const judul = form.watch("judul")
  const isi = form.watch("isi")
  React.useEffect(() => {
    if (!slugManual) form.setValue("slug", slugify(judul), { shouldDirty: true })
  }, [judul, slugManual, form])

  const simpan = async (): Promise<boolean> => {
    if (!(await validasiFormulir(form))) {
      toast.error("Periksa kembali isian yang ditandai merah.")
      return false
    }
    const nilai = form.getValues()
    if (semua.some((b) => b.slug === nilai.slug && b.id !== entri?.id)) {
      form.setError("slug", { message: "Slug sudah dipakai tulisan lain." })
      form.setFocus("slug")
      return false
    }
    try {
      let tersimpan: Berita
      if (entri) {
        tersimpan = await ubah.mutateAsync({ id: entri.id, patch: nilai })
      } else {
        tersimpan = await tambah.mutateAsync({
          ...alurBaru(akun?.nama ?? "Pengelola PINTU", nilai.penyedia, nilai.pemeriksa),
          ...nilai,
        })
      }
      // hanya satu tulisan utama di halaman Informasi
      if (nilai.sorotan) {
        const lain = semua.filter((b) => b.sorotan && b.id !== tersimpan.id)
        for (const b of lain) await ubah.mutateAsync({ id: b.id, patch: { sorotan: false } })
        if (lain.length) toast.info("Sorotan dipindahkan ke tulisan ini.")
      }
      form.reset(nilai)
      toast.success(entri ? "Perubahan disimpan" : "Draf tulisan dibuat")
      // tunggu render ulang agar penjaga perubahan tahu formulir sudah bersih
      if (!entri) setTimeout(() => navigate(`/berita/${tersimpan.id}`, { replace: true }), 0)
      return true
    } catch {
      return false
    }
  }

  return (
    <Halaman>
      <div className="flex flex-wrap items-center gap-2">
        <Button variant="ghost" size="sm" asChild className="-ml-2">
          <Link to="/berita">
            <IconArrowLeft />
            Semua tulisan
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
                setTimeout(() => navigate("/berita", { replace: true }), 0)
              }
            }}
          >
            <IconTrash />
            Hapus tulisan
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
              <Bagian
                judul="Isi tulisan"
                deskripsi={isi ? `Sekitar ${menitBaca(isi)} menit baca.` : "Kalimat pendek dan jelas lebih mudah dibaca warga."}
              >
                <FieldTeks
                  control={form.control}
                  name="judul"
                  label="Judul"
                  wajib
                  placeholder="Mis. Pembayaran QRIS kini tersedia di semua kasir."
                  className="[&_input]:h-11 [&_input]:text-lg [&_input]:font-semibold"
                />
                <FieldArea
                  control={form.control}
                  name="ringkasan"
                  label="Ringkasan"
                  wajib
                  rows={3}
                  maks={240}
                  deskripsi="Tampil di kartu berita dan pratinjau tautan."
                />
                <FieldKaya
                  control={form.control}
                  name="isi"
                  label="Isi"
                  wajib
                  placeholder="Tulis isi berita atau pengumuman…"
                  deskripsi="Gunakan subjudul dan daftar agar mudah dipindai."
                />
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
              <Bagian judul="Atribut">
                <Baris className="sm:grid-cols-1">
                  <FieldPilih
                    control={form.control}
                    name="kategori"
                    label="Kategori"
                    opsi={Object.entries(KATEGORI_BERITA).map(([nilai, label]) => ({ nilai, label }))}
                  />
                  <FieldTanggal control={form.control} name="tanggal" label="Tanggal tayang" wajib />
                </Baris>
                <FormField
                  control={form.control}
                  name="slug"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Slug alamat</FormLabel>
                      <div className="flex gap-2">
                        <FormControl>
                          <Input
                            {...field}
                            onChange={(e) => {
                              setSlugManual(true)
                              field.onChange(e.target.value)
                            }}
                            className="font-mono text-sm"
                          />
                        </FormControl>
                        <Button
                          type="button"
                          variant="outline"
                          size="icon"
                          title="Buat dari judul"
                          onClick={() => {
                            setSlugManual(false)
                            form.setValue("slug", slugify(form.getValues("judul")), {
                              shouldDirty: true,
                              shouldValidate: true,
                            })
                          }}
                        >
                          <IconWand />
                          <span className="sr-only">Buat slug dari judul</span>
                        </Button>
                      </div>
                      <FormDescription>Bagian alamat tulisan, mis. berita/{field.value || "judul-tulisan"}.</FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FieldPilih
                  control={form.control}
                  name="penyedia"
                  label="Unit penyedia"
                  opsi={UNIT_PENYEDIA.map((u) => ({ nilai: u, label: u }))}
                />
                <FieldPilih
                  control={form.control}
                  name="pemeriksa"
                  label="Pemeriksa"
                  opsi={PEMERIKSA.map((u) => ({ nilai: u, label: u }))}
                />
                <FieldSakelar
                  control={form.control}
                  name="sorotan"
                  label="Jadikan tulisan utama"
                  deskripsi="Tampil besar di atas halaman Informasi."
                />
                <FieldSakelar
                  control={form.control}
                  name="pengumumanBeranda"
                  label="Tampilkan di beranda"
                  deskripsi="Muncul di papan layanan sebagai pengumuman baru."
                />
              </Bagian>
              <Bagian judul="Foto sampul">
                <FieldGambar control={form.control} name="sampul" label="Foto" />
                <FieldTeks control={form.control} name="keteranganSampul" label="Keterangan foto" />
                <FieldTeks control={form.control} name="kreditFoto" label="Kredit foto" placeholder="Mis. Sub Bagian Tata Usaha" />
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
