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
        if (!Schema::hasTable('gas_monitorings')) {
            return;
        }

        Schema::table('gas_monitorings', function (Blueprint $table) {
            if (Schema::hasColumn('gas_monitorings', 'co_level')) {
                $table->decimal('co_level', 8, 2)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'o2_level')) {
                $table->decimal('o2_level', 8, 2)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'h2s_level')) {
                $table->decimal('h2s_level', 8, 2)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'lel_level')) {
                $table->decimal('lel_level', 8, 2)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'pressure')) {
                $table->decimal('pressure', 8, 2)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'co_status')) {
                $table->string('co_status', 20)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'o2_status')) {
                $table->string('o2_status', 20)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'h2s_status')) {
                $table->string('h2s_status', 20)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'lel_status')) {
                $table->string('lel_status', 20)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'pressure_status')) {
                $table->string('pressure_status', 20)->nullable()->default(null)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'overall_status')) {
                $table->string('overall_status', 20)->nullable()->default(null)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('gas_monitorings')) {
            return;
        }

        Schema::table('gas_monitorings', function (Blueprint $table) {
            if (Schema::hasColumn('gas_monitorings', 'co_level')) {
                $table->decimal('co_level', 8, 2)->nullable(false)->default(0)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'h2s_level')) {
                $table->decimal('h2s_level', 8, 2)->nullable(false)->default(0)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'pressure')) {
                $table->decimal('pressure', 8, 2)->nullable(false)->default(0)->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'co_status')) {
                $table->string('co_status', 20)->nullable(false)->default('Sesuai')->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'o2_status')) {
                $table->string('o2_status', 20)->nullable(false)->default('Sesuai')->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'h2s_status')) {
                $table->string('h2s_status', 20)->nullable(false)->default('Sesuai')->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'lel_status')) {
                $table->string('lel_status', 20)->nullable(false)->default('Sesuai')->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'pressure_status')) {
                $table->string('pressure_status', 20)->nullable(false)->default('Sesuai')->change();
            }
            if (Schema::hasColumn('gas_monitorings', 'overall_status')) {
                $table->string('overall_status', 20)->nullable(false)->default('Sesuai')->change();
            }
        });
    }
};
