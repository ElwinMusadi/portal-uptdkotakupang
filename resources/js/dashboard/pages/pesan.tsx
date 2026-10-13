import * as React from "react"
import { IconCheck, IconClipboardPlus, IconSend } from "@tabler/icons-react"
import { useSearchParams } from "react-router"
import { toast } from "sonner"
import { z } from "zod"

import { DataTable, HeaderUrut, pembantuKolom } from "@/components/data/data-table"
import { Baris, Halaman, PengantarHalaman } from "@/components/data/page"
import { MenuBaris, TombolHapusMassal, useHapus } from "@/components/data/row-actions"
import { NadaBadge, TitikStatus } from "@/components/data/status-badge"
import { EntriSheet, useEditorEntri } from "@/components/form/entri-sheet"
import { FieldArea, FieldPilih, FieldTeks } from "@/components/form/fields"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { DropdownMenuItem } from "@/components/ui/dropdown-menu"
import { Label } from "@/components/ui/label"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import type { JenisPesan, Pesan, StatusPesan, SumberPesan } from "@/lib/api"
import { tanggalJam, waktuRelatif } from "@/lib/format"
import { newId, newTiket } from "@/lib/id"
import { LAYANAN_PESAN, PESAN_JENIS, PESAN_STATUS, PESAN_SUMBER } from "@/lib/meta"
import { useKoleksi, usePengaturan, useUbahEntri } from "@/lib/queries"
import * as v from "@/lib/validasi"

const schema = z.object({
  jenis: z.enum(["pertanyaan", "masukan", "pengaduan"]),
  nama: v.teks,
  kontak: v.teks,
  layanan: v.wajib("Layanan"),
  isi: v.wajib("Isi pesan").max(2000, "Maksimal 2.000 karakter."),
  sumber: z.enum(["formulir", "kotak-saran", "lapor", "whatsapp", "telepon"]),
})
type FormPesan = z.infer<typeof schema>

const k = pembantuKolom<Pesan>()

