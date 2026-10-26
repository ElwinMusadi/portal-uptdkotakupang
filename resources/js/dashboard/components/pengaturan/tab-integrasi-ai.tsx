import { zodResolver } from "@hookform/resolvers/zod"
import { IconAlertTriangle, IconCircleCheck, IconPlugConnected } from "@tabler/icons-react"
import { useForm, type Resolver } from "react-hook-form"
import { toast } from "sonner"
import { z } from "zod"

import { Bagian, Baris } from "@/components/data/page"
import { FieldSakelar, FieldTeks } from "@/components/form/fields"
import { BilahSimpan, KerangkaMemuat } from "@/components/form/settings-form"
import { JagaPerubahan } from "@/components/form/unsaved"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Form } from "@/components/ui/form"
import { Spinner } from "@/components/ui/spinner"
import { useIntegrasiAi, useSimpanIntegrasiAi, useUjiIntegrasiAi, type NilaiIntegrasiAi } from "@/lib/ai"
import type { IntegrasiAi } from "@/lib/api"
import * as v from "@/lib/validasi"

const maks = (n: number) => `Maksimal ${n} karakter.`

// Batas yang sama dengan server (AiIntegrationRules)
const schema = z.object({
  baseUrl: v
    .wajib("Base URL")
    .max(200, maks(200))
    .refine((u) => /^https:\/\/[^\s/]+/i.test(u), "Base URL harus alamat lengkap yang diawali https://."),
  model: v
    .wajib("Model")
    .max(120, maks(120))
    .regex(/^[A-Za-z0-9][A-Za-z0-9._:/@+-]*$/, "Nama model hanya boleh berisi huruf, angka, dan tanda . _ - / : @ +."),
  kunci: v.teks.max(300, maks(300)).regex(/^[\x21-\x7E]*$/, "Kunci API tidak boleh berisi spasi atau huruf di luar ASCII."),
  hapusKunci: z.boolean(),
})

const SUMBER_KUNCI: Record<NonNullable<IntegrasiAi["kunci"]["sumber"]>, string> = {
  pengaturan: "disimpan di Pengaturan",
  env: "dari .env server",
}

function nilaiAwal(data: IntegrasiAi): NilaiIntegrasiAi {
  return { baseUrl: data.baseUrl, model: data.model, kunci: "", hapusKunci: false }
}

/**
 * Pengaturan › Integrasi AI (D-085), khusus Administrator: API kompatibel
 * OpenAI yang menulis draf berita. Kunci hanya bisa ditulis; setelah disimpan
 * yang tampil hanya 4 karakter terakhirnya.
 */
export function TabIntegrasiAi() {
  const { data, error } = useIntegrasiAi(true)
  if (error) {
    return (
      <p className="rounded-lg border border-destructive/40 p-4 text-sm text-destructive">
        Data gagal dimuat: {(error as Error).message}
      </p>
    )
  }
  if (!data) return <KerangkaMemuat />
  return <FormIntegrasiAi key={`${data.baseUrl} ${data.model} ${data.kunci.akhir}`} data={data} />
}

