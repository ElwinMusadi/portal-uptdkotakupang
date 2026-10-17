import * as React from "react"
import { IconEyeOff, IconInfoCircle, IconServer } from "@tabler/icons-react"
import { Bar, BarChart, CartesianGrid, XAxis } from "recharts"
import type { UseFormReturn } from "react-hook-form"
import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldPilih, FieldSakelar, FieldTeks } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from "@/components/ui/chart"
import { FormControl, FormField, FormItem, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import type { Statistik } from "@/lib/api"
import { angka } from "@/lib/format"
import * as v from "@/lib/validasi"

const BULAN = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"]

// Batas yang sama dengan server (UpdateStatisticsRequest)
const schema = z.object({
  status: z.enum(["ilustrasi", "terverifikasi"]),
  catatan: v.teks.max(300, "Maksimal 300 karakter."),
  tahun: z.string().regex(/^(19|20)\d{2}$/, "Tahun 4 angka."),
  set: z.array(
    z.object({
      kunci: z.string(),
      label: v.wajib("Nama tab").max(60, "Maksimal 60 karakter."),
      sub: v.teks.max(120, "Maksimal 120 karakter."),
      judulGrafik: v.wajib("Judul grafik").max(80, "Maksimal 80 karakter."),
      satuan: v.teks.max(30, "Maksimal 30 karakter."),
      tampil: z.boolean(),
      tiles: z.array(
        z.object({
          id: z.string(),
          label: v.wajib("Label").max(60, "Maksimal 60 karakter."),
          nilai: v.angka({ min: -1e12, maks: 1e12 }),
          satuan: z.string().max(12, "Maksimal 12 karakter."),
          perubahan: v.angka({ min: -1000, maks: 1000 }),
          naikBaik: z.boolean(),
        })
      ),
      seri: z.array(v.angka({ min: 0, maks: 1e12 })).length(12),
    })
  ),
})

const configGrafik = {
  nilai: { label: "Nilai", color: "var(--chart-1)" },
} satisfies ChartConfig

function IsiSet({ form, i }: { form: UseFormReturn<Statistik>; i: number }) {
  const set = form.watch(`set.${i}`)
  const dataGrafik = set.seri.map((n, b) => ({ bulan: BULAN[b], nilai: Number.isFinite(n) ? n : 0 }))
  return (
    <div className="grid gap-4">
      <FieldSakelar
        control={form.control}
        name={`set.${i}.tampil`}
        label="Tampil di portal"
        deskripsi="Bila dimatikan, tab ini tidak muncul di dasbor transparansi beranda, apa pun status datanya. Isinya tetap tersimpan di sini."
      />
      <Bagian judul="Judul">
        <Baris>
          <FieldTeks control={form.control} name={`set.${i}.label`} label="Nama tab" wajib />
          <FieldTeks control={form.control} name={`set.${i}.sub`} label="Subjudul dasbor" />
        </Baris>
        <Baris>
          <FieldTeks control={form.control} name={`set.${i}.judulGrafik`} label="Judul grafik" wajib />
          <FieldTeks control={form.control} name={`set.${i}.satuan`} label="Satuan grafik" placeholder="Mis. transaksi" />
        </Baris>
      </Bagian>
      <Bagian judul="Angka utama" deskripsi="Empat tile di atas grafik. Perubahan dalam persen terhadap periode lalu.">
        <div className="overflow-x-auto rounded-lg border">
          <Table aria-label="Angka utama">
            <TableHeader className="bg-muted">
              <TableRow>
                <TableHead>Label</TableHead>
                <TableHead>Nilai</TableHead>
                <TableHead>Satuan</TableHead>
                <TableHead>Perubahan %</TableHead>
                <TableHead>Naik = baik</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {set.tiles.map((t, j) => (
                <TableRow key={t.id}>
                  {(["label", "nilai", "satuan", "perubahan"] as const).map((kol) => (
                    <TableCell key={kol}>
                      <FormField
                        control={form.control}
                        name={`set.${i}.tiles.${j}.${kol}`}
                        render={({ field }) => (
                          <FormItem>
                            <FormControl>
                              {kol === "nilai" || kol === "perubahan" ? (
                                <Input
                                  type="number"
                                  step="any"
                                  name={field.name}
                                  ref={field.ref}
                                  onBlur={field.onBlur}
                                  value={Number.isFinite(field.value as number) ? (field.value as number) : ""}
                                  onChange={(e) =>
                                    field.onChange(e.target.value === "" ? Number.NaN : e.target.valueAsNumber)
                                  }
                                  className="w-28 tabular-nums"
                                  aria-label={`${kol === "nilai" ? "Nilai" : "Perubahan"} ${t.label}`}
                                />
                              ) : (
                                <Input
                                  {...field}
                                  value={field.value as string}
                                  className={kol === "label" ? "min-w-48" : "w-20"}
                                  aria-label={`${kol === "label" ? "Label" : "Satuan"} tile ${j + 1}`}
                                />
                              )}
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
                      name={`set.${i}.tiles.${j}.naikBaik`}
                      render={({ field }) => (
                        <Switch
                          checked={field.value}
                          onCheckedChange={field.onChange}
                          aria-label={`Kenaikan ${t.label} bermakna baik`}
                        />
                      )}
                    />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      </Bagian>
      <Bagian judul="Data bulanan" deskripsi="Dua belas nilai, Januari sampai Desember.">
        <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
          {BULAN.map((b, j) => (
            <FormField
              key={b}
              control={form.control}
              name={`set.${i}.seri.${j}`}
              render={({ field }) => (
                <FormItem>
                  <label htmlFor={`seri-${i}-${j}`} className="text-xs text-muted-foreground">
                    {b}
                  </label>
                  <FormControl>
                    <Input
                      id={`seri-${i}-${j}`}
                      type="number"
                      min={0}
                      step="any"
                      name={field.name}
                      ref={field.ref}
                      onBlur={field.onBlur}
                      value={Number.isFinite(field.value) ? field.value : ""}
                      onChange={(e) => field.onChange(e.target.value === "" ? Number.NaN : e.target.valueAsNumber)}
                      className="tabular-nums"
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          ))}
        </div>
        <ChartContainer config={configGrafik} className="aspect-auto h-55 w-full">
          <BarChart data={dataGrafik} accessibilityLayer>
            <CartesianGrid vertical={false} />
            <XAxis dataKey="bulan" tickLine={false} axisLine={false} tickMargin={8} />
            <ChartTooltip
              cursor={false}
              content={<ChartTooltipContent formatter={(n) => `${angka(Number(n), 1)} ${set.satuan}`} hideIndicator />}
            />
            <Bar dataKey="nilai" fill="var(--color-nilai)" radius={4} />
          </BarChart>
        </ChartContainer>
      </Bagian>
    </div>
  )
}

export default function HalamanStatistik() {
  const [tab, setTab] = React.useState("0")
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Angka di dasbor transparansi beranda (Transaksi, Samsat Keliling, Kepuasan). Ubah status menjadi Terverifikasi setelah angka disetujui pimpinan. Tab Penerimaan pajak dan Pemetaan PKB di beranda terisi otomatis dari Laporan Penerimaan dan Pemetaan PKB yang ditampilkan di portal." />
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Statistik ini dipakai bersama semua pengelola Statistik Layanan. Hanya statistik berstatus Terverifikasi
          pimpinan yang tampil di dasbor transparansi beranda, langsung setelah disimpan. Setiap kategori bisa
          disembunyikan dari beranda lewat sakelar Tampil di portal.
        </AlertDescription>
      </Alert>
      <FormPengaturan nama="statistik" schema={schema}>
        {(form) => {
          const status = form.watch("status")
          return (
            <>
              <Bagian judul="Status data">
                <Baris className="sm:grid-cols-3">
                  <FieldPilih
                    control={form.control}
                    name="status"
                    label="Status"
                    opsi={[
                      { nilai: "ilustrasi", label: "Ilustrasi (belum tampil di portal)" },
                      { nilai: "terverifikasi", label: "Terverifikasi pimpinan" },
                    ]}
                  />
                  <FieldTeks control={form.control} name="tahun" label="Tahun data" inputMode="numeric" maxLength={4} />
                </Baris>
                <FieldTeks control={form.control} name="catatan" label="Catatan di bawah dasbor" />
                {status === "ilustrasi" && (
                  <Alert>
                    <IconInfoCircle />
                    <AlertDescription>
                      Angka ini tidak tampil di portal selama status belum terverifikasi. Dasbor transparansi di
                      beranda menyatakan statistik layanan belum diterbitkan.
                    </AlertDescription>
                  </Alert>
                )}
              </Bagian>
              <Tabs value={tab} onValueChange={setTab} className="gap-4">
                <TabsList className="flex flex-wrap justify-start group-data-[orientation=horizontal]/tabs:h-auto *:data-[slot=tabs-trigger]:h-auto">
                  {form.getValues("set").map((s, i) => {
                    const galat = !!form.formState.errors.set?.[i]
                    const tersembunyi = !form.watch(`set.${i}.tampil`)
                    return (
                      <TabsTrigger key={s.kunci} value={String(i)}>
                        {form.watch(`set.${i}.label`) || s.kunci}
                        {tersembunyi && (
                          <>
                            <IconEyeOff aria-hidden="true" className="text-muted-foreground" />
                            <span className="sr-only">(tidak tampil di portal)</span>
                          </>
                        )}
                        {galat && (
                          <>
                            <span aria-hidden="true" className="size-1.5 rounded-full bg-destructive" />
                            <span className="sr-only">(ada isian yang perlu diperbaiki)</span>
                          </>
                        )}
                      </TabsTrigger>
                    )
                  })}
                </TabsList>
                {/* Tab yang tidak aktif tidak dirender; nilainya tetap tersimpan di formulir */}
                {form.getValues("set").map((s, i) => (
                  <TabsContent key={s.kunci} value={String(i)}>
                    <IsiSet form={form} i={i} />
                  </TabsContent>
                ))}
              </Tabs>
            </>
          )
        }}
      </FormPengaturan>
    </Halaman>
  )
}
