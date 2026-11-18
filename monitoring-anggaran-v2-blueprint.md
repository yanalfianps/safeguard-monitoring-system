# MASTER PROMPT & SPESIFIKASI TEKNIS: MODUL MONITORING ANGGARAN (HSSE)

```text
================================================================================
MASTER PROMPT: GENERATE MODUL MONITORING ANGGARAN LENGKAP (LARAVEL 11 + BLADE + MYSQL)
================================================================================

Role: Senior Full-Stack Laravel Architect & Enterprise Financial Systems Engineer.
Task: Bangun ulang seluruh modul "Monitoring Anggaran" (HSE & Safeguard Budget Monitoring) 
      secara menyeluruh, kokoh, transaksional (DB Transaction), dan terintegrasi penuh 
      sesuai spesifikasi arsitektur teknis di bawah ini.
Framework: Laravel 11/12, Blade Templates, Bootstrap 5 / Tailwind CSS, Vanilla JS, 
           DomPDF (barryvdh/laravel-dompdf), MySQL.
```

---

## 1. DESKRIPSI & TUJUAN BISNIS MODUL
Modul **Monitoring Anggaran (HSE & Safeguard)** dirancang untuk mengelola tata kelola pagu anggaran berbasis **WBS (Work Breakdown Structure)**, penerbitan kontrak/komitmen pengadaan (**PO - Purchase Order**), termin realisasi pembayaran PO (**PO Payments**), belanja operasional langsung tanpa PO (**Non-PO Expenses**), pengalihan dana antar-WBS (**Budget Transfer**), serta penyesuaian pagu (**Budget Adjustment**).

Sistem menerapkan prinsip akuntansi komitmen (*commitment accounting*) di mana penerbitan PO langsung mengikat anggaran, sehingga pembayaran termin berikutnya tidak lagi mengurangi pagu WBS yang sama (mencegah *double-counting deduction*).

---

## 2. 7 INDIKATOR KEUANGAN & FORMULA PERHITUNGAN (FINANCIAL INVARIANTS)

Sistem menggunakan formula standar keuangan enterprise:

| Indikator | Label Sistem | Formula & Definisi |
|---|---|---|
| **1. Budget** | `current_budget` | Pagu anggaran WBS aktif setelah memperhitungkan penyesuaian (adjustment) dan pengalihan (transfer masuk/keluar). |
| **2. Total PO** | `total_po_value` | Nilai total seluruh Purchase Order aktif yang diterbitkan pada WBS terkait (`status != 'Dibatalkan'`). |
| **3. Commitment** | `remaining_po_commitment` | Sisa kewajiban komitmen PO yang belum dibayarkan.<br>`Commitment = max(0, Total PO - PO Expenses)` |
| **4. PO Expenses** | `po_realization` | Akumulasi seluruh realisasi pembayaran termin PO yang telah dibayarkan ke vendor. |
| **5. Non-PO Expenses** | `non_po_realization` | Akumulasi seluruh pengeluaran langsung operasional tanpa PO. |
| **6. Assigned** | `allocated_budget` | Total anggaran yang telah terikat/teralokasi.<br>`Assigned = Total PO + Non-PO Expenses` *(Pembayaran PO tidak mengurangi anggaran lagi karena nilai PO telah terhitung penuh pada Assigned).* |
| **7. Available** | `remaining_budget` | Sisa pagu anggaran bebas yang masih dapat digunakan untuk membuat PO atau Non-PO baru.<br>`Available = Budget - Assigned` |

### Ringkasan Kartu Dashboard Utama:
Di halaman indeks utama, sistem menampilkan 3 Kartu Metrik Utama:
1. **Budget (Total Pagu)**: Berwarna Teal (`#0f766e`)
2. **Assigned (Teralokasi)**: Berwarna Indigo (`#4f46e5`)
3. **Available (Sisa Saldo)**: Berwarna Emerald Green (`#059669`) atau Red jika minus (`#dc2626`)

Di halaman detail per WBS (`show-wbs.blade.php`), sistem menampilkan seluruh **7 Indikator Keuangan WBS** dalam grid responsif.

---

## 3. SKEMA BASIS DATA & MIGRATIONS

