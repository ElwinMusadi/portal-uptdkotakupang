/*
 * Halaman Ikhtisar (D-099, D-100) di /laporan-penerimaan: penerimaan semua
 * loket dengan saringan seperti Laporan Penerimaan di dashboard, Realisasi
 * Target (Form I), dan Pemetaan PKB untuk rapat, selama Administrator
 * mengaktifkannya di Pengaturan › Halaman Ikhtisar. Angkanya sama dengan
 * dashboard, tanpa identitas wajib pajak, dan selalu menyebut sumber serta
 * tanggal datanya.
 */
import * as React from "react"
import { IconAdjustmentsHorizontal, IconAlertTriangle, IconInfoCircle, IconLock, IconPrinter, IconRefresh, IconWifiOff } from "@tabler/icons-react"

import { IsiRingkasanPemetaan, belumBayar, DaftarBatang, persen as persenPotensi } from "@/components/pemetaan/ringkasan-wilayah"
import { cetakFormI, RingkasanRealisasi, Sumber, TabelFormI, tanggalPanjang, TertutupSaldo } from "@/components/penerimaan/form-i"
import { BadgePerubahan, GrafikHarian, GrafikRincian, PerLoket, Ringkasan, type IntiLaporan } from "@/components/penerimaan/ringkasan-laporan"
import { PanelSaringan, type PilihanSaringan } from "@/components/penerimaan/saringan-laporan"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Skeleton } from "@/components/ui/skeleton"
import { angka, hariIniIso, rupiah } from "@/lib/format"
import { NAMA_BULAN } from "@/lib/pemetaan"
import { bagianProvinsi, DIMENSI, LABEL_DIMENSI, labelNilai, perubahan, type Dimensi, type Saringan } from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { galatPeriode, jumlahHari, labelPeriode, labelTanggal, type Periode } from "../portal/penerimaan/periode"
import { catatPeriode, GalatIkhtisar, periodeDariAlamat, useIkhtisar, type Ikhtisar } from "./data"
import { IsiPembayaran } from "./pembayaran"

const BAGIAN = [
  { id: "ringkasan", judul: "Ringkasan" },
  { id: "penerimaan", judul: "Penerimaan" },
  { id: "realisasi", judul: "Realisasi Target" },
  { id: "pemetaan", judul: "Pemetaan PKB" },
  { id: "pembayaran", judul: "Pembayaran PKB" },
] as const

const jamWita = (d: number) =>
  new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Makassar" }).format(new Date(d)).replace(":", ".")

/** "Sabtu, 21 Nov 2026" */
const tanggalSedang = (t: string) =>
  new Intl.DateTimeFormat("id-ID", { weekday: "long", day: "numeric", month: "short", year: "numeric", timeZone: "UTC" }).format(new Date(`${t}T00:00:00Z`))

/** Saringan yang dipilih, untuk ditulis di halaman dan hasil cetak */
interface Saringannya {
  pilih: PilihanSaringan
  tunggakan: Saringan["tunggakan"]
}

const adaSaringan = (s: Saringannya) => Object.values(s.pilih).some((v) => v && v.length > 0) || s.tunggakan !== ""

