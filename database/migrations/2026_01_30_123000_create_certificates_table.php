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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Certificate type: attendance_full, attendance_partial, oral_presentation, poster_presentation
            $table->string('type', 50);

            // Unique certificate number: RH26-ATT-00001, RH26-ORL-00001, etc.
            $table->string('certificate_number', 50)->unique();

            // For presenter certificates - links to the abstract
            $table->foreignId('abstract_submission_id')->nullable()->constrained('abstract_submissions')->onDelete('cascade');

            // For partial attendance - which days attended (JSON array: [1, 2] or [1, 3])
            $table->json('attendance_days')->nullable();

            // Tracking timestamps
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('first_downloaded_at')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedInteger('verification_count')->default(0);

            // Revocation support
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');

            // Extra metadata (IP, browser, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'type']);
            $table->index('certificate_number');
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
