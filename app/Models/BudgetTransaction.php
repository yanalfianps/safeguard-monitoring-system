<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransaction extends Model
{
    use HasFactory;

    protected $table = 'budget_transactions';

    public const TYPE_PO_COMMITMENT = 'PO_COMMITMENT';
    public const TYPE_PO_PAYMENT = 'PO_PAYMENT';
    public const TYPE_NON_PO = 'NON_PO';
    public const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    public const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';

    public const TYPE_LABELS = [
        self::TYPE_PO_COMMITMENT => 'Pembuatan / Komitmen PO',
        self::TYPE_PO_PAYMENT => 'Pembayaran PO',
        self::TYPE_NON_PO => 'Realisasi Non-PO',
        self::TYPE_TRANSFER_IN => 'Pengalihan Anggaran (Masuk)',
        self::TYPE_TRANSFER_OUT => 'Pengalihan Anggaran (Keluar)',
        self::TYPE_ADJUSTMENT => 'Penyesuaian Anggaran',
    ];

    protected $fillable = [
        'transaction_number',
        'transaction_type',
        'transaction_type_label',
        'transaction_date',
        'budget_wbs_id',
        'program_name',
        'reference_number',
        'amount',
        'description',
        'created_by',
        'related_model_type',
        'related_model_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'transaction_date' => 'date',
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

    public static function generateNextTransactionNumber(): string
    {
        $year = date('Y');
        $prefix = "TRX-{$year}-";
        $latest = static::where('transaction_number', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->transaction_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $seq = '00001';
        }

        return $prefix . $seq;
    }
}

