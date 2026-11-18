<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentMaster extends Model
{
    use HasFactory;

    protected $table = 'environment_masters';

    protected $fillable = [
        'category',
        'name',
        'code',
        'description',
        'submodules',
        'order_index',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
        'submodules' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category)->orderBy('order_index')->orderBy('name');
    }

    public function scopeForSubmodule($query, string $submodule)
    {
        return $query->whereJsonContains('submodules', $submodule);
    }

    /**
     * Submodule options for unit mapping.
     */
    public static function unitSubmoduleOptions(): array
    {
        return [
            'lb3' => 'LB3',
            'non_lb3' => 'Non-LB3',
            'rkl_rpl' => 'RKL–RPL',
            'energi_bbm' => 'Energi BBM',
            'energi_listrik' => 'Energi Listrik',
            'cuaca' => 'Cuaca',
        ];
    }

    public function getSubmoduleLabelsAttribute(): array
    {
        $opts = self::unitSubmoduleOptions();
        if (!is_array($this->submodules)) return [];
        return array_map(fn($key) => $opts[$key] ?? $key, $this->submodules);
    }

    /**
     * Active master categories used in current Environment submenus.
     */
    public static function activeCategoryLabels(): array
    {
        return [
            'unit' => 'Satuan',
            'lb3_characteristic' => 'Karakteristik LB3',
            'rkl_rpl_parameter' => 'Parameter RKL–RPL',
            'rkl_rpl_location' => 'Lokasi Pemantauan RKL–RPL',
            'inspection_type' => 'Jenis Inspeksi',
            'inspection_location' => 'Lokasi Inspeksi',
            'ews_location' => 'Lokasi EWS',
            'weather_parameter' => 'Parameter Cuaca',
        ];
    }

    /**
     * All category labels including legacy/archived categories for historical preservation.
     */
    public static function allCategoryLabels(): array
    {
        return [
            'unit' => 'Satuan',
            'lb3_characteristic' => 'Karakteristik LB3',
            'rkl_rpl_parameter' => 'Parameter RKL–RPL',
            'rkl_rpl_location' => 'Lokasi Pemantauan RKL–RPL',
            'inspection_type' => 'Jenis Inspeksi',
            'inspection_location' => 'Lokasi Inspeksi',
            'ews_location' => 'Lokasi EWS',
            'weather_parameter' => 'Parameter Cuaca',
            'non_lb3_source' => 'Sumber Limbah Non-LB3 (Arsip)',
            'ppich_location' => 'Lokasi PPICH (Arsip)',
            'ppich_activity' => 'Aktivitas PPICH (Arsip)',
            'ppich_reference' => 'Acuan SK PPICH (Arsip)',
        ];
    }

    /**
     * Default options fallback for Inspeksi
     */
    public static function defaultInspectionTypes(): array
    {
        return [
            'Spill Kit',
            'Tempat Sampah',
            'TPS LB3',
            'PPKH/Lahan',
        ];
    }

    public static function defaultInspectionLocations(): array
    {
        return [
            'Area Well Pad',
            'Area PP',
            'Temporary Office',
            'SK 2013',
            'SK 2024',
        ];
    }

    public static function defaultWeatherParameters(): array
    {
        return [
            ['name' => 'Kecepatan Angin', 'unit' => 'm/s'],
            ['name' => 'Curah Hujan', 'unit' => 'mm/hr'],
            ['name' => 'Temperatur', 'unit' => '°C'],
            ['name' => 'Humidity', 'unit' => '%'],
        ];
    }

    /**
     * Category labels for UI management. Default: active categories only.
     */
    public static function categoryLabels(bool $activeOnly = true): array
    {
        return $activeOnly ? self::activeCategoryLabels() : self::allCategoryLabels();
    }
}
