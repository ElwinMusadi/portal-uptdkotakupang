import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldTeks } from "@/components/form/fields"
import { IsianItem } from "@/components/form/item-fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const schema = z.object({
  namaKantor: v.wajib("Nama kantor"),
  alamat: v.wajib("Alamat"),
  kota: v.teks,
  provinsi: v.teks,
  telepon: v.teks,
  whatsapp: v.teks,
  whatsappCatatan: v.teks,
  email: v.email,
  mediaSosial: z.array(z.object({ id: z.string(), platform: v.wajib("Platform"), akun: v.wajib("Akun"), url: v.tautan })),
  mapsUrl: v.tautan,
  catatanLokasi: v.teks,
  janjiBalasan: v.teks,
  kanalPengaduan: z.array(
    z.object({ id: z.string(), nama: v.wajib("Nama kanal"), deskripsi: v.teks, tautanLabel: v.teks, tautanUrl: v.tautan })
  ),
})

export default function HalamanKontak() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Alamat, nomor kontak, media sosial, dan kanal pengaduan resmi. Data ini juga tampil di kaki halaman (footer) seluruh portal." />
      <FormPengaturan nama="kontak" schema={schema}>
        {(form) => (
          <>
            <Bagian judul="Kantor">
              <FieldTeks control={form.control} name="namaKantor" label="Nama kantor" wajib />
              <FieldTeks control={form.control} name="alamat" label="Alamat jalan" wajib />
              <Baris>
                <FieldTeks control={form.control} name="kota" label="Kota" />
                <FieldTeks control={form.control} name="provinsi" label="Provinsi" />
              </Baris>
              <FieldTeks
                control={form.control}
                name="mapsUrl"
                label="Tautan Google Maps"
                type="url"
                deskripsi="Dipakai tombol “Buka di Google Maps” dan petunjuk arah."
              />
              <FieldTeks control={form.control} name="catatanLokasi" label="Catatan lokasi" placeholder="Mis. Parkir tersedia di halaman kantor" />
            </Bagian>
            <Bagian judul="Kontak langsung">
              <Baris>
                <FieldTeks control={form.control} name="telepon" label="Telepon" inputMode="tel" placeholder="(0380) 000 000" />
                <FieldTeks control={form.control} name="email" label="Surel" type="email" />
              </Baris>
              <Baris>
                <FieldTeks control={form.control} name="whatsapp" label="WhatsApp layanan" inputMode="tel" placeholder="+62 8xx-xxxx-xxxx" />
                <FieldTeks control={form.control} name="whatsappCatatan" label="Catatan WhatsApp" placeholder="Hanya pesan teks, hari kerja" />
              </Baris>
              <FieldTeks control={form.control} name="janjiBalasan" label="Janji waktu balasan" deskripsi="Tampil di formulir masukan." />
              <DaftarObjek
                control={form.control}
                name="mediaSosial"
                label="Media sosial"
                labelTambah="Tambah akun"
                labelItem={(i) => `akun media sosial ${i + 1}`}
                itemBaru={() => ({ id: newId(), platform: "Instagram", akun: "", url: "" })}
                render={(i) => (
                  <div className="grid gap-2 sm:grid-cols-[9rem_12rem_minmax(0,1fr)]">
                    <IsianItem control={form.control} name={`mediaSosial.${i}.platform`} label="Platform" />
                    <IsianItem control={form.control} name={`mediaSosial.${i}.akun`} label="Akun" placeholder="@akun" />
                    <IsianItem control={form.control} name={`mediaSosial.${i}.url`} label="Tautan" placeholder="https://…" />
                  </div>
                )}
              />
            </Bagian>
            <Bagian judul="Kanal pengaduan resmi" deskripsi="Tampil di halaman Kontak & Pengaduan.">
              <DaftarObjek
                control={form.control}
                name="kanalPengaduan"
                label="Kanal"
                labelTambah="Tambah kanal"
                labelItem={(i) => `kanal ${i + 1}`}
                itemBaru={() => ({ id: newId(), nama: "", deskripsi: "", tautanLabel: "", tautanUrl: "" })}
                render={(i) => (
                  <>
                    <IsianItem control={form.control} name={`kanalPengaduan.${i}.nama`} label="Nama kanal" />
                    <IsianItem control={form.control} name={`kanalPengaduan.${i}.deskripsi`} label="Penjelasan" area />
                    <div className="grid gap-2 sm:grid-cols-2">
                      <IsianItem control={form.control} name={`kanalPengaduan.${i}.tautanLabel`} label="Label tautan" />
                      <IsianItem control={form.control} name={`kanalPengaduan.${i}.tautanUrl`} label="Alamat tautan" placeholder="https://… atau jadwal.html" />
                    </div>
                  </>
                )}
              />
            </Bagian>
          </>
        )}
      </FormPengaturan>
    </Halaman>
  )
}
