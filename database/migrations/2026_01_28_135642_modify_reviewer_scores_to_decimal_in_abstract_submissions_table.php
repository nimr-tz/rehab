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
            // Change reviewer scores from integer to decimal to support values like 67.5 and 100.0
            $table->decimal('reviewer_1_score', 5, 2)->nullable()->change();
            $table->decimal('reviewer_2_score', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->integer('reviewer_1_score')->nullable()->change();
            $table->integer('reviewer_2_score')->nullable()->change();
        });
    }
};
