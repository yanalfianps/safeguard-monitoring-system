<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransfer extends Model
{
    use HasFactory;

    protected $table = 'budget_transfers';

    protected $fillable = [
        'transfer_number',
        'source_wbs_id',
        'destination_wbs_id',
        'amount',
        'transfer_date',
        'reason',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'transfer_date' => 'date',
    ];

    public function sourceWbs(): BelongsTo
    {
        return $this->belongsTo(BudgetWbs::class, 'source_wbs_id');
    }

    public function destinationWbs(): BelongsTo
    {
        return $this->belongsTo(BudgetWbs::class, 'destination_wbs_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function formatRupiah($val): string
    {
        return 'Rp' . number_format((float) $val, 0, ',', '.');
    }

    public static function generateNextTransferNumber(): string
    {
        $year = date('Y');
        $prefix = "TRF-{$year}-";
        $latest = static::where('transfer_number', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->transfer_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix . $seq;
    }
}

