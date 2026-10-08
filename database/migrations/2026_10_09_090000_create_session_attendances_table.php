<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CPD points per programme session, set by the organisers, and attendance:
 * one row per registration and session, recorded by the staff scanning app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programme_sessions', function (Blueprint $table) {
            $table->decimal('cpd_points', 5, 2)->nullable()->after('description');
        });

        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_session_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scanned_at');
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device', 100)->nullable(); // the app's device name, for the audit trail
            $table->timestamps();

            $table->unique(['registration_id', 'programme_session_id'], 'attendance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');

        Schema::table('programme_sessions', function (Blueprint $table) {
            $table->dropColumn('cpd_points');
        });
    }
};
