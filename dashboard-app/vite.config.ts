import fs from "node:fs"
import path from "node:path"
import { fileURLToPath } from "node:url"
import tailwindcss from "@tailwindcss/vite"
import react from "@vitejs/plugin-react"
import { defineConfig, type Plugin } from "vite"

const here = path.dirname(fileURLToPath(import.meta.url))
const portalRoot = path.resolve(here, "..")

const MIME: Record<string, string> = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".mjs": "text/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".webp": "image/webp",
  ".ico": "image/x-icon",
  ".woff2": "font/woff2",
  ".pdf": "application/pdf",
}

/**
 * Saat `npm run dev`, dashboard berjalan di /dashboard/ dan halaman portal
 * (login.html, assets/, dst.) dilayani dari folder induk. Dengan begitu alur
 * Login Pegawai → Dashboard bisa dicoba persis seperti di server sungguhan.
 */
function portalStatic(): Plugin {
  return {
    name: "pintu-portal-static",
    apply: "serve",
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const url = decodeURIComponent((req.url ?? "/").split("?")[0])
        if (url.startsWith("/dashboard")) return next()
        const file = path.join(portalRoot, url === "/" ? "index.html" : url)
        const inPortal = file.startsWith(portalRoot + path.sep)
        const inApp = file.startsWith(here + path.sep)
        if (!inPortal || inApp) return next()
        fs.stat(file, (err, stat) => {
          if (err || !stat.isFile()) return next()
          res.setHeader("Content-Type", MIME[path.extname(file)] ?? "application/octet-stream")
          fs.createReadStream(file).pipe(res)
        })
      })
    },
  }
}

export default defineConfig(({ command }) => ({
  // Build memakai path relatif agar dashboard bisa di-host di subfolder mana pun
  base: command === "serve" ? "/dashboard/" : "./",
  plugins: [react(), tailwindcss(), portalStatic()],
  resolve: {
    alias: { "@": path.resolve(here, "src") },
  },
  // Paket yang hanya dimuat oleh halaman lazy: dioptimalkan di awal agar dev server tidak memuat ulang
  optimizeDeps: {
    include: [
      "@tiptap/react",
      "@tiptap/starter-kit",
      "@tiptap/extensions",
      "react-day-picker",
      "react-day-picker/locale",
      "zod",
      "@hookform/resolvers/zod",
      "react-hook-form",
      "recharts",
      "@dnd-kit/core",
      "@dnd-kit/sortable",
      "@dnd-kit/modifiers",
      "@dnd-kit/utilities",
    ],
  },
  build: {
    outDir: path.resolve(portalRoot, "dashboard"),
    emptyOutDir: true,
    chunkSizeWarningLimit: 700,
  },
}))
