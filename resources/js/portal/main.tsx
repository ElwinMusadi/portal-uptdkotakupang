/*
 * Komponen React di halaman portal (D-045). Laravel tetap mengirim halaman
 * sebagai HTML lengkap, jadi isinya terbaca sebelum skrip dimuat; halaman
 * memuat berkas ini lewat /skrip/portal (routes/web.php). Setiap elemen
 * [data-react="nama"] diambil alih komponen bernama sama, yang dimuat hanya
 * di halaman yang memakainya. Bagian portal yang masih diisi
 * public/assets/js/main.js dipindahkan ke sini satu per satu.
 */
const KOMPONEN: Record<string, () => Promise<{ pasang: (el: HTMLElement) => void }>> = {
  "judul-pembuka": () => import("./beranda/judul-pembuka"),
  "lambang-pembuka": () => import("./beranda/lambang-pembuka"),
  "laporan-penerimaan": () => import("./penerimaan/laporan-penerimaan"),
  "pemetaan-pkb": () => import("./pemetaan/pemetaan-pkb"),
  "lacak-antrean": () => import("./antrean/lacak-antrean"),
  "layar-antrean": () => import("./antrean/layar-antrean"),
}

for (const el of document.querySelectorAll<HTMLElement>("[data-react]")) {
  const muat = KOMPONEN[el.dataset.react ?? ""]
  if (muat) void muat().then(({ pasang }) => pasang(el))
}
