import { StrictMode } from "react"
import { QueryClientProvider } from "@tanstack/react-query"
import { createRoot } from "react-dom/client"
import { RouterProvider } from "react-router"

import { router } from "@/app/router"
import { ThemeProvider } from "@/components/theme-provider"
import { Toaster } from "@/components/ui/sonner"
import { TooltipProvider } from "@/components/ui/tooltip"
import { bacaSesi, urlLogin } from "@/lib/auth"
import { queryClient } from "@/lib/queries"

import "./index.css"

// Tanpa sesi login yang sah, kembali ke halaman Login Pegawai portal
if (!bacaSesi()) {
  window.location.replace(urlLogin())
} else {
  createRoot(document.getElementById("root")!).render(
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
}