export function AplikasiIkhtisar() {
  // Periode di layar (bisa belum sah selama rentang diketik) dan periode yang diminta ke server
  const [periodeLayar, setPeriodeLayar] = React.useState<Periode | null>(periodeDariAlamat)
  const [periode, setPeriode] = React.useState<Periode | null>(periodeLayar)
  const [pilih, setPilih] = React.useState<PilihanSaringan>({})
  const [tunggakan, setTunggakan] = React.useState<Saringan["tunggakan"]>("")
  const ikhtisar = useIkhtisar({ periode, pilih, tunggakan })
  const galat = ikhtisar.error instanceof GalatIkhtisar ? ikhtisar.error : null
  const data = ikhtisar.data

  if (galat?.status === 403) {
    return (
      <LayarPesan ikon={<IconLock />} judul="Halaman sedang dinonaktifkan" aksi={<Button onClick={() => void ikhtisar.refetch()}>Coba lagi</Button>}>
        {galat.message} Coba lagi setelah halaman diaktifkan kembali.
      </LayarPesan>
    )
  }
  if (!data) {
    if (galat) {
      return (
        <LayarPesan ikon={<IconAlertTriangle />} judul="Ikhtisar belum dapat dimuat" aksi={<Button onClick={() => void ikhtisar.refetch()}>Coba lagi</Button>}>
          {galat.status === 429 ? "Terlalu banyak permintaan dari jaringan ini. Coba lagi sebentar lagi." : galat.message}
        </LayarPesan>
      )
    }
    return <KerangkaIkhtisar />
  }

  // Tanpa pilihan, server memberi bulan tanggal STS terakhir sampai tanggal itu
  const tampil = periodeLayar ?? { mode: "bulanan" as const, dari: data.dari, sampai: data.sampai }
  const batas = data.pembaruan.stsTerakhir ?? hariIniIso()
  const galatLayar = galatPeriode(tampil)
  const saringan = { pilih, tunggakan }

  const ubahPeriode = (p: Periode) => {
    // Bulan yang memuat tanggal STS terakhir berhenti di tanggal itu, seperti bawaan halaman
    const baru = p.mode === "bulanan" && p.dari <= batas && p.sampai > batas ? { ...p, sampai: batas } : p
    setPeriodeLayar(baru)
    if (!galatPeriode(baru)) {
      setPeriode(baru)
      catatPeriode(baru)
    }
  }
  // Saringan pertama menetapkan periode yang sedang tampil, agar periodenya tidak berubah
  const tetapkanPeriode = () => {
    if (!periode && !galatLayar) setPeriode(tampil)
  }
  const ubahPilih = (d: Dimensi, nilai: string[]) => {
    tetapkanPeriode()
    setPilih((p) => {
      const baru = { ...p }
      if (nilai.length > 0) baru[d] = nilai
      else delete baru[d]
      return baru
    })
  }
  const ubahTunggakan = (t: Saringan["tunggakan"]) => {
    tetapkanPeriode()
    setTunggakan(t)
  }

  return (
    <div className="min-h-svh bg-background">
      <Kepala data={data} dimuat={ikhtisar.dataUpdatedAt} memuat={ikhtisar.isFetching} perbarui={() => void ikhtisar.refetch()} />
      <BilahNavigasi label={labelPeriode(data.dari, data.sampai)} saringan={saringan} />
      <main className="mx-auto grid w-full max-w-[100rem] gap-10 px-4 py-6 md:px-8" aria-busy={ikhtisar.isFetching}>
        <section id="saringan" aria-label="Periode dan saringan penerimaan" className="grid gap-3 print:hidden">
          <PanelSaringan
            periode={tampil}
            ubahPeriode={ubahPeriode}
            galat={galatLayar}
            pilihan={data.penerimaan.pilihan}
            pilih={pilih}
            ubahPilih={ubahPilih}
            tunggakan={tunggakan}
            ubahTunggakan={ubahTunggakan}
            adaSaringan={adaSaringan(saringan)}
            hapusSaringan={() => {
              setPilih({})
              setTunggakan("")
            }}
            batasAkhir={batas}
            satuBarisDiPonsel
          />
          {galat && (
            <Alert variant={galat.status === 422 ? "default" : "destructive"} role="status">
              {galat.status === 0 ? <IconWifiOff /> : <IconAlertTriangle />}
              <AlertTitle>{galat.status === 422 ? "Periode atau saringan tidak bisa ditampilkan" : "Angka belum diperbarui"}</AlertTitle>
              <AlertDescription>
                {galat.status === 429 ? "Terlalu banyak permintaan dari jaringan ini. Coba lagi sebentar lagi." : galat.message}{" "}
                {galat.status !== 422 && `Angka di bawah dimuat pukul ${jamWita(ikhtisar.dataUpdatedAt)} WITA.`}
              </AlertDescription>
            </Alert>
          )}
        </section>
        <div className={cn("grid gap-10 transition-opacity", ikhtisar.isFetching && "opacity-70")}>
          <BagianRingkasan data={data} saringan={saringan} />
          <BagianPenerimaan data={data} saringan={saringan} />
          <BagianRealisasi data={data} saringan={saringan} />
          <BagianPemetaan data={data} />
          <BagianPembayaran data={data} />
          <KakiHalaman />
        </div>
      </main>
    </div>
  )
}

/* ---------- Kepala dan navigasi ---------- */

