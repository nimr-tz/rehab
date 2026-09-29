<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badge_name_overrides', function (Blueprint $table) {
            $table->string('print_institute')->nullable()->after('print_name');
        });
    }

    public function down(): void
    {
        Schema::table('badge_name_overrides', function (Blueprint $table) {
            $table->dropColumn('print_institute');
        });
    }
};
