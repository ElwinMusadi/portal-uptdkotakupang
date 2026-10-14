import * as React from "react"
import { IconCamera, IconCameraRotate, IconRefresh } from "@tabler/icons-react"

import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"

import { pesanGalatKamera } from "./perangkat"

/** Sisi terpanjang swafoto; server memperkecil dan membuang metadata lagi */
const SISI_FOTO = 1024

/**
 * Swafoto langsung dari kamera depan (D-034): tidak ada pilihan galeri,
 * sehingga foto lama tidak bisa dipakai ulang. Pratinjau dicerminkan seperti
 * cermin; foto yang tersimpan tidak.
 */
export function Swafoto({ hasil, setHasil }: { hasil: Blob | null; setHasil: (foto: Blob | null) => void }) {
  const video = React.useRef<HTMLVideoElement>(null)
  const [aliran, setAliran] = React.useState<MediaStream | null>(null)
  const [membuka, setMembuka] = React.useState(false)
  const [galat, setGalat] = React.useState("")
  const pratinjau = React.useMemo(() => (hasil ? URL.createObjectURL(hasil) : null), [hasil])

  React.useEffect(() => () => {
    if (pratinjau) URL.revokeObjectURL(pratinjau)
  }, [pratinjau])

  React.useEffect(() => {
    if (video.current && aliran) {
      video.current.srcObject = aliran
      void video.current.play()
    }
    // matikan kamera saat foto diambil atau layar ditutup
    return () => aliran?.getTracks().forEach((t) => t.stop())
  }, [aliran])

  const buka = async () => {
    setGalat("")
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
      setGalat("Kamera hanya bisa dibuka lewat koneksi aman (HTTPS).")
      return
    }
    setMembuka(true)
    try {
      setAliran(
        await navigator.mediaDevices.getUserMedia({
          video: { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 960 } },
          audio: false,
        })
      )
    } catch (e) {
      setGalat(pesanGalatKamera(e))
    } finally {
      setMembuka(false)
    }
  }

  const ambil = async () => {
    const v = video.current
    if (!v?.videoWidth) return
    const skala = Math.min(1, SISI_FOTO / Math.max(v.videoWidth, v.videoHeight))
    const kanvas = document.createElement("canvas")
    kanvas.width = Math.round(v.videoWidth * skala)
    kanvas.height = Math.round(v.videoHeight * skala)
    kanvas.getContext("2d")?.drawImage(v, 0, 0, kanvas.width, kanvas.height)
    const foto = await new Promise<Blob | null>((selesai) => kanvas.toBlob(selesai, "image/jpeg", 0.85))
    if (!foto) {
      setGalat("Foto gagal diambil. Coba lagi.")
      return
    }
    setAliran(null)
    setHasil(foto)
  }

  return (
    <div className="grid gap-3">
      <div className="relative aspect-[3/4] overflow-hidden rounded-xl border bg-muted">
        {pratinjau ? (
          <img src={pratinjau} alt="Swafoto yang akan dikirim" className="size-full object-cover" />
        ) : aliran ? (
          <video ref={video} muted playsInline className="size-full -scale-x-100 object-cover" aria-label="Pratinjau kamera depan" />
        ) : (
          <div className="flex size-full flex-col items-center justify-center gap-2 p-6 text-center text-sm text-muted-foreground">
            <IconCamera className="size-8" aria-hidden="true" />
            Swafoto di lokasi kegiatan, wajah terlihat jelas.
          </div>
        )}
        {membuka && (
          <div className="absolute inset-0 flex items-center justify-center bg-background/70">
            <Spinner />
            <span className="sr-only">Membuka kamera…</span>
          </div>
        )}
      </div>
      {galat && <p className="text-sm text-destructive">{galat}</p>}
      {pratinjau ? (
        <Button type="button" variant="outline" onClick={() => setHasil(null)}>
          <IconRefresh />
          Ulangi swafoto
        </Button>
      ) : aliran ? (
        <Button type="button" size="lg" onClick={() => void ambil()}>
          <IconCamera />
          Ambil swafoto
        </Button>
      ) : (
        <Button type="button" variant="outline" disabled={membuka} onClick={() => void buka()}>
          <IconCameraRotate />
          Buka kamera depan
        </Button>
      )}
    </div>
  )
}
