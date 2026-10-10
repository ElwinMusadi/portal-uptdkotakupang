import type * as React from "react"
import { IconDotsVertical, IconPencil, IconTrash } from "@tabler/icons-react"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import type { Id, NamaKoleksi } from "@/lib/api"
import { useHapusEntri } from "@/lib/queries"

/** Hapus entri dengan konfirmasi; mengembalikan true bila terhapus */
export function useHapus(koleksi: NamaKoleksi, nama: string) {
  const hapus = useHapusEntri(koleksi)
  const konfirmasi = useKonfirmasi()
  return async (ids: Id[], label?: string) => {
    if (ids.length === 0) return false
    const ya = await konfirmasi({
      judul: ids.length > 1 ? `Hapus ${ids.length} ${nama}?` : `Hapus ${nama} ini?`,
      deskripsi: label
        ? `“${label}” akan dihapus permanen dan tidak bisa dikembalikan.`
        : "Data akan dihapus permanen dan tidak bisa dikembalikan.",
      label: "Hapus",
      bahaya: true,
    })
    if (!ya) return false
    try {
      await hapus.mutateAsync(ids)
      toast.success(ids.length > 1 ? `${ids.length} ${nama} dihapus` : `${kapital(nama)} dihapus`)
      return true
    } catch {
      return false
    }
  }
}

function kapital(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}

/** Menu titik tiga di ujung baris tabel */
export function MenuBaris({
  label,
  onSunting,
  onHapus,
  children,
}: {
  label: string
  onSunting?: () => void
  onHapus?: () => void
  children?: React.ReactNode
}) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          className="flex size-8 text-muted-foreground data-[state=open]:bg-muted"
          size="icon"
        >
          <IconDotsVertical />
          <span className="sr-only">Pilihan untuk {label}</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        {onSunting && (
          <DropdownMenuItem onSelect={onSunting}>
            <IconPencil />
            Sunting
          </DropdownMenuItem>
        )}
        {children}
        {onHapus && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" onSelect={onHapus}>
              <IconTrash />
              Hapus
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

/** Tombol hapus untuk tindakan massal */
export function TombolHapusMassal({ onClick }: { onClick: () => void }) {
  return (
    <Button variant="outline" size="sm" className="text-destructive hover:text-destructive" onClick={onClick}>
      <IconTrash />
      Hapus terpilih
    </Button>
  )
}
