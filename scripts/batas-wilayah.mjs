#!/usr/bin/env node
// Membuat resources/js/portal/pemetaan/batas-wilayah.ts (peta Pemetaan PKB,
// D-052) dari shapefile Batas Desa/Kelurahan BIG skala 1:10.000 edisi 2022
// (lapisan ADMINISTRASI_AR_DESAKEL, WGS 1984). Hanya kelurahan Kota Kupang
// yang dipakai; batas kecamatan dirangkai dari batas kelurahannya.
//
// Kelurahan bertetangga memakai titik batas yang sama. Batas dipecah menjadi
// ruas di antara titik pertemuan, setiap ruas disederhanakan sekali
// (Douglas-Peucker), lalu kelurahan dan kecamatan dirangkai ulang dari ruas
// yang sama, jadi garis batas tetangga tetap berimpit tanpa celah. Koordinat
// diproyeksikan sederhana (bujur dikali cos lintang tengah) ke kotak selebar
// 400 dengan utara di atas.
//
// Pemakaian:  node scripts/batas-wilayah.mjs <folder>/ADMINISTRASI_AR_DESAKEL
import { readFileSync, writeFileSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const target = join(root, "resources", "js", "portal", "pemetaan", "batas-wilayah.ts");
const source = process.argv[2];
if (!source) {
  console.error("Pemakaian: node scripts/batas-wilayah.mjs <folder>/ADMINISTRASI_AR_DESAKEL");
  process.exit(1);
}

const LEBAR = 400;
const TEPI = 8;
// Ruang di atas peta untuk tulisan laut
const ATAS = 24;
// Toleransi penyederhanaan dalam satuan peta (±4 m per 0,1)
const TOLERANSI = 0.25;
// Nama di berkas BIG yang ditulis lain dalam daftar resmi aplikasi (App\Enums\Kecamatan)
const NAMA = {
  "BAKUNASE DUA": "BAKUNASE II",
  "NAIKOTEN SATU": "NAIKOTEN I",
  "NAIKOTEN DUA": "NAIKOTEN II",
  "BATU PLAT": "BATUPLAT",
  "LAI LAI BISI KOPAN": "LAI-LAI BESI KOPAN",
};
// Kecamatan yang terlalu sempit untuk tulisannya diberi label di laut (koordinat peta)
const LABEL_LUAR = { "KOTA LAMA": [160, 52] };
// Letak tulisan Teluk Kupang (koordinat peta)
const LAUT = [62, 48];

// ---------- Membaca shapefile ----------

function bacaDbf(path) {
  const data = readFileSync(`${path}.dbf`);
  const jumlah = data.readUInt32LE(4);
  const panjangKepala = data.readUInt16LE(8);
  const panjangBaris = data.readUInt16LE(10);
  const kolom = [];
  for (let pos = 32; data[pos] !== 0x0d; pos += 32) {
    kolom.push({ nama: data.toString("latin1", pos, pos + 11).replace(/\0.*$/, ""), panjang: data[pos + 16] });
  }
  const baris = [];
  for (let i = 0; i < jumlah; i++) {
    const awal = panjangKepala + i * panjangBaris;
    let p = awal + 1;
    const isi = {};
    for (const k of kolom) {
      isi[k.nama] = data.toString("utf8", p, p + k.panjang).trim();
      p += k.panjang;
    }
    baris.push(data[awal] === 0x2a ? null : isi);
  }
  return baris;
}

function bacaShp(path) {
  const data = readFileSync(`${path}.shp`);
  const bentuk = [];
  for (let pos = 100; pos < data.length; ) {
    const panjang = data.readInt32BE(pos + 4) * 2;
    const isi = pos + 8;
    pos = isi + panjang;
    if (data.readInt32LE(isi) === 0) {
      bentuk.push([]);
      continue;
    }
    const bagian = data.readInt32LE(isi + 36);
    const titik = data.readInt32LE(isi + 40);
    const awal = [];
    for (let i = 0; i < bagian; i++) awal.push(data.readInt32LE(isi + 44 + 4 * i));
    const xy = isi + 44 + 4 * bagian;
    const cincin = [];
    for (let i = 0; i < bagian; i++) {
      const dari = awal[i];
      const sampai = i + 1 < bagian ? awal[i + 1] : titik;
      const r = [];
      for (let j = dari; j < sampai; j++) r.push([data.readDoubleLE(xy + 16 * j), data.readDoubleLE(xy + 16 * j + 8)]);
      cincin.push(r);
    }
    bentuk.push(cincin);
  }
  return bentuk;
}

const atribut = bacaDbf(source);
const bentuk = bacaShp(source);
const kelurahan = [];
atribut.forEach((a, i) => {
  if (!a || a.WADMKK !== "Kota Kupang") return;
  const asli = a.NAMOBJ.toUpperCase();
  kelurahan.push({ nama: NAMA[asli] ?? asli, kecamatan: a.WADMKC.toUpperCase(), kode: a.KDEPUM, cincin: bentuk[i] });
});
if (kelurahan.length !== 51 || new Set(kelurahan.map((k) => k.nama)).size !== 51) {
  console.error(`Diharapkan 51 kelurahan Kota Kupang yang berbeda, ditemukan ${kelurahan.length}`);
  process.exit(1);
}

// ---------- Proyeksi ----------

const semua = kelurahan.flatMap((k) => k.cincin.flat());
const lintangTengah = (Math.min(...semua.map((p) => p[1])) + Math.max(...semua.map((p) => p[1]))) / 2;
const kos = Math.cos((lintangTengah * Math.PI) / 180);
const minX = Math.min(...semua.map((p) => p[0] * kos));
const maxX = Math.max(...semua.map((p) => p[0] * kos));
const maxY = Math.max(...semua.map((p) => p[1]));
const minY = Math.min(...semua.map((p) => p[1]));
const skala = (LEBAR - TEPI * 2) / (maxX - minX);
const TINGGI = Math.ceil((maxY - minY) * skala + TEPI + ATAS);
const proyeksi = ([x, y]) => [(x * kos - minX) * skala + TEPI, (maxY - y) * skala + ATAS];
const bulat = (n) => Math.round(n * 10) / 10;

// ---------- Topologi: ruas bersama ----------

const kunci = ([x, y]) => `${x.toFixed(7)},${y.toFixed(7)}`;
const pemilikSisi = new Map();
const cincinBersih = [];
kelurahan.forEach((k, id) => {
  for (const r of k.cincin) {
    const titik = r.slice();
    if (kunci(titik[0]) === kunci(titik[titik.length - 1])) titik.pop();
    const bersih = titik.filter((p, i) => i === 0 || kunci(p) !== kunci(titik[i - 1]));
    cincinBersih.push({ id, titik: bersih });
    for (let i = 0; i < bersih.length; i++) {
      const a = kunci(bersih[i]);
      const b = kunci(bersih[(i + 1) % bersih.length]);
      const s = a < b ? `${a}|${b}` : `${b}|${a}`;
      if (!pemilikSisi.has(s)) pemilikSisi.set(s, new Set());
      pemilikSisi.get(s).add(id);
    }
  }
});
const tandaSisi = (a, b) => {
  const [ka, kb] = [kunci(a), kunci(b)];
  return [...pemilikSisi.get(ka < kb ? `${ka}|${kb}` : `${kb}|${ka}`)].sort((x, y) => x - y).join(",");
};

const ruas = []; // { titik: [[x,y]...] proyeksi, pemilik: string }
const indeksRuas = new Map(); // urutan kunci → nomor ruas
let jumlahTitikAsli = 0;

function daftarRuas(titik, pemilik) {
  const urutan = titik.map(kunci).join(";");
  if (indeksRuas.has(urutan)) return { nomor: indeksRuas.get(urutan), balik: false };
  const terbalik = titik.map(kunci).reverse().join(";");
  if (indeksRuas.has(terbalik)) return { nomor: indeksRuas.get(terbalik), balik: true };
  ruas.push({ titik: titik.map(proyeksi), pemilik });
  indeksRuas.set(urutan, ruas.length - 1);
  return { nomor: ruas.length - 1, balik: false };
}

for (const c of cincinBersih) {
  const t = c.titik;
  const n = t.length;
  jumlahTitikAsli += n;
  const tanda = t.map((_, i) => tandaSisi(t[i], t[(i + 1) % n]));
  let simpul = [];
  for (let i = 0; i < n; i++) if (tanda[i] !== tanda[(i - 1 + n) % n]) simpul.push(i);
  if (simpul.length === 0) {
    // Cincin yang seluruhnya satu ruas: dua titik tetap yang sama bagi kedua sisinya
    let pertama = 0;
    for (let i = 1; i < n; i++) if (kunci(t[i]) < kunci(t[pertama])) pertama = i;
    let jauh = pertama;
    for (let i = 0; i < n; i++) if (Math.hypot(t[i][0] - t[pertama][0], t[i][1] - t[pertama][1]) > Math.hypot(t[jauh][0] - t[pertama][0], t[jauh][1] - t[pertama][1])) jauh = i;
    simpul = [pertama, jauh].sort((a, b) => a - b);
  }
  c.ruas = simpul.map((mulai, j) => {
    const akhir = simpul[(j + 1) % simpul.length];
    const potong = [];
    for (let i = mulai; ; i = (i + 1) % n) {
      potong.push(t[i]);
      if (i === akhir && potong.length > 1) break;
    }
    return daftarRuas(potong, tanda[mulai]);
  });
}

// ---------- Penyederhanaan per ruas ----------

function douglasPeucker(titik, toleransi) {
  if (titik.length <= 2) return titik;
  const simpan = new Array(titik.length).fill(false);
  simpan[0] = simpan[titik.length - 1] = true;
  const tumpukan = [[0, titik.length - 1]];
  while (tumpukan.length) {
    const [a, b] = tumpukan.pop();
    let jauh = -1;
    let indeks = -1;
    for (let i = a + 1; i < b; i++) {
      const d = jarakRuas(titik[i], titik[a], titik[b]);
      if (d > jauh) {
        jauh = d;
        indeks = i;
      }
    }
    if (jauh > toleransi) {
      simpan[indeks] = true;
      tumpukan.push([a, indeks], [indeks, b]);
    }
  }
  return titik.filter((_, i) => simpan[i]);
}

function jarakRuas([px, py], [ax, ay], [bx, by]) {
  const dx = bx - ax;
  const dy = by - ay;
  const t = dx || dy ? Math.max(0, Math.min(1, ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy))) : 0;
  return Math.hypot(px - (ax + t * dx), py - (ay + t * dy));
}

