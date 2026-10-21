import { useId } from "react"

import { cn } from "@/lib/utils"

// DotPattern dari 21st.dev (Magic UI): satu titik di (cx, cy) berjari-jari cr tiap petak width × height,
// memenuhi induk yang relative. Warna titik lewat kelas fill-* atau style fill. Motif "titik" portal (D-073)
// dibuat server dengan petak dan titik yang sama.
interface DotPatternProps extends Omit<React.ComponentProps<"svg">, "width" | "height" | "x" | "y" | "cx" | "cy"> {
  width?: number
  height?: number
  x?: number
  y?: number
  cx?: number
  cy?: number
  cr?: number
}

function DotPattern({ width = 16, height = 16, x = 0, y = 0, cx = 1, cy = 1, cr = 1, className, ...props }: DotPatternProps) {
  const id = useId()

  return (
    <svg
      aria-hidden="true"
      data-slot="dot-pattern"
      className={cn("pointer-events-none absolute inset-0 h-full w-full fill-neutral-400/80", className)}
      {...props}
    >
      <defs>
        <pattern id={id} width={width} height={height} patternUnits="userSpaceOnUse" patternContentUnits="userSpaceOnUse" x={x} y={y}>
          <circle cx={cx} cy={cy} r={cr} />
        </pattern>
      </defs>
      <rect width="100%" height="100%" strokeWidth={0} fill={`url(#${id})`} />
    </svg>
  )
}

export { DotPattern }
