# MASTER BLUEPRINT — KPI MONITORING KONTRAKTOR HSSE PROJECT PMU

> **Status dokumen:** Draft spesifikasi implementasi berdasarkan dokumen KPI kontraktor dan keputusan diskusi dengan user.  
> **Platform target:** Project HSSE Project PMU, Laravel 12, Blade, JavaScript, database existing.  
> **Pengguna utama:** HSE Owner / tim HSSE perusahaan untuk monitoring internal kinerja KPI kontraktor.  
> **Kontraktor awal:** PJSE, TDP / Security, JOTRE. Sistem harus mendukung penambahan kontraktor baru tanpa membuat halaman atau kode khusus baru.

---

## 0. PETUNJUK PENTING UNTUK ANTIGRAVITY

Dokumen ini adalah acuan kebutuhan bisnis dan teknis untuk pengembangan modul **KPI Monitoring**. Baca seluruh dokumen sebelum mengubah kode.

### Instruksi implementasi

1. Periksa project Laravel 12 existing terlebih dahulu: routes, authentication, role/permission, layout, sidebar, controller, model, migration, view, library chart, pola UI, dan tabel audit log.
2. Jangan mengasumsikan project kosong. Gunakan kembali struktur, komponen, library, dan pola coding existing jika sesuai.
3. Menu ini adalah alat monitoring internal HSE Owner. **Jangan membangun portal kontraktor, sistem HR lengkap, sistem roster/security lengkap, atau sistem asset management penuh** kecuali diminta secara khusus di kemudian hari.
4. Input realisasi dilakukan oleh user internal yang berwenang. Kontraktor tidak diasumsikan melakukan login atau menginput sendiri.
5. Implementasikan satu modul generik yang digerakkan oleh konfigurasi KPI dan kontraktor. Jangan membuat controller, halaman, atau tabel terpisah untuk setiap kontraktor.
6. Pertahankan histori. Menonaktifkan kontraktor atau KPI tidak boleh menghapus data, catatan, lampiran, realisasi, alokasi carry forward, atau hasil periode yang telah tersimpan.
7. Jangan menjalankan `migrate:fresh`, `db:wipe`, truncate, atau operasi destruktif lainnya.
8. Jangan mengubah modul lain di luar ruang lingkup KPI Monitoring.
9. Jangan melakukan commit, push, merge, atau mengubah branch Git secara otomatis.
10. Jangan mengarang nilai, target, bobot, rumus, atau kebijakan yang tidak didukung dokumen. Gunakan daftar **Keputusan yang Masih Perlu Dikonfirmasi** pada bagian akhir. Jika nilai yang belum pasti tidak menghalangi implementasi struktur, sediakan konfigurasi yang dapat diisi admin dan beri nilai awal yang belum aktif/berstatus perlu konfigurasi.
11. Lakukan implementasi dalam perubahan yang terukur. Setelah perubahan, laporkan file yang dibuat/diubah, migration, hasil tes, dan kendala.
12. Jangan mengklaim pengujian berhasil jika tidak benar-benar dijalankan.

### Prinsip prioritas

Urutan acuan adalah:
1. Keputusan bisnis yang secara eksplisit sudah disepakati dalam diskusi.
2. Dokumen KPI resmi masing-masing kontraktor untuk nama indikator, target, satuan, perspektif, dan frekuensi.
3. Struktur teknis project Laravel existing.
4. Rekomendasi teknis dalam blueprint ini, yang harus tetap fleksibel jika belum menjadi kebijakan resmi.

---

# BAGIAN I — TUJUAN DAN RUANG LINGKUP

## 1. Tujuan modul

**KPI Monitoring** adalah modul internal untuk membantu HSE Owner memantau apakah program, aktivitas, persyaratan, dan target HSSE kontraktor sudah dilaksanakan sesuai ketentuan.

Fokus utama modul:

- Menampilkan daftar KPI setiap kontraktor.
- Mencatat apakah kegiatan atau persyaratan sudah dilaksanakan.
- Mencatat angka realisasi apabila KPI berbasis jumlah, persentase, jam kerja, kasus, atau indeks.
- Menyimpan bukti pelaksanaan dan catatan HSE Owner.
- Menghitung capaian KPI sesuai jenis indikator dan periode target.
- Menampilkan target, realisasi, capaian, status, hutang, dan kredit secara transparan.
- Menyediakan ringkasan lintas kontraktor.
- Menyediakan histori dan pelaporan yang dapat ditelusuri.

Modul ini **bukan** sistem pengelolaan operasional kontraktor secara penuh. Master data personel, work area, kendaraan, peralatan, dan data shift hanya disediakan secukupnya untuk menghitung KPI yang memang membutuhkannya.

## 2. Kontraktor yang didukung

Kontraktor awal:

1. PJSE.
2. TDP / Security.
3. JOTRE.

Nama kontraktor dan jumlah kontraktor tidak boleh di-hardcode dalam kode program. Daftar kontraktor berasal dari tabel master `contractors`.

## 3. Struktur navigasi sidebar

Tambahkan section sidebar bernama **KPI Monitoring** dengan struktur konseptual:

- KPI Monitoring
  - Ringkasan
  - Monitoring Kontraktor (submenu dinamis dari kontraktor aktif)
  - Kelola Kontraktor
  - Kelola Master KPI (akses sesuai permission)

Perilaku:

- **Ringkasan** menampilkan rekap semua kontraktor.
- Setiap kontraktor aktif memiliki halaman monitoring sendiri, dibuat menggunakan satu template/controller generik dengan parameter kontraktor.
- Kontraktor nonaktif tidak tampil sebagai pilihan input/monitoring aktif di navigasi utama, tetapi tetap dapat diakses melalui filter histori/arsip oleh user yang berwenang.
- Penambahan kontraktor baru otomatis menambah pilihan submenu tanpa perubahan kode.
- Jika project memiliki pola navigasi atau permission existing, ikuti pola tersebut.

## 4. Batasan ruang lingkup versi awal

Termasuk:

- Master kontraktor.
- Master KPI per kontraktor.
- Konfigurasi target, frekuensi, metode input, metode perhitungan, periode, bobot (bila diperlukan), dan aturan carry/credit per KPI.
- Input realisasi sederhana oleh HSE Owner.
- Lampiran dan catatan.
- Ringkasan dan halaman per kontraktor.
- Rekap, filter, histori, export PDF (jika library existing tersedia).
- Import Excel untuk master data yang relevan.
- Audit trail.

Tidak termasuk pada versi awal kecuali diminta kemudian:

- Login/portal mandiri untuk kontraktor.
- HRIS atau payroll.
- Roster dan penjadwalan seluruh tenaga kerja secara penuh.
- Sistem manajemen kendaraan/peralatan penuh.
- Sistem patrol/GPS live tracking.
- Otomatisasi integrasi ke aplikasi eksternal yang belum tersedia.

---

# BAGIAN II — STRUKTUR MENU DAN TAMPILAN

## 5. Halaman Ringkasan

Halaman Ringkasan adalah dashboard lintas kontraktor.

Komponen yang disarankan:

1. Filter periode/tahun.
2. Filter kontraktor: Semua, PJSE, TDP, JOTRE, dan kontraktor aktif lainnya.
3. Ringkasan jumlah kontraktor aktif.
4. Total KPI aktif.
5. KPI tercapai.
6. KPI belum tercapai / dalam progres.
7. KPI belum memiliki input/perlu diperbarui.
8. Ringkasan Leading/Lagging atau perspektif, sesuai struktur KPI masing-masing kontraktor. Jangan menjumlahkan kategori yang tidak sebanding tanpa penjelasan.
9. Tabel perbandingan per kontraktor: jumlah KPI, capaian, indikator yang belum tercapai, hutang, dan kredit.
10. Daftar KPI yang perlu perhatian/follow-up.

**Penting:** jangan membuat satu nilai gabungan lintas kontraktor jika metode, bobot, atau skala KPI berbeda dan tidak dapat dibandingkan secara adil. Tampilkan ringkasan per kontraktor atau normalisasi hanya jika metode disepakati.

## 6. Halaman Monitoring Kontraktor

Gunakan template tampilan yang sama untuk semua kontraktor.

Header menampilkan:

- Nama kontraktor.
- Status aktif/nonaktif.
- Periode evaluasi yang dipilih.
- Tombol tambah/update realisasi sesuai permission.
- Tombol export sesuai permission.

Komponen utama:

1. Ringkasan pencapaian KPI kontraktor.
2. Filter periode, kategori/kelompok, status, dan pencarian KPI.
3. Daftar KPI yang dikelompokkan sesuai karakteristik kontraktor:
   - PJSE/JOTRE: Leading dan Lagging, dengan subkelompok bila diperlukan.
   - TDP: empat perspektif resmi, dengan klasifikasi tambahan KPI bila sudah ditetapkan.
4. Tabel KPI.
5. Detail KPI, riwayat realisasi, lampiran, catatan, saldo hutang/kredit.

Kolom tabel minimum:

