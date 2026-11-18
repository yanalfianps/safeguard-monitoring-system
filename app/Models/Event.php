<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'participants' => 'integer',
        ];
    }

    /**
     * Hitung status otomatis berdasarkan tanggal kegiatan (Direncanakan / Berlangsung / Selesai).
     */
    public static function determineAutoStatus($eventDate, ?string $currentStatus = null): string
    {
        if ($currentStatus === self::STATUS_DIBATALKAN) {
            return self::STATUS_DIBATALKAN;
        }

        if (empty($eventDate)) {
            return $currentStatus ?: self::STATUS_DIRENCANAKAN;
        }

        try {
            $dateStr = $eventDate instanceof Carbon
                ? $eventDate->format('Y-m-d')
                : Carbon::parse($eventDate)->format('Y-m-d');
            $today = Carbon::now('Asia/Jakarta')->toDateString();

            if ($dateStr < $today) {
                return self::STATUS_SELESAI;
            }
            if ($dateStr === $today) {
                return self::STATUS_BERLANGSUNG;
            }
            return self::STATUS_DIRENCANAKAN;
        } catch (\Throwable) {
            return $currentStatus ?: self::STATUS_DIRENCANAKAN;
        }
    }

    /**
     * Sinkronisasi otomatis status kegiatan di database berdasarkan tanggal hari ini.
     * - Tanggal > hari ini -> Direncanakan (kecuali Dibatalkan)
     * - Tanggal == hari ini -> Berlangsung (jika masih Direncanakan)
     * - Tanggal < hari ini -> Selesai (jika masih Direncanakan atau Berlangsung)
     */
    public static function syncAutomaticStatuses(): int
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $updated = 0;

        // 1. Kegiatan yang tanggalnya sudah terlewat (< hari ini) dan berstatus Berlangsung / Direncanakan -> Selesai
        $updated += static::query()
            ->whereDate('event_date', '<', $today)
            ->whereIn('status', [self::STATUS_BERLANGSUNG, self::STATUS_DIRENCANAKAN])
            ->update(['status' => self::STATUS_SELESAI]);

        // 2. Kegiatan pada hari ini (== hari ini) yang masih berstatus Direncanakan -> Berlangsung
        $updated += static::query()
            ->whereDate('event_date', '=', $today)
            ->where('status', self::STATUS_DIRENCANAKAN)
            ->update(['status' => self::STATUS_BERLANGSUNG]);

        // 3. Kegiatan yang dijadwalkan di masa mendatang (> hari ini) namun berstatus Berlangsung -> Direncanakan
        $updated += static::query()
            ->whereDate('event_date', '>', $today)
            ->where('status', self::STATUS_BERLANGSUNG)
            ->update(['status' => self::STATUS_DIRENCANAKAN]);

        return $updated;
    }

    protected static function booted(): void
    {
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
                $event->status = static::determineAutoStatus($event->event_date);
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
