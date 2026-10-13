import { StrictMode } from "react"
import { QueryClientProvider } from "@tanstack/react-query"
import { createRoot } from "react-dom/client"
import { RouterProvider } from "react-router"

import { router } from "@/app/router"
import { ThemeProvider } from "@/components/theme-provider"
import { Toaster } from "@/components/ui/sonner"
import { TooltipProvider } from "@/components/ui/tooltip"
import { muatSesi, urlLogin } from "@/lib/auth"
import { queryClient } from "@/lib/queries"

import "./index.css"

const root = document.getElementById("root")!

// Pastikan sesi login masih berlaku di server sebelum dashboard tampil
muatSesi()
  .then((sesi) => {
    if (!sesi) {
      window.location.replace(urlLogin())
      return
    }
    createRoot(root).render(
      <StrictMode>
        <ThemeProvider>
          <QueryClientProvider client={queryClient}>
            <TooltipProvider delayDuration={300}>
              <RouterProvider router={router} />
              <Toaster position="bottom-right" richColors={false} closeButton />
            </TooltipProvider>
          </QueryClientProvider>
        </ThemeProvider>
      </StrictMode>
    )
  })
  .catch(() => {
    root.textContent = "Dashboard tidak dapat memeriksa sesi login. Periksa koneksi lalu muat ulang halaman."
  })
