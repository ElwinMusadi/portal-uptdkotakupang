import { z } from "zod"

import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldAngka, FieldArea, FieldPilih } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import type { Tarif } from "@/lib/api"
import { rupiah } from "@/lib/format"
import { hitungPkb } from "@/lib/pajak"
import * as v from "@/lib/validasi"

const schema = z.object({
  opsenPersen: v.angka({ min: 0, maks: 100 }),
  dendaPersenPerBulan: v.angka({ min: 0, maks: 10 }),
  maksBulanDenda: v.angka({ min: 0, maks: 60, bulat: true }),
  swdklljMotor: v.angka({ min: 0, bulat: true }),
  swdklljMobil: v.angka({ min: 0, bulat: true }),
  contohPokok: v.angka({ min: 0, maks: 999999999, bulat: true }),
  contohJenis: z.enum(["motor", "mobil"]),
  contohTelat: v.angka({ min: 0, maks: 60, bulat: true }),
  catatan: v.teks,
})

function Pratinjau({ t }: { t: Tarif }) {
  const h = hitungPkb(t, { pokok: t.contohPokok, jenis: t.contohJenis, telat: t.contohTelat })
  const baris: [string, number][] = [
    ["Pokok PKB", h.pokok],
    [`Opsen PKB ${t.opsenPersen}%`, h.opsen],
    [`Denda ${t.dendaPersenPerBulan}%/bulan × ${h.bulan} bulan`, h.denda],
    [`SWDKLLJ ${t.contohJenis}`, h.swd],
  ]
  return (
    <div className="rounded-xl border bg-gradient-to-t from-primary/5 to-card p-4">
      <p className="text-sm text-muted-foreground">Estimasi total bayar · nilai contoh</p>
      <p className="mt-1 font-display text-3xl font-semibold tracking-tight tabular-nums">{rupiah(h.total)}</p>
      <dl className="mt-4 grid gap-2 text-sm">
        {baris.map(([label, nilai]) => (
          <div key={label} className="flex justify-between gap-4 border-t pt-2">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="tabular-nums">{rupiah(nilai)}</dd>
          </div>
        ))}
      </dl>
    </div>
  )
}

export default function HalamanTarif() {
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Parameter simulasi pajak di beranda dan halaman Layanan. Sesuaikan dengan Perda Pajak Daerah Provinsi NTT dan ketentuan Jasa Raharja; tagihan resmi tetap mengikuti notis." />
      <FormPengaturan nama="tarif" schema={schema}>
        {(form) => {
          const nilai = form.watch()
          return (
            <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px] xl:items-start">
              <div className="grid gap-4">
                <Bagian judul="Komponen pajak">
                  <Baris>
                    <FieldAngka control={form.control} name="opsenPersen" label="Opsen PKB" satuan="%" deskripsi="Dari pokok PKB, untuk pemerintah kota." />
                    <FieldAngka control={form.control} name="dendaPersenPerBulan" label="Denda keterlambatan" satuan="%/bln" step="0.1" />
                  </Baris>
                  <FieldAngka control={form.control} name="maksBulanDenda" label="Maksimal bulan denda" satuan="bulan" className="sm:max-w-[50%]" />
                </Bagian>
                <Bagian judul="SWDKLLJ · Jasa Raharja">
                  <Baris>
                    <FieldAngka control={form.control} name="swdklljMotor" label="Sepeda motor" satuan="Rp" />
                    <FieldAngka control={form.control} name="swdklljMobil" label="Mobil" satuan="Rp" />
                  </Baris>
                </Bagian>
                <Bagian judul="Nilai contoh awal" deskripsi="Angka yang tampil saat simulasi pertama kali dibuka.">
                  <Baris className="sm:grid-cols-3">
                    <FieldAngka control={form.control} name="contohPokok" label="Pokok PKB" satuan="Rp" />
                    <FieldPilih
                      control={form.control}
                      name="contohJenis"
                      label="Jenis"
                      opsi={[
                        { nilai: "motor", label: "Motor" },
                        { nilai: "mobil", label: "Mobil" },
                      ]}
                    />
                    <FieldAngka control={form.control} name="contohTelat" label="Terlambat" satuan="bulan" />
                  </Baris>
                  <FieldArea control={form.control} name="catatan" label="Catatan di bawah simulasi" rows={2} />
                </Bagian>
              </div>
              <div className="grid gap-2 xl:sticky xl:top-4">
                <p className="text-sm font-medium">Pratinjau perhitungan</p>
                <Pratinjau t={nilai} />
              </div>
            </div>
          )
        }}
      </FormPengaturan>
    </Halaman>
  )
}
