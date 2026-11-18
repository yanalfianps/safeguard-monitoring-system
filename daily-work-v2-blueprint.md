# MASTER PROMPT & SPESIFIKASI TEKNIS: MODUL DAILY WORK (HSSE)

```text
================================================================================
MASTER PROMPT: GENERATE MODUL DAILY WORK LENGKAP (LARAVEL 11 + BLADE + MYSQL)
================================================================================

Role: Senior Full-Stack Laravel Developer & Database Architect.
Task: Bangun ulang modul "Daily Work" (Pekerjaan Harian HSSE) secara komprehensif, 
      kuat, reaktif, dan terintegrasi penuh sesuai arsitektur sistem di bawah ini.
Framework: Laravel 11, Blade Templates, Tailwind CSS / Bootstrap 5 UI, Alpine.js / Vanilla JS, 
           Chart.js, DomPDF (barryvdh/laravel-dompdf), MySQL.
```

---

## 1. DESKRIPSI & TUJUAN MODUL
Modul **Daily Work** berfungsi untuk mencatat, memantau, memvalidasi perizinan kerja (SIKA), dan merekapitulasi seluruh kegiatan operasional lapangan serta pekerjaan kontraktor/internal di lingkungan kerja operasional HSSE. 
Modul ini memiliki pemisahan mode kerja harian yang dinamis (*Hari Ini* vs *Semua Riwayat*), pembatasan hierarki perusahaan pelaksana, checklist Surat Izin Kerja Aman (SIKA), pengecekan duplikasi real-time, visualisasi distribusi beban kerja (Pie Chart), dan laporan siap cetak format PDF Landscape A4.

---

## 2. SKEMA BASIS DATA & MIGRATIONS

### A. Tabel `daily_work_companies` (Master Perusahaan & Hierarki)
Menyimpan daftar perusahaan induk (*main/parent*), kontraktor rekanan (*subcon*), dan perusahaan independen.
```sql
CREATE TABLE `daily_work_companies` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `category` ENUM('main', 'subcon', 'independent') DEFAULT 'independent',
  `parent_company_id` BIGINT UNSIGNED NULL,
  `is_required` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`parent_company_id`) REFERENCES `daily_work_companies`(`id`) ON DELETE SET NULL
);
```

### B. Tabel `sikkas` (Master Surat Izin Kerja Aman)
Menyimpan daftar jenis izin risiko tinggi yang wajib dipilih saat pekerjaan berlangsung.
```sql
CREATE TABLE `sikkas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
);
```

### C. Tabel `daily_works` (Tabel Transaksi Utama)
```sql
CREATE TABLE `daily_works` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `company_id` BIGINT UNSIGNED NULL,
  `unit` VARCHAR(100) NOT NULL, -- Menyimpan nama snapshot perusahaan saat disimpan
  `category` VARCHAR(100) NULL DEFAULT 'Umum',
  `ptw_classification` TEXT NULL, -- Backward compatibility legacy
  `sikka` TEXT NULL, -- Disimpan sebagai string dipisahkan koma (e.g. "Ketinggian, Panas")
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `executor` VARCHAR(255) NULL DEFAULT '-',
  `status` ENUM('Belum Mulai', 'Berlangsung', 'Selesai', 'Ditunda') NOT NULL DEFAULT 'Berlangsung',
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_daily_works_dates` (`start_date`, `end_date`),
  INDEX `idx_daily_works_status` (`status`),
  FOREIGN KEY (`company_id`) REFERENCES `daily_work_companies`(`id`) ON DELETE SET NULL
);
```

---

## 3. LOGIKA MASTER PERUSAHAAN & ATURAN HIERARKI EKSEKUTOR

### 16 Perusahaan Bawaan Sistem (Required Master Companies):
1. **Grup GDE:**
   - `GDE (All)` [Category: `main`, Parent: `null`, is_required: `1`]
   - `GDE (Only)` [Category: `subcon`, Parent: `GDE (All)`, is_required: `1`]
   - `KODIPA` [Category: `subcon`, Parent: `GDE (All)`, is_required: `1`]
