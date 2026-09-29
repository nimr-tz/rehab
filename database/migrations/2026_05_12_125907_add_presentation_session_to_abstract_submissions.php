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
        if (Schema::hasColumn('abstract_submissions', 'presentation_session')) {
            return;
        }

        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->string('presentation_session')->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('abstract_submissions', 'presentation_session')) {
            return;
        }

        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropColumn('presentation_session');
        });
    }
};
