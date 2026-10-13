import { IconPlus, IconRefresh, IconServer } from "@tabler/icons-react"
import { useWatch, type Control } from "react-hook-form"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { TitikStatus } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldSakelar, FieldTeks } from "@/components/form/fields"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Switch } from "@/components/ui/switch"
import type { Faq, StatusFaq } from "@/lib/api"
import { sesi } from "@/lib/auth"
import { STATUS_FAQ, keadaanPortalFaq } from "@/lib/meta"
import { useKoleksi, useUbahEntri, useUrutkanEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  pertanyaan: v.wajib("Pertanyaan").max(200, "Maksimal 200 karakter."),
  jawaban: v.wajib("Jawaban").max(800, "Maksimal 800 karakter."),
  status: z.enum(["draft", "published", "unpublished"], { error: "Pilih status." }),
  publik: z.boolean(),
  tampil: z.boolean(),
})
type FormFaq = z.infer<typeof schema>

const OPSI_STATUS = (Object.keys(STATUS_FAQ) as StatusFaq[]).map((s) => ({ nilai: s, label: STATUS_FAQ[s].label }))

const k = pembantuKolom<Faq>()

/** Penjelasan langsung di formulir: apakah isian ini membuat tanya jawab tampil di portal */
function KeteranganPortal({ control }: { control: Control<FormFaq> }) {
  const [status, publik, tampil] = useWatch({ control, name: ["status", "publik", "tampil"] })
  const keadaan = keadaanPortalFaq({ status, publik, tampil })
  return (
    <p aria-live="polite">
      <TitikStatus nada={keadaan.nada}>{keadaan.keterangan}</TitikStatus>
    </p>
  )
}

export default function HalamanFaq() {
  const { data, isLoading, error, refetch, isRefetching } = useKoleksi("faq")
  const ubah = useUbahEntri("faq")
  const urutkan = useUrutkanEntri("faq")
  const hapus = useHapus("faq", "tanya jawab")
  // Server hanya mengizinkan Administrator menghapus (D-011); pegawai menyembunyikan dengan sakelar.
  const bolehHapus = sesi().peran === "administrator"

  const ed = useEditorEntri({
    koleksi: "faq",
    schema,
    nama: "tanya jawab",
    kosong: (): FormFaq => ({ pertanyaan: "", jawaban: "", status: "draft", publik: false, tampil: true }),
    dariEntri: (f): FormFaq => ({
      pertanyaan: f.pertanyaan,
      jawaban: f.jawaban,
      status: f.status,
      publik: f.publik,
      tampil: f.tampil,
    }),
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
    k.accessor("status", {
      header: "Status",
      cell: ({ row }) => {
        const keadaan = keadaanPortalFaq(row.original)
        return (
          <span className="whitespace-nowrap">
            <TitikStatus nada={keadaan.nada}>{keadaan.label}</TitikStatus>
          </span>
        )
      },
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
          onHapus={bolehHapus ? () => void hapus([row.original.id], row.original.pertanyaan) : undefined}
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
      <Alert>
        <IconServer />
        <AlertTitle>Tersimpan di server</AlertTitle>
        <AlertDescription>
          Daftar ini dipakai bersama semua pengelola Tanya Jawab. Entri berstatus Di portal (terbit, untuk
          warga, dan ditampilkan) sudah tersedia lewat API publik, tetapi beranda portal masih menampilkan
          tanya jawab lama sampai disambungkan ke API tersebut.
        </AlertDescription>
      </Alert>
      {error ? (
        <Alert variant="destructive">
          <AlertTitle>Daftar tanya jawab gagal dimuat</AlertTitle>
          <AlertDescription>
            <p>{error instanceof Error ? error.message : String(error)}</p>
            <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()} disabled={isRefetching}>
              <IconRefresh />
              Coba lagi
            </Button>
          </AlertDescription>
        </Alert>
      ) : (
        <DataTable
          data={data}
          memuat={isLoading}
          columns={kolom}
          label="Daftar tanya jawab"
          urutkan={(ids) => urutkan.mutate(ids)}
          ukuranHalaman={20}
          pilih={bolehHapus}
          cari={{ placeholder: "Cari pertanyaan atau jawaban…", teks: (f) => `${f.pertanyaan} ${f.jawaban}` }}
          filter={[
            {
              id: "status",
              label: "Status",
              opsi: OPSI_STATUS,
              nilai: (f) => f.status,
            },
            {
              id: "portal",
              label: "Portal",
              opsi: [
                { nilai: "ya", label: "Di portal" },
                { nilai: "tidak", label: "Tidak di portal" },
              ],
              nilai: (f) => (keadaanPortalFaq(f).diPortal ? "ya" : "tidak"),
            },
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
          tindakanMassal={
            bolehHapus
              ? (baris, bersihkan) => (
                  <TombolHapusMassal
                    onClick={async () => {
                      if (await hapus(baris.map((b) => b.id))) bersihkan()
                    }}
                  />
                )
              : undefined
          }
          kosong={{
            judul: "Belum ada tanya jawab",
            deskripsi:
              "Tanya jawab contoh dari penyimpanan peramban tidak dipindahkan ke server. Tambahkan pertanyaan yang paling sering diajukan warga.",
          }}
        />
      )}
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
        <FieldPilih
          control={ed.form.control}
          name="status"
          label="Status"
          wajib
          opsi={OPSI_STATUS}
          deskripsi="Hanya tanya jawab yang terbit bisa tampil di portal. Pilih Ditarik untuk menurunkannya."
        />
        <FieldSakelar
          control={ed.form.control}
          name="publik"
          label="Untuk warga"
          deskripsi="Nyalakan bila isinya memang ditujukan untuk warga, bukan catatan internal."
        />
        <FieldSakelar control={ed.form.control} name="tampil" label="Tampilkan di portal" deskripsi="Matikan untuk menyimpan sebagai cadangan." />
        <KeteranganPortal control={ed.form.control} />
      </EntriSheet>
    </Halaman>
  )
}
