import * as React from "react"
import { IconCopy, IconDeviceDesktopPlus, IconDeviceFloppy, IconPencil, IconPlus, IconTrash } from "@tabler/icons-react"
import { toast } from "sonner"

import { NavAntrean } from "@/components/antrean/nav-antrean"
import { useKonfirmasi } from "@/components/data/confirm"
import { Halaman, PengantarHalaman } from "@/components/data/page"
import { NadaBadge } from "@/components/data/status-badge"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"
import { Switch } from "@/components/ui/switch"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import {
  LABEL_TAHAP,
  TAHAP,
  TAHAP_PANGGIL,
  useCabutLayar,
  useHapusLoket,
  useLayananAntrean,
  useLayarAntrean,
  useLoketAntrean,
  useSimpanLayananAntrean,
  useSimpanLoket,
  useTambahLayar,
  type LayananAntrean,
  type LoketAntrean,
  type TahapAntrean,
} from "@/lib/antrean"
import { sesi } from "@/lib/auth"
import { tanggalJam, waktuRelatif } from "@/lib/format"

/*
 * Pengaturan Antrean Online (D-051), khusus Administrator: layanan (nama,
 * cakupan, tahap, aktif; huruf kode tetap karena tercetak pada nomor), loket
 * yang memanggil nomor, dan layar TV ruang tunggu. Nomor yang sudah dicetak
 * mengikuti tahap saat ia dicetak.
 */
export default function HalamanPengaturanAntrean() {
  const administrator = sesi().peran === "administrator"

  return (
    <Halaman>
      <NavAntrean />
      <PengantarHalaman deskripsi="Atur layanan, loket yang memanggil nomor, dan layar TV ruang tunggu. Perubahan tahap layanan berlaku untuk nomor yang dicetak sesudahnya." />
      {!administrator ? (
        <Alert>
          <AlertTitle>Khusus Administrator</AlertTitle>
          <AlertDescription>Layanan, loket, dan layar antrean hanya bisa diatur Administrator.</AlertDescription>
        </Alert>
      ) : (
        <>
          <BagianLayanan />
          <BagianLoket />
          <BagianLayar />
        </>
      )}
    </Halaman>
  )
}

function BagianLayanan() {
  const layanan = useLayananAntrean()
  return (
    <section className="grid gap-3" aria-labelledby="antrean-judul-layanan">
      <h2 id="antrean-judul-layanan" className="text-lg font-semibold">
        Layanan
      </h2>
      {layanan.isLoading ? (
        <div className="grid gap-4 xl:grid-cols-2">
          {[0, 1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-72" />
          ))}
        </div>
      ) : layanan.error ? (
        <p className="text-sm text-destructive">Layanan gagal dimuat: {layanan.error.message}</p>
      ) : (
        <div className="grid gap-4 xl:grid-cols-2">
          {(layanan.data ?? []).map((l) => (
            <KartuLayanan key={l.id} layanan={l} />
          ))}
        </div>
      )}
    </section>
  )
}

/* ---------- Loket ---------- */

const LOKET_BARU: Omit<LoketAntrean, "id"> = { nama: "", tahap: "pendaftaran", layanan: [], namaSuara: "", aktif: true }

