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
            if (!Schema::hasColumn('gas_monitorings', 'co_notes')) {
                $table->string('co_notes', 255)->nullable()->after('co_status');
            }
            if (!Schema::hasColumn('gas_monitorings', 'o2_notes')) {
                $table->string('o2_notes', 255)->nullable()->after('o2_status');
            }
            if (!Schema::hasColumn('gas_monitorings', 'h2s_notes')) {
                $table->string('h2s_notes', 255)->nullable()->after('h2s_status');
            }
            if (!Schema::hasColumn('gas_monitorings', 'lel_notes')) {
                $table->string('lel_notes', 255)->nullable()->after('lel_status');
            }
            if (!Schema::hasColumn('gas_monitorings', 'pressure_notes')) {
                $table->string('pressure_notes', 255)->nullable()->after('pressure_status');
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
            $columns = array_values(array_filter([
                Schema::hasColumn('gas_monitorings', 'co_notes') ? 'co_notes' : null,
                Schema::hasColumn('gas_monitorings', 'o2_notes') ? 'o2_notes' : null,
                Schema::hasColumn('gas_monitorings', 'h2s_notes') ? 'h2s_notes' : null,
                Schema::hasColumn('gas_monitorings', 'lel_notes') ? 'lel_notes' : null,
                Schema::hasColumn('gas_monitorings', 'pressure_notes') ? 'pressure_notes' : null,
            ]));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
