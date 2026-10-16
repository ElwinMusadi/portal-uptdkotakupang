import { IconExternalLink, IconPlus } from "@tabler/icons-react"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, useHapus } from "@/components/data/row-actions"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldTeks } from "@/components/form/fields"
import { FieldGambar } from "@/components/form/media-fields"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import type { Aplikasi } from "@/lib/api"
import { JENIS_APLIKASI, PENGELOLA_APLIKASI } from "@/lib/meta"
import { useKoleksi, useUrutkanEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

/** Aplikasi Android ditautkan ke halamannya di Google Play, karena portal menawarkannya sebagai "Unduh di Google Play" */
const GOOGLE_PLAY = "https://play.google.com/store/apps/"

const schema = z
  .object({
    nama: v.wajib("Nama aplikasi").max(60, "Nama aplikasi maksimal 60 karakter."),
    namaLengkap: v.teks.max(160, "Nama lengkap maksimal 160 karakter."),
    keterangan: v.teks.max(300, "Keterangan maksimal 300 karakter."),
    jenis: z.enum(["situs", "android"]),
    pengelola: z.enum(["bpad", "uptd"]),
    tautan: v.tautan.pipe(v.wajib("Tautan")).pipe(z.string().max(500, "Tautan maksimal 500 karakter.")),
    logo: z.string().nullable(),
  })
  .refine((f) => f.jenis !== "android" || f.tautan.startsWith(GOOGLE_PLAY), {
    path: ["tautan"],
    message: "Aplikasi Android ditautkan ke halamannya di Google Play (https://play.google.com/store/apps/…).",
  })
type FormAplikasi = z.infer<typeof schema>

/** Logo aplikasi, atau inisial namanya bila belum ada */
function LogoAplikasi({ a }: { a: Pick<Aplikasi, "nama" | "logo"> }) {
  return (
    <span className="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-white p-0.5 text-[0.6875rem] font-semibold text-muted-foreground">
      {a.logo ? <img src={a.logo} alt="" className="size-full object-contain" /> : a.nama.slice(0, 2).toUpperCase()}
    </span>
  )
}

const k = pembantuKolom<Aplikasi>()

export default function HalamanAplikasi() {
  const { data, isLoading } = useKoleksi("aplikasi")
  const urutkan = useUrutkanEntri("aplikasi")
  const hapus = useHapus("aplikasi", "aplikasi")

  const ed = useEditorEntri({
    koleksi: "aplikasi",
    schema,
    nama: "aplikasi",
    kosong: (): FormAplikasi => ({ nama: "", namaLengkap: "", keterangan: "", jenis: "situs", pengelola: "uptd", tautan: "", logo: null }),
    dariEntri: (a): FormAplikasi => ({
      nama: a.nama,
      namaLengkap: a.namaLengkap,
      keterangan: a.keterangan,
      jenis: a.jenis,
      pengelola: a.pengelola,
      tautan: a.tautan,
      logo: a.logo,
    }),
    keEntriBaru: (f) => f,
    kePatch: (f) => f,
  })

  const kolom = [
    k.accessor("nama", {
      header: "Aplikasi",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => ed.bukaUbah(row.original.id)}
          className="flex min-w-56 items-center gap-3 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <LogoAplikasi a={row.original} />
          <span className="grid gap-0.5">
            <span className="font-medium underline-offset-4 hover:underline">{row.original.nama}</span>
            {row.original.namaLengkap && <span className="text-xs text-muted-foreground">{row.original.namaLengkap}</span>}
          </span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("jenis", {
      header: "Jenis",
      cell: ({ row }) => (
        <Badge variant="outline" className="px-1.5 text-muted-foreground">
          {JENIS_APLIKASI[row.original.jenis]}
        </Badge>
      ),
    }),
    k.accessor("pengelola", {
      header: "Pengelola",
      cell: ({ row }) => <span className="text-sm whitespace-normal text-muted-foreground">{PENGELOLA_APLIKASI[row.original.pengelola]}</span>,
    }),
    k.accessor("tautan", {
      header: "Tautan",
      cell: ({ row }) => (
        <a
          href={row.original.tautan.startsWith("http") ? row.original.tautan : `/${row.original.tautan}`}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex max-w-64 items-center gap-1 text-sm text-primary underline-offset-4 hover:underline"
        >
          <span className="truncate">{row.original.tautan.replace(/^https?:\/\//, "")}</span>
          <IconExternalLink className="size-3.5 shrink-0" aria-hidden="true" />
          <span className="sr-only">(tab baru)</span>
        </a>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris label={row.original.nama} onSunting={() => ed.bukaUbah(row.original.id)} onHapus={() => void hapus([row.original.id], row.original.nama)} />
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Aplikasi dan situs milik BPAD Provinsi NTT dan UPTD untuk halaman Aplikasi & Situs di portal. Seret baris untuk mengatur urutannya; perubahan langsung tampil di portal.">
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah aplikasi
        </Button>
      </PengantarHalaman>
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar aplikasi dan situs"
        pilih={false}
        urutkan={(ids) => urutkan.mutate(ids)}
        cari={{ placeholder: "Cari aplikasi…", teks: (a) => `${a.nama} ${a.namaLengkap} ${a.keterangan} ${a.tautan}` }}
        kosong={{ judul: "Belum ada aplikasi", deskripsi: "Tambahkan aplikasi atau situs milik BPAD dan UPTD." }}
      />
      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting aplikasi" : "Tambah aplikasi"}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
        lebar="sm:max-w-2xl"
      >
        <div className="grid gap-5 sm:grid-cols-[10rem_minmax(0,1fr)]">
          <FieldGambar
            control={ed.form.control}
            name="logo"
            label="Logo"
            rasio="aspect-square"
            logo
            deskripsi="Opsional. Inisial nama dipakai bila kosong. PNG transparan paling baik."
            tujuan="aplikasi"
          />
          <div className="grid content-start gap-5">
            <FieldTeks control={ed.form.control} name="nama" label="Nama aplikasi" wajib placeholder="Mis. SIKAP" />
            <FieldTeks control={ed.form.control} name="namaLengkap" label="Nama lengkap" placeholder="Mis. Sistem Informasi Kepegawaian dan Arsip Pegawai" />
          </div>
        </div>
        <FieldArea control={ed.form.control} name="keterangan" label="Keterangan" rows={3} deskripsi="Kegunaannya dalam satu atau dua kalimat, mis. untuk siapa aplikasi ini." />
        <Baris>
          <FieldPilih control={ed.form.control} name="jenis" label="Jenis" opsi={Object.entries(JENIS_APLIKASI).map(([nilai, label]) => ({ nilai, label }))} />
          <FieldPilih control={ed.form.control} name="pengelola" label="Pengelola" opsi={Object.entries(PENGELOLA_APLIKASI).map(([nilai, label]) => ({ nilai, label }))} />
        </Baris>
        <FieldTeks
          control={ed.form.control}
          name="tautan"
          label="Tautan"
          wajib
          placeholder="https://…"
          deskripsi="Alamat lengkap situsnya, halaman aplikasi di Google Play untuk aplikasi Android, atau halaman portal (mis. index.html)."
        />
      </EntriSheet>
    </Halaman>
  )
}
