import { IconArrowLeft } from "@tabler/icons-react"
import { Link } from "react-router"

import { Button } from "@/components/ui/button"
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyTitle,
} from "@/components/ui/empty"

export function EntriTidakDitemukan({
  judul = "Data tidak ditemukan",
  deskripsi = "Data ini mungkin sudah dihapus atau tautannya keliru.",
  kembali,
  labelKembali = "Kembali",
}: {
  judul?: string
  deskripsi?: string
  kembali: string
  labelKembali?: string
}) {
  return (
    <Empty className="m-4 border lg:m-6">
      <EmptyHeader>
        <EmptyTitle>{judul}</EmptyTitle>
        <EmptyDescription>{deskripsi}</EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <Button asChild variant="outline">
          <Link to={kembali}>
            <IconArrowLeft />
            {labelKembali}
          </Link>
        </Button>
      </EmptyContent>
    </Empty>
  )
}
