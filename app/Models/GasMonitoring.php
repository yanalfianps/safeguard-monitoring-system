<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class GasMonitoring extends Model
{
    use HasFactory;

    protected $table = 'gas_monitorings';

    // NAB / Threshold Config
    public const NAB_CO = 25.0;            // ppm (max)
    public const NAB_O2_MIN = 19.5;        // %
    public const NAB_O2_MAX = 23.5;        // %
    public const NAB_H2S = 10.0;           // ppm (max)
    public const NAB_LEL = 5.0;            // % (max)
    public const NAB_PRESSURE = null;      // Psi (tidak ada threshold)

    protected $fillable = [
        'measurement_date',
        'well_pad_id',
        'well_pad',
        'co_level',
        'o2_level',
        'h2s_level',
        'lel_level',
        'pressure',
        'co_status',
        'o2_status',
        'h2s_status',
        'lel_status',
        'pressure_status',
        'overall_status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'measurement_date' => 'date:Y-m-d',
            'co_level' => 'float',
            'o2_level' => 'float',
            'h2s_level' => 'float',
            'lel_level' => 'float',
            'pressure' => 'float',
        ];
    }

    public function wellPad(): BelongsTo
    {
        return $this->belongsTo(WellPad::class, 'well_pad_id');
    }

    protected static function booted(): void
    {
        static::saving(function (GasMonitoring $item) {
            $item->co_level = ($item->co_level !== null && $item->co_level !== '') ? (float) $item->co_level : null;
            $item->o2_level = ($item->o2_level !== null && $item->o2_level !== '') ? (float) $item->o2_level : null;
            $item->h2s_level = ($item->h2s_level !== null && $item->h2s_level !== '') ? (float) $item->h2s_level : null;
            $item->lel_level = ($item->lel_level !== null && $item->lel_level !== '') ? (float) $item->lel_level : null;
            $item->pressure = ($item->pressure !== null && $item->pressure !== '') ? (float) $item->pressure : null;

            // Status per parameter berdasarkan NAB.
            // Parameter kosong (null) tidak dianggap 0 dan tidak otomatis aman ('Sesuai').
            $item->co_status = ($item->co_level !== null)
                ? (($item->co_level > self::NAB_CO) ? 'Tidak Sesuai' : 'Sesuai')
                : '-';

            $item->o2_status = ($item->o2_level !== null)
                ? ((($item->o2_level < self::NAB_O2_MIN) || ($item->o2_level > self::NAB_O2_MAX)) ? 'Tidak Sesuai' : 'Sesuai')
                : '-';

            $item->h2s_status = ($item->h2s_level !== null)
                ? (($item->h2s_level > self::NAB_H2S) ? 'Tidak Sesuai' : 'Sesuai')
                : '-';

            $item->lel_status = ($item->lel_level !== null)
                ? (($item->lel_level > self::NAB_LEL) ? 'Tidak Sesuai' : 'Sesuai')
                : '-';

            $item->pressure_status = ($item->pressure !== null)
                ? 'Sesuai'
                : '-';

            // Overall Status:
            // - Jika ada parameter gas yang melebihi NAB -> 'Tidak Sesuai'
            // - Jika ada parameter gas yang diukur dan seluruhnya aman -> 'Sesuai'
            // - Jika tidak ada parameter gas yang diukur sama sekali -> '-'
            $hasAnyGasMeasured = ($item->co_level !== null || $item->o2_level !== null || $item->h2s_level !== null || $item->lel_level !== null);
            $hasAnyNonConforming = ($item->co_status === 'Tidak Sesuai')
                || ($item->o2_status === 'Tidak Sesuai')
                || ($item->h2s_status === 'Tidak Sesuai')
                || ($item->lel_status === 'Tidak Sesuai');

            if ($hasAnyNonConforming) {
                $item->overall_status = 'Tidak Sesuai';
            } elseif ($hasAnyGasMeasured) {
                $item->overall_status = 'Sesuai';
            } else {
                $item->overall_status = '-';
            }
        });
    }

    /**
     * Ambil catatan per parameter (JSON di dalam kolom notes atau string biasa).
     */
    public function getParameterNotesAttribute(): array
    {
        $default = [
            'co' => null,
            'o2' => null,
            'h2s' => null,
            'lel' => null,
            'pressure' => null,
            'general' => null,
        ];

        $raw = trim((string) $this->notes);
        if ($raw === '') {
            return $default;
        }

        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_merge($default, $decoded);
            }
        }

        $default['general'] = $raw;
        return $default;
    }

    /**
     * Ringkasan keterangan gabungan untuk ditampilkan pada tabel / laporan.
     */
    public function getFormattedNotesAttribute(): string
    {
        $pNotes = $this->parameter_notes;
        $labels = [
            'co' => 'CO',
            'o2' => 'O₂',
            'h2s' => 'H₂S',
            'lel' => 'LEL',
            'pressure' => 'Pressure',
        ];

        $parts = [];
        foreach ($labels as $k => $lbl) {
            $val = trim((string) ($pNotes[$k] ?? ''));
            if ($val !== '') {
                $parts[] = "{$lbl}: {$val}";
            }
        }

        $gen = trim((string) ($pNotes['general'] ?? ''));
        if ($gen !== '') {
            $parts[] = $gen;
        }

        return !empty($parts) ? implode(' | ', $parts) : '-';
    }

    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('measurement_date', $date);
    }

    public function scopeByMonthYear(Builder $query, int $month, int $year): Builder
    {
        return $query->whereMonth('measurement_date', $month)
            ->whereYear('measurement_date', $year);
    }

    public function scopeByWellPad(Builder $query, string|array|null $wellPad): Builder
    {
        if (is_array($wellPad)) {
            $filtered = array_values(array_filter($wellPad, fn ($v) => !empty($v) && $v !== 'all'));
            if (!empty($filtered)) {
                return $query->whereIn('well_pad', $filtered);
            }
            return $query;
        }

        if (!empty($wellPad) && $wellPad !== 'all') {
            return $query->where('well_pad', $wellPad);
        }
        return $query;
    }

    /**
     * Tentukan tanggal pengukuran terbaru secara konsisten antara Dashboard dan modul Gas Monitoring:
     * hari ini (Asia/Jakarta) jika sudah ada data, selain itu tanggal pengukuran terbaru sebelumnya.
     */
    public static function resolveLatestMeasurementDate(?string $todayStr = null): ?string
    {
        $todayStr = $todayStr ?: Carbon::now('Asia/Jakarta')->format('Y-m-d');

        if (static::whereDate('measurement_date', $todayStr)->exists()) {
            return $todayStr;
        }

        $prevMaxRaw = static::whereDate('measurement_date', '<', $todayStr)->max('measurement_date')
            ?? static::max('measurement_date');

        return $prevMaxRaw ? Carbon::parse($prevMaxRaw)->format('Y-m-d') : null;
    }

    /**
     * Hitung ringkasan 5 parameter (CO, O2, H2S, LEL, Pressure) dan status NAB dari kumpulan pengukuran.
     */
    public static function buildSummaryCardsFromCollection(Collection $records): array
    {
        $params = [
            'co' => [
                'col' => 'co_level',
                'status_col' => 'co_status',
                'unit' => 'ppm',
                'threshold' => 'NAB: ≤ 25 ppm',
            ],
            'o2' => [
                'col' => 'o2_level',
                'status_col' => 'o2_status',
                'unit' => '%',
                'threshold' => 'NAB: 19.5 – 23.5 %',
            ],
            'h2s' => [
                'col' => 'h2s_level',
                'status_col' => 'h2s_status',
                'unit' => 'ppm',
                'threshold' => 'NAB: ≤ 10 ppm',
            ],
            'lel' => [
                'col' => 'lel_level',
                'status_col' => 'lel_status',
                'unit' => '%',
                'threshold' => 'NAB: ≤ 5 %',
            ],
            'pressure' => [
                'col' => 'pressure',
                'status_col' => 'pressure_status',
                'unit' => 'Psi',
                'threshold' => '-',
            ],
        ];

        $cards = [];
        foreach ($params as $key => $cfg) {
            $valid = $records->filter(fn ($r) => $r->{$cfg['col']} !== null);
            if ($valid->isEmpty()) {
                $cards[$key] = [
                    'numeric' => null,
                    'formatted' => '-',
                    'val' => '-',
                    'status' => 'Belum Ada Data',
                    'threshold' => $cfg['threshold'],
                ];
                continue;
            }

            $avgVal = round((float) $valid->avg($cfg['col']), 1);

            if ($key === 'co') {
                $hasNonConforming = $valid->contains(fn ($r) => (float) $r->co_level > self::NAB_CO);
            } elseif ($key === 'o2') {
                $hasNonConforming = $valid->contains(fn ($r) => (float) $r->o2_level < self::NAB_O2_MIN || (float) $r->o2_level > self::NAB_O2_MAX);
            } elseif ($key === 'h2s') {
                $hasNonConforming = $valid->contains(fn ($r) => (float) $r->h2s_level > self::NAB_H2S);
            } elseif ($key === 'lel') {
                $hasNonConforming = $valid->contains(fn ($r) => (float) $r->lel_level > self::NAB_LEL);
            } else {
                $hasNonConforming = false;
            }

            $formattedNum = rtrim(rtrim(number_format($avgVal, 1, '.', ''), '0'), '.');

            $cards[$key] = [
                'numeric' => $avgVal,
                'formatted' => $formattedNum,
                'val' => $formattedNum . ' ' . $cfg['unit'],
                'status' => $key === 'pressure' ? null : ($hasNonConforming ? 'Tidak Sesuai' : 'Sesuai'),
                'threshold' => $cfg['threshold'],
            ];
        }

        return $cards;
    }

    /**
     * Ambil ringkasan pengukuran Gas Monitoring aktual untuk tanggal pengukuran terbaru (seluruh Well Pad).
     */
    public static function getLatestDailySummary(?string $todayStr = null): array
    {
        $latestDateStr = static::resolveLatestMeasurementDate($todayStr);

        if ($latestDateStr === null) {
            return [
                'has_data' => false,
                'latest_date' => null,
                'update_date_display' => 'Belum ada data',
                'last_input_time_formatted' => 'Belum ada input',
                'latest_record' => null,
                'records' => collect(),
                'cards' => static::buildSummaryCardsFromCollection(collect()),
            ];
        }

        $records = static::whereDate('measurement_date', $latestDateStr)
            ->orderBy('id', 'desc')
            ->get();

        $latestRecord = static::whereDate('measurement_date', $latestDateStr)
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $updateDateDisplay = Carbon::parse($latestDateStr)
            ->locale('id')
            ->translatedFormat('l, d F Y');

        $lastInputTimeFormatted = $latestRecord && $latestRecord->updated_at
            ? $latestRecord->updated_at->setTimezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') . ' WIB'
            : 'Belum ada input';

        return [
            'has_data' => $records->isNotEmpty(),
            'latest_date' => $latestDateStr,
            'update_date_display' => $updateDateDisplay,
            'last_input_time_formatted' => $lastInputTimeFormatted,
            'latest_record' => $latestRecord,
            'records' => $records,
            'cards' => static::buildSummaryCardsFromCollection($records),
        ];
    }
}