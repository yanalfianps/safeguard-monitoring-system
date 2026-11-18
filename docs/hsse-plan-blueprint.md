# 📋 MASTER PROMPT: SPESIFIKASI TEKNIS FITUR HSSE PLAN & KPI KONTRAKTOR

> **Petunjuk Penggunaan:** Gunakan dokumen blueprint arsitektur ini untuk meregenerasi modul **HSSE Plan** di framework Laravel + Blade / Vanilla JS, mencakup migrasi database, model Eloquent, kalkulasi matematis indikator kinerja, controller CRUD, manajemen catatan, upload bukti, rekapitulasi interaktif, hingga ekspor PDF.

---

## 1. DESKRIPSI & TUJUAN MODUL
Modul **HSSE Plan** adalah sistem manajemen dan evaluasi target kinerja Keselamatan, Kesehatan Kerja, dan Lingkungan (*Health, Safety, Security, and Environment*) untuk unit operasional maupun mitra kerja/kontraktor pelaksana. Sistem ini melacak pencapaian indikator kinerja secara terukur melalui:
1. Pemisahan indikator **Leading** (proaktif/pencegahan) dan **Lagging** (reaktif/kejadian insiden).
2. Tiga jenis frekuensi evaluasi: **Bulanan** (terisolasi per bulan), **Tahunan** (kumulatif *Year-to-Date*), dan **Per Project** (kumulatif rentang durasi proyek).
3. Evaluasi dinamis otomatis: persentase pemenuhan dan status (*Tercapai* vs *Belum Tercapai*).
4. Dual View: Dashboard Monitoring Utama (`/hsse-plan`) dan Halaman Khusus Riwayat Tercapai 100% (`/hsse-plan/riwayat-tercapai`).
5. Audit trail lengkap dan manajemen catatan evaluasi per KPI.

---

## 2. STRUKTUR DATABASE & SKEMA MIGRASI

### A. Tabel Utama: `hsse_plans`
| Kolom | Tipe Data | Keterangan / Aturan |
| :--- | :--- | :--- |
| `id` | `bigint unsigned` | Primary Key, Auto Increment |
| `title` | `varchar(255)` | Nama KPI / Program HSSE (Alias model: `name`) |
| `category` | `varchar(100)` | Kategori indikator: `'Leading'` atau `'Lagging'` (Alias: `type`) |
| `unit` | `varchar(50)` | Unit lokasi kerja (Default: `'Dieng Unit 1'`) |
| `contractor_id` | `bigint unsigned, nullable` | Foreign key relasi ke `contractors(id)` ON DELETE SET NULL |
| `contractor_name` | `varchar(255), nullable` | Nama kontraktor (misal: PJSE, JOTRE, Security) |
| `year` | `year / int` | Tahun evaluasi (Default tahun berjalan) (Alias: `period`) |
| `target` | `decimal(12,2)` | Nilai kuantitatif target (Default: 0) |
| `realization` | `decimal(12,2)` | Nilai realisasi terkini (Default: 0) (Alias: `actual`) |
| `unit_measure` | `varchar(50), nullable` | Satuan pengukuran (`%`, `kali/tahun`, `index`, `kasus`, dsb.) |
| `target_frequency`| `varchar(50)` | Pilihan: `'Bulanan'`, `'Tahunan'`, `'Per Project'` (Default: `'Tahunan'`) |
| `monthly_realizations` | `json, nullable` | Matriks realisasi bulanan: `{"2026-01": 5, "2026-02": 3, ...}` |
| `realization_history` | `json, nullable` | Array log riwayat: `[{"date":"2026-02-15","actual":5,"note":"...","doc":"bukti.pdf"}]` |
| `start_date` | `date, nullable` | Tanggal mulai periode / proyek |
| `end_date` | `date, nullable` | Tanggal akhir periode / proyek |
| `project_start_date` | `date, nullable` | Tanggal khusus durasi proyek (Alias: `project_start`) |
| `project_end_date` | `date, nullable` | Tanggal khusus berakhir proyek (Alias: `project_end`) |
| `status` | `varchar(50)` | `'Tercapai'`, `'Belum Tercapai'`, `'Berlangsung'`, `'Belum Mulai'` |
| `description` | `text, nullable` | Penjelasan/lingkup indikator kinerja |
| `timestamps` | `timestamp` | `created_at` & `updated_at` |

