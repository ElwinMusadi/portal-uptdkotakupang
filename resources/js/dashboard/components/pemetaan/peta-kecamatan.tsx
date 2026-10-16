import { cn } from "@/lib/utils"

import { BATAS_KECAMATAN, UKURAN_PETA } from "../../../portal/pemetaan/batas-kecamatan"

/*
 * Peta kecamatan Kota Kupang (D-052) dengan batas dari geoportal
 * (portal/pemetaan/batas-kecamatan.ts, yang juga dipakai peta portal).
 * Batasnya disederhanakan dan belum mengikuti batas kota yang definitif;
 * batas kelurahan menyusul bila berkas resminya ada.
 */

export interface PetakKecamatan {
  kunci: string
  nama: string
  /** Nilai 0–1 yang menentukan kepekatan warna */
  skala: number
  /** Teks nilai pada label kecamatan */
  label: string
}

export function PetaKecamatan({
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
  // Kecamatan yang dipilih digambar terakhir agar garis tepinya tidak tertutup tetangganya
  const peta = petak.filter((p) => BATAS_KECAMATAN[p.kunci]).sort((a, b) => Number(a.kunci === dipilih) - Number(b.kunci === dipilih))
  // Tulisan bertepi latar agar tetap terbaca di atas warna apa pun dan saat melewati batas
  const tulisan = "stroke-card [stroke-width:3px] [stroke-linejoin:round] [paint-order:stroke]"

  return (
    <figure className="grid gap-2">
      <svg viewBox={`0 0 ${UKURAN_PETA.lebar} ${UKURAN_PETA.tinggi}`} className="w-full max-w-md" role="group" aria-label="Peta kecamatan Kota Kupang">
        <text x={UKURAN_PETA.laut[0]} y={UKURAN_PETA.laut[1]} textAnchor="middle" className="fill-muted-foreground text-[11px] italic">
          Teluk Kupang
        </text>
        {peta.map((p) => {
          const aktif = dipilih === p.kunci
          return (
            <g
              key={p.kunci}
              role="button"
              tabIndex={0}
              aria-pressed={aktif}
              aria-label={`${p.nama}: ${p.label}`}
              className="cursor-pointer outline-none focus-visible:[&>path]:stroke-ring focus-visible:[&>path]:[stroke-width:3]"
              onClick={() => pilih(aktif ? null : p.kunci)}
              onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault()
                  pilih(aktif ? null : p.kunci)
                }
              }}
            >
              <path
                d={BATAS_KECAMATAN[p.kunci].jalur}
                style={{ fill: `color-mix(in oklab, var(--destructive) ${Math.round(8 + p.skala * 62)}%, var(--card))` }}
                className={cn("[stroke-linejoin:round] [vector-effect:non-scaling-stroke]", aktif ? "stroke-foreground [stroke-width:2.5]" : "stroke-card [stroke-width:1.5]")}
              />
            </g>
          )
        })}
        {peta.map((p) => {
          const { label, luar } = BATAS_KECAMATAN[p.kunci]
          const [x, y] = luar ?? label
          return (
            <g key={p.kunci} aria-hidden="true" className="pointer-events-none">
              {luar && <line x1={luar[0]} y1={luar[1] + 17} x2={label[0]} y2={label[1]} className="stroke-foreground [stroke-width:1] [vector-effect:non-scaling-stroke]" />}
              <text x={x} y={y} dy="-0.3em" textAnchor="middle" className={cn("fill-foreground text-[12px] font-semibold", tulisan)}>
                {p.nama}
              </text>
              <text x={x} y={y} dy="1.1em" textAnchor="middle" className={cn("fill-muted-foreground text-[11px]", tulisan)}>
                {p.label}
              </text>
            </g>
          )
        })}
      </svg>
      <figcaption className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
        <span>{judulSkala} per kecamatan:</span>
        <span className="inline-flex items-center gap-2 whitespace-nowrap">
          terendah
          <span
            aria-hidden="true"
            className="h-2 w-24 rounded-full"
            style={{ background: "linear-gradient(to right, color-mix(in oklab, var(--destructive) 8%, var(--card)), color-mix(in oklab, var(--destructive) 70%, var(--card)))" }}
          />
          tertinggi
        </span>
        <span>
          Batas kecamatan: geodatabase batas desa/kelurahan edisi Mei 2021 (BIG, lewat geoportal Kementerian Pertanian), disederhanakan dan belum mengikuti batas kota yang definitif. Pilih kecamatan untuk melihat kelurahannya.
        </span>
      </figcaption>
    </figure>
  )
}
