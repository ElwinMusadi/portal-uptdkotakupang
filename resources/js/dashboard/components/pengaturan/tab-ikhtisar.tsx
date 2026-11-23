import { IconAlertTriangle, IconCopy, IconExternalLink } from "@tabler/icons-react"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { Bagian } from "@/components/data/page"
import { KerangkaMemuat } from "@/components/form/settings-form"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { tanggalJam } from "@/lib/format"
import { useSimpanStatusIkhtisar, useStatusIkhtisar, type StatusIkhtisar } from "@/lib/ikhtisar"

/**
 * Pengaturan › Halaman Ikhtisar (D-100), khusus Administrator: sakelar yang
 * membuka halaman Ikhtisar di /laporan-penerimaan untuk siapa saja sampai
 * dinonaktifkan. Mengaktifkannya diminta konfirmasi, karena angkanya lalu
 * bisa dibuka siapa pun yang tahu alamatnya.
 */
export function TabIkhtisar() {
  const { data, error } = useStatusIkhtisar()
  if (error) {
    return (
      <p className="rounded-lg border border-destructive/40 p-4 text-sm text-destructive">
        Data gagal dimuat: {(error as Error).message}
      </p>
    )
  }
  if (!data) return <KerangkaMemuat />
  return <StatusHalaman data={data} />
}

function StatusHalaman({ data }: { data: StatusIkhtisar }) {
  const simpan = useSimpanStatusIkhtisar()
  const konfirmasi = useKonfirmasi()

  const ubah = async (aktif: boolean) => {
    if (
      aktif &&
      !(await konfirmasi({
        judul: "Aktifkan halaman Ikhtisar?",
        deskripsi:
          "Siapa pun yang tahu alamatnya bisa membuka penerimaan semua loket, Realisasi Target, dan Pemetaan PKB tanpa login, termasuk angka kecil per kelurahan, sampai halaman dinonaktifkan lagi. Identitas wajib pajak tidak pernah ditampilkan.",
        label: "Aktifkan",
      }))
    ) {
      return
    }
    try {
      await simpan.mutateAsync(aktif)
      toast.success(aktif ? "Halaman Ikhtisar diaktifkan" : "Halaman Ikhtisar dinonaktifkan")
    } catch (e) {
      toast.error((e as Error).message)
    }
  }

  const salin = async () => {
    try {
      await navigator.clipboard.writeText(data.alamat)
      toast.success("Alamat disalin")
    } catch {
      toast.error("Alamat tidak bisa disalin otomatis", { description: "Pilih isi kotak alamat lalu salin secara manual." })
    }
  }

  return (
    <Bagian
      judul="Halaman Ikhtisar"
      deskripsi="Halaman untuk rapat: penerimaan semua loket dengan saringan seperti Laporan Penerimaan, Realisasi Target (Form I), Pemetaan PKB, dan pembayaran PKB sejak Data Potensi, sampai STS terakhir, tanpa identitas wajib pajak. Selama aktif, halaman bisa dibuka tanpa login dari alamat di bawah."
    >
      <div className="grid gap-4 rounded-lg border p-4">
        <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
          <div className="grid min-w-0 gap-1">
            <div className="flex flex-wrap items-center gap-2">
              <p className="font-medium">/laporan-penerimaan</p>
              <Badge variant={data.aktif ? "default" : "secondary"}>{data.aktif ? "Aktif" : "Nonaktif"}</Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              {data.sejak ? `${data.aktif ? "Aktif" : "Nonaktif"} sejak ${tanggalJam(data.sejak)}` : "Belum pernah diaktifkan"}
            </p>
          </div>
          <label htmlFor="ikhtisar-aktif" className="flex items-center gap-3 text-sm">
            <Switch
              id="ikhtisar-aktif"
              checked={data.aktif}
              disabled={simpan.isPending}
              onCheckedChange={(nilai) => void ubah(nilai)}
              aria-label="Halaman Ikhtisar bisa dibuka"
            />
            Bisa dibuka
          </label>
        </div>
        <div className="flex flex-wrap gap-2">
          <Input
            readOnly
            value={data.alamat}
            aria-label="Alamat halaman Ikhtisar"
            className="min-w-0 flex-1 basis-64 font-mono text-xs"
            onFocus={(e) => e.currentTarget.select()}
          />
          <Button variant="outline" onClick={() => void salin()}>
            <IconCopy />
            Salin
          </Button>
          <Button variant="outline" asChild>
            <a href={data.alamat} target="_blank" rel="noopener">
              <IconExternalLink />
              Buka
              <span className="sr-only"> (terbuka di tab baru)</span>
            </a>
          </Button>
        </div>
      </div>
      <Alert>
        <IconAlertTriangle />
        <AlertDescription>
          Alamatnya tetap dan mudah ditebak, jadi angka kecil per kelurahan bisa dibaca siapa pun selama halaman aktif. Nonaktifkan setelah rapat
          selesai: pengunjung lalu melihat pesan bahwa halaman sedang dinonaktifkan, dan layar yang masih terbuka beralih ke pesan itu saat memuat
          ulang angkanya (paling lama 5 menit).
        </AlertDescription>
      </Alert>
    </Bagian>
  )
}
