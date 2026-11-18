<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsseKpiNote extends Model
{
    protected $table = 'hsse_kpi_notes';

    protected $fillable = [
        'hsse_kpi_id',
        'hsse_kpi_period_id',
        'note',
        'note_type',
        'author_id',
        'author_name',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(HsseKpi::class, 'hsse_kpi_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(HsseKpiPeriod::class, 'hsse_kpi_period_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getContractorAttribute(): ?Contractor
    {
        return $this->kpi?->contractor;
    }
}
