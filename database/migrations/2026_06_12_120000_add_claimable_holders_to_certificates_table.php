<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow certificates to be held by attendees without user accounts
     * (group members and onsite walk-in visitors), claimed
     * self-service via their badge QR token.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();

            $table->foreignId('group_member_id')->nullable()->after('user_id')
                ->constrained('group_members')->nullOnDelete();
            $table->foreignId('onsite_visitor_id')->nullable()->after('group_member_id')
                ->constrained('onsite_visitors')->nullOnDelete();

            // Snapshot of the holder's name at issuance, so the certificate
            // stays stable even if the member record changes or is removed.
            $table->string('holder_name')->nullable()->after('onsite_visitor_id');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_member_id');
            $table->dropConstrainedForeignId('onsite_visitor_id');
            $table->dropColumn('holder_name');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
