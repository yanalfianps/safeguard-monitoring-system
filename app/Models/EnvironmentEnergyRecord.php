<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentEnergyRecord extends Model
{
    use HasFactory;

    protected $table = 'environment_energy_records';

    protected $fillable = [
        'energy_type',
        'month',
        'year',
        'usage_amount',
        'unit',
        'description',
        'attachment_paths',
        'created_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'usage_amount' => 'float',
        'attachment_paths' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function monthNames(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function getMonthNameAttribute(): string
    {
        return self::monthNames()[$this->month] ?? (string) $this->month;
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }

    public function getAttachmentListAttribute(): array
    {
        return is_array($this->attachment_paths) ? array_values(array_filter($this->attachment_paths)) : [];
    }
}