*Index: `['unit', 'year']`, `status`, `contractor_id`*

### B. Tabel Catatan Evaluasi: `hsse_plan_notes`
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `bigint unsigned` | Primary Key, Auto Increment |
| `hsse_plan_id` | `bigint unsigned` | FK relasi ke `hsse_plans(id)` ON DELETE CASCADE |
| `note` | `text` | Isi catatan evaluasi / rekomendasi tindak lanjut |
| `author` | `varchar(100), nullable`| Nama user pembuat catatan (Default: auth user name) |
| `timestamps` | `timestamp` | `created_at` & `updated_at` |

### C. Tabel Pendukung: `contractors`
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `bigint unsigned` | Primary Key, Auto Increment |
| `name` | `varchar(255)` | Nama instansi kontraktor (Unique) |
| `code` | `varchar(50), nullable` | Kode singkat kontraktor (Unique) |
| `description` | `text, nullable` | Keterangan bidang kerja kontraktor |
| `is_active` | `boolean` | Status keaktifan (Default: true) |
| `timestamps` | `timestamp` | `created_at` & `updated_at` |

---

## 3. LOGIKA BISNIS & PERHITUNGAN MATEMATIS (CORE LOGIC)

### A. Rumus Persentase Capaian
Sistem menghitung persentase capaian secara otomatis berdasarkan jenis indikator:

1. **Leading Indicator (Proaktif - Semakin besar semakin baik):**
   $$\text{Persentase} = \min\left(100, \max\left(0, \text{round}\left(\frac{\text{Realisasi}}{\text{Target}} \times 100\right)\right)\right)$$
   *(Jika Target $\le 0$, maka bernilai 100% jika Realisasi $\le 0$, atau 0% jika sebaliknya).*

2. **Lagging Indicator (Reaktif - Target ideal 0 insiden / kejadian minimal):**
   - Jika **Realisasi $\le 0$**: Persentase = **100%** (*Tercapai / Zero Accident*).
   - Jika **Realisasi $\ge$ Target** (atau Target $\le 0$ tapi Realisasi $> 0$): Persentase = **0%** (*Belum Tercapai / Melampaui batas bahaya*).
   - Jika Realisasi di antara $0$ dan Target (misal Target 2, Realisasi 1):
     $$\text{Persentase} = \min\left(100, \max\left(0, \text{round}\left(\left(1 - \frac{\text{Realisasi}}{\text{Target}}\right) \times 100\right)\right)\right)$$

### B. Perilaku Frekuensi Target (`target_frequency`)
1. **Bulanan**:
   - Realisasi bersifat independen per bulan kalender.
   - Mengambil nilai bulan bersangkutan dari field JSON `monthly_realizations["YYYY-MM"]`.
   - Mengalami reset tampilan setiap awal bulan baru dengan riwayat histori bulan sebelumnya tetap tersimpan utuh.
2. **Tahunan**:
   - Realisasi berjalan kumulatif (*Year-to-Date* / YTD) menjumlahkan Januari sampai bulan evaluasi pada tahun tersebut.
   - Memiliki method otomatisasi `ensureYearlyRollover()`: Ketika berganti tahun baru, KPI tahunan otomatis di-clone ke tahun baru dengan realisasi di-reset ke 0 dan tahun lama diarsipkan.
3. **Per Project**:
   - Realisasi dihitung kumulatif sepanjang rentang tanggal `project_start_date` sampai `project_end_date`.
   - **Aturan Visibilitas**: KPI Per Project hanya tampil pada tabel bila periode/bulan/tahun aktif beririsan (*overlap*) dengan rentang tanggal proyek.

### C. Penentuan Status Otomatis
- Jika Persentase $\ge 100\% \rightarrow$ **Tercapai**
- Jika Persentase $< 100\%$ dan Realisasi $> 0 \rightarrow$ **Berlangsung / Dalam Progress**
- Jika Realisasi $= 0$ dan Target belum terpenuhi $\rightarrow$ **Belum Tercapai / Belum Mulai**

