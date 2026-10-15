import * as React from "react"
import { IconArrowLeft, IconDeviceFloppy } from "@tabler/icons-react"
import { Link } from "react-router"
import { toast } from "sonner"

import { Halaman, PengantarHalaman } from "@/components/data/page"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Skeleton } from "@/components/ui/skeleton"
import { Switch } from "@/components/ui/switch"
import { TAHAP, useLayananAntrean, useSimpanLayananAntrean, type LayananAntrean, type TahapAntrean } from "@/lib/antrean"
import { sesi } from "@/lib/auth"

/*
 * Pengaturan layanan Antrean Online (D-051), khusus Administrator: nama,
 * cakupan, tahap yang dilalui, dan apakah nomornya bisa dicetak. Huruf kode
 * tetap, karena tercetak pada nomor. Nomor yang sudah dicetak mengikuti tahap
 * saat ia dicetak.
 */
export default function HalamanPengaturanAntrean() {
  const layanan = useLayananAntrean()
  const administrator = sesi().peran === "administrator"

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Atur nama, cakupan, dan tahap setiap layanan antrean. Perubahan tahap berlaku untuk nomor yang dicetak sesudahnya.">
        <Button variant="outline" size="sm" asChild>
          <Link to="/antrean">
            <IconArrowLeft />
            Meja Informasi
          </Link>
        </Button>
      </PengantarHalaman>
      {!administrator ? (
        <Alert>
          <AlertTitle>Khusus Administrator</AlertTitle>
          <AlertDescription>Layanan antrean hanya bisa diubah Administrator.</AlertDescription>
        </Alert>
      ) : layanan.isLoading ? (
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
    </Halaman>
  )
}

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
