import { IconExternalLink, IconInfoCircle } from "@tabler/icons-react"
import { toast } from "sonner"

import { useKonfirmasi } from "@/components/data/confirm"
import { Bagian } from "@/components/data/page"
import { KerangkaMemuat } from "@/components/form/settings-form"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Switch } from "@/components/ui/switch"
import type { JenisKuesioner, StatusKuesioner } from "@/lib/api"
import { tanggalJam } from "@/lib/format"
import { KUESIONER, useSimpanStatusKuesioner, useStatusKuesioner } from "@/lib/kuesioner"

/**
 * Pengaturan › Kuesioner (D-098), khusus Administrator: sakelar buka-tutup
 * setiap kuesioner di /kuesioner. Perubahan langsung disimpan; menutup
 * kuesioner diminta konfirmasi lebih dulu.
 */
export function TabKuesioner() {
  const { data, error } = useStatusKuesioner()
  if (error) {
    return (
      <p className="rounded-lg border border-destructive/40 p-4 text-sm text-destructive">
        Data gagal dimuat: {(error as Error).message}
      </p>
    )
  }
  if (!data) return <KerangkaMemuat />
  return <DaftarKuesioner data={data} />
}

function DaftarKuesioner({ data }: { data: StatusKuesioner }) {
  const simpan = useSimpanStatusKuesioner()
  const konfirmasi = useKonfirmasi()

  const ubah = async (jenis: JenisKuesioner, dibuka: boolean) => {
    const { judul } = KUESIONER.find((k) => k.jenis === jenis)!
    if (
      !dibuka &&
      !(await konfirmasi({
        judul: `Tutup ${judul}?`,
        deskripsi: "Halaman kuesioner tetap bisa dibuka, tetapi tidak bisa diisi sampai dibuka lagi. Jawaban yang sudah masuk tetap ada di Google Formulir.",
        label: "Tutup kuesioner",
        bahaya: true,
      }))
    ) {
      return
    }
    try {
      await simpan.mutateAsync({ wajibPajak: data.wajibPajak.dibuka, operator: data.operator.dibuka, [jenis]: dibuka })
      toast.success(`${judul} ${dibuka ? "dibuka" : "ditutup"}`)
    } catch (e) {
      toast.error((e as Error).message)
    }
  }

  return (
    <Bagian
      judul="Kuesioner"
      deskripsi="Buka atau tutup kuesioner penilaian PINTU di halaman /kuesioner. Kuesioner yang ditutup tetap tampil di portal dengan tanda Ditutup, tetapi tidak bisa diisi."
    >
      <ul className="grid gap-3" role="list">
        {KUESIONER.map(({ jenis, judul, alamat }) => {
          const { dibuka, sejak } = data[jenis]
          const id = `kuesioner-${jenis}`
          return (
            <li key={jenis} className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 rounded-lg border p-4">
              <div className="grid min-w-0 gap-1">
                <div className="flex flex-wrap items-center gap-2">
                  <p className="font-medium">{judul}</p>
                  <Badge variant={dibuka ? "default" : "secondary"}>{dibuka ? "Dibuka" : "Ditutup"}</Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  {sejak ? `${dibuka ? "Dibuka" : "Ditutup"} sejak ${tanggalJam(sejak)}` : "Dibuka sejak kuesioner diterbitkan"}
                  {" · "}
                  <a className="inline-flex items-center gap-1 underline-offset-4 hover:underline" href={alamat} target="_blank" rel="noopener">
                    Lihat halaman
                    <IconExternalLink className="size-3.5" aria-hidden="true" />
                    <span className="sr-only"> (terbuka di tab baru)</span>
                  </a>
                </p>
              </div>
              <label htmlFor={id} className="flex items-center gap-3 text-sm">
                <Switch
                  id={id}
                  checked={dibuka}
                  disabled={simpan.isPending}
                  onCheckedChange={(nilai) => void ubah(jenis, nilai)}
                  aria-label={`${judul} menerima jawaban`}
                />
                Menerima jawaban
              </label>
            </li>
          )
        })}
      </ul>
      <Alert>
        <IconInfoCircle />
        <AlertDescription>
          Sakelar ini menutup halaman kuesioner di PINTU. Google Formulir-nya sendiri tetap menerima jawaban dari tautan Google; untuk
          menghentikannya sepenuhnya, matikan juga penerimaan jawaban di Google Formulir (tab Jawaban).
        </AlertDescription>
      </Alert>
    </Bagian>
  )
}
