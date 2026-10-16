import { useCallback, useEffect, useRef, type CSSProperties } from "react"

import { cn } from "@/lib/utils"

import { BATAS_KECAMATAN, BATAS_KELURAHAN, UKURAN_PETA } from "../../../portal/pemetaan/batas-wilayah"
import { kotakTampilan, labelTerpasang, useLebar } from "../../../portal/pemetaan/peta"

/*
 * Peta wilayah Kota Kupang (D-052) dengan batas Desa/Kelurahan BIG 1:10.000
 * edisi 2022 (portal/pemetaan/batas-wilayah.ts, juga dipakai peta portal):
 * enam kecamatan, atau kelurahan kecamatan yang dipilih (peta diperbesar ke
 * kecamatan itu, kecamatan lain redup dan bisa dipilih). Peta setinggi 600 px
 * (paling tinggi 75% layar) dengan lebar mengikuti bentuk tampilan (`--rasio`),
 * sedikitnya 448 px, seperti di portal. Tulisan diukur dari lebar peta yang
 * tampil (`--px`) dan label yang akan saling menimpa dilewati.
 */

export interface PetakWilayah {
  /** Kunci kecamatan, atau nama kelurahan seperti daftar resmi */
  kunci: string
  nama: string
  /** Nilai 0–1 yang menentukan kepekatan warna */
  skala: number
  /** Teks nilai pada label */
  label: string
}

const isian = (skala: number | null) => (skala === null ? "var(--muted)" : `color-mix(in oklab, var(--destructive) ${Math.round(8 + skala * 62)}%, var(--card))`)

// Tulisan bertepi latar agar tetap terbaca di atas warna apa pun dan saat melewati batas; --px = satuan peta per piksel
const tulisan = "stroke-card [stroke-width:calc(3px*var(--px))] [stroke-linejoin:round] [paint-order:stroke]"

