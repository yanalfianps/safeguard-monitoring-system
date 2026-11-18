<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finding_sources')) {
            Schema::create('finding_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->string('description', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            $initialSources = [
                'Audit GIMS',
                'Audit EBTKE',
                'ADB Mission',
                'Audit PMC',
                'GRM',
                'Hasil Observasi',
                'Safety Inspection',
                'Kondisi Tidak Aman (Unsafe Condition)',
                'Tindakan Tidak Aman (Unsafe Action)',
                'Housekeeping & 5R',
                'APD & Peralatan Keselamatan',
                'Lingkungan & Limbah B3',
                'Peralatan & Utilitas',
                'Keamanan (Security)',
            ];

            foreach ($initialSources as $name) {
                DB::table('finding_sources')->insertOrIgnore([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!Schema::hasTable('issue_documentations')) {
            Schema::create('issue_documentations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('issue_follow_up_id')->constrained('issue_follow_ups')->cascadeOnDelete();
                $table->string('file_name');
                $table->string('original_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('mime_type', 100)->nullable();
                $table->string('uploaded_by', 150)->nullable();
                $table->string('caption', 255)->nullable();
                $table->timestamps();

                $table->index(['issue_follow_up_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_documentations');
        Schema::dropIfExists('finding_sources');
    }
};

