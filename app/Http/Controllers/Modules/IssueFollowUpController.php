<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueFollowUpRequest;
use App\Http\Requests\UpdateIssueFollowUpRequest;
use App\Models\ActivityLog;
use App\Models\FindingSource;
use App\Models\IssueDocumentation;
use App\Models\IssueFollowUp;
use App\Models\IssueFollowUpLog;
use App\Services\IssueFollowUpService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueFollowUpController extends Controller
{
    public function __construct(
        protected IssueFollowUpService $service
    ) {}

    /**
     * Tampilkan halaman utama Issue & Follow Up beserta statistik, grafik, highlight terbaru, dan tabel.
     */
    public function index(Request $request): View|JsonResponse
    {
        $activeDivision = trim((string) $request->input('hsse_division', $request->input('division', '')));
        if ($activeDivision !== '') {
            $activeDivision = IssueFollowUp::normalizeDivisionCode($activeDivision) ?? $activeDivision;
        }

        $query = $this->service->buildFilteredQuery($request);
        $allFiltered = (clone $query)->get();

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 15, 25, 50, 100], true) ? $perPage : 10;
        $issues = $query->paginate($perPage)->withQueryString();

        // Statistik untuk 3 kartu atas (All / Fungsi terpilih, Open, Closed)
        // Dihitung dari query tanpa filter status agar angka Open dan Closed tetap akurat saat user mengklik kartu Open/Closed
        $reqWithoutStatus = Request::create('/issue-follow-up', 'GET', collect($request->query())->except(['status'])->all());
        $baseWithoutStatus = $this->service->buildFilteredQuery($reqWithoutStatus)->get();
        $stats = $this->service->calculateStats($baseWithoutStatus);

        // Ringkasan 4 Grafik Ring Fungsi Safeguard (dari data tanpa filter fungsi & status agar keempat grafik selalu utuh)
        $reqWithoutDiv = Request::create('/issue-follow-up', 'GET', collect($request->query())->except(['hsse_division', 'division', 'hsse_divisions', 'status'])->all());
        $baseWithoutDiv = $this->service->buildFilteredQuery($reqWithoutDiv)->get();
        $divisionSummary = $this->service->calculateDivisionSummary($baseWithoutDiv);

        // Data Grafik dari database
        $charts = $this->service->buildChartData($allFiltered);
        $trendChart = $charts['trendChart'];
        $statusChart = $charts['statusChart'];
        $categoryChart = $charts['categoryChart'];
        $divisionChart = $charts['divisionChart'];

        $latestHighlights = $this->service->getActiveHighlights(24, $activeDivision ?: null);

        // Pilihan dropdown Sumber Temuan dari tabel master finding_sources
        $sources = $this->service->getActiveSources();
        $dbCategories = IssueFollowUp::select('category')->distinct()->pluck('category')->filter()->values()->all();
        $categories = array_values(array_unique(array_merge($sources, $dbCategories)));

        $dbUnits = IssueFollowUp::select('unit')->distinct()->pluck('unit')->filter()->values()->all();
        $units = array_values(array_unique(array_merge(IssueFollowUp::UNITS, $dbUnits)));

        $pics = IssueFollowUp::select('pic')->distinct()->orderBy('pic')->pluck('pic')->filter()->values()->all();
        $priorities = IssueFollowUp::PRIORITIES;
        $statuses = IssueFollowUp::STATUSES;
        $divisions = IssueFollowUp::HSSE_DIVISIONS;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'active_division' => $activeDivision,
                'issues' => $issues->items(),
                'all_filtered' => $allFiltered->values(),
                'latest_highlights' => $latestHighlights->values(),
                'pagination' => [
                    'current_page' => $issues->currentPage(),
                    'last_page' => $issues->lastPage(),
                    'per_page' => $issues->perPage(),
                    'total' => $issues->total(),
                ],
                'stats' => $stats,
                'divisionSummary' => $divisionSummary,
                'trendChart' => $trendChart,
                'statusChart' => $statusChart,
                'categoryChart' => $categoryChart,
                'divisionChart' => $divisionChart,
                'sources' => $sources,
            ]);
        }

        return view('data.issue-follow-up.index', compact(
            'issues',
            'allFiltered',
            'latestHighlights',
            'stats',
            'divisionSummary',
            'activeDivision',
            'divisions',
            'trendChart',
            'statusChart',
            'categoryChart',
            'divisionChart',
            'sources',
            'categories',
            'units',
            'pics',
            'priorities',
            'statuses'
        ));
    }

    /**
     * Halaman form tambah temuan baru.
     */
    public function create(): View
    {
        $divisions = IssueFollowUp::HSSE_DIVISIONS;
        $sources = $this->service->getActiveSources();
        $categories = $sources;
        $units = IssueFollowUp::UNITS;
        $priorities = IssueFollowUp::PRIORITIES;
        $statuses = IssueFollowUp::STATUSES;

        return view('data.issue-follow-up.create', compact(
            'divisions',
            'categories',
            'sources',
            'units',
            'priorities',
            'statuses'
        ));
    }

    /**
     * Simpan temuan baru ke database MySQL.
     */
    public function store(StoreIssueFollowUpRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $issue = $this->service->createIssue(
                $request->validated(),
                $request->file('attachment'),
                $request
            );

            session()->flash('success', "Temuan [{$issue->issue_code}] \"{$issue->title}\" berhasil ditambahkan.");

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Temuan HSSE baru berhasil disimpan ke database.',
                    'redirect' => route('issue-follow-up.index'),
                    'issue' => $issue,
                    'latest_highlights' => $this->service->getActiveHighlights(24)->values(),
                    'stats' => $this->service->calculateStats(IssueFollowUp::all()),
                ], 201);
            }

            return redirect()->route('issue-follow-up.index')
                ->with('success', "Temuan [{$issue->issue_code}] \"{$issue->title}\" berhasil ditambahkan.");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan temuan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal menyimpan temuan: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail temuan beserta riwayat tindak lanjut secara kronologis.
     */
    public function show(Request $request, $id): View|JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::with(['logs', 'chronologicalLogs', 'documentations'])->find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data temuan tidak ditemukan.',
                ], 404);
            }

            return redirect()->route('issue-follow-up.index')
                ->with('error', 'Data temuan tidak ditemukan.');
        }

        $activities = ActivityLog::where('module', 'Issue & Follow Up')
            ->where('record_id', $issue->id)
            ->latest('id')
            ->take(20)
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'issue' => $issue,
                'logs' => $issue->logs,
                'chronological_logs' => $issue->chronologicalLogs,
                'documentations' => $issue->documentations,
                'activities' => $activities,
            ]);
        }

        return view('data.issue-follow-up.show', compact('issue', 'activities'));
    }

    /**
     * Halaman form edit temuan.
     */
    public function edit($id): View|RedirectResponse
    {
        $issue = IssueFollowUp::with(['logs', 'documentations'])->find($id);

        if (!$issue) {
            return redirect()->route('issue-follow-up.index')
                ->with('error', 'Data temuan tidak ditemukan.');
        }

        $divisions = IssueFollowUp::HSSE_DIVISIONS;
        $sources = $this->service->getActiveSources();
        $categories = array_values(array_unique(array_merge($sources, [$issue->category])));
        $units = array_values(array_unique(array_merge(IssueFollowUp::UNITS, [$issue->unit])));
        $priorities = IssueFollowUp::PRIORITIES;
        $statuses = IssueFollowUp::STATUSES;

        return view('data.issue-follow-up.edit', compact(
            'issue',
            'divisions',
            'categories',
            'sources',
            'units',
            'priorities',
            'statuses'
        ));
    }

    /**
     * Perbarui data temuan di MySQL.
     */
    public function update(UpdateIssueFollowUpRequest $request, $id): JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data temuan tidak ditemukan.',
                ], 404);
            }

            return redirect()->route('issue-follow-up.index')
                ->with('error', 'Data temuan tidak ditemukan.');
        }

        try {
            $updated = $this->service->updateIssue(
                $issue,
                $request->validated(),
                $request->file('attachment'),
                $request
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data temuan berhasil diperbarui.',
                    'issue' => $updated,
                    'latest_highlights' => $this->service->getActiveHighlights(24)->values(),
                    'stats' => $this->service->calculateStats(IssueFollowUp::all()),
                ]);
            }

            return redirect()->route('issue-follow-up.show', $updated->id)
                ->with('success', "Data temuan [{$updated->issue_code}] berhasil diperbarui.");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui temuan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui temuan: ' . $e->getMessage());
        }
    }

    /**
     * Tambahkan catatan tindak lanjut (Follow Up) tanpa menimpa riwayat sebelumnya.
     */
    public function addFollowUp(Request $request, $id): JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data temuan tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('issue-follow-up.index')->with('error', 'Data temuan tidak ditemukan.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in(['Open', 'In Progress', 'Closed', 'Close'])],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:5120'],
        ], [
            'note.required' => 'Catatan tindak lanjut wajib diisi.',
        ]);

        try {
            $log = $this->service->addFollowUp(
                $issue,
                $validated,
                $request->file('attachment'),
                $request
            );

            $freshIssue = $issue->fresh(['logs', 'chronologicalLogs']);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tindak lanjut berhasil ditambahkan dan status temuan telah diperbarui.',
                    'log' => $log,
                    'issue' => $freshIssue,
                    'latest_highlights' => $this->service->getActiveHighlights(24)->values(),
                    'stats' => $this->service->calculateStats(IssueFollowUp::all()),
                ], 201);
            }

            return back()->with('success', 'Catatan tindak lanjut berhasil disimpan.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan tindak lanjut: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menyimpan tindak lanjut: ' . $e->getMessage());
        }
    }

    /**
     * Tutup temuan (tandai Closed) setelah penyelesaian dicatat.
     */
    public function close(Request $request, $id): JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data temuan tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('issue-follow-up.index')->with('error', 'Data temuan tidak ditemukan.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        try {
            $updated = $this->service->changeStatus(
                $issue,
                'Closed',
                $validated['note'] ?? 'Temuan telah diselesaikan dan ditutup (Closed).',
                $request
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Temuan berhasil ditutup (Closed).',
                    'issue' => $updated,
                    'latest_highlights' => $this->service->getActiveHighlights(24)->values(),
                    'stats' => $this->service->calculateStats(IssueFollowUp::all()),
                ]);
            }

            return back()->with('success', "Temuan [{$updated->issue_code}] berhasil ditutup (Closed).");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menutup temuan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menutup temuan: ' . $e->getMessage());
        }
    }

    /**
     * Buka kembali temuan yang sudah Closed melalui aksi khusus yang tervalidasi.
     */
    public function reopen(Request $request, $id): JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data temuan tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('issue-follow-up.index')->with('error', 'Data temuan tidak ditemukan.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in(['Open', 'In Progress'])],
        ], [
            'note.required' => 'Alasan membuka kembali temuan wajib diisi.',
        ]);

        try {
            $targetStatus = $validated['status'] ?? 'In Progress';
            $updated = $this->service->changeStatus(
                $issue,
                $targetStatus,
                $validated['note'],
                $request
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Temuan berhasil dibuka kembali dengan status {$updated->status}.",
                    'issue' => $updated,
                    'latest_highlights' => $this->service->getActiveHighlights(24)->values(),
                    'stats' => $this->service->calculateStats(IssueFollowUp::all()),
                ]);
            }

            return back()->with('success', "Temuan [{$updated->issue_code}] berhasil dibuka kembali.");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuka kembali temuan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal membuka kembali temuan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus temuan beserta riwayatnya dari database.
     */
    public function destroy(Request $request, $id): JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data temuan tidak ditemukan.',
                ], 404);
            }
            return redirect()->route('issue-follow-up.index')->with('error', 'Data temuan tidak ditemukan.');
        }

        try {
            $code = $issue->issue_code;
            $this->service->deleteIssue($issue, $request);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Data temuan [{$code}] berhasil dihapus.",
                    'latest_highlights' => $this->service->getActiveHighlights(24)->values(),
                    'stats' => $this->service->calculateStats(IssueFollowUp::all()),
                ]);
            }

            return redirect()->route('issue-follow-up.index')
                ->with('success', "Data temuan [{$code}] berhasil dihapus.");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus temuan: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal menghapus temuan: ' . $e->getMessage());
        }
    }

    /**
     * Fitur Add Documentation: Unggah satu atau lebih file dokumentasi untuk temuan.
     */
    public function storeDocumentation(Request $request, $id): JsonResponse|RedirectResponse
    {
        $issue = IssueFollowUp::find($id);

        if (!$issue) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Temuan tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Data temuan tidak ditemukan.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:15360'],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [
            'file.required' => 'Pilih file dokumentasi yang akan diunggah.',
            'file.mimes' => 'Format file yang diperbolehkan: JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX.',
            'file.max' => 'Ukuran file dokumentasi maksimal 15 MB.',
        ]);

        try {
            $doc = $this->service->addDocumentation(
                $issue,
                $request->file('file'),
                $request->input('caption'),
                $request
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Dokumentasi \"{$doc->original_name}\" berhasil ditambahkan.",
                    'documentation' => $doc,
                ], 201);
            }

            return back()->with('success', "Dokumentasi \"{$doc->original_name}\" berhasil ditambahkan.");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengunggah dokumentasi: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Gagal mengunggah dokumentasi: ' . $e->getMessage());
        }
    }

    /**
     * Hapus file dokumentasi temuan.
     */
    public function destroyDocumentation(Request $request, $docId): JsonResponse|RedirectResponse
    {
        $doc = IssueDocumentation::find($docId);

        if (!$doc) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Dokumentasi tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Dokumentasi tidak ditemukan.');
        }

        try {
            $name = $doc->original_name;
            $this->service->deleteDocumentation($doc, $request);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Dokumentasi \"{$name}\" berhasil dihapus.",
                ]);
            }

            return back()->with('success', "Dokumentasi \"{$name}\" berhasil dihapus.");
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal menghapus dokumentasi: ' . $e->getMessage());
        }
    }

    /**
     * Lihat / pratinjau file dokumentasi di browser (inline).
     */
    public function viewDocumentation($docId): Response|BinaryFileResponse|RedirectResponse
    {
        $doc = IssueDocumentation::find($docId);

        if (!$doc) {
            abort(404, 'Data dokumentasi tidak ditemukan.');
        }

        $filePath = ltrim((string) $doc->file_path, '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            abort(404, 'Path dokumentasi tidak valid.');
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                $fullPath = $disk->path($filePath);
                $mime = $doc->mime_type ?: ($disk->mimeType($filePath) ?: 'application/octet-stream');
                $safeName = str_replace(['"', "\r", "\n", '\\', '/'], '_', (string) $doc->original_name);

                return response()->file($fullPath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . $safeName . '"',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        return back()->with('error', "File fisik \"{$doc->original_name}\" tidak ditemukan pada server penyimpanan.");
    }

    /**
     * Unduh file dokumentasi.
     */
    public function downloadDocumentation($docId): BinaryFileResponse|RedirectResponse
    {
        $doc = IssueDocumentation::find($docId);

        if (!$doc) {
            return back()->with('error', 'Data dokumentasi tidak ditemukan.');
        }

        $filePath = ltrim((string) $doc->file_path, '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            return back()->with('error', 'Path dokumentasi tidak valid.');
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($filePath)) {
                return response()->download(
                    $disk->path($filePath),
                    $doc->original_name,
                    [
                        'Content-Type' => $doc->mime_type ?: 'application/octet-stream',
                        'X-Content-Type-Options' => 'nosniff',
                    ]
                );
            }
        }

        return back()->with('error', "File fisik \"{$doc->original_name}\" tidak ditemukan pada server penyimpanan.");
    }

    /**
     * Sajikan lampiran utama temuan secara terautentikasi.
     */
    public function viewAttachment(IssueFollowUp $issueFollowUp): BinaryFileResponse
    {
        return $this->serveStoredAttachment($issueFollowUp->attachment_path);
    }

    /**
     * Sajikan lampiran bukti tindak lanjut (log) secara terautentikasi.
     */
    public function viewLogAttachment($logId): BinaryFileResponse
    {
        $log = IssueFollowUpLog::findOrFail($logId);
        return $this->serveStoredAttachment($log->attachment_path);
    }

    private function serveStoredAttachment(?string $rawPath): BinaryFileResponse
    {
        $filePath = ltrim((string) $rawPath, '/');
        if ($filePath === '' || str_contains($filePath, '..')) {
            abort(404, 'Lampiran tidak ditemukan.');
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

        if (str_starts_with($filePath, 'uploads/issue-follow-up/')) {
            $legacyFull = public_path($filePath);
            if (is_file($legacyFull)) {
                $safeName = str_replace(['"', "\r", "\n", '\\', '/'], '_', basename($legacyFull));
                return response()->file($legacyFull, [
                    'Content-Disposition' => 'inline; filename="' . $safeName . '"',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404, 'File lampiran tidak ditemukan.');
    }

    /**
     * Tambahkan sumber temuan baru melalui antarmuka website (AJAX / Form).
     */
    public function storeSource(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama sumber temuan wajib diisi.',
        ]);

        try {
            $source = $this->service->addFindingSource($validated['name'], $validated['description'] ?? null);
            $allSources = $this->service->getActiveSources();

            return response()->json([
                'success' => true,
                'message' => "Sumber temuan \"{$source->name}\" berhasil ditambahkan dan langsung tersedia.",
                'source' => $source,
                'sources' => $allSources,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan sumber temuan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus sumber temuan dari master data.
     */
    public function destroySource(Request $request, $nameOrId): JsonResponse
    {
        try {
            $source = FindingSource::where('id', $nameOrId)
                ->orWhere('name', $nameOrId)
                ->first();

            if ($source) {
                $sourceName = $source->name;
                $source->delete();
            } else {
                $sourceName = $nameOrId;
            }

            return response()->json([
                'success' => true,
                'message' => "Sumber temuan \"{$sourceName}\" berhasil dihapus dari opsi.",
                'sources' => $this->service->getActiveSources(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus sumber temuan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dapatkan daftar sumber temuan aktif (JSON).
     */
    public function getSources(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'sources' => $this->service->getActiveSources(),
        ]);
    }

    /**
     * Ekspor daftar temuan ke format PDF sesuai filter aktif atau pilihan Fungsi Safeguard.
     */
    public function export(Request $request): Response
    {
        $records = $this->service->buildFilteredQuery($request)->get();

        $divisionLabels = [];
        $divisionsArray = $request->input('hsse_divisions');
        if (is_array($divisionsArray) && !empty(array_filter($divisionsArray))) {
            foreach (array_filter($divisionsArray) as $divItem) {
                $code = IssueFollowUp::normalizeDivisionCode((string) $divItem);
                if ($code && isset(IssueFollowUp::HSSE_DIVISIONS[$code])) {
                    $divisionLabels[] = IssueFollowUp::HSSE_DIVISIONS[$code];
                }
            }
        } else {
            $division = $request->input('hsse_division', $request->input('division'));
            if ($division) {
                $code = IssueFollowUp::normalizeDivisionCode((string) $division);
                if ($code && isset(IssueFollowUp::HSSE_DIVISIONS[$code])) {
                    $divisionLabels[] = IssueFollowUp::HSSE_DIVISIONS[$code];
                }
            }
        }

        $divisionLabel = !empty($divisionLabels) ? implode(', ', array_unique($divisionLabels)) : 'Semua Fungsi Safeguard';

        $pdf = Pdf::loadView('exports.issue-follow-up-pdf', [
            'reportTitle' => 'LAPORAN TEMUAN & TINDAK LANJUT (ISSUE & FOLLOW UP)',
            'moduleName' => 'Issue & Follow Up',
            'filterInfo' => [
                'Fungsi Safeguard' => $divisionLabel,
                'Sumber Temuan' => $request->input('category', 'Semua'),
                'Status' => $request->input('status', 'Semua'),
                'Pencarian' => $request->input('search') ?: '-',
            ],
            'records' => $records,
            'userName' => auth()->user()?->name ?? 'Administrator',
            'generatedAt' => now()->translatedFormat('d F Y, H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Laporan-Issue-FollowUp-' . now()->format('Ymd-His') . '.pdf');
    }
}

