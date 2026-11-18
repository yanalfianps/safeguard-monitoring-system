# MASTER PROMPT & SPESIFIKASI TEKNIS: MODUL INVENTORY (HSSE)

```text
================================================================================
MASTER PROMPT: GENERATE MODUL INVENTORY HSSE LENGKAP (LARAVEL 11/12 + BLADE + MYSQL)
================================================================================

Role: Senior Full-Stack Laravel Architect & Warehouse/Logistics Systems Specialist.
Task: Bangun ulang seluruh modul "Inventory HSSE" (Manajemen Logistik APD, Peralatan Emergency, 
      Mutasi Stok Multi-Lokasi, Batch Expired FEFO, dan Peminjaman/Pengembalian Bertahap) 
      secara menyeluruh, transaksional (DB Transaction), dan terintegrasi penuh 
      sesuai spesifikasi arsitektur teknis di bawah ini.
Framework: Laravel 11/12, Blade Templates, Bootstrap 5 UI, Vanilla JS, 
           DomPDF (barryvdh/laravel-dompdf), MySQL.
```

---

## 1. DESKRIPSI UMUM & ARSITEKTUR BISNIS MODUL

Modul **Inventory HSSE** mengelola seluruh siklus hidup perlengkapan keselamatan kerja, alat pelindung diri (APD), peralatan medis/P3K, instrumen deteksi gas, rambu, dan peralatan tanggap darurat (*emergency rescue*).

### Karakteristik Arsitektur Kunci:
1. **Multi-Lokasi Penyimpanan Riil:** Setiap barang dapat tersebar di 2 lokasi fisik berbeda:
   - **`Warehouse`** (Gudang Pusat / Logistik Utama)
   - **`Gudang HSSE`** (Gudang Operasional / Transit Tim HSSE Lapangan)
   - Kolom stok terpisah: `stock_warehouse` dan `stock_hsse`, dengan agregasi otomatis `stock = stock_warehouse + stock_hsse`.
2. **Manajemen Kedaluwarsa FEFO (*First-Expired, First-Out*):**
   - Didukung tabel `inventory_stock_batches` yang memecah stok per lokasi dan per tanggal expired (`expired_date`).
   - Barang masuk dengan tanggal expired berbeda **tidak membuat master barang baru**, melainkan menambah kelompok batch baru pada master yang sama.
   - Pengurangan stok (transaksi keluar & peminjaman) otomatis memprioritaskan batch yang paling mendekati expired (FEFO), lalu batch yang belum tercatat expired.
3. **Struktur Navigasi 4 Tab dalam 2 Kelompok Warna:**
   - **Kelompok 1 (Hijau - Stok & Transaksi):**
     - Tab 1: **Master Barang** (`tab=master`, default) — Katalog seluruh barang, stok per lokasi, status stok minimum & expired terdekat.
     - Tab 2: **Riwayat Transaksi** (`tab=history`) — Log mutasi barang masuk & keluar terpadu.
   - **Kelompok 2 (Kuning - Peminjaman):**
     - Tab 3: **Barang Terpinjam** (`tab=borrowed`) — Daftar peminjaman aktif yang belum selesai dikembalikan (`quantity_borrowed > quantity_returned`). Terdapat badge alert merah jika ada yang melewati tenggat (*overdue*).
     - Tab 4: **History Peminjaman** (`tab=borrow_history`) — Arsip peminjaman yang sudah lunas/selesai dikembalikan (`quantity_borrowed <= quantity_returned`).
4. **Sistem Peminjaman & Pengembalian Bertahap (*Multi-Step Return*):**
   - **Peminjaman langsung memotong stok di lokasi terpilih** secara atomik agar tidak terjadi *double-booking* stok.
   - Pengembalian mendukung bertahap (1..N kali cicil) yang dicatat ke tabel `inventory_borrowing_returns`.
   - Lokasi pengembalian fleksibel (bisa dipinjam dari Warehouse, tetapi dikembalikan ke Gudang HSSE).
   - Saat dikembalikan, sistem mengembalikan stok ke batch expired asalnya.
5. **Anti-Duplikasi Master Otomatis:**
   - Mencegah pembuatan baris master ganda untuk nama, kategori, dan satuan yang sama. Jika diinput ulang dengan stok awal > 0, stoknya otomatis ditambahkan ke master lama sebagai transaksi masuk batch baru.
