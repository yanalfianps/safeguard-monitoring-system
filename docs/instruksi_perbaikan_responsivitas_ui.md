### TASK INSTRUCTION: Perbaikan Responsivitas UI (Responsive Design) pada Card Dashboard

#### 1. Ringkasan & Objektif

Perbaiki masalah tata letak "tergencet" dan teks/angka yang turun ke bawah (ter-enter) pada layar laptop berukuran kecil. Fokus perbaikan ada pada card "Indikator HSSE" dan "Monitoring Anggaran" di menu Dashboard. Gunakan utilitas responsif Tailwind CSS (`md:`, `lg:`, `xl:`, `truncate`, `whitespace-nowrap`) agar tampilan tetap proporsional dan rapi di semua ukuran layar.

#### 2. Strict Coding & Scope Rules

1. **Target File:** HANYA modifikasi class CSS/Tailwind pada file view `resources/views/dashboard/index.blade.php`.

2. **No Logic Changes:** JANGAN merubah variabel data, logika query, atau arsitektur backend.

3. **Responsive Scaling:** Pastikan angka nominal dan teks panjang menyesuaikan ukuran font secara otomatis berdasarkan lebar layar, dan cegah pembungkusan (wrapping) paksa pada angka penting.

#### 3. Detail Perubahan Tampilan (UI View & Blade)

##### A. Wrapper Utama (Pembungkus "Indikator HSSE" & "Monitoring Anggaran")

* Temukan *container grid* yang membungkus kedua card besar ini agar berdampingan.

* Kemungkinan saat ini menggunakan `lg:grid-cols-2`. Ubah *breakpoint*-nya menjadi **`xl:grid-cols-2`**.
  *(Penjelasan: Layar laptop kecil (1366px ke bawah) biasanya masuk kategori `lg`. Dengan diubah ke `xl`, pada laptop kecil kedua card ini akan tersusun rapi atas-bawah, dan hanya akan berdampingan kiri-kanan pada layar monitor/laptop lebar).*

##### B. Card "Monitoring Anggaran" (Mencegah Angka Ter-enter)

* Pada bagian angka nominal (`$total_budget`, `$total_assigned`, `$total_available`):

  1. Tambahkan class **`whitespace-nowrap`** agar deretan angka dan "Rp" tidak pernah turun ke baris baru.

  2. Tambahkan class **`truncate`** (opsional) agar jika benar-benar tidak muat, ia akan diakhiri dengan titik-titik (ellipsis) tanpa merusak kotak.

  3. Terapkan ukuran font dinamis: **`text-lg lg:text-xl xl:text-2xl tracking-tight`**.

##### C. Card "Indikator HSSE" (Menyesuaikan Teks Judul Panjang)

* Pada judul kotak tengah: **"TOTAL LOST TIME INJURY (LTI)"**, pastikan class-nya menggunakan penyesuaian teks yang rapi.

  * Tambahkan class **`text-center text-[10px] sm:text-xs xl:text-sm leading-tight`** agar saat di layar kecil, ukuran font-nya sedikit mengecil dan jarak antar barisnya merapat, sehingga tidak terlihat memakan tempat.

* Pada bagian angka indikator (`TRIR`, `LTI-FR`, `LTI-SR`):

  * Tambahkan penyesuaian font seperti **`text-base lg:text-lg xl:text-xl font-bold whitespace-nowrap`**.

##### D. Inner Padding & Gap

* Kurangi *gap* (jarak) dan *padding* antar kotak kecil di dalam card jika berada di layar `lg` ke bawah. Misalnya: ubah `p-4` menjadi `p-2 sm:p-3 xl:p-4`, dan `gap-4` menjadi `gap-2 xl:gap-4`.

#### 4. Output yang Diharapkan

Tampilkan modifikasi kode HTML/Blade (fokus pada penambahan class Tailwind responsif) untuk bagian wrapper utama, angka di Monitoring Anggaran, dan teks di Indikator HSSE.