import * as React from "react"
import {
  IconAlertTriangle,
  IconArrowLeft,
  IconCircleCheckFilled,
  IconDeviceMobileDown,
  IconLogout,
  IconMapPin,
  IconMapPinCheck,
  IconMapPinOff,
  IconRefresh,
} from "@tabler/icons-react"

import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { ApiError } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"
import { headerXsrf, SAMA_ORIGIN, type Sesi } from "@/lib/auth"

import { AKURASI_MAKS, jamWita, jarakMeter, tanggalPanjang, useLokasi } from "./perangkat"
import { Swafoto } from "./swafoto"

interface Absen {
  waktu: string
  jarak: number
  akurasi: number
}

interface Kegiatan {
  id: string
  nama: string
  tempat: string
  lat: number
  lng: number
  radius: number
  catatan: string
  absen: Absen | null
}

interface HariIni {
  tanggal: string
  kegiatan: Kegiatan[]
}

/** Kembali ke halaman login; setelah masuk, petugas kembali ke aplikasi ini */
function keLogin(alasan?: "berakhir" | "keluar") {
  const url = new URL("/login", window.location.origin)
  if (alasan === "keluar") url.searchParams.set("keluar", "1")
  else {
    if (alasan) url.searchParams.set("sesi", alasan)
    url.searchParams.set("lanjut", "absen")
  }
  window.location.replace(url.toString())
}

async function keluar() {
  try {
    await fetch("/sanctum/csrf-cookie", SAMA_ORIGIN)
    await fetch("/api/keluar", { ...SAMA_ORIGIN, method: "POST", headers: { Accept: "application/json", ...headerXsrf() } })
  } finally {
    keLogin("keluar")
  }
}

/** Ajakan memasang aplikasi ke layar utama (Android/Chrome); hilang setelah terpasang */
function usePasang() {
  const [ajakan, setAjakan] = React.useState<(Event & { prompt: () => Promise<void> }) | null>(null)
  React.useEffect(() => {
    const tangkap = (e: Event) => {
      e.preventDefault()
      setAjakan(e as Event & { prompt: () => Promise<void> })
    }
    const selesai = () => setAjakan(null)
    window.addEventListener("beforeinstallprompt", tangkap)
    window.addEventListener("appinstalled", selesai)
    return () => {
      window.removeEventListener("beforeinstallprompt", tangkap)
      window.removeEventListener("appinstalled", selesai)
    }
  }, [])
  return ajakan
}

