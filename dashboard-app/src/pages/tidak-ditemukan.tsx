import { EntriTidakDitemukan } from "@/components/data/not-found"

export default function TidakDitemukan() {
  return (
    <EntriTidakDitemukan
      judul="Halaman tidak ditemukan"
      deskripsi="Alamat ini tidak ada di dashboard. Pilih menu di samping atau kembali ke Ringkasan."
      kembali="/"
      labelKembali="Kembali ke Ringkasan"
    />
  )
}
