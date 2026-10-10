import { clear, createStore, get, set, setMany, type UseStore } from "idb-keyval"

import { newId } from "@/lib/id"
import { KOLEKSI_LABEL, PENGATURAN_LABEL, judulEntri, kerjaStatus } from "@/lib/meta"

import { ApiError, type ApiClient } from "./client"
import { VERSI_DATA, buatSeed, seedWaktuPengaturan } from "./seed"
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

const NAMA_KOLEKSI = Object.keys(KOLEKSI_LABEL) as NamaKoleksi[]
const NAMA_PENGATURAN = Object.keys(PENGATURAN_LABEL) as NamaPengaturan[]
const BATAS_AKTIVITAS = 300

interface Meta {
  versi: number
  dibuat: IsoDateTime
  pengaturanDiperbarui: Record<NamaPengaturan, IsoDateTime>
}

/** Penyimpanan kunci–nilai: IndexedDB, atau memori bila IndexedDB tidak tersedia */
interface Wadah {
  get<T>(kunci: string): Promise<T | undefined>
  set(kunci: string, nilai: unknown): Promise<void>
  setMany(isi: [string, unknown][]): Promise<void>
  clear(): Promise<void>
}

class WadahIdb implements Wadah {
  private readonly store: UseStore
  constructor(store: UseStore) {
    this.store = store
  }
  get<T>(kunci: string) {
    return get<T>(kunci, this.store)
  }
  set(kunci: string, nilai: unknown) {
    return set(kunci, nilai, this.store)
  }
  setMany(isi: [string, unknown][]) {
    return setMany(isi, this.store)
  }
  clear() {
    return clear(this.store)
  }
}

class WadahMemori implements Wadah {
  private readonly map = new Map<string, unknown>()
  async get<T>(kunci: string) {
    return structuredClone(this.map.get(kunci)) as T | undefined
  }
  async set(kunci: string, nilai: unknown) {
    this.map.set(kunci, structuredClone(nilai))
  }
  async setMany(isi: [string, unknown][]) {
    for (const [k, v] of isi) this.map.set(k, structuredClone(v))
  }
  async clear() {
    this.map.clear()
  }
}

async function bukaWadah(): Promise<{ wadah: Wadah; sementara: boolean }> {
  try {
    if (typeof indexedDB === "undefined") throw new Error("IndexedDB tidak tersedia")
    const store = createStore("pintu-dashboard", "data")
    // uji tulis: sebagian peramban menolak IndexedDB di mode privat
    await set("__uji", Date.now(), store)
    return { wadah: new WadahIdb(store), sementara: false }
  } catch {
    return { wadah: new WadahMemori(), sementara: true }
  }
}

const kKoleksi = (k: NamaKoleksi) => `koleksi:${k}`
const kPengaturan = (n: NamaPengaturan) => `pengaturan:${n}`

function bacaSebagaiDataUrl(berkas: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result))
    reader.onerror = () => reject(reader.error ?? new Error("Berkas tidak dapat dibaca"))
    reader.readAsDataURL(berkas)
  })
}

export class LocalApi implements ApiClient {
  readonly mode = "lokal" as const
  private _sementara = false
  private readonly siap: Promise<Wadah>
  private antrean: Promise<unknown> = Promise.resolve()

  constructor() {
    this.siap = this.init()
  }

  get sementara() {
    return this._sementara
  }

  private async init(): Promise<Wadah> {
    const { wadah, sementara } = await bukaWadah()
    this._sementara = sementara
    const meta = await wadah.get<Meta>("meta")
    if (!meta) {
      await this.tulisSnapshot(wadah, buatSeed(), seedWaktuPengaturan())
    }
    // Tempat migrasi bila VERSI_DATA naik di masa depan.
    return wadah
  }

  /** Jalankan operasi berurutan agar dua perubahan cepat tidak saling menimpa */
  private kunci<T>(fn: (w: Wadah) => Promise<T>): Promise<T> {
    const hasil = this.antrean.then(() => this.siap).then(fn)
    this.antrean = hasil.catch(() => undefined)
    return hasil
  }

