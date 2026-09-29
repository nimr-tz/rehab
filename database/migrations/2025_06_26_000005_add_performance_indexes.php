<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add performance indexes for abstract_submissions (only if table exists)
        if (Schema::hasTable('abstract_submissions')) {
            // Check for columns existence first
            $hasStatusColumn = Schema::hasColumn('abstract_submissions', 'status');
            $hasAdminPriorityColumn = Schema::hasColumn('abstract_submissions', 'admin_priority');
            $hasUpdatedAtColumn = Schema::hasColumn('abstract_submissions', 'updated_at');
            $hasCreatedAtColumn = Schema::hasColumn('abstract_submissions', 'created_at');
            $hasDecisionMadeAtColumn = Schema::hasColumn('abstract_submissions', 'decision_made_at');
            $hasDecisionMadeByColumn = Schema::hasColumn('abstract_submissions', 'decision_made_by');
            $hasRevisionRoundColumn = Schema::hasColumn('abstract_submissions', 'revision_round');
            $hasSubthemeColumn = Schema::hasColumn('abstract_submissions', 'subtheme');
            $hasCategoryColumn = Schema::hasColumn('abstract_submissions', 'category');
            
            Schema::table('abstract_submissions', function (Blueprint $table) use (
                $hasStatusColumn, $hasAdminPriorityColumn, $hasUpdatedAtColumn,
                $hasCreatedAtColumn, $hasDecisionMadeAtColumn, $hasDecisionMadeByColumn,
                $hasRevisionRoundColumn, $hasSubthemeColumn, $hasCategoryColumn
            ) {
                // Core decision management indexes
                if ($hasStatusColumn && $hasAdminPriorityColumn) {
                    try {
                        $table->index(['status', 'admin_priority'], 'idx_status_priority');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                if ($hasStatusColumn && $hasUpdatedAtColumn) {
                    try {
                        $table->index(['status', 'updated_at'], 'idx_status_updated');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                if ($hasCreatedAtColumn && $hasStatusColumn) {
                    try {
                        $table->index(['created_at', 'status'], 'idx_created_status');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                // Decision tracking indexes
                if ($hasDecisionMadeAtColumn) {
                    try {
                        $table->index(['decision_made_at'], 'idx_decision_date');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                if ($hasDecisionMadeByColumn) {
                    try {
                        $table->index(['decision_made_by'], 'idx_decision_maker');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                // Revision workflow indexes
                // Note: Index on ['revision_deadline', 'status'] already created in 2025_06_26_000001 migration
                // Skipped to avoid duplicate index error
                
                if ($hasRevisionRoundColumn) {
                    try {
                        $table->index(['revision_round'], 'idx_revision_round');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                // Note: Index on ['has_review_conflict', 'status'] already created in 2025_06_26_000001 migration
                // Skipped to avoid duplicate index error
                
                // Subtheme and category for similar abstracts
                if ($hasSubthemeColumn && $hasStatusColumn) {
                    try {
                        $table->index(['subtheme', 'status'], 'idx_subtheme_status');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
                
                if ($hasCategoryColumn && $hasCreatedAtColumn) {
                    try {
                        $table->index(['category', 'created_at'], 'idx_category_created');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
            });
        }
        
        // Add performance indexes for abstract_reviews (only if table exists)
        if (Schema::hasTable('abstract_reviews')) {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                // Core review lookup indexes
                try {
                    $table->index(['abstract_submission_id', 'status'], 'idx_submission_status');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                try {
                    $table->index(['reviewer_id', 'status'], 'idx_reviewer_status');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                // Score and recommendation analysis
                try {
                    $table->index(['score', 'recommendation'], 'idx_score_recommendation');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                try {
                    $table->index(['recommendation', 'created_at'], 'idx_recommendation_created');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                // Completion tracking
                try {
                    $table->index(['completed_at'], 'idx_completed_at');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                try {
                    $table->index(['submitted_at'], 'idx_submitted_at');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                // Review round tracking
                try {
                    $table->index(['review_round'], 'idx_review_round');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
            });
        }
        
        // Add performance indexes for users (reviewers)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Reviewer performance tracking
                try {
                    $table->index(['total_reviews_completed', 'reviewer_consistency_score'], 'idx_reviewer_stats');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
                
                try {
                    $table->index(['review_experience_years', 'reviewer_consistency_score'], 'idx_experience_consistency');
                } catch (\Exception $e) {
                    // Index might already exist, skip
                }
            });
        }
        
        // Composite indexes (only if table exists)
        if (Schema::hasTable('abstract_submissions')) {
            try {
                DB::statement('CREATE INDEX idx_complex_decision_queue ON abstract_submissions (status, created_at, admin_priority)');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            try {
                DB::statement('CREATE INDEX idx_urgent_decisions ON abstract_submissions (updated_at, status)');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }
        
        // Add materialized view-style table for decision analytics (optional optimization)
        if (!Schema::hasTable('decision_analytics_cache')) {
            Schema::create('decision_analytics_cache', function (Blueprint $table) {
                $table->id();
                $table->date('cache_date');
                $table->integer('total_pending');
                $table->integer('conflicts_detected');
                $table->integer('urgent_decisions');
                $table->integer('auto_routed_today');
                $table->decimal('average_decision_time_hours', 8, 2);
                $table->json('detailed_stats');
                $table->timestamps();
                
                $table->unique('cache_date');
                $table->index('cache_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_analytics_cache');
        
        // Drop complex indexes
        try {
            DB::statement('DROP INDEX idx_complex_decision_queue ON abstract_submissions');
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
        
        try {
            DB::statement('DROP INDEX idx_urgent_decisions ON abstract_submissions');
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
        
        // Drop abstract_submissions indexes (only ones created by this migration)
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_status_priority');
            $this->dropIndexIfExists($table, 'idx_status_updated');
            $this->dropIndexIfExists($table, 'idx_created_status');
            $this->dropIndexIfExists($table, 'idx_decision_date');
            $this->dropIndexIfExists($table, 'idx_decision_maker');
            // idx_revision_deadline not dropped - created in previous migration
            $this->dropIndexIfExists($table, 'idx_revision_round');
            // idx_conflict_flag not dropped - created in previous migration
            $this->dropIndexIfExists($table, 'idx_subtheme_status');
            $this->dropIndexIfExists($table, 'idx_category_created');
        });
        
        // Drop abstract_reviews indexes (only if table exists)
        if (Schema::hasTable('abstract_reviews')) {
            Schema::table('abstract_reviews', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'idx_submission_status');
                $this->dropIndexIfExists($table, 'idx_reviewer_status');
                $this->dropIndexIfExists($table, 'idx_score_recommendation');
                $this->dropIndexIfExists($table, 'idx_recommendation_created');
                $this->dropIndexIfExists($table, 'idx_completed_at');
                $this->dropIndexIfExists($table, 'idx_submitted_at');
                $this->dropIndexIfExists($table, 'idx_review_round');
            });
        }
        
        // Drop users indexes
        Schema::table('users', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_reviewer_stats');
            $this->dropIndexIfExists($table, 'idx_experience_consistency');
        });
    }
    
    private function dropIndexIfExists(Blueprint $table, string $index): void
    {
        try {
            $table->dropIndex($index);
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
    }
};