2. **Grup JOTRE:**
   - `JOTRE (All)` [Category: `main`, Parent: `null`, is_required: `1`]
   - `JOTRE (Only)` [Category: `subcon`, Parent: `JOTRE (All)`, is_required: `1`]
   - `PJSE` [Category: `subcon`, Parent: `JOTRE (All)`, is_required: `1`]
   - `ADP` [Category: `subcon`, Parent: `JOTRE (All)`, is_required: `1`]
   - `SOKKO` [Category: `subcon`, Parent: `JOTRE (All)`, is_required: `1`]
   - `BISMO` [Category: `subcon`, Parent: `JOTRE (All)`, is_required: `1`]
   - `WCP` [Category: `subcon`, Parent: `JOTRE (All)`, is_required: `1`]
3. **Perusahaan Independen:**
   - `TDP (BUJP)` [Category: `independent`, Parent: `null`, is_required: `1`]
   - `PJSE (Utility)` [Category: `independent`, Parent: `null`, is_required: `1`]
   - `Rentokil (Pest Control)` [Category: `independent`, Parent: `null`, is_required: `1`]
   - `PMC` [Category: `independent`, Parent: `null`, is_required: `1`]
   - `Bhumi Phala (Maintenance EWS)` [Category: `independent`, Parent: `null`, is_required: `1`]
   - `Environesia` [Category: `independent`, Parent: `null`, is_required: `1`]

### Aturan Bisnis Hierarki:
1. **Parent Company Restriction (Restriksi Input Pelaksana):**
   - Entitas induk `GDE (All)` dan `JOTRE (All)` hanya berfungsi sebagai **kategori payung** untuk filter rekapitulasi data dan statistik.
   - Perusahaan induk **DILARANG / TIDAK BOLEH** dipilih sebagai pelaksana pekerjaan pada form *Tambah Pekerjaan Baru*.
   - User wajib memilih sub-entitasnya (misal: memilih `GDE (Only)`, `KODIPA`, `PJSE`, `WCP`, dll).
   - *Pengecualian Data Historis:* Jika record lama sudah terlanjur memilih `GDE (All)`/`JOTRE (All)`, nilai tersebut tetap dapat ditampilkan dan disimpan pada edit form selama pengguna tidak mengubahnya.
2. **Sinkronisasi Filter Aktif:**
   - Jika induk `GDE (All)` dinonaktifkan oleh Admin, maka otomatis seluruh subcon di bawahnya tidak muncul di dropdown filter aktif, dan sebaliknya saat diaktifkan.
3. **Proteksi Master Data:**
   - Perusahaan bertanda `is_required = 1` tidak dapat dihapus (`delete`), hanya dapat diubah status aktif/nonaktifnya (`toggle`).

---

## 4. MASTER DATA SIKA (SURAT IZIN KERJA AMAN)

### 7 Pilihan Default SIKA:
- `Ruang Terbatas` (Confined Space)
- `Ketinggian` (Working at Height)
- `Penggalian` (Excavation)
- `Listrik` (Electrical)
- `Isolasi Energi` (LOTO / Lockout-Tagout)
- `Lifting` (Pengangkatan Berat)
- `Panas` (Hot Work)

### Aturan Bisnis SIKA:
1. **Multi-Select Input:** Disajikan dalam bentuk daftar kotak centang (*checkboxes*) di dalam modal pekerjaan.
2. **Opsional:** Bersifat opsional (pekerjaan umum tanpa izin khusus diperbolehkan memiliki SIKA kosong / `-`).
3. **Format Penyimpanan:** Disimpan dalam satu kolom `sikka` dengan format string dipisahkan tanda koma (contoh: `Ketinggian, Panas`).
4. **Model Accessor:** Model menyediakan accessor `$dailyWork->sika_array` yang mengembalikan array murni `['Ketinggian', 'Panas']` untuk kemudahan binding form UI.
5. **Cascade Update saat Edit Master:** Jika nama master SIKA diubah oleh Admin (misal: `Panas` -> `Pekerjaan Panas`), seluruh record pekerjaan lama yang mengandung kata `Panas` otomatis diperbarui (*cascaded*).
6. **Deletion Guard:** Master SIKA yang sedang aktif digunakan oleh salah satu pekerjaan di database tidak boleh dihapus (menampilkan alert proteksi).

---

## 5. STRUKTUR FORM INPUT, PENJADWALAN & STATUS OTOMATIS