6. **Master Satuan Dinamis (`inventory_units`):**
   - 12 satuan default, dapat ditambah, diubah namanya, dan diaktifkan/dinonaktifkan (*toggle*).

---

## 2. ATURAN HAK AKSES & ROLE-BASED ACCESS CONTROL (RBAC)

Modul Inventory memiliki matriks otorisasi khusus:

| Aksi / Fungsionalitas | Admin Management (`admin_management`) | Admin Inventory (`admin_inventory`) | User Biasa (`user`) |
|---|:---:|:---:|:---:|
| **Lihat Tab Master Barang & Stok** | ✅ Ya | ❌ Tidak (Akses dibatasi ke peminjaman) | ✅ Ya (Read-only) |
| **Tambah / Edit / Hapus Master Barang** | ✅ Ya | ❌ Ditolak | ❌ Ditolak |
| **Catat Transaksi Masuk & Keluar** | ✅ Ya | ❌ Ditolak | ❌ Ditolak |
| **Kelola Master Satuan** | ✅ Ya | ❌ Ditolak | ❌ Ditolak |
| **Catat Peminjaman Barang** | ✅ Ya | ✅ Ya | ❌ Ditolak |
| **Catat Pengembalian Barang (Return)** | ✅ Ya | ✅ Ya | ❌ Ditolak |
| **Ubah Rencana Jatuh Tempo (Due Date)** | ✅ Ya | ✅ Ya | ❌ Ditolak |
| **Export PDF / CSV Master & Transaksi** | ✅ Ya | ❌ Ditolak | ✅ Ya |
| **Export PDF Barang Terpinjam & History**| ✅ Ya | ✅ Ya | ✅ Ya |
| **Akses Modul Lain (Daily Work, Budget, dll)**| ✅ Ya | ❌ Ditolak via `not.inventory` | ✅ Ya |

---

## 3. SKEMA BASIS DATA & MIGRATIONS LENGKAP

### A. Tabel `inventory_units` (Master Satuan Dinamis)
```sql
CREATE TABLE `inventory_units` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
);
```

### B. Tabel `inventory_items` (Master Barang Logistik HSSE)
```sql
CREATE TABLE `inventory_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_code` VARCHAR(50) NOT NULL UNIQUE,      -- Kode otomatis e.g. "APD-0001", "APR-0002"
  `kode_barang` VARCHAR(50) NOT NULL,           -- Alias kompatibilitas
  `item_name` VARCHAR(255) NOT NULL,
  `nama_barang` VARCHAR(255) NOT NULL,          -- Alias kompatibilitas
  `category` VARCHAR(100) NOT NULL,
  `kategori` VARCHAR(100) NOT NULL,             -- Alias kompatibilitas
  `unit_measure` VARCHAR(50) NOT NULL,
  `satuan` VARCHAR(50) NOT NULL,                -- Alias kompatibilitas
  `stock` INT NOT NULL DEFAULT 0,               -- Total stok akumulasi
  `stok_sekarang` INT NOT NULL DEFAULT 0,
  `stock_warehouse` INT UNSIGNED NOT NULL DEFAULT 0, -- Stok di Warehouse
  `stock_hsse` INT UNSIGNED NOT NULL DEFAULT 0,      -- Stok di Gudang HSSE
  `minimum_stock` INT NOT NULL DEFAULT 0,       -- Batas peringatan restock
  `stok_minimum` INT NOT NULL DEFAULT 0,
  `location` VARCHAR(150) NULL,                 -- Ringkasan lokasi (Warehouse / Gudang HSSE / Warehouse & Gudang HSSE)
  `lokasi_default` VARCHAR(150) NULL,
  `condition` VARCHAR(100) NULL DEFAULT 'Baik',
  `description` TEXT NULL,
  `keterangan` TEXT NULL,
  `expired_date` DATE NULL,                     -- Tanggal expired terdekat yang aktif
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_items_code` (`item_code`, `kode_barang`),
  INDEX `idx_items_category` (`category`),
  INDEX `idx_items_stocks` (`stock_warehouse`, `stock_hsse`)
);
```

### C. Tabel `inventory_stock_batches` (Pemisahan Stok per Lokasi & Expired)
```sql
CREATE TABLE `inventory_stock_batches` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `inventory_item_id` BIGINT UNSIGNED NOT NULL,
  `location` VARCHAR(100) NOT NULL DEFAULT 'Warehouse', -- 'Warehouse' atau 'Gudang HSSE'
  `expired_date` DATE NULL,                             -- NULL = Belum Dicatat
  `quantity` INT UNSIGNED NOT NULL DEFAULT 0,
  `notes` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_stock_batches_lookup` (`inventory_item_id`, `location`, `expired_date`),
  FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE
);
```