function FormIntegrasiAi({ data }: { data: IntegrasiAi }) {
  const simpan = useSimpanIntegrasiAi()
  const uji = useUjiIntegrasiAi()
  const form = useForm<NilaiIntegrasiAi>({
    defaultValues: nilaiAwal(data),
    resolver: zodResolver(schema) as Resolver<NilaiIntegrasiAi>,
    mode: "onTouched",
  })
  const berubah = form.formState.isDirty
  const hapusKunci = form.watch("hapusKunci")
  const kunciBaru = form.watch("kunci")

  const onSubmit = form.handleSubmit(
    async (nilai) => {
      try {
        const tersimpan = await simpan.mutateAsync(nilai)
        form.reset(nilaiAwal(tersimpan))
        uji.reset()
        toast.success("Integrasi AI disimpan")
      } catch (e) {
        toast.error((e as Error).message)
      }
    },
    () => toast.error("Periksa kembali isian yang ditandai merah.")
  )

  const ujiKoneksi = async () => {
    if (!(await form.trigger(["baseUrl", "model", "kunci"], { shouldFocus: true }))) {
      toast.error("Periksa kembali isian yang ditandai merah.")
      return
    }
    const { baseUrl, model, kunci } = form.getValues()
    try {
      await uji.mutateAsync({ baseUrl, model, kunci })
    } catch (e) {
      toast.error((e as Error).message)
    }
  }

  const keteranganKunci = hapusKunci
    ? "Kunci tersimpan akan dihapus; server kembali memakai kunci .env bila ada."
    : kunciBaru
      ? "Kunci baru dipakai setelah disimpan."
      : data.kunci.ada
        ? `Kunci yang dipakai: ••••${data.kunci.akhir ?? ""} (${data.kunci.sumber ? SUMBER_KUNCI[data.kunci.sumber] : ""}). Kosongkan bila tidak diganti.`
        : "Belum ada kunci. Isi kunci API dari penyedia."

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} noValidate className="flex flex-col gap-4 md:gap-6">
        <Bagian
          judul="Integrasi AI"
          deskripsi="API pihak ketiga yang menulis draf di Berita & Pengumuman › Tulis berita › Buat dengan AI. PINTU memakai API yang kompatibel dengan OpenAI (POST {Base URL}/chat/completions); modelnya harus bisa membaca PDF dan gambar."
        >
          {!data.tersimpan && (
            <Alert>
              <IconPlugConnected />
              <AlertTitle>Masih memakai .env server</AlertTitle>
              <AlertDescription>
                Nilai di bawah berasal dari .env server. Setelah disimpan di sini, Pengaturan yang dipakai.
              </AlertDescription>
            </Alert>
          )}
          <Baris>
            <FieldTeks
              control={form.control}
              name="baseUrl"
              label="Base URL"
              wajib
              placeholder="https://ai.sumopod.com/v1"
              deskripsi="Mis. Sumopod https://ai.sumopod.com/v1, OpenRouter https://openrouter.ai/api/v1, OpenAI https://api.openai.com/v1."
            />
            <FieldTeks
              control={form.control}
              name="model"
              label="Model"
              wajib
              placeholder="gemini/gemini-4-argon"
              deskripsi="Nama model persis seperti di daftar model penyedia."
            />
          </Baris>
          <FieldTeks
            control={form.control}
            name="kunci"
            label="Kunci API"
            type="password"
            autoComplete="off"
            spellCheck={false}
            placeholder={data.kunci.ada ? "Kosongkan untuk memakai kunci yang ada" : "Tempel kunci API"}
            deskripsi={keteranganKunci}
          />
          {data.kunci.sumber === "pengaturan" && (
            <FieldSakelar
              control={form.control}
              name="hapusKunci"
              label="Hapus kunci yang disimpan di Pengaturan"
              deskripsi="Server kembali memakai kunci di .env bila ada."
            />
          )}
          <div className="grid gap-3">
            <div className="flex flex-wrap items-center gap-3">
              <Button type="button" variant="outline" onClick={() => void ujiKoneksi()} disabled={uji.isPending}>
                {uji.isPending ? <Spinner /> : <IconPlugConnected />}
                Uji koneksi
              </Button>
              <p className="text-sm text-muted-foreground">
                Menguji isian di atas, sudah disimpan atau belum, dengan satu permintaan singkat ke model.
              </p>
            </div>
            {uji.data && (
              <Alert variant={uji.data.terhubung ? "default" : "destructive"} role="status">
                {uji.data.terhubung ? <IconCircleCheck className="text-success" /> : <IconAlertTriangle />}
                <AlertTitle>{uji.data.terhubung ? "Terhubung" : "Belum terhubung"}</AlertTitle>
                <AlertDescription>
                  <p>{uji.data.pesan}</p>
                  {uji.data.penyedia && <p>Pesan penyedia: “{uji.data.penyedia}”</p>}
                </AlertDescription>
              </Alert>
            )}
          </div>
        </Bagian>
        <BilahSimpan berubah={berubah} menyimpan={simpan.isPending} onBatal={() => form.reset()} />
      </form>
      <JagaPerubahan aktif={berubah && !simpan.isPending} />
    </Form>
  )
}
