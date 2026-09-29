<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->string('subtheme_relevance')->nullable()->after('recommendation');
            $table->string('suggested_subtheme')->nullable()->after('subtheme_relevance');
        });
    }

    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->dropColumn(['subtheme_relevance', 'suggested_subtheme']);
        });
    }
};
