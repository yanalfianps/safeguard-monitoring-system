### TASK INSTRUCTION: Penyesuaian Final Tanggal Target Proyek & Pembersihan Deskripsi UI Dashboard

#### 1. Ringkasan & Objektif

Lakukan penyesuaian tahap akhir pada halaman Dashboard utama. Objektif pertama adalah merevisi tanggal target selesai proyek menjadi "28 Maret 2028" dan memastikan semua kalkulasi waktu (Hari, Minggu, Bulan) menyesuaikan dengan tanggal baru tersebut. Objektif kedua adalah membersihkan antarmuka (UI) dengan menghapus beberapa teks deskripsi kecil di bawah card "Indikator HSSE" dan "Monitoring Anggaran" untuk menghasilkan tampilan yang lebih *clean* dan modern.

#### 2. Strict Coding & Scope Rules (PENTING)

1. **Target File:** Perubahan mutlak HANYA dilakukan pada `DashboardController.php` (untuk logika waktu) dan `resources/views/dashboard/index.blade.php` (untuk penghapusan teks HTML).
2. **Safe Deletion:** Hapus teks deskripsi yang diminta secara presisi. JANGAN menghapus class Tailwind yang mengatur *padding*, *margin*, atau *border* dari elemen induk (container) teks tersebut agar struktur *card* tidak *collapse* atau rusak.
3. **Dynamic Recalculation:** Pastikan perubahan tanggal di Controller otomatis merubah perhitungan total durasi dan sisa waktu tanpa harus merubah logika perhitungannya.

#### 3. Logika Controller (Carbon)

Buka file `DashboardController.php`. Cari bagian yang mendefinisikan batas waktu proyek (`$end_date`), dan lakukan perubahan berikut:

*   **Ubah Tanggal Selesai:**
    Ubah kode `\Carbon\Carbon::parse('2028-04-28');`
    Menjadi: `$end_date = \Carbon\Carbon::parse('2028-03-28');`

*   Pastikan kalkulasi turunan di bawahnya (seperti `$total_days`, `$rem_days`, `$total_weeks`, `$rem_weeks`, `$total_months`, `$rem_months`, dan perhitungan persentase) tetap berjalan normal menggunakan nilai `$end_date` yang baru ini.

#### 4. Detail Perubahan Tampilan (UI View & Blade)

Buka file `index.blade.php` pada folder view dashboard dan lakukan penghapusan teks berikut:

**A. Bagian "Progress Waktu Proyek Dieng 2"**
1. Jika teks tanggal target selesai masih di-*hardcode* di tampilan (misal pada header atau teks pojok kanan bawah), pastikan teksnya diperbarui dari "28 April 2028" menjadi "28 Maret 2028" (atau pastikan ia merender variabel `{{ $end_date->translatedFormat('d F Y') }}`).
2. Nilai rasio pada kotak Hari, Minggu, dan Bulan akan otomatis menyesuaikan dari controller.

**B. Bagian "Indikator HSSE"**
1. Cari teks deskripsi kecil di bagian paling bawah *card* Indikator HSSE (biasanya di-apit tag `<p>` atau `<span>` dengan warna teks abu-abu/terang dan ikon checklist).
2. Hapus baris kode HTML yang berisi teks: `"Metrik keselamatan kerja terintegrasi insiden & jam kerja"`.
3. Jika teks tersebut berada di dalam sebuah div/footer khusus yang memisahkannya dengan garis pembatas (border-t), pertimbangkan untuk menghapus keseluruhan div footer tersebut agar *card* tampak bersih.

**C. Bagian "Monitoring Anggaran"**
1. **Deskripsi Card Utama:** Cari dan hapus teks `"Data pagu anggaran dan realisasi WBS proyek"` di bagian bawah *card* utama (sebelah ikon info/lingkaran 'i'). Hapus div footernya jika perlu.
2. **Deskripsi 3 Kotak Kecil:** Masuk ke bagian dalam *card* Monitoring Anggaran (kotak BUDGET, ASSIGNED, AVAILABLE). Cari dan **HAPUS** elemen HTML yang memuat teks berikut pada masing-masing kotak:
   * Pada kotak BUDGET: hapus teks `"Total pagu anggaran aktif seluruh WBS"`
   * Pada kotak ASSIGNED: hapus teks `"Total PO + Non-PO Expenses"`
   * Pada kotak AVAILABLE: hapus teks `"Budget - Assigned"`

#### 5. Output yang Diharapkan

1. Tampilkan cuplikan kode di `DashboardController.php` yang menunjukkan perubahan variabel `$end_date` menjadi bulan Maret 2028.
2. Tampilkan modifikasi HTML/Blade pada *card* "Indikator HSSE" dan "Monitoring Anggaran" yang membuktikan bahwa elemen teks deskripsi bawahnya sudah bersih terhapus tanpa merusak *layout grid* atau *border*-nya.