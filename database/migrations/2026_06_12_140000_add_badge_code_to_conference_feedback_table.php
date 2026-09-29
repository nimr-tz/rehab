<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feedback submitted through the badge-claim flow is keyed by the badge
     * QR token, so the certificate claim can require feedback from attendees
     * who have no user account.
     */
    public function up(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->string('badge_code', 64)->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('conference_feedback', function (Blueprint $table) {
            $table->dropColumn('badge_code');
        });
    }
};