---

## 4. STRUKTUR FORM INPUT & MODAL

### Modal 1: Tambah / Edit KPI (`#kpiModal`)
- **Nama KPI** (`title` / `name`): Text input, wajib diisi. Contoh: *Inspeksi Keselamatan Harian*, *Zero Accident Rate*.
- **Kontraktor Pelaksana** (`contractor_name` / `contractor_id`): Select option (`PJSE`, `JOTRE`, `Security`, dsb.).
- **Frekuensi Target** (`target_frequency`): Select option (`Tahunan`, `Bulanan`, `Per Project`).
- **Rentang Durasi Proyek** (`project_start_date` & `project_end_date`): Ditampilkan secara kondisional hanya jika frekuensi `Per Project` dipilih.
- **Nilai Target** (`target`): Number input (desimal step 0.01), wajib.
- **Satuan Pengukuran** (`unit_measure`): Text input (`%`, `kali/tahun`, `kasus`, `jam`).
- **Jenis KPI** (`category`): Toggle switch button styled (**Leading** [Biru] vs **Lagging** [Ungu]) disertai kotak petunjuk penjelasan indikator.
- **Keterangan Target** (`description`): Textarea opsional.

### Modal 2: Update Realisasi KPI (`#editRealisasiModal`)
- **Header Info Card**: Menampilkan Nama KPI, Target, Realisasi saat ini, dan Badge Frekuensi.
- **Nilai Realisasi Baru** (`realization`): Number input desimal.
- **Tanggal Update** (`history_date`): Date picker (default: hari ini).
- **Dokumen Bukti** (`history_doc`): File picker (PDF, JPG, PNG, DOC, max 5MB).
- **Keterangan / Catatan** (`history_note`): Textarea deskripsi progres.
- **Daftar Riwayat Update Realisasi**: Timeline visual (dot indicator + tanggal + nominal perubahan + catatan).
- **Anti-Duplikasi**: Tombol submit dilengkapi *loading state* (`isSubmittingRealisasi`) serta backend debounce 15 detik untuk mencegah double-post.

### Modal 3: Catatan Evaluasi & Tindak Lanjut (`#noteModal` & `#editNoteModal`)
- **KPI Terpilih**: Text readonly.
- **Catatan Tindak Lanjut** (`note`): Textarea (min 3 karakter, max 1000 karakter).
- **Penulis / PIC** (`author`): Text input opsional (default terisi nama user yang sedang login).
- Didukung aksi edit catatan di tempat dan hapus catatan via SweetAlert2 konfirmasi.

### Modal 4: Detail Lengkap KPI (`#kpiDetailModal`)
- Menampilkan ringkasan menyeluruh: badge status, jenis indikator, kontraktor, target kualitatif & kuantitatif, progress bar capaian, durasi rentang proyek, daftar lampiran dokumen bukti, log riwayat realisasi, dan feed percakapan/catatan tindak lanjut.
- Tautan tombol "Buka Halaman Penuh" menuju rute permalink `/hsse-plan/{id}`.

---

## 5. UI/UX: TABEL REKAPITULASI, FILTER & KONTROL

```
+---------------------------------------------------------------------------------------------------+
| [Icon] HSSE Plan  (Dashboard > HSSE Plan)                   [+ Tambah KPI]  [Export PDF]          |
+---------------------------------------------------------------------------------------------------+
| [Semua (12)]  |  [PJSE (5)]  |  [JOTRE (4)]  |  [Security (3)]                                    |
+---------------------------------------------------------------------------------------------------+
| Filter: [Tahun 2026 v] [Bulan: Semua v] [Jenis: Semua v] [Status: Semua v] [Cari nama KPI...     ] |
+---------------------------------------------------------------------------------------------------+
| [Total KPI: 12]     [Tercapai: 8]         [Belum Tercapai: 4]     [Leading/Lagging: 8 / 4]        |
+---------------------------------------------------------------------------------------------------+
| DAFTAR KPI KONTRAKTOR                                              [Filter Kategori: Semua v]     |
| +----+----------------------+---------+------------+--------+-----------+------------+--------+--+|
| | No | Nama KPI             | Jenis   | Frekuensi  | Target | Realisasi | Persentase | Status |..||
| +----+----------------------+---------+------------+--------+-----------+------------+--------+--+|
| | 1  | Inspeksi Harian      | Leading | Bulanan    | 30 kali| 28 kali   | [====] 93% | Prgs   |..||
| | 2  | Zero Fatality        | Lagging | Tahunan    | 0 kasus| 0 kasus   |[=====]100% | Selesai|..||
| +----+----------------------+---------+------------+--------+-----------+------------+--------+--+|
+---------------------------------------------------------------------------------------------------+
| DETAIL TERPILIH (3-Grid Bawah):                                                                   |
| [1. Ringkasan & Dokumen KPI]  |  [2. Riwayat Capaian Realisasi]  |  [3. Catatan Evaluasi & PIC]   |
+---------------------------------------------------------------------------------------------------+
```

