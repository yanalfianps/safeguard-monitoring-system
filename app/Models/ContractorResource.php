<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorResource extends Model
{
    public const TYPE_PERSONNEL = 'personnel';
    public const TYPE_SUPERVISOR = 'supervisor';
    public const TYPE_WORK_AREA = 'work_area';
    public const TYPE_VEHICLE = 'vehicle';
    public const TYPE_EQUIPMENT = 'equipment';
    public const TYPE_SECURITY_POST = 'security_post';

    public const TYPES = [
        self::TYPE_PERSONNEL => 'Personel / Pekerja',
        self::TYPE_SUPERVISOR => 'Supervisor & Level Atas',
        self::TYPE_WORK_AREA => 'Work Area / Lokasi Kerja',
        self::TYPE_VEHICLE => 'Kendaraan (Light / Heavy Vehicle)',
        self::TYPE_EQUIPMENT => 'Peralatan Kerja / Keselamatan',
        self::TYPE_SECURITY_POST => 'Pos Penjagaan Security (3 Shift)',
    ];

    protected $table = 'contractor_resources';

    protected $fillable = [
        'contractor_id',
        'resource_type',
        'code',
        'name',
        'category',
        'quantity',
        'shift_requirements',
        'metadata',
        'effective_from',
        'effective_until',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'shift_requirements' => 'array',
        'metadata' => 'array',
        'effective_from' => 'date:Y-m-d',
        'effective_until' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ContractorResourceHistory::class, 'contractor_resource_id')->orderByDesc('id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->resource_type] ?? $this->resource_type;
    }

    /**
     * Total required shift placements per day if this resource is a security_post.
     */
    public function getDailyShiftPlacementTotalAttribute(): float
    {
        if ($this->resource_type !== self::TYPE_SECURITY_POST || !is_array($this->shift_requirements)) {
            return (float) $this->quantity;
        }

        $sum = 0.0;
        foreach ($this->shift_requirements as $req) {
            $sum += (float) $req;
        }

        return $sum;
    }
}

