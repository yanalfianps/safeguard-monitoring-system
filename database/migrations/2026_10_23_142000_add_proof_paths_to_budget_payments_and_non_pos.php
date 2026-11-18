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
        if (Schema::hasTable('budget_po_payments') && !Schema::hasColumn('budget_po_payments', 'proof_paths')) {
            Schema::table('budget_po_payments', function (Blueprint $table) {
                $table->json('proof_paths')->nullable()->after('proof_path');
            });
        }

        if (Schema::hasTable('budget_non_pos') && !Schema::hasColumn('budget_non_pos', 'proof_paths')) {
            Schema::table('budget_non_pos', function (Blueprint $table) {
                $table->json('proof_paths')->nullable()->after('proof_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('budget_po_payments') && Schema::hasColumn('budget_po_payments', 'proof_paths')) {
            Schema::table('budget_po_payments', function (Blueprint $table) {
                $table->dropColumn('proof_paths');
            });
        }

        if (Schema::hasTable('budget_non_pos') && Schema::hasColumn('budget_non_pos', 'proof_paths')) {
            Schema::table('budget_non_pos', function (Blueprint $table) {
                $table->dropColumn('proof_paths');
            });
        }
    }
};

