#!/usr/bin/env node
// Membuat resources/js/portal/pemetaan/batas-kecamatan.ts (peta Pemetaan PKB,
// D-052) dari GeoJSON batas enam kecamatan Kota Kupang. Sumbernya layanan
// Batas_Administrasi di geoportal Kementerian Pertanian (geodatabase batas
// desa/kelurahan edisi Mei 2021); unduh dengan:
//
//   curl -G "https://geoportal.pertanian.go.id/arcgis/rest/services/Batas_Administrasi/MapServer/0/query" \
//     --data-urlencode "where=WADMKC IN ('Alak','Kelapa Lima','Kota Lama','Kota Raja','Maulafa','Oebobo')" \
//     --data-urlencode "geometry=123.3,-10.4,123.9,-10.0" --data-urlencode "geometryType=esriGeometryEnvelope" \
//     --data-urlencode "inSR=4326" --data-urlencode "outFields=WADMKC" --data-urlencode "outSR=4326" \
//     --data-urlencode "f=geojson" -o kecamatan-kupang.geojson
//
// Koordinat diproyeksikan sederhana (garis bujur dikali cos lintang tengah;
// cukup untuk wilayah ±20 km) ke kotak selebar 400 dengan utara di atas, dan
// setiap kecamatan mendapat titik label di bagian terlebarnya.
//
// Pemakaian:  node scripts/batas-kecamatan.mjs <berkas.geojson>
import { readFileSync, writeFileSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const target = join(root, "resources", "js", "portal", "pemetaan", "batas-kecamatan.ts");
const source = process.argv[2];
if (!source) {
  console.error("Pemakaian: node scripts/batas-kecamatan.mjs <berkas.geojson>");
  process.exit(1);
}

const KECAMATAN = ["ALAK", "KELAPA LIMA", "KOTA LAMA", "KOTA RAJA", "MAULAFA", "OEBOBO"];
const LEBAR = 400;
const TEPI = 8;
// Ruang di atas peta untuk tulisan laut
const ATAS = 24;
// Kecamatan yang terlalu sempit untuk tulisannya diberi label di laut (koordinat
// peta), dengan garis ke titik labelnya
const LABEL_LUAR = { "KOTA LAMA": [165, 52] };
// Letak tulisan Teluk Kupang (koordinat peta)
const LAUT = [62, 48];

const geojson = JSON.parse(readFileSync(source, "utf8"));
const fitur = geojson.features.map((f) => ({
  kunci: String(f.properties.WADMKC).toUpperCase(),
  cincin: (f.geometry.type === "Polygon" ? [f.geometry.coordinates] : f.geometry.coordinates).flat(1).map((r) => r.map(([x, y]) => [x, y])),
}));
const hilang = KECAMATAN.filter((k) => !fitur.some((f) => f.kunci === k));
if (hilang.length || fitur.length !== KECAMATAN.length) {
  console.error(`Berkas harus berisi tepat enam kecamatan Kota Kupang; tidak ada: ${hilang.join(", ") || "-"}`);
  process.exit(1);
}

const titik = fitur.flatMap((f) => f.cincin.flat());
const lintangTengah = (Math.min(...titik.map((p) => p[1])) + Math.max(...titik.map((p) => p[1]))) / 2;
const k = Math.cos((lintangTengah * Math.PI) / 180);
const minX = Math.min(...titik.map((p) => p[0] * k));
const maxX = Math.max(...titik.map((p) => p[0] * k));
const maxY = Math.max(...titik.map((p) => p[1]));
const minY = Math.min(...titik.map((p) => p[1]));
const skala = (LEBAR - TEPI * 2) / (maxX - minX);
const tinggi = Math.ceil((maxY - minY) * skala + TEPI + ATAS);
const proyeksi = ([x, y]) => [(x * k - minX) * skala + TEPI, (maxY - y) * skala + ATAS];
const bulat = (n) => Math.round(n * 10) / 10;

/** Jarak titik ke ruas garis */
function jarakRuas([px, py], [ax, ay], [bx, by]) {
  const dx = bx - ax;
  const dy = by - ay;
  const t = dx || dy ? Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy))) : 0;
  return Math.hypot(px - (ax + t * dx), py - (ay + t * dy));
}

