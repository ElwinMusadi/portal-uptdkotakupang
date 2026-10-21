/*
 * Panduan "Layanan apa yang saya perlukan?" (D-059) di bagian teratas
 * halaman Persyaratan Layanan. Warga memilih keperluannya (situasi yang
 * diatur per layanan di Dashboard › Persyaratan Layanan, atau nama layanan
 * bila belum ada) dan, bila berlaku, lampiran tambahan seperti badan hukum.
 * Hasilnya layanan, loket, ketentuan, dan berkas yang bisa dicentang atau
 * dicetak. Data dari GET /api/public/layanan, sama dengan daftar di bawahnya.
 */
import { useEffect, useMemo, useRef, useState } from "react"
import { createRoot } from "react-dom/client"

import { gulirKe } from "../gulir"
import "./panduan-layanan.css"

interface Layanan {
  nama: string
  slug: string
  ringkasan: string
  loket: string
  jenis: "layanan" | "tambahan"
  cekFisik: boolean
  nonTunai: boolean
  diwakilkan: boolean
  label: string[]
  persyaratan: { dokumen: string; jumlah: string }[]
  situasi: string[]
  catatan: string
}

interface Pilihan {
  kunci: string
  teks: string
  layanan: Layanan
}

/** Keterangan dalam kurung di akhir nama berkas, mis. "Fotokopi KTP pemilik (sesuai alamat di STNK)" */
function pisahKeterangan(dokumen: string): [string, string] {
  const m = dokumen.match(/^(.*\S)\s*\(([^()]+)\)$/)
  return m ? [m[1], m[2]] : [dokumen, ""]
}

const bertitik = (t: string) => (/[.!?]$/.test(t) ? t : `${t}.`)

async function muatLayanan(sinyal: AbortSignal): Promise<Layanan[]> {
  const res = await fetch("/api/public/layanan", { headers: { Accept: "application/json" }, credentials: "omit", signal: sinyal })
  if (!res.ok) throw new Error("Panduan belum dapat dimuat. Pilih layanan dari daftar di bawah, atau coba lagi.")
  const data: unknown = await res.json()
  if (!Array.isArray(data)) throw new Error("Panduan belum dapat dimuat. Pilih layanan dari daftar di bawah, atau coba lagi.")
  return (data as Layanan[]).map((l) => ({ ...l, situasi: Array.isArray(l.situasi) ? l.situasi : [] }))
}

function Ikon({ nama, className = "i" }: { nama: string; className?: string }) {
  return (
    <svg className={className} aria-hidden="true">
      <use href={`#i-${nama}`} />
    </svg>
  )
}

function Berkas({ dokumen, jumlah, siap, ubah }: { dokumen: string; jumlah: string; siap: boolean; ubah: () => void }) {
  const [utama, ket] = pisahKeterangan(dokumen)
  return (
    <li>
      <label>
        <input type="checkbox" checked={siap} onChange={ubah} />
        <span>
          {utama}
          {ket && <em>{ket}</em>}
        </span>
        <b>{jumlah || "—"}</b>
      </label>
    </li>
  )
}

