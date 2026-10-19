import * as React from "react"

import { Input } from "@/components/ui/input"
import { angka } from "@/lib/format"

/** Isian rupiah: angka mentah saat diketik, berformat ribuan saat tidak difokus */
export function InputRupiah({
  id,
  nilai,
  ubah,
  describedBy,
  invalid,
}: {
  id: string
  nilai: number
  ubah: (n: number) => void
  describedBy?: string
  invalid?: boolean
}) {
  const [fokus, setFokus] = React.useState(false)
  const [teks, setTeks] = React.useState("")
  return (
    <div className="relative">
      <span
        aria-hidden="true"
        className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
      >
        Rp
      </span>
      <Input
        id={id}
        inputMode="numeric"
        autoComplete="off"
        className="pl-9 tabular-nums"
        value={fokus ? teks : nilai ? angka(nilai) : ""}
        placeholder="0"
        aria-describedby={describedBy}
        aria-invalid={invalid || undefined}
        onFocus={(e) => {
          setFokus(true)
          setTeks(nilai ? String(nilai) : "")
          const el = e.currentTarget
          requestAnimationFrame(() => el.select())
        }}
        onBlur={() => setFokus(false)}
        onChange={(e) => {
          const bersih = e.target.value.replace(/\D/g, "").replace(/^0+(?=\d)/, "").slice(0, 13)
          setTeks(bersih)
          ubah(bersih ? Number(bersih) : 0)
        }}
      />
    </div>
  )
}
