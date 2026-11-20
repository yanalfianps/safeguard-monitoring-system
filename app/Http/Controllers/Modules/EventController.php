<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventCompany;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventController extends Controller
{
    /**
     * Parse date range from request.
     */
    protected function parseDateRange(Request $request): array
    {
        $startDate = null;
        $endDate = null;

        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                try {
                    $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                } catch (\Exception) {}
            }
            if ($request->filled('end_date')) {
                try {
                    $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                } catch (\Exception) {}
            }
        } elseif ($request->filled('date_range')) {
            $raw = trim((string) $request->input('date_range'));
            $dates = preg_split('/\s+(?:-|to|s\/d)\s+/i', $raw);
            if (is_array($dates) && count($dates) === 2) {
                try {
                    $startDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                } catch (\Exception) {
                    try { $startDate = Carbon::parse(trim($dates[0]))->startOfDay(); } catch (\Exception) {}
                }
                try {
                    $endDate = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                } catch (\Exception) {
                    try { $endDate = Carbon::parse(trim($dates[1]))->endOfDay(); } catch (\Exception) {}
                }
            }
        }

        return [$startDate, $endDate];
    }

    /**
     * Apply standard filters to events query.
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        [$startDate, $endDate] = $this->parseDateRange($request);
        $hasRange = Event::hasDateRangeColumns();

        if ($startDate && $endDate) {
            $startStr = $startDate->format('Y-m-d');
            $endStr = $endDate->format('Y-m-d');
            if ($hasRange) {
                $query->where(function ($q) use ($startStr, $endStr) {
                    $q->whereBetween('event_date', [$startStr, $endStr])
                      ->orWhere(function ($sq) use ($startStr, $endStr) {
                          $sq->whereNotNull('start_date')
                             ->where('start_date', '<=', $endStr)
                             ->where('end_date', '>=', $startStr);
                      });
                });
            } else {
                $query->whereBetween('event_date', [$startStr, $endStr]);
            }
        } elseif ($startDate) {
            $startStr = $startDate->format('Y-m-d');
            if ($hasRange) {
                $query->where(function ($q) use ($startStr) {
                    $q->whereDate('event_date', '>=', $startStr)
                      ->orWhere(function ($sq) use ($startStr) {
                          $sq->whereNotNull('end_date')->whereDate('end_date', '>=', $startStr);
                      });
                });
            } else {
                $query->whereDate('event_date', '>=', $startStr);
            }
        } elseif ($endDate) {
            $endStr = $endDate->format('Y-m-d');
            if ($hasRange) {
                $query->where(function ($q) use ($endStr) {
                    $q->whereDate('event_date', '<=', $endStr)
                      ->orWhere(function ($sq) use ($endStr) {
                          $sq->whereNotNull('start_date')->whereDate('start_date', '<=', $endStr);
                      });
                });
            } else {
                $query->whereDate('event_date', '<=', $endStr);
            }
        }

        if ($request->filled('status') && !in_array(strtolower($request->input('status')), ['all', 'semua', ''])) {
            $query->where('status', $request->input('status'));
        }

        // Dukungan filter multiple checklist Kategori / Fungsi Safeguard
        if ($request->has('safeguard_functions') && is_array($request->input('safeguard_functions'))) {
            $selectedFunctions = array_values(array_filter($request->input('safeguard_functions'), fn($v) => !empty($v) && !in_array(strtolower((string) $v), ['all', 'semua'])));
            if (!empty($selectedFunctions)) {
                $query->where(function ($q) use ($selectedFunctions) {
                    foreach ($selectedFunctions as $sf) {
                        $q->orWhere('safeguard_function', 'like', "%{$sf}%");
                    }
                });
            }
        } elseif ($request->filled('safeguard_function') && !in_array(strtolower((string) $request->input('safeguard_function')), ['all', 'semua', 'semua fungsi safeguard', ''])) {
            $sf = $request->input('safeguard_function');
            $query->where('safeguard_function', 'like', "%{$sf}%");
        }

        // Dukungan filter Perusahaan / Instansi (single / checklist)
        if ($request->has('companies') && is_array($request->input('companies'))) {
            $selectedCompanies = array_values(array_filter($request->input('companies'), fn($v) => !empty($v) && !in_array(strtolower((string) $v), ['all', 'semua'])));
            if (!empty($selectedCompanies)) {
                $query->whereIn('company', $selectedCompanies);
            }
        } elseif ($request->filled('company') && !in_array(strtolower((string) $request->input('company')), ['all', 'semua', ''])) {
            $query->where('company', $request->input('company'));
        }

        if ($request->filled('category') && !in_array(strtolower($request->input('category')), ['all', 'semua', ''])) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('unit') && !in_array(strtolower($request->input('unit')), ['all', 'semua', ''])) {
            $query->where('unit', $request->input('unit'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('safeguard_function', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('pic', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Display a listing of events with 4 metric cards and 6-month trend chart.
     */
    public function index(Request $request): View|JsonResponse
    {
        EventCompany::ensureDefaults();
        Event::syncAutomaticStatuses();

        // 1. Base query with eager loaded relationships
        $query = Event::query()->withCount('documents')->with(['documents']);
        $this->applyFilters($query, $request);

        // 2. Metrics calculation
        $statsQuery = clone $query;
        $now = Carbon::now('Asia/Jakarta');

        $totalKegiatan = (clone $statsQuery)->count();
        $selesai = (clone $statsQuery)->where('status', Event::STATUS_SELESAI)->count();
        $berlangsung = (clone $statsQuery)->where('status', 'Berlangsung')->count();
        $direncanakan = (clone $statsQuery)->where('status', 'Direncanakan')->count();
        $dibatalkan = (clone $statsQuery)->where('status', 'Dibatalkan')->count();
        $selesaiParticipants = (int) (clone $statsQuery)->where('status', Event::STATUS_SELESAI)->sum('participants');

        $currentYear = $now->year;
        $tahunIni = (clone $statsQuery)->whereYear('event_date', $currentYear)->count();
        $currentMonth = $now->month;
        $bulanIni = (clone $statsQuery)
            ->whereYear('event_date', $currentYear)
            ->whereMonth('event_date', $currentMonth)
            ->count();

        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY);
        $mingguIni = (clone $statsQuery)
            ->whereBetween('event_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->count();

        $hariIni = (clone $statsQuery)
            ->whereDate('event_date', $now->toDateString())
            ->count();

        // Label detail periode untuk 4 Card Ringkasan
        $periodeTahunIni = '(' . $currentYear . ')';
        $periodeBulanIni = '(' . $now->translatedFormat('F Y') . ')';
        $periodeMingguIni = '(' . $weekStart->translatedFormat('d M') . ' - ' . $weekEnd->translatedFormat('d M Y') . ')';
        $periodeHariIni = '(' . $now->translatedFormat('d F Y') . ')';

        // 3. Mini Chart: 6-Month Trend (Urutan kronologis 5 bulan lalu -> bulan ini)
        $trendMonths = [];
        $trendCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now('Asia/Jakarta')->subMonths($i);
            $trendMonths[] = $m->translatedFormat('M');
            $c = Event::query()
                ->whereYear('event_date', $m->year)
                ->whereMonth('event_date', $m->month)
                ->count();
            $trendCounts[] = $c;
        }

        // Monthly dataset for compatibility with tests & charts
        $monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $monthlyDatasets = [];
        foreach (Event::CATEGORIES as $cat) {
            $catData = [];
            for ($m = 1; $m <= 12; $m++) {
                $catData[] = Event::query()
                    ->whereYear('event_date', $currentYear)
                    ->whereMonth('event_date', $m)
                    ->where('category', $cat)
                    ->count();
            }
            $monthlyDatasets[] = [
                'label' => $cat,
                'data' => $catData,
            ];
        }
        $monthly = [
            'labels' => $monthlyLabels,
            'datasets' => $monthlyDatasets,
        ];

        // 4. Pagination
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $events = $query->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // 5. Master Data Perusahaan / Instansi
        $companies = EventCompany::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
        $existingCompanies = Event::query()->whereNotNull('company')->where('company', '!=', '')->distinct()->pluck('company')->toArray();
        $allCompanies = array_values(array_unique(array_merge($companies, $existingCompanies)));
        sort($allCompanies);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'stats' => [
                    'total' => $totalKegiatan,
                    'selesai' => $selesai,
                    'berlangsung' => $berlangsung,
                    'direncanakan' => $direncanakan,
                    'dibatalkan' => $dibatalkan,
                    'selesai_participants' => $selesaiParticipants,
                    'tahun_ini' => $tahunIni,
                    'bulan_ini' => $bulanIni,
                    'minggu_ini' => $mingguIni,
                    'hari_ini' => $hariIni,
                ],
                'trend' => [
                    'labels' => $trendMonths,
                    'data' => $trendCounts,
                ],
                'monthly' => $monthly,
                'safeguard_functions' => Event::SAFEGUARD_FUNCTIONS,
                'categories' => Event::CATEGORIES,
                'units' => Event::UNITS,
                'companies' => $allCompanies,
                'events' => $events,
            ]);
        }

        return view('events.index', compact(
            'events',
            'totalKegiatan',
            'selesai',
            'tahunIni',
            'bulanIni',
            'mingguIni',
            'hariIni',
            'periodeTahunIni',
            'periodeBulanIni',
            'periodeMingguIni',
            'periodeHariIni',
            'trendMonths',
            'trendCounts',
            'allCompanies',
            'perPage'
        ));
    }

    /**
     * Display the specified event.
     */
    public function show($id): JsonResponse|View
    {
        Event::syncAutomaticStatuses();
        $event = Event::with(['documents', 'activityLogs'])->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            $eventArr = $event->toArray();
            $eventArr['safeguard_functions'] = $event->safeguard_functions_list;
            $eventArr['safeguard_badges'] = $event->safeguard_badges_data;
            $eventArr['formatted_date_range'] = $event->formatted_date_range;

            return response()->json([
                'success' => true,
                'event' => $eventArr,
                'safeguard_functions' => $event->safeguard_functions_list,
                'safeguard_badges' => $event->safeguard_badges_data,
                'formatted_date_range' => $event->formatted_date_range,
                'documents' => $event->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'file_name' => $doc->file_name,
                        'file_path' => $doc->file_path,
                        'file_url' => $doc->file_url,
                        'mime_type' => $doc->mime_type,
                        'file_size' => $doc->file_size,
                        'created_at' => $doc->created_at ? $doc->created_at->format('d/m/Y H:i') : null,
                    ];
                }),
                'logs' => $event->activityLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_name' => $log->user_name,
                        'action' => $log->action,
                        'description' => $log->description,
                        'created_at' => $log->created_at ? $log->created_at->format('d/m/Y H:i') : null,
                    ];
                }),
            ]);
        }

        return view('events.show', compact('event'));
    }

    /**
     * Check for duplicate event records.
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $title = trim((string) $request->input('title'));
        $eventDate = $request->input('event_date');
        $ignoreId = $request->input('ignore_id');

        if (empty($title)) {
            return response()->json(['duplicate' => false]);
        }

        $query = Event::query();

        if ($eventDate) {
            $query->whereDate('event_date', $eventDate);
        }

        $query->where(function ($q) use ($title) {
            $q->where('title', $title)
              ->orWhere('title', 'like', "%{$title}%");
        });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $duplicates = $query->latest('id')->take(3)->get();

        if ($duplicates->isNotEmpty()) {
            $first = $duplicates->first();
            $creatorLog = ActivityLog::whereIn('module', ['events', 'Events'])
                ->where('action', 'TAMBAH')
                ->where('record_id', $first->id)
                ->latest('id')
                ->first();

            return response()->json([
                'duplicate' => true,
                'message' => "Ditemukan kegiatan serupa '{$first->title}' pada tanggal yang sama (" . ($first->event_date ? $first->event_date->format('d/m/Y') : '-') . ").",
                'data' => $first,
                'created_by' => $creatorLog ? $creatorLog->user_name : ($first->created_by ?? 'Tim HSSE'),
                'created_at' => $first->created_at ? $first->created_at->format('d/m/Y H:i') : '-',
            ]);
        }

        return response()->json(['duplicate' => false]);
    }

    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $userName = Auth::user()->name ?? 'Administrator';

        // Pastikan nama instansi/perusahaan tersimpan di master
        if (!empty($validated['company'])) {
            EventCompany::firstOrCreate(
                ['name' => trim($validated['company'])],
                ['is_active' => true]
            );
        }

        DB::beginTransaction();
        try {
            $safeguardFunction = $validated['safeguard_function'] ?? 'Health & Safety';
            $startDate = $validated['start_date'] ?? $validated['event_date'];
            $endDate = $validated['end_date'] ?? $startDate;
            $company = $validated['company'];

            $requestedStatus = $request->input('status');
            $status = ($requestedStatus === Event::STATUS_DIBATALKAN)
                ? Event::STATUS_DIBATALKAN
                : (!empty($requestedStatus) ? $requestedStatus : Event::determineAutoStatus($startDate, $endDate));

            $eventData = [
                'title' => $validated['title'],
                'safeguard_function' => $safeguardFunction,
                'company' => $company,
                'category' => $validated['category'] ?? $safeguardFunction ?? 'Kegiatan',
                'unit' => $validated['unit'] ?? $company ?? 'PMU',
                'event_date' => $startDate,
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'location' => $validated['location'] ?? 'Area Operasi Dieng',
                'participants' => isset($validated['participants']) ? (int) $validated['participants'] : 0,
                'pic' => $validated['pic'] ?? $userName,
                'status' => $status,
                'description' => $validated['description'] ?? null,
                'created_by' => $userName,
                'updated_by' => $userName,
            ];
            if (Event::hasDateRangeColumns()) {
                $eventData['start_date'] = $startDate;
                $eventData['end_date'] = $endDate;
            }

            $event = Event::create($eventData);

            // Simpan berkas dokumentasi
            if ($request->hasFile('documentation_files')) {
                foreach ($request->file('documentation_files') as $file) {
                    if ($file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $storedPath = $file->store('events/documentation', 'public');
                        $mime = $file->getMimeType();
                        $size = $file->getSize();

                        Document::create([
                            'title' => pathinfo($originalName, PATHINFO_FILENAME),
                            'document_type' => str_starts_with($mime, 'image/') ? 'Foto Kegiatan' : 'Dokumen',
                            'module' => 'events',
                            'record_id' => $event->id,
                            'file_name' => $originalName,
                            'file_path' => $storedPath,
                            'mime_type' => $mime,
                            'file_size' => $size,
                            'uploaded_by' => $userName,
                            'description' => 'Dokumentasi kegiatan ' . $event->title,
                        ]);

                        if (empty($event->documentation)) {
                            $event->update(['documentation' => $storedPath]);
                        }
                    }
                }
            }

            ActivityLog::record([
                'module' => 'events',
                'action' => 'TAMBAH',
                'record_type' => Event::class,
                'record_id' => $event->id,
                'description' => "Menambahkan event baru: {$event->title} ({$event->safeguard_function})",
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kegiatan berhasil ditambahkan!',
                    'data' => $event->load('documents'),
                ], 201);
            }

            return redirect()->route('events.index')->with('success', 'Kegiatan berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menambahkan kegiatan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal menambahkan kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified event.
     */
    public function update(UpdateEventRequest $request, $id): RedirectResponse|JsonResponse
    {
        $event = Event::findOrFail($id);
        $validated = $request->validated();
        $userName = Auth::user()->name ?? 'Administrator';

        if (!empty($validated['company'])) {
            EventCompany::firstOrCreate(
                ['name' => trim($validated['company'])],
                ['is_active' => true]
            );
        }

        DB::beginTransaction();
        try {
            $safeguardFunction = $validated['safeguard_function'] ?? $event->safeguard_function ?? 'Health & Safety';
            $startDate = $validated['start_date'] ?? $validated['event_date'] ?? $event->start_date ?: $event->event_date;
            $endDate = $validated['end_date'] ?? $startDate;
            $company = $validated['company'] ?? $event->company;

            $requestedStatus = $request->input('status');
            $status = ($requestedStatus === Event::STATUS_DIBATALKAN)
                ? Event::STATUS_DIBATALKAN
                : (!empty($requestedStatus) ? $requestedStatus : Event::determineAutoStatus($startDate, $endDate, $event->status));

            $updateData = [
                'title' => $validated['title'],
                'safeguard_function' => $safeguardFunction,
                'company' => $company,
                'category' => $validated['category'] ?? $event->category ?? $safeguardFunction ?? 'Kegiatan',
                'unit' => $validated['unit'] ?? $event->unit ?? $company ?? 'PMU',
                'event_date' => $startDate,
                'start_time' => $validated['start_time'] ?? $event->start_time,
                'end_time' => $validated['end_time'] ?? $event->end_time,
                'location' => $validated['location'] ?? $event->location ?? 'Area Operasi Dieng',
                'participants' => isset($validated['participants']) ? (int) $validated['participants'] : $event->participants,
                'pic' => $validated['pic'] ?? $event->pic ?? $userName,
                'status' => $status,
                'description' => $validated['description'] ?? $event->description,
                'updated_by' => $userName,
            ];
            if (Event::hasDateRangeColumns()) {
                $updateData['start_date'] = $startDate;
                $updateData['end_date'] = $endDate;
            }

            $event->update($updateData);

            if ($request->hasFile('documentation_files')) {
                foreach ($request->file('documentation_files') as $file) {
                    if ($file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $storedPath = $file->store('events/documentation', 'public');
                        $mime = $file->getMimeType();
                        $size = $file->getSize();

                        Document::create([
                            'title' => pathinfo($originalName, PATHINFO_FILENAME),
                            'document_type' => str_starts_with($mime, 'image/') ? 'Foto Kegiatan' : 'Dokumen',
                            'module' => 'events',
                            'record_id' => $event->id,
                            'file_name' => $originalName,
                            'file_path' => $storedPath,
                            'mime_type' => $mime,
                            'file_size' => $size,
                            'uploaded_by' => $userName,
                            'description' => 'Dokumentasi kegiatan ' . $event->title,
                        ]);

                        if (empty($event->documentation)) {
                            $event->update(['documentation' => $storedPath]);
                        }
                    }
                }
            }

            ActivityLog::record([
                'module' => 'events',
                'action' => 'UBAH',
                'record_type' => Event::class,
                'record_id' => $event->id,
                'description' => "Memperbarui event: {$event->title}",
                'changes' => [
                    'after' => $event->getChanges(),
                ],
            ]);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kegiatan berhasil diperbarui!',
                    'data' => $event->load('documents'),
                ]);
            }

            return redirect()->route('events.index')->with('success', 'Kegiatan berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui kegiatan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Upload additional documentation directly to an event.
     */
    public function uploadDocumentation(Request $request, $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $request->validate([
            'files.*' => ['required', 'file', 'max:15360', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ], [
            'files.*.max' => 'Ukuran file dokumentasi maksimal 15 MB per file.',
            'files.*.mimes' => 'Format file yang didukung: JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX.',
        ]);

        $userName = Auth::user()->name ?? 'Administrator';
        $uploaded = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $storedPath = $file->store('events/documentation', 'public');
                    $mime = $file->getMimeType();
                    $size = $file->getSize();

                    $doc = Document::create([
                        'title' => pathinfo($originalName, PATHINFO_FILENAME),
                        'document_type' => str_starts_with($mime, 'image/') ? 'Foto Kegiatan' : 'Dokumen',
                        'module' => 'events',
                        'record_id' => $event->id,
                        'file_name' => $originalName,
                        'file_path' => $storedPath,
                        'mime_type' => $mime,
                        'file_size' => $size,
                        'uploaded_by' => $userName,
                        'description' => 'Upload tambahan untuk ' . $event->title,
                    ]);

                    if (empty($event->documentation)) {
                        $event->update(['documentation' => $storedPath]);
                    }

                    $uploaded[] = [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'file_name' => $doc->file_name,
                        'file_url' => $doc->file_url,
                        'mime_type' => $doc->mime_type,
                    ];
                }
            }

            ActivityLog::record([
                'module' => 'Events',
                'action' => 'UPLOAD',
                'record_type' => Event::class,
                'record_id' => $event->id,
                'description' => 'Mengunggah ' . count($uploaded) . " file dokumentasi baru untuk {$event->title}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumentasi berhasil diunggah!',
            'files' => $uploaded,
        ]);
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        $event = Event::findOrFail($id);
        $title = $event->title;

        DB::beginTransaction();
        try {
            $documents = Document::whereIn('module', ['events', 'Events'])->where('record_id', $event->id)->get();
            foreach ($documents as $doc) {
                if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                    Storage::disk('public')->delete($doc->file_path);
                }
                $doc->delete();
            }

            ActivityLog::record([
                'module' => 'events',
                'action' => 'HAPUS',
                'record_type' => Event::class,
                'record_id' => $event->id,
                'description' => "Menghapus kegiatan: {$title}",
                'changes' => [
                    'before' => ['title' => $title, 'category' => $event->category, 'unit' => $event->unit, 'company' => $event->company],
                ],
            ]);

            $event->delete();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kegiatan berhasil dihapus!',
                ]);
            }

            return redirect()->route('events.index')->with('success', 'Kegiatan berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus kegiatan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus kegiatan: ' . $e->getMessage());
        }
    }

    /**
     * Dapatkan daftar perusahaan / instansi master.
     */
    public function getCompanies(): JsonResponse
    {
        EventCompany::ensureDefaults();
        $companies = EventCompany::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
        $existing = Event::query()->whereNotNull('company')->where('company', '!=', '')->distinct()->pluck('company')->toArray();
        $all = array_values(array_unique(array_merge($companies, $existing)));
        sort($all);

        return response()->json([
            'success' => true,
            'companies' => $all,
        ]);
    }

    /**
     * Simpan perusahaan / instansi baru ke master.
     */
    public function storeCompany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ], [
            'name.required' => 'Nama perusahaan / instansi wajib diisi.',
        ]);

        $name = trim($validated['name']);
        $company = EventCompany::firstOrCreate(
            ['name' => $name],
            ['is_active' => true]
        );

        $companies = EventCompany::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

        return response()->json([
            'success' => true,
            'message' => "Instansi/Perusahaan \"{$name}\" berhasil ditambahkan.",
            'company' => $company,
            'companies' => $companies,
        ], 201);
    }

    /**
     * Hapus perusahaan / instansi master.
     */
    public function destroyCompany(Request $request, $nameOrId): JsonResponse
    {
        try {
            $company = EventCompany::where('id', $nameOrId)
                ->orWhere('name', $nameOrId)
                ->first();

            $companyName = $company ? $company->name : trim((string) $nameOrId);

            // Cek apakah instansi sedang digunakan dalam tabel events
            $inUseCount = Event::where('company', $companyName)->count();
            if ($inUseCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Instansi/Perusahaan \"{$companyName}\" sedang digunakan oleh {$inUseCount} kegiatan dan tidak dapat dihapus.",
                ], 422);
            }

            if ($company) {
                $company->delete();
            }

            $companies = EventCompany::where('is_active', true)->orderBy('name')->pluck('name')->toArray();
            $existing = Event::query()->whereNotNull('company')->where('company', '!=', '')->distinct()->pluck('company')->toArray();
            $all = array_values(array_unique(array_merge($companies, $existing)));
            sort($all);

            return response()->json([
                'success' => true,
                'message' => "Instansi/Perusahaan \"{$companyName}\" berhasil dihapus.",
                'companies' => $all,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus instansi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unduh file dokumentasi kegiatan tertentu via ID Dokumen.
     */
    public function downloadDocumentation($documentId): BinaryFileResponse|RedirectResponse
    {
        $doc = Document::whereIn('module', ['events', 'Events'])->find($documentId);

        if (!$doc) {
            return back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $disk = Storage::disk('public');
        if (empty($doc->file_path) || !$disk->exists($doc->file_path)) {
            return back()->with('error', "File fisik \"{$doc->file_name}\" tidak ditemukan di server.");
        }

        return response()->download($disk->path($doc->file_path), $doc->file_name);
    }

    /**
     * Unduh file dokumentasi utama event.
     */
    public function downloadEventDocumentation($eventId): BinaryFileResponse|RedirectResponse
    {
        $event = Event::with('documents')->findOrFail($eventId);
        $doc = $event->documents->first();

        $disk = Storage::disk('public');
        $filePath = $doc ? $doc->file_path : $event->documentation;

        if (empty($filePath) || !$disk->exists($filePath)) {
            return back()->with('error', "File dokumentasi kegiatan \"{$event->title}\" belum diunggah atau tidak ditemukan.");
        }

        $fileName = $doc ? $doc->file_name : basename($filePath);
        return response()->download($disk->path($filePath), $fileName);
    }

    /**
     * Export filtered events to PDF format.
     */
    public function export(Request $request): Response
    {
        Event::syncAutomaticStatuses();

        $query = Event::query()->withCount('documents')->with(['documents']);
        $this->applyFilters($query, $request);

        $records = $query->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        [$startDate, $endDate] = $this->parseDateRange($request);

        // Siapkan foto dokumentasi utama untuk setiap kegiatan (menggunakan Base64 Data URI yang 100% kompatibel dengan DomPDF)
        foreach ($records as $item) {
            $photoBase64 = null;
            $photoName = null;

            // 1. Cari dokumen gambar pertama dari relasi documents
            $firstImgDoc = $item->documents->first(function ($doc) {
                $ext = strtolower(pathinfo($doc->file_name ?: $doc->file_path, PATHINFO_EXTENSION));
                return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) 
                    || str_starts_with($doc->mime_type ?? '', 'image/');
            });

            $targetPath = null;
            if ($firstImgDoc && $firstImgDoc->file_path && Storage::disk('public')->exists($firstImgDoc->file_path)) {
                $targetPath = Storage::disk('public')->path($firstImgDoc->file_path);
                $photoName = $firstImgDoc->file_name ?: 'Foto Dokumentasi';
            } elseif (!empty($item->documentation) && Storage::disk('public')->exists($item->documentation)) {
                $legacyExt = strtolower(pathinfo($item->documentation, PATHINFO_EXTENSION));
                if (in_array($legacyExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $targetPath = Storage::disk('public')->path($item->documentation);
                    $photoName = basename($item->documentation);
                }
            }

            if ($targetPath && file_exists($targetPath)) {
                try {
                    $size = filesize($targetPath);
                    // Hindari kehabisan memori jika file luar biasa besar
                    if ($size > 0 && $size <= 12 * 1024 * 1024) {
                        $mime = mime_content_type($targetPath) ?: 'image/jpeg';
                        $content = file_get_contents($targetPath);
                        if ($content !== false) {
                            $photoBase64 = 'data:' . $mime . ';base64,' . base64_encode($content);
                        }
                    }
                } catch (\Throwable) {
                    $photoBase64 = null;
                }
            }

            $item->pdf_photo_data = $photoBase64;
            $item->pdf_photo_name = $photoName;
        }

        // Statistik Ringkasan Kegiatan
        $totalRecords = $records->count();
        $selesaiCount = $records->where('status', 'Selesai')->count();
        $berlangsungCount = $records->where('status', 'Berlangsung')->count();
        $direncanakanCount = $records->where('status', 'Direncanakan')->count();
        $dibatalkanCount = $records->where('status', 'Dibatalkan')->count();
        $totalDocsCount = $records->sum('documents_count');
        $photosCount = $records->whereNotNull('pdf_photo_data')->count();

        // Keterangan filter untuk header PDF
        $filterPeriod = 'Semua Periode';
        if ($startDate && $endDate) {
            $filterPeriod = $startDate->format('d/m/Y') . ' s/d ' . $endDate->format('d/m/Y');
        } elseif ($startDate) {
            $filterPeriod = 'Sejak ' . $startDate->format('d/m/Y');
        } elseif ($endDate) {
            $filterPeriod = 'Sampai ' . $endDate->format('d/m/Y');
        }

        $selectedFunctions = [];
        if ($request->has('safeguard_functions') && is_array($request->input('safeguard_functions'))) {
            $selectedFunctions = array_values(array_filter($request->input('safeguard_functions'), fn($v) => !empty($v) && !in_array(strtolower((string) $v), ['all', 'semua'])));
        } elseif ($request->filled('safeguard_function') && !in_array(strtolower((string) $request->input('safeguard_function')), ['all', 'semua', 'semua fungsi safeguard', ''])) {
            $selectedFunctions = [$request->input('safeguard_function')];
        }

        $selectedCompanies = [];
        if ($request->has('companies') && is_array($request->input('companies'))) {
            $selectedCompanies = array_values(array_filter($request->input('companies'), fn($v) => !empty($v) && !in_array(strtolower((string) $v), ['all', 'semua'])));
        } elseif ($request->filled('company') && !in_array(strtolower((string) $request->input('company')), ['all', 'semua', ''])) {
            $selectedCompanies = [$request->input('company')];
        }

        $safeguardScopeLabel = !empty($selectedFunctions) ? implode(', ', $selectedFunctions) : 'Semua Fungsi Safeguard';
        $companyScopeLabel = !empty($selectedCompanies) ? implode(', ', $selectedCompanies) : ($request->input('unit') ?: 'Semua Instansi');

        $filterScope = $companyScopeLabel;
        if (!empty($selectedFunctions)) {
            $filterScope .= ' - ' . $safeguardScopeLabel;
        }
        if ($request->filled('status') && !in_array(strtolower((string) $request->input('status')), ['all', 'semua', ''])) {
            $filterScope .= ' (' . $request->input('status') . ')';
        }

        $pdf = Pdf::loadView('exports.events-pdf', [
            'reportTitle' => 'LAPORAN DAFTAR KEGIATAN & EVENTS HSSE',
            'moduleName' => 'Events',
            'filterPeriod' => $filterPeriod,
            'filterScope' => $filterScope,
            'totalRecords' => $totalRecords,
            'selesaiCount' => $selesaiCount,
            'berlangsungCount' => $berlangsungCount,
            'direncanakanCount' => $direncanakanCount,
            'dibatalkanCount' => $dibatalkanCount,
            'totalDocsCount' => $totalDocsCount,
            'photosCount' => $photosCount,
            'filterInfo' => [
                'Fungsi Safeguard' => !empty($selectedFunctions) ? implode(', ', $selectedFunctions) : 'Semua',
                'Status' => $request->input('status', 'Semua'),
                'Perusahaan / Instansi' => !empty($selectedCompanies) ? implode(', ', $selectedCompanies) : 'Semua',
                'Tanggal Mulai' => $startDate ? $startDate->format('d/m/Y') : '-',
                'Tanggal Selesai' => $endDate ? $endDate->format('d/m/Y') : '-',
                'Pencarian' => $request->input('search') ?: '-',
            ],
            'records' => $records,
            'userName' => Auth::user()->name ?? 'Administrator',
            'generatedAt' => now()->translatedFormat('d F Y, H:i:s'),
        ])->setPaper('a4', 'landscape');

        ActivityLog::record([
            'module' => 'events',
            'action' => 'EXPORT',
            'description' => 'Mengekspor laporan PDF data Events (' . $records->count() . ' data)',
        ]);

        return $pdf->download('Laporan-Events-HSSE-' . now()->format('Ymd-His') . '.pdf');
    }
}
