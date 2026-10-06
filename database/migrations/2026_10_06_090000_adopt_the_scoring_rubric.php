<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the four 1-5 scores with the committee's rubric, scored in points:
 * originality /20, technical quality /40, significance /30, clarity /10.
 * Existing scores are rescaled so completed reviews keep their meaning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('score_technical')->nullable()->after('score_originality');
            $table->unsignedTinyInteger('score_significance')->nullable()->after('score_technical');
            $table->json('technical_checks')->nullable()->after('score_clarity');
        });

        DB::table('review_assignments')->whereNotNull('score_originality')->update([
            'score_technical' => DB::raw('score_methods * 8'),
            'score_significance' => DB::raw('score_relevance * 6'),
            'score_originality' => DB::raw('score_originality * 4'),
            'score_clarity' => DB::raw('score_clarity * 2'),
        ]);

        Schema::table('review_assignments', function (Blueprint $table) {
            $table->dropColumn(['score_relevance', 'score_methods']);
        });
    }

    public function down(): void
    {
        Schema::table('review_assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('score_relevance')->nullable()->after('due_on');
            $table->unsignedTinyInteger('score_methods')->nullable()->after('score_originality');
        });

        // Back to 1-5, never below 1. CASE rather than MAX/GREATEST works on SQLite and MySQL.
        $scale = fn (string $column, int $factor) => DB::raw("CASE WHEN ROUND({$column} / {$factor}.0) < 1 THEN 1 ELSE ROUND({$column} / {$factor}.0) END");

        DB::table('review_assignments')->whereNotNull('score_originality')->update([
            'score_relevance' => $scale('score_significance', 6),
            'score_methods' => $scale('score_technical', 8),
            'score_originality' => $scale('score_originality', 4),
            'score_clarity' => $scale('score_clarity', 2),
        ]);

        Schema::table('review_assignments', function (Blueprint $table) {
            $table->dropColumn(['score_technical', 'score_significance', 'technical_checks']);
        });
    }
};
