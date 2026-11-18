# BLUEPRINT TEKNIS MIGRASI MODUL ENVIRONMENT (HSSE) V2.0
**Target File Output:** `environment-v2-blueprint.md`  
**Sumber Audit:** Codebase Aktual Project Laravel `HSSE PROJECT`  
**Tanggal Audit:** 28 Oktober 2026  
**Status:** Terverifikasi Berdasarkan Kode Nyata (Codebase-Verified)

---

## 1. TUJUAN DAN RUANG LINGKUP MIGRASI

### 1.1 Tujuan Migrasi
Dokumen ini disusun sebagai panduan arsitektur dan instruksi teknis langkah demi langkah untuk memindahkan (migrasi) seluruh modul **Environment (Pengelolaan & Pemantauan Lingkungan HSSE)** dari project sumber ke project Laravel HSSE target yang sudah berjalan secara mandiri.

Tujuan utama migrasi adalah:
1. Memindahkan fungsionalitas modul Environment secara 100% lengkap tanpa kehilangan fitur, relasi data, atau integritas operasional.
2. Memastikan seluruh 6 submenu, dashboard ringkasan, master data terpusat, mekanisme upload dokumen multi-file, dan 7 template ekspor PDF berfungsi sempurna di project tujuan.
3. Menjamin proses migrasi berjalan secara **non-destruktif** (tidak menimpa file `.env`, tidak mereset database, tidak menghapus akun/role/permission existing, dan tidak merusak modul lain seperti Daily Work, Monitoring Anggaran, Incident, Issue Follow Up, Gas Monitoring, maupun Inventory).

### 1.2 Ruang Lingkup
Ruang lingkup migrasi meliputi:
- **Routing:** Seluruh route ber-prefix `/environment` dengan name prefix `environment.*`.
- **Database:** 7 tabel database spesifik Environment beserta relasi foreign key, indexes, dan seeder master data awal.
- **Backend:** Controller `EnvironmentController.php` beserta 7 model Eloquent terkait.
- **Frontend / UI:** Seluruh Blade view di `resources/views/data/environment/` (termasuk komponen partial upload) dan template cetak di `resources/views/exports/`.
- **Integrasi Navigasi & Otorisasi:** Integrasi menu Environment pada Sidebar, Blade directives, Gate otorisasi, dan middleware access control.
- **File Storage:** Konfigurasi symlink dan direktori penyimpanan lampiran file dokumen di `storage/app/public/environment/`.

---

## 2. RINGKASAN ARSITEKTUR MODUL ENVIRONMENT

Modul Environment dibangun di atas arsitektur MVC (Model-View-Controller) standar Laravel 11/12 dengan karakteristik teknis berikut:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           ROUTING LAYER (web.php)                           │
│ Prefix: /environment | Middleware: ['auth', 'not.inventory']                │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                 CONTROLLER LAYER (EnvironmentController.php)                │
│  - 28 Public Methods (CRUD 6 Submenu, Master Data, 8 Ekspor PDF/Excel)       │
└──────┬───────────────────────────────┬───────────────────────────────┬──────┘
       │                               │                               │
       ▼                               ▼                               ▼
┌──────────────────────┐    ┌──────────────────────┐    ┌─────────────────────┐
│     MODEL LAYER      │    │      VIEW LAYER      │    │    STORAGE LAYER    │
│                      │    │                      │    │                     │
│ 1. EnvironmentMaster │    │ - Ringkasan (Index)  │    │ storage/app/public/ │
│ 2. Lb3Record         │    │ - Master Data        │    │ environment/        │
│ 3. NonLb3Record      │    │ - 6 Submenu Views    │    │  ├── lb3/           │
│ 4. RklRplRecord      │    │ - Partial Uploader   │    │  ├── non_lb3/       │
│ 5. PpichRecord(PPKH) │    │ - 7 PDF Templates    │    │  ├── rkl_rpl/       │
│ 6. EnergyRecord      │    │                      │    │  ├── ppkh/          │
│ 7. EwsRecord         │    │                      │    │  ├── energy/        │
│                      │    │                      │    │  └── ews/           │
└──────────┬───────────┘    └──────────────────────┘    └─────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                             DATABASE LAYER (MySQL)                          │
│ 7 Tabel: environment_masters, environment_lb3_records,                      │
│          environment_non_lb3_records, environment_rkl_rpl_records,          │
│          environment_ppich_records, environment_energy_records,             │
│          environment_ews_records                                            │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. DAFTAR SUBMENU DAN FUNGSI MASING-MASING

Modul Environment memiliki 1 halaman utama (Ringkasan) dan 6 submenu operasional, ditambah panel kelola Master Data:

