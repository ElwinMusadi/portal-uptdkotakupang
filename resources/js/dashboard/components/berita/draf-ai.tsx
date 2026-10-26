import * as React from "react"
import { IconAlertTriangle, IconFileUpload, IconSparkles, IconX } from "@tabler/icons-react"
import { z } from "zod"

import { Bagian } from "@/components/data/page"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Spinner } from "@/components/ui/spinner"
import { Textarea } from "@/components/ui/textarea"
import { BATAS_DRAF_AI, useBuatDrafAi, type SumberDrafAi } from "@/lib/ai"
import { ApiError, type DrafAi, type KategoriBerita } from "@/lib/api"
import { KATEGORI_BERITA } from "@/lib/meta"

const MB = 1024 * 1024

// Batas yang sama dengan server (GenerateArticleDraftRequest)
const schema = z
  .object({
    tautan: z
      .string()
      .trim()
      .max(2000, "Tautan maksimal 2000 karakter.")
      .refine((t) => t === "" || /^https?:\/\/[^\s/]+/i.test(t), "Tautan harus alamat lengkap yang diawali http:// atau https://."),
    berkas: z
      .array(z.instanceof(File))
      .max(BATAS_DRAF_AI.berkas, `Unggah paling banyak ${BATAS_DRAF_AI.berkas} dokumen.`)
      .refine((b) => b.every((f) => BATAS_DRAF_AI.ekstensi.some((e) => f.name.toLowerCase().endsWith(e))), "Dokumen harus PDF, Word (.docx), JPEG, atau PNG.")
      .refine((b) => b.reduce((n, f) => n + f.size, 0) <= BATAS_DRAF_AI.bait, "Ukuran semua dokumen maksimal 10 MB."),
    kategori: z.enum(["", "berita", "pengumuman", "keliling", "edukasi"]),
    petunjuk: z.string().max(BATAS_DRAF_AI.petunjuk, `Petunjuk tambahan maksimal ${BATAS_DRAF_AI.petunjuk} karakter.`),
  })
  .refine((s) => s.tautan.trim() !== "" || s.berkas.length > 0, { path: ["tautan"], message: "Isi tautan atau unggah dokumen sebagai sumber." })

const ukuran = (bait: number) => (bait >= MB ? `${(bait / MB).toFixed(1).replace(".", ",")} MB` : `${Math.max(1, Math.round(bait / 1024))} KB`)

/**
 * Buat draf dengan AI (D-085): tautan dan/atau dokumen (PDF, Word, JPEG,
 * PNG) dibaca AI dari Pengaturan › Integrasi AI, lalu judul, ringkasan, isi,
 * dan kategori mengisi formulir di bawahnya. Belum ada yang tersimpan sampai
 * penulis menekan Simpan; dokumen tidak disimpan PINTU.
 */
