import { IconServer } from "@tabler/icons-react"
import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldTeks } from "@/components/form/fields"
import { IsianItem } from "@/components/form/item-fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

/** Nomor telepon atau WhatsApp (boleh kosong), dengan aturan yang sama seperti server */
const nomor = (label: string, minAngka: number) =>
  v.teks
    .trim()
    .max(30, maks(30))
    .refine(
      (s) => s === "" || (/^\+?[\d ().-]+$/.test(s) && (s.match(/\d/g)?.length ?? 0) >= minAngka),
      `${label} berisi minimal ${minAngka} angka, boleh dengan spasi, tanda kurung, titik, tanda hubung, dan + di depan.`
    )

// Batas yang sama dengan server (UpdateContactRequest)
const schema = z.object({
  namaKantor: v.wajib("Nama kantor").max(120, maks(120)),
  alamat: v.wajib("Alamat").max(200, maks(200)),
  kota: v.teks.max(80, maks(80)),
  provinsi: v.teks.max(80, maks(80)),
  telepon: nomor("Nomor telepon", 6),
  whatsapp: nomor("Nomor WhatsApp", 9),
  whatsappCatatan: v.teks.max(80, maks(80)),
  email: v.email.max(120, maks(120)),
  mediaSosial: z
    .array(
      z.object({
        id: z.string(),
        platform: v.wajib("Platform").max(40, maks(40)),
        akun: v.wajib("Akun").max(60, maks(60)),
        url: v.tautan.max(300, maks(300)),
      })
    )
    .max(8, "Maksimal 8 akun media sosial."),
  mapsUrl: v.tautan.max(500, maks(500)),
  catatanLokasi: v.teks.max(120, maks(120)),
  janjiBalasan: v.teks.max(200, maks(200)),
  kanalPengaduan: z
    .array(
      z.object({
        id: z.string(),
        nama: v.wajib("Nama kanal").max(80, maks(80)),
        deskripsi: v.teks.max(300, maks(300)),
        tautanLabel: v.teks.max(60, maks(60)),
        tautanUrl: v.tautan.max(300, maks(300)),
      })
    )
    .max(8, "Maksimal 8 kanal pengaduan."),
})

export default function HalamanKontak() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Alamat, nomor kontak, media sosial, dan kanal pengaduan resmi. Data ini juga tampil di kaki halaman (footer) seluruh portal." />
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Data ini dipakai bersama semua pengelola Kontak & Kanal dan langsung tampil di footer seluruh portal serta
          halaman Kontak & Pengaduan. Sebelum disimpan pertama kali, portal menyatakan alamat dan nomor kontak belum
          tersedia.
        </AlertDescription>
      </Alert>
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
                  <div className="grid gap-2 @2xl/main:grid-cols-[9rem_12rem_minmax(0,1fr)]">
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