function Kepala({ data, dimuat, memuat, perbarui }: { data: Ikhtisar; dimuat: number; memuat: boolean; perbarui: () => void }) {
  return (
    <header className="border-b bg-card">
      <div className="mx-auto grid w-full max-w-[100rem] gap-4 px-4 py-4 md:gap-5 md:px-8 md:py-5">
        <div className="flex items-center gap-3 sm:gap-4">
          <img src="/assets/img/logo-pintu-nav.png" alt="PINTU" width={1107} height={361} className="h-7 w-auto shrink-0 sm:h-9" />
          <div className="grid min-w-0 flex-1 gap-0.5 border-l pl-3 sm:pl-4">
            <h1 className="text-base leading-tight font-heading font-(--fw-heading) sm:text-xl md:text-2xl">Ikhtisar Penerimaan &amp; Pemetaan PKB</h1>
            <p className="text-xs text-muted-foreground sm:text-sm">
              UPTD Pendapatan Daerah Wilayah Kota Kupang<span className="hidden sm:inline"> · SAMSAT Kota Kupang</span>
            </p>
          </div>
          <div className="flex shrink-0 items-center gap-3 print:hidden">
            <span className="sr-only text-sm text-muted-foreground md:not-sr-only" aria-live="polite">
              {memuat ? "Memperbarui…" : `Dimuat ${jamWita(dimuat)} WITA`}
            </span>
            <Button variant="outline" size="sm" onClick={perbarui} disabled={memuat} aria-label="Perbarui angka">
              <IconRefresh className={cn(memuat && "animate-spin motion-reduce:animate-none")} />
              <span className="hidden sm:inline">Perbarui</span>
            </Button>
          </div>
        </div>
        <KesegaranData data={data} />
      </div>
    </header>
  )
}

type StatusLoket = "lengkap" | "kurang" | "kosong" | "lain"

/**
 * Sampai kapan datanya: tanggal STS terakhir, Data Potensi, dan tanggal STS
 * setiap loket. Satu baris per loket: hijau bila sampai tanggal STS terakhir,
 * kuning bila terjadwal melayani pada tanggal itu tetapi belum diimpor, abu-abu
 * bila tidak terjadwal pada tanggal itu.
 */
