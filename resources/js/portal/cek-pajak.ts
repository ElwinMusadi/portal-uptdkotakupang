/*
 * Cek biaya pajak di portal (D-041). Pengunjung memasukkan nomor polisi dan
 * 5 digit terakhir nomor rangka; POST /api/public/kendaraan memberikan data
 * kendaraan bila keduanya cocok, lalu mesin pajak kalkulator-pajak menghitung
 * rinciannya di peramban. Tanggal bayar dan fasilitas dihitung ulang tanpa
 * permintaan baru. Nomor rangka tidak disimpan di mana pun.
 *
 * Halaman portal memuat berkas ini lewat /skrip/cek-pajak, yang diteruskan
 * ke hasil build Vite (routes/web.php). Setiap kanvas [data-cek-pajak]
 * berdiri sendiri.
 */
import {
  calculateTax,
  hariIni,
  isWarnaPlat,
  jatuhTempoSesudahTahunBayar,
  jenisDariData,
  keTanggal,
  keteranganMasa,
  keteranganTarif,
  rincianPembayaran,
  rupiah,
  tanggalPanjang,
  warnaPlatDariData,
  BOBOT_MAP,
  type DataKendaraan,
} from "../pkb"

const POLA_NOPOL = /^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/
const normal = (s: string) => s.toUpperCase().replace(/[^A-Z0-9]/g, "")

/** "DH2506KA" → "DH 2506 KA" */
const nopolTampil = (nopol: string) => {
  const m = POLA_NOPOL.exec(nopol)
  return m ? [m[1], m[2], m[3]].filter(Boolean).join(" ") : nopol
}

const teksAtauNull = (v: unknown) => typeof v === "string" || v === null

/** Jawaban 200 berbentuk DataKendaraan; selain itu dianggap gagal */
function dataKendaraan(v: unknown): DataKendaraan | null {
  const d = v as Partial<DataKendaraan> | null
  return d &&
    typeof d.nopol === "string" &&
    teksAtauNull(d.nama) &&
    teksAtauNull(d.jenis) &&
    teksAtauNull(d.jatuhTempoPajak) &&
    teksAtauNull(d.jatuhTempoStnk) &&
    typeof d.njkb === "number" &&
    typeof d.njub === "number" &&
    typeof d.bobot === "number" &&
    (d.warnaPlat === null || isWarnaPlat(d.warnaPlat))
    ? (d as DataKendaraan)
    : null
}

const daftarKata = (kata: string[]) =>
  kata.length > 1 ? `${kata.slice(0, -1).join(", ")} dan ${kata[kata.length - 1]}` : (kata[0] ?? "")

function el<K extends keyof HTMLElementTagNameMap>(tag: K, kelas: string, teks?: string) {
  const e = document.createElement(tag)
  if (kelas) e.className = kelas
  if (teks !== undefined) e.textContent = teks
  return e
}

