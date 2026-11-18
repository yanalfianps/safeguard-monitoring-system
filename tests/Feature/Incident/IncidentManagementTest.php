<?php

namespace Tests\Feature\Incident;

use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::where('role', User::ROLE_ADMIN_MANAGEMENT)->first()
            ?: User::where('role', 'Admin')->first()
            ?: User::factory()->create([
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]);
    }

    public function test_incident_index_renders_successfully(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);
        $response->assertSee('Pencatatan Insiden & Kecelakaan Kerja Lapangan', false);
        $response->assertSee('1. Kategori Umum', false);
        $response->assertSee('2. Kategori EBTKE', false);
        $response->assertSee('3. Kategori Panas Bumi', false);
        $response->assertSee('chartArea', false);
        $response->assertSee('chartUmum', false);
        $response->assertSee('chartEbtke', false);
    }

    public function test_incident_red_identity_strictly_maintained(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);
        // Red color identity is mandatory
        $response->assertSee('--red-incident: #dc2626;', false);
        $response->assertSee('btn-danger', false);
        $response->assertSee('text-danger', false);
    }

    public function test_incident_ajax_json_endpoint(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->getJson('/incident');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'incidents',
            'totalInsiden',
            'totalHariHilang',
            'kasusHariHilangCount',
            'panasBumiCount',
            'nonPanasBumiCount',
            'chartArea',
            'chartUmum',
            'chartEbtke',
            'chartHariHilang',
            'chartDetailIncidents',
        ]);
        $response->assertJson(['success' => true]);
    }

    public function test_incident_displays_three_interactive_pie_charts(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);

        // 3 Pie chart utama harus tampil
        $response->assertSee('1. Kategori Umum', false);
        $response->assertSee('2. Kategori EBTKE', false);
        $response->assertSee('3. Kategori Panas Bumi', false);
        $response->assertDontSee('1. Kategori Umum (Tags)', false);
        $response->assertDontSee('2. Kategori EBTKE (Tags)', false);
        $response->assertDontSee('3. Kategori Insiden', false);

        // Kartu statistik ringkasan dan grafik LWD sudah dihapus
        $response->assertDontSee('Total Hari Hilang', false);
        $response->assertDontSee('4. Visualisasi & Distribusi Total Hari Kerja Hilang', false);
        $response->assertDontSee('Klik segmen untuk rincian insiden', false);
    }

    public function test_can_create_incident_with_binary_classification_and_tags(): void
    {
        $admin = $this->getAdmin();
        $nomor = Incident::generateNextNumber();

        $response = $this->actingAs($admin)->post(route('incident.store'), [
            'nomor_insiden' => $nomor,
            'tanggal_waktu' => '2026-10-13 10:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage', 'Near Miss (Hampir Celaka)'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Wellpad 28 Dieng',
            'komponen_terkait' => 'Pipa Geothermal Unit 1',
            'deskripsi_kejadian' => 'Terjadi rembesan uap saat inspeksi rutin, tidak ada korban cedera.',
            'tindakan_perbaikan' => 'Isolasi area dan penggantian gasket pipa.',
            'penanggung_jawab' => 'Budi Santoso (Safety Officer)',
            'status_laporan' => 'Preliminary',
            'jumlah_hari_hilang' => 0,
        ]);

        $response->assertRedirect(route('incident.index'));
        $this->assertDatabaseHas('incidents', [
            'nomor_insiden' => $nomor,
            'incident_code' => $nomor,
            'lokasi_kejadian' => 'Wellpad 28 Dieng',
            'location' => 'Wellpad 28 Dieng',
            'hari' => 'Selasa',
        ]);
    }

    public function test_can_create_incident_via_ajax_and_upload_document(): void
    {
        Storage::fake('public');
        $admin = $this->getAdmin();

        $pdfFile = UploadedFile::fake()->create('investigasi-insiden.pdf', 256, 'application/pdf');

        $response = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Kebocoran Seal Valve Brine',
            'tanggal_waktu' => '2026-10-15 14:30:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'kategori_ebtke' => ['Kejadian Berbahaya'],
            'lokasi_kejadian' => 'Wellpad 31 Dieng',
            'komponen_terkait' => 'Gathering System',
            'deskripsi_kejadian' => 'Ditemukan kebocoran seal pada valve utama.',
            'tindakan_perbaikan' => 'Penggantian seal valve.',
            'penanggung_jawab' => 'Tim Maintenance',
            'status_laporan' => 'Final',
            'jumlah_hari_hilang' => 0,
            'dokumen_investigasi' => $pdfFile,
        ]);

        $response->assertStatus(201)->assertJson([
            'success' => true,
        ]);

        $incidentData = $response->json('incident');
        $this->assertNotEmpty($incidentData['dokumen_investigasi']);
        Storage::disk('public')->assertExists($incidentData['dokumen_investigasi']);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Incident',
            'action' => 'TAMBAH',
            'record_id' => $incidentData['id'],
        ]);
    }

    public function test_can_update_incident_and_log_diff(): void
    {
        $admin = $this->getAdmin();
        $incident = Incident::create([
            'nomor_insiden' => 'INC-TEST-UPD-' . time(),
            'tanggal_waktu' => '2026-10-13 09:00:00',
            'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
            'lokasi_kejadian' => 'Kantor Administrasi Dieng',
            'komponen_terkait' => 'Gedung Utama',
            'deskripsi_kejadian' => 'Lantai licin di koridor.',
            'status_laporan' => 'Preliminary',
            'jumlah_hari_hilang' => 0,
        ]);

        $response = $this->actingAs($admin)->put(route('incident.update', $incident->id), [
            'tanggal_waktu' => '2026-10-13 09:00:00',
            'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
            'lokasi_kejadian' => 'Kantor Administrasi Dieng Lt. 2',
            'komponen_terkait' => 'Gedung Utama',
            'deskripsi_kejadian' => 'Lantai licin di koridor telah dipasang rambu peringatan.',
            'tindakan_perbaikan' => 'Pembersihan dan pemasangan anti slip mat.',
            'status_laporan' => 'Final',
            'jumlah_hari_hilang' => 0,
        ]);

        $response->assertRedirect(route('incident.index'));
        $incident->refresh();
        $this->assertEquals('Kantor Administrasi Dieng Lt. 2', $incident->lokasi_kejadian);
        $this->assertEquals('Kantor Administrasi Dieng Lt. 2', $incident->location);
        $this->assertEquals('Final', $incident->status_laporan);
        $this->assertEquals('Selesai', $incident->status);
    }

    public function test_can_delete_incident(): void
    {
        $admin = $this->getAdmin();
        $incident = Incident::create([
            'nomor_insiden' => 'INC-TEST-DEL-' . time(),
            'tanggal_waktu' => '2026-10-13 08:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'lokasi_kejadian' => 'Area Turbin',
            'komponen_terkait' => 'Turbin Unit 1',
            'deskripsi_kejadian' => 'Insiden uji coba untuk dihapus.',
            'status_laporan' => 'Preliminary',
            'jumlah_hari_hilang' => 0,
        ]);

        $response = $this->actingAs($admin)->delete(route('incident.destroy', $incident->id));
        $response->assertRedirect(route('incident.index'));
        $this->assertDatabaseMissing('incidents', ['id' => $incident->id]);
    }

    public function test_incident_charts_reflect_active_filters(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->getJson('/incident?' . http_build_query([
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
        ]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('chartArea', $data);
        $this->assertArrayHasKey('colors', $data['chartArea']);
        $this->assertCount(2, $data['chartArea']['colors']);
        $this->assertArrayHasKey('chartUmum', $data);
        $this->assertArrayHasKey('colors', $data['chartUmum']);
        $this->assertArrayHasKey('chartEbtke', $data);
        $this->assertArrayHasKey('colors', $data['chartEbtke']);
    }

    public function test_incident_charts_remain_full_accumulation_across_pagination_pages(): void
    {
        $admin = $this->getAdmin();

        // Buat 15 record insiden khusus uji paginasi
        for ($i = 1; $i <= 15; $i++) {
            Incident::create([
                'incident_code' => 'INC-PAGINASI-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nomor_insiden' => 'INC-PAGINASI-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'title' => 'Insiden Paginasi ' . $i,
                'incident_type' => 'Property Damage',
                'unit' => 'Dieng',
                'incident_date' => now()->subDays($i),
                'tanggal_waktu' => now()->subDays($i),
                'location' => 'Lokasi ' . $i,
                'lokasi_kejadian' => 'Lokasi ' . $i,
                'severity' => 'Low',
                'description' => 'Deskripsi Paginasi ' . $i,
                'deskripsi_kejadian' => 'Deskripsi Paginasi ' . $i,
                'reported_by' => 'Tester',
                'penanggung_jawab' => 'Tester',
                'status' => 'Dilaporkan',
                'status_laporan' => 'Preliminary',
                'klasifikasi_area' => 'Kecelakaan Panas Bumi',
                'kategori_umum' => ['Property Damage'],
                'kategori_ebtke' => ['Kecelakaan Ringan'],
            ]);
        }

        // 1. Cek Halaman 1: Tabel harus berisi 10 record, tapi chartArea['total'] harus 15
        $page1Response = $this->actingAs($admin)->getJson('/incident?search=INC-PAGINASI&page=1');
        $page1Response->assertStatus(200);
        $page1Data = $page1Response->json();
        $this->assertCount(10, $page1Data['incidents']['data']);
        $this->assertEquals(15, $page1Data['chartArea']['total']);
        $this->assertEquals(15, $page1Data['chartArea']['data'][0]); // 15 Panas Bumi

        // 2. Cek Halaman 2: Tabel berisi 5 record sisa, tapi chartArea['total'] TETAP 15 (TIDAK terpotong ke 5)
        $page2Response = $this->actingAs($admin)->getJson('/incident?search=INC-PAGINASI&page=2');
        $page2Response->assertStatus(200);
        $page2Data = $page2Response->json();
        $this->assertCount(5, $page2Data['incidents']['data']);
        $this->assertEquals(15, $page2Data['chartArea']['total'], 'Chart total tidak boleh terpotong saat pagination berpindah ke halaman 2');
        $this->assertEquals(15, $page2Data['chartArea']['data'][0]);
    }

    public function test_incident_export_csv_with_filters(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('incident.export', [
            'format' => 'csv',
            'search' => 'INC',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Laporan_Incident_HSSE', (string) $response->headers->get('content-disposition'));
    }

    public function test_incident_export_pdf_with_filters(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('incident.export', [
            'search' => 'INC',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Laporan-Incident-HSSE', (string) $response->headers->get('content-disposition'));
    }

    public function test_sidebar_has_only_single_incident_menu_without_submenus(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);

        // Sidebar harus memiliki link tunggal ke /incident
        $response->assertSee(url('/incident'), false);

        // Sidebar TIDAK boleh memiliki submenu incidentSubmenu
        $response->assertDontSee('id="incidentSubmenu"', false);
        $response->assertDontSee('submenu-nested', false);
    }

    public function test_incident_module_does_not_render_category_tab_bar(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);

        // Pastikan bar navigasi kategori/tab sudah dihapus sepenuhnya
        $response->assertDontSee('incident-tabs-wrap', false);
        $response->assertDontSee('incident-tab-btn', false);
        $response->assertDontSee('1. Umum', false);
        $response->assertDontSee('2. EBTKE', false);
        $response->assertDontSee('3. Incident', false);

        // Pastikan filter, tabel, dan aksi tetap ada (dan KPI card LTI sudah dihapus)
        $response->assertDontSee('Lost Time Injury (LTI)', false);
        $response->assertSee('Kategori Umum', false);
        $response->assertSee('Kategori EBTKE', false);
        $response->assertSee('Export PDF', false);
        $response->assertSee('Lapor Insiden Baru', false);
    }

    public function test_incident_safety_metrics_and_trend_chart(): void
    {
        $admin = $this->getAdmin();

        // Buat data ManHour
        \App\Models\ManHour::create([
            'work_date' => now()->toDateString(),
            'number_of_workers' => 1000,
            'hours_per_worker' => 500,
            'total_hours' => 500000,
            'unit' => 'Unit Dieng',
            'category' => 'Pegawai GDE',
        ]);

        // Buat data Insiden LTI
        Incident::create([
            'nomor_insiden' => 'INC-METRIC-' . time(),
            'title' => 'Insiden Pengujian Metrik LTI',
            'tanggal_waktu' => now(),
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa',
            'deskripsi_kejadian' => 'Terpeleset dan kehilangan 4 hari kerja',
            'jumlah_hari_hilang' => 4,
            'status_laporan' => 'Preliminary',
            'kategori_umum' => ['LTI (Lost Time Injury)'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
        ]);

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);

        // 4 KPI Cards dan Card Tren Insiden sudah dihapus dari tampilan utama UI
        $response->assertDontSee('safety-metrics-grid', false);
        $response->assertDontSee('Tren Insiden dari Waktu ke Waktu (12 Bulan Terakhir)', false);
        $response->assertDontSee('chartTrendCanvas', false);

        // Cek data di JSON endpoint
        $jsonResponse = $this->actingAs($admin)->getJson('/incident');
        $jsonResponse->assertStatus(200);
        $data = $jsonResponse->json();

        $this->assertArrayHasKey('safetyMetrics', $data);
        $this->assertArrayHasKey('chartTrend', $data);
        $this->assertArrayHasKey('activeTab', $data);
        $this->assertEquals(1000000, $data['safetyMetrics']['multiplier']);
        $this->assertGreaterThan(0, $data['safetyMetrics']['lti']);
        $this->assertGreaterThan(0, $data['safetyMetrics']['totalHariHilang']);
        $this->assertNotNull($data['safetyMetrics']['trir']);
        $this->assertNotNull($data['safetyMetrics']['sr']);
        $this->assertNotNull($data['safetyMetrics']['fr']);
    }

    public function test_can_store_incident_with_new_status_and_auto_day_sync(): void
    {
        $admin = $this->getAdmin();
        $nomor = Incident::generateNextNumber();

        // Tanggal 2026-10-14 adalah hari Rabu (Wednesday)
        $response = $this->actingAs($admin)->post(route('incident.store'), [
            'nomor_insiden' => $nomor,
            'title' => 'Insiden Terpeleset di Power Plant',
            'tanggal_waktu' => '2026-10-14 10:00:00',
            // hari dikosongkan untuk menguji sinkronisasi otomatis
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Near Miss (Hampir Celaka)'],
            'kategori_ebtke' => ['Kejadian Berbahaya'],
            'lokasi_kejadian' => 'Power Plant Unit 1',
            'komponen_terkait' => 'Turbin',
            'deskripsi_kejadian' => 'Lantai basah di dekat genset cadangan',
            'tindakan_perbaikan' => 'Pemasangan penutup pipa uap dan peringatan licin',
            'status_laporan' => 'On Progress',
            'jumlah_hari_hilang' => 0,
        ]);

        $response->assertRedirect(route('incident.index'));
        $this->assertDatabaseHas('incidents', [
            'nomor_insiden' => $nomor,
            'title' => 'Insiden Terpeleset di Power Plant',
            'hari' => 'Rabu',
            'status_laporan' => 'On Progress',
            'status' => 'Investigasi',
            'lokasi_kejadian' => 'Power Plant Unit 1',
        ]);
    }

    public function test_hazard_report_tag_is_removed_and_rejected_in_validation(): void
    {
        $admin = $this->getAdmin();

        $this->assertNotContains('Hazard Report', Incident::KATEGORI_UMUM_LIST);
        $this->assertArrayNotHasKey('Hazard Report', Incident::KATEGORI_UMUM_PALETTE);

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);
        $response->assertDontSee("addUmum('Hazard Report')", false);
        $response->assertDontSee('<option value="Hazard Report"', false);

        // Pengiriman tag Hazard Report harus ditolak oleh validasi
        $invalidResponse = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Uji Tag Hazard Report',
            'tanggal_waktu' => '2026-11-01 09:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Hazard Report'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa Uap',
            'deskripsi_kejadian' => 'Pengujian penolakan tag Hazard Report.',
            'status_laporan' => 'Preliminary',
        ]);

        $invalidResponse->assertStatus(422);
    }

    public function test_hari_kejadian_is_readonly_and_automatically_computed_in_backend(): void
    {
        $admin = $this->getAdmin();

        // Pastikan input Hari Kejadian adalah readonly di halaman
        $page = $this->actingAs($admin)->get('/incident');
        $page->assertStatus(200);
        $page->assertSee('id="tambahHariSelect" name="hari" class="form-control form-control-sm bg-light"', false);
        $page->assertSee('id="editHari" class="form-control form-control-sm bg-light" readonly', false);

        // Contoh 1: 1 November 2026 -> Minggu (meskipun dikirim hari yang salah dari frontend)
        $resMinggu = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Insiden Minggu 1 Nov 2026',
            'tanggal_waktu' => '2026-11-01 08:30:00',
            'hari' => 'Jumat', // Sengaja salah untuk menguji konsistensi backend
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Near Miss (Hampir Celaka)'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Turbin',
            'deskripsi_kejadian' => 'Tes perhitungan otomatis hari Minggu.',
            'status_laporan' => 'Preliminary',
        ]);
        $resMinggu->assertStatus(201);
        $this->assertEquals('Minggu', $resMinggu->json('incident.hari'));

        // Contoh 2: 2 November 2026 -> Senin
        $resSenin = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Insiden Senin 2 Nov 2026',
            'tanggal_waktu' => '2026-11-02 14:15:00',
            'hari' => 'Sabtu', // Sengaja salah
            'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
            'kategori_umum' => ['First Aid Case'],
            'lokasi_kejadian' => 'Kantor Dieng',
            'komponen_terkait' => 'Utility',
            'deskripsi_kejadian' => 'Tes perhitungan otomatis hari Senin.',
            'status_laporan' => 'Preliminary',
        ]);
        $resSenin->assertStatus(201);
        $this->assertEquals('Senin', $resSenin->json('incident.hari'));
    }

    public function test_penanggung_jawab_and_target_penyelesaian_inputs_are_removed_from_form(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);
        $response->assertDontSee('name="penanggung_jawab"', false);
        $response->assertDontSee('name="target_penyelesaian"', false);

        // Penyimpanan tanpa kedua kolom tersebut berjalan lancar tanpa error
        $storeRes = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Insiden Tanpa Kolom PIC & Target',
            'tanggal_waktu' => '2026-11-02 10:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['First Aid Case'],
            'lokasi_kejadian' => 'Wellpad 29',
            'komponen_terkait' => 'Valve',
            'deskripsi_kejadian' => 'Pelaporan tanpa input PIC dan target penyelesaian.',
            'status_laporan' => 'Preliminary',
        ]);
        $storeRes->assertStatus(201)->assertJson(['success' => true]);
    }

    public function test_conditional_jumlah_hari_hilang_saved_only_when_lti_selected(): void
    {
        $admin = $this->getAdmin();

        // 1. Ketika LTI (Lost Time Injury) TIDAK dipilih -> jumlah_hari_hilang harus tersimpan null meskipun dikirim nilai angka
        $nonLtiRes = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Insiden Non-LTI',
            'tanggal_waktu' => '2026-11-01 10:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['First Aid Case', 'Property Damage'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa',
            'deskripsi_kejadian' => 'Tidak ada LTI.',
            'status_laporan' => 'Preliminary',
            'jumlah_hari_hilang' => 5,
        ]);
        $nonLtiRes->assertStatus(201);
        $nonLtiId = $nonLtiRes->json('incident.id');
        $this->assertDatabaseHas('incidents', [
            'id' => $nonLtiId,
            'jumlah_hari_hilang' => null,
        ]);

        // 2. Ketika LTI (Lost Time Injury) DIPILIH -> jumlah_hari_hilang wajib disimpan sesuai input
        $ltiRes = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Insiden Dengan LTI',
            'tanggal_waktu' => '2026-11-02 11:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['LTI (Lost Time Injury)'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Turbin',
            'deskripsi_kejadian' => 'Insiden menyebabkan kehilangan hari kerja.',
            'status_laporan' => 'Preliminary',
            'jumlah_hari_hilang' => 7,
        ]);
        $ltiRes->assertStatus(201);
        $ltiId = $ltiRes->json('incident.id');
        $this->assertDatabaseHas('incidents', [
            'id' => $ltiId,
            'jumlah_hari_hilang' => 7,
        ]);
    }

    public function test_multiple_and_incremental_file_upload_saves_all_attachments(): void
    {
        Storage::fake('public');
        $admin = $this->getAdmin();

        $file1 = UploadedFile::fake()->create('laporan-awal.pdf', 200, 'application/pdf');
        $file2 = UploadedFile::fake()->create('kronologi-investigasi.pdf', 350, 'application/pdf');
        $file3 = UploadedFile::fake()->create('data-pendukung.xlsx', 180, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Upload 3 file sekaligus saat membuat laporan insiden baru
        $createRes = $this->actingAs($admin)->postJson(route('incident.store'), [
            'title' => 'Insiden Multi Dokumen',
            'tanggal_waktu' => '2026-11-01 09:30:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'lokasi_kejadian' => 'Wellpad 30',
            'komponen_terkait' => 'Separator',
            'deskripsi_kejadian' => 'Pengujian unggah banyak dokumen sekaligus.',
            'status_laporan' => 'Preliminary',
            'dokumen_investigasi' => [$file1, $file2, $file3],
        ]);

        $createRes->assertStatus(201);
        $incidentId = $createRes->json('incident.id');
        $docsList = $createRes->json('incident.documents_list');
        $this->assertCount(3, $docsList);

        // Tambah (append) 2 file baru saat update tanpa menghapus 3 file sebelumnya (total 5 file)
        $file4 = UploadedFile::fake()->create('tindak-lanjut-1.pdf', 150, 'application/pdf');
        $file5 = UploadedFile::fake()->create('tindak-lanjut-2.docx', 210, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $updateRes = $this->actingAs($admin)->putJson(route('incident.update', $incidentId), [
            'title' => 'Insiden Multi Dokumen Updated',
            'tanggal_waktu' => '2026-11-01 09:30:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'lokasi_kejadian' => 'Wellpad 30',
            'komponen_terkait' => 'Separator',
            'deskripsi_kejadian' => 'Pengujian penambahan dokumen bertahap.',
            'status_laporan' => 'Final',
            'dokumen_investigasi' => [$file4, $file5],
        ]);

        $updateRes->assertStatus(200);
        $updatedDocsList = $updateRes->json('incident.documents_list');
        $this->assertCount(5, $updatedDocsList);
    }

    public function test_incident_filter_by_status_laporan_and_combinations(): void
    {
        $admin = $this->getAdmin();

        // Verifikasi placeholder Cari Insiden & opsi dropdown Status Laporan pada UI
        $page = $this->actingAs($admin)->get('/incident');
        $page->assertStatus(200);
        $page->assertSee('placeholder="No. Insiden, Lokasi, Tag..."', false);
        $page->assertDontSee('placeholder="No. Insiden, Lokasi, PIC, Tag..."', false);
        $page->assertSee('<option value="">Semua Status</option>', false);
        $page->assertSee('<option value="Preliminary"', false);
        $page->assertSee('<option value="Final"', false);

        // Buat 3 data uji khusus
        $incPrelim1 = Incident::create([
            'nomor_insiden' => 'INC-FLT-STAT-001',
            'title' => 'Uji Filter Status Preliminary 1',
            'tanggal_waktu' => '2026-10-10 08:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa Uap',
            'deskripsi_kejadian' => 'Tes filter Preliminary 1',
            'status_laporan' => 'Preliminary',
        ]);

        $incFinal1 = Incident::create([
            'nomor_insiden' => 'INC-FLT-STAT-002',
            'title' => 'Uji Filter Status Final 1',
            'tanggal_waktu' => '2026-10-15 10:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'kategori_ebtke' => ['Kejadian Berbahaya'],
            'lokasi_kejadian' => 'Wellpad 29',
            'komponen_terkait' => 'Separator',
            'deskripsi_kejadian' => 'Tes filter Final 1',
            'status_laporan' => 'Final',
        ]);

        $incFinal2 = Incident::create([
            'nomor_insiden' => 'INC-FLT-STAT-003',
            'title' => 'Uji Filter Status Final 2',
            'tanggal_waktu' => '2026-10-25 14:00:00',
            'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
            'kategori_umum' => ['First Aid Case'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Kantor Dieng',
            'komponen_terkait' => 'Utility',
            'deskripsi_kejadian' => 'Tes filter Final 2',
            'status_laporan' => 'Final',
        ]);

        // 1. Cari laporan berdasarkan nomor insiden
        $resSearch = $this->actingAs($admin)->getJson('/incident?search=INC-FLT-STAT-001');
        $resSearch->assertStatus(200);
        $this->assertEquals(1, $resSearch->json('totalInsiden'));
        $this->assertEquals('INC-FLT-STAT-001', $resSearch->json('incidents.data.0.nomor_insiden'));

        // 2. Filter berdasarkan Preliminary
        $resPrelim = $this->actingAs($admin)->getJson('/incident?' . http_build_query([
            'search' => 'INC-FLT-STAT-',
            'status_laporan' => 'Preliminary',
        ]));
        $resPrelim->assertStatus(200);
        $this->assertEquals(1, $resPrelim->json('totalInsiden'));
        $this->assertEquals($incPrelim1->id, $resPrelim->json('incidents.data.0.id'));

        // 3. Filter berdasarkan Final
        $resFinal = $this->actingAs($admin)->getJson('/incident?' . http_build_query([
            'search' => 'INC-FLT-STAT-',
            'status_laporan' => 'Final',
        ]));
        $resFinal->assertStatus(200);
        $this->assertEquals(2, $resFinal->json('totalInsiden'));

        // 4. Filter kombinasi Status Laporan (Final) + Kategori Umum (Property Damage)
        $resComboUmum = $this->actingAs($admin)->getJson('/incident?' . http_build_query([
            'search' => 'INC-FLT-STAT-',
            'status_laporan' => 'Final',
            'kategori_umum' => 'Property Damage',
        ]));
        $resComboUmum->assertStatus(200);
        $this->assertEquals(1, $resComboUmum->json('totalInsiden'));
        $this->assertEquals($incFinal1->id, $resComboUmum->json('incidents.data.0.id'));

        // 5. Filter kombinasi Status Laporan (Final) + Rentang Tanggal (2026-10-20 s/d 2026-10-30)
        $resComboDate = $this->actingAs($admin)->getJson('/incident?' . http_build_query([
            'search' => 'INC-FLT-STAT-',
            'status_laporan' => 'Final',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-30',
        ]));
        $resComboDate->assertStatus(200);
        $this->assertEquals(1, $resComboDate->json('totalInsiden'));
        $this->assertEquals($incFinal2->id, $resComboDate->json('incidents.data.0.id'));

        // 6. Reset / Semua Status (status_laporan kosong menampilkan ke-3 laporan)
        $resAll = $this->actingAs($admin)->getJson('/incident?' . http_build_query([
            'search' => 'INC-FLT-STAT-',
            'status_laporan' => '',
        ]));
        $resAll->assertStatus(200);
        $this->assertEquals(3, $resAll->json('totalInsiden'));
    }

    public function test_pie_chart_legends_include_counts_and_accumulation_text_removed(): void
    {
        $admin = $this->getAdmin();

        // Buat data uji untuk memeriksa perubahan dinamis jumlah pada grafik dan legenda
        Incident::create([
            'nomor_insiden' => 'INC-PIE-LEGEND-001',
            'title' => 'Uji Pie Chart Legenda 1',
            'tanggal_waktu' => '2026-11-01 09:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Medical Treatment Case', 'Environmental Incident'],
            'kategori_ebtke' => ['Kecelakaan Berat'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa Uap',
            'deskripsi_kejadian' => 'Data uji pie chart 1',
            'status_laporan' => 'Preliminary',
        ]);

        Incident::create([
            'nomor_insiden' => 'INC-PIE-LEGEND-002',
            'title' => 'Uji Pie Chart Legenda 2',
            'tanggal_waktu' => '2026-11-01 11:00:00',
            'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
            'kategori_umum' => ['Medical Treatment Case'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Kantor Dieng',
            'komponen_terkait' => 'Utility',
            'deskripsi_kejadian' => 'Data uji pie chart 2',
            'status_laporan' => 'Final',
        ]);

        $response = $this->actingAs($admin)->get('/incident?search=INC-PIE-LEGEND-');
        $response->assertStatus(200);

        // 1. Pastikan ketiga Pie Chart tampil dengan struktur seragam (Grafik di Kiri, Legenda di Kanan)
        $response->assertSee('id="chartUmumCanvas"', false);
        $response->assertSee('id="chartEbtkeCanvas"', false);
        $response->assertSee('id="chartAreaCanvas"', false);
        $response->assertSee('id="legendUmumContainer"', false);
        $response->assertSee('id="legendEbtkeContainer"', false);
        $response->assertSee('id="legendAreaContainer"', false);
        $response->assertSee('incident-chart-canvas-wrap', false);

        // 2. Pastikan badge Total Tag dan Total Kasus sudah dihapus dari bagian kanan atas card
        $response->assertDontSee('Total Tag:', false);
        $response->assertDontSee('Total: 2 Kasus', false);

        // 3. Pastikan keterangan Akumulasi sebaran tag tetap terhapus
        $response->assertDontSee('Akumulasi sebaran tag:', false);
        $response->assertDontSee('Jenis Tag Terisi', false);

        // 4. Pastikan setiap legenda di sebelah kanan menampilkan Nama Kategori (Jumlah) secara dinamis
        $response->assertSee('Medical Treatment Case <span class="incident-legend-count">(2)</span>', false);
        $response->assertSee('Environmental Incident <span class="incident-legend-count">(1)</span>', false);
        $response->assertSee('Kecelakaan Berat <span class="incident-legend-count">(1)</span>', false);
        $response->assertSee('Kecelakaan Ringan <span class="incident-legend-count">(1)</span>', false);
        $response->assertSee('Panas Bumi <span class="incident-legend-count">(1)</span>', false);
        $response->assertSee('Non-Panas Bumi <span class="incident-legend-count">(1)</span>', false);

        // 5. Pastikan external HTML tooltip diaktifkan untuk mencegah tooltip terpotong batas canvas
        $response->assertSee('externalDonutTooltipHandler', false);
        $response->assertSee('incident-chart-external-tooltip', false);
    }

    public function test_line_chart_tren_kecelakaan_and_highlight_card_modes_and_calculations(): void
    {
        $admin = $this->getAdmin();

        // Gunakan tahun uji khusus yang bersih (2024 - Tahun Kabisat, Februari 29 hari) untuk Mode Harian & Bulanan
        $incPanas1 = Incident::create([
            'nomor_insiden' => 'INC-TREND-2024-001',
            'title' => 'Panas Bumi Feb 10',
            'tanggal_waktu' => '2024-02-10 08:30:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa Uap',
            'deskripsi_kejadian' => 'Uji trend panas bumi 1',
            'status_laporan' => 'Preliminary',
        ]);

        $incPanas2 = Incident::create([
            'nomor_insiden' => 'INC-TREND-2024-002',
            'title' => 'Panas Bumi Feb 29 (Kabisat)',
            'tanggal_waktu' => '2024-02-29 14:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Near Miss (Hampir Celaka)'],
            'kategori_ebtke' => ['Kejadian Berbahaya'],
            'lokasi_kejadian' => 'Power Plant Unit 1',
            'komponen_terkait' => 'Turbin',
            'deskripsi_kejadian' => 'Uji trend panas bumi 2',
            'status_laporan' => 'Final',
        ]);

        Incident::create([
            'nomor_insiden' => 'INC-TREND-2024-003',
            'title' => 'Non-Panas Bumi Feb 10',
            'tanggal_waktu' => '2024-02-10 11:00:00',
            'klasifikasi_area' => 'Kecelakaan Non-Panas Bumi',
            'kategori_umum' => ['First Aid Case'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Kantor Administrasi',
            'komponen_terkait' => 'Gedung Utama',
            'deskripsi_kejadian' => 'Uji trend non panas bumi 1',
            'status_laporan' => 'Preliminary',
        ]);

        Incident::create([
            'nomor_insiden' => 'INC-TREND-2024-004',
            'title' => 'Panas Bumi Juli 15',
            'tanggal_waktu' => '2024-07-15 09:00:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Medical Treatment Case'],
            'kategori_ebtke' => ['Kecelakaan Berat'],
            'lokasi_kejadian' => 'Wellpad 31',
            'komponen_terkait' => 'Separator',
            'deskripsi_kejadian' => 'Uji trend panas bumi Juli',
            'status_laporan' => 'Preliminary',
        ]);

        // 1. Verifikasi Urutan Elemen di Halaman Utama Dashboard Incident:
        // Judul & Breadcrumb -> Line Chart Tren Kecelakaan Kerja & Highlight Card -> 3 Pie Charts -> Filter -> Tabel
        $htmlResponse = $this->actingAs($admin)->get('/incident?trend_mode=harian&trend_year=2024&trend_month=2');
        $htmlResponse->assertStatus(200);
        $content = $htmlResponse->getContent();

        $posHeader = strpos($content, 'class="page-header"');
        $posTrendSection = strpos($content, 'id="incidentTrendHighlightSection"');
        $posPieCharts = strpos($content, 'class="incident-charts-grid"');
        $posFilter = strpos($content, 'class="filter-grid-row"');
        $posTable = strpos($content, '<table class="table table-hover align-middle mb-0">');

        $this->assertNotFalse($posHeader);
        $this->assertNotFalse($posTrendSection);
        $this->assertNotFalse($posPieCharts);
        $this->assertNotFalse($posFilter);
        $this->assertNotFalse($posTable);

        $this->assertLessThan($posTrendSection, $posHeader, 'Section Tren harus berada di bawah Header & Breadcrumb');
        $this->assertLessThan($posPieCharts, $posTrendSection, 'Section Tren harus berada sebelum 3 Pie Charts');
        $this->assertLessThan($posFilter, $posPieCharts, '3 Pie Charts harus berada sebelum Filter');
        $this->assertLessThan($posTable, $posFilter, 'Filter harus berada sebelum Tabel');

        // Pastikan judul dan elemen Highlight Card tersedia
        $htmlResponse->assertSee('Tren Kecelakaan Kerja', false);
        $htmlResponse->assertSee('Total Kejadian', false);
        $htmlResponse->assertSee('id="chartTrenKecelakaanCanvas"', false);
        $htmlResponse->assertSee('id="highlightTotalPanasBumiValue">2</div>', false);
        $htmlResponse->assertSee('flex-wrap: nowrap;', false);
        $htmlResponse->assertSee('justify-content: flex-end;', false);

        // 2. Uji Mode HARIAN (Februari 2024 = 29 Hari) via AJAX JSON
        $dailyResp = $this->actingAs($admin)->getJson('/incident?trend_mode=harian&trend_year=2024&trend_month=2');
        $dailyResp->assertStatus(200);
        $dailyTrend = $dailyResp->json('trendKlasifikasi');

        $this->assertEquals('harian', $dailyTrend['mode']);
        $this->assertCount(29, $dailyTrend['labels']);
        $this->assertEquals('1', $dailyTrend['labels'][0]);
        $this->assertEquals('29', $dailyTrend['labels'][28]);
        $this->assertEquals(1, $dailyTrend['panasBumi'][9]);      // Tanggal 10 Feb = 1 Panas Bumi
        $this->assertEquals(1, $dailyTrend['nonPanasBumi'][9]);   // Tanggal 10 Feb = 1 Non-Panas Bumi
        $this->assertEquals(1, $dailyTrend['panasBumi'][28]);     // Tanggal 29 Feb = 1 Panas Bumi
        $this->assertEquals(0, $dailyTrend['panasBumi'][0]);      // Tanggal 1 Feb = 0
        // Highlight card HANYA menghitung Kecelakaan Panas Bumi pada Februari 2024 (= 2, bukan 3)
        $this->assertEquals(2, $dailyTrend['highlightTotal']);
        $this->assertEquals('#ea580c', $dailyTrend['colors']['panasBumi']);
        $this->assertEquals('#0284c7', $dailyTrend['colors']['nonPanasBumi']);

        // Uji Februari non-kabisat (2025 = 28 Hari)
        $daily2025Resp = $this->actingAs($admin)->getJson('/incident?trend_mode=harian&trend_year=2025&trend_month=2');
        $this->assertCount(28, $daily2025Resp->json('trendKlasifikasi.labels'));

        // 3. Uji Mode BULANAN (Tahun 2024 = Januari s/d Desember)
        $monthlyResp = $this->actingAs($admin)->getJson('/incident?trend_mode=bulanan&trend_year=2024');
        $monthlyResp->assertStatus(200);
        $monthlyTrend = $monthlyResp->json('trendKlasifikasi');

        $this->assertEquals('bulanan', $monthlyTrend['mode']);
        $this->assertEquals(
            ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            $monthlyTrend['labels']
        );
        $this->assertEquals(2, $monthlyTrend['panasBumi'][1]);    // Februari (index 1) = 2 Panas Bumi
        $this->assertEquals(1, $monthlyTrend['nonPanasBumi'][1]); // Februari (index 1) = 1 Non-Panas Bumi
        $this->assertEquals(1, $monthlyTrend['panasBumi'][6]);    // Juli (index 6) = 1 Panas Bumi
        // Highlight card pada tahun 2024 menghitung total Panas Bumi sepanjang 2024 (2 di Feb + 1 di Juli = 3)
        $this->assertEquals(3, $monthlyTrend['highlightTotal']);

        // 4. Uji Tidak Ada Penghitungan Ganda saat Status berubah dari Preliminary menjadi Final
        $this->actingAs($admin)->put(route('incident.update', $incPanas1->id), [
            'title' => $incPanas1->title,
            'tanggal_waktu' => '2024-02-10 08:30:00',
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'lokasi_kejadian' => 'Wellpad 28',
            'komponen_terkait' => 'Pipa Uap',
            'deskripsi_kejadian' => 'Uji trend panas bumi 1 updated ke Final',
            'status_laporan' => 'Final',
        ])->assertRedirect(route('incident.index'));

        $afterUpdateResp = $this->actingAs($admin)->getJson('/incident?trend_mode=bulanan&trend_year=2024');
        $this->assertEquals(3, $afterUpdateResp->json('trendKlasifikasi.highlightTotal'), 'Perubahan status Preliminary ke Final tidak boleh menggandakan hitungan kejadian');

        // 5. Uji Mode TAHUNAN (5 tahun sebelum tahun berjalan s/d 5 tahun setelah tahun berjalan)
        $currentYear = (int) now('Asia/Jakarta')->year;
        $yearlyResp = $this->actingAs($admin)->getJson('/incident?trend_mode=tahunan');
        $yearlyResp->assertStatus(200);
        $yearlyTrend = $yearlyResp->json('trendKlasifikasi');

        $expectedYears = array_map('strval', range($currentYear - 5, $currentYear + 5));
        $this->assertEquals('tahunan', $yearlyTrend['mode']);
        $this->assertCount(11, $yearlyTrend['labels']);
        $this->assertEquals($expectedYears, $yearlyTrend['labels']);
        // Tahun mendatang (currentYear + 5) yang belum memiliki laporan harus bernilai 0
        $this->assertEquals(0, $yearlyTrend['panasBumi'][10]);
        $this->assertEquals(0, $yearlyTrend['nonPanasBumi'][10]);
        // Highlight total harus sama dengan jumlah seluruh elemen panasBumi pada rentang 11 tahun tersebut
        $this->assertEquals(array_sum($yearlyTrend['panasBumi']), $yearlyTrend['highlightTotal']);
    }
}