### Field Input Modal Tambah/Edit Pekerjaan:
| Field Form | Tipe Input | Wajib/Opsional | Keterangan & Validasi |
|---|---|---|---|
| **Nama Pekerjaan** | `text` | **Wajib** | Min 3 karakter, Max 255 karakter. Dilengkapi AJAX Real-Time Duplicate Checker. |
| **Perusahaan Pelaksana** | `select` | **Wajib** | Mengambil dari `daily_work_companies` (hanya yang aktif & lolos aturan eksekutor). |
| **SIKA Checklist** | `checkbox[]` | Opsional | Checkbox multi-pilihan dari tabel master `sikkas`. |
| **Tanggal Mulai** | `date` | **Wajib** | Format `YYYY-MM-DD`. |
| **Tanggal Selesai** | `date` | **Wajib** | Format `YYYY-MM-DD`, validasi rule: `after_or_equal:start_date`. |
| **Lokasi Pekerjaan** | `text` | **Wajib** | Lokasi spesifik area HSSE / Geothermal field. |
| **Status Pekerjaan** | `select` | **Wajib** | Pilihan: `Belum Mulai`, `Berlangsung`, `Selesai`, `Ditunda`. |
| **Deskripsi / Catatan** | `textarea` | Opsional | Keterangan lingkup kerja atau mitigasi risiko. |

### Logika Otomatisasi Status & Penjadwalan:
1. **Auto-Status via Model Booted Hook:**
   - Jika `status` tidak ditentukan atau bernilai default:
     - Jika hari ini (`today`) berada di antara `start_date` dan `end_date`, status otomatis diset menjadi **`Berlangsung`**.
     - Jika hari ini belum mencapai `start_date`, status diset **`Belum Mulai`**.
   - Jika pengguna secara eksplisit menandai pekerjaan selesai (klik tombol centang "Tandai Selesai" atau memilih opsi `Selesai`), status tetap terkunci sebagai **`Selesai`**.
2. **Pencegahan Duplikasi Real-Time (AJAX Duplicate Check):**
   - Saat mengetik Nama Pekerjaan dan memilih Perusahaan, sistem mengirim query AJAX `POST /daily-work/check-duplicate`.
   - Jika terdapat pekerjaan aktif dengan nama dan perusahaan yang sama dalam rentang tanggal yang bersinggungan, form menampilkan peringatan visual kuning (*soft alert*) untuk mencegah entri berulang tanpa memblokir form secara kaku.

---

## 6. SISTEM LAMPIRAN FILE / DOKUMEN (ATTACHMENT SPECIFICATION)

> **Kondisi Eksisting & Standar Integrasi:**
> Pada core database operasional harian, data Daily Work dioptimalkan untuk kecepatan logging lapangan ringkas. Untuk integrasi penuh dengan modul dokumen HSSE (seperti halnya di Modul *Environment* & *Monitoring Anggaran*), skema lampiran menggunakan relasi Polymorphic Attachment atau Spatie MediaLibrary:

1. **Format File yang Didukung:** PDF, DOCX, XLSX, JPG, JPEG, PNG.
2. **Maksimal Ukuran:** 10 MB per file.
3. **Mekanisme Multi-File Input:** Menggunakan dropzone ringkas (tinggi ~100px) dengan tombol `+ Tambah Dokumen` yang memungkinkan pemilihan dokumen dari berbagai folder tanpa menimpa dokumen yang sudah terpilih.
4. **Tabel Penyimpanan Polymorphic (Rekomendasi Standar HSSE):**
```sql
CREATE TABLE `document_attachments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `attachable_type` VARCHAR(150) NOT NULL, -- e.g. 'App\Models\DailyWork'
  `attachable_id` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_attachable` (`attachable_type`, `attachable_id`)
);
```

---

## 7. ARSITEKTUR UI/UX & DUAL-TAB SYSTEM

Antarmuka Daily Work memiliki parameter query `?mode=today` (default) dan `?mode=all`.

### A. Tab "Hari Ini" (`mode=today`):
- **Tujuan:** Fokus operasional pengawasan harian HSSE Officer.
- **Filter Logika Query:**
  - Hanya menampilkan pekerjaan yang **aktif pada tanggal sistem hari ini** (`start_date <= today AND end_date >= today`).
  - Hanya menampilkan pekerjaan yang **belum berstatus `Selesai`** (`status != 'Selesai'`).
