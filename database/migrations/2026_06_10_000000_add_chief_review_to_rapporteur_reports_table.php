<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapporteur_reports', function (Blueprint $table) {
            // Widen the workflow: draft → submitted → under_review → needs_revision → approved.
            // Convert the enum to a plain string so new states are accepted on every driver.
            $table->string('status', 20)->default('draft')->change();

            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->timestamp('approved_at')->nullable()->after('reviewed_at');
            $table->text('chief_feedback')->nullable()->after('approved_at');
            $table->json('review_history')->nullable()->after('chief_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('rapporteur_reports', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['reviewed_by', 'reviewed_at', 'approved_at', 'chief_feedback', 'review_history']);
            $table->enum('status', ['draft', 'submitted'])->default('draft')->change();
        });
    }
};
