<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations safely without dropping or wiping any existing tables/data.
     */
    public function up(): void
    {
        // 1. Enhance existing contractors table safely
        if (Schema::hasTable('contractors')) {
            Schema::table('contractors', function (Blueprint $table) {
                if (!Schema::hasColumn('contractors', 'slug')) {
                    $table->string('slug', 100)->nullable()->unique()->after('code');
                }
                if (!Schema::hasColumn('contractors', 'display_name')) {
                    $table->string('display_name', 255)->nullable()->after('name');
                }
                if (!Schema::hasColumn('contractors', 'is_kpi_monitored')) {
                    $table->boolean('is_kpi_monitored')->default(true)->after('is_active');
                }
                if (!Schema::hasColumn('contractors', 'structure_type')) {
                    $table->string('structure_type', 50)->default('leading_lagging')->after('is_kpi_monitored');
                }
                if (!Schema::hasColumn('contractors', 'perspective_weights')) {
                    $table->json('perspective_weights')->nullable()->after('structure_type');
                }
                if (!Schema::hasColumn('contractors', 'scorecard_formula_verified')) {
                    $table->boolean('scorecard_formula_verified')->default(false)->after('perspective_weights');
                }
                if (!Schema::hasColumn('contractors', 'scope_locations')) {
                    $table->json('scope_locations')->nullable()->after('scorecard_formula_verified');
                }
                if (!Schema::hasColumn('contractors', 'effective_from')) {
                    $table->date('effective_from')->nullable()->after('scope_locations');
                }
                if (!Schema::hasColumn('contractors', 'effective_until')) {
                    $table->date('effective_until')->nullable()->after('effective_from');
                }
                if (!Schema::hasColumn('contractors', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('effective_until');
                }
            });
        }

        // 2. Contractor Working Calendars
        if (!Schema::hasTable('contractor_calendars')) {
            Schema::create('contractor_calendars', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->string('name', 150);
                $table->string('calendar_mode', 50)->default('mon_fri_overtime');
                $table->json('working_days'); // ISO 1=Mon .. 7=Sun
                $table->json('overtime_days')->nullable();
                $table->boolean('count_overtime_only_when_logged')->default(true);
                $table->unsignedTinyInteger('shifts_per_day')->default(1);
                $table->json('shift_names')->nullable();
                $table->json('holidays')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['contractor_id', 'is_active'], 'idx_ccal_contractor_active');
            });
        }

        // 3. Lightweight Contractor Operational Resources (Personnel, Supervisor, Work Area, Vehicle, Equipment, Security Post)
        if (!Schema::hasTable('contractor_resources')) {
            Schema::create('contractor_resources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->string('resource_type', 50); // personnel, supervisor, work_area, vehicle, equipment, security_post
                $table->string('code', 80)->nullable();
                $table->string('name', 255);
                $table->string('category', 100)->nullable();
                $table->decimal('quantity', 12, 2)->default(1);
                $table->json('shift_requirements')->nullable(); // e.g. {"Pagi": 2, "Sore": 1, "Malam": 2}
                $table->json('metadata')->nullable();
                $table->date('effective_from');
                $table->date('effective_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['contractor_id', 'resource_type', 'is_active'], 'idx_cres_type_active');
                $table->index(['contractor_id', 'effective_from', 'effective_until'], 'idx_cres_eff_dates');
            });
        }

        // 4. Resource Change History (Traceability for Dynamic Target Adjustments)
        if (!Schema::hasTable('contractor_resource_histories')) {
            Schema::create('contractor_resource_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contractor_resource_id')->constrained('contractor_resources')->cascadeOnDelete();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->string('action', 40); // CREATED, UPDATED, DEACTIVATED, IMPORTED
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->date('effective_date')->nullable();
                $table->text('reason')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['contractor_id', 'created_at'], 'idx_crhist_contractor_date');
            });
        }

        // 5. Contractor Work Logs (PJSE Weekend Overtime, JOTRE Sunday Schedule, TDP 3-Shift Post Coverage)
        if (!Schema::hasTable('contractor_work_logs')) {
            Schema::create('contractor_work_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->date('work_date');
                $table->string('log_type', 50); // overtime_work, sunday_schedule, shift_coverage
                $table->foreignId('resource_id')->nullable()->constrained('contractor_resources')->nullOnDelete();
                $table->string('shift_name', 30)->nullable(); // Pagi, Sore, Malam
                $table->decimal('required_count', 12, 2)->nullable();
                $table->decimal('actual_count', 12, 2)->nullable();
                $table->boolean('is_applicable_for_kpi')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['contractor_id', 'work_date', 'log_type'], 'idx_cwlog_date_type');
            });
        }

        // 6. Master KPI Definitions per Contractor
        if (!Schema::hasTable('hsse_kpis')) {
            Schema::create('hsse_kpis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->string('code', 60);
                $table->string('display_code', 60)->nullable();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('official_target_text', 255)->nullable();
                $table->string('perspective', 120); // Leading Indicator, Lagging Indicator, Goal Oriented, Operasional, etc.
                $table->string('sub_group', 150)->nullable();
                $table->string('indicator_type', 50)->default('Leading'); // Leading, Lagging, Compliance, Response, Service Outcome
                $table->string('unit_of_measure', 80)->nullable();
                $table->decimal('target_value', 14, 4)->nullable();
                $table->string('target_operator', 30)->default('gte'); // zero, gte, lte, lt, eq, percentage, index, record_only
                $table->string('target_frequency', 50)->default('monthly'); // daily, weekly, biweekly, monthly, quarterly, biannually, annually, per_project, before_activity, before_mobilization, before_site_access, pre_use, event_based, as_scheduled
                $table->string('evaluation_period', 30)->default('monthly'); // daily, weekly, monthly, quarterly, biannually, annually, per_project
                $table->string('input_method', 30)->default('quantity'); // checklist, quantity, percentage, index, event
                $table->string('input_frequency', 30)->default('per_activity'); // daily, per_activity, per_report, per_object, per_period
                $table->string('calculation_method', 50)->default('target_quantity');
                $table->string('configuration_status', 30)->default('active'); // active, needs_configuration
                $table->text('configuration_note')->nullable();
                $table->string('dynamic_target_source', 50)->nullable(); // null, working_days, personnel, supervisor, work_area, vehicle, equipment, security_post
                $table->decimal('dynamic_target_multiplier', 12, 4)->nullable();
                $table->string('rounding_policy', 30)->default('none'); // none, ceil, round, floor
                $table->string('shared_metric_key', 80)->nullable();
                $table->boolean('is_derived_view')->default(false);
                $table->boolean('allow_carry_forward')->default(false);
                $table->boolean('allow_credit_forward')->default(false);
                $table->string('credit_validity_type', 40)->default('next_period'); // next_period, n_periods, end_of_year, end_of_project, unlimited
                $table->unsignedInteger('credit_validity_value')->nullable();
                $table->decimal('perspective_weight', 6, 2)->nullable();
                $table->decimal('weight', 6, 2)->nullable();
                $table->json('threshold_rules')->nullable();
                $table->boolean('requires_evidence')->default(false);
                $table->date('project_start_date')->nullable();
                $table->date('project_end_date')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('legacy_hsse_plan_id')->nullable()->index('idx_hkpi_legacy_id');
                $table->timestamps();

                $table->unique(['contractor_id', 'code'], 'uq_hkpi_contractor_code');
                $table->index(['contractor_id', 'is_active', 'sort_order'], 'idx_hkpi_contractor_active');
                $table->index(['shared_metric_key'], 'idx_hkpi_shared_metric');
            });
        }

        // 7. KPI Period Evaluations & Snapshots (Model A Immutable History)
        if (!Schema::hasTable('hsse_kpi_periods')) {
            Schema::create('hsse_kpi_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hsse_kpi_id')->constrained('hsse_kpis')->restrictOnDelete();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->string('period_type', 30); // monthly, quarterly, biannually, annually, per_project, weekly
                $table->string('period_key', 30); // e.g. 2026-01, 2026-Q1, 2026-H1, 2026, PROJECT
                $table->string('scope_key', 80)->default('GLOBAL');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month')->nullable();
                $table->unsignedTinyInteger('quarter')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('normal_target', 14, 4)->nullable();
                $table->decimal('opening_debt', 14, 4)->default(0);
                $table->decimal('opening_credit', 14, 4)->default(0);
                $table->decimal('effective_target', 14, 4)->nullable();
                $table->decimal('actual_total', 14, 4)->default(0);
                $table->decimal('eligible_realization', 14, 4)->default(0);
                $table->decimal('numerator_total', 14, 4)->nullable();
                $table->decimal('denominator_total', 14, 4)->nullable();
                $table->decimal('actual_percentage', 10, 2)->nullable(); // Can exceed 100%
                $table->decimal('achievement_percentage', 6, 2)->nullable(); // Max 100%
                $table->decimal('debt_repaid_in_period', 14, 4)->default(0);
                $table->decimal('credit_consumed_in_period', 14, 4)->default(0);
                $table->decimal('closing_debt', 14, 4)->default(0);
                $table->decimal('closing_credit', 14, 4)->default(0);
                $table->string('status', 40)->default('Belum Diinput');
                $table->string('not_applicable_reason', 255)->nullable();
                $table->unsignedSmallInteger('working_days_count')->nullable();
                $table->json('resource_snapshot')->nullable();
                $table->json('calculation_trace')->nullable();
                $table->boolean('is_locked')->default(false);
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('lock_note')->nullable();
                $table->timestamps();

                $table->unique(['hsse_kpi_id', 'period_key', 'scope_key'], 'uq_hkperiod_kpi_key_scope');
                $table->index(['contractor_id', 'year', 'month'], 'idx_hkperiod_ctr_ym');
                $table->index(['status'], 'idx_hkperiod_status');
            });
        }

        // 8. KPI Realization Entries
        if (!Schema::hasTable('hsse_kpi_entries')) {
            Schema::create('hsse_kpi_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hsse_kpi_id')->constrained('hsse_kpis')->restrictOnDelete();
                $table->foreignId('hsse_kpi_period_id')->nullable()->constrained('hsse_kpi_periods')->nullOnDelete();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->date('entry_date');
                $table->string('scope_key', 80)->default('GLOBAL');
                $table->string('location', 150)->nullable();
                $table->string('entry_type', 30)->default('quantity'); // checklist, quantity, percentage, index, event, not_applicable
                $table->string('checklist_status', 30)->nullable(); // terlaksana, belum_terlaksana, tidak_berlaku
                $table->decimal('quantity', 14, 4)->nullable();
                $table->decimal('numerator', 14, 4)->nullable();
                $table->decimal('denominator', 14, 4)->nullable();
                $table->decimal('percentage_value', 10, 2)->nullable();
                $table->decimal('index_value', 10, 2)->nullable();
                $table->decimal('index_scale', 10, 2)->nullable();
                $table->string('unit', 80)->nullable();
                $table->string('status_execution', 40)->default('Terlaksana');
                $table->boolean('is_eligible')->default(true);
                $table->string('source_reference', 120)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_historical_correction')->default(false);
                $table->text('correction_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['hsse_kpi_id', 'source_reference'], 'uq_hkentry_kpi_srcref');
                $table->index(['hsse_kpi_id', 'entry_date'], 'idx_hkentry_kpi_date');
                $table->index(['contractor_id', 'entry_date'], 'idx_hkentry_ctr_date');
            });
        }

        // 9. Evidence Attachments (Multi-upload per Entry / KPI)
        if (!Schema::hasTable('hsse_kpi_attachments')) {
            Schema::create('hsse_kpi_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hsse_kpi_id')->constrained('hsse_kpis')->restrictOnDelete();
                $table->foreignId('hsse_kpi_entry_id')->nullable()->constrained('hsse_kpi_entries')->cascadeOnDelete();
                $table->foreignId('hsse_kpi_period_id')->nullable()->constrained('hsse_kpi_periods')->nullOnDelete();
                $table->string('original_name', 255);
                $table->string('file_path', 500);
                $table->string('disk', 30)->default('public');
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['hsse_kpi_id', 'hsse_kpi_entry_id'], 'idx_hkatt_kpi_entry');
            });
        }

        // 10. Evaluation & Follow-Up Notes by HSE Owner
        if (!Schema::hasTable('hsse_kpi_notes')) {
            Schema::create('hsse_kpi_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hsse_kpi_id')->constrained('hsse_kpis')->restrictOnDelete();
                $table->foreignId('hsse_kpi_period_id')->nullable()->constrained('hsse_kpi_periods')->nullOnDelete();
                $table->text('note');
                $table->string('note_type', 40)->default('evaluasi'); // evaluasi, tindak_lanjut, koreksi
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('author_name', 150);
                $table->timestamps();

                $table->index(['hsse_kpi_id', 'created_at'], 'idx_hknote_kpi_date');
            });
        }

        // 11. Carry Forward (Debt) & Credit Carry Forward Ledger
        if (!Schema::hasTable('hsse_kpi_balance_ledgers')) {
            Schema::create('hsse_kpi_balance_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hsse_kpi_id')->constrained('hsse_kpis')->restrictOnDelete();
                $table->foreignId('contractor_id')->constrained('contractors')->restrictOnDelete();
                $table->string('scope_key', 80)->default('GLOBAL');
                $table->foreignId('source_period_id')->constrained('hsse_kpi_periods')->cascadeOnDelete();
                $table->string('source_period_key', 30);
                $table->string('ledger_type', 20); // debt, credit
                $table->decimal('initial_quantity', 14, 4);
                $table->decimal('used_quantity', 14, 4)->default(0);
                $table->decimal('remaining_quantity', 14, 4);
                $table->decimal('expired_quantity', 14, 4)->default(0);
                $table->date('created_date');
                $table->date('expires_at')->nullable();
                $table->string('status', 30)->default('open'); // open, partially_used, settled, consumed, expired
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['hsse_kpi_id', 'scope_key', 'ledger_type', 'status'], 'idx_hkledger_lookup');
                $table->index(['contractor_id', 'ledger_type', 'status'], 'idx_hkledger_ctr_status');
            });
        }

        // 12. FIFO Ledger Allocations (Audit Trail of Debt Repayments, Credit Consumptions, & Expirations)
        if (!Schema::hasTable('hsse_kpi_ledger_allocations')) {
            Schema::create('hsse_kpi_ledger_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ledger_id')->constrained('hsse_kpi_balance_ledgers')->cascadeOnDelete();
                $table->foreignId('hsse_kpi_id')->constrained('hsse_kpis')->restrictOnDelete();
                $table->foreignId('allocated_in_period_id')->constrained('hsse_kpi_periods')->cascadeOnDelete();
                $table->string('allocated_in_period_key', 30);
                $table->foreignId('source_entry_id')->nullable()->constrained('hsse_kpi_entries')->nullOnDelete();
                $table->string('allocation_type', 30); // debt_repayment, credit_consumption, credit_expiration
                $table->decimal('quantity', 14, 4);
                $table->date('allocated_at');
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->index(['hsse_kpi_id', 'allocated_in_period_id'], 'idx_hkalloc_kpi_period');
            });
        }
    }

    /**
     * Reverse the migrations cleanly (for rollback testing only).
     */
    public function down(): void
    {
        Schema::dropIfExists('hsse_kpi_ledger_allocations');
        Schema::dropIfExists('hsse_kpi_balance_ledgers');
        Schema::dropIfExists('hsse_kpi_notes');
        Schema::dropIfExists('hsse_kpi_attachments');
        Schema::dropIfExists('hsse_kpi_entries');
        Schema::dropIfExists('hsse_kpi_periods');
        Schema::dropIfExists('hsse_kpis');
        Schema::dropIfExists('contractor_work_logs');
        Schema::dropIfExists('contractor_resource_histories');
        Schema::dropIfExists('contractor_resources');
        Schema::dropIfExists('contractor_calendars');

        if (Schema::hasTable('contractors')) {
            Schema::table('contractors', function (Blueprint $table) {
                $cols = [
                    'slug',
                    'display_name',
                    'is_kpi_monitored',
                    'structure_type',
                    'perspective_weights',
                    'scorecard_formula_verified',
                    'scope_locations',
                    'effective_from',
                    'effective_until',
                    'sort_order',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('contractors', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

