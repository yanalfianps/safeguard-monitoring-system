<?php

namespace Database\Seeders;

use App\Models\Incident;
use Illuminate\Database\Seeder;

class IncidentSeeder extends Seeder
{
    /**
     * Seed data contoh Incident sesuai standar blueprint HSSE Geodipa Unit Dieng 2.
     */
    public function run(): void
    {
        if (app()->environment('production') || !filter_var(env('ALLOW_SAMPLE_SEEDING', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('IncidentSeeder dilewati agar tidak menyisipkan data sampel pada environment operasional.');
            return;
        }

        $samples = [
            [
                'nomor_insiden' => 'INC-GDE-' . date('Y') . '-001',
                'title' => 'Rembesan Uap minor pada Flange Pipa Brine Wellpad 28',
                'tanggal_waktu' => now()->subDays(12)->setTime(9, 30),
                'klasifikasi_area' => 'Kecelakaan Panas Bumi',
                'kategori_umum' => ['Near Miss (Hampir Celaka)'],
                'kategori_ebtke' => ['Kejadian Berbahaya'],
                'lokasi_kejadian' => 'Wellpad 28 Dieng',
                'komponen_terkait' => 'Pipa Brine / Gathering System',
                'severity' => 'Low',
                'deskripsi_kejadian' => 'Ditemukan rembesan uap tekanan rendah pada sambungan flange saat patroli rutin pagi.',
                'tindakan_perbaikan' => 'Pemasangan barikade keselamatan, isolasi valve, dan penggantian gasket spiral wound.',
                'penanggung_jawab' => 'Tim HSSE & Maintenance Dieng',
                'target_penyelesaian' => now()->subDays(10)->toDateString(),
                'status_laporan' => 'Final',
                'jumlah_hari_hilang' => 0,
            ],
            [
                'nomor_insiden' => 'INC-GDE-' . date('Y') . '-002',
                'title' => 'Pekerja Terpeleset di Tangga Akses Gudang Material',
                'tanggal_waktu' => now()->subDays(5)->setTime(14, 15),
                'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
                'kategori_umum' => ['First Aid Case'],
                'kategori_ebtke' => ['Kecelakaan Ringan'],
                'lokasi_kejadian' => 'Area Gudang Logistik Kantor Dieng',
                'komponen_terkait' => 'Utility & Logistik',
                'severity' => 'Low',
                'deskripsi_kejadian' => 'Pekerja mengalami lecet ringan pada telapak tangan akibat terpeleset saat hujan.',
                'tindakan_perbaikan' => 'Penanganan P3K di klinik site dan penambahan lapisan anti-slip pada anak tangga.',
                'penanggung_jawab' => 'Koordinator K3L Dieng 2',
                'target_penyelesaian' => now()->addDays(3)->toDateString(),
                'status_laporan' => 'Preliminary',
                'jumlah_hari_hilang' => 0,
            ],
        ];

        foreach ($samples as $item) {
            Incident::updateOrCreate(
                ['nomor_insiden' => $item['nomor_insiden']],
                $item
            );
        }
    }
}