  private async tulisSnapshot(
    wadah: Wadah,
    snap: Snapshot,
    waktuPengaturan: Record<NamaPengaturan, IsoDateTime>
  ) {
    await wadah.clear()
    const isi: [string, unknown][] = []
    for (const k of NAMA_KOLEKSI) isi.push([kKoleksi(k), snap.koleksi[k] ?? []])
    for (const n of NAMA_PENGATURAN) isi.push([kPengaturan(n), snap.pengaturan[n]])
    isi.push(["aktivitas", snap.aktivitas ?? []])
    const meta: Meta = {
      versi: VERSI_DATA,
      dibuat: new Date().toISOString(),
      pengaturanDiperbarui: waktuPengaturan,
    }
    isi.push(["meta", meta])
    await wadah.setMany(isi)
  }

  private async daftar<K extends NamaKoleksi>(w: Wadah, k: K): Promise<Koleksi[K][]> {
    const data = (await w.get<Koleksi[K][]>(kKoleksi(k))) ?? []
    return data.slice().sort((a, b) => a.urutan - b.urutan)
  }

  private async pelaku(w: Wadah): Promise<string> {
    const akun = await w.get<Pengaturan["akun"]>(kPengaturan("akun"))
    return akun?.nama || "Pengelola PINTU"
  }

  private async catat(w: Wadah, aksi: string, modul: string, target: string) {
    const log = (await w.get<Aktivitas[]>("aktivitas")) ?? []
    const entri: Aktivitas = {
      id: newId(),
      waktu: new Date().toISOString(),
      oleh: await this.pelaku(w),
      aksi,
      modul,
      target,
    }
    await w.set("aktivitas", [entri, ...log].slice(0, BATAS_AKTIVITAS))
  }

  list<K extends NamaKoleksi>(k: K): Promise<Koleksi[K][]> {
    return this.kunci((w) => this.daftar(w, k))
  }

  get<K extends NamaKoleksi>(k: K, id: Id): Promise<Koleksi[K] | null> {
    return this.kunci(async (w) => (await this.daftar(w, k)).find((x) => x.id === id) ?? null)
  }

  create<K extends NamaKoleksi>(k: K, data: Draf<Koleksi[K]>): Promise<Koleksi[K]> {
    return this.kunci(async (w) => {
      const semua = await this.daftar(w, k)
      const kini = new Date().toISOString()
      const urutan = data.urutan ?? semua.reduce((m, x) => Math.max(m, x.urutan), -1) + 1
      const baru = { ...data, id: newId(), dibuat: kini, diperbarui: kini, urutan } as Koleksi[K]
      await w.set(kKoleksi(k), [...semua, baru])
      await this.catat(w, "menambahkan", KOLEKSI_LABEL[k], judulEntri(k, baru))
      return baru
    })
  }

  update<K extends NamaKoleksi>(k: K, id: Id, patch: Partial<Koleksi[K]>): Promise<Koleksi[K]> {
    return this.kunci(async (w) => {
      const semua = await this.daftar(w, k)
      const i = semua.findIndex((x) => x.id === id)
      if (i < 0) throw new ApiError("Data tidak ditemukan. Mungkin sudah dihapus.", 404)
      const lama = semua[i]
      const baru = { ...lama, ...patch, id, diperbarui: new Date().toISOString() } as Koleksi[K]
      semua[i] = baru
      await w.set(kKoleksi(k), semua)
      const statusLama = (lama as { status?: string }).status
      const statusBaru = (patch as { status?: string }).status
      const aksi =
        statusBaru && statusBaru !== statusLama && k !== "pesan"
          ? kerjaStatus(statusBaru as never)
          : "memperbarui"
      await this.catat(w, aksi, KOLEKSI_LABEL[k], judulEntri(k, baru))
      return baru
    })
  }

  remove<K extends NamaKoleksi>(k: K, ids: Id[]): Promise<void> {
    return this.kunci(async (w) => {
      const semua = await this.daftar(w, k)
      const hapus = semua.filter((x) => ids.includes(x.id))
      await w.set(
        kKoleksi(k),
        semua.filter((x) => !ids.includes(x.id))
      )
      const target = hapus.length === 1 ? judulEntri(k, hapus[0]) : `${hapus.length} entri`
      if (hapus.length) await this.catat(w, "menghapus", KOLEKSI_LABEL[k], target)
    })
  }

  reorder<K extends NamaKoleksi>(k: K, ids: Id[]): Promise<void> {
    return this.kunci(async (w) => {
      const semua = await this.daftar(w, k)
      const posisi = new Map(ids.map((id, i) => [id, i]))
      const lain = semua.filter((x) => !posisi.has(x.id))
      const urut = semua
        .filter((x) => posisi.has(x.id))
        .sort((a, b) => posisi.get(a.id)! - posisi.get(b.id)!)
      await w.set(
        kKoleksi(k),
        [...urut, ...lain].map((x, i) => ({ ...x, urutan: i }))
      )
      await this.catat(w, "mengubah urutan", KOLEKSI_LABEL[k], `${urut.length} entri`)
    })
  }

