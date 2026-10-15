import * as React from "react"
import { IconCheck, IconPlayerPause, IconPlayerTrackNext, IconScan, IconVolume } from "@tabler/icons-react"
import { toast } from "sonner"

import { NavAntrean } from "@/components/antrean/nav-antrean"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import {
  cariTiket,
  LABEL_TAHAP,
  tahapBerikutnya,
  useLoketAntrean,
  useMejaLoket,
  usePanggil,
  useTindakanPanggilan,
  type LoketAntrean,
  type TiketAntrean,
} from "@/lib/antrean"
import { hariIniIso, tanggal, tanggalJam } from "@/lib/format"

/*
 * Loket Antrean Online (D-051 Irisan 2): petugas memilih loketnya, lalu
 * memanggil nomor berikutnya atau nomor tertentu (diketik, nomor polisi, atau
 * QR berkas yang dipindai). Nomor yang dipanggil tampil dan dibacakan di layar
 * ruang tunggu. Panggil berikutnya menunda otomatis nomor yang masih
 * dipanggil (keputusan pemilik).
 */

const KUNCI_LOKET = "pintu-loket-antrean"

function loketTersimpan(): string | null {
  try {
    return window.localStorage.getItem(KUNCI_LOKET)
  } catch {
    return null
  }
}

function simpanLoket(id: string) {
  try {
    window.localStorage.setItem(KUNCI_LOKET, id)
  } catch {
    // penyimpanan tidak tersedia: pilihan berlaku sampai halaman ditutup
  }
}

/** "A-001" atau "A-001 · 14 Okt" untuk nomor hari sebelumnya */
function nomorBertanggal(t: Pick<TiketAntrean, "nomor" | "tanggal">): string {
  return t.tanggal === hariIniIso() ? t.nomor : `${t.nomor} · ${tanggal(t.tanggal)}`
}

const jamSaja = (iso: string | null) => (iso ? tanggalJam(iso).split(", ").pop() : "—")

