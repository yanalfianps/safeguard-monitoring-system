### TASK INSTRUCTION: Penyesuaian UI Menu Inventory (Popup Batal, Ikon Lokasi, & Warna Tombol)

#### 1. Ringkasan & Objektif
Lakukan tiga penyesuaian antarmuka (UI) khusus pada modul **Inventory** (stok & mutasi barang):
1. Mengganti *native browser alert/confirm* saat menutup form transaksi menjadi popup modal kustom yang modern (seperti SweetAlert2 atau modal bawaan template).
2. Menghapus ikon lokasi pada tabel Riwayat Transaksi agar hanya menampilkan teks saja.
3. Memberikan warna *state active* merah muda (*light red*) pada tombol pilihan "Barang Keluar" di form pencatatan mutasi barang, setara dengan *state active* hijau pada "Barang Masuk".

#### 2. Strict Coding & Scope Rules (PENTING)
1. **Isolated Changes:** HANYA lakukan modifikasi pada file *views* atau komponen Javascript yang berada di dalam lingkup menu Inventory (contoh: `resources/views/inventory/...`). JANGAN merubah file controller, database, atau view dari modul lain.
2. **UI Consistency:** Gunakan *library* popup yang sudah ada di dalam sistem (misalnya `Swal.fire` jika menggunakan SweetAlert2, atau komponen modal Alpine.js/Bootstrap/Tailwind yang sudah terdaftar).
3. **No Breaking Logic:** Perubahan pada tombol "Barang Keluar" murni hanya pada penambahan *class* CSS/Tailwind (kosmetik), jangan merubah *value* input radio/checkbox-nya.

#### 3. Detail Perubahan Tampilan (UI View & Script)

**A. Mengganti Popup Native ke Custom (Form Catat Transaksi)**
1. **Lokasi Target:** File view atau file JS yang menangani *event* klik pada tombol tutup `(X)` atau tombol `Batal` di dalam form "Catat Transaksi Mutasi Barang".
2. **Tindakan:**
   - Cari *script* yang menggunakan `confirm("Perubahan pada formulir transaksi belum disimpan...")`.
   - Ganti pemanggilan *native browser* tersebut menggunakan SweetAlert2 (atau library kustom yang digunakan project ini).
   - Contoh implementasi (jika pakai Swal):
     ```javascript
     Swal.fire({
         title: 'Batalkan Pengisian?',
         text: "Perubahan pada formulir transaksi belum disimpan. Apakah Anda yakin ingin menutup form ini?",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: 'Ya, Tutup',
         cancelButtonText: 'Batal'
     }).then((result) => {
         if (result.isConfirmed) {
             // Eksekusi fungsi penutup form / reset form
             closeModal();
         }
     });
     ```

**B. Menghapus Ikon Lokasi (Tabel Riwayat Transaksi)**
1. **Lokasi Target:** File view yang memuat tabel "Riwayat Transaksi Mutasi Barang".
2. **Tindakan:**
   - Cari blok kode tabel (`<tr>` -> `<td>`) untuk kolom "Lokasi".
   - Di dalam tag pembungkus lokasi (kemungkinan berupa `<div>` atau tag `<a>` berisikan badge), **HAPUS** elemen ikonnya (bisa berupa tag `<svg>`, `<i class="...">`, atau komponen `<x-icon>`).
   - Sisakan hanya teks *dynamic* lokasinya saja (misalnya `{{ $item->lokasi }}`). 
   - Pastikan *padding* atau margin di dalam `<td>` tersebut disesuaikan agar teks tetap rata secara estetik (misal menghapus margin kiri yang ditinggalkan oleh ikon).

**C. Pewarnaan State Active Tombol "Barang Keluar"**
1. **Lokasi Target:** File view form "Catat Transaksi Mutasi Barang", pada bagian input "Jenis Transaksi" (Barang Masuk / Barang Keluar).
2. **Tindakan:**
   - Pada opsi "Barang Keluar", berikan *class binding* atau *conditional class* (Tailwind) saat kondisinya dipilih (aktif).
   - Terapkan warna *background* dan teks yang seirama dengan "Barang Masuk", namun bernuansa merah.
   - Contoh Class Tailwind saat tombol "Barang Keluar" aktif: `bg-red-50 border-red-300 text-red-700` (atau variasi *light red* lainnya sesuai *palette*).
   - Pastikan ikon *upload/download* di dalam tombol tersebut juga mengikuti warna *state* aktifnya.

#### 4. Output yang Diharapkan
1. Cuplikan kode JavaScript (SweetAlert/Kustom) yang menggantikan *native confirm*.
2. Cuplikan kode Blade pada tabel kolom Lokasi yang menunjukkan bahwa tag ikon sudah dihapus.
3. Cuplikan kode Blade pada tombol "Barang Keluar" yang menunjukkan penambahan logika kelas CSS (*conditional styling*) berwarna merah muda.