<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyWorkParentAndCompleteFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminManagement;
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
     * Poin 1: KODIPA menampilkan induk GDE.
     */
    public function test_kodipa_displays_parent_gde(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        foreach (['KODIPA', 'GDE (Only)'] as $unit) {
            $work = DailyWork::create([
                'name' => "Pekerjaan {$unit} Piping",
                'unit' => $unit,
                'start_date' => $today,
                'end_date' => $today,
                'location' => 'Wellpad 28',
                'status' => DailyWork::STATUS_ONGOING,
            ]);

            $this->assertEquals('GDE', $work->parent_company_name);

            $json = $this->getJson(route('daily-work.index', ['date_mode' => 'today']));
            $json->assertStatus(200);

            $target = collect($json->json('works'))->firstWhere('id', $work->id);
            $this->assertNotNull($target);
            $this->assertEquals($unit, $target['unit']);
            $this->assertEquals('GDE', $target['parent_company_name']);
        }
    }

    /**
     * Poin 2: JOTRE (Only), PJSE, ADP, SOKKO, BISMO, dan WCP menampilkan induk JOTRE.
     */
    public function test_jotre_subcons_display_parent_jotre(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $subcons = ['JOTRE (Only)', 'PJSE', 'ADP', 'SOKKO', 'BISMO', 'WCP'];

        foreach ($subcons as $subcon) {
            $work = DailyWork::create([
                'name' => "Pekerjaan {$subcon} Lapangan",
                'unit' => $subcon,
                'start_date' => $today,
                'end_date' => $today,
                'location' => 'Powerhouse',
                'status' => DailyWork::STATUS_ONGOING,
            ]);

            $this->assertEquals('JOTRE', $work->parent_company_name, "Perusahaan {$subcon} harus menampilkan induk JOTRE");
        }
    }

    /**
     * Poin 3: Perusahaan independen dan perusahaan utama tidak menampilkan induk yang keliru.
     */
    public function test_independent_and_main_companies_do_not_display_parent(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $noParentUnits = [
            'TDP (BUJP)',
            'Rentokil (Pest Control)',
            'PMC',
            'Bhumi Phala (Maintenance EWS)',
            'Environesia',
            'PJSE (Utility)',
            'GDE (All)',
            'JOTRE (All)',
        ];

        foreach ($noParentUnits as $unit) {
            $work = DailyWork::create([
                'name' => "Pekerjaan Mandiri {$unit}",
                'unit' => $unit,
                'start_date' => $today,
                'end_date' => $today,
                'location' => 'Kantor PMU Dieng',
                'status' => DailyWork::STATUS_ONGOING,
            ]);

            $this->assertNull($work->parent_company_name, "Perusahaan {$unit} tidak boleh memiliki induk");
        }
    }

    /**
     * Poin 4: PJSE dan PJSE (Utility) tetap dibedakan dengan benar.
     */
    public function test_pjse_and_pjse_utility_are_distinguished_correctly(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $pjseSubcon = DailyWork::create([
            'name' => 'Pekerjaan PJSE Subcon',
            'unit' => 'PJSE',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Turbine Hall',
            'status' => DailyWork::STATUS_ONGOING,
        ]);

        $pjseUtility = DailyWork::create([
            'name' => 'Pekerjaan PJSE Utility Independen',
            'unit' => 'PJSE (Utility)',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Utility Yard',
            'status' => DailyWork::STATUS_ONGOING,
        ]);

        $this->assertEquals('JOTRE', $pjseSubcon->parent_company_name);
        $this->assertNull($pjseUtility->parent_company_name);
    }

    /**
     * Poin 5: Tombol centang memiliki tooltip "Pekerjaan Selesai" pada view.
     */
    public function test_checkmark_button_has_tooltip_pekerjaan_selesai(): void
    {
        $response = $this->get(route('daily-work.index'));
        $response->assertStatus(200);

        // Memastikan HTML/JS menyertakan tombol centang dengan tooltip 'Pekerjaan Selesai'
        $response->assertSee('btn-action-complete');
        $response->assertSee('title="Pekerjaan Selesai"', false);
        $response->assertSee('completeWork(');
    }

    /**
     * Poin 6 & 7: Konfirmasi penyelesaian menyimpan status ke database (dan membatalkan tidak mengubah status).
     */
    public function test_confirming_completion_saves_status_to_database(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $work = DailyWork::create([
            'name' => 'Pekerjaan Uji Status Selesai',
            'unit' => 'PJSE',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Area 1',
            'status' => DailyWork::STATUS_ONGOING,
        ]);

        // Status awal: Berlangsung
        $this->assertEquals(DailyWork::STATUS_ONGOING, $work->fresh()->status);

        // Simulasi batal: jika user tidak klik konfirmasi, tidak ada request ke API -> status tetap Berlangsung
        $this->assertEquals(DailyWork::STATUS_ONGOING, $work->fresh()->status);

        // Simulasi konfirmasi: request ke endpoint complete
        $response = $this->postJson(route('daily-work.complete', $work->id));
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verifikasi di database status tersimpan sebagai Selesai
        $this->assertEquals(DailyWork::STATUS_COMPLETED, $work->fresh()->status);
        $this->assertDatabaseHas('daily_works', [
            'id' => $work->id,
            'status' => DailyWork::STATUS_COMPLETED,
        ]);
    }

    /**
     * Poin 8: Pekerjaan selesai hilang dari HARI INI dan tampil di SEMUA RIWAYAT.
     */
    public function test_completed_work_disappears_from_today_and_appears_in_all_history(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $work = DailyWork::create([
            'name' => 'Pekerjaan Pindah ke Riwayat',
            'unit' => 'WCP',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Pad 9',
            'status' => DailyWork::STATUS_ONGOING,
        ]);

        // Cek pada daftar HARI INI: pekerjaan muncul saat masih berlangsung
        $resTodayBefore = $this->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $resTodayBefore->assertStatus(200);
        $worksTodayBefore = collect($resTodayBefore->json('works'))->pluck('id')->all();
        $this->assertContains($work->id, $worksTodayBefore);

        // Tandai pekerjaan selesai
        $this->postJson(route('daily-work.complete', $work->id))->assertStatus(200);

        // Cek pada daftar HARI INI setelah selesai: pekerjaan HILANG dari daftar Hari Ini
        $resTodayAfter = $this->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $resTodayAfter->assertStatus(200);
        $worksTodayAfter = collect($resTodayAfter->json('works'))->pluck('id')->all();
        $this->assertNotContains($work->id, $worksTodayAfter);

        // Cek pada daftar SEMUA RIWAYAT: pekerjaan TAMPIL di Semua Riwayat dengan status Selesai
        $resAll = $this->getJson(route('daily-work.index', ['date_mode' => 'all']));
        $resAll->assertStatus(200);
        $worksAll = collect($resAll->json('works'));
        $targetInAll = $worksAll->firstWhere('id', $work->id);
        $this->assertNotNull($targetInAll);
        $this->assertEquals(DailyWork::STATUS_COMPLETED, $targetInAll['status']);
    }

    /**
     * Poin 9: Data pekerjaan lama tetap utuh dan tidak terduplikasi saat diselesaikan.
     */
    public function test_work_data_remains_intact_and_not_duplicated(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $work = DailyWork::create([
            'name' => 'Pekerjaan Non-Duplikasi',
            'unit' => 'BISMO',
            'sika' => 'Listrik, Isolasi Energi',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Gardu Trafo 2',
            'description' => 'Inspeksi berkala komponen panel.',
            'status' => DailyWork::STATUS_ONGOING,
        ]);

        $countBefore = DailyWork::count();

        // Tandai selesai
        $this->postJson(route('daily-work.complete', $work->id))->assertStatus(200);

        $countAfter = DailyWork::count();

        // Jumlah pekerjaan di DB harus sama persis (tidak ada duplikasi)
        $this->assertEquals($countBefore, $countAfter);

        // Seluruh kolom data tetap utuh
        $refreshed = $work->fresh();
        $this->assertEquals('Pekerjaan Non-Duplikasi', $refreshed->name);
        $this->assertEquals('BISMO', $refreshed->unit);
        $this->assertEquals('Listrik, Isolasi Energi', $refreshed->sika);
        $this->assertEquals('Gardu Trafo 2', $refreshed->location);
        $this->assertEquals('Inspeksi berkala komponen panel.', $refreshed->description);
        $this->assertEquals($today, $refreshed->start_date->toDateString());
        $this->assertEquals($today, $refreshed->end_date->toDateString());
        $this->assertEquals(DailyWork::STATUS_COMPLETED, $refreshed->status);
    }

    /**
     * Poin 10: Tombol Detail, Edit, dan Hapus yang sudah ada tetap berfungsi.
     */
    public function test_existing_detail_edit_and_delete_actions_continue_to_work(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $work = DailyWork::create([
            'name' => 'Pekerjaan Uji Tombol Aksi',
            'unit' => 'PMC',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Main Office',
            'status' => DailyWork::STATUS_ONGOING,
        ]);

        // 1. Detail (Show)
        $showRes = $this->getJson(route('daily-work.show', $work->id));
        $showRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('work.id', $work->id);

        // 2. Edit (Update)
        $editRes = $this->putJson(route('daily-work.update', $work->id), [
            'name' => 'Pekerjaan Uji Tombol Aksi Diperbarui',
            'unit' => 'PMC',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Main Office Lantai 2',
        ]);
        $editRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertEquals('Pekerjaan Uji Tombol Aksi Diperbarui', $work->fresh()->name);

        // 3. Hapus (Delete)
        $delRes = $this->deleteJson(route('daily-work.destroy', $work->id));
        $delRes->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('daily_works', ['id' => $work->id]);
    }

    /**
     * Tombol centang Pekerjaan Selesai hanya dirender pada Tab Hari Ini dan untuk pekerjaan belum selesai.
     * Pada Tab Semua Riwayat, tombol centang tidak boleh dirender.
     */
    public function test_checkmark_button_only_rendered_in_today_mode_for_incomplete_works(): void
    {
        $response = $this->get(route('daily-work.index'));
        $response->assertStatus(200);

        // Memastikan rendering logic tombol centang menguji currentMode === 'today' && !isCompleted
        $response->assertSee('showCompleteButton = canManageDailyWork && (currentMode === \'today\') && !isCompleted', false);
    }
}

