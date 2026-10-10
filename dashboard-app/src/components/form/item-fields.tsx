import type { Control, FieldPath, FieldValues } from "react-hook-form"

import { FieldTeks } from "@/components/form/fields"
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { cn } from "@/lib/utils"

/** Isian kecil berlabel di dalam item daftar */
export function IsianItem<T extends FieldValues>({
  control,
  name,
  label,
  placeholder,
  area,
  className,
  baris = 2,
}: {
  control: Control<T>
  name: FieldPath<T>
  label: string
  placeholder?: string
  area?: boolean
  className?: string
  baris?: number
}) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => (
        <FormItem className={cn("gap-1.5", className)}>
          <FormLabel className="text-xs text-muted-foreground">{label}</FormLabel>
          <FormControl>
            {area ? (
              <Textarea {...field} value={field.value ?? ""} rows={baris} placeholder={placeholder} className="min-h-0" />
            ) : (
              <Input {...field} value={field.value ?? ""} placeholder={placeholder} />
            )}
          </FormControl>
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

/** Judul dua nada ala portal: kalimat pertama tinta, lanjutan abu-abu */
export function FieldJudul2Nada<T extends FieldValues>({
  control,
  judul,
  lanjutan,
  nilaiJudul,
  nilaiLanjutan,
}: {
  control: Control<T>
  judul: FieldPath<T>
  lanjutan: FieldPath<T>
  nilaiJudul?: string
  nilaiLanjutan?: string
}) {
  return (
    <div className="grid gap-3">
      <div className="grid gap-5 sm:grid-cols-2">
        <FieldTeks control={control} name={judul} label="Judul (nada utama)" wajib />
        <FieldTeks control={control} name={lanjutan} label="Lanjutan (nada abu-abu)" />
      </div>
      {(nilaiJudul || nilaiLanjutan) && (
        <p className="rounded-md bg-muted/50 px-3 py-2 font-display text-xl leading-tight font-semibold tracking-tight">
          <span className="sr-only">Pratinjau judul: </span>
          {nilaiJudul} <span className="text-muted-foreground">{nilaiLanjutan}</span>
        </p>
      )}
    </div>
  )
}
