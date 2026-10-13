import type { ApiClient } from "./client"
import { HttpApi } from "./http"
import { HybridApi, KOLEKSI_SERVER, PENGATURAN_SERVER, UNGGAHAN_SERVER } from "./hybrid"
import { LocalApi } from "./local"
import type { NamaKoleksi, NamaPengaturan, TujuanUnggah } from "./types"

export { ApiError } from "./client"
export type { ApiClient } from "./client"
export { KOLEKSI_SERVER, PENGATURAN_SERVER, UNGGAHAN_SERVER } from "./hybrid"
export * from "./types"

const apiUrl = import.meta.env.VITE_API_URL?.trim()

/**
 * Sumber data aktif. Bawaan: mode campuran, yaitu koleksi di KOLEKSI_SERVER
 * dan pengaturan di PENGATURAN_SERVER lewat API PINTU sendiri (/api), sisanya
 * IndexedDB lokal. Bila VITE_API_URL diisi saat build, semua data memakai
 * REST API tersebut.
 */
export const api: ApiClient = apiUrl ? new HttpApi(apiUrl) : new HybridApi(new HttpApi("/api"), new LocalApi())

/** Apakah koleksi ini tersimpan di server, sehingga perubahan pegawai lain ikut terlihat */
export function diServer(k: NamaKoleksi): boolean {
  return api.mode === "server" || (api.mode === "campuran" && KOLEKSI_SERVER.includes(k))
}

/** Apakah pengaturan ini tersimpan di server */
export function pengaturanDiServer(n: NamaPengaturan): boolean {
  return api.mode === "server" || (api.mode === "campuran" && PENGATURAN_SERVER.includes(n))
}

/** Apakah unggahan untuk tujuan ini disimpan di server, yang hanya menerima berkas unggahan PINTU (D-028) */
export function unggahanDiServer(tujuan: TujuanUnggah): boolean {
  return api.mode === "server" || (api.mode === "campuran" && UNGGAHAN_SERVER.includes(tujuan))
}