| Submenu | Route Path | Active State Sidebar | Deskripsi & Fungsi Utama |
|---|---|---|---|
| **Ringkasan** | `/environment` | `environment`, `environment-overview` | Dashboard eksekutif yang merangkum total limbah LB3, Non-LB3, parameter RKL-RPL, monitoring PPKH, EWS, dan grafik tren energi bulanan (Chart.js). Terdapat tombol aksi **Kelola Master Data**. |
| **1. LB3** | `/environment/lb3` | `environment-lb3` | Pencatatan limbah Bahan Berbahaya dan Beracun yang masuk ke TPS. Mendukung multi-select karakteristik bahaya, dropdown satuan khusus LB3, dan dokumen manifest. |
| **2. Non-LB3** | `/environment/non-lb3` | `environment-non-lb3` | Pencatatan sampah domestik/padat. Sumber limbah diinput secara bebas (*free-text*), terdapat tanggal masuk dan tanggal pengangkutan ke TPA, serta satuan khusus Non-LB3. |
| **3. RKL–RPL** | `/environment/rkl-rpl` | `environment-rkl-rpl` | Pemantauan hasil uji laboratorium lingkungan berdasarkan Parameter, Lokasi Titik Pantau, Tanggal Sampling, Nilai Hasil Pengukuran (angka maupun teks batas deteksi e.g. `< 0.05`), Satuan Lab, dan lampiran Certificate of Analysis (CoA). |
| **4. PPKH** | `/environment/ppkh` | `environment-ppkh`, `environment-ppich` | Pemantauan Persetujuan Penggunaan Kawasan Hutan. Wajib memilih Monitoring Lokasi beracuan **`SK 2013`** atau **`SK 2024`**, mencatat uraian aktivitas lapangan, serta mengunggah bukti dokumentasi batas hutan. |
| **5. Energi** | `/environment/energi` | `environment-energi` | Pemantauan konsumsi energi dengan sistem **Dual Tab**: Tab **BBM** (satuan otomatis `Liter`) dan Tab **Listrik** (satuan otomatis `kWh`). Invariant validasi unik mencegah input ganda pada bulan-tahun yang sama. |
| **6. EWS** | `/environment/ews` | `environment-ews` | Pencatatan status Early Warning System (sensor pergerakan lereng / geoteknik) pada titik pantau master (`PAD9`, `PAD31`, `PAD7`, `PAD30`), deskripsi kondisi aktual bebas, dan foto kondisi lapangan. |
| **Master Data** | `/environment/master` | `environment-master` | Pengelolaan data referensi terpusat: Satuan (dengan pemetaan submodul), Karakteristik LB3, Parameter RKL-RPL, Lokasi RKL-RPL, dan Lokasi EWS. |

---

## 4. INVENTARIS FILE SUMBER BESERTA FUNGSI & DEPENDENSINYA

### 4.1 Backend (PHP / Laravel)
1. **Controller:**
   - `app/Http/Controllers/Modules/EnvironmentController.php`: Mengelola seluruh alur request, validasi, query database, pemetaan satuan, CRUD data operasional, CRUD master data, dan response PDF/Excel.
2. **Models:**
   - `app/Models/EnvironmentMaster.php`: Model master data lingkungan (scope active, byCategory, forSubmodule, casts JSON submodules).
   - `app/Models/EnvironmentLb3Record.php`: Model catatan limbah B3 (casts array characteristics & attachment_paths, relasi belongsTo unit).
   - `app/Models/EnvironmentNonLb3Record.php`: Model limbah non-B3 (relasi belongsTo unit & sourceMaster, casts array attachment_paths).
   - `app/Models/EnvironmentRklRplRecord.php`: Model uji lab RKL-RPL (relasi parameter, location, unit).
   - `app/Models/EnvironmentPpichRecord.php`: Model data PPKH / PPICH (accessor monitoring_lokasi, activity_text).
   - `app/Models/EnvironmentEnergyRecord.php`: Model konsumsi BBM/Listrik (helper monthNames, accessor period_label).
   - `app/Models/EnvironmentEwsRecord.php`: Model pencatatan alat EWS (relasi location, recorder).
3. **Migrations:**
   - `database/migrations/2026_10_28_120000_create_environment_module_tables.php`: Pembuatan 7 tabel utama dan seeder awal.
   - `database/migrations/2026_10_28_130000_seed_environmental_units_and_update_ppkh.php`: Penambahan satuan lab lingkungan (`dBA`, `mg/L`, `ppm`, `mg/Nm3`, `°C`, `%`, `pH`, `Indeks`).
   - `database/migrations/2026_10_28_140000_add_submodules_to_environment_masters_table.php`: Penambahan kolom `submodules` JSON pada `environment_masters` dan mapping awalnya.

### 4.2 Views & Partials (Blade)
Path direktori: `resources/views/data/environment/`
- `index.blade.php`: Halaman Ringkasan Lingkungan dengan Chart.js dan tombol Master Data.
- `master/index.blade.php`: Halaman kelola Master Data (filter kategori pills, modal tambah/edit, toggle status).
- `lb3/index.blade.php`: Halaman CRUD LB3.
- `non-lb3/index.blade.php`: Halaman CRUD Non-LB3.
- `rkl-rpl/index.blade.php`: Halaman CRUD RKL-RPL.
- `ppkh/index.blade.php`: Halaman CRUD PPKH (Monitoring Lokasi SK 2013 / SK 2024).
- `ppich/index.blade.php`: Template legacy PPICH untuk backward-compatibility.
- `energi/index.blade.php`: Halaman Dual-Tab BBM dan Listrik.
- `ews/index.blade.php`: Halaman CRUD EWS.
- `partials/uploader.blade.php`: Komponen UI dropzone upload multi-file compact.
- `partials/upload-scripts.blade.php`: Engine JavaScript untuk drag-and-drop, validasi ukuran/ekstensi, dan penambahan multi-file dari folder berbeda.