- No.
- Nama KPI.
- Kategori/kelompok/perspektif.
- Frekuensi target.
- Target normal periode.
- Hutang/kredit awal (jika carry/credit aktif).
- Target efektif.
- Realisasi.
- Capaian KPI (maksimum 100%).
- Persentase aktual (dapat lebih dari 100%).
- Saldo hutang/kredit akhir.
- Status.
- Aksi/detail.

Kolom dapat disesuaikan agar tidak terlalu padat. Detail tambahan dapat berada pada modal atau panel detail.

## 7. Jenis input realisasi

HSE Owner dapat mencatat realisasi dengan salah satu pola berikut, sesuai konfigurasi KPI:

### 7.1 Checklist / status

Untuk KPI yang cukup dinilai terlaksana atau belum terlaksana.

Field minimum:
- Tanggal/periode.
- Status: terlaksana / belum terlaksana / tidak berlaku (jika diizinkan).
- Catatan.
- Bukti (opsional atau wajib sesuai konfigurasi).

### 7.2 Kuantitatif

Untuk KPI berupa jumlah kegiatan, laporan, inspeksi, personel, atau kasus.

Field minimum:
- Tanggal/periode.
- Nilai realisasi.
- Satuan.
- Catatan.
- Lampiran.

### 7.3 Persentase / kepatuhan

Untuk KPI target 100% atau persentase tertentu.

Field minimum:
- Pembilang/hasil terpenuhi.
- Penyebut/total yang diwajibkan, jika relevan.
- Persentase realisasi (dapat dihitung otomatis).
- Catatan dan bukti.

### 7.4 Input harian atau per kegiatan

Metode input dipisahkan dari frekuensi target. Contoh: KPI target mingguan dapat menerima beberapa input kegiatan sepanjang minggu. Sistem mengakumulasi input tersebut ke periode target yang relevan.

---

# BAGIAN III — MASTER KONTRAKTOR DAN DATA OPERASIONAL RINGAN

## 8. Master kontraktor

Tabel master kontraktor minimal memiliki:

- `id`
- `name`
- `code` (unique jika digunakan)
- `description`
- `is_active`
- `effective_from` (opsional)
- `effective_until` (opsional)
- `created_at`, `updated_at`

Fungsi:

- Tambah kontraktor.
- Edit nama/kode/keterangan.
- Aktifkan/nonaktifkan.
- Filter aktif/nonaktif/semua.
- Import Excel dengan preview dan validasi.

Menonaktifkan kontraktor **bukan menghapus kontraktor**. Semua KPI, realisasi, lampiran, histori, saldo carry/credit, dan laporan historis harus tetap terkait dengan `contractor_id` semula.

Jangan memakai `ON DELETE SET NULL` untuk alur nonaktif. Nonaktif adalah perubahan status, bukan penghapusan record.

## 9. Master data operasional ringan

Data operasional hanya dibuat bila dibutuhkan rumus KPI tertentu. Hindari membangun sistem HR/asset management penuh.

Jenis data yang mungkin diperlukan:

- Personel dan kategori jabatan (termasuk supervisor).
- Work area.
- Kendaraan.
- Peralatan.
- Pos penjagaan dan kebutuhan personel per shift untuk TDP.

Metode pengelolaan:

- Input/edit langsung dari aplikasi.
- Import Excel.
- Validasi duplikasi.
- Preview sebelum konfirmasi import.
- Riwayat perubahan.
- Status aktif/nonaktif.
- Tanggal mulai dan akhir berlaku (`effective_from`, `effective_until`).

### 9.1 Snapshot dan tanggal efektif

Perubahan jumlah personel, supervisor, work area, kendaraan, atau peralatan tidak boleh mengubah histori perhitungan periode terdahulu.

Simpan snapshot kuantitas/daftar objek yang menjadi dasar target saat periode dihitung/dikunci. Simpan juga tanggal efektif perubahan.

### 9.2 Perubahan di tengah periode

Keputusan: target menyesuaikan sejak tanggal efektif perubahan.

Prorata menggunakan **hari kerja yang relevan**, bukan otomatis semua hari kalender.

Rumus umum untuk komponen berbasis jumlah objek:

`Target prorata = target per objek × total exposure objek selama hari kerja yang berlaku`

Contoh exposure personel:

`Person-days = jumlah personel aktif pada setiap hari kerja yang relevan, dijumlahkan sepanjang periode.`

Untuk target per minggu/per shift/per mobilisasi, gunakan unit kewajiban yang tepat; jangan memaksakan prorata hari kerja jika target berbasis pemicu kegiatan.

Snapshot periode harus menyimpan:

- Jumlah objek/personel yang menjadi dasar target.
- Tanggal efektif.
- Hari kerja yang dihitung.
- Hasil target normal.
- Sumber data/snapshot.

## 10. Kalender kerja kontraktor

Masing-masing kontraktor memiliki kalender kerja sendiri.

### 10.1 PJSE

- Hari kerja normal: Senin–Jumat.
- Sabtu dan Minggu dianggap lembur/non-hari kerja normal.
- Sabtu/Minggu hanya masuk perhitungan target jika ada pekerjaan/lembur yang tercatat dan KPI terkait memang berlaku pada pekerjaan tersebut.

### 10.2 TDP / Security

- Operasional: Senin–Minggu.
- Tiga shift: Pagi, Sore, Malam.
- Seluruh pos penjagaan wajib memiliki personel pada ketiga shift setiap hari.
- Setiap pos dapat membutuhkan satu atau lebih personel per shift; jumlah kebutuhan dapat dikonfigurasi per pos dan shift.
- Jangan mengasumsikan setiap personel bekerja tujuh hari berturut-turut. Perhitungan personel mengikuti roster/jadwal aktual.
- Untuk versi awal, cukup sediakan pencatatan/monitoring kebutuhan dan realisasi coverage sebagai data pendukung KPI; jangan membangun sistem roster lengkap.

### 10.3 JOTRE

- Hari kerja efektif: Senin–Sabtu.
- Minggu: beberapa personel/aktivitas tetap bekerja.
- Minggu hanya masuk perhitungan untuk personel/aktivitas yang memang dijadwalkan atau tercatat bekerja.

### 10.4 Pengaturan kalender

Kalender dapat dikonfigurasi per kontraktor dan, bila dibutuhkan, per kelompok personel/area. Hari libur, jadwal shift, dan lembur dapat diperhitungkan jika data tersedia.

---

# BAGIAN IV — MASTER KPI DAN KONFIGURASI PERHITUNGAN

## 11. Master KPI

Setiap kontraktor memiliki daftar KPI sendiri. Sistem menggunakan struktur master KPI generik.

Field konseptual yang perlu didukung:

- `id`
- `contractor_id`
- `code` (kode unik per kontraktor)
- `name`
- `description`
- `source_group` / `perspective` (mis. Leading, Lagging, Goal Oriented, Operasional, dll.)
- `indicator_type` (Leading, Lagging, Compliance, Service Outcome, Response, atau kategori yang dikonfigurasi)
- `unit_of_measure`
- `target_value`
- `target_operator` (equal, minimum, maximum, zero, percentage, index, atau konfigurasi sejenis)
- `target_frequency`
- `evaluation_period`
- `input_method` (checklist, quantity, percentage, index, event, compliance)
- `calculation_method`
- `allow_carry_forward`
- `allow_credit_forward`
- `credit_validity_type`
- `credit_validity_value` (jika dibatasi)
- `weight` (nullable; wajib hanya bila scorecard menggunakan bobot KPI individual)
- `requires_evidence`
- `is_active`
- `effective_from`, `effective_until`
- `sort_order`
- timestamps

Konfigurasi dapat ditambah di masa depan tanpa mengubah tabel khusus setiap kontraktor.

## 12. Pemisahan frekuensi target, periode evaluasi, dan metode input

Ketiganya merupakan hal berbeda dan harus disimpan terpisah.

### 12.1 Frekuensi target / recurrence

Sistem perlu mendukung:

- Daily.
- Weekly.
- Biweekly (telah dikonfirmasi: satu kali setiap dua minggu).
- Monthly.
- Quarterly.
- Biannually (telah dikonfirmasi: dua kali dalam setahun / setiap enam bulan).
- Annually.
- Per Project.
- Before activity / before work commencement.
- Before mobilization.
- Before site access.
- Per use / pre-use.
- Event-based / triggered by incident or significant finding.
- As scheduled / as required.

### 12.2 Periode evaluasi

Contoh: harian, mingguan, bulanan, kuartalan, semesteran, tahunan, atau masa proyek.

Periode evaluasi tidak selalu sama dengan frekuensi input.

### 12.3 Metode input

- Harian.
- Per kegiatan.
- Per laporan.
- Per personel/objek.
- Per periode.
- Checklist/status.

## 13. Metode kalkulasi yang didukung

Gunakan `calculation_method` yang dapat dikonfigurasi. Minimal dukung:

