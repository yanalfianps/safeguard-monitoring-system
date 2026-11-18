<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsseKpiPeriod extends Model
{
    public const STATUS_NOT_STARTED = 'Belum Mulai';
    public const STATUS_NOT_INPUT = 'Belum Diinput';
    public const STATUS_IN_PROGRESS = 'Berlangsung';
    public const STATUS_ACHIEVED = 'Tercapai';
    public const STATUS_NOT_ACHIEVED = 'Belum Tercapai';
    public const STATUS_NOT_APPLICABLE = 'Tidak Berlaku';
    public const STATUS_NEEDS_CONFIG = 'Perlu Konfigurasi';

    protected $table = 'hsse_kpi_periods';

    protected $fillable = [
        'hsse_kpi_id',
        'contractor_id',
        'period_type',
        'period_key',
        'scope_key',
        'year',
        'month',
        'quarter',
        'period_start',
        'period_end',
        'normal_target',
        'opening_debt',
        'opening_credit',
        'effective_target',
        'actual_total',
        'eligible_realization',
        'numerator_total',
        'denominator_total',
        'actual_percentage',
        'achievement_percentage',
        'debt_repaid_in_period',
        'credit_consumed_in_period',
        'closing_debt',
        'closing_credit',
        'status',
        'not_applicable_reason',
        'working_days_count',
        'resource_snapshot',
        'calculation_trace',
        'is_locked',
        'locked_at',
        'locked_by',
        'lock_note',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'quarter' => 'integer',
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'normal_target' => 'float',
        'opening_debt' => 'float',
        'opening_credit' => 'float',
        'effective_target' => 'float',
        'actual_total' => 'float',
        'eligible_realization' => 'float',
        'numerator_total' => 'float',
        'denominator_total' => 'float',
        'actual_percentage' => 'float',
        'achievement_percentage' => 'float',
        'debt_repaid_in_period' => 'float',
        'credit_consumed_in_period' => 'float',
        'closing_debt' => 'float',
        'closing_credit' => 'float',
        'working_days_count' => 'integer',
        'resource_snapshot' => 'array',
        'calculation_trace' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(HsseKpi::class, 'hsse_kpi_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(HsseKpiEntry::class, 'hsse_kpi_period_id')->orderBy('entry_date')->orderBy('id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(HsseKpiNote::class, 'hsse_kpi_period_id')->orderByDesc('id');
    }

    public function sourceLedgers(): HasMany
    {
        return $this->hasMany(HsseKpiBalanceLedger::class, 'source_period_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HsseKpiLedgerAllocation::class, 'allocated_in_period_id');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function getRawRealizationAttribute(): ?float
    {
        return $this->actual_total;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ?: self::STATUS_NOT_INPUT;
    }
}
