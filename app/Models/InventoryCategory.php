<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryCategory extends Model
{
    use HasFactory;

    protected $table = 'inventory_categories';

    public const DEFAULTS = InventoryItem::CATEGORIES;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk kategori yang berstatus aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cari kategori berdasarkan nama (case-insensitive dan trimmed).
     */
    public static function findByName(?string $name): ?self
    {
        $name = preg_replace('/\s+/', ' ', trim((string) $name));
        if ($name === '') {
            return null;
        }

        return static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    /**
     * Hitung berapa kali kategori ini digunakan pada master barang.
     */
    public function getUsageCountAttribute(): int
    {
        return DB::table('inventory_items')
            ->where(function ($q) {
                $lower = mb_strtolower($this->name);
                $q->whereRaw('LOWER(kategori) = ?', [$lower])
                    ->orWhereRaw('LOWER(category) = ?', [$lower]);
            })
            ->count();
    }

    /**
     * Apakah kategori ini aman untuk dihapus (belum pernah digunakan oleh barang).
     */
    public function getCanDeleteAttribute(): bool
    {
        return $this->usage_count === 0;
    }

    /**
     * Pastikan kategori awal tersedia jika tabel masih kosong (termasuk kategori dari barang yang sudah ada).
     */
    public static function ensureDefaults(): void
    {
        if (!Schema::hasTable('inventory_categories')) {
            return;
        }

        if (static::count() > 0) {
            return;
        }

        $existingKategori = DB::table('inventory_items')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori')
            ->all();

        $existingCategory = DB::table('inventory_items')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->all();

        $allCategories = array_unique(array_merge(self::DEFAULTS, $existingKategori, $existingCategory));

        foreach ($allCategories as $name) {
            $trimmed = preg_replace('/\s+/', ' ', trim((string) $name));
            if ($trimmed === '') {
                continue;
            }

            $exists = static::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->exists();
            if (!$exists) {
                static::create([
                    'name' => $trimmed,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Ambil daftar nama kategori aktif untuk dropdown.
     *
     * @return array<int, string>
     */
    public static function getActiveNames(): array
    {
        if (!Schema::hasTable('inventory_categories')) {
            return self::DEFAULTS;
        }

        self::ensureDefaults();

        return static::active()->orderBy('name')->pluck('name')->all();
    }
}
