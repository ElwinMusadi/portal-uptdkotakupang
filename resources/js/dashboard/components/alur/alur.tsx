import * as React from "react"
import { IconArrowRight, IconInfoCircle } from "@tabler/icons-react"
import { toast } from "sonner"

import { IKON_STATUS, StatusAlurBadge } from "@/components/data/status-badge"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Label } from "@/components/ui/label"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"
import type { Beralur, Riwayat } from "@/lib/api"
import { patchAlur, type KoleksiBeralur } from "@/lib/alur"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { AKSI_ALUR, STATUS_ALUR, TAHAP_ALUR, type AksiAlur } from "@/lib/meta"
import { usePengaturan, useUbahEntri } from "@/lib/queries"
import { cn } from "@/lib/utils"

type ItemBeralur = Beralur & { id: string }

/**
 * Jalankan aksi alur publikasi. Aksi "Kembalikan" meminta catatan revisi
 * lewat dialog. Kembalikan `dialog` untuk dirender di halaman.
 */
export function useAksiAlur(koleksi: KoleksiBeralur) {
  const ubah = useUbahEntri(koleksi)
  const { data: akun } = usePengaturan("akun")
  const [minta, setMinta] = React.useState<{ item: ItemBeralur; aksi: AksiAlur } | null>(null)
  const [catatan, setCatatan] = React.useState("")
  const idCatatan = React.useId()

  const terapkan = async (item: ItemBeralur, aksi: AksiAlur, cat?: string) => {
    try {
      await ubah.mutateAsync({
        id: item.id,
        patch: patchAlur(item, aksi, akun?.nama ?? "Pengelola PINTU", cat) as never,
      })
      toast.success(`Status: ${STATUS_ALUR[aksi.ke].label}`)
      return true
    } catch {
      return false
    }
  }

  const jalankan = async (item: ItemBeralur, aksi: AksiAlur) => {
    if (aksi.perluCatatan) {
      setCatatan("")
      setMinta({ item, aksi })
      return false
    }
    return terapkan(item, aksi)
  }

  const dialog = (
    <Dialog open={!!minta} onOpenChange={(o) => !o && setMinta(null)}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Kembalikan untuk revisi</DialogTitle>
          <DialogDescription>
            Tulis catatan agar penyedia tahu apa yang perlu diperbaiki.
          </DialogDescription>
        </DialogHeader>
        <form
          className="grid gap-4"
          onSubmit={async (e) => {
            e.preventDefault()
            if (!minta || !catatan.trim()) return
            if (await terapkan(minta.item, minta.aksi, catatan.trim())) setMinta(null)
          }}
        >
          <div className="grid gap-2">
            <Label htmlFor={idCatatan}>Catatan revisi</Label>
            <Textarea
              id={idCatatan}
              value={catatan}
              onChange={(e) => setCatatan(e.target.value)}
              rows={4}
              required
              placeholder="Mis. sesuaikan jumlah lembar dengan papan loket terbaru."
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setMinta(null)}>
              Batal
            </Button>
            <Button type="submit" variant="destructive" disabled={!catatan.trim() || ubah.isPending}>
              {ubah.isPending && <Spinner />}
              Kembalikan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )

  return { jalankan, dialog, memproses: ubah.isPending }
}

/** Item menu untuk baris tabel */
export function MenuAksiAlur({
  item,
  jalankan,
}: {
  item: ItemBeralur
  jalankan: (item: ItemBeralur, aksi: AksiAlur) => void
}) {
  return (
    <>
      {AKSI_ALUR[item.status].map((aksi) => {
        const Ikon = IKON_STATUS[aksi.ke]
        return (
          <DropdownMenuItem
            key={aksi.ke}
            variant={aksi.bahaya ? "destructive" : "default"}
            onSelect={() => jalankan(item, aksi)}
          >
            <Ikon />
            {aksi.label}
          </DropdownMenuItem>
        )
      })}
    </>
  )
}