### A. Tabel `budget_wbs` (Master WBS & Pagu)
```sql
CREATE TABLE `budget_wbs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wbs_number` VARCHAR(50) NOT NULL UNIQUE, -- e.g. "WBS-HSE-2026-001"
  `wbs_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `initial_budget` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `current_budget` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `fiscal_year` INT NOT NULL, -- e.g. 2026
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_wbs_year_active` (`fiscal_year`, `is_active`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### B. Tabel `budget_pos` (Pengadaan / Kontrak PO)
```sql
CREATE TABLE `budget_pos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `budget_wbs_id` BIGINT UNSIGNED NOT NULL,
  `po_number` VARCHAR(100) NOT NULL UNIQUE, -- e.g. "PO-HSE-2026-0001"
  `program_name` VARCHAR(255) NOT NULL,
  `vendor` VARCHAR(255) NULL,
  `po_value` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `commitment_value` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `po_date` DATE NOT NULL,
  `status` ENUM('Aktif', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Aktif',
  `description` TEXT NULL,
  `attachment_path` VARCHAR(255) NULL, -- legacy single-file fallback
  `attachment_paths` JSON NULL, -- modern multi-file JSON array
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_po_date` (`po_date`),
  FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### C. Tabel `budget_po_payments` (Termin Pembayaran PO)
```sql
CREATE TABLE `budget_po_payments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `budget_po_id` BIGINT UNSIGNED NOT NULL,
  `budget_wbs_id` BIGINT UNSIGNED NOT NULL,
  `payment_number` VARCHAR(100) NOT NULL UNIQUE, -- e.g. "PAY-PO-2026-0001"
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `reference_number` VARCHAR(100) NULL, -- No Invoice / Kwitansi Vendor
  `description` TEXT NULL,
  `proof_path` VARCHAR(255) NULL,
  `proof_paths` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`budget_po_id`) REFERENCES `budget_pos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### D. Tabel `budget_non_pos` (Realisasi Belanja Non-PO)
```sql
CREATE TABLE `budget_non_pos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `budget_wbs_id` BIGINT UNSIGNED NOT NULL,
  `transaction_number` VARCHAR(100) NOT NULL UNIQUE, -- e.g. "NPO-2026-0001"
  `program_name` VARCHAR(255) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `recipient_vendor` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `proof_path` VARCHAR(255) NULL,
  `proof_paths` JSON NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### E. Tabel `budget_transfers` (Pengalihan Anggaran Antar-WBS)
```sql
CREATE TABLE `budget_transfers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transfer_number` VARCHAR(100) NOT NULL UNIQUE, -- e.g. "TRF-2026-0001"
  `source_wbs_id` BIGINT UNSIGNED NOT NULL,
  `destination_wbs_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `transfer_date` DATE NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('Disetujui', 'Draf', 'Ditolak') NOT NULL DEFAULT 'Disetujui',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`source_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`destination_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### F. Tabel `budget_adjustments` (Revisi Pagu Anggaran WBS)
```sql
CREATE TABLE `budget_adjustments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `adjustment_number` VARCHAR(100) NOT NULL UNIQUE, -- e.g. "ADJ-2026-0001"
  `budget_wbs_id` BIGINT UNSIGNED NOT NULL,
  `adjustment_type` ENUM('TAMBAH', 'KURANG') NOT NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `old_budget` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `new_budget` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `adjustment_date` DATE NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### G. Tabel `budget_transactions` (Buku Besar Audit Transaksi Keuangan)
```sql
CREATE TABLE `budget_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transaction_number` VARCHAR(100) NOT NULL UNIQUE, -- e.g. "TRX-2026-00001"
  `transaction_type` VARCHAR(50) NOT NULL, 
  -- ('PO_COMMITMENT', 'PO_PAYMENT', 'NON_PO', 'TRANSFER_IN', 'TRANSFER_OUT', 'ADJUSTMENT')
  `transaction_type_label` VARCHAR(100) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `budget_wbs_id` BIGINT UNSIGNED NOT NULL,
  `program_name` VARCHAR(255) NULL,
  `reference_number` VARCHAR(100) NULL,
  `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `description` TEXT NULL,
  `related_model_type` VARCHAR(150) NULL,
  `related_model_id` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_trx_wbs_date` (`budget_wbs_id`, `transaction_date`),
  FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

---

## 4. VALIDASI TRANSAKSI & LOGIKA BISNIS KETAT

Semua operasi modifikasi wajib dibungkus dalam `DB::transaction()` dengan aturan validasi berikut:

1. **Pembuatan PO Baru (`storePo`):**
   - Nilai PO tidak boleh melebihi sisa anggaran WBS (`po_value <= wbs->remaining_budget`).
   - Otomatis mencatat 1 baris ke `budget_transactions` dengan tipe `PO_COMMITMENT`.
