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
} from "./types"

/**
 * Koleksi yang sudah tersimpan di server PINTU (D-011). Koleksi lain menyusul
 * satu per satu; sampai saat itu tetap di IndexedDB peramban.
 */
export const KOLEKSI_SERVER: readonly NamaKoleksi[] = ["faq"]

/**
 * Mode campuran selama konten dipindahkan ke server secara bertahap. Koleksi
 * di KOLEKSI_SERVER dibaca dan ditulis lewat REST API; koleksi lain,
 * pengaturan, dan unggahan tetap lokal. Log aktivitas menggabungkan keduanya.
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

  getSetting<N extends NamaPengaturan>(n: N) {
    return this.lokal.getSetting(n)
  }

  saveSetting<N extends NamaPengaturan>(n: N, nilai: Pengaturan[N]) {
    return this.lokal.saveSetting(n, nilai)
  }

  settingsUpdatedAt(): Promise<Record<NamaPengaturan, IsoDateTime>> {
    return this.lokal.settingsUpdatedAt()
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

  upload(berkas: File | Blob, nama: string): Promise<Berkas> {
    return this.lokal.upload(berkas, nama)
  }

  /** Cadangan lengkap: data di peramban ini, dengan koleksi server diambil dari server */
  async exportAll(): Promise<Snapshot> {
    const snap = await this.lokal.exportAll()
    for (const k of KOLEKSI_SERVER) {
      ;(snap.koleksi as Record<string, unknown>)[k] = await this.daftarUntukCadangan(k)
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

  /** Hanya data di peramban ini; koleksi server tidak ikut dipulihkan */
  importAll(snap: Snapshot) {
    return this.lokal.importAll(snap)
  }

  /** Hanya data di peramban ini; koleksi server tidak ikut dikembalikan */
  reset() {
    return this.lokal.reset()
  }
}
