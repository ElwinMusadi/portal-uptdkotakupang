import { IconServer } from "@tabler/icons-react"
import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada, IsianItem } from "@/components/form/item-fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FieldGambar } from "@/components/form/media-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

// Batas yang sama dengan server (UpdateProfileRequest). Foto hanya dari unggahan PINTU (D-028).
const schema = z.object({
  judul: v.wajib("Judul").max(80, maks(80)),
  lanjutan: v.teks.max(80, maks(80)),
  pengantar: v.wajib("Pengantar").max(400, maks(400)),
  foto: z.string().nullable(),
  keteranganFoto: v.teks.max(200, maks(200)),
  kedudukan: z.object({
    judul: v.wajib("Judul").max(80, maks(80)),
    lanjutan: v.teks.max(80, maks(80)),
    deskripsi: v.teks.max(400, maks(400)),
  }),
  identitas: z
    .array(z.object({ id: z.string(), label: v.wajib("Label").max(40, maks(40)), nilai: v.wajib("Isi").max(160, maks(160)) }))
    .max(12, "Maksimal 12 baris identitas."),
  tugasFungsi: z
    .array(
      z.object({
        id: z.string(),
        judul: v.wajib("Tugas").max(200, maks(200)),
        deskripsi: v.teks.max(300, maks(300)),
        kataKunci: v.teks.max(30, maks(30)),
      })
    )
    .max(12, "Maksimal 12 tugas."),
  sambutan: z.object({
    kutipan: v.teks.max(500, maks(500)),
    nama: v.teks.max(120, maks(120)),
    jabatan: v.teks.max(120, maks(120)),
    foto: z.string().nullable(),
  }),
})

export default function HalamanProfil() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Isi halaman Profil: kedudukan unit, tugas dan fungsi, serta sambutan pimpinan. Tinjau minimal setahun sekali atau saat ada perubahan organisasi." />
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Isi ini dipakai bersama semua pengelola Tentang UPTD dan langsung tampil di halaman Profil portal. Sebelum
          disimpan pertama kali, isinya adalah teks awal yang disusun dari Renstra BPAD 2025–2029 dan RPJMD NTT
          2025–2029.
        </AlertDescription>
      </Alert>
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
                  <FieldGambar
                    control={form.control}
                    name="foto"
                    label="Foto kantor"
                    tujuan="profil"
                    deskripsi="Kosongkan untuk memakai foto gedung bawaan portal. JPG, PNG, atau WebP; diperkecil otomatis menjadi maks. 1600 px."
                  />
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
                <div className="grid gap-5 md:grid-cols-[13.75rem_minmax(0,1fr)]">
                  <FieldGambar
                    control={form.control}
                    name="sambutan.foto"
                    label="Foto Kepala UPTD"
                    rasio="aspect-[4/5]"
                    tujuan="profil"
                    deskripsi="Foto resmi, latar polos. Tanpa foto, portal menampilkan ilustrasi."
                  />
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
