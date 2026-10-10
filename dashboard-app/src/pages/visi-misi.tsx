import { z } from "zod"

import { Bagian, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { IsianItem } from "@/components/form/item-fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FieldBerkas } from "@/components/form/media-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const schema = z.object({
  sumberVisi: v.teks,
  visi: v.wajib("Visi"),
  misi: z.array(z.object({ id: z.string(), teks: v.wajib("Misi"), kataKunci: v.teks })),
  kontribusi: z.array(z.object({ id: z.string(), misi: v.teks, judul: v.wajib("Judul"), deskripsi: v.teks })),
  nilai: z.array(
    z.object({
      id: z.string(),
      huruf: z.string().trim().max(2, "Satu atau dua huruf."),
      judul: v.wajib("Nilai"),
      deskripsi: v.teks,
    })
  ),
  maklumat: z.object({ teks: v.wajib("Isi maklumat"), penandatangan: v.teks, berkas: v.berkas }),
})

export default function HalamanVisiMisi() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Visi dan misi mengikuti Pemerintah Provinsi NTT. Kontribusi PINTU, nilai dasar ASN (BerAKHLAK), dan maklumat pelayanan juga dikelola di sini." />
      <FormPengaturan nama="visiMisi" schema={schema}>
        {(form) => (
          <>
            <Bagian judul="Visi">
              <FieldTeks control={form.control} name="sumberVisi" label="Sumber" placeholder="Mis. Visi Pemerintah Provinsi NTT" />
              <FieldArea control={form.control} name="visi" label="Rumusan visi" wajib rows={2} className="[&_textarea]:text-lg [&_textarea]:font-medium" />
            </Bagian>
            <Bagian judul="Misi" deskripsi="Kata kunci membantu mengingat misi saat menyusun program dan inovasi layanan.">
              <DaftarObjek
                control={form.control}
                name="misi"
                label="Daftar misi"
                labelTambah="Tambah misi"
                labelItem={(i) => `misi ${i + 1}`}
                itemBaru={() => ({ id: newId(), teks: "", kataKunci: "" })}
                render={(i) => (
                  <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_10rem]">
                    <IsianItem control={form.control} name={`misi.${i}.teks`} label={`Misi ${String(i + 1).padStart(2, "0")}`} area />
                    <IsianItem control={form.control} name={`misi.${i}.kataKunci`} label="Kata kunci" />
                  </div>
                )}
              />
            </Bagian>
            <Bagian judul="Kontribusi PINTU" deskripsi="Bagaimana portal mendukung misi provinsi secara langsung.">
              <DaftarObjek
                control={form.control}
                name="kontribusi"
                label="Kontribusi"
                labelTambah="Tambah kontribusi"
                labelItem={(i) => `kontribusi ${i + 1}`}
                itemBaru={() => ({ id: newId(), misi: "", judul: "", deskripsi: "" })}
                render={(i) => (
                  <>
                    <div className="grid gap-2 sm:grid-cols-[10rem_minmax(0,1fr)]">
                      <IsianItem control={form.control} name={`kontribusi.${i}.misi`} label="Misi" placeholder="Misi 01 · Maju" />
                      <IsianItem control={form.control} name={`kontribusi.${i}.judul`} label="Judul" />
                    </div>
                    <IsianItem control={form.control} name={`kontribusi.${i}.deskripsi`} label="Penjelasan" area />
                  </>
                )}
              />
            </Bagian>
            <Bagian judul="Nilai dasar ASN · BerAKHLAK">
              <DaftarObjek
                control={form.control}
                name="nilai"
                label="Nilai"
                labelTambah="Tambah nilai"
                labelItem={(i) => `nilai ${i + 1}`}
                itemBaru={() => ({ id: newId(), huruf: "", judul: "", deskripsi: "" })}
                render={(i) => (
                  <div className="grid gap-2 sm:grid-cols-[5rem_12rem_minmax(0,1fr)]">
                    <IsianItem control={form.control} name={`nilai.${i}.huruf`} label="Huruf" />
                    <IsianItem control={form.control} name={`nilai.${i}.judul`} label="Nilai" />
                    <IsianItem control={form.control} name={`nilai.${i}.deskripsi`} label="Perilaku" />
                  </div>
                )}
              />
            </Bagian>
            <Bagian judul="Maklumat pelayanan" deskripsi="Janji layanan yang dipasang di ruang tunggu.">
              <FieldArea control={form.control} name="maklumat.teks" label="Isi maklumat" wajib rows={4} />
              <FieldTeks control={form.control} name="maklumat.penandatangan" label="Penandatangan" />
              <FieldBerkas control={form.control} name="maklumat.berkas" label="Berkas maklumat (PDF bertanda tangan)" terima=".pdf" />
            </Bagian>
          </>
        )}
      </FormPengaturan>
    </Halaman>
  )
}
