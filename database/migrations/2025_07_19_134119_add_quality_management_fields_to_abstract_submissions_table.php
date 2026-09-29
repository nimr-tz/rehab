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
            // Quality management fields
            $table->boolean('quality_flag')->default(false)->after('committee_selected');
            $table->text('quality_flag_reason')->nullable()->after('quality_flag');
            $table->enum('quality_flag_priority', ['low', 'medium', 'high', 'critical'])->nullable()->after('quality_flag_reason');
            $table->timestamp('quality_flagged_at')->nullable()->after('quality_flag_priority');
            $table->foreignId('quality_flagged_by')->nullable()->constrained('users')->after('quality_flagged_at');
            $table->text('quality_resolution_notes')->nullable()->after('quality_flagged_by');
            $table->enum('quality_resolution_action', ['no_action', 'reassign_reviewer', 'request_revision', 'override_decision'])->nullable()->after('quality_resolution_notes');
            $table->timestamp('quality_resolved_at')->nullable()->after('quality_resolution_action');
            $table->foreignId('quality_resolved_by')->nullable()->constrained('users')->after('quality_resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropForeign(['quality_flagged_by']);
            $table->dropForeign(['quality_resolved_by']);
            $table->dropColumn([
                'quality_flag',
                'quality_flag_reason',
                'quality_flag_priority',
                'quality_flagged_at',
                'quality_flagged_by',
                'quality_resolution_notes',
                'quality_resolution_action',
                'quality_resolved_at',
                'quality_resolved_by'
            ]);
        });
    }
};
