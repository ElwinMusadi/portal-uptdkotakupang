/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** URL dasar REST API, mis. https://pintu.example.go.id/api. Kosong = penyimpanan lokal. */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
