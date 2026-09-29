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
            $table->timestamp('chair_notified_at')->nullable()->after('session_chair_id');
            $table->timestamp('rapporteur_notified_at')->nullable()->after('session_rapporteur_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->dropColumn(['chair_notified_at', 'rapporteur_notified_at']);
        });
    }
};