2. **Pencatatan Pembayaran PO (`storePoPayment`):**
   - Nilai pembayaran tidak boleh melebihi sisa komitmen PO (`amount <= po->remaining_commitment`).
   - Jika total pembayaran telah mencapai nilai PO (`total_payment >= po_value`), status PO otomatis diubah menjadi `Selesai`.
   - Otomatis mencatat 1 baris ke `budget_transactions` dengan tipe `PO_PAYMENT`.
3. **Pencatatan Non-PO (`storeNonPo`):**
   - Nominal Non-PO tidak boleh melebihi sisa anggaran WBS (`amount <= wbs->remaining_budget`).
   - Otomatis mencatat 1 baris ke `budget_transactions` dengan tipe `NON_PO`.
4. **Pengalihan Anggaran Antar-WBS (`storeTransfer`):**
   - WBS asal tidak boleh sama dengan WBS tujuan (`source_wbs_id != destination_wbs_id`).
   - Nilai transfer tidak boleh melebihi sisa anggaran bebas WBS asal (`amount <= source_wbs->remaining_budget`).
   - Mengurangi `current_budget` WBS Asal dan Menambah `current_budget` WBS Tujuan secara atomik.
   - Otomatis mencatat 2 baris transaksi: `TRANSFER_OUT` pada WBS Asal, dan `TRANSFER_IN` pada WBS Tujuan.
5. **Penyesuaian Anggaran WBS (`adjustBudget`):**
   - Jika tipe `KURANG`: Pengurangan pagu tidak boleh mengakibatkan pagu baru lebih kecil dari anggaran yang sudah teralokasi (`new_budget >= wbs->allocated_budget`).
   - Memperbarui `current_budget` pada WBS dan mencatat riwayat ke `budget_adjustments` dan `budget_transactions` (`ADJUSTMENT`).
6. **Proteksi Penghapusan Data (Guard Integrity):**
   - WBS yang memiliki PO, Non-PO, atau Transfer tidak boleh dihapus sembarangan.
   - PO yang sudah memiliki catatan termin pembayaran tidak dapat dihapus (`!$po->payments()->exists()`); harus menghapus pembayaran-pembayarannya terlebih dahulu.

---

## 5. MEKANISME UPLOAD DOKUMEN MULTI-FILE (COMPACT & ANTI-OVERWRITE)

Sistem upload dokumen dirancang dengan standar UI/UX modern:
- **Tinggi Dropzone Ringkas:** Tinggi dropzone ~80–105px dengan background netral `#f8fafc` dan border tipis putus-putus `#cbd5e1`.
- **Dukungan Multi-Folder Tanpa Menimpa:** Menggunakan tombol `+ Tambah Dokumen` yang men-generate input file dinamis baru (`attachments[]` / `proofs[]`). Pengguna dapat memilih file dari folder A, lalu menekan tombol untuk memilih file dari folder B tanpa menghilangkan file folder A.
- **Daftar Pratinjau Terpadu (Unified File List):** Nama file hanya muncul 1 kali di container daftar file bawah dropzone, dilengkapi ukuran file, icon format, dan tombol hapus individu.
- **Format & Ukuran:**
  - Dokumen Kontrak PO: Max 10MB per file (PDF, JPG, JPEG, PNG, DOC, DOCX). Folder: `storage/app/public/budget/po_attachments/`.
  - Bukti Pembayaran PO & Non-PO: Max 5MB per file (PDF, JPG, JPEG, PNG). Folder: `storage/app/public/budget/payment_proofs/` dan `budget/non_po_proofs/`.
- **Hybrid Storage Schema:**
  - Disimpan sebagai array JSON pada kolom `attachment_paths` / `proof_paths`.
  - Kolom string `attachment_path` / `proof_path` tetap diisi elemen pertama untuk kompatibilitas data legacy.
  - Model Eloquent menyediakan accessor array murni: `$po->attachment_list` dan `$nonPo->proof_list`.
  - Form Edit mendukung array `deleted_attachments[]` / `deleted_proofs[]` untuk menghapus file fisik di storage secara aman.

---

## 6. STRUKTUR FORM INPUT & MODAL

