<?php

use App\Models\DailyWorkCompany;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penyempurnaan Master Perusahaan Daily Work (tambahan, non-destruktif):
 *  - daily_work_companies : category, parent_company_id, is_required, sort_order
 *  - daily_works          : company_id (relasi ID ke master; kolom `unit` tetap dipertahankan)
 *
 * Tidak ada tabel master kedua, tidak ada data yang dihapus. Data lama tetap utuh.
 */
return new class extends Migration
{
    /**
     * Daftar nama perusahaan lama (pra-revisi) yang TIDAK termasuk 16 perusahaan wajib.
     * Baris ini dipertahankan apa adanya (ID & nama tidak diubah) agar histori pekerjaan lama
     * tetap teridentifikasi, hanya dinonaktifkan agar tidak muncul untuk pekerjaan baru.
     * Tidak digabung otomatis ke perusahaan baru karena kemiripan nama.
     */
    private const LEGACY_NAMES = [
        'GDE (Internal)' => DailyWorkCompany::CATEGORY_MAIN,
        'JOTRE' => DailyWorkCompany::CATEGORY_MAIN,
        'BUJP (Security)' => DailyWorkCompany::CATEGORY_INDEPENDENT,
        '(Security)' => DailyWorkCompany::CATEGORY_INDEPENDENT,
    ];

    public function up(): void
    {
        if (!Schema::hasTable('daily_work_companies')) {
            return;
        }

        // 1. Kolom baru pada master perusahaan
        Schema::table('daily_work_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_work_companies', 'category')) {
                $table->string('category', 20)->default(DailyWorkCompany::CATEGORY_INDEPENDENT)->after('name');
                $table->index('category');
            }
            if (!Schema::hasColumn('daily_work_companies', 'parent_company_id')) {
                $table->unsignedBigInteger('parent_company_id')->nullable()->after('category');
                $table->foreign('parent_company_id')
                    ->references('id')->on('daily_work_companies')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('daily_work_companies', 'is_required')) {
                $table->boolean('is_required')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('daily_work_companies', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(100)->after('is_required');
            }
        });

        // 2. Relasi ID pada pekerjaan harian (kolom `unit` string tetap ada sebagai nama tampilan/histori)
        if (Schema::hasTable('daily_works') && !Schema::hasColumn('daily_works', 'company_id')) {
            Schema::table('daily_works', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('unit');
                $table->foreign('company_id')
                    ->references('id')->on('daily_work_companies')
                    ->restrictOnDelete();
            });
        }

        // 3. Klasifikasi data lama (hanya baris yang sudah ada, tanpa mengubah ID/nama)
        $now = now();
        foreach (self::LEGACY_NAMES as $name => $category) {
            DB::table('daily_work_companies')
                ->where('name', $name)
                ->where('is_required', false)
                ->update([
                    'category' => $category,
                    'is_active' => false,
                    'sort_order' => 1000,
                    'updated_at' => $now,
                ]);
        }

        // 4. 16 perusahaan wajib (idempotent: tidak menduplikasi nama yang sudah ada)
        DailyWorkCompany::syncRequired();

        // 5. Backfill relasi ID dari nama persis (tidak ada penggabungan berdasarkan kemiripan nama)
        if (Schema::hasTable('daily_works')) {
            foreach (DB::table('daily_work_companies')->get(['id', 'name']) as $company) {
                DB::table('daily_works')
                    ->whereNull('company_id')
                    ->where('unit', $company->name)
                    ->update(['company_id' => $company->id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('daily_works') && Schema::hasColumn('daily_works', 'company_id')) {
            Schema::table('daily_works', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        if (Schema::hasTable('daily_work_companies')) {
            Schema::table('daily_work_companies', function (Blueprint $table) {
                if (Schema::hasColumn('daily_work_companies', 'parent_company_id')) {
                    $table->dropForeign(['parent_company_id']);
                    $table->dropColumn('parent_company_id');
                }
            });
            Schema::table('daily_work_companies', function (Blueprint $table) {
                if (Schema::hasColumn('daily_work_companies', 'category')) {
                    $table->dropIndex(['category']);
                    $table->dropColumn('category');
                }
                if (Schema::hasColumn('daily_work_companies', 'is_required')) {
                    $table->dropColumn('is_required');
                }
                if (Schema::hasColumn('daily_work_companies', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }
    }
};

