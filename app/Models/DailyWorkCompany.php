<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class DailyWorkCompany extends Model
{
    use HasFactory;

    public const CATEGORY_MAIN = 'main';
    public const CATEGORY_SUBCON = 'subcon';
    public const CATEGORY_INDEPENDENT = 'independent';

    public const CATEGORY_LABELS = [
        self::CATEGORY_MAIN => 'Perusahaan Utama',
        self::CATEGORY_SUBCON => 'Subcon',
        self::CATEGORY_INDEPENDENT => 'Perusahaan Independen',
    ];

    /**
     * 16 perusahaan wajib Daily Work (urutan = urutan tampil).
     * Hubungan subcon disimpan terstruktur lewat parent_company_id.
     * GDE (Only) dan KODIPA berada di bawah induk GDE (All).
     * JOTRE (Only), PJSE, ADP, SOKKO, BISMO, WCP berada di bawah induk JOTRE (All).
     * Perusahaan independen tidak memiliki induk.
     */
    public const REQUIRED = [
        ['name' => 'GDE (All)', 'category' => self::CATEGORY_MAIN, 'parent' => null],
        ['name' => 'GDE (Only)', 'category' => self::CATEGORY_SUBCON, 'parent' => 'GDE (All)'],
        ['name' => 'KODIPA', 'category' => self::CATEGORY_SUBCON, 'parent' => 'GDE (All)'],
        ['name' => 'JOTRE (All)', 'category' => self::CATEGORY_MAIN, 'parent' => null],
        ['name' => 'JOTRE (Only)', 'category' => self::CATEGORY_SUBCON, 'parent' => 'JOTRE (All)'],
        ['name' => 'PJSE', 'category' => self::CATEGORY_SUBCON, 'parent' => 'JOTRE (All)'],
        ['name' => 'ADP', 'category' => self::CATEGORY_SUBCON, 'parent' => 'JOTRE (All)'],
        ['name' => 'SOKKO', 'category' => self::CATEGORY_SUBCON, 'parent' => 'JOTRE (All)'],
        ['name' => 'BISMO', 'category' => self::CATEGORY_SUBCON, 'parent' => 'JOTRE (All)'],
        ['name' => 'WCP', 'category' => self::CATEGORY_SUBCON, 'parent' => 'JOTRE (All)'],
        ['name' => 'TDP (BUJP)', 'category' => self::CATEGORY_INDEPENDENT, 'parent' => null],
        ['name' => 'PJSE (Utility)', 'category' => self::CATEGORY_INDEPENDENT, 'parent' => null],
        ['name' => 'Rentokil (Pest Control)', 'category' => self::CATEGORY_INDEPENDENT, 'parent' => null],
        ['name' => 'PMC', 'category' => self::CATEGORY_INDEPENDENT, 'parent' => null],
        ['name' => 'Bhumi Phala (Maintenance EWS)', 'category' => self::CATEGORY_INDEPENDENT, 'parent' => null],
        ['name' => 'Environesia', 'category' => self::CATEGORY_INDEPENDENT, 'parent' => null],
    ];

    protected $table = 'daily_work_companies';

    protected $fillable = [
        'name',
        'category',
        'parent_company_id',
        'description',
        'is_active',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'parent_company_id' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'category_label',
        'parent_name',
        'parent_short_name',
        'is_parent',
        'can_be_executor',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_company_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_company_id')->ordered();
    }

    public function works(): HasMany
    {
        return $this->hasMany(DailyWork::class, 'company_id');
    }

    /**
     * Scope untuk perusahaan yang berstatus aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Urutan tampil: perusahaan awal/wajib (sort_order 1..16), diikuti perusahaan lama (sort_order 1000),
     * lalu perusahaan baru yang ditambahkan selalu berada paling bawah berdasarkan urutan penambahan (id).
     */
    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN is_required = 1 THEN 0 WHEN sort_order = 1000 THEN 1 ELSE 2 END ASC')
            ->orderByRaw('CASE WHEN is_required = 1 OR sort_order = 1000 THEN sort_order ELSE id END ASC')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? '-';
    }

    public function getParentNameAttribute(): ?string
    {
        return $this->parent_company_id ? $this->parent?->name : null;
    }

    public function getParentShortNameAttribute(): ?string
    {
        $parent = $this->parent_name;
        if (!$parent) {
            return null;
        }

        return preg_replace('/\s*\(All\)$/i', '', trim($parent));
    }

    /**
     * Menentukan apakah perusahaan ini berstatus sebagai perusahaan induk (memiliki subcon).
     * Perusahaan induk berfungsi sebagai entitas naungan dan tidak boleh dipilih sebagai pelaksana pekerjaan.
     */
    public function isParentCompany(): bool
    {
        if ($this->category === self::CATEGORY_MAIN) {
            return true;
        }

        if (in_array(strtoupper(trim((string) $this->name)), ['GDE (ALL)', 'JOTRE (ALL)'], true)) {
            return true;
        }

        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        if (isset($this->children_count)) {
            return (int) $this->children_count > 0;
        }

        return $this->children()->exists();
    }

    /**
     * Menentukan apakah perusahaan ini layak dipilih sebagai pelaksana pekerjaan baru.
     * Syarat: harus aktif dan bukan merupakan perusahaan induk.
     */
    public function canBeWorkExecutor(): bool
    {
        return $this->is_active && !$this->isParentCompany();
    }

    public function getIsParentAttribute(): bool
    {
        return $this->isParentCompany();
    }

    public function getCanBeExecutorAttribute(): bool
    {
        return $this->canBeWorkExecutor();
    }

    /**
     * Cari perusahaan berdasarkan nama (tanpa memperhatikan huruf besar/kecil & spasi ganda).
     */
    public static function findByName(?string $name): ?self
    {
        $name = preg_replace('/\s+/', ' ', trim((string) $name));
        if ($name === '') {
            return null;
        }

        return static::where('name', $name)->first();
    }

    /**
     * Ambil daftar perusahaan aktif yang memenuhi syarat untuk filter dashboard.
     * Aturan sinkronisasi:
     * 1. Perusahaan harus berstatus aktif (is_active = true).
     * 2. Untuk subcon, perusahaan induknya juga harus berstatus aktif.
     *    Jika induk dinonaktifkan, seluruh subcon di bawahnya otomatis tidak tersedia.
     * 3. Jika hanya satu subcon yang dinonaktifkan sementara induknya aktif, sembunyikan hanya subcon tersebut.
     * 4. GDE (Only) dan JOTRE (Only) tetap menjadi pilihan tersendiri selama masing-masing aktif.
     * 5. Perusahaan independen hanya bergantung pada status aktif dirinya sendiri.
     *
     * @return Collection<int, self>
     */
    public static function getActiveForFilter(): Collection
    {
        $all = static::with('parent')->ordered()->get();
        $activeMap = $all->keyBy('id');

        $filtered = $all->filter(function (self $company) use ($activeMap) {
            if (!$company->is_active) {
                return false;
            }

            if ($company->parent_company_id) {
                $parent = $company->parent ?? $activeMap->get($company->parent_company_id);
                if (!$parent || !$parent->is_active) {
                    return false;
                }
            }

            return true;
        })->values();

        return new Collection($filtered->all());
    }

    /**
     * Kelompokkan perusahaan untuk dropdown: Pilihan Utama, Subcon (per induk), Perusahaan Independen.
     * Seluruh label berasal dari data master di database.
     *
     * @param  iterable<self>  $companies
     * @return array<int, array{label: string, items: array<int, self>}>
     */
    public static function groupForSelect(iterable $companies): array
    {
        $companies = collect($companies)->values();
        $groups = [];

        $main = $companies->where('category', self::CATEGORY_MAIN)->values();
        if ($main->isNotEmpty()) {
            $groups[] = ['label' => 'Perusahaan Utama', 'items' => $main->all()];
        }

        $subcons = $companies->where('category', self::CATEGORY_SUBCON)->values();
        foreach ($subcons->groupBy(fn ($c) => $c->parent_name ?? '') as $parentName => $items) {
            $groups[] = [
                'label' => $parentName !== '' ? "Subcon (induk: {$parentName})" : 'Subcon',
                'items' => $items->all(),
            ];
        }

        $independent = $companies->where('category', self::CATEGORY_INDEPENDENT)->values();
        if ($independent->isNotEmpty()) {
            $groups[] = ['label' => 'Perusahaan Independen', 'items' => $independent->all()];
        }

        return $groups;
    }

    /**
     * Pastikan 16 perusahaan wajib tersedia tepat satu kali. Aman dijalankan berulang:
     *  - nama yang sudah ada tidak diduplikasi;
     *  - status aktif/nonaktif, kategori, dan induk yang sudah diatur pengguna tidak ditimpa;
     *  - hanya baris yang baru dibuat / baru "diadopsi" yang diberi kategori & induk awal.
     */
    public static function syncRequired(): void
    {
        DB::transaction(function () {
            $adopted = [];

            foreach (self::REQUIRED as $index => $def) {
                $company = static::where('name', $def['name'])->first();

                if (!$company) {
                    $company = static::create([
                        'name' => $def['name'],
                        'category' => $def['category'],
                        'is_active' => true,
                        'is_required' => true,
                        'sort_order' => $index + 1,
                    ]);
                    $adopted[$def['name']] = $company;
                } elseif (!$company->is_required) {
                    // Baris lama dengan nama yang sama: pertahankan ID & status, pakai ejaan baku.
                    $company->update([
                        'name' => $def['name'],
                        'category' => $def['category'],
                        'is_required' => true,
                        'sort_order' => $index + 1,
                    ]);
                    $adopted[$def['name']] = $company;
                }
            }

            foreach (self::REQUIRED as $def) {
                if ($def['parent'] === null || !isset($adopted[$def['name']])) {
                    continue;
                }
                $parent = static::where('name', $def['parent'])->first();
                if ($parent) {
                    $adopted[$def['name']]->update(['parent_company_id' => $parent->id]);
                }
            }
        });
    }

    /**
     * Inisialisasi ringan: hanya menjalankan sinkronisasi jika tabel perusahaan masih kosong.
     */
    public static function ensureRequired(): void
    {
        if (static::count() === 0) {
            static::syncRequired();
        }
    }
}
