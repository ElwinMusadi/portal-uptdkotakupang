import type { ApiClient } from "./client"
import { HttpApi } from "./http"
import { LocalApi } from "./local"

export { ApiError } from "./client"
export type { ApiClient } from "./client"
export * from "./types"

const apiUrl = import.meta.env.VITE_API_URL?.trim()

/** Sumber data aktif: REST API bila VITE_API_URL diisi, selain itu IndexedDB lokal */
export const api: ApiClient = apiUrl ? new HttpApi(apiUrl) : new LocalApi()
