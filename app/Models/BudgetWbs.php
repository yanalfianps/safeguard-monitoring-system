<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetWbs extends Model
{
    use HasFactory;

    protected $table = 'budget_wbs';

    protected $fillable = [
        'wbs_number',
        'wbs_name',
        'description',
        'initial_budget',
        'current_budget',
        'fiscal_year',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'initial_budget' => 'float',
        'current_budget' => 'float',
        'fiscal_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function pos(): HasMany
    {
        return $this->hasMany(BudgetPo::class, 'budget_wbs_id');
    }

    public function poPayments(): HasMany
    {
        return $this->hasMany(BudgetPoPayment::class, 'budget_wbs_id');
    }

    public function nonPos(): HasMany
    {
        return $this->hasMany(BudgetNonPo::class, 'budget_wbs_id');
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(BudgetTransfer::class, 'source_wbs_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(BudgetTransfer::class, 'destination_wbs_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(BudgetAdjustment::class, 'budget_wbs_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTransaction::class, 'budget_wbs_id');
    }

    /**
     * Hitung Total Nilai PO
     */
    public function getTotalPoValueAttribute(): float
    {
        if ($this->relationLoaded('pos')) {
            return (float) $this->pos
                ->where('status', '!=', 'Dibatalkan')
                ->sum(function ($po) {
                    return (float) ($po->po_value > 0 ? $po->po_value : $po->commitment_value);
                });
        }
        return (float) $this->pos()
            ->where('status', '!=', 'Dibatalkan')
            ->sum(\Illuminate\Support\Facades\DB::raw('CASE WHEN po_value > 0 THEN po_value ELSE commitment_value END'));
    }

    /**
     * Hitung Total Komitmen PO Aktif (Total PO)
     */
    public function getPoCommitmentAttribute(): float
    {
        return $this->total_po_value;
    }

    /**
     * Hitung Total Realisasi Pembayaran PO (PO Expenses)
     */
    public function getPoRealizationAttribute(): float
    {
        if ($this->relationLoaded('poPayments')) {
            return (float) $this->poPayments
                ->filter(fn ($pay) => ($pay->po?->status ?? 'Aktif') !== 'Dibatalkan')
                ->sum('amount');
        }
        return (float) $this->poPayments()
            ->whereHas('po', fn ($q) => $q->where('status', '!=', 'Dibatalkan'))
            ->sum('amount');
    }

    /**
     * Hitung Total Realisasi Non-PO (Non-PO Expenses)
     */
    public function getNonPoRealizationAttribute(): float
    {
        if ($this->relationLoaded('nonPos')) {
            return (float) $this->nonPos->sum('amount');
        }
        return (float) $this->nonPos()->sum('amount');
    }

    /**
     * Hitung Anggaran Teralokasi (Assigned = Total PO + Realisasi Non-PO)
     * Catatan: Pembayaran PO tidak mengurangi anggaran lagi karena komitmen PO sudah mengalokasikan anggaran.
     */
    public function getAllocatedBudgetAttribute(): float
    {
        return (float) ($this->total_po_value + $this->non_po_realization);
    }

    /**
     * Hitung Sisa Anggaran (Available = Total Anggaran WBS Saat Ini - Anggaran Teralokasi)
     */
    public function getRemainingBudgetAttribute(): float
    {
        return (float) ($this->current_budget - $this->allocated_budget);
    }

    /**
     * Hitung Sisa Komitmen PO (Commitment = Total PO - PO Expenses)
     */
    public function getRemainingPoCommitmentAttribute(): float
    {
        return max(0, (float) ($this->total_po_value - $this->po_realization));
    }

    /**
     * Alias Properti Keuangan Standar Baru:
     */
    public function getCommitmentAttribute(): float
    {
        return $this->remaining_po_commitment;
    }

    public function getPoExpensesAttribute(): float
    {
        return $this->po_realization;
    }

    public function getNonPoExpensesAttribute(): float
    {
        return $this->non_po_realization;
    }

    public function getAssignedAttribute(): float
    {
        return $this->allocated_budget;
    }

    public function getAvailableAttribute(): float
    {
        return $this->remaining_budget;
    }

    /**
     * Helper Format Rupiah Indonesia
     */
    public static function formatRupiah($val): string
    {
        $num = (float) $val;
        return 'Rp' . number_format($num, 0, ',', '.');
    }

    /**
     * Generate Nomor WBS Baru secara otomatis jika tidak diinput
     */
    public static function generateNextWbsNumber(): string
    {
        $year = date('Y');
        $prefix = "WBS-HSE-{$year}-";
        $latest = static::where('wbs_number', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->wbs_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $seq = '001';
        }

        return $prefix . $seq;
    }
}

