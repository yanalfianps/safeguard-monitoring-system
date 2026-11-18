<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBorrowing extends Model
{
    use HasFactory;

    protected $table = 'inventory_borrowings';

    public const STATUS_BORROWED = 'Sedang Dipinjam';
    public const STATUS_PARTIAL = 'Sebagian Kembali';
    public const STATUS_OVERDUE = 'Melewati Tenggat';
    public const STATUS_RETURNED = 'Sudah Dikembalikan';

    public bool $stockAlreadyAdjusted = false;

    protected $fillable = [
        'borrow_code',
        'inventory_item_id',
        'location',
        'quantity_borrowed',
        'quantity_returned',
        'borrower_name',
        'department',
        'borrow_date',
        'expected_return_date',
        'expired_date',
        'expired_batches',
        'due_date_history',
        'actual_return_date',
        'status',
        'purpose',
        'notes',
        'recorded_by',
        'user_id',
    ];

    protected $casts = [
        'quantity_borrowed' => 'integer',
        'quantity_returned' => 'integer',
        'borrow_date' => 'datetime',
        'expected_return_date' => 'date',
        'expired_date' => 'date',
        'expired_batches' => 'array',
        'due_date_history' => 'array',
        'actual_return_date' => 'datetime',
    ];

    public static function generateBorrowCode(): string
    {
        $prefix = 'PJM-' . now()->format('ymd');
        $seq = static::whereDate('created_at', now()->toDateString())->count() + 1;
        do {
            $candidate = sprintf('%s-%03d', $prefix, $seq);
            $exists = static::where('borrow_code', $candidate)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $candidate;
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryBorrowing $borrowing) {
            if (empty($borrowing->borrow_code)) {
                $borrowing->borrow_code = static::generateBorrowCode();
            }
            $borrowing->location = InventoryItem::normalizeLocation($borrowing->location);
        });

        static::created(function (InventoryBorrowing $borrowing) {
            if (!$borrowing->stockAlreadyAdjusted) {
                $netBorrowed = max(0, (int) $borrowing->quantity_borrowed - (int) $borrowing->quantity_returned);
                if ($netBorrowed > 0 && $borrowing->item) {
                    $prefExp = $borrowing->expired_date ? $borrowing->expired_date->format('Y-m-d') : null;
                    $allocations = $borrowing->item->deductBatchStock(
                        $borrowing->location ?: 'Warehouse',
                        $netBorrowed,
                        $prefExp,
                        false
                    );

                    if (empty($borrowing->expired_batches)) {
                        $batchesPayload = array_map(function ($a) {
                            return [
                                'batch_id' => $a['batch_id'] ?? null,
                                'location' => $a['location'] ?? 'Warehouse',
                                'expired_date' => $a['expired_date'] ?? null,
                                'quantity_borrowed' => (int) ($a['quantity'] ?? 0),
                                'quantity_returned' => 0,
                            ];
                        }, $allocations);

                        $nonNullDates = array_values(array_filter(array_column($batchesPayload, 'expired_date')));
                        sort($nonNullDates);
                        $earliestExp = $nonNullDates[0] ?? null;

                        $borrowing->expired_batches = $batchesPayload;
                        $borrowing->expired_date = $earliestExp;
                        $borrowing->saveQuietly();
                    }
                }
            }
        });
    }

    /**
     * Daftar rincian kelompok tanggal expired yang dipinjam pada transaksi peminjaman ini.
     * Jangan pernah mengarang tanggal palsu: jika tidak diketahui, tandai sebagai 'Belum Dicatat'.
     *
     * @return array<int, array{
     *   batch_id: int|null,
     *   location: string,
     *   expired_date: string|null,
     *   formatted_date: string,
     *   quantity_borrowed: int,
     *   quantity_returned: int,
     *   remaining_quantity: int,
     *   status: string,
     *   status_label: string,
     *   badge_class: string,
     *   days_remaining: int|null
     * }>
     */
    public function getExpiryDetailsAttribute(): array
    {
        $rawBatches = is_array($this->expired_batches) ? $this->expired_batches : [];

        if (empty($rawBatches)) {
            $fallbackExp = $this->expired_date ? $this->expired_date->format('Y-m-d') : null;
            $rawBatches = [[
                'batch_id' => null,
                'location' => $this->location ?: 'Warehouse',
                'expired_date' => $fallbackExp,
                'quantity_borrowed' => (int) $this->quantity_borrowed,
                'quantity_returned' => (int) $this->quantity_returned,
            ]];
        }

        $details = [];
        foreach ($rawBatches as $b) {
            $expDate = !empty($b['expired_date']) && $b['expired_date'] !== 'UNRECORDED' && $b['expired_date'] !== 'Belum Dicatat'
                ? Carbon::parse($b['expired_date'])->format('Y-m-d')
                : null;
            $statusInfo = InventoryStockBatch::evaluateExpiryStatus($expDate);
            $qtyBorrowed = max(0, (int) ($b['quantity_borrowed'] ?? $b['quantity'] ?? 0));
            $qtyReturned = max(0, (int) ($b['quantity_returned'] ?? 0));

            $details[] = [
                'batch_id' => $b['batch_id'] ?? null,
                'location' => $b['location'] ?? ($this->location ?: 'Warehouse'),
                'expired_date' => $expDate,
                'formatted_date' => $expDate ? Carbon::parse($expDate)->format('d/m/Y') : 'Belum Dicatat',
                'quantity_borrowed' => $qtyBorrowed,
                'quantity_returned' => $qtyReturned,
                'remaining_quantity' => max(0, $qtyBorrowed - $qtyReturned),
                'status' => $statusInfo['status'],
                'status_label' => $statusInfo['label'],
                'badge_class' => $statusInfo['badge_class'],
                'days_remaining' => $statusInfo['days_remaining'],
            ];
        }

        return $details;
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(InventoryBorrowingReturn::class, 'inventory_borrowing_id')
            ->orderBy('return_number', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Jumlah yang masih dipinjam (belum dikembalikan).
     */
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity_borrowed - (int) $this->quantity_returned);
    }

    /**
     * Apakah peminjaman sudah melewati rencana tanggal pengembalian (dan masih ada barang dipinjam).
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->remaining_quantity <= 0) {
            return false;
        }

        if (!$this->expected_return_date) {
            return false;
        }

        return Carbon::parse($this->expected_return_date)->endOfDay()->isPast();
    }

    /**
     * Status tampilan terstandarisasi:
     * - Sudah Dikembalikan (ketika remaining_quantity == 0)
     * - Melewati Tenggat (ketika remaining_quantity > 0 dan hari ini > expected_return_date)
     * - Sedang Dipinjam (ketika remaining_quantity > 0 dan belum lewat tenggat)
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->remaining_quantity <= 0) {
            return self::STATUS_RETURNED;
        }

        if ($this->is_overdue) {
            return self::STATUS_OVERDUE;
        }

        return self::STATUS_BORROWED;
    }

    /**
     * Tanggal pengembalian terakhir (dari tabel returns atau actual_return_date).
     */
    public function getLastReturnDateAttribute(): ?Carbon
    {
        if ($this->relationLoaded('returns') && $this->returns->isNotEmpty()) {
            $last = $this->returns->last();
            return $last?->return_date ? Carbon::parse($last->return_date) : null;
        }

        return $this->actual_return_date ? Carbon::parse($this->actual_return_date) : null;
    }

    /**
     * Daftar riwayat kronologis (Peminjaman + Pengembalian 1..N) untuk Detail Modal.
     */
    public function getChronologicalTimelineAttribute(): array
    {
        $rows = [
            [
                'keterangan' => 'Peminjaman',
                'jumlah' => (int) $this->quantity_borrowed,
                'tanggal' => $this->borrow_date ? $this->borrow_date->format('d/m/Y H:i') : '-',
                'lokasi' => $this->location ?: 'Warehouse',
                'petugas' => $this->recorded_by ?: '-',
                'catatan' => $this->purpose ?: ($this->notes ?: '-'),
            ],
        ];

        $returnRecords = $this->relationLoaded('returns')
            ? $this->returns
            : $this->returns()->get();

        foreach ($returnRecords as $idx => $ret) {
            $num = $ret->return_number ?: ($idx + 1);
            $rows[] = [
                'keterangan' => 'Pengembalian ' . $num,
                'jumlah' => (int) $ret->quantity_returned,
                'tanggal' => $ret->return_date ? Carbon::parse($ret->return_date)->format('d/m/Y H:i') : '-',
                'lokasi' => $ret->return_location ?: ($this->location ?: 'Warehouse'),
                'petugas' => $ret->recorded_by ?: '-',
                'catatan' => $ret->notes ?: ($ret->condition ?: '-'),
            ];
        }

        if ($returnRecords->isEmpty() && (int) $this->quantity_returned > 0) {
            $rows[] = [
                'keterangan' => 'Pengembalian 1',
                'jumlah' => (int) $this->quantity_returned,
                'tanggal' => $this->actual_return_date ? Carbon::parse($this->actual_return_date)->format('d/m/Y H:i') : '-',
                'lokasi' => $this->location ?: 'Warehouse',
                'petugas' => $this->recorded_by ?: '-',
                'catatan' => $this->notes ?: '-',
            ];
        }

        return $rows;
    }
}
