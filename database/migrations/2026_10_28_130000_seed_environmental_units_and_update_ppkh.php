<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah satuan pemantauan lingkungan ke Master Data jika belum ada
        $envUnits = [
            ['category' => 'unit', 'name' => 'dBA', 'code' => 'DBA', 'order_index' => 10],
            ['category' => 'unit', 'name' => 'mg/L', 'code' => 'MG_L', 'order_index' => 11],
            ['category' => 'unit', 'name' => 'ppm', 'code' => 'PPM', 'order_index' => 12],
            ['category' => 'unit', 'name' => 'mg/Nm3', 'code' => 'MG_NM3', 'order_index' => 13],
            ['category' => 'unit', 'name' => '°C', 'code' => 'CELSIUS', 'order_index' => 14],
            ['category' => 'unit', 'name' => '%', 'code' => 'PERCENT', 'order_index' => 15],
            ['category' => 'unit', 'name' => 'pH', 'code' => 'PH', 'order_index' => 16],
            ['category' => 'unit', 'name' => 'Indeks', 'code' => 'INDEX', 'order_index' => 17],
        ];

        $now = now();
        foreach ($envUnits as $u) {
            $exists = DB::table('environment_masters')
                ->where('category', 'unit')
                ->where('name', $u['name'])
                ->exists();

            if (!$exists) {
                DB::table('environment_masters')->insert([
                    'category' => $u['category'],
                    'name' => $u['name'],
                    'code' => $u['code'],
                    'order_index' => $u['order_index'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reversal needed
    }
};

