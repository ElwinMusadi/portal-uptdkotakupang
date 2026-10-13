import * as React from "react"

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog"
import { buttonVariants } from "@/components/ui/button"
import { cn } from "@/lib/utils"

interface OpsiKonfirmasi {
  judul: string
  deskripsi?: React.ReactNode
  label?: string
  bahaya?: boolean
}

type Penyelesai = (ya: boolean) => void

const Ctx = React.createContext<((o: OpsiKonfirmasi) => Promise<boolean>) | null>(null)

/** Dialog konfirmasi berbasis Promise: `if (await konfirmasi({...})) hapus()` */
export function KonfirmasiProvider({ children }: { children: React.ReactNode }) {
  const [opsi, setOpsi] = React.useState<OpsiKonfirmasi | null>(null)
  const penyelesai = React.useRef<Penyelesai | null>(null)

  const konfirmasi = React.useCallback((o: OpsiKonfirmasi) => {
    setOpsi(o)
    return new Promise<boolean>((resolve) => {
      penyelesai.current = resolve
    })
  }, [])

  const selesai = (ya: boolean) => {
    penyelesai.current?.(ya)
    penyelesai.current = null
    setOpsi(null)
  }

  return (
    <Ctx.Provider value={konfirmasi}>
      {children}
      <AlertDialog open={!!opsi} onOpenChange={(o) => !o && selesai(false)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{opsi?.judul}</AlertDialogTitle>
            {opsi?.deskripsi && <AlertDialogDescription>{opsi.deskripsi}</AlertDialogDescription>}
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => selesai(false)}>Batal</AlertDialogCancel>
            <AlertDialogAction
              className={cn(opsi?.bahaya && buttonVariants({ variant: "destructive" }))}
              onClick={() => selesai(true)}
            >
              {opsi?.label ?? "Lanjutkan"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </Ctx.Provider>
  )
}

export function useKonfirmasi() {
  const ctx = React.useContext(Ctx)
  if (!ctx) throw new Error("useKonfirmasi harus dipakai di dalam <KonfirmasiProvider>")
  return ctx
}
