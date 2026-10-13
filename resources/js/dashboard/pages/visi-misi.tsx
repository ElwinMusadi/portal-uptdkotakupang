import { IconServer } from "@tabler/icons-react"
import { z } from "zod"

import { Bagian, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { IsianItem } from "@/components/form/item-fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

// Batas yang sama dengan server (UpdateVisionMissionRequest)
const schema = z.object({
  sumberVisi: v.teks.max(120, maks(120)),
  visi: v.wajib("Visi").max(300, maks(300)),
  misi: z
    .array(z.object({ id: z.string(), teks: v.wajib("Misi").max(400, maks(400)), kataKunci: v.teks.max(40, maks(40)) }))
    .max(10, "Maksimal 10 misi."),
  kontribusi: z
    .array(
      z.object({
        id: z.string(),
        misi: v.teks.max(60, maks(60)),
        judul: v.wajib("Judul").max(120, maks(120)),
        deskripsi: v.teks.max(400, maks(400)),
      })
    )
    .max(6, "Maksimal 6 kontribusi."),
  nilai: z
    .array(
      z.object({
        id: z.string(),
        huruf: z.string().trim().max(2, "Satu atau dua huruf."),
        judul: v.wajib("Nilai").max(60, maks(60)),
        deskripsi: v.teks.max(200, maks(200)),
      })
    )
    .max(10, "Maksimal 10 nilai."),
  // Berkas maklumat belum bisa disimpan di server (D-020); nilainya selalu kosong
  maklumat: z.object({ teks: v.wajib("Isi maklumat").max(600, maks(600)), penandatangan: v.teks.max(120, maks(120)), berkas: z.null() }),
})

export default function HalamanVisiMisi() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Visi dan misi mengikuti Pemerintah Provinsi NTT. Kontribusi PINTU, nilai dasar ASN (BerAKHLAK), dan maklumat pelayanan juga dikelola di sini." />
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Isi ini dipakai bersama semua pengelola Visi & Misi dan langsung tampil di halaman Visi & Misi portal.
          Sebelum disimpan pertama kali, isinya adalah teks resmi yang sudah tampil di portal.
        </AlertDescription>
      </Alert>
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
              <div className="grid gap-1.5 rounded-lg border border-dashed p-3 text-sm">
                <p className="font-medium">Berkas maklumat (PDF bertanda tangan)</p>
                <p className="text-muted-foreground">
                  Unggahan berkas belum bisa disimpan di server. Untuk sementara, tombol “Unduh maklumat (PDF)” di
                  portal mengarah ke halaman Regulasi & Unduhan.
                </p>
              </div>
            </Bagian>
          </>
        )}
      </FormPengaturan>
    </Halaman>
  )
}
