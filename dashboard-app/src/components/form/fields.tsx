import * as React from "react"
import { IconCalendar, IconX } from "@tabler/icons-react"
import { id as localeId } from "react-day-picker/locale"
import type { Control, FieldPath, FieldValues } from "react-hook-form"

import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Switch } from "@/components/ui/switch"
import { Textarea } from "@/components/ui/textarea"
import { tanggal } from "@/lib/format"
import { cn } from "@/lib/utils"

export interface PropsDasar<T extends FieldValues> {
  control: Control<T>
  name: FieldPath<T>
  label: React.ReactNode
  deskripsi?: React.ReactNode
  className?: string
  wajib?: boolean
}

function Label({ label, wajib }: { label: React.ReactNode; wajib?: boolean }) {
  return (
    <FormLabel>
      {label}
      {wajib && (
        <span aria-hidden="true" className="text-destructive">
          *
        </span>
      )}
    </FormLabel>
  )
}

export function FieldTeks<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  wajib,
  ...input
}: PropsDasar<T> & Omit<React.ComponentProps<typeof Input>, "name" | "value" | "onChange" | "defaultValue">) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => (
        <FormItem className={className}>
          <Label label={label} wajib={wajib} />
          <FormControl>
            <Input
              {...input}
              {...field}
              value={field.value ?? ""}
              aria-required={wajib || undefined}
            />
          </FormControl>
          {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

export function FieldArea<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  wajib,
  maks,
  ...area
}: PropsDasar<T> & { maks?: number } & Omit<
    React.ComponentProps<typeof Textarea>,
    "name" | "value" | "onChange" | "defaultValue"
  >) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const panjang = String(field.value ?? "").length
        return (
          <FormItem className={className}>
            <Label label={label} wajib={wajib} />
            <FormControl>
              <Textarea
                {...area}
                {...field}
                value={field.value ?? ""}
                aria-required={wajib || undefined}
              />
            </FormControl>
            {(deskripsi || maks) && (
              <FormDescription className="flex justify-between gap-4">
                <span>{deskripsi}</span>
                {maks && (
                  <span className={cn("tabular-nums", panjang > maks && "text-destructive")}>
                    {panjang}/{maks}
                  </span>
                )}
              </FormDescription>
            )}
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

export function FieldAngka<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  wajib,
  satuan,
  ...input
}: PropsDasar<T> & { satuan?: string } & Omit<
    React.ComponentProps<typeof Input>,
    "name" | "value" | "onChange" | "defaultValue" | "type"
  >) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => (
        <FormItem className={className}>
          <Label label={label} wajib={wajib} />
          <div className="relative">
            <FormControl>
              <Input
                {...input}
                type="number"
                inputMode="decimal"
                name={field.name}
                ref={field.ref}
                onBlur={field.onBlur}
                value={Number.isFinite(field.value) ? field.value : ""}
                onChange={(e) =>
                  field.onChange(e.target.value === "" ? Number.NaN : e.target.valueAsNumber)
                }
                className={cn("tabular-nums", satuan && "pr-14")}
              />
            </FormControl>
            {satuan && (
              <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm text-muted-foreground">
                {satuan}
              </span>
            )}
          </div>
          {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

export function FieldPilih<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  wajib,
  opsi,
  placeholder = "Pilih…",
  angka,
}: PropsDasar<T> & {
  opsi: { nilai: string; label: string }[]
  placeholder?: string
  /** Simpan nilai sebagai angka (mis. hari 0–6) */
  angka?: boolean
}) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => (
        <FormItem className={className}>
          <Label label={label} wajib={wajib} />
          <Select
            onValueChange={(v) => field.onChange(angka ? Number(v) : v)}
            value={field.value == null ? "" : String(field.value)}
          >
            <FormControl>
              <SelectTrigger className="w-full" onBlur={field.onBlur} ref={field.ref}>
                <SelectValue placeholder={placeholder} />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              {opsi.map((o) => (
                <SelectItem key={o.nilai} value={o.nilai}>
                  {o.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
          <FormMessage />
        </FormItem>
      )}
    />
  )
}

export function FieldSakelar<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
}: PropsDasar<T>) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => (
        <FormItem
          className={cn(
            "flex flex-row items-center justify-between gap-4 rounded-lg border p-3 shadow-xs",
            className
          )}
        >
          <div className="grid gap-1">
            <FormLabel>{label}</FormLabel>
            {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
          </div>
          <FormControl>
            <Switch checked={!!field.value} onCheckedChange={field.onChange} ref={field.ref} />
          </FormControl>
        </FormItem>
      )}
    />
  )
}

function keDate(nilai: string | undefined | null) {
  if (!nilai || !/^\d{4}-\d{2}-\d{2}$/.test(nilai)) return undefined
  const [y, m, d] = nilai.split("-").map(Number)
  return new Date(y, m - 1, d)
}

function dariDate(d: Date) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`
}

export function FieldTanggal<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  wajib,
  bisaKosong,
}: PropsDasar<T> & { bisaKosong?: boolean }) {
  const [buka, setBuka] = React.useState(false)
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => {
        const terpilih = keDate(field.value)
        return (
          <FormItem className={className}>
            <Label label={label} wajib={wajib} />
            <div className="flex gap-2">
              <Popover open={buka} onOpenChange={setBuka}>
                <PopoverTrigger asChild>
                  <FormControl>
                    <Button
                      variant="outline"
                      ref={field.ref}
                      className={cn(
                        "flex-1 justify-start font-normal",
                        !terpilih && "text-muted-foreground"
                      )}
                    >
                      <IconCalendar />
                      {terpilih ? tanggal(field.value, "lengkap") : "Pilih tanggal"}
                    </Button>
                  </FormControl>
                </PopoverTrigger>
                <PopoverContent className="w-auto overflow-hidden p-0" align="start">
                  <Calendar
                    mode="single"
                    locale={localeId}
                    selected={terpilih}
                    defaultMonth={terpilih}
                    captionLayout="dropdown"
                    formatters={{
                      formatMonthDropdown: (d) => d.toLocaleString("id-ID", { month: "short" }),
                    }}
                    startMonth={new Date(2020, 0)}
                    endMonth={new Date(2035, 11)}
                    onSelect={(d) => {
                      if (d) field.onChange(dariDate(d))
                      setBuka(false)
                    }}
                  />
                </PopoverContent>
              </Popover>
              {bisaKosong && terpilih && (
                <Button variant="ghost" size="icon" onClick={() => field.onChange("")}>
                  <IconX />
                  <span className="sr-only">Kosongkan tanggal</span>
                </Button>
              )}
            </div>
            {deskripsi && <FormDescription>{deskripsi}</FormDescription>}
            <FormMessage />
          </FormItem>
        )
      }}
    />
  )
}

export function FieldJam<T extends FieldValues>(props: PropsDasar<T> & { disabled?: boolean }) {
  return <FieldTeks {...props} type="time" step={300} className={cn("min-w-28", props.className)} />
}
