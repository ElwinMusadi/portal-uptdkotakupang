import { z } from "zod"

/** Teks wajib diisi */
export const wajib = (label = "Bagian ini") =>
  z.string({ error: `${label} wajib diisi.` }).trim().min(1, `${label} wajib diisi.`)

/** Teks bebas (boleh kosong) */
export const teks = z.string()

/** Alamat tautan: kosong, URL lengkap, mailto/tel, atau halaman portal (mis. kontak.html#masukan) */
export const tautan = z
  .string()
  .trim()
  .refine(
    (v) => v === "" || /^(https?:\/\/|mailto:|tel:|\.{1,2}\/|#|[\w-]+\.html)/i.test(v),
    "Gunakan alamat lengkap (https://…) atau nama halaman portal (mis. jadwal.html)."
  )

export const email = z
  .string()
  .trim()
  .refine((v) => v === "" || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), "Format email belum benar.")

export const angka = (opsi: { min?: number; maks?: number; bulat?: boolean } = {}) => {
  let s = z.number({ error: "Isi dengan angka." })
  if (opsi.bulat) s = s.int("Gunakan bilangan bulat.")
  if (opsi.min !== undefined) s = s.min(opsi.min, `Minimal ${opsi.min}.`)
  if (opsi.maks !== undefined) s = s.max(opsi.maks, `Maksimal ${opsi.maks}.`)
  return s
}

export const jam = z
  .string()
  .refine((v) => v === "" || /^([01]\d|2[0-3]):[0-5]\d$/.test(v), "Format jam HH.MM.")

export const tanggalIso = z
  .string({ error: "Pilih tanggal." })
  .regex(/^\d{4}-\d{2}-\d{2}$/, "Pilih tanggal.")

export const slug = z
  .string()
  .trim()
  .min(1, "Slug wajib diisi.")
  .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, "Gunakan huruf kecil, angka, dan tanda hubung.")

export const berkas = z
  .object({ nama: z.string(), ukuran: z.number(), tipe: z.string(), url: z.string() })
  .nullable()