- **Urutan Tampilan (Sorting):** Wajib diurutkan secara **Alfabetis (A-Z)** berdasarkan nama perusahaan (`unit`), kemudian nama pekerjaan (`name`).
- **Aksi Cepat:** Terdapat tombol ikon centang hijau **"Tandai Selesai"** di setiap baris tabel. Saat diklik, mengirim request AJAX/PATCH `POST /daily-work/{id}/complete` yang langsung mengupdate status menjadi `Selesai` dan merefresh tabel.

### B. Tab "Semua Riwayat" (`mode=all`):
- **Tujuan:** Audit trail, pencarian riwayat pekerjaan masa lalu, monitoring pekerjaan selesai atau ditunda.
- **Filter Logika Query:**
  - Menampilkan seluruh data tanpa batasan status.
  - Mendukung filter rentang tanggal kustom (`start_date` s.d `end_date`), filter perusahaan, filter jenis SIKA, dan pencarian kata kunci (*search keyword*).
- **Urutan Tampilan:** Diurutkan alfabetis nama perusahaan, atau tanggal terbaru (*descending*).
- **Aksi:** Tombol aksi hanya "Detail", "Edit", dan "Hapus" (tidak ada tombol cepat tandai selesai karena tab ini mencakup data arsip).

---

## 8. REKAPITULASI STATISTIK & PIE CHART DISTRIBUSI

### Kartu Metrik Ringkasan:
1. **Kartu "Pekerjaan Hari Ini":** Menampilkan angka total pekerjaan aktif yang sedang berjalan hari ini.
2. **Kartu "Perusahaan Terlibat":** Menampilkan jumlah total kontraktor/perusahaan unik yang memiliki pekerjaan aktif hari ini.
   - *Interaktivitas:* Kartu ini memiliki tombol klik yang memunculkan **Modal Popup Daftar Perusahaan Terlibat Hari Ini**, merinci nama kontraktor dan jumlah pekerjaan aktif masing-masing.

### Diagram Lingkaran (Distribution Pie Chart - Chart.js):
- Merender persentase porsi keterlibatan masing-masing perusahaan secara dinamis.
- Warna palet otomatis terdistribusi rapi (Emerald, Blue, Amber, Rose, Purple, Teal, Indigo, Sky).
- Legend interaktif yang dapat diklik untuk menyembunyikan/menampilkan irisan diagram.
- Tooltip menyajikan: `[Nama Perusahaan]: [Jumlah Pekerjaan] pekerjaan ([Persentase]%)`.

---

## 9. FITUR EXPORT PDF (LANDSCAPE A4)

1. **Format Dokumen:** A4 Landscape, margin 10mm, branding header resmi HSSE (Logo Perusahaan, Judul Modul, Tanggal Cetak, Identitas Petugas).
2. **Dua Mode Laporan:**
   - Mode Hari Ini: Header mencantumkan judul **"LAPORAN HARIAN PEKERJAAN HSSE (DAILY WORK)"** per tanggal cetak.
   - Mode Semua Riwayat: Header mencantumkan judul **"LAPORAN RIWAYAT PEKERJAAN HSSE (DAILY WORK)"** beserta keterangan filter yang diaplikasikan.
3. **Kolom Laporan PDF:**
   - No.
   - Nama Pekerjaan
   - Perusahaan / Kontraktor (disertai label induk jika subcon, e.g. `KODIPA (GDE)`)
   - Lokasi
   - Izin SIKA (Badge SIKA yang berlaku)
   - Periode Pelaksanaan (`dd/mm/yyyy` s.d `dd/mm/yyyy`)
   - Status Pekerjaan (`Berlangsung`, `Selesai`, `Ditunda`)
   - Keterangan / Scope
4. **Otorisasi Cetak:** Terbuka untuk role `Admin Management` dan `User` (dikecualikan untuk role restricted seperti inventory viewer).

---

## 10. MATRIX ROUTING, CONTROLLER & ACTIVITY LOGGING