### D. Tabel `inventory_transactions` (Buku Mutasi Barang Masuk & Keluar)
```sql
CREATE TABLE `inventory_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transaction_code` VARCHAR(60) NOT NULL UNIQUE, -- Auto: "TRX-YYYYMMDD-XXXX"
  `inventory_item_id` BIGINT UNSIGNED NOT NULL,
  `transaction_type` ENUM('Masuk', 'Keluar') NOT NULL,
  `jenis_transaksi` VARCHAR(50) NOT NULL,         -- 'masuk' atau 'keluar'
  `location` VARCHAR(100) NOT NULL DEFAULT 'Warehouse',
  `satuan` VARCHAR(50) NULL,
  `quantity` INT NOT NULL,
  `jumlah` INT NOT NULL,
  `transaction_date` DATE NOT NULL,
  `tanggal` DATE NOT NULL,
  `unit` VARCHAR(100) NULL,
  `person_in_charge` VARCHAR(150) NOT NULL,       -- Nama petugas penanggung jawab
  `petugas` VARCHAR(150) NOT NULL,
  `reference_number` VARCHAR(100) NULL,           -- No PO, Surat Jalan, dll
  `notes` TEXT NULL,
  `keterangan` TEXT NULL,
  `expired_date` DATE NULL,                       -- Expired date batch yang dicatat
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_trx_item_date` (`inventory_item_id`, `transaction_date`),
  FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items`(`id`) ON DELETE CASCADE
);
```

### E. Tabel `inventory_borrowings` (Transaksi Peminjaman Barang)
```sql
CREATE TABLE `inventory_borrowings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `borrow_code` VARCHAR(60) NOT NULL UNIQUE,     -- Auto: "PJM-YYMMDD-XXX"
  `inventory_item_id` BIGINT UNSIGNED NOT NULL,
  `location` VARCHAR(100) NOT NULL DEFAULT 'Warehouse', -- Lokasi asal barang dipinjam
  `quantity_borrowed` INT UNSIGNED NOT NULL,     -- Jumlah dipinjam
  `quantity_returned` INT UNSIGNED NOT NULL DEFAULT 0, -- Jumlah yang sudah kembali
  `borrower_name` VARCHAR(150) NOT NULL,         -- Nama peminjam
  `department` VARCHAR(150) NOT NULL,            -- Divisi / Departemen
  `borrow_date` DATETIME NOT NULL,
  `expected_return_date` DATE NULL,              -- Rencana tanggal pengembalian
  `due_date_history` JSON NULL,                  -- Log perpanjangan tanggal kembali
  `expired_date` DATE NULL,                      -- Expired date terdekat dari batch yang dipinjam
  `expired_batches` JSON NULL,                   -- Rincian batch & kuantitas tiap batch yang dipinjam
  `actual_return_date` DATETIME NULL,            -- Tanggal pengembalian terakhir
  `status` ENUM('Sedang Dipinjam', 'Sebagian Kembali', 'Melewati Tenggat', 'Sudah Dikembalikan') NOT NULL DEFAULT 'Sedang Dipinjam',
  `purpose` TEXT NULL,                           -- Keperluan pemakaian
  `notes` TEXT NULL,
  `recorded_by` VARCHAR(150) NOT NULL,           -- Petugas pencatat peminjaman
  `user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_borrowing_status_dates` (`status`, `expected_return_date`),
  FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
