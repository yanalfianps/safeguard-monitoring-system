<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FindingSource extends Model
{
    use HasFactory;

    protected $table = 'finding_sources';

    public const DEFAULT_SOURCES = [
        'Audit GIMS',
        'Audit EBTKE',
        'ADB Mission',
        'Audit PMC',
        'GRM',
        'Hasil Observasi',
    ];

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Pastikan opsi default Sumber Temuan tersedia di database jika tabel masih kosong.
     */
    public static function ensureDefaults(): void
    {
        if (static::count() > 0) {
            return;
        }

        foreach (self::DEFAULT_SOURCES as $sourceName) {
            static::firstOrCreate(
                ['name' => $sourceName],
                ['description' => null, 'is_active' => true]
            );
        }
    }
}

