<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPoPayment extends Model
{
    use HasFactory;

    protected $table = 'budget_po_payments';

    protected $fillable = [
        'budget_po_id',
        'budget_wbs_id',
        'payment_number',
        'payment_date',
        'amount',
        'reference_number',
        'description',
        'proof_path',
        'proof_paths',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
        'proof_paths' => 'array',
    ];

    /**
     * Mengambil daftar seluruh bukti file (mendukung data lama single-file dan data baru multiple-file)
     */
    public function getProofListAttribute(): array
    {
        $list = [];
        if (!empty($this->proof_paths) && is_array($this->proof_paths)) {
            $list = $this->proof_paths;
        }
        if (!empty($this->proof_path) && !in_array($this->proof_path, $list)) {
            array_unshift($list, $this->proof_path);
        }
        return array_values(array_filter($list));
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(BudgetPo::class, 'budget_po_id');
    }

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

    public static function generateNextPaymentNumber(): string
    {
        $year = date('Y');
        $prefix = "PAY-PO-{$year}-";
        $latest = static::where('payment_number', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->payment_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix . $seq;
    }
}

