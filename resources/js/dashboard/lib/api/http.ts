import { headerXsrf, SAMA_ORIGIN } from "@/lib/auth"

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
 * Permintaan JSON ke server PINTU sendiri dengan cookie sesi Laravel Sanctum
 * dan header X-XSRF-TOKEN. Galat menjadi ApiError berpesan bahasa Indonesia;
 * galat validasi (422) membawa pesan per isian, bukan ringkasan Laravel.
 */
export async function mintaServer<T>(url: string, method: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(method === "GET" ? {} : headerXsrf()),
  }
  let payload: BodyInit | undefined
  if (body instanceof FormData) {
    payload = body
  } else if (body !== undefined) {
    headers["Content-Type"] = "application/json"
    payload = JSON.stringify(body)
  }

  let res: Response
  try {
    res = await fetch(url, { ...SAMA_ORIGIN, method, headers, body: payload })
  } catch {
    throw new ApiError("Server tidak dapat dihubungi. Periksa koneksi internet Anda.")
  }
  if (!res.ok) {
    let pesan = `Permintaan gagal (${res.status}).`
    let isian: Record<string, string> = {}
    try {
      const json = (await res.json()) as { message?: string; pesan?: string; errors?: Record<string, string[]> }
      isian = Object.fromEntries(Object.entries(json.errors ?? {}).map(([nama, daftar]) => [nama, daftar[0]]))
      pesan = Object.values(isian)[0] ?? json.pesan ?? json.message ?? pesan
    } catch {
      // respons bukan JSON
    }
    if (res.status === 401) pesan = "Sesi Anda berakhir. Silakan masuk kembali."
    if (res.status === 413) pesan = "Berkas terlalu besar untuk diterima server."
    if (res.status === 419) pesan = "Halaman ini sudah terlalu lama dibuka. Muat ulang halaman lalu coba lagi."
    throw new ApiError(pesan, res.status, isian)
  }
  if (res.status === 204) return undefined as T
  return (await res.json()) as T
}

/**
 * Adapter REST. Mode campuran memakainya untuk koleksi yang sudah tersimpan
 * di server (/api); untuk semua data bila dashboard di-build dengan
 * VITE_API_URL, mis.
 *   VITE_API_URL=/api npm run build
 * Kontrak endpoint lengkap ada di docs/dashboard.md.
 */
export class HttpApi implements ApiClient {
  readonly mode = "server" as const
  readonly sementara = false
  private readonly base: string

  constructor(base: string) {
    this.base = base.replace(/\/+$/, "")
  }

  private req<T>(method: string, path: string, body?: unknown): Promise<T> {
    return mintaServer<T>(this.base + path, method, body)
  }

  list<K extends NamaKoleksi>(k: K) {
    return this.req<Koleksi[K][]>("GET", `/koleksi/${k}`)
  }

  async get<K extends NamaKoleksi>(k: K, id: Id) {
    try {
      return await this.req<Koleksi[K]>("GET", `/koleksi/${k}/${encodeURIComponent(id)}`)
    } catch (e) {
      if (e instanceof ApiError && e.status === 404) return null
      throw e
    }
  }

  create<K extends NamaKoleksi>(k: K, data: Draf<Koleksi[K]>) {
    return this.req<Koleksi[K]>("POST", `/koleksi/${k}`, data)
  }

  update<K extends NamaKoleksi>(k: K, id: Id, patch: Partial<Koleksi[K]>) {
    return this.req<Koleksi[K]>("PATCH", `/koleksi/${k}/${encodeURIComponent(id)}`, patch)
  }

  remove<K extends NamaKoleksi>(k: K, ids: Id[]) {
    return this.req<void>("POST", `/koleksi/${k}/hapus`, { ids })
  }

  reorder<K extends NamaKoleksi>(k: K, ids: Id[]) {
    return this.req<void>("PUT", `/koleksi/${k}/urutan`, { ids })
  }

  getSetting<N extends NamaPengaturan>(n: N) {
    return this.req<Pengaturan[N]>("GET", `/pengaturan/${n}`)
  }

  saveSetting<N extends NamaPengaturan>(n: N, nilai: Pengaturan[N]) {
    return this.req<Pengaturan[N]>("PUT", `/pengaturan/${n}`, nilai)
  }

  settingsUpdatedAt() {
    return this.req<Record<NamaPengaturan, IsoDateTime>>("GET", "/pengaturan")
  }

  activity(limit = 50) {
    return this.req<Aktivitas[]>("GET", `/aktivitas?limit=${limit}`)
  }

  clearActivity() {
    return this.req<void>("DELETE", "/aktivitas")
  }

  upload(berkas: File | Blob, nama: string, tujuan: TujuanUnggah) {
    const form = new FormData()
    form.append("modul", tujuan)
    form.append("berkas", berkas, nama)
    return this.req<Berkas>("POST", "/unggah", form)
  }

  exportAll() {
    return this.req<Snapshot>("GET", "/ekspor")
  }

  importAll(snap: Snapshot) {
    return this.req<void>("POST", "/impor", snap)
  }

  reset() {
    return this.req<void>("POST", "/reset")
  }
}
