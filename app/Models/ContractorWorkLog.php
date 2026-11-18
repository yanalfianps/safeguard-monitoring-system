<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorWorkLog extends Model
{
    public const TYPE_OVERTIME_WORK = 'overtime_work';
    public const TYPE_WEEKEND_OVERTIME = 'overtime_work';
    public const TYPE_SUNDAY_SCHEDULE = 'sunday_schedule';
    public const TYPE_SHIFT_COVERAGE = 'shift_coverage';

    public const TYPES = [
        self::TYPE_OVERTIME_WORK => 'Lembur Sabtu/Minggu (PJSE)',
        self::TYPE_SUNDAY_SCHEDULE => 'Jadwal Kerja Hari Minggu (JOTRE)',
        self::TYPE_SHIFT_COVERAGE => 'Realisasi Coverage Shift Pos (TDP / Security)',
    ];

    protected $table = 'contractor_work_logs';

    protected $fillable = [
        'contractor_id',
        'work_date',
        'log_type',
        'resource_id',
        'shift_name',
        'required_count',
        'actual_count',
        'is_applicable_for_kpi',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'work_date' => 'date:Y-m-d',
        'required_count' => 'float',
        'actual_count' => 'float',
        'is_applicable_for_kpi' => 'boolean',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ContractorResource::class, 'resource_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
