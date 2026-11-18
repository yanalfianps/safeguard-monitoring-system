<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Contractor extends Model
{
    use HasFactory;

    public const STRUCTURE_LEADING_LAGGING = 'leading_lagging';
    public const STRUCTURE_PERSPECTIVES = 'perspectives';

    protected $table = 'contractors';

    protected $fillable = [
        'name',
        'display_name',
        'code',
        'slug',
        'description',
        'is_active',
        'is_kpi_monitored',
        'structure_type',
        'perspective_weights',
        'scorecard_formula_verified',
        'scope_locations',
        'effective_from',
        'effective_until',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_kpi_monitored' => 'boolean',
        'perspective_weights' => 'array',
        'scorecard_formula_verified' => 'boolean',
        'scope_locations' => 'array',
        'effective_from' => 'date:Y-m-d',
        'effective_until' => 'date:Y-m-d',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Contractor $contractor) {
            if (empty($contractor->slug)) {
                $baseSlug = Str::slug($contractor->code ?: $contractor->name);
                if (empty($baseSlug)) {
                    $baseSlug = 'contractor-' . strtolower(Str::random(6));
                }
                $slug = $baseSlug;
                $counter = 2;
                while (static::where('slug', $slug)
                    ->when($contractor->exists, fn ($q) => $q->where('id', '!=', $contractor->id))
                    ->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                $contractor->slug = $slug;
            }
            if (empty($contractor->display_name)) {
                $contractor->display_name = $contractor->name;
            }
        });
    }

    public function scopeKpiMonitored(Builder $query): Builder
    {
        if (Schema::hasColumn('contractors', 'is_kpi_monitored')) {
            return $query->where('is_kpi_monitored', true);
        }

        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(HsseKpi::class, 'contractor_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeKpis(): HasMany
    {
        return $this->hasMany(HsseKpi::class, 'contractor_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(ContractorCalendar::class, 'contractor_id');
    }

    public function activeCalendar(): HasOne
    {
        return $this->hasOne(ContractorCalendar::class, 'contractor_id')
            ->where('is_active', true)
            ->latestOfMany('id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ContractorResource::class, 'contractor_id');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(ContractorWorkLog::class, 'contractor_id');
    }

    public function kpiPeriods(): HasMany
    {
        return $this->hasMany(HsseKpiPeriod::class, 'contractor_id');
    }

    public function kpiEntries(): HasMany
    {
        return $this->hasMany(HsseKpiEntry::class, 'contractor_id');
    }

    public function balanceLedgers(): HasMany
    {
        return $this->hasMany(HsseKpiBalanceLedger::class, 'contractor_id');
    }

    /**
     * Legacy relationship to HssePlan (preserved for safe backward compatibility).
     */
    public function hssePlans(): HasMany
    {
        return $this->hasMany(HssePlan::class, 'contractor_id');
    }

    /**
     * Whether this contractor groups KPIs by official perspectives (e.g. TDP 4 perspectives) vs Leading/Lagging.
     */
    public function usesPerspectives(): bool
    {
        return $this->structure_type === self::STRUCTURE_PERSPECTIVES;
    }

    /**
     * Fallback accessor to ensure slug is always valid and never null or empty in views and routing.
     */
    public function getSlugAttribute(?string $value): string
    {
        if (!empty($value)) {
            return $value;
        }

        $base = Str::slug($this->code ?: ($this->name ?: ('contractor-' . ($this->id ?? 'unknown'))));
        return !empty($base) ? $base : ('contractor-' . ($this->id ?? 'unknown'));
    }

    /**
     * Label helper for navigation and headers.
     */
    public function getLabelAttribute(): string
    {
        return $this->display_name ?: $this->name;
    }

    /**
     * Safely retrieve active KPI-monitored contractors for dynamic sidebar navigation.
     */
    public static function getActiveForNavigation(): Collection
    {
        try {
            if (!Schema::hasTable('contractors')) {
                return collect();
            }

            if (Schema::hasColumn('contractors', 'is_kpi_monitored')) {
                return static::where('is_kpi_monitored', true)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
            }

            return static::where('is_active', true)->orderBy('name')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Retained as safe no-op for backward compatibility.
     */
    public static function ensureDefaultsSeeded(): void
    {
        // Intentionally left as a safe no-op to prevent auto-seeding dummy data.
    }
}
