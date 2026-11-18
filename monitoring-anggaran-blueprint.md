# 📋 MASTER PROMPT & SPESIFIKASI TEKNIS: MODUL MONITORING ANGGARAN (HSE & SAFEGUARD BUDGET MONITORING)

Dokumen ini berisi spesifikasi teknis lengkap, arsitektur sistem, skema basis data 7 tabel, formula perhitungan akuntansi K3/HSSE, spesifikasi multi-upload bukti transaksi, fitur Detail WBS terbaru, dan master prompt dari modul **Monitoring Anggaran (Budget Monitoring)** pada sistem HSSE Project. Dokumen ini dirancang sebagai cetak biru (*blueprint*) definitif yang siap digunakan untuk meregenerasi seluruh kode di proyek master.

---

## DAFTAR ISI
1. [Bagian I: Master Prompt Siap Pakai untuk AI / Generator Kode](#bagian-i-master-prompt-siap-pakai-untuk-ai--generator-kode)
2. [Bagian II: Arsitektur & Hak Akses (RBAC)](#bagian-ii-arsitektur--hak-akses-rbac)
3. [Bagian III: Skema Basis Data 7 Tabel Lengkap](#bagian-iii-skema-basis-data-7-tabel-lengkap)
4. [Bagian IV: Standar Formula Akuntansi & Rumus Baku K3](#bagian-iv-standar-formula-akuntansi--rumus-baku-k3)
5. [Bagian V: Spesifikasi Form Input & Multi-File Upload Bukti](#bagian-v-spesifikasi-form-input--multi-file-upload-bukti)
6. [Bagian VI: Spesifikasi Lengkap Halaman Detail WBS](#bagian-vi-spesifikasi-lengkap-halaman-detail-wbs)
7. [Bagian VII: Filter Transaksi & Quick Pills](#bagian-vii-filter-transaksi--quick-pills)
8. [Bagian VIII: Bagian Riwayat Pembayaran PO (Tanpa Kata Bertahap)](#bagian-viii-bagian-riwayat-pembayaran-po-tanpa-kata-bertahap)
9. [Bagian IX: Penomoran Urut Audit Log WBS](#bagian-ix-penomoran-urut-audit-log-wbs)
10. [Bagian X: Mekanisme Safe Deletion & Pemulihan Saldo Otomatis](#bagian-x-mekanisme-safe-deletion--pemulihan-saldo-otomatis)
11. [Bagian XI: Fitur Reset Data Mandiri & Auto-Backup Snapshot](#bagian-xi-fitur-reset-data-mandiri--auto-backup-snapshot)
12. [Bagian XII: Routing, Controller, & File Reference](#bagian-xii-routing-controller--file-reference)

---

## BAGIAN I: MASTER PROMPT SIAP PAKAI UNTUK AI / GENERATOR KODE

> **Panduan:** Salin teks dalam blok kode di bawah ini untuk menginstruksikan AI / model bahasa dalam membangun kembali seluruh kode modul Monitoring Anggaran secara komprehensif.

```markdown
Anda adalah Senior Financial & Enterprise Laravel Engineer. Bangunlah modul "Monitoring Anggaran HSSE (Work Breakdown Structure - Budget Monitoring)" lengkap pada proyek Laravel 12 dengan standar akuntansi komitmen, audit trail ketat, dan siap produksi.

### 1. RUANG LINGKUP & PRINSIP KEUANGAN UTAMA
- Modul ini mengelola siklus anggaran K3/HSSE berbasis Work Breakdown Structure (WBS), Pengadaan Purchase Order (PO), Realisasi Pembayaran PO (PO Expenses), Realisasi Pengeluaran Langsung Non-PO (Non-PO Expenses), Pengalihan Anggaran Antar-WBS (Budget Transfer), dan Penyesuaian Anggaran (Adjustment).
- Standar Rumus Keuangan Baku (Anti-Double Deduction):
  1. Budget (Total Anggaran WBS) = current_budget pada tabel budget_wbs.
  2. Total PO = Akumulasi nilai PO aktif (po_value > 0 ? po_value : commitment_value).
  3. PO Expenses = Total realisasi pembayaran bertahap PO (sum amount pada budget_po_payments).
  4. Non-PO Expenses = Total pengeluaran langsung non-PO (sum amount pada budget_non_pos).
  5. Commitment (Sisa Komitmen PO) = max(0, Total PO - PO Expenses). Nilai komitmen berkurang secara bertahap saat pembayaran PO dicatat.
  6. Assigned (Anggaran Teralokasi) = Total PO + Non-PO Expenses. (PENTING: Pembayaran PO TIDAK MENGURANGI sisa anggaran WBS lagi karena komitmen nilai PO sudah mengalokasikannya saat PO dibuat).
  7. Available (Sisa Anggaran WBS) = Budget - Assigned = Budget - (Total PO + Non-PO Expenses).
- Role-Based Access Control (RBAC):
  * Admin Management: Hak akses penuh (CRUD WBS, CRUD PO, Catat Pembayaran PO, Catat Non-PO, Pengalihan Anggaran, Penyesuaian Anggaran, Ekspor PDF/Excel, dan Reset Data Anggaran).
  * User (Pegawai): Read-only (Melihat dashboard, rincian aktivitas WBS, riwayat pengalihan, audit log, dan Ekspor PDF/Excel).
  * Admin Inventory: Dilarang mengakses modul ini (dicegah middleware not.inventory).

### 2. STRUKTUR DATABASE (7 TABEL KEUANGAN)
1. `budget_wbs`: id, wbs_number (unique, format: WBS-HSE-YYYY-XXX), wbs_name, description, initial_budget (decimal 16,2), current_budget (decimal 16,2), fiscal_year (integer), is_active (boolean), created_by, updated_by, timestamps.
2. `budget_pos`: id, budget_wbs_id (FK cascade), po_number (unique, format: PO-HSE-YYYY-XXX), program_name, vendor, po_value (decimal 16,2), commitment_value (decimal 16,2), po_date (date), status (string, default 'Aktif'), description, attachment_path, created_by, timestamps.
3. `budget_po_payments`: id, budget_po_id (FK cascade), budget_wbs_id (FK cascade), payment_number (unique, Nomor SA/GR), payment_date (date), amount (decimal 16,2), reference_number, description, proof_path, proof_paths (json nullable untuk multi-bukti file), created_by, timestamps.
4. `budget_non_pos`: id, budget_wbs_id (FK cascade), transaction_number (unique, format: NPO-YYYY-XXXX), program_name, transaction_date (date), amount (decimal 16,2), recipient_vendor, description, proof_path, proof_paths (json nullable untuk multi-bukti file), created_by, timestamps.
5. `budget_transfers`: id, transfer_number (unique, format: TRF-YYYY-XXXX), source_wbs_id (FK cascade), destination_wbs_id (FK cascade), amount (decimal 16,2), transfer_date (date), reason (text), description, status ('Disetujui'), created_by, timestamps.
6. `budget_adjustments`: id, adjustment_number (unique), budget_wbs_id (FK cascade), adjustment_type ('PENYESUAIAN'), amount (decimal 16,2), old_budget (decimal 16,2), new_budget (decimal 16,2), adjustment_date (date), reason (text), created_by, timestamps.
7. `budget_transactions`: id, transaction_number (unique, format: TRX-YYYY-XXXXX), transaction_type ('PO_COMMITMENT', 'PO_PAYMENT', 'NON_PO', 'TRANSFER_IN', 'TRANSFER_OUT', 'ADJUSTMENT'), transaction_type_label, transaction_date, budget_wbs_id (FK cascade), program_name, reference_number, amount, description, created_by, related_model_type, related_model_id, timestamps.

### 3. FITUR HALAMAN DETAIL WBS
- Header:
  * Badge Kode WBS berformat: `WBS: AAA-001` (menggunakan nomor WBS aktif).
  * Nama Program, Tahun Anggaran, dan Badge Status Aktif/Non-Aktif.
  * Tombol Aksi Header: `+ Tambah Pengadaan / PO`, `+ Catat Pembayaran PO`, `+ Catat Realisasi Non-PO`, `Pengalihan Anggaran`.
- 7 Kartu Metrik Ringkasan WBS (Budget, Total PO, PO Expenses, Non-PO Expenses, Commitment, Assigned, Available).
- Filter Transaksi Tepat di Atas Tabel Aktivitas:
  * Dropdown & Tombol Pilihan Cepat (Pill Badges): Semua Transaksi, Pengadaan PO, PO Expenses, Non-PO Expenses, Pengalihan Anggaran Masuk, Pengalihan Anggaran Keluar.
  * Menampilkan jumlah transaksi di samping label (contoh: `PO Expenses (3)`).
- Tabel Utama: Rincian Aktivitas Transaksi WBS (Tema Excel Kuning):
  * Kolom: No., Tanggal, Program / Aktivitas, No. Trx / PO / SA, Vendor, Nilai PO, PO Expenses, Non-PO Expenses, Bukti Transaksi (link download file bukti), Aksi (Detail, Edit, Hapus).
- Bagian Riwayat Pengalihan Anggaran (Collapsible, Tersembunyi Default):
  * Tombol toggle: `Lihat Riwayat Pengalihan Anggaran ({count})`.
  * Menampilkan narasi mutasi dinamis dan tabel transfer masuk/keluar.
- Bagian Riwayat Pembayaran PO (Collapsible, Tersembunyi Default, Tanpa Kata "Bertahap"):
  * Judul: `Riwayat Pembayaran PO` (kata "Bertahap" dihilangkan).
  * Menampilkan tabel pemantauan kumulatif pembayaran dan sisa komitmen per PO.
- Bagian Riwayat Transaksi Keuangan / Audit Log WBS (Collapsible):
  * Kolom `No.` wajib berurutan rapi: 1, 2, 3, 4, 5... (menggunakan loop iteration dengan collection values).
  * Pengurutan data transaksi kronologis (terbaru di atas: transaction_date DESC, id DESC).

### 4. VALIDASI & INTEGRITAS TRANSAKSI
1. Validasi PO: Nilai PO tidak boleh melebihi sisa anggaran WBS (`Available`).
2. Validasi Pembayaran PO: Nominal pembayaran tidak boleh melebihi sisa komitmen PO (`remaining_commitment`).
3. Validasi Non-PO: Nominal pengeluaran langsung tidak boleh melebihi sisa anggaran WBS (`Available`).
4. Validasi Pengalihan Anggaran: Nominal transfer tidak boleh melebihi sisa anggaran WBS Asal (`Available`). Sifat transfer invarian (Total anggaran seluruh sistem tidak bertambah/berkurang).
5. Multi-file upload: Mendukung unggah banyak bukti pembayaran/kuitansi (PDF/JPG/PNG) per transaksi dengan batas 5MB per file.
6. Penghapusan Aman (Safe Deletion & Balance Restoration): Menghapus PO/Pembayaran/Non-PO/Transfer secara otomatis mengembalikan saldo komitmen dan anggaran ke kondisi sebelumnya.
7. Reset Data Mandiri: Tombol reset data khusus Admin Management yang mewajibkan pengetikan teks konfirmasi dan secara otomatis membuat snapshot backup JSON ke storage sebelum pengosongan data.
```

---

## BAGIAN II: ARSITEKTUR & HAK AKSES (RBAC)

Modul Monitoring Anggaran diatur secara ketat menggunakan otentikasi dan middleware Laravel:

| Role Pengguna | Batasan Hak Akses | Keterangan & Rute yang Diizinkan |
|---|---|---|
| **Admin Management** (`admin_management`) | Akses Penuh (Full Control) | Mengelola WBS, Penyesuaian Anggaran, Pengadaan PO, Pembayaran PO, Non-PO, Pengalihan Anggaran, Ekspor PDF/Excel, dan Reset Data. |
| **User / Pegawai** (`user`) | Akses Baca Saja (Read-Only) | Melihat dashboard ringkasan, membuka halaman Detail WBS, melihat riwayat mutasi, riwayat pembayaran, audit log, serta mengunduh laporan PDF dan Excel. |
| **Admin Inventory** (`admin_inventory`) | Ditolak Penuh (`403 Forbidden`) | Dicegah oleh middleware `not.inventory` agar fokus hanya pada modul Peminjaman Barang / Inventory. |

---

## BAGIAN III: SKEMA BASIS DATA 7 TABEL LENGKAP

### 1. Relasi Antar-Tabel
- `budget_wbs` adalah induk utama (Root Entity).
- `budget_pos` terikat 1:N ke `budget_wbs`.
- `budget_po_payments` terikat 1:N ke `budget_pos` dan 1:N ke `budget_wbs`.
- `budget_non_pos` terikat 1:N ke `budget_wbs`.
- `budget_transfers` mencatat relasi antar dua WBS (`source_wbs_id` dan `destination_wbs_id`).
- `budget_adjustments` mencatat riwayat perubahan pagu anggaran WBS.
- `budget_transactions` adalah tabel buku besar (*general ledger audit trail*) yang mencatat mutasi keuangan secara polimorfis (`related_model_type` & `related_model_id`).

### 2. Definisi SQL DDL Seluruh Tabel

```sql
-- 1. TABEL WBS (PROGRAM ANGGARAN)
CREATE TABLE `budget_wbs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wbs_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wbs_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `initial_budget` decimal(16,2) NOT NULL DEFAULT '0.00',
  `current_budget` decimal(16,2) NOT NULL DEFAULT '0.00',
  `fiscal_year` int NOT NULL DEFAULT '2026',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_wbs_wbs_number_unique` (`wbs_number`),
  KEY `budget_wbs_fiscal_year_index` (`fiscal_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL PENGADAAN PURCHASE ORDER (PO)
CREATE TABLE `budget_pos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `budget_wbs_id` bigint unsigned NOT NULL,
  `po_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_value` decimal(16,2) NOT NULL DEFAULT '0.00',
  `commitment_value` decimal(16,2) NOT NULL DEFAULT '0.00',
  `po_date` date NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `description` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_pos_po_number_unique` (`po_number`),
  KEY `budget_pos_po_date_index` (`po_date`),
  CONSTRAINT `budget_pos_budget_wbs_id_foreign` FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL REALISASI PEMBAYARAN PO (PO EXPENSES)
CREATE TABLE `budget_po_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `budget_po_id` bigint unsigned NOT NULL,
  `budget_wbs_id` bigint unsigned NOT NULL,
  `payment_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_paths` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_po_payments_payment_number_unique` (`payment_number`),
  KEY `budget_po_payments_payment_date_index` (`payment_date`),
  KEY `budget_po_payments_reference_number_index` (`reference_number`),
  CONSTRAINT `budget_po_payments_budget_po_id_foreign` FOREIGN KEY (`budget_po_id`) REFERENCES `budget_pos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_po_payments_budget_wbs_id_foreign` FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL REALISASI NON-PO (NON-PO EXPENSES)
CREATE TABLE `budget_non_pos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `budget_wbs_id` bigint unsigned NOT NULL,
  `transaction_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` date NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `recipient_vendor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_paths` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_non_pos_transaction_number_unique` (`transaction_number`),
  KEY `budget_non_pos_transaction_date_index` (`transaction_date`),
  CONSTRAINT `budget_non_pos_budget_wbs_id_foreign` FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL PENGALIHAN ANGGARAN ANTAR-WBS
CREATE TABLE `budget_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_wbs_id` bigint unsigned NOT NULL,
  `destination_wbs_id` bigint unsigned NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `transfer_date` date NOT NULL,
  `reason` text NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Disetujui',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_transfers_transfer_number_unique` (`transfer_number`),
  KEY `budget_transfers_transfer_date_index` (`transfer_date`),
  CONSTRAINT `budget_transfers_source_wbs_id_foreign` FOREIGN KEY (`source_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_transfers_destination_wbs_id_foreign` FOREIGN KEY (`destination_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL PENYESUAIAN ANGGARAN WBS (ADJUSTMENT)
CREATE TABLE `budget_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_wbs_id` bigint unsigned NOT NULL,
  `adjustment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENYESUAIAN',
  `amount` decimal(16,2) NOT NULL,
  `old_budget` decimal(16,2) NOT NULL,
  `new_budget` decimal(16,2) NOT NULL,
  `adjustment_date` date NOT NULL,
  `reason` text NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_adjustments_adjustment_number_unique` (`adjustment_number`),
  KEY `budget_adjustments_adjustment_date_index` (`adjustment_date`),
  CONSTRAINT `budget_adjustments_budget_wbs_id_foreign` FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABEL AUDIT LOG TRANSAKSI KEUANGAN KESELURUHAN
CREATE TABLE `budget_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type_label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` date NOT NULL,
  `budget_wbs_id` bigint unsigned NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(16,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `related_model_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_model_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_transactions_transaction_number_unique` (`transaction_number`),
  KEY `budget_transactions_transaction_type_index` (`transaction_type`),
  KEY `budget_transactions_transaction_date_index` (`transaction_date`),
  KEY `budget_transactions_reference_number_index` (`reference_number`),
  KEY `budget_transactions_related_model_index` (`related_model_type`, `related_model_id`),
  CONSTRAINT `budget_transactions_budget_wbs_id_foreign` FOREIGN KEY (`budget_wbs_id`) REFERENCES `budget_wbs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## BAGIAN IV: STANDAR FORMULA AKUNTANSI & RUMUS BAKU K3

Modul ini mengadopsi standar akuntansi komitmen proyek (*Commitment-Based Budgeting*):

$$\begin{aligned}
\text{Budget} &= \text{current\_budget} \\
\text{Total PO} &= \sum_{\text{PO Aktif}} \text{po\_value} \\
\text{PO Expenses} &= \sum \text{amount}(\text{budget\_po\_payments}) \\
\text{Non-PO Expenses} &= \sum \text{amount}(\text{budget\_non\_pos}) \\
\text{Commitment} &= \max(0, \text{Total PO} - \text{PO Expenses}) \\
\text{Assigned} &= \text{Total PO} + \text{Non-PO Expenses} \\
\text{Available} &= \text{Budget} - \text{Assigned} = \text{Budget} - (\text{Total PO} + \text{Non-PO Expenses})
\end{aligned}$$

### Pencegahan Pengurangan Ganda (*No Double Deduction*):
Saat PO diterbitkan, seluruh nilai PO langsung mengikat anggaran sebesar nilainya (`Assigned`). Ketika pembayaran bertahap dicatat (`PO Expenses`), pembayaran tersebut **hanya mengurangi sisa komitmen PO** (`Commitment`) dan **tidak mengurangi sisa anggaran WBS (`Available`) untuk kedua kalinya**.

---

## BAGIAN V: SPESIFIKASI FORM INPUT & MULTI-FILE UPLOAD BUKTI

### 1. Form Pengadaan PO
- **Field:**
  1. Pilih WBS (`budget_wbs_id`): WBS aktif.
  2. Nomor PO (`po_number`): Unik, otomatis terisi atau manual.
  3. Nama Program (`program_name`): Keterangan pengadaan barang/jasa.
  4. Vendor (`vendor`): Nama penyedia jasa/rekanan.
  5. Nilai PO (`po_value`): Nominal rupiah.
  6. Tanggal PO (`po_date`): Tanggal penerbitan PO.
  7. Dokumen Kontrak/PO (`attachment`): File kontrak/PO (PDF/DOC/DOCX/JPG/PNG, maks 10MB).
  8. Keterangan (`description`): Catatan lingkup pekerjaan.
- **Aturan:** Input status dan komitmen telah dihapus dari form; sistem otomatis menghitung komitmen dan menetapkan status `Aktif`. Nilai PO tidak boleh melebihi `Available` WBS.

### 2. Form Catat Pembayaran PO
- **Field:**
  1. Pilih PO (`budget_po_id`): PO aktif dengan sisa komitmen yang tersedia.
  2. Nomor Pembayaran SA/GR (`payment_number`): Nomor Service Acceptance / Goods Receipt.
  3. Tanggal Pembayaran (`payment_date`): Tanggal pencairan dana.
  4. Nominal Pembayaran (`amount`): Nominal rupiah. (Validasi: `amount <= remaining_commitment`).
  5. Nomor Referensi (`reference_number`): Nomor invoice/kwitansi vendor.
  6. Keterangan (`description`): Uraian termin/tahap pembayaran.
  7. Bukti Transaksi Multi-File (`proofs[]`): Unggah multi-file (PDF/JPG/PNG, maks 5MB per file). Disimpan pada storage `budget/payment_proofs` (public disk).

### 3. Form Catat Realisasi Non-PO
- **Field:**
  1. Pilih WBS (`budget_wbs_id`): WBS pembebanan anggaran.
  2. Nomor Transaksi (`transaction_number`): Nomor bukti kas/non-PO.
  3. Nama Program / Keperluan (`program_name`): Rincian belanja langsung.
  4. Tanggal Pengeluaran (`transaction_date`): Tanggal transaksi kas.
  5. Nominal Pengeluaran (`amount`): Nominal rupiah. (Validasi: `amount <= Available` WBS).
  6. Penerima / Vendor (`recipient_vendor`): Pihak penerima dana.
  7. Keterangan (`description`): Catatan pengeluaran.
  8. Bukti Transaksi Multi-File (`proofs[]`): Unggah multi-file (PDF/JPG/PNG, maks 5MB per file). Disimpan pada storage `budget/non_po_proofs` (public disk).

### 4. Form Pengalihan Anggaran (Budget Transfer)
- **Field:**
  1. WBS Asal (`source_wbs_id`): WBS yang anggarannya dikurangi.
  2. WBS Tujuan (`destination_wbs_id`): WBS yang anggarannya ditambah.
  3. Nominal Pengalihan (`amount`): Validasi: `amount <= Available` WBS Asal.
  4. Tanggal Pengalihan (`transfer_date`): Tanggal persetujuan mutasi.
  5. Alasan Pengalihan (`reason`): Alasan teknis justifikasi mutasi anggaran.
- **Invarian Total:** Total pagu anggaran seluruh WBS tetap sama (tidak berubah sebelum dan sesudah transfer).

---

## BAGIAN VI: SPESIFIKASI LENGKAP HALAMAN DETAIL WBS

File tampilan: [`resources/views/data/budget-monitoring/show-wbs.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/data/budget-monitoring/show-wbs.blade.php)

### 1. Header Detail WBS
- Tampilan Badge: `<span class="badge ...">WBS: {{ $wbs->wbs_number }}</span>`
- Nama Program: `{{ $wbs->wbs_name }}`
- Status: Badge `Aktif` / `Non-Aktif`
- Keterangan: `Tahun Anggaran: {{ $wbs->fiscal_year }}`
- Tombol Aksi Header:
  - `+ Tambah Pengadaan / PO` (membuka modal `#modalTambahPo`)
  - `+ Catat Pembayaran PO` (membuka modal `#modalTambahPoPayment`)
  - `+ Catat Realisasi Non-PO` (membuka modal `#modalTambahNonPo`)
  - `Pengalihan Anggaran` (membuka modal `#modalTransferAnggaran`)
  - `Riwayat Transaksi` (anchor scroll ke audit log)

### 2. Kartu KPI Metrik Finansial WBS (7 Cards)
1. **Anggaran (Budget)**: Total pagu anggaran WBS saat ini.
2. **Total PO**: Total nilai PO aktif yang telah diterbitkan.
3. **PO Expenses**: Total realisasi pembayaran PO yang telah sah.
4. **Non-PO Expenses**: Total realisasi pengeluaran non-PO.
5. **Commitment**: Sisa komitmen PO yang belum dibayarkan.
6. **Assigned**: Anggaran teralokasi (`Total PO + Non-PO Expenses`).
7. **Available**: Sisa anggaran bebas yang masih dapat digunakan.

---

## BAGIAN VII: FILTER TRANSAKSI & QUICK PILLS

Posisi filter wajib tepat di atas tabel **Rincian Aktivitas Transaksi WBS**:
- **Dropdown & Tombol Pill Cepat:**
  - `Semua Transaksi ({count})`
  - `Pengadaan PO ({count})`
  - `PO Expenses ({count})` *(label resmi untuk pembayaran PO)*
  - `Non-PO Expenses ({count})` *(label resmi untuk pengeluaran non-PO)*
  - `Pengalihan Anggaran Masuk ({count})`
  - `Pengalihan Anggaran Keluar ({count})`
- **Pencarian Real-Time:** Mencari nama program, nomor transaksi/PO/SA, atau vendor.
- **Tombol Reset:** Kembali ke Semua Transaksi.

---

## BAGIAN VIII: BAGIAN RIWAYAT PEMBAYARAN PO (TANPA KATA BERTAHAP)

- Bagian ini **tersembunyi secara default** (*collapsible* via `#collapseRiwayatPembayaran`).
- Judul resmi: **`Riwayat Pembayaran PO`** *(kata "Bertahap" dihilangkan seluruhnya dari judul, subtitle, tombol, dan modal)*.
- Subtitle: *"Riwayat pembayaran PO dengan pemantauan realisasi kumulatif dan sisa komitmen."*
- Tombol toggle: `Lihat Semua Riwayat Pembayaran PO` / `Sembunyikan Riwayat Pembayaran PO`.
- Judul Modal Pencatatan: **`Catat Pembayaran PO`**.
- Tabel pemantauan kumulatif tetap menyajikan: No, Tanggal Pembayaran, Nomor Referensi (No. SA/GR), No. PO & Vendor, Nilai PO, Realisasi Kumulatif, Sisa Komitmen PO, Bukti Transaksi, dan Tombol Hapus Pembayaran.

---

## BAGIAN IX: PENOMORAN URUT AUDIT LOG WBS

Pada tabel **Riwayat Transaksi Keuangan (Audit Log WBS)**:
- Tersembunyi secara default (*collapsible* via `#collapseRiwayatTransaksi`).
- Kolom `No.` **wajib berurutan rapi:** `1, 2, 3, 4, 5, ...` tanpa melompat/acak.
- Implementasi Blade:
  ```blade
  @forelse($wbs->transactions->sortByDesc('transaction_date')->values() as $tIdx => $trx)
      <tr>
          <td class="ps-3 text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
          <td class="text-center small text-muted">
              {{ $trx->transaction_date ? $trx->transaction_date->format('d/m/Y') : '-' }}
          </td>
          <td>
              <span class="font-monospace fw-bold text-dark">{{ $trx->transaction_number }}</span>
              @if($trx->reference_number)
                  <small class="text-muted d-block">Ref: {{ $trx->reference_number }}</small>
              @endif
          </td>
          <td>
              <span class="badge bg-light text-dark border">
                  {{ $trx->transaction_type_label ?? $trx->transaction_type }}
              </span>
          </td>
          <td class="text-end currency-val fw-bold text-dark">
              {{ \App\Models\BudgetTransaction::formatRupiah($trx->amount) }}
          </td>
          <td class="small text-muted text-wrap" style="max-width: 300px;">
              {{ $trx->description }}
          </td>
          <td class="small text-muted">
              {{ $trx->creator?->name ?? 'Admin' }}
          </td>
      </tr>
  @empty
      <tr>
          <td colspan="7" class="text-center py-4 text-muted">
              Belum ada riwayat transaksi keuangan tercatat.
          </td>
      </tr>
  @endforelse
  ```

---

## BAGIAN X: MEKANISME SAFE DELETION & PEMULIHAN SALDO OTOMATIS

Sistem memastikan tidak ada transaksi yang terhapus dengan meninggalkan saldo gantung:
1. **Hapus Pengadaan PO:**
   - Dilarang jika PO sudah memiliki pembayaran (`payments()->exists()`).
   - Menghapus transaksi audit komitmen dan memulihkan alokasi anggaran WBS.
2. **Hapus Pembayaran PO:**
   - Mengembalikan saldo komitmen PO (`commitment_value += amount`).
   - Menghapus transaksi audit pembayaran PO.
   - Menghapus file fisik bukti dari storage.
3. **Hapus Realisasi Non-PO:**
   - Mengembalikan sisa anggaran WBS (`current_budget` tidak terpotong lagi).
   - Menghapus transaksi audit non-PO.
   - Menghapus file fisik bukti dari storage.
4. **Hapus Pengalihan Anggaran:**
   - Mengembalikan saldo simetris: WBS Asal ditambah kembali (`+ amount`), WBS Tujuan dikurangi kembali (`- amount`).
   - Menghapus kedua transaksi audit transfer (Transfer In & Transfer Out).

---

## BAGIAN XI: FITUR RESET DATA MANDIRI & AUTO-BACKUP SNAPSHOT

Dikelola melalui `BudgetMonitoringResetService`:
1. **Snapshot Backup Otomatis:**
   - Lokasi: `storage/app/budget-backups/budget_backup_YYYYMMDD_HHMMSS.json`.
   - Menyimpan seluruh baris data dari ke-7 tabel sebelum truncate/delete dijalankan.
2. **Konfirmasi Keamanan:**
   - Pengguna wajib mengetik teks persis: `RESET ANGGARAN`.
3. **Urutan Pengosongan Tabel:**
   Mematuhi integritas foreign key secara ketat:
   `budget_transactions` $\rightarrow$ `budget_adjustments` $\rightarrow$ `budget_transfers` $\rightarrow$ `budget_non_pos` $\rightarrow$ `budget_po_payments` $\rightarrow$ `budget_pos` $\rightarrow$ `budget_wbs`.

---

## BAGIAN XII: ROUTING, CONTROLLER, & FILE REFERENCE

### 1. Definisi Route (`routes/web.php`):
```php
Route::prefix('monitoring-anggaran')->name('budget-monitoring.')->middleware(['auth', 'not.inventory'])->group(function () {
    // Read & Export (Admin Management & User)
    Route::get('/', [BudgetMonitoringController::class, 'index'])->name('index');
    Route::get('/wbs/{wbs}', [BudgetMonitoringController::class, 'showWbs'])->name('wbs.show');
    Route::get('/export-pdf', [BudgetMonitoringController::class, 'exportPdf'])->name('export-pdf')->middleware('can.export');
    Route::get('/export-excel', [BudgetMonitoringController::class, 'exportExcel'])->name('export-excel')->middleware('can.export');

    // Admin Management Only Actions
    Route::middleware(['admin'])->group(function () {
        // WBS CRUD & Adjustment
        Route::post('/wbs', [BudgetMonitoringController::class, 'storeWbs'])->name('wbs.store');
        Route::put('/wbs/{wbs}', [BudgetMonitoringController::class, 'updateWbs'])->name('wbs.update');
        Route::delete('/wbs/{wbs}', [BudgetMonitoringController::class, 'destroyWbs'])->name('wbs.destroy');
        Route::post('/wbs/{wbs}/adjust', [BudgetMonitoringController::class, 'adjustBudget'])->name('wbs.adjust');

        // Pengadaan / PO
        Route::post('/po', [BudgetMonitoringController::class, 'storePo'])->name('po.store');
        Route::put('/po/{po}', [BudgetMonitoringController::class, 'updatePo'])->name('po.update');
        Route::delete('/po/{po}', [BudgetMonitoringController::class, 'destroyPo'])->name('po.destroy');

        // Realisasi Pembayaran PO
        Route::post('/po/{po}/payments', [BudgetMonitoringController::class, 'storePoPayment'])->name('po-payment.store');
        Route::delete('/payments/{payment}', [BudgetMonitoringController::class, 'destroyPoPayment'])->name('po-payment.destroy');
        Route::delete('/po-payment/{payment}', [BudgetMonitoringController::class, 'destroyPoPayment']);

        // Realisasi Non-PO
        Route::post('/non-po', [BudgetMonitoringController::class, 'storeNonPo'])->name('non-po.store');
        Route::put('/non-po/{nonPo}', [BudgetMonitoringController::class, 'updateNonPo'])->name('non-po.update');
        Route::delete('/non-po/{nonPo}', [BudgetMonitoringController::class, 'destroyNonPo'])->name('non-po.destroy');

        // Pengalihan Anggaran Antar-WBS
        Route::post('/transfers', [BudgetMonitoringController::class, 'storeTransfer'])->name('transfers.store');
        Route::delete('/transfers/{transfer}', [BudgetMonitoringController::class, 'destroyTransfer'])->name('transfers.destroy');

        // Reset Data
        Route::post('/reset-data', [BudgetMonitoringController::class, 'resetData'])->name('reset-data');
    });
});

Route::get('/budget-monitoring', function () {
    return redirect()->route('budget-monitoring.index');
})->middleware(['auth', 'not.inventory']);
```

### 2. Daftar File Terkait pada Project:
- **Models:**
  - [`app/Models/BudgetWbs.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetWbs.php)
  - [`app/Models/BudgetPo.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetPo.php)
  - [`app/Models/BudgetPoPayment.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetPoPayment.php)
  - [`app/Models/BudgetNonPo.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetNonPo.php)
  - [`app/Models/BudgetTransfer.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetTransfer.php)
  - [`app/Models/BudgetAdjustment.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetAdjustment.php)
  - [`app/Models/BudgetTransaction.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Models/BudgetTransaction.php)
- **Controller & Service:**
  - [`app/Http/Controllers/Modules/BudgetMonitoringController.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Http/Controllers/Modules/BudgetMonitoringController.php)
  - [`app/Services/BudgetMonitoringResetService.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/app/Services/BudgetMonitoringResetService.php)
- **Views:**
  - [`resources/views/data/budget-monitoring/index.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/data/budget-monitoring/index.blade.php)
  - [`resources/views/data/budget-monitoring/show-wbs.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/data/budget-monitoring/show-wbs.blade.php)
  - [`resources/views/exports/budget-monitoring-pdf.blade.php`](file:///c:/laragon/www/HSSE%20PROJECT%20NEW%204/HSSE%20PROJECT/resources/views/exports/budget-monitoring-pdf.blade.php)
- **Migrations:**
  - `database/migrations/2026_10_21_170000_create_budget_monitoring_tables.php`
  - `database/migrations/2026_10_23_142000_add_proof_paths_to_budget_payments_and_non_pos.php`
- **Feature Tests:**
  - `tests/Feature/BudgetMonitoring/BudgetMonitoringManagementTest.php` (26 test passed)
  - `tests/Feature/BudgetMonitoring/DetailWbsImprovementsTest.php` (4 test passed)

