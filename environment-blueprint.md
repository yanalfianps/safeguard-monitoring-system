# MASTER PROMPT & SPESIFIKASI TEKNIS: MODUL ENVIRONMENT (HSSE)

```text
================================================================================
MASTER PROMPT: GENERATE MODUL ENVIRONMENT LENGKAP (LARAVEL 11 + BLADE + MYSQL)
================================================================================

Role: Senior Full-Stack Laravel Architect & Environmental Compliance Engineer.
Task: Bangun ulang seluruh modul "Environment" (Pengelolaan & Pemantauan Lingkungan HSSE)
      beserta 6 submenunya secara komprehensif, terstruktur, transaksional, dan terintegrasi
      sesuai spesifikasi arsitektur teknis di bawah ini.
Framework: Laravel 11/12, Blade Templates, Bootstrap 5 / Tailwind CSS, Chart.js, 
           DomPDF (barryvdh/laravel-dompdf), MySQL.
```

---

## 1. DESKRIPSI UMUM & STRUKTUR NAVIGASI MODUL

Modul **Environment** berfungsi untuk mencatat, mengawasi, mendokumentasikan, dan melaporkan seluruh aspek penaatan lingkungan operasional perusahaan di wilayah kerja HSSE / Geothermal Field.

### Struktur Submenu Lingkungan:
1. **Ringkasan Lingkungan (Overview / Dashboard Lingkungan)**
   - Akses via `/environment`
   - Berisi kartu metrik seluruh indikator limbah, pemantauan, energi, dan grafik tren bulanan energi BBM vs Listrik.
   - Menyediakan tombol aksi cepat **Kelola Master Data** (Master data dipusatkan di sini, tidak menjadi submenu mandiri di sidebar).
2. **1. LB3 (Limbah Bahan Berbahaya dan Beracun)**
3. **2. Non-LB3 (Limbah Domestik & Padat Non-B3)**
4. **3. RKL–RPL (Rencana Pengelolaan Lingkungan & Rencana Pemantauan Lingkungan)**
5. **4. PPKH (Persetujuan Penggunaan Kawasan Hutan)** *(Sebelumnya PPICH)*
6. **5. Energi (Pemantauan BBM & Listrik)**
7. **6. EWS (Early Warning System / Sistem Peringatan Dini Geoteknik & Lereng)**

---

## 2. SKEMA BASIS DATA & MIGRATIONS

