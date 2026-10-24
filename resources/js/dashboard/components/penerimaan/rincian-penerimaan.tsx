import { IconAlertTriangle, IconInfoCircle } from "@tabler/icons-react"

import { DataTable, HeaderUrut, pembantuKolom, type KolomTabel } from "@/components/data/data-table"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { angka, rupiah } from "@/lib/format"
import { jendelaRincian, useRincianPenerimaan, type Saringan, type TransaksiRincian } from "@/lib/penerimaan"

import { labelTanggal } from "../../../portal/penerimaan/periode"

const kr = pembantuKolom<TransaksiRincian>()

type KunciTeks = {
  [K in keyof TransaksiRincian]: TransaksiRincian[K] extends string | null ? K : never
}[keyof TransaksiRincian]
type KunciAngka = {
  [K in keyof TransaksiRincian]: TransaksiRincian[K] extends number ? K : never
}[keyof TransaksiRincian]

/** Kolom teks berkas STS; isi kosong ditulis "—" dan diurutkan sebagai teks kosong */
function kolomTeks(kunci: KunciTeks, judul: string, label = judul) {
  return kr.accessor((t) => t[kunci] ?? "", {
    id: kunci,
    header: ({ column }) => <HeaderUrut column={column} judul={judul} />,
    cell: ({ getValue }) => {
      const nilai = getValue() as string
      return nilai === "" ? <span className="text-muted-foreground">—</span> : nilai
    },
    meta: { label },
  })
}

/** Kolom rupiah berkas STS, rata kanan */
function kolomRupiah(kunci: KunciAngka, judul: string, label = judul) {
  return kr.accessor((t) => t[kunci], {
    id: kunci,
    header: ({ column }) => <HeaderUrut column={column} judul={judul} kanan />,
    cell: ({ getValue }) => <div className="text-right tabular-nums">{rupiah(getValue() as number)}</div>,
    meta: { label },
  })
}

/** Nilai jual berkas STS ("18000000.00") sebagai rupiah; isi lain ditulis apa adanya */
function nilaiJual(t: TransaksiRincian): string {
  const n = t.nilaiJual?.match(/^(\d+)(?:\.0+)?$/)
  return n ? rupiah(Number(n[1])) : (t.nilaiJual ?? "")
}

/**
 * Kolom dalam urutan dan kelompok berkas STS (D-079), lalu loket. NOPOL selalu
 * tampil; tiap kolom lain bisa dinyalakan atau dimatikan lewat Atur kolom.
 */
const KOLOM: KolomTabel<TransaksiRincian>[] = [
  kolomTeks("jenisDaftar", "Jenis daftar"),
  kr.accessor((t) => t.nopol ?? "", {
    id: "nopol",
    header: ({ column }) => <HeaderUrut column={column} judul="NOPOL" />,
    cell: ({ getValue }) => {
      const nilai = getValue() as string
      return nilai === "" ? <span className="text-muted-foreground">—</span> : <span className="font-medium whitespace-nowrap">{nilai}</span>
    },
    enableHiding: false,
  }),
  kolomTeks("nomoratur", "Nomorator"),
  kr.accessor("tanggal", {
    header: ({ column }) => <HeaderUrut column={column} judul="Tgl validasi" />,
    cell: ({ getValue }) => <span className="whitespace-nowrap">{labelTanggal(getValue())}</span>,
    meta: { label: "Tgl validasi" },
  }),
  kolomTeks("nama", "Nama pemilik"),
  kolomTeks("jnsKend", "Jns kend", "Jns kend (JNS_KEND)"),
  kolomTeks("tipeKendaraan", "Tipe kend"),
  kolomTeks("merk", "Merk kend"),
  kolomTeks("warnaPlat", "Plat"),
  kr.group({
    id: "jatuhTempo",
    header: "Jatuh tempo",
    columns: kr.columns([kolomTeks("jatuhTempoAwal", "Awal", "Jatuh tempo awal"), kolomTeks("jatuhTempoAkhir", "Terakhir", "Jatuh tempo terakhir")]),
  }),
  kr.accessor((t) => nilaiJual(t), {
    id: "nilaiJual",
    header: ({ column }) => <HeaderUrut column={column} judul="Nilai jual" kanan />,
    cell: ({ getValue }) => <div className="text-right tabular-nums">{(getValue() as string) || "—"}</div>,
    meta: { label: "Nilai jual" },
  }),
  kr.group({
    id: "realisasi",
    header: "Realisasi penerimaan",
    columns: kr.columns([
      kolomRupiah("pkb", "PKB"),
      kolomRupiah("bbn", "BBN"),
      kolomRupiah("dendaPkb", "D.PKB"),
      kolomRupiah("dendaBbn", "D.BBN"),
      kolomRupiah("tunggakan", "Tunggakan"),
      kolomRupiah("dendaTunggakan", "Denda tunggakan"),
      kolomRupiah("opsenPkb", "Opsen PKB"),
      kolomRupiah("opsenBbn", "Opsen BBN"),
      kolomRupiah("opsenDendaPkb", "Opsen DPKB"),
      kolomRupiah("opsenDendaBbn", "Opsen DBBN"),
      kolomRupiah("opsenTunggakan", "Opsen TPKB"),
      kolomRupiah("opsenDendaTunggakan", "Opsen TDPKB"),
      kr.accessor("tahunTunggakan", {
        header: ({ column }) => <HeaderUrut column={column} judul="Tahun tunggakan" kanan />,
        cell: ({ getValue }) => <div className="text-right tabular-nums">{angka(getValue())}</div>,
        meta: { label: "Tahun tunggakan" },
      }),
      kolomRupiah("jumlah", "Jumlah"),
    ]),
  }),
  kolomTeks("uptAsal", "UPT asal"),
  kolomTeks("uptBayar", "UPT bayar"),
  kolomTeks("kodeJenisKendaraan", "Kode jenis kendaraan"),
  kolomTeks("jenisKendaraan", "Jenis kendaraan"),
  kr.group({
    id: "alamat",
    header: "Alamat",
    columns: kr.columns([
      kolomTeks("rt", "RT", "Alamat: RT"),
      kolomTeks("rw", "RW", "Alamat: RW"),
      kolomTeks("kelurahan", "Kelurahan", "Alamat: Kelurahan"),
      kolomTeks("kecamatan", "Kecamatan", "Alamat: Kecamatan"),
    ]),
  }),
  kolomTeks("ubahBentuk", "Ubah bentuk"),
  kolomTeks("namaLoket", "Loket"),
]

