import * as React from "react"
import { IconMapPin, IconPlus } from "@tabler/icons-react"
import { toast } from "sonner"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldSakelar, FieldTeks } from "@/components/form/fields"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Switch } from "@/components/ui/switch"
import type { Hari, JadwalKeliling } from "@/lib/api"
import { jamTitik } from "@/lib/format"
import { sekarangWita } from "@/lib/jadwal"
import { HARI_LABEL } from "@/lib/meta"
import { useKoleksi, useUbahEntri } from "@/lib/queries"
import { cn } from "@/lib/utils"
import * as v from "@/lib/validasi"

const KECAMATAN = ["Alak", "Kelapa Lima", "Kota Lama", "Kota Raja", "Maulafa", "Oebobo"].map((k) => `Kec. ${k}`)
const HARI_KERJA: Hari[] = [1, 2, 3, 4, 5, 6]
const urutHari = (h: number) => (h + 6) % 7

const schema = z
  .object({
    hari: z.number(),
    mulai: v.jam.refine((x) => x !== "", "Isi jam mulai."),
    selesai: v.jam.refine((x) => x !== "", "Isi jam selesai."),
    lokasi: v.wajib("Lokasi"),
    kecamatan: v.wajib("Kecamatan"),
    layanan: v.wajib("Layanan"),
    catatan: v.teks,
    aktif: z.boolean(),
  })
  .refine((d) => d.selesai > d.mulai, { path: ["selesai"], message: "Jam selesai harus setelah jam mulai." })
type FormKeliling = z.infer<typeof schema>

const k = pembantuKolom<JadwalKeliling>()

function PratinjauMinggu({ data }: { data: JadwalKeliling[] }) {
  const hariIni = sekarangWita().hari
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">Pratinjau jadwal minggu ini</CardTitle>
        <CardDescription>Tampilan ringkas seperti di halaman Jadwal portal. Hanya lokasi aktif.</CardDescription>
      </CardHeader>
      <CardContent>
        <ol className="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
          {HARI_KERJA.map((h) => {
            const titik = data
              .filter((x) => x.aktif && x.hari === h)
              .sort((a, b) => a.mulai.localeCompare(b.mulai))
            return (
              <li
                key={h}
                className={cn(
                  "rounded-lg border p-3",
                  h === hariIni && "border-brand/50 bg-brand/5"
                )}
              >
                <p className="flex items-center justify-between text-sm font-medium">
                  {HARI_LABEL[h]}
                  {h === hariIni && <span className="text-xs font-normal text-brand">Hari ini</span>}
                </p>
                {titik.length === 0 ? (
                  <p className="mt-1 text-sm text-muted-foreground">Tidak ada jadwal.</p>
                ) : (
                  <ul className="mt-2 grid gap-2">
                    {titik.map((t) => (
                      <li key={t.id} className="flex gap-2 text-sm">
                        <span className="w-24 shrink-0 text-muted-foreground tabular-nums">
                          {jamTitik(t.mulai)}–{jamTitik(t.selesai)}
                        </span>
                        <span>
                          {t.lokasi}
                          <span className="block text-xs text-muted-foreground">{t.kecamatan}</span>
                        </span>
                      </li>
                    ))}
                  </ul>
                )}
              </li>
            )
          })}
        </ol>
      </CardContent>
    </Card>
  )
}