for (const r of ruas) r.sederhana = douglasPeucker(r.titik, TOLERANSI).map((p) => p.map(bulat));

// ---------- Merangkai kelurahan dan kecamatan ----------

function rangkai(daftar) {
  const titik = [];
  for (const { nomor, balik } of daftar) {
    const bagian = balik ? ruas[nomor].sederhana.slice().reverse() : ruas[nomor].sederhana;
    titik.push(...(titik.length ? bagian.slice(1) : bagian));
  }
  titik.pop();
  return titik;
}
const jalur = (cincin) => cincin.map((t) => `M${t.map((p) => p.join(" ")).join("L")}Z`).join("");

const cincinKelurahan = kelurahan.map((_, id) => cincinBersih.filter((c) => c.id === id).map((c) => rangkai(c.ruas)));

const namaKecamatan = [...new Set(kelurahan.map((k) => k.kecamatan))].sort();
const cincinKecamatan = {};
for (const kec of namaKecamatan) {
  const anggota = new Set(kelurahan.map((k, id) => (k.kecamatan === kec ? id : -1)).filter((id) => id >= 0));
  // Ruas tepi kecamatan: dimiliki satu kelurahan saja (tepi kota atau laut) atau berbatasan dengan kecamatan lain
  const tepi = cincinBersih
    .filter((c) => anggota.has(c.id))
    .flatMap((c) => c.ruas)
    .filter(({ nomor }) => {
      const pemilik = ruas[nomor].pemilik.split(",").map(Number);
      return pemilik.length === 1 || pemilik.some((id) => !anggota.has(id));
    })
    .map((r) => {
      const t = r.balik ? ruas[r.nomor].sederhana.slice().reverse() : ruas[r.nomor].sederhana;
      return { titik: t, mulai: t[0].join(","), akhir: t[t.length - 1].join(","), dipakai: false };
    });
  const cincin = [];
  for (const awal of tepi) {
    if (awal.dipakai) continue;
    const titik = [];
    let r = awal;
    while (r && !r.dipakai) {
      r.dipakai = true;
      titik.push(...(titik.length ? r.titik.slice(1) : r.titik));
      const akhir = r.akhir;
      r = tepi.find((x) => !x.dipakai && x.mulai === akhir);
    }
    titik.pop();
    cincin.push(titik);
  }
  cincinKecamatan[kec] = cincin;
}