function DetailPesan({ pesan, tutup }: { pesan: Pesan | null; tutup: () => void }) {
  const ubah = useUbahEntri("pesan")
  const { data: akun } = usePengaturan("akun")
  const [balasan, setBalasan] = React.useState("")
  const [selesai, setSelesai] = React.useState(true)
  const idBalasan = React.useId()
  const idStatus = React.useId()
  const nama = akun?.nama ?? "Pengelola PINTU"

  React.useEffect(() => {
    setBalasan("")
    setSelesai(true)
  }, [pesan?.id])

  // pesan baru otomatis ditandai sedang diproses saat dibuka
  React.useEffect(() => {
    if (pesan?.status === "baru") {
      ubah.mutate({ id: pesan.id, patch: { status: "diproses", ditangani: pesan.ditangani || nama } })
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pesan?.id])

  const kirim = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!pesan || !balasan.trim()) return
    try {
      await ubah.mutateAsync({
        id: pesan.id,
        patch: {
          tanggapan: [...pesan.tanggapan, { id: newId(), isi: balasan.trim(), oleh: nama, waktu: new Date().toISOString() }],
          status: selesai ? "selesai" : "diproses",
          ditangani: pesan.ditangani || nama,
        },
      })
      setBalasan("")
      toast.success(selesai ? "Tanggapan dicatat dan pesan ditandai selesai" : "Tanggapan dicatat")
    } catch {
      // galat ditampilkan oleh mutasi
    }
  }

  return (
    <Sheet open={!!pesan} onOpenChange={(o) => !o && tutup()}>
      <SheetContent className="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
        {pesan && (
          <>
            <SheetHeader className="border-b pr-12">
              <SheetTitle className="flex flex-wrap items-center gap-2">
                <span className="font-mono">{pesan.tiket}</span>
                <Badge variant="outline">{PESAN_JENIS[pesan.jenis]}</Badge>
              </SheetTitle>
              <SheetDescription>
                Diterima {tanggalJam(pesan.diterima)} lewat {PESAN_SUMBER[pesan.sumber]}
              </SheetDescription>
            </SheetHeader>
            <div className="grid flex-1 content-start gap-5 overflow-y-auto p-4">
              <blockquote className="rounded-lg border-l-2 border-brand bg-muted/40 p-4 text-[15px] leading-relaxed whitespace-pre-line">
                {pesan.isi}
              </blockquote>
              <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                  <dt className="text-muted-foreground">Pengirim</dt>
                  <dd>{pesan.nama || "Tanpa nama"}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Kontak</dt>
                  <dd className="break-all">{pesan.kontak || "—"}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Layanan</dt>
                  <dd>{pesan.layanan}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Ditangani</dt>
                  <dd>{pesan.ditangani || "—"}</dd>
                </div>
              </dl>
              <div className="grid gap-2">
                <Label htmlFor={idStatus}>Status</Label>
                <Select
                  value={pesan.status}
                  onValueChange={(s) => ubah.mutate({ id: pesan.id, patch: { status: s as StatusPesan } })}
                >
                  <SelectTrigger id={idStatus} className="w-48">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {(Object.keys(PESAN_STATUS) as StatusPesan[]).map((s) => (
                      <SelectItem key={s} value={s}>
                        {PESAN_STATUS[s].label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="grid gap-3">
                <p className="text-sm font-medium">Tanggapan</p>
                {pesan.tanggapan.length === 0 ? (
                  <p className="text-sm text-muted-foreground">Belum ada tanggapan. Target balasan paling lambat tiga hari kerja.</p>
                ) : (
                  <ol className="grid gap-2">
                    {pesan.tanggapan.map((t) => (
                      <li key={t.id} className="rounded-lg border p-3 text-sm">
                        <p className="whitespace-pre-line">{t.isi}</p>
                        <p className="mt-1.5 text-xs text-muted-foreground">
                          {t.oleh} · {tanggalJam(t.waktu)}
                        </p>
                      </li>
                    ))}
                  </ol>
                )}
                <form onSubmit={kirim} className="grid gap-2">
                  <Label htmlFor={idBalasan} className="sr-only">
                    Tulis tanggapan
                  </Label>
                  <Textarea
                    id={idBalasan}
                    value={balasan}
                    onChange={(e) => setBalasan(e.target.value)}
                    rows={4}
                    placeholder="Tulis tanggapan atau catatan tindak lanjut…"
                  />
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <Label className="font-normal">
                      <Checkbox checked={selesai} onCheckedChange={(c) => setSelesai(!!c)} />
                      Tandai selesai
                    </Label>
                    <Button type="submit" size="sm" disabled={!balasan.trim() || ubah.isPending}>
                      {ubah.isPending ? <Spinner /> : <IconSend />}
                      Catat tanggapan
                    </Button>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    Dashboard mencatat tanggapan; kirimkan balasan ke kontak pengirim lewat email atau WhatsApp resmi.
                  </p>
                </form>
              </div>
            </div>
          </>
        )}
      </SheetContent>
    </Sheet>
  )
}

export default function HalamanPesan() {
  const [params, setParams] = useSearchParams()
  const { data, isLoading } = useKoleksi("pesan")
  const ubah = useUbahEntri("pesan")
  const hapus = useHapus("pesan", "pesan")
  const statusUrl = params.get("status") ?? "semua"
  const filterAwal = React.useMemo(() => ({ status: statusUrl }), [statusUrl])

  const ed = useEditorEntri({
    koleksi: "pesan",
    schema,
    nama: "pesan",
    kosong: (): FormPesan => ({ jenis: "pengaduan", nama: "", kontak: "", layanan: LAYANAN_PESAN[0], isi: "", sumber: "kotak-saran" }),
    dariEntri: (p): FormPesan => ({ jenis: p.jenis, nama: p.nama, kontak: p.kontak, layanan: p.layanan, isi: p.isi, sumber: p.sumber }),
    keEntriBaru: (f) => {
      const kini = new Date().toISOString()
      return { ...f, tiket: newTiket(), status: "baru" as StatusPesan, diterima: kini, ditangani: "", tanggapan: [] }
    },
  })

  // ?ubah=<id> membuka detail, bukan formulir
  const detail = params.get("ubah") ? (data?.find((p) => p.id === params.get("ubah")) ?? null) : null
  const bukaDetail = (id: string) => setParams((p) => {
    const n = new URLSearchParams(p)
    n.set("ubah", id)
    return n
  })
  const tutupDetail = () => setParams((p) => {
    const n = new URLSearchParams(p)
    n.delete("ubah")
    return n
  }, { replace: true })

  const terurut = React.useMemo(() => data?.slice().sort((a, b) => b.diterima.localeCompare(a.diterima)), [data])
  const jumlah = (s: StatusPesan) => data?.filter((p) => p.status === s).length ?? 0

  const setStatus = async (baris: Pesan[], status: StatusPesan) => {
    for (const p of baris) await ubah.mutateAsync({ id: p.id, patch: { status } })
    toast.success(`${baris.length} pesan ditandai ${PESAN_STATUS[status].label.toLowerCase()}`)
  }

  const kolom = [
    k.accessor("tiket", {
      header: "Tiket",
      cell: ({ row }) => (
        <button
          type="button"
          onClick={() => bukaDetail(row.original.id)}
          className="rounded-sm font-mono text-sm font-medium underline-offset-4 hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          {row.original.tiket}
          {row.original.status === "baru" && <span className="sr-only"> (baru)</span>}
        </button>
      ),
      enableHiding: false,
    }),
    k.accessor("jenis", {
      header: "Jenis",
      cell: ({ row }) =>
        row.original.jenis === "pengaduan" ? (
          <NadaBadge nada="peringatan">Pengaduan</NadaBadge>
        ) : (
          <Badge variant="outline" className="px-1.5 text-muted-foreground">
            {PESAN_JENIS[row.original.jenis]}
          </Badge>
        ),
    }),
    k.accessor("isi", {
      header: "Pesan",
      cell: ({ row }) => (
        <div className="grid max-w-[30rem] min-w-64 gap-0.5 whitespace-normal">
          <span className={row.original.status === "baru" ? "line-clamp-2 font-medium" : "line-clamp-2"}>
            {row.original.isi}
          </span>
          <span className="text-xs text-muted-foreground">
            {row.original.nama || "Tanpa nama"} · {row.original.layanan}
          </span>
        </div>
      ),
    }),
    k.accessor("sumber", {
      header: "Sumber",
      cell: ({ row }) => <span className="text-sm text-muted-foreground">{PESAN_SUMBER[row.original.sumber]}</span>,
    }),
    k.accessor("diterima", {
      header: ({ column }) => <HeaderUrut column={column} judul="Diterima" />,
      cell: ({ row }) => (
        <time dateTime={row.original.diterima} title={tanggalJam(row.original.diterima)} className="text-sm whitespace-nowrap">
          {waktuRelatif(row.original.diterima)}
        </time>
      ),
    }),
    k.accessor("status", {
      header: "Status",
      cell: ({ row }) => (
        <TitikStatus nada={PESAN_STATUS[row.original.status].nada}>{PESAN_STATUS[row.original.status].label}</TitikStatus>
      ),
    }),
    k.display({
      id: "aksi",
      cell: ({ row }) => (
        <MenuBaris
          label={row.original.tiket}
          onHapus={() => void hapus([row.original.id], row.original.tiket)}
        >
          <DropdownMenuItem onSelect={() => bukaDetail(row.original.id)}>Buka & tanggapi</DropdownMenuItem>
          {row.original.status !== "selesai" && (
            <DropdownMenuItem onSelect={() => void setStatus([row.original], "selesai")}>
              <IconCheck />
              Tandai selesai
            </DropdownMenuItem>
          )}
        </MenuBaris>
      ),
    }),
  ]

  return (
    <Halaman>
      <PengantarHalaman deskripsi="Pertanyaan, masukan, dan pengaduan dari formulir portal serta kanal lain. Identitas pelapor dijaga kerahasiaannya; balas paling lambat tiga hari kerja.">
        <Button variant="outline" onClick={ed.bukaBaru}>
          <IconClipboardPlus />
          Catat pesan masuk
        </Button>
      </PengantarHalaman>
      <ToggleGroup
        type="single"
        variant="outline"
        value={statusUrl}
        onValueChange={(s) =>
          s &&
          setParams((p) => {
            const n = new URLSearchParams(p)
            if (s === "semua") n.delete("status")
            else n.set("status", s)
            return n
          })
        }
        className="w-fit flex-wrap"
        aria-label="Saring menurut status"
      >
        <ToggleGroupItem value="semua" className="px-3">
          Semua <span className="ml-1 text-muted-foreground tabular-nums">{data?.length ?? 0}</span>
        </ToggleGroupItem>
        {(Object.keys(PESAN_STATUS) as StatusPesan[]).map((s) => (
          <ToggleGroupItem key={s} value={s} className="px-3">
            {PESAN_STATUS[s].label} <span className="ml-1 text-muted-foreground tabular-nums">{jumlah(s)}</span>
          </ToggleGroupItem>
        ))}
      </ToggleGroup>
      <DataTable
        data={terurut}
        memuat={isLoading}
        columns={kolom}
        label="Kotak pesan dan pengaduan"
        filterAwal={filterAwal}
        cari={{ placeholder: "Cari tiket, isi, atau nama…", teks: (p) => `${p.tiket} ${p.isi} ${p.nama} ${p.kontak}` }}
        filter={[
          {
            id: "status",
            label: "Status",
            opsi: (Object.keys(PESAN_STATUS) as StatusPesan[]).map((s) => ({ nilai: s, label: PESAN_STATUS[s].label })),
            nilai: (p) => p.status,
          },
          {
            id: "jenis",
            label: "Jenis",
            opsi: (Object.keys(PESAN_JENIS) as JenisPesan[]).map((j) => ({ nilai: j, label: PESAN_JENIS[j] })),
            nilai: (p) => p.jenis,
          },
          {
            id: "layanan",
            label: "Layanan",
            opsi: LAYANAN_PESAN.map((l) => ({ nilai: l, label: l })),
            nilai: (p) => p.layanan,
          },
        ]}
        tindakanMassal={(baris, bersihkan) => (
          <>
            <Button variant="outline" size="sm" onClick={() => void setStatus(baris, "diproses").then(bersihkan)}>
              Tandai diproses
            </Button>
            <Button variant="outline" size="sm" onClick={() => void setStatus(baris, "selesai").then(bersihkan)}>
              Tandai selesai
            </Button>
            <TombolHapusMassal
              onClick={async () => {
                if (await hapus(baris.map((b) => b.id))) bersihkan()
              }}
            />
          </>
        )}
        kosong={{ judul: "Belum ada pesan", deskripsi: "Pesan dari formulir portal akan muncul di sini." }}
      />
      <DetailPesan pesan={detail} tutup={tutupDetail} />
      <EntriSheet
        terbuka={ed.baru}
        tutup={ed.tutup}
        judul="Catat pesan masuk"
        deskripsi="Untuk pesan dari kotak saran, WhatsApp, telepon, atau SP4N-LAPOR!."
        form={ed.form}
        simpan={ed.simpan}
        menyimpan={ed.menyimpan}
      >
        <Baris>
          <FieldPilih
            control={ed.form.control}
            name="jenis"
            label="Jenis"
            opsi={(Object.keys(PESAN_JENIS) as JenisPesan[]).map((j) => ({ nilai: j, label: PESAN_JENIS[j] }))}
          />
          <FieldPilih
            control={ed.form.control}
            name="sumber"
            label="Sumber"
            opsi={(Object.keys(PESAN_SUMBER) as SumberPesan[]).map((s) => ({ nilai: s, label: PESAN_SUMBER[s] }))}
          />
        </Baris>
        <FieldPilih
          control={ed.form.control}
          name="layanan"
          label="Terkait layanan"
          opsi={LAYANAN_PESAN.map((l) => ({ nilai: l, label: l }))}
        />
        <Baris>
          <FieldTeks control={ed.form.control} name="nama" label="Nama pengirim" placeholder="Opsional" />
          <FieldTeks control={ed.form.control} name="kontak" label="Email atau WhatsApp" placeholder="Opsional" />
        </Baris>
        <FieldArea control={ed.form.control} name="isi" label="Isi pesan" wajib rows={6} maks={2000} />
      </EntriSheet>
    </Halaman>
  )
}
