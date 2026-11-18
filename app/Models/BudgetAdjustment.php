<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAdjustment extends Model
{
    use HasFactory;

    protected $table = 'budget_adjustments';

    protected $fillable = [
        'adjustment_number',
        'budget_wbs_id',
        'adjustment_type',
        'amount',
        'old_budget',
        'new_budget',
        'adjustment_date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'old_budget' => 'float',
        'new_budget' => 'float',
        'adjustment_date' => 'date',
    ];

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(BudgetWbs::class, 'budget_wbs_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function formatRupiah($val): string
    {
        return 'Rp' . number_format((float) $val, 0, ',', '.');
    }

    public static function generateNextAdjustmentNumber(): string
    {
        $year = date('Y');
        $prefix = "ADJ-{$year}-";
        $latest = static::where('adjustment_number', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->adjustment_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix . $seq;
    }
}

