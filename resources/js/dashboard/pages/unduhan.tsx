import * as React from "react"
import { IconAlertTriangle, IconExternalLink, IconFileDescription, IconLock, IconPaperclip, IconPlus } from "@tabler/icons-react"
import { z } from "zod"

import { MenuAksiAlur, PanelAlur, RiwayatAlur, useAksiAlur } from "@/components/alur/alur"
import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { StatusAlurBadge } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldTeks } from "@/components/form/fields"
import { FieldBerkas } from "@/components/form/media-fields"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { DropdownMenuSeparator } from "@/components/ui/dropdown-menu"
import type { Dokumen, FormatDokumen } from "@/lib/api"
import { alurBaru, bolehUbahAlur, khususAdministrator } from "@/lib/alur"
import { sesi } from "@/lib/auth"
import { ukuranBerkas } from "@/lib/format"
import { KATEGORI_DOKUMEN, PEMERIKSA, STATUS_ALUR, UNIT_PENYEDIA } from "@/lib/meta"
import { useKoleksi } from "@/lib/queries"
import * as v from "@/lib/validasi"

const FORMAT: FormatDokumen[] = ["PDF", "DOCX", "XLSX", "JPG", "Tautan"]

const schema = z
  .object({
    judul: v.wajib("Judul dokumen").max(200, "Judul dokumen maksimal 200 karakter."),
    keterangan: v.teks.max(300, "Keterangan maksimal 300 karakter."),
    kategori: z.enum(["regulasi", "standar", "formulir", "laporan"]),
    format: z.enum(["PDF", "DOCX", "XLSX", "JPG", "Tautan"]),
    nomor: v.teks.max(60, "Nomor maksimal 60 karakter."),
    tahun: z.string().trim().refine((t) => t === "" || /^(19|20)\d{2}$/.test(t), "Tahun 4 angka, mis. 2026."),
    berkas: v.berkas,
    // Portal membuka tautan di tab baru, jadi hanya alamat web lengkap (D-030)
    tautan: z
      .string()
      .trim()
      .max(500, "Tautan maksimal 500 karakter.")
      .refine((t) => t === "" || /^https?:\/\/\S+$/i.test(t), "Gunakan alamat lengkap yang diawali http:// atau https://."),
    penyedia: v.wajib("Unit penyedia"),
    pemeriksa: v.teks,
  })
  .refine((d) => d.format !== "Tautan" || d.tautan.trim() !== "", {
    path: ["tautan"],
    message: "Isi alamat tautan untuk dokumen berformat Tautan.",
  })
type FormDokumen = z.infer<typeof schema>

/** Format menurut jenis berkas yang dikenali server, lalu menurut ekstensinya; sama dengan server (D-030) */
function formatDariBerkas(nama: string, tipe: string): FormatDokumen {
  if (tipe === "application/pdf") return "PDF"
  if (tipe === "application/vnd.openxmlformats-officedocument.wordprocessingml.document") return "DOCX"
  if (tipe === "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") return "XLSX"
  if (tipe.startsWith("image/")) return "JPG"
  const ext = nama.split(".").pop()?.toLowerCase() ?? ""
  if (ext === "pdf") return "PDF"
  if (ext === "doc" || ext === "docx") return "DOCX"
  if (ext === "xls" || ext === "xlsx") return "XLSX"
  return "PDF"
}

const k = pembantuKolom<Dokumen>()