```

### F. Tabel `inventory_borrowing_returns` (Riwayat Pengembalian Bertahap 1..N)
```sql
CREATE TABLE `inventory_borrowing_returns` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `inventory_borrowing_id` BIGINT UNSIGNED NOT NULL,
  `return_number` INT UNSIGNED NOT NULL DEFAULT 1,  -- Tahap pengembalian (1, 2, 3...)
  `quantity_returned` INT UNSIGNED NOT NULL,        -- Jumlah unit yang dikembalikan pada tahap ini
  `return_date` DATETIME NOT NULL,                  -- Tanggal aktual pengembalian tahap ini
  `return_location` VARCHAR(100) NOT NULL DEFAULT 'Warehouse', -- Lokasi tempat barang dikembalikan
  `condition` VARCHAR(100) NULL DEFAULT 'Baik',     -- 'Baik', 'Rusak', 'Hilang'
  `recorded_by` VARCHAR(150) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_borrow_return_num` (`inventory_borrowing_id`, `return_number`),
  FOREIGN KEY (`inventory_borrowing_id`) REFERENCES `inventory_borrowings`(`id`) ON DELETE CASCADE
);
```

---

## 4. 8 KATEGORI MASTER & PREFIX OTOMATIS KODE BARANG

Sistem secara otomatis meng-generate kode unik berformat `PREFIX-0001` berdasarkan kategori barang:

| Kategori Barang | Prefix Kode | Contoh Kode | Sifat Barang |
|---|:---:|:---:|:---:|
| **APD (Alat Pelindung Diri)** | `APD` | `APD-0001` | Non-Consumable (Bisa Dipinjam) |
| **APD Habis Pakai** | `APD` | `APD-0012` | **Consumable** (Dilarang Dipinjam) |
| **Peralatan Pemadam (APAR & Hydrant)** | `APR` | `APR-0001` | Non-Consumable (Bisa Dipinjam) |
| **Peralatan P3K & Medis** | `MED` | `MED-0001` | **Consumable** (Dilarang Dipinjam) |
| **Rambu & Barikade K3 (Safety Sign)** | `SGN` | `SGN-0001` | Non-Consumable (Bisa Dipinjam) |
| **Perlengkapan Kerja di Ketinggian** | `HGT` | `HGT-0001` | Non-Consumable (Bisa Dipinjam) |
| **Detektor & Instrumen Lingkungan** | `DET` | `DET-0001` | Non-Consumable (Bisa Dipinjam) |
| **Peralatan Emergency & Rescue** | `EMG` | `EMG-0001` | Non-Consumable (Bisa Dipinjam) |
| *Kategori Lainnya / Default* | `INV` | `INV-0001` | Sesuai Sifat Barang |

---

## 5. DETAIL 4 TAB & FITUR REKAPITULASI

### TAB 1: MASTER BARANG (`tab=master`)
- **Tujuan:** Katalog persediaan, kontrol stok minimum, dan monitoring masa kedaluwarsa.
- **Kartu Metrik Atas:** Total Item Terdaftar, Total Stok Tersedia, Stok Gudang Warehouse, Stok Gudang HSSE, Peringatan Stok Rendah/Habis, Peringatan Mendekati Expired ($\le 30$ hari).
- **Kolom Tabel:** No, Kode Barang, Nama Barang, Kategori, Satuan, Stok Warehouse, Stok Gudang HSSE, Total Stok, Expired Date Terdekat (dengan badge status safe/warning/danger), Aksi.
- **Aksi Tiap Baris:**
  - `Lihat Stok & Expired` (Ikon jam pasir/kalender): Membuka `#modalLihatStokExpired` yang merinci stok per lokasi & per batch tanggal expired.
  - `Edit Barang` (Ikon pensil kuning): Membuka modal edit master.
  - `Hapus Barang` (Ikon tempat sampah merah): Konfirmasi hapus aman (dicegah jika barang memiliki riwayat transaksi/peminjaman).

### TAB 2: RIWAYAT TRANSAKSI (`tab=history`)
- **Tujuan:** Buku besar pergerakan barang masuk & keluar (*auditing log*).
- **Filter Bar:** Pencarian teks (kode, nama barang, PIC), filter Jenis Transaksi (`Semua`, `Masuk`, `Keluar`), filter Kategori, filter Lokasi (`Warehouse`, `Gudang HSSE`), filter Rentang Tanggal.
- **Kolom Tabel:** No, Kode Transaksi (`TRX-YYYYMMDD-XXXX`), Tanggal, Nama Barang, Jenis Transaksi (Badge Hijau Masuk / Merah Keluar), Lokasi Penyimpanan, Kuantitas, Satuan, Tanggal Expired Batch, PIC / Petugas, Keterangan, Aksi Detail.

