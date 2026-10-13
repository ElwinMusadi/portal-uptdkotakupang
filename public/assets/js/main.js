/* ==========================================================================
   PINTU — Interaksi
   Tanpa dependensi. Setiap modul hanya aktif bila elemennya ada di halaman.
   ========================================================================== */
(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const fmt = new Intl.NumberFormat("id-ID");
  const rupiah = (n) => "Rp" + fmt.format(Math.round(n));
  const compact = new Intl.NumberFormat("id-ID", { notation: "compact", maximumFractionDigits: 1 });
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

    muatJam().then((data) => {
      JAM = data;
      tampilkan();
      tick();
      setInterval(tick, 1000);
    }, gagal);
  }

  /* ---------- Papan layanan di beranda ----------
     Dikelola di dashboard dan dibaca dari GET /api/public/papan. Nomor
     antrean, waktu tunggu, dan status loket hanya ditampilkan pada hari yang
     sama dengan pembaruannya (WITA); papan yang belum diperbarui hari ini
     hanya menyebut waktu pembaruan terakhirnya. */
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
      const jam = `${pad(w.h)}.${pad(w.m)} WITA`;
      waktu.textContent = hariIni ? `Diperbarui ${jam}` : `Diperbarui ${w.d} ${BULAN[w.mo - 1]} ${w.y} · ${jam}`;
      if (catatan) catatan.textContent = typeof p.catatan === "string" ? p.catatan : "";
      if (!hariIni) {
        kosongkan("Belum diperbarui hari ini.");
        return;
      }
      nomor.textContent = p.nomorAntrean || "—";
      layanan.textContent = typeof p.layananAntrean === "string" ? p.layananAntrean : "";
      tunggu.textContent = Number.isInteger(p.waktuTunggu) ? String(p.waktuTunggu) : "—";
      const beda = Number.isInteger(p.selisihKemarin) ? p.selisihKemarin : 0;
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
        const [label, kelas] = STATUS_LOKET[l?.status] ?? ["—", "board__off"];
        return `<li><span class="board__lnum mono">${esc(l.kode)}</span><span>${esc(l.nama)}</span><span class="${kelas}">${label}</span></li>`;
      }).join("");
    };

    ambilJson("/api/public/papan").then(tampilkan, () => {
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
     setelah isinya dimuat. Bila API gagal, keterangan dan tombol coba lagi. */
  const KATA_ANGKA = ["Nol", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh"];
  const kataAngka = (n) => KATA_ANGKA[n] ?? String(n);

  async function muatVisiMisi() {
    const d = await ambilJson("/api/public/visi-misi");
    const teks = (x) => typeof x === "string";
    const daftar = (xs, isian) => Array.isArray(xs) && xs.every((x) => isian.every((f) => teks(x?.[f])));
    if (!teks(d?.sumberVisi) || !teks(d.visi) || !teks(d.maklumat?.teks) || !teks(d.maklumat.penandatangan)
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
     GET /api/public/profil (D-021). Sebelum disimpan, API memberikan teks yang
     sama dengan halaman sebelumnya, tanpa nama Kepala UPTD contoh. Foto masih
     ilustrasi sampai unggahan tersedia di server. Teks dipasang sebagai teks
     polos; bila API gagal, keterangan dan tombol coba lagi. */
  async function muatProfil() {
    const p = await ambilJson("/api/public/profil");
    const teks = (x) => typeof x === "string";
    const daftar = (xs, isian) => Array.isArray(xs) && xs.every((x) => isian.every((f) => teks(x?.[f])));
    if (!["judul", "lanjutan", "pengantar", "keteranganFoto"].every((f) => teks(p?.[f]))
      || !["judul", "lanjutan", "deskripsi"].every((f) => teks(p.kedudukan?.[f]))
      || !["kutipan", "nama", "jabatan"].every((f) => teks(p.sambutan?.[f]))
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

  /* ---------- Samsat Keliling ----------
     Jadwal dikelola di dashboard dan dibaca dari GET /api/public/keliling,
     yang hanya memberikan lokasi aktif. Teksnya di-escape sebelum dipasang.
     Tidak ada jadwal cadangan: bila API gagal, pengunjung melihat pesan dan
     tombol coba lagi. */
  async function muatKeliling() {
    const data = await ambilJson("/api/public/keliling");
    if (!Array.isArray(data)) throw new Error("Respons bukan daftar");
    return data.filter((s) => Number.isInteger(s?.hari) && ["mulai", "selesai", "lokasi", "kecamatan", "catatan"].every((k) => typeof s[k] === "string"));
  }

  // Senin–Jumat, ditambah Sabtu bila ada jadwalnya. Bila hari terakhir minggu ini
  // sudah lewat (atau hari Minggu), tampilkan jadwal minggu depan.
  function mingguKeliling(stops, t) {
    if (!stops.length) return '<p class="muted">Belum ada jadwal Samsat Keliling.</p>';
    const hari = [1, 2, 3, 4, 5, ...(stops.some((s) => s.hari === 6) ? [6] : [])];
    const mingguDepan = t.wd === 0 || t.wd > hari[hari.length - 1];
    const base = new Date(Date.UTC(t.y, t.mo - 1, t.d));
    const monday = new Date(base);
    monday.setUTCDate(base.getUTCDate() + (mingguDepan ? (8 - t.wd) % 7 : -((t.wd + 6) % 7)));
    return hari.map((wd, i) => {
      const d = new Date(monday); d.setUTCDate(monday.getUTCDate() + i);
      const state = mingguDepan ? "" : wd === t.wd ? "is-today" : wd < t.wd ? "is-past" : "";
      const label = state === "is-today" ? '<span class="tag tag--accent">Hari ini</span>' : state === "is-past" ? '<span class="tag">Selesai</span>' : '<span class="tag tag--line">Terjadwal</span>';
      const titik = stops.filter((s) => s.hari === wd);
      const isi = titik.length
        ? titik.map((s) => `
        <div class="stop">
          <p class="stop__time"><span>${esc(rentang(s))} WITA</span>${label}</p>
          <strong>${esc(s.lokasi)}</strong><span>${esc(s.kecamatan)}</span>${s.catatan ? `<span>${esc(s.catatan)}</span>` : ""}
          <a class="link-arrow" href="https://maps.google.com/?q=${esc(encodeURIComponent(s.lokasi + " Kota Kupang"))}" target="_blank" rel="noopener">Buka peta<svg class="i" aria-hidden="true"><use href="#i-arrow-up-right"/></svg></a>
        </div>`).join("")
        : '<p class="muted">Tidak ada jadwal.</p>';
      return `<div class="week__day ${state}"><p class="week__date"><strong>${HARI[wd]}</strong><span>${d.getUTCDate()} ${BULAN[d.getUTCMonth()]} ${d.getUTCFullYear()}</span></p><div class="week__stops">${isi}</div></div>`;
    }).join("");
  }

  function initKeliling() {
    const counts = $$("[data-keliling-count]");
    const todays = $$("[data-keliling-today]");
    const week = $("[data-keliling-week]");
    if (!(counts.length || todays.length || week)) return;

    const tampilkan = (stops) => {
      const t = nowWita();
      const pada = (wd) => stops.filter((s) => s.hari === wd);
      const today = pada(t.wd);
      const berikut = [1, 2, 3, 4, 5, 6].map((i) => (t.wd + i) % 7).find((wd) => pada(wd).length);
      counts.forEach((el) => (el.textContent = today.length ? `${today.length} titik` : "Tidak ada hari ini"));
      todays.forEach((ul) => {
        ul.innerHTML = today.length
          ? today.map((s) => `<li><span class="board__time mono">${esc(rentang(s))}</span><span><strong>${esc(s.lokasi)}</strong><small>${esc(s.kecamatan)}</small></span></li>`).join("")
          : `<li><span class="board__time mono">${HARI[t.wd]}</span><span><strong>Tidak ada Samsat Keliling hari ini.</strong><small>${berikut === undefined ? "Belum ada jadwal." : `Jadwal berikutnya hari ${HARI[berikut]}.`}</small></span></li>`;
      });
      if (week) week.innerHTML = mingguKeliling(stops, t);
    };

    const gagal = () => {
      counts.forEach((el) => (el.textContent = "Tidak tersedia"));
      todays.forEach((ul) => (ul.innerHTML = '<li><span class="board__time mono">—</span><span><strong>Jadwal belum dapat dimuat.</strong><small>Coba muat ulang halaman.</small></span></li>'));
      if (!week) return;
      week.innerHTML = '<p class="muted" role="status">Jadwal Samsat Keliling belum dapat dimuat. <button type="button" class="link" data-keliling-retry>Coba lagi</button></p>';
      $("[data-keliling-retry]", week).addEventListener("click", () => muat(true));
    };

    const muat = async (fokus) => {
      if (week) {
        week.setAttribute("aria-busy", "true");
        if (fokus) week.innerHTML = '<p class="muted" role="status">Memuat jadwal minggu ini…</p>';
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
     Statistik dikelola di dashboard (Statistik Layanan) dan dibaca dari
     GET /api/public/statistik, yang hanya memberikan angka berstatus
     terverifikasi pimpinan (D-018). Tidak ada angka cadangan: selama dimuat,
     sebelum diterbitkan, atau bila API gagal, dasbor menampilkan keterangannya.
     Grafik memuat 12 nilai bulanan (Januari–Desember) tahun data tersebut. */
  async function muatStatistik() {
    const data = await ambilJson("/api/public/statistik");
    if (typeof data?.tahun !== "string" || typeof data.catatan !== "string" || !Array.isArray(data.set)) throw new Error("Respons tidak dikenal");
    const angka = (n) => Number.isFinite(n);
    const teks = (s) => typeof s === "string";
    const lengkap = data.set.every((s) => ["kunci", "label", "sub", "judulGrafik", "satuan"].every((k) => teks(s?.[k]))
      && Array.isArray(s.seri) && s.seri.length === 12 && s.seri.every(angka)
      && Array.isArray(s.tiles) && s.tiles.every((t) => teks(t?.label) && teks(t.satuan) && angka(t.nilai) && angka(t.perubahan) && typeof t.naikBaik === "boolean"));
    if (!lengkap) throw new Error("Respons tidak dikenal");
    return data;
  }

  // "M" berarti miliar rupiah: di bawah 1 miliar ditampilkan dalam juta
  const nilaiTile = (v, satuan) => {
    const u = satuan.trim();
    if (u === "M") return v >= 1 ? `${fmt.format(Math.round(v * 10) / 10)}<small>miliar</small>` : `${fmt.format(Math.round(v * 1000))}<small>juta</small>`;
    return `${fmt.format(Math.round(v * 10) / 10)}${u ? `<small>${esc(u)}</small>` : ""}`;
  };
  const nilaiBatang = (v, satuan) => (satuan === "juta rupiah" ? `${fmt.format(v)} jt` : satuan === "menit" ? `${fmt.format(v)} mnt` : fmt.format(v));

  function initDash() {
    const dash = $("[data-dash]");
    if (!dash) return;
    const tabs = $$("[data-dash-tabs] [role=tab]");
    const tilesEl = $("[data-dash-tiles]", dash);
    const chart = $("[data-dash-chart]", dash);
    const barsEl = $("[data-bars]", dash);
    const catatan = $("[data-dash-catatan]");
    const catatanBawaan = catatan ? catatan.textContent.replace(/\s+/g, " ").trim() : "";
    let D = null;
    let set = tabs.find((t) => t.getAttribute("aria-selected") === "true")?.dataset.set ?? "transaksi";

    const keadaan = (pesan, tombol) => {
      D = null;
      tilesEl.innerHTML = "";
      const p = document.createElement("p");
      p.className = "dash__status";
      p.setAttribute("role", "status");
      p.textContent = pesan;
      if (tombol) p.append(tombol);
      tilesEl.append(p);
      chart.hidden = true;
      if (catatan) catatan.textContent = catatanBawaan;
    };

    const render = () => {
      const d = D?.set.find((s) => s.kunci === set);
      if (!d) return;
      $("[data-dash-sub]", dash).textContent = d.sub;
      $("[data-chart-title]", dash).textContent = d.judulGrafik;
      $("[data-chart-unit]", dash).textContent = d.satuan;
      tilesEl.innerHTML = d.tiles.map((t) => {
        const good = (t.perubahan >= 0) === t.naikBaik;
        return `<div class="tile">
          <p class="tile__label">${esc(t.label)}</p>
          <p class="tile__value">${nilaiTile(t.nilai, t.satuan)}</p>
          <p class="tile__meta"><span class="delta ${good ? "delta--up" : "delta--down"}"><svg class="i" aria-hidden="true"><use href="#i-trend-${t.perubahan >= 0 ? "up" : "down"}"/></svg>${t.perubahan > 0 ? "+" : ""}${fmt.format(t.perubahan)}%</span><span>vs periode lalu</span></p>
        </div>`;
      }).join("");

      // Bulan berjalan disorot bila tahun datanya tahun ini (WITA)
      const t = nowWita();
      const bulanIni = String(t.y) === D.tahun ? t.mo - 1 : -1;
      const max = Math.max(...d.seri) || 1;
      barsEl.innerHTML = d.seri.map((v, i) => {
        const h = Math.max(3, (v / max) * 100);
        const val = nilaiBatang(v, d.satuan);
        const label = `${BULAN[i]} ${D.tahun}: ${fmt.format(v)} ${d.satuan}`.trim();
        return `<div class="bar${i === bulanIni ? " is-now" : ""}" tabindex="0" style="--h:${h}%" aria-label="${esc(label)}"><span class="bar__fill" style="height:${h}%"></span><span class="bar__tip">${esc(val)}</span><span class="bar__x">${BULAN[i]}</span></div>`;
      }).join("");
      barsEl.setAttribute("aria-label", `${d.judulGrafik}, Januari–Desember ${D.tahun}`);
      chart.hidden = false;
    };

    const tampilkan = (data) => {
      D = data;
      tabs.forEach((tab) => {
        const s = D.set.find((x) => x.kunci === tab.dataset.set);
        const txt = $(".catlist__txt", tab);
        if (s && txt) txt.textContent = s.label;
      });
      if (catatan) {
        const w = nowWita(new Date(D.diperbarui));
        const waktu = document.createElement("span");
        waktu.textContent = `Data tahun ${D.tahun}, terverifikasi pimpinan · diperbarui ${w.d} ${BULAN[w.mo - 1]} ${w.y}.`;
        catatan.replaceChildren(waktu);
        if (D.catatan) {
          const isi = document.createElement("span");
          isi.textContent = D.catatan;
          catatan.append(isi);
        }
      }
      render();
    };

    const muat = async (fokus) => {
      keadaan("Memuat statistik layanan…");
      dash.setAttribute("aria-busy", "true");
      try {
        const data = await muatStatistik();
        if (typeof data.diperbarui !== "string" || !data.set.length) keadaan("Statistik layanan belum diterbitkan.");
        else tampilkan(data);
      } catch {
        const retry = document.createElement("button");
        retry.type = "button";
        retry.className = "link";
        retry.textContent = "Coba lagi";
        retry.addEventListener("click", () => muat(true));
        keadaan("Statistik layanan belum dapat dimuat. ", retry);
        if (fokus) retry.focus();
      } finally {
        dash.removeAttribute("aria-busy");
      }
    };

    tabs.forEach((tab, i, all) => {
      const pick = (focus) => {
        all.forEach((t) => { t.setAttribute("aria-selected", String(t === tab)); t.tabIndex = t === tab ? 0 : -1; });
        set = tab.dataset.set; render();
        if (focus) tab.focus();
      };
      tab.addEventListener("click", () => pick());
      tab.addEventListener("keydown", (e) => {
        const dir = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }[e.key];
        if (!dir) return;
        e.preventDefault();
        const next = all[(i + dir + all.length) % all.length];
        next.click(); next.focus();
      });
    });
    muat(false);
  }

  /* ---------- Teks angka yang menyesuaikan lebar wadah ----------
     Angka rupiah tidak boleh terpotong. Bila lebih lebar dari wadahnya
     (nilai besar, layar sempit, atau font sistem yang lebih lebar),
     ukuran huruf dikecilkan bertahap sampai muat, dengan batas bawah. */
  function overflows(el) {
    const cs = getComputedStyle(el);
    const avail = el.getBoundingClientRect().width - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
    const range = document.createRange();
    range.selectNodeContents(el);
    return range.getBoundingClientRect().width > avail + 0.25;
  }
  function fitText(el, min = 14) {
    el.style.fontSize = "";
    if (!el.clientWidth) return;
    let size = parseFloat(getComputedStyle(el).fontSize);
    while (overflows(el) && size - 1 >= min) {
      size -= 1;
      el.style.fontSize = `${size}px`;
    }
  }

  /* ---------- Simulasi PKB ----------
     Tarif dikelola di dashboard (Tarif & Simulasi) dan dibaca dari
     GET /api/public/tarif. Rumusnya sama dengan Kalkulator PKB di dashboard
     (resources/js/dashboard/lib/pajak.ts). Tidak ada tarif cadangan: selama
     dimuat, sebelum tarif pertama kali disimpan petugas, atau bila API gagal,
     simulasi dinonaktifkan dan keterangannya tampil di bawah kanvas. */
  const persenFmt = new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 });
  const persen = (n) => `${persenFmt.format(n)}%`;

  async function muatTarif() {
    const t = await ambilJson("/api/public/tarif");
    const angka = ["opsenPersen", "dendaPersenPerBulan", "maksBulanDenda", "swdklljMotor", "swdklljMobil", "contohPokok", "contohTelat"];
    if (!angka.every((k) => Number.isFinite(t?.[k]) && t[k] >= 0) || !["motor", "mobil"].includes(t.contohJenis) || typeof t.catatan !== "string") {
      throw new Error("Respons tidak dikenal");
    }
    return t;
  }

  /** Satu kanvas [data-sim]; aktif hanya setelah menerima tarif */
  function simulasi(root) {
    let T = null;
    const state = { pokok: 0, jenis: "motor", telat: 0, putih: false };
    const awal = () => ({ pokok: T.contohPokok, jenis: T.contohJenis, telat: Math.min(T.contohTelat, T.maksBulanDenda), putih: false });
    const kontrol = $$("input, button", root);
    const catatan = root.parentElement && $("[data-sim-catatan]", root.parentElement);
    const catatanBawaan = catatan ? catatan.textContent.replace(/\s+/g, " ").trim() : "";
    const inPokok = $("[data-sim-pokok]", root);
    const outTelat = $("[data-sim-telat]", root);
    const putih = $("[data-sim-putih]", root);
    const out = (k) => $$(`[data-sim-out="${k}"]`, root);
    const set = (k, v, raw) => out(k).forEach((el) => {
      el.textContent = v; el.dataset.full = v; el.removeAttribute("title");
      if (raw == null) delete el.dataset.raw; else el.dataset.raw = raw;
    });

    const parse = (s) => Number(String(s).replace(/[^\d]/g, "")) || 0;
    const render = () => {
      const opsen = state.pokok * (T.opsenPersen / 100);
      const denda = state.putih ? 0 : (state.pokok + opsen) * (T.dendaPersenPerBulan / 100) * Math.min(state.telat, T.maksBulanDenda);
      const swd = state.jenis === "mobil" ? T.swdklljMobil : T.swdklljMotor;
      const total = state.pokok + opsen + denda + swd;
      set("pokok", rupiah(state.pokok));
      set("opsen", rupiah(opsen), opsen); set("opsen2", rupiah(opsen));
      set("denda", state.putih ? "Rp0 · dihapus" : rupiah(denda), state.putih ? null : denda); set("denda2", rupiah(denda));
      set("swd", rupiah(swd), swd); set("swd2", rupiah(swd));
      set("total", rupiah(total)); // total tidak pernah diringkas: angka lengkap hanya ada di sini
      if (outTelat) outTelat.textContent = state.telat;
      $$("[data-sim-jenis] [role=radio]", root).forEach((b) => b.setAttribute("aria-checked", String(b.dataset.val === state.jenis)));
      if (putih) putih.checked = state.putih;
      if (inPokok && document.activeElement !== inPokok) inPokok.value = fmt.format(state.pokok);
      fitAll();
    };
    // Angka dikecilkan sampai muat. Bila di ukuran minimum tetap tidak muat (tile 1×1 yang sempit),
    // tampilkan format ringkas ("Rp660 jt"); angka lengkap tetap ada di rincian dan di tooltip.
    const fitAll = () => $$(".bento__val, .bento__total-val", root).forEach((el) => {
      const min = el.matches(".bento__total-val") ? 18 : 14;
      if (el.dataset.full) { el.textContent = el.dataset.full; el.removeAttribute("title"); }
      el.style.whiteSpace = "";
      fitText(el, min);
      if (el.dataset.raw && overflows(el)) {
        el.title = el.dataset.full;
        el.textContent = "Rp" + compact.format(Number(el.dataset.raw));
        fitText(el, min);
      }
      // Pengaman terakhir untuk font sistem yang sangat lebar: boleh membungkus, tidak pernah terpotong
      if (overflows(el)) el.style.whiteSpace = "normal";
    });
    // Hitung ulang saat lebar kanvas berubah (rotasi layar, ubah ukuran jendela) dan setelah font termuat
    if ("ResizeObserver" in window) {
      let lastW = 0;
      new ResizeObserver(([entry]) => {
        const w = Math.round(entry.contentRect.width);
        if (w !== lastW) { lastW = w; fitAll(); }
      }).observe(root);
    }
    document.fonts?.ready.then(fitAll);
    document.fonts?.addEventListener?.("loadingdone", fitAll);

    inPokok?.addEventListener("input", () => {
      if (!T) return;
      state.pokok = Math.min(parse(inPokok.value), 999999999);
      const caret = inPokok.value.length - inPokok.selectionStart;
      inPokok.value = state.pokok ? fmt.format(state.pokok) : "";
      const pos = Math.max(0, inPokok.value.length - caret);
      inPokok.setSelectionRange(pos, pos);
      render();
    });
    inPokok?.addEventListener("blur", () => { if (T) render(); });
    $$("[data-sim-step]", root).forEach((b) => b.addEventListener("click", () => {
      if (!T) return;
      state.telat = Math.max(0, Math.min(T.maksBulanDenda, state.telat + Number(b.dataset.simStep)));
      render();
    }));
    $$("[data-sim-jenis] [role=radio]", root).forEach((b, i, all) => {
      b.addEventListener("click", () => {
        if (!T) return;
        state.jenis = b.dataset.val;
        render();
      });
      b.addEventListener("keydown", (e) => {
        const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
        if (!dir) return;
        e.preventDefault();
        const n = all[(i + dir + all.length) % all.length]; n.click(); n.focus();
      });
    });
    putih?.addEventListener("change", () => { if (!T) return; state.putih = putih.checked; render(); });
    $("[data-sim-reset]", root)?.addEventListener("click", () => {
      if (!T) return;
      Object.assign(state, awal());
      render();
      toast("Simulasi dikembalikan ke nilai contoh.");
    });

    return {
      /** Nonaktifkan kanvas; pesan (dan tombol coba lagi) tampil di bawahnya */
      nonaktif(pesan, tombol) {
        T = null;
        root.classList.add("is-off");
        kontrol.forEach((el) => (el.disabled = true));
        ["pokok", "opsen", "opsen2", "denda", "denda2", "swd", "swd2", "total"].forEach((k) => set(k, "—"));
        if (outTelat) outTelat.textContent = "—";
        if (inPokok) inPokok.value = "";
        if (putih) putih.checked = false;
        if (catatan) {
          catatan.textContent = pesan;
          if (tombol) catatan.append(tombol);
        }
        fitAll();
      },
      aktifkan(tarif) {
        T = tarif;
        root.classList.remove("is-off");
        kontrol.forEach((el) => (el.disabled = false));
        Object.assign(state, awal());
        if (catatan) catatan.textContent = T.catatan || catatanBawaan;
        render();
      },
    };
  }

  function initSim() {
    const sims = $$("[data-sim]").map(simulasi);
    const params = $$("[data-tarif]");
    if (!sims.length && !params.length) return;
    params.forEach((el) => (el.dataset.tarifAwal = el.textContent));

    // Teks parameter di samping kanvas (halaman Layanan) dan di label tile
    const isiParameter = (t) => {
      const maks = t && `maks. ${t.maksBulanDenda}`;
      const teks = t && {
        "label-opsen": `Opsen PKB ${persen(t.opsenPersen)}`,
        "opsen-pokok": `${persen(t.opsenPersen)} dari pokok`,
        "label-denda": `Denda ${persen(t.dendaPersenPerBulan)}/bulan`,
        "denda-maks": `${persen(t.dendaPersenPerBulan)} per bulan, ${maks}`,
        maks,
        swdkllj: `${rupiah(t.swdklljMotor)} / ${rupiah(t.swdklljMobil)}`,
      };
      params.forEach((el) => {
        if (el.dataset.tarif === "diperbarui") {
          const w = t && nowWita(new Date(t.diperbarui));
          el.textContent = w ? `Tarif diperbarui ${w.d} ${BULAN[w.mo - 1]} ${w.y}.` : "";
          el.hidden = !w;
          return;
        }
        el.textContent = teks?.[el.dataset.tarif] ?? el.dataset.tarifAwal;
      });
    };

    const muat = async (fokus) => {
      sims.forEach((s) => s.nonaktif("Memuat tarif simulasi…"));
      isiParameter(null);
      try {
        const t = await muatTarif();
        if (typeof t.diperbarui !== "string") {
          sims.forEach((s) => s.nonaktif("Simulasi belum tersedia karena tarif belum ditetapkan petugas. Tagihan resmi mengikuti notis pajak di loket."));
          return;
        }
        isiParameter(t);
        sims.forEach((s) => s.aktifkan(t));
      } catch {
        const tombol = sims.map(() => {
          const b = document.createElement("button");
          b.type = "button";
          b.className = "link";
          b.textContent = "Coba lagi";
          b.addEventListener("click", () => muat(true));
          return b;
        });
        sims.forEach((s, i) => s.nonaktif("Tarif simulasi belum dapat dimuat. ", tombol[i]));
        if (fokus) tombol[0]?.focus();
      }
    };

    muat(false);
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
      $("[data-lb-title]", dlg).textContent = it.dataset.title;
      $("[data-lb-meta]", dlg).textContent = it.dataset.meta;
      $("[data-lb-count]", dlg).textContent = `${idx + 1} / ${list.length}`;
    };
    $$(".gallery__item").forEach((it) => it.addEventListener("click", () => { show(items().indexOf(it)); dlg.showModal(); }));
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
    initNav(); initReveal(); initTabs(); initChecklists(); initStatus(); initPapan(); initKeliling(); initKontak(); initVisiMisi(); initProfil(); initLayanan();
    initDash(); initSim(); initDialogs(); initGag(); initFilters(); initGallery(); initFaq(); initForms(); initPesanForm(); initAuthFit(); initUtilities();
  });
})();
