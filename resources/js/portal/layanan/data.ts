/*
 * Persyaratan layanan dari GET /api/public/layanan, dipakai bersama panduan
 * layanan (D-059) dan gambar persyaratan yang bisa diunduh (D-091).
 */

export interface Layanan {
  nama: string
  slug: string
  ringkasan: string
  loket: string
  jenis: "layanan" | "tambahan"
  cekFisik: boolean
  nonTunai: boolean
  diwakilkan: boolean
  label: string[]
  persyaratan: { dokumen: string; jumlah: string }[]
  situasi: string[]
  catatan: string
  terbitPada: string | null
}

/** Keterangan dalam kurung di akhir nama berkas, mis. "Fotokopi KTP pemilik (sesuai alamat di STNK)" */
export function pisahKeterangan(dokumen: string): [string, string] {
  const m = dokumen.match(/^(.*\S)\s*\(([^()]+)\)$/)
  return m ? [m[1], m[2]] : [dokumen, ""]
}

export const bertitik = (t: string) => (/[.!?]$/.test(t) ? t : `${t}.`)

/** Layanan terbit; pesanGagal dipakai bila API gagal atau jawabannya tidak dikenal */
export async function muatLayanan(pesanGagal: string, sinyal?: AbortSignal): Promise<Layanan[]> {
  const res = await fetch("/api/public/layanan", { headers: { Accept: "application/json" }, credentials: "omit", signal: sinyal })
  if (!res.ok) throw new Error(pesanGagal)
  const data: unknown = await res.json()
  if (!Array.isArray(data)) throw new Error(pesanGagal)
  return (data as Layanan[]).map((l) => ({
    ...l,
    situasi: Array.isArray(l.situasi) ? l.situasi : [],
    label: Array.isArray(l.label) ? l.label : [],
    persyaratan: Array.isArray(l.persyaratan) ? l.persyaratan : [],
  }))
}
