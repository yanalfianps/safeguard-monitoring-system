<?php

use App\Models\EnvironmentMaster;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('environment_masters', 'submodules')) {
            Schema::table('environment_masters', function (Blueprint $table) {
                $table->json('submodules')->nullable()->after('description');
            });
        }

        // Inisialisasi pemetaan awal untuk Master Data Satuan
        $mapping = [
            'Ton' => ['lb3', 'non_lb3'],
            'Kilogram (kg)' => ['lb3', 'non_lb3'],
            'Liter' => ['lb3', 'non_lb3', 'energi_bbm'],
            'Drum' => ['lb3', 'non_lb3'],
            'Sak/karung' => ['lb3', 'non_lb3'],
            'kWh' => ['energi_listrik'],
            'dBA' => ['rkl_rpl'],
            'mg/L' => ['rkl_rpl'],
            'ppm' => ['rkl_rpl'],
            'mg/Nm3' => ['rkl_rpl'],
            '°C' => ['rkl_rpl'],
            '%' => ['rkl_rpl'],
            'pH' => ['rkl_rpl'],
            'Indeks' => ['rkl_rpl'],
        ];

        foreach ($mapping as $unitName => $submodules) {
            EnvironmentMaster::where('category', 'unit')
                ->where('name', $unitName)
                ->update(['submodules' => $submodules]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('environment_masters', 'submodules')) {
            Schema::table('environment_masters', function (Blueprint $table) {
                $table->dropColumn('submodules');
            });
        }
    }
};

