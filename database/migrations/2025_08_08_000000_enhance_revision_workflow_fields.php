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
        Schema::table('abstract_submissions', function (Blueprint $table) {
            // Add new revision workflow fields
            $table->string('revision_workflow_step')->nullable()->after('revision_round');
            $table->timestamp('revision_approved_at')->nullable()->after('revision_submitted_at');
            $table->unsignedBigInteger('revision_approved_by')->nullable()->after('revision_approved_at');
            $table->text('revision_admin_notes')->nullable()->after('revision_approved_by');
            
            // Add foreign key for revision approved by
            $table->foreign('revision_approved_by')->references('id')->on('users')->onDelete('set null');
        });

        // Update existing revision statuses to new standardized format
        DB::statement("
            UPDATE abstract_submissions 
            SET status = CASE 
                WHEN status IN ('revision', 'minor_revision', 'major_revision') THEN 'revision_requested'
                WHEN status = 'revision_submitted' THEN 'revision_submitted'
                ELSE status
            END
            WHERE status IN ('revision', 'minor_revision', 'major_revision', 'revision_submitted')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropForeign(['revision_approved_by']);
            $table->dropColumn([
                'revision_workflow_step',
                'revision_approved_at',
                'revision_approved_by',
                'revision_admin_notes'
            ]);
        });

        // Revert status changes
        DB::statement("
            UPDATE abstract_submissions 
            SET status = 'revision'
            WHERE status = 'revision_requested'
        ");
    }
};
