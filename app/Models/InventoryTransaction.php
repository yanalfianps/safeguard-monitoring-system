<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'transaction_code',
        'inventory_item_id',
        'transaction_type',
        'jenis_transaksi',
        'location',
        'satuan',
        'quantity',
        'jumlah',
        'transaction_date',
        'tanggal',
        'unit',
        'person_in_charge',
        'petugas',
        'reference_number',
        'notes',
        'keterangan',
        'expired_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'jumlah' => 'integer',
        'transaction_date' => 'date',
        'tanggal' => 'date',
        'expired_date' => 'date',
    ];

    public static function generateTransactionCode(): string
    {
        $datePrefix = 'TRX-' . now()->format('Ymd');
        $seq = static::whereDate('created_at', now()->toDateString())->count() + 1;
        do {
            $candidate = sprintf('%s-%04d', $datePrefix, $seq);
            $exists = static::where('transaction_code', $candidate)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $candidate;
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryTransaction $trx) {
            if (empty($trx->transaction_code)) {
                $trx->transaction_code = static::generateTransactionCode();
            }

            $rawType = strtolower((string) ($trx->jenis_transaksi ?: $trx->transaction_type ?: 'masuk'));
            if (!in_array($trx->transaction_type, ['Masuk', 'Keluar'], true)) {
                $trx->transaction_type = $rawType === 'keluar' ? 'Keluar' : 'Masuk';
            }
            $trx->jenis_transaksi = $rawType;

            $loc = InventoryItem::normalizeLocation($trx->location ?: $trx->unit);
            $trx->location = $loc;
            $trx->unit = $trx->unit ?: $loc;

            $qty = (int) ($trx->jumlah ?? $trx->quantity ?? 0);
            $trx->quantity = $qty;
            $trx->jumlah = $qty;

            $date = $trx->tanggal ?: ($trx->transaction_date ?: now()->toDateString());
            $trx->transaction_date = $date;
            $trx->tanggal = $date;

            $pic = $trx->petugas ?: ($trx->person_in_charge ?: 'Petugas HSSE');
            $trx->person_in_charge = $pic;
            $trx->petugas = $pic;

            $notes = $trx->keterangan ?? $trx->notes;
            $trx->notes = $notes;
            $trx->keterangan = $notes;
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
