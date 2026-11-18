<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentLb3Record extends Model
{
    use HasFactory;

    protected $table = 'environment_lb3_records';

    protected $fillable = [
        'waste_name',
        'amount',
        'unit_id',
        'unit_name',
        'entry_date',
        'characteristics',
        'description',
        'attachment_paths',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'entry_date' => 'date',
        'characteristics' => 'array',
        'attachment_paths' => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EnvironmentMaster::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUnitLabelAttribute(): string
    {
        return $this->unit?->name ?? $this->unit_name ?? '-';
    }

    public function getCharacteristicsListAttribute(): array
    {
        return is_array($this->characteristics) ? $this->characteristics : [];
    }

    public function getAttachmentListAttribute(): array
    {
        return is_array($this->attachment_paths) ? array_values(array_filter($this->attachment_paths)) : [];
    }
}

