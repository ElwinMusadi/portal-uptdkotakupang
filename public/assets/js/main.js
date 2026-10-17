/* ==========================================================================
   PINTU — Interaksi
   Tanpa dependensi. Setiap modul hanya aktif bila elemennya ada di halaman.
   ========================================================================== */
(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const fmt = new Intl.NumberFormat("id-ID");
  const pad = (n) => String(n).padStart(2, "0");

  /* ------------------------------------------------------------------
     KONFIGURASI KONTEN DINAMIS
     Ubah di sini (atau sambungkan ke CMS/API) — tidak perlu menyentuh HTML.
     Semua nilai bertanda (contoh) wajib diverifikasi petugas sebelum terbit.
     ------------------------------------------------------------------ */
  const CONFIG = {
    timeZone: "Asia/Makassar", // WITA
  };

  const HARI = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const BULAN = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  const BULAN_PENUH = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

  /* ---------- Toast ---------- */
  let toastTimer;
  function toast(msg) {
    const el = $("#toast");
    if (!el) return;
    el.textContent = msg;
    el.classList.add("is-on");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove("is-on"), 2600);
  }
  window.PINTU = { toast };

  /* ---------- Navigasi: dropdown pil & menu seluler ---------- */
  function initNav() {
    const items = $$(".nav__item.has-menu");
    const closeAll = (except) => items.forEach((it) => {
      if (it === except) return;
      it.classList.remove("is-open");
      $(".nav__link", it)?.setAttribute("aria-expanded", "false");
    });
    const canHover = window.matchMedia("(hover: hover) and (pointer: fine)").matches;

    items.forEach((item) => {
      const btn = $(".nav__link", item);
      let leaveTimer;
      const open = () => { closeAll(item); item.classList.add("is-open"); btn.setAttribute("aria-expanded", "true"); };
      const close = () => { item.classList.remove("is-open"); btn.setAttribute("aria-expanded", "false"); };
      btn.addEventListener("click", () => (item.classList.contains("is-open") ? close() : open()));
      if (canHover) {
        item.addEventListener("mouseenter", () => { clearTimeout(leaveTimer); open(); });
        item.addEventListener("mouseleave", () => { leaveTimer = setTimeout(close, 140); });
      }
      item.addEventListener("keydown", (e) => {
        if (e.key === "Escape") { close(); btn.focus(); }
      });
      item.addEventListener("focusout", (e) => { if (!item.contains(e.relatedTarget)) close(); });
    });
    document.addEventListener("click", (e) => { if (!e.target.closest(".nav__item")) closeAll(); });

    const toggle = $(".nav__toggle");
    const panel = $("#mobile-menu");
    if (toggle && panel) {
      toggle.addEventListener("click", () => {
        const on = toggle.getAttribute("aria-expanded") !== "true";
        toggle.setAttribute("aria-expanded", String(on));
        panel.hidden = !on;
      });
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !panel.hidden) { panel.hidden = true; toggle.setAttribute("aria-expanded", "false"); toggle.focus(); }
      });
    }
  }

  /* ---------- Fade-up saat scroll ---------- */
  function initReveal() {
    const els = $$("[data-reveal]");
    if (!("IntersectionObserver" in window)) { els.forEach((el) => el.classList.add("is-in")); return; }
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add("is-in"); io.unobserve(en.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
    els.forEach((el) => io.observe(el));
  }

  /* ---------- Tab (role=tablist) dengan keyboard ---------- */
  function initTabs() {
    $$("[data-tabs]").forEach(pasangTab);
  }

  /** Tab dan panelnya; dipanggil lagi untuk tab yang dibuat dari API */
  function pasangTab(list) {
    const tabs = $$('[role="tab"]', list);
    const select = (tab, focus) => {
      tabs.forEach((t) => {
        const on = t === tab;
        t.setAttribute("aria-selected", String(on));
        t.tabIndex = on ? 0 : -1;
        const panel = document.getElementById(t.getAttribute("aria-controls"));
        if (panel) panel.hidden = !on;
      });
      if (focus) tab.focus();
    };
    tabs.forEach((tab, i) => {
      tab.addEventListener("click", () => select(tab));
      tab.addEventListener("keydown", (e) => {
        const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
        if (dir) { e.preventDefault(); select(tabs[(i + dir + tabs.length) % tabs.length], true); }
        if (e.key === "Home") { e.preventDefault(); select(tabs[0], true); }
        if (e.key === "End") { e.preventDefault(); select(tabs[tabs.length - 1], true); }
      });
    });
  }

  /* ---------- Checklist persyaratan ---------- */
  function initChecklists() {
    $$("[data-checklist]").forEach(pasangChecklist);
  }

  /** Penghitung centang; dipanggil lagi untuk daftar yang dibuat dari API */
  function pasangChecklist(box) {
    const out = $("[data-check-count]", box);
    const update = () => { if (out) out.textContent = $$("input:checked", box).length; };
    box.addEventListener("change", update);
    update();
  }

  /* ---------- Waktu WITA & status loket ----------
     Jam loket dan penyesuaian jadwal dikelola di dashboard dan dibaca dari
     GET /api/public/jam. Penyesuaian (libur, jam khusus) menggantikan jam
     reguler pada tanggalnya. Selama dimuat, atau bila gagal, status dan daftar
     jam tidak menebak: tampil pesan, bukan jam contoh. Teks di-escape sebelum
     dipasang. */
  function nowWita(saat = new Date()) {
    const parts = new Intl.DateTimeFormat("en-US", {
      timeZone: CONFIG.timeZone, hourCycle: "h23",
      year: "numeric", month: "numeric", day: "numeric", hour: "numeric", minute: "numeric", second: "numeric", weekday: "short",
    }).formatToParts(saat);
    const get = (t) => parts.find((p) => p.type === t)?.value;
    const wd = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(get("weekday"));
    return { y: +get("year"), mo: +get("month"), d: +get("day"), h: +get("hour"), m: +get("minute"), s: +get("second"), wd };
  }
  const toSec = (hhmm) => { const [h, m] = hhmm.split(":").map(Number); return h * 3600 + m * 60; };
  const dot = (hhmm) => hhmm.replace(":", ".");
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
  const rentang = (s) => `${dot(s.mulai)}–${dot(s.selesai)}`;
  const hms = (sec) => `${pad(Math.floor(sec / 3600))}:${pad(Math.floor((sec % 3600) / 60))}:${pad(sec % 60)}`;

  async function ambilJson(url) {
    const batas = new AbortController();
    const waktu = setTimeout(() => batas.abort(), 10000);
    try {
      const res = await fetch(url, { headers: { Accept: "application/json" }, credentials: "omit", signal: batas.signal });
      if (!res.ok) throw new Error(`Status ${res.status}`);
      return await res.json();
    } finally {
      clearTimeout(waktu);
    }
  }

  /** Jam loket dari dashboard; null sampai dimuat */
  let JAM = null;
  let janjiJam = null;

  /** Jam loket dimuat sekali untuk status loket dan papan layanan */
  function siapkanJam() {
    if (!janjiJam) janjiJam = muatJam().then((data) => (JAM = data));
    return janjiJam;
  }

  async function muatJam() {
    const data = await ambilJson("/api/public/jam");
    if (!Array.isArray(data?.hari) || !Array.isArray(data?.penyesuaian)) throw new Error("Respons tidak dikenal");
    const waktuValid = (x) => typeof x.mulai === "string" && typeof x.selesai === "string";
    return {
      hari: data.hari.filter((h) => Number.isInteger(h?.hari) && typeof h.buka === "boolean" && waktuValid(h)),
      batasBerkasMenit: Number.isInteger(data.batasBerkasMenit) ? data.batasBerkasMenit : 0,
      catatan: typeof data.catatan === "string" ? data.catatan : "",
      pemberitahuan: typeof data.pemberitahuan === "string" ? data.pemberitahuan : "",
      penyesuaian: data.penyesuaian.filter((p) => typeof p?.tanggal === "string" && typeof p.keterangan === "string" && waktuValid(p)),
    };
  }

  /** Tanggal t + i hari: { iso, wd, d, mo } */
  function tanggalKe(t, i) {
    const d = new Date(Date.UTC(t.y, t.mo - 1, t.d + i));
    return { iso: d.toISOString().slice(0, 10), wd: d.getUTCDay(), d: d.getUTCDate(), mo: d.getUTCMonth() };
  }

  /** Jam buka [mulai, selesai] pada tanggal itu, atau null bila tutup */
  function jamPada(tgl) {
    const khusus = JAM.penyesuaian.find((p) => p.tanggal === tgl.iso);
    if (khusus) return khusus.jenis === "jam-khusus" && khusus.mulai && khusus.selesai ? [khusus.mulai, khusus.selesai] : null;
    const h = JAM.hari.find((x) => x.hari === tgl.wd);
    return h?.buka && h.mulai && h.selesai ? [h.mulai, h.selesai] : null;
  }

  function serviceStatus() {
    const t = nowWita();
    const cur = t.h * 3600 + t.m * 60 + t.s;
    const hariIni = tanggalKe(t, 0);
    const khusus = JAM.penyesuaian.find((p) => p.tanggal === hariIni.iso);
    const today = jamPada(hariIni);
    if (today && cur >= toSec(today[0]) && cur < toSec(today[1])) {
      return { open: true, left: toSec(today[1]) - cur, close: dot(today[1]), khusus };
    }
    // jam buka berikutnya: hari ini nanti atau dalam dua pekan (libur panjang)
    for (let i = 0; i < 15; i++) {
      const tgl = tanggalKe(t, i);
      const h = jamPada(tgl);
      if (!h) continue;
      const start = toSec(h[0]);
      if (i === 0 && cur >= start) continue;
      const nextDay = i === 0 ? "hari ini" : i === 1 ? "besok" : i < 7 ? HARI[tgl.wd] : `${HARI[tgl.wd]}, ${tgl.d} ${BULAN[tgl.mo]}`;
      return { open: false, left: i * 86400 + start - cur, nextDay, nextTime: dot(h[0]), khusus };
    }
    return { open: false, none: true, khusus };
  }

  function initStatus() {
    const textEls = $$("[data-status-text]");
    const shortEls = $$("[data-status-short]");
    const cdEls = $$("[data-countdown]");
    const cdLabels = $$("[data-countdown-label]");
    const dots = $$("[data-open-dot]");
    const pills = $$("[data-open-pill]");
    const headline = $$("[data-status-headline]");
    const subs = $$("[data-status-sub]");
    const caps = $$("[data-countdown-cap]");
    const tabel = $("[data-jam-tabel]");
    const ringkas = $$("[data-jam-ringkas]");
    const ringkasBaris = $$("[data-jam-ringkas-baris]");
    const catatan = $("[data-jam-catatan]");
    const pemberitahuan = $("[data-jam-pemberitahuan]");
    const penyesuaian = $("[data-jam-penyesuaian]");
    if (!(textEls.length || shortEls.length || cdEls.length || headline.length || tabel || ringkas.length || ringkasBaris.length)) return;

    const tulis = (els, teks) => els.forEach((el) => (el.textContent = teks));
    const tandai = (buka) => [...dots, ...pills].forEach((el) => el.classList.toggle("is-closed", !buka));

    const tick = () => {
      const st = serviceStatus();
      if (st.none) {
        tulis(textEls, "Jam loket belum tersedia");
        tulis(shortEls, "Jam belum tersedia");
        tulis(headline, "Jam loket belum tersedia.");
        tulis(subs, "Lihat pengumuman terbaru untuk jadwal pelayanan.");
        tulis(cdEls, "--:--:--");
        tulis(cdLabels, "Jam loket");
        tulis(caps, "");
        tandai(false);
        return;
      }
      const tutupKhusus = st.khusus?.jenis === "tutup";
      tulis(textEls, st.open ? `Loket buka · tutup ${st.close} WITA` : `Loket tutup · buka ${st.nextDay} ${st.nextTime}`);
      tulis(shortEls, st.open ? `Buka · s.d. ${st.close}` : `Tutup · buka ${st.nextDay} ${st.nextTime}`);
      tulis(cdEls, hms(Math.max(0, st.left)));
      tulis(cdLabels, st.open ? "Loket tutup dalam" : "Loket buka dalam");
      tandai(st.open);
      tulis(headline, st.open ? "Loket sedang buka." : tutupKhusus ? "Loket tutup hari ini." : "Loket sedang tutup.");
      const keterangan = st.khusus ? `${st.khusus.keterangan}. ` : "";
      tulis(subs, keterangan + (st.open
        ? JAM.batasBerkasMenit > 0
          ? `Penerimaan berkas ditutup ${JAM.batasBerkasMenit} menit sebelum loket tutup pukul ${st.close} WITA.`
          : `Loket tutup pukul ${st.close} WITA.`
        : `Buka kembali ${st.nextDay} pukul ${st.nextTime} WITA.`));
      tulis(caps, st.open ? "menuju tutup" : "menuju buka");
    };

    // Hari buka berurutan dengan jam yang sama digabung, mis. "Senin–Kamis · 08.00–15.00"
    const kelompok = () => {
      const hasil = [];
      [1, 2, 3, 4, 5, 6, 0].forEach((wd) => {
        const h = JAM.hari.find((x) => x.hari === wd);
        const akhir = hasil[hasil.length - 1];
        if (!h?.buka) { if (akhir) akhir.putus = true; return; }
        if (akhir && !akhir.putus && akhir.mulai === h.mulai && akhir.selesai === h.selesai) akhir.sampai = wd;
        else hasil.push({ dari: wd, sampai: wd, mulai: h.mulai, selesai: h.selesai });
      });
      return hasil.map((g) => ({ hari: g.dari === g.sampai ? HARI[g.dari] : `${HARI[g.dari]}–${HARI[g.sampai]}`, jam: rentang(g) }));
    };

    const tampilkan = () => {
      const t = nowWita();
      if (tabel) {
        tabel.innerHTML = JAM.hari.length
          ? JAM.hari.map((h) => `<tr data-weekday="${h.hari}"${h.hari === t.wd ? ' class="is-today"' : ""}><th scope="row">${h.hari === 0 ? "Minggu &amp; libur" : HARI[h.hari]}</th><td>${h.buka ? esc(rentang(h)) : "Tutup"}</td><td>${h.catatan ? esc(h.catatan) : "—"}</td></tr>`).join("")
          : '<tr><td colspan="3">Jam loket belum diumumkan.</td></tr>';
      }
      const grup = kelompok();
      if (grup.length) {
        ringkas.forEach((ul) => (ul.innerHTML = grup.map((g) => `<li>${g.hari} · ${g.jam}</li>`).join("")));
        tulis(ringkasBaris, grup.map((g) => `${g.hari} ${g.jam}`).join(" · "));
      }
      if (catatan) { catatan.textContent = JAM.catatan; catatan.hidden = !JAM.catatan; }
      if (pemberitahuan) { pemberitahuan.textContent = JAM.pemberitahuan; pemberitahuan.hidden = !JAM.pemberitahuan; }
      if (penyesuaian) {
        penyesuaian.innerHTML = JAM.penyesuaian.map((p) => {
          const [y, mo, d] = p.tanggal.split("-").map(Number);
          const tgl = new Date(Date.UTC(y, mo - 1, d));
          const jam = p.jenis === "jam-khusus" ? `${esc(rentang(p))} WITA` : "Tutup";
          return `<li><strong>${HARI[tgl.getUTCDay()]}, ${d} ${BULAN[mo - 1]} ${y}</strong> · ${jam} · ${esc(p.keterangan)}</li>`;
        }).join("");
        penyesuaian.hidden = !JAM.penyesuaian.length;
      }
    };

    const gagal = () => {
      tulis([...textEls, ...shortEls], "Status loket tidak tersedia");
      tulis(headline, "Status loket belum dapat dimuat.");
      tulis(subs, "Muat ulang halaman untuk mencoba lagi.");
      tulis(cdEls, "--:--:--");
      tulis(cdLabels, "Status loket");
      tulis(caps, "");
      tandai(false);
      if (tabel) tabel.innerHTML = '<tr><td colspan="3">Jam loket belum dapat dimuat. Muat ulang halaman untuk mencoba lagi.</td></tr>';
    };

    siapkanJam().then(() => {
      tampilkan();
      tick();
      setInterval(tick, 1000);
    }, gagal);
  }

  /* ---------- Papan layanan di beranda ----------
     Dikelola di dashboard dan dibaca dari GET /api/public/papan. Isi terakhir
     tampil setiap hari sampai diubah (D-050). Selama loket tutup menurut Jam
     Pelayanan, semua loket tampil Tutup dan waktu tunggu tidak ditampilkan;
     bila jam loket tidak tersedia, papan tampil apa adanya. */
  const STATUS_LOKET = { buka: ["Buka", "board__ok"], istirahat: ["Istirahat", "board__off"], tutup: ["Tutup", "board__off"] };

  function initPapan() {
    const nomor = $("[data-papan-nomor]");
    if (!nomor) return;
    const layanan = $("[data-papan-layanan]");
    const tunggu = $("[data-papan-tunggu]");
    const selisih = $("[data-papan-selisih]");
    const loket = $("[data-papan-loket]");
    const waktu = $("[data-papan-waktu]");
    const catatan = $("[data-papan-catatan]");

    const kosongkan = (pesan) => {
      nomor.textContent = "—";
      layanan.textContent = pesan;
      tunggu.textContent = "—";
      selisih.hidden = true;
      loket.innerHTML = "";
    };

    const tampilkan = (p) => {
      if (typeof p?.diperbarui !== "string") {
        kosongkan("Papan layanan belum tersedia.");
        waktu.textContent = "Belum diperbarui";
        return;
      }
      const w = nowWita(new Date(p.diperbarui));
      const t = nowWita();
      const hariIni = w.y === t.y && w.mo === t.mo && w.d === t.d;
      waktu.textContent = hariIni ? `Diperbarui ${pad(w.h)}.${pad(w.m)} WITA` : "Diperbarui hari ini";
      if (catatan) {
        catatan.textContent = typeof p.catatan === "string" ? p.catatan : "";
        // Baris keterangan di bawah papan hanya tampil bila ada catatan
        catatan.parentElement.hidden = !catatan.textContent;
      }
      const st = JAM ? serviceStatus() : null;
      const tutup = !!st && !st.none && !st.open;
      nomor.textContent = p.nomorAntrean || "—";
      layanan.textContent = typeof p.layananAntrean === "string" ? p.layananAntrean : "";
      tunggu.textContent = !tutup && Number.isInteger(p.waktuTunggu) ? String(p.waktuTunggu) : "—";
      const beda = !tutup && Number.isInteger(p.selisihKemarin) ? p.selisihKemarin : 0;
      selisih.hidden = beda === 0;
      if (beda !== 0) {
        const cepat = beda < 0;
        const arah = $("[data-papan-selisih-arah]", selisih);
        arah.classList.toggle("delta--up", cepat);
        arah.classList.toggle("delta--down", !cepat);
        $("use", arah).setAttribute("href", cepat ? "#i-trend-down" : "#i-trend-up");
        $("[data-papan-selisih-menit]", selisih).textContent = String(Math.abs(beda));
        $("[data-papan-selisih-teks]", selisih).textContent = cepat ? "lebih cepat dari kemarin" : "lebih lama dari kemarin";
      }
      loket.innerHTML = (Array.isArray(p.loket) ? p.loket : []).map((l) => {
        const [label, kelas] = STATUS_LOKET[tutup ? "tutup" : l?.status] ?? ["—", "board__off"];
        return `<li><span class="board__lnum mono">${esc(l.kode)}</span><span>${esc(l.nama)}</span><span class="${kelas}">${label}</span></li>`;
      }).join("");
    };

    // Jam loket ditunggu agar papan tidak sempat menampilkan loket buka saat tutup
    Promise.all([ambilJson("/api/public/papan"), siapkanJam().catch(() => null)]).then(([p]) => {
      tampilkan(p);
      // Mengikuti buka-tutup loket dan pergantian hari selama halaman terbuka
      setInterval(() => tampilkan(p), 30000);
    }, () => {
      kosongkan("Papan layanan belum dapat dimuat.");
      waktu.textContent = "Tidak tersedia";
    });
  }

  /* ---------- Kontak & Kanal ----------
     Alamat, kontak, dan kanal pengaduan dikelola di dashboard (Kontak & Kanal)
     dan dibaca dari GET /api/public/kontak untuk footer semua halaman dan
     halaman Kontak (D-019). Teks dipasang sebagai teks polos dan tautan hanya
     dengan skema yang diizinkan. Tidak ada kontak cadangan: sebelum disimpan
     atau bila API gagal, keterangannya yang tampil. */
  const tautanAman = (url) => (/^(https?:\/\/[^/\s]+|mailto:\S|tel:\+?\d|#|\.{1,2}\/|[\w-]+\.html(?:[#?]|$))/i.test(url) && !/[\s\x00-\x1f\x7f]/.test(url) ? url : "");
  // Nomor Indonesia untuk tel: dan wa.me, mis. (0380) 821 234 menjadi +62380821234
  const nomorInternasional = (nomor) => {
    const d = nomor.replace(/[^\d+]/g, "");
    return d.startsWith("+") ? d : d.startsWith("0") ? `+62${d.slice(1)}` : d.startsWith("62") ? `+${d}` : d;
  };

  async function muatKontak() {
    const k = await ambilJson("/api/public/kontak");
    const teks = (x) => typeof x === "string";
    const isian = ["namaKantor", "alamat", "kota", "provinsi", "telepon", "whatsapp", "whatsappCatatan", "email", "mapsUrl", "catatanLokasi", "janjiBalasan"];
    if (!isian.every((f) => teks(k?.[f])) || !Array.isArray(k.mediaSosial) || !Array.isArray(k.kanalPengaduan)) throw new Error("Respons tidak dikenal");
    k.mediaSosial = k.mediaSosial.filter((a) => teks(a?.platform) && teks(a.akun) && teks(a.url));
    k.kanalPengaduan = k.kanalPengaduan.filter((c) => ["nama", "deskripsi", "tautanLabel", "tautanUrl"].every((f) => teks(c?.[f])));
    return k;
  }

  function tautan(teks, url) {
    const a = document.createElement("a");
    a.textContent = teks;
    a.href = url;
    if (/^https?:/i.test(url)) { a.target = "_blank"; a.rel = "noopener"; }
    return a;
  }

  // Ikon kanal pengaduan menurut namanya; hanya hiasan
  const ikonKanal = (nama) => (/lapor/i.test(nama) ? "megaphone" : /ombudsman/i.test(nama) ? "scale" : /kotak|saran/i.test(nama) ? "inbox" : "message");

  function initKontak() {
    const alamatFooter = $$("[data-kontak-alamat]");
    const hubungi = $$("[data-kontak-hubungi]");
    const baris = $("[data-kontak-alamat-baris]");
    const kanal = $("[data-kontak-kanal]");
    if (!(alamatFooter.length || hubungi.length || baris || kanal)) return;
    const nama = $("[data-kontak-nama]");
    const namaBawaan = nama?.textContent ?? "";
    const item = (k) => $(`[data-kontak-${k}]`);
    const isiItem = (k, teks, url) => {
      const li = item(k);
      if (!li) return;
      const a = $("a", li);
      li.hidden = !teks;
      a.textContent = teks;
      if (url) a.href = url; else a.removeAttribute("href");
    };
    const lainnya = { peta: item("peta"), lokasi: item("lokasi"), janji: item("janji") };

    // Keadaan tanpa data: sebelum disimpan, selama dimuat ulang, atau bila gagal
    const kosongkan = (pesanFooter, pesanKontak, pesanKanal, tombol) => {
      alamatFooter.forEach((p) => (p.textContent = pesanFooter));
      hubungi.forEach((ul) => $$("[data-kontak-isi]", ul).forEach((li) => li.remove()));
      if (nama) nama.textContent = namaBawaan;
      if (baris) {
        baris.textContent = pesanKontak;
        if (tombol) baris.append(tombol);
      }
      ["telepon", "whatsapp", "email", "sosial"].forEach((k) => { if (item(k)) item(k).hidden = true; });
      if (lainnya.peta) lainnya.peta.hidden = true;
      if (lainnya.lokasi) lainnya.lokasi.textContent = "";
      if (lainnya.janji) lainnya.janji.textContent = "";
      if (kanal) kanal.innerHTML = `<p class="channels__status" role="status">${esc(pesanKanal)}</p>`;
    };

    const tampilkan = (k) => {
      const kotaProvinsi = [k.kota, k.provinsi].filter(Boolean);
      alamatFooter.forEach((p) => {
        const barisAlamat = [k.namaKantor, [k.alamat, k.kota].filter(Boolean).join(", "), k.provinsi].filter(Boolean);
        p.replaceChildren(...barisAlamat.flatMap((t, i) => (i ? [document.createElement("br"), t] : [t])));
      });
      hubungi.forEach((ul) => {
        $$("[data-kontak-isi]", ul).forEach((li) => li.remove());
        const isi = [
          k.telepon && tautan(k.telepon, `tel:${nomorInternasional(k.telepon)}`),
          k.email && tautan(k.email, `mailto:${k.email}`),
        ].filter(Boolean).map((a) => {
          const li = document.createElement("li");
          li.dataset.kontakIsi = "";
          li.append(a);
          return li;
        });
        ul.prepend(...isi);
      });
      if (nama) nama.textContent = k.namaKantor;
      if (baris) baris.textContent = [k.alamat, ...kotaProvinsi].filter(Boolean).join(", ");
      isiItem("telepon", k.telepon, k.telepon && `tel:${nomorInternasional(k.telepon)}`);
      const wa = k.whatsapp ? [k.whatsapp, k.whatsappCatatan].filter(Boolean).join(" · ") : "";
      isiItem("whatsapp", wa, k.whatsapp && `https://wa.me/${nomorInternasional(k.whatsapp).replace("+", "")}`);
      isiItem("email", k.email, k.email && `mailto:${k.email}`);
      const sosial = item("sosial");
      if (sosial) {
        const wadah = $("span > span", sosial);
        wadah.replaceChildren(...k.mediaSosial.flatMap((a, i) => {
          const teks = `${a.platform} ${a.akun}`;
          const url = tautanAman(a.url);
          return [...(i ? [" · "] : []), url ? tautan(teks, url) : teks];
        }));
        sosial.hidden = !k.mediaSosial.length;
      }
      const peta = tautanAman(k.mapsUrl);
      if (lainnya.peta) {
        lainnya.peta.hidden = !peta;
        if (peta) lainnya.peta.href = peta;
      }
      if (lainnya.lokasi) lainnya.lokasi.textContent = k.catatanLokasi;
      if (lainnya.janji) lainnya.janji.textContent = k.janjiBalasan;
      if (kanal) {
        kanal.innerHTML = k.kanalPengaduan.length
          ? k.kanalPengaduan.map((c) => {
            const url = tautanAman(c.tautanUrl);
            const judul = /[.!?]$/.test(c.nama) ? c.nama : `${c.nama}.`;
            const link = url
              ? `<a class="link-arrow" href="${esc(url)}"${/^https?:/i.test(url) ? ' target="_blank" rel="noopener"' : ""}>${esc(c.tautanLabel || "Buka tautan")}<svg class="i" aria-hidden="true"><use href="#i-arrow-${/^https?:/i.test(url) ? "up-right" : "right"}"/></svg></a>`
              : "";
            return `<article class="card channel"><svg class="i" aria-hidden="true"><use href="#i-${ikonKanal(c.nama)}"/></svg><h3>${esc(judul)}</h3>${c.deskripsi ? `<p>${esc(c.deskripsi)}</p>` : ""}${link}</article>`;
          }).join("")
          : '<p class="channels__status" role="status">Daftar kanal pengaduan belum tersedia. Anda tetap dapat mengirim pesan lewat formulir di atas.</p>';
      }
    };

    const muat = async (fokus) => {
      try {
        const k = await muatKontak();
        if (typeof k.diperbarui === "string") tampilkan(k);
        else kosongkan("Alamat belum tersedia.", "Alamat dan nomor kontak belum tersedia.", "Daftar kanal pengaduan belum tersedia. Anda tetap dapat mengirim pesan lewat formulir di atas.");
      } catch {
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "link";
        retry.textContent = "Coba lagi";
        retry.addEventListener("click", () => {
          kosongkan("Memuat alamat…", "Memuat alamat…", "Memuat kanal pengaduan…");
          muat(true);
        });
        kosongkan("Alamat belum dapat dimuat.", "Kontak belum dapat dimuat. ", "Kanal pengaduan belum dapat dimuat.", retry);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Visi & Misi ----------
     Visi, misi, kontribusi, nilai BerAKHLAK, dan maklumat dikelola di
     dashboard (Visi & Misi) dan dibaca dari GET /api/public/visi-misi (D-020).
     Sebelum disimpan, API memberikan teks resmi yang sama dengan halaman
     sebelumnya. Teks dipasang sebagai teks polos; bagian halaman tampil
     setelah isinya dimuat. Tombol unduh maklumat memakai PDF yang diunggah
     (D-028), atau halaman Regulasi & Unduhan bila belum ada. Bila API gagal,
     keterangan dan tombol coba lagi. */
  const KATA_ANGKA = ["Nol", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh"];
  const kataAngka = (n) => KATA_ANGKA[n] ?? String(n);

  async function muatVisiMisi() {
    const d = await ambilJson("/api/public/visi-misi");
    const teks = (x) => typeof x === "string";
    const daftar = (xs, isian) => Array.isArray(xs) && xs.every((x) => isian.every((f) => teks(x?.[f])));
    if (!teks(d?.sumberVisi) || !teks(d.visi) || !teks(d.maklumat?.teks) || !teks(d.maklumat.penandatangan)
      || !(d.maklumat.berkas === null || teks(d.maklumat.berkas?.url))
      || !daftar(d.misi, ["teks", "kataKunci"]) || !daftar(d.kontribusi, ["misi", "judul", "deskripsi"]) || !daftar(d.nilai, ["huruf", "judul", "deskripsi"])) {
      throw new Error("Respons tidak dikenal");
    }
    return d;
  }

  // Bagian akhir diberi nada redup seperti desain halaman, mis. visi setelah koma terakhir atau lanjutan judul
  function duaNada(el, awal, akhir) {
    const nada = document.createElement("span");
    nada.className = "tone";
    nada.textContent = akhir;
    el.replaceChildren(...(awal ? [awal] : []), ...(akhir ? [nada] : []));
  }

  // Kutipan bertanda petik (maklumat, sambutan): kalimat terakhir bernada redup; satu kalimat tetap polos
  function kutipanDuaNada(el, teks) {
    const kalimat = teks.match(/^([\s\S]*[.!?])\s+(\S[\s\S]*)$/);
    if (kalimat) duaNada(el, `“${kalimat[1]} `, `${kalimat[2]}”`);
    else el.textContent = `“${teks}”`;
  }

  // Inisial untuk avatar, mis. "Kepala UPTD" menjadi KU
  const inisial = (teks) => teks.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w.charAt(0).toUpperCase()).join("");

  function initVisiMisi() {
    const visi = $("[data-visi]");
    if (!visi) return;
    const status = $("[data-visi-status]");
    const bagian = (k) => $(`[data-visi-bagian="${k}"]`);
    const nilaiWadah = $("[data-visi-nilai]");

    const tampilkan = (d) => {
      $("[data-visi-sumber]").textContent = d.sumberVisi || "Visi";
      const koma = d.visi.lastIndexOf(", ");
      if (koma > 0) duaNada(visi, d.visi.slice(0, koma + 2), d.visi.slice(koma + 2));
      else visi.textContent = d.visi;

      $("[data-visi-judul-misi]").textContent = `${kataAngka(d.misi.length)} misi.`;
      $("[data-visi-misi]").innerHTML = d.misi.map((m, i) =>
        `<li><span class="numlist__n">${pad(i + 1)}</span><p class="numlist__t">${esc(m.teks)}</p><span class="numlist__k">${esc(m.kataKunci)}</span></li>`).join("");
      bagian("misi").hidden = !d.misi.length;

      const misiDidukung = new Set(d.kontribusi.map((k) => k.misi).filter(Boolean)).size;
      $("[data-visi-jumlah-kontribusi]").textContent = misiDidukung ? `${kataAngka(misiDidukung).toLowerCase()} ` : "";
      $("[data-visi-kontribusi]").innerHTML = d.kontribusi.map((k) => `<article class="card card--pad">
          ${k.misi ? `<p class="smallcaps">${esc(k.misi)}</p>` : ""}
          <h3 class="h3"${k.misi ? ' style="margin-top:12px"' : ""}>${esc(k.judul)}</h3>
          ${k.deskripsi ? `<p class="body" style="margin-top:10px;max-width:none">${esc(k.deskripsi)}</p>` : ""}
        </article>`).join("");
      bagian("kontribusi").hidden = !d.kontribusi.length;

      $("[data-visi-jumlah-nilai]").textContent = String(d.nilai.length);
      $$(":scope > :not(.values__intro)", nilaiWadah).forEach((el) => el.remove());
      nilaiWadah.insertAdjacentHTML("beforeend", d.nilai.map((n) =>
        `<div><span class="values__l">${esc(n.huruf || n.judul.charAt(0))}</span><h3>${esc(n.judul)}</h3>${n.deskripsi ? `<p>${esc(n.deskripsi)}</p>` : ""}</div>`).join(""));
      bagian("nilai").hidden = !d.nilai.length;

      kutipanDuaNada($("[data-visi-maklumat]"), d.maklumat.teks);
      const penandatangan = $("[data-visi-penandatangan]");
      penandatangan.hidden = !d.maklumat.penandatangan;
      $("[data-visi-penandatangan-nama]").textContent = d.maklumat.penandatangan;
      $("[data-visi-inisial]").textContent = inisial(d.maklumat.penandatangan);
      $("[data-visi-unduh]").href = tautanAman(d.maklumat.berkas?.url ?? "") || "unduhan.html";
      bagian("maklumat").hidden = false;

      status.hidden = true;
      // Tautan seperti visi-misi.html#maklumat baru bisa dituju setelah bagiannya tampil
      if (location.hash) document.getElementById(decodeURIComponent(location.hash.slice(1)))?.scrollIntoView();
    };

    const muat = async (fokus) => {
      status.hidden = false;
      status.textContent = "Memuat visi dan misi…";
      try {
        tampilkan(await muatVisiMisi());
      } catch {
        ["misi", "kontribusi", "nilai", "maklumat"].forEach((k) => (bagian(k).hidden = true));
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "link";
        retry.textContent = "Coba lagi";
        retry.addEventListener("click", () => muat(true));
        status.textContent = "Visi dan misi belum dapat dimuat. ";
        status.append(retry);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Tentang UPTD ----------
     Isi halaman Profil dikelola di dashboard (Tentang UPTD) dan dibaca dari
     GET /api/public/profil (D-021). Sebelum disimpan, API memberikan teks awal
     tanpa nama Kepala UPTD. Foto gedung bawaan di HTML tetap tampil sampai
     foto kantor diunggah dari dashboard, dan foto Kepala UPTD menggantikan
     ilustrasinya (D-028). Teks dipasang sebagai teks polos dan foto hanya dari
     alamat http(s); bila API gagal, keterangan dan tombol coba lagi. */
  const gambarAman = (url) => (typeof url === "string" && /^https?:\/\/[^\s\x00-\x1f\x7f]+$/i.test(url) ? url : "");

  /* Tanda mitra di lencana (Beranda dan Struktur Organisasi): logonya bila
     sudah diunggah, atau inisialnya (D-037). Nama mitra tertulis di sebelahnya,
     jadi logo tidak diberi teks alternatif sendiri. */
  const tandaMitra = (logo, singkatan) => {
    const src = gambarAman(logo);
    if (src) return `<span class="badge__dot badge__dot--logo"><img src="${esc(src)}" alt="" loading="lazy" decoding="async"></span>`;
    return singkatan ? `<span class="badge__dot">${esc(singkatan)}</span>` : "";
  };


  async function muatProfil() {
    const p = await ambilJson("/api/public/profil");
    const teks = (x) => typeof x === "string";
    const daftar = (xs, isian) => Array.isArray(xs) && xs.every((x) => isian.every((f) => teks(x?.[f])));
    if (!["judul", "lanjutan", "pengantar", "keteranganFoto"].every((f) => teks(p?.[f]))
      || !["judul", "lanjutan", "deskripsi"].every((f) => teks(p.kedudukan?.[f]))
      || !["kutipan", "nama", "jabatan"].every((f) => teks(p.sambutan?.[f]))
      || ![p.foto, p.sambutan.foto].every((f) => f === null || teks(f))
      || !daftar(p.identitas, ["label", "nilai"]) || !daftar(p.tugasFungsi, ["judul", "deskripsi", "kataKunci"])) {
      throw new Error("Respons tidak dikenal");
    }
    return p;
  }

  function initProfil() {
    const judul = $("[data-profil-judul]");
    if (!judul) return;
    const status = $("[data-profil-status]");
    const bagian = (k) => $(`[data-profil-bagian="${k}"]`);
    const identitas = $("[data-profil-identitas]");

    const tampilkan = (p) => {
      duaNada(judul, p.lanjutan ? `${p.judul} ` : p.judul, p.lanjutan);
      $("[data-profil-pengantar]").textContent = p.pengantar;
      $("[data-profil-keterangan-foto]").textContent = p.keteranganFoto;
      const fotoKantor = gambarAman(p.foto);
      if (fotoKantor) {
        const foto = $("[data-profil-foto]");
        foto.removeAttribute("srcset");
        foto.src = fotoKantor;
        foto.alt = p.keteranganFoto || "Foto kantor UPTD";
      }

      const k = p.kedudukan;
      duaNada($("[data-profil-kedudukan-judul]"), k.lanjutan ? `${k.judul} ` : k.judul, k.lanjutan);
      $("[data-profil-kedudukan-deskripsi]").textContent = k.deskripsi;
      // Baris "Dasar hukum" tetap di akhir tabel
      $$(":scope > :not([data-profil-tetap])", identitas).forEach((el) => el.remove());
      $("[data-profil-tetap]", identitas).insertAdjacentHTML("beforebegin",
        p.identitas.map((f) => `<div><dt>${esc(f.label)}</dt><dd>${esc(f.nilai)}</dd></div>`).join(""));
      bagian("kedudukan").hidden = false;

      $("[data-profil-tugas]").innerHTML = p.tugasFungsi.map((t, i) =>
        `<li><span class="numlist__n">${pad(i + 1)}</span><p class="numlist__t">${esc(t.judul)}${t.deskripsi ? `<small>${esc(t.deskripsi)}</small>` : ""}</p><span class="numlist__k">${esc(t.kataKunci)}</span></li>`).join("");
      bagian("tugas").hidden = !p.tugasFungsi.length;

      // Tanpa nama, jabatan yang ditampilkan sebagai penanda sambutan
      const s = p.sambutan;
      const kutipan = $("[data-profil-kutipan]");
      if (s.kutipan) kutipanDuaNada(kutipan, s.kutipan);
      else kutipan.textContent = "";
      $("[data-profil-nama]").textContent = s.nama || s.jabatan;
      $("[data-profil-jabatan]").textContent = s.nama ? s.jabatan : "";
      $("[data-profil-inisial]").textContent = inisial(s.nama || s.jabatan);
      $("[data-profil-orang]").hidden = !(s.nama || s.jabatan);
      const fotoKepala = gambarAman(s.foto);
      if (fotoKepala) {
        const wadah = $("[data-profil-sambutan-foto]");
        const foto = document.createElement("img");
        foto.src = fotoKepala;
        foto.alt = `Foto ${s.nama || s.jabatan || "Kepala UPTD"}`;
        foto.loading = "lazy";
        wadah.classList.remove("ph", "ph--slate");
        wadah.replaceChildren(foto);
      }
      bagian("sambutan").hidden = !(s.kutipan || s.nama || s.jabatan);

      status.hidden = true;
    };

    const muat = async (fokus) => {
      status.hidden = false;
      status.textContent = "Memuat profil…";
      try {
        tampilkan(await muatProfil());
      } catch {
        ["kedudukan", "tugas", "sambutan"].forEach((k) => (bagian(k).hidden = true));
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "link";
        retry.textContent = "Coba lagi";
        retry.addEventListener("click", () => muat(true));
        status.textContent = "Profil belum dapat dimuat. ";
        status.append(retry);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Persyaratan Layanan ----------
     Layanan dan persyaratannya dikelola di dashboard (Persyaratan Layanan) dan
     dibaca dari GET /api/public/layanan, yang hanya memberikan layanan terbit
     (D-022). Halaman Layanan menampilkan semuanya; beranda menampilkan layanan
     unggulan sebagai tab dengan daftar centang. Teks di-escape; bila API
     gagal, keterangan dan tombol coba lagi. */
  async function muatLayanan() {
    const data = await ambilJson("/api/public/layanan");
    const teks = (x) => typeof x === "string";
    const valid = Array.isArray(data) && data.every((l) =>
      ["nama", "slug", "ringkasan", "loket", "jenis", "catatan"].every((f) => teks(l?.[f]))
      && ["cekFisik", "nonTunai", "diwakilkan", "unggulan"].every((f) => typeof l[f] === "boolean")
      && Array.isArray(l.label) && l.label.every(teks)
      && Array.isArray(l.persyaratan) && l.persyaratan.every((p) => teks(p?.dokumen) && teks(p.jumlah))
      && (l.terbitPada === null || teks(l.terbitPada)));
    if (!valid) throw new Error("Respons tidak dikenal");
    return data;
  }

  // Keterangan dalam kurung di akhir nama berkas ditampilkan redup, mis. "Fotokopi KTP pemilik (sesuai alamat di STNK)"
  const pisahKeterangan = (dokumen) => {
    const m = dokumen.match(/^(.*\S)\s*\(([^()]+)\)$/);
    return m ? [m[1], m[2]] : [dokumen, ""];
  };
  const bertitik = (t) => (/[.!?]$/.test(t) ? t : `${t}.`);
  // Ikon layanan menurut namanya; hanya hiasan
  const ikonLayanan = (nama) => (/pajak/i.test(nama) ? "ticket" : /5\s*tahun|perpanjangan/i.test(nama) ? "refresh"
    : /ganti pemilik|balik nama|bbn/i.test(nama) ? "users" : /mutasi/i.test(nama) ? "arrow-up-right" : "file");
  const tagLayanan = (l, denganLoket) => [
    l.cekFisik
      ? '<span class="tag tag--warn"><svg class="i" aria-hidden="true"><use href="#i-alert"/></svg>Wajib cek fisik</span>'
      : '<span class="tag tag--ok"><svg class="i" aria-hidden="true"><use href="#i-check"/></svg>Tanpa cek fisik</span>',
    ...(denganLoket ? [`<span class="tag tag--line">${esc(l.loket)}</span>`] : []),
    ...(l.nonTunai ? ['<span class="tag tag--line">Bisa non-tunai</span>'] : []),
    ...(l.diwakilkan ? ['<span class="tag tag--line">Dapat diwakilkan</span>'] : []),
    ...l.label.map((t) => `<span class="tag tag--line">${esc(t)}</span>`),
  ].join("");
  const tabelPersyaratan = (persyaratan) => `<div class="table-wrap"><table class="table">
    <thead><tr><th scope="col">No</th><th scope="col">Kelengkapan dokumen</th><th scope="col" class="num">Jumlah</th></tr></thead>
    <tbody>${persyaratan.map((p, i) => {
      const [utama, ket] = pisahKeterangan(p.dokumen);
      return `<tr><td>${i + 1}</td><td>${esc(utama)}${ket ? ` <span class="muted">(${esc(ket)})</span>` : ""}</td><td class="num">${esc(p.jumlah || "—")}</td></tr>`;
    }).join("")}</tbody>
  </table></div>`;
  const catatanLayanan = (catatan) => (catatan ? `<p class="svc__note"><svg class="i" aria-hidden="true"><use href="#i-info"/></svg><span>${esc(catatan)}</span></p>` : "");

  function initLayanan() {
    const daftar = $("[data-layanan-daftar]");
    const nav = $("[data-layanan-nav]");
    const tabList = $("[data-layanan-tab]");
    const panel = $("[data-layanan-panel]");
    if (!daftar && !panel) return;

    // Halaman Layanan: kartu per layanan; persyaratan tambahan (mis. badan hukum) dilipat di kartu terakhir
    const tampilkanHalaman = (data) => {
      const layanan = data.filter((l) => l.jenis === "layanan");
      const tambahan = data.filter((l) => l.jenis !== "layanan");
      const lipatan = tambahan.map((l) => `<details class="svc__extra">
          <summary>${esc(l.nama)}<svg class="i i--sm" aria-hidden="true"><use href="#i-chevron-down"/></svg></summary>
          ${tabelPersyaratan(l.persyaratan)}${catatanLayanan(l.catatan)}
        </details>`).join("");
      daftar.innerHTML = layanan.length
        ? layanan.map((l, i) => `<article class="card svc" id="${esc(l.slug)}">
          <div class="svc__head">
            <div><p class="smallcaps">${esc(l.loket)}</p><h2 class="h3">${esc(bertitik(l.nama))}</h2></div>
            <svg class="i i--lg" aria-hidden="true"><use href="#i-${ikonLayanan(l.nama)}"/></svg>
          </div>
          <p class="svc__desc">${esc(l.ringkasan)}</p>
          <div class="svc__meta">${tagLayanan(l, false)}</div>
          ${tabelPersyaratan(l.persyaratan)}${catatanLayanan(l.catatan)}${i === layanan.length - 1 ? lipatan : ""}
        </article>`).join("")
        : '<p class="muted" role="status">Persyaratan layanan belum tersedia. Tanyakan di meja informasi kantor.</p>';
      if (nav) {
        nav.innerHTML = layanan.map((l) => `<a href="#${esc(l.slug)}"><span class="catlist__txt">${esc(l.nama)}</span><svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg></a>`).join("");
        pasangSpy(nav);
      }
      // Bulan terbit terakhir sebagai tanda verifikasi petugas
      const terakhir = data.map((l) => l.terbitPada).filter(Boolean).sort().pop();
      const verifikasi = $("[data-layanan-verifikasi]");
      if (verifikasi && terakhir) {
        const w = nowWita(new Date(terakhir));
        verifikasi.textContent = ` · diverifikasi petugas, ${BULAN_PENUH[w.mo - 1]} ${w.y}`;
      }
      if (location.hash) document.getElementById(decodeURIComponent(location.hash.slice(1)))?.scrollIntoView();
    };

    // Beranda: tab layanan unggulan dengan daftar centang
    const tampilkanBeranda = (data) => {
      const unggulan = data.filter((l) => l.unggulan && l.jenis === "layanan");
      const bagian = panel.closest("section");
      if (bagian) bagian.hidden = !unggulan.length;
      tabList.innerHTML = unggulan.map((l, i) => `<button class="numtab" role="tab" id="vt-${i + 1}" aria-controls="vp-${i + 1}" aria-selected="${i === 0}"${i ? ' tabindex="-1"' : ""}><span class="numtab__num">${pad(i + 1)}</span><span class="numtab__label">${esc(l.nama)}</span></button>`).join("");
      panel.innerHTML = unggulan.map((l, i) => {
        const w = l.terbitPada && nowWita(new Date(l.terbitPada));
        return `<div class="views__panel" role="tabpanel" id="vp-${i + 1}" aria-labelledby="vt-${i + 1}"${i ? " hidden" : ""}>
          <p class="smallcaps views__label">Bawa saat datang</p>
          <div class="checklist" data-checklist>
            <div class="checklist__head"><p><strong>Persyaratan</strong> · ${esc(l.nama)}</p><p class="checklist__progress"><span data-check-count>0</span> dari ${l.persyaratan.length} siap</p></div>
            <ul role="list">${l.persyaratan.map((p) => {
              const [utama, ket] = pisahKeterangan(p.dokumen);
              return `<li><label><input type="checkbox"><span>${esc(utama)}${ket ? ` <em>${esc(ket)}</em>` : ""}</span><b>${esc(p.jumlah || "—")}</b></label></li>`;
            }).join("")}</ul>
            <div class="checklist__foot">${tagLayanan(l, true)}</div>
          </div>
          <div class="views__foot">
            <p class="views__caption"><strong>${esc(bertitik(l.nama))}</strong> ${esc(l.ringkasan)}</p>
            <p class="micro muted">Sumber: papan persyaratan loket${w ? ` · diverifikasi ${BULAN[w.mo - 1]} ${w.y}` : ""}</p>
          </div>
        </div>`;
      }).join("");
      pasangTab(tabList);
      $$("[data-checklist]", panel).forEach(pasangChecklist);
    };

    const gagal = (wadah, pesan, muat) => {
      const retry = document.createElement("button");
      retry.type = "button";
      retry.className = "link";
      retry.textContent = "Coba lagi";
      retry.addEventListener("click", () => muat(retry));
      const p = document.createElement("p");
      p.className = "muted";
      p.setAttribute("role", "status");
      p.append(pesan, retry);
      wadah.replaceChildren(p);
      return retry;
    };

    const muat = async (dariTombol) => {
      try {
        const data = await muatLayanan();
        if (daftar) tampilkanHalaman(data);
        if (panel && tabList) tampilkanBeranda(data);
      } catch {
        const tombol = [
          ...(daftar ? [gagal(daftar, "Persyaratan layanan belum dapat dimuat. ", muat)] : []),
          ...(panel ? [gagal(panel, "Persyaratan layanan belum dapat dimuat. ", muat)] : []),
        ];
        if (dariTombol) tombol[0]?.focus();
      }
    };

    muat(null);
  }

  /* ---------- Regulasi & unduhan ----------
     Dokumen dikelola di dashboard (Regulasi & Unduhan) dan dibaca dari
     GET /api/public/dokumen, yang hanya memberikan dokumen terbit, urut per
     kategori (D-030). Teksnya dipasang sebagai teks; berkas dan tautan hanya
     dipakai bila beralamat http(s). Bila belum ada dokumen terbit, halaman
     memberi tahu bahwa dokumen sedang disiapkan; bila API gagal, pengunjung
     melihat pesan dan tombol coba lagi. Saringan dan pencarian di atasnya
     diterapkan ulang setelah daftar tampil. */
  async function muatDokumen() {
    const data = await ambilJson("/api/public/dokumen");
    const teks = (x) => typeof x === "string";
    const valid = Array.isArray(data) && data.every((d) =>
      ["judul", "keterangan", "kategori", "format", "nomor", "tahun", "tautan"].every((f) => teks(d?.[f]))
      && (d.berkas === null || (teks(d.berkas?.url) && Number.isFinite(d.berkas.ukuran)))
      && (d.terbitPada === null || teks(d.terbitPada)));
    if (!valid) throw new Error("Respons tidak dikenal");
    return data;
  }

  const IKON_DOKUMEN = { PDF: "PDF", DOCX: "DOC", XLSX: "XLS", JPG: "JPG", Tautan: "URL" };
  const ukuranBerkas = (b) => (b < 1048576
    ? `${fmt.format(Math.max(1, Math.round(b / 1024)))} KB`
    : `${new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 }).format(b / 1048576)} MB`);

  function initDokumen() {
    const daftar = $("[data-dokumen-daftar]");
    if (!daftar) return;
    const status = $("[data-dokumen-status]");
    const saringan = $("[data-dokumen-saringan]");
    const cari = $('[data-filter-search="docs"]');

    const tampilkan = (data) => {
      const dokumen = data.map((d) => ({ ...d, unduh: gambarAman(d.berkas?.url), buka: gambarAman(d.tautan) }))
        .filter((d) => d.unduh || d.buka);
      daftar.innerHTML = dokumen.map((d) => {
        const ikon = IKON_DOKUMEN[d.format] || "DOC";
        const keterangan = d.unduh ? `${d.format} · ${ukuranBerkas(d.berkas.ukuran)}` : d.format === "Tautan" ? "Tautan luar" : `${d.format} · tautan`;
        const tombol = d.unduh
          ? `<a class="btn btn--ghost btn--xs" href="${esc(d.unduh)}" download target="_blank" rel="noopener" aria-label="Unduh ${esc(d.judul)}"><svg class="i i--sm" aria-hidden="true"><use href="#i-download"/></svg>Unduh</a>`
          : `<a class="btn btn--ghost btn--xs" href="${esc(d.buka)}" target="_blank" rel="noopener" aria-label="Buka ${esc(d.judul)}"><svg class="i i--sm" aria-hidden="true"><use href="#i-external"/></svg>Buka</a>`;
        return `<li class="doc" data-cat="${esc(d.kategori)}"><span class="doc__ic${ikon === "PDF" ? "" : " doc__ic--doc"}">${ikon}</span><span><strong>${esc(d.judul)}</strong><span>${esc(d.keterangan)}</span></span><span class="doc__size">${esc(keterangan)}</span>${tombol}</li>`;
      }).join("");
      if (saringan) saringan.hidden = !dokumen.length;
      status.hidden = dokumen.length > 0;
      status.textContent = "Dokumen sedang disiapkan. Untuk sementara, tanyakan dokumen yang Anda perlukan di meja informasi kantor.";
      // Terapkan saringan dan pencarian yang sudah dipilih pengunjung
      if (dokumen.length) cari?.dispatchEvent(new Event("input"));
    };

    const muat = async (fokus) => {
      status.hidden = false;
      status.textContent = "Memuat dokumen…";
      try {
        tampilkan(await muatDokumen());
      } catch {
        daftar.textContent = "";
        if (saringan) saringan.hidden = true;
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "link";
        retry.textContent = "Coba lagi";
        retry.addEventListener("click", () => muat(true));
        status.textContent = "Dokumen belum dapat dimuat. ";
        status.append(retry);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Berita & pengumuman ----------
     Tulisan dikelola di dashboard (Berita & Pengumuman) dan dibaca dari
     GET /api/public/berita (daftar tanpa isi) dan /api/public/berita/{slug}
     (D-031); hanya tulisan terbit yang diberikan. Halaman Informasi
     menampilkan tulisan utama, daftar bersaringan, dan arsip pengumuman;
     /berita/{slug} menampilkan satu tulisan; papan layanan di beranda
     menampilkan pengumuman terbaru yang ditandai tampil di beranda. Teks
     dipasang sebagai teks, isi tulisan disalin lewat daftar elemen yang
     diizinkan (salinIsi), dan foto hanya dari alamat http(s). */
  const teksBerita = (x) => typeof x === "string";
  const ringkasanBeritaValid = (b) => ["judul", "slug", "kategori", "ringkasan", "keteranganSampul", "tanggal"].every((f) => teksBerita(b?.[f]))
    && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(b.slug) && /^\d{4}-\d{2}-\d{2}$/.test(b.tanggal)
    && (b.sampul === null || teksBerita(b.sampul)) && Number.isInteger(b.menitBaca)
    && (b.terbitPada === null || teksBerita(b.terbitPada));

  async function muatDaftarBerita() {
    const data = await ambilJson("/api/public/berita");
    const valid = Array.isArray(data) && data.every((b) => ringkasanBeritaValid(b)
      && typeof b.sorotan === "boolean" && typeof b.pengumumanBeranda === "boolean");
    if (!valid) throw new Error("Respons tidak dikenal");
    return data;
  }

  async function muatBerita(slug) {
    const b = await ambilJson(`/api/public/berita/${encodeURIComponent(slug)}`);
    const valid = ringkasanBeritaValid(b) && ["isi", "kreditFoto", "pemeriksa"].every((f) => teksBerita(b[f]))
      && (b.diperbarui === null || teksBerita(b.diperbarui));
    if (!valid) throw new Error("Respons tidak dikenal");
    return b;
  }

  // Label, warna bingkai, dan ilustrasi pengganti foto per kategori
  const KATEGORI_BERITA = {
    berita: ["Berita", "blue", "desk"],
    pengumuman: ["Pengumuman", "rose", "building"],
    keliling: ["Samsat Keliling", "sage", "tent"],
    edukasi: ["Edukasi", "sand", "car"],
  };
  const kategoriBerita = (k) => KATEGORI_BERITA[k] || KATEGORI_BERITA.berita;
  const alamatBerita = (slug) => `/berita/${encodeURIComponent(slug)}`;

  // Tanggal tayang (YYYY-MM-DD) tanpa geser zona waktu: "27 Okt 2026", "27 Oktober 2026", atau "Selasa, 27 Oktober 2026"
  function tanggalBerita(t, gaya = "pendek") {
    const [y, m, d] = t.split("-").map(Number);
    if (gaya === "pendek") return `${d} ${BULAN[m - 1]} ${y}`;
    const penuh = `${d} ${BULAN_PENUH[m - 1]} ${y}`;
    return gaya === "hari" ? `${HARI[new Date(Date.UTC(y, m - 1, d)).getUTCDay()]}, ${penuh}` : penuh;
  }

  // Waktu terbit atau diperbarui dalam WITA, mis. "27 Okt 2026, 09.15 WITA"
  function waktuBerita(iso) {
    const w = nowWita(new Date(iso));
    return `${w.d} ${BULAN[w.mo - 1]} ${w.y}, ${pad(w.h)}.${pad(w.m)} WITA`;
  }

  // Bingkai foto: sampul bila ada, selain itu ilustrasi kategori
  function mediaBerita(b, kelas) {
    const [, warna, ilustrasi] = kategoriBerita(b.kategori);
    const foto = gambarAman(b.sampul);
    return `<div class="ph ph--${warna} ${kelas}">${foto
      ? `<img src="${esc(foto)}" alt="" loading="lazy" decoding="async">`
      : `<svg aria-hidden="true"><use href="#ill-${ilustrasi}"/></svg>`}</div>`;
  }

  // Judul dua nada seperti contoh halaman: kalimat pertama polos, sisanya redup
  function judulDuaNada(el, judul) {
    const titik = judul.indexOf(". ");
    if (titik > 0) duaNada(el, judul.slice(0, titik + 2), judul.slice(titik + 2));
    else el.textContent = judul;
  }

  const kartuBerita = (b, denganRingkasan) => `<a class="post" href="${alamatBerita(b.slug)}" data-cat="${esc(b.kategori)}">
          ${mediaBerita(b, "post__media")}
          <p class="post-meta"><span class="tag">${esc(kategoriBerita(b.kategori)[0])}</span><time datetime="${esc(b.tanggal)}">${tanggalBerita(b.tanggal)}</time></p>
          <h3>${esc(b.judul)}</h3>${denganRingkasan ? `
          <p>${esc(b.ringkasan)}</p>` : ""}
        </a>`;

  function tombolCobaLagi(aksi) {
    const tombol = document.createElement("button");
    tombol.type = "button";
    tombol.className = "link";
    tombol.textContent = "Coba lagi";
    tombol.addEventListener("click", aksi);
    return tombol;
  }

  function initInformasi() {
    const daftar = $("[data-berita-daftar]");
    if (!daftar) return;
    const status = $("[data-berita-status]");
    const utama = $("[data-berita-utama]");
    const pengumuman = $("[data-berita-pengumuman]");
    const saringan = $("[data-berita-saringan]");
    const cari = $('[data-filter-search="posts"]');

    const tampilkan = (data) => {
      // Tulisan utama: sorotan terbaru, atau tulisan terbaru bila tidak ada
      const pilihan = data.find((b) => b.sorotan) || data[0];
      utama.closest("section").hidden = !pilihan;
      if (pilihan) {
        utama.href = alamatBerita(pilihan.slug);
        utama.innerHTML = `${mediaBerita(pilihan, "feature-post__media")}
        <div>
          <p class="post-meta"><span class="tag">${esc(kategoriBerita(pilihan.kategori)[0])}</span><time datetime="${esc(pilihan.tanggal)}">${tanggalBerita(pilihan.tanggal, "panjang")}</time><span class="dot" aria-hidden="true"></span><span>${pilihan.menitBaca} menit baca</span></p>
          <h2 class="h2" style="margin-top:18px"></h2>
          <p class="body" style="margin-top:18px">${esc(pilihan.ringkasan)}</p>
          <p style="margin-top:24px"><span class="link-arrow">Baca selengkapnya<svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg></span></p>
        </div>`;
        judulDuaNada($("h2", utama), pilihan.judul);
      }

      const lain = data.filter((b) => b !== pilihan);
      daftar.innerHTML = lain.map((b) => kartuBerita(b, true)).join("");
      saringan.hidden = !lain.length;
      status.hidden = data.length > 0;
      status.textContent = "Belum ada berita atau pengumuman yang terbit.";
      // Terapkan saringan dan pencarian yang sudah dipilih pengunjung
      if (lain.length) cari?.dispatchEvent(new Event("input"));

      const arsip = data.filter((b) => b.kategori === "pengumuman").slice(0, 6);
      pengumuman.closest("section").hidden = !arsip.length;
      pengumuman.innerHTML = arsip.map((b) => `<li><a href="${alamatBerita(b.slug)}"><time datetime="${esc(b.tanggal)}">${tanggalBerita(b.tanggal)}</time><strong>${esc(b.judul)}</strong><svg class="i i--sm" aria-hidden="true"><use href="#i-arrow-right"/></svg></a></li>`).join("");
    };

    const muat = async (fokus) => {
      status.hidden = false;
      status.textContent = "Memuat berita…";
      try {
        tampilkan(await muatDaftarBerita());
      } catch {
        daftar.textContent = "";
        saringan.hidden = true;
        const retry = tombolCobaLagi(() => muat(true));
        status.textContent = "Berita belum dapat dimuat. ";
        status.append(retry);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  function initBerita() {
    const judul = $("[data-berita-judul]");
    if (!judul) return;
    const ringkasan = $("[data-berita-ringkasan]");
    const slug = location.pathname.match(/^\/berita\/([a-z0-9]+(?:-[a-z0-9]+)*)\/?$/)?.[1] ?? "";

    const tampilkan = (b) => {
      document.title = `${b.judul.replace(/\.$/, "")} — PINTU UPTD Pendapatan Daerah Wilayah Kota Kupang`;
      $('meta[name="description"]')?.setAttribute("content", b.ringkasan);
      const [label, warna] = kategoriBerita(b.kategori);
      $("[data-berita-crumb]").textContent = b.judul.split(". ")[0].replace(/\.$/, "");
      $("[data-berita-kategori]").textContent = label;
      const tanggal = $("[data-berita-tanggal]");
      tanggal.dateTime = b.tanggal;
      tanggal.textContent = tanggalBerita(b.tanggal, "hari");
      $("[data-berita-menit]").textContent = `${b.menitBaca} menit baca`;
      $("[data-berita-meta]").hidden = false;
      judulDuaNada(judul, b.judul);
      ringkasan.removeAttribute("role");
      ringkasan.textContent = b.ringkasan;

      const foto = gambarAman(b.sampul);
      $("[data-berita-foto]").hidden = !foto;
      if (foto) {
        const img = document.createElement("img");
        img.src = foto;
        img.alt = b.keteranganSampul;
        img.decoding = "async";
        const media = $("[data-berita-media]");
        media.className = `ph ph--${warna} article-media`;
        media.replaceChildren(img);
        $("[data-berita-keterangan]").textContent = b.keteranganSampul;
        $("[data-berita-kredit]").textContent = b.kreditFoto ? `Foto: ${b.kreditFoto}` : "";
      }

      $("[data-berita-terbit]").textContent = b.terbitPada ? waktuBerita(b.terbitPada) : tanggalBerita(b.tanggal);
      $("[data-berita-diperbarui]").textContent = b.diperbarui ? waktuBerita(b.diperbarui) : "—";
      $("[data-berita-pemeriksa]").textContent = b.pemeriksa;
      $("[data-berita-pemeriksa-baris]").hidden = !b.pemeriksa;
      const alamat = `${location.origin}${alamatBerita(b.slug)}`;
      $("[data-berita-wa]").href = `https://wa.me/?text=${encodeURIComponent(`${b.judul} ${alamat}`)}`;
      $("[data-berita-fb]").href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(alamat)}`;

      const isi = $("[data-berita-isi]");
      isi.replaceChildren();
      salinIsi(new DOMParser().parseFromString(b.isi, "text/html").body, isi);
      // Tautan relatif di isi (mis. kontak.html#masukan) dihitung dari akar situs, bukan dari /berita/
      $$("a[href]", isi).forEach((a) => {
        const href = a.getAttribute("href");
        if (!/^(?:[a-z][a-z0-9+.-]*:|\/|#)/i.test(href)) a.href = new URL(href, `${location.origin}/`).href;
      });
      $("[data-berita-badan]").hidden = false;
    };

    // Tulisan yang tidak ada (belum terbit, ditarik, atau salah alamat) dan galat memuat
    const pesan = (judulPesan, teks, tambahan) => {
      document.title = `${judulPesan.replace(/\.$/, "")} — PINTU UPTD Pendapatan Daerah Wilayah Kota Kupang`;
      judul.textContent = judulPesan;
      ringkasan.replaceChildren(teks, tambahan);
      $("[data-berita-badan]").hidden = true;
      $("[data-berita-foto]").hidden = true;
      $("[data-berita-meta]").hidden = true;
    };

    const tampilkanLain = async () => {
      try {
        const lain = (await muatDaftarBerita()).filter((b) => b.slug !== slug).slice(0, 3);
        $("[data-berita-lain]").innerHTML = lain.map((b) => kartuBerita(b, false)).join("");
        $("[data-berita-lain-bagian]").hidden = !lain.length;
      } catch {
        // "Baca juga" hanya pelengkap; tulisan utama tetap tampil
      }
    };

    const muat = async (fokus) => {
      judul.textContent = "Memuat tulisan…";
      ringkasan.setAttribute("role", "status");
      ringkasan.textContent = "";
      try {
        if (!slug) throw new Error("Status 404");
        tampilkan(await muatBerita(slug));
      } catch (e) {
        if (e.message === "Status 404") {
          const semua = document.createElement("a");
          semua.className = "link";
          semua.href = "/informasi";
          semua.textContent = "Lihat semua berita";
          pesan("Tulisan tidak ditemukan.", "Tulisan ini belum terbit, sudah ditarik, atau alamatnya berubah. ", semua);
        } else {
          const retry = tombolCobaLagi(() => muat(true));
          pesan("Tulisan belum dapat dimuat.", "Periksa sambungan internet Anda. ", retry);
          if (fokus) retry.focus();
        }
      }
    };

    muat(false);
    tampilkanLain();
  }

  // Papan layanan beranda: pengumuman terbaru yang ditandai tampil di beranda
  function initPengumumanBeranda() {
    const judul = $("[data-papan-pengumuman-judul]");
    if (!judul) return;
    const teks = $("[data-papan-pengumuman-teks]");
    const baru = $("[data-papan-pengumuman-baru]");
    const tautan = $("[data-papan-pengumuman-tautan]");

    muatDaftarBerita().then((data) => {
      const p = data.find((b) => b.pengumumanBeranda);
      judul.textContent = p ? p.judul : "Belum ada pengumuman.";
      teks.textContent = p ? p.ringkasan : "Pengumuman resmi UPTD akan tampil di sini.";
      tautan.hidden = !p;
      if (p) tautan.href = alamatBerita(p.slug);
      // "Baru" selama tujuh hari setelah terbit
      baru.hidden = !(p?.terbitPada && Date.now() - new Date(p.terbitPada).getTime() < 7 * 864e5);
    }).catch(() => {
      judul.textContent = "Pengumuman belum dapat dimuat.";
      teks.textContent = "";
    });
  }

  /* ---------- Dokumentasi kegiatan ----------
     Kegiatan dikelola di dashboard (Dokumentasi Kegiatan) dan dibaca dari
     GET /api/public/dokumentasi (D-032); hanya kegiatan terbit beserta
     fotonya. Galeri menampilkan satu periode: bulan terbaru, atau periode
     yang dipilih di bagian arsip (bulan-bulan pada tahun terbaru, tahun
     sebelumnya digabung per tahun). Saringan kategori bekerja di dalam
     periode itu. Foto hanya dari alamat http(s); teks dipasang sebagai teks.
     Klik foto membuka lightbox (initGallery). */
  async function muatDokumentasi() {
    const data = await ambilJson("/api/public/dokumentasi");
    const teks = (x) => typeof x === "string";
    const valid = Array.isArray(data) && data.every((d) =>
      ["judul", "kategori", "lokasi", "tanggal", "deskripsi"].every((f) => teks(d?.[f]))
      && /^\d{4}-\d{2}-\d{2}$/.test(d.tanggal)
      && Array.isArray(d.foto) && d.foto.every((f) => teks(f?.src) && teks(f.keterangan))
      && (d.terbitPada === null || teks(d.terbitPada)));
    if (!valid) throw new Error("Respons tidak dikenal");
    return data;
  }

  // Warna bingkai per kategori, tampak sebelum foto termuat
  const WARNA_DOKUMENTASI = { keliling: "sage", sosialisasi: "rose", operasi: "slate", internal: "blue" };
  // Ukuran ubin bergiliran dalam satu periode agar susunan galeri tetap bervariasi
  const UBIN_GALERI = ["b", "", "t", "", "w", "", "", "w", "", ""];

  function initDokumentasi() {
    const galeri = $("[data-dok-galeri]");
    if (!galeri) return;
    const status = $("[data-dok-status]");
    const kosong = $("[data-dok-kosong]");
    const saringan = $("[data-dok-saringan]");
    const ringkasan = $("[data-dok-ringkasan]");
    const kategoriChips = $$("[data-dok-kategori] [data-filter]");
    const arsip = $("[data-dok-arsip]");
    const pilihanPeriode = $("[data-dok-periode]");
    let kategori = "semua";
    let periode = "";
    let daftarPeriode = [];

    const terapkan = () => {
      let tampil = 0;
      $$(".gallery__item", galeri).forEach((it) => {
        const cocok = it.dataset.periode === periode && (kategori === "semua" || it.dataset.cat === kategori);
        it.hidden = !cocok;
        if (cocok) tampil++;
      });
      kosong.hidden = tampil > 0 || !daftarPeriode.length;
      const p = daftarPeriode.find((x) => x.kunci === periode);
      ringkasan.textContent = p ? `${p.panjang} · ${tampil} foto` : "";
      $$("[data-pilih-periode]", pilihanPeriode).forEach((c) => c.setAttribute("aria-pressed", String(c.dataset.pilihPeriode === periode)));
    };

    const tampilkan = (data) => {
      // Data urut tanggal terbaru: bulan-bulan tahun terbaru, lalu satu periode per tahun sebelumnya
      const tahunTerbaru = data[0]?.tanggal.slice(0, 4);
      const kunciPeriode = (t) => (t.slice(0, 4) === tahunTerbaru ? t.slice(0, 7) : t.slice(0, 4));
      const foto = data.flatMap((d) => d.foto.map((f) => ({ src: gambarAman(f.src), keterangan: f.keterangan, d })).filter((f) => f.src));
      daftarPeriode = [...new Set(foto.map((f) => kunciPeriode(f.d.tanggal)))].map((k) => {
        if (k.length === 4) return { kunci: k, label: `Arsip ${k}`, panjang: `Tahun ${k}` };
        const [y, m] = k.split("-").map(Number);
        return { kunci: k, label: `${BULAN[m - 1]} ${y}`, panjang: `${BULAN_PENUH[m - 1]} ${y}` };
      });

      const urutan = {};
      galeri.innerHTML = foto.map((f) => {
        const kunci = kunciPeriode(f.d.tanggal);
        const ubin = UBIN_GALERI[(urutan[kunci] = (urutan[kunci] ?? -1) + 1) % UBIN_GALERI.length];
        const judul = f.keterangan || f.d.judul;
        const tanggal = tanggalBerita(f.d.tanggal);
        return `<button class="gallery__item${ubin ? ` gallery__item--${ubin}` : ""}" type="button" data-cat="${esc(f.d.kategori)}" data-periode="${esc(kunci)}" data-src="${esc(f.src)}" data-title="${esc(judul)}" data-meta="${esc(`${f.d.lokasi} · ${tanggal}`)}">
        <span class="ph ph--${WARNA_DOKUMENTASI[f.d.kategori] || "slate"}"><img src="${esc(f.src)}" alt="" loading="lazy" decoding="async"></span>
        <span class="gallery__cap"><strong>${esc(judul)}</strong><span>${tanggal}</span></span>
      </button>`;
      }).join("");

      pilihanPeriode.innerHTML = daftarPeriode.map((p) => `<button class="chip" type="button" aria-pressed="false" data-pilih-periode="${esc(p.kunci)}">${esc(p.label)}</button>`).join("");
      arsip.hidden = daftarPeriode.length < 2;
      saringan.hidden = !foto.length;
      status.hidden = foto.length > 0;
      status.textContent = "Belum ada dokumentasi kegiatan yang terbit.";
      periode = daftarPeriode[0]?.kunci ?? "";
      terapkan();
    };

    kategoriChips.forEach((c) => c.addEventListener("click", () => {
      kategori = c.dataset.filter;
      kategoriChips.forEach((x) => x.setAttribute("aria-pressed", String(x === c)));
      terapkan();
    }));
    pilihanPeriode.addEventListener("click", (e) => {
      const c = e.target.closest("[data-pilih-periode]");
      if (!c) return;
      periode = c.dataset.pilihPeriode;
      terapkan();
      galeri.closest("section").scrollIntoView();
    });

    const muat = async (fokus) => {
      status.hidden = false;
      status.textContent = "Memuat dokumentasi…";
      try {
        tampilkan(await muatDokumentasi());
      } catch {
        galeri.textContent = "";
        saringan.hidden = true;
        arsip.hidden = true;
        const retry = tombolCobaLagi(() => muat(true));
        status.textContent = "Dokumentasi belum dapat dimuat. ";
        status.append(retry);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Struktur organisasi ----------
     Unit kerja dikelola di dashboard (Struktur Organisasi) dan dibaca dari
     GET /api/public/struktur (D-033): pohon unit UPTD sesuai urutan dan
     mitra satu atap. Bagan: pimpinan di atas, unit di bawahnya berjajar
     dengan jabatan pelaksana, kelompok jabatan fungsional di bawah garis,
     dan mitra sebagai lencana. Uraian tugas setiap unit tampil di akordeon.
     NIP tidak pernah dikirim ke portal; foto pejabat hanya dari alamat
     http(s), dan teks dipasang sebagai teks. */
  async function muatStruktur() {
    const d = await ambilJson("/api/public/struktur");
    const teks = (x) => typeof x === "string";
    const unitValid = (u) => ["nama", "singkatan", "jenis", "jabatan", "pejabat", "uraianTugas", "catatan"].every((f) => teks(u?.[f]))
      && (u.foto === null || teks(u.foto)) && Array.isArray(u.anggota) && u.anggota.every(teks)
      && Array.isArray(u.bawahan) && u.bawahan.every(unitValid);
    const valid = Array.isArray(d?.unit) && d.unit.every(unitValid)
      && Array.isArray(d.mitra) && d.mitra.every((m) => ["nama", "singkatan", "uraianTugas", "catatan"].every((f) => teks(m?.[f])) && (m.foto === null || teks(m.foto)));
    if (!valid) throw new Error("Respons tidak dikenal");
    return d;
  }

  function initStruktur() {
    const bagan = $("[data-struktur-bagan]");
    if (!bagan) return;
    const tugas = $("[data-struktur-tugas]");

    // Baris kedua kotak: pimpinan dengan nama pejabatnya, unit lain "jabatan · pejabat", atau catatannya bila keduanya kosong
    const pakaiCatatan = (u, puncak) => !puncak && !u.jabatan && !u.pejabat;
    const keterangan = (u, puncak) => (puncak ? u.pejabat || u.jabatan : pakaiCatatan(u, puncak) ? u.catatan : [u.jabatan, u.pejabat].filter(Boolean).join(" · "));
    const avatar = (u) => {
      const foto = gambarAman(u.foto);
      return `<span class="avatar">${foto ? `<img src="${esc(foto)}" alt="" loading="lazy" decoding="async">` : esc(u.singkatan)}</span>`;
    };
    const kotak = (u, kelas = "", puncak = false) => {
      const ket = keterangan(u, puncak);
      return `<div class="org__node${kelas}">${avatar(u)}<span><strong>${esc(u.nama)}</strong>${ket ? `<span>${esc(ket)}</span>` : ""}</span></div>`;
    };
    // Satu kolom: unitnya, unit-unit di bawahnya, lalu jabatan pelaksana
    const turunan = (u) => u.bawahan.flatMap((b) => [kotak(b), ...turunan(b)]);
    const kolom = (u) => {
      const isi = [...turunan(u), ...u.anggota.map((a) => `<span class="org__staff">${esc(a)}</span>`)];
      return `<div class="org__col">${kotak(u)}${isi.length ? `<div class="org__sub">${isi.join("")}</div>` : ""}</div>`;
    };
    const semua = (daftar) => daftar.flatMap((u) => [u, ...semua(u.bawahan)]);

    const tampilkan = (d) => {
      const puncak = d.unit.find((u) => u.jenis === "pimpinan") || d.unit[0];
      const lain = d.unit.filter((u) => u !== puncak);
      const jajar = puncak ? puncak.bawahan.filter((u) => u.jenis !== "fungsional") : [];
      const bawah = [...(puncak ? puncak.bawahan.filter((u) => u.jenis === "fungsional") : []), ...lain];
      const catatanMitra = d.mitra.map((m) => m.catatan).find(Boolean);

      bagan.innerHTML = [
        puncak ? kotak(puncak, " org__node--head", true) : "",
        jajar.length ? `<div class="org__v" aria-hidden="true"></div><div class="org__row" style="--org-kolom:${jajar.length}">${jajar.map(kolom).join("")}</div>` : "",
        bawah.length ? `<div class="org__v" aria-hidden="true" style="margin-top:28px"></div><div style="display:grid;gap:12px;justify-items:center;width:100%">${bawah.map((u) => kotak(u, " org__node--dashed")).join("")}</div>` : "",
        d.mitra.length ? `<div class="org__side">${d.mitra.map((m) => `<span class="badge">${tandaMitra(m.foto, m.singkatan)}${esc(m.nama)}</span>`).join("")}</div>` : "",
        catatanMitra ? `<p class="micro muted center" style="margin-top:12px">${esc(catatanMitra)}</p>` : "",
      ].join("") || '<p class="muted center" role="status">Struktur organisasi belum tersedia.</p>';

      // Catatan yang tidak dipakai di bagan menjadi paragraf kedua uraian tugas
      const daftar = [...(puncak ? semua([puncak]) : []), ...semua(lain)].filter((u) => u.uraianTugas);
      tugas.innerHTML = daftar.map((u, i) => {
        const catatan = pakaiCatatan(u, u === puncak) ? "" : u.catatan;
        return `<details class="acc"${i ? "" : " open"}>
          <summary>${esc(u.nama)}<span class="acc__plus" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span></summary>
          <div class="acc__body"><div><p>${esc(u.uraianTugas)}</p>${catatan ? `<p>${esc(catatan)}</p>` : ""}</div></div>
        </details>`;
      }).join("");
      $("[data-struktur-tugas-bagian]").hidden = !daftar.length;
    };

    const muat = async (fokus) => {
      bagan.innerHTML = '<p class="muted center" role="status">Memuat struktur organisasi…</p>';
      try {
        tampilkan(await muatStruktur());
      } catch {
        const retry = tombolCobaLagi(() => muat(true));
        const p = document.createElement("p");
        p.className = "muted center";
        p.setAttribute("role", "status");
        p.append("Struktur organisasi belum dapat dimuat. ", retry);
        bagan.replaceChildren(p);
        $("[data-struktur-tugas-bagian]").hidden = true;
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Aplikasi & Situs ----------
     Aplikasi dan situs milik BPAD/UPTD dikelola di dashboard (Aplikasi &
     Situs) dan dibaca dari GET /api/public/aplikasi (D-054), sesuai urutan.
     Logo hanya dari alamat http(s), tautan hanya alamat yang aman, dan teks
     dipasang sebagai teks. Aplikasi Android ditawarkan lewat Google Play;
     tautan ke luar portal dibuka di tab baru. */
  const PENGELOLA_APLIKASI = { bpad: "BPAD Provinsi NTT", uptd: "UPTD Kota Kupang" };

  async function muatAplikasi() {
    const d = await ambilJson("/api/public/aplikasi");
    const teks = (x) => typeof x === "string";
    const valid = Array.isArray(d?.aplikasi)
      && d.aplikasi.every((a) => ["nama", "namaLengkap", "keterangan", "jenis", "pengelola", "tautan"].every((f) => teks(a?.[f])) && (a.logo === null || teks(a.logo)));
    if (!valid) throw new Error("Respons tidak dikenal");
    return d.aplikasi;
  }

  function initAplikasi() {
    const daftar = $("[data-aplikasi-daftar]");
    if (!daftar) return;

    const kartu = (a) => {
      const logo = gambarAman(a.logo);
      const url = tautanAman(a.tautan);
      const luar = /^https?:/i.test(url);
      const label = a.jenis === "android" ? "Unduh di Google Play" : luar ? "Buka situs" : "Buka halaman";
      const tag = [a.jenis === "android" ? "Android · Google Play" : "Situs web", PENGELOLA_APLIKASI[a.pengelola]].filter(Boolean);
      return `<li class="card card--pad app">
        <div class="app__head">
          <span class="app__logo">${logo ? `<img src="${esc(logo)}" alt="" width="56" height="56" loading="lazy" decoding="async">` : esc(a.nama.slice(0, 2).toUpperCase())}</span>
          <div><h2 class="app__name">${esc(a.nama)}</h2>${a.namaLengkap ? `<p class="app__full">${esc(a.namaLengkap)}</p>` : ""}</div>
        </div>
        ${a.keterangan ? `<p class="app__desc">${esc(a.keterangan)}</p>` : ""}
        <ul class="app__tags" role="list">${tag.map((t) => `<li class="tag tag--line">${esc(t)}</li>`).join("")}</ul>
        ${url ? `<a class="btn btn--sm btn--dark app__cta" href="${esc(url)}"${luar ? ' target="_blank" rel="noopener"' : ""}>${label}<span class="sr-only"> ${esc(a.nama)}${luar ? " (tab baru)" : ""}</span>${luar ? '<svg class="i" aria-hidden="true"><use href="#i-external"/></svg>' : ""}</a>` : ""}
      </li>`;
    };

    const muat = async (fokus) => {
      daftar.innerHTML = '<p class="muted center" role="status">Memuat daftar aplikasi…</p>';
      try {
        const aplikasi = await muatAplikasi();
        daftar.innerHTML = aplikasi.length
          ? `<ul class="apps__list" role="list">${aplikasi.map(kartu).join("")}</ul>`
          : '<p class="muted center" role="status">Belum ada aplikasi yang ditampilkan.</p>';
      } catch {
        const retry = tombolCobaLagi(() => muat(true));
        const p = document.createElement("p");
        p.className = "muted center";
        p.setAttribute("role", "status");
        p.append("Daftar aplikasi belum dapat dimuat. ", retry);
        daftar.replaceChildren(p);
        if (fokus) retry.focus();
      }
    };

    muat(false);
  }

  /* ---------- Beranda ----------
     Teks beranda dikelola di dashboard (Beranda) dan dibaca dari
     GET /api/public/beranda (D-023). Teks di HTML sama dengan isi awal di
     server dan tetap tampil; skrip menggantinya hanya bila dokumen sudah
     pernah disimpan. Bila API gagal, teks HTML tetap tampil. Ikon butir
     mengikuti posisinya; butir tambahan memakai ikon umum. */
  async function muatBeranda() {
    const b = await ambilJson("/api/public/beranda");
    const teks = (x) => typeof x === "string";
    const judulDua = (x) => teks(x?.judul) && teks(x.lanjutan) && teks(x.deskripsi);
    const valid = ["eyebrow", "judul", "lanjutan", "deskripsi", "tombolLabel", "tombolUrl", "catatan"].every((f) => teks(b?.hero?.[f]))
      && ["layananUtama", "integritas", "biaya", "penutup"].every((k) => judulDua(b[k]))
      && Array.isArray(b.nilai) && b.nilai.every((n) => teks(n?.judul) && teks(n.deskripsi))
      && Array.isArray(b.mitra) && b.mitra.every((m) => teks(m?.singkatan) && teks(m.nama) && (m.logo === null || teks(m.logo)))
      && Array.isArray(b.kanal) && b.kanal.every((k) => teks(k?.kelompok) && Array.isArray(k.item) && k.item.every((t) => teks(t?.teks) && teks(t.tautan)))
      && teks(b.biaya.catatanNotis) && teks(b.biaya.wajibDibawa) && Array.isArray(b.biaya.jaminan) && b.biaya.jaminan.every(teks)
      && (b.video === null || (/^[\w-]{11}$/.test(b.video?.id) && ["label", "durasi", "judul"].every((f) => teks(b.video[f]))))
      && (b.diperbarui === null || teks(b.diperbarui));
    if (!valid) throw new Error("Respons tidak dikenal");
    return b;
  }

  function initBeranda() {
    const nilai = $("[data-beranda-nilai]");
    if (!nilai) return;
    const mitra = $("[data-beranda-mitra]");
    const kanal = $("[data-beranda-kanal]");

    /* Video alur pelayanan (D-040): tombolnya tampil hanya bila video sudah
       diisi di dashboard. Pemutar YouTube mode privasi (youtube-nocookie.com)
       baru dimuat saat dialog dibuka dan dilepas saat ditutup, jadi beranda
       tidak memuat apa pun dari YouTube dan video berhenti saat ditutup. */
    const tombolVideo = $("[data-beranda-video]");
    const dialogVideo = $("#dlg-video");
    const pemutarVideo = $("[data-beranda-video-pemutar]");
    const judulVideo = $("[data-beranda-video-judul]");
    let idVideo = "";
    tombolVideo?.addEventListener("click", () => {
      if (!/^[\w-]{11}$/.test(idVideo)) return;
      const iframe = document.createElement("iframe");
      iframe.src = `https://www.youtube-nocookie.com/embed/${idVideo}?autoplay=1&rel=0`;
      iframe.title = judulVideo.textContent.trim();
      iframe.allow = "autoplay; encrypted-media; picture-in-picture; fullscreen";
      iframe.allowFullscreen = true;
      iframe.referrerPolicy = "strict-origin-when-cross-origin";
      pemutarVideo.replaceChildren(iframe);
    });
    dialogVideo?.addEventListener("close", () => pemutarVideo.replaceChildren());
    // Ikon yang tertulis di HTML, menurut posisi butirnya
    const ikonNilai = $$(".feature > svg", nilai).map((el) => el.outerHTML);
    const ikonKanal = $$(".kanal__col", kanal).map((kolom) => $$(".kanal__ic", kolom).map((el) => el.outerHTML));
    const IKON_NILAI = '<svg class="i" aria-hidden="true"><use href="#i-check-circle"/></svg>';
    const IKON_KANAL = '<span class="kanal__ic"><svg class="i" aria-hidden="true"><use href="#i-arrow-right"/></svg></span>';
    // Kanal bertautan: ikon dan namanya menjadi satu tautan; situs luar dibuka di tab baru (D-038)
    const isiKanal = (ikon, t) => {
      const url = tautanAman(t.tautan);
      if (!url) return `${ikon}${esc(t.teks)}`;
      const luar = /^https?:/i.test(url);
      return `<a class="kanal__tautan" href="${esc(url)}"${luar ? ' target="_blank" rel="noopener"' : ""}>${ikon}<span class="kanal__teks">${esc(t.teks)}</span>${luar ? '<span class="sr-only"> (tab baru)</span>' : ""}</a>`;
    };

    const tampilkan = (b) => {
      $$("[data-beranda-teks]").forEach((el) => {
        const [bagian, isian] = el.dataset.berandaTeks.split(".");
        el.textContent = b[bagian][isian];
      });
      $$("[data-beranda-judul]").forEach((el) => {
        const j = b[el.dataset.berandaJudul];
        duaNada(el, j.lanjutan ? `${j.judul} ` : j.judul, j.lanjutan);
      });

      // Judul pembuka diisi komponen React (resources/js/portal/beranda/judul-pembuka.tsx, D-045)
      const tombol = $("[data-beranda-tombol]");
      const url = tautanAman(b.hero.tombolUrl);
      tombol.closest(".hero__actions").hidden = !url;
      if (url) {
        tombol.href = url;
        tombol.replaceChildren(b.hero.tombolLabel, $("svg", tombol));
      }
      const catatan = $("[data-beranda-catatan]");
      catatan.replaceChildren($("svg", catatan), b.hero.catatan);
      catatan.hidden = !b.hero.catatan;

      if (tombolVideo) {
        idVideo = b.video?.id ?? "";
        tombolVideo.hidden = !b.video;
        if (b.video) {
          $("[data-beranda-video-label]", tombolVideo).textContent = b.video.label;
          const durasi = $("[data-beranda-video-durasi]", tombolVideo);
          durasi.textContent = b.video.durasi;
          durasi.hidden = !b.video.durasi;
          judulVideo.textContent = b.video.judul || b.video.label;
        }
      }

      nilai.innerHTML = b.nilai.map((n, i) =>
        `<div class="feature is-in" data-reveal>${ikonNilai[i] ?? IKON_NILAI}<h2 class="title">${esc(n.judul)}</h2>${n.deskripsi ? `<p>${esc(n.deskripsi)}</p>` : ""}</div>`).join("");
      nilai.closest("section").hidden = !b.nilai.length;
      mitra.innerHTML = b.mitra.map((m) => `<li class="badge">${tandaMitra(m.logo, m.singkatan)}${esc(m.nama)}</li>`).join("");
      mitra.closest("section").hidden = !b.mitra.length;
      kanal.innerHTML = b.kanal.map((k, g) => `<div class="kanal__col"><p class="eyebrow">${esc(k.kelompok)}</p><ul role="list">${k.item.map((t, j) =>
        `<li>${isiKanal(ikonKanal[g]?.[j] ?? IKON_KANAL, t)}</li>`).join("")}</ul></div>`).join("");

      // Biaya: kalimat pertama catatan notis ditebalkan
      const notis = $("[data-beranda-catatan-notis]");
      const kalimat = b.biaya.catatanNotis.match(/^([\s\S]*?[.!?])\s+(\S[\s\S]*)$/);
      const tebal = document.createElement("strong");
      tebal.textContent = kalimat ? kalimat[1] : b.biaya.catatanNotis;
      notis.replaceChildren(tebal, ...(kalimat ? [` ${kalimat[2]}`] : []));
      notis.hidden = !b.biaya.catatanNotis;
      const wajib = $("[data-beranda-wajib]");
      wajib.textContent = `Wajib dibawa: ${b.biaya.wajibDibawa}`;
      wajib.hidden = !b.biaya.wajibDibawa;
      $("[data-beranda-jaminan]").innerHTML = b.biaya.jaminan.map((t) =>
        `<li><svg class="i i--sm" aria-hidden="true"><use href="#i-check"/></svg>${esc(t)}</li>`).join("");
    };

    muatBeranda()
      .then((b) => { if (typeof b.diperbarui === "string") tampilkan(b); })
      .catch(() => {});
  }

  /* ---------- Halaman statis ----------
     Kebijakan Privasi dan Syarat & Ketentuan dikelola di dashboard (Halaman
     Statis) dan dibaca dari GET /api/public/halaman/{slug} (D-024). Teks di
     HTML sama dengan isi awal di server dan tetap tampil; skrip menggantinya
     hanya bila halaman sudah pernah disimpan. Isi dibangun ulang hanya dari
     elemen editor yang diizinkan: teks tetap teks, dan tautan hanya dengan
     skema yang diizinkan. Bila API gagal, teks HTML tetap tampil. */
  const ELEMEN_ISI = new Set(["P", "H2", "H3", "UL", "OL", "LI", "BLOCKQUOTE", "STRONG", "EM", "U", "S", "BR", "HR", "A"]);

  function salinIsi(sumber, tujuan) {
    for (const node of sumber.childNodes) {
      if (node.nodeType === Node.TEXT_NODE) {
        tujuan.append(node.data);
      } else if (node.nodeType === Node.ELEMENT_NODE && ELEMEN_ISI.has(node.tagName)) {
        const el = document.createElement(node.tagName.toLowerCase());
        if (node.tagName === "A") {
          const url = tautanAman(node.getAttribute("href") ?? "");
          if (url) el.setAttribute("href", url);
        } else if (node.tagName === "OL") {
          const mulai = node.getAttribute("start") ?? "";
          const jenis = node.getAttribute("type") ?? "";
          if (/^\d{1,6}$/.test(mulai)) el.setAttribute("start", mulai);
          if (["1", "a", "A", "i", "I"].includes(jenis)) el.setAttribute("type", jenis);
        }
        salinIsi(node, el);
        tujuan.append(el);
      }
    }
  }

  async function muatHalaman(slug) {
    const h = await ambilJson(`/api/public/halaman/${encodeURIComponent(slug)}`);
    const teks = (x) => typeof x === "string";
    const valid = ["judul", "judulLanjutan", "pengantar", "isi"].every((f) => teks(h?.[f]))
      && (h.diperbarui === null || teks(h.diperbarui));
    if (!valid) throw new Error("Respons tidak dikenal");
    return h;
  }

  function initHalaman() {
    const isi = $("[data-halaman]");
    if (!isi) return;

    const tampilkan = (h) => {
      const pengantar = $("[data-halaman-pengantar]");
      pengantar.textContent = h.pengantar;
      pengantar.hidden = !h.pengantar;
      duaNada($("[data-halaman-judul]"), h.judulLanjutan ? `${h.judul} ` : h.judul, h.judulLanjutan);
      // DOMParser tidak menjalankan skrip atau memuat berkas; yang dipasang hanya salinan elemen yang diizinkan
      const dokumen = new DOMParser().parseFromString(h.isi, "text/html");
      const baru = document.createDocumentFragment();
      salinIsi(dokumen.body, baru);
      isi.replaceChildren(baru);
    };

    muatHalaman(isi.dataset.halaman)
      .then((h) => { if (typeof h.diperbarui === "string") tampilkan(h); })
      .catch(() => {});
  }

  /* ---------- Pengaturan Situs ----------
     Deskripsi dan hak cipta di kaki halaman serta bilah pengumuman di
     Informasi dikelola di dashboard (Pengaturan Situs) dan dibaca dari
     GET /api/public/situs (D-026). Teks di HTML sama dengan isi awal di
     server dan tetap tampil; skrip menggantinya hanya bila pengaturan sudah
     pernah disimpan. Teks dipasang sebagai teks polos dan tautan hanya
     dengan skema yang diizinkan. Bila API gagal, teks HTML tetap tampil. */
  async function muatSitus() {
    const s = await ambilJson("/api/public/situs");
    const teks = (x) => typeof x === "string";
    const b = s?.bilahPengumuman;
    const valid = teks(s?.deskripsi) && teks(s.hakCipta)
      && typeof b?.aktif === "boolean" && teks(b.teks) && teks(b.tautanLabel) && teks(b.tautanUrl)
      && (s.diperbarui === null || teks(s.diperbarui));
    if (!valid) throw new Error("Respons tidak dikenal");
    return s;
  }

  function initSitus() {
    const deskripsi = $$("[data-situs-deskripsi]");
    const hakCipta = $$("[data-situs-hak-cipta]");
    const pengumuman = $("[data-situs-pengumuman]");
    if (!deskripsi.length && !hakCipta.length && !pengumuman) return;

    const tampilkan = (s) => {
      deskripsi.forEach((el) => { el.textContent = s.deskripsi; el.hidden = !s.deskripsi; });
      hakCipta.forEach((el) => { el.textContent = s.hakCipta; el.hidden = !s.hakCipta; });
      if (!pengumuman) return;
      const b = s.bilahPengumuman;
      pengumuman.closest("section").hidden = !b.aktif || !b.teks;
      $("[data-situs-pengumuman-teks]", pengumuman).textContent = b.teks;
      // Tanpa tujuan yang sah, bilah tetap tampil sebagai teks, bukan tautan
      const url = tautanAman(b.tautanUrl);
      const label = $("[data-situs-pengumuman-label]", pengumuman);
      if (url) pengumuman.setAttribute("href", url);
      else pengumuman.removeAttribute("href");
      label.replaceChildren(b.tautanLabel || "Baca", $("svg", label));
      label.hidden = !url;
    };

    muatSitus()
      .then((s) => { if (typeof s.diperbarui === "string") tampilkan(s); })
      .catch(() => {});
  }

  /* ---------- Layanan luar kantor ----------
     Samsat Keliling 01–03, gerai tetap, dan loket sementara dikelola di
     dashboard (Layanan Luar Kantor, D-043) dan dibaca dari
     GET /api/public/keliling: "jadwal" berisi yang melayani pada setiap
     tanggal dari hari ini sampai Minggu pekan depan, "gerai" gerai tetap, dan
     "sementara" loket sementara yang belum berakhir. Teksnya di-escape sebelum
     dipasang. Tidak ada jadwal cadangan: bila API gagal, pengunjung melihat
     pesan dan tombol coba lagi. */
  const URUT_HARI = [1, 2, 3, 4, 5, 6, 0];
  const tglIso = (iso) => {
    const [y, m, d] = iso.split("-").map(Number);
    return { y, m, d, wd: new Date(Date.UTC(y, m - 1, d)).getUTCDay() };
  };
  const tglTeks = (iso) => { const t = tglIso(iso); return `${t.d} ${BULAN[t.m - 1]} ${t.y}`; };

  /** "Senin–Jumat", "Setiap hari", atau "Senin, Rabu, Jumat" */
  function ringkasHari(hari) {
    const urut = URUT_HARI.filter((h) => hari.includes(h));
    if (urut.length === 7) return "Setiap hari";
    const pos = urut.map((h) => URUT_HARI.indexOf(h));
    const beruntun = pos.every((p, i) => i === 0 || p === pos[i - 1] + 1);
    return urut.length >= 3 && beruntun ? `${HARI[urut[0]]}–${HARI[urut[urut.length - 1]]}` : urut.map((h) => HARI[h]).join(", ");
  }

  async function muatKeliling() {
    const data = await ambilJson("/api/public/keliling");
    if (!data || !Array.isArray(data.jadwal) || !Array.isArray(data.gerai) || !Array.isArray(data.sementara)) throw new Error("Respons tidak dikenal");
    const isi = (s) => ["nama", "mulai", "selesai", "lokasi", "kecamatan", "layanan", "catatan"].every((k) => typeof s?.[k] === "string");
    const tgl = (x) => typeof x === "string" && /^\d{4}-\d{2}-\d{2}$/.test(x);
    const hari = (s) => Array.isArray(s.hari) && s.hari.every((h) => Number.isInteger(h) && h >= 0 && h <= 6);
    return {
      jadwal: data.jadwal.filter((s) => isi(s) && tgl(s.tanggal) && ["keliling", "gerai", "sementara"].includes(s.jenis)),
      gerai: data.gerai.filter((s) => isi(s) && hari(s)),
      sementara: data.sementara.filter((s) => isi(s) && hari(s) && tgl(s.tanggalMulai) && tgl(s.tanggalSelesai)),
    };
  }

  const petaKeliling = (s) => `<a class="link-arrow" href="https://maps.google.com/?q=${esc(encodeURIComponent(s.lokasi + " Kota Kupang"))}" target="_blank" rel="noopener">Buka peta<svg class="i" aria-hidden="true"><use href="#i-arrow-up-right"/></svg></a>`;
  const kartuTempat = (s, atas, rincian) => `
        <div class="stop">
          ${atas}
          <span class="stop__unit">${esc(s.nama)}</span>
          <strong>${esc(s.lokasi)}</strong><span>${esc(s.kecamatan)}</span>${rincian}${s.catatan ? `<span>${esc(s.catatan)}</span>` : ""}
          ${petaKeliling(s)}
        </div>`;

  // Samsat Keliling dan loket sementara per tanggal; gerai tetap punya daftarnya sendiri.
  function agendaKeliling(jadwal, hariIni) {
    const berpindah = jadwal.filter((s) => s.jenis !== "gerai");
    const tanggal = [...new Set(berpindah.map((s) => s.tanggal))];
    if (!tanggal.length) return '<p class="muted">Belum ada jadwal Samsat Keliling atau loket sementara sampai Minggu depan. Jadwal minggu depan terbit setiap Jumat.</p>';
    return tanggal.map((iso) => {
      const t = tglIso(iso);
      const label = iso === hariIni ? '<span class="tag tag--accent">Hari ini</span>' : '<span class="tag tag--line">Terjadwal</span>';
      const isi = berpindah.filter((s) => s.tanggal === iso)
        .map((s) => kartuTempat(s, `<p class="stop__time"><span>${esc(rentang(s))} WITA</span>${label}</p>`, ""))
        .join("");
      return `<div class="week__day ${iso === hariIni ? "is-today" : ""}"><p class="week__date"><strong>${HARI[t.wd]}</strong><span>${tglTeks(iso)}</span></p><div class="week__stops">${isi}</div></div>`;
    }).join("");
  }

  // Satu kartu per gerai (nama dan lokasi) dengan setiap hari dan jam bukanya.
  function daftarGerai(gerai, hariIni) {
    if (!gerai.length) return '<p class="muted">Belum ada gerai tetap.</p>';
    const kelompok = new Map();
    gerai.forEach((g) => {
      const kunci = `${g.nama}\n${g.lokasi}`;
      if (!kelompok.has(kunci)) kelompok.set(kunci, []);
      kelompok.get(kunci).push(g);
    });
    return [...kelompok.values()].map((baris) => {
      const jam = baris.map((g) => {
        const berlaku = g.tanggalMulai && g.tanggalMulai > hariIni ? ` · mulai ${tglTeks(g.tanggalMulai)}` : g.tanggalSelesai ? ` · sampai ${tglTeks(g.tanggalSelesai)}` : "";
        return `<span>${esc(ringkasHari(g.hari))} · ${esc(rentang(g))} WITA${berlaku}</span>`;
      }).join("");
      return kartuTempat(baris[0], "", jam);
    }).join("");
  }

  function daftarSementara(loket, hariIni) {
    if (!loket.length) return '<p class="muted">Tidak ada loket sementara saat ini.</p>';
    return loket.map((s) => {
      const tanggal = s.tanggalMulai === s.tanggalSelesai ? tglTeks(s.tanggalMulai) : `${tglTeks(s.tanggalMulai)} – ${tglTeks(s.tanggalSelesai)}`;
      const tag = s.tanggalMulai > hariIni ? '<span class="tag tag--line">Akan datang</span>' : '<span class="tag tag--accent">Berlangsung</span>';
      return kartuTempat(s, `<p class="stop__time"><span>${tanggal}</span>${tag}</p>`, `<span>${esc(ringkasHari(s.hari))} · ${esc(rentang(s))} WITA</span>`);
    }).join("");
  }

  // Papan beranda: lebih dulu urutan yang diatur di dashboard untuk tanggal itu
  // (urutanPapan, D-053), sisanya urut jam. Di layar lebar daftar mengikuti
  // tinggi kartu di sebelah kirinya; lokasi yang tidak muat diringkas menjadi
  // baris "+N Lokasi lain hari ini". Di layar sempit tampil 4 lokasi pertama.
  const PAPAN_LAYAR_LEBAR = "(min-width: 981px)";
  const PAPAN_BARIS_SEMPIT = 4;
  const papanDipantau = new WeakMap();
  const posisiPapan = (s) => (Number.isInteger(s.urutanPapan) && s.urutanPapan > 0 ? s.urutanPapan : Number.MAX_SAFE_INTEGER);
  const barisPapan = (s) => `<li><span class="board__time mono">${esc(rentang(s))}</span><span><strong>${esc(s.lokasi)}</strong><small>${esc(s.nama)} · ${esc(s.kecamatan)}</small></span></li>`;
  const barisLainPapan = (n) => `<li><span class="board__time mono">+${n}</span><span><strong>Lokasi lain hari ini</strong><small>Lihat di halaman Jadwal.</small></span></li>`;

  function isiPapanLuar(ul, daftar) {
    const pas = matchMedia(PAPAN_LAYAR_LEBAR).matches;
    const tulis = (n) => {
      ul.innerHTML = daftar.slice(0, n).map(barisPapan).join("") + (daftar.length > n ? barisLainPapan(daftar.length - n) : "");
    };
    let n = pas ? daftar.length : Math.min(daftar.length, PAPAN_BARIS_SEMPIT);
    tulis(n);
    while (pas && n > 1 && ul.scrollHeight > ul.clientHeight + 1) tulis(--n);
  }

  // Pas ulang bila ukuran kartu berubah: isi papan di kiri selesai dimuat,
  // huruf selesai dimuat, atau lebar layar berubah.
  function pantauPapan(ul, isi) {
    const sudah = papanDipantau.has(ul);
    papanDipantau.set(ul, isi);
    if (sudah) return;
    const ulang = () => papanDipantau.get(ul)();
    if ("ResizeObserver" in window) new ResizeObserver(ulang).observe(ul.closest(".tile") ?? ul);
    window.addEventListener("resize", ulang);
    document.fonts?.ready.then(ulang);
  }

  function initKeliling() {
    const counts = $$("[data-keliling-count]");
    const todays = $$("[data-keliling-today]");
    const week = $("[data-keliling-week]");
    const geraiEl = $("[data-keliling-gerai]");
    const sementaraEl = $("[data-keliling-sementara]");
    if (!(counts.length || todays.length || week || geraiEl || sementaraEl)) return;

    const tampilkan = (data) => {
      const t = nowWita();
      const hariIni = `${t.y}-${pad(t.mo)}-${pad(t.d)}`;
      const today = data.jadwal.filter((s) => s.tanggal === hariIni);
      const berikut = data.jadwal.find((s) => s.tanggal > hariIni);
      counts.forEach((el) => (el.textContent = today.length ? `${today.length} titik` : "Tidak ada hari ini"));
      const urutPapan = [...today].sort((a, b) => posisiPapan(a) - posisiPapan(b));
      todays.forEach((ul) => {
        const isi = today.length
          ? () => isiPapanLuar(ul, urutPapan)
          : () => (ul.innerHTML = `<li><span class="board__time mono">${HARI[t.wd]}</span><span><strong>Tidak ada layanan di luar kantor hari ini.</strong><small>${berikut ? `Jadwal berikutnya ${HARI[tglIso(berikut.tanggal).wd]}, ${tglTeks(berikut.tanggal)}.` : "Belum ada jadwal."}</small></span></li>`);
        isi();
        pantauPapan(ul, isi);
      });
      if (week) week.innerHTML = agendaKeliling(data.jadwal, hariIni);
      if (geraiEl) geraiEl.innerHTML = daftarGerai(data.gerai, hariIni);
      if (sementaraEl) sementaraEl.innerHTML = daftarSementara(data.sementara, hariIni);
    };

    const gagal = () => {
      counts.forEach((el) => (el.textContent = "Tidak tersedia"));
      todays.forEach((ul) => (ul.innerHTML = '<li><span class="board__time mono">—</span><span><strong>Jadwal belum dapat dimuat.</strong><small>Coba muat ulang halaman.</small></span></li>'));
      [geraiEl, sementaraEl].forEach((el) => el && (el.innerHTML = '<p class="muted">Jadwal belum dapat dimuat.</p>'));
      if (!week) return;
      week.innerHTML = '<p class="muted" role="status">Jadwal layanan di luar kantor belum dapat dimuat. <button type="button" class="link" data-keliling-retry>Coba lagi</button></p>';
      $("[data-keliling-retry]", week).addEventListener("click", () => muat(true));
    };

    const muat = async (fokus) => {
      if (week) {
        week.setAttribute("aria-busy", "true");
        if (fokus) week.innerHTML = '<p class="muted" role="status">Memuat jadwal…</p>';
      }
      try {
        tampilkan(await muatKeliling());
      } catch {
        gagal();
        if (fokus) $("[data-keliling-retry]", week)?.focus();
      } finally {
        week?.removeAttribute("aria-busy");
      }
    };
    muat(false);
  }

  /* ---------- Dasbor transparansi ----------
     Setiap tab punya sumbernya sendiri (D-055):
     - Transaksi pelayanan, Samsat Keliling, dan Kepuasan masyarakat dari
       Statistik Layanan (GET /api/public/statistik), yang hanya memberikan
       angka berstatus terverifikasi pimpinan (D-018); grafiknya 12 nilai
       bulanan (Januari–Desember) tahun data tersebut.
     - Penerimaan pajak dari Laporan Penerimaan yang ditampilkan di portal
       (GET /api/public/penerimaan/ringkasan): tahun berjalan per bulan; bulan
       yang disamarkan tidak dijumlah.
     - Pemetaan PKB dari GET /api/public/pemetaan: angka kota dan persen
       menunggak per kecamatan.
     Tab Penerimaan dan Pemetaan disembunyikan selama datanya belum
     ditampilkan di portal, dan menautkan ke halaman lengkapnya. Tab
     kategori Statistik Layanan yang dimatikan "Tampil di portal" juga
     disembunyikan, apa pun status datanya (D-056). Tidak ada
     angka cadangan: selama dimuat atau bila API gagal, tab menampilkan
     keterangannya. */
  async function muatStatistik() {
    const data = await ambilJson("/api/public/statistik");
    if (typeof data?.tahun !== "string" || typeof data.catatan !== "string" || !Array.isArray(data.set)) throw new Error("Respons tidak dikenal");
    const angka = (n) => Number.isFinite(n);
    const teks = (s) => typeof s === "string";
    const lengkap = data.set.every((s) => ["kunci", "label", "sub", "judulGrafik", "satuan"].every((k) => teks(s?.[k]))
      && Array.isArray(s.seri) && s.seri.length === 12 && s.seri.every(angka)
      && Array.isArray(s.tiles) && s.tiles.every((t) => teks(t?.label) && teks(t.satuan) && angka(t.nilai) && angka(t.perubahan) && typeof t.naikBaik === "boolean"))
      // Kategori yang tampil di portal (D-056); tanpa daftar ini semua tab statistik tampil
      && (data.kategori === undefined || (Array.isArray(data.kategori) && data.kategori.every(teks)));
    if (!lengkap) throw new Error("Respons tidak dikenal");
    return data;
  }

  async function muatRingkasanPenerimaan() {
    const d = await ambilJson("/api/public/penerimaan/ringkasan");
    if (d?.tersedia === false) return null;
    const angkaAtauNull = (n) => n === null || Number.isFinite(n);
    const valid = d?.tersedia === true && typeof d.tahun === "string" && typeof d.dari === "string" && typeof d.sampai === "string"
      && (d.ringkasan === null || ["transaksi", "pkb", "bbn", "jumlah"].every((k) => Number.isFinite(d.ringkasan[k])))
      && Array.isArray(d.bulan) && d.bulan.length === 12 && d.bulan.every((b) => typeof b?.bulan === "string" && angkaAtauNull(b.transaksi) && angkaAtauNull(b.jumlah));
    if (!valid) throw new Error("Respons tidak dikenal");
    return d;
  }

  async function muatRingkasanPemetaan() {
    const d = await ambilJson("/api/public/pemetaan");
    if (d?.tersedia === false) return null;
    const angkaAtauNull = (n) => n === null || Number.isFinite(n);
    const valid = d?.tersedia === true && typeof d.tanggalPenetapan === "string" && d.kota
      && ["kendaraan", "taat", "menunggak", "tagihan"].every((k) => angkaAtauNull(d.kota[k]))
      && Array.isArray(d.kecamatan) && d.kecamatan.every((k) => typeof k?.kunci === "string" && typeof k.nama === "string" && angkaAtauNull(k.kendaraan) && angkaAtauNull(k.menunggak));
    if (!valid) throw new Error("Respons tidak dikenal");
    return d;
  }

  // "M" berarti miliar rupiah: di bawah 1 miliar ditampilkan dalam juta
  const nilaiTile = (v, satuan) => {
    const u = satuan.trim();
    if (u === "M") return v >= 1 ? `${fmt.format(Math.round(v * 10) / 10)}<small>miliar</small>` : `${fmt.format(Math.round(v * 1000))}<small>juta</small>`;
    return `${fmt.format(Math.round(v * 10) / 10)}${u ? `<small>${esc(u)}</small>` : ""}`;
  };
  const nilaiBatang = (v, satuan) => (satuan === "juta rupiah" ? `${fmt.format(v)} jt` : satuan === "menit" ? `${fmt.format(v)} mnt` : fmt.format(v));
  const fmtDesimal = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 });
  const persenDari = (bagian, total) => (Number.isFinite(bagian) && total > 0 ? `${fmtDesimal.format((bagian / total) * 100)}%` : "—");
  const rupiahTile = (n) => (Number.isFinite(n) ? nilaiTile(n / 1e9, "M") : "—");
  const rupiahRingkas = (n) => (n >= 1e9 ? `${fmtDesimal.format(n / 1e9)} M` : `${fmtDesimal.format(n / 1e6)} jt`);
  const tanggalSingkat = (iso, tahun = true) => {
    const [y, m, d] = iso.split("-").map(Number);
    return `${d} ${BULAN[m - 1]}${tahun ? ` ${y}` : ""}`;
  };

  /* Isi satu tab: subjudul, angka utama, grafik batang, catatan, dan tautan */
  function isiStatistik(D, d) {
    const t = nowWita();
    const bulanIni = String(t.y) === D.tahun ? t.mo - 1 : -1;
    const w = nowWita(new Date(D.diperbarui));
    return {
      sub: d.sub,
      tiles: d.tiles.map((x) => {
        const good = (x.perubahan >= 0) === x.naikBaik;
        return {
          label: x.label,
          nilai: nilaiTile(x.nilai, x.satuan),
          meta: `<span class="delta ${good ? "delta--up" : "delta--down"}"><svg class="i" aria-hidden="true"><use href="#i-trend-${x.perubahan >= 0 ? "up" : "down"}"/></svg>${x.perubahan > 0 ? "+" : ""}${fmt.format(x.perubahan)}%</span><span>vs periode lalu</span>`,
        };
      }),
      grafik: {
        judul: d.judulGrafik,
        satuan: d.satuan,
        aria: `${d.judulGrafik}, Januari–Desember ${D.tahun}`,
        batang: d.seri.map((v, i) => ({ x: BULAN[i], nilai: v, tip: nilaiBatang(v, d.satuan), aria: `${BULAN[i]} ${D.tahun}: ${fmt.format(v)} ${d.satuan}`.trim(), sorot: i === bulanIni })),
      },
      catatan: [`Data tahun ${D.tahun}, terverifikasi pimpinan · diperbarui ${w.d} ${BULAN[w.mo - 1]} ${w.y}.`, D.catatan].filter(Boolean),
      tautan: null,
    };
  }

  function isiPenerimaan(P) {
    const r = P.ringkasan ?? { transaksi: 0, pkb: 0, bbn: 0, jumlah: 0 };
    const opsen = ["opsenPkb", "opsenBbn", "opsenDendaPkb", "opsenDendaBbn", "opsenTunggakan", "opsenDendaTunggakan"].reduce((n, k) => n + (r[k] ?? 0), 0);
    const t = nowWita();
    const bulanIni = String(t.y) === P.tahun ? t.mo - 1 : -1;
    const periode = `${tanggalSingkat(P.dari, P.dari.slice(0, 4) !== P.sampai.slice(0, 4))}–${tanggalSingkat(P.sampai)}`;
    return {
      sub: `Penerimaan pajak ${P.tahun} · ${periode}`,
      tiles: [
        { label: "Total penerimaan", nilai: rupiahTile(r.jumlah), meta: `<span>${fmt.format(r.transaksi)} transaksi</span>` },
        { label: "PKB", nilai: rupiahTile(r.pkb), meta: `<span>${persenDari(r.pkb, r.jumlah)} dari total</span>` },
        { label: "BBNKB", nilai: rupiahTile(r.bbn), meta: `<span>${persenDari(r.bbn, r.jumlah)} dari total</span>` },
        { label: "Opsen kabupaten/kota", nilai: rupiahTile(opsen), meta: `<span>${persenDari(opsen, r.jumlah)} dari total</span>` },
      ],
      grafik: {
        judul: `Penerimaan per bulan ${P.tahun}`,
        satuan: "rupiah",
        aria: `Penerimaan per bulan, Januari–Desember ${P.tahun}`,
        batang: P.bulan.map((b, i) => ({
          x: BULAN[i],
          nilai: b.jumlah,
          tip: b.disamarkan ? "disamarkan" : b.jumlah === null ? "belum ada data" : rupiahRingkas(b.jumlah),
          aria: `${BULAN_PENUH[i]} ${P.tahun}: ${b.disamarkan ? "disamarkan" : b.jumlah === null ? "belum ada data" : `Rp${fmt.format(b.jumlah)}, ${fmt.format(b.transaksi)} transaksi`}`,
          sorot: i === bulanIni,
        })),
      },
      catatan: [`Dari Laporan Penerimaan yang ditampilkan di portal, data ${periode}.`, P.bulan.some((b) => b.disamarkan) ? "Bulan dengan kurang dari 3 transaksi disamarkan dan tidak ikut dijumlah." : ""].filter(Boolean),
      tautan: { href: "penerimaan.html", teks: "Lihat laporan penerimaan" },
    };
  }

  function isiPemetaan(M) {
    const k = M.kota;
    const wilayah = M.kecamatan.filter((x) => x.kunci);
    return {
      sub: `Pemetaan PKB · penetapan ${tanggalSingkat(M.tanggalPenetapan)}`,
      tiles: [
        { label: "Objek pajak", nilai: Number.isFinite(k.kendaraan) ? fmt.format(k.kendaraan) : "—", meta: "<span>kendaraan bermotor</span>" },
        { label: "Taat", nilai: Number.isFinite(k.taat) ? fmt.format(k.taat) : "—", meta: `<span>${persenDari(k.taat, k.kendaraan)} dari objek pajak</span>` },
        { label: "Menunggak ≥ 1 tahun", nilai: Number.isFinite(k.menunggak) ? fmt.format(k.menunggak) : "—", meta: `<span>${persenDari(k.menunggak, k.kendaraan)} dari objek pajak</span>` },
        { label: "Tagihan belum terbayar", nilai: rupiahTile(k.tagihan), meta: "<span>pokok, denda, dan tunggakan</span>" },
      ],
      grafik: {
        judul: "Menunggak ≥ 1 tahun per kecamatan",
        satuan: "% objek pajak",
        aria: "Persen kendaraan menunggak satu tahun atau lebih per kecamatan",
        batang: wilayah.map((x) => {
          const p = Number.isFinite(x.menunggak) && x.kendaraan > 0 ? (x.menunggak / x.kendaraan) * 100 : null;
          return { x: x.nama, nilai: p, tip: p === null ? "disamarkan" : `${fmtDesimal.format(p)}%`, aria: `${x.nama}: ${p === null ? "disamarkan" : `${fmtDesimal.format(p)}% menunggak`}`, sorot: false };
        }),
        wilayah: true,
      },
      catatan: [`Dari Pemetaan PKB yang ditampilkan di portal, data penetapan ${tanggalSingkat(M.tanggalPenetapan)}.`],
      tautan: { href: "pemetaan.html", teks: "Lihat pemetaan PKB per wilayah" },
    };
  }

  function initDash() {
    const dash = $("[data-dash]");
    if (!dash) return;
    const tabs = $$("[data-dash-tabs] [role=tab]");
    const tilesEl = $("[data-dash-tiles]", dash);
    const chart = $("[data-dash-chart]", dash);
    const barsEl = $("[data-bars]", dash);
    const tautan = $("[data-dash-tautan]", dash);
    const catatan = $("[data-dash-catatan]");
    const catatanBawaan = catatan ? catatan.textContent.replace(/\s+/g, " ").trim() : "";
    const subBawaan = $("[data-dash-sub]", dash).textContent.replace(/\s+/g, " ").trim();
    let set = tabs.find((t) => t.getAttribute("aria-selected") === "true")?.dataset.set ?? "transaksi";

    // Tab Penerimaan dan Pemetaan punya sumber sendiri; tab lain dari Statistik Layanan
    const SUMBER = {
      statistik: { muat: muatStatistik, memuat: "Memuat statistik layanan…", kosong: "Statistik layanan belum diterbitkan.", gagal: "Statistik layanan belum dapat dimuat. " },
      penerimaan: { muat: muatRingkasanPenerimaan, memuat: "Memuat ringkasan penerimaan…", kosong: "", gagal: "Ringkasan penerimaan belum dapat dimuat. " },
      pemetaan: { muat: muatRingkasanPemetaan, memuat: "Memuat ringkasan pemetaan PKB…", kosong: "", gagal: "Ringkasan pemetaan PKB belum dapat dimuat. " },
    };
    const sumberDari = (kunci) => (kunci in SUMBER && kunci !== "statistik" ? kunci : "statistik");
    const keadaan = Object.fromEntries(Object.keys(SUMBER).map((k) => [k, { status: "memuat", data: null }]));

    const isiTab = (kunci) => {
      const k = keadaan[sumberDari(kunci)];
      if (k.status !== "siap" || !k.data) return null;
      if (kunci === "penerimaan") return isiPenerimaan(k.data);
      if (kunci === "pemetaan") return isiPemetaan(k.data);
      const d = k.data.set.find((s) => s.kunci === kunci);
      return d ? isiStatistik(k.data, d) : null;
    };

    const pesan = (teks, tombol) => {
      tilesEl.innerHTML = "";
      const p = document.createElement("p");
      p.className = "dash__status";
      p.setAttribute("role", "status");
      p.textContent = teks;
      if (tombol) p.append(tombol);
      tilesEl.append(p);
      chart.hidden = true;
      if (tautan) tautan.hidden = true;
      $("[data-dash-sub]", dash).textContent = subBawaan;
      if (catatan) catatan.textContent = catatanBawaan;
    };

    const render = (fokusUlang) => {
      if (tabs.length && tabs.every((t) => t.hidden)) return pesan(SUMBER.statistik.kosong);
      const nama = sumberDari(set);
      const k = keadaan[nama];
      if (k.status === "memuat") return pesan(SUMBER[nama].memuat);
      if (k.status === "gagal") {
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "link";
        retry.textContent = "Coba lagi";
        retry.addEventListener("click", () => muat(nama, true));
        pesan(SUMBER[nama].gagal, retry);
        if (fokusUlang) retry.focus();
        return;
      }
      const isi = isiTab(set);
      if (!isi) return pesan(SUMBER[nama].kosong || SUMBER.statistik.kosong);

      $("[data-dash-sub]", dash).textContent = isi.sub;
      tilesEl.innerHTML = isi.tiles.map((t) => `<div class="tile">
          <p class="tile__label">${esc(t.label)}</p>
          <p class="tile__value">${t.nilai}</p>
          <p class="tile__meta">${t.meta}</p>
        </div>`).join("");

      const g = isi.grafik;
      $("[data-chart-title]", dash).textContent = g.judul;
      $("[data-chart-unit]", dash).textContent = g.satuan;
      const max = Math.max(0, ...g.batang.map((b) => b.nilai ?? 0)) || 1;
      barsEl.classList.toggle("bars--wilayah", !!g.wilayah);
      barsEl.style.setProperty("--kolom", String(g.batang.length));
      barsEl.innerHTML = g.batang.map((b) => {
        const h = b.nilai === null ? 0 : Math.max(3, (b.nilai / max) * 100);
        return `<div class="bar${b.sorot ? " is-now" : ""}${b.nilai === null ? " is-empty" : ""}" tabindex="0" style="--h:${h}%" aria-label="${esc(b.aria)}"><span class="bar__fill" style="height:${h}%"></span><span class="bar__tip">${esc(b.tip)}</span><span class="bar__x">${esc(b.x)}</span></div>`;
      }).join("");
      barsEl.setAttribute("aria-label", g.aria);
      chart.hidden = false;

      if (tautan) {
        tautan.hidden = !isi.tautan;
        if (isi.tautan) {
          tautan.href = isi.tautan.href;
          $("[data-dash-tautan-teks]", tautan).textContent = isi.tautan.teks;
        }
      }
      if (catatan) catatan.replaceChildren(...isi.catatan.map((teks) => Object.assign(document.createElement("span"), { textContent: teks })));
    };

    // Tab yang sumbernya belum ditampilkan di portal disembunyikan; tab terpilih pindah ke tab pertama yang tampil
    const pilih = (tab, fokus) => {
      tabs.forEach((t) => { t.setAttribute("aria-selected", String(t === tab)); t.tabIndex = t === tab ? 0 : -1; });
      set = tab.dataset.set;
      render();
      if (fokus) tab.focus();
    };
    const aturTab = () => {
      tabs.forEach((tab) => {
        const nama = sumberDari(tab.dataset.set);
        const kategori = keadaan.statistik.data?.kategori;
        tab.hidden = nama === "statistik"
          ? keadaan.statistik.status === "siap" && Array.isArray(kategori) && !kategori.includes(tab.dataset.set)
          : keadaan[nama].status === "siap" && keadaan[nama].data === null;
        const s = nama === "statistik" ? keadaan.statistik.data?.set?.find((x) => x.kunci === tab.dataset.set) : null;
        const txt = $(".catlist__txt", tab);
        if (s && txt) txt.textContent = s.label;
      });
      const aktif = tabs.find((t) => t.dataset.set === set);
      if (aktif?.hidden) {
        const pertama = tabs.find((t) => !t.hidden);
        if (pertama) pilih(pertama);
      }
    };

    const muat = async (nama, fokus) => {
      keadaan[nama] = { status: "memuat", data: null };
      if (sumberDari(set) === nama) render();
      if (nama === sumberDari(set)) dash.setAttribute("aria-busy", "true");
      try {
        const data = await SUMBER[nama].muat();
        // Statistik yang belum terbit tetap disimpan: daftar kategorinya menentukan tab yang tampil
        keadaan[nama] = { status: "siap", data };
      } catch {
        keadaan[nama] = { status: "gagal", data: null };
      } finally {
        if (nama === sumberDari(set)) dash.removeAttribute("aria-busy");
      }
      aturTab();
      if (sumberDari(set) === nama) render(fokus);
    };

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => pilih(tab));
      tab.addEventListener("keydown", (e) => {
        const dir = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }[e.key];
        if (!dir) return;
        e.preventDefault();
        const tampil = tabs.filter((t) => !t.hidden);
        const next = tampil[(tampil.indexOf(tab) + dir + tampil.length) % tampil.length];
        pilih(next, true);
      });
    });
    render();
    Object.keys(SUMBER).forEach((nama) => muat(nama, false));
  }

  /* ---------- Dialog ---------- */
  function initDialogs() {
    $$("[data-open-dialog]").forEach((b) => b.addEventListener("click", () => {
      const dlg = document.getElementById(b.dataset.openDialog);
      if (dlg?.showModal) dlg.showModal();
    }));
    $$("dialog").forEach((dlg) => {
      dlg.addEventListener("click", (e) => { if (e.target === dlg) dlg.close(); });
      $$("[data-close]", dlg).forEach((c) => c.addEventListener("click", () => dlg.close()));
    });
  }

  /* ---------- Lelucon penutup: "Sudah bawa map?" ---------- */
  function initGag() {
    const btn = $("[data-gag]");
    if (!btn) return;
    const text = $("[data-gag-text]");
    const steps = [["Cek lagi", "Sudah bawa map?"], ["Cek sekali lagi", "Yakin? Fotokopi KTP juga?"], ["Siap berangkat", "Map ada. Fotokopi ada. Berangkat."], ["Cek lagi", "Sudah bawa map?"]];
    let i = 0;
    btn.addEventListener("click", () => {
      i = (i + 1) % steps.length;
      btn.textContent = steps[i][0];
      text.textContent = steps[i][1];
      btn.closest(".gag")?.classList.toggle("is-done", i === 2);
    });
  }

  /* ---------- Filter chip (berita, galeri, unduhan) ---------- */
  function initFilters() {
    $$("[data-filter-group]").forEach((group) => {
      const target = document.getElementById(group.dataset.filterGroup);
      if (!target) return;
      const chips = $$("[data-filter]", group);
      const search = $(`[data-filter-search="${group.dataset.filterGroup}"]`);
      const empty = $(`[data-filter-empty="${group.dataset.filterGroup}"]`);
      let cat = "semua";
      const apply = () => {
        const q = (search?.value || "").trim().toLowerCase();
        let shown = 0;
        $$("[data-cat]", target).forEach((it) => {
          const okCat = cat === "semua" || it.dataset.cat.split(" ").includes(cat);
          const okQ = !q || it.textContent.toLowerCase().includes(q);
          it.hidden = !(okCat && okQ);
          if (!it.hidden) shown++;
        });
        if (empty) empty.hidden = shown > 0;
      };
      chips.forEach((c) => c.addEventListener("click", () => {
        chips.forEach((x) => x.setAttribute("aria-pressed", String(x === c)));
        cat = c.dataset.filter; apply();
      }));
      search?.addEventListener("input", apply);
    });
  }

  /* ---------- Galeri + lightbox ---------- */
  function initGallery() {
    const dlg = $("#lightbox");
    if (!dlg) return;
    const items = () => $$(".gallery__item").filter((x) => !x.hidden);
    let idx = 0;
    const show = (i) => {
      const list = items();
      if (!list.length) return;
      idx = (i + list.length) % list.length;
      const it = list[idx];
      const media = $(".lightbox__media", dlg);
      media.className = "ph lightbox__media " + ($(".ph", it).className.match(/ph--\w+/)?.[0] || "");
      // Foto kegiatan dari dashboard (data-src); tanpa foto, bingkai ilustrasi saja
      const foto = document.createElement("img");
      foto.src = it.dataset.src ?? "";
      foto.alt = it.dataset.title;
      media.replaceChildren(...(it.dataset.src ? [foto] : []));
      $("[data-lb-title]", dlg).textContent = it.dataset.title;
      $("[data-lb-meta]", dlg).textContent = it.dataset.meta;
      $("[data-lb-count]", dlg).textContent = `${idx + 1} / ${list.length}`;
    };
    // Lewat galeri, karena item dipasang skrip setelah halaman dimuat (initDokumentasi)
    $$(".gallery").forEach((g) => g.addEventListener("click", (e) => {
      const it = e.target.closest(".gallery__item");
      if (!it) return;
      show(items().indexOf(it));
      dlg.showModal();
    }));
    $("[data-lb-prev]", dlg).addEventListener("click", () => show(idx - 1));
    $("[data-lb-next]", dlg).addEventListener("click", () => show(idx + 1));
    dlg.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") show(idx - 1);
      if (e.key === "ArrowRight") show(idx + 1);
    });
  }

  /* ---------- Tanya jawab di beranda ----------
     Dikelola di dashboard dan dibaca dari GET /api/public/faq, yang hanya
     memberikan tanya jawab terbit, untuk warga, dan ditampilkan. Teksnya
     dipasang sebagai teks polos. Tidak ada daftar cadangan: bila API gagal,
     pengunjung melihat pesan dan tombol coba lagi; bila kosong, bagian ini
     disembunyikan. */
  function initFaq() {
    const section = $("[data-faq]");
    if (!section) return;
    const list = $("[data-faq-list]", section);
    const status = $("[data-faq-status]", section);
    const template = $("template[data-faq-item]", section);

    const retry = document.createElement("button");
    retry.type = "button";
    retry.className = "link";
    retry.textContent = "Coba lagi";

    const tampilkan = (entri) => {
      list.textContent = "";
      list.append(...entri.map(({ pertanyaan, jawaban }) => {
        const item = template.content.firstElementChild.cloneNode(true);
        $("[data-faq-question]", item).textContent = pertanyaan;
        $("[data-faq-answer]", item).textContent = jawaban;
        return item;
      }));
    };

    const muat = async (fokus) => {
      const batas = new AbortController();
      const waktu = setTimeout(() => batas.abort(), 10000);
      list.setAttribute("aria-busy", "true");
      status.textContent = "Memuat tanya jawab…";
      status.hidden = false;
      try {
        const res = await fetch("/api/public/faq", { headers: { Accept: "application/json" }, credentials: "omit", signal: batas.signal });
        if (!res.ok) throw new Error(`Status ${res.status}`);
        const data = await res.json();
        if (!Array.isArray(data)) throw new Error("Respons bukan daftar");
        const entri = data.filter((f) => typeof f?.pertanyaan === "string" && typeof f?.jawaban === "string");
        if (entri.length) tampilkan(entri);
        else section.hidden = true;
      } catch {
        status.textContent = "Tanya jawab belum dapat dimuat. ";
        status.append(retry);
        if (fokus) retry.focus();
      } finally {
        clearTimeout(waktu);
        list.removeAttribute("aria-busy");
      }
    };

    retry.addEventListener("click", () => muat(true));
    muat(false);
  }

  /* ---------- Login Pegawai ----------
     NIP dan kata sandi diperiksa server. Sesinya berupa cookie Laravel Sanctum
     yang juga dipakai dashboard (satu origin); lihat routes/api.php. */

  /** Header CSRF dari cookie XSRF-TOKEN yang dipasang Laravel */
  function headerXsrf() {
    const cocok = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return cocok ? { "X-XSRF-TOKEN": decodeURIComponent(cocok[1]) } : {};
  }

  /* Server mengenali permintaan dari portal lewat Referer, jadi dikirim untuk origin sendiri
     walau halaman kelak memakai kebijakan referrer yang lebih ketat. */
  const SAMA_ORIGIN = { credentials: "same-origin", referrerPolicy: "same-origin" };

  /** true bila peramban ini sudah punya sesi login yang berlaku */
  async function sudahMasuk() {
    try {
      const res = await fetch("/api/sesi", { ...SAMA_ORIGIN, headers: { Accept: "application/json" } });
      return res.ok;
    } catch { return false; }
  }

  async function masuk(nip, sandi, ingat) {
    await fetch("/sanctum/csrf-cookie", SAMA_ORIGIN);
    return fetch("/api/masuk", {
      ...SAMA_ORIGIN,
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json", ...headerXsrf() },
      body: JSON.stringify({ nip, sandi, ingat }),
    });
  }

  /** Pesan untuk pengguna dari respons login yang gagal */
  async function pesanGagal(res) {
    if (res.status === 419) return "Halaman ini sudah terlalu lama dibuka. Muat ulang halaman lalu coba lagi.";
    const data = await res.json().catch(() => ({}));
    return data.errors?.nip?.[0] ?? data.errors?.sandi?.[0] ?? data.message ?? `Login gagal (${res.status}).`;
  }

  /** Tujuan setelah login: hanya halaman di dalam dashboard (cegah pengalihan ke situs lain) */
  function tujuanDashboard() {
    let lanjut = new URLSearchParams(location.search).get("lanjut") || "";
    // Pengalihan dari server membawa layar dashboard sebagai hash halaman ini (/login?lanjut=dashboard%2F#/berita)
    if (lanjut === "dashboard/" && /^#\/[\w\-\/?=&%.]*$/.test(location.hash)) lanjut += location.hash;
    // Petugas yang masuk dari aplikasi absensi kembali ke sana (D-034)
    if (lanjut === "absen") return "absen";
    return /^dashboard\/(#\/[\w\-\/?=&%.]*)?$/.test(lanjut) ? lanjut : "dashboard/";
  }

  /* ---------- Formulir ---------- */
  function initForms() {
    const login = $("[data-login-form]");
    if (login) {
      const params = new URLSearchParams(location.search);
      // Setelah keluar atau aktivasi, tetap di formulir walau peramban ini masih punya sesi lain.
      if (!params.has("keluar") && !params.has("aktif")) {
        sudahMasuk().then((ya) => { if (ya) location.replace(tujuanDashboard()); });
      }
      const nip = $("#nip", login), pass = $("#sandi", login), alert = $("[data-login-alert]"), info = $("[data-login-info]");
      const tampilInfo = (teks) => { $("[data-login-info-msg]").textContent = teks; info.classList.add("is-on"); };
      if (params.has("keluar")) tampilInfo("Anda sudah keluar dari dashboard. Sampai jumpa.");
      else if (params.get("sesi") === "berakhir") tampilInfo("Sesi Anda berakhir. Silakan masuk kembali.");
      else if (params.has("aktif")) tampilInfo("Akun Anda sudah aktif. Silakan masuk dengan kata sandi baru.");

      const galat = (teks, el) => {
        info.classList.remove("is-on");
        $("[data-login-msg]").textContent = teks;
        alert.classList.add("is-on");
        [nip, pass].forEach((x) => x.removeAttribute("aria-invalid"));
        if (el) { el.setAttribute("aria-invalid", "true"); el.focus(); }
      };

      $("[data-toggle-pass]", login)?.addEventListener("click", (e) => {
        const show = pass.type === "password";
        pass.type = show ? "text" : "password";
        e.currentTarget.setAttribute("aria-label", show ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
        $("use", e.currentTarget).setAttribute("href", show ? "#i-eye-off" : "#i-eye");
      });
      [nip, pass].forEach((el) => el.addEventListener("input", () => { el.removeAttribute("aria-invalid"); alert.classList.remove("is-on"); }));
      nip.addEventListener("input", () => { nip.value = nip.value.replace(/\D/g, "").slice(0, 18); });
      $("[data-sso]", login)?.addEventListener("click", () => toast("SSO Pemprov belum tersedia. Masuk dengan NIP dan kata sandi."));

      login.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (!/^\d{18}$/.test(nip.value)) return galat("NIP harus 18 digit angka.", nip);
        if (!pass.value) return galat("Kata sandi wajib diisi.", pass);
        const btn = $("button[type=submit]", login);
        const label = btn.textContent;
        btn.disabled = true;
        btn.setAttribute("aria-disabled", "true");
        btn.textContent = "Memeriksa…";
        try {
          const res = await masuk(nip.value, pass.value, !!login.elements.ingat?.checked);
          if (res.ok) {
            btn.textContent = "Membuka dashboard…";
            location.href = tujuanDashboard();
            return;
          }
          pass.value = "";
          galat(await pesanGagal(res), pass);
        } catch {
          galat("Server tidak dapat dihubungi. Periksa koneksi lalu coba lagi.");
        }
        btn.disabled = false;
        btn.removeAttribute("aria-disabled");
        btn.textContent = label;
      });
    }
  }

  /* ---------- Formulir pesan & pengaduan ----------
     Dikirim ke POST /api/public/pesan dan masuk ke Pesan & Pengaduan di
     dashboard. Isian tersembunyi "kode_tambahan" hanya diisi bot. */
  function initPesanForm() {
    const form = $("[data-pesan-form]");
    if (!form) return;
    const alert = $("[data-pesan-alert]", form);
    const ok = $("[data-pesan-ok]", form);
    const btn = $("button[type=submit]", form);
    const isiTombol = btn.innerHTML;

    const galat = (teks, el) => {
      ok.classList.remove("is-on");
      $("[data-pesan-msg]", form).textContent = teks;
      alert.classList.add("is-on");
      if (el instanceof HTMLElement) { el.setAttribute("aria-invalid", "true"); el.focus(); }
    };
    form.addEventListener("input", (e) => {
      e.target.removeAttribute?.("aria-invalid");
      alert.classList.remove("is-on");
    });

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!form.checkValidity()) { form.reportValidity(); return; }
      const f = form.elements;
      btn.disabled = true;
      btn.setAttribute("aria-disabled", "true");
      btn.textContent = "Mengirim…";
      try {
        const res = await fetch("/api/public/pesan", {
          method: "POST",
          credentials: "omit",
          headers: { Accept: "application/json", "Content-Type": "application/json" },
          body: JSON.stringify({
            jenis: f.jenis.value,
            nama: f.nama.value,
            kontak: f.kontak.value,
            layanan: f.layanan.value,
            isi: f.isi.value,
            persetujuan: f.persetujuan.checked,
            kode_tambahan: f.kode_tambahan.value,
          }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.status === 201 && typeof data.tiket === "string") {
          form.reset();
          alert.classList.remove("is-on");
          $("[data-pesan-ok-msg]", form).textContent = `Terima kasih, pesan Anda kami terima dengan nomor tiket ${data.tiket}. Simpan nomor ini; kami membalas ke kontak Anda paling lambat tiga hari kerja.`;
          ok.classList.add("is-on");
          toast(`Pesan terkirim. Nomor tiket ${data.tiket}.`);
        } else if (res.status === 422 && data.errors) {
          const [nama, daftar] = Object.entries(data.errors)[0];
          galat(daftar[0], f[nama]);
        } else {
          galat(typeof data.message === "string" ? data.message : "Pesan belum terkirim. Coba lagi sebentar lagi.");
        }
      } catch {
        galat("Pesan belum terkirim. Periksa koneksi lalu coba lagi.");
      }
      btn.disabled = false;
      btn.removeAttribute("aria-disabled");
      btn.innerHTML = isiTombol;
    });
  }

  /* ---------- Login & aktivasi: kartu selalu muat dalam satu layar ---------- */
  // Halaman tidak bergulir (lihat .auth di pages.css). Bila kartu tetap lebih
  // tinggi dari ruangnya, misalnya ponsel mendatar atau pesan galat panjang,
  // kartu dikecilkan agar tombolnya tidak terpotong.
  function initAuthFit() {
    const card = $(".auth__card");
    const area = card?.parentElement;
    if (!card || !("ResizeObserver" in window)) return;
    const muatkan = () => {
      card.style.zoom = "";
      const gaya = getComputedStyle(area);
      const ruang = area.clientHeight - parseFloat(gaya.paddingTop) - parseFloat(gaya.paddingBottom);
      if (card.offsetHeight > ruang) card.style.zoom = String(Math.max(ruang / card.offsetHeight, 0.6));
    };
    new ResizeObserver(muatkan).observe(area);
    new MutationObserver(muatkan).observe(card, { subtree: true, childList: true, attributeFilter: ["class"] });
  }

  /* ---------- Warna bilah peramban mengikuti latar tema (D-057) ---------- */
  function initWarnaBilah() {
    const meta = document.querySelector('meta[name="theme-color"]');
    const latar = getComputedStyle(document.documentElement).getPropertyValue("--paper").trim();
    if (meta && latar) meta.setAttribute("content", latar);
  }

  /* ---------- Bagikan / salin tautan / cetak ---------- */
  function initUtilities() {
    $$("[data-copy-link]").forEach((b) => b.addEventListener("click", async () => {
      try { await navigator.clipboard.writeText(window.location.href); toast("Tautan disalin."); }
      catch { toast("Salin tautan dari bilah alamat."); }
    }));
    $$("[data-print]").forEach((b) => b.addEventListener("click", () => window.print()));
    $$("[data-year]").forEach((el) => (el.textContent = nowWita().y));
    $$("[data-today]").forEach((el) => {
      const t = nowWita();
      el.textContent = `${HARI[t.wd]}, ${t.d} ${BULAN_PENUH[t.mo - 1]} ${t.y}`;
    });
    $$("[data-spy]").forEach(pasangSpy);
  }

  /** Sorot layanan aktif di navigasi samping saat menggulir; dipanggil lagi untuk navigasi dari API */
  function pasangSpy(nav) {
    const links = $$("a[href^='#']", nav);
    if (!links.length || !("IntersectionObserver" in window)) return;
    const map = new Map(links.map((a) => [a.getAttribute("href").slice(1), a]));
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (!en.isIntersecting) return;
        links.forEach((a) => a.removeAttribute("aria-current"));
        map.get(en.target.id)?.setAttribute("aria-current", "true");
      });
    }, { rootMargin: "-30% 0px -60% 0px" });
    map.forEach((_, id) => { const el = document.getElementById(id); if (el) io.observe(el); });
  }

  document.addEventListener("DOMContentLoaded", () => {
    initNav(); initReveal(); initTabs(); initChecklists(); initStatus(); initPapan(); initKeliling(); initKontak(); initVisiMisi(); initProfil(); initLayanan(); initDokumen(); initInformasi(); initBerita(); initPengumumanBeranda(); initDokumentasi(); initStruktur(); initAplikasi(); initBeranda(); initHalaman(); initSitus();
    initDash(); initDialogs(); initGag(); initFilters(); initGallery(); initFaq(); initForms(); initPesanForm(); initAuthFit(); initUtilities(); initWarnaBilah();
  });
})();
