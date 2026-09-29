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
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->foreignId('session_chair_id')->nullable()->after('session_chair_email')->constrained('users')->nullOnDelete();
            $table->foreignId('session_rapporteur_id')->nullable()->after('session_rapporteur_email')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->dropForeign(['session_chair_id']);
            $table->dropForeign(['session_rapporteur_id']);
            $table->dropColumn(['session_chair_id', 'session_rapporteur_id']);
        });
    }
};
