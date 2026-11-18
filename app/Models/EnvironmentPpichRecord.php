<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentPpichRecord extends Model
{
    use HasFactory;

    protected $table = 'environment_ppich_records';

    protected $fillable = [
        'inspection_type_id',
        'inspection_type',
        'location_id',
        'location_name',
        'condition',
        'inspection_date',
        'sampling_date',
        'reference_id',
        'reference_name',
        'activity_id',
        'activity_name',
        'description',
        'attachment_paths',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'sampling_date' => 'date',
        'attachment_paths' => 'array',
    ];

    public function inspectionTypeMaster(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'inspection_type_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'location_id');
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'reference_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'activity_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getInspectionTypeLabelAttribute(): string
    {
        return $this->inspection_type ?: ($this->inspectionTypeMaster?->name ?? 'PPKH/Lahan');
    }

    public function getConditionTextAttribute(): string
    {
        return $this->condition ?: ($this->activity_name ?: ($this->description ?? '-'));
    }

    public function getMonitoringLokasiAttribute(): string
    {
        return $this->location_name ?: ($this->reference_name ?: ($this->location?->name ?? '-'));
    }

    public function getActivityTextAttribute(): string
    {
        return $this->condition_text;
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->location?->name ?? $this->location_name ?? '-';
    }

    public function getReferenceLabelAttribute(): string
    {
        return $this->reference?->name ?? $this->reference_name ?? '-';
    }

    public function getActivityLabelAttribute(): string
    {
        return $this->activity?->name ?? $this->activity_name ?? '-';
    }

    public function getAttachmentListAttribute(): array
    {
        return is_array($this->attachment_paths) ? array_values(array_filter($this->attachment_paths)) : [];
    }
}

