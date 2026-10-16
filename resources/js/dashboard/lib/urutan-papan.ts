/*
 * Urutan layanan luar kantor di papan Beranda, per tanggal (D-053).
 *
 * Entri yang diurutkan tampil lebih dulu di papan, sesuai urutan ini; entri
 * lain pada tanggal itu menyusul urut jam. Halaman Jadwal portal tetap urut
 * jam. Data ini selalu dibaca dari server PINTU; kontraknya ada di
 * docs/api.md.
 */
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query"

import type { Hari, IsoDate, JadwalKeliling, JenisLayananLuar } from "@/lib/api"
import { mintaServer } from "@/lib/api/http"
import { namaLayananLuar } from "@/lib/meta"
import { qk } from "@/lib/queries"

export interface UrutanPapan {
  tanggal: IsoDate
  /** ID entri dari atas; entri yang tidak tercantum menyusul urut jam */
  urutan: string[]
}

const KUNCI = ["keliling", "urutan-papan"] as const
const URL_URUTAN = "/api/koleksi/keliling/urutan-papan"
/** Urutan jenis pada jam yang sama, seperti API publik */
const URUT_JENIS: Record<JenisLayananLuar, number> = { keliling: 0, sementara: 1, gerai: 2 }

/** Urutan papan mulai hari ini (WITA) */
export function useUrutanPapan(aktif = true) {
  return useQuery({
    queryKey: KUNCI,
    queryFn: () => mintaServer<UrutanPapan[]>(URL_URUTAN, "GET"),
    enabled: aktif,
    refetchOnWindowFocus: true,
  })
}

/** Simpan urutan satu tanggal; daftar kosong mengembalikannya ke urut jam */
export function useSimpanUrutanPapan() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ tanggal, ids }: { tanggal: IsoDate; ids: string[] }) =>
      mintaServer<UrutanPapan>(`${URL_URUTAN}/${tanggal}`, "PUT", { ids }),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: KUNCI })
      void qc.invalidateQueries({ queryKey: qk.aktivitas })
    },
  })
}

function hariDari(iso: IsoDate): Hari {
  const [y, m, d] = iso.split("-").map(Number)
  return new Date(Date.UTC(y, m - 1, d)).getUTCDay() as Hari
}

/** Apakah entri dilayani pada tanggal itu, seperti MobileServiceStop::occursOn */
export function dilayaniPada(j: JadwalKeliling, iso: IsoDate): boolean {
  return (
    (j.tanggalMulai === null || j.tanggalMulai <= iso) &&
    (j.tanggalSelesai === null || j.tanggalSelesai >= iso) &&
    j.hari.includes(hariDari(iso))
  )
}

const banding = (a: string, b: string) => (a < b ? -1 : a > b ? 1 : 0)

/** Urut jam seperti API publik: jam mulai, jam selesai, jenis, nama, lalu lokasi */
export function urutJam(a: JadwalKeliling, b: JadwalKeliling): number {
  return (
    banding(a.mulai, b.mulai) ||
    banding(a.selesai, b.selesai) ||
    URUT_JENIS[a.jenis] - URUT_JENIS[b.jenis] ||
    banding(namaLayananLuar(a), namaLayananLuar(b)) ||
    banding(a.lokasi, b.lokasi)
  )
}

/** Semua entri pada tanggal itu, aktif maupun belum, dalam urutan papannya */
export function susunPapan(data: JadwalKeliling[], iso: IsoDate, urutan: string[] = []): JadwalKeliling[] {
  const posisi = new Map(urutan.map((id, i) => [id, i]))
  const akhir = urutan.length
  return data
    .filter((j) => dilayaniPada(j, iso))
    .sort((a, b) => (posisi.get(a.id) ?? akhir) - (posisi.get(b.id) ?? akhir) || urutJam(a, b))
}
