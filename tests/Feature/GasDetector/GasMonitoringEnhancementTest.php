<?php

namespace Tests\Feature\GasDetector;

use App\Models\GasMonitoring;
use App\Models\User;
use App\Models\WellPad;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GasMonitoringEnhancementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.gas.enh@geodipa.co.id'],
            [
                'name' => 'Admin Gas Enhancement',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]
        );
        $this->admin->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'Aktif']);

        WellPad::ensureDefaultsSeeded();
    }

    public function test_five_cards_use_worst_measurement_on_latest_date_and_ignore_null_and_filters(): void
    {
        // Bersihkan data transaksi test agar isolasi akurat
        GasMonitoring::query()->delete();

        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $yesterday = Carbon::now('Asia/Jakarta')->subDay()->format('Y-m-d');

        // Data kemarin (tidak boleh masuk ke 5 kartu)
        GasMonitoring::create([
            'measurement_date' => $yesterday,
            'well_pad' => 'Well Pad 7',
            'co_level' => 99.0,
            'o2_level' => 15.0,
            'h2s_level' => 88.0,
            'lel_level' => 44.0,
            'pressure' => 999.0,
        ]);

        // Data hari ini (tanggal terbaru) dari beberapa Well Pad
        GasMonitoring::create([
            'measurement_date' => $today,
            'well_pad' => 'Well Pad 7',
            'co_level' => 10.0,
            'o2_level' => 20.8,
            'h2s_level' => 2.0,
            'lel_level' => 1.0,
            'pressure' => null, // NULL diabaikan
        ]);

        GasMonitoring::create([
            'measurement_date' => $today,
            'well_pad' => 'Well Pad 9',
            'co_level' => 28.0, // tertinggi -> Tidak Sesuai (>25)
            'o2_level' => 18.9, // terendah -> Tidak Sesuai (<19.5)
            'h2s_level' => 6.5, // tertinggi -> Sesuai (<=10)
            'lel_level' => 3.5, // tertinggi -> Sesuai (<=5)
            'pressure' => null, // semua NULL -> harus '-'
        ]);

        $summary = GasMonitoring::getLatestDailySummary($today);
        $cards = $summary['cards'];

        $this->assertEquals('28 ppm', $cards['co']['val']);
        $this->assertEquals('Tidak Sesuai', $cards['co']['status']);

        $this->assertEquals('18.9 %', $cards['o2']['val']);
        $this->assertEquals('Tidak Sesuai', $cards['o2']['status']);

        $this->assertEquals('6.5 ppm', $cards['h2s']['val']);
        $this->assertEquals('Sesuai', $cards['h2s']['status']);

        $this->assertEquals('3.5 %', $cards['lel']['val']);
        $this->assertEquals('Sesuai', $cards['lel']['status']);

        $this->assertEquals('-', $cards['pressure']['val']);

        // Pastikan 5 kartu tetap statis saat filter Semua Riwayat diterapkan
        $resFiltered = $this->actingAs($this->admin)->get(route('gas-detector.index', [
            'section' => 'semua_riwayat',
            'history_period_type' => 'yearly',
            'history_year' => 2020,
            'history_well_pad' => 'Well Pad 7',
        ]));
        $resFiltered->assertStatus(200);
        $resFiltered->assertSee('28 ppm');
        $resFiltered->assertSee('18.9 %');
    }

    public function test_today_section_shows_only_today_records_and_all_history_shows_all_records(): void
    {
        GasMonitoring::query()->delete();

        $today = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $oldDate = Carbon::now('Asia/Jakarta')->subMonths(3)->format('Y-m-d');

        GasMonitoring::create([
            'measurement_date' => $today,
            'well_pad' => 'Well Pad 7',
            'co_level' => 5.0,
            'o2_level' => 20.9,
            'notes' => 'MARKER_HARI_INI',
        ]);

        GasMonitoring::create([
            'measurement_date' => $oldDate,
            'well_pad' => 'Well Pad 9',
            'co_level' => 7.0,
            'o2_level' => 20.5,
            'notes' => 'MARKER_LAMA',
        ]);

        $res = $this->actingAs($this->admin)->get(route('gas-detector.index'));
        $res->assertStatus(200);
        $res->assertViewHas('todayRecords', fn ($pag) => $pag->total() === 1);
        $res->assertViewHas('historyTableRecords', fn ($pag) => $pag->total() === 2);
        $res->assertSee('btnSectionHariIni', false);
        $res->assertSee('btnSectionSemuaRiwayat', false);
    }

    public function test_yearly_history_chart_uses_worst_measurement_per_month_and_null_for_empty_months(): void
    {
        GasMonitoring::query()->delete();

        // Januari 2026 untuk Well Pad 7: beberapa pengukuran
        $janDates = [
            ['2026-01-01', 2.0, 20.5, 1.0, 0.5, 10.0],
            ['2026-01-05', 5.0, 19.8, 3.0, 1.2, 14.0],
            ['2026-01-12', 3.0, 18.5, 2.0, 2.5, 12.0], // O2 terendah = 18.5
            ['2026-01-20', 8.0, 20.0, 6.0, 1.5, 18.0], // CO tertinggi = 8, H2S = 6, Pressure = 18
            ['2026-01-28', 4.0, 19.5, 2.5, 4.0, 11.0], // LEL tertinggi = 4
        ];

        foreach ($janDates as [$dt, $co, $o2, $h2s, $lel, $press]) {
            GasMonitoring::create([
                'measurement_date' => $dt,
                'well_pad' => 'Well Pad 7',
                'co_level' => $co,
                'o2_level' => $o2,
                'h2s_level' => $h2s,
                'lel_level' => $lel,
                'pressure' => $press,
            ]);
        }

        // Data Well Pad 9 pada Januari (tidak boleh tercampur ke grafik Well Pad 7)
        GasMonitoring::create([
            'measurement_date' => '2026-01-15',
            'well_pad' => 'Well Pad 9',
            'co_level' => 50.0,
            'o2_level' => 14.0,
        ]);

        $res = $this->actingAs($this->admin)->getJson(route('gas-detector.history-chart', [
            'history_period_type' => 'yearly',
            'history_year' => 2026,
            'history_well_pad' => 'Well Pad 7',
        ]));

        $res->assertStatus(200);
        $res->assertJson(['success' => true, 'wellPadName' => 'Well Pad 7']);

        $chart = $res->json('chartData');
        $this->assertCount(12, $chart['labels']);
        $this->assertEquals('Jan', $chart['labels'][0]);
        $this->assertEquals('Des', $chart['labels'][11]);

        // Januari: worst measurement (bukan rata-rata)
        $this->assertEquals(8, $chart['co']['data'][0]);
        $this->assertEquals(18.5, $chart['o2']['data'][0]);
        $this->assertEquals(6, $chart['h2s']['data'][0]);
        $this->assertEquals(4, $chart['lel']['data'][0]);
        $this->assertEquals(18, $chart['pressure']['data'][0]);

        // Tooltip date untuk CO ekstrem (20 Jan 2026) & O2 ekstrem (12 Jan 2026)
        $this->assertStringContainsString('20 Jan', $chart['co']['dates'][0]);
        $this->assertStringContainsString('12 Jan', $chart['o2']['dates'][0]);

        // Februari kosong -> harus null, bukan 0
        $this->assertNull($chart['co']['data'][1]);
        $this->assertNull($chart['o2']['data'][1]);
    }

    public function test_monthly_and_custom_history_chart_and_date_validation(): void
    {
        GasMonitoring::query()->delete();

        // Dua pengukuran pada tanggal yang sama (2026-11-05) untuk Well Pad 7
        GasMonitoring::create([
            'measurement_date' => '2026-11-05',
            'well_pad' => 'Well Pad 7',
            'co_level' => 4.0,
            'o2_level' => 21.0,
        ]);
        GasMonitoring::create([
            'measurement_date' => '2026-11-05',
            'well_pad' => 'Well Pad 7',
            'co_level' => 11.0,
            'o2_level' => 19.2,
        ]);

        // Bulanan November 2026 (30 hari)
        $resMonth = $this->actingAs($this->admin)->getJson(route('gas-detector.history-chart', [
            'history_period_type' => 'monthly',
            'history_month' => 11,
            'history_year' => 2026,
            'history_well_pad' => 'Well Pad 7',
        ]));
        $resMonth->assertStatus(200);
        $monthChart = $resMonth->json('chartData');
        $this->assertCount(30, $monthChart['labels']);
        // Tanggal 5 (index 4) harus worst measurement: CO=11, O2=19.2
        $this->assertEquals(11, $monthChart['co']['data'][4]);
        $this->assertEquals(19.2, $monthChart['o2']['data'][4]);
        // Tanggal 1 (index 0) kosong -> null
        $this->assertNull($monthChart['co']['data'][0]);

        // Rentang Tanggal valid
        $resCustom = $this->actingAs($this->admin)->getJson(route('gas-detector.history-chart', [
            'history_period_type' => 'custom',
            'history_start_date' => '2026-11-04',
            'history_end_date' => '2026-11-06',
            'history_well_pad' => 'Well Pad 7',
        ]));
        $resCustom->assertStatus(200);
        $customChart = $resCustom->json('chartData');
        $this->assertCount(3, $customChart['labels']);
        $this->assertNull($customChart['co']['data'][0]);
        $this->assertEquals(11, $customChart['co']['data'][1]);
        $this->assertNull($customChart['co']['data'][2]);

        // Rentang Tanggal tidak valid (start > end) -> 422
        $resInvalid = $this->actingAs($this->admin)->getJson(route('gas-detector.history-chart', [
            'history_period_type' => 'custom',
            'history_start_date' => '2026-11-10',
            'history_end_date' => '2026-11-05',
            'history_well_pad' => 'Well Pad 7',
        ]));
        $resInvalid->assertStatus(422);
    }
}
