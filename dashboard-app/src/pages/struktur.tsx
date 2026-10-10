import * as React from "react"
import { IconPlus } from "@tabler/icons-react"
import { toast } from "sonner"
import { z } from "zod"

import { DataTable, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, useHapus } from "@/components/data/row-actions"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldTeks } from "@/components/form/fields"
import { FieldDaftarTeks } from "@/components/form/list-fields"
import { FieldGambar } from "@/components/form/media-fields"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import type { JenisUnit, UnitKerja } from "@/lib/api"
import { JENIS_UNIT } from "@/lib/meta"
import { useKoleksi, useUrutkanEntri } from "@/lib/queries"
import { cn } from "@/lib/utils"
import * as v from "@/lib/validasi"

const TANPA = "tanpa"

const schema = z.object({
  nama: v.wajib("Nama unit"),
  singkatan: z.string().trim().min(1, "Isi singkatan.").max(3, "Maks. 3 huruf."),
  jenis: z.enum(["pimpinan", "tata-usaha", "seksi", "fungsional", "mitra"]),
  induk: z.string(),
  jabatan: v.teks,
  pejabat: v.teks,
  nip: z.string().trim().refine((n) => n === "" || /^\d{18}$/.test(n.replace(/\s/g, "")), "NIP 18 digit angka."),
  foto: z.string().nullable(),
  anggota: z.array(z.string()),
  uraianTugas: v.teks,
  catatan: v.teks,
})
type FormUnit = z.infer<typeof schema>

function KotakUnit({ u, onBuka }: { u: UnitKerja; onBuka: () => void }) {
  const mitra = u.jenis === "mitra"
  return (
    <button
      type="button"
      onClick={onBuka}
      className={cn(
        "grid w-full gap-1.5 rounded-lg border bg-card p-3 text-left text-sm shadow-xs transition-colors hover:bg-muted/40 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none",
        mitra && "border-dashed bg-muted/20",
        u.jenis === "pimpinan" && "border-primary/40"
      )}
    >
      <span className="flex items-center gap-2">
        <span
          className={cn(
            "flex size-7 shrink-0 items-center justify-center rounded-md bg-primary text-[11px] font-semibold text-primary-foreground",
            mitra && "bg-muted text-muted-foreground"
          )}
        >
          {u.singkatan}
        </span>
        <span className="font-medium">{u.nama}</span>
      </span>
      {(u.jabatan || u.pejabat) && (
        <span className="text-xs text-muted-foreground">
          {[u.jabatan, u.pejabat].filter(Boolean).join(" · ")}
        </span>
      )}
      {u.anggota.length > 0 && (
        <ul className="grid gap-0.5 border-t pt-1.5 text-xs text-muted-foreground">
          {u.anggota.map((a) => (
            <li key={a}>{a}</li>
          ))}
        </ul>
      )}
    </button>
  )
}

