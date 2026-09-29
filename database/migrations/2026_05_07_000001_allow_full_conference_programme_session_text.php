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
            $table->text('name')->change();
            $table->text('room_location')->nullable()->change();
            $table->text('session_chair')->nullable()->change();
            $table->text('session_rapporteur')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_sessions', function (Blueprint $table) {
            $table->string('name')->change();
            $table->string('room_location')->nullable()->change();
            $table->string('session_chair')->nullable()->change();
            $table->string('session_rapporteur')->nullable()->change();
        });
    }
};
