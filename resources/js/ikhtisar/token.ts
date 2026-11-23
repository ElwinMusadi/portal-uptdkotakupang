/*
 * Token tautan Ikhtisar (D-099). Tautan dari Administrator berbentuk
 * /ikhtisar#k=<token>: bagian setelah # tidak pernah dikirim peramban ke
 * server. Saat dibuka, token disimpan di perangkat ini dan alamatnya
 * dibersihkan, agar tidak terlihat di layar proyektor atau tersalin bersama
 * alamat halaman.
 */
const KUNCI = "pintu-ikhtisar"
const POLA = /^#k=([A-Za-z0-9]{48})$/

/** Token dari alamat (lalu disimpan) atau yang tersimpan di perangkat ini; null bila tidak ada */
export function ambilToken(): string | null {
  const cocok = window.location.hash.match(POLA)
  if (cocok) {
    try {
      localStorage.setItem(KUNCI, cocok[1])
    } catch {
      // Penyimpanan tidak tersedia (mis. mode privat): token tetap dipakai selama halaman terbuka
    }
    history.replaceState(history.state, "", window.location.pathname + window.location.search)
    return cocok[1]
  }
  try {
    return localStorage.getItem(KUNCI)
  } catch {
    return null
  }
}

/** Hapus token dari perangkat ini, mis. di komputer ruang rapat bersama atau setelah tautan dicabut */
export function lupakanToken(): void {
  try {
    localStorage.removeItem(KUNCI)
  } catch {
    // tidak ada yang perlu dihapus
  }
}
