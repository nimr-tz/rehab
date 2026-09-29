<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->integer('poster_session_rating')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->dropColumn(['poster_session_rating']);
        });
    }
};