function cekPajak(root: HTMLElement) {
  const form = root.querySelector<HTMLFormElement>("[data-cek-form]")
  const hasil = root.querySelector<HTMLElement>("[data-cek-hasil]")
  const inNopol = form?.elements.namedItem("nopol")
  const inRangka = form?.elements.namedItem("rangka")
  const kirim = root.querySelector<HTMLButtonElement>("[data-cek-kirim]")
  const pesan = root.querySelector<HTMLElement>("[data-cek-pesan]")
  const inBayar = root.querySelector<HTMLInputElement>("[data-cek-bayar]")
  const gempa = root.querySelector<HTMLInputElement>("[data-cek-gempa]")
  const mutasi = root.querySelector<HTMLInputElement>("[data-cek-mutasi]")
  const rincian = root.querySelector<HTMLElement>("[data-cek-rincian]")
  if (
    !form || !hasil || !kirim || !pesan || !inBayar || !gempa || !mutasi || !rincian ||
    !(inNopol instanceof HTMLInputElement) || !(inRangka instanceof HTMLInputElement)
  ) {
    return
  }

  /** Data kendaraan yang sedang ditampilkan; null bila belum ada atau sudah usang */
  let kendaraan: DataKendaraan | null = null

  const isi = (kunci: string, teks: string) =>
    root.querySelectorAll(`[data-cek="${kunci}"]`).forEach((e) => (e.textContent = teks))

  const tulisPesan = (teks: string, galat = false) => {
    pesan.textContent = teks
    pesan.classList.toggle("is-galat", galat)
  }

  /** Hasil disembunyikan dan nama pemilik dihapus dari halaman */
  const tutupHasil = () => {
    if (!kendaraan) return
    kendaraan = null
    hasil.hidden = true
    ;["nama", "nopol", "jenis", "jt-pajak", "jt-stnk", "total", "masa"].forEach((k) => isi(k, "—"))
    isi("catatan", "")
    rincian.replaceChildren()
  }

  const hitung = () => {
    if (!kendaraan) return
    const k = kendaraan
    const jenis = jenisDariData(k.jenis)
    const tglPajak = keTanggal(k.jatuhTempoPajak)
    const tglStnk = keTanggal(k.jatuhTempoStnk)
    const tglBayar = keTanggal(inBayar.value) ?? keTanggal(hariIni())
    rincian.replaceChildren()
    isi("total", "—")
    isi("catatan", isWarnaPlat(k.warnaPlat) ? "" : "Warna plat tidak tercatat, dihitung sebagai plat putih. Plat kuning (angkutan umum) dihitung di loket.")

    const kurang = [
      k.njkb > 0 ? null : "nilai jual kendaraan",
      jenis ? null : "jenis kendaraan",
      tglPajak ? null : "jatuh tempo pajak",
      tglStnk ? null : "jatuh tempo STNK",
    ].filter((x): x is string => x !== null)
    if (kurang.length || !jenis || !tglPajak || !tglStnk || !tglBayar) {
      isi("masa", `Data ${daftarKata(kurang)} belum tersedia, jadi biayanya belum bisa dihitung di sini. Silakan cek di loket Samsat.`)
      return
    }
    if (jatuhTempoSesudahTahunBayar(tglPajak, tglBayar)) {
      isi(
        "masa",
        `PKB tahun ${tglBayar.getFullYear()} sudah dibayar; pajak berikutnya jatuh tempo ${tanggalPanjang(k.jatuhTempoPajak ?? "")}. Pilih tanggal bayar di tahun ${tglPajak.getFullYear()} untuk memperkirakan tagihannya.`
      )
      return
    }

    const h = calculateTax({
      njkb: k.njkb,
      njub: k.njub,
      bobot: k.bobot || BOBOT_MAP[jenis],
      jenisKendaraan: jenis,
      jatuhTempoPajak: tglPajak,
      jatuhTempoStnk: tglStnk,
      tanggalBayar: tglBayar,
      isDomisiliGempa: gempa.checked,
      isMutasiMasuk: mutasi.checked,
      isTembakRu: false,
      warnaPlat: warnaPlatDariData(k.warnaPlat),
    })
    isi("total", rupiah(h.total))
    isi("masa", [keteranganMasa(h), keteranganTarif(h)].filter(Boolean).join(" · "))
    for (const kelompok of rincianPembayaran(h)) {
      const judul = el("p", "cek__kelompok")
      judul.append(el("span", "", kelompok.judul), el("span", "", rupiah(kelompok.jumlah)))
      const baris = el("ul", "cek__baris")
      baris.setAttribute("role", "list")
      for (const b of kelompok.baris) {
        const li = el("li", b.dibebaskan ? "is-bebas" : "")
        li.append(el("span", "", b.label), el("span", "", rupiah(b.nilai)))
        baris.append(li)
      }
      const grup = el("div", "")
      grup.append(judul, baris)
      rincian.append(grup)
    }
  }

  const tampilkan = (k: DataKendaraan) => {
    kendaraan = k
    isi("nama", k.nama ?? "Nama pemilik tidak tersedia")
    isi("nopol", nopolTampil(k.nopol))
    isi("jenis", k.jenis ?? "—")
    isi("jt-pajak", k.jatuhTempoPajak ? tanggalPanjang(k.jatuhTempoPajak) : "—")
    isi("jt-stnk", k.jatuhTempoStnk ? tanggalPanjang(k.jatuhTempoStnk) : "—")
    inBayar.value = inBayar.min = hariIni()
    gempa.checked = false
    mutasi.checked = false
    hasil.hidden = false
    hitung()
  }

  const galat = (teks: string, isian?: HTMLInputElement) => {
    tulisPesan(teks, true)
    if (isian) {
      isian.setAttribute("aria-invalid", "true")
      isian.focus()
    }
  }

  ;[inNopol, inRangka].forEach((isian) =>
    isian.addEventListener("input", () => {
      isian.removeAttribute("aria-invalid")
      tulisPesan("")
      tutupHasil()
    })
  )
  inBayar.addEventListener("change", hitung)
  gempa.addEventListener("change", hitung)
  mutasi.addEventListener("change", hitung)

  form.addEventListener("submit", async (e) => {
    e.preventDefault()
    const nopol = normal(inNopol.value)
    const rangka = normal(inRangka.value)
    if (!POLA_NOPOL.test(nopol)) return galat("Tulis nomor polisi seperti DH 1234 AB.", inNopol)
    if (!/^[A-Z0-9]{5}$/.test(rangka)) return galat("Tulis 5 digit terakhir nomor rangka.", inRangka)

    tutupHasil()
    tulisPesan("Memeriksa data kendaraan…")
    kirim.disabled = true
    const batas = new AbortController()
    const waktu = window.setTimeout(() => batas.abort(), 30000)
    try {
      const res = await fetch("/api/public/kendaraan", {
        method: "POST",
        credentials: "omit",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({ nopol, rangka }),
        signal: batas.signal,
      })
      const data: unknown = await res.json().catch(() => null)
      const k = res.ok ? dataKendaraan(data) : null
      const jawaban = (data ?? {}) as { message?: unknown; errors?: Record<string, string[]> }
      if (k) {
        tulisPesan("Data kendaraan ditemukan.")
        tampilkan(k)
        hasil.scrollIntoView({ block: "nearest" })
      } else if (res.status === 422 && jawaban.errors) {
        const [nama, daftar] = Object.entries(jawaban.errors)[0] ?? []
        galat(daftar?.[0] ?? "Periksa isian Anda.", nama === "rangka" ? inRangka : nama === "nopol" ? inNopol : undefined)
      } else {
        galat(
          typeof jawaban.message === "string" && [404, 422, 429, 503].includes(res.status)
            ? jawaban.message
            : "Pengecekan belum berhasil. Coba lagi beberapa saat lagi."
        )
      }
    } catch {
      galat("Pengecekan belum berhasil. Periksa koneksi lalu coba lagi.")
    } finally {
      window.clearTimeout(waktu)
      kirim.disabled = false
    }
  })
}

document.querySelectorAll<HTMLElement>("[data-cek-pajak]").forEach(cekPajak)
