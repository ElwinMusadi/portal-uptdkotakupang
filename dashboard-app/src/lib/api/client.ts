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
 * Kontrak penyimpanan data dashboard.
 *
 * Ada dua implementasi:
 * - LocalApi  → menyimpan di IndexedDB peramban (bawaan, tanpa server)
 * - HttpApi   → memanggil REST API bila VITE_API_URL diisi saat build
 *
 * Halaman dashboard hanya mengenal antarmuka ini, sehingga mengganti sumber
 * data tidak perlu mengubah komponen.
 */
export interface ApiClient {
  readonly mode: "lokal" | "server"
  /** true bila data hanya tersimpan di memori (IndexedDB tidak tersedia) */
  readonly sementara: boolean

  list<K extends NamaKoleksi>(koleksi: K): Promise<Koleksi[K][]>
  get<K extends NamaKoleksi>(koleksi: K, id: Id): Promise<Koleksi[K] | null>
  create<K extends NamaKoleksi>(koleksi: K, data: Draf<Koleksi[K]>): Promise<Koleksi[K]>
  update<K extends NamaKoleksi>(koleksi: K, id: Id, patch: Partial<Koleksi[K]>): Promise<Koleksi[K]>
  remove<K extends NamaKoleksi>(koleksi: K, ids: Id[]): Promise<void>
  reorder<K extends NamaKoleksi>(koleksi: K, ids: Id[]): Promise<void>

  getSetting<K extends NamaPengaturan>(nama: K): Promise<Pengaturan[K]>
  saveSetting<K extends NamaPengaturan>(nama: K, nilai: Pengaturan[K]): Promise<Pengaturan[K]>
  /** Waktu terakhir tiap halaman pengaturan disimpan */
  settingsUpdatedAt(): Promise<Record<NamaPengaturan, IsoDateTime>>

  activity(limit?: number): Promise<Aktivitas[]>
  clearActivity(): Promise<void>
  upload(file: File | Blob, nama: string): Promise<Berkas>

  exportAll(): Promise<Snapshot>
  importAll(snapshot: Snapshot): Promise<void>
  /** Kembalikan semua data ke isi awal portal */
  reset(): Promise<void>
}

export class ApiError extends Error {
  readonly status: number
  constructor(message: string, status = 0) {
    super(message)
    this.name = "ApiError"
    this.status = status
  }
}
