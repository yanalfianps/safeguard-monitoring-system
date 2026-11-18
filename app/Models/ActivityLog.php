<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'module',
        'action',
        'record_type',
        'record_id',
        'description',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(array $attributes): self
    {
        return static::create(array_merge([
            'user_id' => auth()->id(),
            'user_name' => auth()->check() ? auth()->user()->name : 'System',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ], $attributes));
    }
}

