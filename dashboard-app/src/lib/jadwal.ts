import type { Hari, JamPelayanan, PenyesuaianJadwal } from "@/lib/api"
import { hariIniIso, jamTitik, ZONA_WAKTU } from "@/lib/format"
import { HARI_LABEL } from "@/lib/meta"

/** Waktu sekarang di WITA: hari (0–6) dan menit sejak tengah malam */
export function sekarangWita(d = new Date()) {
  const parts = new Intl.DateTimeFormat("en-US", {
    weekday: "short",
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
    timeZone: ZONA_WAKTU,
  }).formatToParts(d)
  const ambil = (t: string) => parts.find((p) => p.type === t)?.value ?? ""
  const hari = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(ambil("weekday")) as Hari
  return { hari, menit: Number(ambil("hour")) * 60 + Number(ambil("minute")), tanggal: hariIniIso(d) }
}

const keMenit = (jam: string) => {
  const [h, m] = jam.split(":").map(Number)
  return h * 60 + m
}

export interface StatusLayanan {
  buka: boolean
  judul: string
  keterangan: string
  khusus?: string
}

/** Status loket saat ini, memperhitungkan penyesuaian jadwal hari ini */
export function statusLayanan(
  jam: JamPelayanan,
  penyesuaian: PenyesuaianJadwal[] = [],
  d = new Date()
): StatusLayanan {
  const kini = sekarangWita(d)
  const khusus = penyesuaian.find((p) => p.tanggal === kini.tanggal)
  const harian = jam.hari.find((h) => h.hari === kini.hari)
  let mulai = harian?.buka ? harian.mulai : ""
  let selesai = harian?.buka ? harian.selesai : ""
  if (khusus?.jenis === "tutup") {
    return { buka: false, judul: "Tutup hari ini", keterangan: khusus.keterangan, khusus: khusus.keterangan }
  }
  if (khusus?.jenis === "jam-khusus") {
    mulai = khusus.mulai
    selesai = khusus.selesai
  }
  if (!mulai || !selesai) {
    return { buka: false, judul: `Tutup · ${HARI_LABEL[kini.hari]}`, keterangan: "Loket tidak melayani hari ini." }
  }
  const m = keMenit(mulai)
  const s = keMenit(selesai)
  const batas = s - (jam.batasBerkasMenit || 0)
  if (kini.menit < m) {
    return { buka: false, judul: `Belum buka · buka ${jamTitik(mulai)}`, keterangan: `Jam hari ini ${jamTitik(mulai)}–${jamTitik(selesai)} WITA.`, khusus: khusus?.keterangan }
  }
  if (kini.menit >= s) {
    return { buka: false, judul: "Sudah tutup", keterangan: `Jam hari ini ${jamTitik(mulai)}–${jamTitik(selesai)} WITA.`, khusus: khusus?.keterangan }
  }
  const sisa = batas - kini.menit
  return {
    buka: true,
    judul: `Buka · tutup ${jamTitik(selesai)} WITA`,
    keterangan:
      sisa > 0
        ? `Penerimaan berkas sampai ${String(Math.floor(batas / 60)).padStart(2, "0")}.${String(batas % 60).padStart(2, "0")}.`
        : "Penerimaan berkas sudah ditutup.",
    khusus: khusus?.keterangan,
  }
}
