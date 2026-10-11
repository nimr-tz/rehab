<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student rates need proof of enrolment, approved by finance (or seen by the
 * desk) before the fee can be paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('student_status', 12)->nullable()->after('accessibility_needs')->index(); // App\Enums\StudentProofStatus
            $table->string('student_proof_path')->nullable()->after('student_status');
            $table->foreignId('student_reviewed_by')->nullable()->after('student_proof_path')->constrained('users')->nullOnDelete();
            $table->timestamp('student_reviewed_at')->nullable()->after('student_reviewed_by');
            $table->string('student_rejection_reason')->nullable()->after('student_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_reviewed_by');
            $table->dropIndex(['student_status']);
            $table->dropColumn(['student_status', 'student_proof_path', 'student_reviewed_at', 'student_rejection_reason']);
        });
    }
};
