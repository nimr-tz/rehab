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
        // 1. Reviewer Capacity & Load Management
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reviewer_max_load')->default(5)->after('role');
            $table->boolean('reviewer_preferences_set')->default(false)->after('reviewer_max_load');
        });

        // 2. Reviewer Interests / Subthemes
        Schema::create('reviewer_subthemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subtheme_name');
            $table->timestamps();
            
            $table->unique(['user_id', 'subtheme_name']);
        });

        // 3. Automated Tracking for Reviews
        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->timestamp('last_reminded_at')->nullable()->after('completed_at');
            $table->timestamp('assigned_at')->nullable()->after('last_reminded_at');
        });

        // 4. Manual Override for Contentious Abstracts
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->boolean('is_auto_managed')->default(true)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn('is_auto_managed');
        });

        Schema::table('abstract_reviews', function (Blueprint $table) {
            $table->dropColumn(['last_reminded_at', 'assigned_at']);
        });

        Schema::dropIfExists('reviewer_subthemes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reviewer_max_load', 'reviewer_preferences_set']);
        });
    }
};
