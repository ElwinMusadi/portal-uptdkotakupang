import * as React from "react"
import { useQueryClient } from "@tanstack/react-query"
import { IconAlertTriangle, IconCheck, IconExternalLink, IconHistory, IconInfoCircle, IconRestore } from "@tabler/icons-react"
import { useWatch, type UseFormReturn } from "react-hook-form"
import { toast } from "sonner"
import { z } from "zod"

import { Bagian, Baris } from "@/components/data/page"
import { FieldPilih } from "@/components/form/fields"
import { FormPengaturan } from "@/components/form/settings-form"
import { PratinjauPortal } from "@/components/tema/pratinjau-portal"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import type { BagianTema, BagianTemaPortal, Tema } from "@/lib/api"
import { sesi } from "@/lib/auth"
import { tanggalJam } from "@/lib/format"
import { laporGalat } from "@/lib/queries"
import {
  akhiriPratinjauPortal,
  buatTautanPratinjau,
  HURUF,
  hurufJudulDipakai,
  hurufPratinjauPortal,
  keluargaHuruf,
  LATAR,
  MODE_PORTAL,
  muatUlangTemaDashboard,
  POLA_WARNA,
  pratinjauDashboard,
  rasio,
  SKALA,
  SUDUT,
  TEBAL_JUDUL,
  TEMA_BAWAAN,
  TEMA_BAWAAN_PORTAL,
  TEMA_SIAP_PAKAI,
  temaSiapPakai,
  usePratinjauTema,
  useRiwayatTema,
  type Kontras,
  type RiwayatTema,
} from "@/lib/tema"
import { cn } from "@/lib/utils"

// Daftar tertutup yang sama dengan UpdateThemeRequest di server (D-057)
const skemaBagian = z.object({
  aksen: z.string().regex(POLA_WARNA, "Tulis kode warna #RRGGBB, mis. #2B63F6."),
  latar: z.enum(["hangat", "netral", "sejuk"]),
  hurufTeks: z.enum(HURUF.filter((h) => h.teks).map((h) => h.nilai) as [string, ...string[]]),
  hurufJudul: z.enum(["sama", ...HURUF.map((h) => h.nilai)] as [string, ...string[]]),
  sudut: z.enum(["tegas", "sedang", "membulat"]),
  skala: z.union([z.literal(90), z.literal(100), z.literal(110), z.literal(120)]),
  tebalJudul: z.union([z.literal(600), z.literal(700), z.literal(800)]),
})
const skemaTema = z.object({ portal: skemaBagian.extend({ mode: z.enum(["terang", "gelap"]) }), dashboard: skemaBagian })

const OPSI_HURUF_TEKS = HURUF.filter((h) => h.teks).map((h) => ({ nilai: h.nilai, label: h.label }))
const OPSI_HURUF_JUDUL = [
  { nilai: "sama", label: "Sama dengan huruf teks" },
  ...HURUF.map((h) => ({ nilai: h.nilai, label: h.label.replace(" (khusus judul)", "") })),
]

type NamaBagian = keyof Tema

/** Pengaturan › Tampilan: tema portal dan dashboard masing-masing, hanya Administrator yang menyimpan (D-057) */
export function TabTampilan() {
  const admin = sesi().peran === "administrator"
  const queryClient = useQueryClient()
  // Sesudah disimpan: dashboard memakai tema baru, pratinjau di portal berakhir, riwayat bertambah
  const setelahSimpan = React.useCallback(() => {
    muatUlangTemaDashboard()
    void akhiriPratinjauPortal()
    void queryClient.invalidateQueries({ queryKey: ["tema-riwayat"] })
  }, [queryClient])
  return (
    <FormPengaturan nama="tema" schema={skemaTema} setelahSimpan={setelahSimpan}>
      {(form) => <IsiTampilan form={form} admin={admin} />}
    </FormPengaturan>
  )
}

