<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsseKpi extends Model
{
    public const FREQUENCIES = [
        'daily' => 'Daily (Harian)',
        'weekly' => 'Weekly (Mingguan)',
        'biweekly' => 'Biweekly (1x setiap 2 Minggu)',
        'monthly' => 'Monthly (Bulanan)',
        'quarterly' => 'Quarterly (Kuartalan)',
        'biannually' => 'Biannually (2x dalam Setahun)',
        'annually' => 'Annual (Tahunan)',
        'per_project' => 'Per Project',
        'before_activity' => 'Before Activity / Work Commencement',
        'before_mobilization' => 'Before Mobilization',
        'before_site_access' => 'Before Site Access',
        'pre_use' => 'Per Use / Pre-Use',
        'event_based' => 'Event-Based / Sesuai Kegiatan',
        'as_scheduled' => 'As Scheduled / As Required',
    ];

    public const EVALUATION_PERIODS = [
        'monthly' => 'Bulanan',
        'quarterly' => 'Kuartalan',
        'biannually' => 'Semesteran (6 Bulan)',
        'annually' => 'Tahunan',
        'per_project' => 'Masa Proyek (Per Project)',
        'weekly' => 'Mingguan',
    ];

    public const INPUT_METHODS = [
        'checklist' => 'Checklist (Sudah / Belum / N/A)',
        'quantity' => 'Kuantitatif (Target & Jumlah Realisasi)',
        'percentage' => 'Persentase / Kepatuhan (Pembilang / Penyebut / %)',
        'index' => 'Indeks / Skor Survei',
        'event' => 'Event-Based / Kegiatan Pemicu',
    ];

    public const INPUT_FREQUENCIES = [
        'daily' => 'Harian',
        'per_activity' => 'Per Kegiatan',
        'per_report' => 'Per Laporan',
        'per_object' => 'Per Personel / Objek',
        'per_period' => 'Sesuai Periode Pelaporan',
    ];

    public const CALCULATION_METHODS = [
        'zero_event' => 'Zero Event (Target 0 Kejadian)',
        'maximum_threshold' => 'Maximum Threshold / Incident Lagging Limit',
        'minimum_quantity' => 'Minimum Quantity',
        'target_quantity' => 'Target Quantity',
        'schedule_compliance' => 'Schedule Compliance',
        'percentage_compliance' => 'Compliance Percentage',
        'personnel_compliance' => 'Personnel Compliance',
        'asset_compliance' => 'Asset / Vehicle / Equipment Compliance',
        'route_completion' => 'Route / Patrol Completion',
        'response_time_compliance' => 'Response Time Compliance',
        'finding_closure' => 'Finding Closure Rate',
        'index_score' => 'Index / Survey Score',
        'man_hours' => 'Man-Hours Accumulation',
        'event_completion' => 'Project / Event Completion',
        'record_and_report' => 'All Record & Report Compliance',
    ];

    public const CREDIT_VALIDITY_TYPES = [
        'next_period' => 'Hanya Periode Berikutnya',
        'n_periods' => 'Jumlah Periode Tertentu',
        'end_of_year' => 'Sampai Akhir Tahun Berjalan',
        'end_of_project' => 'Sampai Akhir Periode Proyek',
        'unlimited' => 'Tidak Kedaluwarsa Selama KPI Berlaku',
    ];

    public const INDICATOR_TYPES = [
        'Leading' => 'Leading Indicator',
        'Lagging' => 'Lagging Indicator',
        'Compliance' => 'Compliance',
        'Response' => 'Response',
        'Service Outcome' => 'Service Outcome',
    ];

    protected $table = 'hsse_kpis';

    protected $fillable = [
        'contractor_id',
        'code',
        'display_code',
        'name',
        'description',
        'official_target_text',
        'perspective',
        'sub_group',
        'indicator_type',
        'unit_of_measure',
        'target_value',
        'target_operator',
        'target_frequency',
        'evaluation_period',
        'input_method',
        'input_frequency',
        'calculation_method',
        'configuration_status',
        'configuration_note',
        'dynamic_target_source',
        'dynamic_target_multiplier',
        'rounding_policy',
        'shared_metric_key',
        'is_derived_view',
        'allow_carry_forward',
        'allow_credit_forward',
        'credit_validity_type',
        'credit_validity_value',
        'perspective_weight',
        'weight',
        'threshold_rules',
        'requires_evidence',
        'project_start_date',
        'project_end_date',
        'effective_from',
        'effective_until',
        'is_active',
        'sort_order',
        'legacy_hsse_plan_id',
    ];

    protected $casts = [
        'target_value' => 'float',
        'dynamic_target_multiplier' => 'float',
        'is_derived_view' => 'boolean',
        'allow_carry_forward' => 'boolean',
        'allow_credit_forward' => 'boolean',
        'credit_validity_value' => 'integer',
        'perspective_weight' => 'float',
        'weight' => 'float',
        'threshold_rules' => 'array',
        'requires_evidence' => 'boolean',
        'project_start_date' => 'date:Y-m-d',
        'project_end_date' => 'date:Y-m-d',
        'effective_from' => 'date:Y-m-d',
        'effective_until' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (HsseKpi $kpi) {
            if (empty($kpi->perspective)) {
                $kpi->perspective = $kpi->indicator_type === 'Lagging'
                    ? 'Lagging Indicator'
                    : 'Leading Indicator';
            }
            if (empty($kpi->display_code)) {
                $kpi->display_code = $kpi->code;
            }
        });
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(HsseKpiPeriod::class, 'hsse_kpi_id')->orderBy('period_start');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(HsseKpiEntry::class, 'hsse_kpi_id')->orderByDesc('entry_date')->orderByDesc('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(HsseKpiAttachment::class, 'hsse_kpi_id')->orderByDesc('id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(HsseKpiNote::class, 'hsse_kpi_id')->orderByDesc('id');
    }

    public function balanceLedgers(): HasMany
    {
        return $this->hasMany(HsseKpiBalanceLedger::class, 'hsse_kpi_id')->orderBy('created_date')->orderBy('id');
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->target_frequency] ?? $this->target_frequency;
    }

    public function getCalculationLabelAttribute(): string
    {
        return self::CALCULATION_METHODS[$this->calculation_method] ?? $this->calculation_method;
    }

    public function getInputMethodLabelAttribute(): string
    {
        return self::INPUT_METHODS[$this->input_method] ?? $this->input_method;
    }

    public function getShownCodeAttribute(): string
    {
        return $this->display_code ?: $this->code;
    }

    public function getFormattedTargetAttribute(): string
    {
        if (!empty($this->official_target_text)) {
            return $this->official_target_text;
        }

        if ($this->target_operator === 'zero') {
            return '0' . ($this->unit_of_measure ? ' ' . $this->unit_of_measure : '');
        }

        $val = $this->target_value !== null
            ? (rtrim(rtrim(number_format((float) $this->target_value, 2, '.', ''), '0'), '.') ?: '0')
            : '-';

        $prefix = match ($this->target_operator) {
            'lte' => '≤ ',
            'lt' => '< ',
            'gte' => '≥ ',
            default => '',
        };

        if ($this->unit_of_measure === '%' || $this->unit_of_measure === 'Persen') {
            return $prefix . $val . '%';
        }

        return trim($prefix . $val . ($this->unit_of_measure ? ' ' . $this->unit_of_measure : ''));
    }
}