function KesegaranData({ data }: { data: Ikhtisar }) {
  const p = data.pembaruan
  const terakhir = p.stsTerakhir
  const [buka, setBuka] = React.useState(false)
  const idDaftar = React.useId()
  const belum = new Set(p.belumDiimpor.map((l) => l.kunci))
  const loket: { kunci: string; nama: string; tanggal: string | null; status: StatusLoket }[] = [
    ...p.loket.map((l) => ({
      kunci: l.nilai,
      nama: l.nama,
      tanggal: l.sampaiTanggal,
      status: (terakhir && l.sampaiTanggal >= terakhir ? "lengkap" : belum.has(l.nilai) ? "kurang" : "lain") as StatusLoket,
    })),
    ...p.belumDiimpor.filter((b) => !p.loket.some((l) => l.nilai === b.kunci)).map((b) => ({ kunci: b.kunci, nama: b.nama, tanggal: null, status: "kosong" as const })),
  ]
  const tertinggal = loket.filter((l) => l.status === "kurang" || l.status === "kosong").length
  const ada = new Set(loket.map((l) => l.status))

  return (
    <dl className="grid grid-cols-2 gap-3 lg:grid-cols-[minmax(0,14rem)_minmax(0,14rem)_minmax(0,1fr)]">
      <InfoData
        label="STS terakhir"
        nilai={terakhir ? tanggalSedang(terakhir) : "Belum ada"}
        keterangan={
          !terakhir ? (
            "Belum ada berkas STS yang diimpor."
          ) : tertinggal > 0 ? (
            <span className="text-amber-700">{tertinggal} loket terjadwal belum diimpor</span>
          ) : (
            "Semua loket terjadwal sudah diimpor"
          )
        }
      />
      <InfoData
        label="Data Potensi"
        nilai={p.dataPotensi?.tanggal ? tanggalSedang(p.dataPotensi.tanggal) : "Belum ada"}
        keterangan={data.pemetaan ? `Penetapan · ${angka(data.pemetaan.jumlah.kendaraan)} kendaraan` : "Belum ada berkas Data Potensi yang diimpor."}
      />
      {loket.length > 0 && (
        <div className="col-span-2 grid gap-2 rounded-lg border bg-background px-3 py-2.5 lg:col-span-1">
          <div className="flex items-center justify-between gap-3">
            <dt className="text-xs font-medium text-muted-foreground">STS per loket</dt>
            <Button
              variant="ghost"
              size="sm"
              className="-my-1 h-7 px-2 text-xs sm:hidden print:hidden"
              aria-expanded={buka}
              aria-controls={idDaftar}
              onClick={() => setBuka((b) => !b)}
            >
              {buka ? "Sembunyikan" : `Lihat ${loket.length} loket`}
            </Button>
          </div>
          <dd id={idDaftar} className={cn("grid gap-2", !buka && "max-sm:hidden print:grid")}>
            <ul className="grid gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3" role="list">
              {loket.map((l) => (
                <li key={l.kunci} className="flex min-w-0 items-center justify-between gap-3 text-sm">
                  <span className="flex min-w-0 items-center gap-2">
                    <TitikStatus status={l.status} />
                    <span className="truncate">{l.nama}</span>
                  </span>
                  <span className={cn("shrink-0 tabular-nums", l.status === "kurang" || l.status === "kosong" ? "text-amber-700" : "text-muted-foreground")}>
                    {l.tanggal ? `${l.status === "kurang" ? "s.d. " : ""}${labelTanggal(l.tanggal)}` : "belum ada berkas"}
                    <span className="sr-only">
                      {l.status === "lengkap" ? " (sampai STS terakhir)" : l.status === "lain" ? " (tidak terjadwal pada tanggal STS terakhir)" : " (terjadwal, belum diimpor)"}
                    </span>
                  </span>
                </li>
              ))}
            </ul>
            {terakhir && (ada.has("kurang") || ada.has("kosong") || ada.has("lain")) && (
              <p className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground" aria-hidden="true">
                <span className="inline-flex items-center gap-1.5">
                  <TitikStatus status="lengkap" />
                  sampai {labelTanggal(terakhir)}
                </span>
                {(ada.has("kurang") || ada.has("kosong")) && (
                  <span className="inline-flex items-center gap-1.5">
                    <TitikStatus status="kurang" />
                    terjadwal {labelTanggal(terakhir)}, belum diimpor
                  </span>
                )}
                {ada.has("lain") && (
                  <span className="inline-flex items-center gap-1.5">
                    <TitikStatus status="lain" />
                    tidak terjadwal {labelTanggal(terakhir)}
                  </span>
                )}
              </p>
            )}
          </dd>
        </div>
      )}
    </dl>
  )
}

function TitikStatus({ status }: { status: StatusLoket }) {
  return (
    <span
      aria-hidden="true"
      className={cn(
        "size-2 shrink-0 rounded-full",
        status === "lengkap" ? "bg-emerald-500" : status === "lain" ? "bg-muted-foreground/40" : "bg-amber-500"
      )}
    />
  )
}

function InfoData({ label, nilai, keterangan }: { label: string; nilai: string; keterangan: React.ReactNode }) {
  return (
    <div className="grid content-start gap-0.5 rounded-lg border bg-background px-3 py-2.5">
      <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
      <dd className="grid gap-0.5">
        <span className="text-sm font-semibold sm:text-base">{nilai}</span>
        <span className="text-xs text-muted-foreground">{keterangan}</span>
      </dd>
    </div>
  )
}