### 4.3 Template Ekspor PDF
Path direktori: `resources/views/exports/`
- `environment-overview-pdf.blade.php`: Laporan Ringkasan Eksekutif Lingkungan A4 Landscape.
- `environment-lb3-pdf.blade.php`: Laporan Manifest Limbah B3 A4 Landscape.
- `environment-non-lb3-pdf.blade.php`: Laporan Limbah Non-B3 A4 Landscape.
- `environment-rkl-rpl-pdf.blade.php`: Laporan Pemantauan Lingkungan RKL-RPL A4 Landscape.
- `environment-ppkh-pdf.blade.php`: Laporan Kegiatan Kawasan Hutan PPKH A4 Landscape.
- `environment-ppich-pdf.blade.php`: Alias laporan legacy PPICH.
- `environment-energy-pdf.blade.php`: Laporan Pemakaian Energi BBM & Listrik A4 Landscape.
- `environment-ews-pdf.blade.php`: Laporan Pemantauan Early Warning System A4 Landscape.

---

## 5. DAFTAR CONTROLLER, MODEL, ROUTE, VIEW, DAN MIDDLEWARE

### 5.1 Route Definition (`routes/web.php`)
```php
Route::prefix('environment')->name('environment.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Ringkasan & Master Data
    Route::get('/', [EnvironmentController::class, 'index'])->name('index');
    Route::get('/export-pdf', [EnvironmentController::class, 'overviewExportPdf'])->name('export-pdf')->middleware('can.export');
    Route::get('/master', [EnvironmentController::class, 'masterIndex'])->name('master.index');
    Route::post('/master', [EnvironmentController::class, 'masterStore'])->name('master.store');
    Route::put('/master/{master}', [EnvironmentController::class, 'masterUpdate'])->name('master.update');
    Route::post('/master/{master}/toggle', [EnvironmentController::class, 'masterToggle'])->name('master.toggle');
    Route::delete('/master/{master}', [EnvironmentController::class, 'masterDestroy'])->name('master.destroy');

    // Submenu 1: LB3
    Route::get('/lb3', [EnvironmentController::class, 'lb3Index'])->name('lb3.index');
    Route::post('/lb3', [EnvironmentController::class, 'lb3Store'])->name('lb3.store');
    Route::put('/lb3/{record}', [EnvironmentController::class, 'lb3Update'])->name('lb3.update');
    Route::delete('/lb3/{record}', [EnvironmentController::class, 'lb3Destroy'])->name('lb3.destroy');
    Route::get('/lb3/export-pdf', [EnvironmentController::class, 'lb3ExportPdf'])->name('lb3.export-pdf')->middleware('can.export');
    Route::get('/lb3/export-excel', [EnvironmentController::class, 'lb3ExportExcel'])->name('lb3.export-excel')->middleware('can.export');

    // Submenu 2: Non-LB3
    Route::get('/non-lb3', [EnvironmentController::class, 'nonLb3Index'])->name('non-lb3.index');
    Route::post('/non-lb3', [EnvironmentController::class, 'nonLb3Store'])->name('non-lb3.store');
    Route::put('/non-lb3/{record}', [EnvironmentController::class, 'nonLb3Update'])->name('non-lb3.update');
    Route::delete('/non-lb3/{record}', [EnvironmentController::class, 'nonLb3Destroy'])->name('non-lb3.destroy');
    Route::get('/non-lb3/export-pdf', [EnvironmentController::class, 'nonLb3ExportPdf'])->name('non-lb3.export-pdf')->middleware('can.export');
    Route::get('/non-lb3/export-excel', [EnvironmentController::class, 'nonLb3ExportExcel'])->name('non-lb3.export-excel')->middleware('can.export');

    // Submenu 3: RKL-RPL
    Route::get('/rkl-rpl', [EnvironmentController::class, 'rklRplIndex'])->name('rkl-rpl.index');
    Route::post('/rkl-rpl', [EnvironmentController::class, 'rklRplStore'])->name('rkl-rpl.store');
    Route::put('/rkl-rpl/{record}', [EnvironmentController::class, 'rklRplUpdate'])->name('rkl-rpl.update');
    Route::delete('/rkl-rpl/{record}', [EnvironmentController::class, 'rklRplDestroy'])->name('rkl-rpl.destroy');
    Route::get('/rkl-rpl/export-pdf', [EnvironmentController::class, 'rklRplExportPdf'])->name('rkl-rpl.export-pdf')->middleware('can.export');
    Route::get('/rkl-rpl/export-excel', [EnvironmentController::class, 'rklRplExportExcel'])->name('rkl-rpl.export-excel')->middleware('can.export');

    // Submenu 4: PPKH (dan alias PPICH)
    Route::get('/ppkh', [EnvironmentController::class, 'ppkhIndex'])->name('ppkh.index');
    Route::post('/ppkh', [EnvironmentController::class, 'ppkhStore'])->name('ppkh.store');
    Route::put('/ppkh/{record}', [EnvironmentController::class, 'ppkhUpdate'])->name('ppkh.update');
    Route::delete('/ppkh/{record}', [EnvironmentController::class, 'ppkhDestroy'])->name('ppkh.destroy');
    Route::get('/ppkh/export-pdf', [EnvironmentController::class, 'ppkhExportPdf'])->name('ppkh.export-pdf')->middleware('can.export');
    Route::get('/ppkh/export-excel', [EnvironmentController::class, 'ppkhExportExcel'])->name('ppkh.export-excel')->middleware('can.export');
    Route::get('/ppich', [EnvironmentController::class, 'ppkhIndex'])->name('ppich.index');
    Route::post('/ppich', [EnvironmentController::class, 'ppkhStore'])->name('ppich.store');
    Route::put('/ppich/{record}', [EnvironmentController::class, 'ppkhUpdate'])->name('ppich.update');
    Route::delete('/ppich/{record}', [EnvironmentController::class, 'ppkhDestroy'])->name('ppich.destroy');
    Route::get('/ppich/export-pdf', [EnvironmentController::class, 'ppkhExportPdf'])->name('ppich.export-pdf')->middleware('can.export');
    Route::get('/ppich/export-excel', [EnvironmentController::class, 'ppkhExportExcel'])->name('ppich.export-excel')->middleware('can.export');

    // Submenu 5: Energi (BBM & Listrik)
    Route::get('/energi', [EnvironmentController::class, 'energyIndex'])->name('energi.index');
    Route::post('/energi', [EnvironmentController::class, 'energyStore'])->name('energi.store');
    Route::put('/energi/{record}', [EnvironmentController::class, 'energyUpdate'])->name('energi.update');
    Route::delete('/energi/{record}', [EnvironmentController::class, 'energyDestroy'])->name('energi.destroy');
    Route::get('/energi/export-pdf', [EnvironmentController::class, 'energyExportPdf'])->name('energi.export-pdf')->middleware('can.export');
    Route::get('/energi/export-excel', [EnvironmentController::class, 'energyExportExcel'])->name('energi.export-excel')->middleware('can.export');

    // Submenu 6: EWS
    Route::get('/ews', [EnvironmentController::class, 'ewsIndex'])->name('ews.index');
    Route::post('/ews', [EnvironmentController::class, 'ewsStore'])->name('ews.store');
    Route::put('/ews/{record}', [EnvironmentController::class, 'ewsUpdate'])->name('ews.update');
    Route::delete('/ews/{record}', [EnvironmentController::class, 'ewsDestroy'])->name('ews.destroy');
    Route::get('/ews/export-pdf', [EnvironmentController::class, 'ewsExportPdf'])->name('ews.export-pdf')->middleware('can.export');
    Route::get('/ews/export-excel', [EnvironmentController::class, 'ewsExportExcel'])->name('ews.export-excel')->middleware('can.export');
});
```

