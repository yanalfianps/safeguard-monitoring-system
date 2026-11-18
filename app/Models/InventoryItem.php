<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';

    public const LOCATION_WAREHOUSE = 'Warehouse';
    public const LOCATION_HSSE = 'Gudang HSSE';

    public const LOCATIONS = [
        self::LOCATION_WAREHOUSE,
        self::LOCATION_HSSE,
    ];

    public const SATUAN_LIST = [
        'Meter',
        'Liter',
        'Botol',
        'Mililiter',
        'Gram',
        'Kilogram',
        'Box',
        'Batang',
        'cm',
        'Ton',
        'Drum',
        'Inch',
    ];

    public const CATEGORIES = [
        'APD (Alat Pelindung Diri)',
        'APD Habis Pakai',
        'Peralatan Pemadam (APAR & Hydrant)',
        'Peralatan P3K & Medis',
        'Rambu & Barikade K3 (Safety Sign)',
        'Perlengkapan Kerja di Ketinggian',
        'Detektor & Instrumen Lingkungan',
        'Peralatan Emergency & Rescue',
    ];

    protected $fillable = [
        'item_code',
        'kode_barang',
        'item_name',
        'nama_barang',
        'category',
        'kategori',
        'unit_measure',
        'satuan',
        'stock',
        'stok_sekarang',
        'stock_warehouse',
        'stock_hsse',
        'minimum_stock',
        'stok_minimum',
        'location',
        'lokasi_default',
        'condition',
        'description',
        'keterangan',
        'expired_date',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'integer',
        'stok_sekarang' => 'integer',
        'stock_warehouse' => 'integer',
        'stock_hsse' => 'integer',
        'minimum_stock' => 'integer',
        'stok_minimum' => 'integer',
        'expired_date' => 'date',
        'is_active' => 'boolean',
    ];

    public static function generateUniqueItemCode(?string $category = null): string
    {
        $prefix = 'INV';
        if ($category) {
            $catUpper = strtoupper($category);
            if (str_contains($catUpper, 'APD')) {
                $prefix = 'APD';
            } elseif (str_contains($catUpper, 'PEMADAM') || str_contains($catUpper, 'APAR')) {
                $prefix = 'APR';
            } elseif (str_contains($catUpper, 'P3K') || str_contains($catUpper, 'MEDIS')) {
                $prefix = 'MED';
            } elseif (str_contains($catUpper, 'RAMBU') || str_contains($catUpper, 'SIGN')) {
                $prefix = 'SGN';
            } elseif (str_contains($catUpper, 'KETINGGIAN')) {
                $prefix = 'HGT';
            } elseif (str_contains($catUpper, 'DETEKTOR') || str_contains($catUpper, 'INSTRUMEN')) {
                $prefix = 'DET';
            } elseif (str_contains($catUpper, 'EMERGENCY') || str_contains($catUpper, 'RESCUE')) {
                $prefix = 'EMG';
            }
        }

        $count = static::count() + 1;
        do {
            $candidate = sprintf('%s-%04d', $prefix, $count);
            $exists = static::where('item_code', $candidate)
                ->orWhere('kode_barang', $candidate)
                ->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $candidate;
    }

    public static function normalizeLocation(?string $location): string
    {
        $raw = strtolower(trim((string) $location));
        if (str_contains($raw, 'hsse')) {
            return self::LOCATION_HSSE;
        }
        return self::LOCATION_WAREHOUSE;
    }

    protected static function booted(): void
    {
        static::saving(function (InventoryItem $item) {
            // 1. Auto-generate unique Kode Barang if omitted
            if (empty($item->item_code) && empty($item->kode_barang)) {
                $generated = static::generateUniqueItemCode($item->kategori ?? $item->category);
                $item->item_code = $generated;
                $item->kode_barang = $generated;
            } else {
                $item->item_code = $item->item_code ?: $item->kode_barang;
                $item->kode_barang = $item->kode_barang ?: $item->item_code;
            }

            // 2. Sync name, category, unit, description
            $item->item_name = $item->item_name ?: ($item->nama_barang ?: 'Barang HSSE');
            $item->nama_barang = $item->nama_barang ?: $item->item_name;

            $item->category = $item->category ?: ($item->kategori ?: 'APD (Alat Pelindung Diri)');
            $item->kategori = $item->kategori ?: $item->category;

            $item->unit_measure = $item->unit_measure ?: ($item->satuan ?: 'Box');
            $item->satuan = $item->satuan ?: $item->unit_measure;

            $item->description = $item->description ?? $item->keterangan;
            $item->keterangan = $item->keterangan ?? $item->description;

            $item->minimum_stock = (int) ($item->minimum_stock ?? $item->stok_minimum ?? 0);
            $item->stok_minimum = $item->minimum_stock;

            // 3. Sync dual-location stock (Warehouse & Gudang HSSE) and total stock
            $hasExplicitDualStock = $item->isDirty('stock_warehouse')
                || $item->isDirty('stock_hsse')
                || ((int) $item->stock_warehouse > 0)
                || ((int) $item->stock_hsse > 0);

            if (!$hasExplicitDualStock && ($item->isDirty('stock') || $item->isDirty('stok_sekarang'))) {
                // Fallback when created/updated via legacy single-stock parameter
                $fallbackTotal = max(0, (int) ($item->isDirty('stok_sekarang') ? $item->stok_sekarang : $item->stock));
                $targetLoc = static::normalizeLocation($item->location ?? $item->lokasi_default);
                if ($targetLoc === self::LOCATION_HSSE) {
                    $item->stock_hsse = $fallbackTotal;
                    $item->stock_warehouse = 0;
                } else {
                    $item->stock_warehouse = $fallbackTotal;
                    $item->stock_hsse = 0;
                }
            }

            $item->stock_warehouse = max(0, (int) ($item->stock_warehouse ?? 0));
            $item->stock_hsse = max(0, (int) ($item->stock_hsse ?? 0));

            $totalStock = $item->stock_warehouse + $item->stock_hsse;
            $item->stock = $totalStock;
            $item->stok_sekarang = $totalStock;

            // 4. Maintain readable location summary
            if ($item->stock_warehouse > 0 && $item->stock_hsse > 0) {
                $locSummary = 'Warehouse & Gudang HSSE';
            } elseif ($item->stock_hsse > 0) {
                $locSummary = self::LOCATION_HSSE;
            } else {
                $locSummary = self::LOCATION_WAREHOUSE;
            }
            $item->location = $locSummary;
            $item->lokasi_default = $locSummary;
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(InventoryStockBatch::class, 'inventory_item_id')
            ->orderBy('location', 'asc')
            ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expired_date', 'asc');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(InventoryBorrowing::class, 'inventory_item_id');
    }

    public function activeBorrowings(): HasMany
    {
        return $this->hasMany(InventoryBorrowing::class, 'inventory_item_id')
            ->whereColumn('quantity_borrowed', '>', 'quantity_returned');
    }

    public function getStockAtLocation(string $location): int
    {
        $normalized = static::normalizeLocation($location);
        return $normalized === self::LOCATION_HSSE
            ? (int) $this->stock_hsse
            : (int) $this->stock_warehouse;
    }

    public function adjustLocationStock(string $location, int $delta): void
    {
        $normalized = static::normalizeLocation($location);
        if ($normalized === self::LOCATION_HSSE) {
            $this->stock_hsse = max(0, (int) $this->stock_hsse + $delta);
        } else {
            $this->stock_warehouse = max(0, (int) $this->stock_warehouse + $delta);
        }
        $this->save();
    }

    /**
     * Pastikan jumlah stok pada tabel inventory_stock_batches di setiap lokasi
     * selalu konsisten 100% dengan stock_warehouse dan stock_hsse pada master barang.
     */
    public function ensureBatchesSynced(): void
    {
        if (!$this->exists) {
            return;
        }

        foreach (self::LOCATIONS as $loc) {
            $targetLocStock = $this->getStockAtLocation($loc);
            $batchesAtLoc = InventoryStockBatch::where('inventory_item_id', $this->id)
                ->where('location', $loc)
                ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('expired_date', 'asc')
                ->get();

            $currentBatchSum = (int) $batchesAtLoc->sum('quantity');

            if ($currentBatchSum < $targetLocStock) {
                $diff = $targetLocStock - $currentBatchSum;
                $defaultExp = $this->expired_date ? $this->expired_date->format('Y-m-d') : null;

                $targetBatch = $batchesAtLoc->first(function (InventoryStockBatch $b) use ($defaultExp) {
                    $bExp = $b->expired_date ? $b->expired_date->format('Y-m-d') : null;
                    return $bExp === $defaultExp;
                });

                if ($targetBatch) {
                    $targetBatch->quantity = (int) $targetBatch->quantity + $diff;
                    $targetBatch->save();
                } else {
                    InventoryStockBatch::create([
                        'inventory_item_id' => $this->id,
                        'location' => $loc,
                        'expired_date' => $defaultExp,
                        'quantity' => $diff,
                        'notes' => 'Stok awal / penyesuaian lokasi',
                    ]);
                }
            } elseif ($currentBatchSum > $targetLocStock) {
                $excess = $currentBatchSum - $targetLocStock;
                foreach ($batchesAtLoc as $b) {
                    if ($excess <= 0) {
                        break;
                    }
                    $reducible = min((int) $b->quantity, $excess);
                    if ($reducible > 0) {
                        $b->quantity = max(0, (int) $b->quantity - $reducible);
                        $b->save();
                        $excess -= $reducible;
                    }
                }
            }
        }
    }

    /**
     * Sinkronkan kolom expired_date utama pada master barang dengan tanggal expired terdekat yang masih memiliki stok > 0.
     */
    public function refreshMasterEarliestExpiry(): void
    {
        $earliestBatch = InventoryStockBatch::where('inventory_item_id', $this->id)
            ->where('quantity', '>', 0)
            ->whereNotNull('expired_date')
            ->orderBy('expired_date', 'asc')
            ->first();

        if ($earliestBatch && $earliestBatch->expired_date) {
            $this->expired_date = $earliestBatch->expired_date->format('Y-m-d');
            $this->save();
        }
    }

    /**
     * Tambahkan stok masuk ke lokasi & kelompok tanggal expired (null = Belum Dicatat).
     */
    public function addBatchStock(string $location, int $quantity, ?string $expiredDate = null, ?string $notes = null): InventoryStockBatch
    {
        $normalizedLoc = static::normalizeLocation($location);
        $cleanExp = (!empty($expiredDate) && $expiredDate !== 'UNRECORDED' && $expiredDate !== 'Belum Dicatat')
            ? \Carbon\Carbon::parse($expiredDate)->format('Y-m-d')
            : null;

        $this->ensureBatchesSynced();

        $query = InventoryStockBatch::where('inventory_item_id', $this->id)
            ->where('location', $normalizedLoc);

        if ($cleanExp === null) {
            $query->whereNull('expired_date');
        } else {
            $query->whereDate('expired_date', $cleanExp);
        }

        $batch = $query->first();
        if ($batch) {
            $batch->quantity = max(0, (int) $batch->quantity + $quantity);
            if ($notes) {
                $batch->notes = $notes;
            }
            $batch->save();
        } else {
            $batch = InventoryStockBatch::create([
                'inventory_item_id' => $this->id,
                'location' => $normalizedLoc,
                'expired_date' => $cleanExp,
                'quantity' => max(0, $quantity),
                'notes' => $notes,
            ]);
        }

        $this->adjustLocationStock($normalizedLoc, +$quantity);
        $this->refreshMasterEarliestExpiry();

        return $batch;
    }

    /**
     * Kurangi stok dari lokasi & kelompok tanggal expired tertentu (atau otomatis FEFO jika tidak dipilih).
     *
     * @return array<int, array{batch_id: int|null, location: string, expired_date: string|null, quantity: int}>
     */
    public function deductBatchStock(string $location, int $quantity, ?string $preferredExpiredDate = null, bool $strictBatch = false): array
    {
        $normalizedLoc = static::normalizeLocation($location);
        $this->ensureBatchesSynced();

        $isExplicitUnrecorded = ($preferredExpiredDate === 'UNRECORDED' || $preferredExpiredDate === 'Belum Dicatat');
        $cleanExp = (!$isExplicitUnrecorded && !empty($preferredExpiredDate))
            ? \Carbon\Carbon::parse($preferredExpiredDate)->format('Y-m-d')
            : null;

        $allocations = [];
        $remaining = $quantity;

        if ($strictBatch || $isExplicitUnrecorded || $cleanExp !== null) {
            $batchQuery = InventoryStockBatch::where('inventory_item_id', $this->id)
                ->where('location', $normalizedLoc);

            if ($isExplicitUnrecorded || ($strictBatch && $cleanExp === null)) {
                $batchQuery->whereNull('expired_date');
            } else {
                $batchQuery->whereDate('expired_date', $cleanExp);
            }

            $matchingBatches = $batchQuery->orderBy('id', 'asc')->get();
            $availableInGroup = (int) $matchingBatches->sum('quantity');

            if ($strictBatch && $quantity > $availableInGroup) {
                $labelExp = $cleanExp ? \Carbon\Carbon::parse($cleanExp)->format('d/m/Y') : 'Belum Dicatat';
                throw new \RuntimeException(
                    "Jumlah yang diminta ({$quantity} {$this->satuan}) untuk kelompok expired [{$labelExp}] di {$normalizedLoc} melebihi stok kelompok tersebut (Tersedia: {$availableInGroup} {$this->satuan})!"
                );
            }

            foreach ($matchingBatches as $b) {
                if ($remaining <= 0) {
                    break;
                }
                $avail = max(0, (int) $b->quantity);
                if ($avail <= 0) {
                    continue;
                }
                $take = min($remaining, $avail);
                $b->quantity = $avail - $take;
                $b->save();
                $remaining -= $take;

                $allocations[] = [
                    'batch_id' => $b->id,
                    'location' => $normalizedLoc,
                    'expired_date' => $b->expired_date ? $b->expired_date->format('Y-m-d') : null,
                    'quantity' => $take,
                ];
            }
        }

        // Jika tidak strictBatch dan masih ada sisa yang perlu diambil, gunakan FEFO (First Expired First Out, lalu Belum Dicatat)
        if ($remaining > 0 && !$strictBatch) {
            $fefoBatches = InventoryStockBatch::where('inventory_item_id', $this->id)
                ->where('location', $normalizedLoc)
                ->where('quantity', '>', 0)
                ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('expired_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($fefoBatches as $b) {
                if ($remaining <= 0) {
                    break;
                }
                $avail = max(0, (int) $b->quantity);
                if ($avail <= 0) {
                    continue;
                }
                $take = min($remaining, $avail);
                $b->quantity = $avail - $take;
                $b->save();
                $remaining -= $take;

                $allocations[] = [
                    'batch_id' => $b->id,
                    'location' => $normalizedLoc,
                    'expired_date' => $b->expired_date ? $b->expired_date->format('Y-m-d') : null,
                    'quantity' => $take,
                ];
            }
        }

        $this->adjustLocationStock($normalizedLoc, -$quantity);
        $this->refreshMasterEarliestExpiry();

        if (empty($allocations)) {
            $allocations[] = [
                'batch_id' => null,
                'location' => $normalizedLoc,
                'expired_date' => $cleanExp,
                'quantity' => $quantity,
            ];
        }

        return $allocations;
    }

    /**
     * Ambil daftar rincian kelompok stok berdasarkan lokasi & tanggal expired (untuk modal Lihat Stok & Expired serta dropdown peminjaman).
     */
    public function getStockExpiryGroups(bool $onlyPositive = true): array
    {
        $this->ensureBatchesSynced();

        $query = InventoryStockBatch::where('inventory_item_id', $this->id)
            ->orderByRaw("CASE WHEN location = 'Warehouse' THEN 0 ELSE 1 END ASC")
            ->orderByRaw('CASE WHEN expired_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expired_date', 'asc');

        if ($onlyPositive) {
            $query->where('quantity', '>', 0);
        }

        $rows = $query->get();

        // Gabungkan jika ada baris dengan lokasi & tanggal expired yang persis sama
        $grouped = [];
        foreach ($rows as $row) {
            $loc = static::normalizeLocation($row->location);
            $expKey = $row->expired_date ? $row->expired_date->format('Y-m-d') : 'UNRECORDED';
            $compKey = $loc . '|' . $expKey;

            if (!isset($grouped[$compKey])) {
                $statusInfo = InventoryStockBatch::evaluateExpiryStatus($row->expired_date);
                $grouped[$compKey] = [
                    'batch_id' => $row->id,
                    'location' => $loc,
                    'expired_date' => $row->expired_date ? $row->expired_date->format('Y-m-d') : null,
                    'formatted_date' => $row->expired_date ? $row->expired_date->format('d/m/Y') : 'Belum Dicatat',
                    'expiry_key' => $expKey,
                    'quantity' => 0,
                    'satuan' => $this->satuan,
                    'status' => $statusInfo['status'],
                    'status_label' => $statusInfo['label'],
                    'badge_class' => $statusInfo['badge_class'],
                    'days_remaining' => $statusInfo['days_remaining'],
                ];
            }

            $grouped[$compKey]['quantity'] += (int) $row->quantity;
        }

        return array_values($grouped);
    }

    public function canBeDeleted(): bool
    {
        return !$this->transactions()->exists() && !$this->borrowings()->exists();
    }

    public function getBarangIdAttribute(): ?string
    {
        return $this->attributes['kode_barang'] ?? $this->attributes['item_code'] ?? null;
    }

    public function getNamaBarangAttribute($value): ?string
    {
        return $value ?? ($this->attributes['item_name'] ?? null);
    }

    public function getKategoriAttribute($value): ?string
    {
        return $value ?? ($this->attributes['category'] ?? null);
    }

    public function getSatuanAttribute($value): ?string
    {
        return $value ?? ($this->attributes['unit_measure'] ?? null);
    }

    public function getCurrentStockAttribute(): int
    {
        return (int) ($this->attributes['stock_warehouse'] ?? 0) + (int) ($this->attributes['stock_hsse'] ?? 0);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->current_stock;
    }

    public function getBorrowedStockAttribute(): int
    {
        return (int) $this->borrowings()
            ->whereColumn('quantity_borrowed', '>', 'quantity_returned')
            ->get()
            ->sum(fn ($b) => max(0, (int) $b->quantity_borrowed - (int) $b->quantity_returned));
    }

    /**
     * Available stock across Warehouse + Gudang HSSE (borrowed items are already deducted from location stock).
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->current_stock;
    }

    public function getIsConsumableAttribute(): bool
    {
        $cat = strtolower((string) ($this->kategori ?? $this->category ?? ''));
        return str_contains($cat, 'habis pakai') || str_contains($cat, 'p3k') || str_contains($cat, 'medis');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= 0;
    }
}
