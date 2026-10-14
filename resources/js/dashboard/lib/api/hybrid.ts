import { ApiError, type ApiClient } from "./client"
import type {
  Aktivitas,
  Berkas,
  Draf,
  Id,
  IsoDateTime,
  Koleksi,
  NamaKoleksi,
  NamaPengaturan,
  Pengaturan,
  Snapshot,
  TujuanUnggah,
} from "./types"

/**
 * Koleksi dan pengaturan yang sudah tersimpan di server PINTU (D-011, D-013,
 * D-014, D-015, D-016, D-017, D-018, D-019, D-020, D-021, D-022, D-023, D-024, D-025, D-026, D-027, D-030, D-031). Yang lain
 * menyusul satu per satu; sampai saat itu tetap di IndexedDB peramban. Pengaturan "akun" adalah profil akun yang
 * sedang masuk.
 */
export const KOLEKSI_SERVER: readonly NamaKoleksi[] = [
  "faq",
  "keliling",
  "penyesuaian",
  "pesan",
  "layanan",
  "halaman",
  "pemutakhiran",
  "dokumen",
  "berita",
]
export const PENGATURAN_SERVER: readonly NamaPengaturan[] = [
  "jam",
  "papan",
  "tarif",
  "statistik",
  "kontak",
  "visiMisi",
  "profil",
  "beranda",
  "situs",
  "akun",
]

/** Unggahan yang sudah disimpan di server (D-028): tujuan yang modulnya sudah di server */
export const UNGGAHAN_SERVER: readonly TujuanUnggah[] = ["visiMisi", "profil", "dokumen", "berita"]

/**
 * Mode campuran selama konten dipindahkan ke server secara bertahap. Koleksi
 * di KOLEKSI_SERVER dan pengaturan di PENGATURAN_SERVER dibaca dan ditulis
 * lewat REST API; yang lain dan unggahan tetap lokal. Log aktivitas dan waktu
 * simpan pengaturan menggabungkan keduanya.
 */
export class HybridApi implements ApiClient {
  readonly mode = "campuran" as const
  private readonly server: ApiClient
  private readonly lokal: ApiClient

  constructor(server: ApiClient, lokal: ApiClient) {
    this.server = server
    this.lokal = lokal
  }

  get sementara() {
    return this.lokal.sementara
  }

  private sumber(k: NamaKoleksi): ApiClient {
    return KOLEKSI_SERVER.includes(k) ? this.server : this.lokal
  }

  list<K extends NamaKoleksi>(k: K) {
    return this.sumber(k).list(k)
  }

  get<K extends NamaKoleksi>(k: K, id: Id) {
    return this.sumber(k).get(k, id)
  }

  create<K extends NamaKoleksi>(k: K, data: Draf<Koleksi[K]>) {
    return this.sumber(k).create(k, data)
  }

  update<K extends NamaKoleksi>(k: K, id: Id, patch: Partial<Koleksi[K]>) {
    return this.sumber(k).update(k, id, patch)
  }

  remove<K extends NamaKoleksi>(k: K, ids: Id[]) {
    return this.sumber(k).remove(k, ids)
  }

  reorder<K extends NamaKoleksi>(k: K, ids: Id[]) {
    return this.sumber(k).reorder(k, ids)
  }

  private sumberPengaturan(n: NamaPengaturan): ApiClient {
    return PENGATURAN_SERVER.includes(n) ? this.server : this.lokal
  }

  getSetting<N extends NamaPengaturan>(n: N) {
    return this.sumberPengaturan(n).getSetting(n)
  }

  saveSetting<N extends NamaPengaturan>(n: N, nilai: Pengaturan[N]) {
    return this.sumberPengaturan(n).saveSetting(n, nilai)
  }

  /** Waktu simpan pengaturan server dari server (tidak ada bila belum pernah disimpan), sisanya dari peramban */
  async settingsUpdatedAt(): Promise<Record<NamaPengaturan, IsoDateTime>> {
    const [lokal, server] = await Promise.all([this.lokal.settingsUpdatedAt(), this.server.settingsUpdatedAt()])
    const gabungan: Partial<Record<NamaPengaturan, IsoDateTime>> = { ...lokal }
    for (const n of PENGATURAN_SERVER) delete gabungan[n]
    return { ...gabungan, ...server }
  }

  /** Aktivitas di server (sebatas modul pengguna) dan di peramban ini, terbaru lebih dulu */
  async activity(limit = 50): Promise<Aktivitas[]> {
    const [server, lokal] = await Promise.all([this.server.activity(limit), this.lokal.activity(limit)])
    return [...server, ...lokal].sort((a, b) => Date.parse(b.waktu) - Date.parse(a.waktu)).slice(0, limit)
  }

  /** Hanya log di peramban ini; log aktivitas di server tetap tersimpan */
  clearActivity() {
    return this.lokal.clearActivity()
  }

  upload(berkas: File | Blob, nama: string, tujuan: TujuanUnggah): Promise<Berkas> {
    return (UNGGAHAN_SERVER.includes(tujuan) ? this.server : this.lokal).upload(berkas, nama, tujuan)
  }

  /** Cadangan lengkap: data di peramban ini, dengan koleksi dan pengaturan server diambil dari server */
  async exportAll(): Promise<Snapshot> {
    const snap = await this.lokal.exportAll()
    for (const k of KOLEKSI_SERVER) {
      ;(snap.koleksi as Record<string, unknown>)[k] = await this.daftarUntukCadangan(k)
    }
    for (const n of PENGATURAN_SERVER) {
      try {
        ;(snap.pengaturan as unknown as Record<string, unknown>)[n] = await this.server.getSetting(n)
      } catch (e) {
        // Modul yang tidak ditugaskan: cadangan memakai salinan di peramban ini
        if (!(e instanceof ApiError && e.status === 403)) throw e
      }
    }
    return snap
  }

  /** Koleksi server untuk cadangan; kosong bila modulnya tidak ditugaskan kepada pengguna (403) */
  private async daftarUntukCadangan(k: NamaKoleksi): Promise<unknown[]> {
    try {
      return await this.server.list(k)
    } catch (e) {
      if (e instanceof ApiError && e.status === 403) return []
      throw e
    }
  }

  /** Hanya data di peramban ini; koleksi dan pengaturan server tidak ikut dipulihkan */
  importAll(snap: Snapshot) {
    return this.lokal.importAll(snap)
  }

  /** Hanya data di peramban ini; koleksi dan pengaturan server tidak ikut dikembalikan */
  reset() {
    return this.lokal.reset()
  }
}
