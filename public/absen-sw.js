/*
 * Service worker aplikasi Absensi PINTU (/absen, D-034).
 *
 * Gunanya hanya agar aplikasi bisa dipasang ke layar utama dan cepat dibuka:
 * berkas build bernama hash (/build/assets) disimpan setelah dimuat pertama
 * kali. Halaman dan API selalu diambil dari server; clock-in tidak pernah
 * disimpan atau diantrekan saat tidak ada koneksi, karena waktu dan lokasinya
 * harus diperiksa server saat itu juga.
 */
const CACHE = "absen-v1";

const LUAR_JARINGAN = `<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Absensi PINTU</title></head>
<body style="font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;padding:24px;text-align:center;background:#F7F6F2;color:#0F1319">
<div><p style="font-weight:600">Tidak ada koneksi internet.</p><p>Clock-in memerlukan koneksi agar waktu dan lokasi dapat diperiksa server.</p>
<button onclick="location.reload()" style="padding:10px 18px;border-radius:999px;border:0;background:#0F1319;color:#fff">Coba lagi</button></div></body></html>`;

self.addEventListener("install", () => self.skipWaiting());

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((kunci) => Promise.all(kunci.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const { request } = event;
  const url = new URL(request.url);
  if (request.method !== "GET" || url.origin !== self.location.origin) return;

  if (url.pathname.startsWith("/build/assets/")) {
    event.respondWith(
      caches.open(CACHE).then(async (cache) => {
        const tersimpan = await cache.match(request);
        if (tersimpan) return tersimpan;
        const jawaban = await fetch(request);
        if (jawaban.ok) cache.put(request, jawaban.clone());
        return jawaban;
      })
    );
    return;
  }

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request).catch(() => new Response(LUAR_JARINGAN, { headers: { "Content-Type": "text/html; charset=utf-8" } }))
    );
  }
});