1. `zero_event`: target nol kejadian.
2. `maximum_threshold`: jumlah kejadian tidak boleh melebihi batas.
3. `minimum_quantity`: jumlah realisasi minimal.
4. `target_quantity`: realisasi terhadap target jumlah.
5. `schedule_compliance`: jadwal/kegiatan terlaksana dibanding kewajiban terjadwal.
6. `percentage_compliance`: hasil terpenuhi dibanding total yang wajib.
7. `personnel_compliance`: personel memenuhi persyaratan dibanding personel wajib.
8. `asset_compliance`: kendaraan/peralatan yang memenuhi dibanding objek wajib.
9. `route_completion`: rute patroli selesai dibanding rute terjadwal.
10. `response_time_compliance`: respons memenuhi batas waktu dibanding total kejadian yang wajib ditangani.
11. `finding_closure`: temuan selesai ditindaklanjuti dibanding temuan yang wajib ditindaklanjuti.
12. `index_score`: indeks/survey sesuai skala yang ditentukan.
13. `man_hours`: akumulasi jam kerja aktual.
14. `event_completion`: kegiatan berbasis pemicu/proyek selesai sesuai persyaratan.
15. `record_and_report`: seluruh kejadian/observasi yang diketahui dicatat dan dilaporkan; jangan menganggap jumlah laporan yang lebih banyak sebagai pelanggaran tanpa aturan resmi.

### 13.1 Target quantity / minimum

Untuk KPI jumlah kegiatan:

`Persentase aktual = realisasi / target pembanding × 100%`

Jika target lebih dari nol. Penanganan target nol harus memakai metode khusus.

### 13.2 Compliance / percentage

`Persentase aktual = jumlah yang memenuhi / jumlah yang wajib × 100%`

Jika denominator nol atau tidak ada objek/kegiatan yang berlaku, sistem harus menampilkan `N/A` atau `Tidak Berlaku` sesuai konfigurasi, bukan otomatis memberi 100% tanpa aturan.

### 13.3 Zero event

- Realisasi 0 kejadian: 100%.
- Realisasi lebih dari 0: 0%.

### 13.4 Maximum threshold

Jika target adalah batas maksimum, sistem harus menggunakan operator yang benar. Untuk First Aid Case PJSE, telah dikonfirmasi: maksimum 2 kasus; lebih dari 2 menyebabkan capaian 0%. Perlakuan skor untuk 0, 1, dan 2 kasus masih perlu dikonfirmasi (lihat daftar pertanyaan terbuka).

### 13.5 Lagging

Lagging umumnya semakin sedikit semakin baik. Namun, near miss yang harus dicatat dan dilaporkan tidak boleh dinilai dengan prinsip “semakin sedikit semakin baik” tanpa memeriksa definisi target. Target `All Record & Report` adalah kepatuhan pencatatan/pelaporan.

### 13.6 Capaian, persentase aktual, dan kredit

Untuk KPI yang memiliki carry/credit:

- **Capaian KPI:** maksimum 100% setelah memperhitungkan kewajiban efektif.
- **Persentase aktual:** realisasi dibanding target normal periode; boleh lebih dari 100%.
- **Kredit:** surplus realisasi yang memenuhi syarat dan tersedia untuk periode berikutnya.
- **Hutang:** kewajiban periode berjalan yang belum terpenuhi dan boleh dibawa ke depan.

Jangan menampilkan satu angka saja jika hal itu menghilangkan informasi hutang/kredit.

---

# BAGIAN V — CARRY FORWARD DAN CREDIT CARRY FORWARD

## 14. Keputusan bisnis yang telah disepakati

1. Model histori: **Model A** — hasil periode sebelumnya tetap, tidak diubah ketika hutang dilunasi di periode berikutnya.
2. Kekurangan target boleh dibawa sebagai hutang untuk KPI yang mengaktifkan fitur tersebut.
3. Kelebihan realisasi boleh menjadi kredit untuk KPI yang mengaktifkan fitur tersebut.
4. Prioritas alokasi realisasi: FIFO (kewajiban tertua terlebih dahulu).
5. Masa berlaku kredit dikonfigurasi per KPI.
6. Capaian KPI maksimum 100%.
7. Persentase aktual boleh lebih dari 100%.
8. Kredit ditampilkan secara terpisah.
9. Carry/credit tidak boleh otomatis aktif untuk semua KPI. Default harus mengikuti konfigurasi KPI dan kebijakan bisnis.
10. KPI yang sifatnya wajib tepat waktu, zero-event, pre-mobilization, pre-use, pre-shift, atau keselamatan kritis sebaiknya default carry/credit nonaktif sampai ada persetujuan eksplisit.

## 15. Saldo berjalan

Gunakan satu konsep saldo per KPI/per kontraktor/per cakupan yang relevan:

`Saldo akhir = saldo awal + target normal - realisasi eligible`

Interpretasi:

- Saldo positif = hutang.
- Saldo negatif = kredit.
- Saldo nol = kewajiban bersih terpenuhi.

Target efektif periode:

`Target efektif = max(0, target normal + saldo awal)`

Dalam implementasi aktual, realisasi harus dialokasikan ke kewajiban periode yang benar melalui ledger/alokasi, bukan hanya menghitung satu angka saldo tanpa detail sumber.

### 15.1 Urutan alokasi

- Realisasi yang sah dialokasikan ke kewajiban tertua yang masih terbuka (FIFO).
- Setelah hutang lama terpenuhi, realisasi memenuhi kewajiban periode berjalan.
- Jika masih ada surplus dan credit carry forward aktif, sisa surplus dibuat menjadi kredit.
- Kredit yang tersedia dapat mengurangi target efektif periode berikutnya sesuai masa berlaku kredit KPI.
- Kredit hanya dapat digunakan untuk KPI, kontraktor, satuan, dan cakupan yang sama. Contoh Near Miss area A tidak boleh menutup target area B jika target ditentukan per area.
- Jangan mengubah tanggal aktual kegiatan untuk memindahkan realisasi ke periode lalu.

### 15.2 Contoh Model A — Hutang

Target normal 4 kali per bulan.

| Komponen | Januari | Februari |
|---|---:|---:|
| Target normal | 4 | 4 |
| Hutang awal | 0 | 1 |
| Target efektif | 4 | 5 |
| Realisasi | 3 | 5 |
| Capaian efektif | 75% | 100% |
| Hutang akhir | 1 | 0 |

Januari tetap tercatat 75%. Pelunasan di Februari tidak mengubah histori Januari.

### 15.3 Contoh Model A — Kredit

Target normal 4 kali per bulan.

| Komponen | Januari | Februari |
|---|---:|---:|
| Target normal | 4 | 4 |
| Kredit awal | 0 | 1 |
| Target efektif | 4 | 3 |
| Realisasi | 5 | 3 |
| Persentase aktual vs target normal | 125% | 75% |
| Capaian efektif | 100% | 100% |
| Kredit akhir | 1 | 0 |

### 15.4 Tampilan Opsi C yang disepakati

Jika Januari target 4 dan realisasi 5:

- Capaian KPI: 100%.
- Persentase aktual: 125%.
- Kredit: +1 kegiatan.

Jika Februari target normal 4, menerima hutang 1 dari Januari, dan realisasi 6:

- Target efektif: 5.
- Persentase aktual terhadap target normal: 150%.
- Capaian efektif: 100%.
- Kredit akhir: +1 kegiatan.

### 15.5 Masa berlaku kredit

Masa berlaku kredit diatur per KPI. Sediakan opsi konfigurasi yang dapat dipilih admin, misalnya:

- Hanya periode berikutnya.
- Jumlah periode tertentu.
- Sampai akhir tahun.
- Sampai akhir periode proyek.
- Tidak kedaluwarsa selama KPI berlaku.

Ini adalah pilihan konfigurasi sistem; masa berlaku aktual harus ditetapkan per KPI. Sistem harus menyimpan tanggal sumber kredit, jumlah awal, jumlah digunakan, sisa, tanggal kedaluwarsa, dan status.

### 15.6 Ledger hutang/kredit

Simpan transaksi hutang/kredit secara terpisah, minimal berisi:

- KPI dan kontraktor.
- Periode sumber.
- Periode penggunaan/pelunasan.
- Tipe: debt atau credit.
- Jumlah awal, jumlah digunakan, jumlah sisa.
- Tanggal dibuat dan kedaluwarsa.
- Referensi realisasi yang mengalokasikan saldo.
- Status.

Hindari hanya menyimpan saldo terakhir tanpa histori transaksi.

---

# BAGIAN VI — DATA KERJA DAN TARGET DINAMIS

## 16. Kalender kerja kontraktor

Kalender kerja telah ditetapkan sebagai berikut:

| Kontraktor | Kalender kerja |
|---|---|
| PJSE | Senin–Jumat kerja normal. Sabtu/Minggu lembur; dihitung jika ada pekerjaan/lembur yang tercatat dan KPI terkait berlaku. |
| TDP/Security | Operasional Senin–Minggu, tiga shift (pagi, sore, malam). Seluruh pos wajib terisi pada ketiga shift setiap hari. |
| JOTRE | Hari kerja efektif Senin–Sabtu. Minggu hanya untuk personel/aktivitas yang memang dijadwalkan bekerja. |

