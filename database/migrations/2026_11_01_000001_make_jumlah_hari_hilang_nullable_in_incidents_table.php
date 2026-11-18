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
        if (Schema::hasTable('incidents') && Schema::hasColumn('incidents', 'jumlah_hari_hilang')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->unsignedInteger('jumlah_hari_hilang')->nullable()->default(null)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('incidents') && Schema::hasColumn('incidents', 'jumlah_hari_hilang')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->unsignedInteger('jumlah_hari_hilang')->nullable(false)->default(0)->change();
            });
        }
    }
};

