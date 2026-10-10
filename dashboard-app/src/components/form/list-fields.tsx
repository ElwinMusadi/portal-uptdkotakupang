import * as React from "react"
import {
  closestCenter,
  DndContext,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core"
import { restrictToVerticalAxis } from "@dnd-kit/modifiers"
import {
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { IconGripVertical, IconPlus, IconTrash, IconX } from "@tabler/icons-react"
import {
  useFieldArray,
  type ArrayPath,
  type FieldArray,
  type FieldValues,
} from "react-hook-form"

import type { PropsDasar } from "@/components/form/fields"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { cn } from "@/lib/utils"

/** Label/tag bebas: ketik lalu Enter */
export function FieldLabel<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  placeholder = "Ketik lalu tekan Enter",
}: PropsDasar<T> & { placeholder?: string }) {
  const [draf, setDraf] = React.useState("")
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const nilai: string[] = Array.isArray(field.value) ? field.value : []
        const tambah = () => {
          const t = draf.trim()
          if (t && !nilai.includes(t)) field.onChange([...nilai, t])
          setDraf("")
        }
        return (
          <FormItem className={className}>
            <FormLabel>{label}</FormLabel>
            {nilai.length > 0 && (
              <ul className="flex flex-wrap gap-1.5" aria-label="Label terpasang">
                {nilai.map((t) => (
                  <li key={t}>
                    <Badge variant="secondary" className="gap-1 pr-1">
                      {t}
                      <button
                        type="button"
                        className="rounded-sm p-0.5 hover:bg-foreground/10 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                        onClick={() => field.onChange(nilai.filter((x) => x !== t))}
                      >
                        <IconX className="size-3" />
                        <span className="sr-only">Hapus label {t}</span>
                      </button>
                    </Badge>
                  </li>
                ))}
              </ul>
            )}
            <div className="flex gap-2">
              <FormControl>
                <Input
                  value={draf}
                  ref={field.ref}
                  onChange={(e) => setDraf(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === "Enter") {
                      e.preventDefault()
                      tambah()
                    }
                  }}
                  onBlur={field.onBlur}
                  placeholder={placeholder}
                />
              </FormControl>
              <Button type="button" variant="outline" onClick={tambah} disabled={!draf.trim()}>
                Tambah
              </Button>
            </div>
            {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

function GagangSortable({ id, label }: { id: string; label: string }) {
  const { attributes, listeners } = useSortable({ id })
  return (
    <Button
      type="button"
      variant="ghost"
      size="icon"
      className="size-8 shrink-0 cursor-grab text-muted-foreground active:cursor-grabbing"
      {...attributes}
      {...listeners}
    >
      <IconGripVertical className="size-4" />
      <span className="sr-only">Seret untuk memindahkan {label}</span>
    </Button>
  )
}

function ItemSortable({
  id,
  children,
  className,
}: {
  id: string
  children: React.ReactNode
  className?: string
}) {
  const { setNodeRef, transform, transition, isDragging } = useSortable({ id })
  return (
    <li
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      className={cn("relative", isDragging && "z-10 opacity-80", className)}
    >
      {children}
    </li>
  )
}

/**
 * Daftar objek yang bisa ditambah, dihapus, dan diurutkan dengan seret.
 * Setiap item dirender oleh `render(index)`.
 */
export function DaftarObjek<T extends FieldValues, N extends ArrayPath<T>>({
  control,
  name,
  label,
  deskripsi,
  itemBaru,
  render,
  labelItem,
  labelTambah = "Tambah",
  maks,
  className,
  ringkas,
}: {
  control: PropsDasar<T>["control"]
  name: N
  label: React.ReactNode
  deskripsi?: React.ReactNode
  itemBaru: () => FieldArray<T, N>
  render: (index: number) => React.ReactNode
  labelItem: (index: number) => string
  labelTambah?: string
  maks?: number
  className?: string
  /** Tata letak satu baris (untuk item dengan 1–2 kolom pendek) */
  ringkas?: boolean
}) {
  const { fields, append, remove, move } = useFieldArray({ control, name, keyName: "_kunci" })
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  )
  const dndId = React.useId()
  const kunci = fields.map((f) => (f as unknown as { _kunci: string })._kunci)

  const onDragEnd = (e: DragEndEvent) => {
    if (!e.over || e.active.id === e.over.id) return
    move(kunci.indexOf(String(e.active.id)), kunci.indexOf(String(e.over.id)))
  }

  return (
    <fieldset className={cn("grid gap-3", className)}>
      <legend className="mb-1 text-sm leading-none font-medium">{label}</legend>
      {deskripsi && <p className="-mt-1 text-sm text-muted-foreground">{deskripsi}</p>}
      <DndContext
        id={dndId}
        sensors={sensors}
        collisionDetection={closestCenter}
        modifiers={[restrictToVerticalAxis]}
        onDragEnd={onDragEnd}
      >
        <SortableContext items={kunci} strategy={verticalListSortingStrategy}>
          <ol className="grid gap-2">
            {fields.map((_, i) => {
              const k = kunci[i]
              return (
                <ItemSortable key={k} id={k}>
                  <div
                      className={cn(
                        "flex gap-2 rounded-lg border bg-card p-2",
                        ringkas ? "items-start" : "items-start sm:p-3"
                      )}
                    >
                      <GagangSortable id={k} label={labelItem(i)} />
                      <div className="grid min-w-0 flex-1 gap-3">{render(i)}</div>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8 shrink-0 text-muted-foreground hover:text-destructive"
                        onClick={() => remove(i)}
                      >
                        <IconTrash className="size-4" />
                        <span className="sr-only">Hapus {labelItem(i)}</span>
                      </Button>
                    </div>
                </ItemSortable>
              )
            })}
          </ol>
        </SortableContext>
      </DndContext>
      {fields.length === 0 && (
        <p className="rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
          Belum ada isi.
        </p>
      )}
      <div>
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={maks !== undefined && fields.length >= maks}
          onClick={() => append(itemBaru())}
        >
          <IconPlus />
          {labelTambah}
        </Button>
      </div>
    </fieldset>
  )
}

/** Daftar teks sederhana (string[]) yang bisa diurutkan */
export function FieldDaftarTeks<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  placeholder,
  labelTambah = "Tambah baris",
}: PropsDasar<T> & { placeholder?: string; labelTambah?: string }) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const nilai: string[] = Array.isArray(field.value) ? field.value : []
        return (
          <DaftarTeksDasar
            label={label}
            deskripsi={deskripsi}
            className={className}
            nilai={nilai}
            onChange={field.onChange}
            placeholder={placeholder}
            labelTambah={labelTambah}
          />
        )
      }}
    />
  )
}

