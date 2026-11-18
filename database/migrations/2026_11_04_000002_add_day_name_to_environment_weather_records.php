<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('environment_weather_records')) {
            Schema::table('environment_weather_records', function (Blueprint $table) {
                if (!Schema::hasColumn('environment_weather_records', 'day_name')) {
                    $table->string('day_name', 30)->nullable()->after('recording_date');
                }
            });

            // Backfill existing records if any
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $existing = DB::table('environment_weather_records')->get();
            foreach ($existing as $row) {
                if (!empty($row->recording_date)) {
                    $d = Carbon::parse($row->recording_date);
                    $dayName = $days[$d->dayOfWeek] ?? 'Rabu';
                    DB::table('environment_weather_records')
                        ->where('id', $row->id)
                        ->update(['day_name' => $dayName]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('environment_weather_records')) {
            Schema::table('environment_weather_records', function (Blueprint $table) {
                if (Schema::hasColumn('environment_weather_records', 'day_name')) {
                    $table->dropColumn('day_name');
                }
            });
        }
    }
};