### TAB 3: BARANG TERPINJAM (`tab=borrowed`)
- **Tujuan:** Monitoring peminjaman aktif yang masih beredar di lapangan.
- **Filter Bar:** Pencarian teks (kode pinjam, nama peminjam, divisi, nama barang), filter Lokasi, filter Status (`Semua`, `Sedang Dipinjam`, `Melewati Tenggat`), filter Rentang Tanggal Pinjam, filter Rentang Jatuh Tempo.
- **Badge Indikator Tab:** Menampilkan counter merah menyala jika ada barang yang melewati batas rencana kembali (*overdue alert*).
- **Kolom Tabel:** No, Kode Peminjaman (`PJM-YYMMDD-XXX`), Nama Barang, Lokasi Asal, Jumlah Dipinjam, Jumlah Dikembalikan, Sisa Belum Kembali, Nama Peminjam & Divisi, Tanggal Pinjam, Rencana Kembali, Status (Badge `Sedang Dipinjam` atau `Melewati Tenggat`), Aksi.
- **Aksi Tiap Baris:**
  - `Detail Peminjaman` (Ikon mata): Menampilkan riwayat peminjaman, kelompok batch expired yang dipinjam, dan tabel kronologis pengembalian bertahap.
  - `Catat Pengembalian` (Ikon panah kembali hijau): Membuka `#modalKembalikanBarang`.
  - `Ubah Rencana Kembali` (Ikon kalender jam): Membuka `#modalEditDueDate` untuk memperpanjang jatuh tempo.

### TAB 4: HISTORY PEMINJAMAN (`tab=borrow_history`)
- **Tujuan:** Arsip peminjaman yang sudah selesai 100% dikembalikan.
- **Filter Bar:** Pencarian peminjam/barang, filter Departemen, filter Barang, filter Lokasi, filter Tanggal Pinjam, filter Tanggal Pengembalian Selesai.
- **Kolom Tabel:** No, Kode Peminjaman, Nama Barang, Lokasi Asal, Total Dipinjam, Total Dikembalikan (Lunas), Nama Peminjam & Divisi, Tanggal Pinjam, Tanggal Selesai Dikembalikan, Dokumen Riwayat, Aksi Detail.

---

## 6. DETAIL 11 MODAL FORM & INTERAKSI PENGGUNA

### 1. `#modalTambahBarang` (Tambah Master Barang Baru)
- **Field:** Kode Barang (*auto/manual unique*), Nama Barang (*req*), Kategori (*select 8 kategori, req*), Satuan (*select dari master satuan, req*), Stok Awal Warehouse (*int min 0*), Stok Awal Gudang HSSE (*int min 0*), Stok Minimum (*int min 0*), Tanggal Expired Awal (*date, opsional*), Keterangan (*req*).
- **Logika:** Jika nama, kategori, dan satuan sama dengan barang yang sudah ada:
  - Jika total stok > 0: otomatis menambah stok master lama dan mencatat transaksi masuk batch baru (anti-duplikasi).
  - Jika total stok = 0: tolak dengan pesan bahwa master barang sudah terdaftar.

### 2. `#modalEditMasterGlobal` (Ubah Data Master Barang)
- **Field:** Kode Barang (*readonly*), Nama Barang (*req*), Kategori (*req*), Satuan (*req*), Stok Minimum (*req*), Keterangan (*req*).
- *Catatan:* Stok fisik tidak diedit di form ini; penambahan/pengurangan stok wajib melalui form Transaksi Resmi demi integritas audit trail.

### 3. `#modalKelolaSatuan` (Master Satuan Dinamis)
- **Fitur:**
  - Form input tambah nama satuan baru.
  - Tabel daftar satuan lengkap dengan status aktif/nonaktif.
  - Tombol toggle aktif/nonaktif (*soft disable*: satuan nonaktif tidak muncul di form baru, tetapi data lama tidak rusak).
  - Tombol edit nama dan tombol hapus satuan.

### 4. `#modalTransaksiTerpadu` (Catat Transaksi Mutasi Stok)
- **Fitur Multi-Item Dinamis:** Pengguna dapat mencatat transaksi untuk 1 hingga banyak barang sekaligus dalam satu formulir penyerahan.
- **Field Header:** Jenis Transaksi (`Masuk` / `Keluar`), Tanggal Transaksi (*date, req*), Petugas Pencatat (*req*), No Referensi/Surat Jalan (*opsional*), Keterangan Umum.
- **Tabel Baris Barang (Dinamis `+ Tambah Baris`):**
  - Kolom: Pilih Barang (*searchable select*), Lokasi (*Warehouse* / *Gudang HSSE*), Satuan, Kuantitas (*int min 1*), Tanggal Expired (jika barang masuk) atau Dropdown Batch Expired (jika barang keluar), Tombol Hapus Baris.