// ---------- Titik label ----------

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

/** Titik di dalam wilayah yang paling jauh dari tepinya, dan jarak itu */
function titikLabel(cincin) {
  const t = cincin.flat();
  let [x0, y0, x1, y1] = [Math.min(...t.map((p) => p[0])), Math.min(...t.map((p) => p[1])), Math.max(...t.map((p) => p[0])), Math.max(...t.map((p) => p[1]))];
  let terbaik = null;
  let jarakTerbaik = -1;
  for (let putaran = 0; putaran < 4; putaran++) {
    const langkah = Math.max(x1 - x0, y1 - y0) / 40;
    for (let x = x0; x <= x1; x += langkah) {
      for (let y = y0; y <= y1; y += langkah) {
        if (!didalam([x, y], cincin)) continue;
        let jarak = Infinity;
        for (const r of cincin) for (let i = 0; i < r.length; i++) jarak = Math.min(jarak, jarakRuas([x, y], r[i], r[(i + 1) % r.length]));
        if (jarak > jarakTerbaik) {
          jarakTerbaik = jarak;
          terbaik = [x, y];
        }
      }
    }
    const w = (x1 - x0) / 4;
    const h = (y1 - y0) / 4;
    [x0, x1, y0, y1] = [terbaik[0] - w, terbaik[0] + w, terbaik[1] - h, terbaik[1] + h];
  }
  return { titik: terbaik.map(bulat), ruang: bulat(jarakTerbaik) };
}

