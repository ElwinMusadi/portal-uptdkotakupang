import { IconPhoto, IconPlus } from "@tabler/icons-react"
import { z } from "zod"

import { MenuAksiAlur, PanelAlur, RiwayatAlur, useAksiAlur } from "@/components/alur/alur"
import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman, Baris } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { StatusAlurBadge } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldTanggal, FieldTeks } from "@/components/form/fields"
import { PlaceholderFoto } from "@/components/form/media-fields"
import { FieldFoto } from "@/components/form/photo-field"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { DropdownMenuSeparator } from "@/components/ui/dropdown-menu"
import type { Dokumentasi } from "@/lib/api"
import { alurBaru } from "@/lib/alur"
import { hariIniIso, tanggal } from "@/lib/format"
import { KATEGORI_DOKUMENTASI, PEMERIKSA, STATUS_ALUR, UNIT_PENYEDIA } from "@/lib/meta"
import { useKoleksi, usePengaturan } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  judul: v.wajib("Nama kegiatan"),
  kategori: z.enum(["keliling", "sosialisasi", "operasi", "internal"]),
  lokasi: v.wajib("Lokasi"),
  tanggal: v.tanggalIso,
  deskripsi: v.teks,
  foto: z.array(z.object({ id: z.string(), src: z.string().nullable(), keterangan: z.string() })),
  penyedia: v.wajib("Unit penyedia"),
  pemeriksa: v.teks,
})
type FormDok = z.infer<typeof schema>

const k = pembantuKolom<Dokumentasi>()

export default function HalamanDokumentasi() {
  const { data, isLoading } = useKoleksi("dokumentasi")
  const { data: akun } = usePengaturan("akun")
  const hapus = useHapus("dokumentasi", "kegiatan")
  const { jalankan, dialog, memproses } = useAksiAlur("dokumentasi")

  const ed = useEditorEntri({
    koleksi: "dokumentasi",
    schema,
    nama: "kegiatan",
    kosong: (): FormDok => ({
      judul: "",
      kategori: "keliling",
      lokasi: "",
      tanggal: hariIniIso(),
      deskripsi: "",
      foto: [],
      penyedia: UNIT_PENYEDIA[0],
      pemeriksa: PEMERIKSA[0],
    }),
    dariEntri: (d): FormDok => ({
      judul: d.judul,
      kategori: d.kategori,
      lokasi: d.lokasi,
      tanggal: d.tanggal,
      deskripsi: d.deskripsi,
      foto: d.foto,
      penyedia: d.penyedia,
      pemeriksa: d.pemeriksa,
    }),
    keEntriBaru: (f) => ({ ...alurBaru(akun?.nama ?? "Pengelola PINTU", f.penyedia, f.pemeriksa), ...f }),
  })

  const kolom = [
    k.accessor("judul", {
      header: "Kegiatan",
      cell: ({ row }) => {
        const d = row.original
        const sampul = d.foto[0]?.src
        return (
          <button
            type="button"
            onClick={() => ed.bukaUbah(d.id)}
            className="flex min-w-64 items-center gap-3 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            <span className="block size-12 shrink-0 overflow-hidden rounded-md border bg-muted">
              {sampul ? <img src={sampul} alt="" className="size-full object-cover" /> : <PlaceholderFoto className="[&_svg]:size-4" />}
            </span>
            <span className="grid gap-0.5">
              <span className="font-medium underline-offset-4 hover:underline">{d.judul}</span>
              <span className="text-xs text-muted-foreground">{d.lokasi}</span>
            </span>
          </button>
        )
      },
      enableHiding: false,
    }),
    k.accessor("kategori", {
      header: "Kategori",
      cell: ({ row }) => (
        <Badge variant="outline" className="px-1.5 text-muted-foreground">
          {KATEGORI_DOKUMENTASI[row.original.kategori]}
        </Badge>
      ),
    }),
    k.accessor("tanggal", {
      header: ({ column }) => <HeaderUrut column={column} judul="Tanggal" />,
      cell: ({ row }) => <span className="whitespace-nowrap">{tanggal(row.original.tanggal)}</span>,
    }),
    k.accessor((d) => d.foto.length, {
      id: "foto",
      header: "Foto",
      cell: ({ row }) => (
        <span className="inline-flex items-center gap-1 text-sm text-muted-foreground tabular-nums">
          <IconPhoto className="size-4" aria-hidden="true" />
          {row.original.foto.length}
        </span>
      ),
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
          onHapus={() => void hapus([row.original.id], row.original.judul)}
        >
          <DropdownMenuSeparator />
          <MenuAksiAlur item={row.original} jalankan={(i, a) => void jalankan(i, a)} />
        </MenuBaris>
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Foto kegiatan pelayanan, sosialisasi, dan operasi gabungan untuk halaman Dokumentasi. Gunakan foto resmi yang sudah disetujui.">
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah kegiatan
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar dokumentasi kegiatan"
        sortingAwal={[{ id: "tanggal", desc: true }]}
        cari={{ placeholder: "Cari kegiatan atau lokasi…", teks: (d) => `${d.judul} ${d.lokasi} ${d.deskripsi}` }}
        filter={[
          {
            id: "kategori",
            label: "Kategori",
            opsi: Object.entries(KATEGORI_DOKUMENTASI).map(([nilai, label]) => ({ nilai, label })),
            nilai: (d) => d.kategori,
          },
          {
            id: "status",
            label: "Status",
            opsi: Object.entries(STATUS_ALUR).map(([nilai, s]) => ({ nilai, label: s.label })),
            nilai: (d) => d.status,
          },
        ]}
        tindakanMassal={(baris, bersihkan) => (
          <TombolHapusMassal
            onClick={async () => {
              if (await hapus(baris.map((b) => b.id))) bersihkan()
            }}
          />
        )}
        kosong={{
          judul: "Belum ada dokumentasi",
          deskripsi: "Tambahkan foto kegiatan pertama.",
          tindakan: (
            <Button size="sm" onClick={ed.bukaBaru}>
              <IconPlus />
              Tambah kegiatan
            </Button>
          ),
        }}
      />

      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting dokumentasi" : "Tambah dokumentasi kegiatan"}
        deskripsi="Kegiatan baru tersimpan sebagai draf dan tayang setelah disetujui."
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
        lebar="sm:max-w-2xl"
      >
        <FieldTeks control={ed.form.control} name="judul" label="Nama kegiatan" wajib placeholder="Mis. Samsat Keliling di Kelurahan Oesapa." />
        <Baris>
          <FieldPilih
            control={ed.form.control}
            name="kategori"
            label="Kategori"
            opsi={Object.entries(KATEGORI_DOKUMENTASI).map(([nilai, label]) => ({ nilai, label }))}
          />
          <FieldTanggal control={ed.form.control} name="tanggal" label="Tanggal kegiatan" wajib />
        </Baris>
        <FieldTeks control={ed.form.control} name="lokasi" label="Lokasi" wajib placeholder="Mis. Kec. Kelapa Lima" />
        <FieldArea control={ed.form.control} name="deskripsi" label="Deskripsi singkat" rows={3} />
        <FieldFoto control={ed.form.control} name="foto" />
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
        {ed.entri && (
          <>
            <PanelAlur
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
