import * as React from "react"
import { IconCalendarEvent, IconPlus } from "@tabler/icons-react"
import { z } from "zod"

import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, useHapus } from "@/components/data/row-actions"
import { NadaBadge, TitikStatus } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldAngka, FieldPilih, FieldTanggal, FieldTeks } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Button } from "@/components/ui/button"
import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { FormControl, FormField, FormItem, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import type { PenyesuaianJadwal } from "@/lib/api"
import { hariIniIso, jamTitik, tanggal } from "@/lib/format"
import { statusLayanan } from "@/lib/jadwal"
import { HARI_LABEL } from "@/lib/meta"
import { useKoleksi, usePengaturan } from "@/lib/queries"
import * as v from "@/lib/validasi"

const skemaJam = z.object({
  hari: z.array(
    z
      .object({
        hari: z.number(),
        buka: z.boolean(),
        mulai: v.jam,
        selesai: v.jam,
        catatan: v.teks,
      })
      .superRefine((h, ctx) => {
        if (!h.buka) return
        if (!h.mulai) ctx.addIssue({ code: "custom", path: ["mulai"], message: "Isi jam buka." })
        if (!h.selesai) ctx.addIssue({ code: "custom", path: ["selesai"], message: "Isi jam tutup." })
        if (h.mulai && h.selesai && h.selesai <= h.mulai)
          ctx.addIssue({ code: "custom", path: ["selesai"], message: "Jam tutup harus setelah jam buka." })
      })
  ),
  batasBerkasMenit: v.angka({ min: 0, maks: 240, bulat: true }),
  catatan: v.teks,
  pemberitahuan: v.teks,
})

const skemaPenyesuaian = z
  .object({
    tanggal: v.tanggalIso,
    jenis: z.enum(["tutup", "jam-khusus"]),
    mulai: v.jam,
    selesai: v.jam,
    keterangan: v.wajib("Keterangan"),
  })
  .superRefine((p, ctx) => {
    if (p.jenis !== "jam-khusus") return
    if (!p.mulai) ctx.addIssue({ code: "custom", path: ["mulai"], message: "Isi jam buka." })
    if (!p.selesai) ctx.addIssue({ code: "custom", path: ["selesai"], message: "Isi jam tutup." })
    if (p.mulai && p.selesai && p.selesai <= p.mulai)
      ctx.addIssue({ code: "custom", path: ["selesai"], message: "Jam tutup harus setelah jam buka." })
  })
type FormPenyesuaian = z.infer<typeof skemaPenyesuaian>

function StatusSekarang() {
  const { data: jam } = usePengaturan("jam")
  const { data: penyesuaian } = useKoleksi("penyesuaian")
  const [kini, setKini] = React.useState(() => new Date())
  React.useEffect(() => {
    const id = window.setInterval(() => setKini(new Date()), 30_000)
    return () => window.clearInterval(id)
  }, [])
  if (!jam) return null
  const s = statusLayanan(jam, penyesuaian, kini)
  return (
    <Card className="gap-1 bg-gradient-to-t from-primary/5 to-card py-4 shadow-xs">
      <CardHeader className="px-4">
        <CardDescription>Status loket sekarang · seperti tampil di portal</CardDescription>
        <CardTitle className="flex flex-wrap items-center gap-2 text-xl">
          <TitikStatus nada={s.buka ? "sukses" : "netral"}>
            <span className="text-xl font-semibold">{s.judul}</span>
          </TitikStatus>
        </CardTitle>
        <p className="text-sm text-muted-foreground">
          {s.keterangan}
          {s.khusus && ` Penyesuaian: ${s.khusus}.`}
        </p>
      </CardHeader>
    </Card>
  )
}

const k = pembantuKolom<PenyesuaianJadwal>()

function PenyesuaianJadwalBagian() {
  const { data, isLoading } = useKoleksi("penyesuaian")
  const hapus = useHapus("penyesuaian", "penyesuaian jadwal")
  const ed = useEditorEntri({
    koleksi: "penyesuaian",
    schema: skemaPenyesuaian,
    nama: "penyesuaian jadwal",
    kosong: (): FormPenyesuaian => ({ tanggal: hariIniIso(), jenis: "tutup", mulai: "08:00", selesai: "12:00", keterangan: "" }),
    dariEntri: (p): FormPenyesuaian => ({ tanggal: p.tanggal, jenis: p.jenis, mulai: p.mulai, selesai: p.selesai, keterangan: p.keterangan }),
    keEntriBaru: (f) => ({ ...f, mulai: f.jenis === "tutup" ? "" : f.mulai, selesai: f.jenis === "tutup" ? "" : f.selesai }),
    kePatch: (f) => ({ ...f, mulai: f.jenis === "tutup" ? "" : f.mulai, selesai: f.jenis === "tutup" ? "" : f.selesai }),
  })
  const jenis = ed.form.watch("jenis")
  const hariIni = hariIniIso()

  const kolom = [
    k.accessor("tanggal", {
      header: ({ column }) => <HeaderUrut column={column} judul="Tanggal" />,
      cell: ({ row }) => (
        <span className="whitespace-nowrap">
          {tanggal(row.original.tanggal, "lengkap")}
          {row.original.tanggal < hariIni && <span className="ml-2 text-xs text-muted-foreground">(lewat)</span>}
        </span>
      ),
    }),
    k.accessor("jenis", {
      header: "Perubahan",
      cell: ({ row }) =>
        row.original.jenis === "tutup" ? (
          <NadaBadge nada="peringatan">Tutup</NadaBadge>
        ) : (
          <NadaBadge nada="info">
            {jamTitik(row.original.mulai)}–{jamTitik(row.original.selesai)}
          </NadaBadge>
        ),
    }),
    k.accessor("keterangan", {
      header: "Keterangan",
      cell: ({ row }) => <span className="whitespace-normal">{row.original.keterangan}</span>,
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.keterangan}
          onSunting={() => ed.bukaUbah(row.original.id)}
          onHapus={() => void hapus([row.original.id], row.original.keterangan)}
        />
      ),
    }),
  ]

  return (
    <Bagian
      judul="Penyesuaian jadwal"
      deskripsi="Hari libur, cuti bersama, atau jam khusus. Diumumkan paling lambat H-3 dan otomatis mengubah status loket di portal pada tanggalnya."
      aksi={
        <Button size="sm" onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah
        </Button>
      }
    >
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar penyesuaian jadwal"
        pilih={false}
        sortingAwal={[{ id: "tanggal", desc: false }]}
        kosong={{ judul: "Tidak ada penyesuaian", deskripsi: "Loket mengikuti jam reguler." }}
      />
      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting penyesuaian jadwal" : "Tambah penyesuaian jadwal"}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
      >
        <FieldTanggal control={ed.form.control} name="tanggal" label="Tanggal" wajib />
        <FieldPilih
          control={ed.form.control}
          name="jenis"
          label="Jenis perubahan"
          opsi={[
            { nilai: "tutup", label: "Loket tutup" },
            { nilai: "jam-khusus", label: "Jam khusus" },
          ]}
        />
        {jenis === "jam-khusus" && (
          <Baris>
            <FieldTeks control={ed.form.control} name="mulai" label="Buka" type="time" step={300} />
            <FieldTeks control={ed.form.control} name="selesai" label="Tutup" type="time" step={300} />
          </Baris>
        )}
        <FieldTeks control={ed.form.control} name="keterangan" label="Keterangan" wajib placeholder="Mis. Cuti bersama Idulfitri" />
      </EntriSheet>
    </Bagian>
  )
}