/** Kolom yang tampil sejak awal (pemilik): NOPOL, nama, jenis kendaraan, jumlah, dan alamat */
const TAMPIL_AWAL = new Set(["nopol", "nama", "jenisKendaraan", "jumlah", "rt", "rw", "kelurahan", "kecamatan"])

/** Nama kolom: id-nya, atau kunci datanya bila id tidak ditulis */
const idKolom = (k: KolomTabel<TransaksiRincian>) => k.id ?? ("accessorKey" in k ? String(k.accessorKey) : "")

const KOLOM_AWAL = Object.fromEntries(
  KOLOM.flatMap((k) => ("columns" in k && k.columns ? (k.columns as KolomTabel<TransaksiRincian>[]) : [k]))
    .map(idKolom)
    .filter((id) => !TAMPIL_AWAL.has(id))
    .map((id) => [id, false])
)

/**
 * Rincian penerimaan (D-079): setiap transaksi satu hari seperti di berkas STS,
 * dengan saringan laporan. Hanya untuk laporan Harian 7 hari terakhir, karena
 * nomor polisi, nama, dan RT/RW hanya disimpan selama itu.
 */
export function RincianPenerimaan({ saringan }: { saringan: Saringan }) {
  const jendela = jendelaRincian()
  const harian = saringan.dari === saringan.sampai
  const tersedia = harian && saringan.dari >= jendela.dari && saringan.dari <= jendela.sampai
  const { data, isLoading, isError, error, refetch } = useRincianPenerimaan(tersedia ? saringan : null)
  const rentang = `${labelTanggal(jendela.dari)}–${labelTanggal(jendela.sampai)}`

  return (
    <Card className="shadow-xs">
      <CardHeader>
        <CardTitle className="text-base">Rincian penerimaan</CardTitle>
        <CardDescription>
          Setiap transaksi seperti di berkas STS yang diimpor, dengan saringan laporan. Nomor polisi, nama, dan RT/RW hanya
          disimpan untuk 7 hari terakhir dan tidak pernah tampil di portal.
        </CardDescription>
      </CardHeader>
      {/* minmax(0,1fr): tabel yang lebar digulir di dalam kartu, tidak melebarkan halaman */}
      <CardContent className="grid grid-cols-[minmax(0,1fr)] gap-4">
        {!tersedia ? (
          <Alert>
            <IconInfoCircle />
            <AlertTitle>Rincian per transaksi hanya untuk laporan Harian 7 hari terakhir</AlertTitle>
            <AlertDescription>
              {harian
                ? `Rincian ${labelTanggal(saringan.dari)} tidak tersedia. Pilih Harian dan salah satu hari ${rentang}.`
                : `Pilih periode Harian dan salah satu hari ${rentang}.`}
            </AlertDescription>
          </Alert>
        ) : isError ? (
          <Alert variant="destructive">
            <IconAlertTriangle />
            <AlertTitle>Rincian gagal dimuat</AlertTitle>
            <AlertDescription>
              <p>{(error as Error).message}</p>
              <Button variant="outline" size="sm" className="mt-2" onClick={() => void refetch()}>
                Coba lagi
              </Button>
            </AlertDescription>
          </Alert>
        ) : (
          <>
            {data && data.belumTercatat > 0 && (
              <Alert>
                <IconInfoCircle />
                <AlertTitle>{angka(data.belumTercatat)} transaksi belum tercatat rinciannya</AlertTitle>
                <AlertDescription>
                  Transaksi ini diimpor sebelum rincian disimpan. Impor ulang berkas STS loketnya untuk{" "}
                  {labelTanggal(saringan.dari)} agar rinciannya lengkap; penerimaannya tidak dihitung dua kali.
                </AlertDescription>
              </Alert>
            )}
            <DataTable
              data={data?.transaksi}
              memuat={isLoading}
              columns={KOLOM}
              label={`Rincian penerimaan ${labelTanggal(saringan.dari)}`}
              cari={{ placeholder: "Cari NOPOL atau nama…", teks: (t) => `${t.nopol ?? ""} ${t.nama ?? ""}` }}
              kolomAwal={KOLOM_AWAL}
              ukuranHalaman={25}
              pilih={false}
              kosong={{ judul: "Tidak ada transaksi", deskripsi: "Tidak ada transaksi yang cocok dengan saringan pada hari ini." }}
            />
          </>
        )}
      </CardContent>
    </Card>
  )
}