| Nama Modal | Tujuan | Field Input & Aturan |
|---|---|---|
| `#modalTambahWbs` | Menambah WBS Baru | Nomor WBS (Auto `WBS-HSE-YYYY-XXX`), Nama WBS (*req*), Pagu Awal (*req, min: 1*), Tahun Anggaran (*req, default current year*), Deskripsi (*opsional*). |
| `#modalEditWbs` | Mengubah Data Master WBS | Nomor WBS (readonly), Nama WBS (*req*), Tahun Anggaran (*req*), Deskripsi (*opsional*). |
| `#modalAdjustWbs` | Penyesuaian Pagu WBS | Pagu Saat Ini (display), Jenis Penyesuaian (`TAMBAH` / `KURANG`), Nominal Perubahan (*req, min: 1*), Alasan Perubahan (*req*). |
| `#modalTambahPo` | Catat Kontrak Pengadaan | WBS Terkait (hidden/select), Nomor PO (Auto `PO-HSE-YYYY-XXXX`), Tanggal PO (*req*), Nama Pengadaan/Program (*req*), Nama Vendor (*opsional*), Nilai PO (*req, max: remaining_budget*), Keterangan (*opsional*), Multi-File Dropzone Dokumen Kontrak. |
| `#modalEditPo` | Edit Pengadaan | Nilai PO Baru (*min: total pembayaran yang sudah dilakukan, max: available + old_po_value*), Vendor, Keterangan, Manajemen Dokumen Existing & Tambah Dokumen Baru. |
| `#modalTambahPoPayment` | Catat Termin Bayar PO | Pilih PO (*req, menampilkan sisa komitmen*), Nomor Pembayaran (Auto `PAY-PO-YYYY-XXXX`), Tanggal Bayar (*req*), Jumlah Bayar (*req, max: remaining_commitment*), No Referensi/Kwitansi (*opsional*), Keterangan, Multi-File Bukti Bayar. |
| `#modalTambahNonPo` | Catat Pengeluaran Langsung | WBS Terkait (*req*), Nomor Transaksi (Auto `NPO-YYYY-XXXX`), Tanggal (*req*), Nama Program/Uraian (*req*), Vendor/Penerima (*opsional*), Jumlah (*req, max: remaining_budget*), Keterangan, Multi-File Bukti Bayar. |
| `#modalTransferAnggaran` | Pengalihan Antar-WBS | WBS Asal (*req, menampilkan sisa saldo*), WBS Tujuan (*req, != WBS Asal*), Nomor Pengalihan (Auto `TRF-YYYY-XXXX`), Tanggal (*req*), Jumlah Pengalihan (*req, max: source remaining_budget*), Alasan Pengalihan (*req*). |

---

## 7. ARSITEKTUR TABEL REKAPITULASI, TAB, & FILTER

### Tab Navigasi Halaman Utama (`?tab=...`):
1. **Tab `wbs` (Default):**
   - Tabel Kuning Konsep Excel (`table-excel-yellow`): No, Nomor WBS, Nama WBS / Program, Budget, Total PO, Commitment, PO Expenses, Non-PO Expenses, Assigned, Available, Aksi.
   - Baris Total Keseluruhan di `tfoot` dengan kalkulasi kumulatif seluruh kolom moneter.
   - Filter: Dropdown Tahun Anggaran (`fiscal_year`) dan Pencarian Teks (`search_wbs`).
2. **Tab `po`:** Daftar seluruh PO dengan rincian nilai komitmen, total terbayar, sisa kewajiban, status badge, dan tautan lampiran file.
3. **Tab `realisasi-po`:** Rekapitulasi seluruh pencatatan pembayaran termin PO.
4. **Tab `realisasi-non-po`:** Rekapitulasi belanja operasional Non-PO beserta vendor penerima dan bukti struk/kwitansi.
5. **Tab `pengalihan`:** Rekapitulasi transfer dana antar-WBS.
6. **Tab `riwayat`:** Ledger mutasi keuangan seluruh aktivitas (*audit trail*). Dilengkapi filter tipe transaksi (`trx_type`), filter WBS, dan filter rentang tanggal (`trx_start_date` & `trx_end_date`).

### Halaman Rincian WBS (`/monitoring-anggaran/wbs/{wbs}`):
- Menampilkan 7 kartu metrik finansial WBS.
- 5 tombol aksi cepat: `+ Tambah PO`, `+ Catat Bayar PO`, `+ Catat Non-PO`, `Pengalihan Anggaran`, dan `Riwayat Transaksi`.
- Tabel Rincian PO beserta riwayat pembayaran di bawah masing-masing PO.
- Tabel Rincian Non-PO.
- Panel Narasi Pengalihan Anggaran (Kotak Merah untuk Transfer Keluar, Hijau untuk Transfer Masuk).
- Timeline riwayat aktivitas transaksi WBS.

