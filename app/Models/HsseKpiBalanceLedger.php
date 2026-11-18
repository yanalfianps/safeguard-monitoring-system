<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HsseKpiBalanceLedger extends Model
{
    public const TYPE_DEBT = 'debt';
    public const TYPE_CREDIT = 'credit';

    public const STATUS_OPEN = 'open';
    public const STATUS_PARTIAL = 'partially_used';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_EXPIRED = 'expired';

    protected $table = 'hsse_kpi_balance_ledgers';

    protected $fillable = [
        'hsse_kpi_id',
        'contractor_id',
        'scope_key',
        'source_period_id',
        'source_period_key',
        'ledger_type',
        'initial_quantity',
        'used_quantity',
        'remaining_quantity',
        'expired_quantity',
        'created_date',
        'expires_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'initial_quantity' => 'float',
        'used_quantity' => 'float',
        'remaining_quantity' => 'float',
        'expired_quantity' => 'float',
        'created_date' => 'date:Y-m-d',
        'expires_at' => 'date:Y-m-d',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(HsseKpi::class, 'hsse_kpi_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function sourcePeriod(): BelongsTo
    {
        return $this->belongsTo(HsseKpiPeriod::class, 'source_period_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HsseKpiLedgerAllocation::class, 'ledger_id')->orderBy('allocated_at')->orderBy('id');
    }

    public function getBalanceTypeAttribute(): string
    {
        return (string) $this->ledger_type;
    }
}
