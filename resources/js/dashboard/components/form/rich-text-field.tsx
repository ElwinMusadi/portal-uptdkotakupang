import * as React from "react"
import type { FieldValues } from "react-hook-form"

import type { PropsDasar } from "@/components/form/fields"
import {
  FormDescription,
  FormField,
  FormItem,
  FormMessage,
  useFormField,
} from "@/components/ui/form"
import { Label } from "@/components/ui/label"
import { Skeleton } from "@/components/ui/skeleton"

const EditorKaya = React.lazy(() => import("@/components/form/rich-text-editor"))

function IsiEditor({
  value,
  onChange,
  onBlur,
  label,
  placeholder,
}: {
  value: string
  onChange: (v: string) => void
  onBlur: () => void
  label: string
  placeholder?: string
}) {
  const { formItemId, formDescriptionId, formMessageId, error } = useFormField()
  return (
    <React.Suspense fallback={<Skeleton className="h-72 w-full rounded-md" />}>
      <EditorKaya
        id={formItemId}
        value={value}
        onChange={onChange}
        onBlur={onBlur}
        label={label}
        placeholder={placeholder}
        invalid={!!error}
        describedBy={error ? `${formDescriptionId} ${formMessageId}` : formDescriptionId}
      />
    </React.Suspense>
  )
}

/** Isi kaya-teks (paragraf, subjudul, daftar, kutipan, tautan) disimpan sebagai HTML */
export function FieldKaya<T extends FieldValues>({
  control,
  name,
  label,
  deskripsi,
  className,
  wajib,
  placeholder,
}: PropsDasar<T> & { label: string; placeholder?: string }) {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field }) => (
        <FormItem className={className}>
          <Label asChild>
            <span>
              {label}
              {wajib && (
                <span aria-hidden="true" className="text-destructive">
                  *
                </span>
              )}
            </span>
          </Label>
          <IsiEditor
            value={field.value ?? ""}
            onChange={field.onChange}
            onBlur={field.onBlur}
            label={label}
            placeholder={placeholder}
          />
          <FormDescription>{deskripsi}</FormDescription>
          <FormMessage />
        </FormItem>
      )}
    />
  )
}
