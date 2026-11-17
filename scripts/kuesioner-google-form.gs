/**
 * Kuesioner PINTU (D-095): pembuat Google Formulir dan Spreadsheet jawabannya.
 *
 * Cara memakai (sekali saja, dengan akun Google milik UPTD):
 * 1. Buka https://script.google.com lalu pilih "Proyek baru".
 * 2. Hapus isi Code.gs, tempelkan seluruh isi berkas ini, lalu simpan (Ctrl+S).
 * 3. Pilih fungsi buatKuesioner di bilah atas, tekan "Jalankan", lalu izinkan akses ke Formulir dan Spreadsheet.
 * 4. Buka "Log eksekusi" dan salin blok JSON di antara GOOGLE-FORM-JSON dan /GOOGLE-FORM-JSON.
 *    Blok itu diisikan ke resources/js/portal/kuesioner/google-form.json di repository PINTU, lalu PINTU di-deploy;
 *    sejak itu halaman /kuesioner/wajib-pajak dan /kuesioner/operator mengirim jawaban ke formulir ini.
 *
 * Skrip membuat dua formulir (wajib pajak dan petugas operator) dan satu Spreadsheet "Kuesioner PINTU — Jawaban"
 * berisi lembar jawaban setiap formulir dan lembar Ringkasan (jumlah responden dan rata-rata setiap pernyataan).
 * Semua pertanyaan di formulir sengaja tidak wajib: aplikasi web PINTU yang memeriksa jawaban, sedangkan Google
 * menolak seluruh kiriman bila ada pertanyaan wajib yang kosong. Jangan menambah, menghapus, atau mengubah teks
 * pilihan di formulir sesudah tersambung, karena kiriman yang pilihannya tidak dikenal ditolak Google.
 *
 * Daftar pertanyaan di bawah harus sama dengan resources/js/portal/kuesioner/pertanyaan.json
 * (diperiksa tests/Feature/QuestionnaireTest.php).
 */

// @pertanyaan
const KUESIONER = {
  "skala": [
    "Sangat tidak setuju",
    "Tidak setuju",
    "Netral",
    "Setuju",
    "Sangat setuju"
  ],
  "kuesioner": {
    "operator": {
      "judul": "Kuesioner Petugas Operator PINTU",
      "untuk": "Petugas operator",
      "ringkas": "Untuk petugas yang mengelola dan memperbarui konten PINTU melalui dashboard.",
      "pembuka": "Kuesioner ini menilai kemudahan website dan dashboard PINTU bagi petugas yang mengelolanya. Jawaban Anda tanpa nama dan hanya dipakai untuk memperbaiki PINTU.",
      "pertanyaan": [
        {
          "kunci": "unit",
          "jenis": "pilihan",
          "teks": "Di bagian mana Anda bertugas?",
          "pilihan": [
            "Subbagian Tata Usaha",
            "Seksi Penetapan dan Penagihan",
            "Seksi Verifikasi",
            "Loket pelayanan",
            "Lainnya"
          ]
        },
        {
          "kunci": "p1",
          "jenis": "skala",
          "teks": "Menu website mudah dipahami."
        },
        {
          "kunci": "p2",
          "jenis": "skala",
          "teks": "Informasi layanan dan persyaratan lengkap."
        },
        {
          "kunci": "p3",
          "jenis": "skala",
          "teks": "Informasi mudah dibaca di telepon pintar."
        },
        {
          "kunci": "p4",
          "jenis": "skala",
          "teks": "Dashboard mudah digunakan untuk memperbarui konten."
        },
        {
          "kunci": "p5",
          "jenis": "skala",
          "teks": "Alur pemeriksaan dan persetujuan konten jelas."
        },
        {
          "kunci": "p6",
          "jenis": "skala",
          "teks": "Panduan Singkat Operator membantu saya."
        },
        {
          "kunci": "saran",
          "jenis": "isian",
          "teks": "Saran perbaikan untuk PINTU",
          "petunjuk": "Boleh dikosongkan. Jangan menulis nama, NIK, atau nomor polisi."
        }
      ]
    },
    "wajib-pajak": {
      "judul": "Kuesioner Wajib Pajak PINTU",
      "untuk": "Wajib pajak",
      "ringkas": "Untuk masyarakat yang membuka PINTU sebelum atau saat mengurus pajak kendaraan.",
      "pembuka": "Kuesioner singkat ini menilai manfaat PINTU bagi wajib pajak. Jawaban Anda tanpa nama dan hanya dipakai untuk memperbaiki pelayanan informasi SAMSAT Kota Kupang.",
      "pertanyaan": [
        {
          "kunci": "layanan",
          "jenis": "pilihan",
          "teks": "Layanan apa yang Anda urus?",
          "pilihan": [
            "Pajak Tahunan / Pengesahan",
            "Perpanjangan STNK 5 Tahun",
            "Balik Nama di dalam Kota Kupang",
            "Mutasi Keluar",
            "Mutasi Masuk dari Luar Daerah",
            "Duplikat STNK Hilang atau Rusak",
            "Lainnya"
          ]
        },
        {
          "kunci": "sumber",
          "jenis": "pilihan-ganda",
          "teks": "Dari mana Anda mengetahui PINTU?",
          "petunjuk": "Boleh memilih lebih dari satu.",
          "pilihan": [
            "Kode QR di loket",
            "Petugas loket atau meja informasi",
            "Instagram atau TikTok",
            "WhatsApp",
            "Mesin pencari (Google)",
            "Lainnya"
          ]
        },
        {
          "kunci": "p1",
          "jenis": "skala",
          "teks": "Saya mudah menemukan informasi persyaratan layanan di PINTU."
        },
        {
          "kunci": "p2",
          "jenis": "skala",
          "teks": "Informasi di PINTU jelas dan mudah dipahami."
        },
        {
          "kunci": "p3",
          "jenis": "skala",
          "teks": "PINTU nyaman dibuka di telepon pintar."
        },
        {
          "kunci": "p4",
          "jenis": "skala",
          "teks": "Informasi PINTU membantu saya menyiapkan berkas sebelum datang."
        },
        {
          "kunci": "p5",
          "jenis": "skala",
          "teks": "Saya akan membuka PINTU lagi sebelum mengurus pajak kendaraan."
        },
        {
          "kunci": "kurang",
          "jenis": "isian",
          "teks": "Informasi apa yang belum Anda temukan di PINTU?",
          "petunjuk": "Boleh dikosongkan. Jangan menulis nama, NIK, atau nomor polisi."
        }
      ]
    }
  }
};
// /@pertanyaan

