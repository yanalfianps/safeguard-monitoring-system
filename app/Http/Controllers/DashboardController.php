<?php

namespace App\Http\Controllers;

use App\Models\BudgetNonPo;
use App\Models\BudgetPo;
use App\Models\BudgetWbs;
use App\Models\DailyWork;
use App\Models\Event;
use App\Models\GasMonitoring;
use App\Models\Incident;
use App\Models\IssueFollowUp;
use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Tampilkan halaman utama Dashboard dengan data aktual database.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'unit' => $request->input('unit', 'all'),
            'category' => $request->input('category', 'all'),
            'year' => (int) $request->input('year', date('Y')),
        ];

        $data = $this->dashboardService->getDashboardData($filters);

        // Sinkronisasi data 2 card utama (Jam Kerja Aman Kumulatif & Total Manpower Aktif)
        // Mengambil akumulasi all-time dan record terkini selaras dengan modul "Man hours & Manpower"
        $data['core_cards'] = $this->dashboardService->getDashboardCoreCards();

        // 1. Total Akumulatif Man Hours (data kumulatif terkini dari tabel/modul Manhour)
        $total_man_hours = (float) ($data['core_cards']['man_hours']['total_hours'] ?? 0);

        // 2. Total Insiden Panas Bumi (hanya klasifikasi area "Panas Bumi")
        $total_insiden_panas_bumi = Incident::where('klasifikasi_area', 'not like', '%Non%')
            ->where('klasifikasi_area', 'like', '%Panas Bumi%')
            ->count();

        // 3. Total LTI (tag "Lost Time Injury (LTI)" pada kategori umum)
        $total_lti = Incident::where(function ($q) {
            $q->whereJsonContains('kategori_umum', 'LTI (Lost Time Injury)')
              ->orWhere('kategori_umum', 'like', '%Lost Time Injury%')
              ->orWhere('kategori_umum', 'like', '%LTI%');
        })->count();

        // 4. Total LWD (sum dari kolom "Jumlah Hari Hilang (LWD)" pada seluruh data insiden)
        $total_lwd = (int) Incident::sum('jumlah_hari_hilang');

        // Kalkulasi Indikator HSSE (Faktor Pengali 1.000.000)
        $trir = ($total_man_hours > 0) ? ($total_insiden_panas_bumi / $total_man_hours) * 1000000 : 0;
        $lti_fr = ($total_man_hours > 0) ? ($total_lti / $total_man_hours) * 1000000 : 0;
        $lti_sr = ($total_man_hours > 0) ? ($total_lwd / $total_man_hours) * 1000000 : 0;

        // Passing variabel ke View
        $data['total_man_hours'] = $total_man_hours;
        $data['total_insiden_panas_bumi'] = $total_insiden_panas_bumi;
        $data['total_lti'] = $total_lti;
        $data['total_lwd'] = $total_lwd;
        $data['trir'] = $trir;
        $data['lti_fr'] = $lti_fr;
        $data['lti_sr'] = $lti_sr;

        // Sinkronkan ke hsse_metrics agar tetap kompatibel
        $data['hsse_metrics']['total_man_hours'] = $total_man_hours;
        $data['hsse_metrics']['total_insiden_panas_bumi'] = $total_insiden_panas_bumi;
        $data['hsse_metrics']['total_lti'] = $total_lti;
        $data['hsse_metrics']['total_lwd'] = $total_lwd;
        $data['hsse_metrics']['trir'] = $trir;
        $data['hsse_metrics']['lti_fr'] = $lti_fr;
        $data['hsse_metrics']['lti_sr'] = $lti_sr;

        // 4 Kategori Fungsi Safeguard untuk Donut Charts "Issue & Follow Up"
        // Urutan: 1. Health & Safety, 2. Security, 3. Environment, 4. Social
        $issue_categories = [
            [
                'name' => 'Health & Safety',
                'slug' => 'health-safety',
                'code' => 'H',
            ],
            [
                'name' => 'Security',
                'slug' => 'security',
                'code' => 'S_SECURITY',
            ],
            [
                'name' => 'Environment',
                'slug' => 'environment',
                'code' => 'E',
            ],
            [
                'name' => 'Social',
                'slug' => 'social',
                'code' => 'S_SOCIAL',
            ],
        ];

        $issue_follow_up_charts = [];
        foreach ($issue_categories as $cat) {
            $query = IssueFollowUp::byDivision($cat['code']);
            $total = (clone $query)->count();
            $open_count = (clone $query)->where('status', 'Open')->count();
            $closed_count = (clone $query)->whereIn('status', ['Closed', 'Close'])->count();
            $open_percentage = $total > 0 ? (int) round(($open_count / $total) * 100) : 0;
            $closed_percentage = $total > 0 ? (int) round(($closed_count / $total) * 100) : 0;

            $issue_follow_up_charts[] = [
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'code' => $cat['code'],
                'total' => $total,
                'open_count' => $open_count,
                'closed_count' => $closed_count,
                'open_percentage' => $open_percentage,
                'closed_percentage' => $closed_percentage,
            ];
        }

        $data['issue_follow_up_charts'] = $issue_follow_up_charts;

        // Kalkulasi Progress Waktu Proyek Dieng 2 (Carbon)
        // Tanggal Mulai: 29 Desember 2025, Tanggal Selesai: 28 Maret 2028
        Carbon::setLocale('id');
        $start_date = Carbon::parse('2025-12-29')->locale('id');
        $end_date   = Carbon::parse('2028-03-28')->locale('id');
        $today      = Carbon::now()->locale('id');

        // Batasi $today agar tidak melebihi end_date untuk perhitungan rasio
        $passed_today = $today->copy()->min($end_date)->max($start_date);

        // 1. Kalkulasi HARI
        $total_days  = (int) $start_date->diffInDays($end_date);
        $passed_days = (int) $start_date->diffInDays($passed_today);
        $rem_days    = $total_days - $passed_days;

        // 2. Kalkulasi MINGGU
        $total_weeks  = (int) $start_date->diffInWeeks($end_date);
        $passed_weeks = (int) $start_date->diffInWeeks($passed_today);
        $rem_weeks    = $total_weeks - $passed_weeks;

        // 3. Kalkulasi BULAN (Mutlak dalam satuan bulan, tanpa konversi ke tahun)
        $total_months  = (int) $start_date->diffInMonths($end_date);
        $passed_months = (int) $start_date->diffInMonths($passed_today);
        $rem_months    = $total_months - $passed_months;

        // Persentase Progress Waktu
        if ($today->lt($start_date)) {
            $progress_percentage = 0.0;
        } elseif ($today->gt($end_date)) {
            $progress_percentage = 100.0;
        } else {
            $progress_percentage = $total_days > 0
                ? round(($passed_days / $total_days) * 100, 2)
                : 0.0;
        }

        // Passing seluruh metrik waktu ke view
        $data['start_date']    = $start_date;
        $data['end_date']      = $end_date;
        $data['today']         = $today;
        $data['passed_today']  = $passed_today;
        $data['total_days']    = $total_days;
        $data['passed_days']   = $passed_days;
        $data['rem_days']      = $rem_days;
        $data['total_weeks']   = $total_weeks;
        $data['passed_weeks']  = $passed_weeks;
        $data['rem_weeks']     = $rem_weeks;
        $data['total_months']  = $total_months;
        $data['passed_months'] = $passed_months;
        $data['rem_months']    = $rem_months;
        $data['progress_percentage'] = $progress_percentage;

        // Alias untuk kompatibilitas
        $data['total_duration_days'] = $total_days;
        $data['days_passed']         = $passed_days;
        $data['days_remaining']      = $rem_days;
        $data['weeks_passed']        = $passed_weeks;
        $data['months_passed']       = $passed_months;

        // Sinkronisasi data project_timeline
        $data['project_timeline'] = [
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d'),
            'start_date_display' => $start_date->translatedFormat('d F Y'),
            'end_date_display' => $end_date->translatedFormat('d F Y'),
            'now_date_display' => $today->translatedFormat('d F Y'),
            'total_days' => $total_days,
            'elapsed_days' => $passed_days,
            'remaining_days' => $rem_days,
            'percentage' => $progress_percentage,
            'percentage_display' => number_format($progress_percentage, 2, ',', '.') . '%',
            'elapsed_weeks' => $passed_weeks,
            'total_weeks' => $total_weeks,
            'remaining_weeks' => $rem_weeks,
            'elapsed_months' => $passed_months,
            'total_months' => $total_months,
            'remaining_months' => $rem_months,
        ];

        // Query Data untuk Card "Monitoring Anggaran" (WBS, PO, & Non-PO)
        $wbsIds = BudgetWbs::where('is_active', true)->pluck('id')->toArray();
        $total_budget = (float) BudgetWbs::where('is_active', true)->sum('current_budget');

        $totalPo = !empty($wbsIds) ? (float) BudgetPo::whereIn('budget_wbs_id', $wbsIds)
            ->where('status', '!=', 'Dibatalkan')
            ->sum(DB::raw('CASE WHEN po_value > 0 THEN po_value ELSE commitment_value END')) : 0.0;

        $realisasiNonPo = !empty($wbsIds) ? (float) BudgetNonPo::whereIn('budget_wbs_id', $wbsIds)->sum('amount') : 0.0;

        $total_assigned = (float) ($totalPo + $realisasiNonPo);
        $total_available = (float) ($total_budget - $total_assigned);

        // Passing variabel Monitoring Anggaran ke view
        $data['total_budget'] = $total_budget;
        $data['total_assigned'] = $total_assigned;
        $data['total_available'] = $total_available;

        // --- 1. Query Rekap untuk Issue & Follow Up ---
        $total_issues = (int) IssueFollowUp::count();
        $total_open_issues = (int) IssueFollowUp::where('status', 'Open')->count();
        $total_closed_issues = (int) IssueFollowUp::whereIn('status', ['Closed', 'Close'])->count();

        $pct_open_issues = $total_issues > 0 ? (int) round(($total_open_issues / $total_issues) * 100) : 0;
        $pct_closed_issues = $total_issues > 0 ? (int) round(($total_closed_issues / $total_issues) * 100) : 0;

        $data['total_issues'] = $total_issues;
        $data['total_open_issues'] = $total_open_issues;
        $data['total_closed_issues'] = $total_closed_issues;
        $data['pct_open_issues'] = $pct_open_issues;
        $data['pct_closed_issues'] = $pct_closed_issues;

        // --- 2. Query untuk Data Gas Monitoring Terbaru (Sinkron dengan modul Gas Monitoring) ---
        $gasSummary = GasMonitoring::getLatestDailySummary();
        $gasCards = $gasSummary['cards'];
        $latest_gas_log = $gasSummary['latest_record'];
        $gas_has_data = $gasSummary['has_data'];
        $gas_update_date = $gasSummary['update_date_display'];

        $gas_co = $gas_has_data ? $gasCards['co']['formatted'] : '-';
        $gas_o2 = $gas_has_data ? $gasCards['o2']['formatted'] : '-';
        $gas_h2s = $gas_has_data ? $gasCards['h2s']['formatted'] : '-';
        $gas_lel = $gas_has_data ? $gasCards['lel']['formatted'] : '-';
        $gas_pressure = $gas_has_data ? $gasCards['pressure']['formatted'] : '-';

        $gas_co_status = $gasCards['co']['status'];
        $gas_o2_status = $gasCards['o2']['status'];
        $gas_h2s_status = $gasCards['h2s']['status'];
        $gas_lel_status = $gasCards['lel']['status'];
        $gas_pressure_status = 'Sesuai';

        $data['latest_gas_log'] = $latest_gas_log;
        $data['gas_has_data'] = $gas_has_data;
        $data['gas_update_date'] = $gas_update_date;
        $data['gas_co'] = $gas_co;
        $data['gas_o2'] = $gas_o2;
        $data['gas_h2s'] = $gas_h2s;
        $data['gas_lel'] = $gas_lel;
        $data['gas_pressure'] = $gas_pressure;
        $data['gas_co_status'] = $gas_co_status;
        $data['gas_o2_status'] = $gas_o2_status;
        $data['gas_h2s_status'] = $gas_h2s_status;
        $data['gas_lel_status'] = $gas_lel_status;
        $data['gas_pressure_status'] = $gas_pressure_status;

        // 1. Query Kegiatan Terbaru (Events) - limit 5 urut dari tanggal pelaksanaan terbaru (eager load documents)
        $recent_events = Event::with('documents')->orderBy('event_date', 'desc')->take(5)->get();

        // 2. Query Pekerjaan Hari Ini (Daily Work) - menggunakan single source of truth & timezone Asia/Jakarta
        $today_wib = Carbon::now('Asia/Jakarta')->locale('id');
        $today_works = DailyWork::dailyWorkToday($today_wib->toDateString())
            ->orderedForDailyWork()
            ->take(5)
            ->get();

        $data['recent_events'] = $recent_events;
        $data['latest_events'] = $recent_events;
        $data['today_works'] = $today_works;
        $data['today_works_date_display'] = $today_wib->translatedFormat('d F Y');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('dashboard.index', $data);
    }

    /**
     * Unduh Laporan Ringkasan Eksekutif Dashboard dalam format PDF.
     */
    public function export(Request $request): Response
    {
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'unit' => $request->input('unit', 'all'),
            'category' => $request->input('category', 'all'),
            'year' => (int) $request->input('year', date('Y')),
        ];

        $data = $this->dashboardService->getDashboardData($filters);

        $pdf = Pdf::loadView('exports.dashboard-pdf', [
            'reportTitle' => 'LAPORAN EKSEKUTIF HSSE MANAJEMEN',
            'moduleName' => 'Dashboard Safe Guard Monitoring',
            'filterInfo' => [
                'Periode Laporan' => $data['filters']['date_range_display'] ?? 'Seluruh Riwayat',
                'Unit Filter' => $data['filters']['unit'] ?? 'Semua Unit',
                'Kategori Filter' => $data['filters']['category'] ?? 'Semua Kategori',
            ],
            'hsseMetrics' => $data['hsse_metrics'] ?? [],
            'coreCards' => $data['core_cards'] ?? [],
            'progressCards' => $data['progress_cards'] ?? [],
            'projectTimeline' => $data['project_timeline'] ?? [],
            'latestIssues' => $data['latest_issues'] ?? [],
            'userName' => auth()->user()?->name ?? 'Administrator',
            'generatedAt' => now()->translatedFormat('d F Y, H:i:s'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Laporan-Eksekutif-Dashboard-HSSE-' . now()->format('Ymd-His') . '.pdf');
    }
}
