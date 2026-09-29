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
            $table->timestamp('chair_confirmed_at')->nullable()->after('chair_notified_at');
            $table->timestamp('rapporteur_confirmed_at')->nullable()->after('rapporteur_notified_at');
            $table->string('confirmation_token', 64)->nullable()->after('rapporteur_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->dropColumn(['chair_confirmed_at', 'rapporteur_confirmed_at', 'confirmation_token']);
        });
    }
};
