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
            $table->string('oral_presentation_file')->nullable()->after('presentation_time');
            $table->string('poster_presentation_file')->nullable()->after('oral_presentation_file');
            $table->json('presentation_files')->nullable()->after('poster_presentation_file'); // For multiple files
            $table->timestamp('presentation_uploaded_at')->nullable()->after('presentation_files');
            $table->text('presentation_notes')->nullable()->after('presentation_uploaded_at');
            $table->string('presentation_status')->default('pending')->after('presentation_notes'); // pending, uploaded, approved
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'oral_presentation_file',
                'poster_presentation_file', 
                'presentation_files',
                'presentation_uploaded_at',
                'presentation_notes',
                'presentation_status'
            ]);
        });
    }
};
