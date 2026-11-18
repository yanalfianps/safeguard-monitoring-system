<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incidents') && !Schema::hasColumn('incidents', 'hari')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->string('hari', 20)->nullable()->after('tanggal_waktu');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('incidents') && Schema::hasColumn('incidents', 'hari')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->dropColumn('hari');
            });
        }
    }
};