/** Garis tahap: Draf → Diperiksa → Persetujuan → Terbit */
export function TahapAlur({ status }: { status: Beralur["status"] }) {
  const posisi =
    status === "dikembalikan" ? 0 : status === "arsip" ? -1 : TAHAP_ALUR.indexOf(status)
  return (
    <ol className="grid grid-cols-4 gap-1" aria-label="Tahap alur publikasi">
      {TAHAP_ALUR.map((t, i) => {
        const selesai = posisi >= 0 && i < posisi
        const kini = i === posisi
        return (
          <li key={t} className="grid gap-1.5">
            <span
              aria-hidden="true"
              className={cn(
                "h-1.5 rounded-full bg-muted",
                selesai && "bg-success",
                kini && (status === "terbit" ? "bg-success" : "bg-brand")
              )}
            />
            <span
              className={cn(
                "text-[11px] leading-tight text-muted-foreground",
                kini && "font-medium text-foreground"
              )}
            >
              {STATUS_ALUR[t].peran}
              <span className="sr-only">
                {kini ? " (tahap saat ini)" : selesai ? " (selesai)" : ""}
              </span>
            </span>
          </li>
        )
      })}
    </ol>
  )
}

export function RiwayatAlur({ riwayat, batas = 6 }: { riwayat: Riwayat[]; batas?: number }) {
  const [semua, setSemua] = React.useState(false)
  const tampil = semua ? riwayat : riwayat.slice(0, batas)
  if (riwayat.length === 0) return <p className="text-sm text-muted-foreground">Belum ada riwayat.</p>
  return (
    <div className="grid gap-2">
      <ol className="relative grid gap-4 border-l pl-4">
        {tampil.map((r, i) => (
          <li key={`${r.waktu}-${i}`} className="relative grid gap-0.5 text-sm">
            <span
              aria-hidden="true"
              className={cn(
                "absolute top-1.5 -left-[21px] size-2.5 rounded-full border-2 border-background bg-muted-foreground/50",
                i === 0 && "bg-brand"
              )}
            />
            <p>
              <span className="font-medium">{r.oleh}</span> {r.aksi}
            </p>
            {r.catatan && (
              <p className="rounded-md bg-muted px-2 py-1 text-muted-foreground">“{r.catatan}”</p>
            )}
            <time dateTime={r.waktu} className="text-xs text-muted-foreground" title={tanggalJam(r.waktu)}>
              {waktuRelatif(r.waktu)}
            </time>
          </li>
        ))}
      </ol>
      {riwayat.length > batas && (
        <Button type="button" variant="link" size="sm" className="w-fit px-0" onClick={() => setSemua((s) => !s)}>
          {semua ? "Tampilkan lebih sedikit" : `Tampilkan semua (${riwayat.length})`}
        </Button>
      )}
    </div>
  )
}

/**
 * Kartu status di halaman penyunting: tahap, tombol aksi, catatan revisi.
 * `sebelumAksi` dipanggil dulu (mis. menyimpan formulir); bila false, aksi batal.
 */
export function PanelAlur({
  item,
  jalankan,
  memproses,
  sebelumAksi,
  entriBaru,
}: {
  item: ItemBeralur | null
  jalankan: (item: ItemBeralur, aksi: AksiAlur) => Promise<boolean> | void
  memproses?: boolean
  sebelumAksi?: () => Promise<boolean>
  entriBaru?: boolean
}) {
  const status = item?.status ?? "draf"
  const meta = STATUS_ALUR[status]
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="flex flex-wrap items-center gap-2 text-base">
          Alur publikasi
          <StatusAlurBadge status={status} />
        </CardTitle>
        <CardDescription>{meta.keterangan}</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-4">
        <TahapAlur status={status} />
        {item?.status === "dikembalikan" && item.catatanRevisi && (
          <Alert>
            <IconInfoCircle />
            <AlertTitle>Catatan revisi</AlertTitle>
            <AlertDescription>{item.catatanRevisi}</AlertDescription>
          </Alert>
        )}
        {entriBaru || !item ? (
          <p className="text-sm text-muted-foreground">
            Simpan sebagai draf terlebih dahulu, lalu ajukan untuk diperiksa.
          </p>
        ) : (
          <div className="flex flex-wrap gap-2">
            {AKSI_ALUR[item.status].map((aksi) => (
              <Button
                key={aksi.ke}
                type="button"
                size="sm"
                variant={aksi.bahaya ? "outline" : "default"}
                className={cn(aksi.bahaya && "text-destructive hover:text-destructive")}
                disabled={memproses}
                onClick={async () => {
                  if (sebelumAksi && !(await sebelumAksi())) return
                  await jalankan(item, aksi)
                }}
              >
                {!aksi.bahaya && <IconArrowRight />}
                {aksi.label}
              </Button>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}