### 16.1 TDP tiga shift

- Shift: Pagi, Sore, Malam.
- Semua pos wajib memiliki personel setiap shift setiap hari.
- Setiap pos dapat membutuhkan satu atau lebih personel per shift.
- Kebutuhan personel dikonfigurasi per pos dan shift.
- Penggantian personel yang sah dapat dihitung sebagai penempatan terpenuhi.
- Jangan mengasumsikan satu personel bekerja di ketiga shift atau tujuh hari berturut-turut.
- Versi awal cukup mencatat kebutuhan dan realisasi coverage untuk kebutuhan monitoring, bukan membangun aplikasi roster lengkap.

Rumus coverage jika KPI yang dinilai memang coverage:

`Coverage (%) = penempatan terpenuhi / total kebutuhan penempatan × 100%`

KPI resmi TDP **Penjagaan Area** memiliki target kurang dari 12 kejadian temuan area tidak dijaga/kosong. Monitoring coverage shift dapat menjadi sumber informasi operasional, tetapi jangan otomatis menyamakan setiap kekurangan penempatan dengan satu temuan resmi tanpa verifikasi.

### 16.2 Perubahan personel/aset di tengah periode

Keputusan: perubahan mengikuti tanggal efektif dan target menyesuaikan sejak tanggal tersebut.

Metode prorata yang disepakati: **berdasarkan hari kerja**, bukan kalender.

Untuk KPI berbasis personel/asset yang sesuai:

`Exposure = total jumlah objek aktif pada setiap hari kerja yang relevan`

`Target periode = target per objek × exposure / normalisasi frekuensi target`

Detail normalisasi tergantung KPI (per hari, minggu, bulan, atau kegiatan). Jangan menggunakan satu formula prorata untuk semua KPI.

- Perubahan pada personel, supervisor, area, kendaraan, atau peralatan memiliki effective date.
- Snapshot periode menyimpan jumlah/daftar objek yang dipakai menghitung target.
- Perubahan master data tidak boleh mengubah hasil historis.
- Untuk mobilisasi kendaraan/peralatan, hitung berdasarkan kejadian mobilisasi dan inspeksi wajib sebelum mobilisasi, bukan prorata hari kerja.

---

# BAGIAN VII — DAFTAR KPI RESMI PER KONTRAKTOR

> Nama, target, satuan, dan frekuensi pada bagian ini diambil dari dokumen yang diberikan user. Jika ada interpretasi yang belum pasti, hal tersebut dinyatakan secara eksplisit.

## 17. PJSE

**Sumber:** `PJSE Indikator.pdf` (1 halaman).

PJSE memiliki 9 Lagging Indicator dan 16 Leading Indicator.

### 17.1 PJSE — Lagging Indicator

| No. | KPI | Target/satuan dari dokumen | Metode awal |
|---:|---|---|---|
| 1 | Fatality | 0 | Zero event |
| 2 | LTI | 0 | Zero event |
| 3 | PAK | 0 | Zero event |
| 4 | MTC | 0 | Zero event |
| 5 | First Aid Case | Maksimal 2 kasus | Maximum threshold; detail skor 0–2 perlu konfirmasi |
| 6 | Nearmiss | 2 (dokumen tidak menjelaskan apakah minimum atau maksimum) | Belum ditetapkan; perlu konfirmasi |
| 7 | Tumpahan B3 | 0 | Zero event |
| 8 | Kerusakan aset/peralatan | 0 | Zero event |
| 9 | Kebakaran | 0 | Zero event |

Klarifikasi user: untuk First Aid Case, angka 2 berarti maksimal 2 kasus. Jika lebih dari 2, capaian KPI menjadi 0%. Perlakuan skor jika realisasi 0, 1, atau 2 belum dikonfirmasi lebih lanjut.

### 17.2 PJSE — Leading Indicator

| No. | KPI | Target/frekuensi dari dokumen | Metode awal |
|---:|---|---|---|
| 1 | Rapat K3LL | 1 kali/bulan | Target quantity / checklist |
| 2 | Promosi K3LL | 1 kali/project | Project completion |
| 3 | Induction K3LL | Semua personel | Personnel compliance |
| 4 | Laporan K3LL | 1 kali/bulan | On-time/target quantity; detail due date perlu disepakati |
| 5 | Management Visit | 1 kali/project | Project completion |
| 6 | Inspeksi Lapangan | 1 kali/minggu | Schedule compliance |
| 7 | Tindak lanjut temuan | 100% | Finding closure |
| 8 | Hazard Observation Card | 1 pelaporan/minggu/orang | Target quantity per person |
| 9 | Housekeeping | 1 kali/hari | Daily schedule compliance |
| 10 | Emergency Drill | 1 kali/project | Project completion |
| 11 | Man Hours | Sesuai jumlah pekerja | Man-hours accumulation |
| 12 | Pre Job Safety Meeting | Sebelum memulai pekerjaan | Work commencement compliance |
| 13 | Inspeksi kendaraan | Per hari | Daily inspection compliance; denominator kendaraan aktif/jadwal |
| 14 | Inspeksi kelayakan APD | 1 kali/bulan | Periodic inspection |
| 15 | Inspeksi peralatan keselamatan (APAR dan kotak P3K) | 1 kali/bulan | Periodic inspection |
| 16 | Inspeksi peralatan kerja | Sebelum digunakan | Pre-use inspection compliance |

Klarifikasi user untuk Inspeksi Lapangan: target 1 kali per minggu berlaku untuk keseluruhan scope. Daftar Pad 31, Pad 9, Pad 7, Pad 29, Pad 38, Pad 18, dan TO hanya merupakan cakupan lokasi yang boleh dipilih, bukan target satu inspeksi per lokasi.

### 17.3 Catatan khusus PJSE

- Kalender normal Senin–Jumat.
- Sabtu/Minggu adalah lembur; hari tersebut hanya dihitung jika ada pekerjaan/lembur yang dicatat dan KPI terkait berlaku.
- Near Miss target `2` belum jelas maknanya (minimum laporan atau batas maksimum). Jangan menetapkan rumus final sebelum dikonfirmasi.
- First Aid Case: batas maksimal dua kasus; jika lebih dari dua, skor 0%. Skor untuk 0, 1, dan 2 kasus perlu ditetapkan.
- HOC per orang harus memakai jumlah personel relevan dan snapshot yang efektif pada periode.
- Man Hours berdasarkan jumlah pekerja dan jam kerja yang menjadi dasar pencatatan; jangan mengasumsikan jam kerja bila belum tersedia.

## 18. TDP / SECURITY

**Sumber:** tabel TDP pada gambar yang diberikan user. Halaman 1–2 file `JOTRE KPI.pdf` sebenarnya milik TDP dan telah dikonfirmasi user. Halaman 3–10 file tersebut adalah milik JOTRE.

TDP memiliki empat perspektif dan total 11 KPI.

### 18.1 Perspektif, bobot, dan KPI

| Perspektif | Bobot perspektif | KPI | Target | Satuan |
|---|---:|---|---:|---|
| Goal Oriented | 40% | Zero Major Loss | 0 | Kejadian |
| Goal Oriented | 40% | Zero LTI | 0 | Kejadian |
| Operasional | 20% | Pelaksanaan Patroli | 100% | Persen |
| Operasional | 20% | Penjagaan Area (temuan area tidak dijaga/kosong) | <12 | Kejadian |
| Operasional | 20% | Pelaporan & Penanganan Kondisi Darurat (pencurian, sabotase, huru-hara, demo, kebakaran, gempa; <30 menit setelah kejadian) | 100% | Persen |
| Operasional | 20% | Laporan PIJAR | 50 | Laporan/tahun |
| Kompetensi & SDM | 20% | Pelaksanaan Program Pelatihan | 100% | Persen |
| Kompetensi & SDM | 20% | Sertifikasi Personil (Gada Pratama/Madya dan KTA masih berlaku) | 100% | Persen |
| Service Excellence | 20% | Kepuasan Pengguna (Service Excellence)/tahun | 85 | Index |
| Service Excellence | 20% | Tingkat Kehadiran BUJP Representative | 90% | Persen |
| Service Excellence | 20% | Pelaporan bulanan melalui email sebelum tanggal 10 setiap bulan | 100% | Persen |

### 18.2 Klasifikasi sekunder TDP (usulan awal)

Pertahankan empat perspektif resmi sebagai kelompok utama. Untuk analisis sekunder, klasifikasi dapat disediakan seperti:

- Lagging: Zero Major Loss, Zero LTI.
- Leading: Pelaksanaan Patroli, Penjagaan Area, Laporan PIJAR, Program Pelatihan.
- Compliance/Response: Penanganan Kondisi Darurat, Sertifikasi Personil, Kehadiran Representative, Pelaporan Bulanan.
- Service Outcome: Kepuasan Pengguna.

