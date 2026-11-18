<?php

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
        Schema::table('hsse_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('hsse_plans', 'target_frequency')) {
                $table->string('target_frequency', 50)->default('Tahunan')->nullable()->after('unit_measure');
            }
            if (!Schema::hasColumn('hsse_plans', 'monthly_realizations')) {
                $table->json('monthly_realizations')->nullable()->after('target_frequency');
            }
            if (!Schema::hasColumn('hsse_plans', 'realization_history')) {
                $table->json('realization_history')->nullable()->after('monthly_realizations');
            }
            if (!Schema::hasColumn('hsse_plans', 'project_start_date')) {
                $table->date('project_start_date')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('hsse_plans', 'project_end_date')) {
                $table->date('project_end_date')->nullable()->after('project_start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hsse_plans', function (Blueprint $table) {
            $cols = [];
            foreach (['target_frequency', 'monthly_realizations', 'realization_history', 'project_start_date', 'project_end_date'] as $col) {
                if (Schema::hasColumn('hsse_plans', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