export function PanelDrafAi({ onDraf }: { onDraf: (draf: DrafAi) => Promise<boolean> }) {
  const buat = useBuatDrafAi()
  const masukan = React.useRef<HTMLInputElement>(null)
  const [sumber, setSumber] = React.useState<SumberDrafAi>({ tautan: "", berkas: [], kategori: "", petunjuk: "" })
  const [galat, setGalat] = React.useState<string | null>(null)
  const id = React.useId()

  const ubah = <K extends keyof SumberDrafAi>(kunci: K, nilai: SumberDrafAi[K]) => {
    setSumber((s) => ({ ...s, [kunci]: nilai }))
    setGalat(null)
  }

  const kirim = async () => {
    const cek = schema.safeParse(sumber)
    if (!cek.success) {
      setGalat(cek.error.issues[0]?.message ?? "Periksa kembali sumbernya.")
      return
    }
    setGalat(null)
    try {
      const draf = await buat.mutateAsync(sumber)
      await onDraf(draf)
    } catch (e) {
      setGalat(e instanceof ApiError || e instanceof Error ? e.message : "Draf gagal dibuat. Coba lagi.")
    }
  }

  return (
    <Bagian
      judul="Buat draf dengan AI"
      deskripsi="AI membaca tautan atau dokumen, lalu mengisi judul, ringkasan, isi, dan kategori di bawah. Periksa fakta, tanggal, dan nama sebelum menyimpan; AI bisa keliru."
    >
      <div className="grid gap-2">
        <Label htmlFor={`${id}-tautan`}>Tautan berita atau dokumen</Label>
        <Input
          id={`${id}-tautan`}
          type="url"
          inputMode="url"
          placeholder="https://…"
          value={sumber.tautan}
          onChange={(e) => ubah("tautan", e.target.value)}
          disabled={buat.isPending}
        />
        <p className="text-sm text-muted-foreground">Halaman berita, PDF, atau gambar yang bisa dibuka tanpa masuk akun.</p>
      </div>

      <div className="grid gap-2">
        <Label htmlFor={`${id}-berkas`}>Dokumen</Label>
        <input
          ref={masukan}
          id={`${id}-berkas`}
          type="file"
          multiple
          accept=".pdf,.docx,.jpg,.jpeg,.png,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png"
          className="sr-only"
          onChange={(e) => {
            const baru = Array.from(e.target.files ?? [])
            ubah("berkas", [...sumber.berkas, ...baru].slice(0, BATAS_DRAF_AI.berkas + 1))
            e.target.value = ""
          }}
          disabled={buat.isPending}
        />
        <div className="flex flex-wrap items-center gap-2">
          <Button type="button" variant="outline" size="sm" onClick={() => masukan.current?.click()} disabled={buat.isPending}>
            <IconFileUpload />
            Pilih dokumen
          </Button>
          <p className="text-sm text-muted-foreground">
            SK, Pergub, UU, surat tugas, atau dokumen lain: PDF, Word (.docx), JPEG, atau PNG; paling banyak {BATAS_DRAF_AI.berkas} berkas, 10 MB
            seluruhnya.
          </p>
        </div>
        {sumber.berkas.length > 0 && (
          <ul className="grid gap-1" aria-label="Dokumen terpilih">
            {sumber.berkas.map((b, i) => (
              <li key={`${b.name}-${i}`} className="flex min-w-0 items-center gap-2 rounded-md border px-3 py-1.5 text-sm">
                <span className="min-w-0 flex-1 truncate">{b.name}</span>
                <span className="shrink-0 text-muted-foreground">{ukuran(b.size)}</span>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  className="size-7"
                  onClick={() => ubah("berkas", sumber.berkas.filter((_, j) => j !== i))}
                  disabled={buat.isPending}
                >
                  <IconX />
                  <span className="sr-only">Hapus {b.name}</span>
                </Button>
              </li>
            ))}
          </ul>
        )}
      </div>

      <div className="grid gap-4 sm:grid-cols-[minmax(0,14rem)_minmax(0,1fr)]">
        <div className="grid content-start gap-2">
          <Label htmlFor={`${id}-kategori`}>Kategori</Label>
          <Select
            value={sumber.kategori || "otomatis"}
            onValueChange={(k) => ubah("kategori", k === "otomatis" ? "" : (k as KategoriBerita))}
            disabled={buat.isPending}
          >
            <SelectTrigger id={`${id}-kategori`} className="w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="otomatis">Pilih otomatis</SelectItem>
              {Object.entries(KATEGORI_BERITA).map(([nilai, label]) => (
                <SelectItem key={nilai} value={nilai}>
                  {label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="grid gap-2">
          <Label htmlFor={`${id}-petunjuk`}>Petunjuk tambahan</Label>
          <Textarea
            id={`${id}-petunjuk`}
            rows={2}
            maxLength={BATAS_DRAF_AI.petunjuk}
            placeholder="Mis. tekankan jadwal dan persyaratannya, tulis sebagai pengumuman singkat."
            value={sumber.petunjuk}
            onChange={(e) => ubah("petunjuk", e.target.value)}
            disabled={buat.isPending}
          />
        </div>
      </div>

      <p className="text-sm text-muted-foreground">
        Tautan dan dokumen dikirim ke penyedia AI di Pengaturan › Integrasi AI untuk dibaca, lalu tidak disimpan PINTU. Hindari dokumen berisi
        data pribadi yang tidak perlu, seperti NIK, alamat, atau nomor telepon.
      </p>

      {galat && (
        <Alert variant="destructive" role="alert">
          <IconAlertTriangle />
          <AlertTitle>Draf belum dapat dibuat</AlertTitle>
          <AlertDescription>{galat}</AlertDescription>
        </Alert>
      )}

      <div className="flex flex-wrap items-center gap-3">
        <Button type="button" onClick={() => void kirim()} disabled={buat.isPending}>
          {buat.isPending ? <Spinner /> : <IconSparkles />}
          {buat.isPending ? "Menulis draf…" : "Buat draf"}
        </Button>
        {buat.isPending && (
          <p className="text-sm text-muted-foreground" role="status">
            AI sedang membaca sumber dan menulis draf, biasanya 10–60 detik.
          </p>
        )}
      </div>
    </Bagian>
  )
}
