import * as React from "react"
import { IconArrowBackUp, IconCamera, IconCheck, IconScan, IconServer } from "@tabler/icons-react"
import { toast } from "sonner"

import { NavAntrean } from "@/components/antrean/nav-antrean"
import { NadaBadge } from "@/components/data/status-badge"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import {
  cariTiket,
  jamSaja,
  LABEL_TAHAP,
  nomorBertanggal,
  POS,
  useDaftarPos,
  useTandaiPos,
  useUrungkanTanda,
  type PosAntrean,
  type TandaPos,
  type TiketAntrean,
} from "@/lib/antrean"

/*
 * Pos Proses Antrean Online (D-051 Irisan 3): Penetapan, Server, Pelunasan,
 * Cetak SKPD, dan Cetak STNK menandai berkas yang selesai di posnya. Pindaian
 * QR berkas (pemindai USB mengetik lalu Enter, atau kamera bila peramban
 * mendukung) langsung menandai, dengan tombol Urungkan (keputusan pemilik).
 * Memindai tidak wajib: berkas juga bisa dipilih dari daftar, dan tahap
 * sebelumnya yang belum ditandai dianggap selesai. Penetapan bisa mengirim
 * berkas ke Server untuk perubahan data; setelah Server menandai, berkas
 * kembali ke daftar Penetapan.
 */

type InfoPos = (typeof POS)[number]

const KUNCI_POS = "pintu-pos-antrean"

function posTersimpan(): PosAntrean | null {
  try {
    const nilai = window.localStorage.getItem(KUNCI_POS)
    return POS.find((p) => p.kunci === nilai)?.kunci ?? null
  } catch {
    return null
  }
}

function simpanPos(pos: PosAntrean) {
  try {
    window.localStorage.setItem(KUNCI_POS, pos)
  } catch {
    // penyimpanan tidak tersedia: pilihan berlaku sampai halaman ditutup
  }
}