### 5.2 Middleware yang Terlibat
- `auth`: Memastikan user login.
- `not.inventory`: Mencegah role `admin_inventory` mengakses modul ini (Role `admin_inventory` hanya diperuntukkan modul logistik/inventory).
- `can.export`: Memvalidasi hak akses ekspor laporan PDF/Excel (User biasa dan Admin Management diizinkan).

---

## 6. STRUKTUR DATABASE LENGKAP & RELASINYA

```
┌────────────────────────────────────────────────────────┐
│                  environment_masters                   │
├────────────────────────────────────────────────────────┤
│ id (PK)                                                │
│ category VARCHAR(50)                                   │
│ name VARCHAR(150)                                      │
│ code VARCHAR(50) NULL                                  │
│ description TEXT NULL                                  │
│ submodules JSON NULL                                   │
│ order_index INT (0)                                    │
│ is_active TINYINT(1) (1)                               │
│ created_by BIGINT FK -> users(id)                      │
└─────┬───────────────────┬───────────────────┬──────────┘
      │                   │                   │
      │ unit_id           │ unit_id           │ location_id / parameter_id
      ▼                   ▼                   ▼
┌───────────────┐   ┌────────────────┐  ┌─────────────────────┐
│ env_lb3_rec   │   │ env_non_lb3_rec│  │ env_rkl_rpl_records │
├───────────────┤   ├────────────────┤  ├─────────────────────┤
│ waste_name    │   │ waste_name     │  │ parameter_id (FK)   │
│ amount        │   │ source (text)  │  │ location_id (FK)    │
│ unit_id (FK)  │   │ amount         │  │ measurement_val     │
│ unit_name     │   │ unit_id (FK)   │  │ unit_id (FK)        │
│ entry_date    │   │ unit_name      │  │ sampling_date       │
│ characts JSON │   │ entry_date     │  │ attach_paths JSON   │
│ attach JSON   │   │ transport_date │  └─────────────────────┘
└───────────────┘   │ attach JSON    │
                    └────────────────┘
```

### Detail 7 Tabel Database:

1. **`environment_masters`:**  
   Menyimpan seluruh master data. Kolom `submodules` (JSON array) menyimpan kode submodul yang diizinkan menggunakan master tersebut (misal `["lb3", "non_lb3"]`).
2. **`environment_lb3_records`:**  
   Menyimpan data operasional LB3. `characteristics` disimpan sebagai JSON array (misal `["Mudah menyala", "Beracun"]`).
