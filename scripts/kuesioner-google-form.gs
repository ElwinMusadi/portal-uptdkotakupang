/**
 * Kuesioner PINTU (D-095, D-096): pembuat Google Formulir dan Spreadsheet jawabannya.
 *
 * Cara memakai (dengan akun Google milik UPTD; setiap kali daftar pertanyaan berubah):
 * 1. Buka https://script.google.com lalu pilih "Proyek baru" (atau buka proyek yang lama).
 * 2. Hapus isi Code.gs, tempelkan seluruh isi berkas ini, lalu simpan (Ctrl+S).
 * 3. Pilih fungsi buatKuesioner di bilah atas, tekan "Jalankan", lalu izinkan akses ke Formulir dan Spreadsheet.
 * 4. Buka "Log eksekusi" dan salin blok JSON di antara GOOGLE-FORM-JSON dan /GOOGLE-FORM-JSON.
 *    Blok itu diisikan ke resources/js/portal/kuesioner/google-form.json di repository PINTU, lalu PINTU di-deploy;
 *    sejak itu halaman /kuesioner/wajib-pajak dan /kuesioner/operator mengirim jawaban ke formulir ini.
 *
 * Setiap kali dijalankan, skrip membuat dua formulir baru (wajib pajak dan petugas operator) dan satu Spreadsheet
 * baru "Kuesioner PINTU — Jawaban". Formulir dan Spreadsheet dari daftar pertanyaan sebelumnya tidak dipakai lagi
 * dan boleh dibuang dari Google Drive. Spreadsheet berisi lembar jawaban setiap formulir dan lembar Ringkasan:
 * jumlah responden, rata-rata setiap pernyataan, rata-rata setiap aspek, rata-rata semua pernyataan, dan jumlah
 * jawaban setiap pilihan pada pertanyaan profil.
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
      "pembuka": "Kuesioner ini menilai portal dan dashboard PINTU dari sisi petugas yang mengelolanya: portal untuk warga, kemudahan dashboard, fungsi dan kinerja, alur kerja dan keamanan, serta dukungan dan kepuasan. Jawaban Anda tanpa nama dan hanya dipakai untuk mengevaluasi serta memperbaiki PINTU.",
      "sebelum": {
        "teks": "Agar penilaian Anda sesuai pengalaman nyata, masuk ke dashboard dan coba dulu fitur berikut sesuai tugas Anda. Tautan terbuka di tab baru, jadi halaman ini tetap terbuka. Konten percobaan cukup disimpan sebagai draf, tidak perlu diterbitkan.",
        "fitur": [
          {
            "nama": "Masuk dashboard",
            "alamat": "/dashboard",
            "keterangan": "Masuk dengan akun petugas Anda dan buka menu yang menjadi tugas Anda."
          },
          {
            "nama": "Mengelola konten",
            "alamat": "/dashboard",
            "keterangan": "Buat atau ubah konten di menu tugas Anda, misalnya Berita & Pengumuman, Persyaratan Layanan, Layanan Luar Kantor, atau Tanya Jawab."
          },
          {
            "nama": "Alur Publikasi",
            "alamat": "/dashboard#/alur",
            "keterangan": "Ajukan, periksa, atau setujui konten sesuai peran Anda."
          },
          {
            "nama": "Hasil di portal",
            "alamat": "/",
            "keterangan": "Lihat hasil perubahan lewat Pratinjau atau di halaman portal."
          },
          {
            "nama": "Pesan & Pengaduan",
            "alamat": "/dashboard#/pesan",
            "keterangan": "Buka pesan dari warga dan cara menanggapinya."
          },
          {
            "nama": "Panduan dashboard",
            "alamat": "/dashboard#/panduan",
            "keterangan": "Baca Panduan di dashboard."
          },
          {
            "nama": "Portal di telepon pintar",
            "alamat": "/",
            "keterangan": "Buka PINTU di telepon pintar, lalu coba menu, pencarian, dan Cek Biaya Pajak."
          }
        ],
        "konfirmasi": "Saya sudah masuk ke dashboard dan mencoba fitur PINTU di atas."
      },
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
          "kunci": "fitur",
          "jenis": "pilihan-ganda",
          "wajib": true,
          "teks": "Fitur PINTU apa saja yang sudah Anda coba?",
          "petunjuk": "Pilih semua yang sudah Anda buka atau coba.",
          "pilihan": [
            "Masuk dashboard",
            "Mengelola konten",
            "Alur Publikasi",
            "Hasil di portal",
            "Pesan & Pengaduan",
            "Panduan dashboard",
            "Portal di telepon pintar",
            "Lainnya"
          ]
        },
        {
          "kunci": "p1",
          "jenis": "skala",
          "aspek": "Portal untuk warga",
          "teks": "Menu website mudah dipahami."
        },
        {
          "kunci": "p2",
          "jenis": "skala",
          "aspek": "Portal untuk warga",
          "teks": "Informasi layanan dan persyaratan lengkap."
        },
        {
          "kunci": "p3",
          "jenis": "skala",
          "aspek": "Portal untuk warga",
          "teks": "Informasi mudah dibaca di telepon pintar."
        },
        {
          "kunci": "p4",
          "jenis": "skala",
          "aspek": "Kemudahan dashboard",
          "teks": "Dashboard mudah digunakan untuk memperbarui konten."
        },
        {
          "kunci": "p5",
          "jenis": "skala",
          "aspek": "Kemudahan dashboard",
          "teks": "Saya dapat menemukan menu dashboard yang saya perlukan tanpa bantuan."
        },
        {
          "kunci": "p6",
          "jenis": "skala",
          "aspek": "Kemudahan dashboard",
          "teks": "Petunjuk dan pesan kesalahan di dashboard membantu saya mengisi data dengan benar."
        },
        {
          "kunci": "p7",
          "jenis": "skala",
          "aspek": "Fungsi dan kinerja",
          "teks": "Fitur dashboard yang saya coba berjalan sesuai fungsinya."
        },
        {
          "kunci": "p8",
          "jenis": "skala",
          "aspek": "Fungsi dan kinerja",
          "teks": "Perubahan yang disimpan di dashboard tampil dengan benar di portal."
        },
        {
          "kunci": "p9",
          "jenis": "skala",
          "aspek": "Fungsi dan kinerja",
          "teks": "Dashboard terbuka dan menyimpan perubahan dengan cepat."
        },
        {
          "kunci": "p10",
          "jenis": "skala",
          "aspek": "Alur kerja dan keamanan",
          "teks": "Alur pemeriksaan dan persetujuan konten jelas."
        },
        {
          "kunci": "p11",
          "jenis": "skala",
          "aspek": "Alur kerja dan keamanan",
          "teks": "Hak akses akun saya sesuai dengan tugas saya."
        },
        {
          "kunci": "p12",
          "jenis": "skala",
          "aspek": "Alur kerja dan keamanan",
          "teks": "Saya merasa akun dan data di dashboard terlindungi dengan baik."
        },
        {
          "kunci": "p13",
          "jenis": "skala",
          "aspek": "Dukungan dan kepuasan",
          "teks": "Panduan Singkat Operator membantu saya."
        },
        {
          "kunci": "p14",
          "jenis": "skala",
          "aspek": "Dukungan dan kepuasan",
          "teks": "PINTU membantu pekerjaan saya dalam menyampaikan informasi kepada wajib pajak."
        },
        {
          "kunci": "p15",
          "jenis": "skala",
          "aspek": "Dukungan dan kepuasan",
          "teks": "Secara keseluruhan, saya puas menggunakan PINTU."
        },
        {
          "kunci": "kendala",
          "jenis": "isian",
          "teks": "Kendala atau kesalahan apa yang Anda temui saat mencoba PINTU?",
          "petunjuk": "Sebutkan menu atau halamannya bila ada. Boleh dikosongkan."
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
      "pembuka": "Kuesioner ini menilai PINTU dari sisi wajib pajak: kemudahan penggunaan, kualitas informasi, fungsi, tampilan dan kinerja, serta manfaat dan kepuasan. Jawaban Anda tanpa nama dan hanya dipakai untuk mengevaluasi serta memperbaiki pelayanan informasi SAMSAT Kota Kupang.",
      "sebelum": {
        "teks": "Agar penilaian Anda sesuai pengalaman nyata, buka dan coba dulu fitur PINTU berikut. Tautan terbuka di tab baru, jadi halaman ini tetap terbuka.",
        "fitur": [
          {
            "nama": "Persyaratan Layanan",
            "alamat": "/layanan",
            "keterangan": "Cari layanan yang Anda urus dan lihat berkas yang perlu dibawa."
          },
          {
            "nama": "Unduh persyaratan",
            "alamat": "/layanan",
            "keterangan": "Simpan persyaratan layanan sebagai gambar di telepon Anda."
          },
          {
            "nama": "Cek Biaya Pajak",
            "alamat": "/layanan#simulasi",
            "keterangan": "Hitung perkiraan pajak kendaraan Anda."
          },
          {
            "nama": "Jadwal dan SAMSAT Keliling",
            "alamat": "/jadwal",
            "keterangan": "Lihat jam pelayanan dan jadwal layanan di luar kantor."
          },
          {
            "nama": "Berita & Pengumuman",
            "alamat": "/informasi",
            "keterangan": "Baca berita dan pengumuman terbaru."
          },
          {
            "nama": "Tanya Jawab",
            "alamat": "/#faq",
            "keterangan": "Baca jawaban atas pertanyaan yang sering diajukan."
          },
          {
            "nama": "Kontak & Pengaduan",
            "alamat": "/kontak",
            "keterangan": "Lihat alamat kantor dan cara mengirim pesan atau pengaduan."
          },
          {
            "nama": "Pencarian",
            "alamat": "/",
            "keterangan": "Cari informasi dengan kolom Cari di bagian atas halaman."
          }
        ],
        "konfirmasi": "Saya sudah membuka dan mencoba fitur PINTU di atas."
      },
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
          "kunci": "fitur",
          "jenis": "pilihan-ganda",
          "wajib": true,
          "teks": "Fitur PINTU apa saja yang sudah Anda coba?",
          "petunjuk": "Pilih semua yang sudah Anda buka atau coba.",
          "pilihan": [
            "Persyaratan Layanan",
            "Unduh persyaratan",
            "Cek Biaya Pajak",
            "Jadwal dan SAMSAT Keliling",
            "Berita & Pengumuman",
            "Tanya Jawab",
            "Kontak & Pengaduan",
            "Pencarian",
            "Lainnya"
          ]
        },
        {
          "kunci": "p1",
          "jenis": "skala",
          "aspek": "Kemudahan penggunaan",
          "teks": "Menu dan susunan halaman PINTU mudah dipahami."
        },
        {
          "kunci": "p2",
          "jenis": "skala",
          "aspek": "Kemudahan penggunaan",
          "teks": "Saya mudah menemukan informasi persyaratan layanan di PINTU."
        },
        {
          "kunci": "p3",
          "jenis": "skala",
          "aspek": "Kemudahan penggunaan",
          "teks": "Saya dapat menggunakan PINTU tanpa bantuan orang lain."
        },
        {
          "kunci": "p4",
          "jenis": "skala",
          "aspek": "Kualitas informasi",
          "teks": "Informasi di PINTU jelas dan mudah dipahami."
        },
        {
          "kunci": "p5",
          "jenis": "skala",
          "aspek": "Kualitas informasi",
          "teks": "Informasi persyaratan, biaya, dan jadwal layanan di PINTU lengkap."
        },
        {
          "kunci": "p6",
          "jenis": "skala",
          "aspek": "Kualitas informasi",
          "teks": "Informasi di PINTU sesuai dengan ketentuan yang berlaku di loket SAMSAT."
        },
        {
          "kunci": "p7",
          "jenis": "skala",
          "aspek": "Fungsi",
          "teks": "Fitur PINTU yang saya coba berjalan sesuai fungsinya."
        },
        {
          "kunci": "p8",
          "jenis": "skala",
          "aspek": "Fungsi",
          "teks": "Hasil Cek Biaya Pajak membantu saya memperkirakan biaya yang perlu disiapkan."
        },
        {
          "kunci": "p9",
          "jenis": "skala",
          "aspek": "Fungsi",
          "teks": "Tautan, tombol, dan unduhan di PINTU berfungsi tanpa kesalahan."
        },
        {
          "kunci": "p10",
          "jenis": "skala",
          "aspek": "Tampilan dan kinerja",
          "teks": "PINTU nyaman dibuka di telepon pintar."
        },
        {
          "kunci": "p11",
          "jenis": "skala",
          "aspek": "Tampilan dan kinerja",
          "teks": "Halaman PINTU terbuka dengan cepat."
        },
        {
          "kunci": "p12",
          "jenis": "skala",
          "aspek": "Tampilan dan kinerja",
          "teks": "Tampilan PINTU rapi dan tulisannya mudah dibaca."
        },
        {
          "kunci": "p13",
          "jenis": "skala",
          "aspek": "Manfaat dan kepuasan",
          "teks": "Informasi PINTU membantu saya menyiapkan berkas sebelum datang."
        },
        {
          "kunci": "p14",
          "jenis": "skala",
          "aspek": "Manfaat dan kepuasan",
          "teks": "Saya akan membuka PINTU lagi sebelum mengurus pajak kendaraan."
        },
        {
          "kunci": "p15",
          "jenis": "skala",
          "aspek": "Manfaat dan kepuasan",
          "teks": "Secara keseluruhan, saya puas dengan PINTU."
        },
        {
          "kunci": "kendala",
          "jenis": "isian",
          "teks": "Kendala atau kesalahan apa yang Anda temui saat mencoba PINTU?",
          "petunjuk": "Sebutkan halamannya bila ada. Boleh dikosongkan."
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

/** Teks dalam rumus Spreadsheet: diapit tanda petik, tanda petik di dalamnya digandakan. */
function teksRumus(teks) {
  return '"' + String(teks).replace(/"/g, '""') + '"';
}

/**
 * Ringkasan langsung dari lembar jawaban: jumlah responden, rata-rata setiap aspek dan pernyataan, rata-rata semua
 * pernyataan, lalu jumlah jawaban setiap pilihan pada pertanyaan profil. Kolom A lembar jawaban berisi waktu kiriman,
 * dan pertanyaan ke-n (mulai 0) ada di kolom ke-(n + 2).
 */
function isiRingkasan(lembar) {
  lembar.getRange('A1').setValue('Ringkasan Kuesioner PINTU').setFontWeight('bold').setFontSize(14);
  lembar.getRange('A2').setValue('Skala 1–5: 1 = ' + KUESIONER.skala[0] + ', 5 = ' + KUESIONER.skala[4] + '. Rata-rata dihitung dari jawaban yang terisi.');
  let baris = 4;
  const tulis = function (teks, rumus, tebal, format) {
    lembar.getRange(baris, 1).setValue(teks).setFontWeight(tebal ? 'bold' : 'normal');
    if (rumus) lembar.getRange(baris, 2).setFormula(rumus).setFontWeight(tebal ? 'bold' : 'normal').setNumberFormat(format);
    baris++;
  };
  const rerata = function (rentang) {
    return '=IFERROR(AVERAGE(' + rentang.join(',') + '),"-")';
  };

  URUTAN.forEach(function (jenis) {
    const k = KUESIONER.kuesioner[jenis];
    const sumber = "'" + NAMA_LEMBAR[jenis] + "'!";
    const rentang = function (p) {
      const kolom = hurufKolom(k.pertanyaan.indexOf(p) + 2);
      return sumber + kolom + '2:' + kolom;
    };
    const skala = k.pertanyaan.filter(function (p) {
      return p.jenis === 'skala';
    });
    const aspek = [];
    skala.forEach(function (p) {
      if (aspek.indexOf(p.aspek) < 0) aspek.push(p.aspek);
    });

    tulis(k.judul, null, true);
    tulis('Jumlah responden', '=COUNTA(' + sumber + 'A2:A)', false, '0');
    baris++;
    tulis('Penilaian (rata-rata 1–5)', null, true);
    aspek.forEach(function (a) {
      const butir = skala.filter(function (p) {
        return p.aspek === a;
      });
      tulis('Aspek ' + a, rerata(butir.map(rentang)), true, '0.00');
      butir.forEach(function (p) {
        tulis('    ' + p.teks, rerata([rentang(p)]), false, '0.00');
      });
    });
    tulis('Rata-rata semua pernyataan', rerata(skala.map(rentang)), true, '0.00');
    baris++;

    k.pertanyaan.forEach(function (p) {
      if (p.jenis !== 'pilihan' && p.jenis !== 'pilihan-ganda') return;
      tulis(p.teks + ' (jumlah jawaban)', null, true);
      p.pilihan.forEach(function (opsi) {
        // Jawaban pilihan ganda ditulis Google sebagai satu sel: "A, B, C"
        const kriteria = p.jenis === 'pilihan' ? teksRumus(opsi) : teksRumus('*' + opsi + '*');
        tulis('    ' + opsi, '=COUNTIF(' + rentang(p) + ',' + kriteria + ')', false, '0');
      });
      baris++;
    });
    baris++;
  });
  lembar.setColumnWidth(1, 560);
  lembar.setColumnWidth(2, 120);
}
