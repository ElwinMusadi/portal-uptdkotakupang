/** Batas ukuran unggahan (sebelum kompresi) */
export const MAKS_GAMBAR = 15 * 1024 * 1024
export const MAKS_BERKAS = 10 * 1024 * 1024

export const TIPE_GAMBAR = ["image/jpeg", "image/png", "image/webp"]

/**
 * Perkecil foto sebelum disimpan: sisi terpanjang maks. 1600 px.
 * Foto disimpan sebagai JPEG (kualitas 82%); PNG tetap PNG agar transparansi terjaga.
 */
export async function kompresGambar(berkas: File, maksSisi = 1600): Promise<Blob> {
  if (!TIPE_GAMBAR.includes(berkas.type)) {
    throw new Error("Format gambar harus JPG, PNG, atau WebP.")
  }
  if (berkas.size > MAKS_GAMBAR) {
    throw new Error("Ukuran gambar maksimal 15 MB.")
  }
  const bitmap = await createImageBitmap(berkas)
  const skala = Math.min(1, maksSisi / Math.max(bitmap.width, bitmap.height))
  const lebar = Math.round(bitmap.width * skala)
  const tinggi = Math.round(bitmap.height * skala)
  const kanvas = document.createElement("canvas")
  kanvas.width = lebar
  kanvas.height = tinggi
  const ctx = kanvas.getContext("2d")
  if (!ctx) throw new Error("Peramban tidak dapat memproses gambar.")
  ctx.drawImage(bitmap, 0, 0, lebar, tinggi)
  bitmap.close()
  const png = berkas.type === "image/png"
  const blob = await new Promise<Blob | null>((resolve) =>
    kanvas.toBlob(resolve, png ? "image/png" : "image/jpeg", 0.82)
  )
  if (!blob) throw new Error("Gambar gagal diproses.")
  // pakai berkas asli bila hasil kompresi justru lebih besar
  return blob.size < berkas.size ? blob : berkas
}

export function namaBerkasGambar(asli: string, blob: Blob) {
  const dasar = asli.replace(/\.[^.]+$/, "")
  return `${dasar}.${blob.type === "image/png" ? "png" : blob.type === "image/webp" ? "webp" : "jpg"}`
}
