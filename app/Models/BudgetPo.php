<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPo extends Model
{
    use HasFactory;

    protected $table = 'budget_pos';

    protected $fillable = [
        'budget_wbs_id',
        'po_number',
        'program_name',
        'vendor',
        'po_value',
        'commitment_value',
        'po_date',
        'status',
        'description',
        'attachment_path',
        'attachment_paths',
        'created_by',
    ];

    protected $casts = [
        'po_value' => 'float',
        'commitment_value' => 'float',
        'po_date' => 'date',
        'attachment_paths' => 'array',
    ];

    /**
     * Mengambil daftar seluruh dokumen lampiran PO (mendukung data lama single-file dan data baru multiple-file)
     */
    public function getAttachmentListAttribute(): array
    {
        $list = [];
        if (!empty($this->attachment_paths) && is_array($this->attachment_paths)) {
            $list = $this->attachment_paths;
        }
        if (!empty($this->attachment_path) && !in_array($this->attachment_path, $list)) {
            array_unshift($list, $this->attachment_path);
        }
        return array_values(array_filter($list));
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(BudgetWbs::class, 'budget_wbs_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BudgetPoPayment::class, 'budget_po_id');
    }

    /**
     * Hitung Total Realisasi Pembayaran untuk PO ini
     */
    public function getTotalPaymentAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Hitung Sisa Komitmen PO (Nilai Komitmen - Total Pembayaran)
     */
    public function getRemainingCommitmentAttribute(): float
    {
        if ($this->status === 'Dibatalkan') {
            return 0.0;
        }

        $val = (float) ($this->po_value > 0 ? $this->po_value : $this->commitment_value);
        return max(0, (float) ($val - $this->total_payment));
    }

    /**
     * Helper Format Rupiah
     */
    public static function formatRupiah($val): string
    {
        return 'Rp' . number_format((float) $val, 0, ',', '.');
    }

    /**
     * Generate Nomor PO Baru
     */
    public static function generateNextPoNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-HSE-{$year}-";
        $latest = static::where('po_number', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->po_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $seq = '001';
        }

        return $prefix . $seq;
    }
}

