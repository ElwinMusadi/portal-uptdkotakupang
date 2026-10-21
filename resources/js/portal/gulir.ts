/*
 * Gulir di portal (D-072). main.js memasang window.pintuScrollTo, yang
 * menggulir lewat Lenis bila aktif dan native bila tidak; posisinya
 * memperhitungkan header lengket (scroll-padding-top). Bila main.js belum
 * berjalan, elemen digulir native dengan aturan yang sama.
 */

type Blok = "start" | "nearest"

declare global {
  interface Window {
    pintuScrollTo?: (sasaran: Element | number, opsi?: { block?: Blok }) => void
  }
}

/** Gulir ke elemen; "nearest" hanya menggulir bila elemen belum terlihat utuh. */
export function gulirKe(el: Element, block: Blok = "start"): void {
  if (window.pintuScrollTo) window.pintuScrollTo(el, { block })
  else el.scrollIntoView({ block })
}