export default function HalamanLoket() {
  const loket = useLoketAntrean()
  const aktif = (loket.data ?? []).filter((l) => l.aktif)
  const [pilih, setPilih] = React.useState<string | null>(loketTersimpan)
  const dipilih = aktif.find((l) => l.id === pilih) ?? null

  const ganti = (id: string) => {
    setPilih(id)
    simpanLoket(id)
  }

  return (
    <Halaman>
      <NavAntrean />
      <PengantarHalaman deskripsi="Panggil nomor ke loket Anda. Nomor yang dipanggil tampil dan dibacakan di layar ruang tunggu. Pilihan loket diingat di perangkat ini.">
        {aktif.length > 0 && (
          <div className="grid gap-1.5">
            <Label htmlFor="antrean-pilih-loket" className="text-xs text-muted-foreground">
              Loket saya
            </Label>
            <Select value={dipilih?.id ?? ""} onValueChange={ganti}>
              <SelectTrigger id="antrean-pilih-loket" className="w-64">
                <SelectValue placeholder="Pilih loket" />
              </SelectTrigger>
              <SelectContent>
                {aktif.map((l) => (
                  <SelectItem key={l.id} value={l.id}>
                    {l.nama}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        )}
      </PengantarHalaman>
      {loket.isLoading ? (
        <Skeleton className="h-64" />
      ) : loket.error ? (
        <p className="text-sm text-destructive">Daftar loket gagal dimuat: {loket.error.message}</p>
      ) : !dipilih ? (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Pilih loket Anda</CardTitle>
            <CardDescription>{aktif.length ? "Loket yang Anda layani hari ini." : "Belum ada loket yang dipakai. Administrator menambahkannya di Pengaturan."}</CardDescription>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {aktif.map((l) => (
              <Button key={l.id} variant="outline" onClick={() => ganti(l.id)}>
                {l.nama}
              </Button>
            ))}
          </CardContent>
        </Card>
      ) : (
        <MejaLoket key={dipilih.id} loket={dipilih} />
      )}
    </Halaman>
  )
}

function MejaLoket({ loket }: { loket: LoketAntrean }) {
  const meja = useMejaLoket(loket.id)
  const panggil = usePanggil()
  const tindakan = useTindakanPanggilan()
  const d = meja.data
  const sibuk = panggil.isPending || tindakan.isPending

  const panggilNomor = async (tiket?: TiketAntrean) => {
    try {
      const t = await panggil.mutateAsync({ loket: loket.id, tiket: tiket?.id })
      toast.success(`Nomor ${nomorBertanggal(t)} dipanggil`, { description: t.nopol ?? undefined })
      return true
    } catch (e) {
      toast.error(tiket ? `Nomor ${tiket.nomor} gagal dipanggil` : "Nomor berikutnya gagal dipanggil", { description: (e as Error).message })
      return false
    }
  }

  const lakukan = async (t: TiketAntrean, aksi: "panggil-ulang" | "tunda" | "selesai") => {
    try {
      await tindakan.mutateAsync({ id: t.id, tindakan: aksi })
      toast.success(aksi === "panggil-ulang" ? `Nomor ${t.nomor} dipanggil ulang` : aksi === "tunda" ? `Nomor ${t.nomor} ditunda` : `Nomor ${t.nomor} selesai di ${loket.nama}`)
    } catch (e) {
      toast.error("Tindakan gagal", { description: (e as Error).message })
    }
  }

  if (meja.isLoading) return <Skeleton className="h-64" />
  if (meja.error || !d) return <p className="text-sm text-destructive">Antrean loket gagal dimuat: {meja.error?.message}</p>

  const t = d.dipanggil
  const lanjut = t ? tahapBerikutnya(t) : null

  return (
    <div className="grid gap-4">
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">{loket.nama}</CardTitle>
            <CardDescription>
              {LABEL_TAHAP[loket.tahap]} · {loket.layanan.length ? `layanan ${loket.layanan.join(", ")}` : "semua layanan"}
            </CardDescription>
          </CardHeader>
          <CardContent className="grid gap-5">
            {t ? (
              <div className="grid gap-1 rounded-lg border bg-muted/40 p-4" aria-live="polite">
                <p className="text-xs text-muted-foreground">Sedang dipanggil</p>
                <p className="font-mono text-5xl font-bold tracking-tight">{nomorBertanggal(t)}</p>
                <p className="font-medium">{t.layanan.nama}</p>
                <p className="font-mono text-lg tracking-wider">{t.nopol ?? "—"}</p>
                <p className="text-xs text-muted-foreground">
                  Dipanggil {t.jumlahPanggil}× · terakhir {jamSaja(t.dipanggil)}
                </p>
                <div className="mt-3 flex flex-wrap gap-2">
                  <Button variant="outline" disabled={sibuk} onClick={() => void lakukan(t, "panggil-ulang")}>
                    <IconVolume />
                    Panggil ulang
                  </Button>
                  <Button variant="outline" disabled={sibuk} onClick={() => void lakukan(t, "tunda")}>
                    <IconPlayerPause />
                    Tunda
                  </Button>
                  <Button disabled={sibuk} onClick={() => void lakukan(t, "selesai")}>
                    <IconCheck />
                    {lanjut ? `Selesai · lanjut ke ${LABEL_TAHAP[lanjut]}` : "Selesai · berkas diserahkan"}
                  </Button>
                </div>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">Belum ada nomor yang dipanggil di loket ini.</p>
            )}
            <div className="grid gap-1.5">
              <Button size="lg" className="h-14 text-base" disabled={sibuk || d.menunggu.length === 0} onClick={() => void panggilNomor()}>
                <IconPlayerTrackNext />
                Panggil berikutnya
                <Badge variant="secondary" className="ml-1">
                  {d.menunggu.length} menunggu
                </Badge>
              </Button>
              {t && d.menunggu.length > 0 && <p className="text-xs text-muted-foreground">Nomor {t.nomor} otomatis ditunda bila Anda memanggil nomor berikutnya.</p>}
            </div>
          </CardContent>
        </Card>
        <PanggilTertentu loket={loket} panggil={panggilNomor} sibuk={sibuk} />
      </div>
      <div className="grid gap-4 xl:grid-cols-2">
        <DaftarNomor judul="Menunggu" deskripsi="Urut sejak masuk tahap ini; nomor hari sebelumnya lebih dulu." data={d.menunggu} waktu={(x) => x.masukTahap} panggil={panggilNomor} sibuk={sibuk} />
        <DaftarNomor judul="Ditunda" deskripsi="Sudah dipanggil tetapi wajib pajak belum datang. Panggil lagi kapan saja." data={d.ditunda} waktu={(x) => x.dipanggil} panggil={panggilNomor} sibuk={sibuk} />
      </div>
    </div>
  )
}

/** Panggil nomor tertentu: pindai QR berkas (pemindai USB mengetik lalu Enter), ketik nomor, atau nomor polisi */
function PanggilTertentu({ loket, panggil, sibuk }: { loket: LoketAntrean; panggil: (t: TiketAntrean) => Promise<boolean>; sibuk: boolean }) {
  const [q, setQ] = React.useState("")
  const [hasil, setHasil] = React.useState<TiketAntrean[] | null>(null)
  const [mencari, setMencari] = React.useState(false)
  const [galat, setGalat] = React.useState<string | null>(null)

  const cari = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!q.trim()) return
    setMencari(true)
    setGalat(null)
    try {
      const daftar = await cariTiket(q.trim())
      if (daftar.length === 0) {
        setHasil(null)
        setGalat("Tidak ada nomor yang masih berjalan dengan nomor, nomor polisi, atau QR ini.")
      } else if (daftar.length === 1) {
        setHasil(null)
        if (await panggil(daftar[0])) setQ("")
      } else {
        setHasil(daftar)
      }
    } catch (err) {
      setGalat((err as Error).message)
    } finally {
      setMencari(false)
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Panggil nomor tertentu</CardTitle>
        <CardDescription>Pindai QR pada berkas, atau ketik nomor antrean atau nomor polisi. Tahap sebelum {loket.nama} yang belum ditandai dianggap selesai.</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-3">
        <form className="flex gap-2" onSubmit={(e) => void cari(e)}>
          <Input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="A-001, DH 1234 AB, atau pindai QR"
            aria-label="Nomor antrean, nomor polisi, atau QR"
            autoComplete="off"
            aria-invalid={!!galat}
          />
          <Button type="submit" variant="outline" disabled={mencari || sibuk || !q.trim()}>
            <IconScan />
            Panggil
          </Button>
        </form>
        {galat && <p className="text-sm text-destructive">{galat}</p>}
        {hasil && (
          <div className="grid gap-2">
            <p className="text-sm text-muted-foreground">Beberapa nomor cocok. Pilih yang dipanggil:</p>
            {hasil.map((t) => (
              <Button
                key={t.id}
                variant="outline"
                className="h-auto justify-start py-2 text-left"
                disabled={sibuk}
                onClick={() => void panggil(t).then((ok) => ok && (setHasil(null), setQ("")))}
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
      </CardContent>
    </Card>
  )
}

function DaftarNomor({
  judul,
  deskripsi,
  data,
  waktu,
  panggil,
  sibuk,
}: {
  judul: string
  deskripsi: string
  data: TiketAntrean[]
  waktu: (t: TiketAntrean) => string | null
  panggil: (t: TiketAntrean) => Promise<boolean>
  sibuk: boolean
}) {
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
          <p className="text-sm text-muted-foreground">Tidak ada.</p>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Nomor</TableHead>
                  <TableHead>Layanan</TableHead>
                  <TableHead>Nomor polisi</TableHead>
                  <TableHead>Sejak</TableHead>
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
                    <TableCell className="whitespace-nowrap">{jamSaja(waktu(t))}</TableCell>
                    <TableCell className="text-right">
                      <Button size="sm" variant="outline" disabled={sibuk} onClick={() => void panggil(t)}>
                        Panggil
                      </Button>
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