### Komponen Tampilan Utama:
1. **Contractor Tabs**: Tab cepat di bagian paling atas untuk menyaring data per mitra kerja (PJSE, JOTRE, Security, Semua) dengan badge counter jumlah data.
2. **Filter Bar Komprehensif**:
   - Filter Tahun (mengambil daftar tahun unik dari database + tahun berjalan).
   - Filter Bulan (Januari s.d. Desember).
   - Filter Jenis (Leading vs Lagging).
   - Filter Status (Tercapai vs Belum Tercapai).
   - Input Live Search (mencari judul KPI, deskripsi, jenis, dan kontraktor).
3. **Statistik Cards**:
   - Total KPI
   - KPI Tercapai (Hijau)
   - KPI Belum Tercapai (Merah)
   - Rasio Leading / Lagging
4. **Tabel Rekapitulasi**:
   - Kolom: No, Nama KPI, Jenis, Frekuensi (Badge), Target, Realisasi, Persentase (Custom Bar warna hijau/kuning/merah), Status (Badge), Keterangan, dan Aksi.
   - Kolom Aksi: Tombol **Detail (Eye)**, **Edit Realisasi (Pencil)**, **Hapus (Trash)**.
   - Klik pada baris tabel akan mengaktifkan *highlight row* dan memperbarui data di Panel 3-Grid Bawah secara instan tanpa reload halaman.
5. **Panel 3-Grid Bawah**:
   - **Grid 1 (Kiri)**: Detail indikator, target, durasi proyek, dan daftar dokumen lampiran.
   - **Grid 2 (Tengah)**: Tabel riwayat capaian (Tanggal, Realisasi, Keterangan, File Bukti).
   - **Grid 3 (Kanan)**: List catatan tindak lanjut dengan informasi nama PIC dan timestamp, serta tombol `+ Tambah Catatan`.

---

## 6. ENDPOINT RUTE & OTORISASI (CONTROLLER & ROUTES)

Semua rute dibungkus middleware `auth`. Aksi modifikasi (Write) diproteksi role `admin` (`Admin Management`):

```php
Route::prefix('hsse-plan')->name('hsse-plan.')->middleware(['auth'])->group(function () {
    // Read & Export (Semua Role: Admin Management, Admin Inventory, User)
    Route::get('/', [HSSEPlanController::class, 'index'])->name('index');
    Route::get('/check-duplicate', [HSSEPlanController::class, 'checkDuplicate'])->name('check-duplicate');
    Route::get('/riwayat-tercapai', [HSSEPlanController::class, 'achieved'])->name('achieved');
    Route::get('/export', [HSSEPlanController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/{kpi}', [HSSEPlanController::class, 'show'])->name('show');
    Route::get('/{kpi}/notes', [HSSEPlanController::class, 'getNotes'])->name('notes.index');

    // Write Actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [HSSEPlanController::class, 'store'])->name('store');
        Route::put('/{kpi}', [HSSEPlanController::class, 'update'])->name('update');
        Route::delete('/{kpi}', [HSSEPlanController::class, 'destroy'])->name('destroy');

        // CRUD Catatan Evaluasi
        Route::post('/{kpi}/notes', [HSSEPlanController::class, 'storeNote'])->name('notes.store');
        Route::put('/{kpi}/notes/{note}', [HSSEPlanController::class, 'updateNote'])->name('notes.update');
        Route::delete('/{kpi}/notes/{note}', [HSSEPlanController::class, 'destroyNote'])->name('notes.destroy');
    });
});
```

