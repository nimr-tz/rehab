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
        Schema::table('abstract_submissions', function (Blueprint $table) {
            // Update status enum to include new workflow statuses
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'accepted', 'rejected',
                'minor_revision_required', 'major_revision_required',
                'minor_revision_submitted', 'major_revision_submitted',
                'conflict_queue', 'revision_review',
                // New revision workflow statuses
                'revision_requested', 'revision_in_progress', 'revision_submitted',
                'revision_under_review', 'revision_approved', 'revision_final_review',
                'revision_rejected', 'committee_review', 'ready_for_decision'
            ])->change();
            
            // Add new revision tracking fields (only if they don't exist)
            if (!Schema::hasColumn('abstract_submissions', 'revision_deadline')) {
                $table->date('revision_deadline')->nullable()->after('revision_round');
            }
            if (!Schema::hasColumn('abstract_submissions', 'revision_submitted_at')) {
                $table->timestamp('revision_submitted_at')->nullable()->after('revision_deadline');
            }
            if (!Schema::hasColumn('abstract_submissions', 'original_submission_id')) {
                $table->bigInteger('original_submission_id')->nullable()->after('revision_submitted_at');
            }
            
            // Add automated decision tracking
            if (!Schema::hasColumn('abstract_submissions', 'automated_decision')) {
                $table->boolean('automated_decision')->default(false)->after('original_submission_id');
            }
            if (!Schema::hasColumn('abstract_submissions', 'automated_decision_at')) {
                $table->timestamp('automated_decision_at')->nullable()->after('automated_decision');
            }
            if (!Schema::hasColumn('abstract_submissions', 'automated_decision_reason')) {
                $table->text('automated_decision_reason')->nullable()->after('automated_decision_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            // Revert status enum to original values
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'accepted', 'rejected',
                'minor_revision', 'major_revision', 'scheduled'
            ])->change();
            
            // Remove revision tracking fields
            $table->dropColumn([
                'revision_round',
                'revision_deadline', 
                'revision_submitted_at',
                'original_submission_id',
                'automated_decision',
                'automated_decision_at',
                'automated_decision_reason'
            ]);
        });
    }
};