<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which desk print batch a badge went out in, so a batch can be printed again. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('badge_batch', 20)->nullable()->after('badge_printed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['badge_batch']);
            $table->dropColumn('badge_batch');
        });
    }
};
