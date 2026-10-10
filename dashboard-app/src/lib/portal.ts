/** Alamat portal publik, relatif terhadap folder dashboard (bisa diubah di Pengaturan Situs) */
let basis = "../"

export function aturBasisPortal(url: string | undefined) {
  basis = url?.trim() || "../"
}

export function urlPortal(halaman = ""): string {
  try {
    const akar = new URL(basis.endsWith("/") ? basis : basis + "/", window.location.href)
    return new URL(halaman, akar).toString()
  } catch {
    return "../" + halaman
  }
}
