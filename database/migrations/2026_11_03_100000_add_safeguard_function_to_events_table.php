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
        if (Schema::hasTable('events') && !Schema::hasColumn('events', 'safeguard_function')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('safeguard_function', 50)->default('Health & Safety')->after('title')->index();
            });

            // Backfill existing events based on category/unit/title keywords if possible
            DB::table('events')
                ->whereNull('safeguard_function')
                ->orWhere('safeguard_function', '')
                ->update(['safeguard_function' => 'Health & Safety']);

            DB::table('events')
                ->where(function ($q) {
                    $q->where('unit', 'like', '%Security%')
                      ->orWhere('unit', 'like', '%BUJP%')
                      ->orWhere('title', 'like', '%Security%')
                      ->orWhere('title', 'like', '%Keamanan%');
                })
                ->update(['safeguard_function' => 'Security']);

            DB::table('events')
                ->where(function ($q) {
                    $q->where('title', 'like', '%Lingkungan%')
                      ->orWhere('title', 'like', '%Limbah%')
                      ->orWhere('title', 'like', '%PROPER%')
                      ->orWhere('title', 'like', '%Environment%');
                })
                ->update(['safeguard_function' => 'Environment']);

            DB::table('events')
                ->where(function ($q) {
                    $q->where('title', 'like', '%Sosial%')
                      ->orWhere('title', 'like', '%Masyarakat%')
                      ->orWhere('title', 'like', '%Social%');
                })
                ->update(['safeguard_function' => 'Social']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'safeguard_function')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('safeguard_function');
            });
        }
    }
};

