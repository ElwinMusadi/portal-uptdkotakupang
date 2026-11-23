/*
 * Angka halaman Ikhtisar (D-099) dari GET /api/ikhtisar, dengan token tautan
 * sebagai Bearer. Tanpa cookie dan tanpa sesi. Periode dihitung server dari
 * tanggal STS terakhir; pilihan periode ikut tercatat di alamat halaman.
 */
import { keepPreviousData, useQuery } from "@tanstack/react-query"

import type { IsiPemetaan } from "@/components/pemetaan/ringkasan-wilayah"
import type { LaporanFormI } from "@/components/penerimaan/form-i"
import type { IntiLaporan } from "@/components/penerimaan/ringkasan-laporan"
import type { KolomWilayah, RingkasanPemetaan } from "@/lib/pemetaan"
import type { LoketPenerimaan } from "@/lib/penerimaan"

export const PERIODE = {
  "hari-terakhir": "Hari terakhir",
  "bulan-ini": "Bulan ini",
  "bulan-lalu": "Bulan lalu",
  "tahun-ini": "Tahun ini",
} as const

export type Periode = keyof typeof PERIODE

export type Pilihan = { periode: Periode } | { dari: string; sampai: string }

export interface Ikhtisar {
  periode: Periode | "rentang"
  dari: string
  sampai: string
  pembaruan: {
    /** Tanggal transaksi STS terakhir dari semua loket; null bila belum ada STS */
    stsTerakhir: string | null
    tersedia: { awal: string; akhir: string } | null
    /** Tanggal STS terakhir setiap loket, terbaru dulu */
    loket: { nilai: string; nama: string; sampaiTanggal: string }[]
    /** Loket yang dijadwalkan melayani pada tanggal STS terakhir tetapi belum diimpor sampai tanggal itu */
    belumDiimpor: LoketPenerimaan[]
    dataPotensi: { tanggal: string | null; selesai: string | null } | null
  }
  penerimaan: Omit<IntiLaporan, "dari" | "sampai">
  realisasi: LaporanFormI
  pemetaan: (IsiPemetaan & Pick<RingkasanPemetaan, "bulan"> & { tanggal: string | null; selesai: string | null; kolom: KolomWilayah[] }) | null
}

/** Galat dengan status HTTP-nya; 0 bila server tidak dapat dihubungi */
export class GalatIkhtisar extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.status = status
  }
}

/** Pilihan dari alamat halaman (?periode=… atau ?dari=…&sampai=…); bawaannya bulan ini */
export function pilihanDariAlamat(): Pilihan {
  const p = new URLSearchParams(window.location.search)
  const dari = p.get("dari")
  const sampai = p.get("sampai")
  if (dari && sampai && /^\d{4}-\d{2}-\d{2}$/.test(dari) && /^\d{4}-\d{2}-\d{2}$/.test(sampai)) return { dari, sampai }
  const periode = p.get("periode")
  return { periode: periode && periode in PERIODE ? (periode as Periode) : "bulan-ini" }
}

/** Catat pilihan di alamat tanpa menambah riwayat */
export function catatPilihan(pilihan: Pilihan): void {
  const p = new URLSearchParams("periode" in pilihan ? { periode: pilihan.periode } : { dari: pilihan.dari, sampai: pilihan.sampai })
  history.replaceState(history.state, "", `${window.location.pathname}?${p}`)
}

async function muat(token: string, pilihan: Pilihan, signal: AbortSignal): Promise<Ikhtisar> {
  const kueri = new URLSearchParams("periode" in pilihan ? { periode: pilihan.periode } : { dari: pilihan.dari, sampai: pilihan.sampai })
  let res: Response
  try {
    res = await fetch(`/api/ikhtisar?${kueri}`, {
      headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
      credentials: "omit",
      cache: "no-store",
      referrerPolicy: "no-referrer",
      signal,
    })
  } catch (e) {
    if (signal.aborted) throw e
    throw new GalatIkhtisar("Server tidak dapat dihubungi. Periksa koneksi internet.", 0)
  }
  if (!res.ok) {
    let pesan = `Permintaan gagal (${res.status}).`
    try {
      const json = (await res.json()) as { message?: string; errors?: Record<string, string[]> }
      pesan = Object.values(json.errors ?? {})[0]?.[0] ?? json.message ?? pesan
    } catch {
      // jawaban bukan JSON
    }
    throw new GalatIkhtisar(pesan, res.status)
  }
  return (await res.json()) as Ikhtisar
}

/** Angka ikhtisar; dimuat ulang setiap 5 menit dan saat halaman kembali dibuka, angka lama tetap tampil selama memuat */
export function useIkhtisar(token: string | null, pilihan: Pilihan) {
  return useQuery({
    queryKey: ["ikhtisar", pilihan],
    queryFn: ({ signal }) => muat(token!, pilihan, signal),
    enabled: token !== null,
    placeholderData: keepPreviousData,
    refetchInterval: 5 * 60_000,
    refetchOnWindowFocus: true,
    retry: (n, e) => !(e instanceof GalatIkhtisar && [401, 422, 429].includes(e.status)) && n < 2,
  })
}