Klasifikasi ini merupakan usulan sistem, bukan penggantian perspektif resmi TDP.

### 18.3 Bobot TDP

- Bobot perspektif dari dokumen: Goal Oriented 40%, Operasional 20%, Kompetensi & SDM 20%, Service Excellence 20%.
- Scorecard menunjukkan adanya kolom skor KPI, skor rata-rata perspektif, dan skor akhir tertimbang.
- Dugaan cara hitung adalah rata-rata skor KPI di dalam perspektif dikalikan bobot perspektif.
- **Jangan mengunci rumus final sebelum verifikasi formula scorecard resmi**, karena angka Service Excellence pada contoh scorecard belum sepenuhnya dapat direkonsiliasi dari tampilan yang tersedia.
- Sistem sebaiknya mendukung bobot per perspektif dan bobot KPI opsional. Jika bobot KPI tidak disediakan, jangan otomatis menetapkan pembagian tanpa persetujuan; tampilkan sebagai konfigurasi yang perlu dilengkapi.

### 18.4 Operasional TDP

- TDP/Security beroperasi Senin–Minggu.
- Tiga shift: Pagi, Sore, Malam.
- Setiap pos wajib memiliki personel pada ketiga shift setiap hari.
- Setiap pos dapat membutuhkan satu atau lebih personel per shift.
- Kebutuhan personel per pos/shift dikonfigurasi.
- Sistem versi awal hanya membutuhkan pencatatan monitoring coverage sederhana jika relevan, bukan aplikasi roster lengkap.
- KPI resmi Penjagaan Area tetap dinilai dari jumlah temuan area tidak dijaga/kosong dengan target kurang dari 12 kejadian. Data coverage shift dapat menjadi sumber verifikasi, tetapi satu kekurangan penempatan tidak otomatis menjadi temuan resmi tanpa verifikasi.

## 19. JOTRE

**Sumber:** `JOTRE KPI.pdf`, hanya halaman 3–10. User mengklarifikasi bahwa halaman 1–2 adalah milik TDP dan bukan bagian KPI JOTRE.

JOTRE menggunakan struktur Leading dan Lagging. Tidak ada bobot perspektif JOTRE yang dikonfirmasi dalam halaman 3–10. Berdasarkan rincian daftar pada halaman 5–10, terdapat 15 entri pada kelompok HSSE Inspection & Audit (karena nomor 3.5 muncul dua kali pada dokumen sumber) dan total 54 entri Leading.

### 19.1 JOTRE — Lagging Indicator

#### A. Total Incident Case

| No. | KPI | Target |
|---:|---|---|
| 1 | Fatality | Zero |
| 2 | Major / Medical Treatment Case | Zero |
| 3 | Light Accident / First Aid | ≤ 6 Case |
| 4 | Near Miss | All Record & Report |

#### B. Type Incident

| No. | KPI | Target |
|---:|---|---|
| 1 | Traffic Incident | Zero Traffic Incident |
| 2 | Security Incident | Zero Security Breach |
| 3 | Environmental Incident | Zero major environmental incident/spill |
| 4 | Property Damage | Zero Property Damage |
| 5 | Fire Incident | Zero Fire Incident |

Catatan dokumen: untuk spill Tier 1, ada keterangan immediate response dan formal report kepada Employer dalam 24 jam.

#### C. Medical Definition

| No. | KPI | Target |
|---:|---|---|
| 1 | Fit for Duty (new worker) | 100% recorded & medically assessed |
| 2 | Fit with Note | 100% recorded & monitored |
| 3 | Unfit | 100% recorded & managed |
| 4 | Referral Case (illness) | 100% recorded & followed up |
| 5 | Medical Treatment Case | Zero |
| 6 | Return Work Fit | 100% medically verified |
| 7 | RWC (Restricted Work Case) | Zero |
| 8 | Restricted Work-Days Case (RWDC) | Zero |

#### D. Statistics

| No. | KPI | Target |
|---:|---|---|
| 1 | Safe Man Hours | Recorded |
| 2 | Safe Man Days | Recorded |
| 3 | Lost Time Injury (LTI) | Zero |
| 4 | Lost Workdays (LWD) | Zero |
| 5 | Total Recordable Case (TRC) | ≤ 1 minor case |
| 6 | Total Recordable Injury Rate (TRIR) | Zero |
| 7 | Lost Time Injury Rate (LTIR) | Zero |
| 8 | First Aid Frequency Rate (FATR) | 0 |
| 9 | GRM Case | Zero unresolved case |
| 10 | SEAH Case | Zero tolerance |

**Penting:** beberapa kejadian yang sama muncul pada beberapa kelompok (misalnya LTI dan MTC). Sistem harus menghindari input/perhitungan ganda. Satu sumber kejadian dapat dipakai untuk beberapa tampilan KPI turunan jika definisi dan perhitungannya memang sepadan.

### 19.2 JOTRE — Leading Indicator

#### A. Leadership & Commitment

| Kode | KPI | Target |
|---|---|---|
| 1.1 | Senior Management Visibility Visit (PD & VP) | Biannually (2 kali/tahun) |
| 1.2 | Management Visibility Visit (Head Department & PMT) | Quarterly |

#### B. HSSE Communication

| Kode | KPI | Target |
|---|---|---|
| 2.1 | Project HSSE Kick-Off Meeting | Conducted prior to project commencement and major activity |
| 2.2 | Pre-Shift Supervisor Meeting | Weekly |
| 2.3 | Pre-Shift Toolbox Meeting | Daily before work commencement |
| 2.4 | General Toolbox Talk (TBT) | Weekly |
| 2.5 | HSS Progress Meeting | Weekly |
| 2.6 | HSSE Committee Meeting | Monthly |
| 2.7 | HSSE Stand Down | Event-based / after significant incident or major finding |
| 2.8 | T-SAFE Card / Safety Observation Card | Minimum 2 cards/week for supervisor level and above |
| 2.9 | Near Miss Report | Minimum 2 reports/month/work area |
| 2.10 | Safety Campaign / Awareness Program | Minimum Monthly |
| 2.11 | Health Talk / Promotion Program | Monthly |
| 2.12 | Reward & Punishment Program | Monthly evaluation and implementation |

#### C. HSSE Inspection & Audit

| Kode | KPI | Target |
|---|---|---|
| 3.1 | Pre-Mobilization Heavy Vehicle Inspection | 100% prior to mobilization |
| 3.2 | Pre-Mobilization Light Vehicle Inspection | 100% prior to mobilization |
| 3.3 | Pre-Mobilization Equipment Inspection | 100% prior to mobilization |
| 3.4 | Vehicle KM Record Monitoring | Weekly |
| 3.5a | Scaffolding / Working Access Inspection | Weekly |
| 3.5b | Pre-Use Inspection (QR Code System) — Light & Heavy Vehicle, Equipment, etc. | Daily prior to equipment use |
| 3.6 | Daily HSSE Walkabout (Site Inspection including PPE Implementation) | Daily |
| 3.7 | Planned HSSE Inspection / Joined Walkabout | Biweekly (1 kali setiap dua minggu) |
| 3.8 | Hygiene & Sanitation Inspection | Monthly |
| 3.9 | Internal / External HSSE Audit | 100% as scheduled (target 2 times/year) |
| 3.10 | Preventive Maintenance for HSSE Critical Equipment (Fire Extinguisher, Fire Suppression System, etc.) | Monthly |
| 3.11 | Infectious Disease Prevention Program | Monthly monitoring and awareness |
| 3.12 | Camp Hygiene Inspection | Monthly |
| 3.13 | Subcontractor HSSE Audit | Quarterly |
| 3.14 | Color Code Inspection (Hand and Power Tools, Lifting Gear, Light & Heavy Vehicle, Equipment, etc.) | Quarterly |

Dokumen sumber mengulang kode 3.5 untuk dua indikator. Dalam sistem, gunakan kode internal unik (misalnya 3.5a dan 3.5b) tanpa mengubah nama KPI yang ditampilkan.

#### D. Hazard Identification, Risk Assessment & Control

| Kode | KPI | Target |
|---|---|---|
| 4.1 | HSSE Engineering Studies (HAZOP, HAZID, SIL, etc.) | Conducted as required by project phase/activity |
| 4.2 | Construction / Installation HAZID | Conducted prior to major activity commencement |
| 4.3 | JSA / JHA / TRA Development, Review & Discussion | 100% for high-risk activities and weekly review |
| 4.4 | Permit to Work (PTW) Compliance | 100% compliance for applicable work activities |

#### E. HSSE Training, Competency & Fitness for Work