export function AplikasiAbsen({ sesi }: { sesi: Sesi }) {
  const [hari, setHari] = React.useState<HariIni | null>(null)
  const [galat, setGalat] = React.useState("")
  const [pilihan, setPilihan] = React.useState<Kegiatan | null>(null)
  const ajakan = usePasang()

  const muat = React.useCallback(async () => {
    setGalat("")
    try {
      setHari(await mintaServer<HariIni>("/api/absen", "GET"))
    } catch (e) {
      if (e instanceof ApiError && e.status === 401) keLogin("berakhir")
      else setGalat((e as Error).message)
    }
  }, [])

  React.useEffect(() => {
    void muat()
  }, [muat])

  if (pilihan) {
    return (
      <LayarClockIn
        kegiatan={pilihan}
        kembali={() => setPilihan(null)}
        tercatat={(absen) => {
          setHari((h) => h && { ...h, kegiatan: h.kegiatan.map((k) => (k.id === pilihan.id ? { ...k, absen } : k)) })
          setPilihan((k) => k && { ...k, absen })
        }}
      />
    )
  }

  return (
    <main className="mx-auto grid min-h-svh max-w-md content-start gap-5 p-4 pb-10">
      <header className="flex items-start justify-between gap-3">
        <div>
          <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">Absensi Lapangan PINTU</p>
          <h1 className="mt-1 text-xl font-semibold">{sesi.nama}</h1>
          <p className="text-sm text-muted-foreground">{hari ? tanggalPanjang(hari.tanggal) : "Memuat…"}</p>
        </div>
        <Button variant="ghost" size="sm" onClick={() => void keluar()}>
          <IconLogout />
          Keluar
        </Button>
      </header>

      {ajakan && (
        <Button variant="outline" onClick={() => void ajakan.prompt()}>
          <IconDeviceMobileDown />
          Pasang aplikasi di layar utama
        </Button>
      )}

      {galat ? (
        <Alert variant="destructive">
          <IconAlertTriangle />
          <AlertTitle>Daftar kegiatan belum dapat dimuat</AlertTitle>
          <AlertDescription>
            {galat}
            <Button variant="outline" size="sm" className="mt-2" onClick={() => void muat()}>
              <IconRefresh />
              Coba lagi
            </Button>
          </AlertDescription>
        </Alert>
      ) : !hari ? (
        <div className="flex justify-center py-10">
          <Spinner />
          <span className="sr-only">Memuat kegiatan…</span>
        </div>
      ) : hari.kegiatan.length === 0 ? (
        <div className="grid justify-items-center gap-3 rounded-xl border p-6 text-center">
          <p className="font-medium">Tidak ada kegiatan lapangan untuk Anda hari ini.</p>
          <p className="text-sm text-muted-foreground">Kegiatan yang ditugaskan Administrator untuk hari ini tampil di sini.</p>
          <Button variant="outline" size="sm" onClick={() => void muat()}>
            <IconRefresh />
            Muat ulang
          </Button>
        </div>
      ) : (
        <ul className="grid gap-3" aria-label="Kegiatan lapangan hari ini">
          {hari.kegiatan.map((k) => (
            <li key={k.id} className="grid gap-3 rounded-xl border p-4">
              <div>
                <p className="font-semibold">{k.nama}</p>
                <p className="flex items-center gap-1 text-sm text-muted-foreground">
                  <IconMapPin className="size-4 shrink-0" aria-hidden="true" />
                  {k.tempat} · radius {k.radius} m
                </p>
                {k.catatan && <p className="mt-1 text-sm">{k.catatan}</p>}
              </div>
              {k.absen ? (
                <p className="flex items-center gap-2 text-sm font-medium text-success">
                  <IconCircleCheckFilled className="size-5" aria-hidden="true" />
                  Clock-in {jamWita(k.absen.waktu)} WITA · {k.absen.jarak} m dari titik
                </p>
              ) : (
                <Button size="lg" onClick={() => setPilihan(k)}>
                  Clock-in
                </Button>
              )}
            </li>
          ))}
        </ul>
      )}

      <p className="text-center text-xs text-muted-foreground">
        Clock-out dilakukan dengan mesin sidik jari di lapangan setelah tugas selesai.
      </p>
    </main>
  )
}

