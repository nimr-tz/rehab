<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn('presentation_session');
        });
    }

    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->string('presentation_session', 100)->nullable()->after('session_order');
        });
    }
};