3. **`environment_non_lb3_records`:**  
   Menyimpan data sampah domestik. `source` berupa teks bebas langsung (misal "Kantin", "Mess"). `transport_date` opsional.
4. **`environment_rkl_rpl_records`:**  
   Menyimpan data hasil pemantauan RKL-RPL. `measurement_value` disimpan sebagai string (`VARCHAR(255)`) agar fleksibel mendukung teks laboratorium seperti `< 0.05` atau `Negatif`.
5. **`environment_ppich_records`:**  
   Menyimpan pemantauan PPKH. `location_name` dan `reference_name` menyimpan nilai pilihan acuan: `SK 2013` atau `SK 2024`.
6. **`environment_energy_records`:**  
   Menyimpan konsumsi bulanan energi. Memiliki **Unique Key** pada `(energy_type, month, year)`.
7. **`environment_ews_records`:**  
   Menyimpan catatan kondisi EWS lereng/geoteknik. `condition` berupa deskripsi textarea kondisi nyata.

---

## 7. MASTER DATA DAN RELASI OPERASIONAL

### 7.1 Kategori Master Data Aktif (`EnvironmentMaster::activeCategoryLabels()`):
1. **`unit` (Satuan):** Digunakan pada LB3, Non-LB3, RKL-RPL, dan Energi.
2. **`lb3_characteristic` (Karakteristik LB3):** Pilihan multi-select pada form LB3.
3. **`rkl_rpl_parameter` (Parameter Pemantauan):** Pilihan parameter uji pada RKL-RPL.
4. **`rkl_rpl_location` (Lokasi Pemantauan):** Titik lokasi sampling RKL-RPL.
5. **`ews_location` (Lokasi EWS):** Titik sensor EWS (`PAD9`, `PAD31`, `PAD7`, `PAD30`).

### 7.2 Nilai Bawaan (Default Seed Data):
- **Lokasi EWS Bawaan:** `PAD9`, `PAD31`, `PAD7`, `PAD30`.
- **Karakteristik LB3 Bawaan:** `Mudah meledak`, `Mudah menyala`, `Reaktif`, `Infeksius`, `Korosif`, `Beracun`.
- **Parameter RKL-RPL Bawaan:** `Kebisingan`, `Udara ambient`, `Kebauan`, `Air bersih`, `Drainase`, `Limbah domestik`, `Biodiversitas`, `Air sungai`.
- **Lokasi RKL-RPL Bawaan:** `Area Well PAD`, `Area Power Plant`, `Reseptor Pemukiman`, `Hulu Sungai`, `Hilir Sungai`.

---

## 8. ATURAN BISNIS & VALIDASI INPUT AKTUAL

### 8.1 Aturan Validasi per Submenu (Dari Kode Nyata `EnvironmentController.php`):
1. **LB3 Store/Update:**
   - `waste_name`: `required|string|max:255`
   - `amount`: `required|numeric|min:0.001`
   - `unit_id`: `required|exists:environment_masters,id`
   - `entry_date`: `required|date`
   - `characteristics`: `nullable|array` (elemen: `string|max:100`)
   - `attachments.*`: `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`
2. **Non-LB3 Store/Update:**
   - `waste_name`: `required|string|max:255`
   - `source`: `required|string|max:255` (input bebas)
   - `amount`: `required|numeric|min:0.001`
   - `unit_id`: `required|exists:environment_masters,id`
   - `entry_date`: `required|date`
   - `transport_date`: `nullable|date|after_or_equal:entry_date`
   - `attachments.*`: `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`
3. **RKL–RPL Store/Update:**
   - `parameter_id`: `required|exists:environment_masters,id`
   - `location_id`: `required|exists:environment_masters,id`
   - `sampling_date`: `required|date`
   - `measurement_value`: `required|string|max:255`
   - `unit_id`: `nullable|exists:environment_masters,id`
   - `attachments.*`: `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`
4. **PPKH Store/Update:**
   - `monitoring_lokasi`: `required|string|in:SK 2013,SK 2024` (Wajib memilih antara SK 2013 atau SK 2024)
   - `activity`: `required|string`
   - `attachments.*`: `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`
5. **Energi Store/Update:**
   - `energy_type`: `required|in:bbm,listrik`
   - `month`: `required|integer|between:1,12`
   - `year`: `required|integer|min:2000|max:2099`
   - `usage_amount`: `required|numeric|min:0.01`
   - **Anti-Duplikasi:** Cek `EnvironmentEnergyRecord::where('energy_type', ...)->where('month', ...)->where('year', ...)->exists()`. Jika duplikat, kembalikan error `Data pemakaian {tipe} untuk periode {bulan} {tahun} sudah tercatat`.
6. **EWS Store/Update:**
   - `location_id`: `required|exists:environment_masters,id`
   - `recording_date`: `required|date`
   - `condition`: `required|string` (input textarea kondisi aktual)
   - `attachments.*`: `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`
7. **Master Data Store/Update:**
   - `category`: `required|string|in:unit,lb3_characteristic,rkl_rpl_parameter,rkl_rpl_location,ews_location`
   - `name`: `required|string|max:150`
   - `submodules`: `nullable|array` (hanya berlaku untuk kategori `unit`)

