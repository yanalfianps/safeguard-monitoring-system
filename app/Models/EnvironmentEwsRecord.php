<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentEwsRecord extends Model
{
    use HasFactory;

    protected $table = 'environment_ews_records';

    protected $fillable = [
        'location_id',
        'location_name',
        'recording_date',
        'condition',
        'description',
        'attachment_paths',
        'recorded_by',
    ];

    protected $casts = [
        'recording_date' => 'date',
        'attachment_paths' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'location_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getLocationLabelAttribute(): string
    {
        return $this->location?->name ?? $this->location_name ?? '-';
    }

    public function getAttachmentListAttribute(): array
    {
        return is_array($this->attachment_paths) ? array_values(array_filter($this->attachment_paths)) : [];
    }
}

