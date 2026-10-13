import type { ApiClient } from "./client"
import { HttpApi } from "./http"
import { HybridApi, KOLEKSI_SERVER } from "./hybrid"
import { LocalApi } from "./local"
import type { NamaKoleksi } from "./types"

export { ApiError } from "./client"
export type { ApiClient } from "./client"
export { KOLEKSI_SERVER } from "./hybrid"
export * from "./types"

const apiUrl = import.meta.env.VITE_API_URL?.trim()

/**
 * Sumber data aktif. Bawaan: mode campuran, yaitu koleksi di KOLEKSI_SERVER
 * lewat API PINTU sendiri (/api) dan sisanya IndexedDB lokal. Bila
 * VITE_API_URL diisi saat build, semua data memakai REST API tersebut.
 */
export const api: ApiClient = apiUrl ? new HttpApi(apiUrl) : new HybridApi(new HttpApi("/api"), new LocalApi())

/** Apakah koleksi ini tersimpan di server, sehingga perubahan pegawai lain ikut terlihat */
export function diServer(k: NamaKoleksi): boolean {
  return api.mode === "server" || (api.mode === "campuran" && KOLEKSI_SERVER.includes(k))
}
