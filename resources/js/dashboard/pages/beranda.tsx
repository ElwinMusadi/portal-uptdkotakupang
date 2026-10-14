import { IconExternalLink, IconServer } from "@tabler/icons-react"
import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldTeks } from "@/components/form/fields"
import { FieldJudul2Nada, IsianItem } from "@/components/form/item-fields"
import { DaftarObjek, FieldDaftarTeks } from "@/components/form/list-fields"
import { IsianLogo } from "@/components/form/media-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { newId } from "@/lib/id"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

/** ID video YouTube dari tautan bagikan, tonton, Shorts, live, atau sematan; null bila bukan (sama dengan server, D-040) */
function idYoutube(tautan: string): string | null {
  let u: URL
  try {
    u = new URL(tautan.trim())
  } catch {
    return null
  }
  if (!/^https?:$/.test(u.protocol)) return null
  const host = u.hostname.toLowerCase().replace(/^(www|m)\./, "")
  const id =
    host === "youtu.be"
      ? u.pathname.slice(1)
      : !["youtube.com", "youtube-nocookie.com"].includes(host)
        ? null
        : u.pathname === "/watch"
          ? u.searchParams.get("v")
          : (u.pathname.match(/^\/(?:embed|shorts|live)\/([^/]+)\/?$/)?.[1] ?? null)
  return id && /^[\w-]{11}$/.test(id) ? id : null
}

const judul2 = z.object({
  judul: v.wajib("Judul").max(80, maks(80)),
  lanjutan: v.teks.max(80, maks(80)),
  deskripsi: v.teks.max(300, maks(300)),
})

// Batas yang sama dengan server (UpdateHomepageRequest)
const schema = z.object({
  hero: z.object({
    eyebrow: v.teks.max(120, maks(120)),
    judul: v.wajib("Judul").max(80, maks(80)),
    lanjutan: v.teks.max(80, maks(80)),
    deskripsi: v.wajib("Deskripsi").max(300, maks(300)),
    tombolLabel: v.wajib("Label tombol").max(40, maks(40)),
    tombolUrl: v.tautan.max(300, maks(300)),
    catatan: v.teks.max(160, maks(160)),
  }),
  video: z
    .object({
      url: z
        .string()
        .trim()
        .max(300, maks(300))
        .refine((u) => !u || idYoutube(u) !== null, "Gunakan tautan video YouTube, mis. https://youtu.be/…"),
      label: v.teks.max(40, maks(40)),
      durasi: z.string().trim().refine((d) => !d || /^\d{1,2}:\d{2}(:\d{2})?$/.test(d), "Tulis durasi seperti 2:14 atau 1:02:30."),
      judul: v.teks.max(120, maks(120)),
    })
    .refine((x) => !x.url || x.label.trim(), { message: "Isi label tombol untuk video ini.", path: ["label"] }),
  nilai: z
    .array(z.object({ id: z.string(), judul: v.wajib("Judul").max(60, maks(60)), deskripsi: v.teks.max(200, maks(200)) }))
    .max(6, "Maksimal 6 nilai utama."),
  layananUtama: judul2,
  mitra: z
    .array(
      z.object({
        id: z.string(),
        singkatan: z.string().trim().max(3, "Maks. 3 huruf."),
        nama: v.wajib("Nama mitra").max(60, maks(60)),
        logo: z.string().nullable(),
      })
    )
    .max(12, "Maksimal 12 mitra."),
  kanal: z
    .array(
      z.object({
        id: z.string(),
        kelompok: v.wajib("Kelompok").max(40, maks(40)),
        item: z
          .array(
            z
              .object({ teks: z.string().trim().max(60, maks(60)), tautan: v.tautan.max(300, maks(300)) })
              .refine((k) => !k.tautan || k.teks, { message: "Isi nama kanal untuk tautan ini.", path: ["teks"] })
          )
          .max(8, "Maksimal 8 kanal per kelompok."),
      })
    )
    .max(8, "Maksimal 8 kelompok kanal."),
  integritas: judul2,
  biaya: judul2.extend({
    catatanNotis: v.teks.max(200, maks(200)),
    wajibDibawa: v.teks.max(200, maks(200)),
    jaminan: z.array(z.string().max(60, maks(60))).max(6, "Maksimal 6 jaminan."),
  }),
  penutup: judul2,
})