---

## 9. PEMETAAN SATUAN DAN SUMBER DROPDOWN

Sistem menggunakan query scope `forSubmodule($submodule)` pada `EnvironmentMaster` untuk memfilter satuan yang relevan:

```php
// Controller Implementation:
$units = EnvironmentMaster::active()->byCategory('unit')->forSubmodule('lb3')->get();
```

### Tabel Matriks Pemetaan Satuan:
| Nama Satuan | Kode | LB3 (`lb3`) | Non-LB3 (`non_lb3`) | RKL–RPL (`rkl_rpl`) | BBM (`energi_bbm`) | Listrik (`energi_listrik`) |
|---|---|:---:|:---:|:---:|:---:|:---:|
| **Ton** | `TON` | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Kilogram (kg)**| `KG` | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Liter** | `LTR` | ✅ | ✅ | ❌ | ✅ | ❌ |
| **Drum** | `DRM` | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Sak/karung** | `SAK` | ✅ | ✅ | ❌ | ❌ | ❌ |
| **kWh** | `KWH` | ❌ | ❌ | ❌ | ❌ | ✅ |
| **dBA** | `DBA` | ❌ | ❌ | ✅ | ❌ | ❌ |
| **mg/L** | `MG_L` | ❌ | ❌ | ✅ | ❌ | ❌ |
| **ppm** | `PPM` | ❌ | ❌ | ✅ | ❌ | ❌ |
| **mg/Nm3** | `MG_NM3`| ❌ | ❌ | ✅ | ❌ | ❌ |
| **°C** | `CELSIUS`| ❌ | ❌ | ✅ | ❌ | ❌ |
| **%** | `PERCENT`| ❌ | ❌ | ✅ | ❌ | ❌ |
| **pH** | `PH` | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Indeks** | `INDEX` | ❌ | ❌ | ✅ | ❌ | ❌ |

---

## 10. MEKANISME UPLOAD DOKUMEN & STORAGE

1. **Komponen Blade:** `@include('data.environment.partials.uploader', [...])`
2. **Karakteristik UI:**
   - Dropzone compact (tinggi ~80–105px) dengan warna latar netral `#f8fafc`.
   - Tombol **`+ Tambah Dokumen`** yang menyisipkan `<input type="file">` baru secara dinamis. Fitur ini memungkinkan pengguna memilih dokumen dari Folder X, lalu mengklik tombol lagi untuk memilih dokumen dari Folder Y tanpa menimpa file dari Folder X.
   - Daftar pratinjau terpadu (*unified file list*) yang menampilkan nama file, icon ekstensi, dan tombol hapus file individu.
3. **Penyimpanan Disk & Direktori:**
   - Disk default: `public` (`storage/app/public/`)
   - Path subdirektori:
     - LB3: `environment/lb3/`
     - Non-LB3: `environment/non_lb3/`
     - RKL-RPL: `environment/rkl_rpl/`
     - PPKH: `environment/ppkh/`
     - Energi: `environment/energy/`
     - EWS: `environment/ews/`
4. **Mekanisme Update & Deletion:**
   - Form Edit mendukung array `deleted_attachments[]`.
   - File fisik yang ditandai hapus akan dimusnahkan secara aman menggunakan `Storage::disk('public')->delete($filePath)`.

---

## 11. MEKANISME EXPORT PDF

Seluruh ekspor PDF menggunakan library `barryvdh/laravel-dompdf` dengan konfigurasi:
- **Ukuran Kertas:** A4
- **Orientasi:** Landscape (`setPaper('a4', 'landscape')`)
- **Branding Header:** Logo HSSE, Judul Laporan Resmi, Tanggal Cetak, dan Nama Petugas Pengunduh.
- **Filter Data:** Filter pencarian, tanggal, lokasi, dan satuan pada tabel UI otomatis diteruskan ke query PDF sehingga laporan yang dicetak persis sesuai dengan data yang sedang difilter oleh user.

---

## 12. INTEGRASI SIDEBAR, LAYOUT, DAN OTORISASI

### 12.1 Sidebar Navigation (`resources/views/components/sidebar.blade.php`)
Menu Environment diletakkan di bawah menu Monitoring Anggaran dengan icon `bi bi-tree-fill`:
```blade
<a class="nav-link-custom {{ Str::startsWith($active, 'environment') ? '' : 'collapsed' }}" 
   data-bs-toggle="collapse" href="#environmentSubmenu" role="button">
    <i class="bi bi-tree-fill"></i> Environment
    <i class="bi bi-chevron-down nav-chevron"></i>
</a>
<div class="collapse {{ Str::startsWith($active, 'environment') ? 'show' : '' }} submenu" id="environmentSubmenu">
    <a class="nav-link-custom {{ $active === 'environment' ? 'active' : '' }}" href="{{ route('environment.index') }}"><i class="bi bi-speedometer2"></i> Ringkasan</a>
    <a class="nav-link-custom {{ $active === 'environment-lb3' ? 'active' : '' }}" href="{{ route('environment.lb3.index') }}"><i class="bi bi-radioactive"></i> LB3</a>
    <a class="nav-link-custom {{ $active === 'environment-non-lb3' ? 'active' : '' }}" href="{{ route('environment.non-lb3.index') }}"><i class="bi bi-trash3"></i> Non-LB3</a>
    <a class="nav-link-custom {{ $active === 'environment-rkl-rpl' ? 'active' : '' }}" href="{{ route('environment.rkl-rpl.index') }}"><i class="bi bi-clipboard2-pulse"></i> RKL–RPL</a>
    <a class="nav-link-custom {{ in_array($active, ['environment-ppkh', 'environment-ppich']) ? 'active' : '' }}" href="{{ route('environment.ppkh.index') }}"><i class="bi bi-geo-alt"></i> PPKH</a>
    <a class="nav-link-custom {{ Str::startsWith($active, 'environment-energi') ? 'active' : '' }}" href="{{ route('environment.energi.index') }}"><i class="bi bi-lightning-charge"></i> Energi</a>
    <a class="nav-link-custom {{ $active === 'environment-ews' ? 'active' : '' }}" href="{{ route('environment.ews.index') }}"><i class="bi bi-bell"></i> EWS</a>
</div>
```

