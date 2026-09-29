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
            $table->enum('status', ['draft', 'submitted'])->default('draft')->after('review_round');
            $table->timestamp('submitted_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->dropColumn(['status', 'submitted_at']);
        });
    }
};
