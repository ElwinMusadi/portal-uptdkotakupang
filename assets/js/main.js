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
    // Jam loket (contoh). 0 = Minggu … 6 = Sabtu. null = tutup.
    hours: {
      0: null,
      1: ["08:00", "15:00"],
      2: ["08:00", "15:00"],
      3: ["08:00", "15:00"],
      4: ["08:00", "15:00"],
      5: ["08:00", "11:30"],
      6: ["08:00", "12:00"],
    },
    // Rotasi lokasi Samsat Keliling (contoh) — Senin s.d. Jumat, dua titik per hari.
    keliling: [
      [["08.00–12.00", "Kantor Kelurahan Oebobo", "Kec. Oebobo"], ["13.00–15.00", "Kantor Kelurahan Fatululi", "Kec. Oebobo"]],
      [["08.00–12.00", "Kantor Kelurahan Oesapa", "Kec. Kelapa Lima"], ["13.00–15.00", "Kantor Kelurahan Sikumana", "Kec. Maulafa"]],
      [["08.00–12.00", "Kantor Kelurahan Penfui", "Kec. Maulafa"], ["13.00–15.00", "Kantor Kelurahan Liliba", "Kec. Oebobo"]],
      [["08.00–12.00", "Kantor Kelurahan Naikoten I", "Kec. Kota Raja"], ["13.00–15.00", "Kantor Kelurahan Oepura", "Kec. Maulafa"]],
      [["08.00–11.00", "Kantor Kelurahan Alak", "Kec. Alak"], ["08.00–11.00", "Kantor Kelurahan Fontein", "Kec. Kota Raja"]],
    ],
    // Parameter simulasi (contoh; sesuaikan Perda Pajak Daerah NTT & ketentuan Jasa Raharja).
    sim: {
      opsen: 0.66,           // opsen PKB untuk kabupaten/kota
      dendaPerBulan: 0.01,   // sanksi administratif per bulan
      maksBulan: 24,
      swdkllj: { motor: 35000, mobil: 143000 },
      default: { pokok: 285000, jenis: "motor", telat: 3, putih: false },
    },
  };

  const HARI = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const BULAN = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

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
    $$("[data-tabs]").forEach((list) => {
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
    });
  }

  /* ---------- Checklist persyaratan ---------- */
  function initChecklists() {
    $$("[data-checklist]").forEach((box) => {
      const out = $("[data-check-count]", box);
      const update = () => { if (out) out.textContent = $$("input:checked", box).length; };
      box.addEventListener("change", update);
      update();
    });
  }

  /* ---------- Waktu WITA & status loket ---------- */
  function nowWita() {
    const parts = new Intl.DateTimeFormat("en-US", {
      timeZone: CONFIG.timeZone, hourCycle: "h23",
      year: "numeric", month: "numeric", day: "numeric", hour: "numeric", minute: "numeric", second: "numeric", weekday: "short",
    }).formatToParts(new Date());
    const get = (t) => parts.find((p) => p.type === t)?.value;
    const wd = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(get("weekday"));
    return { y: +get("year"), mo: +get("month"), d: +get("day"), h: +get("hour"), m: +get("minute"), s: +get("second"), wd };
  }
  const toSec = (hhmm) => { const [h, m] = hhmm.split(":").map(Number); return h * 3600 + m * 60; };
  const dot = (hhmm) => hhmm.replace(":", ".");

  function serviceStatus() {
    const t = nowWita();
    const cur = t.h * 3600 + t.m * 60 + t.s;
    const today = CONFIG.hours[t.wd];
    if (today && cur >= toSec(today[0]) && cur < toSec(today[1])) {
      return { open: true, left: toSec(today[1]) - cur, close: dot(today[1]), wd: t.wd, t };
    }
    // cari jam buka berikutnya (hari ini nanti atau hari-hari berikutnya)
    for (let i = 0; i < 8; i++) {
      const wd = (t.wd + i) % 7;
      const h = CONFIG.hours[wd];
      if (!h) continue;
      const start = toSec(h[0]);
      if (i === 0 && cur >= start) continue;
      const left = i * 86400 + start - cur;
      return { open: false, left, nextDay: i === 0 ? "hari ini" : i === 1 ? "besok" : HARI[wd], nextTime: dot(h[0]), wd: t.wd, t };
    }
    return { open: false, left: 0, wd: t.wd, t };
  }
  const hms = (sec) => `${pad(Math.floor(sec / 3600))}:${pad(Math.floor((sec % 3600) / 60))}:${pad(sec % 60)}`;

  function initStatus() {
    const textEls = $$("[data-status-text]");
    const shortEls = $$("[data-status-short]");
    const cdEls = $$("[data-countdown]");
    const cdLabels = $$("[data-countdown-label]");
    const dots = $$("[data-open-dot]");
    const pills = $$("[data-open-pill]");
    const headline = $$("[data-status-headline]");
    if (!(textEls.length || shortEls.length || cdEls.length || headline.length)) return;

    const tick = () => {
      const st = serviceStatus();
      const long = st.open ? `Loket buka · tutup ${st.close} WITA` : `Loket tutup · buka ${st.nextDay} ${st.nextTime}`;
      textEls.forEach((el) => (el.textContent = long));
      shortEls.forEach((el) => (el.textContent = st.open ? `Buka · s.d. ${st.close}` : `Tutup · buka ${st.nextDay} ${st.nextTime}`));
      cdEls.forEach((el) => (el.textContent = hms(Math.max(0, st.left))));
      cdLabels.forEach((el) => (el.textContent = st.open ? "Loket tutup dalam" : "Loket buka dalam"));
      dots.forEach((el) => el.classList.toggle("is-closed", !st.open));
      pills.forEach((el) => el.classList.toggle("is-closed", !st.open));
      headline.forEach((el) => (el.textContent = st.open ? "Loket sedang buka." : "Loket sedang tutup."));
      $$("[data-status-sub]").forEach((el) => (el.textContent = st.open
        ? `Penerimaan berkas ditutup 60 menit sebelum loket tutup pukul ${st.close} WITA.`
        : `Buka kembali ${st.nextDay} pukul ${st.nextTime} WITA.`));
      $$("[data-countdown-cap]").forEach((el) => (el.textContent = st.open ? "menuju tutup" : "menuju buka"));
    };
    tick();
    setInterval(tick, 1000);

    // tandai baris hari ini di tabel jam
    const wd = nowWita().wd;
    $$("[data-weekday]").forEach((row) => {
      if (row.dataset.weekday.split(",").map(Number).includes(wd)) row.classList.add("is-today");
    });
  }

  /* ---------- Samsat Keliling ---------- */
  function kelilingFor(wd) { return wd >= 1 && wd <= 5 ? CONFIG.keliling[wd - 1] : []; }

  function initKeliling() {
    const t = nowWita();
    const today = kelilingFor(t.wd);
    $$("[data-keliling-count]").forEach((el) => (el.textContent = today.length ? `${today.length} titik` : "Libur akhir pekan"));
    $$("[data-keliling-today]").forEach((ul) => {
      ul.innerHTML = today.length
        ? today.map(([time, place, kec]) => `<li><span class="board__time mono">${time}</span><span><strong>${place}</strong><small>${kec}</small></span></li>`).join("")
        : `<li><span class="board__time mono">Sabtu–Minggu</span><span><strong>Tidak ada Samsat Keliling.</strong><small>Layanan kembali hari Senin.</small></span></li>`;
    });

    const week = $("[data-keliling-week]");
    if (!week) return;
    const base = new Date(Date.UTC(t.y, t.mo - 1, t.d));
    const weekend = t.wd === 0 || t.wd === 6;
    const monday = new Date(base);
    // Akhir pekan: tampilkan jadwal minggu depan
    monday.setUTCDate(base.getUTCDate() + (weekend ? (8 - t.wd) % 7 || 1 : -((t.wd + 6) % 7)));
    let html = "";
    for (let i = 0; i < 5; i++) {
      const d = new Date(monday); d.setUTCDate(monday.getUTCDate() + i);
      const wd = i + 1;
      const state = weekend ? "" : wd === t.wd ? "is-today" : wd < t.wd ? "is-past" : "";
      const label = state === "is-today" ? '<span class="tag tag--accent">Hari ini</span>' : state === "is-past" ? '<span class="tag">Selesai</span>' : '<span class="tag tag--line">Terjadwal</span>';
      const stops = kelilingFor(wd).map(([time, place, kec]) => `
        <div class="stop">
          <p class="stop__time"><span>${time} WITA</span>${label}</p>
          <strong>${place}</strong><span>${kec}</span>
          <a class="link-arrow" href="https://maps.google.com/?q=${encodeURIComponent(place + " Kota Kupang")}" target="_blank" rel="noopener">Buka peta<svg class="i" aria-hidden="true"><use href="#i-arrow-up-right"/></svg></a>
        </div>`).join("");
      html += `<div class="week__day ${state}"><p class="week__date"><strong>${HARI[wd]}</strong><span>${d.getUTCDate()} ${BULAN[d.getUTCMonth()]} ${d.getUTCFullYear()}</span></p><div class="week__stops">${stops}</div></div>`;
    }
    week.innerHTML = html;
  }

  /* ---------- Dasbor transparansi (data ilustrasi) ---------- */
  const DASH = {
    transaksi: {
      sub: "Transaksi pelayanan · UPTD Kota Kupang", chart: "Transaksi per bulan", unit: "transaksi",
      tiles: [["Total transaksi", 12480, "", 4.2, true], ["Pajak tahunan", 9316, "", 3.1, true], ["Perpanjangan 5\u00a0tahun", 2104, "", 6.8, true], ["Mutasi & BBN", 1060, "", -2.4, true]],
      series: [880, 910, 1020, 960, 1040, 1100, 990, 1080, 1150, 1120, 1060, 1160],
    },
    penerimaan: {
      sub: "Penerimaan pajak daerah · UPTD Kota Kupang", chart: "Penerimaan PKB per bulan", unit: "juta rupiah",
      tiles: [["Penerimaan PKB", 18.4, "M", 5.6, true], ["BBNKB", 6.1, "M", -1.8, true], ["Opsen PKB (Pemkot)", 12.1, "M", 5.6, true], ["Capaian target", 78, "%", 3.0, true]],
      series: [1310, 1380, 1520, 1460, 1590, 1640, 1500, 1620, 1710, 1680, 1600, 1740],
    },
    keliling: {
      sub: "Samsat Keliling · 6 kecamatan, 51 kelurahan", chart: "Transaksi keliling per bulan", unit: "transaksi",
      tiles: [["Titik layanan", 22, "", 10, true], ["Transaksi keliling", 1284, "", 12.5, true], ["Rata-rata per titik", 58, "", 2.1, true], ["Kelurahan terjangkau", 31, "/51", 8.0, true]],
      series: [64, 72, 88, 95, 102, 110, 98, 120, 131, 126, 118, 140],
    },
    kepuasan: {
      sub: "Survei Kepuasan Masyarakat (IKM)", chart: "Waktu tunggu rata-rata per bulan", unit: "menit",
      tiles: [["Indeks kepuasan", 88.4, "", 1.6, true], ["Waktu tunggu rata-rata", 18, " mnt", -6, false], ["Pengaduan selesai", 96, "%", 2.0, true], ["Responden survei", 412, "", 9.3, true]],
      series: [31, 30, 28, 29, 26, 25, 24, 23, 22, 21, 19, 18],
    },
  };
  const RANGE = { ytd: 1, "12m": 1.18, "3m": 0.27, "30d": 0.09, today: 0.0042 };
  const RATIO_UNITS = new Set(["%", "/51", " mnt", "M"]);

  function sparkPath(vals, w = 120, h = 36) {
    const min = Math.min(...vals), max = Math.max(...vals), r = max - min || 1;
    const pts = vals.map((v, i) => [(i / (vals.length - 1)) * w, h - 3 - ((v - min) / r) * (h - 6)]);
    const line = pts.map((p, i) => `${i ? "L" : "M"}${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(" ");
    const area = `${line} L${w} ${h} L0 ${h} Z`;
    const last = pts[pts.length - 1], prev = pts[pts.length - 2];
    return { line, area, last, nowSeg: `M${prev[0].toFixed(1)} ${prev[1].toFixed(1)} L${last[0].toFixed(1)} ${last[1].toFixed(1)}` };
  }

  function initDash() {
    const dash = $("[data-dash]");
    if (!dash) return;
    const tilesEl = $("[data-dash-tiles]", dash);
    const barsEl = $("[data-bars]", dash);
    let set = "transaksi", range = "ytd";

    const render = () => {
      const d = DASH[set], k = RANGE[range];
      $("[data-dash-sub]", dash).textContent = d.sub;
      $("[data-chart-title]", dash).textContent = d.chart;
      $("[data-chart-unit]", dash).textContent = d.unit;
      tilesEl.innerHTML = d.tiles.map(([label, val, unit, delta, upGood], i) => {
        const isRatio = RATIO_UNITS.has(unit) && unit !== "M";
        let v = isRatio ? val : val * k;
        const shown = unit === "M"
          ? (v >= 1 ? `${fmt.format(Math.round(v * 10) / 10)}<small>miliar</small>` : `${fmt.format(Math.round(v * 1000))}<small>juta</small>`)
          : `${fmt.format(Math.round(v * 10) / 10)}${unit ? `<small>${unit.trim()}</small>` : ""}`;
        const good = (delta >= 0) === upGood;
        const seed = d.series.map((s, j) => s * (1 + Math.sin(j * (i + 1.3)) * 0.06));
        const sp = sparkPath(seed);
        return `<div class="tile">
          <p class="tile__label">${label}</p>
          <p class="tile__value">${shown}</p>
          <p class="tile__meta"><span class="delta ${good ? "delta--up" : "delta--down"}"><svg class="i" aria-hidden="true"><use href="#i-trend-${delta >= 0 ? "up" : "down"}"/></svg>${delta > 0 ? "+" : ""}${fmt.format(delta)}%</span><span>vs periode lalu</span></p>
          <svg class="spark" viewBox="0 0 120 36" preserveAspectRatio="none" aria-hidden="true"><path class="spark__area" d="${sp.area}"/><path d="${sp.line}"/><path class="spark__now" d="${sp.nowSeg}"/><circle cx="${sp.last[0]}" cy="${sp.last[1]}" r="4"/></svg>
        </div>`;
      }).join("");

      const series = range === "today" || range === "30d"
        ? d.series.map((v, i) => Math.round(v * (range === "today" ? 0.045 : 0.33) * (0.85 + ((i * 37) % 11) / 30)))
        : d.series;
      const max = Math.max(...series);
      const t = nowWita();
      const labels = range === "today"
        ? ["08", "09", "10", "11", "12", "13", "14", "15", "", "", "", ""].slice(0, 12)
        : range === "30d" ? series.map((_, i) => (i % 3 === 0 ? `H-${(11 - i) * 3 || "0"}` : ""))
        : series.map((_, i) => BULAN[(t.mo - 12 + i + 12) % 12]);
      barsEl.innerHTML = series.map((v, i) => {
        const h = Math.max(3, (v / max) * 100);
        const now = i === series.length - 1;
        const val = d.unit === "juta rupiah" ? `${fmt.format(v)} jt` : `${fmt.format(v)} ${d.unit === "menit" ? "mnt" : ""}`.trim();
        return `<div class="bar${now ? " is-now" : ""}" tabindex="0" style="--h:${h}%" aria-label="${labels[i] || "Periode " + (i + 1)}: ${val}"><span class="bar__fill" style="height:${h}%"></span><span class="bar__tip">${val}</span><span class="bar__x">${labels[i] || ""}</span></div>`;
      }).join("");
      barsEl.setAttribute("aria-label", `${d.chart}, ${series.length} periode`);
    };

    $$("[data-dash-tabs] [role=tab]").forEach((tab, i, all) => {
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
    $$("[data-dash-range] button", dash).forEach((b, _, all) => b.addEventListener("click", () => {
      all.forEach((x) => x.setAttribute("aria-pressed", String(x === b)));
      range = b.dataset.range; render();
    }));
    render();
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

  /* ---------- Simulasi PKB ---------- */
  function initSim() {
    $$("[data-sim]").forEach((root) => {
      const S = CONFIG.sim;
      const state = { ...S.default };
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
        const opsen = state.pokok * S.opsen;
        const denda = state.putih ? 0 : (state.pokok + opsen) * S.dendaPerBulan * Math.min(state.telat, S.maksBulan);
        const swd = S.swdkllj[state.jenis];
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
      const fitAll = () => $$(".bento__val, .bento__total-val, .mini-sim__out strong", root).forEach((el) => {
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
        state.pokok = Math.min(parse(inPokok.value), 999999999);
        const caret = inPokok.value.length - inPokok.selectionStart;
        inPokok.value = state.pokok ? fmt.format(state.pokok) : "";
        const pos = Math.max(0, inPokok.value.length - caret);
        inPokok.setSelectionRange(pos, pos);
        render();
      });
      inPokok?.addEventListener("blur", render);
      $$("[data-sim-step]", root).forEach((b) => b.addEventListener("click", () => {
        state.telat = Math.max(0, Math.min(S.maksBulan, state.telat + Number(b.dataset.simStep)));
        render();
      }));
      $$("[data-sim-jenis] [role=radio]", root).forEach((b, i, all) => {
        b.addEventListener("click", () => {
          state.jenis = b.dataset.val;
          if (state.pokok === S.default.pokok || state.pokok === 2450000) state.pokok = state.jenis === "mobil" ? 2450000 : S.default.pokok;
          render();
        });
        b.addEventListener("keydown", (e) => {
          const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
          if (!dir) return;
          e.preventDefault();
          const n = all[(i + dir + all.length) % all.length]; n.click(); n.focus();
        });
      });
      putih?.addEventListener("change", () => { state.putih = putih.checked; render(); });
      $("[data-sim-reset]", root)?.addEventListener("click", () => { Object.assign(state, S.default); render(); toast("Simulasi dikembalikan ke nilai contoh."); });
      render();
    });
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

  /* ---------- Formulir (demo, tanpa backend) ---------- */
  function initForms() {
    const login = $("[data-login-form]");
    if (login) {
      const nip = $("#nip", login), pass = $("#sandi", login), alert = $("[data-login-alert]");
      $("[data-toggle-pass]", login)?.addEventListener("click", (e) => {
        const show = pass.type === "password";
        pass.type = show ? "text" : "password";
        e.currentTarget.setAttribute("aria-label", show ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");
        $("use", e.currentTarget).setAttribute("href", show ? "#i-eye-off" : "#i-eye");
      });
      nip.addEventListener("input", () => { nip.value = nip.value.replace(/\D/g, "").slice(0, 18); nip.removeAttribute("aria-invalid"); alert.classList.remove("is-on"); });
      login.addEventListener("submit", (e) => {
        e.preventDefault();
        const okNip = /^\d{18}$/.test(nip.value);
        const okPass = pass.value.length >= 8;
        nip.toggleAttribute("aria-invalid", !okNip);
        if (!okNip) nip.setAttribute("aria-invalid", "true");
        if (!okNip || !okPass) {
          $("[data-login-msg]").textContent = !okNip ? "NIP harus 18 digit angka." : "Kata sandi minimal 8 karakter.";
          alert.classList.add("is-on");
          return;
        }
        const btn = $("button[type=submit]", login);
        btn.setAttribute("aria-disabled", "true");
        btn.textContent = "Memeriksa…";
        setTimeout(() => (window.location.href = "dashboard-pegawai.html"), 700);
      });
    }

    $$("[data-demo-form]").forEach((form) => form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (!form.checkValidity()) { form.reportValidity(); return; }
      const ref = "PINTU-" + Date.now().toString(36).toUpperCase().slice(-6);
      form.reset();
      toast(`Terima kasih. Nomor tiket Anda ${ref}.`);
    }));
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
      el.textContent = `${HARI[t.wd]}, ${t.d} ${["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"][t.mo - 1]} ${t.y}`;
    });
    // Sorot layanan aktif di navigasi samping saat menggulir
    const links = $$("[data-spy] a[href^='#']");
    if (links.length && "IntersectionObserver" in window) {
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
  }

  document.addEventListener("DOMContentLoaded", () => {
    initNav(); initReveal(); initTabs(); initChecklists(); initStatus(); initKeliling();
    initDash(); initSim(); initDialogs(); initGag(); initFilters(); initGallery(); initForms(); initUtilities();
  });
})();
