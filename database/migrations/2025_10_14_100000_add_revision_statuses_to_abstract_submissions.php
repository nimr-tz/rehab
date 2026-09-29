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
        // SQLite doesn't support modifying enums, so we need to recreate the column
        // For SQLite: we'll use a raw query approach
        // For MySQL/PostgreSQL: use change() method
        
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite workaround: No direct enum modification support
            // We need to handle this differently or document manual intervention
            // For now, we'll skip the constraint and rely on application-level validation
            
            // Note: If you're using SQLite for testing, the status constraint may need to be
            // manually adjusted or you can use DB::statement() to recreate the table
            
        } else {
            // For MySQL/PostgreSQL
            Schema::table('abstract_submissions', function (Blueprint $table) {
                $table->enum('status', [
                    'draft',
                    'submitted', 
                    'under_review',
                    'accepted',
                    'rejected',
                    'minor_revision_required',
                    'major_revision_required',
                    'minor_revision_submitted',
                    'major_revision_submitted',
                    'conflict_queue',
                    'revision_review',
                    // New revision workflow statuses
                    'revision_requested',
                    'revision_in_progress',
                    'revision_submitted',
                    'revision_under_review',
                    'revision_approved',
                    'revision_final_review',
                    'revision_rejected',
                    // Also add committee_review if missing
                    'committee_review',
                    'ready_for_decision',
                ])->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver !== 'sqlite') {
            Schema::table('abstract_submissions', function (Blueprint $table) {
                $table->enum('status', [
                    'draft', 'submitted', 'under_review', 'accepted', 'rejected',
                    'minor_revision_required', 'major_revision_required',
                    'minor_revision_submitted', 'major_revision_submitted',
                    'conflict_queue', 'revision_review'
                ])->change();
            });
        }
    }
};

