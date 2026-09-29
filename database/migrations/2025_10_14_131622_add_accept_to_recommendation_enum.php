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
        // For SQLite, we need to recreate the table with updated enum values
        // First, check if we're using SQLite
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: Recreate table with updated enum
            DB::statement('
                CREATE TABLE abstract_reviews_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    abstract_submission_id INTEGER NOT NULL,
                    reviewer_id INTEGER NOT NULL,
                    reviewer_number INTEGER NOT NULL,
                    review_round INTEGER DEFAULT 1,
                    technical_quality REAL,
                    novelty REAL,
                    relevance REAL,
                    clarity REAL,
                    score REAL,
                    comments TEXT,
                    recommendation TEXT CHECK(recommendation IN ("accept_oral", "accept_poster", "accept", "minor_revisions", "major_revisions", "reject")),
                    completed_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME,
                    submitted_at DATETIME,
                    status TEXT DEFAULT "draft",
                    is_revision_review INTEGER DEFAULT 0,
                    revision_notes TEXT,
                    improvement_from_previous TEXT,
                    previous_round_id INTEGER,
                    FOREIGN KEY (abstract_submission_id) REFERENCES abstract_submissions(id) ON DELETE CASCADE,
                    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (previous_round_id) REFERENCES abstract_reviews(id) ON DELETE SET NULL,
                    UNIQUE(abstract_submission_id, reviewer_id, review_round)
                )
            ');
            
            // Copy data from old table
            DB::statement('
                INSERT INTO abstract_reviews_new 
                SELECT * FROM abstract_reviews
            ');
            
            // Drop old table
            DB::statement('DROP TABLE abstract_reviews');
            
            // Rename new table
            DB::statement('ALTER TABLE abstract_reviews_new RENAME TO abstract_reviews');
        } else {
            // For MySQL/PostgreSQL, use ALTER TABLE
            DB::statement("
                ALTER TABLE abstract_reviews 
                MODIFY COLUMN recommendation ENUM('accept_oral', 'accept_poster', 'accept', 'minor_revisions', 'major_revisions', 'reject')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse not needed for this migration
    }
};