### 12.2 Role & Hak Akses
1. **Admin Management (`admin_management`):** Memiliki akses penuh (Read, Create, Update, Delete, Export PDF, Kelola Master Data).
2. **User (`user`):** Memiliki akses Read dan Export PDF (Tombol Tambah/Edit/Hapus otomatis disembunyikan via Blade Directive `@adminManagement`).
3. **Admin Inventory (`admin_inventory`):** Dibatasi dan dialihkan oleh middleware `not.inventory` (tidak dapat mengakses modul Environment).

---

## 13. DEPENDENCY COMPOSER & KONFIGURASI YANG DIPERLUKAN

1. **PHP:** `^8.2`
2. **Laravel Framework:** `^11.0` atau `^12.0`
3. **Composer Packages:**
   - `barryvdh/laravel-dompdf: ^3.1` (Wajib untuk generate PDF Landscape).
4. **Frontend CDN / NPM Assets:**
   - Bootstrap 5 (CSS & JS Bundle)
   - Bootstrap Icons (`bi bi-*`)
   - Chart.js (Untuk grafik tren konsumsi energi pada Ringkasan Environment)

---

## 14. POTENSI KONFLIK SAAT DIPASANG KE PROJECT LAIN

1. **Bentrokan Nama Route:** Pastikan route prefix `environment` dan route name prefix `environment.*` belum digunakan modul lain.
2. **Bentrokan Nama Tabel:** Pastikan belum ada tabel dengan awalan `environment_*`. Jika sudah ada tabel bernama `environment_masters`, gunakan migrasi alter/penambahan kolom.
3. **Middleware Alias:** Project target harus memiliki alias middleware `not.inventory` dan `can.export` di `bootstrap/app.php`. Jika belum ada, buat atau sesuaikan dengan sistem auth project target.
4. **Blade Directives:** Pastikan directive `@adminManagement` dan `@canExportPdf` terdaftar di `AppServiceProvider.php` project target.
5. **Storage Symlink:** Pastikan perintah `php artisan storage:link` sudah dijalankan di project target agar lampiran dokumen dapat dibuka publik.

---

## 15. STRATEGI INTEGRASI TANPA MENGGANTI PROJECT TUJUAN

1. **Prinsip Modular Injection:** Hanya copy file-file spesifik modul Environment ke project target.
2. **Jangan Menimpa File Inti:** Jangan menimpa file `routes/web.php` target, melainkan *append* blok route Environment ke dalamnya.
3. **Jangan Menimpa Sidebar Target:** Selipkan grup menu Environment ke dalam `sidebar.blade.php` existing tanpa mengubah menu modul lain.
4. **Gunakan Environment Variables Existing:** Tidak mengubah file `.env` project target (konfigurasi database, app key, mail, dan app url tetap memakai yang ada).

---

## 16. STRATEGI MIGRATION DATABASE YANG AMAN

1. **Gunakan Guard Pemeriksaan Tabel:**
   Setiap migration wajib membungkus pembuatan tabel dengan `if (!Schema::hasTable('table_name'))`:
   ```php
   if (!Schema::hasTable('environment_masters')) {
       Schema::create('environment_masters', function (Blueprint $table) { ... });
   }
   ```
2. **Gunakan Seeder Idempotent:**
   Pengisian master data awal wajib memeriksa keberadaan record (`where(...)->exists()`) sebelum melakukan `insert`, sehingga tidak menghasilkan duplikasi jika migrasi dijalankan ulang.
3. **DILARANG KERAS:** Menjalankan `php artisan migrate:fresh`, `php artisan db:wipe`, atau `php artisan migrate:reset` di project target.

---

## 17. CHECKLIST PENGUJIAN PER SUBMENU

