<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            // Self-declared roles on step 4 (presented / chaired).
            // Distinct from participant_type: people often held several roles.
            $table->json('roles_held')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->dropColumn('roles_held');
        });
    }
};
