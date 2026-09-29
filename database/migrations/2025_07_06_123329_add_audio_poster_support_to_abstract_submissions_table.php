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
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->string('audio_poster_file')->nullable()->after('presentation_status');
            $table->string('audio_poster_poster_file')->nullable()->after('audio_poster_file');
            $table->text('audio_poster_description')->nullable()->after('audio_poster_poster_file');
            $table->integer('audio_poster_duration')->nullable()->after('audio_poster_description'); // in seconds
            $table->json('audio_poster_metadata')->nullable()->after('audio_poster_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'audio_poster_file',
                'audio_poster_poster_file',
                'audio_poster_description',
                'audio_poster_duration',
                'audio_poster_metadata'
            ]);
        });
    }
};
