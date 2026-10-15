import * as React from "react"
import { IconBan, IconCircleCheck, IconExternalLink, IconSearch, IconTrash } from "@tabler/icons-react"
import { toast } from "sonner"

import { NavAntrean } from "@/components/antrean/nav-antrean"
import { useKonfirmasi } from "@/components/data/confirm"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { NadaBadge } from "@/components/data/status-badge"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import {
  BATAS_ARSIP,
  bolehDihapus,
  jamSaja,
  LABEL_STATUS,
  LABEL_TAHAP,
  labelRiwayat,
  SARINGAN_KOSONG,
  TAHAP,
  useArsipAntrean,
  useDetailTiket,
  useHapusTiket,
  useLayananAntrean,
  type SaringanArsip,
  type StatusTiket,
  type TiketAntrean,
} from "@/lib/antrean"
import { sesi } from "@/lib/auth"
import { hariIniIso, tanggal, tanggalJam } from "@/lib/format"

/*
 * Arsip Antrean Online (D-051 Irisan 4): semua nomor yang pernah dicetak,
 * dengan nomor polisi dan riwayatnya, untuk mencari berkas atau tiket yang
 * tercecer. Semua petugas modul Antrean bisa mencari dan membuka riwayat;
 * Administrator bisa menghapus tiket yang sudah selesai atau batal dari hari
 * sebelumnya beserta riwayatnya (keputusan pemilik).
 */

const SEMUA = "semua"

