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
        // 1. Tabel WBS / Program Anggaran
        Schema::create('budget_wbs', function (Blueprint $table) {
            $table->id();
            $table->string('wbs_number', 100)->unique();
            $table->string('wbs_name', 255);
            $table->text('description')->nullable();
            $table->decimal('initial_budget', 16, 2)->default(0);
            $table->decimal('current_budget', 16, 2)->default(0);
            $table->integer('fiscal_year')->default(2026)->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Tabel Pengadaan / Purchase Order (PO)
        Schema::create('budget_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->string('po_number', 100)->unique();
            $table->string('program_name', 255);
            $table->string('vendor', 255)->nullable();
            $table->decimal('po_value', 16, 2)->default(0);
            $table->decimal('commitment_value', 16, 2)->default(0);
            $table->date('po_date')->index();
            $table->string('status', 50)->default('Aktif');
            $table->text('description')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Tabel Realisasi PO (Pembayaran Bertahap PO)
        Schema::create('budget_po_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_po_id')->constrained('budget_pos')->cascadeOnDelete();
            $table->foreignId('budget_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->string('payment_number', 100)->unique();
            $table->date('payment_date')->index();
            $table->decimal('amount', 16, 2);
            $table->string('reference_number', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Tabel Realisasi Non-PO
        Schema::create('budget_non_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->string('transaction_number', 100)->unique();
            $table->string('program_name', 255);
            $table->date('transaction_date')->index();
            $table->decimal('amount', 16, 2);
            $table->string('recipient_vendor', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Tabel Pengalihan Anggaran Antar-WBS
        Schema::create('budget_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 100)->unique();
            $table->foreignId('source_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->foreignId('destination_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->decimal('amount', 16, 2);
            $table->date('transfer_date')->index();
            $table->text('reason');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('Disetujui');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Tabel Penyesuaian Anggaran WBS
        Schema::create('budget_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 100)->unique();
            $table->foreignId('budget_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->string('adjustment_type', 50)->default('PENYESUAIAN');
            $table->decimal('amount', 16, 2);
            $table->decimal('old_budget', 16, 2);
            $table->decimal('new_budget', 16, 2);
            $table->date('adjustment_date')->index();
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 7. Tabel Riwayat Transaksi Keuangan Keseluruhan
        Schema::create('budget_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 100)->unique();
            $table->string('transaction_type', 50)->index();
            $table->string('transaction_type_label', 100);
            $table->date('transaction_date')->index();
            $table->foreignId('budget_wbs_id')->constrained('budget_wbs')->cascadeOnDelete();
            $table->string('program_name', 255)->nullable();
            $table->string('reference_number', 100)->nullable()->index();
            $table->decimal('amount', 16, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('related_model_type', 100)->nullable();
            $table->unsignedBigInteger('related_model_id')->nullable();
            $table->timestamps();

            $table->index(['related_model_type', 'related_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');
        Schema::dropIfExists('budget_adjustments');
        Schema::dropIfExists('budget_transfers');
        Schema::dropIfExists('budget_non_pos');
        Schema::dropIfExists('budget_po_payments');
        Schema::dropIfExists('budget_pos');
        Schema::dropIfExists('budget_wbs');
    }
};

