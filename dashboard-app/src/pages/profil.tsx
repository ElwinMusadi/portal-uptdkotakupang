import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada, IsianItem } from "@/components/form/item-fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FieldGambar } from "@/components/form/media-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const schema = z.object({
  judul: v.wajib("Judul"),
  lanjutan: v.teks,
  pengantar: v.wajib("Pengantar"),
  foto: z.string().nullable(),
  keteranganFoto: v.teks,
  kedudukan: z.object({ judul: v.wajib("Judul"), lanjutan: v.teks, deskripsi: v.teks }),
  identitas: z.array(z.object({ id: z.string(), label: v.wajib("Label"), nilai: v.wajib("Isi") })),
  tugasFungsi: z.array(
    z.object({ id: z.string(), judul: v.wajib("Tugas"), deskripsi: v.teks, kataKunci: v.teks })
  ),
  sambutan: z.object({
    kutipan: v.teks,
    nama: v.teks,
    jabatan: v.teks,
    foto: z.string().nullable(),
  }),
})

export default function HalamanProfil() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Isi halaman Profil: kedudukan unit, tugas dan fungsi, serta sambutan pimpinan. Tinjau minimal setahun sekali atau saat ada perubahan organisasi." />
      <FormPengaturan nama="profil" schema={schema}>
        {(form) => {
          const n = form.watch()
          return (
            <>
              <Bagian judul="Pembuka halaman">
                <FieldJudul2Nada control={form.control} judul="judul" lanjutan="lanjutan" nilaiJudul={n.judul} nilaiLanjutan={n.lanjutan} />
                <FieldArea control={form.control} name="pengantar" label="Pengantar" wajib rows={3} />
              </Bagian>
              <Bagian judul="Foto gedung">
                <div className="grid gap-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                  <FieldGambar control={form.control} name="foto" label="Foto kantor" deskripsi="Gunakan foto resmi Kantor Bersama Samsat Kota Kupang." />
                  <FieldArea control={form.control} name="keteranganFoto" label="Keterangan foto" rows={4} />
                </div>
              </Bagian>
              <Bagian judul="Kedudukan" deskripsi="Ringkasan identitas unit dalam bentuk tabel dua kolom.">
                <FieldJudul2Nada
                  control={form.control}
                  judul="kedudukan.judul"
                  lanjutan="kedudukan.lanjutan"
                  nilaiJudul={n.kedudukan?.judul}
                  nilaiLanjutan={n.kedudukan?.lanjutan}
                />
                <FieldArea control={form.control} name="kedudukan.deskripsi" label="Deskripsi" rows={2} />
                <DaftarObjek
                  control={form.control}
                  name="identitas"
                  label="Tabel identitas"
                  labelTambah="Tambah baris"
                  labelItem={(i) => `baris identitas ${i + 1}`}
                  itemBaru={() => ({ id: newId(), label: "", nilai: "" })}
                  render={(i) => (
                    <div className="grid gap-2 sm:grid-cols-[12rem_minmax(0,1fr)]">
                      <IsianItem control={form.control} name={`identitas.${i}.label`} label="Label" />
                      <IsianItem control={form.control} name={`identitas.${i}.nilai`} label="Isi" />
                    </div>
                  )}
                />
              </Bagian>
              <Bagian judul="Tugas & fungsi" deskripsi="Ringkasan untuk masyarakat; rujukan lengkap mengikuti Peraturan Gubernur.">
                <DaftarObjek
                  control={form.control}
                  name="tugasFungsi"
                  label="Daftar tugas"
                  labelTambah="Tambah tugas"
                  labelItem={(i) => `tugas ${i + 1}`}
                  itemBaru={() => ({ id: newId(), judul: "", deskripsi: "", kataKunci: "" })}
                  render={(i) => (
                    <>
                      <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_9rem]">
                        <IsianItem control={form.control} name={`tugasFungsi.${i}.judul`} label="Tugas" />
                        <IsianItem control={form.control} name={`tugasFungsi.${i}.kataKunci`} label="Kata kunci" placeholder="Mis. Layani" />
                      </div>
                      <IsianItem control={form.control} name={`tugasFungsi.${i}.deskripsi`} label="Penjelasan" />
                    </>
                  )}
                />
              </Bagian>
              <Bagian judul="Sambutan pimpinan">
                <div className="grid gap-5 md:grid-cols-[220px_minmax(0,1fr)]">
                  <FieldGambar control={form.control} name="sambutan.foto" label="Foto Kepala UPTD" rasio="aspect-[4/5]" deskripsi="Foto resmi, latar polos." />
                  <div className="grid content-start gap-5">
                    <FieldArea control={form.control} name="sambutan.kutipan" label="Kutipan sambutan" rows={4} />
                    <Baris>
                      <FieldTeks control={form.control} name="sambutan.nama" label="Nama" />
                      <FieldTeks control={form.control} name="sambutan.jabatan" label="Jabatan" />
                    </Baris>
                  </div>
                </div>
              </Bagian>
            </>
          )
        }}
      </FormPengaturan>
    </Halaman>
  )
}
