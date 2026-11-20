<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCompany extends Model
{
    use HasFactory;

    protected $table = 'event_companies';

    protected $fillable = [
        'name',
        'category',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Pastikan master instansi/perusahaan default terisi.
     */
    public static function ensureDefaults(): void
    {
        if (static::count() === 0) {
            $defaults = [
                ['name' => 'EBTKE', 'category' => 'Regulator', 'is_active' => true],
                ['name' => 'PT Geo Dipa Energi (Persero)', 'category' => 'Internal', 'is_active' => true],
                ['name' => 'PLN', 'category' => 'Mitra BUMN', 'is_active' => true],
                ['name' => 'Kementerian ESDM', 'category' => 'Kementerian', 'is_active' => true],
            ];

            foreach ($defaults as $item) {
                static::create($item);
            }
        }
    }
}