function didalam([px, py], cincin) {
  let masuk = false;
  for (const r of cincin) {
    for (let i = 0, j = r.length - 1; i < r.length; j = i++) {
      const [xi, yi] = r[i];
      const [xj, yj] = r[j];
      if (yi > py !== yj > py && px < ((xj - xi) * (py - yi)) / (yj - yi) + xi) masuk = !masuk;
    }
  }
  return masuk;
}

/** Titik di dalam wilayah yang paling jauh dari tepinya (pencarian kisi bertingkat) */
function titikLabel(cincin) {
  const semua = cincin.flat();
  let [x0, y0, x1, y1] = [Math.min(...semua.map((p) => p[0])), Math.min(...semua.map((p) => p[1])), Math.max(...semua.map((p) => p[0])), Math.max(...semua.map((p) => p[1]))];
  let terbaik = null;
  let jarakTerbaik = -1;
  for (let putaran = 0; putaran < 4; putaran++) {
    const langkah = Math.max(x1 - x0, y1 - y0) / 40;
    for (let x = x0; x <= x1; x += langkah) {
      for (let y = y0; y <= y1; y += langkah) {
        if (!didalam([x, y], cincin)) continue;
        let jarak = Infinity;
        for (const r of cincin) for (let i = 0; i < r.length - 1; i++) jarak = Math.min(jarak, jarakRuas([x, y], r[i], r[i + 1]));
        if (jarak > jarakTerbaik) {
          jarakTerbaik = jarak;
          terbaik = [x, y];
        }
      }
    }
    const lebar = (x1 - x0) / 4;
    const tinggiCari = (y1 - y0) / 4;
    [x0, x1, y0, y1] = [terbaik[0] - lebar, terbaik[0] + lebar, terbaik[1] - tinggiCari, terbaik[1] + tinggiCari];
  }
  return { titik: terbaik, ruang: jarakTerbaik };
}

const baris = KECAMATAN.map((kunci) => {
  const f = fitur.find((x) => x.kunci === kunci);
  const cincin = f.cincin.map((r) => r.map(proyeksi));
  const jalur = cincin
    .map((r) => {
      const t = r.slice(0, r.length - 1).map((p) => p.map(bulat));
      return `M${t.map((p) => p.join(" ")).join("L")}Z`;
    })
    .join("");
  const { titik: [lx, ly], ruang } = titikLabel(cincin);
  console.log(`${kunci}: label ${bulat(lx)},${bulat(ly)}, ruang ${bulat(ruang)}`);
  const luar = LABEL_LUAR[kunci] ? `, luar: [${LABEL_LUAR[kunci].join(", ")}]` : "";
  return `  ${JSON.stringify(kunci)}: { jalur: ${JSON.stringify(jalur)}, label: [${bulat(lx)}, ${bulat(ly)}]${luar} },`;
});

const isi = `/*
 * Batas enam kecamatan Kota Kupang untuk peta Pemetaan PKB di portal dan
 * dashboard (D-052). Sumber: geodatabase batas desa/kelurahan edisi Mei 2021
 * (BIG bersama pemerintah daerah), layanan Batas_Administrasi geoportal
 * Kementerian Pertanian. Batasnya sudah disederhanakan dan belum mengikuti
 * batas kota yang definitif. Dibuat oleh \`node scripts/batas-kecamatan.mjs\`;
 * jangan diubah dengan tangan.
 */

/** Ukuran kotak peta (utara di atas) dan letak tulisan Teluk Kupang */
export const UKURAN_PETA = { lebar: ${LEBAR}, tinggi: ${tinggi}, laut: [${LAUT.join(", ")}] } as const

/**
 * Per kecamatan: jalur SVG dan titik label di bagian terlebarnya; \`luar\`
 * untuk kecamatan yang terlalu sempit, tulisannya di laut dengan garis ke
 * titik label
 */
export const BATAS_KECAMATAN: Record<string, { jalur: string; label: [number, number]; luar?: [number, number] }> = {
${baris.join("\n")}
}
`;
writeFileSync(target, isi);
console.log(`ditulis ${target} (${isi.length} byte, tinggi ${tinggi})`);