function LayarClockIn({ kegiatan, kembali, tercatat }: { kegiatan: Kegiatan; kembali: () => void; tercatat: (absen: Absen) => void }) {
  const { st, cari } = useLokasi()
  const [foto, setFoto] = React.useState<Blob | null>(null)
  // Alamat swafoto yang sudah diunggah; dipakai ulang bila clock-in dicoba lagi tanpa foto baru
  const [alamatFoto, setAlamatFoto] = React.useState<string | null>(null)
  const [mengirim, setMengirim] = React.useState(false)
  const [galat, setGalat] = React.useState("")

  const titik = st.status === "ada" ? st.titik : null
  const jarak = titik ? jarakMeter(kegiatan, titik) : null
  const akurasiCukup = !!titik && titik.akurasi <= AKURASI_MAKS
  const dalamRadius = jarak !== null && jarak <= kegiatan.radius
  const siap = !!titik && akurasiCukup && dalamRadius && !!foto && !mengirim

  const gantiFoto = (baru: Blob | null) => {
    setFoto(baru)
    setAlamatFoto(null)
  }

  const kirim = async () => {
    if (!titik || !foto) return
    setMengirim(true)
    setGalat("")
    try {
      let alamat = alamatFoto
      if (!alamat) {
        const form = new FormData()
        form.append("modul", "absensi")
        form.append("berkas", foto, "swafoto.jpg")
        alamat = (await mintaServer<{ url: string }>("/api/unggah", "POST", form)).url
        setAlamatFoto(alamat)
      }
      tercatat(
        await mintaServer<Absen>(`/api/absen/${encodeURIComponent(kegiatan.id)}`, "POST", {
          foto: alamat,
          lat: titik.lat,
          lng: titik.lng,
          akurasi: titik.akurasi,
        })
      )
    } catch (e) {
      if (e instanceof ApiError && e.status === 401) keLogin("berakhir")
      else setGalat((e as Error).message)
    } finally {
      setMengirim(false)
    }
  }

  if (kegiatan.absen) {
    return (
      <main className="mx-auto grid min-h-svh max-w-md content-center justify-items-center gap-4 p-6 text-center">
        <IconCircleCheckFilled className="size-16 text-success" aria-hidden="true" />
        <h1 className="text-xl font-semibold">Clock-in tercatat</h1>
        <p>
          {kegiatan.nama}
          <br />
          pukul {jamWita(kegiatan.absen.waktu)} WITA, {kegiatan.absen.jarak} m dari titik kegiatan.
        </p>
        <p className="text-sm text-muted-foreground">Lakukan clock-out dengan mesin sidik jari setelah tugas selesai.</p>
        <Button onClick={kembali}>Selesai</Button>
      </main>
    )
  }

  return (
    <main className="mx-auto grid min-h-svh max-w-md content-start gap-5 p-4 pb-10">
      <div>
        <Button variant="ghost" size="sm" className="-ml-2" onClick={kembali}>
          <IconArrowLeft />
          Kembali
        </Button>
        <h1 className="mt-2 text-xl font-semibold">{kegiatan.nama}</h1>
        <p className="text-sm text-muted-foreground">{kegiatan.tempat}</p>
      </div>

      <section className="grid gap-2" aria-labelledby="judul-lokasi">
        <h2 id="judul-lokasi" className="text-sm font-medium">
          1. Lokasi
        </h2>
        <div role="status" aria-live="polite" className="flex items-start gap-3 rounded-xl border p-3 text-sm">
          {st.status === "mencari" ? (
            <Spinner className="mt-0.5 size-5 shrink-0" />
          ) : titik && akurasiCukup && dalamRadius ? (
            <IconMapPinCheck className="mt-0.5 size-5 shrink-0 text-success" aria-hidden="true" />
          ) : (
            <IconMapPinOff className="mt-0.5 size-5 shrink-0 text-destructive" aria-hidden="true" />
          )}
          <div className="grid min-w-0 flex-1 gap-1">
            {st.status === "mencari" && <p>Mencari lokasi GPS…</p>}
            {st.status === "galat" && <p>{st.pesan}</p>}
            {titik && jarak !== null && (
              <>
                <p className="font-medium">
                  {!akurasiCukup
                    ? `Akurasi GPS ±${titik.akurasi} m terlalu rendah`
                    : dalamRadius
                      ? `Dalam radius kegiatan (${jarak} m dari titik)`
                      : `Di luar radius: ${jarak} m dari titik, batasnya ${kegiatan.radius} m`}
                </p>
                <p className="text-muted-foreground">
                  {!akurasiCukup
                    ? `Batasnya ±${AKURASI_MAKS} m. Pindah ke area terbuka lalu perbarui lokasi.`
                    : `Akurasi ±${titik.akurasi} m.`}
                </p>
              </>
            )}
          </div>
          <Button type="button" variant="outline" size="sm" className="shrink-0" disabled={st.status === "mencari"} onClick={cari}>
            <IconRefresh />
            Perbarui
          </Button>
        </div>
      </section>

      <section className="grid gap-2" aria-labelledby="judul-foto">
        <h2 id="judul-foto" className="text-sm font-medium">
          2. Swafoto
        </h2>
        <Swafoto hasil={foto} setHasil={gantiFoto} />
      </section>

      {galat && (
        <Alert variant="destructive">
          <IconAlertTriangle />
          <AlertTitle>Clock-in belum tercatat</AlertTitle>
          <AlertDescription>{galat}</AlertDescription>
        </Alert>
      )}

      <Button size="lg" disabled={!siap} onClick={() => void kirim()}>
        {mengirim && <Spinner />}
        Clock-in sekarang
      </Button>
      <p className="text-center text-xs text-muted-foreground">
        Waktu clock-in mengikuti jam server. Server memeriksa ulang jarak dan akurasi lokasi Anda.
      </p>
    </main>
  )
}
