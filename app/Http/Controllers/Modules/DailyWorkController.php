<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\Event;
use App\Models\ManHour;
use App\Models\OperationalUnit;
use App\Models\Sikka;
use App\Models\Workforce;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DailyWorkController extends Controller
{
    /**
     * Parse dan sanitasi filter perusahaan.
     * Hanya perusahaan aktif yang memenuhi syarat filter (termasuk induk aktif untuk subcon)
     * yang diakui sebagai filter aktif pada dashboard.
     *
     * @return array<int, string>
     */
    protected function parseFilteredCompanies(Request $request): array
    {
        if (!$request->filled('company') && !$request->filled('unit') && !$request->filled('companies')) {
            return [];
        }

        $rawComp = $request->input('companies') ?? ($request->input('company') ?? $request->input('unit'));
        $compList = [];
        if (is_array($rawComp)) {
            $compList = array_values(array_unique(array_filter(array_map('trim', $rawComp))));
        } elseif (is_string($rawComp)) {
            $trimmed = trim($rawComp);
            if ($trimmed !== '' && $trimmed !== '-') {
                $compList = array_values(array_unique(array_filter(array_map('trim', explode(',', $trimmed)))));
            }
        }

        if (empty($compList)) {
            return [];
        }

        // Ambil daftar nama perusahaan yang nonaktif atau subcon yang induknya nonaktif
        $allMaster = DailyWorkCompany::with('parent')->get();
        $activeMap = $allMaster->keyBy('id');

        $inactiveNames = [];
        foreach ($allMaster as $c) {
            if (!$c->is_active) {
                $inactiveNames[mb_strtolower(trim($c->name))] = true;
            } elseif ($c->parent_company_id) {
                $parent = $c->parent ?? $activeMap->get($c->parent_company_id);
                if ($parent && !$parent->is_active) {
                    $inactiveNames[mb_strtolower(trim($c->name))] = true;
                }
            }
        }

        $filtered = [];
        foreach ($compList as $name) {
            $key = mb_strtolower(trim($name));
            if (!isset($inactiveNames[$key])) {
                $filtered[] = $name;
            }
        }

        return array_values(array_unique($filtered));
    }

    /**
     * Hitung statistik pekerjaan (zona waktu Asia/Jakarta).
     * Jika targetDate diberikan, menghitung pekerjaan yang tanggal mulainya sesuai targetDate.
     * Jika tidak, default hari ini.
     */
    protected function getStats(?string $targetDate = null, ?Request $request = null): array
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $date = $targetDate ?: $today;

        // Pekerjaan yang tanggal mulainya tepat sama dengan tanggal acuan (DATE(start_date) = date) dan belum selesai
        $dateWorksQuery = DailyWork::dailyWorkToday($date);

        if ($request) {
            $compList = $this->parseFilteredCompanies($request);
            if (count($compList) === 1) {
                $dateWorksQuery->where('unit', $compList[0]);
            } elseif (count($compList) > 1) {
                $dateWorksQuery->whereIn('unit', $compList);
            }

            if ($request->filled('sika') || $request->filled('sikka')) {
                $sikaVal = trim((string) ($request->input('sika') ?? $request->input('sikka')));
                if ($sikaVal !== '') {
                    $dateWorksQuery->where(function ($q) use ($sikaVal) {
                        $q->where('sikka', $sikaVal)
                          ->orWhere('sikka', 'like', "%{$sikaVal}%");
                    });
                }
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->input('search'));
                if ($search !== '') {
                    $dateWorksQuery->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('unit', 'like', "%{$search}%")
                          ->orWhere('location', 'like', "%{$search}%");
                    });
                }
            }
        }

        $dateWorks = $dateWorksQuery->get();

        $companiesGrouped = $dateWorks->groupBy('unit')->map(function ($items, $unitName) {
            return [
                'name' => $unitName ?: 'Tanpa Perusahaan',
                'count' => $items->count(),
            ];
        })->values();

        $totalAll = DailyWork::count();
        $totalHistory = $totalAll;

        return [
            'total_today' => $dateWorks->count(),
            'companies_today' => $companiesGrouped->count(),
            'companies_list_today' => $companiesGrouped->all(),
            'total_all' => $totalAll,
            'total_history' => $totalHistory,
            'today_date' => $today,
            'target_date' => $date,
        ];
    }

    /**
     * Hitung distribusi pekerjaan per perusahaan untuk diagram lingkaran (pie chart).
     * Jika tidak diberikan koleksi, default menggunakan pekerjaan yang tanggal mulainya hari ini.
     */
    protected function getCompanyDistribution($works = null): array
    {
        if ($works === null) {
            $today = Carbon::now('Asia/Jakarta')->toDateString();
            $works = DailyWork::dailyWorkToday($today)->get();
        }

        $distribution = [];
        $total = $works->count();

        if ($total > 0) {
            $grouped = $works->groupBy('unit');
            foreach ($grouped as $compName => $group) {
                $count = $group->count();
                $distribution[] = [
                    'name' => $compName ?: 'Lainnya',
                    'count' => $count,
                    'percentage' => round(($count / $total) * 100, 1),
                ];
            }
            usort($distribution, fn ($a, $b) => $b['count'] <=> $a['count']);
        }

        return $distribution;
    }

    /**
     * Bangun query daftar pekerjaan berdasarkan mode ('today' atau 'all') serta filter yang dipilih.
     */
    protected function buildFilteredQuery(Request $request, string $today): Builder
    {
        $query = DailyWork::with(['masterCompany.parent']);

        // Filter: Perusahaan (unit) - mendukung pilihan tunggal, array, maupun dipisahkan koma
        $compList = $this->parseFilteredCompanies($request);
        if (count($compList) === 1) {
            $query->where('unit', $compList[0]);
        } elseif (count($compList) > 1) {
            $query->whereIn('unit', $compList);
        }

        // Filter: SIKA (mendukung parameter 'sika' maupun 'sikka')
        if ($request->filled('sika') || $request->filled('sikka')) {
            $sikaVal = trim((string) ($request->input('sika') ?? $request->input('sikka')));
            if ($sikaVal !== '') {
                $query->where(function ($q) use ($sikaVal) {
                    $q->where('sikka', $sikaVal)
                      ->orWhere('sikka', 'like', "%{$sikaVal}%");
                });
            }
        }

        // Filter: Status (bila diisi spesifik pada query riwayat)
        if ($request->filled('status')) {
            $query->where('status', DailyWork::normalizeStatus($request->input('status')));
        }

        // Mode Tanggal: 'today' (default) atau 'all' (semua riwayat)
        $dateMode = strtolower(trim((string) $request->input('date_mode', 'today')));
        $isTodayMode = ($dateMode === '' || $dateMode === 'today');

        $startDate = $request->filled('start_date') ? trim((string) $request->input('start_date')) : null;
        $endDate = $request->filled('end_date') ? trim((string) $request->input('end_date')) : null;
        $legacyDate = $request->filled('date') ? trim((string) $request->input('date')) : null;

        // Fallback kompatibilitas jika pengguna/pengujian mengirim parameter tunggal 'date'
        if (!$startDate && !$endDate && $legacyDate) {
            $startDate = $legacyDate;
            $endDate = $legacyDate;
        }

        // Normalisasi: jika kedua tanggal diisi dan start > end, swap agar rentang selalu valid
        if ($startDate && $endDate && $startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        if ($isTodayMode) {
            if (!$startDate && !$endDate) {
                // Single source of truth bersama Dashboard: start_date hari ini (Asia/Jakarta) & status != Selesai
                $query->dailyWorkToday($today);
            } elseif ($startDate && $endDate && $startDate === $endDate) {
                $query->dailyWorkToday($startDate);
            } else {
                // Mode Hari Ini dengan rentang tanggal: tetap hanya belum selesai dan berdasar start_date
                $query->where('status', '!=', DailyWork::STATUS_COMPLETED);
                if ($startDate && $endDate) {
                    $query->whereDate('start_date', '>=', $startDate)
                          ->whereDate('start_date', '<=', $endDate);
                } elseif ($startDate) {
                    $query->whereDate('start_date', '>=', $startDate);
                } else {
                    $query->whereDate('start_date', '<=', $endDate);
                }
            }
        } else {
            // Mode Semua Riwayat: arsip permanen seluruh data pekerjaan (termasuk hari ini, sebelumnya, berlangsung, & selesai)
            if ($startDate && $endDate) {
                // Rentang tanggal inklusif: pelaksanaan pekerjaan beririsan dengan [startDate, endDate]
                $query->whereDate('start_date', '<=', $endDate)
                      ->where(function ($q) use ($startDate) {
                          $q->whereDate('end_date', '>=', $startDate)
                            ->orWhere(function ($sq) use ($startDate) {
                                $sq->whereNull('end_date')
                                   ->whereDate('start_date', '>=', $startDate);
                            });
                      });
            } elseif ($startDate) {
                // Hanya Tanggal Mulai diisi
                $query->where(function ($q) use ($startDate) {
                    $q->whereDate('end_date', '>=', $startDate)
                      ->orWhere(function ($sq) use ($startDate) {
                          $sq->whereNull('end_date')
                             ->whereDate('start_date', '>=', $startDate);
                      });
                });
            } elseif ($endDate) {
                // Hanya Tanggal Selesai diisi
                $query->whereDate('start_date', '<=', $endDate);
            }
            // Jika keduanya kosong: tampilkan seluruh riwayat (masa lampau & aturan existing masa depan tetap utuh, tanpa data hari ini)
        }

        // Filter: Pencarian nama pekerjaan, lokasi, perusahaan, SIKA, deskripsi
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('location', 'like', "%{$term}%")
                  ->orWhere('unit', 'like', "%{$term}%")
                  ->orWhere('sikka', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    /**
     * Tampilkan halaman utama Daily Work dengan tabel pekerjaan hari ini, pie chart perusahaan, dan statistik hari ini.
     */
    public function index(Request $request): View|JsonResponse
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        Sikka::ensureDefaults();
        DailyWorkCompany::ensureRequired();

        // Ambil data master untuk form dan filter
        $companies = $this->loadCompanies();
        $filterCompanies = DailyWorkCompany::getActiveForFilter();
        $companyGroups = DailyWorkCompany::groupForSelect($filterCompanies);
        $companyCategories = DailyWorkCompany::CATEGORY_LABELS;
        $sikkas = Sikka::orderBy('id')->get();

        $dateModeInput = strtolower(trim((string) $request->input('date_mode', 'today')));
        $isTodayMode = ($dateModeInput === '' || $dateModeInput === 'today');
        $dateMode = $isTodayMode ? 'today' : 'all';
        $selectedDate = $request->input('date');

        // Statistik dan prioritas urutan mengikuti tanggal acuan terpilih dan filter aktif
        $singleFilterDate = null;
        if ($isTodayMode) {
            $reqStart = $request->filled('start_date') ? trim((string) $request->input('start_date')) : null;
            $reqEnd = $request->filled('end_date') ? trim((string) $request->input('end_date')) : null;
            if ($reqStart && $reqEnd && $reqStart === $reqEnd) {
                $singleFilterDate = $reqStart;
            } elseif ($selectedDate) {
                $singleFilterDate = $selectedDate;
            }
        }

        $query = $this->buildFilteredQuery($request, $today);
        $targetDateForOrder = $isTodayMode ? ($singleFilterDate ?: $today) : null;
        $works = $isTodayMode
            ? $query->orderedForToday($targetDateForOrder)->get()
            : $query->orderedForDailyWork()->get();

        $stats = $this->getStats($isTodayMode ? ($singleFilterDate ?: $today) : null, $request);

        // Grafik Pekerjaan per Perusahaan menggunakan kumpulan data yang sesuai dengan mode/tabel saat ini
        $companyDistribution = $this->getCompanyDistribution($works);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'works' => $works,
                'stats' => $stats,
                'company_distribution' => $companyDistribution,
                'today_date' => $today,
                'companies' => $companies,
                'filter_companies' => $filterCompanies,
                'sikas' => $sikkas,
                'sikkas' => $sikkas,
            ]);
        }

        return view('daily-work.index', compact(
            'works',
            'stats',
            'companyDistribution',
            'companies',
            'filterCompanies',
            'companyGroups',
            'companyCategories',
            'sikkas',
            'today',
            'dateMode'
        ));
    }

    /**
     * Cek potensi pekerjaan serupa / duplikat.
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name'));
        $unit = trim((string) ($request->input('unit') ?? $request->input('company')));
        $startDate = $request->input('start_date');
        $ignoreId = $request->input('ignore_id');

        if (empty($name) || empty($unit)) {
            return response()->json(['duplicate' => false]);
        }

        $query = DailyWork::where('unit', $unit)
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                  ->orWhere('name', 'like', "%{$name}%");
            });

        if ($startDate) {
            $query->whereDate('start_date', $startDate);
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $duplicates = $query->latest('id')->take(3)->get();

        if ($duplicates->isNotEmpty()) {
            $first = $duplicates->first();
            return response()->json([
                'duplicate' => true,
                'message' => "Ditemukan {$duplicates->count()} pekerjaan serupa di {$unit} pada tanggal yang sama.",
                'data' => $first,
                'created_at' => $first->created_at ? $first->created_at->format('d/m/Y H:i') : '-',
            ]);
        }

        return response()->json(['duplicate' => false]);
    }

    /**
     * Tampilkan detail pekerjaan harian.
     */
    public function show(Request $request, $id): View|JsonResponse|RedirectResponse
    {
        $work = DailyWork::find($id);

        if (!$work) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data pekerjaan tidak ditemukan.'], 404);
            }
            return redirect()->route('daily-work.index')->with('error', 'Data pekerjaan tidak ditemukan.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'work' => $work,
            ]);
        }

        return view('daily-work.show', compact('work'));
    }

    /**
     * Pastikan pengguna memiliki hak akses Admin Management.
     */
    protected function authorizeAdminManagement(Request $request): void
    {
        $user = Auth::user();
        if (!$user || !$user->isActive() || !$user->isAdminManagement()) {
            if ($request->wantsJson() || $request->ajax()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Tindakan ini hanya dapat dilakukan oleh Admin Management.',
                ], 403));
            }
            abort(403, 'Akses ditolak. Tindakan ini hanya dapat dilakukan oleh Admin Management.');
        }
    }

    /**
     * Pastikan pengguna memiliki hak akses ekspor PDF (Admin Management & User; ditolak untuk Admin Inventory).
     */
    protected function authorizeExportPdf(Request $request): void
    {
        $user = Auth::user();
        if (!$user || !$user->isActive() || !$user->canExportPdf('daily_work')) {
            if ($request->wantsJson() || $request->ajax()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Fitur unduh / ekspor PDF tidak tersedia untuk role Anda.',
                ], 403));
            }
            abort(403, 'Akses ditolak. Fitur unduh / ekspor PDF tidak tersedia untuk role Anda.');
        }
    }

    /**
     * Tentukan perusahaan master untuk pekerjaan harian.
     *  - Pekerjaan baru: wajib perusahaan aktif di master.
     *  - Pekerjaan lama: perusahaan nonaktif / lama boleh dipertahankan jika tidak diganti.
     * Mengembalikan null hanya untuk pekerjaan lama dengan nama perusahaan yang tidak ada di master
     * dan tidak diubah (nilai lama dibiarkan apa adanya).
     */
    protected function resolveWorkCompany(string $unit, ?DailyWork $existing = null): ?DailyWorkCompany
    {
        $company = DailyWorkCompany::findByName($unit);

        $unchanged = $existing
            && ($company
                ? ($existing->company_id === $company->id || strcasecmp(trim((string) $existing->unit), $company->name) === 0)
                : strcasecmp(trim((string) $existing->unit), trim($unit)) === 0);

        if ($company && ($company->is_active || $unchanged)) {
            // Perusahaan induk (seperti GDE (All) dan JOTRE (All)) tidak boleh dipilih sebagai pelaksana pekerjaan baru.
            // Pekerjaan lama yang secara historis sudah memakai perusahaan induk tetap dipertahankan jika tidak diganti.
            if ($company->isParentCompany() && !$unchanged) {
                throw ValidationException::withMessages([
                    'unit' => "Perusahaan '{$company->name}' merupakan perusahaan induk dan tidak dapat dipilih sebagai pelaksana pekerjaan. Silakan pilih subcon atau perusahaan pelaksana yang sesuai.",
                ]);
            }

            if ($company->parent_company_id && !$unchanged) {
                $parent = $company->parent;
                if ($parent && !$parent->is_active) {
                    throw ValidationException::withMessages([
                        'unit' => "Perusahaan induk dari '{$company->name}' sedang nonaktif. Pilih perusahaan aktif dari master data.",
                    ]);
                }
            }

            return $company;
        }

        if (!$company && $unchanged) {
            return null;
        }

        throw ValidationException::withMessages([
            'unit' => 'Perusahaan tidak tersedia atau sudah nonaktif. Pilih perusahaan aktif dari master data.',
        ]);
    }

    /**
     * Normalisasi input SIKA (bisa berupa array checkbox, string koma, string tunggal, atau null).
     * Mengembalikan string koma gabungan (misal: "Ruang Terbatas, Ketinggian") atau null jika tidak ada.
     */
    protected function normalizeSikaInput(mixed $input): ?string
    {
        if (is_array($input)) {
            $items = array_values(array_unique(array_filter(array_map('trim', $input))));
            return !empty($items) ? implode(', ', $items) : null;
        }

        if (is_string($input)) {
            $trimmed = trim($input);
            if ($trimmed === '' || $trimmed === '-') {
                return null;
            }
            $items = array_values(array_unique(array_filter(array_map('trim', explode(',', $trimmed)))));
            return !empty($items) ? implode(', ', $items) : null;
        }

        return null;
    }

    /**
     * Simpan data pekerjaan harian baru.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdminManagement($request);

        // Support field 'company' alias untuk 'unit'
        if (!$request->has('unit') && $request->has('company')) {
            $request->merge(['unit' => $request->input('company')]);
        }

        // Support field 'sika' / 'sikka' (bisa berupa array checkbox, string koma, atau kosong)
        $rawSika = $request->input('sika') ?? $request->input('sikka');
        $normalizedSika = $this->normalizeSikaInput($rawSika);
        $request->merge(['sikka' => $normalizedSika]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:100',
            'sikka' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [], [
            'name' => 'Nama Pekerjaan',
            'unit' => 'Perusahaan',
            'sikka' => 'SIKA',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
            'location' => 'Lokasi Pekerjaan',
        ]);

        $company = $this->resolveWorkCompany($validated['unit']);
        $validated['unit'] = $company->name;
        $validated['company_id'] = $company->id;

        $validated['category'] = 'Umum';
        $validated['ptw_classification'] = $validated['sikka'] ?: null;
        $validated['executor'] = $validated['sikka'] ?: '-';

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $validated['status'] = ($validated['end_date'] < $today) ? DailyWork::STATUS_COMPLETED : DailyWork::STATUS_ONGOING;

        $userName = Auth::user()?->name ?? 'Administrator Dieng';

        DB::beginTransaction();
        try {
            $work = DailyWork::create($validated);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Daily Work',
                'action' => 'TAMBAH',
                'record_type' => DailyWork::class,
                'record_id' => $work->id,
                'description' => "Menambahkan pekerjaan: {$work->name} ({$work->unit} - SIKA: {$work->sikka})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pekerjaan baru berhasil ditambahkan.',
                    'work' => $work,
                    'stats' => $this->getStats(),
                    'company_distribution' => $this->getCompanyDistribution(),
                ], 201);
            }

            return redirect()->route('daily-work.index')->with('success', 'Pekerjaan baru berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'Gagal menyimpan data pekerjaan: ' . $e->getMessage());
        }
    }

    /**
     * Perbarui data pekerjaan harian.
     */
    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        $this->authorizeAdminManagement($request);

        $work = DailyWork::find($id);

        if (!$work) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data pekerjaan tidak ditemukan.'], 404);
            }
            return redirect()->route('daily-work.index')->with('error', 'Data pekerjaan tidak ditemukan.');
        }

        if (!$request->has('unit') && $request->has('company')) {
            $request->merge(['unit' => $request->input('company')]);
        }

        // Support field 'sika' / 'sikka' (bisa berupa array checkbox, string koma, atau kosong)
        $rawSika = $request->exists('sika') ? $request->input('sika')
            : ($request->exists('sikka') ? $request->input('sikka') : $work->sikka);
        $normalizedSika = $this->normalizeSikaInput($rawSika);
        $request->merge(['sikka' => $normalizedSika]);

        $mergedInput = [
            'name' => $request->input('name', $work->name),
            'unit' => $request->input('unit', $work->unit),
            'sikka' => $normalizedSika,
            'start_date' => $request->input('start_date', $work->start_date ? $work->start_date->format('Y-m-d') : null),
            'end_date' => $request->input('end_date', $work->end_date ? $work->end_date->format('Y-m-d') : null),
            'location' => $request->input('location', $work->location),
            'description' => $request->exists('description') ? $request->input('description') : $work->description,
        ];
        $request->merge($mergedInput);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:100',
            'sikka' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [], [
            'name' => 'Nama Pekerjaan',
            'unit' => 'Perusahaan',
            'sikka' => 'SIKA',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
            'location' => 'Lokasi Pekerjaan',
        ]);

        // Perusahaan wajib aktif di master, kecuali tidak diubah dari pekerjaan lama (histori tetap aman).
        $company = $this->resolveWorkCompany($validated['unit'], $work);
        if ($company) {
            $validated['unit'] = $company->name;
            $validated['company_id'] = $company->id;
        } else {
            unset($validated['unit']);
        }

        $validated['ptw_classification'] = $validated['sikka'] ?: null;
        $validated['executor'] = $validated['sikka'] ?: '-';
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        if ($work->status === DailyWork::STATUS_COMPLETED) {
            $validated['status'] = DailyWork::STATUS_COMPLETED;
        } else {
            $validated['status'] = ($validated['end_date'] < $today) ? DailyWork::STATUS_COMPLETED : DailyWork::STATUS_ONGOING;
        }

        $oldData = $work->only(array_keys($validated));
        $userName = Auth::user()?->name ?? 'Administrator Dieng';

        DB::beginTransaction();
        try {
            $work->update($validated);
            $changedKeys = array_keys($work->getChanges());
            $changes = [
                'before' => array_intersect_key($oldData, array_flip($changedKeys)),
                'after' => $work->getChanges(),
            ];

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Daily Work',
                'action' => 'UBAH',
                'record_type' => DailyWork::class,
                'record_id' => $work->id,
                'description' => "Memperbarui pekerjaan: {$work->name}",
                'changes' => $changes,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data pekerjaan berhasil diperbarui.',
                    'work' => $work->fresh(),
                    'stats' => $this->getStats(),
                    'company_distribution' => $this->getCompanyDistribution(),
                ]);
            }

            return redirect()->route('daily-work.index')->with('success', 'Data pekerjaan berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Hapus pekerjaan harian.
     */
    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        $this->authorizeAdminManagement($request);

        $work = DailyWork::find($id);

        if (!$work) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data pekerjaan tidak ditemukan.'], 404);
            }
            return redirect()->route('daily-work.index')->with('error', 'Data pekerjaan tidak ditemukan.');
        }

        $workName = $work->name;
        $workUnit = $work->unit;
        $userName = Auth::user()?->name ?? 'Administrator Dieng';

        DB::beginTransaction();
        try {
            $work->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => $userName,
                'module' => 'Daily Work',
                'action' => 'HAPUS',
                'record_type' => DailyWork::class,
                'record_id' => (int) $id,
                'description' => "Menghapus pekerjaan: {$workName} ({$workUnit})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pekerjaan berhasil dihapus.',
                    'stats' => $this->getStats(),
                    'company_distribution' => $this->getCompanyDistribution(),
                ]);
            }

            return redirect()->route('daily-work.index')->with('success', 'Pekerjaan berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
            }
            return redirect()->route('daily-work.index')->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Tandai pekerjaan harian sebagai selesai.
     */
    public function complete(Request $request, $id): JsonResponse|RedirectResponse
    {
        $this->authorizeAdminManagement($request);

        $work = DailyWork::find($id);

        if (!$work) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Data pekerjaan tidak ditemukan.'], 404);
            }
            return redirect()->route('daily-work.index')->with('error', 'Data pekerjaan tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $work->update([
                'status' => DailyWork::STATUS_COMPLETED,
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pekerjaan '{$work->name}' berhasil ditandai selesai.",
                    'work' => $work->fresh(),
                    'stats' => $this->getStats(),
                    'company_distribution' => $this->getCompanyDistribution(),
                ]);
            }

            return redirect()->route('daily-work.index')->with('success', "Pekerjaan '{$work->name}' berhasil ditandai selesai.");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyelesaikan pekerjaan: ' . $e->getMessage()], 500);
            }
            return redirect()->route('daily-work.index')->with('error', 'Gagal menyelesaikan pekerjaan: ' . $e->getMessage());
        }
    }

    /**
     * Export data pekerjaan harian ke PDF sesuai mode tampilan ('today' atau 'all') dan filter aktif.
     */
    public function export(Request $request): Response
    {
        $this->authorizeExportPdf($request);

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $dateMode = $request->input('date_mode', 'today');
        $selectedDate = $request->input('date');

        $query = $this->buildFilteredQuery($request, $today);

        $startDate = $request->filled('start_date') ? trim((string) $request->input('start_date')) : null;
        $endDate = $request->filled('end_date') ? trim((string) $request->input('end_date')) : null;
        if (!$startDate && !$endDate && $selectedDate) {
            $startDate = $selectedDate;
            $endDate = $selectedDate;
        }
        if ($startDate && $endDate && $startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
        }

        if ($dateMode === 'today') {
            $targetDate = $startDate ?: ($selectedDate ?: $today);
            $records = $query->orderedForToday($targetDate)->get();
            $formattedDate = Carbon::parse($targetDate)->locale('id')->isoFormat('D MMMM Y');
            $reportTitle = ($targetDate === $today && (!$endDate || $endDate === $today))
                ? 'LAPORAN DAILY WORK HARI INI'
                : 'LAPORAN DAILY WORK HARIAN';

            if ($startDate && $endDate && $startDate !== $endDate) {
                $startFmt = Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y');
                $endFmt = Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y');
                $filterPeriod = "Hari Ini ({$startFmt} s/d {$endFmt})";
            } else {
                $filterPeriod = ($targetDate === $today)
                    ? "Hari Ini ({$formattedDate})"
                    : "Tanggal {$formattedDate}";
            }
            $fileName = 'Laporan_Daily_Work_Harian_' . str_replace('-', '', $targetDate) . '_' . date('His') . '.pdf';
        } else {
            $records = $query->orderedForDailyWork()->get();
            $reportTitle = 'LAPORAN RIWAYAT DAILY WORK';
            if ($startDate && $endDate) {
                $startFmt = Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y');
                $endFmt = Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y');
                $filterPeriod = "Semua Riwayat ({$startFmt} s/d {$endFmt})";
            } elseif ($startDate) {
                $startFmt = Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM Y');
                $filterPeriod = "Semua Riwayat (Mulai {$startFmt})";
            } elseif ($endDate) {
                $endFmt = Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM Y');
                $filterPeriod = "Semua Riwayat (Sampai {$endFmt})";
            } elseif ($selectedDate) {
                $formattedDate = Carbon::parse($selectedDate)->locale('id')->isoFormat('D MMMM Y');
                $filterPeriod = "Semua Riwayat (Tanggal: {$formattedDate})";
            } else {
                $filterPeriod = 'Semua Riwayat Pekerjaan (Seluruh Periode)';
            }
            $fileName = 'Laporan_Daily_Work_Riwayat_' . date('Ymd_His') . '.pdf';
        }

        $compInput = $request->input('companies') ?? ($request->input('company') ?? $request->input('unit'));
        if (!empty($compInput)) {
            $compLabel = is_array($compInput) ? implode(', ', $compInput) : $compInput;
        } else {
            $compLabel = 'Semua Perusahaan';
        }
        $sikaLabel = ($request->filled('sika') || $request->filled('sikka'))
            ? ($request->input('sika') ?? $request->input('sikka'))
            : 'Semua SIKA';
        $searchLabel = $request->filled('search')
            ? ' | Pencarian: "' . trim((string) $request->input('search')) . '"'
            : '';

        $filterScope = "{$compLabel} / {$sikaLabel}{$searchLabel}";

        $pdf = Pdf::loadView('exports.daily-work-pdf', [
            'title' => 'Laporan Daily Work - HSSE Manajemen',
            'reportTitle' => $reportTitle,
            'moduleName' => 'Daily Work (Pekerjaan Harian)',
            'filterPeriod' => $filterPeriod,
            'filterScope' => $filterScope,
            'totalRecords' => $records->count(),
            'records' => $records,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($fileName);
    }

    // =========================================================================
    // FITUR CRUD MASTER DATA (PERUSAHAAN & SIKA)
    // =========================================================================

    public function getMasters(): JsonResponse
    {
        Sikka::ensureDefaults();
        DailyWorkCompany::ensureRequired();
        $sikkas = Sikka::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'companies' => $this->loadCompanies(),
            'filter_companies' => DailyWorkCompany::getActiveForFilter(),
            'sikas' => $sikkas,
            'sikkas' => $sikkas,
        ]);
    }

    /**
     * Ambil seluruh master perusahaan (aktif & nonaktif) beserta induk dan jumlah pemakaian.
     * Pemakaian dihitung dari kolom unit pada daily_works (selalu sama dengan nama master).
     */
    protected function loadCompanies(): EloquentCollection
    {
        $companies = DailyWorkCompany::with('parent')->withCount('children')->ordered()->get();

        $usage = [];
        foreach (DailyWork::selectRaw('unit, COUNT(*) as total')->groupBy('unit')->get() as $row) {
            $key = mb_strtolower(trim((string) $row->unit));
            $usage[$key] = ($usage[$key] ?? 0) + (int) $row->total;
        }

        foreach ($companies as $company) {
            $worksCount = $usage[mb_strtolower($company->name)] ?? 0;
            $company->setAttribute('works_count', $worksCount);
            $company->setAttribute('display_name', ManHoursController::toShortDisplayName($company->name));
            $company->setAttribute(
                'can_delete',
                $worksCount === 0 && (int) $company->children_count === 0
            );
        }

        return $companies;
    }

    /**
     * Validasi input master perusahaan (backend). Mengembalikan data siap simpan.
     */
    protected function validateCompanyInput(Request $request, ?DailyWorkCompany $company = null): array
    {
        $name = preg_replace('/\s+/', ' ', trim((string) $request->input('name')));
        $request->merge(['name' => $name]);

        $uniqueRule = Rule::unique('daily_work_companies', 'name');
        if ($company) {
            $uniqueRule->ignore($company->id);
        }

        // Tentukan apakah input memiliki induk (Ya / Tidak)
        $hasParentInput = $request->input('has_parent');
        $parentIdInput = $request->input('parent_company_id');
        $categoryInput = $request->input('category');

        $isSubcon = false;
        if ($hasParentInput !== null) {
            $isSubcon = filter_var($hasParentInput, FILTER_VALIDATE_BOOLEAN);
        } elseif (!empty($parentIdInput)) {
            $isSubcon = true;
        } elseif ($categoryInput === DailyWorkCompany::CATEGORY_SUBCON) {
            $isSubcon = true;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', $uniqueRule],
            'parent_company_id' => ['nullable', 'integer', 'exists:daily_work_companies,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'Nama perusahaan sudah terdaftar.',
        ], [
            'name' => 'Nama Perusahaan',
            'parent_company_id' => 'Perusahaan Induk',
            'description' => 'Keterangan',
            'is_active' => 'Status',
        ]);

        // Nama perusahaan wajib tidak boleh diubah ejaannya.
        if ($company && $company->is_required && $validated['name'] !== $company->name) {
            throw ValidationException::withMessages([
                'name' => "Nama perusahaan wajib '{$company->name}' tidak dapat diubah.",
            ]);
        }

        if ($isSubcon) {
            $parentId = $validated['parent_company_id'] ?? null;
            if (!$parentId) {
                throw ValidationException::withMessages([
                    'parent_company_id' => 'Perusahaan Induk wajib dipilih jika memiliki induk.',
                ]);
            }

            // Cegah perusahaan yang sudah punya anak dijadikan subcon (mencegah hierarki bertingkat)
            if ($company && $company->children()->exists()) {
                throw ValidationException::withMessages([
                    'parent_company_id' => 'Perusahaan ini sudah menjadi induk dari perusahaan lain dan tidak dapat dijadikan subcon (struktur hanya mendukung satu tingkat induk-subcon).',
                ]);
            }

            $parent = DailyWorkCompany::find($parentId);
            if (!$parent) {
                throw ValidationException::withMessages([
                    'parent_company_id' => 'Perusahaan Induk tidak ditemukan.',
                ]);
            }

            // Cegah memilih diri sendiri
            if ($company && $parent->id === $company->id) {
                throw ValidationException::withMessages([
                    'parent_company_id' => 'Perusahaan tidak dapat memilih dirinya sendiri sebagai induk.',
                ]);
            }

            // Induk harus aktif
            if (!$parent->is_active) {
                throw ValidationException::withMessages([
                    'parent_company_id' => 'Perusahaan induk yang dipilih harus berstatus aktif.',
                ]);
            }

            // Induk tidak boleh berupa subcon (mencegah relasi melingkar atau multi-level)
            if ($parent->parent_company_id !== null) {
                throw ValidationException::withMessages([
                    'parent_company_id' => 'Perusahaan yang sudah menjadi subcon tidak dapat dipilih sebagai induk.',
                ]);
            }

            $validated['parent_company_id'] = $parent->id;
            $validated['category'] = DailyWorkCompany::CATEGORY_SUBCON;

            // Pastikan kategori induk adalah main
            if ($parent->category !== DailyWorkCompany::CATEGORY_MAIN) {
                $parent->update(['category' => DailyWorkCompany::CATEGORY_MAIN]);
            }
        } else {
            // Tanpa induk
            if ($company && $company->children()->exists() && $request->has('category') && $request->input('category') !== DailyWorkCompany::CATEGORY_MAIN) {
                throw ValidationException::withMessages([
                    'category' => "Perusahaan '{$company->name}' masih menjadi induk dari subcon dan kategorinya harus tetap 'Perusahaan Utama'.",
                ]);
            }

            $validated['parent_company_id'] = null;
            if ($company && $company->children()->exists()) {
                $validated['category'] = DailyWorkCompany::CATEGORY_MAIN;
            } else {
                $validated['category'] = ($categoryInput === DailyWorkCompany::CATEGORY_MAIN)
                    ? DailyWorkCompany::CATEGORY_MAIN
                    : DailyWorkCompany::CATEGORY_INDEPENDENT;
            }
        }

        return $validated;
    }

    protected function logCompanyActivity(Request $request, DailyWorkCompany $company, string $action, string $description, ?array $changes = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'Administrator Dieng',
            'module' => 'Daily Work',
            'action' => $action,
            'record_type' => DailyWorkCompany::class,
            'record_id' => $company->id,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function storeCompany(Request $request): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $validated = $this->validateCompanyInput($request);

        $company = DB::transaction(function () use ($request, $validated) {
            $maxSortOrder = (int) DailyWorkCompany::max('sort_order');
            $nextSortOrder = max(1000, $maxSortOrder) + 1;

            $company = DailyWorkCompany::create([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'parent_company_id' => $validated['parent_company_id'],
                'description' => $validated['description'] ?? null,
                'is_active' => array_key_exists('is_active', $validated) && $validated['is_active'] !== null
                    ? (bool) $validated['is_active']
                    : true,
                'is_required' => false,
                'sort_order' => $nextSortOrder,
            ]);

            $this->logCompanyActivity($request, $company, 'TAMBAH', "Menambahkan perusahaan master: {$company->name}");

            return $company;
        });

        return response()->json([
            'success' => true,
            'message' => "Perusahaan '{$company->name}' berhasil ditambahkan.",
            'data' => $company,
            'companies' => $this->loadCompanies(),
            'filter_companies' => DailyWorkCompany::getActiveForFilter(),
        ]);
    }

    public function updateCompany(Request $request, $id): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $company = DailyWorkCompany::findOrFail($id);
        $validated = $this->validateCompanyInput($request, $company);

        DB::transaction(function () use ($request, $company, $validated) {
            $oldName = $company->name;
            $before = $company->only(['name', 'category', 'parent_company_id', 'is_active']);

            $company->name = $validated['name'];
            $company->category = $validated['category'];
            $company->parent_company_id = $validated['parent_company_id'];
            if ($request->has('description')) {
                $company->description = $validated['description'] ?? null;
            }
            if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
                $company->is_active = (bool) $validated['is_active'];
            }
            $company->save();

            // ID perusahaan dipertahankan. Jika nama berubah, nama pada histori ikut diperbarui
            // berdasarkan ID (bukan kemiripan nama), sehingga histori perusahaan lain tidak tergabung.
            if ($oldName !== $company->name) {
                DailyWork::where('company_id', $company->id)->update(['unit' => $company->name]);
            }

            $this->logCompanyActivity($request, $company, 'UBAH', "Memperbarui perusahaan master: {$oldName}", [
                'before' => $before,
                'after' => $company->only(['name', 'category', 'parent_company_id', 'is_active']),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Perusahaan berhasil diperbarui.',
            'data' => $company->fresh(),
            'companies' => $this->loadCompanies(),
            'filter_companies' => DailyWorkCompany::getActiveForFilter(),
        ]);
    }

    public function destroyCompany(Request $request, $id): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $company = DailyWorkCompany::findOrFail($id);

        if ($company->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Perusahaan '{$company->name}' tidak dapat dihapus karena masih menjadi induk dari subcon. Silakan hapus atau pindahkan subcon terlebih dahulu.",
            ], 422);
        }

        $usageCount = DailyWork::where('company_id', $company->id)->orWhere('unit', $company->name)->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Perusahaan '{$company->name}' tidak dapat dihapus karena masih digunakan pada {$usageCount} data pekerjaan harian. Anda dapat menonaktifkannya.",
            ], 422);
        }

        $workforceCount = Workforce::whereRaw('UPPER(unit) = ? OR UPPER(category) = ? OR UPPER(name) = ?', [
            strtoupper($company->name),
            strtoupper($company->name),
            strtoupper($company->name)
        ])->count();
        $manHourCount = ManHour::whereRaw('UPPER(unit) = ? OR UPPER(category) = ?', [
            strtoupper($company->name),
            strtoupper($company->name)
        ])->count();
        $manpowerUsage = max($workforceCount, $manHourCount);
        if ($manpowerUsage > 0) {
            return response()->json([
                'success' => false,
                'message' => "Perusahaan '{$company->name}' tidak dapat dihapus karena masih digunakan pada {$manpowerUsage} data Man Hours & Manpower. Anda dapat menonaktifkannya.",
            ], 422);
        }

        $eventUsage = Event::whereRaw('UPPER(company) = ? OR UPPER(unit) = ?', [
            strtoupper($company->name),
            strtoupper($company->name)
        ])->count();
        if ($eventUsage > 0) {
            return response()->json([
                'success' => false,
                'message' => "Perusahaan '{$company->name}' tidak dapat dihapus karena masih digunakan pada {$eventUsage} data Event Kegiatan. Anda dapat menonaktifkannya.",
            ], 422);
        }

        DB::transaction(function () use ($request, $company) {
            $this->logCompanyActivity($request, $company, 'HAPUS', "Menghapus perusahaan master: {$company->name}");
            OperationalUnit::whereRaw('UPPER(name) = ?', [strtoupper($company->name)])
                ->whereDoesntHave('workforces')
                ->whereDoesntHave('manHours')
                ->delete();
            $company->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Perusahaan '{$company->name}' berhasil dihapus.",
            'companies' => $this->loadCompanies(),
            'filter_companies' => DailyWorkCompany::getActiveForFilter(),
        ]);
    }

    public function toggleCompany(Request $request, $id): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $company = DailyWorkCompany::findOrFail($id);

        // Jika klien mengirim status eksplisit, pakai itu (hindari tumpang tindih klik); jika tidak, balikkan.
        $target = $request->has('is_active')
            ? $request->boolean('is_active')
            : !$company->is_active;

        DB::transaction(function () use ($request, $company, $target) {
            $company->is_active = $target;
            $company->save();

            $label = $target ? 'Mengaktifkan' : 'Menonaktifkan';
            $this->logCompanyActivity($request, $company, 'UBAH', "{$label} perusahaan master: {$company->name}");
        });

        $statusText = $company->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Perusahaan '{$company->name}' berhasil {$statusText}.",
            'is_active' => $company->is_active,
            'companies' => $this->loadCompanies(),
            'filter_companies' => DailyWorkCompany::getActiveForFilter(),
        ]);
    }

    public function storeSikka(Request $request): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $name = preg_replace('/\s+/', ' ', trim((string) $request->input('name')));
        $request->merge(['name' => $name]);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:sikkas,name',
            'is_active' => 'nullable|boolean',
        ], [], [
            'name' => 'Nama SIKA',
            'is_active' => 'Status',
        ]);

        $sikka = Sikka::create([
            'name' => $validated['name'],
            'is_active' => array_key_exists('is_active', $validated) && $validated['is_active'] !== null
                ? (bool) $validated['is_active']
                : true,
        ]);

        $allSikas = Sikka::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => "SIKA '{$sikka->name}' berhasil ditambahkan.",
            'data' => $sikka,
            'sikas' => $allSikas,
            'sikkas' => $allSikas,
        ]);
    }

    public function updateSikka(Request $request, $id): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $sikka = Sikka::findOrFail($id);
        $name = preg_replace('/\s+/', ' ', trim((string) $request->input('name')));
        $request->merge(['name' => $name]);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:sikkas,name,' . $id,
            'is_active' => 'nullable|boolean',
        ], [], [
            'name' => 'Nama SIKA',
            'is_active' => 'Status',
        ]);

        $oldName = $sikka->name;
        $newName = $validated['name'];
        $updateData = ['name' => $newName];
        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $updateData['is_active'] = (bool) $validated['is_active'];
        }
        $sikka->update($updateData);

        // Perbarui riwayat pada pekerjaan yang memakai SIKA lama
        if ($oldName !== $newName) {
            DailyWork::where('sikka', $oldName)->update([
                'sikka' => $newName,
                'ptw_classification' => $newName,
            ]);

            $multipleWorks = DailyWork::where('sikka', 'like', "%{$oldName}%")
                ->where('sikka', '!=', $oldName)
                ->get();
            foreach ($multipleWorks as $w) {
                $parts = array_map('trim', explode(',', (string) $w->sikka));
                $replaced = array_map(fn($p) => ($p === $oldName) ? $newName : $p, $parts);
                $w->update([
                    'sikka' => implode(', ', array_unique($replaced)),
                ]);
            }
        }

        $allSikas = Sikka::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'SIKA berhasil diperbarui.',
            'data' => $sikka,
            'sikas' => $allSikas,
            'sikkas' => $allSikas,
        ]);
    }

    public function destroySikka($id): JsonResponse
    {
        $this->authorizeAdminManagement(request());

        $sikka = Sikka::findOrFail($id);
        $sikkaName = $sikka->name;
        $usageCount = DailyWork::where(function ($q) use ($sikkaName) {
            $q->where('sikka', $sikkaName)
              ->orWhere('sikka', 'like', "%{$sikkaName}%");
        })->count();

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "SIKA '{$sikka->name}' tidak dapat dihapus karena masih digunakan pada {$usageCount} data pekerjaan. Anda dapat menonaktifkannya.",
            ], 422);
        }

        $sikka->delete();

        $allSikas = Sikka::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => "SIKA '{$sikka->name}' berhasil dihapus.",
            'sikas' => $allSikas,
            'sikkas' => $allSikas,
        ]);
    }

    public function toggleSikka(Request $request, $id): JsonResponse
    {
        $this->authorizeAdminManagement($request);

        $sikka = Sikka::findOrFail($id);
        $target = $request->has('is_active')
            ? $request->boolean('is_active')
            : !$sikka->is_active;

        $sikka->is_active = $target;
        $sikka->save();

        $statusText = $sikka->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "SIKA '{$sikka->name}' berhasil {$statusText}.",
            'is_active' => $sikka->is_active,
            'sikas' => Sikka::orderBy('id')->get(),
            'sikkas' => Sikka::orderBy('id')->get(),
        ]);
    }
}