function PanduanLayanan() {
  const [data, setData] = useState<Layanan[] | null>(null)
  const [galat, setGalat] = useState<string | null>(null)
  const [putaran, setPutaran] = useState(0)
  const [dipilih, setDipilih] = useState<string | null>(null)
  const [tambahan, setTambahan] = useState<string[]>([])
  const [siap, setSiap] = useState<string[]>([])
  const hasil = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const kendali = new AbortController()
    muatLayanan(kendali.signal)
      .then((d) => {
        setData(d)
        setGalat(null)
      })
      .catch((e: unknown) => {
        if (!kendali.signal.aborted) setGalat((e as Error).message)
      })
    return () => kendali.abort()
  }, [putaran])

  // Satu pilihan per situasi; layanan tanpa situasi tampil dengan namanya
  const pilihan = useMemo<Pilihan[]>(
    () =>
      (data ?? [])
        .filter((l) => l.jenis === "layanan")
        .flatMap((l) => (l.situasi.length ? l.situasi : [l.nama]).map((teks, i) => ({ kunci: `${l.slug}#${i}`, teks, layanan: l }))),
    [data],
  )
  const daftarTambahan = useMemo(() => (data ?? []).filter((l) => l.jenis !== "layanan"), [data])

  if (!data) {
    return galat ? (
      <div className="pl-galat" role="alert">
        <p>{galat}</p>
        <button type="button" className="btn btn--dark btn--sm" onClick={() => setPutaran((n) => n + 1)}>
          Coba lagi
        </button>
      </div>
    ) : (
      <p className="muted" role="status">
        Memuat panduan…
      </p>
    )
  }
  if (!pilihan.length) return null

  const aktif = pilihan.find((p) => p.kunci === dipilih) ?? null
  const tambahanAktif = daftarTambahan.filter((t) => tambahan.includes(t.slug))
  const kelompok = aktif ? [aktif.layanan, ...tambahanAktif] : []
  const semuaBerkas = kelompok.flatMap((l) => l.persyaratan.map((_, i) => `${l.slug}:${i}`))
  const jumlahSiap = semuaBerkas.filter((k) => siap.includes(k)).length

  const pilih = (kunci: string) => {
    setDipilih(kunci)
    setSiap([])
    // Di layar sempit hasilnya di bawah daftar pilihan: digulir ke sana bila belum terlihat
    requestAnimationFrame(() => {
      const el = hasil.current
      if (!el || el.getBoundingClientRect().top < window.innerHeight * 0.6) return
      gulirKe(el)
    })
  }
  const alihTambahan = (slug: string) => setTambahan((t) => (t.includes(slug) ? t.filter((s) => s !== slug) : [...t, slug]))
  const alihSiap = (kunci: string) => setSiap((s) => (s.includes(kunci) ? s.filter((k) => k !== kunci) : [...s, kunci]))
  const cetak = () => {
    const akar = document.documentElement
    akar.dataset.cetak = "panduan"
    window.addEventListener("afterprint", () => delete akar.dataset.cetak, { once: true })
    window.print()
  }

  return (
    <div className="pl">
      <div className="pl-tanya">
        <fieldset className="pl-kelompok">
          <legend className="pl-judul">Apa yang ingin Anda urus?</legend>
          <div className="pl-opsi-daftar">
            {pilihan.map((p) => (
              <label key={p.kunci} className={p.kunci === dipilih ? "pl-opsi is-on" : "pl-opsi"}>
                <input type="radio" name="pl-keperluan" value={p.kunci} checked={p.kunci === dipilih} onChange={() => pilih(p.kunci)} />
                <span className="pl-opsi__teks">{p.teks}</span>
                <Ikon nama="arrow-right" className="i pl-opsi__ikon" />
              </label>
            ))}
          </div>
        </fieldset>

        {daftarTambahan.length > 0 && (
          <fieldset className="pl-kelompok">
            <legend className="pl-judul">Berlaku juga untuk Anda?</legend>
            <div className="pl-opsi-daftar">
              {daftarTambahan.map((t) => (
                <label key={t.slug} className={tambahan.includes(t.slug) ? "pl-opsi is-on" : "pl-opsi"}>
                  <input type="checkbox" checked={tambahan.includes(t.slug)} onChange={() => alihTambahan(t.slug)} />
                  <span className="pl-opsi__teks">{t.situasi[0] ?? t.nama}</span>
                </label>
              ))}
            </div>
          </fieldset>
        )}

        <p className="micro muted">
          Keperluan Anda tidak ada di daftar?{" "}
          <a className="link-ink" href="kontak.html#masukan">
            Tanyakan lewat formulir
          </a>{" "}
          atau di meja informasi kantor.
        </p>
      </div>

      <div className="pl-hasil" ref={hasil} aria-live="polite">
        {!aktif ? (
          <div className="pl-kosong">
            <Ikon nama="list-check" />
            <p>Pilih keperluan Anda untuk melihat layanan, loket, dan berkas yang perlu dibawa.</p>
          </div>
        ) : (
          <>
            <div className="pl-ringkas">
              <p className="smallcaps">{aktif.layanan.loket}</p>
              <h3 className="h3">{bertitik(aktif.layanan.nama)}</h3>
              <p className="svc__desc">{aktif.layanan.ringkasan}</p>
              <div className="svc__meta">
                {aktif.layanan.cekFisik ? (
                  <span className="tag tag--warn">
                    <Ikon nama="alert" />
                    Wajib cek fisik
                  </span>
                ) : (
                  <span className="tag tag--ok">
                    <Ikon nama="check" />
                    Tanpa cek fisik
                  </span>
                )}
                {aktif.layanan.nonTunai && <span className="tag tag--line">Bisa non-tunai</span>}
                {aktif.layanan.diwakilkan && <span className="tag tag--line">Dapat diwakilkan</span>}
                {aktif.layanan.label.map((t) => (
                  <span key={t} className="tag tag--line">
                    {t}
                  </span>
                ))}
              </div>
            </div>

            <div className="checklist pl-daftar">
              <div className="checklist__head">
                <p>
                  <strong>Bawa saat datang</strong>
                </p>
                <p className="checklist__progress">
                  {jumlahSiap} dari {semuaBerkas.length} siap
                </p>
              </div>
              {kelompok.map((l, n) => (
                <div key={l.slug}>
                  {n > 0 && <p className="pl-sub">{l.nama}</p>}
                  <ul role="list">
                    {l.persyaratan.map((p, i) => (
                      <Berkas key={i} dokumen={p.dokumen} jumlah={p.jumlah} siap={siap.includes(`${l.slug}:${i}`)} ubah={() => alihSiap(`${l.slug}:${i}`)} />
                    ))}
                  </ul>
                </div>
              ))}
            </div>

            {kelompok
              .filter((l) => l.catatan)
              .map((l) => (
                <p key={l.slug} className="svc__note pl-catatan">
                  <Ikon nama="info" />
                  <span>{l.catatan}</span>
                </p>
              ))}

            <div className="pl-aksi">
              <button type="button" className="btn btn--dark btn--sm" onClick={cetak}>
                <Ikon nama="printer" />
                Cetak daftar
              </button>
              <a className="btn btn--ghost btn--sm" href={`#${aktif.layanan.slug}`}>
                Rincian layanan
              </a>
              <a className="btn btn--ghost btn--sm" href="#simulasi">
                <Ikon nama="calculator" />
                Cek biaya pajak
              </a>
            </div>
          </>
        )}
      </div>
    </div>
  )
}

/** Ambil alih elemen [data-react="panduan-layanan"] */
export function pasang(el: HTMLElement) {
  createRoot(el).render(<PanduanLayanan />)
}
