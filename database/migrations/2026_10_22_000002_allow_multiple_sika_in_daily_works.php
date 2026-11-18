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
        if (Schema::hasTable('daily_works')) {
            Schema::table('daily_works', function (Blueprint $table) {
                if (Schema::hasColumn('daily_works', 'sikka')) {
                    $table->text('sikka')->nullable()->change();
                }
                if (Schema::hasColumn('daily_works', 'ptw_classification')) {
                    $table->text('ptw_classification')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('daily_works')) {
            Schema::table('daily_works', function (Blueprint $table) {
                if (Schema::hasColumn('daily_works', 'sikka')) {
                    $table->string('sikka', 100)->nullable()->change();
                }
                if (Schema::hasColumn('daily_works', 'ptw_classification')) {
                    $table->string('ptw_classification', 100)->nullable()->change();
                }
            });
        }
    }
};

