import { StrictMode } from "react"
import { createRoot } from "react-dom/client"

import { muatSesi } from "@/lib/auth"

import { AplikasiAbsen } from "./app"
import "./absen.css"

const root = document.getElementById("root")!

// Service worker kecil agar aplikasi bisa dipasang ke layar utama (absen-sw.js); clock-in tetap butuh koneksi
if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker.register("/absen-sw.js", { scope: "/absen" }).catch(() => {})
  })
}

muatSesi()
  .then((sesi) => {
    if (!sesi) {
      window.location.replace("/login?lanjut=absen")
      return
    }
    createRoot(root).render(
      <StrictMode>
        <AplikasiAbsen sesi={sesi} />
      </StrictMode>
    )
  })
  .catch(() => {
    root.textContent = "Aplikasi absensi tidak dapat memeriksa sesi login. Periksa koneksi internet lalu muat ulang."
  })