### A. Tabel `environment_masters` (Pusat Master Data Terintegrasi)
Satu tabel fleksibel untuk menampung seluruh master data kategori lingkungan:
```sql
CREATE TABLE `environment_masters` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(50) NOT NULL, 
  -- ('unit', 'lb3_characteristic', 'rkl_rpl_parameter', 'rkl_rpl_location', 'ews_location')
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `submodules` JSON NULL, -- Pemetaan satuan ke submenu, e.g. ["lb3", "non_lb3"]
  `order_index` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_env_master_cat_active` (`category`, `is_active`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### B. Tabel `environment_lb3_records` (Catatan Limbah B3 Masuk TPS)
```sql
CREATE TABLE `environment_lb3_records` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `waste_name` VARCHAR(255) NOT NULL, -- e.g. "Oli Bekas", "Filter Bekas", "Aki Bekas"
  `amount` DECIMAL(12,3) NOT NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `unit_name` VARCHAR(50) NOT NULL,
  `entry_date` DATE NOT NULL,
  `characteristics` JSON NULL, -- Checkbox karakteristik: e.g. ["Mudah Menyala", "Beracun"]
  `description` TEXT NULL,
  `attachment_paths` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_lb3_date` (`entry_date`),
  FOREIGN KEY (`unit_id`) REFERENCES `environment_masters`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### C. Tabel `environment_non_lb3_records` (Catatan Limbah Non-B3 / Domestik)
```sql
CREATE TABLE `environment_non_lb3_records` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `waste_name` VARCHAR(255) NOT NULL, -- e.g. "Sampah Organik Sisa Makanan", "Plastik Anorganik"
  `source` VARCHAR(255) NOT NULL, -- Free text / input fleksibel sumber limbah (e.g. "Dapur Mess", "Kantor Utama")
  `source_id` BIGINT UNSIGNED NULL,
  `amount` DECIMAL(12,3) NOT NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `unit_name` VARCHAR(50) NOT NULL,
  `entry_date` DATE NOT NULL,
  `transport_date` DATE NULL, -- Tanggal pengangkutan ke TPA (harus >= entry_date)
  `description` TEXT NULL,
  `attachment_paths` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_non_lb3_date` (`entry_date`),
  FOREIGN KEY (`unit_id`) REFERENCES `environment_masters`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### D. Tabel `environment_rkl_rpl_records` (Hasil Pengukuran Laboratorium RKL-RPL)
```sql
CREATE TABLE `environment_rkl_rpl_records` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `parameter_id` BIGINT UNSIGNED NULL,
  `parameter_name` VARCHAR(150) NOT NULL, -- e.g. "Kualitas Udara Ambien (H2S)", "Kebisingan", "pH Air Permukaan"
  `location_id` BIGINT UNSIGNED NULL,
  `location_name` VARCHAR(150) NOT NULL, -- e.g. "Wellpad A", "Power Plant", "Desa Penyangga"
  `sampling_date` DATE NOT NULL,
  `measurement_value` VARCHAR(255) NOT NULL, -- Nilai hasil uji (dapat berupa angka atau teks lab e.g. "< 0.01")
  `unit_id` BIGINT UNSIGNED NULL,
  `unit_name` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `attachment_paths` JSON NULL, -- Hasil uji lab sertifikat analisis (CoA)
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_rkl_rpl_sampling` (`sampling_date`),
  FOREIGN KEY (`parameter_id`) REFERENCES `environment_masters`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`location_id`) REFERENCES `environment_masters`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`unit_id`) REFERENCES `environment_masters`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### E. Tabel `environment_ppich_records` (Catatan Monitoring PPKH / SK Hutan)
