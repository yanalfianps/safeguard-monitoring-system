<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class WellPad extends Model
{
    use HasFactory;

    protected $table = 'well_pads';

    public const DEFAULT_WELL_PADS = [
        ['name' => 'Well Pad 7', 'code' => 'WP-07', 'is_active' => true, 'sort_order' => 1],
        ['name' => 'Well Pad 9', 'code' => 'WP-09', 'is_active' => true, 'sort_order' => 2],
        ['name' => 'Well Pad 28', 'code' => 'WP-28', 'is_active' => true, 'sort_order' => 3],
        ['name' => 'Well Pad 30', 'code' => 'WP-30', 'is_active' => true, 'sort_order' => 4],
        ['name' => 'Well Pad 31', 'code' => 'WP-31', 'is_active' => true, 'sort_order' => 5],
    ];

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'sort_order',
    ];

    protected static bool $isResequencing = false;

    protected static function booted(): void
    {
        static::saved(function () {
            static::resequenceSortOrders();
        });

        static::deleted(function () {
            static::resequenceSortOrders();
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(GasMonitoring::class, 'well_pad_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }

    /**
     * Urutkan koleksi Well Pad secara natural ascending (A-Z) berdasarkan nama.
     * Mendukung pemilahan angka dan huruf secara konsisten (contoh: Well 07A sebelum Well 07B, Well Pad 7 sebelum Well Pad 28).
     */
    public static function sortNaturally(iterable $items): Collection
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        return $collection->sort(function ($a, $b) {
            $nameA = is_string($a) ? $a : ($a->name ?? '');
            $nameB = is_string($b) ? $b : ($b->name ?? '');

            $cmp = strnatcasecmp($nameA, $nameB);
            if ($cmp !== 0) {
                return $cmp;
            }

            $idA = is_object($a) ? ($a->id ?? 0) : 0;
            $idB = is_object($b) ? ($b->id ?? 0) : 0;

            return $idA <=> $idB;
        })->values();
    }

    /**
     * Hitung ulang nomor urut sort_order pada database mengikuti urutan natural nama Well Pad.
     */
    public static function resequenceSortOrders(): void
    {
        if (static::$isResequencing) {
            return;
        }

        static::$isResequencing = true;
        try {
            $all = static::all();
            $sorted = static::sortNaturally($all);

            foreach ($sorted as $index => $pad) {
                $expectedSort = $index + 1;
                if ($pad->sort_order !== $expectedSort) {
                    static::where('id', $pad->id)->update(['sort_order' => $expectedSort]);
                    $pad->sort_order = $expectedSort;
                }
            }
        } finally {
            static::$isResequencing = false;
        }
    }

    public static function ensureDefaultsSeeded(): void
    {
        if (static::count() > 0) {
            return;
        }

        foreach (self::DEFAULT_WELL_PADS as $wp) {
            static::firstOrCreate(
                ['name' => $wp['name']],
                [
                    'code' => $wp['code'],
                    'is_active' => $wp['is_active'],
                    'sort_order' => $wp['sort_order'],
                ]
            );
        }

        static::resequenceSortOrders();
    }
}

