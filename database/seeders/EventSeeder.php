<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventCompany;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || !filter_var(env('ALLOW_DESTRUCTIVE_SEEDING', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('EventSeeder diblokir agar tidak menghapus/menimpa data events operasional. Set ALLOW_DESTRUCTIVE_SEEDING=true di non-produksi jika sengaja ingin mengisi data sampel.');
            return;
        }

        $now = Carbon::now('Asia/Jakarta');

        // 1. Bersihkan data events, dokumen terkait, dan log aktivitas modul events agar tepat 75 data realistis
        Document::whereIn('module', ['events', 'Events'])->delete();
        ActivityLog::whereIn('module', ['events', 'Events'])->delete();
        Event::query()->delete();

        // 2. Pastikan daftar Perusahaan / Instansi master terdaftar
        $companies = [
            'EBTKE',
            'Kementerian ESDM',
            'Dinas Tenaga Kerja',
            'PT Pertamina EP',
            'PT Geo Dipa Energi (Persero)',
            'ADB Mission',
            'PLN',
            'Dinas Lingkungan Hidup (DLH)',
            'BPBD Kabupaten Banjarnegara',
            'BUJP Security Mitra',
        ];

        foreach ($companies as $compName) {
            EventCompany::firstOrCreate(
                ['name' => $compName],
                ['category' => 'Instansi Mitra', 'is_active' => true]
            );
        }

        // 3. Katalog 75 Kegiatan Realistis terbagi rata di 4 Fungsi Safeguard:
        //    - Health & Safety (19 kegiatan)
        //    - Security (19 kegiatan)
        //    - Environment (19 kegiatan)
        //    - Social (18 kegiatan)
        $templates = [
            // ================= HEALTH & SAFETY (19 Data) =================
            [
                'title' => 'Inspeksi Kelayakan Alat Berat Rig & Crane Operasional',
                'safeguard_function' => 'Health & Safety',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan menyeluruh sertifikasi SILO, sistem pengereman, dan wire rope pada unit rig serta mobile crane di area wellpad.',
            ],
            [
                'title' => 'Pelatihan Tanggap Darurat Kebakaran & Penggunaan APAR',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Pelatihan',
                'description' => 'Simulasi pemadaman api skala awal menggunakan APAR CO2 dan Dry Chemical Powder bagi seluruh personel lapangan.',
            ],
            [
                'title' => 'Audit Keselamatan Kerja EBTKE Semester Berjalan',
                'safeguard_function' => 'Health & Safety',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Verifikasi kepatuhan penerapan Sistem Manajemen Keselamatan Panas Bumi (SMKPB) dan standar teknis keselamatan instalasi.',
            ],
            [
                'title' => 'Simulasi Evakuasi Paparan Gas Beracun H2S di Wellpad',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PT Pertamina EP',
                'category' => 'Simulasi/Drill',
                'description' => 'Latihan gabungan deteksi dini gas Hidrogen Sulfida (H2S), penggunaan SCBA, dan evakuasi menuju muster point.',
            ],
            [
                'title' => 'Sertifikasi & Pemeriksaan Kelayakan Perancah (Scaffolding)',
                'safeguard_function' => 'Health & Safety',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Inspeksi',
                'description' => 'Pengecekan struktur pipa penyangga, papan pijakan, dan pemasangan green tag sebelum pekerjaan pemeliharaan di ketinggian.',
            ],
            [
                'title' => 'Safety Talk Prosedur Lockout Tagout (LOTO) Gardu Induk',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PLN',
                'category' => 'Safety Talk',
                'description' => 'Penguatan disiplin isolasi energi listrik tegangan tinggi sebelum teknisi melakukan pemeliharaan panel switchyard.',
            ],
            [
                'title' => 'Pelatihan Pertolongan Pertama Pada Kecelakaan (P3K) & CPR',
                'safeguard_function' => 'Health & Safety',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Pelatihan',
                'description' => 'Pembekalan kompetensi bantuan hidup dasar (Basic Life Support) dan penanganan cedera traumatik bagi tim First Aider.',
            ],
            [
                'title' => 'Inspeksi Kepatuhan Alat Pelindung Diri (APD) Area Turbin',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan pemakaian ear muff, safety helmet, sepatu safety, dan detektor H2S personal di area bising pembangkit.',
            ],
            [
                'title' => 'Pengujian Berkala Sistem Proteksi Kebakaran Hydrant & Sprinkler',
                'safeguard_function' => 'Health & Safety',
                'company' => 'Kementerian ESDM',
                'category' => 'Inspeksi',
                'description' => 'Uji performa tekanan jockey pump, main electric pump, dan diesel fire pump untuk memastikan kesiapsiagaan instalasi.',
            ],
            [
                'title' => 'Pelatihan Bekerja Aman di Ruang Terbatas (Confined Space)',
                'safeguard_function' => 'Health & Safety',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Pelatihan',
                'description' => 'Pelatihan prosedur pengujian gas atmosfer, ventilasi paksa, dan penyelamatan pekerja di dalam tangki kondensor.',
            ],
            [
                'title' => 'Kampanye Bulan K3 Nasional & Komitmen Zero Accident',
                'safeguard_function' => 'Health & Safety',
                'company' => 'Kementerian ESDM',
                'category' => 'Kampanye',
                'description' => 'Apel keselamatan dan penandatanganan pakta integritas budaya K3 oleh seluruh jajaran manajemen serta kontraktor.',
            ],
            [
                'title' => 'Pemeriksaan Kesehatan Berkala (Medical Check Up) Pekerja Shift',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Lainnya',
                'description' => 'Evaluasi kelaikan medis (fit to work), audiometri, dan spirometri bagi operator yang bekerja di area panas bumi.',
            ],
            [
                'title' => 'Verifikasi Job Safety Analysis (JSA) & Permit to Work Pekerjaan Panas',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PT Pertamina EP',
                'category' => 'Inspeksi',
                'description' => 'Peninjauan lapangan terhadap pengendalian bahaya percikan pengelasan pipa uap dan ketersediaan fire watcher.',
            ],
            [
                'title' => 'Simulasi Penyelamatan Korban di Ketinggian (High Angle Rescue)',
                'safeguard_function' => 'Health & Safety',
                'company' => 'BPBD Kabupaten Banjarnegara',
                'category' => 'Simulasi/Drill',
                'description' => 'Latihan teknis penurunan korban menggunakan sistem tali vertikal dari atas menara pendingin (cooling tower).',
            ],
            [
                'title' => 'Pelatihan Defensive Driving Kendaraan Operasional Medan Pegunungan',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Pelatihan',
                'description' => 'Peningkatan keterampilan mengemudi aman pada kondisi jalan curam dan berkabut tebal di kawasan dataran tinggi Dieng.',
            ],
            [
                'title' => 'Inspeksi Keselamatan Bejana Tekan & Katup Pengaman (Safety Valve)',
                'safeguard_function' => 'Health & Safety',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan teknis ketebalan dinding separator uap dan kalibrasi tekanan bukaan pressure safety valve.',
            ],
            [
                'title' => 'Sosialisasi Ergonomi Kerja & Pencegahan Kelelahan (Fatigue Management)',
                'safeguard_function' => 'Health & Safety',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Sosialisasi',
                'description' => 'Edukasi pengaturan pola istirahat kerja shift malam dan postur angkat beban manual yang ergonomis.',
            ],
            [
                'title' => 'Audit Internal Sistem Manajemen K3 (SMK3) & ISO 45001',
                'safeguard_function' => 'Health & Safety',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Tinjauan silang pemenuhan klausul keselamatan kerja dan penutupan temuan ketidaksesuaian operasional.',
            ],
            [
                'title' => 'Inspeksi Kelayakan Perkakas Listrik Portabel (Power Tools)',
                'safeguard_function' => 'Health & Safety',
                'company' => 'PLN',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan isolasi kabel, pelindung mata gerinda, dan pemasangan stiker lulus uji kelayakan triwulanan.',
            ],

            // ================= SECURITY (19 Data) =================
            [
                'title' => 'Patroli Keamanan Terpadu Area Switchyard & Jalur Transmisi',
                'safeguard_function' => 'Security',
                'company' => 'PLN',
                'category' => 'Inspeksi',
                'description' => 'Pengawasan perimeter pagar pengaman gardu induk dan sterilisasi jalur pipa uap dari aktivitas tanpa izin.',
            ],
            [
                'title' => 'Audit Sistem Manajemen Pengamanan (SMP) Objek Vital Nasional',
                'safeguard_function' => 'Security',
                'company' => 'Kementerian ESDM',
                'category' => 'Inspeksi',
                'description' => 'Verifikasi implementasi standar pengamanan fisik, personel, dan dokumen pada fasilitas pembangkitan panas bumi.',
            ],
            [
                'title' => 'Simulasi Penanganan Ancaman Sabotase & Gangguan Keamanan',
                'safeguard_function' => 'Security',
                'company' => 'BUJP Security Mitra',
                'category' => 'Simulasi/Drill',
                'description' => 'Latihan taktis penutupan akses darurat (lockdown) dan koordinasi tanggap cepat personel pengamanan.',
            ],
            [
                'title' => 'Inspeksi Keandalan Kamera CCTV & Sensor Perimeter Pos Utama',
                'safeguard_function' => 'Security',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan fungsi rekaman 24 jam kamera pemantau titik buta (blind spot) serta lampu penerangan pagar keliling.',
            ],
            [
                'title' => 'Pelatihan Pengendalian Massa & Komunikasi Persuasif Personel Security',
                'safeguard_function' => 'Security',
                'company' => 'BUJP Security Mitra',
                'category' => 'Pelatihan',
                'description' => 'Pembekalan pendekatan humanis dan prosedur negosiasi lapangan sesuai prinsip Voluntary Principles on Security and Human Rights.',
            ],
            [
                'title' => 'Pemeriksaan Ketat Akses Masuk Kendaraan Material & Tamu Proyek',
                'safeguard_function' => 'Security',
                'company' => 'PT Pertamina EP',
                'category' => 'Inspeksi',
                'description' => 'Penerapan pemeriksaan surat jalan, kartu identitas visitor, serta deteksi barang berbahaya di gerbang utama.',
            ],
            [
                'title' => 'Apel Kesiapsiagaan Regu Pengamanan Shift Malam & Akhir Pekan',
                'safeguard_function' => 'Security',
                'company' => 'BUJP Security Mitra',
                'category' => 'Safety Talk',
                'description' => 'Pengecekan kelengkapan atribut patroli, radio komunikasi HT, dan pembagian titik fokus pengawasan malam.',
            ],
            [
                'title' => 'Evaluasi Kinerja Badan Usaha Jasa Pengamanan (BUJP) Triwulanan',
                'safeguard_function' => 'Security',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Lainnya',
                'description' => 'Penilaian kedisiplinan personel, ketepatan pelaporan buku mutasi, dan kecepatan respons terhadap insiden keamanan.',
            ],
            [
                'title' => 'Patroli Pengamanan Aset Pipa Produksi & Sumur Injeksi Terpencil',
                'safeguard_function' => 'Security',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Pengecekan kondisi gembok rumah sumur (wellhead), baut flensa pipa, serta rambu larangan masuk area terbatas.',
            ],
            [
                'title' => 'Sosialisasi Prosedur Keamanan Informasi & Kontrol Akses Ruang Server',
                'safeguard_function' => 'Security',
                'company' => 'Kementerian ESDM',
                'category' => 'Sosialisasi',
                'description' => 'Penerapan otorisasi biometrik dan pencatatan log kunjungan pada ruang kontrol SCADA serta pusat data.',
            ],
            [
                'title' => 'Pelatihan Pemeriksaan Bagasi & Penggunaan Metal Detector',
                'safeguard_function' => 'Security',
                'company' => 'BUJP Security Mitra',
                'category' => 'Pelatihan',
                'description' => 'Standarisasi teknik pemeriksaan fisik kendaraan menggunakan under-vehicle inspection mirror dan handheld detector.',
            ],
            [
                'title' => 'Koordinasi Pengamanan Terpadu Bersama Aparat Kewilayahan',
                'safeguard_function' => 'Security',
                'company' => 'BPBD Kabupaten Banjarnegara',
                'category' => 'Sosialisasi',
                'description' => 'Sinkronisasi jalur komunikasi darurat dan pemetaan potensi kerawanan keamanan di sekitar wilayah kerja proyek.',
            ],
            [
                'title' => 'Inspeksi Pos Jaga & Kelengkapan Buku Mutasi Keamanan 24 Jam',
                'safeguard_function' => 'Security',
                'company' => 'BUJP Security Mitra',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan ketertiban administrasi serah terima jaga, daftar telepon darurat, dan kesiapan perlengkapan pos.',
            ],
            [
                'title' => 'Simulasi Tanggap Darurat Ancaman Bom & Benda Mencurigakan',
                'safeguard_function' => 'Security',
                'company' => 'Kementerian ESDM',
                'category' => 'Simulasi/Drill',
                'description' => 'Latihan isolasi area temuan benda mencurigakan dan prosedur evakuasi aman penghuni gedung perkantoran.',
            ],
            [
                'title' => 'Pengawasan Distribusi & Pengawalan Mobilisasi Peralatan Berat Turbin',
                'safeguard_function' => 'Security',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Lainnya',
                'description' => 'Pengawalan rute logistik komponen vital pembangkit agar tiba tepat waktu tanpa hambatan lalu lintas.',
            ],
            [
                'title' => 'Audit Kepatuhan Hak Asasi Manusia dalam Operasional Keamanan',
                'safeguard_function' => 'Security',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Tinjauan kepatuhan prosedur pengamanan terhadap standar safeguard internasional dan perlindungan masyarakat lokal.',
            ],
            [
                'title' => 'Pemasangan & Verifikasi Rambu Peringatan Area Terbatas (Restricted Area)',
                'safeguard_function' => 'Security',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Penambahan plang peringatan bahaya tegangan tinggi dan larangan masuk bagi pihak yang tidak berkepentingan.',
            ],
            [
                'title' => 'Briefing Pengamanan Kunjungan Kerja Delegasi Kementerian & Donor',
                'safeguard_function' => 'Security',
                'company' => 'ADB Mission',
                'category' => 'Safety Talk',
                'description' => 'Pengaturan skema pengawalan VIP, jalur evakuasi medis, dan sterilisasi lokasi peninjauan lapangan.',
            ],
            [
                'title' => 'Inspeksi Sistem Penguncian Gudang Penyimpanan Material Berharga',
                'safeguard_function' => 'Security',
                'company' => 'PT Pertamina EP',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan segel keamanan gudang suku cadang instrumen, pencahayaan malam, dan log keluar-masuk barang.',
            ],

            // ================= ENVIRONMENT (19 Data) =================
            [
                'title' => 'Sosialisasi AMDAL & Pengelolaan Limbah B3 Terpadu',
                'safeguard_function' => 'Environment',
                'company' => 'Dinas Lingkungan Hidup (DLH)',
                'category' => 'Sosialisasi',
                'description' => 'Pemaparan matriks RKL-RPL serta tata cara pengemasan, pelabelan, dan penyimpanan sementara limbah B3.',
            ],
            [
                'title' => 'Pemantauan Kualitas Udara Ambien & Kebisingan Batas Tapak Proyek',
                'safeguard_function' => 'Environment',
                'company' => 'Dinas Lingkungan Hidup (DLH)',
                'category' => 'Inspeksi',
                'description' => 'Pengambilan sampel kadar H2S, SO2, partikulat debu (TSP), dan tingkat kebisingan di permukiman terdekat.',
            ],
            [
                'title' => 'Inspeksi Tempat Penyimpanan Sementara (TPS) Limbah B3',
                'safeguard_function' => 'Environment',
                'company' => 'Kementerian ESDM',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan saluran ceceran (spill containment), ketersediaan spill kit, simbol B3, dan neraca masa simpan limbah.',
            ],
            [
                'title' => 'Program Penghijauan & Revegetasi Lahan Kritis Area Wellpad',
                'safeguard_function' => 'Environment',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Kampanye',
                'description' => 'Penanaman 1.500 bibit pohon endemik penahan erosi pada lereng sekitar area sumur produksi panas bumi.',
            ],
            [
                'title' => 'Uji Kualitas Air Permukaan & Efluen Kolam Pengendapan (Settling Pond)',
                'safeguard_function' => 'Environment',
                'company' => 'Dinas Lingkungan Hidup (DLH)',
                'category' => 'Inspeksi',
                'description' => 'Pengukuran parameter pH, suhu, TSS, dan logam berat terlarut guna memastikan air buangan memenuhi baku mutu.',
            ],
            [
                'title' => 'Audit Kepatuhan Lingkungan & Verifikasi Dokumen PROPER',
                'safeguard_function' => 'Environment',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Evaluasi pemenuhan pelaporan SIMPEL, pengendalian pencemaran air/udara, dan inovasi efisiensi energi.',
            ],
            [
                'title' => 'Simulasi Penanggulangan Tumpahan Oli & Bahan Kimia (Oil Spill Drill)',
                'safeguard_function' => 'Environment',
                'company' => 'PT Pertamina EP',
                'category' => 'Simulasi/Drill',
                'description' => 'Latihan penggunaan absorbent pad dan oil boom untuk melokalisir ceceran pelumas agar tidak mencemari drainase.',
            ],
            [
                'title' => 'Inspeksi Stabilitas Lereng & Saluran Drainase Air Hujan',
                'safeguard_function' => 'Environment',
                'company' => 'BPBD Kabupaten Banjarnegara',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan kondisi dinding penahan tanah (retaining wall) dan pembersihan sedimen saluran air mengantisipasi musim penghujan.',
            ],
            [
                'title' => 'Monitoring Keanekaragaman Hayati (Flora & Fauna) Kawasan Hutan Lindung',
                'safeguard_function' => 'Environment',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Survei berkala indeks biodiversitas vegetasi dan satwa liar di sekitar koridor pipa sesuai Environmental Management Plan.',
            ],
            [
                'title' => 'Kampanye Pengurangan Sampah Plastik & Pemilahan Sampah Domestik',
                'safeguard_function' => 'Environment',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Kampanye',
                'description' => 'Edukasi prinsip 3R (Reduce, Reuse, Recycle) dan penyediaan tempat sampah terpilah organik, anorganik, serta B3.',
            ],
            [
                'title' => 'Pengangkutan Limbah B3 Terjadwal Bersama Vendor Berizin Resmi',
                'safeguard_function' => 'Environment',
                'company' => 'Dinas Lingkungan Hidup (DLH)',
                'category' => 'Lainnya',
                'description' => 'Serah terima oli bekas, majun terkontaminasi, dan lampu TL bekas disertai dokumen Festronik resmi.',
            ],
            [
                'title' => 'Pengukuran Emisi Cerobong Menara Pendingin & Genset Darurat',
                'safeguard_function' => 'Environment',
                'company' => 'EBTKE',
                'category' => 'Inspeksi',
                'description' => 'Pengujian berkala emisi sumber tidak bergerak oleh laboratorium terakreditasi KAN.',
            ],
            [
                'title' => 'Pelatihan Identifikasi Aspek & Dampak Lingkungan (EMS ISO 14001)',
                'safeguard_function' => 'Environment',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Pelatihan',
                'description' => 'Peningkatan kapasitas tim lapangan dalam menyusun matriks risiko lingkungan pada setiap tahapan konstruksi.',
            ],
            [
                'title' => 'Inspeksi Pengelolaan Lumpur Pemboran & Kolam Brine (Thermal Pond)',
                'safeguard_function' => 'Environment',
                'company' => 'Kementerian ESDM',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan lapisan geomembran kedap air dan kapasitas tampung kolam guna mencegah rembesan air panas bumi.',
            ],
            [
                'title' => 'Verifikasi Lapangan Environmental Safeguard Monitoring Report (ESMR)',
                'safeguard_function' => 'Environment',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Kunjungan supervisi tim konsultan safeguard untuk memvalidasi data pemantauan lingkungan semesteran.',
            ],
            [
                'title' => 'Pembersihan Sedimentasi Silt Trap & Oil Catcher Workshop',
                'safeguard_function' => 'Environment',
                'company' => 'PT Pertamina EP',
                'category' => 'Lainnya',
                'description' => 'Pemeliharaan rutin unit pemisah minyak dan air (oil water separator) pada area pencucian alat berat.',
            ],
            [
                'title' => 'Sosialisasi Konservasi Air & Efisiensi Pemakaian Energi Listrik Kantor',
                'safeguard_function' => 'Environment',
                'company' => 'PLN',
                'category' => 'Sosialisasi',
                'description' => 'Kampanye penghematan pemakaian air bersih dan pemadaman peralatan listrik saat di luar jam operasional.',
            ],
            [
                'title' => 'Inspeksi Kepatuhan Penyimpanan Bahan Kimia & Kelengkapan MSDS',
                'safeguard_function' => 'Environment',
                'company' => 'Dinas Lingkungan Hidup (DLH)',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan gudang bahan kimia laboratorium, ketersediaan palet penampung bocoran, dan lembar data keselamatan.',
            ],
            [
                'title' => 'Aksi Bersih Lingkungan Sungai & Saluran Irigasi Sekitar Pembangkit',
                'safeguard_function' => 'Environment',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Kampanye',
                'description' => 'Kegiatan gotong royong pembersihan aliran sungai bersama warga desa penyangga dalam rangka Hari Lingkungan Hidup.',
            ],

            // ================= SOCIAL (18 Data) =================
            [
                'title' => 'Konsultasi Publik Safeguard Sosial & Rencana Pengembangan Proyek',
                'safeguard_function' => 'Social',
                'company' => 'ADB Mission',
                'category' => 'Sosialisasi',
                'description' => 'Dialog terbuka bersama perangkat desa, tokoh masyarakat, dan warga terdampak terkait jadwal pekerjaan konstruksi.',
            ],
            [
                'title' => 'Evaluasi Mekanisme Penanganan Keluhan Masyarakat (Grievance Redress Mechanism)',
                'safeguard_function' => 'Social',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Lainnya',
                'description' => 'Tinjauan tindak lanjut penyelesaian aspirasi dan pengaduan warga sekitar proyek secara transparan dan tepat waktu.',
            ],
            [
                'title' => 'Sosialisasi Peluang Rekrutmen Tenaga Kerja Lokal & Kemitraan UMKM',
                'safeguard_function' => 'Social',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Sosialisasi',
                'description' => 'Penyampaian informasi kuota penyerapan tenaga kerja desa ring-1 serta pemberdayaan katering dan pemasok lokal.',
            ],
            [
                'title' => 'Pelatihan Kewirausahaan & Pengolahan Hasil Pertanian Masyarakat Desa Penyangga',
                'safeguard_function' => 'Social',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Pelatihan',
                'description' => 'Program pengembangan masyarakat (CSR/Comdev) untuk meningkatkan nilai tambah komoditas pertanian dataran tinggi.',
            ],
            [
                'title' => 'Survei Sosial Ekonomi & Pemantauan Pemulihan Mata Pencaharian (Livelihood)',
                'safeguard_function' => 'Social',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Pendataan perkembangan pendapatan rumah tangga warga terdampak pengadaan lahan pasca kompensasi.',
            ],
            [
                'title' => 'Sosialisasi Keselamatan Masyarakat Sekitar Jalur Pipa Panas Bumi',
                'safeguard_function' => 'Social',
                'company' => 'EBTKE',
                'category' => 'Sosialisasi',
                'description' => 'Edukasi warga petani mengenai batas aman beraktivitas di sekitar jalur pipa uap dan nomor kontak darurat HSSE.',
            ],
            [
                'title' => 'Pelayanan Kesehatan Gratis & Penyuluhan Gizi Balita Desa Sekitar',
                'safeguard_function' => 'Social',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Kampanye',
                'description' => 'Kegiatan pemeriksaan kesehatan umum dan pemberian makanan tambahan bekerja sama dengan Puskesmas setempat.',
            ],
            [
                'title' => 'Koordinasi Pengaturan Lalu Lintas Mobilisasi Alat Berat Bersama Warga',
                'safeguard_function' => 'Social',
                'company' => 'BPBD Kabupaten Banjarnegara',
                'category' => 'Sosialisasi',
                'description' => 'Kesepakatan jam operasional angkutan proyek dan penempatan petugas pengatur jalan agar tidak mengganggu aktivitas pasar desa.',
            ],
            [
                'title' => 'Monitoring Pengarusutamaan Gender (Gender Action Plan) di Lingkungan Proyek',
                'safeguard_function' => 'Social',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Verifikasi keterlibatan perempuan dalam forum konsultasi publik, kesempatan kerja setara, dan fasilitas ramah perempuan.',
            ],
            [
                'title' => 'Sosialisasi Pencegahan Penyakit Menular & Perilaku Hidup Bersih Sehat (PHBS)',
                'safeguard_function' => 'Social',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Sosialisasi',
                'description' => 'Penyuluhan kesehatan komunitas dan pekerja kontraktor mengenai sanitasi lingkungan serta pencegahan ISPA.',
            ],
            [
                'title' => 'Forum Komunikasi Multipihak (Stakeholder Engagement) Triwulanan',
                'safeguard_function' => 'Social',
                'company' => 'Kementerian ESDM',
                'category' => 'Sosialisasi',
                'description' => 'Pertemuan rutin koordinasi capaian program tanggung jawab sosial dan lingkungan bersama pemerintah daerah.',
            ],
            [
                'title' => 'Inspeksi Kondisi Jalan Desa & Perbaikan Infrastruktur Terdampak Mobilisasi',
                'safeguard_function' => 'Social',
                'company' => 'PT Geo Dipa Energi (Persero)',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan bersama perangkat desa atas kondisi bahu jalan dan drainase warga untuk segera dilakukan pemeliharaan.',
            ],
            [
                'title' => 'Simulasi Tanggap Bencana Desa Tangguh Bersama Masyarakat Lokal',
                'safeguard_function' => 'Social',
                'company' => 'BPBD Kabupaten Banjarnegara',
                'category' => 'Simulasi/Drill',
                'description' => 'Pelatihan jalur evakuasi warga, bunyi sirine peringatan dini, dan pembentukan relawan siaga bencana tingkat desa.',
            ],
            [
                'title' => 'Verifikasi Kepatuhan Standar Ketenagakerjaan & Upah Kontraktor Lokal',
                'safeguard_function' => 'Social',
                'company' => 'Dinas Tenaga Kerja',
                'category' => 'Inspeksi',
                'description' => 'Pemeriksaan kepesertaan BPJS Ketenagakerjaan, ketepatan pembayaran upah, dan jam kerja pekerja lokal.',
            ],
            [
                'title' => 'Kampanye Perlindungan Situs Budaya & Kearifan Lokal Kawasan Dieng',
                'safeguard_function' => 'Social',
                'company' => 'Kementerian ESDM',
                'category' => 'Kampanye',
                'description' => 'Sosialisasi prosedur temuan tak terduga (Chance Find Procedure) cagar budaya dan penghormatan adat istiadat setempat.',
            ],
            [
                'title' => 'Pendampingan Kelompok Tani Hortikultura Ramah Lingkungan (Agroforestri)',
                'safeguard_function' => 'Social',
                'company' => 'Dinas Lingkungan Hidup (DLH)',
                'category' => 'Pelatihan',
                'description' => 'Pelatihan budidaya tanaman terasering dan pupuk organik guna menjaga kelestarian tanah sekaligus ekonomi petani.',
            ],
            [
                'title' => 'Audit Kepatuhan Social Safeguard & Pelaporan Semi-Annual',
                'safeguard_function' => 'Social',
                'company' => 'ADB Mission',
                'category' => 'Inspeksi',
                'description' => 'Penilaian menyeluruh pemenuhan indikator safeguard sosial, pembebasan lahan, dan kesejahteraan masyarakat sekitar.',
            ],
            [
                'title' => 'Pertemuan Evaluasi Kotak Saran & Posko Pengaduan Masyarakat Desa',
                'safeguard_function' => 'Social',
                'company' => 'EBTKE',
                'category' => 'Sosialisasi',
                'description' => 'Pembukaan kotak aspirasi bulanan dan penyampaian umpan balik resmi atas masukan warga di balai desa.',
            ],
        ];

        // Acak urutan secara deterministik agar distribusi fungsi safeguard merata di masa lalu, hari ini, dan masa depan
        // Kita susun secara bergiliran (interleaved) antara Health & Safety, Security, Environment, Social
        $grouped = [
            'Health & Safety' => array_values(array_filter($templates, fn($t) => $t['safeguard_function'] === 'Health & Safety')),
            'Security' => array_values(array_filter($templates, fn($t) => $t['safeguard_function'] === 'Security')),
            'Environment' => array_values(array_filter($templates, fn($t) => $t['safeguard_function'] === 'Environment')),
            'Social' => array_values(array_filter($templates, fn($t) => $t['safeguard_function'] === 'Social')),
        ];

        $interleavedTemplates = [];
        for ($row = 0; $row < 19; $row++) {
            foreach (['Health & Safety', 'Security', 'Environment', 'Social'] as $funcKey) {
                if (isset($grouped[$funcKey][$row])) {
                    $interleavedTemplates[] = $grouped[$funcKey][$row];
                }
            }
        }

        // Pastikan tepat 75 item
        $interleavedTemplates = array_slice($interleavedTemplates, 0, 75);

        // 4. Susun 75 Tanggal Kegiatan:
        //    - Index 0..49 (50 data): Masa lalu (< hari ini), tersebar merata dalam 6 bulan terakhir (1 s/d 175 hari lalu)
        //      * 46 data berstatus "Selesai" (otomatis sesuai tanggal < hari ini)
        //      * 4 data disisipkan berstatus "Dibatalkan" secara manual untuk variasi
        //    - Index 50..54 (5 data): Hari ini (== hari ini) -> otomatis berstatus "Berlangsung"
        //    - Index 55..74 (20 data): Masa depan (> hari ini), tersebar dalam 2 bulan ke depan (1 s/d 60 hari ke depan) -> otomatis berstatus "Direncanakan"
        $pastOffsets = [
            1, 2, 3, 4, 5, 6, 8, 10, 12, 14,
            17, 20, 23, 26, 29, 32, 35, 38, 42, 45,
            49, 53, 57, 61, 65, 69, 73, 77, 81, 85,
            89, 93, 97, 101, 105, 109, 113, 117, 121, 125,
            130, 135, 140, 145, 150, 155, 160, 165, 170, 175,
        ];

        $futureOffsets = [
            1, 2, 4, 6, 9, 12, 15, 18, 21, 24,
            27, 30, 34, 38, 42, 46, 50, 54, 57, 60,
        ];

        // Indeks pada data masa lalu yang diset manual menjadi "Dibatalkan"
        $cancelledIndices = [11, 24, 37, 46];

        $pics = [
            'Budi Santoso',
            'Rina Dewi',
            'Agus Priyanto',
            'Siti Nurhaliza',
            'Dr. Hendra Wijaya',
            'wahyu Pratama',
            'Dedi Kurniawan',
            'Lestari Handayani',
        ];

        $locations = [
            'Wellpad 28 Dieng',
            'Area Power Plant Unit 1',
            'Switchyard 150 kV',
            'Ruang Rapat Utama HSSE',
            'TPS Limbah B3 Terpadu',
            'Balai Desa Karangtengah',
            'Pos Keamanan Utama (Main Gate)',
            'Workshop Mekanikal & Rig',
        ];

        $sampleImages = [
            'events/sample/foto-1.jpg',
            'events/sample/foto-2.jpg',
            'events/sample/foto-3.jpg',
            'events/sample/foto-4.jpg',
            'events/sample/foto-5.jpg',
        ];

        foreach ($interleavedTemplates as $idx => $item) {
            if ($idx < 50) {
                $daysAgo = $pastOffsets[$idx];
                $eventDate = $now->copy()->subDays($daysAgo)->toDateString();
                $manualStatus = in_array($idx, $cancelledIndices, true) ? Event::STATUS_DIBATALKAN : null;
            } elseif ($idx < 55) {
                $eventDate = $now->toDateString();
                $manualStatus = null;
            } else {
                $daysAhead = $futureOffsets[$idx - 55];
                $eventDate = $now->copy()->addDays($daysAhead)->toDateString();
                $manualStatus = null;
            }

            // Sinkronisasi status menggunakan fungsi standar Model Event
            $status = Event::determineAutoStatus($eventDate, $manualStatus);

            $pic = ucfirst($pics[$idx % count($pics)]);
            $location = $locations[$idx % count($locations)];
            $participants = 12 + (($idx * 7) % 45);
            $docsCount = ($status === Event::STATUS_SELESAI) ? (($idx % 3) + 1) : ($status === Event::STATUS_BERLANGSUNG ? 1 : 0);

            $createdAt = Carbon::parse($eventDate)->setTime(8 + ($idx % 4), 15);
            $updatedAt = Carbon::parse($eventDate)->setTime(14 + ($idx % 3), 30);

            $event = Event::create([
                'title' => $item['title'],
                'safeguard_function' => $item['safeguard_function'],
                'company' => $item['company'],
                'category' => $item['category'],
                'unit' => $item['company'],
                'event_date' => $eventDate,
                'start_time' => '08:30',
                'end_time' => '11:30',
                'location' => $location,
                'participants' => $participants,
                'pic' => $pic,
                'status' => $status,
                'description' => $item['description'],
                'documentation' => $docsCount > 0 ? $sampleImages[$idx % count($sampleImages)] : null,
                'created_by' => $pic,
                'updated_by' => 'Administrator HSSE',
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            // Buat lampiran dokumen untuk kegiatan yang Selesai / Berlangsung
            for ($d = 1; $d <= $docsCount; $d++) {
                $imgPath = $sampleImages[($idx + $d) % count($sampleImages)];
                Document::create([
                    'title' => "Dokumentasi {$event->title} #{$d}",
                    'document_type' => 'Foto Kegiatan',
                    'module' => 'events',
                    'record_id' => $event->id,
                    'file_name' => "dokumentasi-event-{$event->id}-{$d}.jpg",
                    'file_path' => $imgPath,
                    'mime_type' => 'image/jpeg',
                    'file_size' => 1024 * 450 * $d,
                    'uploaded_by' => $pic,
                    'description' => "Foto dokumentasi kegiatan {$event->title}",
                    'created_at' => $createdAt->copy()->addMinutes(30 * $d),
                ]);
            }

            // Catat riwayat aktivitas pembuatan event
            ActivityLog::create([
                'user_id' => null,
                'user_name' => $pic,
                'module' => 'events',
                'action' => 'TAMBAH',
                'record_type' => Event::class,
                'record_id' => $event->id,
                'description' => "Menambahkan kegiatan: {$event->title} ({$event->safeguard_function} - {$event->company})",
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => $createdAt,
            ]);
        }
    }
}
