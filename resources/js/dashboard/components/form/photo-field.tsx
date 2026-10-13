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
import {
  rectSortingStrategy,
  SortableContext,
  sortableKeyboardCoordinates,
  useSortable,
} from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { IconGripVertical, IconPhotoPlus, IconTrash } from "@tabler/icons-react"
import {
  Controller,
  useFieldArray,
  type ArrayPath,
  type Control,
  type FieldArray,
  type FieldPath,
  type FieldValues,
} from "react-hook-form"
import { toast } from "sonner"

import { PlaceholderFoto, unggahGambar } from "@/components/form/media-fields"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import type { Foto } from "@/lib/api"
import { TIPE_GAMBAR } from "@/lib/gambar"
import { newId } from "@/lib/id"
import { cn } from "@/lib/utils"

function KartuFoto({
  id,
  foto,
  nomor,
  keterangan,
  onHapus,
}: {
  id: string
  foto: Foto
  nomor: number
  keterangan: React.ReactNode
  onHapus: () => void
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id })
  return (
    <li
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      className={cn("grid gap-2 rounded-lg border bg-card p-2", isDragging && "z-10 opacity-80 shadow-lg")}
    >
      <div className="relative aspect-[4/3] overflow-hidden rounded-md bg-muted">
        {foto.src ? (
          <img src={foto.src} alt={foto.keterangan || `Foto ${nomor}`} className="size-full object-cover" />
        ) : (
          <PlaceholderFoto label="Foto belum diunggah" />
        )}
        {nomor === 1 && (
          <span className="absolute top-1.5 left-1.5 rounded bg-background/90 px-1.5 py-0.5 text-[11px] font-medium">
            Sampul
          </span>
        )}
      </div>
      {keterangan}
      <div className="flex items-center justify-between">
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="size-7 cursor-grab text-muted-foreground active:cursor-grabbing"
          {...attributes}
          {...listeners}
        >
          <IconGripVertical className="size-4" />
          <span className="sr-only">Seret untuk memindahkan foto {nomor}</span>
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="size-7 text-muted-foreground hover:text-destructive"
          onClick={onHapus}
        >
          <IconTrash className="size-4" />
          <span className="sr-only">Hapus foto {nomor}</span>
        </Button>
      </div>
    </li>
  )
}

/** Kelola banyak foto: unggah sekaligus, beri keterangan, urutkan dengan seret */
export function FieldFoto<T extends FieldValues, N extends ArrayPath<T>>({
  control,
  name,
  label = "Foto kegiatan",
}: {
  control: Control<T>
  name: N
  label?: string
}) {
  const { fields, append, remove, move } = useFieldArray({ control, name, keyName: "_kunci" })
  const inputRef = React.useRef<HTMLInputElement>(null)
  const [proses, setProses] = React.useState(0)
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  )
  const dndId = React.useId()
  const kunci = fields.map((f) => (f as unknown as { _kunci: string })._kunci)
  const daftar = fields as unknown as Foto[]

  const unggah = async (berkas: FileList | null) => {
    if (!berkas?.length) return
    const semua = Array.from(berkas)
    setProses((n) => n + semua.length)
    for (const b of semua) {
      try {
        const src = await unggahGambar(b)
        append({ id: newId(), src, keterangan: "" } as unknown as FieldArray<T, N>)
      } catch (e) {
        toast.error(`${b.name} gagal diunggah`, { description: (e as Error).message })
      } finally {
        setProses((n) => n - 1)
      }
    }
    if (inputRef.current) inputRef.current.value = ""
  }

  const onDragEnd = (e: DragEndEvent) => {
    if (!e.over || e.active.id === e.over.id) return
    move(kunci.indexOf(String(e.active.id)), kunci.indexOf(String(e.over.id)))
  }

  return (
    <fieldset className="grid gap-3">
      <legend className="mb-1 text-sm font-medium">{label}</legend>
      <p className="-mt-1 text-sm text-muted-foreground">
        Foto pertama menjadi sampul. Seret untuk mengubah urutan. Foto diperkecil otomatis.
      </p>
      <DndContext id={dndId} sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
        <SortableContext items={kunci} strategy={rectSortingStrategy}>
          <ol className="grid grid-cols-2 gap-2 sm:grid-cols-3">
            {daftar.map((foto, i) => (
              <KartuFoto
                key={kunci[i]}
                id={kunci[i]}
                foto={foto}
                nomor={i + 1}
                keterangan={
                  <Controller
                    control={control}
                    name={`${name}.${i}.keterangan` as FieldPath<T>}
                    render={({ field }) => (
                      <Input
                        {...field}
                        value={field.value ?? ""}
                        placeholder="Keterangan foto"
                        aria-label={`Keterangan foto ${i + 1}`}
                        className="h-8 text-sm"
                      />
                    )}
                  />
                }
                onHapus={() => remove(i)}
              />
            ))}
            <li>
              <button
                type="button"
                onClick={() => inputRef.current?.click()}
                onDragOver={(e) => e.preventDefault()}
                onDrop={(e) => {
                  e.preventDefault()
                  void unggah(e.dataTransfer.files)
                }}
                className="flex aspect-[4/3] w-full flex-col items-center justify-center gap-1 rounded-lg border border-dashed text-sm text-muted-foreground transition-colors hover:bg-muted/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
              >
                {proses > 0 ? <Spinner /> : <IconPhotoPlus className="size-6" aria-hidden="true" />}
                <span className="font-medium text-foreground">
                  {proses > 0 ? `Mengunggah ${proses} foto…` : "Tambah foto"}
                </span>
                <span className="text-xs">Bisa pilih beberapa sekaligus</span>
              </button>
            </li>
          </ol>
        </SortableContext>
      </DndContext>
      <input
        ref={inputRef}
        type="file"
        multiple
        accept={TIPE_GAMBAR.join(",")}
        className="sr-only"
        tabIndex={-1}
        aria-label="Pilih foto kegiatan"
        onChange={(e) => void unggah(e.target.files)}
      />
    </fieldset>
  )
}