function BilahNavigasi({ label, saringan }: { label: string; saringan: Saringannya }) {
  const aktif = useBagianAktif()
  const jumlah = Object.values(saringan.pilih).filter((v) => v && v.length > 0).length + (saringan.tunggakan ? 1 : 0)
  return (
    <div className="sticky top-0 z-20 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80 print:hidden">
      <div className="mx-auto flex w-full max-w-[100rem] items-center gap-3 px-4 py-2 md:px-8">
        <nav aria-label="Bagian ikhtisar" className="-mx-1 flex min-w-0 flex-1 gap-1 overflow-x-auto">
          {BAGIAN.map((b) => (
            <a
              key={b.id}
              href={`#${b.id}`}
              aria-current={aktif === b.id ? "true" : undefined}
              className={cn(
                "rounded-md px-2.5 py-1.5 text-sm whitespace-nowrap text-muted-foreground hover:bg-muted hover:text-foreground",
                aktif === b.id && "bg-muted font-medium text-foreground"
              )}
            >
              {b.judul}
            </a>
          ))}
        </nav>
        <a
          href="#saringan"
          className="inline-flex shrink-0 items-center gap-2 rounded-md border bg-background px-2.5 py-1.5 text-sm font-medium hover:bg-muted"
          aria-label={`Ubah periode dan saringan. Periode: ${label}${jumlah > 0 ? `, ${jumlah} saringan` : ""}`}
        >
          <IconAdjustmentsHorizontal className="size-4" aria-hidden="true" />
          <span className="hidden whitespace-nowrap md:inline">{label}</span>
          {jumlah > 0 && (
            <Badge variant="secondary" className="rounded-sm px-1.5 font-normal">
              {jumlah} saringan
            </Badge>
          )}
        </a>
      </div>
    </div>
  )
}

/** "Loket: Samsat Keliling 01 · Merek: HONDA, SUZUKI · Ada tunggakan" */
function teksSaringan(s: Saringannya, data: Ikhtisar): string {
  const namaLoket = new Map(data.penerimaan.pilihan.loket.map((l) => [l.nilai, l.nama]))
  const bagian = DIMENSI.filter((d) => (s.pilih[d]?.length ?? 0) > 0).map(
    (d) =>
      `${LABEL_DIMENSI[d]}: ${s.pilih[d]!.map((nilai) => (d === "loket" ? (namaLoket.get(nilai) ?? nilai) : labelNilai(d, { nilai }))).join(", ")}`
  )
  if (s.tunggakan) bagian.push(s.tunggakan === "ada" ? "Ada tunggakan" : "Tanpa tunggakan")
  return bagian.join(" · ")
}

/** Bagian yang sedang terbaca di layar, untuk menandai tautannya di bilah navigasi */
function useBagianAktif(): string {
  const [aktif, setAktif] = React.useState<string>(BAGIAN[0].id)
  React.useEffect(() => {
    if (typeof IntersectionObserver === "undefined") return
    const pengamat = new IntersectionObserver(
      (entri) => {
        const terlihat = entri.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]
        if (terlihat) setAktif(terlihat.target.id)
      },
      { rootMargin: "-20% 0px -70% 0px" }
    )
    for (const b of BAGIAN) {
      const el = document.getElementById(b.id)
      if (el) pengamat.observe(el)
    }
    return () => pengamat.disconnect()
  }, [])
  return aktif
}

/* ---------- Bagian ---------- */

function Bagian({ id, judul, sumber, children }: { id: string; judul: string; sumber: React.ReactNode; children: React.ReactNode }) {
  return (
    <section id={id} aria-labelledby={`${id}-judul`} className="grid grid-cols-[minmax(0,1fr)] gap-4">
      <div className="grid gap-1">
        <h2 id={`${id}-judul`} className="text-lg font-heading font-(--fw-heading) md:text-xl">
          {judul}
        </h2>
        <p className="text-sm text-muted-foreground">{sumber}</p>
      </div>
      {children}
    </section>
  )
}

const laporanDari = (data: Ikhtisar): IntiLaporan => ({ ...data.penerimaan, dari: data.dari, sampai: data.sampai })

