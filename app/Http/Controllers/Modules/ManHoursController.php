<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DailyWorkCompany;
use App\Models\ManHour;
use App\Models\OperationalUnit;
use App\Models\UnitManHourAdjustment;
use App\Models\Workforce;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManHoursController extends Controller
{
    /**
     * Daftar 7 Perusahaan / Kategori Standar untuk kompatibilitas mundur
     */
    public const TARGET_COMPANIES = ['GDE', 'KODIPA', 'UTILITY', 'SECURITY', 'JOTRE', 'PMC', 'KP'];

    /**
     * Daftar 12 Perusahaan Standar sesuai tampilan Man Hours & Manpower (Oktober 2026)
     */
    public const DISPLAY_COMPANIES = [
        'GDE (Only)',
        'KODIPA',
        'JOTRE (Only)',
        'ADP',
        'SOKKO',
        'BISMO',
        'WCP',
        'TDP',
        'PJSE',
        'Rentokil',
        'PMC',
        'Bhumi Phala',
    ];

    /**
     * Data Referensi Harian Default (Dikosongkan agar sistem hanya menampilkan data aktual database)
     */
    public const DEFAULT_OCTOBER_DAYS = [];

    /**
     * Ubah nama master perusahaan menjadi label tampilan pendek yang rapi sesuai UI referensi.
     */
    public static function toShortDisplayName(string $name): string
    {
        $map = [
            'TDP (BUJP)' => 'TDP',
            'PJSE (Utility)' => 'PJSE',
            'Rentokil (Pest Control)' => 'Rentokil',
            'Bhumi Phala (Maintenance EWS)' => 'Bhumi Phala',
        ];

        $trimmed = trim($name);
        return $map[$trimmed] ?? $trimmed;
    }

    /**
     * Ambil daftar nama perusahaan yang aktif untuk digunakan pada Man Hours & Manpower.
     * Mengambil dari master DailyWorkCompany yang aktif (tidak termasuk rollup agregat seperti '(All)').
     *
     * @return array<int, string>
     */
    public static function getActiveCompanyNames(): array
    {
        DailyWorkCompany::ensureRequired();

        $rawCompanies = DailyWorkCompany::where('is_active', true)
            ->where('name', 'not like', '%(All)%')
            ->ordered()
            ->pluck('name')
            ->map(fn ($n) => self::toShortDisplayName($n))
            ->filter(fn ($n) => strcasecmp($n, 'Environesia') !== 0)
            ->unique()
            ->values()
            ->all();

        if (empty($rawCompanies)) {
            return self::DISPLAY_COMPANIES;
        }

        // Susun urutan sesuai urutan standar DISPLAY_COMPANIES terlebih dahulu, diikuti perusahaan baru tambahan
        $ordered = [];
        foreach (self::DISPLAY_COMPANIES as $std) {
            foreach ($rawCompanies as $rc) {
                if (strcasecmp($rc, $std) === 0) {
                    $ordered[] = $std;
                    break;
                }
            }
        }
        foreach ($rawCompanies as $rc) {
            $exists = false;
            foreach ($ordered as $o) {
                if (strcasecmp($o, $rc) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $ordered[] = $rc;
            }
        }

        return !empty($ordered) ? $ordered : self::DISPLAY_COMPANIES;
    }

    /**
     * Petakan nama unit/kategori ke salah satu dari perusahaan aktif atau standar (tanpa double-counting).
     */
    private function resolveTargetCompany(?string $unit, ?string $category, ?array $activeNames = null): string
    {
        $activeNames = $activeNames ?: self::getActiveCompanyNames();
        $uRaw = trim($unit ?? '');
        $cRaw = trim($category ?? '');
        $uShort = self::toShortDisplayName($uRaw);
        $cShort = self::toShortDisplayName($cRaw);
        $uUpper = strtoupper($uShort);
        $cUpper = strtoupper($cShort);

        // 1. Exact match case-insensitive dengan active company list
        foreach ($activeNames as $comp) {
            if (strcasecmp($comp, $uRaw) === 0 || strcasecmp($comp, $cRaw) === 0 || strcasecmp($comp, $uShort) === 0 || strcasecmp($comp, $cShort) === 0) {
                return $comp;
            }
        }

        // 2. Alias mapping untuk legacy / alternate naming
        $aliases = [
            'KERJA PRAKTIK' => 'KP',
            'MAGANG / KP' => 'KP',
            'CONTRACTOR & SUB-CONTRACTOR' => 'PMC',
            'KONTRAKTOR' => 'PMC',
            'UTILITY' => 'PJSE',
            'PJSE (UTILITY)' => 'PJSE',
            'SECURITY' => 'TDP',
            'TDP (BUJP)' => 'TDP',
            'RENTOKIL (PEST CONTROL)' => 'Rentokil',
            'BHUMI PHALA (MAINTENANCE EWS)' => 'Bhumi Phala',
            'GDE' => 'GDE (Only)',
            'PMU' => 'GDE (Only)',
            'DIENG UNIT 1' => 'GDE (Only)',
            'DIENG UNIT 2' => 'GDE (Only)',
            'JOTRE' => 'JOTRE (Only)',
        ];

        if (isset($aliases[$uUpper])) {
            $mapped = $aliases[$uUpper];
            foreach ($activeNames as $comp) {
                if (strcasecmp($comp, $mapped) === 0) {
                    return $comp;
                }
            }
            return $mapped;
        }

        if (isset($aliases[$cUpper])) {
            $mapped = $aliases[$cUpper];
            foreach ($activeNames as $comp) {
                if (strcasecmp($comp, $mapped) === 0) {
                    return $comp;
                }
            }
            return $mapped;
        }

        // 3. Fallback jika ada unit name langsung
        if ($uShort !== '') {
            return $uShort;
        }

        return $activeNames[0] ?? 'GDE (Only)';
    }

    /**
     * Tampilkan halaman Man Hours & Manpower yang bersumber otomatis dari data Workforce.
     * Rumus: Total Man Hours = Jumlah Pekerja x Jam Kerja per Orang (default 8 jam).
     * Idempoten: setiap reload hanya membaca & menyinkronkan 1-ke-1 dengan Workforce tanpa menggandakan data.
     */
    public function index(Request $request)
    {
        // 0. Pastikan master unit standar & master perusahaan tersedia
        OperationalUnit::ensureDefaultsSeeded();
        DailyWorkCompany::ensureRequired();

        // 1. Pastikan seluruh data Workforce telah tersinkronisasi 1-ke-1 ke tabel man_hours
        $unsyncedWorkforces = Workforce::doesntHave('manHours')->get();
        foreach ($unsyncedWorkforces as $wf) {
            $wf->syncManHourRecord();
        }

        // Hapus record yatim (orphan) yang tidak lagi memiliki pasangan Workforce valid
        ManHour::whereNotNull('workforce_id')
            ->whereDoesntHave('workforce')
            ->delete();

        // 2. Ambil daftar unit operasional aktif & semua unit
        $operationalUnits = OperationalUnit::active()->get();
        $allOperationalUnits = OperationalUnit::ordered()->get();

        // 3. Resolusi Filter Periode Dinamis (all, daily, weekly, monthly, yearly, custom)
        $periodInfo = $this->resolvePeriodFilter($request);
        $periodType = $periodInfo['period_type'];
        $startDate = $periodInfo['start_date'];
        $endDate = $periodInfo['end_date'];
        $formattedPeriodLabel = $periodInfo['label'];

        // Mendukung multiple unit / array of units dari dropdown checkbox
        $unitFilter = $request->input('unit', 'all');
        $selectedUnits = [];
        if (is_array($unitFilter)) {
            $selectedUnits = array_values(array_filter(array_map('trim', $unitFilter)));
            if (empty($selectedUnits) || in_array('all', array_map('strtolower', $selectedUnits), true) || in_array('semua', array_map('strtolower', $selectedUnits), true)) {
                $unitFilter = 'all';
                $selectedUnits = [];
            }
        } elseif (in_array(strtolower(trim((string) $unitFilter)), ['all', 'semua', ''], true)) {
            $unitFilter = 'all';
            $selectedUnits = [];
        } else {
            $parts = array_filter(array_map('trim', explode(',', (string) $unitFilter)));
            if (count($parts) > 1) {
                $selectedUnits = $parts;
            } elseif (count($parts) === 1 && !in_array(strtolower($parts[0]), ['all', 'semua'], true)) {
                $selectedUnits = [$parts[0]];
            } else {
                $unitFilter = 'all';
                $selectedUnits = [];
            }
        }
        $categoryFilter = $request->input('category', 'all');
        $search = trim((string) $request->input('search', ''));

        // Ambil waktu terakhir data diinput ke sistem (konversi ke WIB/Asia/Jakarta)
        $lastRecord = Workforce::orderBy('updated_at', 'desc')->first();
        $lastInputTimeFormatted = $lastRecord && $lastRecord->updated_at
            ? Carbon::parse($lastRecord->updated_at)->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i') . ' WIB'
            : '-';

        // 4. Query dasar Workforce & ManHour sesuai filter periode & unit/perusahaan
        $workforceQuery = Workforce::query();
        $manHourQuery = ManHour::with('workforce');

        if ($startDate) {
            $workforceQuery->whereDate('record_date', '>=', $startDate);
            $manHourQuery->whereDate('work_date', '>=', $startDate);
        }
        if ($endDate) {
            $workforceQuery->whereDate('record_date', '<=', $endDate);
            $manHourQuery->whereDate('work_date', '<=', $endDate);
        }

        if ($unitFilter !== 'all') {
            $expandedUnits = $selectedUnits;
            if (in_array('GDE (All)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['GDE', 'GDE (Only)', 'KODIPA']));
            }
            if (in_array('GDE (Only)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['GDE']));
            }
            if (in_array('JOTRE (All)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['JOTRE', 'JOTRE (Only)', 'PJSE', 'ADP', 'SOKKO', 'BISMO', 'WCP']));
            }
            if (in_array('JOTRE (Only)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['JOTRE']));
            }
            if (in_array('TDP', $selectedUnits, true) || in_array('TDP (BUJP)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['TDP', 'TDP (BUJP)', 'SECURITY', 'Security']));
            }
            if (in_array('PJSE', $selectedUnits, true) || in_array('PJSE (Utility)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['PJSE', 'PJSE (Utility)', 'UTILITY', 'Utility']));
            }
            if (in_array('Rentokil', $selectedUnits, true) || in_array('Rentokil (Pest Control)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['Rentokil', 'Rentokil (Pest Control)']));
            }
            if (in_array('Bhumi Phala', $selectedUnits, true) || in_array('Bhumi Phala (Maintenance EWS)', $selectedUnits, true)) {
                $expandedUnits = array_unique(array_merge($expandedUnits, ['Bhumi Phala', 'Bhumi Phala (Maintenance EWS)']));
            }

            $workforceQuery->where(function ($q) use ($expandedUnits, $unitFilter) {
                if (!empty($expandedUnits)) {
                    $q->whereIn('unit', $expandedUnits)
                        ->orWhereIn('category', $expandedUnits);
                } else {
                    $q->where('unit', $unitFilter)
                        ->orWhere('category', $unitFilter);
                }
            });
            $manHourQuery->where(function ($q) use ($expandedUnits, $unitFilter) {
                if (!empty($expandedUnits)) {
                    $q->whereIn('unit', $expandedUnits)
                        ->orWhereIn('category', $expandedUnits);
                } else {
                    $q->where('unit', $unitFilter)
                        ->orWhere('category', $unitFilter);
                }
            });
        }

        if ($categoryFilter !== 'all') {
            $workforceQuery->where('category', $categoryFilter);
            $manHourQuery->where('category', $categoryFilter);
        }

        if ($search !== '') {
            $workforceQuery->where(function ($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });

            $manHourQuery->where(function ($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $filteredWorkforces = (clone $workforceQuery)
            ->with('manHour')
            ->orderBy('record_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $filteredManHours = (clone $manHourQuery)
            ->orderBy('work_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // 5. Statistik Utama untuk 4 Summary Card
        $totalWorkersPeriod = (int) $filteredWorkforces->sum('worker_count');
        $rawPeriodManHours = (float) $filteredManHours->sum('total_hours');

        // Hitung total penyesuaian manual (adjustment delta) dari unit yang relevan
        $relevantUnitsForStats = $unitFilter !== 'all'
            ? $allOperationalUnits->where('name', $unitFilter)
            : $allOperationalUnits;

        $totalManualAdjustmentDelta = 0.0;
        if (!$startDate && !$endDate && $categoryFilter === 'all' && $search === '') {
            $totalManualAdjustmentDelta = (float) $relevantUnitsForStats->sum(function ($u) {
                return $u->manual_accumulated_hours !== null ? (float) $u->accumulated_hours_adjustment : 0.0;
            });
        }

        $totalManHoursPeriod = round($rawPeriodManHours + $totalManualAdjustmentDelta, 2);

        $avgHoursPerWorker = $totalWorkersPeriod > 0
            ? round($totalManHoursPeriod / $totalWorkersPeriod, 2)
            : 0;

        // Total Manhour Kumulatif (Seluruh Periode / All Time)
        $allTimeRawManHours = (float) ManHour::sum('total_hours');
        $allTimeAdjustments = (float) $allOperationalUnits->sum(function ($u) {
            return $u->manual_accumulated_hours !== null ? (float) $u->accumulated_hours_adjustment : 0.0;
        });
        $cumulativeTotalManHours = round($allTimeRawManHours + $allTimeAdjustments, 2);

        $latestDateInScope = (clone $workforceQuery)->max('record_date');
        $latestDateFormatted = $latestDateInScope ? Carbon::parse($latestDateInScope)->toDateString() : null;

        $dailyWorkersLatest = 0;
        $dailyManHoursLatest = 0.0;

        if ($latestDateFormatted) {
            $latestQuery = Workforce::whereDate('record_date', $latestDateFormatted);
            if ($unitFilter !== 'all') {
                $latestQuery->where(function ($q) use ($unitFilter) {
                    $q->where('unit', $unitFilter)->orWhere('category', $unitFilter);
                });
            }
            if ($categoryFilter !== 'all') {
                $latestQuery->where('category', $categoryFilter);
            }
            $latestRows = $latestQuery->get();
            $dailyWorkersLatest = (int) $latestRows->sum('worker_count');
            $dailyManHoursLatest = (float) $latestRows->sum(function ($row) {
                return (int) $row->worker_count * (float) ($row->hours_per_worker ?: 8);
            });
        }

        $refMonthDate = $endDate
            ? Carbon::parse($endDate)
            : ($latestDateFormatted ? Carbon::parse($latestDateFormatted) : Carbon::now());

        $monthlyQuery = ManHour::whereYear('work_date', $refMonthDate->year)
            ->whereMonth('work_date', $refMonthDate->month);

        if ($unitFilter !== 'all') {
            $monthlyQuery->where(function ($q) use ($unitFilter) {
                $q->where('unit', $unitFilter)->orWhere('category', $unitFilter);
            });
        }
        if ($categoryFilter !== 'all') {
            $monthlyQuery->where('category', $categoryFilter);
        }
        $monthlyManHours = (float) $monthlyQuery->sum('total_hours');

        $stats = [
            'total_workers' => $totalWorkersPeriod,
            'total_man_hours' => $totalManHoursPeriod,
            'raw_total_man_hours' => round($rawPeriodManHours, 2),
            'manual_adjustment_delta' => round($totalManualAdjustmentDelta, 2),
            'avg_hours_per_worker' => $avgHoursPerWorker,
            'cumulative_man_hours' => $cumulativeTotalManHours,
            'daily_workers' => $dailyWorkersLatest,
            'daily_man_hours' => round($dailyManHoursLatest, 2),
            'latest_date' => $latestDateFormatted,
            'latest_date_display' => $latestDateFormatted
                ? Carbon::parse($latestDateFormatted)->locale('id')->translatedFormat('d F Y')
                : 'Belum ada data',
            'monthly_man_hours' => round($monthlyManHours, 2),
            'monthly_label' => $refMonthDate->locale('id')->translatedFormat('F Y'),
            'total_records' => $filteredManHours->count(),
            'active_units_count' => $operationalUnits->count(),
        ];

        // 6. Susun Rekap Harian Berdasarkan Tanggal untuk Tabel Utama
        $activeCompanyList = self::getActiveCompanyNames();

        $groupedByDate = $filteredWorkforces->groupBy(function ($item) {
            return $item->record_date ? $item->record_date->format('Y-m-d') : 'Unknown';
        });

        $dateGroupedHistory = collect();
        $overallMaxWorkers = 0;
        $overallMaxWorkersDate = null;
        $overallMaxManhours = 0.0;
        $overallMaxManhoursDate = null;

        // Periksa apakah perlu menggabungkan data referensi Oktober 2026
        // (Hanya digabungkan jika tidak sedang difilter pada tanggal di luar Oktober 2026)
        $includeDefaultOctober = true;
        if ($startDate && $startDate > '2026-10-31') {
            $includeDefaultOctober = false;
        }
        if ($endDate && $endDate < '2026-10-01') {
            $includeDefaultOctober = false;
        }
        if ($categoryFilter !== 'all' || $search !== '') {
            $includeDefaultOctober = false;
        }

        // Bangun daftar tanggal gabungan dari DB + Default Oktober 2026
        $allDateKeys = $groupedByDate->keys()->filter(fn ($k) => $k !== 'Unknown')->all();
        if ($includeDefaultOctober) {
            foreach (array_keys(self::DEFAULT_OCTOBER_DAYS) as $octDate) {
                if ($startDate && $octDate < $startDate) {
                    continue;
                }
                if ($endDate && $octDate > $endDate) {
                    continue;
                }
                if (!in_array($octDate, $allDateKeys, true)) {
                    $allDateKeys[] = $octDate;
                }
            }
        }

        // Expanded selected units untuk pencocokan nama pendek
        $selectedShortUnits = [];
        if (!empty($selectedUnits)) {
            $expandedSel = $selectedUnits;
            if (in_array('GDE (All)', $selectedUnits, true)) {
                $expandedSel = array_merge($expandedSel, ['GDE (Only)', 'KODIPA']);
            }
            if (in_array('JOTRE (All)', $selectedUnits, true)) {
                $expandedSel = array_merge($expandedSel, ['JOTRE (Only)', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'PJSE']);
            }
            $selectedShortUnits = array_values(array_unique(array_map([self::class, 'toShortDisplayName'], $expandedSel)));
        }

        foreach ($allDateKeys as $dateKey) {
            $hasDbRows = $groupedByDate->has($dateKey) && $groupedByDate->get($dateKey)->isNotEmpty();

            if ($hasDbRows) {
                $wList = $groupedByDate->get($dateKey);
                $compBreakdown = [];
                $dateTotalWorkers = 0;
                $dateTotalManhours = 0.0;

                foreach ($activeCompanyList as $cName) {
                    if (!empty($selectedShortUnits) && !in_array($cName, $selectedShortUnits, true)) {
                        $compBreakdown[$cName] = [
                            'workers' => 0,
                            'manhours' => 0.0,
                        ];
                        continue;
                    }

                    $matchedRows = $wList->filter(function ($w) use ($cName, $activeCompanyList) {
                        return $this->resolveTargetCompany($w->unit, $w->category, $activeCompanyList) === $cName;
                    });
                    $wSum = (int) $matchedRows->sum('worker_count');
                    $mhSum = (float) $matchedRows->sum(function ($w) {
                        if ($w->manHour && $w->manHour->total_hours !== null) {
                            return (float) $w->manHour->total_hours;
                        }
                        return $w->calculated_man_hours !== null
                            ? (float) $w->calculated_man_hours
                            : ((int) $w->worker_count * (float) ($w->hours_per_worker ?? 8));
                    });

                    $compBreakdown[$cName] = [
                        'workers' => $wSum,
                        'manhours' => round($mhSum, 2),
                    ];

                    $dateTotalWorkers += $wSum;
                    $dateTotalManhours += $mhSum;
                }

                foreach (self::TARGET_COMPANIES as $stdName) {
                    if (!isset($compBreakdown[$stdName])) {
                        $matchedStd = $wList->filter(function ($w) use ($stdName) {
                            return strtoupper(trim($w->unit ?? '')) === $stdName || strtoupper(trim($w->category ?? '')) === $stdName;
                        });
                        $compBreakdown[$stdName] = [
                            'workers' => (int) $matchedStd->sum('worker_count'),
                            'manhours' => round((float) $matchedStd->sum(fn ($w) => (int) $w->worker_count * (float) ($w->hours_per_worker ?? 8)), 2),
                        ];
                    }
                }

                $lastUpdatedRecord = $wList->sortByDesc('updated_at')->first();
                $lastUpdatedAtFormatted = $lastUpdatedRecord && $lastUpdatedRecord->updated_at
                    ? Carbon::parse($lastUpdatedRecord->updated_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i')
                    : ($lastUpdatedRecord && $lastUpdatedRecord->created_at ? Carbon::parse($lastUpdatedRecord->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-');
            } else {
                // Gunakan data referensi Oktober 2026
                $defDay = self::DEFAULT_OCTOBER_DAYS[$dateKey];
                $defUnits = $defDay['units'] ?? [];
                $defManhoursMap = $defDay['manhours'] ?? [];
                $defTotalW = (int) ($defDay['workers'] ?? array_sum($defUnits));
                $defTotalH = (float) ($defDay['hours'] ?? ($defTotalW * 8));
                $avgRatio = $defTotalW > 0 ? ($defTotalH / $defTotalW) : 8.0;

                $compBreakdown = [];
                $dateTotalWorkers = 0;
                $dateTotalManhours = 0.0;

                foreach ($activeCompanyList as $cName) {
                    if (!empty($selectedShortUnits) && !in_array($cName, $selectedShortUnits, true)) {
                        $compBreakdown[$cName] = [
                            'workers' => 0,
                            'manhours' => 0.0,
                        ];
                        continue;
                    }

                    $wSum = (int) ($defUnits[$cName] ?? 0);
                    $mhSum = isset($defManhoursMap[$cName])
                        ? (float) $defManhoursMap[$cName]
                        : round($wSum * $avgRatio, 0);

                    $compBreakdown[$cName] = [
                        'workers' => $wSum,
                        'manhours' => $mhSum,
                    ];

                    $dateTotalWorkers += $wSum;
                    $dateTotalManhours += $mhSum;
                }

                // Jika tidak ada filter unit, pastikan total manhours persis sama dengan nilai referensi harian
                if (empty($selectedShortUnits)) {
                    $dateTotalWorkers = $defTotalW;
                    $dateTotalManhours = $defTotalH;
                }

                foreach (self::TARGET_COMPANIES as $stdName) {
                    if (!isset($compBreakdown[$stdName])) {
                        $mappedShort = $this->resolveTargetCompany($stdName, $stdName, $activeCompanyList);
                        $compBreakdown[$stdName] = $compBreakdown[$mappedShort] ?? ['workers' => 0, 'manhours' => 0.0];
                    }
                }

                $lastUpdatedAtFormatted = Carbon::parse($dateKey)->format('d/m/Y') . ' 17:00';
            }

            if ($dateTotalWorkers > $overallMaxWorkers) {
                $overallMaxWorkers = $dateTotalWorkers;
                $overallMaxWorkersDate = $dateKey;
            }
            if ($dateTotalManhours > $overallMaxManhours) {
                $overallMaxManhours = $dateTotalManhours;
                $overallMaxManhoursDate = $dateKey;
            }

            $dateGroupedHistory->push([
                'date' => $dateKey,
                'companies' => $compBreakdown,
                'total_workers' => $dateTotalWorkers,
                'total_manhours' => round($dateTotalManhours, 2),
                'last_update' => $lastUpdatedAtFormatted,
            ]);
        }

        // Urutkan tanggal terbaru di atas
        $dateGroupedHistory = $dateGroupedHistory->sortByDesc('date')->values();

        // Bentuk struktur $historyTableRows untuk tabel Riwayat Man Hours & Manpower
        $historyTableRows = $dateGroupedHistory->map(function ($item) use ($activeCompanyList) {
            $unitsSimple = [];
            foreach ($activeCompanyList as $cName) {
                $unitsSimple[$cName] = (int) ($item['companies'][$cName]['workers'] ?? 0);
            }
            foreach (self::TARGET_COMPANIES as $stdName) {
                if (!isset($unitsSimple[$stdName])) {
                    $unitsSimple[$stdName] = (int) ($item['companies'][$stdName]['workers'] ?? 0);
                }
            }

            $wTotal = (int) $item['total_workers'];
            $mhTotal = (float) $item['total_manhours'];
            $avgH = $wTotal > 0 ? round($mhTotal / $wTotal, 1) : 8.0;

            return [
                'date' => $item['date'],
                'date_formatted' => Carbon::parse($item['date'])->format('d/m/Y'),
                'units' => $unitsSimple,
                'companies' => $item['companies'],
                'total_workers' => $wTotal,
                'hours_per_worker' => $avgH,
                'total_man_hours' => round($mhTotal, 2),
                'total_manhours' => round($mhTotal, 2),
                'last_update' => $item['last_update'] ?? '-',
            ];
        })->values()->all();

        // 6.b Pemisahan Logika Data:
        // A. Data Statis untuk Tanggal Utama & 4 Kartu Statistik (Berdasarkan data aktual terbaru & seluruh histori DB, TIDAK terpengaruh filter)
        // B. Data Dinamis untuk Grafik Tren & Rekap Tenaga Kerja (Mengikuti filter rentang tanggal terpilih)
        $isDateFiltered = ($periodType !== 'all')
            || !empty($startDate)
            || !empty($endDate);

        $isFilterActive = $isDateFiltered
            || ($unitFilter !== 'all')
            || !empty($selectedUnits)
            || ($categoryFilter !== 'all')
            || ($search !== '');

        // 1. Tentukan Tanggal Aktivitas/Input Terakhir di Database (tanpa menggunakan now()/today() sebagai sumber tanggal utama)
        $latestWorkforceDateRaw = Workforce::max('record_date') ?: ManHour::max('work_date');
        $latestActivityDateStr = $latestWorkforceDateRaw
            ? Carbon::parse($latestWorkforceDateRaw)->format('Y-m-d')
            : null;
        $referenceSidebarDate = $latestActivityDateStr;

        if ($latestActivityDateStr) {
            $carbonLatestDate = Carbon::parse($latestActivityDateStr)->locale('id');
            $latestActiveDayName = $carbonLatestDate->isoFormat('dddd');
            $latestActiveDateFormatted = $carbonLatestDate->isoFormat('D MMMM Y');
            $latestActiveDateHeader = $latestActiveDayName . ', ' . $latestActiveDateFormatted;

            // Hitung Manpower (Today) & Man Hours (Today) dari tanggal aktivitas terakhir di DB (statis, tidak terpengaruh filter)
            $latestDayWorkforces = Workforce::with('manHour')
                ->whereDate('record_date', $latestActivityDateStr)
                ->get();

            $manpowerToday = 0;
            $manhoursToday = 0.0;
            foreach ($activeCompanyList as $cName) {
                $matchedLatest = $latestDayWorkforces->filter(function ($w) use ($cName, $activeCompanyList) {
                    return $this->resolveTargetCompany($w->unit, $w->category, $activeCompanyList) === $cName;
                });
                $manpowerToday += (int) $matchedLatest->sum('worker_count');
                $manhoursToday += (float) $matchedLatest->sum(function ($w) {
                    if ($w->manHour && $w->manHour->total_hours !== null) {
                        return (float) $w->manHour->total_hours;
                    }
                    return $w->calculated_man_hours !== null
                        ? (float) $w->calculated_man_hours
                        : ((int) $w->worker_count * (float) ($w->hours_per_worker ?? 8));
                });
            }
        } else {
            $latestActiveDayName = '-';
            $latestActiveDateFormatted = 'Belum ada data';
            $latestActiveDateHeader = 'Belum ada data';
            $manpowerToday = 0;
            $manhoursToday = 0.0;
        }

        // 2. Hitung Manpower (Terbanyak) secara global dari seluruh data historis di DB (tidak mengikuti filtering)
        // Jika terdapat jumlah tertinggi yang sama pada beberapa tanggal, pilih tanggal terbaru secara konsisten (orderByDesc('work_day'))
        $globalDailyWorkers = Workforce::selectRaw('DATE(record_date) as work_day, SUM(worker_count) as total_workers')
            ->whereNotNull('record_date')
            ->groupBy(DB::raw('DATE(record_date)'))
            ->orderByDesc('total_workers')
            ->orderByDesc('work_day')
            ->first();

        $globalMaxWorkers = $globalDailyWorkers && (int) $globalDailyWorkers->total_workers > 0
            ? (int) $globalDailyWorkers->total_workers
            : 0;
        $globalMaxWorkersDate = $globalDailyWorkers && (int) $globalDailyWorkers->total_workers > 0
            ? Carbon::parse($globalDailyWorkers->work_day)->locale('id')->translatedFormat('d F Y')
            : '-';

        // 3. Hitung Man Hours (Kumulatif) secara global dari seluruh data historis di DB (tidak mengikuti filtering)
        $globalCumulativeManHours = round($cumulativeTotalManHours, 0);
        $stats['cumulative_man_hours'] = $globalCumulativeManHours;

        $globalMinDateRaw = Workforce::min('record_date');
        $globalMaxDateRaw = Workforce::max('record_date');
        $fallbackSliderDate = $latestActivityDateStr ?: Carbon::now('Asia/Jakarta')->format('Y-m-d');
        $domainStartDate = $globalMinDateRaw ? Carbon::parse($globalMinDateRaw)->format('Y-m-d') : $fallbackSliderDate;
        $domainEndDate = $globalMaxDateRaw ? Carbon::parse($globalMaxDateRaw)->format('Y-m-d') : $fallbackSliderDate;

        // Format label periode untuk keperluan badge grafik/rekap saat filter aktif
        $filteredPeriodLabel = '';
        if ($isFilterActive) {
            if ($startDate && $endDate) {
                $sObj = Carbon::parse($startDate)->locale('id');
                $eObj = Carbon::parse($endDate)->locale('id');
                if ($startDate === $endDate) {
                    $filteredPeriodLabel = $sObj->translatedFormat('d M Y');
                } elseif ($sObj->year === $eObj->year) {
                    $filteredPeriodLabel = $sObj->translatedFormat('d M') . ' – ' . $eObj->translatedFormat('d M Y');
                } else {
                    $filteredPeriodLabel = $sObj->translatedFormat('d M Y') . ' – ' . $eObj->translatedFormat('d M Y');
                }
            } elseif ($startDate) {
                $filteredPeriodLabel = Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
            } elseif ($endDate) {
                $filteredPeriodLabel = Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
            } elseif ($dateGroupedHistory->isNotEmpty()) {
                $sortedDates = $dateGroupedHistory->sortBy('date')->values();
                $sObj = Carbon::parse($sortedDates->first()['date'])->locale('id');
                $eObj = Carbon::parse($sortedDates->last()['date'])->locale('id');
                if ($sObj->isSameDay($eObj)) {
                    $filteredPeriodLabel = $sObj->translatedFormat('d M Y');
                } elseif ($sObj->year === $eObj->year) {
                    $filteredPeriodLabel = $sObj->translatedFormat('d M') . ' – ' . $eObj->translatedFormat('d M Y');
                } else {
                    $filteredPeriodLabel = $sObj->translatedFormat('d M Y') . ' – ' . $eObj->translatedFormat('d M Y');
                }
            } else {
                $filteredPeriodLabel = 'Semua Periode';
            }
        }

        // Perbarui stats agar menyediakan nilai statis untuk Tanggal Utama & 4 Highlight Cards
        $stats['is_filtered'] = $isFilterActive;
        $stats['is_date_filtered'] = $isDateFiltered;
        $stats['filtered_period_label'] = $filteredPeriodLabel;
        $stats['filter_start_date'] = $startDate;
        $stats['filter_end_date'] = $endDate;
        $stats['domain_start_date'] = $domainStartDate;
        $stats['domain_end_date'] = $domainEndDate;
        $stats['latest_activity_date'] = $latestActivityDateStr;
        $stats['manpower_today'] = $manpowerToday;
        $stats['manpower_max'] = $globalMaxWorkers;
        $stats['manpower_max_date'] = $globalMaxWorkersDate;
        $stats['manhours_today'] = round($manhoursToday, 0);
        $stats['manhours_cumulative'] = $globalCumulativeManHours;
        $stats['manhours_max'] = round($overallMaxManhours, 0);
        $stats['manhours_max_date'] = $overallMaxManhoursDate
            ? Carbon::parse($overallMaxManhoursDate)->locale('id')->translatedFormat('d F Y')
            : '-';
        $stats['latest_header_date'] = $latestActiveDateHeader;
        $stats['latest_day_name'] = $latestActiveDayName;
        $stats['latest_date_only'] = $latestActiveDateFormatted;

        // 7. Data Grafik Tren Man Hours & Manpower (mengikuti filter rentang tanggal terpilih)
        $sortedAscHistory = $dateGroupedHistory->sortBy('date')->values();

        if ($startDate && $endDate) {
            $firstD = Carbon::parse($startDate)->locale('id');
            $lastD = Carbon::parse($endDate)->locale('id');
            $countDays = $sortedAscHistory->count();
            if ($startDate === $endDate) {
                $chartPeriodBadge = $firstD->translatedFormat('d M Y') . " ({$countDays} Hari)";
            } elseif ($firstD->year === $lastD->year) {
                $chartPeriodBadge = $firstD->translatedFormat('d M') . ' – ' . $lastD->translatedFormat('d M Y') . " ({$countDays} Hari)";
            } else {
                $chartPeriodBadge = $firstD->translatedFormat('d M Y') . ' – ' . $lastD->translatedFormat('d M Y') . " ({$countDays} Hari)";
            }
        } elseif ($sortedAscHistory->isNotEmpty()) {
            $firstD = Carbon::parse($sortedAscHistory->first()['date'])->locale('id');
            $lastD = Carbon::parse($sortedAscHistory->last()['date'])->locale('id');
            $countDays = $sortedAscHistory->count();
            $chartPeriodBadge = $firstD->translatedFormat('d M Y') === $lastD->translatedFormat('d M Y')
                ? $lastD->translatedFormat('d M Y') . " (1 Hari)"
                : $firstD->translatedFormat('d M') . ' – ' . $lastD->translatedFormat('d M Y') . " ({$countDays} Hari)";
        } else {
            $chartPeriodBadge = 'Belum ada data (0 Hari)';
            $firstD = Carbon::parse($fallbackSliderDate)->locale('id');
            $lastD = Carbon::parse($fallbackSliderDate)->locale('id');
        }
        $stats['chart_period_badge'] = $chartPeriodBadge;

        // Buat 4 titik label untuk bilah slider di bawah grafik Tren Man Hours & Manpower agar mengikuti tanggal awal & akhir filter
        $sliderStartObj = $startDate
            ? Carbon::parse($startDate)->locale('id')
            : ($sortedAscHistory->isNotEmpty()
                ? Carbon::parse($sortedAscHistory->first()['date'])->locale('id')
                : Carbon::parse($fallbackSliderDate)->locale('id'));
        $sliderEndObj = $endDate
            ? Carbon::parse($endDate)->locale('id')
            : ($sortedAscHistory->isNotEmpty()
                ? Carbon::parse($sortedAscHistory->last()['date'])->locale('id')
                : Carbon::parse($fallbackSliderDate)->locale('id'));

        $totalDiffDays = max(0, (int) $sliderStartObj->diffInDays($sliderEndObj));
        if ($totalDiffDays === 0) {
            $sliderTicks = $sortedAscHistory->isEmpty() && !$startDate && !$endDate
                ? ['-', '-', '-', '-']
                : [
                    $sliderStartObj->translatedFormat('d M Y'),
                    $sliderStartObj->translatedFormat('d M Y'),
                    $sliderStartObj->translatedFormat('d M Y'),
                    $sliderStartObj->translatedFormat('d M Y'),
                ];
        } else {
            $t1 = (clone $sliderStartObj)->addDays((int) round($totalDiffDays / 3));
            $t2 = (clone $sliderStartObj)->addDays((int) round(($totalDiffDays * 2) / 3));
            $sliderTicks = [
                $sliderStartObj->translatedFormat('d M Y'),
                $t1->translatedFormat('d M Y'),
                $t2->translatedFormat('d M Y'),
                $sliderEndObj->translatedFormat('d M Y'),
            ];
        }
        $stats['slider_ticks'] = $sliderTicks;

        $trendChart = [
            'labels' => $sortedAscHistory->map(function ($r) {
                return Carbon::parse($r['date'])->locale('id')->translatedFormat('d M');
            })->values()->all(),
            'dates' => $sortedAscHistory->pluck('date')->values()->all(),
            'workers' => $sortedAscHistory->pluck('total_workers')->map(fn ($v) => (int) $v)->values()->all(),
            'manhours' => $sortedAscHistory->pluck('total_manhours')->map(fn ($v) => (float) $v)->values()->all(),
            'man_hours' => $sortedAscHistory->pluck('total_manhours')->map(fn ($v) => (float) $v)->values()->all(),
            'slider_ticks' => $sliderTicks,
        ];
        $comboChartData = $trendChart;

        // 8. Rekap Tenaga Kerja untuk Donut Chart & Tabel Samping Kanan
        // Tanpa filter: tampilkan data berdasarkan tanggal aktivitas terakhir ($latestActivityRow).
        // Saat filter aktif: akumulasikan jumlah pekerja dan total man hours per perusahaan dalam rentang tanggal terpilih.
        $todayBreakdown = [];
        $todayTotalWorkers = 0;
        $todayTotalManhours = 0.0;
        $latestActivityRow = $latestActivityDateStr
            ? $dateGroupedHistory->firstWhere('date', $latestActivityDateStr)
            : null;

        foreach ($activeCompanyList as $cName) {
            if (!empty($selectedShortUnits) && !in_array($cName, $selectedShortUnits, true)) {
                $wCount = 0;
                $mhCount = 0.0;
            } elseif (!$isFilterActive) {
                $wCount = $latestActivityRow ? (int) ($latestActivityRow['companies'][$cName]['workers'] ?? 0) : 0;
                $mhCount = $latestActivityRow ? (float) ($latestActivityRow['companies'][$cName]['manhours'] ?? 0.0) : 0.0;
            } else {
                $wCount = (int) $dateGroupedHistory->sum(function ($dayRow) use ($cName) {
                    return (int) ($dayRow['companies'][$cName]['workers'] ?? 0);
                });
                $mhCount = (float) $dateGroupedHistory->sum(function ($dayRow) use ($cName) {
                    return (float) ($dayRow['companies'][$cName]['manhours'] ?? 0.0);
                });
            }

            $todayTotalWorkers += $wCount;
            $todayTotalManhours += $mhCount;
            $todayBreakdown[] = [
                'unit' => $cName,
                'name' => $cName,
                'count' => $wCount,
                'workers' => $wCount,
                'manhours' => round($mhCount, 0),
                'percentage' => 0.0,
            ];
        }

        foreach ($todayBreakdown as &$row) {
            $row['percentage'] = $todayTotalWorkers > 0 ? round(($row['count'] / $todayTotalWorkers) * 100, 1) : 0.0;
        }
        unset($row);

        // Urutkan dari jumlah pekerja terbanyak, lalu jumlah manhours terbanyak
        usort($todayBreakdown, function ($a, $b) {
            if ($b['workers'] !== $a['workers']) {
                return $b['workers'] <=> $a['workers'];
            }
            return $b['manhours'] <=> $a['manhours'];
        });

        // 9. Rekapitulasi per Unit / Area Operasi (Kompatibilitas Penuh)
        $unitRecap = $this->buildUnitRecap($allOperationalUnits, $filteredManHours, $latestDateFormatted, $startDate, $endDate, $categoryFilter, $search);
        $categoryRecap = $this->buildCategoryRecap($filteredWorkforces, $filteredManHours, $latestDateFormatted);
        $dailyAccumulation = $this->buildDailyAccumulation($filteredManHours);
        $unitExpansion = $this->detectUnitExpansion($allOperationalUnits, $filteredManHours);

        $chartData = [
            'categories' => collect($unitRecap['rows'])->pluck('unit_name')->values()->all(),
            'workers' => collect($unitRecap['rows'])->pluck('total_workers')->values()->all(),
            'man_hours' => collect($unitRecap['rows'])->pluck('period_man_hours')->values()->all(),
            'daily_dates' => collect($dailyAccumulation)->pluck('date_label')->reverse()->values()->all(),
            'daily_hours' => collect($dailyAccumulation)->pluck('daily_hours')->reverse()->values()->all(),
            'cumulative_hours' => collect($dailyAccumulation)->pluck('cumulative_hours')->reverse()->values()->all(),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $manHours = (clone $manHourQuery)
            ->orderBy('work_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Workforce::CATEGORIES;
        $units = $operationalUnits->pluck('name')->all();

        // Ambil struktur hierarki master DailyWorkCompany untuk modal Manajemen Perusahaan
        $hierarchicalCompanies = DailyWorkCompany::with('children')->ordered()->get()->map(function (DailyWorkCompany $comp) {
            $comp->display_name = self::toShortDisplayName($comp->name);
            return $comp;
        });
        $activeCompanies = DailyWorkCompany::where('is_active', true)
            ->where('name', 'not like', '%(All)%')
            ->ordered()
            ->get();
        $activeFilterCompanies = DailyWorkCompany::getActiveForFilter();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'formattedPeriodLabel' => $formattedPeriodLabel,
                'lastInputTimeFormatted' => $lastInputTimeFormatted,
                'latestActiveDateHeader' => $latestActiveDateHeader ?? null,
                'stats' => $stats,
                'unitRecap' => $unitRecap,
                'unit_expansion' => $unitExpansion,
                'categoryRecap' => $categoryRecap,
                'dailyAccumulation' => $dailyAccumulation,
                'chartData' => $chartData,
                'trendChart' => $trendChart,
                'comboChartData' => $comboChartData,
                'historyTableRows' => $historyTableRows,
                'dateGroupedHistory' => $dateGroupedHistory,
                'todayBreakdown' => $todayBreakdown,
                'todayTotalWorkers' => $todayTotalWorkers,
                'todayTotalManhours' => round($todayTotalManhours, 0),
                'selectedUnits' => $selectedUnits,
                'manHours' => $manHours,
                'hierarchicalCompanies' => $hierarchicalCompanies,
                'activeCompanies' => $activeCompanies,
                'activeFilterCompanies' => $activeFilterCompanies,
                'activeCompanyList' => $activeCompanyList,
            ]);
        }

        return view('data.man-hours.index', compact(
            'manHours',
            'stats',
            'unitRecap',
            'unitExpansion',
            'categoryRecap',
            'dailyAccumulation',
            'chartData',
            'trendChart',
            'comboChartData',
            'historyTableRows',
            'dateGroupedHistory',
            'todayBreakdown',
            'todayTotalWorkers',
            'todayTotalManhours',
            'latestActiveDateHeader',
            'selectedUnits',
            'referenceSidebarDate',
            'categories',
            'units',
            'operationalUnits',
            'allOperationalUnits',
            'hierarchicalCompanies',
            'activeCompanies',
            'activeFilterCompanies',
            'activeCompanyList',
            'periodType',
            'formattedPeriodLabel',
            'lastInputTimeFormatted',
            'startDate',
            'endDate',
            'unitFilter',
            'categoryFilter',
            'search'
        ));
    }

    /**
     * Ambil data tenaga kerja & jam kerja berdasarkan tanggal untuk Modal Input/Edit Man Hours & Manpower.
     */
    public function getByDate(Request $request)
    {
        OperationalUnit::ensureDefaultsSeeded();
        DailyWorkCompany::ensureRequired();

        $recordDate = $request->input('record_date', Carbon::today()->toDateString());
        $existingRecords = Workforce::with('manHour')->whereDate('record_date', $recordDate)->get();

        $activeCompanyList = self::getActiveCompanyNames();
        $unitsData = [];

        $allCompanyList = array_values(array_unique(array_merge($activeCompanyList, self::TARGET_COMPANIES)));
        $defaultOctDay = self::DEFAULT_OCTOBER_DAYS[$recordDate] ?? null;

        foreach ($allCompanyList as $compName) {
            if ($existingRecords->isNotEmpty()) {
                $matched = $existingRecords->filter(function ($w) use ($compName, $activeCompanyList) {
                    return $this->resolveTargetCompany($w->unit, $w->category, $activeCompanyList) === $compName
                        || strtoupper(trim($w->unit ?? '')) === strtoupper($compName)
                        || strtoupper(trim($w->category ?? '')) === strtoupper($compName);
                });

                $workerCount = (int) $matched->sum('worker_count');
                $totalMh = (float) $matched->sum(function ($w) {
                    if ($w->manHour && $w->manHour->total_hours !== null) {
                        return (float) $w->manHour->total_hours;
                    }
                    return (int) $w->worker_count * (float) ($w->hours_per_worker ?? 8);
                });
                $firstWf = $matched->first();
                $hoursPerWorker = (float) ($firstWf?->hours_per_worker ?? ($firstWf?->manHour?->hours_per_worker ?? 8.0));
                $calcMh = round($totalMh, 2);
            } elseif ($defaultOctDay && isset($defaultOctDay['units'][$compName])) {
                $workerCount = (int) $defaultOctDay['units'][$compName];
                $defW = (int) ($defaultOctDay['workers'] ?? 1);
                $defH = (float) ($defaultOctDay['hours'] ?? ($defW * 8));
                $avgH = $defW > 0 ? round($defH / $defW, 1) : 8.0;
                $hoursPerWorker = $avgH;
                $calcMh = isset($defaultOctDay['manhours'][$compName])
                    ? (float) $defaultOctDay['manhours'][$compName]
                    : round($workerCount * $hoursPerWorker, 2);
            } else {
                $workerCount = 0;
                $hoursPerWorker = 8.0;
                $calcMh = 0.0;
            }

            $unitsData[] = [
                'unit_name' => $compName,
                'category' => $compName,
                'worker_count' => $workerCount,
                'hours_per_worker' => $hoursPerWorker > 0 ? $hoursPerWorker : 8.0,
                'calculated_man_hours' => $calcMh,
            ];
        }

        return response()->json([
            'success' => true,
            'record_date' => $recordDate,
            'has_existing_data' => $existingRecords->isNotEmpty() || $defaultOctDay !== null,
            'units' => $unitsData,
        ]);
    }

    /**
     * Simpan input/edit batch Man Hours & Manpower per tanggal langsung dari halaman Man Hours & Manpower.
     */
    public function storeBatch(Request $request)
    {
        OperationalUnit::ensureDefaultsSeeded();

        if ($request->has('entries') && !$request->has('units')) {
            return app(WorkforceController::class)->storeBatch($request);
        }

        $validated = $request->validate([
            'record_date' => 'required|date',
            'units' => 'required|array|min:1',
            'units.*.unit' => 'required|string|max:100',
            'units.*.category' => 'nullable|string|max:100',
            'units.*.worker_count' => 'required|integer|min:0|max:100000',
            'units.*.hours_per_worker' => 'nullable|numeric|min:0|max:24',
            'units.*.subtotal_man_hours' => 'nullable|numeric|min:0|max:2400000',
            'units.*.notes' => 'nullable|string|max:500',
        ]);

        $recordDate = Carbon::parse($validated['record_date'])->toDateString();
        $savedRecords = [];
        $totalWorkers = 0;
        $totalManHours = 0.0;

        $hadExistingOnDate = Workforce::whereDate('record_date', $recordDate)->exists();

        DB::transaction(function () use ($validated, $recordDate, &$savedRecords, &$totalWorkers, &$totalManHours) {
            $submittedUpperNames = collect($validated['units'])
                ->map(fn ($u) => strtoupper(trim($u['unit'])))
                ->all();

            $existingRows = Workforce::whereDate('record_date', $recordDate)->get();
            foreach ($existingRows as $oldRow) {
                $oldUpperUnit = strtoupper(trim($oldRow->unit ?? ''));
                if (!in_array($oldUpperUnit, $submittedUpperNames, true)) {
                    ManHour::where('workforce_id', $oldRow->id)->delete();
                    $oldRow->delete();
                }
            }

            foreach ($validated['units'] as $item) {
                $unitName = trim($item['unit']);
                $category = trim($item['category'] ?? $unitName);
                $workerCount = (int) $item['worker_count'];
                $hoursPerWorker = isset($item['hours_per_worker']) && (float) $item['hours_per_worker'] > 0
                    ? (float) $item['hours_per_worker']
                    : 8.0;
                $customSubtotal = (isset($item['subtotal_man_hours']) && $item['subtotal_man_hours'] !== '' && $item['subtotal_man_hours'] !== null)
                    ? max(0, (float) $item['subtotal_man_hours'])
                    : null;

                $effectiveSubtotal = $customSubtotal !== null
                    ? round($customSubtotal, 2)
                    : round($workerCount * $hoursPerWorker, 2);

                $notes = $item['notes'] ?? null;

                $opUnit = OperationalUnit::whereRaw('UPPER(name) = ?', [strtoupper($unitName)])->first();
                if (!$opUnit) {
                    $opUnit = OperationalUnit::create([
                        'name' => $unitName,
                        'code' => 'UNIT-' . strtoupper(Str::random(4)),
                        'description' => 'Unit / Perusahaan ' . $unitName,
                        'default_hours' => $hoursPerWorker,
                        'is_active' => true,
                        'is_initial' => false,
                        'sort_order' => OperationalUnit::max('sort_order') + 1,
                        'created_by' => auth()->check() ? auth()->user()->name : 'Admin',
                    ]);
                }

                $existing = Workforce::whereDate('record_date', $recordDate)
                    ->whereRaw('UPPER(unit) = ?', [strtoupper($unitName)])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'operational_unit_id' => $opUnit->id,
                        'record_date' => $recordDate,
                        'unit' => $unitName,
                        'category' => $category,
                        'name' => $unitName,
                        'worker_count' => $workerCount,
                        'hours_per_worker' => $hoursPerWorker,
                        'status' => 'Aktif',
                        'notes' => $notes ?? $existing->notes,
                    ]);
                    $record = $existing->fresh();
                } else {
                    $record = Workforce::create([
                        'operational_unit_id' => $opUnit->id,
                        'record_date' => $recordDate,
                        'unit' => $unitName,
                        'category' => $category,
                        'name' => $unitName,
                        'employee_code' => 'WF-' . str_replace('-', '', $recordDate) . '-' . strtoupper(Str::random(6)),
                        'employment_type' => str_starts_with(strtoupper($unitName), 'GDE') ? 'Organik' : 'Kontraktor',
                        'join_date' => $recordDate,
                        'worker_count' => $workerCount,
                        'hours_per_worker' => $hoursPerWorker,
                        'status' => 'Aktif',
                        'notes' => $notes,
                    ]);
                }

                $mhRecord = $record->syncManHourRecord();
                if ($customSubtotal !== null && abs((float) $mhRecord->total_hours - $effectiveSubtotal) >= 0.01) {
                    $mhRecord->total_hours = $effectiveSubtotal;
                    $mhRecord->save();
                }
                $savedRecords[] = $record;
                $totalWorkers += $workerCount;
                $totalManHours += $effectiveSubtotal;
            }
        });

        ActivityLog::record([
            'action' => $hadExistingOnDate ? 'UBAH' : 'TAMBAH',
            'module' => 'Man Hours',
            'description' => ($hadExistingOnDate ? 'Memperbarui' : 'Menambahkan') . " data Man Hours & Manpower tanggal " . Carbon::parse($recordDate)->format('d/m/Y') . " (Total {$totalWorkers} pekerja, " . number_format($totalManHours, 0, ',', '.') . " jam kerja)",
            'changes' => [
                'record_date' => $recordDate,
                'total_workers' => $totalWorkers,
                'total_man_hours' => round($totalManHours, 2),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Data Man Hours & Manpower tanggal " . Carbon::parse($recordDate)->locale('id')->translatedFormat('d F Y') . " berhasil disimpan.",
            'record_date' => $recordDate,
            'total_workers' => $totalWorkers,
            'total_man_hours' => round($totalManHours, 2),
            'records' => $savedRecords,
        ]);
    }

    /**
     * Delegasi tambah perusahaan / unit operasional baru dari modal Kelola Perusahaan.
     */
    public function storeUnit(Request $request)
    {
        return app(WorkforceController::class)->storeUnit($request);
    }

    /**
     * Delegasi edit perusahaan / unit operasional dari modal Kelola Perusahaan.
     */
    public function updateUnit(Request $request, string $unitId)
    {
        return app(WorkforceController::class)->updateUnit($request, $unitId);
    }

    /**
     * Delegasi aktifkan/nonaktifkan perusahaan / unit operasional dari modal Kelola Perusahaan.
     */
    public function toggleUnit(Request $request, string $unitId)
    {
        return app(WorkforceController::class)->toggleUnit($request, $unitId);
    }

    /**
     * Hapus seluruh data Manhour & Workforce pada satu tanggal tertentu.
     */
    public function destroyDate(Request $request, string $date)
    {
        try {
            $parsedDate = Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Format tanggal tidak valid.',
            ], 422);
        }

        $workforces = Workforce::whereDate('record_date', $parsedDate)->get();
        $manHours = ManHour::whereDate('work_date', $parsedDate)->get();

        if ($workforces->isEmpty() && $manHours->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data Man Hours & Manpower pada tanggal tersebut tidak ditemukan.',
            ], 404);
        }

        $deletedCount = $workforces->count();
        $totalDeletedHours = (float) $manHours->sum('total_hours');

        DB::transaction(function () use ($parsedDate) {
            ManHour::whereDate('work_date', $parsedDate)->delete();
            Workforce::whereDate('record_date', $parsedDate)->delete();
        });

        ActivityLog::record([
            'action' => 'HAPUS',
            'module' => 'Man Hours',
            'description' => "Menghapus seluruh data Man Hours & Manpower pada tanggal " . Carbon::parse($parsedDate)->format('d/m/Y') . " ({$deletedCount} unit/perusahaan, total {$totalDeletedHours} jam)",
            'changes' => [
                'deleted_date' => $parsedDate,
                'deleted_units_count' => $deletedCount,
                'deleted_total_hours' => $totalDeletedHours,
            ],
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data Man Hours & Manpower tanggal ' . Carbon::parse($parsedDate)->locale('id')->translatedFormat('d F Y') . ' berhasil dihapus.',
            ]);
        }

        return redirect()->route('man-hours.index')
            ->with('success', 'Data Man Hours & Manpower tanggal ' . Carbon::parse($parsedDate)->locale('id')->translatedFormat('d F Y') . ' berhasil dihapus.');
    }

    /**
     * Tampilkan detail 1 record Man Hours beserta relasi Workforce-nya.
     */
    public function show(string $id)
    {
        $manHour = ManHour::with('workforce')->find($id);

        if (!$manHour) {
            if (request()->wantsJson()) {
                return response()->json(['message' => 'Data Man Hours tidak ditemukan.'], 404);
            }
            return redirect()->route('man-hours.index')->with('error', 'Data Man Hours tidak ditemukan.');
        }

        if (request()->wantsJson()) {
            return response()->json(['data' => $manHour]);
        }

        return view('data.man-hours.show', compact('manHour'));
    }

    /**
     * Sesuaikan parameter jam kerja per orang (hours_per_worker) atau catatan pada Man Hours.
     */
    public function update(Request $request, string $id)
    {
        $manHour = ManHour::with('workforce')->find($id);

        if (!$manHour) {
            return response()->json(['message' => 'Data Man Hours tidak ditemukan.'], 404);
        }

        $oldValues = $manHour->only([
            'unit',
            'category',
            'work_date',
            'number_of_workers',
            'hours_per_worker',
            'total_hours',
            'notes',
        ]);

        $validated = $request->validate([
            'hours_per_worker' => 'required|numeric|min:0.5|max:24',
            'notes' => 'nullable|string|max:1000',
        ], [
            'hours_per_worker.required' => 'Jam kerja per orang wajib diisi.',
            'hours_per_worker.min' => 'Jam kerja minimal 0.5 jam.',
            'hours_per_worker.max' => 'Jam kerja maksimal 24 jam per hari.',
        ]);

        $hoursPerWorker = (float) $validated['hours_per_worker'];
        $workerCount = $manHour->workforce
            ? (int) $manHour->workforce->worker_count
            : (int) $manHour->number_of_workers;
        $totalHours = round($workerCount * $hoursPerWorker, 2);

        $manHour->update([
            'number_of_workers' => $workerCount,
            'hours_per_worker' => $hoursPerWorker,
            'total_hours' => $totalHours,
            'notes' => $validated['notes'] ?? $manHour->notes,
        ]);

        if ($manHour->workforce) {
            $manHour->workforce->updateQuietly([
                'hours_per_worker' => $hoursPerWorker,
                'notes' => $validated['notes'] ?? $manHour->workforce->notes,
            ]);
        }

        $newValues = $manHour->fresh()->only([
            'unit',
            'category',
            'work_date',
            'number_of_workers',
            'hours_per_worker',
            'total_hours',
            'notes',
        ]);

        ActivityLog::record([
            'action' => 'UBAH',
            'module' => 'Man Hours',
            'record_id' => $manHour->id,
            'description' => "Menyesuaikan jam kerja unit {$manHour->unit} tanggal " . ($manHour->work_date ? $manHour->work_date->format('d/m/Y') : '-') . " menjadi {$hoursPerWorker} jam/orang (Total: {$totalHours} jam)",
            'changes' => [
                'before' => $oldValues,
                'after' => $newValues,
            ],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Parameter jam kerja berhasil diperbarui dan Man Hours telah dihitung ulang.',
                'data' => $manHour->fresh('workforce'),
            ]);
        }

        return redirect()->route('man-hours.index')
            ->with('success', 'Parameter jam kerja berhasil diperbarui.');
    }

    /**
     * Edit atau Reset Total Akumulasi Man Hours per Unit / Area Operasi.
     */
    public function updateUnitAccumulatedHours(Request $request, string $unitId)
    {
        $unit = OperationalUnit::find($unitId);

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit / Area Operasi tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'accumulated_hours' => 'required|numeric|min:0|max:999999999',
            'notes' => 'nullable|string|max:500',
            'reset' => 'nullable|boolean',
        ], [
            'accumulated_hours.required' => 'Total Akumulasi Man Hours wajib diisi.',
            'accumulated_hours.numeric' => 'Total Akumulasi Man Hours harus berupa angka valid.',
            'accumulated_hours.min' => 'Total Akumulasi Man Hours tidak boleh bernilai negatif.',
        ]);

        $rawSystemHours = (float) ManHour::where(function ($q) use ($unit) {
            $q->where('operational_unit_id', $unit->id)
                ->orWhere('unit', $unit->name)
                ->orWhere('category', $unit->name);
        })->sum('total_hours');

        $previousEffectiveHours = $unit->manual_accumulated_hours !== null
            ? round($rawSystemHours + (float) $unit->accumulated_hours_adjustment, 2)
            : round($rawSystemHours, 2);

        $isReset = (bool) ($validated['reset'] ?? false);
        $userName = auth()->check() ? auth()->user()->name : 'Admin';
        $notes = trim((string) ($validated['notes'] ?? ''));

        if ($isReset) {
            $newAccumulatedHours = round($rawSystemHours, 2);
            $adjustmentDelta = 0.0;

            $unit->update([
                'manual_accumulated_hours' => null,
                'accumulated_hours_adjustment' => 0.00,
                'accumulated_hours_baseline' => 0.00,
                'adjustment_notes' => null,
                'last_adjusted_at' => now(),
                'adjusted_by' => $userName,
            ]);

            $actionLabel = 'Reset ke Perhitungan Otomatis Sistem';
        } else {
            $newAccumulatedHours = round((float) $validated['accumulated_hours'], 2);
            $adjustmentDelta = round($newAccumulatedHours - $rawSystemHours, 2);

            $unit->update([
                'manual_accumulated_hours' => $newAccumulatedHours,
                'accumulated_hours_adjustment' => $adjustmentDelta,
                'accumulated_hours_baseline' => round($rawSystemHours, 2),
                'adjustment_notes' => $notes ?: 'Koreksi manual Total Akumulasi Man Hours',
                'last_adjusted_at' => now(),
                'adjusted_by' => $userName,
            ]);

            $actionLabel = 'Koreksi Manual Akumulasi Man Hours';
        }

        UnitManHourAdjustment::create([
            'operational_unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'previous_accumulated_hours' => $previousEffectiveHours,
            'system_calculated_hours' => round($rawSystemHours, 2),
            'new_accumulated_hours' => $newAccumulatedHours,
            'adjustment_delta' => $adjustmentDelta,
            'notes' => $notes ?: $actionLabel,
            'updated_by' => $userName,
        ]);

        ActivityLog::record([
            'action' => 'UBAH',
            'module' => 'Man Hours',
            'record_id' => $unit->id,
            'description' => "{$actionLabel} pada unit {$unit->name}: dari " . number_format($previousEffectiveHours, 0, ',', '.') . " Jam menjadi " . number_format($newAccumulatedHours, 0, ',', '.') . " Jam",
            'changes' => [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'previous_accumulated_hours' => $previousEffectiveHours,
                'system_calculated_hours' => round($rawSystemHours, 2),
                'new_accumulated_hours' => $newAccumulatedHours,
                'adjustment_delta' => $adjustmentDelta,
                'is_reset' => $isReset,
                'notes' => $notes,
            ],
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $isReset
                    ? "Total Akumulasi Man Hours unit {$unit->name} berhasil dikembalikan ke perhitungan otomatis (" . number_format($newAccumulatedHours, 0, ',', '.') . " Jam)."
                    : "Total Akumulasi Man Hours unit {$unit->name} berhasil diperbarui menjadi " . number_format($newAccumulatedHours, 0, ',', '.') . " Jam.",
                'unit' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'accumulated_hours' => $newAccumulatedHours,
                    'raw_system_hours' => round($rawSystemHours, 2),
                    'adjustment_delta' => $adjustmentDelta,
                    'has_manual_adjustment' => !$isReset && abs($adjustmentDelta) >= 0.01,
                    'notes' => $unit->adjustment_notes,
                    'last_adjusted_at' => $unit->last_adjusted_at ? $unit->last_adjusted_at->format('d/m/Y H:i') : null,
                    'adjusted_by' => $userName,
                ],
            ]);
        }

        return redirect()->route('man-hours.index')
            ->with('success', "Total Akumulasi Man Hours unit {$unit->name} berhasil diperbarui.");
    }

    /**
     * Ekspor data Man Hours & Manpower ke PDF.
     */
    public function export(Request $request)
    {
        $periodInfo = $this->resolvePeriodFilter($request);
        $startDate = $periodInfo['start_date'];
        $endDate = $periodInfo['end_date'];

        $unitFilter = $request->input('unit', 'all');
        $selectedUnits = [];
        if (is_array($unitFilter)) {
            $selectedUnits = array_values(array_filter(array_map('trim', $unitFilter)));
            if (empty($selectedUnits) || in_array('all', array_map('strtolower', $selectedUnits), true) || in_array('semua', array_map('strtolower', $selectedUnits), true)) {
                $unitFilter = 'all';
                $selectedUnits = [];
            }
        } elseif (in_array(strtolower(trim((string) $unitFilter)), ['all', 'semua', ''], true)) {
            $unitFilter = 'all';
            $selectedUnits = [];
        } else {
            $parts = array_filter(array_map('trim', explode(',', (string) $unitFilter)));
            if (count($parts) > 1) {
                $selectedUnits = $parts;
            } elseif (count($parts) === 1 && !in_array(strtolower($parts[0]), ['all', 'semua'], true)) {
                $selectedUnits = [$parts[0]];
            } else {
                $unitFilter = 'all';
                $selectedUnits = [];
            }
        }
        $categoryFilter = $request->input('category', 'all');

        $workforceQuery = Workforce::query()->orderBy('record_date', 'desc')->orderBy('id', 'desc');

        if ($startDate) {
            $workforceQuery->whereDate('record_date', '>=', $startDate);
        }
        if ($endDate) {
            $workforceQuery->whereDate('record_date', '<=', $endDate);
        }
        if ($unitFilter !== 'all') {
            $workforceQuery->where(function ($q) use ($selectedUnits, $unitFilter) {
                if (!empty($selectedUnits)) {
                    $q->whereIn('unit', $selectedUnits)->orWhereIn('category', $selectedUnits);
                } else {
                    $q->where('unit', $unitFilter)->orWhere('category', $unitFilter);
                }
            });
        }
        if ($categoryFilter !== 'all') {
            $workforceQuery->where('category', $categoryFilter);
        }

        $workforces = $workforceQuery->get();
        $activeCompanyList = self::getActiveCompanyNames();

        $groupedByDate = $workforces->groupBy(function ($item) {
            return $item->record_date ? $item->record_date->format('Y-m-d') : 'Unknown';
        });

        $allDateKeys = $groupedByDate->keys()->filter(fn ($k) => $k !== 'Unknown')->all();
        $includeDefaultOctober = (!$startDate || $startDate <= '2026-10-31') && (!$endDate || $endDate >= '2026-10-01');
        if ($includeDefaultOctober) {
            foreach (array_keys(self::DEFAULT_OCTOBER_DAYS) as $octDate) {
                if ($startDate && $octDate < $startDate) continue;
                if ($endDate && $octDate > $endDate) continue;
                if (!in_array($octDate, $allDateKeys, true)) {
                    $allDateKeys[] = $octDate;
                }
            }
        }
        rsort($allDateKeys);

        $pdfRows = [];
        $totalWorkersAll = 0;
        $totalManHoursAll = 0.0;

        foreach ($allDateKeys as $dateKey) {
            $hasDb = $groupedByDate->has($dateKey) && $groupedByDate->get($dateKey)->isNotEmpty();
            $unitsSimple = [];
            $dateTotalWorkers = 0;
            $dateTotalManhours = 0.0;

            if ($hasDb) {
                $wList = $groupedByDate->get($dateKey);
                foreach ($activeCompanyList as $cName) {
                    $matchedRows = $wList->filter(function ($w) use ($cName, $activeCompanyList) {
                        return $this->resolveTargetCompany($w->unit, $w->category, $activeCompanyList) === $cName;
                    });
                    $wSum = (int) $matchedRows->sum('worker_count');
                    $mhSum = (float) $matchedRows->sum(function ($w) {
                        if ($w->manHour && $w->manHour->total_hours !== null) {
                            return (float) $w->manHour->total_hours;
                        }
                        return $w->calculated_man_hours !== null
                            ? (float) $w->calculated_man_hours
                            : ((int) $w->worker_count * (float) ($w->hours_per_worker ?? 8));
                    });
                    $unitsSimple[$cName] = $wSum;
                    $dateTotalWorkers += $wSum;
                    $dateTotalManhours += $mhSum;
                }
            } elseif (isset(self::DEFAULT_OCTOBER_DAYS[$dateKey])) {
                $defDay = self::DEFAULT_OCTOBER_DAYS[$dateKey];
                foreach ($activeCompanyList as $cName) {
                    $unitsSimple[$cName] = (int) ($defDay['units'][$cName] ?? 0);
                }
                $dateTotalWorkers = (int) $defDay['workers'];
                $dateTotalManhours = (float) $defDay['hours'];
            }

            $avgH = $dateTotalWorkers > 0 ? round($dateTotalManhours / $dateTotalWorkers, 1) : 8.0;

            $pdfRows[] = [
                'date' => $dateKey,
                'date_formatted' => Carbon::parse($dateKey)->format('d/m/Y'),
                'units' => $unitsSimple,
                'total_workers' => $dateTotalWorkers,
                'hours_per_worker' => $avgH,
                'total_man_hours' => round($dateTotalManhours, 2),
            ];

            $totalWorkersAll += $dateTotalWorkers;
            $totalManHoursAll += $dateTotalManhours;
        }

        $pdf = Pdf::loadView('exports.man-hours-pdf', [
            'reportTitle' => 'LAPORAN RIWAYAT MAN HOURS & MANPOWER',
            'moduleName' => 'Man Hours & Manpower',
            'companyHeaders' => $activeCompanyList,
            'historyRows' => $pdfRows,
            'records' => $workforces,
            'totalWorkers' => $totalWorkersAll,
            'totalManHours' => $totalManHoursAll,
            'periodLabel' => $periodInfo['label'],
            'unitFilter' => !empty($selectedUnits) ? implode(', ', $selectedUnits) : ($unitFilter === 'all' ? 'Semua Perusahaan' : $unitFilter),
            'userName' => auth()->check() ? auth()->user()->name : 'Administrator',
            'generatedAt' => now()->locale('id')->translatedFormat('d F Y, H:i:s'),
        ])->setPaper('a4', 'landscape');

        ActivityLog::record([
            'action' => 'EXPORT',
            'module' => 'Man Hours',
            'description' => 'Mengekspor laporan PDF data Man Hours & Manpower (' . count($pdfRows) . ' tanggal pencatatan)',
        ]);

        return $pdf->download('Laporan-Man-Hours-Manpower-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * Ekspor data Man Hours & Manpower ke Excel (.csv kompatibel penuh dengan Microsoft Excel).
     */
    public function exportExcel(Request $request)
    {
        $periodInfo = $this->resolvePeriodFilter($request);
        $startDate = $periodInfo['start_date'];
        $endDate = $periodInfo['end_date'];
        $unitFilter = $request->input('unit', 'all');

        $query = Workforce::query()->orderBy('record_date', 'desc')->orderBy('id', 'desc');

        if ($startDate) {
            $query->whereDate('record_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('record_date', '<=', $endDate);
        }
        if ($unitFilter !== 'all' && !is_array($unitFilter)) {
            $query->where(function ($q) use ($unitFilter) {
                $q->where('unit', $unitFilter)->orWhere('category', $unitFilter);
            });
        }

        $workforces = $query->get();
        $activeCompanyList = self::getActiveCompanyNames();

        $groupedByDate = $workforces->groupBy(function ($item) {
            return $item->record_date ? $item->record_date->format('Y-m-d') : 'Unknown';
        });

        $filename = 'Data-Man-Hours-Manpower-HSSE-' . now()->format('Ymd-His') . '.csv';

        ActivityLog::record([
            'action' => 'EXPORT',
            'module' => 'Man Hours',
            'description' => 'Mengekspor data Excel/CSV Man Hours & Manpower (' . $groupedByDate->count() . ' tanggal)',
        ]);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($groupedByDate, $periodInfo, $activeCompanyList) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['LAPORAN REKAPITULASI MAN HOURS & MANPOWER - PT GEO DIPA ENERGI (PERSERO)']);
            fputcsv($file, ['Periode Data:', $periodInfo['label']]);
            fputcsv($file, ['Waktu Ekspor:', now()->locale('id')->translatedFormat('d F Y H:i') . ' WIB']);
            fputcsv($file, []);

            $headerRow = ['No', 'Tanggal'];
            foreach ($activeCompanyList as $comp) {
                $headerRow[] = "{$comp} - Tenaga Kerja";
                $headerRow[] = "{$comp} - Man Hours (Jam)";
            }
            $headerRow[] = 'Total Tenaga Kerja';
            $headerRow[] = 'Total Man Hours (Jam)';
            fputcsv($file, $headerRow);

            $no = 1;
            $grandWorkers = 0;
            $grandManhours = 0.0;

            foreach ($groupedByDate as $dateKey => $wList) {
                if ($dateKey === 'Unknown') {
                    continue;
                }

                $row = [
                    $no++,
                    Carbon::parse($dateKey)->locale('id')->translatedFormat('d F Y'),
                ];

                $dateW = 0;
                $dateMH = 0.0;

                foreach ($activeCompanyList as $cName) {
                    $matched = $wList->filter(function ($w) use ($cName, $activeCompanyList) {
                        return $this->resolveTargetCompany($w->unit, $w->category, $activeCompanyList) === $cName;
                    });
                    $wSum = (int) $matched->sum('worker_count');
                    $mhSum = (float) $matched->sum(function ($w) {
                        return (int) $w->worker_count * (float) ($w->hours_per_worker ?? 8);
                    });

                    $row[] = $wSum;
                    $row[] = round($mhSum, 2);

                    $dateW += $wSum;
                    $dateMH += $mhSum;
                }

                $row[] = $dateW;
                $row[] = round($dateMH, 2);

                $grandWorkers += $dateW;
                $grandManhours += $dateMH;

                fputcsv($file, $row);
            }

            fputcsv($file, []);
            $footerRow = ['', 'TOTAL KESELURUHAN'];
            foreach ($activeCompanyList as $comp) {
                $footerRow[] = '';
                $footerRow[] = '';
            }
            $footerRow[] = $grandWorkers;
            $footerRow[] = round($grandManhours, 2);
            fputcsv($file, $footerRow);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Resolusi Filter Periode Dinamis (all, daily, weekly, monthly, yearly, custom).
     */
    private function resolvePeriodFilter(Request $request): array
    {
        $defaultPeriod = ($request->filled('start_date') || $request->filled('end_date')) ? 'custom' : 'all';
        $periodType = strtolower($request->input('period_type', $defaultPeriod));
        $startDate = null;
        $endDate = null;
        $label = 'Semua Periode';

        try {
            switch ($periodType) {
                case 'daily':
                    $dailyDate = $request->input('filter_date')
                        ?: ($request->input('daily_date')
                        ?: ($request->input('start_date')
                        ?: ($request->input('end_date', date('Y-m-d')))));
                    $parsed = Carbon::parse($dailyDate);
                    $startDate = $parsed->toDateString();
                    $endDate = $parsed->toDateString();
                    $label = $parsed->locale('id')->translatedFormat('d F Y');
                    break;

                case 'weekly':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $sDt = Carbon::parse($request->input('start_date'));
                        $eDt = Carbon::parse($request->input('end_date'));
                        $startDate = $sDt->toDateString();
                        $endDate = $eDt->toDateString();
                        $label = 'Minggu ke-' . $sDt->isoWeek . ' (' . $sDt->locale('id')->translatedFormat('d M') . ' - ' . $eDt->locale('id')->translatedFormat('d M Y') . ')';
                    } else {
                        $weekInput = $request->input('filter_week_date') ?: $request->input('weekly_Input', date('Y-m-d'));
                        if (preg_match('/^(\d{4})-W(\d{2})$/', (string) $weekInput, $m)) {
                            $dt = Carbon::now()->setISODate((int) $m[1], (int) $m[2]);
                        } else {
                            $dt = Carbon::parse($weekInput);
                        }
                        $startDate = $dt->copy()->startOfWeek()->toDateString();
                        $endDate = $dt->copy()->endOfWeek()->toDateString();
                        $label = 'Minggu ke-' . $dt->isoWeek . ' (' . $dt->copy()->startOfWeek()->locale('id')->translatedFormat('d M') . ' - ' . $dt->copy()->endOfWeek()->locale('id')->translatedFormat('d M Y') . ')';
                    }
                    break;

                case 'monthly':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $sDt = Carbon::parse($request->input('start_date'));
                        $eDt = Carbon::parse($request->input('end_date'));
                        $startDate = $sDt->toDateString();
                        $endDate = $eDt->toDateString();
                        $label = $sDt->locale('id')->translatedFormat('F Y');
                    } else {
                        $monthInput = $request->input('filter_month') ?: $request->input('monthly_input', date('Y-m'));
                        $dt = Carbon::parse($monthInput . '-01');
                        $startDate = $dt->copy()->startOfMonth()->toDateString();
                        $endDate = $dt->copy()->endOfMonth()->toDateString();
                        $label = $dt->locale('id')->translatedFormat('F Y');
                    }
                    break;

                case 'yearly':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $sDt = Carbon::parse($request->input('start_date'));
                        $eDt = Carbon::parse($request->input('end_date'));
                        $startDate = $sDt->toDateString();
                        $endDate = $eDt->toDateString();
                        $label = "Tahun {$sDt->year}";
                    } else {
                        $yearInput = (int) ($request->input('filter_year') ?: $request->input('yearly_input', date('Y')));
                        $startDate = "{$yearInput}-01-01";
                        $endDate = "{$yearInput}-12-31";
                        $label = "Tahun {$yearInput}";
                    }
                    break;

                case 'custom':
                    $sInput = $request->input('start_date');
                    $eInput = $request->input('end_date');
                    if ($sInput && $eInput) {
                        $sDt = Carbon::parse($sInput);
                        $eDt = Carbon::parse($eInput);
                        $startDate = $sDt->toDateString();
                        $endDate = $eDt->toDateString();
                        $label = $sDt->locale('id')->translatedFormat('d M Y') . ' — ' . $eDt->locale('id')->translatedFormat('d M Y');
                    } elseif ($sInput) {
                        $sDt = Carbon::parse($sInput);
                        $startDate = $sDt->toDateString();
                        $label = 'Sejak ' . $sDt->locale('id')->translatedFormat('d M Y');
                    } elseif ($eInput) {
                        $eDt = Carbon::parse($eInput);
                        $endDate = $eDt->toDateString();
                        $label = 'Sampai ' . $eDt->locale('id')->translatedFormat('d M Y');
                    }
                    break;

                case 'all':
                default:
                    $periodType = 'all';
                    $minDate = Workforce::min('record_date');
                    $maxDate = Workforce::max('record_date');
                    if ($minDate && $maxDate) {
                        $minFormatted = Carbon::parse($minDate)->locale('id')->translatedFormat('d M Y');
                        $maxFormatted = Carbon::parse($maxDate)->locale('id')->translatedFormat('d M Y');
                        $label = $minFormatted === $maxFormatted
                            ? $maxFormatted
                            : "{$minFormatted} — {$maxFormatted}";
                    } else {
                        $label = 'Semua Periode';
                    }
                    break;
            }
        } catch (\Throwable $e) {
            $periodType = 'all';
            $startDate = null;
            $endDate = null;
            $label = 'Semua Periode';
        }

        return [
            'period_type' => $periodType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label,
        ];
    }

    /**
     * Susun rekap per Unit / Area Operasi dari Master Unit + historis data.
     */
    private function buildUnitRecap($allOperationalUnits, $filteredManHours, ?string $latestDateFormatted, ?string $startDate = null, ?string $endDate = null, string $categoryFilter = 'all', string $search = ''): array
    {
        $rows = [];
        $grandWorkers = 0;
        $grandPeriodManHours = 0.0;
        $grandLatestWorkers = 0;
        $grandLatestManHours = 0.0;

        $knownUnitNames = $allOperationalUnits->pluck('name')->all();
        $isUnfilteredScope = (!$startDate && !$endDate && $categoryFilter === 'all' && $search === '');

        foreach ($allOperationalUnits as $opUnit) {
            $uName = $opUnit->name;

            $unitMh = $filteredManHours->filter(function ($m) use ($opUnit, $uName) {
                return ($m->operational_unit_id && (int) $m->operational_unit_id === (int) $opUnit->id)
                    || $m->unit === $uName
                    || $m->category === $uName;
            });

            if (!$opUnit->is_active && $unitMh->isEmpty() && $opUnit->manual_accumulated_hours === null) {
                continue;
            }

            $totalWorkers = (int) $unitMh->sum('number_of_workers');
            $rawPeriodManHours = (float) $unitMh->sum('total_hours');

            $hasManualAdjustment = $opUnit->manual_accumulated_hours !== null && abs((float) $opUnit->accumulated_hours_adjustment) >= 0.01;
            $adjustmentDelta = $hasManualAdjustment ? (float) $opUnit->accumulated_hours_adjustment : 0.0;

            $effectivePeriodManHours = $isUnfilteredScope && $hasManualAdjustment
                ? round($rawPeriodManHours + $adjustmentDelta, 2)
                : round($rawPeriodManHours, 2);

            $allTimeRawForUnit = (float) ManHour::where(function ($q) use ($opUnit, $uName) {
                $q->where('operational_unit_id', $opUnit->id)
                    ->orWhere('unit', $uName)
                    ->orWhere('category', $uName);
            })->sum('total_hours');
            $allTimeEffectiveForUnit = round($allTimeRawForUnit + $adjustmentDelta, 2);

            $latestWorkers = 0;
            $latestHoursPerWorker = (float) ($opUnit->default_hours ?: 8.0);
            $latestManHours = 0.0;

            if ($latestDateFormatted) {
                $latestRows = $unitMh->filter(fn ($m) => $m->work_date && $m->work_date->format('Y-m-d') === $latestDateFormatted);
                if ($latestRows->isNotEmpty()) {
                    $latestWorkers = (int) $latestRows->sum('number_of_workers');
                    $latestManHours = (float) $latestRows->sum('total_hours');
                    $latestHoursPerWorker = $latestWorkers > 0
                        ? round($latestManHours / $latestWorkers, 2)
                        : (float) ($latestRows->first()->hours_per_worker ?: 8.0);
                }
            }

            $rows[] = [
                'unit_id' => $opUnit->id,
                'unit_name' => $uName,
                'code' => $opUnit->code,
                'description' => $opUnit->description,
                'is_active' => (bool) $opUnit->is_active,
                'is_initial' => (bool) $opUnit->is_initial,
                'total_workers' => $totalWorkers,
                'raw_period_man_hours' => round($rawPeriodManHours, 2),
                'period_man_hours' => $effectivePeriodManHours,
                'all_time_man_hours' => $allTimeEffectiveForUnit,
                'all_time_raw_hours' => round($allTimeRawForUnit, 2),
                'has_manual_adjustment' => $hasManualAdjustment,
                'adjustment_delta' => round($adjustmentDelta, 2),
                'adjustment_notes' => $opUnit->adjustment_notes,
                'last_adjusted_at' => $opUnit->last_adjusted_at ? $opUnit->last_adjusted_at->format('d/m/Y H:i') : null,
                'adjusted_by' => $opUnit->adjusted_by,
                'latest_workers' => $latestWorkers,
                'latest_hours_per_worker' => $latestHoursPerWorker,
                'latest_man_hours' => round($latestManHours, 2),
                'records_count' => $unitMh->count(),
            ];

            $grandWorkers += $totalWorkers;
            $grandPeriodManHours += $effectivePeriodManHours;
            $grandLatestWorkers += $latestWorkers;
            $grandLatestManHours += $latestManHours;
        }

        $unmatchedMh = $filteredManHours->filter(function ($m) use ($knownUnitNames) {
            $u = $m->unit ?: $m->category;
            return !in_array($u, $knownUnitNames, true);
        });

        if ($unmatchedMh->isNotEmpty()) {
            foreach ($unmatchedMh->groupBy(fn ($m) => $m->unit ?: $m->category) as $legacyName => $legacyRows) {
                $wSum = (int) $legacyRows->sum('number_of_workers');
                $hSum = (float) $legacyRows->sum('total_hours');

                $lWorkers = 0;
                $lHours = 0.0;
                if ($latestDateFormatted) {
                    $lRows = $legacyRows->filter(fn ($m) => $m->work_date && $m->work_date->format('Y-m-d') === $latestDateFormatted);
                    $lWorkers = (int) $lRows->sum('number_of_workers');
                    $lHours = (float) $lRows->sum('total_hours');
                }

                $rows[] = [
                    'unit_id' => null,
                    'unit_name' => $legacyName,
                    'code' => 'LEGACY',
                    'description' => 'Unit Historis',
                    'is_active' => true,
                    'is_initial' => false,
                    'total_workers' => $wSum,
                    'raw_period_man_hours' => round($hSum, 2),
                    'period_man_hours' => round($hSum, 2),
                    'all_time_man_hours' => round($hSum, 2),
                    'all_time_raw_hours' => round($hSum, 2),
                    'has_manual_adjustment' => false,
                    'adjustment_delta' => 0.0,
                    'adjustment_notes' => null,
                    'last_adjusted_at' => null,
                    'adjusted_by' => null,
                    'latest_workers' => $lWorkers,
                    'latest_hours_per_worker' => 8.0,
                    'latest_man_hours' => round($lHours, 2),
                    'records_count' => $legacyRows->count(),
                ];

                $grandWorkers += $wSum;
                $grandPeriodManHours += $hSum;
                $grandLatestWorkers += $lWorkers;
                $grandLatestManHours += $lHours;
            }
        }

        foreach ($rows as &$r) {
            $r['share_percentage'] = $grandPeriodManHours > 0
                ? round(($r['period_man_hours'] / $grandPeriodManHours) * 100, 1)
                : 0;
        }
        unset($r);

        return [
            'rows' => $rows,
            'grand_workers' => $grandWorkers,
            'grand_period_man_hours' => round($grandPeriodManHours, 2),
            'grand_latest_workers' => $grandLatestWorkers,
            'grand_latest_man_hours' => round($grandLatestManHours, 2),
        ];
    }

    /**
     * Deteksi apakah jumlah unit operasional aktif bertambah dibanding 6 unit standar awal.
     */
    private function detectUnitExpansion($allOperationalUnits, $filteredManHours): array
    {
        $initialUnits = $allOperationalUnits->where('is_initial', true);
        $addedUnits = $allOperationalUnits->where('is_initial', false);
        $activeAddedUnits = $addedUnits->where('is_active', true);

        $addedUnitsContribution = 0.0;
        $addedUnitsWorkers = 0;

        foreach ($addedUnits as $newUnit) {
            $matched = $filteredManHours->filter(function ($m) use ($newUnit) {
                return ($m->operational_unit_id && (int) $m->operational_unit_id === (int) $newUnit->id)
                    || $m->unit === $newUnit->name
                    || $m->category === $newUnit->name;
            });
            $addedUnitsContribution += (float) $matched->sum('total_hours');
            $addedUnitsWorkers += (int) $matched->sum('number_of_workers');
        }

        return [
            'initial_count' => $initialUnits->count() ?: 6,
            'current_active_count' => $allOperationalUnits->where('is_active', true)->count(),
            'total_registered_count' => $allOperationalUnits->count(),
            'new_active_count' => $activeAddedUnits->count(),
            'new_unit_names' => $activeAddedUnits->pluck('name')->values()->all(),
            'added_units_workers' => $addedUnitsWorkers,
            'added_units_man_hours' => round($addedUnitsContribution, 2),
            'has_new_units' => $addedUnits->isNotEmpty(),
        ];
    }

    /**
     * Kompatibilitas ke belakang untuk variabel $categoryRecap.
     */
    private function buildCategoryRecap($filteredWorkforces, $filteredManHours, ?string $latestDateFormatted): array
    {
        $recap = [];
        $grandWorkers = 0;
        $grandManHours = 0.0;
        $grandLatestWorkers = 0;
        $grandLatestManHours = 0.0;

        $unitNames = OperationalUnit::ordered()->pluck('name')->all();
        if (empty($unitNames)) {
            $unitNames = Workforce::CATEGORIES;
        }

        foreach ($unitNames as $cat) {
            $catWorkforces = $filteredWorkforces->filter(fn ($w) => ($w->unit ?: $w->category) === $cat);
            $catManHours = $filteredManHours->filter(fn ($m) => ($m->unit ?: $m->category) === $cat);

            $totalWorkers = (int) $catWorkforces->sum('worker_count');
            $totalHours = (float) $catManHours->sum('total_hours');

            $latestWorkers = 0;
            $latestHoursPerWorker = 8.0;
            $latestManHours = 0.0;

            if ($latestDateFormatted) {
                $latestCatRows = $catWorkforces->filter(function ($w) use ($latestDateFormatted) {
                    return $w->record_date && $w->record_date->format('Y-m-d') === $latestDateFormatted;
                });

                if ($latestCatRows->isNotEmpty()) {
                    $latestWorkers = (int) $latestCatRows->sum('worker_count');
                    $latestHoursPerWorker = (float) ($latestCatRows->first()->hours_per_worker ?: 8);
                    $latestManHours = (float) $latestCatRows->sum(function ($w) {
                        return (int) $w->worker_count * (float) ($w->hours_per_worker ?: 8);
                    });
                }
            }

            $recap[] = [
                'category' => $cat,
                'total_workers' => $totalWorkers,
                'total_man_hours' => round($totalHours, 2),
                'latest_workers' => $latestWorkers,
                'latest_hours_per_worker' => $latestHoursPerWorker,
                'latest_man_hours' => round($latestManHours, 2),
                'records_count' => $catManHours->count(),
            ];

            $grandWorkers += $totalWorkers;
            $grandManHours += $totalHours;
            $grandLatestWorkers += $latestWorkers;
            $grandLatestManHours += $latestManHours;
        }

        foreach ($recap as &$item) {
            $item['share_percentage'] = $grandManHours > 0
                ? round(($item['total_man_hours'] / $grandManHours) * 100, 1)
                : 0;
        }
        unset($item);

        return [
            'rows' => $recap,
            'grand_workers' => $grandWorkers,
            'grand_man_hours' => round($grandManHours, 2),
            'grand_latest_workers' => $grandLatestWorkers,
            'grand_latest_man_hours' => round($grandLatestManHours, 2),
        ];
    }

    /**
     * Susun tabel akumulasi harian beserta running total (kumulatif).
     */
    private function buildDailyAccumulation($filteredManHours): array
    {
        $groupedByDate = $filteredManHours
            ->groupBy(function ($m) {
                return $m->work_date ? $m->work_date->format('Y-m-d') : 'Unknown';
            })
            ->sortKeys();

        $runningTotal = 0.0;
        $dailyRows = [];

        foreach ($groupedByDate as $dateKey => $items) {
            if ($dateKey === 'Unknown') {
                continue;
            }

            $dailyWorkers = (int) $items->sum('number_of_workers');
            $dailyHours = (float) $items->sum('total_hours');
            $runningTotal += $dailyHours;

            $byCategory = [];
            foreach ($items->groupBy(fn ($i) => $i->unit ?: $i->category) as $uName => $uItems) {
                $byCategory[$uName] = [
                    'workers' => (int) $uItems->sum('number_of_workers'),
                    'hours' => round((float) $uItems->sum('total_hours'), 2),
                ];
            }

            $dailyRows[] = [
                'date' => $dateKey,
                'date_label' => Carbon::parse($dateKey)->locale('id')->translatedFormat('d M Y'),
                'daily_workers' => $dailyWorkers,
                'daily_hours' => round($dailyHours, 2),
                'cumulative_hours' => round($runningTotal, 2),
                'categories_count' => $items->pluck('unit')->filter()->unique()->count() ?: $items->pluck('category')->unique()->count(),
                'by_category' => $byCategory,
            ];
        }

        return array_reverse($dailyRows);
    }
}
