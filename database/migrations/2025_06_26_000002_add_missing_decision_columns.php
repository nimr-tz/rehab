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
        // Only attempt to alter the abstract_submissions table if it exists
        if (Schema::hasTable('abstract_submissions')) {
            Schema::table('abstract_submissions', function (Blueprint $table) {
                // Only add columns that don't already exist
                if (!Schema::hasColumn('abstract_submissions', 'revision_requested_by')) {
                    $table->unsignedBigInteger('revision_requested_by')->nullable()->after('revision_requested_at');
                    $table->foreign('revision_requested_by')->references('id')->on('users')->onDelete('set null');
                }
                
                if (!Schema::hasColumn('abstract_submissions', 'has_review_conflict')) {
                    $table->boolean('has_review_conflict')->default(false)->after('revision_deadline');
                }
                
                if (!Schema::hasColumn('abstract_submissions', 'conflict_severity_score')) {
                    $table->decimal('conflict_severity_score', 5, 2)->nullable()->after('has_review_conflict');
                }
                
                if (!Schema::hasColumn('abstract_submissions', 'conflict_analysis')) {
                    $table->json('conflict_analysis')->nullable()->after('conflict_severity_score');
                }
                
                if (!Schema::hasColumn('abstract_submissions', 'workflow_metadata')) {
                    $table->json('workflow_metadata')->nullable()->after('conflict_analysis');
                }
                
                // Note: Indexes are already created in the previous migration (2025_06_26_000001_enhance_decision_management_system)
                // No need to recreate them here to avoid duplicate key errors
            });
        }

        // Create decision history table for audit trail (only if base table exists)
        if (Schema::hasTable('abstract_submissions') && !Schema::hasTable('decision_history')) {
            Schema::create('decision_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('abstract_submission_id');
                $table->string('decision_type');
                $table->string('previous_status')->nullable();
                $table->string('new_status');
                $table->text('rationale')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('decided_by');
                $table->timestamp('decided_at');
                $table->timestamps();
                
                $table->foreign('abstract_submission_id')->references('id')->on('abstract_submissions')->onDelete('cascade');
                $table->foreign('decided_by')->references('id')->on('users')->onDelete('restrict');
                
                $table->index(['abstract_submission_id', 'decided_at']);
                $table->index(['decided_by', 'decided_at']);
                $table->index(['decision_type', 'decided_at']);
            });
        }

        // Create revision workflow table for detailed revision tracking (only if base exists)
        if (Schema::hasTable('abstract_submissions') && !Schema::hasTable('revision_workflow')) {
            Schema::create('revision_workflow', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('abstract_submission_id');
                $table->integer('revision_round');
                $table->enum('revision_type', ['minor', 'major']);
                $table->text('feedback');
                $table->date('deadline');
                $table->timestamp('requested_at');
                $table->unsignedBigInteger('requested_by');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->text('review_notes')->nullable();
                $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'needs_more_changes'])->default('pending');
                $table->timestamps();
                
                $table->foreign('abstract_submission_id')->references('id')->on('abstract_submissions')->onDelete('cascade');
                $table->foreign('requested_by')->references('id')->on('users')->onDelete('restrict');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                
                $table->index(['abstract_submission_id', 'revision_round']);
                $table->index(['status', 'deadline']);
                $table->index(['requested_at', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_workflow');
        Schema::dropIfExists('decision_history');
        
        Schema::table('abstract_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('abstract_submissions', 'revision_requested_by')) {
                $table->dropForeign(['revision_requested_by']);
            }
            
            // Note: Indexes are managed by the previous migration, so we don't drop them here
            
            $table->dropColumn([
                'revision_requested_by',
                'has_review_conflict',
                'conflict_severity_score',
                'conflict_analysis',
                'workflow_metadata'
            ]);
        });
    }
};