export default function HalamanKeliling() {
  const { data, isLoading } = useKoleksi("keliling")
  const ubah = useUbahEntri("keliling")
  const hapus = useHapus("keliling", "jadwal keliling")

  const ed = useEditorEntri({
    koleksi: "keliling",
    schema,
    nama: "jadwal keliling",
    kosong: (): FormKeliling => ({
      hari: 1,
      mulai: "08:00",
      selesai: "12:00",
      lokasi: "",
      kecamatan: KECAMATAN[0],
      layanan: "Pajak tahunan",
      catatan: "",
      aktif: true,
    }),
    dariEntri: (j): FormKeliling => ({
      hari: j.hari,
      mulai: j.mulai,
      selesai: j.selesai,
      lokasi: j.lokasi,
      kecamatan: j.kecamatan,
      layanan: j.layanan,
      catatan: j.catatan,
      aktif: j.aktif,
    }),
    keEntriBaru: (f) => ({ ...f, hari: f.hari as Hari }),
    kePatch: (f) => ({ ...f, hari: f.hari as Hari }),
  })

  const terurut = React.useMemo(
    () =>
      data
        ?.slice()
        .sort((a, b) => urutHari(a.hari) - urutHari(b.hari) || a.mulai.localeCompare(b.mulai)),
    [data]
  )

  const setAktif = async (baris: JadwalKeliling[], aktif: boolean) => {
    for (const b of baris) await ubah.mutateAsync({ id: b.id, patch: { aktif } })
    toast.success(`${baris.length} jadwal ${aktif ? "diaktifkan" : "dinonaktifkan"}`)
  }

  const kolom = [
    k.accessor("hari", {
      header: "Hari",
      cell: ({ row }) => <span className="font-medium">{HARI_LABEL[row.original.hari]}</span>,
    }),
    k.accessor("mulai", {
      header: "Jam",
      cell: ({ row }) => (
        <span className="whitespace-nowrap tabular-nums">
          {jamTitik(row.original.mulai)}–{jamTitik(row.original.selesai)}
        </span>
      ),
    }),
    k.accessor("lokasi", {
      header: "Lokasi",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => ed.bukaUbah(row.original.id)}
          className="flex min-w-56 items-start gap-2 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <IconMapPin className="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <span className="grid gap-0.5">
            <span className="font-medium underline-offset-4 hover:underline">{row.original.lokasi}</span>
            <span className="text-xs text-muted-foreground">{row.original.kecamatan}</span>
          </span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("layanan", { header: "Layanan" }),
    k.accessor("aktif", {
      header: "Aktif",
      cell: ({ row }) => (
        <Switch
          checked={row.original.aktif}
          aria-label={`Aktifkan jadwal ${row.original.lokasi} hari ${HARI_LABEL[row.original.hari]}`}
          onCheckedChange={(aktif) => ubah.mutate({ id: row.original.id, patch: { aktif } })}
        />
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.lokasi}
          onSunting={() => ed.bukaUbah(row.original.id)}
          onHapus={() => void hapus([row.original.id], `${HARI_LABEL[row.original.hari]} · ${row.original.lokasi}`)}
        />
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Lokasi Samsat Keliling per hari. Jadwal minggu berikutnya diperbarui setiap Jumat; Samsat Keliling hanya melayani pajak tahunan.">
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah lokasi
        </Button>
      </PengantarHalaman>
      {terurut && <PratinjauMinggu data={terurut} />}
      <DataTable
        data={terurut}
        memuat={isLoading}
        columns={kolom}
        label="Daftar jadwal Samsat Keliling"
        ukuranHalaman={20}
        cari={{ placeholder: "Cari lokasi atau kecamatan…", teks: (j) => `${j.lokasi} ${j.kecamatan} ${j.catatan}` }}
        filter={[
          {
            id: "hari",
            label: "Hari",
            opsi: HARI_KERJA.map((h) => ({ nilai: String(h), label: HARI_LABEL[h] })),
            nilai: (j) => String(j.hari),
          },
          {
            id: "kecamatan",
            label: "Kecamatan",
            opsi: KECAMATAN.map((x) => ({ nilai: x, label: x })),
            nilai: (j) => j.kecamatan,
          },
        ]}
        tindakanMassal={(baris, bersihkan) => (
          <>
            <Button variant="outline" size="sm" onClick={() => void setAktif(baris, true).then(bersihkan)}>
              Aktifkan
            </Button>
            <Button variant="outline" size="sm" onClick={() => void setAktif(baris, false).then(bersihkan)}>
              Nonaktifkan
            </Button>
            <TombolHapusMassal
              onClick={async () => {
                if (await hapus(baris.map((b) => b.id))) bersihkan()
              }}
            />
          </>
        )}
        kosong={{ judul: "Belum ada jadwal keliling", deskripsi: "Tambahkan lokasi Samsat Keliling pertama." }}
      />
      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting jadwal keliling" : "Tambah jadwal keliling"}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
      >
        <FieldPilih
          control={ed.form.control}
          name="hari"
          label="Hari"
          angka
          opsi={HARI_KERJA.map((h) => ({ nilai: String(h), label: HARI_LABEL[h] }))}
        />
        <Baris>
          <FieldTeks control={ed.form.control} name="mulai" label="Mulai" type="time" step={300} wajib />
          <FieldTeks control={ed.form.control} name="selesai" label="Selesai" type="time" step={300} wajib />
        </Baris>
        <FieldTeks control={ed.form.control} name="lokasi" label="Lokasi" wajib placeholder="Mis. Kantor Kelurahan Oesapa" />
        <Baris>
          <FieldPilih
            control={ed.form.control}
            name="kecamatan"
            label="Kecamatan"
            opsi={KECAMATAN.map((x) => ({ nilai: x, label: x }))}
          />
          <FieldTeks control={ed.form.control} name="layanan" label="Layanan" wajib />
        </Baris>
        <FieldArea control={ed.form.control} name="catatan" label="Catatan" rows={2} placeholder="Mis. Bawa STNK, notis, dan KTP asli." />
        <FieldSakelar control={ed.form.control} name="aktif" label="Tampilkan di portal" />
      </EntriSheet>
    </Halaman>
  )
}
