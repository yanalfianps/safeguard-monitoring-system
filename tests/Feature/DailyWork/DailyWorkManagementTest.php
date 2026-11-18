<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\Sikka;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyWorkManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminManagement;
    protected User $adminInventory;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminManagement = User::firstOrCreate(
            ['email' => 'admin.dw.test@geodipa.co.id'],
            [
                'name' => 'Admin Management Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminManagement->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        $this->adminInventory = User::firstOrCreate(
            ['email' => 'inventory.dw.test@geodipa.co.id'],
            [
                'name' => 'Admin Inventory Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_INVENTORY,
                'status' => 'aktif',
            ]
        );
        $this->adminInventory->update(['role' => User::ROLE_ADMIN_INVENTORY, 'status' => 'aktif']);

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user.dw.test@geodipa.co.id'],
            [
                'name' => 'User Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_USER,
                'status' => 'aktif',
            ]
        );
        $this->regularUser->update(['role' => User::ROLE_USER, 'status' => 'aktif']);

        $this->actingAs($this->adminManagement);
    }

    /**
     * 1. Pastikan tidak ada label SIKKA atau PTW Classification pada tampilan Daily Work.
     */
    public function test_daily_work_views_do_not_contain_sikka_or_ptw_classification_labels(): void
    {
        $work = DailyWork::create([
            'name' => 'Inspeksi Turbin Harian',
            'unit' => 'GDE (Internal)',
            'sika' => 'Cold Work',
            'start_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'location' => 'Powerhouse Unit 1',
            'description' => 'Pemeriksaan rutin sistem turbin.',
        ]);

        // Cek halaman utama Daily Work
        $indexResponse = $this->get(route('daily-work.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Daily Work');
        $indexResponse->assertSee('SIKA');
        $indexResponse->assertSee('Semua SIKA');
        $indexResponse->assertSee('workSikaContainer');
        $indexResponse->assertSee('Export PDF Hari Ini');
        $indexResponse->assertDontSee('PTW Classification');
        $indexResponse->assertDontSee('Semua PTW');
        $indexResponse->assertDontSee('Pilih PTW');
        $indexResponse->assertDontSee('>SIKKA<', false);
        $indexResponse->assertDontSee('Semua SIKKA');
        $indexResponse->assertDontSee('Pilih SIKKA');

        // Cek halaman detail pekerjaan
        $showResponse = $this->get(route('daily-work.show', $work->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Detail Pekerjaan Harian');
        $showResponse->assertSee('SIKA');
        $showResponse->assertSee('Cold Work');
        $showResponse->assertDontSee('PTW Classification');
        $showResponse->assertDontSee('SIKKA');
    }

    /**
     * 2. Pastikan master SIKA berisi pilihan aktif (tanpa Cold Work & Hot Work) dan dapat dikelola melalui CRUD.
     */
    public function test_sika_dropdown_has_all_eight_initial_options_and_supports_crud(): void
    {
        $expectedOptions = [
            'Ruang Terbatas',
            'Ketinggian',
            'Penggalian',
            'Listrik',
            'Isolasi Energi',
            'Lifting',
            'Panas',
        ];

        $response = $this->get(route('daily-work.index'));
        $response->assertStatus(200);

        foreach ($expectedOptions as $option) {
            $response->assertSee($option);
            $this->assertDatabaseHas('sikkas', [
                'name' => $option,
                'is_active' => true,
            ]);
        }

        // Pastikan Cold Work dan Hot Work yang sudah dihapus TIDAK muncul di master sikkas
        $this->assertDatabaseMissing('sikkas', ['name' => 'Cold Work']);
        $this->assertDatabaseMissing('sikkas', ['name' => 'Hot Work']);

        // Test Create SIKA via CRUD Master
        $storeRes = $this->postJson(route('daily-work.masters.sika.store'), [
            'name' => 'Radiografi Industri',
        ]);
        $storeRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "SIKA 'Radiografi Industri' berhasil ditambahkan.",
            ]);
        $sikaId = $storeRes->json('data.id');
        $this->assertDatabaseHas('sikkas', ['id' => $sikaId, 'name' => 'Radiografi Industri']);

        // Test Update SIKA via CRUD Master
        $updateRes = $this->putJson(route('daily-work.masters.sika.update', $sikaId), [
            'name' => 'Radiografi & NDT',
        ]);
        $updateRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'SIKA berhasil diperbarui.',
            ]);
        $this->assertDatabaseHas('sikkas', ['id' => $sikaId, 'name' => 'Radiografi & NDT']);

        // Test Delete SIKA via CRUD Master
        $deleteRes = $this->deleteJson(route('daily-work.masters.sika.destroy', $sikaId));
        $deleteRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "SIKA 'Radiografi & NDT' berhasil dihapus.",
            ]);
        $this->assertDatabaseMissing('sikkas', ['id' => $sikaId]);
    }

    /**
     * 3. Pastikan CRUD Daily Work dengan SIKA berjalan lancar dan data lama (Cold Work / Hot Work) tetap aman.
     */
    public function test_daily_work_crud_with_sika_and_legacy_cold_hot_work_preservation(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Buat pekerjaan dengan SIKA = Hot Work
        $storeRes = $this->postJson(route('daily-work.store'), [
            'name' => 'Pengelasan Jalur Pipa Uap Pad 7',
            'unit' => 'PMC',
            'sika' => 'Hot Work',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Wellpad 7',
            'description' => 'Pengelasan support pipa utama.',
        ]);

        $storeRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('work.sika', 'Hot Work')
            ->assertJsonPath('work.sikka', 'Hot Work');

        $workId = $storeRes->json('work.id');

        // Update pekerjaan menjadi Cold Work
        $updateRes = $this->putJson(route('daily-work.update', $workId), [
            'name' => 'Inspeksi Visual Jalur Pipa Uap Pad 7',
            'unit' => 'PMC',
            'sika' => 'Cold Work',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Wellpad 7',
            'description' => 'Inspeksi selesai.',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('work.sika', 'Cold Work')
            ->assertJsonPath('work.sikka', 'Cold Work');

        // Simulasikan data lama yang hanya mengisi ptw_classification tanpa sikka
        $legacyWork = DailyWork::create([
            'name' => 'Pekerjaan Legacy PTW Hot Work',
            'unit' => 'JOTRE',
            'ptw_classification' => 'Hot Work',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Area Brine',
        ]);

        $this->assertEquals('Hot Work', $legacyWork->fresh()->sikka);
        $this->assertEquals('Hot Work', $legacyWork->fresh()->sika);
    }

    /**
     * 4. Pastikan Export PDF Mode Hari Ini mengikuti tanggal dan filter yang dipilih.
     */
    public function test_export_pdf_today_mode_respects_selected_date_and_filters(): void
    {
        $customDate = '2026-11-15';

        DailyWork::create([
            'name' => 'Pekerjaan Khusus Tanggal 15 Nov GDE',
            'unit' => 'GDE (Only)',
            'sika' => 'Cold Work',
            'start_date' => $customDate,
            'end_date' => $customDate,
            'location' => 'Unit 1',
        ]);

        DailyWork::create([
            'name' => 'Pekerjaan Khusus Tanggal 15 Nov JOTRE',
            'unit' => 'JOTRE (Only)',
            'sika' => 'Hot Work',
            'start_date' => $customDate,
            'end_date' => $customDate,
            'location' => 'Switchyard',
        ]);

        // Filter Mode Hari Ini pada tanggal custom + filter perusahaan GDE (Only)
        $jsonRes = $this->getJson(route('daily-work.index', [
            'date_mode' => 'today',
            'date' => $customDate,
            'company' => 'GDE (Only)',
        ]));
        $jsonRes->assertStatus(200);
        $this->assertCount(1, $jsonRes->json('works'));
        $this->assertEquals('Pekerjaan Khusus Tanggal 15 Nov GDE', $jsonRes->json('works.0.name'));

        // Export PDF dengan parameter yang sama
        $pdfRes = $this->get(route('daily-work.export', [
            'date_mode' => 'today',
            'date' => $customDate,
            'company' => 'GDE (Only)',
        ]));
        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));
    }

    /**
     * 5. Pastikan Export PDF Mode Semua Riwayat mengunduh seluruh data (masa lalu, hari ini, mendatang) atau sesuai filter.
     */
    public function test_export_pdf_history_mode_includes_past_today_and_future_works(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $pastDate = '2024-05-10';
        $futureDate = '2027-08-20';

        $pastWork = DailyWork::create([
            'name' => 'Riwayat Masa Lalu Unik 2024',
            'unit' => 'GDE (Internal)',
            'sika' => 'Ruang Terbatas',
            'start_date' => $pastDate,
            'end_date' => $pastDate,
            'location' => 'Pad 10',
        ]);

        $todayWork = DailyWork::create([
            'name' => 'Pekerjaan Hari Ini Unik',
            'unit' => 'PJSE (Utility)',
            'sika' => 'Listrik',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Powerhouse',
        ]);

        $futureWork = DailyWork::create([
            'name' => 'Pekerjaan Mendatang Unik 2027',
            'unit' => 'PMC',
            'sika' => 'Lifting',
            'start_date' => $futureDate,
            'end_date' => $futureDate,
            'location' => 'Workshop',
        ]);

        // Pastikan mode 'all' tanpa filter tanggal memuat masa lalu, hari ini, dan mendatang
        $allRes = $this->getJson(route('daily-work.index', [
            'date_mode' => 'all',
        ]));
        $allRes->assertStatus(200);
        $returnedIds = collect($allRes->json('works'))->pluck('id')->all();
        $this->assertContains($pastWork->id, $returnedIds);
        $this->assertContains($todayWork->id, $returnedIds);
        $this->assertContains($futureWork->id, $returnedIds);

        // Export PDF Mode Semua Riwayat
        $pdfAllRes = $this->get(route('daily-work.export', [
            'date_mode' => 'all',
        ]));
        $pdfAllRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfAllRes->headers->get('content-type'));
    }

    /**
     * 6. Pastikan hak akses ekspor PDF untuk ketiga role berfungsi sesuai ketentuan:
     *    - Admin Management: boleh (200)
     *    - User: boleh (200)
     *    - Admin Inventory: ditolak (403)
     */
    public function test_export_pdf_role_permissions_for_all_three_roles(): void
    {
        // 1. Admin Management -> Boleh mengunduh PDF (200)
        $this->actingAs($this->adminManagement)
            ->get(route('daily-work.export', ['date_mode' => 'today']))
            ->assertStatus(200);

        $this->actingAs($this->adminManagement)
            ->get(route('daily-work.export', ['date_mode' => 'all']))
            ->assertStatus(200);

        // 2. User -> Boleh mengunduh PDF (200)
        $this->actingAs($this->regularUser)
            ->get(route('daily-work.index'))
            ->assertStatus(200)
            ->assertSee('Export PDF Hari Ini');

        $this->actingAs($this->regularUser)
            ->get(route('daily-work.export', ['date_mode' => 'today']))
            ->assertStatus(200);

        $this->actingAs($this->regularUser)
            ->get(route('daily-work.export', ['date_mode' => 'all']))
            ->assertStatus(200);

        // 3. Admin Inventory -> Ditolak mengakses modul Daily Work (403)
        $this->actingAs($this->adminInventory)
            ->get(route('daily-work.index'))
            ->assertStatus(403);

        $this->actingAs($this->adminInventory)
            ->get(route('daily-work.export', ['date_mode' => 'today']))
            ->assertStatus(403);

        $this->actingAs($this->adminInventory)
            ->get(route('daily-work.export', ['date_mode' => 'all']))
            ->assertStatus(403);
    }
}
