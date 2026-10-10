import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada, IsianItem } from "@/components/form/item-fields"
import { DaftarObjek, FieldDaftarTeks } from "@/components/form/list-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const judul2 = z.object({ judul: v.wajib("Judul"), lanjutan: v.teks, deskripsi: v.teks })

const schema = z.object({
  hero: z.object({
    eyebrow: v.teks,
    judul: v.wajib("Judul"),
    lanjutan: v.teks,
    deskripsi: v.wajib("Deskripsi"),
    tombolLabel: v.wajib("Label tombol"),
    tombolUrl: v.tautan,
    catatan: v.teks,
  }),
  nilai: z.array(z.object({ id: z.string(), judul: v.wajib("Judul"), deskripsi: v.teks })),
  layananUtama: judul2,
  mitra: z.array(z.object({ id: z.string(), singkatan: z.string().trim().max(3, "Maks. 3 huruf."), nama: v.wajib("Nama mitra") })),
  kanal: z.array(z.object({ id: z.string(), kelompok: v.wajib("Kelompok"), item: z.array(z.string()) })),
  integritas: judul2,
  biaya: judul2.extend({
    catatanNotis: v.teks,
    wajibDibawa: v.teks,
    jaminan: z.array(z.string()),
  }),
  penutup: judul2,
})

const BAGIAN = [
  ["hero", "Pembuka"],
  ["nilai", "Nilai utama"],
  ["layanan-utama", "Layanan utama"],
  ["mitra", "Mitra"],
  ["kanal", "Kanal layanan"],
  ["integritas", "Integritas"],
  ["biaya", "Biaya"],
  ["penutup", "Penutup"],
] as const