const BAGIAN = [
  ["hero", "Pembuka"],
  ["video", "Video"],
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
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Teks ini dipakai bersama semua pengelola Beranda dan langsung tampil di beranda portal. Sebelum disimpan
          pertama kali, isinya sama dengan teks yang sudah tertulis di beranda. Ikon setiap butir nilai dan kanal
          mengikuti urutannya; butir tambahan memakai ikon umum.
        </AlertDescription>
      </Alert>
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

              <Bagian
                id="bagian-video"
                judul="Video alur pelayanan"
                deskripsi="Tombol “Tonton alur pelayanan” di bawah papan layanan membuka video YouTube ini. Tombolnya tidak tampil selama tautan kosong."
              >
                <FieldTeks
                  control={form.control}
                  name="video.url"
                  label="Tautan YouTube"
                  inputMode="url"
                  placeholder="https://youtu.be/…"
                  deskripsi="Tautan bagikan, tonton, atau Shorts. Disimpan sebagai tautan tonton tanpa parameter pelacak."
                />
                <Baris>
                  <FieldTeks control={form.control} name="video.label" label="Label tombol" />
                  <FieldTeks control={form.control} name="video.durasi" label="Durasi" placeholder="2:14" deskripsi="Opsional, tampil di tombol." />
                </Baris>
                <FieldTeks
                  control={form.control}
                  name="video.judul"
                  label="Judul di bawah video"
                  deskripsi="Opsional. Bila kosong, label tombol yang dipakai."
                />
                {n.video?.url && idYoutube(n.video.url) && (
                  <a
                    href={`https://www.youtube.com/watch?v=${idYoutube(n.video.url)}`}
                    target="_blank"
                    rel="noopener"
                    className="inline-flex w-fit items-center gap-1.5 text-sm underline underline-offset-4"
                  >
                    <IconExternalLink className="size-4" aria-hidden="true" />
                    Lihat video di YouTube
                  </a>
                )}
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

              <Bagian id="bagian-mitra" judul="Mitra satu atap" deskripsi="Logo tampil di lencana mitra di beranda; inisial dipakai bila logo belum diunggah. PNG berlatar transparan paling baik.">
                <DaftarObjek
                  control={form.control}
                  name="mitra"
                  label="Mitra"
                  labelTambah="Tambah mitra"
                  labelItem={(i) => `mitra ${i + 1}`}
                  itemBaru={() => ({ id: newId(), singkatan: "", nama: "", logo: null })}
                  render={(i) => (
                    <div className="grid gap-2 sm:grid-cols-[auto_6rem_minmax(0,1fr)]">
                      <IsianLogo
                        control={form.control}
                        name={`mitra.${i}.logo`}
                        label="Logo"
                        labelAksi={`logo mitra ${i + 1}`}
                        inisial={form.watch(`mitra.${i}.singkatan`)}
                        tujuan="beranda"
                      />
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
                      <DaftarObjek
                        control={form.control}
                        name={`kanal.${i}.item`}
                        label="Isi kelompok"
                        deskripsi="Tautan opsional: alamat lengkap (https://…), halaman portal (mis. kontak.html), tel:, atau mailto:. Situs luar dibuka di tab baru."
                        labelTambah="Tambah kanal"
                        labelItem={(j) => `kanal ${j + 1} di kelompok ${i + 1}`}
                        itemBaru={() => ({ teks: "", tautan: "" })}
                        maks={8}
                        ringkas
                        render={(j) => (
                          <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                            <IsianItem control={form.control} name={`kanal.${i}.item.${j}.teks`} label="Nama kanal" />
                            <IsianItem control={form.control} name={`kanal.${i}.item.${j}.tautan`} label="Tautan (opsional)" placeholder="https://… atau kontak.html" />
                          </div>
                        )}
                      />
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
