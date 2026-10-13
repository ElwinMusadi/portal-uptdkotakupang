/**
 * ID acak 16 karakter. Memakai crypto.getRandomValues yang (berbeda dengan
 * crypto.randomUUID) juga tersedia di halaman http biasa, mis. server intranet.
 */
export function newId(): string {
  const bytes = new Uint8Array(8)
  crypto.getRandomValues(bytes)
  return Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("")
}

/** Nomor tiket pesan, mis. "PINTU-K3F9QX" */
export function newTiket(): string {
  const huruf = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
  const bytes = new Uint8Array(6)
  crypto.getRandomValues(bytes)
  return "PINTU-" + Array.from(bytes, (b) => huruf[b % huruf.length]).join("")
}
