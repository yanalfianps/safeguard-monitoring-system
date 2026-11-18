<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyWork extends Model
{
    public const STATUS_NOT_STARTED = 'Belum Mulai';
    public const STATUS_ONGOING = 'Berlangsung';
    public const STATUS_COMPLETED = 'Selesai';
    public const STATUS_PAUSED = 'Ditunda';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_ONGOING,
        self::STATUS_COMPLETED,
        self::STATUS_PAUSED,
    ];

    public const UNIT_GDE = 'GDE (Internal)';
    public const UNIT_BUJP = 'BUJP (Security)';
    public const UNIT_PJSE = 'PJSE (Utility)';
    public const UNIT_JOTRE = 'JOTRE';
    public const UNIT_PMC = 'PMC';

    public const UNITS = [
        self::UNIT_GDE,
        self::UNIT_BUJP,
        self::UNIT_PJSE,
        self::UNIT_JOTRE,
        self::UNIT_PMC,
    ];

    public const UNITS_EXTERNAL = [
        self::UNIT_BUJP,
        self::UNIT_PJSE,
        self::UNIT_JOTRE,
        self::UNIT_PMC,
    ];

    protected $fillable = [
        'name',
        'unit',
        'company_id',
        'category',
        'ptw_classification',
        'sikka',
        'sika',
        'start_date',
        'end_date',
        'location',
        'executor',
        'status',
        'description',
    ];

    protected $appends = [
        'sika',
        'sika_array',
        'parent_company_name',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function getCompanyAttribute(): ?string
    {
        return $this->unit;
    }

    public function setCompanyAttribute(?string $value): void
    {
        $this->unit = $value;
    }

    public function getSikkaAttribute(?string $value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->attributes['ptw_classification'] ?? null;
    }

    public function setSikkaAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $cleaned = array_values(array_unique(array_filter(array_map('trim', $value))));
            $this->attributes['sikka'] = !empty($cleaned) ? implode(', ', $cleaned) : null;
        } elseif (is_string($value)) {
            $trimmed = trim($value);
            $this->attributes['sikka'] = ($trimmed !== '' && $trimmed !== '-') ? $trimmed : null;
        } else {
            $this->attributes['sikka'] = null;
        }
    }

    public function getSikaAttribute(): ?string
    {
        return $this->sikka;
    }

    public function setSikaAttribute(mixed $value): void
    {
        $this->setSikkaAttribute($value);
    }

    /**
     * Array representasi pilihan SIKA untuk memudahkan binding pada checkbox/detail.
     */
    public function getSikaArrayAttribute(): array
    {
        $raw = $this->sikka;
        if (empty($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    protected static function booted(): void
    {
        static::saving(function (DailyWork $work) {
            // Default nilai category jika tidak dikirim dari form baru
            if (empty($work->category)) {
                $work->category = 'Umum';
            }

            // Jika sikka kosong namun ptw_classification diisi (kompatibilitas data lama), salin ke sikka
            if (empty($work->attributes['sikka'] ?? null) && !empty($work->attributes['ptw_classification'] ?? null)) {
                $work->attributes['sikka'] = $work->attributes['ptw_classification'];
            }

            // Sinkronkan ptw_classification dan executor dengan sikka bila tidak diisi
            if (!isset($work->attributes['ptw_classification'])) {
                $work->attributes['ptw_classification'] = $work->sikka ?: null;
            }

            if (empty($work->executor)) {
                $work->executor = $work->sikka ?: '-';
            }

            // Otomatis tentukan status secara aman tanpa input manual dari form
            if (empty($work->status)) {
                $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
                $end = $work->end_date ? $work->end_date->format('Y-m-d') : $today;
                $work->status = ($end < $today) ? self::STATUS_COMPLETED : self::STATUS_ONGOING;
            } else {
                $work->status = self::normalizeStatus($work->status);
            }

            $work->unit = self::normalizeUnit($work->unit);

            // Hubungkan ke master perusahaan (ID) bila belum terhubung atau nama perusahaan berubah.
            // Pencocokan memakai nama persis (tanpa penggabungan berdasarkan kemiripan nama).
            if (($work->isDirty('unit') || empty($work->company_id)) && !$work->isDirty('company_id')) {
                $work->company_id = DailyWorkCompany::findByName($work->unit)?->id;
            }
        });
    }

    public function masterCompany(): BelongsTo
    {
        return $this->belongsTo(DailyWorkCompany::class, 'company_id');
    }

    /**
     * Nama perusahaan induk (khusus untuk subcon; null untuk perusahaan utama / independen).
     * Contoh: 'KODIPA' -> 'GDE', 'PJSE' -> 'JOTRE'.
     */
    public function getParentCompanyNameAttribute(): ?string
    {
        $company = $this->masterCompany ?: DailyWorkCompany::findByName($this->unit);
        if (!$company || !$company->parent_company_id) {
            return null;
        }

        return $company->parent_short_name ?? ($company->parent ? preg_replace('/\s*\(All\)$/i', '', trim($company->parent->name)) : null);
    }

    /**
     * Normalisasi nilai unit agar pilihan unit tersimpan dengan konsisten.
     *
     * Nama yang sudah ada di master perusahaan dipakai apa adanya (ejaan baku master), sehingga
     * PJSE, BISMO, SOKKO, dsb. tidak lagi dipetakan ke perusahaan lain. Alias di bawah hanya
     * untuk istilah lama yang bukan nama master.
     */
    public static function normalizeUnit(?string $unit): string
    {
        $raw = trim((string) $unit);
        if ($raw === '') {
            return self::UNIT_GDE;
        }

        $master = DailyWorkCompany::findByName($raw);
        if ($master) {
            return $master->name;
        }

        return match (strtoupper($raw)) {
            'PMU', 'INTERNAL', 'GDE (INTERNAL)' => self::UNIT_GDE,
            'UTILITY' => self::UNIT_PJSE,
            'SECURITY', 'BUJP (SECURITY)' => self::UNIT_BUJP,
            default => $raw,
        };
    }

    /**
     * Normalisasi nilai status agar kompatibel dengan enum database ('Belum Mulai', 'Berlangsung', 'Selesai', 'Ditunda')
     * maupun istilah umum ('In Progress', 'Completed', 'Cancelled', 'Dibatalkan').
     */
    public static function normalizeStatus(?string $status): string
    {
        $raw = trim((string) $status);
        if ($raw === '') {
            return self::STATUS_NOT_STARTED;
        }

        return match (strtolower($raw)) {
            'berlangsung', 'in progress', 'in_progress', 'ongoing', 'on going', 'aktif', 'active' => self::STATUS_ONGOING,
            'selesai', 'completed', 'complete', 'done', 'closed', 'close' => self::STATUS_COMPLETED,
            'ditunda', 'dibatalkan', 'batal', 'cancelled', 'canceled', 'paused', 'on hold' => self::STATUS_PAUSED,
            'belum mulai', 'not started', 'not_started', 'pending', 'planned' => self::STATUS_NOT_STARTED,
            default => in_array($raw, self::STATUSES, true) ? $raw : self::STATUS_NOT_STARTED,
        };
    }

    /**
     * Scope query untuk pekerjaan yang dimulai pada hari ini (berdasarkan zona waktu Asia/Jakarta).
     */
    public function scopeStartingToday(Builder $query): Builder
    {
        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
        return $query->whereDate('start_date', $today);
    }

    /**
     * Scope query untuk pekerjaan yang dimulai pada tanggal tertentu.
     */
    public function scopeStartingOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_date', $date);
    }

    /**
     * Single source of truth untuk daftar "Pekerjaan Hari Ini" pada Dashboard dan modul Daily Work.
     * Menggunakan tanggal mulai (start_date) strictly pada zona waktu Asia/Jakarta serta mengecualikan status Selesai.
     */
    public function scopeDailyWorkToday(Builder $query, ?string $date = null): Builder
    {
        $targetDate = $date ?: \Carbon\Carbon::now('Asia/Jakarta')->toDateString();

        return $query
            ->whereDate('start_date', $targetDate)
            ->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * Urutan default terpadu untuk daftar pekerjaan pada Dashboard dan modul Daily Work.
     */
    public function scopeOrderedForDailyWork(Builder $query): Builder
    {
        return $query
            ->orderBy('unit', 'asc')
            ->orderBy('name', 'asc')
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Scope query untuk pekerjaan aktif pada hari ini (berdasarkan zona waktu Asia/Jakarta).
     */
    public function scopeActiveToday(Builder $query): Builder
    {
        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
        return $this->scopeActiveOnDate($query, $today);
    }

    /**
     * Scope query untuk pekerjaan aktif pada tanggal tertentu (start_date <= date <= end_date).
     */
    public function scopeActiveOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_date', '<=', $date)
                     ->whereDate('end_date', '>=', $date)
                     ->orderBy('start_date', 'desc')
                     ->orderBy('id', 'desc');
    }

    /**
     * Scope query backend untuk highlight "Pekerjaan Sedang Berlangsung".
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ONGOING)
            ->whereNotIn('status', [
                self::STATUS_COMPLETED,
                self::STATUS_PAUSED,
                self::STATUS_NOT_STARTED,
            ])
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc');
    }
}
