<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When the presenter of an accepted abstract was last reminded to register and pay. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstracts', function (Blueprint $table) {
            $table->timestamp('presenter_reminded_at')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('abstracts', function (Blueprint $table) {
            $table->dropColumn('presenter_reminded_at');
        });
    }
};