---

## 8. EKSPOR LAPORAN (PDF & EXCEL) & SERVICE RESET AMAN

### A. Ekspor PDF (`export-pdf`):
- Format Dokumen: Landscape A4 menggunakan DomPDF.
- Header: Logo HSSE, Judul Laporan Resmi, Tahun Anggaran, Tanggal Cetak, dan Nama Petugas Pengunduh.
- Badan: Ringkasan 6 Indikator Pagu dan Tabel Tabular Kuning Rekapitulasi WBS dengan format Rupiah presisi.
- Hak Akses: Dilindungi middleware `can.export` (Role `Admin Management` dan `User`).

### B. Ekspor Excel / CSV (`export-excel`):
- Mengalirkan file CSV dengan header `BOM (\xEF\xBB\xBF)` agar karakter dan angka terbuka rapi di Microsoft Excel.
- Menyertakan seluruh kolom finansial: WBS, Program, Budget, Total PO, Commitment, PO Expenses, Non-PO Expenses, Assigned, Available, dan Status.

### C. Fitur Reset Data Aman (`BudgetMonitoringResetService`):
- Memiliki method audit count: `getAuditCounts()` untuk menampilkan jumlah record di setiap tabel.
- **Pencadangan Otomatis (Auto Backup):** Sebelum data dihapus, sistem otomatis mengekspor seluruh data tabel anggaran ke file JSON di `storage/app/budget-backups/budget_backup_YYYYMMDD_His.json`.
- **Penghapusan Transaksional Terurut:** Menghapus dari child record terlebih dahulu (`transactions` -> `po_payments` -> `pos` -> `non_pos` -> `transfers` -> `adjustments` -> `wbs`).
- Opsi `resetWbs = false` untuk hanya membersihkan riwayat transaksi sambil mempertahankan master WBS (mengembalikan `current_budget` ke `initial_budget`).

---

## 9. DAFTAR ROUTING & ACTIVITY LOGGING

```php
Route::prefix('monitoring-anggaran')->name('budget-monitoring.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Exports
    Route::get('/', [BudgetMonitoringController::class, 'index'])->name('index');
    Route::get('/wbs/{wbs}', [BudgetMonitoringController::class, 'showWbs'])->name('wbs.show');
    Route::get('/export-pdf', [BudgetMonitoringController::class, 'exportPdf'])->name('export-pdf')->middleware('can.export');
    Route::get('/export-excel', [BudgetMonitoringController::class, 'exportExcel'])->name('export-excel')->middleware('can.export');

    // WBS Transactions
    Route::post('/wbs', [BudgetMonitoringController::class, 'storeWbs'])->name('wbs.store');
    Route::put('/wbs/{wbs}', [BudgetMonitoringController::class, 'updateWbs'])->name('wbs.update');
    Route::delete('/wbs/{wbs}', [BudgetMonitoringController::class, 'destroyWbs'])->name('wbs.destroy');
    Route::post('/wbs/{wbs}/adjust', [BudgetMonitoringController::class, 'adjustBudget'])->name('wbs.adjust');

    // PO Transactions
    Route::post('/po', [BudgetMonitoringController::class, 'storePo'])->name('po.store');
    Route::put('/po/{po}', [BudgetMonitoringController::class, 'updatePo'])->name('po.update');
    Route::delete('/po/{po}', [BudgetMonitoringController::class, 'destroyPo'])->name('po.destroy');

    // PO Payments
    Route::post('/po/{po}/payments', [BudgetMonitoringController::class, 'storePoPayment'])->name('po-payment.store');
    Route::delete('/payments/{payment}', [BudgetMonitoringController::class, 'destroyPoPayment'])->name('po-payment.destroy');

    // Non-PO Transactions
    Route::post('/non-po', [BudgetMonitoringController::class, 'storeNonPo'])->name('non-po.store');
    Route::put('/non-po/{nonPo}', [BudgetMonitoringController::class, 'updateNonPo'])->name('non-po.update');
    Route::delete('/non-po/{nonPo}', [BudgetMonitoringController::class, 'destroyNonPo'])->name('non-po.destroy');

    // Transfers
    Route::post('/transfers', [BudgetMonitoringController::class, 'storeTransfer'])->name('transfers.store');
    Route::delete('/transfers/{transfer}', [BudgetMonitoringController::class, 'destroyTransfer'])->name('transfers.destroy');

    // Reset Service
    Route::post('/reset-data', [BudgetMonitoringController::class, 'resetData'])->name('reset-data');
});
```