export default function HalamanBeranda() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Teks dan blok di beranda portal. Layanan utama, papan layanan, simulasi, dasbor, dan tanya jawab diisi dari menu masing-masing." />
      <nav aria-label="Bagian beranda" className="-mt-1 flex flex-wrap gap-1.5">
        {BAGIAN.map(([id, label]) => (
          <a
            key={id}
            href={`#bagian-${id}`}
            onClick={(e) => {
              e.preventDefault()
              document.getElementById(`bagian-${id}`)?.scrollIntoView({ behavior: "smooth", block: "start" })
            }}
            className="rounded-full border px-3 py-1 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            {label}
          </a>
        ))}
      </nav>
      <FormPengaturan nama="beranda" schema={schema}>
        {(form) => {
          const n = form.watch()
          return (
            <>
              <Bagian id="bagian-hero" judul="Pembuka (hero)">
                <FieldTeks control={form.control} name="hero.eyebrow" label="Teks kecil di atas judul" />
                <FieldJudul2Nada control={form.control} judul="hero.judul" lanjutan="hero.lanjutan" nilaiJudul={n.hero?.judul} nilaiLanjutan={n.hero?.lanjutan} />
                <FieldArea control={form.control} name="hero.deskripsi" label="Deskripsi" wajib rows={3} />
                <Baris>
                  <FieldTeks control={form.control} name="hero.tombolLabel" label="Label tombol" wajib />
                  <FieldTeks control={form.control} name="hero.tombolUrl" label="Tujuan tombol" placeholder="layanan.html" />
                </Baris>
                <FieldTeks control={form.control} name="hero.catatan" label="Catatan kepercayaan" />
              </Bagian>

              <Bagian id="bagian-nilai" judul="Nilai utama" deskripsi="Tiga janji singkat di bawah papan layanan.">
                <DaftarObjek
                  control={form.control}
                  name="nilai"
                  label="Nilai"
                  maks={4}
                  labelTambah="Tambah nilai"
                  labelItem={(i) => `nilai ${i + 1}`}
                  itemBaru={() => ({ id: newId(), judul: "", deskripsi: "" })}
                  render={(i) => (
                    <div className="grid gap-2 sm:grid-cols-[14rem_minmax(0,1fr)]">
                      <IsianItem control={form.control} name={`nilai.${i}.judul`} label="Judul" />
                      <IsianItem control={form.control} name={`nilai.${i}.deskripsi`} label="Penjelasan" />
                    </div>
                  )}
                />
              </Bagian>

              <Bagian id="bagian-layanan-utama" judul="Layanan utama" deskripsi="Judul blok tab layanan. Isi tab diambil dari layanan bertanda “Tampil di beranda”.">
                <FieldJudul2Nada control={form.control} judul="layananUtama.judul" lanjutan="layananUtama.lanjutan" nilaiJudul={n.layananUtama?.judul} nilaiLanjutan={n.layananUtama?.lanjutan} />
                <FieldTeks control={form.control} name="layananUtama.deskripsi" label="Deskripsi" />
              </Bagian>

              <Bagian id="bagian-mitra" judul="Mitra satu atap">
                <DaftarObjek
                  control={form.control}
                  name="mitra"
                  label="Mitra"
                  labelTambah="Tambah mitra"
                  labelItem={(i) => `mitra ${i + 1}`}
                  itemBaru={() => ({ id: newId(), singkatan: "", nama: "" })}
                  render={(i) => (
                    <div className="grid gap-2 sm:grid-cols-[6rem_minmax(0,1fr)]">
                      <IsianItem control={form.control} name={`mitra.${i}.singkatan`} label="Inisial" />
                      <IsianItem control={form.control} name={`mitra.${i}.nama`} label="Nama" />
                    </div>
                  )}
                />
              </Bagian>

              <Bagian id="bagian-kanal" judul="Kanal layanan" deskripsi="Kelompok jalan masuk layanan: kantor, keliling, digital, informasi, pengaduan.">
                <DaftarObjek
                  control={form.control}
                  name="kanal"
                  label="Kelompok kanal"
                  labelTambah="Tambah kelompok"
                  labelItem={(i) => `kelompok kanal ${i + 1}`}
                  itemBaru={() => ({ id: newId(), kelompok: "", item: [] })}
                  render={(i) => (
                    <>
                      <IsianItem control={form.control} name={`kanal.${i}.kelompok`} label="Nama kelompok" />
                      <FieldDaftarTeks control={form.control} name={`kanal.${i}.item`} label="Isi kelompok" labelTambah="Tambah kanal" />
                    </>
                  )}
                />
              </Bagian>

              <Bagian id="bagian-integritas" judul="Pernyataan integritas">
                <FieldJudul2Nada control={form.control} judul="integritas.judul" lanjutan="integritas.lanjutan" nilaiJudul={n.integritas?.judul} nilaiLanjutan={n.integritas?.lanjutan} />
                <FieldArea control={form.control} name="integritas.deskripsi" label="Penjelasan" rows={3} />
              </Bagian>

              <Bagian id="bagian-biaya" judul="Biaya layanan">
                <FieldJudul2Nada control={form.control} judul="biaya.judul" lanjutan="biaya.lanjutan" nilaiJudul={n.biaya?.judul} nilaiLanjutan={n.biaya?.lanjutan} />
                <FieldArea control={form.control} name="biaya.deskripsi" label="Penjelasan" rows={2} />
                <Baris>
                  <FieldTeks control={form.control} name="biaya.catatanNotis" label="Catatan notis" />
                  <FieldTeks control={form.control} name="biaya.wajibDibawa" label="Wajib dibawa" />
                </Baris>
                <FieldDaftarTeks control={form.control} name="biaya.jaminan" label="Jaminan" labelTambah="Tambah jaminan" />
              </Bagian>

              <Bagian id="bagian-penutup" judul="Penutup">
                <FieldJudul2Nada control={form.control} judul="penutup.judul" lanjutan="penutup.lanjutan" nilaiJudul={n.penutup?.judul} nilaiLanjutan={n.penutup?.lanjutan} />
                <FieldTeks control={form.control} name="penutup.deskripsi" label="Penjelasan" />
              </Bagian>
            </>
          )
        }}
      </FormPengaturan>
    </Halaman>
  )
}
