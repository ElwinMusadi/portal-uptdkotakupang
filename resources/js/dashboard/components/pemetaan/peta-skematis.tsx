import { cn } from "@/lib/utils"

/*
 * Peta skematis Kota Kupang (D-052): enam kecamatan sebagai petak yang
 * letaknya kira-kira mengikuti peta (laut di utara, Alak di barat, Kelapa
 * Lima di timur laut, Maulafa di selatan). Bukan batas wilayah sebenarnya;
 * diganti peta batas resmi bila berkasnya tersedia (keputusan pemilik).
 */

export interface PetakKecamatan {
  kunci: string
  nama: string
  /** Nilai 0–1 yang menentukan kepekatan warna */
  skala: number
  /** Teks nilai di dalam petak */
  label: string
}

/** Letak petak dalam kotak 400 × 300, utara di atas */
const LETAK: Record<string, { x: number; y: number; w: number; h: number }> = {
  ALAK: { x: 8, y: 52, w: 112, h: 176 },
  "KOTA LAMA": { x: 128, y: 30, w: 118, h: 72 },
  "KELAPA LIMA": { x: 254, y: 20, w: 138, h: 92 },
  "KOTA RAJA": { x: 128, y: 110, w: 100, h: 82 },
  OEBOBO: { x: 236, y: 120, w: 156, h: 72 },
  MAULAFA: { x: 128, y: 200, w: 264, h: 92 },
}

export function PetaSkematis({
  petak,
  dipilih,
  pilih,
  judulSkala,
}: {
  petak: PetakKecamatan[]
  dipilih: string | null
  pilih: (kunci: string | null) => void
  judulSkala: string
}) {
  return (
    <figure className="grid gap-2">
      <svg viewBox="0 0 400 300" className="w-full max-w-xl" role="group" aria-label="Peta skematis kecamatan Kota Kupang">
        <text x="200" y="12" textAnchor="middle" className="fill-muted-foreground text-[9px]">
          Teluk Kupang (utara)
        </text>
        {petak.map((p) => {
          const l = LETAK[p.kunci]
          if (!l) return null
          const aktif = dipilih === p.kunci
          return (
            <g
              key={p.kunci}
              role="button"
              tabIndex={0}
              aria-pressed={aktif}
              aria-label={`${p.nama}: ${p.label}`}
              className="cursor-pointer outline-none focus-visible:[&>rect]:stroke-ring"
              onClick={() => pilih(aktif ? null : p.kunci)}
              onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault()
                  pilih(aktif ? null : p.kunci)
                }
              }}
            >
              <rect
                x={l.x}
                y={l.y}
                width={l.w}
                height={l.h}
                rx={10}
                style={{ fill: `color-mix(in oklab, var(--destructive) ${Math.round(8 + p.skala * 62)}%, var(--card))` }}
                className={cn("stroke-border transition-[stroke-width]", aktif ? "stroke-foreground [stroke-width:3]" : "[stroke-width:1]")}
              />
              <text x={l.x + l.w / 2} y={l.y + l.h / 2 - 4} textAnchor="middle" className={cn("text-[12px] font-semibold", p.skala > 0.55 ? "fill-white" : "fill-foreground")}>
                {p.nama}
              </text>
              <text x={l.x + l.w / 2} y={l.y + l.h / 2 + 13} textAnchor="middle" className={cn("text-[11px]", p.skala > 0.55 ? "fill-white" : "fill-muted-foreground")}>
                {p.label}
              </text>
            </g>
          )
        })}
      </svg>
      <figcaption className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
        <span>{judulSkala}: terendah</span>
        <span
          aria-hidden="true"
          className="h-2 w-24 rounded-full"
          style={{ background: "linear-gradient(to right, color-mix(in oklab, var(--destructive) 8%, var(--card)), color-mix(in oklab, var(--destructive) 70%, var(--card)))" }}
        />
        <span>tertinggi di antara kecamatan.</span>
        <span>Peta skematis: letak kecamatan disederhanakan, bukan batas wilayah sebenarnya. Pilih kecamatan untuk melihat kelurahannya.</span>
      </figcaption>
    </figure>
  )
}
