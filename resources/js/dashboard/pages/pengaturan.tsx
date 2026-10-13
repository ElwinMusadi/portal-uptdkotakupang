import * as React from "react"
import { useQueryClient } from "@tanstack/react-query"
import {
  IconDatabase,
  IconDownload,
  IconInfoCircle,
  IconLogout,
  IconRestore,
  IconTrash,
  IconUpload,
} from "@tabler/icons-react"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { useKonfirmasi } from "@/components/data/confirm"
import { Bagian, Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { FieldArea, FieldPilih, FieldSakelar, FieldTeks } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { api, ApiError, diServer, type Snapshot } from "@/lib/api"
import { bolehModul, keluar, LABEL_PERAN, sesi } from "@/lib/auth"
import { tanggalJam } from "@/lib/format"
import { KOLEKSI_LABEL, LABEL_MODUL_SERVER } from "@/lib/meta"
import { laporGalat, useKoleksi } from "@/lib/queries"
import * as v from "@/lib/validasi"

const skemaSitus = z.object({
  namaPortal: v.wajib("Nama portal"),
  kepanjangan: v.teks,
  namaInstansi: v.wajib("Nama instansi"),
  namaSingkat: v.teks,
  induk: v.teks,
  deskripsi: v.teks,
  urlPortal: v.wajib("Alamat portal"),
  hakCipta: v.teks,
  bilahPengumuman: z.object({
    aktif: z.boolean(),
    teks: v.teks,
    tautanLabel: v.teks,
    tautanUrl: v.tautan,
  }),
})

const skemaAkun = z.object({
  nama: v.wajib("Nama"),
  jabatan: v.teks,
  unit: v.teks,
  peran: v.wajib("Peran"),
  email: v.email,
})

function TabSitus() {
  return (
    <FormPengaturan nama="situs" schema={skemaSitus}>
      {(form) => (
        <>
          <Bagian judul="Identitas portal" deskripsi="Dipakai di kepala & kaki halaman portal serta judul tab peramban.">
            <Baris>
              <FieldTeks control={form.control} name="namaPortal" label="Nama portal" wajib />
              <FieldTeks control={form.control} name="kepanjangan" label="Kepanjangan" />
            </Baris>
            <Baris>
              <FieldTeks control={form.control} name="namaInstansi" label="Nama instansi" wajib />
              <FieldTeks control={form.control} name="namaSingkat" label="Dikenal sebagai" />
            </Baris>
            <FieldTeks control={form.control} name="induk" label="Instansi induk" />
            <FieldArea control={form.control} name="deskripsi" label="Deskripsi singkat (kaki halaman)" rows={2} />
            <Baris>
              <FieldTeks
                control={form.control}
                name="urlPortal"
                label="Alamat portal publik"
                deskripsi="Relatif terhadap folder dashboard (../) atau alamat lengkap."
              />
              <FieldTeks control={form.control} name="hakCipta" label="Teks hak cipta" />
            </Baris>
          </Bagian>
          <Bagian judul="Bilah pengumuman" deskripsi="Pita pengumuman singkat di atas daftar berita.">
            <FieldSakelar control={form.control} name="bilahPengumuman.aktif" label="Tampilkan bilah pengumuman" />
            <FieldTeks control={form.control} name="bilahPengumuman.teks" label="Teks pengumuman" />
            <Baris>
              <FieldTeks control={form.control} name="bilahPengumuman.tautanLabel" label="Label tautan" />
              <FieldTeks control={form.control} name="bilahPengumuman.tautanUrl" label="Tujuan tautan" placeholder="berita-detail.html" />
            </Baris>
          </Bagian>
        </>
      )}
    </FormPengaturan>
  )
}

function TabAkun() {
  const login = sesi()
  return (
    <div className="grid gap-4 md:gap-6">
      <FormPengaturan nama="akun" schema={skemaAkun}>
        {(form) => (
          <Bagian judul="Profil pengelola" deskripsi="Nama ini tercatat di riwayat alur publikasi dan log aktivitas.">
            <Baris>
              <FieldTeks control={form.control} name="nama" label="Nama tampilan" wajib />
              <FieldTeks control={form.control} name="email" label="Surel dinas" type="email" />
            </Baris>
            <Baris>
              <FieldTeks control={form.control} name="jabatan" label="Jabatan" />
              <FieldTeks control={form.control} name="unit" label="Unit" />
            </Baris>
            <FieldPilih
              control={form.control}
              name="peran"
              label="Peran dalam pengelolaan informasi"
              opsi={["Penyedia", "Pemeriksa", "Penyetuju", "Pengelola publikasi"].map((p) => ({ nilai: p, label: p }))}
              className="sm:max-w-sm"
            />
          </Bagian>
        )}
      </FormPengaturan>
      <Bagian judul="Login & keamanan">
        <dl className="grid gap-3 text-sm sm:grid-cols-3">
          <div>
            <dt className="text-muted-foreground">NIP</dt>
            <dd className="font-mono">{login.nip}</dd>
          </div>
          <div>
            <dt className="text-muted-foreground">Peran</dt>
            <dd>{LABEL_PERAN[login.peran]}</dd>
          </div>
          <div>
            <dt className="text-muted-foreground">Masuk sejak</dt>
            <dd>{login.masuk ? tanggalJam(login.masuk) : "—"}</dd>
          </div>
        </dl>
        <Alert>
          <IconInfoCircle />
          <AlertTitle>Login diperiksa server PINTU</AlertTitle>
          <AlertDescription>
            Sesi berakhir bila lama tidak aktif atau peramban ditutup, kecuali “Ingat perangkat ini” dicentang saat
            masuk. Untuk mengganti kata sandi atau membuka akun yang terkunci, hubungi admin di Sub Bagian Tata Usaha.
          </AlertDescription>
        </Alert>
        <div>
          <Button variant="outline" onClick={keluar}>
            <IconLogout />
            Keluar dari dashboard
          </Button>
        </div>
      </Bagian>
    </div>
  )
}

function unduhJson(nama: string, data: unknown) {
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" })
  const url = URL.createObjectURL(blob)
  const a = document.createElement("a")
  a.href = url
  a.download = nama
  a.click()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}

function JumlahKoleksi() {
  const berita = useKoleksi("berita").data?.length
  const dokumentasi = useKoleksi("dokumentasi").data?.length
  const dokumen = useKoleksi("dokumen").data?.length
  // Koleksi di server hanya dihitung bila modulnya boleh dibaca pengguna.
  const bacaPesan = !diServer("pesan") || bolehModul("pesan")
  const bacaKeliling = !diServer("keliling") || bolehModul("keliling", "pemutakhiran")
  const bacaLayanan = !diServer("layanan") || bolehModul("layanan", "pemutakhiran")
  const pesan = useKoleksi("pesan", { aktif: bacaPesan }).data?.length
  const keliling = useKoleksi("keliling", { aktif: bacaKeliling }).data?.length
  const layanan = useKoleksi("layanan", { aktif: bacaLayanan }).data?.length
  const baris: [string, number | null | undefined][] = [
    [KOLEKSI_LABEL.berita, berita],
    [KOLEKSI_LABEL.dokumentasi, dokumentasi],
    [KOLEKSI_LABEL.dokumen, dokumen],
    [KOLEKSI_LABEL.layanan, bacaLayanan ? layanan : null],
    [KOLEKSI_LABEL.keliling, bacaKeliling ? keliling : null],
    [KOLEKSI_LABEL.pesan, bacaPesan ? pesan : null],
  ]
  return (
    <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
      {baris.map(([label, n]) => (
        <div key={label} className="flex justify-between gap-4 border-b pb-2">
          <dt className="text-muted-foreground">{label}</dt>
          <dd className="tabular-nums">{n === null ? "—" : (n ?? "…")}</dd>
        </div>
      ))}
    </dl>
  )
}

function TabData() {
  const qc = useQueryClient()
  const konfirmasi = useKonfirmasi()
  const inputRef = React.useRef<HTMLInputElement>(null)
  const [proses, setProses] = React.useState<string | null>(null)
  // Pesan contoh hanya ada selama pesan tersimpan di peramban.
  const pesanLokal = !diServer("pesan")
  const { data: pesan } = useKoleksi("pesan", { aktif: pesanLokal })
  const lokal = api.sementara ? "Memori sementara" : "Lokal (IndexedDB)"
  // Mode campuran: pulihkan, kosongkan log, dan kembalikan hanya mengubah data di peramban ini.
  const tetapDiServer = api.mode === "campuran" ? ` ${LABEL_MODUL_SERVER} tersimpan di server dan tidak ikut berubah.` : ""

  const jalan = async (kunci: string, fn: () => Promise<void>, sukses: string) => {
    setProses(kunci)
    try {
      await fn()
      await qc.invalidateQueries()
      toast.success(sukses)
    } catch (e) {
      laporGalat(e, "Gagal memproses data")
    } finally {
      setProses(null)
    }
  }

  const ekspor = () =>
    jalan(
      "ekspor",
      async () => {
        const snap = await api.exportAll()
        const tgl = new Date().toISOString().slice(0, 10)
        unduhJson(`pintu-cadangan-${tgl}.json`, snap)
      },
      "Cadangan diunduh"
    )

  const impor = async (berkas: File | undefined) => {
    if (inputRef.current) inputRef.current.value = ""
    if (!berkas) return
    let snap: Snapshot
    try {
      snap = JSON.parse(await berkas.text()) as Snapshot
    } catch {
      toast.error("Berkas bukan JSON yang valid.")
      return
    }
    const ya = await konfirmasi({
      judul: "Pulihkan dari cadangan?",
      deskripsi: `Seluruh data di dashboard akan diganti dengan isi “${berkas.name}”${
        snap.diekspor ? ` (dibuat ${tanggalJam(snap.diekspor)})` : ""
      }.${tetapDiServer}`,
      label: "Pulihkan",
      bahaya: true,
    })
    if (!ya) return
    await jalan(
      "impor",
      async () => {
        try {
          await api.importAll(snap)
        } catch (e) {
          throw e instanceof ApiError ? e : new Error("Cadangan tidak dapat dipulihkan.")
        }
      },
      "Data dipulihkan dari cadangan"
    )
  }

  return (
    <div className="grid gap-4 md:gap-6">
      <Bagian
        judul="Penyimpanan"
        deskripsi={
          api.mode === "lokal"
            ? "Data tersimpan di IndexedDB peramban ini. Perangkat atau peramban lain tidak melihat perubahan yang sama."
            : api.mode === "campuran"
              ? `${LABEL_MODUL_SERVER} tersimpan di server PINTU. Konten lain masih tersimpan di IndexedDB peramban ini; perangkat atau peramban lain tidak melihat perubahan yang sama.`
              : "Data tersimpan di server melalui REST API."
        }
      >
        <p className="flex items-center gap-2 text-sm">
          <IconDatabase className="size-4 text-muted-foreground" aria-hidden="true" />
          Mode:{" "}
          <strong>
            {api.mode === "lokal"
              ? lokal
              : api.mode === "campuran"
                ? `Campuran (${LABEL_MODUL_SERVER} di server, lainnya ${lokal})`
                : "Server (REST API)"}
          </strong>
        </p>
        <JumlahKoleksi />
      </Bagian>
      <Bagian judul="Cadangan" deskripsi="Simpan seluruh isi dashboard sebagai berkas JSON. Berkas ini juga bisa dipakai sebagai data awal saat memindahkan ke server.">
        <div className="flex flex-wrap gap-2">
          <Button onClick={() => void ekspor()} disabled={!!proses}>
            {proses === "ekspor" ? <Spinner /> : <IconDownload />}
            Unduh cadangan (JSON)
          </Button>
          <Button variant="outline" onClick={() => inputRef.current?.click()} disabled={!!proses}>
            {proses === "impor" ? <Spinner /> : <IconUpload />}
            Pulihkan dari cadangan…
          </Button>
          <input
            ref={inputRef}
            type="file"
            accept="application/json,.json"
            className="sr-only"
            tabIndex={-1}
            aria-label="Pilih berkas cadangan"
            onChange={(e) => void impor(e.target.files?.[0])}
          />
        </div>
      </Bagian>
      <Bagian
        judul="Bersihkan data contoh"
        deskripsi={
          pesanLokal
            ? "Sebelum dipakai sungguhan, hapus pesan dan log aktivitas contoh."
            : "Sebelum dipakai sungguhan, kosongkan log aktivitas contoh. Pesan tersimpan di server dan hanya dihapus Administrator dari halaman Pesan & Pengaduan."
        }
      >
        <div className="flex flex-wrap gap-2">
          {pesanLokal && (
            <Button
              variant="outline"
              disabled={!!proses || !pesan?.length}
              onClick={async () => {
                if (!pesan?.length) return
                const ya = await konfirmasi({
                  judul: `Hapus ${pesan.length} pesan?`,
                  deskripsi: "Semua pesan dan tanggapannya akan dihapus permanen.",
                  label: "Hapus semua pesan",
                  bahaya: true,
                })
                if (ya) await jalan("pesan", () => api.remove("pesan", pesan.map((p) => p.id)), "Semua pesan dihapus")
              }}
            >
              {proses === "pesan" ? <Spinner /> : <IconTrash />}
              Hapus semua pesan
            </Button>
          )}
          <Button
            variant="outline"
            disabled={!!proses}
            onClick={async () => {
              const ya = await konfirmasi({
                judul: "Kosongkan log aktivitas?",
                deskripsi: `Riwayat aktivitas di Ringkasan akan dikosongkan. Riwayat alur tiap konten tetap tersimpan.${
                  api.mode === "campuran" ? " Aktivitas yang tercatat di server tidak ikut dihapus." : ""
                }`,
                label: "Kosongkan",
                bahaya: true,
              })
              if (ya) await jalan("aktivitas", () => api.clearActivity(), "Log aktivitas dikosongkan")
            }}
          >
            {proses === "aktivitas" ? <Spinner /> : <IconTrash />}
            Kosongkan log aktivitas
          </Button>
        </div>
      </Bagian>
      <Bagian
        judul="Kembalikan ke data awal"
        deskripsi={`Mengganti seluruh isi dashboard dengan isi awal portal. Unduh cadangan terlebih dahulu bila perlu.${tetapDiServer}`}
      >
        <div>
          <Button
            variant="destructive"
            disabled={!!proses}
            onClick={async () => {
              const ya = await konfirmasi({
                judul: "Kembalikan semua data ke isi awal?",
                deskripsi: `Semua perubahan, unggahan, dan pesan akan hilang. Tindakan ini tidak bisa dibatalkan.${tetapDiServer}`,
                label: "Kembalikan",
                bahaya: true,
              })
              if (ya) await jalan("reset", () => api.reset(), "Data dikembalikan ke isi awal")
            }}
          >
            {proses === "reset" ? <Spinner /> : <IconRestore />}
            Kembalikan ke data awal
          </Button>
        </div>
      </Bagian>
    </div>
  )
}

export default function HalamanPengaturan() {
  const [params, setParams] = useSearchParams()
  const tab = params.get("tab") ?? "situs"
  return (
    <Halaman>
      <PengantarHalaman deskripsi="Identitas portal, profil pengelola, serta cadangan dan pemulihan data dashboard." />
      <Tabs
        value={tab}
        onValueChange={(t) => setParams({ tab: t }, { replace: true })}
        className="gap-4"
      >
        <TabsList>
          <TabsTrigger value="situs">Situs</TabsTrigger>
          <TabsTrigger value="akun">Akun</TabsTrigger>
          <TabsTrigger value="data">Data</TabsTrigger>
        </TabsList>
        <TabsContent value="situs">
          <TabSitus />
        </TabsContent>
        <TabsContent value="akun">
          <TabAkun />
        </TabsContent>
        <TabsContent value="data">
          <TabData />
        </TabsContent>
      </Tabs>
    </Halaman>
  )
}
