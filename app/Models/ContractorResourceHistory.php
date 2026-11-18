<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorResourceHistory extends Model
{
    protected $table = 'contractor_resource_histories';

    protected $fillable = [
        'contractor_resource_id',
        'contractor_id',
        'action',
        'old_values',
        'new_values',
        'effective_date',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'effective_date' => 'date:Y-m-d',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ContractorResource::class, 'contractor_resource_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'contractor_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

