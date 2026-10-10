import * as React from "react"
import { IconInfoCircle, IconX } from "@tabler/icons-react"
import { Link } from "react-router"

import { AntreanKerja } from "@/components/antrean-kerja"
import { ChartAreaInteractive } from "@/components/chart-area-interactive"
import { SectionCards } from "@/components/section-cards"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { api } from "@/lib/api"
import { tanggal } from "@/lib/format"
import { useItemAlur } from "@/lib/hitung"
import { usePengaturan } from "@/lib/queries"

const KUNCI_INFO = "pintu.info-prototipe"

function InfoPrototipe() {
  const [tampil, setTampil] = React.useState(() => {
    try {
      return localStorage.getItem(KUNCI_INFO) !== "tutup"
    } catch {
      return true
    }
  })
  if (api.mode !== "lokal" || !tampil) return null
  return (
    <div className="px-4 lg:px-6">
      <Alert className="pr-12">
        <IconInfoCircle />
        <AlertTitle>Mode prototipe: data tersimpan di peramban ini</AlertTitle>
        <AlertDescription>
          <p>
            Perubahan disimpan di IndexedDB perangkat ini dan belum tersambung ke portal publik. Cadangkan
            data lewat <Link to="/pengaturan?tab=data" className="underline underline-offset-4">Pengaturan › Data</Link>,
            atau baca <Link to="/panduan" className="underline underline-offset-4">Panduan</Link> untuk
            menyambungkan dashboard ke server.
          </p>
        </AlertDescription>
        <Button
          variant="ghost"
          size="icon"
          className="absolute top-2 right-2 size-7"
          onClick={() => {
            setTampil(false)
            try {
              localStorage.setItem(KUNCI_INFO, "tutup")
            } catch {
              // abaikan
            }
          }}
        >
          <IconX />
          <span className="sr-only">Tutup informasi</span>
        </Button>
      </Alert>
    </div>
  )
}

function Sapaan() {
  const { data: akun } = usePengaturan("akun")
  const { data: alur } = useItemAlur()
  const tindakan = alur?.filter((x) => ["draf", "diperiksa", "persetujuan", "dikembalikan"].includes(x.status)).length
  return (
    <div className="px-4 lg:px-6">
      <p className="text-sm text-muted-foreground">{tanggal(new Date(), "lengkap")}</p>
      <h2 className="mt-1 font-display text-2xl font-semibold tracking-tight">
        Selamat datang, {akun?.nama ?? "Pengelola PINTU"}.{" "}
        {tindakan !== undefined && (
          <span className="text-muted-foreground">
            {tindakan > 0 ? `${tindakan} konten sedang dalam alur publikasi.` : "Tidak ada konten yang tertunda."}
          </span>
        )}
      </h2>
    </div>
  )
}

export default function Ringkasan() {
  return (
    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
      <Sapaan />
      <InfoPrototipe />
      <SectionCards />
      <div className="px-4 lg:px-6">
        <ChartAreaInteractive />
      </div>
      <AntreanKerja />
    </div>
  )
}
