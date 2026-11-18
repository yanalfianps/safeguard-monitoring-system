<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsseKpiEntry extends Model
{
    public const EXEC_COMPLETED = 'completed';
    public const EXEC_PARTIAL = 'partial';
    public const EXEC_NOT_COMPLETED = 'not_completed';
    public const EXEC_NOT_APPLICABLE = 'not_applicable';

    protected $table = 'hsse_kpi_entries';

    protected $fillable = [
        'hsse_kpi_id',
        'hsse_kpi_period_id',
        'contractor_id',
        'entry_date',
        'scope_key',
        'location',
        'entry_type',
        'checklist_status',
        'quantity',
        'numerator',
        'denominator',
        'percentage_value',
        'index_value',
        'index_scale',
        'unit',
        'status_execution',
        'is_eligible',
        'source_reference',
        'notes',
        'is_historical_correction',
        'correction_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date' => 'date:Y-m-d',
        'quantity' => 'float',
        'numerator' => 'float',
        'denominator' => 'float',
        'percentage_value' => 'float',
        'index_value' => 'float',
        'index_scale' => 'float',
        'is_eligible' => 'boolean',
        'is_historical_correction' => 'boolean',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(HsseKpi::class, 'hsse_kpi_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(HsseKpiPeriod::class, 'hsse_kpi_period_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(HsseKpiAttachment::class, 'hsse_kpi_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getExecutionStatusAttribute(): string
    {
        return $this->status_execution ?: 'Terlaksana';
    }

    public function getRealizedValueAttribute(): ?float
    {
        return $this->quantity ?? $this->percentage_value ?? $this->index_value;
    }

    public function getPlannedObligationAttribute(): ?float
    {
        return $this->denominator;
    }

    public function getNumeratorValueAttribute(): ?float
    {
        return $this->numerator;
    }

    public function getDenominatorValueAttribute(): ?float
    {
        return $this->denominator;
    }

    public function getIsCountedForKpiAttribute(): bool
    {
        return (bool) $this->is_eligible;
    }

    public function getCreatedByNameAttribute(): string
    {
        return $this->creator?->name ?? 'HSE Owner';
    }
}
