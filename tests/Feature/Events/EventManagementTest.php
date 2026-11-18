<?php

namespace Tests\Feature\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::where('role', User::ROLE_ADMIN_MANAGEMENT)->first()
            ?: User::factory()->create([
                'name' => 'Admin Management Tester',
                'email' => 'admin_events_test@geodipa.co.id',
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]);
    }

    public function test_events_page_can_be_rendered_successfully_with_standard_units(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get('/events?per_page=100');

        $response->assertStatus(200);
        $response->assertSee('Events');
        $response->assertSee('Daftar Kegiatan');
        $response->assertSee('GDE (Internal)', false);
        $response->assertSee('BUJP (Security)', false);
        $response->assertSee('PJSE (Utility)', false);
        $response->assertSee('JOTRE', false);
        $response->assertSee('PMC', false);
        $response->assertSee('SOKKO', false);
    }

    public function test_events_filtering_updates_table_stats_and_charts_consistently(): void
    {
        $admin = $this->getAdmin();

        Event::create([
            'title' => 'Pelatihan K3 GDE Khusus Filter',
            'category' => 'Pelatihan',
            'unit' => 'GDE (Internal)',
            'event_date' => '2026-05-10',
            'location' => 'Ruang Training GDE',
            'participants' => 28,
            'pic' => 'Koordinator HSSE',
            'status' => 'Selesai',
            'description' => 'Pelatihan internal GDE',
        ]);

        Event::create([
            'title' => 'Inspeksi Pos Security BUJP Khusus Filter',
            'category' => 'Inspeksi',
            'unit' => 'BUJP (Security)',
            'event_date' => '2026-05-12',
            'location' => 'Pos Utama',
            'participants' => 12,
            'pic' => 'Danru Security',
            'status' => 'Direncanakan',
            'description' => 'Inspeksi kesiapan personel BUJP',
        ]);

        $response = $this->actingAs($admin)->get('/events?category=Pelatihan&unit=' . urlencode('GDE (Internal)') . '&date_range=' . urlencode('01/05/2026 - 31/05/2026'));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan K3 GDE Khusus Filter');
        $response->assertDontSee('Inspeksi Pos Security BUJP Khusus Filter');

        $jsonResponse = $this->actingAs($admin)->getJson('/events?category=Pelatihan&unit=' . urlencode('GDE (Internal)') . '&date_range=' . urlencode('01/05/2026 - 31/05/2026'));
        $jsonResponse->assertStatus(200)
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.selesai', 1)
            ->assertJsonPath('stats.selesai_participants', 28);
    }

    public function test_events_ajax_json_response(): void
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->getJson('/events');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats' => ['total', 'selesai', 'berlangsung', 'direncanakan', 'dibatalkan', 'selesai_participants', 'tahun_ini', 'bulan_ini', 'minggu_ini', 'hari_ini'],
            'monthly' => ['labels', 'datasets'],
            'safeguard_functions',
            'categories',
            'units',
            'events',
        ]);
    }

    public function test_events_safeguard_function_filtering_and_automatic_status(): void
    {
        $admin = $this->getAdmin();

        $pastEvent = Event::create([
            'title' => 'Inspeksi Lingkungan Area Sumur',
            'safeguard_function' => 'Environment',
            'company' => 'EBTKE',
            'event_date' => now()->subDays(3)->toDateString(),
            'status' => 'Berlangsung',
            'description' => 'Pemeriksaan baku mutu lingkungan',
        ]);

        $todayEvent = Event::create([
            'title' => 'Sosialisasi Masyarakat Sekitar Pembangkit',
            'safeguard_function' => 'Social',
            'company' => 'PT Geo Dipa Energi (Persero)',
            'event_date' => now()->toDateString(),
            'status' => 'Direncanakan',
            'description' => 'Program pemberdayaan masyarakat desa',
        ]);

        $res = $this->actingAs($admin)->get('/events?safeguard_function=Environment');
        $res->assertStatus(200);
        $res->assertSee('Inspeksi Lingkungan Area Sumur');
        $res->assertDontSee('Sosialisasi Masyarakat Sekitar Pembangkit');

        // Pastikan seluruh opsi Fungsi Safeguard, 4 Card Ringkasan, dan Struktur Kolom Tabel tampil dengan rapi
        $res->assertSee('Semua Fungsi Safeguard');
        $res->assertSee('Health &amp; Safety', false);
        $res->assertSee('Security');
        $res->assertSee('Environment');
        $res->assertSee('Social');
        $res->assertSee('Tahun Ini');
        $res->assertSee('Bulan Ini');
        $res->assertSee('Minggu Ini');
        $res->assertSee('Hari Ini');
        $res->assertSee('No.');
        $res->assertSee('Fungsi');
        $res->assertSee('Judul Kegiatan');
        $res->assertSee('Tanggal');
        $res->assertSee('Perusahaan/Instansi');
        $res->assertSee('Status');
        $res->assertSee('Aksi');
        $res->assertSee('Kelola Instansi');

        // Pastikan status kegiatan otomatis tersinkronisasi berdasarkan tanggalnya
        $this->assertEquals('Selesai', $pastEvent->fresh()->status);
        $this->assertEquals('Berlangsung', $todayEvent->fresh()->status);

        // Uji penambahan kegiatan baru dengan status "Direncanakan" dan Fungsi Safeguard
        $plannedDate = now()->addDays(5)->toDateString();
        $storePlanned = $this->actingAs($admin)->post('/events', [
            'title' => 'Audit Keamanan Terpadu Mendatang',
            'safeguard_function' => 'Security',
            'company' => 'EBTKE',
            'event_date' => $plannedDate,
            'status' => 'Direncanakan',
            'description' => 'Audit sistem keamanan berkala.',
        ]);
        $storePlanned->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Audit Keamanan Terpadu Mendatang',
            'safeguard_function' => 'Security',
            'status' => 'Direncanakan',
        ]);

        // Pastikan filter status=Direncanakan menampilkan kegiatan tersebut dengan badge info-subtle
        $filterPlanned = $this->actingAs($admin)->get('/events?status=Direncanakan&per_page=100');
        $filterPlanned->assertStatus(200);
        $filterPlanned->assertSee('Audit Keamanan Terpadu Mendatang');
        $filterPlanned->assertSee('bg-info-subtle text-info-emphasis border border-info-subtle', false);

        // Pastikan status "Dibatalkan" tidak ikut ter-override oleh sinkronisasi otomatis maupun Artisan command
        $cancelledEvent = Event::create([
            'title' => 'Kegiatan Dibatalkan Kemarin',
            'safeguard_function' => 'Health & Safety',
            'company' => 'EBTKE',
            'event_date' => now()->subDays(2)->toDateString(),
            'status' => 'Dibatalkan',
        ]);
        \Illuminate\Support\Facades\Artisan::call('events:sync-status');
        $this->assertEquals('Dibatalkan', $cancelledEvent->fresh()->status);

        // Pastikan store tanpa status eksplisit menentukan status otomatis dari tanggal kegiatan
        $autoFutureRes = $this->actingAs($admin)->post('/events', [
            'title' => 'Kegiatan Auto Future',
            'safeguard_function' => 'Environment',
            'company' => 'EBTKE',
            'event_date' => now()->addDays(7)->toDateString(),
        ]);
        $autoFutureRes->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Kegiatan Auto Future',
            'status' => 'Direncanakan',
        ]);
    }

    public function test_events_show_details_and_standalone_show_view(): void
    {
        $admin = $this->getAdmin();
        $event = Event::first() ?: Event::create([
            'title' => 'Safety Talk Pagi',
            'category' => 'Safety Talk',
            'unit' => 'GDE (Internal)',
            'event_date' => '2026-03-01',
            'location' => 'Lapangan Apel',
            'participants' => 20,
            'status' => 'Selesai',
            'pic' => 'Petugas HSSE',
        ]);

        $jsonRes = $this->actingAs($admin)->getJson("/events/{$event->id}");
        $jsonRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                ],
            ]);

        $viewRes = $this->actingAs($admin)->get("/events/{$event->id}");
        $viewRes->assertStatus(200)
            ->assertSee($event->title)
            ->assertSee('Kembali ke Daftar Events');
    }

    public function test_events_store_creates_event_documentation_and_logs(): void
    {
        $admin = $this->getAdmin();
        Storage::fake('public');

        $file = UploadedFile::fake()->image('dokumentasi-test.jpg');

        $data = [
            'title' => 'Simulasi Kebakaran Lapangan Baru',
            'category' => 'Simulasi/Drill',
            'unit' => 'GDE (Internal)',
            'event_date' => '2025-10-15',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Area Turbin',
            'participants' => 35,
            'pic' => 'Koordinator Tanggap Darurat',
            'status' => 'Selesai',
            'description' => 'Simulasi tanggap darurat dan uji coba fire alarm.',
            'documentation_files' => [$file],
        ];

        $response = $this->actingAs($admin)->post('/events', $data);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'title' => 'Simulasi Kebakaran Lapangan Baru',
            'category' => 'Simulasi/Drill',
            'unit' => 'GDE (Internal)',
            'participants' => 35,
            'created_by' => $admin->name,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'events',
            'action' => 'TAMBAH',
            'user_name' => $admin->name,
        ]);

        $createdEvent = Event::where('title', 'Simulasi Kebakaran Lapangan Baru')->firstOrFail();
        $this->assertCount(1, $createdEvent->documents);

        // Test direct documentation upload endpoint
        $extraDoc = UploadedFile::fake()->create('laporan-simulasi.pdf', 120, 'application/pdf');
        $uploadRes = $this->actingAs($admin)->postJson("/events/{$createdEvent->id}/upload", [
            'files' => [$extraDoc],
        ]);
        $uploadRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertCount(2, $createdEvent->fresh()->documents);
    }

    public function test_events_update(): void
    {
        $admin = $this->getAdmin();
        $event = Event::first() ?: Event::create([
            'title' => 'Kegiatan Awal',
            'category' => 'Inspeksi',
            'unit' => 'PJSE (Utility)',
            'event_date' => '2026-01-10',
            'location' => 'Area Utility',
            'participants' => 15,
            'status' => 'Direncanakan',
        ]);

        $response = $this->actingAs($admin)->put("/events/{$event->id}", [
            'title' => 'Judul Kegiatan Terupdate',
            'category' => $event->category,
            'unit' => 'PMC',
            'event_date' => $event->event_date->format('Y-m-d'),
            'location' => 'Area Turbin Dieng',
            'participants' => 45,
            'status' => 'Berlangsung',
            'description' => 'Deskripsi update.',
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Judul Kegiatan Terupdate',
            'unit' => 'PMC',
            'participants' => 45,
            'status' => 'Berlangsung',
            'updated_by' => $admin->name,
        ]);
    }

    public function test_events_destroy(): void
    {
        $admin = $this->getAdmin();
        $event = Event::create([
            'title' => 'Event Hapus Temporary',
            'category' => 'Lainnya',
            'unit' => 'SOKKO',
            'event_date' => '2025-12-31',
            'location' => 'Temporary Area',
            'participants' => 5,
            'status' => 'Direncanakan',
            'pic' => 'Tester',
        ]);

        $response = $this->actingAs($admin)->delete("/events/{$event->id}");

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    public function test_events_export_pdf_and_role_permissions(): void
    {
        $admin = $this->getAdmin();
        $response = $this->actingAs($admin)->get('/events/export');

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));

        // Admin Inventory tidak diizinkan mengakses modul Events (hanya Peminjaman Barang)
        $adminInventory = User::factory()->create([
            'role' => User::ROLE_ADMIN_INVENTORY,
            'status' => 'aktif',
        ]);
        $this->actingAs($adminInventory)->get('/events')->assertStatus(403);
        $this->actingAs($adminInventory)->get('/events/export')->assertStatus(403);
        $this->actingAs($adminInventory)->post('/events', ['title' => 'Tidak Boleh'])->assertStatus(403);

        // User boleh melihat Events dan mengunduh PDF, tetapi tidak boleh mengubah data
        $regularUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'status' => 'aktif',
        ]);
        $this->actingAs($regularUser)->get('/events')->assertStatus(200)
            ->assertSee('id="btnExportPdf"', false);
        $this->actingAs($regularUser)->get('/events/export')->assertStatus(200);
        $this->actingAs($regularUser)->post('/events', ['title' => 'Tidak Boleh'])->assertStatus(403);
    }

    public function test_events_export_pdf_with_photo_documentation_and_filters(): void
    {
        $admin = $this->getAdmin();
        Storage::fake('public');

        $imageFile = UploadedFile::fake()->image('dokumentasi-audit.jpg', 600, 400);

        // Event dengan foto
        $eventWithPhoto = Event::create([
            'title' => 'Audit K3 Lapangan dengan Dokumentasi Foto',
            'company' => 'PT Geo Dipa Energi (Persero)',
            'category' => 'Inspeksi',
            'event_date' => '2026-06-15',
            'location' => 'Area Pembangkit Dieng',
            'status' => 'Selesai',
            'created_by' => $admin->name,
        ]);

        $storedPath = $imageFile->store('events/documentation', 'public');
        \App\Models\Document::create([
            'title' => 'Foto Audit',
            'document_type' => 'Foto Kegiatan',
            'module' => 'events',
            'record_id' => $eventWithPhoto->id,
            'file_name' => 'dokumentasi-audit.jpg',
            'file_path' => $storedPath,
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'uploaded_by' => $admin->name,
        ]);

        // Event tanpa foto
        Event::create([
            'title' => 'Koordinasi Harian Tanpa Foto',
            'company' => 'EBTKE',
            'category' => 'Safety Talk',
            'event_date' => '2026-06-16',
            'location' => 'Ruang Rapat',
            'status' => 'Direncanakan',
            'created_by' => $admin->name,
        ]);

        // Export PDF dengan filter
        $response = $this->actingAs($admin)->get('/events/export?status=All&search=Audit');
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));

        // Export PDF dengan filter checklist selektif (Kategori Fungsi Safeguard & Perusahaan/Instansi)
        $responseChecklist = $this->actingAs($admin)->get('/events/export?' . http_build_query([
            'safeguard_functions' => ['Health & Safety', 'Environment'],
            'companies' => ['PT Geo Dipa Energi (Persero)', 'EBTKE'],
        ]));
        $responseChecklist->assertStatus(200);
        $this->assertTrue(str_contains($responseChecklist->headers->get('content-type'), 'application/pdf'));

        // Export PDF tanpa filter
        $responseAll = $this->actingAs($admin)->get('/events/export');
        $responseAll->assertStatus(200);
        $this->assertTrue(str_contains($responseAll->headers->get('content-type'), 'application/pdf'));
    }

    public function test_events_multi_file_upload_mixed_types_and_update_without_losing_docs(): void
    {
        $admin = $this->getAdmin();
        Storage::fake('public');

        $imgFile = UploadedFile::fake()->image('foto-kegiatan-1.png', 500, 500);
        $pdfFile = UploadedFile::fake()->create('laporan-resmi.pdf', 200, 'application/pdf');
        $docxFile = UploadedFile::fake()->create('notulensi.docx', 150, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $xlsxFile = UploadedFile::fake()->create('daftar-hadir.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 1. Tambah Event dengan campuran foto, PDF, DOCX, dan XLSX
        $data = [
            'title' => 'Rapat Gabungan Multi-Dokumen',
            'company' => 'PT Geo Dipa Energi (Persero)',
            'category' => 'Sosialisasi',
            'event_date' => '2026-07-20',
            'location' => 'Gedung Serbaguna',
            'status' => 'Selesai',
            'documentation_files' => [$imgFile, $pdfFile, $docxFile, $xlsxFile],
        ];

        $createResponse = $this->actingAs($admin)->post('/events', $data);
        $createResponse->assertRedirect(route('events.index'));

        $event = Event::where('title', 'Rapat Gabungan Multi-Dokumen')->firstOrFail();
        $this->assertCount(4, $event->documents);

        // 2. Edit Event tanpa mengunggah berkas baru (Dokumentasi lama tidak boleh hilang)
        $updateResponse = $this->actingAs($admin)->put("/events/{$event->id}", [
            'title' => 'Rapat Gabungan Multi-Dokumen (Diperbarui)',
            'company' => 'PT Geo Dipa Energi (Persero)',
            'category' => 'Sosialisasi',
            'event_date' => '2026-07-20',
            'location' => 'Ruang Rapat Utama',
            'status' => 'Selesai',
        ]);
        $updateResponse->assertRedirect(route('events.index'));

        $event->refresh();
        $this->assertEquals('Rapat Gabungan Multi-Dokumen (Diperbarui)', $event->title);
        $this->assertCount(4, $event->documents);

        // 3. Tambah berkas tambahan melalui upload langsung
        $extraDoc = UploadedFile::fake()->create('lampiran-tambahan.pdf', 50, 'application/pdf');
        $uploadRes = $this->actingAs($admin)->postJson("/events/{$event->id}/upload", [
            'files' => [$extraDoc],
        ]);
        $uploadRes->assertStatus(200);
        $this->assertCount(5, $event->fresh()->documents);

        // 4. Unduh berkas dokumen
        $firstDoc = $event->documents()->first();
        $downloadRes = $this->actingAs($admin)->get("/events/documentation/{$firstDoc->id}/download");
        $downloadRes->assertStatus(200);

        // 5. Unduh zip/all dokumentasi event
        $downloadAllRes = $this->actingAs($admin)->get("/events/{$event->id}/download-doc");
        $downloadAllRes->assertStatus(200);
    }
}