function BagianRingkasan({ data, saringan }: { data: Ikhtisar; saringan: Saringannya }) {
  const r = data.penerimaan.ringkasan
  const total = data.realisasi.baris.find((b) => b.jenis === "total")
  const p = data.pemetaan
  const b = data.pembayaran
  const sampaiRealisasi = data.realisasi.sampai ?? data.sampai
  const disaring = adaSaringan(saringan)

  return (
    <Bagian
      id="ringkasan"
      judul={`Ringkasan · ${labelPeriode(data.dari, data.sampai)}`}
      sumber={
        disaring
          ? `Setiap angka menyebut sumber dan tanggal datanya. Saringan (${teksSaringan(saringan, data)}) hanya berlaku untuk penerimaan.`
          : "Setiap angka menyebut sumber dan tanggal datanya."
      }
    >
      <div className="grid gap-4 lg:grid-cols-2 2xl:grid-cols-4">
        <KartuRingkasan
          judul={disaring ? "Penerimaan sesuai saringan" : "Penerimaan semua loket"}
          nilai={rupiah(r.jumlah)}
          lencana={<BadgePerubahan nilai={perubahan(r.jumlah, data.penerimaan.pembanding?.ringkasan.jumlah)} />}
          isi={
            <>
              <strong className="font-semibold text-foreground">{angka(r.transaksi)} unit</strong> membayar · pajak provinsi {rupiah(bagianProvinsi(r))}
            </>
          }
          sumber={`STS ${disaring ? "sesuai saringan" : "semua loket"}, ${labelPeriode(data.dari, data.sampai)}. Termasuk opsen; satu transaksi = satu kendaraan.`}
        />
        <KartuRingkasan
          judul={`Realisasi target ${data.realisasi.tahun}`}
          nilai={data.realisasi.tersedia && total ? rupiah(total.sampaiIni) : "—"}
          lencana={total?.persen != null && <Badge variant="outline">{angka(total.persen, 2)}% dari target</Badge>}
          isi={
            !data.realisasi.tersedia ? (
              "Bulan ini tercatat dalam saldo awal."
            ) : total?.target ? (
              <div className="grid gap-2">
                {total.persen !== null && (
                  <div className="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                    <div className="h-full rounded-full bg-primary" style={{ width: `${Math.min(total.persen, 100)}%` }} />
                  </div>
                )}
                <span>Target {rupiah(total.target)}</span>
              </div>
            ) : (
              "Target tahun ini belum diisi."
            )
          }
          sumber={`Form I tanpa opsen, s.d. ${tanggalPanjang(sampaiRealisasi)}.`}
        />
        <KartuRingkasan
          judul="Kepatuhan PKB"
          nilai={p ? persenPotensi(p.jumlah.kelompok.taat, p.jumlah.kendaraan) : "—"}
          lencana={p && <Badge variant="outline">taat</Badge>}
          isi={
            p ? (
              <>
                {angka(p.jumlah.kelompok.taat)} dari {angka(p.jumlah.kendaraan)} kendaraan taat · belum bayar {angka(belumBayar(p.jumlah))} · menunggak{" "}
                {angka(p.jumlah.kelompok.menunggak)}
              </>
            ) : (
              "Data Potensi belum diimpor."
            )
          }
          sumber={p?.tanggal ? `Data Potensi BPAD, penetapan ${labelTanggal(p.tanggal, "lengkap")}. Tidak berubah oleh pembayaran STS sesudahnya.` : "Data Potensi BPAD."}
        />
        <KartuRingkasan
          judul="Bayar PKB di loket sejak Data Potensi"
          nilai={b ? angka(b.jumlah.bayar) : "—"}
          lencana={b?.dataPotensi.tanggal && <Badge variant="outline">sejak {labelTanggal(b.dataPotensi.tanggal)}</Badge>}
          isi={
            b ? (
              <>
                transaksi PKB dan tunggakan di semua loket, tanpa {angka(b.baru)} kendaraan baru · {angka(b.jumlah.belumBayar + b.jumlah.menunggak)} kendaraan belum taat
                menurut Data Potensi
              </>
            ) : (
              "Data Potensi belum diimpor."
            )
          }
          sumber={b?.sts ? `STS ${labelPeriode(b.sts.dari, b.sts.sampai)}. Transaksi, bukan kendaraan unik.` : "Belum ada STS sejak tanggal Data Potensi."}
        />
      </div>
    </Bagian>
  )
}

function KartuRingkasan({
  judul,
  nilai,
  lencana,
  isi,
  sumber,
}: {
  judul: string
  nilai: string
  lencana?: React.ReactNode
  isi: React.ReactNode
  sumber: string
}) {
  return (
    <Card className="gap-3 bg-gradient-to-t from-primary/5 to-card shadow-xs">
      <CardHeader>
        <CardDescription className="text-sm font-medium">{judul}</CardDescription>
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <CardTitle className="text-3xl tabular-nums">{nilai}</CardTitle>
          {lencana}
        </div>
      </CardHeader>
      <CardContent className="text-sm text-muted-foreground">{isi}</CardContent>
      <CardFooter className="mt-auto border-t pt-3 text-xs text-muted-foreground">{sumber}</CardFooter>
    </Card>
  )
}

