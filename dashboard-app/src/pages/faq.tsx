import { IconPlus } from "@tabler/icons-react"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldSakelar, FieldTeks } from "@/components/form/fields"
import { Button } from "@/components/ui/button"
import { Switch } from "@/components/ui/switch"
import type { Faq } from "@/lib/api"
import { useKoleksi, useUbahEntri, useUrutkanEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  pertanyaan: v.wajib("Pertanyaan").max(200, "Maksimal 200 karakter."),
  jawaban: v.wajib("Jawaban").max(800, "Maksimal 800 karakter."),
  tampil: z.boolean(),
})
type FormFaq = z.infer<typeof schema>

const k = pembantuKolom<Faq>()

export default function HalamanFaq() {
  const { data, isLoading } = useKoleksi("faq")
  const ubah = useUbahEntri("faq")
  const urutkan = useUrutkanEntri("faq")
  const hapus = useHapus("faq", "tanya jawab")

  const ed = useEditorEntri({
    koleksi: "faq",
    schema,
    nama: "tanya jawab",
    kosong: (): FormFaq => ({ pertanyaan: "", jawaban: "", tampil: true }),
    dariEntri: (f): FormFaq => ({ pertanyaan: f.pertanyaan, jawaban: f.jawaban, tampil: f.tampil }),
    keEntriBaru: (f) => f,
  })

  const kolom = [
    k.accessor("pertanyaan", {
      header: "Pertanyaan",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => ed.bukaUbah(row.original.id)}
          className="grid max-w-[40rem] min-w-64 gap-0.5 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="font-medium underline-offset-4 hover:underline">{row.original.pertanyaan}</span>
          <span className="line-clamp-2 text-xs text-muted-foreground">{row.original.jawaban}</span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("tampil", {
      header: "Tampil",
      cell: ({ row }) => (
        <Switch
          checked={row.original.tampil}
          aria-label={`Tampilkan “${row.original.pertanyaan}” di portal`}
          onCheckedChange={(tampil) => ubah.mutate({ id: row.original.id, patch: { tampil } })}
        />
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.pertanyaan}
          onSunting={() => ed.bukaUbah(row.original.id)}
          onHapus={() => void hapus([row.original.id], row.original.pertanyaan)}
        />
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Tanya jawab tampil di beranda sesuai urutan di bawah. Seret untuk mengubah urutan; matikan sakelar untuk menyembunyikan tanpa menghapus.">
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah tanya jawab
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar tanya jawab"
        urutkan={(ids) => urutkan.mutate(ids)}
        ukuranHalaman={20}
        cari={{ placeholder: "Cari pertanyaan atau jawaban…", teks: (f) => `${f.pertanyaan} ${f.jawaban}` }}
        filter={[
          {
            id: "tampil",
            label: "Tampil",
            opsi: [
              { nilai: "ya", label: "Ditampilkan" },
              { nilai: "tidak", label: "Disembunyikan" },
            ],
            nilai: (f) => (f.tampil ? "ya" : "tidak"),
          },
        ]}
        tindakanMassal={(baris, bersihkan) => (
          <TombolHapusMassal
            onClick={async () => {
              if (await hapus(baris.map((b) => b.id))) bersihkan()
            }}
          />
        )}
        kosong={{ judul: "Belum ada tanya jawab", deskripsi: "Tambahkan pertanyaan yang paling sering diajukan warga." }}
      />
      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting tanya jawab" : "Tambah tanya jawab"}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
      >
        <FieldTeks control={ed.form.control} name="pertanyaan" label="Pertanyaan" wajib placeholder="Mis. Apakah pajak tahunan bisa diwakilkan?" />
        <FieldArea control={ed.form.control} name="jawaban" label="Jawaban" wajib rows={6} maks={800} deskripsi="Jawab singkat, mulai dengan Bisa/Tidak bila memungkinkan." />
        <FieldSakelar control={ed.form.control} name="tampil" label="Tampilkan di portal" deskripsi="Matikan untuk menyimpan sebagai cadangan." />
      </EntriSheet>
    </Halaman>
  )
}