const kotak = (cincin) => {
  const t = cincin.flat();
  return [Math.min(...t.map((p) => p[0])), Math.min(...t.map((p) => p[1])), Math.max(...t.map((p) => p[0])), Math.max(...t.map((p) => p[1]))].map(bulat);
};

// ---------- Menulis modul ----------

const barisKecamatan = namaKecamatan.map((kec) => {
  const c = cincinKecamatan[kec];
  const { titik } = titikLabel(c);
  const luar = LABEL_LUAR[kec] ? `, luar: [${LABEL_LUAR[kec].join(", ")}]` : "";
  return `  ${JSON.stringify(kec)}: { jalur: ${JSON.stringify(jalur(c))}, label: [${titik.join(", ")}]${luar}, kotak: [${kotak(c).join(", ")}] },`;
});
const barisKelurahan = kelurahan
  .map((k, id) => ({ k, id }))
  .sort((a, b) => a.k.nama.localeCompare(b.k.nama))
  .map(({ k, id }) => {
    const c = cincinKelurahan[id];
    const { titik, ruang } = titikLabel(c);
    return `  ${JSON.stringify(k.nama)}: { kecamatan: ${JSON.stringify(k.kecamatan)}, jalur: ${JSON.stringify(jalur(c))}, label: [${titik.join(", ")}], ruang: ${ruang} },`;
  });

const titikAkhir = ruas.reduce((n, r) => n + r.sederhana.length, 0);
const isi = `/*
 * Batas kecamatan dan kelurahan Kota Kupang untuk peta Pemetaan PKB di portal
 * dan dashboard (D-052). Sumber: Batas Desa/Kelurahan skala 1:10.000 edisi
 * 2022, Badan Informasi Geospasial (berkas pemilik). Batasnya disederhanakan
 * tanpa memisahkan garis batas tetangga; batas kecamatan dirangkai dari batas
 * kelurahannya. Dibuat oleh \`node scripts/batas-wilayah.mjs\`; jangan diubah
 * dengan tangan.
 */

/** Ukuran kotak peta (utara di atas) dan letak tulisan Teluk Kupang */
export const UKURAN_PETA = { lebar: ${LEBAR}, tinggi: ${TINGGI}, laut: [${LAUT.join(", ")}] } as const

/**
 * Per kecamatan: jalur SVG, titik label di bagian terlebarnya (\\\`luar\\\` untuk
 * kecamatan yang terlalu sempit: tulisannya di laut dengan garis ke titik
 * label), dan kotak batasnya [x0, y0, x1, y1] untuk memperbesar peta
 */
export const BATAS_KECAMATAN: Record<string, { jalur: string; label: [number, number]; luar?: [number, number]; kotak: [number, number, number, number] }> = {
${barisKecamatan.join("\n")}
}

/**
 * Per kelurahan, dengan nama seperti daftar resmi (App\\\\Enums\\\\Kecamatan):
 * kecamatannya, jalur SVG, titik label, dan jarak titik itu ke tepi terdekat
 * (dalam satuan peta, untuk menilai muat-tidaknya tulisan)
 */
export const BATAS_KELURAHAN: Record<string, { kecamatan: string; jalur: string; label: [number, number]; ruang: number }> = {
${barisKelurahan.join("\n")}
}
`;
writeFileSync(target, isi);
const tunggal = ruas.filter((r) => !r.pemilik.includes(",")).length;
console.log(`titik asli ${jumlahTitikAsli}, ruas ${ruas.length} (${tunggal} tepi luar/laut), titik sesudah ${titikAkhir}, tinggi ${TINGGI}`);
console.log(`ditulis ${target} (${(isi.length / 1024).toFixed(1)} KB)`);
for (const kec of namaKecamatan) console.log(kec, "cincin", cincinKecamatan[kec].length, "kotak", kotak(cincinKecamatan[kec]).join(","));
