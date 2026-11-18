<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            ->orderBy('id', 'asc');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
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
    }
}

