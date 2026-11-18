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
        if (Schema::hasTable('issue_follow_ups') && !Schema::hasColumn('issue_follow_ups', 'incident_id')) {
            Schema::table('issue_follow_ups', function (Blueprint $table) {
                $table->unsignedBigInteger('incident_id')->nullable()->after('id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('issue_follow_ups') && Schema::hasColumn('issue_follow_ups', 'incident_id')) {
            Schema::table('issue_follow_ups', function (Blueprint $table) {
                $table->dropColumn('incident_id');
            });
        }
    }
};
