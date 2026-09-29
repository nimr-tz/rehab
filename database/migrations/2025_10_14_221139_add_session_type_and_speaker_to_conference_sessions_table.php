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
            $table->enum('session_type', [
                'presentation',      // Regular parallel presentation sessions with abstracts
                'plenary',          // Plenary sessions (keynote speakers)
                'panel',            // Panel discussions
                'break',            // Tea/Coffee breaks
                'lunch',            // Lunch breaks
                'poster',           // Poster viewing sessions
                'opening',          // Opening ceremony
                'closing',          // Closing ceremony
                'discussion',       // Discussion periods
                'networking',       // Welcome reception/networking events
                'meeting',          // AGM or other meetings
                'other'             // Other session types
            ])->default('presentation')->after('name');
            
            $table->string('speaker')->nullable()->after('session_rapporteur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->dropColumn(['session_type', 'speaker']);
        });
    }
};