function IsiTampilan({ form, admin }: { form: UseFormReturn<Tema>; admin: boolean }) {
  const id = React.useId()
  const tema = useWatch({ control: form.control }) as Tema
  const { data: pratinjau, error } = usePratinjauTema(tema, admin)
  const [bagian, setBagian] = React.useState<NamaBagian>("portal")
  const [membuka, setMembuka] = React.useState(false)

  // Tab baru dibuka saat diklik agar tidak dihalangi pemblokir pop-up, lalu diarahkan ke tautan pratinjau
  const bukaDiPortal = async () => {
    const jendela = window.open("", "_blank")
    if (!(await form.trigger())) {
      jendela?.close()
      toast.error("Periksa kembali isian yang ditandai merah.")
      return
    }
    setMembuka(true)
    try {
      const { tautan } = await buatTautanPratinjau(form.getValues())
      if (jendela) jendela.location.href = tautan
      else toast.success("Tautan pratinjau siap.", { action: { label: "Buka", onClick: () => window.open(tautan, "_blank") } })
    } catch (e) {
      jendela?.close()
      laporGalat(e, "Pratinjau gagal dibuat")
    } finally {
      setMembuka(false)
    }
  }
  const akhiri = async () => {
    await akhiriPratinjauPortal()
    toast.success("Pratinjau di portal diakhiri di peramban ini.")
  }

  // Dashboard ini menjadi pratinjau tema dashboard selama tab ini terbuka; tema tersimpan kembali saat ditinggalkan
  const cssDashboard = pratinjau?.dashboard.css
  React.useEffect(() => {
    if (cssDashboard !== undefined) pratinjauDashboard(cssDashboard)
  }, [cssDashboard])
  const hurufPortal = pratinjau?.portal.huruf
  React.useEffect(() => {
    if (hurufPortal !== undefined) hurufPratinjauPortal(hurufPortal)
  }, [hurufPortal])
  React.useEffect(
    () => () => {
      pratinjauDashboard(null)
      hurufPratinjauPortal(null)
    },
    []
  )

  return (
    <fieldset disabled={!admin} className="grid min-w-0 gap-4 md:gap-6">
      {!admin && (
        <Alert>
          <IconInfoCircle />
          <AlertTitle>Hanya Administrator yang dapat mengubah tampilan</AlertTitle>
          <AlertDescription>Tema berlaku untuk seluruh portal dan dashboard.</AlertDescription>
        </Alert>
      )}
      {error && (
        <Alert variant="destructive">
          <IconAlertTriangle />
          <AlertTitle>Pratinjau gagal dimuat</AlertTitle>
          <AlertDescription>{(error as Error).message}</AlertDescription>
        </Alert>
      )}
      <Tabs value={bagian} onValueChange={(v) => setBagian(v as NamaBagian)} className="gap-4">
        <TabsList aria-label="Bagian yang diatur">
          <TabsTrigger value="portal">Portal</TabsTrigger>
          <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
        </TabsList>
        <TabsContent value="portal" className="grid gap-4 md:gap-6 xl:grid-cols-[minmax(0,1fr)_24rem] xl:items-start">
          <div className="grid min-w-0 gap-4 md:gap-6">
            <FormBagian
              id={id}
              form={form}
              bagian="portal"
              keterangan="Berlaku untuk semua pengunjung di semua halaman portal, halaman 404, Login Pegawai, Aktivasi, dan layar TV antrean."
              aksenJadi={pratinjau?.portal.variabel["--accent"]}
              kontras={pratinjau?.portal.kontras}
              admin={admin}
            />
          </div>
          {admin && (
            <div className="grid gap-2 xl:sticky xl:top-4">
              <p className="text-sm font-medium">Contoh portal</p>
              {pratinjau ? (
                <PratinjauPortal variabel={pratinjau.portal.variabel} skala={tema.portal.skala} />
              ) : (
                <Skeleton className="h-[34rem] rounded-xl" />
              )}
              <p className="text-xs text-muted-foreground">Logo PINTU tetap biru apa pun warna utamanya.</p>
              <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" size="sm" disabled={membuka} onClick={bukaDiPortal}>
                  {membuka ? <Spinner /> : <IconExternalLink />}
                  Pratinjau di portal
                </Button>
                <Button type="button" variant="ghost" size="sm" onClick={akhiri}>
                  Akhiri pratinjau
                </Button>
              </div>
              <p className="text-xs text-muted-foreground">
                Membuka beranda di tab baru dengan tema yang belum disimpan, hanya di peramban ini dan selama 30 menit. Pengunjung
                lain tetap melihat tema tersimpan.
              </p>
            </div>
          )}
        </TabsContent>
        <TabsContent value="dashboard" className="grid gap-4 md:gap-6">
          <FormBagian
            id={id}
            form={form}
            bagian="dashboard"
            keterangan={
              admin
                ? "Berlaku di dashboard dan aplikasi Absensi, mode terang dan gelap. Dashboard ini langsung menampilkan pilihan Anda sebagai pratinjau; pegawai lain baru melihatnya setelah disimpan."
                : "Berlaku di dashboard dan aplikasi Absensi, mode terang dan gelap."
            }
            aksenJadi={pratinjau?.dashboard.aksen}
            kontras={pratinjau?.dashboard.kontras}
            admin={admin}
          />
        </TabsContent>
      </Tabs>
      {admin && <RiwayatTampilan form={form} />}
    </fieldset>
  )
}