### Penjelasan Method Controller Utama (`HSSEPlanController`):
- `index(Request $request)`: Menangani query filter tahun, bulan, kontraktor, jenis, dan frekuensi. Menghitung distribusi statistik dan memfilter visibilitas KPI *Per Project*. Mendukung response JSON untuk request AJAX.
- `achieved(Request $request)`: Menampilkan data khusus KPI yang sudah 100% tercapai (`where('status', 'Tercapai')`).
- `store(Request $request)`: Validasi data KPI baru, inisialisasi target & realisasi awal, pencatatan otomatis ke `activity_logs`.
- `update(Request $request, $id)`: Menerima input pembaruan profil KPI maupun update realisasi. Melakukan append ke JSON `monthly_realizations` dan `realization_history` dengan proteksi duplikasi data ganda.
- `destroy(Request $request, $id)`: Menghapus data KPI dan mencatat audit log.
- `storeNote()`, `updateNote()`, `destroyNote()`: Menangani siklus hidup catatan tindak lanjut.
- `export(Request $request)`: Menghasilkan dokumen laporan PDF landscape A4 via package `Barryvdh\DomPDF\Facade\Pdf` sesuai filter aktif.
- `checkDuplicate(Request $request)`: Endpoint AJAX untuk mendeteksi kesamaan nama KPI pada tahun dan kategori yang sama sebelum form disubmit.

---

## 7. AUDIT TRAIL & LOG AKTIVITAS
Setiap operasi Create, Update, dan Delete wajib dicatat ke tabel `activity_logs` dengan format:
- `module`: `'HSSE Plan'`
- `action`: `'TAMBAH'`, `'UBAH'`, `'HAPUS'`, `'TAMBAH_CATATAN'`, `'UBAH_CATATAN'`, `'HAPUS_CATATAN'`
- `description`: Ringkasan aksi (contoh: *"Memperbarui KPI: Inspeksi Harian (Realisasi: 28 / Target: 30)"*)
- `changes`: JSON diff data sebelum (`before`) dan sesudah (`after`) perubahan.
- `user_id`, `user_name`, `ip_address`, `user_agent`.

---

## 8. ATRIBUT & FITUR KHUSUS MODEL ELOQUENT (`HssePlan.php`)
Pastikan Model memiliki mutator, accessor, dan helper berikut:
1. **Alias Accessors**:
   - `name` $\leftrightarrow$ `title`
   - `type` $\leftrightarrow$ `category`
   - `actual` $\leftrightarrow$ `realization`
   - `period` $\leftrightarrow$ `year`
2. **Casting JSON**:
   - `'monthly_realizations' => 'array'`
   - `'realization_history' => 'array'`
3. **Helper Methods**:
   - `isProjectActiveInPeriod(?int $month, ?int $year, ?string $specificDate): bool`: Memeriksa apakah proyek aktif pada bulan/tahun yang dipilih.
   - `getRealizationForMonth(int $month, ?int $year): float`: Menarik nilai realisasi dari key JSON.
   - `calculateProgressForMonth(?int $month, ?int $year): array`: Menghasilkan array `['actual', 'percentage', 'status']`.
   - `ensureYearlyRollover(?int $currentYear): void`: Static method rollover arsip tahunan.

---

## 9. FITUR EKSPOR PDF (`hsse-plan-pdf.blade.php`)
- Output: Format PDF Landscape A4.
- Header: Kop surat resmi HSSE Manajemen, judul laporan, periode evaluasi, dan cakupan kontraktor.
- Kolom Tabel PDF: No, Program/KPI, Kategori & Frekuensi (termasuk tanggal proyek jika *Per Project*), Unit, Kontraktor, Tahun, Target, Realisasi, % Capaian, Status (badge styling), Catatan ringkas.
- Footer: Timestamp cetak dan tanda tangan verifikasi.
