<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockBatch extends Model
{
    use HasFactory;

    protected $table = 'inventory_stock_batches';

    public const EXPIRY_WARNING_DAYS = 30;

    protected $fillable = [
        'inventory_item_id',
        'location',
        'expired_date',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'quantity' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Format tampilan tanggal expired atau 'Belum Dicatat' jika kosong.
     */
    public function getFormattedExpiredDateAttribute(): string
    {
        if (!$this->expired_date) {
            return 'Belum Dicatat';
        }

        return Carbon::parse($this->expired_date)->format('d/m/Y');
    }

    /**
     * Kunci identifikasi kelompok expired ('YYYY-MM-DD' atau 'UNRECORDED').
     */
    public function getExpiryKeyAttribute(): string
    {
        if (!$this->expired_date) {
            return 'UNRECORDED';
        }

        return Carbon::parse($this->expired_date)->format('Y-m-d');
    }

    /**
     * Evaluasi status kedaluwarsa untuk tanggal expired tertentu.
     *
     * @param string|\Carbon\Carbon|null $expiredDate
     * @return array{status: string, label: string, badge_class: string, days_remaining: int|null}
     */
    public static function evaluateExpiryStatus($expiredDate): array
    {
        if (empty($expiredDate) || $expiredDate === 'UNRECORDED' || $expiredDate === 'Belum Dicatat') {
            return [
                'status' => 'unrecorded',
                'label' => 'Belum Dicatat',
                'badge_class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                'days_remaining' => null,
            ];
        }

        $today = Carbon::today();
        $exp = Carbon::parse($expiredDate)->startOfDay();
        $diffDays = (int) $today->diffInDays($exp, false);

        if ($diffDays < 0) {
            return [
                'status' => 'expired',
                'label' => 'Sudah Expired',
                'badge_class' => 'bg-danger text-white',
                'days_remaining' => $diffDays,
            ];
        }

        if ($diffDays <= self::EXPIRY_WARNING_DAYS) {
            return [
                'status' => 'near_expiry',
                'label' => $diffDays === 0 ? 'Expired Hari Ini' : "Mendekati Expired ({$diffDays} hr)",
                'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                'days_remaining' => $diffDays,
            ];
        }

        return [
            'status' => 'safe',
            'label' => 'Aman / Berlaku',
            'badge_class' => 'bg-success-subtle text-success border border-success-subtle',
            'days_remaining' => $diffDays,
        ];
    }

    public function getExpiryStatusAttribute(): array
    {
        return static::evaluateExpiryStatus($this->expired_date);
    }
}

