import { IconPlus, IconSpeakerphone, IconStarFilled } from "@tabler/icons-react"
import { Link, useNavigate } from "react-router"

import { MenuAksiAlur, useAksiAlur } from "@/components/alur/alur"
import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { StatusAlurBadge } from "@/components/data/status-badge"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { DropdownMenuSeparator } from "@/components/ui/dropdown-menu"
import type { Berita } from "@/lib/api"
import { tanggal, waktuRelatif } from "@/lib/format"
import { KATEGORI_BERITA, STATUS_ALUR } from "@/lib/meta"
import { useKoleksi } from "@/lib/queries"

const k = pembantuKolom<Berita>()

export default function DaftarBerita() {
  const navigate = useNavigate()
  const { data, isLoading } = useKoleksi("berita")
  const hapus = useHapus("berita", "tulisan")
  const { jalankan, dialog } = useAksiAlur("berita")

  const kolom = [
    k.accessor("judul", {
      header: "Judul",
      cell: ({ row }) => (
        <div className="grid max-w-[26rem] min-w-56 gap-0.5 whitespace-normal">
          <Link
            to={`/berita/${row.original.id}`}
            className="font-medium underline-offset-4 hover:underline"
          >
            {row.original.judul}
          </Link>
          <span className="line-clamp-1 text-xs text-muted-foreground">{row.original.ringkasan}</span>
        </div>
      ),
      enableHiding: false,
    }),
    k.accessor("kategori", {
      header: "Kategori",
      cell: ({ row }) => (
        <div className="flex flex-wrap items-center gap-1">
          <Badge variant="outline" className="px-1.5 text-muted-foreground">
            {KATEGORI_BERITA[row.original.kategori]}
          </Badge>
          {row.original.sorotan && (
            <IconStarFilled className="size-3.5 text-warning" aria-label="Tulisan utama" />
          )}
          {row.original.pengumumanBeranda && (
            <IconSpeakerphone className="size-3.5 text-brand" aria-label="Tampil di beranda" />
          )}
        </div>
      ),
    }),
    k.accessor("tanggal", {
      header: ({ column }) => <HeaderUrut column={column} judul="Tanggal" />,
      cell: ({ row }) => <span className="whitespace-nowrap">{tanggal(row.original.tanggal)}</span>,
    }),
    k.accessor("status", {
      header: "Status",
      cell: ({ row }) => <StatusAlurBadge status={row.original.status} />,
    }),
    k.accessor("penyedia", {
      header: "Penyedia",
      cell: ({ row }) => <span className="text-sm text-muted-foreground">{row.original.penyedia}</span>,
    }),
    k.accessor("diperbarui", {
      header: ({ column }) => <HeaderUrut column={column} judul="Diperbarui" />,
      cell: ({ row }) => (
        <span className="text-sm whitespace-nowrap text-muted-foreground">
          {waktuRelatif(row.original.diperbarui)}
        </span>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.judul}
          onSunting={() => navigate(`/berita/${row.original.id}`)}
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
      <PengantarHalaman deskripsi="Tulisan tampil di halaman Informasi dan beranda portal setelah melewati alur publikasi. Tandai satu tulisan sebagai sorotan untuk dijadikan tulisan utama.">
        <Button asChild>
          <Link to="/berita/baru">
            <IconPlus />
            Tulis berita
          </Link>
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar berita dan pengumuman"
        sortingAwal={[{ id: "tanggal", desc: true }]}
        cari={{ placeholder: "Cari judul atau ringkasan…", teks: (b) => `${b.judul} ${b.ringkasan}` }}
        filter={[
          {
            id: "kategori",
            label: "Kategori",
            opsi: Object.entries(KATEGORI_BERITA).map(([nilai, label]) => ({ nilai, label })),
            nilai: (b) => b.kategori,
          },
          {
            id: "status",
            label: "Status",
            opsi: Object.entries(STATUS_ALUR).map(([nilai, s]) => ({ nilai, label: s.label })),
            nilai: (b) => b.status,
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
          judul: "Belum ada tulisan",
          deskripsi: "Tulis berita atau pengumuman pertama untuk portal.",
          tindakan: (
            <Button asChild size="sm">
              <Link to="/berita/baru">
                <IconPlus />
                Tulis berita
              </Link>
            </Button>
          ),
        }}
      />
      {dialog}
    </Halaman>
  )
}
