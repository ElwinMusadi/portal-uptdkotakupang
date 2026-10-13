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
 * Kontrak penyimpanan data dashboard.
 *
 * Ada tiga implementasi:
 * - LocalApi  → menyimpan di IndexedDB peramban
 * - HttpApi   → memanggil REST API server
 * - HybridApi → bawaan: koleksi yang sudah pindah ke server PINTU lewat
 *               HttpApi, sisanya LocalApi (lihat KOLEKSI_SERVER)
 *
 * Halaman dashboard hanya mengenal antarmuka ini, sehingga mengganti sumber
 * data tidak perlu mengubah komponen.
 */
export interface ApiClient {
  readonly mode: "lokal" | "campuran" | "server"
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
  upload(file: File | Blob, nama: string, tujuan: TujuanUnggah): Promise<Berkas>

  exportAll(): Promise<Snapshot>
  importAll(snapshot: Snapshot): Promise<void>
  /** Kembalikan semua data ke isi awal portal */
  reset(): Promise<void>
}

export class ApiError extends Error {
  readonly status: number
  /** Pesan per isian dari validasi server (422), mis. { nip: "NIP ini sudah terdaftar." } */
  readonly isian: Record<string, string>
  constructor(message: string, status = 0, isian: Record<string, string> = {}) {
    super(message)
    this.name = "ApiError"
    this.status = status
    this.isian = isian
  }
}
