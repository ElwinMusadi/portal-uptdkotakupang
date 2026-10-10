import { IconExternalLink, IconPlus } from "@tabler/icons-react"
import { Link, useNavigate } from "react-router"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, useHapus } from "@/components/data/row-actions"
import { Button } from "@/components/ui/button"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import type { Halaman as HalamanStatis } from "@/lib/api"
import { menitBaca, waktuRelatif } from "@/lib/format"
import { urlPortal } from "@/lib/portal"
import { useKoleksi } from "@/lib/queries"

const k = pembantuKolom<HalamanStatis>()

export default function DaftarHalaman() {
  const navigate = useNavigate()
  const { data, isLoading } = useKoleksi("halaman")
  const hapus = useHapus("halaman", "halaman")

  const kolom = [
    k.accessor("judul", {
      header: "Halaman",
      cell: ({ row }) => (
        <div className="grid min-w-56 gap-0.5 whitespace-normal">
          <Link to={`/halaman/${row.original.id}`} className="font-medium underline-offset-4 hover:underline">
            {row.original.judul} <span className="text-muted-foreground">{row.original.judulLanjutan}</span>
          </Link>
          <span className="font-mono text-xs text-muted-foreground">{row.original.slug}.html</span>
        </div>
      ),
      enableHiding: false,
    }),
    k.accessor("pengantar", {
      header: "Keterangan",
      cell: ({ row }) => <span className="text-sm text-muted-foreground">{row.original.pengantar}</span>,
    }),
    k.accessor((h) => menitBaca(h.isi), {
      id: "baca",
      header: "Panjang",
      cell: ({ getValue }) => <span className="text-sm tabular-nums">± {getValue() as number} mnt baca</span>,
    }),
    k.accessor("diperbarui", {
      header: "Diperbarui",
      cell: ({ row }) => (
        <span className="text-sm whitespace-nowrap text-muted-foreground">{waktuRelatif(row.original.diperbarui)}</span>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.judul}
          onSunting={() => navigate(`/halaman/${row.original.id}`)}
          onHapus={() => void hapus([row.original.id], row.original.judul)}
        >
          <DropdownMenuItem asChild>
            <a href={urlPortal(`${row.original.slug}.html`)} target="_blank" rel="noopener">
              <IconExternalLink />
              Lihat di portal
            </a>
          </DropdownMenuItem>
        </MenuBaris>
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Halaman berisi teks panjang seperti Kebijakan Privasi dan Syarat & Ketentuan. Wajib ditinjau unit hukum sebelum diterbitkan.">
        <Button asChild>
          <Link to="/halaman/baru">
            <IconPlus />
            Tambah halaman
          </Link>
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar halaman statis"
        pilih={false}
        kosong={{ judul: "Belum ada halaman", deskripsi: "Tambahkan halaman kebijakan atau ketentuan." }}
      />
    </Halaman>
  )
}
