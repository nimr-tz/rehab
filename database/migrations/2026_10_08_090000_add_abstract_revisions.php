<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One round of revisions. When the two reviewers do not both accept, the
 * scientific committee may ask the author to revise. The text before the
 * revision is kept so reviewers and the committee can compare the versions,
 * and the reviewers who did not accept review the revised version in round 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstracts', function (Blueprint $table) {
            $table->boolean('accepted_automatically')->default(false)->after('decided_at');
            $table->timestamp('revision_requested_at')->nullable()->after('accepted_automatically');
            $table->date('revision_due_on')->nullable()->after('revision_requested_at');
            $table->text('revision_note')->nullable()->after('revision_due_on');
            $table->json('original_version')->nullable()->after('revision_note'); // the text before the revision
            $table->text('revision_response')->nullable()->after('original_version'); // the author's response to the reviewers
            $table->timestamp('revised_at')->nullable()->after('revision_response');
        });

        // A reviewer may review the same abstract once per round. The new index
        // starts with abstract_id, so MySQL keeps an index for that foreign key
        // when the old one is dropped.
        Schema::table('review_assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('round')->default(1)->after('reviewer_id');
            $table->unique(['abstract_id', 'reviewer_id', 'round']);
        });

        Schema::table('review_assignments', function (Blueprint $table) {
            $table->dropUnique(['abstract_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('review_assignments', function (Blueprint $table) {
            $table->unique(['abstract_id', 'reviewer_id']);
        });

        Schema::table('review_assignments', function (Blueprint $table) {
            $table->dropUnique(['abstract_id', 'reviewer_id', 'round']);
            $table->dropColumn('round');
        });

        Schema::table('abstracts', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_automatically', 'revision_requested_at', 'revision_due_on', 'revision_note',
                'original_version', 'revision_response', 'revised_at',
            ]);
        });
    }
};
