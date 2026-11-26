<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GasDetector;
use App\Models\GasMonitoring;
use App\Models\WellPad;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GasDetectorController extends Controller
{
    public const WELL_PADS = [
        'Well Pad 7',
        'Well Pad 9',
        'Well Pad 28',
        'Well Pad 30',
        'Well Pad 31',
    ];

    public const NAB_CONFIG = [
        'co' => [
            'val' => 25,
            'unit' => 'ppm',
            'threshold_label' => 'NAB: ≤ 25 ppm',
            'desc' => 'Nilai Ambang Batas CO adalah 25 ppm',
        ],
        'o2' => [
            'val' => 19.5,
            'max' => 23.5,
            'unit' => '%',
            'threshold_label' => 'NAB: 19.5 – 23.5 %',
            'desc' => 'Kadar oksigen normal kerja aman adalah 19.5% hingga 23.5%',
        ],
        'h2s' => [
            'val' => 10,
            'unit' => 'ppm',
            'threshold_label' => 'NAB: ≤ 10 ppm',
            'desc' => 'Nilai Ambang Batas H2S adalah 10 ppm untuk paparan kerja aman',
        ],
        'lel' => [
            'val' => 5,
            'unit' => '%',
            'threshold_label' => 'NAB: ≤ 5 %',
            'desc' => 'Batas konsentrasi gas mudah terbakar maksimal 5 %',
        ],
        'pressure' => [
            'val' => null,
            'unit' => 'Psi',
            'threshold_label' => 'Tanpa Ambang Batas',
            'desc' => 'Pemantauan tekanan operasional (Psi)',
        ],
    ];

    public const MONTH_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * Tampilkan halaman utama Gas Monitoring & Inventaris Perangkat Gasdetektor.
     */
    public function index(Request $request): View|JsonResponse
    {
        $activeWellPads = WellPad::active()->get();
        if ($activeWellPads->isEmpty()) {
            $activeWellPads = WellPad::ordered()->get();
        }
        $activeWellPads = WellPad::sortNaturally($activeWellPads);

        $todayStr = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $todayDisplayLabel = Carbon::parse($todayStr)->locale('id')->isoFormat('D MMMM Y');

        // 1. 5 Kartu Indikator Paling Atas: Statis terhadap seluruh filter bawah.
        // Selalu mengambil data dari tanggal pengukuran terbaru yang tersedia di database.
        $latestSummary = GasMonitoring::getLatestDailySummary($todayStr);
        $summaryCards = $latestSummary['cards'];
        $lastInputTimeFormatted = $latestSummary['last_input_time_formatted'];
        $latestDateStr = $latestSummary['latest_date'] ?? $todayStr;
        $latestDateObj = Carbon::parse($latestDateStr)->locale('id');
        $headerDayName = $latestDateObj->isoFormat('dddd');
        $headerDateLabel = $latestDateObj->isoFormat('D MMMM Y');
        $isLatestAvailable = true;

        // 2. Active Section ('hari_ini' default | 'semua_riwayat')
        $activeSection = (string) $request->input('section', '');
        if ($activeSection === '') {
            $activeSection = $request->has('history_page') ? 'semua_riwayat' : 'hari_ini';
        }
        if (!in_array($activeSection, ['hari_ini', 'semua_riwayat'], true)) {
            $activeSection = 'hari_ini';
        }

        // 3. SECTION HARI INI (Grafik & Tabel khusus tanggal hari ini, tanpa filter periode/Well Pad)
        $searchToday = trim((string) $request->input('search_today', $request->input('search', '')));
        $todayAllRecords = GasMonitoring::query()
            ->with('wellPad')
            ->whereDate('measurement_date', $todayStr)
            ->orderBy('id', 'desc')
            ->get();

        $todayChartAndStats = $this->buildBarChartAndStatistics($todayAllRecords, $activeWellPads, 'all');
        $chartData = $todayChartAndStats['chartData'];
        $statisticsData = $todayChartAndStats['statisticsData'];

        $todayTableQuery = GasMonitoring::query()
            ->with('wellPad')
            ->whereDate('measurement_date', $todayStr);

        if ($searchToday !== '') {
            $todayTableQuery->where(function (Builder $q) use ($searchToday) {
                $q->where('well_pad', 'like', "%{$searchToday}%")
                  ->orWhere('notes', 'like', "%{$searchToday}%")
                  ->orWhere('recorded_by', 'like', "%{$searchToday}%")
                  ->orWhere('measurement_date', 'like', "%{$searchToday}%");
            });
        }

        $todayRecords = $todayTableQuery
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'today_page')
            ->withQueryString();

        // 4. SECTION SEMUA RIWAYAT (Tabel & Grafik menggunakan filter periode + Well Pad yang sama)
        $historyFilter = $this->resolveHistoryChartFilter($request, $activeWellPads, $todayStr);
        $historyChartResult = $this->buildHistoryChartAndStatistics($historyFilter);
        $historyChartData = $historyChartResult['chartData'];
        $historyStatisticsData = $historyChartResult['statisticsData'];
        $historyPeriodLabel = $historyFilter['formatted_label'];

        $searchHistory = trim((string) $request->input('search_history', ''));
        $historyTableRecords = $this->buildHistoryTableQuery($historyFilter, $searchHistory)
            ->paginate(15, ['*'], 'history_page')
            ->appends([
                'section' => 'semua_riwayat',
                'history_period_type' => $historyFilter['period_type'],
                'history_month' => $historyFilter['month'],
                'history_year' => $historyFilter['year'],
                'history_start_date' => $historyFilter['start_date'],
                'history_end_date' => $historyFilter['end_date'],
                'history_well_pad' => $historyFilter['well_pad'],
                'search_history' => $searchHistory !== '' ? $searchHistory : null,
            ]);

        // Daftar tahun tersedia untuk dropdown tahun
        $dbYears = GasMonitoring::selectRaw('DISTINCT YEAR(measurement_date) as yr')
            ->whereNotNull('measurement_date')
            ->pluck('yr')
            ->map(fn ($y) => (int) $y)
            ->filter()
            ->all();
        $currentYearInt = (int) Carbon::now('Asia/Jakarta')->year;
        $availableYears = array_values(array_unique(array_merge([$currentYearInt], $dbYears)));
        rsort($availableYears);

        // Variabel kompatibilitas untuk komponen/test lama
        $periodType = $historyFilter['period_type'];
        $startDate = $historyFilter['start_date'];
        $endDate = $historyFilter['end_date'];
        $formattedPeriodLabel = $todayDisplayLabel;
        $wellPadFilter = $historyFilter['well_pad'];
        $selectedWellPads = $wellPadFilter !== '' ? [$wellPadFilter] : [];
        $parameterFilter = (string) $request->input('indicator', 'all');
        $searchQuery = $searchToday;
        $tableRecords = $todayRecords;

        // Legacy variables for backward compatibility with tests/components
        $month = (int) $historyFilter['month'];
        $year = (int) $historyFilter['year'];
        $currentDate = Carbon::create($year, $month, 1);
        $prevDate = (clone $currentDate)->subMonth();
        $nextDate = (clone $currentDate)->addMonth();
        $monthNames = self::MONTH_NAMES;
        $currentMonthName = ($monthNames[$month] ?? 'Bulan') . ' ' . $year;
        $nabConfig = self::NAB_CONFIG;
        $records = $todayRecords;
        $highestCo = $todayAllRecords->sortByDesc('co_level')->first();
        $highestH2s = $todayAllRecords->sortByDesc('h2s_level')->first();
        $highestPressure = $todayAllRecords->sortByDesc('pressure')->first();
        $totalDataInMonth = $todayAllRecords->count();
        $rekapPerWellPad = $this->buildRekapPerWellPad($todayAllRecords, $activeWellPads);

        // ---------------------------------------------------------
        // B. BAGIAN 2: INVENTARIS PERANGKAT GASDETEKTOR
        // ---------------------------------------------------------
        $deviceQuery = GasDetector::query();

        if ($request->filled('unit')) {
            $deviceQuery->where('unit', $request->input('unit'));
        }
        if ($request->filled('status')) {
            $deviceQuery->where('status', $request->input('status'));
        }
        if ($request->filled('gas_type')) {
            $deviceQuery->where('gas_type', 'like', '%' . $request->input('gas_type') . '%');
        }
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $deviceQuery->where(function ($q) use ($term) {
                $q->where('device_code', 'like', "%{$term}%")
                  ->orWhere('model_name', 'like', "%{$term}%")
                  ->orWhere('serial_number', 'like', "%{$term}%")
                  ->orWhere('gas_type', 'like', "%{$term}%")
                  ->orWhere('unit', 'like', "%{$term}%")
                  ->orWhere('location', 'like', "%{$term}%")
                  ->orWhere('user_in_charge', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        $sort = $request->input('sort', 'code');
        if ($sort === 'model') {
            $deviceQuery->orderBy('model_name', 'asc');
        } elseif ($sort === 'calibration-asc') {
            $deviceQuery->orderByRaw('CASE WHEN next_calibration_date IS NULL THEN 1 ELSE 0 END, next_calibration_date ASC');
        } elseif ($sort === 'calibration-desc') {
            $deviceQuery->orderByRaw('CASE WHEN next_calibration_date IS NULL THEN 1 ELSE 0 END, next_calibration_date DESC');
        } elseif ($sort === 'date-desc') {
            $deviceQuery->orderBy('created_at', 'desc');
        } else {
            $deviceQuery->orderBy('device_code', 'asc');
        }

        $devices = $deviceQuery->get();

        $totalDevices = $devices->count();
        $availableDevices = $devices->where('status', 'Tersedia')->count();
        $inUseDevices = $devices->where('status', 'Sedang Digunakan')->count();
        $inMaintenanceDevices = $devices->where('status', 'Dalam Perawatan')->count();
        $dueCalibrationDevices = $devices->where('status', 'Perlu Kalibrasi')->count();
        $maintenanceOrCalibration = $inMaintenanceDevices + $dueCalibrationDevices;

        $stats = [
            'total' => $totalDevices,
            'available' => $availableDevices,
            'in_use' => $inUseDevices,
            'in_maintenance' => $inMaintenanceDevices,
            'due_calibration' => $dueCalibrationDevices,
            'maintenance_or_calibration' => $maintenanceOrCalibration,
        ];

        $unitCounts = $devices->groupBy('unit')->map->count();
        $unitChart = [
            'labels' => $unitCounts->keys()->values()->all(),
            'data' => $unitCounts->values()->all(),
        ];

        $statusChart = [
            'labels' => ['Tersedia', 'Sedang Digunakan', 'Dalam Perawatan', 'Perlu Kalibrasi'],
            'data' => [$availableDevices, $inUseDevices, $inMaintenanceDevices, $dueCalibrationDevices],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
        ];

        $gasTypeCounts = $devices->groupBy(function ($item) {
            $val = trim((string) $item->gas_type);
            return empty($val) ? 'Multi-Gas' : $val;
        })->map->count();
        $gasTypeChart = [
            'labels' => $gasTypeCounts->keys()->values()->all(),
            'data' => $gasTypeCounts->values()->all(),
        ];

        $defaultUnits = ['Dieng Unit 1', 'Dieng Unit 2', 'PMU', 'Power Plant', 'Wellpad'];
        $dbUnits = GasDetector::select('unit')->distinct()->pluck('unit')->filter()->values()->all();
        $units = array_values(array_unique(array_merge($defaultUnits, $dbUnits)));

        $defaultGasTypes = [
            'Multi-Gas (H2S, CO, O2, LEL)',
            'H2S Single Gas',
            'CO Single Gas',
            'O2 Single Gas',
            'Combustible / LEL',
        ];
        $dbGasTypes = GasDetector::select('gas_type')->distinct()->pluck('gas_type')->filter()->values()->all();
        $gasTypes = array_values(array_unique(array_merge($defaultGasTypes, $dbGasTypes)));

        $statuses = ['Tersedia', 'Sedang Digunakan', 'Dalam Perawatan', 'Perlu Kalibrasi'];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'devices' => $devices,
                'stats' => $stats,
                'unitChart' => $unitChart,
                'statusChart' => $statusChart,
                'gasTypeChart' => $gasTypeChart,
                'records' => $tableRecords,
                'tableRecords' => $tableRecords,
                'summaryCards' => $summaryCards,
                'chartData' => $chartData,
                'statisticsData' => $statisticsData,
                'rekapPerWellPad' => $rekapPerWellPad,
            ]);
        }

        return view('gas-detector.index', compact(
            // New Gas Monitoring Reference Variables
            'formattedPeriodLabel',
            'todayDisplayLabel',
            'headerDayName',
            'headerDateLabel',
            'isLatestAvailable',
            'lastInputTimeFormatted',
            'activeSection',
            'periodType',
            'startDate',
            'endDate',
            'activeWellPads',
            'wellPadFilter',
            'selectedWellPads',
            'parameterFilter',
            'summaryCards',
            'chartData',
            'statisticsData',
            'tableRecords',
            'todayRecords',
            'historyTableRecords',
            'historyChartData',
            'historyStatisticsData',
            'historyPeriodLabel',
            'historyFilter',
            'availableYears',
            'searchQuery',
            'searchToday',
            'searchHistory',

            // Legacy / Backward-Compatible Variables
            'currentMonthName',
            'prevDate',
            'nextDate',
            'month',
            'year',
            'monthNames',
            'nabConfig',
            'highestCo',
            'highestH2s',
            'highestPressure',
            'totalDataInMonth',
            'records',
            'rekapPerWellPad',

            // Device Inventory Variables
            'devices',
            'stats',
            'unitChart',
            'statusChart',
            'gasTypeChart',
            'units',
            'gasTypes',
            'statuses'
        ));
    }

    /**
     * Endpoint JSON untuk grafik Semua Riwayat (Bulanan / Tahunan / Rentang Tanggal + 1 Well Pad).
     */
    public function historyChartData(Request $request): JsonResponse
    {
        $activeWellPads = WellPad::active()->get();
        if ($activeWellPads->isEmpty()) {
            $activeWellPads = WellPad::ordered()->get();
        }
        $activeWellPads = WellPad::sortNaturally($activeWellPads);

        $todayStr = Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $periodType = (string) $request->input('history_period_type', $request->input('period_type', 'monthly'));
        if ($periodType === 'custom') {
            $s = $request->input('history_start_date', $request->input('start_date'));
            $e = $request->input('history_end_date', $request->input('end_date'));
            if (empty($s) || empty($e)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal Mulai dan Tanggal Akhir wajib diisi.',
                ], 422);
            }
            if (Carbon::parse($e)->lt(Carbon::parse($s))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal Mulai tidak boleh lebih besar daripada Tanggal Akhir.',
                ], 422);
            }
        }

        $historyFilter = $this->resolveHistoryChartFilter($request, $activeWellPads, $todayStr);
        $result = $this->buildHistoryChartAndStatistics($historyFilter);

        $searchHistory = trim((string) $request->input('search_history', ''));
        $historyTableRecords = $this->buildHistoryTableQuery($historyFilter, $searchHistory)
            ->paginate(15, ['*'], 'history_page')
            ->withPath(route('gas-detector.index'))
            ->appends([
                'section' => 'semua_riwayat',
                'history_period_type' => $historyFilter['period_type'],
                'history_month' => $historyFilter['month'],
                'history_year' => $historyFilter['year'],
                'history_start_date' => $historyFilter['start_date'],
                'history_end_date' => $historyFilter['end_date'],
                'history_well_pad' => $historyFilter['well_pad'],
                'search_history' => $searchHistory !== '' ? $searchHistory : null,
            ]);

        $tableHtml = view('gas-detector.partials.history-table-content', [
            'historyTableRecords' => $historyTableRecords,
        ])->render();

        return response()->json([
            'success' => true,
            'filter' => $historyFilter,
            'periodLabel' => $historyFilter['formatted_label'],
            'wellPadName' => $historyFilter['well_pad'],
            'chartData' => $result['chartData'],
            'statisticsData' => $result['statisticsData'],
            'tableHtml' => $tableHtml,
            'tableTotal' => $historyTableRecords->total(),
        ]);
    }

    /**
     * Query builder untuk tabel Semua Riwayat agar 100% sinkron dengan filter grafik (Periode & Well Pad).
     */
    protected function buildHistoryTableQuery(array $historyFilter, string $searchHistory = ''): Builder
    {
        $query = GasMonitoring::query()
            ->with('wellPad')
            ->where('well_pad', $historyFilter['well_pad'])
            ->whereDate('measurement_date', '>=', $historyFilter['start_date'])
            ->whereDate('measurement_date', '<=', $historyFilter['end_date']);

        if ($searchHistory !== '') {
            $query->where(function (Builder $q) use ($searchHistory) {
                $q->where('well_pad', 'like', "%{$searchHistory}%")
                    ->orWhere('notes', 'like', "%{$searchHistory}%")
                    ->orWhere('recorded_by', 'like', "%{$searchHistory}%")
                    ->orWhere('measurement_date', 'like', "%{$searchHistory}%");
            });
        }

        return $query->orderBy('measurement_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Resolve filter grafik untuk section Semua Riwayat (Bulanan / Tahunan / Rentang Tanggal + 1 Well Pad).
     */
    protected function resolveHistoryChartFilter(Request $request, Collection $activeWellPads, string $todayStr): array
    {
        $todayObj = Carbon::parse($todayStr)->locale('id');
        $defaultPad = $activeWellPads->first()?->name ?? (self::WELL_PADS[0] ?? 'Well Pad 7');

        $wellPad = trim((string) $request->input('history_well_pad', $request->input('well_pad', '')));
        if ($wellPad === '' || $wellPad === 'all') {
            $wellPad = $defaultPad;
        }

        $periodType = (string) $request->input('history_period_type', $request->input('period_type', 'monthly'));
        if (!in_array($periodType, ['monthly', 'yearly', 'custom'], true)) {
            $periodType = 'monthly';
        }

        $month = (int) $request->input('history_month', $request->input('month', $todayObj->month));
        if ($month < 1 || $month > 12) {
            $month = $todayObj->month;
        }

        $rawYear = $periodType === 'yearly'
            ? $request->input('history_year', $request->input('history_year_only', $request->input('year', $todayObj->year)))
            : $request->input('history_year', $request->input('year', $todayObj->year));
        $year = (int) $rawYear;
        if ($year < 2000 || $year > 2099) {
            $year = $todayObj->year;
        }

        if ($periodType === 'yearly') {
            $start = Carbon::create($year, 1, 1)->startOfYear();
            $end = (clone $start)->endOfYear();
            $label = 'Tahun ' . $year;
        } elseif ($periodType === 'custom') {
            $rawStart = $request->input('history_start_date', $request->input('start_date', Carbon::parse($todayStr)->startOfMonth()->format('Y-m-d')));
            $rawEnd = $request->input('history_end_date', $request->input('end_date', $todayStr));
            $start = Carbon::parse($rawStart)->locale('id');
            $end = Carbon::parse($rawEnd)->locale('id');
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
            $label = $start->isoFormat('D MMM Y') . ' – ' . $end->isoFormat('D MMM Y');
        } else {
            $periodType = 'monthly';
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $label = (self::MONTH_NAMES[$month] ?? 'Bulan') . ' ' . $year;
        }

        return [
            'period_type' => $periodType,
            'month' => $month,
            'year' => $year,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'well_pad' => $wellPad,
            'formatted_label' => $label,
        ];
    }

    /**
     * Bangun data grafik & statistik khusus section Semua Riwayat (1 Well Pad dalam satu waktu).
     * - Bulanan: tanggal 1..akhir bulan (worst measurement jika ada >1 pengukuran pada tanggal yang sama, null jika kosong)
     * - Tahunan: Jan..Des (worst measurement per bulan + tanggal terjadinya pada tooltip, null jika kosong)
     * - Rentang Tanggal: per tanggal dalam rentang (worst measurement per tanggal, null jika kosong)
     */
    protected function buildHistoryChartAndStatistics(array $filter): array
    {
        $periodType = $filter['period_type'];
        $wellPad = $filter['well_pad'];
        $startDate = $filter['start_date'];
        $endDate = $filter['end_date'];

        $records = GasMonitoring::query()
            ->where('well_pad', $wellPad)
            ->whereBetween('measurement_date', [$startDate, $endDate])
            ->orderBy('measurement_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $paramConfigs = [
            'co' => ['col' => 'co_level', 'unit' => 'ppm', 'max' => 25.0, 'warn' => 20.0, 'is_min' => false],
            'o2' => ['col' => 'o2_level', 'unit' => '%', 'min' => 19.5, 'max' => 23.5, 'is_min' => true],
            'h2s' => ['col' => 'h2s_level', 'unit' => 'ppm', 'max' => 10.0, 'warn' => 8.0, 'is_min' => false],
            'lel' => ['col' => 'lel_level', 'unit' => '%', 'max' => 5.0, 'warn' => 4.0, 'is_min' => false],
            'pressure' => ['col' => 'pressure', 'unit' => 'Psi', 'max' => null, 'warn' => null, 'is_min' => false],
        ];

        $labels = [];
        $buckets = [];

        if ($periodType === 'yearly') {
            $monthShortNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
            ];
            $grouped = $records->groupBy(fn ($r) => (int) Carbon::parse($r->measurement_date)->month);
            for ($m = 1; $m <= 12; $m++) {
                $labels[] = $monthShortNames[$m];
                $buckets[] = [
                    'records' => $grouped->get($m, collect()),
                    'default_date' => null,
                ];
            }
        } elseif ($periodType === 'monthly') {
            $grouped = $records->groupBy(fn ($r) => Carbon::parse($r->measurement_date)->format('Y-m-d'));
            $daysInMonth = Carbon::create($filter['year'], $filter['month'], 1)->daysInMonth;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateKey = sprintf('%04d-%02d-%02d', $filter['year'], $filter['month'], $d);
                $labels[] = (string) $d;
                $buckets[] = [
                    'records' => $grouped->get($dateKey, collect()),
                    'default_date' => Carbon::parse($dateKey)->locale('id')->isoFormat('D MMM Y'),
                ];
            }
        } else {
            $grouped = $records->groupBy(fn ($r) => Carbon::parse($r->measurement_date)->format('Y-m-d'));
            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $dt) {
                $dateKey = $dt->format('Y-m-d');
                $labels[] = $dt->locale('id')->isoFormat('D MMM');
                $buckets[] = [
                    'records' => $grouped->get($dateKey, collect()),
                    'default_date' => $dt->locale('id')->isoFormat('D MMM Y'),
                ];
            }
        }

        $chartData = [
            'labels' => $labels,
            'well_pad' => $wellPad,
            'period_type' => $periodType,
        ];
        $statisticsData = [];

        foreach ($paramConfigs as $key => $cfg) {
            $col = $cfg['col'];
            $isMin = $cfg['is_min'];
            $dataArr = [];
            $colorsArr = [];
            $datesArr = [];
            $validPoints = [];

            foreach ($buckets as $idx => $bucket) {
                $worst = $this->pickWorstRecord($bucket['records'], $col, $isMin);
                if ($worst === null) {
                    $dataArr[] = null;
                    $colorsArr[] = '#cbd5e1';
                    $datesArr[] = $bucket['default_date'];
                    continue;
                }

                $val = round((float) $worst->{$col}, 1);
                $dateDisplay = $worst->measurement_date
                    ? Carbon::parse($worst->measurement_date)->locale('id')->isoFormat('D MMM Y')
                    : $bucket['default_date'];

                $dataArr[] = $val;
                $colorsArr[] = $this->evaluateParamColor($key, $val);
                $datesArr[] = $dateDisplay;
                $validPoints[] = [
                    'val' => $val,
                    'label' => $labels[$idx],
                    'date' => $dateDisplay,
                ];
            }

            $chartData[$key] = [
                'data' => $dataArr,
                'colors' => $colorsArr,
                'dates' => $datesArr,
                'unit' => $cfg['unit'],
            ];

            if (empty($validPoints)) {
                $statisticsData[$key] = [
                    'avg' => '-',
                    'max_val' => '-',
                    'max_pad' => '-',
                    'min_val' => '-',
                    'min_pad' => '-',
                    'count' => 0,
                ];
                continue;
            }

            $vals = array_column($validPoints, 'val');
            $avgNum = round(array_sum($vals) / count($vals), 1);
            $avgFormatted = rtrim(rtrim(number_format($avgNum, 1, '.', ''), '0'), '.');

            $maxPoint = collect($validPoints)->sortByDesc('val')->first();
            $minPoint = collect($validPoints)->sortBy('val')->first();

            $statisticsData[$key] = [
                'avg' => $avgFormatted,
                'max_val' => rtrim(rtrim(number_format((float) $maxPoint['val'], 1, '.', ''), '0'), '.'),
                'max_pad' => $wellPad . (!empty($maxPoint['date']) ? ' · ' . $maxPoint['date'] : ''),
                'min_val' => rtrim(rtrim(number_format((float) $minPoint['val'], 1, '.', ''), '0'), '.'),
                'min_pad' => $wellPad . (!empty($minPoint['date']) ? ' · ' . $minPoint['date'] : ''),
                'count' => count($validPoints),
            ];
        }

        return [
            'chartData' => $chartData,
            'statisticsData' => $statisticsData,
        ];
    }

    /**
     * Ambil rekaman dengan nilai terburuk (worst measurement) untuk suatu parameter.
     */
    protected function pickWorstRecord(Collection $subset, string $col, bool $isMin): ?GasMonitoring
    {
        $valid = $subset->filter(fn ($r) => $r->{$col} !== null);
        if ($valid->isEmpty()) {
            return null;
        }

        $sorted = $isMin ? $valid->sortBy($col) : $valid->sortByDesc($col);
        return $sorted->first();
    }

    /**
     * Tentukan warna titik/batang berdasarkan NAB parameter.
     */
    protected function evaluateParamColor(string $key, ?float $val): string
    {
        if ($val === null) {
            return '#cbd5e1';
        }
        if ($key === 'pressure') {
            return '#10b981';
        }
        if ($key === 'o2') {
            if ($val < GasMonitoring::NAB_O2_MIN || $val > GasMonitoring::NAB_O2_MAX) {
                return '#ef4444';
            }
            if ($val < 20.0 || $val > 23.0) {
                return '#f59e0b';
            }
            return '#10b981';
        }
        $thresholds = [
            'co' => ['max' => GasMonitoring::NAB_CO, 'warn' => 20.0],
            'h2s' => ['max' => GasMonitoring::NAB_H2S, 'warn' => 8.0],
            'lel' => ['max' => GasMonitoring::NAB_LEL, 'warn' => 4.0],
        ];
        $cfg = $thresholds[$key] ?? null;
        if (!$cfg) {
            return '#10b981';
        }
        if ($val > $cfg['max']) {
            return '#ef4444';
        }
        if ($val >= $cfg['warn']) {
            return '#f59e0b';
        }
        return '#10b981';
    }

    /**
     * Resolve 6 period modes: all, daily, weekly, monthly, yearly, custom.
     * Apabila hari ini sudah diisi, tanggalan yang muncul adalah hari ini;
     * apabila belum mengisi hari ini, maka tanggal hari sebelumnya yang muncul.
     */
    protected function resolvePeriodFilter(Request $request): array
    {
        $todayStr = now()->setTimezone('Asia/Jakarta')->format('Y-m-d');
        $latestDateStr = GasMonitoring::resolveLatestMeasurementDate($todayStr) ?? $todayStr;

        $latestDateObj = Carbon::parse($latestDateStr)->locale('id');

        $hasExplicitPeriod = $request->filled('period_type')
            || $request->filled('filter_date')
            || $request->filled('filter_week_date')
            || $request->filled('filter_month')
            || $request->filled('filter_year')
            || $request->filled('start_date')
            || $request->filled('end_date')
            || $request->filled('month')
            || $request->filled('year');

        // Legacy month & year query support
        if (!$request->filled('period_type') && ($request->filled('month') || $request->filled('year'))) {
            $m = (int) $request->input('month', date('n'));
            $y = (int) $request->input('year', date('Y'));
            $start = Carbon::create($y, $m, 1)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            return [
                'period_type' => 'monthly',
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'formatted_label' => (self::MONTH_NAMES[$m] ?? 'Bulan') . ' ' . $y,
                'header_day_name' => $latestDateObj->isoFormat('dddd'),
                'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                'is_latest_available' => false,
            ];
        }

        // Direct start_date & end_date redirect (e.g. after batch create)
        if (!$request->filled('period_type') && $request->filled('start_date') && $request->filled('end_date')) {
            $s = Carbon::parse($request->input('start_date'))->format('Y-m-d');
            $e = Carbon::parse($request->input('end_date'))->format('Y-m-d');
            if ($s === $e) {
                $dObj = Carbon::parse($s)->locale('id');
                return [
                    'period_type' => 'daily',
                    'start_date' => $s,
                    'end_date' => $e,
                    'formatted_label' => $dObj->isoFormat('D MMMM Y'),
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => ($s === $latestDateStr),
                ];
            }
            $rangeLabel = Carbon::parse($s)->locale('id')->isoFormat('D MMM Y') . ' – ' . Carbon::parse($e)->locale('id')->isoFormat('D MMM Y');
            return [
                'period_type' => 'custom',
                'start_date' => $s,
                'end_date' => $e,
                'formatted_label' => $rangeLabel,
                'header_day_name' => $latestDateObj->isoFormat('dddd'),
                'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                'is_latest_available' => false,
            ];
        }

        $periodType = (string) $request->input('period_type', 'daily');

        if (!$hasExplicitPeriod) {
            // Default: jika hari ini sudah diisi maka tampil hari ini, jika belum maka tampil hari sebelumnya
            return [
                'period_type' => 'daily',
                'start_date' => $latestDateStr,
                'end_date' => $latestDateStr,
                'formatted_label' => $latestDateObj->isoFormat('D MMMM Y'),
                'header_day_name' => $latestDateObj->isoFormat('dddd'),
                'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                'is_latest_available' => true,
            ];
        }

        switch ($periodType) {
            case 'all':
                $minRaw = GasMonitoring::min('measurement_date');
                $s = $minRaw ? Carbon::parse($minRaw)->format('Y-m-d') : $latestDateStr;
                $e = $latestDateStr;
                return [
                    'period_type' => 'all',
                    'start_date' => $s,
                    'end_date' => $e,
                    'formatted_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => false,
                ];

            case 'weekly':
                $refDate = $request->input('filter_week_date', $latestDateStr);
                $dObj = Carbon::parse($refDate)->locale('id');
                $start = (clone $dObj)->startOfWeek(Carbon::MONDAY);
                $end = (clone $dObj)->endOfWeek(Carbon::SUNDAY);
                $lbl = $start->isoFormat('D MMM Y') . ' – ' . $end->isoFormat('D MMM Y');
                return [
                    'period_type' => 'weekly',
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                    'formatted_label' => $lbl,
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => false,
                ];

            case 'monthly':
                $monthVal = (string) $request->input('filter_month', substr($latestDateStr, 0, 7));
                if (preg_match('/^(\d{4})-(\d{2})$/', $monthVal, $matches)) {
                    $y = (int) $matches[1];
                    $m = (int) $matches[2];
                } else {
                    $y = (int) date('Y');
                    $m = (int) date('n');
                }
                $start = Carbon::create($y, $m, 1)->startOfMonth();
                $end = (clone $start)->endOfMonth();
                $lbl = (self::MONTH_NAMES[$m] ?? 'Bulan') . ' ' . $y;
                return [
                    'period_type' => 'monthly',
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                    'formatted_label' => $lbl,
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => false,
                ];

            case 'yearly':
                $y = (int) $request->input('filter_year', substr($latestDateStr, 0, 4));
                $start = Carbon::create($y, 1, 1)->startOfYear();
                $end = (clone $start)->endOfYear();
                $lbl = 'Tahun ' . $y;
                return [
                    'period_type' => 'yearly',
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                    'formatted_label' => $lbl,
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => false,
                ];

            case 'custom':
                $s = $request->input('start_date', $latestDateStr);
                $e = $request->input('end_date', $latestDateStr);
                $sObj = Carbon::parse($s)->locale('id');
                $eObj = Carbon::parse($e)->locale('id');
                if ($eObj->lt($sObj)) {
                    [$sObj, $eObj] = [$eObj, $sObj];
                }
                $lbl = $sObj->isoFormat('D MMM Y') . ' – ' . $eObj->isoFormat('D MMM Y');
                return [
                    'period_type' => 'custom',
                    'start_date' => $sObj->format('Y-m-d'),
                    'end_date' => $eObj->format('Y-m-d'),
                    'formatted_label' => $lbl,
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => false,
                ];

            case 'daily':
            default:
                $d = $request->input('filter_date', $request->input('start_date', $latestDateStr));
                $dObj = Carbon::parse($d)->locale('id');
                $dStr = $dObj->format('Y-m-d');
                return [
                    'period_type' => 'daily',
                    'start_date' => $dStr,
                    'end_date' => $dStr,
                    'formatted_label' => $dObj->isoFormat('D MMMM Y'),
                    'header_day_name' => $latestDateObj->isoFormat('dddd'),
                    'header_date_label' => $latestDateObj->isoFormat('D MMMM Y'),
                    'is_latest_available' => ($dStr === $latestDateStr),
                ];
        }
    }

    protected function applyPeriodToQuery(Builder $query, string $periodType, string $startDate, string $endDate): void
    {
        if ($periodType === 'all') {
            return;
        }
        if ($periodType === 'daily' || $startDate === $endDate) {
            $query->whereDate('measurement_date', $startDate);
            return;
        }
        $query->whereBetween('measurement_date', [$startDate, $endDate]);
    }

    /**
     * Build 5 Parameter Summary Cards: CO, O2, H2S, LEL, Pressure.
     */
    protected function buildSummaryCards(Collection $records): array
    {
        return GasMonitoring::buildSummaryCardsFromCollection($records);
    }

    /**
     * Build Bar Chart Data & Statistics Panel Data for 5 Parameters (CO, O2, H2S, LEL, Pressure).
     */
    protected function buildBarChartAndStatistics(Collection $records, Collection $activeWellPads, string|array $wellPadFilter): array
    {
        $wellPadNames = $activeWellPads->pluck('name')->all();
        // Also include any well pad present in filtered records
        $recordPadNames = $records->pluck('well_pad')->filter()->unique()->values()->all();
        $allPads = array_values(array_unique(array_merge($wellPadNames, $recordPadNames)));

        $filterPadsList = is_array($wellPadFilter)
            ? array_values(array_filter($wellPadFilter, fn ($v) => !empty($v) && $v !== 'all'))
            : (($wellPadFilter !== 'all' && $wellPadFilter !== '') ? [$wellPadFilter] : []);

        if (!empty($filterPadsList)) {
            $allPads = array_values(array_filter($allPads, fn ($name) => in_array($name, $filterPadsList, true)));
        }

        $labels = [];
        foreach ($allPads as $pName) {
            if ($records->where('well_pad', $pName)->isNotEmpty() || in_array($pName, $filterPadsList, true) || $records->isNotEmpty()) {
                $labels[] = $pName;
            }
        }

        $paramConfigs = [
            'co' => ['col' => 'co_level', 'unit' => 'ppm', 'max' => 25.0, 'warn' => 20.0],
            'o2' => ['col' => 'o2_level', 'unit' => '%', 'min' => 19.5, 'max' => 23.5],
            'h2s' => ['col' => 'h2s_level', 'unit' => 'ppm', 'max' => 10.0, 'warn' => 8.0],
            'lel' => ['col' => 'lel_level', 'unit' => '%', 'max' => 5.0, 'warn' => 4.0],
            'pressure' => ['col' => 'pressure', 'unit' => 'Psi', 'max' => null, 'warn' => null],
        ];

        $chartData = [
            'labels' => $labels,
        ];
        $statisticsData = [];

        foreach ($paramConfigs as $key => $cfg) {
            $col = $cfg['col'];
            $dataArr = [];
            $colorsArr = [];

            foreach ($labels as $padName) {
                $padRecords = $records->where('well_pad', $padName)->filter(fn ($r) => $r->{$col} !== null);
                if ($padRecords->isEmpty()) {
                    $dataArr[] = 0;
                    $colorsArr[] = '#cbd5e1';
                    continue;
                }

                $avg = round((float) $padRecords->avg($col), 1);
                $dataArr[] = $avg;

                if ($key === 'pressure') {
                    $colorsArr[] = '#10b981'; // Tidak ada threshold
                } elseif ($key === 'o2') {
                    if ($avg < $cfg['min'] || $avg > $cfg['max']) {
                        $colorsArr[] = '#ef4444'; // Tidak Sesuai
                    } elseif ($avg < 20.0 || $avg > 23.0) {
                        $colorsArr[] = '#f59e0b'; // Mendekati Batas
                    } else {
                        $colorsArr[] = '#10b981'; // Sesuai
                    }
                } else {
                    if ($avg > $cfg['max']) {
                        $colorsArr[] = '#ef4444'; // Tidak Sesuai
                    } elseif ($avg >= $cfg['warn']) {
                        $colorsArr[] = '#f59e0b'; // Mendekati Batas
                    } else {
                        $colorsArr[] = '#10b981'; // Sesuai
                    }
                }
            }

            $chartData[$key] = [
                'data' => $dataArr,
                'colors' => $colorsArr,
                'unit' => $cfg['unit'],
            ];

            // Statistics calculation across valid records
            $validRecords = $records->filter(fn ($r) => $r->{$col} !== null);
            if ($validRecords->isEmpty()) {
                $statisticsData[$key] = [
                    'avg' => '-',
                    'max_val' => '-',
                    'max_pad' => '-',
                    'min_val' => '-',
                    'min_pad' => '-',
                    'count' => 0,
                    'overall_status' => 'Belum Ada Data',
                    'detail_note' => 'Belum ada data pengukuran pada periode ini.',
                ];
                continue;
            }

            $overallAvg = rtrim(rtrim(number_format((float) $validRecords->avg($col), 1, '.', ''), '0'), '.');
            $maxRecord = $validRecords->sortByDesc($col)->first();
            $minRecord = $validRecords->sortBy($col)->first();
            $uniquePadsCount = $validRecords->pluck('well_pad')->unique()->count();

            $exceededRecords = $validRecords->filter(function ($r) use ($key, $col, $cfg) {
                if ($key === 'pressure') {
                    return false;
                }
                $val = (float) $r->{$col};
                if ($key === 'o2') {
                    return $val < $cfg['min'] || $val > $cfg['max'];
                }
                return $val > $cfg['max'];
            });

            $isSesuai = $exceededRecords->isEmpty();
            $detailNote = $isSesuai
                ? "Seluruh {$uniquePadsCount} Well Pad berada dalam batas aman NAB."
                : $exceededRecords->pluck('well_pad')->unique()->implode(', ') . ' melebihi ambang batas NAB.';

            $statisticsData[$key] = [
                'avg' => $overallAvg,
                'max_val' => rtrim(rtrim(number_format((float) $maxRecord->{$col}, 1, '.', ''), '0'), '.'),
                'max_pad' => $maxRecord->well_pad ?? '-',
                'min_val' => rtrim(rtrim(number_format((float) $minRecord->{$col}, 1, '.', ''), '0'), '.'),
                'min_pad' => $minRecord->well_pad ?? '-',
                'count' => $uniquePadsCount,
                'overall_status' => $isSesuai ? 'Sesuai' : 'Tidak Sesuai',
                'detail_note' => $detailNote,
            ];
        }

        return [
            'chartData' => $chartData,
            'statisticsData' => $statisticsData,
        ];
    }

    /**
     * Bangun ringkasan rekapitulasi per Well Pad.
     */
    protected function buildRekapPerWellPad(Collection $allData, Collection $activeWellPads): array
    {
        $rekap = [];
        $padNames = $activeWellPads->pluck('name')->all();
        if (empty($padNames)) {
            $padNames = self::WELL_PADS;
        }

        foreach ($padNames as $wp) {
            $subset = $allData->where('well_pad', $wp);
            $count = $subset->count();

            if ($count > 0) {
                $avgCo = round((float) $subset->avg('co_level'), 1);
                $maxCo = round((float) $subset->max('co_level'), 1);
                $avgO2 = round((float) $subset->avg('o2_level'), 1);
                $avgH2s = round((float) $subset->avg('h2s_level'), 1);
                $maxH2s = round((float) $subset->max('h2s_level'), 1);
                $avgLel = round((float) $subset->avg('lel_level'), 1);
                $maxLel = round((float) $subset->max('lel_level'), 1);
                $avgPress = round((float) $subset->avg('pressure'), 1);
                $maxPress = round((float) $subset->max('pressure'), 1);

                $exceeded = $subset->contains(fn ($r) => $r->overall_status === 'Tidak Sesuai');

                $status = $exceeded ? 'Tidak Sesuai' : 'Sesuai';
                $statusClass = $exceeded ? 'danger' : 'success';
            } else {
                $avgCo = '-';
                $maxCo = '-';
                $avgO2 = '-';
                $avgH2s = '-';
                $maxH2s = '-';
                $avgLel = '-';
                $maxLel = '-';
                $avgPress = '-';
                $maxPress = '-';
                $status = 'Belum Ada Data';
                $statusClass = 'secondary';
            }

            $rekap[] = [
                'well_pad' => $wp,
                'count' => $count,
                'avg_co' => $avgCo,
                'max_co' => $maxCo,
                'avg_o2' => $avgO2,
                'avg_h2s' => $avgH2s,
                'max_h2s' => $maxH2s,
                'avg_lel' => $avgLel,
                'max_lel' => $maxLel,
                'avg_pressure' => $avgPress,
                'max_pressure' => $maxPress,
                'status' => $status,
                'status_class' => $statusClass,
            ];
        }

        return $rekap;
    }

    /**
     * Isi data awal realistis untuk well pad Dieng bila tabel masih kosong.
     */
    protected function seedInitialMonitoringDataIfEmpty(): void
    {
        if (GasMonitoring::count() > 0) {
            return;
        }

        WellPad::ensureDefaultsSeeded();
        $wellPadsMap = WellPad::pluck('id', 'name');

        $now = Carbon::now();
        $daysInMonth = $now->daysInMonth;
        $year = $now->year;
        $month = $now->month;

        $samples = [
            'Well Pad 7' => ['co_base' => 8.5, 'o2_base' => 20.9, 'h2s_base' => 3.2, 'lel_base' => 1.5, 'press_base' => 15.5],
            'Well Pad 9' => ['co_base' => 7.2, 'o2_base' => 20.8, 'h2s_base' => 2.5, 'lel_base' => 1.2, 'press_base' => 14.8],
            'Well Pad 28' => ['co_base' => 12.0, 'o2_base' => 20.9, 'h2s_base' => 4.5, 'lel_base' => 2.0, 'press_base' => 16.8],
            'Well Pad 30' => ['co_base' => 6.0, 'o2_base' => 21.0, 'h2s_base' => 2.8, 'lel_base' => 1.0, 'press_base' => 14.2],
            'Well Pad 31' => ['co_base' => 10.2, 'o2_base' => 20.8, 'h2s_base' => 3.9, 'lel_base' => 1.8, 'press_base' => 17.1],
        ];

        for ($d = 1; $d <= min($now->day, $daysInMonth); $d += 2) {
            $mDate = sprintf('%04d-%02d-%02d', $year, $month, $d);

            foreach ($samples as $wp => $cfg) {
                $co = max(1.0, round($cfg['co_base'] + (sin($d) * 3) + (rand(-10, 15) / 10), 1));
                $o2 = max(19.6, min(21.5, round($cfg['o2_base'] + (rand(-2, 2) / 10), 1)));
                $h2s = max(0.5, round($cfg['h2s_base'] + (cos($d) * 1.5) + (rand(-5, 10) / 10), 1));
                $lel = max(0.2, round($cfg['lel_base'] + (rand(-3, 5) / 10), 1));
                $press = max(5.0, round($cfg['press_base'] + (sin($d * 0.5) * 1.8) + (rand(-5, 8) / 10), 1));

                GasMonitoring::create([
                    'measurement_date' => $mDate,
                    'well_pad_id' => $wellPadsMap[$wp] ?? null,
                    'well_pad' => $wp,
                    'co_level' => $co,
                    'o2_level' => $o2,
                    'h2s_level' => $h2s,
                    'lel_level' => $lel,
                    'pressure' => $press,
                    'notes' => "Pemantauan rutin operasional {$wp}",
                    'recorded_by' => 'Petugas HSSE Dieng',
                ]);
            }
        }
    }

    // =========================================================================
    // CRUD PENGUKURAN GAS (SINGLE & BATCH)
    // =========================================================================

    /**
     * Ambil detail 1 pengukuran gas untuk modal detail & edit.
     */
    public function showMeasurement(GasMonitoring $gasMonitoring): JsonResponse
    {
        $pNotes = $gasMonitoring->parameter_notes;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $gasMonitoring->id,
                'measurement_date' => $gasMonitoring->measurement_date ? $gasMonitoring->measurement_date->format('Y-m-d') : null,
                'measurement_date_formatted' => $gasMonitoring->measurement_date ? $gasMonitoring->measurement_date->format('d/m/Y') : '-',
                'well_pad_id' => $gasMonitoring->well_pad_id,
                'well_pad' => $gasMonitoring->well_pad,
                'co_level' => $gasMonitoring->co_level,
                'o2_level' => $gasMonitoring->o2_level,
                'h2s_level' => $gasMonitoring->h2s_level,
                'lel_level' => $gasMonitoring->lel_level,
                'pressure' => $gasMonitoring->pressure,
                'co_status' => $gasMonitoring->co_status,
                'o2_status' => $gasMonitoring->o2_status,
                'h2s_status' => $gasMonitoring->h2s_status,
                'lel_status' => $gasMonitoring->lel_status,
                'pressure_status' => $gasMonitoring->pressure_status,
                'overall_status' => $gasMonitoring->overall_status,
                'co_notes' => $pNotes['co'] ?? null,
                'o2_notes' => $pNotes['o2'] ?? null,
                'h2s_notes' => $pNotes['h2s'] ?? null,
                'lel_notes' => $pNotes['lel'] ?? null,
                'pressure_notes' => $pNotes['pressure'] ?? null,
                'general_notes' => $pNotes['general'] ?? null,
                'notes' => $gasMonitoring->formatted_notes !== '-' ? $gasMonitoring->formatted_notes : null,
                'recorded_by' => $gasMonitoring->recorded_by,
            ],
        ]);
    }

    /**
     * Simpan data pengukuran gas massal (Batch per Tanggal untuk seluruh Well Pad aktif).
     */
    public function storeBatchMeasurements(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'measurement_date' => ['required', 'date'],
            'measurements' => ['required', 'array', 'min:1'],
            'measurements.*.well_pad_id' => ['nullable'],
            'measurements.*.well_pad_name' => ['required', 'string', 'max:100'],
            'measurements.*.co_level' => ['nullable', 'numeric', 'min:0'],
            'measurements.*.o2_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'measurements.*.h2s_level' => ['nullable', 'numeric', 'min:0'],
            'measurements.*.lel_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'measurements.*.pressure' => ['nullable', 'numeric', 'min:0'],
            'measurements.*.co_notes' => ['nullable', 'string', 'max:255'],
            'measurements.*.o2_notes' => ['nullable', 'string', 'max:255'],
            'measurements.*.h2s_notes' => ['nullable', 'string', 'max:255'],
            'measurements.*.lel_notes' => ['nullable', 'string', 'max:255'],
            'measurements.*.pressure_notes' => ['nullable', 'string', 'max:255'],
            'measurements.*.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'measurement_date.required' => 'Tanggal pengukuran wajib diisi.',
            'measurements.required' => 'Data parameter Well Pad wajib diisi.',
        ]);

        $mDate = Carbon::parse($validated['measurement_date'])->format('Y-m-d');
        $userName = Auth::user()?->name ?? 'Petugas HSSE Dieng';
        $savedItems = [];

        DB::beginTransaction();
        try {
            foreach ($validated['measurements'] as $row) {
                $coNote = trim((string) ($row['co_notes'] ?? ''));
                $o2Note = trim((string) ($row['o2_notes'] ?? ''));
                $h2sNote = trim((string) ($row['h2s_notes'] ?? ''));
                $lelNote = trim((string) ($row['lel_notes'] ?? ''));
                $pressNote = trim((string) ($row['pressure_notes'] ?? ''));
                $genNote = trim((string) ($row['notes'] ?? ''));

                $hasParamNotes = ($coNote !== '' || $o2Note !== '' || $h2sNote !== '' || $lelNote !== '' || $pressNote !== '' || $genNote !== '');

                $hasValue = ($row['co_level'] !== null && $row['co_level'] !== '')
                    || ($row['o2_level'] !== null && $row['o2_level'] !== '')
                    || ($row['h2s_level'] !== null && $row['h2s_level'] !== '')
                    || ($row['lel_level'] !== null && $row['lel_level'] !== '')
                    || ($row['pressure'] !== null && $row['pressure'] !== '')
                    || $hasParamNotes;

                if (!$hasValue) {
                    continue;
                }

                $wpName = trim((string) $row['well_pad_name']);
                $wpId = !empty($row['well_pad_id']) ? (int) $row['well_pad_id'] : WellPad::where('name', $wpName)->value('id');

                $existing = GasMonitoring::whereDate('measurement_date', $mDate)
                    ->where('well_pad', $wpName)
                    ->lockForUpdate()
                    ->first();

                $encodedNotes = null;
                if ($coNote !== '' || $o2Note !== '' || $h2sNote !== '' || $lelNote !== '' || $pressNote !== '') {
                    $encodedNotes = json_encode(array_filter([
                        'co' => $coNote !== '' ? $coNote : null,
                        'o2' => $o2Note !== '' ? $o2Note : null,
                        'h2s' => $h2sNote !== '' ? $h2sNote : null,
                        'lel' => $lelNote !== '' ? $lelNote : null,
                        'pressure' => $pressNote !== '' ? $pressNote : null,
                        'general' => $genNote !== '' ? $genNote : null,
                    ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE);
                } elseif ($genNote !== '') {
                    $encodedNotes = $genNote;
                }

                $payload = [
                    'measurement_date' => $mDate,
                    'well_pad_id' => $wpId,
                    'well_pad' => $wpName,
                    'co_level' => ($row['co_level'] !== null && $row['co_level'] !== '') ? (float) $row['co_level'] : null,
                    'o2_level' => ($row['o2_level'] !== null && $row['o2_level'] !== '') ? (float) $row['o2_level'] : null,
                    'h2s_level' => ($row['h2s_level'] !== null && $row['h2s_level'] !== '') ? (float) $row['h2s_level'] : null,
                    'lel_level' => ($row['lel_level'] !== null && $row['lel_level'] !== '') ? (float) $row['lel_level'] : null,
                    'pressure' => ($row['pressure'] !== null && $row['pressure'] !== '') ? (float) $row['pressure'] : null,
                    'notes' => $encodedNotes,
                    'recorded_by' => $userName,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $savedItems[] = $existing->fresh();
                } else {
                    $savedItems[] = GasMonitoring::create($payload);
                }
            }

            if (empty($savedItems)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Harap isi minimal satu parameter atau keterangan pada salah satu Well Pad.',
                ], 422);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Gasdetektor',
                'action' => 'TAMBAH',
                'record_type' => GasMonitoring::class,
                'record_id' => $savedItems[0]->id,
                'description' => 'Mencatat pengukuran Gas Monitoring tanggal ' . Carbon::parse($mDate)->format('d/m/Y') . ' untuk ' . count($savedItems) . ' Well Pad.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data Gas Monitoring (' . count($savedItems) . ' Well Pad) berhasil disimpan.',
                'startDate' => $mDate,
                'endDate' => $mDate,
                'data' => $savedItems,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data pengukuran gas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan data pengukuran gas tunggal di well pad.
     */
    public function storeMeasurement(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'measurement_date' => ['required', 'date'],
            'well_pad' => ['required', 'string', 'max:100'],
            'co_level' => ['nullable', 'numeric', 'min:0'],
            'o2_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'h2s_level' => ['nullable', 'numeric', 'min:0'],
            'lel_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pressure' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $userName = Auth::user()?->name ?? 'Petugas HSSE Dieng';

        $monitoring = DB::transaction(function () use ($validated, $userName) {
            $wpId = WellPad::where('name', $validated['well_pad'])->value('id');

            return GasMonitoring::create([
                'measurement_date' => $validated['measurement_date'],
                'well_pad_id' => $wpId,
                'well_pad' => $validated['well_pad'],
                'co_level' => ($validated['co_level'] !== null && $validated['co_level'] !== '') ? (float) $validated['co_level'] : null,
                'o2_level' => ($validated['o2_level'] !== null && $validated['o2_level'] !== '') ? (float) $validated['o2_level'] : null,
                'h2s_level' => ($validated['h2s_level'] !== null && $validated['h2s_level'] !== '') ? (float) $validated['h2s_level'] : null,
                'lel_level' => ($validated['lel_level'] !== null && $validated['lel_level'] !== '') ? (float) $validated['lel_level'] : null,
                'pressure' => ($validated['pressure'] !== null && $validated['pressure'] !== '') ? (float) $validated['pressure'] : null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $userName,
            ]);
        });

        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_name' => $userName,
            'module' => 'Gasdetektor',
            'action' => 'TAMBAH',
            'record_type' => GasMonitoring::class,
            'record_id' => $monitoring->id,
            'description' => "Mencatat pengukuran gas di {$monitoring->well_pad}: CO {$monitoring->co_level} ppm ({$monitoring->co_status}), O2 {$monitoring->o2_level}% ({$monitoring->o2_status}), H2S {$monitoring->h2s_level} ppm ({$monitoring->h2s_status}), LEL {$monitoring->lel_level}% ({$monitoring->lel_status}), Pressure {$monitoring->pressure} Psi",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pengukuran gas berhasil disimpan.',
                'data' => $monitoring,
            ]);
        }

        return redirect()->route('gas-detector.index', [
            'period_type' => 'daily',
            'filter_date' => Carbon::parse($validated['measurement_date'])->format('Y-m-d'),
            'well_pad' => $validated['well_pad'],
        ])->with('success', 'Data pengukuran gas berhasil disimpan.');
    }

    /**
     * Perbarui data pengukuran gas di well pad.
     */
    public function updateMeasurement(Request $request, GasMonitoring $gasMonitoring): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'measurement_date' => ['required', 'date'],
            'well_pad' => ['required', 'string', 'max:100'],
            'co_level' => ['nullable', 'numeric', 'min:0'],
            'o2_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'h2s_level' => ['nullable', 'numeric', 'min:0'],
            'lel_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pressure' => ['nullable', 'numeric', 'min:0'],
            'co_notes' => ['nullable', 'string', 'max:255'],
            'o2_notes' => ['nullable', 'string', 'max:255'],
            'h2s_notes' => ['nullable', 'string', 'max:255'],
            'lel_notes' => ['nullable', 'string', 'max:255'],
            'pressure_notes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $userName = Auth::user()?->name ?? 'Petugas HSSE Dieng';
        $wpId = WellPad::where('name', $validated['well_pad'])->value('id') ?? $gasMonitoring->well_pad_id;

        $coNote = trim((string) ($validated['co_notes'] ?? ''));
        $o2Note = trim((string) ($validated['o2_notes'] ?? ''));
        $h2sNote = trim((string) ($validated['h2s_notes'] ?? ''));
        $lelNote = trim((string) ($validated['lel_notes'] ?? ''));
        $pressNote = trim((string) ($validated['pressure_notes'] ?? ''));
        $genNote = trim((string) ($validated['notes'] ?? ''));

        $encodedNotes = null;
        if ($coNote !== '' || $o2Note !== '' || $h2sNote !== '' || $lelNote !== '' || $pressNote !== '') {
            $encodedNotes = json_encode(array_filter([
                'co' => $coNote !== '' ? $coNote : null,
                'o2' => $o2Note !== '' ? $o2Note : null,
                'h2s' => $h2sNote !== '' ? $h2sNote : null,
                'lel' => $lelNote !== '' ? $lelNote : null,
                'pressure' => $pressNote !== '' ? $pressNote : null,
                'general' => $genNote !== '' ? $genNote : null,
            ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE);
        } elseif ($genNote !== '') {
            $encodedNotes = $genNote;
        }

        $gasMonitoring = DB::transaction(function () use ($gasMonitoring, $validated, $encodedNotes) {
            $locked = GasMonitoring::where('id', $gasMonitoring->id)->lockForUpdate()->firstOrFail();
            $wpId = WellPad::where('name', $validated['well_pad'])->value('id') ?? $locked->well_pad_id;

            $locked->update([
                'measurement_date' => $validated['measurement_date'],
                'well_pad_id' => $wpId,
                'well_pad' => $validated['well_pad'],
                'co_level' => array_key_exists('co_level', $validated) ? (($validated['co_level'] !== null && $validated['co_level'] !== '') ? (float) $validated['co_level'] : null) : $locked->co_level,
                'o2_level' => array_key_exists('o2_level', $validated) ? (($validated['o2_level'] !== null && $validated['o2_level'] !== '') ? (float) $validated['o2_level'] : null) : $locked->o2_level,
                'h2s_level' => array_key_exists('h2s_level', $validated) ? (($validated['h2s_level'] !== null && $validated['h2s_level'] !== '') ? (float) $validated['h2s_level'] : null) : $locked->h2s_level,
                'lel_level' => array_key_exists('lel_level', $validated) ? (($validated['lel_level'] !== null && $validated['lel_level'] !== '') ? (float) $validated['lel_level'] : null) : $locked->lel_level,
                'pressure' => array_key_exists('pressure', $validated) ? (($validated['pressure'] !== null && $validated['pressure'] !== '') ? (float) $validated['pressure'] : null) : $locked->pressure,
                'notes' => $encodedNotes,
            ]);

            return $locked->fresh();
        });

        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_name' => $userName,
            'module' => 'Gasdetektor',
            'action' => 'UBAH',
            'record_type' => GasMonitoring::class,
            'record_id' => $gasMonitoring->id,
            'description' => "Memperbarui pengukuran gas di {$gasMonitoring->well_pad}: CO {$gasMonitoring->co_level} ppm, O2 {$gasMonitoring->o2_level}%, H2S {$gasMonitoring->h2s_level} ppm, LEL {$gasMonitoring->lel_level}%, Pressure {$gasMonitoring->pressure} Psi",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pengukuran gas berhasil diperbarui.',
                'data' => $gasMonitoring->fresh(),
            ]);
        }

        return redirect()->route('gas-detector.index', [
            'period_type' => 'daily',
            'filter_date' => Carbon::parse($validated['measurement_date'])->format('Y-m-d'),
        ])->with('success', 'Data pengukuran gas berhasil diperbarui.');
    }

    /**
     * Hapus data pengukuran gas di well pad.
     */
    public function destroyMeasurement(GasMonitoring $gasMonitoring): RedirectResponse|JsonResponse
    {
        $userName = Auth::user()?->name ?? 'Petugas HSSE Dieng';

        DB::transaction(function () use ($gasMonitoring, $userName) {
            $locked = GasMonitoring::where('id', $gasMonitoring->id)->lockForUpdate()->firstOrFail();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Gasdetektor',
                'action' => 'HAPUS',
                'record_type' => GasMonitoring::class,
                'record_id' => $locked->id,
                'description' => "Menghapus rekaman pengukuran gas di {$locked->well_pad} tanggal {$locked->measurement_date?->format('d/m/Y')}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $locked->delete();
        });

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pengukuran gas berhasil dihapus.',
            ]);
        }

        return redirect()->route('gas-detector.index')->with('success', 'Data pengukuran gas berhasil dihapus.');
    }

    // =========================================================================
    // MANAJEMEN WELL PAD (MODAL KELOLA WELL PAD)
    // =========================================================================

    /**
     * Ambil daftar seluruh Well Pad beserta jumlah data & tanggal input terakhir.
     */
    public function getWellPadsData(): JsonResponse
    {
        $wellPads = WellPad::sortNaturally(WellPad::ordered()->get());
        $data = $wellPads->values()->map(function (WellPad $wp) {
            $query = GasMonitoring::where(function ($q) use ($wp) {
                $q->where('well_pad_id', $wp->id)
                  ->orWhere('well_pad', $wp->name);
            });
            $count = (clone $query)->count();
            $lastDate = (clone $query)->max('measurement_date');

            return [
                'id' => $wp->id,
                'name' => $wp->name,
                'code' => $wp->code,
                'is_active' => (bool) $wp->is_active,
                'sort_order' => $wp->sort_order,
                'data_count' => $count,
                'last_input_date' => $lastDate ? Carbon::parse($lastDate)->format('d/m/Y') : '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Tambah Well Pad baru.
     */
    public function storeWellPad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:well_pads,name'],
            'code' => ['required', 'string', 'max:50', 'unique:well_pads,code'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama Well Pad wajib diisi.',
            'name.unique' => 'Nama Well Pad sudah terdaftar.',
            'code.required' => 'Kode Well Pad wajib diisi.',
            'code.unique' => 'Kode Well Pad sudah digunakan.',
        ]);

        $wellPad = WellPad::create([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => 9999,
        ]);

        WellPad::resequenceSortOrders();
        $wellPad->refresh();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'Administrator Dieng',
            'module' => 'Gasdetektor',
            'action' => 'TAMBAH',
            'record_type' => WellPad::class,
            'record_id' => $wellPad->id,
            'description' => "Menambahkan master Well Pad baru: {$wellPad->name} ({$wellPad->code})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Well Pad '{$wellPad->name}' berhasil ditambahkan.",
            'data' => $wellPad,
        ], 201);
    }

    /**
     * Perbarui Well Pad & sinkronkan nama pada riwayat gas_monitorings.
     */
    public function updateWellPad(Request $request, WellPad $wellPad): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('well_pads', 'name')->ignore($wellPad->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('well_pads', 'code')->ignore($wellPad->id)],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama Well Pad wajib diisi.',
            'name.unique' => 'Nama Well Pad sudah terdaftar.',
            'code.required' => 'Kode Well Pad wajib diisi.',
            'code.unique' => 'Kode Well Pad sudah digunakan.',
        ]);

        $oldName = $wellPad->name;
        $newName = trim($validated['name']);

        DB::beginTransaction();
        try {
            $wellPad->update([
                'name' => $newName,
                'code' => strtoupper(trim($validated['code'])),
                'is_active' => $validated['is_active'] ?? $wellPad->is_active,
            ]);

            if ($oldName !== $newName) {
                GasMonitoring::where('well_pad_id', $wellPad->id)
                    ->orWhere('well_pad', $oldName)
                    ->update([
                        'well_pad_id' => $wellPad->id,
                        'well_pad' => $newName,
                    ]);
            }

            WellPad::resequenceSortOrders();
            $wellPad->refresh();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'Administrator Dieng',
                'module' => 'Gasdetektor',
                'action' => 'UBAH',
                'record_type' => WellPad::class,
                'record_id' => $wellPad->id,
                'description' => "Memperbarui master Well Pad: {$oldName} -> {$wellPad->name} ({$wellPad->code})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Well Pad '{$wellPad->name}' berhasil diperbarui.",
                'data' => $wellPad,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui Well Pad: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus atau nonaktifkan Well Pad (jika memiliki riwayat pengukuran, otomatis dinonaktifkan).
     */
    public function destroyWellPad(Request $request, WellPad $wellPad): JsonResponse
    {
        $dataCount = GasMonitoring::where('well_pad_id', $wellPad->id)
            ->orWhere('well_pad', $wellPad->name)
            ->count();

        if ($dataCount > 0) {
            $wellPad->update(['is_active' => false]);
            WellPad::resequenceSortOrders();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'Administrator Dieng',
                'module' => 'Gasdetektor',
                'action' => 'UBAH',
                'record_type' => WellPad::class,
                'record_id' => $wellPad->id,
                'description' => "Menonaktifkan Well Pad '{$wellPad->name}' karena memiliki {$dataCount} riwayat pengukuran.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'action' => 'deactivated',
                'message' => "Well Pad '{$wellPad->name}' memiliki {$dataCount} data riwayat sehingga diubah menjadi Nonaktif agar histori tetap terjaga.",
            ]);
        }

        $name = $wellPad->name;
        $wellPad->delete();
        WellPad::resequenceSortOrders();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'Administrator Dieng',
            'module' => 'Gasdetektor',
            'action' => 'HAPUS',
            'record_type' => WellPad::class,
            'record_id' => $wellPad->id,
            'description' => "Menghapus master Well Pad: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'action' => 'deleted',
            'message' => "Well Pad '{$name}' berhasil dihapus.",
        ]);
    }

    // =========================================================================
    // C. INVENTARIS PERANGKAT GASDETEKTOR (FUNGSI ASLI YANG DIPERTAHANKAN 100%)
    // =========================================================================

    public function checkDuplicate(Request $request): JsonResponse
    {
        $deviceCode = trim((string) $request->input('device_code'));
        $ignoreId = $request->input('ignore_id');

        if (empty($deviceCode)) {
            return response()->json(['duplicate' => false]);
        }

        $query = GasDetector::where('device_code', $deviceCode);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $existing = $query->first();

        if ($existing) {
            return response()->json([
                'duplicate' => true,
                'message' => "Kode perangkat '{$existing->device_code}' sudah terdaftar untuk {$existing->model_name} ({$existing->unit}).",
                'data' => $existing,
            ]);
        }

        return response()->json(['duplicate' => false]);
    }

    public function show(Request $request, $id): JsonResponse|View|RedirectResponse
    {
        $device = GasDetector::find($id);

        if (!$device) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data perangkat Gasdetektor tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('gas-detector.index')->with('error', 'Data perangkat Gasdetektor tidak ditemukan.');
        }

        $activityLogs = ActivityLog::where('module', 'Gasdetektor')
            ->where(function ($q) use ($device) {
                $q->where('record_id', $device->id)
                  ->orWhere('description', 'like', "%{$device->device_code}%");
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'device' => $device,
                'is_calibration_due' => $device->isCalibrationDue(),
                'activity_logs' => $activityLogs,
            ]);
        }

        return view('gas-detector.show', compact('device', 'activityLogs'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Administrator yang dapat menambahkan perangkat Gasdetektor.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya Administrator yang dapat menambahkan perangkat Gasdetektor.');
        }

        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:50', 'unique:gas_detectors,device_code'],
            'model_name' => ['required', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'gas_type' => ['nullable', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', 'in:Tersedia,Sedang Digunakan,Dalam Perawatan,Perlu Kalibrasi'],
            'last_calibration_date' => ['nullable', 'date'],
            'next_calibration_date' => ['nullable', 'date', 'after_or_equal:last_calibration_date'],
            'user_in_charge' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'device_code.required' => 'Kode alat wajib diisi.',
            'device_code.unique' => 'Kode alat sudah terdaftar di sistem. Gunakan kode lain.',
            'model_name.required' => 'Model atau tipe alat wajib diisi.',
            'unit.required' => 'Unit operasi wajib dipilih.',
            'status.required' => 'Status alat wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'next_calibration_date.after_or_equal' => 'Tanggal kalibrasi berikutnya tidak boleh sebelum tanggal kalibrasi terakhir.',
        ]);

        $userName = Auth::user()?->name ?? 'Administrator Dieng';

        DB::beginTransaction();
        try {
            $device = GasDetector::create($validated);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Gasdetektor',
                'action' => 'TAMBAH',
                'record_type' => GasDetector::class,
                'record_id' => $device->id,
                'description' => "Menambahkan perangkat Gasdetektor baru: {$device->device_code} - {$device->model_name} ({$device->unit}, Status: {$device->status})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Perangkat Gasdetektor berhasil ditambahkan.',
                    'device' => $device,
                ], 201);
            }

            return redirect()->route('gas-detector.index')->with('success', 'Perangkat Gasdetektor berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan perangkat Gasdetektor: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal menyimpan perangkat Gasdetektor: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id): RedirectResponse|JsonResponse
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Administrator yang dapat mengubah data perangkat Gasdetektor.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya Administrator yang dapat mengubah data perangkat Gasdetektor.');
        }

        $device = GasDetector::find($id);

        if (!$device) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data perangkat Gasdetektor tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('gas-detector.index')->with('error', 'Data perangkat Gasdetektor tidak ditemukan.');
        }

        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:50', 'unique:gas_detectors,device_code,' . $device->id],
            'model_name' => ['required', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'gas_type' => ['nullable', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', 'in:Tersedia,Sedang Digunakan,Dalam Perawatan,Perlu Kalibrasi'],
            'last_calibration_date' => ['nullable', 'date'],
            'next_calibration_date' => ['nullable', 'date', 'after_or_equal:last_calibration_date'],
            'user_in_charge' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'device_code.required' => 'Kode alat wajib diisi.',
            'device_code.unique' => 'Kode alat sudah terdaftar di sistem. Gunakan kode lain.',
            'model_name.required' => 'Model atau tipe alat wajib diisi.',
            'unit.required' => 'Unit operasi wajib dipilih.',
            'status.required' => 'Status alat wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'next_calibration_date.after_or_equal' => 'Tanggal kalibrasi berikutnya tidak boleh sebelum tanggal kalibrasi terakhir.',
        ]);

        $userName = Auth::user()?->name ?? 'Administrator Dieng';
        $oldSnapshot = $device->only(array_keys($validated));

        DB::beginTransaction();
        try {
            $device->update($validated);

            $before = [];
            $after = [];
            foreach ($validated as $field => $newVal) {
                $oldVal = $oldSnapshot[$field] ?? null;
                if ($oldVal instanceof Carbon) {
                    $oldVal = $oldVal->format('Y-m-d');
                }
                if ($newVal instanceof Carbon) {
                    $newVal = $newVal->format('Y-m-d');
                }
                if ((string) $oldVal !== (string) $newVal) {
                    $before[$field] = $oldVal;
                    $after[$field] = $newVal;
                }
            }

            $diffText = '';
            if (!empty($before)) {
                $diffText = ' Perubahan: ' . implode(', ', array_map(function ($key, $oldVal, $newVal) {
                    return "{$key} ('{$oldVal}' -> '{$newVal}')";
                }, array_keys($before), $before, $after));
            }

            $changesPayload = !empty($before) ? ['before' => $before, 'after' => $after] : null;

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Gasdetektor',
                'action' => 'UBAH',
                'record_type' => GasDetector::class,
                'record_id' => $device->id,
                'description' => "Memperbarui perangkat Gasdetektor: {$device->device_code} - {$device->model_name} ({$device->unit}).{$diffText}",
                'changes' => $changesPayload,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data perangkat Gasdetektor berhasil diperbarui.',
                    'device' => $device,
                ]);
            }

            return redirect()->route('gas-detector.index')->with('success', 'Data perangkat Gasdetektor berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui perangkat Gasdetektor: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui perangkat Gasdetektor: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Administrator yang dapat menghapus perangkat Gasdetektor.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya Administrator yang dapat menghapus perangkat Gasdetektor.');
        }

        $device = GasDetector::find($id);

        if (!$device) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data perangkat Gasdetektor tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('gas-detector.index')->with('error', 'Data perangkat Gasdetektor tidak ditemukan.');
        }

        $userName = Auth::user()?->name ?? 'Administrator Dieng';

        DB::beginTransaction();
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Gasdetektor',
                'action' => 'HAPUS',
                'record_type' => GasDetector::class,
                'record_id' => $device->id,
                'description' => "Menghapus perangkat Gasdetektor: {$device->device_code} - {$device->model_name} ({$device->unit})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $device->delete();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Perangkat Gasdetektor berhasil dihapus.',
                ]);
            }

            return redirect()->route('gas-detector.index')->with('success', 'Perangkat Gasdetektor berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus perangkat Gasdetektor: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus perangkat Gasdetektor: ' . $e->getMessage());
        }
    }

    /**
     * Export Gas Monitoring measurements to PDF (supports all 6 period filters).
     */
    public function exportMeasurements(Request $request): Response
    {
        $periodResolved = $this->resolvePeriodFilter($request);
        $periodType = $periodResolved['period_type'];
        $startDate = $periodResolved['start_date'];
        $endDate = $periodResolved['end_date'];
        $periodLabel = $periodResolved['formatted_label'];

        $rawWellPad = $request->input('well_pad', 'all');
        if (is_array($rawWellPad)) {
            $selectedWellPads = array_values(array_filter($rawWellPad, fn ($v) => !empty($v) && $v !== 'all'));
            $wellPadFilter = empty($selectedWellPads) ? 'all' : $selectedWellPads;
            $wellPadLabel = empty($selectedWellPads) ? 'Semua Well Pad' : implode(', ', $selectedWellPads);
        } else {
            $strWp = trim((string) $rawWellPad);
            $wellPadFilter = ($strWp !== '' && $strWp !== 'all') ? $strWp : 'all';
            $wellPadLabel = $wellPadFilter === 'all' ? 'Semua Well Pad' : $wellPadFilter;
        }
        $parameterFilter = (string) $request->input('indicator', 'all');

        $query = GasMonitoring::query()->with('wellPad');
        $this->applyPeriodToQuery($query, $periodType, $startDate, $endDate);
        $query->byWellPad($wellPadFilter);

        $searchQuery = trim((string) $request->input('search', ''));
        if ($searchQuery !== '') {
            $query->where(function (Builder $q) use ($searchQuery) {
                $q->where('well_pad', 'like', "%{$searchQuery}%")
                  ->orWhere('notes', 'like', "%{$searchQuery}%")
                  ->orWhere('recorded_by', 'like', "%{$searchQuery}%")
                  ->orWhere('measurement_date', 'like', "%{$searchQuery}%");
            });
        }

        $records = $query->orderBy('measurement_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.gas-monitoring-pdf', [
            'reportTitle' => 'LAPORAN PEMANTAUAN GAS WELL PAD (GAS MONITORING)',
            'moduleName' => 'Gas Monitoring',
            'filterInfo' => [
                'Periode' => $periodLabel,
                'Well Pad' => $wellPadLabel,
                'Parameter' => $parameterFilter === 'all' ? 'Semua Parameter (CO, O₂, H₂S, LEL, Pressure)' : strtoupper($parameterFilter),
                'Total Rekord' => $records->count() . ' Pengukuran',
            ],
            'records' => $records,
            'userName' => Auth::user()->name ?? 'Administrator',
            'generatedAt' => now()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan-Gas-Monitoring-' . $startDate . '-sd-' . $endDate . '-' . now()->format('His') . '.pdf');
    }

    /**
     * Alias export function to support both route formats.
     */
    public function export(Request $request): Response
    {
        return $this->exportMeasurements($request);
    }
}