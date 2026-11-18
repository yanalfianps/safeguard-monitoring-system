<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryUnit extends Model
{
    use HasFactory;

    protected $table = 'inventory_units';

    public const DEFAULTS = [
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

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk satuan yang berstatus aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cari satuan berdasarkan nama (case-insensitive dan trimmed).
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
     * Hitung berapa kali satuan ini digunakan pada barang atau transaksi.
     */
    public function getUsageCountAttribute(): int
    {
        $itemCount = DB::table('inventory_items')
            ->whereRaw('LOWER(satuan) = ?', [mb_strtolower($this->name)])
            ->orWhereRaw('LOWER(unit_measure) = ?', [mb_strtolower($this->name)])
            ->count();

        $trxCount = DB::table('inventory_transactions')
            ->whereRaw('LOWER(satuan) = ?', [mb_strtolower($this->name)])
            ->count();

        return $itemCount + $trxCount;
    }

    /**
     * Apakah satuan ini aman untuk dihapus (belum pernah digunakan).
     */
    public function getCanDeleteAttribute(): bool
    {
        return $this->usage_count === 0;
    }

    /**
     * Pastikan satuan default selalu tersedia.
     */
    public static function ensureDefaults(): void
    {
        foreach (self::DEFAULTS as $name) {
            $trimmed = trim($name);
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
     * Ambil daftar nama satuan aktif untuk dropdown.
     *
     * @return array<int, string>
     */
    public static function getActiveNames(): array
    {
        self::ensureDefaults();

        return static::active()->orderBy('name')->pluck('name')->all();
    }
}