- **Validasi Transaksi Keluar:** Sistem memeriksa ketersediaan stok di lokasi yang dipilih. Jika kuantitas diminta > stok di lokasi tersebut, sistem membatalkan dan menampilkan notifikasi stok tidak cukup.

### 5. `#modalPinjamBarang` (Form Peminjaman Barang)
- **Field:**
  - Pilih Barang: Otomatis menampilkan sisa stok Warehouse dan Gudang HSSE.
  - Lokasi Penyimpanan: Radio / Select lokasi tempat barang diambil.
  - Jumlah Peminjaman: Input angka, maksimum otomatis dibatasi oleh stok yang tersedia di lokasi terpilih.
  - Pilihan Batch Expired (jika barang memiliki beberapa batch kedaluwarsa).
  - Nama Peminjam (*req*), Departemen / Unit (*req*), Tanggal Pinjam (*req*), Rencana Tanggal Pengembalian (*req, date after_or_equal:borrow_date*), Keperluan Pemakaian (*req*), Petugas Pencatat (*req*), Catatan Tambahan.
- **Proteksi Barang Habis Pakai:** Jika user memilih barang berkategori *APD Habis Pakai* atau *P3K & Medis*, form memblokir peminjaman dengan instruksi: *"Barang habis pakai tidak dapat dipinjam. Gunakan menu Catat Transaksi Keluar"*.
- **Efek Atomik:** Stok di lokasi dan batch yang dipilih langsung terpotong saat peminjaman dibuat.

### 6. `#modalKembalikanBarang` (Pengembalian Bertahap & Penuh)
- **Field:**
  - Kode Peminjaman & Nama Barang (*display*).
  - Sisa yang Belum Dikembalikan (*display badge*).
  - Jumlah yang Dikembalikan Saat Ini (*req, min 1, max: sisa belum kembali*).
  - Tanggal Pengembalian Aktual (*date, req*).
  - Lokasi Penerimaan Pengembalian (*Warehouse* / *Gudang HSSE*, default lokasi asal pinjam).
  - Kondisi Barang Saat Kembali (*select: Baik, Rusak, Hilang*).
  - Petugas Penerima (*req*), Catatan Pengembalian (*opsional*).
- **Logika:**
  - Jika jumlah kembali = sisa: status otomatis menjadi `Sudah Dikembalikan` dan berpindah ke Tab History.
  - Jika jumlah kembali < sisa: status menjadi `Sebagian Kembali` (tetap di Tab Barang Terpinjam dengan counter sisa berkurang).
  - Stok bertambah kembali di lokasi penerimaan dan dialokasikan ke batch expired asalnya.

### 7. `#modalEditDueDate` (Perpanjangan Rencana Pengembalian)
- **Field:** Rencana Tanggal Pengembalian Baru (*date, req*), Alasan Perpanjangan (*req*).
- **Logika:** Menyimpan tanggal lama, tanggal baru, alasan, user pengubah, dan timestamp ke dalam array JSON `due_date_history`.

### 8. `#modalLihatStokExpired` (Rincian Stok & Batch Expired)
- **Tampilan:** Menampilkan kartu stok Warehouse, kartu stok Gudang HSSE, dan tabel rincian batch kedaluwarsa dengan badge warna:
  - `Safe` (Hijau) jika expired > 30 hari lagi.
  - `Mendekati Expired (X hari)` (Kuning/Oranye) jika expired $\le 30$ hari.
  - `Expired Hari Ini` (Merah).
  - `Sudah Expired` (Merah Gelap).
  - `Belum Dicatat` (Abu-abu netral).

### 9. `#modalDetailBorrowing` (Detail Lengkap Peminjaman)
- **Tampilan:**
  - Rincian peminjam, departemen, keperluan, tanggal pinjam, rencana kembali, status.
  - Rincian kelompok batch expired yang dipinjam.
  - **Tabel Kronologis Pengembalian Bertahap:** Menampilkan baris Pengembalian Ke-1, Ke-2, dst., beserta tanggal, kuantitas kembali, kondisi barang, dan petugas penerima.
  - Log riwayat perpanjangan tenggat waktu pengembalian.

