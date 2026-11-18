# 📋 MASTER PROMPT & SPESIFIKASI TEKNIS: MODUL DAILY WORK (PEKERJAAN HARIAN HSSE)

Dokumen ini berisi spesifikasi teknis lengkap, arsitektur sistem, aturan bisnis, skema basis data, dan master prompt dari modul **Daily Work (Pekerjaan Harian)** pada sistem HSSE Project. Dokumen ini dapat digunakan sebagai referensi implementasi maupun cetak biru (*blueprint*) untuk meregenerasi seluruh kode di proyek master.

---

## DAFTAR ISI
1. [Bagian I: Master Prompt untuk AI / Generator Kode](#bagian-i-master-prompt-untuk-ai--generator-kode)
2. [Bagian II: Arsitektur & Hak Akses (RBAC)](#bagian-ii-arsitektur--hak-akses-rbac)
3. [Bagian III: Skema Basis Data & Relasi Model](#bagian-iii-skema-basis-data--relasi-model)
4. [Bagian IV: 16 Perusahaan Wajib & Hierarki Induk-Subcon](#bagian-iv-16-perusahaan-wajib--hierarki-induk-subcon)
5. [Bagian V: Klasifikasi SIKA (Surat Izin Kerja Aman)](#bagian-v-klasifikasi-sika-surat-izin-kerja-aman)
6. [Bagian VI: Spesifikasi Form Input & Validasi](#bagian-vi-spesifikasi-form-input--validasi)
7. [Bagian VII: Logika Dua Tab (Hari Ini vs Semua Riwayat)](#bagian-vii-logika-dua-tab-hari-ini-vs-semua-riwayat)
8. [Bagian VIII: Filter, Pencarian, & Aturan Pengurutan Data](#bagian-viii-filter-pencarian--aturan-pengurutan-data)
9. [Bagian IX: Pengelolaan Master Data (Perusahaan & SIKA)](#bagian-ix-pengelolaan-master-data-perusahaan--sika)
10. [Bagian X: Visualisasi KPI, Chart, & Ekspor PDF](#bagian-x-visualisasi-kpi-chart--ekspor-pdf)
11. [Bagian XI: Routing & Controller Reference](#bagian-xi-routing--controller-reference)

---

## BAGIAN I: MASTER PROMPT UNTUK AI / GENERATOR KODE

> **Panduan:** Salin teks dalam blok kode di bawah ini untuk menginstruksikan AI / model bahasa dalam membangun kembali seluruh kode modul Daily Work secara komprehensif.

```markdown
Anda adalah Senior Full-Stack Engineer Laravel. Bangunlah modul "Daily Work (Pekerjaan Harian HSSE)" lengkap pada proyek Laravel dengan arsitektur bersih, aman, dan siap produksi.

### 1. RUANG LINGKUP & FITUR UTAMA
- Manajemen pekerjaan operasional harian di lingkungan kerja/industri dengan kontrol K3/HSSE.
- Role-Based Access Control (RBAC):
  * Admin Management: Memiliki hak penuh (Create, Read, Update, Delete, Complete, Kelola Master Perusahaan & SIKA, Ekspor PDF).
  * User (Pegawai): Read-only (Melihat data, filter, detail, dan Ekspor PDF).
  * Admin Inventory: Dilarang mengakses modul ini (dicegah middleware not.inventory).
- Dua Tab Tampilan:
  * Tab "Hari Ini": Menampilkan pekerjaan yang aktif/berjalan hari ini yang BELUM SELESAI. Terdapat tombol aksi centang "Pekerjaan Selesai".
  * Tab "Semua Riwayat": Menampilkan seluruh riwayat pekerjaan. Badge status "Selesai" dan tombol centang "Pekerjaan Selesai" TIDAK BOLEH muncul pada tab ini.
- Master Data Perusahaan dengan Hierarki Induk-Subcon (1 tingkat) dan 16 Perusahaan Wajib.
- Master Data SIKA (Surat Izin Kerja Aman) dengan dukungan multi-pilihan checkbox per pekerjaan.
- Filter Bar Lengkap: Filter Perusahaan Hierarkis, Filter SIKA, Rentang Tanggal (Tanggal Mulai & Tanggal Selesai dengan label jelas), Search Bar, dan Tombol Reset.
- Pengurutan Data Baku (Query Level): Urutan ke-1 Nama Perusahaan (A-Z), Urutan ke-2 Nama Pekerjaan (A-Z), Urutan ke-3 Tanggal Mulai terbaru, Urutan ke-4 ID Pekerjaan (DESC).
- KPI Cards & Pie Chart (Chart.js) Distribusi Pekerjaan per Perusahaan yang reaktif terhadap tanggal dan filter.
- Fitur Ekspor Laporan PDF (Landscape A4) via DomPDF dengan filter dinamis.
- Audit Log (ActivityLog) untuk setiap aksi penambahan, perubahan, dan penghapusan data.

### 2. STRUKTUR DATABASE & MODEL
1. Tabel `daily_works`:
   - id: bigint unsigned auto-increment primary key
   - name: varchar(255) [Nama Pekerjaan]
   - unit: varchar(100) [Nama Perusahaan / Tampilan histori]
   - company_id: foreign key nullable ke daily_work_companies(id) ON DELETE RESTRICT
   - category: varchar(100) default 'Umum'
   - ptw_classification: text nullable [Kompatibilitas lama]
   - sikka: text nullable [Daftar SIKA dipisahkan koma, misal: 'Ruang Terbatas, Ketinggian']
   - start_date: date [Tanggal Mulai]
   - end_date: date [Tanggal Selesai]
   - location: varchar(255) [Lokasi Pekerjaan]
   - executor: varchar(255) default '-' [Pelaksana]
   - status: enum('Belum Mulai', 'Berlangsung', 'Selesai', 'Ditunda') default 'Belum Mulai'
   - description: text nullable [Catatan/Rincian Kegiatan]
   - timestamps & indexes: index(unit, status), index(start_date, end_date)

2. Tabel `daily_work_companies`:
   - id: bigint unsigned auto-increment primary key
   - name: varchar(100) unique [Nama Perusahaan]
   - category: varchar(20) default 'independent' ('main', 'subcon', 'independent')
   - parent_company_id: foreign key nullable ke daily_work_companies(id) ON DELETE SET NULL
   - is_active: boolean default true
   - is_required: boolean default false [16 Perusahaan wajib tidak boleh dihapus]
   - sort_order: unsignedSmallInteger default 100
   - timestamps

3. Tabel `sikkas`:
   - id: bigint unsigned auto-increment primary key
   - name: varchar(100) unique [Nama Klasifikasi SIKA]
   - is_active: boolean default true
   - timestamps

### 3. ATURAN BISNIS & VALIDASI
1. Form Input Pekerjaan:
   - Nama Pekerjaan (required, max 255) + Ajax Duplicate Checker.
   - Perusahaan (required): Dropdown hierarkis; hanya perusahaan aktif yang diizinkan untuk data baru.
   - SIKA (opsional, multi-pilihan checkbox): Disimpan sebagai string koma terstandar (misal: "Ruang Terbatas, Ketinggian").
   - Tanggal Mulai (required, date).
   - Tanggal Selesai (required, date, after_or_equal:start_date).
   - Lokasi Pekerjaan (required, max 255).
   - Deskripsi (opsional, text).
   - STATUS TIDAK DIINPUT MANUAL: Sistem otomatis menetapkan status: jika end_date < hari_ini maka 'Selesai', selain itu 'Berlangsung'.
2. Selesaikan Pekerjaan (Mark as Completed):
   - Tombol centang hanya ada di Tab Hari Ini bagi pekerjaan yang belum berstatus 'Selesai'.
   - Begitu diklik dan dikonfirmasi, status diubah menjadi 'Selesai'. Pekerjaan langsung hilang dari Tab Hari Ini dan berpindah ke Tab Semua Riwayat.
3. Master Perusahaan:
   - 16 Perusahaan Wajib: GDE (All), GDE (Only), KODIPA, JOTRE (All), JOTRE (Only), PJSE, ADP, SOKKO, BISMO, WCP, TDP (BUJP), PJSE (Utility), Rentokil (Pest Control), PMC, Bhumi Phala (Maintenance EWS), Environesia.
   - Subcon memiliki induk langsung. Tidak boleh bertingkat (anak dari anak).
   - Jika Perusahaan Induk dinonaktifkan, seluruh subcon di bawahnya otomatis hilang dari filter dashboard.
   - Perusahaan wajib tidak bisa dihapus (hanya bisa dinonaktifkan). Perusahaan yang masih terikat dengan daily_works tidak boleh dihapus.
```

---

## BAGIAN II: ARSITEKTUR & HAK AKSES (RBAC)

Modul Daily Work menggunakan kontrol akses berbasis peran (*Role-Based Access Control*):

| Role Pengguna | Hak Akses Modul Daily Work |
|---|---|
| **Admin Management** (`admin_management`) | Akses penuh: Melihat, Menambah, Mengedit, Menghapus, Menandai Selesai, Mengelola Master Perusahaan & SIKA, dan Ekspor PDF. |
| **User / Pegawai** (`user`) | Akses baca (Read-only): Melihat dashboard, menggunakan filter, membuka modal detail pekerjaan, dan mengunduh laporan PDF. |
| **Admin Inventory** (`admin_inventory`) | Akses ditolak: Dibatasi hanya untuk modul inventaris/peminjaman barang melalui middleware `not.inventory`. |

---

## BAGIAN III: SKEMA BASIS DATA & RELASI MODEL

### 1. Tabel `daily_works`
Menyimpan seluruh data transaksi kegiatan operasional harian.

```sql
CREATE TABLE `daily_works` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Umum',
  `ptw_classification` text COLLATE utf8mb4_unicode_ci,
  `sikka` text COLLATE utf8mb4_unicode_ci,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `executor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '-',
  `status` enum('Belum Mulai','Berlangsung','Selesai','Ditunda') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Belum Mulai',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_works_unit_status_index` (`unit`,`status`),
  KEY `daily_works_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `daily_works_company_id_foreign` (`company_id`),
  CONSTRAINT `daily_works_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `daily_work_companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Tabel `daily_work_companies`
Menyimpan master data perusahaan beserta relasi hierarki induk-subcon.

```sql
CREATE TABLE `daily_work_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'independent',
  `parent_company_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint unsigned NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_work_companies_name_unique` (`name`),
  KEY `daily_work_companies_category_index` (`category`),
  KEY `daily_work_companies_parent_company_id_foreign` (`parent_company_id`),
  CONSTRAINT `daily_work_companies_parent_company_id_foreign` FOREIGN KEY (`parent_company_id`) REFERENCES `daily_work_companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Tabel `sikkas`
Menyimpan klasifikasi izin kerja aman yang dapat dipilih pada pekerjaan harian.

```sql
CREATE TABLE `sikkas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sikkas_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## BAGIAN IV: 16 PERUSAHAAN WAJIB & HIERARKI INDUK-SUBCON

Sistem memelihara 16 perusahaan standar yang dipastikan selalu tersedia (*auto-sync* via `DailyWorkCompany::ensureRequired()`):

```
├── GDE (All) [Perusahaan Utama / Induk]
│   ├── GDE (Only) [Subcon]
│   └── KODIPA [Subcon]
│
├── JOTRE (All) [Perusahaan Utama / Induk]
│   ├── JOTRE (Only) [Subcon]
│   ├── PJSE [Subcon]
│   ├── ADP [Subcon]
│   ├── SOKKO [Subcon]
│   ├── BISMO [Subcon]
│   └── WCP [Subcon]
│
├── TDP (BUJP) [Perusahaan Independen]
├── PJSE (Utility) [Perusahaan Independen]
├── Rentokil (Pest Control) [Perusahaan Independen]
├── PMC [Perusahaan Independen]
├── Bhumi Phala (Maintenance EWS) [Perusahaan Independen]
└── Environesia [Perusahaan Independen]
```

### Aturan Sinkronisasi Aktif / Nonaktif Induk-Subcon:
1. **Inherited Inactivity:** Jika perusahaan induk (contoh: `GDE (All)`) dinonaktifkan, seluruh subcon di bawahnya (`GDE (Only)` & `KODIPA`) otomatis **tidak ditampilkan** pada pilihan filter dashboard.
2. **Selective Inactivity:** Jika hanya salah satu subcon yang dinonaktifkan (misal `BISMO`), subcon lain dan induknya tetap tampil normal.
3. **Proteksi Integritas Data:** Perusahaan wajib (`is_required = true`) tidak dapat dihapus, hanya dapat dinonaktifkan.

---

## BAGIAN V: KLASIFIKASI SIKA (SURAT IZIN KERJA AMAN)

Pilihan standar klasifikasi izin kerja harian:
1. **Ruang Terbatas** (*Confined Space*)
2. **Ketinggian** (*Working at Height*)
3. **Penggalian** (*Excavation*)
4. **Listrik** (*Electrical Safety*)
5. **Isolasi Energi** (*Lockout / Tagout*)
6. **Lifting** (*Heavy Lifting*)
7. **Cold Work**
8. **Hot Work**

Pengguna dapat memilih **lebih dari satu SIKA** per pekerjaan (multi-select checkbox). Nilai disimpan dalam format string dipisahkan koma pada kolom `sikka` dan otomatis tersinkronisasi ke `ptw_classification`.

---

## BAGIAN VI: SPESIFIKASI FORM INPUT & VALIDASI

### 1. Form Tambah / Edit Pekerjaan Harian
Form disajikan dalam bentuk Modal Bootstrap (`#workModal`) dengan field berikut:

| No | Label Field | Tipe Komponen | Aturan Validasi Laravel | Keterangan & Perilaku UX |
|:---:|---|---|---|---|
| 1 | **Nama Pekerjaan** | `text` | `required\|string\|max:255` | Dilengkapi Ajax Duplicate Checker yang memeriksa potensi duplikasi nama pada perusahaan dan tanggal yang sama. |
| 2 | **Perusahaan** | Custom Select Popover | `required\|string\|max:100` | Pilihan terbagi per kelompok: *Perusahaan Utama*, *Subcon (Induk)*, dan *Perusahaan Independen*. Hanya perusahaan aktif yang dapat dipilih. |
| 3 | **SIKA** | Multi Checkbox | `nullable\|array/string` | Checkbox multi-pilihan opsional. Nilai otomatis digabung menjadi string koma (misal: `"Cold Work, Ketinggian"`). |
| 4 | **Tanggal Mulai** | `date` | `required\|date` | Default: Tanggal hari ini (`Asia/Jakarta`). |
| 5 | **Tanggal Selesai** | `date` | `required\|date\|after_or_equal:start_date` | Default: Tanggal hari ini. Validasi mencegah tanggal selesai lebih awal dari tanggal mulai. |
| 6 | **Lokasi Pekerjaan** | `text` | `required\|string\|max:255` | Titik area kerja fisik di lapangan (misal: *Wellpad 28*, *Area Workshop*). |
| 7 | **Deskripsi / Catatan**| `textarea` | `nullable\|string` | Rincian kegiatan pekerjaan operasional. |

### 2. Aturan Khusus Status & Lampiran File:
- **Tanpa Input Status Manual:** Pengguna tidak memilih status secara manual pada form. Sistem secara otomatis menghitung status:
  $$\text{status} = \begin{cases} \text{'Selesai'}, & \text{jika } \text{end\_date} < \text{today} \\ \text{'Berlangsung'}, & \text{lainnya} \end{cases}$$
- **Tanpa File Attachment:** Modul Daily Work secara desain berfokus pada kecepatan pencatatan operasional harian (*lightweight operations*) sehingga tidak membutuhkan unggah dokumen fisik. Histori dan integritas tercatat pada `activity_logs`.

---

## BAGIAN VII: LOGIKA DUA TAB (HARI INI VS SEMUA RIWAYAT)

### 1. Tab "Hari Ini" (`date_mode=today`)
- **Tujuan:** Pengawasan pekerjaan harian yang sedang berjalan.
- **Kriteria Query:** Hanya mengambil pekerjaan yang **belum selesai** (`status != 'Selesai'`) dan aktif pada hari berjalan (atau rentang tanggal filter).
- **Badge Status:** Menampilkan badge `Berlangsung`.
- **Tombol Centang (Pekerjaan Selesai):** **DITAMPILKAN** pada kolom aksi bagi pekerjaan yang belum selesai.
- **Perilaku Tombol Selesai:** Saat diklik, sistem mengeksekusi route `/daily-work/{id}/complete`, mengubah status menjadi `Selesai`, dan pekerjaan langsung hilang dari Tab Hari Ini lalu berpindah ke Tab Semua Riwayat.

### 2. Tab "Semua Riwayat" (`date_mode=all`)
- **Tujuan:** Rekapitulasi komprehensif, pencarian historis, dan pelaporan K3.
- **Kriteria Query:** Menampilkan seluruh pekerjaan tanpa batasan status.
- **Badge Status:** **BADGE STATUS "SELESAI" TIDAK DITAMPILKAN** di samping nama pekerjaan agar tabel riwayat tetap bersih dan tidak redundan.
- **Tombol Centang (Pekerjaan Selesai):** **TIDAK DITAMPILKAN**, baik untuk pekerjaan yang belum selesai maupun yang sudah selesai. Kolom aksi hanya berisi tombol Detail, Edit, dan Hapus.

---

## BAGIAN VIII: FILTER, PENCARIAN, & ATURAN PENGURUTAN DATA

### 1. Komponen Filter Bar
1. **Filter Perusahaan:** Dropdown hierarkis dengan input pencarian internal dan pemilihan subcon/induk.
2. **Filter SIKA:** Dropdown pemilihan jenis klasifikasi izin kerja.
3. **Filter Rentang Tanggal:**
   - Label di atas input 1: **Tanggal Mulai**
   - Pemisah: `s/d`
   - Label di atas input 2: **Tanggal Selesai**
4. **Pencarian Teks:** Mencocokkan nama pekerjaan, lokasi, perusahaan, SIKA, dan deskripsi secara instan.
5. **Tombol Reset:** Mengembalikan seluruh parameter ke kondisi awal.

### 2. Aturan Pengurutan Data (Baku di Database Query)
Pengurutan wajib dilakukan pada query SQL sebelum pagination:
```php
$works = $query
    ->orderBy('unit', 'asc')        // 1. Urutan utama: Nama Perusahaan A - Z
    ->orderBy('name', 'asc')        // 2. Urutan kedua: Nama Pekerjaan A - Z
    ->orderBy('start_date', 'desc') // 3. Urutan ketiga: Tanggal Mulai terbaru
    ->orderBy('id', 'desc')         // 4. Urutan penentu: ID pekerjaan terbaru
    ->get();
```

---

## BAGIAN IX: PENGELOLAAN MASTER DATA (PERUSAHAAN & SIKA)

Dikelola melalui modal `#masterModal` yang hanya dapat diakses oleh Admin Management:

### 1. Tab Master Perusahaan
- **Form Tambah/Edit Perusahaan:**
  1. *Nama Perusahaan* (Text, Max 100)
  2. *Memiliki Induk Perusahaan?* (Radio: `Tidak (Tingkat Utama)` / `Ya (Subcon)`)
  3. *Induk Perusahaan* (Dropdown hanya muncul jika opsi `Ya` dipilih)
  4. *Status* (Pilihan Aktif / Nonaktif)
- **Tabel Hierarki:**
  - Perusahaan Induk rata kiri dengan teks tebal.
  - Subcon menjorok ke kanan dengan garis indentasi visual yang bersih (tanpa kotak dekoratif menyerupai checkbox).
  - Terdapat tombol Toggle Status, Edit, dan Hapus.
- **Aturan Proteksi Hapus:**
  - Perusahaan wajib (`is_required`) tidak dapat dihapus.
  - Perusahaan yang sedang digunakan oleh data pekerjaan tidak dapat dihapus.
  - Perusahaan induk yang masih memiliki subcon tidak dapat dihapus.

### 2. Tab Master SIKA
- Input penambahan jenis izin SIKA baru secara cepat.
- Edit nama SIKA (otomatis memutakhirkan string nama pada data pekerjaan yang sudah ada).
- Toggle aktif/nonaktif dan hapus (dengan proteksi jika masih digunakan).

---

## BAGIAN X: VISUALISASI KPI, CHART, & EKSPOR PDF

### 1. Kartu Ringkasan (KPI Cards)
- **Card 1: Total Pekerjaan Hari Ini:** Menampilkan total pekerjaan aktif yang belum selesai pada tanggal terpilih. Klik untuk memfilter daftar hari ini.
- **Card 2: Perusahaan Terlibat:** Menampilkan jumlah perusahaan yang memiliki pekerjaan aktif hari ini. Klik untuk memunculkan modal daftar perusahaan yang terlibat beserta rincian jumlah pekerjaannya.

### 2. Diagram Lingkaran Distribusi Pekerjaan (Chart.js)
- Menampilkan persentase pembagian pekerjaan per perusahaan secara dinamis mengikuti tabel/filter yang aktif.
- Menampilkan pesan *empty state* yang rapi jika tidak ada pekerjaan pada periode terkait.

### 3. Ekspor PDF Laporan Harian & Riwayat (DomPDF)
- Template: [`resources/views/exports/daily-work-pdf.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/daily-work-pdf.blade.php)
- Format: Dokumen A4 Landscape dengan tabel data, informasi periode filter, nomor halaman, dan stempel waktu ekspor.

---

## BAGIAN XI: ROUTING & CONTROLLER REFERENCE

### Route Definition (`routes/web.php`):
```php
Route::prefix('daily-work')->name('daily-work.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Admin Management & User)
    Route::get('/', [DailyWorkController::class, 'index'])->name('index');
    Route::get('/check-duplicate', [DailyWorkController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::get('/export', [DailyWorkController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/masters/all', [DailyWorkController::class, 'getMasters'])->name('masters.index');
    Route::get('/{daily_work}', [DailyWorkController::class, 'show'])->name('show');

    // Write Actions (Khusus Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [DailyWorkController::class, 'store'])->name('store');
        Route::put('/{daily_work}', [DailyWorkController::class, 'update'])->name('update');
        Route::delete('/{daily_work}', [DailyWorkController::class, 'destroy'])->name('destroy');
        Route::post('/{daily_work}/complete', [DailyWorkController::class, 'complete'])->name('complete');

        // Master Data Perusahaan
        Route::post('/masters/companies', [DailyWorkController::class, 'storeCompany'])->name('masters.companies.store');
        Route::put('/masters/companies/{id}', [DailyWorkController::class, 'updateCompany'])->name('masters.companies.update');
        Route::delete('/masters/companies/{id}', [DailyWorkController::class, 'destroyCompany'])->name('masters.companies.destroy');
        Route::post('/masters/companies/{id}/toggle', [DailyWorkController::class, 'toggleCompany'])->name('masters.companies.toggle');

        // Master Data SIKA
        Route::post('/masters/sika', [DailyWorkController::class, 'storeSikka'])->name('masters.sika.store');
        Route::put('/masters/sika/{id}', [DailyWorkController::class, 'updateSikka'])->name('masters.sika.update');
        Route::delete('/masters/sika/{id}', [DailyWorkController::class, 'destroySikka'])->name('masters.sika.destroy');
        Route::post('/masters/sika/{id}/toggle', [DailyWorkController::class, 'toggleSikka'])->name('masters.sika.toggle');
    });
});
```

### File Terkait pada Project:
- **Model:**
  - [`app/Models/DailyWork.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/DailyWork.php)
  - [`app/Models/DailyWorkCompany.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/DailyWorkCompany.php)
  - [`app/Models/Sikka.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/Sikka.php)
- **Controller:**
  - [`app/Http/Controllers/Modules/DailyWorkController.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Http/Controllers/Modules/DailyWorkController.php)
- **Views:**
  - [`resources/views/daily-work/index.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/daily-work/index.blade.php)
  - [`resources/views/daily-work/show.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/daily-work/show.blade.php)
  - [`resources/views/exports/daily-work-pdf.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/daily-work-pdf.blade.php)
- **Migrations:**
  - `database/migrations/2026_10_10_000001_create_daily_works_table.php`
  - `database/migrations/2026_10_15_140000_create_daily_work_masters_and_enhance_daily_works.php`
  - `database/migrations/2026_10_20_000001_merge_ptw_into_sika_in_daily_works.php`
  - `database/migrations/2026_10_22_000001_add_hierarchy_to_daily_work_companies.php`
  - `database/migrations/2026_10_22_000002_allow_multiple_sika_in_daily_works.php`
- **Unit & Feature Tests:**
  - `tests/Feature/DailyWork/DailyWorkManagementTest.php`
  - `tests/Feature/DailyWork/DailyWorkParentAndCompleteFeatureTest.php`
  - `tests/Feature/DailyWork/DailyWorkCompanyMasterTest.php`
  - `tests/Feature/DailyWork/DailyWorkCompanyFilterActiveSyncTest.php`
  - `tests/Feature/DailyWork/DailyWorkViewAndFilterImprovementsTest.php`
  - `tests/Feature/DailyWork/DailyWorkSikaFeatureTest.php`

