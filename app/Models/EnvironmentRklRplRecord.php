<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentRklRplRecord extends Model
{
    use HasFactory;

    protected $table = 'environment_rkl_rpl_records';

    protected $fillable = [
        'parameter_id',
        'parameter_name',
        'location_id',
        'location_name',
        'sampling_date',
        'measurement_value',
        'unit_id',
        'unit_name',
        'description',
        'attachment_paths',
        'created_by',
    ];

    protected $casts = [
        'sampling_date' => 'date',
        'attachment_paths' => 'array',
    ];

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'parameter_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'location_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getParameterLabelAttribute(): string
    {
        return $this->parameter?->name ?? $this->parameter_name ?? '-';
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->location?->name ?? $this->location_name ?? '-';
    }

    public function getUnitLabelAttribute(): string
    {
        return $this->unit?->name ?? $this->unit_name ?? '';
    }

    public function getAttachmentListAttribute(): array
    {
        return is_array($this->attachment_paths) ? array_values(array_filter($this->attachment_paths)) : [];
    }
}