function DaftarTeksDasar({
  label,
  deskripsi,
  className,
  nilai,
  onChange,
  placeholder,
  labelTambah,
}: {
  label: React.ReactNode
  deskripsi?: React.ReactNode
  className?: string
  nilai: string[]
  onChange: (v: string[]) => void
  placeholder?: string
  labelTambah: string
}) {
  // kunci stabil per baris agar fokus tidak hilang saat mengetik/mengurutkan
  const [kunci, setKunci] = React.useState(() => nilai.map((_, i) => `b${i}`))
  const hitung = React.useRef(nilai.length)
  React.useEffect(() => {
    if (kunci.length !== nilai.length) {
      setKunci(nilai.map((_, i) => kunci[i] ?? `b${hitung.current++}`))
    }
  }, [nilai, kunci])
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  )
  const dndId = React.useId()
  const ubah = (i: number, v: string) => onChange(nilai.map((x, j) => (j === i ? v : x)))
  const hapus = (i: number) => {
    setKunci((k) => k.filter((_, j) => j !== i))
    onChange(nilai.filter((_, j) => j !== i))
  }
  const tambah = () => {
    setKunci((k) => [...k, `b${hitung.current++}`])
    onChange([...nilai, ""])
  }
  const onDragEnd = (e: DragEndEvent) => {
    if (!e.over || e.active.id === e.over.id) return
    const dari = kunci.indexOf(String(e.active.id))
    const ke = kunci.indexOf(String(e.over.id))
    const pindah = <X,>(arr: X[]) => {
      const salin = arr.slice()
      const [x] = salin.splice(dari, 1)
      salin.splice(ke, 0, x)
      return salin
    }
    setKunci(pindah(kunci))
    onChange(pindah(nilai))
  }

  return (
    <FormItem className={className}>
      <FormLabel asChild>
        <span>{label}</span>
      </FormLabel>
      {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
      <DndContext
        id={dndId}
        sensors={sensors}
        collisionDetection={closestCenter}
        modifiers={[restrictToVerticalAxis]}
        onDragEnd={onDragEnd}
      >
        <SortableContext items={kunci.slice(0, nilai.length)} strategy={verticalListSortingStrategy}>
          <ol className="grid gap-2">
            {nilai.map((v, i) => (
              <ItemSortable key={kunci[i] ?? i} id={kunci[i] ?? String(i)}>
                  <div className="flex items-center gap-1">
                    <GagangSortable id={kunci[i] ?? String(i)} label={`baris ${i + 1}`} />
                    <Input
                      value={v}
                      placeholder={placeholder}
                      aria-label={`Baris ${i + 1}`}
                      onChange={(e) => ubah(i, e.target.value)}
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="size-8 shrink-0 text-muted-foreground hover:text-destructive"
                      onClick={() => hapus(i)}
                    >
                      <IconTrash className="size-4" />
                      <span className="sr-only">Hapus baris {i + 1}</span>
                    </Button>
                  </div>
              </ItemSortable>
            ))}
          </ol>
        </SortableContext>
      </DndContext>
      <div>
        <Button type="button" variant="outline" size="sm" onClick={tambah}>
          <IconPlus />
          {labelTambah}
        </Button>
      </div>
      <FormMessage />
    </FormItem>
  )
}
