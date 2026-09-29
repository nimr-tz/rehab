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
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_verification_status')->nullable()->default('pending')->after('student_document');
            $table->timestamp('student_verified_at')->nullable()->after('student_verification_status');
            $table->unsignedBigInteger('student_verified_by')->nullable()->after('student_verified_at');
            $table->text('student_verification_notes')->nullable()->after('student_verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['student_verification_status', 'student_verified_at', 'student_verified_by', 'student_verification_notes']);
        });
    }
};