```sql
CREATE TABLE `environment_ppich_records` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NULL,
  `location_name` VARCHAR(150) NOT NULL, -- Berisi nilai acuan SK: "SK 2013" atau "SK 2024"
  `reference_id` BIGINT UNSIGNED NULL,
  `reference_name` VARCHAR(150) NOT NULL, -- "SK 2013" atau "SK 2024"
  `sampling_date` DATE NOT NULL,
  `activity_id` BIGINT UNSIGNED NULL,
  `activity_name` TEXT NOT NULL, -- Uraian aktivitas / program pemantauan kawasan hutan
  `description` TEXT NULL,
  `attachment_paths` JSON NULL, -- Foto patok batas, berita acara survei kehutanan
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_ppkh_sampling` (`sampling_date`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### F. Tabel `environment_energy_records` (Konsumsi Bulanan BBM & Listrik)
```sql
CREATE TABLE `environment_energy_records` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `energy_type` ENUM('bbm', 'listrik') NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL, -- 1 s.d 12
  `year` SMALLINT UNSIGNED NOT NULL, -- e.g. 2026
  `usage_amount` DECIMAL(14,2) NOT NULL,
  `unit` VARCHAR(20) NOT NULL, -- Otomatis 'Liter' untuk BBM, 'kWh' untuk Listrik
  `description` TEXT NULL,
  `attachment_paths` JSON NULL, -- Bukti invoice listrik PLN atau logbook pengisian solar
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `uk_energy_type_month_year` (`energy_type`, `month`, `year`), -- Invariant anti-duplikasi
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### G. Tabel `environment_ews_records` (Pencatatan Kondisi Early Warning System)
```sql
CREATE TABLE `environment_ews_records` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NULL,
  `location_name` VARCHAR(150) NOT NULL, -- Lokasi sensor EWS (dari master ews_location)
  `recording_date` DATE NOT NULL,
  `condition` VARCHAR(100) NOT NULL, -- e.g. "Normal / Aman", "Waspada", "Siaga", "Perlu Kalibrasi"
  `description` TEXT NULL,
  `attachment_paths` JSON NULL, -- Log pembacaan sensor atau dokumentasi visual alat
  `recorded_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_ews_date` (`recording_date`),
  FOREIGN KEY (`location_id`) REFERENCES `environment_masters`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

---

## 3. SISTEM MASTER DATA & PEMETAAN SATUAN PER SUBMENU

### A. Kategori Master Data Aktif:
1. `unit` (Satuan Pengukuran)
2. `lb3_characteristic` (Karakteristik Bahaya LB3: *Mudah Menyala, Beracun, Korosif, Reaktif, Infeksius, Mudah Meledak*)
3. `rkl_rpl_parameter` (Parameter Pemantauan Lingkungan)
4. `rkl_rpl_location` (Titik Lokasi Pemantauan)
5. `ews_location` (Titik Sensor / Alat EWS)

### B. Aturan Pemetaan Satuan (`submodules` JSON mapping):
Dropdown satuan pada form input **hanya menampilkan satuan yang relevan** dengan submenu yang sedang dibuka:

| Submenu | Kode Submodul | Pilihan Satuan yang Ditampilkan |
|---|---|---|
| **LB3** | `lb3` | Ton, Kilogram (kg), Liter, Drum, Sak/Karung |
| **Non-LB3** | `non_lb3` | Ton, Kilogram (kg), Liter, Sak/Karung, Lembar/Pcs, Unit |
| **RKL–RPL** | `rkl_rpl` | mg/L, mg/Nm³, dBA, ppm, °C, %, pH |
| **Energi BBM** | `energi_bbm` | Liter |
| **Energi Listrik** | `energi_listrik`| kWh |

*Catatan:* Pada form kelola Master Data Satuan, tersedia checklist multi-pilihan submodul yang dapat dipilih admin untuk menentukan di mana saja satuan tersebut berhak muncul.

---

## 4. STRUKTUR FORM INPUT, VALIDASI, & LOGIKA BISNIS SETIAP SUBMENU

### Submenu 1: LB3 (Limbah B3)
- **Field:** Nama Limbah B3 (*req*), Jumlah/Volume (*req, numeric min 0.001*), Satuan (*req, dropdown satuan khusus LB3*), Tanggal Masuk TPS (*req*), Karakteristik Limbah (*checkbox multi-select*), Keterangan (*opsional*), Dokumen Manifest/Foto Limbah (*multi-file*).
- **Rekapitulasi:** Total frekuensi limbah masuk, total akumulasi tonase, tabel daftar limbah, filter kata kunci pencarian, filter satuan, dan filter karakteristik.

### Submenu 2: Non-LB3 (Limbah Domestik)
- **Field:** Nama Limbah (*req*), Sumber Limbah (*req, free text input langsung e.g. "Kantin Area 2"*), Jumlah/Volume (*req, numeric min 0.001*), Satuan (*req, dropdown khusus Non-LB3*), Tanggal Masuk (*req*), Tanggal Pengangkutan ke TPA (*opsional, validasi rule: `after_or_equal:entry_date`*), Keterangan, Lampiran Berita Acara.
- **Rekapitulasi:** Total sampah tercatat, total berat, filter teks, dan filter satuan.

### Submenu 3: RKL–RPL (Hasil Pemantauan Lingkungan)
- **Field:** Parameter Lingkungan (*req, select master parameter*), Lokasi Pemantauan (*req, select master lokasi*), Tanggal Sampling (*req*), Hasil Pengukuran (*req, string mendukung angka & batas deteksi e.g. `< 0.05`*), Satuan (*dropdown khusus RKL-RPL*), Keterangan Uji, Lampiran Hasil Uji Lab (Certificate of Analysis / CoA).
- **Rekapitulasi:** Jumlah sampling selesai, ragam parameter yang dipantau, filter parameter, filter lokasi.

### Submenu 4: PPKH (Persetujuan Penggunaan Kawasan Hutan)
- **Field:**
  - **Monitoring Lokasi (Acuan SK):** Dropdown pilihan wajib: **`SK 2013`** atau **`SK 2024`** *(Rule: `in:SK 2013,SK 2024`)*.
  - **Aktivitas Pemantauan:** Textarea deskripsi kegiatan (*req, e.g. "Pemeriksaan patok batas koordinat 14 s.d 20"*).
  - **Lampiran Dokumen:** Multi-file foto lapangan / berita acara.
- **Logika:** Kolom acuan SK disimpan ke `reference_name` dan `location_name` secara sinkron.
- **Rekapitulasi:** Total kegiatan PPKH, filter berdasarkan SK 2013 vs SK 2024, tabel tabular histori kegiatan.

### Submenu 5: Energi (BBM & Listrik)
- **Struktur Dual Tab:**
  - Tab 1: **BBM** (Satuan otomatis terkunci: **`Liter`**)
  - Tab 2: **Listrik** (Satuan otomatis terkunci: **`kWh`**)
- **Field Form:** Jenis Energi (hidden), Bulan (*req, integer 1–12*), Tahun (*req, integer min 2000*), Jumlah Konsumsi (*req, numeric min 0.01*), Keterangan, Bukti Tagihan / Invoice.
- **Aturan Unik Anti-Duplikasi:** Sistem memvalidasi kombinasi `(energy_type, month, year)`. Jika bulan dan tahun yang sama sudah pernah diisi, sistem membatalkan simpan dan meminta pengguna mengedit entri yang sudah ada.
- **Statistik & Metrik Tahunan:**
  - Total Konsumsi Tahunan
  - Rata-rata Pemakaian per Bulan
  - Bulan Tertinggi (*Peak Consumption Month*)

### Submenu 6: EWS (Early Warning System)
- **Field:** Lokasi Sensor EWS (*req, select master ews_location*), Tanggal Pencatatan (*req*), Kondisi (*req, string*), Keterangan / Tindak Lanjut, Lampiran Foto / Log Data.
- **Rekapitulasi:** Total pencatatan EWS, distribusi kondisi per titik lokasi sensor, filter pencarian teks, dan filter titik lokasi.

---

## 5. MEKANISME MULTI-FILE UPLOAD COMPACT (STANDAR MODUL HSSE)

Seluruh submenu Environment menggunakan arsitektur upload terpadu:
1. **Dropzone Kompak:** Tinggi kotak upload 80–105px dengan styling netral.
2. **Tombol "+ Tambah Dokumen":** Mengizinkan pengguna memilih file dokumen dari direktori folder yang berbeda secara akumulatif tanpa menimpa file yang sudah terpilih.
3. **Format & Ukuran:** Dokumen PDF, JPG, JPEG, PNG (Maksimal 5MB per file).
4. **Penyimpanan:** Disimpan ke direktori disk publik per subfolder:
   - `storage/app/public/environment/lb3/`
   - `storage/app/public/environment/non_lb3/`
   - `storage/app/public/environment/rkl_rpl/`
   - `storage/app/public/environment/ppkh/`
   - `storage/app/public/environment/energy/`
   - `storage/app/public/environment/ews/`
5. **Database Storage:** Disimpan sebagai array JSON murni di kolom `attachment_paths`. Model menyediakan accessor `$model->attachment_list`. Form Edit mendukung array `deleted_attachments[]` untuk menghapus dokumen lama secara aman dari storage fisik.

---

## 6. FITUR EKSPOR LAPORAN PDF (LANDSCAPE A4)

Seluruh submenu Environment dilengkapi fitur **Ekspor PDF** berorientasi Landscape A4 dengan branding resmi HSSE (Logo Perusahaan, Judul Modul, Identitas Petugas Pengunduh, dan Waktu Unduh):

1. **Ringkasan Lingkungan:** `overviewExportPdf` -> [environment-overview-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-overview-pdf.blade.php) (Menyajikan seluruh kartu metrik LB3, Non-LB3, RKL-RPL, PPKH, EWS, dan tabel konsumsi energi BBM vs Listrik 12 bulan).
2. **LB3:** `lb3ExportPdf` -> [environment-lb3-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-lb3-pdf.blade.php)
3. **Non-LB3:** `nonLb3ExportPdf` -> [environment-non-lb3-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-non-lb3-pdf.blade.php)
4. **RKL–RPL:** `rklRplExportPdf` -> [environment-rkl-rpl-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-rkl-rpl-pdf.blade.php)
5. **PPKH:** `ppkhExportPdf` -> [environment-ppkh-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-ppkh-pdf.blade.php)
6. **Energi:** `energyExportPdf` -> [environment-energy-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-energy-pdf.blade.php)
7. **EWS:** `ewsExportPdf` -> [environment-ews-pdf.blade.php](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/environment-ews-pdf.blade.php)

*Catatan Otorisasi:* Dilindungi middleware `can.export` (Role `Admin Management` dan `User`).

---

## 7. MATRIKS ROUTING LENGKAP (`routes/web.php`)

```php
Route::prefix('environment')->name('environment.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Ringkasan Utama & Master Data
    Route::get('/', [EnvironmentController::class, 'index'])->name('index');
    Route::get('/export-pdf', [EnvironmentController::class, 'overviewExportPdf'])->name('export-pdf')->middleware('can.export');
    Route::get('/master', [EnvironmentController::class, 'masterIndex'])->name('master.index');
    Route::post('/master', [EnvironmentController::class, 'masterStore'])->name('master.store');
    Route::put('/master/{master}', [EnvironmentController::class, 'masterUpdate'])->name('master.update');
    Route::post('/master/{master}/toggle', [EnvironmentController::class, 'masterToggle'])->name('master.toggle');
    Route::delete('/master/{master}', [EnvironmentController::class, 'masterDestroy'])->name('master.destroy');

    // 1. LB3
    Route::get('/lb3', [EnvironmentController::class, 'lb3Index'])->name('lb3.index');
    Route::post('/lb3', [EnvironmentController::class, 'lb3Store'])->name('lb3.store');
    Route::put('/lb3/{record}', [EnvironmentController::class, 'lb3Update'])->name('lb3.update');
    Route::delete('/lb3/{record}', [EnvironmentController::class, 'lb3Destroy'])->name('lb3.destroy');
    Route::get('/lb3/export-pdf', [EnvironmentController::class, 'lb3ExportPdf'])->name('lb3.export-pdf')->middleware('can.export');

    // 2. Non-LB3
    Route::get('/non-lb3', [EnvironmentController::class, 'nonLb3Index'])->name('non-lb3.index');
    Route::post('/non-lb3', [EnvironmentController::class, 'nonLb3Store'])->name('non-lb3.store');
    Route::put('/non-lb3/{record}', [EnvironmentController::class, 'nonLb3Update'])->name('non-lb3.update');
    Route::delete('/non-lb3/{record}', [EnvironmentController::class, 'nonLb3Destroy'])->name('non-lb3.destroy');
    Route::get('/non-lb3/export-pdf', [EnvironmentController::class, 'nonLb3ExportPdf'])->name('non-lb3.export-pdf')->middleware('can.export');

    // 3. RKL-RPL
    Route::get('/rkl-rpl', [EnvironmentController::class, 'rklRplIndex'])->name('rkl-rpl.index');
    Route::post('/rkl-rpl', [EnvironmentController::class, 'rklRplStore'])->name('rkl-rpl.store');
    Route::put('/rkl-rpl/{record}', [EnvironmentController::class, 'rklRplUpdate'])->name('rkl-rpl.update');
    Route::delete('/rkl-rpl/{record}', [EnvironmentController::class, 'rklRplDestroy'])->name('rkl-rpl.destroy');
    Route::get('/rkl-rpl/export-pdf', [EnvironmentController::class, 'rklRplExportPdf'])->name('rkl-rpl.export-pdf')->middleware('can.export');

    // 4. PPKH (Persetujuan Penggunaan Kawasan Hutan)
    Route::get('/ppkh', [EnvironmentController::class, 'ppkhIndex'])->name('ppkh.index');
    Route::post('/ppkh', [EnvironmentController::class, 'ppkhStore'])->name('ppkh.store');
    Route::put('/ppkh/{record}', [EnvironmentController::class, 'ppkhUpdate'])->name('ppkh.update');
    Route::delete('/ppkh/{record}', [EnvironmentController::class, 'ppkhDestroy'])->name('ppkh.destroy');
    Route::get('/ppkh/export-pdf', [EnvironmentController::class, 'ppkhExportPdf'])->name('ppkh.export-pdf')->middleware('can.export');

    // 5. Energi (BBM & Listrik)
    Route::get('/energi', [EnvironmentController::class, 'energyIndex'])->name('energi.index');
    Route::post('/energi', [EnvironmentController::class, 'energyStore'])->name('energi.store');
    Route::put('/energi/{record}', [EnvironmentController::class, 'energyUpdate'])->name('energi.update');
    Route::delete('/energi/{record}', [EnvironmentController::class, 'energyDestroy'])->name('energi.destroy');
    Route::get('/energi/export-pdf', [EnvironmentController::class, 'energyExportPdf'])->name('energi.export-pdf')->middleware('can.export');

    // 6. EWS
    Route::get('/ews', [EnvironmentController::class, 'ewsIndex'])->name('ews.index');
    Route::post('/ews', [EnvironmentController::class, 'ewsStore'])->name('ews.store');
    Route::put('/ews/{record}', [EnvironmentController::class, 'ewsUpdate'])->name('ews.update');
    Route::delete('/ews/{record}', [EnvironmentController::class, 'ewsDestroy'])->name('ews.destroy');
    Route::get('/ews/export-pdf', [EnvironmentController::class, 'ewsExportPdf'])->name('ews.export-pdf')->middleware('can.export');
});
```

---

## 8. INSTRUKSI GENERASI KODE (PROMPT AKHIR UNTUK AI ENGINE)

> Salin dan jalankan prompt instruksi di bawah ini ke AI Engine / Code Assistant Anda untuk membuat kode sumber modul Environment secara utuh:

```text
Buatkan modul Environment (Pengelolaan Lingkungan HSSE) Laravel 11/12 secara lengkap sesuai dokumen spesifikasi teknis di atas:

1. Buat file migration untuk tabel:
   - `environment_masters` (kategori, nama, submodules json array, is_active)
   - `environment_lb3_records` (waste_name, amount, unit, entry_date, characteristics json)
   - `environment_non_lb3_records` (waste_name, source, amount, unit, entry_date, transport_date)
   - `environment_rkl_rpl_records` (parameter, location, sampling_date, measurement_value, unit)
   - `environment_ppich_records` (location_name SK 2013/2024, activity_name, sampling_date)
   - `environment_energy_records` (energy_type bbm/listrik, month 1-12, year, usage_amount, unique key)
   - `environment_ews_records` (location, recording_date, condition, description)
2. Buat Model-Model Eloquent lengkap dengan casts array JSON, query scopes (active, byCategory, forSubmodule), dan accessors.
3. Buat `EnvironmentController.php` dengan implementasi lengkap CRUD untuk ke-6 submenu dan master data, validasi dropdown satuan per submenu, pemetaan SK 2013/SK 2024 pada PPKH, validasi anti-duplikasi energi bulanan, serta method export PDF A4 Landscape DomPDF.
4. Buat seluruh Blade view:
   - `data/environment/index.blade.php` (Ringkasan dengan tombol Kelola Master Data & Chart.js tren energi)
   - `data/environment/master/index.blade.php` (Pengelolaan Master Data dengan checklist submodul satuan)
   - Submenu views: `lb3/index.blade.php`, `non-lb3/index.blade.php`, `rkl-rpl/index.blade.php`, `ppkh/index.blade.php`, `energi/index.blade.php`, `ews/index.blade.php`.
   - Export templates: `exports/environment-*-pdf.blade.php` landscape A4.
5. Sediakan komponen multi-file upload compact dengan tombol "+ Tambah Dokumen" yang mendukung penambahan dokumen dari folder berbeda tanpa menimpa file yang sudah terpilih.
```