### Daftar Route (`routes/web.php`):
```php
Route::middleware(['auth', 'not.inventory'])->group(function () {
    // Tampilan Utama & Export PDF
    Route::get('/daily-work', [DailyWorkController::class, 'index'])->name('daily-work.index');
    Route::get('/daily-work/export-pdf', [DailyWorkController::class, 'exportPdf'])->name('daily-work.export-pdf');
    Route::get('/daily-work/{dailyWork}', [DailyWorkController::class, 'show'])->name('daily-work.show');
    Route::post('/daily-work/check-duplicate', [DailyWorkController::class, 'checkDuplicate'])->name('daily-work.check-duplicate');

    // Transaksi Pekerjaan (Write / Update / Delete)
    Route::post('/daily-work', [DailyWorkController::class, 'store'])->name('daily-work.store');
    Route::put('/daily-work/{dailyWork}', [DailyWorkController::class, 'update'])->name('daily-work.update');
    Route::delete('/daily-work/{dailyWork}', [DailyWorkController::class, 'destroy'])->name('daily-work.destroy');
    Route::patch('/daily-work/{dailyWork}/complete', [DailyWorkController::class, 'complete'])->name('daily-work.complete');

    // Pengelolaan Master Data Perusahaan
    Route::post('/daily-work/companies', [DailyWorkController::class, 'storeCompany'])->name('daily-work.companies.store');
    Route::put('/daily-work/companies/{company}', [DailyWorkController::class, 'updateCompany'])->name('daily-work.companies.update');
    Route::patch('/daily-work/companies/{company}/toggle', [DailyWorkController::class, 'toggleCompany'])->name('daily-work.companies.toggle');
    Route::delete('/daily-work/companies/{company}', [DailyWorkController::class, 'destroyCompany'])->name('daily-work.companies.destroy');

    // Pengelolaan Master Data SIKA
    Route::post('/daily-work/sikkas', [DailyWorkController::class, 'storeSikka'])->name('daily-work.sikkas.store');
    Route::put('/daily-work/sikkas/{sikka}', [DailyWorkController::class, 'updateSikka'])->name('daily-work.sikkas.update');
    Route::patch('/daily-work/sikkas/{sikka}/toggle', [DailyWorkController::class, 'toggleSikka'])->name('daily-work.sikkas.toggle');
    Route::delete('/daily-work/sikkas/{sikka}', [DailyWorkController::class, 'destroySikka'])->name('daily-work.sikkas.destroy');
});
```

### Audit Trail (Activity Logging):
Setiap mutasi data wajib tercatat pada tabel `activity_logs`:
- `CREATE`: Mencatat penambahan pekerjaan baru beserta ID, nama pekerjaan, dan perusahaan.
- `UPDATE`: Mencatat perubahan atribut spesifik pekerjaan.
- `COMPLETE`: Log khusus saat tombol cepat selesaikan diklik: `"Menandai pekerjaan [Nama Pekerjaan] sebagai Selesai"`.
- `DELETE`: Mencatat penghapusan record beserta snapshot ringkas data yang dihapus.

---

## 11. INSTRUKSI GENERASI KODE (PROMPT AKHIR UNTUK AI ENGINE)

> Salin dan jalankan prompt instruksi di bawah ini ke AI Engine / Code Assistant Anda untuk membuat kode sumbernya:

```text
Buatkan implementasi kode lengkap untuk modul Daily Work Laravel sesuai dokumen spesifikasi di atas:

1. Buat migration file lengkap untuk tabel `daily_work_companies`, `sikkas`, dan `daily_works` lengkap dengan index dan foreign key constraint.
2. Buat Model `DailyWorkCompany.php` lengkap dengan relasi self-referencing parent-child, query scope active, method getActiveForFilter(), dan validasi canBeWorkExecutor().
3. Buat Model `Sikka.php` dengan default options seeder dan method proteksi hapus saat masih berelasi dengan pekerjaan.
4. Buat Model `DailyWork.php` lengkap dengan casts tanggal, booted hook untuk penentuan status otomatis, accessor sika_array, dan query scope filter.
5. Buat Controller `DailyWorkController.php` yang mengimplementasikan mode today vs all, pencarian overlap tanggal, ajax duplicate check, CRUD master perusahaan, CRUD master SIKA, complete action, dan export PDF DomPDF landscape A4.
6. Buat Blade view `index.blade.php` lengkap dengan:
   - Dua Tab (Hari Ini & Semua Riwayat)
   - Kartu Metrik & Chart.js Pie Chart distribusi perusahaan
   - Modal Tambah & Edit Pekerjaan dengan Checklist SIKA
   - Modal Kelola Master Perusahaan & Master SIKA
   - Modal Daftar Perusahaan Terlibat Hari Ini
   - Tombol Cepat Selesaikan Pekerjaan dengan konfirmasi responsif
7. Pastikan seluruh styling konsisten dengan tema HSSE Project (Tailwind CSS / Bootstrap) dan siap dideploy tanpa error.
```