- [ ] **Ringkasan:** Halaman terbuka tanpa error, angka ringkasan teragregasi benar, chart energi merender 12 bulan, tombol Master Data membuka modal/halaman master.
- [ ] **Master Data:** Filter kategori berfungsi, tambah master satuan baru dengan mapping submodul berhasil, toggle status aktif/nonaktif berfungsi.
- [ ] **LB3:** Form input menampilkan satuan khusus LB3 (Ton, kg, Liter, Drum, Sak), checkbox karakteristik tersimpan sebagai array JSON, upload multi-file berhasil, export PDF terunduh rapi.
- [ ] **Non-LB3:** Input teks sumber limbah berhasil, validasi tanggal angkut >= tanggal masuk berjalan, multi-file lampiran tersimpan, export PDF berhasil.
- [ ] **RKL–RPL:** Dropdown parameter & lokasi terhubung ke master data, input nilai pengukuran teks (`< 0.05`) tersimpan sempurna, filter berfungsi, export PDF berhasil.
- [ ] **PPKH:** Dropdown Monitoring Lokasi menampilkan pilihan wajib **SK 2013** dan **SK 2024**, input aktivitas tersimpan, dokumen foto tersimpan, export PDF berhasil.
- [ ] **Energi (BBM & Listrik):** Tab BBM mengunci satuan Liter, Tab Listrik mengunci satuan kWh, validasi error muncul saat menginput bulan & tahun yang sama, grafik kalkulasi tahunan akurat, export PDF berhasil.
- [ ] **EWS:** Dropdown lokasi menampilkan `PAD9`, `PAD31`, `PAD7`, `PAD30`, input kondisi textarea tersimpan, dokumen log tersimpan, export PDF berhasil.

---

## 18. CHECKLIST REGRESSION TEST (MODUL EXISTING LAINNYA)

- [ ] **Daily Work:** Halaman utama, dual-tab (Hari Ini vs Semua), tombol selesaikan, dan filter perusahaan tetap berfungsi normal 100%.
- [ ] **Monitoring Anggaran:** Tabel kuning WBS, formula keuangan 7 indikator, pencatatan PO, termin pembayaran, dan pengalihan anggaran tetap berfungsi utuh.
- [ ] **Incident & Issue Follow Up:** Form pelaporan insiden dan logbook tindak lanjut tidak terganggu.
- [ ] **Gas Monitoring:** Pemantauan gas detector dan kalibrasi tetap berjalan lancar.
- [ ] **Autentikasi & Akun:** Login, logout, dan pergantian hak akses role user tidak terpengaruh.

---

## 19. LANGKAH IMPLEMENTASI BERTAHAP UNTUK ANTIGRAVITY

Saat mengintegrasikan modul ini ke project target menggunakan AI Antigravity, lakukan secara terurut:

1. **Fase 1: Database & Migrations**
   - Salin 3 migration file Environment ke folder `database/migrations/` project target.
   - Jalankan `php artisan migrate` (pastikan tanpa flag fresh/reset).
2. **Fase 2: Eloquent Models**
   - Salin ke-7 model Environment (`EnvironmentMaster`, `EnvironmentLb3Record`, `EnvironmentNonLb3Record`, `EnvironmentRklRplRecord`, `EnvironmentPpichRecord`, `EnvironmentEnergyRecord`, `EnvironmentEwsRecord`) ke `app/Models/`.
3. **Fase 3: Controller & Service Provider**
   - Salin `EnvironmentController.php` ke `app/Http/Controllers/Modules/`.
   - Pastikan Blade Directives `@adminManagement` dan `@canExportPdf` terdaftar di `app/Providers/AppServiceProvider.php`.
4. **Fase 4: Views & Assets**
   - Salin seluruh folder `resources/views/data/environment/` ke project target.
   - Salin seluruh file `resources/views/exports/environment-*.blade.php` ke project target.
   - Tambahkan blok menu Environment pada `resources/views/components/sidebar.blade.php`.
5. **Fase 5: Routes & Storage**
   - Sisipkan blok route Environment ke `routes/web.php`.
   - Jalankan `php artisan storage:link` dan pastikan folder storage memiliki izin tulis (*write permission*).
6. **Fase 6: Verifikasi & Testing**
   - Jalankan checklist pengujian per submenu dan regression test.

---

## 20. DAFTAR TEMUAN AUDIT & STATUS VERIFIKASI

Berdasarkan audit langsung terhadap seluruh file di codebase:
1. **Terverifikasi Penuh:**
   - Submenu PPKH sudah sepenuhnya mengimplementasikan acuan **`SK 2013`** dan **`SK 2024`** pada dropdown `monitoring_lokasi` dan pencatatan database.
   - Master lokasi EWS sudah terbukti memuat **`PAD9`**, **`PAD31`**, **`PAD7`**, dan **`PAD30`** melalui migration awal.
   - Pemetaan satuan per submodul sudah terbukti menggunakan kolom JSON `submodules` pada `environment_masters`.
   - Dropzone multi-file upload compact dengan tombol `+ Tambah Dokumen` sudah terpasang di seluruh form.
   - 7 Template Export PDF berorientasi Landscape A4 sudah terbukti lengkap di `resources/views/exports/`.
2. **Catatan Transisi / Legacy:**
   - Model untuk PPKH tetap menggunakan nama tabel `environment_ppich_records` dan model `EnvironmentPpichRecord.php` dengan alias route `/ppich` untuk menjaga kompatibilitas data historis yang sudah ada sebelumnya.
   - Kolom `source` pada `environment_non_lb3_records` kini berupa teks input bebas, namun relasi legacy `source_id` tetap dipertahankan sebagai nullable untuk integritas data lama.

