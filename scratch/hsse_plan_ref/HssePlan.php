<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class HssePlan extends Model
{
    use HasFactory;

    protected $table = 'hsse_plans';

    protected $fillable = [
        'title',
        'name',
        'category',
        'type',
        'unit',
        'year',
        'period',
        'target',
        'realization',
        'actual',
        'unit_measure',
        'target_frequency',
        'monthly_realizations',
        'realization_history',
        'start_date',
        'end_date',
        'project_start_date',
        'project_end_date',
        'status',
        'contractor_id',
        'contractor_name',
        'description',
    ];

    protected $casts = [
        'target' => 'float',
        'realization' => 'float',
        'year' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'project_start_date' => 'date:Y-m-d',
        'project_end_date' => 'date:Y-m-d',
        'monthly_realizations' => 'array',
        'realization_history' => 'array',
    ];

    protected $appends = [
        'name',
        'type',
        'actual',
        'period',
        'target_label',
        'percentage',
        'history',
        'project_start',
        'project_end',
    ];

    /**
     * Relationship to Notes.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(HssePlanNote::class, 'hsse_plan_id')->orderBy('created_at', 'desc');
    }

    /**
     * Relationship to Contractor.
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    /**
     * Mutator & Accessor for title <-> name alias.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->title,
            set: fn ($value) => ['title' => $value],
        );
    }

    /**
     * Mutator & Accessor for category <-> type alias.
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category,
            set: fn ($value) => ['category' => $value],
        );
    }

    /**
     * Mutator & Accessor for realization <-> actual alias.
     */
    protected function actual(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) ($this->realization ?? 0),
            set: fn ($value) => ['realization' => $value],
        );
    }

    /**
     * Mutator & Accessor for year <-> period alias.
     */
    protected function period(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ($this->year ?? date('Y')),
            set: fn ($value) => ['year' => $value],
        );
    }

    /**
     * Accessor for project_start alias.
     */
    public function getProjectStartAttribute(): ?string
    {
        $d = $this->project_start_date ?: $this->start_date;
        if (!$d) return null;
        return $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d;
    }

    /**
     * Accessor for project_end alias.
     */
    public function getProjectEndAttribute(): ?string
    {
        $d = $this->project_end_date ?: $this->end_date;
        if (!$d) return null;
        return $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d;
    }

    /**
     * Formatted target label attribute.
     */
    public function getTargetLabelAttribute(): string
    {
        $target = (float) ($this->target ?? 0);
        $measure = trim($this->unit_measure ?? '');

        if ($measure === '%') {
            return (rtrim(rtrim(number_format($target, 2, '.', ''), '0'), '.') ?: '0') . '%';
        }

        if ($measure) {
            return (rtrim(rtrim(number_format($target, 2, '.', ''), '0'), '.') ?: '0') . ' ' . $measure;
        }

        return (string) $target;
    }

    /**
     * History attribute accessor.
     */
    public function getHistoryAttribute(): array
    {
        return is_array($this->realization_history) ? $this->realization_history : [];
    }

    /**
     * Check whether a 'Per Project' KPI is active in a specified period or active viewing date.
     * KPI Per Project hanya akan muncul di daftar/tabel jika tanggal aktif berada di dalam rentang (start date s.d. end date) tersebut.
     */
    public function isProjectActiveInPeriod(?int $month = null, ?int $year = null, ?string $specificDate = null): bool
    {
        if ($this->target_frequency !== 'Per Project') {
            return true;
        }

        $start = $this->project_start_date ?: $this->start_date;
        $end = $this->project_end_date ?: $this->end_date;

        if (!$start || !$end) {
            return true;
        }

        try {
            $startDate = Carbon::parse($start)->startOfDay();
            $endDate = Carbon::parse($end)->endOfDay();

            if ($specificDate) {
                $target = Carbon::parse($specificDate);
                return $target->betweenIncluded($startDate, $endDate);
            }

            $evalYear = $year ?: (int) ($this->year ?: date('Y'));

            if ($month !== null) {
                $monthStart = Carbon::createFromDate($evalYear, $month, 1)->startOfMonth();
                $monthEnd = Carbon::createFromDate($evalYear, $month, 1)->endOfMonth();
                // Ada tumpang tindih rentang (overlap) antara bulan aktif dan durasi proyek
                return $startDate->lte($monthEnd) && $endDate->gte($monthStart);
            }

            if ($year !== null) {
                $yearStart = Carbon::createFromDate($evalYear, 1, 1)->startOfYear();
                $yearEnd = Carbon::createFromDate($evalYear, 12, 31)->endOfYear();
                return $startDate->lte($yearEnd) && $endDate->gte($yearStart);
            }

            // Jika tidak ada filter aktif, bandingkan dengan tanggal hari ini (tanggal aktif sistem)
            $today = Carbon::today();
            return $today->betweenIncluded($startDate, $endDate);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Helper to retrieve realization for a specific month & year.
     */
    public function getRealizationForMonth(int $month, ?int $year = null): float
    {
        $monthly = is_array($this->monthly_realizations) ? $this->monthly_realizations : [];
        $evalYear = $year ?: (int) ($this->year ?: date('Y'));
        $padMonth = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        // 1. Cek format year-month key "2026-03"
        $ymKey = "{$evalYear}-{$padMonth}";
        if (isset($monthly[$ymKey])) {
            return (float) $monthly[$ymKey];
        }

        // 2. Cek format "2026-3"
        $ymKeyShort = "{$evalYear}-{$month}";
        if (isset($monthly[$ymKeyShort])) {
            return (float) $monthly[$ymKeyShort];
        }

        // 3. Cek format nested [$year][$month]
        if (isset($monthly[$evalYear][$month]) || isset($monthly[(string) $evalYear][$month])) {
            return (float) ($monthly[$evalYear][$month] ?? $monthly[(string) $evalYear][$month]);
        }

        // 4. Fallback ke key integer bulan jika tahun cocok
        if ((int) ($this->year ?: date('Y')) === $evalYear) {
            if (isset($monthly[$month])) {
                return (float) $monthly[$month];
            }
            if (isset($monthly[(string) $month])) {
                return (float) $monthly[(string) $month];
            }
        }

        return 0.0;
    }

    /**
     * Calculate actual realization, percentage, and status based on target_frequency and selected month/year.
     * 1. Bulanan: Realisasi dan persentase dihitung terisolasi per bulan (reset setiap bulan, histori tersimpan per bulan & tahun).
     * 2. Tahunan: Realisasi dihitung secara kumulatif (YTD) dari Januari hingga bulan saat ini pada tahun berjalan.
     * 3. Per Project: Realisasi dihitung secara kumulatif sepanjang rentang tanggal project yang diatur, tanpa terpengaruh filter bulan.
     */
    public function calculateProgressForMonth(?int $month = null, ?int $year = null): array
    {
        $freq = $this->target_frequency ?: 'Tahunan';
        $target = (float) ($this->target ?? 0);
        $evalMonth = $month ?: (int) date('n');
        $evalYear = $year ?: (int) ($this->year ?: date('Y'));
        $isLagging = strtolower((string) ($this->category ?? $this->type ?? '')) === 'lagging';

        $actual = 0.0;

        if ($freq === 'Bulanan') {
            // Terisolasi per bulan (reset setiap bulan, tersimpan berdasarkan bulan dan tahun)
            $actual = $this->getRealizationForMonth($evalMonth, $evalYear);
            if ($actual === 0.0 && $month === null && (int) ($this->year ?: date('Y')) === $evalYear && $evalMonth === (int) date('n')) {
                $actual = (float) ($this->realization ?? 0);
            }
        } elseif ($freq === 'Tahunan') {
            // Kumulatif (YTD) dari Januari hingga bulan evaluasi pada tahun tersebut
            $monthly = is_array($this->monthly_realizations) ? $this->monthly_realizations : [];
            if (!empty($monthly)) {
                $actual = 0.0;
                for ($m = 1; $m <= $evalMonth; $m++) {
                    $actual += $this->getRealizationForMonth($m, $evalYear);
                }
            } else {
                $actual = (float) ($this->realization ?? 0);
            }
        } else { // 'Per Project'
            // Kumulatif sepanjang rentang tanggal project, tanpa terpengaruh filter bulan
            $actual = (float) ($this->realization ?? 0);
        }

        // Hitung persentase
        $percentage = 0;
        if ($isLagging) {
            if ($actual <= 0) {
                // Jika realisasi 0, persentase = 100% (Tercapai)
                $percentage = 100;
            } elseif ($target <= 0 || $actual >= $target) {
                // Jika realisasi mencapai atau melebihi target, persentase turun menjadi 0% (Belum Tercapai)
                $percentage = 0;
            } else {
                // Jika realisasi di antara 0 dan target (misal target 2, realisasi 1): (1 - (realisasi / target)) * 100%
                $percentage = (int) max(0, min(100, round((1 - ($actual / $target)) * 100)));
            }
        } else {
            if ($target <= 0) {
                $percentage = $actual <= 0 ? 100 : 0;
            } else {
                $percentage = (int) max(0, min(100, round(($actual / $target) * 100)));
            }
        }

        // Tentukan status Tercapai / Belum Tercapai
        $status = $percentage >= 100 ? 'Tercapai' : 'Belum Tercapai';

        return [
            'frequency' => $freq,
            'year' => $evalYear,
            'month' => $evalMonth,
            'target' => $target,
            'actual' => $actual,
            'percentage' => $percentage,
            'status' => $status,
        ];
    }

    /**
     * Automatic archive and rollover for Tahunan KPIs when transitioning into a new year.
     * Menjamin arsip tahun lalu tetap tersimpan utuh dan dapat dilihat melalui filter tahun.
     */
    public static function ensureYearlyRollover(?int $currentYear = null): void
    {
        $currentYear = $currentYear ?: (int) date('Y');
        $prevYear = $currentYear - 1;

        // Cari KPI Tahunan dari tahun sebelumnya
        $prevKpis = static::where('year', $prevYear)
            ->where(function ($q) {
                $q->where('target_frequency', 'Tahunan')
                  ->orWhereNull('target_frequency');
            })
            ->get();

        foreach ($prevKpis as $old) {
            $exists = static::where('year', $currentYear)
                ->where('title', $old->title)
                ->where('contractor_id', $old->contractor_id)
                ->exists();

            if (!$exists) {
                static::create([
                    'title' => $old->title,
                    'category' => $old->category,
                    'unit' => $old->unit,
                    'contractor_id' => $old->contractor_id,
                    'contractor_name' => $old->contractor_name,
                    'year' => $currentYear,
                    'target' => $old->target,
                    'realization' => 0, // Reset kumulatif untuk tahun baru
                    'unit_measure' => $old->unit_measure,
                    'target_frequency' => 'Tahunan',
                    'monthly_realizations' => [],
                    'realization_history' => [
                        [
                            'date' => "{$currentYear}-01-01",
                            'month' => 1,
                            'year' => $currentYear,
                            'actual' => 0,
                            'note' => "Arsip baru tahun {$currentYear} (rollover kumulatif otomatis dari tahun {$prevYear})",
                            'doc' => null,
                            'created_at' => now()->toIso8601String(),
                        ]
                    ],
                    'start_date' => "{$currentYear}-01-01",
                    'end_date' => "{$currentYear}-12-31",
                    'project_start_date' => null,
                    'project_end_date' => null,
                    'status' => 'Belum Mulai',
                    'description' => $old->description,
                ]);
            }
        }
    }

    /**
     * Computed percentage attribute.
     */
    public function getPercentageAttribute(): int
    {
        return $this->calculateProgressForMonth()['percentage'];
    }
}
