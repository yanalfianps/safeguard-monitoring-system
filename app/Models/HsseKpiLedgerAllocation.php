<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HsseKpiLedgerAllocation extends Model
{
    protected $table = 'hsse_kpi_ledger_allocations';

    protected $fillable = [
        'ledger_id',
        'hsse_kpi_id',
        'allocated_in_period_id',
        'allocated_in_period_key',
        'source_entry_id',
        'allocation_type',
        'quantity',
        'allocated_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'allocated_at' => 'date:Y-m-d',
    ];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(HsseKpiBalanceLedger::class, 'ledger_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(HsseKpi::class, 'hsse_kpi_id');
    }

    public function allocatedInPeriod(): BelongsTo
    {
        return $this->belongsTo(HsseKpiPeriod::class, 'allocated_in_period_id');
    }

    public function sourceEntry(): BelongsTo
    {
        return $this->belongsTo(HsseKpiEntry::class, 'source_entry_id');
    }
}