### 10. `#modalDetailTransaction` (Detail Transaksi Mutasi)
- **Tampilan:** Kode transaksi, jenis mutasi, nama barang, kode barang, lokasi, kuantitas, tanggal, petugas PIC, nomor referensi, dan catatan.

### 11. `#modalKonfirmasiHapus` (Guard Penghapusan Aman)
- **Proteksi Integritas:** Master barang yang sudah memiliki riwayat mutasi atau peminjaman **tidak dapat dihapus** guna mencegah kerusakan data buku besar logistik.

---

## 7. FITUR EKSPOR LAPORAN (PDF LANDSCAPE A4 & CSV)

Fitur ekspor otomatis menyinkronkan seluruh parameter pencarian dan filter aktif dari tab yang sedang dibuka:

1. **Ekspor Master Barang:**
   - PDF: `Laporan_Master_Barang.pdf` (`exports/inventory-master-pdf.blade.php`).
   - CSV: `Laporan_Master_Barang.csv` (Streaming UTF-8).
2. **Ekspor Riwayat Transaksi:**
   - PDF: `Laporan_Riwayat_Transaksi.pdf` (`exports/inventory-history-pdf.blade.php`).
   - CSV: `Laporan_Riwayat_Transaksi.csv`.
3. **Ekspor Barang Terpinjam (Aktif):**
   - PDF: `Laporan_Barang_Terpinjam.pdf` (`exports/inventory-borrowed-pdf.blade.php`).
   - Khusus memuat data peminjaman yang masih aktif berjalan.
4. **Ekspor History Peminjaman (Selesai):**
   - PDF: `Laporan_History_Peminjaman.pdf` (`exports/inventory-borrow-history-pdf.blade.php`).
   - Khusus memuat data peminjaman yang sudah lunas/selesai dikembalikan.

---

## 8. MATRIKS ROUTING LENGKAP (`routes/web.php`)

```php
Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {
    // 1. Tampilan Utama & Ekspor (Semua Role Terotentikasi)
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/export', [InventoryController::class, 'export'])->name('export')->middleware('can.export');
    Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions')->middleware('not.inventory');
    Route::get('/search-items', [InventoryController::class, 'searchItems'])->name('search-items');
    Route::get('/units', [InventoryController::class, 'getUnits'])->name('units.index')->middleware('admin');

    // 2. Transaksi Master Barang & Mutasi Stok (Khusus Admin Management)
    Route::middleware(['admin'])->group(function () {
        Route::post('/', [InventoryController::class, 'storeItem'])->name('store');
        Route::put('/{inventory}', [InventoryController::class, 'updateItem'])->name('update');
        Route::delete('/{inventory}', [InventoryController::class, 'destroyItem'])->name('destroy');
        Route::post('/transactions', [InventoryController::class, 'storeTransaction'])->name('transactions.store');

        // CRUD Master Satuan
        Route::post('/units', [InventoryController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{unit}', [InventoryController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units/{unit}', [InventoryController::class, 'destroyUnit'])->name('units.destroy');
        Route::post('/units/{unit}/toggle', [InventoryController::class, 'toggleUnit'])->name('units.toggle');
    });

    // 3. Peminjaman & Pengembalian Barang (Admin Management & Admin Inventory)
    Route::middleware(['role:admin_management,admin_inventory'])->group(function () {
        Route::post('/borrowings', [InventoryController::class, 'storeBorrowing'])->name('borrowings.store');
        Route::post('/borrowings/{borrowing}/return', [InventoryController::class, 'returnBorrowing'])->name('borrowings.return');
        Route::match(['post', 'put', 'patch'], '/borrowings/{borrowing}/due-date', [InventoryController::class, 'updateBorrowingDueDate'])->name('borrowings.due-date');
    });
});
```

---

## 9. INSTRUKSI GENERASI KODE (PROMPT AKHIR UNTUK AI ENGINE)

> Salin dan jalankan prompt instruksi di bawah ini ke AI Engine / Code Assistant Anda untuk membuat seluruh kode modul Inventory secara utuh di project master:

```text
Buatkan implementasi lengkap modul Inventory HSSE (Manajemen Logistik & Peminjaman APD) Laravel 11/12 sesuai dokumen spesifikasi teknis di atas:

1. Buat migration database lengkap untuk 6 tabel:
   - `inventory_units` (satuan standar awal dan status aktif)
   - `inventory_items` (stok dual lokasi: stock_warehouse dan stock_hsse, minimum stock, expired date)
   - `inventory_stock_batches` (pemecahan stok per lokasi & kelompok expired)
   - `inventory_transactions` (buku mutasi transaksi masuk/keluar)
   - `inventory_borrowings` (peminjaman barang, kuantitas pinjam/kembali, status, json batch & due_date_history)
   - `inventory_borrowing_returns` (riwayat pengembalian bertahap 1..N)

2. Buat Model-Model Eloquent lengkap:
   - `InventoryItem.php`: Auto-generator kode barang unik per kategori, booted hook sinkronisasi dual-lokasi stock, relasi batches/transactions/borrowings, method addBatchStock, deductBatchStock (FEFO), getStockExpiryGroups, dan check canBeDeleted.
   - `InventoryStockBatch.php`: Relasi item, helper evaluateExpiryStatus (Safe, Mendekati Expired <= 30 hari, Expired Hari Ini, Expired, Belum Dicatat).
   - `InventoryTransaction.php`: Auto-generator kode transaksi TRX-YYYYMMDD-XXXX, booted hook normalisasi kolom ganda.
   - `InventoryBorrowing.php`: Auto-generator kode PJM-YYMMDD-XXX, booted hook potong stok otomatis saat peminjaman dibuat, accessor remaining_quantity, is_overdue, displayStatus, expiryDetails.
   - `InventoryBorrowingReturn.php`: Relasi borrowing, pencatatan pengembalian bertahap.
   - `InventoryUnit.php`: Scope active, 12 satuan bawaan, findByName.

3. Buat `InventoryController.php` dengan implementasi lengkap:
   - Method `index` yang mendukung 4 tab (`master`, `history`, `borrowed`, `borrow_history`) dan ringkasan metrik statistik.
   - Method `storeItem` dengan proteksi anti-duplikasi otomatis (menggabungkan stok jika nama/kategori/satuan sama).
   - Method `updateItem` dan `destroyItem` (dengan guard canBeDeleted).
   - Method `storeTransaction` yang mendukung penambahan multi-barang sekaligus dan pemotongan stok FEFO.
   - Method `storeBorrowing` dengan validasi stok per lokasi, proteksi barang consumable/habis pakai, dan pemotongan stok batch atomik.
   - Method `returnBorrowing` yang mendukung pengembalian bertahap (multi-step return) dan pemulihan stok ke batch expired asalnya.
   - Method `updateBorrowingDueDate` yang mencatat log perpanjangan ke json `due_date_history`.
   - Method CRUD Master Satuan (`getUnits`, `storeUnit`, `updateUnit`, `destroyUnit`, `toggleUnit`).
   - Method `export` PDF Landscape A4 (DomPDF) dan CSV stream untuk ke-4 tab.

4. Buat Blade view `resources/views/inventory/index.blade.php` lengkap dengan:
   - Navigasi 4 Tab dalam 2 kelompok warna (Hijau: Stok/Transaksi, Kuning: Peminjaman).
   - Kartu-kartu metrik statistik dan alert counter peminjaman overdue.
   - Tabel dinamis untuk masing-masing tab dengan badge status responsif.
   - Seluruh 11 Modal Form interaktif (Tambah Barang, Edit Master, Kelola Satuan, Transaksi Multi-Item Masuk/Keluar, Peminjaman Barang, Pengembalian Barang, Perpanjang Due Date, Lihat Stok Expired, Detail Peminjaman, Detail Transaksi, Konfirmasi Hapus).
   - Script JavaScript untuk live-search barang, kalkulasi sisa pengembalian, render tabel satuan, dan validasi dinamis.

5. Buat 4 Template PDF Landscape A4 di `resources/views/exports/`:
   - `inventory-master-pdf.blade.php`
   - `inventory-history-pdf.blade.php`
   - `inventory-borrowed-pdf.blade.php`
   - `inventory-borrow-history-pdf.blade.php`

Pastikan kode rapi, bebas bug, transaksional menggunakan DB::transaction(), dan mengikuti konvensi Laravel 11/12 standar.
```