| Kode | KPI | Target |
|---|---|---|
| 5.1 | Medical Check-Up (MCU) | 100% workers medically assessed prior to work |
| 5.2 | Random Drug & Alcohol Test | Weekly random testing program |
| 5.3 | Project / Worksite HSSE Induction | 100% personnel prior to site access |
| 5.4 | Emergency Response / AFF / Rescue Training | Quarterly |
| 5.5 | Craft Competency Training (Rigging, Scaffolding, etc.) | 100% for applicable personnel |
| 5.6 | HSSE Mandatory Training (JSA, PTW, WAH, etc.) | 100% for applicable personnel |
| 5.7 | Client HSSE Required Training | 100% compliance |
| 5.8 | Visitor HSSE Induction | 100% visitors prior to site access |

#### F. Program Lainnya

| Kelompok | KPI | Target |
|---|---|---|
| 6. Security Program | Security Program | Monthly implementation and monitoring |
| 7. Emergency Drill | Site Emergency Drill | Quarterly |
| 7. Emergency Drill | Community Emergency Drill | Quarterly |
| 8. Environmental Program | Environmental Program | Monthly implementation and monitoring |
| 9. Waste Handling & Disposal Program | Waste Handling & Disposal Program | Quarterly evaluation and compliance monitoring |
| 10. Environmental Monitoring Program | Environmental Monitoring Program | Monthly |

#### G. Social Program

| Kode | KPI | Target |
|---|---|---|
| 11.1 | Worker Socialization & Training Program | 100% workforce participation |
| 11.2 | Stakeholder Communication | Monthly |
| 11.3 | Grievance Redress Mechanism (GRM) Socialization | Monthly |
| 11.4 | Local Hire Program | Monthly monitoring |
| 11.5 | Local Enterprise Program | Monthly monitoring |
| 11.6 | Community Health & Safety Program | Monthly |
| 11.7 | Gender Action Plan Implementation | Monthly monitoring and reporting |

JOTRE biweekly telah dikonfirmasi sebagai satu kali setiap dua minggu. Biannually telah dikonfirmasi sebagai dua kali dalam satu tahun.

JOTRE memiliki kalender kerja efektif Senin–Sabtu. Hari Minggu hanya dihitung untuk personel atau aktivitas yang memang dijadwalkan bekerja.

---

# BAGIAN VIII — ARSITEKTUR DATA YANG DIREKOMENDASIKAN

## 20. Prinsip database

- Relasional dan dapat diaudit.
- Master KPI terpisah dari realisasi.
- Target per periode disimpan sebagai record/snapshot.
- Realisasi disimpan per input/per kegiatan, bukan hanya satu angka yang ditimpa.
- Hutang/kredit disimpan sebagai ledger/alokasi.
- Lampiran memiliki relasi jelas ke realisasi atau KPI.
- Data kontraktor nonaktif tetap tersedia.
- Gunakan migration baru dan perubahan bertahap.

## 21. Tabel konseptual

Nama berikut adalah rekomendasi. Sebelum membuat migration, periksa tabel existing dan gunakan tabel yang sudah tersedia jika cocok.

### 21.1 `contractors`

Master kontraktor.

Field konseptual: `id`, `name`, `code`, `description`, `is_active`, `effective_from`, `effective_until`, timestamps.

### 21.2 `contractor_calendars`

Konfigurasi kalender kerja kontraktor.

Field konseptual: `id`, `contractor_id`, `name`, `working_days_json` atau struktur normalized, `shift_configuration` (opsional), `effective_from`, `effective_until`, `is_active`.

Jangan menganggap kalender sama untuk seluruh kontraktor.

### 21.3 `contractor_resources` / tabel master spesifik

Data operasional ringan jika diperlukan: personel, supervisor, area, kendaraan, peralatan, pos. Gunakan tabel spesifik bila atribut tiap entitas cukup berbeda. Semua mendukung active/effective dates dan histori.

### 21.4 `hsse_kpis`

Master definisi KPI dan konfigurasi per kontraktor. Relasi ke `contractors`.

### 21.5 `hsse_kpi_periods`

Satu record untuk satu KPI pada satu periode evaluasi. Menyimpan:

- `hsse_kpi_id`
- `period_type`
- `period_start`, `period_end`
- `normal_target`
- `opening_debt`
- `opening_credit`
- `effective_target`
- `actual_total`
- `actual_percentage`
- `achievement_percentage`
- `closing_debt`
- `closing_credit`
- `status`
- snapshot target/resource/calendar
- `locked_at` (jika periode dikunci)

Nama/struktur final dapat disesuaikan dengan pola project.

### 21.6 `hsse_kpi_entries`

Setiap catatan realisasi/checklist/per kegiatan.

Field konseptual:

- `hsse_kpi_id`
- `hsse_kpi_period_id` (nullable jika entry event-based belum dipetakan ke periode)
- `entry_date`
- `entry_type`
- `quantity` / `numerator` / `denominator` / `percentage` / `status_value`
- `unit`
- `description` / `note`
- `created_by`, `updated_by`
- `source_reference` (opsional untuk mencegah duplikasi)
- timestamps

### 21.7 `hsse_kpi_attachments`

Lampiran bukti untuk entry/KPI. Simpan nama asli, path, mime type, ukuran, uploader, timestamps. Gunakan Laravel Storage dan validasi jenis/ukuran.

### 21.8 `hsse_kpi_notes`

Catatan evaluasi/follow-up dari HSE Owner.

### 21.9 `hsse_kpi_balance_ledger`

Ledger hutang/kredit dan alokasi FIFO.

Field konseptual: `hsse_kpi_id`, `contractor_id`, `source_period_id`, `used_in_period_id`, `transaction_type`, `quantity`, `remaining_quantity`, `expires_at`, `status`, `source_entry_id`, timestamps.

### 21.10 `hsse_kpi_resource_snapshots`

Snapshot jumlah personel/area/kendaraan/peralatan yang dipakai untuk membentuk target periode. Bisa juga diwujudkan melalui kolom JSON snapshot di `hsse_kpi_periods` jika struktur dan auditability memadai. Pilih desain paling konsisten dengan project, jangan membuat kompleksitas yang tidak dibutuhkan.

### 21.11 Audit log

Gunakan `activity_logs` existing jika tersedia. Jika belum, ikuti pola audit log project. Catat Create/Update/Deactivate, input realisasi, perubahan target, import, alokasi hutang/kredit, penguncian periode, dan perubahan lampiran.

---

# BAGIAN IX — STATUS, VALIDASI, DAN HISTORI

## 22. Status KPI

Status umum yang dapat digunakan:

- Belum Mulai: belum ada realisasi dan periode belum berjalan/aktivitas belum dimulai.
- Belum Diinput: periode berjalan tetapi belum ada input yang diperlukan.
- Berlangsung: realisasi sudah ada tetapi target efektif belum terpenuhi.
- Tercapai: target efektif terpenuhi.
- Terlambat/Belum Tercapai: periode berakhir dan target belum terpenuhi.
- Tidak Berlaku: KPI tidak berlaku untuk periode/aktivitas tersebut berdasarkan konfigurasi yang sah.

Jangan menandai KPI gagal sebelum periode berjalan berakhir jika target memang harus dipenuhi selama periode tersebut. Untuk KPI due-date/event-based, status mengikuti jadwal atau pemicu.

## 23. Validasi

- Entry realisasi tidak boleh memiliki tanggal di luar periode yang diizinkan tanpa izin khusus.
- Kuantitas tidak boleh negatif kecuali metode KPI secara eksplisit mengizinkan penyesuaian.
- Persentase harus berada pada rentang yang valid, kecuali persentase aktual kuantitatif yang dapat >100% berdasarkan rumus.
- Lampiran divalidasi format dan ukuran.
- Cegah double submit.
- Cegah input ganda untuk event yang sama melalui source reference/idempotency bila tersedia.
- Satu incident tidak boleh dihitung berkali-kali dalam KPI turunan yang berbagi sumber data.
- Periode yang sudah dikunci tidak boleh diubah tanpa hak khusus dan audit trail.

## 24. Histori dan periode terkunci

- Hasil historis Model A tidak berubah saat hutang dibayar pada periode selanjutnya.
- Perubahan master data tidak mengubah snapshot target historis.
- Koreksi periode lampau harus melalui fungsi koreksi yang memiliki alasan dan audit trail, bukan mengedit data secara diam-diam.
- Perubahan definisi KPI berlaku sesuai effective date dan tidak otomatis mengubah evaluasi historis.

---

# BAGIAN X — HAK AKSES DAN PELAPORAN

## 25. Hak akses

Ikuti authentication, role, middleware, dan permission existing pada HSSE Project PMU.

Kebutuhan minimum:

- User yang memiliki akses baca: melihat ringkasan, halaman kontraktor, detail, histori, dan laporan sesuai permission.
- Admin/role pengelola yang berwenang: menambah/mengedit/menonaktifkan kontraktor, master KPI, kalender, dan master data.
- HSE Owner yang berwenang: input/edit realisasi, upload bukti, dan catatan.
- Viewer: hanya baca jika sesuai role existing.

Jangan mengarang nama role atau mengganti sistem permission existing. Periksa role project saat implementasi.

## 26. Export dan laporan

Sediakan export PDF sesuai filter aktif:

- Ringkasan seluruh kontraktor.
- Laporan per kontraktor.
- Periode bulanan/kuartalan/tahunan/proyek.
- Daftar KPI, target, realisasi, capaian, actual percentage, status.
- Hutang/kredit awal dan akhir bila berlaku.
- Catatan dan status bukti bila diperlukan.
- Perspektif dan bobot TDP jika formula scorecard telah diverifikasi.

Format PDF mengikuti library/template yang sudah tersedia pada project (misalnya DomPDF jika sudah terpasang). Jangan menambah dependency tanpa kebutuhan.

---

# BAGIAN XI — UI/UX YANG DIHARAPKAN

## 27. Desain umum

Gunakan desain modern, rapi, dan konsisten dengan dashboard HSSE Project PMU existing.

Prioritas:

- Informasi mudah dipahami HSE Owner.
- Tidak terlalu padat.
- Tabel dapat difilter dan dicari.
- Detail KPI dibuka melalui modal/panel agar tabel tetap ringkas.
- Warna status konsisten (tercapai/hijau, progres/kuning atau biru, belum tercapai/merah, nonaktif/netral).
- Responsif desktop/tablet/mobile.
- Jangan menambah grafik hanya sebagai dekorasi. Grafik harus memberi nilai monitoring.

## 28. Form Master KPI

Field dasar:

- Kontraktor.
- Kode dan nama KPI.
- Kelompok/perspektif.
- Kategori Leading/Lagging atau klasifikasi yang sesuai.
- Target dan satuan.
- Operator target (zero, maksimum, minimum, 100%, index, dll.).
- Frekuensi target.
- Periode evaluasi.
- Metode input.
- Metode perhitungan.
- Konfigurasi sumber target dinamis jika diperlukan.
- Carry forward aktif/nonaktif.
- Credit carry forward aktif/nonaktif.
- Masa berlaku kredit.
- Kebutuhan bukti.
- Effective date dan status aktif.

Gunakan conditional fields sehingga form tidak menampilkan semua konfigurasi kepada setiap KPI.

## 29. Form Input Realisasi

Field ditentukan oleh metode input KPI:

- KPI yang checklist: status terlaksana/belum.
- KPI quantity: angka realisasi.
- KPI percentage/compliance: numerator dan denominator atau angka persentase.
- KPI index: skor hasil survei dan skala.
- KPI event-based: tanggal kejadian/kegiatan, status, dan bukti.

Sediakan:
- Tanggal/periode.
- Catatan HSE Owner.
- Upload satu atau banyak lampiran jika sesuai.
- Validasi.
- Riwayat entry.

## 30. Detail KPI

Detail KPI menampilkan:

- Definisi dan target KPI.
- Frekuensi dan periode.
- Target normal, hutang/kredit awal, target efektif.
- Total realisasi.
- Capaian KPI dan persentase aktual.
- Saldo hutang/kredit.
- Riwayat realisasi.
- Lampiran.
- Catatan evaluasi.
- Alokasi FIFO dan masa berlaku kredit jika aktif.

---

# BAGIAN XII — TESTING DAN ACCEPTANCE CRITERIA

## 31. Pengujian umum

1. Kontraktor baru dapat ditambahkan dan otomatis muncul sebagai halaman monitoring dinamis.
2. Kontraktor nonaktif tidak dapat dipilih untuk KPI baru, tetapi seluruh data historis tetap dapat dibuka.
3. Satu KPI dapat memiliki frekuensi target berbeda dari frekuensi input.
4. HSE Owner dapat menginput checklist, quantity, percentage/compliance, atau index sesuai konfigurasi.
5. Lampiran dan catatan tersimpan serta dapat dibuka kembali.
6. Target dinamis menggunakan effective date dan snapshot.
7. Perubahan master data tidak mengubah hasil historis.
8. Filter ringkasan/kontraktor/periode berfungsi.
9. Export menghormati filter dan permission.
10. Role/permission existing tetap bekerja.
11. Tidak ada duplikasi kejadian/KPI saat menggunakan sumber data yang sama.

## 32. Pengujian Carry Forward dan Credit

Target normal 4 per bulan, carry dan credit aktif:

**Skenario hutang:** Januari target 4, realisasi 3 → hutang 1. Februari target efektif 5, realisasi 5 → target efektif terpenuhi, hutang 0. Januari tetap 75%.

**Skenario kredit:** Januari target 4, realisasi 5 → capaian KPI 100%, persentase aktual 125%, kredit 1. Februari target normal 4, kredit awal 1 → target efektif 3. Realisasi 3 → target terpenuhi dan kredit habis.

**Skenario saldo campuran:** Januari hutang 1; Februari menerima hutang 1, target normal 4, realisasi 6 → target efektif 5, capaian efektif 100%, actual percentage 150% terhadap target normal, kredit akhir 1.

**Skenario kedaluwarsa:** kredit yang masa berlakunya berakhir tidak boleh digunakan setelah tanggal kedaluwarsa; catat sebagai expired dan tampilkan pada histori.

**Skenario FIFO:** jika terdapat hutang dari lebih dari satu periode, realisasi baru dialokasikan ke kewajiban tertua terlebih dahulu.

## 33. Pengujian kalender dan snapshot

- PJSE: Senin–Jumat hari kerja normal; Sabtu/Minggu hanya dihitung bila ada pekerjaan/lembur tercatat dan KPI berlaku.
- TDP: Senin–Minggu; tiga shift; semua pos terisi; kebutuhan 1+ personel dapat berbeda per pos/shift.
- JOTRE: Senin–Sabtu efektif; Minggu sesuai penugasan aktual.
- Personel/aset berubah di tengah periode: target menyesuaikan sejak effective date berdasarkan hari kerja relevan.
- Hasil periode terdahulu tidak berubah setelah perubahan master data.

---

# BAGIAN XIII — KEPUTUSAN YANG MASIH PERLU DIKONFIRMASI

Antigravity tidak boleh mengarang jawaban untuk hal-hal berikut. Gunakan konfigurasi sementara atau tandai `Needs Configuration` sampai user menetapkan kebijakan.

## 34. PJSE

1. **Near Miss target 2:** apakah berarti minimal 2 laporan, maksimal 2 kejadian, atau target lainnya?
2. **First Aid Case maksimal 2:** user menyatakan lebih dari 2 menyebabkan skor 0%. Belum dipastikan apakah 0, 1, dan 2 masing-masing semuanya mendapat 100%, atau ada skala bertingkat.
3. **Man Hours:** sumber angka jam kerja dan apakah lembur dihitung perlu ditetapkan.
4. **Laporan K3LL:** tanggal jatuh tempo dan kriteria tepat waktu perlu ditetapkan jika ingin menilai ketepatan pelaporan.

## 35. TDP

1. Formula resmi penggabungan skor KPI dalam perspektif dan bobot perspektif perlu dikonfirmasi. Dokumen menunjukkan bobot 40/20/20/20 dan skor rata-rata/akhir, tetapi contoh angka Service Excellence belum dapat direkonsiliasi sepenuhnya dari tampilan.
2. Apakah setiap KPI dalam perspektif memiliki bobot sama atau ada bobot individual?
3. Penjagaan Area target `<12`: perlu konfirmasi apakah skor dihitung sebagai 100% selama temuan 0–11 dan 0% mulai 12, atau menggunakan skala lain.
4. Pelaporan & Penanganan Kondisi Darurat: denominator 100% perlu diperjelas (jumlah kejadian yang wajib dilaporkan/ditangani).
5. PIJAR 50 laporan/tahun: target periode Q1 apakah dibagi proporsional atau mengikuti rencana distribusi tahunan.
6. Kepuasan Pengguna: metode survei, skala, dan konversi ke index 85 perlu dikonfirmasi.
7. Kehadiran BUJP Representative: denominator kehadiran (hari kerja, jadwal, atau rapat tertentu) perlu ditetapkan.
8. Pelaksanaan Patroli: jumlah rute/jadwal yang menjadi denominator harus tersedia dari sistem atau input HSE Owner.
9. Coverage per pos/shift: kebutuhan personel per pos dan shift dikonfigurasi; tidak boleh diasumsikan sama.

## 36. JOTRE

1. LTI/MTC/indikator medis yang muncul di lebih dari satu bagian harus dipetakan ke sumber data yang sama atau didefinisikan sebagai indikator terpisah; hindari duplikasi.
2. Near Miss `All Record & Report`: perlu definisi sumber denominator/pembuktian seluruh near miss yang diketahui sudah tercatat.
3. Safe Man Hours dan Safe Man Days: sumber data dan metode rekap harus ditetapkan.
4. TRIR, LTIR, dan FATR membutuhkan denominator/formula rate yang resmi (misalnya jam kerja dan faktor pengali) sebelum kalkulasi diaktifkan. Jangan menebak faktor pengali.
5. Random Drug & Alcohol Test: “weekly random testing program” belum menentukan jumlah sampel/denominator.
6. T-SAFE Card: target 2 kartu/minggu berlaku untuk supervisor level ke atas; jumlah supervisor relevan harus tersedia dengan snapshot per periode.
7. Near Miss Report: target 2 laporan/bulan/work area; daftar work area aktif menjadi dasar target dan harus memiliki snapshot.
8. Pre-Shift Supervisor Meeting: jumlah supervisor/meeting yang diwajibkan perlu ditetapkan jika ingin kuantifikasi.
9. Event-based KPI: definisi peristiwa pemicu dan bukti penyelesaian perlu dikonfigurasi.
10. Detail frekuensi dan periode kontrak/proyek perlu dipastikan untuk KPI Per Project.

