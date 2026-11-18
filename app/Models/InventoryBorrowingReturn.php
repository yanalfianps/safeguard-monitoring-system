<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBorrowingReturn extends Model
{
    use HasFactory;

    protected $table = 'inventory_borrowing_returns';

    protected $fillable = [
        'inventory_borrowing_id',
        'return_number',
        'quantity_returned',
        'return_date',
        'return_location',
        'condition',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'return_number' => 'integer',
        'quantity_returned' => 'integer',
        'return_date' => 'datetime',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(InventoryBorrowing::class, 'inventory_borrowing_id');
    }
}

