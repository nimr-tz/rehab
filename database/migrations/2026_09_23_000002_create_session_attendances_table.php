<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Badge scans at the door of individual sessions. One row per attendee
     * per session; this is the evidence CPD credit is calculated from.
     */
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_session_id')->constrained('conference_sessions')->cascadeOnDelete();
            $table->string('attendee_type', 20); // user | group_member | onsite_visitor
            $table->unsignedBigInteger('attendee_id');
            $table->timestamp('scanned_at');
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['conference_session_id', 'attendee_type', 'attendee_id'], 'session_attendance_unique');
            $table->index(['attendee_type', 'attendee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
