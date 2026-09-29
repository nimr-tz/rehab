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
        // Institute-only overrides are valid (set print_institute without print_name),
        // so print_name must allow NULL. It was created NOT NULL with no default.
        Schema::table('badge_name_overrides', function (Blueprint $table) {
            $table->string('print_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('badge_name_overrides', function (Blueprint $table) {
            $table->string('print_name')->nullable(false)->change();
        });
    }
};
