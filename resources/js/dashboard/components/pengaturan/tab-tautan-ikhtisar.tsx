import * as React from "react"
import { IconCopy, IconExternalLink, IconLinkPlus } from "@tabler/icons-react"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { Bagian } from "@/components/data/page"
import { Button } from "@/components/ui/button"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Skeleton } from "@/components/ui/skeleton"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { useBuatTautanIkhtisar, useCabutTautanIkhtisar, useTautanIkhtisar } from "@/lib/ikhtisar"

/**
 * Pengaturan › Tautan Ikhtisar (D-099), khusus Administrator: tautan ke
 * halaman Ikhtisar untuk rapat, yang bisa dibuka siapa pun yang memegangnya
 * tanpa login sampai dicabut. Alamatnya hanya tampil sekali.
 */
export function TabTautanIkhtisar() {
  const daftar = useTautanIkhtisar()
  const buat = useBuatTautanIkhtisar()
  const cabut = useCabutTautanIkhtisar()
  const konfirmasi = useKonfirmasi()
  const [nama, setNama] = React.useState("")
  const [membuat, setMembuat] = React.useState(false)
  const [baru, setBaru] = React.useState<{ nama: string; alamat: string } | null>(null)
  const [galat, setGalat] = React.useState<string | null>(null)

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!nama.trim()) return setGalat("Tulis nama tautan, mis. Rapat staf.")
    try {
      const hasil = await buat.mutateAsync(nama.trim())
      setBaru({ nama: hasil.tautan.nama, alamat: hasil.alamat })
      setMembuat(false)
      setNama("")
      setGalat(null)
    } catch (err) {
      setGalat((err as Error).message)
    }
  }

  const cabutTautan = async (id: string, namaTautan: string) => {
    const ya = await konfirmasi({
      judul: `Cabut tautan ${namaTautan}?`,
      deskripsi: "Semua yang memegang tautan ini tidak bisa membuka Ikhtisar lagi, dan layar yang sedang menampilkannya berhenti diperbarui. Tautan lain tidak terpengaruh.",
      label: "Cabut",
      bahaya: true,
    })
    if (!ya) return
    try {
      await cabut.mutateAsync(id)
      toast.success(`Tautan ${namaTautan} dicabut`)
    } catch (e) {
      toast.error("Tautan gagal dicabut", { description: (e as Error).message })
    }
  }

  const salin = async () => {
    if (!baru) return
    try {
      await navigator.clipboard.writeText(baru.alamat)
      toast.success("Tautan disalin")
    } catch {
      toast.error("Tautan tidak bisa disalin otomatis", { description: "Pilih isi kotak tautan lalu salin secara manual." })
    }
  }

  return (
    <Bagian
      judul="Tautan Ikhtisar"
      deskripsi="Halaman Ikhtisar menampilkan penerimaan semua loket, Realisasi Target (Form I), dan Pemetaan PKB sampai STS terakhir untuk rapat, tanpa identitas wajib pajak. Siapa pun yang memegang tautannya bisa membuka halaman itu tanpa login sampai tautannya dicabut."
      aksi={
        <Button size="sm" onClick={() => setMembuat(true)}>
          <IconLinkPlus />
          Buat tautan
        </Button>
      }
    >
      {daftar.isLoading ? (
        <Skeleton className="h-20" />
      ) : daftar.error ? (
        <p className="text-sm text-destructive">Tautan gagal dimuat: {daftar.error.message}</p>
      ) : (daftar.data ?? []).length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada tautan. Buat satu tautan per kelompok, mis. Rapat staf atau Grup WA pimpinan, agar bisa dicabut sendiri-sendiri.</p>
      ) : (
        <ul className="grid gap-2" role="list">
          {(daftar.data ?? []).map((t) => (
            <li key={t.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3">
              <span className="grid">
                <span className="font-medium">{t.nama}</span>
                <span className="text-xs text-muted-foreground">
                  Dibuat {tanggalJam(t.dibuat)} oleh {t.dibuatOleh ?? "—"} · {t.terakhirDibuka ? `terakhir dibuka ${waktuRelatif(t.terakhirDibuka)}` : "belum pernah dibuka"}
                </span>
              </span>
              <Button size="sm" variant="outline" onClick={() => void cabutTautan(t.id, t.nama)}>
                Cabut
              </Button>
            </li>
          ))}
        </ul>
      )}

      <Dialog open={membuat} onOpenChange={(buka) => !buka && setMembuat(false)}>
        <DialogContent>
          <form className="grid gap-4" onSubmit={(e) => void kirim(e)} noValidate>
            <DialogHeader>
              <DialogTitle>Buat tautan Ikhtisar</DialogTitle>
              <DialogDescription>Anda akan mendapat tautan yang hanya ditampilkan sekali. Bagikan ke kelompok yang disebut di nama tautan.</DialogDescription>
            </DialogHeader>
            <div className="grid gap-2">
              <Label htmlFor="ikhtisar-nama">Nama tautan</Label>
              <Input id="ikhtisar-nama" value={nama} maxLength={60} onChange={(e) => setNama(e.target.value)} placeholder="Mis. Rapat staf" />
            </div>
            {galat && <p className="text-sm text-destructive">{galat}</p>}
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setMembuat(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={buat.isPending}>
                Buat tautan
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={!!baru} onOpenChange={(buka) => !buka && setBaru(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>Tautan {baru?.nama}</DialogTitle>
            <DialogDescription>Bagikan tautan ini ke pegawai dan atasan yang perlu melihat Ikhtisar. Perangkat yang membukanya menyimpan tautan itu sendiri.</DialogDescription>
          </DialogHeader>
          <div className="flex gap-2">
            <Input readOnly value={baru?.alamat ?? ""} aria-label="Alamat tautan Ikhtisar" className="font-mono text-xs" onFocus={(e) => e.currentTarget.select()} />
            <Button onClick={() => void salin()}>
              <IconCopy />
              Salin
            </Button>
          </div>
          <p className="text-sm text-muted-foreground">
            Tautan ini tidak bisa ditampilkan lagi. Jangan dibagikan ke luar UPTD: siapa pun yang membukanya bisa melihat angka penerimaan dan pemetaan. Bila hilang atau tersebar, cabut lalu buat yang baru.
          </p>
          <DialogFooter>
            <Button variant="outline" asChild>
              <a href={baru?.alamat} target="_blank" rel="noopener noreferrer">
                <IconExternalLink />
                Buka
              </a>
            </Button>
            <Button onClick={() => setBaru(null)}>Selesai</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Bagian>
  )
}
