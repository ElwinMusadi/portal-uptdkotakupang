import * as React from "react"
import {
  IconCamera,
  IconDeviceDesktop,
  IconExternalLink,
  IconFileDescription,
  IconLogout,
  IconMoon,
  IconSun,
} from "@tabler/icons-react"
import { useNavigate } from "react-router"

import { BUAT_CEPAT, SEMUA_NAV } from "@/app/nav"
import { useTheme } from "@/components/theme-provider"
import {
  CommandDialog,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from "@/components/ui/command"
import { keluar } from "@/lib/auth"
import { useKoleksi } from "@/lib/queries"
import { urlPortal } from "@/lib/portal"

type CommandMenuContext = { open: boolean; setOpen: (open: boolean) => void }

const Ctx = React.createContext<CommandMenuContext | null>(null)

export function useCommandMenu() {
  const ctx = React.useContext(Ctx)
  if (!ctx) throw new Error("useCommandMenu harus dipakai di dalam <CommandMenuProvider>")
  return ctx
}

export function CommandMenuProvider({ children }: { children: React.ReactNode }) {
  const [open, setOpen] = React.useState(false)

  React.useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key.toLowerCase() === "k" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
        setOpen((o) => !o)
      }
    }
    window.addEventListener("keydown", onKey)
    return () => window.removeEventListener("keydown", onKey)
  }, [])

  const value = React.useMemo(() => ({ open, setOpen }), [open])
  return (
    <Ctx.Provider value={value}>
      {children}
      <CommandMenu open={open} setOpen={setOpen} />
    </Ctx.Provider>
  )
}

function CommandMenu({ open, setOpen }: CommandMenuContext) {
  const navigate = useNavigate()
  const { setTheme } = useTheme()
  const { data: berita } = useKoleksi("berita")
  const { data: layanan } = useKoleksi("layanan")
  const { data: dokumen } = useKoleksi("dokumen")

  const jalankan = (fn: () => void) => {
    setOpen(false)
    fn()
  }

  return (
    <CommandDialog
      open={open}
      onOpenChange={setOpen}
      title="Cari di dashboard"
      description="Cari halaman, konten, atau tindakan"
    >
      <CommandInput placeholder="Cari halaman, berita, layanan, dokumen…" />
      <CommandList>
        <CommandEmpty>Tidak ada yang cocok.</CommandEmpty>
        <CommandGroup heading="Halaman">
          {SEMUA_NAV.map((item) => (
            <CommandItem
              key={item.url}
              value={`halaman ${item.judul} ${item.keterangan ?? ""}`}
              onSelect={() => jalankan(() => navigate(item.url))}
            >
              <item.ikon />
              {item.judul}
            </CommandItem>
          ))}
        </CommandGroup>
        <CommandSeparator />
        <CommandGroup heading="Buat baru">
          {BUAT_CEPAT.map((b) => (
            <CommandItem
              key={b.url}
              value={`buat baru tambah ${b.judul}`}
              onSelect={() => jalankan(() => navigate(b.url))}
            >
              <b.ikon />
              Buat {b.judul.toLowerCase()}
            </CommandItem>
          ))}
        </CommandGroup>
        <CommandSeparator />
        <CommandGroup heading="Konten">
          {berita?.map((b) => (
            <CommandItem
              key={b.id}
              value={`berita ${b.judul} ${b.id}`}
              onSelect={() => jalankan(() => navigate(`/berita/${b.id}`))}
            >
              <IconFileDescription />
              <span className="truncate">{b.judul}</span>
            </CommandItem>
          ))}
          {layanan?.map((l) => (
            <CommandItem
              key={l.id}
              value={`layanan persyaratan ${l.nama} ${l.id}`}
              onSelect={() => jalankan(() => navigate(`/layanan/${l.id}`))}
            >
              <IconFileDescription />
              <span className="truncate">Persyaratan · {l.nama}</span>
            </CommandItem>
          ))}
          {dokumen?.map((d) => (
            <CommandItem
              key={d.id}
              value={`dokumen unduhan ${d.judul} ${d.id}`}
              onSelect={() => jalankan(() => navigate(`/unduhan?ubah=${d.id}`))}
            >
              <IconFileDescription />
              <span className="truncate">Dokumen · {d.judul}</span>
            </CommandItem>
          ))}
        </CommandGroup>
        <CommandSeparator />
        <CommandGroup heading="Tindakan">
          <CommandItem value="absen sekarang absensi lapangan" onSelect={() => jalankan(() => navigate("/absensi?baru=1"))}>
            <IconCamera />
            Absen kegiatan lapangan
          </CommandItem>
          <CommandItem
            value="lihat portal publik"
            onSelect={() => jalankan(() => window.open(urlPortal("index.html"), "_blank", "noopener"))}
          >
            <IconExternalLink />
            Lihat portal publik
          </CommandItem>
          <CommandItem value="tema terang" onSelect={() => jalankan(() => setTheme("light"))}>
            <IconSun />
            Tema terang
          </CommandItem>
          <CommandItem value="tema gelap" onSelect={() => jalankan(() => setTheme("dark"))}>
            <IconMoon />
            Tema gelap
          </CommandItem>
          <CommandItem value="tema sistem" onSelect={() => jalankan(() => setTheme("system"))}>
            <IconDeviceDesktop />
            Ikuti tema sistem
          </CommandItem>
          <CommandItem value="keluar logout" onSelect={() => jalankan(keluar)}>
            <IconLogout />
            Keluar
          </CommandItem>
        </CommandGroup>
      </CommandList>
    </CommandDialog>
  )
}
