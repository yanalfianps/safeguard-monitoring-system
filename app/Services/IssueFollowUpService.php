<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FindingSource;
use App\Models\IssueDocumentation;
use App\Models\IssueFollowUp;
use App\Models\IssueFollowUpLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class IssueFollowUpService
{
    /**
     * Terapkan filter pencarian dan parameter ke query IssueFollowUp.
     */
    public function buildFilteredQuery(Request $request): Builder
    {
        $query = IssueFollowUp::query()->with(['logs']);

        // Filter: Rentang Tanggal (reported_date)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('reported_date', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('reported_date', '>=', $request->input('start_date'));
        } elseif ($request->filled('end_date')) {
            $query->whereDate('reported_date', '<=', $request->input('end_date'));
        }

        // Filter: Fungsi Safeguard (hsse_divisions[] atau hsse_division / division)
        $divisionsArray = $request->input('hsse_divisions');
        if (is_array($divisionsArray) && !empty(array_filter($divisionsArray))) {
            $query->byDivision(array_filter($divisionsArray));
        } else {
            $divisionParam = $request->input('hsse_division', $request->input('division'));
            if (!empty($divisionParam)) {
                $query->byDivision($divisionParam);
            }
        }

        // Filter: Status (Open, In Progress, Closed, atau Overdue)
        if ($request->filled('status')) {
            $statusFilter = trim((string) $request->input('status'));
            if (strcasecmp($statusFilter, 'All') === 0) {
                // Jangan filter status jika All
            } elseif (strcasecmp($statusFilter, 'Overdue') === 0) {
                $query->overdue();
            } elseif (strcasecmp($statusFilter, 'Close') === 0 || strcasecmp($statusFilter, 'Closed') === 0) {
                $query->whereIn('status', ['Closed', 'Close']);
            } elseif (strcasecmp($statusFilter, 'Open') === 0) {
                $query->where('status', 'Open');
            } else {
                $query->where('status', $statusFilter);
            }
        }

        // Filter: Kategori
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter: Unit
        if ($request->filled('unit')) {
            $query->where('unit', $request->input('unit'));
        }

        // Filter: PIC
        if ($request->filled('pic')) {
            $pic = trim((string) $request->input('pic'));
            $query->where('pic', 'like', "%{$pic}%");
        }

        // Filter: Prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // Filter: Tahun / Periode
        if ($request->filled('period')) {
            $period = (string) $request->input('period');
            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                [$y, $m] = explode('-', $period);
                $query->whereYear('reported_date', (int) $y)
                      ->whereMonth('reported_date', (int) $m);
            } elseif (preg_match('/^\d{4}$/', $period)) {
                $query->whereYear('reported_date', (int) $period);
            }
        } elseif ($request->filled('year')) {
            $query->whereYear('reported_date', (int) $request->input('year'));
        }

        // Pencarian berdasarkan judul, deskripsi, kode, lokasi, PIC, catatan
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('issue_code', 'like', "%{$term}%")
                  ->orWhere('hsse_division', 'like', "%{$term}%")
                  ->orWhere('location', 'like', "%{$term}%")
                  ->orWhere('pic', 'like', "%{$term}%")
                  ->orWhere('category', 'like', "%{$term}%")
                  ->orWhere('unit', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        // Pengurutan (default: terbaru berdasarkan reported_date DESC, id DESC)
        $sort = $request->input('sort', 'date-desc');
        if ($sort === 'date-asc') {
            $query->orderBy('reported_date', 'asc')->orderBy('id', 'asc');
        } elseif ($sort === 'due-asc') {
            $query->orderByRaw('due_date IS NULL, due_date ASC')->orderBy('reported_date', 'desc');
        } elseif ($sort === 'status') {
            $query->orderBy('status', 'asc')->orderBy('reported_date', 'desc')->orderBy('id', 'desc');
        } elseif ($sort === 'priority') {
            $query->orderByRaw("FIELD(priority, 'Kritis', 'Tinggi', 'Sedang', 'Rendah')")->orderBy('reported_date', 'desc');
        } else {
            $query->orderBy('reported_date', 'desc')->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Hitung kartu statistik dari koleksi temuan.
     */
    public function calculateStats(Collection $issues): array
    {
        $total = $issues->count();
        $open = $issues->where('status', 'Open')->count();
        $inProgress = $issues->where('status', 'In Progress')->count();
        $closed = $issues->filter(fn (IssueFollowUp $i) => in_array($i->status, ['Closed', 'Close'], true))->count();
        $overdue = $issues->filter(fn (IssueFollowUp $i) => $i->isOverdue())->count();

        $openPct = $total > 0 ? (int) round(($open / $total) * 100) : 0;
        $closedPct = $total > 0 ? (int) round(($closed / $total) * 100) : 0;

        return [
            'total_issues' => $total,
            'open_issues' => $open,
            'in_progress_issues' => $inProgress,
            'closed_issues' => $closed,
            'overdue_issues' => $overdue,
            'open_pct' => $openPct,
            'closed_pct' => $closedPct,
        ];
    }

    /**
     * Hitung ringkasan per Fungsi Safeguard (Health & Safety, Security, Environment, Social).
     */
    public function calculateDivisionSummary(?Collection $allIssues = null): array
    {
        $collection = $allIssues ?? IssueFollowUp::all();

        $buildDivStats = function (Collection $subset, string $code, string $label) {
            $total = $subset->count();
            $open = $subset->where('status', 'Open')->count();
            $closed = $subset->filter(fn (IssueFollowUp $i) => in_array($i->status, ['Closed', 'Close'], true))->count();
            $openPct = $total > 0 ? (int) round(($open / $total) * 100) : 0;
            $closedPct = $total > 0 ? (int) round(($closed / $total) * 100) : 0;

            return [
                'code' => $code,
                'label' => $label,
                'total' => $total,
                'open' => $open,
                'in_progress' => $subset->where('status', 'In Progress')->count(),
                'active' => $subset->whereIn('status', IssueFollowUp::ACTIVE_STATUSES)->count(),
                'closed' => $closed,
                'overdue' => $subset->filter(fn (IssueFollowUp $i) => $i->isOverdue())->count(),
                'open_pct' => $openPct,
                'closed_pct' => $closedPct,
            ];
        };

        $hItems = $collection->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'H');
        $sSecurityItems = $collection->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'S_SECURITY');
        $eItems = $collection->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'E');
        $sSocialItems = $collection->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'S_SOCIAL');
        $unclassifiedItems = $collection->filter(fn (IssueFollowUp $i) => empty($i->hsse_division));

        $sSocialStats = $buildDivStats($sSocialItems, 'S_SOCIAL', 'Social');

        return [
            'all' => $buildDivStats($collection, '', 'All'),
            'H' => $buildDivStats($hItems, 'H', 'Health & Safety'),
            'S_SECURITY' => $buildDivStats($sSecurityItems, 'S_SECURITY', 'Security'),
            'E' => $buildDivStats($eItems, 'E', 'Environment'),
            'S_SOCIAL' => $sSocialStats,
            'S' => $sSocialStats, // Backward-compatibility alias
            'unclassified' => $buildDivStats($unclassifiedItems, 'unclassified', 'Belum Diklasifikasikan'),
        ];
    }

    /**
     * Ambil daftar temuan aktif (Open & In Progress) untuk Highlight Temuan Terbaru.
     * Temuan dengan status Closed otomatis dikeluarkan dari query highlight,
     * namun tetap tersimpan di database untuk tabel utama, filter Closed, dan detail.
     */
    public function getActiveHighlights(int $limit = 24, ?string $division = null): Collection
    {
        return IssueFollowUp::with('logs')
            ->byDivision($division)
            ->activeForHighlight()
            ->take($limit)
            ->get();
    }

    /**
     * Bangun data grafik dari database (Tren Bulanan, Status Doughnut, Distribusi Kategori/Unit, & Perbandingan Divisi H/S/E).
     */
    public function buildChartData(Collection $issues): array
    {
        // A. Grafik Tren Temuan per Bulan
        $groupedByMonth = $issues
            ->filter(fn (IssueFollowUp $i) => $i->reported_date !== null)
            ->sortBy(fn (IssueFollowUp $i) => $i->reported_date->format('Y-m'))
            ->groupBy(fn (IssueFollowUp $i) => $i->reported_date->translatedFormat('M Y'));

        $trendChart = [
            'labels' => $groupedByMonth->keys()->values()->all(),
            'total' => $groupedByMonth->map(fn ($items) => $items->count())->values()->all(),
            'open' => $groupedByMonth->map(fn ($items) => $items->where('status', 'Open')->count())->values()->all(),
            'in_progress' => $groupedByMonth->map(fn ($items) => $items->where('status', 'In Progress')->count())->values()->all(),
            'closed' => $groupedByMonth->map(fn ($items) => $items->whereIn('status', ['Closed', 'Close'])->count())->values()->all(),
        ];

        // B. Grafik Status Temuan (Open, In Progress, Closed)
        $openCount = $issues->where('status', 'Open')->count();
        $inProgressCount = $issues->where('status', 'In Progress')->count();
        $closedCount = $issues->whereIn('status', ['Closed', 'Close'])->count();

        $statusChart = [
            'labels' => ['Open', 'In Progress', 'Closed'],
            'data' => [$openCount, $inProgressCount, $closedCount],
            'colors' => ['#f59e0b', '#2563eb', '#059669'],
        ];

        // C. Grafik Temuan per Kategori & Unit (hanya dari data yang benar-benar ada di DB / standar)
        $groupedByCategory = $issues
            ->groupBy(fn (IssueFollowUp $i) => $i->category ?: 'Lainnya')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $groupedByUnit = $issues
            ->groupBy(fn (IssueFollowUp $i) => $i->unit ?: 'PMU')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $palette = ['#059669', '#2563eb', '#0d9488', '#f59e0b', '#7c3aed', '#ec4899', '#ef4444', '#64748b'];

        $categoryChart = [
            'labels' => $groupedByCategory->keys()->values()->all(),
            'data' => $groupedByCategory->values()->all(),
            'colors' => array_slice($palette, 0, max(1, $groupedByCategory->count())),
            'unit_labels' => $groupedByUnit->keys()->values()->all(),
            'unit_data' => $groupedByUnit->values()->all(),
        ];

        // D. Grafik Perbandingan 4 Fungsi Safeguard
        $hCount = $issues->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'H')->count();
        $sSecurityCount = $issues->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'S_SECURITY')->count();
        $eCount = $issues->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'E')->count();
        $sSocialCount = $issues->filter(fn (IssueFollowUp $i) => IssueFollowUp::normalizeDivisionCode((string) $i->hsse_division) === 'S_SOCIAL')->count();
        $unclassifiedCount = $issues->filter(fn (IssueFollowUp $i) => empty($i->hsse_division))->count();

        $divLabels = ['Health & Safety', 'Security', 'Environment', 'Social'];
        $divData = [$hCount, $sSecurityCount, $eCount, $sSocialCount];
        $divColors = ['#0284c7', '#f59e0b', '#059669', '#dc2626'];
        if ($unclassifiedCount > 0) {
            $divLabels[] = 'Belum Diklasifikasikan';
            $divData[] = $unclassifiedCount;
            $divColors[] = '#64748b';
        }

        $divisionChart = [
            'labels' => $divLabels,
            'data' => $divData,
            'colors' => $divColors,
        ];

        return [
            'trendChart' => $trendChart,
            'statusChart' => $statusChart,
            'categoryChart' => $categoryChart,
            'divisionChart' => $divisionChart,
        ];
    }

    /**
     * Dapatkan daftar sumber temuan aktif dari tabel master finding_sources.
     */
    public function getActiveSources(): array
    {
        if (Schema::hasTable('finding_sources')) {
            FindingSource::ensureDefaults();
            $dbSources = FindingSource::where('is_active', true)->orderBy('id')->pluck('name')->all();
            if (!empty($dbSources)) {
                return array_values(array_unique($dbSources));
            }
        }
        return IssueFollowUp::DEFAULT_SOURCES;
    }

    /**
     * Tambahkan sumber temuan baru ke master data.
     */
    public function addFindingSource(string $name, ?string $description = null): FindingSource
    {
        return FindingSource::firstOrCreate(
            ['name' => trim($name)],
            ['description' => $description, 'is_active' => true]
        );
    }

    /**
     * Unggah dan simpan dokumentasi baru untuk temuan.
     */
    public function addDocumentation(
        IssueFollowUp $issue,
        UploadedFile $file,
        ?string $caption = null,
        ?Request $request = null
    ): IssueDocumentation {
        $userName = Auth::user()?->name ?? 'Pengguna';
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType() ?: $file->getMimeType();
        $fileSize = $file->getSize();

        $path = $file->store('issue_documentations', 'local');

        $doc = IssueDocumentation::create([
            'issue_follow_up_id' => $issue->id,
            'file_name' => basename($path),
            'original_name' => $originalName,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by' => $userName,
            'caption' => $caption,
        ]);

        $this->recordActivityLog(
            action: 'TAMBAH',
            issue: $issue,
            description: "Mengunggah dokumentasi \"{$originalName}\" pada temuan {$issue->issue_code}.",
            changes: null,
            request: $request
        );

        return $doc;
    }

    /**
     * Hapus dokumentasi temuan dari penyimpanan dan database.
     */
    public function deleteDocumentation(IssueDocumentation $doc, ?Request $request = null): void
    {
        $issue = $doc->issueFollowUp;
        $originalName = $doc->original_name;

        if (!empty($doc->file_path)) {
            foreach (['local', 'public'] as $diskName) {
                if (Storage::disk($diskName)->exists($doc->file_path)) {
                    Storage::disk($diskName)->delete($doc->file_path);
                }
            }
        }

        $doc->delete();

        if ($issue) {
            $this->recordActivityLog(
                action: 'HAPUS',
                issue: $issue,
                description: "Menghapus dokumentasi \"{$originalName}\" dari temuan {$issue->issue_code}.",
                changes: null,
                request: $request
            );
        }
    }

    /**
     * Simpan lampiran dokumentasi jika diunggah.
     */
    public function handleAttachment(?UploadedFile $file, ?IssueFollowUp $issue = null): ?string
    {
        if (!$file) {
            return null;
        }

        $imageService = app(\App\Services\ImageUploadService::class);
        if ($imageService->isValidImage($file)) {
            $media = $imageService->uploadImage($file, 'issue_attachments', $issue, 'issue-follow-up', 'Foto Lampiran Temuan', 'local');
            return $media->file_path;
        }

        return $file->store('issue_attachments', 'local');
    }

    /**
     * Buat temuan baru beserta log awal dan ActivityLog dalam transaksi database.
     */
    public function createIssue(array $data, ?UploadedFile $attachment = null, ?Request $request = null): IssueFollowUp
    {
        $userName = Auth::user()?->name ?? 'Administrator Dieng';
        $userId = Auth::id();

        return DB::transaction(function () use ($data, $attachment, $request, $userName, $userId) {
            $attachmentPath = $this->handleAttachment($attachment);

            $status = $data['status'] ?? 'Open';
            if (strcasecmp($status, 'Close') === 0 || strcasecmp($status, 'Closed') === 0) {
                $status = 'Closed';
            }

            $closedDate = null;
            if ($status === 'Closed') {
                $closedDate = !empty($data['closed_date'])
                    ? Carbon::parse($data['closed_date'])->toDateString()
                    : Carbon::today()->toDateString();
            }

            $issue = IssueFollowUp::create([
                'hsse_division' => !empty($data['hsse_division']) ? IssueFollowUp::normalizeDivisionCode((string) $data['hsse_division']) : null,
                'reported_date' => $data['reported_date'],
                'title' => $data['title'],
                'category' => $data['category'],
                'unit' => $data['unit'] ?? 'PMU',
                'location' => $data['location'] ?? null,
                'description' => $data['description'],
                'action_plan' => $data['action_plan'] ?? null,
                'pic' => $data['pic'] ?? $userName,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'] ?? 'Sedang',
                'status' => $status,
                'closed_date' => $closedDate,
                'closed_by' => $status === 'Closed' ? $userName : null,
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $attachmentPath,
            ]);

            // Catat riwayat awal pada tabel issue_follow_up_logs
            $initialNote = !empty($data['notes'])
                ? $data['notes']
                : (!empty($data['action_plan']) ? "Rencana Tindak Lanjut: {$data['action_plan']}" : "Temuan baru dilaporkan dan terdaftar dengan status {$issue->status}.");

            IssueFollowUpLog::create([
                'issue_follow_up_id' => $issue->id,
                'user_id' => $userId,
                'user_name' => $userName,
                'action_type' => 'CREATED',
                'old_status' => null,
                'new_status' => $issue->status,
                'note' => $initialNote,
                'attachment_path' => $attachmentPath,
                'followed_up_at' => now(),
            ]);

            // Simpan file dokumentasi jika diunggah bersama formulir
            $docFiles = [];
            if ($request && $request->hasFile('documentations')) {
                $docFiles = (array) $request->file('documentations');
            } elseif (!empty($data['documentations']) && is_array($data['documentations'])) {
                $docFiles = $data['documentations'];
            }

            foreach ($docFiles as $docFile) {
                if ($docFile instanceof UploadedFile && $docFile->isValid()) {
                    $this->addDocumentation($issue, $docFile, null, $request);
                }
            }

            if (empty($docFiles) && $attachment && $attachment->isValid()) {
                try {
                    $originalName = $attachment->getClientOriginalName();
                    $mimeType = $attachment->getClientMimeType() ?: $attachment->getMimeType();
                    $fileSize = $attachment->getSize();
                    IssueDocumentation::create([
                        'issue_follow_up_id' => $issue->id,
                        'file_name' => basename($attachmentPath),
                        'original_name' => $originalName,
                        'file_path' => $attachmentPath,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'uploaded_by' => $userName,
                        'caption' => 'Lampiran Formulir Temuan',
                    ]);
                } catch (\Throwable $e) {}
            }

            // Catat pada ActivityLog terpusat
            $this->recordActivityLog(
                action: 'TAMBAH',
                issue: $issue,
                description: "Menambahkan temuan HSSE baru [{$issue->issue_code}] Divisi {$issue->division_label}: {$issue->title} (PIC: {$issue->pic}, Status: {$issue->status})",
                changes: null,
                request: $request
            );

            return $issue->load('logs');
        });
    }

    /**
     * Perbarui data utama temuan dan catat perubahan status/data ke riwayat tanpa menimpa log lama.
     */
    public function updateIssue(IssueFollowUp $issue, array $data, ?UploadedFile $attachment = null, ?Request $request = null): IssueFollowUp
    {
        $userName = Auth::user()?->name ?? 'Administrator Dieng';
        $userId = Auth::id();

        return DB::transaction(function () use ($issue, $data, $attachment, $request, $userName, $userId) {
            $oldStatus = $issue->status;
            $oldSnapshot = $issue->only([
                'hsse_division', 'reported_date', 'title', 'category', 'unit', 'location',
                'description', 'action_plan', 'pic', 'due_date', 'priority', 'status', 'notes'
            ]);

            $attachmentPath = $attachment ? $this->handleAttachment($attachment) : $issue->attachment_path;

            $newStatus = $data['status'] ?? $issue->status;
            if (strcasecmp($newStatus, 'Close') === 0 || strcasecmp($newStatus, 'Closed') === 0) {
                $newStatus = 'Closed';
            }

            $closedDate = $issue->closed_date ? $issue->closed_date->format('Y-m-d') : null;
            $closedBy = $issue->closed_by;
            if ($newStatus === 'Closed') {
                if (!empty($data['closed_date'])) {
                    $closedDate = Carbon::parse($data['closed_date'])->toDateString();
                } elseif (empty($closedDate)) {
                    $closedDate = Carbon::today()->toDateString();
                }
                $closedBy = $closedBy ?: $userName;
            } else {
                $closedDate = null;
                $closedBy = null;
            }

            $issue->update([
                'hsse_division' => !empty($data['hsse_division']) ? IssueFollowUp::normalizeDivisionCode((string) $data['hsse_division']) : $issue->hsse_division,
                'reported_date' => $data['reported_date'],
                'title' => $data['title'],
                'category' => $data['category'],
                'unit' => $data['unit'] ?? $issue->unit ?? 'PMU',
                'location' => $data['location'] ?? null,
                'description' => $data['description'],
                'action_plan' => $data['action_plan'] ?? $issue->action_plan,
                'pic' => $data['pic'] ?? $issue->pic ?? $userName,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'] ?? $issue->priority,
                'status' => $newStatus,
                'closed_date' => $closedDate,
                'closed_by' => $closedBy,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $issue->notes,
                'attachment_path' => $attachmentPath,
            ]);

            $changedFields = $issue->getChanges();
            unset($changedFields['updated_at']);

            $newNoteAdded = !empty($data['follow_up_note'])
                ? $data['follow_up_note']
                : ((!empty($data['notes']) && $data['notes'] !== ($oldSnapshot['notes'] ?? null)) ? $data['notes'] : null);

            if ($oldStatus !== $issue->status || !empty($newNoteAdded)) {
                $logNote = !empty($newNoteAdded)
                    ? $newNoteAdded
                    : "Status temuan diperbarui dari {$oldStatus} menjadi {$issue->status}.";

                IssueFollowUpLog::create([
                    'issue_follow_up_id' => $issue->id,
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'action_type' => $oldStatus !== $issue->status ? 'STATUS_CHANGED' : 'UPDATED',
                    'old_status' => $oldStatus,
                    'new_status' => $issue->status,
                    'note' => $logNote,
                    'attachment_path' => $attachment ? $attachmentPath : null,
                    'followed_up_at' => now(),
                ]);
            }

            $changes = [
                'before' => array_intersect_key($oldSnapshot, $changedFields),
                'after' => $changedFields,
            ];

            $this->recordActivityLog(
                action: 'UBAH',
                issue: $issue,
                description: "Memperbarui temuan [{$issue->issue_code}]: {$issue->title} (Status: {$issue->status})",
                changes: $changes,
                request: $request
            );

            // Hapus file dokumentasi yang ditandai untuk dihapus oleh pengguna
            $deletedDocIds = [];
            if (!empty($data['deleted_documentations']) && is_array($data['deleted_documentations'])) {
                $deletedDocIds = array_map('intval', $data['deleted_documentations']);
            } elseif ($request && $request->has('deleted_documentations')) {
                $raw = $request->input('deleted_documentations');
                if (is_array($raw)) {
                    $deletedDocIds = array_map('intval', $raw);
                } elseif (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $deletedDocIds = is_array($decoded) ? array_map('intval', $decoded) : array_map('intval', array_filter(explode(',', $raw)));
                }
            } elseif ($request && $request->has('delete_documentations')) {
                $raw = $request->input('delete_documentations');
                if (is_array($raw)) {
                    $deletedDocIds = array_map('intval', $raw);
                } elseif (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    $deletedDocIds = is_array($decoded) ? array_map('intval', $decoded) : array_map('intval', array_filter(explode(',', $raw)));
                }
            }

            if (!empty($deletedDocIds)) {
                $docsToDelete = IssueDocumentation::where('issue_follow_up_id', $issue->id)
                    ->whereIn('id', $deletedDocIds)
                    ->get();

                $filesToPurge = [];
                foreach ($docsToDelete as $docToDelete) {
                    if (!empty($docToDelete->file_path)) {
                        $filesToPurge[] = $docToDelete->file_path;
                    }
                    $docName = $docToDelete->original_name ?: $docToDelete->file_name;
                    $docToDelete->delete();

                    $this->recordActivityLog(
                        action: 'HAPUS',
                        issue: $issue,
                        description: "Menghapus dokumentasi \"{$docName}\" dari temuan {$issue->issue_code}.",
                        changes: null,
                        request: $request
                    );
                }

                if (!empty($filesToPurge)) {
                    DB::afterCommit(function () use ($filesToPurge) {
                        foreach ($filesToPurge as $fPath) {
                            foreach (['local', 'public'] as $diskName) {
                                if (Storage::disk($diskName)->exists($fPath)) {
                                    Storage::disk($diskName)->delete($fPath);
                                }
                            }
                        }
                    });
                }
            }

            // Simpan file dokumentasi tambahan jika ada diunggah saat update
            $newDocFiles = [];
            if ($request && $request->hasFile('documentations')) {
                $newDocFiles = (array) $request->file('documentations');
            } elseif (!empty($data['documentations']) && is_array($data['documentations'])) {
                $newDocFiles = $data['documentations'];
            }

            foreach ($newDocFiles as $docFile) {
                if ($docFile instanceof UploadedFile && $docFile->isValid()) {
                    $this->addDocumentation($issue, $docFile, null, $request);
                }
            }

            return $issue->fresh(['logs', 'documentations']);
        });
    }

    /**
     * Tambahkan catatan tindak lanjut (Follow Up) baru secara kronologis tanpa menimpa riwayat sebelumnya.
     * Jika status sebelumnya 'Open', otomatis berubah menjadi 'In Progress' kecuali langsung ditandai 'Closed'.
     */
    public function addFollowUp(IssueFollowUp $issue, array $data, ?UploadedFile $attachment = null, ?Request $request = null): IssueFollowUpLog
    {
        $userName = Auth::user()?->name ?? 'Administrator Dieng';
        $userId = Auth::id();

        return DB::transaction(function () use ($issue, $data, $attachment, $request, $userName, $userId) {
            $oldStatus = $issue->status;
            $requestedStatus = $data['status'] ?? null;

            if (!empty($requestedStatus) && (strcasecmp($requestedStatus, 'Close') === 0 || strcasecmp($requestedStatus, 'Closed') === 0)) {
                $newStatus = 'Closed';
            } elseif ($oldStatus === 'Open') {
                // Ketika tindak lanjut dimulai pada temuan Open, status otomatis menjadi In Progress kecuali ditandai Closed
                $newStatus = 'In Progress';
            } elseif (!empty($requestedStatus)) {
                $newStatus = $requestedStatus;
            } else {
                $newStatus = $oldStatus;
            }

            $attachmentPath = $this->handleAttachment($attachment);

            $log = IssueFollowUpLog::create([
                'issue_follow_up_id' => $issue->id,
                'user_id' => $userId,
                'user_name' => $userName,
                'action_type' => $newStatus === 'Closed' ? 'CLOSED' : 'FOLLOW_UP',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $data['note'],
                'attachment_path' => $attachmentPath,
                'followed_up_at' => !empty($data['followed_up_at']) ? Carbon::parse($data['followed_up_at']) : now(),
            ]);

            $updateIssueData = [
                'status' => $newStatus,
                'notes' => $data['note'],
            ];

            if ($newStatus === 'Closed' && $oldStatus !== 'Closed') {
                $updateIssueData['closed_date'] = Carbon::today()->toDateString();
                $updateIssueData['closed_by'] = $userName;
            } elseif ($newStatus !== 'Closed') {
                $updateIssueData['closed_date'] = null;
                $updateIssueData['closed_by'] = null;
            }

            if ($attachmentPath) {
                $updateIssueData['attachment_path'] = $attachmentPath;
            }

            $issue->update($updateIssueData);

            $this->recordActivityLog(
                action: 'UBAH',
                issue: $issue,
                description: "Menambahkan tindak lanjut pada temuan [{$issue->issue_code}] {$issue->title}: \"{$data['note']}\" (Status: {$oldStatus} → {$newStatus})",
                changes: [
                    'before' => ['status' => $oldStatus],
                    'after' => ['status' => $newStatus, 'follow_up_note' => $data['note']],
                ],
                request: $request
            );

            return $log;
        });
    }

    /**
     * Tutup temuan (Close Issue) atau buka kembali (Reopen Issue) dengan pencatatan riwayat.
     */
    public function changeStatus(IssueFollowUp $issue, string $targetStatus, ?string $note = null, ?Request $request = null): IssueFollowUp
    {
        $userName = Auth::user()?->name ?? 'Administrator Dieng';
        $userId = Auth::id();

        return DB::transaction(function () use ($issue, $targetStatus, $note, $request, $userName, $userId) {
            $oldStatus = $issue->status;
            $normalizedStatus = (strcasecmp($targetStatus, 'Close') === 0 || strcasecmp($targetStatus, 'Closed') === 0)
                ? 'Closed'
                : (strcasecmp($targetStatus, 'In Progress') === 0 ? 'In Progress' : 'Open');

            $actionType = 'STATUS_CHANGED';
            if ($normalizedStatus === 'Closed') {
                $actionType = 'CLOSED';
            } elseif ($oldStatus === 'Closed' && $normalizedStatus !== 'Closed') {
                $actionType = 'REOPENED';
            }

            $defaultNote = match ($actionType) {
                'CLOSED' => 'Temuan telah diselesaikan dan ditutup (Closed).',
                'REOPENED' => "Temuan dibuka kembali dari status Closed menjadi {$normalizedStatus}.",
                default => "Status temuan diubah dari {$oldStatus} menjadi {$normalizedStatus}.",
            };

            $finalNote = !empty($note) ? $note : $defaultNote;

            IssueFollowUpLog::create([
                'issue_follow_up_id' => $issue->id,
                'user_id' => $userId,
                'user_name' => $userName,
                'action_type' => $actionType,
                'old_status' => $oldStatus,
                'new_status' => $normalizedStatus,
                'note' => $finalNote,
                'followed_up_at' => now(),
            ]);

            $issue->update([
                'status' => $normalizedStatus,
                'closed_date' => $normalizedStatus === 'Closed' ? Carbon::today()->toDateString() : null,
                'closed_by' => $normalizedStatus === 'Closed' ? $userName : null,
                'notes' => $finalNote,
            ]);

            $actVerb = match ($actionType) {
                'CLOSED' => 'Menutup temuan',
                'REOPENED' => 'Membuka kembali temuan',
                default => 'Mengubah status temuan',
            };

            $this->recordActivityLog(
                action: 'UBAH',
                issue: $issue,
                description: "{$actVerb} [{$issue->issue_code}] {$issue->title} ({$oldStatus} → {$normalizedStatus}): {$finalNote}",
                changes: [
                    'before' => ['status' => $oldStatus],
                    'after' => ['status' => $normalizedStatus, 'note' => $finalNote],
                ],
                request: $request
            );

            // Simpan file dokumentasi tambahan jika ada diunggah saat ubah status
            if ($request && $request->hasFile('documentations')) {
                foreach ((array) $request->file('documentations') as $docFile) {
                    if ($docFile instanceof UploadedFile && $docFile->isValid()) {
                        $this->addDocumentation($issue, $docFile, null, $request);
                    }
                }
            }

            return $issue->fresh(['logs', 'documentations']);
        });
    }

    /**
     * Hapus temuan dan catat ke ActivityLog.
     */
    public function deleteIssue(IssueFollowUp $issue, ?Request $request = null): void
    {
        DB::transaction(function () use ($issue, $request) {
            $issue->loadMissing(['documentations', 'logs']);

            $pathsToDelete = [];
            if (!empty($issue->attachment_path)) {
                $pathsToDelete[] = $issue->attachment_path;
            }
            foreach ($issue->documentations as $doc) {
                if (!empty($doc->file_path)) {
                    $pathsToDelete[] = $doc->file_path;
                }
                $doc->delete();
            }
            foreach ($issue->logs as $log) {
                if (!empty($log->attachment_path)) {
                    $pathsToDelete[] = $log->attachment_path;
                }
            }

            $this->recordActivityLog(
                action: 'HAPUS',
                issue: $issue,
                description: "Menghapus temuan [{$issue->issue_code}]: {$issue->title} (PIC: {$issue->pic}, Status: {$issue->status})",
                changes: null,
                request: $request
            );

            $issue->delete();

            DB::afterCommit(function () use ($pathsToDelete) {
                foreach (array_unique($pathsToDelete) as $path) {
                    $clean = ltrim((string) $path, '/');
                    if ($clean === '' || str_contains($clean, '..')) {
                        continue;
                    }
                    foreach (['local', 'public'] as $diskName) {
                        if (Storage::disk($diskName)->exists($clean)) {
                            Storage::disk($diskName)->delete($clean);
                        }
                    }
                    if (str_starts_with($clean, 'uploads/issue-follow-up/')) {
                        $legacyFull = public_path($clean);
                        if (is_file($legacyFull)) {
                            @unlink($legacyFull);
                        }
                    }
                }
            });
        });
    }

    /**
     * Catat ke tabel activity_logs terpusat secara aman.
     */
    protected function recordActivityLog(
        string $action,
        IssueFollowUp $issue,
        string $description,
        ?array $changes = null,
        ?Request $request = null
    ): void {
        try {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'Administrator Dieng',
                'module' => 'Issue & Follow Up',
                'action' => $action,
                'record_type' => IssueFollowUp::class,
                'record_id' => $issue->id,
                'description' => $description,
                'changes' => $changes,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Jangan gagalkan transaksi utama jika pencatatan log mengalami kendala
            report($e);
        }
    }
}