const URUTAN = ['wajib-pajak', 'operator'];
const NAMA_LEMBAR = { 'wajib-pajak': 'Wajib Pajak', operator: 'Operator' };

function buatKuesioner() {
  const ss = SpreadsheetApp.create('Kuesioner PINTU — Jawaban');
  const ringkasan = ss.getSheets()[0].setName('Ringkasan');
  const hasil = {};
  const formulir = {};

  URUTAN.forEach(function (jenis) {
    const k = KUESIONER.kuesioner[jenis];
    const form = FormApp.create(k.judul);
    form.setDescription(k.pembuka).setConfirmationMessage('Terima kasih. Jawaban Anda sudah kami terima.');
    try {
      form.setRequireLogin(false);
    } catch (e) {
      // hanya ada di akun Google Workspace; formulir akun Google biasa terbuka untuk siapa saja
    }
    if (typeof form.setPublished === 'function') form.setPublished(true);

    const butir = k.pertanyaan.map(function (p) {
      return tambahButir(form, p);
    });
    form.setDestination(FormApp.DestinationType.SPREADSHEET, ss.getId());

    const entri = {};
    k.pertanyaan.forEach(function (p, i) {
      entri[p.kunci] = nomorEntri(form, butir[i], p);
    });
    hasil[jenis] = { aksi: form.getPublishedUrl().replace(/\/viewform.*$/, '/formResponse'), entri: entri };
    formulir[jenis] = form;
  });

  // Lembar jawaban yang dibuat Google diberi nama kuesionernya
  SpreadsheetApp.flush();
  ss.getSheets().forEach(function (lembar) {
    const alamat = lembar.getFormUrl();
    if (!alamat) return;
    const id = FormApp.openByUrl(alamat).getId();
    URUTAN.forEach(function (jenis) {
      if (formulir[jenis].getId() === id) lembar.setName(NAMA_LEMBAR[jenis]);
    });
  });
  isiRingkasan(ringkasan);

  Logger.log('Spreadsheet jawaban: ' + ss.getUrl());
  URUTAN.forEach(function (jenis) {
    Logger.log(KUESIONER.kuesioner[jenis].judul + ' — ubah: ' + formulir[jenis].getEditUrl());
    Logger.log(KUESIONER.kuesioner[jenis].judul + ' — isi langsung: ' + formulir[jenis].getPublishedUrl());
  });
  Logger.log('GOOGLE-FORM-JSON\n' + JSON.stringify(hasil, null, 2) + '\n/GOOGLE-FORM-JSON');
}

