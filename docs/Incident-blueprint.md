# 📋 MASTER PROMPT: SPESIFIKASI TEKNIS FITUR INCIDENT (KECELAKAAN & KEJADIAN HSSE)

> **Petunjuk Penggunaan:** Gunakan dokumen blueprint arsitektur ini untuk meregenerasi seluruh fitur modul **Incident** di framework Laravel + Blade / Vanilla JS / Alpine.js. Dokumen ini mencakup struktur skema database, migrasi, model Eloquent, controller CRUD, logika kalkulasi metrik & agregasi chart, sistem tagging ala LinkedIn "Add Skill", toggle biner klasifikasi area, drilldown modal grafik interaktif, modal investigasi, hingga ekspor PDF landscape.

---

## 1. IKHTISAR MODUL (EXECUTIVE OVERVIEW)

Modul **Incident** adalah modul pencatatan, investigasi, visualisasi, dan rekapitulasi data insiden kecelakaan kerja di lingkungan operasi geothermal (panas bumi) dan non-panas bumi. Modul ini memiliki identitas visual khas **Merah (#dc2626)** dengan fitur utama:
1. **Nomor Insiden Berurut Otomatis**: Format `INC-GDE-{TAHUN}-{00X}` (contoh: `INC-GDE-2026-001`).
2. **Klasifikasi Area Biner (Interactive Toggle Card)**: Membedakan tegas antara *Kecelakaan Panas Bumi* (Geothermal) vs *Kecelakaan Non-Panas Bumi*.
3. **Dual Multi-Tagging System (LinkedIn "Add Skill" Style)**:
   - **Kategori Umum**: 10 Tag risiko (Fatality, LTI, MTC, FAC, Property Damage, Near Miss, dll).
   - **Kategori EBTKE (Standar ESDM)**: 5 Kategori tingkat keparahan resmi (Kecelakaan Mati, Berat, Ringan, Kejadian Berbahaya, Penyakit Akibat Kerja).
4. **Auto-Sync Tanggal ke Nama Hari**: Mengisi otomatis dropdown `hari` (Senin–Minggu) saat `tanggal_waktu` dipilih via JavaScript.
5. **3 Donut Charts Interaktif dengan Drilldown Modal**:
   - Chart 1: Sebaran Kategori Umum (Warna kontras per tag).
   - Chart 2: Sebaran Kategori EBTKE (5 Tingkat keparahan).
   - Chart 3: Rasio Klasifikasi Insiden (Panas Bumi vs Non-Panas Bumi).
   - *Interactive Drilldown*: Setiap slice grafik dapat diklik untuk memunculkan modal daftar insiden terkait secara instan tanpa reload halaman.
6. **Indikator Hari Kerja Hilang (Lost Workdays / LWD)**: Menghitung total hari kerja hilang untuk indikator *Severity Rate* (LTI-SR).
7. **Lampiran Dokumentasi & Foto Bukti**: Mendukung upload berkas gambar/PDF investigasi ke storage publik.
8. **Export PDF Landscape**: Berdasarkan filter aktif pencarian, klasifikasi, tag, dan rentang tanggal.

---

## 2. SKEMA DATABASE & ARSITEKTUR DATA

### A. File Migrasi
- `database/migrations/2026_10_10_000008_create_incidents_table.php`
- `database/migrations/2026_10_13_130746_enhance_incidents_table_with_reference_fields.php`
- `database/migrations/2026_10_20_000004_add_hari_to_incidents_table.php`

### B. Kamus Data Tabel `incidents`

| Nama Kolom | Tipe Data | Nullable | Default | Keterangan / Fungsi |
| :--- | :--- | :---: | :--- | :--- |
| `id` | `bigint unsigned` | NO | Auto Increment | Primary Key |
| `nomor_insiden` | `varchar(255)` | YES | NULL | Nomor registrasi resmi (misal: `INC-GDE-2026-001`), bersifat `unique` |
| `incident_code` | `varchar(255)` | NO | - | Kolom kode warisan (sinkron dengan `nomor_insiden`), bersifat `unique` |
| `title` | `varchar(255)` | NO | - | Judul insiden singkat |
| `tanggal_waktu` | `datetime` | YES | NULL | Waktu kejadian insiden lengkap beserta jam:menit |
| `hari` | `varchar(20)` | YES | NULL | Nama hari kejadian (`Senin`, `Selasa`, `Rabu`, `Kamis`, `Jumat`, `Sabtu`, `Minggu`) |
| `incident_date` | `datetime` | NO | - | Sinkron dengan `tanggal_waktu` |
| `klasifikasi_area` | `varchar(100)` | YES | `'Kecelakaan Panas Bumi'` | Pilihan biner: `'Kecelakaan Panas Bumi'` / `'Kecelakaan Non-Panas Bumi'` |
| `incident_type` | `varchar(100)` | NO | - | Sinkron dengan `klasifikasi_area` |
| `kategori_umum` | `json` | YES | NULL | Array JSON tag kategori umum (multi-tag) |
| `kategori_ebtke` | `json` | YES | NULL | Array JSON tag kategori standar ESDM EBTKE (multi-tag) |
| `lokasi_kejadian` | `varchar(255)` | YES | NULL | Nama lokasi fisik (misal: `Wellpad 28`, `Area Power Plant Unit 1`) |
| `location` | `varchar(255)` | NO | - | Sinkron dengan `lokasi_kejadian` |
| `komponen_terkait` | `varchar(100)` | YES | NULL | Unit / Kontraktor (misal: `GDE`, `Utility`, `Kontraktor PT X`) |
| `unit` | `varchar(50)` | NO | - | Sinkron dengan `komponen_terkait` |
| `severity` | `varchar(50)` | NO | `'Low'` | Tingkat keparahan (`Low`, `Medium`, `High`, dll.) |
| `deskripsi_kejadian`| `text` | YES | NULL | Kronologi lengkap kejadian insiden |
| `description` | `text` | NO | - | Sinkron dengan `deskripsi_kejadian` |
| `immediate_action` | `text` | YES | NULL | Tindakan darurat sesaat setelah insiden |
| `root_cause` | `text` | YES | NULL | Akar penyebab insiden |
| `tindakan_perbaikan`| `text` | YES | NULL | Tindakan korektif / pencegahan (CAPA) |
| `corrective_action`| `text` | YES | NULL | Sinkron dengan `tindakan_perbaikan` |
| `penanggung_jawab` | `varchar(100)` | YES | NULL | Nama PIC penanggung jawab perbaikan |
| `reported_by` | `varchar(255)` | NO | - | Sinkron dengan `penanggung_jawab` |
| `target_penyelesaian`| `date` | YES | NULL | Deadline penyelesaian perbaikan/investigasi |
| `closed_at` | `date` | YES | NULL | Tanggal kasus dinyatakan resmi ditutup |
| `status_laporan` | `varchar(50)` | NO | `'Preliminary'` | Status laporan: `'Preliminary'` (Awal) atau `'Final'` (Selesai) |
| `status` | `enum` | NO | `'Dilaporkan'` | Nilai: `'Dilaporkan'`, `'Investigasi'`, `'Dalam Penanganan'`, `'Selesai'` |
| `jumlah_hari_hilang`| `unsigned int` | NO | `0` | Jumlah hari kerja hilang (Lost Workdays / LWD) |
| `dokumen_investigasi`| `varchar(255)` | YES | NULL | Path file bukti foto / PDF dokumen investigasi |
| `created_at` | `timestamp` | YES | NULL | Waktu dibuat |
| `updated_at` | `timestamp` | YES | NULL | Waktu terakhir diubah |

---

## 3. LOGIKA MODEL ELOQUENT (`Incident.php`)

File lokasi: `app/Models/Incident.php`

### A. Konstanta Baku
```php
public const KLASIFIKASI_AREAS = [
    'Kecelakaan Panas Bumi',
    'Kecelakaan Non-Panas Bumi',
];

public const KATEGORI_UMUM_LIST = [
    'Fatality',
    'LTI (Lost Time Injury)',
    'Medical Treatment Case',
    'First Aid Case',
    'Property Damage',
    'Near Miss (Hampir Celaka)',
    'Environmental Incident',
    'Vehicle Accident',
    'Fire Incident',
    'Hazard Report',
];

public const KATEGORI_EBTKE_LIST = [
    'Kecelakaan Mati',
    'Kecelakaan Berat',
    'Kecelakaan Ringan',
    'Kejadian Berbahaya',
    'Penyakit Akibat Kerja',
];
```

### B. Casts Array & Tanggal
```php
protected $casts = [
    'incident_date' => 'datetime',
    'tanggal_waktu' => 'datetime',
    'target_penyelesaian' => 'date',
    'closed_at' => 'date',
    'kategori_umum' => 'array',
    'kategori_ebtke' => 'array',
    'jumlah_hari_hilang' => 'integer',
];
```

### C. Generator Nomor Insiden Otomatis
```php
public static function generateNextNumber(): string
{
    $year = date('Y');
    $prefix = "INC-GDE-{$year}-";
    $latest = static::where('nomor_insiden', 'like', "{$prefix}%")
        ->orWhere('incident_code', 'like', "{$prefix}%")
        ->latest('id')
        ->first();

    if ($latest) {
        $num = $latest->nomor_insiden ?? $latest->incident_code;
        if (preg_match('/(\d+)$/', $num, $matches)) {
            $next = (int) $matches[1] + 1;
            return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
        }
    }

    return $prefix . '001';
}
```

### D. Dual-Field Auto-Synchronization (`booted()` lifecycle)
Agar selalu backward compatible, event `saving` melakukan sinkronisasi otomatis:
1. `nomor_insiden` $\leftrightarrow$ `incident_code` (Jika kosong, auto-generate).
2. `tanggal_waktu` $\leftrightarrow$ `incident_date` (Jika kosong, diisi waktu `now()`).
3. `lokasi_kejadian` $\leftrightarrow$ `location` (Default: `'Area Operasi Dieng'`).
4. `komponen_terkait` $\leftrightarrow$ `unit` (Default: `'Unit Dieng'`).
5. `deskripsi_kejadian` $\leftrightarrow$ `description`.
6. `penanggung_jawab` $\leftrightarrow$ `reported_by` (Default: User login).
7. `tindakan_perbaikan` $\leftrightarrow$ `corrective_action`.
8. `status_laporan` ('Preliminary' $\rightarrow$ 'Investigasi', 'Final' $\rightarrow$ 'Selesai').

---

## 4. SISTEM TAGGING & TOGGLE KLASIFIKASI KHUSUS

### A. Toggle Biner Klasifikasi Area
- Menggantikan radio button konvensional dengan **2 Kotak Pilihan Lebar (Grid 2 Kolom)**:
  - **Kiri**: *Kecelakaan Panas Bumi* (Icon api `bi-fire`, warna Oranye Panas `#ea580c`, background `#fff7ed`).
  - **Kanan**: *Kecelakaan Non-Panas Bumi* (Icon gedung `bi-building`, warna Biru Langit `#0284c7`, background `#f0f9ff`).
- Nilai disimpan ke hidden input `<input type="hidden" name="klasifikasi_area" :value="area">` yang dikontrol oleh Alpine.js.

### B. Sistem Tagging Ala "Add Skill" LinkedIn
- **Komponen**:
  1. **Tag Container (Kotak Terpilih)**: Berisi badge pill dengan tombol silang `(x)`. Mengirim array hidden input `<input type="hidden" name="kategori_umum[]" :value="tag">`.
  2. **Suggestion Pills (Pilihan Tersedia di Bawah)**: Kotak tag dengan border putus-putus. Ketika diklik, tag berpindah ke container terpilih. Jika sudah dipilih, pil otomatis berubah menjadi redup (opacity 0.45) dan tercoret (strikethrough).
- **Palet Warna Tag & Grafik**:
  - `Fatality`: Merah Crimson (`#b91c1c`)
  - `LTI (Lost Time Injury)`: Oranye Terang (`#ea580c`)
  - `Medical Treatment Case`: Pirus / Dark Teal (`#0d9488`)
  - `First Aid Case`: Hijau Zamrud (`#10b981`)
  - `Property Damage`: Biru Safir (`#2563eb`)
  - `Near Miss (Hampir Celaka)`: Golden Amber (`#d97706`)
  - `Environmental Incident`: Hijau Limau (`#65a30d`)
  - `Vehicle Accident`: Ungu Lembayung (`#7c3aed`)
  - `Fire Incident`: Merah Koral (`#f43f5e`)
  - `Hazard Report`: Biru Indigo (`#4338ca`)
  - Kategori EBTKE: Marun Gelap (`#7f1d1d`), Scarlet (`#dc2626`), Amber (`#f59e0b`), Royal Blue (`#1d4ed8`), Forest Emerald (`#059669`).

---

## 5. CONTROLLER ARCHITECTURE & LOGIC (`IncidentController.php`)

File lokasi: `app/Http/Controllers/Modules/IncidentController.php`

### A. Alur `index(Request $request)`
1. **Query Filter Generator**:
   - `search`: Pencarian teks di 10 kolom (`nomor_insiden`, `title`, `incident_code`, `lokasi_kejadian`, `komponen_terkait`, `penanggung_jawab`, `deskripsi_kejadian`, dll).
   - `klasifikasi_area`: Filter nilai tepat.
   - `kategori_umum`: Menggunakan `whereJsonContains('kategori_umum', $tag)` atau regex fallback.
   - `kategori_ebtke`: Menggunakan `whereJsonContains('kategori_ebtke', $tag)`.
   - `start_date` & `end_date`: Filter rentang waktu `whereBetween('tanggal_waktu', [startOfDay, endOfDay])`.
2. **Dual Dataset**:
   - **`$chartIncidents`**: Data unpaginated (`get()`) untuk kalkulasi akurat 3 donut charts, total hari hilang, dan modal drilldown.
   - **`$incidents`**: Data paginated 10 per halaman (`paginate(10)->withQueryString()`) untuk tabel daftar.
3. **Agregasi Metrik & Grafik**:
   - Menghitung frekuensi tag Kategori Umum & EBTKE.
   - Menghitung rasio Panas Bumi vs Non-Panas Bumi.
   - Menghitung Lost Workdays (LWD) & total kasus terkena dampak.
   - Menyusun `$chartDetailIncidents` (JSON) untuk kebutuhan klik grafik.
4. **Respon Ganda**:
   - Jika AJAX / request JSON $\rightarrow$ mengembalikan response JSON data chart.
   - Jika web normal $\rightarrow$ merender view `data.incident.index`.

### B. Alur `store(Request $request)`
1. **Validasi Input**:
   - `tanggal_waktu` (required, date), `hari` (nullable, string).
   - `klasifikasi_area`, `lokasi_kejadian`, `komponen_terkait`, `deskripsi_kejadian`, `status_laporan` (required).
   - `kategori_umum`, `kategori_ebtke` (nullable, array).
   - `jumlah_hari_hilang` (nullable, integer min 0).
   - `dokumen_investigasi` (nullable, file max 10240 KB).
2. **Upload Dokumen**:
   - Jika berupa gambar $\rightarrow$ diproses oleh `ImageUploadService` ke path `incidents/evidence`.
   - Jika dokumen lain (PDF/Doc) $\rightarrow$ disimpan via `$file->store('incidents/evidence', 'public')`.
3. **Penyimpanan Database**:
   - Dijalankan dalam `DB::transaction`.
4. **Audit Trail**:
   - Mencatat otomatis ke tabel `activity_logs` (`action = 'TAMBAH'`).
5. **Respon**:
   - Mengembalikan JSON sukses (jika AJAX) untuk diproses oleh SweetAlert2.

### C. Alur `update(Request $request, $id)` & `destroy($id)`
- `update()`: Memperbarui data, mengganti lampiran jika ada unggahan baru, mencatat log `'UBAH'`.
- `destroy()`: Menghapus record insiden, mencatat log `'HAPUS'`.

### D. Alur `export(Request $request)`
- Mengambil data insiden berdasarkan filter aktif di form index.
- Mengirim data ke view `exports.incident-pdf`.
- Menggunakan library `Barryvdh\DomPDF\Facade\Pdf`.
- Orientasi: **A4 Landscape**. Nama file otomatis: `Laporan-Incident-HSSE-YYYYMMDD-HHmmss.pdf`.

---

## 6. SPESIFIKASI ANTARMUKA PENGGUNA (FRONT-END & VIEW)

File lokasi: `resources/views/data/incident/index.blade.php`

### A. Struktur Halaman
1. **Page Header**:
   - Icon Box Merah: `<i class="bi bi-exclamation-octagon"></i>`.
   - Title: `Incident` & Subtitle `Pencatatan Insiden & Kecelakaan Kerja Lapangan`.
   - Tombol Aksi Kanan:
     - Tombol **Export PDF** (`incident.export` membawa parameter URL filter).
     - Tombol **+ Lapor Insiden Baru** (Membuka `#modalTambahIncident`, hak akses `@adminManagement`).
2. **Top Metrics: 3 Donut Charts Berdampingan (Grid 3 Kolom)**:
   - **Chart 1**: Kategori Umum (Total tag, plugin center text, clickable slice).
   - **Chart 2**: Kategori EBTKE (Total tag ESDM, legend di kanan, clickable slice).
   - **Chart 3**: Kategori Insiden (Panas Bumi vs Non-Panas Bumi, legend custom clickable).
3. **Filter Row Grid (6 Komponen Sejajar)**:
   - Input `search`: "Cari Insiden" (No. Insiden, Lokasi, PIC, Tag).
   - Select `kategori_umum`: Dropdown 10 tag umum.
   - Select `kategori_ebtke`: Dropdown 5 tag EBTKE.
   - Select `klasifikasi_area`: Dropdown Semua / Panas Bumi / Non-Panas Bumi.
   - Input Group Date `start_date` s/d `end_date`.
   - Tombol Aksi: `Filter` (Merah) & `Reset` (Icon Refresh).
4. **Tabel Rekapitulasi Insiden**:
   - **Kolom 1: No. Insiden | Judul**: Badge monospace merah + Judul tebal + Tanggal, Hari, dan Jam (WIB).
   - **Kolom 2: Kategori Umum**: Kumpulan badge pill warna-warni (Fatality di-highlight merah terang).
   - **Kolom 3: Kategori EBTKE**: Kumpulan badge pill EBTKE (Kecelakaan Mati di-highlight gelap).
   - **Kolom 4: Kategori Insiden**: Badge icon api (Panas Bumi) atau gedung (Non-Panas Bumi).
   - **Kolom 5: Lokasi**: Lokasi fisik + Sub-teks Komponen/Unit.
   - **Kolom 6: Status**: Badge `Preliminary` (Kuning) atau `Final` (Hijau).
   - **Kolom 7: Aksi**:
     - `Detail` (Icon Mata - Info): Menampilkan `#modalDetailIncident`.
     - `Edit` (Icon Pensil - Primary): Menampilkan `#modalEditIncident` (Hanya Admin).
     - `Hapus` (Icon Sampah - Danger): Menampilkan `#modalKonfirmasiHapus` (Hanya Admin).
5. **Pagination**: Bootstrap 5 Pagination di footer card.

### B. Daftar 5 Modal Interaktif
1. **Modal Tambah Insiden (`#modalTambahIncident`)**:
   - Ditenagai oleh Alpine.js `incidentFormHandler`.
   - Input Auto-Number (readonly).
   - Tagging container interaktif (Kategori Umum & EBTKE).
   - Toggle Biner Card (Panas Bumi vs Non-Panas Bumi).
   - Datetime picker dengan JS listener otomatis mengisi dropdown nama hari.
   - Input LWD (Jumlah Hari Hilang).
   - File drop upload (PDF/Foto bukti).
   - Submit AJAX dengan SweetAlert2 (Pop-up sukses reload otomatis).
2. **Modal Edit Insiden (`#modalEditIncident`)**:
   - Ditenagai oleh Alpine.js `incidentEditHandler`.
   - Mengisi otomatis seluruh input form, menyinkronkan array tag terpilih dan pilihan biner.
   - Menampilkan link dokumen aktif saat ini serta opsi mengganti file.
3. **Modal Detail Investigasi (`#modalDetailIncident`)**:
   - Menampilkan seluruh rincian insiden secara komprehensif (No. Insiden, Judul, Tanggal, Hari, Jam WIB, Lokasi, PIC, Hari Hilang, Deskripsi Kronologi, Tindakan Perbaikan, dan link unduh dokumen bukti).
4. **Modal Pop-Up Rincian Insiden dari Grafik (`#modalChartDetailIncidents`)**:
   - Terbuka otomatis ketika user mengklik sembarang irisan/slice pada 3 Donut Chart.
   - Menampilkan judul kategori yang diklik, jumlah total insiden, search filter internal modal, dan tabel seluruh insiden terkait lengkap dengan tombol buka detail.
5. **Modal Konfirmasi Hapus (`#modalKonfirmasiHapus`)**:
   - Pop-up konfirmasi berukuran compact (`modal-sm`) dengan icon peringatan, nomor insiden, dan lokasi sebelum aksi `DELETE` dieksekusi.

---

## 7. ROUTING & ACCESS CONTROL (`web.php`)

```php
Route::prefix('incident')->name('incident.')->middleware(['auth'])->group(function () {
    // Read & Export (Semua Role Terotentikasi)
    Route::get('/', [IncidentController::class, 'index'])->name('index');
    Route::get('/export', [IncidentController::class, 'export'])->name('export')->middleware('can.export');

    // Write actions (Hanya Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [IncidentController::class, 'store'])->name('store');
        Route::put('/{incident}', [IncidentController::class, 'update'])->name('update');
        Route::delete('/{incident}', [IncidentController::class, 'destroy'])->name('destroy');
    });
});
```

---

## 8. TEMPLATE EXPORT PDF (`exports/incident-pdf.blade.php`)

- **Layout**: Menurunkan `@extends('exports.layout')`.
- **Header PDF**: Memuat Logo Perusahaan, Judul *"LAPORAN REKAPITULASI INSIDEN & KEJADIAN HSSE"*, Filter Info (Klasifikasi Area, Kategori Umum, Kategori EBTKE, Periode, Pencarian), Nama Pencetak, dan Timestamp cetak.
- **Tabel Data PDF**:
  - Kolom: `No`, `No. Laporan`, `Tanggal/Waktu`, `Judul Insiden`, `Unit / Lokasi`, `Keparahan`, `Kategori`, `LWD`, `Status`.
  - Format kertas: `A4 Landscape`.

---

## 9. MASTER PROMPT SIAP COPY-PASTE UNTUK REGENERASI KODE

Gunakan blok prompt di bawah ini ke AI agent pada project master Anda untuk meregenerasi seluruh fitur ini:

```markdown
Tolong buatkan fitur lengkap menu "Incident" (Pencatatan Insiden & Kecelakaan Kerja HSSE) pada Laravel 11 / PHP 8.2 dengan spesifikasi teknis dan UI/UX modern berikut:

### 1. MODEL & DATABASE MIGRATION (`incidents` table)
- Kolom:
  - id, nomor_insiden (unique), incident_code (unique), title, tanggal_waktu (datetime), hari (varchar 20: Senin-Minggu), incident_date (datetime).
  - klasifikasi_area (varchar 100, default 'Kecelakaan Panas Bumi'): Pilihan biner antara 'Kecelakaan Panas Bumi' dan 'Kecelakaan Non-Panas Bumi'.
  - incident_type (sinkron dengan klasifikasi_area).
  - kategori_umum (json): Array tag kategori umum.
  - kategori_ebtke (json): Array tag standar ESDM EBTKE.
  - lokasi_kejadian & location, komponen_terkait & unit.
  - severity (default 'Low'), deskripsi_kejadian & description (text).
  - immediate_action, root_cause, tindakan_perbaikan & corrective_action (text).
  - penanggung_jawab & reported_by, target_penyelesaian (date), closed_at (date).
  - status_laporan (varchar: 'Preliminary' / 'Final'), status (enum: 'Dilaporkan', 'Investigasi', 'Dalam Penanganan', 'Selesai').
  - jumlah_hari_hilang (unsigned int default 0, indikator LTI-SR).
  - dokumen_investigasi (varchar path lampiran bukti foto/PDF).
  - timestamps.
- Model Incident:
  - Casts: datetime untuk tanggal_waktu, array untuk kategori_umum & kategori_ebtke, int untuk jumlah_hari_hilang.
  - Konstanta KATEGORI_UMUM_LIST: ['Fatality', 'LTI (Lost Time Injury)', 'Medical Treatment Case', 'First Aid Case', 'Property Damage', 'Near Miss (Hampir Celaka)', 'Environmental Incident', 'Vehicle Accident', 'Fire Incident', 'Hazard Report'].
  - Konstanta KATEGORI_EBTKE_LIST: ['Kecelakaan Mati', 'Kecelakaan Berat', 'Kecelakaan Ringan', 'Kejadian Berbahaya', 'Penyakit Akibat Kerja'].
  - Method `generateNextNumber()`: Format otomatis "INC-GDE-{YEAR}-{00X}".
  - Hook `booted() saving`: Lakukan dual-sync otomatis dua arah antara kolom modern (bahasa Indonesia) dan kolom legacy.

### 2. CONTROLLER (`IncidentController.php`)
- `index()`:
  - Filter parameter: `search`, `klasifikasi_area`, `kategori_umum` (whereJsonContains), `kategori_ebtke` (whereJsonContains), `start_date` & `end_date`.
  - Siapkan 2 query: `$incidents` (paginate 10 dengan query string) dan `$chartIncidents` (unpaginated untuk chart & metrik).
  - Hitung 3 konfigurasi Donut Chart:
    1. Chart Area: 'Kecelakaan Panas Bumi' (#ea580c) vs 'Kecelakaan Non-Panas Bumi' (#0284c7).
    2. Chart Umum: Sebaran 10 tag Kategori Umum dengan warna unik kontras (Fatality #b91c1c, LTI #ea580c, Near Miss #d97706, dll).
    3. Chart EBTKE: Sebaran 5 tag standar ESDM (#7f1d1d, #dc2626, #f59e0b, #1d4ed8, #059669).
  - Hitung metrik Hari Kerja Hilang (LWD) dan sebaran per bulan.
  - Buat data `$chartDetailIncidents` (JSON map) agar setiap slice grafik bisa diklik untuk drilldown modal.
  - Dukung return view dan JSON jika diminta via AJAX.
- `store()`: Validasi form, upload file lampiran bukti ke storage/incidents/evidence, simpan ke database dengan DB::transaction, catat ActivityLog (action: 'TAMBAH'), return JSON jika AJAX untuk SweetAlert2.
- `update()`: Update record, upload/replace file jika ada dokumen baru, catat ActivityLog (action: 'UBAH').
- `destroy()`: Hapus record, catat ActivityLog (action: 'HAPUS').
- `export()`: Mengambil data sesuai filter aktif, render PDF menggunakan Barryvdh\DomPDF landscape A4.

### 3. FRONT-END & UI/UX (`resources/views/data/incident/index.blade.php`)
- Tema Identitas: Merah Insiden (#dc2626).
- Header: Icon box merah, breadcrumbs, tombol Export PDF dan tombol "+ Lapor Insiden Baru".
- Bagian Atas: 3 Donut Charts (Chart.js) berdampingan: Kategori Umum, Kategori EBTKE, dan Kategori Insiden. Pasang plugin donut center text yang menampilkan total angka di tengah lingkaran donat. Pastikan setiap slice grafik memiliki event `onClick` yang memanggil modal interaktif `#modalChartDetailIncidents` untuk menampilkan daftar insiden pada kategori tersebut secara real-time.
- Baris Filter (Grid Sejajar): Cari Insiden, Dropdown Kategori Umum, Dropdown Kategori EBTKE, Dropdown Kategori Insiden, Date Range picker (start s/d end), Tombol Filter & Reset.
- Tabel Data Rekapitulasi:
  - Nomor Insiden (Badge monospace) + Judul Insiden + Hari, Tanggal, Jam WIB.
  - Tag Kategori Umum (Render pill badge).
  - Tag Kategori EBTKE (Render pill badge).
  - Klasifikasi Insiden (Badge icon api untuk Panas Bumi / gedung untuk Non-Panas Bumi).
  - Lokasi Kejadian & Komponen Unit.
  - Status Laporan: Preliminary (Kuning) atau Final (Hijau).
  - Kolom Aksi: Tombol Detail (Mata), Tombol Edit (Pensil), Tombol Hapus (Sampah).
  - Pagination Bootstrap 5.
- 5 Modals:
  1. Modal Tambah (Alpine.js): Input nomor auto-generated, judul, input tanggal_waktu dengan listener JS otomatis mengisi dropdown nama hari (Senin-Minggu), toggle biner card 2 kolom (Kecelakaan Panas Bumi vs Non-Panas Bumi), sistem tagging multi-select ala LinkedIn "Add Skill" (container badge terpilih + suggestion pill di bawah yang otomatis redup dan tercoret jika dipilih), deskripsi kejadian, tindakan perbaikan, LWD hari hilang, upload file bukti, dan AJAX submit dengan SweetAlert2.
  2. Modal Edit: Alpine.js sinkronisasi edit form, auto populate semua field, tag, dan dokumen aktif.
  3. Modal Detail: Layout informatif membaca seluruh kronologi, PIC, hari hilang, tag, dan tombol download dokumen investigasi.
  4. Modal Chart Drilldown (`#modalChartDetailIncidents`): Modal ukuran XL yang memuat filter live dan tabel detail insiden dari slice grafik yang diklik.
  5. Modal Hapus: Konfirmasi modal kecil sebelum submit method DELETE.

### 4. ROUTING
- Route prefix `incident`, name `incident.`, middleware auth, dan proteksi admin management untuk write actions (store, update, destroy).
```