---

## 10. INSTRUKSI GENERASI KODE (PROMPT AKHIR UNTUK AI ENGINE)

> Salin dan jalankan prompt instruksi di bawah ini ke AI Engine / Code Assistant Anda untuk membuat kode sumbernya:

```text
Buatkan implementasi modul Monitoring Anggaran (HSE & Safeguard Budget Monitoring) secara lengkap sesuai dokumen spesifikasi teknis di atas:

1. Buat migration file untuk 7 tabel: `budget_wbs`, `budget_pos`, `budget_po_payments`, `budget_non_pos`, `budget_transfers`, `budget_adjustments`, dan `budget_transactions` lengkap dengan indexing, foreign keys, dan field attachment JSON array.
2. Buat Model-Model Eloquent:
   - `BudgetWbs.php`: Lengkap dengan relasi hasMany ke seluruh tabel transaksi, accessor perhitungan 7 indikator finansial (total_po_value, po_realization, non_po_realization, allocated_budget, remaining_budget, remaining_po_commitment), helper formatRupiah, dan auto-generator nomor WBS.
   - `BudgetPo.php`: Relasi belongsTo WBS, hasMany payments, accessor attachment_list (hybrid single & array), hitung remaining_commitment, dan auto-generator nomor PO.
   - `BudgetPoPayment.php` & `BudgetNonPo.php`: Relasi belongsTo WBS/PO, accessor proof_list, dan auto-generator nomor transaksi.
   - `BudgetTransfer.php` & `BudgetAdjustment.php`: Relasi transfer source/destination dan adjustment types.
   - `BudgetTransaction.php`: Buku besar audit transaksi dengan konstanta tipe transaksi lengkap.
3. Buat Service `BudgetMonitoringResetService.php` dengan fitur getAuditCounts, backup JSON otomatis ke storage/app/budget-backups/, dan penghapusan transaksional terurut.
4. Buat Controller `BudgetMonitoringController.php` dengan method lengkap (index, showWbs, storeWbs, updateWbs, destroyWbs, adjustBudget, storePo, updatePo, destroyPo, storePoPayment, destroyPoPayment, storeNonPo, updateNonPo, destroyNonPo, storeTransfer, destroyTransfer, exportPdf, exportExcel, resetData) yang dibungkus DB::transaction() dan mencatat activity log audit trail.
5. Buat Blade view `index.blade.php` lengkap dengan 3 kartu metrik ringkasan, 6 Tab terpisah, tabel Excel kuning dengan footer total sum, dan modal-modal input.
6. Buat Blade view `show-wbs.blade.php` lengkap dengan 7 kartu metrik grid, 5 tombol aksi cepat, tabel rincian PO & payments, rincian Non-PO, panel naratif pengalihan anggaran, dan timeline riwayat transaksi.
7. Buat komponen multi-file upload dropzone compact (~90px) dengan tombol "+ Tambah Dokumen" yang mendukung penambahan dokumen dari folder berbeda tanpa menimpa pilihan sebelumnya.
8. Buat template PDF `exports/budget-monitoring-pdf.blade.php` berorientasi A4 landscape dengan branding resmi HSSE.



## 11. REGRESSION CHECK — ROLE KHUSUS ADMIN INVENTORY

Sebelum menyelesaikan perubahan Environment, pastikan revisi tidak memengaruhi akun dan role khusus Admin Inventory yang sebelumnya sudah dibuat.

- Pertahankan akun `inventory@geodipa.co.id` dan role khusus Admin Inventory.
- Pertahankan hak akses yang sebelumnya ditetapkan: hanya dapat mengakses menu Inventory dan menggunakan fitur peminjaman barang sesuai implementasi yang sudah ada.
- Pastikan menu lain tetap tidak dapat diakses, termasuk melalui URL langsung.
- Jangan mengganti role Admin Management, role user biasa, atau permission pengguna lain.
- Jangan membuat ulang akun, mengubah password, atau mengubah sistem autentikasi.
- Jangan mengubah modul Inventory jika tidak ditemukan masalah yang benar-benar disebabkan oleh revisi Environment.
- Jika ada regresi, perbaiki hanya penyebabnya.
- Verifikasi berdasarkan kode dan pengujian aktual. Jika belum dapat diuji, laporkan dengan jujur.
```

