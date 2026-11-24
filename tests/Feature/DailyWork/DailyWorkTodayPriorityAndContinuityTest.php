<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyWorkTodayPriorityAndContinuityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.dw.priority@geodipa.co.id'],
            [
                'name' => 'Admin DW Priority Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        DailyWorkCompany::ensureRequired();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Skenario 1-7: Pengujian Skenario Lengkap 24-30 November 2026 (Pekerjaan A, B, C, D, E).
     *
     * Pekerjaan A: 24 Nov 2026 s/d 26 Nov 2026
     * Pekerjaan B: 25 Nov 2026 s/d 26 Nov 2026
     * Pekerjaan C: 26 Nov 2026 s/d 27 Nov 2026
     * Pekerjaan D: 27 Nov 2026 s/d 28 Nov 2026
     * Pekerjaan E: 28 Nov 2026 s/d 29 Nov 2026
     */
    public function test_complete_scenario_from_24_to_30_november(): void
    {
        // Buat kelima pekerjaan dengan status eksplisit Berlangsung
        $workA = DailyWork::create([
            'name' => 'Pekerjaan A',
            'unit' => 'GDE (Only)',
            'start_date' => '2026-11-24',
            'end_date' => '2026-11-26',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area 1',
        ]);

        $workB = DailyWork::create([
            'name' => 'Pekerjaan B',
            'unit' => 'PJSE',
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-26',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area 2',
        ]);

        $workC = DailyWork::create([
            'name' => 'Pekerjaan C',
            'unit' => 'KODIPA',
            'start_date' => '2026-11-26',
            'end_date' => '2026-11-27',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area 3',
        ]);

        $workD = DailyWork::create([
            'name' => 'Pekerjaan D',
            'unit' => 'BISMO',
            'start_date' => '2026-11-27',
            'end_date' => '2026-11-28',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area 4',
        ]);

        $workE = DailyWork::create([
            'name' => 'Pekerjaan E',
            'unit' => 'SOKKO',
            'start_date' => '2026-11-28',
            'end_date' => '2026-11-29',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area 5',
        ]);

        $scenarioIds = [$workA->id, $workB->id, $workC->id, $workD->id, $workE->id];

        // 1. Tanggal 24 November 2026: Harus tampil [A]
        Carbon::setTestNow('2026-11-24 09:00:00');
        $res24 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res24->assertStatus(200);
        $ids24 = collect($res24->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEquals([$workA->id], $ids24, 'Tanggal 24 Nov: Hanya pekerjaan A');

        // 2. Tanggal 25 November 2026: Harus tampil [B (dimulai hari ini), A (masih berlangsung)]
        Carbon::setTestNow('2026-11-25 09:00:00');
        $res25 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res25->assertStatus(200);
        $ids25 = collect($res25->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEquals([$workB->id, $workA->id], $ids25, 'Tanggal 25 Nov: B (dimulai hari ini), lalu A (masih berlangsung)');

        // 3. Tanggal 26 November 2026: Harus tampil [C (dimulai hari ini), B (masih berlangsung), A (masih berlangsung)]
        Carbon::setTestNow('2026-11-26 09:00:00');
        $res26 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res26->assertStatus(200);
        $ids26 = collect($res26->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEquals([$workC->id, $workB->id, $workA->id], $ids26, 'Tanggal 26 Nov: C (dimulai hari ini), B, lalu A (berdasarkan start_date DESC)');

        // 4. Tanggal 27 November 2026: Harus tampil [D (dimulai hari ini), C (masih berlangsung)]
        Carbon::setTestNow('2026-11-27 09:00:00');
        $res27 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res27->assertStatus(200);
        $ids27 = collect($res27->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEquals([$workD->id, $workC->id], $ids27, 'Tanggal 27 Nov: D (dimulai hari ini), lalu C (masih berlangsung)');

        // 5. Tanggal 28 November 2026: Harus tampil [E (dimulai hari ini), D (masih berlangsung)]
        Carbon::setTestNow('2026-11-28 09:00:00');
        $res28 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res28->assertStatus(200);
        $ids28 = collect($res28->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEquals([$workE->id, $workD->id], $ids28, 'Tanggal 28 Nov: E (dimulai hari ini), lalu D (masih berlangsung)');

        // 6. Tanggal 29 November 2026: Harus tampil [E (masih berlangsung)]
        Carbon::setTestNow('2026-11-29 09:00:00');
        $res29 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res29->assertStatus(200);
        $ids29 = collect($res29->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEquals([$workE->id], $ids29, 'Tanggal 29 Nov: Hanya E yang masih berlangsung');

        // 7. Tanggal 30 November 2026: Tidak ada pekerjaan dari skenario tersebut yang tampil
        Carbon::setTestNow('2026-11-30 09:00:00');
        $res30 = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res30->assertStatus(200);
        $ids30 = collect($res30->json('works'))->whereIn('id', $scenarioIds)->pluck('id')->values()->all();
        $this->assertEmpty($ids30, 'Tanggal 30 Nov: Seluruh pekerjaan skenario sudah berakhir');
    }

    /**
     * Skenario Konsistensi Dashboard Komponen:
     * - Total Pekerjaan Hari Ini
     * - Perusahaan Terlibat Hari Ini (unique)
     * - Distribusi Pekerjaan per Perusahaan
     * - Tabel Pekerjaan Hari Ini
     */
    public function test_dashboard_components_consistency(): void
    {
        Carbon::setTestNow('2026-12-05 08:30:00');
        $today = '2026-12-05';

        // 1. Pekerjaan dimulai hari ini oleh PT A
        $w1 = DailyWork::create([
            'name' => 'Pekerjaan Baru PT A',
            'unit' => 'PJSE',
            'start_date' => $today,
            'end_date' => '2026-12-07',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Turbin 1',
        ]);

        // 2. Pekerjaan lain dimulai hari ini juga oleh PT A (perusahaan sama)
        $w2 = DailyWork::create([
            'name' => 'Pekerjaan Lain PT A Hari Ini',
            'unit' => 'PJSE',
            'start_date' => $today,
            'end_date' => '2026-12-05',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Turbin 2',
        ]);

        // 3. Pekerjaan kemarin oleh PT B yang masih berlangsung
        $w3 = DailyWork::create([
            'name' => 'Pekerjaan Ongoing PT B',
            'unit' => 'KODIPA',
            'start_date' => '2026-12-03',
            'end_date' => '2026-12-06',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Wellpad 7',
        ]);

        $testIds = [$w1->id, $w2->id, $w3->id];

        $res = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'today']));
        $res->assertStatus(200);

        // 1. Total Pekerjaan Hari Ini
        $this->assertEquals(3, $res->json('stats.total_today'));

        // 2. Perusahaan Terlibat Hari Ini: PJSE (2 jobs) dan KODIPA (1 job) -> 2 perusahaan unik
        $this->assertEquals(2, $res->json('stats.companies_today'));

        // 3. Distribusi Pekerjaan per Perusahaan
        $distribution = collect($res->json('company_distribution'));
        $pjseDist = $distribution->firstWhere('name', 'PJSE');
        $kodipaDist = $distribution->firstWhere('name', 'KODIPA');
        $this->assertNotNull($pjseDist);
        $this->assertNotNull($kodipaDist);
        $this->assertEquals(2, $pjseDist['count']);
        $this->assertEquals(1, $kodipaDist['count']);

        // 4. Tabel Pekerjaan Hari Ini: w1 dan w2 (dimulai hari ini) harus di atas w3 (ongoing dari kemarin)
        $worksList = collect($res->json('works'))->whereIn('id', $testIds)->values();
        $this->assertCount(3, $worksList);

        $posW1 = $worksList->search(fn ($item) => $item['id'] === $w1->id);
        $posW2 = $worksList->search(fn ($item) => $item['id'] === $w2->id);
        $posW3 = $worksList->search(fn ($item) => $item['id'] === $w3->id);

        $this->assertLessThan($posW3, $posW1, 'Pekerjaan baru hari ini (w1) harus di atas pekerjaan kemarin (w3)');
        $this->assertLessThan($posW3, $posW2, 'Pekerjaan baru hari ini (w2) harus di atas pekerjaan kemarin (w3)');
    }

    /**
     * Pastikan mode Semua Riwayat (date_mode = all) tidak terpengaruh oleh prioritas hari ini.
     */
    public function test_all_history_mode_remains_unaffected(): void
    {
        Carbon::setTestNow('2026-12-10 09:00:00');
        $today = '2026-12-10';

        $wOld = DailyWork::create([
            'name' => 'Pekerjaan Lama ADP',
            'unit' => 'ADP',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'status' => DailyWork::STATUS_COMPLETED,
            'location' => 'Loc 1',
        ]);

        $wToday = DailyWork::create([
            'name' => 'Pekerjaan Baru WCP',
            'unit' => 'WCP',
            'start_date' => $today,
            'end_date' => $today,
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Loc 2',
        ]);

        $res = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', ['date_mode' => 'all']));
        $res->assertStatus(200);

        $worksAll = collect($res->json('works'));
        $targetOld = $worksAll->firstWhere('id', $wOld->id);
        $targetToday = $worksAll->firstWhere('id', $wToday->id);

        $this->assertNotNull($targetOld);
        $this->assertNotNull($targetToday);
        // Pada Semua Riwayat, urutan primer adalah unit ASC (ADP sebelum WCP)
        $posOld = $worksAll->search(fn ($item) => $item['id'] === $wOld->id);
        $posToday = $worksAll->search(fn ($item) => $item['id'] === $wToday->id);
        $this->assertLessThan($posToday, $posOld, 'Semua Riwayat mengurutkan unit ASC (ADP sebelum WCP)');
    }

    /**
     * Pastikan Export PDF pada mode today mengurutkan prioritas hari ini terlebih dahulu.
     */
    public function test_export_pdf_today_mode_applies_priority(): void
    {
        Carbon::setTestNow('2026-12-15 09:00:00');
        $today = '2026-12-15';

        DailyWork::create([
            'name' => 'Ongoing Yesterday',
            'unit' => 'GDE (Only)',
            'start_date' => '2026-12-14',
            'end_date' => '2026-12-16',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area Turbin',
        ]);

        DailyWork::create([
            'name' => 'Started Today',
            'unit' => 'WCP',
            'start_date' => $today,
            'end_date' => '2026-12-15',
            'status' => DailyWork::STATUS_ONGOING,
            'location' => 'Area Subcon',
        ]);

        $pdfRes = $this->actingAs($this->adminUser)->get(route('daily-work.export', [
            'date_mode' => 'today',
        ]));
        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));
    }
}
