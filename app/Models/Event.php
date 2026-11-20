<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_BERLANGSUNG = 'Berlangsung';
    public const STATUS_DIRENCANAKAN = 'Direncanakan';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    public const STATUSES = [
        'Direncanakan',
        'Berlangsung',
        'Selesai',
        'Dibatalkan',
    ];

    public const SAFEGUARD_FUNCTIONS = [
        'Health & Safety',
        'Security',
        'Environment',
        'Social',
    ];

    public const CATEGORIES = [
        'Pelatihan',
        'Safety Talk',
        'Inspeksi',
        'Kampanye',
        'Simulasi/Drill',
        'Sosialisasi',
        'Lainnya',
    ];

    public const UNITS = [
        'GDE (Internal)',
        'BUJP (Security)',
        'PJSE (Utility)',
        'JOTRE',
        'PMC',
        'SOKKO',
    ];

    protected $fillable = [
        'title',
        'safeguard_function',
        'company',
        'category',
        'unit',
        'event_date',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'participants',
        'pic',
        'status',
        'description',
        'documentation',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'primary_documentation_url',
        'safeguard_badge_bg',
        'safeguard_badge_color',
        'safeguard_badge_border',
        'formatted_date_range',
        'safeguard_functions_list',
        'safeguard_badges_data',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'participants' => 'integer',
        ];
    }

    /**
     * Hitung status otomatis berdasarkan rentang tanggal kegiatan (Direncanakan / Berlangsung / Selesai).
     */
    public static function determineAutoStatus($startDate, $endDate = null, ?string $currentStatus = null): string
    {
        // Kompatibilitas jika dipanggil dengan format lama: determineAutoStatus($date, $currentStatus)
        if (is_string($endDate) && in_array($endDate, self::STATUSES, true)) {
            $currentStatus = $endDate;
            $endDate = null;
        }

        if ($currentStatus === self::STATUS_DIBATALKAN) {
            return self::STATUS_DIBATALKAN;
        }

        if (empty($startDate)) {
            return $currentStatus ?: self::STATUS_DIRENCANAKAN;
        }

        $end = $endDate ?: $startDate;

        try {
            $startStr = $startDate instanceof Carbon
                ? $startDate->format('Y-m-d')
                : Carbon::parse($startDate)->format('Y-m-d');
            $endStr = $end instanceof Carbon
                ? $end->format('Y-m-d')
                : Carbon::parse($end)->format('Y-m-d');
            $today = Carbon::now('Asia/Jakarta')->toDateString();

            if ($endStr < $today) {
                return self::STATUS_SELESAI;
            }
            if ($startStr <= $today && $endStr >= $today) {
                return self::STATUS_BERLANGSUNG;
            }
            return self::STATUS_DIRENCANAKAN;
        } catch (\Throwable) {
            return $currentStatus ?: self::STATUS_DIRENCANAKAN;
        }
    }

    /**
     * Sinkronisasi otomatis status kegiatan di database berdasarkan rentang tanggal hari ini.
     * - Tanggal selesai < hari ini -> Selesai
     * - Sedang berlangsung (mulai <= hari ini && selesai >= hari ini) -> Berlangsung
     * - Belum mulai (mulai > hari ini) -> Direncanakan
     */
    public static function syncAutomaticStatuses(): int
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $updated = 0;

        $hasEndDate = static::hasDateRangeColumns();

        if ($hasEndDate) {
            // 1. Kegiatan yang tanggal akhirnya sudah terlewat (< hari ini) -> Selesai
            $updated += static::query()
                ->where(function ($q) use ($today) {
                    $q->where(function ($sq) use ($today) {
                        $sq->whereNotNull('end_date')->whereDate('end_date', '<', $today);
                    })->orWhere(function ($sq) use ($today) {
                        $sq->whereNull('end_date')->whereDate('event_date', '<', $today);
                    });
                })
                ->whereIn('status', [self::STATUS_BERLANGSUNG, self::STATUS_DIRENCANAKAN])
                ->update(['status' => self::STATUS_SELESAI]);

            // 2. Kegiatan yang berlangsung hari ini (start_date <= today && end_date >= today) -> Berlangsung
            $updated += static::query()
                ->where(function ($q) use ($today) {
                    $q->where(function ($sq) use ($today) {
                        $sq->whereNotNull('start_date')
                           ->whereDate('start_date', '<=', $today)
                           ->whereDate('end_date', '>=', $today);
                    })->orWhere(function ($sq) use ($today) {
                        $sq->whereNull('start_date')
                           ->whereDate('event_date', '=', $today);
                    });
                })
                ->where('status', self::STATUS_DIRENCANAKAN)
                ->update(['status' => self::STATUS_BERLANGSUNG]);

            // 3. Kegiatan yang baru akan dimulai di masa mendatang (start_date > today) -> Direncanakan
            $updated += static::query()
                ->where(function ($q) use ($today) {
                    $q->where(function ($sq) use ($today) {
                        $sq->whereNotNull('start_date')->whereDate('start_date', '>', $today);
                    })->orWhere(function ($sq) use ($today) {
                        $sq->whereNull('start_date')->whereDate('event_date', '>', $today);
                    });
                })
                ->where('status', self::STATUS_BERLANGSUNG)
                ->update(['status' => self::STATUS_DIRENCANAKAN]);
        } else {
            // Fallback skema sebelum kolom start_date/end_date dimigrasikan
            $updated += static::query()
                ->whereDate('event_date', '<', $today)
                ->whereIn('status', [self::STATUS_BERLANGSUNG, self::STATUS_DIRENCANAKAN])
                ->update(['status' => self::STATUS_SELESAI]);

            $updated += static::query()
                ->whereDate('event_date', '=', $today)
                ->where('status', self::STATUS_DIRENCANAKAN)
                ->update(['status' => self::STATUS_BERLANGSUNG]);

            $updated += static::query()
                ->whereDate('event_date', '>', $today)
                ->where('status', self::STATUS_BERLANGSUNG)
                ->update(['status' => self::STATUS_DIRENCANAKAN]);
        }

        return $updated;
    }

    protected static ?bool $hasDateRangeColumns = null;

    /**
     * Memeriksa apakah kolom start_date dan end_date sudah tersedia di database.
     */
    public static function hasDateRangeColumns(): bool
    {
        if (static::$hasDateRangeColumns === null) {
            try {
                static::$hasDateRangeColumns = \Illuminate\Support\Facades\Schema::hasColumn('events', 'start_date')
                    && \Illuminate\Support\Facades\Schema::hasColumn('events', 'end_date');
            } catch (\Throwable) {
                static::$hasDateRangeColumns = false;
            }
        }
        return static::$hasDateRangeColumns;
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            $hasRange = static::hasDateRangeColumns();
            if ($hasRange) {
                // Sinkronisasi dua arah antara event_date, start_date, dan end_date
                if (!empty($event->start_date) && empty($event->event_date)) {
                    $event->event_date = $event->start_date;
                } elseif (!empty($event->event_date) && empty($event->start_date)) {
                    $event->start_date = $event->event_date;
                }
                if (!empty($event->start_date) && empty($event->end_date)) {
                    $event->end_date = $event->start_date;
                }
            } else {
                if (!empty($event->start_date) && empty($event->event_date)) {
                    $event->event_date = $event->start_date;
                }
                // Hapus atribut start_date dan end_date jika kolom belum dimigrasikan ke database
                unset($event->attributes['start_date'], $event->attributes['end_date']);
            }
        });

        static::creating(function (Event $event) {
            if (empty($event->safeguard_function)) {
                $event->safeguard_function = 'Health & Safety';
            }
            if (empty($event->unit)) {
                $event->unit = $event->company ?: 'PMU';
            }
            if (empty($event->company)) {
                $event->company = $event->unit ?: 'PT Geo Dipa Energi (Persero)';
            }
            if (empty($event->category)) {
                $event->category = $event->safeguard_function ?: 'Kegiatan';
            }
            if (empty($event->location)) {
                $event->location = 'Area Operasi Dieng';
            }
            if (empty($event->status)) {
                $event->status = static::determineAutoStatus($event->start_date ?: $event->event_date, $event->end_date);
            }
        });
    }

    /**
     * Scope khusus kegiatan yang telah terlaksana / selesai.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SELESAI);
    }

    /**
     * Scope pengurutan kegiatan dari tanggal terbaru ke terlama.
     */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderBy('event_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'record_id')
            ->whereIn('module', ['events', 'Events']);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'record_id')
            ->whereIn('module', ['events', 'Events'])
            ->latest('id');
    }

    /**
     * Accessor tampilan rentang tanggal (Contoh: "18/11/2026 - 20/11/2026" atau "18/11/2026").
     */
    public function getFormattedDateRangeAttribute(): string
    {
        $hasRange = static::hasDateRangeColumns();
        $start = ($hasRange && !empty($this->attributes['start_date'])) ? $this->attributes['start_date'] : $this->event_date;
        $end = ($hasRange && !empty($this->attributes['end_date'])) ? $this->attributes['end_date'] : $start;

        if (!$start) {
            return '-';
        }

        try {
            $startDate = $start instanceof Carbon ? $start : Carbon::parse($start);
            $endDate = $end instanceof Carbon ? $end : Carbon::parse($end);

            return $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        } catch (\Throwable) {
            return (string) ($this->event_date ? $this->event_date->format('d/m/Y') . ' - ' . $this->event_date->format('d/m/Y') : '-');
        }
    }

    /**
     * Accessor daftar fungsi safeguard (array).
     */
    public function getSafeguardFunctionsListAttribute(): array
    {
        if (empty($this->safeguard_function)) {
            return ['Health & Safety'];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->safeguard_function))));
    }

    /**
     * Style badge untuk masing-masing fungsi safeguard.
     */
    public static function getSafeguardStyle(string $func): array
    {
        return match (trim($func)) {
            'Health & Safety' => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'border' => '#bfdbfe'],
            'Security' => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa'],
            'Environment' => ['bg' => '#ecfdf5', 'color' => '#047857', 'border' => '#a7f3d0'],
            'Social' => ['bg' => '#f5f3ff', 'color' => '#6d28d9', 'border' => '#ddd6fe'],
            default => ['bg' => '#f1f5f9', 'color' => '#334155', 'border' => '#e2e8f0'],
        };
    }

    /**
     * Data badge lengkap untuk fungsi safeguard terpilih (nama, bg, color, border).
     */
    public function getSafeguardBadgesDataAttribute(): array
    {
        $list = $this->safeguard_functions_list;
        $badges = [];
        foreach ($list as $f) {
            $style = static::getSafeguardStyle($f);
            $badges[] = [
                'name' => $f,
                'bg' => $style['bg'],
                'color' => $style['color'],
                'border' => $style['border'],
            ];
        }
        return $badges;
    }

    public function getPrimaryDocumentationUrlAttribute(): ?string
    {
        if (!empty($this->documentation)) {
            if (str_starts_with($this->documentation, 'http://') || str_starts_with($this->documentation, 'https://')) {
                return $this->documentation;
            }
            return Storage::disk('public')->url($this->documentation);
        }

        $firstDoc = $this->relationLoaded('documents') ? $this->documents->first() : $this->documents()->first();
        return $firstDoc?->file_url;
    }

    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'Pelatihan' => '#fbe8ea',
            'Safety Talk' => '#fef0e3',
            'Inspeksi' => '#e8f4fd',
            'Simulasi/Drill' => '#fde8eb',
            'Kampanye' => '#eef5ec',
            'Sosialisasi' => '#e7f7f0',
            default => '#ede9fe',
        };
    }

    public function getCategoryTextColorAttribute(): string
    {
        return match ($this->category) {
            'Pelatihan' => '#b83250',
            'Safety Talk' => '#b45309',
            'Inspeksi' => '#1d4ed8',
            'Simulasi/Drill' => '#dc2626',
            'Kampanye' => '#15803d',
            'Sosialisasi' => '#047857',
            default => '#6b21a8',
        };
    }

    public function getSafeguardBadgeBgAttribute(): string
    {
        return match ($this->safeguard_function) {
            'Health & Safety' => '#eff6ff',
            'Security' => '#fff7ed',
            'Environment' => '#ecfdf5',
            'Social' => '#f5f3ff',
            default => '#f1f5f9',
        };
    }

    public function getSafeguardBadgeColorAttribute(): string
    {
        return match ($this->safeguard_function) {
            'Health & Safety' => '#1d4ed8',
            'Security' => '#c2410c',
            'Environment' => '#047857',
            'Social' => '#6d28d9',
            default => '#334155',
        };
    }

    public function getSafeguardBadgeBorderAttribute(): string
    {
        return match ($this->safeguard_function) {
            'Health & Safety' => '#bfdbfe',
            'Security' => '#fed7aa',
            'Environment' => '#a7f3d0',
            'Social' => '#ddd6fe',
            default => '#e2e8f0',
        };
    }
}
