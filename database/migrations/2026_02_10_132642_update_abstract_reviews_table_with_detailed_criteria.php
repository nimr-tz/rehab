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
        Schema::table('abstract_reviews', function (Blueprint $table) {
            // New detailed criteria
            $table->decimal('title_score', 5, 2)->nullable()->default(0)->after('review_round');
            $table->decimal('word_count_score', 5, 2)->nullable()->default(0)->after('title_score');
            $table->decimal('writing_quality_score', 5, 2)->nullable()->default(0)->after('word_count_score');
            $table->decimal('structure_score', 5, 2)->nullable()->default(0)->after('writing_quality_score');
            $table->decimal('background_score', 5, 2)->nullable()->default(0)->after('structure_score');
            $table->decimal('rationale_score', 5, 2)->nullable()->default(0)->after('background_score');
            $table->decimal('objective_score', 5, 2)->nullable()->default(0)->after('rationale_score');
            $table->decimal('methodology_design_score', 5, 2)->nullable()->default(0)->after('objective_score');
            $table->decimal('methodology_analysis_score', 5, 2)->nullable()->default(0)->after('methodology_design_score');
            $table->decimal('results_logic_score', 5, 2)->nullable()->default(0)->after('methodology_analysis_score');
            $table->decimal('results_findings_score', 5, 2)->nullable()->default(0)->after('results_logic_score');
            $table->decimal('results_data_score', 5, 2)->nullable()->default(0)->after('results_findings_score');
            $table->decimal('conclusion_interpretation_score', 5, 2)->nullable()->default(0)->after('results_data_score');
            $table->decimal('conclusion_impact_score', 5, 2)->nullable()->default(0)->after('conclusion_interpretation_score');
            $table->decimal('relevance_theme_score', 5, 2)->nullable()->default(0)->after('conclusion_impact_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'title_score',
                'word_count_score',
                'writing_quality_score',
                'structure_score',
                'background_score',
                'rationale_score',
                'objective_score',
                'methodology_design_score',
                'methodology_analysis_score',
                'results_logic_score',
                'results_findings_score',
                'results_data_score',
                'conclusion_interpretation_score',
                'conclusion_impact_score',
                'relevance_theme_score',
            ]);
        });
    }
};
