<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sikka extends Model
{
    use HasFactory;

    public const DEFAULT_OPTIONS = [
        'Ruang Terbatas',
        'Ketinggian',
        'Penggalian',
        'Listrik',
        'Isolasi Energi',
        'Lifting',
        'Panas',
    ];

    protected $table = 'sikkas';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Pastikan pilihan awal SIKA tersedia hanya jika tabel benar-benar kosong.
     * Tidak otomatis memasukkan kembali data yang sudah dihapus oleh pengguna.
     */
    public static function ensureDefaults(): void
    {
        if (static::count() === 0) {
            foreach (self::DEFAULT_OPTIONS as $option) {
                static::create([
                    'name' => $option,
                    'is_active' => true,
                ]);
            }
        }
    }
}
