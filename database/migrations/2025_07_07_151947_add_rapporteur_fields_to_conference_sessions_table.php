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
            $table->string('session_rapporteur')->nullable()->after('session_chair_email');
            $table->string('session_rapporteur_email')->nullable()->after('session_rapporteur');
            $table->text('rapporteur_notes')->nullable()->after('admin_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'session_rapporteur',
                'session_rapporteur_email', 
                'rapporteur_notes'
            ]);
        });
    }
};
