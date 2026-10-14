import path from "node:path"
import tailwindcss from "@tailwindcss/vite"
import react from "@vitejs/plugin-react"
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"

/**
 * Builds the React dashboard (resources/js/dashboard) into public/build.
 * Laravel serves it at /dashboard through resources/views/dashboard.blade.php,
 * and the officers' attendance app (resources/js/absen) at /absen.
 * The public portal (resources/views/portal, public/assets) is server-rendered
 * HTML that moves to React one part at a time (D-045). Its pages are served as
 * written and load the scripts built here from fixed addresses (routes/web.php):
 * /skrip/portal for resources/js/portal/main.tsx, which mounts the portal's
 * React components, and /skrip/cek-pajak for the tax check (D-041). Those pages
 * have no Fast Refresh preamble, so portal files are left out of Fast Refresh
 * and reload fully during development.
 */
export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/js/dashboard/main.tsx", "resources/js/absen/main.tsx", "resources/js/portal/main.tsx", "resources/js/portal/cek-pajak.ts"],
      refresh: true,
    }),
    react({ exclude: [/\/node_modules\//, /\/resources\/js\/portal\//] }),
    tailwindcss(),
  ],
  resolve: {
    alias: { "@": path.resolve(import.meta.dirname, "resources/js/dashboard") },
  },
  // Packages only used by lazy pages: optimized upfront so the dev server does not reload
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
    chunkSizeWarningLimit: 700,
  },
  server: {
    watch: {
      ignored: ["**/storage/framework/views/**"],
    },
  },
})
