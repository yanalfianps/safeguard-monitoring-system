<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\ManHour;
use App\Models\MediaAttachment;
use App\Services\ImageUploadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IncidentController extends Controller
{
    /**
     * Halaman Kategori Umum Incident
     */
    public function umum(Request $request): View|JsonResponse
    {
        $request->merge(['tab' => 'umum']);
        return $this->index($request);
    }

    /**
     * Halaman Kategori EBTKE Incident
     */
    public function ebtke(Request $request): View|JsonResponse
    {
        $request->merge(['tab' => 'ebtke']);
        return $this->index($request);
    }

    /**
     * Tampilkan halaman utama Incident beserta metrik keselamatan, 3 Donut Charts interaktif,
     * filter multi-kriteria, dan tabel rekapitulasi insiden dengan identitas visual Merah (#dc2626).
     */
    public function index(Request $request): View|JsonResponse
    {
        $baseQuery = Incident::with('mediaAttachments');

        // 1. Filter Pencarian Teks (10+ kolom)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $baseQuery->where(function ($q) use ($search) {
                $q->where('nomor_insiden', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('incident_code', 'like', "%{$search}%")
                  ->orWhere('lokasi_kejadian', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('komponen_terkait', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$search}%")
                  ->orWhere('reported_by', 'like', "%{$search}%")
                  ->orWhere('deskripsi_kejadian', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('hari', 'like', "%{$search}%")
                  ->orWhere('kategori_umum', 'like', "%{$search}%")
                  ->orWhere('kategori_ebtke', 'like', "%{$search}%");
            });
        }

        // 2. Filter Klasifikasi Area
        if ($request->filled('klasifikasi_area')) {
            $baseQuery->where('klasifikasi_area', $request->input('klasifikasi_area'));
        }

        // 3. Filter Kategori Umum (JSON tag check + fallback, abaikan Hazard Report)
        if ($request->filled('kategori_umum') && $request->input('kategori_umum') !== 'Hazard Report') {
            $tagUmum = $request->input('kategori_umum');
            $baseQuery->where(function ($q) use ($tagUmum) {
                $q->whereJsonContains('kategori_umum', $tagUmum)
                  ->orWhere('kategori_umum', 'like', "%\"{$tagUmum}\"%");
            });
        }

        // 4. Filter Kategori EBTKE (JSON tag check + fallback)
        if ($request->filled('kategori_ebtke')) {
            $tagEbtke = $request->input('kategori_ebtke');
            $baseQuery->where(function ($q) use ($tagEbtke) {
                $q->whereJsonContains('kategori_ebtke', $tagEbtke)
                  ->orWhere('kategori_ebtke', 'like', "%\"{$tagEbtke}\"%");
            });
        }

        // 5. Filter Status Laporan (Preliminary / Final)
        if ($request->filled('status_laporan') && in_array($request->input('status_laporan'), ['Preliminary', 'Final'], true)) {
            $statusFilter = $request->input('status_laporan');
            if ($statusFilter === 'Final') {
                $baseQuery->where(function ($q) {
                    $q->whereIn('status_laporan', ['Final', 'Selesai'])
                      ->orWhere(function ($sub) {
                          $sub->whereNull('status_laporan')->where('status', 'Selesai');
                      });
                });
            } else {
                $baseQuery->where(function ($q) {
                    $q->whereIn('status_laporan', ['Preliminary', 'On Progress', 'Investigasi', 'Dilaporkan'])
                      ->orWhere(function ($sub) {
                          $sub->whereNull('status_laporan')->where('status', '!=', 'Selesai');
                      });
                });
            }
        }

        // 6. Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $baseQuery->whereBetween('tanggal_waktu', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        } elseif ($request->filled('start_date')) {
            $baseQuery->where('tanggal_waktu', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        } elseif ($request->filled('end_date')) {
            $baseQuery->where('tanggal_waktu', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }

        // A. Data Akumulasi Penuh untuk 3 Donut Charts, Metrik LWD & Drilldown Modal (Unpaginated)
        $chartIncidents = (clone $baseQuery)->orderBy('tanggal_waktu', 'desc')->get();

        // B. Data Tabel Insiden dengan Pagination 10 baris per halaman
        $incidents = (clone $baseQuery)->orderBy('tanggal_waktu', 'desc')->paginate(10)->withQueryString();

        // 1. Chart Area (Panas Bumi vs Non-Panas Bumi)
        $panasBumiCount = $chartIncidents->filter(function ($i) {
            $val = (string) ($i->klasifikasi_area ?? $i->work_area ?? '');
            return !str_contains($val, 'Non') && str_contains($val, 'Panas');
        })->count();

        $nonPanasBumiCount = $chartIncidents->filter(function ($i) {
            $val = (string) ($i->klasifikasi_area ?? $i->work_area ?? '');
            return str_contains($val, 'Non');
        })->count();

        $chartArea = [
            'labels' => ['Kecelakaan Panas Bumi', 'Kecelakaan Non-Panas Bumi'],
            'data' => [$panasBumiCount, $nonPanasBumiCount],
            'colors' => ['#ea580c', '#0284c7'], // Oranye Panas (#ea580c) & Biru Langit (#0284c7)
            'total' => $chartIncidents->count(),
        ];

        // 2. Chart Kategori Umum Tags - Palet Warna Kontras per Tag
        $kategoriUmumPalette = Incident::KATEGORI_UMUM_PALETTE;
        $extraUmumPalette = [
            '#06b6d4', // Cyan Pirus Muda
            '#ec4899', // Pink Fusia
            '#8b5cf6', // Lavender Purple
            '#0284c7', // Ocean Blue
            '#64748b', // Slate Elegan
        ];

        $umumFreq = [];
        $umumColors = [];
        $extraIdx = 0;
        foreach (Incident::KATEGORI_UMUM_LIST as $k) {
            $umumFreq[$k] = 0;
        }
        foreach ($chartIncidents as $inc) {
            $tags = is_array($inc->kategori_umum) ? $inc->kategori_umum : (json_decode($inc->kategori_umum, true) ?: []);
            foreach ($tags as $t) {
                if ($t === 'Hazard Report') {
                    continue;
                }
                if (isset($umumFreq[$t])) {
                    $umumFreq[$t]++;
                } else {
                    $umumFreq[$t] = 1;
                }
            }
        }
        foreach (array_keys($umumFreq) as $lbl) {
            if (isset($kategoriUmumPalette[$lbl])) {
                $umumColors[] = $kategoriUmumPalette[$lbl];
            } else {
                $umumColors[] = $extraUmumPalette[$extraIdx % count($extraUmumPalette)];
                $extraIdx++;
            }
        }
        $chartUmum = [
            'labels' => array_keys($umumFreq),
            'data' => array_values($umumFreq),
            'colors' => $umumColors,
            'color_map' => $kategoriUmumPalette,
            'total' => array_sum($umumFreq),
        ];

        // 3. Chart Kategori EBTKE Tags - 5 Tingkat Keparahan Standar ESDM
        $kategoriEbtkePalette = Incident::KATEGORI_EBTKE_PALETTE;

        $ebtkeFreq = [];
        $ebtkeColors = [];
        $extraEbtkeIdx = 0;
        foreach (Incident::KATEGORI_EBTKE_LIST as $k) {
            $ebtkeFreq[$k] = 0;
        }
        foreach ($chartIncidents as $inc) {
            $tags = is_array($inc->kategori_ebtke) ? $inc->kategori_ebtke : (json_decode($inc->kategori_ebtke, true) ?: []);
            foreach ($tags as $t) {
                if (isset($ebtkeFreq[$t])) {
                    $ebtkeFreq[$t]++;
                } else {
                    $ebtkeFreq[$t] = 1;
                }
            }
        }
        foreach (array_keys($ebtkeFreq) as $lbl) {
            if (isset($kategoriEbtkePalette[$lbl])) {
                $ebtkeColors[] = $kategoriEbtkePalette[$lbl];
            } else {
                $ebtkeColors[] = $extraUmumPalette[$extraEbtkeIdx % count($extraUmumPalette)];
                $extraEbtkeIdx++;
            }
        }
        $chartEbtke = [
            'labels' => array_keys($ebtkeFreq),
            'data' => array_values($ebtkeFreq),
            'colors' => $ebtkeColors,
            'color_map' => $kategoriEbtkePalette,
            'total' => array_sum($ebtkeFreq),
        ];

        // 4. Perhitungan Metrik & Distribusi Hari Kerja Hilang (LWD)
        $totalInsiden = $chartIncidents->count();
        $totalHariHilang = (int) $chartIncidents->sum(function ($i) {
            return (int) ($i->jumlah_hari_hilang ?? $i->lost_workdays ?? 0);
        });
        $kasusHariHilangCount = $chartIncidents->filter(function ($i) {
            return ((int) ($i->jumlah_hari_hilang ?? $i->lost_workdays ?? 0)) > 0;
        })->count();

        $chartHariHilang = [
            'labels' => ['Dengan Kehilangan Hari Kerja', 'Tanpa Kehilangan Hari Kerja'],
            'data' => [$kasusHariHilangCount, max(0, $totalInsiden - $kasusHariHilangCount)],
            'colors' => ['#dc2626', '#10b981'],
            'total' => $totalInsiden,
        ];

        // Perhitungan Total Jam Kerja (Man Hours) dari database ManHour
        if (method_exists(ManHour::class, 'syncAllFromWorkforce')) {
            ManHour::syncAllFromWorkforce();
        }
        $mhQuery = ManHour::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $mhQuery->whereBetween('work_date', [
                Carbon::parse($request->input('start_date'))->toDateString(),
                Carbon::parse($request->input('end_date'))->toDateString(),
            ]);
        } elseif ($request->filled('start_date')) {
            $mhQuery->where('work_date', '>=', Carbon::parse($request->input('start_date'))->toDateString());
        } elseif ($request->filled('end_date')) {
            $mhQuery->where('work_date', '<=', Carbon::parse($request->input('end_date'))->toDateString());
        }
        $filteredManHours = (float) $mhQuery->sum('total_hours');
        $allTimeManHours = (float) ManHour::sum('total_hours');
        $totalManHours = ($request->filled('start_date') || $request->filled('end_date')) ? $filteredManHours : $allTimeManHours;

        // Kriteria LTI (Lost Time Injury)
        $ltiCount = $chartIncidents->filter(function ($i) {
            $severity = strtolower((string) ($i->severity ?? ''));
            $days = (int) ($i->jumlah_hari_hilang ?? $i->lost_workdays ?? 0);
            $ku = is_array($i->kategori_umum) ? $i->kategori_umum : (json_decode($i->kategori_umum, true) ?: []);
            $ke = is_array($i->kategori_ebtke) ? $i->kategori_ebtke : (json_decode($i->kategori_ebtke, true) ?: []);
            return in_array($severity, ['fatality', 'berat'])
                || $days > 0
                || in_array('LTI (Lost Time Injury)', $ku)
                || in_array('Fatality', $ku)
                || in_array('Kecelakaan Mati', $ke)
                || in_array('Kecelakaan Berat', $ke);
        })->count();

        // 4 Indikator Keselamatan Standar ESDM Panas Bumi (Faktor Pengali 1.000.000)
        $multiplier = 1000000;
        $trir = ($totalManHours > 0) ? round(($totalInsiden * $multiplier) / $totalManHours, 2) : null;
        $sr = ($totalManHours > 0) ? round(($totalHariHilang * $multiplier) / $totalManHours, 2) : null;
        $fr = ($totalManHours > 0) ? round(($ltiCount * $multiplier) / $totalManHours, 2) : null;

        $safetyMetrics = [
            'lti' => $ltiCount,
            'trir' => $trir,
            'sr' => $sr,
            'fr' => $fr,
            'totalManHours' => $totalManHours,
            'totalHariHilang' => $totalHariHilang,
            'multiplier' => $multiplier,
        ];

        // 5. Tren Insiden dari Waktu ke Waktu (12 Bulan Terakhir secara kronologis)
        $trendMonths = [];
        $trendIncidentsData = [];
        $trendLtiData = [];
        $trendDaysData = [];

        $refEndMonth = $request->filled('end_date') ? Carbon::parse($request->input('end_date')) : now();
        for ($m = 11; $m >= 0; $m--) {
            $dt = (clone $refEndMonth)->subMonths($m);
            $mKey = $dt->format('Y-m');
            $trendMonths[] = $dt->isoFormat('MMM YYYY');

            $mIncidents = $chartIncidents->filter(function ($inc) use ($mKey) {
                $t = $inc->tanggal_waktu ? Carbon::parse($inc->tanggal_waktu) : ($inc->incident_date ? Carbon::parse($inc->incident_date) : null);
                return $t && $t->format('Y-m') === $mKey;
            });

            $trendIncidentsData[] = $mIncidents->count();

            $mLti = $mIncidents->filter(function ($i) {
                $severity = strtolower((string) ($i->severity ?? ''));
                $days = (int) ($i->jumlah_hari_hilang ?? $i->lost_workdays ?? 0);
                $ku = is_array($i->kategori_umum) ? $i->kategori_umum : (json_decode($i->kategori_umum, true) ?: []);
                $ke = is_array($i->kategori_ebtke) ? $i->kategori_ebtke : (json_decode($i->kategori_ebtke, true) ?: []);
                return in_array($severity, ['fatality', 'berat'])
                    || $days > 0
                    || in_array('LTI (Lost Time Injury)', $ku)
                    || in_array('Fatality', $ku)
                    || in_array('Kecelakaan Mati', $ke)
                    || in_array('Kecelakaan Berat', $ke);
            })->count();
            $trendLtiData[] = $mLti;

            $trendDaysData[] = (int) $mIncidents->sum(function ($i) {
                return (int) ($i->jumlah_hari_hilang ?? $i->lost_workdays ?? 0);
            });
        }

        $chartTrend = [
            'labels' => $trendMonths,
            'incidents' => $trendIncidentsData,
            'lti' => $trendLtiData,
            'days' => $trendDaysData,
        ];

        // 5b. Line Chart Tren Kecelakaan Kerja & Highlight Card (Mode: Harian / Bulanan / Tahunan)
        $trendKlasifikasi = $this->buildTrendKlasifikasiData($request);

        // 6. Data Lengkap untuk Interactive Drilldown Modal & Detail Modal
        $chartDetailIncidents = $chartIncidents->map(function ($i) {
            $ku = is_array($i->kategori_umum) ? $i->kategori_umum : (json_decode($i->kategori_umum, true) ?: []);
            $ke = is_array($i->kategori_ebtke) ? $i->kategori_ebtke : (json_decode($i->kategori_ebtke, true) ?: []);
            $dt = $i->tanggal_waktu ? Carbon::parse($i->tanggal_waktu) : ($i->incident_date ? Carbon::parse($i->incident_date) : null);
            return [
                'id' => $i->id,
                'nomor_insiden' => $i->nomor_insiden ?? $i->incident_code ?? ('INC-' . str_pad($i->id, 4, '0', STR_PAD_LEFT)),
                'title' => $i->title ?? '-',
                'tanggal_waktu' => $dt ? $dt->format('d M Y H:i') : '-',
                'tanggal_waktu_raw' => $dt ? $dt->format('Y-m-d\TH:i') : '',
                'hari' => $i->hari,
                'klasifikasi_area' => $i->klasifikasi_area ?? $i->work_area ?? 'Kecelakaan Panas Bumi',
                'kategori_umum' => $ku,
                'kategori_ebtke' => $ke,
                'lokasi_kejadian' => $i->lokasi_kejadian ?? $i->location ?? '-',
                'komponen_terkait' => $i->komponen_terkait ?? $i->unit ?? '-',
                'deskripsi_kejadian' => $i->deskripsi_kejadian ?? $i->description ?? '-',
                'tindakan_perbaikan' => $i->tindakan_perbaikan ?? $i->corrective_action ?? null,
                'immediate_action' => $i->immediate_action ?? null,
                'root_cause' => $i->root_cause ?? null,
                'penanggung_jawab' => $i->penanggung_jawab ?? $i->reported_by ?? '-',
                'target_penyelesaian' => $i->target_penyelesaian ? Carbon::parse($i->target_penyelesaian)->format('Y-m-d') : null,
                'severity' => $i->severity ?? 'Low',
                'jumlah_hari_hilang' => $i->jumlah_hari_hilang !== null ? (int) $i->jumlah_hari_hilang : null,
                'status_laporan' => $i->status_laporan ?? 'Preliminary',
                'status' => $i->status ?? 'Dilaporkan',
                'dokumen_investigasi' => $i->dokumen_investigasi,
                'documents_list' => $i->documents_list,
            ];
        })->values();

        $nextIncidentNumber = Incident::generateNextNumber();
        $areaList = Incident::KLASIFIKASI_AREAS;
        $kategoriUmumList = Incident::KATEGORI_UMUM_LIST;
        $kategoriEbtkeList = Incident::KATEGORI_EBTKE_LIST;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'incidents' => $incidents,
                'totalInsiden' => $totalInsiden,
                'totalHariHilang' => $totalHariHilang,
                'kasusHariHilangCount' => $kasusHariHilangCount,
                'panasBumiCount' => $panasBumiCount,
                'nonPanasBumiCount' => $nonPanasBumiCount,
                'chartArea' => $chartArea,
                'chartUmum' => $chartUmum,
                'chartEbtke' => $chartEbtke,
                'chartHariHilang' => $chartHariHilang,
                'chartDetailIncidents' => $chartDetailIncidents,
                'safetyMetrics' => $safetyMetrics,
                'chartTrend' => $chartTrend,
                'trendKlasifikasi' => $trendKlasifikasi,
                'activeTab' => $request->input('tab', 'incident'),
            ]);
        }

        return view('data.incident.index', compact(
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
            'nextIncidentNumber',
            'areaList',
            'kategoriUmumList',
            'kategoriEbtkeList',
            'safetyMetrics',
            'chartTrend',
            'trendKlasifikasi'
        ));
    }

    /**
     * Hitung agregasi data Line Chart Tren Kecelakaan Kerja (Panas Bumi vs Non-Panas Bumi)
     * dan Highlight Card Total Kejadian yang Dilaporkan (khusus Panas Bumi) berdasarkan
     * kolom tanggal_waktu pada periode yang dipilih (Harian / Bulanan / Tahunan).
     */
    private function buildTrendKlasifikasiData(Request $request): array
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentYear = (int) $now->year;

        $modeInput = strtolower(trim((string) $request->input('trend_mode', 'bulanan')));
        $mode = in_array($modeInput, ['harian', 'bulanan', 'tahunan'], true) ? $modeInput : 'bulanan';

        $year = (int) $request->input('trend_year', $currentYear);
        if ($year < 1970 || $year > 2100) {
            $year = $currentYear;
        }

        $month = (int) $request->input('trend_month', (int) $now->month);
        if ($month < 1 || $month > 12) {
            $month = (int) $now->month;
        }

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $isPanasBumiRecord = static function ($inc): bool {
            $val = (string) ($inc->klasifikasi_area ?? $inc->incident_type ?? '');
            return !str_contains($val, 'Non') && str_contains($val, 'Panas');
        };

        $isNonPanasBumiRecord = static function ($inc): bool {
            $val = (string) ($inc->klasifikasi_area ?? $inc->incident_type ?? '');
            return str_contains($val, 'Non');
        };

        $labels = [];
        $tooltipLabels = [];
        $panasBumiSeries = [];
        $nonPanasBumiSeries = [];
        $periodSubtitle = '';

        if ($mode === 'harian') {
            $startOfMonth = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth();
            $endOfMonth = (clone $startOfMonth)->endOfMonth();
            $daysInMonth = (int) $startOfMonth->daysInMonth;
            $monthLabel = $monthNames[$month] ?? '';

            $records = Incident::query()
                ->whereBetween('tanggal_waktu', [$startOfMonth, $endOfMonth])
                ->get(['id', 'tanggal_waktu', 'incident_date', 'klasifikasi_area', 'incident_type']);

            $panasByDay = array_fill(1, $daysInMonth, 0);
            $nonPanasByDay = array_fill(1, $daysInMonth, 0);

            foreach ($records as $rec) {
                $dtRaw = $rec->tanggal_waktu ?? $rec->incident_date;
                if (!$dtRaw) {
                    continue;
                }
                $dayNum = (int) Carbon::parse($dtRaw)->day;
                if ($dayNum < 1 || $dayNum > $daysInMonth) {
                    continue;
                }
                if ($isPanasBumiRecord($rec)) {
                    $panasByDay[$dayNum]++;
                } elseif ($isNonPanasBumiRecord($rec)) {
                    $nonPanasByDay[$dayNum]++;
                }
            }

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $labels[] = (string) $d;
                $tooltipLabels[] = "{$d} {$monthLabel} {$year}";
                $panasBumiSeries[] = $panasByDay[$d];
                $nonPanasBumiSeries[] = $nonPanasByDay[$d];
            }

            $periodSubtitle = "Periode: {$monthLabel} {$year}";
        } elseif ($mode === 'tahunan') {
            $startYear = $currentYear - 5;
            $endYear = $currentYear + 5;
            $startOfRange = Carbon::create($startYear, 1, 1, 0, 0, 0, 'Asia/Jakarta')->startOfYear();
            $endOfRange = Carbon::create($endYear, 12, 31, 23, 59, 59, 'Asia/Jakarta')->endOfYear();

            $records = Incident::query()
                ->whereBetween('tanggal_waktu', [$startOfRange, $endOfRange])
                ->get(['id', 'tanggal_waktu', 'incident_date', 'klasifikasi_area', 'incident_type']);

            $panasByYear = [];
            $nonPanasByYear = [];
            for ($y = $startYear; $y <= $endYear; $y++) {
                $panasByYear[$y] = 0;
                $nonPanasByYear[$y] = 0;
            }

            foreach ($records as $rec) {
                $dtRaw = $rec->tanggal_waktu ?? $rec->incident_date;
                if (!$dtRaw) {
                    continue;
                }
                $yr = (int) Carbon::parse($dtRaw)->year;
                if (!isset($panasByYear[$yr])) {
                    continue;
                }
                if ($isPanasBumiRecord($rec)) {
                    $panasByYear[$yr]++;
                } elseif ($isNonPanasBumiRecord($rec)) {
                    $nonPanasByYear[$yr]++;
                }
            }

            for ($y = $startYear; $y <= $endYear; $y++) {
                $labels[] = (string) $y;
                $tooltipLabels[] = "Tahun {$y}";
                $panasBumiSeries[] = $panasByYear[$y];
                $nonPanasBumiSeries[] = $nonPanasByYear[$y];
            }

            $periodSubtitle = "Periode: {$startYear} – {$endYear}";
        } else {
            // Mode Bulanan (Default: Januari – Desember pada tahun terpilih)
            $startOfYear = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Jakarta')->startOfYear();
            $endOfYear = (clone $startOfYear)->endOfYear();

            $records = Incident::query()
                ->whereBetween('tanggal_waktu', [$startOfYear, $endOfYear])
                ->get(['id', 'tanggal_waktu', 'incident_date', 'klasifikasi_area', 'incident_type']);

            $panasByMonth = array_fill(1, 12, 0);
            $nonPanasByMonth = array_fill(1, 12, 0);

            foreach ($records as $rec) {
                $dtRaw = $rec->tanggal_waktu ?? $rec->incident_date;
                if (!$dtRaw) {
                    continue;
                }
                $mNum = (int) Carbon::parse($dtRaw)->month;
                if ($mNum < 1 || $mNum > 12) {
                    continue;
                }
                if ($isPanasBumiRecord($rec)) {
                    $panasByMonth[$mNum]++;
                } elseif ($isNonPanasBumiRecord($rec)) {
                    $nonPanasByMonth[$mNum]++;
                }
            }

            for ($m = 1; $m <= 12; $m++) {
                $mName = $monthNames[$m];
                $labels[] = $mName;
                $tooltipLabels[] = "{$mName} {$year}";
                $panasBumiSeries[] = $panasByMonth[$m];
                $nonPanasBumiSeries[] = $nonPanasByMonth[$m];
            }

            $periodSubtitle = "Periode: Tahun {$year}";
        }

        $highlightPanasBumiTotal = array_sum($panasBumiSeries);
        $totalNonPanasBumi = array_sum($nonPanasBumiSeries);

        return [
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'currentYear' => $currentYear,
            'labels' => $labels,
            'tooltipLabels' => $tooltipLabels,
            'panasBumi' => $panasBumiSeries,
            'nonPanasBumi' => $nonPanasBumiSeries,
            'highlightTotal' => $highlightPanasBumiTotal,
            'totalPanasBumi' => $highlightPanasBumiTotal,
            'totalNonPanasBumi' => $totalNonPanasBumi,
            'periodSubtitle' => $periodSubtitle,
            'colors' => [
                'panasBumi' => '#ea580c',
                'nonPanasBumi' => '#0284c7',
            ],
        ];
    }

    /**
     * Simpan Insiden Baru (Mendukung multiple & incremental upload dokumentasi via ImageUploadService & MediaAttachment)
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $docRules = is_array($request->file('dokumen_investigasi'))
            ? [
                'dokumen_investigasi' => 'nullable|array',
                'dokumen_investigasi.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,gif,bmp,doc,docx,xls,xlsx|max:15360',
            ]
            : [
                'dokumen_investigasi' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif,bmp,doc,docx,xls,xlsx|max:15360',
            ];

        $validated = $request->validate(array_merge([
            'nomor_insiden' => 'nullable|string|max:100',
            'judul_insiden' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'tanggal_waktu' => 'required|date',
            'hari' => 'nullable|string|max:20',
            'klasifikasi_area' => 'required|string|max:100',
            'kategori_umum' => 'nullable|array',
            'kategori_umum.*' => 'string|not_in:Hazard Report',
            'kategori_ebtke' => 'nullable|array',
            'lokasi_kejadian' => 'required|string|max:255',
            'komponen_terkait' => 'required|string|max:100',
            'severity' => 'nullable|string|max:50',
            'deskripsi_kejadian' => 'required|string',
            'immediate_action' => 'nullable|string',
            'root_cause' => 'nullable|string',
            'tindakan_perbaikan' => 'nullable|string',
            'penanggung_jawab' => 'nullable|string|max:100',
            'target_penyelesaian' => 'nullable|date',
            'status_laporan' => 'required|string|max:50',
            'jumlah_hari_hilang' => 'nullable|integer|min:0',
        ], $docRules));

        return DB::transaction(function () use ($validated, $request) {
            $num = $validated['nomor_insiden'] ?? Incident::generateNextNumber();
            $judul = $validated['judul_insiden'] ?? $validated['title'] ?? null;
            $title = !empty($judul) ? $judul : ($num . ' - ' . $validated['lokasi_kejadian']);

            // Hari selalu dihitung secara konsisten dari Tanggal & Waktu Kejadian (Asia/Jakarta)
            $hari = Incident::resolveDayName($validated['tanggal_waktu']);

            $kategoriUmum = array_values(array_filter(
                $validated['kategori_umum'] ?? [],
                fn($t) => $t !== 'Hazard Report'
            ));
            $hasLti = in_array('LTI (Lost Time Injury)', $kategoriUmum, true);
            $jumlahHariHilang = $hasLti
                ? (isset($validated['jumlah_hari_hilang']) && $validated['jumlah_hari_hilang'] !== ''
                    ? max(0, (int) $validated['jumlah_hari_hilang'])
                    : 0)
                : null;

            $rawStatus = $validated['status_laporan'] ?? 'Preliminary';
            $isClosed = in_array($rawStatus, ['Selesai', 'Final']);
            $dbStatus = $isClosed ? 'Selesai' : 'Investigasi';
            $dbStatusLaporan = in_array($rawStatus, ['Final', 'Preliminary']) ? $rawStatus : ($isClosed ? 'Selesai' : 'On Progress');

            $incident = Incident::create([
                'nomor_insiden' => $num,
                'incident_code' => $num,
                'title' => $title,
                'tanggal_waktu' => $validated['tanggal_waktu'],
                'hari' => $hari,
                'incident_date' => $validated['tanggal_waktu'],
                'klasifikasi_area' => $validated['klasifikasi_area'],
                'incident_type' => $validated['klasifikasi_area'],
                'kategori_umum' => $kategoriUmum,
                'kategori_ebtke' => $validated['kategori_ebtke'] ?? [],
                'lokasi_kejadian' => $validated['lokasi_kejadian'],
                'location' => $validated['lokasi_kejadian'],
                'komponen_terkait' => $validated['komponen_terkait'],
                'unit' => $validated['komponen_terkait'],
                'severity' => $validated['severity'] ?? 'Low',
                'deskripsi_kejadian' => $validated['deskripsi_kejadian'],
                'description' => $validated['deskripsi_kejadian'],
                'immediate_action' => $validated['immediate_action'] ?? null,
                'root_cause' => $validated['root_cause'] ?? null,
                'tindakan_perbaikan' => $validated['tindakan_perbaikan'] ?? null,
                'corrective_action' => $validated['tindakan_perbaikan'] ?? null,
                'penanggung_jawab' => $validated['penanggung_jawab'] ?? (Auth::user()?->name ?? 'Admin HSSE'),
                'reported_by' => $validated['penanggung_jawab'] ?? (Auth::user()?->name ?? 'Admin HSSE'),
                'target_penyelesaian' => $validated['target_penyelesaian'] ?? null,
                'status_laporan' => $dbStatusLaporan,
                'status' => $dbStatus,
                'closed_at' => $isClosed ? now() : null,
                'jumlah_hari_hilang' => $jumlahHariHilang,
                'dokumen_investigasi' => null,
            ]);

            $firstDocPath = $this->storeIncidentDocuments($request, $incident);
            if ($firstDocPath) {
                $incident->update(['dokumen_investigasi' => $firstDocPath]);
            }
            $incident->load('mediaAttachments');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'Admin HSSE',
                'module' => 'Incident',
                'action' => 'TAMBAH',
                'record_type' => Incident::class,
                'record_id' => $incident->id,
                'description' => "Melaporkan insiden baru: [{$incident->nomor_insiden}] di {$incident->lokasi_kejadian}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Laporan insiden [{$incident->nomor_insiden}] berhasil ditambahkan.",
                    'incident' => $incident,
                ], 201);
            }

            return redirect()->route('incident.index')->with('success', "Laporan insiden [{$incident->nomor_insiden}] berhasil ditambahkan.");
        });
    }

    /**
     * Perbarui Laporan Insiden (Menambahkan file baru ke lampiran tanpa menimpa dokumentasi sebelumnya)
     */
    public function update(Request $request, $id): RedirectResponse|JsonResponse
    {
        $incident = Incident::findOrFail($id);

        $docRules = is_array($request->file('dokumen_investigasi'))
            ? [
                'dokumen_investigasi' => 'nullable|array',
                'dokumen_investigasi.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,gif,bmp,doc,docx,xls,xlsx|max:15360',
            ]
            : [
                'dokumen_investigasi' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif,bmp,doc,docx,xls,xlsx|max:15360',
            ];

        $validated = $request->validate(array_merge([
            'judul_insiden' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'tanggal_waktu' => 'required|date',
            'hari' => 'nullable|string|max:20',
            'klasifikasi_area' => 'required|string|max:100',
            'kategori_umum' => 'nullable|array',
            'kategori_umum.*' => 'string|not_in:Hazard Report',
            'kategori_ebtke' => 'nullable|array',
            'lokasi_kejadian' => 'required|string|max:255',
            'komponen_terkait' => 'required|string|max:100',
            'severity' => 'nullable|string|max:50',
            'deskripsi_kejadian' => 'required|string',
            'immediate_action' => 'nullable|string',
            'root_cause' => 'nullable|string',
            'tindakan_perbaikan' => 'nullable|string',
            'penanggung_jawab' => 'nullable|string|max:100',
            'target_penyelesaian' => 'nullable|date',
            'status_laporan' => 'required|string|max:50',
            'jumlah_hari_hilang' => 'nullable|integer|min:0',
        ], $docRules));

        return DB::transaction(function () use ($validated, $request, $incident) {
            $newFirstDocPath = $this->storeIncidentDocuments($request, $incident);
            $docPath = $incident->dokumen_investigasi ?: $newFirstDocPath;

            $judul = $validated['judul_insiden'] ?? $validated['title'] ?? null;
            $title = !empty($judul) ? $judul : ($incident->title ?: ($incident->nomor_insiden . ' - ' . $validated['lokasi_kejadian']));

            // Hari selalu dihitung secara konsisten dari Tanggal & Waktu Kejadian (Asia/Jakarta)
            $hari = Incident::resolveDayName($validated['tanggal_waktu']);

            $kategoriUmum = array_values(array_filter(
                $validated['kategori_umum'] ?? [],
                fn($t) => $t !== 'Hazard Report'
            ));
            $hasLti = in_array('LTI (Lost Time Injury)', $kategoriUmum, true);
            $jumlahHariHilang = $hasLti
                ? (isset($validated['jumlah_hari_hilang']) && $validated['jumlah_hari_hilang'] !== ''
                    ? max(0, (int) $validated['jumlah_hari_hilang'])
                    : 0)
                : null;

            $rawStatus = $validated['status_laporan'] ?? $incident->status_laporan ?? 'Preliminary';
            $isClosed = in_array($rawStatus, ['Selesai', 'Final']);
            $dbStatus = $isClosed ? 'Selesai' : 'Investigasi';
            $dbStatusLaporan = in_array($rawStatus, ['Final', 'Preliminary']) ? $rawStatus : ($isClosed ? 'Selesai' : 'On Progress');

            $incident->update([
                'title' => $title,
                'tanggal_waktu' => $validated['tanggal_waktu'],
                'hari' => $hari,
                'incident_date' => $validated['tanggal_waktu'],
                'klasifikasi_area' => $validated['klasifikasi_area'],
                'incident_type' => $validated['klasifikasi_area'],
                'kategori_umum' => $kategoriUmum,
                'kategori_ebtke' => $validated['kategori_ebtke'] ?? [],
                'lokasi_kejadian' => $validated['lokasi_kejadian'],
                'location' => $validated['lokasi_kejadian'],
                'komponen_terkait' => $validated['komponen_terkait'],
                'unit' => $validated['komponen_terkait'],
                'severity' => $validated['severity'] ?? $incident->severity ?? 'Low',
                'deskripsi_kejadian' => $validated['deskripsi_kejadian'],
                'description' => $validated['deskripsi_kejadian'],
                'immediate_action' => $validated['immediate_action'] ?? $incident->immediate_action,
                'root_cause' => $validated['root_cause'] ?? $incident->root_cause,
                'tindakan_perbaikan' => $validated['tindakan_perbaikan'] ?? null,
                'corrective_action' => $validated['tindakan_perbaikan'] ?? null,
                'penanggung_jawab' => $validated['penanggung_jawab'] ?? $incident->penanggung_jawab,
                'reported_by' => $validated['penanggung_jawab'] ?? $incident->reported_by,
                'target_penyelesaian' => array_key_exists('target_penyelesaian', $validated) ? $validated['target_penyelesaian'] : $incident->target_penyelesaian,
                'status_laporan' => $dbStatusLaporan,
                'status' => $dbStatus,
                'closed_at' => $isClosed ? ($incident->closed_at ?: now()) : null,
                'jumlah_hari_hilang' => $jumlahHariHilang,
                'dokumen_investigasi' => $docPath,
            ]);

            $incident->load('mediaAttachments');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'Admin HSSE',
                'module' => 'Incident',
                'action' => 'UBAH',
                'record_type' => Incident::class,
                'record_id' => $incident->id,
                'description' => "Memperbarui laporan insiden [{$incident->nomor_insiden}]",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan insiden berhasil diperbarui!',
                    'incident' => $incident,
                ]);
            }

            return redirect()->route('incident.index')->with('success', "Laporan insiden [{$incident->nomor_insiden}] berhasil diperbarui!");
        });
    }

    /**
     * Simpan satu atau banyak file dokumentasi insiden ke storage & tabel media_attachments.
     */
    private function storeIncidentDocuments(Request $request, Incident $incident): ?string
    {
        if (!$request->hasFile('dokumen_investigasi')) {
            return null;
        }

        $rawFiles = $request->file('dokumen_investigasi');
        $files = is_array($rawFiles) ? $rawFiles : [$rawFiles];
        $imgService = app(ImageUploadService::class);
        $firstPath = null;

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            if ($imgService->isValidImage($file)) {
                $media = $imgService->uploadImage(
                    $file,
                    'incidents/evidence',
                    $incident,
                    'incidents',
                    'Dokumentasi Insiden ' . $incident->nomor_insiden,
                    'local'
                );
                $firstPath = $firstPath ?? $media->file_path;
            } else {
                $storedPath = $file->store('incidents/evidence', 'local');
                $size = (int) ($file->getSize() ?: 0);
                MediaAttachment::create([
                    'mediable_type' => Incident::class,
                    'mediable_id' => $incident->id,
                    'module' => 'incidents',
                    'disk' => 'local',
                    'file_path' => $storedPath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_name' => basename($storedPath),
                    'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                    'extension' => strtolower($file->getClientOriginalExtension()),
                    'original_size' => $size,
                    'compressed_size' => $size,
                    'uploaded_by' => Auth::id(),
                    'caption' => 'Dokumentasi Insiden ' . $incident->nomor_insiden,
                ]);
                $firstPath = $firstPath ?? $storedPath;
            }
        }

        return $firstPath;
    }

    /**
     * Sajikan dokumen / foto bukti investigasi insiden secara terautentikasi dari private storage (fallback legacy public).
     */
    public function viewDocument($attachmentId): BinaryFileResponse
    {
        $att = MediaAttachment::where('mediable_type', Incident::class)->findOrFail($attachmentId);

        $filePath = ltrim((string) $att->file_path, '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            abort(404, 'File tidak ditemukan.');
        }

        $disksToTry = array_values(array_unique([$att->disk ?: 'local', 'local', 'public']));
        foreach ($disksToTry as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                $fullPath = $disk->path($filePath);
                $mime = $att->mime_type ?: ($disk->mimeType($filePath) ?: 'application/octet-stream');
                $safeName = str_replace(['"', "\r", "\n", '\\', '/'], '_', (string) ($att->original_name ?: basename($filePath)));

                return response()->file($fullPath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $safeName . '"',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404, 'File dokumentasi insiden tidak ditemukan.');
    }

    /**
     * Sajikan dokumen investigasi legacy (kolom dokumen_investigasi) secara terautentikasi.
     */
    public function viewLegacyDocument(Incident $incident): BinaryFileResponse
    {
        $filePath = ltrim((string) $incident->dokumen_investigasi, '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            abort(404, 'File tidak ditemukan.');
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                $fullPath = $disk->path($filePath);
                $mime = $disk->mimeType($filePath) ?: 'application/octet-stream';
                $safeName = str_replace(['"', "\r", "\n", '\\', '/'], '_', basename($filePath));

                return response()->file($fullPath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $safeName . '"',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404, 'File dokumentasi insiden tidak ditemukan.');
    }

    /**
     * Hapus Laporan Insiden
     */
    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $incident = Incident::with('mediaAttachments')->findOrFail($id);
            $num = $incident->nomor_insiden;

            $filesToDelete = [];
            foreach ($incident->mediaAttachments as $att) {
                if (!empty($att->file_path)) {
                    $filesToDelete[] = ['disk' => $att->disk ?: 'local', 'path' => $att->file_path];
                }
                $att->delete();
            }
            if (!empty($incident->dokumen_investigasi)) {
                $filesToDelete[] = ['disk' => 'local', 'path' => $incident->dokumen_investigasi];
            }

            $incident->delete();

            DB::afterCommit(function () use ($filesToDelete) {
                foreach ($filesToDelete as $item) {
                    foreach (array_unique([$item['disk'], 'local', 'public']) as $diskName) {
                        if (Storage::disk($diskName)->exists($item['path'])) {
                            Storage::disk($diskName)->delete($item['path']);
                        }
                    }
                }
            });

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'Admin HSSE',
                'module' => 'Incident',
                'action' => 'HAPUS',
                'record_type' => Incident::class,
                'record_id' => (int) $id,
                'description' => "Menghapus laporan insiden [{$num}]",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan insiden berhasil dihapus!',
                ]);
            }

            return redirect()->route('incident.index')->with('success', "Laporan insiden [{$num}] berhasil dihapus!");
        });
    }

    /**
     * Export data Incident ke format PDF Landscape A4 atau CSV sesuai filter aktif.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $query = Incident::query();

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nomor_insiden', 'like', "%{$search}%")
                  ->orWhere('incident_code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('lokasi_kejadian', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('komponen_terkait', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$search}%")
                  ->orWhere('reported_by', 'like', "%{$search}%")
                  ->orWhere('deskripsi_kejadian', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('hari', 'like', "%{$search}%")
                  ->orWhere('kategori_umum', 'like', "%{$search}%")
                  ->orWhere('kategori_ebtke', 'like', "%{$search}%");
            });
        }

        // Filter Klasifikasi Area
        if ($request->filled('klasifikasi_area') && $request->input('klasifikasi_area') !== 'Semua') {
            $area = $request->input('klasifikasi_area');
            $query->where(function ($q) use ($area) {
                $q->where('klasifikasi_area', 'like', "%{$area}%")
                  ->orWhere('incident_type', 'like', "%{$area}%");
            });
        }

        // Filter Kategori Umum (Abaikan Hazard Report)
        if ($request->filled('kategori_umum') && !in_array($request->input('kategori_umum'), ['Semua', 'Hazard Report'], true)) {
            $tagUmum = $request->input('kategori_umum');
            $query->where(function ($q) use ($tagUmum) {
                $q->whereJsonContains('kategori_umum', $tagUmum)
                  ->orWhere('kategori_umum', 'like', "%\"{$tagUmum}\"%")
                  ->orWhere('kategori_umum', 'like', "%{$tagUmum}%");
            });
        }

        // Filter Kategori EBTKE
        if ($request->filled('kategori_ebtke') && $request->input('kategori_ebtke') !== 'Semua') {
            $tagEbtke = $request->input('kategori_ebtke');
            $query->where(function ($q) use ($tagEbtke) {
                $q->whereJsonContains('kategori_ebtke', $tagEbtke)
                  ->orWhere('kategori_ebtke', 'like', "%\"{$tagEbtke}\"%")
                  ->orWhere('kategori_ebtke', 'like', "%{$tagEbtke}%");
            });
        }

        // Filter Status Laporan (Preliminary / Final)
        if ($request->filled('status_laporan') && in_array($request->input('status_laporan'), ['Preliminary', 'Final'], true)) {
            $statusFilter = $request->input('status_laporan');
            if ($statusFilter === 'Final') {
                $query->where(function ($q) {
                    $q->whereIn('status_laporan', ['Final', 'Selesai'])
                      ->orWhere(function ($sub) {
                          $sub->whereNull('status_laporan')->where('status', 'Selesai');
                      });
                });
            } else {
                $query->where(function ($q) {
                    $q->whereIn('status_laporan', ['Preliminary', 'On Progress', 'Investigasi', 'Dilaporkan'])
                      ->orWhere(function ($sub) {
                          $sub->whereNull('status_laporan')->where('status', '!=', 'Selesai');
                      });
                });
            }
        }

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_waktu', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        } elseif ($request->filled('start_date')) {
            $query->where('tanggal_waktu', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        } elseif ($request->filled('end_date')) {
            $query->where('tanggal_waktu', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }

        $records = $query->orderBy('tanggal_waktu', 'desc')->get();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'Admin HSSE',
            'module' => 'Incident',
            'action' => 'EXPORT',
            'description' => 'Mengekspor laporan data Incident (' . $records->count() . ' data)',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->input('format') === 'csv' || $request->has('csv')) {
            $filename = 'Laporan_Incident_HSSE_' . now()->format('Ymd_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($records) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'Nomor Insiden',
                    'Judul Insiden',
                    'Tanggal & Waktu',
                    'Hari',
                    'Klasifikasi Area',
                    'Lokasi Kejadian',
                    'Komponen / Unit',
                    'Kategori Umum',
                    'Kategori EBTKE',
                    'Status Laporan',
                    'Hari Kerja Hilang (LWD)',
                    'Penanggung Jawab',
                    'Deskripsi Kejadian',
                    'Tindakan Perbaikan',
                ]);

                foreach ($records as $item) {
                    $kategoriUmum = is_array($item->kategori_umum) ? implode(', ', $item->kategori_umum) : $item->kategori_umum;
                    $kategoriEbtke = is_array($item->kategori_ebtke) ? implode(', ', $item->kategori_ebtke) : $item->kategori_ebtke;

                    fputcsv($handle, [
                        $item->nomor_insiden ?: $item->incident_code,
                        $item->title ?: '-',
                        $item->tanggal_waktu ? Carbon::parse($item->tanggal_waktu)->format('Y-m-d H:i:s') : $item->incident_date,
                        $item->hari ?: '-',
                        $item->klasifikasi_area,
                        $item->lokasi_kejadian ?: $item->location,
                        $item->komponen_terkait ?: $item->unit,
                        $kategoriUmum,
                        $kategoriEbtke,
                        $item->status_laporan ?: $item->status,
                        (int) ($item->jumlah_hari_hilang ?? $item->lost_workdays ?? 0),
                        $item->penanggung_jawab ?: $item->reported_by,
                        $item->deskripsi_kejadian ?: $item->description,
                        $item->tindakan_perbaikan ?: $item->corrective_action,
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        $filterPeriod = ($request->filled('start_date') ? $request->input('start_date') : 'Awal') . ' s/d ' . ($request->filled('end_date') ? $request->input('end_date') : 'Sekarang');
        $scopeParts = array_filter([
            $request->input('klasifikasi_area'),
            $request->input('kategori_umum'),
            $request->input('kategori_ebtke'),
        ]);
        $filterScope = !empty($scopeParts) ? implode(' | ', $scopeParts) : 'Semua Area & Kategori';

        $pdf = Pdf::loadView('exports.incident-pdf', [
            'reportTitle' => 'LAPORAN REKAPITULASI INSIDEN & KEJADIAN HSSE',
            'moduleName' => 'Incident',
            'filterPeriod' => $filterPeriod,
            'filterScope' => $filterScope,
            'totalRecords' => $records->count(),
            'filterInfo' => [
                'Klasifikasi Area' => $request->input('klasifikasi_area') ?: 'Semua',
                'Kategori Umum' => $request->input('kategori_umum') ?: 'Semua',
                'Kategori EBTKE' => $request->input('kategori_ebtke') ?: 'Semua',
                'Periode' => $filterPeriod,
                'Pencarian' => $request->input('search') ?: '-',
            ],
            'records' => $records,
            'userName' => Auth::user()?->name ?? 'Administrator',
            'generatedAt' => now()->translatedFormat('d F Y, H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan-Incident-HSSE-' . now()->format('Ymd-His') . '.pdf');
    }
}
