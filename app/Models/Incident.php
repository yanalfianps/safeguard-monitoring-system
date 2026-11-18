<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Incident extends Model
{
    use HasFactory;

    protected $table = 'incidents';

    public const KLASIFIKASI_AREAS = [
        'Kecelakaan Panas Bumi',
        'Kecelakaan Non-Panas Bumi',
    ];

    public const KATEGORI_UMUM_LIST = [
        'Fatality',
        'LTI (Lost Time Injury)',
        'Medical Treatment Case',
        'First Aid Case',
        'Property Damage',
        'Near Miss (Hampir Celaka)',
        'Environmental Incident',
        'Vehicle Accident',
        'Fire Incident',
    ];

    public const KATEGORI_EBTKE_LIST = [
        'Kecelakaan Mati',
        'Kecelakaan Berat',
        'Kecelakaan Ringan',
        'Kejadian Berbahaya',
        'Penyakit Akibat Kerja',
    ];

    public const KATEGORI_UMUM_PALETTE = [
        'Fatality' => '#b91c1c',                  // Merah Crimson
        'LTI (Lost Time Injury)' => '#ea580c',    // Oranye Terang
        'Medical Treatment Case' => '#0d9488',    // Pirus / Dark Teal
        'First Aid Case' => '#10b981',            // Hijau Zamrud
        'Property Damage' => '#2563eb',           // Biru Safir
        'Near Miss (Hampir Celaka)' => '#d97706', // Golden Amber
        'Environmental Incident' => '#65a30d',    // Hijau Limau
        'Vehicle Accident' => '#7c3aed',          // Ungu Lembayung
        'Fire Incident' => '#f43f5e',             // Merah Koral
    ];

    public const KATEGORI_EBTKE_PALETTE = [
        'Kecelakaan Mati' => '#7f1d1d',           // Marun Gelap
        'Kecelakaan Berat' => '#dc2626',          // Scarlet
        'Kecelakaan Ringan' => '#f59e0b',         // Amber
        'Kejadian Berbahaya' => '#1d4ed8',        // Royal Blue
        'Penyakit Akibat Kerja' => '#059669',     // Forest Emerald
    ];

    public const DAFTAR_HARI = [
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Minggu',
    ];

    protected $fillable = [
        'incident_code',
        'nomor_insiden',
        'title',
        'incident_type',
        'unit',
        'incident_date',
        'tanggal_waktu',
        'hari',
        'location',
        'lokasi_kejadian',
        'severity',
        'description',
        'deskripsi_kejadian',
        'immediate_action',
        'root_cause',
        'corrective_action',
        'tindakan_perbaikan',
        'status',
        'status_laporan',
        'reported_by',
        'penanggung_jawab',
        'target_penyelesaian',
        'closed_at',
        'klasifikasi_area',
        'kategori_umum',
        'kategori_ebtke',
        'komponen_terkait',
        'jumlah_hari_hilang',
        'dokumen_investigasi',
    ];

    protected $casts = [
        'incident_date' => 'datetime',
        'tanggal_waktu' => 'datetime',
        'target_penyelesaian' => 'date',
        'closed_at' => 'date',
        'kategori_umum' => 'array',
        'kategori_ebtke' => 'array',
        'jumlah_hari_hilang' => 'integer',
    ];

    protected $appends = [
        'documents_list',
    ];

    /**
     * Relasi ke lampiran media (foto & dokumen bukti investigasi).
     */
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'mediable');
    }

    /**
     * Daftar seluruh file dokumentasi yang terlampir pada insiden (dari mediaAttachments + fallback dokumen_investigasi).
     */
    public function getDocumentsListAttribute(): array
    {
        $items = [];
        $seenPaths = [];

        $attachments = $this->relationLoaded('mediaAttachments')
            ? $this->mediaAttachments
            : $this->mediaAttachments()->get();

        foreach ($attachments as $att) {
            $path = $att->file_path;
            $seenPaths[$path] = true;
            $items[] = [
                'id' => $att->id,
                'original_name' => $att->original_name ?: basename((string) $path),
                'file_name' => $att->file_name ?: basename((string) $path),
                'file_path' => $path,
                'file_url' => route('incident.documents.view', $att->id),
                'mime_type' => $att->mime_type ?: 'application/octet-stream',
                'extension' => strtolower((string) ($att->extension ?: pathinfo((string) $path, PATHINFO_EXTENSION))),
                'size' => (int) ($att->original_size ?: $att->compressed_size ?: 0),
            ];
        }

        if (!empty($this->dokumen_investigasi) && !isset($seenPaths[$this->dokumen_investigasi])) {
            $path = $this->dokumen_investigasi;
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            $items[] = [
                'id' => null,
                'original_name' => basename($path),
                'file_name' => basename($path),
                'file_path' => $path,
                'file_url' => route('incident.legacy-document', $this->id),
                'mime_type' => in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true) ? "image/{$ext}" : ($ext === 'pdf' ? 'application/pdf' : 'application/octet-stream'),
                'extension' => $ext,
                'size' => 0,
            ];
        }

        return $items;
    }

    /**
     * Dual-Field Auto-Synchronization lifecycle hook.
     */
    protected static function booted(): void
    {
        static::saving(function (Incident $inc) {
            // 1. Sinkronisasi nomor_insiden <-> incident_code (Jika kosong, auto-generate)
            if (empty($inc->nomor_insiden) && !empty($inc->incident_code)) {
                $inc->nomor_insiden = $inc->incident_code;
            } elseif (empty($inc->incident_code) && !empty($inc->nomor_insiden)) {
                $inc->incident_code = $inc->nomor_insiden;
            } elseif (empty($inc->nomor_insiden) && empty($inc->incident_code)) {
                $code = static::generateNextNumber();
                $inc->nomor_insiden = $code;
                $inc->incident_code = $code;
            } elseif ($inc->isDirty('nomor_insiden') && !$inc->isDirty('incident_code')) {
                $inc->incident_code = $inc->nomor_insiden;
            } elseif ($inc->isDirty('incident_code') && !$inc->isDirty('nomor_insiden')) {
                $inc->nomor_insiden = $inc->incident_code;
            }

            // 2. Sinkronisasi tanggal_waktu <-> incident_date (Jika kosong, diisi waktu now())
            if (empty($inc->tanggal_waktu) && !empty($inc->incident_date)) {
                $inc->tanggal_waktu = $inc->incident_date;
            } elseif (empty($inc->incident_date) && !empty($inc->tanggal_waktu)) {
                $inc->incident_date = $inc->tanggal_waktu;
            } elseif (empty($inc->tanggal_waktu) && empty($inc->incident_date)) {
                $now = now();
                $inc->tanggal_waktu = $now;
                $inc->incident_date = $now;
            } elseif ($inc->isDirty('tanggal_waktu') && !$inc->isDirty('incident_date')) {
                $inc->incident_date = $inc->tanggal_waktu;
            } elseif ($inc->isDirty('incident_date') && !$inc->isDirty('tanggal_waktu')) {
                $inc->tanggal_waktu = $inc->incident_date;
            }

            // 3. Sinkronisasi klasifikasi_area <-> incident_type
            if (empty($inc->klasifikasi_area) && !empty($inc->incident_type)) {
                $inc->klasifikasi_area = $inc->incident_type;
            } elseif (empty($inc->incident_type) && !empty($inc->klasifikasi_area)) {
                $inc->incident_type = $inc->klasifikasi_area;
            } elseif (empty($inc->klasifikasi_area) && empty($inc->incident_type)) {
                $inc->klasifikasi_area = 'Kecelakaan Panas Bumi';
                $inc->incident_type = 'Kecelakaan Panas Bumi';
            } elseif ($inc->isDirty('klasifikasi_area') && !$inc->isDirty('incident_type')) {
                $inc->incident_type = $inc->klasifikasi_area;
            }

            // 4. Sinkronisasi lokasi_kejadian <-> location (Default: 'Area Operasi Dieng')
            if (empty($inc->lokasi_kejadian) && !empty($inc->location)) {
                $inc->lokasi_kejadian = $inc->location;
            } elseif (empty($inc->location) && !empty($inc->lokasi_kejadian)) {
                $inc->location = $inc->lokasi_kejadian;
            } elseif (empty($inc->lokasi_kejadian) && empty($inc->location)) {
                $inc->lokasi_kejadian = 'Area Operasi Dieng';
                $inc->location = 'Area Operasi Dieng';
            } elseif ($inc->isDirty('lokasi_kejadian') && !$inc->isDirty('location')) {
                $inc->location = $inc->lokasi_kejadian;
            } elseif ($inc->isDirty('location') && !$inc->isDirty('lokasi_kejadian')) {
                $inc->lokasi_kejadian = $inc->location;
            }

            // 5. Sinkronisasi komponen_terkait <-> unit (Default: 'Unit Dieng')
            if (empty($inc->komponen_terkait) && !empty($inc->unit)) {
                $inc->komponen_terkait = $inc->unit;
            } elseif (empty($inc->unit) && !empty($inc->komponen_terkait)) {
                $inc->unit = $inc->komponen_terkait;
            } elseif (empty($inc->komponen_terkait) && empty($inc->unit)) {
                $inc->komponen_terkait = 'Unit Dieng';
                $inc->unit = 'Unit Dieng';
            } elseif ($inc->isDirty('komponen_terkait') && !$inc->isDirty('unit')) {
                $inc->unit = $inc->komponen_terkait;
            } elseif ($inc->isDirty('unit') && !$inc->isDirty('komponen_terkait')) {
                $inc->komponen_terkait = $inc->unit;
            }

            // 6. Sinkronisasi deskripsi_kejadian <-> description
            if (empty($inc->deskripsi_kejadian) && !empty($inc->description)) {
                $inc->deskripsi_kejadian = $inc->description;
            } elseif (empty($inc->description) && !empty($inc->deskripsi_kejadian)) {
                $inc->description = $inc->deskripsi_kejadian;
            } elseif (empty($inc->deskripsi_kejadian) && empty($inc->description)) {
                $inc->deskripsi_kejadian = '-';
                $inc->description = '-';
            } elseif ($inc->isDirty('deskripsi_kejadian') && !$inc->isDirty('description')) {
                $inc->description = $inc->deskripsi_kejadian;
            } elseif ($inc->isDirty('description') && !$inc->isDirty('deskripsi_kejadian')) {
                $inc->deskripsi_kejadian = $inc->description;
            }

            // 7. Sinkronisasi penanggung_jawab <-> reported_by (Default: User login)
            if (empty($inc->penanggung_jawab) && !empty($inc->reported_by)) {
                $inc->penanggung_jawab = $inc->reported_by;
            } elseif (empty($inc->reported_by) && !empty($inc->penanggung_jawab)) {
                $inc->reported_by = $inc->penanggung_jawab;
            } elseif (empty($inc->penanggung_jawab) && empty($inc->reported_by)) {
                $defaultUser = auth()->user()?->name ?? 'Petugas HSSE';
                $inc->penanggung_jawab = $defaultUser;
                $inc->reported_by = $defaultUser;
            } elseif ($inc->isDirty('penanggung_jawab') && !$inc->isDirty('reported_by')) {
                $inc->reported_by = $inc->penanggung_jawab;
            } elseif ($inc->isDirty('reported_by') && !$inc->isDirty('penanggung_jawab')) {
                $inc->penanggung_jawab = $inc->reported_by;
            }

            // 8. Sinkronisasi tindakan_perbaikan <-> corrective_action
            if (empty($inc->tindakan_perbaikan) && !empty($inc->corrective_action)) {
                $inc->tindakan_perbaikan = $inc->corrective_action;
            } elseif (empty($inc->corrective_action) && !empty($inc->tindakan_perbaikan)) {
                $inc->corrective_action = $inc->tindakan_perbaikan;
            } elseif ($inc->isDirty('tindakan_perbaikan') && !$inc->isDirty('corrective_action')) {
                $inc->corrective_action = $inc->tindakan_perbaikan;
            } elseif ($inc->isDirty('corrective_action') && !$inc->isDirty('tindakan_perbaikan')) {
                $inc->tindakan_perbaikan = $inc->corrective_action;
            }

            // 9. Sinkronisasi status_laporan ('Preliminary' -> 'Investigasi', 'Final' -> 'Selesai')
            if (empty($inc->status_laporan)) {
                $inc->status_laporan = ($inc->status === 'Selesai') ? 'Final' : 'Preliminary';
            }
            if (
                empty($inc->status)
                || !in_array($inc->status, ['Dilaporkan', 'Investigasi', 'Dalam Penanganan', 'Selesai'])
                || ($inc->isDirty('status_laporan') && !$inc->isDirty('status'))
            ) {
                $inc->status = in_array($inc->status_laporan, ['Final', 'Selesai']) ? 'Selesai' : 'Investigasi';
            }

            // Severity
            if (empty($inc->severity)) {
                $inc->severity = 'Low';
            }

            // Default title
            if (empty($inc->title)) {
                $inc->title = $inc->nomor_insiden . ' - ' . ($inc->lokasi_kejadian ?? 'Dieng');
            }

            // 10. Sinkronisasi hari otomatis dan konsisten dari tanggal_waktu (Timezone Asia/Jakarta)
            if (!empty($inc->tanggal_waktu)) {
                $inc->hari = static::resolveDayName($inc->tanggal_waktu);
            }
        });
    }

    /**
     * Konversi tanggal ke nama hari Bahasa Indonesia (Senin–Minggu) menggunakan timezone Asia/Jakarta.
     */
    public static function resolveDayName($date): string
    {
        $dayMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        try {
            $dt = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)
                : Carbon::parse((string) $date, 'Asia/Jakarta');
            $dayName = $dt->format('l');
            return $dayMap[$dayName] ?? 'Senin';
        } catch (\Throwable $e) {
            return 'Senin';
        }
    }

    /**
     * Accessor untuk nama hari dalam Bahasa Indonesia.
     */
    public function getHariAttribute(): string
    {
        if (!empty($this->attributes['hari'])) {
            return $this->attributes['hari'];
        }
        if (!empty($this->tanggal_waktu)) {
            return static::resolveDayName($this->tanggal_waktu);
        }
        return 'Senin';
    }

    /**
     * Generate Next Incident Number sequentially (INC-GDE-{TAHUN}-{00X}).
     */
    public static function generateNextNumber(): string
    {
        $year = date('Y');
        $prefix = "INC-GDE-{$year}-";
        $latest = static::where('nomor_insiden', 'like', "{$prefix}%")
            ->orWhere('incident_code', 'like', "{$prefix}%")
            ->latest('id')
            ->first();

        if ($latest) {
            $num = $latest->nomor_insiden ?? $latest->incident_code;
            if (preg_match('/(\d+)$/', $num, $matches)) {
                $next = (int) $matches[1] + 1;
                return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
            }
        }

        return $prefix . '001';
    }
}