export default function HalamanJam() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Jam loket kantor menentukan status Buka/Tutup dan hitung mundur di portal (waktu Kupang, WITA). Perbarui setiap ada perubahan dan tinjau minimal sebulan sekali." />
      <StatusSekarang />
      <FormPengaturan nama="jam" schema={skemaJam}>
        {(form) => (
          <>
            <Bagian judul="Jam loket per hari" deskripsi="Matikan sakelar untuk hari tutup.">
              <div className="overflow-hidden rounded-lg border">
                <Table aria-label="Jam pelayanan loket per hari">
                  <TableHeader className="bg-muted">
                    <TableRow>
                      <TableHead>Hari</TableHead>
                      <TableHead>Buka</TableHead>
                      <TableHead>Jam buka</TableHead>
                      <TableHead>Jam tutup</TableHead>
                      <TableHead>Catatan</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {form.getValues("hari").map((h, i) => {
                      const buka = form.watch(`hari.${i}.buka`)
                      return (
                        <TableRow key={h.hari}>
                          <TableCell className="font-medium">
                            {h.hari === 0 ? "Minggu & libur" : HARI_LABEL[h.hari]}
                          </TableCell>
                          <TableCell>
                            <FormField
                              control={form.control}
                              name={`hari.${i}.buka`}
                              render={({ field }) => (
                                <Switch
                                  checked={field.value}
                                  onCheckedChange={field.onChange}
                                  aria-label={`${HARI_LABEL[h.hari]} buka`}
                                />
                              )}
                            />
                          </TableCell>
                          {(["mulai", "selesai"] as const).map((kol) => (
                            <TableCell key={kol}>
                              <FormField
                                control={form.control}
                                name={`hari.${i}.${kol}`}
                                render={({ field }) => (
                                  <FormItem>
                                    <FormControl>
                                      <Input
                                        {...field}
                                        type="time"
                                        step={300}
                                        disabled={!buka}
                                        className="w-32"
                                        aria-label={`${kol === "mulai" ? "Jam buka" : "Jam tutup"} ${HARI_LABEL[h.hari]}`}
                                      />
                                    </FormControl>
                                    <FormMessage />
                                  </FormItem>
                                )}
                              />
                            </TableCell>
                          ))}
                          <TableCell>
                            <FormField
                              control={form.control}
                              name={`hari.${i}.catatan`}
                              render={({ field }) => (
                                <Input
                                  {...field}
                                  className="min-w-48"
                                  placeholder="Mis. Berkas s.d. 14.00"
                                  aria-label={`Catatan ${HARI_LABEL[h.hari]}`}
                                />
                              )}
                            />
                          </TableCell>
                        </TableRow>
                      )
                    })}
                  </TableBody>
                </Table>
              </div>
            </Bagian>
            <Bagian judul="Keterangan">
              <Baris>
                <FieldAngka
                  control={form.control}
                  name="batasBerkasMenit"
                  label="Penerimaan berkas ditutup"
                  satuan="menit"
                  deskripsi="Sebelum loket tutup."
                />
                <FieldTeks control={form.control} name="pemberitahuan" label="Pemberitahuan perubahan" />
              </Baris>
              <FieldTeks control={form.control} name="catatan" label="Catatan untuk warga" />
            </Bagian>
          </>
        )}
      </FormPengaturan>
      <PenyesuaianJadwalBagian />
      <p className="flex items-center gap-2 text-sm text-muted-foreground">
        <IconCalendarEvent className="size-4" aria-hidden="true" />
        Jadwal Samsat Keliling dikelola terpisah di menu Samsat Keliling.
      </p>
    </Halaman>
  )
}