export default function HalamanPos() {
  const [pilih, setPilih] = React.useState<PosAntrean | null>(posTersimpan)
  const pos = POS.find((p) => p.kunci === pilih) ?? null

  const ganti = (kunci: string) => {
    const baru = POS.find((p) => p.kunci === kunci)
    if (!baru) return
    setPilih(baru.kunci)
    simpanPos(baru.kunci)
  }

  return (
    <Halaman>
      <NavAntrean />
      <PengantarHalaman deskripsi="Tandai berkas yang selesai di pos Anda dengan memindai QR pada berkas, atau pilih dari daftar. Tahap sebelumnya yang belum ditandai dianggap selesai. Pilihan pos diingat di perangkat ini.">
        <div className="grid gap-1.5">
          <Label htmlFor="antrean-pilih-pos" className="text-xs text-muted-foreground">
            Pos saya
          </Label>
          <Select value={pos?.kunci ?? ""} onValueChange={ganti}>
            <SelectTrigger id="antrean-pilih-pos" className="w-64">
              <SelectValue placeholder="Pilih pos" />
            </SelectTrigger>
            <SelectContent>
              {POS.map((p) => (
                <SelectItem key={p.kunci} value={p.kunci}>
                  {p.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </PengantarHalaman>
      {pos ? (
        <MejaPos key={pos.kunci} pos={pos} />
      ) : (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Pilih pos Anda</CardTitle>
            <CardDescription>Pos tempat Anda menandai berkas hari ini.</CardDescription>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {POS.map((p) => (
              <Button key={p.kunci} variant="outline" onClick={() => ganti(p.kunci)}>
                {p.label}
              </Button>
            ))}
          </CardContent>
        </Card>
      )}
    </Halaman>
  )
}

/** Hasil tanda terakhir di pos ini: tampil di bawah kotak pindai sampai pindaian berikutnya */
type HasilTanda = { berhasil: true; tiket: TiketAntrean; pesan: string; keterangan: string } | { berhasil: false; pesan: string; keterangan: string }

/** Kalimat konfirmasi tanda: apa yang terjadi pada nomor itu dan ke mana berkasnya */
function konfirmasi(pos: InfoPos, sebelum: TiketAntrean, sesudah: TiketAntrean, tanda: TandaPos): { pesan: string; keterangan: string } {
  const nomor = nomorBertanggal(sesudah)
  const terlewat = sebelum.tahap !== pos.tahap ? " Tahap sebelumnya yang belum ditandai dianggap selesai." : ""
  if (tanda === "perubahan-data") return { pesan: `Nomor ${nomor} dikirim ke Server`, keterangan: `Menunggu perubahan data, lalu kembali ke Penetapan.${terlewat}` }
  if (pos.kunci === "server") return { pesan: `Perubahan data nomor ${nomor} selesai`, keterangan: "Berkas kembali ke daftar Penetapan." }
  const lanjut = sesudah.status === "selesai" || !sesudah.tahap ? "Berkas selesai diproses." : `Lanjut ke ${LABEL_TAHAP[sesudah.tahap]}.`
  return { pesan: `Nomor ${nomor} selesai di ${pos.label}`, keterangan: `${lanjut}${terlewat}` }
}

function MejaPos({ pos }: { pos: InfoPos }) {
  const daftar = useDaftarPos(pos.kunci)
  const tandai = useTandaiPos()
  const urungkan = useUrungkanTanda()
  const [mode, setMode] = React.useState<TandaPos>("selesai")
  const [hasil, setHasil] = React.useState<HasilTanda | null>(null)
  const sibuk = tandai.isPending || urungkan.isPending

  const urungkanTanda = async (t: TiketAntrean) => {
    try {
      const kembali = await urungkan.mutateAsync(t.id)
      setHasil(null)
      toast.success(`Tanda nomor ${t.nomor} diurungkan`, { description: kembali.tahap ? `Kembali ke ${LABEL_TAHAP[kembali.tahap]}.` : undefined })
    } catch (e) {
      toast.error(`Tanda nomor ${t.nomor} gagal diurungkan`, { description: (e as Error).message })
    }
  }

  const tandaiNomor = async (t: TiketAntrean, tanda: TandaPos): Promise<boolean> => {
    try {
      const sesudah = await tandai.mutateAsync({ pos: pos.kunci, tiket: t.id, tanda })
      const isi = konfirmasi(pos, t, sesudah, tanda)
      setHasil({ berhasil: true, tiket: sesudah, ...isi })
      // Perubahan data adalah pengecualian: pindaian berikutnya kembali menandai Selesai
      setMode("selesai")
      // Lebih lama dari toast biasa agar sempat menekan Urungkan
      toast.success(isi.pesan, { description: isi.keterangan, duration: 10_000, action: { label: "Urungkan", onClick: () => void urungkanTanda(sesudah) } })
      return true
    } catch (e) {
      setHasil({ berhasil: false, pesan: `Nomor ${t.nomor} gagal ditandai`, keterangan: (e as Error).message })
      toast.error(`Nomor ${t.nomor} gagal ditandai`, { description: (e as Error).message })
      return false
    }
  }

  return (
    <div className="grid grid-cols-[minmax(0,1fr)] gap-4 2xl:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] 2xl:items-start">
      <KotakPindai
        pos={pos}
        mode={mode}
        gantiMode={setMode}
        tandai={(t) => tandaiNomor(t, pos.kunci === "penetapan" ? mode : "selesai")}
        hasil={hasil}
        gagal={(pesan) => setHasil({ berhasil: false, pesan: "Nomor tidak ditemukan", keterangan: pesan })}
        urungkan={urungkanTanda}
        sibuk={sibuk}
      />
      {daftar.isLoading ? (
        <Skeleton className="h-64" />
      ) : daftar.error || !daftar.data ? (
        <p className="text-sm text-destructive">Daftar pos gagal dimuat: {daftar.error?.message}</p>
      ) : (
        <DaftarPos pos={pos} data={daftar.data} tandai={tandaiNomor} sibuk={sibuk} />
      )}
    </div>
  )
}

/** Pindai QR berkas (pemindai USB atau kamera), atau ketik nomor antrean atau nomor polisi; satu nomor langsung ditandai */
function KotakPindai({
  pos,
  mode,
  gantiMode,
  tandai,
  hasil,
  gagal,
  urungkan,
  sibuk,
}: {
  pos: InfoPos
  mode: TandaPos
  gantiMode: (m: TandaPos) => void
  tandai: (t: TiketAntrean) => Promise<boolean>
  hasil: HasilTanda | null
  gagal: (pesan: string) => void
  urungkan: (t: TiketAntrean) => Promise<void>
  sibuk: boolean
}) {
  const masukan = React.useRef<HTMLInputElement>(null)
  const [q, setQ] = React.useState("")
  const [pilihan, setPilihan] = React.useState<TiketAntrean[] | null>(null)
  const [mencari, setMencari] = React.useState(false)
  const [kamera, setKamera] = React.useState(false)
  const kameraTersedia = typeof window !== "undefined" && "BarcodeDetector" in window && !!navigator.mediaDevices?.getUserMedia

  // Teks dikosongkan sebelum mencari agar pindaian berikutnya tidak tersambung ke pindaian ini
  const proses = async (teks: string) => {
    const isi = teks.trim()
    if (!isi) return
    setQ("")
    setPilihan(null)
    setMencari(true)
    try {
      const daftar = await cariTiket(isi)
      if (daftar.length === 0) gagal("Tidak ada nomor yang masih berjalan dengan nomor, nomor polisi, atau QR ini.")
      else if (daftar.length === 1) await tandai(daftar[0])
      else setPilihan(daftar)
    } catch (e) {
      gagal((e as Error).message)
    } finally {
      setMencari(false)
      masukan.current?.focus()
    }
  }

  const aksi =
    pos.kunci === "server"
      ? "langsung ditandai perubahan datanya selesai dan kembali ke Penetapan"
      : pos.kunci === "penetapan" && mode === "perubahan-data"
        ? "langsung dikirim ke Server untuk perubahan data (hanya pindaian berikutnya; sesudahnya kembali ke Selesai)"
        : `langsung ditandai selesai di ${pos.label}`

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Pindai berkas · {pos.label}</CardTitle>
        <CardDescription>Pindai QR pada berkas, atau ketik nomor antrean atau nomor polisi lalu Enter. Nomornya {aksi}.</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-4">
        {pos.kunci === "penetapan" && (
          <div className="grid gap-1.5">
            <p id="antrean-mode-pindai" className="text-xs text-muted-foreground">
              Pindaian menandai
            </p>
            <ToggleGroup
              type="single"
              variant="outline"
              value={mode}
              onValueChange={(nilai) => nilai && gantiMode(nilai as TandaPos)}
              aria-labelledby="antrean-mode-pindai"
              className="w-full"
            >
              <ToggleGroupItem value="selesai" className="flex-1">
                <IconCheck />
                Selesai
              </ToggleGroupItem>
              <ToggleGroupItem value="perubahan-data" className="flex-1">
                <IconServer />
                Perlu perubahan data
              </ToggleGroupItem>
            </ToggleGroup>
          </div>
        )}
        <form
          className="flex gap-2"
          onSubmit={(e) => {
            e.preventDefault()
            void proses(q)
          }}
        >
          <Input
            ref={masukan}
            autoFocus
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Pindai QR, A-001, atau DH 1234 AB"
            aria-label="Nomor antrean, nomor polisi, atau QR"
            autoComplete="off"
          />
          <Button type="submit" variant="outline" disabled={mencari || sibuk || !q.trim()}>
            <IconScan />
            Tandai
          </Button>
        </form>
        {kameraTersedia && (
          <Button variant="outline" className="justify-self-start" onClick={() => setKamera(true)}>
            <IconCamera />
            Pindai dengan kamera
          </Button>
        )}
        {pilihan && (
          <div className="grid gap-2">
            <p className="text-sm text-muted-foreground">Beberapa nomor cocok. Pilih yang ditandai:</p>
            {pilihan.map((t) => (
              <Button
                key={t.id}
                variant="outline"
                className="h-auto justify-start py-2 text-left"
                disabled={sibuk}
                onClick={() => void tandai(t).then((ok) => ok && setPilihan(null))}
              >
                <span className="grid">
                  <span className="font-mono font-semibold">{nomorBertanggal(t)}</span>
                  <span className="text-xs text-muted-foreground">
                    {t.layanan.nama} · {t.nopol ?? "—"} · {t.tahap ? LABEL_TAHAP[t.tahap] : "—"}
                  </span>
                </span>
              </Button>
            ))}
          </div>
        )}
        <div aria-live="polite">
          {hasil &&
            (hasil.berhasil ? (
              <div className="grid gap-1 rounded-lg border border-success/40 bg-success/5 p-3">
                <p className="text-xs text-muted-foreground">Terakhir ditandai</p>
                <p className="font-medium">{hasil.pesan}</p>
                <p className="font-mono text-sm tracking-wider">{hasil.tiket.nopol ?? "—"}</p>
                <p className="text-sm text-muted-foreground">{hasil.keterangan}</p>
                <Button variant="outline" size="sm" className="mt-1 justify-self-start" disabled={sibuk} onClick={() => void urungkan(hasil.tiket)}>
                  <IconArrowBackUp />
                  Urungkan
                </Button>
              </div>
            ) : (
              <div className="grid gap-1 rounded-lg border border-destructive/40 bg-destructive/5 p-3" role="alert">
                <p className="font-medium text-destructive">{hasil.pesan}</p>
                <p className="text-sm">{hasil.keterangan}</p>
              </div>
            ))}
        </div>
      </CardContent>
      {kameraTersedia && <DialogKamera buka={kamera} tutup={() => setKamera(false)} hasil={(teks) => void proses(teks)} />}
    </Card>
  )
}

interface KodeTerbaca {
  rawValue: string
}

/** BarcodeDetector bawaan peramban (Chrome Android); belum ada di tipe DOM TypeScript */
type PendeteksiKode = new (opsi: { formats: string[] }) => { detect(sumber: HTMLVideoElement): Promise<KodeTerbaca[]> }

/** Membaca QR berkas dari kamera belakang dengan BarcodeDetector, tanpa pustaka tambahan */
function DialogKamera({ buka, tutup, hasil }: { buka: boolean; tutup: () => void; hasil: (teks: string) => void }) {
  const video = React.useRef<HTMLVideoElement>(null)
  const [galat, setGalat] = React.useState<string | null>(null)
  // Fungsi terbaru dari induk tanpa memulai ulang kamera setiap kali induk dirender
  const panggilan = React.useRef({ tutup, hasil })
  React.useEffect(() => {
    panggilan.current = { tutup, hasil }
  })

  React.useEffect(() => {
    if (!buka) return
    let aliran: MediaStream | null = null
    let jeda: number | undefined
    let berhenti = false
    const Pendeteksi = (window as unknown as { BarcodeDetector?: PendeteksiKode }).BarcodeDetector

    const mulai = async () => {
      setGalat(null)
      try {
        if (!Pendeteksi) throw new Error("Peramban ini belum bisa membaca QR dari kamera.")
        const pendeteksi = new Pendeteksi({ formats: ["qr_code"] })
        const s = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false })
        if (berhenti) {
          s.getTracks().forEach((t) => t.stop())
          return
        }
        aliran = s
        const v = video.current
        if (!v) return
        v.srcObject = s
        await v.play()
        const periksa = async () => {
          if (berhenti) return
          try {
            const [kode] = await pendeteksi.detect(v)
            if (kode?.rawValue && !berhenti) {
              berhenti = true
              panggilan.current.tutup()
              panggilan.current.hasil(kode.rawValue)
              return
            }
          } catch {
            // bingkai belum siap; dicoba lagi
          }
          jeda = window.setTimeout(() => void periksa(), 250)
        }
        void periksa()
      } catch (e) {
        if (berhenti) return
        setGalat(
          e instanceof DOMException && e.name === "NotAllowedError"
            ? "Izin kamera ditolak. Izinkan kamera untuk situs ini, atau pakai pemindai atau ketik nomornya."
            : `Kamera tidak bisa dipakai: ${(e as Error).message}`
        )
      }
    }
    void mulai()

    return () => {
      berhenti = true
      window.clearTimeout(jeda)
      aliran?.getTracks().forEach((t) => t.stop())
    }
  }, [buka])

  return (
    <Dialog open={buka} onOpenChange={(terbuka) => !terbuka && tutup()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Pindai QR berkas</DialogTitle>
          <DialogDescription>Arahkan kamera ke QR pada berkas. Nomornya langsung ditandai setelah terbaca.</DialogDescription>
        </DialogHeader>
        {galat ? (
          <p className="text-sm text-destructive" role="alert">
            {galat}
          </p>
        ) : (
          <video ref={video} className="aspect-square w-full rounded-md bg-black object-cover" muted playsInline aria-label="Gambar kamera" />
        )}
      </DialogContent>
    </Dialog>
  )
}

function DaftarPos({ pos, data, tandai, sibuk }: { pos: InfoPos; data: TiketAntrean[]; tandai: (t: TiketAntrean, tanda: TandaPos) => Promise<boolean>; sibuk: boolean }) {
  const judul = pos.kunci === "server" ? "Menunggu perubahan data" : `Di ${pos.label}`
  const deskripsi =
    pos.kunci === "server"
      ? "Berkas yang dikirim Penetapan. Tandai setelah datanya diubah; berkas kembali ke daftar Penetapan."
      : pos.kunci === "pelunasan"
        ? "Wajib pajak membayar di bank dengan kwitansinya. Tandai setelah pelunasannya tercatat. Paling lama menunggu lebih dulu."
        : "Paling lama menunggu lebih dulu, termasuk berkas dari hari sebelumnya."

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">
          {judul} <span className="text-muted-foreground">({data.length})</span>
        </CardTitle>
        <CardDescription>{deskripsi}</CardDescription>
      </CardHeader>
      <CardContent>
        {data.length === 0 ? (
          <p className="text-sm text-muted-foreground">Tidak ada berkas.</p>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nomor</TableHead>
                  <TableHead>Layanan</TableHead>
                  <TableHead>Nomor polisi</TableHead>
                  <TableHead>{pos.kunci === "server" ? "Masuk Penetapan" : "Sejak"}</TableHead>
                  <TableHead>
                    <span className="sr-only">Tindakan</span>
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.map((t) => (
                  <TableRow key={t.id}>
                    <TableCell className="font-mono font-semibold whitespace-nowrap">{nomorBertanggal(t)}</TableCell>
                    <TableCell>{t.layanan.nama}</TableCell>
                    <TableCell className="font-mono whitespace-nowrap">{t.nopol ?? "—"}</TableCell>
                    <TableCell className="whitespace-nowrap">{jamSaja(t.masukTahap)}</TableCell>
                    <TableCell className="text-right">
                      {pos.kunci === "penetapan" && t.status === "perubahan-data" ? (
                        <NadaBadge nada="proses" ikon={IconServer}>
                          Di Server
                        </NadaBadge>
                      ) : (
                        <div className="flex justify-end gap-2">
                          {pos.kunci === "penetapan" && (
                            <Button size="sm" variant="outline" disabled={sibuk} onClick={() => void tandai(t, "perubahan-data")}>
                              Perubahan data
                            </Button>
                          )}
                          <Button size="sm" disabled={sibuk} onClick={() => void tandai(t, "selesai")}>
                            {pos.kunci === "server" ? "Data sudah diubah" : "Selesai"}
                          </Button>
                        </div>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
