<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueFollowUp extends Model
{
    use HasFactory;

    protected $table = 'issue_follow_ups';

    public const CATEGORIES = [
        'Audit GIMS',
        'Audit EBTKE',
        'ADB Mission',
        'Audit PMC',
        'GRM',
        'Hasil Observasi',
    ];

    public const DEFAULT_SOURCES = [
        'Audit GIMS',
        'Audit EBTKE',
        'ADB Mission',
        'Audit PMC',
        'GRM',
        'Hasil Observasi',
    ];

    public const UNITS = [
        'PMU',
        'Dieng Unit 1',
        'Dieng Unit 2',
        'Utility',
        'JOTRE',
        'BISMO',
    ];

    public const PRIORITIES = [
        'Rendah',
        'Sedang',
        'Tinggi',
        'Kritis',
    ];

    public const STATUSES = [
        'Open',
        'Closed',
    ];

    public const HSSE_DIVISIONS = [
        'H' => 'Health & Safety',
        'S_SECURITY' => 'Security',
        'E' => 'Environment',
        'S_SOCIAL' => 'Social',
    ];

    public const HSSE_FUNCTIONS = [
        'H' => 'Health & Safety',
        'S_SECURITY' => 'Security',
        'E' => 'Environment',
        'S_SOCIAL' => 'Social',
    ];

    public const SAFEGUARD_FUNCTIONS = [
        'H' => 'Health & Safety',
        'S_SECURITY' => 'Security',
        'E' => 'Environment',
        'S_SOCIAL' => 'Social',
    ];

    public const ACTIVE_STATUSES = [
        'Open',
        'In Progress',
    ];

    public const CLOSED_STATUSES = [
        'Closed',
        'Close',
    ];

    protected $fillable = [
        'issue_code',
        'hsse_division',
        'title',
        'category',
        'unit',
        'reported_date',
        'due_date',
        'location',
        'description',
        'action_plan',
        'pic',
        'priority',
        'status',
        'closed_date',
        'closed_by',
        'notes',
        'attachment_path',
    ];

    protected $appends = [
        'is_overdue',
        'status_badge_class',
        'priority_badge_class',
        'division_label',
        'division_badge_class',
    ];

    protected function casts(): array
    {
        return [
            'reported_date' => 'date:Y-m-d',
            'due_date' => 'date:Y-m-d',
            'closed_date' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (IssueFollowUp $issue) {
            if (empty($issue->issue_code)) {
                $datePart = $issue->reported_date
                    ? Carbon::parse($issue->reported_date)->format('Ymd')
                    : date('Ymd');
                $randomPart = strtoupper(substr(uniqid(), -4));
                $candidate = "IFU-{$datePart}-{$randomPart}";
                while (static::where('issue_code', $candidate)->exists()) {
                    $candidate = "IFU-{$datePart}-" . random_int(1000, 9999);
                }
                $issue->issue_code = $candidate;
            }

            if (empty($issue->unit)) {
                $issue->unit = 'PMU';
            }

            if (empty($issue->category)) {
                $issue->category = 'Audit GIMS';
            }

            if (empty($issue->priority)) {
                $issue->priority = 'Sedang';
            }

            if (empty($issue->pic)) {
                $issue->pic = 'Tim HSSE';
            }

            // Normalisasi nilai status agar menggunakan nilai baku ('Open' atau 'Closed')
            if (strcasecmp((string) $issue->status, 'Close') === 0 || strcasecmp((string) $issue->status, 'Closed') === 0) {
                $issue->status = 'Closed';
            } elseif (strcasecmp((string) $issue->status, 'In Progress') === 0) {
                $issue->status = 'In Progress';
            } else {
                $issue->status = 'Open';
            }

            if ($issue->status === 'Closed') {
                if (empty($issue->closed_date)) {
                    $issue->closed_date = Carbon::today()->format('Y-m-d');
                }
            } else {
                $issue->closed_date = null;
            }
        });
    }

    /**
     * Relasi dokumentasi temuan (Add Documentation).
     */
    public function documentations(): HasMany
    {
        return $this->hasMany(IssueDocumentation::class, 'issue_follow_up_id')
            ->orderBy('id', 'desc');
    }

    /**
     * Relasi riwayat tindak lanjut (kronologis terbaru di atas).
     */
    public function logs(): HasMany
    {
        return $this->hasMany(IssueFollowUpLog::class, 'issue_follow_up_id')
            ->orderBy('followed_up_at', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Relasi riwayat tindak lanjut secara kronologis dari awal ke akhir.
     */
    public function chronologicalLogs(): HasMany
    {
        return $this->hasMany(IssueFollowUpLog::class, 'issue_follow_up_id')
            ->orderBy('followed_up_at', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Apakah temuan melewati tenggat dan belum berstatus Closed.
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'Closed' || empty($this->due_date)) {
            return false;
        }

        return Carbon::parse($this->due_date)->startOfDay()->lt(Carbon::today());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    public function getDateFoundAttribute(): mixed
    {
        return $this->reported_date;
    }

    public function getResponsiblePersonAttribute(): ?string
    {
        return $this->pic;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->status === 'Closed') {
            return 'status-closed';
        }
        if ($this->status === 'In Progress') {
            return 'status-in-progress';
        }
        return 'status-open';
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'Kritis' => 'priority-critical',
            'Tinggi' => 'priority-high',
            'Rendah' => 'priority-low',
            default => 'priority-medium',
        };
    }

    public function getDivisionLabelAttribute(): string
    {
        $code = strtoupper(trim((string) $this->hsse_division));
        if (in_array($code, ['H', 'HEALTH & SAFETY', 'HEALTH & SAVETY', 'HUMAN SAFETY', 'H — HEALTH & SAVETY', 'H - HEALTH & SAVETY'], true)) {
            return self::HSSE_DIVISIONS['H'];
        }
        if (in_array($code, ['S', 'S_SOCIAL', 'SOSIAL', 'SOCIAL', 'S — SOSIAL', 'S - SOSIAL'], true)) {
            return self::HSSE_DIVISIONS['S_SOCIAL'];
        }
        if (in_array($code, ['S_SECURITY', 'SCURITY', 'SECURITY', 'S — SCURITY', 'S - SCURITY'], true)) {
            return self::HSSE_DIVISIONS['S_SECURITY'];
        }
        if (in_array($code, ['E', 'ENVIRONMENT', 'EMVIRONMENT', 'E — EMVIRONMENT', 'E - EMVIRONMENT'], true)) {
            return self::HSSE_DIVISIONS['E'];
        }
        return self::HSSE_DIVISIONS[$code] ?? 'Belum Diklasifikasikan';
    }

    public function getDivisionBadgeClassAttribute(): string
    {
        $code = strtoupper(trim((string) $this->hsse_division));
        return match ($code) {
            'H', 'HEALTH & SAFETY', 'HEALTH & SAVETY' => 'division-h',
            'S', 'S_SOCIAL', 'SOSIAL', 'SOCIAL' => 'division-s-social',
            'S_SECURITY', 'SCURITY', 'SECURITY' => 'division-s-security',
            'E', 'ENVIRONMENT', 'EMVIRONMENT' => 'division-e',
            default => 'division-unclassified',
        };
    }

    /**
     * Normalisasi input nama/kode Fungsi Safeguard ke kode standar ('H', 'S_SECURITY', 'E', 'S_SOCIAL').
     */
    public static function normalizeDivisionCode(?string $division): ?string
    {
        if ($division === null || trim($division) === '') {
            return null;
        }

        $raw = strtoupper(trim($division));
        return match ($raw) {
            'H', 'HEALTH & SAFETY', 'HEALTH AND SAFETY', 'HUMAN SAFETY', 'H — HUMAN SAFETY', 'H - HUMAN SAFETY', 'HEALTH', 'H — HEALTH', 'H - HEALTH', 'HEALTH & SAVETY', 'HEALTH AND SAVETY', 'H — HEALTH & SAVETY', 'H - HEALTH & SAVETY', 'H — HEALTH & SAFETY', 'H - HEALTH & SAFETY' => 'H',
            'S_SOCIAL', 'S', 'SOSIAL', 'SOCIAL', 'S — SOSIAL', 'S - SOSIAL', 'S — SOCIAL', 'S - SOCIAL' => 'S_SOCIAL',
            'S_SECURITY', 'SCURITY', 'SECURITY', 'S — SCURITY', 'S - SCURITY', 'S — SECURITY', 'S - SECURITY' => 'S_SECURITY',
            'E', 'EMVIRONMENT', 'ENVIRONMENT', 'E — EMVIRONMENT', 'E - EMVIRONMENT', 'E — ENVIRONMENT', 'E - ENVIRONMENT' => 'E',
            'UNCLASSIFIED', 'BELUM DIKLASIFIKASIKAN' => 'UNCLASSIFIED',
            default => $raw,
        };
    }

    /**
     * Scope filter berdasarkan Fungsi Safeguard (H, S_SOCIAL, S_SECURITY, E, atau unclassified).
     */
    public function scopeByDivision(Builder $query, string|array|null $division): Builder
    {
        if (empty($division)) {
            return $query;
        }

        if (is_array($division)) {
            $codes = array_values(array_filter(array_map([self::class, 'normalizeDivisionCode'], $division)));
            if (empty($codes)) {
                return $query;
            }

            return $query->where(function (Builder $outer) use ($codes) {
                foreach ($codes as $code) {
                    $outer->orWhere(function (Builder $inner) use ($code) {
                        $this->applySingleDivisionConstraint($inner, $code);
                    });
                }
            });
        }

        $normalized = self::normalizeDivisionCode($division);
        if (!$normalized) {
            return $query;
        }

        return $this->applySingleDivisionConstraint($query, $normalized);
    }

    protected function applySingleDivisionConstraint(Builder $query, string $normalized): Builder
    {
        if ($normalized === 'UNCLASSIFIED') {
            return $query->where(function (Builder $q) {
                $q->whereNull('hsse_division')->orWhere('hsse_division', '');
            });
        }

        if ($normalized === 'H') {
            return $query->whereIn('hsse_division', ['H', 'Health & Safety', 'Health & Savety']);
        }

        if ($normalized === 'S_SOCIAL') {
            return $query->whereIn('hsse_division', ['S_SOCIAL', 'S', 'Social', 'Sosial']);
        }

        if ($normalized === 'S_SECURITY') {
            return $query->whereIn('hsse_division', ['S_SECURITY', 'SCURITY', 'SECURITY', 'Security', 'Scurity']);
        }

        if ($normalized === 'E') {
            return $query->whereIn('hsse_division', ['E', 'Environment', 'Emvironment']);
        }

        if (array_key_exists($normalized, self::HSSE_DIVISIONS)) {
            return $query->where('hsse_division', $normalized);
        }

        return $query;
    }

    /**
     * Scope untuk temuan Overdue (due_date < hari ini dan status != Closed).
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'Closed')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::today()->toDateString());
    }

    /**
     * Scope query backend untuk bagian "Highlight Temuan Terbaru" (Temuan Aktif):
     * Hanya mengambil temuan yang masih memerlukan penanganan ('Open' dan 'In Progress').
     * Temuan berstatus 'Closed' otomatis dikeluarkan dari highlight.
     * Diurutkan berdasarkan tanggal temuan terbaru (reported_date DESC), waktu pembuatan, dan ID.
     */
    public function scopeActiveForHighlight(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES)
            ->whereNotIn('status', self::CLOSED_STATUSES)
            ->orderBy('reported_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }
}
