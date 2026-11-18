<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentWeatherRecord extends Model
{
    use HasFactory;

    protected $table = 'environment_weather_records';

    protected $fillable = [
        'recording_date',
        'recording_time',
        'day_name',
        'location_id',
        'location_name',
        'wind_speed',
        'rainfall',
        'temperature',
        'humidity',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'recording_date' => 'date',
        'wind_speed' => 'float',
        'rainfall' => 'float',
        'temperature' => 'float',
        'humidity' => 'float',
    ];

    protected $appends = [
        'day_name',
        'formatted_day_date',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            if (!empty($model->recording_date)) {
                $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $carbonDate = is_string($model->recording_date)
                    ? \Carbon\Carbon::parse($model->recording_date)
                    : $model->recording_date;
                $model->day_name = $days[$carbonDate->dayOfWeek] ?? null;
            }
        });
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'location_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getDayNameAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }
        if ($this->recording_date) {
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            return $days[$this->recording_date->dayOfWeek] ?? '-';
        }
        return '-';
    }

    public function getFormattedDayDateAttribute(): string
    {
        $d = $this->day_name;
        $date = $this->formatted_date;
        return ($d !== '-' ? $d . ', ' : '') . $date;
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->location?->name ?? $this->location_name ?? '-';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->recording_date ? $this->recording_date->format('d/m/Y') : '-';
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->recording_time ? substr((string) $this->recording_time, 0, 5) : '-';
    }

    public function getWindSpeedLabelAttribute(): string
    {
        return number_format($this->wind_speed, 2, ',', '.') . ' m/s';
    }

    public function getRainfallLabelAttribute(): string
    {
        return number_format($this->rainfall, 2, ',', '.') . ' mm/hr';
    }

    public function getTemperatureLabelAttribute(): string
    {
        return number_format($this->temperature, 1, ',', '.') . ' °C';
    }

    public function getHumidityLabelAttribute(): string
    {
        return number_format($this->humidity, 1, ',', '.') . ' %';
    }
}