export default function HalamanUnduhan() {
  const { data, isLoading } = useKoleksi("dokumen")
  const hapus = useHapus("dokumen", "dokumen")
  const { jalankan, dialog, memproses } = useAksiAlur("dokumen")
  // Penghapusan hanya untuk Administrator (D-030)
  const khususAdmin = khususAdministrator("dokumen")

  const ed = useEditorEntri({
    koleksi: "dokumen",
    schema,
    nama: "dokumen",
    kosong: (): FormDokumen => ({
      judul: "",
      keterangan: "",
      kategori: "regulasi",
      format: "PDF",
      nomor: "",
      tahun: String(new Date().getFullYear()),
      berkas: null,
      tautan: "",
      penyedia: UNIT_PENYEDIA[0],
      pemeriksa: PEMERIKSA[0],
    }),
    dariEntri: (d): FormDokumen => ({
      judul: d.judul,
      keterangan: d.keterangan,
      kategori: d.kategori,
      format: d.format,
      nomor: d.nomor,
      tahun: d.tahun,
      berkas: d.berkas,
      tautan: d.tautan,
      penyedia: d.penyedia,
      pemeriksa: d.pemeriksa,
    }),
    keEntriBaru: (f) => ({ ...alurBaru(sesi().nama, f.penyedia, f.pemeriksa), ...f }),
  })

  // Dokumen yang menunggu persetujuan atau sudah terbit hanya diubah Administrator (D-030)
  const terkunci = !!ed.entri && !bolehUbahAlur("dokumen", ed.entri.status)

  // format mengikuti berkas yang diunggah
  const berkas = ed.form.watch("berkas")
  React.useEffect(() => {
    if (!berkas) return
    const f = formatDariBerkas(berkas.nama, berkas.tipe)
    if (f !== ed.form.getValues("format")) ed.form.setValue("format", f, { shouldDirty: true })
  }, [berkas, ed.form])

  const kolom = [
    k.accessor("judul", {
      header: "Dokumen",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => ed.bukaUbah(row.original.id)}
          className="flex min-w-64 items-start gap-3 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="mt-0.5 flex h-8 w-9 shrink-0 items-center justify-center rounded-md border bg-muted text-[0.625rem] font-semibold tracking-wide text-muted-foreground">
            {row.original.format === "Tautan" ? <IconExternalLink className="size-4" /> : row.original.format}
          </span>
          <span className="grid max-w-[28rem] gap-0.5">
            <span className="font-medium underline-offset-4 hover:underline">{row.original.judul}</span>
            <span className="text-xs text-muted-foreground">{row.original.keterangan}</span>
          </span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("kategori", {
      header: "Kategori",
      cell: ({ row }) => (
        <Badge variant="outline" className="px-1.5 text-muted-foreground">
          {KATEGORI_DOKUMEN[row.original.kategori]}
        </Badge>
      ),
    }),
    k.accessor((d) => d.berkas?.nama ?? d.tautan, {
      id: "berkas",
      header: "Berkas",
      cell: ({ row }) => {
        const d = row.original
        if (d.berkas)
          return (
            <span className="inline-flex items-center gap-1 text-sm text-muted-foreground">
              <IconPaperclip className="size-4" aria-hidden="true" />
              {ukuranBerkas(d.berkas.ukuran)}
            </span>
          )
        if (d.tautan)
          return (
            <a href={d.tautan} target="_blank" rel="noopener" className="inline-flex items-center gap-1 text-sm text-brand underline-offset-4 hover:underline">
              <IconExternalLink className="size-4" aria-hidden="true" />
              Tautan
            </a>
          )
        return (
          <span className="inline-flex items-center gap-1 text-sm text-warning">
            <IconAlertTriangle className="size-4" aria-hidden="true" />
            Belum ada berkas
          </span>
        )
      },
    }),
    k.accessor("tahun", {
      header: "Tahun",
      cell: ({ row }) => <span className="tabular-nums">{row.original.tahun || "—"}</span>,
    }),
    k.accessor("status", {
      header: "Status",
      cell: ({ row }) => <StatusAlurBadge status={row.original.status} />,
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.judul}
          onSunting={() => ed.bukaUbah(row.original.id)}
          onHapus={khususAdmin ? undefined : () => void hapus([row.original.id], row.original.judul)}
        >
          <DropdownMenuSeparator />
          <MenuAksiAlur koleksi="dokumen" item={row.original} jalankan={(i, a) => void jalankan(i, a)} />
        </MenuBaris>
      ),
    }),
  ]

  const jumlahTanpaBerkas = data?.filter((d) => !d.berkas && !d.tautan).length ?? 0

  return (
    <Halaman>
      <PengantarHalaman
        deskripsi={
          <>
            Regulasi, standar pelayanan, formulir, dan laporan untuk halaman Regulasi & Unduhan.
            {jumlahTanpaBerkas > 0 && (
              <span className="mt-1 flex items-center gap-1 text-warning">
                <IconFileDescription className="size-4" aria-hidden="true" />
                {jumlahTanpaBerkas} dokumen belum memiliki berkas atau tautan.
              </span>
            )}
          </>
        }
      >
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah dokumen
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar dokumen unduhan"
        cari={{ placeholder: "Cari judul, nomor, atau tahun…", teks: (d) => `${d.judul} ${d.keterangan} ${d.nomor} ${d.tahun}` }}
        filter={[
          {
            id: "kategori",
            label: "Kategori",
            opsi: Object.entries(KATEGORI_DOKUMEN).map(([nilai, label]) => ({ nilai, label })),
            nilai: (d) => d.kategori,
          },
          {
            id: "status",
            label: "Status",
            opsi: Object.entries(STATUS_ALUR).map(([nilai, s]) => ({ nilai, label: s.label })),
            nilai: (d) => d.status,
          },
        ]}
        pilih={!khususAdmin}
        tindakanMassal={
          khususAdmin
            ? undefined
            : (baris, bersihkan) => (
                <TombolHapusMassal
                  onClick={async () => {
                    if (await hapus(baris.map((b) => b.id))) bersihkan()
                  }}
                />
              )
        }
        kosong={{ judul: "Belum ada dokumen", deskripsi: "Unggah regulasi atau formulir pertama." }}
      />

      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting dokumen" : "Tambah dokumen"}
        deskripsi="Dokumen resmi dari JDIH atau unit terkait. Format mengikuti berkas yang diunggah."
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
        hanyaBaca={terkunci}
      >
        {terkunci && (
          <Alert>
            <IconLock />
            <AlertTitle>Hanya Administrator yang dapat mengubah dokumen ini</AlertTitle>
            <AlertDescription>
              Dokumen ini {ed.entri?.status === "terbit" ? "sudah terbit di portal" : "menunggu persetujuan"}. Isinya dan
              langkah berikutnya dijalankan Administrator.
            </AlertDescription>
          </Alert>
        )}
        <fieldset disabled={terkunci} className="grid min-w-0 gap-5">
          <FieldTeks control={ed.form.control} name="judul" label="Judul dokumen" wajib />
          <FieldArea control={ed.form.control} name="keterangan" label="Keterangan singkat" rows={2} />
          <Baris>
            <FieldPilih
              control={ed.form.control}
              name="kategori"
              label="Kategori"
              opsi={Object.entries(KATEGORI_DOKUMEN).map(([nilai, label]) => ({ nilai, label }))}
            />
            <FieldPilih
              control={ed.form.control}
              name="format"
              label="Format"
              opsi={FORMAT.map((f) => ({ nilai: f, label: f }))}
            />
          </Baris>
          <Baris>
            <FieldTeks control={ed.form.control} name="nomor" label="Nomor" placeholder="Mis. 1" />
            <FieldTeks control={ed.form.control} name="tahun" label="Tahun" inputMode="numeric" maxLength={4} />
          </Baris>
          <FieldBerkas
            control={ed.form.control}
            name="berkas"
            label="Berkas"
            tujuan="dokumen"
            terima=".pdf,.docx,.xlsx,.jpg,.jpeg,.png,.webp"
            petunjuk="PDF, Word (DOCX), atau Excel (XLSX) maks. 10 MB · gambar maks. 5 MB"
          />
          <FieldTeks
            control={ed.form.control}
            name="tautan"
            label="Atau tautan luar"
            type="url"
            placeholder="https://jdih.nttprov.go.id/…"
            deskripsi="Untuk dokumen yang sudah tersedia di JDIH atau situs resmi lain. Dokumen terbit harus memiliki berkas atau tautan."
          />
          <Baris>
            <FieldPilih
              control={ed.form.control}
              name="penyedia"
              label="Unit penyedia"
              opsi={UNIT_PENYEDIA.map((u) => ({ nilai: u, label: u }))}
            />
            <FieldPilih
              control={ed.form.control}
              name="pemeriksa"
              label="Pemeriksa"
              opsi={PEMERIKSA.map((u) => ({ nilai: u, label: u }))}
            />
          </Baris>
        </fieldset>
        {ed.entri && (
          <>
            <PanelAlur
              koleksi="dokumen"
              item={ed.entri}
              jalankan={jalankan}
              memproses={memproses || ed.menyimpan}
              sebelumAksi={async () => (ed.form.formState.isDirty ? ed.simpanTetapBuka() : true)}
            />
            <div className="grid gap-2">
              <p className="text-sm font-medium">Riwayat</p>
              <RiwayatAlur riwayat={ed.entri.riwayat} batas={4} />
            </div>
          </>
        )}
      </EntriSheet>
      {dialog}
    </Halaman>
  )
}
