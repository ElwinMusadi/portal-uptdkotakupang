/*
 * Periode (Harian, Bulanan, Rentang) dan saringan Laporan Penerimaan (D-047),
 * dipakai halaman Laporan Penerimaan di dashboard dan halaman Ikhtisar di
 * /laporan-penerimaan (D-100).
 */
import * as React from "react"
import { IconCheck, IconChevronLeft, IconChevronRight, IconFilter, IconX } from "@tabler/icons-react"

import { potong } from "@/components/penerimaan/ringkasan-laporan"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command"
import { Input } from "@/components/ui/input"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { ToggleGroup, ToggleGroupItem } from "@/components/ui/toggle-group"
import { hariIniIso } from "@/lib/format"
import { DIMENSI, LABEL_DIMENSI, labelNilai, type Dimensi, type LaporanPenerimaan, type Saringan } from "@/lib/penerimaan"
import { cn } from "@/lib/utils"

import { gantiMode, geserPeriode, labelPeriode, periodeBulan, type Mode, type Periode } from "../../../portal/penerimaan/periode"

/** Nilai terpilih per saringan */
export type PilihanSaringan = Partial<Record<Dimensi, string[]>>

export function PanelSaringan({
  periode,
  ubahPeriode,
  galat,
  pilihan,
  pilih,
  ubahPilih,
  tunggakan,
  ubahTunggakan,
  adaSaringan,
  hapusSaringan,
  batasAkhir = hariIniIso(),
  satuBarisDiPonsel = false,
}: {
  periode: Periode
  ubahPeriode: (p: Periode) => void
  galat: string | null
  /** Nilai yang ada dalam periode; kosong selama laporan dimuat */
  pilihan: LaporanPenerimaan["pilihan"] | undefined
  pilih: PilihanSaringan
  ubahPilih: (d: Dimensi, nilai: string[]) => void
  tunggakan: Saringan["tunggakan"]
  ubahTunggakan: (t: Saringan["tunggakan"]) => void
  adaSaringan: boolean
  hapusSaringan: () => void
  /** Tanggal terakhir yang bisa dipilih; bawaannya hari ini */
  batasAkhir?: string
  /** Di layar sempit saringan digulir mendatar dalam satu baris, tidak bertumpuk */
  satuBarisDiPonsel?: boolean
}) {
  const hariIni = batasAkhir
  const galatId = React.useId()

  const opsi = (d: Dimensi): { nilai: string; label: string }[] => {
    if (!pilihan) return []
    if (d === "loket") return pilihan.loket.map((l) => ({ nilai: l.nilai, label: l.nama }))
    return pilihan[d].map((nilai) => ({ nilai, label: labelNilai(d, { nilai }) }))
  }

  return (
    <Card className="gap-4 py-4">
      <CardContent className="grid gap-4 px-4">
        <div className="flex flex-wrap items-center gap-2">
          <ToggleGroup
            type="single"
            variant="outline"
            value={periode.mode}
            onValueChange={(m) => m && ubahPeriode(gantiMode(periode, m as Mode, hariIni))}
            aria-label="Jenis laporan"
          >
            <ToggleGroupItem value="harian" className="px-3">
              Harian
            </ToggleGroupItem>
            <ToggleGroupItem value="bulanan" className="px-3">
              Bulanan
            </ToggleGroupItem>
            <ToggleGroupItem value="rentang" className="px-3">
              Rentang
            </ToggleGroupItem>
          </ToggleGroup>

          {periode.mode === "rentang" ? (
            <div className="flex flex-wrap items-center gap-2">
              <Input
                type="date"
                aria-label="Dari tanggal"
                aria-invalid={!!galat}
                aria-describedby={galat ? galatId : undefined}
                value={periode.dari}
                max={hariIni}
                onChange={(e) => ubahPeriode({ ...periode, dari: e.target.value })}
                className="w-auto"
              />
              <span className="text-sm text-muted-foreground">s.d.</span>
              <Input
                type="date"
                aria-label="Sampai tanggal"
                aria-invalid={!!galat}
                aria-describedby={galat ? galatId : undefined}
                value={periode.sampai}
                max={hariIni}
                onChange={(e) => ubahPeriode({ ...periode, sampai: e.target.value })}
                className="w-auto"
              />
            </div>
          ) : (
            <div className="flex items-center gap-1">
              <Button
                variant="outline"
                size="icon"
                aria-label={periode.mode === "harian" ? "Hari sebelumnya" : "Bulan sebelumnya"}
                disabled={!!galat}
                onClick={() => ubahPeriode(geserPeriode(periode, -1))}
              >
                <IconChevronLeft />
              </Button>
              {periode.mode === "harian" ? (
                <Input
                  type="date"
                  aria-label="Tanggal laporan"
                  aria-invalid={!!galat}
                  aria-describedby={galat ? galatId : undefined}
                  value={periode.dari}
                  max={hariIni}
                  onChange={(e) => ubahPeriode({ mode: "harian", dari: e.target.value, sampai: e.target.value })}
                  className="w-auto"
                />
              ) : (
                <Input
                  type="month"
                  aria-label="Bulan laporan"
                  value={periode.dari.slice(0, 7)}
                  max={hariIni.slice(0, 7)}
                  onChange={(e) => e.target.value && ubahPeriode(periodeBulan(e.target.value))}
                  className="w-auto"
                />
              )}
              <Button
                variant="outline"
                size="icon"
                aria-label={periode.mode === "harian" ? "Hari berikutnya" : "Bulan berikutnya"}
                disabled={!!galat || periode.sampai >= hariIni}
                onClick={() => ubahPeriode(geserPeriode(periode, 1))}
              >
                <IconChevronRight />
              </Button>
            </div>
          )}
          {!galat && (
            <p className="text-sm font-medium sm:ml-auto" aria-live="polite">
              {labelPeriode(periode.dari, periode.sampai)}
            </p>
          )}
        </div>
        {galat && (
          <p id={galatId} role="alert" className="text-sm text-destructive">
            {galat}
          </p>
        )}

        <div className={cn("flex flex-wrap items-center gap-2", satuBarisDiPonsel && "max-sm:-mx-4 max-sm:flex-nowrap max-sm:overflow-x-auto max-sm:px-4 max-sm:pb-1 max-sm:*:shrink-0")}>
          <IconFilter className="size-4 text-muted-foreground" aria-hidden="true" />
          <span className="sr-only">Saringan:</span>
          {DIMENSI.map((d) => (
            <PilihSaringan key={d} dimensi={d} opsi={opsi(d)} terpilih={pilih[d] ?? []} ubah={(nilai) => ubahPilih(d, nilai)} />
          ))}
          <Select value={tunggakan || "semua"} onValueChange={(v) => ubahTunggakan(v === "semua" ? "" : (v as "ada" | "tidak"))}>
            <SelectTrigger size="sm" aria-label="Saringan tunggakan" className={cn(tunggakan && "border-primary/40 bg-primary/5")}>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="semua">Tunggakan: semua</SelectItem>
              <SelectItem value="ada">Ada tunggakan</SelectItem>
              <SelectItem value="tidak">Tanpa tunggakan</SelectItem>
            </SelectContent>
          </Select>
          {adaSaringan && (
            <Button variant="ghost" size="sm" onClick={hapusSaringan}>
              <IconX />
              Hapus saringan
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  )
}

function PilihSaringan({
  dimensi,
  opsi,
  terpilih,
  ubah,
}: {
  dimensi: Dimensi
  opsi: { nilai: string; label: string }[]
  terpilih: string[]
  ubah: (nilai: string[]) => void
}) {
  const label = LABEL_DIMENSI[dimensi]
  // Nilai terpilih yang tidak ada di periode ini tetap tampil agar bisa dilepas
  const semua = [
    ...opsi,
    ...terpilih.filter((t) => !opsi.some((o) => o.nilai === t)).map((t) => ({ nilai: t, label: labelNilai(dimensi, { nilai: t }) })),
  ]
  const ringkas =
    terpilih.length === 1 ? potong(semua.find((o) => o.nilai === terpilih[0])?.label ?? terpilih[0], 24) : `${terpilih.length} dipilih`

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          size="sm"
          className={cn("border-dashed", terpilih.length > 0 && "border-solid border-primary/40 bg-primary/5")}
        >
          {label}
          {terpilih.length > 0 && (
            <Badge variant="secondary" className="rounded-sm px-1 font-normal">
              {ringkas}
            </Badge>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-72 p-0" align="start">
        <Command>
          <CommandInput placeholder={`Cari ${label.toLowerCase()}…`} />
          <CommandList>
            <CommandEmpty>Tidak ada pada periode ini.</CommandEmpty>
            <CommandGroup>
              {semua.map((o) => {
                const dipilih = terpilih.includes(o.nilai)
                return (
                  <CommandItem
                    key={o.nilai}
                    value={`${o.label} ${o.nilai}`}
                    onSelect={() => ubah(dipilih ? terpilih.filter((t) => t !== o.nilai) : [...terpilih, o.nilai])}
                  >
                    <span
                      className={cn(
                        "flex size-4 shrink-0 items-center justify-center rounded-[4px] border",
                        dipilih ? "border-primary bg-primary text-primary-foreground" : "border-input"
                      )}
                      aria-hidden="true"
                    >
                      {dipilih && <IconCheck className="size-3 text-current" />}
                    </span>
                    <span className="truncate">{o.label}</span>
                    {dipilih && <span className="sr-only">(dipilih)</span>}
                  </CommandItem>
                )
              })}
            </CommandGroup>
          </CommandList>
          {terpilih.length > 0 && (
            <div className="border-t p-1">
              <Button variant="ghost" size="sm" className="w-full" onClick={() => ubah([])}>
                Lepas semua pilihan
              </Button>
            </div>
          )}
        </Command>
      </PopoverContent>
    </Popover>
  )
}
