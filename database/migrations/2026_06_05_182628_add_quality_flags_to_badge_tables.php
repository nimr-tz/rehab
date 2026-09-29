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
        Schema::table('users', function (Blueprint $table) {
            $table->json('quality_flags')->nullable()->after('badge_printed_at');
            $table->boolean('quality_flagged')->default(false)->after('quality_flags');
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->json('quality_flags')->nullable()->after('badge_printed_at');
            $table->boolean('quality_flagged')->default(false)->after('quality_flags');
        });

        if (Schema::hasTable('symposium_members')) {
            Schema::table('symposium_members', function (Blueprint $table) {
                $table->json('quality_flags')->nullable()->after('badge_printed_at');
                $table->boolean('quality_flagged')->default(false)->after('quality_flags');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['quality_flags', 'quality_flagged']);
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn(['quality_flags', 'quality_flagged']);
        });

        if (Schema::hasTable('symposium_members')) {
            Schema::table('symposium_members', function (Blueprint $table) {
                $table->dropColumn(['quality_flags', 'quality_flagged']);
            });
        }
    }
};