function Bagan({ data, buka }: { data: UnitKerja[]; buka: (id: string) => void }) {
  const puncak = data.filter((u) => u.jenis !== "mitra" && !u.induk)
  const mitra = data.filter((u) => u.jenis === "mitra")
  return (
    <Card className="gap-4">
      <CardHeader>
        <CardTitle className="text-base">Bagan organisasi</CardTitle>
        <CardDescription>Klik kotak untuk menyunting. Garis putus-putus menandai mitra satu atap (koordinasi, bukan komando).</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-4">
        {puncak.map((p) => {
          const anak = data.filter((u) => u.induk === p.id)
          return (
            <div key={p.id} className="grid justify-items-center gap-0">
              <div className="w-full max-w-xs">
                <KotakUnit u={p} onBuka={() => buka(p.id)} />
              </div>
              {anak.length > 0 && (
                <>
                  <span aria-hidden="true" className="h-4 w-px bg-border" />
                  <ul className="grid w-full gap-3 border-t pt-4 sm:grid-cols-2 xl:grid-cols-4" aria-label={`Unit di bawah ${p.nama}`}>
                    {anak.map((u) => (
                      <li key={u.id}>
                        <KotakUnit u={u} onBuka={() => buka(u.id)} />
                      </li>
                    ))}
                  </ul>
                </>
              )}
            </div>
          )
        })}
        {mitra.length > 0 && (
          <ul className="grid gap-3 border-t border-dashed pt-4 sm:grid-cols-2" aria-label="Mitra satu atap">
            {mitra.map((u) => (
              <li key={u.id}>
                <KotakUnit u={u} onBuka={() => buka(u.id)} />
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}

const k = pembantuKolom<UnitKerja>()

export default function HalamanStruktur() {
  const { data, isLoading } = useKoleksi("unit")
  const urutkan = useUrutkanEntri("unit")
  const hapus = useHapus("unit", "unit")

  const ed = useEditorEntri({
    koleksi: "unit",
    schema,
    nama: "unit",
    kosong: (): FormUnit => ({
      nama: "",
      singkatan: "",
      jenis: "seksi",
      induk: data?.find((u) => u.jenis === "pimpinan")?.id ?? TANPA,
      jabatan: "",
      pejabat: "",
      nip: "",
      foto: null,
      anggota: [],
      uraianTugas: "",
      catatan: "",
    }),
    dariEntri: (u): FormUnit => ({
      nama: u.nama,
      singkatan: u.singkatan,
      jenis: u.jenis,
      induk: u.induk ?? TANPA,
      jabatan: u.jabatan,
      pejabat: u.pejabat,
      nip: u.nip,
      foto: u.foto,
      anggota: u.anggota,
      uraianTugas: u.uraianTugas,
      catatan: u.catatan,
    }),
    keEntriBaru: (f) => ({ ...f, induk: f.induk === TANPA ? null : f.induk, anggota: f.anggota.filter(Boolean) }),
    kePatch: (f) => ({ ...f, induk: f.induk === TANPA ? null : f.induk, anggota: f.anggota.filter(Boolean) }),
  })

  const namaUnit = React.useMemo(() => new Map(data?.map((u) => [u.id, u.nama])), [data])
  const opsiInduk = [
    { nilai: TANPA, label: "Tidak ada (puncak atau mitra)" },
    ...(data ?? [])
      .filter((u) => u.id !== ed.entri?.id && u.jenis !== "mitra")
      .map((u) => ({ nilai: u.id, label: u.nama })),
  ]

  const kolom = [
    k.accessor("nama", {
      header: "Unit",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => ed.bukaUbah(row.original.id)}
          className="flex min-w-56 items-center gap-2 rounded-md text-left whitespace-normal focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <span className="flex size-7 shrink-0 items-center justify-center rounded-md bg-muted text-[11px] font-semibold">
            {row.original.singkatan}
          </span>
          <span className="font-medium underline-offset-4 hover:underline">{row.original.nama}</span>
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("jenis", {
      header: "Jenis",
      cell: ({ row }) => (
        <Badge variant="outline" className="px-1.5 text-muted-foreground">
          {JENIS_UNIT[row.original.jenis as JenisUnit]}
        </Badge>
      ),
    }),
    k.accessor("pejabat", {
      header: "Pejabat",
      cell: ({ row }) => (
        <span className="text-sm whitespace-normal">
          {row.original.jabatan && <span className="text-muted-foreground">{row.original.jabatan} · </span>}
          {row.original.pejabat || "—"}
        </span>
      ),
    }),
    k.accessor((u) => (u.induk ? namaUnit.get(u.induk) ?? "" : ""), {
      id: "induk",
      header: "Di bawah",
      cell: ({ getValue }) => <span className="text-sm text-muted-foreground">{(getValue() as string) || "—"}</span>,
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.nama}
          onSunting={() => ed.bukaUbah(row.original.id)}
          onHapus={() => {
            const anak = data?.filter((u) => u.induk === row.original.id).length ?? 0
            if (anak > 0) {
              toast.error("Unit masih membawahi unit lain", {
                description: "Pindahkan unit di bawahnya terlebih dahulu.",
              })
              return
            }
            void hapus([row.original.id], row.original.nama)
          }}
        />
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Susunan unit kerja, pejabat, dan uraian tugas untuk halaman Struktur Organisasi. Nama seksi dan pejabat menyesuaikan Peraturan Gubernur NTT yang berlaku.">
        <Button onClick={ed.bukaBaru}>
          <IconPlus />
          Tambah unit
        </Button>
      </PengantarHalaman>
      {data && <Bagan data={data} buka={ed.bukaUbah} />}
      <DataTable
        data={data}
        memuat={isLoading}
        columns={kolom}
        label="Daftar unit kerja"
        pilih={false}
        urutkan={(ids) => urutkan.mutate(ids)}
        cari={{ placeholder: "Cari unit atau pejabat…", teks: (u) => `${u.nama} ${u.pejabat} ${u.jabatan} ${u.anggota.join(" ")}` }}
        kosong={{ judul: "Belum ada unit", deskripsi: "Tambahkan pimpinan dan unit kerja." }}
      />
      <EntriSheet
        terbuka={ed.terbuka}
        tutup={ed.tutup}
        judul={ed.entri ? "Sunting unit" : "Tambah unit"}
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
        lebar="sm:max-w-2xl"
      >
        <Baris className="sm:grid-cols-[minmax(0,1fr)_7rem]">
          <FieldTeks control={ed.form.control} name="nama" label="Nama unit" wajib />
          <FieldTeks control={ed.form.control} name="singkatan" label="Singkatan" wajib maxLength={3} className="[&_input]:uppercase" />
        </Baris>
        <Baris>
          <FieldPilih
            control={ed.form.control}
            name="jenis"
            label="Jenis"
            opsi={Object.entries(JENIS_UNIT).map(([nilai, label]) => ({ nilai, label }))}
          />
          <FieldPilih control={ed.form.control} name="induk" label="Berada di bawah" opsi={opsiInduk} />
        </Baris>
        <Baris>
          <FieldTeks control={ed.form.control} name="jabatan" label="Jabatan pimpinan unit" placeholder="Mis. Kepala Seksi" />
          <FieldTeks control={ed.form.control} name="pejabat" label="Nama pejabat" />
        </Baris>
        <div className="grid gap-5 sm:grid-cols-[180px_minmax(0,1fr)]">
          <FieldGambar control={ed.form.control} name="foto" label="Foto pejabat" rasio="aspect-[4/5]" deskripsi="Opsional." />
          <div className="grid content-start gap-5">
            <FieldTeks
              control={ed.form.control}
              name="nip"
              label="NIP pejabat"
              inputMode="numeric"
              maxLength={18}
              deskripsi="Opsional. Disamarkan di portal publik."
              className="[&_input]:font-mono"
            />
            <FieldDaftarTeks control={ed.form.control} name="anggota" label="Jabatan pelaksana" labelTambah="Tambah jabatan" />
          </div>
        </div>
        <FieldArea control={ed.form.control} name="uraianTugas" label="Uraian tugas" rows={4} />
        <FieldTeks control={ed.form.control} name="catatan" label="Catatan" />
      </EntriSheet>
    </Halaman>
  )
}
