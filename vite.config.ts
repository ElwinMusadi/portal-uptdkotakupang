import path from "node:path"
import tailwindcss from "@tailwindcss/vite"
import react from "@vitejs/plugin-react"
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"

/**
 * Builds the React dashboard (resources/js/dashboard) into public/build.
 * Laravel serves it at /dashboard through resources/views/dashboard.blade.php.
 * The public portal (resources/views/portal, public/assets) needs no build.
 */
export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/js/dashboard/main.tsx"],
      refresh: true,
    }),
    react(),
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
