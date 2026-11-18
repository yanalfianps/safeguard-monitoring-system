<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsseKpiAttachment extends Model
{
    protected $table = 'hsse_kpi_attachments';

    protected $fillable = [
        'hsse_kpi_id',
        'hsse_kpi_entry_id',
        'hsse_kpi_period_id',
        'original_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(HsseKpi::class, 'hsse_kpi_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(HsseKpiEntry::class, 'hsse_kpi_entry_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(HsseKpiPeriod::class, 'hsse_kpi_period_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