export function PetaWilayah({
  petak,
  kelurahan,
  dipilih,
  pilih,
  judulSkala,
}: {
  /** Kecamatan */
  petak: PetakWilayah[]
  /** Kelurahan kecamatan yang dipilih */
  kelurahan: PetakWilayah[]
  dipilih: string | null
  pilih: (kunci: string | null) => void
  judulSkala: string
}) {
  const kecamatan = petak.filter((p) => BATAS_KECAMATAN[p.kunci])
  const aktif = kecamatan.find((p) => p.kunci === dipilih) ?? null
  const [ref, lebar] = useLebar<SVGSVGElement>()
  const petaRef = useRef<SVGSVGElement | null>(null)
  const refPeta = useCallback((el: SVGSVGElement | null) => {
    petaRef.current = el
    return ref(el)
  }, [ref])
  const kembaliRef = useRef<HTMLButtonElement>(null)
  // Kecamatan yang dipilih tidak lagi berupa tombol di peta: fokus pindah ke "Semua kecamatan", lalu kembali ke kecamatan itu
  const fokusKe = useRef<string | null>(null)
  useEffect(() => {
    const tujuan = fokusKe.current
    fokusKe.current = null
    if (tujuan === "") kembaliRef.current?.focus()
    else if (tujuan) petaRef.current?.querySelector<SVGGElement>(`[data-kunci="${tujuan}"]`)?.focus()
  }, [dipilih])
  const tampil = kotakTampilan(aktif ? BATAS_KECAMATAN[aktif.kunci].kotak : undefined)
  const px = tampil.w / (lebar ?? 448)
  const batasKelurahan = aktif ? Object.entries(BATAS_KELURAHAN).filter(([, b]) => b.kecamatan === aktif.kunci) : []
  const nama = (teks: string) => teks.toLowerCase().replace(/(^|[\s-])([a-z])/g, (_, a: string, b: string) => a + b.toUpperCase()).replace(/\b(Ii|Iii|Iv)\b/g, (r) => r.toUpperCase())
  const berlabel = labelTerpasang(
    batasKelurahan.map(([kunci, b]) => ({ kunci, titik: b.label, ruang: b.ruang, nama: nama(kunci), nilai: kelurahan.find((k) => k.kunci === kunci)?.label ?? "—" })),
    px,
  )
  const pilihKecamatan = (kunci: string) => {
    fokusKe.current = ""
    pilih(dipilih === kunci ? null : kunci)
  }

  return (
    <figure className="grid gap-2">
      {aktif && (
        <button
          ref={kembaliRef}
          type="button"
          className="justify-self-start text-sm text-primary underline underline-offset-4"
          onClick={() => {
            fokusKe.current = dipilih
            pilih(null)
          }}
        >
          ← Semua kecamatan
        </button>
      )}
      <svg
        ref={refPeta}
        viewBox={`${tampil.x} ${tampil.y} ${tampil.w} ${tampil.h}`}
        className="w-full max-w-[max(28rem,min(600px,75svh)*var(--rasio))]"
        style={{ "--px": px, "--rasio": tampil.w / tampil.h } as CSSProperties}
        role="group"
        aria-label={aktif ? `Peta kelurahan di ${aktif.nama}` : "Peta kecamatan Kota Kupang"}
      >
        {!aktif && (
          <text x={UKURAN_PETA.laut[0]} y={UKURAN_PETA.laut[1]} textAnchor="middle" className="fill-muted-foreground text-[calc(11px*var(--px))] italic">
            Teluk Kupang
          </text>
        )}
        {kecamatan
          .filter((p) => p.kunci !== aktif?.kunci)
          .map((p) => (
            <g
              key={p.kunci}
              data-kunci={p.kunci}
              role="button"
              tabIndex={0}
              aria-pressed={false}
              aria-label={aktif ? `Tampilkan kelurahan di ${p.nama}` : `${p.nama}: ${p.label}`}
              className={cn("cursor-pointer outline-none focus-visible:[&>path]:stroke-ring focus-visible:[&>path]:[stroke-width:3]", aktif && "opacity-70 hover:opacity-100")}
              onClick={() => pilihKecamatan(p.kunci)}
              onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault()
                  pilihKecamatan(p.kunci)
                }
              }}
            >
              <path
                d={BATAS_KECAMATAN[p.kunci].jalur}
                style={{ fill: aktif ? "var(--muted)" : isian(p.skala) }}
                className="stroke-card [stroke-linejoin:round] [stroke-width:1.5] [vector-effect:non-scaling-stroke]"
              />
            </g>
          ))}
        {batasKelurahan.map(([kunci, b]) => {
          const p = kelurahan.find((k) => k.kunci === kunci)
          const teks = `${nama(kunci)}: ${p ? p.label : "tidak ada kendaraan"}`
          return (
            <g key={kunci} role="img" aria-label={teks}>
              <title>{teks}</title>
              <path d={b.jalur} style={{ fill: isian(p ? p.skala : null) }} className="stroke-card [stroke-linejoin:round] [stroke-width:1] [vector-effect:non-scaling-stroke]" />
            </g>
          )
        })}
        {aktif && <path d={BATAS_KECAMATAN[aktif.kunci].jalur} className="pointer-events-none fill-none stroke-foreground [stroke-width:2] [vector-effect:non-scaling-stroke]" />}
        {aktif
          ? batasKelurahan
              .filter(([kunci]) => berlabel.has(kunci))
              .map(([kunci, b]) => {
                const p = kelurahan.find((k) => k.kunci === kunci)
                return (
                  <g key={kunci} aria-hidden="true" className="pointer-events-none">
                    <text x={b.label[0]} y={b.label[1]} dy="-0.3em" textAnchor="middle" className={cn("fill-foreground text-[calc(12px*var(--px))] font-semibold", tulisan)}>
                      {nama(kunci)}
                    </text>
                    <text x={b.label[0]} y={b.label[1]} dy="1.1em" textAnchor="middle" className={cn("fill-muted-foreground text-[calc(11px*var(--px))]", tulisan)}>
                      {p ? p.label : "—"}
                    </text>
                  </g>
                )
              })
          : kecamatan.map((p) => {
              const { label, luar } = BATAS_KECAMATAN[p.kunci]
              const [x, y] = luar ?? label
              return (
                <g key={p.kunci} aria-hidden="true" className="pointer-events-none">
                  {luar && <line x1={luar[0]} y1={luar[1] + 17 * px} x2={label[0]} y2={label[1]} className="stroke-foreground [stroke-width:1] [vector-effect:non-scaling-stroke]" />}
                  <text x={x} y={y} dy="-0.3em" textAnchor="middle" className={cn("fill-foreground text-[calc(12px*var(--px))] font-semibold", tulisan)}>
                    {p.nama}
                  </text>
                  <text x={x} y={y} dy="1.1em" textAnchor="middle" className={cn("fill-muted-foreground text-[calc(11px*var(--px))]", tulisan)}>
                    {p.label}
                  </text>
                </g>
              )
            })}
      </svg>
      <figcaption className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
        <span>{aktif ? `${judulSkala} per kelurahan di ${aktif.nama}:` : `${judulSkala} per kecamatan:`}</span>
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
          Batas wilayah: Batas Desa/Kelurahan skala 1:10.000 edisi 2022, Badan Informasi Geospasial (disederhanakan). {aktif ? "Pilih kecamatan lain di peta, atau kembali ke semua kecamatan." : "Pilih kecamatan untuk melihat kelurahannya."}
        </span>
      </figcaption>
    </figure>
  )
}
