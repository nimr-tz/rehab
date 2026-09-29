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
            if (!Schema::hasColumn('abstract_submissions', 'revision_feedback')) {
                $table->text('revision_feedback')->nullable()->after('admin_comment');
            }
            if (!Schema::hasColumn('abstract_submissions', 'revision_file')) {
                $table->string('revision_file')->nullable()->after('revision_feedback');
            }
            if (!Schema::hasColumn('abstract_submissions', 'revision_round')) {
                $table->unsignedInteger('revision_round')->default(0)->after('revision_file');
            }
            if (!Schema::hasColumn('abstract_submissions', 'revision_requested_at')) {
                $table->timestamp('revision_requested_at')->nullable()->after('revision_round');
            }
            if (!Schema::hasColumn('abstract_submissions', 'revision_submitted_at')) {
                $table->timestamp('revision_submitted_at')->nullable()->after('revision_requested_at');
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
                'revision_feedback',
                'revision_file',
                'revision_round',
                'revision_requested_at',
                'revision_submitted_at',
            ]);
        });
    }
};
