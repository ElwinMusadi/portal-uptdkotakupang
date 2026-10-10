import * as React from "react"
import { useBlocker } from "react-router"

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

/**
 * Cegah pindah halaman / menutup tab saat ada perubahan yang belum disimpan.
 * Pemblokir hanya dipasang selama formulir berubah.
 */
export function JagaPerubahan({ aktif }: { aktif: boolean }) {
  return aktif ? <Penjaga /> : null
}

function Penjaga() {
  const blocker = useBlocker(
    ({ currentLocation, nextLocation }) =>
      currentLocation.pathname !== nextLocation.pathname || currentLocation.search !== nextLocation.search
  )

  React.useEffect(() => {
    const onBeforeUnload = (e: BeforeUnloadEvent) => {
      e.preventDefault()
    }
    window.addEventListener("beforeunload", onBeforeUnload)
    return () => window.removeEventListener("beforeunload", onBeforeUnload)
  }, [])

  return (
    <AlertDialog open={blocker.state === "blocked"} onOpenChange={(o) => !o && blocker.reset?.()}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Tinggalkan halaman ini?</AlertDialogTitle>
          <AlertDialogDescription>
            Ada perubahan yang belum disimpan. Perubahan itu akan hilang bila Anda pindah halaman.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel onClick={() => blocker.reset?.()}>Tetap di sini</AlertDialogCancel>
          <AlertDialogAction
            className={buttonVariants({ variant: "destructive" })}
            onClick={() => blocker.proceed?.()}
          >
            Buang perubahan
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  )
}
