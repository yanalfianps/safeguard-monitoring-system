<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorCalendar extends Model
{
    public const MODE_MON_FRI_OVERTIME = 'mon_fri_overtime';
    public const MODE_FULL_WEEK_3SHIFT = 'full_week_3shift';
    public const MODE_MON_SAT_SCHEDULED_SUN = 'mon_sat_scheduled_sun';
    public const MODE_CUSTOM = 'custom';

    public const MODES = [
        self::MODE_MON_FRI_OVERTIME => 'Senin–Jumat Normal, Sabtu–Minggu Lembur (Tercatat)',
        self::MODE_FULL_WEEK_3SHIFT => 'Senin–Minggu Operasional Penuh (3 Shift: Pagi, Sore, Malam)',
        self::MODE_MON_SAT_SCHEDULED_SUN => 'Senin–Sabtu Efektif, Minggu Sesuai Jadwal Aktual',
        self::MODE_CUSTOM => 'Kalender Kustom',
    ];

    protected $table = 'contractor_calendars';

    protected $fillable = [
        'contractor_id',
        'name',
        'calendar_mode',
        'working_days',
        'overtime_days',
        'count_overtime_only_when_logged',
        'shifts_per_day',
        'shift_names',
        'holidays',
        'effective_from',
        'effective_until',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'working_days' => 'array',
        'overtime_days' => 'array',
        'count_overtime_only_when_logged' => 'boolean',
        'shifts_per_day' => 'integer',
        'shift_names' => 'array',
        'holidays' => 'array',
        'effective_from' => 'date:Y-m-d',
        'effective_until' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function getModeLabelAttribute(): string
    {
        return self::MODES[$this->calendar_mode] ?? $this->calendar_mode;
    }

    public function getStandardWorkingDaysPerWeekAttribute(): int
    {
        $days = is_array($this->working_days) ? count($this->working_days) : 5;
        return max(1, $days);
    }
}