export default function HalamanArsip() {
  const [isian, setIsian] = React.useState<SaringanArsip>(SARINGAN_KOSONG)
  const [saringan, setSaringan] = React.useState<SaringanArsip>(SARINGAN_KOSONG)
  const [pilih, setPilih] = React.useState<string | null>(null)
  const arsip = useArsipAntrean(saringan)
  const layanan = useLayananAntrean()

  const ubah = <K extends keyof SaringanArsip>(kunci: K, nilai: SaringanArsip[K]) => setIsian((s) => ({ ...s, [kunci]: nilai }))
  const terapkan = (e: React.FormEvent) => {
    e.preventDefault()
    setSaringan(isian)
  }
  const aturUlang = () => {
    setIsian(SARINGAN_KOSONG)
    setSaringan(SARINGAN_KOSONG)
  }
  const data = arsip.data ?? []

  return (
    <Halaman>
      <NavAntrean />
      <PengantarHalaman deskripsi="Cari nomor yang pernah dicetak, termasuk yang sudah selesai atau dibatalkan, untuk menemukan berkas atau tiket yang tercecer. Buka nomornya untuk melihat riwayat lengkap beserta petugas dan loketnya." />
      <Card>
        <CardContent>
          <form className="grid gap-4" onSubmit={terapkan}>
            <div className="grid gap-1.5">
              <Label htmlFor="arsip-q">Nomor antrean, nomor polisi, atau QR</Label>
              <Input
                id="arsip-q"
                value={isian.q}
                onChange={(e) => ubah("q", e.target.value)}
                placeholder="A-001, sebagian nomor polisi seperti 1234, atau pindai QR"
                autoComplete="off"
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
              <div className="grid gap-1.5">
                <Label htmlFor="arsip-dari">Dicetak dari</Label>
                <Input id="arsip-dari" type="date" value={isian.dari} onChange={(e) => ubah("dari", e.target.value)} />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="arsip-sampai">Sampai</Label>
                <Input id="arsip-sampai" type="date" value={isian.sampai} min={isian.dari || undefined} onChange={(e) => ubah("sampai", e.target.value)} />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="arsip-layanan">Layanan</Label>
                <Select value={isian.layanan || SEMUA} onValueChange={(v) => ubah("layanan", v === SEMUA ? "" : v)}>
                  <SelectTrigger id="arsip-layanan" className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SEMUA}>Semua layanan</SelectItem>
                    {(layanan.data ?? []).map((l) => (
                      <SelectItem key={l.kode} value={l.kode}>
                        {l.kode} – {l.nama}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="arsip-status">Status</Label>
                <Select value={isian.status || SEMUA} onValueChange={(v) => ubah("status", v === SEMUA ? "" : (v as StatusTiket))}>
                  <SelectTrigger id="arsip-status" className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SEMUA}>Semua status</SelectItem>
                    {(Object.keys(LABEL_STATUS) as StatusTiket[]).map((s) => (
                      <SelectItem key={s} value={s}>
                        {LABEL_STATUS[s]}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="arsip-tahap">Tahap</Label>
                <Select value={isian.tahap || SEMUA} onValueChange={(v) => ubah("tahap", v === SEMUA ? "" : (v as SaringanArsip["tahap"]))}>
                  <SelectTrigger id="arsip-tahap" className="w-full">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value={SEMUA}>Semua tahap</SelectItem>
                    {TAHAP.map((t) => (
                      <SelectItem key={t.kunci} value={t.kunci}>
                        {t.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="flex flex-wrap gap-2">
              <Button type="submit">
                <IconSearch />
                Cari
              </Button>
              <Button type="button" variant="outline" onClick={aturUlang}>
                Atur ulang
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Hasil {arsip.data && <span className="text-muted-foreground">({data.length})</span>}</CardTitle>
          <CardDescription>
            {data.length >= BATAS_ARSIP
              ? `Menampilkan ${BATAS_ARSIP} nomor terbaru yang cocok. Persempit pencarian bila nomor yang dicari belum tampil.`
              : "Terbaru dulu. Pilih nomor untuk melihat riwayatnya."}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {arsip.isLoading ? (
            <Skeleton className="h-48" />
          ) : arsip.error ? (
            <p className="text-sm text-destructive">Arsip gagal dimuat: {arsip.error.message}</p>
          ) : data.length === 0 ? (
            <p className="text-sm text-muted-foreground">Tidak ada nomor yang cocok.</p>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nomor</TableHead>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Layanan</TableHead>
                    <TableHead>Nomor polisi</TableHead>
                    <TableHead>Keadaan</TableHead>
                    <TableHead>
                      <span className="sr-only">Tindakan</span>
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data.map((t) => (
                    <TableRow key={t.id}>
                      <TableCell className="font-mono font-semibold whitespace-nowrap">{t.nomor}</TableCell>
                      <TableCell className="whitespace-nowrap">{tanggal(t.tanggal)}</TableCell>
                      <TableCell>{t.layanan.nama}</TableCell>
                      <TableCell className="font-mono whitespace-nowrap">{t.nopol ?? "—"}</TableCell>
                      <TableCell>
                        <KeadaanTiket tiket={t} />
                      </TableCell>
                      <TableCell className="text-right">
                        <Button size="sm" variant="outline" onClick={() => setPilih(t.id)}>
                          Riwayat
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
      <SheetRiwayat id={pilih} tutup={() => setPilih(null)} />
    </Halaman>
  )
}

function KeadaanTiket({ tiket: t }: { tiket: TiketAntrean }) {
  if (t.status === "batal")
    return (
      <NadaBadge nada="bahaya" ikon={IconBan}>
        Dibatalkan
      </NadaBadge>
    )
  if (t.status === "selesai")
    return (
      <NadaBadge nada="sukses" ikon={IconCircleCheck}>
        Selesai
      </NadaBadge>
    )
  return (
    <span className="grid justify-items-start gap-0.5">
      <Badge variant="outline">{t.tahap ? LABEL_TAHAP[t.tahap] : "—"}</Badge>
      {t.status !== "menunggu" && <span className="text-xs text-muted-foreground">{t.status === "dipanggil" ? `Dipanggil di ${t.loket ?? "loket"}` : LABEL_STATUS[t.status]}</span>}
    </span>
  )
}

/** Riwayat lengkap satu nomor; Administrator bisa menghapus nomor yang sudah selesai atau batal dari hari sebelumnya */
function SheetRiwayat({ id, tutup }: { id: string | null; tutup: () => void }) {
  const detail = useDetailTiket(id)
  const hapus = useHapusTiket()
  const konfirmasi = useKonfirmasi()
  const administrator = sesi().peran === "administrator"
  const t = detail.data

  const hapusTiket = async () => {
    if (!t) return
    const ya = await konfirmasi({
      judul: `Hapus nomor ${t.nomor} (${tanggal(t.tanggal)})?`,
      deskripsi: "Tiket, nomor polisi, dan seluruh riwayatnya dihapus permanen dan tidak ikut lagi dalam ringkasan. Penghapusan dicatat di log aktivitas tanpa nomor polisi.",
      label: "Hapus permanen",
      bahaya: true,
    })
    if (!ya) return
    try {
      await hapus.mutateAsync(t.id)
      toast.success(`Nomor ${t.nomor} dihapus`)
      tutup()
    } catch (e) {
      toast.error(`Nomor ${t.nomor} gagal dihapus`, { description: (e as Error).message })
    }
  }

  return (
    <Sheet open={!!id} onOpenChange={(buka) => !buka && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
        <SheetHeader className="border-b pr-12">
          <SheetTitle>{t ? `Nomor ${t.nomor} · ${tanggal(t.tanggal)}` : "Riwayat nomor"}</SheetTitle>
          <SheetDescription>{t ? t.layanan.nama : "Memuat…"}</SheetDescription>
        </SheetHeader>
        <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
          {detail.isLoading ? (
            <Skeleton className="h-64" />
          ) : detail.error || !t ? (
            <p className="text-sm text-destructive">Riwayat gagal dimuat: {detail.error?.message}</p>
          ) : (
            <>
              <dl className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
                <dt className="text-muted-foreground">Nomor polisi</dt>
                <dd className="font-mono">{t.nopol ?? "—"}</dd>
                <dt className="text-muted-foreground">Keadaan</dt>
                <dd>
                  <KeadaanTiket tiket={t} />
                </dd>
                <dt className="text-muted-foreground">Dicetak</dt>
                <dd>
                  {tanggalJam(t.dicetak)}
                  {t.dicetakOleh && ` oleh ${t.dicetakOleh}`}
                </dd>
                {t.selesai && (
                  <>
                    <dt className="text-muted-foreground">Selesai</dt>
                    <dd>{tanggalJam(t.selesai)}</dd>
                  </>
                )}
                {t.dibatalkan && (
                  <>
                    <dt className="text-muted-foreground">Dibatalkan</dt>
                    <dd>
                      {tanggalJam(t.dibatalkan)}
                      {t.alasanBatal && <span className="block text-muted-foreground">{t.alasanBatal}</span>}
                    </dd>
                  </>
                )}
              </dl>
              <a href={t.urlLacak} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-sm text-brand underline-offset-4 hover:underline">
                Buka halaman lacak
                <IconExternalLink className="size-4" aria-hidden="true" />
                <span className="sr-only">(tab baru)</span>
              </a>
              <section className="grid gap-3" aria-labelledby="arsip-riwayat">
                <h3 id="arsip-riwayat" className="text-sm font-semibold">
                  Riwayat
                </h3>
                <ol className="grid gap-3 border-l pl-4" role="list">
                  {t.riwayat.map((r, i) => (
                    <li key={i} className="grid gap-0.5 text-sm">
                      <span className="text-xs text-muted-foreground">
                        {r.waktu && hariIniIso(new Date(r.waktu)) === t.tanggal ? jamSaja(r.waktu) : tanggalJam(r.waktu)}
                      </span>
                      <span className="font-medium">
                        {labelRiwayat(r)}
                        {r.otomatis && (
                          <Badge variant="secondary" className="ml-2 align-middle">
                            Otomatis
                          </Badge>
                        )}
                      </span>
                      {r.aksi === "dibatalkan" && r.keterangan && <span className="text-muted-foreground">Alasan: {r.keterangan}</span>}
                      {(r.petugas || (r.loket && !r.aksi.startsWith("dipanggil"))) && (
                        <span className="text-xs text-muted-foreground">{[r.petugas, r.aksi.startsWith("dipanggil") ? null : r.loket].filter(Boolean).join(" · ")}</span>
                      )}
                    </li>
                  ))}
                </ol>
              </section>
            </>
          )}
        </div>
        {administrator && t && (
          <SheetFooter className="border-t">
            {bolehDihapus(t) ? (
              <Button variant="destructive" disabled={hapus.isPending} onClick={() => void hapusTiket()}>
                <IconTrash />
                Hapus nomor ini
              </Button>
            ) : (
              <p className="text-xs text-muted-foreground">
                {t.status === "selesai" || t.status === "batal"
                  ? "Nomor hari ini baru bisa dihapus mulai besok, agar nomornya tidak terpakai dua kali."
                  : "Nomor yang masih berjalan harus dibatalkan dulu sebelum dihapus."}
              </p>
            )}
          </SheetFooter>
        )}
      </SheetContent>
    </Sheet>
  )
}
