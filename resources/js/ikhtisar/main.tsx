import { StrictMode } from "react"
import { QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { createRoot } from "react-dom/client"

import { TooltipProvider } from "@/components/ui/tooltip"

import { AplikasiIkhtisar } from "./app"
import "./ikhtisar.css"

/*
 * Halaman Ikhtisar (D-099, D-100) di /laporan-penerimaan: penerimaan,
 * Realisasi Target, dan Pemetaan PKB tanpa login, selama Administrator
 * mengaktifkannya.
 */
const queryClient = new QueryClient({ defaultOptions: { queries: { staleTime: 60_000 } } })

// Token tautan D-099 yang mungkin masih tersimpan di perangkat ini tidak dipakai lagi
try {
  localStorage.removeItem("pintu-ikhtisar")
} catch {
  // penyimpanan tidak tersedia
}

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <TooltipProvider delayDuration={300}>
        <AplikasiIkhtisar />
      </TooltipProvider>
    </QueryClientProvider>
  </StrictMode>
)
