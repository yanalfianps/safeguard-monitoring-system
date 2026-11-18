### TASK INSTRUCTION: Penyesuaian UI & Export PDF pada Menu Incident

#### 1. Ringkasan & Objektif

Lakukan tiga penyesuaian spesifik pada modul **Incident**:
1. Menghapus teks deskripsi/subtitle di bawah judul grafik "Tren Kecelakaan Kerja".
2. Menghapus field "Target Penyelesaian" pada popup/modal Detail Investigasi Insiden.
3. Menghapus kolom "Keparahan" pada tabel hasil Export PDF insiden dan menyesuaikan ulang lebar kolom tabelnya agar tetap proporsional.

#### 2. Strict Coding & Scope Rules (PENTING)

1. **Isolated Changes:** HANYA lakukan modifikasi pada file *views* yang berada di dalam lingkup menu Incident (contoh: `resources/views/incident/...` dan template PDF-nya, misal `resources/views/incident/pdf.blade.php` atau sejenisnya).
2. **No Backend Breaking:** Dilarang merubah struktur tabel database, query utama, atau logika simpan/update data. Perubahan murni pada *presentation layer* (UI HTML/Blade & template PDF).
3. **PDF Layout Adjustment:** Saat menghapus satu kolom di PDF, wajib mendistribusikan sisa *width* (lebar) ke kolom lain (misalnya judul insiden atau kategori) agar tabel PDF tetap *full width* dan rapi.

#### 3. Detail Perubahan (UI View & Ekspor PDF)

**A. Menghapus Deskripsi "Tren Kecelakaan Kerja"**
1. **Lokasi Target:** File view utama halaman Incident (kemungkinan di `index.blade.php` bagian statistik/grafik).
2. **Tindakan:**
   * Cari teks: `"Menampilkan tren jumlah kecelakaan kerja berdasarkan klasifikasi area kejadian dalam periode yang dipilih."`
   * Hapus elemen HTML (seperti tag `<p>`, `<span>`, atau `<small>`) yang membungkus teks tersebut.
   * Pastikan *margin* atau *padding* di bawah judul "Tren Kecelakaan Kerja" tetap rapi (tidak terlalu renggang) setelah teks dihapus.

**B. Menghapus "Target Penyelesaian" di Modal Detail**
1. **Lokasi Target:** File view yang menangani modal popup Detail Investigasi Insiden (misalnya `show.blade.php`, `detail-modal.blade.php`, atau bagian dari `index.blade.php`).
2. **Tindakan:**
   * Cari blok HTML (biasanya dibungkus `<div>` grid atau list) yang memuat label `"Target Penyelesaian:"`.
   * Hapus secara keseluruhan struktur pembungkus (wrapper `<div>`) yang memuat label dan nilai data tersebut.
   * Pastikan layout grid di dalam modal tetap utuh dan tidak ada *space* kosong yang janggal.

**C. Menghapus Kolom "Keparahan" di Export PDF**
1. **Lokasi Target:** File view template PDF untuk Incident (misalnya `export-pdf.blade.php`, `print.blade.php`, atau file sejenis yang merender tabel PDF).
2. **Tindakan (Header):** 
   * Cari tag `<th>` dengan tulisan `KEPARAHAN` dan hapus tag tersebut.
3. **Tindakan (Body):**
   * Di dalam *looping* data (`@foreach`), cari tag `<td>` yang menampilkan label *Low/Medium/High* (berkaitan dengan keparahan). Hapus tag `<td>` tersebut.
4. **Penyesuaian Lebar Kolom (Width):**
   * Periksa deklarasi *width* (bisa inline CSS style `width: ...%` pada tag `<th>`).
   * Karena satu kolom dihapus, tambahkan persentase *width* yang hilang tersebut ke kolom lain yang membutuhkan ruang lebih (misalnya kolom `JUDUL INSIDEN` atau `KATEGORI`). Pastikan total lebar tetap 100%.

#### 4. Output yang Diharapkan

1. Tampilkan cuplikan kode Blade yang menunjukkan teks deskripsi grafik sudah dihapus.
2. Tampilkan cuplikan kode modal/popup Blade untuk membuktikan bagian "Target Penyelesaian" sudah dibersihkan.
3. Tampilkan struktur tabel (`<thead>` dan sebagian `<tbody>`) dari file template PDF untuk memastikan kolom Keparahan hilang dan *width* kolom lainnya sudah disesuaikan.