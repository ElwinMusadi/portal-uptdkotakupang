/*
 * Angka halaman Ikhtisar (D-099, D-100) dari GET /api/ikhtisar, tanpa cookie
 * dan tanpa sesi; server menjawab selama Administrator mengaktifkan halaman.
 * Tanpa periode, server memberi bulan tanggal STS terakhir sampai tanggal
 * itu. Periode yang dipilih ikut tercatat di alamat halaman; saringan tidak.
 */
import { keepPreviousData, useQuery } from "@tanstack/react-query"

import type { IsiPemetaan } from "@/components/pemetaan/ringkasan-wilayah"
import type { LaporanFormI } from "@/components/penerimaan/form-i"
import type { IntiLaporan } from "@/components/penerimaan/ringkasan-laporan"
import type { PilihanSaringan } from "@/components/penerimaan/saringan-laporan"
import type { KolomWilayah, RingkasanPemetaan } from "@/lib/pemetaan"
import { DIMENSI, type LaporanPenerimaan, type LoketPenerimaan, type Saringan } from "@/lib/penerimaan"

import { galatPeriode, type Mode, type Periode } from "../portal/penerimaan/periode"

/** Periode dan saringan yang diminta; tanpa periode, bulan tanggal STS terakhir */
export interface Permintaan {
  periode: Periode | null
  pilih: PilihanSaringan
  tunggakan: Saringan["tunggakan"]
}

export interface Ikhtisar {
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
  /** Penerimaan periode itu dengan saringan, dan nilai setiap saringan dalam periode itu */
  penerimaan: Omit<IntiLaporan, "dari" | "sampai"> & Pick<LaporanPenerimaan, "pilihan">
  /** Form I gabungan bulan tanggal akhir periode */
  realisasi: LaporanFormI
  /** Form I.I: kendaraan ber-UPT asal Kota Kupang, bulan yang sama (D-101) */
  realisasiKotaKupang: LaporanFormI
  pemetaan: (IsiPemetaan & Pick<RingkasanPemetaan, "bulan"> & { tanggal: string | null; selesai: string | null; kolom: KolomWilayah[] }) | null
  /** Kepatuhan Data Potensi berdampingan dengan transaksi bayar PKB di loket sejak tanggalnya; null tanpa Data Potensi */
  pembayaran: {
    dataPotensi: { tanggal: string | null }
    /** Jendela transaksi STS: tanggal Data Potensi sampai STS terakhir; null bila belum ada STS sejak tanggal itu */
    sts: { dari: string; sampai: string } | null
    jumlah: AngkaPembayaran
    kecamatan: (AngkaPembayaran & { kunci: string; nama: string; kelurahan: (AngkaPembayaran & { nama: string })[] })[]
    /** Kendaraan Data Potensi dan transaksi STS yang kelurahannya di luar daftar resmi */
    lainnya: AngkaPembayaran
    /** Transaksi kendaraan baru (jenis daftar BARU), tidak dihitung per kelurahan */
    baru: number
    jenisDaftar: { nilai: string; transaksi: number }[]
  } | null
}

/** Angka satu wilayah di bagian Pembayaran PKB */
export interface AngkaPembayaran {
  kendaraan: number
  taat: number
  /** Jatuh tempo dan terlambat kurang dari setahun */
  belumBayar: number
  menunggak: number
  /** Transaksi STS yang membayar PKB atau tunggakan */
  bayar: number
}

/** Galat dengan status HTTP-nya; 0 bila server tidak dapat dihubungi */
export class GalatIkhtisar extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.status = status
  }
}

const MODE: Mode[] = ["harian", "bulanan", "rentang"]

/** Periode dari alamat halaman (?mode=…&dari=…&sampai=…); null bila tidak ada atau tidak sah */
export function periodeDariAlamat(): Periode | null {
  const p = new URLSearchParams(window.location.search)
  const mode = p.get("mode") as Mode | null
  const periode = { mode: mode ?? "rentang", dari: p.get("dari") ?? "", sampai: p.get("sampai") ?? "" }
  return mode && MODE.includes(mode) && !galatPeriode(periode) ? periode : null
}

/** Catat periode di alamat tanpa menambah riwayat */
export function catatPeriode(periode: Periode): void {
  const p = new URLSearchParams({ mode: periode.mode, dari: periode.dari, sampai: periode.sampai })
  history.replaceState(history.state, "", `${window.location.pathname}?${p}`)
}

function kueri({ periode, pilih, tunggakan }: Permintaan): string {
  const p = new URLSearchParams(periode ? { dari: periode.dari, sampai: periode.sampai } : {})
  for (const d of DIMENSI) for (const nilai of pilih[d] ?? []) p.append(`${d}[]`, nilai)
  if (tunggakan) p.set("tunggakan", tunggakan)
  const teks = p.toString()
  return teks ? `?${teks}` : ""
}

async function muat(permintaan: Permintaan, signal: AbortSignal): Promise<Ikhtisar> {
  let res: Response
  try {
    res = await fetch(`/api/ikhtisar${kueri(permintaan)}`, {
      headers: { Accept: "application/json" },
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
export function useIkhtisar(permintaan: Permintaan) {
  return useQuery({
    queryKey: ["ikhtisar", permintaan],
    queryFn: ({ signal }) => muat(permintaan, signal),
    placeholderData: keepPreviousData,
    refetchInterval: 5 * 60_000,
    refetchOnWindowFocus: true,
    retry: (n, e) => !(e instanceof GalatIkhtisar && [403, 422, 429].includes(e.status)) && n < 2,
  })
}