function FormBagian({
  id,
  form,
  bagian,
  keterangan,
  aksenJadi,
  kontras,
  admin,
}: {
  id: string
  form: UseFormReturn<Tema>
  bagian: NamaBagian
  keterangan: string
  aksenJadi?: string
  kontras?: Kontras[]
  admin: boolean
}) {
  const nilai = useWatch({ control: form.control, name: bagian })
  const cocok = temaSiapPakai(nilai)
  // Pilihan baru menimpa pilihan sekarang; yang tidak disebut (skala, mode portal) tetap
  const atur = (baru: Partial<BagianTemaPortal>) =>
    form.setValue(bagian, { ...nilai, ...baru } as BagianTemaPortal, { shouldDirty: true, shouldValidate: true })
  const disesuaikan = POLA_WARNA.test(nilai.aksen) && aksenJadi !== undefined && aksenJadi.toUpperCase() !== nilai.aksen.toUpperCase()
  const judul = hurufJudulDipakai(nilai)
  // Huruf judul yang berganti mendapat ketebalan yang disarankan untuknya; sesudahnya tetap bisa diubah
  const sarankanTebal = () =>
    form.setValue(`${bagian}.tebalJudul`, hurufJudulDipakai(form.getValues(bagian)).judul, { shouldDirty: true, shouldValidate: true })

  return (
    <>
      <Bagian
        judul="Tema siap pakai"
        deskripsi={`${keterangan} Sekarang: ${cocok ? cocok.nama : "Kustom"}.`}
      >
        <div className="grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">
          {TEMA_SIAP_PAKAI.map((t) => (
            <button
              key={t.id}
              type="button"
              aria-pressed={cocok?.id === t.id}
              onClick={() => atur({ ...t.nilai, tebalJudul: hurufJudulDipakai(t.nilai).judul })}
              className="grid gap-1 rounded-lg border p-3 text-left transition-colors hover:bg-muted/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-60 aria-pressed:border-ring aria-pressed:bg-muted/50"
            >
              <span className="flex items-center gap-2 text-sm font-medium">
                <span aria-hidden="true" className="flex">
                  <span className="size-4 rounded-full border border-black/10" style={{ background: t.nilai.aksen }} />
                  <span
                    className="-ml-1.5 size-4 rounded-full border border-black/10"
                    style={{ background: LATAR.find((l) => l.nilai === t.nilai.latar)?.contoh[0] }}
                  />
                </span>
                {t.nama}
                {cocok?.id === t.id && <IconCheck className="ml-auto size-4 text-brand" aria-hidden="true" />}
              </span>
              <span className="text-xs text-muted-foreground">{t.keterangan}</span>
            </button>
          ))}
        </div>
        <div className="flex flex-wrap gap-2">
          <Button type="button" variant="outline" size="sm" onClick={() => atur(bagian === "portal" ? TEMA_BAWAAN_PORTAL : TEMA_BAWAAN)}>
            <IconRestore />
            Kembalikan tampilan bawaan PINTU
          </Button>
          {bagian === "dashboard" && (
            <Button type="button" variant="outline" size="sm" onClick={() => atur(tanpaMode(form.getValues("portal")))}>
              Samakan dengan portal
            </Button>
          )}
        </div>
      </Bagian>

      <Bagian
        judul="Warna utama"
        deskripsi="Untuk tombol, tautan, sorotan, dan grafik. Warna turunannya (lebih gelap, lebih muda, dan versi panel gelap) dibuat otomatis."
      >
        <FormField
          control={form.control}
          name={`${bagian}.aksen`}
          render={({ field }) => (
            <FormItem>
              <FormLabel>Kode warna</FormLabel>
              <div className="flex items-center gap-2">
                <input
                  type="color"
                  aria-label="Pilih warna"
                  value={POLA_WARNA.test(field.value) ? field.value.toLowerCase() : "#000000"}
                  onChange={(e) => field.onChange(e.target.value.toUpperCase())}
                  className="h-9 w-12 shrink-0 cursor-pointer rounded-md border bg-transparent p-1 disabled:cursor-not-allowed"
                />
                <FormControl>
                  <Input
                    {...field}
                    onChange={(e) => field.onChange(e.target.value.trim())}
                    maxLength={7}
                    spellCheck={false}
                    autoComplete="off"
                    className="w-32 font-mono uppercase"
                  />
                </FormControl>
              </div>
              {disesuaikan && (
                <FormDescription className="flex flex-wrap items-center gap-2">
                  <span
                    aria-hidden="true"
                    className="size-3.5 rounded-full border border-black/10"
                    style={{ background: aksenJadi }}
                  />
                  Dipakai sebagai {aksenJadi} agar teks putih di atasnya dan tautan tetap terbaca.
                </FormDescription>
              )}
              <FormMessage />
            </FormItem>
          )}
        />
      </Bagian>

      {bagian === "portal" && (
        <Bagian
          judul="Mode tampilan"
          deskripsi="Berlaku untuk semua pengunjung, apa pun pengaturan perangkatnya. Panel gelap (papan layanan, dasbor beranda, layar TV) tetap gelap di kedua mode."
        >
          <FormField
            control={form.control}
            name="portal.mode"
            render={({ field }) => (
              <FormItem>
                <FormLabel className="sr-only">Mode tampilan</FormLabel>
                <FormControl>
                  <RadioGroup value={field.value} onValueChange={field.onChange} className="grid gap-2 sm:grid-cols-2">
                    {MODE_PORTAL.map((m) => (
                      <Label
                        key={m.nilai}
                        htmlFor={`${id}-portal-mode-${m.nilai}`}
                        className="gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                      >
                        <RadioGroupItem value={m.nilai} id={`${id}-portal-mode-${m.nilai}`} />
                        <span
                          aria-hidden="true"
                          className="flex size-6 shrink-0 items-center justify-center rounded-md border text-[0.6875rem] font-semibold"
                          style={{ background: m.contoh[0], color: m.contoh[1] }}
                        >
                          Aa
                        </span>
                        <span className="grid gap-0.5">
                          <span className="font-medium">{m.label}</span>
                          <span className="text-xs text-muted-foreground">{m.keterangan}</span>
                        </span>
                      </Label>
                    ))}
                  </RadioGroup>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </Bagian>
      )}

      <Bagian judul="Nuansa latar" deskripsi="Warna latar halaman, kotak abu, dan garis. Kecerahannya sama, jadi teks tetap terbaca.">
        <FormField
          control={form.control}
          name={`${bagian}.latar`}
          render={({ field }) => (
            <FormItem>
              <FormLabel className="sr-only">Nuansa latar</FormLabel>
              <FormControl>
                <RadioGroup value={field.value} onValueChange={field.onChange} className="grid gap-2 sm:grid-cols-3">
                  {LATAR.map((l) => (
                    <Label
                      key={l.nilai}
                      htmlFor={`${id}-${bagian}-latar-${l.nilai}`}
                      className="gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                    >
                      <RadioGroupItem value={l.nilai} id={`${id}-${bagian}-latar-${l.nilai}`} />
                      <span aria-hidden="true" className="flex shrink-0 overflow-hidden rounded-md border">
                        <span className="size-6" style={{ background: l.contoh[0] }} />
                        <span className="size-6" style={{ background: l.contoh[1] }} />
                      </span>
                      <span className="grid gap-0.5">
                        <span className="font-medium">{l.label}</span>
                        <span className="text-xs text-muted-foreground">{l.keterangan}</span>
                      </span>
                    </Label>
                  ))}
                </RadioGroup>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
      </Bagian>

      <Bagian
        judul="Huruf"
        deskripsi="Huruf sistem memakai huruf bawaan perangkat pengunjung. Huruf lain disimpan di situs sendiri (lisensi SIL Open Font License) dan hanya diunduh bila dipakai."
      >
        <Baris>
          <FieldPilih
            control={form.control}
            name={`${bagian}.hurufTeks`}
            label="Huruf teks"
            opsi={OPSI_HURUF_TEKS}
            onPilih={() => nilai.hurufJudul === "sama" && sarankanTebal()}
          />
          <FieldPilih
            control={form.control}
            name={`${bagian}.hurufJudul`}
            label="Huruf judul"
            opsi={OPSI_HURUF_JUDUL}
            onPilih={sarankanTebal}
          />
        </Baris>
        <FormField
          control={form.control}
          name={`${bagian}.tebalJudul`}
          render={({ field }) => (
            <FormItem>
              <FormLabel>Ketebalan judul</FormLabel>
              <FormControl>
                <RadioGroup
                  value={String(field.value)}
                  onValueChange={(v) => field.onChange(Number(v))}
                  className="grid gap-2 sm:grid-cols-3"
                >
                  {TEBAL_JUDUL.map((t) => (
                    <Label
                      key={t.nilai}
                      htmlFor={`${id}-${bagian}-tebal-${t.nilai}`}
                      className="gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50 has-[:disabled]:opacity-60"
                    >
                      <RadioGroupItem
                        value={String(t.nilai)}
                        id={`${id}-${bagian}-tebal-${t.nilai}`}
                        disabled={judul.maks !== undefined && t.nilai > judul.maks}
                      />
                      <span
                        aria-hidden="true"
                        className="w-9 shrink-0 text-xl leading-none"
                        style={{ fontFamily: keluargaHuruf(judul.keluarga), fontWeight: t.nilai }}
                      >
                        Aa
                      </span>
                      <span className="grid gap-0.5">
                        <span className="font-medium">{t.label}</span>
                        <span className="text-xs text-muted-foreground tabular-nums">
                          {t.nilai}
                          {t.nilai === judul.judul && " · disarankan"}
                        </span>
                      </span>
                    </Label>
                  ))}
                </RadioGroup>
              </FormControl>
              <FormDescription>
                {bagian === "portal"
                  ? "Judul dan angka besar yang memakai huruf judul. "
                  : "Judul kartu, dialog, dan lembar samping, serta angka besar. "}
                Saat huruf judul diganti, ketebalan diisi saran untuk huruf itu. Teks tetap biasa (400).
                {judul.maks !== undefined && ` ${judul.keluarga} paling tebal ${judul.maks}.`}
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
      </Bagian>

      <Bagian judul="Kelengkungan sudut" deskripsi="Sudut kartu, kotak, dan isian. Tombol berbentuk kapsul tetap kapsul.">
        <FormField
          control={form.control}
          name={`${bagian}.sudut`}
          render={({ field }) => (
            <FormItem>
              <FormLabel className="sr-only">Kelengkungan sudut</FormLabel>
              <FormControl>
                <RadioGroup value={field.value} onValueChange={field.onChange} className="grid gap-2 sm:grid-cols-3">
                  {SUDUT.map((s) => (
                    <Label
                      key={s.nilai}
                      htmlFor={`${id}-${bagian}-sudut-${s.nilai}`}
                      className="gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                    >
                      <RadioGroupItem value={s.nilai} id={`${id}-${bagian}-sudut-${s.nilai}`} />
                      <span
                        aria-hidden="true"
                        className="size-7 shrink-0 border-2 border-foreground/50 bg-muted"
                        style={{ borderRadius: s.px }}
                      />
                      <span className="grid gap-0.5">
                        <span className="font-medium">{s.label}</span>
                        <span className="text-xs text-muted-foreground">{s.keterangan}</span>
                      </span>
                    </Label>
                  ))}
                </RadioGroup>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
      </Bagian>

      <Bagian
        judul="Skala tampilan"
        deskripsi={
          bagian === "portal"
            ? "Memperbesar atau memperkecil seluruh halaman portal: teks, gambar, dan jarak. Layar TV antrean tidak ikut karena sudah memenuhi layarnya."
            : "Ukuran dasar teks dan jarak di dashboard dan Absensi. Di layar ponsel dan tablet (di bawah 1024 px), skala di atas 100% tidak dipakai agar halaman tidak melebar."
        }
      >
        <FormField
          control={form.control}
          name={`${bagian}.skala`}
          render={({ field }) => (
            <FormItem>
              <FormLabel className="sr-only">Skala tampilan</FormLabel>
              <FormControl>
                <RadioGroup
                  value={String(field.value)}
                  onValueChange={(v) => field.onChange(Number(v))}
                  className="grid grid-cols-2 gap-2 sm:grid-cols-4"
                >
                  {SKALA.map((s) => (
                    <Label
                      key={s.nilai}
                      htmlFor={`${id}-${bagian}-skala-${s.nilai}`}
                      className="gap-3 rounded-lg border p-3 font-normal has-[[data-state=checked]]:border-ring has-[[data-state=checked]]:bg-muted/50"
                    >
                      <RadioGroupItem value={String(s.nilai)} id={`${id}-${bagian}-skala-${s.nilai}`} />
                      <span className="grid gap-0.5">
                        <span className="font-medium tabular-nums">{s.label}</span>
                        <span className="text-xs text-muted-foreground">{s.keterangan}</span>
                      </span>
                    </Label>
                  ))}
                </RadioGroup>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
      </Bagian>

      {admin && (
        <Bagian
          judul="Keterbacaan"
          deskripsi="Rasio kontras pasangan warna yang memuat teks, dari pilihan di atas. Sedikitnya 4,5 : 1 (WCAG AA); warna yang kurang disesuaikan otomatis."
        >
          {kontras ? (
            <ul className="grid text-sm">
              {kontras.map((k) => (
                <li key={k.pasangan} className="flex items-center justify-between gap-3 border-b py-2 last:border-0">
                  <span>{k.pasangan}</span>
                  <span
                    className={cn(
                      "flex shrink-0 items-center gap-1 font-medium tabular-nums",
                      k.rasio >= 4.5 ? "text-success" : "text-destructive"
                    )}
                  >
                    {rasio(k.rasio)}
                    {k.rasio >= 4.5 ? (
                      <IconCheck className="size-4" aria-label="memenuhi" />
                    ) : (
                      <IconAlertTriangle className="size-4" aria-label="kurang" />
                    )}
                  </span>
                </li>
              ))}
            </ul>
          ) : (
            <Skeleton className="h-32" />
          )}
        </Bagian>
      )}
    </>
  )
}

/** Lima tema terakhir yang disimpan; "Pakai lagi" memuatnya ke formulir untuk disimpan ulang */
function RiwayatTampilan({ form }: { form: UseFormReturn<Tema> }) {
  const { data, error } = useRiwayatTema(true)
  const pakai = (r: RiwayatTema) => {
    form.setValue("portal", { ...r.tema.portal }, { shouldDirty: true, shouldValidate: true })
    form.setValue("dashboard", { ...r.tema.dashboard }, { shouldDirty: true, shouldValidate: true })
    toast.success("Tema dimuat ke formulir. Simpan untuk memakainya.")
  }

  return (
    <Bagian
      judul="Riwayat"
      deskripsi="Lima tema terakhir yang disimpan, terbaru di atas. Pakai lagi memuat tema itu ke formulir; simpan untuk memakainya."
    >
      {error ? (
        <p className="text-sm text-destructive">Riwayat gagal dimuat: {(error as Error).message}</p>
      ) : !data ? (
        <Skeleton className="h-24" />
      ) : data.length === 0 ? (
        <p className="text-sm text-muted-foreground">Belum ada tema yang disimpan dari tab ini.</p>
      ) : (
        <ul className="grid">
          {data.map((r, i) => (
            <li key={r.id} className="flex flex-wrap items-center gap-3 border-b py-3 first:pt-0 last:border-0 last:pb-0">
              <div className="grid min-w-0 flex-1 gap-1">
                <p className="flex flex-wrap items-center gap-2 text-sm font-medium">
                  {tanggalJam(r.disimpan)}
                  {i === 0 && <Badge variant="secondary">Sedang dipakai</Badge>}
                </p>
                <p className="text-xs text-muted-foreground">oleh {r.oleh ?? "akun yang sudah dihapus"}</p>
                <p className="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                  <RingkasanBagian judul="Portal" bagian={r.tema.portal} />
                  <RingkasanBagian judul="Dashboard" bagian={r.tema.dashboard} />
                </p>
              </div>
              <Button type="button" variant="outline" size="sm" onClick={() => pakai(r)}>
                <IconHistory />
                Pakai lagi
              </Button>
            </li>
          ))}
        </ul>
      )}
    </Bagian>
  )
}

function RingkasanBagian({ judul, bagian }: { judul: string; bagian: BagianTema | BagianTemaPortal }) {
  return (
    <span className="flex items-center gap-1.5">
      <span aria-hidden="true" className="size-3 rounded-full border border-black/10" style={{ background: bagian.aksen }} />
      <span>
        <span className="text-muted-foreground">{judul}:</span> {temaSiapPakai(bagian)?.nama ?? "Kustom"} · {bagian.skala}%
        {"mode" in bagian && bagian.mode === "gelap" ? " · gelap" : ""}
      </span>
    </span>
  )
}

/** Bagian portal tanpa mode, untuk disalin ke dashboard yang mode gelapnya pilihan tiap pegawai */
function tanpaMode(portal: BagianTemaPortal): BagianTema {
  const { aksen, latar, hurufTeks, hurufJudul, sudut, skala, tebalJudul } = portal
  return { aksen, latar, hurufTeks, hurufJudul, sudut, skala, tebalJudul }
}