## 37. Kebijakan umum lintas kontraktor

1. Carry forward dan credit forward diatur per KPI. Default nonaktif sampai disetujui.
2. KPI time-critical, zero event, pre-use, pre-mobilization, pre-shift, atau kewajiban keselamatan yang harus dipenuhi sebelum aktivitas tidak boleh otomatis membawa hutang/kredit.
3. Masa berlaku kredit ditetapkan per KPI.
4. Definisi periode evaluasi harus ditetapkan untuk setiap KPI.
5. Target prorata menggunakan hari kerja relevan, tetapi KPI berbasis event/mobilisasi/per-use mengikuti trigger masing-masing.
6. Kebijakan pembulatan target prorata pecahan (mis. 1,2 laporan) perlu ditentukan per KPI. Jangan otomatis membulatkan ke bawah; untuk kewajiban minimum yang berupa jumlah utuh biasanya perlu aturan pembulatan ke atas, tetapi hal ini harus disetujui.
7. Batas tanggal penguncian periode dan prosedur koreksi data historis perlu ditetapkan.
8. Jika KPI tidak berlaku untuk periode tertentu, gunakan `N/A/Tidak Berlaku` dengan alasan; jangan mengubahnya menjadi 0% atau 100% secara otomatis.

---

# BAGIAN XIV — RENCANA IMPLEMENTASI ANTIGRAVITY

## 38. Tahapan implementasi yang disarankan

Antigravity diminta mengerjakan modul ini langsung, tetapi dengan urutan teknis yang terkontrol.

### Tahap 1 — Audit project

- Periksa branch dan working tree.
- Temukan menu HSSE Plan existing.
- Periksa route, controller, model, migration, view, permission, database, library, dan tabel audit log.
- Identifikasi data existing dan potensi migrasi.
- Jangan menghapus kode/data sebelum memahami dampaknya.

### Tahap 2 — Normalisasi master data

- Master kontraktor.
- Master KPI per kontraktor.
- Frekuensi, periode, metode input, metode hitung, bobot, carry/credit.
- Kalender kontraktor.
- Master data operasional ringan hanya jika diperlukan.

### Tahap 3 — Periode dan realisasi

- Membuat record periode KPI.
- Menyimpan target normal dan snapshot.
- Membuat input realisasi.
- Upload bukti dan catatan.
- Validasi dan audit trail.

### Tahap 4 — Calculation Engine

- Menerapkan metode kalkulasi sesuai konfigurasi KPI.
- Menghitung status dan persentase.
- Menerapkan carry/credit ledger, FIFO, expiry, dan histori Model A.
- Jangan mengaktifkan rumus KPI yang masih berstatus Needs Configuration.

### Tahap 5 — UI Monitoring

- Sidebar KPI Monitoring.
- Dashboard Ringkasan.
- Halaman monitoring kontraktor dinamis.
- Kelola kontraktor dan master KPI.
- Detail KPI, histori, lampiran, catatan.

### Tahap 6 — Import dan reporting

- Template Excel.
- Preview dan validasi import.
- Export PDF.

### Tahap 7 — Testing

- Uji semua acceptance criteria.
- Uji setiap jenis metode perhitungan.
- Uji sample carry/credit.
- Uji histori dan nonaktif kontraktor.
- Uji permission.
- Uji tidak ada regresi pada modul lain.

## 39. Instruksi akhir untuk Antigravity

Implementasikan modul **KPI Monitoring** berdasarkan dokumen ini dan struktur project Laravel 12 existing.

Prioritaskan sistem yang sederhana untuk HSE Owner:

- Pilih kontraktor.
- Lihat KPI.
- Input status atau realisasi.
- Tambahkan bukti/catatan.
- Lihat hasil kalkulasi dan histori.

Jangan membangun sistem operasional kontraktor yang terlalu kompleks. Master personel/aset/area hanya secukupnya untuk target KPI yang membutuhkannya.

Gunakan satu engine generik untuk semua kontraktor. Konfigurasi setiap KPI menentukan tampilan form, metode perhitungan, periode, dan aturan carry/credit.

Pertahankan fitur/modul existing yang tidak terkait. Jangan menjalankan operasi database destruktif. Jika menemukan konflik kebijakan atau data yang belum jelas pada bagian 34–37, jangan membuat asumsi bisnis. Tandai sebagai `Needs Configuration`, jelaskan dampaknya, dan lanjutkan bagian lain yang sudah jelas.

Setelah implementasi, laporkan:

1. Struktur file baru dan file yang diubah.
2. Rancangan tabel/migration.
3. KPI dan konfigurasi yang sudah dimasukkan.
4. Calculation method yang sudah aktif dan yang masih Needs Configuration.
5. Hasil testing.
6. Risiko dan pekerjaan lanjutan.

**Jangan hanya menghasilkan analisis atau contoh kode. Implementasikan pada project, tetapi lakukan perubahan secara aman dan dapat diaudit.**

---

# RIWAYAT KEPUTUSAN BISNIS

Bagian ini merekam keputusan user yang telah disepakati selama diskusi:

1. Modul bernama KPI Monitoring dan digunakan HSE Owner untuk monitoring internal.
2. Kontraktor awal: PJSE, TDP/Security, JOTRE; penambahan kontraktor harus dinamis.
3. Menu memiliki Ringkasan dan halaman monitoring per kontraktor.
4. Kontraktor dapat dinonaktifkan tanpa kehilangan histori.
5. Realisasi diinput oleh HSE Owner, dapat harian atau per kegiatan.
6. Target ditetapkan pada awal tahun atau periode proyek; frekuensi target berbeda-beda per KPI.
7. Input frekuensi dan target frequency adalah konsep terpisah.
8. Hutang KPI dapat dibawa ke periode berikutnya untuk KPI yang mengizinkan.
9. Surplus dapat menjadi kredit untuk KPI yang mengizinkan.
10. Histori periode tetap (Model A); pembayaran hutang tidak mengubah nilai periode lampau.
11. FIFO untuk alokasi kewajiban tertua.
12. Masa berlaku kredit dikonfigurasi per KPI.
13. Capaian KPI maksimum 100%, persentase aktual boleh di atas 100%, kredit ditampilkan terpisah (Opsi C).
14. Master data personel/area/kendaraan/peralatan dapat dikelola melalui aplikasi maupun import Excel.
15. Perubahan jumlah personel/aset mengikuti effective date.
16. Target dinamis di-prorata berdasarkan hari kerja yang relevan.
17. PJSE: Senin–Jumat kerja normal; Sabtu/Minggu lembur dan hanya dihitung saat ada pekerjaan yang relevan.
18. TDP: Senin–Minggu, 3 shift (pagi/sore/malam); semua pos wajib terisi setiap shift; kebutuhan personel per pos dapat satu atau lebih.
19. JOTRE: Senin–Sabtu efektif; Minggu hanya bagi personel/aktivitas yang dijadwalkan bekerja.
20. JOTRE Biannually berarti dua kali setahun; Biweekly berarti satu kali setiap dua minggu.
21. Scorecard empat perspektif pada halaman 1–2 PDF yang diunggah bersama JOTRE adalah milik TDP, bukan JOTRE.
22. Untuk TDP, perspektif dan bobot resmi pada dokumen: Goal Oriented 40%, Operasional 20%, Kompetensi & SDM 20%, Service Excellence 20%.
23. Daftar lokasi PJSE (Pad 31, Pad 9, Pad 7, Pad 29, Pad 38, Pad 18, TO) adalah scope lokasi inspeksi, bukan target inspeksi per lokasi.
24. PJSE First Aid Case target maksimal dua; lebih dari dua menghasilkan capaian 0%. Skor 0–2 belum ditetapkan.

# SUMBER DOKUMEN

- `hsse-plan-blueprint.md`: blueprint awal HSSE Plan yang menjadi dasar pembahasan dan direstrukturisasi oleh dokumen ini.
- `PJSE Indikator.pdf`: daftar 9 Lagging dan 16 Leading PJSE.
- Gambar KPI TDP: scorecard Q1 2026 dengan empat perspektif dan bobot.
- `JOTRE KPI.pdf`: halaman 1–2 milik TDP; halaman 3–10 merupakan daftar Lagging dan Leading JOTRE.

> Dokumen ini menggabungkan informasi sumber dan keputusan diskusi. Bagian yang diberi label “perlu konfirmasi” tidak boleh dianggap sebagai kebijakan perusahaan yang sudah final.
