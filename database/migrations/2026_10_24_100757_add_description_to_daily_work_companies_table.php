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
        Schema::table('daily_work_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_work_companies', 'description')) {
                $table->string('description', 255)->nullable()->after('parent_company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_work_companies', function (Blueprint $table) {
            if (Schema::hasColumn('daily_work_companies', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

