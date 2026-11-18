<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentInspectionRecord extends Model
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

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->sampling_date)) {
                $model->sampling_date = $model->inspection_date ?: now()->toDateString();
            }
            if (empty($model->inspection_date)) {
                $model->inspection_date = $model->sampling_date ?: now()->toDateString();
            }
            if (empty($model->reference_name) && !empty($model->location_name)) {
                $model->reference_name = $model->location_name;
            }
            if (empty($model->activity_name) && !empty($model->condition)) {
                $model->activity_name = $model->condition;
            }
            if (empty($model->description) && !empty($model->condition)) {
                $model->description = $model->condition;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('inspection_date') && !$model->isDirty('sampling_date')) {
                $model->sampling_date = $model->inspection_date;
            }
            if ($model->isDirty('location_name') && !$model->isDirty('reference_name')) {
                $model->reference_name = $model->location_name;
            }
            if ($model->isDirty('condition')) {
                if (!$model->isDirty('activity_name')) {
                    $model->activity_name = $model->condition;
                }
                if (!$model->isDirty('description')) {
                    $model->description = $model->condition;
                }
            }
        });
    }

    public function inspectionTypeMaster(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'inspection_type_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getInspectionTypeLabelAttribute(): string
    {
        return $this->inspection_type ?: ($this->inspectionTypeMaster?->name ?? 'PPKH/Lahan');
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->location_name ?: ($this->location?->name ?? ($this->reference_name ?? '-'));
    }

    public function getConditionTextAttribute(): string
    {
        return $this->condition ?: ($this->activity_name ?: ($this->description ?? '-'));
    }

    public function getEffectiveDateAttribute()
    {
        return $this->inspection_date ?: $this->sampling_date ?: $this->created_at;
    }

    public function getFormattedDateAttribute(): string
    {
        $d = $this->effective_date;
        return $d ? $d->format('d/m/Y') : '-';
    }

    public function getAttachmentListAttribute(): array
    {
        return is_array($this->attachment_paths) ? array_values(array_filter($this->attachment_paths)) : [];
    }

    // Backward compatibility attributes for legacy PPKH references
    public function getMonitoringLokasiAttribute(): string
    {
        return $this->location_label;
    }

    public function getActivityTextAttribute(): string
    {
        return $this->condition_text;
    }
}
