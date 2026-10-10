import type { FieldValues, UseFormReturn } from "react-hook-form"

/**
 * Validasi seluruh formulir sebelum menyimpan; hasilnya true bila lolos.
 *
 * Memakai handleSubmit (bukan trigger) agar react-hook-form menandai formulir
 * sudah pernah dikirim. Setelah itu setiap perubahan langsung divalidasi ulang,
 * termasuk isian tanpa blur seperti pilihan, sakelar, dan foto, sehingga pesan
 * galat hilang begitu isian diperbaiki. Isian pertama yang salah otomatis difokus.
 */
export function validasiFormulir<F extends FieldValues>(form: UseFormReturn<F>): Promise<boolean> {
  return new Promise((selesai) => {
    void form.handleSubmit(
      () => selesai(true),
      () => selesai(false)
    )()
  })
}
