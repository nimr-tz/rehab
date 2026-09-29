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
        Schema::table('invitation_letters', function (Blueprint $table) {
            $table->timestamp('downloaded_at')->nullable()->after('status');
            $table->integer('download_count')->default(0)->after('downloaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitation_letters', function (Blueprint $table) {
            $table->dropColumn(['downloaded_at', 'download_count']);
        });
    }
};