  getSetting<N extends NamaPengaturan>(n: N): Promise<Pengaturan[N]> {
    return this.kunci(async (w) => {
      const nilai = await w.get<Pengaturan[N]>(kPengaturan(n))
      return nilai ?? buatSeed().pengaturan[n]
    })
  }

  saveSetting<N extends NamaPengaturan>(n: N, nilai: Pengaturan[N]): Promise<Pengaturan[N]> {
    return this.kunci(async (w) => {
      await w.set(kPengaturan(n), nilai)
      const meta = await w.get<Meta>("meta")
      if (meta) {
        meta.pengaturanDiperbarui[n] = new Date().toISOString()
        await w.set("meta", meta)
      }
      await this.catat(w, "memperbarui", PENGATURAN_LABEL[n], PENGATURAN_LABEL[n])
      return nilai
    })
  }

  settingsUpdatedAt(): Promise<Record<NamaPengaturan, IsoDateTime>> {
    return this.kunci(async (w) => {
      const meta = await w.get<Meta>("meta")
      return meta?.pengaturanDiperbarui ?? seedWaktuPengaturan()
    })
  }

  activity(limit = 50): Promise<Aktivitas[]> {
    return this.kunci(async (w) => ((await w.get<Aktivitas[]>("aktivitas")) ?? []).slice(0, limit))
  }

  clearActivity(): Promise<void> {
    return this.kunci((w) => w.set("aktivitas", []))
  }

  async upload(berkas: File | Blob, nama: string): Promise<Berkas> {
    const url = await bacaSebagaiDataUrl(berkas)
    return { nama, ukuran: berkas.size, tipe: berkas.type || "application/octet-stream", url }
  }

  exportAll(): Promise<Snapshot> {
    return this.kunci(async (w) => {
      const koleksi = {} as Snapshot["koleksi"]
      for (const k of NAMA_KOLEKSI) {
        ;(koleksi as Record<string, unknown>)[k] = await this.daftar(w, k)
      }
      const pengaturan = {} as Pengaturan
      for (const n of NAMA_PENGATURAN) {
        ;(pengaturan as unknown as Record<string, unknown>)[n] = await w.get(kPengaturan(n))
      }
      return {
        versi: VERSI_DATA,
        diekspor: new Date().toISOString(),
        koleksi,
        pengaturan,
        aktivitas: (await w.get<Aktivitas[]>("aktivitas")) ?? [],
      }
    })
  }

  importAll(snap: Snapshot): Promise<void> {
    return this.kunci(async (w) => {
      periksaSnapshot(snap)
      const kini = new Date().toISOString()
      const waktu = Object.fromEntries(NAMA_PENGATURAN.map((n) => [n, kini])) as Record<
        NamaPengaturan,
        IsoDateTime
      >
      await this.tulisSnapshot(w, snap, waktu)
      await this.catat(w, "mengimpor data", "Pengaturan Situs", "Cadangan JSON")
    })
  }

  reset(): Promise<void> {
    return this.kunci(async (w) => {
      await this.tulisSnapshot(w, buatSeed(), seedWaktuPengaturan())
    })
  }
}

/** Validasi ringan berkas cadangan sebelum menimpa data */
export function periksaSnapshot(snap: unknown): asserts snap is Snapshot {
  const s = snap as Partial<Snapshot> | null
  if (!s || typeof s !== "object" || typeof s.versi !== "number" || !s.koleksi || !s.pengaturan) {
    throw new ApiError("Berkas bukan cadangan data PINTU yang valid.")
  }
  if (s.versi > VERSI_DATA) {
    throw new ApiError("Cadangan dibuat oleh versi dashboard yang lebih baru.")
  }
  for (const k of NAMA_KOLEKSI) {
    if (!Array.isArray((s.koleksi as Record<string, unknown>)[k])) {
      throw new ApiError(`Cadangan tidak lengkap: koleksi "${k}" tidak ditemukan.`)
    }
  }
  for (const n of NAMA_PENGATURAN) {
    if (typeof (s.pengaturan as unknown as Record<string, unknown>)[n] !== "object") {
      throw new ApiError(`Cadangan tidak lengkap: pengaturan "${n}" tidak ditemukan.`)
    }
  }
}