function tambahButir(form, p) {
  switch (p.jenis) {
    case 'skala':
      return form.addScaleItem().setTitle(p.teks).setBounds(1, 5).setLabels(KUESIONER.skala[0], KUESIONER.skala[4]).setRequired(false);
    case 'pilihan':
      return form.addMultipleChoiceItem().setTitle(p.teks).setChoiceValues(p.pilihan).setRequired(false);
    case 'pilihan-ganda':
      return form.addCheckboxItem().setTitle(p.teks).setHelpText(p.petunjuk || '').setChoiceValues(p.pilihan).setRequired(false);
    case 'isian':
      return form.addParagraphTextItem().setTitle(p.teks).setHelpText(p.petunjuk || '').setRequired(false);
    default:
      throw new Error('Jenis pertanyaan tidak dikenal: ' + p.jenis);
  }
}

/** Nomor entri (entry.N) sebuah pertanyaan, dibaca dari tautan isian awal yang hanya mengisi pertanyaan itu. */
function nomorEntri(form, butir, p) {
  let jawab;
  if (p.jenis === 'skala') jawab = butir.createResponse(1);
  else if (p.jenis === 'pilihan') jawab = butir.createResponse(p.pilihan[0]);
  else if (p.jenis === 'pilihan-ganda') jawab = butir.createResponse([p.pilihan[0]]);
  else jawab = butir.createResponse('x');
  const alamat = form.createResponse().withItemResponse(jawab).toPrefilledUrl();
  const cocok = alamat.match(/[?&](entry\.\d+)=/);
  if (!cocok) throw new Error('Nomor entri tidak ditemukan untuk ' + p.kunci);
  return cocok[1];
}

/** Huruf kolom Spreadsheet: 1 = A, 27 = AA */
function hurufKolom(n) {
  let huruf = '';
  while (n > 0) {
    const sisa = (n - 1) % 26;
    huruf = String.fromCharCode(65 + sisa) + huruf;
    n = Math.floor((n - 1) / 26);
  }
  return huruf;
}

/** Jumlah responden dan rata-rata setiap pernyataan berskala, langsung dari lembar jawaban. */
function isiRingkasan(lembar) {
  lembar.getRange('A1').setValue('Ringkasan Kuesioner PINTU').setFontWeight('bold').setFontSize(14);
  lembar.getRange('A2').setValue('Skala 1–5: 1 = ' + KUESIONER.skala[0] + ', 5 = ' + KUESIONER.skala[4] + '.');
  let baris = 4;
  URUTAN.forEach(function (jenis) {
    const k = KUESIONER.kuesioner[jenis];
    const sumber = "'" + NAMA_LEMBAR[jenis] + "'!";
    lembar.getRange(baris, 1).setValue(k.judul).setFontWeight('bold');
    baris++;
    lembar.getRange(baris, 1).setValue('Jumlah responden');
    lembar.getRange(baris, 2).setFormula('=COUNTA(' + sumber + 'A2:A)');
    baris++;
    const kolomSkala = [];
    k.pertanyaan.forEach(function (p, i) {
      if (p.jenis !== 'skala') return;
      const kolom = hurufKolom(i + 2); // kolom A berisi waktu kiriman
      kolomSkala.push(kolom);
      lembar.getRange(baris, 1).setValue(p.teks);
      lembar.getRange(baris, 2).setFormula('=IFERROR(AVERAGE(' + sumber + kolom + '2:' + kolom + '),"-")').setNumberFormat('0.00');
      baris++;
    });
    const rentang = sumber + kolomSkala[0] + '2:' + kolomSkala[kolomSkala.length - 1];
    lembar.getRange(baris, 1).setValue('Rata-rata semua pernyataan').setFontWeight('bold');
    lembar.getRange(baris, 2).setFormula('=IFERROR(AVERAGE(' + rentang + '),"-")').setNumberFormat('0.00').setFontWeight('bold');
    baris += 2;
  });
  lembar.setColumnWidth(1, 520);
  lembar.setColumnWidth(2, 120);
}
