<?php

namespace Tests\Feature\Workforce;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\ManHour;
use App\Models\User;
use App\Models\Workforce;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyManagementAndManHoursCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected array $cleanupCompanyIds = [];
    protected array $cleanupDates = [];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'Admin Test',
                'email' => 'admin_test_' . Str::random(5) . '@example.com',
                'role' => 'admin',
                'is_active' => true,
            ]);
        }
        $this->admin = $admin;
    }

    protected function tearDown(): void
    {
        if (!empty($this->cleanupCompanyIds)) {
            $compNames = DailyWorkCompany::whereIn('id', $this->cleanupCompanyIds)->pluck('name')->all();
            if (!empty($compNames)) {
                $wfIds = Workforce::whereIn('unit', $compNames)->orWhereIn('category', $compNames)->pluck('id');
                ManHour::whereIn('workforce_id', $wfIds)->orWhereIn('unit', $compNames)->delete();
                Workforce::whereIn('id', $wfIds)->delete();
            }
            DailyWork::whereIn('company_id', $this->cleanupCompanyIds)->delete();
            DailyWorkCompany::whereIn('id', $this->cleanupCompanyIds)->delete();
        }

        if (!empty($this->cleanupDates)) {
            $wfIds = Workforce::whereIn('record_date', $this->cleanupDates)->pluck('id');
            ManHour::whereIn('workforce_id', $wfIds)->orWhereIn('work_date', $this->cleanupDates)->delete();
            Workforce::whereIn('record_date', $this->cleanupDates)->delete();
        }

        parent::tearDown();
    }

    public function test_admin_can_delete_both_old_and_new_companies_when_no_relations(): void
    {
        // 1. Perusahaan lama (is_required = true) tanpa relasi
        $oldComp = DailyWorkCompany::create([
            'name' => 'Perusahaan Lama Uji ' . Str::random(5),
            'category' => DailyWorkCompany::CATEGORY_INDEPENDENT,
            'is_active' => true,
            'is_required' => true,
            'sort_order' => 99,
        ]);
        $this->cleanupCompanyIds[] = $oldComp->id;

        $resOld = $this->actingAs($this->admin)->deleteJson("/daily-work/masters/companies/{$oldComp->id}");
        $resOld->assertStatus(200);
        $resOld->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseMissing('daily_work_companies', ['id' => $oldComp->id]);

        // 2. Perusahaan baru (is_required = false) tanpa relasi
        $newComp = DailyWorkCompany::create([
            'name' => 'Perusahaan Baru Uji ' . Str::random(5),
            'category' => DailyWorkCompany::CATEGORY_INDEPENDENT,
            'is_active' => true,
            'is_required' => false,
            'sort_order' => 100,
        ]);
        $this->cleanupCompanyIds[] = $newComp->id;

        $resNew = $this->actingAs($this->admin)->deleteJson("/daily-work/masters/companies/{$newComp->id}");
        $resNew->assertStatus(200);
        $resNew->assertJson([
            'success' => true,
        ]);
        $this->assertDatabaseMissing('daily_work_companies', ['id' => $newComp->id]);
    }

    public function test_company_with_subcon_or_relations_is_protected_from_deletion(): void
    {
        // 1. Perusahaan yang memiliki anak (subcon)
        $parentComp = DailyWorkCompany::create([
            'name' => 'Induk Test ' . Str::random(5),
            'category' => DailyWorkCompany::CATEGORY_MAIN,
            'is_active' => true,
            'is_required' => false,
        ]);
        $this->cleanupCompanyIds[] = $parentComp->id;

        $childComp = DailyWorkCompany::create([
            'name' => 'Anak Test ' . Str::random(5),
            'category' => DailyWorkCompany::CATEGORY_SUBCON,
            'parent_company_id' => $parentComp->id,
            'is_active' => true,
            'is_required' => false,
        ]);
        $this->cleanupCompanyIds[] = $childComp->id;

        $resParent = $this->actingAs($this->admin)->deleteJson("/daily-work/masters/companies/{$parentComp->id}");
        $resParent->assertStatus(422);
        $resParent->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertStringContainsString('induk dari subcon', $resParent->json('message'));

        // 2. Perusahaan yang digunakan pada DailyWork
        $usedInDw = DailyWorkCompany::create([
            'name' => 'Used In DW ' . Str::random(5),
            'category' => DailyWorkCompany::CATEGORY_INDEPENDENT,
            'is_active' => true,
            'is_required' => false,
        ]);
        $this->cleanupCompanyIds[] = $usedInDw->id;

        DailyWork::create([
            'name' => 'Pekerjaan Uji Relasi',
            'company_id' => $usedInDw->id,
            'unit' => $usedInDw->name,
            'location' => 'Turbine Area',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'status' => 'Sedang Berlangsung',
        ]);

        $resDw = $this->actingAs($this->admin)->deleteJson("/daily-work/masters/companies/{$usedInDw->id}");
        $resDw->assertStatus(422);
        $resDw->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertStringContainsString('data pekerjaan harian', $resDw->json('message'));

        // 3. Perusahaan yang digunakan pada Man Hours / Workforce
        $usedInMh = DailyWorkCompany::create([
            'name' => 'Used In MH ' . Str::random(5),
            'category' => DailyWorkCompany::CATEGORY_INDEPENDENT,
            'is_active' => true,
            'is_required' => false,
        ]);
        $this->cleanupCompanyIds[] = $usedInMh->id;

        Workforce::create([
            'record_date' => now()->toDateString(),
            'category' => $usedInMh->name,
            'name' => $usedInMh->name,
            'unit' => $usedInMh->name,
            'worker_count' => 5,
            'hours_per_worker' => 8,
        ]);

        $resMh = $this->actingAs($this->admin)->deleteJson("/daily-work/masters/companies/{$usedInMh->id}");
        $resMh->assertStatus(422);
        $resMh->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertStringContainsString('Man Hours & Manpower', $resMh->json('message'));
    }

    public function test_man_hours_calculation_and_preservation_of_manual_subtotal(): void
    {
        $testDate = '2039-11-20';
        $this->cleanupDates[] = $testDate;
        $compName = 'GDE (Only)';

        // 10 pekerja x 8 jam = subtotal manual diubah menjadi 100
        $payload = [
            'record_date' => $testDate,
            'units' => [
                [
                    'unit' => $compName,
                    'category' => $compName,
                    'worker_count' => 10,
                    'hours_per_worker' => 8,
                    'subtotal_man_hours' => 100,
                ],
            ],
        ];

        $storeRes = $this->actingAs($this->admin)->postJson('/man-hours/batch', $payload);
        $storeRes->assertStatus(200);
        $storeRes->assertJson(['success' => true]);

        // Verifikasi di database Workforce: jam_kerja tetap 8 (tidak dibagi menjadi 10)
        $wf = Workforce::whereDate('record_date', $testDate)->where('unit', $compName)->first();
        $this->assertNotNull($wf);
        $this->assertEquals(10, $wf->worker_count);
        $this->assertEquals(8.0, (float) $wf->hours_per_worker, 'Jam kerja di Workforce harus tetap 8, tidak dibagi oleh subtotal');

        // Verifikasi di database ManHour: jam_kerja tetap 8, total_hours sesuai input pengguna (100)
        $mh = ManHour::where('workforce_id', $wf->id)->first();
        $this->assertNotNull($mh);
        $this->assertEquals(10, $mh->number_of_workers);
        $this->assertEquals(8.0, (float) $mh->hours_per_worker, 'Jam kerja di ManHour harus tetap 8');
        $this->assertEquals(100.0, (float) $mh->total_hours, 'Total jam kerja harus sesuai input manual pengguna (100)');

        // Verifikasi pemanggilan endpoint /man-hours/by-date untuk mengedit data tanggal tersebut
        $byDateRes = $this->actingAs($this->admin)->getJson("/man-hours/by-date?record_date={$testDate}");
        $byDateRes->assertStatus(200);
        $units = $byDateRes->json('units');
        $unitData = collect($units)->firstWhere('unit_name', $compName);
        $this->assertNotNull($unitData);
        $this->assertEquals(10, $unitData['worker_count']);
        $this->assertEquals(8.0, (float) $unitData['hours_per_worker'], 'Jam kerja pada getByDate harus tetap 8');
        $this->assertEquals(100.0, (float) $unitData['calculated_man_hours'], 'Subtotal man hours pada getByDate harus 100');
    }

    public function test_newly_added_companies_always_appear_at_the_bottom_of_list(): void
    {
        $existingRootIds = DailyWorkCompany::ordered()
            ->whereNull('parent_company_id')
            ->pluck('id')
            ->all();

        $suffix = Str::random(5);
        $nameFirst = "ZZZ Mitra Pertama {$suffix}";
        $nameSecond = "AAA Mitra Kedua {$suffix}";

        // 1. Tambah perusahaan baru pertama (dengan kategori 'main' seperti dari form Tambah Perusahaan)
        $res1 = $this->actingAs($this->admin)->postJson('/daily-work/masters/companies', [
            'name' => $nameFirst,
            'category' => 'main',
            'description' => 'Mitra Baru 1',
            'is_active' => true,
        ]);
        $res1->assertOk()->assertJsonPath('success', true);
        $id1 = $res1->json('data.id');
        $this->cleanupCompanyIds[] = $id1;

        $companiesAfter1 = collect($res1->json('companies'))
            ->whereNull('parent_company_id')
            ->values();
        $this->assertSame($id1, $companiesAfter1->last()['id'], 'Perusahaan baru pertama harus berada di urutan paling bawah');

        // 2. Tambah perusahaan baru kedua dengan awalan huruf A (tidak boleh naik ke atas karena abjad)
        $res2 = $this->actingAs($this->admin)->postJson('/daily-work/masters/companies', [
            'name' => $nameSecond,
            'category' => 'main',
            'description' => 'Mitra Baru 2',
            'is_active' => true,
        ]);
        $res2->assertOk()->assertJsonPath('success', true);
        $id2 = $res2->json('data.id');
        $this->cleanupCompanyIds[] = $id2;

        $companiesAfter2 = collect($res2->json('companies'))
            ->whereNull('parent_company_id')
            ->pluck('id')
            ->all();

        // Pastikan urutan perusahaan lama tidak berubah sama sekali dan dua perusahaan baru berada paling bawah
        $expectedRootOrder = array_merge($existingRootIds, [$id1, $id2]);
        $this->assertSame($expectedRootOrder, $companiesAfter2, 'Urutan perusahaan lama harus tetap dan perusahaan baru berada paling bawah sesuai urutan penambahan');
    }

    public function test_date_filtering_synchronizes_all_cards_recap_and_charts(): void
    {
        // Bersihkan di dalam transaksi pengujian (otomatis di-rollback oleh DatabaseTransactions setelah test selesai)
        ManHour::query()->delete();
        Workforce::query()->delete();

        $dateNov10 = '2026-11-10';
        $dateNov17 = '2026-11-17';
        $dateNov18 = '2026-11-18';
        $dateSep05 = '2026-09-05';

        // SKENARIO E: Belum ada data sama sekali di database
        $htmlEmpty = $this->actingAs($this->admin)->get('/man-hours');
        $htmlEmpty->assertOk();
        $htmlEmpty->assertViewHas('referenceSidebarDate', null);
        $htmlEmpty->assertSee('Belum ada data');

        $resEmpty = $this->actingAs($this->admin)->getJson('/man-hours');
        $resEmpty->assertOk();
        $this->assertNull($resEmpty->json('stats.latest_activity_date'));
        $this->assertEquals('-', $resEmpty->json('stats.latest_day_name'));
        $this->assertEquals('Belum ada data', $resEmpty->json('stats.latest_date_only'));
        $this->assertEquals(0, $resEmpty->json('stats.manpower_today'));
        $this->assertEquals(0, $resEmpty->json('stats.manpower_max'));
        $this->assertEquals('-', $resEmpty->json('stats.manpower_max_date'));
        $this->assertEquals(0, $resEmpty->json('stats.manhours_today'));
        $this->assertEquals(0, $resEmpty->json('stats.manhours_cumulative'));
        $this->assertEquals(0, $resEmpty->json('todayTotalWorkers'));
        $this->assertEmpty($resEmpty->json('trendChart.workers'));

        // Input data historis (05 Sep 2026: 50 org -> 400 jam, menjadi rekor Manpower Terbanyak global)
        $this->actingAs($this->admin)->postJson('/man-hours/batch', [
            'record_date' => $dateSep05,
            'units' => [
                ['unit' => 'GDE (Only)', 'category' => 'GDE (Only)', 'worker_count' => 50, 'hours_per_worker' => 8, 'subtotal_man_hours' => 400],
            ],
        ])->assertOk();

        // Input data 10 Nov 2026: GDE (Only) = 10 org (80 jam), KODIPA = 5 org (40 jam) -> Total = 15 org, 120 jam
        $this->actingAs($this->admin)->postJson('/man-hours/batch', [
            'record_date' => $dateNov10,
            'units' => [
                ['unit' => 'GDE (Only)', 'category' => 'GDE (Only)', 'worker_count' => 10, 'hours_per_worker' => 8, 'subtotal_man_hours' => 80],
                ['unit' => 'KODIPA', 'category' => 'KODIPA', 'worker_count' => 5, 'hours_per_worker' => 8, 'subtotal_man_hours' => 40],
            ],
        ])->assertOk();

        // Input data 17 Nov 2026: GDE (Only) = 20 org (160 jam) -> Total = 20 org, 160 jam
        $this->actingAs($this->admin)->postJson('/man-hours/batch', [
            'record_date' => $dateNov17,
            'units' => [
                ['unit' => 'GDE (Only)', 'category' => 'GDE (Only)', 'worker_count' => 20, 'hours_per_worker' => 8, 'subtotal_man_hours' => 160],
            ],
        ])->assertOk();

        // SKENARIO A: Belum ada input pada hari kalender terbaru (18 Nov 2026), data terakhir di DB adalah 17 Nov 2026
        // Total Kumulatif saat ini = 400 + 120 + 160 = 680 Jam
        $htmlScenarioA = $this->actingAs($this->admin)->get('/man-hours');
        $htmlScenarioA->assertOk();
        $htmlScenarioA->assertViewHas('referenceSidebarDate', '2026-11-17');
        $htmlScenarioA->assertSee('17 November 2026');

        $resScenarioA = $this->actingAs($this->admin)->getJson('/man-hours');
        $resScenarioA->assertOk();
        $this->assertEquals('2026-11-17', $resScenarioA->json('stats.latest_activity_date'));
        $this->assertEquals('Selasa', $resScenarioA->json('stats.latest_day_name'));
        $this->assertEquals('17 November 2026', $resScenarioA->json('stats.latest_date_only'), 'Skenario A: Tanggal utama harus menampilkan 17 November 2026, bukan tanggal realtime');
        $this->assertEquals(20, $resScenarioA->json('stats.manpower_today'), 'Skenario A: Manpower (Today) harus mengikuti data 17 November 2026 (20)');
        $this->assertEquals(160, $resScenarioA->json('stats.manhours_today'), 'Skenario A: Man Hours (Today) harus mengikuti data 17 November 2026 (160)');
        $this->assertEquals(50, $resScenarioA->json('stats.manpower_max'));
        $this->assertEquals('05 September 2026', $resScenarioA->json('stats.manpower_max_date'));
        $this->assertEquals(680, $resScenarioA->json('stats.manhours_cumulative'));

        // SKENARIO B: Pengguna memasukkan data baru untuk tanggal 18 November 2026 (ADP = 8 org, 64 jam)
        $this->actingAs($this->admin)->postJson('/man-hours/batch', [
            'record_date' => $dateNov18,
            'units' => [
                ['unit' => 'ADP', 'category' => 'ADP', 'worker_count' => 8, 'hours_per_worker' => 8, 'subtotal_man_hours' => 64],
            ],
        ])->assertOk();

        // Total Kumulatif baru = 680 + 64 = 744 Jam
        $resScenarioB = $this->actingAs($this->admin)->getJson('/man-hours');
        $resScenarioB->assertOk();
        $this->assertEquals('2026-11-18', $resScenarioB->json('stats.latest_activity_date'));
        $this->assertEquals('Rabu', $resScenarioB->json('stats.latest_day_name'));
        $this->assertEquals('18 November 2026', $resScenarioB->json('stats.latest_date_only'), 'Skenario B: Tanggal utama otomatis berubah menjadi 18 November 2026 setelah simpan');
        $this->assertEquals(8, $resScenarioB->json('stats.manpower_today'), 'Skenario B: Manpower (Today) mengikuti data 18 November 2026 (8)');
        $this->assertEquals(64, $resScenarioB->json('stats.manhours_today'), 'Skenario B: Man Hours (Today) mengikuti data 18 November 2026 (64)');
        $this->assertEquals(50, $resScenarioB->json('stats.manpower_max'));
        $this->assertEquals('05 September 2026', $resScenarioB->json('stats.manpower_max_date'));
        $this->assertEquals(744, $resScenarioB->json('stats.manhours_cumulative'));

        // SKENARIO C: Filter grafik diubah ke rentang 01–17 November 2026 (mencakup 10 Nov & 17 Nov -> 15+20=35 org, 120+160=280 jam)
        $resScenarioC = $this->actingAs($this->admin)->getJson('/man-hours?period_type=custom&start_date=2026-11-01&end_date=2026-11-17');
        $resScenarioC->assertOk();
        // Tanggal utama & keempat kartu statistik TIDAK berubah sama sekali
        $this->assertEquals('18 November 2026', $resScenarioC->json('stats.latest_date_only'), 'Skenario C: Tanggal utama tetap 18 November 2026');
        $this->assertEquals(8, $resScenarioC->json('stats.manpower_today'), 'Skenario C: Kartu Manpower (Today) statis tidak terpengaruh filter (tetap 8)');
        $this->assertEquals(64, $resScenarioC->json('stats.manhours_today'), 'Skenario C: Kartu Man Hours (Today) statis tidak terpengaruh filter (tetap 64)');
        $this->assertEquals(50, $resScenarioC->json('stats.manpower_max'), 'Skenario C: Kartu Manpower (Terbanyak) statis tidak terpengaruh filter (tetap 50)');
        $this->assertEquals('05 September 2026', $resScenarioC->json('stats.manpower_max_date'));
        $this->assertEquals(744, $resScenarioC->json('stats.manhours_cumulative'), 'Skenario C: Kartu Man Hours (Kumulatif) statis tidak terpengaruh filter (tetap 744)');
        // Grafik dan Rekap Tenaga Kerja mengikuti rentang 01–17 November 2026
        $this->assertEquals(35, $resScenarioC->json('todayTotalWorkers'), 'Skenario C: Rekap Tenaga Kerja mengikuti rentang 01–17 Nov (35 org)');
        $this->assertEquals(280, $resScenarioC->json('todayTotalManhours'), 'Skenario C: Rekap Man Hours mengikuti rentang 01–17 Nov (280 jam)');
        $this->assertEquals([15, 20], $resScenarioC->json('trendChart.workers'), 'Skenario C: Grafik hanya menampilkan titik dalam rentang 01–17 Nov');
        $this->assertEquals([120.0, 160.0], $resScenarioC->json('trendChart.manhours'));

        // SKENARIO D: Filter diubah kembali ke rentang lain (10 Nov 2026 saja -> 15 org, 120 jam)
        $resScenarioD = $this->actingAs($this->admin)->getJson('/man-hours?period_type=daily&start_date=2026-11-10&end_date=2026-11-10');
        $resScenarioD->assertOk();
        // Keempat kartu & tanggal utama tetap sama
        $this->assertEquals('18 November 2026', $resScenarioD->json('stats.latest_date_only'));
        $this->assertEquals(8, $resScenarioD->json('stats.manpower_today'));
        $this->assertEquals(64, $resScenarioD->json('stats.manhours_today'));
        $this->assertEquals(50, $resScenarioD->json('stats.manpower_max'));
        $this->assertEquals(744, $resScenarioD->json('stats.manhours_cumulative'));
        // Grafik dan Rekap diperbarui ke 10 Nov 2026
        $this->assertEquals(15, $resScenarioD->json('todayTotalWorkers'));
        $this->assertEquals(120, $resScenarioD->json('todayTotalManhours'));
        $this->assertEquals([15], $resScenarioD->json('trendChart.workers'));
    }
}