function BagianPenerimaan({ data, saringan }: { data: Ikhtisar; saringan: Saringannya }) {
  const laporan = laporanDari(data)
  const tersedia = data.pembaruan.tersedia
  const disaring = adaSaringan(saringan)
  return (
    <Bagian
      id="penerimaan"
      judul={`Penerimaan · ${labelPeriode(data.dari, data.sampai)}`}
      sumber={
        <>
          Semua berkas STS yang diimpor, semua loket. Data sampai {data.pembaruan.stsTerakhir ? labelTanggal(data.pembaruan.stsTerakhir, "lengkap") : "—"}.
          {disaring && (
            <>
              {" "}
              <strong className="font-medium text-foreground">Saringan: {teksSaringan(saringan, data)}.</strong>
            </>
          )}
        </>
      }
    >
      {tersedia && data.dari < tersedia.awal && (
        <Alert>
          <IconInfoCircle />
          <AlertDescription>Data STS di PINTU tersedia mulai {labelTanggal(tersedia.awal, "lengkap")}; hari sebelumnya belum tercatat.</AlertDescription>
        </Alert>
      )}
      {laporan.ringkasan.transaksi === 0 ? (
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">
            {disaring ? "Tidak ada transaksi yang cocok dengan saringan pada periode ini." : "Belum ada transaksi STS pada periode ini."}
          </CardContent>
        </Card>
      ) : (
        <>
          <Ringkasan laporan={laporan} />
          <PerLoket laporan={laporan} adaSaringan={disaring} />
          {data.dari !== data.sampai && <GrafikHarian laporan={laporan} perBulan={jumlahHari(data.dari, data.sampai) > 62} />}
          <div className="grid grid-cols-[minmax(0,1fr)] gap-4 xl:grid-cols-2">
            <GrafikRincian laporan={laporan} dimensi="jenisDaftar" judul="Per jenis daftar" />
            <GrafikRincian laporan={laporan} dimensi="jenisKendaraan" judul="Per jenis kendaraan" />
            <GrafikRincian laporan={laporan} dimensi="kecamatan" judul="Per kecamatan" deskripsi="Kecamatan alamat pemilik menurut berkas STS." />
            <GrafikRincian laporan={laporan} dimensi="uptAsal" judul="Per asal UPT" deskripsi="UPT tempat kendaraan terdaftar." />
          </div>
        </>
      )}
    </Bagian>
  )
}

function BagianRealisasi({ data, saringan }: { data: Ikhtisar; saringan: Saringannya }) {
  const laporan = data.realisasi
  const tabel = React.useRef<HTMLTableElement>(null)
  return (
    <Bagian
      id="realisasi"
      judul={`Realisasi Target · ${laporan.sampai ? `s.d. ${tanggalPanjang(laporan.sampai)}` : `${NAMA_BULAN[laporan.bulan - 1]} ${laporan.tahun}`}`}
      sumber={`Form I: PKB, BBNKB, dan dendanya dari STS semua loket terhadap target tahunan, bulan tanggal akhir periode. Opsen kabupaten/kota tidak termasuk.${adaSaringan(saringan) ? " Saringan penerimaan tidak berlaku di sini." : ""}`}
    >
      {!laporan.target && (
        <Alert>
          <IconInfoCircle />
          <AlertTitle>Target {laporan.tahun} belum diisi</AlertTitle>
          <AlertDescription>Realisasi tetap dihitung; kolom target dan persentase kosong sampai target diisi di dashboard.</AlertDescription>
        </Alert>
      )}
      {laporan.tersedia ? (
        <>
          <RingkasanRealisasi laporan={laporan} />
          <Card className="gap-4">
            <CardHeader className="flex flex-wrap items-start justify-between gap-3">
              <div className="grid gap-1.5">
                <CardTitle className="text-base">Form I</CardTitle>
                <CardDescription>Realisasi penerimaan gabungan semua loket, sama seperti berkas Excel di dashboard.</CardDescription>
              </div>
              <Button variant="outline" size="sm" className="print:hidden" onClick={() => tabel.current && cetakFormI(tabel.current, laporan)}>
                <IconPrinter />
                Cetak Form I
              </Button>
            </CardHeader>
            <CardContent className="px-0 sm:px-6">
              <TabelFormI laporan={laporan} ref={tabel} />
            </CardContent>
          </Card>
          <Sumber laporan={laporan} />
        </>
      ) : (
        <TertutupSaldo laporan={laporan} />
      )}
    </Bagian>
  )
}

