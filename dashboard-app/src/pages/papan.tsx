import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldAngka, FieldTeks } from "@/components/form/fields"
import { DaftarObjek } from "@/components/form/list-fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { FormControl, FormField, FormItem, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import type { PapanLayanan, StatusLoket } from "@/lib/api"
import { tanggalJam } from "@/lib/format"
import { newId } from "@/lib/id"
import { STATUS_LOKET } from "@/lib/meta"
import { cn } from "@/lib/utils"
import * as v from "@/lib/validasi"

const schema = z.object({
  nomorAntrean: v.wajib("Nomor antrean"),
  layananAntrean: v.wajib("Loket & layanan"),
  waktuTunggu: v.angka({ min: 0, maks: 600, bulat: true }),
  selisihKemarin: v.angka({ min: -600, maks: 600, bulat: true }),
  loket: z.array(
    z.object({
      id: z.string(),
      kode: v.wajib("Kode"),
      nama: v.wajib("Nama loket"),
      status: z.enum(["buka", "istirahat", "tutup"]),
    })
  ),
  catatan: v.teks,
  diperbarui: z.string(),
})

/** Pratinjau papan gelap seperti di beranda portal */
function PratinjauPapan({ p }: { p: PapanLayanan }) {
  const lebih = p.selisihKemarin <= 0
  return (
    <div className="rounded-xl bg-[#0a0a0c] p-4 text-white shadow-sm" aria-label="Pratinjau papan layanan">
      <p className="text-[11px] tracking-[0.12em] text-[#9ba0a8] uppercase">PINTU · Papan layanan hari ini</p>
      <div className="mt-3 grid gap-2 sm:grid-cols-2">
        <div className="rounded-lg bg-[#17181b] p-3">
          <p className="text-xs text-[#9ba0a8]">Nomor antrean dilayani</p>
          <p className="mt-1 font-display text-3xl font-semibold tracking-tight tabular-nums">{p.nomorAntrean || "—"}</p>
          <p className="mt-1 text-xs text-[#9ba0a8]">{p.layananAntrean}</p>
        </div>
        <div className="rounded-lg bg-[#17181b] p-3">
          <p className="text-xs text-[#9ba0a8]">Perkiraan waktu tunggu</p>
          <p className="mt-1 font-display text-3xl font-semibold tracking-tight tabular-nums">
            {Number.isFinite(p.waktuTunggu) ? p.waktuTunggu : "—"}
            <span className="ml-1 text-base font-normal text-[#9ba0a8]">menit</span>
          </p>
          {Number.isFinite(p.selisihKemarin) && p.selisihKemarin !== 0 && (
            <p className={cn("mt-1 text-xs", lebih ? "text-[#5fd39b]" : "text-[#ff8f7e]")}>
              {Math.abs(p.selisihKemarin)} mnt {lebih ? "lebih cepat" : "lebih lama"} dari kemarin
            </p>
          )}
        </div>
      </div>
      <ul className="mt-2 grid gap-1.5 sm:grid-cols-2">
        {p.loket.map((l) => (
          <li key={l.id} className="flex items-center gap-2 rounded-lg bg-[#17181b] px-3 py-2 text-sm">
            <span className="w-7 font-semibold text-[#7fa4ff]">{l.kode}</span>
            <span className="flex-1 truncate">{l.nama}</span>
            <span
              className={cn(
                "text-xs",
                l.status === "buka" ? "text-[#5fd39b]" : l.status === "istirahat" ? "text-[#f2c46b]" : "text-[#9ba0a8]"
              )}
            >
              {STATUS_LOKET[l.status].label}
            </span>
          </li>
        ))}
      </ul>
      <p className="mt-3 text-xs text-[#9ba0a8]">{p.catatan}</p>
    </div>
  )
}

export default function HalamanPapan() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Papan layanan di beranda portal: nomor antrean, waktu tunggu, dan status setiap loket. Perbarui setiap hari kerja sebelum loket buka, dan bila ada perubahan selama jam layanan." />
      <FormPengaturan
        nama="papan"
        schema={schema}
        sebelumSimpan={(n) => ({ ...n, diperbarui: new Date().toISOString() })}
      >
        {(form) => {
          const nilai = form.watch()
          return (
            <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_420px] xl:items-start">
              <div className="grid gap-4">
                <Bagian judul="Antrean" deskripsi={`Terakhir diperbarui ${tanggalJam(nilai.diperbarui)}.`}>
                  <Baris>
                    <FieldTeks control={form.control} name="nomorAntrean" label="Nomor antrean dilayani" wajib className="[&_input]:font-mono" />
                    <FieldTeks control={form.control} name="layananAntrean" label="Loket & layanan" wajib />
                  </Baris>
                  <Baris>
                    <FieldAngka control={form.control} name="waktuTunggu" label="Perkiraan waktu tunggu" satuan="menit" />
                    <FieldAngka
                      control={form.control}
                      name="selisihKemarin"
                      label="Selisih dari kemarin"
                      satuan="menit"
                      deskripsi="Negatif = lebih cepat dari kemarin."
                    />
                  </Baris>
                </Bagian>
                <Bagian judul="Status loket">
                  <DaftarObjek
                    control={form.control}
                    name="loket"
                    label="Loket"
                    labelTambah="Tambah loket"
                    labelItem={(i) => `loket ${i + 1}`}
                    itemBaru={() => ({ id: newId(), kode: `L${nilai.loket.length + 1}`, nama: "", status: "buka" as StatusLoket })}
                    render={(i) => (
                      <div className="grid gap-2 sm:grid-cols-[5rem_minmax(0,1fr)_10rem]">
                        <FormField
                          control={form.control}
                          name={`loket.${i}.kode`}
                          render={({ field }) => (
                            <FormItem>
                              <FormControl>
                                <Input {...field} aria-label={`Kode loket ${i + 1}`} className="font-mono" />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name={`loket.${i}.nama`}
                          render={({ field }) => (
                            <FormItem>
                              <FormControl>
                                <Input {...field} placeholder="Nama loket" aria-label={`Nama loket ${i + 1}`} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />
                        <FormField
                          control={form.control}
                          name={`loket.${i}.status`}
                          render={({ field }) => (
                            <Select value={field.value} onValueChange={field.onChange}>
                              <SelectTrigger className="w-full" aria-label={`Status loket ${i + 1}`}>
                                <SelectValue />
                              </SelectTrigger>
                              <SelectContent>
                                {(Object.keys(STATUS_LOKET) as StatusLoket[]).map((s) => (
                                  <SelectItem key={s} value={s}>
                                    {STATUS_LOKET[s].label}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          )}
                        />
                      </div>
                    )}
                  />
                  <FieldTeks control={form.control} name="catatan" label="Catatan di bawah papan" />
                </Bagian>
              </div>
              <div className="grid gap-2 xl:sticky xl:top-4">
                <p className="text-sm font-medium">Pratinjau</p>
                <PratinjauPapan p={nilai} />
                <p className="text-xs text-muted-foreground">Menyimpan perubahan otomatis memperbarui waktu “Diperbarui”.</p>
              </div>
            </div>
          )
        }}
      </FormPengaturan>
    </Halaman>
  )
}
