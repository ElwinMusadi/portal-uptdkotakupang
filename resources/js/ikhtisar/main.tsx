import { StrictMode } from "react"
import { QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { createRoot } from "react-dom/client"

import { TooltipProvider } from "@/components/ui/tooltip"

import { AplikasiIkhtisar } from "./app"
import "./ikhtisar.css"

/*
 * Halaman Ikhtisar (D-099) di /ikhtisar: penerimaan, Realisasi Target, dan
 * Pemetaan PKB untuk pemegang tautan dari Administrator, tanpa login.
 */
const queryClient = new QueryClient({ defaultOptions: { queries: { staleTime: 60_000 } } })

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <TooltipProvider delayDuration={300}>
        <AplikasiIkhtisar />
      </TooltipProvider>
    </QueryClientProvider>
  </StrictMode>
)
