<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Pastikan tabel sikkas memiliki 8 pilihan utama SIKA (termasuk Cold Work & Hot Work)
        if (Schema::hasTable('sikkas')) {
            $defaultSikas = [
                'Ruang Terbatas',
                'Ketinggian',
                'Penggalian',
                'Listrik',
                'Isolasi Energi',
                'Lifting',
                'Cold Work',
                'Hot Work',
            ];

            // Jika ada data tambahan di ptw_classifications, gabungkan juga ke daftar SIKA
            if (Schema::hasTable('ptw_classifications')) {
                $existingPtws = DB::table('ptw_classifications')->pluck('name')->toArray();
                foreach ($existingPtws as $ptwName) {
                    $ptwName = trim((string) $ptwName);
                    if ($ptwName !== '' && !in_array($ptwName, $defaultSikas, true)) {
                        $defaultSikas[] = $ptwName;
                    }
                }
            }

            foreach ($defaultSikas as $sikaName) {
                $exists = DB::table('sikkas')->where('name', $sikaName)->exists();
                if (!$exists) {
                    DB::table('sikkas')->insert([
                        'name' => $sikaName,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // 2. Tangani data lama pada tabel daily_works agar nilai Cold Work & Hot Work tidak hilang
        if (Schema::hasTable('daily_works')) {
            // Pastikan kolom ptw_classification dan sikka nullable
            Schema::table('daily_works', function (Blueprint $table) {
                if (Schema::hasColumn('daily_works', 'ptw_classification')) {
                    $table->string('ptw_classification', 100)->nullable()->change();
                }
                if (Schema::hasColumn('daily_works', 'sikka')) {
                    $table->string('sikka', 100)->nullable()->change();
                }
            });

            // Jika ada data lama yang kolom sikka-nya masih kosong/null/-, salin dari ptw_classification
            DB::table('daily_works')
                ->where(function ($q) {
                    $q->whereNull('sikka')
                      ->orWhere('sikka', '')
                      ->orWhere('sikka', '-');
                })
                ->whereNotNull('ptw_classification')
                ->where('ptw_classification', '!=', '')
                ->update([
                    'sikka' => DB::raw('ptw_classification'),
                ]);

            // Untuk data historis awal (ID 1-4) yang sebelumnya hanya diisi default 'Ruang Terbatas' saat migrasi awal,
            // selaraskan dengan nilai ptw_classification ('Cold Work' / 'Hot Work') agar distribusi data lama tetap terjaga
            DB::table('daily_works')
                ->whereIn('id', [1, 3, 4])
                ->where('sikka', 'Ruang Terbatas')
                ->whereNotNull('ptw_classification')
                ->where('ptw_classification', '!=', '')
                ->update([
                    'sikka' => DB::raw('ptw_classification'),
                ]);

            DB::table('daily_works')
                ->where('id', 2)
                ->where('sikka', 'Ruang Terbatas')
                ->update([
                    'sikka' => 'Hot Work',
                ]);

            // Pastikan tidak ada record yang sikka-nya tetap kosong
            DB::table('daily_works')
                ->whereNull('sikka')
                ->orWhere('sikka', '')
                ->update([
                    'sikka' => 'Cold Work',
                ]);

            // Perbarui teks 'SIKKA' menjadi 'SIKA' pada nama pekerjaan lama jika ada
            DB::table('daily_works')
                ->where('name', 'like', '%SIKKA%')
                ->get()
                ->each(function ($row) {
                    DB::table('daily_works')
                        ->where('id', $row->id)
                        ->update([
                            'name' => str_ireplace('SIKKA', 'SIKA', $row->name),
                        ]);
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data SIKA tetap dipertahankan agar tidak merusak data pekerjaan
    }
};

