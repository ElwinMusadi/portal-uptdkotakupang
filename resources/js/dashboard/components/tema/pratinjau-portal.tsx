import type * as React from "react"

import { DotPattern } from "@/components/ui/dot-pattern"
import type { ModePortal, MotifLatar } from "@/lib/api"
import { WARNA_TITIK } from "@/lib/tema"

/** Warna portal yang tidak diatur tema (tokens.css), untuk melengkapi contoh */
const TETAP: Record<string, string> = {
  "--night-muted": "#9BA0A8",
  "--night-faint": "#5C6068",
  "--logo-leaf-night": "#7FA4FF",
}

const BATANG = [38, 52, 44, 70, 86, 61]

/**
 * Contoh kecil portal dengan tema yang sedang disunting: tombol, tautan,
 * lencana, kartu, isian, kotak abu, dan panel gelap. Variabel portal diberi
 * awalan --p- karena namanya (mis. --accent) juga dipakai dashboard. Skala
 * diterapkan dengan zoom, seperti di portal; mode gelap lewat variabelnya,
 * dengan --chrome untuk permukaan yang tetap gelap dan --on-ink untuk teks
 * di atas tombol yang dibalik. Tombol gelap memakai --ink-fill (warna gelap);
 * navbar memakai --nav-surface dan teks --nav-tint (warna navbar). Motif
 * titik digambar DotPattern di belakang isi, dengan petak dan warna yang
 * sama dengan /tema-portal.css (D-073).
 */
export function PratinjauPortal({
  variabel,
  skala,
  motif,
  mode,
}: {
  variabel: Record<string, string>
  skala: number
  motif: MotifLatar
  mode: ModePortal
}) {
  const gaya = Object.fromEntries(
    Object.entries({ ...TETAP, ...variabel }).map(([nama, nilai]) => [`--p-${nama.slice(2)}`, nilai])
  ) as React.CSSProperties
  // Skala portal memakai zoom halaman; contoh ini diperbesar dengan cara yang sama
  gaya.zoom = skala / 100

  return (
    <div
      style={gaya}
      aria-label="Contoh portal dengan tema ini"
      role="img"
      className="relative isolate grid gap-4 overflow-hidden rounded-xl border bg-(--p-paper) p-4 font-(family-name:--p-font-sans) text-(--p-ink)"
    >
      {motif === "titik" && <DotPattern className="-z-10" style={{ fill: WARNA_TITIK[mode] }} />}
      <div className="flex items-center justify-between rounded-full bg-(--p-nav-surface) py-2 pr-2 pl-4 text-xs text-[rgb(var(--p-nav-tint))]">
        <span className="flex items-center gap-2 font-semibold tracking-[0.08em]">
          <span aria-hidden="true" className="size-3 rotate-45 rounded-[3px] bg-(--p-logo-leaf-night)" />
          PINTU
        </span>
        <span className="rounded-full bg-(--p-nav-cta) px-3 py-1 text-(--p-nav-cta-text)">Masuk</span>
      </div>

      <div className="grid gap-2">
        <p className="text-[11px] tracking-[0.12em] text-(--p-text-2) uppercase">UPTD Pendapatan Daerah · Kota Kupang</p>
        <p className="font-(family-name:--p-font-display) text-2xl leading-tight font-(--p-fw-display) tracking-tight">
          Semua informasi Samsat.
          <span className="block text-(--p-gray)">Lewat satu pintu.</span>
        </p>
        <p className="text-sm text-(--p-text-2)">Cek persyaratan, jadwal, dan lokasi layanan sebelum Anda berangkat.</p>
      </div>

      <div className="flex flex-wrap items-center gap-2 text-sm font-medium">
        <span className="rounded-full bg-(--p-ink-fill) px-4 py-2 text-(--p-on-ink)">Cek persyaratan</span>
        <span className="rounded-full bg-(--p-accent) px-4 py-2 text-(--p-on-ink)">Ambil antrean</span>
        <span className="text-(--p-accent-ink) underline decoration-(--p-accent-line) decoration-2 underline-offset-4">
          Lihat jadwal →
        </span>
      </div>

      <div className="grid gap-3 rounded-[calc(24px*var(--p-r-scale))] border border-(--p-line) bg-(--p-surface) p-4">
        <div className="flex items-center justify-between gap-2">
          <p className="font-(family-name:--p-font-display) text-[15px] font-(--p-fw-display)">Perpanjangan STNK</p>
          <span className="rounded-full bg-(--p-accent-soft) px-2.5 py-0.5 text-xs font-medium text-(--p-accent-ink)">Baru</span>
        </div>
        <p className="text-xs text-(--p-text-2)">Bawa KTP asli dan STNK lama ke loket pendaftaran.</p>
        <span className="rounded-[calc(14px*var(--p-r-scale))] border border-(--p-line-2) px-3 py-2 text-sm text-(--p-text-2) shadow-[0_0_0_3px_var(--p-accent-soft)]">
          Nomor polisi
        </span>
      </div>

      <div className="flex items-center justify-between gap-2 rounded-[calc(16px*var(--p-r-scale))] bg-(--p-paper-2) px-4 py-3 text-sm">
        <span>Formulir pendaftaran</span>
        <span className="font-medium text-(--p-accent-ink)">Unduh</span>
      </div>

      <div className="grid gap-3 rounded-[calc(24px*var(--p-r-scale))] bg-(--p-night) p-4 text-white">
        <div className="flex items-end justify-between gap-3">
          <div className="grid gap-1">
            <p className="text-xs text-(--p-night-muted)">Transaksi bulan ini</p>
            <p className="font-(family-name:--p-font-display) text-2xl font-(--p-fw-display) text-(--p-night-accent) tabular-nums">
              7.638
            </p>
          </div>
          <div aria-hidden="true" className="flex h-12 items-end gap-1">
            {BATANG.map((tinggi, i) => (
              <span
                key={i}
                style={{ height: `${tinggi}%` }}
                className={
                  i === 4
                    ? "w-2.5 rounded-t-[calc(4px*var(--p-r-scale))] bg-(--p-night-accent-2)"
                    : i === BATANG.length - 1
                      ? "w-2.5 rounded-t-[calc(4px*var(--p-r-scale))] bg-(--p-night-accent)"
                      : "w-2.5 rounded-t-[calc(4px*var(--p-r-scale))] bg-(--p-night-faint)"
                }
              />
            ))}
          </div>
        </div>
        <p className="rounded-[calc(12px*var(--p-r-scale))] bg-(--p-night-2) px-3 py-2 text-xs text-(--p-night-muted)">
          Panel gelap: papan layanan, dasbor beranda, layar antrean.
        </p>
      </div>
    </div>
  )
}
