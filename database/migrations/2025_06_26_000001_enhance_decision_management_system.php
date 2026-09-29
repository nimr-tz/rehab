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
        // Only attempt to alter or reference the `abstract_submissions` table if it already exists.
        if (Schema::hasTable('abstract_submissions') && Schema::hasColumn('abstract_submissions', 'status')) {
            Schema::table('abstract_submissions', function (Blueprint $table) {
                // Admin priority level for decision management
                if (!Schema::hasColumn('abstract_submissions', 'admin_priority')) {
                    $table->enum('admin_priority', ['low', 'normal', 'high', 'urgent'])
                          ->default('normal')
                          ->after('status');
                }

                // Decision tracking
                if (!Schema::hasColumn('abstract_submissions', 'decision_type')) {
                    $table->string('decision_type')->nullable()->after('admin_priority');
                }
                if (!Schema::hasColumn('abstract_submissions', 'decision_rationale')) {
                    $table->text('decision_rationale')->nullable()->after('decision_type');
                }
                if (!Schema::hasColumn('abstract_submissions', 'decision_made_at')) {
                    $table->timestamp('decision_made_at')->nullable()->after('decision_rationale');
                }
                if (!Schema::hasColumn('abstract_submissions', 'decision_made_by')) {
                    $table->unsignedBigInteger('decision_made_by')->nullable()->after('decision_made_at');
                }

                // Enhanced revision tracking
                if (!Schema::hasColumn('abstract_submissions', 'revision_round')) {
                    $table->integer('revision_round')->default(0)->after('decision_made_by');
                }
                if (!Schema::hasColumn('abstract_submissions', 'revision_requested_at')) {
                    $table->timestamp('revision_requested_at')->nullable()->after('revision_round');
                }
                if (!Schema::hasColumn('abstract_submissions', 'revision_requested_by')) {
                    $table->unsignedBigInteger('revision_requested_by')->nullable()->after('revision_requested_at');
                }
                if (!Schema::hasColumn('abstract_submissions', 'revision_feedback')) {
                    $table->text('revision_feedback')->nullable()->after('revision_requested_by');
                }
                if (!Schema::hasColumn('abstract_submissions', 'revision_deadline')) {
                    $table->date('revision_deadline')->nullable()->after('revision_feedback');
                }
                if (!Schema::hasColumn('abstract_submissions', 'revision_submitted_at')) {
                    $table->timestamp('revision_submitted_at')->nullable()->after('revision_deadline');
                }

                // Conflict resolution tracking
                if (!Schema::hasColumn('abstract_submissions', 'has_review_conflict')) {
                    $table->boolean('has_review_conflict')->default(false)->after('revision_submitted_at');
                }
                if (!Schema::hasColumn('abstract_submissions', 'conflict_severity_score')) {
                    $table->decimal('conflict_severity_score', 5, 2)->nullable()->after('has_review_conflict');
                }
                if (!Schema::hasColumn('abstract_submissions', 'conflict_analysis')) {
                    $table->json('conflict_analysis')->nullable()->after('conflict_severity_score');
                }

                // Notification preferences
                if (!Schema::hasColumn('abstract_submissions', 'notification_settings')) {
                    $table->json('notification_settings')->nullable()->after('conflict_analysis');
                }

                // Workflow metadata
                if (!Schema::hasColumn('abstract_submissions', 'workflow_metadata')) {
                    $table->json('workflow_metadata')->nullable()->after('notification_settings');
                }

                // Add foreign key constraints (only if the columns exist)
                if (Schema::hasColumn('abstract_submissions', 'decision_made_by')) {
                    $table->foreign('decision_made_by')->references('id')->on('users')->onDelete('set null');
                }
                if (Schema::hasColumn('abstract_submissions', 'revision_requested_by')) {
                    $table->foreign('revision_requested_by')->references('id')->on('users')->onDelete('set null');
                }

                // Add indexes for better query performance (skip if index exists is managed by DB)
                try {
                    $table->index(['admin_priority', 'status']);
                    $table->index(['has_review_conflict', 'status']);
                    $table->index(['revision_deadline', 'status']);
                    $table->index(['decision_made_at', 'status']);
                } catch (\Throwable $e) {
                    // Ignore index creation errors (index may already exist or DB doesn't support if-not-exists)
                }
            });
        }

        // Only create dependent tables if the base table exists; other migrations handle creation too.
        if (Schema::hasTable('abstract_submissions')) {
            if (!Schema::hasTable('decision_history')) {
                Schema::create('decision_history', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('abstract_submission_id');
                    $table->string('decision_type');
                    $table->string('previous_status')->nullable();
                    $table->string('new_status');
                    $table->text('rationale')->nullable();
                    $table->json('metadata')->nullable(); // Store additional decision context
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

            if (!Schema::hasTable('revision_workflow')) {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_workflow');
        Schema::dropIfExists('decision_history');
        
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropForeign(['decision_made_by']);
            $table->dropForeign(['revision_requested_by']);
            
            $table->dropIndex(['admin_priority', 'status']);
            $table->dropIndex(['has_review_conflict', 'status']);
            $table->dropIndex(['revision_deadline', 'status']);
            $table->dropIndex(['decision_made_at', 'status']);
            
            $table->dropColumn([
                'admin_priority',
                'decision_type',
                'decision_rationale',
                'decision_made_at',
                'decision_made_by',
                'revision_round',
                'revision_requested_at',
                'revision_requested_by',
                'revision_feedback',
                'revision_deadline',
                'revision_submitted_at',
                'has_review_conflict',
                'conflict_severity_score',
                'conflict_analysis',
                'notification_settings',
                'workflow_metadata'
            ]);
        });
    }
};