function BagianPemetaan({ data }: { data: Ikhtisar }) {
  const p = data.pemetaan
  return (
    <Bagian
      id="pemetaan"
      judul="Pemetaan PKB"
      sumber={
        p?.tanggal
          ? `Data Potensi BPAD, penetapan ${labelTanggal(p.tanggal, "lengkap")}: status taat, belum bayar, dan menunggak per tanggal itu, tidak berubah oleh pembayaran STS sesudahnya.`
          : "Data Potensi BPAD."
      }
    >
      {p ? (
        <IsiRingkasanPemetaan
          ringkasan={p}
          kolom={p.kolom}
          kartuTambahan={
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Jatuh tempo per bulan</CardTitle>
                <CardDescription>Kendaraan menurut bulan jatuh tempo pajaknya.</CardDescription>
              </CardHeader>
              <CardContent>
                <DaftarBatang baris={(p.bulan ?? []).map((b) => ({ label: NAMA_BULAN[b.bulan - 1], nilai: b.kendaraan }))} />
              </CardContent>
            </Card>
          }
        />
      ) : (
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">Belum ada berkas Data Potensi yang diimpor.</CardContent>
        </Card>
      )}
    </Bagian>
  )
}

function BagianPembayaran({ data }: { data: Ikhtisar }) {
  const b = data.pembayaran
  return (
    <Bagian
      id="pembayaran"
      judul="Pembayaran PKB sejak Data Potensi"
      sumber={
        b?.sts
          ? `Kepatuhan menurut Data Potensi${b.dataPotensi.tanggal ? ` penetapan ${labelTanggal(b.dataPotensi.tanggal, "lengkap")}` : ""}, berdampingan dengan transaksi bayar PKB di semua loket ${labelPeriode(b.sts.dari, b.sts.sampai)}. Tidak bergantung pada pilihan periode.`
          : "Kepatuhan menurut Data Potensi, berdampingan dengan transaksi bayar PKB di loket sejak tanggalnya sampai STS terakhir."
      }
    >
      {b ? (
        <IsiPembayaran pembayaran={b} />
      ) : (
        <Card>
          <CardContent className="py-6 text-sm text-muted-foreground">Belum ada berkas Data Potensi yang diimpor.</CardContent>
        </Card>
      )}
    </Bagian>
  )
}

function KakiHalaman() {
  return (
    <footer className="grid gap-3 border-t pt-6 text-sm text-muted-foreground">
      <p>
        Untuk rapat UPTD Pendapatan Daerah Wilayah Kota Kupang. Halaman ini hanya bisa dibuka selama Administrator PINTU mengaktifkannya, dan tidak
        memuat identitas wajib pajak.
      </p>
      <div className="flex flex-wrap gap-2 print:hidden">
        <Button variant="outline" size="sm" onClick={() => window.print()}>
          <IconPrinter />
          Cetak halaman
        </Button>
      </div>
    </footer>
  )
}

/* ---------- Keadaan ---------- */

function LayarPesan({ ikon, judul, aksi, children }: { ikon: React.ReactNode; judul: string; aksi?: React.ReactNode; children: React.ReactNode }) {
  return (
    <main className="grid min-h-svh place-items-center bg-background px-4">
      <div className="grid max-w-md justify-items-center gap-4 text-center">
        <span className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground [&_svg]:size-6" aria-hidden="true">
          {ikon}
        </span>
        <h1 className="text-xl font-heading font-(--fw-heading)">{judul}</h1>
        <p className="text-sm text-muted-foreground">{children}</p>
        {aksi}
      </div>
    </main>
  )
}

function KerangkaIkhtisar() {
  return (
    <div className="mx-auto grid w-full max-w-[100rem] gap-6 px-4 py-6 md:px-8" role="status" aria-label="Memuat ikhtisar">
      <Skeleton className="h-24 rounded-xl" />
      <div className="grid gap-4 lg:grid-cols-3">
        {[0, 1, 2].map((i) => (
          <Skeleton key={i} className="h-44 rounded-xl" />
        ))}
      </div>
      <Skeleton className="h-96 rounded-xl" />
    </div>
  )
}
