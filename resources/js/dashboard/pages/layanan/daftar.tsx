import { IconPlus, IconStarFilled } from "@tabler/icons-react"
import { Link, useNavigate } from "react-router"

import { MenuAksiAlur, useAksiAlur } from "@/components/alur/alur"
import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { StatusAlurBadge } from "@/components/data/status-badge"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { DropdownMenuSeparator } from "@/components/ui/dropdown-menu"
import type { Layanan } from "@/lib/api"
import { STATUS_ALUR } from "@/lib/meta"
import { useKoleksi, useUrutkanEntri } from "@/lib/queries"

const k = pembantuKolom<Layanan>()

export default function DaftarLayanan() {
  const navigate = useNavigate()
  const { data, isLoading } = useKoleksi("layanan")
  const urutkan = useUrutkanEntri("layanan")
  const hapus = useHapus("layanan", "layanan")
  const { jalankan, dialog } = useAksiAlur("layanan")

  const kolom = [
    k.accessor("nama", {
      header: "Layanan",
      cell: ({ row }) => (
        <div className="grid max-w-[28rem] min-w-56 gap-0.5 whitespace-normal">
          <Link to={`/layanan/${row.original.id}`} className="font-medium underline-offset-4 hover:underline">
            {row.original.nama}
            {row.original.jenis === "tambahan" && (
              <span className="ml-1.5 text-xs font-normal text-muted-foreground">(lampiran)</span>
            )}
          </Link>
          <span className="text-xs text-muted-foreground">{row.original.ringkasan}</span>
        </div>
      ),
      enableHiding: false,
    }),
    k.accessor("loket", { header: "Loket" }),
    k.accessor((l) => l.persyaratan.length, {
      id: "persyaratan",
      header: "Berkas",
      cell: ({ row }) => <span className="tabular-nums">{row.original.persyaratan.length} item</span>,
    }),
    k.accessor((l) => `${l.cekFisik} ${l.nonTunai}`, {
      id: "ketentuan",
      header: "Ketentuan",
      cell: ({ row }) => (
        <div className="flex flex-wrap gap-1">
          <Badge variant="outline" className="px-1.5 text-muted-foreground">
            {row.original.cekFisik ? "Wajib cek fisik" : "Tanpa cek fisik"}
          </Badge>
          {row.original.nonTunai && (
            <Badge variant="outline" className="px-1.5 text-muted-foreground">
              Bisa non-tunai
            </Badge>
          )}
        </div>
      ),
    }),
    k.accessor("unggulan", {
      header: "Beranda",
      cell: ({ row }) =>
        row.original.unggulan ? (
          <span className="inline-flex items-center gap-1 text-sm">
            <IconStarFilled className="size-3.5 text-warning" aria-hidden="true" />
            Tab beranda
          </span>
        ) : (
          <span className="text-sm text-muted-foreground">—</span>
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
          label={row.original.nama}
          onSunting={() => navigate(`/layanan/${row.original.id}`)}
          onHapus={() => void hapus([row.original.id], row.original.nama)}
        >
          <DropdownMenuSeparator />
          <MenuAksiAlur item={row.original} jalankan={(i, a) => void jalankan(i, a)} />
        </MenuBaris>
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Daftar berkas setiap layanan sama dengan papan persyaratan di loket. Urutan di sini menjadi urutan di halaman Layanan; layanan bertanda bintang tampil sebagai tab di beranda.">
        <Button asChild>
          <Link to="/layanan/baru">
            <IconPlus />
            Tambah layanan
          </Link>
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar layanan dan persyaratan"
        urutkan={(ids) => urutkan.mutate(ids)}
        cari={{
          placeholder: "Cari layanan atau berkas…",
          teks: (l) => `${l.nama} ${l.ringkasan} ${l.persyaratan.map((p) => p.dokumen).join(" ")}`,
        }}
        filter={[
          {
            id: "status",
            label: "Status",
            opsi: Object.entries(STATUS_ALUR).map(([nilai, s]) => ({ nilai, label: s.label })),
            nilai: (l) => l.status,
          },
        ]}
        tindakanMassal={(baris, bersihkan) => (
          <TombolHapusMassal
            onClick={async () => {
              if (await hapus(baris.map((b) => b.id))) bersihkan()
            }}
          />
        )}
        kosong={{ judul: "Belum ada layanan", deskripsi: "Tambahkan layanan dan daftar persyaratannya." }}
      />
      {dialog}
    </Halaman>
  )
}