function BagianLoket() {
  const loket = useLoketAntrean()
  const layanan = useLayananAntrean()
  const hapus = useHapusLoket()
  const konfirmasi = useKonfirmasi()
  const [sunting, setSunting] = React.useState<(Omit<LoketAntrean, "id"> & { id?: string }) | null>(null)

  const hapusLoket = async (l: LoketAntrean) => {
    const ya = await konfirmasi({
      judul: `Hapus ${l.nama}?`,
      deskripsi: "Nomor yang sedang dipanggil di loket ini pindah ke daftar Ditunda. Riwayat panggilannya tetap tersimpan.",
      label: "Hapus",
      bahaya: true,
    })
    if (!ya) return
    try {
      await hapus.mutateAsync(l.id)
      toast.success(`${l.nama} dihapus`)
    } catch (e) {
      toast.error("Loket gagal dihapus", { description: (e as Error).message })
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Loket</CardTitle>
        <CardDescription>Loket yang memanggil nomor ke Loket 1 – Pendaftaran, Loket Kwitansi, atau Loket 2 – Penyerahan. Nama yang dibacakan dipakai suara layar TV.</CardDescription>
        <CardAction>
          <Button size="sm" onClick={() => setSunting({ ...LOKET_BARU })}>
            <IconPlus />
            Tambah loket
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent>
        {loket.isLoading ? (
          <Skeleton className="h-32" />
        ) : loket.error ? (
          <p className="text-sm text-destructive">Loket gagal dimuat: {loket.error.message}</p>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Loket</TableHead>
                  <TableHead>Tahap</TableHead>
                  <TableHead>Layanan</TableHead>
                  <TableHead>Dibacakan</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>
                    <span className="sr-only">Tindakan</span>
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {(loket.data ?? []).map((l) => (
                  <TableRow key={l.id}>
                    <TableCell className="font-medium">{l.nama}</TableCell>
                    <TableCell>{LABEL_TAHAP[l.tahap]}</TableCell>
                    <TableCell>{l.layanan.length ? l.layanan.join(", ") : "Semua"}</TableCell>
                    <TableCell className="text-muted-foreground">“{l.namaSuara}”</TableCell>
                    <TableCell>
                      <NadaBadge nada={l.aktif ? "sukses" : "netral"}>{l.aktif ? "Dipakai" : "Tidak dipakai"}</NadaBadge>
                    </TableCell>
                    <TableCell className="text-right whitespace-nowrap">
                      <Button size="icon" variant="ghost" onClick={() => setSunting(l)} aria-label={`Ubah ${l.nama}`}>
                        <IconPencil />
                      </Button>
                      <Button size="icon" variant="ghost" onClick={() => void hapusLoket(l)} aria-label={`Hapus ${l.nama}`}>
                        <IconTrash />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
      <DialogLoket loket={sunting} layanan={layanan.data ?? []} tutup={() => setSunting(null)} />
    </Card>
  )
}

function DialogLoket({
  loket,
  layanan,
  tutup,
}: {
  loket: (Omit<LoketAntrean, "id"> & { id?: string }) | null
  layanan: LayananAntrean[]
  tutup: () => void
}) {
  const simpan = useSimpanLoket()
  const [isi, setIsi] = React.useState<Omit<LoketAntrean, "id"> & { id?: string }>(loket ?? LOKET_BARU)
  const [galat, setGalat] = React.useState<string | null>(null)

  React.useEffect(() => {
    if (loket) setIsi(loket)
    setGalat(null)
  }, [loket])

  const ubahLayanan = (kode: string, pilih: boolean) =>
    setIsi((lama) => ({ ...lama, layanan: layanan.map((l) => l.kode).filter((k) => (k === kode ? pilih : lama.layanan.includes(k))) }))

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!isi.nama.trim()) return setGalat("Tulis nama loket.")
    if (!isi.namaSuara.trim()) return setGalat("Tulis nama loket yang dibacakan di layar.")
    try {
      await simpan.mutateAsync({ ...isi, nama: isi.nama.trim(), namaSuara: isi.namaSuara.trim() })
      toast.success(isi.id ? `${isi.nama.trim()} disimpan` : `${isi.nama.trim()} ditambahkan`)
      tutup()
    } catch (err) {
      setGalat((err as Error).message)
    }
  }

  return (
    <Dialog open={!!loket} onOpenChange={(buka) => !buka && tutup()}>
      <DialogContent>
        <form className="grid gap-4" onSubmit={(e) => void kirim(e)} noValidate>
          <DialogHeader>
            <DialogTitle>{loket?.id ? `Ubah ${loket.nama}` : "Tambah loket"}</DialogTitle>
            <DialogDescription>Loket memanggil nomor yang sampai di tahapnya. Tanpa layanan yang dipilih, Panggil berikutnya mengambil semua layanan.</DialogDescription>
          </DialogHeader>
          <div className="grid gap-2">
            <Label htmlFor="loket-nama">Nama loket</Label>
            <Input id="loket-nama" value={isi.nama} maxLength={60} onChange={(e) => setIsi({ ...isi, nama: e.target.value })} placeholder="Mis. Loket 1A – Pendaftaran" />
          </div>
          <div className="grid gap-2">
            <Label htmlFor="loket-tahap">Tahap</Label>
            <Select value={isi.tahap} onValueChange={(v) => setIsi({ ...isi, tahap: v as TahapAntrean })}>
              <SelectTrigger id="loket-tahap" className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {TAHAP_PANGGIL.map((t) => (
                  <SelectItem key={t} value={t}>
                    {LABEL_TAHAP[t]}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <fieldset className="grid gap-2">
            <legend className="mb-2 text-sm leading-none font-medium">Layanan yang dipanggil berikutnya</legend>
            <div className="flex flex-wrap gap-4">
              {layanan.map((l) => (
                <Label key={l.kode} htmlFor={`loket-layanan-${l.kode}`} className="flex items-center gap-2 font-normal">
                  <Checkbox id={`loket-layanan-${l.kode}`} checked={isi.layanan.includes(l.kode)} onCheckedChange={(c) => ubahLayanan(l.kode, c === true)} />
                  {l.kode} · {l.nama}
                </Label>
              ))}
            </div>
          </fieldset>
          <div className="grid gap-2">
            <Label htmlFor="loket-suara">Dibacakan di layar</Label>
            <Input
              id="loket-suara"
              value={isi.namaSuara}
              maxLength={80}
              onChange={(e) => setIsi({ ...isi, namaSuara: e.target.value })}
              placeholder="Mis. Loket satu A, pendaftaran"
              aria-describedby="loket-suara-ket"
            />
            <p id="loket-suara-ket" className="text-xs text-muted-foreground">
              Tulis angka dengan huruf agar dibaca jelas: “Silakan ke {isi.namaSuara.trim() || "…"}.”
            </p>
          </div>
          <Label htmlFor="loket-aktif" className="flex items-center gap-3 font-normal">
            <Switch id="loket-aktif" checked={isi.aktif} onCheckedChange={(aktif) => setIsi({ ...isi, aktif })} />
            {isi.aktif ? "Dipakai" : "Tidak dipakai"}
          </Label>
          {galat && <p className="text-sm text-destructive">{galat}</p>}
          <DialogFooter>
            <Button type="button" variant="outline" onClick={tutup}>
              Batal
            </Button>
            <Button type="submit" disabled={simpan.isPending}>
              <IconDeviceFloppy />
              Simpan
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}

/* ---------- Layar ---------- */

function BagianLayar() {
  const layar = useLayarAntrean(true)
  const tambah = useTambahLayar()
  const cabut = useCabutLayar()
  const konfirmasi = useKonfirmasi()
  const [nama, setNama] = React.useState("")
  const [membuat, setMembuat] = React.useState(false)
  const [tautan, setTautan] = React.useState<{ nama: string; tautan: string } | null>(null)
  const [galat, setGalat] = React.useState<string | null>(null)

  const buat = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!nama.trim()) return setGalat("Tulis nama layar, mis. TV ruang tunggu.")
    try {
      const hasil = await tambah.mutateAsync(nama.trim())
      setTautan({ nama: hasil.layar.nama, tautan: hasil.tautan })
      setMembuat(false)
      setNama("")
      setGalat(null)
    } catch (err) {
      setGalat((err as Error).message)
    }
  }

  const cabutLayar = async (id: string, namaLayar: string) => {
    const ya = await konfirmasi({
      judul: `Cabut layar ${namaLayar}?`,
      deskripsi: "Layar itu langsung berhenti menampilkan panggilan. Untuk memakainya lagi, buat tautan pemasangan baru.",
      label: "Cabut",
      bahaya: true,
    })
    if (!ya) return
    try {
      await cabut.mutateAsync(id)
      toast.success(`Layar ${namaLayar} dicabut`)
    } catch (e) {
      toast.error("Layar gagal dicabut", { description: (e as Error).message })
    }
  }

  const salin = async () => {
    if (!tautan) return
    try {
      await navigator.clipboard.writeText(tautan.tautan)
      toast.success("Tautan disalin")
    } catch {
      toast.error("Tautan tidak bisa disalin otomatis", { description: "Pilih isi kotak tautan lalu salin secara manual." })
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Layar TV ruang tunggu</CardTitle>
        <CardDescription>
          Layar menampilkan dan membacakan nomor yang dipanggil, beserta nomor polisinya. Karena itu hanya perangkat yang dipasang dari sini yang bisa membukanya.
        </CardDescription>
        <CardAction>
          <Button size="sm" onClick={() => setMembuat(true)}>
            <IconDeviceDesktopPlus />
            Tambah layar
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent>
        {layar.isLoading ? (
          <Skeleton className="h-20" />
        ) : layar.error ? (
          <p className="text-sm text-destructive">Layar gagal dimuat: {layar.error.message}</p>
        ) : (layar.data ?? []).length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada layar yang dipasang.</p>
        ) : (
          <ul className="grid gap-2" role="list">
            {(layar.data ?? []).map((l) => (
              <li key={l.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3">
                <span className="grid">
                  <span className="font-medium">{l.nama}</span>
                  <span className="text-xs text-muted-foreground">
                    Dipasang {tanggalJam(l.dibuat)} oleh {l.dibuatOleh ?? "—"} ·{" "}
                    {l.terakhirTerlihat ? `terakhir aktif ${waktuRelatif(l.terakhirTerlihat)}` : "belum pernah dibuka"}
                  </span>
                </span>
                <Button size="sm" variant="outline" onClick={() => void cabutLayar(l.id, l.nama)}>
                  Cabut
                </Button>
              </li>
            ))}
          </ul>
        )}
      </CardContent>

      <Dialog open={membuat} onOpenChange={(buka) => !buka && setMembuat(false)}>
        <DialogContent>
          <form className="grid gap-4" onSubmit={(e) => void buat(e)} noValidate>
            <DialogHeader>
              <DialogTitle>Tambah layar</DialogTitle>
              <DialogDescription>Anda akan mendapat tautan pemasangan yang hanya ditampilkan sekali. Buka tautan itu di peramban perangkat yang tersambung ke TV.</DialogDescription>
            </DialogHeader>
            <div className="grid gap-2">
              <Label htmlFor="layar-nama">Nama layar</Label>
              <Input id="layar-nama" value={nama} maxLength={60} onChange={(e) => setNama(e.target.value)} placeholder="Mis. TV ruang tunggu" />
            </div>
            {galat && <p className="text-sm text-destructive">{galat}</p>}
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setMembuat(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={tambah.isPending}>
                Buat tautan
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={!!tautan} onOpenChange={(buka) => !buka && setTautan(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>Tautan pemasangan {tautan?.nama}</DialogTitle>
            <DialogDescription>
              Buka tautan ini sekali di peramban perangkat TV (Chrome atau Edge, layar penuh dengan F11), lalu tekan Aktifkan suara. Perangkat itu menyimpan kuncinya sendiri.
            </DialogDescription>
          </DialogHeader>
          <div className="flex gap-2">
            <Input readOnly value={tautan?.tautan ?? ""} aria-label="Tautan pemasangan layar" className="font-mono text-xs" onFocus={(e) => e.currentTarget.select()} />
            <Button onClick={() => void salin()}>
              <IconCopy />
              Salin
            </Button>
          </div>
          <p className="text-sm text-muted-foreground">Tautan ini tidak bisa ditampilkan lagi. Jangan dibagikan: siapa pun yang membukanya bisa melihat nomor polisi yang dipanggil. Bila hilang, cabut layar lalu buat yang baru.</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setTautan(null)}>
              Selesai
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </Card>
  )
}

/* ---------- Layanan ---------- */

function KartuLayanan({ layanan }: { layanan: LayananAntrean }) {
  const simpan = useSimpanLayananAntrean()
  const [nama, setNama] = React.useState(layanan.nama)
  const [cakupan, setCakupan] = React.useState(layanan.cakupan)
  const [tahap, setTahap] = React.useState<TahapAntrean[]>(layanan.tahap)
  const [aktif, setAktif] = React.useState(layanan.aktif)
  const [galat, setGalat] = React.useState<string | null>(null)
  const id = `layanan-${layanan.kode}`

  const berubah =
    nama !== layanan.nama || cakupan !== layanan.cakupan || aktif !== layanan.aktif || tahap.join() !== layanan.tahap.join()

  const ubahTahap = (kunci: TahapAntrean, pilih: boolean) =>
    setTahap((lama) => TAHAP.map((t) => t.kunci).filter((k) => (k === kunci ? pilih : lama.includes(k))))

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!nama.trim()) return setGalat("Tulis nama layanan.")
    if (tahap.length === 0) return setGalat("Pilih sedikitnya satu tahap.")
    setGalat(null)
    try {
      await simpan.mutateAsync({ id: layanan.id, nama: nama.trim(), cakupan: cakupan.trim(), tahap, aktif })
      toast.success(`Layanan ${layanan.kode} disimpan`)
    } catch (err) {
      setGalat((err as Error).message)
    }
  }

  return (
    <Card>
      <form onSubmit={(e) => void kirim(e)} noValidate className="grid gap-6">
        <CardHeader>
          <CardTitle className="flex items-baseline gap-2 text-base">
            <span className="font-mono text-2xl">{layanan.kode}</span>
            {layanan.nama}
          </CardTitle>
          <CardDescription>Nomor tercetak {layanan.kode}-001, {layanan.kode}-002, dan seterusnya setiap hari.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-5">
          <div className="grid gap-2">
            <Label htmlFor={`${id}-nama`}>Nama layanan</Label>
            <Input id={`${id}-nama`} value={nama} onChange={(e) => setNama(e.target.value)} maxLength={80} />
          </div>
          <div className="grid gap-2">
            <Label htmlFor={`${id}-cakupan`}>Cakupan</Label>
            <Input id={`${id}-cakupan`} value={cakupan} onChange={(e) => setCakupan(e.target.value)} maxLength={200} placeholder="Mis. Registrasi Ulang, Duplikat STNK" />
          </div>
          <fieldset className="grid gap-2">
            <legend className="mb-2 text-sm leading-none font-medium">Tahap yang dilalui</legend>
            <div className="grid gap-2 sm:grid-cols-2">
              {TAHAP.map((t) => (
                <Label key={t.kunci} htmlFor={`${id}-${t.kunci}`} className="flex items-center gap-2 font-normal">
                  <Checkbox id={`${id}-${t.kunci}`} checked={tahap.includes(t.kunci)} onCheckedChange={(c) => ubahTahap(t.kunci, c === true)} />
                  {t.label}
                </Label>
              ))}
            </div>
          </fieldset>
          <Label htmlFor={`${id}-aktif`} className="flex items-center gap-3 font-normal">
            <Switch id={`${id}-aktif`} checked={aktif} onCheckedChange={setAktif} />
            {aktif ? "Aktif: nomor bisa dicetak" : "Tidak aktif: tidak muncul di Meja Informasi"}
          </Label>
          {galat && <p className="text-sm text-destructive">{galat}</p>}
        </CardContent>
        <CardFooter>
          <Button type="submit" disabled={!berubah || simpan.isPending}>
            <IconDeviceFloppy />
            Simpan
          </Button>
        </CardFooter>
      </form>
    </Card>
  )
}
