import { IconExternalLink, IconPlus } from "@tabler/icons-react"
import { Link } from "react-router"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, useHapus } from "@/components/data/row-actions"
import { NadaBadge, TitikStatus } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldAngka, FieldPilih, FieldTeks } from "@/components/form/fields"
import { Button } from "@/components/ui/button"
import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import type { ModulPantau, Pemutakhiran } from "@/lib/api"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { usePemutakhiran, type StatusPantau } from "@/lib/hitung"
import { MODUL_PANTAU } from "@/lib/meta"
import { useUrutkanEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  konten: v.wajib("Nama konten"),
  modul: z.enum(["keliling", "dokumentasi", "jam", "layanan", "berita", "dokumen", "papan", "profil"]),
  unit: v.wajib("Penanggung jawab"),
  frekuensi: v.wajib("Frekuensi"),
  intervalHari: v.angka({ min: 1, maks: 730, bulat: true }),
})
type FormPantau = z.infer<typeof schema>

type BarisPantau = Pemutakhiran & { terakhir?: string; sisa: number; status: StatusPantau }

const k = pembantuKolom<BarisPantau>()

function LabelStatus({ s }: { s: BarisPantau }) {
  if (s.status === "jatuh-tempo") return <NadaBadge nada="peringatan">Jatuh tempo {s.sisa < 0 ? `${-s.sisa} hari` : ""}</NadaBadge>
  if (s.status === "segera") return <TitikStatus nada="proses">Segera · {s.sisa} hari lagi</TitikStatus>
  return <TitikStatus nada="sukses">Terkini · {s.sisa} hari lagi</TitikStatus>
}

export default function HalamanPemutakhiran() {
  const data = usePemutakhiran()
  const urutkan = useUrutkanEntri("pemutakhiran")
  const hapus = useHapus("pemutakhiran", "standar pemutakhiran")

  const ed = useEditorEntri({
    koleksi: "pemutakhiran",
    schema,
    nama: "standar pemutakhiran",
    kosong: (): FormPantau => ({ konten: "", modul: "berita", unit: "", frekuensi: "", intervalHari: 30 }),
    dariEntri: (p): FormPantau => ({ konten: p.konten, modul: p.modul, unit: p.unit, frekuensi: p.frekuensi, intervalHari: p.intervalHari }),
    keEntriBaru: (f) => ({ ...f, modul: f.modul as ModulPantau }),
  })

  const ringkas = {
    terkini: data?.filter((d) => d.status === "terkini").length ?? 0,
    segera: data?.filter((d) => d.status === "segera").length ?? 0,
    jatuhTempo: data?.filter((d) => d.status === "jatuh-tempo").length ?? 0,
  }

  const kolom = [
    k.accessor("konten", {
      header: "Konten",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => ed.bukaUbah(row.original.id)}
          className="grid min-w-48 gap-0.5 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="font-medium underline-offset-4 hover:underline">{row.original.konten}</span>
          <span className="text-xs text-muted-foreground">{row.original.unit}</span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("frekuensi", {
      header: "Frekuensi",
      cell: ({ row }) => (
        <span className="text-sm whitespace-normal">
          {row.original.frekuensi}
          <span className="block text-xs text-muted-foreground">batas {row.original.intervalHari} hari</span>
        </span>
      ),
    }),
    k.accessor((p) => p.terakhir ?? "", {
      id: "terakhir",
      header: "Terakhir diperbarui",
      cell: ({ row }) =>
        row.original.terakhir ? (
          <time dateTime={row.original.terakhir} title={tanggalJam(row.original.terakhir)} className="text-sm whitespace-nowrap">
            {waktuRelatif(row.original.terakhir)}
          </time>
        ) : (
          <span className="text-sm text-muted-foreground">Belum pernah</span>
        ),
    }),
    k.accessor("sisa", {
      header: "Status",
      cell: ({ row }) => <LabelStatus s={row.original} />,
    }),
    k.display({
      id: "buka",
      cell: ({ row }) => (
        <Button variant="ghost" size="sm" asChild>
          <Link to={MODUL_PANTAU[row.original.modul].rute}>
            Perbarui
            <IconExternalLink />
          </Link>
        </Button>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.konten}
          onSunting={() => ed.bukaUbah(row.original.id)}
          onHapus={() => void hapus([row.original.id], row.original.konten)}
        />
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Standar frekuensi pemutakhiran sesuai SOP pengelolaan informasi. Waktu “terakhir diperbarui” dihitung otomatis dari perubahan terakhir di modul terkait.">
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah standar
        </Button>
      </PengantarHalaman>
      <div className="grid gap-4 sm:grid-cols-3">
        {[
          ["Terkini", ringkas.terkini, "Masih dalam batas frekuensi"],
          ["Segera diperbarui", ringkas.segera, "Mendekati batas waktu"],
          ["Jatuh tempo", ringkas.jatuhTempo, "Melewati batas frekuensi"],
        ].map(([judul, n, ket]) => (
          <Card key={judul as string} className="gap-1 bg-gradient-to-t from-primary/5 to-card py-4 shadow-xs">
            <CardHeader className="px-4">
              <CardDescription>{judul}</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums">{n}</CardTitle>
              <p className="text-xs text-muted-foreground">{ket}</p>
            </CardHeader>
          </Card>
        ))}
      </div>
      <DataTable
        data={data}
        memuat={!data}
        columns={kolom}
        label="Jadwal pemutakhiran konten"
        pilih={false}
        urutkan={(ids) => urutkan.mutate(ids)}
        filter={[
          {
            id: "status",
            label: "Status",
            opsi: [
              { nilai: "terkini", label: "Terkini" },
              { nilai: "segera", label: "Segera" },
              { nilai: "jatuh-tempo", label: "Jatuh tempo" },
            ],
            nilai: (p) => p.status,
          },
        ]}
        kosong={{ judul: "Belum ada standar", deskripsi: "Tambahkan standar frekuensi untuk tiap jenis konten." }}
      />
      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting standar pemutakhiran" : "Tambah standar pemutakhiran"}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
      >
        <FieldTeks control={ed.form.control} name="konten" label="Konten" wajib placeholder="Mis. Jadwal Samsat Keliling" />
        <FieldPilih
          control={ed.form.control}
          name="modul"
          label="Dipantau dari modul"
          opsi={Object.entries(MODUL_PANTAU).map(([nilai, m]) => ({ nilai, label: m.label }))}
          deskripsi="Waktu pembaruan terakhir diambil dari modul ini."
        />
        <FieldTeks control={ed.form.control} name="unit" label="Penanggung jawab" wajib placeholder="Mis. Seksi Penagihan & Pelaporan" />
        <Baris>
          <FieldTeks control={ed.form.control} name="frekuensi" label="Frekuensi (teks)" wajib placeholder="Mis. Mingguan, setiap Jumat" />
          <FieldAngka control={ed.form.control} name="intervalHari" label="Batas" satuan="hari" />
        </Baris>
      </EntriSheet>
    </Halaman>
  )
}
