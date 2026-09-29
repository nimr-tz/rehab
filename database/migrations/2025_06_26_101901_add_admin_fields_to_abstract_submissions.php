<?php
// Create migration: php artisan make:migration add_admin_fields_to_abstract_submissions

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
            // Check and add columns only if they don't exist
            if (!Schema::hasColumn('abstract_submissions', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'reviewer_id')) {
                $table->unsignedBigInteger('reviewer_id')->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'admin_comment')) {
                $table->text('admin_comment')->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'status_changed_by')) {
                $table->unsignedBigInteger('status_changed_by')->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
            
            // NEW: Add second reviewer and scoring fields for the dual review system
            if (!Schema::hasColumn('abstract_submissions', 'reviewer_2_id')) {
                $table->unsignedBigInteger('reviewer_2_id')->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'reviewer_1_score')) {
                $table->decimal('reviewer_1_score', 3, 1)->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'reviewer_2_score')) {
                $table->decimal('reviewer_2_score', 3, 1)->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'average_score')) {
                $table->decimal('average_score', 3, 1)->nullable();
            }
            
            if (!Schema::hasColumn('abstract_submissions', 'review_completed_at')) {
                $table->timestamp('review_completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'reviewer_id',
                'reviewer_2_id',
                'reviewer_1_score',
                'reviewer_2_score',
                'average_score',
                'admin_comment', 
                'status_changed_at',
                'status_changed_by',
                'assigned_at',
                'submitted_at',
                'review_completed_at'
            ]);
        });
    }
};
